-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jan 17, 2026 at 11:02 AM
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
(1518, 8, 581, '2021006', '2026-01-17', 'morning', 'absent', '2026-01-17 09:50:16', '2026-01-17 09:50:16'),
(1519, 8, 576, '2021001', '2026-01-17', 'morning', 'late', '2026-01-17 09:50:16', '2026-01-17 09:50:16'),
(1520, 8, 627, '2147483647', '2026-01-17', 'morning', 'present', '2026-01-17 09:50:16', '2026-01-17 09:50:16'),
(1521, 8, 620, '2021045', '2026-01-17', 'morning', 'late', '2026-01-17 09:50:16', '2026-01-17 09:50:16'),
(1522, 8, 619, '2021044', '2026-01-17', 'morning', 'late', '2026-01-17 09:50:16', '2026-01-17 09:50:16'),
(1523, 8, 594, '2021019', '2026-01-17', 'morning', 'present', '2026-01-17 09:50:16', '2026-01-17 09:50:16'),
(1524, 8, 583, '2021008', '2026-01-17', 'morning', 'present', '2026-01-17 09:50:16', '2026-01-17 09:50:16'),
(1525, 8, 598, '2021023', '2026-01-17', 'morning', 'late', '2026-01-17 09:50:16', '2026-01-17 09:50:16'),
(1526, 8, 607, '2021032', '2026-01-17', 'morning', 'late', '2026-01-17 09:50:16', '2026-01-17 09:50:16'),
(1527, 8, 603, '2021028', '2026-01-17', 'morning', 'present', '2026-01-17 09:50:16', '2026-01-17 09:50:16'),
(1528, 8, 591, '2021016', '2026-01-17', 'morning', 'present', '2026-01-17 09:50:16', '2026-01-17 09:50:16'),
(1529, 8, 589, '2021014', '2026-01-17', 'morning', 'present', '2026-01-17 09:50:16', '2026-01-17 09:50:16'),
(1530, 8, 578, '2021003', '2026-01-17', 'morning', 'present', '2026-01-17 09:50:16', '2026-01-17 09:50:16'),
(1531, 8, 605, '2021030', '2026-01-17', 'morning', 'present', '2026-01-17 09:50:16', '2026-01-17 09:50:16'),
(1532, 8, 586, '2021011', '2026-01-17', 'morning', 'present', '2026-01-17 09:50:16', '2026-01-17 09:50:16'),
(1533, 8, 601, '2021026', '2026-01-17', 'morning', 'present', '2026-01-17 09:50:16', '2026-01-17 09:50:16'),
(1534, 8, 597, '2021022', '2026-01-17', 'morning', 'present', '2026-01-17 09:50:16', '2026-01-17 09:50:16'),
(1535, 8, 592, '2021017', '2026-01-17', 'morning', 'present', '2026-01-17 09:50:16', '2026-01-17 09:50:16'),
(1536, 8, 595, '2021020', '2026-01-17', 'morning', 'present', '2026-01-17 09:50:16', '2026-01-17 09:50:16'),
(1537, 8, 584, '2021009', '2026-01-17', 'morning', 'present', '2026-01-17 09:50:16', '2026-01-17 09:50:16'),
(1538, 8, 612, '2021037', '2026-01-17', 'morning', 'present', '2026-01-17 09:50:16', '2026-01-17 09:50:16'),
(1539, 8, 588, '2021013', '2026-01-17', 'morning', 'present', '2026-01-17 09:50:16', '2026-01-17 09:50:16'),
(1540, 8, 624, '2021049', '2026-01-17', 'morning', 'present', '2026-01-17 09:50:16', '2026-01-17 09:50:16'),
(1541, 8, 610, '2021035', '2026-01-17', 'morning', 'present', '2026-01-17 09:50:16', '2026-01-17 09:50:16'),
(1542, 8, 618, '2021043', '2026-01-17', 'morning', 'present', '2026-01-17 09:50:16', '2026-01-17 09:50:16'),
(1543, 8, 599, '2021024', '2026-01-17', 'morning', 'present', '2026-01-17 09:50:16', '2026-01-17 09:50:16'),
(1544, 8, 613, '2021038', '2026-01-17', 'morning', 'present', '2026-01-17 09:50:16', '2026-01-17 09:50:16'),
(1545, 8, 616, '2021041', '2026-01-17', 'morning', 'present', '2026-01-17 09:50:16', '2026-01-17 09:50:16'),
(1546, 8, 625, '2021050', '2026-01-17', 'morning', 'present', '2026-01-17 09:50:16', '2026-01-17 09:50:16'),
(1547, 8, 614, '2021039', '2026-01-17', 'morning', 'present', '2026-01-17 09:50:16', '2026-01-17 09:50:16'),
(1548, 8, 596, '2021021', '2026-01-17', 'morning', 'present', '2026-01-17 09:50:16', '2026-01-17 09:50:16'),
(1549, 8, 623, '2021048', '2026-01-17', 'morning', 'present', '2026-01-17 09:50:16', '2026-01-17 09:50:16'),
(1550, 8, 606, '2021031', '2026-01-17', 'morning', 'present', '2026-01-17 09:50:16', '2026-01-17 09:50:16'),
(1551, 8, 577, '2021002', '2026-01-17', 'morning', 'present', '2026-01-17 09:50:16', '2026-01-17 09:50:16'),
(1552, 8, 622, '2021047', '2026-01-17', 'morning', 'present', '2026-01-17 09:50:16', '2026-01-17 09:50:16'),
(1553, 8, 587, '2021012', '2026-01-17', 'morning', 'present', '2026-01-17 09:50:16', '2026-01-17 09:50:16'),
(1554, 8, 593, '2021018', '2026-01-17', 'morning', 'present', '2026-01-17 09:50:16', '2026-01-17 09:50:16'),
(1555, 8, 615, '2021040', '2026-01-17', 'morning', 'present', '2026-01-17 09:50:16', '2026-01-17 09:50:16'),
(1556, 8, 602, '2021027', '2026-01-17', 'morning', 'present', '2026-01-17 09:50:16', '2026-01-17 09:50:16'),
(1557, 8, 590, '2021015', '2026-01-17', 'morning', 'present', '2026-01-17 09:50:16', '2026-01-17 09:50:16'),
(1558, 8, 579, '2021004', '2026-01-17', 'morning', 'present', '2026-01-17 09:50:16', '2026-01-17 09:50:16'),
(1559, 8, 600, '2021025', '2026-01-17', 'morning', 'present', '2026-01-17 09:50:16', '2026-01-17 09:50:16'),
(1560, 8, 582, '2021007', '2026-01-17', 'morning', 'present', '2026-01-17 09:50:16', '2026-01-17 09:50:16'),
(1561, 8, 604, '2021029', '2026-01-17', 'morning', 'present', '2026-01-17 09:50:16', '2026-01-17 09:50:16');

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
(8, 'Class 1', 'A', '1st Semester', 'dumaraograffie@sac.edu.ph', 0, '2026-01-17 09:13:29'),
(9, 'Class 2', 'B', '1st Semester', 'dumaraograffie@sac.edu.ph', 0, '2026-01-17 09:25:14'),
(10, 'Class 3', 'C', '1st Semester', 'dumaraograffie@sac.edu.ph', 0, '2026-01-17 09:25:45');

-- --------------------------------------------------------

--
-- Table structure for table `email_logs`
--

CREATE TABLE `email_logs` (
  `id` int(11) NOT NULL,
  `teacher_email` varchar(255) NOT NULL,
  `student_email` varchar(255) NOT NULL,
  `class_id` int(11) DEFAULT NULL,
  `email_type` varchar(50) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `email_logs`
--

INSERT INTO `email_logs` (`id`, `teacher_email`, `student_email`, `class_id`, `email_type`, `created_at`) VALUES
(30, 'dumaraograffie@sac.edu.ph', 'emily.davis@student.edu', 8, 'attendance', '2026-01-17 09:50:20'),
(31, 'dumaraograffie@sac.edu.ph', 'john.doe@student.edu', 8, 'attendance', '2026-01-17 09:50:23'),
(32, 'dumaraograffie@sac.edu.ph', 'raptae8888@gmail.com', 8, 'attendance', '2026-01-17 09:50:27'),
(33, 'dumaraograffie@sac.edu.ph', 'caleb.edwards@student.edu', 8, 'attendance', '2026-01-17 09:50:31'),
(34, 'dumaraograffie@sac.edu.ph', 'riley.evans@student.edu', 8, 'attendance', '2026-01-17 09:50:34'),
(35, 'dumaraograffie@sac.edu.ph', 'alexander.garcia@student.edu', 8, 'attendance', '2026-01-17 09:50:38'),
(36, 'dumaraograffie@sac.edu.ph', 'lisa.garcia@student.edu', 8, 'attendance', '2026-01-17 09:50:41'),
(37, 'dumaraograffie@sac.edu.ph', 'ethan.gonzalez@student.edu', 8, 'attendance', '2026-01-17 09:50:44'),
(38, 'dumaraograffie@sac.edu.ph', 'ella.green@student.edu', 8, 'attendance', '2026-01-17 09:50:48'),
(39, 'dumaraograffie@sac.edu.ph', 'elizabeth.hall@student.edu', 8, 'attendance', '2026-01-17 09:50:51'),
(40, 'dumaraograffie@sac.edu.ph', 'ava.harris@student.edu', 8, 'attendance', '2026-01-17 09:50:55'),
(41, 'dumaraograffie@sac.edu.ph', 'sophia.jackson@student.edu', 8, 'attendance', '2026-01-17 09:50:59'),
(42, 'dumaraograffie@sac.edu.ph', 'mike.johnsoniii@student.edu', 8, 'attendance', '2026-01-17 09:51:02'),
(43, 'dumaraograffie@sac.edu.ph', 'grace.king@student.edu', 8, 'attendance', '2026-01-17 09:51:06'),
(44, 'dumaraograffie@sac.edu.ph', 'christopher.lee@student.edu', 8, 'attendance', '2026-01-17 09:51:10'),
(45, 'dumaraograffie@sac.edu.ph', 'abigail.lewis@student.edu', 8, 'attendance', '2026-01-17 09:51:13'),
(46, 'dumaraograffie@sac.edu.ph', 'amelia.lopez@student.edu', 8, 'attendance', '2026-01-17 09:51:17'),
(47, 'dumaraograffie@sac.edu.ph', 'william.martin@student.edu', 8, 'attendance', '2026-01-17 09:51:21'),
(48, 'dumaraograffie@sac.edu.ph', 'charlotte.martinez@student.edu', 8, 'attendance', '2026-01-17 09:51:24'),
(49, 'dumaraograffie@sac.edu.ph', 'kevin.martineziii@student.edu', 8, 'attendance', '2026-01-17 09:51:28'),
(50, 'dumaraograffie@sac.edu.ph', 'levi.mitchell@student.edu', 8, 'attendance', '2026-01-17 09:51:32');

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
(576, 8, 2021001, 'john.doe@student.edu', '$2y$10$nXxKBjmfKYBngGNeKzbaVOm75kpkHsx9V59ClrL6Y/emQxVn.k6MO', 1, 'John', 'Doe', 'A', '', 'BS Computer Science', '2026-01-17', 'dumaraograffie@sac.edu.ph'),
(577, 8, 2021002, 'jane.smithjr@student.edu', '$2y$10$ZHZGC47ApZ87NDYsfiw6ieOiRjfFVYY597e835obyVER2giig2reu', 1, 'Jane', 'Smith', 'B', 'Jr.', 'BS Information Technology', '2026-01-17', 'dumaraograffie@sac.edu.ph'),
(578, 8, 2021003, 'mike.johnsoniii@student.edu', '$2y$10$C5fHbMX40fncl16M4VUtSeYmCPCQVhdsKS7UmfdG1td.iJvB6KaBe', 1, 'Mike', 'Johnson', '', 'III', 'BS Computer Engineering', '2026-01-17', 'dumaraograffie@sac.edu.ph'),
(579, 8, 2021004, 'anna.williams@student.edu', '$2y$10$lJ9SbkRdBiuEp30mtW.NXOqYYdAOR325q0MGvbhXzQ8kvp5S5GY7G', 1, 'Anna', 'Williams', 'C', '', 'BA Psychology', '2026-01-17', 'dumaraograffie@sac.edu.ph'),
(580, 8, 2021005, 'david.brownsr@student.edu', '$2y$10$ma3LP9/b5YJUDRNL2sqPZ.drxrEq/cHH2frMs4.iP6coD3JtXnkw2', 1, 'David', 'Brown', '', 'Sr.', 'BA English', '2026-01-17', 'dumaraograffie@sac.edu.ph'),
(581, 8, 2021006, 'emily.davis@student.edu', '$2y$10$1vmsCJUNSc93noSdonDL5OHOkS6zFY.CzKlLt0SiI5MWXern/RUD2', 1, 'Emily', 'Davis', 'D', '', 'BS Business Administration', '2026-01-17', 'dumaraograffie@sac.edu.ph'),
(582, 8, 2021007, 'robert.wilsonjr@student.edu', '$2y$10$pI7df2X3CVdyozInMGWwEOSPY/OQ7O0uFKm0cvbUnfsurWYzVTybW', 1, 'Robert', 'Wilson', '', 'Jr.', 'BS Nursing', '2026-01-17', 'dumaraograffie@sac.edu.ph'),
(583, 8, 2021008, 'lisa.garcia@student.edu', '$2y$10$O.uzGN2KpVm9RiTlHukGa.1K1SNLCescOaDwQehVRv4O6ImwZvbi6', 1, 'Lisa', 'Garcia', 'E', '', 'BA Mathematics', '2026-01-17', 'dumaraograffie@sac.edu.ph'),
(584, 8, 2021009, 'kevin.martineziii@student.edu', '$2y$10$W9.pBszc2FaQE1Z0TrEChuMMzLewYdczk5TR0K851ol/a.wksDjiO', 1, 'Kevin', 'Martinez', '', 'III', 'BS Engineering', '2026-01-17', 'dumaraograffie@sac.edu.ph'),
(585, 8, 2021010, 'sarah.anderson@student.edu', '$2y$10$kpT/DLQoEWWuOOy0M1li9uzmA33PM8J5BeKyGWy4rdEjMiftUX5OW', 1, 'Sarah', 'Anderson', 'F', '', 'BS Computer Science', '2026-01-17', 'dumaraograffie@sac.edu.ph'),
(586, 8, 2021011, 'christopher.lee@student.edu', '$2y$10$GsObv9WgjaMLXvToCiHMXueLQR8m3yVHcZ1CcPhiBKXINxJ3Mewpu', 1, 'Christopher', 'Lee', 'G', '', 'BS Information Technology', '2026-01-17', 'dumaraograffie@sac.edu.ph'),
(587, 8, 2021012, 'olivia.taylor@student.edu', '$2y$10$MR2KvmzoNrKCtBsG/oYdb.D7Y/P1pu/nsw8eVs9YZZYX/CoOj9hP.', 1, 'Olivia', 'Taylor', 'H', '', 'BS Computer Engineering', '2026-01-17', 'dumaraograffie@sac.edu.ph'),
(588, 8, 2021013, 'james.moore@student.edu', '$2y$10$RJ9NyP7VOeyMs/oSyRr0we5IV1znBcyg72ey0P3bNcd6qLvJ0UngC', 1, 'James', 'Moore', '', 'Jr.', 'BA Psychology', '2026-01-17', 'dumaraograffie@sac.edu.ph'),
(589, 8, 2021014, 'sophia.jackson@student.edu', '$2y$10$nC2GY.wQWUkutLAJMHh0O.HLJsNoQAWeMYZ4cCGHCrHMNNZGnibZ.', 1, 'Sophia', 'Jackson', 'I', '', 'BA English', '2026-01-17', 'dumaraograffie@sac.edu.ph'),
(590, 8, 2021015, 'benjamin.white@student.edu', '$2y$10$QBi8PhlngioedPD.d3agoeb92AGlVpQHCxvyXBaBFmu9WRfEK9Ub2', 1, 'Benjamin', 'White', '', 'III', 'BS Business Administration', '2026-01-17', 'dumaraograffie@sac.edu.ph'),
(591, 8, 2021016, 'ava.harris@student.edu', '$2y$10$.1NKkO.AYJwAZeQnwSX14erRd/TY2b4og/V4X/T/v6oAlh4tJ0dyG', 1, 'Ava', 'Harris', 'J', '', 'BS Nursing', '2026-01-17', 'dumaraograffie@sac.edu.ph'),
(592, 8, 2021017, 'william.martin@student.edu', '$2y$10$QCiH.iAR.FDN3QZ1TYgYQea0fH.0N5Xr8cHSrOXQAN40YJsq4oCnS', 1, 'William', 'Martin', 'K', '', 'BA Mathematics', '2026-01-17', 'dumaraograffie@sac.edu.ph'),
(593, 8, 2021018, 'isabella.thompson@student.edu', '$2y$10$xwLlvRAwLw5rVj5M/k0sWOZuQxfuJhzumAqSkeXxe1Z5pbk657xQ.', 1, 'Isabella', 'Thompson', 'L', '', 'BS Engineering', '2026-01-17', 'dumaraograffie@sac.edu.ph'),
(594, 8, 2021019, 'alexander.garcia@student.edu', '$2y$10$vNuga.jXx1l1Q2gfrozzqu.MA.ohn6F0WU9BJ2k43/M1.rXVK6r8W', 1, 'Alexander', 'Garcia', '', 'Sr.', 'BS Computer Science', '2026-01-17', 'dumaraograffie@sac.edu.ph'),
(595, 8, 2021020, 'charlotte.martinez@student.edu', '$2y$10$d66VYNMhmdIN/qcDhLyaIOv/FlfPBkGWq7WImTz.uKBFY/XeJyZVi', 1, 'Charlotte', 'Martinez', 'M', '', 'BS Information Technology', '2026-01-17', 'dumaraograffie@sac.edu.ph'),
(596, 8, 2021021, 'michael.rodriguez@student.edu', '$2y$10$Nu/2jYuhob1aiCVlzq4Rq.6Cx0UwhaaC/ktMeVkKgCCXJ2jTQ6eme', 1, 'Michael', 'Rodriguez', 'N', '', 'BS Computer Engineering', '2026-01-17', 'dumaraograffie@sac.edu.ph'),
(597, 8, 2021022, 'amelia.lopez@student.edu', '$2y$10$V0qg1JOI2qdGHjlkbxT91eMEVavl3DTolFV.hXX.zmWzJENVl9JXu', 1, 'Amelia', 'Lopez', 'O', '', 'BA Psychology', '2026-01-17', 'dumaraograffie@sac.edu.ph'),
(598, 8, 2021023, 'ethan.gonzalez@student.edu', '$2y$10$Yvl7nUcjbkxr1O4PApBglOcUSucXeT/M7NpUCU/6ZXNrJRKYbENIO', 1, 'Ethan', 'Gonzalez', 'P', '', 'BA English', '2026-01-17', 'dumaraograffie@sac.edu.ph'),
(599, 8, 2021024, 'harper.perez@student.edu', '$2y$10$ouwq/SAffHDCh371hy4ewOF8941Nr/EZhNr1jvUgabVsX0CRKvcwy', 1, 'Harper', 'Perez', 'Q', '', 'BS Business Administration', '2026-01-17', 'dumaraograffie@sac.edu.ph'),
(600, 8, 2021025, 'logan.williams@student.edu', '$2y$10$T5bMOITGh0gfsllm1SyGbefH360/l.jiz2X4yqdCcNMgApLIbhxVC', 1, 'Logan', 'Williams', '', 'Jr.', 'BS Nursing', '2026-01-17', 'dumaraograffie@sac.edu.ph'),
(601, 8, 2021026, 'abigail.lewis@student.edu', '$2y$10$yMNa6hmfFLa0BJX5xBexaeHskyToe73OI8ZHZupPnSnNF4x1oC9Mu', 1, 'Abigail', 'Lewis', 'R', '', 'BA Mathematics', '2026-01-17', 'dumaraograffie@sac.edu.ph'),
(602, 8, 2021027, 'jacob.walker@student.edu', '$2y$10$xD.2wE1pJjQKm3fTC8ax4.0Dsgzrir2SC0gdAKSoJUSYEJNPcXQ.C', 1, 'Jacob', 'Walker', 'S', '', 'BS Engineering', '2026-01-17', 'dumaraograffie@sac.edu.ph'),
(603, 8, 2021028, 'elizabeth.hall@student.edu', '$2y$10$WTR5vY8rRXAxvKjdS1stee8.mVntSCkCknrFjoOMTJ8zVGp8FA1IC', 1, 'Elizabeth', 'Hall', 'T', '', 'BS Computer Science', '2026-01-17', 'dumaraograffie@sac.edu.ph'),
(604, 8, 2021029, 'daniel.young@student.edu', '$2y$10$klvrLdr3pEdX1vaIY47O3eALvhdTRVIc9B.sCDhehYnH4w0bd75vG', 1, 'Daniel', 'Young', '', 'III', 'BS Information Technology', '2026-01-17', 'dumaraograffie@sac.edu.ph'),
(605, 8, 2021030, 'grace.king@student.edu', '$2y$10$ciAy6QV4O9qSNOyH6Rf84.xG/EytMfT4ONSb0cZmkYJjqyniFkIUG', 1, 'Grace', 'King', 'U', '', 'BS Computer Engineering', '2026-01-17', 'dumaraograffie@sac.edu.ph'),
(606, 8, 2021031, 'henry.scott@student.edu', '$2y$10$VPjlT3v/QASU8h.xleo0W.5Y.VY11of.ozjLXhNAV5WG20EN.2PJC', 1, 'Henry', 'Scott', 'V', '', 'BA Psychology', '2026-01-17', 'dumaraograffie@sac.edu.ph'),
(607, 8, 2021032, 'ella.green@student.edu', '$2y$10$zpYccIfKgxc5x9oTAUfFPu82z3uKlEg8svlxVFxnI2EosIDFx4rFK', 1, 'Ella', 'Green', 'W', '', 'BA English', '2026-01-17', 'dumaraograffie@sac.edu.ph'),
(608, 8, 2021033, 'sebastian.adams@student.edu', '$2y$10$5HjoYAVqU9Dqkanm34DsR.c.aawuY2Cj8KgfnaW.PyIduvPWW0il6', 1, 'Sebastian', 'Adams', '', 'Jr.', 'BS Business Administration', '2026-01-17', 'dumaraograffie@sac.edu.ph'),
(609, 8, 2021034, 'scarlett.baker@student.edu', '$2y$10$8a17H4iwITeCJVk5D91Pd.nvrFP4THwrWuWgoJfBOEyYV1SE3y1u6', 1, 'Scarlett', 'Baker', 'X', '', 'BS Nursing', '2026-01-17', 'dumaraograffie@sac.edu.ph'),
(610, 8, 2021035, 'jack.nelson@student.edu', '$2y$10$PPpwGLKqKLP6f2mjewKAHupozoY.Vjes4QmPdkR8GIBGuC/iGZyfW', 1, 'Jack', 'Nelson', 'Y', '', 'BA Mathematics', '2026-01-17', 'dumaraograffie@sac.edu.ph'),
(611, 8, 2021036, 'zoey.carter@student.edu', '$2y$10$nGcPNJ5BnLcmPvhHT7RbG.rNt0ge//OI/uKHNPCV/N2u9dEwRQ0py', 1, 'Zoey', 'Carter', 'Z', '', 'BS Engineering', '2026-01-17', 'dumaraograffie@sac.edu.ph'),
(612, 8, 2021037, 'levi.mitchell@student.edu', '$2y$10$yUcG/G/GQExwLfHnCtcQFOURkPXM.85w5RbRY8CEU0VUG23xuc1L2', 1, 'Levi', 'Mitchell', 'A', '', 'BS Computer Science', '2026-01-17', 'dumaraograffie@sac.edu.ph'),
(613, 8, 2021038, 'layla.perez@student.edu', '$2y$10$vp/uwF3TffGxNLGZftu/SuONbFvzTO9RopbyXsOoaJ63GF4yjFYBe', 1, 'Layla', 'Perez', 'B', '', 'BS Information Technology', '2026-01-17', 'dumaraograffie@sac.edu.ph'),
(614, 8, 2021039, 'owen.roberts@student.edu', '$2y$10$hZxUTK/qV5o65FIhOJGw1O5UJYk/zbvOwWO2ACH9d8JxbEDmBwT/G', 1, 'Owen', 'Roberts', '', 'Sr.', 'BS Computer Engineering', '2026-01-17', 'dumaraograffie@sac.edu.ph'),
(615, 8, 2021040, 'madison.turner@student.edu', '$2y$10$.Vq34VPIA9mizfSx1Yys1.5TxrzDAgLehwjKMWX5db1wtYE8Y1Iva', 1, 'Madison', 'Turner', 'C', '', 'BA Psychology', '2026-01-17', 'dumaraograffie@sac.edu.ph'),
(616, 8, 2021041, 'ryan.phillips@student.edu', '$2y$10$h5G790DmCILRrnUF301miOFq6YL/Ic483emX7GpC1TX/LBvgy/6A2', 1, 'Ryan', 'Phillips', 'D', '', 'BA English', '2026-01-17', 'dumaraograffie@sac.edu.ph'),
(617, 8, 2021042, 'penelope.campbell@student.edu', '$2y$10$3fOp761QgzyjcsB/jYNn3OcGC.tdsPQuQRxTqp84h1fZt3V7TPxAO', 1, 'Penelope', 'Campbell', 'E', '', 'BS Business Administration', '2026-01-17', 'dumaraograffie@sac.edu.ph'),
(618, 8, 2021043, 'nolan.parker@student.edu', '$2y$10$Ir2wasC75nYi5Pyt.lhKzuVcROs6mnMv5ctFgcA3jwbV4gUTNrq8.', 1, 'Nolan', 'Parker', 'F', '', 'BS Nursing', '2026-01-17', 'dumaraograffie@sac.edu.ph'),
(619, 8, 2021044, 'riley.evans@student.edu', '$2y$10$pEeflrjia/7qLwc.6NVfKOGVspaHC057kXo9YEai4bVEw04u7OmrW', 1, 'Riley', 'Evans', 'G', '', 'BA Mathematics', '2026-01-17', 'dumaraograffie@sac.edu.ph'),
(620, 8, 2021045, 'caleb.edwards@student.edu', '$2y$10$fx0QAi8WyKC.KKWh16X/DexBLoHZmTnLKdXoW8w67zZiY3mgJDwzW', 1, 'Caleb', 'Edwards', '', 'III', 'BS Engineering', '2026-01-17', 'dumaraograffie@sac.edu.ph'),
(621, 8, 2021046, 'madelyn.collins@student.edu', '$2y$10$32kT4CGSZyVUHZE113mWN.l2AOf/B4q/t4NxryQ0.G4eePGiTSlDW', 1, 'Madelyn', 'Collins', 'H', '', 'BS Computer Science', '2026-01-17', 'dumaraograffie@sac.edu.ph'),
(622, 8, 2021047, 'brayden.stewart@student.edu', '$2y$10$zL3sTZygdSKsMqFtDIcT7.ajY5s18jFM1Sj8cMaf9K1p1.TKXQgB.', 1, 'Brayden', 'Stewart', 'I', '', 'BS Information Technology', '2026-01-17', 'dumaraograffie@sac.edu.ph'),
(623, 8, 2021048, 'aria.sanchez@student.edu', '$2y$10$4LRMvqQJ1snLy3xE37OB2OZMp.nksYl33QrE33kmVsXpJByBowuc.', 1, 'Aria', 'Sanchez', 'J', '', 'BS Computer Engineering', '2026-01-17', 'dumaraograffie@sac.edu.ph'),
(624, 8, 2021049, 'lincoln.morris@student.edu', '$2y$10$AZ/7NUWWn9EcYE7Zm6jKCe4qU92j0pTvoXvDW9tnKrg8XGxIqUlg6', 1, 'Lincoln', 'Morris', 'K', '', 'BA Psychology', '2026-01-17', 'dumaraograffie@sac.edu.ph'),
(625, 8, 2021050, 'brooklyn.rivera@student.edu', '$2y$10$cJGIi.qF1CR3JDCfl3uLsedZZnjcOdgeu3KTDzSHbvpxZO.2AyLXe', 1, 'Brooklyn', 'Rivera', 'L', '', 'BA English', '2026-01-17', 'dumaraograffie@sac.edu.ph'),
(626, 0, 1111111, 'rey.john@example.com', '$2y$10$5rN77Cp14OuqqUp3tjt1d.0plmbwPkz1tpLreCrRAxbhsaqN.MZm2', 1, 'John', 'Rey', 'T', '', 'BS Information Technology', '2026-01-17', 'dumaraograffie@sac.edu.ph'),
(627, 9, 2147483647, 'raptae8888@gmail.com', '$2y$10$MWzsXK3T3kLMYpFckbjT1O3OacPw13Zdop/lgbkjcO55PlrNNJaHS', 0, 'Raffie', 'Dumaraog', 'E', '', 'BS Information Technology', '2026-01-17', 'dumaraograffie@sac.edu.ph');

-- --------------------------------------------------------

--
-- Table structure for table `student_classes`
--

CREATE TABLE `student_classes` (
  `id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `class_id` int(11) NOT NULL,
  `enrolled_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `student_classes`
--

INSERT INTO `student_classes` (`id`, `student_id`, `class_id`, `enrolled_at`) VALUES
(152, 576, 8, '2026-01-17 09:23:06'),
(153, 577, 8, '2026-01-17 09:23:06'),
(154, 578, 8, '2026-01-17 09:23:06'),
(155, 579, 8, '2026-01-17 09:23:06'),
(157, 581, 8, '2026-01-17 09:23:06'),
(158, 582, 8, '2026-01-17 09:23:06'),
(159, 583, 8, '2026-01-17 09:23:06'),
(160, 584, 8, '2026-01-17 09:23:06'),
(162, 586, 8, '2026-01-17 09:23:06'),
(163, 587, 8, '2026-01-17 09:23:06'),
(164, 588, 8, '2026-01-17 09:23:06'),
(165, 589, 8, '2026-01-17 09:23:06'),
(166, 590, 8, '2026-01-17 09:23:06'),
(167, 591, 8, '2026-01-17 09:23:06'),
(168, 592, 8, '2026-01-17 09:23:06'),
(169, 593, 8, '2026-01-17 09:23:06'),
(170, 594, 8, '2026-01-17 09:23:06'),
(171, 595, 8, '2026-01-17 09:23:06'),
(172, 596, 8, '2026-01-17 09:23:06'),
(173, 597, 8, '2026-01-17 09:23:06'),
(174, 598, 8, '2026-01-17 09:23:06'),
(175, 599, 8, '2026-01-17 09:23:06'),
(176, 600, 8, '2026-01-17 09:23:06'),
(177, 601, 8, '2026-01-17 09:23:06'),
(178, 602, 8, '2026-01-17 09:23:06'),
(179, 603, 8, '2026-01-17 09:23:06'),
(180, 604, 8, '2026-01-17 09:23:06'),
(181, 605, 8, '2026-01-17 09:23:06'),
(182, 606, 8, '2026-01-17 09:23:06'),
(183, 607, 8, '2026-01-17 09:23:06'),
(186, 610, 8, '2026-01-17 09:23:06'),
(188, 612, 8, '2026-01-17 09:23:06'),
(189, 613, 8, '2026-01-17 09:23:06'),
(190, 614, 8, '2026-01-17 09:23:06'),
(191, 615, 8, '2026-01-17 09:23:06'),
(192, 616, 8, '2026-01-17 09:23:06'),
(194, 618, 8, '2026-01-17 09:23:06'),
(195, 619, 8, '2026-01-17 09:23:06'),
(196, 620, 8, '2026-01-17 09:23:06'),
(198, 622, 8, '2026-01-17 09:23:06'),
(199, 623, 8, '2026-01-17 09:23:06'),
(200, 624, 8, '2026-01-17 09:23:06'),
(201, 625, 8, '2026-01-17 09:23:06'),
(202, 581, 9, '2026-01-17 09:25:34'),
(203, 627, 9, '2026-01-17 09:26:32'),
(204, 627, 8, '2026-01-17 09:31:28');

-- --------------------------------------------------------

--
-- Table structure for table `student_requests`
--

CREATE TABLE `student_requests` (
  `id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `student_number` varchar(50) NOT NULL,
  `student_name` varchar(255) NOT NULL,
  `student_email` varchar(255) NOT NULL,
  `class_id` int(11) DEFAULT NULL,
  `class_name` varchar(255) DEFAULT NULL,
  `teacher_email` varchar(255) NOT NULL,
  `request_type` enum('grade','attendance') NOT NULL,
  `term` enum('prelim','midterm','finals','all') DEFAULT NULL,
  `message` text DEFAULT NULL,
  `status` enum('pending','resolved') NOT NULL DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `is_seen` tinyint(1) NOT NULL DEFAULT 0,
  `resolved_at` timestamp NULL DEFAULT NULL,
  `resolved_by` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `student_requests`
--

INSERT INTO `student_requests` (`id`, `student_id`, `student_number`, `student_name`, `student_email`, `class_id`, `class_name`, `teacher_email`, `request_type`, `term`, `message`, `status`, `created_at`, `is_seen`, `resolved_at`, `resolved_by`) VALUES
(10, 627, '2147483647', 'Raffie Dumaraog', 'raptae8888@gmail.com', 8, 'Class 1', 'dumaraograffie@sac.edu.ph', 'grade', NULL, NULL, 'pending', '2026-01-17 10:00:29', 0, NULL, NULL);

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
(9, 'Teacher', 'Raffie', 'Teacher Raffie', '', 'dumaraograffie@sac.edu.ph', '$2y$10$MCY4N7wNVcKmaxqpkxye7ODbbzOu33NH1VxQekFL4PWVGwRIxpKCy', 'teacher', '2025-11-03 15:09:07'),
(11, 'Site', 'Admin', 'Site Admin', 'Administration', 'raffiedumaraog@gmail.com', '$2a$12$98P3GPGfBgaDc/tV8s885uFdjiQq2LLeVwNuI9w2RvN9YMlErH9Wq', 'admin', '2025-12-15 12:14:29'),
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
-- Indexes for table `email_logs`
--
ALTER TABLE `email_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_teacher_date` (`teacher_email`,`created_at`);

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
-- Indexes for table `student_classes`
--
ALTER TABLE `student_classes`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_student_class` (`student_id`,`class_id`),
  ADD KEY `idx_class` (`class_id`),
  ADD KEY `idx_student` (`student_id`);

--
-- Indexes for table `student_requests`
--
ALTER TABLE `student_requests`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_teacher_status` (`teacher_email`,`status`,`created_at`);

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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1562;

--
-- AUTO_INCREMENT for table `calculated_grades`
--
ALTER TABLE `calculated_grades`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=589;

--
-- AUTO_INCREMENT for table `classes`
--
ALTER TABLE `classes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `email_logs`
--
ALTER TABLE `email_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=51;

--
-- AUTO_INCREMENT for table `grades`
--
ALTER TABLE `grades`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1735;

--
-- AUTO_INCREMENT for table `password_reset_requests`
--
ALTER TABLE `password_reset_requests`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `students`
--
ALTER TABLE `students`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=628;

--
-- AUTO_INCREMENT for table `student_classes`
--
ALTER TABLE `student_classes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=205;

--
-- AUTO_INCREMENT for table `student_requests`
--
ALTER TABLE `student_requests`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

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

--
-- Constraints for table `student_classes`
--
ALTER TABLE `student_classes`
  ADD CONSTRAINT `student_classes_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `student_classes_ibfk_2` FOREIGN KEY (`class_id`) REFERENCES `classes` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
