<?php

namespace App\Controllers;

use App\Libraries\TabSessionManager;
use App\Services\AuthService;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Psr\Log\LoggerInterface;

class AuthController extends BaseController
{
    protected $authService;
    protected $validation;

    public function initController(RequestInterface $request, ResponseInterface $response, LoggerInterface $logger)
    {
        parent::initController($request, $response, $logger);
        $this->authService = new AuthService();
        $this->validation  = service('validation');
    }

    /**
     * Show login page
     * Generate tabToken baru jika belum ada di query string
     */
    public function loginPage()
    {
        // tabToken digenerate di sisi client (JS), halaman login hanya render saja
        return view('auth/login');
    }

    /**
     * Handle login request
     * Menerima tabToken dari header X-Tab-Token
     */
    public function login()
    {
        // Ambil tabToken dari header
        // Untuk logout, token bisa dari query param (GET link)
        $tabToken = $this->request->getGet('_tab_token');
        if ($tabToken && !TabSessionManager::isValidToken($tabToken)) {
            $tabToken = null;
        }
        if (!$tabToken) {
            $tabToken = TabSessionManager::getTokenFromRequest();
        }

        if (!$tabToken) {
            return $this->response->setStatusCode(400)->setJSON([
                'success' => false,
                'message' => 'Tab token tidak ditemukan. Refresh halaman dan coba lagi.'
            ]);
        }

        $rules = [
            'email'    => 'required|valid_email',
            'password' => 'required|min_length[8]',
        ];

        if (!$this->validate($rules)) {
            return $this->response->setStatusCode(422)->setJSON([
                'success' => false,
                'errors'  => $this->validator->getErrors()
            ]);
        }

        $email    = $this->request->getPost('email');
        $password = $this->request->getPost('password');

        if (!$email || !$password) {
            $json     = $this->request->getJSON(true);
            $email    = $json['email']    ?? $email;
            $password = $json['password'] ?? $password;
        }

        if (!$email || !$password) {
            return $this->response->setStatusCode(400)->setJSON([
                'success' => false,
                'message' => 'Email dan password harus diisi'
            ]);
        }

        $result = $this->authService->login($email, $password);

        if ($result['success']) {
            // Simpan data user ke slot tab ini di session
            TabSessionManager::setTabUser($tabToken, [
                'user_id'    => $result['user']['id'],
                'user_name'  => $result['user']['name'],
                'user_email' => $result['user']['email'],
                'user_role'  => $result['user']['role'],
            ]);

            return $this->response->setJSON([
                'success'  => true,
                'message'  => 'Login berhasil',
                'redirect' => $this->redirectByRole($result['user']['role'])
            ]);
        }

        return $this->response->setStatusCode(401)->setJSON([
            'success' => false,
            'message' => $result['message']
        ]);
    }

    /**
     * Show register page
     */
    public function registerPage()
    {
        return view('auth/register');
    }

    /**
     * Handle register request
     */
    public function register()
    {
        $rules = [
            'name'             => 'required|string|max_length[100]',
            'email'            => 'required|valid_email|is_unique[users.email]',
            'password'         => 'required|min_length[8]',
            'password_confirm' => 'required|matches[password]',
            'role'             => 'required|in_list[user,admin]',
        ];

        if (!$this->validate($rules)) {
            return $this->response->setStatusCode(422)->setJSON([
                'success' => false,
                'errors'  => $this->validator->getErrors()
            ]);
        }

        $name     = $this->request->getPost('name');
        $email    = $this->request->getPost('email');
        $password = $this->request->getPost('password');
        $role     = $this->request->getPost('role');

        if (!$name || !$email || !$password || !$role) {
            $json     = $this->request->getJSON(true);
            $name     = $json['name']     ?? $name;
            $email    = $json['email']    ?? $email;
            $password = $json['password'] ?? $password;
            $role     = $json['role']     ?? $role;
        }

        $result = $this->authService->register(compact('name', 'email', 'password', 'role'));

        if ($result['success']) {
            return $this->response->setJSON([
                'success'  => true,
                'message'  => $result['message'],
                'redirect' => '/auth/login'
            ]);
        }

        return $this->response->setStatusCode(400)->setJSON([
            'success' => false,
            'message' => $result['message']
        ]);
    }

    /**
     * Handle logout - hanya logout tab ini, tab lain tidak terpengaruh
     */
    public function logout()
    {
        // Untuk logout, token bisa dari query param (GET link)
        $tabToken = $this->request->getGet('_tab_token');
        if ($tabToken && !TabSessionManager::isValidToken($tabToken)) {
            $tabToken = null;
        }
        if (!$tabToken) {
            $tabToken = TabSessionManager::getTokenFromRequest();
        }

        if ($tabToken) {
            // Hapus hanya slot tab ini
            TabSessionManager::clearTab($tabToken);
        }

        // Jangan destroy() seluruh session karena tab lain masih pakai!
        // Hanya hapus slot tab ini dari session.

        return redirect()->to('/');
    }

    /**
     * Show user dashboard screen (reminds user to request via cafe landing/Android app)
     */
    public function userDashboard()
    {
        return view('auth/user_dashboard');
    }

    /**
     * Redirect by user role
     */
    private function redirectByRole(string $role): string
    {
        return match ($role) {
            'admin'      => '/admin/dashboard',
            'superadmin' => '/superadmin/dashboard',
            default      => '/user/dashboard',
        };
    }
}