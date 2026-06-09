<?php

namespace App\Services;

/**
 * Spotify Service - Integrasi dengan Spotify API
 * Menggunakan native PHP cURL untuk menghindari bug CI4 CURLRequest
 * yang share cookies/headers dari browser request ke outgoing cURL calls
 */
class SpotifyService
{
    protected ?string $clientId;
    protected ?string $clientSecret;
    protected ?string $accessToken = null;
    protected ?int $tokenExpiresAt = null;

    protected string $baseUrl = 'https://api.spotify.com/v1';
    protected string $authUrl = 'https://accounts.spotify.com/api/token';

    public function __construct()
    {
        $this->clientId     = env('SPOTIFY_CLIENT_ID') ? trim(env('SPOTIFY_CLIENT_ID')) : null;
        $this->clientSecret = env('SPOTIFY_CLIENT_SECRET') ? trim(env('SPOTIFY_CLIENT_SECRET')) : null;
        $this->loadCachedToken();
    }

    public function isConfigured(): bool
    {
        return !empty($this->clientId) && !empty($this->clientSecret);
    }

    /**
     * Native cURL GET request
     */
    private function curlGet(string $url, array $headers = []): array
    {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER     => $headers,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_TIMEOUT        => 15,
        ]);
        $body     = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error    = curl_error($ch);
        curl_close($ch);

        return ['body' => $body, 'status' => $httpCode, 'error' => $error];
    }

    /**
     * Native cURL POST request
     */
    private function curlPost(string $url, string $postFields, array $headers = []): array
    {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => $postFields,
            CURLOPT_HTTPHEADER     => $headers,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_TIMEOUT        => 15,
        ]);
        $body     = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error    = curl_error($ch);
        curl_close($ch);

        return ['body' => $body, 'status' => $httpCode, 'error' => $error];
    }

    /**
     * Get access token via Client Credentials Flow
     */
    public function getAccessToken(): ?string
    {
        if (!$this->isConfigured()) {
            log_message('error', 'Spotify: Client ID atau Secret belum dikonfigurasi');
            return null;
        }

        // Return cached token if valid
        if ($this->accessToken && $this->tokenExpiresAt && time() < $this->tokenExpiresAt) {
            return $this->accessToken;
        }

        try {
            $credentials = base64_encode($this->clientId . ':' . $this->clientSecret);

            $result = $this->curlPost($this->authUrl, 'grant_type=client_credentials', [
                'Authorization: Basic ' . $credentials,
                'Content-Type: application/x-www-form-urlencoded',
            ]);

            if ($result['error']) {
                log_message('error', 'Spotify: Token cURL error - ' . $result['error']);
                return null;
            }

            $body = json_decode($result['body'], true);

            if ($result['status'] === 200 && isset($body['access_token'])) {
                $this->accessToken    = $body['access_token'];
                $this->tokenExpiresAt = time() + ($body['expires_in'] - 300);
                $this->cacheToken($this->accessToken, $body['expires_in'] - 300);
                log_message('info', 'Spotify: Token berhasil (expires_in: ' . $body['expires_in'] . 's)');
                return $this->accessToken;
            }

            log_message('error', 'Spotify: Token gagal HTTP ' . $result['status'] . ' - ' . $result['body']);
            return null;

        } catch (\Exception $e) {
            log_message('error', 'Spotify: Token exception - ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Search lagu di Spotify
     */
    public function searchSongs(string $query, int $limit = 10, int $offset = 0): array
    {
        $token = $this->getAccessToken();

        if (!$token) {
            return ['success' => false, 'message' => 'Gagal mendapatkan token Spotify', 'data' => [], 'total' => 0];
        }

        try {
            $params = http_build_query([
                'q'      => $query,
                'type'   => 'track',
                'limit'  => min($limit, 10),
                'offset' => $offset,
                'market' => 'ID',
            ]);

            $result = $this->curlGet($this->baseUrl . '/search?' . $params, [
                'Authorization: Bearer ' . $token,
            ]);

            if ($result['error']) {
                log_message('error', 'Spotify: Search cURL error - ' . $result['error']);
                return ['success' => false, 'message' => 'Connection error', 'data' => [], 'total' => 0];
            }

            // Handle token expired - retry once
            if ($result['status'] === 401) {
                $this->clearCachedToken();
                $token = $this->getAccessToken();
                if (!$token) {
                    return ['success' => false, 'message' => 'Token expired', 'data' => [], 'total' => 0];
                }
                $result = $this->curlGet($this->baseUrl . '/search?' . $params, [
                    'Authorization: Bearer ' . $token,
                ]);
            }

            if ($result['status'] === 200) {
                $body  = json_decode($result['body'], true);
                $songs = [];
                foreach (($body['tracks']['items'] ?? []) as $track) {
                    $songs[] = $this->formatTrack($track);
                }
                return ['success' => true, 'data' => $songs, 'total' => $body['tracks']['total'] ?? 0];
            }

            log_message('error', 'Spotify: Search HTTP ' . $result['status'] . ' - ' . $result['body']);
            return ['success' => false, 'message' => 'Spotify error HTTP ' . $result['status'], 'data' => [], 'total' => 0];

        } catch (\Exception $e) {
            log_message('error', 'Spotify: Search exception - ' . $e->getMessage());
            return ['success' => false, 'message' => $e->getMessage(), 'data' => [], 'total' => 0];
        }
    }

    /**
     * Get detail lagu dari Spotify
     */
    public function getTrackDetails(string $spotifyId): ?array
    {
        $token = $this->getAccessToken();
        if (!$token) return null;

        try {
            $result = $this->curlGet($this->baseUrl . '/tracks/' . $spotifyId . '?market=ID', [
                'Authorization: Bearer ' . $token,
            ]);

            if ($result['status'] === 200) {
                return $this->formatTrack(json_decode($result['body'], true));
            }
            return null;

        } catch (\Exception $e) {
            log_message('error', 'Spotify: Track detail error - ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Get new releases
     */
    public function getNewReleases(int $limit = 20): array
    {
        $token = $this->getAccessToken();
        if (!$token) return [];

        try {
            $params = http_build_query(['limit' => min($limit, 50), 'country' => 'ID']);
            $result = $this->curlGet($this->baseUrl . '/browse/new-releases?' . $params, [
                'Authorization: Bearer ' . $token,
            ]);

            if ($result['status'] === 200) {
                $body = json_decode($result['body'], true);
                return $body['albums']['items'] ?? [];
            }
            return [];

        } catch (\Exception $e) {
            log_message('error', 'Spotify: New releases error - ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Format Spotify track ke format internal
     */
    private function formatTrack(array $track): array
    {
        $thumbnail = null;
        $images = $track['album']['images'] ?? [];
        if (!empty($images)) {
            foreach ($images as $img) {
                if (isset($img['height']) && $img['height'] === 300) {
                    $thumbnail = $img['url'];
                    break;
                }
            }
            if (!$thumbnail) {
                $thumbnail = $images[0]['url'] ?? null;
            }
        }

        $artists = array_map(fn($a) => $a['name'], $track['artists'] ?? []);

        return [
            'spotify_id'   => $track['id'],
            'title'        => $track['name'],
            'artist'       => implode(', ', $artists) ?: 'Unknown Artist',
            'album'        => $track['album']['name'] ?? 'Unknown Album',
            'duration'     => (int) round(($track['duration_ms'] ?? 0) / 1000),
            'thumbnail'    => $thumbnail,
            'spotify_url'  => $track['external_urls']['spotify'] ?? null,
            'preview_url'  => $track['preview_url'] ?? null,
            'popularity'   => $track['popularity'] ?? 0,
        ];
    }

    private function loadCachedToken(): void
    {
        $this->accessToken    = cache()->get('spotify_access_token');
        $this->tokenExpiresAt = cache()->get('spotify_token_expires_at');
    }

    private function cacheToken(string $token, int $ttl): void
    {
        cache()->save('spotify_access_token', $token, $ttl);
        cache()->save('spotify_token_expires_at', time() + $ttl, $ttl);
    }

    private function clearCachedToken(): void
    {
        cache()->delete('spotify_access_token');
        cache()->delete('spotify_token_expires_at');
        $this->accessToken    = null;
        $this->tokenExpiresAt = null;
    }

    /**
     * Play track on user's active Spotify device
     */
    public function playTrackForUser(string $accessToken, string $spotifyId): array
    {
        $url = $this->baseUrl . '/me/player/play';
        $body = json_encode([
            'uris' => ['spotify:track:' . $spotifyId]
        ]);

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CUSTOMREQUEST  => 'PUT',
            CURLOPT_POSTFIELDS     => $body,
            CURLOPT_HTTPHEADER     => [
                'Authorization: Bearer ' . $accessToken,
                'Content-Type: application/json',
            ],
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_TIMEOUT        => 10,
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error    = curl_error($ch);
        curl_close($ch);

        return ['status' => $httpCode, 'body' => $response, 'error' => $error];
    }
}
