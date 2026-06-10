<?php

namespace App\Models;

use CodeIgniter\Model;

class PaymentModel extends Model
{
    protected $table = 'payments';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    protected $useSoftDeletes = false;
    protected $allowedFields = [
        'request_id', 'cafe_id', 'payment_method', 'transaction_id',
        'external_reference', 'amount', 'payment_status', 'paid_at', 'updated_at'
    ];
    protected $useTimestamps = false;

    protected $validationRules = [
        'request_id' => 'required|integer',
        'cafe_id' => 'required|integer',
        'payment_method' => 'required|string|max_length[50]',
        'amount' => 'required|integer|greater_than[0]',
    ];

    /**
     * Create payment record dengan uniqueness check
     *
     * @param array $data
     * @return int|false
     */
    public function createPayment(array $data)
    {
        $data['payment_status'] = 'pending';
        $data['created_at'] = date('Y-m-d H:i:s');

        // Jika sudah ada payment pending untuk request ini, return ID yang ada
        $existing = $this->where('request_id', $data['request_id'])
                         ->where('payment_status', 'pending')
                         ->first();
        if ($existing) {
            return $existing['id'];
        }

        if ($this->insert($data)) {
            return $this->insertID;
        }

        log_message('error', 'PaymentModel::createPayment failed: ' . json_encode($this->errors()));
        return false;
    }

    /**
     * Verify payment dari webhook dengan idempotency
     * PENTING: Cek apakah sudah success untuk prevent duplicate balance update
     *
     * @param string $transactionId
     * @param string $externalReference
     * @return array|null
     */
    public function getPaymentByReference(string $transactionId = null, string $externalReference = null): ?array
    {
        $query = $this->where('payment_status !=', 'pending');

        if ($transactionId) {
            $query->where('transaction_id', $transactionId);
        } elseif ($externalReference) {
            $query->where('external_reference', $externalReference);
        }

        return $query->first();
    }

    /**
     * Update payment status dari gateway webhook DENGAN TRANSACTION
     * CRITICAL: Prevent duplicate processing dengan check existing success status
     *
     * @param int $paymentId
     * @param string $status
     * @param string|null $transactionId
     * @return bool
     */
    public function updatePaymentStatus(int $paymentId, string $status, ?string $transactionId = null): bool
    {
        $db = \Config\Database::connect();

        try {
            $payment = $this->find($paymentId);
            if (!$payment) {
                return false;
            }

            // Prevent idempotency issue - if already success, return false
            if ($payment['payment_status'] === 'success' && $status === 'success') {
                return false;
            }

            $updateData = [
                'payment_status' => $status,
                'updated_at' => date('Y-m-d H:i:s'),
            ];

            if ($status === 'success' && !$payment['paid_at']) {
                $updateData['paid_at'] = date('Y-m-d H:i:s');
            }

            if ($transactionId) {
                $updateData['transaction_id'] = $transactionId;
            }

            return $this->update($paymentId, $updateData);
        } catch (\Exception $e) {
            log_message('error', 'Payment status update error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Get payment dengan request dan cafe info
     *
     * @param int $paymentId
     * @return array|null
     */
    public function getPaymentWithDetails(int $paymentId): ?array
    {
        return $this->select('payments.*, song_requests.nominal, cafes.nama_kafe, users.name')
            ->join('song_requests', 'payments.request_id = song_requests.id', 'left')
            ->join('cafes', 'payments.cafe_id = cafes.id', 'left')
            ->join('users', 'song_requests.user_id = users.id', 'left')
            ->find($paymentId);
    }

    /**
     * Get cafe payments dengan pagination
     *
     * @param int $cafeId
     * @param int $limit
     * @param int $offset
     * @return array
     */
    public function getCafePayments(int $cafeId, int $limit = 50, int $offset = 0): array
    {
        return $this->select('payments.*, song_requests.nominal, songs.title, songs.artist')
            ->join('song_requests', 'payments.request_id = song_requests.id', 'left')
            ->join('songs', 'song_requests.song_id = songs.id', 'left')
            ->where('payments.cafe_id', $cafeId)
            ->orderBy('payments.created_at', 'DESC')
            ->limit($limit, $offset)
            ->findAll();
    }

    /**
     * Get payments untuk hari ini di cafe
     *
     * @param int $cafeId
     * @return array
     */
    public function getTodayPayments(int $cafeId): array
    {
        $today = date('Y-m-d');

        return $this->where('cafe_id', $cafeId)
            ->where('payment_status', 'success')
            ->where("DATE(paid_at) = '$today'")
            ->findAll();
    }

    /**
     * Calculate total income untuk cafe
     *
     * @param int $cafeId
     * @return int
     */
    public function calculateTotalIncome(int $cafeId): int
    {
        $result = $this->selectSum('amount')
            ->where('cafe_id', $cafeId)
            ->where('payment_status', 'success')
            ->first();

        return $result['amount'] ?? 0;
    }

    /**
     * Count successful payments
     *
     * @param int $cafeId
     * @return int
     */
    public function countSuccessfulPayments(int $cafeId): int
    {
        return $this->where('cafe_id', $cafeId)
            ->where('payment_status', 'success')
            ->countAllResults();
    }
}