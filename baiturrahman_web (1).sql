-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Waktu pembuatan: 11 Bulan Mei 2026 pada 02.00
-- Versi server: 10.4.32-MariaDB
-- Versi PHP: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `baiturrahman_web`
--

-- --------------------------------------------------------

--
-- Struktur dari tabel `classes`
--

CREATE TABLE `classes` (
  `id` int(11) NOT NULL,
  `name` varchar(50) NOT NULL,
  `teacher` varchar(100) DEFAULT 'Belum ditentukan',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `classes`
--

INSERT INTO `classes` (`id`, `name`, `teacher`, `created_at`) VALUES
(1, 'aliyah', 'Abu assa', '2025-07-15 03:16:08'),
(2, 'Mutawasith 2', 'Abu assa', '2025-07-16 23:13:05'),
(3, 'Aliyah 1', '', '2025-08-01 16:51:20'),
(4, 'Aliyah 2', '', '2025-08-01 16:51:53'),
(5, 'Mutawasith 1', '', '2025-08-01 16:52:03'),
(6, 'Mutawasith 2', '', '2025-08-01 16:52:12'),
(7, 'Ibtidaiyah 1', '', '2025-08-01 16:52:26'),
(8, 'Ibtidaiyah 2', '', '2025-08-01 16:52:36'),
(9, 'Ibtidaiyah 3', '', '2025-08-01 16:52:46'),
(10, 'Ibtidaiyah 4', '', '2025-08-01 16:52:57'),
(11, 'Tarbiyatul Aulad', '', '2025-10-03 00:47:38');

-- --------------------------------------------------------

--
-- Struktur dari tabel `exams`
--

CREATE TABLE `exams` (
  `id` int(11) NOT NULL,
  `type` enum('file','consolidatedExam') NOT NULL,
  `title` varchar(200) NOT NULL,
  `subject_id` int(11) DEFAULT NULL,
  `class_id` int(11) DEFAULT NULL,
  `file_name` varchar(255) DEFAULT NULL,
  `file_type` varchar(50) DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `file_path` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `exams`
--

INSERT INTO `exams` (`id`, `type`, `title`, `subject_id`, `class_id`, `file_name`, `file_type`, `created_by`, `file_path`, `created_at`) VALUES
(65, '', 'Bahasa Jepang - Kelas aliyah', 18, 1, NULL, NULL, NULL, NULL, '2025-07-25 02:57:10'),
(73, 'file', 'Bahasa Inggris - Kelas aliyah', 3, 1, 'MTS 1 - B ARAB.docx', 'application/vnd.openxmlformats-officedocument.word', NULL, 'uploads/68880fce0d4db.docx', '2025-07-29 00:03:30'),
(83, '', 'Ekonomi - Kelas Mutawasith 2', 9, 2, NULL, NULL, NULL, NULL, '2025-07-31 08:02:52'),
(84, '', 'Ekonomi - Kelas Mutawasith 2', 9, 2, NULL, NULL, NULL, NULL, '2025-07-31 08:03:21'),
(85, '', 'Biologi - Kelas aliyah', 6, 1, NULL, NULL, NULL, NULL, '2025-07-31 08:30:39'),
(86, '', 'Biologi - Kelas aliyah', 6, 1, NULL, NULL, NULL, NULL, '2025-07-31 08:30:55'),
(89, '', 'Biologi - Kelas aliyah', 6, 1, NULL, NULL, NULL, NULL, '2025-07-31 11:08:09'),
(90, '', 'Matematika - Kelas aliyah', 1, 1, NULL, NULL, 1, NULL, '2025-07-31 13:53:40'),
(91, '', 'Fisika - Kelas aliyah', 4, 1, NULL, NULL, 1, NULL, '2025-07-31 14:13:53');

-- --------------------------------------------------------

--
-- Struktur dari tabel `exam_questions`
--

CREATE TABLE `exam_questions` (
  `id` int(11) NOT NULL,
  `exam_id` int(11) DEFAULT NULL,
  `question_type` enum('essay','multiple_choice') NOT NULL,
  `question_text` text NOT NULL,
  `option_a` varchar(255) DEFAULT NULL,
  `option_b` varchar(255) DEFAULT NULL,
  `option_c` varchar(255) DEFAULT NULL,
  `option_d` varchar(255) DEFAULT NULL,
  `correct_answer` char(1) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `grades`
--

CREATE TABLE `grades` (
  `id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `subject_id` int(11) NOT NULL,
  `grade` decimal(5,2) NOT NULL,
  `semester` enum('1','2') NOT NULL,
  `academic_year` varchar(9) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ;

--
-- Dumping data untuk tabel `grades`
--

INSERT INTO `grades` (`id`, `student_id`, `subject_id`, `grade`, `semester`, `academic_year`, `created_at`, `updated_at`) VALUES
(11, 1, 14, 80.00, '2', '2025/2026', '2025-07-25 02:38:19', '2025-07-25 02:38:19'),
(13, 1, 13, 70.00, '1', '2025/2026', '2025-07-25 03:17:24', '2025-07-30 15:50:04');

-- --------------------------------------------------------

--
-- Struktur dari tabel `questions`
--

CREATE TABLE `questions` (
  `id` int(11) NOT NULL,
  `exam_id` int(11) NOT NULL,
  `question_text` text NOT NULL,
  `question_type` enum('essay','multiple_choice') NOT NULL,
  `option_a` varchar(500) DEFAULT NULL,
  `option_b` varchar(500) DEFAULT NULL,
  `option_c` varchar(500) DEFAULT NULL,
  `option_d` varchar(500) DEFAULT NULL,
  `correct_answer` char(1) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `questions`
--

INSERT INTO `questions` (`id`, `exam_id`, `question_text`, `question_type`, `option_a`, `option_b`, `option_c`, `option_d`, `correct_answer`, `created_at`) VALUES
(57, 65, 'wefwef', 'essay', NULL, NULL, NULL, NULL, NULL, '2025-07-25 02:57:10'),
(77, 83, 'Qqedwdww', 'essay', NULL, NULL, NULL, NULL, NULL, '2025-07-31 08:02:53'),
(78, 84, 'efwsef', 'multiple_choice', 'sefgsef', 'sefsef', 'sefsefs', 'fesef', 'A', '2025-07-31 08:03:21'),
(80, 85, 'ascaca', 'multiple_choice', 'scasc', 'asc', 'asc', 'asc', 'A', '2025-07-31 08:30:39'),
(81, 86, 'dfhdth', 'essay', NULL, NULL, NULL, NULL, NULL, '2025-07-31 08:30:56'),
(84, 89, 'sadgrgrh', 'multiple_choice', 'drhdr', 'hdrhd', 'rhdrhdrhdrh', 'hdrh', 'A', '2025-07-31 11:08:09'),
(85, 90, 'sfsf', 'multiple_choice', 'sef', 'sefs', 'efef', 'sef', 'D', '2025-07-31 13:53:40'),
(86, 90, 'segfseg', 'multiple_choice', 'segs', 'egsegsef', 'fegs', 'egse', 'A', '2025-07-31 13:53:40'),
(87, 90, 'segseg', 'multiple_choice', 'segs', 'egsegf', 'segseg', 'seg', 'A', '2025-07-31 13:53:41'),
(88, 91, 'wefewgfwegerg', 'essay', NULL, NULL, NULL, NULL, NULL, '2025-07-31 14:13:53'),
(89, 91, 'ergergerg', 'essay', NULL, NULL, NULL, NULL, NULL, '2025-07-31 14:13:55'),
(90, 91, 'ergerg', 'essay', NULL, NULL, NULL, NULL, NULL, '2025-07-31 14:13:55');

-- --------------------------------------------------------

--
-- Struktur dari tabel `students`
--

CREATE TABLE `students` (
  `id` int(11) NOT NULL,
  `student_id` varchar(20) DEFAULT NULL,
  `name` varchar(100) NOT NULL,
  `class_id` int(11) DEFAULT NULL,
  `birth_place` varchar(100) DEFAULT NULL,
  `birth_date` date DEFAULT NULL,
  `guardian` varchar(100) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `students`
--

INSERT INTO `students` (`id`, `student_id`, `name`, `class_id`, `birth_place`, `birth_date`, `guardian`, `created_at`) VALUES
(1, '201102', 'Aura', 1, 'Bandung', '2024-03-17', 'nur', '2025-07-16 23:13:24'),
(4, '19009', 'Adilah Dzakiyy', 4, 'Bandung', '2007-11-05', 'Shodiqin', '2025-10-03 00:49:05'),
(5, '19013', 'Ghina Mawadah Mutmainnah', 4, 'Bandung', '2008-06-05', 'Aa Permana', '2025-10-03 00:50:47'),
(6, '19014', 'Hamidah Lailah Syabani', 4, 'Cikampek', '2007-09-02', 'Ibnu Maulana', '2025-10-03 00:52:22'),
(7, '19018', 'Muhammad Rafi Fauzan', 4, 'Cimahi', '2009-04-15', 'Nurbuwono Langgeng', '2025-10-03 00:53:12'),
(8, '19023', 'Khofifah Az-Zahra', 6, 'Bandung', '2009-02-27', 'Ahmad Hilal', '2025-10-03 00:54:41'),
(9, '19024', 'Muhammad Ibadurrohman', 6, 'Bandung', '2009-11-02', 'Yayat Cahdiyat', '2025-10-03 00:55:46'),
(10, '19025', 'Nada Lubna Mumtaz', 6, 'Serang Banten', '2008-09-09', 'Fiki Hartono', '2025-10-03 00:56:37'),
(11, '19026', 'Salwa Zahra Annisa', 6, 'Bandung', '2008-10-10', 'Abdul Muhyi', '2025-10-03 00:58:48'),
(12, '22063', 'Jundullah Al Hafidz', 6, 'Waringin Sari', '2007-10-16', 'M. Imron', '2025-10-03 01:00:53'),
(13, '23080', 'Abza Al Ghifari Putra', 6, 'Tasikmalaya', '2009-12-19', 'Hendra Gunawan', '2025-10-03 01:01:37'),
(14, '23096', 'Mutiara Wafi Tazkiah', 6, 'Lampung', '2007-03-11', 'Supriyanto', '2025-10-03 01:02:09'),
(15, '19028', 'Ali Faiz Mubarak', 5, 'Cimahi', '2011-04-15', 'Shodiqin', '2025-10-03 01:03:33'),
(16, '19033', 'Nadzira Zakiya', 5, 'Bandung', '2011-03-07', 'Sholeh Abdurrahman', '2025-10-03 01:04:17'),
(17, '19035', 'Raisya Rabbani Shodiqin', 5, 'Bandung', '2011-09-28', 'Jajang Iqin Shodiqin', '2025-10-03 01:05:06'),
(18, '22064', 'Samudra Al Hafidz', 5, 'Waringin Sari', '2011-06-16', 'M. Imron', '2025-10-03 01:05:53'),
(19, '20039', 'Ikrima Rabbaniya', 5, 'Bandung', '2011-03-07', 'Sholeh Abdurrahman', '2025-10-03 01:06:44'),
(20, '20040', 'Muhammad Farhan', 5, 'Bandung', '2012-01-07', 'Ahmad Hilal', '2025-10-03 01:07:54'),
(21, '20042', 'Nisa amelia', 5, 'Bandung', '2009-05-09', 'Firmansyah', '2025-10-03 01:08:33'),
(22, '23081', 'Muhammad Abdul Azmi Rifaat', 5, 'Karawang', '2011-10-21', 'Masna', '2025-10-03 01:09:52'),
(23, '24107', 'Aydin Fakhri Ali', 5, 'Bandung', '2011-10-28', 'Suhendar', '2025-10-03 01:10:43'),
(24, '24108', 'Siti Nuraeni', 5, 'Bandung Barat', '2012-02-23', 'Dedih Jukarwan', '2025-10-03 01:11:33'),
(25, '24109', 'Sabila Mustaqima', 5, 'Bandung', '2011-07-09', 'Gugun Wiguna', '2025-10-03 01:12:13'),
(26, '24110', 'Urwah Abdurrahman', 6, 'Magelang', '2010-03-07', 'Sutrisno', '2025-10-03 01:14:57'),
(27, '20045', 'Abneera Husna Askariyan', 10, 'Bandung', '2012-11-06', 'Saeful Ridwan', '2025-10-03 01:20:20'),
(28, '20058', 'Kaisyah Yasarah', 10, 'Karawang', '2013-04-24', 'Masna', '2025-10-03 01:21:12'),
(29, '22066', 'Muhammad Azzam Faiz Al-faruq', 10, 'Karawang', '2012-12-10', 'Aep Saepudin', '2025-10-03 01:21:47'),
(30, '22067', 'Ukasyah Al Fadani', 10, 'Bekasi', '2012-09-30', 'Dedi Alamsyah', '2025-10-03 01:22:36'),
(31, '22072', 'Muhamad Syafiq', 10, 'Karawang', '2012-01-16', 'Ibnu Maulana', '2025-10-03 01:23:43'),
(32, '24106', 'Hanan Nabila', 10, 'Sukabumi', '2013-01-09', 'Acep Acun Mansur', '2025-10-03 01:24:29'),
(33, '25120', 'Qonita Zaida Zahra', 10, 'Bandung', '2013-01-28', 'Heru Sulaiman', '2025-10-03 01:25:23'),
(34, '25123', 'Rania Nabilah Muhsinat', 10, 'Bandung', '2012-09-01', 'Mulyawansyah', '2025-10-03 01:25:56'),
(35, '23082', 'Ahmad Imron Abdulloh', 9, 'Karawang', '2013-08-11', 'Ahmad Suherman', '2025-10-13 08:31:25'),
(36, '22069', 'Zaid Rojulun Makis', 9, 'Karawang', '2013-10-09', 'Aep Saepudin', '2025-10-13 08:31:46'),
(37, '22068', 'Utsman Kahfi', 9, 'Bekasi', '2014-03-17', 'Dedi Alamsyah', '2025-10-13 08:32:05'),
(38, '20051', 'Wafi Taqilah Kautsar', 9, 'Bandung', '2014-10-25', 'Eka Alkasah', '2025-10-13 08:32:15'),
(39, '20060', 'Gisya Hamidah Fajriah', 9, 'Bandung barat', '2014-09-20', 'Aa Permana', '2025-10-13 08:32:42'),
(40, '23083', 'Muhammad Hamzah Assadulloh', 9, 'Tanggerang Selatan', '2014-07-15', 'Imam Syarif Hidayatulloh', '2025-10-13 08:33:24'),
(41, '23092', 'Attaqi Habibi Inghimasy', 8, 'Purwakarta', '2016-04-21', 'Hendra Arifin', '2025-10-13 08:34:06'),
(42, '24105', 'Muhammad Faqih Al-Jihad', 8, 'Subang', '2015-06-05', 'Husni Mubarok', '2025-10-13 08:34:21'),
(43, '20046', 'Abyan Muhammad umar Faqiha', 8, 'Bandung', '2015-12-06', 'Saeful Ridwan', '2025-10-13 08:34:47'),
(44, '24103', 'Ulwan Ghozi', 8, 'Bekasi', '2015-07-23', 'Dedi Alamsyah', '2025-10-13 08:35:07'),
(45, '25119', 'Abiyan Syaifurrahman', 8, 'Karawang', '2016-08-26', 'Subur Sanusi', '2025-10-13 08:35:29'),
(46, '24111', 'Asma Adiba', 8, 'Sukabumi', '2015-05-15', 'Acep Acun Mansur', '2025-10-13 08:36:59'),
(47, '23093', 'Zahra Aulia Nafisyah', 8, 'Bandung', '2016-02-23', 'Deni Rahmat Dipraja', '2025-10-13 08:37:16'),
(48, '25116', 'Ibrahim', 11, 'Bandung', '2017-04-11', 'Muhammad Irfan Muttaqin', '2025-10-16 06:14:29'),
(49, '24099', 'Arbaniyan Muhammad Bassam Khuluqy', 11, 'Bandung', '2018-12-09', 'Saeful Ridwan', '2025-10-16 06:15:19'),
(50, '25117', 'Maryam Sulha Adiba', 11, 'Bandung barat', '2020-01-04', 'Asral', '2025-10-16 06:16:01'),
(51, '25121', 'Muhammad Fadhilah Kahfi', 11, 'Purwakarta', '2018-11-09', 'Hendra Arifin', '2025-10-16 06:16:41'),
(52, '25118', 'Risya Naifa Salimi', 11, 'Bandung barat', '2020-06-04', 'Eka Alkasah', '2025-10-16 06:17:10'),
(53, '25122', 'Ruby', 11, '', '0000-00-00', 'Masna', '2025-10-16 06:17:38');

-- --------------------------------------------------------

--
-- Struktur dari tabel `student_reports`
--

CREATE TABLE `student_reports` (
  `id` int(11) NOT NULL,
  `student_id` int(11) DEFAULT NULL,
  `subject_id` int(11) DEFAULT NULL,
  `class_id` int(11) DEFAULT NULL,
  `semester` tinyint(4) DEFAULT NULL,
  `grade` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `subjects`
--

CREATE TABLE `subjects` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `subjects`
--

INSERT INTO `subjects` (`id`, `name`, `created_at`) VALUES
(1, 'Matematika', '2025-07-15 03:13:39'),
(2, 'Bahasa sunda', '2025-07-15 03:13:39'),
(3, 'Bahasa Inggris', '2025-07-15 03:13:39'),
(4, 'Fisika', '2025-07-15 03:13:39'),
(5, 'Kimia', '2025-07-15 03:13:39'),
(6, 'Biologi', '2025-07-15 03:13:39'),
(8, 'Geografi', '2025-07-15 03:13:39'),
(9, 'Ekonomi', '2025-07-15 03:13:39'),
(13, 'Pendidikan Jasmani', '2025-07-15 03:13:39'),
(14, 'Prakarya', '2025-07-15 03:13:39'),
(18, 'Bahasa Jepang', '2025-07-15 03:13:39'),
(19, 'Kewirausahaan', '2025-07-15 03:13:39'),
(20, 'Bimbingan Konseling', '2025-07-15 03:13:39'),
(21, 'Tauhid', '2025-08-01 16:50:45'),
(22, 'Fiqih', '2025-08-01 16:50:52'),
(23, 'Ushul fiqih', '2025-08-01 16:50:59'),
(25, 'Matematika', '2025-08-02 11:44:47'),
(28, 'Bahasa Inggris', '2025-09-11 12:37:39'),
(29, 'Tarikh', '2025-09-11 12:37:45'),
(30, 'Mustholah', '2025-09-11 12:37:58'),
(31, 'Hafdo', '2025-09-24 04:30:17'),
(32, 'Tematik 1', '2025-10-02 00:24:32'),
(33, 'Bahasa arab', '2025-10-02 00:24:51'),
(34, 'Komputer', '2025-10-03 00:22:43'),
(35, 'Tashrif', '2025-10-05 11:21:21'),
(36, 'Tajwid', '2025-10-05 11:21:28'),
(37, 'Tematik 2', '2025-10-05 11:22:20'),
(38, 'TTB', '2025-10-15 03:38:22'),
(39, 'Hadist', '2025-10-15 04:04:43'),
(40, 'Thibun nabawi', '2025-10-15 22:27:24'),
(41, 'Adab', '2025-10-15 22:35:07'),
(42, 'Listrik', '2025-10-16 01:08:58');

-- --------------------------------------------------------

--
-- Struktur dari tabel `surahs`
--

CREATE TABLE `surahs` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `arabic_name` varchar(200) DEFAULT NULL,
  `number` int(11) NOT NULL,
  `total_ayahs` int(11) NOT NULL,
  `type` enum('Makkiyah','Madaniyah') NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `surahs`
--

INSERT INTO `surahs` (`id`, `name`, `arabic_name`, `number`, `total_ayahs`, `type`, `created_at`) VALUES
(1, 'Al-Fatihah', 'الفاتحة', 1, 7, 'Makkiyah', '2025-07-31 14:39:12'),
(2, 'Al-Baqarah', 'البقرة', 2, 286, 'Madaniyah', '2025-07-31 14:39:12'),
(3, 'Ali-Imran', 'آل عمران', 3, 200, 'Madaniyah', '2025-07-31 14:39:12'),
(4, 'An-Nisa', 'النساء', 4, 176, 'Madaniyah', '2025-07-31 14:39:12'),
(5, 'Al-Maidah', 'المائدة', 5, 120, 'Madaniyah', '2025-07-31 14:39:12'),
(6, 'Al-Mulk', 'الملك', 67, 30, 'Makkiyah', '2025-07-31 14:39:12'),
(7, 'Al-Qalam', 'القلم', 68, 52, 'Makkiyah', '2025-07-31 14:39:12'),
(8, 'Al-Haqqah', 'الحاقة', 69, 52, 'Makkiyah', '2025-07-31 14:39:12'),
(9, 'Al-Maarij', 'المعارج', 70, 44, 'Makkiyah', '2025-07-31 14:39:12'),
(10, 'Nuh', 'نوح', 71, 28, 'Makkiyah', '2025-07-31 14:39:12'),
(11, 'Al-Jinn', 'الجن', 72, 28, 'Makkiyah', '2025-07-31 14:39:12'),
(12, 'Al-Muzzammil', 'المزمل', 73, 20, 'Makkiyah', '2025-07-31 14:39:12'),
(13, 'Al-Muddaththir', 'المدثر', 74, 56, 'Makkiyah', '2025-07-31 14:39:12'),
(14, 'Al-Qiyamah', 'القيامة', 75, 40, 'Makkiyah', '2025-07-31 14:39:12'),
(15, 'Al-Insan', 'الإنسان', 76, 31, 'Madaniyah', '2025-07-31 14:39:12'),
(16, 'Al-Mursalat', 'المرسلات', 77, 50, 'Makkiyah', '2025-07-31 14:39:12'),
(17, 'An-Naba', 'النبأ', 78, 40, 'Makkiyah', '2025-07-31 14:39:12'),
(18, 'An-Naziat', 'النازعات', 79, 46, 'Makkiyah', '2025-07-31 14:39:12'),
(19, 'Abasa', 'عبس', 80, 42, 'Makkiyah', '2025-07-31 14:39:12'),
(20, 'At-Takwir', 'التكوير', 81, 29, 'Makkiyah', '2025-07-31 14:39:12'),
(21, 'Al-Infitar', 'الإنفطار', 82, 19, 'Makkiyah', '2025-07-31 14:39:12'),
(22, 'Al-Mutaffifin', 'المطففين', 83, 36, 'Makkiyah', '2025-07-31 14:39:12'),
(23, 'Al-Inshiqaq', 'الإنشقاق', 84, 25, 'Makkiyah', '2025-07-31 14:39:12'),
(24, 'Al-Buruj', 'البروج', 85, 22, 'Makkiyah', '2025-07-31 14:39:12'),
(25, 'At-Tariq', 'الطارق', 86, 17, 'Makkiyah', '2025-07-31 14:39:12'),
(26, 'Al-Ala', 'الأعلى', 87, 19, 'Makkiyah', '2025-07-31 14:39:12'),
(27, 'Al-Ghashiyah', 'الغاشية', 88, 26, 'Makkiyah', '2025-07-31 14:39:12'),
(28, 'Al-Fajr', 'الفجر', 89, 30, 'Makkiyah', '2025-07-31 14:39:12'),
(29, 'Al-Balad', 'البلد', 90, 20, 'Makkiyah', '2025-07-31 14:39:12'),
(30, 'Ash-Shams', 'الشمس', 91, 15, 'Makkiyah', '2025-07-31 14:39:12'),
(31, 'Al-Layl', 'الليل', 92, 21, 'Makkiyah', '2025-07-31 14:39:12'),
(32, 'Ad-Duha', 'الضحى', 93, 11, 'Makkiyah', '2025-07-31 14:39:12'),
(33, 'Ash-Sharh', 'الشرح', 94, 8, 'Makkiyah', '2025-07-31 14:39:12'),
(34, 'At-Tin', 'التين', 95, 8, 'Makkiyah', '2025-07-31 14:39:12'),
(35, 'Al-Alaq', 'العلق', 96, 19, 'Makkiyah', '2025-07-31 14:39:12'),
(36, 'Al-Qadr', 'القدر', 97, 5, 'Makkiyah', '2025-07-31 14:39:12'),
(37, 'Al-Bayyinah', 'البينة', 98, 8, 'Madaniyah', '2025-07-31 14:39:12'),
(38, 'Az-Zalzalah', 'الزلزلة', 99, 8, 'Madaniyah', '2025-07-31 14:39:12'),
(39, 'Al-Adiyat', 'العاديات', 100, 11, 'Makkiyah', '2025-07-31 14:39:12'),
(40, 'Al-Qariah', 'القارعة', 101, 11, 'Makkiyah', '2025-07-31 14:39:12'),
(41, 'At-Takathur', 'التكاثر', 102, 8, 'Makkiyah', '2025-07-31 14:39:12'),
(42, 'Al-Asr', 'العصر', 103, 3, 'Makkiyah', '2025-07-31 14:39:12'),
(43, 'Al-Humazah', 'الهمزة', 104, 9, 'Makkiyah', '2025-07-31 14:39:12'),
(44, 'Al-Fil', 'الفيل', 105, 5, 'Makkiyah', '2025-07-31 14:39:12'),
(45, 'Quraysh', 'قريش', 106, 4, 'Makkiyah', '2025-07-31 14:39:12'),
(46, 'Al-Maun', 'الماعون', 107, 7, 'Makkiyah', '2025-07-31 14:39:12'),
(47, 'Al-Kawthar', 'الكوثر', 108, 3, 'Makkiyah', '2025-07-31 14:39:12'),
(48, 'Al-Kafirun', 'الكافرون', 109, 6, 'Makkiyah', '2025-07-31 14:39:12'),
(49, 'An-Nasr', 'النصر', 110, 3, 'Madaniyah', '2025-07-31 14:39:12'),
(50, 'Al-Masad', 'المسد', 111, 5, 'Makkiyah', '2025-07-31 14:39:12'),
(51, 'Al-Ikhlas', 'الإخلاص', 112, 4, 'Makkiyah', '2025-07-31 14:39:12'),
(52, 'Al-Falaq', 'الفلق', 113, 5, 'Makkiyah', '2025-07-31 14:39:12'),
(53, 'An-Nas', 'الناس', 114, 6, 'Makkiyah', '2025-07-31 14:39:12');

-- --------------------------------------------------------

--
-- Struktur dari tabel `tahfidz_grades`
--

CREATE TABLE `tahfidz_grades` (
  `id` int(11) NOT NULL,
  `student_id` varchar(20) DEFAULT NULL,
  `student_name` varchar(100) NOT NULL,
  `class_id` int(11) DEFAULT NULL,
  `semester` int(11) NOT NULL CHECK (`semester` in (1,2)),
  `from_juz` int(11) DEFAULT NULL,
  `to_juz` int(11) DEFAULT NULL,
  `until_surah` varchar(100) DEFAULT NULL,
  `surah_name` varchar(100) NOT NULL,
  `ayah_range` varchar(50) DEFAULT NULL,
  `memorization_quality` int(11) NOT NULL CHECK (`memorization_quality` between 1 and 100),
  `fluency_score` int(11) NOT NULL CHECK (`fluency_score` between 1 and 100),
  `tajweed_score` int(11) NOT NULL CHECK (`tajweed_score` between 1 and 100),
  `total_score` decimal(5,2) GENERATED ALWAYS AS ((`memorization_quality` + `tajweed_score`) / 2) STORED,
  `grade_letter` varchar(2) GENERATED ALWAYS AS (case when (`memorization_quality` + `tajweed_score`) / 2 >= 86 then 'A' when (`memorization_quality` + `tajweed_score`) / 2 >= 81 then 'B' when (`memorization_quality` + `tajweed_score`) / 2 >= 71 then 'C' when (`memorization_quality` + `tajweed_score`) / 2 >= 60 then 'D' else 'E' end) STORED,
  `grade_description` varchar(20) GENERATED ALWAYS AS (case when (`memorization_quality` + `tajweed_score`) / 2 >= 86 then 'MUMTAZ' when (`memorization_quality` + `tajweed_score`) / 2 >= 81 then 'JAYYID JIDDAN' when (`memorization_quality` + `tajweed_score`) / 2 >= 71 then 'JAYYID' when (`memorization_quality` + `tajweed_score`) / 2 >= 60 then 'MAQBUL' else 'RISIB' end) STORED,
  `notes` text DEFAULT NULL,
  `test_date` date DEFAULT curdate(),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ;

--
-- Dumping data untuk tabel `tahfidz_grades`
--

INSERT INTO `tahfidz_grades` (`id`, `student_id`, `student_name`, `class_id`, `semester`, `from_juz`, `to_juz`, `until_surah`, `surah_name`, `ayah_range`, `memorization_quality`, `fluency_score`, `tajweed_score`, `notes`, `test_date`, `created_at`, `updated_at`) VALUES
(2, '201102', 'Aura', 1, 2, NULL, NULL, NULL, '', '', 60, 1, 70, 'juz 1 s.d juz 5 & juz 26 s.d juz 30', '2025-07-31', '2025-07-31 16:23:49', '2025-07-31 16:28:16'),
(3, '23093', 'Zahra Aulia Nafisyah', 8, 2, NULL, NULL, NULL, '', '', 65, 1, 60, 'Juz 30,juz 29 surat Aljin', '2025-10-15', '2025-10-15 09:54:54', '2025-10-16 06:22:14'),
(4, '20039', 'Ikrima Rabbaniya', 5, 2, NULL, NULL, NULL, '', '', 70, 1, 65, 'Juz 30 -juz 27', '2025-10-15', '2025-10-15 09:59:44', '2025-10-15 09:59:44'),
(5, '20045', 'Abneera Husna Askariyan', 10, 2, NULL, NULL, NULL, '', '', 74, 1, 65, 'Juz 30-juz 27 surat Arrahman', '2025-10-15', '2025-10-15 10:05:13', '2025-10-15 10:05:13'),
(6, '25120', 'Qonita Zaida Zahra', 10, 2, NULL, NULL, NULL, '', '', 70, 1, 65, 'Juz 30 dari Annaba-Annaas', '2025-10-15', '2025-10-15 10:05:13', '2025-10-16 06:21:51'),
(7, '25123', 'Rania Nabilah Muhsinat', 10, 2, NULL, NULL, NULL, '', '', 80, 1, 65, 'Juz 30 surat Annaba-Annaas', '2025-10-15', '2025-10-15 10:05:13', '2025-10-16 06:20:39'),
(9, '19013', 'Ghina Mawadah Mutmainnah', 4, 2, NULL, NULL, NULL, '', '', 85, 1, 80, 'Juz 1 -juz 6,juz 30-,juz 20', '2025-10-15', '2025-10-15 10:12:54', '2025-10-16 05:38:35'),
(10, '19033', 'Nadzira Zakiya', 5, 2, NULL, NULL, NULL, '', '', 79, 1, 65, 'Juz 30-juz 27', '2025-10-15', '2025-10-15 14:25:39', '2025-10-15 14:25:39'),
(11, '19035', 'Raisya Rabbani Shodiqin', 5, 2, NULL, NULL, NULL, '', '', 79, 1, 70, 'Juz 30-juz 24', '2025-10-15', '2025-10-15 14:25:39', '2025-10-15 14:25:39'),
(12, '24109', 'Sabila Mustaqima', 5, 2, NULL, NULL, NULL, '', '', 80, 1, 70, 'Juz 30-juz 29 surat Al insan', '2025-10-15', '2025-10-15 14:25:39', '2025-10-15 14:25:39'),
(13, '24108', 'Siti Nuraeni', 5, 2, NULL, NULL, NULL, '', '', 77, 1, 62, 'Juz 30-juz 29 surat Al ma\'arij.', '2025-10-15', '2025-10-15 14:25:39', '2025-10-15 14:25:39'),
(14, '24111', 'Asma Adiba', 8, 2, NULL, NULL, NULL, '', '', 80, 1, 60, 'Juz 30 surat annaba-Annaas', '2025-10-15', '2025-10-15 14:39:33', '2025-10-16 06:22:39'),
(15, '19009', 'Adilah Dzakiyy', 4, 2, NULL, NULL, NULL, '', '', 85, 1, 80, 'Juz 30-juz 20', '2025-10-16', '2025-10-16 05:40:51', '2025-10-16 05:40:51');

-- --------------------------------------------------------

--
-- Struktur dari tabel `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `profile_picture` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `users`
--

INSERT INTO `users` (`id`, `username`, `password`, `full_name`, `profile_picture`, `created_at`) VALUES
(1, 'abuza', '$2y$10$CvXXpa2VWQistrbFd6ao/.L/61.dqbZSicJVpk1m0kDWCH/LhWs8K', 'Muhammad Siroj', NULL, '2025-07-15 03:14:32');

--
-- Indexes for dumped tables
--

--
-- Indeks untuk tabel `classes`
--
ALTER TABLE `classes`
  ADD PRIMARY KEY (`id`);

--
-- Indeks untuk tabel `exams`
--
ALTER TABLE `exams`
  ADD PRIMARY KEY (`id`),
  ADD KEY `subject_id` (`subject_id`),
  ADD KEY `class_id` (`class_id`),
  ADD KEY `fk_exams_created_by` (`created_by`);

--
-- Indeks untuk tabel `exam_questions`
--
ALTER TABLE `exam_questions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `exam_id` (`exam_id`);

--
-- Indeks untuk tabel `grades`
--
ALTER TABLE `grades`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_student_subject_semester` (`student_id`,`subject_id`,`semester`,`academic_year`),
  ADD KEY `subject_id` (`subject_id`);

--
-- Indeks untuk tabel `questions`
--
ALTER TABLE `questions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `exam_id` (`exam_id`);

--
-- Indeks untuk tabel `students`
--
ALTER TABLE `students`
  ADD PRIMARY KEY (`id`),
  ADD KEY `class_id` (`class_id`);

--
-- Indeks untuk tabel `student_reports`
--
ALTER TABLE `student_reports`
  ADD PRIMARY KEY (`id`),
  ADD KEY `student_id` (`student_id`),
  ADD KEY `subject_id` (`subject_id`),
  ADD KEY `class_id` (`class_id`);

--
-- Indeks untuk tabel `subjects`
--
ALTER TABLE `subjects`
  ADD PRIMARY KEY (`id`);

--
-- Indeks untuk tabel `surahs`
--
ALTER TABLE `surahs`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_number` (`number`);

--
-- Indeks untuk tabel `tahfidz_grades`
--
ALTER TABLE `tahfidz_grades`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_student_semester` (`student_id`,`semester`),
  ADD KEY `idx_class_semester` (`class_id`,`semester`),
  ADD KEY `idx_surah` (`surah_name`);

--
-- Indeks untuk tabel `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- AUTO_INCREMENT untuk tabel yang dibuang
--

--
-- AUTO_INCREMENT untuk tabel `classes`
--
ALTER TABLE `classes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT untuk tabel `exams`
--
ALTER TABLE `exams`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=143;

--
-- AUTO_INCREMENT untuk tabel `exam_questions`
--
ALTER TABLE `exam_questions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT untuk tabel `grades`
--
ALTER TABLE `grades`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT untuk tabel `questions`
--
ALTER TABLE `questions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=196;

--
-- AUTO_INCREMENT untuk tabel `students`
--
ALTER TABLE `students`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=54;

--
-- AUTO_INCREMENT untuk tabel `student_reports`
--
ALTER TABLE `student_reports`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT untuk tabel `subjects`
--
ALTER TABLE `subjects`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=43;

--
-- AUTO_INCREMENT untuk tabel `surahs`
--
ALTER TABLE `surahs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=54;

--
-- AUTO_INCREMENT untuk tabel `tahfidz_grades`
--
ALTER TABLE `tahfidz_grades`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT untuk tabel `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- Ketidakleluasaan untuk tabel pelimpahan (Dumped Tables)
--

--
-- Ketidakleluasaan untuk tabel `exams`
--
ALTER TABLE `exams`
  ADD CONSTRAINT `exams_ibfk_1` FOREIGN KEY (`subject_id`) REFERENCES `subjects` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `exams_ibfk_2` FOREIGN KEY (`class_id`) REFERENCES `classes` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_exams_created_by` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Ketidakleluasaan untuk tabel `exam_questions`
--
ALTER TABLE `exam_questions`
  ADD CONSTRAINT `exam_questions_ibfk_1` FOREIGN KEY (`exam_id`) REFERENCES `exams` (`id`) ON DELETE CASCADE;

--
-- Ketidakleluasaan untuk tabel `grades`
--
ALTER TABLE `grades`
  ADD CONSTRAINT `grades_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `grades_ibfk_2` FOREIGN KEY (`subject_id`) REFERENCES `subjects` (`id`) ON DELETE CASCADE;

--
-- Ketidakleluasaan untuk tabel `questions`
--
ALTER TABLE `questions`
  ADD CONSTRAINT `questions_ibfk_1` FOREIGN KEY (`exam_id`) REFERENCES `exams` (`id`) ON DELETE CASCADE;

--
-- Ketidakleluasaan untuk tabel `students`
--
ALTER TABLE `students`
  ADD CONSTRAINT `students_ibfk_1` FOREIGN KEY (`class_id`) REFERENCES `classes` (`id`) ON DELETE SET NULL;

--
-- Ketidakleluasaan untuk tabel `student_reports`
--
ALTER TABLE `student_reports`
  ADD CONSTRAINT `student_reports_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `student_reports_ibfk_2` FOREIGN KEY (`subject_id`) REFERENCES `subjects` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `student_reports_ibfk_3` FOREIGN KEY (`class_id`) REFERENCES `classes` (`id`) ON DELETE CASCADE;

--
-- Ketidakleluasaan untuk tabel `tahfidz_grades`
--
ALTER TABLE `tahfidz_grades`
  ADD CONSTRAINT `tahfidz_grades_ibfk_1` FOREIGN KEY (`class_id`) REFERENCES `classes` (`id`) ON DELETE SET NULL;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
