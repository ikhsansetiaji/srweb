<?php

namespace App\Libraries;

/**
 * TabSessionManager
 *
 * Mengelola session per-tab menggunakan token unik.
 * Data login disimpan di PHP session berdasarkan tabToken,
 * sehingga setiap tab browser bisa login dengan akun berbeda.
 *
 * Cara kerja:
 *  - Setiap tab mendapat tabToken unik (dari sessionStorage browser)
 *  - Token dikirim via header X-Tab-Token di setiap request
 *  - Server menyimpan: $_SESSION['tabs'][tabToken] = [...data user...]
 *  - Filter membaca token dari header untuk identifikasi siapa yang request
 *
 * PENTING: hydrateSession() menyimpan data ke session() hanya untuk
 * kompatibilitas kode lama, dan HARUS dibersihkan di after-filter
 * via dehydrateSession() agar tidak "bocor" ke tab lain.
 */
class TabSessionManager
{
    private const SESSION_KEY = 'tabs';
    private const TOKEN_HEADER = 'X-Tab-Token';
    private const TOKEN_LENGTH = 32;

    /**
     * In-memory store: data user yang sedang di-hydrate untuk request ini.
     * Digunakan untuk mengetahui key mana yang perlu dibersihkan di after-filter.
     */
    private static ?array $hydratedData = null;

    /**
     * In-memory store: tab token yang aktif di request ini.
     * Bisa diakses dari view tanpa perlu query param.
     */
    private static ?string $currentTabToken = null;

    /**
     * Generate token baru untuk tab baru
     */
    public static function generateToken(): string
    {
        return bin2hex(random_bytes(self::TOKEN_LENGTH));
    }

    /**
     * Ambil tabToken dari header request
     */
    public static function getTokenFromRequest(): ?string
    {
        $request = service('request');

        $token = $request->getHeaderLine(self::TOKEN_HEADER);
        if ($token && self::isValidToken($token)) {
            return $token;
        }

        // Fallback: dari GET query parameter (untuk navigasi biasa)
        $token = $request->getGet('_tab_token');
        if ($token && self::isValidToken($token)) {
            return $token;
        }

        // Fallback: dari POST body (untuk form biasa)
        $token = $request->getPost('_tab_token');
        if ($token && self::isValidToken($token)) {
            return $token;
        }

        return null;
    }

    /**
     * Simpan data user ke session tab tertentu
     */
    public static function setTabUser(string $tabToken, array $userData): void
    {
        $tabs = session()->get(self::SESSION_KEY) ?? [];
        $tabs[$tabToken] = [
            'user_id'    => $userData['user_id'],
            'user_name'  => $userData['user_name'],
            'user_email' => $userData['user_email'],
            'user_role'  => $userData['user_role'],
            'logged_in'  => true,
            'login_at'   => time(),
        ];
        session()->set(self::SESSION_KEY, $tabs);
    }

    /**
     * Ambil data user untuk tab tertentu
     */
    public static function getTabUser(string $tabToken): ?array
    {
        $tabs = session()->get(self::SESSION_KEY) ?? [];
        return $tabs[$tabToken] ?? null;
    }

    /**
     * Hapus session untuk tab tertentu (logout tab)
     */
    public static function clearTab(string $tabToken): void
    {
        $tabs = session()->get(self::SESSION_KEY) ?? [];
        unset($tabs[$tabToken]);
        session()->set(self::SESSION_KEY, $tabs);
    }

    /**
     * Hapus semua tab session (logout semua)
     */
    public static function clearAllTabs(): void
    {
        session()->remove(self::SESSION_KEY);
    }

    /**
     * Cek apakah tab ini sudah login
     */
    public static function isTabLoggedIn(string $tabToken): bool
    {
        $user = self::getTabUser($tabToken);
        return $user !== null && ($user['logged_in'] ?? false) === true;
    }

    /**
     * Ambil role dari tab tertentu
     */
    public static function getTabRole(string $tabToken): ?string
    {
        $user = self::getTabUser($tabToken);
        return $user['user_role'] ?? null;
    }

    /**
     * Ambil user_id dari tab tertentu
     */
    public static function getTabUserId(string $tabToken): ?int
    {
        $user = self::getTabUser($tabToken);
        return isset($user['user_id']) ? (int) $user['user_id'] : null;
    }

    /**
     * Validasi format token (hex 64 char)
     */
    public static function isValidToken(string $token): bool
    {
        return (bool) preg_match('/^[a-f0-9]{64}$/', $token);
    }

    /**
     * Set tab user data ke session() helper agar
     * kode lama yang pakai session()->get('user_role') tetap jalan
     * selama request berlangsung.
     *
     * PENTING: Data ini HARUS dibersihkan di after() filter via
     * dehydrateSession() agar tidak bocor ke tab lain!
     *
     * Dipanggil di filter setelah token divalidasi.
     */
    public static function hydrateSession(string $tabToken): bool
    {
        $user = self::getTabUser($tabToken);
        if (!$user) {
            return false;
        }

        // Simpan referensi agar bisa dihapus nanti
        self::$hydratedData = [
            'user_id'    => $user['user_id'],
            'user_name'  => $user['user_name'],
            'user_email' => $user['user_email'],
            'user_role'  => $user['user_role'],
            'logged_in'  => true,
            '_tab_token' => $tabToken,
        ];

        self::$currentTabToken = $tabToken;

        // Set ke session untuk kompatibilitas kode lama
        session()->set(self::$hydratedData);

        return true;
    }

    /**
     * Bersihkan data hydrated dari session agar tidak bocor ke tab lain.
     * Dipanggil di after() filter.
     */
    public static function dehydrateSession(): void
    {
        if (self::$hydratedData === null) {
            return;
        }

        $keys = array_keys(self::$hydratedData);
        foreach ($keys as $key) {
            session()->remove($key);
        }

        self::$hydratedData = null;
        self::$currentTabToken = null;
    }

    /**
     * Ambil current tab token yang sedang aktif di request ini.
     * Berguna untuk view/template tanpa perlu query param.
     */
    public static function getCurrentTabToken(): ?string
    {
        return self::$currentTabToken;
    }

    /**
     * Set current tab token (untuk request tanpa auth filter,
     * misalnya di global filter).
     */
    public static function setCurrentTabToken(?string $token): void
    {
        self::$currentTabToken = $token;
    }

    /**
     * Ambil data user yang sedang di-hydrate di request ini.
     * NULL jika belum di-hydrate (tab belum login / guest).
     */
    public static function getCurrentUser(): ?array
    {
        return self::$hydratedData;
    }
}