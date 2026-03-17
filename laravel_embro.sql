-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Mar 17, 2026 at 11:15 PM
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
-- Database: `laravel_embro`
--

-- --------------------------------------------------------

--
-- Table structure for table `audit_logs`
--

CREATE TABLE `audit_logs` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `actor_user_id` bigint(20) UNSIGNED DEFAULT NULL,
  `shop_id` bigint(20) UNSIGNED DEFAULT NULL,
  `entity_type` varchar(80) NOT NULL,
  `entity_id` bigint(20) UNSIGNED DEFAULT NULL,
  `action` varchar(100) NOT NULL,
  `old_values_json` longtext DEFAULT NULL,
  `new_values_json` longtext DEFAULT NULL,
  `ip_address` varchar(64) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `bargaining_offers`
--

CREATE TABLE `bargaining_offers` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `design_post_id` bigint(20) UNSIGNED NOT NULL,
  `job_post_application_id` bigint(20) UNSIGNED DEFAULT NULL,
  `parent_offer_id` bigint(20) UNSIGNED DEFAULT NULL,
  `offered_by_user_id` bigint(20) UNSIGNED NOT NULL,
  `amount` decimal(12,2) NOT NULL,
  `estimated_days` int(11) DEFAULT NULL,
  `message` text DEFAULT NULL,
  `status` enum('pending','accepted','rejected','countered','withdrawn') NOT NULL DEFAULT 'pending',
  `responded_by` bigint(20) UNSIGNED DEFAULT NULL,
  `responded_at` datetime DEFAULT NULL,
  `expires_at` datetime DEFAULT NULL,
  `negotiation_round` int(10) UNSIGNED NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `bargaining_offers`
--

INSERT INTO `bargaining_offers` (`id`, `design_post_id`, `job_post_application_id`, `parent_offer_id`, `offered_by_user_id`, `amount`, `estimated_days`, `message`, `status`, `responded_by`, `responded_at`, `expires_at`, `negotiation_round`, `created_at`, `updated_at`) VALUES
(1, 1, NULL, NULL, 6, 1500.00, 5, NULL, 'pending', NULL, NULL, '2026-03-18 10:26:34', 1, '2026-03-15 02:26:34', '2026-03-15 02:26:34');

-- --------------------------------------------------------

--
-- Table structure for table `cache`
--

CREATE TABLE `cache` (
  `key` varchar(255) NOT NULL,
  `value` mediumtext NOT NULL,
  `expiration` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `cache_locks`
--

CREATE TABLE `cache_locks` (
  `key` varchar(255) NOT NULL,
  `owner` varchar(255) NOT NULL,
  `expiration` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `cavite_locations`
--

CREATE TABLE `cavite_locations` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `location_type` enum('city','municipality') NOT NULL,
  `name` varchar(120) NOT NULL,
  `province_name` varchar(120) NOT NULL DEFAULT 'Cavite',
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `cavite_locations`
--

INSERT INTO `cavite_locations` (`id`, `location_type`, `name`, `province_name`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 'city', 'Bacoor', 'Cavite', 1, '2026-03-14 02:07:40', '2026-03-14 02:07:40'),
(2, 'city', 'Cavite City', 'Cavite', 1, '2026-03-14 02:07:40', '2026-03-14 02:07:40'),
(3, 'city', 'Dasmariñas', 'Cavite', 1, '2026-03-14 02:07:40', '2026-03-14 02:07:40'),
(4, 'city', 'General Trias', 'Cavite', 1, '2026-03-14 02:07:40', '2026-03-14 02:07:40'),
(5, 'city', 'Imus', 'Cavite', 1, '2026-03-14 02:07:40', '2026-03-14 02:07:40'),
(6, 'city', 'Tagaytay', 'Cavite', 1, '2026-03-14 02:07:40', '2026-03-14 02:07:40'),
(7, 'city', 'Trece Martires', 'Cavite', 1, '2026-03-14 02:07:40', '2026-03-14 02:07:40'),
(8, 'municipality', 'Alfonso', 'Cavite', 1, '2026-03-14 02:07:40', '2026-03-14 02:07:40'),
(9, 'municipality', 'Amadeo', 'Cavite', 1, '2026-03-14 02:07:40', '2026-03-14 02:07:40'),
(10, 'municipality', 'Carmona', 'Cavite', 1, '2026-03-14 02:07:40', '2026-03-14 02:07:40'),
(11, 'municipality', 'General Emilio Aguinaldo', 'Cavite', 1, '2026-03-14 02:07:40', '2026-03-14 02:07:40'),
(12, 'municipality', 'General Mariano Alvarez', 'Cavite', 1, '2026-03-14 02:07:40', '2026-03-14 02:07:40'),
(13, 'municipality', 'Indang', 'Cavite', 1, '2026-03-14 02:07:40', '2026-03-14 02:07:40'),
(14, 'municipality', 'Kawit', 'Cavite', 1, '2026-03-14 02:07:40', '2026-03-14 02:07:40'),
(15, 'municipality', 'Magallanes', 'Cavite', 1, '2026-03-14 02:07:40', '2026-03-14 02:07:40'),
(16, 'municipality', 'Maragondon', 'Cavite', 1, '2026-03-14 02:07:40', '2026-03-14 02:07:40'),
(17, 'municipality', 'Mendez', 'Cavite', 1, '2026-03-14 02:07:40', '2026-03-14 02:07:40'),
(18, 'municipality', 'Naic', 'Cavite', 1, '2026-03-14 02:07:40', '2026-03-14 02:07:40'),
(19, 'municipality', 'Noveleta', 'Cavite', 1, '2026-03-14 02:07:40', '2026-03-14 02:07:40'),
(20, 'municipality', 'Rosario', 'Cavite', 1, '2026-03-14 02:07:40', '2026-03-14 02:07:40'),
(21, 'municipality', 'Silang', 'Cavite', 1, '2026-03-14 02:07:40', '2026-03-14 02:07:40'),
(22, 'municipality', 'Tanza', 'Cavite', 1, '2026-03-14 02:07:40', '2026-03-14 02:07:40'),
(23, 'municipality', 'Ternate', 'Cavite', 1, '2026-03-14 02:07:40', '2026-03-14 02:07:40');

-- --------------------------------------------------------

--
-- Table structure for table `client_payment_methods`
--

CREATE TABLE `client_payment_methods` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `label` varchar(255) NOT NULL,
  `method_type` varchar(50) NOT NULL,
  `account_name` varchar(255) DEFAULT NULL,
  `account_number` varchar(255) DEFAULT NULL,
  `provider` varchar(255) DEFAULT NULL,
  `instructions` text DEFAULT NULL,
  `is_default` tinyint(1) NOT NULL DEFAULT 0,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `client_profiles`
--

CREATE TABLE `client_profiles` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `cavite_location_id` bigint(20) UNSIGNED DEFAULT NULL,
  `default_address` text DEFAULT NULL,
  `postal_code` varchar(20) DEFAULT NULL,
  `first_name` varchar(100) DEFAULT NULL,
  `middle_name` varchar(100) DEFAULT NULL,
  `last_name` varchar(100) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `phone_number` varchar(30) DEFAULT NULL,
  `registration_date` date DEFAULT NULL,
  `registered_at_platform` timestamp NULL DEFAULT NULL,
  `phone` varchar(30) DEFAULT NULL,
  `billing_contact_name` varchar(180) DEFAULT NULL,
  `billing_phone` varchar(30) DEFAULT NULL,
  `billing_email` varchar(255) DEFAULT NULL,
  `default_payment_method` varchar(100) DEFAULT NULL,
  `preferred_payment_method_id` bigint(20) UNSIGNED DEFAULT NULL,
  `organization_name` varchar(180) DEFAULT NULL,
  `preferred_contact_method` enum('email','phone','chat') NOT NULL DEFAULT 'email',
  `preferred_fulfillment_type` enum('pickup','delivery') DEFAULT NULL,
  `saved_measurements_json` longtext DEFAULT NULL,
  `default_garment_preferences_json` longtext DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `mobile_push_enabled` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `client_profiles`
--

INSERT INTO `client_profiles` (`id`, `user_id`, `cavite_location_id`, `default_address`, `postal_code`, `first_name`, `middle_name`, `last_name`, `email`, `phone_number`, `registration_date`, `registered_at_platform`, `phone`, `billing_contact_name`, `billing_phone`, `billing_email`, `default_payment_method`, `preferred_payment_method_id`, `organization_name`, `preferred_contact_method`, `preferred_fulfillment_type`, `saved_measurements_json`, `default_garment_preferences_json`, `notes`, `mobile_push_enabled`, `created_at`, `updated_at`) VALUES
(1, 6, NULL, NULL, NULL, NULL, NULL, NULL, 'client1@example.com', NULL, '2026-03-14', '2026-03-14 05:30:57', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'email', NULL, NULL, NULL, NULL, 1, '2026-03-14 23:03:27', '2026-03-17 07:06:32'),
(2, 7, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'email', NULL, NULL, NULL, NULL, 1, '2026-03-15 00:03:44', '2026-03-15 00:03:44');

-- --------------------------------------------------------

--
-- Table structure for table `client_saved_addresses`
--

CREATE TABLE `client_saved_addresses` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `client_profile_id` bigint(20) UNSIGNED NOT NULL,
  `label` varchar(60) NOT NULL DEFAULT 'Address',
  `recipient_name` varchar(150) DEFAULT NULL,
  `recipient_phone` varchar(30) DEFAULT NULL,
  `country` varchar(80) DEFAULT NULL,
  `province` varchar(80) DEFAULT NULL,
  `city_municipality` varchar(120) DEFAULT NULL,
  `barangay` varchar(120) DEFAULT NULL,
  `house_street` varchar(255) DEFAULT NULL,
  `address_line_2` varchar(255) DEFAULT NULL,
  `cavite_location_id` bigint(20) UNSIGNED DEFAULT NULL,
  `address_line` text NOT NULL,
  `postal_code` varchar(20) DEFAULT NULL,
  `country_name` varchar(80) DEFAULT NULL,
  `province_name` varchar(80) DEFAULT NULL,
  `city_name` varchar(120) DEFAULT NULL,
  `barangay_name` varchar(120) DEFAULT NULL,
  `house_number_street` varchar(255) DEFAULT NULL,
  `other_house_information` varchar(255) DEFAULT NULL,
  `is_default` tinyint(1) NOT NULL DEFAULT 0,
  `delivery_notes` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `design_customizations`
--

CREATE TABLE `design_customizations` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `design_post_id` bigint(20) UNSIGNED DEFAULT NULL,
  `order_id` bigint(20) UNSIGNED DEFAULT NULL,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(180) NOT NULL,
  `garment_type` varchar(100) DEFAULT NULL,
  `placement_area` varchar(100) DEFAULT NULL,
  `fabric_type` varchar(100) DEFAULT NULL,
  `width_mm` decimal(10,2) DEFAULT NULL,
  `height_mm` decimal(10,2) DEFAULT NULL,
  `color_count` int(11) DEFAULT NULL,
  `stitch_count_estimate` int(11) DEFAULT NULL,
  `complexity_level` enum('simple','standard','complex','premium') NOT NULL DEFAULT 'standard',
  `special_styles_json` longtext DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `artwork_path` varchar(255) DEFAULT NULL,
  `preview_path` varchar(255) DEFAULT NULL,
  `status` enum('draft','estimated','proof_ready','approved','archived') NOT NULL DEFAULT 'draft',
  `workflow_status` varchar(50) DEFAULT NULL,
  `current_version_no` int(10) UNSIGNED NOT NULL DEFAULT 1,
  `approved_version_no` int(10) UNSIGNED DEFAULT NULL,
  `submitted_at` datetime DEFAULT NULL,
  `last_revision_requested_at` datetime DEFAULT NULL,
  `locked_at` datetime DEFAULT NULL,
  `production_status` varchar(60) DEFAULT NULL,
  `digitizing_status` varchar(255) DEFAULT NULL,
  `machine_file_status` varchar(255) DEFAULT NULL,
  `production_ready_at` timestamp NULL DEFAULT NULL,
  `digitizing_required_at` timestamp NULL DEFAULT NULL,
  `machine_ready_at` timestamp NULL DEFAULT NULL,
  `latest_production_package_id` bigint(20) UNSIGNED DEFAULT NULL,
  `latest_digitizing_job_id` bigint(20) UNSIGNED DEFAULT NULL,
  `color_mapping_json` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`color_mapping_json`)),
  `risk_flags_json` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`risk_flags_json`)),
  `suggested_quote_basis_json` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`suggested_quote_basis_json`)),
  `production_meta_json` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`production_meta_json`)),
  `digitizing_meta_json` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`digitizing_meta_json`)),
  `estimated_base_price` decimal(12,2) NOT NULL DEFAULT 0.00,
  `estimated_total_price` decimal(12,2) NOT NULL DEFAULT 0.00,
  `pricing_breakdown_json` longtext DEFAULT NULL,
  `design_session_json` longtext DEFAULT NULL,
  `preview_meta_json` longtext DEFAULT NULL,
  `pricing_confidence_score` decimal(5,2) DEFAULT NULL,
  `pricing_strategy` varchar(80) DEFAULT NULL,
  `last_priced_at` datetime DEFAULT NULL,
  `approved_proof_id` bigint(20) UNSIGNED DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `design_customization_snapshots`
--

CREATE TABLE `design_customization_snapshots` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `design_customization_id` bigint(20) UNSIGNED NOT NULL,
  `version_no` int(10) UNSIGNED NOT NULL DEFAULT 1,
  `captured_by` bigint(20) UNSIGNED DEFAULT NULL,
  `change_summary` varchar(180) DEFAULT NULL,
  `snapshot_json` longtext DEFAULT NULL,
  `pricing_snapshot_json` longtext DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `design_digitizing_jobs`
--

CREATE TABLE `design_digitizing_jobs` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `design_customization_id` bigint(20) UNSIGNED NOT NULL,
  `design_proof_id` bigint(20) UNSIGNED DEFAULT NULL,
  `order_id` bigint(20) UNSIGNED DEFAULT NULL,
  `assigned_digitizer_user_id` bigint(20) UNSIGNED DEFAULT NULL,
  `status` varchar(255) NOT NULL DEFAULT 'pending_digitizing',
  `digitizing_notes` text DEFAULT NULL,
  `machine_file_status` varchar(255) DEFAULT NULL,
  `revision_count` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `approval_state` varchar(255) NOT NULL DEFAULT 'pending',
  `result_meta_json` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`result_meta_json`)),
  `submitted_for_review_at` timestamp NULL DEFAULT NULL,
  `approved_at` timestamp NULL DEFAULT NULL,
  `rejected_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `design_machine_files`
--

CREATE TABLE `design_machine_files` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `design_digitizing_job_id` bigint(20) UNSIGNED NOT NULL,
  `design_customization_id` bigint(20) UNSIGNED NOT NULL,
  `design_version_no` int(10) UNSIGNED NOT NULL DEFAULT 1,
  `file_version` int(10) UNSIGNED NOT NULL DEFAULT 1,
  `file_type` varchar(20) NOT NULL,
  `file_name` varchar(255) DEFAULT NULL,
  `file_path` varchar(255) DEFAULT NULL,
  `uploaded_by` bigint(20) UNSIGNED DEFAULT NULL,
  `approval_state` varchar(255) NOT NULL DEFAULT 'pending_review',
  `file_meta_json` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`file_meta_json`)),
  `approved_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `design_posts`
--

CREATE TABLE `design_posts` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `client_user_id` bigint(20) UNSIGNED NOT NULL,
  `selected_shop_id` bigint(20) UNSIGNED DEFAULT NULL,
  `converted_order_id` bigint(20) UNSIGNED DEFAULT NULL,
  `cavite_location_id` bigint(20) UNSIGNED DEFAULT NULL,
  `title` varchar(180) NOT NULL,
  `description` text NOT NULL,
  `design_type` enum('logo','uniform','cap','patch','custom_art','digitizing','other') NOT NULL DEFAULT 'custom_art',
  `fabric_type` varchar(100) DEFAULT NULL,
  `garment_type` varchar(100) DEFAULT NULL,
  `quantity` int(11) NOT NULL DEFAULT 1,
  `target_budget` decimal(12,2) DEFAULT NULL,
  `deadline_date` date DEFAULT NULL,
  `visibility` enum('public','private','closed') NOT NULL DEFAULT 'public',
  `status` enum('open','under_review','shop_selected','converted_to_order','cancelled','completed') NOT NULL DEFAULT 'open',
  `reference_file_path` varchar(255) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `closed_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `design_posts`
--

INSERT INTO `design_posts` (`id`, `client_user_id`, `selected_shop_id`, `converted_order_id`, `cavite_location_id`, `title`, `description`, `design_type`, `fabric_type`, `garment_type`, `quantity`, `target_budget`, `deadline_date`, `visibility`, `status`, `reference_file_path`, `notes`, `closed_at`, `created_at`, `updated_at`) VALUES
(1, 6, NULL, NULL, NULL, 'pantanga', 'hfjdshfjasd', 'logo', NULL, 'Polo Shirt', 1, 4589.00, NULL, 'public', 'open', NULL, NULL, NULL, '2026-03-15 02:25:34', '2026-03-15 02:25:34'),
(2, 6, NULL, NULL, NULL, 'pantanga', 'hfjdshfjasd', 'logo', NULL, 'Polo Shirt', 1, 500.00, NULL, 'public', 'open', NULL, NULL, NULL, '2026-03-15 02:26:12', '2026-03-15 02:26:12'),
(3, 6, NULL, NULL, NULL, 'pantanga', 'hfjdshfjasd', 'logo', NULL, 'Polo Shirt', 1, 500.00, NULL, 'public', 'open', NULL, NULL, NULL, '2026-03-15 02:26:14', '2026-03-15 02:26:14');

-- --------------------------------------------------------

--
-- Table structure for table `design_production_packages`
--

CREATE TABLE `design_production_packages` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `design_customization_id` bigint(20) UNSIGNED NOT NULL,
  `created_by` bigint(20) UNSIGNED DEFAULT NULL,
  `version_no` int(10) UNSIGNED NOT NULL DEFAULT 1,
  `package_no` int(10) UNSIGNED NOT NULL DEFAULT 1,
  `status` varchar(50) NOT NULL DEFAULT 'prepared',
  `preview_path` varchar(255) DEFAULT NULL,
  `proof_summary_json` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`proof_summary_json`)),
  `design_metadata_json` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`design_metadata_json`)),
  `quote_basis_json` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`quote_basis_json`)),
  `thread_mapping_json` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`thread_mapping_json`)),
  `risk_flags_json` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`risk_flags_json`)),
  `production_summary_json` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`production_summary_json`)),
  `internal_note` text DEFAULT NULL,
  `qc_note` text DEFAULT NULL,
  `handed_off_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `design_proofs`
--

CREATE TABLE `design_proofs` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `design_customization_id` bigint(20) UNSIGNED NOT NULL,
  `proof_no` int(10) UNSIGNED NOT NULL DEFAULT 1,
  `version_no` int(10) UNSIGNED NOT NULL DEFAULT 1,
  `generated_by` bigint(20) UNSIGNED NOT NULL,
  `preview_file_path` varchar(255) NOT NULL,
  `annotated_notes` text DEFAULT NULL,
  `pricing_snapshot_json` longtext DEFAULT NULL,
  `proof_summary_json` longtext DEFAULT NULL,
  `status` enum('pending_client','approved','rejected','superseded') NOT NULL DEFAULT 'pending_client',
  `responded_by` bigint(20) UNSIGNED DEFAULT NULL,
  `responded_at` datetime DEFAULT NULL,
  `expires_at` datetime DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `design_workflow_events`
--

CREATE TABLE `design_workflow_events` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `design_customization_id` bigint(20) UNSIGNED NOT NULL,
  `actor_user_id` bigint(20) UNSIGNED DEFAULT NULL,
  `event_type` varchar(80) NOT NULL,
  `summary` varchar(255) NOT NULL,
  `details` text DEFAULT NULL,
  `event_meta_json` longtext DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `dispute_cases`
--

CREATE TABLE `dispute_cases` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `shop_id` bigint(20) UNSIGNED NOT NULL,
  `order_id` bigint(20) UNSIGNED DEFAULT NULL,
  `complainant_user_id` bigint(20) UNSIGNED DEFAULT NULL,
  `assigned_handler_user_id` bigint(20) UNSIGNED DEFAULT NULL,
  `dispute_type` varchar(255) NOT NULL,
  `issue_summary` text NOT NULL,
  `attachments_json` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`attachments_json`)),
  `status` varchar(255) NOT NULL DEFAULT 'open',
  `resolution` text DEFAULT NULL,
  `resolved_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `dss_recommendations`
--

CREATE TABLE `dss_recommendations` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `client_user_id` bigint(20) UNSIGNED DEFAULT NULL,
  `shop_id` bigint(20) UNSIGNED NOT NULL,
  `generated_for_type` enum('client','admin','owner') NOT NULL DEFAULT 'client',
  `basis` varchar(100) NOT NULL,
  `score` decimal(8,4) NOT NULL DEFAULT 0.0000,
  `rank_position` int(11) NOT NULL DEFAULT 1,
  `context_json` longtext DEFAULT NULL,
  `generated_at` datetime NOT NULL DEFAULT current_timestamp(),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `dss_recommendations`
--

INSERT INTO `dss_recommendations` (`id`, `client_user_id`, `shop_id`, `generated_for_type`, `basis`, `score`, `rank_position`, `context_json`, `generated_at`, `created_at`) VALUES
(9, 6, 1, 'client', 'completion_rate, rating, recommendation score, delay risk', 26.0000, 1, '{\"recommendation_context\":\"marketplace_match\",\"design_type\":null,\"quantity\":null}', '2026-03-17 22:12:18', '2026-03-15 02:29:53');

-- --------------------------------------------------------

--
-- Table structure for table `dss_shop_metrics`
--

CREATE TABLE `dss_shop_metrics` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `shop_id` bigint(20) UNSIGNED NOT NULL,
  `metric_date` date NOT NULL,
  `total_orders` int(11) NOT NULL DEFAULT 0,
  `completed_orders` int(11) NOT NULL DEFAULT 0,
  `cancelled_orders` int(11) NOT NULL DEFAULT 0,
  `avg_rating` decimal(4,2) DEFAULT NULL,
  `review_count` int(11) NOT NULL DEFAULT 0,
  `completion_rate` decimal(6,4) DEFAULT NULL,
  `avg_turnaround_days` decimal(8,2) DEFAULT NULL,
  `active_staff_count` int(11) NOT NULL DEFAULT 0,
  `open_job_posts_taken` int(11) NOT NULL DEFAULT 0,
  `revenue_total` decimal(14,2) NOT NULL DEFAULT 0.00,
  `price_competitiveness_score` decimal(6,2) DEFAULT NULL,
  `recommendation_score` decimal(6,2) DEFAULT NULL,
  `delay_risk_score` decimal(6,2) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `dss_shop_metrics`
--

INSERT INTO `dss_shop_metrics` (`id`, `shop_id`, `metric_date`, `total_orders`, `completed_orders`, `cancelled_orders`, `avg_rating`, `review_count`, `completion_rate`, `avg_turnaround_days`, `active_staff_count`, `open_job_posts_taken`, `revenue_total`, `price_competitiveness_score`, `recommendation_score`, `delay_risk_score`, `created_at`, `updated_at`) VALUES
(1, 1, '2026-03-15', 1, 0, 1, 0.00, 0, 0.0000, 0.00, 2, 0, 0.00, 100.00, 24.00, 10.00, '2026-03-14 18:46:26', '2026-03-14 19:22:39'),
(2, 1, '2026-03-16', 1, 0, 1, 0.00, 0, 0.0000, 3.50, 3, 0, 1500.00, 75.00, 80.00, 20.00, '2026-03-16 08:29:49', '2026-03-16 08:29:49'),
(3, 1, '2026-03-17', 1, 0, 1, 0.00, 0, 0.0000, 3.50, 3, 0, 1500.00, 75.00, 80.00, 20.00, '2026-03-16 18:36:08', '2026-03-16 18:36:08');

-- --------------------------------------------------------

--
-- Table structure for table `embroidery_design_sessions`
--

CREATE TABLE `embroidery_design_sessions` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `order_id` bigint(20) UNSIGNED DEFAULT NULL,
  `shop_id` bigint(20) UNSIGNED DEFAULT NULL,
  `name` varchar(180) NOT NULL,
  `garment_type` varchar(100) NOT NULL DEFAULT 'Polo Shirt',
  `placement_area` varchar(100) NOT NULL DEFAULT 'Front Left Chest',
  `canvas_width` int(10) UNSIGNED NOT NULL DEFAULT 640,
  `canvas_height` int(10) UNSIGNED NOT NULL DEFAULT 640,
  `thread_palette_json` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`thread_palette_json`)),
  `design_json` longtext DEFAULT NULL,
  `preview_svg` longtext DEFAULT NULL,
  `estimated_stitches` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `thread_color_count` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `suggested_price` decimal(12,2) NOT NULL DEFAULT 0.00,
  `pricing_confidence` decimal(5,2) NOT NULL DEFAULT 0.00,
  `status` enum('draft','proof_requested','approved','locked') NOT NULL DEFAULT 'draft',
  `version_no` int(10) UNSIGNED NOT NULL DEFAULT 1,
  `approved_version_no` int(10) UNSIGNED DEFAULT NULL,
  `last_priced_at` timestamp NULL DEFAULT NULL,
  `approved_at` timestamp NULL DEFAULT NULL,
  `locked_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `embroidery_design_versions`
--

CREATE TABLE `embroidery_design_versions` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `session_id` bigint(20) UNSIGNED NOT NULL,
  `created_by` bigint(20) UNSIGNED DEFAULT NULL,
  `version_no` int(10) UNSIGNED NOT NULL,
  `design_json` longtext DEFAULT NULL,
  `preview_svg` longtext DEFAULT NULL,
  `estimated_stitches` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `thread_color_count` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `suggested_price` decimal(12,2) NOT NULL DEFAULT 0.00,
  `pricing_confidence` decimal(5,2) NOT NULL DEFAULT 0.00,
  `notes` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `employment_applications`
--

CREATE TABLE `employment_applications` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `job_opening_id` bigint(20) UNSIGNED NOT NULL,
  `applicant_user_id` bigint(20) UNSIGNED NOT NULL,
  `resume_file_path` varchar(255) DEFAULT NULL,
  `portfolio_file_path` varchar(255) DEFAULT NULL,
  `cover_letter` text DEFAULT NULL,
  `applied_role` varchar(100) DEFAULT NULL,
  `expected_salary` decimal(12,2) DEFAULT NULL,
  `status` enum('submitted','screening','interview','shortlisted','accepted','rejected','withdrawn') NOT NULL DEFAULT 'submitted',
  `reviewed_by` bigint(20) UNSIGNED DEFAULT NULL,
  `reviewed_at` datetime DEFAULT NULL,
  `decision_notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `failed_jobs`
--

CREATE TABLE `failed_jobs` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `uuid` varchar(255) NOT NULL,
  `connection` text NOT NULL,
  `queue` text NOT NULL,
  `payload` longtext NOT NULL,
  `exception` longtext NOT NULL,
  `failed_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `fulfillments`
--

CREATE TABLE `fulfillments` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `order_id` bigint(20) UNSIGNED NOT NULL,
  `fulfillment_type` enum('pickup','delivery') NOT NULL,
  `receiver_name` varchar(150) DEFAULT NULL,
  `receiver_contact` varchar(50) DEFAULT NULL,
  `cavite_location_id` bigint(20) UNSIGNED DEFAULT NULL,
  `delivery_address` text DEFAULT NULL,
  `courier_name` varchar(120) DEFAULT NULL,
  `tracking_number` varchar(120) DEFAULT NULL,
  `shipping_fee` decimal(12,2) NOT NULL DEFAULT 0.00,
  `pickup_schedule_at` datetime DEFAULT NULL,
  `shipped_at` datetime DEFAULT NULL,
  `delivered_at` datetime DEFAULT NULL,
  `received_at` datetime DEFAULT NULL,
  `status` enum('pending','scheduled','ready','shipped','delivered','picked_up','failed','cancelled') NOT NULL DEFAULT 'pending',
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `fulfillments`
--

INSERT INTO `fulfillments` (`id`, `order_id`, `fulfillment_type`, `receiver_name`, `receiver_contact`, `cavite_location_id`, `delivery_address`, `courier_name`, `tracking_number`, `shipping_fee`, `pickup_schedule_at`, `shipped_at`, `delivered_at`, `received_at`, `status`, `notes`, `created_at`, `updated_at`) VALUES
(1, 1, 'pickup', 'Juan Dela Cruz', '09123456789', NULL, 'Imus, Cavite', 'Lalamove', 'TRACK-001', 120.00, NULL, '2026-03-14 17:17:37', '2026-03-14 17:17:57', '2026-03-14 17:18:18', 'picked_up', 'Audit delivery setup', '2026-03-14 09:16:47', '2026-03-14 19:21:52');

-- --------------------------------------------------------

--
-- Table structure for table `jobs`
--

CREATE TABLE `jobs` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `queue` varchar(255) NOT NULL,
  `payload` longtext NOT NULL,
  `attempts` tinyint(3) UNSIGNED NOT NULL,
  `reserved_at` int(10) UNSIGNED DEFAULT NULL,
  `available_at` int(10) UNSIGNED NOT NULL,
  `created_at` int(10) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `job_batches`
--

CREATE TABLE `job_batches` (
  `id` varchar(255) NOT NULL,
  `name` varchar(255) NOT NULL,
  `total_jobs` int(11) NOT NULL,
  `pending_jobs` int(11) NOT NULL,
  `failed_jobs` int(11) NOT NULL,
  `failed_job_ids` longtext NOT NULL,
  `options` mediumtext DEFAULT NULL,
  `cancelled_at` int(11) DEFAULT NULL,
  `created_at` int(11) NOT NULL,
  `finished_at` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `job_openings`
--

CREATE TABLE `job_openings` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `shop_id` bigint(20) UNSIGNED NOT NULL,
  `created_by` bigint(20) UNSIGNED NOT NULL,
  `title` varchar(180) NOT NULL,
  `department` varchar(100) DEFAULT NULL,
  `employment_type` enum('full_time','part_time','contract','project_based','internship') NOT NULL DEFAULT 'full_time',
  `role_target` enum('hr','staff','digitizer','embroidery_operator','quality_control','delivery','other') NOT NULL DEFAULT 'staff',
  `description` text NOT NULL,
  `requirements` text DEFAULT NULL,
  `salary_min` decimal(12,2) DEFAULT NULL,
  `salary_max` decimal(12,2) DEFAULT NULL,
  `cavite_location_id` bigint(20) UNSIGNED DEFAULT NULL,
  `location_text` varchar(180) DEFAULT NULL,
  `status` enum('draft','open','closed','filled','cancelled') NOT NULL DEFAULT 'draft',
  `open_until` date DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `job_post_applications`
--

CREATE TABLE `job_post_applications` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `design_post_id` bigint(20) UNSIGNED NOT NULL,
  `shop_id` bigint(20) UNSIGNED NOT NULL,
  `owner_user_id` bigint(20) UNSIGNED NOT NULL,
  `proposed_price` decimal(12,2) DEFAULT NULL,
  `estimated_days` int(11) DEFAULT NULL,
  `available_start_date` date DEFAULT NULL,
  `message` text DEFAULT NULL,
  `sample_work_link` varchar(255) DEFAULT NULL,
  `attachment_path` varchar(255) DEFAULT NULL,
  `status` enum('pending','accepted','rejected','withdrawn') NOT NULL DEFAULT 'pending',
  `applied_at` datetime NOT NULL DEFAULT current_timestamp(),
  `responded_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `messages`
--

CREATE TABLE `messages` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `thread_id` bigint(20) UNSIGNED DEFAULT NULL,
  `from_user_id` bigint(20) UNSIGNED NOT NULL,
  `to_user_id` bigint(20) UNSIGNED NOT NULL,
  `order_id` bigint(20) UNSIGNED DEFAULT NULL,
  `design_post_id` bigint(20) UNSIGNED DEFAULT NULL,
  `parent_message_id` bigint(20) UNSIGNED DEFAULT NULL,
  `message_text` text NOT NULL,
  `is_read` tinyint(1) NOT NULL DEFAULT 0,
  `read_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `message_attachments`
--

CREATE TABLE `message_attachments` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `message_id` bigint(20) UNSIGNED NOT NULL,
  `file_name` varchar(255) NOT NULL,
  `file_path` varchar(255) NOT NULL,
  `mime_type` varchar(120) DEFAULT NULL,
  `file_size` bigint(20) UNSIGNED DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `message_threads`
--

CREATE TABLE `message_threads` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `thread_type` varchar(40) NOT NULL DEFAULT 'general',
  `subject` varchar(180) NOT NULL,
  `order_id` bigint(20) UNSIGNED DEFAULT NULL,
  `design_post_id` bigint(20) UNSIGNED DEFAULT NULL,
  `shop_id` bigint(20) UNSIGNED DEFAULT NULL,
  `created_by` bigint(20) UNSIGNED DEFAULT NULL,
  `last_message_at` datetime DEFAULT NULL,
  `is_closed` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `message_thread_participants`
--

CREATE TABLE `message_thread_participants` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `thread_id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `joined_at` datetime DEFAULT NULL,
  `last_read_at` datetime DEFAULT NULL,
  `is_muted` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

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
(1, '0001_01_01_000000_create_users_table', 1),
(2, '0001_01_01_000001_create_cache_table', 1),
(3, '0001_01_01_000002_create_jobs_table', 1),
(4, '2026_03_14_041422_create_personal_access_tokens_table', 2),
(5, '2026_03_14_155321_create_order_exceptions_table', 3),
(6, '2026_03_15_000001_create_order_exceptions_table', 4),
(7, '2026_03_15_230000_create_fulfillments_table', 5),
(8, '2026_03_15_231000_create_order_assignments_table', 6),
(9, '2026_03_14_180500_create_dss_shop_metrics_table', 7),
(10, '2026_03_14_180600_create_dss_recommendations_table', 7),
(11, '2026_03_15_210100_expand_client_profiles_table', 8),
(12, '2026_03_15_210200_create_design_customizations_table', 8),
(13, '2026_03_15_210300_create_design_proofs_table', 8),
(14, '2026_03_15_210400_create_price_suggestion_rules_table', 8),
(15, '2026_03_15_210500_create_bargaining_offers_table', 8),
(16, '2026_03_15_210600_create_shop_projects_table', 8),
(17, '2026_03_16_000100_enhance_design_customizations_table', 9),
(18, '2026_03_16_000200_create_design_customization_snapshots_table', 9),
(19, '2026_03_16_000300_create_client_saved_addresses_table', 9),
(20, '2026_03_16_000400_enhance_design_proofs_and_bargaining_offers_table', 9),
(21, '2026_03_16_000500_create_operational_alerts_table', 9),
(22, '2026_03_15_120000_create_message_threads_table', 10),
(23, '2026_03_15_120100_enhance_order_files_table', 10),
(24, '2026_03_15_120200_create_pricing_rules_table', 10),
(25, '2026_03_15_120300_create_operational_alerts_table', 10),
(26, '2026_03_16_000000_create_embroidery_design_sessions_table', 10),
(27, '2026_03_16_000100_create_embroidery_design_versions_table', 10),
(28, '2026_03_16_010000_create_owner_management_tables', 11),
(29, '2026_03_16_020000_fix_shop_services_category_column', 12),
(30, '2026_03_16_030000_create_client_workspace_tables', 13),
(31, '2026_03_17_000500_expand_client_profile_and_address_fields', 14),
(32, '2026_03_17_200000_expand_client_profile_and_address_fields', 15),
(33, '2026_03_17_200000_expand_client_profile_for_workspace', 16),
(34, '2026_03_17_230000_create_owner_pricing_and_ops_expansions', 17),
(35, '2026_03_18_230000_expand_owner_pricing_and_operations', 18),
(36, '2026_03_18_231500_expand_design_workflow_for_phase3', 19),
(37, '2026_03_18_233500_add_phase4_design_operations', 19),
(38, '2026_03_19_000500_add_digitizing_workflow_tables', 19);

-- --------------------------------------------------------

--
-- Table structure for table `mobile_device_tokens`
--

CREATE TABLE `mobile_device_tokens` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `platform` enum('android','ios','web_push') NOT NULL,
  `device_name` varchar(150) DEFAULT NULL,
  `device_token` varchar(255) NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `last_used_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

CREATE TABLE `notifications` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `type` varchar(100) NOT NULL,
  `category` varchar(50) DEFAULT NULL,
  `priority` varchar(20) DEFAULT NULL,
  `title` varchar(180) NOT NULL,
  `message` text NOT NULL,
  `action_label` varchar(255) DEFAULT NULL,
  `reference_type` varchar(50) DEFAULT NULL,
  `reference_id` bigint(20) UNSIGNED DEFAULT NULL,
  `channel` enum('web','mobile','email','sms') NOT NULL DEFAULT 'web',
  `is_read` tinyint(1) NOT NULL DEFAULT 0,
  `read_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `notifications`
--

INSERT INTO `notifications` (`id`, `user_id`, `type`, `category`, `priority`, `title`, `message`, `action_label`, `reference_type`, `reference_id`, `channel`, `is_read`, `read_at`, `created_at`) VALUES
(1, 3, 'order_created', NULL, NULL, 'New order received', 'A new order ORD-20260314-0683 was submitted.', NULL, 'order', 1, 'web', 0, NULL, '2026-03-14 05:32:09'),
(2, 3, 'payment_submitted', NULL, NULL, 'Payment submitted', 'A payment was submitted for order ORD-20260314-0683.', NULL, 'payment', 1, 'web', 0, NULL, '2026-03-14 06:22:48'),
(3, 6, 'payment_confirmed', NULL, NULL, 'Payment confirmed', 'Your payment for order ORD-20260314-0683 has been confirmed.', NULL, 'payment', 1, 'web', 0, NULL, '2026-03-14 06:45:44'),
(4, 6, 'order_stage_failed', NULL, NULL, 'Order stage issue', 'There was an issue during \"Completed\" for order ORD-20260314-0683.', NULL, 'order', 1, 'web', 0, NULL, '2026-03-14 08:58:46'),
(6, 6, 'order_ready_for_pickup', NULL, NULL, 'Order ready for pickup', 'Order ORD-20260314-0683 is ready for pickup.', NULL, 'order', 1, 'web', 0, NULL, '2026-03-14 09:17:15'),
(7, 6, 'order_shipped', NULL, NULL, 'Order shipped', 'Order ORD-20260314-0683 has been shipped.', NULL, 'order', 1, 'web', 0, NULL, '2026-03-14 09:17:37'),
(8, 6, 'order_delivered', NULL, NULL, 'Order delivered', 'Order ORD-20260314-0683 has been delivered.', NULL, 'order', 1, 'web', 0, NULL, '2026-03-14 09:17:57'),
(9, 6, 'order_completed', NULL, NULL, 'Order completed', 'Order ORD-20260314-0683 was picked up and marked completed.', NULL, 'order', 1, 'web', 0, NULL, '2026-03-14 09:18:18'),
(14, 6, 'revision_requested', NULL, NULL, 'Revision requested', 'Revision #1 was requested for order ORD-20260314-0683.', NULL, 'order_revision', 1, 'web', 0, NULL, '2026-03-14 09:58:47'),
(15, 3, 'revision_requested', NULL, NULL, 'Revision requested', 'Revision #1 was requested for order ORD-20260314-0683.', NULL, 'order_revision', 1, 'web', 0, NULL, '2026-03-14 09:58:47'),
(26, 5, 'order_assignment_created', NULL, NULL, 'New work assignment', 'You were assigned quality check for order ORD-20260314-0683.', NULL, 'order', 1, 'web', 0, NULL, '2026-03-14 19:19:51'),
(27, 3, 'order_assignment_created', NULL, NULL, 'Order assignment created', 'Quality check was assigned for order ORD-20260314-0683.', NULL, 'order', 1, 'web', 0, NULL, '2026-03-14 19:19:51'),
(28, 6, 'revision_requested', NULL, NULL, 'Revision requested', 'Revision #2 was requested for order ORD-20260314-0683.', NULL, 'order_revision', 2, 'web', 0, NULL, '2026-03-14 19:21:12'),
(30, 3, 'order_assignment_accepted', NULL, NULL, 'Assignment accepted', 'Staff One accepted digitizing for order ORD-20260314-0683.', NULL, 'order', 1, 'web', 0, NULL, '2026-03-14 19:24:48'),
(31, 3, 'order_assignment_completed', NULL, NULL, 'Assignment completed', 'Staff One completed digitizing for order ORD-20260314-0683.', NULL, 'order', 1, 'web', 0, NULL, '2026-03-14 19:25:25'),
(32, 6, 'revision_updated', NULL, NULL, 'Revision updated', 'Revision #1 for order ORD-20260314-0683 is now preview_uploaded.', NULL, 'order_revision', 1, 'web', 0, NULL, '2026-03-14 19:38:32'),
(35, 3, 'revision_requested', NULL, NULL, 'Revision requested', 'Revision #3 was requested for order ORD-20260314-0683.', NULL, 'order_revision', 3, 'web', 0, NULL, '2026-03-14 19:52:53'),
(38, 3, 'revision_updated', NULL, NULL, 'Revision updated', 'Revision #1 for order ORD-20260314-0683 is now rejected.', NULL, 'order_revision', 1, 'web', 0, NULL, '2026-03-14 19:55:42'),
(39, 5, 'revision_updated', NULL, NULL, 'Revision updated', 'Revision #1 for order ORD-20260314-0683 is now rejected.', NULL, 'order_revision', 1, 'web', 0, NULL, '2026-03-14 19:55:42'),
(40, 6, 'order_cancelled', NULL, NULL, 'Order cancelled', 'Your order ORD-20260314-0683 has been cancelled. Reason: Cancelled from frontend dashboard.', NULL, 'order', 1, 'web', 0, NULL, '2026-03-15 02:29:53'),
(41, 3, 'operational_alert', 'exceptions', 'medium', 'Delay risk detected', 'Order ORD-20260314-0683 shows delay risk: stage idle more than 24h.', 'Open', 'operational_alert', 1, 'web', 0, NULL, '2026-03-16 08:29:49'),
(42, 4, 'operational_alert', 'exceptions', 'medium', 'Delay risk detected', 'Order ORD-20260314-0683 shows delay risk: stage idle more than 24h.', 'Open', 'operational_alert', 1, 'web', 0, NULL, '2026-03-16 08:29:49');

-- --------------------------------------------------------

--
-- Table structure for table `operational_alerts`
--

CREATE TABLE `operational_alerts` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `shop_id` bigint(20) UNSIGNED DEFAULT NULL,
  `order_id` bigint(20) UNSIGNED DEFAULT NULL,
  `user_id` bigint(20) UNSIGNED DEFAULT NULL,
  `category` varchar(80) NOT NULL,
  `severity` enum('low','medium','high','critical') NOT NULL DEFAULT 'medium',
  `title` varchar(180) NOT NULL,
  `message` text NOT NULL,
  `reference_type` varchar(80) DEFAULT NULL,
  `reference_id` bigint(20) UNSIGNED DEFAULT NULL,
  `status` enum('open','resolved','dismissed') NOT NULL DEFAULT 'open',
  `resolved_at` datetime DEFAULT NULL,
  `meta_json` longtext DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `operational_alerts`
--

INSERT INTO `operational_alerts` (`id`, `shop_id`, `order_id`, `user_id`, `category`, `severity`, `title`, `message`, `reference_type`, `reference_id`, `status`, `resolved_at`, `meta_json`, `created_at`, `updated_at`) VALUES
(1, 1, 1, NULL, 'delay_prediction', 'medium', 'Delay risk detected', 'Order ORD-20260314-0683 shows delay risk: stage idle more than 24h.', 'order', 1, 'open', NULL, '{\"signals\":[\"stage idle more than 24h\"],\"current_stage\":\"cancelled\"}', '2026-03-16 08:29:49', '2026-03-16 08:29:49');

-- --------------------------------------------------------

--
-- Table structure for table `orders`
--

CREATE TABLE `orders` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `order_number` varchar(40) NOT NULL,
  `client_user_id` bigint(20) UNSIGNED NOT NULL,
  `shop_id` bigint(20) UNSIGNED NOT NULL,
  `source_design_post_id` bigint(20) UNSIGNED DEFAULT NULL,
  `service_id` bigint(20) UNSIGNED DEFAULT NULL,
  `latest_quote_id` bigint(20) UNSIGNED DEFAULT NULL,
  `approved_quote_id` bigint(20) UNSIGNED DEFAULT NULL,
  `order_type` enum('direct_order','custom_order','marketplace_job') NOT NULL DEFAULT 'custom_order',
  `status` enum('pending','quoted','approved','in_production','ready_for_pickup','shipped','completed','cancelled','rejected') NOT NULL DEFAULT 'pending',
  `current_stage` enum('intake','quotation','payment_waiting','digitizing','mockup','client_approval','production','quality_check','packing','pickup_ready','shipping','delivered','completed','cancelled') NOT NULL DEFAULT 'intake',
  `payment_status` enum('unpaid','partial','paid','refunded') NOT NULL DEFAULT 'unpaid',
  `fulfillment_type` enum('pickup','delivery') NOT NULL DEFAULT 'pickup',
  `subtotal` decimal(12,2) NOT NULL DEFAULT 0.00,
  `customization_fee` decimal(12,2) NOT NULL DEFAULT 0.00,
  `rush_fee` decimal(12,2) NOT NULL DEFAULT 0.00,
  `discount_amount` decimal(12,2) NOT NULL DEFAULT 0.00,
  `total_amount` decimal(12,2) NOT NULL DEFAULT 0.00,
  `quoted_at` datetime DEFAULT NULL,
  `approved_at` datetime DEFAULT NULL,
  `payment_due_date` date DEFAULT NULL,
  `due_date` date DEFAULT NULL,
  `completed_at` datetime DEFAULT NULL,
  `cancelled_at` datetime DEFAULT NULL,
  `cancelled_reason` text DEFAULT NULL,
  `delivery_address` text DEFAULT NULL,
  `customer_notes` text DEFAULT NULL,
  `internal_notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `orders`
--

INSERT INTO `orders` (`id`, `order_number`, `client_user_id`, `shop_id`, `source_design_post_id`, `service_id`, `latest_quote_id`, `approved_quote_id`, `order_type`, `status`, `current_stage`, `payment_status`, `fulfillment_type`, `subtotal`, `customization_fee`, `rush_fee`, `discount_amount`, `total_amount`, `quoted_at`, `approved_at`, `payment_due_date`, `due_date`, `completed_at`, `cancelled_at`, `cancelled_reason`, `delivery_address`, `customer_notes`, `internal_notes`, `created_at`, `updated_at`) VALUES
(1, 'ORD-20260314-0683', 6, 1, NULL, 1, 1, NULL, 'direct_order', 'cancelled', 'cancelled', 'partial', 'pickup', 1500.00, 0.00, 0.00, 0.00, 1500.00, '2026-03-14 13:55:46', NULL, NULL, NULL, '2026-03-14 17:18:18', '2026-03-15 10:29:53', 'Cancelled from frontend dashboard.', 'Imus, Cavite', 'Need logo embroidery on 10 shirts', NULL, '2026-03-14 05:32:09', '2026-03-15 02:29:53');

-- --------------------------------------------------------

--
-- Table structure for table `order_assignments`
--

CREATE TABLE `order_assignments` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `order_id` bigint(20) UNSIGNED NOT NULL,
  `assigned_to` bigint(20) UNSIGNED NOT NULL,
  `assigned_by` bigint(20) UNSIGNED NOT NULL,
  `assignment_role` enum('hr','staff') NOT NULL,
  `assignment_type` enum('digitizing','embroidery','quality_check','packing','delivery','other') NOT NULL DEFAULT 'embroidery',
  `status` enum('assigned','in_progress','done','cancelled') NOT NULL DEFAULT 'assigned',
  `assigned_at` datetime NOT NULL DEFAULT current_timestamp(),
  `completed_at` datetime DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `order_assignments`
--

INSERT INTO `order_assignments` (`id`, `order_id`, `assigned_to`, `assigned_by`, `assignment_role`, `assignment_type`, `status`, `assigned_at`, `completed_at`, `notes`, `created_at`, `updated_at`) VALUES
(1, 1, 5, 3, 'staff', 'digitizing', 'done', '2026-03-14 17:36:45', '2026-03-15 03:25:25', 'Task completed during audit', '2026-03-14 09:36:45', '2026-03-14 19:25:25'),
(2, 1, 5, 3, 'staff', 'quality_check', 'assigned', '2026-03-14 17:36:58', NULL, NULL, '2026-03-14 09:36:58', '2026-03-14 09:36:58'),
(3, 1, 5, 5, 'staff', 'other', 'done', '2026-03-14 17:58:47', '2026-03-14 18:01:08', 'Revision #1 handling: color_change', '2026-03-14 09:58:47', '2026-03-14 10:01:08'),
(4, 1, 5, 3, 'staff', 'digitizing', 'assigned', '2026-03-15 03:16:13', NULL, 'Audit assignment test', '2026-03-14 19:16:13', '2026-03-14 19:16:13'),
(5, 1, 5, 3, 'staff', 'quality_check', 'assigned', '2026-03-15 03:19:51', NULL, NULL, '2026-03-14 19:19:51', '2026-03-14 19:19:51'),
(6, 1, 5, 3, 'staff', 'other', 'assigned', '2026-03-15 03:21:12', NULL, 'Revision #2 handling: color_change', '2026-03-14 19:21:12', '2026-03-14 19:21:12'),
(7, 1, 5, 6, 'staff', 'other', 'assigned', '2026-03-15 03:52:53', NULL, 'Revision #3 handling: color_change', '2026-03-14 19:52:53', '2026-03-14 19:52:53');

-- --------------------------------------------------------

--
-- Table structure for table `order_exceptions`
--

CREATE TABLE `order_exceptions` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `order_id` bigint(20) UNSIGNED NOT NULL,
  `exception_type` varchar(255) NOT NULL,
  `severity` enum('low','medium','high','critical') NOT NULL DEFAULT 'medium',
  `status` enum('open','in_progress','escalated','resolved','dismissed') NOT NULL DEFAULT 'open',
  `notes` text DEFAULT NULL,
  `assigned_handler_id` bigint(20) UNSIGNED DEFAULT NULL,
  `escalated_at` timestamp NULL DEFAULT NULL,
  `resolved_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `order_exceptions`
--

INSERT INTO `order_exceptions` (`id`, `order_id`, `exception_type`, `severity`, `status`, `notes`, `assigned_handler_id`, `escalated_at`, `resolved_at`, `created_at`, `updated_at`) VALUES
(1, 1, 'machine_delay', 'high', 'open', 'Machine unavailable', NULL, NULL, NULL, '2026-03-14 08:02:16', '2026-03-14 08:02:16'),
(2, 1, 'stage_failure', 'high', 'open', 'Thread mismatch detected', NULL, NULL, NULL, '2026-03-14 08:58:46', '2026-03-14 08:58:46'),
(3, 1, 'order_cancelled', 'medium', 'resolved', 'Client requested cancellation', NULL, NULL, '2026-03-14 08:59:03', '2026-03-14 08:59:03', '2026-03-14 08:59:03'),
(4, 1, 'order_cancelled', 'medium', 'resolved', 'Audit cancellation test', NULL, NULL, '2026-03-14 19:22:39', '2026-03-14 19:22:39', '2026-03-14 19:22:39'),
(5, 1, 'order_cancelled', 'medium', 'resolved', 'bawal', NULL, NULL, '2026-03-14 19:42:14', '2026-03-14 19:42:14', '2026-03-14 19:42:14'),
(6, 1, 'order_cancelled', 'medium', 'resolved', 'Cancelled from frontend dashboard.', NULL, NULL, '2026-03-15 02:29:53', '2026-03-15 02:29:53', '2026-03-15 02:29:53');

-- --------------------------------------------------------

--
-- Table structure for table `order_files`
--

CREATE TABLE `order_files` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `order_id` bigint(20) UNSIGNED NOT NULL,
  `order_item_id` bigint(20) UNSIGNED DEFAULT NULL,
  `uploaded_by` bigint(20) UNSIGNED NOT NULL,
  `file_type` enum('reference','design_source','preview','stitch_file','final_output','other') NOT NULL DEFAULT 'reference',
  `file_name` varchar(255) NOT NULL,
  `file_path` varchar(255) NOT NULL,
  `mime_type` varchar(120) DEFAULT NULL,
  `file_size` bigint(20) UNSIGNED DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `version_no` int(10) UNSIGNED NOT NULL DEFAULT 1,
  `is_canonical` tinyint(1) NOT NULL DEFAULT 0,
  `approved_at` datetime DEFAULT NULL,
  `superseded_by_id` bigint(20) UNSIGNED DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `order_items`
--

CREATE TABLE `order_items` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `order_id` bigint(20) UNSIGNED NOT NULL,
  `item_name` varchar(180) NOT NULL,
  `garment_type` varchar(100) DEFAULT NULL,
  `size_label` varchar(50) DEFAULT NULL,
  `fabric_type` varchar(100) DEFAULT NULL,
  `placement_area` varchar(100) DEFAULT NULL,
  `placement_notes` varchar(255) DEFAULT NULL,
  `embroidery_type` enum('flat','3d_puff','patch','applique','digitized','other') NOT NULL DEFAULT 'flat',
  `backing_type` varchar(100) DEFAULT NULL,
  `width_mm` decimal(10,2) DEFAULT NULL,
  `height_mm` decimal(10,2) DEFAULT NULL,
  `stitch_count` int(11) DEFAULT NULL,
  `thread_colors` int(11) DEFAULT NULL,
  `color_notes` varchar(255) DEFAULT NULL,
  `quantity` int(11) NOT NULL DEFAULT 1,
  `unit_price` decimal(12,2) NOT NULL DEFAULT 0.00,
  `line_total` decimal(12,2) NOT NULL DEFAULT 0.00,
  `customization_notes` text DEFAULT NULL,
  `mockup_approved` tinyint(1) NOT NULL DEFAULT 0,
  `mockup_approved_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `order_items`
--

INSERT INTO `order_items` (`id`, `order_id`, `item_name`, `garment_type`, `size_label`, `fabric_type`, `placement_area`, `placement_notes`, `embroidery_type`, `backing_type`, `width_mm`, `height_mm`, `stitch_count`, `thread_colors`, `color_notes`, `quantity`, `unit_price`, `line_total`, `customization_notes`, `mockup_approved`, `mockup_approved_at`, `created_at`, `updated_at`) VALUES
(1, 1, 'Polo Shirt Logo', 'Polo Shirt', NULL, NULL, NULL, NULL, 'flat', NULL, NULL, NULL, NULL, NULL, NULL, 10, 150.00, 1500.00, 'Front left chest logo embroidery', 0, NULL, '2026-03-14 05:32:09', '2026-03-14 05:32:09');

-- --------------------------------------------------------

--
-- Table structure for table `order_progress_logs`
--

CREATE TABLE `order_progress_logs` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `order_id` bigint(20) UNSIGNED NOT NULL,
  `status` varchar(50) NOT NULL,
  `title` varchar(150) NOT NULL,
  `description` text DEFAULT NULL,
  `actor_user_id` bigint(20) UNSIGNED DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `order_progress_logs`
--

INSERT INTO `order_progress_logs` (`id`, `order_id`, `status`, `title`, `description`, `actor_user_id`, `created_at`) VALUES
(1, 1, 'pending', 'Order created', 'Client submitted a new order.', 6, '2026-03-14 05:32:09'),
(2, 1, 'payment_verified', 'Payment verified', 'Payment was confirmed and production workflow is ready.', 3, '2026-03-14 06:45:44'),
(3, 1, 'digitizing', 'Production started', 'Digitizing stage started automatically after payment confirmation.', 3, '2026-03-14 06:45:44'),
(4, 1, 'digitizing', 'Stage completed', 'Production started automatically after payment confirmation.', 3, '2026-03-14 06:49:25'),
(5, 1, 'mockup', 'Stage auto-started', NULL, 3, '2026-03-14 06:49:25'),
(6, 1, 'mockup', 'Stage completed', NULL, 3, '2026-03-14 06:56:43'),
(7, 1, 'client_approval', 'Stage auto-started', NULL, 3, '2026-03-14 06:56:43'),
(8, 1, 'client_approval', 'Stage completed', NULL, 3, '2026-03-14 06:57:01'),
(9, 1, 'production', 'Stage auto-started', NULL, 3, '2026-03-14 06:57:01'),
(10, 1, 'production', 'Stage completed', NULL, 3, '2026-03-14 06:57:11'),
(11, 1, 'quality_check', 'Stage auto-started', NULL, 3, '2026-03-14 06:57:11'),
(12, 1, 'quality_check', 'Stage completed', NULL, 3, '2026-03-14 06:57:19'),
(13, 1, 'packing', 'Stage auto-started', NULL, 3, '2026-03-14 06:57:19'),
(14, 1, 'packing', 'Stage completed', NULL, 3, '2026-03-14 06:57:25'),
(15, 1, 'pickup_ready', 'Stage auto-started', NULL, 3, '2026-03-14 06:57:25'),
(16, 1, 'pickup_ready', 'Stage completed', NULL, 3, '2026-03-14 06:57:33'),
(17, 1, 'completed', 'Stage auto-started', NULL, 3, '2026-03-14 06:57:33'),
(18, 1, 'completed', 'Stage completed', NULL, 3, '2026-03-14 06:57:41'),
(19, 1, 'completed', 'Order completed', NULL, 3, '2026-03-14 06:57:41'),
(20, 1, 'stage_failed', 'Stage failed', 'Thread mismatch detected', 3, '2026-03-14 08:58:46'),
(21, 1, 'cancelled', 'Order cancelled', 'Client requested cancellation', 3, '2026-03-14 08:59:03'),
(22, 1, 'ready', 'Fulfillment ready', 'Packed and ready', 3, '2026-03-14 09:17:15'),
(23, 1, 'shipped', 'Order shipped', 'Picked up by rider', 3, '2026-03-14 09:17:37'),
(24, 1, 'delivered', 'Order delivered', 'Delivered to client', 3, '2026-03-14 09:17:57'),
(25, 1, 'picked_up', 'Order picked up', 'Claimed by client', 3, '2026-03-14 09:18:18'),
(26, 1, 'assignment_created', 'Work assignment created', 'Digitizing assigned to Staff One.', 3, '2026-03-14 09:36:45'),
(27, 1, 'assignment_created', 'Work assignment created', 'Quality check assigned to Staff One.', 3, '2026-03-14 09:36:58'),
(28, 1, 'assignment_in_progress', 'Assignment accepted', 'Staff One accepted digitizing assignment.', 5, '2026-03-14 09:39:46'),
(29, 1, 'assignment_done', 'Assignment completed', 'Staff One completed digitizing assignment.', 5, '2026-03-14 09:40:06'),
(30, 1, 'revision_requested', 'Revision requested', 'Revision #1 was requested: Please switch thread color to dark navy.', 5, '2026-03-14 09:58:47'),
(31, 1, 'revision_in_review', 'Revision claimed', 'Staff One claimed revision #1 for review.', 5, '2026-03-14 09:59:04'),
(32, 1, 'revision_preview_uploaded', 'Revision preview uploaded', 'Preview uploaded for revision #1.', 5, '2026-03-14 09:59:27'),
(33, 1, 'revision_approved', 'Revision approved', 'Revision #1 was approved.', 6, '2026-03-14 10:00:11'),
(34, 1, 'revision_implemented', 'Revision implemented', 'Revision #1 was implemented.', 3, '2026-03-14 10:01:08'),
(35, 1, 'assignment_created', 'Work assignment created', 'Digitizing assigned to Staff One.', 3, '2026-03-14 19:16:13'),
(36, 1, 'assignment_created', 'Work assignment created', 'Quality check assigned to Staff One.', 3, '2026-03-14 19:19:51'),
(37, 1, 'revision_requested', 'Revision requested', 'Revision #2 was requested: Switch logo thread to dark navy', 3, '2026-03-14 19:21:12'),
(38, 1, 'cancelled', 'Order cancelled', 'Audit cancellation test', 3, '2026-03-14 19:22:39'),
(39, 1, 'assignment_in_progress', 'Assignment accepted', 'Staff One accepted digitizing assignment.', 5, '2026-03-14 19:24:48'),
(40, 1, 'assignment_done', 'Assignment completed', 'Staff One completed digitizing assignment.', 5, '2026-03-14 19:25:25'),
(41, 1, 'revision_preview_uploaded', 'Revision preview uploaded', 'Preview uploaded for revision #1.', 5, '2026-03-14 19:38:32'),
(42, 1, 'cancelled', 'Order cancelled', 'bawal', 5, '2026-03-14 19:42:14'),
(43, 1, 'revision_requested', 'Revision requested', 'Revision #3 was requested: change the design', 6, '2026-03-14 19:52:53'),
(44, 1, 'revision_approved', 'Revision approved', 'Revision #1 was approved.', 6, '2026-03-14 19:54:14'),
(45, 1, 'revision_rejected', 'Revision rejected', 'Revision #1 was rejected: di na pwede', 6, '2026-03-14 19:55:42'),
(46, 1, 'cancelled', 'Order cancelled', 'Cancelled from frontend dashboard.', 3, '2026-03-15 02:29:53');

-- --------------------------------------------------------

--
-- Table structure for table `order_quotes`
--

CREATE TABLE `order_quotes` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `order_id` bigint(20) UNSIGNED NOT NULL,
  `shop_id` bigint(20) UNSIGNED NOT NULL,
  `quoted_by` bigint(20) UNSIGNED NOT NULL,
  `quote_number` varchar(60) NOT NULL,
  `version_no` int(11) NOT NULL DEFAULT 1,
  `status` enum('draft','sent','accepted','rejected','expired','superseded') NOT NULL DEFAULT 'draft',
  `valid_until` date DEFAULT NULL,
  `subtotal` decimal(12,2) NOT NULL DEFAULT 0.00,
  `digitizing_fee` decimal(12,2) NOT NULL DEFAULT 0.00,
  `material_fee` decimal(12,2) NOT NULL DEFAULT 0.00,
  `labor_fee` decimal(12,2) NOT NULL DEFAULT 0.00,
  `rush_fee` decimal(12,2) NOT NULL DEFAULT 0.00,
  `shipping_fee` decimal(12,2) NOT NULL DEFAULT 0.00,
  `discount_amount` decimal(12,2) NOT NULL DEFAULT 0.00,
  `tax_amount` decimal(12,2) NOT NULL DEFAULT 0.00,
  `total_amount` decimal(12,2) NOT NULL DEFAULT 0.00,
  `terms_and_notes` text DEFAULT NULL,
  `client_response_notes` text DEFAULT NULL,
  `responded_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `order_quotes`
--

INSERT INTO `order_quotes` (`id`, `order_id`, `shop_id`, `quoted_by`, `quote_number`, `version_no`, `status`, `valid_until`, `subtotal`, `digitizing_fee`, `material_fee`, `labor_fee`, `rush_fee`, `shipping_fee`, `discount_amount`, `tax_amount`, `total_amount`, `terms_and_notes`, `client_response_notes`, `responded_at`, `created_at`, `updated_at`) VALUES
(1, 1, 1, 3, 'QT-20260314-1-V1-8WVM', 1, 'sent', '2026-03-20', 1500.00, 200.00, 100.00, 150.00, 0.00, 0.00, 0.00, 0.00, 1950.00, '50% downpayment required before production.', NULL, NULL, '2026-03-14 05:55:46', '2026-03-14 05:55:46');

-- --------------------------------------------------------

--
-- Table structure for table `order_quote_items`
--

CREATE TABLE `order_quote_items` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `order_quote_id` bigint(20) UNSIGNED NOT NULL,
  `order_item_id` bigint(20) UNSIGNED DEFAULT NULL,
  `line_label` varchar(180) NOT NULL,
  `line_type` enum('item','digitizing','material','labor','rush','shipping','discount','other') NOT NULL DEFAULT 'item',
  `quantity` decimal(12,2) NOT NULL DEFAULT 1.00,
  `unit` varchar(50) DEFAULT NULL,
  `unit_price` decimal(12,2) NOT NULL DEFAULT 0.00,
  `line_total` decimal(12,2) NOT NULL DEFAULT 0.00,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `order_quote_items`
--

INSERT INTO `order_quote_items` (`id`, `order_quote_id`, `order_item_id`, `line_label`, `line_type`, `quantity`, `unit`, `unit_price`, `line_total`, `notes`, `created_at`, `updated_at`) VALUES
(1, 1, NULL, 'Embroidery work', 'item', 10.00, 'pcs', 150.00, 1500.00, 'Front chest logo embroidery', '2026-03-14 05:55:46', '2026-03-14 05:55:46'),
(2, 1, NULL, 'Digitizing fee', 'digitizing', 1.00, 'job', 200.00, 200.00, NULL, '2026-03-14 05:55:46', '2026-03-14 05:55:46');

-- --------------------------------------------------------

--
-- Table structure for table `order_revisions`
--

CREATE TABLE `order_revisions` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `order_id` bigint(20) UNSIGNED NOT NULL,
  `order_item_id` bigint(20) UNSIGNED DEFAULT NULL,
  `revision_no` int(11) NOT NULL DEFAULT 1,
  `requested_by` bigint(20) UNSIGNED NOT NULL,
  `handled_by` bigint(20) UNSIGNED DEFAULT NULL,
  `revision_type` enum('design_change','size_change','color_change','placement_change','text_change','file_fix','other') NOT NULL DEFAULT 'design_change',
  `request_notes` text NOT NULL,
  `response_notes` text DEFAULT NULL,
  `preview_file_path` varchar(255) DEFAULT NULL,
  `status` enum('requested','in_review','preview_uploaded','approved','rejected','implemented','cancelled') NOT NULL DEFAULT 'requested',
  `approved_at` datetime DEFAULT NULL,
  `rejected_at` datetime DEFAULT NULL,
  `completed_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `order_revisions`
--

INSERT INTO `order_revisions` (`id`, `order_id`, `order_item_id`, `revision_no`, `requested_by`, `handled_by`, `revision_type`, `request_notes`, `response_notes`, `preview_file_path`, `status`, `approved_at`, `rejected_at`, `completed_at`, `created_at`, `updated_at`) VALUES
(1, 1, NULL, 1, 5, 5, 'color_change', 'Please switch thread color to dark navy.', 'di na pwede', 'revisions/order-1/rev-1-preview.png', 'rejected', '2026-03-15 03:54:14', '2026-03-15 03:55:42', '2026-03-14 18:01:08', '2026-03-14 09:58:47', '2026-03-14 19:55:42'),
(2, 1, NULL, 2, 3, NULL, 'color_change', 'Switch logo thread to dark navy', NULL, NULL, 'requested', NULL, NULL, NULL, '2026-03-14 19:21:12', '2026-03-14 19:21:12'),
(3, 1, NULL, 3, 6, NULL, 'color_change', 'change the design', NULL, NULL, 'requested', NULL, NULL, NULL, '2026-03-14 19:52:53', '2026-03-14 19:52:53');

-- --------------------------------------------------------

--
-- Table structure for table `order_stage_history`
--

CREATE TABLE `order_stage_history` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `order_id` bigint(20) UNSIGNED NOT NULL,
  `stage_code` enum('intake','quotation','payment_waiting','digitizing','mockup','client_approval','production','quality_check','packing','pickup_ready','shipping','delivered','completed','cancelled') NOT NULL,
  `stage_status` enum('pending','active','done','failed','skipped') NOT NULL DEFAULT 'pending',
  `started_at` datetime DEFAULT NULL,
  `ended_at` datetime DEFAULT NULL,
  `actor_user_id` bigint(20) UNSIGNED DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `order_stage_history`
--

INSERT INTO `order_stage_history` (`id`, `order_id`, `stage_code`, `stage_status`, `started_at`, `ended_at`, `actor_user_id`, `notes`, `created_at`, `updated_at`) VALUES
(1, 1, 'intake', 'active', '2026-03-14 13:32:09', NULL, 6, 'Order created by client.', '2026-03-14 05:32:09', '2026-03-14 05:32:09'),
(2, 1, 'digitizing', 'done', '2026-03-14 14:45:44', '2026-03-14 14:49:25', 3, 'Production started automatically after payment confirmation.', '2026-03-14 06:45:44', '2026-03-14 06:49:25'),
(3, 1, 'mockup', 'done', '2026-03-14 14:49:25', '2026-03-14 14:56:43', 3, NULL, '2026-03-14 06:49:25', '2026-03-14 06:56:43'),
(4, 1, 'client_approval', 'done', '2026-03-14 14:56:43', '2026-03-14 14:57:01', 3, NULL, '2026-03-14 06:56:43', '2026-03-14 06:57:01'),
(5, 1, 'production', 'done', '2026-03-14 14:57:01', '2026-03-14 14:57:11', 3, NULL, '2026-03-14 06:57:01', '2026-03-14 06:57:11'),
(6, 1, 'quality_check', 'done', '2026-03-14 14:57:11', '2026-03-14 14:57:19', 3, NULL, '2026-03-14 06:57:11', '2026-03-14 06:57:19'),
(7, 1, 'packing', 'done', '2026-03-14 14:57:19', '2026-03-14 14:57:25', 3, NULL, '2026-03-14 06:57:19', '2026-03-14 06:57:25'),
(8, 1, 'pickup_ready', 'done', '2026-03-14 14:57:25', '2026-03-14 14:57:33', 3, 'Packed and ready', '2026-03-14 06:57:25', '2026-03-14 09:17:15'),
(9, 1, 'completed', 'done', '2026-03-14 14:57:33', '2026-03-14 17:18:18', 3, 'Claimed by client', '2026-03-14 06:57:33', '2026-03-14 09:18:18'),
(10, 1, 'cancelled', 'done', '2026-03-14 16:59:03', '2026-03-14 16:59:03', 3, 'Client requested cancellation', '2026-03-14 08:59:03', '2026-03-14 08:59:03'),
(11, 1, 'shipping', 'active', '2026-03-14 17:17:37', NULL, 3, 'Picked up by rider', '2026-03-14 09:17:37', '2026-03-14 09:17:37'),
(12, 1, 'delivered', 'active', '2026-03-14 17:17:57', '2026-03-14 17:17:57', 3, 'Delivered to client', '2026-03-14 09:17:57', '2026-03-14 09:17:57'),
(13, 1, 'cancelled', 'done', '2026-03-15 03:22:39', '2026-03-15 03:22:39', 3, 'Audit cancellation test', '2026-03-14 19:22:39', '2026-03-14 19:22:39'),
(14, 1, 'cancelled', 'done', '2026-03-15 03:42:14', '2026-03-15 03:42:14', 5, 'bawal', '2026-03-14 19:42:14', '2026-03-14 19:42:14'),
(15, 1, 'cancelled', 'done', '2026-03-15 10:29:53', '2026-03-15 10:29:53', 3, 'Cancelled from frontend dashboard.', '2026-03-15 02:29:53', '2026-03-15 02:29:53');

-- --------------------------------------------------------

--
-- Table structure for table `owner_pricing_rules`
--

CREATE TABLE `owner_pricing_rules` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `shop_id` bigint(20) UNSIGNED NOT NULL,
  `rule_type` varchar(60) NOT NULL,
  `rule_key` varchar(120) NOT NULL,
  `label` varchar(180) DEFAULT NULL,
  `config_json` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`config_json`)),
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `owner_settings`
--

CREATE TABLE `owner_settings` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `shop_id` bigint(20) UNSIGNED NOT NULL,
  `shop_name` varchar(255) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `contact_number` varchar(255) DEFAULT NULL,
  `contact_email` varchar(255) DEFAULT NULL,
  `operating_hours` varchar(255) DEFAULT NULL,
  `default_labor_rate` decimal(10,2) NOT NULL DEFAULT 0.00,
  `rush_fee_percent` decimal(10,2) NOT NULL DEFAULT 0.00,
  `default_profit_margin` decimal(10,2) NOT NULL DEFAULT 0.00,
  `minimum_order_quantity` int(10) UNSIGNED NOT NULL DEFAULT 1,
  `minimum_billable_amount` decimal(12,2) NOT NULL DEFAULT 0.00,
  `max_manual_discount_percent` decimal(8,2) NOT NULL DEFAULT 0.00,
  `max_rush_orders_per_day` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `cancellation_rules` text DEFAULT NULL,
  `notification_settings_json` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`notification_settings_json`)),
  `delivery_defaults_json` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`delivery_defaults_json`)),
  `ui_preferences_json` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`ui_preferences_json`)),
  `security_settings_json` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`security_settings_json`)),
  `workflow_automation_settings_json` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`workflow_automation_settings_json`)),
  `document_settings_json` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`document_settings_json`)),
  `approval_settings_json` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`approval_settings_json`)),
  `pricing_rules_json` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`pricing_rules_json`)),
  `quote_automation_controls_json` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`quote_automation_controls_json`)),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `owner_settings`
--

INSERT INTO `owner_settings` (`id`, `shop_id`, `shop_name`, `address`, `contact_number`, `contact_email`, `operating_hours`, `default_labor_rate`, `rush_fee_percent`, `default_profit_margin`, `minimum_order_quantity`, `minimum_billable_amount`, `max_manual_discount_percent`, `max_rush_orders_per_day`, `cancellation_rules`, `notification_settings_json`, `delivery_defaults_json`, `ui_preferences_json`, `security_settings_json`, `workflow_automation_settings_json`, `document_settings_json`, `approval_settings_json`, `pricing_rules_json`, `quote_automation_controls_json`, `created_at`, `updated_at`) VALUES
(1, 1, 'Stitch Cavite', NULL, NULL, NULL, 'Mon-Sat 9:00 AM - 6:00 PM', 35.00, 15.00, 20.00, 1, 0.00, 0.00, 5, 'Rush orders are non-refundable once production starts.', '{\"new_order\":true,\"delayed_production\":true,\"payment_received\":true,\"low_stock\":true}', '{\"preferred_courier\":\"LBC\",\"pickup_hours\":\"10:00 AM - 5:00 PM\",\"shipping_fee_rules\":\"Actual courier rate or configured flat rate.\"}', '{\"theme\":\"system\",\"language\":\"en\",\"dashboard_layout\":\"operations_first\"}', '{\"device_access_review\":true,\"login_session_visibility\":true}', '{\"auto_move_order_after_payment\":true,\"auto_create_production_task\":true,\"auto_low_stock_alert\":true,\"auto_notify_owner_on_dispute\":true,\"auto_notify_client_on_proof_update\":true}', '{\"invoice_format\":\"EMB-INV-{number}\",\"quotation_format\":\"EMB-QUO-{number}\",\"receipt_numbering\":\"EMB-REC-{number}\"}', '{\"discount_approver_role\":\"owner\",\"dispute_approver_role\":\"owner\",\"supplier_order_approver_role\":\"owner\"}', NULL, NULL, '2026-03-15 09:36:35', '2026-03-15 09:36:35');

-- --------------------------------------------------------

--
-- Table structure for table `password_reset_tokens`
--

CREATE TABLE `password_reset_tokens` (
  `email` varchar(255) NOT NULL,
  `token` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `payments`
--

CREATE TABLE `payments` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `order_id` bigint(20) UNSIGNED NOT NULL,
  `client_user_id` bigint(20) UNSIGNED NOT NULL,
  `shop_id` bigint(20) UNSIGNED NOT NULL,
  `payment_method_id` bigint(20) UNSIGNED DEFAULT NULL,
  `payment_type` enum('downpayment','partial','full','refund','adjustment') NOT NULL DEFAULT 'partial',
  `amount` decimal(12,2) NOT NULL,
  `proof_file_path` varchar(255) DEFAULT NULL,
  `transaction_reference` varchar(120) DEFAULT NULL,
  `payer_name` varchar(150) DEFAULT NULL,
  `payment_status` enum('pending','submitted','confirmed','rejected','refunded') NOT NULL DEFAULT 'pending',
  `paid_at` datetime DEFAULT NULL,
  `confirmed_at` datetime DEFAULT NULL,
  `confirmed_by` bigint(20) UNSIGNED DEFAULT NULL,
  `rejection_reason` text DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `payments`
--

INSERT INTO `payments` (`id`, `order_id`, `client_user_id`, `shop_id`, `payment_method_id`, `payment_type`, `amount`, `proof_file_path`, `transaction_reference`, `payer_name`, `payment_status`, `paid_at`, `confirmed_at`, `confirmed_by`, `rejection_reason`, `notes`, `created_at`, `updated_at`) VALUES
(1, 1, 6, 1, NULL, 'downpayment', 500.00, NULL, 'TEST-REF-001', 'Client One', 'confirmed', '2026-03-14 14:22:48', '2026-03-14 14:45:44', 3, NULL, 'Initial payment', '2026-03-14 06:22:48', '2026-03-14 06:45:44');

-- --------------------------------------------------------

--
-- Table structure for table `payment_methods`
--

CREATE TABLE `payment_methods` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `shop_id` bigint(20) UNSIGNED DEFAULT NULL,
  `method_code` varchar(50) NOT NULL,
  `method_name` varchar(100) NOT NULL,
  `provider_name` varchar(100) DEFAULT NULL,
  `account_name` varchar(150) DEFAULT NULL,
  `account_number` varchar(100) DEFAULT NULL,
  `qr_image_path` varchar(255) DEFAULT NULL,
  `instructions` text DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `personal_access_tokens`
--

CREATE TABLE `personal_access_tokens` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `tokenable_type` varchar(255) NOT NULL,
  `tokenable_id` bigint(20) UNSIGNED NOT NULL,
  `name` text NOT NULL,
  `token` varchar(64) NOT NULL,
  `abilities` text DEFAULT NULL,
  `last_used_at` timestamp NULL DEFAULT NULL,
  `expires_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `personal_access_tokens`
--

INSERT INTO `personal_access_tokens` (`id`, `tokenable_type`, `tokenable_id`, `name`, `token`, `abilities`, `last_used_at`, `expires_at`, `created_at`, `updated_at`) VALUES
(42, 'App\\Models\\User', 2, 'spa', 'e6d933e64a4be6f988ff7e6690f45d64961ac8862be755bf0a7cb052572e2335', '[\"*\"]', '2026-03-14 22:57:59', NULL, '2026-03-14 21:51:23', '2026-03-14 22:57:59'),
(51, 'App\\Models\\User', 7, 'spa', '4f8f4400adfcf072f814c2e6f4a591ea31e7feba156ca1513fe36c9b0c4f87de', '[\"*\"]', '2026-03-15 00:03:48', NULL, '2026-03-15 00:03:44', '2026-03-15 00:03:48'),
(130, 'App\\Models\\User', 6, 'spa', '7c81fffbf7dfdaf1b4d2a7f3cd4f60b13f88092eefabe65c3f8ce36e01aedf01', '[\"*\"]', '2026-03-17 14:12:19', NULL, '2026-03-17 14:12:17', '2026-03-17 14:12:19');

-- --------------------------------------------------------

--
-- Table structure for table `price_suggestion_rules`
--

CREATE TABLE `price_suggestion_rules` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `rule_code` varchar(80) NOT NULL,
  `rule_name` varchar(180) NOT NULL,
  `category` varchar(80) NOT NULL DEFAULT 'general',
  `amount_type` enum('fixed','percent') NOT NULL DEFAULT 'fixed',
  `amount_value` decimal(12,2) NOT NULL DEFAULT 0.00,
  `conditions_json` longtext DEFAULT NULL,
  `priority` int(11) NOT NULL DEFAULT 1,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `price_suggestion_rules`
--

INSERT INTO `price_suggestion_rules` (`id`, `rule_code`, `rule_name`, `category`, `amount_type`, `amount_value`, `conditions_json`, `priority`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 'RUSH_MARKUP', 'Rush markup baseline', 'speed', 'percent', 15.00, '{\"design_type\":null}', 5, 1, '2026-03-14 23:02:18', '2026-03-14 23:02:18'),
(2, 'PREMIUM_COMPLEXITY', 'Premium complexity markup', 'complexity', 'percent', 12.00, '{\"complexity_level\":\"premium\"}', 10, 1, '2026-03-14 23:02:18', '2026-03-14 23:02:18'),
(3, 'BULK_PREP', 'Bulk preparation fee', 'bulk', 'fixed', 120.00, '{\"minimum_quantity\":50}', 3, 1, '2026-03-14 23:02:18', '2026-03-14 23:02:18');

-- --------------------------------------------------------

--
-- Table structure for table `pricing_rules`
--

CREATE TABLE `pricing_rules` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `shop_id` bigint(20) UNSIGNED NOT NULL,
  `rule_name` varchar(150) NOT NULL,
  `garment_type` varchar(100) DEFAULT NULL,
  `embroidery_type` varchar(50) DEFAULT NULL,
  `placement_area` varchar(100) DEFAULT NULL,
  `min_quantity` int(10) UNSIGNED DEFAULT NULL,
  `max_quantity` int(10) UNSIGNED DEFAULT NULL,
  `base_price` decimal(12,2) NOT NULL DEFAULT 0.00,
  `per_thousand_stitches` decimal(12,2) NOT NULL DEFAULT 0.00,
  `per_color_price` decimal(12,2) NOT NULL DEFAULT 0.00,
  `complexity_multiplier` decimal(8,2) NOT NULL DEFAULT 1.00,
  `rush_fee_multiplier` decimal(8,2) NOT NULL DEFAULT 1.25,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `quality_checks`
--

CREATE TABLE `quality_checks` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `shop_id` bigint(20) UNSIGNED NOT NULL,
  `order_id` bigint(20) UNSIGNED NOT NULL,
  `checked_by` bigint(20) UNSIGNED DEFAULT NULL,
  `result` varchar(255) NOT NULL,
  `issue_notes` text DEFAULT NULL,
  `attachments_json` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`attachments_json`)),
  `rework_required` tinyint(1) NOT NULL DEFAULT 0,
  `action_taken` text DEFAULT NULL,
  `checked_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `raw_materials`
--

CREATE TABLE `raw_materials` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `shop_id` bigint(20) UNSIGNED NOT NULL,
  `supplier_id` bigint(20) UNSIGNED DEFAULT NULL,
  `material_name` varchar(255) NOT NULL,
  `category` varchar(255) DEFAULT NULL,
  `color` varchar(255) DEFAULT NULL,
  `unit` varchar(255) NOT NULL DEFAULT 'pcs',
  `stock_quantity` decimal(12,2) NOT NULL DEFAULT 0.00,
  `reorder_level` decimal(12,2) NOT NULL DEFAULT 0.00,
  `cost_per_unit` decimal(12,2) NOT NULL DEFAULT 0.00,
  `last_restocked_at` timestamp NULL DEFAULT NULL,
  `status` varchar(255) NOT NULL DEFAULT 'in_stock',
  `notes` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `raw_materials`
--

INSERT INTO `raw_materials` (`id`, `shop_id`, `supplier_id`, `material_name`, `category`, `color`, `unit`, `stock_quantity`, `reorder_level`, `cost_per_unit`, `last_restocked_at`, `status`, `notes`, `created_at`, `updated_at`) VALUES
(1, 1, 1, 'thread', 'thread', 'brown', 'pcs', 100.00, 20.00, 1.00, NULL, 'in_stock', NULL, '2026-03-16 19:19:12', '2026-03-16 19:19:12');

-- --------------------------------------------------------

--
-- Table structure for table `reviews`
--

CREATE TABLE `reviews` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `order_id` bigint(20) UNSIGNED NOT NULL,
  `shop_id` bigint(20) UNSIGNED NOT NULL,
  `client_user_id` bigint(20) UNSIGNED NOT NULL,
  `rating` tinyint(3) UNSIGNED NOT NULL,
  `review_title` varchar(180) DEFAULT NULL,
  `review_text` text DEFAULT NULL,
  `is_public` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ;

-- --------------------------------------------------------

--
-- Table structure for table `sessions`
--

CREATE TABLE `sessions` (
  `id` varchar(255) NOT NULL,
  `user_id` bigint(20) UNSIGNED DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `payload` longtext NOT NULL,
  `last_activity` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `sessions`
--

INSERT INTO `sessions` (`id`, `user_id`, `ip_address`, `user_agent`, `payload`, `last_activity`) VALUES
('FvbPjoBKcP35Ujb4AulPpJBzgMPP54bimwrlrW2r', 6, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', 'YTo0OntzOjY6Il90b2tlbiI7czo0MDoidGt4YzZER1JVeXFQS1BOaUVodEFmbXpuTGJOZ0k5NzVQVTdjem9jMyI7czo5OiJfcHJldmlvdXMiO2E6Mjp7czozOiJ1cmwiO3M6Mzg6Imh0dHA6Ly8xMjcuMC4wLjE6ODAwMC9jbGllbnQtZGFzaGJvYXJkIjtzOjU6InJvdXRlIjtzOjE2OiJjbGllbnQuZGFzaGJvYXJkIjt9czo2OiJfZmxhc2giO2E6Mjp7czozOiJvbGQiO2E6MDp7fXM6MzoibmV3IjthOjA6e319czo1MDoibG9naW5fd2ViXzU5YmEzNmFkZGMyYjJmOTQwMTU4MGYwMTRjN2Y1OGVhNGUzMDk4OWQiO2k6Njt9', 1773785537);

-- --------------------------------------------------------

--
-- Table structure for table `shops`
--

CREATE TABLE `shops` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `owner_user_id` bigint(20) UNSIGNED NOT NULL,
  `cavite_location_id` bigint(20) UNSIGNED NOT NULL,
  `shop_name` varchar(150) NOT NULL,
  `slug` varchar(180) NOT NULL,
  `description` text DEFAULT NULL,
  `logo_path` varchar(255) DEFAULT NULL,
  `banner_path` varchar(255) DEFAULT NULL,
  `email` varchar(150) DEFAULT NULL,
  `phone` varchar(30) DEFAULT NULL,
  `address_line` varchar(255) DEFAULT NULL,
  `postal_code` varchar(20) DEFAULT NULL,
  `service_radius_km` decimal(8,2) DEFAULT NULL,
  `verification_status` enum('pending','approved','rejected','suspended') NOT NULL DEFAULT 'pending',
  `approval_notes` text DEFAULT NULL,
  `approved_by` bigint(20) UNSIGNED DEFAULT NULL,
  `approved_at` datetime DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `shops`
--

INSERT INTO `shops` (`id`, `owner_user_id`, `cavite_location_id`, `shop_name`, `slug`, `description`, `logo_path`, `banner_path`, `email`, `phone`, `address_line`, `postal_code`, `service_radius_km`, `verification_status`, `approval_notes`, `approved_by`, `approved_at`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 3, 5, 'Stitch Cavite', 'stitch-cavite-9gkrrk', 'Custom embroidery shop', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'approved', NULL, 1, '2026-03-14 09:59:45', 1, '2026-03-14 01:57:05', '2026-03-14 01:59:45');

-- --------------------------------------------------------

--
-- Table structure for table `shop_couriers`
--

CREATE TABLE `shop_couriers` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `shop_id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL,
  `contact_person` varchar(255) DEFAULT NULL,
  `phone` varchar(255) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `shop_hiring_openings`
--

CREATE TABLE `shop_hiring_openings` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `shop_id` bigint(20) UNSIGNED NOT NULL,
  `title` varchar(255) NOT NULL,
  `department` varchar(100) DEFAULT NULL,
  `employment_type` varchar(50) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `status` varchar(30) NOT NULL DEFAULT 'open',
  `posted_by` bigint(20) UNSIGNED DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `shop_members`
--

CREATE TABLE `shop_members` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `shop_id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `member_role` enum('owner','hr','staff') NOT NULL,
  `position` varchar(120) DEFAULT NULL,
  `approval_status` varchar(40) NOT NULL DEFAULT 'approved',
  `review_notes` text DEFAULT NULL,
  `created_by_user_id` bigint(20) UNSIGNED DEFAULT NULL,
  `hired_by_user_id` bigint(20) UNSIGNED DEFAULT NULL,
  `reviewed_by_user_id` bigint(20) UNSIGNED DEFAULT NULL,
  `reviewed_at` timestamp NULL DEFAULT NULL,
  `employment_status` enum('active','inactive','terminated') NOT NULL DEFAULT 'active',
  `joined_at` datetime NOT NULL DEFAULT current_timestamp(),
  `ended_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `shop_members`
--

INSERT INTO `shop_members` (`id`, `shop_id`, `user_id`, `member_role`, `position`, `approval_status`, `review_notes`, `created_by_user_id`, `hired_by_user_id`, `reviewed_by_user_id`, `reviewed_at`, `employment_status`, `joined_at`, `ended_at`, `created_at`, `updated_at`) VALUES
(1, 1, 3, 'owner', NULL, 'approved', NULL, NULL, NULL, NULL, NULL, 'active', '2026-03-14 17:57:05', NULL, '2026-03-14 01:57:05', '2026-03-14 01:57:05'),
(2, 1, 4, 'hr', NULL, 'approved', NULL, NULL, NULL, NULL, NULL, 'active', '2026-03-14 18:14:30', NULL, '2026-03-14 02:14:30', '2026-03-14 02:14:30'),
(3, 1, 5, 'staff', NULL, 'approved', NULL, NULL, NULL, NULL, NULL, 'active', '2026-03-14 18:15:13', NULL, '2026-03-14 02:15:13', '2026-03-14 02:15:13');

-- --------------------------------------------------------

--
-- Table structure for table `shop_portfolio`
--

CREATE TABLE `shop_portfolio` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `shop_id` bigint(20) UNSIGNED NOT NULL,
  `uploaded_by` bigint(20) UNSIGNED NOT NULL,
  `title` varchar(180) NOT NULL,
  `category` enum('logo','uniform','cap','patch','custom_art','digitizing','other') NOT NULL DEFAULT 'other',
  `description` text DEFAULT NULL,
  `image_path` varchar(255) NOT NULL,
  `is_featured` tinyint(1) NOT NULL DEFAULT 0,
  `is_public` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `shop_projects`
--

CREATE TABLE `shop_projects` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `shop_id` bigint(20) UNSIGNED NOT NULL,
  `created_by` bigint(20) UNSIGNED NOT NULL,
  `title` varchar(180) NOT NULL,
  `description` text NOT NULL,
  `embroidery_size` varchar(120) DEFAULT NULL,
  `canvas_used` varchar(120) DEFAULT NULL,
  `category` varchar(80) DEFAULT NULL,
  `base_price` decimal(12,2) NOT NULL DEFAULT 0.00,
  `min_order_qty` int(11) NOT NULL DEFAULT 1,
  `turnaround_days` int(11) DEFAULT NULL,
  `is_customizable` tinyint(1) NOT NULL DEFAULT 1,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `preview_image_path` varchar(255) DEFAULT NULL,
  `image_path` varchar(255) DEFAULT NULL,
  `default_fulfillment_type` enum('pickup','delivery') NOT NULL DEFAULT 'pickup',
  `automation_profile_json` longtext DEFAULT NULL,
  `tags_json` longtext DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `shop_projects`
--

INSERT INTO `shop_projects` (`id`, `shop_id`, `created_by`, `title`, `description`, `embroidery_size`, `canvas_used`, `category`, `base_price`, `min_order_qty`, `turnaround_days`, `is_customizable`, `is_active`, `preview_image_path`, `image_path`, `default_fulfillment_type`, `automation_profile_json`, `tags_json`, `created_at`, `updated_at`) VALUES
(1, 1, 3, 'Cap embroidery', 'Test', '4x3', 'Cotton', 'custom_project', 250.00, 1, NULL, 1, 1, 'shop-projects/TuZWPzF9gUEgU89MON8xxij8xZ5pYC1SW1S4WFu0.jpg', NULL, 'pickup', NULL, NULL, '2026-03-17 08:47:02', '2026-03-17 08:47:02');

-- --------------------------------------------------------

--
-- Table structure for table `shop_services`
--

CREATE TABLE `shop_services` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `shop_id` bigint(20) UNSIGNED NOT NULL,
  `service_name` varchar(150) NOT NULL,
  `category` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `base_price` decimal(12,2) NOT NULL DEFAULT 0.00,
  `unit_price` decimal(10,2) NOT NULL DEFAULT 0.00,
  `stitch_range` varchar(255) DEFAULT NULL,
  `complexity_multiplier` decimal(10,2) NOT NULL DEFAULT 1.00,
  `rush_fee_allowed` tinyint(1) NOT NULL DEFAULT 1,
  `rush_multiplier` decimal(8,2) NOT NULL DEFAULT 1.00,
  `price_type` enum('fixed','per_piece','per_thousand_stitches','quoted') NOT NULL DEFAULT 'quoted',
  `min_order_qty` int(11) NOT NULL DEFAULT 1,
  `turnaround_days` int(11) DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `shop_services`
--

INSERT INTO `shop_services` (`id`, `shop_id`, `service_name`, `category`, `description`, `base_price`, `unit_price`, `stitch_range`, `complexity_multiplier`, `rush_fee_allowed`, `rush_multiplier`, `price_type`, `min_order_qty`, `turnaround_days`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 1, 'Logo Embroidery', 'logo_embroidery', 'Custom machine embroidery for logos', 150.00, 0.00, NULL, 1.00, 1, 1.00, 'fixed', 1, 3, 1, '2026-03-14 02:15:57', '2026-03-14 02:15:57'),
(2, 1, 'Name embroidery', 'name_embroidery', NULL, 60.00, 60.00, '1500-5000', 1.00, 1, 1.00, 'quoted', 1, 3, 1, '2026-03-15 09:54:46', '2026-03-15 09:54:46'),
(3, 1, 'Patch embroidery', 'patch_embroidery', NULL, 90.00, 90.00, '1500-5000', 1.00, 1, 1.00, 'quoted', 5, 3, 1, '2026-03-15 09:54:46', '2026-03-15 09:54:46'),
(4, 1, 'Uniform embroidery', 'uniform_embroidery', NULL, 140.00, 140.00, '1500-5000', 1.00, 1, 1.00, 'quoted', 5, 3, 1, '2026-03-15 09:54:47', '2026-03-15 09:54:47'),
(5, 1, 'Cap embroidery', 'cap_embroidery', NULL, 135.00, 135.00, '1500-5000', 1.00, 1, 1.00, 'quoted', 5, 3, 1, '2026-03-15 09:54:47', '2026-03-15 09:54:47'),
(6, 1, 'Custom design embroidery', 'custom_design_embroidery', NULL, 180.00, 180.00, '1500-5000', 1.00, 1, 1.00, 'quoted', 1, 3, 1, '2026-03-15 09:54:47', '2026-03-15 09:54:47');

-- --------------------------------------------------------

--
-- Table structure for table `shop_service_areas`
--

CREATE TABLE `shop_service_areas` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `shop_id` bigint(20) UNSIGNED NOT NULL,
  `cavite_location_id` bigint(20) UNSIGNED NOT NULL,
  `supports_pickup` tinyint(1) NOT NULL DEFAULT 1,
  `supports_delivery` tinyint(1) NOT NULL DEFAULT 0,
  `delivery_fee_base` decimal(12,2) NOT NULL DEFAULT 0.00,
  `notes` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `staff_profiles`
--

CREATE TABLE `staff_profiles` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `employee_code` varchar(50) DEFAULT NULL,
  `position_title` varchar(100) DEFAULT NULL,
  `hire_date` date DEFAULT NULL,
  `salary_type` enum('daily','weekly','monthly','per_piece') DEFAULT NULL,
  `salary_amount` decimal(12,2) DEFAULT NULL,
  `emergency_contact_name` varchar(150) DEFAULT NULL,
  `emergency_contact_phone` varchar(30) DEFAULT NULL,
  `mobile_can_update_tasks` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `suppliers`
--

CREATE TABLE `suppliers` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `shop_id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL,
  `contact_person` varchar(255) DEFAULT NULL,
  `phone` varchar(255) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `materials_supplied` text DEFAULT NULL,
  `lead_time_days` int(10) UNSIGNED DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `status` varchar(255) NOT NULL DEFAULT 'active',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `suppliers`
--

INSERT INTO `suppliers` (`id`, `shop_id`, `name`, `contact_person`, `phone`, `email`, `address`, `materials_supplied`, `lead_time_days`, `notes`, `status`, `created_at`, `updated_at`) VALUES
(1, 1, 'thread supplies', 'Cardo Dalisay', '09772123215', 'cardo@gmail.com', 'sa tabe', 'threads,', 7, NULL, 'active', '2026-03-16 19:17:21', '2026-03-16 19:17:21');

-- --------------------------------------------------------

--
-- Table structure for table `supply_orders`
--

CREATE TABLE `supply_orders` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `shop_id` bigint(20) UNSIGNED NOT NULL,
  `supplier_id` bigint(20) UNSIGNED NOT NULL,
  `po_number` varchar(255) NOT NULL,
  `materials_json` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`materials_json`)),
  `quantity_total` decimal(12,2) NOT NULL DEFAULT 0.00,
  `total_cost` decimal(12,2) NOT NULL DEFAULT 0.00,
  `ordered_at` date DEFAULT NULL,
  `expected_arrival_at` date DEFAULT NULL,
  `received_at` date DEFAULT NULL,
  `status` varchar(255) NOT NULL DEFAULT 'draft',
  `notes` text DEFAULT NULL,
  `approved_by` bigint(20) UNSIGNED DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `support_tickets`
--

CREATE TABLE `support_tickets` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `shop_id` bigint(20) UNSIGNED DEFAULT NULL,
  `order_id` bigint(20) UNSIGNED DEFAULT NULL,
  `subject` varchar(255) NOT NULL,
  `category` varchar(50) NOT NULL DEFAULT 'support',
  `priority` varchar(20) NOT NULL DEFAULT 'medium',
  `status` varchar(30) NOT NULL DEFAULT 'open',
  `message` longtext NOT NULL,
  `attachments_json` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`attachments_json`)),
  `assigned_to` bigint(20) UNSIGNED DEFAULT NULL,
  `resolved_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `phone` varchar(30) DEFAULT NULL,
  `profile_photo` varchar(255) DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `email_verified_at` timestamp NULL DEFAULT NULL,
  `last_login_at` timestamp NULL DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('admin','owner','hr','staff','client') NOT NULL DEFAULT 'client',
  `shop_id` bigint(20) UNSIGNED DEFAULT NULL,
  `remember_token` varchar(100) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `name`, `email`, `phone`, `profile_photo`, `is_active`, `email_verified_at`, `last_login_at`, `password`, `role`, `shop_id`, `remember_token`, `created_at`, `updated_at`) VALUES
(1, 'System Admin', 'admin@embroidery.com', NULL, NULL, 1, NULL, '2026-03-14 21:49:32', '$2y$12$7LFbl5uzyjMZRX8LvWsbRuFtW/TDx9TaI0qw9A/i9yNm39xylTShK', 'admin', NULL, NULL, '2026-03-13 18:12:31', '2026-03-14 21:49:32'),
(2, 'Test Owner', 'owner1@example.com', '09123456789', NULL, 1, NULL, '2026-03-14 21:51:23', '$2y$12$ALrZcOD0YxJ8N59RtwuJyOug3zVhr3vcFYEMFvGuhO0YffFfB/R/m', 'owner', NULL, NULL, '2026-03-13 19:32:59', '2026-03-14 21:51:23'),
(3, 'Test Owner2', 'owner2@example.com', '09123456789', NULL, 1, NULL, '2026-03-17 09:15:18', '$2y$12$m9QrXciSoQjxO2lhXIhS6uXFqAldvHh6aykxKxT8UdhS9u7KK52Te', 'owner', 1, NULL, '2026-03-13 19:38:07', '2026-03-17 09:15:18'),
(4, 'HR One', 'hr1@example.com', '09123456789', NULL, 1, NULL, '2026-03-14 21:14:44', '$2y$12$pZZF7m39hN6eFA4vjoHMMOIRGRW9xRHS448Mee7RWDtfI7.JsFaIm', 'hr', 1, NULL, '2026-03-14 02:14:30', '2026-03-14 21:14:44'),
(5, 'Staff One', 'staff1@example.com', '09123456789', NULL, 1, NULL, '2026-03-14 21:15:20', '$2y$12$11uXoEag13uXyj.nfbTO.uUiWHtEqAl9xoIY4TUnGxK254r8i7YpW', 'staff', 1, NULL, '2026-03-14 02:15:13', '2026-03-14 21:15:20'),
(6, 'Test Client1', 'client1@example.com', '09123456789', NULL, 1, NULL, '2026-03-17 14:12:17', '$2y$12$h.CPUd5p.dlzLNDX4Jxg4.fPLvcFkTYkJPpuQKFBCOG1OkLrabokS', 'client', NULL, NULL, '2026-03-14 05:30:57', '2026-03-17 14:12:17'),
(7, 'Benedict Oriol', 'ben@example.com', '09995556565', NULL, 1, NULL, NULL, '$2y$12$vUmyl1C1qka7RSFhXLahZOFgeBiTP4A8rBXnUwB4SH8QUcH9vQ/Zm', 'client', NULL, NULL, '2026-03-15 00:03:44', '2026-03-15 00:03:44');

-- --------------------------------------------------------

--
-- Table structure for table `workforce_schedules`
--

CREATE TABLE `workforce_schedules` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `shop_id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `order_id` bigint(20) UNSIGNED DEFAULT NULL,
  `shift_date` date NOT NULL,
  `shift_start` time DEFAULT NULL,
  `shift_end` time DEFAULT NULL,
  `deadline_at` timestamp NULL DEFAULT NULL,
  `assignment_notes` text DEFAULT NULL,
  `is_day_off` tinyint(1) NOT NULL DEFAULT 0,
  `is_overtime` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `audit_logs`
--
ALTER TABLE `audit_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_audit_logs_actor` (`actor_user_id`),
  ADD KEY `idx_audit_logs_shop` (`shop_id`),
  ADD KEY `idx_audit_logs_entity` (`entity_type`,`entity_id`),
  ADD KEY `idx_audit_logs_action` (`action`);

--
-- Indexes for table `bargaining_offers`
--
ALTER TABLE `bargaining_offers`
  ADD PRIMARY KEY (`id`),
  ADD KEY `bargaining_offers_design_post_id_foreign` (`design_post_id`),
  ADD KEY `bargaining_offers_job_post_application_id_foreign` (`job_post_application_id`),
  ADD KEY `bargaining_offers_parent_offer_id_foreign` (`parent_offer_id`),
  ADD KEY `bargaining_offers_offered_by_user_id_foreign` (`offered_by_user_id`),
  ADD KEY `bargaining_offers_responded_by_foreign` (`responded_by`);

--
-- Indexes for table `cache`
--
ALTER TABLE `cache`
  ADD PRIMARY KEY (`key`),
  ADD KEY `cache_expiration_index` (`expiration`);

--
-- Indexes for table `cache_locks`
--
ALTER TABLE `cache_locks`
  ADD PRIMARY KEY (`key`),
  ADD KEY `cache_locks_expiration_index` (`expiration`);

--
-- Indexes for table `cavite_locations`
--
ALTER TABLE `cavite_locations`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_cavite_locations_name` (`name`);

--
-- Indexes for table `client_payment_methods`
--
ALTER TABLE `client_payment_methods`
  ADD PRIMARY KEY (`id`),
  ADD KEY `client_payment_methods_user_id_foreign` (`user_id`);

--
-- Indexes for table `client_profiles`
--
ALTER TABLE `client_profiles`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_client_profiles_user` (`user_id`),
  ADD KEY `idx_client_profiles_location` (`cavite_location_id`),
  ADD KEY `client_profiles_preferred_payment_method_id_foreign` (`preferred_payment_method_id`);

--
-- Indexes for table `client_saved_addresses`
--
ALTER TABLE `client_saved_addresses`
  ADD PRIMARY KEY (`id`),
  ADD KEY `client_saved_addresses_client_profile_id_foreign` (`client_profile_id`),
  ADD KEY `client_saved_addresses_cavite_location_id_foreign` (`cavite_location_id`);

--
-- Indexes for table `design_customizations`
--
ALTER TABLE `design_customizations`
  ADD PRIMARY KEY (`id`),
  ADD KEY `design_customizations_design_post_id_foreign` (`design_post_id`),
  ADD KEY `design_customizations_order_id_foreign` (`order_id`),
  ADD KEY `design_customizations_user_id_foreign` (`user_id`),
  ADD KEY `design_customizations_approved_proof_id_foreign` (`approved_proof_id`);

--
-- Indexes for table `design_customization_snapshots`
--
ALTER TABLE `design_customization_snapshots`
  ADD PRIMARY KEY (`id`),
  ADD KEY `design_customization_snapshots_captured_by_foreign` (`captured_by`),
  ADD KEY `idx_design_customization_snapshots_version` (`design_customization_id`,`version_no`);

--
-- Indexes for table `design_digitizing_jobs`
--
ALTER TABLE `design_digitizing_jobs`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `design_machine_files`
--
ALTER TABLE `design_machine_files`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `design_posts`
--
ALTER TABLE `design_posts`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_design_posts_client` (`client_user_id`),
  ADD KEY `idx_design_posts_shop` (`selected_shop_id`),
  ADD KEY `idx_design_posts_location` (`cavite_location_id`),
  ADD KEY `idx_design_posts_status` (`status`),
  ADD KEY `fk_design_posts_converted_order` (`converted_order_id`);

--
-- Indexes for table `design_production_packages`
--
ALTER TABLE `design_production_packages`
  ADD PRIMARY KEY (`id`),
  ADD KEY `design_production_packages_design_customization_id_foreign` (`design_customization_id`),
  ADD KEY `design_production_packages_created_by_foreign` (`created_by`);

--
-- Indexes for table `design_proofs`
--
ALTER TABLE `design_proofs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `design_proofs_design_customization_id_foreign` (`design_customization_id`),
  ADD KEY `design_proofs_generated_by_foreign` (`generated_by`),
  ADD KEY `design_proofs_responded_by_foreign` (`responded_by`);

--
-- Indexes for table `design_workflow_events`
--
ALTER TABLE `design_workflow_events`
  ADD PRIMARY KEY (`id`),
  ADD KEY `design_workflow_events_actor_user_id_foreign` (`actor_user_id`),
  ADD KEY `idx_design_workflow_events_lookup` (`design_customization_id`,`event_type`);

--
-- Indexes for table `dispute_cases`
--
ALTER TABLE `dispute_cases`
  ADD PRIMARY KEY (`id`),
  ADD KEY `dispute_cases_shop_id_foreign` (`shop_id`),
  ADD KEY `dispute_cases_order_id_foreign` (`order_id`),
  ADD KEY `dispute_cases_complainant_user_id_foreign` (`complainant_user_id`),
  ADD KEY `dispute_cases_assigned_handler_user_id_foreign` (`assigned_handler_user_id`);

--
-- Indexes for table `dss_recommendations`
--
ALTER TABLE `dss_recommendations`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_dss_recommendations_client` (`client_user_id`),
  ADD KEY `idx_dss_recommendations_shop` (`shop_id`),
  ADD KEY `idx_dss_recommendations_type` (`generated_for_type`),
  ADD KEY `idx_dss_recommendations_score` (`score`);

--
-- Indexes for table `dss_shop_metrics`
--
ALTER TABLE `dss_shop_metrics`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_dss_shop_metrics_shop_date` (`shop_id`,`metric_date`),
  ADD KEY `idx_dss_shop_metrics_date` (`metric_date`),
  ADD KEY `idx_dss_shop_metrics_score` (`recommendation_score`);

--
-- Indexes for table `embroidery_design_sessions`
--
ALTER TABLE `embroidery_design_sessions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `embroidery_design_sessions_user_id_status_index` (`user_id`,`status`),
  ADD KEY `embroidery_design_sessions_shop_id_status_index` (`shop_id`,`status`),
  ADD KEY `embroidery_design_sessions_order_id_index` (`order_id`);

--
-- Indexes for table `embroidery_design_versions`
--
ALTER TABLE `embroidery_design_versions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `embroidery_design_versions_session_id_version_no_unique` (`session_id`,`version_no`),
  ADD KEY `embroidery_design_versions_created_by_foreign` (`created_by`);

--
-- Indexes for table `employment_applications`
--
ALTER TABLE `employment_applications`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_employment_applications_job_user` (`job_opening_id`,`applicant_user_id`),
  ADD KEY `idx_employment_applications_applicant` (`applicant_user_id`),
  ADD KEY `idx_employment_applications_reviewed_by` (`reviewed_by`),
  ADD KEY `idx_employment_applications_status` (`status`);

--
-- Indexes for table `failed_jobs`
--
ALTER TABLE `failed_jobs`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `failed_jobs_uuid_unique` (`uuid`);

--
-- Indexes for table `fulfillments`
--
ALTER TABLE `fulfillments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_fulfillments_order` (`order_id`),
  ADD KEY `idx_fulfillments_type` (`fulfillment_type`),
  ADD KEY `idx_fulfillments_status` (`status`),
  ADD KEY `idx_fulfillments_location` (`cavite_location_id`);

--
-- Indexes for table `jobs`
--
ALTER TABLE `jobs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `jobs_queue_reserved_at_available_at_index` (`queue`,`reserved_at`,`available_at`);

--
-- Indexes for table `job_batches`
--
ALTER TABLE `job_batches`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `job_openings`
--
ALTER TABLE `job_openings`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_job_openings_shop` (`shop_id`),
  ADD KEY `idx_job_openings_created_by` (`created_by`),
  ADD KEY `idx_job_openings_status` (`status`),
  ADD KEY `idx_job_openings_location` (`cavite_location_id`);

--
-- Indexes for table `job_post_applications`
--
ALTER TABLE `job_post_applications`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_job_post_applications_post_shop` (`design_post_id`,`shop_id`),
  ADD KEY `idx_job_post_applications_shop` (`shop_id`),
  ADD KEY `idx_job_post_applications_owner` (`owner_user_id`),
  ADD KEY `idx_job_post_applications_status` (`status`);

--
-- Indexes for table `messages`
--
ALTER TABLE `messages`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_messages_from` (`from_user_id`),
  ADD KEY `idx_messages_to` (`to_user_id`),
  ADD KEY `idx_messages_order` (`order_id`),
  ADD KEY `idx_messages_design_post` (`design_post_id`),
  ADD KEY `messages_thread_id_foreign` (`thread_id`),
  ADD KEY `messages_parent_message_id_foreign` (`parent_message_id`);

--
-- Indexes for table `message_attachments`
--
ALTER TABLE `message_attachments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `message_attachments_message_id_foreign` (`message_id`);

--
-- Indexes for table `message_threads`
--
ALTER TABLE `message_threads`
  ADD PRIMARY KEY (`id`),
  ADD KEY `message_threads_order_id_foreign` (`order_id`),
  ADD KEY `message_threads_design_post_id_foreign` (`design_post_id`),
  ADD KEY `message_threads_shop_id_foreign` (`shop_id`),
  ADD KEY `message_threads_created_by_foreign` (`created_by`);

--
-- Indexes for table `message_thread_participants`
--
ALTER TABLE `message_thread_participants`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `message_thread_participants_thread_id_user_id_unique` (`thread_id`,`user_id`),
  ADD KEY `message_thread_participants_user_id_foreign` (`user_id`);

--
-- Indexes for table `migrations`
--
ALTER TABLE `migrations`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `mobile_device_tokens`
--
ALTER TABLE `mobile_device_tokens`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_mobile_device_tokens_token` (`device_token`),
  ADD KEY `idx_mobile_device_tokens_user` (`user_id`),
  ADD KEY `idx_mobile_device_tokens_platform` (`platform`);

--
-- Indexes for table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_notifications_user` (`user_id`),
  ADD KEY `idx_notifications_type` (`type`),
  ADD KEY `idx_notifications_reference` (`reference_type`,`reference_id`),
  ADD KEY `idx_notifications_channel` (`channel`);

--
-- Indexes for table `operational_alerts`
--
ALTER TABLE `operational_alerts`
  ADD PRIMARY KEY (`id`),
  ADD KEY `operational_alerts_order_id_foreign` (`order_id`),
  ADD KEY `operational_alerts_user_id_foreign` (`user_id`),
  ADD KEY `idx_operational_alerts_shop_status` (`shop_id`,`status`),
  ADD KEY `idx_operational_alerts_reference` (`reference_type`,`reference_id`);

--
-- Indexes for table `orders`
--
ALTER TABLE `orders`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_orders_order_number` (`order_number`),
  ADD KEY `idx_orders_client` (`client_user_id`),
  ADD KEY `idx_orders_shop` (`shop_id`),
  ADD KEY `idx_orders_post` (`source_design_post_id`),
  ADD KEY `idx_orders_service` (`service_id`),
  ADD KEY `idx_orders_status` (`status`),
  ADD KEY `idx_orders_payment_status` (`payment_status`),
  ADD KEY `idx_orders_current_stage` (`current_stage`),
  ADD KEY `fk_orders_latest_quote` (`latest_quote_id`),
  ADD KEY `fk_orders_approved_quote` (`approved_quote_id`);

--
-- Indexes for table `order_assignments`
--
ALTER TABLE `order_assignments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_order_assignments_order` (`order_id`),
  ADD KEY `idx_order_assignments_assigned_to` (`assigned_to`),
  ADD KEY `idx_order_assignments_assigned_by` (`assigned_by`);

--
-- Indexes for table `order_exceptions`
--
ALTER TABLE `order_exceptions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `order_exceptions_order_id_foreign` (`order_id`),
  ADD KEY `order_exceptions_assigned_handler_id_foreign` (`assigned_handler_id`);

--
-- Indexes for table `order_files`
--
ALTER TABLE `order_files`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_order_files_order` (`order_id`),
  ADD KEY `idx_order_files_order_item` (`order_item_id`),
  ADD KEY `idx_order_files_uploaded_by` (`uploaded_by`);

--
-- Indexes for table `order_items`
--
ALTER TABLE `order_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_order_items_order` (`order_id`);

--
-- Indexes for table `order_progress_logs`
--
ALTER TABLE `order_progress_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_order_progress_logs_order` (`order_id`),
  ADD KEY `idx_order_progress_logs_status` (`status`),
  ADD KEY `idx_order_progress_logs_actor` (`actor_user_id`);

--
-- Indexes for table `order_quotes`
--
ALTER TABLE `order_quotes`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_order_quotes_quote_number` (`quote_number`),
  ADD KEY `idx_order_quotes_order` (`order_id`),
  ADD KEY `idx_order_quotes_shop` (`shop_id`),
  ADD KEY `idx_order_quotes_quoted_by` (`quoted_by`),
  ADD KEY `idx_order_quotes_status` (`status`);

--
-- Indexes for table `order_quote_items`
--
ALTER TABLE `order_quote_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_order_quote_items_quote` (`order_quote_id`),
  ADD KEY `idx_order_quote_items_order_item` (`order_item_id`);

--
-- Indexes for table `order_revisions`
--
ALTER TABLE `order_revisions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_order_revisions_order` (`order_id`),
  ADD KEY `idx_order_revisions_item` (`order_item_id`),
  ADD KEY `idx_order_revisions_requested_by` (`requested_by`),
  ADD KEY `idx_order_revisions_handled_by` (`handled_by`),
  ADD KEY `idx_order_revisions_status` (`status`);

--
-- Indexes for table `order_stage_history`
--
ALTER TABLE `order_stage_history`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_order_stage_history_order` (`order_id`),
  ADD KEY `idx_order_stage_history_stage` (`stage_code`),
  ADD KEY `idx_order_stage_history_status` (`stage_status`),
  ADD KEY `idx_order_stage_history_actor` (`actor_user_id`);

--
-- Indexes for table `owner_pricing_rules`
--
ALTER TABLE `owner_pricing_rules`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `owner_pricing_rules_shop_type_key_unique` (`shop_id`,`rule_type`,`rule_key`);

--
-- Indexes for table `owner_settings`
--
ALTER TABLE `owner_settings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `owner_settings_shop_id_unique` (`shop_id`);

--
-- Indexes for table `password_reset_tokens`
--
ALTER TABLE `password_reset_tokens`
  ADD PRIMARY KEY (`email`);

--
-- Indexes for table `payments`
--
ALTER TABLE `payments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_payments_order` (`order_id`),
  ADD KEY `idx_payments_client` (`client_user_id`),
  ADD KEY `idx_payments_shop` (`shop_id`),
  ADD KEY `idx_payments_method` (`payment_method_id`),
  ADD KEY `idx_payments_status` (`payment_status`),
  ADD KEY `fk_payments_confirmed_by` (`confirmed_by`);

--
-- Indexes for table `payment_methods`
--
ALTER TABLE `payment_methods`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_payment_methods_shop` (`shop_id`),
  ADD KEY `idx_payment_methods_code` (`method_code`),
  ADD KEY `idx_payment_methods_active` (`is_active`);

--
-- Indexes for table `personal_access_tokens`
--
ALTER TABLE `personal_access_tokens`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `personal_access_tokens_token_unique` (`token`),
  ADD KEY `personal_access_tokens_tokenable_type_tokenable_id_index` (`tokenable_type`,`tokenable_id`),
  ADD KEY `personal_access_tokens_expires_at_index` (`expires_at`);

--
-- Indexes for table `price_suggestion_rules`
--
ALTER TABLE `price_suggestion_rules`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `price_suggestion_rules_rule_code_unique` (`rule_code`);

--
-- Indexes for table `pricing_rules`
--
ALTER TABLE `pricing_rules`
  ADD PRIMARY KEY (`id`),
  ADD KEY `pricing_rules_shop_id_foreign` (`shop_id`);

--
-- Indexes for table `quality_checks`
--
ALTER TABLE `quality_checks`
  ADD PRIMARY KEY (`id`),
  ADD KEY `quality_checks_shop_id_foreign` (`shop_id`),
  ADD KEY `quality_checks_order_id_foreign` (`order_id`),
  ADD KEY `quality_checks_checked_by_foreign` (`checked_by`);

--
-- Indexes for table `raw_materials`
--
ALTER TABLE `raw_materials`
  ADD PRIMARY KEY (`id`),
  ADD KEY `raw_materials_shop_id_foreign` (`shop_id`),
  ADD KEY `raw_materials_supplier_id_foreign` (`supplier_id`);

--
-- Indexes for table `reviews`
--
ALTER TABLE `reviews`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_reviews_order_client` (`order_id`,`client_user_id`),
  ADD KEY `idx_reviews_shop` (`shop_id`),
  ADD KEY `idx_reviews_client` (`client_user_id`),
  ADD KEY `idx_reviews_rating` (`rating`);

--
-- Indexes for table `sessions`
--
ALTER TABLE `sessions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `sessions_user_id_index` (`user_id`),
  ADD KEY `sessions_last_activity_index` (`last_activity`);

--
-- Indexes for table `shops`
--
ALTER TABLE `shops`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_shops_slug` (`slug`),
  ADD KEY `idx_shops_owner` (`owner_user_id`),
  ADD KEY `idx_shops_location` (`cavite_location_id`),
  ADD KEY `idx_shops_verification` (`verification_status`),
  ADD KEY `idx_shops_active` (`is_active`),
  ADD KEY `fk_shops_approved_by` (`approved_by`);

--
-- Indexes for table `shop_couriers`
--
ALTER TABLE `shop_couriers`
  ADD PRIMARY KEY (`id`),
  ADD KEY `shop_couriers_shop_id_foreign` (`shop_id`);

--
-- Indexes for table `shop_hiring_openings`
--
ALTER TABLE `shop_hiring_openings`
  ADD PRIMARY KEY (`id`),
  ADD KEY `shop_hiring_openings_shop_id_foreign` (`shop_id`),
  ADD KEY `shop_hiring_openings_posted_by_foreign` (`posted_by`);

--
-- Indexes for table `shop_members`
--
ALTER TABLE `shop_members`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_shop_members_shop_user` (`shop_id`,`user_id`),
  ADD KEY `idx_shop_members_user` (`user_id`),
  ADD KEY `idx_shop_members_role` (`member_role`),
  ADD KEY `idx_shop_members_status` (`employment_status`),
  ADD KEY `shop_members_hired_by_user_id_foreign` (`hired_by_user_id`),
  ADD KEY `shop_members_reviewed_by_user_id_foreign` (`reviewed_by_user_id`),
  ADD KEY `shop_members_created_by_user_id_foreign` (`created_by_user_id`);

--
-- Indexes for table `shop_portfolio`
--
ALTER TABLE `shop_portfolio`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_shop_portfolio_shop` (`shop_id`),
  ADD KEY `idx_shop_portfolio_uploaded_by` (`uploaded_by`),
  ADD KEY `idx_shop_portfolio_category` (`category`),
  ADD KEY `idx_shop_portfolio_public` (`is_public`);

--
-- Indexes for table `shop_projects`
--
ALTER TABLE `shop_projects`
  ADD PRIMARY KEY (`id`),
  ADD KEY `shop_projects_shop_id_foreign` (`shop_id`),
  ADD KEY `shop_projects_created_by_foreign` (`created_by`);

--
-- Indexes for table `shop_services`
--
ALTER TABLE `shop_services`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_shop_services_shop` (`shop_id`),
  ADD KEY `idx_shop_services_category` (`category`),
  ADD KEY `idx_shop_services_active` (`is_active`);

--
-- Indexes for table `shop_service_areas`
--
ALTER TABLE `shop_service_areas`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_shop_service_area` (`shop_id`,`cavite_location_id`),
  ADD KEY `idx_shop_service_areas_location` (`cavite_location_id`);

--
-- Indexes for table `staff_profiles`
--
ALTER TABLE `staff_profiles`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_staff_profiles_user` (`user_id`),
  ADD UNIQUE KEY `uq_staff_profiles_employee_code` (`employee_code`);

--
-- Indexes for table `suppliers`
--
ALTER TABLE `suppliers`
  ADD PRIMARY KEY (`id`),
  ADD KEY `suppliers_shop_id_foreign` (`shop_id`);

--
-- Indexes for table `supply_orders`
--
ALTER TABLE `supply_orders`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `supply_orders_po_number_unique` (`po_number`),
  ADD KEY `supply_orders_shop_id_foreign` (`shop_id`),
  ADD KEY `supply_orders_supplier_id_foreign` (`supplier_id`),
  ADD KEY `supply_orders_approved_by_foreign` (`approved_by`);

--
-- Indexes for table `support_tickets`
--
ALTER TABLE `support_tickets`
  ADD PRIMARY KEY (`id`),
  ADD KEY `support_tickets_user_id_foreign` (`user_id`),
  ADD KEY `support_tickets_shop_id_foreign` (`shop_id`),
  ADD KEY `support_tickets_order_id_foreign` (`order_id`),
  ADD KEY `support_tickets_assigned_to_foreign` (`assigned_to`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `users_email_unique` (`email`),
  ADD KEY `idx_users_role` (`role`),
  ADD KEY `idx_users_shop` (`shop_id`),
  ADD KEY `idx_users_is_active` (`is_active`);

--
-- Indexes for table `workforce_schedules`
--
ALTER TABLE `workforce_schedules`
  ADD PRIMARY KEY (`id`),
  ADD KEY `workforce_schedules_shop_id_foreign` (`shop_id`),
  ADD KEY `workforce_schedules_user_id_foreign` (`user_id`),
  ADD KEY `workforce_schedules_order_id_foreign` (`order_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `audit_logs`
--
ALTER TABLE `audit_logs`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `bargaining_offers`
--
ALTER TABLE `bargaining_offers`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `cavite_locations`
--
ALTER TABLE `cavite_locations`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=24;

--
-- AUTO_INCREMENT for table `client_payment_methods`
--
ALTER TABLE `client_payment_methods`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `client_profiles`
--
ALTER TABLE `client_profiles`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `client_saved_addresses`
--
ALTER TABLE `client_saved_addresses`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `design_customizations`
--
ALTER TABLE `design_customizations`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `design_customization_snapshots`
--
ALTER TABLE `design_customization_snapshots`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `design_digitizing_jobs`
--
ALTER TABLE `design_digitizing_jobs`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `design_machine_files`
--
ALTER TABLE `design_machine_files`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `design_posts`
--
ALTER TABLE `design_posts`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `design_production_packages`
--
ALTER TABLE `design_production_packages`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `design_proofs`
--
ALTER TABLE `design_proofs`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `design_workflow_events`
--
ALTER TABLE `design_workflow_events`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `dispute_cases`
--
ALTER TABLE `dispute_cases`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `dss_recommendations`
--
ALTER TABLE `dss_recommendations`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `dss_shop_metrics`
--
ALTER TABLE `dss_shop_metrics`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `embroidery_design_sessions`
--
ALTER TABLE `embroidery_design_sessions`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `embroidery_design_versions`
--
ALTER TABLE `embroidery_design_versions`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `employment_applications`
--
ALTER TABLE `employment_applications`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `failed_jobs`
--
ALTER TABLE `failed_jobs`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `fulfillments`
--
ALTER TABLE `fulfillments`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `jobs`
--
ALTER TABLE `jobs`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `job_openings`
--
ALTER TABLE `job_openings`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `job_post_applications`
--
ALTER TABLE `job_post_applications`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `messages`
--
ALTER TABLE `messages`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `message_attachments`
--
ALTER TABLE `message_attachments`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `message_threads`
--
ALTER TABLE `message_threads`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `message_thread_participants`
--
ALTER TABLE `message_thread_participants`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `migrations`
--
ALTER TABLE `migrations`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=39;

--
-- AUTO_INCREMENT for table `mobile_device_tokens`
--
ALTER TABLE `mobile_device_tokens`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=43;

--
-- AUTO_INCREMENT for table `operational_alerts`
--
ALTER TABLE `operational_alerts`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `orders`
--
ALTER TABLE `orders`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `order_assignments`
--
ALTER TABLE `order_assignments`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `order_exceptions`
--
ALTER TABLE `order_exceptions`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `order_files`
--
ALTER TABLE `order_files`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `order_items`
--
ALTER TABLE `order_items`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `order_progress_logs`
--
ALTER TABLE `order_progress_logs`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=47;

--
-- AUTO_INCREMENT for table `order_quotes`
--
ALTER TABLE `order_quotes`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `order_quote_items`
--
ALTER TABLE `order_quote_items`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `order_revisions`
--
ALTER TABLE `order_revisions`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `order_stage_history`
--
ALTER TABLE `order_stage_history`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT for table `owner_pricing_rules`
--
ALTER TABLE `owner_pricing_rules`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `owner_settings`
--
ALTER TABLE `owner_settings`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `payments`
--
ALTER TABLE `payments`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `payment_methods`
--
ALTER TABLE `payment_methods`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `personal_access_tokens`
--
ALTER TABLE `personal_access_tokens`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=131;

--
-- AUTO_INCREMENT for table `price_suggestion_rules`
--
ALTER TABLE `price_suggestion_rules`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `pricing_rules`
--
ALTER TABLE `pricing_rules`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `quality_checks`
--
ALTER TABLE `quality_checks`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `raw_materials`
--
ALTER TABLE `raw_materials`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `reviews`
--
ALTER TABLE `reviews`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `shops`
--
ALTER TABLE `shops`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `shop_couriers`
--
ALTER TABLE `shop_couriers`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `shop_hiring_openings`
--
ALTER TABLE `shop_hiring_openings`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `shop_members`
--
ALTER TABLE `shop_members`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `shop_portfolio`
--
ALTER TABLE `shop_portfolio`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `shop_projects`
--
ALTER TABLE `shop_projects`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `shop_services`
--
ALTER TABLE `shop_services`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `shop_service_areas`
--
ALTER TABLE `shop_service_areas`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `staff_profiles`
--
ALTER TABLE `staff_profiles`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `suppliers`
--
ALTER TABLE `suppliers`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `supply_orders`
--
ALTER TABLE `supply_orders`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `support_tickets`
--
ALTER TABLE `support_tickets`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `workforce_schedules`
--
ALTER TABLE `workforce_schedules`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `audit_logs`
--
ALTER TABLE `audit_logs`
  ADD CONSTRAINT `fk_audit_logs_actor` FOREIGN KEY (`actor_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_audit_logs_shop` FOREIGN KEY (`shop_id`) REFERENCES `shops` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `bargaining_offers`
--
ALTER TABLE `bargaining_offers`
  ADD CONSTRAINT `bargaining_offers_design_post_id_foreign` FOREIGN KEY (`design_post_id`) REFERENCES `design_posts` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `bargaining_offers_job_post_application_id_foreign` FOREIGN KEY (`job_post_application_id`) REFERENCES `job_post_applications` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `bargaining_offers_offered_by_user_id_foreign` FOREIGN KEY (`offered_by_user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `bargaining_offers_parent_offer_id_foreign` FOREIGN KEY (`parent_offer_id`) REFERENCES `bargaining_offers` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `bargaining_offers_responded_by_foreign` FOREIGN KEY (`responded_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `client_payment_methods`
--
ALTER TABLE `client_payment_methods`
  ADD CONSTRAINT `client_payment_methods_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `client_profiles`
--
ALTER TABLE `client_profiles`
  ADD CONSTRAINT `client_profiles_preferred_payment_method_id_foreign` FOREIGN KEY (`preferred_payment_method_id`) REFERENCES `client_payment_methods` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_client_profiles_location` FOREIGN KEY (`cavite_location_id`) REFERENCES `cavite_locations` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_client_profiles_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `client_saved_addresses`
--
ALTER TABLE `client_saved_addresses`
  ADD CONSTRAINT `client_saved_addresses_cavite_location_id_foreign` FOREIGN KEY (`cavite_location_id`) REFERENCES `cavite_locations` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `client_saved_addresses_client_profile_id_foreign` FOREIGN KEY (`client_profile_id`) REFERENCES `client_profiles` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `design_customizations`
--
ALTER TABLE `design_customizations`
  ADD CONSTRAINT `design_customizations_approved_proof_id_foreign` FOREIGN KEY (`approved_proof_id`) REFERENCES `design_proofs` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `design_customizations_design_post_id_foreign` FOREIGN KEY (`design_post_id`) REFERENCES `design_posts` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `design_customizations_order_id_foreign` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `design_customizations_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `design_customization_snapshots`
--
ALTER TABLE `design_customization_snapshots`
  ADD CONSTRAINT `design_customization_snapshots_captured_by_foreign` FOREIGN KEY (`captured_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `design_customization_snapshots_design_customization_id_foreign` FOREIGN KEY (`design_customization_id`) REFERENCES `design_customizations` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `design_posts`
--
ALTER TABLE `design_posts`
  ADD CONSTRAINT `fk_design_posts_client` FOREIGN KEY (`client_user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_design_posts_converted_order` FOREIGN KEY (`converted_order_id`) REFERENCES `orders` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_design_posts_location` FOREIGN KEY (`cavite_location_id`) REFERENCES `cavite_locations` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_design_posts_selected_shop` FOREIGN KEY (`selected_shop_id`) REFERENCES `shops` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `design_production_packages`
--
ALTER TABLE `design_production_packages`
  ADD CONSTRAINT `design_production_packages_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `design_production_packages_design_customization_id_foreign` FOREIGN KEY (`design_customization_id`) REFERENCES `design_customizations` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `design_proofs`
--
ALTER TABLE `design_proofs`
  ADD CONSTRAINT `design_proofs_design_customization_id_foreign` FOREIGN KEY (`design_customization_id`) REFERENCES `design_customizations` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `design_proofs_generated_by_foreign` FOREIGN KEY (`generated_by`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `design_proofs_responded_by_foreign` FOREIGN KEY (`responded_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `design_workflow_events`
--
ALTER TABLE `design_workflow_events`
  ADD CONSTRAINT `design_workflow_events_actor_user_id_foreign` FOREIGN KEY (`actor_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `design_workflow_events_design_customization_id_foreign` FOREIGN KEY (`design_customization_id`) REFERENCES `design_customizations` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `dispute_cases`
--
ALTER TABLE `dispute_cases`
  ADD CONSTRAINT `dispute_cases_assigned_handler_user_id_foreign` FOREIGN KEY (`assigned_handler_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `dispute_cases_complainant_user_id_foreign` FOREIGN KEY (`complainant_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `dispute_cases_order_id_foreign` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `dispute_cases_shop_id_foreign` FOREIGN KEY (`shop_id`) REFERENCES `shops` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `dss_recommendations`
--
ALTER TABLE `dss_recommendations`
  ADD CONSTRAINT `fk_dss_recommendations_client` FOREIGN KEY (`client_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_dss_recommendations_shop` FOREIGN KEY (`shop_id`) REFERENCES `shops` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `dss_shop_metrics`
--
ALTER TABLE `dss_shop_metrics`
  ADD CONSTRAINT `fk_dss_shop_metrics_shop` FOREIGN KEY (`shop_id`) REFERENCES `shops` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `embroidery_design_sessions`
--
ALTER TABLE `embroidery_design_sessions`
  ADD CONSTRAINT `embroidery_design_sessions_order_id_foreign` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `embroidery_design_sessions_shop_id_foreign` FOREIGN KEY (`shop_id`) REFERENCES `shops` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `embroidery_design_sessions_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `embroidery_design_versions`
--
ALTER TABLE `embroidery_design_versions`
  ADD CONSTRAINT `embroidery_design_versions_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `embroidery_design_versions_session_id_foreign` FOREIGN KEY (`session_id`) REFERENCES `embroidery_design_sessions` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `employment_applications`
--
ALTER TABLE `employment_applications`
  ADD CONSTRAINT `fk_employment_applications_applicant` FOREIGN KEY (`applicant_user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_employment_applications_job` FOREIGN KEY (`job_opening_id`) REFERENCES `job_openings` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_employment_applications_reviewed_by` FOREIGN KEY (`reviewed_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `fulfillments`
--
ALTER TABLE `fulfillments`
  ADD CONSTRAINT `fk_fulfillments_location` FOREIGN KEY (`cavite_location_id`) REFERENCES `cavite_locations` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_fulfillments_order` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `job_openings`
--
ALTER TABLE `job_openings`
  ADD CONSTRAINT `fk_job_openings_created_by` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_job_openings_location` FOREIGN KEY (`cavite_location_id`) REFERENCES `cavite_locations` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_job_openings_shop` FOREIGN KEY (`shop_id`) REFERENCES `shops` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `job_post_applications`
--
ALTER TABLE `job_post_applications`
  ADD CONSTRAINT `fk_job_post_applications_owner` FOREIGN KEY (`owner_user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_job_post_applications_post` FOREIGN KEY (`design_post_id`) REFERENCES `design_posts` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_job_post_applications_shop` FOREIGN KEY (`shop_id`) REFERENCES `shops` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `messages`
--
ALTER TABLE `messages`
  ADD CONSTRAINT `fk_messages_design_post` FOREIGN KEY (`design_post_id`) REFERENCES `design_posts` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_messages_from_user` FOREIGN KEY (`from_user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_messages_order` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_messages_to_user` FOREIGN KEY (`to_user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `messages_parent_message_id_foreign` FOREIGN KEY (`parent_message_id`) REFERENCES `messages` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `messages_thread_id_foreign` FOREIGN KEY (`thread_id`) REFERENCES `message_threads` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `message_attachments`
--
ALTER TABLE `message_attachments`
  ADD CONSTRAINT `message_attachments_message_id_foreign` FOREIGN KEY (`message_id`) REFERENCES `messages` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `message_threads`
--
ALTER TABLE `message_threads`
  ADD CONSTRAINT `message_threads_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `message_threads_design_post_id_foreign` FOREIGN KEY (`design_post_id`) REFERENCES `design_posts` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `message_threads_order_id_foreign` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `message_threads_shop_id_foreign` FOREIGN KEY (`shop_id`) REFERENCES `shops` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `message_thread_participants`
--
ALTER TABLE `message_thread_participants`
  ADD CONSTRAINT `message_thread_participants_thread_id_foreign` FOREIGN KEY (`thread_id`) REFERENCES `message_threads` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `message_thread_participants_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `mobile_device_tokens`
--
ALTER TABLE `mobile_device_tokens`
  ADD CONSTRAINT `fk_mobile_device_tokens_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `notifications`
--
ALTER TABLE `notifications`
  ADD CONSTRAINT `fk_notifications_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `operational_alerts`
--
ALTER TABLE `operational_alerts`
  ADD CONSTRAINT `operational_alerts_order_id_foreign` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `operational_alerts_shop_id_foreign` FOREIGN KEY (`shop_id`) REFERENCES `shops` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `operational_alerts_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `orders`
--
ALTER TABLE `orders`
  ADD CONSTRAINT `fk_orders_approved_quote` FOREIGN KEY (`approved_quote_id`) REFERENCES `order_quotes` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_orders_client` FOREIGN KEY (`client_user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_orders_design_post` FOREIGN KEY (`source_design_post_id`) REFERENCES `design_posts` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_orders_latest_quote` FOREIGN KEY (`latest_quote_id`) REFERENCES `order_quotes` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_orders_service` FOREIGN KEY (`service_id`) REFERENCES `shop_services` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_orders_shop` FOREIGN KEY (`shop_id`) REFERENCES `shops` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `order_assignments`
--
ALTER TABLE `order_assignments`
  ADD CONSTRAINT `fk_order_assignments_assigned_by` FOREIGN KEY (`assigned_by`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_order_assignments_assigned_to` FOREIGN KEY (`assigned_to`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_order_assignments_order` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `order_exceptions`
--
ALTER TABLE `order_exceptions`
  ADD CONSTRAINT `order_exceptions_assigned_handler_id_foreign` FOREIGN KEY (`assigned_handler_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `order_exceptions_order_id_foreign` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `order_files`
--
ALTER TABLE `order_files`
  ADD CONSTRAINT `fk_order_files_order` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_order_files_order_item` FOREIGN KEY (`order_item_id`) REFERENCES `order_items` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_order_files_uploaded_by` FOREIGN KEY (`uploaded_by`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `order_items`
--
ALTER TABLE `order_items`
  ADD CONSTRAINT `fk_order_items_order` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `order_progress_logs`
--
ALTER TABLE `order_progress_logs`
  ADD CONSTRAINT `fk_order_progress_logs_actor` FOREIGN KEY (`actor_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_order_progress_logs_order` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `order_quotes`
--
ALTER TABLE `order_quotes`
  ADD CONSTRAINT `fk_order_quotes_order` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_order_quotes_quoted_by` FOREIGN KEY (`quoted_by`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_order_quotes_shop` FOREIGN KEY (`shop_id`) REFERENCES `shops` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `order_quote_items`
--
ALTER TABLE `order_quote_items`
  ADD CONSTRAINT `fk_order_quote_items_order_item` FOREIGN KEY (`order_item_id`) REFERENCES `order_items` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_order_quote_items_quote` FOREIGN KEY (`order_quote_id`) REFERENCES `order_quotes` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `order_revisions`
--
ALTER TABLE `order_revisions`
  ADD CONSTRAINT `fk_order_revisions_handled_by` FOREIGN KEY (`handled_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_order_revisions_item` FOREIGN KEY (`order_item_id`) REFERENCES `order_items` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_order_revisions_order` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_order_revisions_requested_by` FOREIGN KEY (`requested_by`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `order_stage_history`
--
ALTER TABLE `order_stage_history`
  ADD CONSTRAINT `fk_order_stage_history_actor` FOREIGN KEY (`actor_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_order_stage_history_order` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `owner_pricing_rules`
--
ALTER TABLE `owner_pricing_rules`
  ADD CONSTRAINT `owner_pricing_rules_shop_id_foreign` FOREIGN KEY (`shop_id`) REFERENCES `shops` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `owner_settings`
--
ALTER TABLE `owner_settings`
  ADD CONSTRAINT `owner_settings_shop_id_foreign` FOREIGN KEY (`shop_id`) REFERENCES `shops` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `payments`
--
ALTER TABLE `payments`
  ADD CONSTRAINT `fk_payments_client` FOREIGN KEY (`client_user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_payments_confirmed_by` FOREIGN KEY (`confirmed_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_payments_method` FOREIGN KEY (`payment_method_id`) REFERENCES `payment_methods` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_payments_order` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_payments_shop` FOREIGN KEY (`shop_id`) REFERENCES `shops` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `payment_methods`
--
ALTER TABLE `payment_methods`
  ADD CONSTRAINT `fk_payment_methods_shop` FOREIGN KEY (`shop_id`) REFERENCES `shops` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `pricing_rules`
--
ALTER TABLE `pricing_rules`
  ADD CONSTRAINT `pricing_rules_shop_id_foreign` FOREIGN KEY (`shop_id`) REFERENCES `shops` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `quality_checks`
--
ALTER TABLE `quality_checks`
  ADD CONSTRAINT `quality_checks_checked_by_foreign` FOREIGN KEY (`checked_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `quality_checks_order_id_foreign` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `quality_checks_shop_id_foreign` FOREIGN KEY (`shop_id`) REFERENCES `shops` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `raw_materials`
--
ALTER TABLE `raw_materials`
  ADD CONSTRAINT `raw_materials_shop_id_foreign` FOREIGN KEY (`shop_id`) REFERENCES `shops` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `raw_materials_supplier_id_foreign` FOREIGN KEY (`supplier_id`) REFERENCES `suppliers` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `reviews`
--
ALTER TABLE `reviews`
  ADD CONSTRAINT `fk_reviews_client` FOREIGN KEY (`client_user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_reviews_order` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_reviews_shop` FOREIGN KEY (`shop_id`) REFERENCES `shops` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `shops`
--
ALTER TABLE `shops`
  ADD CONSTRAINT `fk_shops_approved_by` FOREIGN KEY (`approved_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_shops_location` FOREIGN KEY (`cavite_location_id`) REFERENCES `cavite_locations` (`id`) ON DELETE NO ACTION,
  ADD CONSTRAINT `fk_shops_owner` FOREIGN KEY (`owner_user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `shop_couriers`
--
ALTER TABLE `shop_couriers`
  ADD CONSTRAINT `shop_couriers_shop_id_foreign` FOREIGN KEY (`shop_id`) REFERENCES `shops` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `shop_hiring_openings`
--
ALTER TABLE `shop_hiring_openings`
  ADD CONSTRAINT `shop_hiring_openings_posted_by_foreign` FOREIGN KEY (`posted_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `shop_hiring_openings_shop_id_foreign` FOREIGN KEY (`shop_id`) REFERENCES `shops` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `shop_members`
--
ALTER TABLE `shop_members`
  ADD CONSTRAINT `fk_shop_members_shop` FOREIGN KEY (`shop_id`) REFERENCES `shops` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_shop_members_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `shop_members_created_by_user_id_foreign` FOREIGN KEY (`created_by_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `shop_members_hired_by_user_id_foreign` FOREIGN KEY (`hired_by_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `shop_members_reviewed_by_user_id_foreign` FOREIGN KEY (`reviewed_by_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `shop_portfolio`
--
ALTER TABLE `shop_portfolio`
  ADD CONSTRAINT `fk_shop_portfolio_shop` FOREIGN KEY (`shop_id`) REFERENCES `shops` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_shop_portfolio_uploaded_by` FOREIGN KEY (`uploaded_by`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `shop_projects`
--
ALTER TABLE `shop_projects`
  ADD CONSTRAINT `shop_projects_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `shop_projects_shop_id_foreign` FOREIGN KEY (`shop_id`) REFERENCES `shops` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `shop_services`
--
ALTER TABLE `shop_services`
  ADD CONSTRAINT `fk_shop_services_shop` FOREIGN KEY (`shop_id`) REFERENCES `shops` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `shop_service_areas`
--
ALTER TABLE `shop_service_areas`
  ADD CONSTRAINT `fk_shop_service_areas_location` FOREIGN KEY (`cavite_location_id`) REFERENCES `cavite_locations` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_shop_service_areas_shop` FOREIGN KEY (`shop_id`) REFERENCES `shops` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `staff_profiles`
--
ALTER TABLE `staff_profiles`
  ADD CONSTRAINT `fk_staff_profiles_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `suppliers`
--
ALTER TABLE `suppliers`
  ADD CONSTRAINT `suppliers_shop_id_foreign` FOREIGN KEY (`shop_id`) REFERENCES `shops` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `supply_orders`
--
ALTER TABLE `supply_orders`
  ADD CONSTRAINT `supply_orders_approved_by_foreign` FOREIGN KEY (`approved_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `supply_orders_shop_id_foreign` FOREIGN KEY (`shop_id`) REFERENCES `shops` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `supply_orders_supplier_id_foreign` FOREIGN KEY (`supplier_id`) REFERENCES `suppliers` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `support_tickets`
--
ALTER TABLE `support_tickets`
  ADD CONSTRAINT `support_tickets_assigned_to_foreign` FOREIGN KEY (`assigned_to`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `support_tickets_order_id_foreign` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `support_tickets_shop_id_foreign` FOREIGN KEY (`shop_id`) REFERENCES `shops` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `support_tickets_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `users`
--
ALTER TABLE `users`
  ADD CONSTRAINT `fk_users_shop` FOREIGN KEY (`shop_id`) REFERENCES `shops` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `workforce_schedules`
--
ALTER TABLE `workforce_schedules`
  ADD CONSTRAINT `workforce_schedules_order_id_foreign` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `workforce_schedules_shop_id_foreign` FOREIGN KEY (`shop_id`) REFERENCES `shops` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `workforce_schedules_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
