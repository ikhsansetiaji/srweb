<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddTransferColumnsToWithdrawals extends Migration
{
    public function up()
    {
        $fields = [
            'notes' => [
                'type' => 'TEXT',
                'null' => true,
                'after' => 'rejection_reason',
            ],
            'transfer_reference' => [
                'type'       => 'VARCHAR',
                'constraint' => 100,
                'null'       => true,
                'after'      => 'notes',
            ],
            'transfer_proof' => [
                'type' => 'TEXT',
                'null' => true,
                'after' => 'transfer_reference',
            ],
            'processed_by' => [
                'type' => 'INT',
                'null' => true,
                'after' => 'transfer_proof',
            ],
            'processed_at' => [
                'type' => 'TIMESTAMP',
                'null' => true,
                'after' => 'processed_by',
            ],
            'completed_at' => [
                'type' => 'TIMESTAMP',
                'null' => true,
                'after' => 'processed_at',
            ],
            'failure_reason' => [
                'type' => 'TEXT',
                'null' => true,
                'after' => 'completed_at',
            ],
        ];

        $this->forge->addColumn('withdrawals', $fields);

        // Add FK for processed_by
        $this->db->query('ALTER TABLE withdrawals ADD CONSTRAINT fk_wd_processed_by FOREIGN KEY (processed_by) REFERENCES users(id) ON DELETE SET NULL ON UPDATE CASCADE');

        // Add index on processed_by
        $this->db->query('CREATE INDEX idx_withdrawals_processed_by ON withdrawals(processed_by)');
    }

    public function down()
    {
        $this->db->query('ALTER TABLE withdrawals DROP CONSTRAINT IF EXISTS fk_wd_processed_by');
        $this->db->query('DROP INDEX IF EXISTS idx_withdrawals_processed_by');

        $cols = ['notes', 'transfer_reference', 'transfer_proof', 'processed_by', 'processed_at', 'completed_at', 'failure_reason'];
        foreach ($cols as $col) {
            $this->forge->dropColumn('withdrawals', $col);
        }
    }
}
