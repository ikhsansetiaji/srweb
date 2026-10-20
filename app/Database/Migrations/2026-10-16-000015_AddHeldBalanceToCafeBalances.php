<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddHeldBalanceToCafeBalances extends Migration
{
    public function up()
    {
        // Add held_balance column — saldo yang sedang dialokasikan untuk withdrawal pending/approved/processing
        $fields = [
            'held_balance' => [
                'type'    => 'BIGINT',
                'default' => 0,
                'null'    => false,
                'after'   => 'available_balance',
            ],
        ];

        $this->forge->addColumn('cafe_balances', $fields);

        // Ensure available_balance >= 0 constraint
        $this->db->query('ALTER TABLE cafe_balances ADD CONSTRAINT chk_available_balance_non_negative CHECK (available_balance >= 0)');
        $this->db->query('ALTER TABLE cafe_balances ADD CONSTRAINT chk_held_balance_non_negative CHECK (held_balance >= 0)');
    }

    public function down()
    {
        $this->db->query('ALTER TABLE cafe_balances DROP CONSTRAINT IF EXISTS chk_available_balance_non_negative');
        $this->db->query('ALTER TABLE cafe_balances DROP CONSTRAINT IF EXISTS chk_held_balance_non_negative');
        $this->forge->dropColumn('cafe_balances', 'held_balance');
    }
}
