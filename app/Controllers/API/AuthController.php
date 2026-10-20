<?php

namespace App\Controllers\API;

use App\Services\AuthService;
use CodeIgniter\HTTP\ResponseInterface;
use CodeIgniter\RESTful\ResourceController;

class AuthController extends ResourceController
{
    protected $authService;
    protected $format = 'json';

    public function __construct()
    {
        $this->authService = new AuthService();
    }

    /**
     * Mobile app register endpoint
     */
    public function register()
    {
        // Parse JSON body from mobile app
        $json = $this->request->getJSON(true);
        if ($json) {
            $this->request->setGlobal('request', $json);
        }

        $rules = [
            'name' => 'required|string|max_length[100]',
            'email' => 'required|valid_email|is_unique[users.email]',
            'password' => 'required|min_length[8]',
            'phone' => 'permit_empty|string|max_length[20]',
        ];

        if (!$this->validate($rules)) {
            return $this->failValidationErrors($this->validator->getErrors());
        }

        $data = [
            'name' => $json['name'] ?? $this->request->getVar('name'),
            'email' => $json['email'] ?? $this->request->getVar('email'),
            'password' => $json['password'] ?? $this->request->getVar('password'),
            'role' => 'user',
        ];

        $result = $this->authService->register($data);

        if ($result['success']) {
            return $this->respondCreated([
                'success' => true,
                'message' => 'Registration successful',
                'user_id' => $result['user_id']
            ]);
        }

        return $this->fail($result['message'], 400);
    }

    /**
     * Mobile app login endpoint
     */
    public function login()
    {
        // Parse JSON body from mobile app
        $json = $this->request->getJSON(true);
        if ($json) {
            $this->request->setGlobal('request', $json);
        }

        $rules = [
            'email' => 'required|valid_email',
            'password' => 'required|min_length[8]',
        ];

        if (!$this->validate($rules)) {
            return $this->failValidationErrors($this->validator->getErrors());
        }

        $email = $json['email'] ?? $this->request->getVar('email');
        $password = $json['password'] ?? $this->request->getVar('password');

        $result = $this->authService->login($email, $password);

        if ($result['success']) {
            $user = $result['user'];

            // Generate API token untuk mobile
            $token = $this->generateApiToken($user['id']);

            return $this->respond([
                'success' => true,
                'message' => 'Login successful',
                'user' => $user,
                'token' => $token
            ]);
        }

        return $this->failUnauthorized($result['message']);
    }

    private function generateApiToken(int $userId): string
    {
        $token = bin2hex(random_bytes(32));
        $hashedToken = hash('sha256', $token);

        // Save mapping: userId -> hashedToken
        cache()->save("api_token_{$userId}", $hashedToken, 30 * 24 * 60 * 60); // 30 days
        // Save mapping: hashedToken -> userId (for quick reverse lookup)
        cache()->save("api_token_val_{$hashedToken}", $userId, 30 * 24 * 60 * 60); // 30 days

        return $token;
    }
}

