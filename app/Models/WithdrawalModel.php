<?php

namespace App\Models;

use CodeIgniter\Model;

class WithdrawalModel extends Model
{
    protected $table = 'withdrawals';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    protected $useSoftDeletes = false;
    protected $allowedFields = [
        'cafe_id', 'amount', 'bank_name', 'account_number', 'account_holder',
        'status', 'approved_by', 'approved_at', 'rejection_reason', 'updated_at'
    ];
    protected $useTimestamps = false;

    protected $validationRules = [
        'cafe_id' => 'required|integer',
        'amount' => 'required|integer|greater_than[0]',
        'bank_name' => 'required|string|max_length[100]',
        'account_number' => 'required|string|max_length[50]',
        'account_holder' => 'required|string|max_length[100]',
    ];

    /**
     * Create withdrawal request dengan safety checks
     *
     * @param array $data
     * @return int|false
     */
    public function createWithdrawal(array $data)
    {
        $data['status'] = 'pending';
        $data['created_at'] = date('Y-m-d H:i:s');

        // Verify cafe dan balance
        $cafeModel = new CafeModel();
        $cafe = $cafeModel->find($data['cafe_id']);
        if (!$cafe) {
            log_message('error', 'Invalid cafe for withdrawal: ' . $data['cafe_id']);
            return false;
        }

        // Verify available balance
        $balanceModel = new CafeBalanceModel();
        $balance = $balanceModel->find($data['cafe_id']);
        if (!$balance || $balance['available_balance'] < $data['amount']) {
            log_message('warning', 'Insufficient balance for withdrawal');
            return false;
        }

        if ($this->insert($data)) {
            return $this->insertID;
        }

        return false;
    }

    /**
     * Approve withdrawal by superadmin DENGAN TRANSACTION
     *
     * @param int $withdrawalId
     * @param int $superadminId
     * @return bool
     */
    public function approveWithdrawal(int $withdrawalId, int $superadminId): bool
    {
        $db = \Config\Database::connect();
        $db->transStart();

        try {
            $withdrawal = $this->find($withdrawalId);
            if (!$withdrawal || $withdrawal['status'] !== 'pending') {
                $db->transRollback();
                return false;
            }

            // Update withdrawal status
            $this->update($withdrawalId, [
                'status' => 'approved',
                'approved_by' => $superadminId,
                'approved_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ]);

            $db->transComplete();

            if ($db->transStatus()) {
                return true;
            }
        } catch (\Exception $e) {
            $db->transRollback();
            log_message('error', 'Withdrawal approval error: ' . $e->getMessage());
        }

        return false;
    }

    /**
     * Reject withdrawal dengan reason
     *
     * @param int $withdrawalId
     * @param int $superadminId
     * @param string $reason
     * @return bool
     */
    public function rejectWithdrawal(int $withdrawalId, int $superadminId, string $reason): bool
    {
        return $this->update($withdrawalId, [
            'status' => 'rejected',
            'approved_by' => $superadminId,
            'approved_at' => date('Y-m-d H:i:s'),
            'rejection_reason' => $reason,
            'updated_at' => date('Y-m-d H:i:s'),
        ]);
    }

    /**
     * Mark withdrawal as paid DENGAN TRANSACTION
     * CRITICAL: Deduct balance when marked as paid
     *
     * @param int $withdrawalId
     * @param int $superadminId
     * @return bool
     */
    public function markAsPaid(int $withdrawalId, int $superadminId): bool
    {
        $db = \Config\Database::connect();
        $db->transStart();

        try {
            $withdrawal = $this->find($withdrawalId);
            if (!$withdrawal || $withdrawal['status'] !== 'approved') {
                $db->transRollback();
                return false;
            }

            // Deduct balance
            $balanceModel = new CafeBalanceModel();
            if (!$balanceModel->deductBalance($withdrawal['cafe_id'], $withdrawal['amount'])) {
                $db->transRollback();
                log_message('error', 'Failed to deduct balance for withdrawal: ' . $withdrawalId);
                return false;
            }

            // Update withdrawal status
            $this->update($withdrawalId, [
                'status' => 'paid',
                'updated_at' => date('Y-m-d H:i:s'),
            ]);

            $db->transComplete();

            if ($db->transStatus()) {
                return true;
            }
        } catch (\Exception $e) {
            $db->transRollback();
            log_message('error', 'Withdrawal paid update error: ' . $e->getMessage());
        }

        return false;
    }

    /**
     * Get pending withdrawals untuk superadmin
     *
     * @return array
     */
    public function getPendingWithdrawals(): array
    {
        return $this->select('withdrawals.*, cafes.nama_kafe, users.name as admin_name')
            ->join('cafes', 'withdrawals.cafe_id = cafes.id', 'left')
            ->join('users', 'cafes.admin_id = users.id', 'left')
            ->where('withdrawals.status', 'pending')
            ->orderBy('withdrawals.created_at', 'ASC')
            ->findAll();
    }

    /**
     * Get withdrawal history untuk cafe
     *
     * @param int $cafeId
     * @param int $limit
     * @return array
     */
    public function getCafeWithdrawals(int $cafeId, int $limit = 50): array
    {
        return $this->where('cafe_id', $cafeId)
            ->orderBy('created_at', 'DESC')
            ->limit($limit)
            ->findAll();
    }

    /**
     * Count pending withdrawals
     *
     * @return int
     */
    public function countPendingWithdrawals(): int
    {
        return $this->where('status', 'pending')->countAllResults();
    }

    /**
     * Calculate total approved but not paid
     *
     * @return int
     */
    public function getTotalApprovedAmount(): int
    {
        $result = $this->selectSum('amount')
            ->whereIn('status', ['approved', 'paid'])
            ->first();

        return $result['amount'] ?? 0;
    }
}

