-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jun 03, 2026 at 01:23 PM
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
-- Database: `tourist`
--

-- --------------------------------------------------------

--
-- Table structure for table `arrangements`
--

CREATE TABLE `arrangements` (
  `id` int(11) NOT NULL,
  `tourist_id` int(11) NOT NULL,
  `hotel_id` int(11) DEFAULT NULL,
  `check_in` datetime DEFAULT NULL,
  `check_out` datetime DEFAULT NULL,
  `cab_no` varchar(50) DEFAULT NULL,
  `driver_name` varchar(100) DEFAULT NULL,
  `driver_contact` varchar(20) DEFAULT NULL,
  `pickup_time` datetime DEFAULT NULL,
  `cab_voucher` varchar(255) DEFAULT NULL,
  `room_no` varchar(50) DEFAULT NULL,
  `hotel_voucher` varchar(255) DEFAULT NULL,
  `assigned_by` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `flight_details` varchar(255) DEFAULT NULL,
  `flight_ticket` varchar(255) DEFAULT NULL,
  `reach_time` datetime DEFAULT NULL,
  `arrival_date` date DEFAULT NULL,
  `arrival_time` time DEFAULT NULL,
  `pnr_number` varchar(50) DEFAULT NULL,
  `return_flight_details` varchar(255) DEFAULT NULL,
  `return_flight_ticket` varchar(255) DEFAULT NULL,
  `return_pnr_number` varchar(50) DEFAULT NULL,
  `return_date` date DEFAULT NULL,
  `return_time` time DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `cabs`
--

CREATE TABLE `cabs` (
  `id` int(11) NOT NULL,
  `provider_name` varchar(100) NOT NULL,
  `location` int(11) DEFAULT NULL,
  `vehicle_type` varchar(50) DEFAULT NULL,
  `vehicle_number` varchar(20) DEFAULT NULL,
  `contact_number` varchar(20) DEFAULT NULL,
  `driver_name` varchar(100) DEFAULT NULL,
  `driver_contact` varchar(20) DEFAULT NULL,
  `capacity` int(11) DEFAULT 4,
  `status` enum('available','booked','maintenance') DEFAULT 'available',
  `added_by` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `cabs`
--

INSERT INTO `cabs` (`id`, `provider_name`, `location`, `vehicle_type`, `vehicle_number`, `contact_number`, `driver_name`, `driver_contact`, `capacity`, `status`, `added_by`) VALUES
(1, 'surya', 1, 'suv', NULL, '389238928', NULL, NULL, 4, 'available', 1),
(2, 'FastTrack', 1, 'SUV', 'TN 07 B 1234', '9876543210', 'Ramesh', '9876543211', 7, 'available', 1),
(3, 'City Cabs', 1, 'Sedan', 'TN 01 A 5678', '9884012345', 'Kumar', '9884012346', 4, 'available', 1),
(4, 'Ooty Travels', 1, 'Tempo', 'TN 43 C 9012', '9443054321', 'Selvam', '9443054322', 12, 'available', 1),
(5, 'Blue Hills', 1, 'SUV', 'TN 43 D 1122', '9845098765', 'Mani', '9845098766', 7, 'booked', 1),
(6, 'Sky Cabs', 1, 'Hatchback', 'TN 05 E 3344', '9123456789', 'Prabhu', '9123456780', 4, 'available', 1),
(7, 'Green Valley', 3, 'Sedan', 'TN 43 F 5566', '9332211009', 'Vijay', '9332211010', 4, 'available', 1),
(8, 'Hill Station Tours', 5, 'SUV', 'TN 43 G 7788', '9556677889', 'Arul', '9556677880', 8, 'available', 1),
(9, 'Safe Ride', 1, 'Sedan', 'TN 01 H 9900', '9778899001', 'David', '9778899002', 4, 'available', 1),
(10, 'Royal Cabs', 4, 'Premium', 'TN 07 J 2211', '9990011223', 'Sathish', '9990011224', 5, 'booked', 1),
(11, 'Budget Travels', 2, 'Mini', 'TN 43 K 4455', '9221133445', 'Ganesh', '9221133446', 4, 'available', 1),
(12, 'surya', 4, 'suv', 'TN 43 F 5566', '9990011223', 'Sathish', '9332211010', 4, 'available', 1);

-- --------------------------------------------------------

--
-- Table structure for table `food_arrangements`
--

CREATE TABLE `food_arrangements` (
  `id` int(11) NOT NULL,
  `tourist_id` int(11) NOT NULL,
  `day_number` int(11) NOT NULL,
  `breakfast` varchar(255) DEFAULT NULL,
  `lunch` varchar(255) DEFAULT NULL,
  `dinner` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `hotels`
--

CREATE TABLE `hotels` (
  `id` int(11) NOT NULL,
  `hotel_name` varchar(100) NOT NULL,
  `location` int(11) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `contact_number` varchar(20) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `star_rating` int(11) DEFAULT 3,
  `status` enum('active','inactive') DEFAULT 'active',
  `description` text DEFAULT NULL,
  `added_by` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `hotels`
--

INSERT INTO `hotels` (`id`, `hotel_name`, `location`, `address`, `contact_number`, `email`, `star_rating`, `status`, `description`, `added_by`) VALUES
(1, 'Hotel Lakeview', 1, '123 Lake Road, Ooty', '0423-2444444', 'lakeview@example.com', 3, 'active', 'Beautiful lake view.', 1),
(2, 'Savoy IHCL SeleQtions', 1, 'Sylks Road, Ooty', '0423-2225500', 'savoy@ihcl.com', 5, 'active', 'Heritage luxury stay.', 1),
(3, 'Sterling Ooty Fern Hill', 1, 'Fern Hill, Ooty', '0423-2441073', 'fernhill@sterling.com', 4, 'active', 'Resort with valley view.', 1),
(4, 'Gateway Coonoor', 2, 'Upper Coonoor', '0423-2230021', 'gateway@ihcl.com', 4, 'active', 'Colonial style stay.', 1),
(5, 'Hotel Gem Park', 1, 'Sheddon Road, Ooty', '0423-2441761', 'gempark@example.com', 4, 'active', 'Modern amenities and great food.', 1),
(6, 'Accord Highland', 1, 'Doddabetta Road', '0423-2450021', 'accord@example.com', 4, 'active', 'High altitude stay with clouds.', 1),
(7, 'Sinclairs Retreat', 1, 'Gorishola Road', '0423-2441376', 'sinclairs@example.com', 3, 'active', 'Quiet retreat away from city.', 1),
(8, 'Kurumba Village Resort', 2, 'Hill Grove', '0423-2230284', 'kurumba@example.com', 4, 'inactive', 'Tribal theme resort in nature.', 1),
(9, 'Sherlock Hotel', 1, 'Tiger Hill Road', '0423-2444011', 'sherlock@example.com', 3, 'active', 'Victorian style theme hotel.', 1),
(10, 'Pine View Residency', 1, 'Near Rose Garden', '0423-2441122', 'pineview@example.com', 2, 'active', 'Budget friendly stay near park.', 1);

-- --------------------------------------------------------

--
-- Table structure for table `itineraries`
--

CREATE TABLE `itineraries` (
  `id` int(11) NOT NULL,
  `tourist_id` int(11) NOT NULL,
  `day_number` int(11) DEFAULT 1,
  `start_time` time DEFAULT NULL,
  `end_time` time DEFAULT NULL,
  `place_name` varchar(255) DEFAULT NULL,
  `activity` text DEFAULT NULL,
  `assigned_by` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `itineraries`
--

INSERT INTO `itineraries` (`id`, `tourist_id`, `day_number`, `start_time`, `end_time`, `place_name`, `activity`, `assigned_by`, `created_at`) VALUES
(8, 23, 1, '10:30:00', '11:25:00', 'marina beach', 'photoshoot', 2, '2026-06-01 03:55:42'),
(9, 27, 1, '18:31:00', '22:31:00', 'marina beach', 'photoshoot', 2, '2026-06-03 07:02:06');

-- --------------------------------------------------------

--
-- Table structure for table `locations`
--

CREATE TABLE `locations` (
  `id` int(11) NOT NULL,
  `location_name` varchar(100) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `locations`
--

INSERT INTO `locations` (`id`, `location_name`, `created_at`) VALUES
(1, 'Ooty', '2026-05-18 09:06:46'),
(2, 'Coonoor', '2026-05-18 09:06:46'),
(4, 'Chennai', '2026-05-18 09:08:40'),
(5, 'Kerala', '2026-05-18 09:08:46');

-- --------------------------------------------------------

--
-- Table structure for table `mail_settings`
--

CREATE TABLE `mail_settings` (
  `id` int(11) NOT NULL,
  `smtp_host` varchar(255) NOT NULL,
  `smtp_user` varchar(255) NOT NULL,
  `smtp_pass` varchar(255) NOT NULL,
  `smtp_port` int(11) NOT NULL,
  `smtp_secure` varchar(10) NOT NULL,
  `from_email` varchar(255) NOT NULL,
  `from_name` varchar(255) NOT NULL,
  `global_footer` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `mail_settings`
--

INSERT INTO `mail_settings` (`id`, `smtp_host`, `smtp_user`, `smtp_pass`, `smtp_port`, `smtp_secure`, `from_email`, `from_name`, `global_footer`) VALUES
(1, 'smtp.gmail.com', 'suryapanneer04@gmail.com', 'xlwv dltg vhou bars', 587, 'TLS', 'suryapanneer04@gmail.com', 'Tourist Portal', 'Regards, Team Tourist Portal');

-- --------------------------------------------------------

--
-- Table structure for table `mail_templates`
--

CREATE TABLE `mail_templates` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `template_key` varchar(50) NOT NULL,
  `subject` varchar(255) NOT NULL,
  `body` text NOT NULL,
  `placeholders` varchar(255) DEFAULT NULL,
  `status` enum('active','inactive') DEFAULT 'active'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `mail_templates`
--

INSERT INTO `mail_templates` (`id`, `name`, `template_key`, `subject`, `body`, `placeholders`, `status`) VALUES
(1, 'User Account Credentials', 'user_credentials', 'Your Tourist Account Credentials', '<h2>Hello {{full_name}}!</h2><p>Your user account email is: <strong>{{email}}</strong> and password is: <strong>{{password}}</strong></p><p>Please log in and update your profile.</p>', '{{full_name}},{{email}},{{password}}', 'active'),
(3, 'Welcome Email', 'welcome_email', 'Welcome to Our Tourist Service - Action Required', '<h2>Hello {{name}}!</h2><p>Welcome to our service. Please verify your email to complete your registration and provide arrival details.</p><p><a href=\"{{verify_link}}\" style=\"background:#10b981; color:white; padding:10px 20px; text-decoration:none; border-radius:5px;\">Verify Email & Provide Details</a></p><p>If the button doesn\'t work, copy this link: {{verify_link}}</p>', '{{name}},{{verify_link}}', 'active'),
(4, 'OTP Verification', 'otp_verification', 'Your OTP for Tourist Verification', '<h2>OTP Verification</h2><p>Hello {{name}},</p><p>Your OTP for verifying your email is: <strong>{{otp}}</strong></p><p>Enter this OTP on the verification page to continue.</p>', '{{name}},{{otp}}', 'active'),
(5, 'Schedule Ready', 'schedule_ready', 'Your Trip Schedule is Ready', '<h2>Hello {{name}}!</h2><p>Your trip schedule has been finalized. We have arranged your accommodations, transportation, and itinerary.</p><p><a href=\"{{schedule_link}}\" style=\"background:#10b981; color:white; padding:10px 20px; display:inline-block; text-decoration:none; border-radius:5px;\">View Your Schedule</a></p><br><p>If the button doesn\'t work, copy this link: {{schedule_link}}</p>', '{{name}},{{schedule_link}}', 'active');

-- --------------------------------------------------------

--
-- Table structure for table `theme_settings`
--

CREATE TABLE `theme_settings` (
  `id` int(11) NOT NULL,
  `sidebar_bg_color` varchar(20) NOT NULL DEFAULT '#0f172a'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `theme_settings`
--

INSERT INTO `theme_settings` (`id`, `sidebar_bg_color`) VALUES
(1, '#000000');

-- --------------------------------------------------------

--
-- Table structure for table `tourist_companions`
--

CREATE TABLE `tourist_companions` (
  `id` int(11) NOT NULL,
  `tourist_id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `age` int(11) DEFAULT NULL,
  `passport_number` varchar(100) DEFAULT NULL,
  `passport_validation` date DEFAULT NULL,
  `passport_scan` varchar(255) DEFAULT NULL,
  `signature` varchar(255) DEFAULT NULL,
  `visa_scan` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tourist_companions`
--

INSERT INTO `tourist_companions` (`id`, `tourist_id`, `name`, `age`, `passport_number`, `passport_validation`, `passport_scan`, `signature`, `visa_scan`) VALUES
(2, 28, 'priya', 36, '638273821', '2029-06-20', 'uploads/1780482320_c0_download.jpg', 'uploads/1780482320_c0_download.jpg', 'uploads/1780482320_c0_download.jpg');

-- --------------------------------------------------------

--
-- Table structure for table `tourist_entries`
--

CREATE TABLE `tourist_entries` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `age` int(11) DEFAULT NULL,
  `passport_number` varchar(100) DEFAULT NULL,
  `passport_validation` date DEFAULT NULL,
  `purpose_of_travel` varchar(255) DEFAULT NULL,
  `travel_start_date` date DEFAULT NULL,
  `travel_end_date` date DEFAULT NULL,
  `stay_days` int(11) DEFAULT 1,
  `visa_type` varchar(150) NOT NULL,
  `travel_country` varchar(100) DEFAULT NULL,
  `employee_id` int(11) DEFAULT NULL,
  `token` varchar(100) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `passport_scan` varchar(255) DEFAULT NULL,
  `signature` varchar(255) DEFAULT NULL,
  `visa_scan` varchar(255) DEFAULT NULL,
  `status` enum('Pending','Processing','Completed') DEFAULT 'Pending'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tourist_entries`
--

INSERT INTO `tourist_entries` (`id`, `name`, `email`, `age`, `passport_number`, `passport_validation`, `purpose_of_travel`, `travel_start_date`, `travel_end_date`, `stay_days`, `visa_type`, `travel_country`, `employee_id`, `token`, `created_at`, `passport_scan`, `signature`, `visa_scan`, `status`) VALUES
(28, 'surya', 'suryapanneer04@gmail.com', 21, '7393731273', '2029-06-03', 'Tourism', '2026-06-10', '2026-06-12', 2, 'Multiple Entry Visa', 'India', 2, 'efa87c1a75b693062761b4430d32729d', '2026-06-03 10:25:20', 'uploads/1780482320_download.jpg', 'uploads/1780482320_download.jpg', 'uploads/1780482320_download.jpg', 'Pending');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('admin','employee') NOT NULL,
  `full_name` varchar(100) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `email` varchar(150) DEFAULT NULL,
  `number` varchar(20) DEFAULT NULL,
  `department` varchar(150) DEFAULT NULL,
  `country` varchar(150) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `password`, `role`, `full_name`, `created_at`, `email`, `number`, `department`, `country`) VALUES
(1, 'admin', '$2y$10$/rlfsCMr1mfsBU3QzP8p4.R/.ccwtYmI0HHOT0TkgKOrxNPE/y41q', 'admin', 'Super Admin', '2026-05-15 10:23:44', 'admin@gmail.com', NULL, 'Management', NULL),
(2, 'india', '$2y$10$T1mZJmKraZ6FmqjP4Q8F..e.4gI9srhx11/hUI0rCM7x6D8ZLpp9O', 'employee', 'India HR', '2026-05-15 10:23:44', 'suryapanneer04@gmail.com', NULL, 'HR', 'India'),
(7, 'japanHR', '$2y$10$QyWPZqpPNYw1PKc4HTgPZOr0vH0GOPxy9x21kTpDoDmEQvHuqPmdi', 'employee', 'japan', '2026-06-02 11:38:53', 'japanhr@gmail.com', '2323232', 'HR', 'Japan');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `arrangements`
--
ALTER TABLE `arrangements`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `cabs`
--
ALTER TABLE `cabs`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `food_arrangements`
--
ALTER TABLE `food_arrangements`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `tourist_day` (`tourist_id`,`day_number`);

--
-- Indexes for table `hotels`
--
ALTER TABLE `hotels`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `itineraries`
--
ALTER TABLE `itineraries`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `locations`
--
ALTER TABLE `locations`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `location_name` (`location_name`);

--
-- Indexes for table `mail_settings`
--
ALTER TABLE `mail_settings`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `mail_templates`
--
ALTER TABLE `mail_templates`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `template_key` (`template_key`);

--
-- Indexes for table `theme_settings`
--
ALTER TABLE `theme_settings`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `tourist_companions`
--
ALTER TABLE `tourist_companions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `tourist_id` (`tourist_id`);

--
-- Indexes for table `tourist_entries`
--
ALTER TABLE `tourist_entries`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `arrangements`
--
ALTER TABLE `arrangements`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `cabs`
--
ALTER TABLE `cabs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `food_arrangements`
--
ALTER TABLE `food_arrangements`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT for table `hotels`
--
ALTER TABLE `hotels`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `itineraries`
--
ALTER TABLE `itineraries`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `locations`
--
ALTER TABLE `locations`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `mail_settings`
--
ALTER TABLE `mail_settings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `mail_templates`
--
ALTER TABLE `mail_templates`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `theme_settings`
--
ALTER TABLE `theme_settings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `tourist_companions`
--
ALTER TABLE `tourist_companions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `tourist_entries`
--
ALTER TABLE `tourist_entries`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=29;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `food_arrangements`
--
ALTER TABLE `food_arrangements`
  ADD CONSTRAINT `food_arrangements_ibfk_1` FOREIGN KEY (`tourist_id`) REFERENCES `tourist_entries` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `tourist_companions`
--
ALTER TABLE `tourist_companions`
  ADD CONSTRAINT `tourist_companions_ibfk_1` FOREIGN KEY (`tourist_id`) REFERENCES `tourist_entries` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
