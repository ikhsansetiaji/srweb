<?php

namespace App\Services;

use App\Models\SongRequestModel;

class QueueService
{
    protected $requestModel;

    public function __construct()
    {
        $this->requestModel = new SongRequestModel();
    }

    /**
     * Get next song untuk diputar menggunakan algorithm:
     * 1. Priority queue dengan nominal terbesar dulu
     * 2. Jika nominal sama, ambil yang paling dulu (requested_at ASC)
     * 3. Jika priority kosong, ambil FIFO queue
     *
     * @param int $cafeId
     * @return array|null
     */
    public function getNextSong(int $cafeId): ?array
    {
        return $this->requestModel->getNextSongToPlay($cafeId);
    }

    /**
     * Start playing song
     *
     * @param int $requestId
     * @return bool
     */
    public function playSong(int $requestId): bool
    {
        return $this->requestModel->startPlaying($requestId);
    }

    /**
     * Mark song as done dan return next song
     *
     * @param int $cafeId
     * @param int $currentRequestId
     * @return array|null
     */
    public function nextSong(int $cafeId, int $currentRequestId): ?array
    {
        // Mark current as done
        $this->requestModel->markAsDone($currentRequestId);

        // Return next song
        return $this->getNextSong($cafeId);
    }

    /**
     * Get queue info untuk cafe
     *
     * @param int $cafeId
     * @return array
     */
    public function getQueueInfo(int $cafeId): array
    {
        $currentlyPlaying = $this->requestModel->getCurrentlyPlaying($cafeId);
        $waitingCount = $this->requestModel->countWaitingRequests($cafeId);

        return [
            'current' => $currentlyPlaying,
            'waiting_count' => $waitingCount,
        ];
    }

    /**
     * Get full queue untuk admin cafe
     *
     * @param int $cafeId
     * @return array
     */
    public function getFullQueue(int $cafeId): array
    {
        $db = \Config\Database::connect();

        // Priority queue
        $priorityQueue = $db->table('song_requests')
            ->select('song_requests.*, songs.title, songs.artist, songs.duration, songs.thumbnail')
            ->join('songs', 'song_requests.song_id = songs.id', 'left')
            ->where('song_requests.cafe_id', $cafeId)
            ->where('song_requests.queue_type', 'priority')
            ->where('song_requests.status', 'waiting')
            ->orderBy('song_requests.nominal', 'DESC')
            ->orderBy('song_requests.requested_at', 'ASC')
            ->get()
            ->getResultArray();

        // FIFO queue
        $fifoQueue = $db->table('song_requests')
            ->select('song_requests.*, songs.title, songs.artist, songs.duration, songs.thumbnail')
            ->join('songs', 'song_requests.song_id = songs.id', 'left')
            ->where('song_requests.cafe_id', $cafeId)
            ->where('song_requests.queue_type', 'fifo')
            ->where('song_requests.status', 'waiting')
            ->orderBy('song_requests.requested_at', 'ASC')
            ->get()
            ->getResultArray();

        return [
            'priority' => $priorityQueue,
            'fifo' => $fifoQueue,
            'total' => count($priorityQueue) + count($fifoQueue),
        ];
    }

    /**
     * Get total request untuk cafe today
     *
     * @param int $cafeId
     * @return int
     */
    public function getTodayRequestCount(int $cafeId): int
    {
        $db = \Config\Database::connect();
        $today = date('Y-m-d');

        $result = $db->table('song_requests')
            ->where('cafe_id', $cafeId)
            ->where("DATE(requested_at) = '$today'")
            ->countAllResults();

        return $result;
    }

    /**
     * Get queue statistics
     *
     * @param int $cafeId
     * @return array
     */
    public function getQueueStats(int $cafeId): array
    {
        $db = \Config\Database::connect();

        $stats = $db->table('song_requests')
            ->selectCount('id', 'total_requests')
            ->selectCount('id', 'priority_count', false)
            ->where('cafe_id', $cafeId)
            ->where('status != ', 'cancelled')
            ->groupBy('queue_type')
            ->get()
            ->getResultArray();

        $waiting = $this->requestModel->countWaitingRequests($cafeId);

        return [
            'total_requests' => array_sum(array_column($stats, 'total_requests')),
            'waiting_requests' => $waiting,
            'by_type' => $stats,
        ];
    }

    /**
     * Get next song yang harus diputar (alias untuk getNextSong)
     *
     * @param int $cafeId
     * @return array|null
     */
    public function getNextSongToPlay(int $cafeId): ?array
    {
        return $this->getNextSong($cafeId);
    }
}

