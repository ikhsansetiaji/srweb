<?php

namespace App\Filters;

use App\Libraries\TabSessionManager;
use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * TabSessionFilter - Global filter yang:
 *
 * BEFORE: Deteksi tab token dan hydrate session agar kode lama
 *         (session()->get('user_role')) tetap bisa jalan.
 *
 * AFTER:  Bersihkan (dehydrate) session supaya data user dari
 *         tab ini TIDAK bocor/bertahan ke request dari tab lain.
 *
 * Filter ini harus dijalankan di SEMUA request (global).
 */
class TabSessionFilter implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        $tabToken = TabSessionManager::getTokenFromRequest();

        if ($tabToken && TabSessionManager::isTabLoggedIn($tabToken)) {
            // Hydrate session untuk request ini saja
            TabSessionManager::hydrateSession($tabToken);
        } else {
            // Simpan token saja (untuk view yang perlu tahu token meski guest)
            TabSessionManager::setCurrentTabToken($tabToken);
        }

        return null;
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        // Bersihkan hydrated data agar tidak persist ke tab lain
        TabSessionManager::dehydrateSession();
    }
}
