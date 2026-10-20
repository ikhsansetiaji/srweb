<?php

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * AuthFilter - verifikasi login user melalui standard CodeIgniter session
 */
class AuthFilter implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        if (!session()->get('logged_in') || !session()->has('user_id')) {
            if ($request->isAJAX() || str_contains($request->getHeaderLine('Accept'), 'application/json')) {
                return response()->setStatusCode(401)->setJSON([
                    'success'  => false,
                    'message'  => 'Sesi Anda telah berakhir. Silakan login kembali.',
                    'redirect' => '/auth/login',
                ]);
            }
            return redirect()->to('/auth/login');
        }

        return null;
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
    }
}