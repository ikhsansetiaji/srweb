<?php

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * SuperadminFilter - cek login + role superadmin
 */
class SuperadminFilter implements FilterInterface
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

        if (session()->get('user_role') !== 'superadmin') {
            if ($request->isAJAX() || str_contains($request->getHeaderLine('Accept'), 'application/json')) {
                return response()->setStatusCode(403)->setJSON([
                    'success' => false,
                    'message' => 'Unauthorized - Superadmin access required'
                ]);
            }
            return redirect()->to('/')->with('error', 'Unauthorized access');
        }

        return null;
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
    }
}