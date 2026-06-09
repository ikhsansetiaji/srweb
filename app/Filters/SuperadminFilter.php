<?php

namespace App\Filters;

use App\Libraries\TabSessionManager;
use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * SuperadminFilter - cek login + role superadmin per-tab
 */
class SuperadminFilter implements FilterInterface
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

        if (TabSessionManager::getTabRole($tabToken) !== 'superadmin') {
            if ($request->isAJAX()) {
                return response()->setStatusCode(403)->setJSON([
                    'success' => false,
                    'message' => 'Unauthorized - Superadmin access required'
                ]);
            }
            return redirect()->to('/')->with('error', 'Unauthorized access');
        }

        TabSessionManager::hydrateSession($tabToken);

        return null;
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
    }
}