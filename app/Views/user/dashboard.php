<?= $this->extend('layout/app') ?>

<?= $this->section('css') ?>
<style>
    body { background: #F4EFEA; color: #34373C; }

    .user-hero {
        background: linear-gradient(135deg, #E05A47 0%, #FF6347 100%);
        border-radius: 20px;
        padding: 40px;
        color: #fff;
        position: relative;
        overflow: hidden;
        margin-bottom: 30px;
        box-shadow: 0 8px 25px rgba(224, 90, 71, 0.15);
    }
    .user-hero::before {
        content: '';
        position: absolute;
        top: -40%;
        right: -10%;
        width: 300px;
        height: 300px;
        background: radial-gradient(circle, rgba(255,255,255,0.15) 0%, transparent 70%);
        pointer-events: none;
    }
    .user-hero h2 { font-family: 'Poppins', sans-serif; font-weight: 700; }
    .user-hero .lead { opacity: 0.9; font-size: 1rem; }

    .stat-card {
        background: #ffffff;
        border: 1px solid rgba(224, 90, 71, 0.12);
        border-radius: 16px;
        padding: 20px;
        text-align: center;
        color: #34373C;
        transition: transform 0.2s;
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.02);
    }
    .stat-card:hover { transform: translateY(-3px); }
    .stat-card .stat-icon { font-size: 1.6rem; margin-bottom: 8px; color: #E05A47; }
    .stat-card .stat-value { font-size: 1.5rem; font-weight: 700; color: #E05A47; }
    .stat-card .stat-label { font-size: 0.8rem; color: #666; }

    .cafe-card-sr {
        background: #ffffff;
        border: 1px solid rgba(224, 90, 71, 0.12);
        border-radius: 16px;
        padding: 20px;
        color: #34373C;
        transition: all 0.25s;
        cursor: pointer;
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.02);
    }
    .cafe-card-sr:hover {
        border-color: #E05A47;
        transform: translateY(-3px);
        box-shadow: 0 8px 25px rgba(224, 90, 71, 0.15);
    }
    .cafe-card-sr h6 { font-weight: 700; margin-bottom: 6px; color: #34373C; }
    .cafe-card-sr .meta { font-size: 0.85rem; color: #666; }

    .history-item {
        background: #ffffff;
        border: 1px solid rgba(224, 90, 71, 0.1);
        border-radius: 12px;
        padding: 14px 18px;
        color: #34373C;
        margin-bottom: 10px;
        display: flex;
        align-items: center;
        gap: 14px;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.02);
    }
    .history-item img { width: 45px; height: 45px; border-radius: 8px; object-fit: cover; }
    .history-item .info { flex: 1; }
    .history-item .song-title { font-weight: 600; font-size: 0.95rem; color: #34373C; }
    .history-item .song-meta { font-size: 0.8rem; color: #666; }
    .badge-status {
        font-size: 0.7rem;
        padding: 4px 10px;
        border-radius: 50px;
    }

    .section-title {
        font-family: 'Poppins', sans-serif;
        font-weight: 700;
        color: #34373C;
        margin-bottom: 20px;
    }
    .section-title i { color: #E05A47; }
</style>
<?= $this->endSection() ?>

<?= $this->section('content') ?>

<div class="container-lg py-4">
    <!-- Hero -->
    <div class="user-hero">
        <div class="row align-items-center">
            <div class="col-md-8">
                <h2><i class="fas fa-coffee me-2"></i>Halo, <?= esc($user['name'] ?? 'Penikmat Musik') ?>!</h2>
                <p class="lead mb-0 text-light">Temukan kafe favorit dan request lagu kesukaanmu. Musik terbaik menunggu untuk diputar!</p>
            </div>
            <div class="col-md-4 text-end d-none d-md-block">
                <a href="/song-request/request" class="btn btn-danger btn-lg px-4">
                    <i class="fas fa-music me-2 "></i>Request Lagu
                </a>
            </div>
        </div>
    </div>

    <!-- Stats -->
    <div class="row g-3 mb-4">
        <div class="col-6 col-md-3">
            <div class="stat-card">
                <div class="stat-icon"><i class="fas fa-headphones"></i></div>
                <div class="stat-value"><?= count($recentRequests) ?></div>
                <div class="stat-label">Request Terbaru</div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="stat-card">
                <div class="stat-icon"><i class="fas fa-store"></i></div>
                <div class="stat-value"><?= count($cafes) ?></div>
                <div class="stat-label">Kafe Aktif</div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="stat-card">
                <div class="stat-icon"><i class="fas fa-star"></i></div>
                <div class="stat-value"><?= count(array_filter($recentRequests, fn($r) => $r['queue_type'] === 'priority')) ?></div>
                <div class="stat-label">Priority</div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="stat-card">
                <div class="stat-icon"><i class="fas fa-list-ol"></i></div>
                <div class="stat-value"><?= count(array_filter($recentRequests, fn($r) => $r['queue_type'] === 'fifo')) ?></div>
                <div class="stat-label">Regular (FIFO)</div>
            </div>
        </div>
    </div>

    <div class="row g-4">
        <!-- Cafe List -->
        <div class="col-lg-7">
            <h5 class="section-title"><i class="fas fa-store me-2"></i>Pilih Kafe & Request Lagu</h5>
            <div class="row g-3">
                <?php if (empty($cafes)): ?>
                    <div class="col-12">
                        <div class="cafe-card-sr text-center py-4">
                            <i class="fas fa-mug-hot fa-2x mb-3" style="opacity:0.3"></i>
                            <p class="mb-0" style="opacity:0.5">Belum ada kafe aktif saat ini</p>
                        </div>
                    </div>
                <?php else: ?>
                    <?php foreach ($cafes as $cafe): ?>
                        <div class="col-sm-6">
                            <a href="/song-request/request?cafe_id=<?= $cafe['id'] ?>" class="text-decoration-none">
                                <div class="cafe-card-sr">
                                    <h6><i class="fas fa-mug-hot me-2" style="color:#FF4500"></i><?= esc($cafe['nama_kafe']) ?></h6>
                                    <p class="meta mb-2"><i class="fas fa-map-marker-alt me-1"></i><?= esc($cafe['alamat']) ?></p>
                                    <span class="badge bg-danger text-white">
                                        <i class="fas fa-play-circle me-1"></i>Request Lagu
                                    </span>
                                </div>

                            </a>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

        <!-- Recent History -->
        <div class="col-lg-5">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h5 class="section-title mb-0"><i class="fas fa-history me-2"></i>Request Terakhir</h5>
                <?php if (!empty($recentRequests)): ?>
                    <a href="/user/history" class="text-danger small">Lihat Semua →</a>
                <?php endif; ?>
            </div>

            <?php if (empty($recentRequests)): ?>
                <div class="history-item justify-content-center py-4">
                    <div class="text-center" style="opacity:0.4">
                        <i class="fas fa-music fa-2x mb-2"></i>
                        <p class="mb-0 small">Belum ada riwayat request</p>
                    </div>
                </div>
            <?php else: ?>
                <?php foreach ($recentRequests as $req): ?>
                    <div class="history-item">
                        <?php if (!empty($req['thumbnail'])): ?>
                            <img src="<?= esc($req['thumbnail']) ?>" alt="thumbnail">
                        <?php else: ?>
                            <div style="width:45px;height:45px;border-radius:8px;background:#2a2a3e;display:flex;align-items:center;justify-content:center;">
                                <i class="fas fa-music" style="opacity:0.3"></i>
                            </div>
                        <?php endif; ?>
                        <div class="info">
                            <div class="song-title"><?= esc($req['title'] ?? 'Unknown') ?></div>
                            <div class="song-meta"><?= esc($req['artist'] ?? '') ?> · <?= esc($req['nama_kafe'] ?? '') ?></div>
                        </div>
                        <?php
                            $statusColors = [
                                'waiting' => 'warning',
                                'playing' => 'info',
                                'done' => 'success',
                                'cancelled' => 'secondary',
                            ];
                            $color = $statusColors[$req['status']] ?? 'secondary';
                        ?>
                        <span class="badge bg-<?= $color ?> badge-status"><?= ucfirst($req['status']) ?></span>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <!-- Mobile CTA -->
    <div class="d-md-none mt-4">
        <a href="/song-request/request" class="btn btn-danger btn-lg w-100">
            <i class="fas fa-music me-2"></i>Request Lagu Sekarang
        </a>
    </div>
</div>

<?= $this->endSection() ?>
