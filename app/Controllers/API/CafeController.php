<?php

namespace App\Controllers\API;

use App\Models\CafeModel;
use CodeIgniter\RESTful\ResourceController;

class CafeController extends ResourceController
{
    protected $cafeModel;
    protected $format = 'json';

    public function __construct()
    {
        $this->cafeModel = new CafeModel();
    }

    /**
     * Get list of active cafes
     * GET /api/v1/cafes
     */
    public function index()
    {
        $cafes = $this->cafeModel->getActiveCafes();

        return $this->respond([
            'success' => true,
            'data' => $cafes,
            'total' => count($cafes)
        ]);
    }

    /**
     * Get cafe detail
     * GET /api/v1/cafes/(:id)
     */
    public function show($id = null)
    {
        $cafe = $this->cafeModel->getCafeWithOwner($id);

        if (!$cafe) {
            return $this->failNotFound('Cafe not found');
        }

        return $this->respond([
            'success' => true,
            'data' => $cafe
        ]);
    }

    /**
     * Get cafes by location/search
     * GET /api/v1/cafes/search?q=query&lat=lat&lng=lng
     */
    public function search()
    {
        $query = $this->request->getVar('q');

        if (!$query || strlen($query) < 2) {
            return $this->failValidationError('Query must be at least 2 characters');
        }

        $cafes = $this->cafeModel->like('nama_kafe', $query)
            ->orLike('alamat', $query)
            ->where('status', 'approved')
            ->where('is_active', true)
            ->limit(20)
            ->findAll();

        return $this->respond([
            'success' => true,
            'data' => $cafes,
            'total' => count($cafes)
        ]);
    }

    /**
     * Get pending cafes for superadmin
     * GET /api/v1/superadmin/cafes/pending
     */
    public function getPendingCafes()
    {
        $token = $this->getBearerToken();
        if (!$token || !$this->verifyApiToken($token)) {
            return $this->failUnauthorized('Invalid API token');
        }

        $userId = $this->getUserIdFromToken($token);
        $userModel = new \App\Models\UserModel();
        $user = $userModel->find($userId);

        if (!$user || $user['role'] !== 'superadmin') {
            return $this->failForbidden('Only superadmin can access this endpoint');
        }

        $pendingCafes = $this->cafeModel->getPendingCafes();

        return $this->respond([
            'success' => true,
            'data' => $pendingCafes,
            'total' => count($pendingCafes)
        ]);
    }

    /**
     * Approve cafe
     * POST /api/v1/superadmin/cafes/approve
     */
    public function approveCafe()
    {
        $token = $this->getBearerToken();
        if (!$token || !$this->verifyApiToken($token)) {
            return $this->failUnauthorized('Invalid API token');
        }

        $userId = $this->getUserIdFromToken($token);
        $userModel = new \App\Models\UserModel();
        $user = $userModel->find($userId);

        if (!$user || $user['role'] !== 'superadmin') {
            return $this->failForbidden('Only superadmin can access this endpoint');
        }

        $cafeId = $this->request->getVar('cafe_id');
        if (!$cafeId) {
            return $this->failValidationError('cafe_id is required');
        }

        if ($this->cafeModel->approveCafe((int)$cafeId, $userId)) {
            return $this->respond([
                'success' => true,
                'message' => 'Cafe approved successfully'
            ]);
        }

        return $this->fail('Failed to approve cafe', 400);
    }

    /**
     * Reject cafe
     * POST /api/v1/superadmin/cafes/reject
     */
    public function rejectCafe()
    {
        $token = $this->getBearerToken();
        if (!$token || !$this->verifyApiToken($token)) {
            return $this->failUnauthorized('Invalid API token');
        }

        $userId = $this->getUserIdFromToken($token);
        $userModel = new \App\Models\UserModel();
        $user = $userModel->find($userId);

        if (!$user || $user['role'] !== 'superadmin') {
            return $this->failForbidden('Only superadmin can access this endpoint');
        }

        $cafeId = $this->request->getVar('cafe_id');
        $reason = $this->request->getVar('reason') ?: 'Ditolak oleh superadmin';

        if (!$cafeId) {
            return $this->failValidationError('cafe_id is required');
        }

        if ($this->cafeModel->rejectCafe((int)$cafeId, $userId, $reason)) {
            return $this->respond([
                'success' => true,
                'message' => 'Cafe rejected successfully'
            ]);
        }

        return $this->fail('Failed to reject cafe', 400);
    }

    /**
     * Token helpers
     */
    private function getBearerToken(): ?string
    {
        $header = $this->request->getHeaderLine('Authorization');
        if (preg_match('/Bearer\s+(.+)/', $header, $matches)) {
            return $matches[1];
        }
        return null;
    }

    private function verifyApiToken(string $token): bool
    {
        $hashedToken = hash('sha256', $token);
        $userId = cache()->get("api_token_val_{$hashedToken}");
        return !empty($userId);
    }

    private function getUserIdFromToken(string $token): ?int
    {
        $hashedToken = hash('sha256', $token);
        $userId = cache()->get("api_token_val_{$hashedToken}");
        return $userId ? (int)$userId : null;
    }
}

