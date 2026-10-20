<?php

namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;
use App\Services\WhatsAppNotificationService;

/**
 * WhatsApp Notification Retry Worker
 *
 * CLI command untuk memproses notifikasi WhatsApp yang belum terkirim.
 *
 * Cara menjalankan (dari root project):
 *   php spark wa:notify-retry
 *   php spark wa:notify-retry --limit=50
 *
 * Cron job (jalankan setiap menit):
 *   * * * * * /path/to/php /path/to/project/spark wa:notify-retry >> /dev/null 2>&1
 *
 * PENTING — Keterbatasan tanpa queue system formal:
 * - Jika PHP-CLI tidak tersedia di server shared hosting, gunakan endpoint HTTP
 *   internal yang dipanggil dari cron (dengan basic auth atau IP whitelist).
 * - Worker ini AMAN dijalankan paralel: idempotensi via DB transaction FOR UPDATE
 *   mencegah pengiriman ganda, TAPI ada risiko kecil race condition jika
 *   dua worker memulai pengiriman ke Fonnte hampir bersamaan sebelum status
 *   diupdate. Untuk menghindarinya, jalankan hanya satu worker per waktu.
 * - Pesan tidak dijamin terkirim tepat sekali ke WhatsApp jika Fonnte menerima
 *   request tetapi response timeout sebelum status disimpan.
 *   Dokumentasikan keterbatasan ini kepada operator sistem.
 */
class WaNotifyRetry extends BaseCommand
{
    protected $group       = 'WhatsApp';
    protected $name        = 'wa:notify-retry';
    protected $description = 'Proses retry notifikasi WhatsApp yang belum terkirim.';

    protected $arguments = [];

    protected $options = [
        '--limit' => 'Maksimum notifikasi yang diproses per run (default: 20)',
        '--dry-run' => 'Tampilkan antrian tanpa mengirim (untuk debugging)',
    ];

    public function run(array $params)
    {
        $limit  = (int) CLI::getOption('limit')  ?: 20;
        $dryRun = (bool) CLI::getOption('dry-run') ?: false;

        CLI::write('[WA Worker] ' . date('Y-m-d H:i:s'), 'yellow');

        if (!filter_var(env('FONNTE_ENABLED', 'false'), FILTER_VALIDATE_BOOLEAN)) {
            CLI::write('[WA Worker] FONNTE_ENABLED=false, skip.', 'light_gray');
            return;
        }

        $waService = new WhatsAppNotificationService();

        if ($dryRun) {
            $logModel = new \App\Models\NotificationLogModel();
            $pending  = $logModel->getPendingForRetry(
                (int) env('FONNTE_MAX_RETRIES', 3),
                (int) env('FONNTE_RETRY_DELAY', 60),
                $limit
            );

            CLI::write("[WA Worker] DRY RUN — {$limit} notifikasi pending:", 'cyan');
            foreach ($pending as $n) {
                CLI::write(sprintf(
                    '  [#%d] event=%s entity=%s#%d attempts=%d status=%s',
                    $n['id'],
                    $n['event_type'],
                    $n['entity_type'] ?? '-',
                    $n['entity_id'] ?? 0,
                    $n['attempt_count'],
                    $n['status']
                ), 'light_gray');
            }
            return;
        }

        $stats = $waService->processRetryQueue($limit);

        CLI::write(sprintf(
            '[WA Worker] Selesai: processed=%d sent=%d failed=%d',
            $stats['processed'],
            $stats['sent'],
            $stats['failed']
        ), $stats['failed'] > 0 ? 'red' : 'green');

        if ($stats['sent'] > 0) {
            log_message('info', "[WA Worker] Sent={$stats['sent']} Failed={$stats['failed']} Processed={$stats['processed']}");
        }
    }
}
