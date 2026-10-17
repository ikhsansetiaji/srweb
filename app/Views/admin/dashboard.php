<?= $this->extend('layout/app') ?>

<?= $this->section('css') ?>
<style>
    .player-section {
        background: linear-gradient(135deg, #1a1a2e 0%, #16213e 50%, #0f3460 100%);
        border-radius: 16px;
        padding: 0;
        overflow: hidden;
        box-shadow: 0 8px 32px rgba(0,0,0,0.3);
    }
    .player-header {
        padding: 20px 24px;
        display: flex;
        justify-content: space-between;
        align-items: center;
        border-bottom: 1px solid rgba(255,255,255,0.08);
    }
    .player-header h5 {
        color: #fff;
        margin: 0;
        font-weight: 700;
    }
    .track-detail-card {
        background: rgba(255,255,255,0.04);
        border: 1px solid rgba(255,255,255,0.08);
        border-radius: 12px;
        padding: 20px;
        margin: 20px 24px 24px;
    }
    .track-detail-card .track-title { color: #fff; font-size: 1.25rem; font-weight: 700; margin-bottom: 2px; }
    .track-detail-card .track-artist { color: rgba(255,255,255,0.6); font-size: 0.95rem; }
    .track-detail-card .track-meta { color: rgba(255,255,255,0.45); font-size: 0.8rem; }
    .track-detail-card img { border-radius: 8px; box-shadow: 0 4px 16px rgba(0,0,0,0.4); }
    .badge-priority { background: linear-gradient(135deg, #e53935, #ff5252); color: #fff; }
    .badge-fifo { background: linear-gradient(135deg, #f9a825, #ffc107); color: #333; }
    .btn-action { border-radius: 8px; font-weight: 600; font-size: 0.82rem; padding: 8px 14px; }
    .btn-done { background: rgba(255,255,255,0.1); border: 1px solid rgba(255,255,255,0.2); color: #fff; }
    .btn-done:hover { background: rgba(255,82,82,0.3); border-color: #ff5252; color: #ff5252; }
    .btn-skip { background: rgba(255,255,255,0.08); border: 1px solid rgba(255,255,255,0.15); color: rgba(255,255,255,0.7); }
    .btn-skip:hover { background: rgba(255,255,255,0.15); color: #fff; }
    .btn-spotify-open { background: #1DB954; border: none; color: #fff; }
    .btn-spotify-open:hover { background: #1ed760; color: #fff; }
    .empty-player {
        display: flex; flex-direction: column; align-items: center; justify-content: center;
        min-height: 200px; color: rgba(255,255,255,0.4);
    }
    .empty-player .spotify-icon { font-size: 3rem; color: #1DB954; margin-bottom: 16px; }
    .empty-player p { margin: 0 0 16px; font-size: 1rem; }
    .btn-play-first {
        background: linear-gradient(135deg, #e53935, #ff5252);
        border: none; color: #fff; padding: 12px 32px; border-radius: 50px;
        font-weight: 700; font-size: 1rem; letter-spacing: 0.3px;
        box-shadow: 0 4px 20px rgba(229,57,53,0.4);
        transition: transform 0.15s, box-shadow 0.15s;
    }
    .btn-play-first:hover { transform: translateY(-2px); box-shadow: 0 6px 28px rgba(229,57,53,0.5); color: #fff; }
    .autoplay-timer {
        display: flex; align-items: center; gap: 8px;
        background: rgba(255,255,255,0.06); border-radius: 8px; padding: 6px 12px;
        font-size: 0.78rem; color: rgba(255,255,255,0.5);
    }
    .autoplay-timer .timer-bar {
        width: 60px; height: 4px; background: rgba(255,255,255,0.1); border-radius: 2px; overflow: hidden;
    }
    .autoplay-timer .timer-bar-fill {
        height: 100%; background: #1DB954; border-radius: 2px; transition: width 1s linear;
    }
    .queue-card { border: none; border-radius: 12px; box-shadow: 0 2px 12px rgba(0,0,0,0.06); }
    .queue-card .card-header { background: #fff; border-bottom: 1px solid #f0f0f0; border-radius: 12px 12px 0 0 !important; padding: 16px 20px; }
    .queue-item { border-radius: 8px !important; margin-bottom: 6px; border: 1px solid #f0f0f0 !important; transition: background 0.15s; }
    .queue-item:hover { background: #fafafa; }
    .stat-card { border: none; border-radius: 12px; box-shadow: 0 2px 12px rgba(0,0,0,0.06); transition: transform 0.15s; }
    .stat-card:hover { transform: translateY(-2px); }

    .finance-shell {
        background: #fff;
        border: 1px solid #eef2f7;
        border-radius: 16px;
        box-shadow: 0 2px 16px rgba(15, 23, 42, 0.06);
    }
    .finance-card {
        background: #fff;
        border: 1px solid #eef2f7;
        border-radius: 14px;
        box-shadow: 0 1px 8px rgba(15, 23, 42, 0.04);
    }
    .finance-mini {
        border-radius: 14px;
        padding: 16px 18px;
        background: linear-gradient(135deg, #f8fafc 0%, #ffffff 100%);
        border: 1px solid #eef2f7;
    }
    .finance-mini .label {
        color: #6b7280;
        font-size: 0.76rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.4px;
    }
    .finance-mini .value {
        font-size: 1.2rem;
        font-weight: 800;
        color: #111827;
        margin-top: 4px;
    }
    .finance-table {
        width: 100%;
    }
    .finance-table th {
        background: #f8fafc;
        color: #6b7280;
        font-size: 0.72rem;
        text-transform: uppercase;
        letter-spacing: 0.4px;
        padding: 10px 12px;
        border-bottom: 1px solid #eef2f7;
    }
    .finance-table td {
        padding: 11px 12px;
        border-bottom: 1px solid #f1f5f9;
        font-size: 0.84rem;
        vertical-align: middle;
    }
    .finance-badge {
        display: inline-block;
        padding: 3px 8px;
        border-radius: 999px;
        font-size: 0.7rem;
        font-weight: 700;
    }
    .finance-badge.success { background: #ecfdf5; color: #059669; }
    .finance-badge.pending { background: #fff7ed; color: #d97706; }
    .finance-badge.failed { background: #fef2f2; color: #dc2626; }
    .finance-badge.waiting { background: #eff6ff; color: #2563eb; }
    .finance-badge.done { background: #f0fdf4; color: #16a34a; }
    .finance-badge.approved { background: #ecfdf5; color: #059669; }
    .finance-badge.rejected { background: #fef2f2; color: #dc2626; }
    .finance-badge.processing { background: #fdf4ff; color: #9333ea; }
    .finance-badge.completed { background: #f0fdf4; color: #15803d; }
    .finance-badge.paid { background: #f0fdf4; color: #16a34a; }
    .finance-badge.cancelled { background: #f3f4f6; color: #6b7280; }
</style>
<?= $this->endSection() ?>

<?= $this->section('content') ?>



<div class="container-lg py-4">
    <!-- Compact Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="fw-bold mb-0">
                <i class="fas fa-store text-danger"></i> <?= esc($cafe['nama_kafe']) ?>
            </h4>
            <small class="text-muted">Dashboard • Kelola antrean dan putar lagu</small>
        </div>
        <div class="d-flex align-items-center gap-3">
            <span class="badge bg-light text-dark border px-3 py-2">
                <i class="fas fa-hourglass-half text-warning me-1"></i> Antrian: <strong id="statTotalWaiting"><?= esc($queue_stats['waiting_requests'] ?? 0) ?></strong>
            </span>

            <!-- Aggregate toggle -->
            <div class="form-check form-switch mb-0">
                <input class="form-check-input" type="checkbox" role="switch" id="aggregateSwitch" <?= (!empty($cafe['aggregate_enabled']) ? 'checked' : '') ?> onclick="toggleAggregate(event)">
                <label class="form-check-label small fw-bold" style="color: rgba(255,255,255,0.7);" for="aggregateSwitch">
                    <i class="fas fa-layer-group text-info me-1"></i> Aggregat
                </label>
                <div class="small text-muted" style="max-width: 260px;">
                    Prioritaskan lagu berdasarkan jumlah request dan total saweran.
                </div>
            </div>
        </div>
    </div>

    <!-- ==================== -->
    <!-- MAIN PLAYER SECTION  -->
    <!-- ==================== -->
    <div class="player-section mb-4">
        <div class="player-header">
            <div class="d-flex align-items-center gap-2">
                <h5><i class="fab fa-spotify text-success me-2"></i>Spotify Player</h5>
                <?php if (session()->has('spotify_access_token')): ?>
                    <span class="badge bg-success bg-opacity-25 text-success border border-success border-opacity-50 px-2 py-1" style="font-size: 0.7rem;">
                        <i class="fas fa-link me-1"></i> Connected
                    </span>
                    <a href="/spotify/disconnect" class="text-white-50 hover-white small text-decoration-none ms-1" title="Putuskan Hubungan Spotify">
                        <i class="fas fa-unlink"></i>
                    </a>
                <?php else: ?>
                    <a href="/spotify/connect" class="btn btn-sm btn-success px-3" style="font-size: 0.72rem; font-weight: 600; border-radius: 20px;">
                        <i class="fab fa-spotify me-1"></i> Hubungkan Spotify
                    </a>
                <?php endif; ?>
            </div>
            <div class="d-flex align-items-center gap-3">
                <!-- Auto-play duration timer -->
                <div id="autoplayTimerDisplay" class="autoplay-timer" style="display:none;">
                    <i class="fas fa-clock"></i>
                    <span id="autoplayTimeLeft">0:00</span>
                    <div class="timer-bar"><div class="timer-bar-fill" id="autoplayTimerBar" style="width:100%"></div></div>
                    <button class="btn btn-link text-white-50 p-0 ms-2 hover-white" onclick="resetDurationTimer(event)" title="Reset Timer / Ulangi Lagu">
                        <i class="fas fa-redo-alt" style="font-size: 0.72rem;"></i>
                    </button>
                </div>
                <div class="form-check form-switch mb-0">
                    <input class="form-check-input" type="checkbox" role="switch" id="autoPlaySwitch" checked>
                    <label class="form-check-label small fw-bold" style="color: rgba(255,255,255,0.7);" for="autoPlaySwitch">
                        <i class="fas fa-magic text-danger me-1"></i> Auto Play
                    </label>
                </div>
            </div>
        </div>

        <!-- Spotify Embed -->
        <div id="spotifyEmbedWrapper">
            <div id="spotifyPlaceholder" class="empty-player">
                <i class="fab fa-spotify spotify-icon"></i>
                <p>Belum ada lagu yang diputar</p>
                <button class="btn btn-play-first" onclick="playNext()">
                    <i class="fas fa-play me-2"></i> Putar Lagu Berikutnya
                </button>
            </div>
            <iframe id="spotifyEmbed" src="" width="100%" height="152" frameborder="0"
                allow="autoplay; clipboard-write; encrypted-media; fullscreen; picture-in-picture"
                loading="lazy" style="display:none;"></iframe>
        </div>

        <!-- Track Detail (inside player section) -->
        <div id="trackDetailSection" style="display:none;">
            <div class="track-detail-card" id="trackDetailCard">
                <!-- Filled by JS -->
            </div>
        </div>
    </div>

    <!-- ==================== -->
    <!-- QUEUE LISTS          -->
    <!-- ==================== -->
    <div class="row g-4 mb-4">
        <!-- Priority Queue -->
        <div class="col-md-6">
            <div class="card queue-card h-100">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <strong class="text-danger"><i class="fas fa-star me-1"></i> Antrian Prioritas</strong>
                    <span id="priorityQueueBadge" class="badge bg-danger rounded-pill">0</span>
                </div>
                <div class="card-body p-3" id="priorityQueueList" style="max-height: 380px; overflow-y: auto;">
                    <div class="text-center py-4 text-muted small">Antrean kosong</div>
                </div>
            </div>
        </div>
        <!-- FIFO Queue -->
        <div class="col-md-6">
            <div class="card queue-card h-100">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <strong class="text-warning"><i class="fas fa-list-ol me-1"></i> Antrian Regular (FIFO)</strong>
                    <span id="fifoQueueBadge" class="badge bg-warning text-dark rounded-pill">0</span>
                </div>
                <div class="card-body p-3" id="fifoQueueList" style="max-height: 380px; overflow-y: auto;">
                    <div class="text-center py-4 text-muted small">Antrean kosong</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Pending Payments (separate section) -->
    <div class="row g-4 mb-4">
        <div class="col-12">
            <div class="card queue-card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <strong class="text-secondary"><i class="fas fa-credit-card me-1"></i> Pending Payments</strong>
                    <span id="pendingPaymentBadge" class="badge bg-secondary rounded-pill">0</span>
                </div>
                <div class="card-body p-3" id="pendingPaymentList" style="max-height: 220px; overflow-y: auto;">
                    <div class="text-center py-3 text-muted small">Tidak ada request yang menunggu pembayaran</div>
                </div>
            </div>
        </div>
    </div>
    </div>

    <!-- ==================== -->
    <!-- STATS (secondary)    -->
    <!-- ==================== -->
    <div class="row g-3">
        <div class="col-12">
            <small class="text-muted fw-bold text-uppercase" style="letter-spacing: 1px;"><i class="fas fa-chart-bar me-1"></i> Statistik</small>
        </div>
        <div class="col-md-4">
            <div class="card stat-card">
                <div class="card-body py-3 px-4">
                    <p class="text-muted mb-1 small"><i class="fas fa-music text-danger me-1"></i> Request Hari Ini</p>
                    <h4 class="fw-bold text-danger mb-0" id="statTodayRequests"><?= esc($today_requests) ?></h4>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card stat-card">
                <div class="card-body py-3 px-4">
                    <p class="text-muted mb-1 small"><i class="fas fa-money-bill-wave text-success me-1"></i> Pendapatan Hari Ini</p>
                    <h4 class="fw-bold text-success mb-0" id="statDailyIncome">Rp <?= number_format($daily_income, 2, ',', '.') ?></h4>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card stat-card">
                <div class="card-body py-3 px-4">
                    <p class="text-muted mb-1 small"><i class="fas fa-wallet text-info me-1"></i> Saldo Anda</p>
                    <h4 class="fw-bold text-info mb-0" id="statBalance">Rp <?= number_format($balance['available_balance'] ?? 0, 2, ',', '.') ?></h4>
                </div>
            </div>
        </div>
    </div>

    <!-- ==================== -->
    <!-- FINANCE CENTER       -->
    <!-- ==================== -->
    <div class="finance-shell p-4 mt-4">
        <div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-4">
            <div>
                <div class="text-uppercase text-muted small fw-bold" style="letter-spacing: 1px;">Finance Center</div>
                <h5 class="fw-bold mb-1">Riwayat pendapatan, request lagu, dan withdrawal</h5>
                <p class="text-muted mb-0 small">Pantau pendapatan cafe, request lagu yang menghasilkan saldo, dan ajukan penarikan saldo dari satu tempat.</p>
            </div>
            <span class="badge bg-light text-dark border px-3 py-2">
                <i class="fas fa-shield-alt text-success me-1"></i> Transaksi terproteksi
            </span>
        </div>

        <div class="row g-3 mb-4">
            <div class="col-md-4 col-lg-2-4">
                <div class="finance-mini h-100">
                    <div class="label">Saldo tersedia</div>
                    <div class="value" id="financeBalance">Rp <?= number_format($finance_summary['available_balance'] ?? $balance['available_balance'] ?? 0, 2, ',', '.') ?></div>
                    <div style="font-size:0.72rem;color:#9ca3af;margin-top:4px">Bisa ditarik</div>
                </div>
            </div>
            <div class="col-md-4 col-lg-2-4">
                <div class="finance-mini h-100" style="background:linear-gradient(135deg,#fff7ed 0%,#ffffff 100%);border-color:#fed7aa">
                    <div class="label" style="color:#d97706">Saldo ditahan</div>
                    <div class="value" style="color:#d97706" id="financeHeldBalance">Rp <?= number_format($finance_summary['held_balance'] ?? $balance['held_balance'] ?? 0, 2, ',', '.') ?></div>
                    <div style="font-size:0.72rem;color:#d97706;margin-top:4px">Pending withdrawal</div>
                </div>
            </div>
            <div class="col-md-4 col-lg-2-4">
                <div class="finance-mini h-100" style="background:linear-gradient(135deg,#f0fdf4 0%,#ffffff 100%);border-color:#bbf7d0">
                    <div class="label" style="color:#15803d">Total pendapatan</div>
                    <div class="value" style="color:#15803d" id="financeTotalIncome">Rp <?= number_format($finance_summary['total_income'] ?? 0, 2, ',', '.') ?></div>
                    <div style="font-size:0.72rem;color:#15803d;margin-top:4px">Sejak awal</div>
                </div>
            </div>
            <div class="col-md-6 col-lg-2-4">
                <div class="finance-mini h-100">
                    <div class="label">Pendapatan hari ini</div>
                    <div class="value" id="financeTodayIncome">Rp <?= number_format($daily_income ?? 0, 2, ',', '.') ?></div>
                </div>
            </div>
            <div class="col-md-6 col-lg-2-4">
                <div class="finance-mini h-100">
                    <div class="label">Request hari ini</div>
                    <div class="value" id="financeTodayRequests"><?= esc($today_requests ?? 0) ?></div>
                </div>
            </div>
        </div>

        <div class="row g-3">
            <div class="col-lg-4">
                <div class="finance-card h-100 p-3">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <div>
                            <h6 class="fw-bold mb-1">Ajukan Withdrawal</h6>
                            <small class="text-muted">Masuk ke approval superadmin dulu sebelum dibayar.</small>
                        </div>
                        <span class="finance-badge pending">Pending review</span>
                    </div>
                    <form id="withdrawalRequestForm">
                        <div class="mb-3">
                            <label class="form-label small fw-bold text-muted">Nominal</label>
                            <input type="number" class="form-control" name="amount" min="10000" step="1000" placeholder="10000" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label small fw-bold text-muted">Tujuan penarikan</label>
                            <select class="form-select" name="destination_type" id="withdrawalDestinationType" onchange="toggleWithdrawalDestination()" required>
                                <option value="bank_transfer">Transfer Bank</option>
                                <option value="e_wallet">E-Wallet</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label small fw-bold text-muted" id="withdrawalProviderLabel">Nama Bank</label>
                            <input type="text" class="form-control" name="bank_name" id="withdrawalProviderInput" placeholder="BCA / BRI / Mandiri" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label small fw-bold text-muted" id="withdrawalAccountLabel">Nomor Rekening</label>
                            <input type="text" class="form-control" name="account_number" placeholder="1234567890" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label small fw-bold text-muted">Nama Pemilik</label>
                            <input type="text" class="form-control" name="account_holder" placeholder="Nama sesuai rekening" required>
                        </div>
                        <div class="d-flex justify-content-between align-items-center">
                            <small class="text-muted">Status: menunggu konfirmasi superadmin</small>
                            <button type="submit" class="btn btn-danger btn-sm" id="withdrawalSubmitBtn">
                                <i class="fas fa-paper-plane me-1"></i> Ajukan
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <div class="col-lg-8">
                <div class="finance-card p-3 mb-3">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <div>
                            <h6 class="fw-bold mb-1">History Pendapatan</h6>
                            <small class="text-muted">Transaksi pembayaran yang masuk ke saldo cafe.</small>
                        </div>
                        <span class="finance-badge success">Midtrans</span>
                    </div>
                    <div class="table-responsive">
                        <table class="finance-table table mb-0">
                            <thead>
                                <tr>
                                    <th>Tanggal</th>
                                    <th>Request</th>
                                    <th>Lagu</th>
                                    <th>Metode</th>
                                    <th>Nominal</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody id="payments-tbody">
                                <tr><td colspan="6" class="text-center text-muted py-4">Memuat data...</td></tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="finance-card p-3 mb-3">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <div>
                            <h6 class="fw-bold mb-1">History Lagu / Request</h6>
                            <small class="text-muted">Riwayat request yang masuk ke cafe beserta status antrean.</small>
                        </div>
                        <span class="finance-badge waiting">Queue log</span>
                    </div>
                    <div class="table-responsive">
                        <table class="finance-table table mb-0">
                            <thead>
                                <tr>
                                    <th>Tanggal</th>
                                    <th>Peminta</th>
                                    <th>Lagu</th>
                                    <th>Antrean</th>
                                    <th>Nominal</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody id="requests-tbody">
                                <tr><td colspan="6" class="text-center text-muted py-4">Memuat data...</td></tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="finance-card p-3">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <div>
                            <h6 class="fw-bold mb-1">History Withdrawal</h6>
                            <small class="text-muted">Status pengajuan penarikan saldo dan detail tujuan.</small>
                        </div>
                        <span class="finance-badge approved">Riwayat</span>
                    </div>
                    <div class="table-responsive">
                        <table class="finance-table table mb-0">
                            <thead>
                                <tr>
                                    <th>Tanggal</th>
                                    <th>Jumlah</th>
                                    <th>Tujuan</th>
                                    <th>Bank / Provider</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody id="withdrawals-tbody">
                                <tr><td colspan="5" class="text-center text-muted py-4">Memuat data...</td></tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
const cafeId = <?= esc($cafe['id']) ?>;
const csrfToken = '<?= csrf_hash() ?>';
const csrfName  = '<?= csrf_token() ?>';
// Ensure checkbox reflects server value (fix browser preserving form state issue)
document.addEventListener('DOMContentLoaded', function() {
    const cb = document.getElementById('aggregateSwitch');
    if (!cb) return;
    const serverValue = <?= json_encode((bool) ($cafe['aggregate_enabled'] ?? false)) ?>;
    cb.checked = serverValue;
});
let currentTrack = null;
let autoPlayEnabled = true;
let pollingInterval = null;
let hasPlayedOnce = false;
let isPlayingNext = false;

document.addEventListener('DOMContentLoaded', () => {
    loadFinanceData();
    const form = document.getElementById('withdrawalRequestForm');
    if (form) {
        form.addEventListener('submit', submitWithdrawalRequest);
    }
    toggleWithdrawalDestination();
});

// Aggregate toggle handler (admin can enable/disable per-cafe)
async function toggleAggregateSwitch(checked) {
    const cb = document.getElementById('aggregateSwitch');
    if (!cb) return;
    const enabled = checked ? 1 : 0;
    cb.disabled = true;
    try {
        const form = new FormData();
        form.append('aggregate_enabled', enabled);
        form.append('_method', 'PUT');

        const res = await fetch(`/cafe/update/${cafeId}`, {
            method: 'POST',
            body: form,
            credentials: 'same-origin'
        });
        const data = await res.json().catch(() => null);
        if (res.ok && data && data.success) {
            // Ensure checkbox state matches what server actually stored
            if (typeof data.aggregate_enabled !== 'undefined') {
                cb.checked = !!data.aggregate_enabled;
            }
            showAlert(data.message || 'Pengaturan aggregate berhasil disimpan', 'success');
        } else {
            showAlert((data && data.message) ? data.message : 'Gagal menyimpan pengaturan aggregate', 'danger');
            // revert to server state if provided
            if (data && typeof data.aggregate_enabled !== 'undefined') {
                cb.checked = !!data.aggregate_enabled;
            } else {
                cb.checked = !cb.checked;
            }
        }
    } catch (err) {
        console.error(err);
        showAlert('Terjadi kesalahan saat menghubungi server', 'danger');
        cb.checked = !cb.checked;
    } finally {
        cb.disabled = false;
    }
}

function toggleAggregate(event) {
    event.preventDefault();
    toggleAggregateSwitch(event.target.checked);
}

// Autoplay duration timer
let durationTimer = null;
let durationStartTime = null;
let durationTotal = 0;

function resetDurationTimer(event) {
    if (event) event.stopPropagation();
    if (currentTrack && currentTrack.duration) {
        startDurationTimer(currentTrack.duration, currentTrack.duration);
        showAlert('Timer direset ke durasi penuh lagu', 'success');
    }
}

document.getElementById('autoPlaySwitch').addEventListener('change', function() {
    autoPlayEnabled = this.checked;
    if (!autoPlayEnabled) {
        stopDurationTimer();
    } else {
        // If toggled back on, recalculate and start timer if a track is currently playing
        if (currentTrack && currentTrack.duration) {
            fetchDashboardUpdates();
        }
    }
});

// =====================
// Spotify Embed
// =====================
function showSpotifyEmbed(trackId) {
    const iframe = document.getElementById('spotifyEmbed');
    const placeholder = document.getElementById('spotifyPlaceholder');
    iframe.src = `https://open.spotify.com/embed/track/${trackId}?utm_source=generator&theme=0&autoplay=1`;
    iframe.style.display = 'block';
    placeholder.style.display = 'none';
}

function hideSpotifyEmbed() {
    const iframe = document.getElementById('spotifyEmbed');
    const placeholder = document.getElementById('spotifyPlaceholder');
    iframe.src = '';
    iframe.style.display = 'none';
    placeholder.style.display = 'flex';
}

// =====================
// Duration Timer (auto-play trigger)
// =====================
function startDurationTimer(remainingSeconds, totalDuration) {
    stopDurationTimer();
    durationTotal = totalDuration;
    durationStartTime = Date.now() - (totalDuration - remainingSeconds) * 1000;

    const timerDisplay = document.getElementById('autoplayTimerDisplay');
    const timeLeftEl = document.getElementById('autoplayTimeLeft');
    const timerBar = document.getElementById('autoplayTimerBar');

    if (autoPlayEnabled) {
        timerDisplay.style.display = 'flex';
    }

    durationTimer = setInterval(() => {
        const elapsed = Math.floor((Date.now() - durationStartTime) / 1000);
        const remaining = Math.max(0, durationTotal - elapsed);
        const pct = Math.max(0, (remaining / durationTotal) * 100);

        timeLeftEl.textContent = formatDuration(remaining);
        timerBar.style.width = pct + '%';

        if (remaining <= 0) {
            stopDurationTimer();
            hasPlayedOnce = true;
            if (autoPlayEnabled) {
                const pC = parseInt(document.getElementById('priorityQueueBadge').textContent) || 0;
                const fC = parseInt(document.getElementById('fifoQueueBadge').textContent) || 0;
                if (pC > 0 || fC > 0) {
                    playNext();
                } else {
                    if (currentTrack) {
                        markDone(currentTrack.id);
                    }
                }
            }
        }
    }, 1000);
}

function stopDurationTimer() {
    clearInterval(durationTimer);
    durationTimer = null;
    document.getElementById('autoplayTimerDisplay').style.display = 'none';
}

// =====================
// Helpers
// =====================
function formatDuration(s) {
    return `${Math.floor(s/60)}:${String(s%60).padStart(2,'0')}`;
}
function formatCurrency(a) {
    if (a === null || typeof a === 'undefined' || a === '') return '-';
    const num = Number(a) || 0;
    return 'Rp ' + num.toLocaleString('id-ID', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
}

function escapeHtml(value) {
    if (value === null || typeof value === 'undefined') return '';
    return String(value)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#39;');
}

function formatDateTime(value) {
    if (!value) return '-';
    const date = new Date(String(value).replace(' ', 'T'));
    if (Number.isNaN(date.getTime())) return String(value);
    return date.toLocaleDateString('id-ID', {
        day: '2-digit',
        month: 'short',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    });
}

function financeStatusClass(status) {
    const s = String(status || '').toLowerCase();
    switch (s) {
        case 'success':
        case 'approved':
        case 'paid':
        case 'done':
        case 'waiting':
        case 'processing':
        case 'completed':
        case 'failed':
        case 'rejected':
        case 'cancelled':
            return s;
        case 'pending':
        default:
            return 'pending';
    }
}

function wdStatusLabel(status) {
    const labels = {
        pending: 'Pending',
        approved: 'Disetujui',
        processing: 'Diproses',
        completed: 'Selesai',
        failed: 'Gagal',
        rejected: 'Ditolak',
        cancelled: 'Dibatalkan',
        paid: 'Selesai (Paid)'
    };
    return labels[String(status || '').toLowerCase()] || (status ? String(status).toUpperCase() : '-');
}

function toggleWithdrawalDestination() {
    const type = document.getElementById('withdrawalDestinationType');
    const providerLabel = document.getElementById('withdrawalProviderLabel');
    const providerInput = document.getElementById('withdrawalProviderInput');
    const accountLabel = document.getElementById('withdrawalAccountLabel');
    if (!type || !providerLabel || !providerInput || !accountLabel) return;

    if (type.value === 'e_wallet') {
        providerLabel.textContent = 'Nama Provider E-Wallet';
        providerInput.placeholder = 'DANA / OVO / GoPay';
        accountLabel.textContent = 'Nomor E-Wallet';
    } else {
        providerLabel.textContent = 'Nama Bank';
        providerInput.placeholder = 'BCA / BRI / Mandiri';
        accountLabel.textContent = 'Nomor Rekening';
    }
}

async function loadFinanceData() {
    try {
        const [paymentsRes, requestsRes, withdrawalsRes, summaryRes] = await Promise.all([
            fetch('/admin/payments', { headers: { 'X-Requested-With': 'XMLHttpRequest' } }),
            fetch('/admin/requests', { headers: { 'X-Requested-With': 'XMLHttpRequest' } }),
            fetch('/admin/withdrawals', { headers: { 'X-Requested-With': 'XMLHttpRequest' } }),
            fetch('/admin/finance/summary', { headers: { 'X-Requested-With': 'XMLHttpRequest' } }),
        ]);

        const [paymentsJson, requestsJson, withdrawalsJson, summaryJson] = await Promise.all([
            paymentsRes.json().catch(() => ({ data: [] })),
            requestsRes.json().catch(() => ({ data: [] })),
            withdrawalsRes.json().catch(() => ({ data: [] })),
            summaryRes.json().catch(() => ({ success: false })),
        ]);

        if (summaryJson && summaryJson.success && summaryJson.data) {
            const sum = summaryJson.data;
            const elBal = document.getElementById('financeBalance');
            const elHeld = document.getElementById('financeHeldBalance');
            const elTot = document.getElementById('financeTotalIncome');
            if (elBal && typeof sum.available_balance !== 'undefined') elBal.textContent = formatCurrency(sum.available_balance);
            if (elHeld && typeof sum.held_balance !== 'undefined') elHeld.textContent = formatCurrency(sum.held_balance);
            if (elTot && typeof sum.total_income !== 'undefined') elTot.textContent = formatCurrency(sum.total_income);
        }

        renderPaymentHistory(paymentsJson.data || []);
        renderRequestHistory(requestsJson.data || []);
        renderWithdrawalHistory(withdrawalsJson.data || []);
    } catch (e) {
        console.error(e);
    }
}

function renderPaymentHistory(rows) {
    const tbody = document.getElementById('payments-tbody');
    if (!tbody) return;
    if (!rows.length) {
        tbody.innerHTML = '<tr><td colspan="6" class="text-center text-muted py-4">Belum ada pendapatan</td></tr>';
        return;
    }

    tbody.innerHTML = rows.map((row) => `
        <tr>
            <td>${formatDateTime(row.created_at)}</td>
            <td>${escapeHtml(row.request_id || '-')}</td>
            <td>${escapeHtml(row.title || '-')}<br><small class="text-muted">${escapeHtml(row.artist || '')}</small></td>
            <td>${escapeHtml(row.payment_method || '-')}</td>
            <td><strong>${formatCurrency(row.amount || 0)}</strong></td>
            <td><span class="finance-badge ${financeStatusClass(row.payment_status)}">${escapeHtml(row.payment_status || '-')}</span></td>
        </tr>
    `).join('');
}

function renderRequestHistory(rows) {
    const tbody = document.getElementById('requests-tbody');
    if (!tbody) return;
    if (!rows.length) {
        tbody.innerHTML = '<tr><td colspan="6" class="text-center text-muted py-4">Belum ada request</td></tr>';
        return;
    }

    tbody.innerHTML = rows.map((row) => `
        <tr>
            <td>${formatDateTime(row.requested_at)}</td>
            <td>${escapeHtml(row.guest_name || 'Anonim')}</td>
            <td>${escapeHtml(row.title || '-')}<br><small class="text-muted">${escapeHtml(row.artist || '')}</small></td>
            <td>${escapeHtml(row.queue_type || '-')}</td>
            <td>${row.nominal ? formatCurrency(row.nominal) : '-'}</td>
            <td><span class="finance-badge ${financeStatusClass(row.status)}">${escapeHtml(row.status || '-')}</span></td>
        </tr>
    `).join('');
}

function renderWithdrawalHistory(rows) {
    const tbody = document.getElementById('withdrawals-tbody');
    if (!tbody) return;
    if (!rows.length) {
        tbody.innerHTML = '<tr><td colspan="5" class="text-center text-muted py-4">Belum ada withdrawal</td></tr>';
        return;
    }

    tbody.innerHTML = rows.map((row) => {
        const destType = row.destination_type === 'e_wallet' ? 'E-Wallet' : 'Transfer Bank';
        const accountNum = row.account_number_masked || row.account_number || '';
        const accInfo = accountNum ? `<br><small class="text-muted">${escapeHtml(accountNum)}${row.account_holder ? ' (' + escapeHtml(row.account_holder) + ')' : ''}</small>` : '';

        let extraMeta = '';
        if (row.status === 'completed' && row.transfer_reference) {
            extraMeta = `<div class="text-muted mt-1" style="font-size:0.72rem"><i class="fas fa-check-circle text-success me-1"></i>Ref: ${escapeHtml(row.transfer_reference)}</div>`;
        } else if (row.status === 'processing') {
            extraMeta = `<div class="text-muted mt-1" style="font-size:0.72rem"><i class="fas fa-spinner fa-spin text-primary me-1"></i>Sedang diproses admin</div>`;
        } else if ((row.status === 'failed' || row.status === 'rejected') && (row.failure_reason || row.notes)) {
            extraMeta = `<div class="text-danger mt-1" style="font-size:0.72rem"><i class="fas fa-exclamation-circle me-1"></i>${escapeHtml(row.failure_reason || row.notes)}</div>`;
        }

        return `
        <tr>
            <td>${formatDateTime(row.created_at)}</td>
            <td><strong>${formatCurrency(row.amount || 0)}</strong></td>
            <td>${destType}</td>
            <td>${escapeHtml(row.bank_name || '-')}${accInfo}</td>
            <td>
                <span class="finance-badge ${financeStatusClass(row.status)}">${escapeHtml(wdStatusLabel(row.status))}</span>
                ${extraMeta}
            </td>
        </tr>`;
    }).join('');
}

async function submitWithdrawalRequest(event) {
    event.preventDefault();
    const form = event.target;
    const btn = document.getElementById('withdrawalSubmitBtn');
    const formData = new FormData(form);
    formData.append(csrfName, csrfToken);

    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i> Mengajukan...';

    try {
        const res = await fetch('/admin/withdrawals/request', {
            method: 'POST',
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
            body: formData
        });
        const json = await res.json().catch(() => ({ success: false, message: 'Respon tidak valid' }));
        if (res.ok && json.success) {
            showAlert(json.message || 'Withdrawal berhasil diajukan', 'success');
            form.reset();
            toggleWithdrawalDestination();
            await loadFinanceData();
        } else {
            const message = json.message || (json.errors ? Object.values(json.errors).join(', ') : 'Gagal mengajukan withdrawal');
            showAlert(message, 'danger');
        }
    } catch (e) {
        showAlert('Error: ' + e.message, 'danger');
    } finally {
        btn.disabled = false;
        btn.innerHTML = '<i class="fas fa-paper-plane me-1"></i> Ajukan';
    }
}

// =====================
// Dashboard Fetch
// =====================
async function fetchDashboardUpdates() {
    try {
        const response = await fetch(`/song-request/webhook/queue/${cafeId}`, {
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        });

        if (response.status === 401) {
            clearInterval(pollingInterval);
            showAlert('Sesi Anda telah berakhir. Silakan login kembali.', 'warning');
            setTimeout(() => window.location.href = '/auth/login', 2000);
            return;
        }

        const contentType = response.headers.get("content-type");
        if (!response.ok || !contentType || contentType.indexOf("application/json") === -1) {
            return;
        }

        const result = await response.json();
        if (!result.success) return;

        // Stats
        document.getElementById('statTodayRequests').textContent = result.stats.today_requests;
        document.getElementById('statDailyIncome').textContent = formatCurrency(result.stats.daily_income);
        document.getElementById('statBalance').textContent = formatCurrency(result.stats.available_balance);
        document.getElementById('statTotalWaiting').textContent = result.stats.total_waiting;

        renderQueueLists(result.queue);

        // Now Playing
        const newTrack = result.currently_playing;
        if (newTrack) {
            const trackChanged = !currentTrack || currentTrack.id !== newTrack.id;
            if (trackChanged) {
                currentTrack = newTrack;
                hasPlayedOnce = true;
                if (newTrack.api_song_id) showSpotifyEmbed(newTrack.api_song_id);
            }

            // Start or maintain duration timer for auto-play
            if (autoPlayEnabled && newTrack.duration) {
                if (trackChanged || !durationTimer) {
                    const serverTime = new Date(result.timestamp.replace(' ', 'T')).getTime();
                    const playedAt = new Date(newTrack.played_at.replace(' ', 'T')).getTime();
                    const elapsedSeconds = Math.max(0, Math.floor((serverTime - playedAt) / 1000));
                    const remainingSeconds = Math.max(0, newTrack.duration - elapsedSeconds);
                    startDurationTimer(remainingSeconds, newTrack.duration);
                }
            }
        } else {
            if (currentTrack) {
                currentTrack = null;
                hideSpotifyEmbed();
                stopDurationTimer();
            }
            // Auto play immediately if queue receives a track and player is idle
            if (autoPlayEnabled && !isPlayingNext) {
                const pC = parseInt(document.getElementById('priorityQueueBadge').textContent) || 0;
                const fC = parseInt(document.getElementById('fifoQueueBadge').textContent) || 0;
                if (pC > 0 || fC > 0) {
                    playNext();
                }
            }
        }
        updateTrackDetail();
    } catch (e) {
        console.error("Dashboard update failed:", e);
    }
}

// =====================
// Track Detail UI
// =====================
function updateTrackDetail() {
    const section = document.getElementById('trackDetailSection');
    const card = document.getElementById('trackDetailCard');

    if (!currentTrack) {
        section.style.display = 'none';
        // Show placeholder play button
        document.getElementById('spotifyPlaceholder').querySelector('.btn-play-first')?.removeAttribute('disabled');
        return;
    }

    section.style.display = 'block';
    const qBadge = currentTrack.queue_type === 'priority'
        ? '<span class="badge badge-priority px-3 py-2 me-2"><i class="fas fa-star me-1"></i>PRIORITAS</span>'
        : '<span class="badge badge-fifo px-3 py-2 me-2"><i class="fas fa-list-ol me-1"></i>FIFO</span>';

    card.innerHTML = `
        <div class="d-flex align-items-center gap-3 flex-wrap">
            <img src="${currentTrack.thumbnail || '/assets/images/default-album.png'}" alt="" width="64" height="64" style="object-fit:cover;">
            <div class="flex-grow-1" style="min-width: 180px;">
                <div class="d-flex align-items-center gap-2 mb-1 flex-wrap">
                    ${qBadge}
                    ${currentTrack.nominal > 0 ? `<span class="badge bg-success bg-opacity-75 px-2 py-1" style="font-size:0.75rem;">${formatCurrency(currentTrack.nominal)}</span>` : ''}
                </div>
                <div class="track-title">${currentTrack.title}</div>
                <div class="track-artist">${currentTrack.artist}</div>
                <div class="track-meta mt-1">
                    <i class="fas fa-user me-1"></i> ${currentTrack.guest_name || 'Anonim'}
                    &nbsp;•&nbsp; <i class="fas fa-clock me-1"></i> ${formatDuration(currentTrack.duration || 0)}
                </div>
            </div>
            <div class="d-flex gap-2 flex-wrap">
                <button class="btn btn-action btn-done" onclick="markDone(${currentTrack.id})">
                    <i class="fas fa-check me-1"></i> Selesai
                </button>
                <button class="btn btn-action btn-skip" onclick="playNext()">
                    <i class="fas fa-forward me-1"></i> Skip
                </button>
                ${currentTrack.spotify_url ? `
                <a href="${currentTrack.spotify_url}" target="spotify_web" class="btn btn-action btn-spotify-open">
                    <i class="fab fa-spotify me-1"></i> Buka Spotify
                </a>` : ''}
            </div>
        </div>
    `;
}

// =====================
// Queue Lists
// =====================
function renderQueueLists(queue) {
    const pList = document.getElementById('priorityQueueList');
    const fList = document.getElementById('fifoQueueList');
    const pendingList = document.getElementById('pendingPaymentList');

    const pItems = queue.priority || [];
    document.getElementById('priorityQueueBadge').textContent = pItems.length;
    pList.innerHTML = pItems.length === 0
        ? '<div class="text-center py-4 text-muted small">Antrean kosong</div>'
        : pItems.map((item, i) => queueItemHTML(item, i, 'priority')).join('');

    const fItems = queue.fifo || [];
    document.getElementById('fifoQueueBadge').textContent = fItems.length;
    fList.innerHTML = fItems.length === 0
        ? '<div class="text-center py-4 text-muted small">Antrean kosong</div>'
        : fItems.map((item, i) => queueItemHTML(item, i, 'fifo')).join('');

    const pendingItems = queue.pending_payments || [];
    document.getElementById('pendingPaymentBadge').textContent = pendingItems.length;
    pendingList.innerHTML = pendingItems.length === 0
        ? '<div class="text-center py-3 text-muted small">Tidak ada request yang menunggu pembayaran</div>'
        : pendingItems.map((item, i) => queueItemHTML(item, i, 'pending')).join('');
}

function queueItemHTML(item, idx, type) {
    const badgeClass = type === 'priority' ? 'bg-danger' : (type === 'fifo' ? 'bg-warning text-dark' : 'bg-secondary text-dark');
    const nominalBadge = (item.nominal && item.nominal > 0) ? `<span class="badge bg-success bg-opacity-10 text-success border-0" style="font-size:0.7rem;">${formatCurrency(item.nominal)}</span>` : '';
    const pendingLabel = (type === 'pending') ? `<div class="small text-muted">Menunggu pembayaran</div>` : '';

    return `
        <div class="queue-item list-group-item border-0 px-3 py-3">
            <div class="d-flex align-items-center gap-3">
                <span class="badge ${badgeClass} rounded-circle" style="width:28px;height:28px;line-height:28px;padding:0;text-align:center;">${idx+1}</span>
                ${item.thumbnail ? `<img src="${item.thumbnail}" width="40" height="40" class="rounded" style="object-fit:cover;">` : ''}
                <div class="flex-grow-1 min-width-0">
                    <h6 class="fw-bold mb-0 text-truncate" style="font-size:0.9rem;">${item.title}</h6>
                    <small class="text-muted text-truncate d-block">${item.artist}</small>
                    ${pendingLabel}
                </div>
                <div class="text-end" style="min-width: 70px;">
                    ${nominalBadge}
                    <small class="text-muted d-block">${formatDuration(item.duration)}</small>
                </div>
            </div>
        </div>
    `;
}

// =====================
// API Calls
// =====================
async function playNext() {
    if (isPlayingNext) return;
    isPlayingNext = true;
    try {
        const res = await fetch('/admin/play-next', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded', 'X-Requested-With': 'XMLHttpRequest' },
            body: `cafe_id=${cafeId}`
        });

        if (res.status === 401) {
            showAlert('Sesi Anda telah berakhir. Silakan login kembali.', 'warning');
            setTimeout(() => window.location.href = '/auth/login', 2000);
            return;
        }

        const contentType = res.headers.get("content-type");
        if (!res.ok || !contentType || contentType.indexOf("application/json") === -1) {
            const text = await res.text();
            console.error("Non-JSON Response from play-next:", text);
            showAlert('Gagal memutar lagu: Terjadi kesalahan server', 'danger');
            return;
        }

        const result = await res.json();
        if (result.success) {
            if (result.spotify_playback && result.spotify_playback.status) {
                if (result.spotify_playback.status === 'success') {
                    showAlert(result.spotify_playback.message, 'success');
                } else {
                    showAlert(result.spotify_playback.message, 'warning');
                }
            } else {
                showAlert('Racikan lagu berikutnya siap dinikmati!', 'success');
            }
            if (result.song?.api_song_id) showSpotifyEmbed(result.song.api_song_id);
            await fetchDashboardUpdates();
        } else {
            showAlert(result.message, 'warning');
        }
    } catch (e) {
        showAlert('Gagal memutar lagu: ' + e.message, 'danger');
    } finally {
        isPlayingNext = false;
    }
}

async function markDone(requestId) {
    stopDurationTimer();
    try {
        const res = await fetch('/admin/mark-done', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded', 'X-Requested-With': 'XMLHttpRequest' },
            body: `cafe_id=${cafeId}&request_id=${requestId}`
        });

        if (res.status === 401) {
            showAlert('Sesi Anda telah berakhir. Silakan login kembali.', 'warning');
            setTimeout(() => window.location.href = '/auth/login', 2000);
            return;
        }

        const contentType = res.headers.get("content-type");
        if (!res.ok || !contentType || contentType.indexOf("application/json") === -1) {
            const text = await res.text();
            console.error("Non-JSON Response from mark-done:", text);
            showAlert('Gagal menandai selesai: Terjadi kesalahan server', 'danger');
            return;
        }

        const result = await res.json();
        if (result.success) {
            showAlert('Lagu ditandai selesai', 'success');
            currentTrack = null;
            hideSpotifyEmbed();
            await fetchDashboardUpdates();
            if (autoPlayEnabled) {
                const pC = parseInt(document.getElementById('priorityQueueBadge').textContent) || 0;
                const fC = parseInt(document.getElementById('fifoQueueBadge').textContent) || 0;
                if (pC > 0 || fC > 0) {
                    playNext();
                }
            }
        } else {
            showAlert(result.message, 'warning');
        }
    } catch (e) {
        showAlert('Error: ' + e.message, 'danger');
    }
}

// =====================
// Init
// =====================
document.addEventListener('DOMContentLoaded', () => {
    fetchDashboardUpdates();
    pollingInterval = setInterval(fetchDashboardUpdates, 3000);
});
window.onbeforeunload = () => { clearInterval(pollingInterval); stopDurationTimer(); };
</script>

<?= $this->endSection() ?>
