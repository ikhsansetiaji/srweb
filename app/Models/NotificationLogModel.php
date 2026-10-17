<?php

namespace App\Models;

use CodeIgniter\Model;

/**
 * NotificationLogModel
 *
 * Mengelola tabel whatsapp_notifications.
 * Menyediakan operasi CRUD untuk log notifikasi WA,
 * termasuk query untuk retry worker.
 *
 * PENTING: Model ini TIDAK menyimpan token Fonnte atau credential apapun.
 * Nomor penerima HARUS selalu dimasking sebelum disimpan.
 */
class NotificationLogModel extends Model
{
    protected $table            = 'whatsapp_notifications';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $allowedFields    = [
        'idempotency_key',
        'event_type',
        'recipient_type',
        'recipient_masked',
        'entity_type',
        'entity_id',
        'status',
        'attempt_count',
        'provider_message_id',
        'error_summary',
        'response_code',
        'created_at',
        'last_attempt_at',
        'sent_at',
        'updated_at',
    ];
    protected $useTimestamps = false;

    // Status constants
    const STATUS_PENDING = 'pending';
    const STATUS_SENT    = 'sent';
    const STATUS_FAILED  = 'failed';
    const STATUS_SKIPPED = 'skipped'; // Tidak memenuhi syarat (no consent, no number, dsb)

    /**
     * Cek apakah notification dengan idempotency_key sudah ada.
     * Digunakan untuk mencegah duplikat event.
     *
     * @param string $idempotencyKey
     * @return array|null
     */
    public function findByIdempotencyKey(string $idempotencyKey): ?array
    {
        return $this->where('idempotency_key', $idempotencyKey)->first();
    }

    /**
     * Buat record notifikasi baru.
     * Mengembalikan ID jika berhasil, null jika idempotency_key sudah ada
     * (duplicate key conflict → bukan error, ini skenario normal).
     *
     * @param array $data
     * @return int|null  Insert ID atau null jika duplikat
     */
    public function createNotification(array $data): ?int
    {
        $data['created_at'] = $data['created_at'] ?? date('Y-m-d H:i:s');
        $data['updated_at'] = date('Y-m-d H:i:s');

        try {
            if ($this->insert($data)) {
                return (int)$this->insertID;
            }
        } catch (\Exception $e) {
            // Unique constraint violation = duplikat event → normal, bukan error
            if ($this->isDuplicateKeyError($e)) {
                log_message('info', "WA notification duplicate (idempotent skip): {$data['idempotency_key']}");
                return null;
            }
            log_message('error', 'NotificationLogModel::createNotification error: ' . $e->getMessage());
        }

        return null;
    }

    /**
     * Update status notifikasi setelah percobaan pengiriman.
     *
     * @param int    $id
     * @param string $status
     * @param array  $extra  Data tambahan (response_code, provider_message_id, error_summary)
     * @return bool
     */
    public function updateAfterAttempt(int $id, string $status, array $extra = []): bool
    {
        $updateData = array_merge([
            'status'          => $status,
            'last_attempt_at' => date('Y-m-d H:i:s'),
            'updated_at'      => date('Y-m-d H:i:s'),
        ], $extra);

        // Jika berhasil, set sent_at
        if ($status === self::STATUS_SENT) {
            $updateData['sent_at'] = $updateData['sent_at'] ?? date('Y-m-d H:i:s');
        }

        return $this->update($id, $updateData);
    }

    /**
     * Increment attempt_count secara atomic.
     *
     * @param int $id
     * @return bool
     */
    public function incrementAttemptCount(int $id): bool
    {
        return $this->db->query(
            'UPDATE whatsapp_notifications SET attempt_count = attempt_count + 1, updated_at = ? WHERE id = ?',
            [date('Y-m-d H:i:s'), $id]
        );
    }

    /**
     * Get notifikasi yang perlu di-retry.
     * Kondisi retry:
     * - status = pending
     * - attempt_count < max_retries
     * - (last_attempt_at IS NULL) ATAU (last_attempt_at + retry_delay <= now)
     *
     * @param int $maxRetries  Maksimum percobaan dari konfigurasi
     * @param int $retryDelay  Jeda antar retry dalam detik
     * @param int $limit
     * @return array
     */
    public function getPendingForRetry(int $maxRetries = 3, int $retryDelay = 60, int $limit = 50): array
    {
        $cutoff = date('Y-m-d H:i:s', time() - $retryDelay);

        return $this->where('status', self::STATUS_PENDING)
            ->where('attempt_count <', $maxRetries)
            ->groupStart()
                ->where('last_attempt_at IS NULL', null, false)
                ->orWhere('last_attempt_at <=', $cutoff)
            ->groupEnd()
            ->orderBy('created_at', 'ASC')
            ->limit($limit)
            ->findAll();
    }

    /**
     * Tandai notifikasi sebagai failed setelah batas retry tercapai.
     *
     * @param int    $id
     * @param string $reason
     * @return bool
     */
    public function markAsFailed(int $id, string $reason): bool
    {
        return $this->update($id, [
            'status'          => self::STATUS_FAILED,
            'error_summary'   => mb_substr($reason, 0, 500), // Batasi panjang
            'updated_at'      => date('Y-m-d H:i:s'),
        ]);
    }

    /**
     * Get riwayat notifikasi untuk satu entitas (untuk debug/audit).
     *
     * @param string $entityType  'payment', 'request', 'withdrawal'
     * @param int    $entityId
     * @return array
     */
    public function getByEntity(string $entityType, int $entityId): array
    {
        return $this->where('entity_type', $entityType)
            ->where('entity_id', $entityId)
            ->orderBy('created_at', 'DESC')
            ->findAll();
    }

    /**
     * Count pending notifications (untuk dashboard monitoring).
     *
     * @return int
     */
    public function countPending(): int
    {
        return $this->where('status', self::STATUS_PENDING)->countAllResults();
    }

    /**
     * Count failed notifications.
     *
     * @return int
     */
    public function countFailed(): int
    {
        return $this->where('status', self::STATUS_FAILED)->countAllResults();
    }

    /**
     * Mask nomor telepon untuk logging aman.
     * Contoh: 6281234567890 → 628123****890
     *
     * @param string $phone
     * @return string
     */
    public static function maskPhone(string $phone): string
    {
        $len = strlen($phone);
        if ($len <= 7) {
            return str_repeat('*', $len);
        }
        // Tampilkan 4 digit awal dan 3 digit akhir
        return substr($phone, 0, 4) . str_repeat('*', $len - 7) . substr($phone, -3);
    }

    /**
     * Cek apakah exception adalah duplicate key error (PostgreSQL).
     *
     * @param \Exception $e
     * @return bool
     */
    private function isDuplicateKeyError(\Exception $e): bool
    {
        $msg = $e->getMessage();
        // PostgreSQL: "duplicate key value violates unique constraint"
        // Also check for SQLSTATE 23505
        return str_contains($msg, 'duplicate key')
            || str_contains($msg, '23505')
            || str_contains($msg, 'unique constraint');
    }
}
