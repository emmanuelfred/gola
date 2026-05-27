-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: May 27, 2026 at 01:44 PM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `goodness_omogo_db`
--

-- --------------------------------------------------------

--
-- Table structure for table `academic_departments`
--

CREATE TABLE `academic_departments` (
  `id` int(11) NOT NULL,
  `department_name` varchar(255) NOT NULL,
  `slug` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `subjects_covered` text DEFAULT NULL,
  `featured_image` varchar(255) DEFAULT NULL,
  `icon_type` varchar(50) DEFAULT NULL,
  `display_order` int(11) DEFAULT 0,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `academic_departments`
--

INSERT INTO `academic_departments` (`id`, `department_name`, `slug`, `description`, `subjects_covered`, `featured_image`, `icon_type`, `display_order`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 'Sciences', 'sciences', 'Nurturing future scientists, engineers, and medical professionals through rigorous STEM education.', 'Physics, Chemistry, Biology, Further Mathematics, Computer Science', NULL, 'science', 1, 1, '2026-02-12 18:14:49', '2026-02-12 18:14:49'),
(2, 'Humanities & Arts', 'humanities-arts', 'Developing critical thinking, creativity, and cultural awareness through literature and social sciences.', 'Literature, History, Government, CRS/IRS, Visual Arts', NULL, 'menu_book', 2, 1, '2026-02-12 18:14:49', '2026-02-12 18:14:49'),
(3, 'Commercials', 'commercials', 'Preparing business leaders and entrepreneurs with essential financial and management skills.', 'Economics, Accounting, Commerce, Business Studies', NULL, 'business_center', 3, 1, '2026-02-12 18:14:49', '2026-02-12 18:14:49'),
(4, 'Leadership Center', 'leadership', 'Building character, ethics, and leadership qualities for tomorrow\'s change-makers.', 'Leadership Development, Civic Education, Public Speaking, Ethics', NULL, 'group', 4, 1, '2026-02-12 18:14:49', '2026-02-12 18:14:49');

-- --------------------------------------------------------

--
-- Table structure for table `academic_events`
--

CREATE TABLE `academic_events` (
  `id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `event_date` date NOT NULL,
  `event_time` varchar(100) DEFAULT NULL,
  `event_type` enum('Exam','Holiday','Activity','Meeting','Sports','Cultural','Other') DEFAULT 'Other',
  `location` varchar(255) DEFAULT NULL,
  `pdf_file` varchar(255) DEFAULT NULL,
  `is_featured` tinyint(1) DEFAULT 0,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `academic_events`
--

INSERT INTO `academic_events` (`id`, `title`, `description`, `event_date`, `event_time`, `event_type`, `location`, `pdf_file`, `is_featured`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 'Inter-House Sports Festival 2025', 'Annual athletic competition featuring track and field events across all houses', '2025-02-24', '8:00 AM - 4:00 PM', 'Sports', 'School Sports Complex', NULL, 1, 1, '2026-02-12 18:31:33', '2026-02-12 18:31:33'),
(2, 'Mid-Term Parent/Teacher Consultation', 'Meet with teachers to discuss your child\'s progress and development', '2025-03-02', '10:00 AM - 2:00 PM', 'Meeting', 'School Hall', NULL, 1, 1, '2026-02-12 18:31:33', '2026-02-12 18:31:33'),
(3, 'Annual Leadership Symposium', 'Featuring keynote speakers from business, government, and civil society', '2025-03-15', '9:00 AM - 3:00 PM', 'Cultural', 'Main Auditorium', NULL, 1, 1, '2026-02-12 18:31:33', '2026-02-12 18:31:33'),
(4, 'First Term Begins', 'Resumption for 2024/2025 Academic Session', '2024-09-16', '8:00 AM', 'Other', 'All Classes', NULL, 0, 1, '2026-02-12 18:31:33', '2026-02-12 18:31:33'),
(5, 'Mid-Term Break', 'One week mid-term break', '2024-10-28', 'All Day', 'Holiday', 'N/A', NULL, 0, 1, '2026-02-12 18:31:33', '2026-02-12 18:31:33'),
(6, 'First Term Exams', 'First term examination period', '2024-12-02', '8:00 AM', 'Exam', 'Examination Halls', NULL, 0, 1, '2026-02-12 18:31:33', '2026-02-12 18:31:33'),
(7, 'First Term Ends', 'End of first term academic activities', '2024-12-20', '2:00 PM', 'Other', 'All Classes', NULL, 0, 1, '2026-02-12 18:31:33', '2026-02-12 18:31:33'),
(8, 'Second Term Begins', 'Resumption for second term', '2025-01-13', '8:00 AM', 'Other', 'All Classes', NULL, 0, 1, '2026-02-12 18:31:33', '2026-02-12 18:31:33'),
(9, 'WAEC Registration', 'Registration for WAEC examinations', '2025-02-10', '9:00 AM - 3:00 PM', 'Activity', 'Admin Block', NULL, 0, 1, '2026-02-12 18:31:33', '2026-02-12 18:31:33'),
(10, 'Cultural Day Celebration', 'Celebrating Nigeria\'s rich cultural diversity', '2025-04-20', '10:00 AM - 4:00 PM', 'Cultural', 'School Grounds', NULL, 1, 1, '2026-02-12 18:31:33', '2026-02-12 18:31:33'),
(11, 'Science Fair & Exhibition', 'Students showcase innovative science projects', '2025-05-05', '9:00 AM - 2:00 PM', 'Activity', 'STEM Laboratory', NULL, 1, 1, '2026-02-12 18:31:33', '2026-02-12 18:31:33'),
(12, 'Graduation Ceremony 2025', 'Celebrating the success of our graduating students', '2025-06-15', '10:00 AM - 1:00 PM', 'Other', 'Main Hall', NULL, 1, 1, '2026-02-12 18:31:33', '2026-02-12 18:31:33');

-- --------------------------------------------------------

--
-- Table structure for table `academic_sessions`
--

CREATE TABLE `academic_sessions` (
  `id` int(11) NOT NULL,
  `session_name` varchar(20) NOT NULL,
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `is_current` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `academic_sessions`
--

INSERT INTO `academic_sessions` (`id`, `session_name`, `start_date`, `end_date`, `is_current`, `created_at`) VALUES
(1, '2024/2025', '2024-09-01', '2025-08-31', 0, '2026-02-12 15:36:38'),
(2, '2026/2027', '2026-09-01', '2027-12-15', 1, '2026-05-26 15:32:38');

-- --------------------------------------------------------

--
-- Table structure for table `activity_logs`
--

CREATE TABLE `activity_logs` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `action` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `activity_logs`
--

INSERT INTO `activity_logs` (`id`, `user_id`, `action`, `description`, `ip_address`, `created_at`) VALUES
(1, 1, 'login', 'User logged in', '::1', '2026-02-12 16:12:34'),
(2, 1, 'logout', 'User logged out', '::1', '2026-02-12 16:19:05'),
(3, 1, 'login', 'User logged in', '::1', '2026-02-12 16:22:00'),
(4, 1, 'delete_news', 'Deleted news article ID: 1', '::1', '2026-02-12 16:37:05'),
(5, 1, 'create_news', 'Created news article: fsv', '::1', '2026-02-12 16:48:29'),
(6, 1, 'add_gallery_image', 'Uploaded gallery image: somethin new', '::1', '2026-02-12 17:35:05'),
(7, 1, 'add_event', 'Added event: love', '::1', '2026-02-12 18:43:22'),
(8, 1, 'generate_cards', 'Generated 10 scratch cards (max 5 uses each)', '::1', '2026-02-12 23:07:47'),
(9, 1, 'activate_cards', 'Activated 1 scratch cards', '::1', '2026-02-12 23:08:49'),
(10, 1, 'delete_gallery_image', 'Deleted gallery image ID: 1', '::1', '2026-02-13 09:46:59'),
(11, 1, 'delete_gallery_image', 'Deleted gallery image ID: 2', '::1', '2026-02-13 09:47:06'),
(12, 1, 'delete_gallery_image', 'Deleted gallery image ID: 3', '::1', '2026-02-13 09:47:40'),
(13, 1, 'delete_event', 'Deleted event ID: 13', '::1', '2026-02-13 09:54:03'),
(14, 1, 'login', 'User logged in', '::1', '2026-05-26 12:02:11'),
(15, 1, 'logout', 'User logged out', '::1', '2026-05-26 12:04:40'),
(16, 2, 'login', 'User logged in', '::1', '2026-05-26 12:04:53'),
(17, 2, 'add_subject', 'Added subject: sdf (DD)', '::1', '2026-05-26 15:28:33'),
(18, 2, 'add_session', 'Added session: 2026/2027', '::1', '2026-05-26 15:32:39'),
(19, 2, 'set_current_session', 'Set session id=2 as current', '::1', '2026-05-26 15:32:59'),
(20, 2, 'enter_student_results', 'Saved 8 subject scores for Chukwuemeka Adebayo', '::1', '2026-05-27 09:21:31'),
(21, 2, 'publish_student_result', 'Published result for Chukwuemeka Adebayo', '::1', '2026-05-27 09:38:20'),
(22, 2, 'add_subject', 'Added subject: language (MTN)', '::1', '2026-05-27 09:46:34'),
(23, 2, 'enter_student_results', 'Saved 6 subject scores for Chukwuemeka Adebayo', '::1', '2026-05-27 09:51:50'),
(24, 2, 'publish_student_result', 'Published result for Chukwuemeka Adebayo', '::1', '2026-05-27 09:52:36'),
(25, 2, 'create_news', 'Created news article: dt', '::1', '2026-05-27 10:54:04');

-- --------------------------------------------------------

--
-- Table structure for table `admin_users`
--

CREATE TABLE `admin_users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `role` enum('super_admin','admin','teacher') DEFAULT 'teacher',
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `last_login` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `admin_users`
--

INSERT INTO `admin_users` (`id`, `username`, `password`, `full_name`, `email`, `role`, `is_active`, `created_at`, `last_login`) VALUES
(1, 'admin', '123456', 'System Administrator', 'admin@goodnessomogo.edu.ng', 'super_admin', 1, '2026-02-12 15:36:38', '2026-05-26 12:02:11'),
(2, 'user', 'Action65.', 'Emmanuel', 'emmanuelfredrick66@gmail.com', 'teacher', 1, '2026-05-26 12:04:00', '2026-05-26 12:04:53');

-- --------------------------------------------------------

--
-- Table structure for table `classes`
--

CREATE TABLE `classes` (
  `id` int(11) NOT NULL,
  `class_name` varchar(50) NOT NULL,
  `class_level` varchar(20) NOT NULL,
  `arm` varchar(10) DEFAULT NULL,
  `class_teacher_id` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `classes`
--

INSERT INTO `classes` (`id`, `class_name`, `class_level`, `arm`, `class_teacher_id`, `created_at`) VALUES
(1, 'JSS 1', 'Junior', 'A', NULL, '2026-02-12 15:36:39'),
(2, 'JSS 1', 'Junior', 'B', NULL, '2026-02-12 15:36:39'),
(3, 'JSS 2', 'Junior', 'A', NULL, '2026-02-12 15:36:39'),
(4, 'JSS 2', 'Junior', 'B', NULL, '2026-02-12 15:36:39'),
(5, 'JSS 3', 'Junior', 'A', NULL, '2026-02-12 15:36:39'),
(6, 'JSS 3', 'Junior', 'B', NULL, '2026-02-12 15:36:39'),
(7, 'SS 1', 'Senior', 'Science', NULL, '2026-02-12 15:36:39'),
(8, 'SS 1', 'Senior', 'Arts', NULL, '2026-02-12 15:36:39'),
(9, 'SS 1', 'Senior', 'Commercial', NULL, '2026-02-12 15:36:39'),
(10, 'SS 2', 'Senior', 'Science', NULL, '2026-02-12 15:36:39'),
(11, 'SS 2', 'Senior', 'Arts', NULL, '2026-02-12 15:36:39'),
(12, 'SS 2', 'Senior', 'Commercial', NULL, '2026-02-12 15:36:39'),
(13, 'SS 3', 'Senior', 'Science', NULL, '2026-02-12 15:36:39'),
(14, 'SS 3', 'Senior', 'Arts', NULL, '2026-02-12 15:36:39'),
(15, 'SS 3', 'Senior', 'Commercial', NULL, '2026-02-12 15:36:39');

-- --------------------------------------------------------

--
-- Table structure for table `class_subjects`
--

CREATE TABLE `class_subjects` (
  `id` int(11) NOT NULL,
  `class_id` int(11) NOT NULL,
  `subject_id` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `class_subjects`
--

INSERT INTO `class_subjects` (`id`, `class_id`, `subject_id`, `created_at`) VALUES
(1, 10, 1, '2026-02-12 22:38:02'),
(2, 10, 2, '2026-02-12 22:38:02'),
(3, 10, 3, '2026-02-12 22:38:02'),
(4, 10, 4, '2026-02-12 22:38:02'),
(5, 10, 5, '2026-02-12 22:38:02'),
(6, 10, 14, '2026-02-12 22:38:02'),
(7, 1, 1, '2026-05-26 14:38:52'),
(8, 1, 2, '2026-05-26 14:38:52'),
(9, 1, 37, '2026-05-26 14:38:53'),
(10, 1, 47, '2026-05-26 14:38:53'),
(11, 1, 38, '2026-05-26 14:38:53'),
(12, 1, 39, '2026-05-26 14:38:53'),
(13, 1, 40, '2026-05-26 14:38:53'),
(14, 1, 41, '2026-05-26 14:38:53'),
(15, 1, 15, '2026-05-26 14:38:53'),
(16, 1, 22, '2026-05-26 14:38:53'),
(17, 1, 21, '2026-05-26 14:38:53'),
(18, 1, 12, '2026-05-26 14:38:53'),
(19, 1, 24, '2026-05-26 14:38:53'),
(20, 1, 16, '2026-05-26 14:38:54'),
(21, 1, 6, '2026-05-26 14:38:54'),
(22, 1, 48, '2026-05-26 14:38:54'),
(23, 1, 42, '2026-05-26 14:38:54'),
(24, 2, 1, '2026-05-26 14:38:54'),
(25, 2, 2, '2026-05-26 14:38:54'),
(26, 2, 37, '2026-05-26 14:38:54'),
(27, 2, 47, '2026-05-26 14:38:54'),
(28, 2, 38, '2026-05-26 14:38:54'),
(29, 2, 39, '2026-05-26 14:38:54'),
(30, 2, 40, '2026-05-26 14:38:55'),
(31, 2, 41, '2026-05-26 14:38:55'),
(32, 2, 15, '2026-05-26 14:38:55'),
(33, 2, 22, '2026-05-26 14:38:55'),
(34, 2, 21, '2026-05-26 14:38:55'),
(35, 2, 12, '2026-05-26 14:38:55'),
(36, 2, 24, '2026-05-26 14:38:55'),
(37, 2, 16, '2026-05-26 14:38:55'),
(38, 2, 6, '2026-05-26 14:38:55'),
(39, 2, 48, '2026-05-26 14:38:55'),
(40, 2, 42, '2026-05-26 14:38:56'),
(41, 3, 1, '2026-05-26 14:38:56'),
(42, 3, 2, '2026-05-26 14:38:56'),
(43, 3, 37, '2026-05-26 14:38:56'),
(44, 3, 47, '2026-05-26 14:38:56'),
(45, 3, 38, '2026-05-26 14:38:56'),
(46, 3, 39, '2026-05-26 14:38:56'),
(47, 3, 40, '2026-05-26 14:38:56'),
(48, 3, 41, '2026-05-26 14:38:56'),
(49, 3, 15, '2026-05-26 14:38:57'),
(50, 3, 22, '2026-05-26 14:38:57'),
(51, 3, 21, '2026-05-26 14:38:57'),
(52, 3, 12, '2026-05-26 14:38:57'),
(53, 3, 24, '2026-05-26 14:38:57'),
(54, 3, 16, '2026-05-26 14:38:57'),
(55, 3, 6, '2026-05-26 14:38:57'),
(56, 3, 48, '2026-05-26 14:38:57'),
(57, 3, 42, '2026-05-26 14:38:57'),
(58, 4, 1, '2026-05-26 14:38:58'),
(59, 4, 2, '2026-05-26 14:38:58'),
(60, 4, 37, '2026-05-26 14:38:58'),
(61, 4, 47, '2026-05-26 14:38:58'),
(62, 4, 38, '2026-05-26 14:38:58'),
(63, 4, 39, '2026-05-26 14:38:58'),
(64, 4, 40, '2026-05-26 14:38:58'),
(65, 4, 41, '2026-05-26 14:38:58'),
(66, 4, 15, '2026-05-26 14:38:58'),
(67, 4, 22, '2026-05-26 14:38:59'),
(68, 4, 21, '2026-05-26 14:38:59'),
(69, 4, 12, '2026-05-26 14:38:59'),
(70, 4, 24, '2026-05-26 14:38:59'),
(71, 4, 16, '2026-05-26 14:38:59'),
(72, 4, 6, '2026-05-26 14:38:59'),
(73, 4, 48, '2026-05-26 14:38:59'),
(74, 4, 42, '2026-05-26 14:38:59'),
(75, 5, 1, '2026-05-26 14:38:59'),
(76, 5, 2, '2026-05-26 14:39:00'),
(77, 5, 37, '2026-05-26 14:39:00'),
(78, 5, 47, '2026-05-26 14:39:00'),
(79, 5, 38, '2026-05-26 14:39:00'),
(80, 5, 39, '2026-05-26 14:39:00'),
(81, 5, 40, '2026-05-26 14:39:00'),
(82, 5, 41, '2026-05-26 14:39:00'),
(83, 5, 15, '2026-05-26 14:39:00'),
(84, 5, 22, '2026-05-26 14:39:00'),
(85, 5, 21, '2026-05-26 14:39:00'),
(86, 5, 12, '2026-05-26 14:39:01'),
(87, 5, 24, '2026-05-26 14:39:01'),
(88, 5, 16, '2026-05-26 14:39:01'),
(89, 5, 6, '2026-05-26 14:39:01'),
(90, 5, 48, '2026-05-26 14:39:01'),
(91, 5, 42, '2026-05-26 14:39:01'),
(92, 6, 1, '2026-05-26 14:39:01'),
(93, 6, 2, '2026-05-26 14:39:01'),
(94, 6, 37, '2026-05-26 14:39:01'),
(95, 6, 47, '2026-05-26 14:39:01'),
(96, 6, 38, '2026-05-26 14:39:02'),
(97, 6, 39, '2026-05-26 14:39:02'),
(98, 6, 40, '2026-05-26 14:39:03'),
(99, 6, 41, '2026-05-26 14:39:03'),
(100, 6, 15, '2026-05-26 14:39:03'),
(101, 6, 22, '2026-05-26 14:39:04'),
(102, 6, 21, '2026-05-26 14:39:04'),
(103, 6, 12, '2026-05-26 14:39:04'),
(104, 6, 24, '2026-05-26 14:39:04'),
(105, 6, 16, '2026-05-26 14:39:04'),
(106, 6, 6, '2026-05-26 14:39:04'),
(107, 6, 48, '2026-05-26 14:39:04'),
(108, 6, 42, '2026-05-26 14:39:04'),
(109, 7, 1, '2026-05-26 14:39:04'),
(110, 7, 2, '2026-05-26 14:39:05'),
(111, 7, 3, '2026-05-26 14:39:05'),
(112, 7, 4, '2026-05-26 14:39:05'),
(113, 7, 5, '2026-05-26 14:39:05'),
(114, 7, 17, '2026-05-26 14:39:05'),
(115, 7, 16, '2026-05-26 14:39:05'),
(116, 7, 6, '2026-05-26 14:39:05'),
(117, 7, 15, '2026-05-26 14:39:05'),
(118, 7, 12, '2026-05-26 14:39:05'),
(119, 7, 24, '2026-05-26 14:39:05'),
(120, 7, 48, '2026-05-26 14:39:06'),
(121, 7, 7, '2026-05-26 14:39:06'),
(122, 8, 1, '2026-05-26 14:39:06'),
(123, 8, 2, '2026-05-26 14:39:06'),
(124, 8, 11, '2026-05-26 14:39:06'),
(125, 8, 10, '2026-05-26 14:39:06'),
(126, 8, 42, '2026-05-26 14:39:06'),
(127, 8, 13, '2026-05-26 14:39:06'),
(128, 8, 12, '2026-05-26 14:39:06'),
(129, 8, 24, '2026-05-26 14:39:06'),
(130, 8, 21, '2026-05-26 14:39:06'),
(131, 8, 22, '2026-05-26 14:39:07'),
(132, 8, 15, '2026-05-26 14:39:07'),
(133, 8, 48, '2026-05-26 14:39:07'),
(134, 8, 7, '2026-05-26 14:39:07'),
(135, 9, 1, '2026-05-26 14:39:07'),
(136, 9, 2, '2026-05-26 14:39:07'),
(137, 9, 7, '2026-05-26 14:39:07'),
(138, 9, 9, '2026-05-26 14:39:08'),
(139, 9, 8, '2026-05-26 14:39:08'),
(140, 9, 10, '2026-05-26 14:39:08'),
(141, 9, 12, '2026-05-26 14:39:08'),
(142, 9, 24, '2026-05-26 14:39:08'),
(143, 9, 15, '2026-05-26 14:39:08'),
(144, 9, 16, '2026-05-26 14:39:08'),
(145, 9, 48, '2026-05-26 14:39:09'),
(146, 9, 13, '2026-05-26 14:39:09'),
(152, 10, 17, '2026-05-26 14:39:09'),
(153, 10, 16, '2026-05-26 14:39:10'),
(154, 10, 6, '2026-05-26 14:39:10'),
(155, 10, 15, '2026-05-26 14:39:10'),
(156, 10, 12, '2026-05-26 14:39:10'),
(157, 10, 24, '2026-05-26 14:39:10'),
(158, 10, 48, '2026-05-26 14:39:10'),
(159, 10, 7, '2026-05-26 14:39:10'),
(160, 11, 1, '2026-05-26 14:39:10'),
(161, 11, 2, '2026-05-26 14:39:10'),
(162, 11, 11, '2026-05-26 14:39:11'),
(163, 11, 10, '2026-05-26 14:39:11'),
(164, 11, 42, '2026-05-26 14:39:11'),
(165, 11, 13, '2026-05-26 14:39:11'),
(166, 11, 12, '2026-05-26 14:39:11'),
(167, 11, 24, '2026-05-26 14:39:11'),
(168, 11, 21, '2026-05-26 14:39:11'),
(169, 11, 22, '2026-05-26 14:39:11'),
(170, 11, 15, '2026-05-26 14:39:11'),
(171, 11, 48, '2026-05-26 14:39:11'),
(172, 11, 7, '2026-05-26 14:39:11'),
(173, 12, 1, '2026-05-26 14:39:12'),
(174, 12, 2, '2026-05-26 14:39:12'),
(175, 12, 7, '2026-05-26 14:39:12'),
(176, 12, 9, '2026-05-26 14:39:12'),
(177, 12, 8, '2026-05-26 14:39:12'),
(178, 12, 10, '2026-05-26 14:39:12'),
(179, 12, 12, '2026-05-26 14:39:12'),
(180, 12, 24, '2026-05-26 14:39:12'),
(181, 12, 15, '2026-05-26 14:39:12'),
(182, 12, 16, '2026-05-26 14:39:12'),
(183, 12, 48, '2026-05-26 14:39:12'),
(184, 12, 13, '2026-05-26 14:39:13'),
(185, 13, 1, '2026-05-26 14:39:13'),
(186, 13, 2, '2026-05-26 14:39:13'),
(187, 13, 3, '2026-05-26 14:39:13'),
(188, 13, 4, '2026-05-26 14:39:13'),
(189, 13, 5, '2026-05-26 14:39:13'),
(190, 13, 17, '2026-05-26 14:39:13'),
(191, 13, 16, '2026-05-26 14:39:13'),
(192, 13, 6, '2026-05-26 14:39:13'),
(193, 13, 15, '2026-05-26 14:39:14'),
(194, 13, 12, '2026-05-26 14:39:14'),
(195, 13, 24, '2026-05-26 14:39:14'),
(196, 13, 48, '2026-05-26 14:39:14'),
(197, 13, 7, '2026-05-26 14:39:14'),
(198, 14, 1, '2026-05-26 14:39:14'),
(199, 14, 2, '2026-05-26 14:39:14'),
(200, 14, 11, '2026-05-26 14:39:14'),
(201, 14, 10, '2026-05-26 14:39:15'),
(202, 14, 42, '2026-05-26 14:39:15'),
(203, 14, 13, '2026-05-26 14:39:15'),
(204, 14, 12, '2026-05-26 14:39:15'),
(205, 14, 24, '2026-05-26 14:39:15'),
(206, 14, 21, '2026-05-26 14:39:15'),
(207, 14, 22, '2026-05-26 14:39:15'),
(208, 14, 15, '2026-05-26 14:39:15'),
(209, 14, 48, '2026-05-26 14:39:15'),
(210, 14, 7, '2026-05-26 14:39:15'),
(211, 15, 1, '2026-05-26 14:39:15'),
(212, 15, 2, '2026-05-26 14:39:16'),
(213, 15, 7, '2026-05-26 14:39:16'),
(214, 15, 9, '2026-05-26 14:39:16'),
(215, 15, 8, '2026-05-26 14:39:16'),
(216, 15, 10, '2026-05-26 14:39:16'),
(217, 15, 12, '2026-05-26 14:39:16'),
(218, 15, 24, '2026-05-26 14:39:16'),
(219, 15, 15, '2026-05-26 14:39:16'),
(220, 15, 16, '2026-05-26 14:39:16'),
(221, 15, 48, '2026-05-26 14:39:16'),
(222, 15, 13, '2026-05-26 14:39:16'),
(223, 8, 71, '2026-05-27 09:47:37');

-- --------------------------------------------------------

--
-- Table structure for table `curriculum_subjects`
--

CREATE TABLE `curriculum_subjects` (
  `id` int(11) NOT NULL,
  `subject_name` varchar(255) NOT NULL,
  `category` enum('Core','Vocational','Languages','Elective') DEFAULT 'Core',
  `class_level` enum('JSS1','JSS2','JSS3','SSS1','SSS2','SSS3','All') DEFAULT 'All',
  `description` text DEFAULT NULL,
  `display_order` int(11) DEFAULT 0,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `curriculum_subjects`
--

INSERT INTO `curriculum_subjects` (`id`, `subject_name`, `category`, `class_level`, `description`, `display_order`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 'Mathematics', 'Core', 'All', 'Fundamental mathematical concepts and problem-solving', 1, 1, '2026-02-12 18:14:49', '2026-02-12 18:14:49'),
(2, 'English Language', 'Core', 'All', 'Reading, writing, grammar, and communication skills', 2, 1, '2026-02-12 18:14:49', '2026-02-12 18:14:49'),
(3, 'Basic Science', 'Core', 'All', 'Introduction to Physics, Chemistry, and Biology', 3, 1, '2026-02-12 18:14:49', '2026-02-12 18:14:49'),
(4, 'Basic Technology', 'Core', 'All', 'Technical drawing and basic technology concepts', 4, 1, '2026-02-12 18:14:49', '2026-02-12 18:14:49'),
(5, 'Civic Education', 'Core', 'All', 'Citizenship, rights, and civic responsibilities', 5, 1, '2026-02-12 18:14:49', '2026-02-12 18:14:49'),
(6, 'Physical & Health Education', 'Core', 'All', 'Sports, fitness, and health awareness', 6, 1, '2026-02-12 18:14:49', '2026-02-12 18:14:49'),
(7, 'Cultural & Creative Arts', 'Vocational', 'All', 'Music, drama, and visual arts', 7, 1, '2026-02-12 18:14:49', '2026-02-12 18:14:49'),
(8, 'Home Economics', 'Vocational', 'All', 'Nutrition, clothing, and home management', 8, 1, '2026-02-12 18:14:49', '2026-02-12 18:14:49'),
(9, 'Agricultural Science', 'Vocational', 'All', 'Crop production, animal husbandry, and farming', 9, 1, '2026-02-12 18:14:49', '2026-02-12 18:14:49'),
(10, 'Business Studies', 'Vocational', 'All', 'Basic business concepts and entrepreneurship', 10, 1, '2026-02-12 18:14:49', '2026-02-12 18:14:49'),
(11, 'French Language', 'Languages', 'All', 'French grammar, vocabulary, and conversation', 11, 1, '2026-02-12 18:14:49', '2026-02-12 18:14:49'),
(12, 'Yoruba Language', 'Languages', 'All', 'Yoruba language and culture', 12, 1, '2026-02-12 18:14:49', '2026-02-12 18:14:49'),
(13, 'Igbo Language', 'Languages', 'All', 'Igbo language and culture', 13, 1, '2026-02-12 18:14:49', '2026-02-12 18:14:49'),
(14, 'Hausa Language', 'Languages', 'All', 'Hausa language and culture', 14, 1, '2026-02-12 18:14:49', '2026-02-12 18:14:49'),
(15, 'Computer Studies', 'Core', 'All', 'Basic computing, coding, and digital literacy', 15, 1, '2026-02-12 18:14:49', '2026-02-12 18:14:49'),
(16, 'Leadership Development', 'Core', 'All', 'Leadership skills, ethics, and character building', 16, 1, '2026-02-12 18:14:49', '2026-02-12 18:14:49'),
(17, 'Physics', 'Core', 'SSS1', 'Mechanics, electricity, and modern physics', 20, 1, '2026-02-12 18:14:49', '2026-02-12 18:14:49'),
(18, 'Chemistry', 'Core', 'SSS1', 'Chemical reactions, organic and inorganic chemistry', 21, 1, '2026-02-12 18:14:49', '2026-02-12 18:14:49'),
(19, 'Biology', 'Core', 'SSS1', 'Life sciences, ecology, and human biology', 22, 1, '2026-02-12 18:14:49', '2026-02-12 18:14:49'),
(20, 'Further Mathematics', 'Elective', 'SSS1', 'Advanced mathematics for science students', 23, 1, '2026-02-12 18:14:49', '2026-02-12 18:14:49'),
(21, 'Literature in English', 'Elective', 'SSS1', 'Poetry, prose, and drama analysis', 24, 1, '2026-02-12 18:14:49', '2026-02-12 18:14:49'),
(22, 'Government', 'Elective', 'SSS1', 'Political systems, democracy, and governance', 25, 1, '2026-02-12 18:14:49', '2026-02-12 18:14:49'),
(23, 'History', 'Elective', 'SSS1', 'World history and African history', 26, 1, '2026-02-12 18:14:49', '2026-02-12 18:14:49'),
(24, 'CRS/IRS', 'Elective', 'SSS1', 'Christian/Islamic Religious Studies', 27, 1, '2026-02-12 18:14:49', '2026-02-12 18:14:49'),
(25, 'Economics', 'Elective', 'SSS1', 'Micro and macroeconomics principles', 28, 1, '2026-02-12 18:14:49', '2026-02-12 18:14:49'),
(26, 'Accounting', 'Elective', 'SSS1', 'Financial accounting and bookkeeping', 29, 1, '2026-02-12 18:14:49', '2026-02-12 18:14:49'),
(27, 'Commerce', 'Elective', 'SSS1', 'Trade, marketing, and business operations', 30, 1, '2026-02-12 18:14:49', '2026-02-12 18:14:49'),
(28, 'Mathematics', 'Core', 'All', 'Fundamental mathematical concepts and problem-solving', 1, 1, '2026-02-12 18:31:34', '2026-02-12 18:31:34'),
(29, 'English Language', 'Core', 'All', 'Reading, writing, grammar, and communication skills', 2, 1, '2026-02-12 18:31:34', '2026-02-12 18:31:34'),
(30, 'Basic Science', 'Core', 'All', 'Introduction to Physics, Chemistry, and Biology', 3, 1, '2026-02-12 18:31:34', '2026-02-12 18:31:34'),
(31, 'Basic Technology', 'Core', 'All', 'Technical drawing and basic technology concepts', 4, 1, '2026-02-12 18:31:34', '2026-02-12 18:31:34'),
(32, 'Civic Education', 'Core', 'All', 'Citizenship, rights, and civic responsibilities', 5, 1, '2026-02-12 18:31:34', '2026-02-12 18:31:34'),
(33, 'Physical & Health Education', 'Core', 'All', 'Sports, fitness, and health awareness', 6, 1, '2026-02-12 18:31:34', '2026-02-12 18:31:34'),
(34, 'Cultural & Creative Arts', 'Vocational', 'All', 'Music, drama, and visual arts', 7, 1, '2026-02-12 18:31:34', '2026-02-12 18:31:34'),
(35, 'Home Economics', 'Vocational', 'All', 'Nutrition, clothing, and home management', 8, 1, '2026-02-12 18:31:34', '2026-02-12 18:31:34'),
(36, 'Agricultural Science', 'Vocational', 'All', 'Crop production, animal husbandry, and farming', 9, 1, '2026-02-12 18:31:34', '2026-02-12 18:31:34'),
(37, 'Business Studies', 'Vocational', 'All', 'Basic business concepts and entrepreneurship', 10, 1, '2026-02-12 18:31:34', '2026-02-12 18:31:34'),
(38, 'French Language', 'Languages', 'All', 'French grammar, vocabulary, and conversation', 11, 1, '2026-02-12 18:31:34', '2026-02-12 18:31:34'),
(39, 'Yoruba Language', 'Languages', 'All', 'Yoruba language and culture', 12, 1, '2026-02-12 18:31:34', '2026-02-12 18:31:34'),
(40, 'Igbo Language', 'Languages', 'All', 'Igbo language and culture', 13, 1, '2026-02-12 18:31:34', '2026-02-12 18:31:34'),
(41, 'Hausa Language', 'Languages', 'All', 'Hausa language and culture', 14, 1, '2026-02-12 18:31:34', '2026-02-12 18:31:34'),
(42, 'Computer Studies', 'Core', 'All', 'Basic computing, coding, and digital literacy', 15, 1, '2026-02-12 18:31:34', '2026-02-12 18:31:34'),
(43, 'Leadership Development', 'Core', 'All', 'Leadership skills, ethics, and character building', 16, 1, '2026-02-12 18:31:34', '2026-02-12 18:31:34'),
(44, 'Physics', 'Core', 'SSS1', 'Mechanics, electricity, and modern physics', 20, 1, '2026-02-12 18:31:34', '2026-02-12 18:31:34'),
(45, 'Chemistry', 'Core', 'SSS1', 'Chemical reactions, organic and inorganic chemistry', 21, 1, '2026-02-12 18:31:34', '2026-02-12 18:31:34'),
(46, 'Biology', 'Core', 'SSS1', 'Life sciences, ecology, and human biology', 22, 1, '2026-02-12 18:31:34', '2026-02-12 18:31:34'),
(47, 'Further Mathematics', 'Elective', 'SSS1', 'Advanced mathematics for science students', 23, 1, '2026-02-12 18:31:34', '2026-02-12 18:31:34'),
(48, 'Literature in English', 'Elective', 'SSS1', 'Poetry, prose, and drama analysis', 24, 1, '2026-02-12 18:31:34', '2026-02-12 18:31:34'),
(49, 'Government', 'Elective', 'SSS1', 'Political systems, democracy, and governance', 25, 1, '2026-02-12 18:31:34', '2026-02-12 18:31:34'),
(50, 'History', 'Elective', 'SSS1', 'World history and African history', 26, 1, '2026-02-12 18:31:34', '2026-02-12 18:31:34'),
(51, 'CRS/IRS', 'Elective', 'SSS1', 'Christian/Islamic Religious Studies', 27, 1, '2026-02-12 18:31:34', '2026-02-12 18:31:34'),
(52, 'Economics', 'Elective', 'SSS1', 'Micro and macroeconomics principles', 28, 1, '2026-02-12 18:31:34', '2026-02-12 18:31:34'),
(53, 'Accounting', 'Elective', 'SSS1', 'Financial accounting and bookkeeping', 29, 1, '2026-02-12 18:31:34', '2026-02-12 18:31:34'),
(54, 'Commerce', 'Elective', 'SSS1', 'Trade, marketing, and business operations', 30, 1, '2026-02-12 18:31:34', '2026-02-12 18:31:34');

-- --------------------------------------------------------

--
-- Table structure for table `gallery_images`
--

CREATE TABLE `gallery_images` (
  `id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `image_path` varchar(255) NOT NULL,
  `category` enum('Campus','Classrooms','Facilities','Sports','Events') NOT NULL,
  `display_order` int(11) DEFAULT 0,
  `is_active` tinyint(1) DEFAULT 1,
  `uploaded_by` varchar(100) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `gallery_images`
--

INSERT INTO `gallery_images` (`id`, `title`, `description`, `image_path`, `category`, `display_order`, `is_active`, `uploaded_by`, `created_at`, `updated_at`) VALUES
(4, 'Modern Classroom', 'Air-conditioned, spacious learning spaces', 'classroom-modern.jpg', 'Classrooms', 4, 1, 'Admin', '2026-02-12 17:33:57', '2026-02-12 17:33:57'),
(5, 'Smart Classroom', 'Interactive whiteboards and projectors', 'classroom-smart.jpg', 'Classrooms', 5, 1, 'Admin', '2026-02-12 17:33:57', '2026-02-12 17:33:57'),
(6, 'STEM Laboratory', 'State-of-the-art science equipment', 'stem-lab.jpg', 'Facilities', 6, 1, 'Admin', '2026-02-12 17:33:57', '2026-02-12 17:33:57'),
(7, 'School Library', '10,000+ books and digital resources', 'library.jpg', 'Facilities', 7, 1, 'Admin', '2026-02-12 17:33:57', '2026-02-12 17:33:57'),
(8, 'Computer Laboratory', 'Latest technology for digital learning', 'computer-lab.jpg', 'Facilities', 8, 1, 'Admin', '2026-02-12 17:33:57', '2026-02-12 17:33:57'),
(9, 'Sports Complex', 'Football, basketball, and athletics', 'sports-field.jpg', 'Sports', 9, 1, 'Admin', '2026-02-12 17:33:57', '2026-02-12 17:33:57'),
(10, 'Indoor Sports Hall', 'Table tennis, badminton, and more', 'sports-indoor.jpg', 'Sports', 10, 1, 'Admin', '2026-02-12 17:33:57', '2026-02-12 17:33:57'),
(11, 'Graduation Ceremony', 'Celebrating our graduates success', 'graduation.jpg', 'Events', 11, 1, 'Admin', '2026-02-12 17:33:57', '2026-02-12 17:33:57'),
(12, 'Cultural Day', 'Celebrating Nigerias rich diversity', 'cultural-day.jpg', 'Events', 12, 1, 'Admin', '2026-02-12 17:33:57', '2026-02-12 17:33:57'),
(13, 'somethin new', 'all', '1770917705_698e0f4957627.png', 'Facilities', 0, 1, 'System Administrator', '2026-02-12 17:35:05', '2026-02-12 17:35:05');

-- --------------------------------------------------------

--
-- Table structure for table `grading_system`
--

CREATE TABLE `grading_system` (
  `id` int(11) NOT NULL,
  `min_score` decimal(5,2) NOT NULL,
  `max_score` decimal(5,2) NOT NULL,
  `grade` varchar(2) NOT NULL,
  `remark` varchar(50) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `grading_system`
--

INSERT INTO `grading_system` (`id`, `min_score`, `max_score`, `grade`, `remark`, `created_at`) VALUES
(1, 75.00, 100.00, 'A1', 'Excellent', '2026-02-12 22:38:01'),
(2, 70.00, 74.00, 'B2', 'Very Good', '2026-02-12 22:38:01'),
(3, 65.00, 69.00, 'B3', 'Good', '2026-02-12 22:38:01'),
(4, 60.00, 64.00, 'C4', 'Credit', '2026-02-12 22:38:01'),
(5, 55.00, 59.00, 'C5', 'Credit', '2026-02-12 22:38:01'),
(6, 50.00, 54.00, 'C6', 'Credit', '2026-02-12 22:38:01'),
(7, 45.00, 49.00, 'D7', 'Pass', '2026-02-12 22:38:01'),
(8, 40.00, 44.00, 'E8', 'Pass', '2026-02-12 22:38:01'),
(9, 0.00, 39.00, 'F9', 'Fail', '2026-02-12 22:38:01');

-- --------------------------------------------------------

--
-- Table structure for table `news_articles`
--

CREATE TABLE `news_articles` (
  `id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `slug` varchar(255) NOT NULL,
  `category` enum('Academics','Sports','Events','Facilities','Achievements','General') DEFAULT 'General',
  `excerpt` text DEFAULT NULL,
  `content` text NOT NULL,
  `featured_image` varchar(255) DEFAULT NULL,
  `author` varchar(100) DEFAULT NULL,
  `views` int(11) DEFAULT 0,
  `is_published` tinyint(1) DEFAULT 1,
  `published_date` date DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `news_articles`
--

INSERT INTO `news_articles` (`id`, `title`, `slug`, `category`, `excerpt`, `content`, `featured_image`, `author`, `views`, `is_published`, `published_date`, `created_at`, `updated_at`) VALUES
(2, 'Commissioning of the New Ultra-Modern STEM Laboratory', 'commissioning-ultra-modern-stem-laboratory', 'Facilities', 'Enhancing practical learning with state-of-the-art robotics and science equipment.', '<p>On October 28, 2024, Goodness Omogo Leadership Academy commissioned its new state-of-the-art STEM Laboratory, marking a significant milestone in our commitment to 21st-century education.</p>\n\n<h3>World-Class Facilities</h3>\n<p>The new laboratory features:</p>\n<ul>\n<li>Advanced robotics equipment for hands-on learning</li>\n<li>3D printing technology for design and innovation</li>\n<li>Modern chemistry and physics apparatus</li>\n<li>Computer-aided design (CAD) workstations</li>\n<li>Virtual reality learning modules</li>\n<li>Biotechnology research corner</li>\n</ul>\n\n<p>The facility was commissioned by Dr. Ngozi Okonjo-Iweala, Former Finance Minister and current Director-General of the World Trade Organization, who praised the school for investing in practical STEM education.</p>\n\n<h3>Impact on Learning</h3>\n<p>\"This laboratory will transform how our students engage with science and technology,\" said Mr. Adebayo Johnson, Head of Sciences. \"Students can now conduct experiments and projects that were previously impossible.\"</p>\n\n<p>The laboratory is equipped to accommodate 40 students at a time and will be available for both regular classes and after-school STEM clubs.</p>\n\n<p>This investment aligns with our vision to produce graduates who are not just academically sound but also practically skilled and innovation-ready.</p>', 'stem-lab.jpg', 'Admin', 1, 1, '2024-10-28', '2026-02-12 16:03:14', '2026-02-12 16:04:14'),
(3, 'Inter-House Sports Festival 2024 - Red House Triumphs', 'inter-house-sports-festival-2024', 'Sports', 'Red House takes the trophy in a thrilling display of athletic excellence.', '<p>The annual Inter-House Sports Festival concluded on September 15, 2024, with Red House emerging victorious in a fiercely contested competition.</p>\n\n<h3>Final Standings</h3>\n<ol>\n<li><strong>Red House:</strong> 450 points</li>\n<li><strong>Blue House:</strong> 420 points</li>\n<li><strong>Green House:</strong> 380 points</li>\n<li><strong>Yellow House:</strong> 350 points</li>\n</ol>\n\n<h3>Highlights of the Day</h3>\n<p>The festival showcased outstanding athletic talent across various events including:</p>\n<ul>\n<li>Track and Field events (100m, 200m, 400m, relay races)</li>\n<li>Field events (Long jump, High jump, Shot put)</li>\n<li>Ball games (Football, Basketball, Volleyball)</li>\n<li>March past and House chants competition</li>\n</ul>\n\n<h3>Outstanding Athletes</h3>\n<p><strong>Male Athlete of the Year:</strong> Master Tunde Bakare (Red House) - Won gold in 100m, 200m, and Long Jump</p>\n<p><strong>Female Athlete of the Year:</strong> Miss Ada Nwosu (Blue House) - Dominated middle-distance races</p>\n\n<p>The Chief Guest, Olympic Gold Medalist Mr. Olusoji Fasuba, commended the students for their sportsmanship and encouraged them to pursue sports alongside academics.</p>\n\n<p>\"Sports build character, discipline, and resilience - qualities that will serve you well in life,\" Mr. Fasuba advised.</p>\n\n<p>The school is proud of all participants who demonstrated the true spirit of healthy competition and teamwork.</p>', 'sports-festival.jpg', 'Sports Coordinator', 0, 1, '2024-09-15', '2026-02-12 16:03:14', '2026-02-12 16:03:14'),
(4, 'STEM Competition: Our Students Win National Robotics Challenge', 'students-win-national-robotics-challenge', 'Achievements', 'Three of our students represented Nigeria at the Pan-African Robotics Competition.', '<p>Goodness Omogo Leadership Academy students have brought glory to the institution by winning the National Robotics Challenge held in Abuja on November 5, 2024.</p>\n\n<h3>The Winning Team</h3>\n<p>Our team, named \"GOLA Innovators,\" comprised:</p>\n<ul>\n<li>Master Chinedu Okoro (SS2 Science) - Team Lead</li>\n<li>Miss Sarah Adeleke (SS2 Science) - Programmer</li>\n<li>Master Ibrahim Musa (SS2 Science) - Designer</li>\n</ul>\n\n<p>They competed against 50 teams from secondary schools across Nigeria with their project: \"AgriBot - An Automated Irrigation System for Smart Farming.\"</p>\n\n<h3>The Innovation</h3>\n<p>AgriBot is a solar-powered robot designed to:</p>\n<ul>\n<li>Monitor soil moisture levels</li>\n<li>Automatically irrigate farmland based on data</li>\n<li>Send real-time alerts to farmers via SMS</li>\n<li>Reduce water wastage by 60%</li>\n</ul>\n\n<p>The project impressed the judges with its practical application and potential to solve real agricultural challenges in Nigeria.</p>\n\n<h3>International Recognition</h3>\n<p>As national champions, the team will represent Nigeria at the Pan-African Robotics Competition in Nairobi, Kenya, in March 2025.</p>\n\n<p>\"We are incredibly proud of these brilliant minds,\" said Prof. Alewale Omogo, School Principal. \"This achievement validates our investment in STEM education and shows that our students can compete globally.\"</p>\n\n<p>The school has pledged full support for the team as they prepare for the international competition.</p>', 'robotics-win.jpg', 'Admin', 1, 1, '2024-11-05', '2026-02-12 16:03:14', '2026-02-13 09:56:18'),
(5, 'World Teachers Day Celebration 2024', 'world-teachers-day-2024', 'Events', 'Honoring the dedicated educators who shape future leaders.', '<p>On October 5, 2024, Goodness Omogo Leadership Academy joined the global community in celebrating World Teachers Day, honoring our exceptional teaching staff.</p>\n\n<h3>Theme: \"Teachers Make the Difference\"</h3>\n<p>The celebration featured special presentations by students, award ceremonies, and a thanksgiving service acknowledging the tireless efforts of our educators.</p>\n\n<h3>Teacher of the Year Awards</h3>\n<p><strong>Overall Teacher of the Year:</strong> Mrs. Blessing Okafor (English Language)</p>\n<p><strong>Science Teacher Award:</strong> Mr. Kunle Ademola (Physics)</p>\n<p><strong>Humanities Teacher Award:</strong> Miss Ngozi Eze (Government)</p>\n<p><strong>Most Innovative Teacher:</strong> Mr. Tayo Williams (Computer Science)</p>\n\n<h3>Student Tributes</h3>\n<p>Students expressed heartfelt appreciation through poems, songs, and video presentations showcasing how teachers have impacted their lives.</p>\n\n<p>\"Teachers are not just instructors; they are mentors, role models, and life coaches,\" said Miss Jennifer Ade, Head Girl, during her address.</p>\n\n<p>The management presented gifts and certificates of appreciation to all teaching staff and reaffirmed its commitment to teacher welfare and professional development.</p>\n\n<p>We salute all our teachers for their dedication to nurturing excellence!</p>', 'teachers-day.jpg', 'Admin', 0, 1, '2024-10-05', '2026-02-12 16:03:14', '2026-02-12 16:03:14'),
(6, 'New School Library Opens - 10,000 Books Collection', 'new-library-opens-10000-books', 'Facilities', 'A modern reading sanctuary for knowledge seekers.', '<p>The Goodness Omogo Leadership Academy Library officially opened its doors on August 20, 2024, offering students access to over 10,000 books and digital resources.</p>\n\n<h3>Library Features</h3>\n<ul>\n<li>Main reading hall with 200 seating capacity</li>\n<li>Quiet study rooms for individual research</li>\n<li>Digital resource center with 50 computers</li>\n<li>Audio-visual room for multimedia learning</li>\n<li>Children\'s corner for junior students</li>\n<li>E-library with access to international journals</li>\n</ul>\n\n<h3>Book Collection</h3>\n<p>The library houses diverse materials including:</p>\n<ul>\n<li>Subject textbooks and reference materials</li>\n<li>Literature classics and contemporary fiction</li>\n<li>Biography and history books</li>\n<li>Science and technology publications</li>\n<li>Career guidance materials</li>\n<li>Newspapers and magazines</li>\n</ul>\n\n<h3>Operating Hours</h3>\n<p>Monday - Friday: 7:30 AM - 6:00 PM<br>\nSaturday: 9:00 AM - 2:00 PM</p>\n\n<p>\"A school without a good library is like a house without windows,\" remarked the Librarian, Mrs. Funmi Adeyemi. \"This facility will significantly enhance our students\' research capabilities and reading culture.\"</p>\n\n<p>Students have expressed excitement about the new facility and are already making good use of its resources.</p>', 'library-opening.jpg', 'Admin', 0, 1, '2024-08-20', '2026-02-12 16:03:14', '2026-02-12 16:03:14'),
(7, 'Test Article 1770914632', 'test-article-1770914632', 'General', 'This is a test excerpt', '<p>This is test content</p>', 'test-image.jpg', 'Test Admin', 0, 1, '2026-02-12', '2026-02-12 16:43:52', '2026-02-12 16:43:52'),
(8, 'fsv', 'fsv', 'Academics', 'wfrfe', 'qfrgt', '1770914909_698e045dd76ea.png', 'System Administrator', 0, 1, '2026-02-13', '2026-02-12 16:48:29', '2026-02-12 16:48:29'),
(9, 'dt', 'dt', 'Academics', 'ytt', 'td', '1779879244_6a16cd4c1d5e2.png', 'Emmanuel', 0, 1, '2026-05-27', '2026-05-27 10:54:04', '2026-05-27 10:54:04');

-- --------------------------------------------------------

--
-- Table structure for table `results`
--

CREATE TABLE `results` (
  `id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `subject_id` int(11) NOT NULL,
  `class_id` int(11) NOT NULL,
  `session_id` int(11) NOT NULL,
  `term_id` int(11) NOT NULL,
  `ca1` decimal(5,2) DEFAULT 0.00 COMMENT 'CA 1 out of 20',
  `ca2` decimal(5,2) DEFAULT 0.00 COMMENT 'CA 2 out of 20',
  `exam_score` decimal(5,2) DEFAULT 0.00 COMMENT 'Exam out of 60',
  `total_score` decimal(5,2) DEFAULT 0.00 COMMENT 'Total out of 100',
  `grade` varchar(2) DEFAULT NULL,
  `remark` varchar(100) DEFAULT NULL,
  `entered_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `results`
--

INSERT INTO `results` (`id`, `student_id`, `subject_id`, `class_id`, `session_id`, `term_id`, `ca1`, `ca2`, `exam_score`, `total_score`, `grade`, `remark`, `entered_by`, `created_at`, `updated_at`) VALUES
(1, 1, 1, 10, 1, 1, 18.00, 17.00, 52.00, 87.00, 'A1', 'Excellent performance', 2, '2026-02-12 22:38:02', '2026-05-27 09:21:31'),
(2, 1, 2, 10, 1, 1, 19.00, 18.00, 58.00, 95.00, 'A1', 'Distinction, keep it up', 2, '2026-02-12 22:38:02', '2026-05-27 09:21:31'),
(3, 1, 14, 10, 1, 1, 20.00, 19.00, 55.00, 94.00, 'A1', 'Outstanding leadership traits', 2, '2026-02-12 22:38:02', '2026-05-27 09:21:30'),
(4, 1, 3, 10, 1, 1, 15.00, 14.00, 42.00, 71.00, 'B2', 'Very good effort', 2, '2026-02-12 22:38:02', '2026-05-27 09:21:31'),
(5, 1, 4, 10, 1, 1, 16.00, 15.00, 44.00, 75.00, 'A1', 'A solid performance', 2, '2026-02-12 22:38:02', '2026-05-27 09:21:30'),
(6, 1, 5, 10, 1, 1, 17.00, 18.00, 45.00, 80.00, 'A1', 'Commendable work', 2, '2026-02-12 22:38:02', '2026-05-27 09:21:30'),
(11, 1, 24, 10, 1, 1, 0.00, 10.00, 0.00, 10.00, 'F9', 'Fail', 2, '2026-05-27 09:21:31', '2026-05-27 09:21:31'),
(12, 1, 48, 10, 1, 1, 20.00, 0.00, 40.00, 60.00, 'C4', 'Credit', 2, '2026-05-27 09:21:31', '2026-05-27 09:21:31'),
(15, 1, 6, 10, 2, 1, 10.00, 0.00, 60.00, 70.00, 'B2', 'Very Good', 2, '2026-05-27 09:51:49', '2026-05-27 09:51:49'),
(16, 1, 5, 10, 2, 1, 20.00, 0.00, 60.00, 80.00, 'A1', 'Excellent', 2, '2026-05-27 09:51:50', '2026-05-27 09:51:50'),
(17, 1, 16, 10, 2, 1, 0.00, 0.00, 60.00, 60.00, 'C4', 'Credit', 2, '2026-05-27 09:51:50', '2026-05-27 09:51:50'),
(18, 1, 48, 10, 2, 1, 6.00, 10.00, 60.00, 76.00, 'A1', 'Excellent', 2, '2026-05-27 09:51:50', '2026-05-27 09:51:50'),
(19, 1, 15, 10, 2, 1, 20.00, 10.00, 0.00, 30.00, 'F9', 'Fail', 2, '2026-05-27 09:51:50', '2026-05-27 09:51:50'),
(20, 1, 3, 10, 2, 1, 0.00, 0.00, 54.00, 54.00, 'C6', 'Credit', 2, '2026-05-27 09:51:50', '2026-05-27 09:51:50');

--
-- Triggers `results`
--
DELIMITER $$
CREATE TRIGGER `trg_results_before_insert` BEFORE INSERT ON `results` FOR EACH ROW BEGIN
  SET NEW.total_score = NEW.ca1 + NEW.ca2 + NEW.exam_score;
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `trg_results_before_update` BEFORE UPDATE ON `results` FOR EACH ROW BEGIN
  SET NEW.total_score = NEW.ca1 + NEW.ca2 + NEW.exam_score;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `result_summary`
--

CREATE TABLE `result_summary` (
  `id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `class_id` int(11) NOT NULL,
  `session_id` int(11) NOT NULL,
  `term_id` int(11) NOT NULL,
  `total_subjects` int(11) DEFAULT 0,
  `total_score` decimal(8,2) DEFAULT 0.00,
  `average_score` decimal(5,2) DEFAULT 0.00,
  `overall_grade` varchar(2) DEFAULT NULL,
  `overall_position` varchar(20) DEFAULT NULL,
  `class_size` int(11) DEFAULT NULL,
  `attendance_present` int(11) DEFAULT NULL,
  `attendance_total` int(11) DEFAULT NULL,
  `class_teacher_comment` text DEFAULT NULL,
  `class_teacher_name` varchar(150) DEFAULT NULL,
  `principal_comment` text DEFAULT NULL,
  `principal_name` varchar(150) DEFAULT 'DR. SAMUEL OMOGO',
  `published` tinyint(1) DEFAULT 0,
  `published_at` timestamp NULL DEFAULT NULL,
  `result_unique_id` varchar(50) DEFAULT NULL,
  `date_issued` date DEFAULT NULL,
  `next_term_begins` date DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `result_summary`
--

INSERT INTO `result_summary` (`id`, `student_id`, `class_id`, `session_id`, `term_id`, `total_subjects`, `total_score`, `average_score`, `overall_grade`, `overall_position`, `class_size`, `attendance_present`, `attendance_total`, `class_teacher_comment`, `class_teacher_name`, `principal_comment`, `principal_name`, `published`, `published_at`, `result_unique_id`, `date_issued`, `next_term_begins`, `created_at`, `updated_at`) VALUES
(1, 1, 10, 1, 1, 8, 572.00, 71.50, 'B2', '3rd', 45, 64, 65, 'Chukwuemeka is a diligent and highly focused student. He shows great potential in leadership and consistently assists his peers. His academic growth this term has been remarkable.', 'Mrs. Sarah Okon', 'A very impressive result. You are a true ambassador of the Academy\'s values. Maintain this standard of excellence.', 'DR. SAMUEL OMOGO', 1, NULL, 'GOLA-2026-00001', '2024-07-15', '2026-06-09', '2026-02-12 22:38:02', '2026-05-27 09:38:20'),
(3, 1, 10, 2, 1, 6, 370.00, 61.67, 'C4', '3rd', 35, 60, 400, 'yufyy', 'yfyfu', 'pp', 'DR. SAMUEL OMOGO', 1, NULL, 'GOLA-2026-00001', '2026-05-27', '2026-05-26', '2026-05-27 09:52:35', '2026-05-27 09:52:35');

-- --------------------------------------------------------

--
-- Table structure for table `scratch_cards`
--

CREATE TABLE `scratch_cards` (
  `id` int(11) NOT NULL,
  `pin_code` varchar(20) NOT NULL,
  `serial_number` varchar(30) DEFAULT NULL,
  `max_uses` int(11) NOT NULL DEFAULT 5,
  `times_used` int(11) NOT NULL DEFAULT 0,
  `is_activated` tinyint(1) NOT NULL DEFAULT 0,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `scratch_cards`
--

INSERT INTO `scratch_cards` (`id`, `pin_code`, `serial_number`, `max_uses`, `times_used`, `is_activated`, `created_by`, `created_at`, `updated_at`) VALUES
(1, 'GOLA-1234-5678-9012', 'SC-000001', 5, 0, 1, NULL, '2026-02-12 22:38:02', '2026-02-12 22:38:02'),
(2, 'GOLA-9876-5432-1098', 'SC-000002', 5, 0, 1, NULL, '2026-02-12 22:38:02', '2026-02-12 22:38:02'),
(3, 'GOLA-1111-2222-3333', 'SC-000003', 5, 5, 1, NULL, '2026-02-12 22:38:02', '2026-02-12 22:38:02'),
(4, 'GOLA-4444-5555-6666', 'SC-000004', 5, 0, 0, NULL, '2026-02-12 22:38:02', '2026-02-12 22:38:02'),
(5, 'GOLA-CBD7-F29A-A03F', 'SC-000005', 5, 0, 0, 1, '2026-02-12 23:07:46', '2026-02-12 23:07:46'),
(6, 'GOLA-52A7-8196-F24D', 'SC-000007', 5, 0, 0, 1, '2026-02-12 23:07:46', '2026-02-12 23:07:46'),
(7, 'GOLA-9992-FD8A-A1E8', 'SC-000009', 5, 0, 0, 1, '2026-02-12 23:07:47', '2026-02-12 23:07:47'),
(8, 'GOLA-FF6E-BB60-D593', 'SC-000011', 5, 0, 0, 1, '2026-02-12 23:07:47', '2026-02-12 23:07:47'),
(9, 'GOLA-9210-CE76-8CB7', 'SC-000013', 5, 0, 0, 1, '2026-02-12 23:07:47', '2026-02-12 23:07:47'),
(10, 'GOLA-DD86-2C18-2F59', 'SC-000015', 5, 0, 0, 1, '2026-02-12 23:07:47', '2026-02-12 23:07:47'),
(11, 'GOLA-1D2E-5F45-C28C', 'SC-000017', 5, 0, 0, 1, '2026-02-12 23:07:47', '2026-02-12 23:07:47'),
(12, 'GOLA-4866-22E2-F993', 'SC-000019', 5, 0, 0, 1, '2026-02-12 23:07:47', '2026-02-12 23:07:47'),
(13, 'GOLA-24A5-BA2C-A30F', 'SC-000021', 5, 2, 1, 1, '2026-02-12 23:07:47', '2026-05-27 10:02:05'),
(14, 'GOLA-664C-5152-02FA', 'SC-000023', 5, 0, 0, 1, '2026-02-12 23:07:47', '2026-02-12 23:07:47');

-- --------------------------------------------------------

--
-- Table structure for table `scratch_card_usage`
--

CREATE TABLE `scratch_card_usage` (
  `id` int(11) NOT NULL,
  `scratch_card_id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `session_id` int(11) DEFAULT NULL,
  `term_id` int(11) DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `used_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `students`
--

CREATE TABLE `students` (
  `id` int(11) NOT NULL,
  `student_id` varchar(30) NOT NULL COMMENT 'e.g. GOLA/2023/SS2/045',
  `admission_number` varchar(30) DEFAULT NULL,
  `first_name` varchar(100) NOT NULL,
  `middle_name` varchar(100) DEFAULT NULL,
  `last_name` varchar(100) NOT NULL,
  `other_name` varchar(100) DEFAULT NULL,
  `passport_photo` varchar(255) DEFAULT NULL,
  `gender` enum('Male','Female') NOT NULL,
  `date_of_birth` date DEFAULT NULL,
  `state_of_origin` varchar(100) DEFAULT NULL,
  `lga` varchar(100) DEFAULT NULL,
  `nationality` varchar(100) DEFAULT 'Nigerian',
  `religion` varchar(50) DEFAULT NULL,
  `blood_group` enum('A+','A-','B+','B-','AB+','AB-','O+','O-','') DEFAULT '',
  `genotype` enum('AA','AS','AC','SS','SC','CC','') DEFAULT '',
  `phone` varchar(20) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `admission_date` date DEFAULT NULL,
  `class_id` int(11) NOT NULL,
  `session_id` int(11) DEFAULT NULL,
  `status` enum('Active','Graduated','Withdrawn','Suspended') DEFAULT 'Active',
  `student_type` enum('Boarding','Day') DEFAULT 'Day',
  `father_name` varchar(150) DEFAULT NULL,
  `father_phone` varchar(20) DEFAULT NULL,
  `father_occupation` varchar(100) DEFAULT NULL,
  `mother_name` varchar(150) DEFAULT NULL,
  `mother_phone` varchar(20) DEFAULT NULL,
  `mother_occupation` varchar(100) DEFAULT NULL,
  `guardian_name` varchar(150) DEFAULT NULL,
  `guardian_phone` varchar(20) DEFAULT NULL,
  `guardian_relationship` varchar(50) DEFAULT NULL,
  `parent_email` varchar(100) DEFAULT NULL,
  `parent_address` text DEFAULT NULL,
  `home_address` text DEFAULT NULL,
  `city` varchar(100) DEFAULT NULL,
  `state` varchar(100) DEFAULT NULL,
  `emergency_contact_name` varchar(150) DEFAULT NULL,
  `emergency_contact_phone` varchar(20) DEFAULT NULL,
  `emergency_contact_relationship` varchar(50) DEFAULT NULL,
  `has_medical_condition` enum('Yes','No') DEFAULT 'No',
  `medical_condition_desc` text DEFAULT NULL,
  `allergies` text DEFAULT NULL,
  `physical_disability` text DEFAULT NULL,
  `doctor_name` varchar(150) DEFAULT NULL,
  `doctor_phone` varchar(20) DEFAULT NULL,
  `hospital_name` varchar(200) DEFAULT NULL,
  `special_medical_instructions` text DEFAULT NULL,
  `previous_school_name` varchar(200) DEFAULT NULL,
  `previous_school_address` text DEFAULT NULL,
  `previous_class_completed` varchar(50) DEFAULT NULL,
  `reason_for_leaving` text DEFAULT NULL,
  `transfer_cert_number` varchar(100) DEFAULT NULL,
  `previous_performance` text DEFAULT NULL,
  `date_left_previous` date DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `students`
--

INSERT INTO `students` (`id`, `student_id`, `admission_number`, `first_name`, `middle_name`, `last_name`, `other_name`, `passport_photo`, `gender`, `date_of_birth`, `state_of_origin`, `lga`, `nationality`, `religion`, `blood_group`, `genotype`, `phone`, `email`, `admission_date`, `class_id`, `session_id`, `status`, `student_type`, `father_name`, `father_phone`, `father_occupation`, `mother_name`, `mother_phone`, `mother_occupation`, `guardian_name`, `guardian_phone`, `guardian_relationship`, `parent_email`, `parent_address`, `home_address`, `city`, `state`, `emergency_contact_name`, `emergency_contact_phone`, `emergency_contact_relationship`, `has_medical_condition`, `medical_condition_desc`, `allergies`, `physical_disability`, `doctor_name`, `doctor_phone`, `hospital_name`, `special_medical_instructions`, `previous_school_name`, `previous_school_address`, `previous_class_completed`, `reason_for_leaving`, `transfer_cert_number`, `previous_performance`, `date_left_previous`, `created_at`, `updated_at`) VALUES
(1, 'GOLA/2023/SS2/045', 'GOLA/2023/SS2/045', 'Chukwuemeka', 'Daniel', 'Adebayo', '', NULL, 'Male', '2008-05-15', 'Abia', NULL, 'Nigerian', NULL, '', '', NULL, NULL, NULL, 10, 1, 'Active', 'Day', 'Mr. Daniel Adebayo', '08012345678', NULL, 'Mrs. Grace Adebayo', '08098765432', NULL, NULL, NULL, NULL, NULL, NULL, '15 Academy Drive', 'Abuja', 'FCT', NULL, NULL, NULL, 'No', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-02-12 22:38:01', '2026-02-12 22:38:01');

-- --------------------------------------------------------

--
-- Table structure for table `subjects`
--

CREATE TABLE `subjects` (
  `id` int(11) NOT NULL,
  `subject_code` varchar(10) NOT NULL,
  `subject_name` varchar(100) NOT NULL,
  `category` enum('Core','Elective','Vocational') DEFAULT 'Core',
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `subjects`
--

INSERT INTO `subjects` (`id`, `subject_code`, `subject_name`, `category`, `is_active`, `created_at`) VALUES
(1, 'ENG', 'English Language', 'Core', 1, '2026-02-12 15:36:38'),
(2, 'MTH', 'Mathematics', 'Core', 1, '2026-02-12 15:36:38'),
(3, 'PHY', 'Physics', 'Core', 1, '2026-02-12 15:36:38'),
(4, 'CHEM', 'Chemistry', 'Core', 1, '2026-02-12 15:36:38'),
(5, 'BIO', 'Biology', 'Core', 1, '2026-02-12 15:36:38'),
(6, 'AGR', 'Agricultural Science', 'Core', 1, '2026-02-12 15:36:38'),
(7, 'ECO', 'Economics', 'Core', 1, '2026-02-12 15:36:38'),
(8, 'COM', 'Commerce', 'Elective', 1, '2026-02-12 15:36:38'),
(9, 'ACC', 'Financial Accounting', 'Elective', 1, '2026-02-12 15:36:38'),
(10, 'GOV', 'Government', 'Elective', 1, '2026-02-12 15:36:38'),
(11, 'LIT', 'Literature in English', 'Elective', 1, '2026-02-12 15:36:38'),
(12, 'CRS', 'Christian Religious Studies', 'Core', 1, '2026-02-12 15:36:38'),
(13, 'GEO', 'Geography', 'Elective', 1, '2026-02-12 15:36:38'),
(14, 'CIV', 'Civic Education', 'Core', 1, '2026-02-12 15:36:38'),
(15, 'PHE', 'Physical & Health Education', 'Core', 1, '2026-02-12 15:36:38'),
(16, 'CSC', 'Computer Science', 'Core', 1, '2026-02-12 15:36:38'),
(17, 'FMA', 'Further Mathematics', 'Elective', 1, '2026-02-12 15:36:38'),
(18, 'TEC', 'Technical Drawing', 'Vocational', 1, '2026-02-12 15:36:38'),
(19, 'HAS', 'Hausa', 'Core', 1, '2026-02-12 22:57:49'),
(20, 'IGB', 'Igbo', 'Core', 1, '2026-02-12 22:57:49'),
(21, 'YOR', 'Yoruba', 'Core', 1, '2026-02-12 22:57:49'),
(22, 'FRN', 'French', 'Core', 1, '2026-02-12 22:57:49'),
(23, 'ARB', 'Arabic', 'Elective', 1, '2026-02-12 22:57:49'),
(24, 'IRS', 'Islamic Studies', 'Core', 1, '2026-02-12 22:57:49'),
(25, 'MUS', 'Music', 'Elective', 1, '2026-02-12 22:57:49'),
(26, 'VAT', 'Visual Art', 'Elective', 1, '2026-02-12 22:57:49'),
(27, 'AUM', 'Auto Mechanics', 'Vocational', 1, '2026-02-12 22:57:49'),
(28, 'BDC', 'Building Construction', 'Vocational', 1, '2026-02-12 22:57:49'),
(29, 'MTW', 'Metal Work', 'Vocational', 1, '2026-02-12 22:57:49'),
(30, 'WDW', 'Woodwork', 'Vocational', 1, '2026-02-12 22:57:49'),
(31, 'BEL', 'Basic Electricity', 'Vocational', 1, '2026-02-12 22:57:49'),
(32, 'BEE', 'Basic Electronics', 'Vocational', 1, '2026-02-12 22:57:49'),
(33, 'CLT', 'Clothing and Textiles', 'Vocational', 1, '2026-02-12 22:57:49'),
(34, 'FON', 'Food and Nutrition', 'Vocational', 1, '2026-02-12 22:57:49'),
(35, 'HMG', 'Home Management', 'Vocational', 1, '2026-02-12 22:57:49'),
(36, 'PHE2', 'Health Education', 'Vocational', 1, '2026-02-12 22:57:49'),
(37, 'BST', 'Basic Science & Technology', 'Core', 1, '2026-02-12 23:00:00'),
(38, 'NVE', 'National Values Education', 'Core', 1, '2026-02-12 23:00:00'),
(39, 'BSS', 'Business Studies', 'Core', 1, '2026-02-12 23:00:00'),
(40, 'CCA', 'Cultural & Creative Arts', 'Core', 1, '2026-02-12 23:00:00'),
(41, 'PVS', 'Pre-Vocational Studies', 'Vocational', 1, '2026-02-12 23:00:00'),
(42, 'HST', 'History', 'Core', 1, '2026-02-12 23:00:00'),
(43, 'EDO', 'Edo', 'Core', 1, '2026-02-12 23:00:00'),
(44, 'EFI', 'Efik', 'Core', 1, '2026-02-12 23:00:00'),
(45, 'IBI', 'Ibibio', 'Core', 1, '2026-02-12 23:00:00'),
(46, 'HEC', 'Home Economics', 'Vocational', 1, '2026-05-26 14:38:52'),
(47, 'BTH', 'Basic Technology', 'Core', 1, '2026-05-26 14:38:52'),
(48, 'LED', 'Leadership Development', 'Core', 1, '2026-05-26 14:38:52'),
(71, 'DD', 'sdf', 'Elective', 1, '2026-05-26 15:28:33'),
(72, 'MTN', 'language', 'Elective', 1, '2026-05-27 09:46:34');

-- --------------------------------------------------------

--
-- Table structure for table `terms`
--

CREATE TABLE `terms` (
  `id` int(11) NOT NULL,
  `term_name` enum('First Term','Second Term','Third Term') NOT NULL,
  `session_id` int(11) NOT NULL,
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `is_current` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `terms`
--

INSERT INTO `terms` (`id`, `term_name`, `session_id`, `start_date`, `end_date`, `is_current`, `created_at`) VALUES
(1, 'First Term', 1, '2024-09-01', '2024-12-15', 0, '2026-02-12 15:36:38'),
(2, 'Second Term', 1, '2025-01-06', '2025-04-15', 1, '2026-02-12 15:36:38'),
(3, 'Third Term', 1, '2025-04-28', '2025-08-15', 0, '2026-02-12 15:36:38'),
(4, 'First Term', 2, '2026-09-01', '2026-12-15', 0, '2026-05-26 15:32:38'),
(5, 'Second Term', 2, '2027-01-06', '2027-04-15', 0, '2026-05-26 15:32:38'),
(6, 'Third Term', 2, '2027-04-28', '2027-08-15', 0, '2026-05-26 15:32:38');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `academic_departments`
--
ALTER TABLE `academic_departments`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `slug` (`slug`),
  ADD KEY `idx_active` (`is_active`);

--
-- Indexes for table `academic_events`
--
ALTER TABLE `academic_events`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_date` (`event_date`),
  ADD KEY `idx_active` (`is_active`),
  ADD KEY `idx_featured` (`is_featured`);

--
-- Indexes for table `academic_sessions`
--
ALTER TABLE `academic_sessions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `session_name` (`session_name`),
  ADD KEY `idx_current` (`is_current`);

--
-- Indexes for table `activity_logs`
--
ALTER TABLE `activity_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user` (`user_id`),
  ADD KEY `idx_created` (`created_at`);

--
-- Indexes for table `admin_users`
--
ALTER TABLE `admin_users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `idx_username` (`username`),
  ADD KEY `idx_email` (`email`);

--
-- Indexes for table `classes`
--
ALTER TABLE `classes`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_class_arm` (`class_name`,`arm`);

--
-- Indexes for table `class_subjects`
--
ALTER TABLE `class_subjects`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_class_subject` (`class_id`,`subject_id`);

--
-- Indexes for table `curriculum_subjects`
--
ALTER TABLE `curriculum_subjects`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_category` (`category`),
  ADD KEY `idx_level` (`class_level`),
  ADD KEY `idx_active` (`is_active`);

--
-- Indexes for table `gallery_images`
--
ALTER TABLE `gallery_images`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_category` (`category`),
  ADD KEY `idx_active` (`is_active`),
  ADD KEY `idx_order` (`display_order`);

--
-- Indexes for table `grading_system`
--
ALTER TABLE `grading_system`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `news_articles`
--
ALTER TABLE `news_articles`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `slug` (`slug`),
  ADD KEY `idx_slug` (`slug`),
  ADD KEY `idx_category` (`category`),
  ADD KEY `idx_published` (`is_published`,`published_date`);

--
-- Indexes for table `results`
--
ALTER TABLE `results`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_result` (`student_id`,`subject_id`,`session_id`,`term_id`),
  ADD KEY `idx_student` (`student_id`),
  ADD KEY `idx_class_session_term` (`class_id`,`session_id`,`term_id`);

--
-- Indexes for table `result_summary`
--
ALTER TABLE `result_summary`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_summary` (`student_id`,`session_id`,`term_id`),
  ADD KEY `idx_published` (`published`);

--
-- Indexes for table `scratch_cards`
--
ALTER TABLE `scratch_cards`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `pin_code` (`pin_code`),
  ADD KEY `idx_activated` (`is_activated`);

--
-- Indexes for table `scratch_card_usage`
--
ALTER TABLE `scratch_card_usage`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_card` (`scratch_card_id`);

--
-- Indexes for table `students`
--
ALTER TABLE `students`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `student_id` (`student_id`),
  ADD KEY `idx_class` (`class_id`),
  ADD KEY `idx_status` (`status`);

--
-- Indexes for table `subjects`
--
ALTER TABLE `subjects`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `subject_code` (`subject_code`),
  ADD KEY `idx_code` (`subject_code`);

--
-- Indexes for table `terms`
--
ALTER TABLE `terms`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_current` (`is_current`),
  ADD KEY `idx_session` (`session_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `academic_departments`
--
ALTER TABLE `academic_departments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `academic_events`
--
ALTER TABLE `academic_events`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `academic_sessions`
--
ALTER TABLE `academic_sessions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `activity_logs`
--
ALTER TABLE `activity_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=26;

--
-- AUTO_INCREMENT for table `admin_users`
--
ALTER TABLE `admin_users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `classes`
--
ALTER TABLE `classes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT for table `class_subjects`
--
ALTER TABLE `class_subjects`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=224;

--
-- AUTO_INCREMENT for table `curriculum_subjects`
--
ALTER TABLE `curriculum_subjects`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=55;

--
-- AUTO_INCREMENT for table `gallery_images`
--
ALTER TABLE `gallery_images`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `grading_system`
--
ALTER TABLE `grading_system`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `news_articles`
--
ALTER TABLE `news_articles`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `results`
--
ALTER TABLE `results`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- AUTO_INCREMENT for table `result_summary`
--
ALTER TABLE `result_summary`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `scratch_cards`
--
ALTER TABLE `scratch_cards`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT for table `scratch_card_usage`
--
ALTER TABLE `scratch_card_usage`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `students`
--
ALTER TABLE `students`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `subjects`
--
ALTER TABLE `subjects`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=73;

--
-- AUTO_INCREMENT for table `terms`
--
ALTER TABLE `terms`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `activity_logs`
--
ALTER TABLE `activity_logs`
  ADD CONSTRAINT `activity_logs_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `admin_users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `terms`
--
ALTER TABLE `terms`
  ADD CONSTRAINT `terms_ibfk_1` FOREIGN KEY (`session_id`) REFERENCES `academic_sessions` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
