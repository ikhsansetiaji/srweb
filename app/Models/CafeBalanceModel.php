<?php

namespace App\Models;

use CodeIgniter\Model;

class CafeBalanceModel extends Model
{
    protected $table = 'cafe_balances';
    protected $primaryKey = 'cafe_id';
    protected $useAutoIncrement = false;
    protected $returnType = 'array';
    protected $useSoftDeletes = false;
    protected $allowedFields = ['cafe_id', 'available_balance', 'total_income', 'total_withdrawn', 'updated_at'];
    protected $useTimestamps = false;

    /**
     * Get balance safely dengan cafe ke existence check
     *
     * @param int $cafeId
     * @return array|null
     */
    public function getBalance(int $cafeId): ?array
    {
        return $this->find($cafeId);
    }

    /**
     * Add balance ketika payment success DENGAN TRANSACTION
     * CRITICAL: Must be called within transaction
     *
     * @param int $cafeId
     * @param int $amount
     * @return bool
     */
    public function addBalance(int $cafeId, int $amount): bool
    {
        if ($amount <= 0) {
            log_message('warning', "Invalid amount for cafe $cafeId: $amount");
            return false;
        }

        $balance = $this->find($cafeId);
        if (!$balance) {
            return false;
        }

        return $this->update($cafeId, [
            'available_balance' => $balance['available_balance'] + $amount,
            'total_income' => $balance['total_income'] + $amount,
            'updated_at' => date('Y-m-d H:i:s'),
        ]);
    }

    /**
     * Deduct balance ketika withdrawal paid DENGAN TRANSACTION
     * CRITICAL: Must be called within transaction
     *
     * @param int $cafeId
     * @param int $amount
     * @return bool
     */
    public function deductBalance(int $cafeId, int $amount): bool
    {
        if ($amount <= 0) {
            log_message('warning', "Invalid withdrawal amount for cafe $cafeId: $amount");
            return false;
        }

        $balance = $this->find($cafeId);
        if (!$balance) {
            return false;
        }

        // Check sufficient balance
        if ($balance['available_balance'] < $amount) {
            log_message('warning', "Insufficient balance for cafe $cafeId. Required: $amount, Available: " . $balance['available_balance']);
            return false;
        }

        return $this->update($cafeId, [
            'available_balance' => $balance['available_balance'] - $amount,
            'total_withdrawn' => $balance['total_withdrawn'] + $amount,
            'updated_at' => date('Y-m-d H:i:s'),
        ]);
    }

    /**
     * Get top earning cafes
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
     * Calculate system total income
     *
     * @return int
     */
    public function getSystemTotalIncome(): int
    {
        $result = $this->selectSum('total_income')->first();
        return $result['total_income'] ?? 0;
    }

    /**
     * Calculate system total withdrawn
     *
     * @return int
     */
    public function getSystemTotalWithdrawn(): int
    {
        $result = $this->selectSum('total_withdrawn')->first();
        return $result['total_withdrawn'] ?? 0;
    }
}

