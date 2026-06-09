<?= $this->extend('layout/app') ?>

<?= $this->section('css') ?>
<style>
    .coffee-dashboard-card {
        background: linear-gradient(135deg, #2c1b12 0%, #1e110a 100%);
        border: 1px solid rgba(230, 194, 128, 0.15);
        border-radius: 20px;
        box-shadow: 0 15px 35px rgba(0, 0, 0, 0.4);
        padding: 40px;
        text-align: center;
        color: #F5EBE6;
        position: relative;
        overflow: hidden;
    }
    .coffee-dashboard-card::before {
        content: '';
        position: absolute;
        top: -50%;
        left: -50%;
        width: 200%;
        height: 200%;
        background: radial-gradient(circle, rgba(230,194,128,0.05) 0%, transparent 60%);
        pointer-events: none;
    }
    .coffee-icon-wrapper {
        width: 80px;
        height: 80px;
        background: rgba(230, 194, 128, 0.1);
        border: 2px dashed #e6c280;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 0 auto 24px;
        font-size: 2.2rem;
        color: #E6C280;
        animation: steam 3s ease-in-out infinite alternate;
    }
    @keyframes steam {
        0% { transform: translateY(0) scale(1); filter: drop-shadow(0 0 2px rgba(230,194,128,0.2)); }
        100% { transform: translateY(-5px) scale(1.05); filter: drop-shadow(0 5px 8px rgba(230,194,128,0.4)); }
    }
    .coffee-dashboard-title {
        font-family: 'Poppins', sans-serif;
        font-weight: 700;
        color: #E6C280;
        font-size: 1.8rem;
        margin-bottom: 16px;
    }
    .coffee-dashboard-lead {
        font-family: 'Inter', sans-serif;
        font-size: 1.05rem;
        opacity: 0.9;
        line-height: 1.6;
        max-width: 500px;
        margin: 0 auto 30px;
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
        margin-bottom: 24px;
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
    }
    .btn-coffee-secondary:hover {
        background: rgba(255, 255, 255, 0.15);
        color: #fff;
    }
</style>
<?= $this->endSection() ?>

<?= $this->section('content') ?>
<div class="container-lg py-5 my-5">
    <div class="row justify-content-center">
        <div class="col-md-8 col-lg-6">
            <div class="coffee-dashboard-card">
                <div class="coffee-icon-wrapper">
                    <i class="fas fa-mobile-alt"></i>
                </div>
                <div class="coffee-badge">
                    <i class="fas fa-coffee me-1"></i> Mode Penikmat Kopi
                </div>
                <h2 class="coffee-dashboard-title">Halo, Penikmat Musik & Kopi!</h2>
                <p class="coffee-dashboard-lead">
                    Kamu terdeteksi login sebagai <strong>User</strong> melalui website. Untuk melakukan pemesanan lagu langsung dari meja kafe kamu, silakan gunakan <strong>Aplikasi Song Request Android</strong> atau scan QR code di meja kafe tujuan kamu!
                </p>
                <div class="d-flex justify-content-center gap-3 flex-wrap">
                    <a href="/cafe/list" class="btn btn-coffee-primary">
                        <i class="fas fa-store me-2"></i> Cari Kafe Aktif
                    </a>
                    <a href="/auth/logout" class="btn btn-coffee-secondary">
                        <i class="fas fa-sign-out-alt me-2"></i> Keluar Sesi
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
<?= $this->endSection() ?>
