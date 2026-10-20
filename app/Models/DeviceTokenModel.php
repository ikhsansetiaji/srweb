<?php

namespace App\Models;

use CodeIgniter\Model;

class DeviceTokenModel extends Model
{
    protected $table      = 'superadmin_device_tokens';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $allowedFields = [
        'user_id',
        'device_token',
        'token_hash',
        'user_agent',
        'ip_address',
        'device_name',
        'created_at',
        'last_used_at',
        'expires_at',
        'is_active',
    ];
    protected $useTimestamps = false;

    /**
     * Buat device token baru dan simpan ke DB.
     * Return raw token (untuk disimpan di cookie).
     */
    public function createToken(int $userId, string $userAgent, string $ip): string
    {
        // Generate 64-byte random token
        $rawToken  = bin2hex(random_bytes(64));
        $tokenHash = hash('sha256', $rawToken);

        $this->insert([
            'user_id'      => $userId,
            'device_token' => substr($rawToken, 0, 128),
            'token_hash'   => $tokenHash,
            'user_agent'   => substr($userAgent, 0, 500),
            'ip_address'   => $ip,
            'device_name'  => $this->guessDeviceName($userAgent),
            'created_at'   => date('Y-m-d H:i:s'),
            'last_used_at' => date('Y-m-d H:i:s'),
            'expires_at'   => date('Y-m-d H:i:s', strtotime('+30 days')),
            'is_active'    => 1,
        ]);

        return $rawToken;
    }

    /**
     * Validasi raw token dari cookie.
     * Return user_id jika valid, null jika tidak.
     */
    public function validateToken(string $rawToken, int $userId): ?array
    {
        $hash   = hash('sha256', $rawToken);
        $record = $this
            ->where('token_hash', $hash)
            ->where('user_id', $userId)
            ->where('is_active', 1)
            ->where('expires_at >', date('Y-m-d H:i:s'))
            ->first();

        if (!$record) {
            return null;
        }

        // Update last_used_at
        $this->update($record['id'], ['last_used_at' => date('Y-m-d H:i:s')]);

        return $record;
    }

    /**
     * Hapus semua token yang sudah expired
     */
    public function purgeExpired(): int
    {
        return $this->where('expires_at <', date('Y-m-d H:i:s'))->delete();
    }

    /**
     * Revoke semua device token milik user
     */
    public function revokeAll(int $userId): bool
    {
        return $this->where('user_id', $userId)->set(['is_active' => 0])->update();
    }

    /**
     * Coba tebak nama device dari User-Agent
     */
    private function guessDeviceName(string $ua): string
    {
        if (stripos($ua, 'Mobile') !== false || stripos($ua, 'Android') !== false) {
            return 'Mobile';
        }
        if (stripos($ua, 'iPad') !== false || stripos($ua, 'Tablet') !== false) {
            return 'Tablet';
        }
        return 'Desktop';
    }
}
