-- ========================================
-- TABEL UTAMA (harus dibuat pertama)
-- ========================================

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `users` (
  `id` int NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `name` varchar(100) NOT NULL,
  `role` enum('admin','siswa','pembimbing_sekolah','pembimbing_dudika') NOT NULL,
  `jurusan` varchar(50) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `tahun_ajaran` varchar(20) DEFAULT NULL,
  `alamat` text,
  `kontak` varchar(50) DEFAULT NULL,
  `foto` varchar(255) DEFAULT NULL,
  `dudi_nama_pimpinan` varchar(100) DEFAULT NULL,
  `dudi_nama_instruktur` varchar(100) DEFAULT NULL,
  `dudi_nomor_instruktur` varchar(50) DEFAULT NULL,
  `dudi_latlong` varchar(100) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`),
  KEY `idx_role` (`role`),
  KEY `idx_jurusan` (`jurusan`),
  KEY `idx_tahun_ajaran` (`tahun_ajaran`),
  KEY `idx_role_jurusan_ta` (`role`,`jurusan`,`tahun_ajaran`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

-- ========================================
-- TABEL REFERENSI USERS
-- ========================================

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `absensi_siswa` (
  `id` int NOT NULL AUTO_INCREMENT,
  `id_siswa` int NOT NULL,
  `tanggal` date NOT NULL,
  `tipe_pkl` enum('internal','eksternal') NOT NULL,
  `waktu_datang` time DEFAULT NULL,
  `foto_datang` varchar(255) DEFAULT NULL,
  `latlong_datang` varchar(100) DEFAULT NULL,
  `waktu_pulang` time DEFAULT NULL,
  `foto_pulang` varchar(255) DEFAULT NULL,
  `latlong_pulang` varchar(100) DEFAULT NULL,
  `is_wajah_valid` tinyint(1) DEFAULT NULL,
  `is_lokasi_valid` tinyint(1) DEFAULT NULL,
  `jarak_meter` int DEFAULT NULL,
  `status` enum('Hadir','Sakit','Izin','Alpha') DEFAULT 'Hadir',
  `keterangan` text,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unik_absen` (`id_siswa`,`tanggal`),
  CONSTRAINT `absensi_siswa_ibfk_1` FOREIGN KEY (`id_siswa`) REFERENCES `users` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `jurnal_siswa` (
  `id` int NOT NULL AUTO_INCREMENT,
  `id_siswa` int NOT NULL,
  `tanggal` date NOT NULL,
  `kegiatan` text NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `logbook_internal` (
  `id` int NOT NULL AUTO_INCREMENT,
  `id_siswa` int NOT NULL,
  `tanggal` date NOT NULL,
  `jam_masuk` time NOT NULL,
  `jam_pulang` time NOT NULL,
  `catatan_apel` text,
  `catatan_instruktur` text,
  `sesi_1` text,
  `sesi_2` text,
  `sesi_3` text,
  `kendala` text,
  `rencana_besok` text,
  `is_verified` tinyint(1) DEFAULT '0',
  `id_verifier` int DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `id_verifier` (`id_verifier`),
  KEY `idx_siswa_tanggal` (`id_siswa`,`tanggal`),
  KEY `idx_is_verified` (`is_verified`),
  CONSTRAINT `logbook_internal_ibfk_1` FOREIGN KEY (`id_siswa`) REFERENCES `users` (`id`),
  CONSTRAINT `logbook_internal_ibfk_2` FOREIGN KEY (`id_verifier`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `notifikasi` (
  `id` int NOT NULL AUTO_INCREMENT,
  `id_user` int DEFAULT NULL COMMENT 'Null jika global untuk role',
  `target_role` enum('admin','siswa','pembimbing_sekolah','pembimbing_dudika','all') NOT NULL,
  `judul` varchar(100) NOT NULL,
  `pesan` text NOT NULL,
  `is_read` tinyint(1) DEFAULT '0',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_target_role` (`target_role`),
  KEY `idx_user_read` (`id_user`,`is_read`),
  CONSTRAINT `notifikasi_ibfk_1` FOREIGN KEY (`id_user`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `penempatan_dudi` (
  `id` int NOT NULL AUTO_INCREMENT,
  `id_siswa` int NOT NULL,
  `id_dudika` int NOT NULL,
  `id_pembimbing_sekolah` int DEFAULT NULL,
  `tahun_ajaran` varchar(20) NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `id_siswa` (`id_siswa`),
  KEY `idx_dudi_ta` (`id_dudika`,`tahun_ajaran`),
  KEY `id_pembimbing_sekolah` (`id_pembimbing_sekolah`),
  CONSTRAINT `penempatan_dudi_ibfk_1` FOREIGN KEY (`id_siswa`) REFERENCES `users` (`id`),
  CONSTRAINT `penempatan_dudi_ibfk_2` FOREIGN KEY (`id_dudika`) REFERENCES `users` (`id`),
  CONSTRAINT `penempatan_dudi_ibfk_3` FOREIGN KEY (`id_pembimbing_sekolah`) REFERENCES `users` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `penilaian_dudi` (
  `id` int NOT NULL AUTO_INCREMENT,
  `id_siswa` int NOT NULL,
  `id_dudika` int NOT NULL,
  `nilai_softskill` int DEFAULT '0',
  `nilai_hardskill` int DEFAULT '0',
  `catatan_industri` text,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_siswa_dudi` (`id_siswa`,`id_dudika`),
  KEY `id_dudika` (`id_dudika`),
  CONSTRAINT `penilaian_dudi_ibfk_1` FOREIGN KEY (`id_siswa`) REFERENCES `users` (`id`),
  CONSTRAINT `penilaian_dudi_ibfk_2` FOREIGN KEY (`id_dudika`) REFERENCES `users` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `presensi_internal` (
  `id` int NOT NULL AUTO_INCREMENT,
  `id_siswa` int NOT NULL,
  `tanggal` date NOT NULL,
  `status` enum('H','A','I','S') NOT NULL,
  `keterangan` varchar(100) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_siswa` (`id_siswa`),
  KEY `idx_tanggal` (`tanggal`),
  CONSTRAINT `presensi_internal_ibfk_1` FOREIGN KEY (`id_siswa`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `proyek_internal` (
  `id` int NOT NULL AUTO_INCREMENT,
  `kode_proyek` varchar(50) NOT NULL,
  `nama_klien` varchar(100) NOT NULL,
  `judul_proyek` varchar(150) NOT NULL,
  `id_pembimbing_sekolah` int NOT NULL,
  `status_gate` int DEFAULT '0' COMMENT '0: Belum Mulai, 1-4: Lulus Gate',
  `doc_gate_1` varchar(255) DEFAULT NULL,
  `doc_gate_2` varchar(255) DEFAULT NULL,
  `doc_gate_3` varchar(255) DEFAULT NULL,
  `doc_gate_4` varchar(255) DEFAULT NULL,
  `is_remedial` tinyint(1) DEFAULT '0',
  `tahun_ajaran` varchar(20) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `kode_proyek` (`kode_proyek`),
  KEY `idx_ta` (`tahun_ajaran`),
  KEY `idx_guru_ta` (`id_pembimbing_sekolah`,`tahun_ajaran`),
  KEY `idx_status_gate` (`status_gate`),
  CONSTRAINT `proyek_internal_ibfk_1` FOREIGN KEY (`id_pembimbing_sekolah`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `settings` (
  `setting_key` varchar(50) NOT NULL,
  `setting_value` varchar(255) NOT NULL,
  PRIMARY KEY (`setting_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `tahun_ajaran` (
  `id` int NOT NULL AUTO_INCREMENT,
  `nama` varchar(20) NOT NULL,
  `is_active` tinyint(1) DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `nama` (`nama`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `tim_proyek` (
  `id_proyek` int NOT NULL,
  `id_siswa` int NOT NULL,
  `is_ketua` tinyint(1) DEFAULT '0',
  PRIMARY KEY (`id_proyek`,`id_siswa`),
  KEY `id_siswa` (`id_siswa`),
  CONSTRAINT `tim_proyek_ibfk_1` FOREIGN KEY (`id_proyek`) REFERENCES `proyek_internal` (`id`),
  CONSTRAINT `tim_proyek_ibfk_2` FOREIGN KEY (`id_siswa`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;


-- ========================================
-- DATA DEFAULT (wajib diisi saat instalasi)
-- ========================================

INSERT INTO tahun_ajaran (nama, is_active) VALUES ('2025/2026', 0), ('2026/2027', 1);

INSERT INTO settings (setting_key, setting_value) VALUES 
('gate_1', '2026-08-31'),
('gate_2', '2026-09-30'),
('gate_3', '2026-10-31'),
('gate_4', '2026-11-30'),
('pkl_eks_start', '2026-12-01'),
('pkl_eks_end', '2027-02-18'),
('sekolah_nama', 'SMK Salafiyah Pati'),
('sekolah_alamat', 'Kajen, Margoyoso, Pati, Jawa Tengah'),
('sekolah_latlong', '-6.6669,111.0263');

-- Password default: admin123
-- Ganti hash sesuai kebutuhan menggunakan: php -r "echo password_hash('admin123', PASSWORD_BCRYPT);"
-- INSERT INTO users (username, password, name, role) VALUES ('adminpkl', '<BCRYPT_HASH>', 'Admin Pokja PKL', 'admin');
