<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateUsersTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id' => [
                'type' => 'INT',
                'auto_increment' => true,
            ],
            'name' => [
                'type' => 'VARCHAR',
                'constraint' => 100,
                'null' => false,
            ],
            'username' => [
                'type' => 'VARCHAR',
                'constraint' => 50,
                'null' => true,
                'unique' => true,
            ],
            'email' => [
                'type' => 'VARCHAR',
                'constraint' => 100,
                'null' => false,
                'unique' => true,
            ],
            'password' => [
                'type' => 'TEXT',
                'null' => false,
            ],
            'role' => [
                'type' => 'user_role',
                'default' => 'user',
                'null' => false,
            ],
            'is_verified' => [
                'type' => 'BOOLEAN',
                'default' => false,
                'null' => false,
            ],
            'is_active' => [
                'type' => 'BOOLEAN',
                'default' => true,
                'null' => false,
            ],
            'last_login' => [
                'type' => 'TIMESTAMP',
                'null' => true,
            ],
            'created_at' => [
                'type' => 'TIMESTAMP',
                'default' => 'CURRENT_TIMESTAMP',
                'null' => false,
            ],
            'updated_at' => [
                'type' => 'TIMESTAMP',
                'null' => true,
            ],
        ]);

        $this->forge->addKey('id', true);
        $this->forge->createTable('users');
    }

    public function down()
    {
        $this->forge->dropTable('users');
    }
}

