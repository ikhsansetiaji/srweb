<?php

namespace App\Controllers;

use App\Models\UserModel;
use App\Models\DeviceTokenModel;
use App\Models\SuperadminOtpModel;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Psr\Log\LoggerInterface;

/**
 * SuperadminDeviceController
 *
 * Mengelola verifikasi device untuk superadmin.
 * Alur:
 *   GET  /superadmin/verify-device  → tampilkan form verifikasi
 *   POST /superadmin/send-otp       → kirim OTP via email
 *   POST /superadmin/verify-otp     → verifikasi OTP & trust device
 */
class SuperadminDeviceController extends BaseController
{
    protected $helpers = ['cookie', 'url', 'form'];

    protected UserModel        $userModel;
    protected DeviceTokenModel $deviceModel;
    protected SuperadminOtpModel $otpModel;

    public function initController(RequestInterface $request, ResponseInterface $response, LoggerInterface $logger)
    {
        parent::initController($request, $response, $logger);
        helper(['cookie', 'url', 'form']);
        $this->userModel   = new UserModel();
        $this->deviceModel = new DeviceTokenModel();
        $this->otpModel    = new SuperadminOtpModel();
    }

    // =========================================================================
    // Halaman Verifikasi Device
    // =========================================================================

    /**
     * GET /superadmin/verify-device
     * Tampilkan halaman verifikasi OTP device baru.
     */
    public function verifyDevicePage()
    {
        // Harus sudah login sebagai superadmin
        if (!$this->isSuperadminSession()) {
            return redirect()->to('/auth/login');
        }

        $user = $this->userModel->find(session()->get('user_id'));
        return view('superadmin/verify_device', [
            'user'       => $user,
            'userEmail'  => $this->maskEmail($user['email'] ?? ''),
        ]);
    }

    /**
     * POST /superadmin/send-otp
     * Kirim OTP ke email superadmin.
     */
    public function sendOtp()
    {
        if (!$this->isSuperadminSession()) {
            return $this->response->setStatusCode(401)->setJSON(['success' => false, 'message' => 'Unauthorized']);
        }

        $userId = (int) session()->get('user_id');
        $user   = $this->userModel->find($userId);

        if (!$user) {
            return $this->response->setStatusCode(404)->setJSON(['success' => false, 'message' => 'User tidak ditemukan']);
        }

        // Batasi rate: max 1 OTP per 1 menit
        $cacheKey  = "sa_otp_rate_{$userId}";
        if (cache()->get($cacheKey)) {
            return $this->response->setStatusCode(429)->setJSON([
                'success' => false,
                'message' => 'Tunggu 1 menit sebelum meminta OTP baru.',
            ]);
        }

        // Generate OTP di database
        $ip = $this->request->getIPAddress();
        try {
            $otp = $this->otpModel->generateOtp($userId, $ip);
        } catch (\Throwable $e) {
            log_message('error', 'SuperadminDeviceController::generateOtp error: ' . $e->getMessage());
            return $this->response->setStatusCode(500)->setJSON([
                'success' => false,
                'message' => 'Gagal memproses OTP di database (pastikan tabel superadmin_otp_codes sudah dibuat di database VPS): ' . $e->getMessage(),
            ]);
        }

        // Set rate limit cache (60 detik)
        cache()->save($cacheKey, 1, 60);

        // Kirim via email
        try {
            $this->sendOtpEmail($user, $otp);
        } catch (\Throwable $e) {
            log_message('error', 'SuperadminDeviceController::sendOtp email error: ' . $e->getMessage());
            cache()->delete($cacheKey);
            return $this->response->setStatusCode(500)->setJSON([
                'success' => false,
                'message' => 'Gagal mengirim OTP: ' . $e->getMessage(),
            ]);
        }

        log_message('info', "Superadmin OTP sent to user #{$userId} ({$user['email']}) from IP {$ip}");

        return $this->response->setJSON([
            'success' => true,
            'message' => 'OTP berhasil dikirim ke email ' . $this->maskEmail($user['email']),
        ]);
    }

    /**
     * POST /superadmin/verify-otp
     * Verifikasi OTP dan trust device ini.
     */
    public function verifyOtp()
    {
        if (!$this->isSuperadminSession()) {
            return $this->response->setStatusCode(401)->setJSON(['success' => false, 'message' => 'Unauthorized']);
        }

        $json   = $this->request->getJSON(true);
        $otp    = trim((string) ($this->request->getPost('otp') ?? $json['otp'] ?? ''));
        $trust  = (bool) ($this->request->getPost('trust_device') ?? $json['trust_device'] ?? true);

        if (!$otp || strlen($otp) !== 6 || !ctype_digit($otp)) {
            return $this->response->setStatusCode(422)->setJSON([
                'success' => false,
                'message' => 'OTP harus 6 digit angka.',
            ]);
        }

        $userId = (int) session()->get('user_id');

        // Verifikasi OTP
        if (!$this->otpModel->verifyOtp($userId, $otp)) {
            log_message('warning', "Superadmin OTP verification failed for user #{$userId}");
            return $this->response->setStatusCode(401)->setJSON([
                'success' => false,
                'message' => 'Kode OTP salah atau sudah expired. Coba minta OTP baru.',
            ]);
        }

        // Tandai device verified di session
        session()->set('sa_device_verified', true);

        // Jika user minta trust device ini (30 hari)
        if ($trust) {
            $ua       = $this->request->getUserAgent()->getAgentString();
            $ip       = $this->request->getIPAddress();
            $rawToken = $this->deviceModel->createToken($userId, $ua, $ip);

            // Simpan token di cookie (HttpOnly, Secure, SameSite=Strict, 30 hari)
            helper('cookie');
            $this->response->setCookie([
                'name'     => \App\Filters\SuperadminFilter::COOKIE_NAME,
                'value'    => $rawToken,
                'expire'   => 30 * 24 * 3600,
                'secure'   => (ENVIRONMENT === 'production'),
                'httponly' => true,
                'samesite' => 'Strict',
                'path'     => '/',
            ]);
            if (function_exists('set_cookie')) {
                set_cookie([
                    'name'     => \App\Filters\SuperadminFilter::COOKIE_NAME,
                    'value'    => $rawToken,
                    'expire'   => 30 * 24 * 3600,
                    'secure'   => (ENVIRONMENT === 'production'),
                    'httponly' => true,
                    'samesite' => 'Strict',
                    'path'     => '/',
                ]);
            }
        }

        // Redirect ke URL yang dimaksud, default ke dashboard
        $intendedUrl = session()->get('sa_intended_url') ?? '/superadmin/dashboard';
        session()->remove('sa_intended_url');

        log_message('info', "Superadmin device verified for user #{$userId}, trust={$trust}");

        return $this->response->setJSON([
            'success'  => true,
            'message'  => 'Verifikasi berhasil!',
            'redirect' => $intendedUrl,
        ]);
    }

    /**
     * POST /superadmin/revoke-devices
     * Revoke semua device trusted (logout dari semua perangkat).
     */
    public function revokeAllDevices()
    {
        if (!$this->isSuperadminSession()) {
            return $this->response->setStatusCode(401)->setJSON(['success' => false, 'message' => 'Unauthorized']);
        }

        $userId = (int) session()->get('user_id');
        $this->deviceModel->revokeAll($userId);

        // Reset session flag & hapus cookie
        session()->remove('sa_device_verified');
        helper('cookie');
        $this->response->deleteCookie(\App\Filters\SuperadminFilter::COOKIE_NAME, '', '/');
        if (function_exists('delete_cookie')) {
            delete_cookie(\App\Filters\SuperadminFilter::COOKIE_NAME, '', '/');
        }

        return $this->response->setJSON([
            'success' => true,
            'message' => 'Semua perangkat terpercaya telah dicabut. Login ulang diperlukan.',
        ]);
    }

    // =========================================================================
    // Helper
    // =========================================================================

    private function isSuperadminSession(): bool
    {
        return session()->get('logged_in') === true
            && session()->get('user_role') === 'superadmin';
    }

    private function maskEmail(string $email): string
    {
        if (!$email || !str_contains($email, '@')) {
            return '***@***.***';
        }
        [$local, $domain] = explode('@', $email, 2);
        $masked = substr($local, 0, 2) . str_repeat('*', max(0, strlen($local) - 3)) . substr($local, -1);
        return $masked . '@' . $domain;
    }

    /**
     * Kirim OTP via email menggunakan layanan email Brevo / NotificationEmailService.
     */
    private function sendOtpEmail(array $user, string $otp): void
    {
        $lastError = null;

        // 1. Coba via NotificationEmailService (Brevo API v3 terpusat)
        try {
            $emailService = service('notificationEmail');
            if ($emailService && $emailService->isConfigured()) {
                $result = $emailService->sendSuperadminDeviceOtp(
                    $user['email'],
                    $user['name'] ?? 'Superadmin',
                    $otp
                );

                if (!($result['success'] ?? false)) {
                    $lastError = $result['message'] ?? 'Gagal mengirim email OTP via Brevo';
                    log_message('error', 'SuperadminDeviceController Brevo error: ' . $lastError);
                    // Jika Brevo menolak karena IP belum di-whitelist atau error auth, lempar langsung agar user tahu
                    if (str_contains($lastError, 'unrecognised IP address') || str_contains($lastError, 'authorised_ips') || ($result['http_code'] ?? 0) === 401) {
                        throw new \RuntimeException($lastError);
                    }
                } else {
                    return;
                }
            } else {
                $lastError = 'Layanan Brevo belum terkonfigurasi (periksa BREVO_API_KEY di file .env server)';
            }
        } catch (\Throwable $e) {
            log_message('warning', 'SuperadminDeviceController Brevo failed: ' . $e->getMessage());
            if (str_contains($e->getMessage(), 'unrecognised IP address') || str_contains($e->getMessage(), 'authorised_ips')) {
                throw $e;
            }
            $lastError = $e->getMessage();
        }

        // 2. Fallback: coba mailer default CI4 jika Brevo tidak ada / gagal
        try {
            $email = \Config\Services::email();
            $email->setFrom(env('email.from', 'noreply@songreq.app'), env('email.fromName', 'SongReq'));
            $email->setTo($user['email']);
            $email->setSubject('[SongReq] Kode Verifikasi Device Superadmin');
            $email->setMessage($this->buildOtpEmailBody($user['name'] ?? 'Superadmin', $otp));
            $email->setMailType('html');

            if ($email->send(false)) {
                return;
            }
        } catch (\Throwable $e) {
            log_message('error', 'SuperadminDeviceController fallback email error: ' . $e->getMessage());
        }

        throw new \RuntimeException($lastError ?: 'Gagal mengirim email OTP. Pastikan konfigurasi email di .env sudah benar.');
    }

    private function buildOtpEmailBody(string $name, string $otp): string
    {
        $expiry = date('H:i', strtotime('+10 minutes'));
        return <<<HTML
<!DOCTYPE html>
<html>
<head><meta charset="UTF-8"></head>
<body style="font-family: Arial, sans-serif; background:#f4f6fb; margin:0; padding:20px;">
  <div style="max-width:480px; margin:0 auto; background:#fff; border-radius:12px; overflow:hidden; box-shadow:0 2px 12px rgba(0,0,0,0.08);">
    <div style="background:linear-gradient(135deg,#1a1a2e,#e53935); padding:28px 32px; text-align:center;">
      <h1 style="color:#fff; margin:0; font-size:1.4rem;">🔐 Verifikasi Device</h1>
      <p style="color:rgba(255,255,255,0.8); margin:8px 0 0; font-size:0.85rem;">SongReq Superadmin Panel</p>
    </div>
    <div style="padding:32px;">
      <p style="color:#374151; margin:0 0 16px;">Halo <strong>{$name}</strong>,</p>
      <p style="color:#6b7280; font-size:0.9rem; margin:0 0 24px;">
        Ada percobaan login ke panel Superadmin dari perangkat baru. Gunakan kode OTP berikut untuk memverifikasi perangkat Anda:
      </p>
      <div style="text-align:center; margin:24px 0;">
        <div style="display:inline-block; background:#f4f6fb; border:2px dashed #e53935; border-radius:12px; padding:16px 40px;">
          <span style="font-size:2.4rem; font-weight:800; letter-spacing:0.3em; color:#1a1a2e; font-family:monospace;">{$otp}</span>
        </div>
        <p style="color:#9ca3af; font-size:0.78rem; margin:10px 0 0;">Berlaku hingga pukul <strong>{$expiry}</strong> (10 menit)</p>
      </div>
      <div style="background:#fff7ed; border:1px solid #fed7aa; border-radius:8px; padding:12px 16px; margin-top:16px;">
        <p style="color:#92400e; font-size:0.82rem; margin:0;">
          ⚠️ <strong>Jangan bagikan kode ini kepada siapapun!</strong> Jika Anda tidak melakukan login ini, segera hubungi tim teknis.
        </p>
      </div>
    </div>
    <div style="background:#f9fafb; padding:16px 32px; text-align:center; border-top:1px solid #f3f4f6;">
      <p style="color:#9ca3af; font-size:0.75rem; margin:0;">SongReq &copy; 2026 — Email otomatis, jangan dibalas.</p>
    </div>
  </div>
</body>
</html>
HTML;
    }
}
