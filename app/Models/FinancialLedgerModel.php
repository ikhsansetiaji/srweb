<?php

namespace App\Models;

use CodeIgniter\Model;

class FinancialLedgerModel extends Model
{
    protected $table      = 'financial_ledger';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    protected $useSoftDeletes = false;
    protected $allowedFields = [
        'cafe_id', 'entry_type', 'debit', 'credit', 'balance_after',
        'reference_type', 'reference_id', 'actor_id', 'notes',
        'correction_of', 'created_at',
    ];
    protected $useTimestamps = false;

    // Valid entry types
    const TYPE_PAYMENT_INCOME      = 'payment_income';
    const TYPE_WITHDRAWAL_HOLD     = 'withdrawal_hold';
    const TYPE_WITHDRAWAL_RELEASE  = 'withdrawal_release';
    const TYPE_WITHDRAWAL_COMPLETE = 'withdrawal_complete';
    const TYPE_CORRECTION_CREDIT   = 'correction_credit';
    const TYPE_CORRECTION_DEBIT    = 'correction_debit';

    /**
     * Catat kredit (uang masuk) ke saldo kafe.
     * WAJIB dipanggil di dalam database transaction yang sudah aktif.
     *
     * @param int         $cafeId
     * @param int         $amount       Nominal kredit (> 0)
     * @param string      $entryType    Lihat konstanta TYPE_*
     * @param int         $balanceAfter Saldo available_balance setelah operasi
     * @param string|null $refType
     * @param int|null    $refId
     * @param int|null    $actorId
     * @param string|null $notes
     * @return int|false  ID entri ledger yang baru
     */
    public function recordCredit(
        int $cafeId,
        int $amount,
        string $entryType,
        int $balanceAfter,
        ?string $refType = null,
        ?int $refId = null,
        ?int $actorId = null,
        ?string $notes = null
    ) {
        if ($amount <= 0) {
            log_message('error', "FinancialLedgerModel::recordCredit — amount harus > 0, got {$amount}");
            return false;
        }

        $data = [
            'cafe_id'        => $cafeId,
            'entry_type'     => $entryType,
            'debit'          => 0,
            'credit'         => $amount,
            'balance_after'  => $balanceAfter,
            'reference_type' => $refType,
            'reference_id'   => $refId,
            'actor_id'       => $actorId,
            'notes'          => $notes,
            'created_at'     => date('Y-m-d H:i:s'),
        ];

        if ($this->insert($data)) {
            return $this->insertID;
        }

        log_message('error', 'FinancialLedgerModel::recordCredit failed: ' . json_encode($this->errors()));
        return false;
    }

    /**
     * Catat debit (uang keluar / hold) dari saldo kafe.
     * WAJIB dipanggil di dalam database transaction yang sudah aktif.
     *
     * @param int         $cafeId
     * @param int         $amount       Nominal debit (> 0)
     * @param string      $entryType    Lihat konstanta TYPE_*
     * @param int         $balanceAfter Saldo available_balance setelah operasi
     * @param string|null $refType
     * @param int|null    $refId
     * @param int|null    $actorId
     * @param string|null $notes
     * @param int|null    $correctionOf ID entri yang dikoreksi (jika ini entri reversal)
     * @return int|false  ID entri ledger yang baru
     */
    public function recordDebit(
        int $cafeId,
        int $amount,
        string $entryType,
        int $balanceAfter,
        ?string $refType = null,
        ?int $refId = null,
        ?int $actorId = null,
        ?string $notes = null,
        ?int $correctionOf = null
    ) {
        if ($amount <= 0) {
            log_message('error', "FinancialLedgerModel::recordDebit — amount harus > 0, got {$amount}");
            return false;
        }

        $data = [
            'cafe_id'        => $cafeId,
            'entry_type'     => $entryType,
            'debit'          => $amount,
            'credit'         => 0,
            'balance_after'  => $balanceAfter,
            'reference_type' => $refType,
            'reference_id'   => $refId,
            'actor_id'       => $actorId,
            'notes'          => $notes,
            'correction_of'  => $correctionOf,
            'created_at'     => date('Y-m-d H:i:s'),
        ];

        if ($this->insert($data)) {
            return $this->insertID;
        }

        log_message('error', 'FinancialLedgerModel::recordDebit failed: ' . json_encode($this->errors()));
        return false;
    }

    /**
     * Get ledger entries untuk satu cafe dengan pagination.
     *
     * @param int    $cafeId
     * @param int    $limit
     * @param int    $offset
     * @param string|null $entryType Filter by type (null = semua)
     * @return array
     */
    public function getCafeLedger(int $cafeId, int $limit = 50, int $offset = 0, ?string $entryType = null): array
    {
        $q = $this->where('cafe_id', $cafeId);
        if ($entryType) {
            $q = $q->where('entry_type', $entryType);
        }
        return $q->orderBy('created_at', 'DESC')
                 ->limit($limit, $offset)
                 ->findAll();
    }

    /**
     * Hitung total kredit (pendapatan) untuk cafe berdasarkan tipe entri.
     *
     * @param int    $cafeId
     * @param string $entryType
     * @return int
     */
    public function sumCredit(int $cafeId, string $entryType): int
    {
        $result = $this->selectSum('credit')
                       ->where('cafe_id', $cafeId)
                       ->where('entry_type', $entryType)
                       ->first();
        return (int) ($result['credit'] ?? 0);
    }

    /**
     * Hitung total debit untuk cafe berdasarkan tipe entri.
     *
     * @param int    $cafeId
     * @param string $entryType
     * @return int
     */
    public function sumDebit(int $cafeId, string $entryType): int
    {
        $result = $this->selectSum('debit')
                       ->where('cafe_id', $cafeId)
                       ->where('entry_type', $entryType)
                       ->first();
        return (int) ($result['debit'] ?? 0);
    }

    /**
     * Rekonsiliasi: bandingkan total ledger vs cafe_balances.
     * Berguna untuk debugging dan audit.
     *
     * @param int $cafeId
     * @return array
     */
    public function reconcile(int $cafeId): array
    {
        $totalIncome    = $this->sumCredit($cafeId, self::TYPE_PAYMENT_INCOME);
        $totalHeld      = $this->sumDebit($cafeId, self::TYPE_WITHDRAWAL_HOLD);
        $totalReleased  = $this->sumCredit($cafeId, self::TYPE_WITHDRAWAL_RELEASE);
        $totalCompleted = $this->sumDebit($cafeId, self::TYPE_WITHDRAWAL_COMPLETE);

        $ledgerAvailable = $totalIncome - $totalHeld + $totalReleased;
        $ledgerWithdrawn = $totalCompleted;

        $balanceModel   = new CafeBalanceModel();
        $balance        = $balanceModel->find($cafeId);

        return [
            'ledger_income'        => $totalIncome,
            'ledger_held'          => $totalHeld,
            'ledger_released'      => $totalReleased,
            'ledger_completed'     => $totalCompleted,
            'ledger_available'     => $ledgerAvailable,
            'ledger_withdrawn'     => $ledgerWithdrawn,
            'balance_available'    => $balance['available_balance'] ?? null,
            'balance_held'         => $balance['held_balance'] ?? null,
            'balance_withdrawn'    => $balance['total_withdrawn'] ?? null,
            'is_consistent'        => $balance !== null && (int)$balance['available_balance'] === $ledgerAvailable,
        ];
    }
}
