<?= $this->extend('layout/app') ?>

<?= $this->section('css') ?>
<style>
    .player-section {
        background: linear-gradient(135deg, #1a1a2e 0%, #16213e 50%, #0f3460 100%);
        border-radius: 16px;
        padding: 0;
        overflow: hidden;
        box-shadow: 0 8px 32px rgba(0,0,0,0.3);
    }
    .player-header {
        padding: 20px 24px;
        display: flex;
        justify-content: space-between;
        align-items: center;
        border-bottom: 1px solid rgba(255,255,255,0.08);
    }
    .player-header h5 {
        color: #fff;
        margin: 0;
        font-weight: 700;
    }
    .track-detail-card {
        background: rgba(255,255,255,0.04);
        border: 1px solid rgba(255,255,255,0.08);
        border-radius: 12px;
        padding: 20px;
        margin: 20px 24px 24px;
    }
    .track-detail-card .track-title { color: #fff; font-size: 1.25rem; font-weight: 700; margin-bottom: 2px; }
    .track-detail-card .track-artist { color: rgba(255,255,255,0.6); font-size: 0.95rem; }
    .track-detail-card .track-meta { color: rgba(255,255,255,0.45); font-size: 0.8rem; }
    .track-detail-card img { border-radius: 8px; box-shadow: 0 4px 16px rgba(0,0,0,0.4); }
    .badge-priority { background: linear-gradient(135deg, #e53935, #ff5252); color: #fff; }
    .badge-fifo { background: linear-gradient(135deg, #f9a825, #ffc107); color: #333; }
    .btn-action { border-radius: 8px; font-weight: 600; font-size: 0.82rem; padding: 8px 14px; }
    .btn-done { background: rgba(255,255,255,0.1); border: 1px solid rgba(255,255,255,0.2); color: #fff; }
    .btn-done:hover { background: rgba(255,82,82,0.3); border-color: #ff5252; color: #ff5252; }
    .btn-skip { background: rgba(255,255,255,0.08); border: 1px solid rgba(255,255,255,0.15); color: rgba(255,255,255,0.7); }
    .btn-skip:hover { background: rgba(255,255,255,0.15); color: #fff; }
    .btn-spotify-open { background: #1DB954; border: none; color: #fff; }
    .btn-spotify-open:hover { background: #1ed760; color: #fff; }
    .empty-player {
        display: flex; flex-direction: column; align-items: center; justify-content: center;
        min-height: 200px; color: rgba(255,255,255,0.4);
    }
    .empty-player .spotify-icon { font-size: 3rem; color: #1DB954; margin-bottom: 16px; }
    .empty-player p { margin: 0 0 16px; font-size: 1rem; }
    .btn-play-first {
        background: linear-gradient(135deg, #e53935, #ff5252);
        border: none; color: #fff; padding: 12px 32px; border-radius: 50px;
        font-weight: 700; font-size: 1rem; letter-spacing: 0.3px;
        box-shadow: 0 4px 20px rgba(229,57,53,0.4);
        transition: transform 0.15s, box-shadow 0.15s;
    }
    .btn-play-first:hover { transform: translateY(-2px); box-shadow: 0 6px 28px rgba(229,57,53,0.5); color: #fff; }
    .autoplay-timer {
        display: flex; align-items: center; gap: 8px;
        background: rgba(255,255,255,0.06); border-radius: 8px; padding: 6px 12px;
        font-size: 0.78rem; color: rgba(255,255,255,0.5);
    }
    .autoplay-timer .timer-bar {
        width: 60px; height: 4px; background: rgba(255,255,255,0.1); border-radius: 2px; overflow: hidden;
    }
    .autoplay-timer .timer-bar-fill {
        height: 100%; background: #1DB954; border-radius: 2px; transition: width 1s linear;
    }
    .queue-card { border: none; border-radius: 12px; box-shadow: 0 2px 12px rgba(0,0,0,0.06); }
    .queue-card .card-header { background: #fff; border-bottom: 1px solid #f0f0f0; border-radius: 12px 12px 0 0 !important; padding: 16px 20px; }
    .queue-item { border-radius: 8px !important; margin-bottom: 6px; border: 1px solid #f0f0f0 !important; transition: background 0.15s; }
    .queue-item:hover { background: #fafafa; }
    .stat-card { border: none; border-radius: 12px; box-shadow: 0 2px 12px rgba(0,0,0,0.06); transition: transform 0.15s; }
    .stat-card:hover { transform: translateY(-2px); }
</style>
<?= $this->endSection() ?>

<?= $this->section('content') ?>



<div class="container-lg py-4">
    <!-- Compact Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="fw-bold mb-0">
                <i class="fas fa-store text-danger"></i> <?= esc($cafe['nama_kafe']) ?>
            </h4>
            <small class="text-muted">Dashboard • Kelola antrean dan putar lagu</small>
        </div>
        <div class="d-flex align-items-center gap-2">
            <span class="badge bg-light text-dark border px-3 py-2">
                <i class="fas fa-hourglass-half text-warning me-1"></i> Antrian: <strong id="statTotalWaiting"><?= esc($queue_stats['waiting_requests'] ?? 0) ?></strong>
            </span>
        </div>
    </div>

    <!-- ==================== -->
    <!-- MAIN PLAYER SECTION  -->
    <!-- ==================== -->
    <div class="player-section mb-4">
        <div class="player-header">
            <div class="d-flex align-items-center gap-2">
                <h5><i class="fab fa-spotify text-success me-2"></i>Spotify Player</h5>
                <?php if (session()->has('spotify_access_token')): ?>
                    <span class="badge bg-success bg-opacity-25 text-success border border-success border-opacity-50 px-2 py-1" style="font-size: 0.7rem;">
                        <i class="fas fa-link me-1"></i> Connected
                    </span>
                    <a href="/spotify/disconnect" class="text-white-50 hover-white small text-decoration-none ms-1" title="Putuskan Hubungan Spotify">
                        <i class="fas fa-unlink"></i>
                    </a>
                <?php else: ?>
                    <a href="/spotify/connect" class="btn btn-sm btn-success px-3" style="font-size: 0.72rem; font-weight: 600; border-radius: 20px;">
                        <i class="fab fa-spotify me-1"></i> Hubungkan Spotify
                    </a>
                <?php endif; ?>
            </div>
            <div class="d-flex align-items-center gap-3">
                <!-- Auto-play duration timer -->
                <div id="autoplayTimerDisplay" class="autoplay-timer" style="display:none;">
                    <i class="fas fa-clock"></i>
                    <span id="autoplayTimeLeft">0:00</span>
                    <div class="timer-bar"><div class="timer-bar-fill" id="autoplayTimerBar" style="width:100%"></div></div>
                    <button class="btn btn-link text-white-50 p-0 ms-2 hover-white" onclick="resetDurationTimer(event)" title="Reset Timer / Ulangi Lagu">
                        <i class="fas fa-redo-alt" style="font-size: 0.72rem;"></i>
                    </button>
                </div>
                <div class="form-check form-switch mb-0">
                    <input class="form-check-input" type="checkbox" role="switch" id="autoPlaySwitch" checked>
                    <label class="form-check-label small fw-bold" style="color: rgba(255,255,255,0.7);" for="autoPlaySwitch">
                        <i class="fas fa-magic text-danger me-1"></i> Auto Play
                    </label>
                </div>
            </div>
        </div>

        <!-- Spotify Embed -->
        <div id="spotifyEmbedWrapper">
            <div id="spotifyPlaceholder" class="empty-player">
                <i class="fab fa-spotify spotify-icon"></i>
                <p>Belum ada lagu yang diputar</p>
                <button class="btn btn-play-first" onclick="playNext()">
                    <i class="fas fa-play me-2"></i> Putar Lagu Berikutnya
                </button>
            </div>
            <iframe id="spotifyEmbed" src="" width="100%" height="152" frameborder="0"
                allow="autoplay; clipboard-write; encrypted-media; fullscreen; picture-in-picture"
                loading="lazy" style="display:none;"></iframe>
        </div>

        <!-- Track Detail (inside player section) -->
        <div id="trackDetailSection" style="display:none;">
            <div class="track-detail-card" id="trackDetailCard">
                <!-- Filled by JS -->
            </div>
        </div>
    </div>

    <!-- ==================== -->
    <!-- QUEUE LISTS          -->
    <!-- ==================== -->
    <div class="row g-4 mb-4">
        <!-- Priority Queue -->
        <div class="col-md-6">
            <div class="card queue-card h-100">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <strong class="text-danger"><i class="fas fa-star me-1"></i> Antrian Prioritas</strong>
                    <span id="priorityQueueBadge" class="badge bg-danger rounded-pill">0</span>
                </div>
                <div class="card-body p-3" id="priorityQueueList" style="max-height: 380px; overflow-y: auto;">
                    <div class="text-center py-4 text-muted small">Antrean kosong</div>
                </div>
            </div>
        </div>
        <!-- FIFO Queue -->
        <div class="col-md-6">
            <div class="card queue-card h-100">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <strong class="text-warning"><i class="fas fa-list-ol me-1"></i> Antrian Regular (FIFO)</strong>
                    <span id="fifoQueueBadge" class="badge bg-warning text-dark rounded-pill">0</span>
                </div>
                <div class="card-body p-3" id="fifoQueueList" style="max-height: 380px; overflow-y: auto;">
                    <div class="text-center py-4 text-muted small">Antrean kosong</div>
                </div>
            </div>
        </div>
    </div>

    <!-- ==================== -->
    <!-- STATS (secondary)    -->
    <!-- ==================== -->
    <div class="row g-3">
        <div class="col-12">
            <small class="text-muted fw-bold text-uppercase" style="letter-spacing: 1px;"><i class="fas fa-chart-bar me-1"></i> Statistik</small>
        </div>
        <div class="col-md-4">
            <div class="card stat-card">
                <div class="card-body py-3 px-4">
                    <p class="text-muted mb-1 small"><i class="fas fa-music text-danger me-1"></i> Request Hari Ini</p>
                    <h4 class="fw-bold text-danger mb-0" id="statTodayRequests"><?= esc($today_requests) ?></h4>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card stat-card">
                <div class="card-body py-3 px-4">
                    <p class="text-muted mb-1 small"><i class="fas fa-money-bill-wave text-success me-1"></i> Pendapatan Hari Ini</p>
                    <h4 class="fw-bold text-success mb-0" id="statDailyIncome">Rp <?= number_format($daily_income, 0, ',', '.') ?></h4>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card stat-card">
                <div class="card-body py-3 px-4">
                    <p class="text-muted mb-1 small"><i class="fas fa-wallet text-info me-1"></i> Saldo Anda</p>
                    <h4 class="fw-bold text-info mb-0" id="statBalance">Rp <?= number_format($balance['available_balance'] ?? 0, 0, ',', '.') ?></h4>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
const cafeId = <?= esc($cafe['id']) ?>;
let currentTrack = null;
let autoPlayEnabled = true;
let pollingInterval = null;
let hasPlayedOnce = false;
let isPlayingNext = false;

// Autoplay duration timer
let durationTimer = null;
let durationStartTime = null;
let durationTotal = 0;

function resetDurationTimer(event) {
    if (event) event.stopPropagation();
    if (currentTrack && currentTrack.duration) {
        startDurationTimer(currentTrack.duration, currentTrack.duration);
        showAlert('Timer direset ke durasi penuh lagu', 'success');
    }
}

document.getElementById('autoPlaySwitch').addEventListener('change', function() {
    autoPlayEnabled = this.checked;
    if (!autoPlayEnabled) {
        stopDurationTimer();
    } else {
        // If toggled back on, recalculate and start timer if a track is currently playing
        if (currentTrack && currentTrack.duration) {
            fetchDashboardUpdates();
        }
    }
});

// =====================
// Spotify Embed
// =====================
function showSpotifyEmbed(trackId) {
    const iframe = document.getElementById('spotifyEmbed');
    const placeholder = document.getElementById('spotifyPlaceholder');
    iframe.src = `https://open.spotify.com/embed/track/${trackId}?utm_source=generator&theme=0&autoplay=1`;
    iframe.style.display = 'block';
    placeholder.style.display = 'none';
}

function hideSpotifyEmbed() {
    const iframe = document.getElementById('spotifyEmbed');
    const placeholder = document.getElementById('spotifyPlaceholder');
    iframe.src = '';
    iframe.style.display = 'none';
    placeholder.style.display = 'flex';
}

// =====================
// Duration Timer (auto-play trigger)
// =====================
function startDurationTimer(remainingSeconds, totalDuration) {
    stopDurationTimer();
    durationTotal = totalDuration;
    durationStartTime = Date.now() - (totalDuration - remainingSeconds) * 1000;

    const timerDisplay = document.getElementById('autoplayTimerDisplay');
    const timeLeftEl = document.getElementById('autoplayTimeLeft');
    const timerBar = document.getElementById('autoplayTimerBar');

    if (autoPlayEnabled) {
        timerDisplay.style.display = 'flex';
    }

    durationTimer = setInterval(() => {
        const elapsed = Math.floor((Date.now() - durationStartTime) / 1000);
        const remaining = Math.max(0, durationTotal - elapsed);
        const pct = Math.max(0, (remaining / durationTotal) * 100);

        timeLeftEl.textContent = formatDuration(remaining);
        timerBar.style.width = pct + '%';

        if (remaining <= 0) {
            stopDurationTimer();
            hasPlayedOnce = true;
            if (autoPlayEnabled) {
                const pC = parseInt(document.getElementById('priorityQueueBadge').textContent) || 0;
                const fC = parseInt(document.getElementById('fifoQueueBadge').textContent) || 0;
                if (pC > 0 || fC > 0) {
                    playNext();
                } else {
                    if (currentTrack) {
                        markDone(currentTrack.id);
                    }
                }
            }
        }
    }, 1000);
}

function stopDurationTimer() {
    clearInterval(durationTimer);
    durationTimer = null;
    document.getElementById('autoplayTimerDisplay').style.display = 'none';
}

// =====================
// Helpers
// =====================
function formatDuration(s) {
    return `${Math.floor(s/60)}:${String(s%60).padStart(2,'0')}`;
}
function formatCurrency(a) {
    return new Intl.NumberFormat('id-ID',{style:'currency',currency:'IDR',minimumFractionDigits:0}).format(a);
}

// =====================
// Dashboard Fetch
// =====================
async function fetchDashboardUpdates() {
    try {
        const response = await fetch(`/song-request/webhook/queue/${cafeId}`, {
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        });

        if (response.status === 401) {
            clearInterval(pollingInterval);
            showAlert('Sesi Anda telah berakhir. Silakan login kembali.', 'warning');
            setTimeout(() => window.location.href = '/auth/login', 2000);
            return;
        }

        const contentType = response.headers.get("content-type");
        if (!response.ok || !contentType || contentType.indexOf("application/json") === -1) {
            return;
        }

        const result = await response.json();
        if (!result.success) return;

        // Stats
        document.getElementById('statTodayRequests').textContent = result.stats.today_requests;
        document.getElementById('statDailyIncome').textContent = formatCurrency(result.stats.daily_income);
        document.getElementById('statBalance').textContent = formatCurrency(result.stats.available_balance);
        document.getElementById('statTotalWaiting').textContent = result.stats.total_waiting;

        renderQueueLists(result.queue);

        // Now Playing
        const newTrack = result.currently_playing;
        if (newTrack) {
            const trackChanged = !currentTrack || currentTrack.id !== newTrack.id;
            if (trackChanged) {
                currentTrack = newTrack;
                hasPlayedOnce = true;
                if (newTrack.api_song_id) showSpotifyEmbed(newTrack.api_song_id);
            }

            // Start or maintain duration timer for auto-play
            if (autoPlayEnabled && newTrack.duration) {
                if (trackChanged || !durationTimer) {
                    const serverTime = new Date(result.timestamp.replace(' ', 'T')).getTime();
                    const playedAt = new Date(newTrack.played_at.replace(' ', 'T')).getTime();
                    const elapsedSeconds = Math.max(0, Math.floor((serverTime - playedAt) / 1000));
                    const remainingSeconds = Math.max(0, newTrack.duration - elapsedSeconds);
                    startDurationTimer(remainingSeconds, newTrack.duration);
                }
            }
        } else {
            if (currentTrack) {
                currentTrack = null;
                hideSpotifyEmbed();
                stopDurationTimer();
            }
            // Auto play immediately if queue receives a track and player is idle
            if (autoPlayEnabled && !isPlayingNext) {
                const pC = parseInt(document.getElementById('priorityQueueBadge').textContent) || 0;
                const fC = parseInt(document.getElementById('fifoQueueBadge').textContent) || 0;
                if (pC > 0 || fC > 0) {
                    playNext();
                }
            }
        }
        updateTrackDetail();
    } catch (e) {
        console.error("Dashboard update failed:", e);
    }
}

// =====================
// Track Detail UI
// =====================
function updateTrackDetail() {
    const section = document.getElementById('trackDetailSection');
    const card = document.getElementById('trackDetailCard');

    if (!currentTrack) {
        section.style.display = 'none';
        // Show placeholder play button
        document.getElementById('spotifyPlaceholder').querySelector('.btn-play-first')?.removeAttribute('disabled');
        return;
    }

    section.style.display = 'block';
    const qBadge = currentTrack.queue_type === 'priority'
        ? '<span class="badge badge-priority px-3 py-2 me-2"><i class="fas fa-star me-1"></i>PRIORITAS</span>'
        : '<span class="badge badge-fifo px-3 py-2 me-2"><i class="fas fa-list-ol me-1"></i>FIFO</span>';

    card.innerHTML = `
        <div class="d-flex align-items-center gap-3 flex-wrap">
            <img src="${currentTrack.thumbnail || '/assets/images/default-album.png'}" alt="" width="64" height="64" style="object-fit:cover;">
            <div class="flex-grow-1" style="min-width: 180px;">
                <div class="d-flex align-items-center gap-2 mb-1 flex-wrap">
                    ${qBadge}
                    ${currentTrack.nominal > 0 ? `<span class="badge bg-success bg-opacity-75 px-2 py-1" style="font-size:0.75rem;">${formatCurrency(currentTrack.nominal)}</span>` : ''}
                </div>
                <div class="track-title">${currentTrack.title}</div>
                <div class="track-artist">${currentTrack.artist}</div>
                <div class="track-meta mt-1">
                    <i class="fas fa-user me-1"></i> ${currentTrack.guest_name || 'Anonim'}
                    &nbsp;•&nbsp; <i class="fas fa-clock me-1"></i> ${formatDuration(currentTrack.duration || 0)}
                </div>
            </div>
            <div class="d-flex gap-2 flex-wrap">
                <button class="btn btn-action btn-done" onclick="markDone(${currentTrack.id})">
                    <i class="fas fa-check me-1"></i> Selesai
                </button>
                <button class="btn btn-action btn-skip" onclick="playNext()">
                    <i class="fas fa-forward me-1"></i> Skip
                </button>
                ${currentTrack.spotify_url ? `
                <a href="${currentTrack.spotify_url}" target="spotify_web" class="btn btn-action btn-spotify-open">
                    <i class="fab fa-spotify me-1"></i> Buka Spotify
                </a>` : ''}
            </div>
        </div>
    `;
}

// =====================
// Queue Lists
// =====================
function renderQueueLists(queue) {
    const pList = document.getElementById('priorityQueueList');
    const fList = document.getElementById('fifoQueueList');

    const pItems = queue.priority || [];
    document.getElementById('priorityQueueBadge').textContent = pItems.length;
    pList.innerHTML = pItems.length === 0
        ? '<div class="text-center py-4 text-muted small">Antrean kosong</div>'
        : pItems.map((item, i) => queueItemHTML(item, i, 'priority')).join('');

    const fItems = queue.fifo || [];
    document.getElementById('fifoQueueBadge').textContent = fItems.length;
    fList.innerHTML = fItems.length === 0
        ? '<div class="text-center py-4 text-muted small">Antrean kosong</div>'
        : fItems.map((item, i) => queueItemHTML(item, i, 'fifo')).join('');
}

function queueItemHTML(item, idx, type) {
    const badgeClass = type === 'priority' ? 'bg-danger' : 'bg-secondary';
    return `
        <div class="queue-item list-group-item border-0 px-3 py-3">
            <div class="d-flex align-items-center gap-3">
                <span class="badge ${badgeClass} rounded-circle" style="width:28px;height:28px;line-height:28px;padding:0;text-align:center;">${idx+1}</span>
                ${item.thumbnail ? `<img src="${item.thumbnail}" width="40" height="40" class="rounded" style="object-fit:cover;">` : ''}
                <div class="flex-grow-1 min-width-0">
                    <h6 class="fw-bold mb-0 text-truncate" style="font-size:0.9rem;">${item.title}</h6>
                    <small class="text-muted text-truncate d-block">${item.artist}</small>
                </div>
                <div class="text-end" style="min-width: 70px;">
                    ${type === 'priority' ? `<span class="badge bg-success bg-opacity-10 text-success border-0" style="font-size:0.7rem;">${formatCurrency(item.nominal)}</span>` : ''}
                    <small class="text-muted d-block">${formatDuration(item.duration)}</small>
                </div>
            </div>
        </div>
    `;
}

// =====================
// API Calls
// =====================
async function playNext() {
    if (isPlayingNext) return;
    isPlayingNext = true;
    try {
        const res = await fetch('/admin/play-next', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded', 'X-Requested-With': 'XMLHttpRequest' },
            body: `cafe_id=${cafeId}`
        });

        if (res.status === 401) {
            showAlert('Sesi Anda telah berakhir. Silakan login kembali.', 'warning');
            setTimeout(() => window.location.href = '/auth/login', 2000);
            return;
        }

        const contentType = res.headers.get("content-type");
        if (!res.ok || !contentType || contentType.indexOf("application/json") === -1) {
            const text = await res.text();
            console.error("Non-JSON Response from play-next:", text);
            showAlert('Gagal memutar lagu: Terjadi kesalahan server', 'danger');
            return;
        }

        const result = await res.json();
        if (result.success) {
            if (result.spotify_playback && result.spotify_playback.status) {
                if (result.spotify_playback.status === 'success') {
                    showAlert(result.spotify_playback.message, 'success');
                } else {
                    showAlert(result.spotify_playback.message, 'warning');
                }
            } else {
                showAlert('Racikan lagu berikutnya siap dinikmati!', 'success');
            }
            if (result.song?.api_song_id) showSpotifyEmbed(result.song.api_song_id);
            await fetchDashboardUpdates();
        } else {
            showAlert(result.message, 'warning');
        }
    } catch (e) {
        showAlert('Gagal memutar lagu: ' + e.message, 'danger');
    } finally {
        isPlayingNext = false;
    }
}

async function markDone(requestId) {
    stopDurationTimer();
    try {
        const res = await fetch('/admin/mark-done', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded', 'X-Requested-With': 'XMLHttpRequest' },
            body: `cafe_id=${cafeId}&request_id=${requestId}`
        });

        if (res.status === 401) {
            showAlert('Sesi Anda telah berakhir. Silakan login kembali.', 'warning');
            setTimeout(() => window.location.href = '/auth/login', 2000);
            return;
        }

        const contentType = res.headers.get("content-type");
        if (!res.ok || !contentType || contentType.indexOf("application/json") === -1) {
            const text = await res.text();
            console.error("Non-JSON Response from mark-done:", text);
            showAlert('Gagal menandai selesai: Terjadi kesalahan server', 'danger');
            return;
        }

        const result = await res.json();
        if (result.success) {
            showAlert('Lagu ditandai selesai', 'success');
            currentTrack = null;
            hideSpotifyEmbed();
            await fetchDashboardUpdates();
            if (autoPlayEnabled) {
                const pC = parseInt(document.getElementById('priorityQueueBadge').textContent) || 0;
                const fC = parseInt(document.getElementById('fifoQueueBadge').textContent) || 0;
                if (pC > 0 || fC > 0) {
                    playNext();
                }
            }
        } else {
            showAlert(result.message, 'warning');
        }
    } catch (e) {
        showAlert('Error: ' + e.message, 'danger');
    }
}

// =====================
// Init
// =====================
document.addEventListener('DOMContentLoaded', () => {
    fetchDashboardUpdates();
    pollingInterval = setInterval(fetchDashboardUpdates, 3000);
});
window.onbeforeunload = () => { clearInterval(pollingInterval); stopDurationTimer(); };
</script>

<?= $this->endSection() ?>
