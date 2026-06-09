<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateSongRequestsTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id' => [
                'type' => 'INT',
                'auto_increment' => true,
            ],
            'user_id' => [
                'type' => 'INT',
                'null' => true,
            ],
            'guest_name' => [
                'type' => 'VARCHAR',
                'constraint' => 100,
                'default' => 'Anonim',
                'null' => false,
            ],
            'cafe_id' => [
                'type' => 'INT',
                'null' => false,
            ],
            'song_id' => [
                'type' => 'INT',
                'null' => false,
            ],
            'nominal' => [
                'type' => 'BIGINT',
                'null' => false,
            ],
            'queue_type' => [
                'type' => 'queue_type_enum',
                'null' => false,
            ],
            'status' => [
                'type' => 'request_status_enum',
                'default' => 'waiting',
                'null' => false,
            ],
            'requested_at' => [
                'type' => 'TIMESTAMP',
                'default' => 'CURRENT_TIMESTAMP',
                'null' => false,
            ],
            'played_at' => [
                'type' => 'TIMESTAMP',
                'null' => true,
            ],
        ]);

        $this->forge->addKey('id', true);
        $this->forge->addForeignKey('user_id', 'users', 'id', 'SET NULL', 'CASCADE');
        $this->forge->addForeignKey('cafe_id', 'cafes', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('song_id', 'songs', 'id', 'RESTRICT', 'CASCADE');
        $this->forge->createTable('song_requests');

        // Critical index for queue algorithm: priority DESC, then requested_at ASC
        $this->db->query('CREATE INDEX idx_song_requests_queue ON song_requests(queue_type, nominal DESC, requested_at)');
        $this->db->query('CREATE INDEX idx_song_requests_status ON song_requests(status)');
        $this->db->query('CREATE INDEX idx_song_requests_cafe ON song_requests(cafe_id)');
    }

    public function down()
    {
        $this->forge->dropTable('song_requests');
    }
}

