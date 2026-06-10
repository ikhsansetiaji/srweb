<?= $this->extend('layout/app') ?>

<?= $this->section('css') ?>
<link href="<?= base_url('assets/css/landingbaru.css') ?>" rel="stylesheet">
<?= $this->endSection() ?>

<?= $this->section('content') ?>

<!-- Hero Section -->
<section class="hero-section">

    <div class="hero-bg">

        <div class="music-float music-1">
            <i class="fas fa-music"></i>
        </div>

        <div class="music-float music-2">
            <i class="fas fa-headphones"></i>
        </div>

        <div class="music-float music-3">
            <i class="fas fa-compact-disc"></i>
        </div>

        <div class="music-float music-4">
            <i class="fas fa-wave-square"></i>
        </div>

        <div class="music-float music-5">
            <i class="fas fa-play"></i>
        </div>

    </div>

    <div class="container-lg">

        <div class="hero-center">

            <h1 class="hero-title">
                Putar Lagu Favoritmu
                <span>Langsung di Kafe</span>
            </h1>

            <div class="typewriter-container">
                <span class="typewriter"></span>
                <span class="cursor"></span>
            </div>

            <div class="hero-cta">

                <a href="/song-request/request" class="btn btn-danger btn-sm ms-2">
                    Request Lagu
                </a>

                <a href="#how-it-works"
                   class="btn btn-secondary-custom">
                    Lihat Cara Kerja
                </a>

            </div>

        </div>

    </div>

</section>

<!-- partner  -->
<section class="partner-section">

    <div class="container-lg">

        <div class="text-center mb-5">

            <h2 class="fw-bold">
                Digunakan Oleh Tempat Nongkrong Pilihan
            </h2>

            <p class="text-muted">
                Hadir untuk membantu menciptakan pengalaman musik yang lebih interaktif.
            </p>

        </div>

        <div class="partner-grid">

            <div class="partner-card">
                <h5>Small Space</h5>
                <span>Yogyakarta</span>
            </div>

            <div class="partner-card">
                <h5>Kafe A</h5>
                <span>Di Suatu Tempat</span>
            </div>

            <div class="partner-card">
                <h5>Kafe B</h5>
                <span>Di Suatu Tempat</span>
            </div>

            <div class="partner-card">
                <h5>Kafe C</h5>
                <span>Di Suatu Tempat</span>
            </div>

        </div>

    </div>

</section>


<!-- How It Works Section -->
<section id="how-it-works" class="how-section">

    <div class="container">

        <div class="text-center mb-5">

            <h2 class="how-title">
                Bagaimana Cara Kerjanya?
            </h2>

            <p class="how-desc">
                Hanya perlu beberapa langkah sederhana untuk
                memutar lagu favoritmu di kafe.
            </p>

        </div>

        <div class="how-grid">

            <div class="how-card">

                <div class="how-number">
                    01
                </div>

                <i class="fas fa-search"></i>

                <h5>Pilih Lagu</h5>

                <p>
                    Cari lagu favoritmu dari jutaan lagu
                    yang tersedia.
                </p>

            </div>

            <div class="how-arrow">
                →
            </div>

            <div class="how-card">

                <div class="how-number">
                    02
                </div>

                <i class="fas fa-bolt"></i>

                <h5>Pilih Antrean</h5>

                <p>
                    Lagumu terlalu keren untuk antre lama, scan QRIS atau metode yang tersedia, sawer sekarang agar langsung diputar!
                </p>

            </div>

            <div class="how-arrow">
                →
            </div>

            <div class="how-card">

                <div class="how-number">
                    03
                </div>

                <i class="fas fa-music"></i>

                <h5>Lagu Diputar</h5>

                <p>
                    Lagu masuk antrean, siap diputar.
                </p>

            </div>

        </div>

    </div>

</section>

<!-- Features Section -->
<section id="features" class="features py-5">
    <div class="feature-grid">

        <div class="feature-box">
            <span class="feature-emoji">🎵</span>

            <h4>Musik Sesuai Suasana</h4>

            <p>
                Pengunjung ikut menentukan lagu
                yang diputar sehingga suasana terasa
                lebih hidup dan personal.
            </p>
        </div>

        <div class="feature-box">
            <span class="feature-emoji">⚡</span>

            <h4>Priority atau FIFO</h4>

            <p>
                Request gratis tetap berjalan melalui
                FIFO Queue, atau gunakan Priority Queue
                agar lagu diputar lebih cepat.
            </p>
        </div>

        <div class="feature-box">
            <span class="feature-emoji">☕</span>

            <h4>Nongkrong Jadi Lebih Personal</h4>

            <p>
                Musik favorit membuat pengalaman
                berkumpul terasa lebih dekat dan berkesan.
            </p>
        </div>

        <div class="feature-box">
            <span class="feature-emoji">💸</span>

            <h4>Tambahan Revenue Untuk Kafe</h4>

            <p>
                Saweran lagu dapat menjadi sumber
                pemasukan tambahan tanpa mengubah
                operasional yang sudah berjalan.
            </p>
        </div>

    </div>
</section>



<!-- CTA -->
<!--<div class="text-center mt-5">-->
<!--    <a href="#" class="btn btn-danger btn-lg">-->
<!--        <i class="fas fa-play-circle"></i> Request Lagu Sekarang-->
<!--    </a>-->
<!--</div>-->
<!--</div>-->
<!-- CTA Section -->
<section id="contact" class="cta-section">
    <div class="container-lg">
        <div class="cta-card">
            <div class="cta-content">
            <span class="cta-emoji">
                🎵
            </span>

                <h2>
                    Lagu Favoritmu Belum Diputar?
                </h2>

                <p>
                    Jangan hanya mendengarkan playlist orang lain.
                    Pilih lagu yang ingin kamu dengar dan jadikan
                    suasana nongkrong lebih personal.
                </p>

                <div class="cta-buttons">
                    <a href="/song-request/request" class="btn-cta-primary">
                        Request Lagu Sekarang
                    </a>
                </div>
            </div>
        </div>
    </div>
</section>


<section id="faq" class="faq-section">
    <div class="container-lg">
        <div class="text-center mb-5">
            <h2 class="fw-bold">
                Masih Punya Pertanyaan?
            </h2>

            <p class="text-muted">
                Berikut beberapa hal yang sering ditanyakan pengunjung.
            </p>
        </div>

        <div class="faq-wrapper">
            <div class="accordion" id="faqAccordion">
                <!-- FAQ 1 -->

                <div class="accordion-item">
                    <h2 class="accordion-header">
                        <button class="accordion-button" type="button"
                                data-bs-toggle="collapse"
                                data-bs-target="#faq1">

                            Apakah saya harus membayar untuk request lagu?
                        </button>
                    </h2>

                    <div id="faq1"
                         class="accordion-collapse collapse show"
                         data-bs-parent="#faqAccordion">

                        <div class="accordion-body">

                            Tidak selalu. Lagu tetap dapat masuk ke antrian
                            secara gratis melalui sistem FIFO Queue.
                            Jika ingin lagu diputar lebih cepat, Anda dapat
                            menggunakan Priority Queue.

                        </div>
                    </div>
                </div>

                <!-- FAQ 2 -->
                <div class="accordion-item">
                    <h2 class="accordion-header">
                        <button class="accordion-button collapsed"
                                type="button"
                                data-bs-toggle="collapse"
                                data-bs-target="#faq2">

                            Apa itu Priority Queue?
                        </button>
                    </h2>

                    <div id="faq2"
                         class="accordion-collapse collapse"
                         data-bs-parent="#faqAccordion">

                        <div class="accordion-body">
                            Priority Queue memungkinkan lagu diprioritaskan
                            untuk diputar lebih cepat dibanding antrian biasa.
                            Sistem ini tetap berjalan berdampingan dengan FIFO Queue.
                        </div>
                    </div>
                </div>

                <!-- FAQ 3 -->
                <div class="accordion-item">
                    <h2 class="accordion-header">
                        <button class="accordion-button collapsed"
                                type="button"
                                data-bs-toggle="collapse"
                                data-bs-target="#faq3">
                            Bagaimana cara request lagu?
                        </button>
                    </h2>

                    <div id="faq3"
                         class="accordion-collapse collapse"
                         data-bs-parent="#faqAccordion">
                        <div class="accordion-body">
                            Cukup scan QR yang tersedia di kafe,
                            cari lagu yang ingin didengarkan,
                            lalu kirim request dalam beberapa detik.
                        </div>
                    </div>
                </div>

                <!-- FAQ 4 -->
                <div class="accordion-item">
                    <h2 class="accordion-header">
                        <button class="accordion-button collapsed"
                                type="button"
                                data-bs-toggle="collapse"
                                data-bs-target="#faq4">
                            Apakah saya harus login terlebih dahulu?
                        </button>
                    </h2>

                    <div id="faq4"
                         class="accordion-collapse collapse"
                         data-bs-parent="#faqAccordion">

                        <div class="accordion-body">
                            Tidak. Pengunjung dapat melakukan request
                            sebagai tamu tanpa perlu membuat akun.
                        </div>
                    </div>
                </div>
                <!-- FAQ 5 -->
                <div class="accordion-item">
                    <h2 class="accordion-header">
                        <button class="accordion-button collapsed"
                                type="button"
                                data-bs-toggle="collapse"
                                data-bs-target="#faq5">

                            Bagaimana jika lagu yang saya cari tidak tersedia?
                        </button>
                    </h2>

                    <div id="faq5"
                         class="accordion-collapse collapse"
                         data-bs-parent="#faqAccordion">

                        <div class="accordion-body">
                            Ketersediaan lagu mengikuti katalog Spotify.
                            Jika lagu tersedia di Spotify, kemungkinan besar
                            lagu tersebut juga dapat direquest melalui sistem.
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>


<?= $this->endSection() ?>

<?= $this->section('js') ?>
<!--<script src="--><?php //= base_url('assets/js/landing.js') ?><!--"></script>-->
<?= $this->endSection() ?>