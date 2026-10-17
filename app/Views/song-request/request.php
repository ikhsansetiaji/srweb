<?= $this->extend('layout/app') ?>

<?= $this->section('css') ?>
<style>
    .request-hero-banner {
        background: #FFFFFF;
        border: 1px solid var(--border-color);
        border-radius: var(--radius-lg);
        padding: 24px 28px;
        margin-bottom: 24px;
        box-shadow: var(--shadow-sm);
    }

    .cafe-live-tag {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        background: rgba(16, 185, 129, 0.1);
        color: #059669;
        font-weight: 600;
        font-size: 0.75rem;
        padding: 4px 10px;
        border-radius: var(--radius-pill);
        border: 1px solid rgba(16, 185, 129, 0.2);
    }

    .cafe-live-tag .dot {
        width: 7px;
        height: 7px;
        background-color: #10B981;
        border-radius: 50%;
        animation: pulseLive 1.8s infinite;
    }

    @keyframes pulseLive {
        0%, 100% { transform: scale(1); opacity: 1; }
        50% { transform: scale(1.4); opacity: 0.5; }
    }

    .search-input-wrapper {
        position: relative;
        display: flex;
        align-items: center;
    }

    .search-input-wrapper .search-icon {
        position: absolute;
        left: 18px;
        color: var(--accent-coral);
        font-size: 1.15rem;
        pointer-events: none;
    }

    .search-input-wrapper .form-control {
        padding-left: 50px;
        padding-right: 45px;
        height: 56px;
        font-size: 1rem;
        border-radius: 14px;
        border: 1.5px solid rgba(224, 90, 71, 0.2);
        box-shadow: 0 4px 16px rgba(0, 0, 0, 0.02);
    }

    .search-input-wrapper .search-spinner {
        position: absolute;
        right: 18px;
        color: var(--accent-coral);
        display: none;
    }

    .song-result-item {
        background: #FFFFFF;
        border: 1px solid var(--border-subtle);
        border-radius: 12px;
        padding: 12px 16px;
        margin-bottom: 8px;
        display: flex;
        align-items: center;
        gap: 14px;
        cursor: pointer;
        transition: all 0.2s ease;
        text-align: left;
        width: 100%;
    }

    .song-result-item:hover {
        background: var(--bg-cream-light);
        border-color: var(--accent-coral);
        transform: translateY(-2px);
        box-shadow: 0 6px 18px rgba(224, 90, 71, 0.1);
    }

    .song-result-thumb {
        width: 52px;
        height: 52px;
        border-radius: 8px;
        object-fit: cover;
        flex-shrink: 0;
        background: #eee;
    }

    .selected-song-card {
        background: linear-gradient(135deg, rgba(224, 90, 71, 0.04) 0%, rgba(244, 239, 234, 0.6) 100%);
        border: 2px solid var(--accent-coral);
        border-radius: 16px;
        padding: 20px;
        box-shadow: 0 6px 20px rgba(224, 90, 71, 0.08);
    }

    /* Queue Type Selection Cards */
    .queue-card-option {
        border: 2px solid var(--border-subtle);
        border-radius: 14px;
        padding: 18px;
        cursor: pointer;
        transition: all 0.25s ease;
        background: #FFFFFF;
        height: 100%;
        display: flex;
        flex-direction: column;
        justify-content: space-between;
    }

    .queue-card-option:hover {
        border-color: rgba(224, 90, 71, 0.4);
        background: var(--bg-cream-light);
    }

    .queue-radio-input:checked + .queue-card-option {
        border-color: var(--accent-coral);
        background: var(--bg-cream-light);
        box-shadow: 0 6px 20px rgba(224, 90, 71, 0.12);
    }

    .nominal-btn-pill {
        border: 1.5px solid rgba(224, 90, 71, 0.25);
        background: #FFFFFF;
        color: var(--text-dark);
        font-weight: 600;
        font-size: 0.88rem;
        padding: 9px 18px;
        border-radius: var(--radius-pill);
        transition: all 0.2s ease;
    }

    .nominal-btn-pill:hover, .nominal-btn-pill.active {
        background: var(--accent-coral);
        border-color: var(--accent-coral);
        color: #FFFFFF;
        transform: translateY(-2px);
        box-shadow: 0 4px 12px var(--accent-coral-shadow);
    }

    .empty-state-box {
        text-align: center;
        padding: 36px 20px;
        color: var(--text-muted);
    }

    .empty-state-icon {
        font-size: 2.5rem;
        color: var(--accent-coral);
        opacity: 0.5;
        margin-bottom: 12px;
    }
</style>
<?= $this->endSection() ?>

<?= $this->section('content') ?>

<div class="container-lg py-4 py-md-5">
    <div class="row justify-content-center">
        <div class="col-lg-8">

            <!-- Back navigation & Cafe Banner -->
            <div class="request-hero-banner">
                <div class="d-flex flex-wrap justify-content-between align-items-center gap-3">
                    <div>
                        <a href="/song-request/request" class="text-muted small fw-semibold text-decoration-none d-inline-flex align-items-center mb-2">
                            <i class="fas fa-arrow-left me-1"></i> Ganti Kafe
                        </a>
                        <h3 class="fw-bold mb-1">
                            <i class="fas fa-store text-coral me-2"></i><?= esc($cafe['nama_kafe']) ?>
                        </h3>
                        <p class="text-muted mb-0 small">
                            <i class="fas fa-map-marker-alt me-1 text-coral"></i> <?= esc($cafe['alamat'] ?? 'Yogyakarta') ?>
                        </p>
                    </div>
                    <div class="text-md-end d-flex flex-column align-items-start align-items-md-end gap-2">
                        <span class="cafe-live-tag">
                            <span class="dot"></span> Live Queue
                        </span>
                        <span class="badge bg-light text-dark border px-3 py-2" style="font-size: 0.8rem;">
                            <i class="fas fa-hourglass-half text-warning me-1"></i> Antrean: <strong><?= esc($queue_stats['waiting_requests'] ?? 0) ?> lagu</strong>
                        </span>
                    </div>
                </div>

                <?php if (!empty($currently_playing)): ?>
                    <div class="mt-3 pt-3 border-top d-flex align-items-center gap-3">
                        <span class="badge badge-coral px-3 py-2 flex-shrink-0">
                            <i class="fas fa-play me-1"></i> Sedang Diputar
                        </span>
                        <div class="text-truncate">
                            <strong class="text-dark small"><?= esc($currently_playing['title']) ?></strong>
                            <span class="text-muted small">&bull; <?= esc($currently_playing['artist']) ?></span>
                        </div>
                    </div>
                <?php endif; ?>
            </div>

            <!-- STEP 1: Search Song Card -->
            <div class="card shadow-sm border-0 mb-4" id="searchCard">
                <div class="card-body p-4">
                    <div class="d-flex align-items-center justify-content-between mb-3">
                        <div>
                            <span class="badge badge-coral mb-1">Langkah 1</span>
                            <h5 class="fw-bold mb-0">Cari & Pilih Lagu</h5>
                        </div>
                        <small class="text-muted"><i class="fab fa-spotify text-success me-1"></i>Katalog Spotify</small>
                    </div>

                    <div class="search-input-wrapper mb-3">
                        <i class="fas fa-search search-icon"></i>
                        <input type="text" class="form-control" id="songSearch" placeholder="Ketik judul lagu atau nama penyanyi..." autocomplete="off">
                        <div class="spinner-border spinner-border-sm search-spinner" id="searchSpinner" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                    </div>

                    <!-- Song Results List -->
                    <div id="songList" style="max-height: 420px; overflow-y: auto;">
                        <div class="empty-state-box" id="initialSearchHint">
                            <div class="empty-state-icon"><i class="fas fa-compact-disc"></i></div>
                            <h6 class="fw-bold text-dark mb-1">Cari lagu favoritmu</h6>
                            <small class="text-muted">Ketik minimal 2 huruf untuk menemukan lagu favorit dari Spotify</small>
                        </div>
                    </div>
                </div>
            </div>

            <!-- STEP 2: Request Detail Form -->
            <div class="card shadow-sm border-0">
                <div class="card-body p-4">
                    <div class="d-flex align-items-center justify-content-between mb-3">
                        <div>
                            <span class="badge badge-coral mb-1">Langkah 2</span>
                            <h5 class="fw-bold mb-0">Detail Permintaan Lagu</h5>
                        </div>
                    </div>

                    <form id="requestForm">
                        <!-- Selected Song Banner -->
                        <div id="selectedSongInfo" style="display: none;" class="mb-4">
                            <div class="selected-song-card position-relative">
                                <div class="d-flex align-items-center justify-content-between mb-2">
                                    <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25 px-2 py-1" style="font-size:0.75rem;">
                                        <i class="fas fa-check-circle me-1"></i> Lagu Terpilih
                                    </span>
                                    <button type="button" class="btn btn-sm btn-outline-secondary rounded-pill py-1 px-3" id="cancelSelectionBtn" style="font-size:0.78rem;">
                                        <i class="fas fa-times me-1"></i> Ganti Lagu
                                    </button>
                                </div>
                                <div class="d-flex align-items-center gap-3">
                                    <img id="songThumbnail" src="" alt="Thumbnail" class="rounded shadow-sm flex-shrink-0" width="68" height="68" style="object-fit: cover;">
                                    <div class="flex-grow-1 min-width-0">
                                        <h5 id="songTitle" class="fw-bold mb-1 text-truncate"></h5>
                                        <p id="songArtist" class="text-muted mb-0 small text-truncate"></p>
                                        <div class="mt-1 small text-muted">
                                            <i class="fas fa-clock me-1 text-coral"></i> <span id="songDuration">-</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Hidden IDs -->
                        <input type="hidden" id="songId" name="song_id">
                        <input type="hidden" id="cafeId" name="cafe_id" value="<?= $cafe['id'] ?>">

                        <!-- Guest Name -->
                        <div class="mb-4">
                            <label for="guestName" class="form-label">Nama Anda (Opsional)</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-user text-coral"></i></span>
                                <input type="text" class="form-control" id="guestName" name="guest_name" value="<?= !empty($current_user['name']) ? esc($current_user['name']) : '' ?>" placeholder="Kosongkan jika ingin tampil sebagai 'Anonim'">
                            </div>
                            <div class="form-text small text-muted mt-1">Nama ini akan ditampilkan di layar DJ dan monitor antrean saat lagu diputar.</div>
                        </div>

                        <!-- WhatsApp Notification Section (User vs Guest) -->
                        <?php if (!empty($current_user)): ?>
                            <input type="hidden" name="user_id" value="<?= esc($current_user['id']) ?>">
                            <div class="mb-4 p-3 rounded-3 bg-light border">
                                <div class="d-flex align-items-center justify-content-between mb-2">
                                    <span class="small fw-bold text-dark">
                                        <i class="fab fa-whatsapp text-success me-1"></i> Notifikasi WhatsApp
                                    </span>
                                    <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25 px-2 py-1 small">
                                        <i class="fas fa-check-circle me-1"></i> Akun Login
                                    </span>
                                </div>
                                <p class="small text-muted mb-2">
                                    Pemberitahuan antrean lagu & pembayaran akan otomatis dikirim ke: 
                                    <strong class="text-dark"><?= esc($current_user['phone_wa'] ?: ($current_user['phone'] ?: 'Nomor di profil Anda')) ?></strong>
                                </p>
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" id="wa_consent" name="wa_consent" value="1" <?= (!empty($current_user['wa_notif_enabled'])) ? 'checked' : '' ?>>
                                    <label class="form-check-label small text-muted" for="wa_consent">
                                        Kirim notifikasi untuk request lagu ini via WhatsApp
                                    </label>
                                </div>
                            </div>
                        <?php else: ?>
                            <!-- Pengunjung Belum Login (Guest) -->
                            <div class="mb-4 p-3 rounded-3 bg-light border">
                                <label for="phone_wa" class="form-label d-flex justify-content-between align-items-center mb-1">
                                    <span class="fw-semibold text-dark"><i class="fab fa-whatsapp text-success me-1"></i> Nomor WhatsApp (Opsional)</span>
                                    <span class="badge bg-light text-muted border small">Notifikasi Real-time</span>
                                </label>
                                <div class="input-group mb-2">
                                    <span class="input-group-text bg-white"><i class="fab fa-whatsapp text-success"></i></span>
                                    <input type="tel" class="form-control" id="phone_wa" name="phone_wa" placeholder="08xxxxxxxxxx">
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="wa_consent" name="wa_consent" value="1" checked>
                                    <label class="form-check-label small text-muted" for="wa_consent">
                                        Kirim notifikasi saat pembayaran berhasil, lagu masuk antrean, mulai diputar, dan selesai diputar.
                                    </label>
                                </div>
                            </div>
                        <?php endif; ?>

                        <!-- Queue Type Selection Cards -->
                        <div class="mb-4">
                            <label class="form-label d-block">Pilih Tipe Antrean</label>
                            <div class="row g-3">
                                <!-- Option 1: FIFO (Gratis) -->
                                <div class="col-sm-6">
                                    <input type="radio" class="d-none queue-radio-input" name="queue_type" id="queueFifo" value="fifo" checked>
                                    <label class="queue-card-option" for="queueFifo">
                                        <div>
                                            <div class="d-flex justify-content-between align-items-center mb-2">
                                                <div class="text-success fw-bold">
                                                    <i class="fas fa-list-ol me-1"></i> Reguler (FIFO)
                                                </div>
                                                <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25">GRATIS</span>
                                            </div>
                                            <p class="small text-muted mb-0">Lagu diputar berurutan berdasarkan urutan waktu request Anda.</p>
                                        </div>
                                        <div class="mt-3 pt-2 border-top small text-muted d-flex align-items-center justify-content-between">
                                            <span>Biaya: <strong>Rp 0,00</strong></span>
                                            <i class="fas fa-check-circle text-success check-icon"></i>
                                        </div>
                                    </label>
                                </div>

                                <!-- Option 2: Priority (Saweran) -->
                                <div class="col-sm-6">
                                    <input type="radio" class="d-none queue-radio-input" name="queue_type" id="queuePriority" value="priority">
                                    <label class="queue-card-option" for="queuePriority">
                                        <div>
                                            <div class="d-flex justify-content-between align-items-center mb-2">
                                                <div class="text-coral fw-bold">
                                                    <i class="fas fa-star me-1"></i> Prioritas
                                                </div>
                                                <span class="badge badge-coral">SAWERAN</span>
                                            </div>
                                            <p class="small text-muted mb-0">Lagu diputar lebih cepat di depan antrean reguler berdasarkan nominal saweran.</p>
                                        </div>
                                        <div class="mt-3 pt-2 border-top small text-muted d-flex align-items-center justify-content-between">
                                            <span>Mulai: <strong>Rp 5.000,00</strong></span>
                                            <i class="fas fa-check-circle text-coral check-icon"></i>
                                        </div>
                                    </label>
                                </div>
                            </div>
                        </div>

                        <!-- Nominal Section (Only shown when Priority queue is selected) -->
                        <div class="mb-4" id="nominalSection" style="display: none;">
                            <div class="p-3 bg-light rounded-3 border">
                                <label for="nominal" class="form-label d-flex justify-content-between">
                                    <span>Nominal Saweran</span>
                                    <span class="text-muted small">Min. Rp 5.000,00</span>
                                </label>
                                <div class="input-group input-group-lg mb-3">
                                    <span class="input-group-text fw-bold text-coral">Rp</span>
                                    <input type="number" class="form-control fw-bold" id="nominal" name="nominal" min="5000" step="1000" value="5000">
                                </div>

                                <div class="mb-2">
                                    <label class="form-label small text-muted mb-2">Pilihan Cepat:</label>
                                    <div class="d-flex flex-wrap gap-2">
                                        <button type="button" class="btn nominal-btn-pill active" data-amount="5000">Rp 5.000,00</button>
                                        <button type="button" class="btn nominal-btn-pill" data-amount="10000">Rp 10.000,00</button>
                                        <button type="button" class="btn nominal-btn-pill" data-amount="25000">Rp 25.000,00</button>
                                        <button type="button" class="btn nominal-btn-pill" data-amount="50000">Rp 50.000,00</button>
                                    </div>
                                </div>
                                <small class="text-muted d-block mt-2">
                                    <i class="fas fa-shield-alt text-success me-1"></i> Pembayaran instan & terverifikasi otomatis via QRIS / E-Wallet Midtrans.
                                </small>
                            </div>
                        </div>

                        <!-- Submit Button -->
                        <button type="submit" class="btn btn-coral btn-lg w-100 py-3 mt-2" id="submitBtn" disabled>
                            <i class="fas fa-paper-plane me-2"></i> Kirim Request Lagu
                        </button>
                        <div class="text-center mt-3">
                            <small class="text-muted">Dengan mengirim request, Anda menyetujui aturan pemutaran lagu di kafe ini.</small>
                        </div>
                    </form>
                </div>
            </div>

        </div>
    </div>
</div>

<script>
let selectedSong = null;
let searchTimeout;

// Song Search
const searchInput = document.getElementById('songSearch');
const searchSpinner = document.getElementById('searchSpinner');
const songList = document.getElementById('songList');

searchInput.addEventListener('input', function() {
    clearTimeout(searchTimeout);
    const query = this.value.trim();

    if (query.length < 2) {
        searchSpinner.style.display = 'none';
        songList.innerHTML = `
            <div class="empty-state-box">
                <div class="empty-state-icon"><i class="fas fa-compact-disc"></i></div>
                <h6 class="fw-bold text-dark mb-1">Cari lagu favoritmu</h6>
                <small class="text-muted">Ketik minimal 2 huruf untuk menemukan lagu favorit dari Spotify</small>
            </div>
        `;
        return;
    }

    searchSpinner.style.display = 'block';

    searchTimeout = setTimeout(async () => {
        try {
            const response = await fetch(`/song-request/search?q=${encodeURIComponent(query)}`);
            const data = await response.json();

            searchSpinner.style.display = 'none';

            if (data.success) {
                renderSongs(data.data);
            } else {
                renderEmptyState(data.message || 'Lagu tidak ditemukan.');
            }
        } catch (error) {
            searchSpinner.style.display = 'none';
            console.error('Search failed:', error);
            renderEmptyState('Gagal menghubungi server Spotify.');
        }
    }, 400);
});

function formatDuration(s) {
    if (!s) return '0:00';
    return `${Math.floor(s / 60)}:${String(s % 60).padStart(2, '0')}`;
}

function renderSongs(songs) {
    if (!songs || songs.length === 0) {
        renderEmptyState('Lagu tidak ditemukan. Coba gunakan kata kunci atau nama artis yang berbeda.');
        return;
    }

    songList.innerHTML = songs.map(song => {
        const songId = song.local_id || song.id;
        const thumb = song.thumbnail || '/assets/images/default-album.png';
        const songData = JSON.stringify(Object.assign({}, song, { id: songId })).replace(/'/g, '&#39;');

        return `
            <div class="song-result-item" data-song-id="${songId}" data-song='${songData}'>
                <img src="${thumb}" class="song-result-thumb" alt="">
                <div class="flex-grow-1 min-width-0">
                    <h6 class="mb-0 fw-bold text-truncate text-dark">${escapeHtml(song.title)}</h6>
                    <small class="text-muted text-truncate d-block">${escapeHtml(song.artist)}</small>
                </div>
                <div class="text-end flex-shrink-0 d-flex align-items-center gap-2">
                    <span class="badge bg-light text-muted border px-2 py-1 d-none d-sm-inline">${formatDuration(song.duration)}</span>
                    <button type="button" class="btn btn-sm btn-coral px-3 py-1">Pilih</button>
                </div>
            </div>
        `;
    }).join('');

    // Attach click events
    document.querySelectorAll('.song-result-item').forEach(item => {
        item.addEventListener('click', function() {
            const song = JSON.parse(this.dataset.song);
            selectSong(song);
        });
    });
}

function renderEmptyState(message) {
    songList.innerHTML = `
        <div class="empty-state-box">
            <div class="empty-state-icon"><i class="fas fa-search"></i></div>
            <h6 class="fw-bold text-dark mb-1">Hasil Tidak Ditemukan</h6>
            <small class="text-muted">${escapeHtml(message)}</small>
        </div>
    `;
}

function selectSong(song) {
    selectedSong = song;
    document.getElementById('songId').value = song.id;
    document.getElementById('songTitle').textContent = song.title;
    document.getElementById('songArtist').textContent = song.artist;
    document.getElementById('songDuration').textContent = formatDuration(song.duration);
    document.getElementById('songThumbnail').src = song.thumbnail || '/assets/images/default-album.png';
    
    document.getElementById('selectedSongInfo').style.display = 'block';
    searchInput.value = '';
    songList.innerHTML = '';
    submitBtn.disabled = false;

    // Scroll smoothly to form detail
    document.getElementById('selectedSongInfo').scrollIntoView({ behavior: 'smooth', block: 'center' });
}

// Cancel Selection Button
document.getElementById('cancelSelectionBtn').addEventListener('click', function() {
    selectedSong = null;
    document.getElementById('songId').value = '';
    document.getElementById('selectedSongInfo').style.display = 'none';
    submitBtn.disabled = true;
    searchInput.value = '';
    searchInput.focus();
    renderEmptyState('Pencarian dibatalkan. Silakan ketik nama lagu yang diinginkan.');
});

// Nominal Presets
document.querySelectorAll('.nominal-btn-pill').forEach(btn => {
    btn.addEventListener('click', function() {
        document.querySelectorAll('.nominal-btn-pill').forEach(b => b.classList.remove('active'));
        this.classList.add('active');
        document.getElementById('nominal').value = this.dataset.amount;
    });
});

document.getElementById('nominal').addEventListener('input', function() {
    const val = this.value;
    document.querySelectorAll('.nominal-btn-pill').forEach(b => {
        b.classList.toggle('active', b.dataset.amount === val);
    });
});

// Queue Type Toggle
const queueFifo = document.getElementById('queueFifo');
const queuePriority = document.getElementById('queuePriority');
const nominalSection = document.getElementById('nominalSection');
const nominalInput = document.getElementById('nominal');
const submitBtn = document.getElementById('submitBtn');

function handleQueueTypeChange() {
    if (queueFifo.checked) {
        nominalSection.style.display = 'none';
        nominalInput.disabled = true;
        nominalInput.removeAttribute('required');
        nominalInput.removeAttribute('min');
        nominalInput.value = 0;
        submitBtn.innerHTML = '<i class="fas fa-paper-plane me-2"></i> Kirim Request Lagu (Gratis)';
    } else {
        nominalSection.style.display = 'block';
        nominalInput.disabled = false;
        nominalInput.setAttribute('required', 'required');
        nominalInput.setAttribute('min', '5000');
        if (parseInt(nominalInput.value) <= 0) {
            nominalInput.value = 5000;
        }
        submitBtn.innerHTML = '<i class="fas fa-credit-card me-2"></i> Lanjut ke Pembayaran';
    }
}

queueFifo.addEventListener('change', handleQueueTypeChange);
queuePriority.addEventListener('change', handleQueueTypeChange);
handleQueueTypeChange();

// Flash Toast on Success Redirect
window.addEventListener('DOMContentLoaded', () => {
    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.get('success') === '1') {
        showAlert('Berhasil! Lagu Anda telah masuk ke dalam antrean Reguler (FIFO).', 'success');
        const cleanUrl = window.location.protocol + "//" + window.location.host + window.location.pathname + "?cafe_id=" + urlParams.get('cafe_id');
        window.history.replaceState({path: cleanUrl}, '', cleanUrl);
    }
});

// Request Form Submit Handler
document.getElementById('requestForm').addEventListener('submit', async function(e) {
    e.preventDefault();

    if (!selectedSong) {
        showAlert('Silakan cari dan pilih lagu terlebih dahulu di Langkah 1.', 'warning');
        searchInput.focus();
        return;
    }

    const formData = new FormData(this);
    const data = Object.fromEntries(formData);

    if (!data.nominal && data.nominal !== 0) {
        data.nominal = 0;
    }

    const waConsentInput = document.getElementById('wa_consent');
    if (waConsentInput) {
        data.wa_consent = waConsentInput.checked;
    }

    submitBtn.disabled = true;
    const originalBtnText = submitBtn.innerHTML;
    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i> Memproses Request...';

    try {
        const response = await fetch('/song-request/create', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify(data)
        });

        const result = await response.json();

        if (result.success) {
            window.location.href = result.redirect;
        } else {
            showAlert(result.message || 'Terjadi kesalahan saat memproses permintaan', 'danger');
            submitBtn.disabled = false;
            submitBtn.innerHTML = originalBtnText;
        }
    } catch (error) {
        console.error('Request error:', error);
        showAlert('Gagal menghubungi server: ' + error.message, 'danger');
        submitBtn.disabled = false;
        submitBtn.innerHTML = originalBtnText;
    }
});

function escapeHtml(s) {
    if (!s) return '';
    return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;').replace(/'/g,'&#39;');
}
</script>

<?= $this->endSection() ?>