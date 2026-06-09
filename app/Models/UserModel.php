<?php

namespace App\Models;

use CodeIgniter\Model;
use App\Helpers\SecurityHelper;

class UserModel extends Model
{
    protected $table = 'users';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    protected $useSoftDeletes = false;
    protected $allowedFields = ['name', 'username', 'email', 'password', 'role', 'is_verified', 'is_active', 'last_login', 'updated_at'];
    protected $useTimestamps = false;

    protected $validationRules = [
        'name' => 'required|string|max_length[100]',
        'email' => 'required|valid_email|is_unique[users.email,id,{id}]',
        'username' => 'permit_empty|string|max_length[50]|is_unique[users.username,id,{id}]',
        'password' => 'required|string|min_length[8]',
        'role' => 'required|in_list[user,admin,superadmin]',
    ];

    protected $validationMessages = [];
    protected $skipValidation = false;
    protected $cleanValidationRules = true;

    /**
     * Register new user dengan security
     *
     * @param array $data
     * @return array|false
     */
    public function register(array $data)
    {
        $data['password'] = SecurityHelper::hashPassword($data['password']);
        $data['role'] = $data['role'] ?? 'user';

        // Admin kafe: pending verifikasi superadmin. User biasa: langsung aktif.
        if ($data['role'] === 'admin') {
            $data['is_active']   = false;  // override default DB
            $data['is_verified'] = false;
        } else {
            $data['is_active']   = true;
            $data['is_verified'] = true;   // override default DB
        }

        $data['created_at'] = date('Y-m-d H:i:s');

        if ($this->insert($data)) {
            return ['success' => true, 'id' => $this->insertID];
        }

        return false;
    }

    /**
     * Verify password dengan security
     *
     * @param string $email
     * @param string $password
     * @return array|null
     */
    public function verifyPassword(string $email, string $password): ?array
    {
        $user = $this->where('email', $email)->first();

        if (!$user) {
            return null;
        }

        if (!SecurityHelper::verifyPassword($password, $user['password'])) {
            return null;
        }

        return $user;
    }

    /**
     * Get user by email safely
     *
     * @param string $email
     * @return array|null
     */
    public function getByEmail(string $email): ?array
    {
        return $this->where('email', $email)->first();
    }

    /**
     * Update last login timestamp
     *
     * @param int $userId
     * @return bool
     */
    public function updateLastLogin(int $userId): bool
    {
        return $this->update($userId, [
            'last_login' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);
    }

    /**
     * Check if user has specific role
     *
     * @param int $userId
     * @param string $role
     * @return bool
     */
    public function hasRole(int $userId, string $role): bool
    {
        $user = $this->find($userId);
        return $user && $user['role'] === $role;
    }

    /**
     * Deactivate user (soft delete alternative)
     *
     * @param int $userId
     * @return bool
     */
    public function deactivate(int $userId): bool
    {
        return $this->update($userId, [
            'is_active' => false,
            'updated_at' => date('Y-m-d H:i:s'),
        ]);
    }
}