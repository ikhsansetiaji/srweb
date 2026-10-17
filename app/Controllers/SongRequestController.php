<?php

namespace App\Controllers;

use App\Models\SongRequestModel;
use App\Models\SongModel;
use App\Models\CafeModel;
use App\Models\UserModel;
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
    protected $userModel;
    protected $queueService;
    protected $notificationEmailService;

    public function initController(RequestInterface $request, ResponseInterface $response, LoggerInterface $logger)
    {
        parent::initController($request, $response, $logger);
        $this->requestModel = new SongRequestModel();
        $this->songModel = new SongModel();
        $this->cafeModel = new CafeModel();
        $this->userModel = new UserModel();
        $this->queueService = new QueueService();
        $this->notificationEmailService = service('notificationEmail');
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

        $queueStats = $this->queueService->getQueueStats($cafeId);
        $currentlyPlaying = $this->requestModel->getCurrentlyPlaying($cafeId);
        $userId = session()->get('user_id');
        $currentUser = $userId ? $this->userModel->find($userId) : null;

        return view('song-request/request', [
            'cafe'              => $cafe,
            'queue_stats'       => $queueStats,
            'currently_playing' => $currentlyPlaying,
            'current_user'      => $currentUser,
        ]);
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
            'cafe_id'    => 'required|integer',
            'song_id'    => 'required|integer',
            'nominal'    => $nominalRule,
            'queue_type' => 'required|in_list[priority,fifo]',
            'guest_name' => 'permit_empty|string|max_length[100]',
            'user_id'    => 'permit_empty|integer',
            'phone_wa'   => 'permit_empty|string|max_length[20]',
            'wa_consent' => 'permit_empty',
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

        $userId = $this->request->getVar('user_id') ?: session()->get('user_id');
        $phoneWa = $this->request->getVar('phone_wa');
        $waConsentRaw = $this->request->getVar('wa_consent');

        // Jika pengunjung sudah login dan nomor WA tidak diisi form, ambil otomatis dari profil user
        if (!empty($userId)) {
            $user = $this->userModel->find((int)$userId);
            if ($user) {
                if (empty($phoneWa) && !empty($user['phone_wa'])) {
                    $phoneWa = $user['phone_wa'];
                } elseif (empty($phoneWa) && !empty($user['phone'])) {
                    $phoneWa = $user['phone'];
                }
                if ($waConsentRaw === null && isset($user['wa_notif_enabled'])) {
                    $waConsentRaw = $user['wa_notif_enabled'];
                }
            }
        }

        $fonnteService = new \App\Services\FonnteService();
        $normalizedPhoneWa = !empty($phoneWa) ? ($fonnteService->normalizePhone($phoneWa) ?: $phoneWa) : null;

        $data = [
            'cafe_id'    => $cafeId,
            'song_id'    => $songId,
            'nominal'    => ($queueType === 'fifo') ? 0 : (int)$this->request->getVar('nominal'),
            'queue_type' => $queueType,
            'guest_name' => $this->request->getVar('guest_name') ?: 'Anonim',
            'user_id'    => $userId,
            // WhatsApp notification fields (opsional, auto-fill jika user login)
            'phone_wa'   => $normalizedPhoneWa,
            'wa_consent' => \App\Services\WhatsAppNotificationService::parseBoolean($waConsentRaw ?? false),
        ];

        $requestId = $this->requestModel->createRequest($data);

        if ($requestId) {
            if (!empty($data['user_id'])) {
                $user = $this->userModel->find((int) $data['user_id']);
                if ($user && !empty($user['email'])) {
                    $this->notificationEmailService->sendRequestCreatedEmail((int) $requestId);
                }
            }

            // Notifikasi WA: FIFO request langsung masuk antrean (waiting)
            // Priority: notifikasi dikirim dari PaymentService setelah payment confirmed
            if ($queueType === 'fifo') {
                try {
                    /** @var \App\Services\WhatsAppNotificationService $wa */
                    $wa = service('whatsappNotification');
                    $wa->onRequestQueued((int)$requestId);
                } catch (\Throwable $waEx) {
                    log_message('error', 'WA notification error (non-critical) createRequest: ' . $waEx->getMessage());
                }
            }

            $redirectUrl = ($queueType === 'fifo')
                ? "/song-request/request?cafe_id=$cafeId&success=1"
                : "/payment/checkout?request_id=$requestId";

            return $this->response->setJSON([
                'success'    => true,
                'message'    => 'Request created',
                'request_id' => $requestId,
                'redirect'   => $redirectUrl
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
            try {
                $email = service('notificationEmail');
                $email->onSongStatusChanged((int)$requestId, 'cancelled');
            } catch (\Throwable $emailEx) {
                log_message('error', 'Email cancelRequest error (non-critical): ' . $emailEx->getMessage());
            }

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
                'pending_payment_count' => count($queue['pending_payments'] ?? []),
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
            // Notifikasi WA: lagu mulai diputar
            try {
                /** @var \App\Services\WhatsAppNotificationService $wa */
                $wa = service('whatsappNotification');
                $wa->onSongStarted($requestId);
            } catch (\Throwable $waEx) {
                log_message('error', 'WA notification error (non-critical) markAsPlaying: ' . $waEx->getMessage());
            }

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
            // Notifikasi WA: lagu selesai diputar
            try {
                /** @var \App\Services\WhatsAppNotificationService $wa */
                $wa = service('whatsappNotification');
                $wa->onSongFinished($requestId);
            } catch (\Throwable $waEx) {
                log_message('error', 'WA notification error (non-critical) markAsDone: ' . $waEx->getMessage());
            }

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

