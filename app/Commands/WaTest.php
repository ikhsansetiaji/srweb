<?php

namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;
use App\Services\FonnteService;
use App\Services\WhatsAppNotificationService;
use App\Models\NotificationLogModel;

/**
 * WhatsApp Diagnostics & Test Command
 *
 * Digunakan untuk memvalidasi konfigurasi Fonnte, status koneksi device WhatsApp,
 * status tabel migrasi DB, dan melakukan pengujian kirim pesan langsung.
 *
 * Penggunaan:
 *   php spark wa:test
 *   php spark wa:test --target=081234567890
 *   php spark wa:test --target=081234567890 --msg="Halo dari Song Request Small Space"
 */
class WaTest extends BaseCommand
{
    protected $group       = 'WhatsApp';
    protected $name        = 'wa:test';
    protected $description = 'Uji konfigurasi Fonnte, status device, dan kirim pesan test WhatsApp.';

    protected $arguments = [];

    protected $options = [
        '--target'       => 'Nomor WhatsApp tujuan untuk test kirim (contoh: 081234567890)',
        '--msg'          => 'Pesan kustom untuk dikirim (opsional)',
        '--test-request' => 'ID request lagu untuk uji kirim notifikasi',
        '--req'          => 'ID request lagu untuk uji kirim notifikasi (alias)',
    ];

    public function run(array $params)
    {
        CLI::write("==================================================", 'cyan');
        CLI::write("   DIAGNOSTIK & TEST WHATSAPP (FONNTE)", 'yellow');
        CLI::write("==================================================", 'cyan');

        // 1. Cek Konfigurasi Environment
        $enabled = filter_var(env('FONNTE_ENABLED', 'false'), FILTER_VALIDATE_BOOLEAN);
        $token   = trim((string) env('FONNTE_TOKEN', ''));
        $apiUrl  = trim((string) env('FONNTE_API_URL', 'https://api.fonnte.com/send'));

        CLI::write("\n[1] Konfigurasi .env:", 'yellow');
        CLI::write("  - FONNTE_ENABLED : " . ($enabled ? "TRUE (Aktif)" : "FALSE (Nonaktif)"), $enabled ? 'green' : 'red');
        
        $tokenDisplay = '(KOSONG / BELUM DIISI)';
        if ($token !== '') {
            $len = strlen($token);
            $tokenDisplay = ($len > 8) ? substr($token, 0, 4) . str_repeat('*', $len - 8) . substr($token, -4) : '****';
        }
        CLI::write("  - FONNTE_TOKEN   : " . $tokenDisplay, ($token !== '') ? 'green' : 'red');
        CLI::write("  - FONNTE_API_URL : " . $apiUrl, 'light_gray');

        // 2. Cek Database & Tabel
        CLI::write("\n[2] Status Database & Tabel:", 'yellow');
        $db = \Config\Database::connect();

        $doMigrate = (bool) CLI::getOption('migrate');
        if ($doMigrate) {
            CLI::write("  -> Memeriksa status migrasi di database...", 'yellow');
            try {
                // Jalankan query langsung untuk menambahkan kolom dan tabel WA jika migrasi umum tersendat
                $migRows = $db->table('migrations')->get()->getResultArray();
                CLI::write("  -> Ditemukan " . count($migRows) . " entri di tabel migrations.", 'light_gray');
            } catch (\Throwable $e) {
                CLI::write("  -> Gagal membaca tabel migrations: " . $e->getMessage(), 'red');
            }

            CLI::write("  -> Menjalankan migrasi WhatsApp spesifik...", 'yellow');
            try {
                $migPath = APPPATH . 'Database/Migrations/';
                require_once $migPath . '2026-10-16-000019_AddWhatsAppFieldsToSongRequests.php';
                require_once $migPath . '2026-10-16-000020_AddWhatsAppFieldsToUsers.php';
                require_once $migPath . '2026-10-16-000021_CreateWhatsAppNotificationsTable.php';
                require_once $migPath . '2026-10-16-000022_AddPhoneToUsers.php';

                // 1. Add fields to song_requests
                if (!$db->fieldExists('phone_wa', 'song_requests')) {
                    $m19 = new \App\Database\Migrations\AddWhatsAppFieldsToSongRequests();
                    $m19->up();
                    CLI::write("  -> [OK] Kolom WhatsApp di song_requests berhasil ditambahkan.", 'green');
                }
                // 2. Add fields to users
                if (!$db->fieldExists('phone_wa', 'users')) {
                    $m20 = new \App\Database\Migrations\AddWhatsAppFieldsToUsers();
                    $m20->up();
                    CLI::write("  -> [OK] Kolom WhatsApp di users berhasil ditambahkan.", 'green');
                }
                // 3. Create whatsapp_notifications table
                if (!$db->tableExists('whatsapp_notifications')) {
                    $m21 = new \App\Database\Migrations\CreateWhatsAppNotificationsTable();
                    $m21->up();
                    CLI::write("  -> [OK] Tabel whatsapp_notifications berhasil dibuat.", 'green');
                }
                // 4. Add phone to users
                if (!$db->fieldExists('phone', 'users')) {
                    $m22 = new \App\Database\Migrations\AddPhoneToUsers();
                    $m22->up();
                    CLI::write("  -> [OK] Kolom phone di users berhasil ditambahkan.", 'green');
                }

                // Catat ke tabel migrations CI4 agar tercatat rapi
                $waMigrations = [
                    'App\Database\Migrations\AddWhatsAppFieldsToSongRequests',
                    'App\Database\Migrations\AddWhatsAppFieldsToUsers',
                    'App\Database\Migrations\CreateWhatsAppNotificationsTable',
                    'App\Database\Migrations\AddPhoneToUsers',
                ];
                $maxBatch = (int) ($db->table('migrations')->selectMax('batch')->get()->getRow()->batch ?? 1);
                foreach ($waMigrations as $migClass) {
                    $exists = $db->table('migrations')->where('class', $migClass)->countAllResults();
                    if ($exists === 0) {
                        $db->table('migrations')->insert([
                            'version'   => substr(basename(str_replace('\\', '/', $migClass)), 0, 14) ?: date('Y-m-d-His'),
                            'class'     => $migClass,
                            'group'     => 'default',
                            'namespace' => 'App',
                            'time'      => time(),
                            'batch'     => $maxBatch + 1,
                        ]);
                    }
                }

                CLI::write("  -> Migrasi WhatsApp & Phone sukses!", 'green');
            } catch (\Throwable $e) {
                CLI::write("  -> Migrasi gagal: " . $e->getMessage(), 'red');
            }
        }
        
        $hasLogTable = $db->tableExists('whatsapp_notifications');
        CLI::write("  - Tabel 'whatsapp_notifications' : " . ($hasLogTable ? 'ADA (OK)' : 'BELUM ADA (Perlu migrasi)'), $hasLogTable ? 'green' : 'red');

        $hasSrPhone = $db->fieldExists('phone_wa', 'song_requests');
        $hasSrConsent = $db->fieldExists('wa_consent', 'song_requests');
        CLI::write("  - Kolom 'song_requests.phone_wa'    : " . ($hasSrPhone ? 'ADA (OK)' : 'BELUM ADA'), $hasSrPhone ? 'green' : 'red');
        CLI::write("  - Kolom 'song_requests.wa_consent'  : " . ($hasSrConsent ? 'ADA (OK)' : 'BELUM ADA'), $hasSrConsent ? 'green' : 'red');

        $hasUserGeneralPhone = $db->fieldExists('phone', 'users');
        $hasUserPhone = $db->fieldExists('phone_wa', 'users');
        $hasUserNotif = $db->fieldExists('wa_notif_enabled', 'users');
        CLI::write("  - Kolom 'users.phone'              : " . ($hasUserGeneralPhone ? 'ADA (OK)' : 'BELUM ADA (Perlu migrasi)'), $hasUserGeneralPhone ? 'green' : 'red');
        CLI::write("  - Kolom 'users.phone_wa'           : " . ($hasUserPhone ? 'ADA (OK)' : 'BELUM ADA'), $hasUserPhone ? 'green' : 'red');
        CLI::write("  - Kolom 'users.wa_notif_enabled'   : " . ($hasUserNotif ? 'ADA (OK)' : 'BELUM ADA'), $hasUserNotif ? 'green' : 'red');

        // 2.5 Cek 5 Song Request & Notifikasi Terbaru
        CLI::write("\n[2.5] 5 Request Lagu Terakhir di Database:", 'yellow');
        $recentRequests = $db->table('song_requests')
            ->select('id, guest_name, user_id, queue_type, phone_wa, wa_consent, status, requested_at')
            ->orderBy('id', 'DESC')
            ->limit(5)
            ->get()
            ->getResultArray();

        if (empty($recentRequests)) {
            CLI::write("  Belum ada song request di database.", 'light_gray');
        } else {
            foreach ($recentRequests as $sr) {
                CLI::write(sprintf(
                    "  #%d | UserID: %s | Nama: %s | Queue: %s | PhoneWA: %s | Consent: %s | Status: %s",
                    $sr['id'],
                    $sr['user_id'] ?? 'guest',
                    $sr['guest_name'],
                    $sr['queue_type'],
                    $sr['phone_wa'] ?? '(KOSONG)',
                    var_export($sr['wa_consent'], true),
                    $sr['status']
                ), 'light_cyan');
            }
        }

        CLI::write("\n[2.6] 5 Riwayat Log whatsapp_notifications Terakhir:", 'yellow');
        $recentLogs = $db->table('whatsapp_notifications')
            ->select('id, idempotency_key, event_type, recipient_masked, status, error_summary, response_code, created_at')
            ->orderBy('id', 'DESC')
            ->limit(5)
            ->get()
            ->getResultArray();

        if (empty($recentLogs)) {
            CLI::write("  Belum ada log notifikasi WhatsApp.", 'light_gray');
        } else {
            foreach ($recentLogs as $log) {
                $statusColor = ($log['status'] === 'sent') ? 'green' : (($log['status'] === 'skipped') ? 'yellow' : 'red');
                CLI::write(sprintf(
                    "  [#%d] %s | Event: %s | To: %s | Status: %s | Err: %s",
                    $log['id'],
                    $log['idempotency_key'],
                    $log['event_type'],
                    $log['recipient_masked'] ?? '-',
                    $log['status'],
                    $log['error_summary'] ?? '-'
                ), $statusColor);
            }
        }

        // 3. Cek Status Device di Fonnte API
        CLI::write("\n[3] Cek Koneksi & Device ke Fonnte API:", 'yellow');
        if (empty($token)) {
            CLI::write("  [!] Token Fonnte kosong. Tidak dapat memeriksa status device.", 'red');
        } else {
            $deviceInfo = $this->checkFonnteDevice($token);
            if ($deviceInfo['success']) {
                $data = $deviceInfo['data'] ?? [];
                CLI::write("  - HTTP Status   : " . ($deviceInfo['http_code'] ?? 200) . " OK", 'green');
                CLI::write("  - Status Device : " . ($data['device_status'] ?? ($data['status'] ?? 'OK')), 'green');
                if (isset($data['device'])) {
                    CLI::write("  - Nomor Device  : " . $data['device'], 'light_cyan');
                }
                if (isset($data['name'])) {
                    CLI::write("  - Nama Akun     : " . $data['name'], 'light_cyan');
                }
                if (isset($data['quota'])) {
                    CLI::write("  - Sisa Kuota    : " . $data['quota'], 'light_cyan');
                }
                if (isset($data['expired'])) {
                    CLI::write("  - Masa Berlaku  : " . $data['expired'], 'light_cyan');
                }
            } else {
                CLI::write("  [!] Gagal menghubungi Fonnte / Token tidak valid:", 'red');
                CLI::write("      " . ($deviceInfo['error'] ?? 'Unknown error'), 'light_red');
            }
        }

        // 4. Test Kirim Pesan jika --target diberikan atau parameter posisi
        $target = CLI::getOption('target') ?: ($params['target'] ?? ($params[0] ?? null));
        if (!empty($target)) {
            CLI::write("\n[4] Pengujian Kirim Pesan WhatsApp Langsung:", 'yellow');
            CLI::write("  - Target Nomor : {$target}", 'light_gray');

            $fonnteService = new FonnteService();
            $customMsg = CLI::getOption('msg') ?: ($params['msg'] ?? null);
            $testMsg = $customMsg ?: ("*Song Request Kafe Small Space*\n\n"
                . "Halo! Ini adalah pesan uji coba integrasi WhatsApp via Fonnte.\n"
                . "Waktu: " . date('d M Y H:i:s') . "\n"
                . "Status: Integrasi Berhasil & Aktif! 🎵☕");

            CLI::write("  - Mengirim pesan...", 'yellow');
            $res = $fonnteService->sendMessage($target, $testMsg);

            if ($res['success']) {
                CLI::write("  [BERHASIL] Pesan berhasil dikirim ke Fonnte!", 'green');
                CLI::write("  - Provider Message ID : " . ($res['provider_id'] ?? '-'), 'light_cyan');
                CLI::write("  - HTTP Code           : " . $res['http_code'], 'light_cyan');

                // Catat ke log DB jika tabel tersedia
                if ($hasLogTable) {
                    $logModel = new NotificationLogModel();
                    $normPhone = $fonnteService->normalizePhone($target) ?? $target;
                    $logModel->insert([
                        'idempotency_key'     => 'test_ping:' . time() . ':' . substr(md5($normPhone), 0, 6),
                        'event_type'          => 'test_ping',
                        'recipient_type'      => 'system_test',
                        'recipient_masked'    => NotificationLogModel::maskPhone($normPhone),
                        'entity_type'         => 'system',
                        'entity_id'           => 1,
                        'status'              => NotificationLogModel::STATUS_SENT,
                        'attempt_count'       => 1,
                        'provider_message_id' => $res['provider_id'],
                        'response_code'       => $res['http_code'],
                        'sent_at'             => date('Y-m-d H:i:s'),
                        'created_at'          => date('Y-m-d H:i:s'),
                        'updated_at'          => date('Y-m-d H:i:s'),
                    ]);
                    CLI::write("  - Log tersimpan di database: whatsapp_notifications", 'green');
                }
            } else {
                CLI::write("  [GAGAL] Pengiriman pesan gagal:", 'red');
                CLI::write("  - Status    : " . ($res['status'] ?? '-'), 'light_red');
                CLI::write("  - HTTP Code : " . ($res['http_code'] ?? 0), 'light_red');
                CLI::write("  - Error     : " . ($res['error'] ?? 'Tidak ada pesan error'), 'light_red');
            }
        } else {
            CLI::write("\n[Tips Pengujian Kirim Pesan]", 'light_gray');
            CLI::write("Untuk menguji pengiriman pesan nyata ke WhatsApp Anda, jalankan:");
            CLI::write("  php spark wa:test --target=08xxxxxxxxxx\n", 'cyan');
        }

        // 5. Test Notifikasi untuk Song Request tertentu
        $testReqId = (int) (CLI::getOption('req') ?: (CLI::getOption('test-request') ?: ($params['req'] ?? ($params['test-request'] ?? 0))));
        if ($testReqId > 0) {
            CLI::write("\n[5] Uji Notifikasi WhatsApp untuk Request Lagu #{$testReqId}:", 'yellow');
            $waService = new \App\Services\WhatsAppNotificationService();
            $srModel = new \App\Models\SongRequestModel();
            $req = $srModel->find($testReqId);
            if (!$req) {
                CLI::write("  [!] Request #{$testReqId} tidak ditemukan di database.", 'red');
            } else {
                CLI::write("  - Judul / Guest : " . ($req['guest_name'] ?? 'Anonim') . " | Status: " . $req['status'], 'light_cyan');
                CLI::write("  - Phone WA      : " . ($req['phone_wa'] ?? '(KOSONG)'), 'light_cyan');
                CLI::write("  - Consent DB    : " . var_export($req['wa_consent'], true), 'light_cyan');

                // Bersihkan log skipped sebelumnya agar bisa dites ulang
                $db->table('whatsapp_notifications')
                    ->whereIn('idempotency_key', [
                        "request_queued:{$testReqId}",
                        "song_started:{$testReqId}",
                        "song_finished:{$testReqId}"
                    ])
                    ->where('status', 'skipped')
                    ->delete();

                CLI::write("  -> Memicu event onSongStarted(#{$testReqId})...", 'yellow');
                $waService->onSongStarted($testReqId);

                $log = $db->table('whatsapp_notifications')
                    ->where('idempotency_key', "song_started:{$testReqId}")
                    ->get()
                    ->getRowArray();

                if ($log) {
                    CLI::write("  -> Status Log: " . $log['status'] . " | ProviderID: " . ($log['provider_message_id'] ?? '-') . " | Err: " . ($log['error_summary'] ?? '-'), ($log['status'] === 'sent') ? 'green' : 'red');
                } else {
                    CLI::write("  -> Tidak ada log yang dibuat.", 'red');
                }
            }
        }

        CLI::write("==================================================\n", 'cyan');
    }

    /**
     * Panggil endpoint device Fonnte
     */
    protected function checkFonnteDevice(string $token): array
    {
        $ch = curl_init('https://api.fonnte.com/device');
        if ($ch === false) {
            return ['success' => false, 'error' => 'Gagal inisialisasi cURL'];
        }

        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_HTTPHEADER     => [
                'Authorization: ' . $token,
            ],
            CURLOPT_TIMEOUT        => 10,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => 0,
        ]);

        $response  = curl_exec($ch);
        $httpCode  = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        if ($curlError !== '') {
            return ['success' => false, 'error' => "cURL error: {$curlError}"];
        }

        $decoded = json_decode((string) $response, true);
        if ($httpCode === 200 && is_array($decoded)) {
            return [
                'success'   => true,
                'http_code' => $httpCode,
                'data'      => $decoded,
            ];
        }

        $reason = $decoded['reason'] ?? ($decoded['detail'] ?? ($decoded['message'] ?? 'Respons bukan status OK'));
        return [
            'success'   => false,
            'http_code' => $httpCode,
            'error'     => "HTTP {$httpCode}: {$reason}",
        ];
    }
}
