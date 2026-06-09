<?= $this->extend('layout/app') ?>

<?= $this->section('content') ?>

<div class="container-lg py-5">
    <div class="row">
        <div class="col-lg-8 mx-auto">
            <h2 class="fw-bold mb-4">
                <i class="fas fa-music text-danger"></i> Request Lagu - <?= $cafe['nama_kafe'] ?>
            </h2>

            <div class="card shadow-sm border-0 mb-4">
                <div class="card-body p-4">
                    <h5 class="fw-bold mb-3">Cari Lagu</h5>

                    <div class="input-group input-group-lg mb-3">
                        <span class="input-group-text bg-light border-0"><i class="fas fa-search"></i></span>
                        <input type="text" class="form-control border-0" id="songSearch" placeholder="Cari lagu, artis...">
                    </div>

                    <div id="songList" class="list-group" style="max-height: 400px; overflow-y: auto;">
                        <!-- Songs will be loaded here -->
                    </div>
                </div>
            </div>

            <div class="card shadow-sm border-0">
                <div class="card-body p-4">
                    <h5 class="fw-bold mb-3">Detail Request</h5>

                    <form id="requestForm">
                        <!-- Selected song info -->
                        <div id="selectedSongInfo" style="display: none;" class="mb-4 p-3 bg-light rounded position-relative">
                            <button type="button" class="btn-close position-absolute top-0 end-0 m-2" id="cancelSelectionBtn" aria-label="Batal Pilih Lagu"></button>
                            <div class="row align-items-center">
                                <div class="col-md-2 col-3 text-center">
                                    <img id="songThumbnail" src="" alt="Thumbnail" class="img-fluid rounded shadow-sm" style="max-height: 80px; object-fit: cover;">
                                </div>
                                <div class="col-md-10 col-9">
                                    <h6 id="songTitle" class="fw-bold mb-1"></h6>
                                    <p id="songArtist" class="text-muted mb-0"></p>
                                </div>
                            </div>
                        </div>

                        <input type="hidden" id="songId" name="song_id">
                        <input type="hidden" id="cafeId" name="cafe_id" value="<?= $cafe['id'] ?>">

                        <div class="mb-3">
                            <label for="guestName" class="form-label fw-600">Nama Anda (Opsional)</label>
                            <input type="text" class="form-control form-control-lg" id="guestName" name="guest_name" placeholder="Kosongkan untuk 'Anonim'">
                            <small class="text-muted">Nama ini akan ditampilkan saat lagu Anda diputar</small>
                        </div>

                        <div class="mb-4">
                            <label class="form-label fw-600">Tipe Antrean</label>
                            <div class="btn-group w-100 mb-2" role="group" aria-label="Queue Type Selection">
                                <input type="radio" class="btn-check" name="queue_type" id="queueFifo" value="fifo" autocomplete="off" checked>
                                <label class="btn btn-outline-danger py-3" for="queueFifo">
                                    <i class="fas fa-list-ol me-2"></i>Regular (FIFO Queue)
                                </label>

                                <input type="radio" class="btn-check" name="queue_type" id="queuePriority" value="priority" autocomplete="off">
                                <label class="btn btn-outline-danger py-3" for="queuePriority">
                                    <i class="fas fa-star me-2"></i>Prioritas (Priority Queue)
                                </label>
                            </div>
                            <div id="fifoDesc" class="text-muted small">
                                <i class="fas fa-info-circle me-1"></i> Lagu diputar berurutan berdasarkan waktu request. (Gratis)
                            </div>
                            <div id="priorityDesc" class="text-muted small" style="display: none;">
                                <i class="fas fa-info-circle me-1"></i> Lagu dengan nominal saweran terbesar diputar lebih cepat. Jika nominal sama, yang request lebih awal didahulukan.
                            </div>
                        </div>

                        <!-- Nominal Section (Only shown when Priority queue is selected) -->
                        <div class="mb-4" id="nominalSection" style="display: none;">
                            <label for="nominal" class="form-label fw-600">Saweran (Rp)</label>
                            <div class="input-group input-group-lg mb-2">
                                <span class="input-group-text">Rp</span>
                                <input type="number" class="form-control" id="nominal" name="nominal" min="5000" step="1000" value="5000">
                            </div>
                            <div class="row g-2">
                                <div class="col-auto">
                                    <button type="button" class="btn btn-outline-danger btn-sm nominal-btn" data-amount="5000">Rp 5.000</button>
                                </div>
                                <div class="col-auto">
                                    <button type="button" class="btn btn-outline-danger btn-sm nominal-btn" data-amount="10000">Rp 10.000</button>
                                </div>
                                <div class="col-auto">
                                    <button type="button" class="btn btn-outline-danger btn-sm nominal-btn" data-amount="25000">Rp 25.000</button>
                                </div>
                                <div class="col-auto">
                                    <button type="button" class="btn btn-outline-danger btn-sm nominal-btn" data-amount="50000">Rp 50.000</button>
                                </div>
                            </div>
                        </div>

                        <button type="submit" class="btn btn-danger btn-lg w-100" id="submitBtn" disabled>
                            <i class="fas fa-paper-plane"></i> Kirim Request
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
let selectedSong = null;

// Search songs
let searchTimeout;
document.getElementById('songSearch').addEventListener('input', function() {
    clearTimeout(searchTimeout);
    const query = this.value.trim();

    if (query.length < 2) {
        document.getElementById('songList').innerHTML = '';
        return;
    }

    searchTimeout = setTimeout(async () => {
        try {
            const response = await fetch(`/song-request/search?q=${encodeURIComponent(query)}`);
            const data = await response.json();

            if (data.success) {
                renderSongs(data.data);
            }
        } catch (error) {
            console.error('Search failed:', error);
        }
    }, 500);
});

function renderSongs(songs) {
    const container = document.getElementById('songList');
    container.innerHTML = '';

    if (songs.length === 0) {
        container.innerHTML = '<div class="list-group-item">Lagu tidak ditemukan</div>';
        return;
    }

    songs.forEach(song => {
        // Support both Spotify results (local_id) and local DB results (id)
        const songId = song.local_id || song.id;
        const thumb = song.thumbnail ? `<img src="${song.thumbnail}" class="rounded me-3" width="45" height="45" style="object-fit:cover">` : '';
        const songData = JSON.stringify(Object.assign({}, song, {id: songId})).replace(/'/g, '&#39;');
        const html = `
            <button type="button" class="list-group-item list-group-item-action" data-song-id="${songId}" data-song='${songData}'>
                <div class="d-flex align-items-center">
                    ${thumb}
                    <div class="flex-grow-1">
                        <h6 class="mb-0 fw-bold">${song.title}</h6>
                        <small class="text-muted">${song.artist}</small>
                    </div>
                    <div class="text-muted">
                        <small>${Math.floor(song.duration / 60)}:${String(song.duration % 60).padStart(2, '0')}</small>
                    </div>
                </div>
            </button>
        `;
        container.innerHTML += html;
    });

    // Add click handlers
    document.querySelectorAll('[data-song-id]').forEach(btn => {
        btn.addEventListener('click', function() {
            const song = JSON.parse(this.dataset.song);
            selectSong(song);
        });
    });
}

function selectSong(song) {
    selectedSong = song;
    document.getElementById('songId').value = song.id;
    document.getElementById('songTitle').textContent = song.title;
    document.getElementById('songArtist').textContent = song.artist;
    document.getElementById('songThumbnail').src = song.thumbnail || '/assets/images/default-album.png';
    document.getElementById('selectedSongInfo').style.display = 'block';
    document.getElementById('songSearch').value = '';
    document.getElementById('songList').innerHTML = '';
    document.getElementById('submitBtn').disabled = false;

    // Scroll to form
    document.querySelector('.card:last-of-type').scrollIntoView({ behavior: 'smooth' });
}

// Cancel Selection Button
document.getElementById('cancelSelectionBtn').addEventListener('click', function() {
    selectedSong = null;
    document.getElementById('songId').value = '';
    document.getElementById('selectedSongInfo').style.display = 'none';
    document.getElementById('submitBtn').disabled = true;
    document.getElementById('songSearch').value = '';
    document.getElementById('songSearch').focus();
});

// Nominal buttons
document.querySelectorAll('.nominal-btn').forEach(btn => {
    btn.addEventListener('click', function() {
        document.getElementById('nominal').value = this.dataset.amount;
    });
});

// Toggle queue descriptions and nominal section
const queueFifo = document.getElementById('queueFifo');
const queuePriority = document.getElementById('queuePriority');
const nominalSection = document.getElementById('nominalSection');
const nominalInput = document.getElementById('nominal');
const submitBtn = document.getElementById('submitBtn');

function handleQueueTypeChange() {
    if (queueFifo.checked) {
        document.getElementById('fifoDesc').style.display = 'block';
        document.getElementById('priorityDesc').style.display = 'none';
        nominalSection.style.display = 'none';
        // Disable entirely so browser skips validation on hidden field
        nominalInput.disabled = true;
        nominalInput.removeAttribute('required');
        nominalInput.removeAttribute('min');
        nominalInput.value = 0;
        submitBtn.innerHTML = '<i class="fas fa-paper-plane me-2"></i>Kirim Request Lagu (Gratis)';
    } else {
        document.getElementById('fifoDesc').style.display = 'none';
        document.getElementById('priorityDesc').style.display = 'block';
        nominalSection.style.display = 'block';
        nominalInput.disabled = false;
        nominalInput.setAttribute('required', 'required');
        nominalInput.setAttribute('min', '5000');
        if (parseInt(nominalInput.value) <= 0) {
            nominalInput.value = 5000;
        }
        submitBtn.innerHTML = '<i class="fas fa-credit-card me-2"></i>Lanjut ke Pembayaran';
    }
}

queueFifo.addEventListener('change', handleQueueTypeChange);
queuePriority.addEventListener('change', handleQueueTypeChange);

// Run on page load initialization
handleQueueTypeChange();

// Check for success URL query parameter on page load
window.addEventListener('DOMContentLoaded', () => {
    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.get('success') === '1') {
        showAlert('Berhasil! Lagu Anda telah dimasukkan ke dalam antrean Regular (FIFO).', 'success');
        const cleanUrl = window.location.protocol + "//" + window.location.host + window.location.pathname + "?cafe_id=" + urlParams.get('cafe_id');
        window.history.replaceState({path: cleanUrl}, '', cleanUrl);
    }
});

// Form submit
document.getElementById('requestForm').addEventListener('submit', async function(e) {
    e.preventDefault();

    if (!selectedSong) {
        showAlert('Pilih lagu terlebih dahulu', 'warning');
        return;
    }

    // Build data manually — disabled inputs are excluded from FormData
    const formData = new FormData(this);
    const data = Object.fromEntries(formData);

    // Ensure nominal is always present (disabled fields are excluded from FormData)
    if (!data.nominal && data.nominal !== 0) {
        data.nominal = 0;
    }

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
            showAlert(result.message || 'Terjadi kesalahan', 'danger');
        }
    } catch (error) {
        showAlert('Request failed: ' + error.message, 'danger');
    }
});
</script>

<?= $this->endSection() ?>