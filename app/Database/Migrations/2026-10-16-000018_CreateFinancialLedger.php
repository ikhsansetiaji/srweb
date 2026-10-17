<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateFinancialLedger extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id' => [
                'type'           => 'BIGINT',
                'auto_increment' => true,
            ],
            'cafe_id' => [
                'type' => 'INT',
                'null' => false,
            ],
            /**
             * Jenis mutasi:
             *   payment_income   — pendapatan dari pembayaran request lagu yang berhasil
             *   withdrawal_hold  — saldo ditahan saat pengajuan withdrawal dibuat
             *   withdrawal_release — saldo dikembalikan saat withdrawal ditolak/dibatalkan/gagal
             *   withdrawal_complete — saldo benar-benar dikurangi setelah transfer selesai
             *   correction_credit — koreksi kredit oleh superadmin (dengan referensi ke entri asal)
             *   correction_debit  — koreksi debit oleh superadmin (dengan referensi ke entri asal)
             */
            'entry_type' => [
                'type'       => 'VARCHAR',
                'constraint' => 50,
                'null'       => false,
            ],
            // Debit: uang keluar dari saldo kafe
            'debit' => [
                'type'    => 'BIGINT',
                'default' => 0,
                'null'    => false,
            ],
            // Credit: uang masuk ke saldo kafe
            'credit' => [
                'type'    => 'BIGINT',
                'default' => 0,
                'null'    => false,
            ],
            // Saldo available_balance SETELAH entri ini
            'balance_after' => [
                'type' => 'BIGINT',
                'null' => false,
            ],
            // 'payment', 'withdrawal', 'manual'
            'reference_type' => [
                'type'       => 'VARCHAR',
                'constraint' => 50,
                'null'       => true,
            ],
            // ID payment atau withdrawal terkait
            'reference_id' => [
                'type' => 'INT',
                'null' => true,
            ],
            // User yang memicu aksi (admin kafe untuk request, superadmin untuk approval)
            'actor_id' => [
                'type' => 'INT',
                'null' => true,
            ],
            'notes' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            // Referensi ke entri ledger yang dikoreksi (untuk reversal/koreksi)
            'correction_of' => [
                'type' => 'BIGINT',
                'null' => true,
            ],
            'created_at' => [
                'type'    => 'TIMESTAMP',
                'default' => 'CURRENT_TIMESTAMP',
                'null'    => false,
            ],
        ]);

        $this->forge->addKey('id', true);
        $this->forge->addForeignKey('cafe_id', 'cafes', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('actor_id', 'users', 'id', 'SET NULL', 'CASCADE');
        $this->forge->createTable('financial_ledger');

        // Self-referencing FK untuk correction_of
        $this->db->query('ALTER TABLE financial_ledger ADD CONSTRAINT fk_ledger_correction_of FOREIGN KEY (correction_of) REFERENCES financial_ledger(id) ON DELETE SET NULL');

        // Constraint: debit dan credit tidak boleh keduanya 0
        $this->db->query('ALTER TABLE financial_ledger ADD CONSTRAINT chk_ledger_non_zero CHECK (debit > 0 OR credit > 0)');
        // Constraint: tidak boleh keduanya ada nilai sekaligus (entri debit atau credit saja)
        $this->db->query('ALTER TABLE financial_ledger ADD CONSTRAINT chk_ledger_exclusive CHECK (NOT (debit > 0 AND credit > 0))');

        // Indexes untuk query umum
        $this->db->query('CREATE INDEX idx_ledger_cafe ON financial_ledger(cafe_id)');
        $this->db->query('CREATE INDEX idx_ledger_reference ON financial_ledger(reference_type, reference_id)');
        $this->db->query('CREATE INDEX idx_ledger_created ON financial_ledger(created_at DESC)');
        $this->db->query('CREATE INDEX idx_ledger_type ON financial_ledger(entry_type)');
    }

    public function down()
    {
        $this->forge->dropTable('financial_ledger');
    }
}
