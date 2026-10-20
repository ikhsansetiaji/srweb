<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;
use CodeIgniter\Database\RawSql;

/**
 * Tabel notifikasi WhatsApp.
 *
 * Menyimpan setiap event notifikasi beserta status pengirimannya.
 * Digunakan untuk:
 * - Logging: admin/developer bisa melihat status pengiriman
 * - Idempotensi: mencegah event yang sama membuat notifikasi duplikat
 * - Retry: worker bisa melakukan pengiriman ulang untuk yang gagal
 *
 * Kolom idempotency_key adalah UNIQUE CONSTRAINT per jenis event + entitas.
 * Contoh: "payment_success:42", "song_started:15", "withdrawal_status:7:approved"
 */
class CreateWhatsAppNotificationsTable extends Migration
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
            // Jenis event: payment_success, payment_failed, request_queued,
            // song_started, song_finished, withdrawal_status
            'event_type' => [
                'type'       => 'VARCHAR',
                'constraint' => 50,
                'null'       => false,
            ],
            // Jenis penerima: visitor, admin
            'recipient_type' => [
                'type'       => 'VARCHAR',
                'constraint' => 20,
                'null'       => false,
            ],
            // Nomor penerima — dimasking sebelum disimpan (62812****5678)
            'recipient_masked' => [
                'type'       => 'VARCHAR',
                'constraint' => 30,
                'null'       => true,
            ],
            // Referensi ke entitas terkait (request_id, payment_id, withdrawal_id)
            'entity_type' => [
                'type'       => 'VARCHAR',
                'constraint' => 30,
                'null'       => true,
            ],
            'entity_id' => [
                'type' => 'INT',
                'null' => true,
            ],
            // Status pengiriman: pending, sent, failed, skipped
            'status' => [
                'type'       => 'VARCHAR',
                'constraint' => 20,
                'default'    => 'pending',
                'null'       => false,
            ],
            // Jumlah percobaan
            'attempt_count' => [
                'type'    => 'SMALLINT',
                'default' => 0,
                'null'    => false,
            ],
            // Message ID dari Fonnte jika tersedia
            'provider_message_id' => [
                'type'       => 'VARCHAR',
                'constraint' => 100,
                'null'       => true,
            ],
            // Ringkasan error (tanpa token/credential)
            'error_summary' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            // HTTP response code dari Fonnte
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
        $this->forge->createTable('whatsapp_notifications');

        // Unique constraint untuk idempotensi (PALING PENTING)
        $this->db->query('CREATE UNIQUE INDEX uq_wa_idempotency ON whatsapp_notifications(idempotency_key)');

        // Index untuk query retry worker
        $this->db->query('CREATE INDEX idx_wa_notif_status ON whatsapp_notifications(status, attempt_count)');
        $this->db->query('CREATE INDEX idx_wa_notif_event ON whatsapp_notifications(event_type, entity_id)');
    }

    public function down()
    {
        $this->forge->dropTable('whatsapp_notifications');
    }
}
