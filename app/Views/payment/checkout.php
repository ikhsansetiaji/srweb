<?= $this->extend('layout/app') ?>

<?= $this->section('content') ?>
<?php
$req       = $request ?? [];
$nominal   = (int)($req['nominal']   ?? 0);
$title     = $req['title']           ?? 'Lagu';
$artist    = $req['artist']          ?? '-';
$cafeName  = $req['nama_kafe']       ?? '-';
$thumbnail = $req['thumbnail']       ?? '/assets/images/default-album.png';
$requestId = (int)($req['id']        ?? 0);
$cafeId    = (int)($req['cafe_id']   ?? 0);
$guestName = $req['guest_name']      ?? 'Anonim';
$clientKey = env('MIDTRANS_CLIENT_KEY', '');
$isSandbox = !env('MIDTRANS_IS_PRODUCTION', false);
?>

<?php if ($clientKey): ?>
<script src="https://app.<?= $isSandbox ? 'sandbox.' : '' ?>midtrans.com/snap/snap.js"
        data-client-key="<?= esc($clientKey) ?>"></script>
<?php endif; ?>

<div class="container py-5" style="max-width:500px">

    <?php if ($isSandbox && $clientKey): ?>
    <div class="alert alert-info border-0 shadow-sm d-flex gap-2 mb-4 rounded-3" style="font-size:.84rem; background: #eff6ff; color: #1e40af;">
        <i class="fas fa-flask mt-1 text-primary"></i>
        <span>Mode <strong>Sandbox</strong> — gunakan kartu/akun test Midtrans. Tidak ada uang nyata yang ditarik.</span>
    </div>
    <?php endif; ?>

    <!-- Detail Card -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body p-4">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <span class="badge badge-coral">Konfirmasi Pembayaran</span>
                <span class="badge bg-danger bg-opacity-10 text-danger border border-danger border-opacity-25 px-2 py-1">
                    <i class="fas fa-star me-1"></i> Prioritas
                </span>
            </div>

            <div class="d-flex gap-3 align-items-center mb-3 pb-3 border-bottom">
                <img src="<?= esc($thumbnail) ?>" alt="cover"
                     style="width:68px;height:68px;object-fit:cover;border-radius:10px"
                     onerror="this.src='/assets/images/default-album.png'">
                <div class="min-width-0">
                    <div class="fw-bold text-dark text-truncate"><?= esc($title) ?></div>
                    <div class="text-muted small text-truncate"><?= esc($artist) ?></div>
                    <div class="text-muted small mt-1"><i class="fas fa-store me-1 text-coral"></i><?= esc($cafeName) ?></div>
                </div>
            </div>

            <div class="d-flex justify-content-between mb-2 small">
                <span class="text-muted">Nama Peminta</span>
                <span class="fw-semibold text-dark"><?= esc($guestName) ?></span>
            </div>
            <div class="d-flex justify-content-between mb-2 small">
                <span class="text-muted">Tipe Antrean</span>
                <span class="text-coral fw-bold">Prioritas (Priority Queue)</span>
            </div>

            <hr class="my-3">

            <div class="d-flex justify-content-between align-items-center">
                <span class="fw-bold text-dark">Total Saweran</span>
                <span class="fw-bold fs-4 text-coral">Rp <?= number_format($nominal, 2, ',', '.') ?></span>
            </div>
            <small class="text-muted d-block mt-2" style="font-size:0.75rem;">
                <i class="fas fa-lock text-success me-1"></i> Pembayaran aman didukung oleh Midtrans Snap Gateway.
            </small>
        </div>
    </div>

    <!-- Tombol Bayar -->
    <button id="payBtn" class="btn btn-coral btn-lg w-100 py-3 mb-3" onclick="startPayment()">
        <i class="fas fa-lock me-2"></i>
        <?= $clientKey ? 'Bayar Sekarang' : 'Simulasi Bayar (Demo)' ?>
    </button>

    <div class="text-center">
        <a href="javascript:history.back()" class="text-muted small text-decoration-none">
            <i class="fas fa-arrow-left me-1"></i> Kembali ubah request
        </a>
    </div>
</div>

<script>
const _REQ_ID  = <?= $requestId ?>;
const _CAFE_ID = <?= $cafeId ?>;
const _HAS_KEY = <?= $clientKey ? 'true' : 'false' ?>;

async function startPayment() {
    var btn = document.getElementById('payBtn');
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i> Memproses...';

    if (!_HAS_KEY) {
        // Mode demo — tidak ada Midtrans
        await demoPayment(btn);
        return;
    }

    try {
        var res  = await fetch('/payment/create', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            body: JSON.stringify({ request_id: _REQ_ID, cafe_id: _CAFE_ID })
        });
        var data = await res.json();

        if (!data.success || !data.snap_token) {
            alert(data.message || 'Gagal membuat transaksi. Coba lagi.');
            resetBtn(btn);
            return;
        }

        // Buka popup Midtrans Snap
        window.snap.pay(data.snap_token, {
            onSuccess: function(r) {
                window.location.href = '/payment/finish?order_id=' + r.order_id + '&status=success';
            },
            onPending: function(r) {
                window.location.href = '/payment/finish?order_id=' + r.order_id + '&status=pending';
            },
            onError: function() {
                alert('Pembayaran gagal. Silakan coba lagi.');
                resetBtn(btn);
            },
            onClose: function() {
                resetBtn(btn);
            }
        });
    } catch(e) {
        alert('Error: ' + e.message);
        resetBtn(btn);
    }
}

async function demoPayment(btn) {
    try {
        var res  = await fetch('/payment/demo-success', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            body: JSON.stringify({ request_id: _REQ_ID, cafe_id: _CAFE_ID })
        });
        var data = await res.json();
        if (data.success) {
            window.location.href = data.redirect || '/';
        } else {
            alert(data.message || 'Gagal');
            resetBtn(btn);
        }
    } catch(e) {
        alert('Error: ' + e.message);
        resetBtn(btn);
    }
}

function resetBtn(btn) {
    btn.disabled = false;
    btn.innerHTML = '<i class="fas fa-' + (_HAS_KEY ? 'lock' : 'play-circle') + ' me-2"></i>' +
                    (_HAS_KEY ? 'Bayar Sekarang' : 'Simulasi Bayar (Demo)');
}
</script>

<?= $this->endSection() ?>