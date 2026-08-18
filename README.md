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

## 🛡️ Keamanan & Lisensi

Aplikasi ini dikembangkan dengan kaidah *Secure Coding* dan diinisiasi untuk kebutuhan edukasi serta implementasi sistem sekolah yang aman.

Dilisensikan di bawah **MIT License**. Jangan ragu untuk melakukan *fork*, modifikasi, maupun kontribusi!
