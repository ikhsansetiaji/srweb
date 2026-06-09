<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreatePaymentsTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id' => [
                'type' => 'INT',
                'auto_increment' => true,
            ],
            'request_id' => [
                'type' => 'INT',
                'null' => false,
                'unique' => true,
            ],
            'cafe_id' => [
                'type' => 'INT',
                'null' => false,
            ],
            'payment_method' => [
                'type' => 'VARCHAR',
                'constraint' => 50,
                'null' => false,
            ],
            'transaction_id' => [
                'type' => 'VARCHAR',
                'constraint' => 100,
                'null' => true,
                'unique' => true,
            ],
            'external_reference' => [
                'type' => 'VARCHAR',
                'constraint' => 100,
                'null' => true,
                'unique' => true,
            ],
            'amount' => [
                'type' => 'BIGINT',
                'null' => false,
            ],
            'payment_status' => [
                'type' => 'payment_status_enum',
                'default' => 'pending',
                'null' => false,
            ],
            'paid_at' => [
                'type' => 'TIMESTAMP',
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
        $this->forge->addForeignKey('request_id', 'song_requests', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('cafe_id', 'cafes', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('payments');

        // Add indexes for payment tracking
        $this->db->query('CREATE INDEX idx_payments_status ON payments(payment_status)');
        $this->db->query('CREATE INDEX idx_payments_cafe ON payments(cafe_id)');
    }

    public function down()
    {
        $this->forge->dropTable('payments');
    }
}

