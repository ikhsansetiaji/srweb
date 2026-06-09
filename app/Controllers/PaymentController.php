<?php

namespace App\Controllers;

use App\Services\PaymentService;
use App\Models\PaymentModel;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Psr\Log\LoggerInterface;

class PaymentController extends BaseController
{
    protected $paymentService;
    protected $paymentModel;

    public function initController(RequestInterface $request, ResponseInterface $response, LoggerInterface $logger)
    {
        parent::initController($request, $response, $logger);
        $this->paymentService = new PaymentService();
        $this->paymentModel = new PaymentModel();
    }

    /**
     * Show payment page untuk request
     */
    public function paymentPage()
    {
        $requestId = $this->request->getGet('request_id');

        if (!$requestId) {
            return $this->response->setStatusCode(400)->setJSON([
                'success' => false,
                'message' => 'Request ID required'
            ]);
        }

        $requestDetail = $this->paymentService->getPaymentDetails($requestId);

        if (!$requestDetail) {
            return $this->response->setStatusCode(404)->setJSON([
                'success' => false,
                'message' => 'Request tidak ditemukan'
            ]);
        }

        return view('payment/checkout', ['request' => $requestDetail]);
    }

    /**
     * Create payment untuk request
     */
    public function createPayment()
    {
        // Handle form-encoded dan JSON requests
        $requestId = $this->request->getPost('request_id') ?? $this->request->getJSON('request_id');
        $cafeId = $this->request->getPost('cafe_id') ?? $this->request->getJSON('cafe_id');
        $paymentMethod = $this->request->getPost('payment_method') ?? $this->request->getJSON('payment_method');

        // Validasi data
        if (!$requestId || !$cafeId || !$paymentMethod) {
            return $this->response->setStatusCode(400)->setJSON([
                'success' => false,
                'message' => 'request_id, cafe_id, dan payment_method wajib diisi',
                'required_fields' => ['request_id', 'cafe_id', 'payment_method']
            ]);
        }

        $rules = [
            'request_id' => 'required|integer',
            'cafe_id' => 'required|integer',
            'payment_method' => 'required|in_list[QRIS,gopay,ovo,transfer_bank]',
        ];

        // Manual validation
        if (!is_numeric($requestId) || !is_numeric($cafeId)) {
            return $this->response->setStatusCode(422)->setJSON([
                'success' => false,
                'message' => 'request_id dan cafe_id harus berupa angka'
            ]);
        }

        if (!in_array($paymentMethod, ['QRIS', 'gopay', 'ovo', 'transfer_bank'])) {
            return $this->response->setStatusCode(422)->setJSON([
                'success' => false,
                'message' => 'payment_method tidak valid: QRIS, gopay, ovo, atau transfer_bank',
                'valid_methods' => ['QRIS', 'gopay', 'ovo', 'transfer_bank']
            ]);
        }

        $paymentData = [
            'payment_method' => $paymentMethod,
        ];

        $result = $this->paymentService->createPayment(
            (int)$requestId,
            (int)$cafeId,
            $paymentData
        );

        if ($result['success']) {
            // TODO: Generate payment link dari gateway (Midtrans/Xendit)
            return $this->response->setJSON([
                'success' => true,
                'message' => 'Payment created',
                'payment_id' => $result['payment_id'],
                'amount' => $result['amount'],
                'payment_method' => $paymentMethod,
                // 'payment_url' => generate dari gateway
            ]);
        }

        return $this->response->setStatusCode(400)->setJSON([
            'success' => false,
            'message' => $result['message']
        ]);
    }

    /**
     * Webhook handler dari payment gateway
     * CRITICAL: MUST be idempotent
     */
    public function webhook()
    {
        $payload = $this->request->getJSON(true);

        if (!$payload) {
            return $this->response->setStatusCode(400)->setJSON([
                'success' => false,
                'message' => 'Invalid payload'
            ]);
        }

        // Verify signature from gateway
        $signature = $this->request->getHeaderLine('X-Signature') ??
                     $this->request->getHeaderLine('Authorization') ?? '';

        // TODO: Implement signature verification berdasarkan payment gateway
        // Contoh: $this->paymentService->verifySignature($payload, $signature, $secret)

        // Determine payment status
        $status = $payload['transaction_status'] ?? $payload['status'] ?? 'pending';
        $transactionId = $payload['transaction_id'] ?? null;
        $externalReference = $payload['external_reference'] ?? null;

        // Handle webhook
        $result = $this->paymentService->handlePaymentWebhook(
            $transactionId,
            $externalReference,
            $status
        );

        if ($result['success']) {
            log_message('info', "Webhook processed: Payment ID {$result['payment_id']}");
            return $this->response->setJSON([
                'success' => true,
                'message' => $result['message']
            ]);
        }

        log_message('error', "Webhook failed: " . $result['message']);
        return $this->response->setStatusCode(400)->setJSON([
            'success' => false,
            'message' => $result['message']
        ]);
    }

    /**
     * Get payment status
     */
    public function getPaymentStatus(int $paymentId)
    {
        $payment = $this->paymentModel->find($paymentId);

        if (!$payment) {
            return $this->response->setStatusCode(404)->setJSON([
                'success' => false,
                'message' => 'Payment tidak ditemukan'
            ]);
        }

        return $this->response->setJSON([
            'success' => true,
            'data' => [
                'id' => $payment['id'],
                'status' => $payment['payment_status'],
                'amount' => $payment['amount'],
                'created_at' => $payment['created_at'],
                'paid_at' => $payment['paid_at'],
            ]
        ]);
    }

    /**
     * Get payment history untuk cafe (admin only)
     */
    public function getCafePaymentHistory(int $cafeId)
    {
        // Verify ownership
        if (session()->get('user_role') === 'admin') {
            $userModel = new \App\Models\UserModel();
            $cafeModel = new \App\Models\CafeModel();

            $cafe = $cafeModel->find($cafeId);
            if (!$cafe || $cafe['admin_id'] !== session()->get('user_id')) {
                return $this->response->setStatusCode(403)->setJSON([
                    'success' => false,
                    'message' => 'Unauthorized'
                ]);
            }
        }

        $page = $this->request->getGet('page') ?? 1;
        $limit = 50;
        $offset = ($page - 1) * $limit;

        $payments = $this->paymentModel->getCafePayments($cafeId, $limit, $offset);

        return $this->response->setJSON([
            'success' => true,
            'data' => $payments,
            'page' => $page,
            'limit' => $limit
        ]);
    }

    /**
     * Calculate daily income untuk cafe
     */
    public function getDailyIncome(int $cafeId)
    {
        // Verify ownership
        if (session()->get('user_role') === 'admin') {
            $cafeModel = new \App\Models\CafeModel();
            $cafe = $cafeModel->find($cafeId);
            if (!$cafe || $cafe['admin_id'] !== session()->get('user_id')) {
                return $this->response->setStatusCode(403)->setJSON([
                    'success' => false,
                    'message' => 'Unauthorized'
                ]);
            }
        }

        $income = $this->paymentService->getDailyIncome($cafeId);

        return $this->response->setJSON([
            'success' => true,
            'income' => $income,
            'date' => date('Y-m-d')
        ]);
    }
}

