<?php

namespace App\Models;

use CodeIgniter\Model;

class SuperadminOtpModel extends Model
{
    protected $table      = 'superadmin_otp_codes';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $allowedFields = [
        'user_id',
        'otp_code',
        'otp_hash',
        'purpose',
        'ip_address',
        'attempts',
        'is_used',
        'created_at',
        'expires_at',
    ];
    protected $useTimestamps = false;

    /**
     * Generate dan simpan OTP baru untuk user.
     * Return plain OTP (6 digit).
     */
    public function generateOtp(int $userId, string $ip = '', string $purpose = 'device_verify'): string
    {
        // Invalidasi OTP lama yang belum terpakai
        $this->where('user_id', $userId)
             ->where('purpose', $purpose)
             ->where('is_used', 0)
             ->set(['is_used' => 1])
             ->update();

        // Generate 6-digit OTP
        $otp     = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        $otpHash = hash('sha256', $otp . $userId . $purpose);

        $this->insert([
            'user_id'    => $userId,
            'otp_code'   => $otp,          // plain untuk kirim via email/WA
            'otp_hash'   => $otpHash,
            'purpose'    => $purpose,
            'ip_address' => $ip,
            'attempts'   => 0,
            'is_used'    => 0,
            'created_at' => date('Y-m-d H:i:s'),
            'expires_at' => date('Y-m-d H:i:s', strtotime('+10 minutes')),
        ]);

        return $otp;
    }

    /**
     * Verifikasi OTP yang dimasukkan user.
     * Return true jika valid, false jika tidak.
     */
    public function verifyOtp(int $userId, string $inputOtp, string $purpose = 'device_verify'): bool
    {
        $record = $this
            ->where('user_id', $userId)
            ->where('purpose', $purpose)
            ->where('is_used', 0)
            ->where('expires_at >', date('Y-m-d H:i:s'))
            ->orderBy('id', 'DESC')
            ->first();

        if (!$record) {
            return false;
        }

        // Max 5 kali percobaan
        if ((int) $record['attempts'] >= 5) {
            $this->update($record['id'], ['is_used' => 1]);
            return false;
        }

        // Increment attempts
        $this->update($record['id'], ['attempts' => (int) $record['attempts'] + 1]);

        $expectedHash = hash('sha256', $inputOtp . $userId . $purpose);
        if (!hash_equals($record['otp_hash'], $expectedHash)) {
            return false;
        }

        // Mark as used
        $this->update($record['id'], ['is_used' => 1]);

        return true;
    }

    /**
     * Bersihkan OTP yang sudah expired
     */
    public function purgeExpired(): int
    {
        return $this->where('expires_at <', date('Y-m-d H:i:s'))->delete();
    }

    /**
     * Cek apakah ada OTP aktif (belum expired) untuk user
     */
    public function hasActiveOtp(int $userId, string $purpose = 'device_verify'): bool
    {
        return (bool) $this
            ->where('user_id', $userId)
            ->where('purpose', $purpose)
            ->where('is_used', 0)
            ->where('expires_at >', date('Y-m-d H:i:s'))
            ->countAllResults();
    }
}
