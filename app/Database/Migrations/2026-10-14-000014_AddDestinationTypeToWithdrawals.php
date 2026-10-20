<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddDestinationTypeToWithdrawals extends Migration
{
    public function up()
    {
        $fields = [
            'destination_type' => [
                'type' => 'VARCHAR',
                'constraint' => 20,
                'default' => 'bank_transfer',
                'null' => false,
                'after' => 'cafe_id',
            ],
        ];

        $this->forge->addColumn('withdrawals', $fields);
    }

    public function down()
    {
        $this->forge->dropColumn('withdrawals', 'destination_type');
    }
}
