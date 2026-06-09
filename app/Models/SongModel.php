<?php

namespace App\Models;

use CodeIgniter\Model;

class SongModel extends Model
{
    protected $table            = 'songs';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $useTimestamps    = false;

    protected $allowedFields = [
        'api_song_id',
        'title',
        'artist',
        'album',
        'duration',
        'thumbnail',
        'spotify_url',
        'preview_url',
    ];

    // Validation tanpa is_unique karena getOrCreateSong() sudah handle duplicate check
    protected $validationRules = [
        'api_song_id' => 'required|string|max_length[100]',
        'title'       => 'required|string|max_length[255]',
        'artist'      => 'required|string|max_length[255]',
        'duration'    => 'required|integer',
    ];

    protected $validationMessages = [
        'api_song_id' => ['required' => 'Spotify ID harus diisi'],
        'title'       => ['required' => 'Judul lagu harus diisi'],
        'artist'      => ['required' => 'Nama artis harus diisi'],
    ];

    /**
     * Get atau create song dari data Spotify
     * Safe dari duplicate — cek dulu apakah sudah ada berdasarkan api_song_id
     *
     * @param string $apiSongId Spotify track ID
     * @param array $songData Data lagu dari Spotify
     * @return int Song ID (existing or newly created)
     */
    public function getOrCreateSong(string $apiSongId, array $songData): int
    {
        // Cari dulu apakah sudah ada
        $existingSong = $this->where('api_song_id', $apiSongId)->first();

        if ($existingSong) {
            // Update data jika ada perubahan (thumbnail, preview_url, dll bisa berubah)
            $updateData = [];
            $fieldsToCheck = ['title', 'artist', 'album', 'duration', 'thumbnail', 'spotify_url', 'preview_url'];

            foreach ($fieldsToCheck as $field) {
                if (isset($songData[$field]) && $songData[$field] !== ($existingSong[$field] ?? null)) {
                    $updateData[$field] = $songData[$field];
                }
            }

            if (!empty($updateData)) {
                $this->skipValidation(true)->update($existingSong['id'], $updateData);
            }

            return (int) $existingSong['id'];
        }

        // Insert baru
        $songData['api_song_id'] = $apiSongId;
        $songData['created_at']  = date('Y-m-d H:i:s');

        $this->skipValidation(false)->insert($songData);

        $insertId = $this->insertID();
        if (!$insertId) {
            // Log validation errors jika ada
            $errors = $this->errors();
            if (!empty($errors)) {
                log_message('error', 'SongModel insert validation errors: ' . json_encode($errors));
            }
            throw new \RuntimeException('Gagal menyimpan lagu ke database: ' . json_encode($errors));
        }

        return (int) $insertId;
    }

    /**
     * Search songs dari database lokal
     *
     * @param string $query Kata kunci pencarian
     * @param int $limit Jumlah maksimal hasil
     * @return array
     */
    public function searchSongs(string $query, int $limit = 20): array
    {
        $query = trim($query);

        if (empty($query) || strlen($query) < 2) {
            return [];
        }

        return $this->select('*')
            ->groupStart()
                ->like('title', $query)
                ->orLike('artist', $query)
                ->orLike('album', $query)
            ->groupEnd()
            ->orderBy('title', 'ASC')
            ->limit($limit)
            ->findAll();
    }

    /**
     * Get popular songs berdasarkan jumlah request
     *
     * @param int $limit
     * @return array
     */
    public function getPopularSongs(int $limit = 20): array
    {
        return $this->select('songs.*, COUNT(sr.id) as request_count')
            ->join('song_requests sr', 'songs.id = sr.song_id', 'left')
            ->groupBy('songs.id')
            ->orderBy('request_count', 'DESC')
            ->limit($limit)
            ->findAll();
    }

    /**
     * Get song detail dengan verifikasi existence
     *
     * @param int $songId
     * @return array|null
     */
    public function getSongSafely(int $songId): ?array
    {
        return $this->find($songId);
    }

    /**
     * Get song by Spotify ID
     *
     * @param string $spotifyId
     * @return array|null
     */
    public function getBySpotifyId(string $spotifyId): ?array
    {
        return $this->where('api_song_id', $spotifyId)->first();
    }
}
