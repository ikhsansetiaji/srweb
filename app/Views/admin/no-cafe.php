<?= $this->extend('layout/app') ?>

<?= $this->section('css') ?>
<style>
    .status-container {
        display: flex;
        justify-content: center;
        align-items: center;
        min-height: calc(100vh - 200px);
    }
    .status-card {
        background: linear-gradient(135deg, #2c1b12 0%, #1e110a 100%);
        border: 1px solid rgba(230, 194, 128, 0.15);
        border-radius: 20px;
        box-shadow: 0 15px 35px rgba(0, 0, 0, 0.4);
        padding: 40px;
        text-align: center;
        color: #F5EBE6;
        max-width: 550px;
        width: 100%;
        position: relative;
        overflow: hidden;
    }
    .status-card::before {
        content: '';
        position: absolute;
        top: -50%;
        left: -50%;
        width: 200%;
        height: 200%;
        background: radial-gradient(circle, rgba(230,194,128,0.05) 0%, transparent 60%);
        pointer-events: none;
    }
    .status-icon-wrapper {
        width: 80px;
        height: 80px;
        background: rgba(230, 194, 128, 0.1);
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 0 auto 24px;
        font-size: 2.2rem;
        transition: transform 0.3s;
    }
    .icon-no-cafe {
        border: 2px dashed #e6c280;
        color: #E6C280;
    }
    .icon-pending {
        border: 2px solid #F1C40F;
        color: #F1C40F;
        animation: rotateHourglass 4s infinite ease-in-out;
    }
    .icon-rejected {
        border: 2px solid #E74C3C;
        color: #E74C3C;
    }
    @keyframes rotateHourglass {
        0%, 100% { transform: rotate(0); }
        50% { transform: rotate(180deg); }
    }
    .status-title {
        font-family: 'Poppins', sans-serif;
        font-weight: 700;
        margin-bottom: 16px;
    }
    .title-no-cafe { color: #E6C280; }
    .title-pending { color: #F1C40F; }
    .title-rejected { color: #E74C3C; }

    .status-lead {
        font-family: 'Inter', sans-serif;
        font-size: 1rem;
        opacity: 0.9;
        line-height: 1.6;
        margin-bottom: 30px;
    }
    .coffee-badge {
        background: rgba(230, 194, 128, 0.15);
        color: #E6C280;
        border: 1px solid rgba(230, 194, 128, 0.3);
        padding: 6px 16px;
        border-radius: 50px;
        font-size: 0.85rem;
        font-weight: 600;
        display: inline-block;
        margin-bottom: 20px;
    }
    .rejection-box {
        background: rgba(231, 76, 60, 0.1);
        border: 1px solid rgba(231, 76, 60, 0.25);
        border-radius: 10px;
        padding: 15px 20px;
        margin-bottom: 30px;
        font-size: 0.9rem;
        text-align: left;
    }
    .rejection-label {
        font-weight: 700;
        color: #F19E95;
        margin-bottom: 4px;
        text-transform: uppercase;
        font-size: 0.75rem;
        letter-spacing: 0.5px;
    }
    .rejection-text {
        color: #F5EBE6;
        font-style: italic;
    }
    .btn-coffee-primary {
        background: #8B5A2B;
        color: #fff;
        border: none;
        padding: 12px 30px;
        font-weight: 600;
        border-radius: 10px;
        box-shadow: 0 4px 15px rgba(139, 90, 43, 0.4);
        transition: all 0.2s;
        text-decoration: none;
        display: inline-block;
    }
    .btn-coffee-primary:hover {
        background: #a06832;
        color: #fff;
        transform: translateY(-2px);
        box-shadow: 0 6px 20px rgba(139, 90, 43, 0.5);
    }
    .btn-coffee-secondary {
        background: rgba(255, 255, 255, 0.08);
        color: #F5EBE6;
        border: 1px solid rgba(255, 255, 255, 0.15);
        padding: 12px 30px;
        font-weight: 600;
        border-radius: 10px;
        transition: all 0.2s;
        text-decoration: none;
        display: inline-block;
    }
    .btn-coffee-secondary:hover {
        background: rgba(255, 255, 255, 0.15);
        color: #fff;
    }
</style>
<?= $this->endSection() ?>

<?= $this->section('content') ?>
<div class="container-lg status-container my-5">
    <?php
    $statusType = $status ?? 'no_registered';
    ?>

    <?php if ($statusType === 'pending'): ?>
        <!-- PENDING SCREEN -->
        <div class="status-card">
            <div class="status-icon-wrapper icon-pending">
                <i class="fas fa-hourglass-half"></i>
            </div>
            <div class="coffee-badge">
                <i class="fas fa-mug-hot me-1"></i> Sedang Diseduh
            </div>
            <h2 class="status-title title-pending">Kafe Sedang Ditinjau</h2>
            <p class="status-lead">
                Registrasi kafe <strong><?= esc($cafe['nama_kafe']) ?></strong> sedang diperiksa oleh Superadmin. Mohon tunggu proses verifikasi selesai (maksimal 1x24 jam) sebelum Anda dapat menerima request lagu dan saweran.
            </p>
            <div class="d-flex justify-content-center gap-3">
                <a href="/auth/logout" class="btn btn-coffee-secondary">
                    <i class="fas fa-sign-out-alt me-2"></i> Keluar
                </a>
            </div>
        </div>

    <?php elseif ($statusType === 'rejected'): ?>
        <!-- REJECTED SCREEN -->
        <div class="status-card">
            <div class="status-icon-wrapper icon-rejected">
                <i class="fas fa-exclamation-circle"></i>
            </div>
            <div class="coffee-badge">
                <i class="fas fa-fire me-1"></i> Roast Terlalu Panas
            </div>
            <h2 class="status-title title-rejected">Registrasi Kafe Ditolak</h2>
            <p class="status-lead">
                Mohon maaf, pendaftaran kafe <strong><?= esc($cafe['nama_kafe']) ?></strong> belum disetujui oleh Superadmin karena alasan berikut:
            </p>
            
            <div class="rejection-box">
                <div class="rejection-label"><i class="fas fa-info-circle me-1"></i> Alasan Penolakan:</div>
                <div class="rejection-text"><?= esc($cafe['rejection_reason'] ?: 'Data tidak valid atau kurang lengkap.') ?></div>
            </div>

            <div class="d-flex justify-content-center gap-3 flex-wrap">
                <a href="/cafe/register" class="btn btn-coffee-primary">
                    <i class="fas fa-redo-alt me-2"></i> Daftar Ulang
                </a>
                <a href="/auth/logout" class="btn btn-coffee-secondary">
                    <i class="fas fa-sign-out-alt me-2"></i> Keluar
                </a>
            </div>
        </div>

    <?php else: ?>
        <!-- NO REGISTERED SCREEN -->
        <div class="status-card">
            <div class="status-icon-wrapper icon-no-cafe">
                <i class="fas fa-store-slash"></i>
            </div>
            <div class="coffee-badge">
                <i class="fas fa-mug-hot me-1"></i> Mulai Bisnis Anda
            </div>
            <h2 class="status-title title-no-cafe">Kafe Belum Terdaftar</h2>
            <p class="status-lead">
                Anda belum mendaftarkan kafe Anda pada platform Song Request. Daftarkan kafe Anda sekarang dan mulai sediakan pemutar lagu interaktif berbasis saweran untuk pelanggan Anda!
            </p>
            <div class="d-flex justify-content-center gap-3">
                <a href="/cafe/register" class="btn btn-coffee-primary">
                    <i class="fas fa-store me-2"></i> Daftarkan Kafe Baru
                </a>
                <a href="/auth/logout" class="btn btn-coffee-secondary">
                    <i class="fas fa-sign-out-alt me-2"></i> Keluar
                </a>
            </div>
        </div>
    <?php endif; ?>
</div>
<?= $this->endSection() ?>
