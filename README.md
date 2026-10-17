<div align="center">

# 🎵 Sistem Request Lagu
### Berbasis Web dan Android

**Rancang Bangun Sistem Request Lagu Berbasis Web dan Android**  
**Menggunakan Algoritma Priority Queue dan FIFO di Kafe Small Space**

---

| | |
|---|---|
| **Nama** | Ahmad Ikhsan Setiaji |
| **NIM** | 5230411082 |

---

*Skripsi / Tugas Akhir*

</div>

---

## 📋 Deskripsi Sistem

Sistem Request Lagu Digital adalah aplikasi berbasis Web dan Android yang dikembangkan untuk Kafe Small Space Yogyakarta. Sistem ini menggantikan proses request lagu manual (via operator, WhatsApp, atau kertas) menjadi sistem digital yang transparan dan terkelola.

**Permasalahan yang diselesaikan:**
- Antrean lagu tidak transparan
- Proses manual sulit dikelola
- Lagu sering terlewat atau tidak tercatat

**Solusi:**
- Request lagu secara online via Web & Android
- Pembayaran digital terintegrasi
- Algoritma **Priority Queue** — lagu dengan nominal lebih tinggi tampil lebih awal
- Algoritma **FIFO** — antrean normal berdasarkan waktu request
- Dashboard admin kafe untuk kelola antrean secara real-time

---

## 🛠️ Teknologi

| Komponen | Teknologi |
|---|---|
| Backend & Web | CodeIgniter 4 (PHP 8.2+) |
| Database | PostgreSQL |
| Frontend | Bootstrap 5, Vanilla JS |
| Mobile | Android |
| API Musik | Spotify Web API |
| Pembayaran | Midtrans |

---

## ⚙️ Instalasi

### Prasyarat
- PHP 8.2+, ekstensi: `intl`, `mbstring`, `json`, `mysqlnd`, `curl`
- PostgreSQL
- Composer

### Langkah

```bash
# 1. Clone repository
git clone <url-repo>
cd song-request

# 2. Install dependensi
composer install

# 3. Konfigurasi environment
cp env .env
# Edit .env: isi baseURL, database, Spotify API key, Midtrans key,
# serta RESEND_API_KEY, RESEND_FROM_EMAIL, dan TESTMAIL_INBOX_EMAIL

# 4. Jalankan migrasi
php spark migrate

# 5. Jalankan server
php spark serve
```

Akses di `http://localhost:8080`

### Email notification setup
- Daftarkan Resend dan verifikasi domain/sender yang akan dipakai sebagai `RESEND_FROM_EMAIL`.
- Isi `RESEND_API_KEY` di `.env` dengan API key Resend.
- Jika pakai XAMPP/Windows dan muncul error SSL, set `RESEND_VERIFY_SSL = false` untuk development.
- Untuk testing, ubah email akun user di database menjadi alamat inbox Testmail yang aktif.
- Gunakan tombol **Kirim Email Percobaan** di halaman profil user untuk mengirim email test ke inbox tersebut.
- Jika domain belum terverifikasi di Resend, gunakan sender dari domain yang sudah diverifikasi atau alamat dev yang diizinkan Resend untuk environment development.

---

## 👤 Peran Pengguna

| Role | Akses |
|---|---|
| **User** | Request lagu, lihat antrean, pembayaran |
| **Admin Kafe** | Kelola antrean, dashboard kafe, withdrawal |
| **Superadmin** | Verifikasi admin kafe, kelola semua cafe, transaksi |

> Admin kafe baru harus diverifikasi oleh superadmin sebelum bisa login.

---

## 📁 Struktur Direktori

```
app/
├── Controllers/     # AuthController, AdminController, SuperadminController, dst.
├── Models/          # UserModel, CafeModel, SongModel, dst.
├── Views/           # Tampilan per role (admin/, superadmin/, user/, auth/)
├── Services/        # AuthService, WithdrawalService, SpotifyService
├── Libraries/       # TabSessionManager
├── Filters/         # AuthFilter, AdminFilter, SuperadminFilter
└── Config/          # Routes, Filters, Database
public/
└── assets/          # CSS, JS, gambar
writable/
└── logs/            # Log aplikasi
```

---

## 🔑 Algoritma Antrian

```
Request masuk
     │
     ├─► Priority Queue (nominal > 0)
     │       Urutan: nominal DESC → waktu ASC
     │
     └─► FIFO (nominal = 0 / gratis)
             Urutan: waktu ASC

Ambil lagu berikutnya:
  → Cek Priority Queue dulu
  → Jika kosong, ambil dari FIFO
```

---

<div align="center">
<sub>Dikembangkan untuk keperluan Skripsi — Program Studi Teknik Informatika</sub>
</div>
