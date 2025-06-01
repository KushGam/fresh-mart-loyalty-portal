-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: May 16, 2025 at 12:21 PM
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
-- Database: `login_db`
--

-- --------------------------------------------------------

--
-- Table structure for table `categories`
--

CREATE TABLE `categories` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `item_count` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `categories`
--

INSERT INTO `categories` (`id`, `name`, `item_count`, `created_at`) VALUES
(1, 'Vegetables', 5, '2025-03-21 10:12:10'),
(2, 'Peach', 5, '2025-03-21 10:12:10'),
(3, 'Strawberry', 5, '2025-03-21 10:12:10'),
(4, 'Apple', 5, '2025-03-21 10:12:10'),
(5, 'Orange', 5, '2025-03-21 10:12:10'),
(6, 'Potato', 5, '2025-03-21 10:12:10');

-- --------------------------------------------------------

--
-- Table structure for table `customer_details`
--

CREATE TABLE `customer_details` (
  `id` int(11) NOT NULL,
  `user_id` bigint(20) NOT NULL,
  `first_name` varchar(50) NOT NULL,
  `last_name` varchar(50) NOT NULL,
  `phone_number` varchar(20) NOT NULL,
  `address` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `loyalty_tier` int(11) DEFAULT 1,
  `monthly_spending` decimal(10,2) DEFAULT 0.00,
  `birthday` date NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `customer_details`
--

INSERT INTO `customer_details` (`id`, `user_id`, `first_name`, `last_name`, `phone_number`, `address`, `created_at`, `loyalty_tier`, `monthly_spending`, `birthday`) VALUES
(153, 45, 'Abinash', 'Gupta', '0410123123', 'Street', '2025-05-07 02:04:59', 2, 9024.00, '2001-05-16');

-- --------------------------------------------------------

--
-- Table structure for table `daily_best_sells`
--

CREATE TABLE `daily_best_sells` (
  `id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `type` enum('featured','popular','new') NOT NULL,
  `discount_percent` int(11) DEFAULT 10
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `loyalty_settings`
--

CREATE TABLE `loyalty_settings` (
  `id` int(11) NOT NULL,
  `points_per_dollar` decimal(10,2) NOT NULL DEFAULT 1.00,
  `min_points_redeem` int(11) NOT NULL DEFAULT 100,
  `points_to_amount` int(11) NOT NULL DEFAULT 100,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `loyalty_settings`
--

INSERT INTO `loyalty_settings` (`id`, `points_per_dollar`, `min_points_redeem`, `points_to_amount`, `created_at`, `updated_at`) VALUES
(1, 1.00, 1000, 100, '2025-03-23 10:09:40', '2025-04-09 02:19:13');

-- --------------------------------------------------------

--
-- Table structure for table `loyalty_tiers`
--

CREATE TABLE `loyalty_tiers` (
  `id` int(11) NOT NULL,
  `tier_name` varchar(50) NOT NULL,
  `points_multiplier` decimal(4,2) DEFAULT 1.00,
  `special_benefits` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `spending_required` decimal(10,2) NOT NULL DEFAULT 0.00
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `loyalty_tiers`
--

INSERT INTO `loyalty_tiers` (`id`, `tier_name`, `points_multiplier`, `special_benefits`, `created_at`, `updated_at`, `spending_required`) VALUES
(1, 'Bronze', 1.00, 'Basic membership benefits', '2025-03-24 09:35:12', '2025-03-27 11:35:34', 0.00),
(2, 'Silver', 1.20, 'Free delivery on orders \\\\\\\\\\\\\\\\r\\\\\\\\\\\\\\\\nExclusive monthly offers', '2025-03-24 09:35:12', '2025-04-11 01:51:26', 5000.00),
(3, 'Gold', 1.50, 'Free delivery on all orders\\r\\nDouble points on weekends\\r\\nPriority customer service', '2025-03-24 09:35:12', '2025-04-11 01:34:34', 10000.00),
(4, 'Platinum', 2.00, 'All Gold benefits\\r\\nEarly access to sales\\r\\nPersonalized shopping assistant', '2025-03-24 09:35:12', '2025-04-11 01:34:34', 20000.00);

-- --------------------------------------------------------

--
-- Table structure for table `offer_redemptions`
--

CREATE TABLE `offer_redemptions` (
  `id` int(11) NOT NULL,
  `user_id` bigint(20) NOT NULL,
  `offer_id` int(11) NOT NULL,
  `redemption_date` datetime NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `offer_usage`
--

CREATE TABLE `offer_usage` (
  `id` int(11) NOT NULL,
  `user_id` bigint(20) NOT NULL,
  `offer_id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `used_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `orders`
--

CREATE TABLE `orders` (
  `id` int(11) NOT NULL,
  `user_id` bigint(20) NOT NULL,
  `order_number` varchar(50) NOT NULL,
  `total_amount` decimal(10,2) NOT NULL,
  `shipping_fee` decimal(10,2) NOT NULL,
  `delivery_time` datetime NOT NULL,
  `status` varchar(20) NOT NULL DEFAULT 'pending',
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `rewards_discount` decimal(10,2) DEFAULT 0.00,
  `tier_name` varchar(50) DEFAULT 'Bronze',
  `tier_multiplier` decimal(3,2) DEFAULT 1.00,
  `offer_id` int(11) DEFAULT NULL,
  `offer_discount` decimal(10,2) DEFAULT 0.00,
  `offer_points_multiplier` decimal(3,2) DEFAULT 1.00,
  `birthday_discount` decimal(10,2) DEFAULT 0.00,
  `promo_discount` decimal(10,2) DEFAULT 0.00
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `orders`
--

INSERT INTO `orders` (`id`, `user_id`, `order_number`, `total_amount`, `shipping_fee`, `delivery_time`, `status`, `created_at`, `updated_at`, `rewards_discount`, `tier_name`, `tier_multiplier`, `offer_id`, `offer_discount`, `offer_points_multiplier`, `birthday_discount`, `promo_discount`) VALUES
(191, 45, 'FM20250507042134129', 5100.00, 0.00, '2025-05-09 04:21:34', 'pending', '2025-05-07 12:21:34', '2025-05-15 20:38:16', 0.00, '0', 1.00, NULL, 0.00, 1.00, 0.00, 0.00),
(192, 45, 'FM20250507042513543', 3924.00, 0.00, '2025-05-09 04:25:13', 'pending', '2025-05-07 12:25:13', '2025-05-07 12:25:13', 76.00, '0', 1.20, NULL, 0.00, 1.00, 0.00, 0.00);

-- --------------------------------------------------------

--
-- Table structure for table `order_items`
--

CREATE TABLE `order_items` (
  `id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL,
  `price` decimal(10,2) NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `order_items`
--

INSERT INTO `order_items` (`id`, `order_id`, `product_id`, `quantity`, `price`, `created_at`) VALUES
(223, 191, 1, 3, 2000.00, '2025-05-07 12:21:34'),
(224, 192, 1, 2, 2000.00, '2025-05-07 12:25:13');

-- --------------------------------------------------------

--
-- Table structure for table `order_promotions`
--

CREATE TABLE `order_promotions` (
  `id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `promotion_id` int(11) NOT NULL,
  `user_id` bigint(20) NOT NULL,
  `discount_amount` decimal(10,2) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `order_promotions`
--

INSERT INTO `order_promotions` (`id`, `order_id`, `promotion_id`, `user_id`, `discount_amount`, `created_at`) VALUES
(0, 191, 16, 45, 600.00, '2025-05-07 02:21:39');

-- --------------------------------------------------------

--
-- Table structure for table `personalized_offers`
--

CREATE TABLE `personalized_offers` (
  `id` int(11) NOT NULL,
  `title` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `offer_type` enum('birthday','loyalty_tier','special','seasonal') NOT NULL,
  `points_multiplier` decimal(4,2) DEFAULT 1.00,
  `minimum_purchase` decimal(10,2) DEFAULT 0.00,
  `discount_amount` decimal(10,2) NOT NULL,
  `discount_type` enum('percentage','fixed') NOT NULL,
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `loyalty_tier` varchar(50) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `usage_limit` int(11) DEFAULT NULL COMMENT 'Number of times a customer can use this offer (NULL for unlimited)',
  `usage_count` int(11) DEFAULT 0,
  `birthday_dates` varchar(255) DEFAULT NULL COMMENT 'Comma-separated list of MM-DD dates for birthday offers'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `personalized_offers`
--

INSERT INTO `personalized_offers` (`id`, `title`, `description`, `offer_type`, `points_multiplier`, `minimum_purchase`, `discount_amount`, `discount_type`, `start_date`, `end_date`, `loyalty_tier`, `is_active`, `created_at`, `updated_at`, `usage_limit`, `usage_count`, `birthday_dates`) VALUES
(38, 'Happy birthday', 'Enjoy your day', 'birthday', 1.00, 0.00, 5.00, 'percentage', '2025-05-15', '2026-05-15', '', 1, '2025-05-15 10:36:20', '2025-05-15 10:36:27', 1, 0, NULL),
(39, 'Welcome', '!!!', 'special', 1.00, 0.00, 50.00, 'fixed', '2025-05-15', '2025-05-16', '', 1, '2025-05-15 10:46:48', '2025-05-15 10:46:56', 1, 1, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `products`
--

CREATE TABLE `products` (
  `id` int(11) NOT NULL,
  `product_name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `img` varchar(255) DEFAULT NULL,
  `price` decimal(10,2) NOT NULL,
  `stock` int(11) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `category` varchar(50) NOT NULL DEFAULT 'other',
  `featured` tinyint(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `products`
--

INSERT INTO `products` (`id`, `product_name`, `description`, `img`, `price`, `stock`, `created_at`, `updated_at`, `category`, `featured`) VALUES
(1, 'Radish 500g', 'Fresh and crispy radishes', 'Images Assets/68085982f0ba7.png', 2000.00, 100, '2025-04-23 02:55:26', '2025-05-16 09:25:29', 'vegetables', 1),
(2, 'Potato 1kg', 'Fresh farm potatoes', 'Images Assets/Potato.png', 120.00, 92, '2025-04-23 02:55:26', '2025-05-16 07:12:56', 'vegetables', 1),
(3, 'Tomato 200g', 'Ripe and juicy tomatoes', 'Images Assets/Tomatos 200g.png', 0.30, 95, '2025-04-23 02:55:26', '2025-05-16 07:12:55', 'vegetables', 1),
(4, 'Fresh Peaches', 'Sweet and juicy peaches', 'Images Assets/peach.png', 3.50, 95, '2025-04-23 02:55:26', '2025-05-16 09:49:17', 'fruits', 1),
(5, 'Fresh Strawberries', 'Sweet red strawberries', 'Images Assets/Strawberry.png', 4.00, 95, '2025-04-23 02:55:26', '2025-05-16 07:12:50', 'fruits', 1),
(6, 'Fresh Apples', 'Crisp and sweet apples', 'Images Assets/Apples.png', 3.00, 100, '2025-04-23 02:55:26', '2025-05-16 04:45:59', 'fruits', 0),
(7, 'Fresh Oranges', 'Juicy citrus oranges', 'Images Assets/Orange.png', 2.50, 100, '2025-04-23 02:55:26', '2025-05-16 04:45:59', 'fruits', 0),
(8, 'Onion 1kg', 'Fresh brown onions', 'Images Assets/Onion 1 KG.png', 2.50, 95, '2025-04-23 02:55:26', '2025-05-16 09:46:57', 'vegetables', 0),
(9, 'Fresh Spinach', 'Organic green spinach', 'Images Assets/Spinach.png', 2.50, 100, '2025-04-23 02:55:26', '2025-05-16 09:46:57', 'vegetables', 0),
(10, 'Chicken Breast', 'Fresh chicken breast', 'Images Assets/Chicken Breast.png', 5.00, 100, '2025-05-16 04:51:38', '2025-05-16 09:49:20', 'meat', 1),
(11, 'Halal Sausage', 'Halal certified sausage', 'Images Assets/Halal Sausage.png', 4.50, 100, '2025-05-16 04:51:38', '2025-05-16 07:12:52', 'meat', 1),
(12, 'Minced Meat', 'Fresh minced meat', 'Images Assets/Minced Meat.png', 6.00, 100, '2025-05-16 04:51:38', '2025-05-16 07:12:53', 'meat', 1),
(13, 'Drumsticks', 'Chicken drumsticks', 'Images Assets/Chicken Drumsticks.png', 4.00, 100, '2025-05-16 04:51:38', '2025-05-16 10:04:00', 'meat', 1),
(14, 'Goat Curry Cut', 'Goat meat for curry', 'Images Assets/Goat Meat.png', 8.00, 100, '2025-05-16 04:51:38', '2025-05-16 07:09:46', 'meat', 0),
(15, 'Milk', 'Fresh milk', 'Images Assets/milk.png', 2.00, 100, '2025-05-16 04:51:38', '2025-05-16 07:12:45', 'dairy', 1),
(16, 'Cheese', 'Cheddar cheese', 'Images Assets/cheese.png', 3.00, 100, '2025-05-16 04:51:38', '2025-05-16 07:12:46', 'dairy', 1),
(17, 'Yogurt', 'Plain yogurt', 'Images Assets/yogurt.png', 1.50, 100, '2025-05-16 04:51:38', '2025-05-16 09:49:15', 'dairy', 1),
(18, 'Butter', 'Creamy butter', 'Images Assets/butter.png', 2.50, 100, '2025-05-16 04:51:38', '2025-05-16 04:51:38', 'dairy', 0),
(19, 'Cream', 'Rich cream', 'Images Assets/cream.png', 2.20, 100, '2025-05-16 04:51:38', '2025-05-16 04:51:38', 'dairy', 0),
(20, 'White Bread', 'Soft white bread', 'Images Assets/bread.png', 1.80, 100, '2025-05-16 04:51:38', '2025-05-16 07:09:50', 'bakery', 1),
(21, 'Croissant', 'Buttery croissant', 'Images Assets/croissant.png', 2.20, 100, '2025-05-16 04:51:38', '2025-05-16 10:16:45', 'bakery', 0),
(22, 'Baguette', 'French baguette', 'Images Assets/baguette.png', 2.00, 100, '2025-05-16 04:51:38', '2025-05-16 10:16:44', 'bakery', 0),
(23, 'Muffin', 'Chocolate muffin', 'Images Assets/muffin.png', 1.80, 100, '2025-05-16 04:51:38', '2025-05-16 10:16:46', 'bakery', 0),
(24, 'Donut', 'Glazed donut', 'Images Assets/donut.png', 1.50, 100, '2025-05-16 04:51:38', '2025-05-16 10:05:44', 'bakery', 0),
(25, 'Herbal Tea', 'Refreshing herbal tea', 'Images Assets/Herbal Tea.png', 3.00, 100, '2025-05-16 04:51:38', '2025-05-16 07:09:56', 'beverages', 1),
(26, 'Green Tea', 'Green tea box', 'Images Assets/Green Tea Box.png', 3.20, 100, '2025-05-16 04:51:38', '2025-05-16 07:12:43', 'beverages', 1),
(27, 'Black Tea', 'Black tea', 'Images Assets/Black Tea.png', 2.80, 100, '2025-05-16 04:51:38', '2025-05-16 09:49:13', 'beverages', 1),
(28, 'Coffee', 'Premium coffee', 'Images Assets/Coffee 1 kg.png', 8.00, 100, '2025-05-16 04:51:38', '2025-05-16 10:06:26', 'beverages', 0),
(29, 'Juice', 'Mixed fruit juice', 'Images Assets/juice.png', 2.50, 100, '2025-05-16 04:51:38', '2025-05-16 04:51:38', 'beverages', 0),
(31, 'Banana 1KG', 'Fresh Banana', 'Images Assets/images.jpg', 4.50, 100, '2025-05-16 05:24:34', '2025-05-16 07:12:49', 'fruits', 1);

-- --------------------------------------------------------

--
-- Table structure for table `promotions`
--

CREATE TABLE `promotions` (
  `id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `banner_image` varchar(255) DEFAULT NULL,
  `discount_amount` decimal(10,2) DEFAULT NULL,
  `discount_type` enum('percentage','fixed') DEFAULT 'percentage',
  `minimum_purchase` decimal(10,2) DEFAULT 0.00,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `usage_limit` int(11) DEFAULT NULL COMMENT 'Number of times each customer can use this promotion (NULL or 0 for unlimited)',
  `usage_count` int(11) DEFAULT 0 COMMENT 'Total number of times this promotion has been used across all customers',
  `is_redeemable` tinyint(1) DEFAULT 1 COMMENT 'Whether the promotion can be redeemed by customers',
  `total_usage_count` int(11) DEFAULT 0 COMMENT 'Total number of times this promotion has been used across all customers'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `promotions`
--

INSERT INTO `promotions` (`id`, `title`, `description`, `start_date`, `end_date`, `banner_image`, `discount_amount`, `discount_type`, `minimum_purchase`, `is_active`, `created_at`, `usage_limit`, `usage_count`, `is_redeemable`, `total_usage_count`) VALUES
(16, 'Buy more save more', 'The more you buy, The more you save', '2025-05-07', '2025-05-09', 'Images Assets/681ab3c61f3cf.png', 10.00, 'percentage', 200.00, 1, '2025-05-07 01:13:42', 1, 0, 1, 1),
(17, 'Weekend flash sale', 'Bigger buys, bigger saving', '2025-05-08', '2025-05-09', 'Images Assets/681ab43966720.jpeg', 100.00, 'fixed', 500.00, 1, '2025-05-07 01:15:37', 1, 0, 1, 0);

-- --------------------------------------------------------

--
-- Table structure for table `promotion_usage`
--

CREATE TABLE `promotion_usage` (
  `id` int(11) NOT NULL,
  `user_id` bigint(20) NOT NULL,
  `promotion_id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `used_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `rewards`
--

CREATE TABLE `rewards` (
  `id` int(11) NOT NULL,
  `user_id` bigint(20) NOT NULL,
  `points` int(11) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `rewards`
--

INSERT INTO `rewards` (`id`, `user_id`, `points`, `created_at`, `updated_at`) VALUES
(1, 11, 0, '2025-03-21 11:51:53', '2025-03-21 11:51:53'),
(20, 45, 4758, '2025-05-07 02:04:06', '2025-05-07 02:25:24');

-- --------------------------------------------------------

--
-- Table structure for table `reward_redemptions`
--

CREATE TABLE `reward_redemptions` (
  `id` int(11) NOT NULL,
  `user_id` bigint(20) NOT NULL,
  `order_id` int(11) NOT NULL,
  `points_redeemed` int(11) NOT NULL,
  `amount_saved` decimal(10,2) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `reward_redemptions`
--

INSERT INTO `reward_redemptions` (`id`, `user_id`, `order_id`, `points_redeemed`, `amount_saved`, `created_at`) VALUES
(38, 45, 192, 7600, 76.00, '2025-05-07 02:25:19');

-- --------------------------------------------------------

--
-- Table structure for table `saved_cards`
--

CREATE TABLE `saved_cards` (
  `id` bigint(20) NOT NULL,
  `user_id` bigint(20) NOT NULL,
  `card_number` varchar(255) NOT NULL,
  `expiry_date` varchar(255) NOT NULL,
  `card_holder_name` varchar(255) NOT NULL,
  `last_four_digits` varchar(4) NOT NULL,
  `card_type` varchar(50) DEFAULT NULL,
  `is_default` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `saved_cards`
--

INSERT INTO `saved_cards` (`id`, `user_id`, `card_number`, `expiry_date`, `card_holder_name`, `last_four_digits`, `card_type`, `is_default`, `created_at`, `updated_at`) VALUES
(8, 45, '1234567890123456', '01/26', '', '3456', NULL, 1, '2025-05-07 02:21:34', '2025-05-07 02:21:34');

-- --------------------------------------------------------

--
-- Table structure for table `store`
--

CREATE TABLE `store` (
  `id` int(11) NOT NULL,
  `store_name` varchar(100) NOT NULL,
  `points_required` int(11) NOT NULL DEFAULT 0,
  `reward_description` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `store_redemptions`
--

CREATE TABLE `store_redemptions` (
  `id` int(11) NOT NULL,
  `user_id` bigint(20) NOT NULL,
  `redemption_code` varchar(16) NOT NULL,
  `points_redeemed` int(11) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `status` enum('pending','redeemed','expired') NOT NULL DEFAULT 'pending',
  `admin_id` bigint(20) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `redeemed_at` timestamp NULL DEFAULT NULL,
  `expires_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` bigint(20) NOT NULL,
  `user_id` bigint(20) NOT NULL,
  `user_name` varchar(255) NOT NULL,
  `last_name` varchar(64) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `date` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `email` varchar(255) NOT NULL,
  `reset_token_hash` varchar(64) DEFAULT NULL,
  `reset_token_expires_at` datetime DEFAULT NULL,
  `user_otp` varchar(64) NOT NULL,
  `role_as` tinyint(4) NOT NULL DEFAULT 0,
  `birthday` date DEFAULT NULL,
  `used_birthday_offer` tinyint(1) DEFAULT 0,
  `loyalty_tier` varchar(20) DEFAULT 'Bronze' COMMENT 'User loyalty tier (Bronze, Silver, Gold, etc)',
  `must_change_password` tinyint(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `user_id`, `user_name`, `last_name`, `password_hash`, `date`, `email`, `reset_token_hash`, `reset_token_expires_at`, `user_otp`, `role_as`, `birthday`, `used_birthday_offer`, `loyalty_tier`, `must_change_password`) VALUES
(11, 24554200238263138, 'admin', '', '$2y$10$R88KggVdEDZSwYqCRQWujexvZEYUegcDJRPfNCgge4hgkQvxt57ni', '2025-03-23 10:57:00', 'freshmarket2002@gmail.com', NULL, NULL, '', 1, NULL, 0, 'Bronze', 0),
(45, 20788486957608722, 'Abi', '', '$2y$10$kK6azdmEff2fMo.7fEb.2uCd76JC2Hg5s8EPGpOhtkT4EHNyR.hrC', '2025-05-16 06:53:02', 'sg5603427@gmail.com', NULL, NULL, '', 0, '2001-05-16', 0, 'Bronze', 0);

-- --------------------------------------------------------

--
-- Table structure for table `user_offers`
--

CREATE TABLE `user_offers` (
  `id` int(11) NOT NULL,
  `user_id` bigint(20) NOT NULL,
  `offer_id` int(11) NOT NULL,
  `status` enum('active','used','expired') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `used_date` timestamp NULL DEFAULT NULL,
  `order_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `user_offers`
--

INSERT INTO `user_offers` (`id`, `user_id`, `offer_id`, `status`, `created_at`, `used_date`, `order_id`) VALUES
(28, 45, 39, 'active', '2025-05-15 10:46:56', NULL, NULL);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `categories`
--
ALTER TABLE `categories`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `customer_details`
--
ALTER TABLE `customer_details`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_user_id` (`user_id`),
  ADD KEY `loyalty_tier` (`loyalty_tier`),
  ADD KEY `idx_customer_birthday` (`birthday`);

--
-- Indexes for table `daily_best_sells`
--
ALTER TABLE `daily_best_sells`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_product_type` (`product_id`,`type`);

--
-- Indexes for table `loyalty_settings`
--
ALTER TABLE `loyalty_settings`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `loyalty_tiers`
--
ALTER TABLE `loyalty_tiers`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `offer_redemptions`
--
ALTER TABLE `offer_redemptions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `offer_id` (`offer_id`),
  ADD KEY `idx_user_offer` (`user_id`,`offer_id`),
  ADD KEY `idx_redemption_date` (`redemption_date`);

--
-- Indexes for table `offer_usage`
--
ALTER TABLE `offer_usage`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `offer_id` (`offer_id`),
  ADD KEY `order_id` (`order_id`);

--
-- Indexes for table `orders`
--
ALTER TABLE `orders`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `order_number` (`order_number`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `offer_id` (`offer_id`);

--
-- Indexes for table `order_items`
--
ALTER TABLE `order_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `order_id` (`order_id`),
  ADD KEY `product_id` (`product_id`);

--
-- Indexes for table `order_promotions`
--
ALTER TABLE `order_promotions`
  ADD KEY `fk_order_promo_user_new` (`user_id`);

--
-- Indexes for table `personalized_offers`
--
ALTER TABLE `personalized_offers`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_offer_usage` (`id`,`usage_limit`,`usage_count`);

--
-- Indexes for table `products`
--
ALTER TABLE `products`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `promotions`
--
ALTER TABLE `promotions`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `promotion_usage`
--
ALTER TABLE `promotion_usage`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_user_promo` (`user_id`,`promotion_id`,`order_id`),
  ADD KEY `promotion_id` (`promotion_id`),
  ADD KEY `order_id` (`order_id`),
  ADD KEY `idx_promotion_usage` (`user_id`,`promotion_id`);

--
-- Indexes for table `rewards`
--
ALTER TABLE `rewards`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `user_id` (`user_id`);

--
-- Indexes for table `reward_redemptions`
--
ALTER TABLE `reward_redemptions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `order_id` (`order_id`);

--
-- Indexes for table `saved_cards`
--
ALTER TABLE `saved_cards`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user_cards` (`user_id`);

--
-- Indexes for table `store`
--
ALTER TABLE `store`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `store_redemptions`
--
ALTER TABLE `store_redemptions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `redemption_code` (`redemption_code`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD UNIQUE KEY `reset_token_hash` (`reset_token_hash`),
  ADD KEY `idx_loyalty_tier` (`loyalty_tier`);

--
-- Indexes for table `user_offers`
--
ALTER TABLE `user_offers`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `offer_id` (`offer_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `categories`
--
ALTER TABLE `categories`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `customer_details`
--
ALTER TABLE `customer_details`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=160;

--
-- AUTO_INCREMENT for table `daily_best_sells`
--
ALTER TABLE `daily_best_sells`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=22;

--
-- AUTO_INCREMENT for table `loyalty_settings`
--
ALTER TABLE `loyalty_settings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `loyalty_tiers`
--
ALTER TABLE `loyalty_tiers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `offer_redemptions`
--
ALTER TABLE `offer_redemptions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `offer_usage`
--
ALTER TABLE `offer_usage`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=60;

--
-- AUTO_INCREMENT for table `orders`
--
ALTER TABLE `orders`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=193;

--
-- AUTO_INCREMENT for table `order_items`
--
ALTER TABLE `order_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=225;

--
-- AUTO_INCREMENT for table `personalized_offers`
--
ALTER TABLE `personalized_offers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=40;

--
-- AUTO_INCREMENT for table `products`
--
ALTER TABLE `products`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=32;

--
-- AUTO_INCREMENT for table `promotions`
--
ALTER TABLE `promotions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

--
-- AUTO_INCREMENT for table `promotion_usage`
--
ALTER TABLE `promotion_usage`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `rewards`
--
ALTER TABLE `rewards`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- AUTO_INCREMENT for table `reward_redemptions`
--
ALTER TABLE `reward_redemptions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=39;

--
-- AUTO_INCREMENT for table `saved_cards`
--
ALTER TABLE `saved_cards`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `store`
--
ALTER TABLE `store`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `store_redemptions`
--
ALTER TABLE `store_redemptions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=49;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=55;

--
-- AUTO_INCREMENT for table `user_offers`
--
ALTER TABLE `user_offers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=29;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `customer_details`
--
ALTER TABLE `customer_details`
  ADD CONSTRAINT `customer_details_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `customer_details_ibfk_2` FOREIGN KEY (`loyalty_tier`) REFERENCES `loyalty_tiers` (`id`);

--
-- Constraints for table `daily_best_sells`
--
ALTER TABLE `daily_best_sells`
  ADD CONSTRAINT `daily_best_sells_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `offer_redemptions`
--
ALTER TABLE `offer_redemptions`
  ADD CONSTRAINT `offer_redemptions_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `offer_redemptions_ibfk_2` FOREIGN KEY (`offer_id`) REFERENCES `personalized_offers` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `offer_usage`
--
ALTER TABLE `offer_usage`
  ADD CONSTRAINT `offer_usage_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `offer_usage_ibfk_2` FOREIGN KEY (`offer_id`) REFERENCES `personalized_offers` (`id`),
  ADD CONSTRAINT `offer_usage_ibfk_3` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`);

--
-- Constraints for table `orders`
--
ALTER TABLE `orders`
  ADD CONSTRAINT `orders_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `orders_ibfk_2` FOREIGN KEY (`offer_id`) REFERENCES `personalized_offers` (`id`);

--
-- Constraints for table `order_items`
--
ALTER TABLE `order_items`
  ADD CONSTRAINT `order_items_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `order_items_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`);

--
-- Constraints for table `order_promotions`
--
ALTER TABLE `order_promotions`
  ADD CONSTRAINT `fk_order_promo_user_new` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `promotion_usage`
--
ALTER TABLE `promotion_usage`
  ADD CONSTRAINT `promotion_usage_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `promotion_usage_ibfk_2` FOREIGN KEY (`promotion_id`) REFERENCES `promotions` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `promotion_usage_ibfk_3` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `rewards`
--
ALTER TABLE `rewards`
  ADD CONSTRAINT `rewards_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `reward_redemptions`
--
ALTER TABLE `reward_redemptions`
  ADD CONSTRAINT `reward_redemptions_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `reward_redemptions_ibfk_2` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `saved_cards`
--
ALTER TABLE `saved_cards`
  ADD CONSTRAINT `saved_cards_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `store_redemptions`
--
ALTER TABLE `store_redemptions`
  ADD CONSTRAINT `store_redemptions_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `user_offers`
--
ALTER TABLE `user_offers`
  ADD CONSTRAINT `user_offers_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `user_offers_ibfk_2` FOREIGN KEY (`offer_id`) REFERENCES `personalized_offers` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
