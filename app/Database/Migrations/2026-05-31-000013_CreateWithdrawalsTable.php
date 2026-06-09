<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateWithdrawalsTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id' => [
                'type' => 'INT',
                'auto_increment' => true,
            ],
            'cafe_id' => [
                'type' => 'INT',
                'null' => false,
            ],
            'amount' => [
                'type' => 'BIGINT',
                'null' => false,
            ],
            'bank_name' => [
                'type' => 'VARCHAR',
                'constraint' => 100,
                'null' => false,
            ],
            'account_number' => [
                'type' => 'VARCHAR',
                'constraint' => 50,
                'null' => false,
            ],
            'account_holder' => [
                'type' => 'VARCHAR',
                'constraint' => 100,
                'null' => false,
            ],
            'status' => [
                'type' => 'withdrawal_status',
                'default' => 'pending',
                'null' => false,
            ],
            'approved_by' => [
                'type' => 'INT',
                'null' => true,
            ],
            'approved_at' => [
                'type' => 'TIMESTAMP',
                'null' => true,
            ],
            'rejection_reason' => [
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
        $this->forge->addForeignKey('cafe_id', 'cafes', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('approved_by', 'users', 'id', 'SET NULL', 'CASCADE');
        $this->forge->createTable('withdrawals');

        // Add constraint to ensure amount > 0
        $this->db->query('ALTER TABLE withdrawals ADD CONSTRAINT chk_withdrawal_amount CHECK (amount > 0)');

        // Add indexes for performance
        $this->db->query('CREATE INDEX idx_withdrawals_status ON withdrawals(status)');
        $this->db->query('CREATE INDEX idx_withdrawals_cafe ON withdrawals(cafe_id)');
        $this->db->query('CREATE INDEX idx_withdrawals_created_at ON withdrawals(created_at)');
    }

    public function down()
    {
        $this->forge->dropTable('withdrawals');
    }
}

