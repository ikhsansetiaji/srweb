<?php

namespace App\Models;

use CodeIgniter\Model;

/**
 * EmailNotificationLogModel
 *
 * Mengelola tabel email_notifications.
 * Menyediakan operasi pencatatan log notifikasi email dan idempotensi
 * untuk mencegah pengiriman email ganda.
 */
class EmailNotificationLogModel extends Model
{
    protected $table            = 'email_notifications';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $allowedFields    = [
        'idempotency_key',
        'event_type',
        'recipient_email',
        'recipient_name',
        'entity_type',
        'entity_id',
        'subject',
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
    public const STATUS_PENDING = 'pending';
    public const STATUS_SENT    = 'sent';
    public const STATUS_FAILED  = 'failed';
    public const STATUS_SKIPPED = 'skipped';

    /**
     * Cek apakah notifikasi dengan idempotency_key sudah ada.
     *
     * @param string $idempotencyKey
     * @return array|null
     */
    public function findByIdempotencyKey(string $idempotencyKey): ?array
    {
        try {
            return $this->where('idempotency_key', $idempotencyKey)->first();
        } catch (\Throwable $e) {
            log_message('warning', 'EmailNotificationLogModel::findByIdempotencyKey exception: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Buat record notifikasi baru.
     * Mengembalikan ID jika berhasil, null jika sudah ada (duplikat) atau error.
     *
     * @param array $data
     * @return int|null
     */
    public function createNotification(array $data): ?int
    {
        $data['created_at'] = $data['created_at'] ?? date('Y-m-d H:i:s');
        $data['updated_at'] = date('Y-m-d H:i:s');

        try {
            if ($this->insert($data)) {
                return (int)$this->insertID;
            }
        } catch (\Throwable $e) {
            if ($this->isDuplicateKeyError($e)) {
                log_message('info', "Email notification duplicate (idempotent skip): {$data['idempotency_key']}");
                return null;
            }
            log_message('error', 'EmailNotificationLogModel::createNotification error: ' . $e->getMessage());
        }

        return null;
    }

    /**
     * Update status notifikasi setelah pengiriman.
     *
     * @param int    $id
     * @param string $status
     * @param array  $extra
     * @return bool
     */
    public function updateAfterAttempt(int $id, string $status, array $extra = []): bool
    {
        $updateData = array_merge([
            'status'          => $status,
            'last_attempt_at' => date('Y-m-d H:i:s'),
            'updated_at'      => date('Y-m-d H:i:s'),
        ], $extra);

        if ($status === self::STATUS_SENT) {
            $updateData['sent_at'] = $updateData['sent_at'] ?? date('Y-m-d H:i:s');
        }

        try {
            return $this->update($id, $updateData);
        } catch (\Throwable $e) {
            log_message('error', 'EmailNotificationLogModel::updateAfterAttempt error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Cek apakah exception adalah duplicate key error.
     *
     * @param \Throwable $e
     * @return bool
     */
    protected function isDuplicateKeyError(\Throwable $e): bool
    {
        $msg = $e->getMessage();
        return str_contains($msg, 'duplicate key')
            || str_contains($msg, '23505')
            || str_contains($msg, 'unique constraint')
            || str_contains($msg, 'Duplicate entry');
    }
}
