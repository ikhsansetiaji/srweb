<?php

namespace App\Controllers;


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
     */
    public function login()
    {
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
            // Regenerate session ID untuk mencegah session fixation attacks
            session()->regenerate(true);

            // Simpan data user ke standard session
            session()->set([
                'user_id'    => $result['user']['id'],
                'user_name'  => $result['user']['name'],
                'user_email' => $result['user']['email'],
                'user_role'  => $result['user']['role'],
                'logged_in'  => true,
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
            'phone'            => 'required|string|min_length[9]|max_length[20]',
            'phone_wa'         => 'permit_empty|string|max_length[20]',
            'is_wa_same'       => 'permit_empty',
            'wa_notif_enabled' => 'permit_empty',
        ];

        if (!$this->validate($rules)) {
            return $this->response->setStatusCode(422)->setJSON([
                'success' => false,
                'errors'  => $this->validator->getErrors()
            ]);
        }

        $name           = $this->request->getPost('name');
        $email          = $this->request->getPost('email');
        $password       = $this->request->getPost('password');
        $role           = $this->request->getPost('role');
        $phone          = $this->request->getPost('phone');
        $phoneWa        = $this->request->getPost('phone_wa');
        $isWaSame       = $this->request->getPost('is_wa_same');
        $waNotifEnabled = $this->request->getPost('wa_notif_enabled');

        if (!$name || !$email || !$password || !$role) {
            $json           = $this->request->getJSON(true);
            $name           = $json['name']             ?? $name;
            $email          = $json['email']            ?? $email;
            $password       = $json['password']         ?? $password;
            $role           = $json['role']             ?? $role;
            $phone          = $json['phone']            ?? $phone;
            $phoneWa        = $json['phone_wa']         ?? $phoneWa;
            $isWaSame       = $json['is_wa_same']       ?? $isWaSame;
            $waNotifEnabled = $json['wa_notif_enabled'] ?? $waNotifEnabled;
        }

        // Tentukan nomor WhatsApp berdasarkan konfirmasi pengguna
        $isSame = filter_var($isWaSame ?? true, FILTER_VALIDATE_BOOLEAN);
        $finalPhoneWa = ($isSame || empty($phoneWa)) ? $phone : $phoneWa;

        $fonnteService = new \App\Services\FonnteService();
        $normalizedPhoneWa = $fonnteService->normalizePhone($finalPhoneWa) ?: $finalPhoneWa;

        $registerData = [
            'name'             => $name,
            'email'            => $email,
            'password'         => $password,
            'role'             => $role,
            'phone'            => $phone,
            'phone_wa'         => $normalizedPhoneWa,
            'wa_notif_enabled' => \App\Services\WhatsAppNotificationService::parseBoolean($waNotifEnabled ?? true),
        ];

        $result = $this->authService->register($registerData);

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
     * Handle logout
     */
    public function logout()
    {
        session()->destroy();
        return redirect()->to('/auth/login')->with('info', 'Anda telah berhasil logout.');
    }

    /**
     * Show user dashboard screen (redirects to UserController)
     */
    public function userDashboard()
    {
        return redirect()->to('/user/dashboard');
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