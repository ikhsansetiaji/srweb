<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateCafeStatusEnum extends Migration
{
    public function up()
    {
        $this->db->query("CREATE TYPE cafe_status AS ENUM ('pending', 'approved', 'rejected')");
    }

    public function down()
    {
        $this->db->query("DROP TYPE IF EXISTS cafe_status");
    }
}

