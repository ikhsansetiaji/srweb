<?php

namespace App\Helpers;

/**
 * Security Helper - Password hashing, encryption, dan security utilities
 */
class SecurityHelper
{
    /**
     * Hash password dengan BCRYPT
     * Cost: 12 untuk security yang kuat
     *
     * @param string $password
     * @param int $cost
     * @return string
     */
    public static function hashPassword(string $password, int $cost = 12): string
    {
        return password_hash($password, PASSWORD_BCRYPT, ['cost' => $cost]);
    }

    /**
     * Verify password dengan hash
     *
     * @param string $password
     * @param string $hash
     * @return bool
     */
    public static function verifyPassword(string $password, string $hash): bool
    {
        return password_verify($password, $hash);
    }

    /**
     * Check password strength
     * Requirements:
     * - Minimum 8 characters
     * - At least 1 uppercase letter
     * - At least 1 lowercase letter
     * - At least 1 number
     *
     * @param string $password
     * @return bool
     */
    public static function isStrongPassword(string $password): bool
    {
        if (strlen($password) < 8) {
            return false;
        }

        return preg_match('/[A-Z]/', $password) &&
               preg_match('/[a-z]/', $password) &&
               preg_match('/[0-9]/', $password);
    }

    /**
     * Generate random token untuk verifikasi
     *
     * @param int $length
     * @return string
     */
    public static function generateToken(int $length = 32): string
    {
        return bin2hex(random_bytes($length / 2));
    }

    /**
     * Generate random code untuk OTP
     *
     * @param int $length
     * @return string
     */
    public static function generateOTP(int $length = 6): string
    {
        return str_pad((string)random_int(0, (10 ** $length) - 1), $length, '0', STR_PAD_LEFT);
    }

    /**
     * Sanitize string untuk prevent XSS
     *
     * @param string $string
     * @return string
     */
    public static function sanitizeString(string $string): string
    {
        return htmlspecialchars($string, ENT_QUOTES, 'UTF-8');
    }

    /**
     * Encrypt data menggunakan Encryption service
     *
     * @param string $data
     * @return string Base64 encoded
     */
    public static function encrypt(string $data): string
    {
        $encrypter = service('encrypter');
        return base64_encode($encrypter->encrypt($data));
    }

    /**
     * Decrypt data dari base64
     *
     * @param string $encryptedData
     * @return string|false
     */
    public static function decrypt(string $encryptedData)
    {
        try {
            $encrypter = service('encrypter');
            return $encrypter->decrypt(base64_decode($encryptedData));
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Generate signature untuk payment gateway (Midtrans example)
     *
     * @param string $orderId
     * @param string $statusCode
     * @param string $grossAmount
     * @param string $secret
     * @return string
     */
    public static function generateMidtransSignature(
        string $orderId,
        string $statusCode,
        string $grossAmount,
        string $secret
    ): string {
        $signatureString = $orderId . $statusCode . $grossAmount . $secret;
        return hash('sha512', $signatureString);
    }

    /**
     * Verify Midtrans signature
     *
     * @param string $orderId
     * @param string $statusCode
     * @param string $grossAmount
     * @param string $signature
     * @param string $secret
     * @return bool
     */
    public static function verifyMidtransSignature(
        string $orderId,
        string $statusCode,
        string $grossAmount,
        string $signature,
        string $secret
    ): bool {
        $expectedSignature = self::generateMidtransSignature($orderId, $statusCode, $grossAmount, $secret);
        return hash_equals($expectedSignature, $signature);
    }

    /**
     * Generate signature untuk Xendit
     *
     * @param string $secretKey
     * @param string $verificationToken
     * @return string
     */
    public static function generateXenditSignature(string $secretKey, string $verificationToken): string
    {
        return hash_hmac('sha256', $verificationToken, $secretKey);
    }

    /**
     * Verify Xendit signature
     *
     * @param string $secretKey
     * @param string $verificationToken
     * @param string $signature
     * @return bool
     */
    public static function verifyXenditSignature(string $secretKey, string $verificationToken, string $signature): bool
    {
        $expectedSignature = self::generateXenditSignature($secretKey, $verificationToken);
        return hash_equals($expectedSignature, $signature);
    }

    /**
     * Generate HMAC signature general purpose
     *
     * @param string $data
     * @param string $secret
     * @param string $algorithm
     * @return string
     */
    public static function generateHMACSignature(
        string $data,
        string $secret,
        string $algorithm = 'sha256'
    ): string {
        return hash_hmac($algorithm, $data, $secret);
    }

    /**
     * Verify HMAC signature
     *
     * @param string $data
     * @param string $signature
     * @param string $secret
     * @param string $algorithm
     * @return bool
     */
    public static function verifyHMACSignature(
        string $data,
        string $signature,
        string $secret,
        string $algorithm = 'sha256'
    ): bool {
        $expectedSignature = self::generateHMACSignature($data, $secret, $algorithm);
        return hash_equals($expectedSignature, $signature);
    }
}

