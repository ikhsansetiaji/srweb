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
<div class="container py-5" style="max-width:480px">
    <div class="card border-0 shadow-sm text-center">
        <div class="card-body p-4 p-md-5">
            <div style="width:76px;height:76px;border-radius:50%;background:<?= $cfg['bg'] ?>;
                        display:flex;align-items:center;justify-content:center;margin:0 auto 18px">
                <i class="<?= $cfg['icon'] ?>" style="font-size:2.2rem;color:<?= $cfg['color'] ?>"></i>
            </div>
            
            <span class="badge mb-2" style="background:<?= $cfg['bg'] ?>;color:<?= $cfg['color'] ?>;font-size:0.75rem;">
                STATUS PEMBAYARAN
            </span>
            <h4 class="fw-bold mb-2 text-dark"><?= $cfg['title'] ?></h4>
            <p class="text-muted mb-4" style="font-size:.88rem"><?= $cfg['desc'] ?></p>

            <?php if ($status === 'success'): ?>
            <!-- Status Antrean Section (Dipisahkan secara eksplisit) -->
            <div class="p-3 mb-4 text-start rounded-3 border" style="background: rgba(224, 90, 71, 0.04); border-color: rgba(224, 90, 71, 0.2) !important;">
                <div class="d-flex align-items-center gap-2 mb-1">
                    <span class="badge badge-coral" style="font-size:0.7rem;">STATUS ANTREAN</span>
                    <strong class="text-dark small">Antrean Prioritas Aktif</strong>
                </div>
                <p class="small text-muted mb-0">Lagu Anda telah dijadwalkan di sistem kafe. Anda dapat memantau giliran putar melalui monitor kafe atau menanyakan ke operator kafe.</p>
            </div>
            <?php endif; ?>

            <?php if (!empty($payment['external_reference']) || !empty($payment['amount'])): ?>
            <div class="rounded-3 p-3 mb-4 text-start" style="background:#f9fafb;border:1px solid #e5e7eb;font-size:.84rem">
                <div class="text-uppercase text-muted fw-bold mb-2" style="font-size:0.7rem;letter-spacing:0.5px">Rincian Transaksi</div>
                <?php if (!empty($payment['external_reference'])): ?>
                <div class="d-flex justify-content-between mb-1">
                    <span class="text-muted">Order ID</span>
                    <span class="fw-semibold text-dark" style="font-size:.78rem;font-family:monospace"><?= esc($payment['external_reference']) ?></span>
                </div>
                <?php endif; ?>
                <?php if (!empty($payment['amount'])): ?>
                <div class="d-flex justify-content-between pt-1 border-top">
                    <span class="text-muted">Total Pembayaran</span>
                    <span class="fw-bold text-coral">Rp <?= number_format($payment['amount'], 2, ',', '.') ?></span>
                </div>
                <?php endif; ?>
            </div>
            <?php endif; ?>

            <a href="<?= $cafeId ? '/song-request/request?cafe_id=' . $cafeId : '/' ?>"
               class="btn btn-coral w-100 py-3 mb-2">
                <i class="fas fa-music me-2"></i><?= $cfg['btnText'] ?>
            </a>
            <a href="/" class="btn btn-secondary-custom w-100 btn-sm py-2">
                <i class="fas fa-home me-1"></i> Kembali ke Beranda
            </a>
        </div>
    </div>
</div>
<?= $this->endSection() ?>