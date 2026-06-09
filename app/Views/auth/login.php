<?= $this->extend('layout/app') ?>

<?= $this->section('content') ?>

<div class="container-lg py-5">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card shadow-sm border-0">
                <div class="card-body p-5">
                    <h2 class="fw-bold mb-4 text-center">Login</h2>

                    <form id="loginForm" method="POST" action="/auth/login">
                        <div class="mb-3">
                            <label for="email" class="form-label fw-600">Email</label>
                            <input type="email" class="form-control form-control-lg" id="email" name="email" required>
                            <div class="invalid-feedback"></div>
                        </div>

                        <div class="mb-4">
                            <label for="password" class="form-label fw-600">Password</label>
                            <div class="input-group">
                                <input type="password" class="form-control form-control-lg" id="password" name="password" required>
                                <button type="button" class="btn btn-outline-secondary" onclick="togglePassword('password', this)" tabindex="-1">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </div>
                            <div class="invalid-feedback" id="passwordError"></div>
                        </div>

                        <button type="submit" class="btn btn-danger btn-lg w-100 mb-3" id="loginBtn">
                            <i class="fas fa-sign-in-alt"></i> Login
                        </button>

                        <p class="text-center text-muted">
                            Belum punya akun? <a href="/auth/register" class="text-danger fw-bold">Daftar sekarang</a>
                        </p>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
/**
 * TAB TOKEN SYSTEM
 *
 * Setiap tab mendapat token unik yang disimpan di sessionStorage.
 * sessionStorage TIDAK dibagikan antar tab (berbeda dengan localStorage).
 * Token ini dikirim via header X-Tab-Token di setiap request ke server,
 * sehingga server tahu data login mana yang harus dipakai untuk tab ini.
 */
(function () {
    // Ambil atau generate tabToken untuk tab ini
    // sessionStorage otomatis terisolasi per tab oleh browser
    if (!sessionStorage.getItem('tabToken')) {
        sessionStorage.setItem('tabToken', generateToken());
    }

    function generateToken() {
        const arr = new Uint8Array(32);
        crypto.getRandomValues(arr);
        return Array.from(arr).map(b => b.toString(16).padStart(2, '0')).join('');
    }

    function getTabToken() {
        return sessionStorage.getItem('tabToken');
    }

    // ===== LOGIN FORM =====
    document.getElementById('loginForm').addEventListener('submit', async function (e) {
        e.preventDefault();

        const btn   = document.getElementById('loginBtn');
        const email    = document.getElementById('email').value;
        const password = document.getElementById('password').value;

        btn.disabled  = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Memproses...';

        // Bersihkan error lama
        document.querySelectorAll('.invalid-feedback').forEach(el => el.textContent = '');
        document.querySelectorAll('.form-control').forEach(el => el.classList.remove('is-invalid'));

        try {
            const response = await fetch('/auth/login', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-Tab-Token': getTabToken()   // <-- kunci utama
                },
                body: JSON.stringify({ email, password })
            });

            const data = await response.json();

            if (data.success) {
                // Append _tab_token ke redirect URL agar navigasi biasa bisa dikenali server
                var sep = data.redirect.includes('?') ? '&' : '?';
                window.location.href = data.redirect + sep + '_tab_token=' + getTabToken();
            } else if (data.errors) {
                // Validation errors
                Object.entries(data.errors).forEach(([field, msg]) => {
                    const input = document.getElementById(field);
                    if (input) {
                        input.classList.add('is-invalid');
                        input.nextElementSibling.textContent = msg;
                    }
                });
                btn.disabled  = false;
                btn.innerHTML = '<i class="fas fa-sign-in-alt"></i> Login';
            } else {
                showAlert(data.message || 'Login gagal', 'danger');
                btn.disabled  = false;
                btn.innerHTML = '<i class="fas fa-sign-in-alt"></i> Login';
            }
        } catch (error) {
            showAlert('Terjadi kesalahan: ' + error.message, 'danger');
            btn.disabled  = false;
            btn.innerHTML = '<i class="fas fa-sign-in-alt"></i> Login';
        }
    });

    // Menggunakan showAlert global dari helpers.js
})();

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