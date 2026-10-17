<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;
use CodeIgniter\Database\RawSql;

/**
 * Tabel notifikasi Email.
 *
 * Menyimpan setiap event notifikasi email beserta status pengirimannya ke Brevo.
 * Digunakan untuk:
 * - Logging: admin/developer bisa melihat riwayat & status pengiriman email
 * - Idempotensi: mencegah event yang sama mengirim email duplikat
 * - Audit trail notifikasi sistem (request, pembayaran, withdrawal, akun admin)
 */
class CreateEmailNotificationsTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id' => [
                'type'           => 'BIGINT',
                'auto_increment' => true,
            ],
            // Kunci idempotensi unik per event + entitas
            'idempotency_key' => [
                'type'       => 'VARCHAR',
                'constraint' => 200,
                'null'       => false,
            ],
            // Jenis event: request_created, song_playing, song_done, song_cancelled,
            // payment_success, payment_failed, payment_expired,
            // withdrawal_created, withdrawal_approved, withdrawal_rejected, withdrawal_completed,
            // admin_registered, admin_approved, admin_rejected, cafe_status_changed
            'event_type' => [
                'type'       => 'VARCHAR',
                'constraint' => 50,
                'null'       => false,
            ],
            'recipient_email' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
                'null'       => false,
            ],
            'recipient_name' => [
                'type'       => 'VARCHAR',
                'constraint' => 100,
                'null'       => true,
            ],
            // Referensi ke entitas terkait (request, payment, withdrawal, user, cafe)
            'entity_type' => [
                'type'       => 'VARCHAR',
                'constraint' => 30,
                'null'       => true,
            ],
            'entity_id' => [
                'type' => 'INT',
                'null' => true,
            ],
            'subject' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
                'null'       => false,
            ],
            // Status pengiriman: pending, sent, failed, skipped
            'status' => [
                'type'       => 'VARCHAR',
                'constraint' => 20,
                'default'    => 'pending',
                'null'       => false,
            ],
            'attempt_count' => [
                'type'    => 'SMALLINT',
                'default' => 0,
                'null'    => false,
            ],
            // Message ID dari Brevo jika tersedia
            'provider_message_id' => [
                'type'       => 'VARCHAR',
                'constraint' => 150,
                'null'       => true,
            ],
            // Ringkasan error jika terjadi kegagalan (tanpa kredensial/token)
            'error_summary' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            // HTTP response code dari API Brevo
            'response_code' => [
                'type'       => 'SMALLINT',
                'null'       => true,
            ],
            'created_at' => [
                'type'    => 'TIMESTAMP',
                'default' => new RawSql('CURRENT_TIMESTAMP'),
                'null'    => false,
            ],
            'last_attempt_at' => [
                'type' => 'TIMESTAMP',
                'null' => true,
            ],
            'sent_at' => [
                'type' => 'TIMESTAMP',
                'null' => true,
            ],
            'updated_at' => [
                'type' => 'TIMESTAMP',
                'null' => true,
            ],
        ]);

        $this->forge->addKey('id', true);
        $this->forge->createTable('email_notifications', true);

        // Unique constraint untuk idempotensi
        $this->db->query('CREATE UNIQUE INDEX IF NOT EXISTS uq_email_idempotency ON email_notifications(idempotency_key)');

        // Index untuk status dan query reporting
        $this->db->query('CREATE INDEX IF NOT EXISTS idx_email_notif_status ON email_notifications(status, attempt_count)');
        $this->db->query('CREATE INDEX IF NOT EXISTS idx_email_notif_event ON email_notifications(event_type, entity_id)');
    }

    public function down()
    {
        $this->forge->dropTable('email_notifications', true);
    }
}
