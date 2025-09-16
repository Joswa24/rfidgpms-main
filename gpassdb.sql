-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Sep 16, 2025 at 09:13 AM
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
-- Database: `gpassdb`
--

-- --------------------------------------------------------

--
-- Table structure for table `about`
--

CREATE TABLE `about` (
  `id` int(11) NOT NULL,
  `logo1` varchar(255) NOT NULL,
  `logo2` varchar(255) NOT NULL,
  `name` varchar(255) NOT NULL,
  `address` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `about`
--

INSERT INTO `about` (`id`, `logo1`, `logo2`, `name`, `address`) VALUES
(1, 'mcc.png', 'madridejos.png', 'Madridejos Community College', 'Bunakan, Madridejos, Cebu');

-- --------------------------------------------------------

--
-- Table structure for table `admin_sessions`
--

CREATE TABLE `admin_sessions` (
  `id` int(11) NOT NULL,
  `location` varchar(255) DEFAULT NULL,
  `ip_address` varchar(255) DEFAULT NULL,
  `device` varchar(255) DEFAULT NULL,
  `date_logged` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `admin_sessions`
--

INSERT INTO `admin_sessions` (`id`, `location`, `ip_address`, `device`, `date_logged`) VALUES
(20, 'Bacolod City, Philippines', '2001:fd8:18c3:a83b:bcae:71d0:b449:6826', '753ba0fc779274301e96db88e1da550691cb88122cdf15fbf9d771d52c765514', '2024-11-29 14:01:06'),
(21, 'Bacolod City, Philippines', '2001:fd8:18c3:a83b:bcae:71d0:b449:6826', '753ba0fc779274301e96db88e1da550691cb88122cdf15fbf9d771d52c765514', '2024-11-29 14:09:50'),
(22, 'Bacolod City, Philippines', '2001:fd8:18c3:a83b:bcae:71d0:b449:6826', '753ba0fc779274301e96db88e1da550691cb88122cdf15fbf9d771d52c765514', '2024-11-29 14:18:54'),
(23, 'Bacolod City, Philippines', '2001:fd8:18c3:a83b:bcae:71d0:b449:6826', '753ba0fc779274301e96db88e1da550691cb88122cdf15fbf9d771d52c765514', '2024-11-29 14:25:53'),
(24, 'Iloilo City, Philippines', '2001:fd8:1f57:2c65:6da3:0:d797:cfe4', 'c9690321bd40e98f164cc63addd64118f4a75e816bc917b0ce328eba3fb74f7f', '2024-11-30 04:03:20'),
(25, 'Angeles City, Philippines', '122.54.225.58', '22872a0e8a859fc23f285e3907d8e5393878a719ce2ef7e88710f94493e6caa9', '2024-11-30 06:50:00'),
(26, 'Bacolod City, Philippines', '110.54.230.243', 'afc45245ae7123b7307ff2ff1989351ad9e0f5e60bac421cafe2ef0d9aab1a99', '2024-12-02 07:04:24'),
(27, 'Bacolod City, Philippines', '110.54.182.120', 'f44add62ebd05f224aed9e3d079ad49afceec85155a8244eaf056667eacb6b79', '2024-12-03 08:40:32'),
(28, 'Bacolod City, Philippines', '110.54.182.28', '3a788e4888ec4fcbbf9f69ec6c2823c59b3c1390c78e64ca0a1d33cb68c936a3', '2024-12-04 09:18:21'),
(29, 'Bacolod City, Philippines', '110.54.228.25', '11348ee6f5a578c606f19c74de1f5ee36f0e1a494ea7e30f764b50009b4a8504', '2024-12-05 05:43:46'),
(30, 'Bacolod City, Philippines', '110.54.230.162', '7a6b147004d648d93f28b2b265c254a2343c81696d71208b769289709aa1093a', '2024-12-07 02:36:31'),
(31, 'Bacolod City, Philippines', '2001:fd8:18c6:4380:9909:c752:8176:5271', '1e9690a7d0a0af09696f73282bbb4423b1981a9c1f964af6fb408053cb874d7c', '2024-12-09 07:09:09'),
(32, 'Bacolod City, Philippines', '2001:fd8:18c6:4380:7030:f9e6:e5a6:df31', '8acde11aa109d5974dbfd238e7bc0991fc5e0ee426cce0f82e7c0a9559dedcb5', '2024-12-09 09:02:51'),
(33, 'Bacolod City, Philippines', '2001:fd8:18c6:4380:88af:d1d2:1804:f0e0', '13e52e3b9125f3e06ed6863101f6e9e14f208c55cea37a954eab2bfca55afecc', '2024-12-09 09:17:27'),
(34, 'Bacolod City, Philippines', '2001:fd8:18c6:4380:4525:5da4:de62:f243', '7e3cf50da282f87e90dfb14698bf453a867007f278d7c2c43c80bf39b156b0ed', '2024-12-09 09:48:33'),
(35, 'Bacolod City, Philippines', '110.54.229.117', '6725ee5c2302265ac41df547bb8abbb9e50c8da227d0629b85017bf984e8f7b4', '2024-12-10 05:41:39'),
(36, 'Iloilo City, Philippines', '2001:fd8:1a66:2af3:c0ec:ffd3:e72:27f6', '27696d5cbf6b53924931b141c9a4b756a06a5bbb25606f1aa2dec4c2c4d7b6b5', '2024-12-10 23:55:12'),
(37, 'Iloilo City, Philippines', '2001:fd8:1a66:2af3:60ce:d316:680d:7754', '11e481231cad1adabf6990175fb6b3b16886d0b3e87d7ff22babb16d861ec938', '2024-12-11 00:12:56'),
(38, 'Bacolod City, Philippines', '110.54.231.158', '4a5536c3623284d34d0fe6bc8813f4dd31e77d5b22c79051a0bed0208087fe5b', '2024-12-11 07:27:55'),
(39, 'Bacolod City, Philippines', '110.54.223.255', '46fbf0ab5355c3e7cc6ec6ba345c2b7bf74df9bc253d78146fd2246b70b2f2a4', '2024-12-12 13:34:28'),
(40, 'Iloilo City, Philippines', '2001:fd8:20e0:39ef:95c3:234e:281c:f86b', '1f4d159afb60bdba2c6ebbbebb042b97c3073695511dcf9fe3b0d18986f34422', '2024-12-13 10:04:10'),
(41, 'Iloilo City, Philippines', '2001:fd8:1a66:1aa7:304c:d1bb:3b98:bb06', '95920a6706809e494e97e59646d58527b47a1ddace21beb55a99a31fa8cf1962', '2024-12-13 14:39:45'),
(42, 'Bacolod City, Philippines', '2001:fd8:18c0:81d9:39b2:cc0a:ef43:ba70', '9b2bbfcfec3078ae42e4c234a5f1456b22bd79da735650be1922a223ca65be84', '2024-12-14 03:23:57'),
(43, 'Bacolod City, Philippines', '2001:fd8:18c0:81d9:50f8:fdc5:ced7:4c99', '8fd9e1d497cbe7ca6073d663edf38dd04e0bc4fef045c71509a9a6e2fc00fc66', '2024-12-14 03:26:25'),
(44, 'Bacolod City, Philippines', '110.54.172.98', '9de41373b8d96078056d502111fcb38424758ff502b1347d384e61567e38a303', '2024-12-19 01:55:25'),
(45, 'Iloilo City, Philippines', '112.198.112.224', 'f820dcfc2fef775d9b01ded465a37858c3ba1b6e7937ed96b9bd2d972dc44276', '2024-12-19 06:01:11'),
(46, 'Bacolod City, Philippines', '110.54.230.26', 'e45a99f619de50c4d82d68e445040fb18a76c35916f073f58c1e96049cd25ff4', '2024-12-22 15:07:58'),
(47, 'Bacolod City, Philippines', '110.54.230.62', '5c21a3aef3aaf1d26fd8650e856591cc8245f1174a12026e2a246e9ea696b2b0', '2024-12-23 03:36:16'),
(48, 'Bacolod City, Philippines', '110.54.230.72', '5a1530b0b44e13f9f62c8db91f36525efbb685fac180bd767be8b1ade698f08d', '2024-12-23 05:16:14'),
(49, 'Bacolod City, Philippines', '110.54.223.221', 'a77604ecf57a9d2e19622fe962bdbe140984b8525c2a8f2e9802827a016bfa70', '2025-01-06 13:21:56'),
(50, 'Bacolod City, Philippines', '110.54.223.254', 'b833507879e625e9bdfc0a1e7a804e94b19e88ebb52eecc6c0d22c34add5c6b6', '2025-01-14 07:33:37'),
(51, 'Bacolod City, Philippines', '110.54.223.246', 'c9af022e761e165c33e575a0a13f30d7a0ad066aab0f3c6e49b053275f797e38', '2025-01-14 18:59:16');

-- --------------------------------------------------------

--
-- Table structure for table `archived_attendance_20250727_190813`
--

CREATE TABLE `archived_attendance_20250727_190813` (
  `id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `id_number` varchar(50) NOT NULL,
  `time_in` datetime NOT NULL,
  `time_out` datetime DEFAULT NULL,
  `department` varchar(255) NOT NULL,
  `location` varchar(255) NOT NULL,
  `instructor_id` varchar(9) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `archived_attendance_logs`
--

CREATE TABLE `archived_attendance_logs` (
  `id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `id_number` varchar(50) NOT NULL,
  `time_in` datetime NOT NULL,
  `time_out` datetime DEFAULT NULL,
  `department` varchar(255) NOT NULL,
  `location` varchar(255) NOT NULL,
  `instructor_id` varchar(9) NOT NULL,
  `status` varchar(20) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `archived_attendance_logs`
--

INSERT INTO `archived_attendance_logs` (`id`, `student_id`, `id_number`, `time_in`, `time_out`, `department`, `location`, `instructor_id`, `status`) VALUES
(190, 40, '2024-1697', '2025-08-06 19:08:36', '2025-08-06 19:08:42', 'BSIT', 'ComLab1', '', NULL),
(191, 40, '2024-1697', '2025-08-06 19:16:01', '2025-08-06 19:16:04', 'BSIT', 'ComLab1', '', NULL),
(192, 40, '2024-1697', '2025-08-06 19:31:42', '2025-08-06 19:31:46', 'BSIT', 'ComLab1', '', NULL),
(193, 40, '2024-1697', '2025-08-06 19:33:46', '2025-08-06 19:41:09', 'BSIT', 'ComLab1', '', NULL),
(194, 40, '2024-1697', '2025-08-06 19:44:21', '2025-08-06 19:44:28', 'BSIT', 'ComLab1', '', NULL),
(195, 40, '2024-1697', '2025-08-09 11:52:32', '2025-08-09 11:52:41', 'BSIT', 'ComLab1', '', NULL),
(196, 40, '2024-1697', '2025-08-12 16:10:26', NULL, 'BSIT', 'ComLab2', '', NULL),
(197, 40, '2024-1697', '2025-08-12 16:58:31', '2025-08-12 16:58:46', 'BSIT', 'ComLab1', '', NULL),
(198, 40, '2024-1697', '2025-08-12 17:17:47', NULL, 'BSIT', 'ComLab1', '', NULL),
(199, 40, '2024-1697', '2025-08-15 14:51:19', '2025-08-15 15:25:34', 'BSIT', 'ComLab1', '', NULL),
(200, 46, '8888-8888', '2025-08-15 15:44:53', '2025-08-15 15:44:53', 'BSIT', 'ComLab1', '', NULL),
(201, 44, '9999-9999', '2025-08-15 15:46:32', '2025-08-15 15:47:24', 'BSIT', 'ComLab1', '', NULL),
(202, 46, '8888-8888', '2025-08-15 15:57:35', '2025-08-15 15:58:02', 'BSIT', 'ComLab1', '', NULL),
(203, 46, '8888-8888', '2025-08-15 16:13:25', '2025-08-15 16:23:52', 'BSIT', 'ComLab1', '', NULL),
(204, 46, '8888-8888', '2025-08-15 16:30:27', '2025-08-15 16:31:45', 'BSIT', 'ComLab1', '', NULL),
(206, 40, '2024-1697', '2025-08-16 13:34:39', '2025-08-16 13:34:39', 'Department', 'Location', '', NULL),
(207, 46, '8888-8888', '2025-08-16 13:48:43', '2025-08-16 13:48:43', 'BSIT', 'ComLab2', '', NULL),
(208, 46, '8888-8888', '2025-08-16 13:50:01', '2025-08-16 13:50:22', 'Department', 'Location', '', NULL),
(209, 46, '8888-8888', '2025-08-19 11:49:30', '2025-08-19 11:49:30', 'BSIT', 'ComLab1', '', NULL),
(210, 40, '2024-1697', '2025-08-22 11:07:54', '2025-08-22 11:07:59', 'BSIT', 'ComLab2', '', NULL),
(211, 40, '2024-1697', '2025-08-22 11:09:26', '2025-08-22 11:09:39', 'BSIT', 'ComLab2', '', NULL),
(213, 40, '2024-1697', '2025-08-25 11:53:33', '2025-08-25 11:55:25', 'BSIT', 'ComLab2', '', NULL),
(215, 46, '8888-8888', '2025-08-25 12:26:22', NULL, 'BSIT', 'ComLab2', '', NULL),
(216, 44, '9999-9999', '2025-08-25 12:26:34', NULL, 'BSIT', 'ComLab2', '', NULL),
(217, 44, '9999-9999', '2025-08-25 12:27:57', NULL, 'BSIT', 'ComLab1', '', NULL),
(218, 47, '7777-7777', '2025-08-25 12:28:23', NULL, 'BSIT', 'ComLab1', '', NULL),
(300, 78, '0000-0001', '2025-09-10 09:45:31', NULL, 'BSIT', 'ComLab2', '', NULL),
(301, 77, '2024-1697', '2025-09-10 09:45:47', NULL, 'BSIT', 'ComLab2', '', NULL),
(302, 75, '2024-1570', '2025-09-10 09:46:05', NULL, 'BSIT', 'ComLab2', '', NULL),
(303, 77, '2024-1697', '2025-09-10 10:10:59', NULL, 'BSIT', 'ComLab2', '', NULL),
(304, 77, '2024-1697', '2025-09-10 10:18:05', NULL, 'BSIT', 'ComLab2', '', NULL),
(305, 77, '2024-1697', '2025-09-10 10:19:32', NULL, 'BSIT', 'ComLab2', '', NULL),
(308, 75, '2024-1570', '2025-09-12 07:02:34', '2025-09-12 07:03:30', 'BSIT', 'ComLab2', '', NULL),
(310, 75, '2024-1570', '2025-09-12 12:37:39', '2025-09-12 12:37:39', 'BSIT', 'ComLab2', '', NULL),
(311, 75, '2024-1570', '2025-09-12 12:38:15', '2025-09-12 12:38:15', 'BSIT', 'ComLab2', '', NULL),
(312, 75, '2024-1570', '2025-09-12 12:38:52', '2025-09-12 12:38:52', 'BSIT', 'ComLab2', '', NULL),
(313, 75, '2024-1570', '2025-09-12 12:39:13', '2025-09-12 12:39:13', 'BSIT', 'ComLab2', '', NULL),
(314, 75, '2024-1570', '2025-09-12 15:29:14', NULL, 'BSIT', 'ComLab2', '', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `archived_instructor_logs`
--

CREATE TABLE `archived_instructor_logs` (
  `id` int(11) NOT NULL,
  `instructor_id` int(11) DEFAULT NULL,
  `id_number` varchar(20) DEFAULT NULL,
  `time_in` datetime DEFAULT NULL,
  `time_out` datetime DEFAULT NULL,
  `department` varchar(100) DEFAULT NULL,
  `location` varchar(100) DEFAULT NULL,
  `status` varchar(20) DEFAULT NULL,
  `swapped_with` varchar(255) NOT NULL,
  `swap_time` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `archived_instructor_logs`
--

INSERT INTO `archived_instructor_logs` (`id`, `instructor_id`, `id_number`, `time_in`, `time_out`, `department`, `location`, `status`, `swapped_with`, `swap_time`) VALUES
(56, 16, '2024-0117', '2025-08-06 16:06:23', '2025-08-06 16:06:35', 'BSIT', 'ComLab1', 'saved', '', '2025-09-10 02:18:57'),
(57, 16, '2024-0117', '2025-08-06 16:06:38', NULL, 'BSIT', 'ComLab1', NULL, '', '2025-09-10 02:18:57'),
(58, 11, '0001-0001', '2025-08-06 19:03:48', '2025-08-06 19:09:01', 'BSIT', 'ComLab1', 'saved', '', '2025-09-10 02:18:57'),
(59, 11, '0001-0001', '2025-08-06 19:09:04', '2025-08-06 19:16:16', 'BSIT', 'ComLab1', 'saved', '', '2025-09-10 02:18:57'),
(60, 11, '0001-0001', '2025-08-06 19:16:19', '2025-08-06 19:33:10', 'BSIT', 'ComLab1', 'saved', '', '2025-09-10 02:18:57'),
(61, 11, '0001-0001', '2025-08-06 19:33:14', '2025-08-06 19:41:23', 'BSIT', 'ComLab1', 'saved', '', '2025-09-10 02:18:57'),
(62, 11, '0001-0001', '2025-08-06 19:41:26', '2025-08-06 19:44:44', 'BSIT', 'ComLab1', 'saved', '', '2025-09-10 02:18:57'),
(66, 16, '2024-0117', '2025-08-09 11:45:53', '2025-08-09 11:52:52', 'BSIT', 'ComLab1', 'saved', '', '2025-09-10 02:18:57'),
(68, 16, '2024-0117', '2025-08-12 16:10:31', '2025-08-12 16:58:58', 'BSIT', 'ComLab2', 'saved', '', '2025-09-10 02:18:57'),
(69, 16, '2024-0117', '2025-08-12 16:59:01', '2025-08-12 17:17:58', 'BSIT', 'ComLab1', 'saved', '', '2025-09-10 02:18:57'),
(71, 16, '2024-0117', '2025-08-15 14:51:30', '2025-08-15 15:38:42', 'BSIT', 'ComLab1', 'saved', '', '2025-09-10 02:18:57'),
(72, 16, '2024-0117', '2025-08-15 15:38:45', '2025-08-15 15:46:26', 'BSIT', 'ComLab1', 'saved', '', '2025-09-10 02:18:57'),
(73, 16, '2024-0117', '2025-08-15 15:47:19', '2025-08-15 15:49:08', 'BSIT', 'ComLab1', 'saved', '', '2025-09-10 02:18:57'),
(74, 16, '2024-0117', '2025-08-15 15:49:11', '2025-08-15 15:58:12', 'BSIT', 'ComLab1', 'saved', '', '2025-09-10 02:18:57'),
(75, 16, '2024-0117', '2025-08-15 16:13:30', '2025-08-15 16:30:21', 'BSIT', 'ComLab1', 'saved', '', '2025-09-10 02:18:57'),
(76, 16, '2024-0117', '2025-08-15 16:30:23', '2025-08-15 16:32:00', 'BSIT', 'ComLab1', 'saved', '', '2025-09-10 02:18:57'),
(78, 16, '2024-0117', '2025-08-16 13:39:45', '2025-08-16 13:48:27', 'BSIT', 'ComLab2', 'saved', '', '2025-09-10 02:18:57'),
(79, 16, '2024-0117', '2025-08-16 13:48:30', '2025-08-16 13:52:05', 'BSIT', 'ComLab2', 'saved', '', '2025-09-10 02:18:57'),
(82, 16, '2024-0117', '2025-08-17 10:59:27', '2025-08-17 10:59:32', 'BSIT', 'ComLab2', 'saved', '', '2025-09-10 02:18:57'),
(90, 16, '2024-0117', '2025-09-10 09:34:58', '2025-09-10 10:07:56', 'BSIT', 'ComLab2', 'saved', '', '2025-09-10 02:19:39'),
(93, 16, '2024-0117', '2025-09-12 07:03:20', '2025-09-12 07:03:40', 'BSIT', 'ComLab2', 'saved', '', '2025-09-11 23:03:40'),
(94, 16, '2024-0117', '2025-09-12 07:03:40', '2025-09-12 12:29:22', 'BSIT', 'ComLab2', 'saved', '', '2025-09-12 04:29:23'),
(95, 16, '2024-0117', '2025-09-12 12:29:23', '2025-09-12 12:30:07', 'BSIT', 'ComLab2', 'saved', '', '2025-09-12 04:30:07'),
(96, 16, '2024-0117', '2025-09-12 12:30:07', '2025-09-12 12:30:16', 'BSIT', 'ComLab2', 'saved', '', '2025-09-12 04:30:16'),
(97, 16, '2024-0117', '2025-09-12 12:30:16', '2025-09-12 12:37:49', 'BSIT', 'ComLab2', 'saved', '', '2025-09-12 04:37:49'),
(98, 16, '2024-0117', '2025-09-12 12:37:49', '2025-09-12 12:37:54', 'BSIT', 'ComLab2', 'saved', '', '2025-09-12 04:37:54'),
(99, 16, '2024-0117', '2025-09-12 12:37:54', '2025-09-12 12:38:26', 'BSIT', 'ComLab2', 'saved', '', '2025-09-12 04:38:26'),
(100, 16, '2024-0117', '2025-09-12 12:38:26', '2025-09-12 12:38:58', 'BSIT', 'ComLab2', 'saved', '', '2025-09-12 04:38:58'),
(101, 16, '2024-0117', '2025-09-12 12:38:58', '2025-09-12 12:39:18', 'BSIT', 'ComLab2', 'saved', '', '2025-09-12 04:39:18'),
(102, 16, '2024-0117', '2025-09-12 12:39:18', '2025-09-12 15:29:55', 'BSIT', 'ComLab2', 'saved', '', '2025-09-12 07:29:55');

-- --------------------------------------------------------

--
-- Table structure for table `attendance_logs`
--

CREATE TABLE `attendance_logs` (
  `id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `id_number` varchar(50) NOT NULL,
  `time_in` datetime NOT NULL,
  `time_out` datetime DEFAULT NULL,
  `department` varchar(255) NOT NULL,
  `location` varchar(255) NOT NULL,
  `instructor_id` varchar(9) NOT NULL,
  `status` varchar(20) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `department`
--

CREATE TABLE `department` (
  `department_id` int(11) NOT NULL,
  `department_name` varchar(50) NOT NULL,
  `department_desc` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `department`
--

INSERT INTO `department` (`department_id`, `department_name`, `department_desc`) VALUES
(33, 'BSIT', 'Bachelor in Science and Information Technology'),
(56, 'BSBA', 'Bachelor in Science and Business Administration'),
(60, 'BSHRM', 'Bachelor of Science in Hotel and Restaurant Manage'),
(62, 'BEED', 'Bachelor of Elementary Education'),
(63, 'BSED', 'Bachelor of Secondary Education'),
(164, 'BSCE', 'Bachelor of Science in Civil Engineering'),
(165, 'Main', 'adada');

-- --------------------------------------------------------

--
-- Table structure for table `gate_logs`
--

CREATE TABLE `gate_logs` (
  `id` int(11) NOT NULL,
  `person_type` enum('student','instructor','personell','visitor') NOT NULL,
  `person_id` int(11) NOT NULL,
  `id_number` varchar(50) NOT NULL,
  `name` varchar(255) NOT NULL,
  `action` enum('IN','OUT') NOT NULL,
  `time` time NOT NULL,
  `date` date NOT NULL,
  `location` varchar(100) NOT NULL,
  `department` varchar(100) NOT NULL,
  `photo` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `instructor`
--

CREATE TABLE `instructor` (
  `id` int(11) NOT NULL,
  `fullname` varchar(50) NOT NULL,
  `id_number` varchar(9) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `department_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `instructor`
--

INSERT INTO `instructor` (`id`, `fullname`, `id_number`, `created_at`, `updated_at`, `department_id`) VALUES
(11, 'Mr.Kurt Alegre', '0001-0001', '2025-06-28 11:52:22', '2025-07-14 22:16:12', 33),
(12, 'Mr.Alvin Billiones', '0001-0004', '2025-07-08 08:26:38', '2025-07-14 22:19:33', 33),
(13, 'Mr.Danilo Villariono', '0001-0003', '2025-07-09 00:38:05', '2025-07-14 22:18:53', 33),
(16, 'Ms.Jessica Alcazar', '2024-0117', '2025-07-14 22:18:35', '2025-07-14 22:47:34', 33),
(17, 'Mr.Richard Bracero', '0001-0005', '2025-07-14 22:20:02', '2025-07-14 22:20:02', 33),
(18, 'Mrs.Emily Forrosuelo', '0001-0006', '2025-07-14 22:20:39', '2025-07-14 22:20:39', 33),
(19, 'Mr.GlennFord Buncal', '0001-0007', '2025-07-14 22:21:14', '2025-07-14 22:21:14', 33);

-- --------------------------------------------------------

--
-- Table structure for table `instructor_accounts`
--

CREATE TABLE `instructor_accounts` (
  `id` int(11) NOT NULL,
  `instructor_id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `last_login` varchar(11) NOT NULL,
  `department` varchar(55) NOT NULL,
  `fullname` varchar(55) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `instructor_accounts`
--

INSERT INTO `instructor_accounts` (`id`, `instructor_id`, `username`, `password`, `created_at`, `updated_at`, `last_login`, `department`, `fullname`) VALUES
(1, 13, 'Danilo', '$2y$10$0oqoE/tgXBvzmz.WcZ0Dpe3E7QdrBlrAqxHIAWmXG/mqC46GVgJyO', '2025-09-08 08:26:12', '2025-09-11 23:29:24', '2025-09-09 ', 'BSIT', 'Mr. Danilo Villariono'),
(2, 16, 'jessica', '$2y$10$5WTQH1ItwPa8PT8Dq3MLPuWwkQEbfYoAK5R9wWqU2KeLsyOl/QA0i', '2025-09-11 23:12:18', '2025-09-11 23:29:52', '', 'BSIT', 'Ms.Jessica Alcazar'),
(3, 12, 'alvin', '$2y$10$bxLgIrb/Y216/EbgHWGyFuT9OBEWMwpXQ5ZrWmMrRH71fDaOsmWjq', '2025-09-11 23:36:14', '2025-09-11 23:36:14', '', '', '');

-- --------------------------------------------------------

--
-- Table structure for table `instructor_attendance`
--

CREATE TABLE `instructor_attendance` (
  `id` int(11) NOT NULL,
  `date` date NOT NULL,
  `instructor_name` varchar(50) NOT NULL,
  `instructor_id` int(11) NOT NULL,
  `time_in` datetime NOT NULL,
  `time_out` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `instructor_attendance`
--

INSERT INTO `instructor_attendance` (`id`, `date`, `instructor_name`, `instructor_id`, `time_in`, `time_out`) VALUES
(1, '2025-07-26', 'Ms.Jessica Alcazar', 2024, '2025-07-26 17:25:34', '2025-07-26 17:25:34');

-- --------------------------------------------------------

--
-- Table structure for table `instructor_glogs`
--

CREATE TABLE `instructor_glogs` (
  `id` int(11) NOT NULL,
  `instructor_id` int(11) NOT NULL,
  `id_number` varchar(50) NOT NULL,
  `name` varchar(255) NOT NULL,
  `action` enum('IN','OUT') NOT NULL,
  `time` time NOT NULL,
  `date` date NOT NULL,
  `period` enum('AM','PM') NOT NULL,
  `location` varchar(100) NOT NULL,
  `department` varchar(100) NOT NULL,
  `photo` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `instructor_logs`
--

CREATE TABLE `instructor_logs` (
  `id` int(11) NOT NULL,
  `instructor_id` int(11) DEFAULT NULL,
  `id_number` varchar(20) DEFAULT NULL,
  `time_in` datetime DEFAULT NULL,
  `time_out` datetime DEFAULT NULL,
  `swapped_with` int(11) DEFAULT NULL,
  `swap_time` datetime DEFAULT NULL,
  `department` varchar(100) DEFAULT NULL,
  `location` varchar(100) DEFAULT NULL,
  `status` varchar(20) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `instructor_logs`
--

INSERT INTO `instructor_logs` (`id`, `instructor_id`, `id_number`, `time_in`, `time_out`, `swapped_with`, `swap_time`, `department`, `location`, `status`) VALUES
(38, 16, '2024-0117', '2025-08-03 14:31:21', NULL, NULL, NULL, 'BSIT', 'ComLab2', NULL),
(47, 11, '0001-0001', '2025-08-05 11:41:38', NULL, NULL, NULL, 'BSIT', 'IT-LEC1', NULL),
(63, 11, '0001-0001', '2025-08-06 19:44:47', NULL, NULL, NULL, 'BSIT', 'ComLab1', NULL),
(64, 16, '2024-0117', '2025-08-06 19:45:50', NULL, NULL, NULL, 'BSIT', 'ComLab1', NULL),
(65, 16, '2024-0117', '2025-08-07 06:35:59', NULL, NULL, NULL, 'BSIT', 'ComLab1', NULL),
(67, 16, '2024-0117', '2025-08-09 11:52:59', NULL, NULL, NULL, 'BSIT', 'ComLab1', NULL),
(70, 16, '2024-0117', '2025-08-12 17:18:29', NULL, NULL, NULL, 'BSIT', 'ComLab1', NULL),
(77, 16, '2024-0117', '2025-08-15 16:32:02', NULL, NULL, NULL, 'BSIT', 'ComLab1', NULL),
(80, 16, '2024-0117', '2025-08-16 13:52:09', NULL, NULL, NULL, 'BSIT', 'ComLab2', NULL),
(81, 12, '0001-0004', '2025-08-16 13:56:14', NULL, NULL, NULL, 'BSIT', 'ComLab2', NULL),
(83, 16, '2024-0117', '2025-08-17 10:59:36', NULL, NULL, NULL, 'BSIT', 'ComLab2', NULL),
(84, 16, '2024-0117', '2025-08-19 11:47:01', '2025-08-19 11:47:10', NULL, NULL, 'BSIT', 'ComLab1', 'saved'),
(85, 16, '2024-0117', '2025-08-22 11:08:01', '2025-08-22 11:08:13', NULL, NULL, 'BSIT', 'ComLab2', 'saved'),
(86, 16, '2024-0117', '2025-08-25 11:26:08', '2025-08-25 11:26:13', NULL, NULL, 'BSIT', 'ComLab2', 'saved'),
(87, 16, '2024-0117', '2025-09-02 20:18:24', '2025-09-02 22:59:08', NULL, NULL, 'BSIT', 'ComLab3', 'saved'),
(88, 16, '2024-0117', '2025-09-03 00:27:39', '2025-09-03 09:19:52', NULL, NULL, 'BSIT', 'ComLab3', 'saved'),
(89, 12, '0001-0004', '2025-09-03 12:54:57', '2025-09-03 12:55:01', NULL, NULL, 'BSIT', 'ComLab2', 'saved'),
(91, 16, '2024-0117', '2025-09-10 10:19:39', NULL, NULL, NULL, 'BSIT', 'ComLab2', NULL),
(92, 16, '2024-0117', '2025-09-11 00:40:27', NULL, NULL, NULL, 'BSIT', 'ComLab2', NULL),
(103, 16, '2024-0117', '2025-09-12 15:29:56', NULL, NULL, NULL, 'BSIT', 'ComLab2', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `lostcard`
--

CREATE TABLE `lostcard` (
  `id` int(11) NOT NULL,
  `personnel_id` int(11) NOT NULL,
  `date_requested` datetime NOT NULL,
  `status` int(1) NOT NULL,
  `verification_photo` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `lost_found`
--

CREATE TABLE `lost_found` (
  `id` int(11) NOT NULL,
  `sender` varchar(255) NOT NULL,
  `status` int(11) NOT NULL,
  `department` varchar(255) NOT NULL,
  `name` varchar(255) NOT NULL,
  `date` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `personell`
--

CREATE TABLE `personell` (
  `id` int(11) NOT NULL,
  `id_no` varchar(255) NOT NULL,
  `rfid_number` varchar(10) NOT NULL,
  `last_name` varchar(255) NOT NULL,
  `first_name` varchar(255) NOT NULL,
  `middle_name` varchar(255) DEFAULT NULL,
  `date_of_birth` date NOT NULL,
  `role` varchar(255) NOT NULL,
  `sex` varchar(10) NOT NULL,
  `civil_status` varchar(10) NOT NULL,
  `contact_number` varchar(11) DEFAULT NULL,
  `email_address` varchar(255) DEFAULT NULL,
  `department` varchar(255) NOT NULL,
  `section` varchar(255) NOT NULL,
  `status` varchar(255) NOT NULL,
  `complete_address` varchar(255) NOT NULL,
  `photo` varchar(255) DEFAULT NULL,
  `place_of_birth` varchar(50) NOT NULL,
  `category` varchar(255) DEFAULT NULL,
  `date_added` timestamp NULL DEFAULT current_timestamp(),
  `deleted` int(11) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `personell`
--

INSERT INTO `personell` (`id`, `id_no`, `rfid_number`, `last_name`, `first_name`, `middle_name`, `date_of_birth`, `role`, `sex`, `civil_status`, `contact_number`, `email_address`, `department`, `section`, `status`, `complete_address`, `photo`, `place_of_birth`, `category`, `date_added`, `deleted`) VALUES
(68, '', '22222222', 'Rity', 'Secu', NULL, '1976-02-19', 'Security Personnel', '', '', NULL, NULL, 'BSIT', '', 'Active', '', '68c375d5026eb.png', '', 'Regular', '2025-09-12 01:22:29', 0),
(69, '', '11111111', 'Bantay', 'Tig', NULL, '1986-03-20', 'Security Personnel', '', '', NULL, NULL, 'BSIT', '', 'Active', '', '68c37551339fa.png', '', 'Regular', '2025-09-12 01:20:17', 0);

-- --------------------------------------------------------

--
-- Table structure for table `personell_logs`
--

CREATE TABLE `personell_logs` (
  `id` int(11) NOT NULL,
  `photo` varchar(255) NOT NULL,
  `rfid_number` varchar(50) NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `time_in_am` varchar(100) NOT NULL,
  `time_out_am` varchar(100) NOT NULL,
  `date_logged` date NOT NULL,
  `time_in_pm` varchar(100) NOT NULL,
  `time_out_pm` varchar(100) NOT NULL,
  `role` varchar(50) NOT NULL,
  `department` varchar(100) NOT NULL,
  `status` varchar(100) NOT NULL,
  `time_in` varchar(255) DEFAULT NULL,
  `time_out` varchar(255) DEFAULT NULL,
  `location` varchar(255) DEFAULT NULL,
  `personnel_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `personnel_glogs`
--

CREATE TABLE `personnel_glogs` (
  `id` int(11) NOT NULL,
  `personnel_id` int(11) NOT NULL,
  `id_number` varchar(50) NOT NULL,
  `name` varchar(255) NOT NULL,
  `action` enum('IN','OUT') NOT NULL,
  `time` time NOT NULL,
  `date` date NOT NULL,
  `period` enum('AM','PM') NOT NULL,
  `location` varchar(100) NOT NULL,
  `department` varchar(100) NOT NULL,
  `photo` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `role`
--

CREATE TABLE `role` (
  `id` int(11) NOT NULL,
  `role` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `role`
--

INSERT INTO `role` (`id`, `role`) VALUES
(5, 'Instructor'),
(6, 'Security Personnel'),
(8, 'Staff'),
(20, 'Logistics'),
(21, 'Director'),
(23, 'Executive'),
(24, 'HR'),
(25, 'Instructor'),
(26, 'HR'),
(27, 'Data Analyst'),
(28, 'Security'),
(30, 'Instructor'),
(31, 'Maintenance'),
(32, 'Developer'),
(34, 'Designer'),
(35, 'Quality Assurance'),
(37, 'Designer'),
(39, 'Developer'),
(40, 'Facilities'),
(41, 'Admin'),
(99, 'Administrator'),
(100, 'Service Manager'),
(101, 'Purchasing Officer'),
(102, 'Accountant'),
(103, 'Analyst'),
(106, 'Director'),
(107, 'Manager'),
(110, 'Clerk'),
(111, 'Clerk'),
(113, 'Operator'),
(114, 'Customer Service'),
(119, 'jkkjjkkj'),
(125, 'Cutter'),
(129, 'bubu'),
(136, '1111');

-- --------------------------------------------------------

--
-- Table structure for table `rooms`
--

CREATE TABLE `rooms` (
  `id` int(11) NOT NULL,
  `room` varchar(255) NOT NULL,
  `department` varchar(255) NOT NULL,
  `password` varchar(255) DEFAULT NULL,
  `desc` varchar(255) DEFAULT NULL,
  `descr` varchar(255) DEFAULT NULL,
  `authorized_personnel` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `rooms`
--

INSERT INTO `rooms` (`id`, `room`, `department`, `password`, `desc`, `descr`, `authorized_personnel`) VALUES
(150, 'BSHM 01', 'BSHRM', 'BSHM01', NULL, 'Kitchen Lab', 'Instructor'),
(151, 'ComLab2', 'BSIT', 'comlab2', NULL, 'IT lab1', 'Instructor'),
(152, 'ComLab1', 'BSIT', 'comlab1', NULL, 'comlab1', 'Instructor'),
(153, 'Gate', 'Main', 'gate123', NULL, 'gilugewqe', 'Instructor'),
(154, 'ComLab3', 'BSIT', 'comlab3', NULL, 'IT lab 3', 'Instructor'),
(155, 'IT-LEC1', 'BSIT', 'itlec1', NULL, 'IT LECTURE 1', 'Instructor'),
(156, 'IT-LEC2', 'BSIT', 'itlec2', NULL, 'IT Lecture 2', 'Instructor');

-- --------------------------------------------------------

--
-- Table structure for table `room_logs`
--

CREATE TABLE `room_logs` (
  `id` int(11) NOT NULL,
  `date_logged` varchar(255) NOT NULL,
  `location` varchar(255) NOT NULL,
  `time_in` varchar(255) NOT NULL,
  `time_out` varchar(255) NOT NULL,
  `personnel_id` int(11) DEFAULT NULL,
  `log_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `room_schedules`
--

CREATE TABLE `room_schedules` (
  `department` varchar(5155) NOT NULL,
  `id` int(11) NOT NULL,
  `room_name` varchar(100) DEFAULT NULL,
  `room_location` varchar(255) DEFAULT NULL,
  `room_password` varchar(255) DEFAULT NULL,
  `subject` varchar(100) DEFAULT NULL,
  `section` varchar(50) DEFAULT NULL,
  `year_level` varchar(20) DEFAULT NULL,
  `start_time` time DEFAULT NULL,
  `end_time` time DEFAULT NULL,
  `day` varchar(20) DEFAULT NULL,
  `instructor` varchar(58) NOT NULL,
  `instructor_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `room_schedules`
--

INSERT INTO `room_schedules` (`department`, `id`, `room_name`, `room_location`, `room_password`, `subject`, `section`, `year_level`, `start_time`, `end_time`, `day`, `instructor`, `instructor_id`) VALUES
('BSIT', 13, 'ComLab1', NULL, NULL, 'Networking', 'West', '1st', '11:31:00', '13:47:00', 'Friday', 'Mr.Danilo Villariono', 13),
('BSIT', 14, 'ComLab1', NULL, NULL, 'Web Development', 'West', '4th', '09:20:00', '11:20:00', 'Monday', 'Mr.Alvin Billiones', 12),
('BSIT', 15, 'ComLab1', NULL, NULL, 'Sub1', 'west', '4th', '09:00:00', '10:30:00', 'Tuesday', 'Ms.Jessica Alcazar', 16),
('BSIT', 16, 'ComLab1', NULL, NULL, 'Sub2', 'west', '4th', '13:00:00', '14:30:00', 'Tuesday', 'Ms.Jessica Alcazar', 16),
('BSIT', 17, 'ComLab1', NULL, NULL, 'Sub3', 'West', '4th', '15:10:00', '16:15:00', 'Tuesday', 'Ms.Jessica Alcazar', 16),
('BSIT', 18, 'ComLab1', NULL, NULL, 'Sub4', 'West', '4th', '17:20:00', '19:15:00', 'Tuesday', 'Ms.Jessica Alcazar', 16),
('BSIT', 20, 'ComLab2', NULL, NULL, 'Web', 'west', '4th', '13:40:00', '15:40:00', 'Tuesday', 'Ms.Jessica Alcazar', 16),
('BSIT', 21, 'IT-LEC1', NULL, NULL, 'Sub1', 'West', '4th', '11:40:00', '02:40:00', 'Monday', 'Mr.Kurt Alegre', 11),
('BSIT', 22, 'ComLab1', NULL, NULL, 'SUB2-01', 'West', '2nd Year', '00:00:00', '15:00:00', 'Wednesday', 'Mr.Kurt Alegre', 11),
('BSIT', 23, 'IT-LEC1', NULL, NULL, 'SUB1', 'West', '1st Year', '13:20:00', '15:20:00', 'Friday', 'Mr.Kurt Alegre', 11),
('BSIT', 24, 'ComLab2', NULL, NULL, 'SUB4-01', 'West', '4th Year', '14:00:00', '15:00:00', 'Saturday', 'Ms.Jessica Alcazar', 16),
('BSIT', 25, 'ComLab2', NULL, NULL, 'ITE PROF ELECT 4', 'East', '4th Year', '16:00:00', '17:00:00', 'Saturday', 'Mr.Alvin Billiones', 12),
('BSIT', 26, 'ComLab2', NULL, NULL, 'Philippine Popular Culture', 'West', '2nd Year', '00:17:00', '13:11:00', 'Monday', 'Mr.Alvin Billiones', NULL),
('BSIT', 27, 'ComLab1', NULL, NULL, 'Program Logic Formulation', 'West', '1st Year', '18:15:00', '19:15:00', 'Monday', 'Ms.Jessica Alcazar', NULL),
('BSIT', 28, 'ComLab2', NULL, NULL, 'Computer Assembly, Trblshting & Repair', 'West', '1st Year', '18:16:00', '18:16:00', 'Tuesday', 'Ms.Jessica Alcazar', NULL),
('BSIT', 29, 'ComLab3', NULL, NULL, 'Program Logic Formulation', 'West', '1st Year', '20:18:00', '23:18:00', 'Wednesday', 'Ms.Jessica Alcazar', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `stranger_logs`
--

CREATE TABLE `stranger_logs` (
  `id` int(11) NOT NULL,
  `attempts` int(11) NOT NULL,
  `last_log` varchar(255) DEFAULT NULL,
  `rfid_number` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `stranger_logs`
--

INSERT INTO `stranger_logs` (`id`, `attempts`, `last_log`, `rfid_number`) VALUES
(1, 4, '2024-11-20', '123'),
(2, 1, '2024-10-18', 'dfdfdfd'),
(3, 1, '2024-10-18', 'kadmas'),
(4, 1, '2024-10-20', '0009693526	'),
(5, 1, '2024-11-29', '0009646720'),
(6, 1, '2024-12-11', '0009669869	'),
(7, 1, '2024-12-11', '245635678956'),
(8, 1, '2024-12-11', '2545667747'),
(9, 1, '2024-12-11', '3566799'),
(10, 1, '2024-12-13', '00096935250009693525'),
(11, 2, '2025-09-07', '7777-7888'),
(12, 1, '2025-09-07', '5345'),
(13, 1, '2025-09-07', '213213');

-- --------------------------------------------------------

--
-- Table structure for table `students`
--

CREATE TABLE `students` (
  `id` int(11) NOT NULL,
  `id_number` varchar(9) NOT NULL,
  `fullname` varchar(100) NOT NULL,
  `section` varchar(50) NOT NULL,
  `year` varchar(20) NOT NULL,
  `status` varchar(10) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `department_id` varchar(144) NOT NULL,
  `photo` varchar(25) NOT NULL,
  `date_added` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `students`
--

INSERT INTO `students` (`id`, `id_number`, `fullname`, `section`, `year`, `status`, `created_at`, `updated_at`, `department_id`, `photo`, `date_added`) VALUES
(75, '2024-1570', 'John Cyrus Pescante', 'West', '1st Year', '', '2025-09-03 03:40:12', '2025-09-03 04:53:21', '33', '68b7b89cc0097_2024-1570.j', '2025-09-03 03:40:12'),
(77, '2024-1697', 'Rose Ann V. Forrosuelo', 'West', '1st Year', '', '2025-09-03 03:41:47', '2025-09-03 04:53:08', '33', '68b7b8fb407f5_2024-1697.j', '2025-09-03 03:41:47'),
(78, '0000-0001', 'Truy', 'West', '1st Year', '', '2025-09-10 01:45:14', '2025-09-10 01:45:14', '33', '68c0d82a10aff_0000-0001.p', '2025-09-10 01:45:14');

-- --------------------------------------------------------

--
-- Table structure for table `students_glogs`
--

CREATE TABLE `students_glogs` (
  `id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `id_number` varchar(50) NOT NULL,
  `name` varchar(255) NOT NULL,
  `action` enum('IN','OUT') NOT NULL,
  `time` time NOT NULL,
  `date` date NOT NULL,
  `period` enum('AM','PM') NOT NULL,
  `location` varchar(100) NOT NULL,
  `department` varchar(100) NOT NULL,
  `photo` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `subjects`
--

CREATE TABLE `subjects` (
  `id` int(11) NOT NULL,
  `subject_code` varchar(50) NOT NULL,
  `subject_name` varchar(255) NOT NULL,
  `year_level` varchar(20) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `subjects`
--

INSERT INTO `subjects` (`id`, `subject_code`, `subject_name`, `year_level`) VALUES
(1, '111', 'SUB1', '1st Year'),
(2, '444', 'SUB4-01', '4th Year'),
(3, '333', 'SUB3-01', '3rd Year'),
(4, '222', 'SUB2-01', '2nd Year'),
(5, 'PE 1', 'Movement Enhancement', '1st Year'),
(6, 'NSTP 1', 'National Service Training Program 1', '1st Year'),
(7, 'FIL110', 'Komunikasyon sa Akademikong Filipino', '1st Year'),
(8, 'GE ELECT1', 'People and The Earths Ecosystem', '1st Year'),
(9, 'MATH 110', 'Mathematics in the Modern Science', '1st Year'),
(10, 'LIT 110', 'Contemporary World', '1st Year'),
(11, 'ITE112', 'Program Logic Formulation', '1st Year'),
(12, 'ITE111', 'Computer Assembly, Trblshting & Repair', '1st Year'),
(13, 'ITE113', 'Introduction to Computing', '1st Year'),
(14, 'BRIDGING1', 'Precalculus', '1st Year'),
(15, 'BRIDGING2', 'General Biology', '1st Year'),
(16, 'PATHFit3', 'Physical Activity towards Health and Fitness', '2nd Year'),
(17, 'GE ELECT2', 'Philippine Popular Culture', '2nd Year'),
(18, 'IT ELECT1', 'Fundamentals of Accounting', '2nd Year'),
(19, 'ITE215', 'Platform Technologies', '2nd Year'),
(20, 'ITE214', 'Digital Logic Design (Workshop 1)', '2nd Year'),
(21, 'ITE212', 'Graphic Designing', '2nd Year'),
(22, 'ITE211', 'Computer Programming 2', '2nd Year'),
(23, 'ITE213', 'Information Management', '2nd Year'),
(24, 'ITE311', 'Information Management', '3rd Year'),
(25, 'ITE310', 'Platform Technologies', '3rd Year'),
(26, 'ITE312', 'System Integration and Architecture', '3rd Year'),
(27, 'MATH310', 'Linear Algebra', '3rd Year'),
(28, 'AH310', 'Philippine Popular Culture', '3rd Year'),
(29, 'ENGL310', 'Speech Improvement', '3rd Year'),
(30, 'ITE410', 'ITE PROF ELECT 4', '4th Year'),
(31, 'ITE411', 'Information Assurance and Security 2', '4th Year'),
(32, 'ITE412', 'Capstone Project 2', '4th Year');

-- --------------------------------------------------------

--
-- Table structure for table `swap_requests`
--

CREATE TABLE `swap_requests` (
  `id` int(11) NOT NULL,
  `requester_id` varchar(20) NOT NULL,
  `target_id` varchar(20) NOT NULL,
  `room` varchar(50) NOT NULL,
  `department` varchar(50) NOT NULL,
  `subject` varchar(100) DEFAULT NULL,
  `request_date` datetime NOT NULL DEFAULT current_timestamp(),
  `response_date` datetime DEFAULT NULL,
  `status` enum('pending','approved','rejected','cancelled') NOT NULL DEFAULT 'pending',
  `notes` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `user`
--

CREATE TABLE `user` (
  `id` int(11) NOT NULL,
  `contact` varchar(15) NOT NULL,
  `email` varchar(255) NOT NULL,
  `username` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `rfid_number` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `user`
--

INSERT INTO `user` (`id`, `contact`, `email`, `username`, `password`, `rfid_number`) VALUES
(2, '09560379350', 'kyebejeanu@gmail.com', 'admin', '$2y$10$jcAd4HtKBXyVxRRGNf39sOmX6FzsDb4hOcu6DGRnISwPGNSs6YM4.', '1234567899'),
(23, '09483733246', 'try@gmail.com', 'admin', '$2y$10$SVcfbC0I/jGhtwrU8ajneeKdEAPv7QpStuWCXXJeG7.r2ZrK5SOwa', '9876543211'),
(20240331, '', 'kyebejeanungon@gmail.com', 'rfidgpms', '$2y$10$NOm2di8hyRuWXbopq10XTunGgHflPyjE.g//WGND0hmuW/MBIIXb.', '1122334455');

-- --------------------------------------------------------

--
-- Table structure for table `visitor`
--

CREATE TABLE `visitor` (
  `id` int(11) NOT NULL,
  `name` varchar(50) NOT NULL,
  `department` varchar(50) NOT NULL,
  `contact_number` varchar(100) NOT NULL,
  `address` varchar(100) NOT NULL,
  `purpose` varchar(225) NOT NULL,
  `sex` varchar(11) NOT NULL,
  `photo` varchar(225) NOT NULL,
  `civil_status` varchar(100) NOT NULL,
  `rfid_number` varchar(100) NOT NULL,
  `v_code` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `visitor`
--

INSERT INTO `visitor` (`id`, `name`, `department`, `contact_number`, `address`, `purpose`, `sex`, `photo`, `civil_status`, `rfid_number`, `v_code`) VALUES
(2, '', '', '', '', '', '', '', '', '0009669869', 'Visitor002'),
(13, '', '', '', '', '', '', '', '', '1111111111', 'sdgsdsedfg'),
(17, '', '', '', '', '', '', '', '', '7698997991', 'sdfgf'),
(31, '', '', '', '', '', '', '', '', '0987654321', ''),
(32, '', '', '', '', '', '', '', '', '5187753692', ''),
(33, '', '', '', '', '', '', '', '', '0914780959', ''),
(34, '', '', '', '', '', '', '', '', '7486184439', ''),
(35, '', '', '', '', '', '', '', '', '1211640195', ''),
(36, '', '', '', '', '', '', '', '', '3118719031', ''),
(37, '', '', '', '', '', '', '', '', '2888167227', ''),
(38, '', '', '', '', '', '', '', '', '0206905146', ''),
(39, '', '', '', '', '', '', '', '', '7474490588', ''),
(40, '', '', '', '', '', '', '', '', '4298813407', ''),
(41, '', '', '', '', '', '', '', '', '8564213767', ''),
(44, '', '', '', '', '', '', '', '', '2884576225', ''),
(45, '', '', '', '', '', '', '', '', '1357115735', ''),
(46, '', '', '', '', '', '', '', '', '7224178482', ''),
(47, '', '', '', '', '', '', '', '', '7365324101', ''),
(48, '', '', '', '', '', '', '', '', '3153618285', ''),
(49, '', '', '', '', '', '', '', '', '0885497719', ''),
(50, '', '', '', '', '', '', '', '', '6785209688', ''),
(51, '', '', '', '', '', '', '', '', '6404604576', ''),
(52, '', '', '', '', '', '', '', '', '0413214449', ''),
(53, '', '', '', '', '', '', '', '', '0662845200', ''),
(55, '', '', '', '', '', '', '', '', '4302506668', ''),
(56, '', '', '', '', '', '', '', '', '3240643840', ''),
(57, '', '', '', '', '', '', '', '', '6604314909', ''),
(58, '', '', '', '', '', '', '', '', '2453068181', ''),
(59, '', '', '', '', '', '', '', '', '0495361030', ''),
(60, '', '', '', '', '', '', '', '', '7250421127', ''),
(62, '', '', '', '', '', '', '', '', '2533812870', ''),
(63, '', '', '', '', '', '', '', '', '4232848677', ''),
(64, '', '', '', '', '', '', '', '', '0734831735', ''),
(65, '', '', '', '', '', '', '', '', '4167105671', ''),
(66, '', '', '', '', '', '', '', '', '5215972187', ''),
(68, '', '', '', '', '', '', '', '', '1251842896', ''),
(69, '', '', '', '', '', '', '', '', '0325614706', ''),
(70, '', '', '', '', '', '', '', '', '0383030484', ''),
(72, '', '', '', '', '', '', '', '', '3681595736', ''),
(73, '', '', '', '', '', '', '', '', '4873628718', ''),
(74, '', '', '', '', '', '', '', '', '5516043504', ''),
(75, '', '', '', '', '', '', '', '', '0753734967', ''),
(76, '', '', '', '', '', '', '', '', '6096357539', ''),
(77, '', '', '', '', '', '', '', '', '1067537789', ''),
(78, '', '', '', '', '', '', '', '', '0748519543', ''),
(79, '', '', '', '', '', '', '', '', '4175833370', ''),
(80, '', '', '', '', '', '', '', '', '2408966258', ''),
(81, '', '', '', '', '', '', '', '', '5211109402', ''),
(82, '', '', '', '', '', '', '', '', '1370533925', ''),
(83, '', '', '', '', '', '', '', '', '6806531704', ''),
(84, '', '', '', '', '', '', '', '', '3875995871', ''),
(85, '', '', '', '', '', '', '', '', '2785011108', ''),
(86, '', '', '', '', '', '', '', '', '2343243423', ''),
(87, '', '', '', '', '', '', '', '', '4064709907', ''),
(88, '', '', '', '', '', '', '', '', '3412143639', ''),
(89, '', '', '', '', '', '', '', '', '1174443772', ''),
(90, '', '', '', '', '', '', '', '', '1679070822', ''),
(91, '', '', '', '', '', '', '', '', '0069598353', ''),
(92, '', '', '', '', '', '', '', '', '7666888684', ''),
(93, '', '', '', '', '', '', '', '', '4779904125', ''),
(94, '', '', '', '', '', '', '', '', '5601601294', ''),
(95, '', '', '', '', '', '', '', '', '2635021670', ''),
(96, '', '', '', '', '', '', '', '', '3291285548', ''),
(97, '', '', '', '', '', '', '', '', '7039444845', ''),
(98, '', '', '', '', '', '', '', '', '0043742223', ''),
(99, '', '', '', '', '', '', '', '', '1840326393', ''),
(100, '', '', '', '', '', '', '', '', '5139538034', ''),
(101, '', '', '', '', '', '', '', '', '6246485118', ''),
(102, '', '', '', '', '', '', '', '', '0239874705', ''),
(103, '', '', '', '', '', '', '', '', '0556589801', ''),
(105, '', '', '', '', '', '', '', '', '3482548405', ''),
(106, '', '', '', '', '', '', '', '', '7493563613', ''),
(107, '', '', '', '', '', '', '', '', '2484248566', ''),
(108, '', '', '', '', '', '', '', '', '2085771360', ''),
(109, '', '', '', '', '', '', '', '', '4000655662', ''),
(110, '', '', '', '', '', '', '', '', '6271826161', ''),
(111, '', '', '', '', '', '', '', '', '6111609690', ''),
(112, '', '', '', '', '', '', '', '', '2256382014', ''),
(113, '', '', '', '', '', '', '', '', '5768050518', ''),
(115, '', '', '', '', '', '', '', '', '4639438742', ''),
(116, '', '', '', '', '', '', '', '', '4407780893', ''),
(117, '', '', '', '', '', '', '', '', '1941451470', ''),
(118, '', '', '', '', '', '', '', '', '3632650737', ''),
(119, '', '', '', '', '', '', '', '', '8568603799', ''),
(120, '', '', '', '', '', '', '', '', '3615157062', ''),
(121, '', '', '', '', '', '', '', '', '8261191217', ''),
(122, '', '', '', '', '', '', '', '', '8462915688', ''),
(123, '', '', '', '', '', '', '', '', '5119967235', ''),
(124, '', '', '', '', '', '', '', '', '5995391685', ''),
(125, '', '', '', '', '', '', '', '', '3434343434', ''),
(126, '', '', '', '', '', '', '', '', '5152297351', ''),
(127, '', '', '', '', '', '', '', '', '7218905170', ''),
(128, '', '', '', '', '', '', '', '', '1233212121', '');

-- --------------------------------------------------------

--
-- Table structure for table `visitor_glogs`
--

CREATE TABLE `visitor_glogs` (
  `id` int(11) NOT NULL,
  `visitor_id` int(11) NOT NULL,
  `id_number` varchar(50) NOT NULL,
  `name` varchar(255) NOT NULL,
  `action` enum('IN','OUT') NOT NULL,
  `time` time NOT NULL,
  `date` date NOT NULL,
  `period` enum('AM','PM') NOT NULL,
  `location` varchar(100) NOT NULL,
  `department` varchar(100) NOT NULL,
  `photo` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `visitor_logs`
--

CREATE TABLE `visitor_logs` (
  `id` int(11) NOT NULL,
  `photo` varchar(255) NOT NULL,
  `v_code` varchar(11) NOT NULL,
  `rfid_number` varchar(11) NOT NULL,
  `date_logged` date NOT NULL,
  `department` varchar(100) NOT NULL,
  `sex` varchar(11) NOT NULL,
  `contact_number` varchar(100) NOT NULL,
  `address` varchar(225) NOT NULL,
  `purpose` varchar(225) NOT NULL,
  `time_in_am` varchar(100) NOT NULL,
  `time_out_am` varchar(100) NOT NULL,
  `role` varchar(100) NOT NULL,
  `time_in_pm` varchar(50) NOT NULL,
  `time_out_pm` varchar(50) NOT NULL,
  `civil_status` varchar(100) NOT NULL,
  `name` varchar(100) NOT NULL,
  `time_in` varchar(255) DEFAULT NULL,
  `time_out` varchar(255) DEFAULT NULL,
  `location` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `visitor_logs`
--

INSERT INTO `visitor_logs` (`id`, `photo`, `v_code`, `rfid_number`, `date_logged`, `department`, `sex`, `contact_number`, `address`, `purpose`, `time_in_am`, `time_out_am`, `role`, `time_in_pm`, `time_out_pm`, `civil_status`, `name`, `time_in`, `time_out`, `location`) VALUES
(1, 'Kyebe Jean.jpeg', 'Visitor001', '0009500932', '2024-09-07', '21', 'Male', '02342352524', 'asda', 'tes', '', '', 'Visitor', '13:37:06', '', 'Single', 'Kyebe Jean', '?', '13:42:11', ''),
(2, 'Kathllen.jpeg', 'Visitor002', '0009669869', '2024-09-07', '22', 'Male', '02598249752', 'adfas', 'asdras', '', '', 'Visitor', '13:42:50', '', 'Single', 'Kathllen', '?', '13:42:59', ''),
(3, 'Test.jpeg', '75', '35647657578', '2024-09-07', '22', '', '98868998897', 'kjbkjbkj', 'kjhkho', '', '', 'Visitor', '', '', 'Married', 'Test', '14:03:45', '?', ''),
(4, 'Test.jpeg', '75', '35647657578', '2024-09-07', '22', '', '98868998897', 'kjbkjbkj', 'kjhkho', '', '', 'Visitor', '', '', 'Married', 'Test', '14:03:50', '?', ''),
(5, 'Test.jpeg', '75', '35647657578', '2024-09-07', '22', '', '98868998897', 'kjbkjbkj', 'kjhkho', '', '', 'Visitor', '', '', 'Married', 'Test', '14:03:53', '?', ''),
(6, 'Test this.jpeg', 'dsw', '3413423', '2024-09-07', '21', '', '09234342342', 'asas', 'dadasda', '', '', 'Visitor', '', '', 'Single', 'Test this', '14:12:18', '14:12:24', ''),
(7, '.jpeg', '', '', '2024-09-07', '', '', '', '', '', '', '', 'Visitor', '', '', '', '', '14:17:36', '?', ''),
(8, 'hay naku lageeeee.jpeg', '1211', '1231425323', '2024-09-07', '21', 'Male', '09342342342', 'asda', 'sfsd', '', '', 'Visitor', '', '', 'Widowed', 'hay naku lageeeee', '14:32:20', '?', ''),
(9, 'madrodejps.jpeg', 'qwert', '123456', '2024-09-07', '21', '', '72934572903', 'skdhtaklsdjf', 'oshrkawejrps', '', '', 'Visitor', '', '', 'Single', 'madrodejps', '14:35:52', '20:29:00', ''),
(10, 'try rhis.jpeg', '0987', '0980978968', '2024-09-07', '20', 'Male', '97459273942', 'sdfdf', 'asd', '', '', 'Visitor', '', '', 'Single', 'try rhis', '14:39:34', '?', ''),
(11, 'try rhis.jpeg', '0987', '0980978968', '2024-09-07', '20', 'Male', '97459273942', 'sdfdf', 'asd', '', '', 'Visitor', '', '', 'Single', 'try rhis', '14:41:54', '?', ''),
(12, 'Adik.jpeg', 'adfadfs', '235263634', '2024-09-07', '20', 'Male', '87985720934', 'kdghndfngidnf', 'ktjyiodrgmod', '', '', 'Visitor', '', '', 'Single', 'Adik', '14:42:24', '?', ''),
(13, 'Adik version 2.jpeg', '4535353', '23542536365', '2024-09-07', '21', 'Male', '25394539745', 'dhgskodhf', 'aidurfoapsdj', '', '', 'Visitor', '', '', 'Married', 'Adik version 2', '14:54:05', '15:10:33', ''),
(14, 'Adik version 2.jpeg', '4535353', '23542536365', '2024-09-07', '21', 'Male', '25394539745', 'dhgskodhf', 'aidurfoapsdj', '', '', 'Visitor', '', '', 'Married', 'Adik version 2', '14:54:14', '?', ''),
(15, 'Adik version 2.jpeg', '4535353', '23542536365', '2024-09-07', '21', 'Male', '25394539745', 'dhgskodhf', 'aidurfoapsdj', '', '', 'Visitor', '', '', 'Married', 'Adik version 2', '14:54:54', '?', ''),
(16, 'ang pangalan.jpeg', 'gjjgi', '89698798', '2024-09-07', '20', 'Male', '09342525234', 'asdfasd', 'stdf', '', '', 'Visitor', '', '', 'Married', 'ang pangalan', '14:55:34', '20:36:44', ''),
(17, 'Amo ini iya pangalaan.jpeg', '76768767868', '97907908098', '2024-09-07', '20', 'Female', '87987298347', 'oahsfkoahskla', 'aksdaksj', '', '', 'Visitor', '', '', 'Single', 'Amo ini iya pangalaan', '15:04:24', '?', ''),
(18, 'iya panagaln2.jpeg', '76768767868', '97907908098', '2024-09-07', '20', 'Male', '87658769897', 'bjklbnlk', 'okohp', '', '', 'Visitor', '', '', 'Single', 'iya panagaln2', '15:06:27', '?', ''),
(19, 'iya panagalan3.jpeg', '76768767868', '97907908098', '2024-09-07', '20', 'Male', '89869889798', 'jbkl', 'oiho', '', '', 'Visitor', '', '', 'Married', 'iya panagalan3', '15:07:19', '?', ''),
(20, 'planagn453.jpeg', '76768767868', '97907908098', '2024-09-07', '20', '', '89875290347', 'kjbkjbjkbijbi', 'hjvijgbiuhoi', '', '', 'Visitor', '', '', 'Single', 'planagn453', '15:11:59', '?', ''),
(21, 'sdngos.jpeg', '76768767868', '97907908098', '2024-09-07', '19', 'Male', '52523423423', 'sdfjaosda', 'sjdoad', '', '', 'Visitor', '', '', 'Married', 'sdngos', '15:16:48', '?', ''),
(22, 'Kyebe Jean Ungon.jpeg', 'sdgsdsedfg', '34534534534', '2024-09-07', '21', 'Female', '36345345345', 'Conception Street', 'retwe', '', '', 'Visitor', '', '', 'Married', 'Kyebe Jean Ungon', '20:17:31', '20:34:36', ''),
(23, 'Ktahe;lee.jpeg', 'gertwertwe', '234235425', '2024-09-07', '22', 'Male', '09245245231', 'sdgtasr', 'dtgwqerwe', '', '', 'Visitor', '', '', 'Married', 'Ktahe;lee', '20:21:47', '20:21:55', ''),
(24, 'cegjdghdf.jpeg', 'ertsdf', '3643564', '2024-09-07', '22', 'Male', '35634563453', 'dfas', 'adfgsdf', '', '', 'Visitor', '', '', 'Single', 'cegjdghdf', '20:35:23', '20:35:31', ''),
(25, 'eryeryer.jpeg', 'cgxsdgfsd', '78998899898', '2024-09-07', '22', 'Female', '34535345345', 'yryrty', 'sdhdt', '', '', 'Visitor', '', '', 'Married', 'eryeryer', '20:38:17', '?', ''),
(26, 'tryyyyyyyy.jpeg', 'sdfgf', '769899799', '2024-09-07', '22', 'Male', '09796785674', 'sdfsdf', 'fgsdg', '', '', 'Visitor', '', '', 'Single', 'tryyyyyyyy', '22:40:09', '22:40:17', ''),
(27, 'kyebe jean.jpeg', 'cgxsdgfsd', '78998899898', '2024-09-14', '22', 'Female', '09089797887', 'afddf', 'eras', '', '', 'Visitor', '', '', 'Single', 'kyebe jean', '08:43:52', '?', ''),
(28, 'hellooo.jpeg', 'ertsdf', '3643564', '2024-09-14', '22', 'Male', '09907924234', 'kjjkgkh', 'ugiuoo', '', '', 'Visitor', '', '', 'Married', 'hellooo', '09:15:20', '09:15:27', ''),
(29, 'tiaw.jpeg', 'gertwertwe', '234235425', '2024-09-14', '22', 'Female', '98896986898', 'kjk', 'lhklj', '', '', 'Visitor', '', '', 'Single', 'tiaw', '10:13:55', '10:13:59', ''),
(30, 'visitorrrr.jpeg', 'cgxsdgfsd', '78998899898', '2024-09-19', '21', 'Male', '09234234232', 'dfsdfsdfs', 'sdgerytre', '', '', 'Visitor', '', '', 'Married', 'visitorrrr', '21:36:35', '?', ''),
(31, 'visittt.jpeg', 'cgxsdgfsd', '78998899898', '2024-09-23', '14', 'Male', '08656746734', 'dfgdgsd', 'fadshdhdyyyyyyyyyyyy', '', '', 'Visitor', '', '', 'Widowed', 'visittt', '10:55:29', '?', ''),
(32, 'visittry.jpeg', 'ertsdf', '3643564', '2024-09-23', '21', 'Male', '09645634534', 'sdfstgertsdg', 'tryyyyyy', '', '', 'Visitor', '', '', 'Single', 'visittry', '11:34:07', '11:34:10', ''),
(33, 'thisss.jpeg', 'gertwertwe', '234235425', '2024-09-23', 'BSIT', 'Male', '09345233234', 'thisss', 'thisss', '', '', 'Visitor', '', '', 'Single', 'thisss', '11:36:44', '11:36:49', ''),
(34, 'last.jpeg', 'sdfgf', '769899799', '2024-09-23', 'BEED', 'Male', '09345234234', 'last', 'last', '', '', 'Visitor', '', '', 'Married', 'last', '11:45:16', '11:45:25', ''),
(35, 'ahmmm.jpeg', 'Visitor001', '0009500932', '2024-09-23', 'BEED', 'Female', '09235424234', 'ahmmm', 'ahmmm', '', '', 'Visitor', '', '', 'Single', 'ahmmm', '11:47:52', '?', ''),
(36, 'hayyay.jpeg', 'Visitor002', '0009669869', '2024-09-23', 'BEED', 'Male', '06856456345', 'hayyay', 'hayyay', '', '', 'Visitor', '', '', 'Single', 'hayyay', '11:49:24', '?', ''),
(37, 'visitwww.jpeg', 'gndf', '707989669', '2024-09-23', 'HD', 'Male', '06523523235', 'visitwww', 'visitwww', '', '', 'Visitor', '', '', 'Single', 'visitwww', '11:54:11', '?', ''),
(38, 'kyebeee.jpeg', 'gjjgi', '89698798', '2024-09-23', 'FD', '', '95736345345', 'kyebeee', 'kyebeee', '', '', 'Visitor', '', '', 'Single', 'kyebeee', '11:57:30', '11:57:36', ''),
(39, 'dsfsdsdfsd.jpeg', 'qwert', '123456', '2024-09-23', 'FD', 'Male', '45673452354', 'dsfsdsdfsd', 'dsfsdsdfsd', '', '', 'Visitor', '', '', 'Single', 'dsfsdsdfsd', '12:00:06', '12:01:32', ''),
(40, 'vitiyt111.jpeg', 'vitiyt111', '123455555', '2024-09-23', 'HD', 'Male', '95674563454', 'vitiyt111', 'vitiyt111', '', '', 'Visitor', '', '', 'Married', 'vitiyt111', '12:02:08', '12:02:20', ''),
(41, 'gertwertwe.jpeg', 'gertwertwe', '234235425', '2024-10-01', 'RGSTR', '', '09234242342', 'gertwertwe', 'gertwertwe', '', '', 'Visitor', '', '', 'Single', 'gertwertwe', '06:42:27', '06:42:40', ''),
(42, 'gndf.jpeg', 'gndf', '707989669', '2024-10-01', 'HD', 'Male', '09243234234', 'gndf', 'gndf', '', '', 'Visitor', '', '', 'Married', 'gndf', '06:45:09', '06:45:13', ''),
(43, 'sdfgf.jpeg', 'sdfgf', '769899799', '2024-10-01', 'HD', 'Male', '06654663453', 'sdfgf', 'sdfgf', '', '', 'Visitor', '', '', 'Single', 'sdfgf', '08:30:24', '08:30:51', ''),
(44, 'Visitor002.jpeg', 'Visitor002', '0009669869', '2024-10-01', 'FD', 'Male', '09223423423', 'Visitor002', 'Visitor002', '', '', 'Visitor', '', '', 'Single', 'Visitor002', '08:32:44', '08:32:52', ''),
(45, '.jpeg', '', '', '2024-10-03', '', '', '', '', '', '', '', 'Visitor', '', '', '', '', '10:11:26', '?', ''),
(46, '.jpeg', '', '', '2024-10-03', '', '', '', '', '', '', '', 'Visitor', '', '', '', '', '10:12:09', '?', ''),
(47, '.jpeg', '', '', '2024-10-03', '', '', '', '', '', '', '', 'Visitor', '', '', '', '', '10:12:43', '?', ''),
(48, '.jpeg', '', '', '2024-10-03', '', '', '', '', '', '', '', 'Visitor', '', '', '', '', '10:15:56', '?', ''),
(49, '.jpeg', '', '', '2024-10-03', '', '', '', '', '', '', '', 'Visitor', '', '', '', '', '10:17:49', '?', ''),
(50, '.jpeg', '', '', '2024-10-03', '', '', '', '', '', '', '', 'Visitor', '', '', '', '', '10:57:55', '?', ''),
(51, '.jpeg', '', '', '2024-10-03', '', '', '', '', '', '', '', 'Visitor', '', '', '', '', '10:59:02', '?', ''),
(52, '.jpeg', '', '', '2024-10-03', '', '', '', '', '', '', '', 'Visitor', '', '', '', '', '11:19:20', '?', ''),
(53, '.jpeg', '', '', '2024-10-03', '', '', '', '', '', '', '', 'Visitor', '', '', '', '', '12:54:29', '?', ''),
(54, '.jpeg', '', '', '2024-10-03', '', '', '', '', '', '', '', 'Visitor', '', '', '', '', '14:59:01', '?', ''),
(55, '.jpeg', '', '', '2024-10-03', '', '', '', '', '', '', '', 'Visitor', '', '', '', '', '18:38:27', '?', ''),
(56, '.jpeg', '', '', '2024-10-03', '', '', '', '', '', '', '', 'Visitor', '', '', '', '', '18:43:23', '?', ''),
(57, '.jpeg', '', '', '2024-10-03', '', '', '', '', '', '', '', 'Visitor', '', '', '', '', '18:44:02', '?', ''),
(58, '.jpeg', '', '', '2024-10-03', '', '', '', '', '', '', '', 'Visitor', '', '', '', '', '18:49:20', '?', ''),
(66, 'Kyebe Jean Ungon.jpeg', '', '769899799', '2024-10-20', 'HD', 'Male', '45645645646', 'Conception Street', 'sdfsdfsdfsd', '', '', 'Visitor', '', '', 'Single', 'Kyebe Jean Ungon', '12:45:25', '?', 'Gate'),
(67, 'dfgdgsd.jpeg', '', '3453334343', '2024-10-20', 'HD', 'Male', '34563453453', 'dfgdgsd', 'dfgdgsd', '', '', 'Visitor', '', '', 'Single', 'dfgdgsd', '13:34:57', '?', 'Gate'),
(68, 'Kyebe Jean Ungon.jpeg', '', '769899799', '2024-11-21', 'HD', '', '43534534534', 'Conception Street', 'sfdgsdfsdfsd', '', '', 'Visitor', '', '', '', 'Kyebe Jean Ungon', '10:25:36', '?', 'Gate'),
(69, 'jhnjknjkn.jpeg', '', '3453334343', '2024-11-29', 'HD', '', '34567895678', 'gvghhjbnhj', 'fyvgbgnhnhj', '', '', 'Visitor', '', '', '', 'jhnjknjkn', '13:40:20', '?', 'Gate'),
(70, 'Donna.jpeg', '', '1111111111', '2024-12-11', 'RGSTR', '', '09876545676', 'Poblacion', 'Get TOR', '', '', 'Visitor', '', '', '', 'Donna', '12:28:41', '?', 'Gate'),
(71, 'Sharon.jpeg', '', '0987654321', '2024-12-11', 'BEED', '', '09677875566', 'Talangnan', 'May tuyo sa instructor', '', '', 'Visitor', '', '', '', 'Sharon', '12:32:11', '12:34:51', 'Gate'),
(72, 'Juros.jpeg', '', '0009669869', '2024-12-11', 'HD', '', '09456785676', 'Santa Fe', 'Gepatawag', '', '', 'Visitor', '', '', '', 'Juros', '12:34:15', '?', 'Gate'),
(73, 'Jonalyn.jpeg', '', '7698997991', '2024-12-11', 'BSIT', '', '09677446757', 'Malbago', 'To Apply', '', '', 'Visitor', '', '', '', 'Jonalyn', '12:36:04', '?', 'Gate'),
(74, 'photo_35.jpg', '', '8462915688', '2025-01-10', 'Finance', '', '6349353249', 'Street 3, City 8', 'Visit purpose 5', '', '', 'Visitor', '', '', '', 'Oscar Martinez', '21:42:02', '03:48:30', 'Gate'),
(75, 'photo_23.jpg', '', '6604314909', '2025-01-13', 'IT', '', '5006249821', 'Street 65, City 4', 'Visit purpose 3', '', '', 'Visitor', '', '', '', 'Charlie Wilson', '17:56:56', '06:30:14', 'Gate'),
(76, 'photo_18.jpg', '', '0325614706', '2024-12-27', 'Operations', '', '9611117284', 'Street 95, City 6', 'Visit purpose 4', '', '', 'Visitor', '', '', '', 'Oscar Martinez', '19:42:28', '03:37:20', 'Gate'),
(77, 'photo_18.jpg', '', '2085771360', '2024-12-29', 'Sales', '', '6147207273', 'Street 24, City 2', 'Visit purpose 1', '', '', 'Visitor', '', '', '', 'Alice Johnson', '21:31:21', '04:42:52', 'Gate'),
(78, 'photo_25.jpg', '', '1067537789', '2024-12-17', 'HR', '', '1281831960', 'Street 33, City 8', 'Visit purpose 4', '', '', 'Visitor', '', '', '', 'Oscar Martinez', '23:55:45', '03:18:00', 'Gate'),
(79, 'photo_60.jpg', '', '7666888684', '2024-12-15', 'HR', '', '2806207917', 'Street 61, City 7', 'Visit purpose 3', '', '', 'Visitor', '', '', '', 'Bob Brown', '21:56:37', '06:03:44', 'Gate'),
(80, 'photo_49.jpg', '', '0009669869', '2025-01-04', 'IT', '', '7000287430', 'Street 8, City 4', 'Visit purpose 2', '', '', 'Visitor', '', '', '', 'Jane Smith', '18:10:51', '02:55:52', 'Gate'),
(81, 'photo_48.jpg', '', '0556589801', '2024-12-21', 'Operations', '', '2086786109', 'Street 93, City 9', 'Visit purpose 2', '', '', 'Visitor', '', '', '', 'Grace Taylor', '17:45:31', '06:04:54', 'Gate'),
(82, 'photo_93.jpg', '', '0239874705', '2024-12-21', 'IT', '', '4759107811', 'Street 18, City 9', 'Visit purpose 4', '', '', 'Visitor', '', '', '', 'Leo Rodriguez', '18:19:04', '04:13:42', 'Gate'),
(83, 'photo_71.jpg', '', '1174443772', '2025-01-13', 'Finance', '', '4371364848', 'Street 76, City 2', 'Visit purpose 1', '', '', 'Visitor', '', '', '', 'Grace Taylor', '21:10:30', '02:28:58', 'Gate'),
(84, 'photo_42.jpg', '', '0069598353', '2025-01-09', 'Operations', '', '1393114521', 'Street 78, City 4', 'Visit purpose 2', '', '', 'Visitor', '', '', '', 'Alice Johnson', '22:51:44', '07:39:25', 'Gate'),
(85, 'photo_45.jpg', '', '7666888684', '2024-12-23', 'Operations', '', '6900929684', 'Street 22, City 5', 'Visit purpose 5', '', '', 'Visitor', '', '', '', 'Eve Davis', '23:45:42', '03:55:26', 'Gate'),
(86, 'photo_70.jpg', '', '7698997991', '2025-01-08', 'IT', '', '7072094662', 'Street 67, City 2', 'Visit purpose 5', '', '', 'Visitor', '', '', '', 'Leo Rodriguez', '21:50:09', '06:13:24', 'Gate'),
(87, 'photo_48.jpg', '', '4779904125', '2025-01-11', 'HR', '', '4152785206', 'Street 12, City 9', 'Visit purpose 1', '', '', 'Visitor', '', '', '', 'Eve Davis', '21:37:08', '04:38:22', 'Gate'),
(88, 'photo_45.jpg', '', '7698997991', '2024-12-17', 'IT', '', '8592419079', 'Street 49, City 7', 'Visit purpose 5', '', '', 'Visitor', '', '', '', 'Alice Johnson', '20:27:17', '05:04:40', 'Gate'),
(89, 'photo_15.jpg', '', '4407780893', '2024-12-30', 'Sales', '', '1899712405', 'Street 81, City 3', 'Visit purpose 2', '', '', 'Visitor', '', '', '', 'Leo Rodriguez', '21:31:26', '04:50:24', 'Gate'),
(90, 'photo_80.jpg', '', '4639438742', '2024-12-19', 'HR', '', '2209707629', 'Street 57, City 1', 'Visit purpose 3', '', '', 'Visitor', '', '', '', 'Ivy Clark', '00:15:25', '07:38:33', 'Gate'),
(91, 'photo_54.jpg', '', '4175833370', '2024-12-18', 'Operations', '', '7206204180', 'Street 66, City 9', 'Visit purpose 4', '', '', 'Visitor', '', '', '', 'Charlie Wilson', '00:08:06', '05:51:40', 'Gate'),
(92, 'photo_8.jpg', '', '7250421127', '2025-01-08', 'Finance', '', '5153335802', 'Street 79, City 4', 'Visit purpose 2', '', '', 'Visitor', '', '', '', 'Grace Taylor', '19:48:17', '05:46:25', 'Gate'),
(93, 'photo_91.jpg', '', '6806531704', '2024-12-26', 'Sales', '', '6409407260', 'Street 68, City 3', 'Visit purpose 3', '', '', 'Visitor', '', '', '', 'Charlie Wilson', '18:43:45', '07:47:22', 'Gate'),
(94, 'photo_62.jpg', '', '1370533925', '2025-01-08', 'IT', '', '7210192862', 'Street 6, City 4', 'Visit purpose 4', '', '', 'Visitor', '', '', '', 'Eve Davis', '19:12:44', '05:55:52', 'Gate'),
(95, 'photo_77.jpg', '', '7666888684', '2025-01-07', 'IT', '', '8298606839', 'Street 84, City 10', 'Visit purpose 1', '', '', 'Visitor', '', '', '', 'Ivy Clark', '00:15:04', '04:24:54', 'Gate'),
(96, 'photo_1.jpg', '', '7486184439', '2025-01-13', 'HR', '', '1028980806', 'Street 11, City 7', 'Visit purpose 3', '', '', 'Visitor', '', '', '', 'John Doe', '20:44:40', '05:33:07', 'Gate'),
(97, 'photo_44.jpg', '', '0043742223', '2024-12-24', 'Finance', '', '6127085299', 'Street 79, City 5', 'Visit purpose 1', '', '', 'Visitor', '', '', '', 'Grace Taylor', '00:23:14', '07:44:50', 'Gate'),
(98, 'photo_16.jpg', '', '1840326393', '2024-12-28', 'Finance', '', '2433215480', 'Street 42, City 9', 'Visit purpose 4', '', '', 'Visitor', '', '', '', 'Bob Brown', '00:59:18', '07:17:26', 'Gate'),
(99, 'photo_68.jpg', '', '2884576225', '2024-12-31', 'IT', '', '1281562593', 'Street 8, City 8', 'Visit purpose 5', '', '', 'Visitor', '', '', '', 'Grace Taylor', '00:48:38', '06:10:01', 'Gate'),
(100, 'photo_20.jpg', '', '7474490588', '2024-12-27', 'Operations', '', '3266534050', 'Street 8, City 3', 'Visit purpose 2', '', '', 'Visitor', '', '', '', 'Bob Brown', '01:43:28', '03:19:05', 'Gate'),
(101, 'photo_19.jpg', '', '7365324101', '2024-12-15', 'Operations', '', '4382238483', 'Street 96, City 9', 'Visit purpose 2', '', '', 'Visitor', '', '', '', 'Alice Johnson', '19:02:31', '07:17:34', 'Gate'),
(102, 'photo_35.jpg', '', '4167105671', '2024-12-21', 'Sales', '', '2254042808', 'Street 42, City 9', 'Visit purpose 5', '', '', 'Visitor', '', '', '', 'Bob Brown', '21:25:00', '06:48:17', 'Gate'),
(103, 'photo_87.jpg', '', '3240643840', '2024-12-29', 'IT', '', '7723506694', 'Street 74, City 9', 'Visit purpose 1', '', '', 'Visitor', '', '', '', 'Oscar Martinez', '19:06:45', '04:57:15', 'Gate'),
(104, 'photo_73.jpg', '', '7698997991', '2025-01-09', 'Sales', '', '8907313958', 'Street 25, City 7', 'Visit purpose 5', '', '', 'Visitor', '', '', '', 'Jane Smith', '16:57:54', '05:48:02', 'Gate'),
(105, 'photo_84.jpg', '', '0748519543', '2025-01-11', 'HR', '', '9949644972', 'Street 54, City 10', 'Visit purpose 5', '', '', 'Visitor', '', '', '', 'Oscar Martinez', '22:09:00', '06:53:14', 'Gate'),
(106, 'photo_74.jpg', '', '6246485118', '2025-01-12', 'Finance', '', '8669500910', 'Street 72, City 1', 'Visit purpose 5', '', '', 'Visitor', '', '', '', 'John Doe', '01:09:29', '02:17:54', 'Gate'),
(107, 'photo_10.jpg', '', '6806531704', '2024-12-22', 'Finance', '', '9822293533', 'Street 76, City 10', 'Visit purpose 4', '', '', 'Visitor', '', '', '', 'Oscar Martinez', '17:06:28', '04:21:23', 'Gate'),
(108, 'photo_32.jpg', '', '7698997991', '2024-12-31', 'Operations', '', '8037228491', 'Street 72, City 6', 'Visit purpose 4', '', '', 'Visitor', '', '', '', 'Grace Taylor', '21:14:54', '02:01:34', 'Gate'),
(109, 'photo_47.jpg', '', '3412143639', '2025-01-07', 'Sales', '', '7948420248', 'Street 34, City 2', 'Visit purpose 5', '', '', 'Visitor', '', '', '', 'Jane Smith', '19:50:48', '04:00:01', 'Gate'),
(110, 'photo_66.jpg', '', '0914780959', '2024-12-19', 'Sales', '', '8921503542', 'Street 72, City 9', 'Visit purpose 4', '', '', 'Visitor', '', '', '', 'Grace Taylor', '21:16:16', '04:22:32', 'Gate'),
(111, 'photo_95.jpg', '', '3482548405', '2024-12-27', 'IT', '', '4223059282', 'Street 44, City 1', 'Visit purpose 4', '', '', 'Visitor', '', '', '', 'Eve Davis', '16:48:02', '07:04:24', 'Gate'),
(112, 'photo_54.jpg', '', '0556589801', '2025-01-04', 'IT', '', '8572901652', 'Street 34, City 10', 'Visit purpose 5', '', '', 'Visitor', '', '', '', 'Leo Rodriguez', '23:01:37', '05:34:35', 'Gate'),
(113, 'photo_13.jpg', '', '2453068181', '2024-12-24', 'IT', '', '8758443990', 'Street 98, City 2', 'Visit purpose 4', '', '', 'Visitor', '', '', '', 'Alice Johnson', '20:08:23', '02:27:56', 'Gate');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `about`
--
ALTER TABLE `about`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `admin_sessions`
--
ALTER TABLE `admin_sessions`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `archived_attendance_20250727_190813`
--
ALTER TABLE `archived_attendance_20250727_190813`
  ADD PRIMARY KEY (`id`),
  ADD KEY `student_id` (`student_id`);

--
-- Indexes for table `archived_attendance_logs`
--
ALTER TABLE `archived_attendance_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `student_id` (`student_id`);

--
-- Indexes for table `archived_instructor_logs`
--
ALTER TABLE `archived_instructor_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `instructor_id` (`instructor_id`);

--
-- Indexes for table `attendance_logs`
--
ALTER TABLE `attendance_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `student_id` (`student_id`);

--
-- Indexes for table `department`
--
ALTER TABLE `department`
  ADD PRIMARY KEY (`department_id`);

--
-- Indexes for table `gate_logs`
--
ALTER TABLE `gate_logs`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `instructor`
--
ALTER TABLE `instructor`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `instructor_accounts`
--
ALTER TABLE `instructor_accounts`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD KEY `instructor_id` (`instructor_id`);

--
-- Indexes for table `instructor_attendance`
--
ALTER TABLE `instructor_attendance`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `instructor_glogs`
--
ALTER TABLE `instructor_glogs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_instructor_id` (`instructor_id`),
  ADD KEY `idx_id_number` (`id_number`),
  ADD KEY `idx_date` (`date`),
  ADD KEY `idx_action` (`action`),
  ADD KEY `idx_period` (`period`);

--
-- Indexes for table `instructor_logs`
--
ALTER TABLE `instructor_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `instructor_id` (`instructor_id`);

--
-- Indexes for table `lostcard`
--
ALTER TABLE `lostcard`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `lost_found`
--
ALTER TABLE `lost_found`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `personell`
--
ALTER TABLE `personell`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `personell_logs`
--
ALTER TABLE `personell_logs`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `personnel_glogs`
--
ALTER TABLE `personnel_glogs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_personnel_id` (`personnel_id`),
  ADD KEY `idx_id_number` (`id_number`),
  ADD KEY `idx_date` (`date`),
  ADD KEY `idx_action` (`action`),
  ADD KEY `idx_period` (`period`);

--
-- Indexes for table `role`
--
ALTER TABLE `role`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `rooms`
--
ALTER TABLE `rooms`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `room_logs`
--
ALTER TABLE `room_logs`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `room_schedules`
--
ALTER TABLE `room_schedules`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `stranger_logs`
--
ALTER TABLE `stranger_logs`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `students`
--
ALTER TABLE `students`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `id_number` (`id_number`);

--
-- Indexes for table `students_glogs`
--
ALTER TABLE `students_glogs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_student_id` (`student_id`),
  ADD KEY `idx_id_number` (`id_number`),
  ADD KEY `idx_date` (`date`),
  ADD KEY `idx_action` (`action`),
  ADD KEY `idx_period` (`period`);

--
-- Indexes for table `subjects`
--
ALTER TABLE `subjects`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `subject_code` (`subject_code`);

--
-- Indexes for table `swap_requests`
--
ALTER TABLE `swap_requests`
  ADD PRIMARY KEY (`id`),
  ADD KEY `requester_id` (`requester_id`),
  ADD KEY `target_id` (`target_id`);

--
-- Indexes for table `user`
--
ALTER TABLE `user`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `visitor`
--
ALTER TABLE `visitor`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `visitor_glogs`
--
ALTER TABLE `visitor_glogs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_visitor_id` (`visitor_id`),
  ADD KEY `idx_id_number` (`id_number`),
  ADD KEY `idx_date` (`date`),
  ADD KEY `idx_action` (`action`),
  ADD KEY `idx_period` (`period`);

--
-- Indexes for table `visitor_logs`
--
ALTER TABLE `visitor_logs`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `about`
--
ALTER TABLE `about`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `admin_sessions`
--
ALTER TABLE `admin_sessions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=52;

--
-- AUTO_INCREMENT for table `archived_attendance_20250727_190813`
--
ALTER TABLE `archived_attendance_20250727_190813`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `archived_attendance_logs`
--
ALTER TABLE `archived_attendance_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=315;

--
-- AUTO_INCREMENT for table `archived_instructor_logs`
--
ALTER TABLE `archived_instructor_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=103;

--
-- AUTO_INCREMENT for table `attendance_logs`
--
ALTER TABLE `attendance_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=315;

--
-- AUTO_INCREMENT for table `department`
--
ALTER TABLE `department`
  MODIFY `department_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=166;

--
-- AUTO_INCREMENT for table `gate_logs`
--
ALTER TABLE `gate_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `instructor`
--
ALTER TABLE `instructor`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=20;

--
-- AUTO_INCREMENT for table `instructor_accounts`
--
ALTER TABLE `instructor_accounts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `instructor_attendance`
--
ALTER TABLE `instructor_attendance`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `instructor_glogs`
--
ALTER TABLE `instructor_glogs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `instructor_logs`
--
ALTER TABLE `instructor_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=104;

--
-- AUTO_INCREMENT for table `lostcard`
--
ALTER TABLE `lostcard`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=103;

--
-- AUTO_INCREMENT for table `lost_found`
--
ALTER TABLE `lost_found`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `personell`
--
ALTER TABLE `personell`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2147483648;

--
-- AUTO_INCREMENT for table `personell_logs`
--
ALTER TABLE `personell_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=170;

--
-- AUTO_INCREMENT for table `personnel_glogs`
--
ALTER TABLE `personnel_glogs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `role`
--
ALTER TABLE `role`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=139;

--
-- AUTO_INCREMENT for table `rooms`
--
ALTER TABLE `rooms`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=157;

--
-- AUTO_INCREMENT for table `room_logs`
--
ALTER TABLE `room_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=91;

--
-- AUTO_INCREMENT for table `room_schedules`
--
ALTER TABLE `room_schedules`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=30;

--
-- AUTO_INCREMENT for table `stranger_logs`
--
ALTER TABLE `stranger_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `students`
--
ALTER TABLE `students`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=79;

--
-- AUTO_INCREMENT for table `students_glogs`
--
ALTER TABLE `students_glogs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `subjects`
--
ALTER TABLE `subjects`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=33;

--
-- AUTO_INCREMENT for table `swap_requests`
--
ALTER TABLE `swap_requests`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `user`
--
ALTER TABLE `user`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=20240333;

--
-- AUTO_INCREMENT for table `visitor`
--
ALTER TABLE `visitor`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=129;

--
-- AUTO_INCREMENT for table `visitor_glogs`
--
ALTER TABLE `visitor_glogs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `visitor_logs`
--
ALTER TABLE `visitor_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=114;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `attendance_logs`
--
ALTER TABLE `attendance_logs`
  ADD CONSTRAINT `attendance_logs_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`);

--
-- Constraints for table `instructor_accounts`
--
ALTER TABLE `instructor_accounts`
  ADD CONSTRAINT `instructor_accounts_ibfk_1` FOREIGN KEY (`instructor_id`) REFERENCES `instructor` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `instructor_logs`
--
ALTER TABLE `instructor_logs`
  ADD CONSTRAINT `instructor_logs_ibfk_1` FOREIGN KEY (`instructor_id`) REFERENCES `instructor` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
