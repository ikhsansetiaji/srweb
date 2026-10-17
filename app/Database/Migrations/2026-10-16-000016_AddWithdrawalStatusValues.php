<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddWithdrawalStatusValues extends Migration
{
    public function up()
    {
        // PostgreSQL: tambah nilai baru ke enum type withdrawal_status
        // Status baru: processing, completed, failed, cancelled
        // Status lama (dipertahankan): pending, approved, rejected, paid
        $this->db->query("ALTER TYPE withdrawal_status ADD VALUE IF NOT EXISTS 'processing'");
        $this->db->query("ALTER TYPE withdrawal_status ADD VALUE IF NOT EXISTS 'completed'");
        $this->db->query("ALTER TYPE withdrawal_status ADD VALUE IF NOT EXISTS 'failed'");
        $this->db->query("ALTER TYPE withdrawal_status ADD VALUE IF NOT EXISTS 'cancelled'");
    }

    public function down()
    {
        // PostgreSQL tidak mendukung DROP VALUE dari enum secara langsung.
        // Jika diperlukan rollback, harus recreate enum — tidak diimplementasi
        // karena berisiko merusak data.
        log_message('warning', 'AddWithdrawalStatusValues::down() tidak dapat menghapus enum values di PostgreSQL. Manual action required.');
    }
}
