# 🚀 SIPKL (Sistem Informasi Praktik Kerja Lapangan)

![SIPKL Banner](https://img.shields.io/badge/Status-Production%20Ready-success)
![PHP Version](https://img.shields.io/badge/PHP-8.0%2B-blue)
![Database](https://img.shields.io/badge/Database-MySQL%2FMariaDB-orange)
![License](https://img.shields.io/badge/License-MIT-green)

**SIPKL** adalah sebuah aplikasi manajemen Praktik Kerja Lapangan (PKL) berbasis web yang dirancang khusus untuk Sekolah Menengah Kejuruan (SMK) modern. Aplikasi ini memfasilitasi komunikasi dan pengelolaan data *End-to-End* yang melibatkan 4 pilar utama: **Admin Pokja Sekolah**, **Guru Pembimbing**, **Mitra Industri (DUDI)**, dan **Siswa**.

Dengan antarmuka bergaya *Glassmorphism* yang memanjakan mata dan *Mobile-First Design*, SIPKL sangat mudah digunakan baik melalui Desktop maupun *Smartphone*.

---

## ✨ Fitur Unggulan

- 📍 **Absensi Cerdas dengan Geofencing & Face Recognition**
  Mendukung Absensi Datang/Pulang bagi Siswa. Sistem divalidasi dengan algoritma Haversine (pembatasan radius lokasi GPS 100m) untuk *Proyek Internal*, dan validasi wajah (*Face API*) secara langsung (*client-side*).
- 🗂️ **Manajemen Multi-PKL (Internal & Eksternal)**
  Dukungan alur PKL untuk penugasan *Proyek Internal* Sekolah maupun pemetaan (*mapping*) ke Perusahaan Eksternal (Mitra DUDI).
- 🧑‍💻 **Dashboard Multi-Peran Terpadu**
  Satu aplikasi, 4 hak akses (*Role Base Access Control*):
  - **Admin**: Konfigurasi timeline, master data siswa/guru/DUDI, pengaturan GPS sekolah.
  - **Siswa**: Laporan absensi dan input Jurnal/Logbook kegiatan harian.
  - **Guru Pembimbing**: Pemantauan absensi, validasi jurnal, dan *monitoring* nilai siswa bimbingannya.
  - **Pembimbing DUDI**: Memantau anak magang dan melakukan Penilaian Akhir (Softskill & Hardskill).
- 🛡️ **Keamanan Kelas Enterprise**
  Terlindungi dari kerentanan *Cross-Site Request Forgery* (CSRF) via *Token Validation*, *Insecure Direct Object Reference* (IDOR), *SQL Injection* (via MySQLi Prepared Statements), dan dilengkapi *Hashing* BCRYPT.
- 📱 **Mobile-First & UI Modern**
  Desain estetis ala aplikasi modern (tanpa *border-radius* membosankan) menggunakan CSS Vanilla, meminimalisir ketergantungan *library* pihak ketiga. Animasi interaktif (Modal *Bottom-Sheet*) dan ikon vektor super ringan via [Lucide Icons](https://lucide.dev/).

---

## 🛠️ Stack Teknologi Terapan

- **Bahasa Utama**: PHP 8.0+ (Native/Vanilla)
- **Database**: MySQL / MariaDB
- **Styling**: Vanilla CSS3 (Custom Design System, Glassmorphism)
- **JavaScript**: Vanilla JS, [Face-api.js](https://github.com/justadudewhohacks/face-api.js) (Deteksi Wajah Klien)
- **Ikonografi**: Lucide SVG Icons

---

## 🚀 Cara Instalasi & Penggunaan

1. **Clone Repository**
   ```bash
   git clone https://github.com/yuwaficmuhammad/sipkl.git
   cd sipkl/app
   ```
2. **Impor Database**
   - Buat database baru bernama `pkl_management` di phpMyAdmin / MySQL CLI.
   - Impor berkas struktur tabel dari `database/schema.sql` ke dalam database tersebut.
3. **Konfigurasi**
   - Buka file `includes/config.php`.
   - Sesuaikan kredensial *host*, *username*, dan *password* koneksi *database* MySQL Anda.
4. **Jalankan Aplikasi**
   - Anda bisa meletakkan *folder* ini di dalam `htdocs` (jika menggunakan XAMPP) atau menjalankan server internal PHP:
     ```bash
     php -S localhost:8000
     ```
   - Buka `http://localhost:8000` di peramban web Anda.

---

## 🔐 Kredensial Default (Admin)

Untuk memulai pengaturan awal (*setup*), silakan masuk menggunakan akun Administrator bawaan:

- **Username**: `adminpkl`
- **Password**: `admin123`

*(Sangat disarankan untuk segera mengubah sandi ini pada antarmuka manajemen pengguna setelah instalasi).*

---

## 📸 Tangkapan Layar (Screenshots)

*(Tangkapan layar dapat ditambahkan di sini oleh *developer* untuk memamerkan antarmuka Dasbor, Modal interaktif, dan halaman validasi Wajah/GPS).*

---

## 👥 Panduan Penggunaan Per Role

### 🔴 1. Admin Pokja PKL

Admin adalah pengelola utama sistem. Semua konfigurasi awal **wajib** diselesaikan oleh Admin sebelum role lain dapat menggunakan aplikasi.

**Langkah-langkah:**

1. **Login** menggunakan akun admin (`adminpkl` / `admin123`, segera ganti setelah login pertama).
2. **Kelola Tahun Ajaran** → Menu *Tahun Ajaran*: Buat tahun ajaran aktif (contoh: `2026/2027`) dan aktifkan.
3. **Atur Timeline PKL** → Menu *Timeline*: Tentukan batas waktu Gate 1–4, tanggal mulai & selesai PKL Eksternal.
4. **Atur Lokasi Sekolah (GPS)** → Menu *Lembaga*: Masukkan koordinat `lat,long` sekolah untuk validasi Geofencing absensi internal.
5. **Import Data Guru** → Menu *Import Guru*: Upload file Excel data guru pembimbing sekolah.
6. **Import Data Siswa** → Menu *Import Siswa*: Upload file Excel data siswa PKL.
7. **Kelola Akun DUDI** → Menu *Manajemen DUDI*: Tambah akun mitra industri beserta data lokasi GPS perusahaan.
8. **Mapping Siswa ke DUDI** → Menu *Mapping DUDI*: Petakan siswa ke perusahaan mitra dan tentukan guru pembimbingnya.
9. **Kelola Proyek Internal** → Menu *Proyek Internal*: Buat proyek internal dan assign tim siswa beserta ketua tim.
10. **Pantau Absensi** → Menu *Rekap Absensi*: Lihat rekapitulasi kehadiran seluruh siswa.
11. **Export Data** → Menu *Export*: Unduh data absensi/penilaian dalam format Excel/CSV.
12. **Kelola Arsip** → Menu *Arsip*: Akses data tahun ajaran yang sudah selesai.

---

### 🟢 2. Siswa

Siswa menggunakan aplikasi setiap hari untuk absensi dan pencatatan kegiatan.

**Langkah-langkah:**

1. **Login** menggunakan username & password yang diberikan oleh Admin.
2. **Cek Dashboard** → Lihat notifikasi timeline (Gate aktif, pengingat deadline) dan status penempatan PKL.
3. **Absensi Datang** → Menu *Absensi* → Tombol **Datang**:
   - Aplikasi akan mengakses **kamera** untuk validasi wajah (Face Recognition).
   - Aplikasi akan mengakses **GPS** untuk validasi lokasi (radius 100m dari sekolah/perusahaan).
   - Jika valid, waktu datang tercatat otomatis.
4. **Absensi Pulang** → Lakukan hal yang sama dengan tombol **Pulang** saat hendak pulang.
5. **Isi Jurnal/Logbook Harian** → Menu *Logbook*: Catat kegiatan yang dikerjakan hari ini sebelum pulang.
6. **Pantau Rekap Absensi** → Lihat riwayat kehadiran pribadi (Hadir, Sakit, Izin, Alpha).
7. **Unduh Sertifikat** → Menu *Sertifikat*: Unduh sertifikat PKL setelah program selesai (jika tersedia).

> ⚠️ Pastikan izin **Kamera** dan **Lokasi** sudah diberikan di browser sebelum melakukan absensi.

---

### 🔵 3. Guru Pembimbing Sekolah

Guru memantau dan memvalidasi aktivitas siswa yang menjadi tanggung jawabnya.

**Langkah-langkah:**

1. **Login** menggunakan akun yang di-import oleh Admin.
2. **Cek Dashboard** → Lihat ringkasan siswa bimbingan dan notifikasi timeline Gate.
3. **Pantau Absensi Siswa** → Menu *Rekap Absensi*: Lihat detail kehadiran setiap siswa bimbingan (waktu datang, pulang, foto, koordinat GPS).
4. **Validasi Logbook** → Menu *Logbook Siswa*: Baca dan verifikasi catatan kegiatan harian siswa.
5. **Monitor Gate Proyek** → Menu *Gate Proyek*: Pantau progres gate proyek internal siswa bimbingan (Gate 1–4) dan lihat dokumen yang diupload.
6. **Lihat Profil** → Perbarui data profil (foto, kontak, alamat) melalui menu Profil.

---

### 🟠 4. Pembimbing DUDI (Mitra Industri)

Pembimbing dari perusahaan memantau kehadiran dan memberikan penilaian akhir siswa magang.

**Langkah-langkah:**

1. **Login** menggunakan akun yang dibuat oleh Admin (akun khusus role `pembimbing_dudika`).
2. **Cek Dashboard** → Lihat daftar siswa magang di perusahaan Anda.
3. **Pantau Absensi Siswa** → Menu *Rekap Absensi*: Lihat kehadiran siswa di perusahaan (waktu datang, pulang, foto selfie, koordinat lokasi).
4. **Berikan Penilaian Akhir** → Menu *Penilaian*:
   - Isi nilai **Softskill** (sikap, kedisiplinan, komunikasi) — skala 0–100.
   - Isi nilai **Hardskill** (kompetensi teknis) — skala 0–100.
   - Tambahkan catatan/rekomendasi untuk siswa.
5. **Perbarui Profil** → Pastikan data perusahaan (nama pimpinan, nama instruktur, nomor kontak, koordinat GPS kantor) sudah benar agar validasi absensi siswa berjalan akurat.

> 💡 Koordinat GPS perusahaan digunakan sebagai pusat validasi lokasi absensi siswa PKL Eksternal.

---

## 🛡️ Keamanan & Lisensi

Aplikasi ini dikembangkan dengan kaidah *Secure Coding* dan diinisiasi untuk kebutuhan edukasi serta implementasi sistem sekolah yang aman.

Dilisensikan di bawah **MIT License**. Jangan ragu untuk melakukan *fork*, modifikasi, maupun kontribusi!

