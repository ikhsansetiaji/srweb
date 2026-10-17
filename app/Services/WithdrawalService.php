<?php

namespace App\Services;

use App\Models\WithdrawalModel;
use App\Models\CafeBalanceModel;
use App\Models\CafeModel;
use App\Models\FinancialLedgerModel;

/**
 * WithdrawalService — logika bisnis penarikan saldo.
 *
 * Alur withdrawal dua tahap (WAJIB):
 *
 * [Admin Kafe]
 *   requestWithdrawal()  → status: pending
 *                          saldo di-hold (available ↓, held ↑)
 *                          ledger: withdrawal_hold
 *
 * [Superadmin]
 *   approveWithdrawal()  → status: approved
 *                          saldo tetap di-hold
 *
 *   rejectWithdrawal()   → status: rejected
 *                          saldo dikembalikan (available ↑, held ↓)
 *                          ledger: withdrawal_release
 *
 *   markAsProcessing()   → status: processing
 *                          superadmin mulai transfer manual
 *
 *   completeWithdrawal() → status: completed
 *                          held dikurangi, total_withdrawn ↑
 *                          ledger: withdrawal_complete
 *                          simpan referensi transfer
 *
 *   failWithdrawal()     → status: failed
 *                          saldo dikembalikan (available ↑, held ↓)
 *                          ledger: withdrawal_release
 */
class WithdrawalService
{
    protected $withdrawalModel;
    protected $balanceModel;
    protected $cafeModel;
    protected $ledgerModel;
    protected $db;

    public function __construct()
    {
        $this->withdrawalModel = new WithdrawalModel();
        $this->balanceModel    = new CafeBalanceModel();
        $this->cafeModel       = new CafeModel();
        $this->ledgerModel     = new FinancialLedgerModel();
        $this->db              = \Config\Database::connect();
    }

    // =========================================================================
    // ADMIN KAFE — Pengajuan Withdrawal
    // =========================================================================

    /**
     * Ajukan withdrawal baru dari admin kafe.
     * Langsung menahan saldo (hold) saat pengajuan dibuat.
     *
     * @param int   $cafeId
     * @param array $data   ['amount', 'destination_type', 'bank_name', 'account_number', 'account_holder', 'notes'?]
     * @param int|null $actorId User ID admin kafe yang mengajukan
     * @return array ['success' => bool, 'message' => string, 'withdrawal_id'? => int]
     */
    public function requestWithdrawal(int $cafeId, array $data, ?int $actorId = null): array
    {
        // === Validasi awal (sebelum masuk transaction) ===
        $cafe = $this->cafeModel->find($cafeId);
        if (!$cafe) {
            return ['success' => false, 'message' => 'Cafe tidak ditemukan'];
        }

        if ($cafe['status'] !== 'approved' || !$cafe['is_active']) {
            return ['success' => false, 'message' => 'Cafe belum disetujui atau tidak aktif'];
        }

        $amount = (int) ($data['amount'] ?? 0);
        if ($amount < 10000) {
            return ['success' => false, 'message' => 'Minimal withdrawal Rp 10.000'];
        }

        $destinationType = $data['destination_type'] ?? 'bank_transfer';
        if (!in_array($destinationType, ['bank_transfer', 'e_wallet'], true)) {
            return ['success' => false, 'message' => 'Tujuan penarikan tidak valid'];
        }

        if (empty(trim((string)($data['bank_name'] ?? '')))) {
            return ['success' => false, 'message' => 'Nama bank / provider e-wallet wajib diisi'];
        }

        if (empty(trim((string)($data['account_number'] ?? '')))) {
            return ['success' => false, 'message' => 'Nomor rekening / nomor e-wallet wajib diisi'];
        }

        if (empty(trim((string)($data['account_holder'] ?? '')))) {
            return ['success' => false, 'message' => 'Nama pemilik rekening / akun wajib diisi'];
        }

        // === Transaction dengan row lock untuk mencegah race condition ===
        $this->db->transStart();

        try {
            // Lock baris balance terlebih dahulu (PostgreSQL: SELECT FOR UPDATE)
            $balance = $this->db->query(
                'SELECT * FROM cafe_balances WHERE cafe_id = ? FOR UPDATE',
                [$cafeId]
            )->getRowArray();

            if (!$balance) {
                $this->db->transRollback();
                return ['success' => false, 'message' => 'Data saldo tidak ditemukan'];
            }

            if ((int)$balance['available_balance'] < $amount) {
                $this->db->transRollback();
                return [
                    'success' => false,
                    'message' => 'Saldo tidak cukup. Saldo tersedia: Rp ' . number_format($balance['available_balance'], 2, ',', '.')
                ];
            }

            // Insert withdrawal record
            $now = date('Y-m-d H:i:s');
            $withdrawalId = $this->withdrawalModel->insert([
                'cafe_id'          => $cafeId,
                'destination_type' => $destinationType,
                'amount'           => $amount,
                'bank_name'        => trim($data['bank_name']),
                'account_number'   => trim($data['account_number']),
                'account_holder'   => trim($data['account_holder']),
                'notes'            => trim($data['notes'] ?? ''),
                'status'           => 'pending',
                'created_at'       => $now,
                'updated_at'       => $now,
            ]);

            if (!$withdrawalId) {
                $this->db->transRollback();
                log_message('error', 'WithdrawalService::requestWithdrawal — insert failed: ' . json_encode($this->withdrawalModel->errors()));
                return ['success' => false, 'message' => 'Gagal membuat pengajuan withdrawal'];
            }

            $withdrawalId = $this->withdrawalModel->getInsertID();

            // Hold saldo (atomic SQL)
            $newBalances = $this->balanceModel->holdBalance($cafeId, $amount);
            if ($newBalances === false) {
                $this->db->transRollback();
                return ['success' => false, 'message' => 'Gagal menahan saldo. Kemungkinan saldo tidak cukup.'];
            }

            // Catat ke ledger
            $ledgerEntry = $this->ledgerModel->recordDebit(
                $cafeId,
                $amount,
                FinancialLedgerModel::TYPE_WITHDRAWAL_HOLD,
                $newBalances['available_balance'],
                'withdrawal',
                $withdrawalId,
                $actorId,
                'Saldo ditahan untuk pengajuan withdrawal #' . $withdrawalId
            );

            if (!$ledgerEntry) {
                $this->db->transRollback();
                return ['success' => false, 'message' => 'Gagal mencatat mutasi ledger'];
            }

            $this->db->transComplete();

            if (!$this->db->transStatus()) {
                return ['success' => false, 'message' => 'Transaction gagal dikommit'];
            }

            log_message('info', "Withdrawal #{$withdrawalId} created for cafe {$cafeId}, amount {$amount}, balance held");

            // Notifikasi WA: withdrawal diajukan (status: pending)
            try {
                $wa = service('whatsappNotification');
                $wa->onWithdrawalStatusChanged($withdrawalId, 'pending');
            } catch (\Throwable $waEx) {
                log_message('error', 'WA withdrawal pending notification error: ' . $waEx->getMessage());
            }

            // Notifikasi Email: konfirmasi ke admin kafe + pemberitahuan ke superadmin
            try {
                $email = service('notificationEmail');
                $email->onWithdrawalRequested((int)$withdrawalId);
            } catch (\Throwable $emailEx) {
                log_message('error', 'Email withdrawal pending notification error: ' . $emailEx->getMessage());
            }

            return [
                'success'       => true,
                'message'       => 'Pengajuan withdrawal berhasil. Menunggu persetujuan superadmin.',
                'withdrawal_id' => $withdrawalId,
            ];

        } catch (\Exception $e) {
            $this->db->transRollback();
            log_message('error', 'WithdrawalService::requestWithdrawal exception: ' . $e->getMessage());
            return ['success' => false, 'message' => 'Terjadi kesalahan sistem'];
        }
    }

    // =========================================================================
    // SUPERADMIN — Tahap A: Persetujuan
    // =========================================================================

    /**
     * Setujui withdrawal (Tahap A).
     * Tidak mengubah saldo — hanya mengubah status dari pending → approved.
     * Admin kafe TIDAK BOLEH menyetujui pengajuannya sendiri.
     *
     * @param int $withdrawalId
     * @param int $superadminId User ID superadmin yang menyetujui
     * @return array
     */
    public function approveWithdrawal(int $withdrawalId, int $superadminId): array
    {
        $this->db->transStart();

        try {
            // Lock baris withdrawal
            $withdrawal = $this->db->query(
                'SELECT w.*, c.admin_id as cafe_admin_id FROM withdrawals w
                 JOIN cafes c ON w.cafe_id = c.id
                 WHERE w.id = ? FOR UPDATE',
                [$withdrawalId]
            )->getRowArray();

            if (!$withdrawal) {
                $this->db->transRollback();
                return ['success' => false, 'message' => 'Withdrawal tidak ditemukan'];
            }

            if ($withdrawal['status'] !== 'pending') {
                $this->db->transRollback();
                return ['success' => false, 'message' => 'Hanya withdrawal dengan status pending yang dapat disetujui. Status saat ini: ' . $withdrawal['status']];
            }

            // Superadmin tidak boleh menyetujui withdrawal dari kafenya sendiri
            // (jika superadmin juga admin dari kafe tertentu)
            if ((int)$withdrawal['cafe_admin_id'] === $superadminId) {
                $this->db->transRollback();
                return ['success' => false, 'message' => 'Tidak dapat menyetujui pengajuan dari kafe yang Anda kelola sendiri'];
            }

            $now = date('Y-m-d H:i:s');
            $updated = $this->withdrawalModel->update($withdrawalId, [
                'status'      => 'approved',
                'approved_by' => $superadminId,
                'approved_at' => $now,
                'updated_at'  => $now,
            ]);

            if (!$updated) {
                $this->db->transRollback();
                return ['success' => false, 'message' => 'Gagal mengupdate status withdrawal'];
            }

            $this->db->transComplete();

            if (!$this->db->transStatus()) {
                return ['success' => false, 'message' => 'Transaction gagal dikommit'];
            }

            log_message('info', "Withdrawal #{$withdrawalId} approved by superadmin #{$superadminId}");

            // Notifikasi WA: withdrawal disetujui
            try {
                $wa = service('whatsappNotification');
                $wa->onWithdrawalStatusChanged($withdrawalId, 'approved');
            } catch (\Throwable $waEx) {
                log_message('error', 'WA withdrawal approved notification error: ' . $waEx->getMessage());
            }

            // Notifikasi Email: penarikan disetujui ke admin kafe
            try {
                $email = service('notificationEmail');
                $email->onWithdrawalApproved((int)$withdrawalId);
            } catch (\Throwable $emailEx) {
                log_message('error', 'Email withdrawal approved notification error: ' . $emailEx->getMessage());
            }

            return [
                'success' => true,
                'message' => 'Withdrawal disetujui. Silakan lakukan transfer dan catat hasilnya.',
            ];

        } catch (\Exception $e) {
            $this->db->transRollback();
            log_message('error', 'WithdrawalService::approveWithdrawal exception: ' . $e->getMessage());
            return ['success' => false, 'message' => 'Terjadi kesalahan sistem'];
        }
    }

    /**
     * Tolak withdrawal (Tahap A alternatif).
     * Saldo dikembalikan dari held ke available.
     *
     * @param int    $withdrawalId
     * @param int    $superadminId
     * @param string $reason
     * @return array
     */
    public function rejectWithdrawal(int $withdrawalId, int $superadminId, string $reason): array
    {
        if (strlen(trim($reason)) < 10) {
            return ['success' => false, 'message' => 'Alasan penolakan minimal 10 karakter'];
        }

        $this->db->transStart();

        try {
            $withdrawal = $this->db->query(
                'SELECT * FROM withdrawals WHERE id = ? FOR UPDATE',
                [$withdrawalId]
            )->getRowArray();

            if (!$withdrawal) {
                $this->db->transRollback();
                return ['success' => false, 'message' => 'Withdrawal tidak ditemukan'];
            }

            // Hanya pending dan approved yang bisa ditolak
            if (!in_array($withdrawal['status'], ['pending', 'approved'], true)) {
                $this->db->transRollback();
                return ['success' => false, 'message' => 'Withdrawal dengan status ' . $withdrawal['status'] . ' tidak dapat ditolak'];
            }

            $now = date('Y-m-d H:i:s');
            $updated = $this->withdrawalModel->update($withdrawalId, [
                'status'           => 'rejected',
                'approved_by'      => $superadminId,
                'approved_at'      => $now,
                'rejection_reason' => trim($reason),
                'updated_at'       => $now,
            ]);

            if (!$updated) {
                $this->db->transRollback();
                return ['success' => false, 'message' => 'Gagal mengupdate status withdrawal'];
            }

            // Kembalikan saldo dari held ke available
            $cafeId = (int)$withdrawal['cafe_id'];
            $amount = (int)$withdrawal['amount'];

            $newBalances = $this->balanceModel->releaseHeldBalance($cafeId, $amount);
            if ($newBalances === false) {
                $this->db->transRollback();
                log_message('error', "WithdrawalService::rejectWithdrawal — failed to release held balance for cafe {$cafeId}, withdrawal #{$withdrawalId}");
                return ['success' => false, 'message' => 'Gagal mengembalikan saldo tertahan'];
            }

            // Catat ke ledger (reversal dari withdrawal_hold)
            $ledgerEntry = $this->ledgerModel->recordCredit(
                $cafeId,
                $amount,
                FinancialLedgerModel::TYPE_WITHDRAWAL_RELEASE,
                $newBalances['available_balance'],
                'withdrawal',
                $withdrawalId,
                $superadminId,
                'Saldo dikembalikan — withdrawal #' . $withdrawalId . ' ditolak. Alasan: ' . $reason
            );

            if (!$ledgerEntry) {
                $this->db->transRollback();
                return ['success' => false, 'message' => 'Gagal mencatat mutasi ledger'];
            }

            $this->db->transComplete();

            if (!$this->db->transStatus()) {
                return ['success' => false, 'message' => 'Transaction gagal dikommit'];
            }

            log_message('info', "Withdrawal #{$withdrawalId} rejected by superadmin #{$superadminId}. Balance released.");

            // Notifikasi WA: withdrawal ditolak (sertakan alasan)
            try {
                $wa = service('whatsappNotification');
                $wa->onWithdrawalStatusChanged($withdrawalId, 'rejected', $reason);
            } catch (\Throwable $waEx) {
                log_message('error', 'WA withdrawal rejected notification error: ' . $waEx->getMessage());
            }

            // Notifikasi Email: penarikan ditolak ke admin kafe
            try {
                $email = service('notificationEmail');
                $email->onWithdrawalRejected((int)$withdrawalId, $reason);
            } catch (\Throwable $emailEx) {
                log_message('error', 'Email withdrawal rejected notification error: ' . $emailEx->getMessage());
            }

            return [
                'success' => true,
                'message' => 'Withdrawal ditolak dan saldo dikembalikan ke kafe.',
            ];

        } catch (\Exception $e) {
            $this->db->transRollback();
            log_message('error', 'WithdrawalService::rejectWithdrawal exception: ' . $e->getMessage());
            return ['success' => false, 'message' => 'Terjadi kesalahan sistem'];
        }
    }

    // =========================================================================
    // SUPERADMIN — Tahap B: Pelaksanaan Transfer
    // =========================================================================

    /**
     * Tandai withdrawal sebagai sedang diproses (Tahap B - mulai).
     * Status: approved → processing
     *
     * @param int    $withdrawalId
     * @param int    $superadminId
     * @param string|null $notes Catatan proses
     * @return array
     */
    public function markAsProcessing(int $withdrawalId, int $superadminId, ?string $notes = null): array
    {
        $this->db->transStart();

        try {
            $withdrawal = $this->db->query(
                'SELECT * FROM withdrawals WHERE id = ? FOR UPDATE',
                [$withdrawalId]
            )->getRowArray();

            if (!$withdrawal) {
                $this->db->transRollback();
                return ['success' => false, 'message' => 'Withdrawal tidak ditemukan'];
            }

            if ($withdrawal['status'] !== 'approved') {
                $this->db->transRollback();
                return ['success' => false, 'message' => 'Hanya withdrawal approved yang dapat ditandai processing. Status saat ini: ' . $withdrawal['status']];
            }

            $now = date('Y-m-d H:i:s');
            $updateData = [
                'status'       => 'processing',
                'processed_by' => $superadminId,
                'processed_at' => $now,
                'updated_at'   => $now,
            ];
            if ($notes) {
                $updateData['notes'] = $notes;
            }

            $updated = $this->withdrawalModel->update($withdrawalId, $updateData);

            if (!$updated) {
                $this->db->transRollback();
                return ['success' => false, 'message' => 'Gagal mengupdate status withdrawal'];
            }

            $this->db->transComplete();

            if (!$this->db->transStatus()) {
                return ['success' => false, 'message' => 'Transaction gagal dikommit'];
            }

            log_message('info', "Withdrawal #{$withdrawalId} marked as processing by superadmin #{$superadminId}");

            // Notifikasi WA: withdrawal sedang diproses
            try {
                $wa = service('whatsappNotification');
                $wa->onWithdrawalStatusChanged($withdrawalId, 'processing');
            } catch (\Throwable $waEx) {
                log_message('error', 'WA withdrawal processing notification error: ' . $waEx->getMessage());
            }

            return [
                'success' => true,
                'message' => 'Withdrawal ditandai sedang diproses. Lakukan transfer manual dan catat hasilnya.',
            ];

        } catch (\Exception $e) {
            $this->db->transRollback();
            log_message('error', 'WithdrawalService::markAsProcessing exception: ' . $e->getMessage());
            return ['success' => false, 'message' => 'Terjadi kesalahan sistem'];
        }
    }

    /**
     * Selesaikan withdrawal setelah transfer terkonfirmasi (Tahap B - selesai).
     * Status: processing → completed
     * Saldo held dikurangi, total_withdrawn bertambah.
     *
     * @param int    $withdrawalId
     * @param int    $superadminId
     * @param string $transferReference Nomor referensi transfer
     * @param string|null $transferProof Path/URL bukti transfer
     * @param string|null $notes
     * @return array
     */
    public function completeWithdrawal(
        int $withdrawalId,
        int $superadminId,
        string $transferReference,
        ?string $transferProof = null,
        ?string $notes = null
    ): array {
        if (empty(trim($transferReference))) {
            return ['success' => false, 'message' => 'Referensi transfer wajib diisi'];
        }

        $this->db->transStart();

        try {
            $withdrawal = $this->db->query(
                'SELECT * FROM withdrawals WHERE id = ? FOR UPDATE',
                [$withdrawalId]
            )->getRowArray();

            if (!$withdrawal) {
                $this->db->transRollback();
                return ['success' => false, 'message' => 'Withdrawal tidak ditemukan'];
            }

            // Hanya approved atau processing yang bisa di-complete
            if (!in_array($withdrawal['status'], ['approved', 'processing'], true)) {
                $this->db->transRollback();
                return ['success' => false, 'message' => 'Withdrawal dengan status ' . $withdrawal['status'] . ' tidak dapat diselesaikan'];
            }

            $cafeId = (int)$withdrawal['cafe_id'];
            $amount = (int)$withdrawal['amount'];

            $now = date('Y-m-d H:i:s');
            $updateData = [
                'status'             => 'completed',
                'transfer_reference' => trim($transferReference),
                'completed_at'       => $now,
                'updated_at'         => $now,
            ];
            if ($transferProof) {
                $updateData['transfer_proof'] = $transferProof;
            }
            if ($notes) {
                $updateData['notes'] = $notes;
            }
            // Set processed_by jika belum diisi (jika langsung dari approved ke completed)
            if (!$withdrawal['processed_by']) {
                $updateData['processed_by'] = $superadminId;
                $updateData['processed_at'] = $now;
            }

            $updated = $this->withdrawalModel->update($withdrawalId, $updateData);

            if (!$updated) {
                $this->db->transRollback();
                return ['success' => false, 'message' => 'Gagal mengupdate status withdrawal'];
            }

            // Kurangi held_balance dan tambah total_withdrawn (atomic SQL)
            $newBalances = $this->balanceModel->completeWithdrawal($cafeId, $amount);
            if ($newBalances === false) {
                $this->db->transRollback();
                log_message('error', "WithdrawalService::completeWithdrawal — failed to complete balance for cafe {$cafeId}, withdrawal #{$withdrawalId}");
                return ['success' => false, 'message' => 'Gagal memperbarui saldo'];
            }

            // Catat ke ledger
            $ledgerEntry = $this->ledgerModel->recordDebit(
                $cafeId,
                $amount,
                FinancialLedgerModel::TYPE_WITHDRAWAL_COMPLETE,
                $newBalances['available_balance'],
                'withdrawal',
                $withdrawalId,
                $superadminId,
                'Transfer selesai — ref: ' . $transferReference
            );

            if (!$ledgerEntry) {
                $this->db->transRollback();
                return ['success' => false, 'message' => 'Gagal mencatat mutasi ledger'];
            }

            $this->db->transComplete();

            if (!$this->db->transStatus()) {
                return ['success' => false, 'message' => 'Transaction gagal dikommit'];
            }

            log_message('info', "Withdrawal #{$withdrawalId} completed by superadmin #{$superadminId}. Ref: {$transferReference}");

            // Notifikasi WA: withdrawal selesai (transfer terverifikasi)
            try {
                $wa = service('whatsappNotification');
                $wa->onWithdrawalStatusChanged($withdrawalId, 'completed');
            } catch (\Throwable $waEx) {
                log_message('error', 'WA withdrawal completed notification error: ' . $waEx->getMessage());
            }

            // Notifikasi Email: transfer selesai terverifikasi ke admin kafe
            try {
                $email = service('notificationEmail');
                $email->onWithdrawalCompleted((int)$withdrawalId, $transferReference);
            } catch (\Throwable $emailEx) {
                log_message('error', 'Email withdrawal completed notification error: ' . $emailEx->getMessage());
            }

            return [
                'success' => true,
                'message' => 'Transfer berhasil dicatat. Withdrawal selesai.',
            ];

        } catch (\Exception $e) {
            $this->db->transRollback();
            log_message('error', 'WithdrawalService::completeWithdrawal exception: ' . $e->getMessage());
            return ['success' => false, 'message' => 'Terjadi kesalahan sistem'];
        }
    }

    /**
     * Tandai transfer gagal — saldo dikembalikan ke available.
     * Status: processing → failed
     *
     * @param int    $withdrawalId
     * @param int    $superadminId
     * @param string $failureReason Alasan kegagalan
     * @return array
     */
    public function failWithdrawal(int $withdrawalId, int $superadminId, string $failureReason): array
    {
        if (strlen(trim($failureReason)) < 5) {
            return ['success' => false, 'message' => 'Alasan kegagalan wajib diisi (minimal 5 karakter)'];
        }

        $this->db->transStart();

        try {
            $withdrawal = $this->db->query(
                'SELECT * FROM withdrawals WHERE id = ? FOR UPDATE',
                [$withdrawalId]
            )->getRowArray();

            if (!$withdrawal) {
                $this->db->transRollback();
                return ['success' => false, 'message' => 'Withdrawal tidak ditemukan'];
            }

            if ($withdrawal['status'] !== 'processing') {
                $this->db->transRollback();
                return ['success' => false, 'message' => 'Hanya withdrawal processing yang dapat ditandai gagal'];
            }

            $cafeId = (int)$withdrawal['cafe_id'];
            $amount = (int)$withdrawal['amount'];

            $now = date('Y-m-d H:i:s');
            $updated = $this->withdrawalModel->update($withdrawalId, [
                'status'         => 'failed',
                'failure_reason' => trim($failureReason),
                'updated_at'     => $now,
            ]);

            if (!$updated) {
                $this->db->transRollback();
                return ['success' => false, 'message' => 'Gagal mengupdate status withdrawal'];
            }

            // Kembalikan saldo dari held ke available
            $newBalances = $this->balanceModel->releaseHeldBalance($cafeId, $amount);
            if ($newBalances === false) {
                $this->db->transRollback();
                log_message('error', "WithdrawalService::failWithdrawal — failed to release balance for cafe {$cafeId}, withdrawal #{$withdrawalId}");
                return ['success' => false, 'message' => 'Gagal mengembalikan saldo tertahan'];
            }

            // Catat ke ledger
            $ledgerEntry = $this->ledgerModel->recordCredit(
                $cafeId,
                $amount,
                FinancialLedgerModel::TYPE_WITHDRAWAL_RELEASE,
                $newBalances['available_balance'],
                'withdrawal',
                $withdrawalId,
                $superadminId,
                'Saldo dikembalikan — transfer withdrawal #' . $withdrawalId . ' gagal. ' . $failureReason
            );

            if (!$ledgerEntry) {
                $this->db->transRollback();
                return ['success' => false, 'message' => 'Gagal mencatat mutasi ledger'];
            }

            $this->db->transComplete();

            if (!$this->db->transStatus()) {
                return ['success' => false, 'message' => 'Transaction gagal dikommit'];
            }

            log_message('info', "Withdrawal #{$withdrawalId} failed. Balance released for cafe {$cafeId}.");

            // Notifikasi WA: transfer gagal
            try {
                $wa = service('whatsappNotification');
                $wa->onWithdrawalStatusChanged($withdrawalId, 'failed', $failureReason);
            } catch (\Throwable $waEx) {
                log_message('error', 'WA withdrawal failed notification error: ' . $waEx->getMessage());
            }

            return [
                'success' => true,
                'message' => 'Transfer dicatat sebagai gagal. Saldo dikembalikan ke kafe.',
            ];

        } catch (\Exception $e) {
            $this->db->transRollback();
            log_message('error', 'WithdrawalService::failWithdrawal exception: ' . $e->getMessage());
            return ['success' => false, 'message' => 'Terjadi kesalahan sistem'];
        }
    }

    // =========================================================================
    // Query Helpers
    // =========================================================================

    /**
     * Get pending withdrawals untuk superadmin.
     *
     * @return array
     */
    public function getPendingWithdrawals(): array
    {
        return $this->withdrawalModel->getPendingWithdrawals();
    }

    /**
     * Get semua withdrawal dengan filter opsional.
     *
     * @param string|null $status
     * @param int         $limit
     * @param int         $offset
     * @return array
     */
    public function getAllWithdrawals(?string $status = null, int $limit = 50, int $offset = 0): array
    {
        return $this->withdrawalModel->getAllWithdrawals($status, $limit, $offset);
    }

    /**
     * Get withdrawal history untuk cafe.
     *
     * @param int $cafeId
     * @param int $limit
     * @param int $offset
     * @return array
     */
    public function getCafeWithdrawalHistory(int $cafeId, int $limit = 50, int $offset = 0): array
    {
        return $this->withdrawalModel->getCafeWithdrawals($cafeId, $limit, $offset);
    }

    /**
     * Get withdrawal detail lengkap (untuk superadmin).
     *
     * @param int $withdrawalId
     * @return array|null
     */
    public function getWithdrawalDetail(int $withdrawalId): ?array
    {
        return $this->withdrawalModel->getWithdrawalWithDetails($withdrawalId);
    }

    /**
     * Get ringkasan finance untuk satu cafe (untuk panel admin kafe).
     *
     * @param int $cafeId
     * @return array
     */
    public function getCafeFinanceSummary(int $cafeId): array
    {
        $balance = $this->balanceModel->getBalance($cafeId);
        $pendingCount = $this->withdrawalModel->countCafeWithdrawalsByStatus($cafeId, 'pending');
        $approvedCount = $this->withdrawalModel->countCafeWithdrawalsByStatus($cafeId, 'approved');
        $processingCount = $this->withdrawalModel->countCafeWithdrawalsByStatus($cafeId, 'processing');

        return [
            'total_income'      => (int)($balance['total_income'] ?? 0),
            'platform_fee'      => 0, // Platform fee = 0% per keputusan bisnis
            'net_income'        => (int)($balance['total_income'] ?? 0),
            'available_balance' => (int)($balance['available_balance'] ?? 0),
            'held_balance'      => (int)($balance['held_balance'] ?? 0),
            'total_withdrawn'   => (int)($balance['total_withdrawn'] ?? 0),
            'pending_count'     => $pendingCount,
            'approved_count'    => $approvedCount,
            'processing_count'  => $processingCount,
        ];
    }

    /**
     * @deprecated Gunakan markAsProcessing() + completeWithdrawal() sesuai alur baru.
     */
    public function markAsPaid(int $withdrawalId, int $superadminId): array
    {
        log_message('warning', 'WithdrawalService::markAsPaid() deprecated. Use completeWithdrawal() instead.');
        return $this->completeWithdrawal(
            $withdrawalId,
            $superadminId,
            'manual-' . $withdrawalId . '-' . time(),
            null,
            'Marked as paid via legacy API'
        );
    }
}
