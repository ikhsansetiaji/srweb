<?php

namespace App\Controllers\API;

use App\Models\CafeModel;
use CodeIgniter\RESTful\ResourceController;

class CafeController extends ResourceController
{
    protected $cafeModel;
    protected $format = 'json';

    public function __construct()
    {
        $this->cafeModel = new CafeModel();
    }

    /**
     * Get list of active cafes
     * GET /api/v1/cafes
     */
    public function index()
    {
        $cafes = $this->cafeModel->getActiveCafes();

        return $this->respond([
            'success' => true,
            'data' => $cafes,
            'total' => count($cafes)
        ]);
    }

    /**
     * Get cafe detail
     * GET /api/v1/cafes/(:id)
     */
    public function show($id = null)
    {
        $cafe = $this->cafeModel->getCafeWithOwner($id);

        if (!$cafe) {
            return $this->failNotFound('Cafe not found');
        }

        return $this->respond([
            'success' => true,
            'data' => $cafe
        ]);
    }

    /**
     * Get cafes by location/search
     * GET /api/v1/cafes/search?q=query&lat=lat&lng=lng
     */
    public function search()
    {
        $query = $this->request->getVar('q');

        if (!$query || strlen($query) < 2) {
            return $this->failValidationError('Query must be at least 2 characters');
        }

        $cafes = $this->cafeModel->like('nama_kafe', $query)
            ->orLike('alamat', $query)
            ->where('status', 'approved')
            ->where('is_active', true)
            ->limit(20)
            ->findAll();

        return $this->respond([
            'success' => true,
            'data' => $cafes,
            'total' => count($cafes)
        ]);
    }
}

