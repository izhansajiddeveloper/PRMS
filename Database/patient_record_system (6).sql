-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Apr 10, 2026 at 12:14 PM
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
-- Database: `patient_record_system`
--

-- --------------------------------------------------------

--
-- Table structure for table `announcements`
--

CREATE TABLE `announcements` (
  `id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `target_audience` enum('all','doctors','staff') NOT NULL DEFAULT 'all',
  `status` enum('active','inactive') DEFAULT 'active',
  `start_at` datetime DEFAULT NULL,
  `expiry_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `appointments`
--

CREATE TABLE `appointments` (
  `id` int(11) NOT NULL,
  `patient_id` int(11) DEFAULT NULL,
  `doctor_id` int(11) DEFAULT NULL,
  `appointment_date` datetime DEFAULT NULL,
  `symptoms` text DEFAULT NULL,
  `category_id` int(11) DEFAULT NULL,
  `consultation_fee` decimal(10,2) DEFAULT 0.00,
  `shift_type` varchar(20) DEFAULT NULL,
  `time_slot` varchar(50) DEFAULT NULL,
  `patient_number` int(11) DEFAULT 0,
  `status` enum('pending','completed','cancelled') DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `has_tests` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `appointments`
--

INSERT INTO `appointments` (`id`, `patient_id`, `doctor_id`, `appointment_date`, `symptoms`, `category_id`, `consultation_fee`, `shift_type`, `time_slot`, `patient_number`, `status`, `created_at`, `has_tests`) VALUES
(26, 22, 12, '2026-04-07 16:00:00', 'Walk-in (Call Booking)', 6, 1300.00, 'Evening', '16', 1, 'pending', '2026-04-06 11:08:15', 0),
(27, 23, 12, '2026-04-07 16:30:00', 'Walk-in (Call Booking)', 6, 1300.00, 'Evening', '16', 2, 'pending', '2026-04-06 11:08:47', 0),
(28, 24, 12, '2026-04-07 17:00:00', '-----', 6, 1300.00, 'Evening', '17:00:00', 3, 'pending', '2026-04-06 11:09:41', 0),
(29, 25, 38, '2026-04-08 09:30:00', 'lungs cancaer', 19, 1900.00, 'Morning', '09:30:00', 1, 'completed', '2026-04-08 07:00:54', 0),
(30, 26, 38, '2026-04-08 10:00:00', 'Walk-in (Call Booking)', 19, 1900.00, 'Morning', '10', 2, 'cancelled', '2026-04-08 07:03:39', 0),
(31, 28, 38, '2026-04-09 14:00:00', 'Walk-in (Call Booking)', 19, 1900.00, 'Afternoon', '14', 1, 'completed', '2026-04-08 11:43:17', 0),
(32, 29, 47, '2026-04-08 16:00:00', '----', 13, 500.00, 'Evening', '16:00:00', 1, 'pending', '2026-04-08 11:53:31', 0),
(33, 30, 47, '2026-04-09 16:40:00', '-------', 13, 500.00, 'Evening', '16:40:00', 1, 'pending', '2026-04-09 11:27:21', 0),
(34, 31, 47, '2026-04-09 17:00:00', 'Walk-in (Call Booking)', 13, 500.00, 'Evening', '17', 3, 'pending', '2026-04-09 11:37:28', 0),
(35, 32, 29, '2026-04-10 10:50:00', 'Walk-in (Call Booking)', 15, 1400.00, 'Morning', '10', 1, 'pending', '2026-04-10 05:40:11', 0);

-- --------------------------------------------------------

--
-- Table structure for table `call_appointments`
--

CREATE TABLE `call_appointments` (
  `id` int(11) NOT NULL,
  `patient_name` varchar(100) NOT NULL,
  `phone` varchar(20) NOT NULL,
  `doctor_id` int(11) NOT NULL,
  `disease_id` int(11) NOT NULL,
  `call_date` datetime DEFAULT current_timestamp(),
  `appointment_date` datetime DEFAULT NULL,
  `status` enum('pending','visited','not_visited') DEFAULT 'pending',
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `patient_number` int(11) DEFAULT NULL,
  `shift_type` varchar(50) DEFAULT 'Morning',
  `time_slot` varchar(50) DEFAULT NULL,
  `patient_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `call_appointments`
--

INSERT INTO `call_appointments` (`id`, `patient_name`, `phone`, `doctor_id`, `disease_id`, `call_date`, `appointment_date`, `status`, `notes`, `created_at`, `patient_number`, `shift_type`, `time_slot`, `patient_id`) VALUES
(10, 'test', '03177990549', 12, 6, '2026-04-06 04:05:24', '2026-04-07 16:00:00', 'visited', 'hdffhhdz', '2026-04-06 11:05:24', 1, 'Evening', '16', 22),
(11, 'Shani', '+923214785693', 12, 6, '2026-04-06 04:05:52', '2026-04-07 16:30:00', 'visited', 'sgwgew', '2026-04-06 11:05:52', 2, 'Evening', '16', 23),
(12, 'Zahid', '03214785693', 38, 19, '2026-04-08 00:02:49', '2026-04-08 10:00:00', 'visited', '--', '2026-04-08 07:02:49', 2, 'Morning', '10', 26),
(13, 'salman ', '0511565120', 38, 19, '2026-04-08 04:42:49', '2026-04-09 14:00:00', 'visited', '----', '2026-04-08 11:42:49', 1, 'Afternoon', '14', 28),
(16, 'Zahid', '03177990549', 47, 13, '2026-04-09 04:31:24', '2026-04-09 16:50:00', 'pending', '------', '2026-04-09 11:31:24', 2, 'Evening', '16', NULL),
(17, 'Faith Mayo', '+1 (982) 763-5193', 47, 13, '2026-04-09 04:34:51', '2026-04-09 17:00:00', 'visited', 'Sit alias eiusmod v', '2026-04-09 11:34:51', 3, 'Evening', '17', 31),
(18, 'Samson Mayo', '+1 (714) 615-5252', 27, 14, '2026-04-09 04:36:16', '2026-04-10 09:00:00', 'pending', 'Commodi et facilis e', '2026-04-09 11:36:16', 1, 'Morning', '9', NULL),
(19, 'Wesley Farrell', '+1 (273) 581-6794', 29, 15, '2026-04-09 22:39:51', '2026-04-10 10:50:00', 'visited', 'Eligendi molestiae d', '2026-04-10 05:39:51', 1, 'Morning', '10', 32);

-- --------------------------------------------------------

--
-- Table structure for table `categories`
--

CREATE TABLE `categories` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `icon` varchar(50) DEFAULT NULL,
  `status` enum('active','inactive') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `categories`
--

INSERT INTO `categories` (`id`, `name`, `description`, `icon`, `status`, `created_at`, `updated_at`) VALUES
(1, 'Heart', 'Heart and cardiovascular diseases specialist', 'fa-heartbeat', 'active', '2026-04-01 05:50:08', '2026-04-02 09:55:03'),
(2, 'Brain', 'Brain, nerves and nervous system disorders specialist', 'fa-brain', 'active', '2026-04-01 05:50:08', '2026-04-02 09:55:03'),
(3, 'Eye', 'Eye diseases and vision problems specialist', 'fa-eye', 'active', '2026-04-01 05:50:08', '2026-04-02 09:55:03'),
(4, 'ENT', 'Ear, nose and throat diseases specialist', 'fa-ear-deaf', 'active', '2026-04-01 05:50:08', '2026-04-02 09:55:03'),
(5, 'Skin', 'Skin, hair and nail disorders specialist', 'fa-hand-sparkles', 'active', '2026-04-01 05:50:08', '2026-04-02 09:55:03'),
(6, 'Lungs', 'Lung and respiratory diseases specialist', 'fa-lungs', 'active', '2026-04-01 05:50:08', '2026-04-02 09:55:03'),
(7, 'Stomach', 'Digestive system disorders specialist', 'fa-stomach', 'active', '2026-04-01 05:50:08', '2026-04-02 09:55:03'),
(8, 'Bones', 'Bone, joint and muscle disorders specialist', 'fa-bone', 'active', '2026-04-01 05:50:08', '2026-04-02 09:55:03'),
(9, 'Hormones', 'Hormone and metabolic disorders specialist', 'fa-droplet', 'active', '2026-04-01 05:50:08', '2026-04-02 09:55:03'),
(10, 'Infection', 'Fever and infectious diseases specialist', 'fa-virus', 'active', '2026-04-01 05:50:08', '2026-04-02 09:55:03'),
(11, 'Child Health', 'Child health and diseases specialist', 'fa-child', 'active', '2026-04-01 05:50:08', '2026-04-02 09:55:03'),
(12, 'Mental Health', 'Mental health disorders specialist', 'fa-brain', 'active', '2026-04-01 05:50:08', '2026-04-02 09:55:03'),
(13, 'Kidney', 'Kidney diseases specialist', 'fa-filter', 'active', '2026-04-01 05:50:08', '2026-04-02 09:55:03'),
(14, 'Urinary', 'Urinary tract and male reproductive system specialist', 'fa-bladder', 'active', '2026-04-01 05:50:08', '2026-04-02 09:55:03'),
(15, 'Women Health', 'Women reproductive health specialist', 'fa-female', 'active', '2026-04-01 05:50:08', '2026-04-02 09:55:03'),
(16, 'Joint Pain', 'Joint and autoimmune diseases specialist', 'fa-hand-holding-heart', 'active', '2026-04-01 05:50:08', '2026-04-02 09:55:03'),
(17, 'Allergy', 'Allergies and immune system disorders specialist', 'fa-allergies', 'active', '2026-04-01 05:50:08', '2026-04-02 09:55:03'),
(18, 'Blood', 'Blood disorders specialist', 'fa-tint', 'active', '2026-04-01 05:50:08', '2026-04-02 09:55:03'),
(19, 'Cancer', 'Cancer and tumors specialist', 'fa-ribbon', 'active', '2026-04-01 05:50:08', '2026-04-02 09:55:03'),
(20, 'Elderly', 'Elderly health care specialist', 'fa-user-clock', 'active', '2026-04-01 05:50:08', '2026-04-02 09:55:03'),
(21, 'test', 'test', 'fa-stethoscope', 'active', '2026-04-01 11:38:34', '2026-04-01 11:38:34'),
(22, 'Test1', '--', 'fa-stethoscope', 'active', '2026-04-01 11:52:13', '2026-04-01 11:52:13');

-- --------------------------------------------------------

--
-- Table structure for table `doctors`
--

CREATE TABLE `doctors` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `specialization` varchar(100) DEFAULT NULL,
  `category_id` int(11) DEFAULT NULL,
  `status` enum('active','inactive') DEFAULT 'active',
  `consultation_fee` decimal(10,2) DEFAULT 500.00,
  `max_patients` int(11) DEFAULT 20,
  `experience_years` int(11) DEFAULT 0,
  `qualification` varchar(255) DEFAULT NULL,
  `about` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `doctors`
--

INSERT INTO `doctors` (`id`, `user_id`, `specialization`, `category_id`, `status`, `consultation_fee`, `max_patients`, `experience_years`, `qualification`, `about`, `created_at`, `updated_at`) VALUES
(1, 3, 'Interventional Cardiology', 1, 'active', 1500.00, 20, 12, 'MBBS, MD Cardiology, FACC', 'Expert in heart diseases, angioplasty, and stenting procedures', '2026-04-01 05:52:40', '2026-04-01 05:52:40'),
(2, 4, 'Clinical Cardiology', 1, 'active', 1400.00, 20, 8, 'MBBS, DM Cardiology', 'Specialist in heart failure and preventive cardiology', '2026-04-01 05:52:40', '2026-04-01 05:52:40'),
(3, 5, 'Stroke Neurology', 2, 'active', 1800.00, 20, 10, 'MBBS, FCPS Neurology', 'Stroke specialist and headache management', '2026-04-01 05:52:40', '2026-04-01 05:52:40'),
(4, 6, 'Movement Disorders', 2, 'active', 1700.00, 20, 7, 'MBBS, MD Neurology', 'Parkinson\'s and movement disorder specialist', '2026-04-01 05:52:40', '2026-04-01 05:52:40'),
(5, 7, 'Cataract Surgery', 3, 'active', 1200.00, 20, 9, 'MBBS, DOMS', 'Cataract and glaucoma specialist', '2026-04-01 05:52:40', '2026-04-01 05:52:40'),
(6, 8, 'Retina Specialist', 3, 'active', 1300.00, 20, 6, 'MBBS, MS Ophthalmology', 'Retina and diabetic eye disease specialist', '2026-04-01 05:52:40', '2026-04-01 05:52:40'),
(7, 9, 'Otology', 4, 'active', 1000.00, 20, 11, 'MBBS, FCPS ENT', 'Ear and hearing disorder specialist', '2026-04-01 05:52:40', '2026-04-01 05:52:40'),
(8, 10, 'Rhinology', 4, 'active', 1100.00, 20, 8, 'MBBS, MS ENT', 'Nose and sinus specialist', '2026-04-01 05:52:40', '2026-04-01 05:52:40'),
(9, 11, 'Cosmetic Dermatology', 5, 'active', 1300.00, 20, 7, 'MBBS, DDV', 'Skin, hair and nail specialist', '2026-04-01 05:52:40', '2026-04-01 05:52:40'),
(10, 12, 'Pediatric Dermatology', 5, 'active', 1200.00, 20, 5, 'MBBS, MD Dermatology', 'Children skin disease specialist', '2026-04-01 05:52:40', '2026-04-01 05:52:40'),
(11, 13, 'Respiratory Medicine', 6, 'active', 1400.00, 20, 9, 'MBBS, MD Pulmonology', 'Asthma and COPD specialist', '2026-04-01 05:52:40', '2026-04-01 05:52:40'),
(12, 14, 'Sleep Medicine', 6, 'active', 1300.00, 20, 6, 'MBBS, DTCD', 'Sleep disorder and respiratory specialist', '2026-04-01 05:52:40', '2026-04-01 05:52:40'),
(13, 15, 'Hepatology', 7, 'active', 1500.00, 20, 10, 'MBBS, MD Gastroenterology', 'Liver and digestive system specialist', '2026-04-01 05:52:40', '2026-04-01 05:52:40'),
(14, 16, 'IBD Specialist', 7, 'active', 1400.00, 20, 7, 'MBBS, DNB Gastro', 'Inflammatory bowel disease specialist', '2026-04-01 05:52:40', '2026-04-01 05:52:40'),
(15, 17, 'Joint Replacement', 8, 'active', 1600.00, 20, 12, 'MBBS, MS Ortho', 'Knee and hip replacement specialist', '2026-04-01 05:52:40', '2026-04-01 05:52:40'),
(16, 18, 'Sports Medicine', 8, 'active', 1500.00, 20, 8, 'MBBS, DNB Ortho', 'Sports injury and arthroscopy specialist', '2026-04-01 05:52:40', '2026-04-01 05:52:40'),
(17, 19, 'Diabetes Specialist', 9, 'active', 1400.00, 20, 9, 'MBBS, MD Endocrinology', 'Diabetes and metabolic disorder specialist', '2026-04-01 05:52:40', '2026-04-01 05:52:40'),
(18, 20, 'Thyroid Specialist', 9, 'active', 1300.00, 20, 6, 'MBBS, DM Endocrinology', 'Thyroid and hormone disorder specialist', '2026-04-01 05:52:40', '2026-04-01 05:52:40'),
(19, 21, 'Tropical Medicine', 10, 'active', 1500.00, 20, 8, 'MBBS, MD Infectious Diseases', 'Tropical and infectious disease specialist', '2026-04-01 05:52:40', '2026-04-01 05:52:40'),
(20, 22, 'HIV Specialist', 10, 'active', 1400.00, 20, 5, 'MBBS, DNB ID', 'HIV/AIDS and immunology specialist', '2026-04-01 05:52:40', '2026-04-01 05:52:40'),
(21, 23, 'General Pediatrics', 11, 'active', 1000.00, 20, 10, 'MBBS, FCPS Pediatrics', 'Child health and development specialist', '2026-04-01 05:52:40', '2026-04-01 05:52:40'),
(22, 24, 'Neonatology', 11, 'active', 1200.00, 20, 7, 'MBBS, MD Pediatrics', 'Newborn and premature baby specialist', '2026-04-01 05:52:40', '2026-04-01 05:52:40'),
(23, 25, 'Adult Psychiatry', 12, 'active', 1800.00, 20, 12, 'MBBS, MD Psychiatry', 'Mental health and depression specialist', '2026-04-01 05:52:40', '2026-04-01 05:52:40'),
(24, 26, 'Child Psychiatry', 12, 'active', 1700.00, 20, 8, 'MBBS, DPM', 'Child and adolescent mental health specialist', '2026-04-01 05:52:40', '2026-04-01 05:52:40'),
(25, 27, 'Dialysis Specialist', 13, 'active', 1500.00, 20, 9, 'MBBS, MD Nephrology', 'Kidney disease and dialysis specialist', '2026-04-01 05:52:40', '2026-04-01 05:52:40'),
(26, 28, 'Transplant Nephrology', 13, 'active', 1600.00, 20, 6, 'MBBS, DNB Nephro', 'Kidney transplant specialist', '2026-04-01 05:52:40', '2026-04-01 05:52:40'),
(27, 29, 'Andrology', 14, 'active', 1500.00, 20, 8, 'MBBS, FCPS Urology', 'Male reproductive and urinary tract specialist', '2026-04-01 05:52:40', '2026-04-01 05:52:40'),
(28, 30, 'Endourology', 14, 'active', 1400.00, 20, 5, 'MBBS, MS Urology', 'Minimally invasive urological surgery', '2026-04-01 05:52:40', '2026-04-01 05:52:40'),
(29, 31, 'Obstetrics', 15, 'active', 1400.00, 20, 10, 'MBBS, FCPS Gynecology', 'Pregnancy and childbirth specialist', '2026-04-01 05:52:40', '2026-04-01 05:52:40'),
(30, 32, 'Infertility Specialist', 15, 'active', 1500.00, 20, 7, 'MBBS, MS Gyne', 'Fertility and reproductive health specialist', '2026-04-01 05:52:40', '2026-04-01 05:52:40'),
(31, 33, 'Autoimmune Diseases', 16, 'active', 1600.00, 20, 9, 'MBBS, MD Rheumatology', 'Arthritis and autoimmune disease specialist', '2026-04-01 05:52:40', '2026-04-01 05:52:40'),
(32, 34, 'Pediatric Rheumatology', 16, 'active', 1500.00, 20, 5, 'MBBS, DNB Rheumatology', 'Children joint and autoimmune specialist', '2026-04-01 05:52:40', '2026-04-01 05:52:40'),
(33, 35, 'Clinical Allergy', 17, 'active', 1200.00, 20, 7, 'MBBS, MD Allergy', 'Food and environmental allergy specialist', '2026-04-01 05:52:40', '2026-04-01 05:52:40'),
(34, 36, 'Immunotherapy', 17, 'active', 1100.00, 20, 4, 'MBBS, DNB Allergy', 'Allergy treatment and immunotherapy specialist', '2026-04-01 05:52:40', '2026-04-01 05:52:40'),
(35, 37, 'Blood Disorders', 18, 'active', 1600.00, 20, 10, 'MBBS, MD Hematology', 'Blood cancer and disorder specialist', '2026-04-01 05:52:40', '2026-04-01 05:52:40'),
(36, 38, 'Pediatric Hematology', 18, 'active', 1500.00, 20, 6, 'MBBS, DNB Hematology', 'Children blood disorder specialist', '2026-04-01 05:52:40', '2026-04-01 05:52:40'),
(37, 39, 'Medical Oncology', 19, 'active', 2000.00, 20, 12, 'MBBS, MD Oncology', 'Cancer chemotherapy and treatment specialist', '2026-04-01 05:52:40', '2026-04-01 05:52:40'),
(38, 40, 'Radiation Oncology', 19, 'active', 1900.00, 20, 8, 'MBBS, DNB Oncology', 'Radiation therapy cancer specialist', '2026-04-01 05:52:40', '2026-04-01 05:52:40'),
(39, 41, 'Elderly Care', 20, 'active', 1400.00, 20, 9, 'MBBS, MD Geriatrics', 'Senior citizen health and wellness specialist', '2026-04-01 05:52:40', '2026-04-01 05:52:40'),
(40, 42, 'Dementia Specialist', 20, 'active', 1300.00, 20, 6, 'MBBS, DNB Geriatrics', 'Memory loss and dementia care specialist', '2026-04-01 05:52:40', '2026-04-01 05:52:40'),
(43, 67, 'test', 1, 'active', 500.00, 20, 0, NULL, NULL, '2026-04-03 07:15:14', '2026-04-03 07:15:14'),
(44, 68, 'Cardiologist', 1, 'active', 500.00, 20, 0, NULL, NULL, '2026-04-03 11:14:33', '2026-04-03 11:14:33'),
(45, 69, 'Cardiologist', 1, 'active', 500.00, 20, 0, NULL, NULL, '2026-04-04 13:13:41', '2026-04-04 13:13:41'),
(46, 70, 'Cardiologist', 1, 'active', 500.00, 20, 0, NULL, NULL, '2026-04-08 07:11:00', '2026-04-08 07:11:00'),
(47, 71, 'Quis irure ipsa dis', 13, 'active', 500.00, 20, 0, NULL, NULL, '2026-04-08 11:50:51', '2026-04-08 11:51:53');

-- --------------------------------------------------------

--
-- Table structure for table `doctor_schedules`
--

CREATE TABLE `doctor_schedules` (
  `id` int(11) NOT NULL,
  `doctor_id` int(11) NOT NULL,
  `day_of_week` enum('Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday') NOT NULL,
  `shift_type` enum('Morning','Afternoon','Evening','Full Day') NOT NULL,
  `start_time` time NOT NULL,
  `end_time` time NOT NULL,
  `max_appointments` int(11) DEFAULT 20,
  `status` enum('active','inactive') DEFAULT 'active',
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `doctor_schedules`
--

INSERT INTO `doctor_schedules` (`id`, `doctor_id`, `day_of_week`, `shift_type`, `start_time`, `end_time`, `max_appointments`, `status`, `notes`, `created_at`, `updated_at`) VALUES
(1, 1, 'Monday', 'Morning', '09:00:00', '13:00:00', 15, 'active', NULL, '2026-04-01 06:01:02', '2026-04-01 06:01:02'),
(2, 1, 'Wednesday', 'Morning', '09:00:00', '13:00:00', 15, 'active', NULL, '2026-04-01 06:01:02', '2026-04-01 06:01:02'),
(3, 1, 'Friday', 'Morning', '09:00:00', '13:00:00', 15, 'active', NULL, '2026-04-01 06:01:02', '2026-04-01 06:01:02'),
(4, 2, 'Tuesday', 'Morning', '09:00:00', '13:00:00', 12, 'active', NULL, '2026-04-01 06:01:02', '2026-04-01 06:01:02'),
(5, 2, 'Thursday', 'Morning', '09:00:00', '13:00:00', 12, 'active', NULL, '2026-04-01 06:01:02', '2026-04-01 06:01:02'),
(6, 2, 'Saturday', 'Morning', '10:00:00', '14:00:00', 10, 'active', NULL, '2026-04-01 06:01:02', '2026-04-01 06:01:02'),
(7, 3, 'Monday', 'Afternoon', '14:00:00', '18:00:00', 10, 'active', NULL, '2026-04-01 06:01:02', '2026-04-01 06:01:02'),
(8, 3, 'Wednesday', 'Afternoon', '14:00:00', '18:00:00', 10, 'active', NULL, '2026-04-01 06:01:02', '2026-04-01 06:01:02'),
(9, 3, 'Friday', 'Afternoon', '14:00:00', '18:00:00', 10, 'active', NULL, '2026-04-01 06:01:02', '2026-04-01 06:01:02'),
(10, 4, 'Tuesday', 'Morning', '09:00:00', '13:00:00', 12, 'active', NULL, '2026-04-01 06:01:02', '2026-04-01 06:01:02'),
(11, 4, 'Thursday', 'Morning', '09:00:00', '13:00:00', 12, 'active', NULL, '2026-04-01 06:01:02', '2026-04-01 06:01:02'),
(12, 4, 'Saturday', 'Morning', '10:00:00', '14:00:00', 8, 'active', NULL, '2026-04-01 06:01:02', '2026-04-01 06:01:02'),
(13, 5, 'Monday', 'Morning', '09:00:00', '13:00:00', 15, 'active', NULL, '2026-04-01 06:01:02', '2026-04-01 06:01:02'),
(14, 5, 'Wednesday', 'Morning', '09:00:00', '13:00:00', 15, 'active', NULL, '2026-04-01 06:01:02', '2026-04-01 06:01:02'),
(15, 5, 'Friday', 'Morning', '09:00:00', '13:00:00', 15, 'active', NULL, '2026-04-01 06:01:02', '2026-04-01 06:01:02'),
(16, 6, 'Tuesday', 'Evening', '16:00:00', '20:00:00', 10, 'active', NULL, '2026-04-01 06:01:02', '2026-04-01 06:01:02'),
(17, 6, 'Thursday', 'Evening', '16:00:00', '20:00:00', 10, 'active', NULL, '2026-04-01 06:01:02', '2026-04-01 06:01:02'),
(18, 6, 'Saturday', 'Morning', '10:00:00', '14:00:00', 8, 'active', NULL, '2026-04-01 06:01:02', '2026-04-01 06:01:02'),
(19, 7, 'Monday', 'Morning', '09:00:00', '13:00:00', 12, 'active', NULL, '2026-04-01 06:01:02', '2026-04-01 06:01:02'),
(20, 7, 'Tuesday', 'Afternoon', '14:00:00', '18:00:00', 10, 'active', NULL, '2026-04-01 06:01:02', '2026-04-01 06:01:02'),
(21, 7, 'Thursday', 'Morning', '09:00:00', '13:00:00', 12, 'active', NULL, '2026-04-01 06:01:02', '2026-04-01 06:01:02'),
(22, 7, 'Friday', 'Afternoon', '14:00:00', '18:00:00', 10, 'active', NULL, '2026-04-01 06:01:02', '2026-04-01 06:01:02'),
(23, 8, 'Wednesday', 'Morning', '09:00:00', '13:00:00', 15, 'active', NULL, '2026-04-01 06:01:02', '2026-04-01 06:01:02'),
(24, 8, 'Thursday', 'Afternoon', '14:00:00', '18:00:00', 10, 'active', NULL, '2026-04-01 06:01:02', '2026-04-01 06:01:02'),
(25, 8, 'Saturday', 'Morning', '10:00:00', '14:00:00', 10, 'active', NULL, '2026-04-01 06:01:02', '2026-04-01 06:01:02'),
(26, 9, 'Monday', 'Morning', '09:00:00', '13:00:00', 15, 'active', NULL, '2026-04-01 06:01:02', '2026-04-01 06:01:02'),
(27, 9, 'Wednesday', 'Morning', '09:00:00', '13:00:00', 15, 'active', NULL, '2026-04-01 06:01:02', '2026-04-01 06:01:02'),
(28, 9, 'Friday', 'Morning', '09:00:00', '13:00:00', 15, 'active', NULL, '2026-04-01 06:01:02', '2026-04-01 06:01:02'),
(29, 10, 'Tuesday', 'Afternoon', '14:00:00', '18:00:00', 10, 'active', NULL, '2026-04-01 06:01:02', '2026-04-01 06:01:02'),
(30, 10, 'Thursday', 'Afternoon', '14:00:00', '18:00:00', 10, 'active', NULL, '2026-04-01 06:01:02', '2026-04-01 06:01:02'),
(31, 10, 'Saturday', 'Morning', '10:00:00', '14:00:00', 8, 'active', NULL, '2026-04-01 06:01:02', '2026-04-01 06:01:02'),
(32, 11, 'Monday', 'Morning', '09:00:00', '13:00:00', 12, 'active', NULL, '2026-04-01 06:01:02', '2026-04-01 06:01:02'),
(33, 11, 'Wednesday', 'Morning', '09:00:00', '13:00:00', 12, 'active', NULL, '2026-04-01 06:01:02', '2026-04-01 06:01:02'),
(34, 11, 'Friday', 'Morning', '09:00:00', '13:00:00', 12, 'active', NULL, '2026-04-01 06:01:02', '2026-04-01 06:01:02'),
(35, 12, 'Tuesday', 'Evening', '16:00:00', '20:00:00', 8, 'active', NULL, '2026-04-01 06:01:02', '2026-04-01 06:01:02'),
(36, 12, 'Thursday', 'Evening', '16:00:00', '20:00:00', 8, 'active', NULL, '2026-04-01 06:01:02', '2026-04-01 06:01:02'),
(37, 12, 'Saturday', 'Morning', '10:00:00', '14:00:00', 10, 'active', NULL, '2026-04-01 06:01:02', '2026-04-01 06:01:02'),
(38, 13, 'Monday', 'Morning', '09:00:00', '13:00:00', 15, 'active', NULL, '2026-04-01 06:01:02', '2026-04-01 06:01:02'),
(39, 13, 'Tuesday', 'Afternoon', '14:00:00', '18:00:00', 10, 'active', NULL, '2026-04-01 06:01:02', '2026-04-01 06:01:02'),
(40, 13, 'Thursday', 'Morning', '09:00:00', '13:00:00', 15, 'active', NULL, '2026-04-01 06:01:02', '2026-04-01 06:01:02'),
(41, 13, 'Friday', 'Afternoon', '14:00:00', '18:00:00', 10, 'active', NULL, '2026-04-01 06:01:02', '2026-04-01 06:01:02'),
(42, 14, 'Wednesday', 'Morning', '09:00:00', '13:00:00', 12, 'active', NULL, '2026-04-01 06:01:02', '2026-04-01 06:01:02'),
(43, 14, 'Thursday', 'Afternoon', '14:00:00', '18:00:00', 10, 'active', NULL, '2026-04-01 06:01:02', '2026-04-01 06:01:02'),
(44, 14, 'Saturday', 'Morning', '10:00:00', '14:00:00', 8, 'active', NULL, '2026-04-01 06:01:02', '2026-04-01 06:01:02'),
(45, 15, 'Monday', 'Morning', '09:00:00', '13:00:00', 12, 'active', NULL, '2026-04-01 06:01:02', '2026-04-01 06:01:02'),
(46, 15, 'Wednesday', 'Morning', '09:00:00', '13:00:00', 12, 'active', NULL, '2026-04-01 06:01:02', '2026-04-01 06:01:02'),
(47, 15, 'Friday', 'Morning', '09:00:00', '13:00:00', 12, 'active', NULL, '2026-04-01 06:01:02', '2026-04-01 06:01:02'),
(48, 16, 'Tuesday', 'Afternoon', '14:00:00', '18:00:00', 10, 'active', NULL, '2026-04-01 06:01:02', '2026-04-01 06:01:02'),
(49, 16, 'Thursday', 'Afternoon', '14:00:00', '18:00:00', 10, 'active', NULL, '2026-04-01 06:01:02', '2026-04-01 06:01:02'),
(50, 16, 'Saturday', 'Morning', '10:00:00', '14:00:00', 8, 'active', NULL, '2026-04-01 06:01:02', '2026-04-01 06:01:02'),
(51, 17, 'Monday', 'Morning', '09:00:00', '13:00:00', 15, 'active', NULL, '2026-04-01 06:01:02', '2026-04-01 06:01:02'),
(52, 17, 'Wednesday', 'Morning', '09:00:00', '13:00:00', 15, 'active', NULL, '2026-04-01 06:01:02', '2026-04-01 06:01:02'),
(53, 17, 'Friday', 'Morning', '09:00:00', '13:00:00', 15, 'active', NULL, '2026-04-01 06:01:02', '2026-04-01 06:01:02'),
(54, 18, 'Tuesday', 'Evening', '16:00:00', '20:00:00', 10, 'active', NULL, '2026-04-01 06:01:02', '2026-04-01 06:01:02'),
(55, 18, 'Thursday', 'Evening', '16:00:00', '20:00:00', 10, 'active', NULL, '2026-04-01 06:01:02', '2026-04-01 06:01:02'),
(56, 18, 'Saturday', 'Morning', '10:00:00', '14:00:00', 8, 'active', NULL, '2026-04-01 06:01:02', '2026-04-01 06:01:02'),
(57, 19, 'Monday', 'Morning', '09:00:00', '13:00:00', 12, 'active', NULL, '2026-04-01 06:01:02', '2026-04-01 06:01:02'),
(58, 19, 'Tuesday', 'Afternoon', '14:00:00', '18:00:00', 10, 'active', NULL, '2026-04-01 06:01:02', '2026-04-01 06:01:02'),
(59, 19, 'Thursday', 'Morning', '09:00:00', '13:00:00', 12, 'active', NULL, '2026-04-01 06:01:02', '2026-04-01 06:01:02'),
(60, 19, 'Friday', 'Afternoon', '14:00:00', '18:00:00', 10, 'active', NULL, '2026-04-01 06:01:02', '2026-04-01 06:01:02'),
(61, 20, 'Wednesday', 'Morning', '09:00:00', '13:00:00', 15, 'active', NULL, '2026-04-01 06:01:02', '2026-04-01 06:01:02'),
(62, 20, 'Thursday', 'Afternoon', '14:00:00', '18:00:00', 10, 'active', NULL, '2026-04-01 06:01:02', '2026-04-01 06:01:02'),
(63, 20, 'Saturday', 'Morning', '10:00:00', '14:00:00', 10, 'active', NULL, '2026-04-01 06:01:02', '2026-04-01 06:01:02'),
(64, 21, 'Monday', 'Morning', '09:00:00', '13:00:00', 15, 'active', NULL, '2026-04-01 06:01:02', '2026-04-01 06:01:02'),
(65, 21, 'Wednesday', 'Morning', '09:00:00', '13:00:00', 15, 'active', NULL, '2026-04-01 06:01:02', '2026-04-01 06:01:02'),
(66, 21, 'Friday', 'Morning', '09:00:00', '13:00:00', 15, 'active', NULL, '2026-04-01 06:01:02', '2026-04-01 06:01:02'),
(67, 22, 'Tuesday', 'Afternoon', '14:00:00', '18:00:00', 10, 'active', NULL, '2026-04-01 06:01:02', '2026-04-01 06:01:02'),
(68, 22, 'Thursday', 'Afternoon', '14:00:00', '18:00:00', 10, 'active', NULL, '2026-04-01 06:01:02', '2026-04-01 06:01:02'),
(69, 22, 'Saturday', 'Morning', '10:00:00', '14:00:00', 8, 'active', NULL, '2026-04-01 06:01:02', '2026-04-01 06:01:02'),
(70, 23, 'Monday', 'Morning', '09:00:00', '13:00:00', 12, 'active', NULL, '2026-04-01 06:01:02', '2026-04-01 06:01:02'),
(71, 23, 'Wednesday', 'Morning', '09:00:00', '13:00:00', 12, 'active', NULL, '2026-04-01 06:01:02', '2026-04-01 06:01:02'),
(72, 23, 'Friday', 'Morning', '09:00:00', '13:00:00', 12, 'active', NULL, '2026-04-01 06:01:02', '2026-04-01 06:01:02'),
(73, 24, 'Tuesday', 'Evening', '16:00:00', '20:00:00', 8, 'active', NULL, '2026-04-01 06:01:02', '2026-04-01 06:01:02'),
(74, 24, 'Thursday', 'Evening', '16:00:00', '20:00:00', 8, 'active', NULL, '2026-04-01 06:01:02', '2026-04-01 06:01:02'),
(75, 24, 'Saturday', 'Morning', '10:00:00', '14:00:00', 10, 'active', NULL, '2026-04-01 06:01:02', '2026-04-01 06:01:02'),
(76, 25, 'Monday', 'Morning', '09:00:00', '13:00:00', 15, 'active', NULL, '2026-04-01 06:01:02', '2026-04-01 06:01:02'),
(77, 25, 'Tuesday', 'Afternoon', '14:00:00', '18:00:00', 10, 'active', NULL, '2026-04-01 06:01:02', '2026-04-01 06:01:02'),
(78, 25, 'Thursday', 'Morning', '09:00:00', '13:00:00', 15, 'active', NULL, '2026-04-01 06:01:02', '2026-04-01 06:01:02'),
(79, 25, 'Friday', 'Afternoon', '14:00:00', '18:00:00', 10, 'active', NULL, '2026-04-01 06:01:02', '2026-04-01 06:01:02'),
(80, 26, 'Wednesday', 'Morning', '09:00:00', '13:00:00', 12, 'active', NULL, '2026-04-01 06:01:02', '2026-04-01 06:01:02'),
(81, 26, 'Thursday', 'Afternoon', '14:00:00', '18:00:00', 10, 'active', NULL, '2026-04-01 06:01:02', '2026-04-01 06:01:02'),
(82, 26, 'Saturday', 'Morning', '10:00:00', '14:00:00', 8, 'active', NULL, '2026-04-01 06:01:02', '2026-04-01 06:01:02'),
(83, 27, 'Monday', 'Morning', '09:00:00', '13:00:00', 12, 'active', NULL, '2026-04-01 06:01:02', '2026-04-01 06:01:02'),
(84, 27, 'Wednesday', 'Morning', '09:00:00', '13:00:00', 12, 'active', NULL, '2026-04-01 06:01:02', '2026-04-01 06:01:02'),
(85, 27, 'Friday', 'Morning', '09:00:00', '13:00:00', 12, 'active', NULL, '2026-04-01 06:01:02', '2026-04-01 06:01:02'),
(86, 28, 'Tuesday', 'Afternoon', '14:00:00', '18:00:00', 10, 'active', NULL, '2026-04-01 06:01:02', '2026-04-01 06:01:02'),
(87, 28, 'Thursday', 'Afternoon', '14:00:00', '18:00:00', 10, 'active', NULL, '2026-04-01 06:01:02', '2026-04-01 06:01:02'),
(88, 28, 'Saturday', 'Morning', '10:00:00', '14:00:00', 8, 'active', NULL, '2026-04-01 06:01:02', '2026-04-01 06:01:02'),
(89, 29, 'Monday', 'Morning', '09:00:00', '13:00:00', 15, 'active', NULL, '2026-04-01 06:01:02', '2026-04-01 06:01:02'),
(90, 29, 'Wednesday', 'Morning', '09:00:00', '13:00:00', 15, 'active', NULL, '2026-04-01 06:01:02', '2026-04-01 06:01:02'),
(91, 29, 'Friday', 'Morning', '09:00:00', '13:00:00', 15, 'active', NULL, '2026-04-01 06:01:02', '2026-04-01 06:01:02'),
(92, 30, 'Tuesday', 'Evening', '16:00:00', '20:00:00', 10, 'active', NULL, '2026-04-01 06:01:02', '2026-04-01 06:01:02'),
(93, 30, 'Thursday', 'Evening', '16:00:00', '20:00:00', 10, 'active', NULL, '2026-04-01 06:01:02', '2026-04-01 06:01:02'),
(94, 30, 'Saturday', 'Morning', '10:00:00', '14:00:00', 8, 'active', NULL, '2026-04-01 06:01:02', '2026-04-01 06:01:02'),
(95, 31, 'Monday', 'Morning', '09:00:00', '13:00:00', 12, 'active', NULL, '2026-04-01 06:01:02', '2026-04-01 06:01:02'),
(96, 31, 'Tuesday', 'Afternoon', '14:00:00', '18:00:00', 10, 'active', NULL, '2026-04-01 06:01:02', '2026-04-01 06:01:02'),
(97, 31, 'Thursday', 'Morning', '09:00:00', '13:00:00', 12, 'active', NULL, '2026-04-01 06:01:02', '2026-04-01 06:01:02'),
(98, 31, 'Friday', 'Afternoon', '14:00:00', '18:00:00', 10, 'active', NULL, '2026-04-01 06:01:02', '2026-04-01 06:01:02'),
(99, 32, 'Wednesday', 'Morning', '09:00:00', '13:00:00', 15, 'active', NULL, '2026-04-01 06:01:02', '2026-04-01 06:01:02'),
(100, 32, 'Thursday', 'Afternoon', '14:00:00', '18:00:00', 10, 'active', NULL, '2026-04-01 06:01:02', '2026-04-01 06:01:02'),
(101, 32, 'Saturday', 'Morning', '10:00:00', '14:00:00', 10, 'active', NULL, '2026-04-01 06:01:02', '2026-04-01 06:01:02'),
(102, 33, 'Monday', 'Morning', '09:00:00', '13:00:00', 15, 'active', NULL, '2026-04-01 06:01:02', '2026-04-01 06:01:02'),
(103, 33, 'Wednesday', 'Morning', '09:00:00', '13:00:00', 15, 'active', NULL, '2026-04-01 06:01:02', '2026-04-01 06:01:02'),
(104, 33, 'Friday', 'Morning', '09:00:00', '13:00:00', 15, 'active', NULL, '2026-04-01 06:01:02', '2026-04-01 06:01:02'),
(105, 34, 'Tuesday', 'Afternoon', '14:00:00', '18:00:00', 10, 'active', NULL, '2026-04-01 06:01:02', '2026-04-01 06:01:02'),
(106, 34, 'Thursday', 'Afternoon', '14:00:00', '18:00:00', 10, 'active', NULL, '2026-04-01 06:01:02', '2026-04-01 06:01:02'),
(107, 34, 'Saturday', 'Morning', '10:00:00', '14:00:00', 8, 'active', NULL, '2026-04-01 06:01:02', '2026-04-01 06:01:02'),
(108, 35, 'Monday', 'Morning', '09:00:00', '13:00:00', 12, 'active', NULL, '2026-04-01 06:01:02', '2026-04-01 06:01:02'),
(109, 35, 'Wednesday', 'Morning', '09:00:00', '13:00:00', 12, 'active', NULL, '2026-04-01 06:01:02', '2026-04-01 06:01:02'),
(110, 35, 'Friday', 'Morning', '09:00:00', '13:00:00', 12, 'active', NULL, '2026-04-01 06:01:02', '2026-04-01 06:01:02'),
(111, 36, 'Tuesday', 'Evening', '16:00:00', '20:00:00', 8, 'active', NULL, '2026-04-01 06:01:02', '2026-04-01 06:01:02'),
(112, 36, 'Thursday', 'Evening', '16:00:00', '20:00:00', 8, 'active', NULL, '2026-04-01 06:01:02', '2026-04-01 06:01:02'),
(113, 36, 'Saturday', 'Morning', '10:00:00', '14:00:00', 10, 'active', NULL, '2026-04-01 06:01:02', '2026-04-01 06:01:02'),
(114, 37, 'Monday', 'Morning', '09:00:00', '13:00:00', 15, 'active', NULL, '2026-04-01 06:01:02', '2026-04-01 06:01:02'),
(115, 37, 'Tuesday', 'Afternoon', '14:00:00', '18:00:00', 10, 'active', NULL, '2026-04-01 06:01:02', '2026-04-01 06:01:02'),
(116, 37, 'Thursday', 'Morning', '09:00:00', '13:00:00', 15, 'active', NULL, '2026-04-01 06:01:02', '2026-04-01 06:01:02'),
(117, 37, 'Friday', 'Afternoon', '14:00:00', '18:00:00', 10, 'active', NULL, '2026-04-01 06:01:02', '2026-04-01 06:01:02'),
(118, 38, 'Wednesday', 'Morning', '09:00:00', '13:00:00', 12, 'active', NULL, '2026-04-01 06:01:02', '2026-04-01 06:01:02'),
(119, 38, 'Thursday', 'Afternoon', '14:00:00', '18:00:00', 10, 'active', NULL, '2026-04-01 06:01:02', '2026-04-01 06:01:02'),
(120, 38, 'Saturday', 'Morning', '10:00:00', '14:00:00', 8, 'active', NULL, '2026-04-01 06:01:02', '2026-04-01 06:01:02'),
(121, 39, 'Monday', 'Morning', '09:00:00', '13:00:00', 12, 'active', NULL, '2026-04-01 06:01:02', '2026-04-01 06:01:02'),
(122, 39, 'Wednesday', 'Morning', '09:00:00', '13:00:00', 12, 'active', NULL, '2026-04-01 06:01:02', '2026-04-01 06:01:02'),
(123, 39, 'Friday', 'Morning', '09:00:00', '13:00:00', 12, 'active', NULL, '2026-04-01 06:01:02', '2026-04-01 06:01:02'),
(124, 40, 'Tuesday', 'Afternoon', '14:00:00', '18:00:00', 10, 'active', NULL, '2026-04-01 06:01:02', '2026-04-01 06:01:02'),
(125, 40, 'Thursday', 'Afternoon', '14:00:00', '18:00:00', 10, 'active', NULL, '2026-04-01 06:01:02', '2026-04-01 06:01:02'),
(126, 40, 'Saturday', 'Morning', '10:00:00', '14:00:00', 8, 'active', NULL, '2026-04-01 06:01:02', '2026-04-01 06:01:02'),
(130, 29, 'Tuesday', 'Morning', '09:00:00', '00:00:13', 15, 'active', 'the doctor is not avaaible on thursday at 11:30am', '2026-04-03 07:11:58', '2026-04-03 07:11:58'),
(131, 29, 'Thursday', 'Morning', '09:00:00', '00:00:13', 15, 'active', 'the doctor is not avaaible on thursday at 11:30am', '2026-04-03 07:11:58', '2026-04-03 07:11:58'),
(132, 29, 'Saturday', 'Morning', '09:00:00', '00:00:13', 15, 'active', 'the doctor is not avaaible on thursday at 11:30am', '2026-04-03 07:11:58', '2026-04-03 07:11:58'),
(133, 29, 'Sunday', 'Morning', '09:00:00', '00:00:13', 15, 'active', 'the doctor is not avaaible on thursday at 11:30am', '2026-04-03 07:11:58', '2026-04-03 07:11:58'),
(134, 43, 'Monday', 'Morning', '09:00:00', '00:00:13', 15, 'active', '---', '2026-04-03 07:16:01', '2026-04-03 07:16:01'),
(135, 43, 'Tuesday', 'Morning', '09:00:00', '00:00:13', 15, 'active', '---', '2026-04-03 07:16:01', '2026-04-03 07:16:01'),
(136, 43, 'Wednesday', 'Morning', '09:00:00', '00:00:13', 15, 'active', '---', '2026-04-03 07:16:01', '2026-04-03 07:16:01'),
(137, 43, 'Thursday', 'Morning', '09:00:00', '00:00:13', 15, 'active', '---', '2026-04-03 07:16:01', '2026-04-03 07:16:01'),
(138, 43, 'Friday', 'Morning', '09:00:00', '00:00:13', 15, 'active', '---', '2026-04-03 07:16:01', '2026-04-03 07:16:01'),
(139, 43, 'Saturday', 'Morning', '09:00:00', '00:00:13', 15, 'active', '---', '2026-04-03 07:16:01', '2026-04-03 07:16:01'),
(140, 43, 'Sunday', 'Morning', '09:00:00', '00:00:13', 15, 'active', '---', '2026-04-03 07:16:01', '2026-04-03 07:16:01'),
(141, 43, 'Monday', 'Evening', '16:00:00', '00:00:20', 15, 'active', '----', '2026-04-03 07:16:39', '2026-04-03 07:16:39'),
(142, 43, 'Wednesday', 'Evening', '16:00:00', '00:00:20', 15, 'active', '----', '2026-04-03 07:16:39', '2026-04-03 07:16:39'),
(143, 43, 'Friday', 'Evening', '16:00:00', '00:00:20', 15, 'active', '----', '2026-04-03 07:16:39', '2026-04-03 07:16:39'),
(144, 44, 'Monday', 'Evening', '16:00:00', '00:00:20', 15, 'active', '---', '2026-04-03 11:16:32', '2026-04-03 11:16:32'),
(145, 44, 'Tuesday', 'Evening', '16:00:00', '00:00:20', 15, 'active', '---', '2026-04-03 11:16:32', '2026-04-03 11:16:32'),
(146, 44, 'Wednesday', 'Evening', '16:00:00', '00:00:20', 15, 'active', '---', '2026-04-03 11:16:32', '2026-04-03 11:16:32'),
(147, 44, 'Thursday', 'Evening', '16:00:00', '00:00:20', 15, 'active', '---', '2026-04-03 11:16:32', '2026-04-03 11:16:32'),
(148, 44, 'Friday', 'Evening', '16:00:00', '00:00:20', 15, 'active', '---', '2026-04-03 11:16:32', '2026-04-03 11:16:32'),
(149, 44, 'Saturday', 'Evening', '16:00:00', '00:00:20', 15, 'active', '---', '2026-04-03 11:16:32', '2026-04-03 11:16:32'),
(150, 44, 'Sunday', 'Evening', '16:00:00', '00:00:20', 15, 'active', '---', '2026-04-03 11:16:32', '2026-04-03 11:16:32'),
(151, 45, 'Monday', 'Morning', '09:00:00', '00:00:13', 15, 'active', '---', '2026-04-04 13:16:17', '2026-04-04 13:16:17'),
(152, 45, 'Wednesday', 'Morning', '09:00:00', '00:00:13', 15, 'active', '---', '2026-04-04 13:16:17', '2026-04-04 13:16:17'),
(153, 45, 'Friday', 'Morning', '09:00:00', '00:00:13', 15, 'active', '---', '2026-04-04 13:16:17', '2026-04-04 13:16:17'),
(155, 46, 'Tuesday', 'Morning', '09:00:00', '00:00:13', 50, 'active', '----', '2026-04-08 07:11:46', '2026-04-08 07:11:46'),
(156, 46, 'Wednesday', 'Morning', '09:00:00', '00:00:13', 50, 'active', '----', '2026-04-08 07:11:46', '2026-04-08 07:11:46'),
(157, 46, 'Thursday', 'Morning', '09:00:00', '00:00:13', 50, 'active', '----', '2026-04-08 07:11:46', '2026-04-08 07:11:46'),
(158, 46, 'Friday', 'Morning', '09:00:00', '00:00:13', 50, 'active', '----', '2026-04-08 07:11:46', '2026-04-08 07:11:46'),
(159, 47, 'Monday', 'Evening', '16:00:00', '00:00:20', 15, 'active', '----', '2026-04-08 11:51:21', '2026-04-08 11:51:21'),
(160, 47, 'Tuesday', 'Evening', '16:00:00', '00:00:20', 15, 'active', '----', '2026-04-08 11:51:21', '2026-04-08 11:51:21'),
(161, 47, 'Wednesday', 'Evening', '16:00:00', '00:00:20', 15, 'active', '----', '2026-04-08 11:51:21', '2026-04-08 11:51:21'),
(162, 47, 'Thursday', 'Evening', '16:00:00', '00:00:20', 15, 'active', '----', '2026-04-08 11:51:21', '2026-04-08 11:51:21'),
(163, 47, 'Friday', 'Evening', '16:00:00', '00:00:20', 15, 'active', '----', '2026-04-08 11:51:21', '2026-04-08 11:51:21'),
(164, 47, 'Saturday', 'Evening', '16:00:00', '00:00:20', 15, 'active', '----', '2026-04-08 11:51:21', '2026-04-08 11:51:21'),
(165, 47, 'Sunday', 'Evening', '16:00:00', '00:00:20', 15, 'active', '----', '2026-04-08 11:51:21', '2026-04-08 11:51:21');

-- --------------------------------------------------------

--
-- Table structure for table `patients`
--

CREATE TABLE `patients` (
  `id` int(11) NOT NULL,
  `name` varchar(100) DEFAULT NULL,
  `age` int(11) DEFAULT NULL,
  `weight` decimal(5,2) DEFAULT NULL,
  `disease` int(11) DEFAULT NULL,
  `gender` enum('male','female','other') DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `blood_group` varchar(10) DEFAULT NULL,
  `status` enum('active','inactive') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `patients`
--

INSERT INTO `patients` (`id`, `name`, `age`, `weight`, `disease`, `gender`, `phone`, `address`, `blood_group`, `status`, `created_at`) VALUES
(22, 'test', 23, 66.90, 6, 'male', '03177990549', 'wara', 'A-', 'active', '2026-04-06 11:08:02'),
(23, 'Shani', 22, 33.00, 6, 'female', '+923214785693', 't-t', 'A-', 'active', '2026-04-06 11:08:36'),
(24, 'Izhan Sajid', 22, 44.80, 6, 'male', '03214785693', 'Kohat', 'A+', 'active', '2026-04-06 11:09:29'),
(25, 'Hammad', 67, 110.00, 19, 'male', '03177990549', 'Kohat', 'AB-', 'active', '2026-04-08 06:59:19'),
(26, 'Zahid', 34, 45.00, 6, 'male', '03214785693', 'kohat', 'A+', 'active', '2026-04-08 07:03:18'),
(28, 'salman ', 22, 68.00, 19, 'male', '0511565120', 'kohat', 'A+', 'active', '2026-04-08 11:43:03'),
(29, 'Sajid Mehmood', 22, 56.00, 13, 'male', '03214785693', 'karachi', 'AB-', 'active', '2026-04-08 11:53:17'),
(30, 'Iona Rojas', 102, 103.00, 13, 'other', '+1 (588) 603-1654', 'Ad reiciendis et qua', 'AB-', 'active', '2026-04-09 11:26:57'),
(31, 'Faith Mayo', 22, 78.00, 13, 'male', '+1 (982) 763-5193', '-----kohat---', 'A+', 'active', '2026-04-09 11:37:14'),
(32, 'Wesley Farrell', 22, 56.00, 15, 'male', '+1 (273) 581-6794', 'kohat', 'A+', 'active', '2026-04-10 05:40:00');

-- --------------------------------------------------------

--
-- Table structure for table `payments`
--

CREATE TABLE `payments` (
  `id` int(11) NOT NULL,
  `appointment_id` int(11) DEFAULT NULL,
  `patient_id` int(11) NOT NULL,
  `doctor_id` int(11) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `payment_method` varchar(50) NOT NULL DEFAULT 'cash',
  `status` varchar(50) NOT NULL DEFAULT 'pending',
  `transaction_id` varchar(100) DEFAULT NULL,
  `payment_date` datetime DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `receptionist_id` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `payment_type` enum('appointment','test') NOT NULL DEFAULT 'appointment',
  `record_test_id` int(11) DEFAULT NULL,
  `lab_assistant_id` int(11) DEFAULT NULL,
  `recorded_by_role` enum('receptionist','lab_assistant') DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `payments`
--

INSERT INTO `payments` (`id`, `appointment_id`, `patient_id`, `doctor_id`, `amount`, `payment_method`, `status`, `transaction_id`, `payment_date`, `notes`, `receptionist_id`, `created_at`, `payment_type`, `record_test_id`, `lab_assistant_id`, `recorded_by_role`) VALUES
(33, 26, 22, 12, 1300.00, 'cash', 'completed', '', '2026-04-06 13:08:15', '', 65, '2026-04-06 11:08:15', 'appointment', NULL, NULL, 'receptionist'),
(34, 27, 23, 12, 1300.00, 'cash', 'completed', '', '2026-04-06 13:08:47', '', 65, '2026-04-06 11:08:47', 'appointment', NULL, NULL, 'receptionist'),
(35, 28, 24, 12, 1300.00, 'cash', 'completed', '', '2026-04-06 13:09:41', '', 65, '2026-04-06 11:09:41', 'appointment', NULL, NULL, 'receptionist'),
(36, 29, 25, 38, 1900.00, 'cash', 'completed', '', '2026-04-08 09:00:54', '', 65, '2026-04-08 07:00:54', 'appointment', NULL, NULL, 'receptionist'),
(37, 30, 26, 38, 1900.00, 'cash', 'refunded', '', '2026-04-08 09:03:39', '', 65, '2026-04-08 07:03:39', 'appointment', NULL, NULL, 'receptionist'),
(38, 31, 28, 38, 1900.00, 'cash', 'completed', '', '2026-04-08 13:43:17', '', 65, '2026-04-08 11:43:17', 'appointment', NULL, NULL, 'receptionist'),
(39, 32, 29, 47, 500.00, 'cash', 'completed', '', '2026-04-08 13:53:31', '', 65, '2026-04-08 11:53:31', 'appointment', NULL, NULL, 'receptionist'),
(40, 0, 28, 38, 500.00, 'cash', 'completed', NULL, '2026-04-09 04:15:09', 'record_id:2|test_id:1', 72, '2026-04-09 11:15:09', 'test', 1, 72, 'lab_assistant'),
(41, 33, 30, 47, 500.00, 'cash', 'completed', '', '2026-04-09 16:27:21', '', 65, '2026-04-09 11:27:21', 'appointment', NULL, NULL, NULL),
(42, 34, 31, 47, 500.00, 'cash', 'completed', '', '2026-04-09 16:37:28', '', 65, '2026-04-09 11:37:28', 'appointment', NULL, NULL, NULL),
(43, 35, 32, 29, 1400.00, 'cash', 'completed', '', '2026-04-10 10:40:11', '', 65, '2026-04-10 05:40:11', 'appointment', NULL, NULL, NULL),
(44, 0, 32, 29, 1500.00, 'cash', 'completed', NULL, '2026-04-09 23:48:23', 'record_id:4|tests:3|total:1500', NULL, '2026-04-10 06:48:23', 'test', 2, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `prescriptions`
--

CREATE TABLE `prescriptions` (
  `id` int(11) NOT NULL,
  `record_id` int(11) DEFAULT NULL,
  `medicine_name` varchar(100) DEFAULT NULL,
  `dosage` varchar(100) DEFAULT NULL,
  `duration` varchar(100) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `has_tests` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `prescriptions`
--

INSERT INTO `prescriptions` (`id`, `record_id`, `medicine_name`, `dosage`, `duration`, `notes`, `has_tests`) VALUES
(1, 1, 'Robert Albert', 'Nostrum explicabo E', 'Aut nisi ut officia ', 'Delectus ex tempora', 0),
(2, 1, 'Orla Blankenship', 'Qui quod est nisi in', 'Molestiae et minima ', 'Sit qui modi quasi l', 0),
(9, 4, 'panadol', '5mg', '3 days ', '--', 0);

-- --------------------------------------------------------

--
-- Table structure for table `records`
--

CREATE TABLE `records` (
  `id` int(11) NOT NULL,
  `patient_id` int(11) DEFAULT NULL,
  `doctor_id` int(11) DEFAULT NULL,
  `appointment_id` int(11) DEFAULT NULL,
  `visit_date` datetime DEFAULT NULL,
  `symptoms` text DEFAULT NULL,
  `diagnosis` text DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `has_tests` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `records`
--

INSERT INTO `records` (`id`, `patient_id`, `doctor_id`, `appointment_id`, `visit_date`, `symptoms`, `diagnosis`, `notes`, `created_at`, `has_tests`) VALUES
(1, 25, 38, 29, '2026-04-08 09:08:12', 'Voluptatem eos quos ', 'Dolore excepturi ex ', 'Delectus eum aliqui', '2026-04-08 07:08:12', 0),
(2, 28, 38, 31, '2026-04-09 23:16:35', 'test', 'test', 'test', '2026-04-09 18:16:35', 1),
(3, 30, 47, 33, '2026-04-09 16:42:59', 'Rerum repudiandae qu', 'Fuga Sit natus ut p', 'Et at quia quas sit ', '2026-04-09 11:42:59', 1),
(4, 32, 29, 35, '2026-04-10 11:18:48', 'Aut hic labore ea ea', 'In omnis sint aperia', 'Aliquip iusto conseq', '2026-04-10 06:18:48', 1);

-- --------------------------------------------------------

--
-- Table structure for table `record_tests`
--

CREATE TABLE `record_tests` (
  `id` int(11) NOT NULL,
  `record_id` int(11) NOT NULL,
  `test_id` int(11) NOT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `payment_status` enum('pending','paid') DEFAULT 'pending',
  `result` text DEFAULT NULL,
  `status` enum('pending','sample_collected','completed') DEFAULT 'pending',
  `wait_time` int(11) DEFAULT 0,
  `payment_id` int(11) DEFAULT NULL,
  `completed_at` datetime DEFAULT NULL,
  `interpretation` varchar(100) DEFAULT NULL,
  `remarks` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `record_tests`
--

INSERT INTO `record_tests` (`id`, `record_id`, `test_id`, `notes`, `created_at`, `payment_status`, `result`, `status`, `wait_time`, `payment_id`, `completed_at`, `interpretation`, `remarks`) VALUES
(1, 2, 1, 'Initial CBC for Salman', '2026-04-09 11:12:39', 'paid', '12', 'completed', 15, 40, '2026-04-09 04:15:32', NULL, NULL),
(2, 4, 2, 'Consequuntur maiores', '2026-04-10 06:18:48', 'paid', '76', 'completed', 45, 44, '2026-04-10 00:18:18', 'Normal', '---'),
(3, 4, 18, 'Consequuntur maiores', '2026-04-10 06:18:48', 'paid', '1.8', 'completed', 45, 44, '2026-04-10 00:27:57', 'Normal', '-----'),
(4, 4, 28, 'Consequuntur maiores', '2026-04-10 06:18:48', 'paid', '5.00', 'completed', 45, 44, '2026-04-10 00:28:19', 'Borderline', '-----');

-- --------------------------------------------------------

--
-- Table structure for table `roles`
--

CREATE TABLE `roles` (
  `id` int(11) NOT NULL,
  `name` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `roles`
--

INSERT INTO `roles` (`id`, `name`) VALUES
(1, 'admin'),
(2, 'doctor'),
(3, 'receptionist'),
(4, 'lab_assistant');

-- --------------------------------------------------------

--
-- Table structure for table `staff`
--

CREATE TABLE `staff` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `position` varchar(100) DEFAULT NULL,
  `shift` varchar(50) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `staff`
--

INSERT INTO `staff` (`id`, `user_id`, `position`, `shift`, `address`, `created_at`) VALUES
(1, 65, 'Receptionist', 'Morning', 'Main Reception Desk', '2026-04-02 09:27:52');

-- --------------------------------------------------------

--
-- Table structure for table `tests`
--

CREATE TABLE `tests` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `icon` varchar(50) DEFAULT NULL,
  `status` enum('active','inactive') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `fee` decimal(10,2) DEFAULT 500.00,
  `reference_range_male` varchar(100) DEFAULT NULL,
  `reference_range_female` varchar(100) DEFAULT NULL,
  `unit` varchar(20) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tests`
--

INSERT INTO `tests` (`id`, `name`, `description`, `icon`, `status`, `created_at`, `updated_at`, `fee`, `reference_range_male`, `reference_range_female`, `unit`) VALUES
(1, 'CBC', 'Complete Blood Count test', 'fa-vial', 'active', '2026-04-08 06:54:53', '2026-04-09 11:20:13', 500.00, '13.5-17.5', '12.0-15.5', 'g/dL'),
(2, 'Blood Sugar Fasting', 'Measures fasting glucose level', 'fa-tint', 'active', '2026-04-08 06:54:53', '2026-04-09 11:20:13', 500.00, '70-99', '70-99', 'mg/dL'),
(3, 'Blood Sugar Random', 'Random glucose level test', 'fa-tint', 'active', '2026-04-08 06:54:53', '2026-04-09 11:20:13', 500.00, '70-99', '70-99', 'mg/dL'),
(4, 'HbA1c', 'Average blood sugar over 3 months', 'fa-chart-line', 'active', '2026-04-08 06:54:53', '2026-04-10 06:32:32', 500.00, '4.0-5.6', '4.0-5.6', '%'),
(5, 'Lipid Profile', 'Cholesterol and triglycerides test', 'fa-heart', 'active', '2026-04-08 06:54:53', '2026-04-10 06:32:32', 500.00, '<200 (Desirable)', '<200 (Desirable)', 'mg/dL'),
(6, 'Liver Function Test', 'Checks liver health', 'fa-procedures', 'active', '2026-04-08 06:54:53', '2026-04-10 06:32:32', 500.00, '10-40', '7-35', 'U/L'),
(7, 'Kidney Function Test', 'Checks kidney performance', 'fa-filter', 'active', '2026-04-08 06:54:53', '2026-04-10 06:32:32', 500.00, '8-20', '8-20', 'mg/dL'),
(8, 'Urine Complete', 'Urine analysis test', 'fa-flask', 'active', '2026-04-08 06:54:53', '2026-04-10 06:32:32', 500.00, 'Normal (No protein, glucose, ketones)', 'Normal (No protein, glucose, ketones)', '-'),
(9, 'Serum Creatinine', 'Measures kidney function', 'fa-vial', 'active', '2026-04-08 06:54:53', '2026-04-09 11:20:13', 500.00, '0.7-1.3', '0.6-1.1', 'mg/dL'),
(10, 'Blood Urea', 'Checks waste in blood', 'fa-vial', 'active', '2026-04-08 06:54:53', '2026-04-10 06:32:32', 500.00, '15-45', '15-45', 'mg/dL'),
(11, 'Thyroid Profile', 'Thyroid hormone levels', 'fa-brain', 'active', '2026-04-08 06:54:53', '2026-04-10 06:32:32', 500.00, '0.4-4.0', '0.4-4.0', 'mIU/L'),
(12, 'TSH', 'Thyroid Stimulating Hormone test', 'fa-brain', 'active', '2026-04-08 06:54:53', '2026-04-10 06:32:32', 500.00, '0.4-4.0', '0.4-4.0', 'mIU/L'),
(13, 'T3', 'Triiodothyronine hormone test', 'fa-brain', 'active', '2026-04-08 06:54:53', '2026-04-10 06:32:32', 500.00, '80-200', '80-200', 'ng/dL'),
(14, 'T4', 'Thyroxine hormone test', 'fa-brain', 'active', '2026-04-08 06:54:53', '2026-04-10 06:32:32', 500.00, '5.0-12.0', '5.0-12.0', 'µg/dL'),
(15, 'Calcium Test', 'Measures calcium level', 'fa-bone', 'active', '2026-04-08 06:54:53', '2026-04-10 06:32:32', 500.00, '8.6-10.2', '8.6-10.2', 'mg/dL'),
(16, 'Vitamin D Test', 'Checks vitamin D level', 'fa-sun', 'active', '2026-04-08 06:54:53', '2026-04-10 06:32:32', 500.00, '20-50 (Normal)', '20-50 (Normal)', 'ng/mL'),
(17, 'Vitamin B12', 'Measures B12 level', 'fa-capsules', 'active', '2026-04-08 06:54:53', '2026-04-10 06:32:32', 500.00, '200-900', '200-900', 'pg/mL'),
(18, 'CRP', 'C-Reactive Protein test for inflammation', 'fa-fire', 'active', '2026-04-08 06:54:53', '2026-04-10 06:32:32', 500.00, '<3.0', '<3.0', 'mg/L'),
(19, 'ESR', 'Erythrocyte Sedimentation Rate', 'fa-hourglass', 'active', '2026-04-08 06:54:53', '2026-04-10 06:32:32', 500.00, '0-15', '0-20', 'mm/hr'),
(20, 'Dengue Test', 'Detects dengue infection', 'fa-bug', 'active', '2026-04-08 06:54:53', '2026-04-10 06:32:32', 500.00, 'Negative', 'Negative', '-'),
(21, 'Malaria Test', 'Detects malaria parasite', 'fa-bug', 'active', '2026-04-08 06:54:53', '2026-04-10 06:32:32', 500.00, 'Negative', 'Negative', '-'),
(22, 'Typhoid Test', 'Detects typhoid fever', 'fa-thermometer', 'active', '2026-04-08 06:54:53', '2026-04-10 06:32:32', 500.00, 'Negative', 'Negative', '-'),
(23, 'COVID-19 PCR', 'Coronavirus detection test', 'fa-virus', 'active', '2026-04-08 06:54:53', '2026-04-10 06:32:32', 500.00, 'Negative', 'Negative', '-'),
(24, 'Hepatitis B', 'Detects Hepatitis B virus', 'fa-shield-virus', 'active', '2026-04-08 06:54:53', '2026-04-10 06:32:32', 500.00, 'Negative', 'Negative', '-'),
(25, 'Hepatitis C', 'Detects Hepatitis C virus', 'fa-shield-virus', 'active', '2026-04-08 06:54:53', '2026-04-10 06:32:32', 500.00, 'Negative', 'Negative', '-'),
(26, 'X-Ray', 'Radiology imaging test', 'fa-x-ray', 'active', '2026-04-08 06:54:53', '2026-04-08 06:54:53', 500.00, NULL, NULL, NULL),
(27, 'Ultrasound', 'Imaging using sound waves', 'fa-wave-square', 'active', '2026-04-08 06:54:53', '2026-04-08 06:54:53', 500.00, NULL, NULL, NULL),
(28, 'ECG', 'Heart electrical activity test', 'fa-heartbeat', 'active', '2026-04-08 06:54:53', '2026-04-08 06:54:53', 500.00, NULL, NULL, NULL),
(29, 'Echo', 'Heart ultrasound test', 'fa-heart', 'active', '2026-04-08 06:54:53', '2026-04-08 06:54:53', 500.00, NULL, NULL, NULL),
(30, 'MRI', 'Magnetic Resonance Imaging', 'fa-magnet', 'active', '2026-04-08 06:54:53', '2026-04-08 06:54:53', 500.00, NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `role_id` int(11) DEFAULT NULL,
  `name` varchar(100) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `password` varchar(100) DEFAULT NULL,
  `status` enum('active','inactive') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `role_id`, `name`, `email`, `phone`, `password`, `status`, `created_at`, `updated_at`) VALUES
(1, 1, 'Admin User', 'admin@prms.com', '03000000001', '123456', 'active', '2026-04-01 05:49:22', '2026-04-01 05:49:22'),
(3, 2, 'Dr. Ahmed Hassan', 'ahmed.hassan@prms.com', '03001111001', '123456', 'active', '2026-04-01 05:50:57', '2026-04-01 05:50:57'),
(4, 2, 'Dr. Fatima Khalid', 'fatima.khalid@prms.com', '03001111002', '123456', 'active', '2026-04-01 05:50:57', '2026-04-01 05:50:57'),
(5, 2, 'Dr. Omar Farooq', 'omar.farooq@prms.com', '03001111003', '123456', 'active', '2026-04-01 05:50:57', '2026-04-01 05:50:57'),
(6, 2, 'Dr. Sara Mahmood', 'sara.mahmood@prms.com', '03001111004', '123456', 'active', '2026-04-01 05:50:57', '2026-04-01 05:50:57'),
(7, 2, 'Dr. Bilal Ahmed', 'bilal.ahmed@prms.com', '03001111005', '123456', 'active', '2026-04-01 05:50:57', '2026-04-01 05:50:57'),
(8, 2, 'Dr. Ayesha Khan', 'ayesha.khan@prms.com', '03001111006', '123456', 'active', '2026-04-01 05:50:57', '2026-04-01 05:50:57'),
(9, 2, 'Dr. Khalid Rehman', 'khalid.rehman@prms.com', '03001111007', '123456', 'active', '2026-04-01 05:50:57', '2026-04-01 05:50:57'),
(10, 2, 'Dr. Nadia Anwar', 'nadia.anwar@prms.com', '03001111008', '123456', 'active', '2026-04-01 05:50:57', '2026-04-01 05:50:57'),
(11, 2, 'Dr. Hassan Raza', 'hassan.raza@prms.com', '03001111009', '123456', 'active', '2026-04-01 05:50:57', '2026-04-01 05:50:57'),
(12, 2, 'Dr. Zara Tariq', 'zara.tariq@prms.com', '03001111010', '123456', 'active', '2026-04-01 05:50:57', '2026-04-01 05:50:57'),
(13, 2, 'Dr. Rashid Mehmood', 'rashid.mehmood@prms.com', '03001111011', '123456', 'active', '2026-04-01 05:50:57', '2026-04-01 05:50:57'),
(14, 2, 'Dr. Sana Iqbal', 'sana.iqbal@prms.com', '03001111012', '123456', 'active', '2026-04-01 05:50:57', '2026-04-01 05:50:57'),
(15, 2, 'Dr. Tariq Mahmood', 'tariq.mahmood@prms.com', '03001111013', '123456', 'active', '2026-04-01 05:50:57', '2026-04-01 05:50:57'),
(16, 2, 'Dr. Fariha Ali', 'fariha.ali@prms.com', '03001111014', '123456', 'active', '2026-04-01 05:50:57', '2026-04-01 05:50:57'),
(17, 2, 'Dr. Asad Raza', 'asad.raza@prms.com', '03001111015', '123456', 'active', '2026-04-01 05:50:57', '2026-04-01 05:50:57'),
(18, 2, 'Dr. Mariam Hassan', 'mariam.hassan@prms.com', '03001111016', '123456', 'active', '2026-04-01 05:50:57', '2026-04-01 05:50:57'),
(19, 2, 'Dr. Naveed Akhtar', 'naveed.akhtar@prms.com', '03001111017', '123456', 'active', '2026-04-01 05:50:57', '2026-04-01 05:50:57'),
(20, 2, 'Dr. Saima Khalid', 'saima.khalid@prms.com', '03001111018', '123456', 'active', '2026-04-01 05:50:57', '2026-04-01 05:50:57'),
(21, 2, 'Dr. Imran Ali', 'imran.ali@prms.com', '03001111019', '123456', 'active', '2026-04-01 05:50:57', '2026-04-01 05:50:57'),
(22, 2, 'Dr. Rabia Nawaz', 'rabia.nawaz@prms.com', '03001111020', '123456', 'active', '2026-04-01 05:50:57', '2026-04-01 05:50:57'),
(23, 2, 'Dr. Shahid Khan', 'shahid.khan@prms.com', '03001111021', '123456', 'active', '2026-04-01 05:50:57', '2026-04-01 05:50:57'),
(24, 2, 'Dr. Hina Riaz', 'hina.riaz@prms.com', '03001111022', '123456', 'active', '2026-04-01 05:50:57', '2026-04-01 05:50:57'),
(25, 2, 'Dr. Faisal Javed', 'faisal.javed@prms.com', '03001111023', '123456', 'active', '2026-04-01 05:50:57', '2026-04-01 05:50:57'),
(26, 2, 'Dr. Areeba Shafiq', 'areeba.shafiq@prms.com', '03001111024', '123456', 'active', '2026-04-01 05:50:57', '2026-04-01 05:50:57'),
(27, 2, 'Dr. Waqas Ahmed', 'waqas.ahmed@prms.com', '03001111025', '123456', 'active', '2026-04-01 05:50:57', '2026-04-01 05:50:57'),
(28, 2, 'Dr. Mehreen Aslam', 'mehreen.aslam@prms.com', '03001111026', '123456', 'active', '2026-04-01 05:50:57', '2026-04-01 05:50:57'),
(29, 2, 'Dr. Salman Mirza', 'salman.mirza@prms.com', '03001111027', '123456', 'active', '2026-04-01 05:50:57', '2026-04-01 05:50:57'),
(30, 2, 'Dr. Kiran Zafar', 'kiran.zafar@prms.com', '03001111028', '123456', 'active', '2026-04-01 05:50:57', '2026-04-01 05:50:57'),
(31, 2, 'Dr. Aamir Sohail', 'aamir.sohail@prms.com', '03001111029', '123456', 'active', '2026-04-01 05:50:57', '2026-04-01 05:50:57'),
(32, 2, 'Dr. Sadia Tariq', 'sadia.tariq@prms.com', '03001111030', '123456', 'active', '2026-04-01 05:50:57', '2026-04-01 05:50:57'),
(33, 2, 'Dr. Usman Chaudhry', 'usman.chaudhry@prms.com', '03001111031', '123456', 'active', '2026-04-01 05:50:57', '2026-04-01 05:50:57'),
(34, 2, 'Dr. Nimra Sheikh', 'nimra.sheikh@prms.com', '03001111032', '123456', 'active', '2026-04-01 05:50:57', '2026-04-01 05:50:57'),
(35, 2, 'Dr. Farhan Akhtar', 'farhan.akhtar@prms.com', '03001111033', '123456', 'active', '2026-04-01 05:50:57', '2026-04-01 05:50:57'),
(36, 2, 'Dr. Eman Tariq', 'eman.tariq@prms.com', '03001111034', '123456', 'active', '2026-04-01 05:50:57', '2026-04-01 05:50:57'),
(37, 2, 'Dr. Zeeshan Haider', 'zeeshan.haider@prms.com', '03001111035', '123456', 'active', '2026-04-01 05:50:57', '2026-04-01 05:50:57'),
(38, 2, 'Dr. Aleena Nadeem', 'aleena.nadeem@prms.com', '03001111036', '123456', 'active', '2026-04-01 05:50:57', '2026-04-01 05:50:57'),
(39, 2, 'Dr. Murtaza Abbas', 'murtaza.abbas@prms.com', '03001111037', '123456', 'active', '2026-04-01 05:50:57', '2026-04-01 05:50:57'),
(40, 2, 'Dr. Uzma Asif', 'uzma.asif@prms.com', '03001111038', '123456', 'active', '2026-04-01 05:50:57', '2026-04-01 05:50:57'),
(41, 2, 'Dr. Kamran Bhatti', 'kamran.bhatti@prms.com', '03001111039', '123456', 'active', '2026-04-01 05:50:57', '2026-04-01 05:50:57'),
(42, 2, 'Dr. Bushra Anwar', 'bushra.anwar@prms.com', '03001111040', '123456', 'active', '2026-04-01 05:50:57', '2026-04-01 05:50:57'),
(65, 3, 'Receptionist Ali', 'reception1@gmail.com', '03001234567', '123456', 'active', '2026-04-02 09:26:04', '2026-04-02 09:26:04'),
(66, 3, 'Receptionist Evening', 'reception2@gmail.com', '03009998877', '123456', 'active', '2026-04-02 09:30:52', '2026-04-02 09:30:52'),
(67, 2, 'Izhan Sajid', 'izhan@gmail.com', '03214785693', '123', 'active', '2026-04-03 07:15:14', NULL),
(68, 2, 'Test 2', 'test2@gmail.com', '032147856432', '123', 'active', '2026-04-03 11:14:33', NULL),
(69, 2, 'Muhammad Hasan``', 'hassan@gmail.com', '03369211850', '123', 'active', '2026-04-04 13:13:41', NULL),
(70, 2, 'Hammad', 'hamad@gmail.com', '036446649685', '123', 'active', '2026-04-08 07:11:00', NULL),
(71, 2, 'Kareem Whitaker', 'kareem@gmail.com', '+1 (533) 113-5934', '123', 'active', '2026-04-08 11:50:51', NULL),
(72, 4, 'Lab Assistant', 'lab.assistant@prms.com', '03005555555', '123456', 'active', '2026-04-09 06:56:39', '2026-04-09 06:56:39');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `announcements`
--
ALTER TABLE `announcements`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `appointments`
--
ALTER TABLE `appointments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `patient_id` (`patient_id`),
  ADD KEY `doctor_id` (`doctor_id`),
  ADD KEY `category_id` (`category_id`);

--
-- Indexes for table `call_appointments`
--
ALTER TABLE `call_appointments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `disease_id` (`disease_id`),
  ADD KEY `call_appointments_ibfk_1` (`doctor_id`);

--
-- Indexes for table `categories`
--
ALTER TABLE `categories`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `doctors`
--
ALTER TABLE `doctors`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `fk_doctor_category` (`category_id`);

--
-- Indexes for table `doctor_schedules`
--
ALTER TABLE `doctor_schedules`
  ADD PRIMARY KEY (`id`),
  ADD KEY `doctor_id` (`doctor_id`);

--
-- Indexes for table `patients`
--
ALTER TABLE `patients`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `payments`
--
ALTER TABLE `payments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `appointment_id` (`appointment_id`),
  ADD KEY `patient_id` (`patient_id`),
  ADD KEY `idx_payment_type` (`payment_type`),
  ADD KEY `idx_recorded_by_role` (`recorded_by_role`);

--
-- Indexes for table `prescriptions`
--
ALTER TABLE `prescriptions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `record_id` (`record_id`);

--
-- Indexes for table `records`
--
ALTER TABLE `records`
  ADD PRIMARY KEY (`id`),
  ADD KEY `patient_id` (`patient_id`),
  ADD KEY `doctor_id` (`doctor_id`),
  ADD KEY `appointment_id` (`appointment_id`);

--
-- Indexes for table `record_tests`
--
ALTER TABLE `record_tests`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_payment_id` (`payment_id`);

--
-- Indexes for table `roles`
--
ALTER TABLE `roles`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `staff`
--
ALTER TABLE `staff`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `tests`
--
ALTER TABLE `tests`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD KEY `role_id` (`role_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `announcements`
--
ALTER TABLE `announcements`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `appointments`
--
ALTER TABLE `appointments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=36;

--
-- AUTO_INCREMENT for table `call_appointments`
--
ALTER TABLE `call_appointments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=20;

--
-- AUTO_INCREMENT for table `categories`
--
ALTER TABLE `categories`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=24;

--
-- AUTO_INCREMENT for table `doctors`
--
ALTER TABLE `doctors`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=48;

--
-- AUTO_INCREMENT for table `doctor_schedules`
--
ALTER TABLE `doctor_schedules`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=166;

--
-- AUTO_INCREMENT for table `patients`
--
ALTER TABLE `patients`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=33;

--
-- AUTO_INCREMENT for table `payments`
--
ALTER TABLE `payments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=47;

--
-- AUTO_INCREMENT for table `prescriptions`
--
ALTER TABLE `prescriptions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `records`
--
ALTER TABLE `records`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `record_tests`
--
ALTER TABLE `record_tests`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `roles`
--
ALTER TABLE `roles`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `staff`
--
ALTER TABLE `staff`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `tests`
--
ALTER TABLE `tests`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=31;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=73;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `appointments`
--
ALTER TABLE `appointments`
  ADD CONSTRAINT `appointments_ibfk_1` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`id`),
  ADD CONSTRAINT `appointments_ibfk_2` FOREIGN KEY (`doctor_id`) REFERENCES `doctors` (`id`),
  ADD CONSTRAINT `appointments_ibfk_3` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `call_appointments`
--
ALTER TABLE `call_appointments`
  ADD CONSTRAINT `call_appointments_ibfk_1` FOREIGN KEY (`doctor_id`) REFERENCES `doctors` (`id`),
  ADD CONSTRAINT `call_appointments_ibfk_2` FOREIGN KEY (`disease_id`) REFERENCES `categories` (`id`);

--
-- Constraints for table `doctors`
--
ALTER TABLE `doctors`
  ADD CONSTRAINT `doctors_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `fk_doctor_category` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `doctor_schedules`
--
ALTER TABLE `doctor_schedules`
  ADD CONSTRAINT `doctor_schedules_ibfk_1` FOREIGN KEY (`doctor_id`) REFERENCES `doctors` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `payments`
--
ALTER TABLE `payments`
  ADD CONSTRAINT `payments_ibfk_2` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `prescriptions`
--
ALTER TABLE `prescriptions`
  ADD CONSTRAINT `prescriptions_ibfk_1` FOREIGN KEY (`record_id`) REFERENCES `records` (`id`);

--
-- Constraints for table `records`
--
ALTER TABLE `records`
  ADD CONSTRAINT `records_ibfk_1` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`id`),
  ADD CONSTRAINT `records_ibfk_2` FOREIGN KEY (`doctor_id`) REFERENCES `doctors` (`id`),
  ADD CONSTRAINT `records_ibfk_3` FOREIGN KEY (`appointment_id`) REFERENCES `appointments` (`id`);

--
-- Constraints for table `staff`
--
ALTER TABLE `staff`
  ADD CONSTRAINT `staff_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `users`
--
ALTER TABLE `users`
  ADD CONSTRAINT `users_ibfk_1` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
