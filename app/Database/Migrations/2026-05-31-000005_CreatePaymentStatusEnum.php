<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreatePaymentStatusEnum extends Migration
{
    public function up()
    {
        $this->db->query("CREATE TYPE payment_status_enum AS ENUM ('pending', 'success', 'failed', 'expired')");
    }

    public function down()
    {
        $this->db->query("DROP TYPE IF EXISTS payment_status_enum");
    }
}

