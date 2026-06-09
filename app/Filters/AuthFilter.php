<?php

namespace App\Filters;

use App\Libraries\TabSessionManager;
use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * AuthFilter - verifikasi login per-tab menggunakan tabToken
 */
class AuthFilter implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        $tabToken = TabSessionManager::getTokenFromRequest();

        if (!$tabToken || !TabSessionManager::isTabLoggedIn($tabToken)) {
            if ($request->isAJAX()) {
                return response()->setStatusCode(401)->setJSON([
                    'success' => false,
                    'message' => 'Sesi tab ini telah berakhir. Silakan login kembali.'
                ]);
            }
            return redirect()->to('/auth/login');
            
        }

        // Hydrate sudah dilakukan oleh TabSessionFilter (global),
        // tapi pastikan tetap ada sebagai fallback safety
        if (!TabSessionManager::getCurrentUser()) {
            TabSessionManager::hydrateSession($tabToken);
        }

        return null;
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        // Dehydrate ditangani oleh TabSessionFilter global
    }
}