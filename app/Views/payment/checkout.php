<?= $this->extend('layout/app') ?>

<?= $this->section('content') ?>

<div class="container-lg py-5">
    <div class="row">
        <div class="col-lg-8 mx-auto">
            <h2 class="fw-bold mb-4">
                <i class="fas fa-credit-card text-danger"></i> Pembayaran
            </h2>

            <div class="row g-4">
                <!-- Order Summary -->
                <div class="col-md-7">
                    <div class="card shadow-sm border-0 mb-4">
                        <div class="card-body p-4">
                            <h5 class="fw-bold mb-3">Detail Request</h5>

                            <div class="row align-items-center mb-3 pb-3 border-bottom">
                                <div class="col-3">
                                    <img src="/assets/images/default-album.png" alt="Album" class="img-fluid rounded">
                                </div>
                                <div class="col-9">
                                    <h6 class="fw-bold mb-1">Lorem Ipsum Dolor</h6>
                                    <p class="text-muted mb-0">Artist Name</p>
                                    <small class="text-muted">Cafe Name</small>
                                </div>
                            </div>

                            <div class="mb-3">
                                <p class="text-muted mb-1">Nama Pengirim:</p>
                                <p class="fw-bold">Anonim</p>
                            </div>

                            <div class="mb-3">
                                <p class="text-muted mb-1">Nominal Saweran:</p>
                                <p class="fw-bold fs-5">Rp 25.000</p>
                            </div>

                            <hr>

                            <div class="d-flex justify-content-between fw-bold fs-5">
                                <span>Total Pembayaran:</span>
                                <span class="text-danger">Rp 25.000</span>
                            </div>
                        </div>
                    </div>

                    <!-- Payment Methods -->
                    <div class="card shadow-sm border-0">
                        <div class="card-body p-4">
                            <h5 class="fw-bold mb-3">Pilih Metode Pembayaran</h5>

                            <form id="paymentForm">
                                <div class="mb-3">
                                    <div class="form-check payment-method">
                                        <input class="form-check-input" type="radio" name="payment_method" id="qris" value="QRIS" checked>
                                        <label class="form-check-label w-100" for="qris">
                                            <i class="fas fa-qrcode text-danger"></i> QRIS
                                            <small class="d-block text-muted">Scan QR Code untuk pembayaran instant</small>
                                        </label>
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <div class="form-check payment-method">
                                        <input class="form-check-input" type="radio" name="payment_method" id="gopay" value="gopay">
                                        <label class="form-check-label w-100" for="gopay">
                                            <i class="fas fa-mobile-alt text-primary"></i> GoPay
                                            <small class="d-block text-muted">Transfer via GoPay</small>
                                        </label>
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <div class="form-check payment-method">
                                        <input class="form-check-input" type="radio" name="payment_method" id="ovo" value="ovo">
                                        <label class="form-check-label w-100" for="ovo">
                                            <i class="fas fa-wallet text-info"></i> OVO
                                            <small class="d-block text-muted">Transfer via OVO</small>
                                        </label>
                                    </div>
                                </div>

                                <div class="mb-4">
                                    <div class="form-check payment-method">
                                        <input class="form-check-input" type="radio" name="payment_method" id="transfer" value="transfer_bank">
                                        <label class="form-check-label w-100" for="transfer">
                                            <i class="fas fa-university text-success"></i> Transfer Bank
                                            <small class="d-block text-muted">Transfer ke rekening cafe</small>
                                        </label>
                                    </div>
                                </div>

                                <!-- Hidden inputs -->
                                <input type="hidden" name="request_id" value="1">
                                <input type="hidden" name="cafe_id" value="1">

                                <button type="submit" class="btn btn-danger btn-lg w-100">
                                    <i class="fas fa-lock"></i> Lanjut ke Pembayaran
                                </button>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- Info & Security -->
                <div class="col-md-5">
                    <div class="card bg-light border-0 mb-4">
                        <div class="card-body">
                            <h6 class="fw-bold mb-2 text-danger">
                                <i class="fas fa-shield-alt"></i> Keamanan Terjamin
                            </h6>
                            <p class="text-muted small mb-0">
                                Pembayaran Anda dienkripsi dan diproses melalui payment gateway terpercaya.
                            </p>
                        </div>
                    </div>

                    <div class="card bg-light border-0 mb-4">
                        <div class="card-body">
                            <h6 class="fw-bold mb-2 text-danger">
                                <i class="fas fa-info-circle"></i> Cara Kerja
                            </h6>
                            <ol class="text-muted small mb-0">
                                <li>Pilih metode pembayaran</li>
                                <li>Selesaikan pembayaran</li>
                                <li>Lagu masuk ke antrian</li>
                                <li>Tunggu lagu Anda diputar</li>
                            </ol>
                        </div>
                    </div>

                    <div class="card bg-light border-0">
                        <div class="card-body">
                            <h6 class="fw-bold mb-2">
                                <i class="fas fa-redo text-danger"></i> Belum siap?
                            </h6>
                            <p class="text-muted small mb-2">Anda bisa membatalkan kapan saja</p>
                            <a href="/song-request/request" class="btn btn-outline-danger btn-sm w-100">Kembali</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.payment-method {
    padding: 1rem;
    border: 2px solid #e9ecef;
    border-radius: 0.5rem;
    cursor: pointer;
    transition: all 0.3s ease;
}

.payment-method:hover {
    border-color: #ff4500;
    background-color: rgba(255, 69, 0, 0.05);
}

.payment-method input:checked ~ label,
input[type="radio"]:checked ~ label {
    color: #ff4500;
}

.form-check-input:checked {
    background-color: #ff4500;
    border-color: #ff4500;
}
</style>

<script>
document.getElementById('paymentForm').addEventListener('submit', async function(e) {
    e.preventDefault();

    const formData = new FormData(this);
    const data = Object.fromEntries(formData);

    try {
        const response = await fetch('/payment/create', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify(data)
        });

        const result = await response.json();

        if (result.success) {
            // TODO: Redirect to payment gateway (Midtrans/Xendit)
            // window.location.href = result.payment_url;
            showAlert('Payment gateway integration pending', 'info');
        } else {
            showAlert(result.message, 'danger');
        }
    } catch (error) {
        showAlert('Payment failed: ' + error.message, 'danger');
    }
});
</script>

<?= $this->endSection() ?>

