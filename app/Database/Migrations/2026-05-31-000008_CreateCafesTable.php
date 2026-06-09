<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateCafesTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id' => [
                'type' => 'INT',
                'auto_increment' => true,
            ],
            'admin_id' => [
                'type' => 'INT',
                'null' => false,
            ],
            'nama_kafe' => [
                'type' => 'VARCHAR',
                'constraint' => 100,
                'null' => false,
            ],
            'slug' => [
                'type' => 'VARCHAR',
                'constraint' => 120,
                'null' => true,
                'unique' => true,
            ],
            'alamat' => [
                'type' => 'TEXT',
                'null' => false,
            ],
            'deskripsi' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'logo' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'phone_number' => [
                'type' => 'VARCHAR',
                'constraint' => 20,
                'null' => true,
            ],
            'status' => [
                'type' => 'cafe_status',
                'default' => 'pending',
                'null' => false,
            ],
            'is_active' => [
                'type' => 'BOOLEAN',
                'default' => false,
                'null' => false,
            ],
            'verified_by' => [
                'type' => 'INT',
                'null' => true,
            ],
            'verified_at' => [
                'type' => 'TIMESTAMP',
                'null' => true,
            ],
            'rejection_reason' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'payment_receiver' => [
                'type' => 'VARCHAR',
                'constraint' => 100,
                'null' => false,
            ],
            'payment_method' => [
                'type' => 'VARCHAR',
                'constraint' => 50,
                'null' => false,
            ],
            'payment_qris' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'payment_gate_token' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'created_at' => [
                'type' => 'TIMESTAMP',
                'default' => 'CURRENT_TIMESTAMP',
                'null' => false,
            ],
            'updated_at' => [
                'type' => 'TIMESTAMP',
                'null' => true,
            ],
        ]);

        $this->forge->addKey('id', true);
        $this->forge->addForeignKey('admin_id', 'users', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('verified_by', 'users', 'id', 'SET NULL', 'CASCADE');
        $this->forge->createTable('cafes');

        // Add indexes
        $this->db->query('CREATE INDEX idx_cafes_admin ON cafes(admin_id)');
        $this->db->query('CREATE INDEX idx_cafes_status ON cafes(status)');
    }

    public function down()
    {
        $this->forge->dropTable('cafes');
    }
}

