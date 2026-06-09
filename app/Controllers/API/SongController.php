<?php

namespace App\Controllers\API;

use App\Models\SongModel;
use App\Services\SpotifyService;
use CodeIgniter\RESTful\ResourceController;

class SongController extends ResourceController
{
    protected SongModel $songModel;
    protected $format = 'json';

    public function __construct()
    {
        $this->songModel = new SongModel();
    }

    /**
     * Search songs via Spotify API + sinkronisasi ke database lokal
     * GET /api/v1/songs/search?q=query&limit=20
     */
    public function search()
    {
        $query = $this->request->getVar('q');
        $limit = (int) ($this->request->getVar('limit') ?? 10);
        $limit = max(1, min($limit, 10));

        if (!$query || strlen(trim($query)) < 2) {
            return $this->failValidationError('Query minimal 2 karakter');
        }

        $query = trim($query);

        $spotifyService = new SpotifyService();

        if (!$spotifyService->isConfigured()) {
            return $this->respond([
                'success' => false,
                'message' => 'Spotify belum dikonfigurasi'
            ], 503);
        }

        $spotifyResult = $spotifyService->searchSongs($query, $limit);

        if (!$spotifyResult['success']) {
            // Fallback ke database lokal
            $localSongs = $this->songModel->searchSongs($query, $limit);
            return $this->respond([
                'success' => true,
                'data'    => $localSongs,
                'total'   => count($localSongs),
                'source'  => 'local_fallback',
            ]);
        }

        // Sync ke DB lokal & tambahkan local_id ke setiap hasil
        $results = [];
        foreach ($spotifyResult['data'] as $song) {
            try {
                $localId = $this->songModel->getOrCreateSong($song['spotify_id'], [
                    'title'       => $song['title'],
                    'artist'      => $song['artist'],
                    'album'       => $song['album'],
                    'duration'    => (int) $song['duration'],
                    'thumbnail'   => $song['thumbnail'],
                    'spotify_url' => $song['spotify_url'],
                    'preview_url' => $song['preview_url'],
                ]);
                $song['local_id'] = $localId;
            } catch (\Exception $e) {
                log_message('error', "Sync gagal [{$song['spotify_id']}]: " . $e->getMessage());
                $song['local_id'] = null;
            }
            $results[] = $song;
        }

        return $this->respond([
            'success' => true,
            'data'    => $results,
            'total'   => count($results),
            'source'  => 'spotify',
        ]);
    }

    /**
     * Search Spotify langsung tanpa sinkronisasi database
     * GET /api/v1/spotify/search?q=query&limit=20
     * Endpoint ini mengembalikan data langsung dari Spotify
     */
    public function searchSpotify()
    {
        $query = $this->request->getVar('q');
        $limit = (int) ($this->request->getVar('limit') ?? 20);
        $limit = max(1, min($limit, 50));

        if (!$query || strlen(trim($query)) < 2) {
            return $this->failValidationError('Query minimal 2 karakter');
        }

        $spotifyService = new SpotifyService();

        if (!$spotifyService->isConfigured()) {
            return $this->respond([
                'success' => false,
                'message' => 'Spotify belum dikonfigurasi',
            ], 503);
        }

        $result = $spotifyService->searchSongs(trim($query), $limit);

        return $this->respond($result);
    }

    /**
     * Get track detail dari Spotify
     * GET /api/v1/spotify/track/:spotifyId
     */
    public function getTrackDetail($spotifyId = null)
    {
        if (!$spotifyId) {
            return $this->failValidationError('Spotify ID diperlukan');
        }

        $spotifyService = new SpotifyService();
        $track = $spotifyService->getTrackDetails($spotifyId);

        if (!$track) {
            return $this->failNotFound('Lagu tidak ditemukan di Spotify');
        }

        // Sinkronisasi ke database lokal
        try {
            $localId = $this->songModel->getOrCreateSong($track['spotify_id'], [
                'title'       => $track['title'],
                'artist'      => $track['artist'],
                'album'       => $track['album'],
                'duration'    => $track['duration'],
                'thumbnail'   => $track['thumbnail'],
                'spotify_url' => $track['spotify_url'],
                'preview_url' => $track['preview_url'],
            ]);
            $track['local_id'] = $localId;
        } catch (\Exception $e) {
            log_message('error', 'Gagal sync track detail: ' . $e->getMessage());
        }

        return $this->respond([
            'success' => true,
            'data'    => $track,
        ]);
    }

    /**
     * Get popular songs dari database lokal
     * GET /api/v1/songs/popular?limit=20
     */
    public function popular()
    {
        $limit = (int) ($this->request->getVar('limit') ?? 20);
        $songs = $this->songModel->getPopularSongs($limit);

        return $this->respond([
            'success' => true,
            'data'    => $songs,
            'total'   => count($songs),
        ]);
    }

    /**
     * Get song detail dari database lokal
     * GET /api/v1/songs/:id
     */
    public function show($id = null)
    {
        $song = $this->songModel->getSongSafely((int) $id);

        if (!$song) {
            return $this->failNotFound('Lagu tidak ditemukan');
        }

        return $this->respond([
            'success' => true,
            'data'    => $song,
        ]);
    }
}
