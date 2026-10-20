<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Tambah kolom WhatsApp ke tabel users:
 * - phone_wa: nomor WA admin (untuk notifikasi withdrawal)
 * - wa_notif_enabled: preferensi notifikasi WA admin
 *
 * Admin kafe menggunakan kolom ini untuk menerima notifikasi status withdrawal.
 */
class AddWhatsAppFieldsToUsers extends Migration
{
    public function up()
    {
        $this->forge->addColumn('users', [
            'phone_wa' => [
                'type'       => 'VARCHAR',
                'constraint' => 20,
                'null'       => true,
                'default'    => null,
                'after'      => 'email',
            ],
            'wa_notif_enabled' => [
                'type'    => 'BOOLEAN',
                'default' => true,
                'null'    => false,
                'after'   => 'phone_wa',
            ],
        ]);
    }

    public function down()
    {
        $this->forge->dropColumn('users', ['phone_wa', 'wa_notif_enabled']);
    }
}
