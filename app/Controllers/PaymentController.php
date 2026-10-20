<?php

namespace App\Controllers;

use App\Services\PaymentService;
use App\Models\PaymentModel;
use App\Models\SongRequestModel;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Psr\Log\LoggerInterface;

class PaymentController extends BaseController
{
    protected $paymentService;
    protected $requestModel;
    protected $paymentModel;

    public function initController(RequestInterface $request, ResponseInterface $response, LoggerInterface $logger)
    {
        parent::initController($request, $response, $logger);
        $this->paymentService = new PaymentService();
        $this->requestModel  = new SongRequestModel();
        $this->paymentModel = new PaymentModel();
    }

    /**
     * Show payment page untuk request
     */
    public function paymentPage()
    {
        $requestId = (int) $this->request->getGet('request_id');

        if (!$requestId) {
            return $this->response->setStatusCode(400)->setJSON([
                'success' => false,
                'message' => 'Request ID required'
            ]);
        }

        // Query ke song_requests (bukan payments — payment belum ada saat halaman ini dibuka)
        $requestDetail = $this->requestModel->getRequestWithDetails($requestId);

        if (!$requestDetail) {
            return $this->response->setStatusCode(404)->setJSON([
                'success' => false,
                'message' => 'Request tidak ditemukan'
            ]);
        }

        // Cek apakah sudah ada payment untuk request ini
        $existingPayment = $this->paymentService->getPaymentByRequestId($requestId);

        return view('payment/checkout', [
            'request'         => $requestDetail,
            'existing_payment' => $existingPayment,
        ]);
    }

    /**
     * Create payment untuk request
     */
    public function createPayment()
    {
        $json      = $this->request->getJSON(true);
        $requestId = $this->request->getPost('request_id') ?? ($json['request_id'] ?? null);
        $cafeId    = $this->request->getPost('cafe_id')    ?? ($json['cafe_id']    ?? null);

        if (!$requestId || !$cafeId || !is_numeric($requestId) || !is_numeric($cafeId)) {
            return $this->response->setStatusCode(400)->setJSON([
                'success' => false,
                'message' => 'request_id dan cafe_id wajib diisi dan harus berupa angka',
            ]);
        }

        // payment_method dihandle Midtrans Snap — user pilih di popup
        $paymentData = ['payment_method' => 'midtrans_snap'];

        $result = $this->paymentService->createPayment(
            (int)$requestId,
            (int)$cafeId,
            $paymentData
        );

        if ($result['success']) {
            // Generate Midtrans Snap Token
            $snapToken = $this->paymentService->generateSnapToken(
                $result['payment_id'],
                $result['amount'],
                (int)$requestId
            );

            return $this->response->setJSON([
                'success'    => true,
                'message'    => 'Payment created',
                'payment_id' => $result['payment_id'],
                'snap_token' => $snapToken,
                'amount' => $result['amount'],
                'payment_method' => 'midtrans_snap',
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

    /**
     * Demo payment success — hanya untuk development/testing tanpa Midtrans
     * HAPUS atau disable di production!
     */

    public function finish()
    {
        $orderId = $this->request->getGet('order_id') ?? '';
        $status  = $this->request->getGet('transaction_status') ?? $this->request->getGet('status') ?? '';
        $payment = $this->paymentService->getPaymentByOrderId($orderId);
        $cafeId  = $payment['cafe_id'] ?? 0;
        $isSuccess = in_array($status, ['settlement', 'capture', 'success']);
        $isPending = in_array($status, ['pending', 'authorize']);
        return view('payment/finish', [
            'status'  => $isSuccess ? 'success' : ($isPending ? 'pending' : 'failed'),
            'order_id'=> $orderId,
            'payment' => $payment,
            'cafe_id' => $cafeId,
        ]);
    }

    public function pending()
    {
        $orderId = $this->request->getGet('order_id') ?? '';
        $payment = $this->paymentService->getPaymentByOrderId($orderId);
        return view('payment/finish', [
            'status'  => 'pending',
            'order_id'=> $orderId,
            'payment' => $payment,
            'cafe_id' => $payment['cafe_id'] ?? 0,
        ]);
    }

    public function error()
    {
        $orderId = $this->request->getGet('order_id') ?? '';
        $payment = $this->paymentService->getPaymentByOrderId($orderId);
        return view('payment/finish', [
            'status'  => 'failed',
            'order_id'=> $orderId,
            'payment' => $payment,
            'cafe_id' => $payment['cafe_id'] ?? 0,
        ]);
    }

    public function demoSuccess()
    {
        if (env('MIDTRANS_CLIENT_KEY')) {
            return $this->response->setStatusCode(403)->setJSON([
                'success' => false,
                'message' => 'Demo mode tidak tersedia jika Midtrans sudah dikonfigurasi'
            ]);
        }

        $requestId = (int) ($this->request->getJSON(true)['request_id'] ?? $this->request->getPost('request_id'));
        $cafeId    = (int) ($this->request->getJSON(true)['cafe_id']    ?? $this->request->getPost('cafe_id'));

        if (!$requestId) {
            return $this->response->setStatusCode(400)->setJSON(['success' => false, 'message' => 'request_id required']);
        }

        $songRequest = $this->requestModel->find($requestId);
        if (!$songRequest) {
            return $this->response->setStatusCode(404)->setJSON(['success' => false, 'message' => 'Request tidak ditemukan']);
        }

        // Buat payment record dengan status success langsung
        $demoOrderId = 'DEMO-' . $requestId . '-' . time();
        $this->paymentService->handlePaymentWebhook(
            $demoOrderId,
            $demoOrderId,
            'success'
        );

        // Pastikan song_request status = waiting (masuk antrean)
        $this->requestModel->update($requestId, ['status' => 'waiting']);

        // Update cafe balance juga
        $this->paymentService->addDemoBalance($cafeId ?: $songRequest['cafe_id'], $songRequest['nominal']);

        return $this->response->setJSON([
            'success'  => true,
            'message'  => 'Demo payment berhasil',
            'redirect' => '/song-request/request?cafe_id=' . ($cafeId ?: $songRequest['cafe_id']) . '&success=1'
        ]);
    }

    public function webhook()
    {
        // Midtrans kirim JSON atau form-encoded
        $payload = $this->request->getJSON(true);
        if (!$payload) {
            $payload = $this->request->getPost();
        }

        // Payload kosong → tetap 200 agar Midtrans tidak retry
        if (empty($payload)) {
            log_message('warning', 'Midtrans webhook: empty payload');
            return $this->response->setJSON(['success' => true, 'message' => 'OK']);
        }

        log_message('info', 'Midtrans webhook: ' . json_encode($payload));

        $orderId      = $payload['order_id']      ?? '';
        $statusCode   = $payload['status_code']   ?? '';
        $grossAmount  = $payload['gross_amount']  ?? '';
        $signatureKey = $payload['signature_key'] ?? '';

        // ── Verifikasi Signature ───────────────────────────────────────────
        $serverKey = env('MIDTRANS_SERVER_KEY', getenv('MIDTRANS_SERVER_KEY'));
        if ($serverKey && $signatureKey) {
            $expected = hash('sha512', $orderId . $statusCode . $grossAmount . $serverKey);
            if (!hash_equals($expected, $signatureKey)) {
                log_message('error', "Midtrans webhook: invalid signature order={$orderId}");
                // Tetap 200 supaya Midtrans tidak anggap URL invalid, tapi log error-nya
                return $this->response->setJSON(['success' => false, 'message' => 'Invalid signature']);
            }
        }

        // ── Deteksi payload test dari Midtrans dashboard ───────────────────
        // Midtrans "Tes URL notifikasi" kirim order_id = "test-*" atau tanpa transaction_id
        $isTestPayload = empty($payload['transaction_id']) ||
                         str_starts_with((string)$orderId, 'test-') ||
                         ($statusCode === '200' && empty($payload['payment_type']));

        if ($isTestPayload) {
            log_message('info', 'Midtrans webhook: test payload received, returning 200');
            return $this->response->setJSON(['success' => true, 'message' => 'Test OK']);
        }

        // ── Map status ────────────────────────────────────────────────────
        $transactionStatus = $payload['transaction_status'] ?? '';
        $fraudStatus       = $payload['fraud_status'] ?? 'accept';

        $status = match(true) {
            $transactionStatus === 'capture'    && $fraudStatus === 'accept' => 'success',
            $transactionStatus === 'settlement'                               => 'success',
            in_array($transactionStatus, ['cancel', 'deny', 'expire', 'failure']) => 'failed',
            default => 'pending',
        };

        $result = $this->paymentService->handlePaymentWebhook(
            $payload['transaction_id'] ?? null,
            $orderId ?: null,
            $status
        );

        if ($result['success']) {
            log_message('info', "Webhook OK: payment={$result['payment_id']} status={$status}");
            return $this->response->setJSON(['success' => true, 'message' => $result['message']]);
        }

        // Payment not found = mungkin duplikat / sudah diproses → tetap 200
        log_message('warning', 'Webhook: ' . $result['message']);
        return $this->response->setJSON(['success' => true, 'message' => $result['message']]);
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