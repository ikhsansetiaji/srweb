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

    <script src="<?= base_url('assets/js/tab-session.js') ?>"></script>

    <?= $this->renderSection('css') ?>
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark sticky-top" style="background-color: #333333;">
        <div class="container-lg">
            <a class="navbar-brand fw-bold" href="<?= base_url('/') ?>">
                <i class="fas fa-music" style="color: #FF4500;"></i> Song Request
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <?php if (session()->has('user_id')): ?>
                        <!-- Logged in navigation -->
                        <?php if (session()->get('user_role') === 'admin'): ?>
                            <li class="nav-item">
                                <a class="nav-link" href="/admin/dashboard">Dashboard</a>
                            </li>
                        <?php elseif (session()->get('user_role') === 'superadmin'): ?>
                            <li class="nav-item">
                                <a class="nav-link" href="/superadmin/dashboard">Dashboard</a>
                            </li>
                        <?php else: ?>
                            <li class="nav-item">
                                <a class="nav-link" href="/song-request/history">Riwayat</a>
                            </li>
                        <?php endif; ?>

                        <li class="nav-item">
                            <a class="nav-link" href="/auth/logout">Logout</a>
                        </li>
                    <?php else: ?>
                        <!-- Guest navigation -->
                        <li class="nav-item">
                            <a class="nav-link" href="/auth/login">Login</a>
                        </li>
                        <li class="nav-item ms-2">
                            <a class="btn btn-danger btn-sm" href="/song-request/request">Request Lagu</a>
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
    <footer class="footer">
        <div class="container-lg">
            <div class="row gy-5">
                <!-- Brand -->
                <div class="col-lg-5">
                    <div class="footer-brand">
                        <h4>
                            🎵 Song Request
                        </h4>
                        <p>
                            Jadikan setiap lagu sebagai bagian dari pengalaman
                            nongkrong. Pilih lagu favoritmu, masuk antrian,
                            dan ciptakan suasana yang lebih hidup di kafe.
                        </p>
                    </div>
                </div>
                <!-- Navigasi -->
                <div class="col-md-3">
                    <h6>
                        Navigasi
                    </h6>
                    <ul class="footer-links">
                        <li>
                            <a href="#home">
                                Beranda
                            </a>
                        </li>
                        <li>
                            <a href="#how-it-works">
                                Cara Kerja
                            </a>
                        </li>

                        <li>
                            <a href="#features">
                                Keunggulan
                            </a>
                        </li>
                    </ul>

                </div>

                <!-- Kontak -->
                <div class="col-md-4">
                    <h6>
                        Hubungi Kami
                    </h6>

                    <ul class="footer-links">

                        <li>
                            <i class="fas fa-envelope me-2"></i>
                            contact@songrequest.id
                        </li>

                        <li>
                            <i class="fas fa-phone me-2"></i>
                            +62 812 3456 7890
                        </li>

                        <li>
                            <i class="fas fa-map-marker-alt me-2"></i>
                            Indonesia
                        </li>

                    </ul>

                </div>

            </div>

            <div class="footer-bottom">

                <p>
                    © 2026 Song Request. Dibuat untuk menghadirkan pengalaman musik yang lebih personal.
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

    <?= $this->renderSection('js') ?>
</body>
</html>

