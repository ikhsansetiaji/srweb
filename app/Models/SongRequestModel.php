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

    /**
     * Get song request lengkap dengan info lagu dan cafe
     */
    public function getRequestWithDetails(int $requestId): ?array
    {
        return $this->select('song_requests.*, songs.title, songs.artist, songs.duration, songs.thumbnail, cafes.nama_kafe, cafes.id as cafe_id')
            ->join('songs', 'song_requests.song_id = songs.id', 'left')
            ->join('cafes', 'song_requests.cafe_id = cafes.id', 'left')
            ->find($requestId);
    }

    public function createRequest(array $data)
    {
        $data['guest_name'] = empty($data['guest_name']) ? 'Anonim' : $data['guest_name'];

        // Priority harus bayar dulu — status pending_payment sampai Midtrans konfirmasi
        $queueType = $data['queue_type'] ?? 'fifo';
        if (empty($queueType)) {
            $queueType = ($data['nominal'] >= 50000) ? 'priority' : 'fifo';
        }
        $data['queue_type'] = $queueType;
        $data['status'] = ($queueType === 'priority') ? 'pending_payment' : 'waiting';
        $data['requested_at'] = date('Y-m-d H:i:s');

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
    /**
     * Jika aggregation diaktifkan per-cafe, pilih lagu berdasarkan total nominal per lagu.
     * Skema pemilihan:
     * 1. Pilih song_id dengan SUM(nominal) terbesar (total_nominal)
     * 2. Jika tie, pilih dengan COUNT(request) lebih banyak
     * 3. Jika tie, pilih dengan earliest first_requested_at
     * 4. Hasil: ambil request pertama (earliest requested_at) yang masih waiting untuk song_id tersebut
     *
     * Jika aggregation tidak aktif atau tidak ada hasil, fallback ke behavior lama.
     */
    public function getNextSongToPlay(int $cafeId): ?array
    {
        $cafeModel = new \App\Models\CafeModel();
        $cafe = $cafeModel->find($cafeId);
        $aggregateEnabled = !empty($cafe) && !empty($cafe['aggregate_enabled']);

        if ($aggregateEnabled) {
            $aggRequest = $this->getAggregatedNextSong($cafeId);
            if ($aggRequest) {
                return $aggRequest;
            }
            // lanjut ke fallback jika tidak ada
        }

        // Fallback ke behavior lama: priority berdasarkan nominal tunggal
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
     * Hitung agregasi dan kembalikan request pertama untuk song terpilih
     *
     * @param int $cafeId
     * @return array|null
     */
    public function getAggregatedNextSong(int $cafeId): ?array
    {
        $db = \Config\Database::connect();

        $agg = $db->table('song_requests sr')
            ->select('sr.song_id, SUM(sr.nominal) AS total_nominal, COUNT(sr.id) AS request_count, MIN(sr.requested_at) AS first_requested_at')
            ->where('sr.cafe_id', $cafeId)
            ->where('sr.nominal >', 0)
            ->where('sr.status', 'waiting')
            ->groupBy('sr.song_id')
            ->orderBy('total_nominal', 'DESC')
            ->orderBy('request_count', 'DESC')
            ->orderBy('first_requested_at', 'ASC')
            ->get()
            ->getRowArray();

        if (empty($agg) || empty($agg['song_id'])) {
            return null;
        }

        // Ambil request pertama (earliest) untuk song tersebut yang masih waiting dan berbayar
        $request = $this->select('song_requests.*, songs.title, songs.artist, songs.duration, songs.thumbnail, songs.spotify_url, songs.preview_url, songs.api_song_id')
            ->join('songs', 'song_requests.song_id = songs.id', 'left')
            ->where('song_requests.cafe_id', $cafeId)
            ->where('song_requests.song_id', $agg['song_id'])
            ->where('song_requests.nominal >', 0)
            ->where('song_requests.status', 'waiting')
            ->orderBy('song_requests.requested_at', 'ASC')
            ->first();

        return $request ?: null;
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
            ->where('song_requests.status !=', 'cancelled')
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