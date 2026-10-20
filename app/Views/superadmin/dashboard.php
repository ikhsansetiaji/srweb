<?= $this->extend('layout/app') ?>

<?= $this->section('css') ?>
<style>
    /* ===== SUPERADMIN LAYOUT ===== */
    .sa-wrapper {
        display: flex;
        min-height: calc(100vh - 56px);
        background: #f4f6fb;
    }

    /* Sidebar */
    .sa-sidebar {
        width: 240px;
        flex-shrink: 0;
        background: #1a1a2e;
        padding: 24px 0;
        position: sticky;
        top: 56px;
        height: calc(100vh - 56px);
        overflow-y: auto;
    }
    .sa-sidebar-brand {
        display: flex;
        align-items: center;
        gap: 10px;
        padding: 0 20px 20px;
        border-bottom: 1px solid rgba(255,255,255,0.08);
        margin-bottom: 8px;
    }
    .sa-sidebar-brand .brand-icon {
        width: 36px; height: 36px;
        background: linear-gradient(135deg, #e53935, #ff5252);
        border-radius: 8px;
        display: flex; align-items: center; justify-content: center;
        color: #fff; font-size: 1rem;
    }
    .sa-sidebar-brand span {
        color: #fff; font-weight: 700; font-size: 0.9rem;
        line-height: 1.2;
    }
    .sa-sidebar-brand small { color: rgba(255,255,255,0.45); font-size: 0.7rem; display: block; }
    .sa-nav-item {
        display: flex; align-items: center; gap: 10px;
        padding: 10px 20px; color: rgba(255,255,255,0.55);
        font-size: 0.85rem; font-weight: 500; cursor: pointer;
        border-left: 3px solid transparent;
        transition: all 0.2s; text-decoration: none;
    }
    .sa-nav-item:hover { color: #fff; background: rgba(255,255,255,0.05); text-decoration: none; }
    .sa-nav-item.active { color: #fff; background: rgba(229,57,53,0.15); border-left-color: #e53935; }
    .sa-nav-item i { width: 18px; text-align: center; font-size: 0.9rem; }
    .sa-nav-section {
        padding: 16px 20px 6px;
        font-size: 0.65rem; font-weight: 700; letter-spacing: 1.2px;
        color: rgba(255,255,255,0.25); text-transform: uppercase;
    }

    /* Main Content */
    .sa-main {
        flex: 1;
        padding: 28px 32px;
        overflow-y: auto;
    }

    /* Header Bar */
    .sa-page-header {
        display: flex; align-items: flex-start; justify-content: space-between;
        margin-bottom: 28px;
    }
    .sa-page-header h1 {
        font-size: 1.5rem; font-weight: 700; color: #1a1a2e; margin: 0 0 4px;
    }
    .sa-page-header p { color: #6b7280; font-size: 0.85rem; margin: 0; }
    .sa-badge-live {
        display: inline-flex; align-items: center; gap: 6px;
        background: #ecfdf5; color: #059669;
        border: 1px solid #a7f3d0; border-radius: 20px;
        font-size: 0.72rem; font-weight: 600; padding: 4px 10px;
    }
    .sa-badge-live .dot {
        width: 6px; height: 6px; border-radius: 50%; background: #10b981;
        animation: pulse 1.5s infinite;
    }
    @keyframes pulse {
        0%, 100% { opacity: 1; } 50% { opacity: 0.4; }
    }

    /* Stat Cards */
    .sa-stat-card {
        background: #fff;
        border-radius: 14px;
        padding: 20px 22px;
        border: none;
        box-shadow: 0 1px 4px rgba(0,0,0,0.06);
        height: 100%;
        transition: transform 0.15s, box-shadow 0.15s;
    }
    .sa-stat-card:hover { transform: translateY(-2px); box-shadow: 0 6px 20px rgba(0,0,0,0.09); }
    .sa-stat-icon {
        width: 44px; height: 44px; border-radius: 10px;
        display: flex; align-items: center; justify-content: center;
        font-size: 1.1rem; margin-bottom: 14px;
    }
    .sa-stat-value { font-size: 1.65rem; font-weight: 800; color: #1a1a2e; margin: 0 0 2px; }
    .sa-stat-label { font-size: 0.78rem; color: #6b7280; margin: 0; }
    .sa-stat-sub { font-size: 0.72rem; color: #9ca3af; margin-top: 4px; }

    /* Section Cards */
    .sa-card {
        background: #fff;
        border-radius: 14px;
        border: none;
        box-shadow: 0 1px 4px rgba(0,0,0,0.06);
        overflow: hidden;
    }
    .sa-card-header {
        padding: 18px 22px;
        border-bottom: 1px solid #f3f4f6;
        display: flex; align-items: center; justify-content: space-between;
    }
    .sa-card-header h5 {
        font-size: 0.9rem; font-weight: 700; color: #1a1a2e; margin: 0;
        display: flex; align-items: center; gap: 8px;
    }
    .sa-card-header .badge-count {
        background: #fee2e2; color: #e53935;
        font-size: 0.7rem; font-weight: 700;
        padding: 2px 7px; border-radius: 10px;
    }

    /* Table */
    .sa-table { width: 100%; border-collapse: collapse; }
    .sa-table th {
        padding: 10px 16px; font-size: 0.72rem; font-weight: 700;
        letter-spacing: 0.5px; text-transform: uppercase;
        color: #9ca3af; background: #f9fafb;
        border-bottom: 1px solid #f3f4f6; text-align: left;
    }
    .sa-table td { padding: 12px 16px; font-size: 0.83rem; color: #374151; border-bottom: 1px solid #f9fafb; vertical-align: middle; }
    .sa-table tr:last-child td { border-bottom: none; }
    .sa-table tr:hover td { background: #fafbff; }

    /* Status badges */
    .s-badge { display: inline-block; padding: 3px 9px; border-radius: 20px; font-size: 0.7rem; font-weight: 600; }
    .s-badge.pending    { background: #fff7ed; color: #d97706; }
    .s-badge.approved   { background: #ecfdf5; color: #059669; }
    .s-badge.rejected   { background: #fef2f2; color: #dc2626; }
    .s-badge.active     { background: #eff6ff; color: #2563eb; }
    .s-badge.paid       { background: #f0fdf4; color: #16a34a; }
    .s-badge.processing { background: #fdf4ff; color: #9333ea; }
    .s-badge.completed  { background: #f0fdf4; color: #15803d; }
    .s-badge.failed     { background: #fef2f2; color: #dc2626; }
    .s-badge.cancelled  { background: #f3f4f6; color: #6b7280; }

    /* Action buttons */
    .btn-sa-approve  { background: #ecfdf5; color: #059669; border: 1px solid #a7f3d0; font-size: 0.75rem; font-weight: 600; padding: 4px 10px; border-radius: 6px; cursor: pointer; transition: all 0.15s; }
    .btn-sa-approve:hover  { background: #059669; color: #fff; }
    .btn-sa-reject   { background: #fef2f2; color: #dc2626; border: 1px solid #fecaca; font-size: 0.75rem; font-weight: 600; padding: 4px 10px; border-radius: 6px; cursor: pointer; transition: all 0.15s; }
    .btn-sa-reject:hover   { background: #dc2626; color: #fff; }
    .btn-sa-paid     { background: #eff6ff; color: #2563eb; border: 1px solid #bfdbfe; font-size: 0.75rem; font-weight: 600; padding: 4px 10px; border-radius: 6px; cursor: pointer; transition: all 0.15s; }
    .btn-sa-paid:hover     { background: #2563eb; color: #fff; }
    .btn-sa-process  { background: #fdf4ff; color: #9333ea; border: 1px solid #e9d5ff; font-size: 0.75rem; font-weight: 600; padding: 4px 10px; border-radius: 6px; cursor: pointer; transition: all 0.15s; }
    .btn-sa-process:hover  { background: #9333ea; color: #fff; }
    .btn-sa-complete { background: #f0fdf4; color: #15803d; border: 1px solid #86efac; font-size: 0.75rem; font-weight: 600; padding: 4px 10px; border-radius: 6px; cursor: pointer; transition: all 0.15s; }
    .btn-sa-complete:hover { background: #15803d; color: #fff; }
    .btn-sa-fail     { background: #fff1f2; color: #be123c; border: 1px solid #fda4af; font-size: 0.75rem; font-weight: 600; padding: 4px 10px; border-radius: 6px; cursor: pointer; transition: all 0.15s; }
    .btn-sa-fail:hover     { background: #be123c; color: #fff; }
    .btn-sa-detail   { background: #f8fafc; color: #475569; border: 1px solid #e2e8f0; font-size: 0.75rem; font-weight: 600; padding: 4px 10px; border-radius: 6px; cursor: pointer; transition: all 0.15s; }
    .btn-sa-detail:hover   { background: #475569; color: #fff; }
    /* Withdrawal filter bar */
    .wd-filter-bar { display: flex; gap: 6px; flex-wrap: wrap; margin-bottom: 16px; }
    .wd-filter-btn { padding: 5px 14px; border-radius: 20px; font-size: 0.76rem; font-weight: 600; border: 1px solid #e5e7eb; background: #f9fafb; color: #6b7280; cursor: pointer; transition: all 0.15s; }
    .wd-filter-btn:hover { border-color: #1a1a2e; color: #1a1a2e; }
    .wd-filter-btn.active { background: #1a1a2e; color: #fff; border-color: #1a1a2e; }

    /* Tabs */
    .sa-tabs { display: flex; gap: 4px; margin-bottom: 20px; }
    .sa-tab {
        padding: 7px 16px; border-radius: 8px; font-size: 0.82rem; font-weight: 600;
        cursor: pointer; border: none; background: #f3f4f6; color: #6b7280; transition: all 0.15s;
    }
    .sa-tab.active { background: #1a1a2e; color: #fff; }

    /* Tab panes */
    .tab-pane { display: none; }
    .tab-pane.active { display: block; }

    /* Toast */
    .sa-toast-wrap { position: fixed; bottom: 24px; right: 24px; z-index: 9999; display: flex; flex-direction: column; gap: 8px; }
    .sa-toast {
        background: #1a1a2e; color: #fff;
        padding: 12px 18px; border-radius: 10px;
        font-size: 0.82rem; font-weight: 500;
        box-shadow: 0 8px 24px rgba(0,0,0,0.2);
        display: flex; align-items: center; gap: 8px;
        animation: slideUp 0.3s ease;
        border-left: 3px solid #10b981;
    }
    .sa-toast.error { border-left-color: #e53935; }
    @keyframes slideUp { from { transform: translateY(20px); opacity: 0; } to { transform: translateY(0); opacity: 1; } }

    /* Loading skeleton */
    .sk { background: linear-gradient(90deg, #f0f0f0 25%, #e0e0e0 50%, #f0f0f0 75%); background-size: 200% 100%; animation: shimmer 1.5s infinite; border-radius: 6px; }
    @keyframes shimmer { 0% { background-position: 200% 0; } 100% { background-position: -200% 0; } }

    /* Reject modal */
    .sa-modal-overlay { position: fixed; inset: 0; background: rgba(0,0,0,0.45); z-index: 9000; display: none; align-items: center; justify-content: center; }
    .sa-modal-overlay.show { display: flex; }
    .sa-modal {
        background: #fff; border-radius: 16px; padding: 28px; width: 100%; max-width: 440px;
        box-shadow: 0 20px 60px rgba(0,0,0,0.2);
    }
    .sa-modal h5 { font-size: 1rem; font-weight: 700; color: #1a1a2e; margin: 0 0 6px; }
    .sa-modal p { font-size: 0.82rem; color: #6b7280; margin: 0 0 16px; }
    .sa-modal textarea { width: 100%; border: 1px solid #e5e7eb; border-radius: 8px; padding: 10px 12px; font-size: 0.83rem; resize: vertical; min-height: 90px; }
    .sa-modal textarea:focus { outline: none; border-color: #e53935; box-shadow: 0 0 0 3px rgba(229,57,53,0.1); }
    .sa-modal-actions { display: flex; gap: 8px; justify-content: flex-end; margin-top: 16px; }

    /* ===== RESPONSIVE MOBILE ===== */
    @media (max-width: 991px) {
        /* Sidebar jadi drawer, tersembunyi by default */
        .sa-sidebar {
            position: fixed;
            top: 0; left: 0;
            width: 280px;
            height: 100vh;
            z-index: 1050;
            transform: translateX(-100%);
            transition: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            padding-top: 0;
        }
        .sa-sidebar.open {
            transform: translateX(0);
            box-shadow: 8px 0 40px rgba(0,0,0,0.4);
        }
        .sa-sidebar-close {
            display: flex !important;
            align-items: center;
            justify-content: flex-end;
            padding: 14px 16px 8px;
        }
        .sa-sidebar-close button {
            background: rgba(255,255,255,0.08);
            border: none; border-radius: 8px;
            color: rgba(255,255,255,0.6); width: 32px; height: 32px;
            display: flex; align-items: center; justify-content: center;
            cursor: pointer; font-size: 1rem;
        }
        /* Overlay backdrop */
        .sa-overlay {
            display: none;
            position: fixed; inset: 0;
            background: rgba(0,0,0,0.55);
            z-index: 1040;
            backdrop-filter: blur(2px);
        }
        .sa-overlay.show { display: block; }
        /* Mobile top bar */
        .sa-mobile-topbar {
            display: flex !important;
        }
        /* Main padding */
        .sa-main {
            padding: 16px 14px;
            min-height: calc(100vh - 56px - 50px);
        }
        /* Table scrollable horizontal */
        .sa-table-wrap {
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
            margin: 0 -14px;
            padding: 0 14px;
        }
        .sa-table { min-width: 580px; }
        /* Stat cards - 2 kolom di mobile */
        .sa-stat-card { padding: 16px; }
        .sa-stat-value { font-size: 1.35rem !important; }
        /* Page header stack */
        .sa-page-header {
            flex-direction: column;
            align-items: flex-start;
            gap: 8px;
        }
        /* Modal full width */
        .sa-modal {
            max-width: 95vw;
            padding: 20px;
            margin: 12px;
        }
        /* Card sections padding */
        .sa-card-header { padding: 14px 16px; }
        /* Filter bar wrap */
        .wd-filter-bar { gap: 4px; }
        .wd-filter-btn { padding: 4px 10px; font-size: 0.72rem; }
        /* Toast bottom center di mobile */
        .sa-toast-wrap {
            bottom: 12px; right: 12px; left: 12px;
        }
        .sa-toast { font-size: 0.78rem; padding: 10px 14px; }
    }
    @media (max-width: 575px) {
        .sa-main { padding: 12px 10px; }
        .sa-page-header h1 { font-size: 1.2rem; }
        .sa-stat-value { font-size: 1.15rem !important; }
    }
    /* Mobile top bar — hidden on desktop */
    .sa-mobile-topbar {
        display: none;
        align-items: center;
        justify-content: space-between;
        background: #1a1a2e;
        padding: 10px 16px;
        position: sticky;
        top: 56px;
        z-index: 200;
        border-bottom: 1px solid rgba(255,255,255,0.06);
    }
    .sa-mobile-topbar .brand { color: #fff; font-weight: 700; font-size: 0.88rem; display: flex; align-items: center; gap: 8px; }
    .sa-mobile-topbar .brand i { color: #e53935; }
    .sa-hamburger {
        background: rgba(255,255,255,0.08);
        border: none; border-radius: 8px; padding: 6px 10px;
        color: rgba(255,255,255,0.75); font-size: 1rem; cursor: pointer;
        display: flex; align-items: center; gap: 6px;
        transition: background 0.15s;
    }
    .sa-hamburger:hover { background: rgba(255,255,255,0.14); }
    /* Sidebar close button — hidden on desktop */
    .sa-sidebar-close { display: none; }
</style>
<?= $this->endSection() ?>

<?= $this->section('content') ?>

<!-- Overlay backdrop for mobile sidebar -->
<div class="sa-overlay" id="saSidebarOverlay" onclick="closeSidebar()"></div>

<!-- Mobile Top Bar -->
<div class="sa-mobile-topbar">
    <div class="brand"><i class="fas fa-crown"></i> Superadmin Panel</div>
    <button class="sa-hamburger" onclick="openSidebar()" aria-label="Buka menu">
        <i class="fas fa-bars"></i>
    </button>
</div>

<div class="sa-wrapper">

    <!-- Sidebar (Desktop sticky + Mobile drawer) -->
    <aside class="sa-sidebar" id="saSidebar">
        <!-- Close button (mobile only) -->
        <div class="sa-sidebar-close">
            <button onclick="closeSidebar()" aria-label="Tutup menu"><i class="fas fa-times"></i></button>
        </div>

        <div class="sa-sidebar-brand">
            <div class="brand-icon"><i class="fas fa-crown"></i></div>
            <div>
                <span>Song Request<small>Superadmin Panel</small></span>
            </div>
        </div>

        <div class="sa-nav-section">Menu</div>
        <a href="#" class="sa-nav-item active" onclick="showSection('dashboard', this); closeSidebar(); return false;">
            <i class="fas fa-th-large"></i> Dashboard
        </a>
        <a href="#" class="sa-nav-item" onclick="showSection('cafes', this); closeSidebar(); return false;">
            <i class="fas fa-store"></i> Kelola Cafe
        </a>
        <a href="#" class="sa-nav-item" onclick="showSection('pending-admins', this); closeSidebar(); return false;">
            <i class="fas fa-user-clock"></i> Verifikasi Admin
            <span class="ms-auto badge-count" id="sidebar-admin-count" style="display:none"></span>
        </a>
        <a href="#" class="sa-nav-item" onclick="showSection('withdrawals', this); closeSidebar(); return false;">
            <i class="fas fa-wallet"></i> Withdrawal
            <span class="ms-auto badge-count" id="sidebar-wd-count" style="display:none"></span>
        </a>
        <a href="#" class="sa-nav-item" onclick="showSection('transactions', this); closeSidebar(); return false;">
            <i class="fas fa-receipt"></i> Transaksi
        </a>

        <div class="sa-nav-section">Keamanan</div>
        <a href="#" class="sa-nav-item" onclick="revokeDevices(); return false;" title="Keluar dari semua perangkat">
            <i class="fas fa-shield-alt"></i> Cabut Perangkat
        </a>

        <div class="sa-nav-section">Akun</div>
        <a href="/auth/logout" class="sa-nav-item">
            <i class="fas fa-sign-out-alt"></i> Logout
        </a>
    </aside>

    <!-- Main -->
    <main class="sa-main">

        <!-- ===== SECTION: DASHBOARD ===== -->
        <section id="section-dashboard">
            <div class="sa-page-header">
                <div>
                    <h1><i class="fas fa-th-large me-2" style="color:#e53935;font-size:1.2rem"></i>Dashboard</h1>
                    <p>Ringkasan seluruh sistem Song Request</p>
                </div>
                <span class="sa-badge-live"><span class="dot"></span> Data Real-time</span>
            </div>

            <!-- Stats -->
            <div class="row g-3 mb-4">
                <div class="col-sm-6 col-xl-3">
                    <div class="sa-stat-card">
                        <div class="sa-stat-icon" style="background:#fff0f0;color:#e53935"><i class="fas fa-store"></i></div>
                        <div class="sa-stat-value" id="stat-total-cafe"><span class="sk d-block" style="height:32px;width:60px"></span></div>
                        <div class="sa-stat-label">Total Cafe</div>
                        <div class="sa-stat-sub" id="stat-total-cafe-sub">&nbsp;</div>
                    </div>
                </div>
                <div class="col-sm-6 col-xl-3">
                    <div class="sa-stat-card">
                        <div class="sa-stat-icon" style="background:#ecfdf5;color:#059669"><i class="fas fa-check-circle"></i></div>
                        <div class="sa-stat-value" id="stat-active-cafe"><span class="sk d-block" style="height:32px;width:60px"></span></div>
                        <div class="sa-stat-label">Cafe Aktif</div>
                        <div class="sa-stat-sub" id="stat-pending-sub">&nbsp;</div>
                    </div>
                </div>
                <div class="col-sm-6 col-xl-3">
                    <div class="sa-stat-card">
                        <div class="sa-stat-icon" style="background:#eff6ff;color:#2563eb"><i class="fas fa-money-bill-wave"></i></div>
                        <div class="sa-stat-value" id="stat-income"><span class="sk d-block" style="height:32px;width:100px"></span></div>
                        <div class="sa-stat-label">Total Pendapatan Sistem</div>
                        <div class="sa-stat-sub" id="stat-income-sub">&nbsp;</div>
                    </div>
                </div>
                <div class="col-sm-6 col-xl-3">
                    <div class="sa-stat-card">
                        <div class="sa-stat-icon" style="background:#fff7ed;color:#d97706"><i class="fas fa-hourglass-half"></i></div>
                        <div class="sa-stat-value" id="stat-wd-pending"><span class="sk d-block" style="height:32px;width:40px"></span></div>
                        <div class="sa-stat-label">Withdrawal Pending</div>
                        <div class="sa-stat-sub"><a href="#" onclick="showSection('withdrawals'); return false;" style="color:#e53935;font-size:0.72rem">Review →</a></div>
                    </div>
                </div>
                <div class="col-sm-6 col-xl-3">
                    <div class="sa-stat-card">
                        <div class="sa-stat-icon" style="background:#faf5ff;color:#7c3aed"><i class="fas fa-user-clock"></i></div>
                        <div class="sa-stat-value" id="stat-admin-pending">-</div>
                        <div class="sa-stat-label">Admin Pending</div>
                        <div class="sa-stat-sub"><a href="#" onclick="showSection('pending-admins'); return false;" style="color:#e53935;font-size:0.72rem">Verifikasi →</a></div>
                    </div>
                </div>
            </div>

            <!-- Quick Tables -->
            <div class="row g-3">
                <!-- Pending Cafe Verification -->
                <div class="col-lg-6">
                    <div class="sa-card">
                        <div class="sa-card-header">
                            <h5><i class="fas fa-clock" style="color:#d97706"></i> Menunggu Verifikasi <span class="badge-count" id="pending-cafe-count">0</span></h5>
                            <a href="#" onclick="showSection('cafes', null, 'pending'); return false;" style="font-size:0.78rem;color:#e53935;text-decoration:none">Lihat semua →</a>
                        </div>
                        <div id="quick-pending-cafes">
                            <div style="padding:20px 22px;text-align:center;color:#9ca3af;font-size:0.83rem">
                                <span class="sk d-block mb-2" style="height:48px"></span>
                                <span class="sk d-block" style="height:48px"></span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Pending Withdrawals -->
                <div class="col-lg-6">
                    <div class="sa-card">
                        <div class="sa-card-header">
                            <h5><i class="fas fa-wallet" style="color:#2563eb"></i> Withdrawal Pending <span class="badge-count" id="pending-wd-count">0</span></h5>
                            <a href="#" onclick="showSection('withdrawals'); return false;" style="font-size:0.78rem;color:#e53935;text-decoration:none">Lihat semua →</a>
                        </div>
                        <div id="quick-pending-wds">
                            <div style="padding:20px 22px;text-align:center;color:#9ca3af;font-size:0.83rem">
                                <span class="sk d-block mb-2" style="height:48px"></span>
                                <span class="sk d-block" style="height:48px"></span>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- Pending Admin Kafe -->
                <div class="col-lg-6 mt-3">
                    <div class="sa-card">
                        <div class="sa-card-header">
                            <h5><i class="fas fa-user-clock" style="color:#7c3aed"></i> Pendaftaran Admin Kafe <span class="badge-count" id="quick-admin-pending-count">0</span></h5>
                            <a href="#" onclick="showSection('pending-admins'); return false;" style="font-size:0.78rem;color:#e53935;text-decoration:none">Lihat semua →</a>
                        </div>
                        <div id="quick-pending-admins">
                            <div style="padding:20px 22px;text-align:center;color:#9ca3af;font-size:0.83rem">
                                <span class="sk d-block mb-2" style="height:48px"></span>
                                <span class="sk d-block" style="height:48px"></span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- ===== SECTION: PENDING ADMINS ===== -->
        <section id="section-pending-admins" style="display:none">
            <div class="sa-page-header">
                <div>
                    <h1><i class="fas fa-user-clock me-2" style="color:#7c3aed;font-size:1.2rem"></i>Verifikasi Admin Kafe</h1>
                    <p>Aktivasi akun admin kafe yang baru mendaftar</p>
                </div>
            </div>
            <div class="sa-card">
                <div style="overflow-x:auto">
                    <table class="sa-table">
                        <thead>
                            <tr>
                                <th>Nama</th>
                                <th>Email</th>
                                <th>Daftar</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody id="pending-admins-tbody">
                            <tr><td colspan="4" style="text-align:center;padding:28px;color:#9ca3af">Memuat data...</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </section>

        <!-- ===== SECTION: CAFES ===== -->
        <section id="section-cafes" style="display:none">
            <div class="sa-page-header">
                <div>
                    <h1><i class="fas fa-store me-2" style="color:#e53935;font-size:1.2rem"></i>Kelola Cafe</h1>
                    <p>Verifikasi dan kelola semua cafe terdaftar</p>
                </div>
                <button class="btn-sa-approve px-3 py-2 fw-bold" onclick="openCreateCafeModal()">
                    <i class="fas fa-plus-circle me-1"></i> Tambah Kafe Baru
                </button>
            </div>

            <div class="sa-tabs">
                <button class="sa-tab active" id="cafe-tab-pending" onclick="cafeSwitchTab('pending', this)">Pending <span id="tab-pending-count"></span></button>
                <button class="sa-tab" id="cafe-tab-all" onclick="cafeSwitchTab('all', this)">Semua Cafe</button>
            </div>

            <!-- Pending cafes table -->
            <div id="cafe-pane-pending" class="tab-pane active">
                <div class="sa-card">
                    <div style="overflow-x:auto">
                        <table class="sa-table">
                            <thead>
                                <tr>
                                    <th>Nama Cafe</th>
                                    <th>Admin</th>
                                    <th>Alamat</th>
                                    <th>Terdaftar</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody id="pending-cafe-tbody">
                                <tr><td colspan="5" style="text-align:center;padding:28px;color:#9ca3af">Memuat data...</td></tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- All cafes table -->
            <div id="cafe-pane-all" class="tab-pane">
                <div class="sa-card">
                    <div style="overflow-x:auto">
                        <table class="sa-table">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Nama Cafe</th>
                                    <th>Admin</th>
                                    <th>Status</th>
                                    <th>Aktif</th>
                                    <th>Payment</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody id="all-cafe-tbody">
                                <tr><td colspan="7" style="text-align:center;padding:28px;color:#9ca3af">Memuat data...</td></tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </section>

        <!-- ===== SECTION: WITHDRAWALS ===== -->
        <section id="section-withdrawals" style="display:none">
            <div class="sa-page-header">
                <div>
                    <h1><i class="fas fa-wallet me-2" style="color:#e53935;font-size:1.2rem"></i>Withdrawal</h1>
                    <p>Kelola permintaan penarikan saldo dari cafe — alur: Pending → Approved → Processing → Completed</p>
                </div>
            </div>

            <!-- Filter Status Bar -->
            <div class="wd-filter-bar" id="wd-filter-bar">
                <button class="wd-filter-btn active" data-status="" onclick="setWdFilter('', this)">Semua</button>
                <button class="wd-filter-btn" data-status="pending" onclick="setWdFilter('pending', this)">Pending <span id="wd-count-pending" class="badge-count" style="margin-left:4px;font-size:0.65rem"></span></button>
                <button class="wd-filter-btn" data-status="approved" onclick="setWdFilter('approved', this)">Approved</button>
                <button class="wd-filter-btn" data-status="processing" onclick="setWdFilter('processing', this)">Processing</button>
                <button class="wd-filter-btn" data-status="completed" onclick="setWdFilter('completed', this)">Completed</button>
                <button class="wd-filter-btn" data-status="rejected" onclick="setWdFilter('rejected', this)">Rejected</button>
                <button class="wd-filter-btn" data-status="failed" onclick="setWdFilter('failed', this)">Failed</button>
            </div>

            <div class="sa-card">
                <div style="overflow-x:auto">
                    <table class="sa-table">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Cafe</th>
                                <th>Jumlah</th>
                                <th>Tujuan</th>
                                <th>Bank / Provider</th>
                                <th>No. Rekening</th>
                                <th>A/N</th>
                                <th>Status</th>
                                <th>Diajukan</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody id="wd-tbody">
                            <tr><td colspan="10" style="text-align:center;padding:28px;color:#9ca3af">Memuat data...</td></tr>
                        </tbody>
                    </table>
                </div>
                <div id="wd-pagination" style="padding:14px 20px;text-align:center;border-top:1px solid #f3f4f6;display:none">
                    <button class="btn-sa-approve" id="wd-load-more" onclick="loadMoreWithdrawals()">Muat lebih banyak</button>
                </div>
            </div>
        </section>

        <!-- ===== SECTION: TRANSACTIONS ===== -->
        <section id="section-transactions" style="display:none">
            <div class="sa-page-header">
                <div>
                    <h1><i class="fas fa-receipt me-2" style="color:#e53935;font-size:1.2rem"></i>Transaksi</h1>
                    <p>Semua transaksi pembayaran di sistem</p>
                </div>
            </div>

            <div class="sa-card">
                <div style="overflow-x:auto">
                    <table class="sa-table">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Cafe</th>
                                <th>Nominal</th>
                                <th>Status</th>
                                <th>Metode</th>
                                <th>Waktu</th>
                            </tr>
                        </thead>
                        <tbody id="trx-tbody">
                            <tr><td colspan="6" style="text-align:center;padding:28px;color:#9ca3af">Memuat data...</td></tr>
                        </tbody>
                    </table>
                </div>
                <div id="trx-pagination" style="padding:14px 20px;text-align:center;border-top:1px solid #f3f4f6;display:none">
                    <button class="btn-sa-approve" id="trx-load-more" onclick="loadTransactions()">Muat lebih banyak</button>
                </div>
            </div>
        </section>

    </main>
</div>

<!-- Reject Modal -->
<div class="sa-modal-overlay" id="reject-modal">
    <div class="sa-modal">
        <h5 id="reject-modal-title">Tolak Pengajuan</h5>
        <p>Masukkan alasan penolakan (minimal 10 karakter)</p>
        <textarea id="reject-reason" placeholder="Contoh: Dokumen tidak lengkap, informasi tidak valid, dsb."></textarea>
        <div class="sa-modal-actions">
            <button class="btn-sa-approve" onclick="closeRejectModal()">Batal</button>
            <button class="btn-sa-reject" onclick="submitReject()">Tolak</button>
        </div>
    </div>
</div>

<!-- Fail Withdrawal Modal -->
<div class="sa-modal-overlay" id="fail-modal">
    <div class="sa-modal">
        <h5>Transfer Gagal</h5>
        <p>Catat alasan kegagalan transfer. Saldo akan dikembalikan ke kafe.</p>
        <textarea id="fail-reason" placeholder="Contoh: Nomor rekening tidak valid, rekening dibekukan, dsb."></textarea>
        <div class="sa-modal-actions">
            <button class="btn-sa-approve" onclick="closeFailModal()">Batal</button>
            <button class="btn-sa-fail" onclick="submitFail()">Catat Gagal</button>
        </div>
    </div>
</div>

<!-- Complete Withdrawal Modal -->
<div class="sa-modal-overlay" id="complete-modal">
    <div class="sa-modal" style="max-width:500px">
        <h5>Konfirmasi Transfer Selesai</h5>
        <p>Isi referensi transfer yang sudah dikirim ke kafe.</p>
        <div style="margin-bottom:14px">
            <label style="font-size:0.8rem;font-weight:700;color:#374151;display:block;margin-bottom:4px">Referensi Transfer <span style="color:#e53935">*</span></label>
            <input type="text" id="complete-ref" placeholder="Contoh: 250000000012345 / TF-20241016" style="width:100%;border:1px solid #e5e7eb;border-radius:8px;padding:8px 12px;font-size:0.85rem">
        </div>
        <div style="margin-bottom:14px">
            <label style="font-size:0.8rem;font-weight:700;color:#374151;display:block;margin-bottom:4px">Catatan (opsional)</label>
            <textarea id="complete-notes" placeholder="Catatan tambahan..." style="width:100%;border:1px solid #e5e7eb;border-radius:8px;padding:8px 12px;font-size:0.85rem;min-height:70px;resize:vertical"></textarea>
        </div>
        <div style="margin-bottom:14px">
            <label style="font-size:0.8rem;font-weight:700;color:#374151;display:block;margin-bottom:4px">Bukti Transfer (opsional)</label>
            <input type="file" id="complete-proof" accept="image/*,.pdf" style="width:100%;border:1px solid #e5e7eb;border-radius:8px;padding:6px 10px;font-size:0.83rem">
            <div style="font-size:0.72rem;color:#9ca3af;margin-top:4px">JPG, PNG, atau PDF. Maks 5MB.</div>
        </div>
        <div class="sa-modal-actions">
            <button class="btn-sa-reject" onclick="closeCompleteModal()">Batal</button>
            <button class="btn-sa-complete" id="complete-submit-btn" onclick="submitComplete()">Konfirmasi Selesai</button>
        </div>
    </div>
</div>

<!-- Withdrawal Detail Modal -->
<div class="sa-modal-overlay" id="wd-detail-modal">
    <div class="sa-modal" style="max-width:600px;max-height:90vh;overflow-y:auto">
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:20px">
            <h5 style="margin:0">Detail Withdrawal</h5>
            <button onclick="closeDetailModal()" style="background:none;border:none;font-size:1.2rem;color:#9ca3af;cursor:pointer">&times;</button>
        </div>
        <div id="wd-detail-content">
            <div style="text-align:center;padding:40px;color:#9ca3af"><i class="fas fa-spinner fa-spin fa-2x"></i></div>
        </div>
    </div>
</div>

<!-- Create Cafe Modal -->
<div class="sa-modal-overlay" id="create-cafe-modal" style="position: fixed; inset: 0; background: rgba(0,0,0,0.45); z-index: 9000; display: none; align-items: center; justify-content: center; overflow-y: auto;">
    <div class="sa-modal" style="background: #fff; border-radius: 16px; padding: 28px; width: 100%; max-width: 500px; max-height: 90vh; overflow-y: auto;">
        <h5 class="fw-bold mb-1" style="color:#1a1a2e"><i class="fas fa-plus-circle text-danger me-2"></i>Tambah Kafe Baru</h5>
        <p class="text-muted small mb-4">Input data kafe partner baru dari pengajuan kemitraan email.</p>
        
        <form id="createCafeForm">
            <div class="mb-3">
                <label for="new_cafe_admin" class="form-label fw-bold small mb-1" style="display:block;text-align:left;color:#374151">Pilih User Admin Kafe</label>
                <select class="form-select form-select-sm" id="new_cafe_admin" name="admin_id" style="width:100%;padding:6px;border:1px solid #e5e7eb;border-radius:6px;font-size:0.85rem;" required>
                    <option value="">Memuat admin...</option>
                </select>
                <div class="form-text text-muted small" style="font-size:0.75rem;margin-top:4px;">Hanya menampilkan akun Admin yang belum memiliki kafe.</div>
            </div>
            
            <div class="mb-3">
                <label for="new_cafe_name" class="form-label fw-bold small mb-1" style="display:block;text-align:left;color:#374151">Nama Kafe</label>
                <input type="text" class="form-control form-control-sm" id="new_cafe_name" name="nama_kafe" style="width:100%;padding:6px;border:1px solid #e5e7eb;border-radius:6px;font-size:0.85rem;" placeholder="Contoh: Kopi Sederhana" required>
            </div>
            
            <div class="mb-3">
                <label for="new_cafe_address" class="form-label fw-bold small mb-1" style="display:block;text-align:left;color:#374151">Alamat Kafe</label>
                <textarea class="form-control form-control-sm" id="new_cafe_address" name="alamat" rows="2" style="width:100%;padding:6px;border:1px solid #e5e7eb;border-radius:6px;font-size:0.85rem;resize:vertical;" placeholder="Alamat lengkap kafe..." required></textarea>
            </div>

            <div class="mb-3">
                <label for="new_cafe_desc" class="form-label fw-bold small mb-1" style="display:block;text-align:left;color:#374151">Deskripsi (Opsional)</label>
                <textarea class="form-control form-control-sm" id="new_cafe_desc" name="deskripsi" rows="2" style="width:100%;padding:6px;border:1px solid #e5e7eb;border-radius:6px;font-size:0.85rem;resize:vertical;" placeholder="Deskripsi singkat kafe..."></textarea>
            </div>
            
            <div class="mb-3">
                <label for="new_cafe_phone" class="form-label fw-bold small mb-1" style="display:block;text-align:left;color:#374151">Nomor Telepon / WA</label>
                <input type="text" class="form-control form-control-sm" id="new_cafe_phone" name="phone_number" style="width:100%;padding:6px;border:1px solid #e5e7eb;border-radius:6px;font-size:0.85rem;" placeholder="Contoh: 08123456789">
            </div>

            <!-- Metode Pembayaran -->
            <div class="mb-3">
                <label class="form-label fw-bold small mb-1" style="display:block;text-align:left;color:#374151">Metode Pembayaran</label>
                <select class="form-select form-select-sm" id="new_cafe_method" name="payment_method"
                        style="width:100%;padding:6px;border:1px solid #e5e7eb;border-radius:6px;font-size:0.85rem;"
                        required onchange="togglePaymentFields()">
                    <option value="bank_transfer">Transfer Bank</option>
                    <option value="e_wallet">E-Wallet</option>
                </select>
            </div>

            <div class="mb-3">
                <label class="form-label fw-bold small mb-1" style="display:block;text-align:left;color:#374151">Nama Pemilik Rekening / Akun</label>
                <input type="text" class="form-control form-control-sm" id="new_cafe_receiver" name="payment_receiver"
                       style="width:100%;padding:6px;border:1px solid #e5e7eb;border-radius:6px;font-size:0.85rem;"
                       placeholder="Contoh: Ahmad Ikhsan" required>
            </div>

            <!-- Field Transfer Bank -->
            <div id="new_bank_group" style="display:none">
                <div class="row g-2 mb-3">
                    <div class="col-sm-6">
                        <label class="form-label fw-bold small mb-1" style="display:block;text-align:left;color:#374151">Nama Bank / Provider</label>
                        <input type="text" class="form-control form-control-sm" name="bank_name"
                               style="width:100%;padding:6px;border:1px solid #e5e7eb;border-radius:6px;font-size:0.85rem;"
                               placeholder="BCA / BRI / Mandiri...">
                    </div>
                    <div class="col-sm-6">
                        <label class="form-label fw-bold small mb-1" style="display:block;text-align:left;color:#374151">Nomor Rekening</label>
                        <input type="text" class="form-control form-control-sm" name="account_number"
                               style="width:100%;padding:6px;border:1px solid #e5e7eb;border-radius:6px;font-size:0.85rem;"
                               placeholder="Contoh: 1234567890">
                    </div>
                </div>
            </div>

            <!-- Field E-Wallet -->
            <div class="mb-3" id="new_ewallet_group" style="display:none">
                <label class="form-label fw-bold small mb-1" style="display:block;text-align:left;color:#374151">Nomor E-Wallet <span class="text-muted fw-normal">(GoPay / OVO / Dana)</span></label>
                <input type="text" class="form-control form-control-sm" name="ewallet_number"
                       style="width:100%;padding:6px;border:1px solid #e5e7eb;border-radius:6px;font-size:0.85rem;"
                       placeholder="Contoh: 0812xxxxxxxx">
            </div>

            <!-- Payment Gate Token (Midtrans dsb) -->
            <div class="mb-3">
                <label class="form-label fw-bold small mb-1" style="display:block;text-align:left;color:#374151">
                    Payment Gate Token <span class="text-muted fw-normal">(Midtrans / opsional)</span>
                </label>
                <input type="text" class="form-control form-control-sm" name="payment_gate_token"
                       style="width:100%;padding:6px;border:1px solid #e5e7eb;border-radius:6px;font-size:0.85rem;"
                       placeholder="SB-Mid-server-xxxxxxx atau kosongkan">
                <div class="form-text" style="font-size:0.72rem;color:#9ca3af;margin-top:3px;">
                    Isi jika kafe ini menggunakan Midtrans sendiri. Kosongkan jika pakai gateway utama sistem.
                </div>
            </div>

            <div style="margin-bottom: 24px; text-align: left; display: flex; align-items: center; gap: 8px;">
                <input type="checkbox" id="new_cafe_active" name="is_active_now" style="width:18px;height:18px;" checked>
                <label class="fw-bold text-success small" for="new_cafe_active" style="cursor:pointer;margin:0;">
                    <i class="fas fa-power-off me-1"></i> Aktifkan Kafe Sekarang (Langsung Aktif)
                </label>
            </div>

            <div class="sa-modal-actions">
                <button type="button" class="btn-sa-reject" onclick="closeCreateCafeModal()">Batal</button>
                <button type="submit" class="btn-sa-approve" id="submitCreateCafeBtn">Simpan Kafe</button>
            </div>
        </form>
    </div>
</div>

<!-- Toast Container -->
<div class="sa-toast-wrap" id="toast-wrap"></div>

<?= $this->endSection() ?>

<?= $this->section('js') ?>
<script>
// ===== STATE =====
const BASE = '<?= base_url() ?>';
let rejectTarget = null; // { type: 'cafe'|'withdrawal', id }
let trxPage = 1;
let trxLoading = false;
const csrfToken = '<?= csrf_hash() ?>';
const csrfName  = '<?= csrf_token() ?>';

// ===== INIT =====
document.addEventListener('DOMContentLoaded', () => {
    loadDashboard();
    loadPendingCafes();
    loadPendingAdmins();
});

// ===== SECTION SWITCH =====
function showSection(name, navEl, tab) {
    // hide all
    document.querySelectorAll('[id^="section-"]').forEach(s => s.style.display = 'none');
    document.getElementById('section-' + name).style.display = 'block';

    // nav highlight
    if (navEl) {
        document.querySelectorAll('.sa-nav-item').forEach(i => i.classList.remove('active'));
        navEl.classList.add('active');
    } else {
        // auto-highlight
        document.querySelectorAll('.sa-nav-item').forEach(i => {
            i.classList.remove('active');
            if (i.getAttribute('onclick') && i.getAttribute('onclick').includes("'" + name + "'")) {
                i.classList.add('active');
            }
        });
    }

    // Lazy load per section
    if (name === 'pending-admins') loadPendingAdmins();
    if (name === 'cafes') {
        loadPendingCafes();
        if (tab === 'pending') {
            cafeSwitchTab('pending', document.getElementById('cafe-tab-pending'));
        }
    }
    if (name === 'withdrawals') loadWithdrawals();
    if (name === 'transactions') { trxPage = 1; loadTransactions(true); }
}

// ===== DASHBOARD =====
async function loadDashboard() {
    try {
        // Stats come from PHP (server-side rendered)
        const totalCafe = <?= $total_cafes ?? 0 ?>;
        const activeCafe = <?= $active_cafes ?? 0 ?>;
        const pendingCafe = <?= $pending_cafes ?? 0 ?>;
        const totalIncome = <?= $total_income ?? 0 ?>;
        const totalWithdrawn = <?= $total_withdrawn ?? 0 ?>;
        const pendingWd = <?= $pending_withdrawals ?? 0 ?>;

        document.getElementById('stat-total-cafe').textContent = totalCafe;
        document.getElementById('stat-total-cafe-sub').textContent = pendingCafe + ' menunggu verifikasi';
        document.getElementById('stat-active-cafe').textContent = activeCafe;
        document.getElementById('stat-pending-sub').textContent = pendingCafe + ' pending';
        document.getElementById('stat-income').textContent = formatRp(totalIncome);
        document.getElementById('stat-income-sub').textContent = 'Ditarik: ' + formatRp(totalWithdrawn);
        document.getElementById('stat-wd-pending').textContent = pendingWd;

        if (pendingWd > 0) {
            const el = document.getElementById('sidebar-wd-count');
            el.textContent = pendingWd;
            el.style.display = 'inline';
        }
    } catch (e) {
        console.error(e);
    }
}

// ===== PENDING CAFES (Quick + Table) =====
async function loadPendingCafes() {
    try {
        const res = await fetch(BASE + 'superadmin/cafes-pending');
        const json = await res.json();
        const cafes = json.data || [];

        // Update counts
        document.getElementById('pending-cafe-count').textContent = cafes.length;
        const tabCount = document.getElementById('tab-pending-count');
        if (tabCount) tabCount.textContent = cafes.length ? '(' + cafes.length + ')' : '';

        // Quick widget (dashboard)
        const quickEl = document.getElementById('quick-pending-cafes');
        if (cafes.length === 0) {
            quickEl.innerHTML = '<div style="padding:24px;text-align:center;color:#9ca3af;font-size:0.83rem"><i class="fas fa-check-circle text-success me-2"></i>Tidak ada cafe pending</div>';
        } else {
            quickEl.innerHTML = '<div class="list-group list-group-flush">' + cafes.slice(0, 4).map(c => `
                <div class="list-group-item px-4 py-3">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <div style="font-weight:700;font-size:0.85rem;color:#1a1a2e">${esc(c.nama_kafe)}</div>
                            <div style="font-size:0.75rem;color:#6b7280">${esc(c.admin_name || '-')} &bull; ${esc(c.alamat || '')}</div>
                        </div>
                        <div style="display:flex;gap:6px;flex-shrink:0">
                            <button class="btn-sa-approve" onclick="approveCafe(${c.id}, '${esc(c.nama_kafe)}')">Approve</button>
                            <button class="btn-sa-reject" onclick="openRejectModal('cafe', ${c.id}, '${esc(c.nama_kafe)}')">Reject</button>
                        </div>
                    </div>
                </div>`).join('') + '</div>';
        }

        // Full table (cafes section)
        const tbody = document.getElementById('pending-cafe-tbody');
        if (cafes.length === 0) {
            tbody.innerHTML = '<tr><td colspan="5" style="text-align:center;padding:28px;color:#9ca3af"><i class="fas fa-check-circle text-success me-2"></i>Tidak ada cafe pending</td></tr>';
        } else {
            tbody.innerHTML = cafes.map(c => `
                <tr id="pending-row-${c.id}">
                    <td><strong>${esc(c.nama_kafe)}</strong><br><small style="color:#9ca3af">${esc(c.slug || '')}</small></td>
                    <td>${esc(c.admin_name || '-')}<br><small style="color:#9ca3af">${esc(c.admin_email || '')}</small></td>
                    <td style="max-width:180px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis">${esc(c.alamat || '-')}</td>
                    <td>${formatDate(c.created_at)}</td>
                    <td>
                        <div style="display:flex;gap:6px">
                            <button class="btn-sa-approve" onclick="approveCafe(${c.id}, '${esc(c.nama_kafe)}')">Approve</button>
                            <button class="btn-sa-reject" onclick="openRejectModal('cafe', ${c.id}, '${esc(c.nama_kafe)}')">Reject</button>
                        </div>
                    </td>
                </tr>`).join('');
        }

    } catch (e) {
        console.error('Load pending cafes error:', e);
        toast('Gagal memuat data cafe', true);
    }
}

async function loadAllCafes() {
    const tbody = document.getElementById('all-cafe-tbody');

    try {
        const res = await fetch(BASE + 'superadmin/cafes');
        const json = await res.json();
        const cafes = json.data || [];

        if (!cafes.length) {
            tbody.innerHTML =
                '<tr><td colspan="7" style="text-align:center">Belum ada cafe</td></tr>';
            return;
        }

        tbody.innerHTML = cafes.map((c, i) => {

            const isActive = c.is_active === 't';

            return `
                <tr>
                    <td>${i + 1}</td>
                    <td><strong>${esc(c.nama_kafe)}</strong></td>
                    <td>${esc(c.admin_name || '-')}</td>

                    <td>
                        <span class="s-badge ${c.status}">
                            ${c.status}
                        </span>
                    </td>

                    <td>
                        ${isActive
                            ? '<span class="s-badge active">Aktif</span>'
                            : '<span class="s-badge rejected">Tidak Aktif</span>'
                        }
                    </td>

                    <td>${esc(c.payment_method || '-')}</td>

                    <td>
                        <button
                            onclick="toggleCafeActive(${c.id}, ${isActive ? 1 : 0})"
                            class="btn-sa-${isActive ? 'reject' : 'approve'} px-2 py-1"
                            style="font-size:0.75rem"
                        >
                            ${isActive ? 'Nonaktifkan' : 'Aktifkan'}
                        </button>
                    </td>
                </tr>
            `;
        }).join('');

    } catch (e) {
        console.error(e);
        tbody.innerHTML =
            '<tr><td colspan="7" style="text-align:center">Gagal memuat data</td></tr>';
    }
}

function cafeSwitchTab(tab, el) {
    document.querySelectorAll('.sa-tab').forEach(t => t.classList.remove('active'));
    if (el) el.classList.add('active');

    document.getElementById('cafe-pane-pending').classList.toggle('active', tab === 'pending');
    document.getElementById('cafe-pane-all').classList.toggle('active', tab === 'all');

    if (tab === 'all') loadAllCafes();
}

// ===== WITHDRAWALS =====
let wdCurrentFilter = '';
let wdPage = 1;
let wdAllLoaded = false;

function setWdFilter(status, btn) {
    wdCurrentFilter = status;
    wdPage = 1;
    wdAllLoaded = false;
    document.querySelectorAll('.wd-filter-btn').forEach(b => b.classList.remove('active'));
    if (btn) btn.classList.add('active');
    loadWithdrawals(true);
}

async function loadWithdrawals(reset = false) {
    if (reset) { wdPage = 1; wdAllLoaded = false; }
    const tbody = document.getElementById('wd-tbody');
    const limitPerPage = 50;

    if (wdPage === 1) {
        tbody.innerHTML = '<tr><td colspan="10" style="text-align:center;padding:28px;color:#9ca3af">Memuat...</td></tr>';
    }

    try {
        const url = BASE + 'superadmin/withdrawals?page=' + wdPage + (wdCurrentFilter ? '&status=' + wdCurrentFilter : '');
        const res = await fetch(url);
        const json = await res.json();
        const wds = json.data || [];

        // Update dashboard quick widget (hanya untuk pending)
        if (!wdCurrentFilter || wdCurrentFilter === 'pending') {
            const quickEl = document.getElementById('quick-pending-wds');
            // Fetch fresh pending count
            fetch(BASE + 'superadmin/withdrawals-pending').then(r => r.json()).then(pj => {
                const pending = pj.data || [];
                document.getElementById('pending-wd-count').textContent = pending.length;
                const cntEl = document.getElementById('wd-count-pending');
                if (cntEl) cntEl.textContent = pending.length || '';
                if (pending.length === 0) {
                    quickEl.innerHTML = '<div style="padding:24px;text-align:center;color:#9ca3af;font-size:0.83rem"><i class="fas fa-check-circle text-success me-2"></i>Tidak ada withdrawal pending</div>';
                } else {
                    quickEl.innerHTML = '<div class="list-group list-group-flush">' + pending.slice(0, 4).map(w => `
                        <div class="list-group-item px-4 py-3">
                            <div class="d-flex align-items-center justify-content-between">
                                <div>
                                    <div style="font-weight:700;font-size:0.85rem;color:#1a1a2e">${formatRp(w.amount)}</div>
                                    <div style="font-size:0.75rem;color:#6b7280">${esc(w.nama_kafe || '-')} &bull; ${esc(w.bank_name || '')} ${esc(w.account_number || '')}</div>
                                </div>
                                <div style="display:flex;gap:6px;flex-shrink:0">
                                    <button class="btn-sa-approve" onclick="approveWithdrawal(${w.id})">Approve</button>
                                    <button class="btn-sa-reject" onclick="openRejectModal('withdrawal', ${w.id}, '${esc(w.nama_kafe)}')">Reject</button>
                                </div>
                            </div>
                        </div>`).join('') + '</div>';
                }
            });
        }

        if (wds.length === 0 && wdPage === 1) {
            tbody.innerHTML = '<tr><td colspan="10" style="text-align:center;padding:28px;color:#9ca3af"><i class="fas fa-check-circle text-success me-2"></i>Tidak ada data withdrawal</td></tr>';
            document.getElementById('wd-pagination').style.display = 'none';
            return;
        }

        // Render rows
        const rows = wds.map((w, i) => {
            const actionBtns = buildWdActions(w);
            return `
            <tr id="wd-row-${w.id}">
                <td>${((wdPage - 1) * limitPerPage) + i + 1}</td>
                <td><strong>${esc(w.nama_kafe || '-')}</strong><br><small style="color:#9ca3af">${esc(w.admin_name || '')}</small></td>
                <td><strong>${formatRp(w.amount)}</strong></td>
                <td><span style="font-size:0.78rem">${w.destination_type === 'e_wallet' ? 'E-Wallet' : 'Bank Transfer'}</span></td>
                <td>${esc(w.bank_name || '-')}</td>
                <td style="font-family:monospace;font-size:0.82rem">${esc(w.account_number || '-')}</td>
                <td>${esc(w.account_holder || '-')}</td>
                <td><span class="s-badge ${w.status}">${wdStatusLabel(w.status)}</span></td>
                <td><span style="font-size:0.78rem;color:#6b7280">${formatDate(w.created_at)}</span></td>
                <td>
                    <div style="display:flex;gap:4px;flex-wrap:wrap">
                        ${actionBtns}
                    </div>
                </td>
            </tr>`;
        }).join('');

        if (wdPage === 1) tbody.innerHTML = rows;
        else tbody.insertAdjacentHTML('beforeend', rows);

        // Pagination
        const paginationEl = document.getElementById('wd-pagination');
        const loadMoreBtn = document.getElementById('wd-load-more');
        if (wds.length === limitPerPage) {
            wdPage++;
            paginationEl.style.display = 'block';
            loadMoreBtn.textContent = 'Muat lebih banyak';
            loadMoreBtn.disabled = false;
        } else {
            wdAllLoaded = true;
            paginationEl.style.display = wdPage > 1 ? 'block' : 'none';
            if (loadMoreBtn) { loadMoreBtn.textContent = 'Semua data sudah dimuat'; loadMoreBtn.disabled = true; }
        }

    } catch (e) {
        console.error(e);
        if (wdPage === 1) tbody.innerHTML = '<tr><td colspan="10" style="text-align:center;padding:28px;color:#e53935">Gagal memuat data</td></tr>';
    }
}

function loadMoreWithdrawals() {
    if (!wdAllLoaded) loadWithdrawals();
}

function wdStatusLabel(status) {
    const labels = {
        pending: 'Pending', approved: 'Approved', processing: 'Processing',
        completed: 'Completed', rejected: 'Ditolak', failed: 'Gagal',
        cancelled: 'Dibatalkan', paid: 'Paid (lama)'
    };
    return labels[status] || status;
}

function buildWdActions(w) {
    const id = w.id;
    const name = esc(w.nama_kafe || '');
    let btns = `<button class="btn-sa-detail" onclick="openWithdrawalDetail(${id})"><i class="fas fa-eye"></i></button>`;
    if (w.status === 'pending') {
        btns += `<button class="btn-sa-approve" onclick="approveWithdrawal(${id})">Approve</button>`;
        btns += `<button class="btn-sa-reject" onclick="openRejectModal('withdrawal', ${id}, '${name}')">Reject</button>`;
    } else if (w.status === 'approved') {
        btns += `<button class="btn-sa-process" onclick="markWdProcessing(${id})">Proses</button>`;
        btns += `<button class="btn-sa-complete" onclick="openCompleteModal(${id})">Selesai</button>`;
        btns += `<button class="btn-sa-reject" onclick="openRejectModal('withdrawal', ${id}, '${name}')">Reject</button>`;
    } else if (w.status === 'processing') {
        btns += `<button class="btn-sa-complete" onclick="openCompleteModal(${id})">Selesai</button>`;
        btns += `<button class="btn-sa-fail" onclick="openFailModal(${id})">Gagal</button>`;
    }
    return btns;
}

// ===== TRANSACTIONS =====
async function loadTransactions(reset = false) {
    if (trxLoading) return;
    if (reset) { trxPage = 1; document.getElementById('trx-tbody').innerHTML = ''; }
    trxLoading = true;
    const tbody = document.getElementById('trx-tbody');

    if (trxPage === 1) {
        tbody.innerHTML = '<tr><td colspan="6" style="text-align:center;padding:28px;color:#9ca3af">Memuat...</td></tr>';
    }

    try {
        const res = await fetch(BASE + 'superadmin/transactions?page=' + trxPage);
        const json = await res.json();
        const trxs = json.data || [];

        if (trxPage === 1 && trxs.length === 0) {
            tbody.innerHTML = '<tr><td colspan="6" style="text-align:center;padding:28px;color:#9ca3af">Belum ada transaksi</td></tr>';
            return;
        }

        const rows = trxs.map((t, i) => `
            <tr>
                <td>${((trxPage - 1) * 50) + i + 1}</td>
                <td>${esc(t.nama_kafe || '-')}</td>
                <td><strong>${formatRp(t.nominal || t.amount || 0)}</strong></td>
                <td><span class="s-badge ${t.status || ''}">${t.status || '-'}</span></td>
                <td>${esc(t.payment_method || t.method || '-')}</td>
                <td>${formatDate(t.created_at)}</td>
            </tr>`).join('');

        if (trxPage === 1) tbody.innerHTML = rows;
        else tbody.insertAdjacentHTML('beforeend', rows);

        const loadMoreBtn = document.getElementById('trx-load-more');
        const paginationEl = document.getElementById('trx-pagination');

        if (trxs.length === 50) {
            trxPage++;
            paginationEl.style.display = 'block';
            loadMoreBtn.textContent = 'Muat lebih banyak';
            loadMoreBtn.disabled = false;
        } else {
            paginationEl.style.display = trxPage > 1 ? 'block' : 'none';
            if (paginationEl.style.display === 'block') loadMoreBtn.textContent = 'Semua data sudah dimuat';
            loadMoreBtn.disabled = true;
        }
    } catch (e) {
        if (trxPage === 1) tbody.innerHTML = '<tr><td colspan="6" style="text-align:center;padding:28px;color:#e53935">Gagal memuat data</td></tr>';
    } finally {
        trxLoading = false;
    }
}

// ===== PENDING ADMINS =====
async function loadPendingAdmins() {
    try {
        const res  = await fetch(BASE + 'superadmin/pending-admins');
        const json = await res.json();
        const admins = json.data || [];

        // Stat card & sidebar badge
        document.getElementById('stat-admin-pending').textContent = admins.length;
        const sideEl = document.getElementById('sidebar-admin-count');
        const quickCount = document.getElementById('quick-admin-pending-count');
        if (admins.length > 0) { sideEl.textContent = admins.length; sideEl.style.display = 'inline'; }
        if (quickCount) quickCount.textContent = admins.length;

        // Quick widget
        const quickEl = document.getElementById('quick-pending-admins');
        if (admins.length === 0) {
            if (quickEl) quickEl.innerHTML = '<div style="padding:24px;text-align:center;color:#9ca3af;font-size:0.83rem"><i class="fas fa-check-circle text-success me-2"></i>Tidak ada pendaftaran admin baru</div>';
        } else if (quickEl) {
            quickEl.innerHTML = '<div class="list-group list-group-flush">' + admins.slice(0, 4).map(a => `
                <div class="list-group-item px-4 py-3">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <div style="font-weight:700;font-size:0.85rem;color:#1a1a2e">${esc(a.name)}</div>
                            <div style="font-size:0.75rem;color:#6b7280">${esc(a.email)}</div>
                        </div>
                        <div style="display:flex;gap:6px;flex-shrink:0">
                            <button class="btn-sa-approve" onclick="approveAdmin(${a.id}, '${esc(a.name)}')">Aktifkan</button>
                            <button class="btn-sa-reject" onclick="openRejectModal('admin', ${a.id}, '${esc(a.name)}')">Tolak</button>
                        </div>
                    </div>
                </div>`).join('') + '</div>';
        }

        // Full table
        const tbody = document.getElementById('pending-admins-tbody');
        if (!tbody) return;
        if (admins.length === 0) {
            tbody.innerHTML = '<tr><td colspan="4" style="text-align:center;padding:28px;color:#9ca3af"><i class="fas fa-check-circle text-success me-2"></i>Tidak ada pendaftaran admin baru</td></tr>';
            return;
        }
        tbody.innerHTML = admins.map(a => `
            <tr id="admin-row-${a.id}">
                <td><strong>${esc(a.name)}</strong></td>
                <td>${esc(a.email)}</td>
                <td>${formatDate(a.created_at)}</td>
                <td>
                    <div style="display:flex;gap:6px">
                        <button class="btn-sa-approve" onclick="approveAdmin(${a.id}, '${esc(a.name)}')">Aktifkan</button>
                        <button class="btn-sa-reject" onclick="openRejectModal('admin', ${a.id}, '${esc(a.name)}')">Tolak</button>
                    </div>
                </td>
            </tr>`).join('');
    } catch(e) { console.error(e); }
}

async function approveAdmin(id, name) {
    if (!confirm('Aktifkan akun admin "' + name + '"?')) return;
    try {
        const res  = await postData('superadmin/admin-approve', { user_id: id });
        const json = await res.json();
        if (json.success) {
            removeRow('admin-row-' + id);
            toast('Admin "' + name + '" berhasil diaktifkan ✓');
            loadPendingAdmins();
        } else { toast(json.message || 'Gagal', true); }
    } catch(e) { toast('Error: ' + e.message, true); }
}

// ===== ACTIONS =====
async function approveCafe(id, name) {
    if (!confirm('Approve cafe "' + name + '"?')) return;
    try {
        const res = await postData('superadmin/cafe-approve', { cafe_id: id });
        const json = await res.json();
        if (json.success) {
            removeRow('pending-row-' + id);
            toast('Cafe "' + name + '" berhasil di-approve ✓');
            refreshStats();
            loadPendingCafes();
        } else {
            toast(json.message || 'Gagal approve cafe', true);
        }
    } catch (e) { toast('Error: ' + e.message, true); }
}

async function approveWithdrawal(id) {
    if (!confirm('Approve withdrawal #' + id + '?')) return;
    try {
        const res = await postData('superadmin/withdrawal-approve', { withdrawal_id: id });
        const json = await res.json();
        if (json.success) {
            removeRow('wd-row-' + id);
            toast('Withdrawal #' + id + ' berhasil di-approve ✓');
            loadWithdrawals();
        } else {
            toast(json.message || 'Gagal approve withdrawal', true);
        }
    } catch (e) { toast('Error: ' + e.message, true); }
}

// Mark processing
async function markWdProcessing(id) {
    if (!confirm('Tandai withdrawal #' + id + ' sebagai SEDANG DIPROSES?\nSilakan lakukan transfer manual setelah ini.')) return;
    try {
        const res = await postData('superadmin/withdrawal-process', { withdrawal_id: id });
        const json = await res.json();
        if (json.success) {
            toast('Withdrawal #' + id + ' ditandai Processing ✓');
            loadWithdrawals(true);
        } else {
            toast(json.message || 'Gagal', true);
        }
    } catch (e) { toast('Error: ' + e.message, true); }
}

// Complete modal
let completeTargetId = null;
function openCompleteModal(id) {
    completeTargetId = id;
    document.getElementById('complete-ref').value = '';
    document.getElementById('complete-notes').value = '';
    document.getElementById('complete-proof').value = '';
    document.getElementById('complete-modal').classList.add('show');
    setTimeout(() => document.getElementById('complete-ref').focus(), 50);
}
function closeCompleteModal() {
    document.getElementById('complete-modal').classList.remove('show');
    completeTargetId = null;
}
async function submitComplete() {
    if (!completeTargetId) return;
    const ref = document.getElementById('complete-ref').value.trim();
    if (!ref || ref.length < 3) { toast('Referensi transfer wajib diisi (minimal 3 karakter)', true); return; }

    const btn = document.getElementById('complete-submit-btn');
    btn.disabled = true;
    btn.textContent = 'Menyimpan...';

    try {
        const formData = new FormData();
        formData.append('withdrawal_id', completeTargetId);
        formData.append('transfer_reference', ref);
        formData.append('notes', document.getElementById('complete-notes').value.trim());
        const proofFile = document.getElementById('complete-proof').files[0];
        if (proofFile) formData.append('transfer_proof', proofFile);
        formData.append(csrfName, csrfToken);

        const res = await fetch(BASE + 'superadmin/withdrawal-complete', {
            method: 'POST',
            body: formData,
            credentials: 'same-origin'
        });
        const json = await res.json();
        if (json.success) {
            closeCompleteModal();
            toast('Transfer berhasil dicatat. Withdrawal selesai ✓');
            loadWithdrawals(true);
        } else {
            toast(json.message || 'Gagal menyimpan', true);
        }
    } catch (e) { toast('Error: ' + e.message, true); }
    finally { btn.disabled = false; btn.textContent = 'Konfirmasi Selesai'; }
}

// Fail modal
let failTargetId = null;
function openFailModal(id) {
    failTargetId = id;
    document.getElementById('fail-reason').value = '';
    document.getElementById('fail-modal').classList.add('show');
    setTimeout(() => document.getElementById('fail-reason').focus(), 50);
}
function closeFailModal() {
    document.getElementById('fail-modal').classList.remove('show');
    failTargetId = null;
}
async function submitFail() {
    if (!failTargetId) return;
    const reason = document.getElementById('fail-reason').value.trim();
    if (reason.length < 5) { toast('Alasan kegagalan minimal 5 karakter', true); return; }
    try {
        const res = await postData('superadmin/withdrawal-fail', { withdrawal_id: failTargetId, failure_reason: reason });
        const json = await res.json();
        if (json.success) {
            closeFailModal();
            toast('Transfer dicatat gagal. Saldo dikembalikan ke kafe ✓');
            loadWithdrawals(true);
        } else { toast(json.message || 'Gagal', true); }
    } catch (e) { toast('Error: ' + e.message, true); }
}

// Detail modal
async function openWithdrawalDetail(id) {
    document.getElementById('wd-detail-modal').classList.add('show');
    document.getElementById('wd-detail-content').innerHTML = '<div style="text-align:center;padding:40px;color:#9ca3af"><i class="fas fa-spinner fa-spin fa-2x"></i></div>';
    try {
        const res = await fetch(BASE + 'superadmin/withdrawals/' + id);
        const json = await res.json();
        if (!json.success) { document.getElementById('wd-detail-content').innerHTML = '<p style="color:#e53935">Gagal memuat detail</p>'; return; }
        const w = json.data;
        const bal = w.cafe_balance || {};
        document.getElementById('wd-detail-content').innerHTML = `
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-bottom:20px">
                <div style="background:#f8fafc;border-radius:10px;padding:14px">
                    <div style="font-size:0.7rem;font-weight:700;color:#9ca3af;text-transform:uppercase;letter-spacing:0.5px">Kafe</div>
                    <div style="font-size:0.95rem;font-weight:700;color:#1a1a2e;margin-top:4px">${esc(w.nama_kafe || '-')}</div>
                    <div style="font-size:0.78rem;color:#6b7280">${esc(w.admin_name || '')} · ${esc(w.admin_email || '')}</div>
                </div>
                <div style="background:#f8fafc;border-radius:10px;padding:14px">
                    <div style="font-size:0.7rem;font-weight:700;color:#9ca3af;text-transform:uppercase;letter-spacing:0.5px">Status</div>
                    <div style="margin-top:6px"><span class="s-badge ${w.status}">${wdStatusLabel(w.status)}</span></div>
                    <div style="font-size:0.75rem;color:#6b7280;margin-top:4px">Diajukan: ${formatDate(w.created_at)}</div>
                </div>
            </div>
            <div style="background:#fff7ed;border-radius:10px;padding:14px;margin-bottom:16px;text-align:center">
                <div style="font-size:0.7rem;font-weight:700;color:#d97706;text-transform:uppercase">Jumlah Withdrawal</div>
                <div style="font-size:1.8rem;font-weight:900;color:#1a1a2e">${formatRp(w.amount)}</div>
            </div>
            <table style="width:100%;font-size:0.83rem;border-collapse:collapse;margin-bottom:16px">
                <tr style="border-bottom:1px solid #f3f4f6">
                    <td style="padding:8px 0;color:#6b7280;width:40%">Tujuan</td>
                    <td style="padding:8px 0;font-weight:600">${w.destination_type === 'e_wallet' ? 'E-Wallet' : 'Transfer Bank'}</td>
                </tr>
                <tr style="border-bottom:1px solid #f3f4f6">
                    <td style="padding:8px 0;color:#6b7280">Bank / Provider</td>
                    <td style="padding:8px 0;font-weight:600">${esc(w.bank_name || '-')}</td>
                </tr>
                <tr style="border-bottom:1px solid #f3f4f6">
                    <td style="padding:8px 0;color:#6b7280">No. Rekening / Akun</td>
                    <td style="padding:8px 0;font-weight:700;font-family:monospace">${esc(w.account_number || '-')}</td>
                </tr>
                <tr style="border-bottom:1px solid #f3f4f6">
                    <td style="padding:8px 0;color:#6b7280">Atas Nama</td>
                    <td style="padding:8px 0;font-weight:600">${esc(w.account_holder || '-')}</td>
                </tr>
                ${w.notes ? `<tr style="border-bottom:1px solid #f3f4f6"><td style="padding:8px 0;color:#6b7280">Catatan Admin</td><td style="padding:8px 0">${esc(w.notes)}</td></tr>` : ''}
                ${w.approved_by_name ? `<tr style="border-bottom:1px solid #f3f4f6"><td style="padding:8px 0;color:#6b7280">Disetujui oleh</td><td style="padding:8px 0">${esc(w.approved_by_name)} · ${formatDate(w.approved_at)}</td></tr>` : ''}
                ${w.processed_by_name ? `<tr style="border-bottom:1px solid #f3f4f6"><td style="padding:8px 0;color:#6b7280">Diproses oleh</td><td style="padding:8px 0">${esc(w.processed_by_name)} · ${formatDate(w.processed_at)}</td></tr>` : ''}
                ${w.transfer_reference ? `<tr style="border-bottom:1px solid #f3f4f6"><td style="padding:8px 0;color:#6b7280">Ref. Transfer</td><td style="padding:8px 0;font-family:monospace;font-weight:700;color:#059669">${esc(w.transfer_reference)}</td></tr>` : ''}
                ${w.rejection_reason ? `<tr style="border-bottom:1px solid #f3f4f6"><td style="padding:8px 0;color:#6b7280">Alasan Tolak</td><td style="padding:8px 0;color:#dc2626">${esc(w.rejection_reason)}</td></tr>` : ''}
                ${w.failure_reason ? `<tr style="border-bottom:1px solid #f3f4f6"><td style="padding:8px 0;color:#6b7280">Alasan Gagal</td><td style="padding:8px 0;color:#dc2626">${esc(w.failure_reason)}</td></tr>` : ''}
            </table>
            <div style="background:#f0fdf4;border-radius:10px;padding:12px;margin-bottom:16px">
                <div style="font-size:0.7rem;font-weight:700;color:#15803d;text-transform:uppercase;margin-bottom:8px">Saldo Kafe Saat Ini</div>
                <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:8px;text-align:center">
                    <div><div style="font-size:0.68rem;color:#6b7280">Tersedia</div><div style="font-weight:700;color:#1a1a2e">${formatRp(bal.available_balance || 0)}</div></div>
                    <div><div style="font-size:0.68rem;color:#6b7280">Ditahan</div><div style="font-weight:700;color:#d97706">${formatRp(bal.held_balance || 0)}</div></div>
                    <div><div style="font-size:0.68rem;color:#6b7280">Total Ditarik</div><div style="font-weight:700;color:#374151">${formatRp(bal.total_withdrawn || 0)}</div></div>
                </div>
            </div>
            <div style="display:flex;gap:8px;flex-wrap:wrap;justify-content:flex-end">
                ${buildWdActions(w)}
                <button class="btn-sa-reject" onclick="closeDetailModal()">Tutup</button>
            </div>
        `;
    } catch (e) { document.getElementById('wd-detail-content').innerHTML = '<p style="color:#e53935">Error: ' + e.message + '</p>'; }
}
function closeDetailModal() {
    document.getElementById('wd-detail-modal').classList.remove('show');
}

// ===== REJECT MODAL =====
function openRejectModal(type, id, name) {
    rejectTarget = { type, id, name };
    document.getElementById('reject-reason').value = '';
    document.getElementById('reject-modal-title').textContent = 'Tolak: ' + name;
    document.getElementById('reject-modal').classList.add('show');
    setTimeout(() => document.getElementById('reject-reason').focus(), 50);
}

function closeRejectModal() {
    document.getElementById('reject-modal').classList.remove('show');
    rejectTarget = null;
}

async function submitReject() {
    if (!rejectTarget) return;
    const reason = document.getElementById('reject-reason').value.trim();
    if (reason.length < 10) { toast('Alasan minimal 10 karakter', true); return; }

    let endpoint, payload;
    if (rejectTarget.type === 'cafe') {
        endpoint = 'superadmin/cafe-reject'; payload = { cafe_id: rejectTarget.id, reason };
    } else if (rejectTarget.type === 'admin') {
        endpoint = 'superadmin/admin-reject'; payload = { user_id: rejectTarget.id, reason };
    } else {
        endpoint = 'superadmin/withdrawal-reject'; payload = { withdrawal_id: rejectTarget.id, reason };
    }

    try {
        const res = await postData(endpoint, payload);
        const json = await res.json();
        if (json.success) {
            const rowId = (rejectTarget.type === 'cafe' ? 'pending-row-' : 'wd-row-') + rejectTarget.id;
            removeRow(rowId);
            toast('Ditolak: ' + rejectTarget.name);
            closeRejectModal();
            if (rejectTarget.type === 'cafe') loadPendingCafes();
            else if (rejectTarget.type === 'admin') loadPendingAdmins();
            else loadWithdrawals();
        } else {
            toast(json.message || 'Gagal menolak', true);
        }
    } catch (e) { toast('Error: ' + e.message, true); }
}

// Close modal on overlay click
document.getElementById('reject-modal').addEventListener('click', function(e) {
    if (e.target === this) closeRejectModal();
});

// ===== HELPERS =====
async function postData(endpoint, data) {
    const body = new URLSearchParams({ ...data, [csrfName]: csrfToken });
    return fetch(BASE + endpoint, {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded', 'X-Requested-With': 'XMLHttpRequest' },
        body: body.toString()
    });
}

function refreshStats() {
    // Re-load from PHP by reloading the stats from CI data (already rendered server-side)
    // For live refresh without page reload, we'd need a stats API endpoint
    // For now, reload if needed
}

function removeRow(id) {
    const el = document.getElementById(id);
    if (el) { el.style.opacity = '0'; el.style.transition = 'opacity 0.3s'; setTimeout(() => el.remove(), 300); }
}

function formatRp(n) {
    if (!n && n !== 0) return '-';
    return 'Rp ' + Number(n).toLocaleString('id-ID', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
}

function formatDate(d) {
    if (!d) return '-';
    const dt = new Date(d);
    if (isNaN(dt)) return d;
    return dt.toLocaleDateString('id-ID', { day: '2-digit', month: 'short', year: 'numeric', hour: '2-digit', minute: '2-digit' });
}

function esc(s) {
    if (!s) return '';
    return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;').replace(/'/g,'&#39;');
}

function toast(msg, isError = false) {
    const wrap = document.getElementById('toast-wrap');
    const el = document.createElement('div');
    el.className = 'sa-toast' + (isError ? ' error' : '');
    el.innerHTML = `<i class="fas ${isError ? 'fa-exclamation-circle' : 'fa-check-circle'}"></i> ${esc(msg)}`;
    wrap.appendChild(el);
    setTimeout(() => { el.style.opacity = '0'; el.style.transition = 'opacity 0.4s'; setTimeout(() => el.remove(), 400); }, 3500);
}

// ===== CREATE CAFE MODAL =====
function togglePaymentFields() {
    const method = document.getElementById('new_cafe_method').value;
    document.getElementById('new_bank_group').style.display   = method === 'bank_transfer' ? 'block' : 'none';
    document.getElementById('new_ewallet_group').style.display = method === 'e_wallet'     ? 'block' : 'none';
}

function openCreateCafeModal() {
    document.getElementById('createCafeForm').reset();
    togglePaymentFields();
    document.getElementById('create-cafe-modal').style.display = 'flex';
    loadAvailableAdmins();
}

function closeCreateCafeModal() {
    document.getElementById('create-cafe-modal').style.display = 'none';
}

async function loadAvailableAdmins() {
    const select = document.getElementById('new_cafe_admin');
    select.innerHTML = '<option value="">Memuat admin...</option>';
    try {
        const res = await fetch(BASE + 'superadmin/available-admins');
        const json = await res.json();
        const admins = json.data || [];
        if (admins.length === 0) {
            select.innerHTML = '<option value="">(Tidak ada admin kosong)</option>';
            return;
        }
        select.innerHTML = '<option value="">-- Pilih Akun Admin --</option>' + 
            admins.map(a => `<option value="${a.id}">${esc(a.name)} (${esc(a.email)})</option>`).join('');
    } catch(e) {
        select.innerHTML = '<option value="">Gagal memuat admin</option>';
    }
}

async function toggleCafeActive(cafeId, currentActive) {
    const label = currentActive ? 'nonaktifkan' : 'aktifkan';

    if (!confirm(`Yakin ingin ${label} kafe ini?`)) {
        return;
    }

    try {
        const res = await postData('superadmin/cafe-toggle-active', {
            cafe_id: cafeId
        });

        const json = await res.json();

        if (json.success) {
            toast(json.message);
            loadAllCafes();
        } else {
            toast(json.message || 'Gagal', true);
        }
    } catch (e) {
        console.error(e);
        toast('Terjadi kesalahan', true);
    }
}

// Handle form submit
document.getElementById('createCafeForm').addEventListener('submit', async function(e) {
    e.preventDefault();
    const btn = document.getElementById('submitCreateCafeBtn');
    btn.disabled = true;
    btn.textContent = 'Menyimpan...';

    const formData = new FormData(this);
    const data = Object.fromEntries(formData);
    
    if (!data.is_active_now) {
        data.is_active_now = '0';
    }

    try {
        const res = await postData('superadmin/cafe-create', data);
        const json = await res.json();
        if (json.success) {
            toast(json.message || 'Kafe berhasil dibuat!');
            closeCreateCafeModal();
            setTimeout(() => location.reload(), 1000);
        } else {
            toast(json.message || 'Gagal menyimpan kafe', true);
        }
    } catch(e) {
        toast('Error: ' + e.message, true);
    } finally {
        btn.disabled = false;
        btn.textContent = 'Simpan Kafe';
    }
});

// Close modal on click overlay
document.getElementById('create-cafe-modal').addEventListener('click', function(e) {
    if (e.target === this) closeCreateCafeModal();
});

// ===== MOBILE SIDEBAR DRAWER =====
function openSidebar() {
    document.getElementById('saSidebar').classList.add('open');
    document.getElementById('saSidebarOverlay').classList.add('show');
    document.body.style.overflow = 'hidden';
}

function closeSidebar() {
    document.getElementById('saSidebar').classList.remove('open');
    document.getElementById('saSidebarOverlay').classList.remove('show');
    document.body.style.overflow = '';
}

// Swipe-to-close sidebar
let _swipeStartX = 0;
document.getElementById('saSidebar').addEventListener('touchstart', function(e) {
    _swipeStartX = e.changedTouches[0].screenX;
}, { passive: true });
document.getElementById('saSidebar').addEventListener('touchend', function(e) {
    if (_swipeStartX - e.changedTouches[0].screenX > 60) closeSidebar();
}, { passive: true });

// ===== REVOKE ALL DEVICES =====
async function revokeDevices() {
    if (!confirm('Cabut semua perangkat terpercaya?\n\nAnda harus verifikasi OTP lagi di setiap perangkat untuk dapat masuk kembali.')) return;
    closeSidebar();
    try {
        const res = await fetch(BASE + 'superadmin/revoke-devices', {
            method: 'POST',
            headers: { 'X-Requested-With': 'XMLHttpRequest', 'Content-Type': 'application/json' }
        });
        const json = await res.json();
        if (json.success) {
            toast('Semua perangkat dicabut. Mengarahkan ke verifikasi...');
            setTimeout(() => { window.location.href = '/superadmin/verify-device'; }, 1500);
        } else {
            toast(json.message || 'Gagal mencabut perangkat', true);
        }
    } catch(e) {
        toast('Error: ' + e.message, true);
    }
}

</script>
<?= $this->endSection() ?>
