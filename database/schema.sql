-- Skema Database Aplikasi PKL SMK Salafiyah Pati

CREATE DATABASE IF NOT EXISTS pkl_management;
USE pkl_management;

CREATE TABLE `users` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `username` VARCHAR(50) NOT NULL UNIQUE,
  `password` VARCHAR(255) NOT NULL,
  `name` VARCHAR(100) NOT NULL,
  `role` ENUM('admin', 'siswa', 'pembimbing_sekolah', 'pembimbing_dudika') NOT NULL,
  `jurusan` VARCHAR(50) DEFAULT NULL,
  `tahun_ajaran` VARCHAR(20) DEFAULT NULL,
  `alamat` TEXT DEFAULT NULL,
  `kontak` VARCHAR(50) DEFAULT NULL,
  `foto` VARCHAR(255) DEFAULT NULL,
  `dudi_nama_pimpinan` VARCHAR(100) DEFAULT NULL,
  `dudi_nama_instruktur` VARCHAR(100) DEFAULT NULL,
  `dudi_nomor_instruktur` VARCHAR(50) DEFAULT NULL,
  `dudi_latlong` VARCHAR(100) DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE settings (
    setting_key VARCHAR(50) PRIMARY KEY,
    setting_value VARCHAR(255) NOT NULL
);

INSERT INTO settings (setting_key, setting_value) VALUES 
('gate_1', '2026-08-31'),
('gate_2', '2026-09-30'),
('gate_3', '2026-10-31'),
('gate_4', '2026-11-30'),
('pkl_eks_start', '2026-12-01'),
('pkl_eks_end', '2027-02-18');

CREATE TABLE tahun_ajaran (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nama VARCHAR(20) NOT NULL UNIQUE,
    is_active TINYINT(1) DEFAULT 0
);

INSERT INTO tahun_ajaran (nama, is_active) VALUES ('2025/2026', 0), ('2026/2027', 1);

CREATE TABLE `proyek_internal` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `kode_proyek` VARCHAR(50) NOT NULL UNIQUE,
  `nama_klien` VARCHAR(100) NOT NULL,
  `judul_proyek` VARCHAR(150) NOT NULL,
  `id_pembimbing_sekolah` INT NOT NULL,
  `status_gate` INT DEFAULT 0 COMMENT '0: Belum Mulai, 1-4: Lulus Gate',
  `doc_gate_1` VARCHAR(255) DEFAULT NULL,
  `doc_gate_2` VARCHAR(255) DEFAULT NULL,
  `doc_gate_3` VARCHAR(255) DEFAULT NULL,
  `doc_gate_4` VARCHAR(255) DEFAULT NULL,
  `is_remedial` BOOLEAN DEFAULT FALSE,
  `tahun_ajaran` VARCHAR(20) DEFAULT NULL,
  FOREIGN KEY (`id_pembimbing_sekolah`) REFERENCES `users`(`id`)
);

CREATE TABLE `tim_proyek` (
  `id_proyek` INT NOT NULL,
  `id_siswa` INT NOT NULL,
  `is_ketua` BOOLEAN DEFAULT FALSE,
  PRIMARY KEY (`id_proyek`, `id_siswa`),
  FOREIGN KEY (`id_proyek`) REFERENCES `proyek_internal`(`id`),
  FOREIGN KEY (`id_siswa`) REFERENCES `users`(`id`)
);

CREATE TABLE `logbook_internal` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `id_siswa` INT NOT NULL,
  `tanggal` DATE NOT NULL,
  `jam_masuk` TIME NOT NULL,
  `jam_pulang` TIME NOT NULL,
  `catatan_apel` TEXT,
  `catatan_instruktur` TEXT,
  `sesi_1` TEXT,
  `sesi_2` TEXT,
  `sesi_3` TEXT,
  `kendala` TEXT,
  `rencana_besok` TEXT,
  `is_verified` BOOLEAN DEFAULT FALSE,
  `id_verifier` INT DEFAULT NULL,
  FOREIGN KEY (`id_siswa`) REFERENCES `users`(`id`),
  FOREIGN KEY (`id_verifier`) REFERENCES `users`(`id`)
);

CREATE TABLE `presensi_internal` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `id_siswa` INT NOT NULL,
  `tanggal` DATE NOT NULL,
  `status` ENUM('H', 'A', 'I', 'S') NOT NULL,
  `keterangan` VARCHAR(100) DEFAULT NULL,
  FOREIGN KEY (`id_siswa`) REFERENCES `users`(`id`)
);

CREATE TABLE `penempatan_dudi` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `id_siswa` INT NOT NULL UNIQUE,
  `id_dudika` INT NOT NULL,
  `id_pembimbing_sekolah` INT DEFAULT NULL,
  `tahun_ajaran` VARCHAR(20) NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`id_siswa`) REFERENCES `users`(`id`),
  FOREIGN KEY (`id_dudika`) REFERENCES `users`(`id`),
  FOREIGN KEY (`id_pembimbing_sekolah`) REFERENCES `users`(`id`)
);

CREATE TABLE `penilaian_dudi` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `id_siswa` INT NOT NULL,
  `id_dudika` INT NOT NULL,
  `nilai_softskill` INT DEFAULT 0,
  `nilai_hardskill` INT DEFAULT 0,
  `catatan_industri` TEXT,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (`id_siswa`) REFERENCES `users`(`id`),
  FOREIGN KEY (`id_dudika`) REFERENCES `users`(`id`)
);

CREATE TABLE `notifikasi` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `id_user` INT DEFAULT NULL COMMENT 'Null jika global untuk role',
  `target_role` ENUM('admin', 'siswa', 'pembimbing_sekolah', 'pembimbing_dudika', 'all') NOT NULL,
  `judul` VARCHAR(100) NOT NULL,
  `pesan` TEXT NOT NULL,
  `is_read` BOOLEAN DEFAULT FALSE,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`id_user`) REFERENCES `users`(`id`)
);

-- CATATAN KEAMANAN: Passwords disimpan sebagai bcrypt hash (password_hash PHP)
-- Untuk generate hash: php -r "echo password_hash('admin123', PASSWORD_BCRYPT);"
-- Jalankan script seed.php atau admin panel untuk menambah user setelah instalasi.

-- Insert Admin Default (generate hash terlebih dahulu, lalu ganti di bawah)
-- Contoh hash untuk 'admin123':
-- INSERT INTO `users` (`username`, `password`, `name`, `role`) VALUES
-- ('adminpkl', '$2y$12$HASH_DISINI', 'Admin Pokja PKL', 'admin');CREATE TABLE `absensi_siswa` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `id_siswa` INT NOT NULL,
  `tanggal` DATE NOT NULL,
  `tipe_pkl` ENUM('internal', 'eksternal') NOT NULL,
  `waktu_datang` TIME DEFAULT NULL,
  `foto_datang` VARCHAR(255) DEFAULT NULL,
  `latlong_datang` VARCHAR(100) DEFAULT NULL,
  `waktu_pulang` TIME DEFAULT NULL,
  `foto_pulang` VARCHAR(255) DEFAULT NULL,
  `latlong_pulang` VARCHAR(100) DEFAULT NULL,
  `is_wajah_valid` BOOLEAN DEFAULT NULL,
  `is_lokasi_valid` BOOLEAN DEFAULT NULL,
  `jarak_meter` INT DEFAULT NULL,
  `status` ENUM('Hadir', 'Sakit', 'Izin', 'Alpha') DEFAULT 'Hadir',
  `keterangan` TEXT DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`id_siswa`) REFERENCES `users`(`id`),
  UNIQUE KEY `unik_absen` (`id_siswa`, `tanggal`)
);
