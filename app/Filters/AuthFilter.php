<?php

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * AuthFilter - verifikasi login user melalui standard CodeIgniter session & Bearer token
 */
class AuthFilter implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        // 1. Web session (CodeIgniter session)
        if (session()->get('logged_in') && session()->has('user_id')) {
            return null;
        }

        // 2. Bearer token (Android/mobile)
        $authHeader = $request->getHeaderLine('Authorization');
        if (str_starts_with($authHeader, 'Bearer ')) {
            $token       = substr($authHeader, 7);
            $hashedToken = hash('sha256', $token);
            $userId      = cache()->get("api_token_val_{$hashedToken}");

            if ($userId) {
                $user = (new \App\Models\UserModel())->find($userId);
                if ($user && $user['is_active']) {
                    session()->set([
                        'user_id'    => $user['id'],
                        'user_name'  => $user['name'],
                        'user_email' => $user['email'],
                        'user_role'  => $user['role'],
                        'logged_in'  => true,
                    ]);
                    return null;
                }
            }
        }

        if ($request->isAJAX() || str_contains($request->getHeaderLine('Accept'), 'application/json')) {
            return response()->setStatusCode(401)->setJSON([
                'success'  => false,
                'message'  => 'Sesi telah berakhir. Silakan login kembali.',
                'redirect' => '/auth/login',
            ]);
        }
        return redirect()->to('/auth/login');
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
    }
}