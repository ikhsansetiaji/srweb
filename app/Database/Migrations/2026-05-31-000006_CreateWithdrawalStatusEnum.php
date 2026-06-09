<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateWithdrawalStatusEnum extends Migration
{
    public function up()
    {
        $this->db->query("CREATE TYPE withdrawal_status AS ENUM ('pending', 'approved', 'rejected', 'paid')");
    }

    public function down()
    {
        $this->db->query("DROP TYPE IF EXISTS withdrawal_status");
    }
}

