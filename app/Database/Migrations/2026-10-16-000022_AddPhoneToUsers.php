<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Tambah kolom phone (nomor HP umum) ke tabel users:
 * - phone: nomor handphone umum pengguna (VARCHAR 20, null)
 *
 * Kolom phone_wa dan wa_notif_enabled sudah dibuat pada migrasi 20.
 * Kolom ini melengkapi profil agar pengguna bisa membedakan nomor kontak dan nomor WhatsApp jika berbeda.
 */
class AddPhoneToUsers extends Migration
{
    public function up()
    {
        if (!$this->db->fieldExists('phone', 'users')) {
            $this->forge->addColumn('users', [
                'phone' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 20,
                    'null'       => true,
                    'default'    => null,
                    'after'      => 'email',
                ],
            ]);
        }
    }

    public function down()
    {
        if ($this->db->fieldExists('phone', 'users')) {
            $this->forge->dropColumn('users', 'phone');
        }
    }
}
