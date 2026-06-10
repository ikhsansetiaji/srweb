<?= $this->extend('layout/app') ?>

<?= $this->section('css') ?>
<style>
    body { background: #F4EFEA; color: #34373C; }

    .select-cafe-container {
        max-width: 1000px;
        margin: 0 auto;
    }

    .header-section {
        text-align: center;
        margin-bottom: 40px;
    }

    .header-section h2 {
        font-family: 'Poppins', sans-serif;
        font-weight: 700;
        color: #34373C;
        margin-bottom: 10px;
    }

    .header-section p {
        color: #666;
        font-size: 1.05rem;
    }

    .search-wrapper {
        position: relative;
        margin-bottom: 40px;
    }

    .search-input-sr {
        background: #ffffff;
        border: 1px solid rgba(224, 90, 71, 0.15);
        border-radius: 50px;
        color: #34373C;
        padding: 18px 25px 18px 60px;
        font-size: 1.1rem;
        transition: all 0.3s;
        box-shadow: 0 10px 30px rgba(0,0,0,0.03);
    }

    .search-input-sr:focus {
        background: #ffffff;
        border-color: #E05A47;
        box-shadow: 0 10px 30px rgba(224, 90, 71, 0.15);
        outline: none;
    }

    .search-icon {
        position: absolute;
        left: 25px;
        top: 50%;
        transform: translateY(-50%);
        font-size: 1.3rem;
        color: #666;
    }

    .cafe-card {
        background: #ffffff;
        border: 1px solid rgba(224, 90, 71, 0.12);
        border-radius: 20px;
        color: #34373C;
        transition: all 0.3s cubic-bezier(0.25, 0.8, 0.25, 1);
        overflow: hidden;
        height: 100%;
        display: flex;
        flex-direction: column;
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.02);
    }

    .cafe-card:hover {
        transform: translateY(-5px);
        border-color: #E05A47;
        box-shadow: 0 15px 35px rgba(224, 90, 71, 0.15);
    }

    .cafe-card-body {
        padding: 30px;
        flex-grow: 1;
        display: flex;
        flex-direction: column;
    }

    .cafe-logo-placeholder {
        width: 60px;
        height: 60px;
        border-radius: 14px;
        background: linear-gradient(135deg, #F4EFEA 0%, #E05A47 100%);
        border: 1px solid rgba(224, 90, 71, 0.15);
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.8rem;
        color: #ffffff;
        margin-bottom: 20px;
    }

    .cafe-title {
        font-family: 'Poppins', sans-serif;
        font-weight: 700;
        font-size: 1.25rem;
        color: #34373C;
        margin-bottom: 12px;
    }

    .cafe-meta {
        font-size: 0.9rem;
        color: #666;
        margin-bottom: 8px;
        display: flex;
        align-items: flex-start;
        gap: 10px;
    }

    .cafe-meta i {
        color: #E05A47;
        margin-top: 3px;
        width: 16px;
    }

    .cafe-card-footer {
        padding: 0 30px 30px 30px;
    }

    .btn-select-cafe {
        background: #E05A47;
        color: #fff;
        border: none;
        border-radius: 12px;
        padding: 12px 20px;
        font-weight: 600;
        width: 100%;
        transition: all 0.2s;
        text-align: center;
        text-decoration: none;
        display: inline-block;
    }

    .btn-select-cafe:hover {
        background: #d9533f;
        box-shadow: 0 5px 15px rgba(224, 90, 71, 0.3);
        color: #fff;
    }

    .empty-state {
        text-align: center;
        padding: 60px 20px;
        opacity: 0.6;
        display: none;
    }

    .empty-state i {
        font-size: 3.5rem;
        margin-bottom: 20px;
        color: #E05A47;
    }

    .empty-state h5 {
        color: #34373C;
        font-weight: 600;
    }
</style>
<?= $this->endSection() ?>

<?= $this->section('content') ?>

<div class="container-lg py-5">
    <div class="select-cafe-container">
        <!-- Header -->
        <div class="header-section">
            <h2>Pilih Kafe Terdekat</h2>
            <p>Silakan pilih kafe tempat Anda berada sekarang untuk mulai request lagu favorit Anda.</p>
        </div>

        <!-- Search Bar -->
        <div class="search-wrapper">
            <i class="fas fa-search search-icon"></i>
            <input type="text" class="form-control form-control-lg search-input-sr" id="cafeSearch" placeholder="Cari berdasarkan nama atau alamat kafe...">
        </div>

        <!-- Cafe List -->
        <div id="cafeList" class="row g-4">
            <!-- Loading Spinner -->
            <div class="col-12 text-center py-5" id="loadingSpinner">
                <div class="spinner-border text-danger" style="width: 3rem; height: 3rem;" role="status">
                    <span class="visually-hidden">Loading...</span>
                </div>
                <p class="text-muted mt-3">Mencari kafe aktif...</p>
            </div>
        </div>

        <!-- Empty State -->
        <div class="empty-state" id="emptyState">
            <i class="fas fa-store-slash"></i>
            <h5>Kafe Tidak Ditemukan</h5>
            <p class="text-muted">Tidak ada kafe yang cocok dengan pencarian Anda.</p>
        </div>
    </div>
</div>

<?= $this->endSection() ?>

<?= $this->section('js') ?>
<script>
let allCafes = [];

// Load active cafes
async function loadCafes() {
    const spinner = document.getElementById('loadingSpinner');
    try {
        const response = await fetch('/cafe/list');
        const data = await response.json();

        if (spinner) spinner.remove();

        if (data.success && data.data) {
            allCafes = data.data;
            renderCafes(allCafes);
        } else {
            showError('Gagal memuat daftar kafe');
        }
    } catch (error) {
        if (spinner) spinner.remove();
        showError('Gagal menghubungkan ke server: ' + error.message);
    }
}

function renderCafes(cafes) {
    const container = document.getElementById('cafeList');
    const emptyState = document.getElementById('emptyState');
    container.innerHTML = '';

    if (cafes.length === 0) {
        emptyState.style.display = 'block';
        return;
    }

    emptyState.style.display = 'none';

    cafes.forEach(cafe => {
        const initial = cafe.nama_kafe.charAt(0).toUpperCase();
        const html = `
            <div class="col-md-6 col-lg-4 cafe-item-card">
                <div class="cafe-card shadow-sm">
                    <div class="cafe-card-body">
                        <div class="cafe-logo-placeholder">
                            ${initial}
                        </div>
                        <h4 class="cafe-title">${escapeHtml(cafe.nama_kafe)}</h4>
                        <div class="cafe-meta text-muted">
                            <i class="fas fa-map-marker-alt"></i>
                            <span>${escapeHtml(cafe.alamat)}</span>
                        </div>
                        ${cafe.phone_number ? `
                            <div class="cafe-meta text-muted">
                                <i class="fas fa-phone"></i>
                                <span>${escapeHtml(cafe.phone_number)}</span>
                            </div>
                        ` : ''}
                    </div>
                    <div class="cafe-card-footer">
                        <a href="/song-request/request?cafe_id=${cafe.id}" class="btn-select-cafe">
                            <i class="fas fa-music me-2"></i>Pilih Kafe
                        </a>
                    </div>
                </div>
            </div>
        `;
        container.insertAdjacentHTML('beforeend', html);
    });
}

function showError(message) {
    const container = document.getElementById('cafeList');
    container.innerHTML = `
        <div class="col-12 text-center py-5 text-danger">
            <i class="fas fa-exclamation-triangle fa-2x mb-3"></i>
            <p>${message}</p>
            <button onclick="location.reload()" class="btn btn-outline-danger btn-sm px-4 rounded-pill mt-2">Coba Lagi</button>
        </div>
    `;
}

function escapeHtml(str) {
    if (!str) return '';
    return str.replace(/&/g, "&amp;")
              .replace(/</g, "&lt;")
              .replace(/>/g, "&gt;")
              .replace(/"/g, "&quot;")
              .replace(/'/g, "&#039;");
}

// Search functionality
document.getElementById('cafeSearch').addEventListener('input', function(e) {
    const query = e.target.value.toLowerCase().trim();
    if (!query) {
        renderCafes(allCafes);
        return;
    }

    const filtered = allCafes.filter(cafe => {
        const nameMatch = cafe.nama_kafe.toLowerCase().includes(query);
        const addressMatch = cafe.alamat.toLowerCase().includes(query);
        return nameMatch || addressMatch;
    });

    renderCafes(filtered);
});

// Load on page load
loadCafes();
</script>
<?= $this->endSection() ?>


