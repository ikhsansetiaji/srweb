<?php

namespace App\Controllers\API;

use App\Models\UserModel;
use CodeIgniter\RESTful\ResourceController;

class SuperadminController extends ResourceController
{
    protected $userModel;
    protected $format = 'json';

    public function __construct()
    {
        $this->userModel = new UserModel();
    }

    /**
     * GET /api/v1/superadmin/admins/pending
     */
    public function getPendingAdmins()
    {
        $token = $this->getBearerToken();
        if (!$token || !$this->verifyApiToken($token)) {
            return $this->failUnauthorized('Invalid API token');
        }

        $userId = $this->getUserIdFromToken($token);
        $user = $this->userModel->find($userId);

        if (!$user || $user['role'] !== 'superadmin') {
            return $this->failForbidden('Only superadmin can access this endpoint');
        }

        $admins = $this->userModel
            ->where('role', 'admin')
            ->where('is_active', false)
            ->where('is_verified', false)
            ->orderBy('created_at', 'ASC')
            ->findAll();

        return $this->respond([
            'success' => true,
            'data' => $admins,
            'total' => count($admins)
        ]);
    }

    /**
     * POST /api/v1/superadmin/admins/approve
     */
    public function approveAdmin()
    {
        $token = $this->getBearerToken();
        if (!$token || !$this->verifyApiToken($token)) {
            return $this->failUnauthorized('Invalid API token');
        }

        $userId = $this->getUserIdFromToken($token);
        $user = $this->userModel->find($userId);

        if (!$user || $user['role'] !== 'superadmin') {
            return $this->failForbidden('Only superadmin can access this endpoint');
        }

        $targetUserId = $this->request->getVar('user_id');
        if (!$targetUserId) {
            return $this->failValidationError('user_id is required');
        }

        $targetUser = $this->userModel->find($targetUserId);
        if (!$targetUser || $targetUser['role'] !== 'admin') {
            return $this->failNotFound('Admin user not found');
        }

        $this->userModel->update($targetUserId, [
            'is_active'   => true,
            'is_verified' => true,
            'updated_at'  => date('Y-m-d H:i:s'),
        ]);

        return $this->respond([
            'success' => true,
            'message' => 'Admin cafe approved successfully'
        ]);
    }

    /**
     * POST /api/v1/superadmin/admins/reject
     */
    public function rejectAdmin()
    {
        $token = $this->getBearerToken();
        if (!$token || !$this->verifyApiToken($token)) {
            return $this->failUnauthorized('Invalid API token');
        }

        $userId = $this->getUserIdFromToken($token);
        $user = $this->userModel->find($userId);

        if (!$user || $user['role'] !== 'superadmin') {
            return $this->failForbidden('Only superadmin can access this endpoint');
        }

        $targetUserId = $this->request->getVar('user_id');
        if (!$targetUserId) {
            return $this->failValidationError('user_id is required');
        }

        $targetUser = $this->userModel->find($targetUserId);
        if (!$targetUser || $targetUser['role'] !== 'admin') {
            return $this->failNotFound('Admin user not found');
        }

        $this->userModel->delete($targetUserId);

        return $this->respond([
            'success' => true,
            'message' => 'Admin registration rejected and deleted'
        ]);
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
