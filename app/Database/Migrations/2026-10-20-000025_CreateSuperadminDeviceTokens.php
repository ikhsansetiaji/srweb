<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateSuperadminDeviceTokens extends Migration
{
    public function up(): void
    {
        // Tabel untuk menyimpan device yang sudah dipercaya (trusted)
        $this->forge->addField([
            'id' => [
                'type'           => 'INT',
                'constraint'     => 11,
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'user_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
                'null'       => false,
            ],
            // Token unik yang disimpan di cookie browser
            'device_token' => [
                'type'       => 'VARCHAR',
                'constraint' => 128,
                'null'       => false,
            ],
            // Hash dari device_token (yang disimpan di DB)
            'token_hash' => [
                'type'       => 'VARCHAR',
                'constraint' => 64,
                'null'       => false,
            ],
            // User-agent untuk identifikasi device
            'user_agent' => [
                'type'       => 'VARCHAR',
                'constraint' => 500,
                'null'       => true,
            ],
            // IP address saat verifikasi
            'ip_address' => [
                'type'       => 'VARCHAR',
                'constraint' => 45,
                'null'       => true,
            ],
            // Label device (optional)
            'device_name' => [
                'type'       => 'VARCHAR',
                'constraint' => 100,
                'null'       => true,
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'last_used_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            // Token expired setelah 30 hari
            'expires_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'is_active' => [
                'type'    => 'TINYINT',
                'constraint' => 1,
                'default' => 1,
            ],
        ]);

        $this->forge->addPrimaryKey('id');
        $this->forge->addKey('user_id');
        $this->forge->addUniqueKey('token_hash');
        $this->forge->createTable('superadmin_device_tokens');

        // Tabel untuk OTP verifikasi device superadmin
        $this->forge->addField([
            'id' => [
                'type'           => 'INT',
                'constraint'     => 11,
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'user_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
                'null'       => false,
            ],
            'otp_code' => [
                'type'       => 'VARCHAR',
                'constraint' => 6,
                'null'       => false,
            ],
            'otp_hash' => [
                'type'       => 'VARCHAR',
                'constraint' => 64,
                'null'       => false,
            ],
            'purpose' => [
                'type'       => 'VARCHAR',
                'constraint' => 50,
                'default'    => 'device_verify',
            ],
            'ip_address' => [
                'type'       => 'VARCHAR',
                'constraint' => 45,
                'null'       => true,
            ],
            'attempts' => [
                'type'    => 'TINYINT',
                'constraint' => 3,
                'default' => 0,
            ],
            'is_used' => [
                'type'    => 'TINYINT',
                'constraint' => 1,
                'default' => 0,
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            // OTP expired setelah 10 menit
            'expires_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
        ]);

        $this->forge->addPrimaryKey('id');
        $this->forge->addKey('user_id');
        $this->forge->createTable('superadmin_otp_codes');
    }

    public function down(): void
    {
        $this->forge->dropTable('superadmin_device_tokens', true);
        $this->forge->dropTable('superadmin_otp_codes', true);
    }
}
