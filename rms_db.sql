-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Sep 25, 2026 at 03:24 PM
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
-- Database: `rms_db`
--

-- --------------------------------------------------------

--
-- Table structure for table `activity_logs`
--

CREATE TABLE `activity_logs` (
  `id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED DEFAULT NULL,
  `action` varchar(80) NOT NULL,
  `target` varchar(80) DEFAULT NULL,
  `target_id` int(10) UNSIGNED DEFAULT NULL,
  `details` text DEFAULT NULL,
  `ip` varchar(45) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `activity_logs`
--

INSERT INTO `activity_logs` (`id`, `user_id`, `action`, `target`, `target_id`, `details`, `ip`, `created_at`) VALUES
(2, 1, 'create_apartment', 'apartments', 1, 'Four seasons apartments', '::1', '2026-08-29 15:07:21'),
(3, 1, 'create_unit', 'units', 1, 'A1 — apt #1', '::1', '2026-08-29 15:09:23'),
(4, 1, 'update_caretaker', 'users', 4, 'Nicholas Mwiti', '::1', '2026-08-29 15:20:28'),
(5, 1, 'create_apartment', 'apartments', 2, 'Wanjee Apartments', '::1', '2026-08-31 06:48:51'),
(6, 1, 'create_apartment', 'apartments', 3, 'Penuel Apartments', '::1', '2026-08-31 07:59:27'),
(7, 1, 'update_apartment', 'apartments', 1, 'Four seasons apartments', '::1', '2026-08-31 08:06:37'),
(8, 1, 'update_apartment', 'apartments', 3, 'Penuel Apartments', '::1', '2026-08-31 08:07:00'),
(9, 1, 'record_payment', 'payments', 1, 'August 2026 — KES 45,000 — tenant #3', '::1', '2026-08-31 08:08:21'),
(10, 1, 'assign_caretaker', 'caretaker_assignments', 5, 'Phillip Njugi → apt #1', '::1', '2026-08-31 10:10:11'),
(11, 1, 'update_caretaker', 'users', 5, 'Phillip Njugi', '::1', '2026-08-31 10:10:49'),
(12, 1, 'update_apartment', 'apartments', 1, 'Four seasons apartments', '::1', '2026-08-31 10:14:58'),
(13, 1, 'update_apartment', 'apartments', 1, 'Four seasons apartments', '::1', '2026-08-31 10:22:36'),
(14, 1, 'update_apartment', 'apartments', 1, 'Four seasons apartments', '::1', '2026-08-31 10:26:21'),
(15, 5, 'move_in', 'tenant_units', 3, 'Leila Kerubo → Unit A1 on 2026-08-31', '::1', '2026-08-31 10:29:06'),
(16, 1, 'update_apartment', 'apartments', 1, 'Four seasons apartments', '::1', '2026-08-31 10:36:37'),
(17, 1, 'create_unit', 'units', 2, 'A2 — apt #1', '::1', '2026-08-31 10:37:59'),
(18, 1, 'create_unit', 'units', 3, 'B1 — apt #3', '::1', '2026-08-31 12:40:46'),
(19, 1, 'create_unit', 'units', 4, 'B2 — apt #3', '::1', '2026-08-31 12:48:08'),
(20, 1, 'create_unit', 'units', 5, 'B3 — apt #3', '::1', '2026-08-31 12:50:00'),
(21, 4, 'move_in', 'tenant_units', 6, 'Shaline Martha → Unit B3 on 2026-08-11', '::1', '2026-08-31 12:51:06'),
(22, 5, 'update_utility_bill', 'utility_bills', 1, 'Garbage - September 2026 - KES 500', '::1', '2026-09-01 07:28:55'),
(23, 8, 'submit_payment', 'payments', 2, 'Utility payment submitted - September 2026 - KES 500', '::1', '2026-09-01 07:58:59'),
(24, 1, 'record_payment', 'payments', 5, 'September 2026 — KES 500,000 — tenant #6', '::1', '2026-09-01 10:29:22'),
(25, 1, 'confirm_payment', 'payments', 2, 'September 2026 — Leila Kerubo', '::1', '2026-09-01 13:54:28'),
(26, 1, 'update_apartment', 'apartments', 1, 'Four seasons apartments', '::1', '2026-09-01 14:59:39'),
(27, 1, 'update_apartment', 'apartments', 1, 'Four seasons apartments', '::1', '2026-09-01 15:06:22'),
(28, 1, 'update_apartment', 'apartments', 1, 'Four seasons apartments', '::1', '2026-09-01 15:17:59'),
(29, 1, 'update_apartment', 'apartments', 1, 'Four seasons apartments', '::1', '2026-09-01 15:23:39'),
(30, 1, 'update_unit', 'units', 3, 'B1', '::1', '2026-09-01 15:24:37'),
(31, 1, 'update_apartment', 'apartments', 1, 'Four seasons apartments', '::1', '2026-09-01 15:31:33'),
(32, 1, 'update_apartment', 'apartments', 1, 'Four seasons apartments', '::1', '2026-09-01 15:38:32'),
(33, 1, 'update_apartment', 'apartments', 3, 'Penuel Apartments', '::1', '2026-09-01 15:39:33'),
(34, 1, 'delete_apartment', 'apartments', 2, 'Deleted apartment: Wanjee Apartments', '::1', '2026-09-01 15:43:11'),
(35, 1, 'create_apartment', 'apartments', 4, 'Wanjee Apartments', '::1', '2026-09-01 15:44:00'),
(36, 1, 'update_unit', 'units', 5, 'B3', '::1', '2026-09-01 16:01:16'),
(37, 1, 'update_unit', 'units', 3, 'B1', '::1', '2026-09-01 16:01:49'),
(38, 1, 'update_apartment', 'apartments', 3, 'Penuel Apartments', '::1', '2026-09-01 16:03:15'),
(39, 1, 'update_apartment', 'apartments', 1, 'Four seasons apartments', '::1', '2026-09-01 16:04:47'),
(40, 1, 'update_apartment', 'apartments', 1, 'Four seasons apartments', '::1', '2026-09-01 16:05:28'),
(41, 1, 'update_apartment', 'apartments', 4, 'Wanjee Apartments', '::1', '2026-09-01 16:06:22'),
(42, 1, 'update_apartment', 'apartments', 4, 'Wanjee Apartments', '::1', '2026-09-01 16:13:01'),
(43, 8, 'submit_payment', 'payments', 6, 'Rent payment submitted - September 2026 - KES 45,000', '::1', '2026-09-01 17:20:17'),
(44, 1, 'update_unit', 'units', 41, '8', '::1', '2026-09-01 17:23:24'),
(45, 1, 'update_unit', 'units', 34, '1', '::1', '2026-09-01 17:23:38'),
(46, 1, 'update_unit', 'units', 37, '4', '::1', '2026-09-01 17:24:13'),
(47, 1, 'update_unit', 'units', 6, '1', '::1', '2026-09-01 17:25:48'),
(48, 1, 'update_unit', 'units', 7, '2', '::1', '2026-09-01 17:26:03'),
(49, 1, 'update_unit', 'units', 8, '3', '::1', '2026-09-01 17:26:17'),
(50, 1, 'create_apartment', 'apartments', 5, 'Kazi house', '::1', '2026-09-01 17:28:08'),
(51, 1, 'update_apartment', 'apartments', 5, 'Kazi house', '::1', '2026-09-01 17:28:26'),
(52, 1, 'delete_apartment', 'apartments', 5, 'Deleted apartment: Kazi house', '::1', '2026-09-01 18:01:47'),
(53, 1, 'create_apartment', 'apartments', 6, 'Kazi house', '::1', '2026-09-01 18:02:29'),
(54, 1, 'update_apartment', 'apartments', 1, 'Four seasons apartments', '::1', '2026-09-01 18:34:44'),
(55, 1, 'update_apartment', 'apartments', 6, 'Kazi house', '::1', '2026-09-01 18:35:08'),
(56, 1, 'update_apartment', 'apartments', 3, 'Penuel Apartments', '::1', '2026-09-01 18:36:07'),
(57, 1, 'update_apartment', 'apartments', 6, 'Kazi house', '::1', '2026-09-01 18:36:21'),
(58, 1, 'update_apartment', 'apartments', 6, 'Kazi house', '::1', '2026-09-01 18:39:44'),
(59, 1, 'create_apartment', 'apartments', 7, 'Mkulima', '::1', '2026-09-01 18:50:25'),
(60, 4, 'update_utility_bill', 'utility_bills', 2, 'Electricity - September 2026 - KES 1,000', '::1', '2026-09-01 18:55:00'),
(61, 1, 'create_apartment', 'apartments', 8, 'xyn', '::1', '2026-09-01 19:26:55'),
(62, 1, 'update_unit', 'units', 285, 'G01', '::1', '2026-09-01 19:28:25'),
(63, 1, 'confirm_payment', 'payments', 6, 'September 2026 — Leila Kerubo', '::1', '2026-09-01 19:48:52'),
(64, 1, 'confirm_utility_bill', 'utility_bills', 2, 'Electricity bill - September 2026 - Shaline Martha', '::1', '2026-09-01 19:49:42'),
(65, 1, 'confirm_utility_bill', 'utility_bills', 1, 'Garbage bill - September 2026 - Leila Kerubo', '::1', '2026-09-01 19:49:53'),
(66, 4, 'create_tenant', 'users', 7, 'Antonate Moraa', '::1', '2026-09-01 20:52:09'),
(67, 1, 'update_tenant', 'users', 7, 'Antonate Moraa', '::1', '2026-09-01 20:53:12'),
(68, 1, 'update_apartment', 'apartments', 1, 'Four seasons apartments', '::1', '2026-09-01 20:55:45'),
(69, 1, 'create_caretaker', 'users', 8, 'Jecinta njoki', '::1', '2026-09-02 09:35:18'),
(70, 1, 'update_apartment', 'apartments', 8, 'xyn', '::1', '2026-09-02 16:06:42'),
(71, 1, 'create_caretaker', 'users', 9, 'jhhhh', '::1', '2026-09-02 16:09:13'),
(72, 1, 'remove_caretaker', 'caretaker_history', 9, 'jhhhh (account deleted)', '::1', '2026-09-02 16:09:57'),
(73, 1, 'update_unit', 'units', 6, '1', '::1', '2026-09-02 16:42:18'),
(74, 1, 'update_unit', 'units', 5, 'B3', '::1', '2026-09-02 17:01:15'),
(75, 1, 'remove_caretaker', 'caretaker_history', 8, 'Jecinta njoki — Resigned (account deleted)', '::1', '2026-09-02 18:27:48'),
(76, 1, 'update_tenant', 'users', 7, 'Antonate Moraa', '::1', '2026-09-02 18:28:57'),
(77, 1, 'move_in', 'tenant_units', 7, 'Antonate Moraa → Unit 12 on 2026-09-15', '::1', '2026-09-03 07:24:35'),
(78, 1, 'move_out', 'tenant_units', 7, 'Antonate Moraa left Unit 12 on 2026-09-03', '::1', '2026-09-03 07:24:54'),
(79, 8, 'login', 'users', 3, 'Successful login', '::1', '2026-09-08 09:01:34'),
(80, 4, 'login', 'users', 4, 'Successful login', '::1', '2026-09-08 09:05:53'),
(81, 4, 'login', 'users', 4, 'Successful login', '::1', '2026-09-08 09:45:47'),
(82, 1, 'login', 'users', 2, 'Successful login', '::1', '2026-09-08 09:46:20'),
(83, 4, 'login', 'users', 4, 'Successful login', '::1', '2026-09-08 09:55:20'),
(84, 4, 'approve_tenant', 'users', 11, 'Anne Wangoi', '::1', '2026-09-08 10:21:39'),
(85, 4, 'move_in', 'tenant_units', 11, 'Anne Wangoi → Unit 103 on 2026-09-08', '::1', '2026-09-08 10:33:15'),
(86, 1, 'login', 'users', 2, 'Successful login', '::1', '2026-09-08 10:33:32'),
(87, 4, 'login', 'users', 4, 'Successful login', '::1', '2026-09-08 11:14:33'),
(88, 4, 'approve_tenant', 'users', 12, 'Anne Wangoi', '::1', '2026-09-08 11:14:45'),
(89, 4, 'move_in', 'tenant_units', 12, 'Anne Wangoi → Unit 12 on 2026-09-08', '::1', '2026-09-08 11:15:04'),
(90, 12, 'login', 'users', 12, 'Successful login', '::1', '2026-09-08 11:15:33'),
(91, 12, 'submit_payment', 'payments', 7, 'Deposit payment submitted - September 2026 - KES 15,000', '::1', '2026-09-08 11:22:42'),
(92, 1, 'login', 'users', 2, 'Successful login', '::1', '2026-09-08 11:34:34'),
(93, 1, 'confirm_payment', 'payments', 7, 'September 2026 — Anne Wangoi', '::1', '2026-09-08 11:34:59'),
(94, 12, 'login', 'users', 12, 'Successful login', '::1', '2026-09-08 11:39:20'),
(95, 1, 'login', 'users', 2, 'Successful login', '::1', '2026-09-08 11:40:13'),
(96, 1, 'update_apartment', 'apartments', 3, 'Penuel Apartments', '::1', '2026-09-08 11:53:57'),
(97, 1, 'update_apartment', 'apartments', 6, 'Kazi house', '::1', '2026-09-08 12:00:03'),
(98, 1, 'update_unit', 'units', 213, 'G01', '::1', '2026-09-08 12:01:33'),
(99, 1, 'update_apartment', 'apartments', 7, 'Mkulima', '::1', '2026-09-08 12:02:26'),
(100, 1, 'update_unit', 'units', 5, 'B3', '::1', '2026-09-08 12:04:20'),
(101, 1, 'update_unit', 'units', 213, 'G01', '::1', '2026-09-08 12:05:12'),
(102, 1, 'update_unit', 'units', 214, 'G02', '::1', '2026-09-08 12:05:26'),
(103, 1, 'update_unit', 'units', 215, 'G03', '::1', '2026-09-08 12:05:48'),
(104, 1, 'update_unit', 'units', 214, 'G02', '::1', '2026-09-08 12:05:57'),
(105, 1, 'update_unit', 'units', 216, 'G04', '::1', '2026-09-08 12:06:19'),
(106, 1, 'update_apartment', 'apartments', 4, 'Wanjee Apartments', '::1', '2026-09-08 12:08:10'),
(107, 1, 'create_caretaker', 'users', 13, 'Benjamin Otieno', '::1', '2026-09-08 12:14:57'),
(108, 13, 'login', 'users', 13, 'Successful login', '::1', '2026-09-08 12:16:14'),
(109, 1, 'login', 'users', 2, 'Successful login', '::1', '2026-09-08 13:03:37'),
(110, 1, 'assign_caretaker', 'caretaker_assignments', 13, 'Benjamin Otieno → apt #8', '::1', '2026-09-08 13:06:52'),
(111, 12, 'login', 'users', 12, 'Successful login', '::1', '2026-09-08 13:08:34'),
(112, 12, 'submit_payment', 'payments', 11, 'Rent payment submitted - September 2026 - KES 35,000', '::1', '2026-09-08 13:08:59'),
(113, 1, 'login', 'users', 2, 'Successful login', '::1', '2026-09-08 13:10:59'),
(114, 1, 'confirm_payment', 'payments', 11, 'September 2026 — Anne Wangoi', '::1', '2026-09-08 13:11:37'),
(115, 1, 'login', 'users', 2, 'Successful login', '::1', '2026-09-08 13:14:15'),
(116, 13, 'login', 'users', 13, 'Successful login', '::1', '2026-09-08 13:14:38'),
(117, 13, 'approve_tenant', 'users', 14, 'Daniel Muasya', '::1', '2026-09-08 13:14:58'),
(118, 13, 'move_in', 'tenant_units', 14, 'Daniel Muasya → Unit G01 on 2026-09-08', '::1', '2026-09-08 13:15:43'),
(119, 14, 'login', 'users', 14, 'Successful login', '::1', '2026-09-08 13:15:58'),
(120, 14, 'submit_payment', 'payments', 12, 'Deposit payment submitted - September 2026 - KES 150,000', '::1', '2026-09-08 13:17:28'),
(121, 1, 'login', 'users', 2, 'Successful login', '::1', '2026-09-08 13:18:05'),
(122, 1, 'update_apartment', 'apartments', 1, 'Four seasons apartments', '::1', '2026-09-08 13:22:15'),
(123, 1, 'update_apartment', 'apartments', 8, 'xyn', '::1', '2026-09-08 13:23:53'),
(124, 1, 'confirm_payment', 'payments', 12, 'September 2026 — Daniel Muasya', '::1', '2026-09-08 13:24:47'),
(125, 14, 'login', 'users', 14, 'Successful login', '::1', '2026-09-08 13:26:56'),
(126, 14, 'submit_payment', 'payments', 13, 'Rent payment submitted - September 2026 - KES 400,000', '::1', '2026-09-08 13:27:33'),
(127, 1, 'login', 'users', 2, 'Successful login', '::1', '2026-09-08 13:50:35'),
(128, 1, 'confirm_payment', 'payments', 13, 'September 2026 — Daniel Muasya', '::1', '2026-09-08 13:51:03'),
(129, 13, 'login', 'users', 13, 'Successful login', '::1', '2026-09-08 14:02:30'),
(130, 14, 'login', 'users', 14, 'Successful login', '::1', '2026-09-08 14:03:07'),
(131, 14, 'login', 'users', 14, 'Successful login', '::1', '2026-09-08 14:52:44'),
(132, 13, 'login', 'users', 13, 'Successful login', '::1', '2026-09-08 14:53:26'),
(133, 13, 'add_expense', 'shared_expenses', 1, 'Plumbing — KES 3,500 — September 2026', '::1', '2026-09-08 15:05:41'),
(134, 1, 'login', 'users', 2, 'Successful login', '::1', '2026-09-08 15:06:21'),
(135, 1, 'add_expense', 'shared_expenses', 2, 'Plumbing — KES 3,500 — September 2026', '::1', '2026-09-08 15:07:59'),
(136, 1, 'delete_expense', 'shared_expenses', 1, 'Deleted expense #1', '::1', '2026-09-08 15:08:08'),
(137, 1, 'login', 'users', 2, 'Successful login', '::1', '2026-09-08 16:28:34'),
(138, 1, 'login', 'users', 2, 'Successful login', '::1', '2026-09-08 16:34:54'),
(139, 1, 'create_caretaker', 'users', 15, 'Edgar Rotich', '::1', '2026-09-08 16:38:39'),
(140, 1, 'update_apartment', 'apartments', 6, 'Kazi house', '::1', '2026-09-08 16:39:14'),
(141, 3, 'login', 'users', 15, 'Successful login', '::1', '2026-09-08 16:43:34'),
(142, 1, 'login', 'users', 1, 'Successful login', '::1', '2026-09-08 17:53:15'),
(143, 1, 'assign_caretaker', 'caretaker_assignments', 3, 'Edgar Rotich → apt #1', '::1', '2026-09-08 17:54:57'),
(144, 1, 'assign_caretaker', 'caretaker_assignments', 3, 'Edgar Rotich → apt #1', '::1', '2026-09-08 17:55:25'),
(145, 1, 'assign_caretaker', 'caretaker_assignments', 3, 'Edgar Rotich → apt #1', '::1', '2026-09-08 18:02:07'),
(146, 1, 'assign_caretaker', 'caretaker_assignments', 3, 'Edgar Rotich → apt #1', '::1', '2026-09-08 18:03:32'),
(147, 1, 'login', 'users', 1, 'Successful login', '::1', '2026-09-08 19:08:40'),
(148, 1, 'assign_caretaker', 'caretaker_assignments', 3, 'Edgar Rotich → apt #1', '::1', '2026-09-08 19:09:33'),
(149, 3, 'login', 'users', 3, 'Successful login', '::1', '2026-09-08 19:10:14'),
(150, 3, 'login', 'users', 3, 'Successful login', '::1', '2026-09-08 19:52:32'),
(151, 3, 'approve_tenant', 'users', 16, 'Kelsey Namaswa', '::1', '2026-09-08 19:52:57'),
(152, 3, 'move_in', 'tenant_units', 16, 'Kelsey Namaswa → Unit 301 on 2026-07-08', '::1', '2026-09-08 19:54:03'),
(153, 16, 'login', 'users', 16, 'Successful login', '::1', '2026-09-08 19:54:49'),
(154, 16, 'submit_payment', 'payments', 14, 'Deposit payment submitted - September 2026 - KES 50,000', '::1', '2026-09-08 19:56:45'),
(155, 1, 'login', 'users', 1, 'Successful login', '::1', '2026-09-08 20:00:21'),
(156, 1, 'confirm_payment', 'payments', 14, 'September 2026 — Kelsey Namaswa', '::1', '2026-09-08 20:01:24'),
(157, 16, 'login', 'users', 16, 'Successful login', '::1', '2026-09-08 20:08:12'),
(158, 16, 'login', 'users', 16, 'Successful login', '::1', '2026-09-08 21:12:50'),
(159, 13, 'login', 'users', 13, 'Successful login', '::1', '2026-09-11 21:43:22'),
(160, 1, 'login', 'users', 1, 'Successful login', '::1', '2026-09-11 21:46:26'),
(161, 16, 'login', 'users', 16, 'Successful login', '::1', '2026-09-11 22:27:36'),
(162, 1, 'login', 'users', 1, 'Successful login', '::1', '2026-09-12 08:18:38'),
(163, 12, 'login', 'users', 12, 'Successful login', '::1', '2026-09-12 08:19:47'),
(164, 5, 'login', 'users', 5, 'Successful login', '::1', '2026-09-12 08:27:46'),
(165, 1, 'login', 'users', 1, 'Successful login', '::1', '2026-09-12 14:37:45'),
(166, 12, 'login', 'users', 12, 'Successful login', '::1', '2026-09-12 14:41:56'),
(167, 4, 'login', 'users', 4, 'Successful login', '::1', '2026-09-12 14:43:46'),
(168, 8, 'login', 'users', 8, 'Successful login', '::1', '2026-09-12 14:44:21'),
(169, 1, 'login', 'users', 1, 'Successful login', '::1', '2026-09-12 15:48:34'),
(170, 4, 'login', 'users', 4, 'Successful login', '::1', '2026-09-12 15:49:21'),
(171, 1, 'login', 'users', 1, 'Successful login', '::1', '2026-09-12 15:50:23'),
(172, 12, 'login', 'users', 12, 'Successful login', '::1', '2026-09-12 15:59:02'),
(173, 13, 'login', 'users', 13, 'Successful login', '::1', '2026-09-12 16:01:01'),
(174, 1, 'login', 'users', 1, 'Successful login', '::1', '2026-09-12 16:24:49'),
(175, 1, 'login', 'users', 1, 'Successful login', '::1', '2026-09-14 19:03:36'),
(176, 13, 'login', 'users', 13, 'Successful login', '::1', '2026-09-14 19:07:16'),
(177, 1, 'login', 'users', 1, 'Successful login', '::1', '2026-09-14 19:08:32'),
(178, 1, 'login', 'users', 1, 'Successful login', '::1', '2026-09-14 21:23:42'),
(179, 13, 'login', 'users', 13, 'Successful login', '::1', '2026-09-15 18:56:03'),
(180, 12, 'login', 'users', 12, 'Successful login', '::1', '2026-09-15 18:56:39'),
(181, 1, 'login', 'users', 1, 'Successful login', '::1', '2026-09-15 18:59:07'),
(182, 13, 'login', 'users', 13, 'Successful login', '::1', '2026-09-15 18:59:43'),
(183, 13, 'login', 'users', 13, 'Successful login', '::1', '2026-09-15 19:05:10'),
(184, 13, 'update_utility_bill', 'utility_bills', 3, 'Water - September 2026 - KES 5,000', '::1', '2026-09-15 19:06:32'),
(185, 14, 'login', 'users', 14, 'Successful login', '::1', '2026-09-15 19:06:46'),
(186, 1, 'login', 'users', 1, 'Successful login', '::1', '2026-09-15 21:14:04'),
(187, 13, 'login', 'users', 13, 'Successful login', '::1', '2026-09-15 21:14:30'),
(188, 1, 'login', 'users', 1, 'Successful login', '::1', '2026-09-15 22:32:49'),
(189, 1, 'create_apartment', 'apartments', 9, 'Shalom apartments', '::1', '2026-09-15 22:35:00'),
(190, 1, 'login', 'users', 1, 'Successful login', '::1', '2026-09-15 22:44:29'),
(191, 1, 'approve_tenant', 'users', 17, 'Bhakita Namaemba', '::1', '2026-09-15 22:44:50'),
(192, 17, 'login', 'users', 17, 'Successful login', '::1', '2026-09-15 22:45:19'),
(193, 1, 'login', 'users', 1, 'Successful login', '::1', '2026-09-15 22:53:58'),
(194, 1, 'login', 'users', 1, 'Successful login', '::1', '2026-09-18 22:30:50'),
(195, 13, 'login', 'users', 13, 'Successful login', '::1', '2026-09-18 22:35:22'),
(196, 8, 'login', 'users', 8, 'Successful login', '::1', '2026-09-20 00:04:05'),
(197, 12, 'login', 'users', 12, 'Successful login', '::1', '2026-09-20 03:08:00'),
(198, 13, 'login', 'users', 13, 'Successful login', '::1', '2026-09-20 03:16:01'),
(199, 1, 'login', 'users', 1, 'Successful login', '::1', '2026-09-20 03:16:40');

-- --------------------------------------------------------

--
-- Table structure for table `announcements`
--

CREATE TABLE `announcements` (
  `id` int(10) UNSIGNED NOT NULL,
  `created_by` int(10) UNSIGNED NOT NULL,
  `title` varchar(200) NOT NULL,
  `body` text NOT NULL,
  `type` enum('General','Urgent','Information') NOT NULL DEFAULT 'General',
  `target` enum('all_tenants','all_caretakers','specific_apartment','specific_tenant') NOT NULL DEFAULT 'all_tenants',
  `target_id` int(10) UNSIGNED DEFAULT NULL COMMENT 'apartment_id or tenant_id when target is specific',
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `expires_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `announcements`
--

INSERT INTO `announcements` (`id`, `created_by`, `title`, `body`, `type`, `target`, `target_id`, `created_at`, `expires_at`) VALUES
(1, 1, 'Rent payment', 'Kindly remember to pay your rent on time.', 'General', 'all_tenants', NULL, '2026-09-02 16:27:20', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `apartments`
--

CREATE TABLE `apartments` (
  `id` int(10) UNSIGNED NOT NULL,
  `name` varchar(120) NOT NULL,
  `location` varchar(255) NOT NULL,
  `county` varchar(80) DEFAULT NULL,
  `town` varchar(80) DEFAULT NULL,
  `estate` varchar(80) DEFAULT NULL,
  `street` varchar(120) DEFAULT NULL,
  `floors` tinyint(3) UNSIGNED DEFAULT 1,
  `total_units` smallint(5) UNSIGNED DEFAULT 0,
  `caretaker_id` int(10) UNSIGNED DEFAULT NULL COMMENT 'FK → users (caretaker)',
  `status` enum('Active','Inactive') NOT NULL DEFAULT 'Active',
  `description` text DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT NULL ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `apartments`
--

INSERT INTO `apartments` (`id`, `name`, `location`, `county`, `town`, `estate`, `street`, `floors`, `total_units`, `caretaker_id`, `status`, `description`, `created_at`, `updated_at`) VALUES
(1, 'Kazi house', 'Westlands', '', '', '', '', 1, 40, 3, 'Active', '', '2026-06-25 18:02:29', '2026-09-08 17:09:18'),
(2, 'Four seasons apartments', 'Kasarani', 'Nairobi', '', '', '', 6, 32, 5, 'Active', '', '2026-08-04 15:07:21', '2026-09-08 17:07:46'),
(3, 'Penuel Apartments', 'Thika', '', 'Thika Town', '', '', 7, 56, 4, 'Active', '', '2026-08-31 07:59:27', '2026-09-08 11:53:57'),
(4, 'Wanjee Apartments', 'Juja', 'Nairobi', '', '', '', 3, 12, 2, 'Active', '', '2026-09-01 15:44:00', '2026-09-08 12:08:10'),
(5, 'Mkulima', 'Lugari', 'Kakamega', '', '', '', 1, 72, NULL, 'Active', '', '2026-09-01 18:50:25', '2026-09-08 17:10:34'),
(6, 'xyn', 'Kilimani', 'Nairobi', '', '', '', 1, 1, 13, 'Active', '', '2026-09-01 19:26:55', '2026-09-08 17:11:17'),
(9, 'Shalom apartments', 'Nairobi', '', '', '', '', 1, 24, NULL, 'Active', '', '2026-09-15 22:35:00', '2026-09-15 22:35:00');

-- --------------------------------------------------------

--
-- Table structure for table `caretaker_apartments`
--

CREATE TABLE `caretaker_apartments` (
  `caretaker_id` int(10) UNSIGNED NOT NULL,
  `apartment_id` int(10) UNSIGNED NOT NULL,
  `assigned_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `caretaker_apartments`
--

INSERT INTO `caretaker_apartments` (`caretaker_id`, `apartment_id`, `assigned_at`) VALUES
(2, 4, '2026-09-01 15:44:00'),
(3, 1, '2026-09-08 16:39:14'),
(4, 3, '2026-08-31 07:59:27'),
(5, 2, '2026-08-31 10:10:11'),
(13, 6, '2026-09-08 13:06:52');

-- --------------------------------------------------------

--
-- Table structure for table `caretaker_assignments`
--

CREATE TABLE `caretaker_assignments` (
  `id` int(10) UNSIGNED NOT NULL,
  `caretaker_id` int(10) UNSIGNED NOT NULL,
  `apartment_id` int(10) UNSIGNED NOT NULL,
  `status` enum('active','inactive') NOT NULL DEFAULT 'active',
  `assigned_date` date DEFAULT NULL,
  `removed_date` date DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `caretaker_assignments`
--

INSERT INTO `caretaker_assignments` (`id`, `caretaker_id`, `apartment_id`, `status`, `assigned_date`, `removed_date`, `notes`, `created_at`) VALUES
(2, 4, 3, 'active', '2026-08-31', NULL, NULL, '2026-08-31 07:59:27'),
(4, 5, 2, 'active', '2026-08-31', NULL, NULL, '2026-08-31 10:10:11'),
(16, 2, 4, 'active', '2026-09-01', NULL, NULL, '2026-09-01 15:44:00'),
(28, 13, 6, 'active', '2026-09-08', NULL, NULL, '2026-09-08 13:06:52'),
(31, 3, 1, 'active', '2026-06-06', NULL, NULL, '2026-09-08 16:39:13');

-- --------------------------------------------------------

--
-- Table structure for table `caretaker_history`
--

CREATE TABLE `caretaker_history` (
  `id` int(10) UNSIGNED NOT NULL,
  `full_name` varchar(120) NOT NULL,
  `email` varchar(180) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `id_number` varchar(30) DEFAULT NULL,
  `apartment_name` varchar(120) DEFAULT NULL COMMENT 'Name at time of removal',
  `apartment_id` int(10) UNSIGNED DEFAULT NULL COMMENT 'Original FK (may be deleted)',
  `assigned_date` date DEFAULT NULL,
  `removed_date` date DEFAULT NULL,
  `removal_reason` text DEFAULT NULL,
  `removed_by_name` varchar(120) DEFAULT NULL COMMENT 'Admin who removed them',
  `original_user_id` int(10) UNSIGNED DEFAULT NULL COMMENT 'Was users.id',
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `caretaker_history`
--

INSERT INTO `caretaker_history` (`id`, `full_name`, `email`, `phone`, `id_number`, `apartment_name`, `apartment_id`, `assigned_date`, `removed_date`, `removal_reason`, `removed_by_name`, `original_user_id`, `created_at`) VALUES
(2, 'Jecinta njoki', 'njoki@gmail.com', '0789542445', NULL, 'xyn', 8, '2026-09-02', '2026-09-02', 'Resigned', 'Cynthia Wafula', 8, '2026-09-02 18:27:48');

-- --------------------------------------------------------

--
-- Table structure for table `maintenance_logs`
--

CREATE TABLE `maintenance_logs` (
  `id` int(10) UNSIGNED NOT NULL,
  `request_id` int(10) UNSIGNED NOT NULL,
  `updated_by` int(10) UNSIGNED DEFAULT NULL,
  `status` varchar(40) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `maintenance_logs`
--

INSERT INTO `maintenance_logs` (`id`, `request_id`, `updated_by`, `status`, `notes`, `created_at`) VALUES
(1, 2, 5, 'Pending', 'Locks replaced', '2026-09-01 18:32:08'),
(2, 2, 5, 'Resolved', 'Other locks in the house are working well', '2026-09-01 18:32:52'),
(3, 3, 13, 'In Progress', 'Started work.', '2026-09-08 14:53:43'),
(4, 3, 13, 'In Progress', 'Maintenance inspection is in progress. The sink drain pipe has been identified as the source of a severe leak, with water continuously pooling underneath the sink. Repair or replacement of the faulty connection is currently being assessed.', '2026-09-08 15:00:32'),
(5, 3, 13, 'In Progress', 'The replacement pipe has been purchased. Repair work is ready to proceed once installation is scheduled.', '2026-09-08 15:04:26');

-- --------------------------------------------------------

--
-- Table structure for table `maintenance_requests`
--

CREATE TABLE `maintenance_requests` (
  `id` int(10) UNSIGNED NOT NULL,
  `tenant_id` int(10) UNSIGNED NOT NULL,
  `unit_id` int(10) UNSIGNED DEFAULT NULL,
  `category` enum('Plumbing','Electrical','Structural / Building','Appliances','Security / Lock','Pest Control','Cleaning / Sanitation','Other') NOT NULL DEFAULT 'Other',
  `priority` enum('Normal','Urgent','Emergency') NOT NULL DEFAULT 'Normal',
  `title` varchar(200) NOT NULL,
  `description` text NOT NULL,
  `preferred_time` varchar(60) DEFAULT NULL,
  `photos` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL COMMENT 'Array of image paths' CHECK (json_valid(`photos`)),
  `status` enum('Pending','In Progress','Resolved','Cancelled') NOT NULL DEFAULT 'Pending',
  `caretaker_notes` text DEFAULT NULL,
  `resolved_at` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT NULL ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `maintenance_requests`
--

INSERT INTO `maintenance_requests` (`id`, `tenant_id`, `unit_id`, `category`, `priority`, `title`, `description`, `preferred_time`, `photos`, `status`, `caretaker_notes`, `resolved_at`, `created_at`, `updated_at`) VALUES
(2, 8, 1, 'Other', 'Normal', 'Locks not working', 'My bathroom and kitchen doors are not Locking', 'Anytime', '[]', 'Resolved', 'Other locks in the house are working well', '2026-09-01 18:32:52', '2026-08-31 13:23:14', '2026-09-01 18:32:52'),
(3, 14, 285, 'Plumbing', 'Urgent', 'My kitchen sink is leaking', 'Severe leak from the sink drain pipe underneath. Water is leaking continuously and pooling on the floor, creating a risk of water damage.', 'Anytime', '[\"assets\\/images\\/maintenance\\/maint_1788867713_b330c382.jpg\"]', 'In Progress', 'The replacement pipe has been purchased. Repair work is ready to proceed once installation is scheduled.', NULL, '2026-09-08 14:41:53', '2026-09-08 15:04:26');

-- --------------------------------------------------------

--
-- Table structure for table `messages`
--

CREATE TABLE `messages` (
  `id` int(10) UNSIGNED NOT NULL,
  `from_user_id` int(10) UNSIGNED NOT NULL,
  `to_user_id` int(10) UNSIGNED NOT NULL,
  `subject` varchar(200) NOT NULL,
  `body` text NOT NULL,
  `is_read` tinyint(1) NOT NULL DEFAULT 0,
  `read_at` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `messages`
--

INSERT INTO `messages` (`id`, `from_user_id`, `to_user_id`, `subject`, `body`, `is_read`, `read_at`, `created_at`) VALUES
(1, 8, 5, 'rent', 'Why is my rent not reflecting yet', 0, NULL, '2026-09-01 09:21:08');

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

CREATE TABLE `notifications` (
  `id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `type` varchar(50) NOT NULL DEFAULT 'general',
  `title` varchar(200) NOT NULL,
  `body` text DEFAULT NULL,
  `link` varchar(255) DEFAULT NULL,
  `is_read` tinyint(1) NOT NULL DEFAULT 0,
  `read_at` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `notifications`
--

INSERT INTO `notifications` (`id`, `user_id`, `type`, `title`, `body`, `link`, `is_read`, `read_at`, `created_at`) VALUES
(1, 1, 'caretaker_registration', 'New Caretaker Registration', 'Nicholas Mwiti has registered as a caretaker and is awaiting approval.', 'admin/caretakers.php', 1, '2026-09-08 15:24:47', '2026-08-29 15:19:00'),
(2, 8, 'payment_received', 'Payment confirmed — August 2026', 'Your rent payment of KES 45,000 for August 2026 has been confirmed.', 'tenant/payments.php', 0, NULL, '2026-08-31 08:08:21'),
(3, 1, 'caretaker_registration', 'New Caretaker Registration', 'Phillip Njugi has registered as a caretaker and is awaiting approval.', 'admin/caretakers.php', 1, '2026-09-08 15:24:47', '2026-08-31 10:08:50'),
(4, 5, 'assignment', 'You have been assigned to an apartment', 'Your apartment assignment has been updated. Log in to view your dashboard.', 'caretaker/dashboard.php', 0, NULL, '2026-08-31 10:10:11'),
(5, 8, 'move_in', 'Welcome to your new unit!', 'You have been assigned to Unit A1. Move-in date: 31 August 2026.', 'tenant/my_unit.php', 0, NULL, '2026-08-31 10:29:06'),
(6, 1, 'move_in', 'Tenant moved in — Unit A1', 'Leila Kerubo moved into Unit A1 on 31 August 2026.', 'admin/tenants.php?view=3', 1, '2026-09-08 15:24:47', '2026-08-31 10:29:06'),
(7, 6, 'move_in', 'Welcome to your new unit!', 'You have been assigned to Unit B3. Move-in date: 11 August 2026.', 'tenant/my_unit.php', 0, NULL, '2026-08-31 12:51:06'),
(8, 1, 'move_in', 'Tenant moved in — Unit B3', 'Shaline Martha moved into Unit B3 on 11 August 2026.', 'admin/tenants.php?view=6', 1, '2026-09-08 15:24:47', '2026-08-31 12:51:06'),
(9, 8, 'utility_bill', 'New utility bill — Garbage', 'A Garbage bill of KES 500 for September 2026 has been issued. Due: 31 October 2026.', 'tenant/expenses.php', 0, NULL, '2026-09-01 07:28:55'),
(10, 5, 'payment_pending', 'New payment submission', 'Tenant Leila Kerubo submitted a Utility payment of KES 500 for verification.', 'caretaker/rent.php', 0, NULL, '2026-09-01 07:58:59'),
(11, 1, 'payment_pending', 'New payment submission', 'Tenant Leila Kerubo submitted a Utility payment of KES 500 for verification.', 'admin/payments.php', 1, '2026-09-08 15:24:47', '2026-09-01 07:58:59'),
(12, 5, 'new_message', 'New Message from Leila Kerubo', 'Why is my rent not reflecting yet', 'caretaker/messages.php?view=1', 0, NULL, '2026-09-01 09:21:08'),
(13, 6, 'payment_received', 'Payment confirmed — September 2026', 'Your rent payment of KES 500,000 for September 2026 has been confirmed.', 'tenant/payments.php', 0, NULL, '2026-09-01 10:29:22'),
(14, 8, 'payment_received', 'Payment confirmed — September 2026', 'Your rent of KES 500 for September 2026 has been confirmed.', 'tenant/payments.php', 0, NULL, '2026-09-01 13:54:28'),
(15, 5, 'payment_pending', 'New payment submission', 'Tenant Leila Kerubo submitted a Rent payment of KES 45,000 for verification.', 'caretaker/rent.php', 0, NULL, '2026-09-01 17:20:17'),
(16, 1, 'payment_pending', 'New payment submission', 'Tenant Leila Kerubo submitted a Rent payment of KES 45,000 for verification.', 'admin/payments.php', 1, '2026-09-08 15:24:47', '2026-09-01 17:20:17'),
(17, 8, 'maintenance_resolved', 'Maintenance request resolved', 'Your maintenance request has been resolved. Note: Other locks in the house are working well', 'tenant/maintenance.php', 0, NULL, '2026-09-01 18:32:52'),
(18, 6, 'utility_bill', 'New utility bill — Electricity', 'A Electricity bill of KES 1,000 for September 2026 has been issued. Due: 30 September 2026.', 'tenant/expenses.php', 0, NULL, '2026-09-01 18:55:00'),
(19, 8, 'payment_received', 'Payment confirmed — September 2026', 'Your rent of KES 45,000 for September 2026 has been confirmed.', 'tenant/payments.php', 0, NULL, '2026-09-01 19:48:52'),
(20, 6, 'utility_bill_confirmed', 'Utility bill confirmed — September 2026', 'Your Electricity bill of KES 1,000 for September 2026 has been confirmed.', 'tenant/payments.php', 0, NULL, '2026-09-01 19:49:42'),
(21, 8, 'utility_bill_confirmed', 'Utility bill confirmed — September 2026', 'Your Garbage bill of KES 500 for September 2026 has been confirmed.', 'tenant/payments.php', 0, NULL, '2026-09-01 19:49:53'),
(22, 2, 'tenant_registration', 'New tenant application', 'Antonate Moraa applied for Wanjee Apartments. Review the application and approve or reject it.', '../caretaker/tenants.php?tab=pending', 0, NULL, '2026-09-03 07:14:25'),
(23, 7, 'move_in', 'Welcome to your new unit!', 'You have been assigned to Unit 12. Move-in date: 15 September 2026.', '../tenant/my_unit.php', 0, NULL, '2026-09-03 07:24:35'),
(24, 4, 'tenant_registration', 'New tenant application', 'Anne Wangoi applied for Penuel Apartments. Review the application and approve or reject it.', '../caretaker/tenants.php?tab=pending', 0, NULL, '2026-09-08 09:45:18'),
(27, 1, 'move_in', 'Tenant moved in — Unit 103', 'Anne Wangoi moved into Unit 103 on 8 September 2026.', '../admin/tenants.php?view=11', 1, '2026-09-08 15:24:47', '2026-09-08 10:33:15'),
(28, 4, 'tenant_registration', 'New tenant application', 'Anne Wangoi applied for Penuel Apartments. Review the application and approve or reject it.', '../caretaker/tenants.php?tab=pending', 0, NULL, '2026-09-08 11:14:22'),
(29, 12, 'application_approved', 'Your tenant application has been approved!', 'Your application has been approved. A unit will be assigned to you, after which you can submit your deposit payment proof.', '../tenant/dashboard.php', 0, NULL, '2026-09-08 11:14:45'),
(30, 12, 'move_in', 'Unit assigned — deposit required', 'You have been assigned to Unit 12. Submit your deposit payment amount for administrator verification.', '../tenant/pay_now.php', 0, NULL, '2026-09-08 11:15:04'),
(31, 1, 'move_in', 'Tenant moved in — Unit 12', 'Anne Wangoi moved into Unit 12 on 8 September 2026.', '../admin/tenants.php?view=12', 1, '2026-09-08 15:24:47', '2026-09-08 11:15:04'),
(32, 12, 'payment_received', 'Deposit submitted for verification', 'Your deposit payment proof was submitted and is awaiting administrator verification.', '../tenant/payments.php', 0, NULL, '2026-09-08 11:22:42'),
(33, 4, 'payment_pending', 'New payment submission', 'Tenant Anne Wangoi submitted a Deposit payment of KES 15,000 for verification.', 'caretaker/rent.php', 0, NULL, '2026-09-08 11:22:42'),
(34, 1, 'payment_pending', 'New payment submission', 'Tenant Anne Wangoi submitted a Deposit payment of KES 15,000 for verification.', 'admin/payments.php', 1, '2026-09-08 15:24:47', '2026-09-08 11:22:42'),
(35, 12, 'payment_received', 'Deposit verified and account activated', 'Your deposit payment was verified by the administrator. Your tenant account is now active.', '../tenant/dashboard.php', 0, NULL, '2026-09-08 11:34:59'),
(36, 12, 'payment_received', 'Payment confirmed — September 2026', 'Your rent of KES 15,000 for September 2026 has been confirmed.', 'tenant/payments.php', 0, NULL, '2026-09-08 11:34:59'),
(37, 1, 'tenant_registration', 'Tenant application needs review', 'Daniel Muasya applied for xyn, which has no assigned caretaker.', '../admin/tenants.php?status=pending_approval', 1, '2026-09-08 15:24:47', '2026-09-08 13:03:08'),
(38, 13, 'assignment', 'You have been assigned to an apartment', 'Your apartment assignment has been updated. Log in to view your dashboard.', 'caretaker/dashboard.php', 0, NULL, '2026-09-08 13:06:52'),
(39, 4, 'payment_pending', 'New payment submission', 'Tenant Anne Wangoi submitted a Rent payment of KES 35,000 for verification.', 'caretaker/rent.php', 0, NULL, '2026-09-08 13:08:59'),
(40, 1, 'payment_pending', 'New payment submission', 'Tenant Anne Wangoi submitted a Rent payment of KES 35,000 for verification.', 'admin/payments.php', 1, '2026-09-08 15:24:47', '2026-09-08 13:08:59'),
(41, 12, 'payment_received', 'Payment confirmed — September 2026', 'Your rent of KES 35,000 for September 2026 has been confirmed.', 'tenant/payments.php', 0, NULL, '2026-09-08 13:11:36'),
(42, 14, 'application_approved', 'Your tenant application has been approved!', 'Your application has been approved. A unit will be assigned to you, after which you can submit your deposit payment proof.', '../tenant/dashboard.php', 1, '2026-09-08 14:47:42', '2026-09-08 13:14:58'),
(43, 14, 'move_in', 'Unit assigned — deposit required', 'You have been assigned to Unit G01. Submit your deposit payment amount for administrator verification.', '../tenant/pay_now.php', 1, '2026-09-08 14:42:38', '2026-09-08 13:15:43'),
(44, 1, 'move_in', 'Tenant moved in — Unit G01', 'Daniel Muasya moved into Unit G01 on 8 September 2026.', '../admin/tenants.php?view=14', 1, '2026-09-08 15:24:47', '2026-09-08 13:15:43'),
(45, 14, 'payment_received', 'Deposit submitted for verification', 'Your deposit payment proof was submitted and is awaiting administrator verification.', '../tenant/payments.php', 1, '2026-09-08 14:42:30', '2026-09-08 13:17:28'),
(46, 13, 'payment_pending', 'New payment submission', 'Tenant Daniel Muasya submitted a Deposit payment of KES 150,000 for verification.', 'caretaker/rent.php', 0, NULL, '2026-09-08 13:17:28'),
(47, 1, 'payment_pending', 'New payment submission', 'Tenant Daniel Muasya submitted a Deposit payment of KES 150,000 for verification.', 'admin/payments.php', 1, '2026-09-08 15:24:47', '2026-09-08 13:17:28'),
(48, 14, 'payment_received', 'Deposit verified and account activated', 'Your deposit payment was verified by the administrator. Your tenant account is now active.', '../tenant/dashboard.php', 1, '2026-09-08 14:42:42', '2026-09-08 13:24:47'),
(49, 14, 'payment_received', 'Payment confirmed — September 2026', 'Your rent of KES 150,000 for September 2026 has been confirmed.', 'tenant/payments.php', 1, '2026-09-08 14:42:40', '2026-09-08 13:24:47'),
(50, 13, 'payment_pending', 'New payment submission', 'Tenant Daniel Muasya submitted a Rent payment of KES 400,000 for verification.', 'caretaker/rent.php', 0, NULL, '2026-09-08 13:27:33'),
(51, 1, 'payment_pending', 'New payment submission', 'Tenant Daniel Muasya submitted a Rent payment of KES 400,000 for verification.', 'admin/payments.php', 1, '2026-09-08 15:24:47', '2026-09-08 13:27:33'),
(52, 14, 'payment_received', 'Payment confirmed — September 2026', 'Your rent of KES 400,000 for September 2026 has been confirmed.', 'tenant/payments.php', 1, '2026-09-08 14:42:44', '2026-09-08 13:51:03'),
(53, 1, 'expense_reported', 'Maintenance expense reported', 'Benjamin Otieno reported a Plumbing expense of KES 3,500 for xyn.', 'admin/expenses.php', 1, '2026-09-08 15:24:47', '2026-09-08 15:05:41'),
(54, 3, 'assignment', 'You have been assigned to an apartment', 'Your apartment assignment has been updated. Log in to view your dashboard.', 'caretaker/dashboard.php', 0, NULL, '2026-09-08 17:54:57'),
(55, 3, 'assignment', 'You have been assigned to an apartment', 'Your apartment assignment has been updated. Log in to view your dashboard.', 'caretaker/dashboard.php', 0, NULL, '2026-09-08 17:55:25'),
(56, 3, 'assignment', 'You have been assigned to an apartment', 'Your apartment assignment has been updated. Log in to view your dashboard.', 'caretaker/dashboard.php', 0, NULL, '2026-09-08 18:02:07'),
(57, 3, 'assignment', 'You have been assigned to an apartment', 'Your apartment assignment has been updated. Log in to view your dashboard.', 'caretaker/dashboard.php', 0, NULL, '2026-09-08 18:03:32'),
(58, 3, 'assignment', 'You have been assigned to an apartment', 'Your apartment assignment has been updated. Log in to view your dashboard.', 'caretaker/dashboard.php', 0, NULL, '2026-09-08 19:09:33'),
(59, 3, 'tenant_registration', 'New tenant application', 'Kelsey Namaswa applied for Kazi house. Review the application and approve or reject it.', '../caretaker/tenants.php?tab=pending', 0, NULL, '2026-09-08 19:52:18'),
(60, 16, 'application_approved', 'Your tenant application has been approved!', 'Your application has been approved. A unit will be assigned to you, after which you can submit your deposit payment proof.', '../tenant/dashboard.php', 1, '2026-09-08 20:41:43', '2026-09-08 19:52:56'),
(61, 16, 'move_in', 'Unit assigned — deposit required', 'You have been assigned to Unit 301. Submit your deposit payment amount for administrator verification.', '../tenant/pay_now.php', 1, '2026-09-08 20:41:43', '2026-09-08 19:54:03'),
(62, 1, 'move_in', 'Tenant moved in — Unit 301', 'Kelsey Namaswa moved into Unit 301 on 8 July 2026.', '../admin/tenants.php?view=16', 0, NULL, '2026-09-08 19:54:03'),
(63, 16, 'payment_received', 'Deposit submitted for verification', 'Your deposit payment proof was submitted and is awaiting administrator verification.', '../tenant/payments.php', 1, '2026-09-08 20:41:43', '2026-09-08 19:56:45'),
(64, 3, 'payment_pending', 'New payment submission', 'Tenant Kelsey Namaswa submitted a Deposit payment of KES 50,000 for verification.', 'caretaker/rent.php', 0, NULL, '2026-09-08 19:56:45'),
(65, 1, 'payment_pending', 'New payment submission', 'Tenant Kelsey Namaswa submitted a Deposit payment of KES 50,000 for verification.', 'admin/payments.php', 0, NULL, '2026-09-08 19:56:45'),
(66, 16, 'payment_received', 'Deposit verified and account activated', 'Your deposit payment was verified by the administrator. Your tenant account is now active.', '../tenant/dashboard.php', 1, '2026-09-08 20:41:43', '2026-09-08 20:01:24'),
(67, 16, 'payment_received', 'Payment confirmed — September 2026', 'Your rent of KES 50,000 for September 2026 has been confirmed.', 'tenant/payments.php', 1, '2026-09-08 20:41:43', '2026-09-08 20:01:24'),
(68, 14, 'utility_bill', 'New utility bill — Water', 'A Water bill of KES 5,000 for September 2026 has been issued. Due: 30 September 2026.', 'tenant/expenses.php', 0, NULL, '2026-09-15 19:06:32'),
(69, 1, 'tenant_registration', 'Tenant application needs review', 'Bhakita Namaemba applied for Shalom apartments, which has no assigned caretaker.', '../admin/tenants.php?status=pending_approval', 0, NULL, '2026-09-15 22:42:38'),
(70, 17, 'application_approved', 'Your tenant application has been approved!', 'Your application has been approved. A unit will be assigned to you, after which you can submit your deposit payment proof.', '../tenant/dashboard.php', 0, NULL, '2026-09-15 22:44:50');

-- --------------------------------------------------------

--
-- Table structure for table `password_resets`
--

CREATE TABLE `password_resets` (
  `id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `token_hash` char(64) NOT NULL COMMENT 'SHA-256 hash of the raw token',
  `expires_at` datetime NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `password_resets`
--

INSERT INTO `password_resets` (`id`, `user_id`, `token_hash`, `expires_at`, `created_at`) VALUES
(1, 2, '18f8dcefd9832e2e7df598e3b79d1632d8a664fab4bd89509f5a271931b2d3c3', '2026-09-01 13:10:22', '2026-08-29 14:10:22'),
(2, 1, '1b271a4c4c72cbb1b13e6b28ef586c2d331a4d5d14f4cd8bbc363079f7833530', '2026-09-02 16:34:42', '2026-09-02 16:31:50');

-- --------------------------------------------------------

--
-- Table structure for table `payments`
--

CREATE TABLE `payments` (
  `id` int(10) UNSIGNED NOT NULL,
  `tenant_id` int(10) UNSIGNED NOT NULL,
  `payment_type` enum('Rent','Deposit','Utility','Other') NOT NULL DEFAULT 'Rent',
  `unit_id` int(10) UNSIGNED DEFAULT NULL,
  `apartment_id` int(10) UNSIGNED DEFAULT NULL,
  `month` enum('January','February','March','April','May','June','July','August','September','October','November','December') NOT NULL,
  `year` year(4) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `expected_amount` decimal(10,2) DEFAULT NULL COMMENT 'Expected rent',
  `balance` decimal(10,2) DEFAULT 0.00 COMMENT 'Remaining balance',
  `carried_credit` decimal(10,2) DEFAULT 0.00 COMMENT 'Credit from previous month',
  `payment_method` enum('M-PESA','M-PESA Paybill','Airtel Money','PDQ/POS','Bank Transfer','Cash','Cheque','Other') NOT NULL DEFAULT 'M-PESA',
  `reference_number` varchar(80) DEFAULT NULL COMMENT 'M-PESA/Airtel transaction ID',
  `receipt_number` varchar(80) DEFAULT NULL COMMENT 'System-generated receipt reference',
  `cheque_number` varchar(60) DEFAULT NULL,
  `cheque_bank` varchar(80) DEFAULT NULL,
  `cheque_status` enum('Pending','Cleared','Bounced') DEFAULT NULL,
  `payment_date` date DEFAULT NULL,
  `status` enum('Paid','Pending','Partial','Overdue','Waived') NOT NULL DEFAULT 'Pending',
  `notes` text DEFAULT NULL,
  `recorded_by` int(10) UNSIGNED DEFAULT NULL COMMENT 'FK → users (admin/caretaker)',
  `confirmed_by` int(10) UNSIGNED DEFAULT NULL COMMENT 'FK ??? users (who confirmed this payment)',
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT NULL ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `payments`
--

INSERT INTO `payments` (`id`, `tenant_id`, `payment_type`, `unit_id`, `apartment_id`, `month`, `year`, `amount`, `expected_amount`, `balance`, `carried_credit`, `payment_method`, `reference_number`, `receipt_number`, `cheque_number`, `cheque_bank`, `cheque_status`, `payment_date`, `status`, `notes`, `recorded_by`, `confirmed_by`, `created_at`, `updated_at`) VALUES
(1, 8, 'Rent', NULL, NULL, 'August', '2026', 45000.00, NULL, 0.00, 0.00, 'Cash', NULL, NULL, NULL, NULL, NULL, '2026-08-31', 'Paid', '', 1, NULL, '2026-08-31 08:08:21', '2026-09-08 13:59:51'),
(2, 8, 'Utility', 1, 2, 'September', '2026', 500.00, NULL, 0.00, 0.00, 'M-PESA Paybill', 'M-PESA Paybill', NULL, NULL, NULL, NULL, '2026-09-01', 'Paid', '[Water Utility]  [UTILITY PAYMENT - Submitted by tenant]', NULL, 1, '2026-09-01 07:58:59', '2026-09-01 13:54:28'),
(5, 6, 'Rent', 5, 3, 'September', '2026', 500000.00, 500000.00, 0.00, 0.00, 'Bank Transfer', NULL, NULL, NULL, NULL, NULL, '2026-09-01', 'Paid', '', 1, NULL, '2026-09-01 10:29:22', '2026-09-08 13:59:51'),
(6, 8, 'Rent', 1, 2, 'September', '2026', 45000.00, NULL, 0.00, 0.00, 'Airtel Money', 'Airtel Money', NULL, NULL, NULL, NULL, '2026-09-01', 'Paid', ' [Submitted by tenant]', NULL, 1, '2026-09-01 17:20:17', '2026-09-01 19:48:52'),
(7, 12, 'Deposit', 45, 3, 'September', '2026', 15000.00, NULL, 0.00, 0.00, 'Bank Transfer', 'Bank: 2345667778', NULL, NULL, NULL, NULL, '2026-09-08', 'Paid', ' [DEPOSIT PAYMENT - Submitted by tenant]', NULL, 1, '2026-09-08 11:22:42', '2026-09-08 11:34:59'),
(11, 12, 'Rent', 45, 3, 'September', '2026', 35000.00, NULL, 0.00, 0.00, 'Bank Transfer', 'Bank: 2345667778', NULL, NULL, NULL, NULL, '2026-09-08', 'Paid', ' [Submitted by tenant]', NULL, 1, '2026-09-08 13:08:59', '2026-09-08 13:11:36'),
(12, 14, 'Deposit', 285, 6, 'September', '2026', 150000.00, NULL, 0.00, 0.00, 'Bank Transfer', 'Bank: 1029394756', NULL, NULL, NULL, NULL, '2026-09-08', 'Paid', ' [DEPOSIT PAYMENT - Submitted by tenant]', NULL, 1, '2026-09-08 13:17:28', '2026-09-08 13:24:47'),
(13, 14, 'Rent', 285, 6, 'September', '2026', 400000.00, NULL, 0.00, 0.00, 'Bank Transfer', 'Bank: 1029394756', NULL, NULL, NULL, NULL, '2026-09-08', 'Paid', ' [Submitted by tenant]', NULL, 1, '2026-09-08 13:27:33', '2026-09-08 13:51:03'),
(14, 16, 'Deposit', 185, 1, 'July', '2026', 50000.00, NULL, 0.00, 0.00, 'Bank Transfer', 'Bank: 192837465', NULL, NULL, NULL, NULL, '2026-07-08', 'Paid', ' [DEPOSIT PAYMENT - Submitted by tenant]', NULL, 1, '2026-09-08 19:56:45', '2026-09-08 20:06:13'),
(15, 16, 'Rent', 185, 1, 'July', '2026', 100000.00, 100000.00, 0.00, 0.00, 'Bank Transfer', 'Historical July rent', NULL, NULL, NULL, NULL, '2026-07-08', 'Paid', 'Paid on time', 1, 1, '2026-09-08 20:22:34', NULL),
(16, 16, 'Rent', 185, 1, 'August', '2026', 110000.00, 100000.00, -10000.00, 0.00, 'Bank Transfer', 'Historical August rent plus KES 10,000 credit', NULL, NULL, NULL, NULL, '2026-08-05', 'Paid', 'Paid on time; KES 10,000 credit carried to September rent', 1, 1, '2026-09-08 20:22:34', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `remember_tokens`
--

CREATE TABLE `remember_tokens` (
  `id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `token_hash` char(64) NOT NULL,
  `expires_at` datetime NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `shared_expenses`
--

CREATE TABLE `shared_expenses` (
  `id` int(10) UNSIGNED NOT NULL,
  `apartment_id` int(10) UNSIGNED DEFAULT NULL,
  `unit_id` int(10) UNSIGNED DEFAULT NULL COMMENT 'NULL = apartment-wide charge',
  `tenant_id` int(10) UNSIGNED DEFAULT NULL,
  `expense_type` enum('Plumbing','Electrical','Structural','Appliances','Security','Cleaning','Pest Control','Locks & Keys','Painting','General Maintenance','Other') NOT NULL DEFAULT 'Other',
  `cost_category` enum('Labour','Materials','Equipment','Contractor','Permit','Other') NOT NULL DEFAULT 'Other' COMMENT 'What the money was spent on',
  `vendor` varchar(120) DEFAULT NULL COMMENT 'Supplier, technician or contractor name',
  `paid_by` int(10) UNSIGNED DEFAULT NULL COMMENT 'FK to users ? admin/caretaker who made the payment',
  `receipt_ref` varchar(80) DEFAULT NULL COMMENT 'Receipt or invoice number',
  `expense_date` date DEFAULT NULL COMMENT 'Actual date money was spent',
  `description` varchar(255) DEFAULT NULL,
  `amount` decimal(10,2) NOT NULL,
  `month` enum('January','February','March','April','May','June','July','August','September','October','November','December') NOT NULL,
  `year` year(4) NOT NULL,
  `due_date` date DEFAULT NULL,
  `status` enum('Paid','Pending','Cancelled') NOT NULL DEFAULT 'Paid',
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `shared_expenses`
--

INSERT INTO `shared_expenses` (`id`, `apartment_id`, `unit_id`, `tenant_id`, `expense_type`, `cost_category`, `vendor`, `paid_by`, `receipt_ref`, `expense_date`, `description`, `amount`, `month`, `year`, `due_date`, `status`, `created_at`) VALUES
(2, 6, NULL, NULL, 'Plumbing', 'Labour', NULL, 2, NULL, '2026-09-08', 'The replacement pipe has been purchased. Repair work is ready to proceed once installation is scheduled.', 3500.00, 'September', '2026', NULL, 'Paid', '2026-09-08 15:07:59');

-- --------------------------------------------------------

--
-- Table structure for table `system_settings`
--

CREATE TABLE `system_settings` (
  `id` int(10) UNSIGNED NOT NULL,
  `key` varchar(80) NOT NULL,
  `value` text DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `system_settings`
--

INSERT INTO `system_settings` (`id`, `key`, `value`, `updated_at`) VALUES
(1, 'system_name', 'Majumba properties', '2026-08-29 15:04:49'),
(2, 'currency', 'USD', '2026-09-08 15:43:09'),
(3, 'date_format', 'D M Y', NULL),
(4, 'rent_due_day', '5', NULL),
(5, 'penalty_percent', '10', NULL),
(7, 'timezone', 'Africa/Nairobi', NULL),
(8, 'version', '1.0.0', NULL),
(11, 'paybill_number', '522533', NULL),
(25, 'backend_created_date', '2026-06-03', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `tenant_units`
--

CREATE TABLE `tenant_units` (
  `id` int(10) UNSIGNED NOT NULL,
  `tenant_id` int(10) UNSIGNED NOT NULL,
  `unit_id` int(10) UNSIGNED NOT NULL,
  `move_in_date` date DEFAULT NULL COMMENT 'Actual move-in date',
  `move_out_date` date DEFAULT NULL COMMENT 'Actual move-out date',
  `lease_start` date DEFAULT NULL,
  `lease_end` date DEFAULT NULL,
  `monthly_rent` decimal(10,2) DEFAULT NULL COMMENT 'Rent locked in at time of assignment',
  `deposit_amount` decimal(10,2) DEFAULT 0.00 COMMENT 'Deposit amount',
  `deposit_paid` tinyint(1) DEFAULT 0 COMMENT '1=paid, 0=not paid',
  `deposit_paid_date` date DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `status` enum('active','moved_out','terminated') NOT NULL DEFAULT 'active',
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT NULL ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `tenant_units`
--

INSERT INTO `tenant_units` (`id`, `tenant_id`, `unit_id`, `move_in_date`, `move_out_date`, `lease_start`, `lease_end`, `monthly_rent`, `deposit_amount`, `deposit_paid`, `deposit_paid_date`, `notes`, `status`, `created_at`, `updated_at`) VALUES
(1, 8, 1, '2026-08-31', NULL, NULL, NULL, 45000.00, 0.00, 0, NULL, NULL, 'active', '2026-08-31 10:29:06', NULL),
(2, 6, 5, '2026-08-11', NULL, NULL, NULL, 500000.00, 0.00, 0, NULL, NULL, 'active', '2026-08-31 12:51:06', NULL),
(3, 7, 17, '2026-09-15', '2026-09-03', NULL, NULL, 0.00, 15001.00, 0, NULL, 'Reason: Other.', '', '2026-09-03 07:24:35', '2026-09-03 07:24:54'),
(5, 12, 45, '2026-09-08', NULL, NULL, NULL, 0.00, 15000.00, 1, '2026-09-08', NULL, 'active', '2026-09-08 11:15:04', '2026-09-08 11:34:59'),
(6, 14, 285, '2026-09-08', NULL, NULL, NULL, 0.00, 150000.00, 1, '2026-09-08', NULL, 'active', '2026-09-08 13:15:43', '2026-09-08 13:24:47'),
(8, 16, 185, '2026-07-08', NULL, NULL, NULL, 100000.00, 49981.00, 1, '2026-09-08', NULL, 'active', '2026-09-08 19:54:03', '2026-09-08 20:01:24');

-- --------------------------------------------------------

--
-- Table structure for table `units`
--

CREATE TABLE `units` (
  `id` int(10) UNSIGNED NOT NULL,
  `apartment_id` int(10) UNSIGNED NOT NULL,
  `unit_number` varchar(20) NOT NULL,
  `unit_type` enum('Bedsitter','Studio','Self-Contained','1 Bedroom','2 Bedroom','3 Bedroom','4 Bedroom','Penthouse','Maisonette','Shop','Office','Other') NOT NULL DEFAULT '1 Bedroom',
  `floor` tinyint(3) UNSIGNED DEFAULT 0,
  `bathrooms` tinyint(3) UNSIGNED DEFAULT 1,
  `monthly_rent` decimal(10,2) NOT NULL,
  `deposit` decimal(10,2) DEFAULT 0.00,
  `status` enum('Vacant','Occupied','Under Maintenance') NOT NULL DEFAULT 'Vacant',
  `features` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL COMMENT 'Array of amenity strings' CHECK (json_valid(`features`)),
  `notes` text DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT NULL ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `units`
--

INSERT INTO `units` (`id`, `apartment_id`, `unit_number`, `unit_type`, `floor`, `bathrooms`, `monthly_rent`, `deposit`, `status`, `features`, `notes`, `created_at`, `updated_at`) VALUES
(1, 2, 'A1', '4 Bedroom', 5, 2, 49000.00, 15000.00, 'Occupied', '[\"Balcony\",\"WiFi\"]', '', '2026-08-04 15:07:21', '2026-09-08 17:15:45'),
(2, 2, 'A2', '4 Bedroom', 5, 1, 49000.00, 15000.00, 'Vacant', '[\"Balcony\",\"WiFi\"]', '', '2026-08-04 15:07:21', '2026-09-08 17:15:45'),
(3, 3, 'B1', '3 Bedroom', 7, 3, 35000.00, 200000.00, 'Vacant', '[\"Balcony\",\"Parking\",\"Water Heater\",\"Furnished\",\"WiFi\",\"Garden View\",\"En-suite\",\"Storage\"]', '', '2026-08-31 07:59:27', '2026-09-08 17:15:45'),
(4, 3, 'B2', '3 Bedroom', 7, 3, 35000.00, 150000.00, 'Vacant', '[\"Balcony\",\"Parking\",\"Water Heater\",\"WiFi\"]', '', '2026-08-31 07:59:27', '2026-09-08 17:15:45'),
(5, 3, 'B3', 'Maisonette', 0, 1, 500000.00, 200000.00, 'Occupied', '[\"Balcony\",\"Parking\",\"Water Heater\",\"WiFi\",\"Garden View\",\"Storage\"]', '', '2026-08-31 07:59:27', '2026-09-08 17:15:45'),
(6, 2, '1', '4 Bedroom', 1, 1, 49000.00, 0.00, 'Vacant', '[]', '', '2026-08-04 15:07:21', '2026-09-08 17:15:45'),
(7, 2, '2', '4 Bedroom', 1, 1, 49000.00, 0.00, 'Vacant', '[]', '', '2026-08-04 15:07:21', '2026-09-08 17:15:45'),
(8, 2, '3', '4 Bedroom', 1, 1, 49000.00, 0.00, 'Vacant', '[]', '', '2026-08-04 15:07:21', '2026-09-08 17:15:45'),
(9, 2, '4', '4 Bedroom', 1, 1, 49000.00, 0.00, 'Vacant', NULL, NULL, '2026-08-04 15:07:21', '2026-09-08 17:15:45'),
(10, 2, '5', '4 Bedroom', 1, 1, 49000.00, 0.00, 'Vacant', NULL, NULL, '2026-08-04 15:07:21', '2026-09-08 17:15:45'),
(11, 2, '6', '4 Bedroom', 1, 1, 49000.00, 0.00, 'Vacant', NULL, NULL, '2026-08-04 15:07:21', '2026-09-08 17:15:45'),
(12, 2, '7', '4 Bedroom', 1, 1, 49000.00, 0.00, 'Vacant', NULL, NULL, '2026-08-04 15:07:21', '2026-09-08 17:15:45'),
(13, 2, '8', '4 Bedroom', 1, 1, 49000.00, 0.00, 'Vacant', NULL, NULL, '2026-08-04 15:07:21', '2026-09-08 17:15:45'),
(14, 2, '9', '4 Bedroom', 2, 1, 49000.00, 0.00, 'Vacant', NULL, NULL, '2026-08-04 15:07:21', '2026-09-08 17:15:45'),
(15, 2, '10', '4 Bedroom', 2, 1, 49000.00, 0.00, 'Vacant', NULL, NULL, '2026-08-04 15:07:21', '2026-09-08 17:15:45'),
(16, 2, '11', '4 Bedroom', 2, 1, 49000.00, 0.00, 'Vacant', NULL, NULL, '2026-08-04 15:07:21', '2026-09-08 17:15:45'),
(17, 2, '12', '4 Bedroom', 2, 1, 49000.00, 0.00, 'Vacant', NULL, NULL, '2026-08-04 15:07:21', '2026-09-08 17:15:45'),
(18, 2, '13', '4 Bedroom', 2, 1, 49000.00, 0.00, 'Vacant', NULL, NULL, '2026-08-04 15:07:21', '2026-09-08 17:15:45'),
(19, 2, '14', '4 Bedroom', 2, 1, 49000.00, 0.00, 'Vacant', NULL, NULL, '2026-08-04 15:07:21', '2026-09-08 17:15:45'),
(20, 2, '15', '4 Bedroom', 2, 1, 49000.00, 0.00, 'Vacant', NULL, NULL, '2026-08-04 15:07:21', '2026-09-08 17:15:45'),
(21, 2, '16', '4 Bedroom', 2, 1, 49000.00, 0.00, 'Vacant', NULL, NULL, '2026-08-04 15:07:21', '2026-09-08 17:15:45'),
(22, 2, '17', '4 Bedroom', 3, 1, 49000.00, 0.00, 'Vacant', NULL, NULL, '2026-08-04 15:07:21', '2026-09-08 17:15:45'),
(23, 2, '18', '4 Bedroom', 3, 1, 49000.00, 0.00, 'Vacant', NULL, NULL, '2026-08-04 15:07:21', '2026-09-08 17:15:45'),
(24, 2, '19', '4 Bedroom', 3, 1, 49000.00, 0.00, 'Vacant', NULL, NULL, '2026-08-04 15:07:21', '2026-09-08 17:15:45'),
(25, 2, '20', '4 Bedroom', 3, 1, 49000.00, 0.00, 'Vacant', NULL, NULL, '2026-08-04 15:07:21', '2026-09-08 17:15:45'),
(26, 2, '21', '4 Bedroom', 3, 1, 49000.00, 0.00, 'Vacant', NULL, NULL, '2026-08-04 15:07:21', '2026-09-08 17:15:45'),
(27, 2, '22', '4 Bedroom', 3, 1, 49000.00, 0.00, 'Vacant', NULL, NULL, '2026-08-04 15:07:21', '2026-09-08 17:15:45'),
(28, 2, '23', '4 Bedroom', 3, 1, 49000.00, 0.00, 'Vacant', NULL, NULL, '2026-08-04 15:07:21', '2026-09-08 17:15:45'),
(29, 2, '24', '4 Bedroom', 3, 1, 49000.00, 0.00, 'Vacant', NULL, NULL, '2026-08-04 15:07:21', '2026-09-08 17:15:45'),
(30, 2, '25', '4 Bedroom', 4, 1, 49000.00, 0.00, 'Vacant', NULL, NULL, '2026-08-04 15:07:21', '2026-09-08 17:15:45'),
(31, 2, '26', '4 Bedroom', 4, 1, 49000.00, 0.00, 'Vacant', NULL, NULL, '2026-08-04 15:07:21', '2026-09-08 17:15:45'),
(32, 2, '27', '4 Bedroom', 4, 1, 49000.00, 0.00, 'Vacant', NULL, NULL, '2026-08-04 15:07:21', '2026-09-08 17:15:45'),
(33, 2, '28', '4 Bedroom', 4, 1, 49000.00, 0.00, 'Vacant', NULL, NULL, '2026-08-04 15:07:21', '2026-09-08 17:15:45'),
(34, 3, '1', '3 Bedroom', 1, 1, 35000.00, 0.00, 'Vacant', '[]', '', '2026-08-31 07:59:27', '2026-09-08 17:15:45'),
(35, 3, '2', '3 Bedroom', 1, 1, 35000.00, 0.00, 'Vacant', NULL, NULL, '2026-08-31 07:59:27', '2026-09-08 17:15:45'),
(36, 3, '3', '3 Bedroom', 1, 1, 35000.00, 0.00, 'Vacant', NULL, NULL, '2026-08-31 07:59:27', '2026-09-08 17:15:45'),
(37, 3, '4', '3 Bedroom', 1, 1, 35000.00, 0.00, 'Vacant', '[]', '', '2026-08-31 07:59:27', '2026-09-08 17:15:45'),
(38, 3, '5', '3 Bedroom', 1, 1, 35000.00, 0.00, 'Vacant', NULL, NULL, '2026-08-31 07:59:27', '2026-09-08 17:15:45'),
(39, 3, '6', '3 Bedroom', 1, 1, 35000.00, 0.00, 'Vacant', NULL, NULL, '2026-08-31 07:59:27', '2026-09-08 17:15:45'),
(40, 3, '7', '3 Bedroom', 1, 1, 35000.00, 0.00, 'Vacant', NULL, NULL, '2026-08-31 07:59:27', '2026-09-08 17:15:45'),
(41, 3, '8', '3 Bedroom', 1, 1, 35000.00, 0.00, 'Vacant', '[]', '', '2026-08-31 07:59:27', '2026-09-08 17:15:45'),
(42, 3, '9', '3 Bedroom', 2, 1, 35000.00, 0.00, 'Vacant', NULL, NULL, '2026-08-31 07:59:27', '2026-09-08 17:15:45'),
(43, 3, '10', '3 Bedroom', 2, 1, 35000.00, 0.00, 'Vacant', NULL, NULL, '2026-08-31 07:59:27', '2026-09-08 17:15:45'),
(44, 3, '11', '3 Bedroom', 2, 1, 35000.00, 0.00, 'Vacant', NULL, NULL, '2026-08-31 07:59:27', '2026-09-08 17:15:45'),
(45, 3, '12', '3 Bedroom', 2, 1, 35000.00, 0.00, 'Occupied', NULL, NULL, '2026-08-31 07:59:27', '2026-09-08 17:15:45'),
(46, 3, '13', '3 Bedroom', 2, 1, 35000.00, 0.00, 'Vacant', NULL, NULL, '2026-08-31 07:59:27', '2026-09-08 17:15:45'),
(47, 3, '14', '3 Bedroom', 2, 1, 35000.00, 0.00, 'Vacant', NULL, NULL, '2026-08-31 07:59:27', '2026-09-08 17:15:45'),
(48, 3, '15', '3 Bedroom', 2, 1, 35000.00, 0.00, 'Vacant', NULL, NULL, '2026-08-31 07:59:27', '2026-09-08 17:15:45'),
(49, 3, '16', '3 Bedroom', 2, 1, 35000.00, 0.00, 'Vacant', NULL, NULL, '2026-08-31 07:59:27', '2026-09-08 17:15:45'),
(50, 3, '17', '3 Bedroom', 3, 1, 35000.00, 0.00, 'Vacant', NULL, NULL, '2026-08-31 07:59:27', '2026-09-08 17:15:45'),
(51, 3, '18', '3 Bedroom', 3, 1, 35000.00, 0.00, 'Vacant', NULL, NULL, '2026-08-31 07:59:27', '2026-09-08 17:15:45'),
(52, 3, '19', '3 Bedroom', 3, 1, 35000.00, 0.00, 'Vacant', NULL, NULL, '2026-08-31 07:59:27', '2026-09-08 17:15:45'),
(53, 3, '20', '3 Bedroom', 3, 1, 35000.00, 0.00, 'Vacant', NULL, NULL, '2026-08-31 07:59:27', '2026-09-08 17:15:45'),
(54, 3, '21', '3 Bedroom', 3, 1, 35000.00, 0.00, 'Vacant', NULL, NULL, '2026-08-31 07:59:27', '2026-09-08 17:15:45'),
(55, 3, '22', '3 Bedroom', 3, 1, 35000.00, 0.00, 'Vacant', NULL, NULL, '2026-08-31 07:59:27', '2026-09-08 17:15:45'),
(56, 3, '23', '3 Bedroom', 3, 1, 35000.00, 0.00, 'Vacant', NULL, NULL, '2026-08-31 07:59:27', '2026-09-08 17:15:45'),
(57, 3, '24', '3 Bedroom', 3, 1, 35000.00, 0.00, 'Vacant', NULL, NULL, '2026-08-31 07:59:27', '2026-09-08 17:15:45'),
(58, 3, '25', '3 Bedroom', 4, 1, 35000.00, 0.00, 'Vacant', NULL, NULL, '2026-08-31 07:59:27', '2026-09-08 17:15:45'),
(59, 3, '26', '3 Bedroom', 4, 1, 35000.00, 0.00, 'Vacant', NULL, NULL, '2026-08-31 07:59:27', '2026-09-08 17:15:45'),
(60, 3, '27', '3 Bedroom', 4, 1, 35000.00, 0.00, 'Vacant', NULL, NULL, '2026-08-31 07:59:27', '2026-09-08 17:15:45'),
(61, 3, '28', '3 Bedroom', 4, 1, 35000.00, 0.00, 'Vacant', NULL, NULL, '2026-08-31 07:59:27', '2026-09-08 17:15:45'),
(62, 3, '29', '3 Bedroom', 4, 1, 35000.00, 0.00, 'Vacant', NULL, NULL, '2026-08-31 07:59:27', '2026-09-08 17:15:45'),
(63, 3, '30', '3 Bedroom', 4, 1, 35000.00, 0.00, 'Vacant', NULL, NULL, '2026-08-31 07:59:27', '2026-09-08 17:15:45'),
(64, 3, '31', '3 Bedroom', 4, 1, 35000.00, 0.00, 'Vacant', NULL, NULL, '2026-08-31 07:59:27', '2026-09-08 17:15:45'),
(65, 3, '32', '3 Bedroom', 4, 1, 35000.00, 0.00, 'Vacant', NULL, NULL, '2026-08-31 07:59:27', '2026-09-08 17:15:45'),
(66, 3, '33', '3 Bedroom', 5, 1, 35000.00, 0.00, 'Vacant', NULL, NULL, '2026-08-31 07:59:27', '2026-09-08 17:15:45'),
(67, 3, '34', '3 Bedroom', 5, 1, 35000.00, 0.00, 'Vacant', NULL, NULL, '2026-08-31 07:59:27', '2026-09-08 17:15:45'),
(68, 3, '35', '3 Bedroom', 5, 1, 35000.00, 0.00, 'Vacant', NULL, NULL, '2026-08-31 07:59:27', '2026-09-08 17:15:45'),
(69, 3, '36', '3 Bedroom', 5, 1, 35000.00, 0.00, 'Vacant', NULL, NULL, '2026-08-31 07:59:27', '2026-09-08 17:15:45'),
(70, 3, '37', '3 Bedroom', 5, 1, 35000.00, 0.00, 'Vacant', NULL, NULL, '2026-08-31 07:59:27', '2026-09-08 17:15:45'),
(71, 3, '38', '3 Bedroom', 5, 1, 35000.00, 0.00, 'Vacant', NULL, NULL, '2026-08-31 07:59:27', '2026-09-08 17:15:45'),
(72, 3, '39', '3 Bedroom', 5, 1, 35000.00, 0.00, 'Vacant', NULL, NULL, '2026-08-31 07:59:27', '2026-09-08 17:15:45'),
(73, 3, '40', '3 Bedroom', 5, 1, 35000.00, 0.00, 'Vacant', NULL, NULL, '2026-08-31 07:59:27', '2026-09-08 17:15:45'),
(74, 3, '41', '3 Bedroom', 6, 1, 35000.00, 0.00, 'Vacant', NULL, NULL, '2026-08-31 07:59:27', '2026-09-08 17:15:45'),
(75, 3, '42', '3 Bedroom', 6, 1, 35000.00, 0.00, 'Vacant', NULL, NULL, '2026-08-31 07:59:27', '2026-09-08 17:15:45'),
(76, 3, '43', '3 Bedroom', 6, 1, 35000.00, 0.00, 'Vacant', NULL, NULL, '2026-08-31 07:59:27', '2026-09-08 17:15:45'),
(77, 3, '44', '3 Bedroom', 6, 1, 35000.00, 0.00, 'Vacant', NULL, NULL, '2026-08-31 07:59:27', '2026-09-08 17:15:45'),
(78, 3, '45', '3 Bedroom', 6, 1, 35000.00, 0.00, 'Vacant', NULL, NULL, '2026-08-31 07:59:27', '2026-09-08 17:15:45'),
(79, 3, '46', '3 Bedroom', 6, 1, 35000.00, 0.00, 'Vacant', NULL, NULL, '2026-08-31 07:59:27', '2026-09-08 17:15:45'),
(80, 3, '47', '3 Bedroom', 6, 1, 35000.00, 0.00, 'Vacant', NULL, NULL, '2026-08-31 07:59:27', '2026-09-08 17:15:45'),
(81, 3, '48', '3 Bedroom', 6, 1, 35000.00, 0.00, 'Vacant', NULL, NULL, '2026-08-31 07:59:27', '2026-09-08 17:15:45'),
(82, 3, '49', '3 Bedroom', 7, 1, 35000.00, 0.00, 'Vacant', NULL, NULL, '2026-08-31 07:59:27', '2026-09-08 17:15:45'),
(83, 3, '50', '3 Bedroom', 7, 1, 35000.00, 0.00, 'Vacant', NULL, NULL, '2026-08-31 07:59:27', '2026-09-08 17:15:45'),
(84, 4, '1', 'Bedsitter', 1, 1, 10000.00, 0.00, 'Vacant', NULL, NULL, '2026-09-01 15:44:00', '2026-09-08 12:08:10'),
(85, 4, '2', 'Bedsitter', 1, 1, 10000.00, 0.00, 'Vacant', NULL, NULL, '2026-09-01 15:44:00', '2026-09-08 12:08:10'),
(86, 4, '3', 'Bedsitter', 1, 1, 10000.00, 0.00, 'Vacant', NULL, NULL, '2026-09-01 15:44:00', '2026-09-08 12:08:10'),
(87, 4, '4', 'Bedsitter', 1, 1, 10000.00, 0.00, 'Vacant', NULL, NULL, '2026-09-01 15:44:00', '2026-09-08 12:08:10'),
(88, 4, '5', 'Bedsitter', 2, 1, 10000.00, 0.00, 'Vacant', NULL, NULL, '2026-09-01 15:44:00', '2026-09-08 12:08:10'),
(89, 4, '6', 'Bedsitter', 2, 1, 10000.00, 0.00, 'Vacant', NULL, NULL, '2026-09-01 15:44:00', '2026-09-08 12:08:10'),
(90, 4, '7', 'Bedsitter', 2, 1, 10000.00, 0.00, 'Vacant', NULL, NULL, '2026-09-01 15:44:00', '2026-09-08 12:08:10'),
(91, 4, '8', 'Bedsitter', 2, 1, 10000.00, 0.00, 'Vacant', NULL, NULL, '2026-09-01 15:44:00', '2026-09-08 12:08:10'),
(92, 4, '9', 'Bedsitter', 3, 1, 10000.00, 0.00, 'Vacant', NULL, NULL, '2026-09-01 15:44:00', '2026-09-08 12:08:10'),
(93, 4, '10', 'Bedsitter', 3, 1, 10000.00, 0.00, 'Vacant', NULL, NULL, '2026-09-01 15:44:00', '2026-09-08 12:08:10'),
(94, 3, '101', '3 Bedroom', 1, 1, 35000.00, 0.00, 'Vacant', NULL, NULL, '2026-08-31 07:59:27', '2026-09-08 17:15:45'),
(95, 3, '102', '3 Bedroom', 1, 1, 35000.00, 0.00, 'Vacant', NULL, NULL, '2026-08-31 07:59:27', '2026-09-08 17:15:45'),
(96, 3, '103', '3 Bedroom', 1, 1, 35000.00, 0.00, 'Occupied', NULL, NULL, '2026-08-31 07:59:27', '2026-09-08 17:15:45'),
(97, 2, '601', '4 Bedroom', 6, 1, 49000.00, 0.00, 'Vacant', NULL, NULL, '2026-08-04 15:07:21', '2026-09-08 17:15:45'),
(98, 2, '602', '4 Bedroom', 6, 1, 49000.00, 0.00, 'Vacant', NULL, NULL, '2026-08-04 15:07:21', '2026-09-08 17:15:45'),
(99, 4, '101', 'Bedsitter', 1, 1, 10000.00, 0.00, 'Vacant', NULL, NULL, '2026-09-01 15:44:00', '2026-09-08 17:15:45'),
(100, 4, '102', 'Bedsitter', 1, 1, 10000.00, 0.00, 'Vacant', NULL, NULL, '2026-09-01 15:44:00', '2026-09-08 17:15:45'),
(173, 1, 'G01', 'Office', 0, 1, 100000.00, 0.00, 'Vacant', NULL, NULL, '2026-06-25 18:02:29', '2026-09-08 17:15:45'),
(174, 1, 'G02', 'Office', 0, 1, 100000.00, 0.00, 'Vacant', NULL, NULL, '2026-06-25 18:02:29', '2026-09-08 17:15:45'),
(175, 1, 'G03', 'Office', 0, 1, 100000.00, 0.00, 'Vacant', NULL, NULL, '2026-06-25 18:02:29', '2026-09-08 17:15:45'),
(176, 1, 'G04', 'Office', 0, 1, 100000.00, 0.00, 'Vacant', NULL, NULL, '2026-06-25 18:02:29', '2026-09-08 17:15:45'),
(177, 1, '101', 'Office', 1, 1, 100000.00, 0.00, 'Vacant', NULL, NULL, '2026-06-25 18:02:29', '2026-09-08 17:15:45'),
(178, 1, '102', 'Office', 1, 1, 100000.00, 0.00, 'Vacant', NULL, NULL, '2026-06-25 18:02:29', '2026-09-08 17:15:45'),
(179, 1, '103', 'Office', 1, 1, 100000.00, 0.00, 'Vacant', NULL, NULL, '2026-06-25 18:02:29', '2026-09-08 17:15:45'),
(180, 1, '104', 'Office', 1, 1, 100000.00, 0.00, 'Vacant', NULL, NULL, '2026-06-25 18:02:29', '2026-09-08 17:15:45'),
(181, 1, '201', 'Office', 2, 1, 100000.00, 0.00, 'Vacant', NULL, NULL, '2026-06-25 18:02:29', '2026-09-08 17:15:45'),
(182, 1, '202', 'Office', 2, 1, 100000.00, 0.00, 'Vacant', NULL, NULL, '2026-06-25 18:02:29', '2026-09-08 17:15:45'),
(183, 1, '203', 'Office', 2, 1, 100000.00, 0.00, 'Vacant', NULL, NULL, '2026-06-25 18:02:29', '2026-09-08 17:15:45'),
(184, 1, '204', 'Office', 2, 1, 100000.00, 0.00, 'Vacant', NULL, NULL, '2026-06-25 18:02:29', '2026-09-08 17:15:45'),
(185, 1, '301', 'Office', 3, 1, 100000.00, 0.00, 'Occupied', NULL, NULL, '2026-06-25 18:02:29', '2026-09-08 19:54:03'),
(186, 1, '302', 'Office', 3, 1, 100000.00, 0.00, 'Vacant', NULL, NULL, '2026-06-25 18:02:29', '2026-09-08 17:15:45'),
(187, 1, '303', 'Office', 3, 1, 100000.00, 0.00, 'Vacant', NULL, NULL, '2026-06-25 18:02:29', '2026-09-08 17:15:45'),
(188, 1, '304', 'Office', 3, 1, 100000.00, 0.00, 'Vacant', NULL, NULL, '2026-06-25 18:02:29', '2026-09-08 17:15:45'),
(189, 1, '401', 'Office', 4, 1, 100000.00, 0.00, 'Vacant', NULL, NULL, '2026-06-25 18:02:29', '2026-09-08 17:15:45'),
(190, 1, '402', 'Office', 4, 1, 100000.00, 0.00, 'Vacant', NULL, NULL, '2026-06-25 18:02:29', '2026-09-08 17:15:45'),
(191, 1, '403', 'Office', 4, 1, 100000.00, 0.00, 'Vacant', NULL, NULL, '2026-06-25 18:02:29', '2026-09-08 17:15:45'),
(192, 1, '404', 'Office', 4, 1, 100000.00, 0.00, 'Vacant', NULL, NULL, '2026-06-25 18:02:29', '2026-09-08 17:15:45'),
(193, 1, '501', 'Office', 5, 1, 100000.00, 0.00, 'Vacant', NULL, NULL, '2026-06-25 18:02:29', '2026-09-08 17:15:45'),
(194, 1, '502', 'Office', 5, 1, 100000.00, 0.00, 'Vacant', NULL, NULL, '2026-06-25 18:02:29', '2026-09-08 17:15:45'),
(195, 1, '503', 'Office', 5, 1, 100000.00, 0.00, 'Vacant', NULL, NULL, '2026-06-25 18:02:29', '2026-09-08 17:15:45'),
(196, 1, '504', 'Office', 5, 1, 100000.00, 0.00, 'Vacant', NULL, NULL, '2026-06-25 18:02:29', '2026-09-08 17:15:45'),
(197, 1, '601', 'Office', 6, 1, 100000.00, 0.00, 'Vacant', NULL, NULL, '2026-06-25 18:02:29', '2026-09-08 17:15:45'),
(198, 1, '602', 'Office', 6, 1, 100000.00, 0.00, 'Vacant', NULL, NULL, '2026-06-25 18:02:29', '2026-09-08 17:15:45'),
(199, 1, '603', 'Office', 6, 1, 100000.00, 0.00, 'Vacant', NULL, NULL, '2026-06-25 18:02:29', '2026-09-08 17:15:45'),
(200, 1, '604', 'Office', 6, 1, 100000.00, 0.00, 'Vacant', NULL, NULL, '2026-06-25 18:02:29', '2026-09-08 17:15:45'),
(201, 1, '701', 'Office', 7, 1, 100000.00, 0.00, 'Vacant', NULL, NULL, '2026-06-25 18:02:29', '2026-09-08 17:15:45'),
(202, 1, '702', 'Office', 7, 1, 100000.00, 0.00, 'Vacant', NULL, NULL, '2026-06-25 18:02:29', '2026-09-08 17:15:45'),
(203, 1, '703', 'Office', 7, 1, 100000.00, 0.00, 'Vacant', NULL, NULL, '2026-06-25 18:02:29', '2026-09-08 17:15:45'),
(204, 1, '704', 'Office', 7, 1, 100000.00, 0.00, 'Vacant', NULL, NULL, '2026-06-25 18:02:29', '2026-09-08 17:15:45'),
(205, 1, '801', 'Office', 8, 1, 100000.00, 0.00, 'Vacant', NULL, NULL, '2026-06-25 18:02:29', '2026-09-08 17:15:45'),
(206, 1, '802', 'Office', 8, 1, 100000.00, 0.00, 'Vacant', NULL, NULL, '2026-06-25 18:02:29', '2026-09-08 17:15:45'),
(207, 1, '803', 'Office', 8, 1, 100000.00, 0.00, 'Vacant', NULL, NULL, '2026-06-25 18:02:29', '2026-09-08 17:15:45'),
(208, 1, '804', 'Office', 8, 1, 100000.00, 0.00, 'Vacant', NULL, NULL, '2026-06-25 18:02:29', '2026-09-08 17:15:45'),
(209, 1, '901', 'Office', 9, 1, 100000.00, 0.00, 'Vacant', NULL, NULL, '2026-06-25 18:02:29', '2026-09-08 17:15:45'),
(210, 1, '902', 'Office', 9, 1, 100000.00, 0.00, 'Vacant', NULL, NULL, '2026-06-25 18:02:29', '2026-09-08 17:15:45'),
(211, 1, '903', 'Office', 9, 1, 100000.00, 0.00, 'Vacant', NULL, NULL, '2026-06-25 18:02:29', '2026-09-08 17:15:45'),
(212, 1, '904', 'Office', 9, 1, 100000.00, 0.00, 'Vacant', NULL, NULL, '2026-06-25 18:02:29', '2026-09-08 17:15:45'),
(213, 5, 'G01', 'Shop', 0, 1, 30000.00, 0.00, 'Vacant', '[\"Storage\"]', '', '2026-09-01 18:50:25', '2026-09-08 12:05:12'),
(214, 5, 'G02', 'Shop', 0, 1, 30000.00, 0.00, 'Vacant', '[\"Storage\"]', '', '2026-09-01 18:50:25', '2026-09-08 12:05:57'),
(215, 5, 'G03', 'Shop', 0, 1, 30000.00, 0.00, 'Vacant', '[\"Storage\"]', '', '2026-09-01 18:50:25', '2026-09-08 12:05:48'),
(216, 5, 'G04', 'Shop', 0, 1, 29999.82, 0.00, 'Vacant', '[\"Storage\"]', '', '2026-09-01 18:50:25', '2026-09-08 12:06:19'),
(217, 5, '101', '2 Bedroom', 1, 1, 25000.00, 0.00, 'Vacant', NULL, NULL, '2026-09-01 18:50:25', '2026-09-08 12:02:26'),
(218, 5, '102', '2 Bedroom', 1, 1, 25000.00, 0.00, 'Vacant', NULL, NULL, '2026-09-01 18:50:25', '2026-09-08 12:02:26'),
(219, 5, '103', '2 Bedroom', 1, 1, 25000.00, 0.00, 'Vacant', NULL, NULL, '2026-09-01 18:50:25', '2026-09-08 12:02:26'),
(220, 5, '104', '2 Bedroom', 1, 1, 25000.00, 0.00, 'Vacant', NULL, NULL, '2026-09-01 18:50:25', '2026-09-08 12:02:26'),
(221, 5, '201', '2 Bedroom', 2, 1, 25000.00, 0.00, 'Vacant', NULL, NULL, '2026-09-01 18:50:25', '2026-09-08 12:02:26'),
(222, 5, '202', '2 Bedroom', 2, 1, 25000.00, 0.00, 'Vacant', NULL, NULL, '2026-09-01 18:50:25', '2026-09-08 12:02:26'),
(223, 5, '203', '2 Bedroom', 2, 1, 25000.00, 0.00, 'Vacant', NULL, NULL, '2026-09-01 18:50:25', '2026-09-08 12:02:26'),
(224, 5, '204', '2 Bedroom', 2, 1, 25000.00, 0.00, 'Vacant', NULL, NULL, '2026-09-01 18:50:25', '2026-09-08 12:02:26'),
(225, 5, '301', '2 Bedroom', 3, 1, 25000.00, 0.00, 'Vacant', NULL, NULL, '2026-09-01 18:50:25', '2026-09-08 12:02:26'),
(226, 5, '302', '2 Bedroom', 3, 1, 25000.00, 0.00, 'Vacant', NULL, NULL, '2026-09-01 18:50:25', '2026-09-08 12:02:26'),
(227, 5, '303', '2 Bedroom', 3, 1, 25000.00, 0.00, 'Vacant', NULL, NULL, '2026-09-01 18:50:25', '2026-09-08 12:02:26'),
(228, 5, '304', '2 Bedroom', 3, 1, 25000.00, 0.00, 'Vacant', NULL, NULL, '2026-09-01 18:50:25', '2026-09-08 12:02:26'),
(229, 5, '401', '2 Bedroom', 4, 1, 25000.00, 0.00, 'Vacant', NULL, NULL, '2026-09-01 18:50:25', '2026-09-08 12:02:26'),
(230, 5, '402', '2 Bedroom', 4, 1, 25000.00, 0.00, 'Vacant', NULL, NULL, '2026-09-01 18:50:25', '2026-09-08 12:02:26'),
(231, 5, '403', '2 Bedroom', 4, 1, 25000.00, 0.00, 'Vacant', NULL, NULL, '2026-09-01 18:50:25', '2026-09-08 12:02:26'),
(232, 5, '404', '2 Bedroom', 4, 1, 25000.00, 0.00, 'Vacant', NULL, NULL, '2026-09-01 18:50:25', '2026-09-08 12:02:26'),
(233, 5, '501', '2 Bedroom', 5, 1, 25000.00, 0.00, 'Vacant', NULL, NULL, '2026-09-01 18:50:25', '2026-09-08 12:02:26'),
(234, 5, '502', '2 Bedroom', 5, 1, 25000.00, 0.00, 'Vacant', NULL, NULL, '2026-09-01 18:50:25', '2026-09-08 12:02:26'),
(235, 5, '503', '2 Bedroom', 5, 1, 25000.00, 0.00, 'Vacant', NULL, NULL, '2026-09-01 18:50:25', '2026-09-08 12:02:26'),
(236, 5, '504', '2 Bedroom', 5, 1, 25000.00, 0.00, 'Vacant', NULL, NULL, '2026-09-01 18:50:25', '2026-09-08 12:02:26'),
(237, 5, '601', '2 Bedroom', 6, 1, 25000.00, 0.00, 'Vacant', NULL, NULL, '2026-09-01 18:50:25', '2026-09-08 12:02:26'),
(238, 5, '602', '2 Bedroom', 6, 1, 25000.00, 0.00, 'Vacant', NULL, NULL, '2026-09-01 18:50:25', '2026-09-08 12:02:26'),
(239, 5, '603', '2 Bedroom', 6, 1, 25000.00, 0.00, 'Vacant', NULL, NULL, '2026-09-01 18:50:25', '2026-09-08 12:02:26'),
(240, 5, '604', '2 Bedroom', 6, 1, 25000.00, 0.00, 'Vacant', NULL, NULL, '2026-09-01 18:50:25', '2026-09-08 12:02:26'),
(241, 5, '701', '2 Bedroom', 7, 1, 25000.00, 0.00, 'Vacant', NULL, NULL, '2026-09-01 18:50:25', '2026-09-08 12:02:26'),
(242, 5, '702', '2 Bedroom', 7, 1, 25000.00, 0.00, 'Vacant', NULL, NULL, '2026-09-01 18:50:25', '2026-09-08 12:02:26'),
(243, 5, '703', '2 Bedroom', 7, 1, 25000.00, 0.00, 'Vacant', NULL, NULL, '2026-09-01 18:50:25', '2026-09-08 12:02:26'),
(244, 5, '704', '2 Bedroom', 7, 1, 25000.00, 0.00, 'Vacant', NULL, NULL, '2026-09-01 18:50:25', '2026-09-08 12:02:26'),
(245, 5, '801', '2 Bedroom', 8, 1, 25000.00, 0.00, 'Vacant', NULL, NULL, '2026-09-01 18:50:25', '2026-09-08 12:02:26'),
(246, 5, '802', '2 Bedroom', 8, 1, 25000.00, 0.00, 'Vacant', NULL, NULL, '2026-09-01 18:50:25', '2026-09-08 12:02:26'),
(247, 5, '803', '2 Bedroom', 8, 1, 25000.00, 0.00, 'Vacant', NULL, NULL, '2026-09-01 18:50:25', '2026-09-08 12:02:26'),
(248, 5, '804', '2 Bedroom', 8, 1, 25000.00, 0.00, 'Vacant', NULL, NULL, '2026-09-01 18:50:25', '2026-09-08 12:02:26'),
(249, 5, '901', '2 Bedroom', 9, 1, 25000.00, 0.00, 'Vacant', NULL, NULL, '2026-09-01 18:50:25', '2026-09-08 12:02:26'),
(250, 5, '902', '2 Bedroom', 9, 1, 25000.00, 0.00, 'Vacant', NULL, NULL, '2026-09-01 18:50:25', '2026-09-08 12:02:26'),
(251, 5, '903', '2 Bedroom', 9, 1, 25000.00, 0.00, 'Vacant', NULL, NULL, '2026-09-01 18:50:25', '2026-09-08 12:02:26'),
(252, 5, '904', '2 Bedroom', 9, 1, 25000.00, 0.00, 'Vacant', NULL, NULL, '2026-09-01 18:50:25', '2026-09-08 12:02:26'),
(253, 5, '1001', '2 Bedroom', 10, 1, 25000.00, 0.00, 'Vacant', NULL, NULL, '2026-09-01 18:50:25', '2026-09-08 12:02:26'),
(254, 5, '1002', '2 Bedroom', 10, 1, 25000.00, 0.00, 'Vacant', NULL, NULL, '2026-09-01 18:50:25', '2026-09-08 12:02:26'),
(255, 5, '1003', '2 Bedroom', 10, 1, 25000.00, 0.00, 'Vacant', NULL, NULL, '2026-09-01 18:50:25', '2026-09-08 12:02:26'),
(256, 5, '1004', '2 Bedroom', 10, 1, 25000.00, 0.00, 'Vacant', NULL, NULL, '2026-09-01 18:50:25', '2026-09-08 12:02:26'),
(257, 5, '1101', '2 Bedroom', 11, 1, 25000.00, 0.00, 'Vacant', NULL, NULL, '2026-09-01 18:50:25', '2026-09-08 12:02:26'),
(258, 5, '1102', '2 Bedroom', 11, 1, 25000.00, 0.00, 'Vacant', NULL, NULL, '2026-09-01 18:50:25', '2026-09-08 12:02:26'),
(259, 5, '1103', '2 Bedroom', 11, 1, 25000.00, 0.00, 'Vacant', NULL, NULL, '2026-09-01 18:50:25', '2026-09-08 12:02:26'),
(260, 5, '1104', '2 Bedroom', 11, 1, 25000.00, 0.00, 'Vacant', NULL, NULL, '2026-09-01 18:50:25', '2026-09-08 12:02:26'),
(261, 5, '1201', '2 Bedroom', 12, 1, 25000.00, 0.00, 'Vacant', NULL, NULL, '2026-09-01 18:50:25', '2026-09-08 12:02:26'),
(262, 5, '1202', '2 Bedroom', 12, 1, 25000.00, 0.00, 'Vacant', NULL, NULL, '2026-09-01 18:50:25', '2026-09-08 12:02:26'),
(263, 5, '1203', '2 Bedroom', 12, 1, 25000.00, 0.00, 'Vacant', NULL, NULL, '2026-09-01 18:50:25', '2026-09-08 12:02:26'),
(264, 5, '1204', '2 Bedroom', 12, 1, 25000.00, 0.00, 'Vacant', NULL, NULL, '2026-09-01 18:50:25', '2026-09-08 12:02:26'),
(265, 5, '1301', '2 Bedroom', 13, 1, 25000.00, 0.00, 'Vacant', NULL, NULL, '2026-09-01 18:50:25', '2026-09-08 12:02:26'),
(266, 5, '1302', '2 Bedroom', 13, 1, 25000.00, 0.00, 'Vacant', NULL, NULL, '2026-09-01 18:50:25', '2026-09-08 12:02:26'),
(267, 5, '1303', '2 Bedroom', 13, 1, 25000.00, 0.00, 'Vacant', NULL, NULL, '2026-09-01 18:50:25', '2026-09-08 12:02:26'),
(268, 5, '1304', '2 Bedroom', 13, 1, 25000.00, 0.00, 'Vacant', NULL, NULL, '2026-09-01 18:50:25', '2026-09-08 12:02:26'),
(269, 5, '1401', '2 Bedroom', 14, 1, 25000.00, 0.00, 'Vacant', NULL, NULL, '2026-09-01 18:50:25', '2026-09-08 12:02:26'),
(270, 5, '1402', '2 Bedroom', 14, 1, 25000.00, 0.00, 'Vacant', NULL, NULL, '2026-09-01 18:50:25', '2026-09-08 12:02:26'),
(271, 5, '1403', '2 Bedroom', 14, 1, 25000.00, 0.00, 'Vacant', NULL, NULL, '2026-09-01 18:50:25', '2026-09-08 12:02:26'),
(272, 5, '1404', '2 Bedroom', 14, 1, 25000.00, 0.00, 'Vacant', NULL, NULL, '2026-09-01 18:50:25', '2026-09-08 12:02:26'),
(273, 5, '1501', '2 Bedroom', 15, 1, 25000.00, 0.00, 'Vacant', NULL, NULL, '2026-09-01 18:50:25', '2026-09-08 12:02:26'),
(274, 5, '1502', '2 Bedroom', 15, 1, 25000.00, 0.00, 'Vacant', NULL, NULL, '2026-09-01 18:50:25', '2026-09-08 12:02:26'),
(275, 5, '1503', '2 Bedroom', 15, 1, 25000.00, 0.00, 'Vacant', NULL, NULL, '2026-09-01 18:50:25', '2026-09-08 12:02:26'),
(276, 5, '1504', '2 Bedroom', 15, 1, 25000.00, 0.00, 'Vacant', NULL, NULL, '2026-09-01 18:50:25', '2026-09-08 12:02:26'),
(277, 5, '1601', '2 Bedroom', 16, 1, 25000.00, 0.00, 'Vacant', NULL, NULL, '2026-09-01 18:50:25', '2026-09-08 12:02:26'),
(278, 5, '1602', '2 Bedroom', 16, 1, 25000.00, 0.00, 'Vacant', NULL, NULL, '2026-09-01 18:50:25', '2026-09-08 12:02:26'),
(279, 5, '1603', '2 Bedroom', 16, 1, 25000.00, 0.00, 'Vacant', NULL, NULL, '2026-09-01 18:50:25', '2026-09-08 12:02:26'),
(280, 5, '1604', '2 Bedroom', 16, 1, 25000.00, 0.00, 'Vacant', NULL, NULL, '2026-09-01 18:50:25', '2026-09-08 12:02:26'),
(281, 5, '1701', '2 Bedroom', 17, 1, 25000.00, 0.00, 'Vacant', NULL, NULL, '2026-09-01 18:50:25', '2026-09-08 12:02:26'),
(282, 5, '1702', '2 Bedroom', 17, 1, 25000.00, 0.00, 'Vacant', NULL, NULL, '2026-09-01 18:50:25', '2026-09-08 12:02:26'),
(283, 5, '1703', '2 Bedroom', 17, 1, 25000.00, 0.00, 'Vacant', NULL, NULL, '2026-09-01 18:50:25', '2026-09-08 12:02:26'),
(284, 5, '1704', '2 Bedroom', 17, 1, 25000.00, 0.00, 'Vacant', NULL, NULL, '2026-09-01 18:50:25', '2026-09-08 12:02:26'),
(285, 6, 'G01', 'Penthouse', 0, 3, 400000.00, 250000.00, 'Occupied', '[\"Balcony\",\"Parking\",\"Water Heater\",\"Fibre Ready\",\"WiFi\",\"Garden View\",\"En-suite\",\"Storage\"]', '', '2026-09-01 19:26:55', '2026-09-08 13:23:53'),
(286, 9, 'G01', 'Shop', 0, 1, 59999.74, 0.00, 'Vacant', NULL, NULL, '2026-09-15 22:35:00', NULL),
(287, 9, 'G02', 'Shop', 0, 1, 59999.74, 0.00, 'Vacant', NULL, NULL, '2026-09-15 22:35:00', NULL),
(288, 9, 'G03', 'Shop', 0, 1, 59999.74, 0.00, 'Vacant', NULL, NULL, '2026-09-15 22:35:00', NULL),
(289, 9, 'G04', 'Shop', 0, 1, 59999.74, 0.00, 'Vacant', NULL, NULL, '2026-09-15 22:35:00', NULL),
(290, 9, '101', '2 Bedroom', 1, 1, 59999.74, 0.00, 'Vacant', NULL, NULL, '2026-09-15 22:35:00', NULL),
(291, 9, '102', '2 Bedroom', 1, 1, 59999.74, 0.00, 'Vacant', NULL, NULL, '2026-09-15 22:35:00', NULL),
(292, 9, '103', '2 Bedroom', 1, 1, 59999.74, 0.00, 'Vacant', NULL, NULL, '2026-09-15 22:35:00', NULL),
(293, 9, '104', '2 Bedroom', 1, 1, 59999.74, 0.00, 'Vacant', NULL, NULL, '2026-09-15 22:35:00', NULL),
(294, 9, '201', '2 Bedroom', 2, 1, 59999.74, 0.00, 'Vacant', NULL, NULL, '2026-09-15 22:35:00', NULL),
(295, 9, '202', '2 Bedroom', 2, 1, 59999.74, 0.00, 'Vacant', NULL, NULL, '2026-09-15 22:35:00', NULL),
(296, 9, '203', '2 Bedroom', 2, 1, 59999.74, 0.00, 'Vacant', NULL, NULL, '2026-09-15 22:35:00', NULL),
(297, 9, '204', '2 Bedroom', 2, 1, 59999.74, 0.00, 'Vacant', NULL, NULL, '2026-09-15 22:35:00', NULL),
(298, 9, '301', '2 Bedroom', 3, 1, 59999.74, 0.00, 'Vacant', NULL, NULL, '2026-09-15 22:35:00', NULL),
(299, 9, '302', '2 Bedroom', 3, 1, 59999.74, 0.00, 'Vacant', NULL, NULL, '2026-09-15 22:35:00', NULL),
(300, 9, '303', '2 Bedroom', 3, 1, 59999.74, 0.00, 'Vacant', NULL, NULL, '2026-09-15 22:35:00', NULL),
(301, 9, '304', '2 Bedroom', 3, 1, 59999.74, 0.00, 'Vacant', NULL, NULL, '2026-09-15 22:35:00', NULL),
(302, 9, '401', '2 Bedroom', 4, 1, 59999.74, 0.00, 'Vacant', NULL, NULL, '2026-09-15 22:35:00', NULL),
(303, 9, '402', '2 Bedroom', 4, 1, 59999.74, 0.00, 'Vacant', NULL, NULL, '2026-09-15 22:35:00', NULL),
(304, 9, '403', '2 Bedroom', 4, 1, 59999.74, 0.00, 'Vacant', NULL, NULL, '2026-09-15 22:35:00', NULL),
(305, 9, '404', '2 Bedroom', 4, 1, 59999.74, 0.00, 'Vacant', NULL, NULL, '2026-09-15 22:35:00', NULL),
(306, 9, '501', '2 Bedroom', 5, 1, 59999.74, 0.00, 'Vacant', NULL, NULL, '2026-09-15 22:35:00', NULL),
(307, 9, '502', '2 Bedroom', 5, 1, 59999.74, 0.00, 'Vacant', NULL, NULL, '2026-09-15 22:35:00', NULL),
(308, 9, '503', '2 Bedroom', 5, 1, 59999.74, 0.00, 'Vacant', NULL, NULL, '2026-09-15 22:35:00', NULL),
(309, 9, '504', '2 Bedroom', 5, 1, 59999.74, 0.00, 'Vacant', NULL, NULL, '2026-09-15 22:35:00', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(10) UNSIGNED NOT NULL,
  `full_name` varchar(120) NOT NULL,
  `email` varchar(180) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `id_number` varchar(30) DEFAULT NULL COMMENT 'National ID or Passport',
  `password_hash` varchar(255) NOT NULL,
  `role` enum('admin','caretaker','tenant') NOT NULL,
  `status` enum('active','inactive','suspended','prospective','moved_out','terminated','pending_setup','pending_approval') NOT NULL DEFAULT 'pending_setup',
  `must_change_password` tinyint(1) NOT NULL DEFAULT 0 COMMENT '1 = force password change on next login',
  `avatar` varchar(255) DEFAULT NULL COMMENT 'Relative path to profile image',
  `emergency_contact` varchar(200) DEFAULT NULL,
  `preferred_apartment_id` int(10) UNSIGNED DEFAULT NULL COMMENT 'Tenant preference set during self-registration',
  `preferred_unit_type` varchar(60) DEFAULT NULL COMMENT 'Unit type preferred on registration',
  `notes` text DEFAULT NULL COMMENT 'Free-text notes from registration form',
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT NULL ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `full_name`, `email`, `phone`, `id_number`, `password_hash`, `role`, `status`, `must_change_password`, `avatar`, `emergency_contact`, `preferred_apartment_id`, `preferred_unit_type`, `notes`, `created_at`, `updated_at`) VALUES
(1, 'Cynthia Wafula', 'cynnask@gmail.com', '0714500986', NULL, '$2y$12$DwnkLBqZssXMhwT0sjsWiO04Zyl1HgHOJwdkE7p6YjzTmVUslgn52', 'admin', 'active', 0, 'assets/images/avatars/user_1_1789410267.png', NULL, NULL, NULL, NULL, '2026-05-05 15:04:49', '2026-09-14 21:24:27'),
(2, 'Moses Matangi', 'moses@gmail.com', '0765892278', NULL, '$2y$10$fkpqINItH9/qIyh/x.5bOeEMeL4NoUwcZEEvnMVHExABFlZsYVrw.', 'caretaker', 'active', 1, NULL, NULL, NULL, NULL, NULL, '2026-08-29 14:10:22', '2026-09-08 17:50:01'),
(3, 'Edgar Rotich', 'edgar@gmail.com', '0714707419', NULL, '$2y$12$599fUSEllBl1Dhg1wqqSV.pTsFhLHSba.1v4nGc4UEwOjjPKoFu6y', 'caretaker', 'active', 0, NULL, NULL, NULL, NULL, NULL, '2026-06-25 19:38:39', '2026-09-08 17:51:20'),
(4, 'Nicholas Mwiti', 'mwiti@gmail.com', '0745673882', NULL, '$2y$12$lsVdpx7M302rTTem8wiXT.ds4PRAOm8K6vL9sQZLs8/ERflfLtGJq', 'caretaker', 'active', 0, NULL, NULL, NULL, NULL, NULL, '2026-08-29 15:19:00', '2026-08-29 15:20:28'),
(5, 'Phillip Njugi', 'phillip@gmail.com', '0745678799', NULL, '$2y$12$y2KtvZ4V2VbLGfZgHcjhb.YA9TgsJpHc.eh9uyxjGPf01iwzOmECy', 'caretaker', 'active', 0, NULL, NULL, NULL, NULL, NULL, '2026-08-31 10:08:50', '2026-08-31 10:10:49'),
(6, 'Shaline Martha', 'shaline@gmail.com', '0724251308', NULL, '$2y$12$oS9K8uvt.GstE4.JpVOGx.ng1sbWHVqXkMzFG5/8AQE.yjakby1Lm', 'tenant', 'active', 0, NULL, NULL, 3, 'Maisonette', NULL, '2026-08-31 12:44:09', NULL),
(7, 'Antonate Moraa', 'antonate@gmail.com', '0714110927', '', '$2y$12$lpO25KB/7PtZvGascPEl..Z9WALHcvHHdwSDYOaXAnik57RXqW3I.', 'tenant', 'moved_out', 0, NULL, '', NULL, NULL, NULL, '2026-09-01 20:52:09', '2026-09-03 07:24:54'),
(8, 'Leila Kerubo', 'leila@gmail.com', '0712346578', NULL, '$2y$12$/NUhFFDSbShdCDmJ5bL2Fegq9nvLrkciVM1S86PvFXqyURLnCSl4i', 'tenant', 'active', 0, NULL, NULL, 1, '4 Bedroom', NULL, '2026-08-29 15:15:35', '2026-09-08 17:51:01'),
(12, 'Anne Wangoi', 'anne@gmail.com', '0753567899', 'anne@gmail.com', '$2y$12$nVYq3bIcECE1DOa0P/DSvu6Wr1mmvEw8/0T6OcTaNsXhS9a3kX1Ru', 'tenant', 'active', 0, NULL, NULL, 3, NULL, NULL, '2026-09-08 11:14:21', '2026-09-08 11:34:59'),
(13, 'Benjamin Otieno', 'benjamin@gmail.com', '0723632130', NULL, '$2y$12$Ncx8hj3378ppGZErBkIbiO3Cg2M48gI443VlI3MAHtNbXP54EjGR2', 'caretaker', 'active', 0, 'assets/images/avatars/user_13_1789152286.png', NULL, NULL, NULL, NULL, '2026-09-08 12:14:57', '2026-09-11 21:44:46'),
(14, 'Daniel Muasya', 'dante@gmail.com', '0712408181', NULL, '$2y$12$IP0xBfUHigTyIJGpHQgo5OegIujiZGUpvZ636dZzBcLr7IBzsDM4q', 'tenant', 'active', 0, NULL, NULL, 8, NULL, NULL, '2026-09-08 13:03:08', '2026-09-08 13:24:47'),
(16, 'Kelsey Namaswa', 'kelsey@gmail.com', '0114522350', NULL, '$2y$12$vSHAY8U1F0cRjHuN0MaciuNE7MvWavvjuIRjLZo9h/dPMHAWOJx3e', 'tenant', 'active', 0, NULL, NULL, 1, NULL, NULL, '2026-09-08 19:52:18', '2026-09-08 20:01:24'),
(17, 'Bhakita Namaemba', 'bakhita@gmail.com', '0715432843', 'xyz', '$2y$12$unnmAuFgDTmShebzM/QEp.0C4DXjzu0F/R8O3IoF6sY3y21WHp4PO', 'tenant', 'prospective', 0, NULL, NULL, 9, NULL, NULL, '2026-09-15 22:42:37', '2026-09-15 22:44:50');

-- --------------------------------------------------------

--
-- Stand-in structure for view `users_ordered`
-- (See below for the actual view)
--
CREATE TABLE `users_ordered` (
`id` int(10) unsigned
,`full_name` varchar(120)
,`email` varchar(180)
,`phone` varchar(20)
,`id_number` varchar(30)
,`password_hash` varchar(255)
,`role` enum('admin','caretaker','tenant')
,`status` enum('active','inactive','suspended','prospective','moved_out','terminated','pending_setup','pending_approval')
,`must_change_password` tinyint(1)
,`avatar` varchar(255)
,`emergency_contact` varchar(200)
,`preferred_apartment_id` int(10) unsigned
,`preferred_unit_type` varchar(60)
,`notes` text
,`created_at` datetime
,`updated_at` datetime
);

-- --------------------------------------------------------

--
-- Table structure for table `utility_bills`
--

CREATE TABLE `utility_bills` (
  `id` int(10) UNSIGNED NOT NULL,
  `apartment_id` int(10) UNSIGNED DEFAULT NULL,
  `unit_id` int(10) UNSIGNED DEFAULT NULL COMMENT 'NULL = apartment-wide charge',
  `tenant_id` int(10) UNSIGNED DEFAULT NULL,
  `bill_type` enum('Water','Electricity','Garbage','Security','Internet','Other') NOT NULL DEFAULT 'Other',
  `description` varchar(255) DEFAULT NULL,
  `amount` decimal(10,2) NOT NULL,
  `billing_month` enum('January','February','March','April','May','June','July','August','September','October','November','December') NOT NULL,
  `billing_year` year(4) NOT NULL,
  `due_date` date DEFAULT NULL,
  `status` enum('Paid','Pending','Overdue') NOT NULL DEFAULT 'Pending',
  `paid_date` date DEFAULT NULL,
  `issued_by` int(10) UNSIGNED DEFAULT NULL COMMENT 'FK → users (caretaker who issued)',
  `reference` varchar(100) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `utility_bills`
--

INSERT INTO `utility_bills` (`id`, `apartment_id`, `unit_id`, `tenant_id`, `bill_type`, `description`, `amount`, `billing_month`, `billing_year`, `due_date`, `status`, `paid_date`, `issued_by`, `reference`, `created_at`) VALUES
(1, 2, 1, 8, 'Garbage', '', 500.00, 'September', '2026', '2026-10-31', 'Paid', '2026-09-01', 5, NULL, '2026-09-01 07:28:55'),
(2, 3, 5, 6, 'Electricity', '', 1000.00, 'September', '2026', '2026-09-30', 'Paid', '2026-09-01', 4, NULL, '2026-09-01 18:55:00'),
(3, 6, 285, 14, 'Water', 'Water usage for September', 5000.00, 'September', '2026', '2026-09-30', 'Pending', NULL, 13, NULL, '2026-09-15 19:06:32');

-- --------------------------------------------------------

--
-- Stand-in structure for view `v_active_tenants`
-- (See below for the actual view)
--
CREATE TABLE `v_active_tenants` (
`tenant_id` int(10) unsigned
,`tenant_name` varchar(120)
,`email` varchar(180)
,`phone` varchar(20)
,`account_status` enum('active','inactive','suspended','prospective','moved_out','terminated','pending_setup','pending_approval')
,`unit_number` varchar(20)
,`unit_type` enum('Bedsitter','Studio','Self-Contained','1 Bedroom','2 Bedroom','3 Bedroom','4 Bedroom','Penthouse','Maisonette','Shop','Office','Other')
,`monthly_rent` decimal(10,2)
,`apartment_name` varchar(120)
,`location` varchar(255)
,`lease_start` date
,`lease_end` date
);

-- --------------------------------------------------------

--
-- Stand-in structure for view `v_apartment_occupancy`
-- (See below for the actual view)
--
CREATE TABLE `v_apartment_occupancy` (
`id` int(10) unsigned
,`name` varchar(120)
,`location` varchar(255)
,`total_units` smallint(5) unsigned
,`occupied` bigint(21)
,`vacant` bigint(21)
,`occupancy_pct` decimal(25,1)
,`caretaker_name` varchar(120)
);

-- --------------------------------------------------------

--
-- Stand-in structure for view `v_payment_summary`
-- (See below for the actual view)
--
CREATE TABLE `v_payment_summary` (
`tenant_id` int(10) unsigned
,`tenant_name` varchar(120)
,`unit_number` varchar(20)
,`apartment_name` varchar(120)
,`month` enum('January','February','March','April','May','June','July','August','September','October','November','December')
,`year` year(4)
,`amount` decimal(10,2)
,`payment_method` enum('M-PESA','M-PESA Paybill','Airtel Money','PDQ/POS','Bank Transfer','Cash','Cheque','Other')
,`reference_number` varchar(80)
,`payment_date` date
,`status` enum('Paid','Pending','Partial','Overdue','Waived')
);

-- --------------------------------------------------------

--
-- Structure for view `users_ordered`
--
DROP TABLE IF EXISTS `users_ordered`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `users_ordered`  AS SELECT `users`.`id` AS `id`, `users`.`full_name` AS `full_name`, `users`.`email` AS `email`, `users`.`phone` AS `phone`, `users`.`id_number` AS `id_number`, `users`.`password_hash` AS `password_hash`, `users`.`role` AS `role`, `users`.`status` AS `status`, `users`.`must_change_password` AS `must_change_password`, `users`.`avatar` AS `avatar`, `users`.`emergency_contact` AS `emergency_contact`, `users`.`preferred_apartment_id` AS `preferred_apartment_id`, `users`.`preferred_unit_type` AS `preferred_unit_type`, `users`.`notes` AS `notes`, `users`.`created_at` AS `created_at`, `users`.`updated_at` AS `updated_at` FROM `users` ORDER BY CASE WHEN `users`.`role` = 'admin' THEN 0 ELSE 1 END ASC, `users`.`created_at` ASC, `users`.`id` ASC ;

-- --------------------------------------------------------

--
-- Structure for view `v_active_tenants`
--
DROP TABLE IF EXISTS `v_active_tenants`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `v_active_tenants`  AS SELECT `u`.`id` AS `tenant_id`, `u`.`full_name` AS `tenant_name`, `u`.`email` AS `email`, `u`.`phone` AS `phone`, `u`.`status` AS `account_status`, `un`.`unit_number` AS `unit_number`, `un`.`unit_type` AS `unit_type`, `un`.`monthly_rent` AS `monthly_rent`, `a`.`name` AS `apartment_name`, `a`.`location` AS `location`, `tu`.`lease_start` AS `lease_start`, `tu`.`lease_end` AS `lease_end` FROM (((`users` `u` join `tenant_units` `tu` on(`tu`.`tenant_id` = `u`.`id` and `tu`.`status` = 'active')) join `units` `un` on(`un`.`id` = `tu`.`unit_id`)) join `apartments` `a` on(`a`.`id` = `un`.`apartment_id`)) WHERE `u`.`role` = 'tenant' ;

-- --------------------------------------------------------

--
-- Structure for view `v_apartment_occupancy`
--
DROP TABLE IF EXISTS `v_apartment_occupancy`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `v_apartment_occupancy`  AS SELECT `a`.`id` AS `id`, `a`.`name` AS `name`, `a`.`location` AS `location`, `a`.`total_units` AS `total_units`, count(case when `un`.`status` = 'Occupied' then 1 end) AS `occupied`, count(case when `un`.`status` = 'Vacant' then 1 end) AS `vacant`, round(count(case when `un`.`status` = 'Occupied' then 1 end) * 100.0 / nullif(`a`.`total_units`,0),1) AS `occupancy_pct`, `u`.`full_name` AS `caretaker_name` FROM ((`apartments` `a` left join `units` `un` on(`un`.`apartment_id` = `a`.`id`)) left join `users` `u` on(`u`.`id` = `a`.`caretaker_id`)) GROUP BY `a`.`id`, `a`.`name`, `a`.`location`, `a`.`total_units`, `u`.`full_name` ;

-- --------------------------------------------------------

--
-- Structure for view `v_payment_summary`
--
DROP TABLE IF EXISTS `v_payment_summary`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `v_payment_summary`  AS SELECT `u`.`id` AS `tenant_id`, `u`.`full_name` AS `tenant_name`, `un`.`unit_number` AS `unit_number`, `a`.`name` AS `apartment_name`, `p`.`month` AS `month`, `p`.`year` AS `year`, `p`.`amount` AS `amount`, `p`.`payment_method` AS `payment_method`, `p`.`reference_number` AS `reference_number`, `p`.`payment_date` AS `payment_date`, `p`.`status` AS `status` FROM (((`payments` `p` join `users` `u` on(`u`.`id` = `p`.`tenant_id`)) join `units` `un` on(`un`.`id` = `p`.`unit_id`)) join `apartments` `a` on(`a`.`id` = `p`.`apartment_id`)) ORDER BY `p`.`year` DESC, field(`p`.`month`,'January','February','March','April','May','June','July','August','September','October','November','December') DESC ;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `activity_logs`
--
ALTER TABLE `activity_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_al_user` (`user_id`),
  ADD KEY `idx_al_action` (`action`);

--
-- Indexes for table `announcements`
--
ALTER TABLE `announcements`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_ann_creator` (`created_by`),
  ADD KEY `idx_ann_type` (`type`);

--
-- Indexes for table `apartments`
--
ALTER TABLE `apartments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_apt_caretaker` (`caretaker_id`);

--
-- Indexes for table `caretaker_apartments`
--
ALTER TABLE `caretaker_apartments`
  ADD PRIMARY KEY (`caretaker_id`,`apartment_id`),
  ADD KEY `fk_ca_apartment` (`apartment_id`);

--
-- Indexes for table `caretaker_assignments`
--
ALTER TABLE `caretaker_assignments`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_ca_caretaker_apt` (`caretaker_id`,`apartment_id`),
  ADD KEY `idx_ca_status` (`status`),
  ADD KEY `fk_caa_apartment` (`apartment_id`);

--
-- Indexes for table `caretaker_history`
--
ALTER TABLE `caretaker_history`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_ch_email` (`email`),
  ADD KEY `idx_ch_apartment` (`apartment_id`);

--
-- Indexes for table `maintenance_logs`
--
ALTER TABLE `maintenance_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_ml_request` (`request_id`),
  ADD KEY `fk_ml_updater` (`updated_by`);

--
-- Indexes for table `maintenance_requests`
--
ALTER TABLE `maintenance_requests`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_mr_tenant` (`tenant_id`),
  ADD KEY `idx_mr_unit` (`unit_id`),
  ADD KEY `idx_mr_status` (`status`),
  ADD KEY `idx_mr_priority` (`priority`);

--
-- Indexes for table `messages`
--
ALTER TABLE `messages`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_msg_from` (`from_user_id`),
  ADD KEY `idx_msg_to` (`to_user_id`),
  ADD KEY `idx_msg_read` (`is_read`);

--
-- Indexes for table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_notif_user` (`user_id`),
  ADD KEY `idx_notif_is_read` (`is_read`);

--
-- Indexes for table `password_resets`
--
ALTER TABLE `password_resets`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_pr_user` (`user_id`),
  ADD KEY `idx_pr_token` (`token_hash`);

--
-- Indexes for table `payments`
--
ALTER TABLE `payments`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_payment_tenant_month_year_type` (`tenant_id`,`month`,`year`,`payment_type`),
  ADD KEY `idx_pay_status` (`status`),
  ADD KEY `idx_pay_date` (`payment_date`),
  ADD KEY `idx_pay_unit` (`unit_id`),
  ADD KEY `idx_pay_apt` (`apartment_id`),
  ADD KEY `fk_pay_recorded_by` (`recorded_by`),
  ADD KEY `fk_pay_confirmed_by` (`confirmed_by`),
  ADD KEY `idx_pay_type` (`payment_type`);

--
-- Indexes for table `remember_tokens`
--
ALTER TABLE `remember_tokens`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_rt_user` (`user_id`);

--
-- Indexes for table `shared_expenses`
--
ALTER TABLE `shared_expenses`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_exp_tenant` (`tenant_id`),
  ADD KEY `idx_exp_apartment` (`apartment_id`),
  ADD KEY `idx_exp_status` (`status`),
  ADD KEY `fk_exp_unit` (`unit_id`);

--
-- Indexes for table `system_settings`
--
ALTER TABLE `system_settings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_settings_key` (`key`);

--
-- Indexes for table `tenant_units`
--
ALTER TABLE `tenant_units`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_tu_tenant` (`tenant_id`),
  ADD KEY `idx_tu_unit` (`unit_id`),
  ADD KEY `idx_tu_status` (`status`);

--
-- Indexes for table `units`
--
ALTER TABLE `units`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_unit_apt_number` (`apartment_id`,`unit_number`),
  ADD KEY `idx_unit_status` (`status`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_users_email` (`email`),
  ADD KEY `idx_users_role` (`role`),
  ADD KEY `idx_users_status` (`status`),
  ADD KEY `idx_users_pref_apt` (`preferred_apartment_id`);

--
-- Indexes for table `utility_bills`
--
ALTER TABLE `utility_bills`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_ub_tenant` (`tenant_id`),
  ADD KEY `idx_ub_apartment` (`apartment_id`),
  ADD KEY `idx_ub_status` (`status`),
  ADD KEY `idx_ub_unit` (`unit_id`),
  ADD KEY `fk_ub_issued_by` (`issued_by`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `activity_logs`
--
ALTER TABLE `activity_logs`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=200;

--
-- AUTO_INCREMENT for table `announcements`
--
ALTER TABLE `announcements`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `apartments`
--
ALTER TABLE `apartments`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `caretaker_assignments`
--
ALTER TABLE `caretaker_assignments`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=38;

--
-- AUTO_INCREMENT for table `caretaker_history`
--
ALTER TABLE `caretaker_history`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `maintenance_logs`
--
ALTER TABLE `maintenance_logs`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `maintenance_requests`
--
ALTER TABLE `maintenance_requests`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `messages`
--
ALTER TABLE `messages`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=71;

--
-- AUTO_INCREMENT for table `password_resets`
--
ALTER TABLE `password_resets`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `payments`
--
ALTER TABLE `payments`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT for table `remember_tokens`
--
ALTER TABLE `remember_tokens`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `shared_expenses`
--
ALTER TABLE `shared_expenses`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `system_settings`
--
ALTER TABLE `system_settings`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=26;

--
-- AUTO_INCREMENT for table `tenant_units`
--
ALTER TABLE `tenant_units`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `units`
--
ALTER TABLE `units`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=310;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

--
-- AUTO_INCREMENT for table `utility_bills`
--
ALTER TABLE `utility_bills`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `activity_logs`
--
ALTER TABLE `activity_logs`
  ADD CONSTRAINT `fk_al_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `announcements`
--
ALTER TABLE `announcements`
  ADD CONSTRAINT `fk_ann_creator` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `apartments`
--
ALTER TABLE `apartments`
  ADD CONSTRAINT `fk_apt_caretaker` FOREIGN KEY (`caretaker_id`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `caretaker_apartments`
--
ALTER TABLE `caretaker_apartments`
  ADD CONSTRAINT `fk_ca_apartment` FOREIGN KEY (`apartment_id`) REFERENCES `apartments` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_ca_caretaker` FOREIGN KEY (`caretaker_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `caretaker_assignments`
--
ALTER TABLE `caretaker_assignments`
  ADD CONSTRAINT `fk_caa_apartment` FOREIGN KEY (`apartment_id`) REFERENCES `apartments` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_caa_caretaker` FOREIGN KEY (`caretaker_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `maintenance_logs`
--
ALTER TABLE `maintenance_logs`
  ADD CONSTRAINT `fk_ml_request` FOREIGN KEY (`request_id`) REFERENCES `maintenance_requests` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_ml_updater` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `maintenance_requests`
--
ALTER TABLE `maintenance_requests`
  ADD CONSTRAINT `fk_mr_tenant` FOREIGN KEY (`tenant_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_mr_unit` FOREIGN KEY (`unit_id`) REFERENCES `units` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `messages`
--
ALTER TABLE `messages`
  ADD CONSTRAINT `fk_msg_from` FOREIGN KEY (`from_user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_msg_to` FOREIGN KEY (`to_user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `notifications`
--
ALTER TABLE `notifications`
  ADD CONSTRAINT `fk_notif_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `password_resets`
--
ALTER TABLE `password_resets`
  ADD CONSTRAINT `fk_pr_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `payments`
--
ALTER TABLE `payments`
  ADD CONSTRAINT `fk_pay_apartment` FOREIGN KEY (`apartment_id`) REFERENCES `apartments` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_pay_confirmed_by` FOREIGN KEY (`confirmed_by`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_pay_recorded_by` FOREIGN KEY (`recorded_by`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_pay_tenant` FOREIGN KEY (`tenant_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_pay_unit` FOREIGN KEY (`unit_id`) REFERENCES `units` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `remember_tokens`
--
ALTER TABLE `remember_tokens`
  ADD CONSTRAINT `fk_rt_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `shared_expenses`
--
ALTER TABLE `shared_expenses`
  ADD CONSTRAINT `fk_exp_apartment` FOREIGN KEY (`apartment_id`) REFERENCES `apartments` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_exp_tenant` FOREIGN KEY (`tenant_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_exp_unit` FOREIGN KEY (`unit_id`) REFERENCES `units` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `tenant_units`
--
ALTER TABLE `tenant_units`
  ADD CONSTRAINT `fk_tu_tenant` FOREIGN KEY (`tenant_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_tu_unit` FOREIGN KEY (`unit_id`) REFERENCES `units` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `units`
--
ALTER TABLE `units`
  ADD CONSTRAINT `fk_unit_apartment` FOREIGN KEY (`apartment_id`) REFERENCES `apartments` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `utility_bills`
--
ALTER TABLE `utility_bills`
  ADD CONSTRAINT `fk_ub_apartment` FOREIGN KEY (`apartment_id`) REFERENCES `apartments` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_ub_issued_by` FOREIGN KEY (`issued_by`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_ub_tenant` FOREIGN KEY (`tenant_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_ub_unit` FOREIGN KEY (`unit_id`) REFERENCES `units` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
