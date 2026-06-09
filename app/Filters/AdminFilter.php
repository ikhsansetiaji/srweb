<?php

namespace App\Filters;

use App\Libraries\TabSessionManager;
use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * AdminFilter - cek login + role admin per-tab
 */
class AdminFilter implements FilterInterface
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

        if (TabSessionManager::getTabRole($tabToken) !== 'admin') {
            if ($request->isAJAX()) {
                return response()->setStatusCode(403)->setJSON([
                    'success' => false,
                    'message' => 'Unauthorized - Admin access required'
                ]);
            }
            return redirect()->to('/')->with('error', 'Unauthorized access');
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