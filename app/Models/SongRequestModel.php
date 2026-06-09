<?php

namespace App\Models;

use CodeIgniter\Model;

class SongRequestModel extends Model
{
    protected $table = 'song_requests';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    protected $useSoftDeletes = false;
    protected $allowedFields = ['user_id', 'guest_name', 'cafe_id', 'song_id', 'nominal', 'queue_type', 'status', 'requested_at', 'played_at'];
    protected $useTimestamps = false;

    protected $validationRules = [
        'cafe_id' => 'required|integer',
        'song_id' => 'required|integer',
        'nominal' => 'required|integer|greater_than_equal_to[0]',
        'queue_type' => 'required|in_list[priority,fifo]',
        'guest_name' => 'permit_empty|string|max_length[100]',
    ];

    /**
     * Create song request dengan security
     *
     * @param array $data
     * @return int|false
     */
    public function createRequest(array $data)
    {
        $data['guest_name'] = empty($data['guest_name']) ? 'Anonim' : $data['guest_name'];
        $data['status'] = 'waiting';
        $data['requested_at'] = date('Y-m-d H:i:s');

        // Determine queue_type based on nominal threshold
        if (empty($data['queue_type'])) {
            $data['queue_type'] = $data['nominal'] >= 50000 ? 'priority' : 'fifo';
        }

        if ($this->insert($data)) {
            return $this->insertID;
        }

        return false;
    }

    /**
     * Get next song untuk diputar (QUEUE ALGORITHM)
     * Priority:
     * 1. Priority queue dengan nominal terbesar
     * 2. Jika nominal sama, ambil yang paling dulu (requested_at ASC)
     * 3. Jika priority kosong, ambil FIFO queue (requested_at ASC)
     *
     * @param int $cafeId
     * @return array|null
     */
    public function getNextSongToPlay(int $cafeId): ?array
    {
        // Cari priority queue dulu
        $prioritySong = $this->select('song_requests.*, songs.title, songs.artist, songs.duration, songs.thumbnail, songs.spotify_url, songs.preview_url, songs.api_song_id')
            ->join('songs', 'song_requests.song_id = songs.id', 'left')
            ->where('song_requests.cafe_id', $cafeId)
            ->where('song_requests.queue_type', 'priority')
            ->where('song_requests.status', 'waiting')
            ->orderBy('song_requests.nominal', 'DESC')
            ->orderBy('song_requests.requested_at', 'ASC')
            ->first();

        if ($prioritySong) {
            return $prioritySong;
        }

        // Jika tidak ada priority, ambil FIFO
        return $this->select('song_requests.*, songs.title, songs.artist, songs.duration, songs.thumbnail, songs.spotify_url, songs.preview_url, songs.api_song_id')
            ->join('songs', 'song_requests.song_id = songs.id', 'left')
            ->where('song_requests.cafe_id', $cafeId)
            ->where('song_requests.queue_type', 'fifo')
            ->where('song_requests.status', 'waiting')
            ->orderBy('song_requests.requested_at', 'ASC')
            ->first();
    }

    /**
     * Start playing song
     *
     * @param int $requestId
     * @return bool
     */
    public function startPlaying(int $requestId): bool
    {
        return $this->update($requestId, [
            'status' => 'playing',
            'played_at' => date('Y-m-d H:i:s'),
        ]);
    }

    /**
     * Mark song as done
     *
     * @param int $requestId
     * @return bool
     */
    public function markAsDone(int $requestId): bool
    {
        return $this->update($requestId, [
            'status' => 'done',
        ]);
    }

    /**
     * Cancel song request
     *
     * @param int $requestId
     * @return bool
     */
    public function cancelRequest(int $requestId): bool
    {
        return $this->update($requestId, [
            'status' => 'cancelled',
        ]);
    }

    /**
     * Get requests untuk cafe dengan pagination
     *
     * @param int $cafeId
     * @param int $limit
     * @param int $offset
     * @return array
     */
    public function getCafeRequests(int $cafeId, int $limit = 20, int $offset = 0): array
    {
        return $this->select('song_requests.*, songs.title, songs.artist, songs.duration, songs.thumbnail')
            ->join('songs', 'song_requests.song_id = songs.id', 'left')
            ->where('song_requests.cafe_id', $cafeId)
            ->orderBy('song_requests.requested_at', 'DESC')
            ->limit($limit, $offset)
            ->findAll();
    }

    /**
     * Get user requests history
     *
     * @param int $userId
     * @param int $limit
     * @return array
     */
    public function getUserRequests(int $userId, int $limit = 50): array
    {
        return $this->select('song_requests.*, songs.title, songs.artist, cafes.nama_kafe')
            ->join('songs', 'song_requests.song_id = songs.id', 'left')
            ->join('cafes', 'song_requests.cafe_id = cafes.id', 'left')
            ->where('song_requests.user_id', $userId)
            ->where('song_requests.status != ', 'cancelled')
            ->orderBy('song_requests.requested_at', 'DESC')
            ->limit($limit)
            ->findAll();
    }

    /**
     * Count waiting requests in cafe
     *
     * @param int $cafeId
     * @return int
     */
    public function countWaitingRequests(int $cafeId): int
    {
        return $this->where('cafe_id', $cafeId)
            ->where('status', 'waiting')
            ->countAllResults();
    }

    /**
     * Get currently playing song in cafe
     *
     * @param int $cafeId
     * @return array|null
     */
    public function getCurrentlyPlaying(int $cafeId): ?array
    {
        return $this->select('song_requests.*, songs.title, songs.artist, songs.duration, songs.thumbnail, songs.spotify_url, songs.preview_url, songs.api_song_id')
            ->join('songs', 'song_requests.song_id = songs.id', 'left')
            ->where('song_requests.cafe_id', $cafeId)
            ->where('song_requests.status', 'playing')
            ->first();
    }

    /**
     * Update status request
     *
     * @param int $requestId
     * @param string $status
     * @return bool
     */
    public function updateStatus(int $requestId, string $status): bool
    {
        $updateData = ['status' => $status];

        if ($status === 'playing') {
            $updateData['played_at'] = date('Y-m-d H:i:s');
        }

        return $this->update($requestId, $updateData);
    }
}

