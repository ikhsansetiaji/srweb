<?php

namespace App\Models;

use App\Services\AggregateQueueService;
use CodeIgniter\Model;

class SongRequestModel extends Model
{
    protected $table = 'song_requests';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    protected $useSoftDeletes = false;
    protected $allowedFields = ['user_id', 'guest_name', 'phone_wa', 'wa_consent', 'wa_consent_at', 'cafe_id', 'song_id', 'nominal', 'queue_type', 'status', 'requested_at', 'played_at'];
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

        // Normalisasi field WhatsApp (opsional)
        if (!empty($data['phone_wa']) && !empty($data['wa_consent'])) {
            $data['wa_consent']    = true;
            $data['wa_consent_at'] = date('Y-m-d H:i:s');
        } else {
            // Jika tidak ada nomor atau consent tidak diberikan, simpan null/false
            $data['phone_wa']      = $data['phone_wa'] ?? null;
            $data['wa_consent']    = false;
            $data['wa_consent_at'] = null;
        }

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
        return (new AggregateQueueService())->getNextSong($cafeId);
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
     * Get requests untuk cafe dengan pagination.
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
     * Get requests untuk cafe dengan filter dan pagination (untuk halaman riwayat lengkap).
     *
     * @param int    $cafeId
     * @param int    $limit
     * @param int    $offset
     * @param string $status   Filter status
     * @param string $dateFrom Filter tanggal mulai
     * @param string $dateTo   Filter tanggal akhir
     * @param string $search   Pencarian judul/artis
     * @return array
     */
    public function getCafeRequestsFiltered(
        int $cafeId,
        int $limit = 20,
        int $offset = 0,
        string $status = '',
        string $dateFrom = '',
        string $dateTo = '',
        string $search = ''
    ): array {
        $q = $this->select('song_requests.*, songs.title, songs.artist, songs.duration, songs.thumbnail')
            ->join('songs', 'song_requests.song_id = songs.id', 'left')
            ->where('song_requests.cafe_id', $cafeId);

        if ($status) {
            $q = $q->where('song_requests.status', $status);
        }
        if ($dateFrom) {
            $q = $q->where("DATE(song_requests.requested_at) >=", $dateFrom);
        }
        if ($dateTo) {
            $q = $q->where("DATE(song_requests.requested_at) <=", $dateTo);
        }
        if ($search) {
            $q = $q->groupStart()
                   ->like('songs.title', $search)
                   ->orLike('songs.artist', $search)
                   ->orLike('song_requests.guest_name', $search)
                   ->groupEnd();
        }

        return $q->orderBy('song_requests.requested_at', 'DESC')
                 ->limit($limit, $offset)
                 ->findAll();
    }

    /**
     * Count requests cafe dengan filter (untuk pagination).
     */
    public function countCafeRequests(
        int $cafeId,
        string $status = '',
        string $dateFrom = '',
        string $dateTo = '',
        string $search = ''
    ): int {
        $q = $this->join('songs', 'song_requests.song_id = songs.id', 'left')
                  ->where('song_requests.cafe_id', $cafeId);
        if ($status)   $q = $q->where('song_requests.status', $status);
        if ($dateFrom) $q = $q->where("DATE(song_requests.requested_at) >=", $dateFrom);
        if ($dateTo)   $q = $q->where("DATE(song_requests.requested_at) <=", $dateTo);
        if ($search) {
            $q = $q->groupStart()
                   ->like('songs.title', $search)
                   ->orLike('songs.artist', $search)
                   ->orLike('song_requests.guest_name', $search)
                   ->groupEnd();
        }
        return $q->countAllResults();
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
