<?php

namespace App\Models;

use CodeIgniter\Model;

/**
 * WithdrawalModel
 *
 * Alur status withdrawal:
 *   1. pending   → menunggu keputusan superadmin
 *   2. approved  → disetujui superadmin, transfer belum dilakukan
 *   3. processing→ transfer sedang diproses manual
 *   4. completed → transfer berhasil dan terverifikasi
 *   5. rejected  → ditolak superadmin (saldo dikembalikan)
 *   6. failed    → transfer gagal (saldo dikembalikan)
 *   7. cancelled → dibatalkan (saldo dikembalikan)
 *   [paid]       → status lama (kompatibilitas data historis, setara completed)
 *
 * PENTING: Semua method yang mengubah status dan saldo HARUS dipanggil
 *          melalui WithdrawalService yang mengelola transaction dan ledger.
 */
class WithdrawalModel extends Model
{
    protected $table            = 'withdrawals';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $allowedFields    = [
        'cafe_id', 'destination_type', 'amount', 'bank_name', 'account_number', 'account_holder',
        'status', 'approved_by', 'approved_at', 'rejection_reason',
        'notes', 'transfer_reference', 'transfer_proof',
        'processed_by', 'processed_at', 'completed_at', 'failure_reason',
        'created_at', 'updated_at',
    ];
    protected $useTimestamps = false;

    // Status yang dianggap "sedang berlangsung" (saldo masih di-hold)
    const ACTIVE_STATUSES = ['pending', 'approved', 'processing'];
    // Status yang dianggap "selesai" (saldo sudah dikurangi dari held)
    const COMPLETED_STATUSES = ['completed', 'paid'];
    // Status yang dianggap "dibatalkan" (saldo dikembalikan ke available)
    const CANCELLED_STATUSES = ['rejected', 'failed', 'cancelled'];

    protected $validationRules = [
        'cafe_id'          => 'required|integer',
        'amount'           => 'required|integer|greater_than[0]',
        'destination_type' => 'required|in_list[bank_transfer,e_wallet]',
        'bank_name'        => 'required|string|max_length[100]',
        'account_number'   => 'required|string|max_length[50]',
        'account_holder'   => 'required|string|max_length[100]',
    ];

    /**
     * Get withdrawal dengan info cafe dan admin.
     *
     * @param int $withdrawalId
     * @return array|null
     */
    public function getWithdrawalWithDetails(int $withdrawalId): ?array
    {
        return $this->select(
                'withdrawals.*, cafes.nama_kafe, cafes.admin_id as cafe_admin_id,
                 u_admin.name as admin_name, u_admin.email as admin_email,
                 u_approved.name as approved_by_name,
                 u_processed.name as processed_by_name'
            )
            ->join('cafes', 'withdrawals.cafe_id = cafes.id', 'left')
            ->join('users u_admin', 'cafes.admin_id = u_admin.id', 'left')
            ->join('users u_approved', 'withdrawals.approved_by = u_approved.id', 'left')
            ->join('users u_processed', 'withdrawals.processed_by = u_processed.id', 'left')
            ->where('withdrawals.id', $withdrawalId)
            ->first();
    }

    /**
     * Get pending withdrawals untuk superadmin (semua status).
     *
     * @param string|null $status Filter status (null = pending saja)
     * @param int         $limit
     * @param int         $offset
     * @return array
     */
    public function getAllWithdrawals(?string $status = null, int $limit = 50, int $offset = 0): array
    {
        $q = $this->select(
                'withdrawals.*, cafes.nama_kafe,
                 u_admin.name as admin_name, u_admin.email as admin_email,
                 u_approved.name as approved_by_name'
            )
            ->join('cafes', 'withdrawals.cafe_id = cafes.id', 'left')
            ->join('users u_admin', 'cafes.admin_id = u_admin.id', 'left')
            ->join('users u_approved', 'withdrawals.approved_by = u_approved.id', 'left');

        if ($status) {
            $q = $q->where('withdrawals.status', $status);
        }

        return $q->orderBy('withdrawals.created_at', 'DESC')
                 ->limit($limit, $offset)
                 ->findAll();
    }

    /**
     * Get pending withdrawals (status = pending saja).
     *
     * @return array
     */
    public function getPendingWithdrawals(): array
    {
        return $this->getAllWithdrawals('pending');
    }

    /**
     * Get withdrawal history untuk cafe dengan pagination.
     *
     * @param int    $cafeId
     * @param int    $limit
     * @param int    $offset
     * @return array
     */
    public function getCafeWithdrawals(int $cafeId, int $limit = 50, int $offset = 0): array
    {
        return $this->select(
                'withdrawals.*, u_approved.name as approved_by_name, u_processed.name as processed_by_name'
            )
            ->join('users u_approved', 'withdrawals.approved_by = u_approved.id', 'left')
            ->join('users u_processed', 'withdrawals.processed_by = u_processed.id', 'left')
            ->where('cafe_id', $cafeId)
            ->orderBy('created_at', 'DESC')
            ->limit($limit, $offset)
            ->findAll();
    }

    /**
     * Count pending withdrawals untuk notifikasi dashboard.
     *
     * @return int
     */
    public function countPendingWithdrawals(): int
    {
        return $this->where('status', 'pending')->countAllResults();
    }

    /**
     * Count withdrawals berdasarkan status untuk satu cafe.
     *
     * @param int    $cafeId
     * @param string $status
     * @return int
     */
    public function countCafeWithdrawalsByStatus(int $cafeId, string $status): int
    {
        return $this->where('cafe_id', $cafeId)->where('status', $status)->countAllResults();
    }

    /**
     * Hitung total withdrawal aktif (pending/approved/processing) untuk cafe.
     * Berguna untuk verifikasi bahwa held_balance cukup.
     *
     * @param int $cafeId
     * @return int
     */
    public function getTotalActiveWithdrawalAmount(int $cafeId): int
    {
        $result = $this->selectSum('amount')
                       ->where('cafe_id', $cafeId)
                       ->whereIn('status', self::ACTIVE_STATUSES)
                       ->first();
        return (int) ($result['amount'] ?? 0);
    }

    /**
     * Hitung total withdrawal selesai untuk cafe.
     *
     * @param int $cafeId
     * @return int
     */
    public function getTotalCompletedAmount(int $cafeId): int
    {
        $result = $this->selectSum('amount')
                       ->where('cafe_id', $cafeId)
                       ->whereIn('status', self::COMPLETED_STATUSES)
                       ->first();
        return (int) ($result['amount'] ?? 0);
    }

    /**
     * Cek apakah ada withdrawal aktif lain untuk cafe yang sama yang belum selesai.
     * Digunakan untuk informasi UI (bukan sebagai pembatas — pembatas adalah saldo).
     *
     * @param int $cafeId
     * @return bool
     */
    public function hasActiveWithdrawal(int $cafeId): bool
    {
        return $this->where('cafe_id', $cafeId)
                    ->whereIn('status', self::ACTIVE_STATUSES)
                    ->countAllResults() > 0;
    }
}
