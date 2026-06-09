<?php

namespace App\Controllers;

use App\Models\CafeModel;
use App\Services\QueueService;
use App\Models\PaymentModel;
use App\Models\CafeBalanceModel;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Psr\Log\LoggerInterface;

class AdminController extends BaseController
{
    protected $cafeModel;
    protected $queueService;
    protected $paymentModel;
    protected $balanceModel;

    public function initController(RequestInterface $request, ResponseInterface $response, LoggerInterface $logger)
    {
        parent::initController($request, $response, $logger);
        $this->cafeModel = new CafeModel();
        $this->queueService = new QueueService();
        $this->paymentModel = new PaymentModel();
        $this->balanceModel = new CafeBalanceModel();
    }

    /**
     * Admin cafe dashboard
     */
    public function dashboard()
    {
        if (session()->get('user_role') !== 'admin') {
            return redirect()->to('/auth/login');
        }

        $adminId = session()->get('user_id');

        // Get cafe for this admin
        $cafe = $this->cafeModel->where('admin_id', $adminId)->first();

        if (!$cafe) {
            return view('admin/no-cafe', ['status' => 'no_registered']);
        }

        if ($cafe['status'] === 'pending') {
            return view('admin/no-cafe', ['status' => 'pending', 'cafe' => $cafe]);
        }

        if ($cafe['status'] === 'rejected') {
            return view('admin/no-cafe', ['status' => 'rejected', 'cafe' => $cafe]);
        }

        // Get stats
        $balance = $this->balanceModel->getBalance($cafe['id']);
        $todayRequests = $this->queueService->getTodayRequestCount($cafe['id']);
        
        $paymentService = new \App\Services\PaymentService();
        $dailyIncome = $paymentService->getDailyIncome($cafe['id']);
        
        $queueStats = $this->queueService->getQueueStats($cafe['id']);

        $data = [
            'cafe' => $cafe,
            'balance' => $balance,
            'today_requests' => $todayRequests,
            'daily_income' => $dailyIncome,
            'queue_stats' => $queueStats,
        ];

        return view('admin/dashboard', $data);
    }

    /**
     * Get cafe queue
     */
    public function getQueue()
    {
        if (session()->get('user_role') !== 'admin') {
            return redirect()->to('/auth/login');
        }

        $cafeId = $this->request->getGet('cafe_id');

        // Verify ownership
        $cafe = $this->cafeModel->getCafeByAdmin($cafeId, session()->get('user_id'));

        if (!$cafe) {
            return $this->response->setStatusCode(403)->setJSON([
                'success' => false,
                'message' => 'Unauthorized'
            ]);
        }

        $queue = $this->queueService->getFullQueue($cafeId);

        return $this->response->setJSON([
            'success' => true,
            'data' => $queue
        ]);
    }

    /**
     * Play next song
     */
    public function playNextSong()
    {
        if (session()->get('user_role') !== 'admin') {
            return redirect()->to('/auth/login');
        }

        $cafeId = $this->request->getPost('cafe_id');

        // Verify ownership
        $cafe = $this->cafeModel->getCafeByAdmin($cafeId, session()->get('user_id'));

        if (!$cafe) {
            return $this->response->setStatusCode(403)->setJSON([
                'success' => false,
                'message' => 'Unauthorized'
            ]);
        }

        // Automatically mark currently playing song as done
        $songRequestModel = new \App\Models\SongRequestModel();
        $currentlyPlaying = $songRequestModel->getCurrentlyPlaying($cafeId);
        if ($currentlyPlaying) {
            $songRequestModel->markAsDone($currentlyPlaying['id']);
        }

        $nextSong = $this->queueService->getNextSong($cafeId);

        if (!$nextSong) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Tidak ada lagu dalam antrian'
            ]);
        }

        // Play song
        if ($this->queueService->playSong($nextSong['id'])) {
            $spotifyToken = \App\Controllers\SpotifyAuthController::getValidAccessToken();
            $spotifyPlaybackStatus = null;
            $spotifyPlaybackMsg = '';

            if ($spotifyToken && !empty($nextSong['api_song_id'])) {
                $spotifyService = new \App\Services\SpotifyService();
                $playbackResult = $spotifyService->playTrackForUser($spotifyToken, $nextSong['api_song_id']);
                
                if ($playbackResult['status'] === 204) {
                    $spotifyPlaybackStatus = 'success';
                    $spotifyPlaybackMsg = 'Espresso Musik mengalir! Lagu diputar langsung di Spotify Anda.';
                } else if ($playbackResult['status'] === 404) {
                    $spotifyPlaybackStatus = 'no_active_device';
                    $spotifyPlaybackMsg = 'Belum ada cangkir aktif! Buka aplikasi Spotify di HP/PC Anda dan putar sembarang lagu terlebih dahulu.';
                } else {
                    $spotifyPlaybackStatus = 'error';
                    $spotifyPlaybackMsg = 'Sedikit gangguan pada mesin kopi (HTTP ' . $playbackResult['status'] . ').';
                }
            }

            return $this->response->setJSON([
                'success' => true,
                'message' => 'Lagu berikutnya mulai diseduh!',
                'song' => $nextSong,
                'spotify_playback' => [
                    'status' => $spotifyPlaybackStatus,
                    'message' => $spotifyPlaybackMsg
                ]
            ]);
        }

        return $this->response->setStatusCode(400)->setJSON([
            'success' => false,
            'message' => 'Failed to play song'
        ]);
    }

    /**
     * Mark song as done
     */
    public function markSongDone()
    {
        if (session()->get('user_role') !== 'admin') {
            return redirect()->to('/auth/login');
        }

        $cafeId = $this->request->getPost('cafe_id');
        $requestId = $this->request->getPost('request_id');

        // Verify ownership
        $cafe = $this->cafeModel->getCafeByAdmin($cafeId, session()->get('user_id'));

        if (!$cafe) {
            return $this->response->setStatusCode(403)->setJSON([
                'success' => false,
                'message' => 'Unauthorized'
            ]);
        }

        $song = $this->queueService->nextSong($cafeId, $requestId);

        return $this->response->setJSON([
            'success' => true,
            'message' => 'Song marked as done',
            'next_song' => $song
        ]);
    }

    /**
     * Get withdrawal history
     */
    public function getWithdrawalHistory()
    {
        if (session()->get('user_role') !== 'admin') {
            return redirect()->to('/auth/login');
        }

        $adminId = session()->get('user_id');
        $cafe = $this->cafeModel->where('admin_id', $adminId)->first();

        if (!$cafe) {
            return $this->response->setStatusCode(404)->setJSON([
                'success' => false,
                'message' => 'Cafe not found'
            ]);
        }

        $withdrawalModel = new \App\Models\WithdrawalModel();
        $withdrawals = $withdrawalModel->getCafeWithdrawals($cafe['id']);

        return $this->response->setJSON([
            'success' => true,
            'data' => $withdrawals
        ]);
    }

    /**
     * Get cafe payments
     */
    public function getPayments()
    {
        if (session()->get('user_role') !== 'admin') {
            return redirect()->to('/auth/login');
        }

        $adminId = session()->get('user_id');
        $cafe = $this->cafeModel->where('admin_id', $adminId)->first();

        if (!$cafe) {
            return $this->response->setStatusCode(404)->setJSON([
                'success' => false,
                'message' => 'Cafe not found'
            ]);
        }

        $page = $this->request->getGet('page') ?? 1;
        $limit = 20;
        $offset = ($page - 1) * $limit;

        $payments = $this->paymentModel->getCafePayments($cafe['id'], $limit, $offset);

        return $this->response->setJSON([
            'success' => true,
            'data' => $payments
        ]);
    }
}

