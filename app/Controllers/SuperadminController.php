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

    /**
     * GET active/pending admins who don't have a cafe
     */
    public function getAvailableAdmins()
    {
        $db = \Config\Database::connect();
        
        $admins = $db->table('users')
            ->select('users.id, users.name, users.email')
            ->join('cafes', 'users.id = cafes.admin_id', 'left')
            ->where('users.role', 'admin')
            ->where('cafes.id', null)
            ->orderBy('users.name', 'ASC')
            ->get()
            ->getResultArray();

        return $this->response->setJSON([
            'success' => true,
            'data' => $admins
        ]);
    }

    /**
     * POST create new cafe manually (superadmin only)
     */
    public function createCafe()
    {
        $rules = [
            'admin_id' => 'required|integer',
            'nama_kafe' => 'required|string|max_length[100]',
            'alamat' => 'required|string',
            'deskripsi' => 'permit_empty|string',
            'phone_number' => 'permit_empty|string|max_length[20]',
            'payment_receiver' => 'required|string|max_length[100]',
            'payment_method' => 'required|in_list[QRIS,bank_transfer,e_wallet]',
            'payment_qris'       => 'permit_empty|string',
            'bank_name'          => 'permit_empty|string|max_length[100]',
            'account_number'     => 'permit_empty|string|max_length[50]',
            'ewallet_number'     => 'permit_empty|string|max_length[20]',
            'payment_gate_token' => 'permit_empty|string',
            'is_active_now'      => 'permit_empty|in_list[0,1,on]',
        ];

        if (!$this->validate($rules)) {
            return $this->response->setStatusCode(422)->setJSON([
                'success' => false,
                'errors' => $this->validator->getErrors()
            ]);
        }

        $adminId = (int) $this->request->getPost('admin_id');
        
        $adminUser = $this->userModel->find($adminId);
        if (!$adminUser || $adminUser['role'] !== 'admin') {
            return $this->response->setStatusCode(404)->setJSON([
                'success' => false,
                'message' => 'User Admin tidak ditemukan'
            ]);
        }

        $existingCafe = $this->cafeModel->where('admin_id', $adminId)->first();
        if ($existingCafe) {
            return $this->response->setStatusCode(400)->setJSON([
                'success' => false,
                'message' => 'Admin ini sudah memiliki kafe terdaftar'
            ]);
        }

        $namaKafe = $this->request->getPost('nama_kafe');
        
        $slug = url_title($namaKafe, '-', true);
        $originalSlug = $slug;
        $count = 1;
        while ($this->cafeModel->where('slug', $slug)->first()) {
            $slug = $originalSlug . '-' . $count;
            $count++;
        }

        $isActiveNow = $this->request->getPost('is_active_now');
        $approved = ($isActiveNow === '1' || $isActiveNow === 'on');

        $db = \Config\Database::connect();
        $db->transStart();

        try {
            $cafeData = [
                'admin_id' => $adminId,
                'nama_kafe' => $namaKafe,
                'slug' => $slug,
                'alamat' => $this->request->getPost('alamat'),
                'deskripsi' => $this->request->getPost('deskripsi'),
                'phone_number' => $this->request->getPost('phone_number'),
                'payment_receiver' => $this->request->getPost('payment_receiver'),
                'payment_method' => $this->request->getPost('payment_method'),
                'payment_qris'       => $this->request->getPost('payment_qris'),
                'bank_name'          => $this->request->getPost('bank_name'),
                'account_number'     => $this->request->getPost('account_number'),
                'ewallet_number'     => $this->request->getPost('ewallet_number'),
                'payment_gate_token' => $this->request->getPost('payment_gate_token'),
                'status' => $approved ? 'approved' : 'pending',
                'is_active' => $approved,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ];

            if ($approved) {
                $cafeData['verified_by'] = session()->get('user_id');
                $cafeData['verified_at'] = date('Y-m-d H:i:s');
            }

            $cafeId = $this->cafeModel->insert($cafeData);

            if ($approved && $cafeId) {
                $this->balanceModel->insert([
                    'cafe_id' => $cafeId,
                    'available_balance' => 0,
                    'total_income' => 0,
                    'total_withdrawn' => 0,
                    'updated_at' => date('Y-m-d H:i:s'),
                ]);
            }

            $db->transComplete();

            if ($db->transStatus()) {
                if ($approved) {
                    $this->userModel->update($adminId, [
                        'is_active' => true,
                        'is_verified' => true,
                        'updated_at' => date('Y-m-d H:i:s'),
                    ]);
                }

                return $this->response->setJSON([
                    'success' => true,
                    'message' => $approved ? 'Kafe berhasil dibuat dan diaktifkan!' : 'Kafe berhasil dibuat dengan status pending.'
                ]);
            }
        } catch (\Exception $e) {
            $db->transRollback();
            log_message('error', 'Manual Cafe creation error: ' . $e->getMessage());
        }

        return $this->response->setStatusCode(400)->setJSON([
            'success' => false,
            'message' => 'Gagal membuat kafe'
        ]);
    }
}