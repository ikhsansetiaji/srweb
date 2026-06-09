<?php

namespace App\Models;

use CodeIgniter\Model;

class CafeModel extends Model
{
    protected $table = 'cafes';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    protected $useSoftDeletes = false;
    protected $allowedFields = [
        'admin_id', 'nama_kafe', 'slug', 'alamat', 'deskripsi', 'logo',
        'phone_number', 'status', 'is_active', 'verified_by', 'verified_at',
        'rejection_reason', 'payment_receiver', 'payment_method', 'payment_qris',
        'payment_gate_token', 'updated_at'
    ];
    protected $useTimestamps = false;

    protected $validationRules = [
        'admin_id' => 'required|integer',
        'nama_kafe' => 'required|string|max_length[100]',
        'slug' => 'permit_empty|string|max_length[120]|is_unique[cafes.slug,id,{id}]',
        'alamat' => 'required|string',
        'deskripsi' => 'permit_empty|string',
        'phone_number' => 'permit_empty|string|max_length[20]',
        'payment_receiver' => 'required|string|max_length[100]',
        'payment_method' => 'required|string|max_length[50]',
    ];

    /**
     * Get cafe dengan owner info
     *
     * @param int $cafeId
     * @return array|null
     */
    public function getCafeWithOwner(int $cafeId): ?array
    {
        return $this->select('cafes.*, users.name as admin_name, users.email as admin_email')
            ->join('users', 'cafes.admin_id = users.id', 'left')
            ->find($cafeId);
    }

    /**
     * Get cafes yang pending untuk verifikasi superadmin
     *
     * @return array
     */
    public function getPendingCafes(): array
    {
        return $this->where('status', 'pending')
            ->select('cafes.*, users.name as admin_name, users.email as admin_email')
            ->join('users', 'cafes.admin_id = users.id', 'left')
            ->orderBy('cafes.created_at', 'ASC')
            ->findAll();
    }

    /**
     * Approve cafe by superadmin dengan security
     *
     * @param int $cafeId
     * @param int $superadminId
     * @return bool
     */
    public function approveCafe(int $cafeId, int $superadminId): bool
    {
        $db = \Config\Database::connect();
        $db->transStart();

        try {
            // Update cafe status
            $this->update($cafeId, [
                'status' => 'approved',
                'is_active' => true,
                'verified_by' => $superadminId,
                'verified_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ]);

            // Create cafe balance entry
            $balanceModel = new CafeBalanceModel();
            $balanceModel->insert([
                'cafe_id' => $cafeId,
                'available_balance' => 0,
                'total_income' => 0,
                'total_withdrawn' => 0,
                'updated_at' => date('Y-m-d H:i:s'),
            ]);

            $db->transComplete();

            if ($db->transStatus()) {
                return true;
            }
        } catch (\Exception $e) {
            $db->transRollback();
            log_message('error', 'Cafe approval error: ' . $e->getMessage());
        }

        return false;
    }

    /**
     * Reject cafe dengan reason
     *
     * @param int $cafeId
     * @param int $superadminId
     * @param string $reason
     * @return bool
     */
    public function rejectCafe(int $cafeId, int $superadminId, string $reason): bool
    {
        return $this->update($cafeId, [
            'status' => 'rejected',
            'is_active' => false,
            'verified_by' => $superadminId,
            'verified_at' => date('Y-m-d H:i:s'),
            'rejection_reason' => $reason,
            'updated_at' => date('Y-m-d H:i:s'),
        ]);
    }

    /**
     * Get active cafes untuk guest user
     *
     * @return array
     */
    public function getActiveCafes(): array
    {
        return $this->where('status', 'approved')
            ->where('is_active', true)
            ->orderBy('nama_kafe', 'ASC')
            ->findAll();
    }

    /**
     * Get cafe by admin - verifikasi ownership
     *
     * @param int $cafeId
     * @param int $adminId
     * @return array|null
     */
    public function getCafeByAdmin(int $cafeId, int $adminId): ?array
    {
        return $this->where('id', $cafeId)
            ->where('admin_id', $adminId)
            ->first();
    }
}

