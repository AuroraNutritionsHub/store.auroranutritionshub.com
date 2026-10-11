-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Oct 10, 2026 at 01:49 PM
-- Server version: 10.4.32-MariaDB-log
-- PHP Version: 8.2.29

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `gym_store`
--

-- --------------------------------------------------------

--
-- Table structure for table `admins`
--

CREATE TABLE `admins` (
  `id` int(10) UNSIGNED NOT NULL,
  `name` varchar(100) NOT NULL,
  `email` varchar(150) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('ADMIN','BILLER') NOT NULL DEFAULT 'ADMIN',
  `status` enum('ACTIVE','INACTIVE') NOT NULL DEFAULT 'ACTIVE',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `admins`
--

INSERT INTO `admins` (`id`, `name`, `email`, `password`, `role`, `status`, `created_at`, `updated_at`) VALUES
(1, 'Super Admin', 'admin@gymstore.com', '$2a$10$E.L2UROhCteyDlV2z8bMY.LFFlxMO4b/UUiyb/tlN2JRYkI/7/74S', 'ADMIN', 'ACTIVE', '2026-09-16 12:13:15', '2026-09-16 12:13:15'),
(3, 'KN', 'shashankkn2002@gmail.com', '$2a$10$s4Sw2Av9YKFMTAWtErwxi.yjMQj4DrRI9AX0jimpU5/NQ.SbQo0Ui', 'BILLER', 'ACTIVE', '2026-09-18 05:53:15', '2026-09-18 06:08:06');

-- --------------------------------------------------------

--
-- Table structure for table `carts`
--

CREATE TABLE `carts` (
  `id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `carts`
--

INSERT INTO `carts` (`id`, `user_id`, `created_at`, `updated_at`) VALUES
(1, 1, '2026-09-16 12:13:16', '2026-09-16 12:13:16'),
(2, 2, '2026-09-16 12:13:16', '2026-09-16 12:13:16'),
(3, 3, '2026-09-16 12:13:16', '2026-09-16 12:13:16'),
(4, 4, '2026-09-16 12:13:16', '2026-09-16 12:13:16'),
(5, 5, '2026-09-16 12:13:16', '2026-09-16 12:13:16');

-- --------------------------------------------------------

--
-- Table structure for table `cart_items`
--

CREATE TABLE `cart_items` (
  `id` int(10) UNSIGNED NOT NULL,
  `cart_id` int(10) UNSIGNED NOT NULL,
  `variant_id` int(10) UNSIGNED NOT NULL,
  `quantity` int(11) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `categories`
--

CREATE TABLE `categories` (
  `id` int(10) UNSIGNED NOT NULL,
  `name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `image` varchar(255) DEFAULT NULL,
  `status` enum('ACTIVE','INACTIVE') NOT NULL DEFAULT 'ACTIVE',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `categories`
--

INSERT INTO `categories` (`id`, `name`, `description`, `image`, `status`, `created_at`, `updated_at`) VALUES
(1, 'Protein', 'Protein products for muscle building and recovery', 'categories/protein.png', 'ACTIVE', '2026-09-16 12:13:16', '2026-09-16 12:13:16'),
(2, 'Supplements', 'Supplements products for muscle building and recovery', 'categories/supplements.png', 'ACTIVE', '2026-09-16 12:13:16', '2026-09-16 12:13:16'),
(3, 'Nuts', 'Nuts products for muscle building and recovery', 'categories/nuts.png', 'ACTIVE', '2026-09-16 12:13:16', '2026-09-16 12:13:16'),
(4, 'Protein Bars', 'Protein Bars products for muscle building and recovery', 'categories/protein-bars.png', 'ACTIVE', '2026-09-16 12:13:16', '2026-09-16 12:13:16'),
(5, 'Creatine', 'Creatine products for muscle building and recovery', 'categories/creatine.png', 'ACTIVE', '2026-09-16 12:13:16', '2026-09-16 12:13:16'),
(6, 'Vitamins', 'Vitamins products for muscle building and recovery', 'categories/vitamins.png', 'ACTIVE', '2026-09-16 12:13:16', '2026-09-16 12:13:16'),
(7, 'Pre Workout', 'Pre Workout products for muscle building and recovery', 'categories/pre-workout.png', 'ACTIVE', '2026-09-16 12:13:16', '2026-09-16 12:13:16'),
(8, 'Accessories', 'Accessories products for muscle building and recovery', 'categories/accessories.png', 'INACTIVE', '2026-09-16 12:13:16', '2026-09-16 12:13:16');

-- --------------------------------------------------------

--
-- Table structure for table `complaints`
--

CREATE TABLE `complaints` (
  `id` int(10) UNSIGNED NOT NULL,
  `gym_id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `order_id` int(10) UNSIGNED DEFAULT NULL,
  `subject` varchar(200) NOT NULL,
  `message` text NOT NULL,
  `status` enum('OPEN','RESOLVED') NOT NULL DEFAULT 'OPEN',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `complaints`
--

INSERT INTO `complaints` (`id`, `gym_id`, `user_id`, `order_id`, `subject`, `message`, `status`, `created_at`, `updated_at`) VALUES
(3, 1, 1, NULL, 'Delivery issues', 'I order one missing', 'RESOLVED', '2026-09-21 06:33:13', '2026-09-21 06:45:15'),
(5, 1, 1, NULL, 'Test', 'Test msg', 'RESOLVED', '2026-09-21 06:48:18', '2026-09-21 06:48:40');

-- --------------------------------------------------------

--
-- Table structure for table `gyms`
--

CREATE TABLE `gyms` (
  `id` int(10) UNSIGNED NOT NULL,
  `gym_name` varchar(150) NOT NULL,
  `gym_code` varchar(20) NOT NULL,
  `logo` varchar(255) DEFAULT NULL,
  `username` varchar(100) DEFAULT NULL,
  `password` varchar(255) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `email` varchar(150) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `status` enum('ACTIVE','INACTIVE') NOT NULL DEFAULT 'ACTIVE',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `gyms`
--

INSERT INTO `gyms` (`id`, `gym_name`, `gym_code`, `logo`, `username`, `password`, `phone`, `email`, `address`, `status`, `created_at`, `updated_at`) VALUES
(1, 'Muscle Factory', 'GYM001', 'gyms/gym001-logo.png', NULL, NULL, '9876500001', 'contact@musclefactory.com', 'MG Road, Bengaluru', 'ACTIVE', '2026-09-16 12:13:15', '2026-09-16 12:13:15'),
(2, 'Power Fitness', 'GYM002', 'gyms/gym002-logo.png', NULL, NULL, '9876500002', 'contact@powerfitness.com', 'Andheri West, Mumbai', 'ACTIVE', '2026-09-16 12:13:15', '2026-09-16 12:13:15'),
(3, 'Fit Zone', 'GYM003', 'gyms/gym003-logo.png', NULL, NULL, '9876500003', 'contact@fitzone.com', 'Sector 18, Noida', 'ACTIVE', '2026-09-16 12:13:15', '2026-09-16 12:13:15');

-- --------------------------------------------------------

--
-- Table structure for table `gym_products`
--

CREATE TABLE `gym_products` (
  `id` int(10) UNSIGNED NOT NULL,
  `gym_id` int(10) UNSIGNED NOT NULL,
  `product_id` int(10) UNSIGNED NOT NULL,
  `status` enum('ACTIVE','INACTIVE') NOT NULL DEFAULT 'ACTIVE',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `gym_products`
--

INSERT INTO `gym_products` (`id`, `gym_id`, `product_id`, `status`, `created_at`) VALUES
(1, 1, 1, 'ACTIVE', '2026-09-16 12:13:16'),
(2, 1, 2, 'ACTIVE', '2026-09-16 12:13:16'),
(3, 1, 3, 'ACTIVE', '2026-09-16 12:13:16'),
(4, 1, 5, 'ACTIVE', '2026-09-16 12:13:16'),
(5, 1, 7, 'ACTIVE', '2026-09-16 12:13:16'),
(6, 1, 9, 'ACTIVE', '2026-09-16 12:13:16'),
(7, 1, 10, 'ACTIVE', '2026-09-16 12:13:16'),
(8, 2, 2, 'ACTIVE', '2026-09-16 12:13:16'),
(9, 2, 3, 'ACTIVE', '2026-09-16 12:13:16'),
(10, 2, 4, 'ACTIVE', '2026-09-16 12:13:16'),
(11, 2, 6, 'ACTIVE', '2026-09-16 12:13:16'),
(12, 2, 8, 'ACTIVE', '2026-09-16 12:13:16'),
(13, 3, 1, 'ACTIVE', '2026-09-16 12:13:16'),
(14, 3, 2, 'ACTIVE', '2026-09-16 12:13:16'),
(15, 3, 4, 'ACTIVE', '2026-09-16 12:13:16'),
(16, 3, 5, 'ACTIVE', '2026-09-16 12:13:16'),
(17, 3, 7, 'ACTIVE', '2026-09-16 12:13:16'),
(18, 3, 8, 'ACTIVE', '2026-09-16 12:13:16'),
(19, 3, 10, 'ACTIVE', '2026-09-16 12:13:16');

-- --------------------------------------------------------

--
-- Table structure for table `gym_product_variants`
--

CREATE TABLE `gym_product_variants` (
  `id` int(10) UNSIGNED NOT NULL,
  `gym_id` int(10) UNSIGNED NOT NULL,
  `variant_id` int(10) UNSIGNED NOT NULL,
  `price` decimal(10,2) DEFAULT NULL,
  `stock` int(11) DEFAULT NULL,
  `status` enum('ACTIVE','INACTIVE') NOT NULL DEFAULT 'ACTIVE',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

CREATE TABLE `notifications` (
  `id` int(10) UNSIGNED NOT NULL,
  `gym_id` int(10) UNSIGNED DEFAULT NULL,
  `user_id` int(10) UNSIGNED DEFAULT NULL,
  `order_id` int(10) UNSIGNED DEFAULT NULL,
  `title` varchar(200) NOT NULL,
  `message` text NOT NULL,
  `type` varchar(50) DEFAULT 'GENERAL',
  `audience` enum('ADMIN','USER') NOT NULL DEFAULT 'USER',
  `is_read` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `notifications`
--

INSERT INTO `notifications` (`id`, `gym_id`, `user_id`, `order_id`, `title`, `message`, `type`, `audience`, `is_read`, `created_at`) VALUES
(1, 1, 1, 1, 'Order Placed', 'Your order ORD000001 has been received.', 'ORDER_PLACED', 'USER', 1, '2026-09-04 06:43:16'),
(2, 1, NULL, 1, 'New Order', 'Order ORD000001 received - ₹7350', 'NEW_ORDER', 'ADMIN', 1, '2026-09-04 06:43:16'),
(3, 1, 1, 2, 'Order Placed', 'Your order ORD000002 has been received.', 'ORDER_PLACED', 'USER', 1, '2026-09-13 06:43:16'),
(4, 1, NULL, 2, 'New Order', 'Order ORD000002 received - ₹1650', 'NEW_ORDER', 'ADMIN', 1, '2026-09-13 06:43:16'),
(5, 1, 2, 3, 'Order Placed', 'Your order ORD000003 has been received.', 'ORDER_PLACED', 'USER', 1, '2026-09-14 06:43:16'),
(6, 1, NULL, 3, 'New Order', 'Order ORD000003 received - ₹2150', 'NEW_ORDER', 'ADMIN', 1, '2026-09-14 06:43:16'),
(7, 1, 2, 4, 'Order Placed', 'Your order ORD000004 has been received.', 'ORDER_PLACED', 'USER', 1, '2026-09-15 06:43:16'),
(8, 1, NULL, 4, 'New Order', 'Order ORD000004 received - ₹1550', 'NEW_ORDER', 'ADMIN', 1, '2026-09-15 06:43:16'),
(9, 2, 3, 5, 'Order Placed', 'Your order ORD000005 has been received.', 'ORDER_PLACED', 'USER', 1, '2026-09-07 06:43:16'),
(10, 2, NULL, 5, 'New Order', 'Order ORD000005 received - ₹6850', 'NEW_ORDER', 'ADMIN', 1, '2026-09-07 06:43:16'),
(11, 2, 3, 6, 'Order Placed', 'Your order ORD000006 has been received.', 'ORDER_PLACED', 'USER', 1, '2026-09-16 06:43:16'),
(12, 2, NULL, 6, 'New Order', 'Order ORD000006 received - ₹1650', 'NEW_ORDER', 'ADMIN', 1, '2026-09-16 06:43:16'),
(13, 2, 4, 7, 'Order Placed', 'Your order ORD000007 has been received.', 'ORDER_PLACED', 'USER', 1, '2026-09-10 06:43:16'),
(14, 2, NULL, 7, 'New Order', 'Order ORD000007 received - ₹3600', 'NEW_ORDER', 'ADMIN', 1, '2026-09-10 06:43:16'),
(15, 2, 4, 8, 'Order Placed', 'Your order ORD000008 has been received.', 'ORDER_PLACED', 'USER', 1, '2026-09-16 06:43:16'),
(16, 2, NULL, 8, 'New Order', 'Order ORD000008 received - ₹1450', 'NEW_ORDER', 'ADMIN', 1, '2026-09-16 06:43:16'),
(17, 3, 5, 9, 'Order Placed', 'Your order ORD000009 has been received.', 'ORDER_PLACED', 'USER', 1, '2026-09-02 06:43:16'),
(18, 3, NULL, 9, 'New Order', 'Order ORD000009 received - ₹6500', 'NEW_ORDER', 'ADMIN', 1, '2026-09-02 06:43:16'),
(19, 3, 5, 10, 'Order Placed', 'Your order ORD000010 has been received.', 'ORDER_PLACED', 'USER', 1, '2026-09-12 06:43:16'),
(20, 3, NULL, 10, 'New Order', 'Order ORD000010 received - ₹970', 'NEW_ORDER', 'ADMIN', 1, '2026-09-12 06:43:16'),
(21, 3, 5, 11, 'Order Placed', 'Your order ORD000011 has been received.', 'ORDER_PLACED', 'USER', 1, '2026-09-15 06:43:16'),
(22, 3, NULL, 11, 'New Order', 'Order ORD000011 received - ₹6850', 'NEW_ORDER', 'ADMIN', 1, '2026-09-15 06:43:16'),
(23, 1, NULL, 16, 'New Order', 'Order ORD000016 received from Rahul Sharma - ₹170', 'NEW_ORDER', 'ADMIN', 1, '2026-09-17 06:01:56'),
(24, 1, 1, 16, 'Order Placed', 'Your order ORD000016 has been received.', 'ORDER_PLACED', 'USER', 1, '2026-09-17 06:01:56'),
(25, 1, NULL, 26, 'New Order', 'Order ORD000026 received from Rahul Sharma - ₹950', 'NEW_ORDER', 'ADMIN', 1, '2026-09-21 06:15:44'),
(26, 1, 1, 26, 'Order Placed', 'Your order ORD000026 has been received.', 'ORDER_PLACED', 'USER', 1, '2026-09-21 06:15:44'),
(31, 1, NULL, NULL, 'New Complaint', 'Rahul Sharma raised: Delivery issues', 'NEW_COMPLAINT', 'ADMIN', 1, '2026-09-21 06:33:13'),
(32, 1, 1, NULL, 'Reply to your complaint', 'Super Admin: wht orde missing', 'COMPLAINT_REPLY', 'USER', 1, '2026-09-21 06:33:46'),
(33, 1, 1, NULL, 'Reply to your complaint', 'Super Admin: share the bill', 'COMPLAINT_REPLY', 'USER', 1, '2026-09-21 06:34:06'),
(34, 1, 1, NULL, 'Complaint resolved', 'Your complaint \"Delivery issues\" has been marked resolved.', 'COMPLAINT_RESOLVED', 'USER', 1, '2026-09-21 06:34:20'),
(35, 1, 1, NULL, 'Complaint resolved', 'Your complaint \"Delivery issues\" has been marked resolved.', 'COMPLAINT_RESOLVED', 'USER', 1, '2026-09-21 06:34:24'),
(36, 1, 1, NULL, 'Complaint resolved', 'Your complaint \"Delivery issues\" has been marked resolved.', 'COMPLAINT_RESOLVED', 'USER', 1, '2026-09-21 06:45:15'),
(38, 1, NULL, NULL, 'New Complaint', 'Rahul Sharma raised: Test', 'NEW_COMPLAINT', 'ADMIN', 1, '2026-09-21 06:48:18'),
(39, 1, 1, NULL, 'Complaint resolved', 'Your complaint \"Test\" has been marked resolved.', 'COMPLAINT_RESOLVED', 'USER', 1, '2026-09-21 06:48:40'),
(40, 1, NULL, 28, 'New Order', 'Order ORD000028 received from Rahul Sharma - ₹4150', 'NEW_ORDER', 'ADMIN', 1, '2026-09-21 07:33:15'),
(41, 1, 1, 28, 'Order Placed', 'Your order ORD000028 has been received.', 'ORDER_PLACED', 'USER', 1, '2026-09-21 07:33:15'),
(42, 1, 1, 28, 'CONFIRMED', 'Your order has been confirmed. (Order ORD000028)', 'ORDER_STATUS', 'USER', 1, '2026-09-21 07:40:38'),
(43, 1, 1, 28, 'PREPARING', 'Your order is being prepared. (Order ORD000028)', 'ORDER_STATUS', 'USER', 1, '2026-09-21 07:40:41'),
(44, 1, NULL, 29, 'New Order', 'Order ORD000029 received from Rahul Sharma - ₹200', 'NEW_ORDER', 'ADMIN', 0, '2026-10-01 09:43:55'),
(45, 1, 1, 29, 'Order Placed', 'Your order ORD000029 has been received.', 'ORDER_PLACED', 'USER', 0, '2026-10-01 09:43:55'),
(46, 1, NULL, 29, 'Payment Submitted', 'Payment screenshot submitted for order ORD000029 - ₹200.00', 'PAYMENT_SUBMITTED', 'ADMIN', 0, '2026-10-01 09:44:10'),
(47, 1, 1, 29, 'Payment Verified', 'Your payment for order ORD000029 has been verified. Your order is confirmed.', 'PAYMENT_VERIFIED', 'USER', 0, '2026-10-01 09:44:52'),
(48, 1, NULL, 30, 'New Order', 'Order ORD000030 received from Rahul Sharma - ₹1550', 'NEW_ORDER', 'ADMIN', 0, '2026-10-01 09:45:06'),
(49, 1, 1, 30, 'Order Placed', 'Your order ORD000030 has been received.', 'ORDER_PLACED', 'USER', 0, '2026-10-01 09:45:06');

-- --------------------------------------------------------

--
-- Table structure for table `orders`
--

CREATE TABLE `orders` (
  `id` int(10) UNSIGNED NOT NULL,
  `order_number` varchar(20) NOT NULL,
  `gym_id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED DEFAULT NULL,
  `customer_name` varchar(100) DEFAULT NULL,
  `customer_phone` varchar(20) DEFAULT NULL,
  `source` enum('ONLINE','ONSITE') NOT NULL DEFAULT 'ONLINE',
  `subtotal` decimal(10,2) NOT NULL DEFAULT 0.00,
  `discount_percent` decimal(5,2) NOT NULL DEFAULT 0.00,
  `discount_amount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `delivery_charge` decimal(10,2) NOT NULL DEFAULT 0.00,
  `total_amount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `payment_status` enum('PENDING','SUBMITTED','VERIFIED','REJECTED') NOT NULL DEFAULT 'PENDING',
  `order_status` enum('PLACED','PAYMENT_SUBMITTED','CONFIRMED','PREPARING','READY_FOR_DELIVERY','OUT_FOR_DELIVERY','DELIVERED','CANCELLED') NOT NULL DEFAULT 'PLACED',
  `delivery_date` date DEFAULT NULL,
  `delivery_time` varchar(50) DEFAULT NULL,
  `delivery_address` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `orders`
--

INSERT INTO `orders` (`id`, `order_number`, `gym_id`, `user_id`, `customer_name`, `customer_phone`, `source`, `subtotal`, `discount_percent`, `discount_amount`, `delivery_charge`, `total_amount`, `payment_status`, `order_status`, `delivery_date`, `delivery_time`, `delivery_address`, `created_at`, `updated_at`) VALUES
(1, 'ORD000001', 1, 1, NULL, NULL, 'ONLINE', 7300.00, 0.00, 0.00, 50.00, 7350.00, 'VERIFIED', 'DELIVERED', '2026-09-06', '10:00 AM - 12:00 PM', 'Reception Desk, GYM001', '2026-09-04 06:43:16', '2026-09-04 06:43:16'),
(2, 'ORD000002', 1, 1, NULL, NULL, 'ONLINE', 1600.00, 0.00, 0.00, 50.00, 1650.00, 'VERIFIED', 'OUT_FOR_DELIVERY', '2026-09-15', '10:00 AM - 12:00 PM', 'Reception Desk, GYM001', '2026-09-13 06:43:16', '2026-09-13 06:43:16'),
(3, 'ORD000003', 1, 2, NULL, NULL, 'ONLINE', 2100.00, 0.00, 0.00, 50.00, 2150.00, 'VERIFIED', 'PREPARING', '2026-09-16', '10:00 AM - 12:00 PM', 'Reception Desk, GYM001', '2026-09-14 06:43:16', '2026-09-14 06:43:16'),
(4, 'ORD000004', 1, 2, NULL, NULL, 'ONLINE', 1500.00, 0.00, 0.00, 50.00, 1550.00, 'VERIFIED', 'CONFIRMED', '2026-09-17', '10:00 AM - 12:00 PM', 'Reception Desk, GYM001', '2026-09-15 06:43:16', '2026-09-15 06:43:16'),
(5, 'ORD000005', 2, 3, NULL, NULL, 'ONLINE', 6800.00, 0.00, 0.00, 50.00, 6850.00, 'VERIFIED', 'DELIVERED', '2026-09-09', '10:00 AM - 12:00 PM', 'Reception Desk, GYM002', '2026-09-07 06:43:16', '2026-09-07 06:43:16'),
(6, 'ORD000006', 2, 3, NULL, NULL, 'ONLINE', 1600.00, 0.00, 0.00, 50.00, 1650.00, 'SUBMITTED', 'PAYMENT_SUBMITTED', NULL, NULL, 'Reception Desk, GYM002', '2026-09-16 06:43:16', '2026-09-16 06:43:16'),
(7, 'ORD000007', 2, 4, NULL, NULL, 'ONLINE', 3550.00, 0.00, 0.00, 50.00, 3600.00, 'VERIFIED', 'DELIVERED', '2026-09-12', '10:00 AM - 12:00 PM', 'Reception Desk, GYM002', '2026-09-10 06:43:16', '2026-09-10 06:43:16'),
(8, 'ORD000008', 2, 4, NULL, NULL, 'ONLINE', 1400.00, 0.00, 0.00, 50.00, 1450.00, 'PENDING', 'PLACED', NULL, NULL, 'Reception Desk, GYM002', '2026-09-16 06:43:16', '2026-09-16 06:43:16'),
(9, 'ORD000009', 3, 5, NULL, NULL, 'ONLINE', 6450.00, 0.00, 0.00, 50.00, 6500.00, 'VERIFIED', 'DELIVERED', '2026-09-04', '10:00 AM - 12:00 PM', 'Reception Desk, GYM003', '2026-09-02 06:43:16', '2026-09-02 06:43:16'),
(10, 'ORD000010', 3, 5, NULL, NULL, 'ONLINE', 920.00, 0.00, 0.00, 50.00, 970.00, 'REJECTED', 'CANCELLED', NULL, NULL, 'Reception Desk, GYM003', '2026-09-12 06:43:16', '2026-09-12 06:43:16'),
(11, 'ORD000011', 3, 5, NULL, NULL, 'ONLINE', 6800.00, 0.00, 0.00, 50.00, 6850.00, 'VERIFIED', 'READY_FOR_DELIVERY', '2026-09-17', '10:00 AM - 12:00 PM', 'Reception Desk, GYM003', '2026-09-15 06:43:16', '2026-09-15 06:43:16'),
(12, 'ORD000012', 1, NULL, 'Walk-in Test', '9999999999', 'ONSITE', 2400.00, 10.00, 240.00, 0.00, 2160.00, 'VERIFIED', 'DELIVERED', NULL, NULL, '123 Test Street', '2026-09-16 12:14:44', '2026-09-16 12:14:44'),
(13, 'ORD000013', 1, NULL, 'Name Only Test', NULL, 'ONSITE', 1200.00, 0.00, 0.00, 0.00, 1200.00, 'VERIFIED', 'DELIVERED', NULL, NULL, 'Onsite / Walk-in Purchase', '2026-09-16 12:18:09', '2026-09-16 12:18:09'),
(14, 'ORD000014', 1, NULL, 'shashank', '8618496388', 'ONSITE', 1300.00, 0.00, 0.00, 0.00, 1300.00, 'VERIFIED', 'DELIVERED', NULL, NULL, 'bantwal', '2026-09-16 12:31:37', '2026-09-16 12:31:37'),
(15, 'ORD000015', 1, NULL, 'dfgfd', '55677', 'ONSITE', 3900.00, 5.00, 195.00, 0.00, 3705.00, 'VERIFIED', 'DELIVERED', NULL, NULL, 'saa', '2026-09-16 12:32:29', '2026-09-16 12:32:29'),
(16, 'ORD000016', 1, 1, NULL, NULL, 'ONLINE', 120.00, 0.00, 0.00, 50.00, 170.00, 'PENDING', 'PLACED', NULL, NULL, 'Fycy y', '2026-09-17 06:01:56', '2026-09-17 06:01:56'),
(17, 'ORD000017', 1, NULL, 'kn', '8618496388', 'ONSITE', 150.00, 0.00, 0.00, 0.00, 150.00, 'VERIFIED', 'DELIVERED', NULL, NULL, 'bantwal', '2026-09-18 05:25:43', '2026-09-18 05:25:43'),
(19, 'ORD000018', 1, NULL, 'test', '83222222', 'ONSITE', 200.00, 0.00, 0.00, 0.00, 200.00, 'VERIFIED', 'DELIVERED', NULL, NULL, 'asdsa', '2026-09-18 05:54:34', '2026-09-18 05:54:34'),
(20, 'ORD000020', 1, NULL, 'dfd', 'dfd', 'ONSITE', 650.00, 0.00, 0.00, 0.00, 650.00, 'VERIFIED', 'DELIVERED', NULL, NULL, 'dfvdf', '2026-09-18 06:01:51', '2026-09-18 06:01:51'),
(21, 'ORD000021', 1, NULL, 'dgfg', '3233', 'ONSITE', 150.00, 0.00, 0.00, 0.00, 150.00, 'VERIFIED', 'DELIVERED', NULL, NULL, 'sdsad', '2026-09-18 06:07:00', '2026-09-18 06:07:00'),
(22, 'ORD000022', 1, NULL, 'dfd', 'g', 'ONSITE', 5100.00, 0.00, 0.00, 0.00, 5100.00, 'VERIFIED', 'DELIVERED', NULL, NULL, 'tyyy', '2026-09-18 06:08:55', '2026-09-18 06:08:55'),
(23, 'ORD000023', 1, NULL, 'KN', '8618496388', 'ONSITE', 2350.00, 5.00, 117.50, 0.00, 2232.50, 'VERIFIED', 'DELIVERED', NULL, NULL, 'BANTWAL', '2026-09-18 12:08:23', '2026-09-18 12:08:23'),
(24, 'ORD000024', 1, NULL, 'HGFHG', 'JHGJH', 'ONSITE', 200.00, 0.00, 0.00, 0.00, 200.00, 'VERIFIED', 'DELIVERED', NULL, NULL, 'HJJHJH', '2026-09-18 12:39:43', '2026-09-18 12:39:43'),
(25, 'ORD000025', 1, NULL, 'KN', '8618496388', 'ONSITE', 1900.00, 0.00, 0.00, 0.00, 1900.00, 'VERIFIED', 'DELIVERED', NULL, NULL, 'BANTWAL', '2026-09-21 05:09:35', '2026-09-21 05:09:35'),
(26, 'ORD000026', 1, 1, NULL, NULL, 'ONLINE', 900.00, 0.00, 0.00, 50.00, 950.00, 'PENDING', 'PLACED', NULL, NULL, 'Bangalore', '2026-09-21 06:15:44', '2026-09-21 06:15:44'),
(27, 'ORD000027', 1, NULL, 'jhj', '44', 'ONSITE', 1000.00, 0.00, 0.00, 0.00, 1000.00, 'VERIFIED', 'DELIVERED', NULL, NULL, '4544hgfhgf', '2026-09-21 06:52:41', '2026-09-21 06:52:41'),
(28, 'ORD000028', 1, 1, NULL, NULL, 'ONLINE', 4100.00, 0.00, 0.00, 50.00, 4150.00, 'PENDING', 'PREPARING', NULL, NULL, 'Heysj', '2026-09-21 07:33:15', '2026-09-21 07:40:41'),
(29, 'ORD000029', 1, 1, NULL, NULL, 'ONLINE', 150.00, 0.00, 0.00, 50.00, 200.00, 'VERIFIED', 'CONFIRMED', NULL, NULL, '5cycy', '2026-10-01 09:43:55', '2026-10-01 09:44:52'),
(30, 'ORD000030', 1, 1, NULL, NULL, 'ONLINE', 1500.00, 0.00, 0.00, 50.00, 1550.00, 'PENDING', 'PLACED', NULL, NULL, 'Fth', '2026-10-01 09:45:06', '2026-10-01 09:45:06');

-- --------------------------------------------------------

--
-- Table structure for table `order_items`
--

CREATE TABLE `order_items` (
  `id` int(10) UNSIGNED NOT NULL,
  `order_id` int(10) UNSIGNED NOT NULL,
  `product_id` int(10) UNSIGNED NOT NULL,
  `variant_id` int(10) UNSIGNED NOT NULL,
  `product_name` varchar(150) NOT NULL,
  `variant_name` varchar(100) NOT NULL,
  `quantity` int(11) NOT NULL,
  `unit_price` decimal(10,2) NOT NULL,
  `total_price` decimal(10,2) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `order_items`
--

INSERT INTO `order_items` (`id`, `order_id`, `product_id`, `variant_id`, `product_name`, `variant_name`, `quantity`, `unit_price`, `total_price`, `created_at`) VALUES
(1, 1, 2, 5, 'Whey Protein Gold', '2 KG', 2, 3200.00, 6400.00, '2026-09-04 06:43:16'),
(2, 1, 3, 7, '100% Creatine Monohydrate', '250 GM', 1, 900.00, 900.00, '2026-09-04 06:43:16'),
(3, 2, 1, 1, 'Hyper Mass', '1 KG', 1, 1200.00, 1200.00, '2026-09-13 06:43:16'),
(4, 2, 10, 24, 'Shaker Bottle', '700 ML', 2, 200.00, 400.00, '2026-09-13 06:43:16'),
(5, 3, 7, 17, 'Protein Bar Choco', '6 Pieces', 2, 650.00, 1300.00, '2026-09-14 06:43:16'),
(6, 3, 5, 13, 'Premium Almonds', '1 KG', 1, 800.00, 800.00, '2026-09-14 06:43:16'),
(7, 4, 9, 21, 'Pre Workout Blast', '300 GM', 1, 1500.00, 1500.00, '2026-09-15 06:43:16'),
(8, 5, 2, 4, 'Whey Protein Gold', '1 KG', 1, 1800.00, 1800.00, '2026-09-07 06:43:16'),
(9, 5, 4, 10, 'BCAA Zero', '1 KG', 2, 2500.00, 5000.00, '2026-09-07 06:43:16'),
(10, 6, 8, 20, 'Daily Multivitamin', '120 Tablets', 2, 800.00, 1600.00, '2026-09-16 06:43:16'),
(11, 7, 6, 14, 'Peanut Butter Crunchy', '500 GM', 1, 350.00, 350.00, '2026-09-10 06:43:16'),
(12, 7, 3, 8, '100% Creatine Monohydrate', '500 GM', 2, 1600.00, 3200.00, '2026-09-10 06:43:16'),
(13, 8, 4, 9, 'BCAA Zero', '500 GM', 1, 1400.00, 1400.00, '2026-09-16 06:43:16'),
(14, 9, 1, 2, 'Hyper Mass', '3 KG', 2, 3000.00, 6000.00, '2026-09-02 06:43:16'),
(15, 9, 8, 19, 'Daily Multivitamin', '60 Tablets', 1, 450.00, 450.00, '2026-09-02 06:43:16'),
(16, 10, 5, 13, 'Premium Almonds', '1 KG', 1, 800.00, 800.00, '2026-09-12 06:43:16'),
(17, 10, 7, 16, 'Protein Bar Choco', '1 Piece', 1, 120.00, 120.00, '2026-09-12 06:43:16'),
(18, 11, 10, 24, 'Shaker Bottle', '700 ML', 2, 200.00, 400.00, '2026-09-15 06:43:16'),
(19, 11, 2, 5, 'Whey Protein Gold', '2 KG', 2, 3200.00, 6400.00, '2026-09-15 06:43:16'),
(20, 12, 1, 1, 'Hyper Mass', '1 KG', 2, 1200.00, 2400.00, '2026-09-16 12:14:44'),
(21, 13, 1, 1, 'Hyper Mass', '1 KG', 1, 1200.00, 1200.00, '2026-09-16 12:18:09'),
(22, 14, 7, 17, 'Protein Bar Choco', '6 Pieces', 2, 650.00, 1300.00, '2026-09-16 12:31:37'),
(23, 15, 7, 18, 'Protein Bar Choco', '12 Pieces', 1, 1200.00, 1200.00, '2026-09-16 12:32:29'),
(24, 15, 9, 22, 'Pre Workout Blast', '600 GM', 1, 2700.00, 2700.00, '2026-09-16 12:32:29'),
(25, 16, 7, 16, 'Protein Bar Choco', '1 Piece', 1, 120.00, 120.00, '2026-09-17 06:01:56'),
(26, 17, 10, 23, 'Shaker Bottle', '500 ML', 1, 150.00, 150.00, '2026-09-18 05:25:43'),
(28, 19, 10, 24, 'Shaker Bottle', '700 ML', 1, 200.00, 200.00, '2026-09-18 05:54:34'),
(29, 20, 7, 17, 'Protein Bar Choco', '6 Pieces', 1, 650.00, 650.00, '2026-09-18 06:01:51'),
(30, 21, 10, 23, 'Shaker Bottle', '500 ML', 1, 150.00, 150.00, '2026-09-18 06:07:00'),
(31, 22, 3, 8, '100% Creatine Monohydrate', '500 GM', 3, 1700.00, 5100.00, '2026-09-18 06:08:55'),
(32, 23, 10, 24, 'Shaker Bottle', '700 ML', 1, 200.00, 200.00, '2026-09-18 12:08:23'),
(33, 23, 3, 8, '100% Creatine Monohydrate', '500 GM', 1, 1700.00, 1700.00, '2026-09-18 12:08:23'),
(34, 23, 5, 12, 'Premium Almonds', '500 GM', 1, 450.00, 450.00, '2026-09-18 12:08:23'),
(35, 24, 10, 24, 'Shaker Bottle', '700 ML', 1, 200.00, 200.00, '2026-09-18 12:39:43'),
(36, 25, 10, 24, 'Shaker Bottle', '700 ML', 1, 200.00, 200.00, '2026-09-21 05:09:35'),
(37, 25, 3, 8, '100% Creatine Monohydrate', '500 GM', 1, 1700.00, 1700.00, '2026-09-21 05:09:35'),
(38, 26, 3, 7, '100% Creatine Monohydrate', '250 GM', 1, 900.00, 900.00, '2026-09-21 06:15:44'),
(39, 27, 3, 7, '100% Creatine Monohydrate', '250 GM', 1, 1000.00, 1000.00, '2026-09-21 06:52:41'),
(40, 28, 3, 8, '100% Creatine Monohydrate', '500 GM', 2, 1600.00, 3200.00, '2026-09-21 07:33:15'),
(41, 28, 3, 7, '100% Creatine Monohydrate', '250 GM', 1, 900.00, 900.00, '2026-09-21 07:33:15'),
(42, 29, 10, 23, 'Shaker Bottle', '500 ML', 1, 150.00, 150.00, '2026-10-01 09:43:55'),
(43, 30, 9, 21, 'Pre Workout Blast', '300 GM', 1, 1500.00, 1500.00, '2026-10-01 09:45:06');

-- --------------------------------------------------------

--
-- Table structure for table `payments`
--

CREATE TABLE `payments` (
  `id` int(10) UNSIGNED NOT NULL,
  `order_id` int(10) UNSIGNED NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `payment_method` varchar(50) DEFAULT 'UPI_QR',
  `transaction_id` varchar(150) DEFAULT NULL,
  `screenshot` varchar(255) DEFAULT NULL,
  `payment_status` enum('PENDING','SUBMITTED','VERIFIED','REJECTED') NOT NULL DEFAULT 'PENDING',
  `verified_by` int(10) UNSIGNED DEFAULT NULL,
  `verified_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `payments`
--

INSERT INTO `payments` (`id`, `order_id`, `amount`, `payment_method`, `transaction_id`, `screenshot`, `payment_status`, `verified_by`, `verified_at`, `created_at`, `updated_at`) VALUES
(1, 1, 7350.00, 'UPI_QR', 'DEMO100000002', 'payments/demo-payment-2.png', 'VERIFIED', NULL, '2026-09-04 06:43:16', '2026-09-04 06:43:16', '2026-09-04 06:43:16'),
(2, 2, 1650.00, 'UPI_QR', 'DEMO100000003', 'payments/demo-payment-3.png', 'VERIFIED', NULL, '2026-09-13 06:43:16', '2026-09-13 06:43:16', '2026-09-13 06:43:16'),
(3, 3, 2150.00, 'UPI_QR', 'DEMO100000004', 'payments/demo-payment-4.png', 'VERIFIED', NULL, '2026-09-14 06:43:16', '2026-09-14 06:43:16', '2026-09-14 06:43:16'),
(4, 4, 1550.00, 'UPI_QR', 'DEMO100000005', 'payments/demo-payment-5.png', 'VERIFIED', NULL, '2026-09-15 06:43:16', '2026-09-15 06:43:16', '2026-09-15 06:43:16'),
(5, 5, 6850.00, 'UPI_QR', 'DEMO100000006', 'payments/demo-payment-6.png', 'VERIFIED', NULL, '2026-09-07 06:43:16', '2026-09-07 06:43:16', '2026-09-07 06:43:16'),
(6, 6, 1650.00, 'UPI_QR', 'DEMO100000007', 'payments/demo-payment-7.png', 'SUBMITTED', NULL, NULL, '2026-09-16 06:43:16', '2026-09-16 06:43:16'),
(7, 7, 3600.00, 'UPI_QR', 'DEMO100000008', 'payments/demo-payment-8.png', 'VERIFIED', NULL, '2026-09-10 06:43:16', '2026-09-10 06:43:16', '2026-09-10 06:43:16'),
(8, 8, 1450.00, 'UPI_QR', NULL, NULL, 'PENDING', NULL, NULL, '2026-09-16 06:43:16', '2026-09-16 06:43:16'),
(9, 9, 6500.00, 'UPI_QR', 'DEMO100000010', 'payments/demo-payment-10.png', 'VERIFIED', NULL, '2026-09-02 06:43:16', '2026-09-02 06:43:16', '2026-09-02 06:43:16'),
(10, 10, 970.00, 'UPI_QR', 'DEMO100000011', 'payments/demo-payment-11.png', 'REJECTED', NULL, '2026-09-12 06:43:16', '2026-09-12 06:43:16', '2026-09-12 06:43:16'),
(11, 11, 6850.00, 'UPI_QR', 'DEMO100000012', 'payments/demo-payment-12.png', 'VERIFIED', NULL, '2026-09-15 06:43:16', '2026-09-15 06:43:16', '2026-09-15 06:43:16'),
(12, 12, 2160.00, 'ONSITE_CASH', NULL, NULL, 'VERIFIED', 1, '2026-09-16 12:14:44', '2026-09-16 12:14:44', '2026-09-16 12:14:44'),
(13, 13, 1200.00, 'ONSITE_CASH', NULL, NULL, 'VERIFIED', 1, '2026-09-16 12:18:09', '2026-09-16 12:18:09', '2026-09-16 12:18:09'),
(14, 14, 1300.00, 'ONSITE_CASH', NULL, NULL, 'VERIFIED', 1, '2026-09-16 12:31:37', '2026-09-16 12:31:37', '2026-09-16 12:31:37'),
(15, 15, 3705.00, 'ONSITE_CASH', NULL, NULL, 'VERIFIED', 1, '2026-09-16 12:32:29', '2026-09-16 12:32:29', '2026-09-16 12:32:29'),
(16, 16, 170.00, 'UPI_QR', NULL, NULL, 'PENDING', NULL, NULL, '2026-09-17 06:01:56', '2026-09-17 06:01:56'),
(17, 17, 150.00, 'ONSITE_CASH', NULL, NULL, 'VERIFIED', 1, '2026-09-18 05:25:43', '2026-09-18 05:25:43', '2026-09-18 05:25:43'),
(19, 19, 200.00, 'UPI', NULL, NULL, 'VERIFIED', 1, '2026-09-18 05:54:34', '2026-09-18 05:54:34', '2026-09-18 05:54:34'),
(20, 20, 650.00, 'UPI', NULL, NULL, 'VERIFIED', 3, '2026-09-18 06:01:51', '2026-09-18 06:01:51', '2026-09-18 06:01:51'),
(21, 21, 150.00, 'CASH', NULL, NULL, 'VERIFIED', 3, '2026-09-18 06:07:00', '2026-09-18 06:07:00', '2026-09-18 06:07:00'),
(22, 22, 5100.00, 'UPI', NULL, NULL, 'VERIFIED', 3, '2026-09-18 06:08:55', '2026-09-18 06:08:55', '2026-09-18 06:08:55'),
(23, 23, 2232.50, 'UPI', NULL, NULL, 'VERIFIED', 1, '2026-09-18 12:08:23', '2026-09-18 12:08:23', '2026-09-18 12:08:23'),
(24, 24, 200.00, 'CASH', NULL, NULL, 'VERIFIED', 1, '2026-09-18 12:39:43', '2026-09-18 12:39:43', '2026-09-18 12:39:43'),
(25, 25, 1900.00, 'CASH', NULL, NULL, 'VERIFIED', 1, '2026-09-21 05:09:35', '2026-09-21 05:09:35', '2026-09-21 05:09:35'),
(26, 26, 950.00, 'UPI_QR', NULL, NULL, 'PENDING', NULL, NULL, '2026-09-21 06:15:44', '2026-09-21 06:15:44'),
(27, 27, 1000.00, 'CASH', NULL, NULL, 'VERIFIED', 1, '2026-09-21 06:52:41', '2026-09-21 06:52:41', '2026-09-21 06:52:41'),
(28, 28, 4150.00, 'UPI_QR', NULL, NULL, 'PENDING', NULL, NULL, '2026-09-21 07:33:15', '2026-09-21 07:33:15'),
(29, 29, 200.00, 'UPI_QR', 'Hchch', 'payments/1790847850875-7427855.jpg', 'VERIFIED', 1, '2026-10-01 09:44:52', '2026-10-01 09:43:55', '2026-10-01 09:44:52'),
(30, 30, 1550.00, 'UPI_QR', NULL, NULL, 'PENDING', NULL, NULL, '2026-10-01 09:45:06', '2026-10-01 09:45:06');

-- --------------------------------------------------------

--
-- Table structure for table `payment_settings`
--

CREATE TABLE `payment_settings` (
  `id` int(10) UNSIGNED NOT NULL,
  `gym_id` int(10) UNSIGNED NOT NULL,
  `upi_id` varchar(150) DEFAULT NULL,
  `account_name` varchar(150) DEFAULT NULL,
  `qr_image` varchar(255) DEFAULT NULL,
  `instructions` text DEFAULT NULL,
  `status` enum('ACTIVE','INACTIVE') NOT NULL DEFAULT 'ACTIVE',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `payment_settings`
--

INSERT INTO `payment_settings` (`id`, `gym_id`, `upi_id`, `account_name`, `qr_image`, `instructions`, `status`, `created_at`, `updated_at`) VALUES
(1, 1, 'musclefactory@upi', 'Muscle Factory', 'qr/gym001-qr.png', '1. Scan the QR code using your UPI application.\n2. Pay the exact amount.\n3. Enter your transaction ID.\n4. Upload payment screenshot.\n5. Submit payment details.', 'ACTIVE', '2026-09-16 12:13:16', '2026-09-16 12:13:16'),
(2, 2, 'powerfitness@upi', 'Power Fitness', 'qr/gym002-qr.png', '1. Scan the QR code using your UPI application.\n2. Pay the exact amount.\n3. Enter your transaction ID.\n4. Upload payment screenshot.\n5. Submit payment details.', 'ACTIVE', '2026-09-16 12:13:16', '2026-09-16 12:13:16'),
(3, 3, 'fitzone@upi', 'Fit Zone', 'qr/gym003-qr.png', '1. Scan the QR code using your UPI application.\n2. Pay the exact amount.\n3. Enter your transaction ID.\n4. Upload payment screenshot.\n5. Submit payment details.', 'ACTIVE', '2026-09-16 12:13:16', '2026-09-16 12:13:16');

-- --------------------------------------------------------

--
-- Table structure for table `products`
--

CREATE TABLE `products` (
  `id` int(10) UNSIGNED NOT NULL,
  `category_id` int(10) UNSIGNED NOT NULL,
  `name` varchar(150) NOT NULL,
  `brand` varchar(100) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `image` varchar(255) DEFAULT NULL,
  `status` enum('ACTIVE','INACTIVE') NOT NULL DEFAULT 'ACTIVE',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `products`
--

INSERT INTO `products` (`id`, `category_id`, `name`, `brand`, `description`, `image`, `status`, `created_at`, `updated_at`) VALUES
(1, 1, 'Hyper Mass', 'Biotech USA', 'High-calorie mass gainer formulated for serious muscle and weight gain.', 'products/hyper_mass.png', 'ACTIVE', '2026-09-16 12:13:16', '2026-09-16 12:13:16'),
(2, 1, 'Whey Protein Gold', 'Biotech USA', 'Premium whey protein concentrate for fast post-workout recovery.', 'products/whey_gold.png', 'ACTIVE', '2026-09-16 12:13:16', '2026-09-16 12:13:16'),
(3, 5, '100% Creatine Monohydrate', 'Biotech USA', 'Pure micronized creatine monohydrate for strength and power output.', 'products/creatine_mono.png', 'ACTIVE', '2026-09-16 12:13:16', '2026-09-16 12:13:16'),
(4, 2, 'BCAA Zero', 'Xtend', 'Zero sugar BCAA formula to support muscle recovery and hydration.', 'products/bcaa_zero.png', 'ACTIVE', '2026-09-16 12:13:16', '2026-09-16 12:13:16'),
(5, 3, 'Premium Almonds', 'FitFood', 'Roasted natural almonds - a wholesome source of healthy fats and protein.', 'products/almonds.png', 'ACTIVE', '2026-09-16 12:13:16', '2026-09-16 12:13:16'),
(6, 3, 'Peanut Butter Crunchy', 'FitFood', 'High protein crunchy peanut butter with no added sugar.', 'products/peanut_butter.png', 'ACTIVE', '2026-09-16 12:13:16', '2026-09-16 12:13:16'),
(7, 4, 'Protein Bar Choco', 'MuscleBite', 'Delicious chocolate protein bar - the perfect on-the-go snack.', 'products/protein_bar.png', 'ACTIVE', '2026-09-16 12:13:16', '2026-09-16 12:13:16'),
(8, 6, 'Daily Multivitamin', 'VitaCore', 'Complete daily multivitamin and mineral formula for active lifestyles.', 'products/multivitamin.png', 'ACTIVE', '2026-09-16 12:13:16', '2026-09-16 12:13:16'),
(9, 7, 'Pre Workout Blast', 'PowerX', 'Explosive energy and focus formula for high intensity training sessions.', 'products/preworkout.png', 'ACTIVE', '2026-09-16 12:13:16', '2026-09-16 12:13:16'),
(10, 8, 'Shaker Bottle', 'GymGear', 'Leak-proof shaker bottle with mixing ball, 700ml capacity.', 'products/shaker.png', 'ACTIVE', '2026-09-16 12:13:16', '2026-09-16 12:13:16');

-- --------------------------------------------------------

--
-- Table structure for table `product_images`
--

CREATE TABLE `product_images` (
  `id` int(10) UNSIGNED NOT NULL,
  `product_id` int(10) UNSIGNED NOT NULL,
  `image` varchar(255) NOT NULL,
  `sort_order` int(11) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `product_images`
--

INSERT INTO `product_images` (`id`, `product_id`, `image`, `sort_order`, `created_at`) VALUES
(1, 1, 'products/hyper_mass-2.png', 1, '2026-09-16 12:13:16'),
(2, 1, 'products/hyper_mass-3.png', 2, '2026-09-16 12:13:16'),
(3, 2, 'products/whey_gold-2.png', 1, '2026-09-16 12:13:16'),
(4, 2, 'products/whey_gold-3.png', 2, '2026-09-16 12:13:16');

-- --------------------------------------------------------

--
-- Table structure for table `product_variants`
--

CREATE TABLE `product_variants` (
  `id` int(10) UNSIGNED NOT NULL,
  `product_id` int(10) UNSIGNED NOT NULL,
  `variant_name` varchar(100) NOT NULL,
  `unit` varchar(50) DEFAULT NULL,
  `mrp` decimal(10,2) DEFAULT NULL,
  `price` decimal(10,2) NOT NULL DEFAULT 0.00,
  `onsite_price` decimal(10,2) DEFAULT NULL,
  `stock` int(11) NOT NULL DEFAULT 0,
  `sku` varchar(100) DEFAULT NULL,
  `status` enum('ACTIVE','INACTIVE') NOT NULL DEFAULT 'ACTIVE',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `product_variants`
--

INSERT INTO `product_variants` (`id`, `product_id`, `variant_name`, `unit`, `mrp`, `price`, `onsite_price`, `stock`, `sku`, `status`, `created_at`, `updated_at`) VALUES
(1, 1, '1 KG', 'kg', 1380.00, 1200.00, NULL, 37, 'HYPER_MASS-1KG', 'ACTIVE', '2026-09-16 12:13:16', '2026-09-18 05:49:09'),
(2, 1, '3 KG', 'kg', 3450.00, 3000.00, NULL, 25, 'HYPER_MASS-3KG', 'ACTIVE', '2026-09-16 12:13:16', '2026-09-16 12:13:16'),
(3, 1, '5 KG', 'kg', 5520.00, 4800.00, NULL, 15, 'HYPER_MASS-5KG', 'ACTIVE', '2026-09-16 12:13:16', '2026-09-16 12:13:16'),
(4, 2, '1 KG', 'kg', 2070.00, 1800.00, NULL, 50, 'WHEY_GOLD-1KG', 'ACTIVE', '2026-09-16 12:13:16', '2026-09-16 12:13:16'),
(5, 2, '2 KG', 'kg', 3680.00, 3200.00, NULL, 30, 'WHEY_GOLD-2KG', 'ACTIVE', '2026-09-16 12:13:16', '2026-09-16 12:13:16'),
(6, 2, '5 KG', 'kg', 8050.00, 7000.00, NULL, 12, 'WHEY_GOLD-5KG', 'ACTIVE', '2026-09-16 12:13:16', '2026-09-16 12:13:16'),
(7, 3, '250 GM', 'gm', 1035.00, 900.00, 1000.00, 59, 'CREATINE_MONO-250GM', 'ACTIVE', '2026-09-16 12:13:16', '2026-09-21 06:52:41'),
(8, 3, '500 GM', 'gm', 1840.00, 1600.00, 1700.00, 30, 'CREATINE_MONO-500GM', 'ACTIVE', '2026-09-16 12:13:16', '2026-09-21 05:09:35'),
(9, 4, '500 GM', 'gm', 1610.00, 1400.00, NULL, 30, 'BCAA_ZERO-500GM', 'ACTIVE', '2026-09-16 12:13:16', '2026-09-16 12:13:16'),
(10, 4, '1 KG', 'kg', 2875.00, 2500.00, NULL, 18, 'BCAA_ZERO-1KG', 'ACTIVE', '2026-09-16 12:13:16', '2026-09-16 12:13:16'),
(11, 5, '250 GM', 'gm', 288.00, 250.00, NULL, 100, 'ALMONDS-250GM', 'ACTIVE', '2026-09-16 12:13:16', '2026-09-16 12:13:16'),
(12, 5, '500 GM', 'gm', 518.00, 450.00, NULL, 69, 'ALMONDS-500GM', 'ACTIVE', '2026-09-16 12:13:16', '2026-09-18 12:08:23'),
(13, 5, '1 KG', 'kg', 920.00, 800.00, NULL, 40, 'ALMONDS-1KG', 'ACTIVE', '2026-09-16 12:13:16', '2026-09-16 12:13:16'),
(14, 6, '500 GM', 'gm', 402.00, 350.00, NULL, 60, 'PEANUT_BUTTER-500GM', 'ACTIVE', '2026-09-16 12:13:16', '2026-09-16 12:13:16'),
(15, 6, '1 KG', 'kg', 690.00, 600.00, NULL, 35, 'PEANUT_BUTTER-1KG', 'ACTIVE', '2026-09-16 12:13:16', '2026-09-16 12:13:16'),
(16, 7, '1 Piece', 'piece', 138.00, 120.00, NULL, 200, 'PROTEIN_BAR-1Piece', 'ACTIVE', '2026-09-16 12:13:16', '2026-09-16 12:13:16'),
(17, 7, '6 Pieces', 'pack', 747.00, 650.00, NULL, 77, 'PROTEIN_BAR-6Pieces', 'ACTIVE', '2026-09-16 12:13:16', '2026-09-18 06:01:51'),
(18, 7, '12 Pieces', 'pack', 1380.00, 1200.00, NULL, 39, 'PROTEIN_BAR-12Pieces', 'ACTIVE', '2026-09-16 12:13:16', '2026-09-16 12:32:29'),
(19, 8, '60 Tablets', 'tablets', 518.00, 450.00, NULL, 90, 'MULTIVITAMIN-60Tablets', 'ACTIVE', '2026-09-16 12:13:16', '2026-09-16 12:13:16'),
(20, 8, '120 Tablets', 'tablets', 920.00, 800.00, NULL, 55, 'MULTIVITAMIN-120Tablets', 'ACTIVE', '2026-09-16 12:13:16', '2026-09-16 12:13:16'),
(21, 9, '300 GM', 'gm', 1725.00, 1500.00, NULL, 45, 'PREWORKOUT-300GM', 'ACTIVE', '2026-09-16 12:13:16', '2026-09-16 12:13:16'),
(22, 9, '600 GM', 'gm', 3105.00, 2700.00, NULL, 19, 'PREWORKOUT-600GM', 'ACTIVE', '2026-09-16 12:13:16', '2026-09-16 12:32:29'),
(23, 10, '500 ML', 'ml', 173.00, 150.00, NULL, 148, 'SHAKER-500ML', 'ACTIVE', '2026-09-16 12:13:16', '2026-09-18 06:07:00'),
(24, 10, '700 ML', 'ml', 230.00, 200.00, NULL, 116, 'SHAKER-700ML', 'ACTIVE', '2026-09-16 12:13:16', '2026-09-21 05:09:35');

-- --------------------------------------------------------

--
-- Table structure for table `shop_settings`
--

CREATE TABLE `shop_settings` (
  `id` int(10) UNSIGNED NOT NULL DEFAULT 1,
  `shop_name` varchar(150) DEFAULT NULL,
  `logo` varchar(255) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `gstin` varchar(20) DEFAULT NULL,
  `gst_percent` tinyint(3) UNSIGNED DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `shop_settings`
--

INSERT INTO `shop_settings` (`id`, `shop_name`, `logo`, `phone`, `address`, `gstin`, `gst_percent`, `updated_at`) VALUES
(1, 'AURORA SUPPLIMENTS', 'shop/1789733265927-650831003.png', '8618496388', 'Manglore, Bantwal, DK, Karnataka, 574219', 'VFFDFDR15F555', 18, '2026-09-21 07:35:31');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(10) UNSIGNED NOT NULL,
  `gym_id` int(10) UNSIGNED NOT NULL,
  `name` varchar(100) NOT NULL,
  `username` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `email` varchar(150) DEFAULT NULL,
  `status` enum('ACTIVE','INACTIVE') NOT NULL DEFAULT 'ACTIVE',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `gym_id`, `name`, `username`, `password`, `phone`, `email`, `status`, `created_at`, `updated_at`) VALUES
(1, 1, 'Rahul Sharma', 'rahul', '$2a$10$BOTjojLT4l2mtqagQtAci.6f0wT8Ii48MXdzss6OTo0pRGvPVxUSy', '9000000001', 'rahul@example.com', 'ACTIVE', '2026-09-16 12:13:16', '2026-09-16 12:13:16'),
(2, 1, 'Aman Verma', 'aman', '$2a$10$BOTjojLT4l2mtqagQtAci.6f0wT8Ii48MXdzss6OTo0pRGvPVxUSy', '9000000002', 'aman@example.com', 'ACTIVE', '2026-09-16 12:13:16', '2026-09-16 12:13:16'),
(3, 2, 'Priya Singh', 'priya', '$2a$10$BOTjojLT4l2mtqagQtAci.6f0wT8Ii48MXdzss6OTo0pRGvPVxUSy', '9000000003', 'priya@example.com', 'ACTIVE', '2026-09-16 12:13:16', '2026-09-16 12:13:16'),
(4, 2, 'Karan Mehta', 'karan', '$2a$10$BOTjojLT4l2mtqagQtAci.6f0wT8Ii48MXdzss6OTo0pRGvPVxUSy', '9000000004', 'karan@example.com', 'ACTIVE', '2026-09-16 12:13:16', '2026-09-16 12:13:16'),
(5, 3, 'Sneha Patil', 'sneha', '$2a$10$BOTjojLT4l2mtqagQtAci.6f0wT8Ii48MXdzss6OTo0pRGvPVxUSy', '9000000005', 'sneha@example.com', 'ACTIVE', '2026-09-16 12:13:16', '2026-09-16 12:13:16');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `admins`
--
ALTER TABLE `admins`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `carts`
--
ALTER TABLE `carts`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uniq_user_cart` (`user_id`);

--
-- Indexes for table `cart_items`
--
ALTER TABLE `cart_items`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uniq_cart_variant` (`cart_id`,`variant_id`),
  ADD KEY `fk_cartitems_variant` (`variant_id`),
  ADD KEY `idx_cartitems_cart` (`cart_id`);

--
-- Indexes for table `categories`
--
ALTER TABLE `categories`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_category_status` (`status`);

--
-- Indexes for table `complaints`
--
ALTER TABLE `complaints`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_complaints_order` (`order_id`),
  ADD KEY `idx_complaints_gym` (`gym_id`),
  ADD KEY `idx_complaints_user` (`user_id`),
  ADD KEY `idx_complaints_status` (`status`);

--
-- Indexes for table `gyms`
--
ALTER TABLE `gyms`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `gym_code` (`gym_code`),
  ADD UNIQUE KEY `username` (`username`),
  ADD KEY `idx_gym_code` (`gym_code`),
  ADD KEY `idx_gym_status` (`status`);

--
-- Indexes for table `gym_products`
--
ALTER TABLE `gym_products`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uniq_gym_product` (`gym_id`,`product_id`),
  ADD KEY `idx_gymproducts_gym` (`gym_id`),
  ADD KEY `idx_gymproducts_product` (`product_id`);

--
-- Indexes for table `gym_product_variants`
--
ALTER TABLE `gym_product_variants`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uniq_gym_variant` (`gym_id`,`variant_id`),
  ADD KEY `idx_gymvariants_gym` (`gym_id`),
  ADD KEY `idx_gymvariants_variant` (`variant_id`);

--
-- Indexes for table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_notifications_gym` (`gym_id`),
  ADD KEY `fk_notifications_order` (`order_id`),
  ADD KEY `idx_notifications_user` (`user_id`),
  ADD KEY `idx_notifications_audience` (`audience`),
  ADD KEY `idx_notifications_read` (`is_read`);

--
-- Indexes for table `orders`
--
ALTER TABLE `orders`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `order_number` (`order_number`),
  ADD KEY `idx_orders_gym` (`gym_id`),
  ADD KEY `idx_orders_user` (`user_id`),
  ADD KEY `idx_orders_status` (`order_status`),
  ADD KEY `idx_orders_payment_status` (`payment_status`),
  ADD KEY `idx_orders_created` (`created_at`),
  ADD KEY `idx_orders_source` (`source`);

--
-- Indexes for table `order_items`
--
ALTER TABLE `order_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_orderitems_product` (`product_id`),
  ADD KEY `fk_orderitems_variant` (`variant_id`),
  ADD KEY `idx_orderitems_order` (`order_id`);

--
-- Indexes for table `payments`
--
ALTER TABLE `payments`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uniq_order_payment` (`order_id`),
  ADD KEY `fk_payments_admin` (`verified_by`),
  ADD KEY `idx_payments_status` (`payment_status`);

--
-- Indexes for table `payment_settings`
--
ALTER TABLE `payment_settings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uniq_gym_payment` (`gym_id`);

--
-- Indexes for table `products`
--
ALTER TABLE `products`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_products_category` (`category_id`),
  ADD KEY `idx_products_status` (`status`);

--
-- Indexes for table `product_images`
--
ALTER TABLE `product_images`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_productimages_product` (`product_id`);

--
-- Indexes for table `product_variants`
--
ALTER TABLE `product_variants`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_variants_product` (`product_id`),
  ADD KEY `idx_variants_status` (`status`);

--
-- Indexes for table `shop_settings`
--
ALTER TABLE `shop_settings`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uniq_gym_username` (`gym_id`,`username`),
  ADD KEY `idx_users_gym` (`gym_id`),
  ADD KEY `idx_users_status` (`status`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `admins`
--
ALTER TABLE `admins`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `carts`
--
ALTER TABLE `carts`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `cart_items`
--
ALTER TABLE `cart_items`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `categories`
--
ALTER TABLE `categories`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `complaints`
--
ALTER TABLE `complaints`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `gyms`
--
ALTER TABLE `gyms`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `gym_products`
--
ALTER TABLE `gym_products`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=20;

--
-- AUTO_INCREMENT for table `gym_product_variants`
--
ALTER TABLE `gym_product_variants`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=50;

--
-- AUTO_INCREMENT for table `orders`
--
ALTER TABLE `orders`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=31;

--
-- AUTO_INCREMENT for table `order_items`
--
ALTER TABLE `order_items`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=44;

--
-- AUTO_INCREMENT for table `payments`
--
ALTER TABLE `payments`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=31;

--
-- AUTO_INCREMENT for table `payment_settings`
--
ALTER TABLE `payment_settings`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `products`
--
ALTER TABLE `products`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `product_images`
--
ALTER TABLE `product_images`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `product_variants`
--
ALTER TABLE `product_variants`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=25;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `carts`
--
ALTER TABLE `carts`
  ADD CONSTRAINT `fk_carts_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `cart_items`
--
ALTER TABLE `cart_items`
  ADD CONSTRAINT `fk_cartitems_cart` FOREIGN KEY (`cart_id`) REFERENCES `carts` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_cartitems_variant` FOREIGN KEY (`variant_id`) REFERENCES `product_variants` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `complaints`
--
ALTER TABLE `complaints`
  ADD CONSTRAINT `fk_complaints_gym` FOREIGN KEY (`gym_id`) REFERENCES `gyms` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_complaints_order` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_complaints_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `gym_products`
--
ALTER TABLE `gym_products`
  ADD CONSTRAINT `fk_gymproducts_gym` FOREIGN KEY (`gym_id`) REFERENCES `gyms` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_gymproducts_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `gym_product_variants`
--
ALTER TABLE `gym_product_variants`
  ADD CONSTRAINT `fk_gymvariants_gym` FOREIGN KEY (`gym_id`) REFERENCES `gyms` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_gymvariants_variant` FOREIGN KEY (`variant_id`) REFERENCES `product_variants` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `notifications`
--
ALTER TABLE `notifications`
  ADD CONSTRAINT `fk_notifications_gym` FOREIGN KEY (`gym_id`) REFERENCES `gyms` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_notifications_order` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_notifications_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `orders`
--
ALTER TABLE `orders`
  ADD CONSTRAINT `fk_orders_gym` FOREIGN KEY (`gym_id`) REFERENCES `gyms` (`id`),
  ADD CONSTRAINT `fk_orders_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `order_items`
--
ALTER TABLE `order_items`
  ADD CONSTRAINT `fk_orderitems_order` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_orderitems_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`),
  ADD CONSTRAINT `fk_orderitems_variant` FOREIGN KEY (`variant_id`) REFERENCES `product_variants` (`id`);

--
-- Constraints for table `payments`
--
ALTER TABLE `payments`
  ADD CONSTRAINT `fk_payments_admin` FOREIGN KEY (`verified_by`) REFERENCES `admins` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_payments_order` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `payment_settings`
--
ALTER TABLE `payment_settings`
  ADD CONSTRAINT `fk_paymentsettings_gym` FOREIGN KEY (`gym_id`) REFERENCES `gyms` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `products`
--
ALTER TABLE `products`
  ADD CONSTRAINT `fk_products_category` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`);

--
-- Constraints for table `product_images`
--
ALTER TABLE `product_images`
  ADD CONSTRAINT `fk_productimages_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `product_variants`
--
ALTER TABLE `product_variants`
  ADD CONSTRAINT `fk_variants_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `users`
--
ALTER TABLE `users`
  ADD CONSTRAINT `fk_users_gym` FOREIGN KEY (`gym_id`) REFERENCES `gyms` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
