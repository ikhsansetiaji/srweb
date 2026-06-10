<?php

use CodeIgniter\Router\RouteCollection;

/** @var RouteCollection $routes */

// ==========================================
// Landing Page
// ==========================================
$routes->get('/', 'Pages\LandingPageController::index');

// ==========================================
// Authentication Routes
// ==========================================
$routes->group('auth', static function ($routes) {
    $routes->get('login', 'AuthController::loginPage');
    $routes->post('login', 'AuthController::login');
    $routes->get('register', 'AuthController::registerPage');
    $routes->post('register', 'AuthController::register');
    $routes->get('logout', 'AuthController::logout');
});

$routes->get('user/dashboard', 'AuthController::userDashboard', ['filter' => 'auth']);

// ==========================================
// Cafe Routes
// ==========================================
$routes->group('cafe', static function ($routes) {
    $routes->get('register', 'CafeController::registerPage');
    $routes->post('register', 'CafeController::register');
    $routes->get('list', 'CafeController::getActiveCafes');
    $routes->get('detail/(:num)', 'CafeController::getCafeDetail/$1');
    $routes->put('update/(:num)', 'CafeController::updateCafe/$1');
});

// ==========================================
// Song Request Routes
// ==========================================
$routes->group('song-request', static function ($routes) {
    $routes->get('request', 'SongRequestController::requestPage');
    $routes->post('create', 'SongRequestController::createRequest');
    $routes->get('search', 'SongRequestController::searchSongs');
    $routes->get('queue/(:num)', 'SongRequestController::getQueue/$1');
    $routes->get('history', 'SongRequestController::getUserHistory');
    $routes->get('detail/(:num)', 'SongRequestController::getRequestDetails/$1');
    $routes->delete('cancel/(:num)', 'SongRequestController::cancelRequest/$1');
    $routes->get('api/v1/spotify/search', '\App\Controllers\API\SongController::searchSpotify');





    // Queue Webhooks (Real-time)
    $routes->get('webhook/queue/(:num)', 'SongRequestController::queueWebhook/$1');
    $routes->get('webhook/next-to-play/(:num)', 'SongRequestController::getNextToPlay/$1');
    $routes->post('webhook/mark-playing/(:num)', 'SongRequestController::markAsPlaying/$1');
    $routes->post('webhook/mark-done/(:num)', 'SongRequestController::markAsDone/$1');
});

// ==========================================
// Payment Routes
// ==========================================
$routes->group('payment', static function ($routes) {
    $routes->get('checkout', 'PaymentController::paymentPage');
    $routes->post('create', 'PaymentController::createPayment');
    $routes->get('finish', 'PaymentController::finish');
    $routes->get('pending', 'PaymentController::pending');
    $routes->get('error', 'PaymentController::error');
    $routes->post('webhook', 'PaymentController::webhook');
    $routes->post('demo-success', 'PaymentController::demoSuccess');
    $routes->get('status/(:num)', 'PaymentController::getPaymentStatus/$1');
    $routes->get('history/cafe/(:num)', 'PaymentController::getCafePaymentHistory/$1');
    $routes->get('daily-income/(:num)', 'PaymentController::getDailyIncome/$1');
});

// ==========================================
// Spotify OAuth Routes (Web Playback SDK)
// ==========================================
$routes->get('spotify/connect', 'SpotifyAuthController::connect');
$routes->get('spotify/callback', 'SpotifyAuthController::callback');
$routes->get('spotify/token', 'SpotifyAuthController::getToken');
$routes->get('spotify/disconnect', 'SpotifyAuthController::disconnect');

// ==========================================
// Admin Routes (Admin Cafe)
// ==========================================
$routes->group('admin', static function ($routes) {
    $routes->get('dashboard', 'AdminController::dashboard');
    $routes->get('queue', 'AdminController::getQueue');
    $routes->post('play-next', 'AdminController::playNextSong');
    $routes->post('mark-done', 'AdminController::markSongDone');
    $routes->get('withdrawals', 'AdminController::getWithdrawalHistory');
    $routes->get('payments', 'AdminController::getPayments');
});

// ==========================================
// Superadmin Routes
// ==========================================
$routes->group('superadmin', static function ($routes) {
    $routes->get('dashboard', 'SuperadminController::dashboard');
    $routes->get('cafes-pending', 'SuperadminController::getPendingCafes');
    $routes->post('cafe-approve', 'SuperadminController::approveCafe');
    $routes->post('cafe-reject', 'SuperadminController::rejectCafe');
    $routes->get('withdrawals-pending', 'SuperadminController::getPendingWithdrawals');
    $routes->post('withdrawal-approve', 'SuperadminController::approveWithdrawal');
    $routes->post('withdrawal-reject', 'SuperadminController::rejectWithdrawal');
    $routes->post('withdrawal-paid', 'SuperadminController::markWithdrawalPaid');
    $routes->get('transactions', 'SuperadminController::getAllTransactions');
    $routes->get('cafes', 'SuperadminController::getAllCafes');
    $routes->get('pending-admins', 'SuperadminController::getPendingAdmins');
    $routes->post('admin-approve', 'SuperadminController::approveAdmin');
    $routes->post('admin-reject', 'SuperadminController::rejectAdmin');
});

// ==========================================
// API Routes (untuk mobile app, future)
// ==========================================
$routes->group('api', static function ($routes) {
    $routes->group('v1', static function ($routes) {
        // Auth endpoints
        $routes->post('auth/register', 'API\AuthController::register');
        $routes->post('auth/login', 'API\AuthController::login');
        
        // Public endpoints - Spotify
        $routes->get('songs/search', 'API\SongController::search');
        $routes->get('songs/popular', 'API\SongController::popular');
        $routes->get('songs/(:num)', 'API\SongController::show/$1');
        $routes->get('spotify/track/(:segment)', 'API\SongController::getTrackDetail/$1');

        // Public endpoints - Cafes
        $routes->get('cafes', 'API\CafeController::index');
        
        // Protected endpoints (require auth token)
        $routes->post('song-request/create', 'API\SongRequestController::create');
        $routes->get('user/requests', 'API\SongRequestController::getUserRequests');

        // Superadmin endpoints (require auth token)
        $routes->get('superadmin/cafes/pending', 'API\CafeController::getPendingCafes');
        $routes->post('superadmin/cafes/approve', 'API\CafeController::approveCafe');
        $routes->post('superadmin/cafes/reject', 'API\CafeController::rejectCafe');
        $routes->get('superadmin/admins/pending', 'API\SuperadminController::getPendingAdmins');
        $routes->post('superadmin/admins/approve', 'API\SuperadminController::approveAdmin');
        $routes->post('superadmin/admins/reject', 'API\SuperadminController::rejectAdmin');
    });
});