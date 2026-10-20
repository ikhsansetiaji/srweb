<?php

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use App\Models\DeviceTokenModel;

/**
 * SuperadminFilter — cek login + role superadmin + device trusted
 *
 * Alur keamanan:
 *  1. Session harus valid & role=superadmin
 *  2. Device harus sudah ter-verifikasi (trusted cookie) ATAU baru verifikasi OTP
 *  3. Jika device baru → redirect ke halaman verifikasi OTP
 */
class SuperadminFilter implements FilterInterface
{
    /** Nama cookie device trust */
    const COOKIE_NAME = 'sa_device_token';

    public function before(RequestInterface $request, $arguments = null)
    {
        // ================================================================
        // 1. Bearer token (Mobile/API) — skip device trust check
        // ================================================================
        $authHeader = $request->getHeaderLine('Authorization');
        if (str_starts_with($authHeader, 'Bearer ')) {
            $token       = substr($authHeader, 7);
            $hashedToken = hash('sha256', $token);
            $userId      = cache()->get("api_token_val_{$hashedToken}");

            if ($userId) {
                $user = (new \App\Models\UserModel())->find($userId);
                if ($user && $user['is_active'] && $user['role'] === 'superadmin') {
                    session()->set([
                        'user_id'    => $user['id'],
                        'user_name'  => $user['name'],
                        'user_email' => $user['email'],
                        'user_role'  => $user['role'],
                        'logged_in'  => true,
                    ]);
                    return null; // OK - API akses tidak perlu device trust
                }
            }
        }

        // ================================================================
        // 2. Web session — cek login & role
        // ================================================================
        if (!session()->get('logged_in') || !session()->has('user_id')) {
            return redirect()->to('/auth/login');
        }

        if (session()->get('user_role') !== 'superadmin') {
            return $this->unauthorized($request);
        }

        $userId = (int) session()->get('user_id');

        // ================================================================
        // 3. Device Trust Check
        //    - Cek cookie `sa_device_token`
        //    - Jika ada & valid → lanjut
        //    - Jika tidak ada / invalid → redirect ke verifikasi OTP
        // ================================================================

        // Load helper cookie dan url
        helper(['cookie', 'url']);

        // Jika device sudah di-verify dalam session ini (session flag)
        if (session()->get('sa_device_verified') === true) {
            return null;
        }

        // Cek cookie
        $rawToken = null;
        if (method_exists($request, 'getCookie')) {
            $rawToken = $request->getCookie(self::COOKIE_NAME);
        }
        if (!$rawToken && function_exists('get_cookie')) {
            $rawToken = get_cookie(self::COOKIE_NAME);
        }

        if ($rawToken) {
            try {
                $deviceModel = new DeviceTokenModel();
                $record      = $deviceModel->validateToken((string) $rawToken, $userId);
                if ($record) {
                    // Device trusted - tandai di session supaya tidak cek cookie setiap request
                    session()->set('sa_device_verified', true);
                    return null;
                }
            } catch (\Throwable $e) {
                log_message('error', 'SuperadminFilter deviceToken validation error: ' . $e->getMessage());
            }
        }

        // Device belum trusted → redirect ke halaman verifikasi
        // Simpan URL tujuan asal supaya bisa redirect setelah verifikasi
        $currUrl = current_url();
        if (!str_contains($currUrl, 'verify-device')) {
            session()->set('sa_intended_url', $currUrl);
        }
        return redirect()->to('/superadmin/verify-device');
    }

    private function unauthorized(RequestInterface $request)
    {
        if ($request->isAJAX() || str_contains($request->getHeaderLine('Accept'), 'application/json')) {
            return response()->setStatusCode(403)->setJSON([
                'success' => false,
                'message' => 'Unauthorized - Superadmin access required',
            ]);
        }
        return redirect()->to('/')->with('error', 'Unauthorized access');
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
    }
}