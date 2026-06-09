<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;
use CodeIgniter\Database\RawSql;

class CreateUserRoleEnum extends Migration
{
    public function up()
    {
        // Create ENUM type for user_role
        $this->db->query("CREATE TYPE user_role AS ENUM ('user', 'admin', 'superadmin')");
    }

    public function down()
    {
        $this->db->query("DROP TYPE IF EXISTS user_role");
    }
}

