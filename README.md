# SIPKL — Sistem Informasi Praktik Kerja Lapangan

![Status](https://img.shields.io/badge/Status-Production%20Ready-success)
![PHP](https://img.shields.io/badge/PHP-8.0%2B-blue)
![Database](https://img.shields.io/badge/Database-MySQL%2FMariaDB-orange)
![License](https://img.shields.io/badge/License-MIT-green)

**SIPKL** adalah aplikasi manajemen Praktik Kerja Lapangan (PKL) berbasis web yang dirancang khusus untuk Sekolah Menengah Kejuruan (SMK). Aplikasi ini mengelola alur PKL secara *end-to-end* dengan melibatkan 4 peran utama: **Admin**, **Guru Pembimbing**, **Pembimbing DUDI**, dan **Siswa**.

Dibangun dengan antarmuka *Glassmorphism* dan pendekatan *Mobile-First*, SIPKL nyaman digunakan baik di desktop maupun smartphone.

---

## Daftar Isi

- [Fitur Unggulan](#-fitur-unggulan)
- [Stack Teknologi](#-stack-teknologi)
- [Instalasi](#-instalasi)
- [Kredensial Default](#-kredensial-default)
- [Panduan Per Role](#-panduan-penggunaan-per-role)
  - [Admin](#1-admin-pokja-pkl)
  - [Siswa](#2-siswa)
  - [Guru Pembimbing](#3-guru-pembimbing-sekolah)
  - [Pembimbing DUDI](#4-pembimbing-dudi-mitra-industri)
- [Keamanan & Lisensi](#-keamanan--lisensi)

---

## ✨ Fitur Unggulan

| Fitur | Deskripsi |
|---|---|
| 📍 **Absensi Geofencing + Face Recognition** | Validasi kehadiran siswa menggunakan GPS (algoritma Haversine, radius 100m) dan pengenalan wajah secara *client-side* |
| 🗂️ **Multi-Jalur PKL** | Mendukung dua skema PKL: *Proyek Internal* Sekolah dan penempatan ke Perusahaan Mitra (DUDI) |
| 🧑‍💻 **Dashboard Multi-Peran (RBAC)** | Satu aplikasi dengan 4 tampilan berbeda sesuai hak akses masing-masing pengguna |
| 🛡️ **Keamanan Enterprise** | Proteksi CSRF, IDOR, SQL Injection (Prepared Statements), dan enkripsi password BCRYPT |
| 📱 **Mobile-First & UI Modern** | Desain CSS Vanilla tanpa framework berat, animasi *bottom-sheet*, ikon ringan via Lucide |

---

## 🛠️ Stack Teknologi

| Layer | Teknologi |
|---|---|
| Backend | PHP 8.0+ (Native/Vanilla) |
| Database | MySQL / MariaDB |
| Styling | Vanilla CSS3 (Glassmorphism Design System) |
| JavaScript | Vanilla JS + [Face-api.js](https://github.com/justadudewhohacks/face-api.js) |
| Icons | [Lucide SVG Icons](https://lucide.dev/) |

---

## 🚀 Instalasi

### 1. Clone Repository

```bash
git clone https://github.com/yuwaficmuhammad/sipkl.git
cd sipkl/app
```

### 2. Import Database

1. Buat database baru di phpMyAdmin atau MySQL CLI:
   ```sql
   CREATE DATABASE pkl_management;
   ```
2. Import skema tabel:
   ```bash
   mysql -u root -p pkl_management < database/schema.sql
   ```

### 3. Konfigurasi Koneksi

Buka `includes/config.php` dan sesuaikan kredensial database:

```php
$host     = 'localhost';
$user     = 'root';
$password = '';
$database = 'pkl_management';
```

### 4. Jalankan Aplikasi

**Opsi A — XAMPP:** Letakkan folder `app/` di dalam `htdocs/`, lalu akses `http://localhost/app`.

**Opsi B — PHP Built-in Server:**
```bash
php -S localhost:8000
```
Buka `http://localhost:8000` di browser.

---

## 🔐 Kredensial Default

Gunakan akun berikut untuk login pertama kali sebagai Administrator:

| Field | Value |
|---|---|
| Username | `adminpkl` |
| Password | `admin123` |

> ⚠️ **Segera ganti password** setelah login pertama melalui menu Manajemen Pengguna.

Untuk membuat hash password baru:
```bash
php -r "echo password_hash('passwordbaru', PASSWORD_BCRYPT);"
```
Kemudian insert ke database:
```sql
INSERT INTO users (username, password, name, role)
VALUES ('adminpkl', '<HASH_HASIL>', 'Admin Pokja PKL', 'admin');
```

---

## 👥 Panduan Penggunaan Per Role

---

### 1. Admin Pokja PKL 🔴

> Admin adalah pengelola utama sistem. Seluruh konfigurasi awal **wajib** diselesaikan oleh Admin sebelum role lain dapat menggunakan aplikasi.

**Urutan setup yang disarankan:**

1. Login menggunakan akun admin.
2. **Tahun Ajaran** — Buat dan aktifkan tahun ajaran berjalan (contoh: `2026/2027`).
3. **Timeline PKL** — Tentukan batas waktu Gate 1–4 dan periode PKL Eksternal.
4. **Lembaga / GPS Sekolah** — Masukkan koordinat `lat,long` sekolah untuk validasi geofencing absensi internal.
5. **Import Guru** — Upload file Excel data guru pembimbing sekolah.
6. **Import Siswa** — Upload file Excel data siswa PKL.
7. **Manajemen DUDI** — Tambah akun mitra industri beserta koordinat GPS kantor.
8. **Mapping DUDI** — Petakan siswa ke perusahaan mitra dan tentukan guru pembimbingnya.
9. **Proyek Internal** — Buat proyek internal, assign tim siswa, dan tentukan ketua tim.
10. **Rekap Absensi** — Pantau dan verifikasi kehadiran seluruh siswa.
11. **Export Data** — Unduh rekap absensi atau penilaian dalam format Excel/CSV.
12. **Arsip** — Akses dan kelola data dari tahun ajaran yang sudah selesai.

---

### 2. Siswa 🟢

> Siswa menggunakan aplikasi setiap hari untuk mencatat kehadiran dan kegiatan harian.

**Alur penggunaan harian:**

1. Login menggunakan username dan password yang diberikan Admin.
2. **Dashboard** — Cek notifikasi deadline Gate aktif dan status penempatan PKL.
3. **Absensi Datang** — Buka menu *Absensi*, tekan tombol **Datang**:
   - Izinkan akses **Kamera** (untuk validasi wajah).
   - Izinkan akses **Lokasi/GPS** (validasi radius 100m dari titik sekolah/perusahaan).
   - Waktu datang tercatat otomatis jika validasi berhasil.
4. **Absensi Pulang** — Ulangi langkah di atas dengan tombol **Pulang**.
5. **Logbook Harian** — Buka menu *Logbook*, isi catatan kegiatan hari ini sebelum pulang.
6. **Rekap Absensi** — Lihat riwayat kehadiran pribadi (Hadir / Sakit / Izin / Alpha).
7. **Sertifikat** — Unduh sertifikat PKL setelah program selesai (jika sudah tersedia).

> ⚠️ Pastikan izin **Kamera** dan **Lokasi** sudah diaktifkan di browser sebelum melakukan absensi.

---

### 3. Guru Pembimbing Sekolah 🔵

> Guru memantau dan memvalidasi aktivitas siswa yang menjadi tanggung jawabnya.

**Menu yang tersedia:**

1. Login menggunakan akun yang di-import oleh Admin.
2. **Dashboard** — Lihat ringkasan siswa bimbingan dan pengingat timeline Gate.
3. **Rekap Absensi** — Pantau kehadiran per siswa bimbingan secara detail (jam datang, jam pulang, foto, koordinat GPS).
4. **Logbook Siswa** — Baca dan verifikasi catatan kegiatan harian setiap siswa.
5. **Gate Proyek** — Monitor progres Gate 1–4 proyek internal siswa bimbingan dan lihat dokumen yang sudah diupload.
6. **Profil** — Perbarui data diri (foto, nomor kontak, alamat).

---

### 4. Pembimbing DUDI (Mitra Industri) 🟠

> Pembimbing dari perusahaan mitra memantau kehadiran dan memberikan penilaian akhir siswa magang.

**Menu yang tersedia:**

1. Login menggunakan akun yang dibuat Admin (role: `pembimbing_dudika`).
2. **Dashboard** — Lihat daftar siswa yang magang di perusahaan Anda.
3. **Rekap Absensi** — Pantau kehadiran siswa magang (jam datang, jam pulang, foto selfie, koordinat lokasi).
4. **Penilaian Akhir** — Isi penilaian untuk setiap siswa:
   - **Softskill** (sikap, kedisiplinan, komunikasi) — skala 0–100.
   - **Hardskill** (kompetensi teknis) — skala 0–100.
   - Tambahkan catatan atau rekomendasi jika diperlukan.
5. **Profil Perusahaan** — Pastikan data berikut sudah benar:
   - Nama pimpinan dan instruktur perusahaan.
   - Nomor kontak instruktur.
   - Koordinat GPS kantor *(digunakan sebagai pusat validasi lokasi absensi siswa magang)*.

> 💡 Koordinat GPS perusahaan yang akurat memastikan validasi absensi siswa PKL Eksternal berjalan dengan benar.

---

## 🛡️ Keamanan & Lisensi

Aplikasi ini dikembangkan dengan prinsip *Secure Coding* untuk kebutuhan edukasi dan implementasi sistem sekolah yang aman.

Dilisensikan di bawah **MIT License** — bebas untuk di-*fork*, dimodifikasi, dan dikontribusikan.
