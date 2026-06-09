<?php

namespace App\Controllers\API;

use App\Models\SongRequestModel;
use CodeIgniter\RESTful\ResourceController;

class SongRequestController extends ResourceController
{
    protected $requestModel;
    protected $format = 'json';

    public function __construct()
    {
        $this->requestModel = new SongRequestModel();
    }

    /**
     * Create song request
     * POST /api/v1/song-request/create
     */
    public function create()
    {
        // Verify API token
        $token = $this->getBearerToken();
        if (!$token || !$this->verifyApiToken($token)) {
            return $this->failUnauthorized('Invalid API token');
        }

        $rules = [
            'cafe_id' => 'required|integer',
            'song_id' => 'required|integer',
            'nominal' => 'required|integer|greater_than[0]',
            'guest_name' => 'permit_empty|string|max_length[100]',
        ];

        if (!$this->validate($rules)) {
            return $this->failValidationErrors($this->validator->getErrors());
        }

        $data = [
            'cafe_id' => $this->request->getVar('cafe_id'),
            'song_id' => $this->request->getVar('song_id'),
            'nominal' => $this->request->getVar('nominal'),
            'guest_name' => $this->request->getVar('guest_name') ?: 'Anonim',
            'user_id' => $this->getUserIdFromToken($token),
        ];

        $requestId = $this->requestModel->createRequest($data);

        if ($requestId) {
            return $this->respondCreated([
                'success' => true,
                'message' => 'Request created successfully',
                'request_id' => $requestId
            ]);
        }

        return $this->fail('Request creation failed', 400);
    }

    /**
     * Get user requests
     * GET /api/v1/user/requests
     */
    public function getUserRequests()
    {
        // Verify API token
        $token = $this->getBearerToken();
        if (!$token || !$this->verifyApiToken($token)) {
            return $this->failUnauthorized('Invalid API token');
        }

        $userId = $this->getUserIdFromToken($token);
        $requests = $this->requestModel->getUserRequests($userId);

        return $this->respond([
            'success' => true,
            'data' => $requests,
            'total' => count($requests)
        ]);
    }

    /**
     * Get request detail
     * GET /api/v1/song-request/(:id)
     */
    public function show($id = null)
    {
        $request = $this->requestModel->select('song_requests.*, songs.title, songs.artist, songs.duration')
            ->join('songs', 'song_requests.song_id = songs.id', 'left')
            ->find($id);

        if (!$request) {
            return $this->failNotFound('Request not found');
        }

        return $this->respond([
            'success' => true,
            'data' => $request
        ]);
    }

    /**
     * Get Bearer token from header
     */
    private function getBearerToken(): ?string
    {
        $header = $this->request->getHeaderLine('Authorization');

        if (preg_match('/Bearer\s+(.+)/', $header, $matches)) {
            return $matches[1];
        }

        return null;
    }

    /**
     * Verify API token
     */
    private function verifyApiToken(string $token): bool
    {
        $hashedToken = hash('sha256', $token);
        $userId = cache()->get("api_token_val_{$hashedToken}");
        return !empty($userId);
    }

    /**
     * Get user ID from token
     */
    private function getUserIdFromToken(string $token): ?int
    {
        $hashedToken = hash('sha256', $token);
        $userId = cache()->get("api_token_val_{$hashedToken}");
        return $userId ? (int)$userId : null;
    }
}

