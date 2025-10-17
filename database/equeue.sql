-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Oct 12, 2025 at 01:10 PM
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
-- Database: `equeue`
--
Create Database equeue;
Use equeue;
-- --------------------------------------------------------

--
-- Table structure for table `departments`
--

CREATE TABLE `departments` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `departments`
--

INSERT INTO `departments` (`id`, `name`) VALUES
(1, 'General Medicine'),
(2, 'Pediatrics'),
(3, 'Dental'),
(4, 'Obstetrics and Gynecology (OB-GYN)'),
(5, 'Ophthalmology'),
(6, 'Dermatology');

-- --------------------------------------------------------

--
-- Table structure for table `doctors`
--

CREATE TABLE `doctors` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `department_id` int(11) NOT NULL,
  `status` enum('available','unavailable') NOT NULL DEFAULT 'available'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `doctors`
--

INSERT INTO `doctors` (`id`, `name`, `department_id`, `status`) VALUES
(1, 'Dr. John Smith', 1, 'available'),
(2, 'Dr. Evelyn Reed', 1, 'available'),
(3, 'Dr. Michael Chen', 2, 'available'),
(4, 'Dr. Sarah Jones', 2, 'unavailable'),
(5, 'Dr. David Garcia', 3, 'available'),
(6, 'Dr. Maria Angela Cruz', 4, 'available'),
(7, 'Dr. Kristine Dela Peña', 4, 'available'),
(8, 'Dr. Liza Mendoza', 4, 'available'),
(9, 'Dr. Raymond Santos', 5, 'available'),
(10, 'Dr. Clarisse Go', 5, 'available'),
(11, 'Dr. Benedict Uy', 5, 'available'),
(12, 'Dr. Trisha Villanueva', 6, 'available'),
(13, 'Dr. Paolo Lim', 6, 'available'),
(14, 'Dr. Hannah Robles', 6, 'available');

-- --------------------------------------------------------

--
-- Table structure for table `patients`
--

CREATE TABLE `patients` (
  `id` int(11) NOT NULL,
  `first_name` varchar(100) NOT NULL,
  `middle_name` varchar(100) DEFAULT NULL,
  `last_name` varchar(100) NOT NULL,
  `age` int(11) NOT NULL,
  `contact_number` varchar(20) NOT NULL,
  `reason_for_visit` text DEFAULT NULL,
  `parent_guardian` varchar(255) DEFAULT NULL,
  `queue_number` int(11) NOT NULL,
  `department_id` int(11) NOT NULL,
  `doctor_id` int(11) DEFAULT NULL,
  `status` enum('waiting','in consultation','done','cancelled','no show') NOT NULL DEFAULT 'waiting',
  `check_in_time` timestamp NOT NULL DEFAULT current_timestamp(),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `birthdate` date DEFAULT NULL,
  `address` text DEFAULT NULL,
  `gender` enum('male','female','preferred not to say') DEFAULT NULL,
  `civil_status` enum('single','married','widow') DEFAULT NULL,
  `registration_datetime` datetime DEFAULT NULL,
  `bp` varchar(50) DEFAULT NULL,
  `temp` varchar(50) DEFAULT NULL,
  `cr_pr` varchar(50) DEFAULT NULL,
  `rr` varchar(50) DEFAULT NULL,
  `wt` varchar(50) DEFAULT NULL,
  `o2sat` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `patients`
--

INSERT INTO `patients` (`id`, `first_name`, `middle_name`, `last_name`, `age`, `contact_number`, `reason_for_visit`, `parent_guardian`, `queue_number`, `department_id`, `doctor_id`, `status`, `check_in_time`, `created_at`, `birthdate`, `address`, `gender`, `civil_status`, `registration_datetime`, `bp`, `temp`, `cr_pr`, `rr`, `wt`, `o2sat`) VALUES
(125, 'Cody', 'H', 'Buenaventura', 21, '9567990016', 'Check-up', 'N/A', 1, 2, 3, 'done', '2025-10-12 09:55:56', '2025-10-12 09:55:56', '2003-10-16', '0', 'male', 'single', '2025-10-12 17:55:00', '', '', '', '', '', '');

-- --------------------------------------------------------

--
-- Table structure for table `queue_history`
--

CREATE TABLE `queue_history` (
  `id` int(11) NOT NULL,
  `patient_id` int(11) NOT NULL,
  `action` varchar(50) NOT NULL,
  `old_status` varchar(50) DEFAULT NULL,
  `new_status` varchar(50) DEFAULT NULL,
  `department_id` int(11) NOT NULL,
  `doctor_id` int(11) DEFAULT NULL,
  `staff_id` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `queue_history`
--

INSERT INTO `queue_history` (`id`, `patient_id`, `action`, `old_status`, `new_status`, `department_id`, `doctor_id`, `staff_id`, `created_at`) VALUES
(219, 125, 'registered', NULL, 'waiting', 2, 3, 11, '2025-10-12 09:55:56'),
(220, 125, 'status_changed', 'waiting', 'in consultation', 2, 3, 11, '2025-10-12 09:56:06'),
(221, 125, 'status_changed', 'in consultation', 'done', 2, 3, 11, '2025-10-12 10:52:49');

-- --------------------------------------------------------

--
-- Table structure for table `staff`
--

CREATE TABLE `staff` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `username` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `department_id` int(11) DEFAULT NULL,
  `role` enum('staff','receptionist') NOT NULL DEFAULT 'staff'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `staff`
--

INSERT INTO `staff` (`id`, `name`, `username`, `password`, `department_id`, `role`) VALUES
(10, 'Justin Villanueva', 'jinuu', '$2y$10$8T3O3QIljZN8nKLZbIRJD.2FkONEvQunHgyyj3P6wU9XuUZ7SwUoK', 1, 'staff'),
(11, 'Cody Buenaventura', 'codyjameel', '$2y$10$GVTQxRT8Zs5VqBIDMG9/PuoFewY3WEPmbD5MMLU808QMpJzm0.Idm', 2, 'staff'),
(12, 'Jaz Salazar', 'westburat', '$2y$10$XVXnR/JKR1ABhMHDmIcUKeV2Jj1FJXzl039SMe0Dj14HQbpDdHJNS', 3, 'staff'),
(13, 'Receptionist User', 'receptionist', '$2y$10$eUAXVOUqL8h0s5OcjebkNeBP4Vrw1h5kyJt6E7PjQrwd6sqE6omfu', 1, 'receptionist');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `departments`
--
ALTER TABLE `departments`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `doctors`
--
ALTER TABLE `doctors`
  ADD PRIMARY KEY (`id`),
  ADD KEY `department_id` (`department_id`);

--
-- Indexes for table `patients`
--
ALTER TABLE `patients`
  ADD PRIMARY KEY (`id`),
  ADD KEY `department_id` (`department_id`);

--
-- Indexes for table `queue_history`
--
ALTER TABLE `queue_history`
  ADD PRIMARY KEY (`id`),
  ADD KEY `patient_id` (`patient_id`),
  ADD KEY `department_id` (`department_id`),
  ADD KEY `staff_id` (`staff_id`);

--
-- Indexes for table `staff`
--
ALTER TABLE `staff`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_staff_department` (`department_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `departments`
--
ALTER TABLE `departments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `doctors`
--
ALTER TABLE `doctors`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT for table `patients`
--
ALTER TABLE `patients`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=126;

--
-- AUTO_INCREMENT for table `queue_history`
--
ALTER TABLE `queue_history`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=222;

--
-- AUTO_INCREMENT for table `staff`
--
ALTER TABLE `staff`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `doctors`
--
ALTER TABLE `doctors`
  ADD CONSTRAINT `doctors_ibfk_1` FOREIGN KEY (`department_id`) REFERENCES `departments` (`id`);

--
-- Constraints for table `patients`
--
ALTER TABLE `patients`
  ADD CONSTRAINT `patients_ibfk_1` FOREIGN KEY (`department_id`) REFERENCES `departments` (`id`);

--
-- Constraints for table `queue_history`
--
ALTER TABLE `queue_history`
  ADD CONSTRAINT `queue_history_ibfk_1` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `queue_history_ibfk_2` FOREIGN KEY (`department_id`) REFERENCES `departments` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `queue_history_ibfk_3` FOREIGN KEY (`staff_id`) REFERENCES `staff` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `staff`
--
-- ALTER TABLE `staff`
--   ADD CONSTRAINT `fk_staff_department` FOREIGN KEY (`department_id`) REFERENCES `departments` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
