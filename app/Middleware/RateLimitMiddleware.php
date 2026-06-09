<?php

namespace App\Middleware;

use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * Rate Limiting Middleware
 * Prevent brute force attacks
 */
class RateLimitMiddleware
{
    protected $allowedRequests = 100;
    protected $timeWindow = 60; // seconds

    public function before(RequestInterface $request, $arguments = null)
    {
        $clientIp = $this->getClientIp($request);
        $key = "rate_limit_{$clientIp}";
        $cache = cache();

        $count = $cache->get($key) ?? 0;

        if ($count >= $this->allowedRequests) {
            return response()
                ->setStatusCode(429)
                ->setJSON([
                    'success' => false,
                    'message' => 'Too many requests. Please try again later.'
                ]);
        }

        $cache->save($key, $count + 1, $this->timeWindow);

        return null;
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
    }

    /**
     * Get client IP address safely
     */
    private function getClientIp(RequestInterface $request): string
    {
        if (!empty($_SERVER['HTTP_CF_CONNECTING_IP'])) {
            return $_SERVER['HTTP_CF_CONNECTING_IP'];
        } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            return explode(',', $_SERVER['HTTP_X_FORWARDED_FOR'])[0];
        } elseif (!empty($_SERVER['HTTP_X_FORWARDED'])) {
            return $_SERVER['HTTP_X_FORWARDED'];
        } elseif (!empty($_SERVER['HTTP_FORWARDED_FOR'])) {
            return $_SERVER['HTTP_FORWARDED_FOR'];
        } elseif (!empty($_SERVER['HTTP_FORWARDED'])) {
            return $_SERVER['HTTP_FORWARDED'];
        } else {
            return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
        }
    }
}

