-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Apr 02, 2026 at 09:32 AM
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

--
-- Dumping data for table `announcements`
--

INSERT INTO `announcements` (`id`, `title`, `message`, `target_audience`, `status`, `start_at`, `expiry_at`, `created_at`, `updated_at`) VALUES
(4, 'system maintenance', 'he system will be under maintenance for 2 hours.', 'all', 'active', '2026-04-02 00:16:54', '2026-04-02 01:16:54', '2026-04-02 07:16:54', '2026-04-02 07:16:54');

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
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `appointments`
--

INSERT INTO `appointments` (`id`, `patient_id`, `doctor_id`, `appointment_date`, `symptoms`, `category_id`, `consultation_fee`, `shift_type`, `time_slot`, `patient_number`, `status`, `created_at`) VALUES
(9, 7, 5, '2026-04-01 09:30:00', 'pain in this back bone', 3, 1200.00, 'Morning', NULL, 0, 'completed', '2026-04-01 07:18:59'),
(12, 7, 5, '2026-04-03 09:00:00', 'pain in head bone', 3, 1200.00, 'Morning', NULL, 0, 'cancelled', '2026-04-01 10:44:07'),
(14, 9, 6, '2026-04-02 16:00:00', 'pain in head', 3, 1300.00, 'Evening', NULL, 0, 'cancelled', '2026-04-02 06:40:12');

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
(1, 'Cardiologist', 'Heart and cardiovascular diseases specialist', 'fa-heartbeat', 'active', '2026-04-01 05:50:08', '2026-04-01 05:50:08'),
(2, 'Neurologist', 'Brain, nerves and nervous system disorders specialist', 'fa-brain', 'active', '2026-04-01 05:50:08', '2026-04-01 05:50:08'),
(3, 'Ophthalmologist', 'Eye diseases and vision problems specialist', 'fa-eye', 'active', '2026-04-01 05:50:08', '2026-04-01 05:50:08'),
(4, 'ENT Specialist', 'Ear, nose and throat diseases specialist', 'fa-ear-deaf', 'active', '2026-04-01 05:50:08', '2026-04-01 05:50:08'),
(5, 'Dermatologist', 'Skin, hair and nail disorders specialist', 'fa-hand-sparkles', 'active', '2026-04-01 05:50:08', '2026-04-01 05:50:08'),
(6, 'Pulmonologist', 'Lung and respiratory diseases specialist', 'fa-lungs', 'active', '2026-04-01 05:50:08', '2026-04-01 05:50:08'),
(7, 'Gastroenterologist', 'Digestive system disorders specialist', 'fa-stomach', 'active', '2026-04-01 05:50:08', '2026-04-01 05:50:08'),
(8, 'Orthopedic Surgeon', 'Bone, joint and muscle disorders specialist', 'fa-bone', 'active', '2026-04-01 05:50:08', '2026-04-01 05:50:08'),
(9, 'Endocrinologist', 'Hormone and metabolic disorders specialist', 'fa-droplet', 'active', '2026-04-01 05:50:08', '2026-04-01 05:50:08'),
(10, 'Infectious Disease Specialist', 'Fever and infectious diseases specialist', 'fa-virus', 'active', '2026-04-01 05:50:08', '2026-04-01 05:50:08'),
(11, 'Pediatrician', 'Child health and diseases specialist', 'fa-child', 'active', '2026-04-01 05:50:08', '2026-04-01 05:50:08'),
(12, 'Psychiatrist', 'Mental health disorders specialist', 'fa-brain', 'active', '2026-04-01 05:50:08', '2026-04-01 05:50:08'),
(13, 'Nephrologist', 'Kidney diseases specialist', 'fa-filter', 'active', '2026-04-01 05:50:08', '2026-04-01 05:50:08'),
(14, 'Urologist', 'Urinary tract and male reproductive system specialist', 'fa-bladder', 'active', '2026-04-01 05:50:08', '2026-04-01 05:50:08'),
(15, 'Gynecologist', 'Women reproductive health specialist', 'fa-female', 'active', '2026-04-01 05:50:08', '2026-04-01 05:50:08'),
(16, 'Rheumatologist', 'Joint and autoimmune diseases specialist', 'fa-hand-holding-heart', 'active', '2026-04-01 05:50:08', '2026-04-01 05:50:08'),
(17, 'Allergy Specialist', 'Allergies and immune system disorders specialist', 'fa-allergies', 'active', '2026-04-01 05:50:08', '2026-04-01 05:50:08'),
(18, 'Hematologist', 'Blood disorders specialist', 'fa-tint', 'active', '2026-04-01 05:50:08', '2026-04-01 05:50:08'),
(19, 'Oncologist', 'Cancer and tumors specialist', 'fa-ribbon', 'active', '2026-04-01 05:50:08', '2026-04-01 05:50:08'),
(20, 'Geriatrician', 'Elderly health care specialist', 'fa-user-clock', 'active', '2026-04-01 05:50:08', '2026-04-01 05:50:08'),
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
(41, 63, 'test', 21, 'active', 500.00, 20, 0, NULL, NULL, '2026-04-01 11:39:00', '2026-04-01 11:39:00'),
(42, 64, 'test', 22, 'active', 500.00, 20, 0, NULL, NULL, '2026-04-01 11:52:51', '2026-04-01 11:52:51');

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
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `doctor_schedules`
--

INSERT INTO `doctor_schedules` (`id`, `doctor_id`, `day_of_week`, `shift_type`, `start_time`, `end_time`, `max_appointments`, `status`, `created_at`, `updated_at`) VALUES
(1, 1, 'Monday', 'Morning', '09:00:00', '13:00:00', 15, 'active', '2026-04-01 06:01:02', '2026-04-01 06:01:02'),
(2, 1, 'Wednesday', 'Morning', '09:00:00', '13:00:00', 15, 'active', '2026-04-01 06:01:02', '2026-04-01 06:01:02'),
(3, 1, 'Friday', 'Morning', '09:00:00', '13:00:00', 15, 'active', '2026-04-01 06:01:02', '2026-04-01 06:01:02'),
(4, 2, 'Tuesday', 'Morning', '09:00:00', '13:00:00', 12, 'active', '2026-04-01 06:01:02', '2026-04-01 06:01:02'),
(5, 2, 'Thursday', 'Morning', '09:00:00', '13:00:00', 12, 'active', '2026-04-01 06:01:02', '2026-04-01 06:01:02'),
(6, 2, 'Saturday', 'Morning', '10:00:00', '14:00:00', 10, 'active', '2026-04-01 06:01:02', '2026-04-01 06:01:02'),
(7, 3, 'Monday', 'Afternoon', '14:00:00', '18:00:00', 10, 'active', '2026-04-01 06:01:02', '2026-04-01 06:01:02'),
(8, 3, 'Wednesday', 'Afternoon', '14:00:00', '18:00:00', 10, 'active', '2026-04-01 06:01:02', '2026-04-01 06:01:02'),
(9, 3, 'Friday', 'Afternoon', '14:00:00', '18:00:00', 10, 'active', '2026-04-01 06:01:02', '2026-04-01 06:01:02'),
(10, 4, 'Tuesday', 'Morning', '09:00:00', '13:00:00', 12, 'active', '2026-04-01 06:01:02', '2026-04-01 06:01:02'),
(11, 4, 'Thursday', 'Morning', '09:00:00', '13:00:00', 12, 'active', '2026-04-01 06:01:02', '2026-04-01 06:01:02'),
(12, 4, 'Saturday', 'Morning', '10:00:00', '14:00:00', 8, 'active', '2026-04-01 06:01:02', '2026-04-01 06:01:02'),
(13, 5, 'Monday', 'Morning', '09:00:00', '13:00:00', 15, 'active', '2026-04-01 06:01:02', '2026-04-01 06:01:02'),
(14, 5, 'Wednesday', 'Morning', '09:00:00', '13:00:00', 15, 'active', '2026-04-01 06:01:02', '2026-04-01 06:01:02'),
(15, 5, 'Friday', 'Morning', '09:00:00', '13:00:00', 15, 'active', '2026-04-01 06:01:02', '2026-04-01 06:01:02'),
(16, 6, 'Tuesday', 'Evening', '16:00:00', '20:00:00', 10, 'active', '2026-04-01 06:01:02', '2026-04-01 06:01:02'),
(17, 6, 'Thursday', 'Evening', '16:00:00', '20:00:00', 10, 'active', '2026-04-01 06:01:02', '2026-04-01 06:01:02'),
(18, 6, 'Saturday', 'Morning', '10:00:00', '14:00:00', 8, 'active', '2026-04-01 06:01:02', '2026-04-01 06:01:02'),
(19, 7, 'Monday', 'Morning', '09:00:00', '13:00:00', 12, 'active', '2026-04-01 06:01:02', '2026-04-01 06:01:02'),
(20, 7, 'Tuesday', 'Afternoon', '14:00:00', '18:00:00', 10, 'active', '2026-04-01 06:01:02', '2026-04-01 06:01:02'),
(21, 7, 'Thursday', 'Morning', '09:00:00', '13:00:00', 12, 'active', '2026-04-01 06:01:02', '2026-04-01 06:01:02'),
(22, 7, 'Friday', 'Afternoon', '14:00:00', '18:00:00', 10, 'active', '2026-04-01 06:01:02', '2026-04-01 06:01:02'),
(23, 8, 'Wednesday', 'Morning', '09:00:00', '13:00:00', 15, 'active', '2026-04-01 06:01:02', '2026-04-01 06:01:02'),
(24, 8, 'Thursday', 'Afternoon', '14:00:00', '18:00:00', 10, 'active', '2026-04-01 06:01:02', '2026-04-01 06:01:02'),
(25, 8, 'Saturday', 'Morning', '10:00:00', '14:00:00', 10, 'active', '2026-04-01 06:01:02', '2026-04-01 06:01:02'),
(26, 9, 'Monday', 'Morning', '09:00:00', '13:00:00', 15, 'active', '2026-04-01 06:01:02', '2026-04-01 06:01:02'),
(27, 9, 'Wednesday', 'Morning', '09:00:00', '13:00:00', 15, 'active', '2026-04-01 06:01:02', '2026-04-01 06:01:02'),
(28, 9, 'Friday', 'Morning', '09:00:00', '13:00:00', 15, 'active', '2026-04-01 06:01:02', '2026-04-01 06:01:02'),
(29, 10, 'Tuesday', 'Afternoon', '14:00:00', '18:00:00', 10, 'active', '2026-04-01 06:01:02', '2026-04-01 06:01:02'),
(30, 10, 'Thursday', 'Afternoon', '14:00:00', '18:00:00', 10, 'active', '2026-04-01 06:01:02', '2026-04-01 06:01:02'),
(31, 10, 'Saturday', 'Morning', '10:00:00', '14:00:00', 8, 'active', '2026-04-01 06:01:02', '2026-04-01 06:01:02'),
(32, 11, 'Monday', 'Morning', '09:00:00', '13:00:00', 12, 'active', '2026-04-01 06:01:02', '2026-04-01 06:01:02'),
(33, 11, 'Wednesday', 'Morning', '09:00:00', '13:00:00', 12, 'active', '2026-04-01 06:01:02', '2026-04-01 06:01:02'),
(34, 11, 'Friday', 'Morning', '09:00:00', '13:00:00', 12, 'active', '2026-04-01 06:01:02', '2026-04-01 06:01:02'),
(35, 12, 'Tuesday', 'Evening', '16:00:00', '20:00:00', 8, 'active', '2026-04-01 06:01:02', '2026-04-01 06:01:02'),
(36, 12, 'Thursday', 'Evening', '16:00:00', '20:00:00', 8, 'active', '2026-04-01 06:01:02', '2026-04-01 06:01:02'),
(37, 12, 'Saturday', 'Morning', '10:00:00', '14:00:00', 10, 'active', '2026-04-01 06:01:02', '2026-04-01 06:01:02'),
(38, 13, 'Monday', 'Morning', '09:00:00', '13:00:00', 15, 'active', '2026-04-01 06:01:02', '2026-04-01 06:01:02'),
(39, 13, 'Tuesday', 'Afternoon', '14:00:00', '18:00:00', 10, 'active', '2026-04-01 06:01:02', '2026-04-01 06:01:02'),
(40, 13, 'Thursday', 'Morning', '09:00:00', '13:00:00', 15, 'active', '2026-04-01 06:01:02', '2026-04-01 06:01:02'),
(41, 13, 'Friday', 'Afternoon', '14:00:00', '18:00:00', 10, 'active', '2026-04-01 06:01:02', '2026-04-01 06:01:02'),
(42, 14, 'Wednesday', 'Morning', '09:00:00', '13:00:00', 12, 'active', '2026-04-01 06:01:02', '2026-04-01 06:01:02'),
(43, 14, 'Thursday', 'Afternoon', '14:00:00', '18:00:00', 10, 'active', '2026-04-01 06:01:02', '2026-04-01 06:01:02'),
(44, 14, 'Saturday', 'Morning', '10:00:00', '14:00:00', 8, 'active', '2026-04-01 06:01:02', '2026-04-01 06:01:02'),
(45, 15, 'Monday', 'Morning', '09:00:00', '13:00:00', 12, 'active', '2026-04-01 06:01:02', '2026-04-01 06:01:02'),
(46, 15, 'Wednesday', 'Morning', '09:00:00', '13:00:00', 12, 'active', '2026-04-01 06:01:02', '2026-04-01 06:01:02'),
(47, 15, 'Friday', 'Morning', '09:00:00', '13:00:00', 12, 'active', '2026-04-01 06:01:02', '2026-04-01 06:01:02'),
(48, 16, 'Tuesday', 'Afternoon', '14:00:00', '18:00:00', 10, 'active', '2026-04-01 06:01:02', '2026-04-01 06:01:02'),
(49, 16, 'Thursday', 'Afternoon', '14:00:00', '18:00:00', 10, 'active', '2026-04-01 06:01:02', '2026-04-01 06:01:02'),
(50, 16, 'Saturday', 'Morning', '10:00:00', '14:00:00', 8, 'active', '2026-04-01 06:01:02', '2026-04-01 06:01:02'),
(51, 17, 'Monday', 'Morning', '09:00:00', '13:00:00', 15, 'active', '2026-04-01 06:01:02', '2026-04-01 06:01:02'),
(52, 17, 'Wednesday', 'Morning', '09:00:00', '13:00:00', 15, 'active', '2026-04-01 06:01:02', '2026-04-01 06:01:02'),
(53, 17, 'Friday', 'Morning', '09:00:00', '13:00:00', 15, 'active', '2026-04-01 06:01:02', '2026-04-01 06:01:02'),
(54, 18, 'Tuesday', 'Evening', '16:00:00', '20:00:00', 10, 'active', '2026-04-01 06:01:02', '2026-04-01 06:01:02'),
(55, 18, 'Thursday', 'Evening', '16:00:00', '20:00:00', 10, 'active', '2026-04-01 06:01:02', '2026-04-01 06:01:02'),
(56, 18, 'Saturday', 'Morning', '10:00:00', '14:00:00', 8, 'active', '2026-04-01 06:01:02', '2026-04-01 06:01:02'),
(57, 19, 'Monday', 'Morning', '09:00:00', '13:00:00', 12, 'active', '2026-04-01 06:01:02', '2026-04-01 06:01:02'),
(58, 19, 'Tuesday', 'Afternoon', '14:00:00', '18:00:00', 10, 'active', '2026-04-01 06:01:02', '2026-04-01 06:01:02'),
(59, 19, 'Thursday', 'Morning', '09:00:00', '13:00:00', 12, 'active', '2026-04-01 06:01:02', '2026-04-01 06:01:02'),
(60, 19, 'Friday', 'Afternoon', '14:00:00', '18:00:00', 10, 'active', '2026-04-01 06:01:02', '2026-04-01 06:01:02'),
(61, 20, 'Wednesday', 'Morning', '09:00:00', '13:00:00', 15, 'active', '2026-04-01 06:01:02', '2026-04-01 06:01:02'),
(62, 20, 'Thursday', 'Afternoon', '14:00:00', '18:00:00', 10, 'active', '2026-04-01 06:01:02', '2026-04-01 06:01:02'),
(63, 20, 'Saturday', 'Morning', '10:00:00', '14:00:00', 10, 'active', '2026-04-01 06:01:02', '2026-04-01 06:01:02'),
(64, 21, 'Monday', 'Morning', '09:00:00', '13:00:00', 15, 'active', '2026-04-01 06:01:02', '2026-04-01 06:01:02'),
(65, 21, 'Wednesday', 'Morning', '09:00:00', '13:00:00', 15, 'active', '2026-04-01 06:01:02', '2026-04-01 06:01:02'),
(66, 21, 'Friday', 'Morning', '09:00:00', '13:00:00', 15, 'active', '2026-04-01 06:01:02', '2026-04-01 06:01:02'),
(67, 22, 'Tuesday', 'Afternoon', '14:00:00', '18:00:00', 10, 'active', '2026-04-01 06:01:02', '2026-04-01 06:01:02'),
(68, 22, 'Thursday', 'Afternoon', '14:00:00', '18:00:00', 10, 'active', '2026-04-01 06:01:02', '2026-04-01 06:01:02'),
(69, 22, 'Saturday', 'Morning', '10:00:00', '14:00:00', 8, 'active', '2026-04-01 06:01:02', '2026-04-01 06:01:02'),
(70, 23, 'Monday', 'Morning', '09:00:00', '13:00:00', 12, 'active', '2026-04-01 06:01:02', '2026-04-01 06:01:02'),
(71, 23, 'Wednesday', 'Morning', '09:00:00', '13:00:00', 12, 'active', '2026-04-01 06:01:02', '2026-04-01 06:01:02'),
(72, 23, 'Friday', 'Morning', '09:00:00', '13:00:00', 12, 'active', '2026-04-01 06:01:02', '2026-04-01 06:01:02'),
(73, 24, 'Tuesday', 'Evening', '16:00:00', '20:00:00', 8, 'active', '2026-04-01 06:01:02', '2026-04-01 06:01:02'),
(74, 24, 'Thursday', 'Evening', '16:00:00', '20:00:00', 8, 'active', '2026-04-01 06:01:02', '2026-04-01 06:01:02'),
(75, 24, 'Saturday', 'Morning', '10:00:00', '14:00:00', 10, 'active', '2026-04-01 06:01:02', '2026-04-01 06:01:02'),
(76, 25, 'Monday', 'Morning', '09:00:00', '13:00:00', 15, 'active', '2026-04-01 06:01:02', '2026-04-01 06:01:02'),
(77, 25, 'Tuesday', 'Afternoon', '14:00:00', '18:00:00', 10, 'active', '2026-04-01 06:01:02', '2026-04-01 06:01:02'),
(78, 25, 'Thursday', 'Morning', '09:00:00', '13:00:00', 15, 'active', '2026-04-01 06:01:02', '2026-04-01 06:01:02'),
(79, 25, 'Friday', 'Afternoon', '14:00:00', '18:00:00', 10, 'active', '2026-04-01 06:01:02', '2026-04-01 06:01:02'),
(80, 26, 'Wednesday', 'Morning', '09:00:00', '13:00:00', 12, 'active', '2026-04-01 06:01:02', '2026-04-01 06:01:02'),
(81, 26, 'Thursday', 'Afternoon', '14:00:00', '18:00:00', 10, 'active', '2026-04-01 06:01:02', '2026-04-01 06:01:02'),
(82, 26, 'Saturday', 'Morning', '10:00:00', '14:00:00', 8, 'active', '2026-04-01 06:01:02', '2026-04-01 06:01:02'),
(83, 27, 'Monday', 'Morning', '09:00:00', '13:00:00', 12, 'active', '2026-04-01 06:01:02', '2026-04-01 06:01:02'),
(84, 27, 'Wednesday', 'Morning', '09:00:00', '13:00:00', 12, 'active', '2026-04-01 06:01:02', '2026-04-01 06:01:02'),
(85, 27, 'Friday', 'Morning', '09:00:00', '13:00:00', 12, 'active', '2026-04-01 06:01:02', '2026-04-01 06:01:02'),
(86, 28, 'Tuesday', 'Afternoon', '14:00:00', '18:00:00', 10, 'active', '2026-04-01 06:01:02', '2026-04-01 06:01:02'),
(87, 28, 'Thursday', 'Afternoon', '14:00:00', '18:00:00', 10, 'active', '2026-04-01 06:01:02', '2026-04-01 06:01:02'),
(88, 28, 'Saturday', 'Morning', '10:00:00', '14:00:00', 8, 'active', '2026-04-01 06:01:02', '2026-04-01 06:01:02'),
(89, 29, 'Monday', 'Morning', '09:00:00', '13:00:00', 15, 'active', '2026-04-01 06:01:02', '2026-04-01 06:01:02'),
(90, 29, 'Wednesday', 'Morning', '09:00:00', '13:00:00', 15, 'active', '2026-04-01 06:01:02', '2026-04-01 06:01:02'),
(91, 29, 'Friday', 'Morning', '09:00:00', '13:00:00', 15, 'active', '2026-04-01 06:01:02', '2026-04-01 06:01:02'),
(92, 30, 'Tuesday', 'Evening', '16:00:00', '20:00:00', 10, 'active', '2026-04-01 06:01:02', '2026-04-01 06:01:02'),
(93, 30, 'Thursday', 'Evening', '16:00:00', '20:00:00', 10, 'active', '2026-04-01 06:01:02', '2026-04-01 06:01:02'),
(94, 30, 'Saturday', 'Morning', '10:00:00', '14:00:00', 8, 'active', '2026-04-01 06:01:02', '2026-04-01 06:01:02'),
(95, 31, 'Monday', 'Morning', '09:00:00', '13:00:00', 12, 'active', '2026-04-01 06:01:02', '2026-04-01 06:01:02'),
(96, 31, 'Tuesday', 'Afternoon', '14:00:00', '18:00:00', 10, 'active', '2026-04-01 06:01:02', '2026-04-01 06:01:02'),
(97, 31, 'Thursday', 'Morning', '09:00:00', '13:00:00', 12, 'active', '2026-04-01 06:01:02', '2026-04-01 06:01:02'),
(98, 31, 'Friday', 'Afternoon', '14:00:00', '18:00:00', 10, 'active', '2026-04-01 06:01:02', '2026-04-01 06:01:02'),
(99, 32, 'Wednesday', 'Morning', '09:00:00', '13:00:00', 15, 'active', '2026-04-01 06:01:02', '2026-04-01 06:01:02'),
(100, 32, 'Thursday', 'Afternoon', '14:00:00', '18:00:00', 10, 'active', '2026-04-01 06:01:02', '2026-04-01 06:01:02'),
(101, 32, 'Saturday', 'Morning', '10:00:00', '14:00:00', 10, 'active', '2026-04-01 06:01:02', '2026-04-01 06:01:02'),
(102, 33, 'Monday', 'Morning', '09:00:00', '13:00:00', 15, 'active', '2026-04-01 06:01:02', '2026-04-01 06:01:02'),
(103, 33, 'Wednesday', 'Morning', '09:00:00', '13:00:00', 15, 'active', '2026-04-01 06:01:02', '2026-04-01 06:01:02'),
(104, 33, 'Friday', 'Morning', '09:00:00', '13:00:00', 15, 'active', '2026-04-01 06:01:02', '2026-04-01 06:01:02'),
(105, 34, 'Tuesday', 'Afternoon', '14:00:00', '18:00:00', 10, 'active', '2026-04-01 06:01:02', '2026-04-01 06:01:02'),
(106, 34, 'Thursday', 'Afternoon', '14:00:00', '18:00:00', 10, 'active', '2026-04-01 06:01:02', '2026-04-01 06:01:02'),
(107, 34, 'Saturday', 'Morning', '10:00:00', '14:00:00', 8, 'active', '2026-04-01 06:01:02', '2026-04-01 06:01:02'),
(108, 35, 'Monday', 'Morning', '09:00:00', '13:00:00', 12, 'active', '2026-04-01 06:01:02', '2026-04-01 06:01:02'),
(109, 35, 'Wednesday', 'Morning', '09:00:00', '13:00:00', 12, 'active', '2026-04-01 06:01:02', '2026-04-01 06:01:02'),
(110, 35, 'Friday', 'Morning', '09:00:00', '13:00:00', 12, 'active', '2026-04-01 06:01:02', '2026-04-01 06:01:02'),
(111, 36, 'Tuesday', 'Evening', '16:00:00', '20:00:00', 8, 'active', '2026-04-01 06:01:02', '2026-04-01 06:01:02'),
(112, 36, 'Thursday', 'Evening', '16:00:00', '20:00:00', 8, 'active', '2026-04-01 06:01:02', '2026-04-01 06:01:02'),
(113, 36, 'Saturday', 'Morning', '10:00:00', '14:00:00', 10, 'active', '2026-04-01 06:01:02', '2026-04-01 06:01:02'),
(114, 37, 'Monday', 'Morning', '09:00:00', '13:00:00', 15, 'active', '2026-04-01 06:01:02', '2026-04-01 06:01:02'),
(115, 37, 'Tuesday', 'Afternoon', '14:00:00', '18:00:00', 10, 'active', '2026-04-01 06:01:02', '2026-04-01 06:01:02'),
(116, 37, 'Thursday', 'Morning', '09:00:00', '13:00:00', 15, 'active', '2026-04-01 06:01:02', '2026-04-01 06:01:02'),
(117, 37, 'Friday', 'Afternoon', '14:00:00', '18:00:00', 10, 'active', '2026-04-01 06:01:02', '2026-04-01 06:01:02'),
(118, 38, 'Wednesday', 'Morning', '09:00:00', '13:00:00', 12, 'active', '2026-04-01 06:01:02', '2026-04-01 06:01:02'),
(119, 38, 'Thursday', 'Afternoon', '14:00:00', '18:00:00', 10, 'active', '2026-04-01 06:01:02', '2026-04-01 06:01:02'),
(120, 38, 'Saturday', 'Morning', '10:00:00', '14:00:00', 8, 'active', '2026-04-01 06:01:02', '2026-04-01 06:01:02'),
(121, 39, 'Monday', 'Morning', '09:00:00', '13:00:00', 12, 'active', '2026-04-01 06:01:02', '2026-04-01 06:01:02'),
(122, 39, 'Wednesday', 'Morning', '09:00:00', '13:00:00', 12, 'active', '2026-04-01 06:01:02', '2026-04-01 06:01:02'),
(123, 39, 'Friday', 'Morning', '09:00:00', '13:00:00', 12, 'active', '2026-04-01 06:01:02', '2026-04-01 06:01:02'),
(124, 40, 'Tuesday', 'Afternoon', '14:00:00', '18:00:00', 10, 'active', '2026-04-01 06:01:02', '2026-04-01 06:01:02'),
(125, 40, 'Thursday', 'Afternoon', '14:00:00', '18:00:00', 10, 'active', '2026-04-01 06:01:02', '2026-04-01 06:01:02'),
(126, 40, 'Saturday', 'Morning', '10:00:00', '14:00:00', 8, 'active', '2026-04-01 06:01:02', '2026-04-01 06:01:02'),
(127, 41, 'Monday', 'Morning', '21:00:00', '03:00:00', 15, 'active', '2026-04-01 11:39:29', '2026-04-01 11:39:29'),
(128, 42, 'Monday', 'Morning', '04:53:00', '16:53:00', 15, 'active', '2026-04-01 11:53:39', '2026-04-01 11:53:39'),
(129, 42, 'Monday', 'Morning', '04:55:00', '06:54:00', 15, 'active', '2026-04-01 11:54:18', '2026-04-01 11:54:18');

-- --------------------------------------------------------

--
-- Table structure for table `patients`
--

CREATE TABLE `patients` (
  `id` int(11) NOT NULL,
  `name` varchar(100) DEFAULT NULL,
  `age` int(11) DEFAULT NULL,
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

INSERT INTO `patients` (`id`, `name`, `age`, `gender`, `phone`, `address`, `blood_group`, `status`, `created_at`) VALUES
(7, 'Izhan Sajid', 22, 'male', '03214785693', 'Billi tang', 'AB-', 'active', '2026-04-01 07:03:36'),
(8, 'panda zahid', 38, 'male', '0127568924', 'KOHAT', 'B+', 'active', '2026-04-01 10:30:23'),
(9, 'Salman ', 22, 'male', '5635657678', 'Peshwar', 'O-', 'active', '2026-04-02 06:39:50');

-- --------------------------------------------------------

--
-- Table structure for table `payments`
--

CREATE TABLE `payments` (
  `id` int(11) NOT NULL,
  `appointment_id` int(11) NOT NULL,
  `patient_id` int(11) NOT NULL,
  `doctor_id` int(11) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `payment_method` varchar(50) NOT NULL DEFAULT 'cash',
  `status` varchar(50) NOT NULL DEFAULT 'pending',
  `transaction_id` varchar(100) DEFAULT NULL,
  `payment_date` datetime DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `payments`
--

INSERT INTO `payments` (`id`, `appointment_id`, `patient_id`, `doctor_id`, `amount`, `payment_method`, `status`, `transaction_id`, `payment_date`, `notes`, `created_at`) VALUES
(6, 9, 7, 5, 1200.00, 'cash', 'completed', '', '2026-04-01 09:19:11', '--', '2026-04-01 07:19:11'),
(8, 12, 7, 5, 1200.00, 'cash', 'refunded', '', '2026-04-01 12:44:24', '', '2026-04-01 10:44:24'),
(10, 14, 9, 6, 1300.00, 'cash', 'refunded', '', '2026-04-02 08:40:14', '', '2026-04-02 06:40:14');

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
  `notes` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `prescriptions`
--

INSERT INTO `prescriptions` (`id`, `record_id`, `medicine_name`, `dosage`, `duration`, `notes`) VALUES
(3, 3, 'Acetaminophen (Tylenol),', '5mg', '3 days ', '--'),
(4, 3, 'NSAIDs ', '5mg', '3 days ', '--');

-- --------------------------------------------------------

--
-- Table structure for table `records`
--

CREATE TABLE `records` (
  `id` int(11) NOT NULL,
  `patient_id` int(11) DEFAULT NULL,
  `doctor_id` int(11) DEFAULT NULL,
  `visit_date` datetime DEFAULT NULL,
  `symptoms` text DEFAULT NULL,
  `diagnosis` text DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `records`
--

INSERT INTO `records` (`id`, `patient_id`, `doctor_id`, `visit_date`, `symptoms`, `diagnosis`, `notes`, `created_at`) VALUES
(3, 7, 5, '2026-04-01 09:54:40', 'Muscular Ache/Stiffness: A dull, aching pain or stiffness in the lower back or spine, often worsening with bending, lifting, or prolonged sitting.\\r\\nRadiating Pain: Pain that shoots or radiates down the legs, sometimes accompanied by tingling, numbness, or weakness, which may indicate nerve issues.\\r\\nMuscle Spasms: Intense muscle spasms or contractions in the back, making it difficult to move, walk, or stand straight.\\r\\nReduced Range of Motion: Difficulty moving or straightening the back, frequently causing a “crooked” or bent posture.\\r\\nPain that Changes with Activity: Pain that feels worse when standing, walking, or sitting for long periods, but may improve with rest or gentle movement. ', 'NSAIDs ', '', '2026-04-01 07:54:40');

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
(3, 'receptionist');

-- --------------------------------------------------------

--
-- Table structure for table `staff`
--

CREATE TABLE `staff` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `category_id` int(11) DEFAULT NULL,
  `position` varchar(100) DEFAULT NULL,
  `shift` varchar(50) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `staff`
--

INSERT INTO `staff` (`id`, `user_id`, `category_id`, `position`, `shift`, `address`, `created_at`) VALUES
(4, 43, NULL, 'Receptionist', 'Morning', 'Cardiology Department, 1st Floor', '2026-04-01 06:07:59'),
(5, 44, NULL, 'Receptionist', 'Morning', 'Neurology Department, 2nd Floor', '2026-04-01 06:07:59'),
(6, 45, NULL, 'Receptionist', 'Morning', 'Ophthalmology Department, 1st Floor', '2026-04-01 06:07:59'),
(7, 46, NULL, 'Receptionist', 'Afternoon', 'ENT Department, 2nd Floor', '2026-04-01 06:07:59'),
(8, 47, NULL, 'Receptionist', 'Morning', 'Dermatology Department, 3rd Floor', '2026-04-01 06:07:59'),
(9, 48, NULL, 'Receptionist', 'Morning', 'Pulmonology Department, 2nd Floor', '2026-04-01 06:07:59'),
(10, 49, NULL, 'Receptionist', 'Afternoon', 'Gastroenterology Department, 1st Floor', '2026-04-01 06:07:59'),
(11, 50, NULL, 'Receptionist', 'Morning', 'Orthopedic Department, 3rd Floor', '2026-04-01 06:07:59'),
(12, 51, NULL, 'Receptionist', 'Morning', 'Endocrinology Department, 2nd Floor', '2026-04-01 06:07:59'),
(13, 52, NULL, 'Receptionist', 'Afternoon', 'Infectious Disease Department, 1st Floor', '2026-04-01 06:07:59'),
(14, 53, NULL, 'Receptionist', 'Morning', 'Pediatric Department, Ground Floor', '2026-04-01 06:07:59'),
(15, 54, NULL, 'Receptionist', 'Morning', 'Psychiatry Department, 4th Floor', '2026-04-01 06:07:59'),
(16, 55, NULL, 'Receptionist', 'Afternoon', 'Nephrology Department, 2nd Floor', '2026-04-01 06:07:59'),
(17, 56, NULL, 'Receptionist', 'Morning', 'Urology Department, 3rd Floor', '2026-04-01 06:07:59'),
(18, 57, NULL, 'Receptionist', 'Morning', 'Gynecology Department, 1st Floor', '2026-04-01 06:07:59'),
(19, 58, NULL, 'Receptionist', 'Afternoon', 'Rheumatology Department, 2nd Floor', '2026-04-01 06:07:59'),
(20, 59, NULL, 'Receptionist', 'Morning', 'Allergy Department, 3rd Floor', '2026-04-01 06:07:59'),
(21, 60, NULL, 'Receptionist', 'Morning', 'Hematology Department, 2nd Floor', '2026-04-01 06:07:59'),
(22, 61, NULL, 'Receptionist', 'Afternoon', 'Oncology Department, 4th Floor', '2026-04-01 06:07:59'),
(23, 62, NULL, 'Receptionist', 'Morning', 'Geriatric Department, 1st Floor', '2026-04-01 06:07:59');

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
(2, 3, 'Reception User', 'reception@prms.com', '03000005599', '123456', 'active', '2026-04-01 05:49:22', '2026-04-01 05:49:22'),
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
(43, 3, 'Receptionist Sarah', 'reception.cardiologist@prms.com', '03001111041', '123456', 'active', '2026-04-01 06:07:32', '2026-04-01 06:07:32'),
(44, 3, 'Receptionist Ahmed', 'reception.neurologist@prms.com', '03001111042', '123456', 'active', '2026-04-01 06:07:32', '2026-04-01 06:07:32'),
(45, 3, 'Receptionist Fatima', 'reception.ophthalmologist@prms.com', '03001111043', '123456', 'active', '2026-04-01 06:07:32', '2026-04-01 06:07:32'),
(46, 3, 'Receptionist Omar', 'reception.ent@prms.com', '03001111044', '123456', 'active', '2026-04-01 06:07:32', '2026-04-01 06:07:32'),
(47, 3, 'Receptionist Ayesha', 'reception.dermatologist@prms.com', '03001111045', '123456', 'active', '2026-04-01 06:07:32', '2026-04-01 06:07:32'),
(48, 3, 'Receptionist Bilal', 'reception.pulmonologist@prms.com', '03001111046', '123456', 'active', '2026-04-01 06:07:32', '2026-04-01 06:07:32'),
(49, 3, 'Receptionist Zara', 'reception.gastroenterologist@prms.com', '03001111047', '123456', 'active', '2026-04-01 06:07:32', '2026-04-01 06:07:32'),
(50, 3, 'Receptionist Hassan', 'reception.orthopedic@prms.com', '03001111048', '123456', 'active', '2026-04-01 06:07:32', '2026-04-01 06:07:32'),
(51, 3, 'Receptionist Nadia', 'reception.endocrinologist@prms.com', '03001111049', '123456', 'active', '2026-04-01 06:07:32', '2026-04-01 06:07:32'),
(52, 3, 'Receptionist Rashid', 'reception.infectious@prms.com', '03001111050', '123456', 'active', '2026-04-01 06:07:32', '2026-04-01 06:07:32'),
(53, 3, 'Receptionist Sana', 'reception.pediatrician@prms.com', '03001111051', '123456', 'active', '2026-04-01 06:07:32', '2026-04-01 06:07:32'),
(54, 3, 'Receptionist Tariq', 'reception.psychiatrist@prms.com', '03001111052', '123456', 'active', '2026-04-01 06:07:32', '2026-04-01 06:07:32'),
(55, 3, 'Receptionist Fariha', 'reception.nephrologist@prms.com', '03001111053', '123456', 'active', '2026-04-01 06:07:32', '2026-04-01 06:07:32'),
(56, 3, 'Receptionist Asad', 'reception.urologist@prms.com', '03001111054', '123456', 'active', '2026-04-01 06:07:32', '2026-04-01 06:07:32'),
(57, 3, 'Receptionist Mariam', 'reception.gynecologist@prms.com', '03001111055', '123456', 'active', '2026-04-01 06:07:32', '2026-04-01 06:07:32'),
(58, 3, 'Receptionist Naveed', 'reception.rheumatologist@prms.com', '03001111056', '123456', 'active', '2026-04-01 06:07:32', '2026-04-01 06:07:32'),
(59, 3, 'Receptionist Saima', 'reception.allergy@prms.com', '03001111057', '123456', 'active', '2026-04-01 06:07:32', '2026-04-01 06:07:32'),
(60, 3, 'Receptionist Imran', 'reception.hematologist@prms.com', '03001111058', '123456', 'active', '2026-04-01 06:07:32', '2026-04-01 06:07:32'),
(61, 3, 'Receptionist Rabia', 'reception.oncologist@prms.com', '03001111059', '123456', 'active', '2026-04-01 06:07:32', '2026-04-01 06:07:32'),
(62, 3, 'Receptionist Shahid', 'reception.geriatrician@prms.com', '03001111060', '123456', 'active', '2026-04-01 06:07:32', '2026-04-01 06:07:32'),
(63, 2, 'test test', 'test@gmail.com', '03214785693', '123', 'active', '2026-04-01 11:39:00', NULL),
(64, 2, 'Test1', 'test2@gmail.com', '03214785693', '123', 'active', '2026-04-01 11:52:51', NULL);

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
  ADD KEY `patient_id` (`patient_id`);

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
  ADD KEY `doctor_id` (`doctor_id`);

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
  ADD KEY `user_id` (`user_id`),
  ADD KEY `fk_staff_category` (`category_id`);

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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `appointments`
--
ALTER TABLE `appointments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT for table `categories`
--
ALTER TABLE `categories`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=24;

--
-- AUTO_INCREMENT for table `doctors`
--
ALTER TABLE `doctors`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=43;

--
-- AUTO_INCREMENT for table `doctor_schedules`
--
ALTER TABLE `doctor_schedules`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=130;

--
-- AUTO_INCREMENT for table `patients`
--
ALTER TABLE `patients`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `payments`
--
ALTER TABLE `payments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `prescriptions`
--
ALTER TABLE `prescriptions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `records`
--
ALTER TABLE `records`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `roles`
--
ALTER TABLE `roles`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `staff`
--
ALTER TABLE `staff`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=24;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=65;

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
  ADD CONSTRAINT `payments_ibfk_1` FOREIGN KEY (`appointment_id`) REFERENCES `appointments` (`id`) ON DELETE CASCADE,
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
  ADD CONSTRAINT `records_ibfk_2` FOREIGN KEY (`doctor_id`) REFERENCES `doctors` (`id`);

--
-- Constraints for table `staff`
--
ALTER TABLE `staff`
  ADD CONSTRAINT `fk_staff_category` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`),
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
