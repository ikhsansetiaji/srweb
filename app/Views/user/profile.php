<?= $this->extend('layout/app') ?>

<?= $this->section('css') ?>
<style>
    body { background: #F4EFEA; color: #34373C; }

    .profile-container {
        max-width: 800px;
        margin: 0 auto;
    }

    .profile-card {
        background: #ffffff;
        border: 1px solid rgba(224, 90, 71, 0.12);
        border-radius: 16px;
        color: #34373C;
        padding: 40px;
        margin-bottom: 30px;
        box-shadow: 0 8px 30px rgba(0, 0, 0, 0.04) !important;
    }

    .profile-header {
        display: flex;
        align-items: center;
        gap: 24px;
        border-bottom: 1px solid rgba(224, 90, 71, 0.12);
        padding-bottom: 30px;
        margin-bottom: 30px;
    }

    .profile-avatar {
        width: 90px;
        height: 90px;
        border-radius: 50%;
        background: linear-gradient(135deg, #E05A47 0%, #FF6347 100%);
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 2.5rem;
        font-weight: 700;
        color: #fff;
        box-shadow: 0 8px 20px rgba(224, 90, 71, 0.3);
    }

    .profile-info h3 {
        font-family: 'Poppins', sans-serif;
        font-weight: 700;
        margin-bottom: 6px;
        color: #34373C;
    }

    .profile-info .role-badge {
        background: rgba(224, 90, 71, 0.1);
        color: #E05A47;
        border: 1px solid rgba(224, 90, 71, 0.2);
        padding: 4px 12px;
        border-radius: 50px;
        font-size: 0.8rem;
        font-weight: 600;
        display: inline-block;
    }

    .stats-row {
        display: flex;
        gap: 20px;
        margin-bottom: 30px;
    }

    .stat-box {
        flex: 1;
        background: rgba(224, 90, 71, 0.03);
        border: 1px solid rgba(224, 90, 71, 0.08);
        border-radius: 12px;
        padding: 20px;
        text-align: center;
    }

    .stat-box .value {
        font-size: 1.8rem;
        font-weight: 700;
        color: #E05A47;
        margin-bottom: 4px;
    }

    .stat-box .label {
        font-size: 0.85rem;
        color: #666;
    }

    .form-group-sr {
        margin-bottom: 20px;
    }

    .form-group-sr label {
        font-weight: 600;
        font-size: 0.9rem;
        margin-bottom: 8px;
        color: #34373C;
        display: block;
    }

    .form-control-sr {
        background: #ffffff;
        border: 1px solid rgba(224, 90, 71, 0.12);
        border-radius: 10px;
        color: #34373C;
        padding: 12px 16px;
        font-size: 0.95rem;
        transition: all 0.2s;
        width: 100%;
    }

    .form-control-sr:focus {
        background: #ffffff;
        border-color: #E05A47;
        outline: none;
        box-shadow: 0 0 0 3px rgba(224, 90, 71, 0.25);
    }

    .form-control-sr:disabled {
        opacity: 0.55;
        background: rgba(224, 90, 71, 0.03);
        cursor: not-allowed;
    }

    .btn-save-profile {
        background: #E05A47;
        color: #fff;
        border: none;
        font-weight: 600;
        padding: 12px 25px;
        border-radius: 10px;
        transition: all 0.2s;
        display: inline-flex;
        align-items: center;
        gap: 8px;
    }

    .btn-save-profile:hover {
        background: #d9533f;
        transform: translateY(-2px);
        box-shadow: 0 5px 15px rgba(224, 90, 71, 0.3);
    }
</style>
<?= $this->endSection() ?>

<?= $this->section('content') ?>

<div class="container-lg py-5">
    <div class="profile-container">
        <div class="profile-card shadow">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <span class="fs-5 fw-bold text-dark"><i class="fas fa-id-card text-danger me-2"></i>Profil Pengguna</span>
                <a href="/user/dashboard" class="btn btn-outline-dark btn-sm rounded-pill px-3">
                    <i class="fas fa-arrow-left me-2"></i>Kembali
                </a>
            </div>

            <!-- Profile Header -->
            <div class="profile-header">
                <?php 
                    $initials = '';
                    if (!empty($user['name'])) {
                        $parts = explode(' ', $user['name']);
                        $initials = strtoupper(substr($parts[0], 0, 1) . (count($parts) > 1 ? substr($parts[1], 0, 1) : ''));
                    } else {
                        $initials = 'U';
                    }
                ?>
                <div class="profile-avatar">
                    <?= esc($initials) ?>
                </div>
                <div class="profile-info">
                    <h3><?= esc($user['name'] ?? 'Penikmat Musik') ?></h3>
                    <div class="role-badge">
                        <i class="fas fa-user-check me-1"></i><?= ucfirst(esc($user['role'] ?? 'user')) ?>
                    </div>
                </div>
            </div>

            <!-- Stats -->
            <div class="stats-row">
                <div class="stat-box">
                    <div class="value"><?= esc($totalRequests) ?></div>
                    <div class="label">Total Request Lagu</div>
                </div>
                <div class="stat-box">
                    <div class="value">Rp <?= number_format($totalSpent, 0, ',', '.') ?></div>
                    <div class="label">Total Saweran</div>
                </div>
            </div>

            <!-- Form Edit -->
            <form id="profileForm">
                <?= csrf_field() ?>
                <input type="hidden" name="_method" value="PUT">

                <div class="form-group-sr">
                    <label for="name">Nama Lengkap</label>
                    <input type="text" id="name" name="name" class="form-control-sr" value="<?= esc($user['name'] ?? '') ?>" required>
                </div>

                <div class="form-group-sr">
                    <label for="username">Username</label>
                    <input type="text" id="username" class="form-control-sr" value="<?= esc($user['username'] ?? '-') ?>" disabled>
                </div>

                <div class="form-group-sr">
                    <label for="email">Alamat Email</label>
                    <input type="email" id="email" class="form-control-sr" value="<?= esc($user['email'] ?? '') ?>" disabled>
                    <small class="text-muted mt-1 d-block"><i class="fas fa-info-circle me-1"></i>Email dan Username tidak dapat diubah.</small>
                </div>

                <div class="text-end mt-4">
                    <button type="submit" class="btn-save-profile" id="submitBtn">
                        <i class="fas fa-save"></i> Simpan Perubahan
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<?= $this->endSection() ?>

<?= $this->section('js') ?>
<script>
document.getElementById('profileForm').addEventListener('submit', async function(e) {
    e.preventDefault();
    
    const submitBtn = document.getElementById('submitBtn');
    const nameInput = document.getElementById('name').value;
    
    submitBtn.disabled = true;
    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Menyimpan...';
    
    try {
        const formData = new FormData(this);
        
        const response = await fetch('/user/profile', {
            method: 'POST', // standard POST to support FormData + spoofed PUT
            body: formData,
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        });
        
        const data = await response.json();
        
        if (response.ok && data.success) {
            showAlert(data.message || 'Profil berhasil disimpan!', 'success');
            // Update the display name in the page header
            document.querySelector('.profile-info h3').textContent = nameInput;
            
            // Generate new avatar initials
            const parts = nameInput.trim().split(' ');
            const initials = (parts[0].charAt(0) + (parts.length > 1 ? parts[1].charAt(0) : '')).toUpperCase();
            document.querySelector('.profile-avatar').textContent = initials;
            
            // Also update navbar if present
            const navUser = document.querySelector('.dropdown-toggle');
            if (navUser) {
                navUser.innerHTML = `<i class="fas fa-user-circle me-1"></i>${nameInput}`;
            }
        } else {
            let errMsg = data.message || 'Terjadi kesalahan saat menyimpan profil';
            if (data.errors && Object.keys(data.errors).length > 0) {
                errMsg = Object.values(data.errors).join('<br>');
            }
            showAlert(errMsg, 'danger');
        }
    } catch (error) {
        showAlert('Koneksi bermasalah: ' + error.message, 'danger');
    } finally {
        submitBtn.disabled = false;
        submitBtn.innerHTML = '<i class="fas fa-save"></i> Simpan Perubahan';
    }
});
</script>
<?= $this->endSection() ?>
