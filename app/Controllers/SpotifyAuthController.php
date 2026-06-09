<?php

namespace App\Controllers;

/**
 * Spotify OAuth Controller
 * Handles OAuth 2.0 Authorization Code flow for Spotify Web Playback SDK
 * Admin cafe harus login Spotify Premium agar bisa memutar lagu di browser
 */
class SpotifyAuthController extends BaseController
{
    private string $authUrl = 'https://accounts.spotify.com/authorize';
    private string $tokenUrl = 'https://accounts.spotify.com/api/token';

    private function getClientId(): string
    {
        return trim(env('SPOTIFY_CLIENT_ID', ''));
    }

    private function getClientSecret(): string
    {
        return trim(env('SPOTIFY_CLIENT_SECRET', ''));
    }

    private function getRedirectUri(): string
    {
        return rtrim(base_url(), '/') . '/spotify/callback';
    }

    /**
     * Step 1: Redirect admin to Spotify login page
     */
    public function connect()
    {
        if (session()->get('user_role') !== 'admin') {
            return redirect()->to('/auth/login');
        }

        $scopes = 'streaming user-read-email user-read-private user-modify-playback-state user-read-playback-state';
        
        $params = http_build_query([
            'client_id'     => $this->getClientId(),
            'response_type' => 'code',
            'redirect_uri'  => $this->getRedirectUri(),
            'scope'         => $scopes,
            'show_dialog'   => 'true',
        ]);

        return redirect()->to($this->authUrl . '?' . $params);
    }

    /**
     * Step 2: Handle callback from Spotify
     */
    public function callback()
    {
        if (session()->get('user_role') !== 'admin') {
            return redirect()->to('/auth/login');
        }

        $code = $this->request->getGet('code');
        $error = $this->request->getGet('error');

        if ($error) {
            session()->setFlashdata('error', 'Spotify connection cancelled: ' . $error);
            return redirect()->to('/admin/dashboard');
        }

        if (!$code) {
            session()->setFlashdata('error', 'Invalid Spotify callback');
            return redirect()->to('/admin/dashboard');
        }

        // Exchange code for tokens
        $tokens = $this->exchangeCodeForTokens($code);

        if (!$tokens) {
            session()->setFlashdata('error', 'Gagal mendapatkan token Spotify');
            return redirect()->to('/admin/dashboard');
        }

        // Store tokens in session
        session()->set([
            'spotify_access_token'  => $tokens['access_token'],
            'spotify_refresh_token' => $tokens['refresh_token'] ?? null,
            'spotify_expires_at'    => time() + ($tokens['expires_in'] ?? 3600),
        ]);

        session()->setFlashdata('success', 'Spotify berhasil terhubung! Lagu sekarang bisa diputar langsung di browser.');
        return redirect()->to('/admin/dashboard');
    }

    /**
     * API: Get current access token (for JS SDK)
     */
    public function getToken()
    {
        if (session()->get('user_role') !== 'admin') {
            return $this->response->setStatusCode(403)->setJSON(['error' => 'Unauthorized']);
        }

        $accessToken = session()->get('spotify_access_token');
        $expiresAt = session()->get('spotify_expires_at');

        // If token is expired or about to expire (within 5 minutes), refresh it
        if (!$accessToken || (time() + 300) >= ($expiresAt ?? 0)) {
            $refreshToken = session()->get('spotify_refresh_token');
            if ($refreshToken) {
                $newTokens = $this->refreshAccessToken($refreshToken);
                if ($newTokens) {
                    $accessToken = $newTokens['access_token'];
                    session()->set([
                        'spotify_access_token' => $accessToken,
                        'spotify_expires_at'   => time() + ($newTokens['expires_in'] ?? 3600),
                    ]);
                    if (!empty($newTokens['refresh_token'])) {
                        session()->set('spotify_refresh_token', $newTokens['refresh_token']);
                    }
                } else {
                    return $this->response->setJSON([
                        'connected' => false,
                        'error'     => 'Token expired, please reconnect',
                    ]);
                }
            } else {
                return $this->response->setJSON([
                    'connected' => false,
                    'error'     => 'No refresh token',
                ]);
            }
        }

        return $this->response->setJSON([
            'connected'    => true,
            'access_token' => $accessToken,
            'expires_in'   => max(0, ($expiresAt ?? 0) - time()),
        ]);
    }

    /**
     * Disconnect Spotify
     */
    public function disconnect()
    {
        if (session()->get('user_role') !== 'admin') {
            return redirect()->to('/auth/login');
        }

        session()->remove(['spotify_access_token', 'spotify_refresh_token', 'spotify_expires_at']);
        session()->setFlashdata('success', 'Spotify berhasil diputus.');
        return redirect()->to('/admin/dashboard');
    }

    /**
     * Exchange authorization code for access + refresh tokens
     */
    private function exchangeCodeForTokens(string $code): ?array
    {
        $postFields = http_build_query([
            'grant_type'   => 'authorization_code',
            'code'         => $code,
            'redirect_uri' => $this->getRedirectUri(),
        ]);

        $credentials = base64_encode($this->getClientId() . ':' . $this->getClientSecret());

        $ch = curl_init($this->tokenUrl);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => $postFields,
            CURLOPT_HTTPHEADER     => [
                'Authorization: Basic ' . $credentials,
                'Content-Type: application/x-www-form-urlencoded',
            ],
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_TIMEOUT        => 15,
        ]);

        $body = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode !== 200) {
            log_message('error', 'Spotify OAuth token exchange failed: HTTP ' . $httpCode . ' - ' . $body);
            return null;
        }

        return json_decode($body, true);
    }

    /**
     * Refresh access token using refresh token
     */
    private function refreshAccessToken(string $refreshToken): ?array
    {
        $postFields = http_build_query([
            'grant_type'    => 'refresh_token',
            'refresh_token' => $refreshToken,
        ]);

        $credentials = base64_encode($this->getClientId() . ':' . $this->getClientSecret());

        $ch = curl_init($this->tokenUrl);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => $postFields,
            CURLOPT_HTTPHEADER     => [
                'Authorization: Basic ' . $credentials,
                'Content-Type: application/x-www-form-urlencoded',
            ],
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_TIMEOUT        => 15,
        ]);

        $body = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode !== 200) {
            log_message('error', 'Spotify token refresh failed: HTTP ' . $httpCode . ' - ' . $body);
            return null;
        }

        return json_decode($body, true);
    }

    /**
     * Get or refresh a valid user access token
     */
    public static function getValidAccessToken(): ?string
    {
        $session = session();
        $accessToken = $session->get('spotify_access_token');
        $expiresAt = $session->get('spotify_expires_at');

        if (!$accessToken) {
            return null;
        }

        // If expired or about to expire in 5 minutes
        if ((time() + 300) >= ($expiresAt ?? 0)) {
            $refreshToken = $session->get('spotify_refresh_token');
            if ($refreshToken) {
                $instance = new self();
                $newTokens = $instance->refreshAccessToken($refreshToken);
                if ($newTokens) {
                    $accessToken = $newTokens['access_token'];
                    $session->set([
                        'spotify_access_token' => $accessToken,
                        'spotify_expires_at'   => time() + ($newTokens['expires_in'] ?? 3600),
                    ]);
                    if (!empty($newTokens['refresh_token'])) {
                        $session->set('spotify_refresh_token', $newTokens['refresh_token']);
                    }
                    return $accessToken;
                }
            }
            return null;
        }

        return $accessToken;
    }
}
