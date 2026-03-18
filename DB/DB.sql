-- phpMyAdmin SQL Dump
-- version 4.9.7
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Generation Time: Jan 29, 2023 at 04:37 AM
-- Server version: 10.3.37-MariaDB-log-cll-lve
-- PHP Version: 7.4.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET AUTOCOMMIT = 0;
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `chanfsjd_pos`
--

-- --------------------------------------------------------

--
-- Table structure for table `activities`
--

CREATE TABLE `activities` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `activity` varchar(55) DEFAULT NULL,
  `datetime` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `activities`
--

-- --------------------------------------------------------

--
-- Table structure for table `adjustments`
--

CREATE TABLE `adjustments` (
  `id` int(10) UNSIGNED NOT NULL,
  `user_id` int(11) NOT NULL,
  `comments` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `adjustment_items`
--

CREATE TABLE `adjustment_items` (
  `id` int(10) UNSIGNED NOT NULL,
  `adjustment_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `adjustment` int(11) NOT NULL,
  `diff` int(11) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `banners`
--

CREATE TABLE `banners` (
  `id` int(11) NOT NULL,
  `image` varchar(200) DEFAULT NULL,
  `page` varchar(55) DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `banners`
--

INSERT INTO `banners` (`id`, `image`, `page`, `created_at`, `updated_at`) VALUES
(1, 'home1.jpg', 'home1', NULL, NULL),
(2, 'home2.jpg', 'home2', NULL, NULL),
(4, 'about.jpg', 'about', NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `bookings`
--

CREATE TABLE `bookings` (
  `id` int(11) NOT NULL,
  `customer_id` int(11) DEFAULT NULL,
  `bookings` varchar(500) DEFAULT NULL,
  `booking_date` date DEFAULT NULL,
  `booking_time` time DEFAULT NULL,
  `end_time` time DEFAULT NULL,
  `payment_status` enum('Pending','Paid','Cancelled') NOT NULL DEFAULT 'Pending',
  `name` varchar(100) DEFAULT NULL,
  `phone` varchar(100) DEFAULT NULL,
  `comments` text DEFAULT NULL,
  `amount` double(10,2) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `bookings`
--

INSERT INTO `bookings` (`id`, `customer_id`, `bookings`, `booking_date`, `booking_time`, `end_time`, `payment_status`, `name`, `phone`, `comments`, `amount`, `created_at`) VALUES
(15, 5, '[{\"type_id\":\"1\",\"qty\":\"3\",\"hours\":\"1\",\"price\":50},{\"type_id\":\"6\",\"qty\":\"1\",\"hours\":\"1\",\"price\":40},{\"type_id\":\"7\",\"qty\":\"1\",\"hours\":\"2\",\"price\":20},{\"type_id\":\"10\",\"qty\":\"1\",\"hours\":\"1\",\"price\":400}]', '2018-11-21', '08:00:00', NULL, 'Pending', 'Admin', NULL, 'sf', 610.00, '2018-11-08 16:57:13'),
(16, 5, '[{\"type_id\":\"1\",\"qty\":\"2\",\"hours\":\"1\",\"price\":300},{\"type_id\":\"8\",\"qty\":\"1\",\"hours\":\"1\",\"price\":15}]', '2019-02-14', '00:00:00', NULL, 'Pending', 'Admin', NULL, 'fgdgfd', 115.00, '2019-02-01 12:44:05');

-- --------------------------------------------------------

--
-- Table structure for table `booking_types`
--

CREATE TABLE `booking_types` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `price` double(10,2) NOT NULL,
  `qty` int(1) NOT NULL DEFAULT 0,
  `hourly_price` double(10,2) NOT NULL DEFAULT 0.00,
  `hours` int(11) NOT NULL DEFAULT 1,
  `type` enum('fixed','hourly','daily','weekly','monthly') NOT NULL,
  `available` int(11) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `booking_types`
--

INSERT INTO `booking_types` (`id`, `name`, `price`, `qty`, `hourly_price`, `hours`, `type`, `available`) VALUES
(1, 'Room', 50.00, 0, 300.00, 3, 'weekly', 3),
(6, 'Catering', 40.00, 0, 0.00, 1, 'fixed', 1),
(7, 'Waiter', 10.00, 1, 20.00, 1, 'hourly', 1),
(8, 'Waitress', 15.00, 1, 0.00, 1, 'fixed', 1),
(9, 'Bar', 100.00, 0, 0.00, 1, 'fixed', 1),
(10, 'Live Performance', 400.00, 0, 0.00, 1, 'fixed', 1);

-- --------------------------------------------------------

--
-- Table structure for table `categories`
--

CREATE TABLE `categories` (
  `id` int(11) NOT NULL,
  `company_id` int(11) DEFAULT NULL,
  `name` varchar(100) DEFAULT NULL,
  `image` VARCHAR(255) DEFAULT NULL,
  `thumb` VARCHAR(255) DEFAULT NULL,
  `printers` varchar(20) DEFAULT NULL,
  `sort` int(1) NOT NULL,
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  `created_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `categories`
--

--INSERT INTO `categories` (`id`, `company_id`, `name`, `printers`, `sort`, `updated_at`, `created_at`) VALUES
--(1, NULL, 'Appetizers', NULL, 0, '2023-01-24 23:06:09', '2023-01-24 23:06:09'),
--(2, NULL, 'Seafood', NULL, 0, '2023-01-24 23:06:55', '2023-01-24 23:06:55'),
--(3, NULL, 'Rice and Noodles', NULL, 0, '2023-01-24 23:07:43', '2023-01-24 23:07:43'),
--(4, NULL, 'Soup', NULL, 0, '2023-01-24 23:08:41', '2023-01-24 23:08:41'),
--(5, NULL, 'Desserts', NULL, 0, '2023-01-24 23:09:14', '2023-01-24 23:09:14'),
--(6, NULL, 'Beverages', NULL, 0, '2023-01-24 23:09:53', '2023-01-24 23:09:53');

-- --------------------------------------------------------

--
-- Table structure for table `companies`
--

CREATE TABLE `companies` (
  `id` int(11) NOT NULL,
  `name` varchar(100) DEFAULT NULL,
  `address` varchar(255) DEFAULT NULL,
  `phone` varchar(255) DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `customers`
--

CREATE TABLE `customers` (
  `id` int(10) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `password` varchar(255) DEFAULT NULL,
  `phone` varchar(255) NOT NULL,
  `address` text NOT NULL,
  `city` varchar(100) DEFAULT NULL,
  `state` varchar(100) DEFAULT NULL,
  `zip` varchar(10) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `customer_invoices`
--

CREATE TABLE `customer_invoices` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `invoice_date` date DEFAULT NULL,
  `ship_date` date DEFAULT NULL,
  `due_date` date DEFAULT NULL,
  `po_number` varchar(55) DEFAULT NULL,
  `terms` varchar(500) DEFAULT NULL,
  `bill_customer` int(11) DEFAULT NULL,
  `bill_address` varchar(255) DEFAULT NULL,
  `bill_city` varchar(100) DEFAULT NULL,
  `bill_state` varchar(100) DEFAULT NULL,
  `bill_zip` varchar(10) DEFAULT NULL,
  `bill_country` varchar(100) DEFAULT NULL,
  `ship_customer` int(11) DEFAULT NULL,
  `ship_address` varchar(500) DEFAULT NULL,
  `ship_city` varchar(100) DEFAULT NULL,
  `ship_state` varchar(100) DEFAULT NULL,
  `ship_zip` varchar(10) DEFAULT NULL,
  `ship_country` varchar(100) DEFAULT NULL,
  `total_amount` double(10,2) DEFAULT NULL,
  `tax` double(10,2) DEFAULT NULL,
  `note` text DEFAULT NULL,
  `discount` double(10,2) DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- --------------------------------------------------------

--
-- Table structure for table `customer_invoice_items`
--

CREATE TABLE `customer_invoice_items` (
  `id` int(11) NOT NULL,
  `invoice_id` int(11) DEFAULT NULL,
  `product_id` int(11) DEFAULT NULL,
  `product_name` varchar(200) DEFAULT NULL,
  `quantity` int(11) DEFAULT NULL,
  `units` int(11) NOT NULL DEFAULT 1,
  `unit_price` double(10,2) DEFAULT NULL,
  `gross_total` double(10,2) DEFAULT NULL,
  `sold_price` double(10,2) DEFAULT NULL,
  `created_at` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `email_templates`
--

CREATE TABLE `email_templates` (
  `id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `short_code` varchar(255) NOT NULL,
  `subject` text NOT NULL,
  `message` text NOT NULL,
  `status` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` datetime NOT NULL,
  `created_by` int(11) NOT NULL,
  `updated_at` datetime NOT NULL,
  `updated_by` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `email_templates`
--

INSERT INTO `email_templates` (`id`, `title`, `short_code`, `subject`, `message`, `status`, `created_at`, `created_by`, `updated_at`, `updated_by`) VALUES
(1, 'You Are In!', 'you_are_in', 'You Are In!', '<meta name=\"viewport\" content=\"width=device-width, initial-scale=1.0\" /><meta http-equiv=\"Content-Type\" content=\"text/html; charset=UTF-8\" />\r\n<title></title>\r\n<link href=\"https://fonts.googleapis.com/css?family=Heebo:300,400,500,700,800\" rel=\"stylesheet\" /><script src=\"https://ajax.googleapis.com/ajax/libs/jquery/3.3.1/jquery.min.js\"></script>\r\n<style type=\"text/css\">body {\r\n			padding: 0px;\r\n			margin: 0px;\r\n			/*font-family: -apple-system,BlinkMacSystemFont,\"Segoe UI\",Roboto,Oxygen-Sans,Ubuntu,Cantarell,\"Helvetica Neue\",sans-serif;*/\r\n			font-family: -apple-system, BlinkMacSystemFont, \"Segoe UI\", Roboto, Helvetica, Arial, sans-serif, \"Apple Color Emoji\", \"Segoe UI Emoji\", \"Segoe UI Symbol\";\r\n			color: #666;\r\n		}\r\n\r\n		a > img {\r\n\r\n    padding: 0px;\r\n    margin: 0px;\r\n    border: 0px;\r\n    outline: none;\r\n\r\n}\r\n.cke_show_borders table.cke_show_border, .cke_show_borders table.cke_show_border > tbody > tr > td, .cke_show_borders table.cke_show_border > thead > tr > th, .cke_show_borders table.cke_show_border > tfoot > tr > td {border:0px;}\r\n\r\n\r\n		a {\r\n			text-decoration: none;\r\n			transition: all .5s;\r\n		}\r\n		strong {\r\n			font-weight: 500;\r\n		}\r\n		.text-primary {\r\n			color: #18A689;\r\n		}\r\n		.bg-primary {\r\n			background-color: #18A689;\r\n		}\r\n		.text-danger {\r\n			color: #dc3545;\r\n		}\r\n		.text-white {\r\n			color: #FFF;\r\n		}\r\n		.m-0 {\r\n			margin: 0px;\r\n		}\r\n		.w-100 {\r\n			width: 100%;\r\n		}\r\n		.text-center {\r\n			text-align: center;\r\n		}\r\n		.text-right {\r\n			text-align: right;\r\n		}\r\n		.email-container {\r\n			max-width: 500px;\r\n			margin: 0 auto;\r\n		}\r\n		.btn-email {\r\n			background-color: #18A689;\r\n			border-radius: 3px;\r\n			color: #FFF;\r\n			text-align: center;\r\n			display: inline-block;\r\n			padding: 1rem;\r\n			font-weight: 600;\r\n			font-size: 1.5rem;\r\n		}\r\n		.btn-signup {\r\n			padding: 0px;\r\n			height: 60px;\r\n			line-height: 56px;\r\n			width: 295px;\r\n			margin-top: 1rem;\r\n		}\r\n		.btn-accept {\r\n			padding: 0px;\r\n			height: 60px;\r\n			line-height: 56px;\r\n		}\r\n		.btn-decline {\r\n			padding: 0px;\r\n			height: 60px;\r\n			line-height: 56px;\r\n			border: #666666 1px solid;\r\n			color: #666;\r\n			background-color: transparent;\r\n		}\r\n		.mail-container {\r\n			max-width: 600px;\r\n			width: 100%;\r\n			margin: 0 auto;\r\n		}\r\n		.mail-header > tr > th {\r\n			padding: 2rem 1rem;\r\n			text-align: left;\r\n		}\r\n		.cke_show_borders table.cke_show_border > tfoot > tr > td, .mail-footer > tr > td {\r\n			padding: 2rem 1rem;\r\n			text-align: center;\r\n			border-top: #e8e8e8 2px solid;\r\n			font-size: 13px;\r\n		}\r\n		.mail-footer .subscribe-bar a {\r\n			color: #666;\r\n		}\r\n		.cke_show_borders table.cke_show_border > tbody.mail-body > tr > td, .mail-body > tr > td {\r\n			padding: 2rem 1rem;\r\n			text-align: left;\r\n			border-top: #e8e8e8 2px solid;\r\n		}\r\n		.signup-body p {\r\n			font-size: 1.5rem;\r\n			font-weight: 300;\r\n		}\r\n		.mail-sm-icons {\r\n			margin-top: 1rem;\r\n		}\r\n		.booked-services-table {\r\n			font-size: 1.5rem;\r\n			font-weight: 500;\r\n		}\r\n		.booked-services-table tr td {\r\n			padding: .5rem 0;\r\n		}\r\n		.booked-services-btns td {\r\n			padding: 10px !important;\r\n		}\r\n		.booked-services-btns .btn {\r\n			display: block;\r\n		}\r\n		\r\n		.how-it-works-table {\r\n			margin: 0 -20px;\r\n			width: calc(100% + 40px);\r\n		}\r\n		.how-it-works-table p {\r\n			color: #FFF;\r\n			margin: 0px;\r\n			text-align: center;\r\n			font-weight: 500;\r\n			padding: 40px 20px;\r\n		}\r\n		.how-it-works-table td {\r\n			position: relative;\r\n		}\r\n		.how-it-works-table .count {\r\n			display: inline-block;\r\n			background-color: #FFF;\r\n			text-align: center;\r\n			width: 40px;\r\n			height: 40px;\r\n			line-height: 40px;\r\n			font-weight: 600;\r\n			font-size: 16px;\r\n			border-radius: 0 50% 50% 50%;\r\n			margin: -5px 0 -40px -5px;\r\n			position: absolute;\r\n			left: 0;\r\n			top: 0;\r\n		}\r\n\r\n		.custom-checkbox {\r\n			color: #ffcc28;\r\n			margin: 1rem 0;\r\n			min-width: 130px;\r\n		}\r\n		.checkbox-inline {\r\n			display: inline-block;\r\n			cursor: pointer;\r\n		}\r\n		.custom-checkbox input[type=\"radio\"] {\r\n			float: left;\r\n			margin: 5px;\r\n		}\r\n		.custom-checkbox input[type=\"radio\"] + label {\r\n			font-size: 24px;\r\n			line-height: 18px;\r\n			height: 22px;\r\n			display: block;\r\n			cursor: pointer;\r\n		}\r\n		.custom-checkbox input[type=\"radio\"] + label img {\r\n			width: 20px;\r\n		}\r\n		.custom-checkbox input[type=\"radio\"] + label .checkbox-div , .custom-checkbox input[type=\"radio\"] + label .checkbox-tick-div {\r\n			float: left;\r\n			display: block;\r\n		}\r\n		.custom-checkbox input[type=\"radio\"] + label .checkbox-div {\r\n			border: #ffcc28 1px solid;\r\n			width: 20px;\r\n			height: 20px;\r\n			margin-right: 10px;\r\n		}\r\n		.custom-checkbox input[type=\"radio\"]:checked + label .checkbox-tick-div {\r\n			border-left: #ffcc28 2px solid;\r\n			border-bottom: #ffcc28 2px solid;\r\n			width: 10px;\r\n			height: 5px;\r\n			transform: rotate(-45deg);\r\n			background-color: yellow;\r\n		}\r\n\r\n		@media  screen and (max-width:600px) {\r\n\r\n			.mail-header > tr > th {\r\n				padding: 2rem 1rem !important;\r\n			}\r\n			.mail-body > tr > td {\r\n				padding: 2rem 1rem !important;\r\n			}\r\n			.mail-footer > tr > td {\r\n				padding: 2rem 1rem !important;\r\n			}\r\n			.booked-services-btns tr td .btn {\r\n				margin: .5rem 0;\r\n				font-size: 14px;\r\n			}\r\n\r\n		}\r\n</style>\r\n<table border=\"0\" cellpadding=\"0\" cellspacing=\"0\" class=\"mail-container\" width=\"100%\">\r\n	<thead class=\"mail-header\">\r\n		<tr>\r\n			<th>\r\n			<div class=\"email-container\"><img alt=\"\" src=\"http://outislandlounge.com/assets/frontend/img/logo.png\" /></div>\r\n			</th>\r\n		</tr>\r\n	</thead>\r\n	<tbody class=\"mail-body signup-body\">\r\n		<tr>\r\n			<td class=\"bg-primary text-white\">\r\n			<h2 class=\"m-0 text-center\">Thank you email for Out Island Lounge!</h2>\r\n			</td>\r\n		</tr>\r\n		<tr>\r\n			<td>\r\n			<div class=\"email-container\">\r\n			<p><strong>About Us</strong></p>\r\n\r\n			<p>Our company started with one mission. To help you find and book the most talented beauty professionals out there.</p>\r\n\r\n			<p class=\"text-primary\"><small><strong class=\"text-underline\">Ready To Discover The Best Beauty Professionals?</strong></small></p>\r\n\r\n			<p>We&#39;re really excited to have you on board and hope we can make your experience with beauty better than it&#39;s ever been before.</p>\r\n\r\n			<p>PS: Don&#39;t feel shy to hit reply to this email and give us any feedback.</p>\r\n			<a class=\"btn-email btn-primary btn-block\" href=\"http://outislandlounge.com\">Order Now OutIslandLounge</a></div>\r\n			</td>\r\n		</tr>\r\n	</tbody>\r\n	<tfoot class=\"mail-footer\">\r\n		<tr>\r\n			<td>\r\n			<p>Powered By</p>\r\n\r\n			<p><a href=\"http://localhost/laravelproject/bookbeauty/public\" target=\"_blank\"><img alt=\"\" src=\"http://outislandlounge.com/assets/frontend/img/logo.png\" /></a></p>\r\n\r\n			<p>&copy; 2018 OutIslandLounge<br />\r\n			All rights reserved.</p>\r\n\r\n			<div class=\"mail-sm-icons\"><a href=\"https://instagram.com\" target=\"_blank\"><img alt=\"\" src=\"http://localhost/laravelproject/bookbeauty/public/assets/img/icon-instagram.png\" /></a> <a href=\"https://www.youtube.com/\" target=\"_blank\"><img alt=\"\" src=\"http://localhost/laravelproject/bookbeauty/public/assets/img/icon-youtube.png\" /></a> <a href=\"https://www.facebook.com/\" target=\"_blank\"><img alt=\"\" src=\"http://localhost/laravelproject/bookbeauty/public/assets/img/icon-facebook.png\" /></a></div>\r\n			</td>\r\n		</tr>\r\n	</tfoot>\r\n</table>', 1, '2018-12-01 01:09:43', 1, '2018-12-20 11:03:17', 5);

-- --------------------------------------------------------

--
-- Table structure for table `expenses`
--

CREATE TABLE `expenses` (
  `id` int(11) NOT NULL,
  `title` varchar(255) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  `price` double(10,2) DEFAULT NULL,
  `company_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

--
-- Dumping data for table `expenses`
--

INSERT INTO `expenses` (`id`, `title`, `description`, `created_at`, `updated_at`, `price`, `company_id`) VALUES
(1, 'test eit', 'this is a test edit', '2017-11-25 10:22:13', '2017-11-25 05:23:46', 12.00, NULL),
(2, 'new name', 'thoe', '2017-11-25 10:25:30', '2017-11-25 05:25:44', 123.00, NULL),
(3, 'Rotti Expense', 'this isa a totti', '2017-11-25 10:51:01', NULL, 440.00, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `hold_order`
--

CREATE TABLE `hold_order` (
  `id` int(11) NOT NULL,
  `company_id` int(11) DEFAULT NULL,
  `room_id` int(11) DEFAULT NULL,
  `table_id` int(11) DEFAULT NULL,
  `user_id` int(11) DEFAULT NULL,
  `comment` varchar(255) DEFAULT NULL,
  `cart` text DEFAULT NULL,
  `status` int(1) NOT NULL DEFAULT 0,
  `discount` double(10,2) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `hold_order`
--

INSERT INTO `hold_order` (`id`, `company_id`, `room_id`, `table_id`, `user_id`, `comment`, `cart`, `status`, `discount`, `created_at`) VALUES
(43, NULL, NULL, 15, 26, NULL, '[{\"id\":\"501\",\"product_id\":\"5\",\"price\":\"1\",\"size\":\"small\",\"name\":\"1 more product (small)\",\"note\":\"this\",\"deleted\":\"0\",\"quantity\":\"1\",\"p_qty\":\"1\"},{\"id\":\"5137\",\"product_id\":\"5\",\"price\":\"2\",\"size\":\"large\",\"name\":\"1 more product (large)\",\"note\":\"hello\",\"deleted\":\"0\",\"quantity\":\"1\",\"p_qty\":\"1\"}]', 1, NULL, '2021-10-09 07:15:28'),
(44, NULL, NULL, 1, 1, NULL, '[{\"id\":\"30\",\"product_id\":\"3\",\"price\":\"2\",\"size\":\"Title\",\"name\":\"Arfan (Title)\",\"quantity\":\"1\",\"p_qty\":\"1\"},{\"id\":\"31\",\"product_id\":\"3\",\"price\":\"10\",\"size\":\"Description\",\"name\":\"Arfan (Description)\",\"quantity\":\"1\",\"p_qty\":\"1\"}]', 1, NULL, '2022-06-04 13:37:06');

-- --------------------------------------------------------

--
-- Table structure for table `homepage`
--

CREATE TABLE `homepage` (
  `id` int(10) UNSIGNED NOT NULL,
  `key` varchar(100) NOT NULL,
  `type` varchar(10) DEFAULT NULL,
  `label` varchar(100) NOT NULL,
  `value` text NOT NULL,
  `language` varchar(10) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

--
-- Dumping data for table `homepage`
--

INSERT INTO `homepage` (`id`, `key`, `type`, `label`, `value`, `language`, `created_at`, `updated_at`) VALUES
(1, 'story_title', 'text', 'Story Title', '<span>Discover</span>Our Story', NULL, NULL, '2017-09-20 16:13:04'),
(2, 'story_desc', 'textarea', 'Story Description', 'accusantium doloremque laudantium, totam rem aperiam, eaque ipsa quae ab illo inventore veritatis et quasi architecto beatae vitae dicta sunt explicabo. Nemo enim ipsam voluptatem quia voluptas sit aspernatur aut odit aut fugit, sed quia consequuntur magni dolores eos qui ratione voluptatem sequi nesciunt. Neque porro quisquam est.', NULL, NULL, '2017-09-20 16:13:04'),
(3, 'menu_title', 'text', 'Menu Title', '<span>Discover</span>Our Menu', NULL, NULL, '2017-09-20 16:13:04'),
(4, 'menu_desc', 'textarea', 'Menu Description', 'accusantium doloremque laudantium, totam rem aperiam, eaque ipsa quae ab illo inventore veritatis et quasi architecto beatae vitae dicta sunt explicabo. Nemo enim ipsam voluptatem quia voluptas sit aspernatur aut odit aut fugit, sed quia consequuntur magni dolores eos qui ratione voluptatem sequi nesciunt. Neque porro quisquam est.', NULL, NULL, '2017-09-20 16:13:04'),
(5, 'img_title1', 'text', 'Image Title 1', '<h2><span>We Are Sharing</span></h2>                    <h1>delicious treats</h1>', NULL, NULL, '2017-09-25 16:36:13'),
(6, 'img_title2', 'text', 'Image Title 2', '<h2><span>The Perfect</span></h2>                    <h1>Blend</h1>', NULL, NULL, '2017-09-25 16:36:13'),
(7, 'category', NULL, 'Home Categories', '1,2,3,4', NULL, NULL, '2023-01-25 22:18:12');

-- --------------------------------------------------------

--
-- Table structure for table `inventories`
--

CREATE TABLE `inventories` (
  `id` int(10) UNSIGNED NOT NULL,
  `supplier_id` int(11) DEFAULT NULL,
  `product_id` int(11) NOT NULL,
  `type` varchar(10) DEFAULT NULL,
  `quantity` int(11) NOT NULL,
  `track_type` varchar(55) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `comments` varchar(500) DEFAULT NULL,
  `storeroom` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

--
-- Dumping data for table `inventories`
--

INSERT INTO `inventories` (`id`, `supplier_id`, `product_id`, `type`, `quantity`, `track_type`, `created_at`, `updated_at`, `comments`, `storeroom`) VALUES
(1, 2, 2, NULL, 5, 'add', NULL, NULL, NULL, NULL),
(2, 2, 1, NULL, 3, 'add', NULL, NULL, NULL, NULL),
(3, 2, 2, NULL, 1, 'add', '2018-08-29 07:28:46', NULL, NULL, NULL),
(4, 2, 2, NULL, 20, 'sub', '2018-08-29 07:28:56', NULL, NULL, NULL),
(5, 2, 2, NULL, 10, 'add', '2018-09-23 08:10:55', NULL, NULL, NULL),
(6, 2, 2, NULL, 3, 'add', '2018-11-16 07:10:23', NULL, NULL, NULL),
(7, 2, 1, NULL, 4, 'add', '2018-11-16 07:10:23', NULL, NULL, NULL),
(8, 2, 1, NULL, 10, 'add', '2018-11-16 07:11:10', NULL, NULL, NULL),
(9, NULL, 2, NULL, 1, 'add', '2018-11-19 07:39:12', NULL, 'testing', NULL),
(10, NULL, 2, NULL, 1, 'add', '2018-11-19 07:39:48', NULL, 'fdsfs', NULL),
(11, NULL, 1, NULL, 2, 'add', '2018-11-19 07:39:48', NULL, 'fds fsd fsdfsdf sdf dsfsd fsf s', NULL),
(12, NULL, 2, NULL, 1, 'add', '2018-11-20 08:13:02', NULL, NULL, 4),
(13, NULL, 1, NULL, 1, 'add', '2018-11-20 08:13:03', NULL, NULL, 17),
(14, NULL, 5, NULL, 200, 'add', '2019-08-27 18:30:47', NULL, NULL, 300),
(15, NULL, 7, NULL, 250, 'add', '2019-08-27 18:30:47', NULL, NULL, 500),
(16, NULL, 4, NULL, 200, 'add', '2019-08-31 15:42:01', NULL, NULL, 300),
(17, NULL, 8, NULL, 10, 'add', '2021-02-05 05:55:38', NULL, NULL, 100),
(18, NULL, 9, NULL, 2, 'add', '2021-02-05 05:55:38', NULL, NULL, 8),
(19, NULL, 5, NULL, 10, 'add', '2023-01-22 11:42:16', NULL, NULL, 290),
(20, NULL, 5, 'wherehouse', 20, 'add', '2023-01-22 11:44:29', NULL, NULL, 320),
(21, NULL, 5, NULL, 70, 'add', '2023-01-22 11:46:07', NULL, NULL, 300),
(22, NULL, 5, NULL, 70, 'add', '2023-01-22 11:48:33', NULL, NULL, 300),
(23, NULL, 5, NULL, 40, 'sub', '2023-01-22 11:49:17', NULL, 'wrong', 300);

-- --------------------------------------------------------

--
-- Table structure for table `ltm_translations`
--

CREATE TABLE `ltm_translations` (
  `id` int(10) UNSIGNED NOT NULL,
  `status` int(11) NOT NULL DEFAULT 0,
  `locale` varchar(255) NOT NULL,
  `group` varchar(255) NOT NULL,
  `key` varchar(255) NOT NULL,
  `value` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `menus`
--

CREATE TABLE `menus` (
  `menu_id` int(11) NOT NULL,
  `parent_id` int(11) NOT NULL DEFAULT 0,
  `link` varchar(255) NOT NULL,
  `title` varchar(255) NOT NULL,
  `active` tinyint(1) NOT NULL DEFAULT 0,
  `order_by` int(11) NOT NULL,
  `translate` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

--
-- Dumping data for table `menus`
--

INSERT INTO `menus` (`menu_id`, `parent_id`, `link`, `title`, `active`, `order_by`, `translate`) VALUES
(1, 0, 'home', 'Home', 1, 1, 'Home'),
(2, 0, 'map-home', 'Map Home', 1, 2, 'MapHome'),
(3, 0, 'about-us', 'About Us', 1, 3, 'About'),
(4, 3, 'gallery', 'Gallery', 1, 1, 'Gallery'),
(5, 0, 'contact-us', 'Contact', 1, 5, 'Contact'),
(6, 3, 'services', 'Services', 1, 2, 'Services'),
(7, 3, 'listing?type=RENT', 'Rent', 1, 5, 'Rent'),
(8, 3, 'listing?type=SALE', 'Sale', 1, 3, 'Sale'),
(9, 0, 'faq', 'FAQ', 0, 2, 'Faq'),
(10, 3, 'blog', 'Blog', 1, 4, 'Blog'),
(12, 0, 'all-agent', 'Agents', 1, 4, 'Agents'),
(13, 0, 'admin', 'Submit Property', 1, 6, 'Submit_Property'),
(14, 0, 'loan-calculator', 'Calculator', 0, 3, 'Calculator');

-- --------------------------------------------------------

--
-- Table structure for table `migrations`
--

CREATE TABLE `migrations` (
  `id` int(10) UNSIGNED NOT NULL,
  `migration` varchar(255) NOT NULL,
  `batch` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `migrations`
--

INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES
(1, '2017_10_16_183611_create_categories_table', 0),
(2, '2017_10_16_183611_create_customers_table', 0),
(3, '2017_10_16_183611_create_homepage_table', 0),
(4, '2017_10_16_183611_create_menus_table', 0),
(5, '2017_10_16_183611_create_newsletters_table', 0),
(6, '2017_10_16_183611_create_pages_table', 0),
(7, '2017_10_16_183611_create_password_resets_table', 0),
(8, '2017_10_16_183611_create_permission_role_table', 0),
(9, '2017_10_16_183611_create_permissions_table', 0),
(10, '2017_10_16_183611_create_products_table', 0),
(11, '2017_10_16_183611_create_roles_table', 0),
(12, '2017_10_16_183611_create_sale_items_table', 0),
(13, '2017_10_16_183611_create_sales_table', 0),
(14, '2017_10_16_183611_create_settings_table', 0),
(15, '2017_10_16_183611_create_sliders_table', 0),
(16, '2017_10_16_183611_create_suppliers_table', 0),
(17, '2017_10_16_183611_create_users_table', 0),
(18, '2017_10_23_101103_create_categories_table', 0),
(19, '2017_10_23_101103_create_customers_table', 0),
(20, '2017_10_23_101103_create_homepage_table', 0),
(21, '2017_10_23_101103_create_menus_table', 0),
(22, '2017_10_23_101103_create_newsletters_table', 0),
(23, '2017_10_23_101103_create_pages_table', 0),
(24, '2017_10_23_101103_create_password_resets_table', 0),
(25, '2017_10_23_101103_create_permission_role_table', 0),
(26, '2017_10_23_101103_create_permissions_table', 0),
(27, '2017_10_23_101103_create_products_table', 0),
(28, '2017_10_23_101103_create_roles_table', 0),
(29, '2017_10_23_101103_create_sale_items_table', 0),
(30, '2017_10_23_101103_create_sales_table', 0),
(31, '2017_10_23_101103_create_settings_table', 0),
(32, '2017_10_23_101103_create_sliders_table', 0),
(33, '2017_10_23_101103_create_suppliers_table', 0),
(34, '2017_10_23_101103_create_users_table', 0),
(35, '2017_11_20_162731_create_categories_table', 0),
(36, '2017_11_20_162731_create_customers_table', 0),
(37, '2017_11_20_162731_create_expense_table', 0),
(38, '2017_11_20_162731_create_homepage_table', 0),
(39, '2017_11_20_162731_create_menus_table', 0),
(40, '2017_11_20_162731_create_newsletters_table', 0),
(41, '2017_11_20_162731_create_pages_table', 0),
(42, '2017_11_20_162731_create_password_resets_table', 0),
(43, '2017_11_20_162731_create_permission_role_table', 0),
(44, '2017_11_20_162731_create_permissions_table', 0),
(45, '2017_11_20_162731_create_products_table', 0),
(46, '2017_11_20_162731_create_roles_table', 0),
(47, '2017_11_20_162731_create_sale_items_table', 0),
(48, '2017_11_20_162731_create_sales_table', 0),
(49, '2017_11_20_162731_create_settings_table', 0),
(50, '2017_11_20_162731_create_sliders_table', 0),
(51, '2017_11_20_162731_create_suppliers_table', 0),
(52, '2017_11_20_162731_create_users_table', 0),
(53, '2017_11_25_110908_create_categories_table', 0),
(54, '2017_11_25_110908_create_customers_table', 0),
(55, '2017_11_25_110908_create_expenses_table', 0),
(56, '2017_11_25_110908_create_homepage_table', 0),
(57, '2017_11_25_110908_create_menus_table', 0),
(58, '2017_11_25_110908_create_newsletters_table', 0),
(59, '2017_11_25_110908_create_pages_table', 0),
(60, '2017_11_25_110908_create_password_resets_table', 0),
(61, '2017_11_25_110908_create_permission_role_table', 0),
(62, '2017_11_25_110908_create_permissions_table', 0),
(63, '2017_11_25_110908_create_products_table', 0),
(64, '2017_11_25_110908_create_roles_table', 0),
(65, '2017_11_25_110908_create_sale_items_table', 0),
(66, '2017_11_25_110908_create_sales_table', 0),
(67, '2017_11_25_110908_create_settings_table', 0),
(68, '2017_11_25_110908_create_sliders_table', 0),
(69, '2017_11_25_110908_create_suppliers_table', 0),
(70, '2017_11_25_110908_create_users_table', 0);

-- --------------------------------------------------------

--
-- Table structure for table `newsletters`
--

CREATE TABLE `newsletters` (
  `id` int(11) NOT NULL,
  `email` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `newsletters`
--

INSERT INTO `newsletters` (`id`, `email`) VALUES
(5, 'arfan67@gmail.com');

-- --------------------------------------------------------

--
-- Table structure for table `pages`
--

CREATE TABLE `pages` (
  `id` int(11) NOT NULL,
  `title` varchar(100) NOT NULL,
  `slug` varchar(255) NOT NULL,
  `image` varchar(255) NOT NULL,
  `body` longtext NOT NULL,
  `parent_id` int(11) NOT NULL,
  `is_delete` tinyint(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

--
-- Dumping data for table `pages`
--

INSERT INTO `pages` (`id`, `title`, `slug`, `image`, `body`, `parent_id`, `is_delete`) VALUES
(3, 'About Us', 'about-us', 'pages/SIUkiFG8DW8gJ0ZaCPymRe4bscaJDxsDTGXOmQCk.jpg', '<p> Lorem ipsum dolor sit amet, consectetur adipisicing elit. Sed voluptate nihil eum consectetur similique? Consectetur, quod, incidunt, harum nisi dolores delectus reprehenderit voluptatem perferendis dicta dolorem non blanditiis ex fugiat. </p>\r\n\r\n\r\n<h2> Heading 2</h2>\r\n\r\n<p> Lorem ipsum dolor sit amet, consectetur adipisicing elit. Sed voluptate nihil eum consectetur similique? Consectetur, quod, incidunt, harum nisi dolores delectus reprehenderit voluptatem perferendis dicta dolorem non blanditiis ex fugiat. </p><p><br></p><h2 style=\"color: rgb(103, 106, 108);\">Heading 2</h2><p>Lorem ipsum dolor sit amet, consectetur adipisicing elit. Sed voluptate nihil eum consectetur similique? Consectetur, quod, incidunt, harum nisi dolores delectus reprehenderit voluptatem perferendis dicta dolorem non blanditiis ex fugiat.</p>', 0, 0);

-- --------------------------------------------------------

--
-- Table structure for table `password_resets`
--

CREATE TABLE `password_resets` (
  `email` varchar(255) NOT NULL,
  `token` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `payments`
--

CREATE TABLE `payments` (
  `id` int(11) NOT NULL,
  `body` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- --------------------------------------------------------

--
-- Table structure for table `permissions`
--

CREATE TABLE `permissions` (
  `id` int(10) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL,
  `display_name` varchar(255) DEFAULT NULL,
  `description` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `permissions`
--

INSERT INTO `permissions` (`id`, `name`, `display_name`, `description`, `created_at`, `updated_at`) VALUES
(1, 'view_sale', 'View Sales ', NULL, NULL, NULL),
(2, 'add_sale', 'Add Sales', NULL, NULL, NULL),
(3, 'add_product', 'Add Product ', NULL, NULL, NULL),
(4, 'view_products', 'View Products', NULL, NULL, NULL),
(5, 'edit_products', 'Edit Products', NULL, NULL, NULL),
(6, 'delete_products', 'Delete Products', NULL, NULL, NULL),
(7, 'add_category', 'Add Category ', NULL, NULL, NULL),
(8, 'view_categorys', 'View Categorys', NULL, NULL, NULL),
(9, 'edit_categorys', 'Edit Categorys', NULL, NULL, NULL),
(10, 'delete_categorys', 'Delete Categorys', NULL, NULL, NULL),
(11, 'add_expense', 'Add Expense ', NULL, NULL, NULL),
(12, 'view_expense', 'View Expenses', NULL, NULL, NULL),
(13, 'edit_expense', 'Edit Expenses', NULL, NULL, NULL),
(14, 'delete_expense', 'Delete Expenses', NULL, NULL, NULL),
(15, 'setting', 'Overall Setting', NULL, NULL, NULL),
(16, 'frontend_setting', 'Frontend Setting', NULL, NULL, NULL),
(17, 'reports', 'View Reports ', NULL, NULL, NULL),
(18, 'roles', 'Manage Roles ', NULL, NULL, NULL),
(19, 'dashboard', 'Dashboard', NULL, NULL, NULL),
(20, 'users', 'Manage Users', NULL, NULL, NULL),
(21, 'Profile', 'View Profile', NULL, NULL, NULL),
(22, 'suppliers', 'Manage Suppliers', NULL, NULL, NULL),
(23, 'customers', 'Manage Customers', NULL, NULL, NULL),
(24, 'update_inventory', 'Update Inventory', NULL, NULL, NULL),
(25, 'inventory', 'Inventory', NULL, NULL, NULL),
(26, 'reservations', 'Reservations', NULL, NULL, NULL),
(27, 'bookings', 'Bookings (Book Now)', NULL, NULL, NULL),
(28, 'sales_staff_to_compelte_sales', 'Sales Staff to Complete Sales', 'Sales Staff to Complete Sales', NULL, NULL),
(29, 'purchases', 'Purchases', NULL, NULL, NULL),
(30, 'customer_invoice', 'Customer Invoices', NULL, NULL, NULL),
(31, 'newsletters', 'Newsletters', NULL, NULL, NULL),
(32, 'purchases', 'Purchases', NULL, NULL, NULL),
(33, 'customer_invoices', 'Customer Invoices', NULL, NULL, NULL),
(34, 'inventory', 'Inventory', NULL, NULL, NULL),
(35, 'dashboard', 'Dashboard', NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `permission_role`
--

CREATE TABLE `permission_role` (
  `permission_id` int(10) UNSIGNED NOT NULL,
  `role_id` int(10) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `permission_role`
--

INSERT INTO `permission_role` (`permission_id`, `role_id`) VALUES
(1, 1),
(1, 2),
(2, 1),
(2, 2),
(2, 3),
(3, 1),
(3, 2),
(4, 1),
(4, 2),
(5, 1),
(5, 2),
(6, 1),
(6, 2),
(7, 1),
(7, 2),
(8, 1),
(8, 2),
(9, 1),
(9, 2),
(10, 1),
(10, 2),
(11, 1),
(11, 2),
(12, 1),
(12, 2),
(13, 1),
(13, 2),
(14, 1),
(14, 2),
(15, 1),
(15, 2),
(16, 1),
(16, 2),
(17, 1),
(17, 2),
(18, 1),
(18, 2),
(19, 1),
(19, 2),
(20, 1),
(20, 2),
(21, 1),
(21, 2),
(22, 1),
(22, 2),
(23, 1),
(23, 2),
(24, 1),
(24, 2),
(25, 1),
(25, 2),
(26, 1),
(26, 2),
(27, 1),
(27, 2),
(28, 1),
(28, 2),
(29, 1),
(29, 2),
(30, 1),
(30, 2),
(31, 1),
(31, 2),
(32, 1),
(32, 2),
(33, 1),
(33, 2),
(34, 1),
(34, 2),
(35, 1),
(35, 2);

-- --------------------------------------------------------

--
-- Table structure for table `printers`
--

CREATE TABLE `printers` (
  `id` int(11) NOT NULL,
  `name` varchar(100) DEFAULT NULL,
  `ip` varchar(55) NOT NULL,
  `port` int(10) NOT NULL,
  `status` tinytext NOT NULL,
  `created_at` datetime NOT NULL,
  `updated_at` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

--
-- Dumping data for table `printers`
--

INSERT INTO `printers` (`id`, `name`, `ip`, `port`, `status`, `created_at`, `updated_at`) VALUES
(1, 'PRinter 1', '192.168.0.1', 9101, '1', '0000-00-00 00:00:00', '2021-09-20 04:39:07'),
(2, NULL, '192.168.0.2', 9200, '1', '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(3, NULL, '192.168.0.1', 90, '1', '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(4, NULL, '151.53.220.146', 333, '1', '0000-00-00 00:00:00', '0000-00-00 00:00:00');

-- --------------------------------------------------------

--
-- Table structure for table `products`
--

CREATE TABLE `products` (
  `id` int(10) UNSIGNED NOT NULL,
  `category_id` int(11) DEFAULT NULL,
  `company_id` int(11) DEFAULT NULL,
  `barcode` varchar(255) DEFAULT NULL,
  `name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `titles` varchar(255) DEFAULT NULL,
  `prices` varchar(255) NOT NULL,
  `qty` varchar(255) DEFAULT NULL,
  `quantity` int(11) NOT NULL DEFAULT 0,
  `is_delete` int(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `warehouse` int(11) DEFAULT NULL,
  `min_qty` int(11) DEFAULT NULL,
  `store_min` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

--
-- Dumping data for table `products`
--

INSERT INTO `products` (`id`, `category_id`, `company_id`, `barcode`, `name`, `description`, `titles`, `prices`, `qty`, `quantity`, `is_delete`, `created_at`, `updated_at`, `deleted_at`, `warehouse`, `min_qty`, `store_min`) VALUES
(1, 1, NULL, NULL, 'Appetizers', 'Enjoy ultimate experience of the best food in town.', '[\"Eggroll\",\"Beef Dumplings\",\"Wonton\"]', '[\"14\",\"12\",\"16\"]', NULL, 0, 0, '2023-01-24 23:18:26', '2023-01-24 23:18:26', NULL, NULL, NULL, NULL),
(2, 2, NULL, NULL, 'Seafood', 'Enjoy ultimate experience of the best food in town.', '[\"Cucumber\",\"Seafood Platter\",\"Braised\",\"Lobster\"]', '[\"70\",\"60\",\"90\",\"120\"]', NULL, 0, 0, '2023-01-24 23:23:33', '2023-01-24 23:23:33', NULL, NULL, NULL, NULL),
(3, 3, NULL, NULL, 'Rice and Noodles', 'Enjoy ultimate experience of the best food in town.', '[\"Chow Mein\",\"Fried\",\"Vegetable\",\"Broccoli Rice\",\"Cauliflower Rice\"]', '[\"25\",\"35\",\"30\",\"25\",\"23\"]', NULL, 0, 0, '2023-01-24 23:25:58', '2023-01-25 23:14:50', NULL, NULL, NULL, NULL),
(4, 4, NULL, NULL, 'Soup', 'Enjoy ultimate experience of the best food in town.', '[\"Shrimp\",\"Hot Sour\",\"Yum Yum\",\"Lentil Soup\",\"Egg Noodle Soup\"]', '[\"10\",\"16\",\"16\",\"23\",\"22\"]', NULL, 0, 0, '2023-01-24 23:28:59', '2023-01-25 23:12:56', NULL, NULL, NULL, NULL),
(5, 5, NULL, NULL, 'Desserts', 'Enjoy ultimate experience of the best food in town.', '[\"Dumplings\",\"Pudding\",\"Avocado\",\"Custard\",\"Marshmallow\"]', '[\"20\",\"18\",\"22\",\"22\",\"12\"]', NULL, 0, 0, '2023-01-24 23:30:41', '2023-01-25 23:09:59', NULL, NULL, NULL, NULL),
(6, 6, NULL, NULL, 'Beverages', 'Enjoy ultimate experience of the best food in town.', '[\"Fountain\",\"Water\",\"Voss Water\",\"Soft Drink\",\"Fresh Water\"]', '[\"3\",\"2\",\"3\",\"2\",\"2\"]', NULL, 0, 0, '2023-01-24 23:32:28', '2023-01-25 23:10:29', NULL, NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `purchases`
--

CREATE TABLE `purchases` (
  `id` int(11) NOT NULL,
  `company_id` int(11) DEFAULT NULL,
  `bill_no` varchar(55) DEFAULT NULL,
  `supplier_id` int(11) DEFAULT NULL,
  `purchase_date` date DEFAULT NULL,
  `note` varchar(1000) DEFAULT NULL,
  `total_amount` decimal(25,2) DEFAULT NULL,
  `tax` decimal(25,2) DEFAULT NULL,
  `discount` decimal(25,2) DEFAULT NULL,
  `user` varchar(255) DEFAULT NULL,
  `updated_by` varchar(255) DEFAULT NULL,
  `paid` decimal(25,2) DEFAULT NULL,
  `paid_by` enum('cash','cheque') DEFAULT NULL,
  `cheque_no` varchar(20) DEFAULT NULL,
  `created_at` date DEFAULT NULL,
  `updated_at` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `purchase_items`
--

CREATE TABLE `purchase_items` (
  `id` int(11) NOT NULL,
  `purchase_id` int(11) DEFAULT NULL,
  `product_id` int(11) DEFAULT NULL,
  `quantity` int(11) DEFAULT NULL,
  `units` int(11) NOT NULL DEFAULT 1,
  `unit_price` double(10,2) DEFAULT NULL,
  `gross_total` double(10,2) DEFAULT NULL,
  `sold_price` double(10,2) DEFAULT NULL,
  `created_at` date DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `reservations`
--

CREATE TABLE `reservations` (
  `id` int(11) NOT NULL,
  `customer_id` int(11) DEFAULT NULL,
  `name` varchar(100) DEFAULT NULL,
  `phone` varchar(100) DEFAULT NULL,
  `guests` int(2) DEFAULT NULL,
  `booking_date` date DEFAULT NULL,
  `booking_time` time DEFAULT NULL,
  `comments` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `status` enum('Booked','Cancelled') NOT NULL DEFAULT 'Booked'
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- --------------------------------------------------------

--
-- Table structure for table `restaurants`
--

CREATE TABLE `restaurants` (
  `id` int(11) NOT NULL,
  `name` varchar(100) DEFAULT NULL,
  `address` varchar(255) DEFAULT NULL,
  `phone` varchar(255) DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `roles`
--

CREATE TABLE `roles` (
  `id` int(10) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL,
  `display_name` varchar(255) DEFAULT NULL,
  `description` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `roles`
--

INSERT INTO `roles` (`id`, `name`, `display_name`, `description`, `created_at`, `updated_at`) VALUES
(1, 'admin', 'Administrator', 'tis is isafds', NULL, '2023-01-22 09:42:46'),
(2, 'manager', 'Sales Manager', NULL, NULL, '2023-01-22 09:43:47'),
(3, 'sales_staff', 'Waitress', NULL, NULL, '2021-08-19 13:28:24');

-- --------------------------------------------------------

--
-- Table structure for table `role_user`
--

CREATE TABLE `role_user` (
  `user_id` int(10) UNSIGNED NOT NULL,
  `role_id` int(10) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `role_user`
--

INSERT INTO `role_user` (`user_id`, `role_id`) VALUES
(1, 1),
(5, 2),
(6, 2),
(25, 3),
(26, 3);

-- --------------------------------------------------------

--
-- Table structure for table `rooms`
--

CREATE TABLE `rooms` (
  `id` int(11) NOT NULL,
  `name` varchar(100) DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

--
-- Dumping data for table `rooms`
--

INSERT INTO `rooms` (`id`, `name`, `created_at`, `updated_at`) VALUES
(1, 'Room 1', NULL, '2021-08-24 17:52:13'),
(2, 'Room 2', NULL, NULL),
(3, 'Room 3', NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `sales`
--

CREATE TABLE `sales` (
  `id` int(10) UNSIGNED NOT NULL,
  `customer_id` int(11) DEFAULT 0,
  `company_id` int(11) DEFAULT NULL,
  `cashier_id` int(11) DEFAULT NULL,
  `comments` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `status` tinyint(1) DEFAULT 1 COMMENT '1:completed, 0 canceled',
  `amount` double(10,2) NOT NULL DEFAULT 0.00,
  `discount` double(10,2) DEFAULT 0.00,
  `vat` double(10,2) DEFAULT 0.00,
  `gratuity` double(10,2) NOT NULL,
  `delivery_cost` double(10,2) DEFAULT 0.00,
  `name` varchar(100) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `address` varchar(255) DEFAULT NULL,
  `comment` varchar(500) DEFAULT NULL,
  `delivery_time` int(11) DEFAULT NULL,
  `type` varchar(20) DEFAULT 'pos',
  `payment_with` enum('card','cash','paypal') DEFAULT 'cash',
  `total_given` double(10,2) DEFAULT NULL,
  `change` double(10,2) DEFAULT NULL,
  `room_id` int(11) DEFAULT NULL,
  `table_id` int(11) DEFAULT NULL,
  `payment_success` varchar(11) NOT NULL DEFAULT 'No'
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

--
-- Dumping data for table `sales`
--

INSERT INTO `sales` (`id`, `customer_id`, `company_id`, `cashier_id`, `comments`, `created_at`, `updated_at`, `status`, `amount`, `discount`, `vat`, `gratuity`, `delivery_cost`, `name`, `email`, `phone`, `address`, `comment`, `delivery_time`, `type`, `payment_with`, `total_given`, `change`, `room_id`, `table_id`, `payment_success`) VALUES
(1, 0, NULL, NULL, NULL, '2023-01-25 13:11:14', '2023-01-25 13:11:14', 2, 9.80, 0.00, 0.80, 0.00, 1.00, 'Muhammad Arfan', 'muhammad.arfan@hostingshouse.com', '+923005095213', '171، Johar Town Rd, Block B 1 Phase 1 Johar Town', NULL, NULL, 'order', NULL, NULL, NULL, NULL, NULL, 'No'),
(2, NULL, NULL, 1, 'testing', '2023-01-25 22:29:40', '2023-01-25 22:29:40', 1, 104.50, 0.00, 9.50, 0.00, 0.00, NULL, NULL, NULL, NULL, NULL, NULL, 'pos', 'cash', 110.00, 5.50, NULL, NULL, 'No'),
(3, NULL, NULL, 1, NULL, '2023-01-25 22:34:29', '2023-01-25 22:34:29', 1, 178.20, 0.00, 16.20, 0.00, 0.00, NULL, NULL, NULL, NULL, NULL, NULL, 'pos', 'cash', 200.00, 21.80, NULL, NULL, 'No');

-- --------------------------------------------------------

--
-- Table structure for table `sale_items`
--

CREATE TABLE `sale_items` (
  `id` int(10) UNSIGNED NOT NULL,
  `company_id` int(11) DEFAULT NULL,
  `sale_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `price` decimal(8,2) NOT NULL,
  `quantity` int(11) NOT NULL,
  `units` int(11) DEFAULT 1,
  `p_qty` int(11) NOT NULL DEFAULT 0,
  `size` varchar(20) DEFAULT NULL,
  `note` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

--
-- Dumping data for table `sale_items`
--

INSERT INTO `sale_items` (`id`, `company_id`, `sale_id`, `product_id`, `price`, `quantity`, `units`, `p_qty`, `size`, `note`, `created_at`, `updated_at`) VALUES
(1, NULL, 1, 6, '3.00', 1, 1, 0, 'Fountain Beverages', NULL, '2023-01-25 13:11:14', '2023-01-25 13:11:14'),
(2, NULL, 1, 6, '3.00', 1, 1, 0, 'Voss Still Water', NULL, '2023-01-25 13:11:14', '2023-01-25 13:11:14'),
(3, NULL, 1, 6, '2.00', 1, 1, 0, 'Bottled Water', NULL, '2023-01-25 13:11:14', '2023-01-25 13:11:14'),
(4, NULL, 2, 3, '35.00', 1, 1, 0, 'Fried', NULL, '2023-01-25 22:29:40', '2023-01-25 22:29:40'),
(5, NULL, 2, 2, '60.00', 1, 1, 0, 'Seafood Platter', NULL, '2023-01-25 22:29:40', '2023-01-25 22:29:40'),
(6, NULL, 3, 2, '120.00', 1, 1, 0, 'Lobster', NULL, '2023-01-25 22:34:29', '2023-01-25 22:34:29'),
(7, NULL, 3, 3, '30.00', 1, 1, 0, 'Vegetable', NULL, '2023-01-25 22:34:29', '2023-01-25 22:34:29'),
(8, NULL, 3, 1, '12.00', 1, 1, 0, 'Beef Dumplings', NULL, '2023-01-25 22:34:29', '2023-01-25 22:34:29');

-- --------------------------------------------------------

--
-- Table structure for table `services`
--

CREATE TABLE `services` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `price` int(10) DEFAULT 0,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL,
  `filename` varchar(255) DEFAULT NULL,
  `order_by` int(11) NOT NULL DEFAULT 100
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

--
-- Dumping data for table `services`
--

INSERT INTO `services` (`id`, `name`, `description`, `price`, `created_at`, `updated_at`, `filename`, `order_by`) VALUES
(1, 'Service  1', 'test fdsf sdf sdf dsf', 12, '2018-10-20 21:02:13', '2019-01-31 20:48:45', 'service_1.jpg', 100),
(2, 'hels fsdf', 'SIL is the largest footwear exporter of the country for the last 10 years and export its products in many destinations around the world!', 45, '2018-12-08 06:48:16', '2018-12-08 01:51:40', 'service_2.jpg', 100),
(3, 'Shoe Box', 'SIL is the largest footwear exporter of the country for the last 10 years and export its products in many destinations around the world!', 45, '2018-12-08 06:48:37', '2018-12-08 01:51:25', 'service_3.jpg', 100),
(4, 'Service Tyre', 'SIL is the largest footwear exporter of the country for the last 10 years and export its products in many destinations around the world!', 45, '2018-12-08 06:48:53', '2018-12-08 01:51:11', 'service_4.jpg', 100),
(5, 'Service Export', 'SIL is the largest footwear exporter of the country for the last 10 years and export its products in many destinations around the world!', 53, '2018-12-08 06:49:17', '2018-12-08 01:50:40', 'service_5.jpg', 100),
(6, 'Testing Service', 'this is a testservice', 0, '2019-02-01 06:36:23', '2019-01-31 20:36:24', 'service_6.jpg', 100),
(7, 'Slider 2', 'fsdfsd', 0, '2019-02-01 06:36:52', '2019-01-31 20:36:52', 'service_7.jpg', 100),
(8, 'fds', 'fsd', 0, '2019-02-01 06:37:18', NULL, NULL, 100),
(9, 'fds', 'fsd', 0, '2019-02-01 06:38:05', NULL, NULL, 100);

-- --------------------------------------------------------

--
-- Table structure for table `settings`
--

CREATE TABLE `settings` (
  `id` int(10) UNSIGNED NOT NULL,
  `company_id` int(11) DEFAULT NULL,
  `key` varchar(255) NOT NULL,
  `label` varchar(255) NOT NULL,
  `value` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

--
-- Dumping data for table `settings`
--

INSERT INTO `settings` (`id`, `company_id`, `key`, `label`, `value`, `created_at`, `updated_at`) VALUES
(1, 0, 'title', 'Site Title', 'Cannabest', NULL, '2023-01-25 22:32:14'),
(2, 0, 'phone', 'Phone', '5038675309', NULL, '2023-01-25 22:32:14'),
(3, 0, 'email', 'Email', 'wholesale@staff.com', NULL, '2017-09-06 22:08:34'),
(4, 0, 'address', 'Address', '3rd Floor Street 6 Gali 5', NULL, '2017-08-16 03:53:13'),
(5, 0, 'country', 'Country', 'USA', NULL, '2017-08-16 03:53:13'),
(6, 0, 'timing1', 'Monday To Saturday', '12PM to 12AM', NULL, '2017-09-18 18:19:33'),
(7, 0, 'sunday', 'Sunday', 'Closed', NULL, '2017-09-18 18:19:34'),
(8, 0, 'facebook', 'Facebook', 'https://www.facebook.com/cent040', NULL, '2017-10-03 15:35:48'),
(9, 0, 'twitter', 'Twitter', 'https://www.twitter.com/cent040', NULL, '2017-10-03 15:35:48'),
(10, 0, 'vat', 'VAT', '10', NULL, '2017-10-03 16:50:12'),
(11, 0, 'delivery_cost', 'Delivery Cost', '1', NULL, '2017-10-03 15:35:48'),
(12, 0, 'currency', 'Currency', '$', NULL, '2017-10-03 17:00:43'),
(13, 0, 'lng', 'Longitude', '43.8041', NULL, NULL),
(14, 0, 'lat', 'Latitude', '120.5542', NULL, NULL),
(16, 0, 'frontend', 'Hide Frontend', 'no', NULL, '2017-11-25 06:26:00'),
(19, 0, 'staff_allow_sales', 'Sales Staff to Complete Sales', 'yes', NULL, NULL),
(37, 0, 'footer_text', 'Footer Text', '<h5></h5><p>', NULL, '2021-08-19 11:42:48');



INSERT INTO `wholesale_settings` (`id`, `company_id`, `key`, `label`, `value`, `created_at`, `updated_at`) VALUES
(1, 0, 'title', 'Site Title', 'Wholesale', NULL, '2023-01-25 22:32:14'),
(2, 0, 'phone', 'Phone', '5038675309', NULL, '2023-01-25 22:32:14'),
(3, 0, 'email', 'Email', 'wholesale@staff.com', NULL, '2017-09-06 22:08:34'),
(4, 0, 'address', 'Address', '3rd Floor Street 6 Gali 5', NULL, '2017-08-16 03:53:13'),
(5, 0, 'country', 'Country', 'USA', NULL, '2017-08-16 03:53:13'),

(8, 0, 'facebook', 'Facebook', 'https://www.facebook.com/cent040', NULL, '2017-10-03 15:35:48'),
(9, 0, 'twitter', 'Twitter', 'https://www.twitter.com/cent040', NULL, '2017-10-03 15:35:48'),

(11, 0, 'delivery_cost', 'Delivery Cost', '1', NULL, '2017-10-03 15:35:48'),
(12, 0, 'currency', 'Currency', '$', NULL, '2017-10-03 17:00:43'),
(13, 0, 'lng', 'Longitude', '43.8041', NULL, NULL),
(14, 0, 'lat', 'Latitude', '120.5542', NULL, NULL),
(16, 0, 'frontend', 'Hide Frontend', 'no', NULL, '2017-11-25 06:26:00'),
(19, 0, 'staff_allow_sales', 'Sales Staff to Complete Sales', 'yes', NULL, NULL),
(37, 0, 'footer_text', 'Footer Text', '<h5></h5><p>', NULL, '2021-08-19 11:42:48');

-- --------------------------------------------------------

--
-- Table structure for table `sliders`
--

CREATE TABLE `sliders` (
  `id` int(11) NOT NULL,
  `restaurant_id` int(11) DEFAULT NULL,
  `title` varchar(255) NOT NULL,
  `image` varchar(500) NOT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

--
-- Dumping data for table `sliders`
--

INSERT INTO `sliders` (`id`, `restaurant_id`, `title`, `image`, `created_at`, `updated_at`) VALUES
(6, NULL, 'Mouth Watering', '947370.jpg', NULL, NULL),
(7, NULL, 'Food For You', '171155.jpg', NULL, NULL),
(8, NULL, 'Enjoy the Taste', '953088.jpg', NULL, NULL),
(9, NULL, 'Eat Healthy', '856611.jpg', NULL, NULL),
(10, NULL, 'Nicely Cooked', '621866.jpg', NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `suppliers`
--

CREATE TABLE `suppliers` (
  `id` int(10) UNSIGNED NOT NULL,
  `company_id` int(11) DEFAULT NULL,
  `name` varchar(255) NOT NULL,
  `company_name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `phone` varchar(255) NOT NULL,
  `address` text NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

--
-- Dumping data for table `suppliers`
--

INSERT INTO `suppliers` (`id`, `company_id`, `name`, `company_name`, `email`, `phone`, `address`, `created_at`, `updated_at`, `deleted_at`) VALUES
(1, 1, 'test', 'Test', 'test', 'test', 'test', '2017-08-11 06:00:36', '2017-08-11 06:00:36', NULL),
(2, 1, 'Muhammad Arfan', 'Software Company', 'arfan67@gmail.com', '3005095213', '3rd Floor 86 Cavalry Ground', '2018-08-23 15:15:07', '2018-08-23 15:15:07', NULL),
(4, 2, 'Arfan Ali', 'Purelife.pk', 'arfan670@gmail.com', '5555555', 'Shop 471 Gopal Nagar Gulberg 3 Lahore\r\nShop 471 Gopal Nagar Gulberg 3 Lahore', '2021-02-05 05:45:59', '2021-02-05 05:45:59', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `tables`
--

CREATE TABLE `tables` (
  `id` int(11) NOT NULL,
  `room_id` int(11) DEFAULT NULL,
  `company_id` int(11) DEFAULT NULL,
  `table_name` varchar(200) DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(10) UNSIGNED NOT NULL,
  `company_id` int(11) DEFAULT NULL,
  `name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `facebook_id` varchar(55) DEFAULT NULL,
  `password` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `address` varchar(255) DEFAULT NULL,
  `city` varchar(100) DEFAULT NULL,
  `state` varchar(100) DEFAULT NULL,
  `zip` varchar(10) DEFAULT NULL,
  `role_id` int(11) NOT NULL DEFAULT 0,
  `remember_token` varchar(100) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `company_id`, `name`, `email`, `facebook_id`, `password`, `phone`, `address`, `city`, `state`, `zip`, `role_id`, `remember_token`, `created_at`, `updated_at`) VALUES
(1, 0, 'Super Admin', 'admin@example.com', NULL, '$2y$10$NDJ8GvTAdoJ/uG0AQ2Y.9ucXwjy75NVf.VgFnSZDSakRRvrEyAlMq', NULL, NULL, NULL, NULL, NULL, 1, 'rDWTyMV7OybRAqR2W5jCSb3a6O78yOfWbzLz1dspV6qlNoiv3IcXi9QBSxwp', NULL, NULL),
(5, 1, 'Admin', 'admin@admin.com', NULL, '$2y$10$NDJ8GvTAdoJ/uG0AQ2Y.9ucXwjy75NVf.VgFnSZDSakRRvrEyAlMq', NULL, NULL, NULL, NULL, NULL, 2, 'GXMRWhuf3sqeoVNTPnyEnLEhBsyP3tURLefbUIcwBRvlTDRTBMZdxuUbUl4T', NULL, NULL),
(6, 1, 'Sale Manger', 'sales@manager.com', NULL, '$2y$10$NDJ8GvTAdoJ/uG0AQ2Y.9ucXwjy75NVf.VgFnSZDSakRRvrEyAlMq', NULL, NULL, NULL, NULL, NULL, 2, 'qpPtzImTYQNY8ysYEjUztZn01zjsW4Qda73C68FO7QZ3qhDPDtwb9MdD9Jvw', NULL, NULL),
(25, 2, 'Brnach 2 Manager', 'branch2@admin.com', NULL, '$2y$10$NDJ8GvTAdoJ/uG0AQ2Y.9ucXwjy75NVf.VgFnSZDSakRRvrEyAlMq', NULL, NULL, NULL, NULL, NULL, 3, 'AruJiY7PZpVAhORzCYnA9paAsVJzaPqvycgUN2mdy4GBlwQPUymXdi1S73Zn', '2021-02-05 05:33:08', '2021-08-19 13:09:24'),
(26, NULL, 'Waitress 1', 'waitress@staff.com', NULL, '$2y$10$6CwU.Mz9EPVk2ZLLezog5O8xUCNY0VBCDgAsC1u98pRXPLOW0iMY6', NULL, NULL, NULL, NULL, NULL, 3, NULL, '2021-08-20 12:08:00', '2021-08-20 12:08:00');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `activities`
--
ALTER TABLE `activities`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `adjustments`
--
ALTER TABLE `adjustments`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `adjustment_items`
--
ALTER TABLE `adjustment_items`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `banners`
--
ALTER TABLE `banners`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `bookings`
--
ALTER TABLE `bookings`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `booking_types`
--
ALTER TABLE `booking_types`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `categories`
--
ALTER TABLE `categories`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `companies`
--
ALTER TABLE `companies`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `customers`
--
ALTER TABLE `customers`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `customers_email_unique` (`email`);

--
-- Indexes for table `customer_invoices`
--
ALTER TABLE `customer_invoices`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `customer_invoice_items`
--
ALTER TABLE `customer_invoice_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `sale_id` (`invoice_id`),
  ADD KEY `product_id` (`product_id`);

--
-- Indexes for table `email_templates`
--
ALTER TABLE `email_templates`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `expenses`
--
ALTER TABLE `expenses`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `hold_order`
--
ALTER TABLE `hold_order`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `homepage`
--
ALTER TABLE `homepage`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `settings_key_unique` (`key`);

--
-- Indexes for table `inventories`
--
ALTER TABLE `inventories`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `ltm_translations`
--
ALTER TABLE `ltm_translations`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `menus`
--
ALTER TABLE `menus`
  ADD PRIMARY KEY (`menu_id`);

--
-- Indexes for table `migrations`
--
ALTER TABLE `migrations`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `newsletters`
--
ALTER TABLE `newsletters`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `pages`
--
ALTER TABLE `pages`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `password_resets`
--
ALTER TABLE `password_resets`
  ADD KEY `password_resets_email_index` (`email`),
  ADD KEY `password_resets_token_index` (`token`);

--
-- Indexes for table `payments`
--
ALTER TABLE `payments`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `permissions`
--
ALTER TABLE `permissions`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `permission_role`
--
ALTER TABLE `permission_role`
  ADD PRIMARY KEY (`permission_id`,`role_id`),
  ADD KEY `permission_role_role_id_foreign` (`role_id`);

--
-- Indexes for table `printers`
--
ALTER TABLE `printers`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `products`
--
ALTER TABLE `products`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `purchases`
--
ALTER TABLE `purchases`
  ADD PRIMARY KEY (`id`),
  ADD KEY `id` (`id`);

--
-- Indexes for table `purchase_items`
--
ALTER TABLE `purchase_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `sale_id` (`purchase_id`),
  ADD KEY `product_id` (`product_id`);

--
-- Indexes for table `reservations`
--
ALTER TABLE `reservations`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `restaurants`
--
ALTER TABLE `restaurants`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `roles`
--
ALTER TABLE `roles`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `role_user`
--
ALTER TABLE `role_user`
  ADD PRIMARY KEY (`user_id`,`role_id`),
  ADD KEY `role_user_role_id_foreign` (`role_id`);

--
-- Indexes for table `rooms`
--
ALTER TABLE `rooms`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `sales`
--
ALTER TABLE `sales`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `sale_items`
--
ALTER TABLE `sale_items`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `services`
--
ALTER TABLE `services`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `settings`
--
ALTER TABLE `settings`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `sliders`
--
ALTER TABLE `sliders`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `suppliers`
--
ALTER TABLE `suppliers`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `suppliers_email_unique` (`email`);

--
-- Indexes for table `tables`
--
ALTER TABLE `tables`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `users_email_unique` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `activities`
--
ALTER TABLE `activities`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=142;

--
-- AUTO_INCREMENT for table `adjustments`
--
ALTER TABLE `adjustments`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `adjustment_items`
--
ALTER TABLE `adjustment_items`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `banners`
--
ALTER TABLE `banners`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `bookings`
--
ALTER TABLE `bookings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT for table `booking_types`
--
ALTER TABLE `booking_types`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `categories`
--
ALTER TABLE `categories`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `companies`
--
ALTER TABLE `companies`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `customers`
--
ALTER TABLE `customers`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `customer_invoices`
--
ALTER TABLE `customer_invoices`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `customer_invoice_items`
--
ALTER TABLE `customer_invoice_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `email_templates`
--
ALTER TABLE `email_templates`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `expenses`
--
ALTER TABLE `expenses`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `hold_order`
--
ALTER TABLE `hold_order`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=45;

--
-- AUTO_INCREMENT for table `homepage`
--
ALTER TABLE `homepage`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `inventories`
--
ALTER TABLE `inventories`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=24;

--
-- AUTO_INCREMENT for table `ltm_translations`
--
ALTER TABLE `ltm_translations`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `menus`
--
ALTER TABLE `menus`
  MODIFY `menu_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT for table `migrations`
--
ALTER TABLE `migrations`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=71;

--
-- AUTO_INCREMENT for table `newsletters`
--
ALTER TABLE `newsletters`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `pages`
--
ALTER TABLE `pages`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `payments`
--
ALTER TABLE `payments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `permissions`
--
ALTER TABLE `permissions`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=36;

--
-- AUTO_INCREMENT for table `printers`
--
ALTER TABLE `printers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `products`
--
ALTER TABLE `products`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `purchases`
--
ALTER TABLE `purchases`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `purchase_items`
--
ALTER TABLE `purchase_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `reservations`
--
ALTER TABLE `reservations`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `restaurants`
--
ALTER TABLE `restaurants`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `roles`
--
ALTER TABLE `roles`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `rooms`
--
ALTER TABLE `rooms`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `sales`
--
ALTER TABLE `sales`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `sale_items`
--
ALTER TABLE `sale_items`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `services`
--
ALTER TABLE `services`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `settings`
--
ALTER TABLE `settings`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=38;

--
-- AUTO_INCREMENT for table `sliders`
--
ALTER TABLE `sliders`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `suppliers`
--
ALTER TABLE `suppliers`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `tables`
--
ALTER TABLE `tables`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=27;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `permission_role`
--
ALTER TABLE `permission_role`
  ADD CONSTRAINT `permission_role_ibfk_1` FOREIGN KEY (`permission_id`) REFERENCES `permissions` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `permission_role_ibfk_2` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `role_user`
--
ALTER TABLE `role_user`
  ADD CONSTRAINT `role_user_ibfk_1` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `role_user_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
