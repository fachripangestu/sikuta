# SIKUTA

SIKUTA adalah Sistem Informasi Kunjungan Tamu Inspektorat DKI Jakarta. Aplikasi ini menerima permohonan kunjungan, mendukung verifikasi dan disposisi oleh petugas, serta mengelola surat balasan.

## Menjalankan secara lokal

Siapkan PHP 8.3 dengan ekstensi `pdo_mysql`, Composer 2, MySQL atau MariaDB, dan Node.js 24 dengan npm. Di Laragon, jalankan MySQL terlebih dahulu.

1. Buat database kosong bernama `sikuta`. Dari folder proyek, jalankan perintah berikut di PowerShell.

   ```powershell
   composer install
   Copy-Item .env.example .env
   php artisan key:generate
   ```

2. Isi `DB_*` sesuai koneksi MySQL lokal di `.env`. Isi `ADMIN_PASSWORD` dengan sandi yang kuat dan unik untuk akun awal. Anda dapat menyesuaikan `ADMIN_USERNAME` dan `ADMIN_EMAIL` di berkas yang sama.

3. Siapkan database, tautan penyimpanan, dan aset frontend.

   ```powershell
   php artisan migrate --seed
   php artisan storage:link
   npm ci
   npm run build
   php artisan serve
   ```

Formulir tamu tersedia di `http://127.0.0.1:8000/` dan login petugas di `http://127.0.0.1:8000/admin/login`. Buat akun petugas tambahan melalui halaman **Kelola Akun** setelah login sebagai admin. Jika menggunakan halaman lama `/tamu-login.php`, atur `GUEST_LOGIN_CODE` di `.env`.

## Pengujian

Aktifkan ekstensi PHP `pdo_sqlite` dan jalankan `php artisan test`. Pengujian memakai SQLite dalam memori. GitHub Actions menjalankan validasi Composer, build aset, dan pengujian saat ada push ke `main` atau pull request.
