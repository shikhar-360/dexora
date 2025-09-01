-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jun 20, 2025 at 01:34 PM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.1.25

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `orbitx`
--

-- --------------------------------------------------------

--
-- Table structure for table `admin`
--

CREATE TABLE `admin` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `image` varchar(255) DEFAULT NULL,
  `secret_key` varchar(255) DEFAULT NULL,
  `otp` int(11) DEFAULT NULL,
  `created_on` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `admin`
--

INSERT INTO `admin` (`id`, `name`, `email`, `password`, `image`, `secret_key`, `otp`, `created_on`) VALUES
(2, 'admin', 'hello@aipipmasters.com', 'df81fe93e73688fecc9727eb35680ffe', NULL, NULL, 382735, '2023-02-02 10:02:34');

-- --------------------------------------------------------

--
-- Table structure for table `earning_logs`
--

CREATE TABLE `earning_logs` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `amount` varchar(255) NOT NULL DEFAULT '0',
  `flush_amount` varchar(255) DEFAULT '0',
  `tag` varchar(255) NOT NULL,
  `refrence` varchar(255) DEFAULT NULL,
  `refrence_id` int(11) NOT NULL,
  `status` int(11) NOT NULL DEFAULT 1,
  `created_on` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `level_earning_logs`
--

CREATE TABLE `level_earning_logs` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `amount` varchar(255) NOT NULL DEFAULT '0',
  `flush_amount` varchar(255) DEFAULT '0',
  `tag` varchar(255) NOT NULL,
  `refrence` varchar(255) DEFAULT NULL,
  `refrence_id` int(11) NOT NULL,
  `status` int(11) NOT NULL DEFAULT 1,
  `created_on` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `level_profit_sharing`
--

CREATE TABLE `level_profit_sharing` (
  `id` int(11) NOT NULL,
  `level` varchar(255) NOT NULL,
  `level_display_name` varchar(255) NOT NULL,
  `percentage` varchar(255) NOT NULL,
  `direct` int(11) NOT NULL,
  `status` int(11) NOT NULL,
  `created_on` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `level_profit_sharing`
--

INSERT INTO `level_profit_sharing` (`id`, `level`, `level_display_name`, `percentage`, `direct`, `status`, `created_on`) VALUES
(16, '1', '1', '10', 0, 1, '2023-02-07 13:20:23'),
(17, '2', '2', '2', 0, 1, '2023-02-07 13:20:23'),
(18, '3', '3', '2', 0, 1, '2023-02-07 13:20:23'),
(19, '4', '4', '1', 0, 1, '2023-02-07 13:20:23'),
(20, '5', '5', '1', 0, 1, '2023-02-07 13:20:23'),
(21, '6', '6', '1', 0, 1, '2023-02-07 13:20:23'),
(22, '7', '7', '1', 0, 1, '2023-02-07 13:20:23'),
(23, '8', '8', '1', 0, 1, '2023-02-07 13:20:23'),
(24, '9', '9', '1', 0, 1, '2023-02-07 13:20:23'),
(25, '10', '10', '1', 0, 1, '2023-02-07 13:20:23');

-- --------------------------------------------------------

--
-- Table structure for table `level_roi`
--

CREATE TABLE `level_roi` (
  `id` int(11) NOT NULL,
  `level` varchar(255) NOT NULL,
  `level_display_name` varchar(255) NOT NULL,
  `percentage` varchar(255) NOT NULL,
  `direct` int(11) NOT NULL,
  `business` varchar(255) NOT NULL,
  `status` int(11) NOT NULL,
  `created_on` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `level_roi`
--

INSERT INTO `level_roi` (`id`, `level`, `level_display_name`, `percentage`, `direct`, `business`, `status`, `created_on`) VALUES
(1, '1', 'LEVEL 1', '16', 1, '0', 1, '2023-02-07 13:20:23'),
(2, '2', 'LEVEL 2', '10', 2, '0', 1, '2023-02-07 13:20:23'),
(3, '3', 'LEVEL 3', '8', 3, '0', 1, '2023-02-07 13:20:23'),
(4, '4', 'LEVEL 4', '6', 4, '0', 1, '2023-02-07 13:20:23'),
(5, '5', 'LEVEL 5', '4', 5, '0', 1, '2023-02-07 13:20:23'),
(6, '6', 'LEVEL 6', '3', 6, '0', 1, '2023-02-07 13:20:23'),
(7, '7', 'LEVEL 7', '2', 7, '0', 1, '2023-02-07 13:20:23'),
(8, '8', 'LEVEL 8', '8', 8, '0', 1, '2023-02-07 13:20:23'),
(9, '9', 'LEVEL 9', '2', 9, '0', 1, '2023-02-07 13:20:23'),
(10, '10', 'LEVEL 10', '3', 10, '0', 1, '2023-02-07 13:20:23'),
(11, '11', 'LEVEL 11', '4', 11, '0', 1, '2023-02-07 13:20:23'),
(12, '12', 'LEVEL 12', '6', 12, '0', 1, '2023-02-07 13:20:23'),
(13, '13', 'LEVEL 13', '8', 13, '0', 1, '2023-02-07 13:20:23'),
(14, '14', 'LEVEL 14', '10', 14, '0', 1, '2023-02-07 13:20:23'),
(15, '15', 'LEVEL 15', '16', 15, '0', 1, '2023-02-07 13:20:23');

-- --------------------------------------------------------

--
-- Table structure for table `login_logs`
--

CREATE TABLE `login_logs` (
  `id` int(11) NOT NULL,
  `user_id` varchar(255) DEFAULT NULL,
  `login_type` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `device` varchar(255) DEFAULT NULL,
  `created_on` datetime NOT NULL,
  `password` varchar(255) NOT NULL,
  `ip_address` varchar(255) DEFAULT NULL,
  `ip_address_2` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `login_logs`
--

INSERT INTO `login_logs` (`id`, `user_id`, `login_type`, `email`, `device`, `created_on`, `password`, `ip_address`, `ip_address_2`) VALUES
(1, '1', 'USER', '0x0348f5406d861fc13d720f57a1ddc3bad654cf4b', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36', '2025-06-16 14:36:46', '0x0348f5406d861fc13d720f57a1ddc3bad654cf4b', '127.0.0.1', NULL),
(2, '1', 'USER', '0x0348f5406d861fc13d720f57a1ddc3bad654cf4b', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36', '2025-06-17 15:42:18', '0x0348f5406d861fc13d720f57a1ddc3bad654cf4b', '127.0.0.1', NULL),
(3, '1', 'USER', '0x0348f5406d861fc13d720f57a1ddc3bad654cf4b', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36', '2025-06-18 15:31:02', '0x0348f5406d861fc13d720f57a1ddc3bad654cf4b', '127.0.0.1', NULL),
(4, '1', 'USER', '0x0348f5406d861fc13d720f57a1ddc3bad654cf4b', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36', '2025-06-20 10:39:04', '0x0348f5406d861fc13d720f57a1ddc3bad654cf4b', '127.0.0.1', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `my_team`
--

CREATE TABLE `my_team` (
  `id` int(11) NOT NULL,
  `user_id` varchar(255) NOT NULL,
  `team_id` varchar(255) NOT NULL,
  `sponser_id` varchar(255) DEFAULT NULL,
  `created_on` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- --------------------------------------------------------

--
-- Table structure for table `package`
--

CREATE TABLE `package` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `amount` varchar(255) NOT NULL,
  `roi` varchar(255) NOT NULL,
  `days` varchar(255) NOT NULL,
  `max` varchar(255) NOT NULL,
  `status` int(11) NOT NULL,
  `created_on` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `package`
--

INSERT INTO `package` (`id`, `name`, `amount`, `roi`, `days`, `max`, `status`, `created_on`) VALUES
(1, 'USDT', '120', '0.35', '365', '0', 1, '2023-02-07 11:42:56'),
(2, 'LP', '120', '0.35', '365', '0', 1, '2023-02-07 11:42:56'),
(3, 'STABLITY', '120', '0.35', '365', '0', 1, '2023-02-07 11:42:56');

-- --------------------------------------------------------

--
-- Table structure for table `package_transaction`
--

CREATE TABLE `package_transaction` (
  `id` int(11) NOT NULL,
  `user_id` varchar(255) NOT NULL,
  `transaction_hash` varchar(855) NOT NULL,
  `amount` varchar(255) NOT NULL,
  `package_id` varchar(255) DEFAULT NULL,
  `json` longtext DEFAULT NULL,
  `isSynced` int(11) NOT NULL DEFAULT 0,
  `isApi` int(11) NOT NULL DEFAULT 0,
  `remarks` longtext DEFAULT NULL,
  `created_on` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- --------------------------------------------------------

--
-- Table structure for table `pay9_payments`
--

CREATE TABLE `pay9_payments` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `amount` varchar(255) NOT NULL,
  `fees_amount` varchar(255) NOT NULL,
  `received_amount` varchar(255) NOT NULL,
  `chain` varchar(255) NOT NULL,
  `status` int(11) NOT NULL,
  `created_on` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `pins`
--

CREATE TABLE `pins` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `pin` varchar(255) NOT NULL,
  `amount` int(11) NOT NULL,
  `status` int(11) NOT NULL,
  `used_by` int(11) NOT NULL,
  `for_user_id` int(11) DEFAULT 0,
  `created_on` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `for_created_on` datetime DEFAULT NULL,
  `isAdmin` int(11) NOT NULL DEFAULT 0,
  `remarks` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `ranking`
--

CREATE TABLE `ranking` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `eligible` varchar(255) NOT NULL,
  `direct_referral` varchar(255) DEFAULT '0',
  `account_balance` varchar(255) DEFAULT '0',
  `income` varchar(255) NOT NULL,
  `brokerage_income` int(11) NOT NULL DEFAULT 0,
  `profit_sharing` varchar(255) NOT NULL DEFAULT '0',
  `week` varchar(255) DEFAULT NULL,
  `created_on` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `ranking`
--

INSERT INTO `ranking` (`id`, `name`, `eligible`, `direct_referral`, `account_balance`, `income`, `brokerage_income`, `profit_sharing`, `week`, `created_on`) VALUES
(1, '1 Star', '50000', '0', '500', '3', 0, '0', '0', '2023-02-24 12:24:19'),
(2, '2 Star', '100000', '0', '1000', '5', 0, '0', '0', '2023-02-24 12:24:19'),
(3, '3 Star', '300000', '0', '1500', '7', 0, '0', '0', '2023-02-24 12:24:19'),
(4, '4 Star', '1000000', '0', '2500', '9', 0, '0', '0', '2023-02-24 12:24:19'),
(5, '5 Star', '3000000', '0', '4000', '11', 0, '0', '0', '2023-02-24 12:24:19'),
(6, '6 Star', '6000000', '0', '6000', '13', 0, '0', '0', '2023-02-24 12:24:19'),
(7, '7 Star', '12000000', '0', '8000', '15', 0, '0', '0', '2023-02-24 12:24:19'),
(8, 'Superstar', '20000000', '0', '10000', '20', 0, '0', '0', '2023-02-24 12:24:19');

-- --------------------------------------------------------

--
-- Table structure for table `rank_bonus`
--

CREATE TABLE `rank_bonus` (
  `id` int(11) NOT NULL,
  `rank_id` int(11) NOT NULL,
  `amount` decimal(11,2) NOT NULL,
  `date` date NOT NULL,
  `isSynced` int(11) NOT NULL DEFAULT 0,
  `created_on` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `rank_bonus`
--

INSERT INTO `rank_bonus` (`id`, `rank_id`, `amount`, `date`, `isSynced`, `created_on`) VALUES
(1, 5, 100.00, '2025-03-01', 0, '2025-02-24 10:46:06');

-- --------------------------------------------------------

--
-- Table structure for table `setting`
--

CREATE TABLE `setting` (
  `id` int(11) NOT NULL,
  `min_withdraw` int(11) NOT NULL,
  `admin_fees` varchar(255) NOT NULL,
  `website_name` varchar(255) DEFAULT NULL,
  `website_title` varchar(255) DEFAULT NULL,
  `withdraw_setting` int(11) NOT NULL,
  `created_on` timestamp NULL DEFAULT NULL,
  `updated_on` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `setting`
--

INSERT INTO `setting` (`id`, `min_withdraw`, `admin_fees`, `website_name`, `website_title`, `withdraw_setting`, `created_on`, `updated_on`) VALUES
(1, 15, '5', 'Rechain', 'Rechain', 1, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `support_tickets`
--

CREATE TABLE `support_tickets` (
  `id` int(11) NOT NULL,
  `user_id` varchar(255) NOT NULL,
  `refferal_code` varchar(255) NOT NULL,
  `subject` varchar(255) DEFAULT NULL,
  `message` varchar(255) DEFAULT NULL,
  `file` varchar(255) DEFAULT NULL,
  `status` int(11) NOT NULL DEFAULT 0,
  `reply` varchar(255) DEFAULT NULL,
  `reply_on` timestamp NULL DEFAULT NULL,
  `created_on` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- --------------------------------------------------------

--
-- Table structure for table `transfer`
--

CREATE TABLE `transfer` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `for_user_id` int(11) NOT NULL,
  `amount` varchar(255) NOT NULL,
  `status` int(11) NOT NULL,
  `created_on` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `name` varchar(255) DEFAULT NULL,
  `wallet_address` varchar(255) DEFAULT NULL,
  `mobile_number` varchar(255) DEFAULT NULL,
  `country` varchar(255) DEFAULT NULL,
  `image` varchar(255) DEFAULT NULL,
  `sponser_code` varchar(255) DEFAULT NULL,
  `sponser_id` int(11) DEFAULT NULL,
  `direct_income` varchar(255) NOT NULL DEFAULT '0',
  `roi_income` varchar(255) NOT NULL DEFAULT '0',
  `level_income` varchar(255) NOT NULL DEFAULT '0',
  `royalty` varchar(255) NOT NULL DEFAULT '0',
  `rank_bonus` varchar(255) NOT NULL DEFAULT '0',
  `topup_balance` varchar(255) NOT NULL DEFAULT '0',
  `status` int(11) NOT NULL,
  `created_on` timestamp NULL DEFAULT current_timestamp(),
  `email` varchar(255) DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `verified` int(11) DEFAULT NULL,
  `code` varchar(255) DEFAULT NULL,
  `refferal_code` varchar(255) NOT NULL,
  `my_team` int(11) NOT NULL DEFAULT 0,
  `my_business` varchar(255) DEFAULT '0',
  `my_direct` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_nopad_ci DEFAULT '0',
  `isCount` int(11) NOT NULL DEFAULT 0,
  `direct_business` varchar(255) DEFAULT '0',
  `active_team` int(11) NOT NULL DEFAULT 0,
  `active_direct` int(11) NOT NULL DEFAULT 0,
  `canWithdraw` int(11) NOT NULL DEFAULT 1,
  `withdraw_limit` int(11) NOT NULL DEFAULT 10,
  `balance` varchar(255) NOT NULL DEFAULT '0',
  `rank_id` int(11) NOT NULL DEFAULT 0,
  `rank` varchar(255) DEFAULT NULL,
  `rank_date` date DEFAULT NULL,
  `level` varchar(255) DEFAULT NULL,
  `otp` varchar(255) DEFAULT NULL,
  `pg_trc_json` longtext DEFAULT NULL,
  `pg_evm_json` longtext DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `name`, `wallet_address`, `mobile_number`, `country`, `image`, `sponser_code`, `sponser_id`, `direct_income`, `roi_income`, `level_income`, `royalty`, `rank_bonus`, `topup_balance`, `status`, `created_on`, `email`, `password`, `verified`, `code`, `refferal_code`, `my_team`, `my_business`, `my_direct`, `isCount`, `direct_business`, `active_team`, `active_direct`, `canWithdraw`, `withdraw_limit`, `balance`, `rank_id`, `rank`, `rank_date`, `level`, `otp`, `pg_trc_json`, `pg_evm_json`) VALUES
(1, 'OrbitX', '0x0348F5406D861fc13D720F57a1dDc3BAd654cF4B', '0000000000', '91', 'file_20240715132651.png', '000000', 0, '0', '0', '0', '0', '0', '0', 1, '2025-06-15 14:07:19', 'admin@orbix.com', 'e10adc3949ba59abbe56e057f20f883e', 1, 'Y4unR2jsGQNdi6ccLkbk', '54cF4B', 0, '0', '0', 1, '0', 0, 0, 1, 10, '0', 0, NULL, NULL, '15', '515837', '{\"address\":\"TXKy4wMBUFZL2aHS5XgJcNCjYKodhAEQzH\",\"createdAt\":\"2025-02-06T11:58:47.386Z\",\"userId\":\"1\"}', '{\"address\":\"0xfB45bC49f4d038259Ff35124A42A09659A23de5A\",\"createdAt\":\"2025-02-06T11:58:46.416Z\",\"info\":{\"provider\":null,\"address\":\"0xfB45bC49f4d038259Ff35124A42A09659A23de5A\",\"publicKey\":\"0x03f9e014f9c9016fe9da7cf6ed4a0b4c7035e8af9af7ba63aaf4650b3e14cedd47\",\"fingerprint\":\"0xfee90400\",\"parentFingerprint\":\"0x15a58410\",\"chainCode\":\"0x6415cba0e99f31c0f2aaa2b4e3d8f0bf0db158c3bee2faaf7e8c5d08d568e8e1\",\"path\":\"m\\/44\'\\/60\'\\/0\'\\/0\\/0\",\"index\":0,\"depth\":5}}');

-- --------------------------------------------------------

--
-- Table structure for table `user_plans`
--

CREATE TABLE `user_plans` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `package_id` int(11) NOT NULL,
  `amount` varchar(255) NOT NULL,
  `compound_amount` varchar(255) NOT NULL DEFAULT '0',
  `roi` varchar(255) NOT NULL,
  `days` varchar(255) NOT NULL,
  `return` varchar(255) NOT NULL DEFAULT '0',
  `max_return` varchar(255) NOT NULL DEFAULT '0',
  `created_on` timestamp NULL DEFAULT current_timestamp(),
  `isSynced` varchar(255) DEFAULT '0',
  `isCount` int(11) NOT NULL DEFAULT 0,
  `transaction_hash` varchar(255) DEFAULT NULL,
  `unique_th` varchar(255) NOT NULL,
  `status` varchar(255) DEFAULT '1'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `user_plans`
--

INSERT INTO `user_plans` (`id`, `user_id`, `package_id`, `amount`, `compound_amount`, `roi`, `days`, `return`, `max_return`, `created_on`, `isSynced`, `isCount`, `transaction_hash`, `unique_th`, `status`) VALUES
(1, 1, 1, '120', '0', '1', '365', '0', '0', '2025-05-16 15:15:17', '1', 1, '-', '-', '1');

-- --------------------------------------------------------

--
-- Table structure for table `user_ranks`
--

CREATE TABLE `user_ranks` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `rank` varchar(255) NOT NULL,
  `week` int(11) NOT NULL,
  `amount` varchar(255) NOT NULL,
  `date` date NOT NULL,
  `created_on` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- --------------------------------------------------------

--
-- Table structure for table `withdraw`
--

CREATE TABLE `withdraw` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `withdraw_type` varchar(255) DEFAULT NULL,
  `amount` varchar(255) NOT NULL,
  `net_amount` varchar(255) DEFAULT NULL,
  `admin_charge` varchar(255) DEFAULT NULL,
  `daily_pool_amount` varchar(255) NOT NULL DEFAULT '0',
  `monthly_pool_amount` varchar(255) NOT NULL DEFAULT '0',
  `fees` int(11) NOT NULL DEFAULT 0,
  `status` int(11) NOT NULL,
  `transaction_hash` varchar(255) DEFAULT NULL,
  `claim_hash` longtext DEFAULT NULL,
  `coin_price` varchar(255) DEFAULT NULL,
  `created_on` timestamp NULL DEFAULT current_timestamp(),
  `processed_date` timestamp NULL DEFAULT current_timestamp(),
  `json_response` longtext DEFAULT NULL,
  `isSynced` int(11) NOT NULL DEFAULT 0,
  `isRequestSynced` int(11) NOT NULL DEFAULT 0,
  `requestResponse` text NOT NULL,
  `claimResponse` text NOT NULL,
  `checkClaim` int(11) NOT NULL DEFAULT 1,
  `checkRequest` int(11) NOT NULL DEFAULT 1,
  `isApi` int(11) NOT NULL DEFAULT 0,
  `signatureJson` longtext DEFAULT NULL,
  `remarks` longtext DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `admin`
--
ALTER TABLE `admin`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `earning_logs`
--
ALTER TABLE `earning_logs`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `level_earning_logs`
--
ALTER TABLE `level_earning_logs`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `level_profit_sharing`
--
ALTER TABLE `level_profit_sharing`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `level_roi`
--
ALTER TABLE `level_roi`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `login_logs`
--
ALTER TABLE `login_logs`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `my_team`
--
ALTER TABLE `my_team`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `package`
--
ALTER TABLE `package`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `package_transaction`
--
ALTER TABLE `package_transaction`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `pay9_payments`
--
ALTER TABLE `pay9_payments`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `pins`
--
ALTER TABLE `pins`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `ranking`
--
ALTER TABLE `ranking`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `rank_bonus`
--
ALTER TABLE `rank_bonus`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `setting`
--
ALTER TABLE `setting`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `support_tickets`
--
ALTER TABLE `support_tickets`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `transfer`
--
ALTER TABLE `transfer`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `wallet_address` (`wallet_address`),
  ADD KEY `id` (`id`,`wallet_address`,`refferal_code`),
  ADD KEY `sponser_id` (`sponser_id`);

--
-- Indexes for table `user_plans`
--
ALTER TABLE `user_plans`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_th` (`unique_th`),
  ADD KEY `id` (`id`,`user_id`,`unique_th`);

--
-- Indexes for table `user_ranks`
--
ALTER TABLE `user_ranks`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `withdraw`
--
ALTER TABLE `withdraw`
  ADD PRIMARY KEY (`id`),
  ADD KEY `id` (`id`,`user_id`,`transaction_hash`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `admin`
--
ALTER TABLE `admin`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `earning_logs`
--
ALTER TABLE `earning_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `level_earning_logs`
--
ALTER TABLE `level_earning_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `level_profit_sharing`
--
ALTER TABLE `level_profit_sharing`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=36;

--
-- AUTO_INCREMENT for table `level_roi`
--
ALTER TABLE `level_roi`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT for table `login_logs`
--
ALTER TABLE `login_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `my_team`
--
ALTER TABLE `my_team`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `package`
--
ALTER TABLE `package`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `package_transaction`
--
ALTER TABLE `package_transaction`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `pay9_payments`
--
ALTER TABLE `pay9_payments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `pins`
--
ALTER TABLE `pins`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `ranking`
--
ALTER TABLE `ranking`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `rank_bonus`
--
ALTER TABLE `rank_bonus`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `setting`
--
ALTER TABLE `setting`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `support_tickets`
--
ALTER TABLE `support_tickets`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `transfer`
--
ALTER TABLE `transfer`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `user_plans`
--
ALTER TABLE `user_plans`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `user_ranks`
--
ALTER TABLE `user_ranks`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `withdraw`
--
ALTER TABLE `withdraw`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
