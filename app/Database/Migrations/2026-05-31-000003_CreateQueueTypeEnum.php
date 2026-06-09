<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateQueueTypeEnum extends Migration
{
    public function up()
    {
        $this->db->query("CREATE TYPE queue_type_enum AS ENUM ('priority', 'fifo')");
    }

    public function down()
    {
        $this->db->query("DROP TYPE IF EXISTS queue_type_enum");
    }
}

