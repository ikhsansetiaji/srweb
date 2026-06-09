<?php

namespace App\Controllers;

use App\Models\SongRequestModel;
use App\Models\SongModel;
use App\Models\CafeModel;
use App\Services\QueueService;
use App\Services\SpotifyService;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Psr\Log\LoggerInterface;

class SongRequestController extends BaseController
{
    protected $requestModel;
    protected $songModel;
    protected $cafeModel;
    protected $queueService;

    public function initController(RequestInterface $request, ResponseInterface $response, LoggerInterface $logger)
    {
        parent::initController($request, $response, $logger);
        $this->requestModel = new SongRequestModel();
        $this->songModel = new SongModel();
        $this->cafeModel = new CafeModel();
        $this->queueService = new QueueService();
    }

    /**
     * Show request lagu page untuk guest
     */
    public function requestPage()
    {
        $cafeId = $this->request->getGet('cafe_id');

        if (!$cafeId) {
            return view('song-request/select-cafe', [
                'cafes' => $this->cafeModel->getActiveCafes()
            ]);
        }

        $cafe = $this->cafeModel->find($cafeId);
        if (!$cafe || $cafe['status'] !== 'approved') {
            return $this->response->setStatusCode(404)->setJSON([
                'success' => false,
                'message' => 'Cafe tidak ditemukan'
            ]);
        }

        return view('song-request/request', ['cafe' => $cafe]);
    }

    /**
     * Search songs via Spotify API
     */
    public function searchSongs()
    {
        $query = $this->request->getGet('q');
        $limit = (int) ($this->request->getGet('limit') ?? 10);
        $limit = max(1, min($limit, 10));

        if (!$query || strlen(trim($query)) < 2) {
            return $this->response->setStatusCode(400)->setJSON([
                'success' => false,
                'message' => 'Query minimal 2 karakter'
            ]);
        }

        $query = trim($query);

        $spotifyService = new SpotifyService();

        if ($spotifyService->isConfigured()) {
            $spotifyResult = $spotifyService->searchSongs($query, $limit);

            if ($spotifyResult['success'] && !empty($spotifyResult['data'])) {
                // Sync & return langsung dari Spotify
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
                        log_message('error', 'Sync lagu gagal: ' . $e->getMessage());
                        $song['local_id'] = null;
                    }
                    $results[] = $song;
                }

                return $this->response->setJSON([
                    'success' => true,
                    'data'    => $results,
                    'total'   => count($results),
                ]);
            }
        }

        // Fallback ke database lokal
        $songs = $this->songModel->searchSongs($query, $limit);

        return $this->response->setJSON([
            'success' => true,
            'data'    => $songs,
            'total'   => count($songs),
        ]);
    }

    /**
     * Create song request
     */
    public function createRequest()
    {
        $queueType = $this->request->getVar('queue_type');
        $nominalRule = ($queueType === 'fifo') ? 'permit_empty|integer' : 'required|integer|greater_than[0]';

        $rules = [
            'cafe_id' => 'required|integer',
            'song_id' => 'required|integer',
            'nominal' => $nominalRule,
            'queue_type' => 'required|in_list[priority,fifo]',
            'guest_name' => 'permit_empty|string|max_length[100]',
            'user_id' => 'permit_empty|integer',
        ];

        if (!$this->validate($rules)) {
            return $this->response->setStatusCode(422)->setJSON([
                'success' => false,
                'errors' => $this->validator->getErrors()
            ]);
        }

        // Verify cafe exists
        $cafeId = $this->request->getVar('cafe_id');
        $cafe = $this->cafeModel->find($cafeId);
        if (!$cafe || $cafe['status'] !== 'approved') {
            return $this->response->setStatusCode(404)->setJSON([
                'success' => false,
                'message' => 'Cafe tidak valid'
            ]);
        }

        // Verify song exists
        $songId = $this->request->getVar('song_id');
        $song = $this->songModel->getSongSafely($songId);
        if (!$song) {
            return $this->response->setStatusCode(404)->setJSON([
                'success' => false,
                'message' => 'Lagu tidak ditemukan'
            ]);
        }

        $data = [
            'cafe_id' => $cafeId,
            'song_id' => $songId,
            'nominal' => ($queueType === 'fifo') ? 0 : (int)$this->request->getVar('nominal'),
            'queue_type' => $queueType,
            'guest_name' => $this->request->getVar('guest_name') ?: 'Anonim',
            'user_id' => $this->request->getVar('user_id') ?: session()->get('user_id'),
        ];

        $requestId = $this->requestModel->createRequest($data);

        if ($requestId) {
            $redirectUrl = ($queueType === 'fifo')
                ? "/song-request/request?cafe_id=$cafeId&success=1"
                : "/payment/checkout?request_id=$requestId";

            return $this->response->setJSON([
                'success' => true,
                'message' => 'Request created',
                'request_id' => $requestId,
                'redirect' => $redirectUrl
            ]);
        }

        return $this->response->setStatusCode(400)->setJSON([
            'success' => false,
            'message' => 'Request creation failed'
        ]);
    }

    /**
     * Get queue untuk cafe
     */
    public function getQueue(int $cafeId)
    {
        $cafe = $this->cafeModel->find($cafeId);
        if (!$cafe) {
            return $this->response->setStatusCode(404)->setJSON([
                'success' => false,
                'message' => 'Cafe tidak ditemukan'
            ]);
        }

        $queue = $this->queueService->getFullQueue($cafeId);

        return $this->response->setJSON([
            'success' => true,
            'data' => $queue
        ]);
    }

    /**
     * Get request history untuk user
     */
    public function getUserHistory()
    {
        if (!session()->has('user_id')) {
            return redirect()->to('/auth/login');
        }

        $userId = session()->get('user_id');
        $requests = $this->requestModel->getUserRequests($userId);

        return view('song-request/history', ['requests' => $requests]);
    }

    /**
     * Get request details
     */
    public function getRequestDetails(int $requestId)
    {
        $request = $this->requestModel->select('song_requests.*, songs.title, songs.artist, songs.duration, cafes.nama_kafe')
            ->join('songs', 'song_requests.song_id = songs.id', 'left')
            ->join('cafes', 'song_requests.cafe_id = cafes.id', 'left')
            ->find($requestId);

        if (!$request) {
            return $this->response->setStatusCode(404)->setJSON([
                'success' => false,
                'message' => 'Request tidak ditemukan'
            ]);
        }

        return $this->response->setJSON([
            'success' => true,
            'data' => $request
        ]);
    }

    /**
     * Cancel request
     */
    public function cancelRequest(int $requestId)
    {
        $request = $this->requestModel->find($requestId);

        if (!$request) {
            return $this->response->setStatusCode(404)->setJSON([
                'success' => false,
                'message' => 'Request tidak ditemukan'
            ]);
        }

        // Only can cancel if status is waiting
        if ($request['status'] !== 'waiting') {
            return $this->response->setStatusCode(400)->setJSON([
                'success' => false,
                'message' => 'Hanya waiting request yang bisa dibatalkan'
            ]);
        }

        if ($this->requestModel->cancelRequest($requestId)) {
            return $this->response->setJSON([
                'success' => true,
                'message' => 'Request cancelled'
            ]);
        }

        return $this->response->setStatusCode(400)->setJSON([
            'success' => false,
            'message' => 'Cancel failed'
        ]);
    }

    /**
     * WEBHOOK: Real-time queue update
     * Digunakan untuk real-time update antrian lagu
     * Bisa dipanggil dari admin panel untuk melihat antrian terbaru
     *
     * @param int $cafeId
     * @return void
     */
    public function queueWebhook(int $cafeId)
    {
        // Verify cafe exists
        $cafe = $this->cafeModel->find($cafeId);
        if (!$cafe) {
            return $this->response->setStatusCode(404)->setJSON([
                'success' => false,
                'message' => 'Cafe tidak ditemukan'
            ]);
        }

        // Get full queue dengan priority dan fifo
        $queue = $this->queueService->getFullQueue($cafeId);

        // Add now_playing
        $currentlyPlaying = $this->requestModel->getCurrentlyPlaying($cafeId);

        // Balance & stats
        $balanceModel = new \App\Models\CafeBalanceModel();
        $balanceData = $balanceModel->getBalance($cafeId);
        $availableBalance = $balanceData ? (int)$balanceData['available_balance'] : 0;

        $paymentService = new \App\Services\PaymentService();
        $dailyIncome = $paymentService->getDailyIncome($cafeId);

        $todayRequests = $this->queueService->getTodayRequestCount($cafeId);
        $totalWaiting = count($queue['priority'] ?? []) + count($queue['fifo'] ?? []);

        // Add metadata
        $response = [
            'success' => true,
            'cafe_id' => $cafeId,
            'cafe_name' => $cafe['nama_kafe'],
            'timestamp' => date('Y-m-d H:i:s'),
            'queue' => $queue,
            'currently_playing' => $currentlyPlaying,
            'stats' => [
                'available_balance' => $availableBalance,
                'daily_income' => $dailyIncome,
                'today_requests' => $todayRequests,
                'total_waiting' => $totalWaiting,
                'priority_count' => count($queue['priority'] ?? []),
                'fifo_count' => count($queue['fifo'] ?? []),
            ]
        ];

        // Set headers
        $this->response->setHeader('Content-Type', 'application/json');
        $this->response->setHeader('Cache-Control', 'no-cache, must-revalidate');
        $this->response->setHeader('X-Accel-Buffering', 'no');

        return $this->response->setJSON($response);
    }

    /**
     * Get next song to play berdasarkan queue algorithm
     * Digunakan oleh admin untuk mendapatkan lagu berikutnya yang harus diputar
     *
     * @param int $cafeId
     * @return void
     */
    public function getNextToPlay(int $cafeId)
    {
        // Verify cafe exists
        $cafe = $this->cafeModel->find($cafeId);
        if (!$cafe) {
            return $this->response->setStatusCode(404)->setJSON([
                'success' => false,
                'message' => 'Cafe tidak ditemukan'
            ]);
        }

        // Get next song from queue
        $nextSong = $this->queueService->getNextSongToPlay($cafeId);

        if (!$nextSong) {
            return $this->response->setStatusCode(404)->setJSON([
                'success' => true,
                'message' => 'Tidak ada lagu dalam antrian',
                'data' => null
            ]);
        }

        return $this->response->setJSON([
            'success' => true,
            'message' => 'Next song',
            'data' => $nextSong
        ]);
    }

    /**
     * Mark song as playing
     * Ketika admin memulai memutar lagu
     *
     * @param int $requestId
     * @return void
     */
    public function markAsPlaying(int $requestId)
    {
        $request = $this->requestModel->find($requestId);

        if (!$request) {
            return $this->response->setStatusCode(404)->setJSON([
                'success' => false,
                'message' => 'Request tidak ditemukan'
            ]);
        }

        if ($request['status'] !== 'waiting') {
            return $this->response->setStatusCode(400)->setJSON([
                'success' => false,
                'message' => 'Hanya request dengan status waiting yang bisa diputar'
            ]);
        }

        if ($this->requestModel->updateStatus($requestId, 'playing')) {
            return $this->response->setJSON([
                'success' => true,
                'message' => 'Lagu sedang diputar'
            ]);
        }

        return $this->response->setStatusCode(400)->setJSON([
            'success' => false,
            'message' => 'Gagal mengupdate status'
        ]);
    }

    /**
     * Mark song as done
     * Ketika lagu selesai diputar
     *
     * @param int $requestId
     * @return void
     */
    public function markAsDone(int $requestId)
    {
        $request = $this->requestModel->find($requestId);

        if (!$request) {
            return $this->response->setStatusCode(404)->setJSON([
                'success' => false,
                'message' => 'Request tidak ditemukan'
            ]);
        }

        if ($request['status'] !== 'playing') {
            return $this->response->setStatusCode(400)->setJSON([
                'success' => false,
                'message' => 'Hanya request dengan status playing yang bisa diselesaikan'
            ]);
        }

        if ($this->requestModel->updateStatus($requestId, 'done')) {
            return $this->response->setJSON([
                'success' => true,
                'message' => 'Lagu berhasil diselesaikan'
            ]);
        }

        return $this->response->setStatusCode(400)->setJSON([
            'success' => false,
            'message' => 'Gagal mengupdate status'
        ]);
    }
}

