<?php

namespace App\Services;

use App\Models\PaymentModel;
use App\Models\SongRequestModel;
use App\Models\CafeBalanceModel;
use App\Models\FinancialLedgerModel;
use Config\Database;

class PaymentService
{
    protected $paymentModel;
    protected $requestModel;
    protected $balanceModel;
    protected $ledgerModel;
    protected $db;

    public function __construct()
    {
        $this->paymentModel = new PaymentModel();
        $this->requestModel = new SongRequestModel();
        $this->balanceModel = new CafeBalanceModel();
        $this->ledgerModel  = new FinancialLedgerModel();
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
        if (!$request || (int)$request['cafe_id'] !== $cafeId) {
            return ['success' => false, 'message' => 'Request invalid'];
        }

        $paymentData['request_id'] = $requestId;
        $paymentData['cafe_id'] = $cafeId;
        $paymentData['amount'] = (int) $request['nominal'];

        $paymentId = $this->paymentModel->createPayment($paymentData);
        log_message('info', 'createPayment data: ' . json_encode($paymentData) . ' result: ' . var_export($paymentId, true));
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
            // Cari payment by external_reference atau transaction_id (status apapun)
            $payment = null;
            if ($externalReference) {
                $payment = $this->paymentModel->where('external_reference', $externalReference)->first();
            }
            if (!$payment && $transactionId) {
                $payment = $this->paymentModel->where('transaction_id', $transactionId)->first();
            }

            if (!$payment) {
                $this->db->transRollback();
                return ['success' => false, 'message' => 'Payment not found'];
            }

            // Idempotency — sudah diproses sebelumnya
            if ($payment['payment_status'] === 'success' && $status === 'success') {
                $this->db->transComplete();
                return [
                    'success'    => true,
                    'message'    => 'Payment already processed (idempotent)',
                    'payment_id' => $payment['id']
                ];
            }

            // Update payment status
            if (!$this->paymentModel->updatePaymentStatus($payment['id'], $status, $transactionId)) {
                $this->db->transRollback();
                return ['success' => false, 'message' => 'Failed to update payment status'];
            }

            // If success, update cafe balance (ATOMIC) + ledger + aktifkan song request ke antrean
            if ($status === 'success') {
                $cafeId = (int)$payment['cafe_id'];
                $amount = (int)$payment['amount'];

                // Atomic balance update — mencegah race condition pada webhook paralel
                $newBalance = $this->balanceModel->addBalanceAtomic($cafeId, $amount);
                if ($newBalance === false) {
                    $this->db->transRollback();
                    log_message('error', "Failed to add balance atomically for payment: {$payment['id']}");
                    return ['success' => false, 'message' => 'Failed to update cafe balance'];
                }

                // Catat ke financial ledger
                $ledgerEntry = $this->ledgerModel->recordCredit(
                    $cafeId,
                    $amount,
                    FinancialLedgerModel::TYPE_PAYMENT_INCOME,
                    $newBalance,
                    'payment',
                    (int)$payment['id'],
                    null,
                    'Pembayaran request lagu #' . $payment['request_id'] . ' berhasil'
                );

                if (!$ledgerEntry) {
                    $this->db->transRollback();
                    log_message('error', "Failed to record ledger for payment: {$payment['id']}");
                    return ['success' => false, 'message' => 'Failed to record financial ledger'];
                }

                // Pindahkan song_request dari pending_payment → waiting (masuk antrean)
                $this->requestModel
                    ->where('id', $payment['request_id'])
                    ->where('status', 'pending_payment')
                    ->set(['status' => 'waiting'])
                    ->update();
            }

            // Jika payment gagal/expired, batalkan song_request
            if (in_array($status, ['failed', 'expired'])) {
                $this->requestModel
                    ->where('id', $payment['request_id'])
                    ->where('status', 'pending_payment')
                    ->set(['status' => 'cancelled'])
                    ->update();
            }

            $this->db->transComplete();

            if ($this->db->transStatus()) {
                log_message('info', "Payment webhook processed: Payment ID {$payment['id']}, Status: {$status}");

                // ── Notifikasi WhatsApp & Email (SETELAH transaksi DB commit) ─────
                // Kegagalan notifikasi TIDAK boleh membalikkan transaksi yang sudah selesai.
                $paymentId = (int)$payment['id'];
                $requestId = (int)$payment['request_id'];

                // 1. WhatsApp
                try {
                    /** @var \App\Services\WhatsAppNotificationService $wa */
                    $wa = service('whatsappNotification');
                    if ($status === 'success') {
                        $wa->onPaymentSuccess($paymentId);
                        if ($requestId) {
                            $wa->onRequestQueued($requestId);
                        }
                    } elseif (in_array($status, ['failed', 'expired'], true)) {
                        $wa->onPaymentFailed($paymentId, $status);
                    }
                } catch (\Throwable $waEx) {
                    log_message('error', 'WhatsApp notification error (non-critical): ' . $waEx->getMessage());
                }

                // 2. Email Notifikasi (Brevo)
                try {
                    /** @var \App\Services\NotificationEmailService $emailService */
                    $emailService = service('notificationEmail');
                    if ($status === 'success') {
                        $emailService->onPaymentSuccess($paymentId);
                        if ($requestId) {
                            $emailService->onSongRequestCreated($requestId);
                        }
                    } elseif (in_array($status, ['failed', 'expired'], true)) {
                        $emailService->onPaymentFailed($paymentId, $status);
                    }
                } catch (\Throwable $emailEx) {
                    log_message('error', 'Email payment notification error (non-critical): ' . $emailEx->getMessage());
                }
                // ── End notifikasi ────────────────────────────────────────────────

                return ['success' => true, 'message' => 'Payment processed', 'payment_id' => $paymentId];
            }
        } catch (\Exception $e) {
            $this->db->transRollback();
            log_message('error', 'Payment webhook error: ' . $e->getMessage());
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

    /**
     * Get payment berdasarkan request_id (jika sudah ada)
     */

    /**
     * Generate Midtrans Snap Token
     * Docs: https://docs.midtrans.com/reference/snap-api
     */
    public function generateSnapToken(int $paymentId, int $amount, int $requestId): ?string
    {
        $serverKey  = env('MIDTRANS_SERVER_KEY', getenv('MIDTRANS_SERVER_KEY'));
        $isSandbox  = !env('MIDTRANS_IS_PRODUCTION', false);
        $baseUrl    = $isSandbox
            ? 'https://app.sandbox.midtrans.com/snap/v1/transactions'
            : 'https://app.midtrans.com/snap/v1/transactions';

        if (!$serverKey) {
            log_message('error', 'MIDTRANS_SERVER_KEY tidak diset di .env');
            return null;
        }

        // order_id harus unik per transaksi
        $orderId = 'SR-' . $requestId . '-' . $paymentId . '-' . time();

        // Simpan order_id ke payment record untuk dicocokkan saat webhook
        $this->paymentModel->update($paymentId, [
            'external_reference' => $orderId,
            'payment_status'     => 'pending',
        ]);

        $payload = [
            'transaction_details' => [
                'order_id'     => $orderId,
                'gross_amount' => $amount,
            ],
            'callbacks' => [
                'finish' => env('app.baseURL') . 'payment/finish',
            ],
        ];

        $ch = curl_init($baseUrl);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => json_encode($payload),
            CURLOPT_HTTPHEADER     => [
                'Content-Type: application/json',
                'Accept: application/json',
                'Authorization: Basic ' . base64_encode($serverKey . ':'),
            ],
            // Fix SSL untuk Windows/XAMPP
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false,
            CURLOPT_TIMEOUT        => 30,
            CURLOPT_CONNECTTIMEOUT => 10,
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);

        if ($curlError) {
            log_message('error', "Midtrans curl error: {$curlError}");
            return null;
        }

        if ($httpCode !== 201) {
            log_message('error', "Midtrans Snap HTTP {$httpCode}: {$response}");
            return null;
        }
        log_message('info', "Midtrans Snap OK token acquired");

        $data = json_decode($response, true);
        return $data['token'] ?? null;
    }

    public function getPaymentByOrderId(string $orderId): ?array
    {
        return $this->paymentModel->where('external_reference', $orderId)->first();
    }

    public function getPaymentByRequestId(int $requestId): ?array
    {
        return $this->paymentModel->where('request_id', $requestId)->first();
    }

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