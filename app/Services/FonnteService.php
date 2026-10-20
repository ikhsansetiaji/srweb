<?php

namespace App\Services;

use CodeIgniter\HTTP\CURLRequest;

/**
 * FonnteService — HTTP client untuk Fonnte WhatsApp API.
 *
 * Bertanggung jawab HANYA untuk komunikasi HTTP dengan endpoint Fonnte.
 * Tidak mengandung logika bisnis, template pesan, atau keputusan siapa penerima.
 *
 * KEAMANAN:
 * - Token Fonnte dibaca dari environment, tidak pernah di-log atau dikembalikan ke response.
 * - Semua error dicatat tanpa menyertakan token.
 *
 * REFERENSI: https://docs.fonnte.com/
 */
class FonnteService
{
    protected string $token;
    protected string $apiUrl;
    protected int    $timeout;
    protected bool   $enabled;

    public function __construct()
    {
        $this->enabled = filter_var(
            env('FONNTE_ENABLED', 'false'),
            FILTER_VALIDATE_BOOLEAN
        );

        $this->token   = trim((string) env('FONNTE_TOKEN', ''));
        $this->apiUrl  = trim((string) env('FONNTE_API_URL', 'https://api.fonnte.com/send'));
        $this->timeout = (int) env('FONNTE_TIMEOUT', 10);
    }

    /**
     * Apakah Fonnte dikonfigurasi dan diaktifkan.
     *
     * @return bool
     */
    public function isEnabled(): bool
    {
        return $this->enabled && $this->token !== '';
    }

    /**
     * Kirim pesan WhatsApp ke satu nomor.
     *
     * @param string $phone  Nomor tujuan format internasional (628xxx)
     * @param string $message Isi pesan (plain text)
     * @return array ['success' => bool, 'status' => string, 'provider_id' => string|null, 'http_code' => int, 'error' => string|null]
     */
    public function sendMessage(string $phone, string $message): array
    {
        if (!$this->isEnabled()) {
            return [
                'success'     => false,
                'status'      => 'skipped',
                'provider_id' => null,
                'http_code'   => 0,
                'error'       => 'Fonnte tidak aktif atau token belum dikonfigurasi',
            ];
        }

        if (empty($phone) || empty($message)) {
            return [
                'success'     => false,
                'status'      => 'skipped',
                'provider_id' => null,
                'http_code'   => 0,
                'error'       => 'Nomor atau pesan kosong',
            ];
        }

        // Normalisasi nomor
        $normalizedPhone = $this->normalizePhone($phone);
        if ($normalizedPhone === null) {
            return [
                'success'     => false,
                'status'      => 'skipped',
                'provider_id' => null,
                'http_code'   => 0,
                'error'       => "Nomor tidak valid: format tidak dikenali",
            ];
        }

        $payload = [
            'target'  => $normalizedPhone,
            'message' => $message,
        ];

        return $this->doSend($payload);
    }

    /**
     * Eksekusi HTTP request ke Fonnte.
     * Menggunakan cURL secara langsung (seperti pola existing di project ini).
     *
     * Token TIDAK boleh muncul di log apapun.
     *
     * @param array $payload
     * @return array
     */
    protected function doSend(array $payload): array
    {
        $ch = curl_init($this->apiUrl);
        if ($ch === false) {
            log_message('error', 'FonnteService: curl_init gagal');
            return $this->errorResult(0, 'Gagal menyiapkan koneksi HTTP');
        }

        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => $payload, // Form data, bukan JSON
            CURLOPT_HTTPHEADER     => [
                // Token dikirim di header Authorization — tidak di log
                'Authorization: ' . $this->token,
            ],
            CURLOPT_CONNECTTIMEOUT => min($this->timeout, 10),
            CURLOPT_TIMEOUT        => $this->timeout,
            // Sesuaikan SSL verification dengan env
            CURLOPT_SSL_VERIFYPEER => (env('CI_ENVIRONMENT') === 'production'),
            CURLOPT_SSL_VERIFYHOST => (env('CI_ENVIRONMENT') === 'production') ? 2 : 0,
        ]);

        $response  = curl_exec($ch);
        $httpCode  = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        // cURL error (koneksi timeout, DNS, dsb)
        if ($curlError !== '') {
            log_message('warning', "FonnteService: cURL error (HTTP {$httpCode}): {$curlError}");
            return $this->errorResult($httpCode, "cURL error: {$curlError}");
        }

        $decoded = null;
        if ($response !== false && $response !== '') {
            $decoded = json_decode((string) $response, true);
        }

        // Referensi response Fonnte: https://docs.fonnte.com/
        // HTTP 200 + status: true = berhasil diterima Fonnte (bukan berarti WA sudah diterima user)
        if ($httpCode === 200 && isset($decoded['status']) && $decoded['status'] === true) {
            $rawId = $decoded['id'] ?? null;
            if (is_array($rawId)) {
                $providerId = !empty($rawId) ? (string) reset($rawId) : null;
            } elseif ($rawId !== null) {
                $providerId = (string) $rawId;
            } else {
                $providerId = null;
            }

            log_message('info', "FonnteService: pesan diterima Fonnte. provider_id=" . ($providerId ?? 'null'));
            return [
                'success'     => true,
                'status'      => 'sent',
                'provider_id' => $providerId,
                'http_code'   => $httpCode,
                'error'       => null,
            ];
        }

        // Respons tidak sukses — log ringkasan tanpa token
        $rawReason = $decoded['reason'] ?? ($decoded['detail'] ?? null);
        $reason = is_array($rawReason) ? json_encode($rawReason) : (string) ($rawReason ?? '');
        $errorMsg = "HTTP {$httpCode}";
        if ($reason !== '') {
            $errorMsg .= ": {$reason}";
        }

        // HTTP 401/403 = token invalid atau device belum terkoneksi
        if (in_array($httpCode, [401, 403])) {
            log_message('error', "FonnteService: autentikasi gagal (HTTP {$httpCode}) — periksa FONNTE_TOKEN dan status perangkat WhatsApp di dashboard Fonnte");
        } else {
            log_message('warning', "FonnteService: pengiriman gagal — {$errorMsg}");
        }

        return $this->errorResult($httpCode, $errorMsg);
    }

    /**
     * Normalisasi nomor ke format internasional Indonesia (62xxx).
     * Menolak nomor yang jelas tidak valid.
     *
     * @param string $phone
     * @return string|null  Null jika tidak valid
     */
    public function normalizePhone(string $phone): ?string
    {
        // Hilangkan semua karakter non-digit
        $phone = preg_replace('/[^0-9]/', '', $phone);

        if (empty($phone)) {
            return null;
        }

        // Awali dengan 0 → ganti dengan 62
        if (str_starts_with($phone, '0')) {
            $phone = '62' . substr($phone, 1);
        }

        // Awali dengan 62 → sudah benar
        if (str_starts_with($phone, '62')) {
            // Minimal 10 digit setelah 62 (08xx = 10 digit)
            if (strlen($phone) < 10 || strlen($phone) > 15) {
                return null;
            }
            return $phone;
        }

        // Format lain tidak didukung
        return null;
    }

    /**
     * Build error result array.
     *
     * @param int    $httpCode
     * @param string $error
     * @return array
     */
    protected function errorResult(int $httpCode, string $error): array
    {
        return [
            'success'     => false,
            'status'      => 'failed',
            'provider_id' => null,
            'http_code'   => $httpCode,
            'error'       => $error,
        ];
    }
}
