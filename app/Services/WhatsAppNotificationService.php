<?php

namespace App\Services;

use App\Models\NotificationLogModel;
use App\Models\SongRequestModel;
use App\Models\PaymentModel;
use App\Models\UserModel;
use App\Models\CafeModel;
use App\Models\SongModel;

/**
 * WhatsAppNotificationService
 *
 * Pusat kontrol semua notifikasi WhatsApp di aplikasi Song Request Kafe Small Space.
 *
 * Tanggung jawab:
 * - Menentukan penerima yang valid (ada nomor, ada consent)
 * - Membangun template pesan
 * - Mencatat event ke log (idempotensi via unique constraint)
 * - Mendelegasikan pengiriman HTTP ke FonnteService
 * - Menangani retry dan error tanpa merusak transaksi utama
 *
 * POLA: Setiap event hanya dipanggil SETELAH transaksi database utama selesai.
 * Kegagalan notifikasi TIDAK boleh membatalkan atau mengubah data bisnis.
 *
 * IDEMPOTENSI: Setiap event memiliki idempotency_key unik.
 * Jika key sudah ada di DB → event diabaikan (tidak dikirim ulang).
 */
class WhatsAppNotificationService
{
    protected FonnteService      $fonnte;
    protected NotificationLogModel $logModel;
    protected SongRequestModel   $requestModel;
    protected PaymentModel       $paymentModel;
    protected UserModel          $userModel;
    protected CafeModel          $cafeModel;
    protected SongModel          $songModel;
    protected int                $maxRetries;
    protected int                $retryDelay;

    public function __construct()
    {
        $this->fonnte       = new FonnteService();
        $this->logModel     = new NotificationLogModel();
        $this->requestModel = new SongRequestModel();
        $this->paymentModel = new PaymentModel();
        $this->userModel    = new UserModel();
        $this->cafeModel    = new CafeModel();
        $this->songModel    = new SongModel();
        $this->maxRetries   = (int) env('FONNTE_MAX_RETRIES', 3);
        $this->retryDelay   = (int) env('FONNTE_RETRY_DELAY', 60);
    }

    // =========================================================================
    // PUBLIC API — Event triggers
    // =========================================================================

    /**
     * [A] Pembayaran berhasil.
     * Dipanggil setelah PaymentService::handlePaymentWebhook berhasil dengan status 'success'.
     *
     * @param int $paymentId
     */
    public function onPaymentSuccess(int $paymentId): void
    {
        $this->safeDispatch(function () use ($paymentId) {
            $key = "payment_success:{$paymentId}";

            $payment = $this->paymentModel->getPaymentWithDetails($paymentId);
            if (!$payment) {
                log_message('warning', "WA onPaymentSuccess: payment #{$paymentId} tidak ditemukan");
                return;
            }

            // Ambil nomor WA dari song_request
            $request = $this->requestModel->find((int)$payment['request_id']);
            if (!$request) {
                return;
            }

            $phone   = $this->getVisitorPhone($request);
            $consent = $this->getVisitorConsent($request);

            if (!$phone || !$consent) {
                $this->logSkipped($key, 'payment_success', 'visitor', 'payment', $paymentId, 'Tidak ada nomor WA atau consent');
                return;
            }

            $name   = $request['guest_name'] ?: 'Pelanggan';
            $title  = $payment['title']  ?? ($request['title']  ?? '-');
            $artist = $payment['artist'] ?? ($request['artist'] ?? '-');
            $amount = number_format((int)$payment['amount'], 0, ',', '.');

            $message = "Halo {$name}! Pembayaran request lagu kamu berhasil 🎉\n"
                . "Lagu: {$title} — {$artist}\n"
                . "Nominal: Rp{$amount}\n"
                . "Request kamu sedang diproses oleh sistem antrean Song Request Kafe Small Space. Terima kasih!";

            $this->dispatchNotification($key, 'payment_success', 'visitor', $phone, $message, 'payment', $paymentId);
        }, 'onPaymentSuccess', $paymentId);
    }

    /**
     * [B] Pembayaran gagal / kedaluwarsa / dibatalkan.
     * Hanya dipanggil untuk status yang benar-benar final (failed/expired/cancelled).
     * JANGAN dipanggil untuk status pending.
     *
     * @param int    $paymentId
     * @param string $midtransStatus  Status asli dari Midtrans
     */
    public function onPaymentFailed(int $paymentId, string $midtransStatus): void
    {
        // Jangan kirim untuk status yang masih bisa berubah
        if (in_array($midtransStatus, ['pending', 'authorize'], true)) {
            return;
        }

        $this->safeDispatch(function () use ($paymentId, $midtransStatus) {
            $key = "payment_failed:{$paymentId}:{$midtransStatus}";

            $payment = $this->paymentModel->getPaymentWithDetails($paymentId);
            if (!$payment) {
                return;
            }

            $request = $this->requestModel->find((int)$payment['request_id']);
            if (!$request) {
                return;
            }

            $phone   = $this->getVisitorPhone($request);
            $consent = $this->getVisitorConsent($request);

            if (!$phone || !$consent) {
                $this->logSkipped($key, 'payment_failed', 'visitor', 'payment', $paymentId, 'Tidak ada nomor WA atau consent');
                return;
            }

            $name  = $request['guest_name'] ?: 'Pelanggan';
            $title = $payment['title'] ?? ($request['title'] ?? '-');

            $message = "Halo {$name}, pembayaran untuk request lagu {$title} belum berhasil atau sudah kedaluwarsa.\n"
                . "Silakan periksa status transaksi di aplikasi sebelum mencoba kembali.\n"
                . "Jika pembayaran sudah terpotong, hubungi admin kafe untuk pengecekan.";

            $this->dispatchNotification($key, 'payment_failed', 'visitor', $phone, $message, 'payment', $paymentId);
        }, 'onPaymentFailed', $paymentId);
    }

    /**
     * [C] Request masuk antrean (FIFO yang langsung waiting, atau priority setelah payment success).
     * Dipanggil setelah song_request.status berubah menjadi 'waiting' secara valid.
     *
     * @param int $requestId
     */
    public function onRequestQueued(int $requestId): void
    {
        $this->safeDispatch(function () use ($requestId) {
            $key = "request_queued:{$requestId}";

            $request = $this->requestModel
                ->select('song_requests.*, songs.title, songs.artist, cafes.nama_kafe')
                ->join('songs', 'song_requests.song_id = songs.id', 'left')
                ->join('cafes', 'song_requests.cafe_id = cafes.id', 'left')
                ->find($requestId);

            if (!$request) {
                return;
            }

            // Pastikan status memang waiting sebelum mengirim notifikasi antrean
            if ($request['status'] !== 'waiting') {
                log_message('info', "WA onRequestQueued: request #{$requestId} status={$request['status']}, skip");
                return;
            }

            $phone   = $this->getVisitorPhone($request);
            $consent = $this->getVisitorConsent($request);

            if (!$phone || !$consent) {
                $this->logSkipped($key, 'request_queued', 'visitor', 'request', $requestId, 'Tidak ada nomor WA atau consent');
                return;
            }

            $name     = $request['guest_name'] ?: 'Pelanggan';
            $title    = $request['title']  ?? '-';
            $artist   = $request['artist'] ?? '-';
            $kafeName = $request['nama_kafe'] ?? 'Kafe Small Space';

            // Hitung posisi antrean (perkiraan — bisa berubah sebelum pesan terkirim)
            $position = $this->getQueuePosition($requestId, (int)$request['cafe_id']);
            $positionText = $position > 0 ? "Posisi antrean saat ini: {$position}." : '';

            $message = "Request lagu kamu berhasil masuk antrean 🎵\n"
                . "Judul: {$title} — {$artist}\n"
                . ($positionText ? $positionText . "\n" : '')
                . "Terima kasih sudah request di {$kafeName}!";

            $this->dispatchNotification($key, 'request_queued', 'visitor', $phone, $message, 'request', $requestId);
        }, 'onRequestQueued', $requestId);
    }

    /**
     * [D] Lagu mulai diputar.
     * Dipanggil setelah song_request.status berubah menjadi 'playing'.
     * Hanya valid jika ada bukti nyata dari sistem (bukan estimasi).
     *
     * @param int $requestId
     */
    public function onSongStarted(int $requestId): void
    {
        $this->safeDispatch(function () use ($requestId) {
            $key = "song_started:{$requestId}";

            $request = $this->requestModel
                ->select('song_requests.*, songs.title, songs.artist, cafes.nama_kafe')
                ->join('songs', 'song_requests.song_id = songs.id', 'left')
                ->join('cafes', 'song_requests.cafe_id = cafes.id', 'left')
                ->find($requestId);

            if (!$request) {
                return;
            }

            $phone   = $this->getVisitorPhone($request);
            $consent = $this->getVisitorConsent($request);

            if (!$phone || !$consent) {
                $this->logSkipped($key, 'song_started', 'visitor', 'request', $requestId, 'Tidak ada nomor WA atau consent');
                return;
            }

            $title    = $request['title']  ?? '-';
            $artist   = $request['artist'] ?? '-';
            $kafeName = $request['nama_kafe'] ?? 'Kafe Small Space';

            $message = "Yeay! 🎶 Lagu {$title} — {$artist} dari request kamu sekarang mulai diputar di {$kafeName}. Selamat menikmati!";

            $this->dispatchNotification($key, 'song_started', 'visitor', $phone, $message, 'request', $requestId);
        }, 'onSongStarted', $requestId);
    }

    /**
     * [E] Lagu selesai diputar.
     * Dipanggil setelah song_request.status berubah menjadi 'done'.
     * Setiap request hanya menghasilkan satu notifikasi selesai (idempotency_key unik).
     *
     * @param int $requestId
     */
    public function onSongFinished(int $requestId): void
    {
        $this->safeDispatch(function () use ($requestId) {
            $key = "song_finished:{$requestId}";

            $request = $this->requestModel
                ->select('song_requests.*, songs.title, songs.artist, cafes.nama_kafe')
                ->join('songs', 'song_requests.song_id = songs.id', 'left')
                ->join('cafes', 'song_requests.cafe_id = cafes.id', 'left')
                ->find($requestId);

            if (!$request) {
                return;
            }

            $phone   = $this->getVisitorPhone($request);
            $consent = $this->getVisitorConsent($request);

            if (!$phone || !$consent) {
                $this->logSkipped($key, 'song_finished', 'visitor', 'request', $requestId, 'Tidak ada nomor WA atau consent');
                return;
            }

            $title    = $request['title']  ?? '-';
            $artist   = $request['artist'] ?? '-';
            $kafeName = $request['nama_kafe'] ?? 'Kafe Small Space';

            $message = "Lagu request kamu, {$title} — {$artist}, sudah selesai diputar 🎵\n"
                . "Terima kasih sudah meramaikan {$kafeName}. Request lagu lagi, yuk!";

            $this->dispatchNotification($key, 'song_finished', 'visitor', $phone, $message, 'request', $requestId);
        }, 'onSongFinished', $requestId);
    }

    /**
     * [F] Status withdrawal berubah.
     * Dipanggil setelah perubahan status withdrawal berhasil disimpan ke DB.
     * Admin kafe menerima notifikasi untuk kafenya sendiri saja.
     *
     * @param int    $withdrawalId
     * @param string $newStatus     pending, approved, processing, completed, rejected, failed
     * @param string $reason        Alasan penolakan/kegagalan (untuk rejected/failed)
     */
    public function onWithdrawalStatusChanged(int $withdrawalId, string $newStatus, string $reason = ''): void
    {
        // Hanya status yang perlu dinotifikasi
        $notifiableStatuses = ['pending', 'approved', 'processing', 'completed', 'rejected', 'failed'];
        if (!in_array($newStatus, $notifiableStatuses, true)) {
            return;
        }

        $this->safeDispatch(function () use ($withdrawalId, $newStatus, $reason) {
            $key = "withdrawal_status:{$withdrawalId}:{$newStatus}";

            // Ambil withdrawal dengan info cafe dan admin
            $db = \Config\Database::connect();
            $withdrawal = $db->query(
                'SELECT w.*, c.nama_kafe, c.admin_id,
                        u.name as admin_name, u.phone_wa as admin_phone_wa, u.wa_notif_enabled
                 FROM withdrawals w
                 JOIN cafes c ON w.cafe_id = c.id
                 JOIN users u ON c.admin_id = u.id
                 WHERE w.id = ?',
                [$withdrawalId]
            )->getRowArray();

            if (!$withdrawal) {
                log_message('warning', "WA onWithdrawalStatusChanged: withdrawal #{$withdrawalId} tidak ditemukan");
                return;
            }

            $phone = $this->getAdminPhone($withdrawal);
            if (!$phone) {
                $this->logSkipped($key, 'withdrawal_status', 'admin', 'withdrawal', $withdrawalId, 'Admin tidak punya nomor WA atau notifikasi dinonaktifkan');
                return;
            }

            $adminName = $withdrawal['admin_name'] ?? 'Admin';
            $kafeName  = $withdrawal['nama_kafe']  ?? 'Kafe Small Space';
            $amount    = number_format((int)$withdrawal['amount'], 0, ',', '.');

            $message = $this->buildWithdrawalMessage($adminName, $kafeName, $amount, $newStatus, $reason);

            if ($message === null) {
                return; // Status tidak memerlukan pesan
            }

            $this->dispatchNotification($key, 'withdrawal_status', 'admin', $phone, $message, 'withdrawal', $withdrawalId);
        }, 'onWithdrawalStatusChanged', $withdrawalId);
    }

    // =========================================================================
    // RETRY WORKER — Dijalankan oleh cron/CLI command
    // =========================================================================

    /**
     * Proses notifikasi yang belum terkirim (retry worker).
     * Dipanggil dari CLI command atau cron job.
     *
     * PENTING: Worker ini idempoten — menjalankan berulang tidak akan
     * mengirim event yang sama dua kali (dijamin oleh unique constraint DB).
     *
     * @param int $limit  Maksimum notifikasi yang diproses per run
     * @return array ['processed' => int, 'sent' => int, 'failed' => int]
     */
    public function processRetryQueue(int $limit = 20): array
    {
        $pending = $this->logModel->getPendingForRetry($this->maxRetries, $this->retryDelay, $limit);

        $stats = ['processed' => 0, 'sent' => 0, 'failed' => 0];

        foreach ($pending as $notification) {
            $stats['processed']++;

            $id    = (int)$notification['id'];
            $phone = $notification['_phone'] ?? null; // Nomor sudah tersimpan terpisah (TIDAK di log)

            // Karena nomor dimasking di log, retry harus mengambil nomor dari entitas asli
            $phone = $this->resolvePhoneForRetry($notification);

            if (!$phone) {
                $this->logModel->markAsFailed($id, 'Tidak dapat menemukan nomor penerima untuk retry');
                $stats['failed']++;
                continue;
            }

            // Rebuild pesan dari entitas asli (bisa saja berbeda jika data berubah, tapi idempotency key sama)
            $message = $this->rebuildMessageForRetry($notification);
            if (!$message) {
                $this->logModel->markAsFailed($id, 'Tidak dapat membangun pesan untuk retry');
                $stats['failed']++;
                continue;
            }

            // Increment attempt sebelum mengirim
            $this->logModel->incrementAttemptCount($id);

            $result = $this->fonnte->sendMessage($phone, $message);

            if ($result['success']) {
                $this->logModel->updateAfterAttempt($id, NotificationLogModel::STATUS_SENT, [
                    'provider_message_id' => $result['provider_id'],
                    'response_code'       => $result['http_code'],
                ]);
                $stats['sent']++;
            } else {
                $currentAttempt = (int)$notification['attempt_count'] + 1;

                if ($currentAttempt >= $this->maxRetries) {
                    $this->logModel->markAsFailed($id, 'Batas retry tercapai: ' . ($result['error'] ?? 'unknown'));
                    $stats['failed']++;
                } else {
                    // Tetap pending untuk retry berikutnya
                    $this->logModel->updateAfterAttempt($id, NotificationLogModel::STATUS_PENDING, [
                        'response_code' => $result['http_code'],
                        'error_summary' => mb_substr($result['error'] ?? '', 0, 200),
                    ]);
                }
            }
        }

        return $stats;
    }

    // =========================================================================
    // PRIVATE HELPERS
    // =========================================================================

    /**
     * Core dispatcher: catat ke log, kirim ke Fonnte.
     * Semua error ditangkap — TIDAK boleh melempar exception ke caller.
     *
     * @param string $idempotencyKey
     * @param string $eventType
     * @param string $recipientType  'visitor' | 'admin'
     * @param string $phone          Nomor yang sudah dinormalisasi
     * @param string $message
     * @param string $entityType
     * @param int    $entityId
     */
    protected function dispatchNotification(
        string $idempotencyKey,
        string $eventType,
        string $recipientType,
        string $phone,
        string $message,
        string $entityType,
        int    $entityId
    ): void {
        // Normalisasi nomor
        $normalizedPhone = $this->fonnte->normalizePhone($phone);
        if ($normalizedPhone === null) {
            log_message('warning', "WA dispatchNotification: nomor tidak valid untuk event={$eventType} entity={$entityType}#{$entityId}");
            $this->logSkipped($idempotencyKey, $eventType, $recipientType, $entityType, $entityId, 'Nomor tidak valid');
            return;
        }

        $masked = NotificationLogModel::maskPhone($normalizedPhone);

        // Cek idempotensi sebelum insert
        $existing = $this->logModel->findByIdempotencyKey($idempotencyKey);
        if ($existing !== null && $existing['status'] !== NotificationLogModel::STATUS_SKIPPED) {
            // Event sudah pernah dibuat & sedang diproses/berhasil — skip
            log_message('info', "WA idempotensi skip: {$idempotencyKey} (status={$existing['status']})");
            return;
        }

        if ($existing !== null && $existing['status'] === NotificationLogModel::STATUS_SKIPPED) {
            $notifId = (int) $existing['id'];
            $this->logModel->update($notifId, [
                'recipient_masked' => $masked,
                'status'           => NotificationLogModel::STATUS_PENDING,
                'error_summary'    => null,
                'updated_at'       => date('Y-m-d H:i:s'),
            ]);
        } else {
            // Catat ke log (status pending dulu)
            $notifId = $this->logModel->createNotification([
                'idempotency_key' => $idempotencyKey,
                'event_type'      => $eventType,
                'recipient_type'  => $recipientType,
                'recipient_masked'=> $masked,
                'entity_type'     => $entityType,
                'entity_id'       => $entityId,
                'status'          => NotificationLogModel::STATUS_PENDING,
                'attempt_count'   => 0,
            ]);
        }

        if ($notifId === null) {
            // Bisa jadi race condition — idempotency_key sudah ada (concurrent request)
            log_message('info', "WA createNotification returned null (kemungkinan race condition idempotensi): {$idempotencyKey}");
            return;
        }

        // Increment attempt sebelum kirim
        $this->logModel->incrementAttemptCount($notifId);

        // Kirim ke Fonnte — jangan di dalam transaksi DB utama
        $result = $this->fonnte->sendMessage($normalizedPhone, $message);

        if ($result['success']) {
            $this->logModel->updateAfterAttempt($notifId, NotificationLogModel::STATUS_SENT, [
                'provider_message_id' => $result['provider_id'],
                'response_code'       => $result['http_code'],
            ]);
            log_message('info', "WA notifikasi terkirim: {$idempotencyKey} → {$masked}");
        } else {
            $errorSummary = mb_substr($result['error'] ?? 'Unknown', 0, 200);
            $this->logModel->updateAfterAttempt($notifId, NotificationLogModel::STATUS_PENDING, [
                'response_code' => $result['http_code'],
                'error_summary' => $errorSummary,
            ]);
            log_message('warning', "WA notifikasi gagal (akan di-retry): {$idempotencyKey} — {$errorSummary}");
        }
    }

    /**
     * Log notifikasi yang di-skip (tidak memenuhi syarat).
     * Tidak mengirim pesan apapun.
     */
    protected function logSkipped(
        string $idempotencyKey,
        string $eventType,
        string $recipientType,
        string $entityType,
        int    $entityId,
        string $reason
    ): void {
        // Skip jika sudah ada (idempotensi)
        if ($this->logModel->findByIdempotencyKey($idempotencyKey) !== null) {
            return;
        }

        $this->logModel->createNotification([
            'idempotency_key' => $idempotencyKey,
            'event_type'      => $eventType,
            'recipient_type'  => $recipientType,
            'recipient_masked'=> null,
            'entity_type'     => $entityType,
            'entity_id'       => $entityId,
            'status'          => NotificationLogModel::STATUS_SKIPPED,
            'error_summary'   => $reason,
            'attempt_count'   => 0,
        ]);
    }

    /**
     * Wrapper aman untuk semua dispatch.
     * Memastikan exception apapun dari notifikasi tidak naik ke caller.
     *
     * @param callable $fn
     * @param string   $context
     * @param int      $entityId
     */
    protected function safeDispatch(callable $fn, string $context, int $entityId): void
    {
        try {
            $fn();
        } catch (\Throwable $e) {
            log_message('error', "WhatsAppNotificationService::{$context}(#{$entityId}) exception: " . $e->getMessage());
            // TIDAK melempar ulang — transaksi utama harus tetap berjalan
        }
    }

    /**
     * Ambil nomor WA dari data song_request.
     * Mengembalikan null jika tidak ada nomor.
     *
     * @param array $request
     * @return string|null
     */
    /**
     * Parsing nilai boolean secara aman, kompatibel dengan PostgreSQL ('t'/'f', '1'/'0', true/false).
     *
     * @param mixed $val
     * @return bool
     */
    public static function parseBoolean($val): bool
    {
        if (is_bool($val)) {
            return $val;
        }
        if (is_numeric($val)) {
            return (int)$val !== 0;
        }
        if (is_string($val)) {
            $val = strtolower(trim($val));
            return in_array($val, ['1', 'true', 't', 'yes', 'on', 'y'], true);
        }
        return false;
    }

    /**
     * Ambil nomor WA dari data song_request.
     * Mengembalikan null jika tidak ada nomor.
     *
     * @param array $request
     * @return string|null
     */
    protected function getVisitorPhone(array $request): ?string
    {
        $phone = trim((string)($request['phone_wa'] ?? ''));
        if ($phone !== '') {
            return $phone;
        }

        // Fallback otomatis ke profil akun pengguna login jika ada user_id
        if (!empty($request['user_id'])) {
            $user = $this->userModel->find((int)$request['user_id']);
            if ($user) {
                $userPhoneWa = trim((string)($user['phone_wa'] ?? ''));
                if ($userPhoneWa !== '') {
                    return $userPhoneWa;
                }
                $userPhone = trim((string)($user['phone'] ?? ''));
                if ($userPhone !== '') {
                    return $userPhone;
                }
            }
        }

        return null;
    }

    /**
     * Cek consent pengunjung.
     *
     * @param array $request
     * @return bool
     */
    protected function getVisitorConsent(array $request): bool
    {
        if (isset($request['wa_consent']) && $request['wa_consent'] !== null && $request['wa_consent'] !== '') {
            return self::parseBoolean($request['wa_consent']);
        }

        // Fallback otomatis ke preferensi notifikasi akun pengguna login
        if (!empty($request['user_id'])) {
            $user = $this->userModel->find((int)$request['user_id']);
            if ($user && isset($user['wa_notif_enabled'])) {
                return self::parseBoolean($user['wa_notif_enabled']);
            }
        }

        return false;
    }

    /**
     * Ambil nomor WA admin dari data withdrawal.
     * Mengembalikan null jika tidak ada nomor atau notifikasi dinonaktifkan.
     *
     * @param array $withdrawal  Array dengan admin_phone_wa dan wa_notif_enabled
     * @return string|null
     */
    protected function getAdminPhone(array $withdrawal): ?string
    {
        $enabled = self::parseBoolean($withdrawal['wa_notif_enabled'] ?? true);
        if (!$enabled) {
            return null;
        }

        $phone = trim((string)($withdrawal['admin_phone_wa'] ?? ($withdrawal['admin_phone'] ?? '')));
        return $phone !== '' ? $phone : null;
    }

    /**
     * Hitung estimasi posisi antrean request tertentu.
     * Posisi adalah hitungan request dengan status 'waiting' yang masuk
     * sebelum request ini (berdasarkan queue_type dan nominal).
     *
     * @param int $requestId
     * @param int $cafeId
     * @return int  0 jika tidak dapat dihitung
     */
    protected function getQueuePosition(int $requestId, int $cafeId): int
    {
        try {
            $request = $this->requestModel->find($requestId);
            if (!$request) {
                return 0;
            }

            $db = \Config\Database::connect();

            if ($request['queue_type'] === 'priority') {
                // Posisi di priority queue: berapa banyak yang nominal-nya lebih besar,
                // atau nominal sama tapi requested_at lebih awal
                $result = $db->query(
                    "SELECT COUNT(*) as pos FROM song_requests
                     WHERE cafe_id = ? AND queue_type = 'priority' AND status = 'waiting'
                     AND (nominal > ? OR (nominal = ? AND requested_at < ?))",
                    [$cafeId, $request['nominal'], $request['nominal'], $request['requested_at']]
                )->getRowArray();

                return ((int)($result['pos'] ?? 0)) + 1;
            }

            // FIFO: posisi berdasarkan waktu request
            $result = $db->query(
                "SELECT COUNT(*) as pos FROM song_requests
                 WHERE cafe_id = ? AND queue_type = 'fifo' AND status = 'waiting'
                 AND requested_at < ?",
                [$cafeId, $request['requested_at']]
            )->getRowArray();

            // FIFO menyusul priority — hitung juga priority yang masih waiting
            $priorityCount = (int)($db->query(
                "SELECT COUNT(*) as cnt FROM song_requests
                 WHERE cafe_id = ? AND queue_type = 'priority' AND status = 'waiting'",
                [$cafeId]
            )->getRowArray()['cnt'] ?? 0);

            return $priorityCount + ((int)($result['pos'] ?? 0)) + 1;
        } catch (\Throwable $e) {
            log_message('warning', 'WA getQueuePosition error: ' . $e->getMessage());
            return 0;
        }
    }

    /**
     * Build pesan notifikasi withdrawal berdasarkan status.
     *
     * @param string $adminName
     * @param string $kafeName
     * @param string $amount    Sudah diformat (Rp format)
     * @param string $status
     * @param string $reason
     * @return string|null  Null jika status tidak memerlukan pesan
     */
    protected function buildWithdrawalMessage(
        string $adminName,
        string $kafeName,
        string $amount,
        string $status,
        string $reason
    ): ?string {
        switch ($status) {
            case 'pending':
                return "Halo {$adminName}, pengajuan withdrawal sebesar Rp{$amount} untuk {$kafeName} telah diterima dan sedang menunggu persetujuan superadmin.";

            case 'approved':
                return "Halo {$adminName}, pengajuan withdrawal sebesar Rp{$amount} untuk {$kafeName} telah disetujui dan sedang diproses.\n"
                    . "Status: Disetujui. Persetujuan ini belum berarti dana sudah diterima.";

            case 'processing':
                return "Halo {$adminName}, withdrawal sebesar Rp{$amount} untuk {$kafeName} sedang dalam proses transfer. Mohon menunggu konfirmasi selanjutnya.";

            case 'completed':
                return "Halo {$adminName}, withdrawal sebesar Rp{$amount} telah selesai diproses. Silakan periksa riwayat keuangan di dashboard {$kafeName}.";

            case 'rejected':
                $reasonText = $reason ? "\nAlasan: {$reason}" : '';
                return "Halo {$adminName}, pengajuan withdrawal sebesar Rp{$amount} ditolak.{$reasonText}\n"
                    . "Saldo telah dikembalikan. Silakan buka dashboard untuk melihat detailnya.";

            case 'failed':
                $reasonText = $reason ? "\nAlasan: {$reason}" : '';
                return "Halo {$adminName}, proses transfer withdrawal sebesar Rp{$amount} untuk {$kafeName} mengalami kegagalan.{$reasonText}\n"
                    . "Saldo telah dikembalikan ke akun. Silakan hubungi superadmin jika ada pertanyaan.";

            default:
                return null;
        }
    }

    /**
     * Ambil nomor telepon dari entitas asli untuk proses retry.
     * Karena nomor dimasking di log, kita perlu mengambilnya kembali dari DB.
     *
     * @param array $notification
     * @return string|null
     */
    protected function resolvePhoneForRetry(array $notification): ?string
    {
        try {
            $entityType = $notification['entity_type'] ?? '';
            $entityId   = (int)($notification['entity_id'] ?? 0);
            $recipientType = $notification['recipient_type'] ?? 'visitor';

            if ($recipientType === 'admin' && $entityType === 'withdrawal') {
                $db = \Config\Database::connect();
                $row = $db->query(
                    'SELECT u.phone_wa, u.wa_notif_enabled FROM withdrawals w
                     JOIN cafes c ON w.cafe_id = c.id
                     JOIN users u ON c.admin_id = u.id
                     WHERE w.id = ?',
                    [$entityId]
                )->getRowArray();

                if (!$row) {
                    return null;
                }

                return $this->getAdminPhone($row);
            }

            // visitor — ambil dari song_request atau payment
            if ($entityType === 'payment') {
                $payment = $this->paymentModel->find($entityId);
                if (!$payment) {
                    return null;
                }
                $request = $this->requestModel->find((int)$payment['request_id']);
            } elseif ($entityType === 'request') {
                $request = $this->requestModel->find($entityId);
            } else {
                return null;
            }

            if (!$request) {
                return null;
            }

            return $this->getVisitorConsent($request) ? $this->getVisitorPhone($request) : null;
        } catch (\Throwable $e) {
            log_message('warning', 'WA resolvePhoneForRetry error: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Rebuild pesan untuk retry berdasarkan event_type dan entity yang tersimpan di log.
     *
     * @param array $notification
     * @return string|null
     */
    protected function rebuildMessageForRetry(array $notification): ?string
    {
        try {
            $eventType = $notification['event_type'] ?? '';
            $entityId  = (int)($notification['entity_id'] ?? 0);

            switch ($eventType) {
                case 'payment_success':
                    return $this->buildPaymentSuccessMessage($entityId);
                case 'payment_failed':
                    return $this->buildPaymentFailedMessage($entityId);
                case 'request_queued':
                    return $this->buildRequestQueuedMessage($entityId);
                case 'song_started':
                    return $this->buildSongStartedMessage($entityId);
                case 'song_finished':
                    return $this->buildSongFinishedMessage($entityId);
                case 'withdrawal_status':
                    // Parse status dari idempotency_key: withdrawal_status:{id}:{status}
                    $parts = explode(':', $notification['idempotency_key'] ?? '');
                    $status = $parts[2] ?? '';
                    return $this->buildWithdrawalMessageFromId($entityId, $status);
                default:
                    return null;
            }
        } catch (\Throwable $e) {
            log_message('warning', 'WA rebuildMessageForRetry error: ' . $e->getMessage());
            return null;
        }
    }

    protected function buildPaymentSuccessMessage(int $paymentId): ?string
    {
        $payment = $this->paymentModel->getPaymentWithDetails($paymentId);
        if (!$payment) {
            return null;
        }
        $request = $this->requestModel->find((int)$payment['request_id']);
        $name   = $request['guest_name'] ?? 'Pelanggan';
        $title  = $payment['title']  ?? '-';
        $artist = $payment['artist'] ?? '-';
        $amount = number_format((int)$payment['amount'], 0, ',', '.');

        return "Halo {$name}! Pembayaran request lagu kamu berhasil 🎉\n"
            . "Lagu: {$title} — {$artist}\n"
            . "Nominal: Rp{$amount}\n"
            . "Request kamu sedang diproses oleh sistem antrean Song Request Kafe Small Space. Terima kasih!";
    }

    protected function buildPaymentFailedMessage(int $paymentId): ?string
    {
        $payment = $this->paymentModel->getPaymentWithDetails($paymentId);
        if (!$payment) {
            return null;
        }
        $request = $this->requestModel->find((int)$payment['request_id']);
        $name  = $request['guest_name'] ?? 'Pelanggan';
        $title = $payment['title'] ?? '-';

        return "Halo {$name}, pembayaran untuk request lagu {$title} belum berhasil atau sudah kedaluwarsa.\n"
            . "Silakan periksa status transaksi di aplikasi sebelum mencoba kembali.";
    }

    protected function buildRequestQueuedMessage(int $requestId): ?string
    {
        $request = $this->requestModel
            ->select('song_requests.*, songs.title, songs.artist, cafes.nama_kafe')
            ->join('songs', 'song_requests.song_id = songs.id', 'left')
            ->join('cafes', 'song_requests.cafe_id = cafes.id', 'left')
            ->find($requestId);
        if (!$request) {
            return null;
        }
        $name     = $request['guest_name'] ?? 'Pelanggan';
        $title    = $request['title']  ?? '-';
        $artist   = $request['artist'] ?? '-';
        $kafeName = $request['nama_kafe'] ?? 'Kafe Small Space';

        return "Request lagu kamu berhasil masuk antrean 🎵\n"
            . "Judul: {$title} — {$artist}\n"
            . "Terima kasih sudah request di {$kafeName}!";
    }

    protected function buildSongStartedMessage(int $requestId): ?string
    {
        $request = $this->requestModel
            ->select('song_requests.*, songs.title, songs.artist, cafes.nama_kafe')
            ->join('songs', 'song_requests.song_id = songs.id', 'left')
            ->join('cafes', 'song_requests.cafe_id = cafes.id', 'left')
            ->find($requestId);
        if (!$request) {
            return null;
        }
        $title    = $request['title']  ?? '-';
        $artist   = $request['artist'] ?? '-';
        $kafeName = $request['nama_kafe'] ?? 'Kafe Small Space';

        return "Yeay! 🎶 Lagu {$title} — {$artist} dari request kamu sekarang mulai diputar di {$kafeName}. Selamat menikmati!";
    }

    protected function buildSongFinishedMessage(int $requestId): ?string
    {
        $request = $this->requestModel
            ->select('song_requests.*, songs.title, songs.artist, cafes.nama_kafe')
            ->join('songs', 'song_requests.song_id = songs.id', 'left')
            ->join('cafes', 'song_requests.cafe_id = cafes.id', 'left')
            ->find($requestId);
        if (!$request) {
            return null;
        }
        $title    = $request['title']  ?? '-';
        $artist   = $request['artist'] ?? '-';
        $kafeName = $request['nama_kafe'] ?? 'Kafe Small Space';

        return "Lagu request kamu, {$title} — {$artist}, sudah selesai diputar 🎵\n"
            . "Terima kasih sudah meramaikan {$kafeName}. Request lagu lagi, yuk!";
    }

    protected function buildWithdrawalMessageFromId(int $withdrawalId, string $status): ?string
    {
        $db = \Config\Database::connect();
        $row = $db->query(
            'SELECT w.amount, w.rejection_reason, w.failure_reason, c.nama_kafe, u.name as admin_name
             FROM withdrawals w
             JOIN cafes c ON w.cafe_id = c.id
             JOIN users u ON c.admin_id = u.id
             WHERE w.id = ?',
            [$withdrawalId]
        )->getRowArray();

        if (!$row) {
            return null;
        }

        $reason = $row['rejection_reason'] ?? $row['failure_reason'] ?? '';
        $amount = number_format((int)$row['amount'], 0, ',', '.');

        return $this->buildWithdrawalMessage(
            $row['admin_name'] ?? 'Admin',
            $row['nama_kafe']  ?? 'Kafe',
            $amount,
            $status,
            $reason
        );
    }
}
