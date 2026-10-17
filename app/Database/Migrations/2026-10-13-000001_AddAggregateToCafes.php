<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddAggregateToCafes extends Migration
{
    public function up()
    {
        // Add aggregate_enabled boolean column to cafes
        $fields = [
            'aggregate_enabled' => [
                'type' => 'BOOLEAN',
                'default' => false,
                'null' => false,
            ],
        ];

        $this->forge->addColumn('cafes', $fields);
    }

    public function down()
    {
        $this->forge->dropColumn('cafes', 'aggregate_enabled');
    }
}
