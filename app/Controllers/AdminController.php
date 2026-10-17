<?php

namespace App\Controllers;

use App\Models\CafeModel;
use App\Services\QueueService;
use App\Models\SongRequestModel;
use App\Models\PaymentModel;
use App\Models\CafeBalanceModel;
use App\Services\WithdrawalService;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Psr\Log\LoggerInterface;

class AdminController extends BaseController
{
    protected $cafeModel;
    protected $queueService;
    protected $requestModel;
    protected $paymentModel;
    protected $balanceModel;
    protected $withdrawalService;

    public function initController(RequestInterface $request, ResponseInterface $response, LoggerInterface $logger)
    {
        parent::initController($request, $response, $logger);
        $this->cafeModel         = new CafeModel();
        $this->queueService      = new QueueService();
        $this->requestModel      = new SongRequestModel();
        $this->paymentModel      = new PaymentModel();
        $this->balanceModel      = new CafeBalanceModel();
        $this->withdrawalService = new WithdrawalService();
    }

    // =========================================================================
    // HELPER: Ambil cafe milik admin yang sedang login
    // =========================================================================
    private function getAdminCafe(): ?array
    {
        $adminId = session()->get('user_id');
        $cafe    = $this->cafeModel->where('admin_id', $adminId)->first();
        return $cafe ?: null;
    }

    private function requireAdmin(): bool
    {
        return session()->get('user_role') === 'admin';
    }

    // =========================================================================
    // DASHBOARD
    // =========================================================================

    /**
     * Admin cafe dashboard
     */
    public function dashboard()
    {
        if (!$this->requireAdmin()) {
            return redirect()->to('/auth/login');
        }

        $adminId = session()->get('user_id');
        $cafe    = $this->cafeModel->where('admin_id', $adminId)->first();

        if (!$cafe) {
            return view('admin/no-cafe', ['status' => 'no_registered']);
        }

        if ($cafe['status'] === 'pending') {
            return view('admin/no-cafe', ['status' => 'pending', 'cafe' => $cafe]);
        }

        if ($cafe['status'] === 'rejected') {
            return view('admin/no-cafe', ['status' => 'rejected', 'cafe' => $cafe]);
        }

        $balance    = $this->balanceModel->getBalance($cafe['id']);
        $todayRequests = $this->queueService->getTodayRequestCount($cafe['id']);

        $paymentService = new \App\Services\PaymentService();
        $dailyIncome    = $paymentService->getDailyIncome($cafe['id']);

        $queueStats = $this->queueService->getQueueStats($cafe['id']);

        // Finance summary untuk header dashboard
        $financeSummary = $this->withdrawalService->getCafeFinanceSummary($cafe['id']);

        $data = [
            'cafe'           => $cafe,
            'balance'        => $balance,
            'today_requests' => $todayRequests,
            'daily_income'   => $dailyIncome,
            'queue_stats'    => $queueStats,
            'finance_summary' => $financeSummary,
        ];

        return view('admin/dashboard', $data);
    }

    // =========================================================================
    // QUEUE MANAGEMENT (tidak berubah)
    // =========================================================================

    public function getQueue()
    {
        if (!$this->requireAdmin()) {
            return redirect()->to('/auth/login');
        }

        $cafeId = $this->request->getGet('cafe_id');
        $cafe   = $this->cafeModel->getCafeByAdmin($cafeId, session()->get('user_id'));

        if (!$cafe) {
            return $this->response->setStatusCode(403)->setJSON(['success' => false, 'message' => 'Unauthorized']);
        }

        $queue = $this->queueService->getFullQueue($cafeId);
        return $this->response->setJSON(['success' => true, 'data' => $queue]);
    }

    public function playNextSong()
    {
        if (!$this->requireAdmin()) {
            return redirect()->to('/auth/login');
        }

        $cafeId = $this->request->getPost('cafe_id');
        $cafe   = $this->cafeModel->getCafeByAdmin($cafeId, session()->get('user_id'));

        if (!$cafe) {
            return $this->response->setStatusCode(403)->setJSON(['success' => false, 'message' => 'Unauthorized']);
        }

        $songRequestModel = new \App\Models\SongRequestModel();
        $currentlyPlaying = $songRequestModel->getCurrentlyPlaying($cafeId);
        if ($currentlyPlaying) {
            $songRequestModel->markAsDone($currentlyPlaying['id']);
            // Notifikasi WA & Email: lagu sebelumnya selesai
            try {
                $wa = service('whatsappNotification');
                $wa->onSongFinished((int)$currentlyPlaying['id']);
            } catch (\Throwable $waEx) {
                log_message('error', 'WA onSongFinished error (non-critical): ' . $waEx->getMessage());
            }
            try {
                $email = service('notificationEmail');
                $email->onSongStatusChanged((int)$currentlyPlaying['id'], 'done');
            } catch (\Throwable $emailEx) {
                log_message('error', 'Email onSongStatusChanged done error: ' . $emailEx->getMessage());
            }
        }

        $nextSong = $this->queueService->getNextSong($cafeId);

        if (!$nextSong) {
            return $this->response->setJSON(['success' => false, 'message' => 'Tidak ada lagu dalam antrian']);
        }

        if ($this->queueService->playSong($nextSong['id'])) {
            // Notifikasi WA & Email: lagu baru mulai diputar
            try {
                $wa = service('whatsappNotification');
                $wa->onSongStarted((int)$nextSong['id']);
            } catch (\Throwable $waEx) {
                log_message('error', 'WA onSongStarted error (non-critical): ' . $waEx->getMessage());
            }
            try {
                $email = service('notificationEmail');
                $email->onSongStatusChanged((int)$nextSong['id'], 'playing');
            } catch (\Throwable $emailEx) {
                log_message('error', 'Email onSongStatusChanged playing error: ' . $emailEx->getMessage());
            }

            $spotifyToken        = \App\Controllers\SpotifyAuthController::getValidAccessToken();
            $spotifyPlaybackStatus = null;
            $spotifyPlaybackMsg    = '';

            if ($spotifyToken && !empty($nextSong['api_song_id'])) {
                $spotifyService  = new \App\Services\SpotifyService();
                $playbackResult  = $spotifyService->playTrackForUser($spotifyToken, $nextSong['api_song_id']);

                if ($playbackResult['status'] === 204) {
                    $spotifyPlaybackStatus = 'success';
                    $spotifyPlaybackMsg    = 'Espresso Musik mengalir! Lagu diputar langsung di Spotify Anda.';
                } elseif ($playbackResult['status'] === 404) {
                    $spotifyPlaybackStatus = 'no_active_device';
                    $spotifyPlaybackMsg    = 'Belum ada cangkir aktif! Buka aplikasi Spotify di HP/PC Anda dan putar sembarang lagu terlebih dahulu.';
                } else {
                    $spotifyPlaybackStatus = 'error';
                    $spotifyPlaybackMsg    = 'Sedikit gangguan pada mesin kopi (HTTP ' . $playbackResult['status'] . ').';
                }
            }

            return $this->response->setJSON([
                'success'          => true,
                'message'          => 'Lagu berikutnya mulai diseduh!',
                'song'             => $nextSong,
                'spotify_playback' => [
                    'status'  => $spotifyPlaybackStatus,
                    'message' => $spotifyPlaybackMsg,
                ],
            ]);
        }

        return $this->response->setStatusCode(400)->setJSON(['success' => false, 'message' => 'Failed to play song']);
    }

    public function markSongDone()
    {
        if (!$this->requireAdmin()) {
            return redirect()->to('/auth/login');
        }

        $cafeId    = $this->request->getPost('cafe_id');
        $requestId = $this->request->getPost('request_id');
        $cafe      = $this->cafeModel->getCafeByAdmin($cafeId, session()->get('user_id'));

        if (!$cafe) {
            return $this->response->setStatusCode(403)->setJSON(['success' => false, 'message' => 'Unauthorized']);
        }

        $song = $this->queueService->nextSong($cafeId, $requestId);

        // Notifikasi WA & Email: lagu selesai diputar
        try {
            $wa = service('whatsappNotification');
            $wa->onSongFinished((int)$requestId);
        } catch (\Throwable $waEx) {
            log_message('error', 'WA onSongFinished error (non-critical) markSongDone: ' . $waEx->getMessage());
        }
        try {
            $email = service('notificationEmail');
            $email->onSongStatusChanged((int)$requestId, 'done');
        } catch (\Throwable $emailEx) {
            log_message('error', 'Email onSongStatusChanged done markSongDone error: ' . $emailEx->getMessage());
        }

        return $this->response->setJSON(['success' => true, 'message' => 'Song marked as done', 'next_song' => $song]);
    }

    // =========================================================================
    // FINANCE — Data Endpoints
    // =========================================================================

    /**
     * GET /admin/finance/summary — ringkasan keuangan untuk panel finance.
     */
    public function getFinanceSummary()
    {
        if (!$this->requireAdmin()) {
            return $this->response->setStatusCode(403)->setJSON(['success' => false, 'message' => 'Unauthorized']);
        }

        $cafe = $this->getAdminCafe();
        if (!$cafe) {
            return $this->response->setStatusCode(404)->setJSON(['success' => false, 'message' => 'Cafe not found']);
        }

        $summary = $this->withdrawalService->getCafeFinanceSummary($cafe['id']);
        return $this->response->setJSON(['success' => true, 'data' => $summary]);
    }

    /**
     * GET /admin/dashboard-stats — data untuk dua seksi dashboard (10 transaksi + 10 request).
     */
    public function getDashboardStats()
    {
        if (!$this->requireAdmin()) {
            return $this->response->setStatusCode(403)->setJSON(['success' => false, 'message' => 'Unauthorized']);
        }

        $cafe = $this->getAdminCafe();
        if (!$cafe) {
            return $this->response->setStatusCode(404)->setJSON(['success' => false, 'message' => 'Cafe not found']);
        }

        $cafeId = (int)$cafe['id'];

        // Maksimal 10 transaksi keuangan terbaru
        $recentPayments = $this->paymentModel->getCafePayments($cafeId, 10, 0);

        // Maksimal 10 request lagu terbaru
        $recentRequests = $this->requestModel->getCafeRequests($cafeId, 10, 0);

        return $this->response->setJSON([
            'success'         => true,
            'recent_payments' => $recentPayments,
            'recent_requests' => $recentRequests,
        ]);
    }

    /**
     * GET /admin/payments — daftar transaksi keuangan dengan pagination.
     */
    public function getPayments()
    {
        if (!$this->requireAdmin()) {
            return $this->response->setStatusCode(403)->setJSON(['success' => false, 'message' => 'Unauthorized']);
        }

        $cafe = $this->getAdminCafe();
        if (!$cafe) {
            return $this->response->setStatusCode(404)->setJSON(['success' => false, 'message' => 'Cafe not found']);
        }

        $page   = max(1, (int)($this->request->getGet('page') ?? 1));
        $limit  = 20;
        $offset = ($page - 1) * $limit;

        // Filter opsional
        $status    = $this->request->getGet('status') ?? '';
        $dateFrom  = $this->request->getGet('date_from') ?? '';
        $dateTo    = $this->request->getGet('date_to') ?? '';

        $payments = $this->paymentModel->getCafePayments($cafe['id'], $limit, $offset, $status, $dateFrom, $dateTo);
        $total    = $this->paymentModel->countCafePayments($cafe['id'], $status, $dateFrom, $dateTo);

        return $this->response->setJSON([
            'success'     => true,
            'data'        => $payments,
            'page'        => $page,
            'limit'       => $limit,
            'total'       => $total,
            'total_pages' => (int)ceil($total / $limit),
        ]);
    }

    /**
     * GET /admin/requests — daftar request lagu dengan pagination.
     */
    public function getRequestHistory()
    {
        if (!$this->requireAdmin()) {
            return $this->response->setStatusCode(403)->setJSON(['success' => false, 'message' => 'Unauthorized']);
        }

        $cafe = $this->getAdminCafe();
        if (!$cafe) {
            return $this->response->setStatusCode(404)->setJSON(['success' => false, 'message' => 'Cafe not found']);
        }

        $page   = max(1, (int)($this->request->getGet('page') ?? 1));
        $limit  = 20;
        $offset = ($page - 1) * $limit;

        $status   = $this->request->getGet('status') ?? '';
        $dateFrom = $this->request->getGet('date_from') ?? '';
        $dateTo   = $this->request->getGet('date_to') ?? '';
        $search   = $this->request->getGet('search') ?? '';

        $requests = $this->requestModel->getCafeRequestsFiltered($cafe['id'], $limit, $offset, $status, $dateFrom, $dateTo, $search);
        $total    = $this->requestModel->countCafeRequests($cafe['id'], $status, $dateFrom, $dateTo, $search);

        return $this->response->setJSON([
            'success'     => true,
            'data'        => $requests,
            'page'        => $page,
            'limit'       => $limit,
            'total'       => $total,
            'total_pages' => (int)ceil($total / $limit),
        ]);
    }

    /**
     * GET /admin/withdrawals — riwayat withdrawal dengan pagination.
     */
    public function getWithdrawalHistory()
    {
        if (!$this->requireAdmin()) {
            return $this->response->setStatusCode(403)->setJSON(['success' => false, 'message' => 'Unauthorized']);
        }

        $cafe = $this->getAdminCafe();
        if (!$cafe) {
            return $this->response->setStatusCode(404)->setJSON(['success' => false, 'message' => 'Cafe not found']);
        }

        $page   = max(1, (int)($this->request->getGet('page') ?? 1));
        $limit  = 20;
        $offset = ($page - 1) * $limit;

        $withdrawals = $this->withdrawalService->getCafeWithdrawalHistory($cafe['id'], $limit, $offset);

        return $this->response->setJSON([
            'success' => true,
            'data'    => array_map(fn($w) => $this->maskAccountNumber($w), $withdrawals),
            'page'    => $page,
            'limit'   => $limit,
        ]);
    }

    /**
     * POST /admin/withdrawal/request — ajukan withdrawal baru.
     */
    public function createWithdrawalRequest()
    {
        if (!$this->requireAdmin()) {
            return $this->response->setStatusCode(403)->setJSON(['success' => false, 'message' => 'Unauthorized']);
        }

        $cafe = $this->getAdminCafe();
        if (!$cafe) {
            return $this->response->setStatusCode(404)->setJSON(['success' => false, 'message' => 'Cafe not found']);
        }

        $rules = [
            'amount'           => 'required|integer|greater_than[0]',
            'destination_type' => 'required|in_list[bank_transfer,e_wallet]',
            'bank_name'        => 'required|string|max_length[100]',
            'account_number'   => 'required|string|max_length[50]',
            'account_holder'   => 'required|string|max_length[100]',
            'notes'            => 'permit_empty|string|max_length[500]',
        ];

        if (!$this->validate($rules)) {
            return $this->response->setStatusCode(422)->setJSON([
                'success' => false,
                'errors'  => $this->validator->getErrors(),
            ]);
        }

        $payload = [
            'amount'           => (int)$this->request->getPost('amount'),
            'destination_type' => $this->request->getPost('destination_type'),
            'bank_name'        => $this->request->getPost('bank_name'),
            'account_number'   => $this->request->getPost('account_number'),
            'account_holder'   => $this->request->getPost('account_holder'),
            'notes'            => $this->request->getPost('notes') ?? '',
        ];

        $result = $this->withdrawalService->requestWithdrawal(
            (int)$cafe['id'],
            $payload,
            (int)session()->get('user_id')
        );

        if ($result['success']) {
            return $this->response->setJSON($result);
        }

        return $this->response->setStatusCode(400)->setJSON($result);
    }

    // =========================================================================
    // HELPER: Masking nomor rekening
    // =========================================================================
    private function maskAccountNumber(array $withdrawal): array
    {
        if (!empty($withdrawal['account_number']) && strlen($withdrawal['account_number']) > 4) {
            $len = strlen($withdrawal['account_number']);
            $withdrawal['account_number_masked'] = str_repeat('*', $len - 4) . substr($withdrawal['account_number'], -4);
        } else {
            $withdrawal['account_number_masked'] = $withdrawal['account_number'] ?? '';
        }
        // Hapus nomor rekening lengkap dari response listing
        unset($withdrawal['account_number']);
        return $withdrawal;
    }
}
