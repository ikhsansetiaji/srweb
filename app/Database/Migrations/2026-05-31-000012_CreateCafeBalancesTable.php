<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateCafeBalancesTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'cafe_id' => [
                'type' => 'INT',
                'null' => false,
                'primary_key' => true,
            ],
            'available_balance' => [
                'type' => 'BIGINT',
                'default' => 0,
                'null' => false,
            ],
            'total_income' => [
                'type' => 'BIGINT',
                'default' => 0,
                'null' => false,
            ],
            'total_withdrawn' => [
                'type' => 'BIGINT',
                'default' => 0,
                'null' => false,
            ],
            'updated_at' => [
                'type' => 'TIMESTAMP',
                'default' => 'CURRENT_TIMESTAMP',
                'null' => false,
            ],
        ]);

        $this->forge->createTable('cafe_balances');

        // Add foreign key
        $this->db->query('ALTER TABLE cafe_balances ADD CONSTRAINT fk_balance_cafe FOREIGN KEY (cafe_id) REFERENCES cafes(id) ON DELETE CASCADE');
    }

    public function down()
    {
        $this->forge->dropTable('cafe_balances');
    }
}

