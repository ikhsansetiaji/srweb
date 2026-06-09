<?= $this->extend('layout/app') ?>

<?= $this->section('content') ?>

<div class="container-lg py-5">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card shadow-sm border-0">
                <div class="card-body p-5">
                    <h2 class="fw-bold mb-2 text-center">Daftar Cafe</h2>
                    <p class="text-center text-muted mb-4">Lengkapi data cafe Anda untuk mendaftar di platform Song Request</p>

                    <form id="cafeRegisterForm" method="POST" action="/cafe/register">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="nama_kafe" class="form-label fw-600">Nama Cafe</label>
                                <input type="text" class="form-control form-control-lg" id="nama_kafe" name="nama_kafe" required>
                                <div class="invalid-feedback"></div>
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="phone_number" class="form-label fw-600">No. Telepon</label>
                                <input type="tel" class="form-control form-control-lg" id="phone_number" name="phone_number" required>
                                <div class="invalid-feedback"></div>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="alamat" class="form-label fw-600">Alamat</label>
                            <textarea class="form-control form-control-lg" id="alamat" name="alamat" rows="3" required></textarea>
                            <div class="invalid-feedback"></div>
                        </div>

                        <div class="mb-3">
                            <label for="deskripsi" class="form-label fw-600">Deskripsi (Opsional)</label>
                            <textarea class="form-control form-control-lg" id="deskripsi" name="deskripsi" rows="3"></textarea>
                        </div>

                        <hr>

                        <h5 class="fw-bold mb-3">Metode Pembayaran</h5>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="payment_receiver" class="form-label fw-600">Nama Penerima</label>
                                <input type="text" class="form-control form-control-lg" id="payment_receiver" name="payment_receiver" required>
                                <div class="invalid-feedback"></div>
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="payment_method" class="form-label fw-600">Tipe Pembayaran</label>
                                <select class="form-select form-select-lg" id="payment_method" name="payment_method" required>
                                    <option value="">Pilih metode</option>
                                    <option value="QRIS">QRIS</option>
                                    <option value="bank_transfer">Transfer Bank</option>
                                    <option value="e_wallet">E-Wallet</option>
                                </select>
                                <div class="invalid-feedback"></div>
                            </div>
                        </div>

                        <div class="mb-4">
                            <label for="payment_qris" class="form-label fw-600">QRIS/Detail Pembayaran</label>
                            <textarea class="form-control form-control-lg" id="payment_qris" name="payment_qris" rows="2" placeholder="Masukkan detail QRIS atau nomor rekening"></textarea>
                        </div>

                        <button type="submit" class="btn btn-danger btn-lg w-100 mb-3">
                            <i class="fas fa-store"></i> Daftar Cafe
                        </button>

                        <p class="text-center text-muted small">
                            Data cafe akan kami verifikasi terlebih dahulu sebelum dapat beroperasi
                        </p>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.getElementById('cafeRegisterForm').addEventListener('submit', async function(e) {
    e.preventDefault();

    const formData = new FormData(this);
    const data = Object.fromEntries(formData);

    try {
        const response = await fetch('/cafe/register', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify(data)
        });

        const result = await response.json();

        if (result.success) {
            showAlert(result.message, 'success');
            setTimeout(() => {
                window.location.href = result.redirect;
            }, 1500);
        } else {
            if (result.errors) {
                Object.keys(result.errors).forEach(key => {
                    showAlert(`${key}: ${result.errors[key]}`, 'danger');
                });
            } else {
                showAlert(result.message, 'danger');
            }
        }
    } catch (error) {
        showAlert('Registration failed: ' + error.message, 'danger');
    }
});
</script>

<?= $this->endSection() ?>

