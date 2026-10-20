<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="<?= $description ?? 'Song Request - Layanan Request Lagu Berbasis Saweran' ?>">
    <title><?= $title ?? 'Song Request' ?></title>

    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">

    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&family=Inter:wght@400;500;700&display=swap" rel="stylesheet">

    <!-- Custom CSS -->
    <link href="<?= base_url('assets/css/style.css') ?>" rel="stylesheet">
    <link href="<?= base_url('assets/css/landingbaru.css') ?>" rel="stylesheet">
    <script>
        // Bersihkan parameter _tab_token dari address bar jika pengguna membuka link lama / bookmark
        if (window.location.search && window.location.search.indexOf('_tab_token=') !== -1) {
            try {
                var _cleanUrl = new URL(window.location.href);
                _cleanUrl.searchParams.delete('_tab_token');
                window.history.replaceState({}, document.title, _cleanUrl.pathname + (_cleanUrl.search ? _cleanUrl.search : '') + _cleanUrl.hash);
            } catch (e) {}
        }
    </script>
    <?= $this->renderSection('css') ?>
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-sr sticky-top">
        <div class="container-lg">
            <a class="navbar-brand" href="<?= base_url('/') ?>">
                <i class="fas fa-compact-disc text-coral"></i>
                <span>Song Request</span>
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <i class="fas fa-bars" style="color: var(--text-dark);"></i>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto align-items-lg-center gap-1">
                    <?php if (session()->has('user_id')): ?>
                        <!-- Logged in navigation -->
                        <?php if (session()->get('user_role') === 'admin'): ?>
                            <li class="nav-item">
                                <a class="nav-link" href="/admin/dashboard"><i class="fas fa-store me-1"></i>Dashboard Kafe</a>
                            </li>
                        <?php elseif (session()->get('user_role') === 'superadmin'): ?>
                            <li class="nav-item">
                                <a class="nav-link" href="/superadmin/dashboard"><i class="fas fa-shield-alt me-1"></i>Superadmin</a>
                            </li>
                        <?php else: ?>
                            <!-- User menu -->
                            <li class="nav-item">
                                <a class="nav-link" href="/user/dashboard"><i class="fas fa-home me-1"></i>Dashboard</a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" href="/song-request/request"><i class="fas fa-music me-1"></i>Request Lagu</a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" href="/user/history"><i class="fas fa-history me-1"></i>Riwayat</a>
                            </li>
                        <?php endif; ?>

                        <li class="nav-item dropdown ms-lg-2">
                            <a class="nav-link dropdown-toggle btn-secondary-custom px-3 py-2 rounded-pill d-inline-flex align-items-center w-100 justify-content-between justify-content-lg-start" href="javascript:void(0)" id="userProfileDropdown" role="button" data-bs-toggle="dropdown" data-bs-display="static" aria-expanded="false">
                                <span class="d-inline-flex align-items-center">
                                    <i class="fas fa-user-circle me-2 text-coral"></i>
                                    <span><?= esc(session()->get('user_name') ?? 'Akun') ?></span>
                                </span>
                            </a>
                            <ul class="dropdown-menu dropdown-menu-end shadow-sm border-0 mt-2" aria-labelledby="userProfileDropdown" id="userProfileDropdownMenu">
                                <li>
                                    <div class="px-3 py-2 border-bottom mb-1 d-lg-none">
                                        <div class="fw-bold text-dark small"><?= esc(session()->get('user_name') ?? 'Pengguna') ?></div>
                                        <div class="text-muted" style="font-size: 0.75rem;"><?= esc(session()->get('user_email') ?? '') ?></div>
                                        <span class="badge bg-danger bg-opacity-10 text-danger mt-1" style="font-size: 0.68rem; text-transform: uppercase;">
                                            <?= esc(session()->get('user_role') ?? 'user') ?>
                                        </span>
                                    </div>
                                </li>
                                <li><a class="dropdown-item py-2" href="/user/profile"><i class="fas fa-id-card me-2 text-coral"></i>Profil Saya</a></li>
                                <?php if (session()->get('user_role') === 'admin'): ?>
                                    <li><a class="dropdown-item py-2" href="/admin/dashboard"><i class="fas fa-store me-2 text-muted"></i>Dashboard Kafe</a></li>
                                <?php elseif (session()->get('user_role') === 'superadmin'): ?>
                                    <li><a class="dropdown-item py-2" href="/superadmin/dashboard"><i class="fas fa-shield-alt me-2 text-muted"></i>Dashboard Superadmin</a></li>
                                <?php endif; ?>
                                <li><hr class="dropdown-divider my-1"></li>
                                <li><a class="dropdown-item py-2 text-danger" href="/auth/logout"><i class="fas fa-sign-out-alt me-2"></i>Keluar</a></li>
                            </ul>
                        </li>
                    <?php else: ?>
                        <!-- Guest navigation -->
                        <li class="nav-item">
                            <a class="nav-link" href="#" data-bs-toggle="modal" data-bs-target="#partnerModal"><i class="fas fa-handshake me-1 text-coral"></i>Jadi Partner</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="/auth/login"><i class="fas fa-sign-in-alt me-1"></i>Masuk</a>
                        </li>
                        <li class="nav-item ms-lg-2 mt-2 mt-lg-0">
                            <a class="btn btn-coral btn-sm px-4 py-2" href="/song-request/request">
                                <i class="fas fa-music me-1"></i>Request Lagu
                            </a>
                        </li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <main>
        <?= $this->renderSection('content') ?>
    </main>

    <!-- Footer -->
    <footer class="footer-sr">
        <div class="container-lg">
            <div class="row gy-4">
                <!-- Brand -->
                <div class="col-lg-5">
                    <div class="footer-brand">
                        <h4>
                            <i class="fas fa-music text-coral me-2"></i>Song Request
                        </h4>
                        <p>
                            Jadikan setiap lagu sebagai bagian dari pengalaman nongkrong.
                            Pilih lagu favoritmu, masuk antrean, dan ciptakan suasana yang lebih hidup di kafe Small Space.
                        </p>
                    </div>
                </div>
                <!-- Navigasi -->
                <div class="col-md-3">
                    <h6>Navigasi</h6>
                    <ul class="footer-links">
                        <li><a href="<?= base_url('/') ?>"><i class="fas fa-chevron-right me-1 small"></i> Beranda</a></li>
                        <li><a href="<?= base_url('/#how-it-works') ?>"><i class="fas fa-chevron-right me-1 small"></i> Cara Kerja</a></li>
                        <li><a href="<?= base_url('/#features') ?>"><i class="fas fa-chevron-right me-1 small"></i> Keunggulan</a></li>
                        <li><a href="<?= base_url('/song-request/request') ?>"><i class="fas fa-chevron-right me-1 small"></i> Request Lagu</a></li>
                    </ul>
                </div>

                <!-- Kontak -->
                <div class="col-md-4">
                    <h6>Hubungi Kami</h6>
                    <ul class="footer-links">
                        <li><i class="fas fa-envelope text-coral me-2"></i> contact@songrequest.id</li>
                        <li><i class="fas fa-phone text-coral me-2"></i> +62 812 3456 7890</li>
                        <li><i class="fas fa-map-marker-alt text-coral me-2"></i> Yogyakarta, Indonesia</li>
                    </ul>
                </div>
            </div>

            <div class="footer-bottom">
                <p class="mb-0">
                    &copy; 2026 Song Request Kafe Small Space. Seluruh hak cipta dilindungi.
                </p>
            </div>
        </div>
    </footer>


     


    <!-- Bootstrap 5 JS Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <!-- Global Helpers -->
    <script src="<?= base_url('assets/js/helpers.js') ?>"></script>

    <!-- Custom JS -->
    <script src="<?= base_url('assets/js/script.js') ?>"></script>
    <script src="<?= base_url('assets/js/landing.js') ?>"></script>

    <!-- Automatic Flash Message Toasts -->
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        <?php if (session()->has('success')): ?>
            showAlert(<?= json_encode(session()->get('success')) ?>, 'success');
        <?php endif; ?>
        <?php if (session()->has('error')): ?>
            showAlert(<?= json_encode(session()->get('error')) ?>, 'danger');
        <?php endif; ?>
        <?php if (session()->has('warning')): ?>
            showAlert(<?= json_encode(session()->get('warning')) ?>, 'warning');
        <?php endif; ?>
        <?php if (session()->has('info')): ?>
            showAlert(<?= json_encode(session()->get('info')) ?>, 'info');
        <?php endif; ?>
    });
    </script>

    <!-- Modal Partner -->
    <div class="modal fade" id="partnerModal" tabindex="-1" aria-labelledby="partnerModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content" style="border-radius: 16px; border: 1px solid rgba(224,90,71,0.15); background: #ffffff; color: #34373C;">
                <div class="modal-header border-0 pb-0">
                    <h5 class="modal-title fw-bold" id="partnerModalLabel" style="color: #34373C;">
                        <i class="fas fa-handshake text-danger me-2"></i>Jadi Partner Kafe Kami
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body pt-3 text-start">
                    <p class="text-muted small mb-4">Bergabunglah dengan Song Request untuk menciptakan suasana nongkrong yang lebih interaktif dan menarik bagi pengunjung kafe Anda.</p>
                    
                    <h6 class="fw-bold mb-3"><i class="fas fa-star text-warning me-2"></i>Keuntungan Utama Partner:</h6>
                    <ul class="list-unstyled mb-4">
                        <li class="mb-2 d-flex align-items-start"><i class="fas fa-check-circle text-success me-2 mt-1"></i> <span><strong>Bagi Hasil Saweran:</strong> Tambahan revenue otomatis dari setiap request lagu yang diprioritaskan.</span></li>
                        <li class="mb-2 d-flex align-items-start"><i class="fas fa-check-circle text-success me-2 mt-1"></i> <span><strong>Sistem Queue Cerdas:</strong> Antrean FIFO (gratis) & Priority (berbayar) berjalan berdampingan secara otomatis.</span></li>
                        <li class="mb-2 d-flex align-items-start"><i class="fas fa-check-circle text-success me-2 mt-1"></i> <span><strong>Panel Operator / DJ:</strong> Halaman kontrol antrean lagu yang intuitif dan real-time.</span></li>
                        <li class="mb-2 d-flex align-items-start"><i class="fas fa-check-circle text-success me-2 mt-1"></i> <span><strong>Integrasi Spotify:</strong> Koneksikan akun Spotify Premium kafe Anda untuk memutar lagu langsung.</span></li>
                    </ul>

                    <p class="text-muted small">Klik tombol di bawah untuk membuka email pendaftaran kemitraan. Isi data kafe Anda pada draf email yang tersedia.</p>
                    <p class="small text-muted mt-3">
                        Data yang Anda kirimkan akan digunakan hanya untuk proses verifikasi kemitraan.
                        Tim Song Request akan menghubungi Anda melalui email atau WhatsApp setelah proses peninjauan selesai.
                    </p>
                </div>
                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-secondary rounded-pill px-4" data-bs-dismiss="modal">Batal</button>
                    <a href="mailto:ahmadikhsansetiaji@gmail.com?subject=Pendaftaran%20Partner%20Kafe%20-%20Song%20Request&body=Halo%20Tim%20Song%20Request,%0D%0A%0D%0ASaya%20tertarik%20untuk%20mendaftarkan%20kafe%20saya%20sebagai%20partner.%20Berikut%20informasi%20kafe%20kami:%0D%0A%0D%0A===%20DATA%20KAFE%20===%0D%0A-%20Nama%20Kafe:%20%0D%0A-%20Alamat%20Lengkap:%20%0D%0A-%20Deskripsi%20Singkat%20Kafe:%20%0D%0A-%20Nomor%20Telepon%20Kafe:%20%0D%0A-%20Instagram%20Kafe:%20%0D%0A%0D%0A===%20DATA%20ADMIN%20KAFE%20===%0D%0A-%20Nama%20Lengkap:%20%0D%0A-%20Email%20Admin:%20%0D%0A-%20Nomor%20WhatsApp:%20%0D%0A%0D%0A===%20INFORMASI%20PEMBAYARAN%20===%0D%0A-%20Nama%20Penerima%20Dana:%20%0D%0A-%20Metode%20Pencairan%20(Bank/E-Wallet):%20%0D%0A-%20Nomor%20Rekening%20/%20Nomor%20E-Wallet:%20%0D%0A%0D%0AMohon%20informasi%20langkah%20selanjutnya.%20Terima%20kasih." class="btn btn-danger rounded-pill px-4" id="confirmPartnerMailBtn">
                        <i class="fas fa-paper-plane me-1"></i>Kirim Email Kemitraan
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Mobile Dropdown Helper -->
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const userDropdown = document.getElementById('userProfileDropdown');
        const userMenu = document.getElementById('userProfileDropdownMenu');
        if (userDropdown && userMenu) {
            userDropdown.addEventListener('click', function(e) {
                if (window.innerWidth < 992) {
                    e.preventDefault();
                    e.stopPropagation();
                    const isShown = userMenu.classList.contains('show');
                    if (isShown) {
                        userMenu.classList.remove('show');
                        this.setAttribute('aria-expanded', 'false');
                    } else {
                        userMenu.classList.add('show');
                        this.setAttribute('aria-expanded', 'true');
                    }
                }
            });

            document.addEventListener('click', function(e) {
                if (!e.target.closest('#userProfileDropdown') && !e.target.closest('#userProfileDropdownMenu')) {
                    if (userMenu.classList.contains('show')) {
                        userMenu.classList.remove('show');
                        if (userDropdown) userDropdown.setAttribute('aria-expanded', 'false');
                    }
                }
            });
        }
    });
    </script>

    <?= $this->renderSection('js') ?>
</body>
</html>

