<?php

namespace Config;

use CodeIgniter\Config\Filters as BaseFilters;
use CodeIgniter\Filters\Cors;
use CodeIgniter\Filters\CSRF;
use CodeIgniter\Filters\DebugToolbar;
use CodeIgniter\Filters\ForceHTTPS;
use CodeIgniter\Filters\Honeypot;
use CodeIgniter\Filters\InvalidChars;
use CodeIgniter\Filters\PageCache;
use CodeIgniter\Filters\PerformanceMetrics;
use CodeIgniter\Filters\SecureHeaders;
use App\Filters\AuthFilter;
use App\Filters\AdminFilter;
use App\Filters\SuperadminFilter;
use App\Filters\TabSessionFilter;

class Filters extends BaseFilters
{
    public array $aliases = [
        'csrf'          => CSRF::class,
        'toolbar'       => DebugToolbar::class,
        'honeypot'      => Honeypot::class,
        'invalidchars'  => InvalidChars::class,
        'secureheaders' => SecureHeaders::class,
        'cors'          => Cors::class,
        'forcehttps'    => ForceHTTPS::class,
        'pagecache'     => PageCache::class,
        'performance'   => PerformanceMetrics::class,
        'auth'          => AuthFilter::class,
        'admin'         => AdminFilter::class,
        'superadmin'    => SuperadminFilter::class,
        'tabsession'    => TabSessionFilter::class,
    ];

    public array $required = [
        'before' => [
            'forcehttps', // Force Global Secure Requests
            'pagecache',  // Web Page Caching
        ],
        'after' => [
            'pagecache',   // Web Page Caching
            'performance', // Performance Metrics
            'toolbar',     // Debug Toolbar
        ],
    ];

    public array $globals = [
        'before' => [
            'tabsession', // Hydrate per-tab session data
            // 'honeypot',
            // 'csrf',
            // 'invalidchars',
        ],
        'after' => [
            'tabsession', // Dehydrate: bersihkan session agar tidak bocor antar tab
            // 'honeypot',
            // 'secureheaders',
        ],
    ];

    public array $methods = [];

    public array $filters = [
        // Admin cafe routes - require auth + admin role
        'admin/*' => [
            'before' => ['auth', 'admin'],
        ],

        // Superadmin routes - require auth + superadmin role
        'superadmin/*' => [
            'before' => ['auth', 'superadmin'],
        ],

        // Cafe management routes - require auth
        'cafe/register' => [
            'before' => ['auth'],
        ],
        'cafe/update/*' => [
            'before' => ['auth'],
        ],

        // Song request - require auth for history
        'song-request/history' => [
            'before' => ['auth'],
        ],
        'song-request/cancel/*' => [
            'before' => ['auth'],
        ],

        // Payment routes
        'payment/history/*' => [
            'before' => ['auth'],
        ],
    ];
}
