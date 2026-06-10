<?= $this->extend('layout/app') ?>
<?= $this->section('content') ?>
<?php
$status  = $status  ?? 'failed';
$cafeId  = (int)($cafeId  ?? 0);
$payment = $payment ?? [];

$cfg = [
    'success' => [
        'icon'    => 'fas fa-check-circle',
        'color'   => '#10b981',
        'bg'      => '#ecfdf5',
        'title'   => 'Pembayaran Berhasil!',
        'desc'    => 'Lagu kamu sudah masuk ke antrean priority. Tunggu sebentar ya!',
        'btnText' => 'Request Lagu Lagi',
        'btnCls'  => 'btn-success',
    ],
    'pending' => [
        'icon'    => 'fas fa-clock',
        'color'   => '#d97706',
        'bg'      => '#fffbeb',
        'title'   => 'Pembayaran Diproses',
        'desc'    => 'Pembayaran kamu sedang diverifikasi. Lagu akan masuk antrean setelah konfirmasi diterima.',
        'btnText' => 'Kembali ke Kafe',
        'btnCls'  => 'btn-warning',
    ],
    'failed'  => [
        'icon'    => 'fas fa-times-circle',
        'color'   => '#ef4444',
        'bg'      => '#fef2f2',
        'title'   => 'Pembayaran Gagal',
        'desc'    => 'Transaksi tidak berhasil. Silakan coba lagi atau pilih metode pembayaran lain.',
        'btnText' => 'Coba Lagi',
        'btnCls'  => 'btn-danger',
    ],
][$status] ?? [];
?>
<div class="container py-5" style="max-width:440px">
    <div class="card border-0 shadow-sm text-center">
        <div class="card-body p-5">
            <div style="width:80px;height:80px;border-radius:50%;background:<?= $cfg['bg'] ?>;
                        display:flex;align-items:center;justify-content:center;margin:0 auto 20px">
                <i class="<?= $cfg['icon'] ?>" style="font-size:2.2rem;color:<?= $cfg['color'] ?>"></i>
            </div>
            <h4 class="fw-bold mb-2"><?= $cfg['title'] ?></h4>
            <p class="text-muted mb-4" style="font-size:.88rem"><?= $cfg['desc'] ?></p>
            <?php if (!empty($payment['external_reference']) || !empty($payment['amount'])): ?>
            <div class="rounded p-3 mb-4 text-start" style="background:#f9fafb;font-size:.82rem">
                <?php if (!empty($payment['external_reference'])): ?>
                <div class="d-flex justify-content-between mb-1">
                    <span class="text-muted">Order ID</span>
                    <span class="fw-semibold" style="font-size:.75rem;word-break:break-all"><?= esc($payment['external_reference']) ?></span>
                </div>
                <?php endif; ?>
                <?php if (!empty($payment['amount'])): ?>
                <div class="d-flex justify-content-between">
                    <span class="text-muted">Jumlah</span>
                    <span class="fw-bold text-danger">Rp <?= number_format($payment['amount'], 0, ',', '.') ?></span>
                </div>
                <?php endif; ?>
            </div>
            <?php endif; ?>
            <a href="<?= $cafeId ? '/song-request/request?cafe_id=' . $cafeId : '/' ?>"
               class="btn <?= $cfg['btnCls'] ?> w-100 mb-2">
                <i class="fas fa-music me-2"></i><?= $cfg['btnText'] ?>
            </a>
            <a href="/" class="btn btn-outline-secondary w-100 btn-sm">Kembali ke Beranda</a>
        </div>
    </div>
</div>
<?= $this->endSection() ?>