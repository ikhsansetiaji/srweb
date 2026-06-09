<?php

namespace App\Controllers;

use App\Models\CafeModel;
use App\Models\UserModel;
use App\Models\WithdrawalModel;
use App\Models\PaymentModel;
use App\Models\CafeBalanceModel;
use App\Services\WithdrawalService;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Psr\Log\LoggerInterface;

class SuperadminController extends BaseController
{
    protected $cafeModel;
    protected $userModel;
    protected $withdrawalModel;
    protected $paymentModel;
    protected $balanceModel;
    protected $withdrawalService;

    public function initController(RequestInterface $request, ResponseInterface $response, LoggerInterface $logger)
    {
        parent::initController($request, $response, $logger);
        $this->cafeModel = new CafeModel();
        $this->userModel = new UserModel();
        $this->withdrawalModel = new WithdrawalModel();
        $this->paymentModel = new PaymentModel();
        $this->balanceModel = new CafeBalanceModel();
        $this->withdrawalService = new WithdrawalService();
    }

    /**
     * Superadmin dashboard
     */
    public function dashboard()
    {
        if (session()->get('user_role') !== 'superadmin') {
            return redirect()->to('/auth/login');
        }

        $totalCafes = $this->cafeModel->countAllResults();
        $activeCafes = $this->cafeModel->where('status', 'approved')->where('is_active', true)->countAllResults();
        $pendingCafes = $this->cafeModel->where('status', 'pending')->countAllResults();

        $totalIncome = $this->balanceModel->getSystemTotalIncome();
        $totalWithdrawn = $this->balanceModel->getSystemTotalWithdrawn();
        $pendingWithdrawals = $this->withdrawalModel->countPendingWithdrawals();

        $data = [
            'total_cafes' => $totalCafes,
            'active_cafes' => $activeCafes,
            'pending_cafes' => $pendingCafes,
            'total_income' => $totalIncome,
            'total_withdrawn' => $totalWithdrawn,
            'pending_withdrawals' => $pendingWithdrawals,
        ];

        return view('superadmin/dashboard', $data);
    }

    /**
     * Get pending cafes untuk verification
     */
    public function getPendingCafes()
    {
        if (session()->get('user_role') !== 'superadmin') {
            return redirect()->to('/auth/login');
        }

        $cafes = $this->cafeModel->getPendingCafes();

        return $this->response->setJSON([
            'success' => true,
            'data' => $cafes
        ]);
    }

    /**
     * Approve cafe
     */
    public function approveCafe()
    {
        if (session()->get('user_role') !== 'superadmin') {
            return redirect()->to('/auth/login');
        }

        $cafeId = $this->request->getPost('cafe_id');

        if (!$cafeId) {
            return $this->response->setStatusCode(400)->setJSON([
                'success' => false,
                'message' => 'Cafe ID required'
            ]);
        }

        if ($this->cafeModel->approveCafe($cafeId, session()->get('user_id'))) {
            return $this->response->setJSON([
                'success' => true,
                'message' => 'Cafe approved'
            ]);
        }

        return $this->response->setStatusCode(400)->setJSON([
            'success' => false,
            'message' => 'Approval failed'
        ]);
    }

    /**
     * Reject cafe
     */
    public function rejectCafe()
    {
        if (session()->get('user_role') !== 'superadmin') {
            return redirect()->to('/auth/login');
        }

        $rules = [
            'cafe_id' => 'required|integer',
            'reason' => 'required|string|min_length[10]',
        ];

        if (!$this->validate($rules)) {
            return $this->response->setStatusCode(422)->setJSON([
                'success' => false,
                'errors' => $this->validator->getErrors()
            ]);
        }

        $cafeId = $this->request->getPost('cafe_id');
        $reason = $this->request->getPost('reason');

        if ($this->cafeModel->rejectCafe($cafeId, session()->get('user_id'), $reason)) {
            return $this->response->setJSON([
                'success' => true,
                'message' => 'Cafe rejected'
            ]);
        }

        return $this->response->setStatusCode(400)->setJSON([
            'success' => false,
            'message' => 'Rejection failed'
        ]);
    }

    /**
     * Get pending withdrawals
     */
    public function getPendingWithdrawals()
    {
        if (session()->get('user_role') !== 'superadmin') {
            return redirect()->to('/auth/login');
        }

        $withdrawals = $this->withdrawalService->getPendingWithdrawals();

        return $this->response->setJSON([
            'success' => true,
            'data' => $withdrawals
        ]);
    }

    /**
     * Approve withdrawal
     */
    public function approveWithdrawal()
    {
        if (session()->get('user_role') !== 'superadmin') {
            return redirect()->to('/auth/login');
        }

        $withdrawalId = $this->request->getPost('withdrawal_id');

        if (!$withdrawalId) {
            return $this->response->setStatusCode(400)->setJSON([
                'success' => false,
                'message' => 'Withdrawal ID required'
            ]);
        }

        $result = $this->withdrawalService->approveWithdrawal($withdrawalId, session()->get('user_id'));

        if ($result['success']) {
            return $this->response->setJSON($result);
        }

        return $this->response->setStatusCode(400)->setJSON($result);
    }

    /**
     * Reject withdrawal
     */
    public function rejectWithdrawal()
    {
        if (session()->get('user_role') !== 'superadmin') {
            return redirect()->to('/auth/login');
        }

        $rules = [
            'withdrawal_id' => 'required|integer',
            'reason' => 'required|string|min_length[10]',
        ];

        if (!$this->validate($rules)) {
            return $this->response->setStatusCode(422)->setJSON([
                'success' => false,
                'errors' => $this->validator->getErrors()
            ]);
        }

        $result = $this->withdrawalService->rejectWithdrawal(
            $this->request->getPost('withdrawal_id'),
            session()->get('user_id'),
            $this->request->getPost('reason')
        );

        if ($result['success']) {
            return $this->response->setJSON($result);
        }

        return $this->response->setStatusCode(400)->setJSON($result);
    }

    /**
     * Mark withdrawal as paid
     */
    public function markWithdrawalPaid()
    {
        if (session()->get('user_role') !== 'superadmin') {
            return redirect()->to('/auth/login');
        }

        $withdrawalId = $this->request->getPost('withdrawal_id');

        if (!$withdrawalId) {
            return $this->response->setStatusCode(400)->setJSON([
                'success' => false,
                'message' => 'Withdrawal ID required'
            ]);
        }

        $result = $this->withdrawalService->markAsPaid($withdrawalId, session()->get('user_id'));

        if ($result['success']) {
            return $this->response->setJSON($result);
        }

        return $this->response->setStatusCode(400)->setJSON($result);
    }

    /**
     * Get all transactions
     */
    public function getAllTransactions()
    {
        if (session()->get('user_role') !== 'superadmin') {
            return redirect()->to('/auth/login');
        }

        $page = $this->request->getGet('page') ?? 1;
        $limit = 50;
        $offset = ($page - 1) * $limit;

        $db = \Config\Database::connect();

        $payments = $db->table('payments')
            ->select('payments.*, cafes.nama_kafe, song_requests.nominal')
            ->join('cafes', 'payments.cafe_id = cafes.id', 'left')
            ->join('song_requests', 'payments.request_id = song_requests.id', 'left')
            ->orderBy('payments.created_at', 'DESC')
            ->limit($limit, $offset)
            ->get()
            ->getResultArray();

        return $this->response->setJSON([
            'success' => true,
            'data' => $payments,
            'page' => $page
        ]);
    }

    /**
     * Get all cafes info
     */

    /**
     * GET pending admin kafe (belum diverifikasi)
     */
    public function getPendingAdmins()
    {
        $admins = $this->userModel
            ->where('role', 'admin')
            ->where('is_active', false)
            ->where('is_verified', false)
            ->orderBy('created_at', 'ASC')
            ->findAll();

        return $this->response->setJSON(['success' => true, 'data' => $admins]);
    }

    /**
     * POST approve admin kafe → aktifkan akun
     */
    public function approveAdmin()
    {
        $userId = (int) $this->request->getPost('user_id');
        if (!$userId) {
            return $this->response->setStatusCode(400)->setJSON(['success' => false, 'message' => 'user_id wajib diisi']);
        }

        $user = $this->userModel->find($userId);
        if (!$user || $user['role'] !== 'admin') {
            return $this->response->setStatusCode(404)->setJSON(['success' => false, 'message' => 'User tidak ditemukan']);
        }

        $this->userModel->update($userId, [
            'is_active'   => true,
            'is_verified' => true,
            'updated_at'  => date('Y-m-d H:i:s'),
        ]);

        return $this->response->setJSON(['success' => true, 'message' => 'Admin kafe berhasil diaktifkan']);
    }

    /**
     * POST reject / hapus admin kafe pending
     */
    public function rejectAdmin()
    {
        $userId = (int) $this->request->getPost('user_id');
        if (!$userId) {
            return $this->response->setStatusCode(400)->setJSON(['success' => false, 'message' => 'user_id wajib diisi']);
        }

        $user = $this->userModel->find($userId);
        if (!$user || $user['role'] !== 'admin') {
            return $this->response->setStatusCode(404)->setJSON(['success' => false, 'message' => 'User tidak ditemukan']);
        }

        $this->userModel->delete($userId);

        return $this->response->setJSON(['success' => true, 'message' => 'Pendaftaran admin ditolak dan dihapus']);
    }

    public function getAllCafes()
    {
        if (session()->get('user_role') !== 'superadmin') {
            return redirect()->to('/auth/login');
        }

        $page = $this->request->getGet('page') ?? 1;
        $limit = 50;
        $offset = ($page - 1) * $limit;

        $cafes = $this->cafeModel->select('cafes.*, users.name as admin_name')
            ->join('users', 'cafes.admin_id = users.id', 'left')
            ->limit($limit, $offset)
            ->findAll();

        return $this->response->setJSON([
            'success' => true,
            'data' => $cafes,
            'page' => $page
        ]);
    }
}