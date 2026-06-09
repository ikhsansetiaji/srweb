<?= $this->extend('layout/app') ?>

<?= $this->section('content') ?>

<div class="container-lg py-5">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card shadow-sm border-0">
                <div class="card-body p-5">
                    <h2 class="fw-bold mb-4 text-center">Daftar Akun</h2>

                    <form id="registerForm" method="POST" action="/auth/register">
                        <div class="mb-3">
                            <label for="name" class="form-label fw-600">Nama Lengkap</label>
                            <input type="text" class="form-control form-control-lg" id="name" name="name" required>
                            <div class="invalid-feedback"></div>
                        </div>

                        <div class="mb-3">
                            <label for="email" class="form-label fw-600">Email</label>
                            <input type="email" class="form-control form-control-lg" id="email" name="email" required>
                            <div class="invalid-feedback"></div>
                        </div>

                        <div class="mb-3">
                            <label for="password" class="form-label fw-600">Password</label>
                            <div class="input-group">
                                <input type="password" class="form-control form-control-lg" id="password" name="password" required>
                                <button type="button" class="btn btn-outline-secondary" onclick="togglePassword('password', this)" tabindex="-1">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </div>
                            <small class="text-muted d-block mt-1">Minimal 8 karakter, harus ada huruf besar, huruf kecil, dan angka</small>
                            <div class="invalid-feedback"></div>
                        </div>

                        <div class="mb-3">
                            <label for="password_confirm" class="form-label fw-600">Konfirmasi Password</label>
                            <div class="input-group">
                                <input type="password" class="form-control form-control-lg" id="password_confirm" name="password_confirm" required>
                                <button type="button" class="btn btn-outline-secondary" onclick="togglePassword('password_confirm', this)" tabindex="-1">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </div>
                            <div class="invalid-feedback"></div>
                        </div>

                        <div class="mb-4">
                            <label for="role" class="form-label fw-600">Tipe Akun</label>
                            <select class="form-select form-select-lg" id="role" name="role" required>
                                <option value="">Pilih tipe akun</option>
                                <option value="user">Pengguna (Request Lagu)</option>
                                <option value="admin">Admin Cafe</option>
                            </select>
                            <div class="invalid-feedback"></div>
                        </div>

                        <button type="submit" class="btn btn-danger btn-lg w-100 mb-3">
                            <i class="fas fa-user-plus"></i> Daftar
                        </button>

                        <p class="text-center text-muted">
                            Sudah punya akun? <a href="/auth/login" class="text-danger fw-bold">Login sekarang</a>
                        </p>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.getElementById('registerForm').addEventListener('submit', async function(e) {
    e.preventDefault();

    const formData = {
        name: document.getElementById('name').value,
        email: document.getElementById('email').value,
        password: document.getElementById('password').value,
        password_confirm: document.getElementById('password_confirm').value,
        role: document.getElementById('role').value
    };

    try {
        const response = await fetch('/auth/register', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify(formData)
        });

        const data = await response.json();

        if (data.success) {
            showAlert(data.message, 'success');
            setTimeout(() => {
                window.location.href = data.redirect;
            }, 1500);
        } else {
            if (data.errors) {
                Object.keys(data.errors).forEach(key => {
                    showAlert(`${key}: ${data.errors[key]}`, 'danger');
                });
            } else {
                showAlert(data.message, 'danger');
            }
        }
    } catch (error) {
        showAlert('Registration failed: ' + error.message, 'danger');
    }
});

function togglePassword(id, btn) {
    var inp = document.getElementById(id);
    var icon = btn.querySelector('i');
    if (inp.type === 'password') {
        inp.type = 'text';
        icon.className = 'fas fa-eye-slash';
    } else {
        inp.type = 'password';
        icon.className = 'fas fa-eye';
    }
}
</script>

<?= $this->endSection() ?>