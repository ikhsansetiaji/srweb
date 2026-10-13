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
     * Show register cafe form (Disabled for self-registration)
     */
    public function registerPage()
    {
        if (session()->get('user_role') === 'admin') {
            return redirect()->to('/admin/dashboard');
        }
        return redirect()->to('/');
    }

    /**
     * Handle cafe registration (Disabled for self-registration)
     */
    public function register()
    {
        return $this->response->setStatusCode(403)->setJSON([
            'success' => false,
            'message' => 'Registrasi mandiri dinonaktifkan. Hubungi Superadmin via email kemitraan.'
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

        // Quick-toggle: if only aggregate_enabled is provided (AJAX toggle), skip full validation
        $postedAggregate = $this->request->getPost('aggregate_enabled');
        $postedNama = $this->request->getPost('nama_kafe');
        $postedAlamat = $this->request->getPost('alamat');

        if ($postedAggregate !== null && $postedNama === null && $postedAlamat === null) {
            $data = [
                // Cast to boolean explicitly so PostgreSQL accepts the value
                'aggregate_enabled' => (bool) filter_var($postedAggregate, FILTER_VALIDATE_BOOLEAN),
                'updated_at' => date('Y-m-d H:i:s'),
            ];

            if ($this->cafeModel->update($cafeId, $data)) {
                return $this->response->setJSON([
                    'success' => true,
                    'message' => 'Pengaturan aggregate berhasil diperbarui'
                ]);
            }

            return $this->response->setStatusCode(400)->setJSON([
                'success' => false,
                'message' => 'Gagal menyimpan pengaturan aggregate'
            ]);
        }

        $rules = [
            'nama_kafe' => 'required|string|max_length[100]',
            'alamat' => 'required|string',
            'phone_number' => 'permit_empty|string|max_length[20]',
            'aggregate_enabled' => 'permit_empty',
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
            'aggregate_enabled' => (bool) filter_var($this->request->getPost('aggregate_enabled'), FILTER_VALIDATE_BOOLEAN),
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

