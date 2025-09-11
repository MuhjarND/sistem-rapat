-- phpMyAdmin SQL Dump
-- version 5.2.0
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Sep 11, 2025 at 06:18 AM
-- Server version: 10.4.27-MariaDB
-- PHP Version: 7.4.33

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `sistem_rapat`
--

-- --------------------------------------------------------

--
-- Table structure for table `absensi`
--

CREATE TABLE `absensi` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `id_rapat` bigint(20) UNSIGNED NOT NULL,
  `id_user` bigint(20) UNSIGNED NOT NULL,
  `status` enum('hadir','izin','alfa') NOT NULL DEFAULT 'alfa',
  `waktu_absen` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `absensi`
--

INSERT INTO `absensi` (`id`, `id_rapat`, `id_user`, `status`, `waktu_absen`, `created_at`, `updated_at`) VALUES
(1, 1, 3, 'hadir', '2025-08-23 01:02:23', '2025-08-23 01:02:23', '2025-08-23 01:02:23'),
(2, 3, 4, 'hadir', '2025-08-24 08:25:46', '2025-08-24 08:25:46', '2025-08-24 08:25:46'),
(3, 8, 4, 'hadir', '2025-08-25 01:04:10', '2025-08-25 01:04:10', '2025-08-25 01:04:10');

-- --------------------------------------------------------

--
-- Table structure for table `failed_jobs`
--

CREATE TABLE `failed_jobs` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `connection` text NOT NULL,
  `queue` text NOT NULL,
  `payload` longtext NOT NULL,
  `exception` longtext NOT NULL,
  `failed_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `jobs`
--

CREATE TABLE `jobs` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `queue` varchar(255) NOT NULL,
  `payload` longtext NOT NULL,
  `attempts` tinyint(3) UNSIGNED NOT NULL,
  `reserved_at` int(10) UNSIGNED DEFAULT NULL,
  `available_at` int(10) UNSIGNED NOT NULL,
  `created_at` int(10) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `kategori_rapat`
--

CREATE TABLE `kategori_rapat` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `nama` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `kategori_rapat`
--

INSERT INTO `kategori_rapat` (`id`, `nama`, `created_at`, `updated_at`) VALUES
(1, 'Monitoring dan Evaluasi', '2025-08-24 01:07:16', '2025-08-24 01:07:16'),
(2, 'Koordinasi Sewilayah PTA Papua Barat', '2025-08-24 01:07:40', '2025-08-24 01:07:40'),
(3, 'Koordinasi Internal', '2025-08-24 01:07:57', '2025-08-24 01:07:57'),
(4, 'Kategori 1', '2025-08-28 06:15:01', '2025-08-28 06:15:01'),
(5, 'Kategori 2', '2025-08-28 06:15:10', '2025-08-28 06:15:10'),
(6, 'Kategori 3', '2025-08-28 06:15:19', '2025-08-28 06:15:19'),
(7, 'Kategori 4', '2025-08-28 06:15:28', '2025-08-28 06:15:28');

-- --------------------------------------------------------

--
-- Table structure for table `laporan_archived_meetings`
--

CREATE TABLE `laporan_archived_meetings` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `rapat_id` bigint(20) UNSIGNED NOT NULL,
  `archived_by` bigint(20) UNSIGNED DEFAULT NULL,
  `archived_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `laporan_archived_meetings`
--

INSERT INTO `laporan_archived_meetings` (`id`, `rapat_id`, `archived_by`, `archived_at`, `created_at`, `updated_at`) VALUES
(1, 14, 1, NULL, '2025-09-11 02:12:00', '2025-09-11 02:12:00'),
(2, 1, 1, NULL, '2025-09-11 02:12:18', '2025-09-11 02:12:18'),
(3, 23, 1, NULL, '2025-09-11 02:12:45', '2025-09-11 02:12:45'),
(4, 15, 1, NULL, '2025-09-11 02:19:35', '2025-09-11 02:19:35'),
(5, 20, 1, NULL, '2025-09-11 02:20:07', '2025-09-11 02:20:07'),
(6, 16, NULL, '2025-09-11 04:13:56', '2025-09-11 04:13:56', '2025-09-11 04:13:56'),
(7, 4, NULL, '2025-09-11 04:14:24', '2025-09-11 04:14:24', '2025-09-11 04:14:24');

-- --------------------------------------------------------

--
-- Table structure for table `laporan_files`
--

CREATE TABLE `laporan_files` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `is_archived` tinyint(1) NOT NULL DEFAULT 0,
  `archived_at` timestamp NULL DEFAULT NULL,
  `id_rapat` bigint(20) UNSIGNED DEFAULT NULL,
  `id_kategori` bigint(20) UNSIGNED DEFAULT NULL,
  `judul` varchar(255) NOT NULL,
  `tanggal_laporan` date DEFAULT NULL,
  `keterangan` text DEFAULT NULL,
  `file_name` varchar(255) NOT NULL,
  `file_path` varchar(255) NOT NULL,
  `mime` varchar(255) DEFAULT NULL,
  `size` bigint(20) UNSIGNED NOT NULL DEFAULT 0,
  `uploaded_by` bigint(20) UNSIGNED NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `laporan_files`
--

INSERT INTO `laporan_files` (`id`, `is_archived`, `archived_at`, `id_rapat`, `id_kategori`, `judul`, `tanggal_laporan`, `keterangan`, `file_name`, `file_path`, `mime`, `size`, `uploaded_by`, `created_at`, `updated_at`) VALUES
(11, 0, NULL, NULL, 1, 'Rapat 6', '2025-06-26', NULL, 'Laporan rapat internal08-13-2025-200153.pdf', 'laporan/d36f8cb5-a263-48e0-96a0-b7b7afa03ffc.pdf', 'application/pdf', 1393333, 1, '2025-08-29 04:56:56', '2025-08-29 04:56:56'),
(13, 0, NULL, NULL, 3, 'Testing aplikasi', '2025-06-18', NULL, 'Laporan rapat internal08-13-2025-200153.pdf', 'laporan/5354b122-7eec-4658-a4d1-0a4811d50f13.pdf', 'application/pdf', 1393333, 1, '2025-08-29 05:10:25', '2025-08-29 05:10:25'),
(14, 0, NULL, NULL, 2, 'Testing fitur', '2024-06-29', 'sfasf', 'Laporan 08-13-2025-195913.pdf', 'laporan/35e4392b-46f1-45b7-880f-e3b6caa5e0d5.pdf', 'application/pdf', 1055965, 1, '2025-08-29 05:11:56', '2025-08-29 05:11:56'),
(17, 0, NULL, NULL, 4, 'Testing lagi', '2025-05-08', NULL, 'Laporan 08-13-2025-195913.pdf', 'laporan/80be8059-de0b-43b0-bc8f-9227a6f4cf6f.pdf', 'application/pdf', 1055965, 1, '2025-08-29 05:42:21', '2025-08-29 05:42:21'),
(18, 0, NULL, NULL, 5, 'testing fitur', '2025-07-17', NULL, 'Laporan 08-13-2025-195913.pdf', 'laporan/b298b066-33f7-42a0-8314-de611831194e.pdf', 'application/pdf', 1055965, 1, '2025-08-29 06:19:40', '2025-08-29 06:19:40'),
(20, 0, NULL, NULL, 2, 'Rapat 4', '2025-09-05', NULL, 'Undangan Rapat Monev Kesekretariatan.pdf', 'laporan/f0ee2fc3-9f25-4863-bfca-c6a67fbb2765.pdf', 'application/pdf', 290678, 1, '2025-09-05 13:26:31', '2025-09-08 06:55:40'),
(21, 0, NULL, NULL, 5, 'Rapat 2', '2025-09-08', NULL, 'TAMPALATE PESERTA BANYAK.pdf', 'laporan/d0e6c260-8ff3-42d0-8a5d-556cb675916f.pdf', 'application/pdf', 1068551, 1, '2025-09-08 06:16:59', '2025-09-08 06:16:59'),
(22, 0, NULL, NULL, 2, 'Rapat 3', '2025-09-08', NULL, 'TAMPALATE PESERTA BANYAK.pdf', 'laporan/c660ed58-b204-47a8-b633-45a3dc06913c.pdf', 'application/pdf', 1068551, 1, '2025-09-08 06:17:14', '2025-09-08 06:17:14'),
(23, 1, '2025-09-11 04:16:21', NULL, NULL, 'Rapat 4', '2025-09-08', NULL, 'TAMPALATE PESERTA BANYAK.pdf', 'laporan/244cc0fe-4042-43ca-ad4a-80ad729e2709.pdf', 'application/pdf', 1068551, 1, '2025-09-08 06:17:36', '2025-09-11 04:16:21'),
(24, 1, '2025-09-08 23:44:43', NULL, 5, 'Rapat 2', '2025-09-08', NULL, 'TAMPALATE PESERTA BANYAK.pdf', 'laporan/89e098c9-152f-4dad-909b-c8f631c649ca.pdf', 'application/pdf', 1068551, 1, '2025-09-08 06:17:51', '2025-09-08 23:44:43'),
(25, 1, '2025-09-10 03:53:06', NULL, 7, 'Rapat 6', '2025-09-08', NULL, 'TAMPALATE PESERTA BANYAK.pdf', 'laporan/74b0f371-7676-4715-ba4e-84d951f1e3f8.pdf', 'application/pdf', 1068551, 1, '2025-09-08 06:18:04', '2025-09-10 03:53:06'),
(26, 1, '2025-09-08 06:49:52', NULL, 6, 'Rapat 1', '2025-09-08', NULL, 'TAMPALATE PESERTA BANYAK.pdf', 'laporan/75cf10d7-ea09-49ef-b3ee-fbe0b21710cd.pdf', 'application/pdf', 1068551, 1, '2025-09-08 06:18:16', '2025-09-08 06:49:52'),
(30, 1, '2025-09-10 03:31:30', 14, 1, 'Gabungan Rapat: mencoba fitur queue ke 2 (12 Sep 2025)', '2025-09-12', 'PDF gabungan otomatis (Undangan + Absensi + Notulensi)', 'mencoba-fitur-queue-ke-2-gabungan.pdf', 'laporan/6729861b-ab2c-44b9-bb88-3d3f39759358.pdf', 'application/pdf', 3395824, 1, '2025-09-10 03:31:30', '2025-09-10 03:31:30'),
(31, 1, '2025-09-10 03:31:46', 15, 5, 'Gabungan Rapat: mencoba fitur queue ke 3 (12 Sep 2025)', '2025-09-12', 'PDF gabungan otomatis (Undangan + Absensi + Notulensi)', 'mencoba-fitur-queue-ke-3-gabungan.pdf', 'laporan/66768ef6-aff7-42d9-a3c6-2710cb0fc509.pdf', 'application/pdf', 3392800, 1, '2025-09-10 03:31:46', '2025-09-10 03:31:46'),
(32, 1, '2025-09-10 03:33:09', 1, 1, 'Gabungan Rapat: Rapat 1 (12 Sep 2020)', '2020-09-12', 'PDF gabungan otomatis (Undangan + Absensi + Notulensi)', 'rapat-1-gabungan.pdf', 'laporan/885b8e6e-d7c0-4f52-b32a-dc7e2a3a5b38.pdf', 'application/pdf', 3043881, 1, '2025-09-10 03:33:09', '2025-09-10 03:33:09'),
(33, 1, '2025-09-10 03:34:53', 1, 1, 'Gabungan Rapat: Rapat 1 (12 Sep 2020)', '2020-09-12', 'PDF gabungan otomatis (Undangan + Absensi + Notulensi)', 'rapat-1-gabungan.pdf', 'laporan/cf2c0f65-95ea-471d-945e-b687fca9b6c2.pdf', 'application/pdf', 3043881, 1, '2025-09-10 03:34:53', '2025-09-10 03:34:53'),
(34, 1, '2025-09-10 03:35:13', 14, 1, 'Gabungan Rapat: mencoba fitur queue ke 2 (12 Sep 2025)', '2025-09-12', 'PDF gabungan otomatis (Undangan + Absensi + Notulensi)', 'mencoba-fitur-queue-ke-2-gabungan.pdf', 'laporan/37bf6efe-3383-49a2-807b-2ecd206569bd.pdf', 'application/pdf', 3395824, 1, '2025-09-10 03:35:13', '2025-09-10 03:35:13'),
(35, 1, '2025-09-10 03:38:34', 15, 5, 'Gabungan Rapat: mencoba fitur queue ke 3 (12 Sep 2025)', '2025-09-12', 'PDF gabungan otomatis (Undangan + Absensi + Notulensi)', 'mencoba-fitur-queue-ke-3-gabungan.pdf', 'laporan/ffbc2de4-acda-4e82-a87e-ac477d783e9b.pdf', 'application/pdf', 3392800, 1, '2025-09-10 03:38:34', '2025-09-10 03:38:34'),
(36, 1, '2025-09-10 03:52:49', 15, 5, 'Gabungan Rapat: mencoba fitur queue ke 3 (12 Sep 2025)', '2025-09-12', 'PDF gabungan otomatis (Undangan + Absensi + Notulensi)', 'mencoba-fitur-queue-ke-3-gabungan.pdf', 'laporan/a5be9e98-90ed-422f-9581-6f1acd0ca58a.pdf', 'application/pdf', 3392800, 1, '2025-09-10 03:52:49', '2025-09-10 03:52:49'),
(37, 1, '2025-09-10 03:53:32', 16, 2, 'Gabungan Rapat: Mencoba fitur wa (12 Sep 2025)', '2025-09-12', 'PDF gabungan otomatis (Undangan + Absensi)', 'mencoba-fitur-wa-gabungan.pdf', 'laporan/01e8710c-e5e6-4a14-8f0c-2ef5c0717d90.pdf', 'application/pdf', 190238, 1, '2025-09-10 03:53:32', '2025-09-10 03:53:32'),
(38, 1, '2025-09-11 04:13:56', 16, 2, 'Gabungan Rapat: Mencoba fitur wa (12 Sep 2025)', '2025-09-12', 'PDF gabungan otomatis (Undangan + Absensi)', 'mencoba-fitur-wa-gabungan.pdf', 'laporan/2cfe9be0-a5c9-4210-8dca-b031fae6335e.pdf', 'application/pdf', 190238, 1, '2025-09-11 04:13:56', '2025-09-11 04:13:56'),
(39, 1, '2025-09-11 04:14:24', 4, 4, 'Gabungan Rapat: dslkfjklsadf (23 Agt 2025)', '2025-08-23', 'PDF gabungan otomatis (Undangan + Absensi)', 'dslkfjklsadf-gabungan.pdf', 'laporan/d304b3cf-cbf8-4582-bdfa-3065d753e8d8.pdf', 'application/pdf', 190130, 1, '2025-09-11 04:14:24', '2025-09-11 04:14:24');

-- --------------------------------------------------------

--
-- Table structure for table `migrations`
--

CREATE TABLE `migrations` (
  `id` int(10) UNSIGNED NOT NULL,
  `migration` varchar(255) NOT NULL,
  `batch` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `migrations`
--

INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES
(1, '2014_10_12_000000_create_users_table', 1),
(2, '2014_10_12_100000_create_password_resets_table', 1),
(3, '2019_08_19_000000_create_failed_jobs_table', 1),
(4, '2025_08_23_090148_create_rapat_table', 1),
(5, '2025_08_23_090214_create_undangan_table', 1),
(6, '2025_08_23_090234_create_absensi_table', 1),
(7, '2025_08_23_090254_create_notulensi_table', 1),
(8, '2025_08_23_103529_tambah_nomor_undangan_di_rapat', 2),
(9, '2025_08_23_113333_tambah_pemimpin_rapat_di_rapat', 3),
(10, '2025_08_23_115916_create_pimpinan_rapat_table', 4),
(11, '2025_08_23_120008_tambah_id_pimpinan_di_rapat', 4),
(12, '2025_08_23_122845_tambah_jabatan_pada_users', 5),
(13, '2025_08_23_160700_tambah_status_pada_rapat', 6),
(14, '2025_08_23_174323_create_kategori_rapat_table', 7),
(15, '2025_08_23_174738_tambah_id_kategori_di_rapat', 7),
(16, '2025_08_25_091108_tambah_token_qr_di_rapat', 8),
(17, '2025_08_26_082836_create_notulensi_detail_table', 9),
(18, '2025_08_26_095313_add_dibuat_oleh_to_notulensi_table', 10),
(19, '2025_08_26_100357_modify_isi_nullable_in_notulensi', 11),
(20, '2025_08_26_101846_create_notulensi_dokumentasi_table', 12),
(21, '2025_08_27_144404_create_laporan_files_table', 13),
(22, '2025_08_27_152207_add_id_kategori_to_laporan_files_table', 14),
(23, '2025_09_03_091757_create_jobs_table', 15),
(24, '2025_09_05_222059_add_constraint_to_undangan_absensi', 16),
(25, '2025_09_05_222149_add_constraint_to_absensi', 16),
(26, '2025_09_06_114705_add_no_hp_to_users_table', 17),
(27, '2025_09_08_150228_add_is_archived_to_laporan_files_table', 18),
(28, '2025_09_08_150540_add_archived_at_to_laporan_files_table', 19),
(29, '2025_09_10_122949_create_laporan_archived_meetings_table', 20);

-- --------------------------------------------------------

--
-- Table structure for table `notulensi`
--

CREATE TABLE `notulensi` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `id_rapat` bigint(20) UNSIGNED NOT NULL,
  `dibuat_oleh` bigint(20) UNSIGNED DEFAULT NULL,
  `id_user` bigint(20) UNSIGNED NOT NULL,
  `isi` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `notulensi`
--

INSERT INTO `notulensi` (`id`, `id_rapat`, `dibuat_oleh`, `id_user`, `isi`, `created_at`, `updated_at`) VALUES
(1, 1, NULL, 2, 'sfksfljsdlddfj sfjlasf', '2025-08-23 01:12:37', '2025-08-23 01:12:37'),
(2, 2, NULL, 1, NULL, '2025-08-26 01:16:51', '2025-08-26 01:16:51'),
(3, 8, NULL, 1, NULL, '2025-08-26 01:42:08', '2025-08-26 05:18:31'),
(4, 3, NULL, 1, NULL, '2025-08-29 05:27:05', '2025-08-29 05:27:05'),
(5, 7, NULL, 1, NULL, '2025-08-29 05:27:54', '2025-08-29 05:27:54'),
(6, 14, NULL, 1, NULL, '2025-09-08 04:30:37', '2025-09-08 04:30:37'),
(7, 15, NULL, 1, NULL, '2025-09-08 04:31:33', '2025-09-08 04:31:33');

-- --------------------------------------------------------

--
-- Table structure for table `notulensi_detail`
--

CREATE TABLE `notulensi_detail` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `id_notulensi` bigint(20) UNSIGNED NOT NULL,
  `urut` int(11) NOT NULL DEFAULT 1,
  `hasil_pembahasan` text NOT NULL,
  `rekomendasi` text DEFAULT NULL,
  `penanggung_jawab` varchar(255) DEFAULT NULL,
  `tgl_penyelesaian` date DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `notulensi_detail`
--

INSERT INTO `notulensi_detail` (`id`, `id_notulensi`, `urut`, `hasil_pembahasan`, `rekomendasi`, `penanggung_jawab`, `tgl_penyelesaian`, `created_at`, `updated_at`) VALUES
(1, 2, 1, '<p>dsf</p>', '<p>df</p>', 'KESEKRETARIATAN', '2025-08-26', '2025-08-26 01:16:51', '2025-08-26 01:16:51'),
(2, 2, 2, '<p>dfdsf</p>', '<p>sdfdsf</p>', NULL, NULL, '2025-08-26 01:16:51', '2025-08-26 01:16:51'),
(17, 3, 1, '<p>Lorem ipsum adalah teks pengganti atau teks contoh yang biasa digunakan dalam desain grafis dan percetakan untuk menampilkan tata letak suatu proyek. Teks ini, yang berasal dari bahasa Latin, tidak memiliki arti<strong> yang jelas dan sengaja dibuat untuk menghindari perha</strong>tian pada isi teks, sehingga fokus pada elemen desain seperti tipografi dan tata letak.&nbsp;</p>', '<p>Lorem ipsum adalah teks pengganti atau teks contoh yang biasa digunakan dalam desain grafis dan percetakan untuk menampilkan tata letak suatu proyek. Teks ini, yang berasal dari bahasa Latin, tidak memiliki arti yang jelas dan sengaja dibuat untuk menghindari perhatian pada isi teks, sehingga fokus pada elemen desain seperti tipografi dan tata letak.&nbsp;</p>', 'KESEKRETARIATAN', NULL, '2025-08-26 05:18:31', '2025-08-26 05:18:31'),
(18, 3, 2, '<p><strong>Lorem ipsum adalah teks pengganti atau teks contoh yang biasa digunakan dalam desain grafis dan percetakan untuk menampilkan tata letak suatu proyek. Teks ini, yang berasal dari bahasa Latin, tidak memiliki arti yang jelas dan sengaja dibuat untuk menghindari perhatian pada isi teks, sehingga fokus pada elemen desain seperti tipografi dan tata letak.&nbsp;</strong></p>', '<ul><li>Lorem ipsum adalah teks pengganti atau teks contoh yang biasa digunakan dalam desain grafis dan percetakan untuk menampilkan tata letak suatu proyek.&nbsp;</li><li>Teks ini, yang berasal dari bahasa Latin, tidak memiliki arti yang jelas dan sengaja dibuat untuk menghindari perhatian pada isi teks, sehingga fokus pada elemen desain seperti tipografi dan tata letak.&nbsp;</li></ul>', NULL, '2025-08-05', '2025-08-26 05:18:31', '2025-08-26 05:18:31'),
(19, 3, 3, '<p>Lorem ipsum adalah teks pengganti atau teks contoh yang biasa digunakan dalam desain grafis dan percetakan untuk menampilkan tata letak suatu proyek. Teks ini, yang berasal dari bahasa Latin, tidak memiliki arti yang jelas dan sengaja dibuat untuk menghindari perhatian pada isi teks, sehingga fokus pada elemen desain seperti tipografi dan tata letak.&nbsp;</p>', '<p>Lorem ipsum adalah teks pengganti atau teks contoh yang biasa digunakan dalam desain grafis dan percetakan untuk menampilkan tata letak suatu proyek. Teks ini, yang berasal dari bahasa Latin, tidak memiliki arti yang jelas dan sengaja dibuat untuk menghindari perhatian pada isi teks, sehingga fokus pada elemen desain seperti tipografi dan tata letak.&nbsp;</p>', NULL, '2025-08-06', '2025-08-26 05:18:31', '2025-08-26 05:18:31'),
(20, 3, 4, '<p>Lorem ipsum adalah teks pengganti atau teks contoh yang biasa digunakan dalam desain grafis dan percetakan untuk menampilkan tata letak suatu proyek. Teks ini, yang berasal dari bahasa Latin, tidak memiliki arti yang jelas dan sengaja dibuat untuk menghindari perhatian pada isi teks, sehingga fokus pada elemen desain seperti tipografi dan tata letak.&nbsp;</p>', '<p>Lorem ipsum adalah teks pengganti atau teks contoh yang biasa digunakan dalam desain grafis dan percetakan untuk menampilkan tata letak suatu proyek. Teks ini, yang berasal dari bahasa Latin, tidak memiliki arti yang jelas dan sengaja dibuat untuk menghindari perhatian pada isi teks, sehingga fokus pada elemen desain seperti tipografi dan tata letak.&nbsp;</p>', NULL, '2025-08-21', '2025-08-26 05:18:31', '2025-08-26 05:18:31'),
(21, 3, 5, '<p>Lorem ipsum adalah teks pengganti atau teks contoh yang biasa digunakan dalam desain grafis dan percetakan untuk menampilkan tata letak suatu proyek. Teks ini, yang berasal dari bahasa Latin, tidak memiliki arti yang jelas dan sengaja dibuat untuk menghindari perhatian pada isi teks, sehingga fokus pada elemen desain seperti tipografi dan tata letak.&nbsp;</p>', '<p>Lorem ipsum adalah teks pengganti atau teks contoh yang biasa digunakan dalam desain grafis dan percetakan untuk menampilkan tata letak suatu proyek. Teks ini, yang berasal dari bahasa Latin, tidak memiliki arti yang jelas dan sengaja dibuat untuk menghindari perhatian pada isi teks, sehingga fokus pada elemen desain seperti tipografi dan tata letak.&nbsp;</p>', NULL, '2025-08-29', '2025-08-26 05:18:31', '2025-08-26 05:18:31'),
(22, 3, 6, '<p>Lorem ipsum adalah teks pengganti atau teks contoh yang biasa digunakan dalam desain grafis dan percetakan untuk menampilkan tata letak suatu proyek. Teks ini, yang berasal dari bahasa Latin, tidak memiliki arti yang jelas dan sengaja dibuat untuk menghindari perhatian pada isi teks, sehingga fokus pada elemen desain seperti tipografi dan tata letak.&nbsp;</p>', '<p>Lorem ipsum adalah teks pengganti atau teks contoh yang biasa digunakan dalam desain grafis dan percetakan untuk menampilkan tata letak suatu proyek. Teks ini, yang berasal dari bahasa Latin, tidak memiliki arti yang jelas dan sengaja dibuat untuk menghindari perhatian pada isi teks, sehingga fokus pada elemen desain seperti tipografi dan tata letak.&nbsp;</p>', NULL, NULL, '2025-08-26 05:18:31', '2025-08-26 05:18:31'),
(23, 4, 1, '<p>testing</p>', '<p>testing</p>', NULL, NULL, '2025-08-29 05:27:05', '2025-08-29 05:27:05'),
(24, 4, 2, '<p><strong>cba</strong></p>', '<p>coba lagi</p>', NULL, NULL, '2025-08-29 05:27:05', '2025-08-29 05:27:05'),
(25, 5, 1, '<p>testing</p>', '<p>testing</p>', NULL, NULL, '2025-08-29 05:27:54', '2025-08-29 05:27:54'),
(26, 5, 2, '<p><strong>bdsfjls</strong></p>', '<ul><li>coba lagi</li></ul>', NULL, NULL, '2025-08-29 05:27:54', '2025-08-29 05:27:54'),
(27, 6, 1, '<ul><li>Membahas realiasi belanja pegawai yang sudah dilakukan revisi namun baru setengahnya yang terealisasi.&nbsp;</li></ul>', '<ul><li>Membahas realiasi belanja pegawai yang sudah dilakukan revisi namun baru setengahnya yang terealisasi.&nbsp;</li></ul>', NULL, NULL, '2025-09-08 04:30:37', '2025-09-08 04:30:37'),
(28, 6, 2, '<ul><li>Membahas realiasi belanja pegawai yang sudah dilakukan revisi namun baru setengahnya yang terealisasi.&nbsp;</li></ul>', '<ul><li>Membahas realiasi belanja pegawai yang sudah dilakukan revisi namun baru setengahnya yang terealisasi.&nbsp;</li></ul>', NULL, NULL, '2025-09-08 04:30:37', '2025-09-08 04:30:37'),
(29, 6, 3, '<ul><li>Membahas realiasi belanja pegawai yang sudah dilakukan revisi namun baru setengahnya yang terealisasi.&nbsp;</li></ul>', '<ul><li>Membahas realiasi belanja pegawai yang sudah dilakukan revisi namun baru setengahnya yang terealisasi.&nbsp;</li></ul>', NULL, NULL, '2025-09-08 04:30:37', '2025-09-08 04:30:37'),
(30, 6, 4, '<ul><li>Membahas realiasi belanja pegawai yang sudah dilakukan revisi namun baru setengahnya yang terealisasi.&nbsp;</li></ul>', '<ul><li>Membahas realiasi belanja pegawai yang sudah dilakukan revisi namun baru setengahnya yang terealisasi.&nbsp;</li></ul>', NULL, NULL, '2025-09-08 04:30:37', '2025-09-08 04:30:37'),
(31, 7, 1, '<ul><li>Membahas realiasi belanja pegawai yang sudah dilakukan revisi namun baru setengahnya yang terealisasi.&nbsp;</li></ul>', '<ul><li>Membahas realiasi belanja pegawai yang sudah dilakukan revisi namun baru setengahnya yang terealisasi.&nbsp;</li></ul>', NULL, NULL, '2025-09-08 04:31:33', '2025-09-08 04:31:33'),
(32, 7, 2, '<ul><li>Membahas realiasi belanja pegawai yang sudah dilakukan revisi namun baru setengahnya yang terealisasi.&nbsp;</li></ul>', '<ul><li>Membahas realiasi belanja pegawai yang sudah dilakukan revisi namun baru setengahnya yang terealisasi.&nbsp;</li></ul>', NULL, NULL, '2025-09-08 04:31:33', '2025-09-08 04:31:33'),
(33, 7, 3, '<ul><li>Membahas realiasi belanja pegawai yang sudah dilakukan revisi namun baru setengahnya yang terealisasi.&nbsp;</li></ul>', '<ul><li>Membahas realiasi belanja pegawai yang sudah dilakukan revisi namun baru setengahnya yang terealisasi.&nbsp;</li></ul>', NULL, NULL, '2025-09-08 04:31:33', '2025-09-08 04:31:33');

-- --------------------------------------------------------

--
-- Table structure for table `notulensi_dokumentasi`
--

CREATE TABLE `notulensi_dokumentasi` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `id_notulensi` bigint(20) UNSIGNED NOT NULL,
  `file_path` varchar(255) NOT NULL,
  `caption` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `notulensi_dokumentasi`
--

INSERT INTO `notulensi_dokumentasi` (`id`, `id_notulensi`, `file_path`, `caption`, `created_at`, `updated_at`) VALUES
(1, 3, 'uploads/notulensi/WhatsApp-Image-2025-07-07-at-08-43-39-4a68da7d-68ad10f01a1e1.jpg', NULL, '2025-08-26 01:42:08', '2025-08-26 01:42:08'),
(2, 3, 'uploads/notulensi/WhatsApp-Image-2025-07-07-at-08-43-39-ae9cd5bd-68ad10f01ad6d.jpg', NULL, '2025-08-26 01:42:08', '2025-08-26 01:42:08'),
(3, 3, 'uploads/notulensi/WhatsApp-Image-2025-07-07-at-08-43-40-2f998487-68ad10f01b727.jpg', NULL, '2025-08-26 01:42:08', '2025-08-26 01:42:08'),
(4, 4, 'uploads/notulensi/WhatsApp-Image-2025-08-14-at-8-42-14-AM-68b13a2961fde.jpeg', NULL, '2025-08-29 05:27:05', '2025-08-29 05:27:05'),
(5, 4, 'uploads/notulensi/WhatsApp-Image-2025-08-14-at-8-42-15-AM-1--68b13a2962961.jpeg', NULL, '2025-08-29 05:27:05', '2025-08-29 05:27:05'),
(6, 4, 'uploads/notulensi/WhatsApp-Image-2025-08-14-at-8-42-16-AM-1--68b13a2963184.jpeg', NULL, '2025-08-29 05:27:05', '2025-08-29 05:27:05'),
(7, 5, 'uploads/notulensi/WhatsApp-Image-2025-08-14-at-8-42-15-AM-68b13a5a961a2.jpeg', NULL, '2025-08-29 05:27:54', '2025-08-29 05:27:54'),
(8, 5, 'uploads/notulensi/WhatsApp-Image-2025-08-14-at-8-42-16-AM-1--68b13a5a96b92.jpeg', NULL, '2025-08-29 05:27:54', '2025-08-29 05:27:54'),
(9, 5, 'uploads/notulensi/WhatsApp-Image-2025-08-14-at-8-42-16-AM-68b13a5a97581.jpeg', NULL, '2025-08-29 05:27:54', '2025-08-29 05:27:54'),
(10, 6, 'uploads/notulensi/WhatsApp-Image-2025-09-03-at-6-56-08-PM-68be5bedf40b6.jpeg', NULL, '2025-09-08 04:30:38', '2025-09-08 04:30:38'),
(11, 6, 'uploads/notulensi/WhatsApp-Image-2025-09-03-at-6-56-09-PM-68be5bee00823.jpeg', NULL, '2025-09-08 04:30:38', '2025-09-08 04:30:38'),
(12, 6, 'uploads/notulensi/WhatsApp-Image-2025-09-03-at-6-56-10-PM-68be5bee019a9.jpeg', NULL, '2025-09-08 04:30:38', '2025-09-08 04:30:38'),
(13, 7, 'uploads/notulensi/WhatsApp-Image-2025-09-03-at-6-56-27-PM-68be5c2566c24.jpeg', NULL, '2025-09-08 04:31:33', '2025-09-08 04:31:33'),
(14, 7, 'uploads/notulensi/WhatsApp-Image-2025-09-03-at-6-56-28-PM-68be5c25678ff.jpeg', NULL, '2025-09-08 04:31:33', '2025-09-08 04:31:33'),
(15, 7, 'uploads/notulensi/WhatsApp-Image-2025-09-03-at-6-56-33-PM-68be5c25686d9.jpeg', NULL, '2025-09-08 04:31:33', '2025-09-08 04:31:33');

-- --------------------------------------------------------

--
-- Table structure for table `password_resets`
--

CREATE TABLE `password_resets` (
  `email` varchar(255) NOT NULL,
  `token` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `pimpinan_rapat`
--

CREATE TABLE `pimpinan_rapat` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `nama` varchar(255) NOT NULL,
  `jabatan` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `pimpinan_rapat`
--

INSERT INTO `pimpinan_rapat` (`id`, `nama`, `jabatan`, `created_at`, `updated_at`) VALUES
(1, 'Nurmansyah, S.Ag, M.H', 'Sekretaris', '2025-08-23 03:20:13', '2025-08-23 03:20:13'),
(2, 'Muhjar', 'PKSTI', '2025-08-23 03:20:22', '2025-08-23 03:20:22');

-- --------------------------------------------------------

--
-- Table structure for table `rapat`
--

CREATE TABLE `rapat` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `token_qr` varchar(64) DEFAULT NULL,
  `nomor_undangan` varchar(255) DEFAULT NULL,
  `id_kategori` bigint(20) UNSIGNED DEFAULT NULL,
  `judul` varchar(255) NOT NULL,
  `deskripsi` text DEFAULT NULL,
  `tanggal` date NOT NULL,
  `waktu_mulai` time NOT NULL,
  `tempat` varchar(255) NOT NULL,
  `dibuat_oleh` bigint(20) UNSIGNED NOT NULL,
  `id_pimpinan` bigint(20) UNSIGNED DEFAULT NULL,
  `status` varchar(255) NOT NULL DEFAULT 'akan_datang',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `rapat`
--

INSERT INTO `rapat` (`id`, `token_qr`, `nomor_undangan`, `id_kategori`, `judul`, `deskripsi`, `tanggal`, `waktu_mulai`, `tempat`, `dibuat_oleh`, `id_pimpinan`, `status`, `created_at`, `updated_at`) VALUES
(1, NULL, '324658623683', 1, 'Rapat 1', 'Testing', '2020-09-12', '09:22:00', 'PTA Papua Barat', 1, 1, 'akan_datang', '2025-08-23 00:48:34', '2025-08-24 01:15:04'),
(2, NULL, 'dsfhsfhk123134', 3, 'Rapat 2', 'dfdsjflasd', '2025-08-25', '09:00:00', 'PTA Papua Barat', 1, 1, 'dibatalkan', '2025-08-23 01:54:04', '2025-08-24 01:10:15'),
(3, NULL, '123456678', 2, 'Rapat 3', 'Testing', '2025-08-23', '16:36:00', 'PTA Papua Barat', 1, 1, 'akan_datang', '2025-08-23 07:35:16', '2025-08-24 01:10:58'),
(4, NULL, 'dfsf', 4, 'dslkfjklsadf', 'kldsklasfj', '2025-08-23', '17:20:00', 'bkhk', 1, 2, 'akan_datang', '2025-08-23 08:17:37', '2025-08-28 06:15:55'),
(5, NULL, '123456778', 7, 'Testing rapat', 'tes tes', '2025-08-23', '17:20:00', 'PTA Papua Barat', 1, 1, 'akan_datang', '2025-08-23 08:18:24', '2025-08-28 06:16:30'),
(6, NULL, '2224343243', 3, 'Rapat 4', 'dslfjs lkfdla jsfl', '2025-08-23', '17:33:00', 'PTA Papua Barat', 1, 1, 'akan_datang', '2025-08-23 08:30:42', '2025-08-24 01:11:28'),
(7, NULL, '2323435235', 6, 'Rapat 5', 'slfjlkasfj lsfjla', '2025-08-23', '17:36:00', 'PTA Papua Barat', 1, 2, 'akan_datang', '2025-08-23 08:34:08', '2025-08-28 06:16:18'),
(8, '3mtcXrUax4VIrcu6qgVKTrbNTCrJb0id', '238985035803', 5, 'Coba testing tanggal rapat', 'Rapat untuk melakukan uji coba', '2025-08-02', '09:55:00', 'PTA Papua Barat', 1, 1, 'akan_datang', '2025-08-25 00:49:42', '2025-09-03 00:04:51'),
(9, 'zzsfKWobV5quOKiYKaunUM6NskHuOKCj', '987654321', 1, 'Coba Membuat Rapat', 'Testing create rapat manual lagi.', '2025-09-03', '08:50:00', 'Hotel Ambarukmo', 1, 1, 'akan_datang', '2025-09-02 23:43:57', '2025-09-02 23:43:57'),
(10, 'EcUt281L3gxILYRLY1DUNul5KjslERlE', '8765432', 1, 'Rapat Monitoring dan Evaluasi Bidang Kesekretariatan', 'Monitoring dan Evaluasi Bidang Kesekretariatan Bulanan', '2025-09-03', '09:25:00', 'PTA Papua Barat', 1, 1, 'akan_datang', '2025-09-03 00:23:41', '2025-09-03 00:23:41'),
(11, 'chtPkAX1rYzQOCTLr3euXLfnXIyJG9Rg', '3454745u562', 1, 'coba fitur queue', 'mencoba fitur queue apakah berfungsi atau tidak', '2025-09-10', '09:30:00', 'Ruang Kesekretariatan PTA Papua Barat', 1, 2, 'akan_datang', '2025-09-03 00:29:06', '2025-09-03 00:29:06'),
(12, '9uqqZokmZqMRmHajBjLIK1S48B6YX0Rh', '4583745934769', 4, 'coba fitur queue', 'mencoba fitur antrian undangan', '2025-09-03', '09:50:00', 'PTA Papua Barat', 1, 1, 'akan_datang', '2025-09-03 00:47:54', '2025-09-03 00:47:54'),
(13, 'WCq7tQSydTs8RIGMXqEEr1yZck50Jn5b', '42348023840', 2, 'mencoba fitur queue ke 1', 'coba dulu ya gaes', '2025-09-05', '21:35:00', 'PTA Papua Barat', 1, 1, 'akan_datang', '2025-09-05 12:34:06', '2025-09-05 12:34:06'),
(14, '1PyUMX91gDqKAsX4EhzkYJOoHAMCxmnr', '534535345', 1, 'mencoba fitur queue ke 2', 'coba dulu lagi', '2025-09-12', '21:49:00', 'PTA Papua Barat', 1, 2, 'akan_datang', '2025-09-05 12:45:38', '2025-09-06 11:35:58'),
(15, 'niaQWUi2RY41aYKh2NbSgo5IRw9oHmKx', '2340802850580', 5, 'mencoba fitur queue ke 3', 'coba lagiiiiii huuft', '2025-09-12', '21:50:00', 'PTA Papua Barat', 1, 1, 'akan_datang', '2025-09-05 12:48:55', '2025-09-05 12:48:55'),
(16, 'HOKGfLwS00EVXEgCaC1mvzDkNnWolPT2', '4235080680', 2, 'Mencoba fitur wa', 'Testing fitur wa satu user', '2025-09-12', '12:14:00', 'PTA Papua Barat', 1, 1, 'akan_datang', '2025-09-06 03:08:41', '2025-09-06 03:08:41'),
(17, 'HrdwLE9e1NVAzYGUAbs6J55fZV4iStEb', '035048308', 1, 'Mencoba fitur wa ke 2', 'coba fitur wa dengan notif dan arah ke sistem', '2025-09-06', '18:10:00', 'Ruang Kesekretariatan PTA Papua Barat', 1, 1, 'akan_datang', '2025-09-06 09:08:59', '2025-09-06 09:08:59'),
(19, '9eh5MLgNP62aOhBU1qVirNT0MBnYXwvj', '2385038028-', 3, 'testing fitur create pop up', 'coba fitur create pop up apakah bisa atau tidak', '2025-09-06', '19:25:00', 'PTA PAPUA BARAT', 1, 1, 'akan_datang', '2025-09-06 10:24:27', '2025-09-06 10:24:47'),
(20, '4OEKKw3GWH0zxJamJXJjNdJUelE6nI2Y', '438508602', 1, 'coba fitur pop up lagi', 'testing fitur pop up', '2025-09-06', '19:30:00', 'PTA Papua Barat', 1, 1, 'akan_datang', '2025-09-06 10:25:51', '2025-09-06 10:25:51'),
(21, 'XIjUqNj5R1cj7MoMXsLrgb5IefF2BYnr', '4050376080', 1, 'Testing', 'coba coba', '2025-09-06', '20:38:00', 'PTA Papua Barat', 1, 1, 'akan_datang', '2025-09-06 11:36:35', '2025-09-06 11:36:35'),
(22, 'kXMyZk4hWvWIE7wgdMAjS7M4XBBc4wGR', '03458036820', 3, 'Testing fitur pilihan peserta', 'coba coba', '2025-09-06', '21:43:00', 'PTA Papua Barat', 1, 1, 'akan_datang', '2025-09-06 12:37:56', '2025-09-06 12:37:56'),
(23, 'XLeOMacuMvJqFrzqS2N5L8J94BLKjSTX', '28402750280', 3, 'mencoba fitur drop down user', 'semoga kali ini bisa.', '2025-09-07', '10:20:00', 'PTA Papua Barat', 1, 1, 'akan_datang', '2025-09-07 01:18:19', '2025-09-07 01:18:19'),
(24, 'KPWFZ2O3Bs02UePNzBXsHRFFvn6xdZrb', '34259350580', 2, 'mencoba kembali fitur undangan', 'coba coba lagi gaes', '2025-09-08', '13:52:00', 'PTA Papua Barat', 1, 1, 'akan_datang', '2025-09-07 04:50:17', '2025-09-07 04:50:17');

-- --------------------------------------------------------

--
-- Table structure for table `undangan`
--

CREATE TABLE `undangan` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `id_rapat` bigint(20) UNSIGNED NOT NULL,
  `id_user` bigint(20) UNSIGNED NOT NULL,
  `status` enum('terkirim','diterima','dibaca') NOT NULL DEFAULT 'terkirim',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `undangan`
--

INSERT INTO `undangan` (`id`, `id_rapat`, `id_user`, `status`, `created_at`, `updated_at`) VALUES
(17, 2, 3, 'terkirim', '2025-08-24 01:10:15', '2025-08-24 01:10:15'),
(18, 2, 4, 'terkirim', '2025-08-24 01:10:15', '2025-08-24 01:10:15'),
(19, 3, 3, 'terkirim', '2025-08-24 01:10:58', '2025-08-24 01:10:58'),
(20, 3, 4, 'terkirim', '2025-08-24 01:10:58', '2025-08-24 01:10:58'),
(25, 6, 3, 'terkirim', '2025-08-24 01:11:28', '2025-08-24 01:11:28'),
(26, 6, 4, 'terkirim', '2025-08-24 01:11:28', '2025-08-24 01:11:28'),
(29, 1, 3, 'terkirim', '2025-08-24 01:15:04', '2025-08-24 01:15:04'),
(32, 4, 3, 'terkirim', '2025-08-28 06:15:55', '2025-08-28 06:15:55'),
(33, 4, 4, 'terkirim', '2025-08-28 06:15:55', '2025-08-28 06:15:55'),
(36, 7, 3, 'terkirim', '2025-08-28 06:16:18', '2025-08-28 06:16:18'),
(37, 7, 4, 'terkirim', '2025-08-28 06:16:18', '2025-08-28 06:16:18'),
(38, 5, 3, 'terkirim', '2025-08-28 06:16:30', '2025-08-28 06:16:30'),
(39, 5, 4, 'terkirim', '2025-08-28 06:16:30', '2025-08-28 06:16:30'),
(44, 9, 3, 'terkirim', '2025-09-02 23:43:57', '2025-09-02 23:43:57'),
(45, 9, 4, 'terkirim', '2025-09-02 23:44:05', '2025-09-02 23:44:05'),
(46, 8, 3, 'terkirim', '2025-09-03 00:04:51', '2025-09-03 00:04:51'),
(47, 8, 4, 'terkirim', '2025-09-03 00:04:51', '2025-09-03 00:04:51'),
(48, 10, 3, 'terkirim', '2025-09-03 00:23:41', '2025-09-03 00:23:41'),
(49, 11, 3, 'terkirim', '2025-09-03 00:29:06', '2025-09-03 00:29:06'),
(50, 12, 3, 'terkirim', '2025-09-03 00:47:54', '2025-09-03 00:47:54'),
(51, 13, 3, 'terkirim', '2025-09-05 12:34:06', '2025-09-05 12:34:06'),
(54, 15, 3, 'terkirim', '2025-09-05 12:48:55', '2025-09-05 12:48:55'),
(55, 15, 4, 'terkirim', '2025-09-05 12:48:55', '2025-09-05 12:48:55'),
(56, 16, 3, 'terkirim', '2025-09-06 03:08:41', '2025-09-06 03:08:41'),
(57, 16, 4, 'terkirim', '2025-09-06 03:08:41', '2025-09-06 03:08:41'),
(58, 17, 3, 'terkirim', '2025-09-06 09:08:59', '2025-09-06 09:08:59'),
(59, 17, 4, 'terkirim', '2025-09-06 09:09:05', '2025-09-06 09:09:05'),
(63, 19, 3, 'terkirim', '2025-09-06 10:24:47', '2025-09-06 10:24:47'),
(64, 19, 4, 'terkirim', '2025-09-06 10:24:47', '2025-09-06 10:24:47'),
(65, 20, 3, 'terkirim', '2025-09-06 10:25:51', '2025-09-06 10:25:51'),
(66, 20, 4, 'terkirim', '2025-09-06 10:25:51', '2025-09-06 10:25:51'),
(68, 14, 4, 'terkirim', '2025-09-06 11:35:58', '2025-09-06 11:35:58'),
(69, 21, 4, 'terkirim', '2025-09-06 11:36:35', '2025-09-06 11:36:35'),
(70, 22, 3, 'terkirim', '2025-09-06 12:37:56', '2025-09-06 12:37:56'),
(71, 22, 4, 'terkirim', '2025-09-06 12:37:56', '2025-09-06 12:37:56'),
(72, 23, 3, 'terkirim', '2025-09-07 01:18:19', '2025-09-07 01:18:19'),
(73, 23, 4, 'terkirim', '2025-09-07 01:18:25', '2025-09-07 01:18:25'),
(74, 24, 3, 'terkirim', '2025-09-07 04:50:17', '2025-09-07 04:50:17'),
(75, 24, 4, 'terkirim', '2025-09-07 04:50:23', '2025-09-07 04:50:23');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `no_hp` varchar(20) DEFAULT NULL,
  `jabatan` varchar(255) DEFAULT NULL,
  `email_verified_at` timestamp NULL DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('admin','notulis','peserta') NOT NULL DEFAULT 'peserta',
  `remember_token` varchar(100) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `name`, `email`, `no_hp`, `jabatan`, `email_verified_at`, `password`, `role`, `remember_token`, `created_at`, `updated_at`) VALUES
(1, 'Admin Sistem', 'admin@sistemrapat.test', NULL, NULL, NULL, '$2y$10$5i7o5CoHbL.6IQsNCz22R.gqfTyjMCTeF6UFV3d8yEb4quPkXJp8m', 'admin', NULL, '2025-08-23 00:08:00', '2025-08-23 00:08:00'),
(2, 'Notulis Satu', 'notulis@sistemrapat.test', NULL, NULL, NULL, '$2y$10$PNztkyptCNqzdPpcscnse.NsCpdymtMx9xZwKJG/CPDwMRpV28qUa', 'notulis', NULL, '2025-08-23 00:08:00', '2025-08-23 00:08:00'),
(3, 'Syamsul Bahri, S.H.I.', 'peserta@sistemrapat.test', NULL, 'Kepala Bagian Perencanaan dan Kepegawaian', NULL, '$2y$10$tLBMl2Ts9auu5siiikLBlOspFkdHpc8.QYfpyDaC9q9MKAiEi1WB.', 'peserta', NULL, '2025-08-23 00:08:00', '2025-08-23 03:38:31'),
(4, 'Muhjar', 'muhjardani0900@gmail.com', '081240170314', 'CPNS', NULL, '$2y$10$TrdGf1c3Bgk/GDfxQflIruf3PAiQ3ibXPvGtfdYBDr.RzVqSVvDfa', 'peserta', NULL, '2025-08-23 01:54:44', '2025-08-23 03:37:51');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `absensi`
--
ALTER TABLE `absensi`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `absensi_unique_rapat_user` (`id_rapat`,`id_user`),
  ADD KEY `absensi_id_user_foreign` (`id_user`);

--
-- Indexes for table `failed_jobs`
--
ALTER TABLE `failed_jobs`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `jobs`
--
ALTER TABLE `jobs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `jobs_queue_index` (`queue`);

--
-- Indexes for table `kategori_rapat`
--
ALTER TABLE `kategori_rapat`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `laporan_archived_meetings`
--
ALTER TABLE `laporan_archived_meetings`
  ADD PRIMARY KEY (`id`),
  ADD KEY `laporan_archived_meetings_archived_by_foreign` (`archived_by`),
  ADD KEY `laporan_archived_meetings_rapat_id_index` (`rapat_id`);

--
-- Indexes for table `laporan_files`
--
ALTER TABLE `laporan_files`
  ADD PRIMARY KEY (`id`),
  ADD KEY `laporan_files_id_kategori_index` (`id_kategori`);

--
-- Indexes for table `migrations`
--
ALTER TABLE `migrations`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `notulensi`
--
ALTER TABLE `notulensi`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `notulensi_detail`
--
ALTER TABLE `notulensi_detail`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `notulensi_dokumentasi`
--
ALTER TABLE `notulensi_dokumentasi`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `password_resets`
--
ALTER TABLE `password_resets`
  ADD KEY `password_resets_email_index` (`email`);

--
-- Indexes for table `pimpinan_rapat`
--
ALTER TABLE `pimpinan_rapat`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `rapat`
--
ALTER TABLE `rapat`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `undangan`
--
ALTER TABLE `undangan`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `undangan_unique_rapat_user` (`id_rapat`,`id_user`),
  ADD KEY `undangan_id_user_foreign` (`id_user`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `users_email_unique` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `absensi`
--
ALTER TABLE `absensi`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `failed_jobs`
--
ALTER TABLE `failed_jobs`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `jobs`
--
ALTER TABLE `jobs`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `kategori_rapat`
--
ALTER TABLE `kategori_rapat`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `laporan_archived_meetings`
--
ALTER TABLE `laporan_archived_meetings`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `laporan_files`
--
ALTER TABLE `laporan_files`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=40;

--
-- AUTO_INCREMENT for table `migrations`
--
ALTER TABLE `migrations`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=30;

--
-- AUTO_INCREMENT for table `notulensi`
--
ALTER TABLE `notulensi`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `notulensi_detail`
--
ALTER TABLE `notulensi_detail`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=34;

--
-- AUTO_INCREMENT for table `notulensi_dokumentasi`
--
ALTER TABLE `notulensi_dokumentasi`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT for table `pimpinan_rapat`
--
ALTER TABLE `pimpinan_rapat`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `rapat`
--
ALTER TABLE `rapat`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=25;

--
-- AUTO_INCREMENT for table `undangan`
--
ALTER TABLE `undangan`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=76;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `absensi`
--
ALTER TABLE `absensi`
  ADD CONSTRAINT `absensi_id_rapat_foreign` FOREIGN KEY (`id_rapat`) REFERENCES `rapat` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `absensi_id_user_foreign` FOREIGN KEY (`id_user`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `laporan_archived_meetings`
--
ALTER TABLE `laporan_archived_meetings`
  ADD CONSTRAINT `laporan_archived_meetings_archived_by_foreign` FOREIGN KEY (`archived_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `laporan_archived_meetings_rapat_id_foreign` FOREIGN KEY (`rapat_id`) REFERENCES `rapat` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `undangan`
--
ALTER TABLE `undangan`
  ADD CONSTRAINT `undangan_id_rapat_foreign` FOREIGN KEY (`id_rapat`) REFERENCES `rapat` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `undangan_id_user_foreign` FOREIGN KEY (`id_user`) REFERENCES `users` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
