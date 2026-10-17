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
        $this->cafeModel         = new CafeModel();
        $this->userModel         = new UserModel();
        $this->withdrawalModel   = new WithdrawalModel();
        $this->paymentModel      = new PaymentModel();
        $this->balanceModel      = new CafeBalanceModel();
        $this->withdrawalService = new WithdrawalService();
    }

    private function requireSuperadmin(): bool
    {
        return session()->get('user_role') === 'superadmin';
    }

    // =========================================================================
    // DASHBOARD
    // =========================================================================

    public function dashboard()
    {
        $totalCafes         = $this->cafeModel->countAllResults();
        $activeCafes        = $this->cafeModel->where('status', 'approved')->where('is_active', true)->countAllResults();
        $pendingCafes       = $this->cafeModel->where('status', 'pending')->countAllResults();
        $totalIncome        = $this->balanceModel->getSystemTotalIncome();
        $totalWithdrawn     = $this->balanceModel->getSystemTotalWithdrawn();
        $pendingWithdrawals = $this->withdrawalModel->countPendingWithdrawals();

        $data = [
            'total_cafes'         => $totalCafes,
            'active_cafes'        => $activeCafes,
            'pending_cafes'       => $pendingCafes,
            'total_income'        => $totalIncome,
            'total_withdrawn'     => $totalWithdrawn,
            'pending_withdrawals' => $pendingWithdrawals,
        ];

        return view('superadmin/dashboard', $data);
    }

    // =========================================================================
    // CAFE MANAGEMENT
    // =========================================================================

    public function getPendingCafes()
    {
        $cafes = $this->cafeModel->getPendingCafes();
        return $this->response->setJSON(['success' => true, 'data' => $cafes]);
    }

    public function approveCafe()
    {
        $cafeId = $this->request->getPost('cafe_id');
        if (!$cafeId) {
            return $this->response->setStatusCode(400)->setJSON(['success' => false, 'message' => 'Cafe ID required']);
        }

        if ($this->cafeModel->approveCafe($cafeId, session()->get('user_id'))) {
            return $this->response->setJSON(['success' => true, 'message' => 'Cafe approved']);
        }

        return $this->response->setStatusCode(400)->setJSON(['success' => false, 'message' => 'Approval failed']);
    }

    public function rejectCafe()
    {
        $rules = [
            'cafe_id' => 'required|integer',
            'reason'  => 'required|string|min_length[10]',
        ];

        if (!$this->validate($rules)) {
            return $this->response->setStatusCode(422)->setJSON(['success' => false, 'errors' => $this->validator->getErrors()]);
        }

        $cafeId = $this->request->getPost('cafe_id');
        $reason = $this->request->getPost('reason');

        if ($this->cafeModel->rejectCafe($cafeId, session()->get('user_id'), $reason)) {
            return $this->response->setJSON(['success' => true, 'message' => 'Cafe rejected']);
        }

        return $this->response->setStatusCode(400)->setJSON(['success' => false, 'message' => 'Rejection failed']);
    }

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

    public function approveAdmin()
    {
        $userId = (int)$this->request->getPost('user_id');
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

        try {
            $email = service('notificationEmail');
            $email->onAdminApproved($userId);
        } catch (\Throwable $e) {
            log_message('error', 'Email onAdminApproved error (non-critical): ' . $e->getMessage());
        }

        return $this->response->setJSON(['success' => true, 'message' => 'Admin kafe berhasil diaktifkan']);
    }

    public function rejectAdmin()
    {
        $userId = (int)$this->request->getPost('user_id');
        $reason = trim((string)($this->request->getPost('reason') ?? ''));
        if (!$userId) {
            return $this->response->setStatusCode(400)->setJSON(['success' => false, 'message' => 'user_id wajib diisi']);
        }

        $user = $this->userModel->find($userId);
        if (!$user || $user['role'] !== 'admin') {
            return $this->response->setStatusCode(404)->setJSON(['success' => false, 'message' => 'User tidak ditemukan']);
        }

        try {
            $email = service('notificationEmail');
            $email->onAdminRejected($userId, $reason);
        } catch (\Throwable $e) {
            log_message('error', 'Email onAdminRejected error (non-critical): ' . $e->getMessage());
        }

        $this->userModel->delete($userId);
        return $this->response->setJSON(['success' => true, 'message' => 'Pendaftaran admin ditolak dan dihapus']);
    }

    public function getAllCafes()
    {
        $page   = $this->request->getGet('page') ?? 1;
        $limit  = 50;
        $offset = ($page - 1) * $limit;

        $cafes = $this->cafeModel->select('cafes.*, users.name as admin_name')
            ->join('users', 'cafes.admin_id = users.id', 'left')
            ->limit($limit, $offset)
            ->findAll();

        return $this->response->setJSON(['success' => true, 'data' => $cafes, 'page' => $page]);
    }

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

        return $this->response->setJSON(['success' => true, 'data' => $admins]);
    }

    public function createCafe()
    {
        $rules = [
            'admin_id'          => 'required|integer',
            'nama_kafe'         => 'required|string|max_length[100]',
            'alamat'            => 'required|string',
            'deskripsi'         => 'permit_empty|string',
            'phone_number'      => 'permit_empty|string|max_length[20]',
            'payment_receiver'  => 'required|string|max_length[100]',
            'payment_method'    => 'required|in_list[bank_transfer,e_wallet]',
            'payment_qris'      => 'permit_empty|string',
            'bank_name'         => 'permit_empty|string|max_length[100]',
            'account_number'    => 'permit_empty|string|max_length[50]',
            'ewallet_number'    => 'permit_empty|string|max_length[20]',
            'payment_gate_token'=> 'permit_empty|string',
            'is_active_now'     => 'permit_empty|in_list[0,1,on]',
        ];

        if (!$this->validate($rules)) {
            return $this->response->setStatusCode(422)->setJSON(['success' => false, 'errors' => $this->validator->getErrors()]);
        }

        $adminId   = (int)$this->request->getPost('admin_id');
        $adminUser = $this->userModel->find($adminId);
        if (!$adminUser || $adminUser['role'] !== 'admin') {
            return $this->response->setStatusCode(404)->setJSON(['success' => false, 'message' => 'User Admin tidak ditemukan']);
        }

        $existingCafe = $this->cafeModel->where('admin_id', $adminId)->first();
        if ($existingCafe) {
            return $this->response->setStatusCode(400)->setJSON(['success' => false, 'message' => 'Admin ini sudah memiliki kafe terdaftar']);
        }

        $namaKafe = $this->request->getPost('nama_kafe');
        $slug     = url_title($namaKafe, '-', true);
        $originalSlug = $slug;
        $count = 1;
        while ($this->cafeModel->where('slug', $slug)->first()) {
            $slug = $originalSlug . '-' . $count;
            $count++;
        }

        $isActiveNow = $this->request->getPost('is_active_now');
        $approved    = ($isActiveNow === '1' || $isActiveNow === 'on');

        $db = \Config\Database::connect();
        $db->transStart();

        try {
            $cafeData = [
                'admin_id'           => $adminId,
                'nama_kafe'          => $namaKafe,
                'slug'               => $slug,
                'alamat'             => $this->request->getPost('alamat'),
                'deskripsi'          => $this->request->getPost('deskripsi'),
                'phone_number'       => $this->request->getPost('phone_number'),
                'payment_receiver'   => $this->request->getPost('payment_receiver'),
                'payment_method'     => $this->request->getPost('payment_method'),
                'payment_qris'       => $this->request->getPost('payment_qris'),
                'bank_name'          => $this->request->getPost('bank_name'),
                'account_number'     => $this->request->getPost('account_number'),
                'ewallet_number'     => $this->request->getPost('ewallet_number'),
                'payment_gate_token' => $this->request->getPost('payment_gate_token'),
                'status'             => $approved ? 'approved' : 'pending',
                'is_active'          => $approved,
                'created_at'         => date('Y-m-d H:i:s'),
                'updated_at'         => date('Y-m-d H:i:s'),
            ];

            if ($approved) {
                $cafeData['verified_by'] = session()->get('user_id');
                $cafeData['verified_at'] = date('Y-m-d H:i:s');
            }

            $cafeId = $this->cafeModel->insert($cafeData);

            if ($approved && $cafeId) {
                $this->balanceModel->insert([
                    'cafe_id'           => $cafeId,
                    'available_balance' => 0,
                    'held_balance'      => 0,
                    'total_income'      => 0,
                    'total_withdrawn'   => 0,
                    'updated_at'        => date('Y-m-d H:i:s'),
                ]);
            }

            $db->transComplete();

            if ($db->transStatus()) {
                if ($approved) {
                    $this->userModel->update($adminId, [
                        'is_active'  => true,
                        'is_verified'=> true,
                        'updated_at' => date('Y-m-d H:i:s'),
                    ]);
                }

                return $this->response->setJSON([
                    'success' => true,
                    'message' => $approved ? 'Kafe berhasil dibuat dan diaktifkan!' : 'Kafe berhasil dibuat dengan status pending.',
                ]);
            }
        } catch (\Exception $e) {
            $db->transRollback();
            log_message('error', 'Manual Cafe creation error: ' . $e->getMessage());
        }

        return $this->response->setStatusCode(400)->setJSON(['success' => false, 'message' => 'Gagal membuat kafe']);
    }

    public function toggleCafeActive()
    {
        $cafeId        = (int)$this->request->getPost('cafe_id');
        $currentActive = (int)$this->request->getPost('current_active');
        $cafe          = $this->cafeModel->find($cafeId);

        if (!$cafe) {
            return $this->response->setStatusCode(404)->setJSON(['success' => false, 'message' => 'Kafe tidak ditemukan']);
        }

        $newActive = $currentActive ? false : true;
        $updated   = $this->cafeModel->update($cafeId, ['is_active' => $newActive, 'updated_at' => date('Y-m-d H:i:s')]);

        if (!$updated) {
            return $this->response->setJSON(['success' => false, 'message' => 'Gagal update database', 'errors' => $this->cafeModel->errors()]);
        }

        try {
            $email = service('notificationEmail');
            $email->onCafeStatusChanged($cafeId, $newActive);
        } catch (\Throwable $e) {
            log_message('error', 'Email onCafeStatusChanged error (non-critical): ' . $e->getMessage());
        }

        $updatedCafe = $this->cafeModel->find($cafeId);
        return $this->response->setJSON([
            'success'   => true,
            'message'   => $newActive ? 'Kafe diaktifkan' : 'Kafe dinonaktifkan',
            'is_active' => $updatedCafe['is_active'],
        ]);
    }

    // =========================================================================
    // WITHDRAWAL MANAGEMENT — Dua Tahap
    // =========================================================================

    /**
     * GET /superadmin/withdrawals — daftar withdrawal dengan filter status.
     */
    public function getWithdrawals()
    {
        if (!$this->requireSuperadmin()) {
            return $this->response->setStatusCode(403)->setJSON(['success' => false, 'message' => 'Unauthorized']);
        }

        $status = $this->request->getGet('status') ?? '';
        $page   = max(1, (int)($this->request->getGet('page') ?? 1));
        $limit  = 50;
        $offset = ($page - 1) * $limit;

        // Validasi status
        $validStatuses = ['', 'pending', 'approved', 'processing', 'completed', 'rejected', 'failed', 'cancelled', 'paid'];
        if (!in_array($status, $validStatuses, true)) {
            $status = '';
        }

        $withdrawals = $this->withdrawalService->getAllWithdrawals($status ?: null, $limit, $offset);

        // Mask nomor rekening di listing
        $withdrawals = array_map(fn($w) => $this->maskAccountForListing($w), $withdrawals);

        return $this->response->setJSON([
            'success' => true,
            'data'    => $withdrawals,
            'page'    => $page,
            'limit'   => $limit,
            'status'  => $status,
        ]);
    }

    /**
     * GET /superadmin/withdrawals/{id} — detail withdrawal.
     * Nomor rekening tampil lengkap hanya untuk superadmin.
     */
    public function getWithdrawalDetail(int $withdrawalId)
    {
        if (!$this->requireSuperadmin()) {
            return $this->response->setStatusCode(403)->setJSON(['success' => false, 'message' => 'Unauthorized']);
        }

        $withdrawal = $this->withdrawalService->getWithdrawalDetail($withdrawalId);
        if (!$withdrawal) {
            return $this->response->setStatusCode(404)->setJSON(['success' => false, 'message' => 'Withdrawal tidak ditemukan']);
        }

        // Tambahkan info saldo cafe
        $balance = $this->balanceModel->getBalance($withdrawal['cafe_id']);
        $withdrawal['cafe_balance'] = $balance;

        return $this->response->setJSON(['success' => true, 'data' => $withdrawal]);
    }

    /**
     * GET /superadmin/withdrawals/pending — alias untuk backward compatibility.
     */
    public function getPendingWithdrawals()
    {
        $withdrawals = $this->withdrawalService->getPendingWithdrawals();
        $withdrawals = array_map(fn($w) => $this->maskAccountForListing($w), $withdrawals);

        return $this->response->setJSON(['success' => true, 'data' => $withdrawals]);
    }

    /**
     * POST /superadmin/withdrawal/approve — setujui withdrawal (Tahap A).
     * Hanya mengubah status, tidak menyelesaikan transfer.
     */
    public function approveWithdrawal()
    {
        if (!$this->requireSuperadmin()) {
            return $this->response->setStatusCode(403)->setJSON(['success' => false, 'message' => 'Unauthorized']);
        }

        $withdrawalId = (int)($this->request->getPost('withdrawal_id') ?? 0);
        if (!$withdrawalId) {
            return $this->response->setStatusCode(400)->setJSON(['success' => false, 'message' => 'Withdrawal ID required']);
        }

        $result = $this->withdrawalService->approveWithdrawal($withdrawalId, (int)session()->get('user_id'));

        if ($result['success']) {
            return $this->response->setJSON($result);
        }

        return $this->response->setStatusCode(400)->setJSON($result);
    }

    /**
     * POST /superadmin/withdrawal/reject — tolak withdrawal.
     */
    public function rejectWithdrawal()
    {
        if (!$this->requireSuperadmin()) {
            return $this->response->setStatusCode(403)->setJSON(['success' => false, 'message' => 'Unauthorized']);
        }

        $rules = [
            'withdrawal_id' => 'required|integer',
            'reason'        => 'required|string|min_length[10]',
        ];

        if (!$this->validate($rules)) {
            return $this->response->setStatusCode(422)->setJSON(['success' => false, 'errors' => $this->validator->getErrors()]);
        }

        $result = $this->withdrawalService->rejectWithdrawal(
            (int)$this->request->getPost('withdrawal_id'),
            (int)session()->get('user_id'),
            $this->request->getPost('reason')
        );

        if ($result['success']) {
            return $this->response->setJSON($result);
        }

        return $this->response->setStatusCode(400)->setJSON($result);
    }

    /**
     * POST /superadmin/withdrawal/process — tandai sedang diproses (Tahap B - mulai).
     */
    public function markWithdrawalProcessing()
    {
        if (!$this->requireSuperadmin()) {
            return $this->response->setStatusCode(403)->setJSON(['success' => false, 'message' => 'Unauthorized']);
        }

        $withdrawalId = (int)($this->request->getPost('withdrawal_id') ?? 0);
        if (!$withdrawalId) {
            return $this->response->setStatusCode(400)->setJSON(['success' => false, 'message' => 'Withdrawal ID required']);
        }

        $notes  = $this->request->getPost('notes') ?? null;
        $result = $this->withdrawalService->markAsProcessing(
            $withdrawalId,
            (int)session()->get('user_id'),
            $notes
        );

        if ($result['success']) {
            return $this->response->setJSON($result);
        }

        return $this->response->setStatusCode(400)->setJSON($result);
    }

    /**
     * POST /superadmin/withdrawal/complete — catat transfer selesai (Tahap B - selesai).
     */
    public function completeWithdrawal()
    {
        if (!$this->requireSuperadmin()) {
            return $this->response->setStatusCode(403)->setJSON(['success' => false, 'message' => 'Unauthorized']);
        }

        $rules = [
            'withdrawal_id'      => 'required|integer',
            'transfer_reference' => 'required|string|min_length[3]|max_length[100]',
            'notes'              => 'permit_empty|string|max_length[500]',
        ];

        if (!$this->validate($rules)) {
            return $this->response->setStatusCode(422)->setJSON(['success' => false, 'errors' => $this->validator->getErrors()]);
        }

        // Handle upload bukti transfer jika ada
        $transferProof = null;
        $proofFile     = $this->request->getFile('transfer_proof');
        if ($proofFile && $proofFile->isValid() && !$proofFile->hasMoved()) {
            $newName = $proofFile->getRandomName();
            $proofFile->move(WRITEPATH . 'uploads/transfer_proofs', $newName);
            $transferProof = 'uploads/transfer_proofs/' . $newName;
        }

        $result = $this->withdrawalService->completeWithdrawal(
            (int)$this->request->getPost('withdrawal_id'),
            (int)session()->get('user_id'),
            $this->request->getPost('transfer_reference'),
            $transferProof,
            $this->request->getPost('notes') ?? null
        );

        if ($result['success']) {
            return $this->response->setJSON($result);
        }

        return $this->response->setStatusCode(400)->setJSON($result);
    }

    /**
     * POST /superadmin/withdrawal/fail — catat transfer gagal.
     */
    public function failWithdrawal()
    {
        if (!$this->requireSuperadmin()) {
            return $this->response->setStatusCode(403)->setJSON(['success' => false, 'message' => 'Unauthorized']);
        }

        $rules = [
            'withdrawal_id'  => 'required|integer',
            'failure_reason' => 'required|string|min_length[5]',
        ];

        if (!$this->validate($rules)) {
            return $this->response->setStatusCode(422)->setJSON(['success' => false, 'errors' => $this->validator->getErrors()]);
        }

        $result = $this->withdrawalService->failWithdrawal(
            (int)$this->request->getPost('withdrawal_id'),
            (int)session()->get('user_id'),
            $this->request->getPost('failure_reason')
        );

        if ($result['success']) {
            return $this->response->setJSON($result);
        }

        return $this->response->setStatusCode(400)->setJSON($result);
    }

    /**
     * @deprecated Gunakan completeWithdrawal() sesuai alur baru.
     */
    public function markWithdrawalPaid()
    {
        log_message('warning', 'SuperadminController::markWithdrawalPaid() deprecated. Use completeWithdrawal().');
        return $this->completeWithdrawal();
    }

    // =========================================================================
    // TRANSACTIONS
    // =========================================================================

    public function getAllTransactions()
    {
        $page   = $this->request->getGet('page') ?? 1;
        $limit  = 50;
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

        return $this->response->setJSON(['success' => true, 'data' => $payments, 'page' => $page]);
    }

    // =========================================================================
    // HELPER
    // =========================================================================

    /**
     * Masking nomor rekening untuk tampilan listing.
     * Nomor rekening lengkap hanya tersedia di endpoint detail.
     */
    private function maskAccountForListing(array $withdrawal): array
    {
        if (!empty($withdrawal['account_number']) && strlen($withdrawal['account_number']) > 4) {
            $len = strlen($withdrawal['account_number']);
            $withdrawal['account_number'] = str_repeat('*', $len - 4) . substr($withdrawal['account_number'], -4);
        }
        return $withdrawal;
    }
}
