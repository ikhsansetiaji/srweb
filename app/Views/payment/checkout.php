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

<div class="container py-5" style="max-width:480px">

    <?php if ($isSandbox && $clientKey): ?>
    <div class="alert alert-info d-flex gap-2 mb-4" style="font-size:.82rem">
        <i class="fas fa-flask mt-1"></i>
        <span>Mode <strong>Sandbox</strong> — gunakan kartu/akun test Midtrans. Tidak ada uang nyata.</span>
    </div>
    <?php endif; ?>

    <!-- Detail -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body p-4">
            <p class="text-muted mb-3" style="font-size:.72rem;font-weight:700;letter-spacing:.5px;text-transform:uppercase">Detail Request</p>
            <div class="d-flex gap-3 align-items-center mb-3 pb-3 border-bottom">
                <img src="<?= esc($thumbnail) ?>" alt="cover"
                     style="width:60px;height:60px;object-fit:cover;border-radius:8px"
                     onerror="this.src='/assets/images/default-album.png'">
                <div>
                    <div class="fw-bold"><?= esc($title) ?></div>
                    <div class="text-muted small"><?= esc($artist) ?></div>
                    <div class="text-muted small"><i class="fas fa-store me-1"></i><?= esc($cafeName) ?></div>
                </div>
            </div>
            <div class="d-flex justify-content-between mb-1 small">
                <span class="text-muted">Pengirim</span><span class="fw-semibold"><?= esc($guestName) ?></span>
            </div>
            <div class="d-flex justify-content-between mb-1 small">
                <span class="text-muted">Jenis</span><span class="badge bg-danger">Priority</span>
            </div>
            <hr class="my-3">
            <div class="d-flex justify-content-between align-items-center">
                <span class="fw-bold">Total</span>
                <span class="fw-bold fs-5 text-danger">Rp <?= number_format($nominal, 0, ',', '.') ?></span>
            </div>
        </div>
    </div>

    <!-- Tombol -->
    <button id="payBtn" class="btn btn-danger btn-lg w-100 mb-3" onclick="startPayment()">
        <i class="fas fa-lock me-2"></i>
        <?= $clientKey ? 'Bayar Sekarang' : 'Simulasi Bayar (Demo)' ?>
    </button>

    <div class="text-center">
        <a href="javascript:history.back()" class="btn btn-outline-secondary btn-sm">
            <i class="fas fa-arrow-left me-1"></i> Kembali
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