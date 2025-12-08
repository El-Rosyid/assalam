/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;
DROP TABLE IF EXISTS `academic_year`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `academic_year` (
  `tahun_ajaran_id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `year` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `semester` varchar(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '0',
  `pembagian_raport` date NOT NULL,
  `tanggal_penerimaan_raport` date DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`tahun_ajaran_id`),
  UNIQUE KEY `academic_year_year_unique` (`year`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `assessment_rating_descriptions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `assessment_rating_descriptions` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `assessment_variable_id` bigint unsigned NOT NULL,
  `rating` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `ard_variable_rating_unique` (`assessment_variable_id`,`rating`),
  KEY `ard_variable_rating_index` (`assessment_variable_id`,`rating`),
  CONSTRAINT `assessment_rating_descriptions_assessment_variable_id_foreign` FOREIGN KEY (`assessment_variable_id`) REFERENCES `assessment_variable` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `assessment_variable`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `assessment_variable` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `deskripsi` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `attendance_records`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `attendance_records` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `siswa_nis` varchar(15) COLLATE utf8mb4_unicode_ci NOT NULL,
  `data_guru_id` bigint unsigned NOT NULL,
  `data_kelas_id` bigint unsigned NOT NULL,
  `alfa` tinyint unsigned NOT NULL DEFAULT '0',
  `ijin` tinyint unsigned NOT NULL DEFAULT '0',
  `sakit` tinyint unsigned NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `attendance_records_data_guru_id_foreign` (`data_guru_id`),
  KEY `attendance_records_data_kelas_id_foreign` (`data_kelas_id`),
  KEY `attendance_records_siswa_nis_foreign` (`siswa_nis`),
  CONSTRAINT `attendance_records_data_guru_id_foreign` FOREIGN KEY (`data_guru_id`) REFERENCES `data_guru` (`guru_id`) ON DELETE CASCADE,
  CONSTRAINT `attendance_records_data_kelas_id_foreign` FOREIGN KEY (`data_kelas_id`) REFERENCES `data_kelas` (`kelas_id`) ON DELETE CASCADE,
  CONSTRAINT `attendance_records_siswa_nis_foreign` FOREIGN KEY (`siswa_nis`) REFERENCES `data_siswa` (`nis`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `data_guru`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `data_guru` (
  `user_id` bigint unsigned DEFAULT NULL,
  `nama_lengkap` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `guru_id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `nip` varchar(12) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `nuptk` varchar(12) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `jenis_kelamin` enum('Laki-laki','Perempuan') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `tempat_lahir` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `tanggal_lahir` date NOT NULL,
  `alamat` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `no_telp` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `email` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `status` enum('Aktif','Non_Aktif') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Aktif',
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`guru_id`),
  UNIQUE KEY `data_guru_nuptk_unique` (`nuptk`),
  UNIQUE KEY `data_guru_nip_unique` (`nip`),
  KEY `data_guru_user_id_foreign` (`user_id`),
  CONSTRAINT `data_guru_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `data_kelas`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `data_kelas` (
  `kelas_id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `nama_kelas` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `walikelas_id` bigint unsigned DEFAULT NULL,
  `tahun_ajaran_id` bigint unsigned DEFAULT NULL,
  `tingkat` int NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`kelas_id`),
  KEY `data_kelas_walikelas_id_foreign` (`walikelas_id`),
  KEY `data_kelas_tahun_ajaran_id_foreign` (`tahun_ajaran_id`),
  CONSTRAINT `data_kelas_tahun_ajaran_id_foreign` FOREIGN KEY (`tahun_ajaran_id`) REFERENCES `academic_year` (`tahun_ajaran_id`) ON DELETE SET NULL,
  CONSTRAINT `data_kelas_walikelas_id_foreign` FOREIGN KEY (`walikelas_id`) REFERENCES `data_guru` (`guru_id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `data_siswa`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `data_siswa` (
  `kelas` bigint unsigned DEFAULT NULL,
  `user_id` bigint unsigned NOT NULL,
  `nama_lengkap` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `nisn` varchar(10) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `nis` varchar(15) COLLATE utf8mb4_unicode_ci NOT NULL,
  `diterima_kelas` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `agama` enum('Islam','Kristen','Hindu','Buddha','Konghucu') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `asal_sekolah` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `jenis_kelamin` enum('Laki-laki','Perempuan') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `anak_ke` tinyint unsigned NOT NULL,
  `jumlah_saudara` tinyint unsigned NOT NULL,
  `tanggal_lahir` date NOT NULL,
  `tempat_lahir` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `tanggal_diterima` date NOT NULL,
  `alamat` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `nama_ayah` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `nama_ibu` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `pekerjaan_ayah` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `pekerjaan_ibu` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `no_telp_ortu_wali` varchar(15) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `email_ortu_wali` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_by` bigint unsigned DEFAULT NULL,
  `updated_by` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `status` enum('Aktif','Non_Aktif') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Aktif',
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`nis`),
  UNIQUE KEY `data_siswa_nisn_unique` (`nisn`),
  KEY `data_siswa_user_id_foreign` (`user_id`),
  KEY `data_siswa_created_by_foreign` (`created_by`),
  KEY `data_siswa_updated_by_foreign` (`updated_by`),
  KEY `data_siswa_kelas_foreign` (`kelas`),
  CONSTRAINT `data_siswa_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`user_id`) ON DELETE SET NULL,
  CONSTRAINT `data_siswa_kelas_foreign` FOREIGN KEY (`kelas`) REFERENCES `data_kelas` (`kelas_id`) ON DELETE SET NULL,
  CONSTRAINT `data_siswa_updated_by_foreign` FOREIGN KEY (`updated_by`) REFERENCES `users` (`user_id`) ON DELETE SET NULL,
  CONSTRAINT `data_siswa_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `failed_jobs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `failed_jobs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `uuid` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `connection` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `queue` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `payload` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `exception` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `failed_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `failed_jobs_uuid_unique` (`uuid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `growth_records`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `growth_records` (
  `pertumbuhan_id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `siswa_nis` varchar(15) COLLATE utf8mb4_unicode_ci NOT NULL,
  `data_guru_id` bigint unsigned DEFAULT NULL,
  `data_kelas_id` bigint unsigned DEFAULT NULL,
  `tahun_ajaran_id` bigint unsigned DEFAULT NULL,
  `month` tinyint unsigned NOT NULL,
  `year` smallint unsigned DEFAULT NULL,
  `lingkar_kepala` decimal(5,2) DEFAULT NULL COMMENT 'dalam cm',
  `lingkar_lengan` decimal(5,2) DEFAULT NULL COMMENT 'dalam cm',
  `berat_badan` decimal(5,2) DEFAULT NULL COMMENT 'dalam kg',
  `tinggi_badan` decimal(5,2) DEFAULT NULL COMMENT 'dalam cm',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`pertumbuhan_id`),
  UNIQUE KEY `unique_student_month` (`siswa_nis`,`month`),
  KEY `growth_records_data_guru_id_foreign` (`data_guru_id`),
  KEY `growth_records_data_kelas_id_foreign` (`data_kelas_id`),
  KEY `growth_records_tahun_ajaran_id_foreign` (`tahun_ajaran_id`),
  CONSTRAINT `growth_records_data_guru_id_foreign` FOREIGN KEY (`data_guru_id`) REFERENCES `data_guru` (`guru_id`) ON DELETE SET NULL,
  CONSTRAINT `growth_records_data_kelas_id_foreign` FOREIGN KEY (`data_kelas_id`) REFERENCES `data_kelas` (`kelas_id`) ON DELETE SET NULL,
  CONSTRAINT `growth_records_siswa_nis_foreign` FOREIGN KEY (`siswa_nis`) REFERENCES `data_siswa` (`nis`) ON DELETE CASCADE,
  CONSTRAINT `growth_records_tahun_ajaran_id_foreign` FOREIGN KEY (`tahun_ajaran_id`) REFERENCES `academic_year` (`tahun_ajaran_id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `job_batches`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `job_batches` (
  `id` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `total_jobs` int NOT NULL,
  `pending_jobs` int NOT NULL,
  `failed_jobs` int NOT NULL,
  `failed_job_ids` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `options` mediumtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `cancelled_at` int DEFAULT NULL,
  `created_at` int NOT NULL,
  `finished_at` int DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `jobs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `jobs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `queue` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `payload` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `attempts` tinyint unsigned NOT NULL,
  `reserved_at` int unsigned DEFAULT NULL,
  `available_at` int unsigned NOT NULL,
  `created_at` int unsigned NOT NULL,
  PRIMARY KEY (`id`),
  KEY `jobs_queue_index` (`queue`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `migrations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `migrations` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `migration` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `batch` int NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `model_has_permissions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `model_has_permissions` (
  `permission_id` bigint unsigned NOT NULL,
  `model_type` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `model_id` bigint unsigned NOT NULL,
  PRIMARY KEY (`permission_id`,`model_id`,`model_type`),
  KEY `model_has_permissions_model_id_model_type_index` (`model_id`,`model_type`),
  CONSTRAINT `model_has_permissions_permission_id_foreign` FOREIGN KEY (`permission_id`) REFERENCES `permissions` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `model_has_roles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `model_has_roles` (
  `role_id` bigint unsigned NOT NULL,
  `model_type` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `model_id` bigint unsigned NOT NULL,
  PRIMARY KEY (`role_id`,`model_id`,`model_type`),
  KEY `model_has_roles_model_id_model_type_index` (`model_id`,`model_type`),
  CONSTRAINT `model_has_roles_role_id_foreign` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `monthly_report_broadcasts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `monthly_report_broadcasts` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `siswa_nis` varchar(15) COLLATE utf8mb4_unicode_ci NOT NULL,
  `monthly_report_id` bigint unsigned NOT NULL,
  `phone_number` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `message` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `status` enum('pending','sent','failed') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `response` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `error_message` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `retry_count` int NOT NULL DEFAULT '0',
  `sent_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `monthly_report_broadcasts_monthly_report_id_status_index` (`monthly_report_id`,`status`),
  KEY `monthly_report_broadcasts_sent_at_index` (`sent_at`),
  KEY `monthly_report_broadcasts_siswa_nis_foreign` (`siswa_nis`),
  CONSTRAINT `monthly_report_broadcasts_monthly_report_id_foreign` FOREIGN KEY (`monthly_report_id`) REFERENCES `monthly_reports` (`id`) ON DELETE CASCADE,
  CONSTRAINT `monthly_report_broadcasts_siswa_nis_foreign` FOREIGN KEY (`siswa_nis`) REFERENCES `data_siswa` (`nis`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `monthly_reports`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `monthly_reports` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `siswa_nis` varchar(15) COLLATE utf8mb4_unicode_ci NOT NULL,
  `data_guru_id` bigint unsigned NOT NULL,
  `data_kelas_id` bigint unsigned NOT NULL,
  `tahun_ajaran_id` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `month` tinyint unsigned NOT NULL,
  `year` smallint unsigned NOT NULL,
  `catatan` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `photos` json DEFAULT NULL COMMENT 'Array of photo file paths',
  `status` enum('draft','final') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'draft',
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `monthly_reports_data_guru_id_foreign` (`data_guru_id`),
  KEY `monthly_reports_data_kelas_id_foreign` (`data_kelas_id`),
  KEY `monthly_reports_tahun_ajaran_id_foreign` (`tahun_ajaran_id`),
  KEY `monthly_reports_siswa_nis_foreign` (`siswa_nis`),
  CONSTRAINT `monthly_reports_data_guru_id_foreign` FOREIGN KEY (`data_guru_id`) REFERENCES `data_guru` (`guru_id`) ON DELETE CASCADE,
  CONSTRAINT `monthly_reports_data_kelas_id_foreign` FOREIGN KEY (`data_kelas_id`) REFERENCES `data_kelas` (`kelas_id`) ON DELETE CASCADE,
  CONSTRAINT `monthly_reports_siswa_nis_foreign` FOREIGN KEY (`siswa_nis`) REFERENCES `data_siswa` (`nis`) ON DELETE CASCADE,
  CONSTRAINT `monthly_reports_tahun_ajaran_id_foreign` FOREIGN KEY (`tahun_ajaran_id`) REFERENCES `academic_year` (`tahun_ajaran_id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `notifications`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `notifications` (
  `id` char(36) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `type` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `notifiable_type` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `notifiable_id` bigint unsigned NOT NULL,
  `data` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `read_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `notifications_notifiable_type_notifiable_id_index` (`notifiable_type`,`notifiable_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `password_reset_tokens`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `password_reset_tokens` (
  `email` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `token` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `permissions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `permissions` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `guard_name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `permissions_name_guard_name_unique` (`name`,`guard_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `personal_access_tokens`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `personal_access_tokens` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `tokenable_type` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `tokenable_id` bigint unsigned NOT NULL,
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `token` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `abilities` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `last_used_at` timestamp NULL DEFAULT NULL,
  `expires_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `personal_access_tokens_token_unique` (`token`),
  KEY `personal_access_tokens_tokenable_type_tokenable_id_index` (`tokenable_type`,`tokenable_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `role_has_permissions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `role_has_permissions` (
  `permission_id` bigint unsigned NOT NULL,
  `role_id` bigint unsigned NOT NULL,
  PRIMARY KEY (`permission_id`,`role_id`),
  KEY `role_has_permissions_role_id_foreign` (`role_id`),
  CONSTRAINT `role_has_permissions_permission_id_foreign` FOREIGN KEY (`permission_id`) REFERENCES `permissions` (`id`) ON DELETE CASCADE,
  CONSTRAINT `role_has_permissions_role_id_foreign` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `roles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `roles` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `guard_name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `roles_name_guard_name_unique` (`name`,`guard_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `sekolah`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `sekolah` (
  `sekolah_id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `nama_sekolah` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `alamat` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `npsn` varchar(8) COLLATE utf8mb4_unicode_ci NOT NULL,
  `nss` varchar(8) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `kode_pos` varchar(5) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `kepala_sekolah` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `kepala_sekolah_id` bigint unsigned DEFAULT NULL,
  `nip_kepala_sekolah` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `logo_sekolah` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`sekolah_id`),
  KEY `sekolah_kepala_sekolah_id_foreign` (`kepala_sekolah_id`),
  CONSTRAINT `sekolah_kepala_sekolah_id_foreign` FOREIGN KEY (`kepala_sekolah_id`) REFERENCES `data_guru` (`guru_id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `sessions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `sessions` (
  `id` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `user_id` bigint unsigned DEFAULT NULL,
  `ip_address` varchar(45) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `user_agent` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `payload` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `last_activity` int NOT NULL,
  PRIMARY KEY (`id`),
  KEY `sessions_user_id_index` (`user_id`),
  KEY `sessions_last_activity_index` (`last_activity`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `student_assessment_details`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `student_assessment_details` (
  `detail_id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `penilaian_id` bigint unsigned NOT NULL,
  `variabel_id` bigint unsigned NOT NULL,
  `rating` enum('Berkembang Sesuai Harapan','Belum Berkembang','Mulai Berkembang','Sudah Berkembang') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `description` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `images` json DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`detail_id`),
  UNIQUE KEY `unique_student_assessment_variable` (`penilaian_id`,`variabel_id`),
  KEY `student_assessment_details_assessment_variable_id_foreign` (`variabel_id`),
  CONSTRAINT `student_assessment_details_penilaian_id_foreign` FOREIGN KEY (`penilaian_id`) REFERENCES `student_assessments` (`penilaian_id`) ON DELETE CASCADE,
  CONSTRAINT `student_assessment_details_variabel_id_foreign` FOREIGN KEY (`variabel_id`) REFERENCES `assessment_variables` (`variabel_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `student_assessments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `student_assessments` (
  `penilaian_id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `siswa_nis` varchar(15) COLLATE utf8mb4_unicode_ci NOT NULL,
  `tahun_ajaran_id` bigint unsigned DEFAULT NULL,
  `semester` varchar(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `status` enum('belum_dinilai','sebagian','selesai') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'belum_dinilai',
  `completed_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`penilaian_id`),
  UNIQUE KEY `unique_student_semester` (`siswa_nis`,`semester`),
  UNIQUE KEY `unique_student_semester_assessment` (`siswa_nis`,`semester`),
  KEY `student_assessments_tahun_ajaran_id_foreign` (`tahun_ajaran_id`),
  CONSTRAINT `student_assessments_siswa_nis_foreign` FOREIGN KEY (`siswa_nis`) REFERENCES `data_siswa` (`nis`) ON DELETE CASCADE,
  CONSTRAINT `student_assessments_tahun_ajaran_id_foreign` FOREIGN KEY (`tahun_ajaran_id`) REFERENCES `academic_year` (`tahun_ajaran_id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `users` (
  `user_id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `username` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `avatar` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `password` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `remember_token` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`user_id`),
  UNIQUE KEY `username` (`username`),
  UNIQUE KEY `users_email_unique` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (1,'2014_10_12_000000_create_users_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (2,'2019_08_19_000000_create_failed_jobs_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (3,'2019_12_14_000001_create_personal_access_tokens_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (4,'2025_09_13_134845_sekolah',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (5,'2025_09_14_082211_data-siswa',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (6,'2025_09_15_004736_add_tempat_lahir_to_data_siswa_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (7,'2025_09_15_015956_data_guru',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (8,'2025_09_15_060507_create_permission_tables',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (9,'2025_09_15_141353_add_status_to_data_guru_table',2);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (10,'2025_09_15_144424_add_status_to_data_siswa_table',3);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (11,'2025_09_22_052144_create_academic_year',4);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (12,'2025_10_04_045949_add_user_id_to_data_guru_table',5);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (15,'2025_10_05_063949_create_data_kelas',6);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (16,'2025_10_07_044824_modify_kelas_column_in_data_siswa_table',7);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (17,'2025_10_09_095740_create_assessment_variable_table',8);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (18,'2025_10_12_063435_create_student_assessments_table',9);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (19,'2025_10_12_063449_create_student_assessment_details_table',10);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (20,'2025_10_12_135332_create_growth_records_table',11);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (21,'2025_10_12_143457_remove_academic_year_from_growth_records_table',12);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (22,'2025_10_12_144613_update_growth_records_table_structure',13);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (23,'2025_10_12_144726_add_unique_constraint_to_growth_records',14);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (24,'2025_10_12_163459_create_attendance_records_table',15);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (27,'2025_10_14_231435_crate_monthly_report_table',16);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (28,'2025_10_15_060239_fix_monthly_reports_unique_constraint',16);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (29,'2025_10_15_060525_update_existing_monthly_reports_table',17);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (30,'2025_10_15_062142_drop_old_constraint_monthly_reports',18);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (31,'2025_10_15_062612_force_fix_monthly_reports_constraints',19);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (32,'2025_10_15_063949_final_fix_monthly_reports_constraints',20);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (33,'2025_10_15_070049_add_photos_to_monthly_reports',21);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (34,'2025_10_16_091637_add_avatar_to_users_table',22);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (36,'2025_10_19_135557_create_notifications_table',23);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (37,'2025_10_26_104233_create_assessment_rating_descriptions_table',24);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (38,'2025_10_27_000001_create_monthly_report_broadcasts_table',25);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (39,'2025_10_27_000002_create_jobs_table',26);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (40,'2025_10_27_113109_create_job_batches_table',26);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (41,'2025_11_06_000001_add_soft_deletes_to_critical_tables',27);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (42,'2025_11_08_103244_add_kepala_sekolah_id_to_sekolah_table',27);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (43,'2025_11_10_111346_add_fields_to_academic_year_table',28);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (44,'2025_11_10_114813_fix_academic_year_semester_enum',29);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (45,'2025_11_12_075807_update_existing_kelas_with_active_tahun_ajaran',30);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (46,'2025_11_15_100001_convert_siswa_to_natural_key',30);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (49,'2025_11_15_100002_rename_primary_keys_to_semantic_names',31);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (51,'2025_11_15_100003_update_foreign_keys_to_semantic_names',32);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (52,'2025_11_16_043438_add_missing_columns_to_growth_records_table',33);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (53,'2025_11_16_144105_add_tahun_ajaran_to_student_assessments_table',34);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (54,'2025_11_16_144138_add_tahun_ajaran_to_monthly_reports_table',34);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (55,'2025_11_16_144145_add_tahun_ajaran_to_growth_records_table',34);
