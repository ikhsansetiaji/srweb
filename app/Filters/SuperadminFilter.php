<?php

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * SuperadminFilter - cek login + role superadmin melalui standard session & Bearer token
 */
class SuperadminFilter implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        // 1. Web session (CodeIgniter session)
        if (session()->get('logged_in') && session()->has('user_id')) {
            if (session()->get('user_role') !== 'superadmin') {
                return $this->unauthorized($request);
            }
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
                if ($user && $user['is_active'] && $user['role'] === 'superadmin') {
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

        // Tidak ada auth yang valid
        if ($request->isAJAX() || str_contains($request->getHeaderLine('Accept'), 'application/json')) {
            return response()->setStatusCode(401)->setJSON([
                'success' => false,
                'message' => 'Unauthorized'
            ]);
        }
        return redirect()->to('/auth/login');
    }

    private function unauthorized(RequestInterface $request)
    {
        if ($request->isAJAX() || str_contains($request->getHeaderLine('Accept'), 'application/json')) {
            return response()->setStatusCode(403)->setJSON([
                'success' => false,
                'message' => 'Unauthorized - Superadmin access required'
            ]);
        }
        return redirect()->to('/')->with('error', 'Unauthorized access');
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
    }
}