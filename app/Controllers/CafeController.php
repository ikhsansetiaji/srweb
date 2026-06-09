<?php

namespace App\Controllers;

use App\Services\AuthService;
use App\Models\CafeModel;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Psr\Log\LoggerInterface;

class CafeController extends BaseController
{
    protected $cafeModel;
    protected $authService;

    public function initController(RequestInterface $request, ResponseInterface $response, LoggerInterface $logger)
    {
        parent::initController($request, $response, $logger);
        $this->cafeModel = new CafeModel();
        $this->authService = new AuthService();
    }

    /**
     * Show register cafe form
     */
    public function registerPage()
    {
        if (!session()->has('user_id')) {
            return redirect()->to('/auth/login');
        }

        // Only admin can register cafe
        if (session()->get('user_role') !== 'admin') {
            return $this->response->setStatusCode(403)->setJSON([
                'success' => false,
                'message' => 'Unauthorized'
            ]);
        }

        return view('cafe/register');
    }

    /**
     * Handle cafe registration
     */
    public function register()
    {
        if (!session()->has('user_id')) {
            return redirect()->to('/auth/login');
        }

        $rules = [
            'nama_kafe' => 'required|string|max_length[100]',
            'alamat' => 'required|string',
            'phone_number' => 'required|string|max_length[20]',
            'payment_receiver' => 'required|string|max_length[100]',
            'payment_method' => 'required|in_list[QRIS,bank_transfer,e_wallet]',
            'payment_qris' => 'permit_empty|string',
        ];

        if (!$this->validate($rules)) {
            return $this->response->setStatusCode(422)->setJSON([
                'success' => false,
                'errors' => $this->validator->getErrors()
            ]);
        }

        $data = [
            'nama_kafe' => $this->request->getPost('nama_kafe') ?? $this->request->getJSON('nama_kafe'),
            'alamat' => $this->request->getPost('alamat') ?? $this->request->getJSON('alamat'),
            'deskripsi' => $this->request->getPost('deskripsi') ?? $this->request->getJSON('deskripsi'),
            'phone_number' => $this->request->getPost('phone_number') ?? $this->request->getJSON('phone_number'),
            'payment_receiver' => $this->request->getPost('payment_receiver') ?? $this->request->getJSON('payment_receiver'),
            'payment_method' => $this->request->getPost('payment_method') ?? $this->request->getJSON('payment_method'),
            'payment_qris' => $this->request->getPost('payment_qris') ?? $this->request->getJSON('payment_qris'),
        ];

        $result = $this->authService->registerCafe(session()->get('user_id'), $data);

        if ($result['success']) {
            return $this->response->setJSON([
                'success' => true,
                'message' => $result['message'],
                'redirect' => '/admin/dashboard'
            ]);
        }

        return $this->response->setStatusCode(400)->setJSON([
            'success' => false,
            'message' => $result['message']
        ]);
    }

    /**
     * Get list active cafes untuk guest
     */
    public function getActiveCafes()
    {
        $cafes = $this->cafeModel->getActiveCafes();

        return $this->response->setJSON([
            'success' => true,
            'data' => $cafes
        ]);
    }

    /**
     * Get cafe by ID dengan ownership check
     */
    public function getCafeDetail(int $cafeId)
    {
        $userId = session()->get('user_id');
        $userRole = session()->get('user_role');

        $cafe = $this->cafeModel->find($cafeId);

        if (!$cafe) {
            return $this->response->setStatusCode(404)->setJSON([
                'success' => false,
                'message' => 'Cafe tidak ditemukan'
            ]);
        }

        // Check ownership (admin or superadmin can view)
        if ($userRole === 'admin' && $cafe['admin_id'] !== $userId) {
            return $this->response->setStatusCode(403)->setJSON([
                'success' => false,
                'message' => 'Unauthorized'
            ]);
        }

        return $this->response->setJSON([
            'success' => true,
            'data' => $cafe
        ]);
    }

    /**
     * Update cafe info (admin only)
     */
    public function updateCafe(int $cafeId)
    {
        if (!session()->has('user_id')) {
            return redirect()->to('/auth/login');
        }

        $cafe = $this->cafeModel->getCafeByAdmin($cafeId, session()->get('user_id'));

        if (!$cafe) {
            return $this->response->setStatusCode(403)->setJSON([
                'success' => false,
                'message' => 'Unauthorized'
            ]);
        }

        $rules = [
            'nama_kafe' => 'required|string|max_length[100]',
            'alamat' => 'required|string',
            'phone_number' => 'permit_empty|string|max_length[20]',
        ];

        if (!$this->validate($rules)) {
            return $this->response->setStatusCode(422)->setJSON([
                'success' => false,
                'errors' => $this->validator->getErrors()
            ]);
        }

        $data = [
            'nama_kafe' => $this->request->getPost('nama_kafe'),
            'alamat' => $this->request->getPost('alamat'),
            'phone_number' => $this->request->getPost('phone_number'),
            'updated_at' => date('Y-m-d H:i:s'),
        ];

        if ($this->cafeModel->update($cafeId, $data)) {
            return $this->response->setJSON([
                'success' => true,
                'message' => 'Cafe updated successfully'
            ]);
        }

        return $this->response->setStatusCode(400)->setJSON([
            'success' => false,
            'message' => 'Update failed'
        ]);
    }
}

