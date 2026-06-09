<?= $this->extend('layout/app') ?>

<?= $this->section('content') ?>

<div class="container-lg py-5">
    <div class="row">
        <div class="col-12">
            <h2 class="fw-bold mb-4">
                <i class="fas fa-music text-danger"></i> Pilih Cafe
            </h2>

            <div class="input-group input-group-lg mb-4">
                <span class="input-group-text bg-light border-0"><i class="fas fa-search"></i></span>
                <input type="text" class="form-control form-control-lg border-0" id="cafeSearch" placeholder="Cari cafe...">
            </div>

            <div id="cafeList" class="row g-4">
                <!-- Load via JS -->
            </div>
        </div>
    </div>
</div>

<script>
// Load cafes
async function loadCafes() {
    try {
        const response = await fetch('/cafe/list');
        const data = await response.json();

        if (data.success) {
            renderCafes(data.data);
        }
    } catch (error) {
        showAlert('Failed to load cafes: ' + error.message, 'danger');
    }
}

function renderCafes(cafes) {
    const container = document.getElementById('cafeList');
    container.innerHTML = '';

    cafes.forEach(cafe => {
        const html = `
            <div class="col-md-6 col-lg-4">
                <div class="card h-100 shadow-sm border-0 cursor-pointer cafe-card" data-cafe-id="${cafe.id}">
                    <div class="card-body">
                        <h5 class="card-title fw-bold">${cafe.nama_kafe}</h5>
                        <p class="card-text text-muted mb-2">
                            <i class="fas fa-map-marker-alt text-danger"></i> ${cafe.alamat}
                        </p>
                        <p class="card-text text-muted mb-2">
                            <i class="fas fa-phone text-danger"></i> ${cafe.phone_number}
                        </p>
                        <a href="/song-request/request?cafe_id=${cafe.id}" class="btn btn-danger btn-sm w-100">
                            <i class="fas fa-play-circle"></i> Request Lagu
                        </a>
                    </div>
                </div>
            </div>
        `;
        container.innerHTML += html;
    });
}

// Search functionality
document.getElementById('cafeSearch').addEventListener('input', function() {
    // TODO: Implement search filter
});

// Load on page load
loadCafes();
</script>

<?= $this->endSection() ?>

