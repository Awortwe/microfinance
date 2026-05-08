-- ============================================
-- MICROFINANCE DATABASE SCHEMA
-- Drops existing tables and recreates them
-- ============================================

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

-- ============================================
-- DROP ALL EXISTING TABLES FIRST
-- ============================================

DROP TABLE IF EXISTS `notifications`;
DROP TABLE IF EXISTS `audit_trail`;
DROP TABLE IF EXISTS `savings_loan_link`;
DROP TABLE IF EXISTS `loan_schedule`;
DROP TABLE IF EXISTS `loan_repayments`;
DROP TABLE IF EXISTS `loan_collateral`;
DROP TABLE IF EXISTS `loan_guarantors`;
DROP TABLE IF EXISTS `loans`;
DROP TABLE IF EXISTS `savings_transactions`;
DROP TABLE IF EXISTS `savings_accounts`;
DROP TABLE IF EXISTS `loan_products`;
DROP TABLE IF EXISTS `customers`;
DROP TABLE IF EXISTS `system_settings`;
DROP TABLE IF EXISTS `users`;

-- ============================================
-- TABLE: users
-- ============================================

CREATE TABLE `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `user_type` enum('admin','employee') NOT NULL DEFAULT 'employee',
  `is_active` tinyint(1) DEFAULT 1,
  `remember_token` varchar(64) DEFAULT NULL,
  `last_login` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- TABLE: customers
-- ============================================

CREATE TABLE `customers` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `customer_code` varchar(20) NOT NULL,
  `first_name` varchar(50) NOT NULL,
  `last_name` varchar(50) NOT NULL,
  `email` varchar(100) DEFAULT NULL,
  `phone` varchar(20) NOT NULL,
  `alternate_phone` varchar(20) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `city` varchar(50) DEFAULT NULL,
  `region` varchar(50) DEFAULT NULL,
  `id_type` enum('Ghana Card','Voter ID','Passport','Driver License','NHIS') DEFAULT 'Ghana Card',
  `id_number` varchar(50) DEFAULT NULL,
  `occupation` varchar(100) DEFAULT NULL,
  `business_name` varchar(100) DEFAULT NULL,
  `business_address` text DEFAULT NULL,
  `date_of_birth` date DEFAULT NULL,
  `gender` enum('Male','Female','Other') DEFAULT NULL,
  `marital_status` enum('Single','Married','Divorced','Widowed') DEFAULT NULL,
  `photo` varchar(255) DEFAULT NULL,
  `id_document` varchar(255) DEFAULT NULL,
  `status` enum('active','inactive','blacklisted') DEFAULT 'active',
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `customer_code` (`customer_code`),
  KEY `idx_customer_status` (`status`),
  KEY `idx_customer_phone` (`phone`),
  KEY `idx_customer_name` (`first_name`,`last_name`),
  KEY `idx_customer_created_by` (`created_by`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- TABLE: savings_accounts
-- ============================================

CREATE TABLE `savings_accounts` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `customer_id` int(11) NOT NULL,
  `account_number` varchar(20) NOT NULL,
  `account_type` enum('regular','susu','fixed_deposit') NOT NULL DEFAULT 'regular',
  `balance` decimal(15,2) DEFAULT 0.00,
  `interest_rate` decimal(5,2) DEFAULT 0.00,
  `susu_amount` decimal(10,2) DEFAULT 0.00,
  `susu_collection_day` enum('daily','weekly','monthly') DEFAULT 'daily',
  `status` enum('active','dormant','closed') DEFAULT 'active',
  `opened_date` date NOT NULL,
  `closed_date` date DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `account_number` (`account_number`),
  KEY `idx_savings_customer` (`customer_id`),
  KEY `idx_savings_status` (`status`),
  KEY `idx_savings_created_by` (`created_by`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- TABLE: savings_transactions
-- ============================================

CREATE TABLE `savings_transactions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `account_id` int(11) NOT NULL,
  `transaction_type` enum('deposit','withdrawal','interest','fee','susu_collection') NOT NULL,
  `amount` decimal(15,2) NOT NULL,
  `balance_before` decimal(15,2) NOT NULL,
  `balance_after` decimal(15,2) NOT NULL,
  `description` text DEFAULT NULL,
  `transaction_date` date NOT NULL,
  `transaction_time` time NOT NULL,
  `payment_method` enum('cash','transfer','mobile_money','cheque') DEFAULT 'cash',
  `reference_number` varchar(50) DEFAULT NULL,
  `processed_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_transaction_account` (`account_id`),
  KEY `idx_transaction_date` (`transaction_date`),
  KEY `idx_transaction_processed_by` (`processed_by`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- TABLE: loan_products
-- ============================================

CREATE TABLE `loan_products` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `product_name` varchar(100) NOT NULL,
  `product_code` varchar(20) NOT NULL,
  `description` text DEFAULT NULL,
  `min_amount` decimal(15,2) NOT NULL,
  `max_amount` decimal(15,2) NOT NULL,
  `interest_rate` decimal(5,2) NOT NULL,
  `interest_type` enum('flat','reducing_balance') DEFAULT 'reducing_balance',
  `duration_min_months` int(11) NOT NULL,
  `duration_max_months` int(11) NOT NULL,
  `processing_fee_percentage` decimal(5,2) DEFAULT 0.00,
  `processing_fee_fixed` decimal(10,2) DEFAULT 0.00,
  `late_fee_percentage` decimal(5,2) DEFAULT 0.00,
  `late_fee_fixed` decimal(10,2) DEFAULT 0.00,
  `collateral_required` tinyint(1) DEFAULT 0,
  `guarantor_required` tinyint(1) DEFAULT 1,
  `min_savings_balance` decimal(15,2) DEFAULT 0.00,
  `status` enum('active','inactive') DEFAULT 'active',
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `product_code` (`product_code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- TABLE: loans
-- ============================================

CREATE TABLE `loans` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `customer_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `loan_number` varchar(20) NOT NULL,
  `principal_amount` decimal(15,2) NOT NULL,
  `interest_rate` decimal(5,2) NOT NULL,
  `interest_type` enum('flat','reducing_balance') DEFAULT 'reducing_balance',
  `total_interest` decimal(15,2) NOT NULL,
  `processing_fee` decimal(10,2) DEFAULT 0.00,
  `total_amount` decimal(15,2) NOT NULL,
  `monthly_payment` decimal(15,2) NOT NULL,
  `duration_months` int(11) NOT NULL,
  `amount_paid` decimal(15,2) DEFAULT 0.00,
  `balance` decimal(15,2) NOT NULL,
  `application_date` date NOT NULL,
  `approval_date` date DEFAULT NULL,
  `disbursement_date` date DEFAULT NULL,
  `expected_end_date` date DEFAULT NULL,
  `actual_end_date` date DEFAULT NULL,
  `status` enum('pending','approved','disbursed','active','completed','defaulted','written_off','rejected') DEFAULT 'pending',
  `rejection_reason` text DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `approved_by` int(11) DEFAULT NULL,
  `disbursed_by` int(11) DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `loan_number` (`loan_number`),
  KEY `idx_loan_customer` (`customer_id`),
  KEY `idx_loan_status` (`status`),
  KEY `idx_loan_dates` (`disbursement_date`,`expected_end_date`),
  KEY `idx_loan_created_by` (`created_by`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- TABLE: loan_guarantors
-- ============================================

CREATE TABLE `loan_guarantors` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `loan_id` int(11) NOT NULL,
  `customer_id` int(11) NOT NULL,
  `relationship` varchar(50) DEFAULT NULL,
  `status` enum('pending','approved','rejected') DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `loan_id` (`loan_id`),
  KEY `customer_id` (`customer_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- TABLE: loan_collateral
-- ============================================

CREATE TABLE `loan_collateral` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `loan_id` int(11) NOT NULL,
  `collateral_type` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `estimated_value` decimal(15,2) DEFAULT NULL,
  `document_reference` varchar(100) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `loan_id` (`loan_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- TABLE: loan_repayments
-- ============================================

CREATE TABLE `loan_repayments` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `loan_id` int(11) NOT NULL,
  `amount` decimal(15,2) NOT NULL,
  `principal_paid` decimal(15,2) NOT NULL,
  `interest_paid` decimal(15,2) NOT NULL,
  `fee_paid` decimal(10,2) DEFAULT 0.00,
  `late_fee` decimal(10,2) DEFAULT 0.00,
  `balance_before` decimal(15,2) NOT NULL,
  `balance_after` decimal(15,2) NOT NULL,
  `payment_date` date NOT NULL,
  `payment_time` time NOT NULL,
  `payment_method` enum('cash','transfer','mobile_money','cheque') DEFAULT 'cash',
  `reference_number` varchar(50) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `processed_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_repayment_loan` (`loan_id`),
  KEY `idx_repayment_date` (`payment_date`),
  KEY `idx_repayment_processed_by` (`processed_by`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- TABLE: loan_schedule
-- ============================================

CREATE TABLE `loan_schedule` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `loan_id` int(11) NOT NULL,
  `installment_number` int(11) NOT NULL,
  `due_date` date NOT NULL,
  `principal_amount` decimal(15,2) NOT NULL,
  `interest_amount` decimal(15,2) NOT NULL,
  `total_amount` decimal(15,2) NOT NULL,
  `amount_paid` decimal(15,2) DEFAULT 0.00,
  `balance_after` decimal(15,2) NOT NULL,
  `status` enum('pending','partial','paid','overdue') DEFAULT 'pending',
  `paid_date` date DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_schedule_loan` (`loan_id`),
  KEY `idx_schedule_due_date` (`due_date`,`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- TABLE: savings_loan_link
-- ============================================

CREATE TABLE `savings_loan_link` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `loan_id` int(11) NOT NULL,
  `savings_account_id` int(11) NOT NULL,
  `lien_amount` decimal(15,2) NOT NULL,
  `status` enum('active','released') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `loan_id` (`loan_id`),
  KEY `savings_account_id` (`savings_account_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- TABLE: system_settings
-- ============================================

CREATE TABLE `system_settings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `setting_key` varchar(50) NOT NULL,
  `setting_value` text DEFAULT NULL,
  `setting_type` enum('text','number','boolean','json') DEFAULT 'text',
  `description` varchar(255) DEFAULT NULL,
  `updated_by` int(11) DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `setting_key` (`setting_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- TABLE: notifications
-- ============================================

CREATE TABLE `notifications` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `title` varchar(100) NOT NULL,
  `message` text NOT NULL,
  `type` enum('info','warning','success','danger') DEFAULT 'info',
  `is_read` tinyint(1) DEFAULT 0,
  `link` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_notification_user` (`user_id`,`is_read`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- TABLE: audit_trail
-- ============================================

CREATE TABLE `audit_trail` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) DEFAULT NULL,
  `action` varchar(255) NOT NULL,
  `module` varchar(50) NOT NULL,
  `record_id` int(11) DEFAULT NULL,
  `old_values` text DEFAULT NULL,
  `new_values` text DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_audit_user` (`user_id`),
  KEY `idx_audit_module` (`module`,`created_at`),
  KEY `idx_audit_created` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- INSERT DEFAULT DATA (Only 3 users, no duplicates)
-- ============================================

-- Default Admin User (password: admin123)
INSERT INTO `users` (`username`, `password`, `full_name`, `email`, `phone`, `user_type`) VALUES 
('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'System Administrator', 'admin@nkwa.com', '0240000000', 'admin');

-- Default Employee User 1 (password: employee123)
INSERT INTO `users` (`username`, `password`, `full_name`, `email`, `phone`, `user_type`) VALUES 
('employee1', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'John Doe', 'john@nkwa.com', '0240000001', 'employee');

-- Default Employee User 2 (password: employee123)
INSERT INTO `users` (`username`, `password`, `full_name`, `email`, `phone`, `user_type`) VALUES 
('employee2', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Jane Smith', 'jane@nkwa.com', '0240000002', 'employee');

-- Loan Products
INSERT INTO `loan_products` (`product_name`, `product_code`, `description`, `min_amount`, `max_amount`, `interest_rate`, `interest_type`, `duration_min_months`, `duration_max_months`, `processing_fee_percentage`, `late_fee_percentage`, `guarantor_required`, `collateral_required`) VALUES
('Personal Loan Basic', 'PLB001', 'Small personal loans for emergencies and personal needs', 500.00, 5000.00, 15.00, 'reducing_balance', 1, 6, 2.00, 5.00, 1, 0),
('Personal Loan Standard', 'PLS002', 'Medium personal loans for various personal projects', 5001.00, 20000.00, 12.00, 'reducing_balance', 3, 12, 2.00, 5.00, 1, 0),
('Business Starter Loan', 'BSL003', 'Small business loans for startups and small businesses', 1000.00, 10000.00, 10.00, 'reducing_balance', 3, 12, 3.00, 5.00, 1, 0),
('Business Growth Loan', 'BGL004', 'Larger business loans for expanding businesses', 10001.00, 50000.00, 8.00, 'reducing_balance', 6, 24, 3.00, 5.00, 1, 1),
('Education Loan', 'EDL005', 'Loans for school fees and educational expenses', 500.00, 15000.00, 10.00, 'reducing_balance', 3, 24, 1.00, 5.00, 1, 0),
('Emergency Loan', 'EML006', 'Quick emergency loans with fast approval', 100.00, 3000.00, 12.00, 'flat', 1, 3, 1.00, 3.00, 0, 0),
('Group Loan', 'GRL007', 'Loans for registered groups and associations', 5000.00, 100000.00, 8.00, 'reducing_balance', 6, 36, 2.00, 5.00, 1, 1),
('Agricultural Loan', 'AGL008', 'Loans for farmers and agricultural businesses', 1000.00, 20000.00, 10.00, 'reducing_balance', 3, 12, 2.00, 5.00, 1, 0);

-- System Settings
INSERT INTO `system_settings` (`setting_key`, `setting_value`, `setting_type`, `description`) VALUES
('company_name', 'Western Eye Susu Enterprise', 'text', 'Company name'),
('company_address', 'Takoradi Mpintin First Junction', 'text', 'Company address'),
('company_phone', '+233 53 392 4067', 'text', 'Company phone'),
('company_email', 'info@nkwa.com', 'text', 'Company email'),
('default_currency', 'GHS', 'text', 'Default currency'),
('loan_max_active', '3', 'number', 'Maximum active loans per customer'),
('savings_min_balance', '50.00', 'number', 'Minimum savings balance'),
('susu_default_amount', '10.00', 'number', 'Default daily susu amount'),
('late_fee_percentage', '5.00', 'number', 'Late payment fee percentage'),
('session_lifetime', '28800', 'number', 'Session lifetime in seconds');

COMMIT;