<?php

namespace App\Controllers\Pages;

use App\Controllers\BaseController;

class LandingPageController extends BaseController
{
    /**
     * Show landing page
     *
     * @return string
     */
    public function index(): string
    {
        $data = [
            'title' => 'Song Request - Layanan Request Lagu Berbasis Saweran',
            'description' => 'Minta lagu favorit di kafe dengan saweran digital. Semakin besar saweran, semakin cepat lagu Anda diputar!',
        ];

        return view('pages/landing', $data);
    }
}

