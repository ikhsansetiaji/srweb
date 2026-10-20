<?= $this->extend('layout/app') ?>

<?= $this->section('css') ?>
<style>
    /* ===== VERIFY DEVICE PAGE ===== */
    body { background: #0f0f1a; }
    .vd-wrap {
        min-height: calc(100vh - 56px);
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 24px 16px;
        background: radial-gradient(ellipse at 60% 20%, rgba(229,57,53,0.12) 0%, transparent 60%),
                    radial-gradient(ellipse at 20% 80%, rgba(26,26,46,0.8) 0%, transparent 60%),
                    #0f0f1a;
    }
    .vd-card {
        width: 100%;
        max-width: 460px;
        background: #1a1a2e;
        border-radius: 20px;
        overflow: hidden;
        box-shadow: 0 24px 80px rgba(0,0,0,0.5);
        border: 1px solid rgba(255,255,255,0.06);
    }
    .vd-header {
        padding: 36px 36px 28px;
        text-align: center;
        background: linear-gradient(135deg, rgba(229,57,53,0.15) 0%, transparent 60%);
        border-bottom: 1px solid rgba(255,255,255,0.06);
    }
    .vd-shield-icon {
        width: 72px; height: 72px;
        background: linear-gradient(135deg, #e53935, #ff5252);
        border-radius: 18px;
        display: inline-flex; align-items: center; justify-content: center;
        font-size: 1.8rem; color: #fff;
        margin-bottom: 18px;
        box-shadow: 0 8px 24px rgba(229,57,53,0.35);
        animation: float 3s ease-in-out infinite;
    }
    @keyframes float {
        0%, 100% { transform: translateY(0); }
        50% { transform: translateY(-6px); }
    }
    .vd-header h1 {
        color: #fff; font-size: 1.35rem; font-weight: 800; margin: 0 0 6px;
        letter-spacing: -0.3px;
    }
    .vd-header p { color: rgba(255,255,255,0.5); font-size: 0.83rem; margin: 0; line-height: 1.5; }
    .vd-body { padding: 32px 36px; }

    /* Device info badge */
    .vd-device-info {
        background: rgba(255,255,255,0.04);
        border: 1px solid rgba(255,255,255,0.08);
        border-radius: 10px;
        padding: 12px 16px;
        margin-bottom: 24px;
        display: flex; align-items: center; gap: 12px;
    }
    .vd-device-info i {
        color: #e53935; font-size: 1.2rem; flex-shrink: 0;
    }
    .vd-device-info span { color: rgba(255,255,255,0.55); font-size: 0.8rem; line-height: 1.4; }
    .vd-device-info strong { color: rgba(255,255,255,0.85); }

    /* Email info */
    .vd-email-badge {
        text-align: center; margin-bottom: 24px;
    }
    .vd-email-badge .label {
        color: rgba(255,255,255,0.4); font-size: 0.75rem; text-transform: uppercase;
        letter-spacing: 0.8px; margin-bottom: 6px;
    }
    .vd-email-badge .email {
        color: #fff; font-size: 0.92rem; font-weight: 600;
        background: rgba(229,57,53,0.12);
        border: 1px solid rgba(229,57,53,0.25);
        border-radius: 8px;
        padding: 6px 14px;
        display: inline-block;
    }

    /* Send OTP button */
    .btn-send-otp {
        width: 100%;
        padding: 12px;
        border-radius: 10px;
        background: linear-gradient(135deg, #e53935, #ff5252);
        color: #fff; font-size: 0.88rem; font-weight: 700;
        border: none; cursor: pointer;
        transition: all 0.2s;
        margin-bottom: 20px;
        display: flex; align-items: center; justify-content: center; gap: 8px;
    }
    .btn-send-otp:hover { transform: translateY(-1px); box-shadow: 0 6px 20px rgba(229,57,53,0.4); }
    .btn-send-otp:disabled { opacity: 0.6; cursor: not-allowed; transform: none; }

    /* OTP Input */
    .otp-section { display: none; }
    .otp-section.show { display: block; }
    .otp-label { color: rgba(255,255,255,0.6); font-size: 0.8rem; margin-bottom: 10px; }
    .otp-inputs {
        display: flex; gap: 8px; justify-content: center; margin-bottom: 16px;
    }
    .otp-digit {
        width: 52px; height: 60px;
        background: rgba(255,255,255,0.05);
        border: 2px solid rgba(255,255,255,0.1);
        border-radius: 10px;
        color: #fff; font-size: 1.5rem; font-weight: 700; text-align: center;
        outline: none; transition: all 0.2s;
        font-family: 'Courier New', monospace;
    }
    .otp-digit:focus {
        border-color: #e53935;
        background: rgba(229,57,53,0.08);
        box-shadow: 0 0 0 3px rgba(229,57,53,0.15);
    }
    .otp-digit.filled { border-color: rgba(229,57,53,0.5); color: #ff5252; }
    .otp-digit.error { border-color: #e53935; background: rgba(229,57,53,0.1); animation: shake 0.4s; }
    @keyframes shake {
        0%, 100% { transform: translateX(0); }
        25% { transform: translateX(-6px); }
        75% { transform: translateX(6px); }
    }

    /* Timer */
    .otp-timer {
        text-align: center; color: rgba(255,255,255,0.4); font-size: 0.78rem; margin-bottom: 16px;
    }
    .otp-timer span { color: #ff5252; font-weight: 700; }

    /* Trust checkbox */
    .trust-check {
        display: flex; align-items: center; gap: 10px;
        background: rgba(255,255,255,0.03);
        border: 1px solid rgba(255,255,255,0.07);
        border-radius: 10px;
        padding: 12px 14px; margin-bottom: 20px; cursor: pointer;
    }
    .trust-check input[type="checkbox"] {
        width: 18px; height: 18px; accent-color: #e53935; flex-shrink: 0; cursor: pointer;
    }
    .trust-check label {
        color: rgba(255,255,255,0.65); font-size: 0.8rem; cursor: pointer; line-height: 1.4;
        margin: 0;
    }
    .trust-check label strong { color: rgba(255,255,255,0.85); }

    /* Verify button */
    .btn-verify-otp {
        width: 100%; padding: 13px;
        border-radius: 10px;
        background: linear-gradient(135deg, #059669, #10b981);
        color: #fff; font-size: 0.9rem; font-weight: 700;
        border: none; cursor: pointer; transition: all 0.2s;
        display: flex; align-items: center; justify-content: center; gap: 8px;
    }
    .btn-verify-otp:hover { transform: translateY(-1px); box-shadow: 0 6px 20px rgba(5,150,105,0.4); }
    .btn-verify-otp:disabled { opacity: 0.6; cursor: not-allowed; transform: none; }

    /* Alert */
    .vd-alert {
        border-radius: 8px; padding: 10px 14px;
        font-size: 0.8rem; margin-bottom: 16px;
        display: none;
    }
    .vd-alert.show { display: flex; align-items: center; gap: 8px; }
    .vd-alert.success { background: rgba(5,150,105,0.15); color: #34d399; border: 1px solid rgba(5,150,105,0.25); }
    .vd-alert.error   { background: rgba(229,57,53,0.15);  color: #f87171; border: 1px solid rgba(229,57,53,0.25); }

    /* Resend */
    .otp-resend {
        text-align: center; margin-top: 12px;
    }
    .otp-resend button {
        background: none; border: none; color: rgba(255,255,255,0.4);
        font-size: 0.78rem; cursor: not-allowed;
        transition: all 0.2s;
    }
    .otp-resend button.active { color: #ff5252; cursor: pointer; }
    .otp-resend button.active:hover { text-decoration: underline; }

    /* Footer */
    .vd-footer {
        padding: 16px 36px 24px;
        text-align: center;
    }
    .vd-footer a {
        color: rgba(255,255,255,0.3); font-size: 0.75rem; text-decoration: none;
        transition: color 0.2s;
    }
    .vd-footer a:hover { color: rgba(255,255,255,0.6); }

    /* Security tips */
    .security-tip {
        margin-top: 20px;
        background: rgba(245,158,11,0.08);
        border: 1px solid rgba(245,158,11,0.2);
        border-radius: 8px;
        padding: 10px 14px;
        display: flex; gap: 8px;
    }
    .security-tip i { color: #f59e0b; flex-shrink: 0; margin-top: 2px; }
    .security-tip p { color: rgba(255,255,255,0.5); font-size: 0.75rem; margin: 0; line-height: 1.5; }
</style>
<?= $this->endSection() ?>

<?= $this->section('content') ?>

<div class="vd-wrap">
    <div class="vd-card">
        <!-- Header -->
        <div class="vd-header">
            <div class="vd-shield-icon">
                <i class="fas fa-shield-alt"></i>
            </div>
            <h1>Verifikasi Perangkat</h1>
            <p>Kami mendeteksi login dari perangkat baru.<br>Konfirmasi identitas Anda untuk melanjutkan.</p>
        </div>

        <!-- Body -->
        <div class="vd-body">
            <!-- Device info -->
            <div class="vd-device-info">
                <i class="fas fa-laptop"></i>
                <span id="deviceInfoText">
                    <strong>Perangkat baru terdeteksi.</strong><br>
                    Verifikasi diperlukan untuk keamanan akun superadmin Anda.
                </span>
            </div>

            <!-- Email target -->
            <div class="vd-email-badge">
                <div class="label">OTP akan dikirim ke</div>
                <div class="email"><i class="fas fa-envelope me-2" style="opacity:.6"></i><?= esc($userEmail) ?></div>
            </div>

            <!-- Alert -->
            <div class="vd-alert" id="vdAlert"></div>

            <!-- Step 1: Kirim OTP -->
            <div id="step1">
                <button class="btn-send-otp" id="btnSendOtp" onclick="sendOtp()">
                    <i class="fas fa-paper-plane"></i> Kirim Kode OTP ke Email
                </button>
            </div>

            <!-- Step 2: Masukkan OTP -->
            <div class="otp-section" id="step2">
                <p class="otp-label">Masukkan 6 digit kode OTP yang dikirim ke email Anda:</p>
                <div class="otp-inputs" id="otpInputs">
                    <input type="text" class="otp-digit" id="d0" maxlength="1" inputmode="numeric" pattern="[0-9]" autocomplete="one-time-code">
                    <input type="text" class="otp-digit" id="d1" maxlength="1" inputmode="numeric" pattern="[0-9]">
                    <input type="text" class="otp-digit" id="d2" maxlength="1" inputmode="numeric" pattern="[0-9]">
                    <input type="text" class="otp-digit" id="d3" maxlength="1" inputmode="numeric" pattern="[0-9]">
                    <input type="text" class="otp-digit" id="d4" maxlength="1" inputmode="numeric" pattern="[0-9]">
                    <input type="text" class="otp-digit" id="d5" maxlength="1" inputmode="numeric" pattern="[0-9]">
                </div>

                <div class="otp-timer" id="otpTimer">
                    OTP berlaku selama <span id="timerCount">10:00</span>
                </div>

                <!-- Trust device checkbox -->
                <div class="trust-check">
                    <input type="checkbox" id="trustDevice" checked>
                    <label for="trustDevice">
                        <strong>Percayai perangkat ini</strong> selama 30 hari<br>
                        Saya tidak perlu verifikasi lagi di perangkat ini
                    </label>
                </div>

                <button class="btn-verify-otp" id="btnVerifyOtp" onclick="verifyOtp()" disabled>
                    <i class="fas fa-check-circle"></i> Verifikasi & Masuk
                </button>

                <div class="otp-resend">
                    <button id="btnResend" onclick="resendOtp()" class="">
                        <i class="fas fa-redo me-1"></i>Kirim ulang OTP
                        <span id="resendTimer"></span>
                    </button>
                </div>

                <div class="security-tip">
                    <i class="fas fa-exclamation-triangle fa-sm"></i>
                    <p>Jangan berikan kode OTP ini kepada siapapun termasuk tim support. OTP berlaku 10 menit.</p>
                </div>
            </div>
        </div>

        <!-- Footer -->
        <div class="vd-footer">
            <a href="/auth/logout"><i class="fas fa-sign-out-alt me-1"></i>Batal & Keluar</a>
        </div>
    </div>
</div>

<script>
(function () {
    // Deteksi device info
    const ua = navigator.userAgent;
    let deviceType = 'Desktop';
    if (/Mobile|Android|iPhone/.test(ua)) deviceType = 'Mobile';
    else if (/iPad|Tablet/.test(ua)) deviceType = 'Tablet';
    document.getElementById('deviceInfoText').innerHTML =
        `<strong>${deviceType} baru terdeteksi.</strong><br>` +
        `Browser: ${detectBrowser(ua)} · Verifikasi diperlukan untuk keamanan.`;

    function detectBrowser(ua) {
        if (ua.includes('Chrome') && !ua.includes('Edg')) return 'Chrome';
        if (ua.includes('Firefox')) return 'Firefox';
        if (ua.includes('Safari') && !ua.includes('Chrome')) return 'Safari';
        if (ua.includes('Edg')) return 'Edge';
        return 'Browser Lain';
    }

    // OTP digit auto-focus
    const digits = document.querySelectorAll('.otp-digit');
    digits.forEach((el, i) => {
        el.addEventListener('input', function (e) {
            this.value = this.value.replace(/[^0-9]/g, '');
            this.classList.toggle('filled', this.value !== '');
            if (this.value && i < 5) digits[i + 1].focus();
            checkOtpComplete();
        });
        el.addEventListener('keydown', function (e) {
            if (e.key === 'Backspace' && !this.value && i > 0) {
                digits[i - 1].focus();
                digits[i - 1].value = '';
                digits[i - 1].classList.remove('filled');
                checkOtpComplete();
            }
        });
        el.addEventListener('paste', function (e) {
            e.preventDefault();
            const text = (e.clipboardData || window.clipboardData).getData('text').replace(/\D/g, '');
            if (text.length === 6) {
                digits.forEach((d, idx) => {
                    d.value = text[idx] || '';
                    d.classList.toggle('filled', !!d.value);
                });
                digits[5].focus();
                checkOtpComplete();
            }
        });
    });

    function checkOtpComplete() {
        const otp = getOtp();
        document.getElementById('btnVerifyOtp').disabled = otp.length < 6;
    }

    // Timer countdown
    let timerInterval = null;
    window.startTimer = function (seconds) {
        clearInterval(timerInterval);
        const el = document.getElementById('timerCount');
        const resendBtn = document.getElementById('btnResend');
        const resendTimer = document.getElementById('resendTimer');
        let remaining = seconds;

        timerInterval = setInterval(() => {
            const m = Math.floor(remaining / 60).toString().padStart(2, '0');
            const s = (remaining % 60).toString().padStart(2, '0');
            el.textContent = `${m}:${s}`;

            // Enable resend after 60s
            if (remaining <= (seconds - 60)) {
                resendBtn.classList.add('active');
                resendBtn.textContent = 'Kirim ulang OTP';
            } else {
                const wait = seconds - 60 - (seconds - remaining);
                resendTimer.textContent = ` (${wait}s)`;
            }

            remaining--;
            if (remaining < 0) {
                clearInterval(timerInterval);
                el.textContent = '00:00';
                el.style.color = '#f87171';
                showAlert('Kode OTP sudah expired. Minta kode baru.', 'error');
            }
        }, 1000);
    };

    window.getOtp = function () {
        return Array.from(digits).map(d => d.value).join('');
    };

    window.showAlert = function (msg, type) {
        const el = document.getElementById('vdAlert');
        el.className = `vd-alert show ${type}`;
        el.innerHTML = `<i class="fas fa-${type === 'success' ? 'check-circle' : type === 'error' ? 'times-circle' : 'info-circle'}"></i> ${msg}`;
        setTimeout(() => el.classList.remove('show'), 6000);
    };

    window.sendOtp = async function () {
        const btn = document.getElementById('btnSendOtp');
        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Mengirim...';

        try {
            const res = await fetch('/superadmin/send-otp', {
                method: 'POST',
                headers: { 'X-Requested-With': 'XMLHttpRequest', 'Content-Type': 'application/json' }
            });
            const data = await res.json();

            if (data.success) {
                showAlert(data.message, 'success');
                document.getElementById('step1').style.display = 'none';
                const step2 = document.getElementById('step2');
                step2.classList.add('show');
                startTimer(600); // 10 menit
                digits[0].focus();
            } else {
                showAlert(data.message || 'Gagal mengirim OTP.', 'error');
                btn.disabled = false;
                btn.innerHTML = '<i class="fas fa-paper-plane"></i> Kirim Kode OTP ke Email';
            }
        } catch (err) {
            showAlert('Terjadi kesalahan. Coba lagi.', 'error');
            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-paper-plane"></i> Kirim Kode OTP ke Email';
        }
    };

    window.resendOtp = async function () {
        const btn = document.getElementById('btnResend');
        if (!btn.classList.contains('active')) return;
        btn.classList.remove('active');
        digits.forEach(d => { d.value = ''; d.classList.remove('filled', 'error'); });
        document.getElementById('btnVerifyOtp').disabled = true;

        try {
            const res = await fetch('/superadmin/send-otp', {
                method: 'POST',
                headers: { 'X-Requested-With': 'XMLHttpRequest', 'Content-Type': 'application/json' }
            });
            const data = await res.json();
            showAlert(data.message || (data.success ? 'OTP baru terkirim.' : 'Gagal.'), data.success ? 'success' : 'error');
            if (data.success) {
                startTimer(600);
                digits[0].focus();
            }
        } catch {
            showAlert('Gagal mengirim ulang OTP.', 'error');
        }
    };

    window.verifyOtp = async function () {
        const otp  = getOtp();
        const trust = document.getElementById('trustDevice').checked;
        const btn  = document.getElementById('btnVerifyOtp');

        if (otp.length < 6) return;

        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Memverifikasi...';

        try {
            const res = await fetch('/superadmin/verify-otp', {
                method: 'POST',
                headers: { 'X-Requested-With': 'XMLHttpRequest', 'Content-Type': 'application/json' },
                body: JSON.stringify({ otp, trust_device: trust }),
            });
            const data = await res.json();

            if (data.success) {
                showAlert('✓ Verifikasi berhasil! Mengarahkan...', 'success');
                clearInterval(timerInterval);
                setTimeout(() => { window.location.href = data.redirect || '/superadmin/dashboard'; }, 1200);
            } else {
                digits.forEach(d => d.classList.add('error'));
                setTimeout(() => digits.forEach(d => d.classList.remove('error')), 500);
                showAlert(data.message || 'OTP salah.', 'error');
                btn.disabled = false;
                btn.innerHTML = '<i class="fas fa-check-circle"></i> Verifikasi & Masuk';
                digits[0].focus();
                digits.forEach(d => { d.value = ''; d.classList.remove('filled'); });
            }
        } catch (err) {
            showAlert('Terjadi kesalahan: ' + err.message, 'error');
            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-check-circle"></i> Verifikasi & Masuk';
        }
    };
})();
</script>

<?= $this->endSection() ?>
