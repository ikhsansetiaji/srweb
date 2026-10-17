<?php

namespace App\Models;

use CodeIgniter\Model;

/**
 * CafeBalanceModel — kelola saldo kafe.
 *
 * PENTING:
 * - Semua metode yang memodifikasi saldo HARUS dipanggil di dalam database transaction.
 * - Gunakan operasi atomik SQL untuk mencegah race condition.
 * - Setiap mutasi saldo HARUS diikuti pencatatan di financial_ledger melalui FinancialLedgerModel.
 *
 * Definisi kolom:
 *   available_balance — saldo yang benar-benar bisa ditarik oleh admin kafe
 *   held_balance      — saldo yang sedang dialokasikan untuk withdrawal pending/approved/processing
 *   total_income      — total kumulatif pendapatan dari semua pembayaran berhasil (tidak berkurang)
 *   total_withdrawn   — total kumulatif withdrawal yang sudah benar-benar selesai (tidak berkurang)
 */
class CafeBalanceModel extends Model
{
    protected $table            = 'cafe_balances';
    protected $primaryKey       = 'cafe_id';
    protected $useAutoIncrement = false;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $allowedFields    = ['cafe_id', 'available_balance', 'held_balance', 'total_income', 'total_withdrawn', 'updated_at'];
    protected $useTimestamps    = false;

    /**
     * Get balance dengan safe NULL check.
     *
     * @param int $cafeId
     * @return array|null
     */
    public function getBalance(int $cafeId): ?array
    {
        return $this->find($cafeId);
    }

    /**
     * Tambah saldo ketika payment berhasil — ATOMIC SQL UPDATE.
     * WAJIB dipanggil di dalam database transaction yang aktif.
     * WAJIB diikuti oleh pencatatan ke financial_ledger.
     *
     * @param int $cafeId
     * @param int $amount  Nominal yang masuk (> 0)
     * @return int|false   available_balance baru, atau false jika gagal
     */
    public function addBalanceAtomic(int $cafeId, int $amount)
    {
        if ($amount <= 0) {
            log_message('warning', "CafeBalanceModel::addBalanceAtomic — invalid amount {$amount} for cafe {$cafeId}");
            return false;
        }

        $db = \Config\Database::connect();

        // Gunakan RETURNING untuk mendapatkan nilai baru setelah update (PostgreSQL)
        $result = $db->query(
            "UPDATE cafe_balances
             SET available_balance = available_balance + ?,
                 total_income      = total_income + ?,
                 updated_at        = NOW()
             WHERE cafe_id = ?
             RETURNING available_balance",
            [$amount, $amount, $cafeId]
        );

        if (!$result) {
            log_message('error', "CafeBalanceModel::addBalanceAtomic — UPDATE failed for cafe {$cafeId}");
            return false;
        }

        $row = $result->getRowArray();
        if (!$row) {
            log_message('error', "CafeBalanceModel::addBalanceAtomic — no row returned for cafe {$cafeId}");
            return false;
        }

        return (int) $row['available_balance'];
    }

    /**
     * Hold saldo untuk withdrawal — pindahkan dari available ke held.
     * WAJIB dipanggil di dalam database transaction dengan row lock (SELECT FOR UPDATE).
     * WAJIB diikuti oleh pencatatan ke financial_ledger.
     *
     * @param int $cafeId
     * @param int $amount  Nominal yang di-hold (> 0)
     * @return array|false ['available_balance' => int, 'held_balance' => int] atau false
     */
    public function holdBalance(int $cafeId, int $amount)
    {
        if ($amount <= 0) {
            log_message('warning', "CafeBalanceModel::holdBalance — invalid amount {$amount} for cafe {$cafeId}");
            return false;
        }

        $db = \Config\Database::connect();

        $result = $db->query(
            "UPDATE cafe_balances
             SET available_balance = available_balance - ?,
                 held_balance      = held_balance + ?,
                 updated_at        = NOW()
             WHERE cafe_id = ? AND available_balance >= ?
             RETURNING available_balance, held_balance",
            [$amount, $amount, $cafeId, $amount]
        );

        if (!$result) {
            log_message('error', "CafeBalanceModel::holdBalance — UPDATE failed for cafe {$cafeId}");
            return false;
        }

        $row = $result->getRowArray();
        if (!$row) {
            // Baris tidak terupdate — kemungkinan saldo tidak cukup (available < amount)
            log_message('warning', "CafeBalanceModel::holdBalance — insufficient balance for cafe {$cafeId}, amount {$amount}");
            return false;
        }

        return [
            'available_balance' => (int) $row['available_balance'],
            'held_balance'      => (int) $row['held_balance'],
        ];
    }

    /**
     * Kembalikan saldo dari held ke available (saat withdrawal ditolak/dibatalkan/gagal).
     * WAJIB dipanggil di dalam database transaction yang aktif.
     * WAJIB diikuti oleh pencatatan ke financial_ledger.
     *
     * @param int $cafeId
     * @param int $amount  Nominal yang dikembalikan (> 0)
     * @return array|false ['available_balance' => int, 'held_balance' => int] atau false
     */
    public function releaseHeldBalance(int $cafeId, int $amount)
    {
        if ($amount <= 0) {
            log_message('warning', "CafeBalanceModel::releaseHeldBalance — invalid amount {$amount} for cafe {$cafeId}");
            return false;
        }

        $db = \Config\Database::connect();

        $result = $db->query(
            "UPDATE cafe_balances
             SET available_balance = available_balance + ?,
                 held_balance      = GREATEST(held_balance - ?, 0),
                 updated_at        = NOW()
             WHERE cafe_id = ?
             RETURNING available_balance, held_balance",
            [$amount, $amount, $cafeId]
        );

        if (!$result) {
            log_message('error', "CafeBalanceModel::releaseHeldBalance — UPDATE failed for cafe {$cafeId}");
            return false;
        }

        $row = $result->getRowArray();
        if (!$row) {
            log_message('error', "CafeBalanceModel::releaseHeldBalance — no row returned for cafe {$cafeId}");
            return false;
        }

        return [
            'available_balance' => (int) $row['available_balance'],
            'held_balance'      => (int) $row['held_balance'],
        ];
    }

    /**
     * Selesaikan withdrawal — kurangi held_balance dan tambah total_withdrawn.
     * Dipanggil hanya saat transfer sudah benar-benar terkonfirmasi (status: completed).
     * WAJIB dipanggil di dalam database transaction yang aktif.
     * WAJIB diikuti oleh pencatatan ke financial_ledger.
     *
     * @param int $cafeId
     * @param int $amount  Nominal yang selesai ditransfer (> 0)
     * @return array|false ['held_balance' => int, 'total_withdrawn' => int] atau false
     */
    public function completeWithdrawal(int $cafeId, int $amount)
    {
        if ($amount <= 0) {
            log_message('warning', "CafeBalanceModel::completeWithdrawal — invalid amount {$amount} for cafe {$cafeId}");
            return false;
        }

        $db = \Config\Database::connect();

        $result = $db->query(
            "UPDATE cafe_balances
             SET held_balance    = GREATEST(held_balance - ?, 0),
                 total_withdrawn = total_withdrawn + ?,
                 updated_at      = NOW()
             WHERE cafe_id = ?
             RETURNING held_balance, total_withdrawn, available_balance",
            [$amount, $amount, $cafeId]
        );

        if (!$result) {
            log_message('error', "CafeBalanceModel::completeWithdrawal — UPDATE failed for cafe {$cafeId}");
            return false;
        }

        $row = $result->getRowArray();
        if (!$row) {
            log_message('error', "CafeBalanceModel::completeWithdrawal — no row returned for cafe {$cafeId}");
            return false;
        }

        return [
            'held_balance'      => (int) $row['held_balance'],
            'total_withdrawn'   => (int) $row['total_withdrawn'],
            'available_balance' => (int) $row['available_balance'],
        ];
    }

    /**
     * @deprecated Gunakan addBalanceAtomic() untuk mencegah race condition.
     * Dipertahankan untuk kompatibilitas kode lama — akan dihapus di versi berikutnya.
     */
    public function addBalance(int $cafeId, int $amount): bool
    {
        log_message('warning', 'CafeBalanceModel::addBalance() deprecated. Use addBalanceAtomic() inside a transaction.');
        $result = $this->addBalanceAtomic($cafeId, $amount);
        return $result !== false;
    }

    /**
     * @deprecated Gunakan completeWithdrawal() + releaseHeldBalance() sesuai alur baru.
     * Dipertahankan untuk kompatibilitas kode lama.
     */
    public function deductBalance(int $cafeId, int $amount): bool
    {
        log_message('warning', 'CafeBalanceModel::deductBalance() deprecated. Use completeWithdrawal() inside a transaction.');

        if ($amount <= 0) return false;

        $balance = $this->find($cafeId);
        if (!$balance) return false;
        if ($balance['available_balance'] < $amount) {
            log_message('warning', "Insufficient balance for cafe {$cafeId}. Required: {$amount}, Available: " . $balance['available_balance']);
            return false;
        }

        return $this->update($cafeId, [
            'available_balance' => $balance['available_balance'] - $amount,
            'total_withdrawn'   => $balance['total_withdrawn'] + $amount,
            'updated_at'        => date('Y-m-d H:i:s'),
        ]);
    }

    /**
     * Get top earning cafes.
     *
     * @param int $limit
     * @return array
     */
    public function getTopEarningCafes(int $limit = 10): array
    {
        return $this->select('cafe_balances.*, cafes.nama_kafe')
                    ->join('cafes', 'cafe_balances.cafe_id = cafes.id', 'left')
                    ->orderBy('cafe_balances.total_income', 'DESC')
                    ->limit($limit)
                    ->findAll();
    }

    /**
     * Total income seluruh sistem.
     *
     * @return int
     */
    public function getSystemTotalIncome(): int
    {
        $result = $this->selectSum('total_income')->first();
        return (int) ($result['total_income'] ?? 0);
    }

    /**
     * Total withdrawal selesai seluruh sistem.
     *
     * @return int
     */
    public function getSystemTotalWithdrawn(): int
    {
        $result = $this->selectSum('total_withdrawn')->first();
        return (int) ($result['total_withdrawn'] ?? 0);
    }
}
