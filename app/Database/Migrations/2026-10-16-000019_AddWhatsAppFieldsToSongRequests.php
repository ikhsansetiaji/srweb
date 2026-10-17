<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Tambah kolom WhatsApp ke song_requests:
 * - phone_wa: nomor WA pengunjung (opsional, bisa null)
 * - wa_consent: apakah pengunjung setuju menerima notifikasi WA
 * - wa_consent_at: waktu persetujuan (untuk audit)
 *
 * Kolom ini terpisah dari user account.
 * Pengunjung anonim (tanpa login) tetap bisa mengisi nomor WA untuk menerima notifikasi.
 */
class AddWhatsAppFieldsToSongRequests extends Migration
{
    public function up()
    {
        $this->forge->addColumn('song_requests', [
            'phone_wa' => [
                'type'       => 'VARCHAR',
                'constraint' => 20,
                'null'       => true,
                'default'    => null,
                'after'      => 'guest_name',
            ],
            'wa_consent' => [
                'type'    => 'BOOLEAN',
                'default' => false,
                'null'    => false,
                'after'   => 'phone_wa',
            ],
            'wa_consent_at' => [
                'type' => 'TIMESTAMP',
                'null' => true,
                'after' => 'wa_consent',
            ],
        ]);
    }

    public function down()
    {
        $this->forge->dropColumn('song_requests', ['phone_wa', 'wa_consent', 'wa_consent_at']);
    }
}
