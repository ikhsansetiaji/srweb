<?php

namespace App\Services;

use App\Models\WithdrawalModel;
use App\Models\CafeBalanceModel;
use App\Models\CafeModel;

class WithdrawalService
{
    protected $withdrawalModel;
    protected $balanceModel;
    protected $cafeModel;

    public function __construct()
    {
        $this->withdrawalModel = new WithdrawalModel();
        $this->balanceModel = new CafeBalanceModel();
        $this->cafeModel = new CafeModel();
    }

    /**
     * Request withdrawal dengan validation
     *
     * @param int $cafeId
     * @param array $data
     * @return array
     */
    public function requestWithdrawal(int $cafeId, array $data): array
    {
        // Verify cafe exist dan belongs to admin
        $cafe = $this->cafeModel->find($cafeId);
        if (!$cafe) {
            return ['success' => false, 'message' => 'Cafe tidak ditemukan'];
        }

        // Check cafe is approved
        if ($cafe['status'] !== 'approved' || !$cafe['is_active']) {
            return ['success' => false, 'message' => 'Cafe belum disetujui atau tidak aktif'];
        }

        // Check balance
        $balance = $this->balanceModel->getBalance($cafeId);
        if (!$balance || $balance['available_balance'] < $data['amount']) {
            return ['success' => false, 'message' => 'Saldo tidak cukup'];
        }

        // Validate withdrawal amount
        if ($data['amount'] < 10000) {
            return ['success' => false, 'message' => 'Minimal withdrawal Rp 10.000'];
        }

        $data['cafe_id'] = $cafeId;

        $withdrawalId = $this->withdrawalModel->createWithdrawal($data);
        if ($withdrawalId) {
            return [
                'success' => true,
                'message' => 'Withdrawal request submitted',
                'withdrawal_id' => $withdrawalId
            ];
        }

        return ['success' => false, 'message' => 'Withdrawal request failed'];
    }

    /**
     * Approve withdrawal by superadmin
     *
     * @param int $withdrawalId
     * @param int $superadminId
     * @return array
     */
    public function approveWithdrawal(int $withdrawalId, int $superadminId): array
    {
        $withdrawal = $this->withdrawalModel->find($withdrawalId);
        if (!$withdrawal) {
            return ['success' => false, 'message' => 'Withdrawal tidak ditemukan'];
        }

        if ($withdrawal['status'] !== 'pending') {
            return ['success' => false, 'message' => 'Hanya withdrawal pending yang bisa diapprove'];
        }

        if ($this->withdrawalModel->approveWithdrawal($withdrawalId, $superadminId)) {
            return ['success' => true, 'message' => 'Withdrawal approved'];
        }

        return ['success' => false, 'message' => 'Approval failed'];
    }

    /**
     * Reject withdrawal dengan reason
     *
     * @param int $withdrawalId
     * @param int $superadminId
     * @param string $reason
     * @return array
     */
    public function rejectWithdrawal(int $withdrawalId, int $superadminId, string $reason): array
    {
        $withdrawal = $this->withdrawalModel->find($withdrawalId);
        if (!$withdrawal) {
            return ['success' => false, 'message' => 'Withdrawal tidak ditemukan'];
        }

        if ($withdrawal['status'] !== 'pending') {
            return ['success' => false, 'message' => 'Hanya withdrawal pending yang bisa ditolak'];
        }

        if ($this->withdrawalModel->rejectWithdrawal($withdrawalId, $superadminId, $reason)) {
            return ['success' => true, 'message' => 'Withdrawal rejected'];
        }

        return ['success' => false, 'message' => 'Rejection failed'];
    }

    /**
     * Mark withdrawal as paid DENGAN TRANSACTION
     *
     * @param int $withdrawalId
     * @param int $superadminId
     * @return array
     */
    public function markAsPaid(int $withdrawalId, int $superadminId): array
    {
        $withdrawal = $this->withdrawalModel->find($withdrawalId);
        if (!$withdrawal) {
            return ['success' => false, 'message' => 'Withdrawal tidak ditemukan'];
        }

        if ($withdrawal['status'] !== 'approved') {
            return ['success' => false, 'message' => 'Hanya withdrawal yang di-approve bisa ditandai paid'];
        }

        if ($this->withdrawalModel->markAsPaid($withdrawalId, $superadminId)) {
            return ['success' => true, 'message' => 'Withdrawal marked as paid'];
        }

        return ['success' => false, 'message' => 'Update failed'];
    }

    /**
     * Get pending withdrawals untuk superadmin
     *
     * @return array
     */
    public function getPendingWithdrawals(): array
    {
        return $this->withdrawalModel->getPendingWithdrawals();
    }

    /**
     * Get withdrawal history untuk cafe
     *
     * @param int $cafeId
     * @return array
     */
    public function getCafeWithdrawalHistory(int $cafeId): array
    {
        return $this->withdrawalModel->getCafeWithdrawals($cafeId);
    }

    /**
     * Get withdrawal detail
     *
     * @param int $withdrawalId
     * @return array|null
     */
    public function getWithdrawalDetail(int $withdrawalId): ?array
    {
        return $this->withdrawalModel->find($withdrawalId);
    }
}

