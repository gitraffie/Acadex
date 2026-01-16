-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Dec 17, 2025 at 04:12 AM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.0.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `acadex_db`
--

-- --------------------------------------------------------

--
-- Table structure for table `assessment_items`
--

CREATE TABLE `assessment_items` (
  `id` int(11) NOT NULL,
  `class_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `max_score` decimal(5,2) NOT NULL,
  `component` enum('class_standing','exam','project') NOT NULL,
  `term` enum('prelim','midterm','finals') NOT NULL,
  `teacher_email` varchar(255) NOT NULL,
  `date_created` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `assessment_items`
--

INSERT INTO `assessment_items` (`id`, `class_id`, `title`, `max_score`, `component`, `term`, `teacher_email`, `date_created`) VALUES
(1, 7, 'Sample Assessment', 10.00, 'class_standing', 'prelim', 'dumaraograffie@sac.edu.ph', '2025-11-09 15:16:22'),
(2, 7, 'Quiz Assessment', 20.00, 'class_standing', 'midterm', 'dumaraograffie@sac.edu.ph', '2025-11-10 14:22:38'),
(3, 7, 'Major Exam', 60.00, 'exam', 'midterm', 'dumaraograffie@sac.edu.ph', '2025-11-10 14:39:49'),
(6, 7, 'Quiz', 30.00, 'class_standing', 'midterm', 'dumaraograffie@sac.edu.ph', '2025-11-10 14:53:16'),
(7, 7, 'Midterm Exam', 70.00, 'exam', 'midterm', 'dumaraograffie@sac.edu.ph', '2025-11-10 14:53:51'),
(8, 7, 'Midterm Project', 60.00, 'class_standing', 'midterm', 'dumaraograffie@sac.edu.ph', '2025-11-10 15:29:15'),
(9, 7, 'Another Exam', 40.00, 'exam', 'midterm', 'dumaraograffie@sac.edu.ph', '2025-11-10 15:29:38'),
(10, 6, 'Quiz 1', 20.00, 'class_standing', 'prelim', 'dumaraograffie@sac.edu.ph', '2025-11-10 16:53:16'),
(11, 6, 'Quiz 2', 20.00, 'class_standing', 'midterm', 'dumaraograffie@sac.edu.ph', '2025-11-10 16:53:36'),
(12, 6, 'Quiz 3', 20.00, 'class_standing', 'finals', 'dumaraograffie@sac.edu.ph', '2025-11-10 16:53:49'),
(13, 6, 'Exam 1', 60.00, 'exam', 'prelim', 'dumaraograffie@sac.edu.ph', '2025-11-10 16:56:08'),
(14, 6, 'Exam 2', 60.00, 'exam', 'midterm', 'dumaraograffie@sac.edu.ph', '2025-11-10 16:56:17'),
(15, 6, 'Exam 3', 60.00, 'exam', 'finals', 'dumaraograffie@sac.edu.ph', '2025-11-10 16:56:26'),
(16, 7, 'Quiz Prelim', 100.00, 'class_standing', 'prelim', 'dumaraograffie@sac.edu.ph', '2025-12-05 09:11:04');

-- --------------------------------------------------------

--
-- Table structure for table `assessment_scores`
--

CREATE TABLE `assessment_scores` (
  `id` int(11) NOT NULL,
  `assessment_item_id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `score` decimal(5,2) NOT NULL,
  `date_modified` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `assessment_scores`
--

INSERT INTO `assessment_scores` (`id`, `assessment_item_id`, `student_id`, `score`, `date_modified`) VALUES
(1, 1, 243, 7.00, '2025-11-10 14:39:18'),
(2, 1, 220, 9.00, '2025-11-09 17:37:22'),
(3, 2, 255, 15.00, '2025-11-10 14:23:07'),
(4, 3, 243, 56.00, '2025-11-10 14:39:55'),
(7, 2, 243, 10.00, '2025-11-10 15:47:32'),
(8, 6, 243, 26.00, '2025-11-10 15:47:27'),
(9, 7, 243, 20.00, '2025-11-10 16:12:27'),
(10, 8, 243, 55.00, '2025-11-10 16:12:18'),
(11, 9, 243, 40.00, '2025-12-02 14:26:47'),
(18, 9, 220, 38.00, '2025-11-15 08:55:00'),
(19, 15, 322, 60.00, '2025-12-05 07:09:29'),
(20, 16, 243, 75.00, '2025-12-05 09:11:29');

-- --------------------------------------------------------

--
-- Table structure for table `attendance`
--

CREATE TABLE `attendance` (
  `id` int(11) NOT NULL,
  `class_id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `student_number` varchar(50) NOT NULL,
  `attendance_date` date NOT NULL,
  `session` varchar(255) NOT NULL,
  `status` enum('present','absent','late','excused','unexcused') NOT NULL DEFAULT 'present',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `attendance`
--

INSERT INTO `attendance` (`id`, `class_id`, `student_id`, `student_number`, `attendance_date`, `session`, `status`, `created_at`, `updated_at`) VALUES
(1498, 6, 324, '2147483647', '2025-12-15', 'morning', 'absent', '2025-12-15 16:01:14', '2025-12-15 16:01:14'),
(1499, 6, 322, '1234509', '2025-12-15', 'morning', 'present', '2025-12-15 16:01:14', '2025-12-15 16:01:14'),
(1500, 6, 323, '1122097', '2025-12-15', 'morning', 'present', '2025-12-15 16:01:14', '2025-12-15 16:01:14'),
(1501, 6, 265, '2021050', '2025-12-15', 'morning', 'present', '2025-12-15 16:01:14', '2025-12-15 16:01:14'),
(1502, 6, 324, '2147483647', '2025-12-16', 'morning', 'present', '2025-12-16 01:41:27', '2025-12-16 01:41:27'),
(1503, 6, 322, '1234509', '2025-12-16', 'morning', 'present', '2025-12-16 01:41:27', '2025-12-16 01:41:27'),
(1504, 6, 323, '1122097', '2025-12-16', 'morning', 'present', '2025-12-16 01:41:27', '2025-12-16 01:41:27'),
(1505, 6, 265, '2021050', '2025-12-16', 'morning', 'present', '2025-12-16 01:41:27', '2025-12-16 01:41:27');

-- --------------------------------------------------------

--
-- Table structure for table `calculated_grades`
--

CREATE TABLE `calculated_grades` (
  `id` int(11) NOT NULL,
  `class_id` int(11) NOT NULL,
  `teacher_email` varchar(255) NOT NULL,
  `student_number` int(11) NOT NULL,
  `prelim` float DEFAULT NULL,
  `midterm` float DEFAULT NULL,
  `finals` float DEFAULT NULL,
  `final_grade` float DEFAULT NULL,
  `created_at` datetime NOT NULL,
  `updated_at` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `calculated_grades`
--

INSERT INTO `calculated_grades` (`id`, `class_id`, `teacher_email`, `student_number`, `prelim`, `midterm`, `finals`, `final_grade`, `created_at`, `updated_at`) VALUES
(587, 6, 'dumaraograffie@sac.edu.ph', 2147483647, 90.5, 86.4, 89.3, 88.73, '2025-12-15 23:42:29', '2025-12-15 23:50:43');

-- --------------------------------------------------------

--
-- Table structure for table `classes`
--

CREATE TABLE `classes` (
  `id` int(11) NOT NULL,
  `class_name` varchar(255) NOT NULL,
  `section` varchar(255) NOT NULL,
  `term` varchar(255) NOT NULL,
  `user_email` varchar(255) NOT NULL,
  `archived` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `classes`
--

INSERT INTO `classes` (`id`, `class_name`, `section`, `term`, `user_email`, `archived`, `created_at`) VALUES
(5, 'Class 1', 'Section A', '1st Semester', 'dumaraograffie@sac.edu.ph', 1, '2025-11-03 17:40:46'),
(6, 'Class 2', 'Section B', '2nd Semester', 'dumaraograffie@sac.edu.ph', 0, '2025-11-03 17:47:16'),
(7, 'Class 3', 'Section 2', '1st Semester', 'dumaraograffie@sac.edu.ph', 0, '2025-11-06 08:05:30');

-- --------------------------------------------------------

--
-- Table structure for table `grades`
--

CREATE TABLE `grades` (
  `id` int(11) NOT NULL,
  `class_id` varchar(255) NOT NULL,
  `teacher_email` varchar(255) NOT NULL,
  `student_number` int(11) NOT NULL,
  `class_standing` float NOT NULL,
  `exam` float NOT NULL,
  `term` varchar(255) NOT NULL,
  `created_at` datetime NOT NULL,
  `updated_at` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `grades`
--

INSERT INTO `grades` (`id`, `class_id`, `teacher_email`, `student_number`, `class_standing`, `exam`, `term`, `created_at`, `updated_at`) VALUES
(1729, '6', 'dumaraograffie@sac.edu.ph', 2147483647, 89, 94, 'Prelim', '2025-12-15 23:42:29', '2025-12-15 23:42:29'),
(1730, '6', 'dumaraograffie@sac.edu.ph', 2147483647, 87, 85, 'Midterm', '2025-12-15 23:50:35', '2025-12-15 23:50:35'),
(1731, '6', 'dumaraograffie@sac.edu.ph', 2147483647, 89, 90, 'Finals', '2025-12-15 23:50:42', '2025-12-15 23:50:42');

-- --------------------------------------------------------

--
-- Table structure for table `password_reset_requests`
--

CREATE TABLE `password_reset_requests` (
  `id` int(11) NOT NULL,
  `email` varchar(255) NOT NULL,
  `verification_code` varchar(20) NOT NULL,
  `status` varchar(20) NOT NULL DEFAULT 'Pending',
  `expires_at` datetime NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `verified_at` datetime DEFAULT NULL,
  `completed_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `password_reset_requests`
--

INSERT INTO `password_reset_requests` (`id`, `email`, `verification_code`, `status`, `expires_at`, `created_at`, `verified_at`, `completed_at`) VALUES
(3, 'dumaraograffie@sac.edu.ph', 'ZE743F', 'Completed', '2025-12-15 21:53:19', '2025-12-15 13:38:19', '2025-12-15 21:38:51', '2025-12-15 21:39:03'),
(5, 'dumaraograffie@sac.edu.ph', 'EC73W6', 'Verified', '2025-12-15 22:41:18', '2025-12-15 14:26:18', '2025-12-15 22:26:59', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `students`
--

CREATE TABLE `students` (
  `id` int(11) NOT NULL,
  `class_id` int(11) NOT NULL,
  `student_number` int(11) NOT NULL,
  `student_email` varchar(255) NOT NULL,
  `password` varchar(255) DEFAULT NULL,
  `must_change_password` tinyint(1) NOT NULL DEFAULT 1,
  `first_name` varchar(255) NOT NULL,
  `last_name` varchar(255) NOT NULL,
  `middle_initial` varchar(255) DEFAULT NULL,
  `suffix` varchar(255) DEFAULT NULL,
  `program` varchar(255) NOT NULL,
  `created_at` date NOT NULL,
  `teacher_email` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `students`
--

INSERT INTO `students` (`id`, `class_id`, `student_number`, `student_email`, `password`, `must_change_password`, `first_name`, `last_name`, `middle_initial`, `suffix`, `program`, `created_at`, `teacher_email`) VALUES
(212, 7, 2021001, 'john.doe@student.edu', '$2y$10$ztWl8.EjNOj9a.3KxNkhRe1dJfT9t.1c0pZv7aLxXrv9nszHyZmFe', 1, 'John', 'Doe', 'A', '', 'BS Computer Science', '2025-11-06', 'dumaraograffie@sac.edu.ph'),
(213, 7, 2021002, 'jane.smithjr@student.edu', '$2y$10$90O0Cz2t6XJHhB8tzOniz.OrBsf6nu53fFKtMywuXioeNQmI4pAiG', 1, 'Jane', 'Smith', 'B', 'Jr.', 'BS Information Technology', '2025-11-06', 'dumaraograffie@sac.edu.ph'),
(214, 7, 2021003, 'mike.johnsoniii@student.edu', '$2y$10$9wXvxww6fIwBF0oEQtUaI.IeeqLWYlCl31SlUrXsiTPE.eUftyoTi', 1, 'Mike', 'Johnson', '', 'III', 'BS Computer Engineering', '2025-11-06', 'dumaraograffie@sac.edu.ph'),
(215, 7, 2021004, 'anna.williams@student.edu', '$2y$10$6YiYtkZQqM7wkJgz/G5rMepqutduyqEjA7IzGbimU8y1ND4oqVy7K', 1, 'Anna', 'Williams', 'C', '', 'BA Psychology', '2025-11-06', 'dumaraograffie@sac.edu.ph'),
(216, 7, 2021006, 'emily.davis@student.edu', '$2y$10$0PF5WreLpjHOX42/LbL9Q.tqFa9DfS6vLB6bqegkqXPD2bEIL0j0S', 1, 'Emily', 'Davis', 'D', '', 'BS Business Administration', '2025-11-06', 'dumaraograffie@sac.edu.ph'),
(217, 7, 2021007, 'robert.wilsonjr@student.edu', '$2y$10$htQGaOcnM0E04N9qnC4xiuthHDrabU63TZWb8Jt1Vx8VrrExgoqRu', 1, 'Robert', 'Wilson', '', 'Jr.', 'BS Nursing', '2025-11-06', 'dumaraograffie@sac.edu.ph'),
(218, 7, 2021008, 'lisa.garcia@student.edu', '$2y$10$oaKeepExI2sSE6nPIaq6/uFxDrHEFzpsIkWskA7hdxsXA.ZdCuSN.', 1, 'Lisa', 'Garcia', 'E', '', 'BA Mathematics', '2025-11-06', 'dumaraograffie@sac.edu.ph'),
(219, 7, 2021009, 'kevin.martineziii@student.edu', '$2y$10$IBJ2MV4DlfaYNFxLMalwZ.4KURW2S7Sz/.udr0Vp2FEIIOQ4RGpsq', 1, 'Kevin', 'Martinez', '', 'III', 'BS Engineering', '2025-11-06', 'dumaraograffie@sac.edu.ph'),
(220, 7, 2021010, 'sarah.anderson@student.edu', '$2y$10$wlNgkGWaZq3cE3589cKBkeKEjGl07MAU5y35G2rDTlqVu4kcwXbUy', 1, 'Sarah', 'Anderson', 'F', '', 'BS Computer Science', '2025-11-06', 'dumaraograffie@sac.edu.ph'),
(221, 7, 2021011, 'christopher.lee@student.edu', '$2y$10$PcMrE6drbj0cxhrGBVc.QusaoKXcA6iFTj1wXrARSMBwh88SMAi/y', 1, 'Christopher', 'Lee', 'G', '', 'BS Information Technology', '2025-11-06', 'dumaraograffie@sac.edu.ph'),
(222, 7, 2021012, 'olivia.taylor@student.edu', '$2y$10$2V63oJofrgqboziQ7SABquMnzw.dhkzHWtOFMxtKzdLQtY.6onKD2', 1, 'Olivia', 'Taylor', 'H', '', 'BS Computer Engineering', '2025-11-06', 'dumaraograffie@sac.edu.ph'),
(223, 7, 2021013, 'james.moore@student.edu', '$2y$10$AghIRZX4WsA0exnAL3x96ekcdIaPKvTtKpmHfZ3R4ymOHp32otcsu', 1, 'James', 'Moore', '', 'Jr.', 'BA Psychology', '2025-11-06', 'dumaraograffie@sac.edu.ph'),
(224, 7, 2021014, 'sophia.jackson@student.edu', '$2y$10$k.w1joRnxPvecnSiKkuYse5L9cqNQLL7siO0cXkEEY5AKKLTZdNjm', 1, 'Sophia', 'Jackson', 'I', '', 'BA English', '2025-11-06', 'dumaraograffie@sac.edu.ph'),
(225, 7, 2021015, 'benjamin.white@student.edu', '$2y$10$WrNm1EER8klWGW66/VAyFuMUEUhz7WczoS81UOSxvpf5Mdxy7G13W', 1, 'Benjamin', 'White', '', 'III', 'BS Business Administration', '2025-11-06', 'dumaraograffie@sac.edu.ph'),
(226, 7, 2021016, 'ava.harris@student.edu', '$2y$10$3ZFqgnuQgQ4TKeYP1DWpiOM16YRy32tDC6s0Y8sJvOjBUGbNSAhHG', 1, 'Ava', 'Harris', 'J', '', 'BS Nursing', '2025-11-06', 'dumaraograffie@sac.edu.ph'),
(227, 7, 2021017, 'william.martin@student.edu', '$2y$10$6uNdju/FOH1aclOeEUsSaeuut3q7N3Os3OBLvCd1UCMNakA7A4aG2', 1, 'William', 'Martin', 'K', '', 'BA Mathematics', '2025-11-06', 'dumaraograffie@sac.edu.ph'),
(228, 7, 2021018, 'isabella.thompson@student.edu', '$2y$10$0wJkIEWimURAYEmmsMWdg.tHxUrqn/wBK46UhQNwx7/fqQJbvhSwO', 1, 'Isabella', 'Thompson', 'L', '', 'BS Engineering', '2025-11-06', 'dumaraograffie@sac.edu.ph'),
(229, 7, 2021019, 'alexander.garcia@student.edu', '$2y$10$Jr8fSxNlh4Ax7wDQ0FZfOOrZ8RYfN.Jbes29XAzq5smBaXIecFwj6', 1, 'Alexander', 'Garcia', '', 'Sr.', 'BS Computer Science', '2025-11-06', 'dumaraograffie@sac.edu.ph'),
(230, 7, 2021020, 'charlotte.martinez@student.edu', '$2y$10$MF0nVXsmZRXSLJOZQiCCQ.lVu3XkNTuPVxdFl93LQCc7bxTADkapy', 1, 'Charlotte', 'Martinez', 'M', '', 'BS Information Technology', '2025-11-06', 'dumaraograffie@sac.edu.ph'),
(231, 7, 2021021, 'michael.rodriguez@student.edu', '$2y$10$xh16YsTkR2rj6p4cJT8/QuHYd.fApY/NUppZv5eFMAiaWscT6XkBO', 1, 'Michael', 'Rodriguez', 'N', '', 'BS Computer Engineering', '2025-11-06', 'dumaraograffie@sac.edu.ph'),
(232, 7, 2021022, 'amelia.lopez@student.edu', '$2y$10$h7qPRNvVnRrDdh2yyIqVIOeBMgvB7p7DTMDk.5dvpDO5on3S7yGyW', 1, 'Amelia', 'Lopez', 'O', '', 'BA Psychology', '2025-11-06', 'dumaraograffie@sac.edu.ph'),
(233, 7, 2021023, 'ethan.gonzalez@student.edu', '$2y$10$ZDuQsqFqi3kxkDCXcIkzS.eRQ5PQdW9xDlR2h217CXSTNLQPvAbVC', 1, 'Ethan', 'Gonzalez', 'P', '', 'BA English', '2025-11-06', 'dumaraograffie@sac.edu.ph'),
(234, 7, 2021024, 'harper.perez@student.edu', '$2y$10$84Kl.m4BzLf6fB/diR.hQ.OrbY.CoUdNwMyMVvpuVxXu5Z6hxwvtC', 1, 'Harper', 'Perez', 'Q', '', 'BS Business Administration', '2025-11-06', 'dumaraograffie@sac.edu.ph'),
(235, 7, 2021025, 'logan.williams@student.edu', '$2y$10$AKUObZyyQwudc8N92tuytuiszDaDpgOaLwST5AgkBQxdAHaGsT0n2', 1, 'Logan', 'Williams', '', 'Jr.', 'BS Nursing', '2025-11-06', 'dumaraograffie@sac.edu.ph'),
(236, 7, 2021026, 'abigail.lewis@student.edu', '$2y$10$uU3Becb.jDi7TEqnwYBet.2dQMG/imSEvaOKc78UkxQs5Tg85UPtu', 1, 'Abigail', 'Lewis', 'R', '', 'BA Mathematics', '2025-11-06', 'dumaraograffie@sac.edu.ph'),
(237, 7, 2021027, 'jacob.walker@student.edu', '$2y$10$aZvfasaPBzVbuOd.Pyw0/.oG9hkIE0B7qvVcsx3iy4/WNJgRZMAxG', 1, 'Jacob', 'Walker', 'S', '', 'BS Engineering', '2025-11-06', 'dumaraograffie@sac.edu.ph'),
(238, 7, 2021028, 'elizabeth.hall@student.edu', '$2y$10$/0EDDQDisF6ErXAyOkPoNetiSw3ok8PevYTIInJzPCS4mCX9P2yte', 1, 'Elizabeth', 'Hall', 'T', '', 'BS Computer Science', '2025-11-06', 'dumaraograffie@sac.edu.ph'),
(239, 7, 2021029, 'daniel.young@student.edu', '$2y$10$vX2PYxn39V2RYj.sNkq8zuf3HkWpkD7nSHlylbmpo12J.Gy5IWOwS', 1, 'Daniel', 'Young', '', 'III', 'BS Information Technology', '2025-11-06', 'dumaraograffie@sac.edu.ph'),
(240, 7, 2021030, 'grace.king@student.edu', '$2y$10$zE7ywZc4iywE/0c.f0zc5upLBXLhCkrz47ooUWOxkmufur/aeQzu.', 1, 'Grace', 'King', 'U', '', 'BS Computer Engineering', '2025-11-06', 'dumaraograffie@sac.edu.ph'),
(241, 7, 2021031, 'henry.scott@student.edu', '$2y$10$r2.wY1TS2RZZcr5JnOUYnOooWBVH.iFTzyJHJ323UWi6Pz.Bgg9Aa', 1, 'Henry', 'Scott', 'V', '', 'BA Psychology', '2025-11-06', 'dumaraograffie@sac.edu.ph'),
(242, 7, 2021032, 'ella.green@student.edu', '$2y$10$Eq1oAGXUmCpGiyGPBlT.8enEnt1eUEhe6PInu7ya3kCynmIJovxbe', 1, 'Ella', 'Green', 'W', '', 'BA English', '2025-11-06', 'dumaraograffie@sac.edu.ph'),
(243, 7, 2021033, 'sebastian.adams@student.edu', '$2y$10$G/ndebDm6vVKOOPJbDXvNuh0KxXggcMuThnIW7pTS5XAJiXd2Cm9y', 1, 'Sebastian', 'Adams', '', 'Jr.', 'BS Information Technology', '2025-11-06', 'dumaraograffie@sac.edu.ph'),
(244, 7, 2021034, 'scarlett.baker@student.edu', '$2y$10$GJgOblI/RjtNVY6NO6pv1eF11ChEgctKeD/JMa1bi1dIxQrVHH.Pe', 1, 'Scarlett', 'Baker', 'X', '', 'BS Nursing', '2025-11-06', 'dumaraograffie@sac.edu.ph'),
(245, 7, 2021035, 'jack.nelson@student.edu', '$2y$10$QUD5aQupcNz51/pY9GRnCOSKWwxkDDlS1Bx.Ow5j1CZbrss9Lo9u2', 1, 'Jack', 'Nelson', 'Y', '', 'BA Mathematics', '2025-11-06', 'dumaraograffie@sac.edu.ph'),
(246, 7, 2021036, 'zoey.carter@student.edu', '$2y$10$jafWUNgEVUVxO7JIKI2dQOZoDQ5xXXnPmrTwzJCnV6AeEvBLst0py', 1, 'Zoey', 'Carter', 'Z', '', 'BS Engineering', '2025-11-06', 'dumaraograffie@sac.edu.ph'),
(247, 7, 2021037, 'levi.mitchell@student.edu', '$2y$10$bSN7r0Datjru7qqXeU.y0eB18qX24KjIhoGTXzsyEWXTYRLV5RRsm', 1, 'Levi', 'Mitchell', 'A', '', 'BS Computer Science', '2025-11-06', 'dumaraograffie@sac.edu.ph'),
(248, 7, 2021038, 'layla.perez@student.edu', '$2y$10$1M641N0Lz9PSswymBc5ZteNp3YrGtAw9H20s9.rlno3wu6b15VJtS', 1, 'Layla', 'Perez', 'B', '', 'BS Information Technology', '2025-11-06', 'dumaraograffie@sac.edu.ph'),
(249, 7, 2021039, 'owen.roberts@student.edu', '$2y$10$fPwlTaawKTu9HHFsZ.iVe.UB3fL2D/lIz6iRys/ZRr.8TcpBb50cy', 1, 'Owen', 'Roberts', '', 'Sr.', 'BS Computer Engineering', '2025-11-06', 'dumaraograffie@sac.edu.ph'),
(250, 7, 2021040, 'madison.turner@student.edu', '$2y$10$Dj/dFHZabLrtBfFLnma.eOMOJ7YP.aTx9jTwps6fstV9I9p/T4DL.', 1, 'Madison', 'Turner', 'C', '', 'BA Psychology', '2025-11-06', 'dumaraograffie@sac.edu.ph'),
(251, 7, 2021041, 'ryan.phillips@student.edu', '$2y$10$n2/GqOFMdLVXfgEbIu8mzOgeUwOVHDEws7Yc1LOczMjBs43Lw7X6K', 1, 'Ryan', 'Phillips', 'D', '', 'BA English', '2025-11-06', 'dumaraograffie@sac.edu.ph'),
(252, 7, 2021042, 'penelope.campbell@student.edu', '$2y$10$XF2y.G0PaTfdDr/A07K7se9CqQ6sIZ3QVKtGsvC/0VA6bUWxOnwLW', 1, 'Penelope', 'Campbell', 'E', '', 'BS Business Administration', '2025-11-06', 'dumaraograffie@sac.edu.ph'),
(253, 7, 2021043, 'nolan.parker@student.edu', '$2y$10$wRIEKTyxfgl.w3UuiSNsrOz2feOx7v67b2.a2Sh5I6qCOW/2xWog6', 1, 'Nolan', 'Parker', 'F', '', 'BS Nursing', '2025-11-06', 'dumaraograffie@sac.edu.ph'),
(254, 7, 2021044, 'riley.evans@student.edu', '$2y$10$0VhLOaSQay3JXwb5qBjMyelFfYHX8.PsyR19VHdJOSelV/dKNaxlK', 1, 'Riley', 'Evans', 'G', '', 'BA Mathematics', '2025-11-06', 'dumaraograffie@sac.edu.ph'),
(255, 7, 2021045, 'caleb.edwards@student.edu', '$2y$10$iC4id2wChZHRWTMljJbA8egoRbBrrhiwjW9GOmxhFX24rkGFAzA0S', 1, 'Caleb', 'Edwards', '', 'III', 'BS Engineering', '2025-11-06', 'dumaraograffie@sac.edu.ph'),
(256, 7, 2021046, 'madelyn.collins@student.edu', '$2y$10$xpXSjuBxtJFRlUO9SPJd6ONz8BNSmtCkFfOf1nNlKjHAAGAvS0Pqu', 1, 'Madelyn', 'Collins', 'H', '', 'BS Computer Science', '2025-11-06', 'dumaraograffie@sac.edu.ph'),
(257, 7, 2021047, 'brayden.stewart@student.edu', '$2y$10$6B9j4QZaRl1Z1a95DCXnA.wazU/i1HxTI5TKvHsb4kUiYjckvba.O', 1, 'Brayden', 'Stewart', 'I', '', 'BS Information Technology', '2025-11-06', 'dumaraograffie@sac.edu.ph'),
(258, 7, 2021048, 'aria.sanchez@student.edu', '$2y$10$SQAB3OpyOlUE9Afyl0fmWOT9YuMQ1eCQo1GZ9wQB7CVl4IOYw.LqG', 1, 'Aria', 'Sanchez', 'J', '', 'BS Computer Engineering', '2025-11-06', 'dumaraograffie@sac.edu.ph'),
(259, 7, 2021049, 'lincoln.morris@student.edu', '$2y$10$BDFVKePEZ5iH3ug/d2QzSu4dLxKcQxD3xFt92zXMfsDAa8ItWvS/a', 1, 'Lincoln', 'Morris', 'K', '', 'BA Psychology', '2025-11-06', 'dumaraograffie@sac.edu.ph'),
(260, 7, 2021050, 'brooklyn.rivera@student.edu', '$2y$10$IDIpRVXOsobHRHwXpU7T2eUmtLwNTeVoDRIXWg5AEu1RoyzUiC62W', 1, 'Brooklyn', 'Rivera', 'L', '', 'BA English', '2025-11-06', 'dumaraograffie@sac.edu.ph'),
(261, 7, 100000001, 'barcelonakristel@sac.edu.ph', '$2y$10$gCQCe9Ym6LCumpYyiPm33eWCFFkqH6ZS/gGdqaEVbp5HODK4msG06', 1, 'Kristel', 'Barcelona', 'D', '', 'BS Nursing', '2025-11-10', 'dumaraograffie@sac.edu.ph'),
(264, 7, 2021005, 'david.brownsr@student.edu', '$2y$10$3QHtYjWry33.y/Nl8CyiteaOO2W.lgQpVDg5zu2.UtoAghCwdnFoG', 1, 'David', 'Brown', '', 'Sr.', 'BA English', '2025-12-01', 'dumaraograffie@sac.edu.ph'),
(265, 6, 2021050, 'rivera.brooklyn@email.com', '$2y$10$X1gfQk2IKPYotNK2oJU7Ce9ukmwgWS2PDt/TvqIXb4QM5lx2H5TXC', 1, 'Brooklyn', 'Rivera', '', '', 'BS Information Technology', '2025-12-02', 'dumaraograffie@sac.edu.ph'),
(320, 0, 112233, 'lyle.joshua@email.com', '$2y$10$jw/UZ6Du14PAvDa0nJQdle8ns7V944/GUg22sgDX9K07uEO3gJ/FS', 1, 'Joshua', 'Lyle', '', '', 'BS in Information Technology', '2025-12-04', 'dumaraograffie@sac.edu.ph'),
(322, 6, 1234509, 'dungganonjaster@sac.edu.ph', '$2y$10$OqC5EKrRG9OiP97zHvFW5OIfS/wSpFYJics8yWRy4Q8fxDtcBfwJG', 1, 'Jaster', 'Dungganon', 'M', '', 'BS Information Technology', '2025-12-05', 'dumaraograffie@sac.edu.ph'),
(323, 6, 1122097, 'molouajane@sac.edu.ph', '$2y$10$gf.bsn22/LWAbJBVc2nrGed7kDdj1izT4B2hTa68Nc4PS6QRwbAi6', 1, 'Jane', 'Moloua', 'S', '', 'BS Information Technology', '2025-12-05', 'dumaraograffie@sac.edu.ph'),
(324, 6, 2147483647, 'raptae8888@gmail.com', '$2y$10$i/6jFIOmx1b2D.XEk2XnAO87v9AlQOL0HfONP6z4T5vBOPsTAiXBK', 0, 'Raffie', 'Dumaraog', 'E', '', 'BS Information Technology', '2025-12-15', 'dumaraograffie@sac.edu.ph');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `first_name` varchar(255) NOT NULL DEFAULT '',
  `last_name` varchar(255) NOT NULL DEFAULT '',
  `full_name` varchar(255) NOT NULL,
  `department` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `user_type` varchar(50) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `first_name`, `last_name`, `full_name`, `department`, `email`, `password`, `user_type`, `created_at`) VALUES
(9, 'Teacher', 'Raffie', 'Teacher Raffie', '', 'dumaraograffie@sac.edu.ph', '$2y$10$DaLvI3qaKugDn6/bT2F/ze3OcaJzNp/SoMMm5KYIp4Z1w9ZxQKE.i', 'teacher', '2025-11-03 15:09:07'),
(11, 'Site', 'Admin', 'Site Admin', 'Administration', 'raffiedumaraog@gmail.com', '$2y$10$6NV1kIkKCSE/5UkCrBI/bus.QDxL3.vJu/zgd5N1XPBzdl/CPRhlW', 'admin', '2025-12-15 12:14:29'),
(12, 'Sample', 'Added Teacher', 'Sample Added Teacher', 'General', 'sample.teacher@example.com', '$2y$10$zyIvZAzVXyChF50A0sWtZ.LJPEUZzrRR1ilzM07d4IaOZEuyx0D1W', 'teacher', '2025-12-15 12:23:09');

-- --------------------------------------------------------

--
-- Table structure for table `weights`
--

CREATE TABLE `weights` (
  `id` int(11) NOT NULL,
  `class_id` int(11) NOT NULL,
  `teacher_email` varchar(255) NOT NULL,
  `class_standing` decimal(3,2) NOT NULL DEFAULT 0.70,
  `exam` decimal(3,2) NOT NULL DEFAULT 0.30,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `weights`
--

INSERT INTO `weights` (`id`, `class_id`, `teacher_email`, `class_standing`, `exam`, `created_at`, `updated_at`) VALUES
(4, 7, 'dumaraograffie@sac.edu.ph', 0.70, 0.30, '2025-12-02 08:34:53', '2025-12-05 09:12:15');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `assessment_items`
--
ALTER TABLE `assessment_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `class_id` (`class_id`);

--
-- Indexes for table `assessment_scores`
--
ALTER TABLE `assessment_scores`
  ADD PRIMARY KEY (`id`),
  ADD KEY `assessment_item_id` (`assessment_item_id`),
  ADD KEY `student_id` (`student_id`);

--
-- Indexes for table `attendance`
--
ALTER TABLE `attendance`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_student_date_session` (`student_id`,`attendance_date`,`class_id`,`session`),
  ADD KEY `class_id` (`class_id`);

--
-- Indexes for table `calculated_grades`
--
ALTER TABLE `calculated_grades`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `classes`
--
ALTER TABLE `classes`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `grades`
--
ALTER TABLE `grades`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_grade` (`class_id`,`student_number`,`term`);

--
-- Indexes for table `password_reset_requests`
--
ALTER TABLE `password_reset_requests`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `students`
--
ALTER TABLE `students`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `weights`
--
ALTER TABLE `weights`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_class_teacher` (`class_id`,`teacher_email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `assessment_items`
--
ALTER TABLE `assessment_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT for table `assessment_scores`
--
ALTER TABLE `assessment_scores`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- AUTO_INCREMENT for table `attendance`
--
ALTER TABLE `attendance`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1506;

--
-- AUTO_INCREMENT for table `calculated_grades`
--
ALTER TABLE `calculated_grades`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=588;

--
-- AUTO_INCREMENT for table `classes`
--
ALTER TABLE `classes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `grades`
--
ALTER TABLE `grades`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1732;

--
-- AUTO_INCREMENT for table `password_reset_requests`
--
ALTER TABLE `password_reset_requests`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `students`
--
ALTER TABLE `students`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=325;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `weights`
--
ALTER TABLE `weights`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `assessment_items`
--
ALTER TABLE `assessment_items`
  ADD CONSTRAINT `assessment_items_ibfk_1` FOREIGN KEY (`class_id`) REFERENCES `classes` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `assessment_scores`
--
ALTER TABLE `assessment_scores`
  ADD CONSTRAINT `assessment_scores_ibfk_1` FOREIGN KEY (`assessment_item_id`) REFERENCES `assessment_items` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `assessment_scores_ibfk_2` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `attendance`
--
ALTER TABLE `attendance`
  ADD CONSTRAINT `attendance_ibfk_1` FOREIGN KEY (`class_id`) REFERENCES `classes` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
