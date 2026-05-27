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
