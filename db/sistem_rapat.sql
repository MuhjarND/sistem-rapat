-- phpMyAdmin SQL Dump
-- version 5.2.0
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Waktu pembuatan: 22 Sep 2025 pada 03.05
-- Versi server: 10.4.27-MariaDB
-- Versi PHP: 7.4.33

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
-- Struktur dari tabel `absensi`
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
-- Dumping data untuk tabel `absensi`
--

INSERT INTO `absensi` (`id`, `id_rapat`, `id_user`, `status`, `waktu_absen`, `created_at`, `updated_at`) VALUES
(1, 2, 6, 'hadir', '2025-09-18 07:19:53', '2025-09-18 07:19:53', '2025-09-18 07:19:53');

-- --------------------------------------------------------

--
-- Struktur dari tabel `approval_requests`
--

CREATE TABLE `approval_requests` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `rapat_id` bigint(20) UNSIGNED NOT NULL,
  `doc_type` enum('undangan','absensi','notulensi') NOT NULL,
  `approver_user_id` bigint(20) UNSIGNED NOT NULL,
  `order_index` tinyint(3) UNSIGNED NOT NULL DEFAULT 1,
  `status` enum('pending','approved','rejected') NOT NULL DEFAULT 'pending',
  `signature_qr_path` varchar(255) DEFAULT NULL,
  `signature_payload` text DEFAULT NULL,
  `signed_at` timestamp NULL DEFAULT NULL,
  `sign_token` varchar(64) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data untuk tabel `approval_requests`
--

INSERT INTO `approval_requests` (`id`, `rapat_id`, `doc_type`, `approver_user_id`, `order_index`, `status`, `signature_qr_path`, `signature_payload`, `signed_at`, `sign_token`, `created_at`, `updated_at`) VALUES
(1, 1, 'undangan', 5, 1, 'approved', 'qr/qr_undangan_r1_a5_fQhe82.png', '{\"v\":1,\"doc_type\":\"undangan\",\"rapat_id\":1,\"nomor\":\"t4qtqtqt\",\"judul\":\"Rapat Monitoring dan Evaluasi\",\"tanggal\":\"2025-09-19\",\"approver\":{\"id\":5,\"name\":\"Gilang\",\"jabatan\":\"Analis APBN\",\"order\":1},\"issued_at\":\"2025-09-18T15:11:27+09:00\",\"nonce\":\"PX4OMNAmbXgiPQcd\",\"sig\":\"081039cf50047c7afb39e6ada26f8c47e89a190190d1554a0abd76522cbd8069\"}', '2025-09-18 06:11:29', 'kH6eTu2q2Sh8qFLq4fn1byLvnDJRaM6lorSzIoqaonNjvrtl', '2025-09-18 06:10:11', '2025-09-18 06:11:29'),
(2, 1, 'undangan', 4, 2, 'approved', 'qr/qr_undangan_r1_a4_sud9Zd.png', '{\"v\":1,\"doc_type\":\"undangan\",\"rapat_id\":1,\"nomor\":\"t4qtqtqt\",\"judul\":\"Rapat Monitoring dan Evaluasi\",\"tanggal\":\"2025-09-19\",\"approver\":{\"id\":4,\"name\":\"Luthfi\",\"jabatan\":\"Ketua PTA\",\"order\":2},\"issued_at\":\"2025-09-18T15:11:51+09:00\",\"nonce\":\"hu0mKnhboFYEFs3o\",\"sig\":\"5def718e6548a9780c5fb3253b7b561b05c1afe9b50d2cc77d1a47e06b5b9ca3\"}', '2025-09-18 06:11:53', 'p9xpNbjb2dEzHFMLaFeixgsZM8z1X8WAMno3p6MrG6oz5UQO', '2025-09-18 06:10:11', '2025-09-18 06:11:53'),
(3, 2, 'undangan', 4, 1, 'approved', 'qr/qr_undangan_r2_a4_umwInm.png', '{\"v\":1,\"doc_type\":\"undangan\",\"rapat_id\":2,\"nomor\":\"523850208\",\"judul\":\"coba fitur\",\"tanggal\":\"2025-09-20\",\"approver\":{\"id\":4,\"name\":\"Luthfi\",\"jabatan\":\"Ketua PTA\",\"order\":1},\"issued_at\":\"2025-09-18T16:15:21+09:00\",\"nonce\":\"qTLqBOhBUxt0U2QF\",\"sig\":\"26aa2bec6a6d74ae42b6c9c84f744e1ced80cb66836de532af7c8f6865df5e75\"}', '2025-09-18 07:15:24', '0B1y23mUVFgDpHRfarOmSGGH5TKrftN8FHfjoJ30Zdx8nIHs', '2025-09-18 06:57:20', '2025-09-18 07:15:24'),
(4, 3, 'undangan', 4, 1, 'approved', 'qr/qr_undangan_r3_a4_LPiwg1.png', '{\"v\":1,\"doc_type\":\"undangan\",\"rapat_id\":3,\"nomor\":\"529834793597\",\"judul\":\"rakor\",\"tanggal\":\"2025-09-19\",\"approver\":{\"id\":4,\"name\":\"Luthfi\",\"jabatan\":\"Ketua PTA\",\"order\":1},\"issued_at\":\"2025-09-18T16:15:29+09:00\",\"nonce\":\"McVxWNR8l3Q39e4H\",\"sig\":\"540712a150c71090f5c1008f770c9888c64cfff9f46c139acad197ab7b7c3899\"}', '2025-09-18 07:15:31', '6Iak91OZVQVCTbflxbMD4VFJcSz61EnIUdjjGuBwVC1OBIuh', '2025-09-18 07:03:48', '2025-09-18 07:15:31'),
(5, 4, 'undangan', 4, 1, 'approved', 'qr/qr_undangan_r4_a4_TE1DMC.png', '{\"v\":1,\"doc_type\":\"undangan\",\"rapat_id\":4,\"nomor\":\"57295026800\",\"judul\":\"Testing qr absensi\",\"tanggal\":\"2025-09-20\",\"approver\":{\"id\":4,\"name\":\"Luthfi\",\"jabatan\":\"Ketua PTA\",\"order\":1},\"issued_at\":\"2025-09-19T08:36:01+09:00\",\"nonce\":\"NzBTuLT9efBNH8eY\",\"sig\":\"1684dc0971706840ad9b5f244405ca4e0f97277092a3042197ee34ced509ed6f\"}', '2025-09-18 23:36:03', 'nhvtWvRups0Tyd7ENBcA6oATcPFtJMUSGdLkkHKnhwWplJIl', '2025-09-18 23:34:59', '2025-09-18 23:36:03'),
(6, 4, 'absensi', 4, 1, 'approved', 'qr/qr_absensi_r4_a4_xriRta.png', '{\"v\":1,\"doc_type\":\"absensi\",\"rapat_id\":4,\"nomor\":\"57295026800\",\"judul\":\"Testing qr absensi\",\"tanggal\":\"2025-09-20\",\"derived\":{\"from\":\"undangan\",\"chain_sig\":\"6469302f6c3572751cc6204934153728aa2f3872899c367cecf48863c4c7c383\"},\"approver\":{\"id\":4,\"name\":\"Luthfi\",\"jabatan\":\"Ketua PTA\",\"role\":\"final\"},\"issued_at\":\"2025-09-19T08:36:14+09:00\",\"nonce\":\"UDwxOC2oyB5JGzb7Lp\",\"sig\":\"1594e2a1b10a7171d8a079031b221b54f7f426bcc12c7a6055714ee49fe17d7b\"}', '2025-09-18 23:36:16', 'tMNQpEOaVBeohpoLSZih5ez57YIrBH5U', '2025-09-18 23:36:16', '2025-09-18 23:36:16'),
(7, 5, 'undangan', 5, 1, 'approved', 'qr/qr_undangan_r5_a5_8VMVcs.png', '{\"v\":1,\"doc_type\":\"undangan\",\"rapat_id\":5,\"nomor\":\"3r45234234\",\"judul\":\"Testing fitur qr code absensi\",\"tanggal\":\"2025-09-20\",\"approver\":{\"id\":5,\"name\":\"Gilang\",\"jabatan\":\"Analis APBN\",\"order\":1},\"issued_at\":\"2025-09-19T09:59:30+09:00\",\"nonce\":\"wez98s8OgiwzxMbk\",\"sig\":\"bedf2948dd3b482a0c45570d00a4befe05cd50e74c380c610337eb5358757d36\"}', '2025-09-19 00:59:33', 'HqAQx08S6R5djuYhqMVuOrbXQbGXaI0DwuX1HCbdqe0efoqq', '2025-09-19 00:58:49', '2025-09-19 00:59:33'),
(8, 5, 'absensi', 5, 1, 'approved', 'qr/qr_absensi_r5_a5_aeDvDa.png', '{\"v\":1,\"doc_type\":\"absensi\",\"rapat_id\":5,\"nomor\":\"3r45234234\",\"judul\":\"Testing fitur qr code absensi\",\"tanggal\":\"2025-09-20\",\"derived\":{\"from\":\"undangan\",\"chain_sig\":\"78bc446221fdae54e9bd81997328b75f4611e08a645580657793e022798507c6\"},\"approver\":{\"id\":5,\"name\":\"Gilang\",\"jabatan\":\"Analis APBN\",\"role\":\"final\"},\"issued_at\":\"2025-09-19T09:59:55+09:00\",\"nonce\":\"cvQurd7YKQIbBt0UWy\",\"sig\":\"cc5c448051b2ebc45148282dadd1f364e04d879c575d68177a8d022abd917d27\"}', '2025-09-19 00:59:58', '8tBjHVQ34RsscsKEHfB3Crr4vtLjGX9C', '2025-09-19 00:59:58', '2025-09-19 00:59:58'),
(9, 6, 'undangan', 4, 1, 'approved', 'qr/qr_undangan_r6_a4_fl8iM5.png', '{\"v\":1,\"doc_type\":\"undangan\",\"rapat_id\":6,\"nomor\":\"6038530750927\",\"judul\":\"Testing fitur QR Absensi Ke 2\",\"tanggal\":\"2025-09-21\",\"approver\":{\"id\":4,\"name\":\"Luthfi\",\"jabatan\":\"Ketua PTA\",\"order\":1},\"issued_at\":\"2025-09-19T13:23:37+09:00\",\"nonce\":\"4HkFQ4rGFk20dtSk\",\"sig\":\"aca7020f1d930d77e8a56b2af4fd780114ea43caf68a81ebccf56393ea90ff06\"}', '2025-09-19 04:23:43', 'XNvlfJsGofMGlW2Ydl9g3Y4JL5kybXpr9cXMaQ31RWO4HMqa', '2025-09-19 04:22:44', '2025-09-19 04:23:43'),
(10, 6, 'absensi', 4, 1, 'approved', 'qr/qr_absensi_r6_a4_xK6etJ.png', '{\"v\":1,\"doc_type\":\"absensi\",\"rapat_id\":6,\"nomor\":\"6038530750927\",\"judul\":\"Testing fitur QR Absensi Ke 2\",\"tanggal\":\"2025-09-21\",\"approver\":{\"id\":4,\"name\":\"Luthfi\",\"jabatan\":\"Ketua PTA\",\"order\":1},\"issued_at\":\"2025-09-19T13:23:49+09:00\",\"nonce\":\"2elIaeUo335xqar4\",\"sig\":\"ed6ba8212ccc3c2b0b85b8674dc66ce54e03d68f34dc9cf91c85a626bf5b96e1\"}', '2025-09-19 04:23:51', 'O8iGg28E46kQhfzXDp2ObRVAQmuFiIOHP49vA8bQFohaHeq4', '2025-09-19 04:22:45', '2025-09-19 04:23:51'),
(11, 7, 'undangan', 5, 1, 'approved', 'qr/qr_undangan_r7_a5_FRFL9h.png', '{\"v\":1,\"doc_type\":\"undangan\",\"rapat_id\":7,\"nomor\":\"34535255\",\"judul\":\"testing fitur\",\"tanggal\":\"2025-09-21\",\"approver\":{\"id\":5,\"name\":\"Gilang\",\"jabatan\":\"Analis APBN\",\"order\":1},\"issued_at\":\"2025-09-19T14:05:40+09:00\",\"nonce\":\"RwuAzp4eyrbuyTIe\",\"sig\":\"df0808f92dcda59aae8281f15eae7cfeee5d686c34c8993e1af03a02efd23d71\"}', '2025-09-19 05:05:42', '6EClxKat7dGM95lsMloHid6iLBdtI1qXFa50CvGMc0R4tXKh', '2025-09-19 05:04:37', '2025-09-19 05:05:42'),
(12, 7, 'undangan', 4, 2, 'approved', 'qr/qr_undangan_r7_a4_sPNtYG.png', '{\"v\":1,\"doc_type\":\"undangan\",\"rapat_id\":7,\"nomor\":\"34535255\",\"judul\":\"testing fitur\",\"tanggal\":\"2025-09-21\",\"approver\":{\"id\":4,\"name\":\"Luthfi\",\"jabatan\":\"Ketua PTA\",\"order\":2},\"issued_at\":\"2025-09-19T14:06:20+09:00\",\"nonce\":\"fxQU6Jz4ZhG4meLh\",\"sig\":\"79fce5548dd3f9ef92f20708d5758f7531e57d4267e0d0633232e457a8bdea1f\"}', '2025-09-19 05:06:22', 'L6ufqXb8IGWLMbDgLzUdiA7yhgChePUJrRzQv0cfDNs3dZQV', '2025-09-19 05:04:37', '2025-09-19 05:06:22'),
(13, 7, 'absensi', 5, 1, 'approved', 'qr/qr_absensi_r7_a5_n3rLFT.png', '{\"v\":1,\"doc_type\":\"absensi\",\"rapat_id\":7,\"nomor\":\"34535255\",\"judul\":\"testing fitur\",\"tanggal\":\"2025-09-21\",\"approver\":{\"id\":5,\"name\":\"Gilang\",\"jabatan\":\"Analis APBN\",\"order\":1},\"issued_at\":\"2025-09-19T14:05:48+09:00\",\"nonce\":\"recBPMnPdtwD1zcL\",\"sig\":\"b50c2c98c73677af8ac10e16184421a1a2c381664f4af680c816feed2f3192da\"}', '2025-09-19 05:05:50', 'FP12mYR3xOkpfN4R3yTKuc26LrH2XptOMifLL7TmvpOqsYLE', '2025-09-19 05:04:37', '2025-09-19 05:05:50'),
(14, 7, 'absensi', 4, 2, 'approved', 'qr/qr_absensi_r7_a4_aeM7uI.png', '{\"v\":1,\"doc_type\":\"absensi\",\"rapat_id\":7,\"nomor\":\"34535255\",\"judul\":\"testing fitur\",\"tanggal\":\"2025-09-21\",\"approver\":{\"id\":4,\"name\":\"Luthfi\",\"jabatan\":\"Ketua PTA\",\"order\":2},\"issued_at\":\"2025-09-19T14:06:28+09:00\",\"nonce\":\"rSVRXvHERFzPAtxG\",\"sig\":\"2a45ceb93002e2e9d8bb3d3f32c42b358ea6b5e4a383aa0c5c993a77b97dddad\"}', '2025-09-19 05:06:30', 'eKjQupIOBzEz0hdEN6kb0U5Hz5OgsUmqeWacLHBglFS3LEjj', '2025-09-19 05:04:37', '2025-09-19 05:06:30'),
(15, 8, 'undangan', 4, 1, 'approved', 'qr/qr_undangan_r8_a4_GJTUoW.png', '{\"v\":1,\"doc_type\":\"undangan\",\"rapat_id\":8,\"nomor\":\"5248096830\",\"judul\":\"coba 2 lampiran\",\"tanggal\":\"2025-09-22\",\"approver\":{\"id\":4,\"name\":\"Luthfi\",\"jabatan\":\"Ketua PTA\",\"order\":1},\"issued_at\":\"2025-09-19T14:11:02+09:00\",\"nonce\":\"Csu0E4dkTSztXsx7\",\"sig\":\"e7fda64bed63ae637a3a116ebca7c16b5254e292f0df898c1cf47f0298bda3bb\"}', '2025-09-19 05:11:04', '3QNWRwKeeNcNY1priph31diP7vErLsMYp6v6wx7w2UO6WMCY', '2025-09-19 05:10:06', '2025-09-19 05:11:04'),
(16, 8, 'absensi', 4, 1, 'approved', 'qr/qr_absensi_r8_a4_t5YGZ4.png', '{\"v\":1,\"doc_type\":\"absensi\",\"rapat_id\":8,\"nomor\":\"5248096830\",\"judul\":\"coba 2 lampiran\",\"tanggal\":\"2025-09-22\",\"approver\":{\"id\":4,\"name\":\"Luthfi\",\"jabatan\":\"Ketua PTA\",\"order\":1},\"issued_at\":\"2025-09-19T14:11:10+09:00\",\"nonce\":\"1yGp4iJ65lWjJjnM\",\"sig\":\"50c5e80bce552822147b7fd1e520d838753ac0694a02c7b1409256dbc912b679\"}', '2025-09-19 05:11:12', 'lyKNNSmS9c5eMCr14bdwDDC6b1LfhTcwtfzBWrYojFCUo44N', '2025-09-19 05:10:06', '2025-09-19 05:11:12'),
(17, 9, 'undangan', 4, 1, 'approved', 'qr/qr_undangan_r9_a4_PWl815.png', '{\"v\":1,\"doc_type\":\"undangan\",\"rapat_id\":9,\"nomor\":\"57q239589308\",\"judul\":\"Testing lampiran 2\",\"tanggal\":\"2025-09-23\",\"approver\":{\"id\":4,\"name\":\"Luthfi\",\"jabatan\":\"Ketua PTA\",\"order\":1},\"issued_at\":\"2025-09-19T14:13:31+09:00\",\"nonce\":\"5oVWbIx8k1mKFCrd\",\"sig\":\"bbdb93bd328a1e564ffdc71f9cfe1e7fc8d10515a59d61e4b6ad0242ac5cbabe\"}', '2025-09-19 05:13:33', 'kdlwbyj9soFKlo37FZE0cCzz8855eXVfhu6LKJJEPg9nydH5', '2025-09-19 05:13:10', '2025-09-19 05:13:33'),
(18, 9, 'absensi', 4, 1, 'approved', 'qr/qr_absensi_r9_a4_0TOU4p.png', '{\"v\":1,\"doc_type\":\"absensi\",\"rapat_id\":9,\"nomor\":\"57q239589308\",\"judul\":\"Testing lampiran 2\",\"tanggal\":\"2025-09-23\",\"approver\":{\"id\":4,\"name\":\"Luthfi\",\"jabatan\":\"Ketua PTA\",\"order\":1},\"issued_at\":\"2025-09-19T14:13:39+09:00\",\"nonce\":\"2Hd9HnFpcWYoJISj\",\"sig\":\"610de59be1a60b4431b542b4df3dffa85fabd61e923bb24842043a99fa358cf8\"}', '2025-09-19 05:13:41', 'ffDGFO6kZvtTpImMc4Pz3fB6XC0qOPnJU7EhIec1b2XmTcVH', '2025-09-19 05:13:10', '2025-09-19 05:13:41'),
(19, 10, 'undangan', 4, 1, 'approved', 'qr/qr_undangan_r10_a4_oYpzYc.png', '{\"v\":1,\"doc_type\":\"undangan\",\"rapat_id\":10,\"nomor\":\"375433096805\",\"judul\":\"Testing fitur lagi\",\"tanggal\":\"2025-09-23\",\"approver\":{\"id\":4,\"name\":\"Luthfi\",\"jabatan\":\"Ketua PTA\",\"order\":1},\"issued_at\":\"2025-09-19T14:29:53+09:00\",\"nonce\":\"odvwLCrnf1I8Iex8\",\"sig\":\"a3e5aaecfd44a147a3c24398e8d9abe1a161dc5153e6f2e345de4ba436866eb0\"}', '2025-09-19 05:29:56', 'Jh55H0YfuQz8gzKjhy5rd0TK0Vtd0rPgjo7iHyQSqyBbF4lu', '2025-09-19 05:29:41', '2025-09-19 05:29:56'),
(20, 10, 'absensi', 4, 1, 'approved', 'qr/qr_absensi_r10_a4_yL3z51.png', '{\"v\":1,\"doc_type\":\"absensi\",\"rapat_id\":10,\"nomor\":\"375433096805\",\"judul\":\"Testing fitur lagi\",\"tanggal\":\"2025-09-23\",\"approver\":{\"id\":4,\"name\":\"Luthfi\",\"jabatan\":\"Ketua PTA\",\"order\":1},\"issued_at\":\"2025-09-19T14:30:00+09:00\",\"nonce\":\"UotwxDrTXvriFjTq\",\"sig\":\"bd4af776717aeaf06a52c4ece49b7df167c267156fac6f92667cfb4682ad28fd\"}', '2025-09-19 05:30:02', 'D2umqGjhFjfYqoQwTVW7sd4Fu2VUq6EgtgKWHzU6Zw8DVuaD', '2025-09-19 05:29:41', '2025-09-19 05:30:02'),
(21, 11, 'undangan', 4, 1, 'approved', 'qr/qr_undangan_r11_a4_qDa1GW.png', '{\"v\":1,\"doc_type\":\"undangan\",\"rapat_id\":11,\"nomor\":\"534252395028\",\"judul\":\"jsflajflkakl\",\"tanggal\":\"2025-09-24\",\"approver\":{\"id\":4,\"name\":\"Luthfi\",\"jabatan\":\"Ketua PTA\",\"order\":1},\"issued_at\":\"2025-09-19T16:54:41+09:00\",\"nonce\":\"AgpdC4RZkeAhs2I3\",\"sig\":\"9da0b8f04af43d4bd7ce3818185c1dad39292f2900ed1cee723c51bd283862ff\"}', '2025-09-19 07:54:43', '3PELQ66ZgCmuVJ8kcfuyWXEwqbyDQJR2JREVGUMYOyYVRMwb', '2025-09-19 07:53:55', '2025-09-19 07:54:43'),
(22, 11, 'absensi', 4, 1, 'approved', 'qr/qr_absensi_r11_a4_1bu3on.png', '{\"v\":1,\"doc_type\":\"absensi\",\"rapat_id\":11,\"nomor\":\"534252395028\",\"judul\":\"jsflajflkakl\",\"tanggal\":\"2025-09-24\",\"approver\":{\"id\":4,\"name\":\"Luthfi\",\"jabatan\":\"Ketua PTA\",\"order\":1},\"issued_at\":\"2025-09-19T16:54:49+09:00\",\"nonce\":\"cyPVZP1VyVbfQiHJ\",\"sig\":\"2de34fdd35b93ed2cabbab10f0009438d95f1a50be8b2ef64083b4bd137fb27b\"}', '2025-09-19 07:54:51', '5gV1mRKwBpklLlWF0j4yjnhvcjyVGDzOi6P6RW1rWWaFC7X3', '2025-09-19 07:53:55', '2025-09-19 07:54:51'),
(23, 11, 'absensi', 4, 1, 'approved', 'qr/qr_absensi_r11_a4_kVYcIk.png', '{\"v\":1,\"doc_type\":\"absensi\",\"rapat_id\":11,\"nomor\":\"534252395028\",\"judul\":\"jsflajflkakl\",\"tanggal\":\"2025-09-24\",\"approver\":{\"id\":4,\"name\":\"Luthfi\",\"jabatan\":\"Ketua PTA\",\"order\":1},\"issued_at\":\"2025-09-19T16:54:43+09:00\",\"nonce\":\"yJz19FMeJZwQgUCz\",\"sig\":\"a153dc27e66eeb97e102b99759865c73c4af112e9e310829351ed782d1b0ea65\"}', '2025-09-19 07:54:44', 'd5tc9p5AwwGCABT6umWZJscCJ6wPbgxH', '2025-09-19 07:54:44', '2025-09-19 07:54:44'),
(24, 12, 'undangan', 4, 1, 'approved', 'qr/qr_undangan_r12_a4_K0B8yu.png', '{\"v\":1,\"doc_type\":\"undangan\",\"rapat_id\":12,\"nomor\":\"508028\",\"judul\":\"uiouioo\",\"tanggal\":\"2025-09-24\",\"approver\":{\"id\":4,\"name\":\"Luthfi\",\"jabatan\":\"Ketua PTA\",\"order\":1},\"issued_at\":\"2025-09-19T16:57:36+09:00\",\"nonce\":\"HWUyzitPfu7yojUg\",\"sig\":\"b2291cf82139e20df85cf36d7844e7da58a4302e9ec55ce75e0cea436b3f6788\"}', '2025-09-19 07:57:37', 'W7RXHgSatzIZ81ab3vHi5p3sOIMfgyswPRT57U7NTaE1hHRr', '2025-09-19 07:56:39', '2025-09-19 07:57:37'),
(25, 12, 'absensi', 4, 1, 'approved', 'qr/qr_absensi_r12_a4_ZkLUGe.png', '{\"v\":1,\"doc_type\":\"absensi\",\"rapat_id\":12,\"nomor\":\"508028\",\"judul\":\"uiouioo\",\"tanggal\":\"2025-09-24\",\"approver\":{\"id\":4,\"name\":\"Luthfi\",\"jabatan\":\"Ketua PTA\",\"order\":1},\"issued_at\":\"2025-09-19T16:57:37+09:00\",\"nonce\":\"bKa59DgvYi3pMc9r\",\"sig\":\"62ab9b6ef9557daa3c96aeb3436ab62a4a417fc72607e97f9abccc4392996004\"}', '2025-09-19 07:57:39', 'tU8ow27nLpsHGlwJLqhmUp5tjnsKXe0ZXc2ob1OO6W06o71s', '2025-09-19 07:56:39', '2025-09-19 07:57:39'),
(26, 13, 'undangan', 4, 1, 'approved', 'qr/qr_undangan_r13_a4_e6ZIN5.png', '{\"v\":1,\"doc_type\":\"undangan\",\"rapat_id\":13,\"nomor\":\"348403859080\",\"judul\":\"Rapat Koordinasi Internal\",\"tanggal\":\"2025-09-22\",\"approver\":{\"id\":4,\"name\":\"Luthfi\",\"jabatan\":\"Ketua PTA\",\"order\":1},\"issued_at\":\"2025-09-22T08:53:36+09:00\",\"nonce\":\"2Nv2gdO8xDrjpVJa\",\"sig\":\"28dbb738281d7b1cb27061e8a83ee48912895ef39e260fd625f4faba4c56bc14\"}', '2025-09-21 23:53:38', '1WJ73TEffKODB8PAXtrz4doY3lVgPDcAeNjvC1zx2h7MttJx', '2025-09-21 23:52:58', '2025-09-21 23:53:38'),
(27, 13, 'absensi', 4, 1, 'approved', 'qr/qr_absensi_r13_a4_CnTWHA.png', '{\"v\":1,\"doc_type\":\"absensi\",\"rapat_id\":13,\"nomor\":\"348403859080\",\"judul\":\"Rapat Koordinasi Internal\",\"tanggal\":\"2025-09-22\",\"derived\":{\"from\":\"undangan\",\"chain_sig\":\"ee18085cb265530cfe99e358777aab019d8297ffab3c17236d52c3bc5f47b127\"},\"approver\":{\"id\":4,\"name\":\"Luthfi\",\"jabatan\":\"Ketua PTA\",\"role\":\"final\"},\"issued_at\":\"2025-09-22T08:53:40+09:00\",\"nonce\":\"fCawOKFHfxByPBC2IS\",\"sig\":\"664d749255ba684df6a673f3beea1d7166c38432a4a652cf24da6ad18db66647\"}', '2025-09-21 23:53:41', 'NIeIARvY5HXjCIP6ThzwWb98wiuyA3yvdbvDbC2uJ01QZgk2', '2025-09-21 23:52:58', '2025-09-21 23:53:41'),
(28, 13, 'absensi', 4, 1, 'approved', 'qr/qr_absensi_r13_a4_dN4mwk.png', '{\"v\":1,\"doc_type\":\"absensi\",\"rapat_id\":13,\"nomor\":\"348403859080\",\"judul\":\"Rapat Koordinasi Internal\",\"tanggal\":\"2025-09-22\",\"approver\":{\"id\":4,\"name\":\"Luthfi\",\"jabatan\":\"Ketua PTA\",\"order\":1},\"issued_at\":\"2025-09-22T08:53:38+09:00\",\"nonce\":\"HB68ts4hbYpULig9\",\"sig\":\"69435f0374a919b5d07b205e348d4e5ceb5c393b2691c9866e81e24bac8e4114\"}', '2025-09-21 23:53:39', '4PIaRRxkowzOnptFdFUrQ3U3zlNrOkSY', '2025-09-21 23:53:39', '2025-09-21 23:53:39');

-- --------------------------------------------------------

--
-- Struktur dari tabel `failed_jobs`
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
-- Struktur dari tabel `jobs`
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
-- Struktur dari tabel `kategori_rapat`
--

CREATE TABLE `kategori_rapat` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `nama` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data untuk tabel `kategori_rapat`
--

INSERT INTO `kategori_rapat` (`id`, `nama`, `created_at`, `updated_at`) VALUES
(1, 'Monitoring dan Evaluasi', '2025-09-18 06:08:42', '2025-09-18 06:08:42'),
(2, 'Koordinasi Internal', '2025-09-18 06:08:56', '2025-09-18 06:08:56'),
(3, 'Koordinasi Nasional', '2025-09-18 06:09:10', '2025-09-18 06:09:10'),
(4, 'Kategori A', '2025-09-18 06:09:18', '2025-09-18 06:09:18'),
(5, 'Kategori B', '2025-09-18 06:09:24', '2025-09-18 06:09:24'),
(6, 'Kategori C', '2025-09-18 06:09:34', '2025-09-18 06:09:34');

-- --------------------------------------------------------

--
-- Struktur dari tabel `laporan_archived_meetings`
--

CREATE TABLE `laporan_archived_meetings` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `rapat_id` bigint(20) UNSIGNED NOT NULL,
  `archived_by` bigint(20) UNSIGNED DEFAULT NULL,
  `archived_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `laporan_files`
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

-- --------------------------------------------------------

--
-- Struktur dari tabel `migrations`
--

CREATE TABLE `migrations` (
  `id` int(10) UNSIGNED NOT NULL,
  `migration` varchar(255) NOT NULL,
  `batch` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data untuk tabel `migrations`
--

INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES
(1, '2014_10_12_000000_create_users_table', 1),
(2, '2014_10_12_100000_create_password_resets_table', 1),
(3, '2019_08_19_000000_create_failed_jobs_table', 1),
(4, '2025_08_23_090148_create_rapat_table', 1),
(5, '2025_08_23_090214_create_undangan_table', 1),
(6, '2025_08_23_090234_create_absensi_table', 1),
(7, '2025_08_23_090254_create_notulensi_table', 1),
(8, '2025_08_23_103529_tambah_nomor_undangan_di_rapat', 1),
(9, '2025_08_23_115916_create_pimpinan_rapat_table', 1),
(10, '2025_08_23_120008_tambah_id_pimpinan_di_rapat', 1),
(11, '2025_08_23_122845_tambah_jabatan_pada_users', 1),
(12, '2025_08_23_160700_tambah_status_pada_rapat', 1),
(13, '2025_08_23_174323_create_kategori_rapat_table', 1),
(14, '2025_08_23_174738_tambah_id_kategori_di_rapat', 1),
(15, '2025_08_25_091108_tambah_token_qr_di_rapat', 1),
(16, '2025_08_26_082836_create_notulensi_detail_table', 1),
(17, '2025_08_26_095313_add_dibuat_oleh_to_notulensi_table', 1),
(18, '2025_08_26_100357_modify_isi_nullable_in_notulensi', 1),
(19, '2025_08_26_101846_create_notulensi_dokumentasi_table', 1),
(20, '2025_08_27_144404_create_laporan_files_table', 1),
(21, '2025_08_27_152207_add_id_kategori_to_laporan_files_table', 1),
(22, '2025_09_03_091757_create_jobs_table', 1),
(23, '2025_09_05_222059_add_constraint_to_undangan_absensi', 1),
(24, '2025_09_05_222149_add_constraint_to_absensi', 1),
(25, '2025_09_06_114705_add_no_hp_to_users_table', 1),
(26, '2025_09_08_150228_add_is_archived_to_laporan_files_table', 1),
(27, '2025_09_08_150540_add_archived_at_to_laporan_files_table', 1),
(28, '2025_09_10_122949_create_laporan_archived_meetings_table', 1),
(29, '2025_09_16_133332_add_unit_to_users_table', 1),
(30, '2025_09_16_143058_add_tingkatan_to_users_table', 1),
(31, '2025_09_16_145230_add_approvals_columns_to_rapat_table', 1),
(32, '2025_09_16_145918_alter_role_enum_add_approval_in_users_table', 1),
(33, '2025_09_17_161406_create_approval_requests_table', 1),
(34, '2025_09_19_094920_add_absensi_qr_to_rapat_table', 2);

-- --------------------------------------------------------

--
-- Struktur dari tabel `notulensi`
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
-- Dumping data untuk tabel `notulensi`
--

INSERT INTO `notulensi` (`id`, `id_rapat`, `dibuat_oleh`, `id_user`, `isi`, `created_at`, `updated_at`) VALUES
(1, 2, NULL, 1, NULL, '2025-09-18 23:18:48', '2025-09-18 23:18:48');

-- --------------------------------------------------------

--
-- Struktur dari tabel `notulensi_detail`
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
-- Dumping data untuk tabel `notulensi_detail`
--

INSERT INTO `notulensi_detail` (`id`, `id_notulensi`, `urut`, `hasil_pembahasan`, `rekomendasi`, `penanggung_jawab`, `tgl_penyelesaian`, `created_at`, `updated_at`) VALUES
(1, 1, 1, '<ul><li>Membahas teknis pengisian survey setiap triwulan agar ada pembatasan dalam pengisian survey untuk meminimalisir nilai yang rendah. Misalnya masing-masing satker 8 orang.&nbsp;</li></ul>', '<ul><li>Membahas teknis pengisian survey setiap triwulan agar ada pembatasan dalam pengisian survey untuk meminimalisir nilai yang rendah. Misalnya masing-masing satker 8 orang.&nbsp;</li></ul>', NULL, '2025-09-20', '2025-09-18 23:18:48', '2025-09-18 23:18:48'),
(2, 1, 2, '<ul><li>Membahas teknis pengisian survey setiap triwulan agar ada pembatasan dalam pengisian survey untuk meminimalisir nilai yang rendah. Misalnya masing-masing satker 8 orang.&nbsp;</li></ul>', '<ul><li>Membahas teknis pengisian survey setiap triwulan agar ada pembatasan dalam pengisian survey untuk meminimalisir nilai yang rendah. Misalnya masing-masing satker 8 orang.&nbsp;</li></ul>', NULL, '2025-09-20', '2025-09-18 23:18:48', '2025-09-18 23:18:48'),
(3, 1, 3, '<ul><li>Membahas teknis pengisian survey setiap triwulan agar ada pembatasan dalam pengisian survey untuk meminimalisir nilai yang rendah. Misalnya masing-masing satker 8 orang.&nbsp;</li></ul>', '<ul><li>Membahas teknis pengisian survey setiap triwulan agar ada pembatasan dalam pengisian survey untuk meminimalisir nilai yang rendah. Misalnya masing-masing satker 8 orang.&nbsp;</li></ul>', NULL, '2025-09-20', '2025-09-18 23:18:48', '2025-09-18 23:18:48'),
(4, 1, 4, '<ul><li>Membahas teknis pengisian survey setiap triwulan agar ada pembatasan dalam pengisian survey untuk meminimalisir nilai yang rendah. Misalnya masing-masing satker 8 orang.&nbsp;</li></ul>', '<ul><li>Membahas teknis pengisian survey setiap triwulan agar ada pembatasan dalam pengisian survey untuk meminimalisir nilai yang rendah. Misalnya masing-masing satker 8 orang.&nbsp;</li></ul>', NULL, '2025-09-20', '2025-09-18 23:18:48', '2025-09-18 23:18:48'),
(5, 1, 5, '<ul><li>Membahas teknis pengisian survey setiap triwulan agar ada pembatasan dalam pengisian survey untuk meminimalisir nilai yang rendah. Misalnya masing-masing satker 8 orang.&nbsp;</li></ul>', '<ul><li>Membahas teknis pengisian survey setiap triwulan agar ada pembatasan dalam pengisian survey untuk meminimalisir nilai yang rendah. Misalnya masing-masing satker 8 orang.&nbsp;</li></ul>', NULL, '2025-09-20', '2025-09-18 23:18:48', '2025-09-18 23:18:48'),
(6, 1, 6, '<ul><li>Membahas teknis pengisian survey setiap triwulan agar ada pembatasan dalam pengisian survey untuk meminimalisir nilai yang rendah. Misalnya masing-masing satker 8 orang.&nbsp;</li></ul>', '<ul><li>Membahas teknis pengisian survey setiap triwulan agar ada pembatasan dalam pengisian survey untuk meminimalisir nilai yang rendah. Misalnya masing-masing satker 8 orang.&nbsp;</li></ul>', NULL, '2025-09-20', '2025-09-18 23:18:48', '2025-09-18 23:18:48'),
(7, 1, 7, '<ul><li>Membahas teknis pengisian survey setiap triwulan agar ada pembatasan dalam pengisian survey untuk meminimalisir nilai yang rendah. Misalnya masing-masing satker 8 orang.&nbsp;</li></ul>', '<ul><li>Membahas teknis pengisian survey setiap triwulan agar ada pembatasan dalam pengisian survey untuk meminimalisir nilai yang rendah. Misalnya masing-masing satker 8 orang.&nbsp;</li></ul>', NULL, '2025-09-20', '2025-09-18 23:18:48', '2025-09-18 23:18:48');

-- --------------------------------------------------------

--
-- Struktur dari tabel `notulensi_dokumentasi`
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
-- Dumping data untuk tabel `notulensi_dokumentasi`
--

INSERT INTO `notulensi_dokumentasi` (`id`, `id_notulensi`, `file_path`, `caption`, `created_at`, `updated_at`) VALUES
(1, 1, 'uploads/notulensi/WhatsApp-Image-2025-09-03-at-6-56-10-PM-68cc935867916.jpeg', NULL, '2025-09-18 23:18:48', '2025-09-18 23:18:48'),
(2, 1, 'uploads/notulensi/WhatsApp-Image-2025-09-03-at-6-56-14-PM-68cc93586b07e.jpeg', NULL, '2025-09-18 23:18:48', '2025-09-18 23:18:48'),
(3, 1, 'uploads/notulensi/WhatsApp-Image-2025-09-03-at-6-56-27-PM-68cc93586c2d5.jpeg', NULL, '2025-09-18 23:18:48', '2025-09-18 23:18:48');

-- --------------------------------------------------------

--
-- Struktur dari tabel `password_resets`
--

CREATE TABLE `password_resets` (
  `email` varchar(255) NOT NULL,
  `token` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `pimpinan_rapat`
--

CREATE TABLE `pimpinan_rapat` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `nama` varchar(255) NOT NULL,
  `jabatan` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `rapat`
--

CREATE TABLE `rapat` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `token_qr` varchar(64) DEFAULT NULL,
  `undangan_approved_at` timestamp NULL DEFAULT NULL,
  `absensi_approved_at` timestamp NULL DEFAULT NULL,
  `notulensi_approved_at` timestamp NULL DEFAULT NULL,
  `nomor_undangan` varchar(255) DEFAULT NULL,
  `id_kategori` bigint(20) UNSIGNED DEFAULT NULL,
  `approval1_user_id` bigint(20) UNSIGNED DEFAULT NULL,
  `approval2_user_id` bigint(20) UNSIGNED DEFAULT NULL,
  `absensi_qr_payload` text DEFAULT NULL,
  `absensi_qr_path` varchar(255) DEFAULT NULL,
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
-- Dumping data untuk tabel `rapat`
--

INSERT INTO `rapat` (`id`, `token_qr`, `undangan_approved_at`, `absensi_approved_at`, `notulensi_approved_at`, `nomor_undangan`, `id_kategori`, `approval1_user_id`, `approval2_user_id`, `absensi_qr_payload`, `absensi_qr_path`, `judul`, `deskripsi`, `tanggal`, `waktu_mulai`, `tempat`, `dibuat_oleh`, `id_pimpinan`, `status`, `created_at`, `updated_at`) VALUES
(1, 'ds5KIT7mjiOzmmRtdKv0qWbHyEIlI1HK', '2025-09-18 06:11:53', NULL, NULL, 't4qtqtqt', 1, 4, 5, NULL, NULL, 'Rapat Monitoring dan Evaluasi', 'monev', '2025-09-19', '15:10:00', 'PTA', 1, NULL, 'akan_datang', '2025-09-18 06:10:10', '2025-09-18 06:10:10'),
(2, 'bdVPUn98aTI5sV1geuO7PtjZdae8ok01', '2025-09-18 07:15:24', NULL, NULL, '523850208', 2, 4, NULL, NULL, NULL, 'coba fitur', 'coba fitur', '2025-09-20', '16:00:00', 'Testing', 1, NULL, 'akan_datang', '2025-09-18 06:57:17', '2025-09-18 06:57:17'),
(3, 'k49ucxPLzZEQot8QG7Ou1lE2qe7j1K0J', '2025-09-18 07:15:31', NULL, NULL, '529834793597', 4, 4, NULL, NULL, NULL, 'rakor', 'rapat koordinasi', '2025-09-19', '16:10:00', 'pta', 1, NULL, 'akan_datang', '2025-09-18 07:03:48', '2025-09-18 07:03:48'),
(4, '38rYYW0xZrdDWJ2YSEGD4oin4OpC87cb', '2025-09-18 23:36:03', NULL, NULL, '57295026800', 1, 4, NULL, NULL, NULL, 'Testing qr absensi', 'coba coba dulu', '2025-09-20', '08:40:00', 'PTA Papua Barat', 1, NULL, 'akan_datang', '2025-09-18 23:34:54', '2025-09-18 23:34:54'),
(5, 'C8MGKD7IkKlEZbzFES1c1pPpzVDoNet1', '2025-09-19 00:59:33', NULL, NULL, '3r45234234', 2, 5, NULL, '{\"v\":1,\"doc_type\":\"absensi\",\"rapat_id\":5,\"nomor\":\"3r45234234\",\"judul\":\"Testing fitur qr code absensi\",\"tanggal\":\"2025-09-20\",\"issued_at\":\"2025-09-19T09:59:33+09:00\",\"nonce\":\"bLB4PtqvV9oCHNT4\",\"sig\":\"89d4648f14bf2c8421ffe6ef0b199132cfc3c0d4ce3b7b195c981b1fb78b16c3\"}', 'qr/qr_absensi_r5_qYjyDc.png', 'Testing fitur qr code absensi', 'coba coba', '2025-09-20', '10:00:00', 'pta', 1, NULL, 'akan_datang', '2025-09-19 00:58:49', '2025-09-19 00:59:35'),
(6, 'FZH5UJnBPeyPrhDe2A2nwffNeObZJyc2', '2025-09-19 04:23:44', '2025-09-19 04:23:51', NULL, '6038530750927', 1, 4, NULL, NULL, NULL, 'Testing fitur QR Absensi Ke 2', 'Mencoba fitur qr absensi lainnya', '2025-09-21', '13:25:00', 'pta', 1, NULL, 'akan_datang', '2025-09-19 04:22:41', '2025-09-19 04:22:41'),
(7, 'i3WjuNCdwcHYAogb08LgnjfPzyJhmjHb', '2025-09-19 05:06:22', '2025-09-19 05:06:30', NULL, '34535255', 1, 4, 5, NULL, NULL, 'testing fitur', 'sjflajflkajl', '2025-09-21', '17:09:00', 'pta', 1, NULL, 'akan_datang', '2025-09-19 05:04:36', '2025-09-19 05:04:36'),
(8, 'DGGgU7PrhLF3Rf6smrhI7u5brnsnNz41', '2025-09-19 05:11:04', '2025-09-19 05:11:12', NULL, '5248096830', 5, 4, NULL, NULL, NULL, 'coba 2 lampiran', 'testing lampirang', '2025-09-22', '14:10:00', 'pta', 1, NULL, 'akan_datang', '2025-09-19 05:10:04', '2025-09-19 05:10:04'),
(9, 'Ah9rwEBxJ9PW3MoK7B0ZDKPPfjGsp2pb', '2025-09-19 05:13:33', '2025-09-19 05:13:41', NULL, '57q239589308', 2, 4, NULL, NULL, NULL, 'Testing lampiran 2', 'coba coba', '2025-09-23', '14:14:00', 'online', 1, NULL, 'akan_datang', '2025-09-19 05:13:07', '2025-09-19 05:13:07'),
(10, 'hVWaplEfM8U1xkPDfLXrVuP9VjQU4gW9', '2025-09-19 05:29:56', '2025-09-19 05:30:02', NULL, '375433096805', 1, 4, NULL, NULL, NULL, 'Testing fitur lagi', 'sfjajfopa', '2025-09-23', '14:30:00', 'asjfos', 1, NULL, 'akan_datang', '2025-09-19 05:29:39', '2025-09-19 05:29:39'),
(11, 'Mmj9HTYmPebzWCdOwzDBaTInCiED3Xep', '2025-09-19 07:54:43', '2025-09-19 07:54:51', NULL, '534252395028', 3, 4, NULL, NULL, NULL, 'jsflajflkakl', 'ldjsaklfjklasjf', '2025-09-24', '17:00:00', 'pta', 1, NULL, 'akan_datang', '2025-09-19 07:53:53', '2025-09-19 07:53:53'),
(12, 'iuCC81E0JlOEVjbWEIUIaUtvNeIkOsEN', '2025-09-19 07:57:37', '2025-09-19 07:57:16', NULL, '508028', 1, 4, NULL, NULL, NULL, 'uiouioo', 'ouiouio', '2025-09-24', '18:00:00', 'afa', 1, NULL, 'akan_datang', '2025-09-19 07:56:38', '2025-09-19 07:56:38'),
(13, 'G6OvSqgAMOODelvidutvejsHdWxVbMEJ', '2025-09-21 23:53:38', NULL, NULL, '348403859080', 3, 4, NULL, NULL, NULL, 'Rapat Koordinasi Internal', 'Rapat Koordinasi Internal membahas tentang blba lbal', '2025-09-22', '08:58:00', 'pta papua barat', 1, NULL, 'akan_datang', '2025-09-21 23:52:53', '2025-09-21 23:52:53');

-- --------------------------------------------------------

--
-- Struktur dari tabel `undangan`
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
-- Dumping data untuk tabel `undangan`
--

INSERT INTO `undangan` (`id`, `id_rapat`, `id_user`, `status`, `created_at`, `updated_at`) VALUES
(1, 1, 6, 'terkirim', '2025-09-18 06:10:10', '2025-09-18 06:10:10'),
(2, 1, 7, 'terkirim', '2025-09-18 06:10:10', '2025-09-18 06:10:10'),
(3, 2, 6, 'terkirim', '2025-09-18 06:57:17', '2025-09-18 06:57:17'),
(4, 3, 6, 'terkirim', '2025-09-18 07:03:48', '2025-09-18 07:03:48'),
(5, 4, 6, 'terkirim', '2025-09-18 23:34:54', '2025-09-18 23:34:54'),
(6, 5, 6, 'terkirim', '2025-09-19 00:58:49', '2025-09-19 00:58:49'),
(7, 6, 6, 'terkirim', '2025-09-19 04:22:41', '2025-09-19 04:22:41'),
(8, 7, 6, 'terkirim', '2025-09-19 05:04:36', '2025-09-19 05:04:36'),
(9, 8, 6, 'terkirim', '2025-09-19 05:10:04', '2025-09-19 05:10:04'),
(10, 8, 7, 'terkirim', '2025-09-19 05:10:05', '2025-09-19 05:10:05'),
(11, 8, 8, 'terkirim', '2025-09-19 05:10:05', '2025-09-19 05:10:05'),
(12, 8, 9, 'terkirim', '2025-09-19 05:10:05', '2025-09-19 05:10:05'),
(13, 8, 10, 'terkirim', '2025-09-19 05:10:06', '2025-09-19 05:10:06'),
(14, 9, 3, 'terkirim', '2025-09-19 05:13:07', '2025-09-19 05:13:07'),
(15, 9, 6, 'terkirim', '2025-09-19 05:13:07', '2025-09-19 05:13:07'),
(16, 9, 7, 'terkirim', '2025-09-19 05:13:08', '2025-09-19 05:13:08'),
(17, 9, 8, 'terkirim', '2025-09-19 05:13:09', '2025-09-19 05:13:09'),
(18, 9, 9, 'terkirim', '2025-09-19 05:13:09', '2025-09-19 05:13:09'),
(19, 9, 10, 'terkirim', '2025-09-19 05:13:09', '2025-09-19 05:13:09'),
(20, 10, 3, 'terkirim', '2025-09-19 05:29:39', '2025-09-19 05:29:39'),
(21, 10, 6, 'terkirim', '2025-09-19 05:29:39', '2025-09-19 05:29:39'),
(22, 10, 7, 'terkirim', '2025-09-19 05:29:39', '2025-09-19 05:29:39'),
(23, 10, 8, 'terkirim', '2025-09-19 05:29:40', '2025-09-19 05:29:40'),
(24, 10, 9, 'terkirim', '2025-09-19 05:29:40', '2025-09-19 05:29:40'),
(25, 10, 10, 'terkirim', '2025-09-19 05:29:40', '2025-09-19 05:29:40'),
(26, 11, 3, 'terkirim', '2025-09-19 07:53:53', '2025-09-19 07:53:53'),
(27, 11, 6, 'terkirim', '2025-09-19 07:53:53', '2025-09-19 07:53:53'),
(28, 11, 7, 'terkirim', '2025-09-19 07:53:53', '2025-09-19 07:53:53'),
(29, 11, 8, 'terkirim', '2025-09-19 07:53:54', '2025-09-19 07:53:54'),
(30, 11, 9, 'terkirim', '2025-09-19 07:53:54', '2025-09-19 07:53:54'),
(31, 11, 10, 'terkirim', '2025-09-19 07:53:54', '2025-09-19 07:53:54'),
(32, 12, 3, 'terkirim', '2025-09-19 07:56:38', '2025-09-19 07:56:38'),
(33, 12, 6, 'terkirim', '2025-09-19 07:56:38', '2025-09-19 07:56:38'),
(34, 12, 7, 'terkirim', '2025-09-19 07:56:38', '2025-09-19 07:56:38'),
(35, 12, 8, 'terkirim', '2025-09-19 07:56:38', '2025-09-19 07:56:38'),
(36, 12, 9, 'terkirim', '2025-09-19 07:56:39', '2025-09-19 07:56:39'),
(37, 12, 10, 'terkirim', '2025-09-19 07:56:39', '2025-09-19 07:56:39'),
(38, 13, 6, 'terkirim', '2025-09-21 23:52:53', '2025-09-21 23:52:53'),
(39, 13, 7, 'terkirim', '2025-09-21 23:52:57', '2025-09-21 23:52:57'),
(40, 13, 8, 'terkirim', '2025-09-21 23:52:57', '2025-09-21 23:52:57'),
(41, 13, 9, 'terkirim', '2025-09-21 23:52:57', '2025-09-21 23:52:57'),
(42, 13, 10, 'terkirim', '2025-09-21 23:52:58', '2025-09-21 23:52:58');

-- --------------------------------------------------------

--
-- Struktur dari tabel `users`
--

CREATE TABLE `users` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `no_hp` varchar(20) DEFAULT NULL,
  `jabatan` varchar(255) DEFAULT NULL,
  `unit` enum('kepaniteraan','kesekretariatan') NOT NULL DEFAULT 'kesekretariatan',
  `tingkatan` tinyint(4) DEFAULT NULL,
  `email_verified_at` timestamp NULL DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('admin','notulis','peserta','approval') NOT NULL DEFAULT 'peserta',
  `remember_token` varchar(100) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data untuk tabel `users`
--

INSERT INTO `users` (`id`, `name`, `email`, `no_hp`, `jabatan`, `unit`, `tingkatan`, `email_verified_at`, `password`, `role`, `remember_token`, `created_at`, `updated_at`) VALUES
(1, 'Admin Sistem', 'admin@sistemrapat.test', NULL, NULL, 'kesekretariatan', NULL, NULL, '$2y$10$Nw85.OknZ077vjQbeC9G7eigRz81i4eE6hFx86yDRhlFCU/40bOo.', 'admin', NULL, '2025-09-18 06:01:53', '2025-09-18 06:01:53'),
(2, 'Notulis Satu', 'notulis@sistemrapat.test', NULL, NULL, 'kesekretariatan', NULL, NULL, '$2y$10$bzCs90tppjxCZhjmra1TnOiLKH6gWXL1OjeUaFs0W7QYaAyrTzVhC', 'notulis', NULL, '2025-09-18 06:01:53', '2025-09-18 06:01:53'),
(3, 'Peserta Satu', 'peserta@sistemrapat.test', NULL, NULL, 'kesekretariatan', NULL, NULL, '$2y$10$XIejy1XsSiPTlKtifClZIeljnN7WUOtwvuUJ6rcg81DWQxm5GLUOG', 'peserta', NULL, '2025-09-18 06:01:53', '2025-09-18 06:01:53'),
(4, 'Luthfi', 'luthfi@gmail.com', '08121579761533', 'Ketua PTA', 'kepaniteraan', 1, NULL, '$2y$10$zQbkDs3eUd1LDfG175/Dx.A8YVOSLoQRQmCS/EeYGpECw43LTmOYu', 'approval', NULL, '2025-09-18 06:05:16', '2025-09-18 23:44:29'),
(5, 'Gilang', 'gilang@gmail.com', '083129031844', 'Analis APBN', 'kesekretariatan', 2, NULL, '$2y$10$r.gpL2aYE05.JdEhzMUUkO.2p9cWO5dNw8Qu9yruD0nLmZkv9vm6W', 'approval', NULL, '2025-09-18 06:06:35', '2025-09-18 06:06:35'),
(6, 'Muhjar', 'muhjar@gmail.com', '081240170314', 'PKSTi', 'kesekretariatan', NULL, NULL, '$2y$10$rxhFQyVACKZQ70RCLoFQPuxZh8RM9Gj3zD6wQxVc22wJ.yFsgQUO2', 'peserta', NULL, '2025-09-18 06:07:09', '2025-09-18 06:07:09'),
(7, 'Hari', 'hari@gmail.com', '081234325558', 'PPNPN', 'kesekretariatan', NULL, NULL, '$2y$10$YfLNtg4GFnp9VRkFr9g4QuUdj/lNBsLpMGdW5pxp4s/lMJSt23oQi', 'peserta', NULL, '2025-09-18 06:07:38', '2025-09-18 06:07:38'),
(8, 'akbar', 'akbar@gmail.com', '08237592379024', 'App', 'kepaniteraan', NULL, NULL, '$2y$10$psqbJHbwEHmOaUH.c0gjauwRrg1dUAeLZYTuZ5MSbKh6x9n54Qua2', 'peserta', NULL, '2025-09-19 05:07:45', '2025-09-19 05:07:45'),
(9, 'yudis', 'yudis@gmail.com', '0834368595947', 'Keprotokolan', 'kesekretariatan', NULL, NULL, '$2y$10$zFY3UBhJd5g8i7eWm86BU.QvebeygnyK5FIvDkX29YyO7bc3eUUl6', 'peserta', NULL, '2025-09-19 05:08:17', '2025-09-19 05:08:17'),
(10, 'Fajri', 'fajri@gmail.com', '08123435357', 'app', 'kepaniteraan', NULL, NULL, '$2y$10$EcrFd3E1auUJ.fvNkR6/rep/cpC/6eGPFhVikv53TkQoUWISjjQtO', 'peserta', NULL, '2025-09-19 05:09:11', '2025-09-19 05:09:11');

--
-- Indexes for dumped tables
--

--
-- Indeks untuk tabel `absensi`
--
ALTER TABLE `absensi`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `absensi_unique_rapat_user` (`id_rapat`,`id_user`),
  ADD KEY `absensi_id_user_foreign` (`id_user`);

--
-- Indeks untuk tabel `approval_requests`
--
ALTER TABLE `approval_requests`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `approval_requests_sign_token_unique` (`sign_token`),
  ADD KEY `approval_requests_approver_user_id_foreign` (`approver_user_id`),
  ADD KEY `approval_requests_rapat_id_doc_type_order_index_index` (`rapat_id`,`doc_type`,`order_index`);

--
-- Indeks untuk tabel `failed_jobs`
--
ALTER TABLE `failed_jobs`
  ADD PRIMARY KEY (`id`);

--
-- Indeks untuk tabel `jobs`
--
ALTER TABLE `jobs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `jobs_queue_index` (`queue`);

--
-- Indeks untuk tabel `kategori_rapat`
--
ALTER TABLE `kategori_rapat`
  ADD PRIMARY KEY (`id`);

--
-- Indeks untuk tabel `laporan_archived_meetings`
--
ALTER TABLE `laporan_archived_meetings`
  ADD PRIMARY KEY (`id`),
  ADD KEY `laporan_archived_meetings_archived_by_foreign` (`archived_by`),
  ADD KEY `laporan_archived_meetings_rapat_id_index` (`rapat_id`);

--
-- Indeks untuk tabel `laporan_files`
--
ALTER TABLE `laporan_files`
  ADD PRIMARY KEY (`id`),
  ADD KEY `laporan_files_id_kategori_index` (`id_kategori`);

--
-- Indeks untuk tabel `migrations`
--
ALTER TABLE `migrations`
  ADD PRIMARY KEY (`id`);

--
-- Indeks untuk tabel `notulensi`
--
ALTER TABLE `notulensi`
  ADD PRIMARY KEY (`id`);

--
-- Indeks untuk tabel `notulensi_detail`
--
ALTER TABLE `notulensi_detail`
  ADD PRIMARY KEY (`id`);

--
-- Indeks untuk tabel `notulensi_dokumentasi`
--
ALTER TABLE `notulensi_dokumentasi`
  ADD PRIMARY KEY (`id`);

--
-- Indeks untuk tabel `password_resets`
--
ALTER TABLE `password_resets`
  ADD KEY `password_resets_email_index` (`email`);

--
-- Indeks untuk tabel `pimpinan_rapat`
--
ALTER TABLE `pimpinan_rapat`
  ADD PRIMARY KEY (`id`);

--
-- Indeks untuk tabel `rapat`
--
ALTER TABLE `rapat`
  ADD PRIMARY KEY (`id`);

--
-- Indeks untuk tabel `undangan`
--
ALTER TABLE `undangan`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `undangan_unique_rapat_user` (`id_rapat`,`id_user`),
  ADD KEY `undangan_id_user_foreign` (`id_user`);

--
-- Indeks untuk tabel `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `users_email_unique` (`email`),
  ADD KEY `users_tingkatan_index` (`tingkatan`);

--
-- AUTO_INCREMENT untuk tabel yang dibuang
--

--
-- AUTO_INCREMENT untuk tabel `absensi`
--
ALTER TABLE `absensi`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT untuk tabel `approval_requests`
--
ALTER TABLE `approval_requests`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=29;

--
-- AUTO_INCREMENT untuk tabel `failed_jobs`
--
ALTER TABLE `failed_jobs`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT untuk tabel `jobs`
--
ALTER TABLE `jobs`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT untuk tabel `kategori_rapat`
--
ALTER TABLE `kategori_rapat`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT untuk tabel `laporan_archived_meetings`
--
ALTER TABLE `laporan_archived_meetings`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT untuk tabel `laporan_files`
--
ALTER TABLE `laporan_files`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT untuk tabel `migrations`
--
ALTER TABLE `migrations`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=35;

--
-- AUTO_INCREMENT untuk tabel `notulensi`
--
ALTER TABLE `notulensi`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT untuk tabel `notulensi_detail`
--
ALTER TABLE `notulensi_detail`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT untuk tabel `notulensi_dokumentasi`
--
ALTER TABLE `notulensi_dokumentasi`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT untuk tabel `pimpinan_rapat`
--
ALTER TABLE `pimpinan_rapat`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT untuk tabel `rapat`
--
ALTER TABLE `rapat`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT untuk tabel `undangan`
--
ALTER TABLE `undangan`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=43;

--
-- AUTO_INCREMENT untuk tabel `users`
--
ALTER TABLE `users`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- Ketidakleluasaan untuk tabel pelimpahan (Dumped Tables)
--

--
-- Ketidakleluasaan untuk tabel `absensi`
--
ALTER TABLE `absensi`
  ADD CONSTRAINT `absensi_id_rapat_foreign` FOREIGN KEY (`id_rapat`) REFERENCES `rapat` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `absensi_id_user_foreign` FOREIGN KEY (`id_user`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Ketidakleluasaan untuk tabel `approval_requests`
--
ALTER TABLE `approval_requests`
  ADD CONSTRAINT `approval_requests_approver_user_id_foreign` FOREIGN KEY (`approver_user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `approval_requests_rapat_id_foreign` FOREIGN KEY (`rapat_id`) REFERENCES `rapat` (`id`) ON DELETE CASCADE;

--
-- Ketidakleluasaan untuk tabel `laporan_archived_meetings`
--
ALTER TABLE `laporan_archived_meetings`
  ADD CONSTRAINT `laporan_archived_meetings_archived_by_foreign` FOREIGN KEY (`archived_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `laporan_archived_meetings_rapat_id_foreign` FOREIGN KEY (`rapat_id`) REFERENCES `rapat` (`id`) ON DELETE CASCADE;

--
-- Ketidakleluasaan untuk tabel `undangan`
--
ALTER TABLE `undangan`
  ADD CONSTRAINT `undangan_id_rapat_foreign` FOREIGN KEY (`id_rapat`) REFERENCES `rapat` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `undangan_id_user_foreign` FOREIGN KEY (`id_user`) REFERENCES `users` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
