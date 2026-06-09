<?php

namespace App\Services;

use App\Models\UserModel;
use App\Models\CafeModel;

class AuthService
{
    protected $userModel;
    protected $cafeModel;

    public function __construct()
    {
        $this->userModel = new UserModel();
        $this->cafeModel = new CafeModel();
    }

    /**
     * Register user dengan validation
     *
     * @param array $data
     * @return array
     */
    public function register(array $data): array
    {
        // Validate email format
        if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            return ['success' => false, 'message' => 'Email tidak valid'];
        }

        // Validate password strength
        if (!$this->isStrongPassword($data['password'])) {
            return [
                'success' => false,
                'message' => 'Password minimal 8 karakter, harus mengandung huruf besar, huruf kecil, dan angka'
            ];
        }

        // Check existing email
        if ($this->userModel->getByEmail($data['email'])) {
            return ['success' => false, 'message' => 'Email sudah terdaftar'];
        }

        // Register
        $result = $this->userModel->register($data);
        if ($result) {
            return ['success' => true, 'message' => 'Registrasi berhasil', 'user_id' => $result['id']];
        }

        return ['success' => false, 'message' => 'Registrasi gagal'];
    }

    /**
     * Login user dengan security
     *
     * @param string $email
     * @param string $password
     * @return array
     */
    public function login(string $email, string $password): array
    {
        $user = $this->userModel->verifyPassword($email, $password);

        if (!$user) {
            log_message('warning', "Login attempt failed for: $email");
            return ['success' => false, 'message' => 'Email atau password salah'];
        }

        // Admin kafe yang belum diverifikasi superadmin
        if ($user['role'] === 'admin' && !$user['is_active']) {
            return [
                'success' => false,
                'message' => 'Akun Anda sedang menunggu verifikasi oleh superadmin. Harap tunggu konfirmasi.'
            ];
        }

        // User nonaktif (diblokir)
        if (!$user['is_active']) {
            return ['success' => false, 'message' => 'Akun Anda telah dinonaktifkan.'];
        }

        // Update last login
        $this->userModel->updateLastLogin($user['id']);

        return [
            'success' => true,
            'message' => 'Login berhasil',
            'user' => [
                'id' => $user['id'],
                'name' => $user['name'],
                'email' => $user['email'],
                'role' => $user['role'],
            ]
        ];
    }

    /**
     * Check password strength
     * Minimum 8 characters, uppercase, lowercase, dan number
     *
     * @param string $password
     * @return bool
     */
    private function isStrongPassword(string $password): bool
    {
        if (strlen($password) < 8) {
            return false;
        }

        return preg_match('/[A-Z]/', $password) &&
               preg_match('/[a-z]/', $password) &&
               preg_match('/[0-9]/', $password);
    }

    /**
     * Register cafe dengan verification pending
     *
     * @param int $adminId
     * @param array $data
     * @return array
     */
    public function registerCafe(int $adminId, array $data): array
    {
        $data['admin_id'] = $adminId;
        $data['status'] = 'pending';
        $data['is_active'] = false;
        $data['created_at'] = date('Y-m-d H:i:s');

        // Generate slug
        $data['slug'] = $this->generateSlug($data['nama_kafe']);

        if ($this->cafeModel->insert($data)) {
            return ['success' => true, 'message' => 'Registrasi cafe berhasil, menunggu verifikasi', 'cafe_id' => $this->cafeModel->insertID];
        }

        return ['success' => false, 'message' => 'Registrasi cafe gagal'];
    }

    /**
     * Generate URL slug dari nama
     *
     * @param string $name
     * @return string
     */
    private function generateSlug(string $name): string
    {
        $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $name), '-'));
        $slug = substr($slug, 0, 120);

        return $slug;
    }

    /**
     * Verify user role
     *
     * @param int $userId
     * @param array $allowedRoles
     * @return bool
     */
    public function hasRole(int $userId, array $allowedRoles): bool
    {
        $user = $this->userModel->find($userId);
        if (!$user) {
            return false;
        }

        return in_array($user['role'], $allowedRoles);
    }
}