<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateRequestStatusEnum extends Migration
{
    public function up()
    {
        $this->db->query("CREATE TYPE request_status_enum AS ENUM ('waiting', 'playing', 'done', 'cancelled')");
    }

    public function down()
    {
        $this->db->query("DROP TYPE IF EXISTS request_status_enum");
    }
}

