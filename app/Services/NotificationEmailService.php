<?php

namespace App\Services;

use App\Models\CafeModel;
use App\Models\EmailNotificationLogModel;
use App\Models\PaymentModel;
use App\Models\SongRequestModel;
use App\Models\UserModel;
use App\Models\WithdrawalModel;
use Config\App as AppConfig;

/**
 * NotificationEmailService
 *
 * Layanan terpusat untuk notifikasi email berbasis Brevo API v3.
 * Mengelola:
 * - Notifikasi request & status lagu (waiting, playing, done, cancelled)
 * - Notifikasi pembayaran (success, failed, expired)
 * - Notifikasi penarikan saldo kafe (pending, approved, rejected, completed)
 * - Notifikasi persetujuan admin & superadmin (pendaftaran, persetujuan, penolakan, status akun)
 * - Idempotensi & audit log via EmailNotificationLogModel
 * - Template HTML responsif dengan identitas visual Song Request (#F4EFEA / #E05A47)
 */
class NotificationEmailService
{
    private const ENDPOINT = 'https://api.brevo.com/v3/smtp/email';

    protected ?string $apiKey;
    protected string $fromEmail;
    protected string $fromName;
    protected bool $verifySsl;

    protected UserModel $userModel;
    protected SongRequestModel $requestModel;
    protected PaymentModel $paymentModel;
    protected WithdrawalModel $withdrawalModel;
    protected CafeModel $cafeModel;
    protected EmailNotificationLogModel $logModel;

    public function __construct()
    {
        $this->apiKey = trim((string) (env('BREVO_API_KEY', getenv('BREVO_API_KEY')) ?: env('RESEND_API_KEY', getenv('RESEND_API_KEY'))));
        $this->fromEmail = trim((string) (env('BREVO_FROM_EMAIL', getenv('BREVO_FROM_EMAIL')) ?: env('RESEND_FROM_EMAIL', getenv('RESEND_FROM_EMAIL'))));
        $this->fromName = trim((string) (env('BREVO_FROM_NAME', getenv('BREVO_FROM_NAME')) ?: env('RESEND_FROM_NAME', getenv('RESEND_FROM_NAME')))) ?: 'Song Request';
        $this->verifySsl = filter_var(
            env('BREVO_VERIFY_SSL', getenv('BREVO_VERIFY_SSL') !== false ? getenv('BREVO_VERIFY_SSL') : (env('RESEND_VERIFY_SSL', getenv('RESEND_VERIFY_SSL')) !== false ? getenv('RESEND_VERIFY_SSL') : (env('CI_ENVIRONMENT') === 'production' ? 'true' : 'false'))),
            FILTER_VALIDATE_BOOL
        );

        $this->userModel       = new UserModel();
        $this->requestModel    = new SongRequestModel();
        $this->paymentModel    = new PaymentModel();
        $this->withdrawalModel = new WithdrawalModel();
        $this->cafeModel       = new CafeModel();
        $this->logModel        = new EmailNotificationLogModel();
    }

    public function isConfigured(): bool
    {
        return $this->apiKey !== '' && $this->fromEmail !== '';
    }

    // =========================================================================
    // 1. NOTIFIKASI REQUEST & STATUS LAGU
    // =========================================================================

    /**
     * A. Request lagu berhasil diterima ke antrean.
     */
    public function onSongRequestCreated(int $requestId): void
    {
        $this->safeDispatch(function () use ($requestId) {
            $key = "request_created:{$requestId}";

            $request = $this->requestModel->select('song_requests.*, songs.title, songs.artist, cafes.nama_kafe')
                ->join('songs', 'song_requests.song_id = songs.id', 'left')
                ->join('cafes', 'song_requests.cafe_id = cafes.id', 'left')
                ->find($requestId);

            if (!$request) {
                return;
            }

            $user = !empty($request['user_id']) ? $this->userModel->find((int)$request['user_id']) : null;
            $email = $this->extractRecipientEmail($user);

            // Jika anonim atau tidak memiliki email, jangan kirim email
            if (!$email) {
                $this->recordSkipped($key, 'request_created', 'request', $requestId, 'Pengguna anonim atau email tidak tersedia');
                return;
            }

            $name = $this->extractName($user, $request['guest_name'] ?? '');
            $title = (string)($request['title'] ?? 'Lagu Tanpa Judul');
            $artist = (string)($request['artist'] ?? 'Artis Tidak Diketahui');
            $kafeName = (string)($request['nama_kafe'] ?? 'Kafe');
            $queueType = strtoupper((string)($request['queue_type'] ?? 'FIFO'));
            $status = (string)($request['status'] ?? 'waiting');
            $requestedAt = (string)($request['requested_at'] ?? date('Y-m-d H:i:s'));

            $isPendingPayment = ($status === 'pending_payment');
            $subject = $isPendingPayment
                ? "Menunggu Pembayaran: Request Lagu {$title} — Song Request"
                : "Request Diterima: Lagu {$title} — Song Request";

            $badgeLabel = $isPendingPayment ? 'Menunggu Pembayaran' : 'Masuk Antrean';
            $badgeType  = $isPendingPayment ? 'pending' : 'waiting';

            $leadMessage = $isPendingPayment
                ? "Request lagu kamu telah tercatat di sistem kami dan sedang menunggu penyelesaian pembayaran."
                : "Request lagu kamu sudah berhasil diterima dan masuk ke dalam sistem antrean kafe. <strong>Pemberitahuan ini menandakan lagu telah tercatat dalam antrean, bukan berarti lagu langsung mulai diputar.</strong>";

            $rows = [
                ['label' => 'Judul Lagu', 'value' => $title],
                ['label' => 'Artis', 'value' => $artist],
                ['label' => 'Kafe', 'value' => $kafeName],
                ['label' => 'Waktu Request', 'value' => $this->formatDate($requestedAt)],
                ['label' => 'Mode Antrean', 'value' => $queueType],
                ['label' => 'Status', 'value' => $badgeLabel],
            ];

            if ((int)($request['nominal'] ?? 0) > 0) {
                $rows[] = ['label' => 'Nominal Dukungan', 'value' => 'Rp ' . number_format((int)$request['nominal'], 0, ',', '.')];
            }

            $ctaUrl = $isPendingPayment
                ? $this->siteUrl("/payment/checkout?request_id={$requestId}")
                : $this->siteUrl("/user/history");
            $ctaText = $isPendingPayment ? 'Lanjutkan Pembayaran' : 'Pantau Antrean Request';

            $html = $this->renderTemplate([
                'title'       => $isPendingPayment ? 'Konfirmasi Request Lagu (Menunggu Pembayaran)' : 'Request Lagu Berhasil Diterima',
                'name'        => $name,
                'lead'        => $leadMessage,
                'badge'       => ['label' => $badgeLabel, 'type' => $badgeType],
                'rows'        => $rows,
                'cta'         => ['text' => $ctaText, 'url' => $ctaUrl],
                'footer_note' => 'Kamu bisa memantau status antrean lagu ini kapan saja melalui dashboard pengguna.',
            ]);

            $text = "Halo {$name},\n\n"
                . strip_tags($leadMessage) . "\n\n"
                . "Detail Request:\n"
                . "- Lagu: {$title} — {$artist}\n"
                . "- Kafe: {$kafeName}\n"
                . "- Waktu: {$requestedAt}\n"
                . "- Mode: {$queueType}\n"
                . "- Status: {$badgeLabel}\n\n"
                . "Lihat detail: {$ctaUrl}\n";

            $this->dispatchEmail($key, 'request_created', 'request', $requestId, $email, $name, $subject, $html, $text);
        }, 'onSongRequestCreated', $requestId);
    }

    /**
     * B. Status lagu berubah (playing, done, cancelled).
     */
    public function onSongStatusChanged(int $requestId, string $newStatus): void
    {
        $allowedStatuses = ['playing', 'done', 'cancelled'];
        if (!in_array($newStatus, $allowedStatuses, true)) {
            return;
        }

        $this->safeDispatch(function () use ($requestId, $newStatus) {
            $key = "song_status:{$requestId}:{$newStatus}";

            $request = $this->requestModel->select('song_requests.*, songs.title, songs.artist, cafes.nama_kafe')
                ->join('songs', 'song_requests.song_id = songs.id', 'left')
                ->join('cafes', 'song_requests.cafe_id = cafes.id', 'left')
                ->find($requestId);

            if (!$request) {
                return;
            }

            $user = !empty($request['user_id']) ? $this->userModel->find((int)$request['user_id']) : null;
            $email = $this->extractRecipientEmail($user);

            if (!$email) {
                $this->recordSkipped($key, "song_{$newStatus}", 'request', $requestId, 'Pengguna anonim atau email tidak tersedia');
                return;
            }

            $name = $this->extractName($user, $request['guest_name'] ?? '');
            $title = (string)($request['title'] ?? 'Lagu Tanpa Judul');
            $artist = (string)($request['artist'] ?? 'Artis Tidak Diketahui');
            $kafeName = (string)($request['nama_kafe'] ?? 'Kafe');

            switch ($newStatus) {
                case 'playing':
                    $subject = "🎵 Lagu Diputar: {$title} — {$kafeName}";
                    $headline = "Lagu Kamu Sedang Diputar!";
                    $leadMessage = "Kabar gembira! Lagu <strong>{$this->escapeHtml($title)}</strong> oleh <strong>{$this->escapeHtml($artist)}</strong> sedang diputar sekarang di {$this->escapeHtml($kafeName)}.";
                    $badgeLabel = 'Sedang Diputar';
                    $badgeType = 'playing';
                    break;
                case 'done':
                    $subject = "✅ Lagu Selesai Diputar: {$title} — {$kafeName}";
                    $headline = "Lagu Selesai Diputar";
                    $leadMessage = "Lagu pilihanmu <strong>{$this->escapeHtml($title)}</strong> telah selesai diputar di {$this->escapeHtml($kafeName)}. Terima kasih sudah meramaikan suasana kafe!";
                    $badgeLabel = 'Selesai Diputar';
                    $badgeType = 'completed';
                    break;
                case 'cancelled':
                    $subject = "Request Lagu Dibatalkan: {$title} — Song Request";
                    $headline = "Request Lagu Dibatalkan";
                    $leadMessage = "Permintaan lagu <strong>{$this->escapeHtml($title)}</strong> di {$this->escapeHtml($kafeName)} telah dibatalkan dari antrean sistem.";
                    $badgeLabel = 'Dibatalkan';
                    $badgeType = 'failed';
                    break;
                default:
                    return;
            }

            $rows = [
                ['label' => 'Judul Lagu', 'value' => $title],
                ['label' => 'Artis', 'value' => $artist],
                ['label' => 'Kafe', 'value' => $kafeName],
                ['label' => 'Status Sekarang', 'value' => $badgeLabel],
                ['label' => 'Waktu Perubahan', 'value' => $this->formatDate(date('Y-m-d H:i:s'))],
            ];

            $ctaUrl = $this->siteUrl("/song-request/select-cafe");
            $ctaText = ($newStatus === 'done' || $newStatus === 'cancelled') ? 'Request Lagu Lainnya' : 'Buka Song Request';

            $html = $this->renderTemplate([
                'title'       => $headline,
                'name'        => $name,
                'lead'        => $leadMessage,
                'badge'       => ['label' => $badgeLabel, 'type' => $badgeType],
                'rows'        => $rows,
                'cta'         => ['text' => $ctaText, 'url' => $ctaUrl],
                'footer_note' => 'Nikmati waktu santaimu di kafe bersama alunan lagu pilihanmu.',
            ]);

            $text = "Halo {$name},\n\n"
                . strip_tags($leadMessage) . "\n\n"
                . "Detail:\n"
                . "- Lagu: {$title} — {$artist}\n"
                . "- Kafe: {$kafeName}\n"
                . "- Status: {$badgeLabel}\n\n"
                . "Kunjungi: {$ctaUrl}\n";

            $this->dispatchEmail($key, "song_{$newStatus}", 'request', $requestId, $email, $name, $subject, $html, $text);
        }, 'onSongStatusChanged', $requestId);
    }

    // =========================================================================
    // 2. NOTIFIKASI PEMBAYARAN & PENARIKAN SALDO
    // =========================================================================

    /**
     * A1. Pembayaran Berhasil Diverifikasi.
     */
    public function onPaymentSuccess(int $paymentId): void
    {
        $this->safeDispatch(function () use ($paymentId) {
            $key = "payment_success:{$paymentId}";

            $payment = $this->paymentModel->getPaymentWithDetails($paymentId);
            if (!$payment) {
                return;
            }

            $request = $this->requestModel->find((int)$payment['request_id']);
            $user = !empty($request['user_id']) ? $this->userModel->find((int)$request['user_id']) : null;
            $email = $this->extractRecipientEmail($user);

            if (!$email) {
                $this->recordSkipped($key, 'payment_success', 'payment', $paymentId, 'Pengguna anonim atau email tidak tersedia');
                return;
            }

            $name = $this->extractName($user, $request['guest_name'] ?? '');
            $title = (string)($payment['title'] ?? ($request['title'] ?? 'Lagu Pilihan'));
            $artist = (string)($payment['artist'] ?? ($request['artist'] ?? '-'));
            $kafeName = (string)($payment['nama_kafe'] ?? 'Kafe');
            $amount = 'Rp ' . number_format((int)$payment['amount'], 0, ',', '.');
            $refId = (string)($payment['transaction_id'] ?: ($payment['external_reference'] ?: '#' . $paymentId));
            $paidAt = (string)($payment['paid_at'] ?: ($payment['created_at'] ?: date('Y-m-d H:i:s')));

            $subject = "Pembayaran Terkonfirmasi: Request Lagu {$title} — Song Request";

            $rows = [
                ['label' => 'ID Transaksi', 'value' => $refId],
                ['label' => 'Judul Lagu', 'value' => $title],
                ['label' => 'Artis', 'value' => $artist],
                ['label' => 'Kafe', 'value' => $kafeName],
                ['label' => 'Nominal Pembayaran', 'value' => $amount],
                ['label' => 'Metode Pembayaran', 'value' => strtoupper((string)($payment['payment_method'] ?? 'QRIS / Gateway'))],
                ['label' => 'Waktu Transaksi', 'value' => $this->formatDate($paidAt)],
                ['label' => 'Status Pembayaran', 'value' => 'Berhasil Terverifikasi'],
            ];

            $ctaUrl = $this->siteUrl('/user/history');

            $html = $this->renderTemplate([
                'title'       => 'Pembayaran Berhasil Diverifikasi',
                'name'        => $name,
                'lead'        => "Pembayaran untuk request lagu <strong>{$this->escapeHtml($title)}</strong> telah berhasil diverifikasi oleh sistem. Request kamu kini resmi masuk ke antrean prioritas kafe.",
                'badge'       => ['label' => 'Lunas / Berhasil', 'type' => 'success'],
                'rows'        => $rows,
                'cta'         => ['text' => 'Lihat Riwayat & Status Antrean', 'url' => $ctaUrl],
                'footer_note' => 'Simpan email ini sebagai tanda bukti pembayaran sah yang dikeluarkan oleh sistem Song Request.',
            ]);

            $text = "Halo {$name},\n\nPembayaran sebesar {$amount} untuk request lagu {$title} telah berhasil diverifikasi.\n"
                . "ID Transaksi: {$refId}\nKafe: {$kafeName}\nWaktu: {$paidAt}\n\n"
                . "Lihat status: {$ctaUrl}\n";

            $this->dispatchEmail($key, 'payment_success', 'payment', $paymentId, $email, $name, $subject, $html, $text);
        }, 'onPaymentSuccess', $paymentId);
    }

    /**
     * A2. Pembayaran Gagal atau Kedaluwarsa.
     */
    public function onPaymentFailed(int $paymentId, string $status = 'failed'): void
    {
        if (in_array($status, ['pending', 'authorize'], true)) {
            return;
        }

        $this->safeDispatch(function () use ($paymentId, $status) {
            $key = "payment_{$status}:{$paymentId}";

            $payment = $this->paymentModel->getPaymentWithDetails($paymentId);
            if (!$payment) {
                return;
            }

            $request = $this->requestModel->find((int)$payment['request_id']);
            $user = !empty($request['user_id']) ? $this->userModel->find((int)$request['user_id']) : null;
            $email = $this->extractRecipientEmail($user);

            if (!$email) {
                $this->recordSkipped($key, "payment_{$status}", 'payment', $paymentId, 'Pengguna anonim atau email tidak tersedia');
                return;
            }

            $name = $this->extractName($user, $request['guest_name'] ?? '');
            $title = (string)($payment['title'] ?? ($request['title'] ?? 'Lagu Pilihan'));
            $amount = 'Rp ' . number_format((int)$payment['amount'], 0, ',', '.');
            $refId = (string)($payment['transaction_id'] ?: ($payment['external_reference'] ?: '#' . $paymentId));
            $isExpired = ($status === 'expired');

            $subject = $isExpired
                ? "Pembayaran Kedaluwarsa: Request Lagu {$title} — Song Request"
                : "Pembayaran Tidak Berhasil: Request Lagu {$title} — Song Request";

            $badgeLabel = $isExpired ? 'Kedaluwarsa' : 'Gagal / Ditolak';
            $headline   = $isExpired ? 'Waktu Pembayaran Telah Berakhir' : 'Pembayaran Tidak Berhasil';
            $leadMessage = $isExpired
                ? "Sesi pembayaran sebesar <strong>{$this->escapeHtml($amount)}</strong> untuk lagu <strong>{$this->escapeHtml($title)}</strong> telah kedaluwarsa karena melewati batas waktu yang ditentukan."
                : "Transaksi pembayaran sebesar <strong>{$this->escapeHtml($amount)}</strong> untuk lagu <strong>{$this->escapeHtml($title)}</strong> tidak dapat diselesaikan atau ditolak oleh sistem pembayaran.";

            $rows = [
                ['label' => 'ID Transaksi', 'value' => $refId],
                ['label' => 'Judul Lagu', 'value' => $title],
                ['label' => 'Nominal', 'value' => $amount],
                ['label' => 'Status', 'value' => $badgeLabel],
                ['label' => 'Waktu', 'value' => $this->formatDate(date('Y-m-d H:i:s'))],
            ];

            $ctaUrl = $this->siteUrl('/song-request/select-cafe');

            $html = $this->renderTemplate([
                'title'       => $headline,
                'name'        => $name,
                'lead'        => $leadMessage,
                'badge'       => ['label' => $badgeLabel, 'type' => 'failed'],
                'rows'        => $rows,
                'cta'         => ['text' => 'Coba Request Lagu Kembali', 'url' => $ctaUrl],
                'footer_note' => 'Jika saldo kamu sempat terpotong, silakan hubungi pengelola kafe dengan menunjukkan ID transaksi di atas.',
            ]);

            $text = "Halo {$name},\n\n{$leadMessage}\nID Transaksi: {$refId}\nNominal: {$amount}\nStatus: {$badgeLabel}\n\nKunjungi: {$ctaUrl}\n";

            $this->dispatchEmail($key, "payment_{$status}", 'payment', $paymentId, $email, $name, $subject, $html, $text);
        }, 'onPaymentFailed', $paymentId);
    }

    /**
     * B1. Pengajuan Penarikan Saldo Baru (Pending).
     * Kirim konfirmasi ke Admin Kafe + beri tahu Superadmin.
     */
    public function onWithdrawalRequested(int $withdrawalId): void
    {
        $this->safeDispatch(function () use ($withdrawalId) {
            $withdrawal = $this->withdrawalModel->getWithdrawalWithDetails($withdrawalId);
            if (!$withdrawal) {
                return;
            }

            $cafe = $this->cafeModel->find((int)$withdrawal['cafe_id']);
            $cafeAdmin = !empty($cafe['admin_id']) ? $this->userModel->find((int)$cafe['admin_id']) : null;
            $adminEmail = $this->extractRecipientEmail($cafeAdmin);

            $amount = 'Rp ' . number_format((int)$withdrawal['amount'], 0, ',', '.');
            $destType = ($withdrawal['destination_type'] ?? '') === 'e_wallet' ? 'E-Wallet' : 'Transfer Bank';
            $bankName = (string)($withdrawal['bank_name'] ?? '-');
            $accNumber = $this->maskAccountNumber((string)($withdrawal['account_number'] ?? ''));
            $accHolder = (string)($withdrawal['account_holder'] ?? '-');
            $cafeName = (string)($cafe['nama_kafe'] ?? 'Kafe');

            // 1. Email konfirmasi ke Admin Kafe
            if ($adminEmail) {
                $key = "withdrawal_req_admin:{$withdrawalId}";
                $subject = "Pengajuan Penarikan Saldo Berhasil Dibuat: #{$withdrawalId} — Song Request";
                $adminName = $this->extractName($cafeAdmin);

                $rows = [
                    ['label' => 'ID Penarikan', 'value' => '#' . $withdrawalId],
                    ['label' => 'Kafe', 'value' => $cafeName],
                    ['label' => 'Nominal Penarikan', 'value' => $amount],
                    ['label' => 'Tujuan', 'value' => "{$destType} ({$bankName})"],
                    ['label' => 'Rekening / Akun Tujuan', 'value' => "{$accNumber} a.n {$accHolder}"],
                    ['label' => 'Waktu Pengajuan', 'value' => $this->formatDate($withdrawal['created_at'] ?? date('Y-m-d H:i:s'))],
                    ['label' => 'Status', 'value' => 'Menunggu Persetujuan'],
                ];

                $html = $this->renderTemplate([
                    'title'       => 'Pengajuan Penarikan Saldo Diterima',
                    'name'        => $adminName,
                    'lead'        => "Pengajuan penarikan saldo sebesar <strong>{$amount}</strong> telah berhasil dibuat. Saldo sebesar nominal tersebut saat ini <em>ditahan sementara (held)</em> hingga proses verifikasi dan transfer selesai.",
                    'badge'       => ['label' => 'Menunggu Verifikasi', 'type' => 'pending'],
                    'rows'        => $rows,
                    'cta'         => ['text' => 'Buka Panel Keuangan Kafe', 'url' => $this->siteUrl('/admin/dashboard')],
                    'footer_note' => 'Superadmin akan memeriksa pengajuan ini. Kami akan mengabari Anda segera setelah ada pembaruan status.',
                ]);

                $text = "Halo {$adminName},\n\nPengajuan penarikan saldo #{$withdrawalId} sebesar {$amount} untuk {$cafeName} telah diterima.\nRekening tujuan: {$accNumber} a.n {$accHolder}\nStatus: Menunggu Persetujuan.\n";

                $this->dispatchEmail($key, 'withdrawal_requested_admin', 'withdrawal', $withdrawalId, $adminEmail, $adminName, $subject, $html, $text);
            }

            // 2. Notifikasi ke Superadmin (Call to action untuk review)
            $superadmins = $this->userModel->where('role', 'superadmin')->where('is_active', true)->findAll();
            foreach ($superadmins as $sa) {
                $saEmail = $this->extractRecipientEmail($sa);
                if (!$saEmail) {
                    continue;
                }

                $saKey = "withdrawal_req_sa:{$withdrawalId}:user_{$sa['id']}";
                $saName = $this->extractName($sa);
                $saSubject = "🔔 Perlu Persetujuan: Penarikan Saldo #{$withdrawalId} dari {$cafeName}";

                $rows = [
                    ['label' => 'ID Penarikan', 'value' => '#' . $withdrawalId],
                    ['label' => 'Nama Kafe', 'value' => $cafeName],
                    ['label' => 'Nominal', 'value' => $amount],
                    ['label' => 'Tujuan', 'value' => "{$destType} {$bankName} — {$accNumber} ({$accHolder})"],
                    ['label' => 'Waktu Pengajuan', 'value' => $this->formatDate($withdrawal['created_at'] ?? date('Y-m-d H:i:s'))],
                ];

                $html = $this->renderTemplate([
                    'title'       => 'Pengajuan Penarikan Saldo Baru',
                    'name'        => $saName,
                    'lead'        => "Terdapat permohonan penarikan saldo baru sebesar <strong>{$amount}</strong> dari <strong>{$this->escapeHtml($cafeName)}</strong> yang memerlukan persetujuan Anda.",
                    'badge'       => ['label' => 'Perlu Ditinjau', 'type' => 'pending'],
                    'rows'        => $rows,
                    'cta'         => ['text' => 'Tinjau Penarikan di Superadmin', 'url' => $this->siteUrl('/superadmin/dashboard')],
                    'footer_note' => 'Pastikan data rekening dan saldo kafe sesuai sebelum memberikan persetujuan.',
                ]);

                $text = "Halo {$saName},\n\nTerdapat pengajuan penarikan saldo #{$withdrawalId} sebesar {$amount} dari {$cafeName}.\nSilakan tinjau di panel Superadmin: " . $this->siteUrl('/superadmin/dashboard') . "\n";

                $this->dispatchEmail($saKey, 'withdrawal_requested_sa', 'withdrawal', $withdrawalId, $saEmail, $saName, $saSubject, $html, $text);
            }
        }, 'onWithdrawalRequested', $withdrawalId);
    }

    /**
     * B2. Penarikan Disetujui oleh Superadmin (Approved).
     * Beri tahu Admin Kafe. Catatan: Persetujuan bukan berarti dana sudah ditransfer.
     */
    public function onWithdrawalApproved(int $withdrawalId): void
    {
        $this->safeDispatch(function () use ($withdrawalId) {
            $key = "withdrawal_approved:{$withdrawalId}";

            $withdrawal = $this->withdrawalModel->getWithdrawalWithDetails($withdrawalId);
            if (!$withdrawal) {
                return;
            }

            $cafe = $this->cafeModel->find((int)$withdrawal['cafe_id']);
            $cafeAdmin = !empty($cafe['admin_id']) ? $this->userModel->find((int)$cafe['admin_id']) : null;
            $adminEmail = $this->extractRecipientEmail($cafeAdmin);

            if (!$adminEmail) {
                $this->recordSkipped($key, 'withdrawal_approved', 'withdrawal', $withdrawalId, 'Email admin kafe tidak tersedia');
                return;
            }

            $adminName = $this->extractName($cafeAdmin);
            $amount = 'Rp ' . number_format((int)$withdrawal['amount'], 0, ',', '.');
            $cafeName = (string)($cafe['nama_kafe'] ?? 'Kafe');
            $accNumber = $this->maskAccountNumber((string)($withdrawal['account_number'] ?? ''));
            $accHolder = (string)($withdrawal['account_holder'] ?? '-');
            $bankName = (string)($withdrawal['bank_name'] ?? '-');

            $subject = "Penarikan Disetujui: #{$withdrawalId} ({$cafeName}) — Song Request";

            $rows = [
                ['label' => 'ID Penarikan', 'value' => '#' . $withdrawalId],
                ['label' => 'Kafe', 'value' => $cafeName],
                ['label' => 'Nominal', 'value' => $amount],
                ['label' => 'Rekening Tujuan', 'value' => "{$bankName} - {$accNumber} a.n {$accHolder}"],
                ['label' => 'Status Sekarang', 'value' => 'Disetujui (Menunggu Pelaksanaan Transfer)'],
                ['label' => 'Waktu Persetujuan', 'value' => $this->formatDate($withdrawal['approved_at'] ?? date('Y-m-d H:i:s'))],
            ];

            $html = $this->renderTemplate([
                'title'       => 'Pengajuan Penarikan Telah Disetujui',
                'name'        => $adminName,
                'lead'        => "Kabar baik, pengajuan penarikan saldo sebesar <strong>{$amount}</strong> telah <strong>disetujui</strong> oleh superadmin.<br><br><strong>Catatan Penting:</strong> Persetujuan ini menandakan validasi berkas telah selesai. Dana saat ini sedang diproses untuk pelaksanaan transfer ke rekening tujuan Anda.",
                'badge'       => ['label' => 'Disetujui', 'type' => 'approved'],
                'rows'        => $rows,
                'cta'         => ['text' => 'Buka Dashboard Keuangan', 'url' => $this->siteUrl('/admin/dashboard')],
                'footer_note' => 'Email konfirmasi akhir beserta nomor referensi transfer akan dikirimkan begitu proses transfer bank berhasil diselesaikan.',
            ]);

            $text = "Halo {$adminName},\n\nPenarikan saldo #{$withdrawalId} sebesar {$amount} telah disetujui oleh Superadmin.\n"
                . "Perhatian: Persetujuan ini berarti verifikasi berhasil, dana selanjutnya akan ditransfer ke rekening {$accNumber}.\n";

            $this->dispatchEmail($key, 'withdrawal_approved', 'withdrawal', $withdrawalId, $adminEmail, $adminName, $subject, $html, $text);
        }, 'onWithdrawalApproved', $withdrawalId);
    }

    /**
     * B3. Penarikan Ditolak (Rejected).
     * Beri tahu Admin Kafe beserta alasan dan konfirmasi saldo dikembalikan.
     */
    public function onWithdrawalRejected(int $withdrawalId, string $reason): void
    {
        $this->safeDispatch(function () use ($withdrawalId, $reason) {
            $key = "withdrawal_rejected:{$withdrawalId}";

            $withdrawal = $this->withdrawalModel->getWithdrawalWithDetails($withdrawalId);
            if (!$withdrawal) {
                return;
            }

            $cafe = $this->cafeModel->find((int)$withdrawal['cafe_id']);
            $cafeAdmin = !empty($cafe['admin_id']) ? $this->userModel->find((int)$cafe['admin_id']) : null;
            $adminEmail = $this->extractRecipientEmail($cafeAdmin);

            if (!$adminEmail) {
                $this->recordSkipped($key, 'withdrawal_rejected', 'withdrawal', $withdrawalId, 'Email admin kafe tidak tersedia');
                return;
            }

            $adminName = $this->extractName($cafeAdmin);
            $amount = 'Rp ' . number_format((int)$withdrawal['amount'], 0, ',', '.');
            $cafeName = (string)($cafe['nama_kafe'] ?? 'Kafe');

            $subject = "Penarikan Ditolak: #{$withdrawalId} ({$cafeName}) — Song Request";

            $rows = [
                ['label' => 'ID Penarikan', 'value' => '#' . $withdrawalId],
                ['label' => 'Kafe', 'value' => $cafeName],
                ['label' => 'Nominal', 'value' => $amount],
                ['label' => 'Status', 'value' => 'Ditolak (Saldo Dikembalikan)'],
                ['label' => 'Alasan Penolakan', 'value' => $reason ?: 'Data tidak sesuai ketentuan'],
                ['label' => 'Waktu', 'value' => $this->formatDate(date('Y-m-d H:i:s'))],
            ];

            $html = $this->renderTemplate([
                'title'       => 'Pengajuan Penarikan Tidak Disetujui',
                'name'        => $adminName,
                'lead'        => "Pengajuan penarikan saldo sebesar <strong>{$amount}</strong> belum dapat disetujui oleh superadmin.<br><br><strong>Kabar Penting:</strong> Saldo sebesar nominal tersebut telah <em>secara otomatis dikembalikan seutuhnya</em> ke saldo aktif kafe Anda.",
                'badge'       => ['label' => 'Ditolak', 'type' => 'failed'],
                'rows'        => $rows,
                'cta'         => ['text' => 'Periksa Saldo Kafe', 'url' => $this->siteUrl('/admin/dashboard')],
                'footer_note' => 'Silakan periksa kembali data rekening atau ketentuan penarikan sebelum mengajukan permohonan baru.',
            ]);

            $text = "Halo {$adminName},\n\nPengajuan penarikan saldo #{$withdrawalId} sebesar {$amount} ditolak.\n"
                . "Alasan: {$reason}\nSaldo sebesar {$amount} telah dikembalikan ke saldo aktif kafe Anda.\n";

            $this->dispatchEmail($key, 'withdrawal_rejected', 'withdrawal', $withdrawalId, $adminEmail, $adminName, $subject, $html, $text);
        }, 'onWithdrawalRejected', $withdrawalId);
    }

    /**
     * B4. Transfer Benar-benar Selesai (Completed).
     * Beri tahu Admin Kafe hanya setelah status transfer tercatat dan diverifikasi.
     */
    public function onWithdrawalCompleted(int $withdrawalId, string $transferReference): void
    {
        $this->safeDispatch(function () use ($withdrawalId, $transferReference) {
            $key = "withdrawal_completed:{$withdrawalId}";

            $withdrawal = $this->withdrawalModel->getWithdrawalWithDetails($withdrawalId);
            if (!$withdrawal) {
                return;
            }

            $cafe = $this->cafeModel->find((int)$withdrawal['cafe_id']);
            $cafeAdmin = !empty($cafe['admin_id']) ? $this->userModel->find((int)$cafe['admin_id']) : null;
            $adminEmail = $this->extractRecipientEmail($cafeAdmin);

            if (!$adminEmail) {
                $this->recordSkipped($key, 'withdrawal_completed', 'withdrawal', $withdrawalId, 'Email admin kafe tidak tersedia');
                return;
            }

            $adminName = $this->extractName($cafeAdmin);
            $amount = 'Rp ' . number_format((int)$withdrawal['amount'], 0, ',', '.');
            $cafeName = (string)($cafe['nama_kafe'] ?? 'Kafe');
            $accNumber = $this->maskAccountNumber((string)($withdrawal['account_number'] ?? ''));
            $accHolder = (string)($withdrawal['account_holder'] ?? '-');
            $bankName = (string)($withdrawal['bank_name'] ?? '-');
            $completedAt = (string)($withdrawal['completed_at'] ?? date('Y-m-d H:i:s'));

            $subject = "Transfer Selesai: Penarikan Saldo #{$withdrawalId} Berhasil Ditransfer — Song Request";

            $rows = [
                ['label' => 'ID Penarikan', 'value' => '#' . $withdrawalId],
                ['label' => 'Nomor Referensi Transfer', 'value' => $transferReference],
                ['label' => 'Kafe', 'value' => $cafeName],
                ['label' => 'Nominal Ditransfer', 'value' => $amount],
                ['label' => 'Rekening Penerima', 'value' => "{$bankName} - {$accNumber} a.n {$accHolder}"],
                ['label' => 'Waktu Penyelesaian', 'value' => $this->formatDate($completedAt)],
                ['label' => 'Status Akhir', 'value' => 'Transfer Selesai & Terverifikasi'],
            ];

            $html = $this->renderTemplate([
                'title'       => 'Dana Penarikan Berhasil Ditransfer',
                'name'        => $adminName,
                'lead'        => "Transfer dana penarikan saldo sebesar <strong>{$amount}</strong> telah <strong>berhasil dilaksanakan dan diverifikasi</strong> ke rekening bank/e-wallet Anda.",
                'badge'       => ['label' => 'Transfer Selesai', 'type' => 'completed'],
                'rows'        => $rows,
                'cta'         => ['text' => 'Lihat Rincian Mutasi Keuangan', 'url' => $this->siteUrl('/admin/dashboard')],
                'footer_note' => 'Silakan periksa mutasi rekening Anda dengan mencocokkan nomor referensi transfer di atas.',
            ]);

            $text = "Halo {$adminName},\n\nTransfer dana penarikan #{$withdrawalId} sebesar {$amount} telah selesai dilaksanakan.\n"
                . "No Referensi: {$transferReference}\nRekening: {$bankName} {$accNumber} a.n {$accHolder}\n"
                . "Waktu: {$completedAt}\n";

            $this->dispatchEmail($key, 'withdrawal_completed', 'withdrawal', $withdrawalId, $adminEmail, $adminName, $subject, $html, $text);
        }, 'onWithdrawalCompleted', $withdrawalId);
    }

    // =========================================================================
    // 3. NOTIFIKASI PERSETUJUAN ADMIN & SUPERADMIN
    // =========================================================================

    /**
     * A1. Pendaftaran Admin Baru Masuk.
     * Kirim konfirmasi ke pendaftar + notifikasi ke Superadmin.
     */
    public function onAdminRegistered(int $userId): void
    {
        $this->safeDispatch(function () use ($userId) {
            $user = $this->userModel->find($userId);
            if (!$user || $user['role'] !== 'admin') {
                return;
            }

            $applicantEmail = $this->extractRecipientEmail($user);
            $applicantName = $this->extractName($user);

            // 1. Email konfirmasi pendaftaran ke admin baru
            if ($applicantEmail) {
                $key = "admin_reg_confirm:{$userId}";
                $subject = "Pendaftaran Admin Kafe Diterima — Song Request";

                $rows = [
                    ['label' => 'Nama Pemohon', 'value' => $applicantName],
                    ['label' => 'Email Terdaftar', 'value' => $applicantEmail],
                    ['label' => 'Role Akun', 'value' => 'Admin Kafe'],
                    ['label' => 'Waktu Pendaftaran', 'value' => $this->formatDate($user['created_at'] ?? date('Y-m-d H:i:s'))],
                    ['label' => 'Status Verifikasi', 'value' => 'Menunggu Tinjauan Superadmin'],
                ];

                $html = $this->renderTemplate([
                    'title'       => 'Pendaftaran Admin Kafe Berhasil Diterima',
                    'name'        => $applicantName,
                    'lead'        => "Terima kasih telah mendaftar sebagai Admin Kafe di Song Request.<br><br>Pendaftaran akun Anda saat ini sedang dalam antrean peninjauan oleh tim Superadmin. Kami akan mengirimkan email pemberitahuan segera setelah akun Anda diverifikasi dan diaktifkan.",
                    'badge'       => ['label' => 'Menunggu Verifikasi', 'type' => 'pending'],
                    'rows'        => $rows,
                    'footer_note' => 'Anda belum dapat login ke dashboard sebelum akun disetujui oleh Superadmin.',
                ]);

                $text = "Halo {$applicantName},\n\nPendaftaran akun Admin Kafe Anda di Song Request telah diterima dan menunggu verifikasi Superadmin.\nKami akan mengabari Anda setelah akun disetujui.\n";

                $this->dispatchEmail($key, 'admin_registered_applicant', 'user', $userId, $applicantEmail, $applicantName, $subject, $html, $text);
            }

            // 2. Beri tahu Superadmin
            $superadmins = $this->userModel->where('role', 'superadmin')->where('is_active', true)->findAll();
            foreach ($superadmins as $sa) {
                $saEmail = $this->extractRecipientEmail($sa);
                if (!$saEmail) {
                    continue;
                }

                $saKey = "admin_reg_sa:{$userId}:user_{$sa['id']}";
                $saName = $this->extractName($sa);
                $saSubject = "🔔 Pendaftaran Admin Baru: {$applicantName} — Song Request";

                $rows = [
                    ['label' => 'Nama Pendaftar', 'value' => $applicantName],
                    ['label' => 'Email', 'value' => $applicantEmail],
                    ['label' => 'Nomor HP', 'value' => (string)($user['phone'] ?? ($user['phone_wa'] ?? '-'))],
                    ['label' => 'Waktu Mendaftar', 'value' => $this->formatDate($user['created_at'] ?? date('Y-m-d H:i:s'))],
                ];

                $html = $this->renderTemplate([
                    'title'       => 'Pendaftaran Admin Baru Memerlukan Verifikasi',
                    'name'        => $saName,
                    'lead'        => "Terdapat permohonan akun Admin Kafe baru atas nama <strong>{$this->escapeHtml($applicantName)}</strong> ({$this->escapeHtml($applicantEmail)}) yang memerlukan verifikasi Anda.",
                    'badge'       => ['label' => 'Perlu Verifikasi', 'type' => 'pending'],
                    'rows'        => $rows,
                    'cta'         => ['text' => 'Buka Menu Verifikasi Admin', 'url' => $this->siteUrl('/superadmin/dashboard')],
                    'footer_note' => 'Silakan verifikasi kelayakan calon admin sebelum mengaktifkan akun.',
                ]);

                $text = "Halo {$saName},\n\nTerdapat pendaftaran admin kafe baru: {$applicantName} ({$applicantEmail}).\nSilakan verifikasi di: " . $this->siteUrl('/superadmin/dashboard') . "\n";

                $this->dispatchEmail($saKey, 'admin_registered_sa', 'user', $userId, $saEmail, $saName, $saSubject, $html, $text);
            }
        }, 'onAdminRegistered', $userId);
    }

    /**
     * A2. Pendaftaran Admin Disetujui (Approved).
     * Kirim email ke admin kafe bahwa akun telah aktif dan siap digunakan.
     */
    public function onAdminApproved(int $userId): void
    {
        $this->safeDispatch(function () use ($userId) {
            $key = "admin_approved:{$userId}";

            $user = $this->userModel->find($userId);
            if (!$user) {
                return;
            }

            $email = $this->extractRecipientEmail($user);
            if (!$email) {
                $this->recordSkipped($key, 'admin_approved', 'user', $userId, 'Email tidak tersedia');
                return;
            }

            $name = $this->extractName($user);
            $subject = "🎉 Akun Admin Kafe Disetujui & Aktif — Song Request";

            $rows = [
                ['label' => 'Nama Akun', 'value' => $name],
                ['label' => 'Email Login', 'value' => $email],
                ['label' => 'Role', 'value' => 'Admin Kafe'],
                ['label' => 'Status Akun', 'value' => 'Aktif & Terverifikasi'],
                ['label' => 'Waktu Persetujuan', 'value' => $this->formatDate(date('Y-m-d H:i:s'))],
            ];

            $loginUrl = $this->siteUrl('/auth/login');

            $html = $this->renderTemplate([
                'title'       => 'Selamat! Akun Admin Kafe Telah Aktif',
                'name'        => $name,
                'lead'        => "Pendaftaran akun Admin Kafe Anda telah <strong>berhasil disetujui dan diaktifkan</strong> oleh Superadmin.<br><br>Sekarang Anda sudah dapat masuk ke sistem, mendaftarkan kafe, mengelola antrean lagu, dan mengatur penerimaan pembayaran.",
                'badge'       => ['label' => 'Akun Aktif', 'type' => 'success'],
                'rows'        => $rows,
                'cta'         => ['text' => 'Masuk ke Dashboard Admin', 'url' => $loginUrl],
                'footer_note' => 'Gunakan email dan password yang Anda daftarkan sebelumnya untuk masuk ke aplikasi.',
            ]);

            $text = "Halo {$name},\n\nSelamat! Akun Admin Kafe Anda di Song Request telah disetujui dan aktif.\nSilakan login ke dashboard: {$loginUrl}\n";

            $this->dispatchEmail($key, 'admin_approved', 'user', $userId, $email, $name, $subject, $html, $text);
        }, 'onAdminApproved', $userId);
    }

    /**
     * A3. Pendaftaran Admin Ditolak (Rejected).
     */
    public function onAdminRejected(int $userId, string $reason = ''): void
    {
        $this->safeDispatch(function () use ($userId, $reason) {
            $key = "admin_rejected:{$userId}";

            $user = $this->userModel->find($userId);
            if (!$user) {
                return;
            }

            $email = $this->extractRecipientEmail($user);
            if (!$email) {
                $this->recordSkipped($key, 'admin_rejected', 'user', $userId, 'Email tidak tersedia');
                return;
            }

            $name = $this->extractName($user);
            $subject = "Pemberitahuan Pendaftaran Admin Kafe — Song Request";

            $rows = [
                ['label' => 'Nama Pemohon', 'value' => $name],
                ['label' => 'Email', 'value' => $email],
                ['label' => 'Status', 'value' => 'Pendaftaran Tidak Disetujui'],
            ];

            if ($reason !== '') {
                $rows[] = ['label' => 'Keterangan / Alasan', 'value' => $reason];
            }

            $html = $this->renderTemplate([
                'title'       => 'Pendaftaran Belum Dapat Disetujui',
                'name'        => $name,
                'lead'        => "Terima kasih atas minat Anda bergabung dengan Song Request.<br><br>Mohon maaf, setelah melalui proses verifikasi, pendaftaran akun Admin Kafe Anda <strong>belum dapat kami setujui saat ini</strong>" . ($reason ? " dengan pertimbangan: <em>{$this->escapeHtml($reason)}</em>." : "."),
                'badge'       => ['label' => 'Tidak Disetujui', 'type' => 'failed'],
                'rows'        => $rows,
                'footer_note' => 'Jika Anda merasa ada kekeliruan data, silakan hubungi tim pengelola atau mendaftar kembali dengan data yang valid.',
            ]);

            $text = "Halo {$name},\n\nMohon maaf, pendaftaran akun Admin Kafe Anda di Song Request belum dapat disetujui.\n"
                . ($reason ? "Alasan: {$reason}\n" : "");

            $this->dispatchEmail($key, 'admin_rejected', 'user', $userId, $email, $name, $subject, $html, $text);
        }, 'onAdminRejected', $userId);
    }

    /**
     * B. Perubahan Status Akun / Kafe Penting (Aktif / Nonaktif).
     */
    public function onCafeStatusChanged(int $cafeId, bool $isActive): void
    {
        $this->safeDispatch(function () use ($cafeId, $isActive) {
            $statusKey = $isActive ? 'active' : 'suspended';
            $key = "cafe_status:{$cafeId}:{$statusKey}_" . date('YmdH');

            $cafe = $this->cafeModel->find($cafeId);
            if (!$cafe) {
                return;
            }

            $admin = !empty($cafe['admin_id']) ? $this->userModel->find((int)$cafe['admin_id']) : null;
            $email = $this->extractRecipientEmail($admin);

            if (!$email) {
                $this->recordSkipped($key, 'cafe_status_changed', 'cafe', $cafeId, 'Email admin kafe tidak tersedia');
                return;
            }

            $name = $this->extractName($admin);
            $cafeName = (string)($cafe['nama_kafe'] ?? 'Kafe');

            $subject = $isActive
                ? "Kafe {$cafeName} Telah Diaktifkan Kembali — Song Request"
                : "Pemberitahuan: Kafe {$cafeName} Ditangguhkan — Song Request";

            $badgeLabel = $isActive ? 'Kafe Aktif' : 'Ditangguhkan (Nonaktif)';
            $badgeType  = $isActive ? 'success' : 'failed';

            $leadMessage = $isActive
                ? "Kafe <strong>{$this->escapeHtml($cafeName)}</strong> telah <strong>diaktifkan kembali</strong> oleh Superadmin. Pengunjung kini dapat kembali mencari kafe dan mengirimkan request lagu."
                : "Kafe <strong>{$this->escapeHtml($cafeName)}</strong> saat ini <strong>ditangguhkan sementara</strong> oleh Superadmin. Selama penangguhan, halaman request lagu kafe ini tidak dapat diakses oleh publik.";

            $rows = [
                ['label' => 'Nama Kafe', 'value' => $cafeName],
                ['label' => 'Status Sekarang', 'value' => $badgeLabel],
                ['label' => 'Waktu Perubahan', 'value' => $this->formatDate(date('Y-m-d H:i:s'))],
            ];

            $ctaUrl = $this->siteUrl('/admin/dashboard');

            $html = $this->renderTemplate([
                'title'       => $isActive ? 'Kafe Telah Diaktifkan' : 'Kafe Ditangguhkan Sementara',
                'name'        => $name,
                'lead'        => $leadMessage,
                'badge'       => ['label' => $badgeLabel, 'type' => $badgeType],
                'rows'        => $rows,
                'cta'         => ['text' => 'Buka Dashboard Kafe', 'url' => $ctaUrl],
                'footer_note' => 'Hubungi Superadmin jika Anda memiliki pertanyaan terkait status operasional kafe.',
            ]);

            $text = "Halo {$name},\n\n{$leadMessage}\nStatus: {$badgeLabel}\nDashboard: {$ctaUrl}\n";

            $this->dispatchEmail($key, 'cafe_status_changed', 'cafe', $cafeId, $email, $name, $subject, $html, $text);
        }, 'onCafeStatusChanged', $cafeId);
    }

    // =========================================================================
    // TESTING & COMPATIBILITY METHODS
    // =========================================================================

    public function sendRegistrationEmail(array $user): array
    {
        $recipient = $this->extractRecipientEmail($user);
        if ($recipient === null) {
            return ['success' => false, 'message' => 'Email pengguna tidak tersedia'];
        }

        $name = $this->extractName($user);
        $subject = 'Selamat datang di Song Request';
        $dashboardUrl = $this->siteUrl('/user/dashboard');
        $historyUrl = $this->siteUrl('/user/history');

        $rows = [
            ['label' => 'Nama Pengguna', 'value' => $name],
            ['label' => 'Email Terdaftar', 'value' => $recipient],
            ['label' => 'Status Akun', 'value' => 'Aktif & Siap Digunakan'],
        ];

        $html = $this->renderTemplate([
            'title'       => 'Selamat Datang di Song Request',
            'name'        => $name,
            'lead'        => 'Akun Song Request kamu sudah aktif. Sekarang kamu bisa request lagu favorit di kafe yang bekerja sama, memantau antrean lagu secara real-time, dan melihat riwayat request kamu.',
            'badge'       => ['label' => 'Akun Aktif', 'type' => 'success'],
            'rows'        => $rows,
            'cta'         => ['text' => 'Mulai Request Lagu', 'url' => $dashboardUrl],
            'footer_note' => 'Terima kasih telah bergabung bersama Song Request.',
        ]);

        $text = "Halo {$name},\n\nAkun kamu di Song Request sudah aktif.\nBuka dashboard: {$dashboardUrl}\nRiwayat request: {$historyUrl}\n";

        return $this->send($recipient, $subject, $html, $text, $name);
    }

    public function sendRequestCreatedEmail(int $requestId): array
    {
        $this->onSongRequestCreated($requestId);
        return ['success' => true, 'message' => 'Notifikasi request lagu diproses'];
    }

    public function sendTestEmailToUser(int $userId): array
    {
        $user = $this->userModel->find($userId);
        if (!$user) {
            return ['success' => false, 'message' => 'User tidak ditemukan'];
        }

        return $this->sendTestEmail($user);
    }

    public function sendTestEmail(array $user): array
    {
        $recipient = $this->extractRecipientEmail($user);
        if ($recipient === null) {
            return ['success' => false, 'message' => 'Email pengguna tidak tersedia'];
        }

        $name = $this->extractName($user);
        $subject = 'Test Notifikasi Email Brevo — Song Request';

        $rows = [
            ['label' => 'Penerima', 'value' => "{$name} ({$recipient})"],
            ['label' => 'Layanan Email', 'value' => 'Brevo REST API (v3)'],
            ['label' => 'Status Konfigurasi', 'value' => 'Berhasil Terhubung'],
            ['label' => 'Waktu Uji Coba', 'value' => $this->formatDate(date('Y-m-d H:i:s'))],
        ];

        $html = $this->renderTemplate([
            'title'       => 'Uji Coba Pengiriman Email Berhasil',
            'name'        => $name,
            'lead'        => 'Ini adalah email percobaan dari sistem notifikasi <strong>Song Request</strong>. Jika email ini masuk ke inbox Anda, integrasi pengiriman email berbasis Brevo telah berfungsi dengan sempurna.',
            'badge'       => ['label' => 'Uji Coba Sukses', 'type' => 'success'],
            'rows'        => $rows,
            'cta'         => ['text' => 'Buka Aplikasi Song Request', 'url' => $this->siteUrl('/')],
            'footer_note' => 'Email percobaan ini membuktikan bahwa seluruh template HTML dan koneksi API telah siap digunakan.',
        ]);

        $text = "Halo {$name},\n\nIni adalah email percobaan dari Song Request.\nJika email ini masuk ke inbox Anda, konfigurasi Brevo sudah berhasil.\n";

        return $this->send($recipient, $subject, $html, $text, $name);
    }

    // =========================================================================
    // CORE SENDER & IDEMPOTENCY ENGINE
    // =========================================================================

    /**
     * Dispatch email dengan pengecekan idempotensi dan pencatatan log.
     */
    protected function dispatchEmail(
        string $idempotencyKey,
        string $eventType,
        string $entityType,
        int $entityId,
        string $recipientEmail,
        string $recipientName,
        string $subject,
        string $html,
        string $text
    ): void {
        // Cek apakah event ini sudah pernah diproses sebelumnya (idempotent)
        $existing = $this->logModel->findByIdempotencyKey($idempotencyKey);
        if ($existing) {
            if ($existing['status'] === EmailNotificationLogModel::STATUS_SENT) {
                log_message('info', "Email duplicate skipped: {$idempotencyKey}");
                return;
            }
        }

        // Catat record awal ke log
        $logId = $this->logModel->createNotification([
            'idempotency_key' => $idempotencyKey,
            'event_type'      => $eventType,
            'recipient_email' => $recipientEmail,
            'recipient_name'  => $recipientName,
            'entity_type'     => $entityType,
            'entity_id'       => $entityId,
            'subject'         => $subject,
            'status'          => EmailNotificationLogModel::STATUS_PENDING,
            'attempt_count'   => 1,
        ]);

        $result = $this->send($recipientEmail, $subject, $html, $text, $recipientName);

        if ($logId) {
            if ($result['success']) {
                $messageId = $result['data']['messageId'] ?? null;
                $this->logModel->updateAfterAttempt($logId, EmailNotificationLogModel::STATUS_SENT, [
                    'provider_message_id' => $messageId,
                    'response_code'       => 201,
                    'sent_at'             => date('Y-m-d H:i:s'),
                ]);
            } else {
                $this->logModel->updateAfterAttempt($logId, EmailNotificationLogModel::STATUS_FAILED, [
                    'error_summary' => mb_substr((string)($result['message'] ?? 'Error pengiriman'), 0, 500),
                    'response_code' => $result['http_code'] ?? 400,
                ]);
            }
        }
    }

    /**
     * Catat event yang dilewati (skipped) ke log.
     */
    protected function recordSkipped(string $key, string $eventType, string $entityType, int $entityId, string $reason): void
    {
        $this->logModel->createNotification([
            'idempotency_key' => $key,
            'event_type'      => $eventType,
            'recipient_email' => 'none@skipped.local',
            'recipient_name'  => 'None',
            'entity_type'     => $entityType,
            'entity_id'       => $entityId,
            'subject'         => 'Skipped: ' . $reason,
            'status'          => EmailNotificationLogModel::STATUS_SKIPPED,
            'error_summary'   => $reason,
            'attempt_count'   => 0,
        ]);
    }

    /**
     * Safe wrapper agar error notifikasi email TIDAK PERNAH membatalkan transaksi DB.
     */
    protected function safeDispatch(callable $fn, string $methodName, $contextId = null): void
    {
        try {
            $fn();
        } catch (\Throwable $e) {
            log_message('error', "NotificationEmailService::{$methodName} failed (non-critical, context: {$contextId}): " . $e->getMessage());
        }
    }

    /**
     * Low-level cURL call ke Brevo REST API v3.
     */
    protected function send(string $to, string $subject, string $html, string $text, ?string $toName = null): array
    {
        try {
            if (!$this->isConfigured()) {
                log_message('error', 'Brevo belum dikonfigurasi: BREVO_API_KEY atau BREVO_FROM_EMAIL kosong');
                return ['success' => false, 'message' => 'Konfigurasi email belum lengkap'];
            }

            $recipientItem = ['email' => $to];
            if (!empty($toName)) {
                $recipientItem['name'] = $toName;
            }

            $payload = json_encode([
                'sender' => [
                    'name'  => $this->fromName,
                    'email' => $this->fromEmail,
                ],
                'to'          => [$recipientItem],
                'subject'     => $subject,
                'htmlContent' => $html,
                'textContent' => $text,
            ]);

            if ($payload === false) {
                log_message('error', 'Brevo payload JSON encoding failed');
                return ['success' => false, 'message' => 'Gagal menyiapkan payload email'];
            }

            $ch = curl_init(self::ENDPOINT);
            if ($ch === false) {
                log_message('error', 'Brevo curl_init gagal');
                return ['success' => false, 'message' => 'Gagal menyiapkan koneksi email'];
            }

            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_POST           => true,
                CURLOPT_POSTFIELDS     => $payload,
                CURLOPT_HTTPHEADER     => [
                    'api-key: ' . $this->apiKey,
                    'Content-Type: application/json',
                    'Accept: application/json',
                ],
                CURLOPT_SSL_VERIFYPEER => $this->verifySsl,
                CURLOPT_SSL_VERIFYHOST => $this->verifySsl ? 2 : 0,
                CURLOPT_CONNECTTIMEOUT => 10,
                CURLOPT_TIMEOUT        => 30,
            ]);

            $response = curl_exec($ch);
            $curlError = curl_error($ch);
            $httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            if ($curlError !== '') {
                log_message('error', 'Brevo curl error: ' . $curlError);
                return [
                    'success'   => false,
                    'message'   => 'Gagal menghubungi layanan email: ' . $curlError,
                    'http_code' => $httpCode,
                ];
            }

            $decoded = json_decode((string) $response, true);

            if ($httpCode < 200 || $httpCode >= 300) {
                log_message('error', 'Brevo HTTP ' . $httpCode . ': ' . (string) $response);
                return [
                    'success'   => false,
                    'message'   => $decoded['message'] ?? 'Gagal mengirim email',
                    'http_code' => $httpCode,
                ];
            }

            return [
                'success'   => true,
                'message'   => 'Email berhasil dikirim',
                'data'      => $decoded,
                'http_code' => $httpCode,
            ];
        } catch (\Throwable $e) {
            log_message('error', 'Brevo unexpected error: ' . $e->getMessage());
            return ['success' => false, 'message' => 'Gagal mengirim email'];
        }
    }

    // =========================================================================
    // TEMPLATE RENDERER (Brand Identity: #F4EFEA / #E05A47)
    // =========================================================================

    /**
     * Render template email HTML modern & responsif sesuai identitas visual Song Request.
     */
    protected function renderTemplate(array $params): string
    {
        $title      = $this->escapeHtml((string)($params['title'] ?? 'Song Request'));
        $name       = $this->escapeHtml((string)($params['name'] ?? 'Pelanggan'));
        $lead       = (string)($params['lead'] ?? ''); // Raw HTML with safe tags already formatted
        $badge      = $params['badge'] ?? null;
        $rows       = $params['rows'] ?? [];
        $cta        = $params['cta'] ?? null;
        $footerNote = (string)($params['footer_note'] ?? '');
        $currentYear = date('Y');

        // Status badge colors
        $badgeHtml = '';
        if ($badge && !empty($badge['label'])) {
            $badgeLabel = $this->escapeHtml((string)$badge['label']);
            $badgeType = (string)($badge['type'] ?? 'info');

            switch ($badgeType) {
                case 'success':
                case 'approved':
                case 'completed':
                    $bgColor = '#EAF8EE';
                    $textColor = '#1E7E34';
                    $borderColor = '#C3E6CB';
                    break;
                case 'pending':
                case 'waiting':
                    $bgColor = '#FFF8E6';
                    $textColor = '#B7791F';
                    $borderColor = '#FEEBC8';
                    break;
                case 'playing':
                    $bgColor = '#F2E8FD';
                    $textColor = '#6B46C1';
                    $borderColor = '#E9D8FD';
                    break;
                case 'failed':
                case 'rejected':
                case 'cancelled':
                    $bgColor = '#FDF0ED';
                    $textColor = '#C53030';
                    $borderColor = '#FED7D7';
                    break;
                default:
                    $bgColor = '#EDF2F7';
                    $textColor = '#4A5568';
                    $borderColor = '#E2E8F0';
            }

            $badgeHtml = <<<HTML
<div style="margin-bottom: 20px;">
    <span style="display: inline-block; padding: 6px 14px; background-color: {$bgColor}; color: {$textColor}; border: 1px solid {$borderColor}; border-radius: 9999px; font-size: 13px; font-weight: 700; letter-spacing: 0.3px;">
        {$badgeLabel}
    </span>
</div>
HTML;
        }

        // Info card rows
        $rowsHtml = '';
        if (!empty($rows)) {
            $rowsHtml .= '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin-top: 24px; margin-bottom: 24px; background-color: #FAFAFA; border: 1px solid #ECE7E1; border-radius: 10px; overflow: hidden;">';
            $rowIndex = 0;
            foreach ($rows as $row) {
                $label = $this->escapeHtml((string)($row['label'] ?? ''));
                $value = $this->escapeHtml((string)($row['value'] ?? ''));
                $borderBottom = ($rowIndex < count($rows) - 1) ? 'border-bottom: 1px solid #EFEAE4;' : '';

                $rowsHtml .= <<<HTML
<tr>
    <td style="padding: 12px 18px; font-size: 13px; color: #766E65; width: 38%; vertical-align: top; font-weight: 500; {$borderBottom}">
        {$label}
    </td>
    <td style="padding: 12px 18px; font-size: 14px; color: #2C2724; font-weight: 600; text-align: right; vertical-align: top; {$borderBottom}">
        {$value}
    </td>
</tr>
HTML;
                $rowIndex++;
            }
            $rowsHtml .= '</table>';
        }

        // CTA button & fallback link
        $ctaHtml = '';
        if ($cta && !empty($cta['url']) && !empty($cta['text'])) {
            $ctaText = $this->escapeHtml((string)$cta['text']);
            $ctaUrl = $this->escapeHtml((string)$cta['url']);

            $ctaHtml = <<<HTML
<div style="margin-top: 32px; margin-bottom: 28px; text-align: center;">
    <a href="{$ctaUrl}" target="_blank" style="display: inline-block; background-color: #E05A47; color: #FFFFFF; font-size: 15px; font-weight: 700; text-decoration: none; padding: 14px 32px; border-radius: 8px; box-shadow: 0 4px 12px rgba(224, 90, 71, 0.25);">
        {$ctaText}
    </a>
    <p style="margin-top: 14px; margin-bottom: 0; font-size: 12px; color: #8F8780; line-height: 1.5;">
        Jika tombol di atas tidak dapat diklik, salin tautan berikut ke browser Anda:<br>
        <span style="word-break: break-all; color: #E05A47; font-size: 11px;">{$ctaUrl}</span>
    </p>
</div>
HTML;
        }

        $footerNoteHtml = '';
        if ($footerNote) {
            $footerNoteSafe = $this->escapeHtml($footerNote);
            $footerNoteHtml = <<<HTML
<p style="margin: 20px 0 0 0; padding-top: 16px; border-top: 1px dashed #ECE7E1; font-size: 13px; color: #766E65; line-height: 1.6;">
    💡 {$footerNoteSafe}
</p>
HTML;
        }

        return <<<HTML
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{$title}</title>
</head>
<body style="margin: 0; padding: 0; background-color: #F4EFEA; font-family: ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; -webkit-font-smoothing: antialiased; color: #2C2724;">
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color: #F4EFEA; padding: 40px 16px;">
        <tr>
            <td align="center">
                <!-- Outer Container -->
                <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="max-width: 580px; margin: 0 auto;">
                    <!-- Brand Header -->
                    <tr>
                        <td align="center" style="padding-bottom: 24px;">
                            <div style="display: inline-block;">
                                <table role="presentation" cellpadding="0" cellspacing="0">
                                    <tr>
                                        <td style="background-color: #E05A47; width: 34px; height: 34px; border-radius: 8px; text-align: center; vertical-align: middle;">
                                            <span style="color: #FFFFFF; font-size: 18px; font-weight: 800; line-height: 34px;">🎵</span>
                                        </td>
                                        <td style="padding-left: 10px; text-align: left;">
                                            <span style="font-size: 20px; font-weight: 800; letter-spacing: -0.5px; color: #2C2724; display: block;">Song Request</span>
                                            <span style="font-size: 11px; font-weight: 600; text-transform: uppercase; letter-spacing: 1px; color: #E05A47; display: block; margin-top: -2px;">Sistem Musik & Notifikasi</span>
                                        </td>
                                    </tr>
                                </table>
                            </div>
                        </td>
                    </tr>

                    <!-- Main Card -->
                    <tr>
                        <td>
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color: #FFFFFF; border: 1px solid #EAE3DC; border-radius: 14px; box-shadow: 0 4px 18px rgba(44, 39, 36, 0.04); overflow: hidden;">
                                <tr>
                                    <td style="padding: 36px 32px;">
                                        {$badgeHtml}

                                        <h1 style="margin: 0 0 16px 0; font-size: 22px; font-weight: 800; line-height: 1.35; color: #2C2724; letter-spacing: -0.3px;">
                                            {$title}
                                        </h1>

                                        <p style="margin: 0 0 14px 0; font-size: 15px; line-height: 1.6; color: #2C2724;">
                                            Halo <strong>{$name}</strong>,
                                        </p>

                                        <div style="font-size: 14px; line-height: 1.7; color: #524B44;">
                                            {$lead}
                                        </div>

                                        {$rowsHtml}

                                        {$ctaHtml}

                                        {$footerNoteHtml}
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    <!-- Footer -->
                    <tr>
                        <td align="center" style="padding-top: 28px; padding-bottom: 20px; text-align: center; font-size: 12px; line-height: 1.6; color: #8F8780;">
                            <p style="margin: 0 0 6px 0;">
                                Email ini dikirimkan otomatis oleh sistem <strong>Song Request</strong>. Mohon tidak membalas email ini secara langsung.
                            </p>
                            <p style="margin: 0; color: #A69E96;">
                                &copy; {$currentYear} Song Request. Seluruh hak cipta dilindungi.
                            </p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
HTML;
    }

    // =========================================================================
    // UTILITIES & DATA PROTECTION
    // =========================================================================

    protected function extractRecipientEmail(?array $user): ?string
    {
        if (!$user) {
            return null;
        }

        $email = trim((string) ($user['email'] ?? ''));
        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return null;
        }

        return $email;
    }

    protected function extractName(?array $user, string $fallback = ''): string
    {
        $name = trim((string) ($user['name'] ?? ''));
        if ($name !== '') {
            return $name;
        }

        $fallback = trim($fallback);
        if ($fallback !== '' && $fallback !== 'Anonim') {
            return $fallback;
        }

        return 'Pelanggan';
    }

    protected function maskAccountNumber(string $accountNumber): string
    {
        $accountNumber = trim($accountNumber);
        $len = strlen($accountNumber);
        if ($len <= 6) {
            return str_repeat('*', $len);
        }

        return substr($accountNumber, 0, 4) . str_repeat('*', $len - 7) . substr($accountNumber, -3);
    }

    protected function formatDate(string $datetime): string
    {
        try {
            $ts = strtotime($datetime);
            if ($ts === false) {
                return $datetime;
            }
            return date('d M Y, H:i', $ts) . ' WIB';
        } catch (\Throwable $e) {
            return $datetime;
        }
    }

    protected function siteUrl(string $path = ''): string
    {
        $baseUrl = rtrim((string) (config(AppConfig::class)->baseURL ?? ''), '/');
        return $baseUrl . '/' . ltrim($path, '/');
    }

    protected function escapeHtml(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}
