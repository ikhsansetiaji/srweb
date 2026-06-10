<?= $this->extend('layout/app') ?>

<?= $this->section('css') ?>
<style>
    body { background: #F4EFEA; color: #34373C; }
    
    .history-card {
        background: #ffffff;
        border: 1px solid rgba(224, 90, 71, 0.12);
        border-radius: 16px;
        color: #34373C;
        padding: 30px;
        margin-bottom: 30px;
        box-shadow: 0 8px 30px rgba(0, 0, 0, 0.04) !important;
    }

    .history-card-title {
        font-family: 'Poppins', sans-serif;
        font-weight: 700;
        margin-bottom: 25px;
        color: #34373C;
    }

    .history-table-container {
        overflow-x: auto;
    }

    .table-sr {
        width: 100%;
        color: #34373C;
        border-collapse: collapse;
    }

    .table-sr th {
        background: rgba(224, 90, 71, 0.03);
        border-bottom: 2px solid rgba(224, 90, 71, 0.12);
        padding: 12px 16px;
        font-weight: 600;
        text-align: left;
        font-size: 0.85rem;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        color: #666;
    }

    .table-sr td {
        padding: 16px;
        border-bottom: 1px solid rgba(224, 90, 71, 0.08);
        vertical-align: middle;
    }

    .song-info {
        display: flex;
        align-items: center;
        gap: 12px;
    }

    .song-info img {
        width: 40px;
        height: 40px;
        border-radius: 6px;
        object-fit: cover;
    }

    .song-title {
        font-weight: 600;
        font-size: 0.95rem;
        color: #34373C;
    }

    .song-artist {
        font-size: 0.8rem;
        opacity: 0.6;
    }

    .badge-status {
        font-size: 0.75rem;
        padding: 6px 12px;
        border-radius: 50px;
        font-weight: 600;
        text-transform: capitalize;
    }

    .badge-queue {
        font-size: 0.7rem;
        padding: 4px 10px;
        border-radius: 4px;
        font-weight: 600;
        letter-spacing: 0.5px;
        text-transform: uppercase;
    }

    .btn-action-view {
        background: rgba(224, 90, 71, 0.05);
        color: #E05A47;
        border: 1px solid rgba(224, 90, 71, 0.15);
        padding: 6px 14px;
        font-size: 0.8rem;
        border-radius: 8px;
        transition: all 0.2s;
        text-decoration: none;
    }

    .btn-action-view:hover {
        background: #E05A47;
        color: #fff;
        border-color: #E05A47;
    }

    .empty-state {
        text-align: center;
        padding: 50px 20px;
        opacity: 0.6;
    }

    .empty-state i {
        font-size: 3rem;
        margin-bottom: 15px;
        color: #E05A47;
    }
</style>
<?= $this->endSection() ?>

<?= $this->section('content') ?>

<div class="container-lg py-5">
    <div class="history-card shadow">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h3 class="history-card-title mb-0">
                <i class="fas fa-history text-danger me-2"></i>Riwayat Request Lagu
            </h3>
            <a href="/user/dashboard" class="btn btn-outline-dark btn-sm rounded-pill px-3">
                <i class="fas fa-arrow-left me-2"></i>Kembali
            </a>
        </div>

        <?php if (empty($requests)): ?>
            <div class="empty-state">
                <i class="fas fa-music"></i>
                <h5>Belum Ada Riwayat Request</h5>
                <p class="text-muted small">Anda belum melakukan request lagu apapun. Yuk, request lagu kesukaanmu di kafe terdekat!</p>
                <a href="/song-request/request" class="btn btn-danger mt-3 px-4">
                    <i class="fas fa-music me-2"></i>Request Sekarang
                </a>
            </div>
        <?php else: ?>
            <div class="history-table-container">
                <table class="table table-sr">
                    <thead>
                        <tr>
                            <th>Lagu</th>
                            <th>Kafe</th>
                            <th>Tipe Antrean</th>
                            <th>Saweran</th>
                            <th>Status</th>
                            <th>Tanggal Request</th>
                            <th class="text-end">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($requests as $req): ?>
                            <tr>
                                <td>
                                    <div class="song-info">
                                        <?php if (!empty($req['thumbnail'])): ?>
                                            <img src="<?= esc($req['thumbnail']) ?>" alt="thumbnail">
                                        <?php else: ?>
                                            <div style="width:40px;height:40px;border-radius:6px;background:#2a2a3e;display:flex;align-items:center;justify-content:center;">
                                                <i class="fas fa-music" style="opacity:0.3"></i>
                                            </div>
                                        <?php endif; ?>
                                        <div>
                                            <div class="song-title"><?= esc($req['title'] ?? 'Unknown') ?></div>
                                            <div class="song-artist"><?= esc($req['artist'] ?? '') ?></div>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <span class="text-dark fw-semibold"><?= esc($req['nama_kafe'] ?? '') ?></span>
                                </td>
                                <td>
                                    <?php if ($req['queue_type'] === 'priority'): ?>
                                        <span class="badge badge-queue bg-danger bg-opacity-25 text-danger">Priority</span>
                                    <?php else: ?>
                                        <span class="badge badge-queue bg-secondary bg-opacity-25 text-white">Regular (FIFO)</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="text-warning fw-semibold">
                                        <?= $req['queue_type'] === 'priority' ? 'Rp ' . number_format($req['nominal'], 0, ',', '.') : 'Gratis' ?>
                                    </span>
                                </td>
                                <td>
                                    <?php
                                        $statusColors = [
                                            'waiting' => 'warning',
                                            'playing' => 'info',
                                            'done' => 'success',
                                            'cancelled' => 'secondary',
                                        ];
                                        $color = $statusColors[$req['status']] ?? 'secondary';
                                    ?>
                                    <span class="badge bg-<?= $color ?> badge-status"><?= esc(ucfirst($req['status'])) ?></span>
                                </td>
                                <td>
                                    <span class="small opacity-75">
                                        <?= date('d M Y, H:i', strtotime($req['requested_at'])) ?> WIB
                                    </span>
                                </td>
                                <td class="text-end">
                                    <a href="/song-request/request?cafe_id=<?= $req['cafe_id'] ?>" class="btn-action-view">
                                        <i class="fas fa-play me-1"></i>Request Lagi
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<?= $this->endSection() ?>
