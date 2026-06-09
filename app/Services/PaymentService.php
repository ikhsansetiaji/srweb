<?php

namespace App\Services;

use App\Models\PaymentModel;
use App\Models\SongRequestModel;
use App\Models\CafeBalanceModel;
use Config\Database;

class PaymentService
{
    protected $paymentModel;
    protected $requestModel;
    protected $balanceModel;
    protected $db;

    public function __construct()
    {
        $this->paymentModel = new PaymentModel();
        $this->requestModel = new SongRequestModel();
        $this->balanceModel = new CafeBalanceModel();
        $this->db = Database::connect();
    }

    /**
     * Create payment untuk song request
     *
     * @param int $requestId
     * @param int $cafeId
     * @param array $paymentData
     * @return array
     */
    public function createPayment(int $requestId, int $cafeId, array $paymentData): array
    {
        // Verify request exists dan cafe match
        $request = $this->requestModel->find($requestId);
        if (!$request || $request['cafe_id'] !== $cafeId) {
            return ['success' => false, 'message' => 'Request invalid'];
        }

        $paymentData['request_id'] = $requestId;
        $paymentData['cafe_id'] = $cafeId;
        $paymentData['amount'] = $request['nominal'];

        $paymentId = $this->paymentModel->createPayment($paymentData);
        if ($paymentId) {
            return [
                'success' => true,
                'message' => 'Payment created',
                'payment_id' => $paymentId,
                'amount' => $request['nominal']
            ];
        }

        return ['success' => false, 'message' => 'Payment creation failed'];
    }

    /**
     * Handle webhook payment success DENGAN TRANSACTION
     * CRITICAL: IDEMPOTENT - if already processed, return success
     *
     * @param string|null $transactionId
     * @param string|null $externalReference
     * @param string $status
     * @return array
     */
    public function handlePaymentWebhook(?string $transactionId, ?string $externalReference, string $status): array
    {
        $this->db->transStart();

        try {
            // Find payment by reference
            $payment = $this->paymentModel->getPaymentByReference($transactionId, $externalReference);

            if ($payment) {
                // Already processed - idempotency
                if ($payment['payment_status'] === 'success' && $status === 'success') {
                    $this->db->transComplete();
                    return [
                        'success' => true,
                        'message' => 'Payment already processed (idempotent)',
                        'payment_id' => $payment['id']
                    ];
                }
            }

            // Find payment by referensi
            $query = $this->paymentModel->where('payment_status', 'pending');
            if ($transactionId) {
                $query->where('transaction_id', $transactionId);
            } else {
                $query->where('external_reference', $externalReference);
            }

            $payment = $query->first();
            if (!$payment) {
                $this->db->transRollback();
                return ['success' => false, 'message' => 'Payment not found'];
            }

            // Update payment status
            if (!$this->paymentModel->updatePaymentStatus($payment['id'], $status, $transactionId)) {
                $this->db->transRollback();
                return ['success' => false, 'message' => 'Failed to update payment status'];
            }

            // If success, update cafe balance
            if ($status === 'success') {
                if (!$this->balanceModel->addBalance($payment['cafe_id'], $payment['amount'])) {
                    $this->db->transRollback();
                    log_message('error', "Failed to add balance for payment: {$payment['id']}");
                    return ['success' => false, 'message' => 'Failed to update cafe balance'];
                }
            }

            $this->db->transComplete();

            if ($this->db->transStatus()) {
                log_message('info', "Payment webhook processed: Payment ID {$payment['id']}, Status: $status");
                return ['success' => true, 'message' => 'Payment processed', 'payment_id' => $payment['id']];
            }
        } catch (\Exception $e) {
            $this->db->transRollback();
            log_message('error', "Payment webhook error: " . $e->getMessage());
        }

        return ['success' => false, 'message' => 'Payment processing failed'];
    }

    /**
     * Verify payment menggunakan signature dari payment gateway
     *
     * @param array $payload
     * @param string $signature
     * @param string $secret
     * @return bool
     */
    public function verifySignature(array $payload, string $signature, string $secret): bool
    {
        // Example for Midtrans
        $signatureKey = hash('sha512', $payload['order_id'] . $payload['status_code'] . $payload['gross_amount'] . $secret);

        return hash_equals($signatureKey, $signature);
    }

    /**
     * Get payment dengan semua info
     *
     * @param int $paymentId
     * @return array|null
     */
    public function getPaymentDetails(int $paymentId): ?array
    {
        return $this->paymentModel->getPaymentWithDetails($paymentId);
    }

    /**
     * Calculate daily income untuk cafe
     *
     * @param int $cafeId
     * @return int
     */
    public function getDailyIncome(int $cafeId): int
    {
        $payments = $this->paymentModel->getTodayPayments($cafeId);
        $total = 0;

        foreach ($payments as $payment) {
            $total += $payment['amount'];
        }

        return $total;
    }
}

