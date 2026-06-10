<?php

namespace App\Controllers;

use App\Models\SongRequestModel;
use App\Models\CafeModel;
use App\Models\UserModel;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Psr\Log\LoggerInterface;

class UserController extends BaseController
{
    protected $requestModel;
    protected $cafeModel;
    protected $userModel;

    public function initController(RequestInterface $request, ResponseInterface $response, LoggerInterface $logger)
    {
        parent::initController($request, $response, $logger);
        $this->requestModel = new SongRequestModel();
        $this->cafeModel = new CafeModel();
        $this->userModel = new UserModel();
    }

    /**
     * User dashboard — daftar cafe + riwayat terbaru
     */
    public function dashboard()
    {
        $userId = session()->get('user_id');

        $cafes = $this->cafeModel->getActiveCafes();

        // Get 5 recent requests
        $recentRequests = $this->requestModel
            ->select('song_requests.*, songs.title, songs.artist, songs.thumbnail, cafes.nama_kafe')
            ->join('songs', 'song_requests.song_id = songs.id', 'left')
            ->join('cafes', 'song_requests.cafe_id = cafes.id', 'left')
            ->where('song_requests.user_id', $userId)
            ->orderBy('song_requests.requested_at', 'DESC')
            ->limit(5)
            ->find();

        $user = $this->userModel->find($userId);

        return view('user/dashboard', [
            'cafes' => $cafes,
            'recentRequests' => $recentRequests ?: [],
            'user' => $user,
        ]);
    }

    /**
     * Full request history
     */
    public function history()
    {
        $userId = session()->get('user_id');

        $requests = $this->requestModel
            ->select('song_requests.*, songs.title, songs.artist, songs.thumbnail, cafes.nama_kafe')
            ->join('songs', 'song_requests.song_id = songs.id', 'left')
            ->join('cafes', 'song_requests.cafe_id = cafes.id', 'left')
            ->where('song_requests.user_id', $userId)
            ->orderBy('song_requests.requested_at', 'DESC')
            ->findAll();

        return view('user/history', [
            'requests' => $requests ?: [],
        ]);
    }

    /**
     * Profile page
     */
    public function profile()
    {
        $userId = session()->get('user_id');
        $user = $this->userModel->find($userId);

        // Stats
        $totalRequests = $this->requestModel->where('user_id', $userId)->countAllResults(false);
        $totalSpent = $this->requestModel
            ->selectSum('nominal')
            ->where('user_id', $userId)
            ->first();

        return view('user/profile', [
            'user' => $user,
            'totalRequests' => $totalRequests,
            'totalSpent' => (int)($totalSpent['nominal'] ?? 0),
        ]);
    }

    /**
     * Update profile (name only)
     */
    public function updateProfile()
    {
        $userId = session()->get('user_id');

        $rules = [
            'name' => 'required|string|max_length[100]',
        ];

        if (!$this->validate($rules)) {
            return $this->response->setStatusCode(422)->setJSON([
                'success' => false,
                'errors' => $this->validator->getErrors()
            ]);
        }

        $name = $this->request->getVar('name');

        if ($this->userModel->update($userId, [
            'name' => $name,
            'updated_at' => date('Y-m-d H:i:s'),
        ])) {
            // Update session name
            session()->set('user_name', $name);

            return $this->response->setJSON([
                'success' => true,
                'message' => 'Profil berhasil diperbarui'
            ]);
        }

        return $this->response->setStatusCode(400)->setJSON([
            'success' => false,
            'message' => 'Gagal memperbarui profil'
        ]);
    }
}
