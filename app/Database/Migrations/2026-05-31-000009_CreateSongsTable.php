<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateSongsTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id' => [
                'type' => 'INT',
                'auto_increment' => true,
            ],
            'api_song_id' => [
                'type' => 'VARCHAR',
                'constraint' => 100,
                'null' => false,
                'unique' => true,
            ],
            'title' => [
                'type' => 'VARCHAR',
                'constraint' => 150,
                'null' => false,
            ],
            'artist' => [
                'type' => 'VARCHAR',
                'constraint' => 150,
                'null' => false,
            ],
            'album' => [
                'type' => 'VARCHAR',
                'constraint' => 150,
                'null' => true,
            ],
            'duration' => [
                'type' => 'INT',
                'null' => false,
            ],
            'thumbnail' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'spotify_url' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'preview_url' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'created_at' => [
                'type' => 'TIMESTAMP',
                'default' => 'CURRENT_TIMESTAMP',
                'null' => false,
            ],
        ]);

        $this->forge->addKey('id', true);
        $this->forge->createTable('songs');

        // Add indexes for search
        $this->db->query('CREATE INDEX idx_songs_title ON songs(title)');
        $this->db->query('CREATE INDEX idx_songs_artist ON songs(artist)');
    }

    public function down()
    {
        $this->forge->dropTable('songs');
    }
}

