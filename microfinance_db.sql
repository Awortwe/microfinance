-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1:4306
-- Generation Time: May 07, 2026 at 01:28 PM
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
-- Database: `microfinance_db`
--

DELIMITER $$
--
-- Procedures
--
CREATE DEFINER=`root`@`localhost` PROCEDURE `sp_calculate_loan_schedule` (IN `p_loan_id` INT, IN `p_principal` DECIMAL(15,2), IN `p_interest_rate` DECIMAL(5,2), IN `p_duration_months` INT, IN `p_interest_type` ENUM('flat','reducing_balance'), IN `p_start_date` DATE)   BEGIN
    DECLARE i INT DEFAULT 1;
    DECLARE v_monthly_principal DECIMAL(15,2);
    DECLARE v_monthly_interest DECIMAL(15,2);
    DECLARE v_monthly_total DECIMAL(15,2);
    DECLARE v_balance DECIMAL(15,2) DEFAULT p_principal;
    DECLARE v_due_date DATE;
    
    IF p_interest_type = 'flat' THEN
        -- Flat interest calculation
        SET v_monthly_principal = p_principal / p_duration_months;
        SET v_monthly_interest = (p_principal * (p_interest_rate/100)) / 12;
        SET v_monthly_total = v_monthly_principal + v_monthly_interest;
        
        WHILE i <= p_duration_months DO
            SET v_due_date = DATE_ADD(p_start_date, INTERVAL i MONTH);
            SET v_balance = v_balance - v_monthly_principal;
            
            INSERT INTO loan_schedule (loan_id, installment_number, due_date, 
                                      principal_amount, interest_amount, total_amount, 
                                      balance_after, status)
            VALUES (p_loan_id, i, v_due_date, 
                   v_monthly_principal, v_monthly_interest, v_monthly_total, 
                   v_balance, 'pending');
            
            SET i = i + 1;
        END WHILE;
    ELSE
        -- Reducing balance interest calculation
        SET v_monthly_principal = p_principal / p_duration_months;
        SET v_monthly_interest = (p_principal * (p_interest_rate/100)) / 12;
        
        WHILE i <= p_duration_months DO
            SET v_due_date = DATE_ADD(p_start_date, INTERVAL i MONTH);
            SET v_balance = v_balance - v_monthly_principal;
            
            INSERT INTO loan_schedule (loan_id, installment_number, due_date,
                                      principal_amount, interest_amount, total_amount,
                                      balance_after, status)
            VALUES (p_loan_id, i, v_due_date,
                   v_monthly_principal, v_monthly_interest, 
                   v_monthly_principal + v_monthly_interest,
                   v_balance, 'pending');
            
            -- Recalculate interest for next month based on remaining balance
            SET v_monthly_interest = (v_balance * (p_interest_rate/100)) / 12;
            SET i = i + 1;
        END WHILE;
    END IF;
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `sp_generate_customer_code` (OUT `customer_code` VARCHAR(20))   BEGIN
    DECLARE next_id INT;
    SELECT COALESCE(MAX(id), 0) + 1 INTO next_id FROM customers;
    SET customer_code = CONCAT('CUS', LPAD(next_id, 6, '0'));
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `sp_generate_loan_number` (OUT `loan_number` VARCHAR(20))   BEGIN
    DECLARE next_id INT;
    SELECT COALESCE(MAX(id), 0) + 1 INTO next_id FROM loans;
    SET loan_number = CONCAT('LN', LPAD(next_id, 8, '0'));
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `sp_generate_savings_account` (OUT `account_number` VARCHAR(20))   BEGIN
    DECLARE next_id INT;
    SELECT COALESCE(MAX(id), 0) + 1 INTO next_id FROM savings_accounts;
    SET account_number = CONCAT('SAV', LPAD(next_id, 8, '0'));
END$$

DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `audit_trail`
--

CREATE TABLE `audit_trail` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `action` varchar(100) NOT NULL,
  `module` varchar(50) NOT NULL,
  `record_id` int(11) DEFAULT NULL,
  `old_values` text DEFAULT NULL,
  `new_values` text DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `audit_trail`
--

INSERT INTO `audit_trail` (`id`, `user_id`, `action`, `module`, `record_id`, `old_values`, `new_values`, `ip_address`, `user_agent`, `created_at`) VALUES
(1, NULL, 'Login Failed - Wrong Password', 'auth', 1, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-06 11:33:50'),
(2, NULL, 'Login Failed - Wrong Password', 'auth', 2, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-06 11:34:28'),
(3, 1, 'Login Successful', 'auth', 1, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-06 11:38:41'),
(4, 1, 'Page Access: dashboard.php', 'system', NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-06 11:38:41'),
(5, 1, 'Page Access: dashboard.php', 'system', NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-06 11:39:19'),
(6, 1, 'Page Access: dashboard.php', 'system', NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-06 11:39:51'),
(7, 1, 'Page Access: dashboard.php', 'system', NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-06 11:39:53'),
(8, 1, 'Page Access: dashboard.php', 'system', NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-06 11:40:20'),
(9, 1, 'Page Access: dashboard.php', 'system', NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-06 11:43:02'),
(10, 1, 'Page Access: dashboard.php', 'system', NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-06 11:43:22'),
(11, 1, 'Login Successful', 'auth', 1, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-06 12:05:53'),
(12, 1, 'Page Access: dashboard.php', 'system', NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-06 12:05:53'),
(13, 1, 'Page Access: dashboard.php', 'system', NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-06 12:10:30'),
(14, 1, 'Logout', 'auth', 1, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-06 12:11:13'),
(15, 1, 'Login Successful', 'auth', 1, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-06 12:11:23'),
(16, 1, 'Page Access: dashboard.php', 'system', NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-06 12:11:23'),
(17, 1, 'Page Access: dashboard.php', 'system', NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-06 12:16:25'),
(18, 1, 'Page Access: dashboard.php', 'system', NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-06 12:19:59'),
(19, 1, 'Page Access: dashboard.php', 'system', NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-06 12:24:59'),
(20, 1, 'Page Access: dashboard.php', 'system', NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-06 12:27:49'),
(21, 1, 'Page Access: add.php', 'system', NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-06 12:28:34'),
(22, 1, 'Page Access: add.php', 'system', NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-06 12:29:05'),
(23, 1, 'Page Access: add.php', 'system', NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-06 12:29:33'),
(24, 1, 'Page Access: dashboard.php', 'system', NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-06 12:47:33'),
(25, 1, 'Page Access: add.php', 'system', NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-06 12:47:43'),
(26, 1, 'Page Access: add.php', 'system', NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-06 12:48:00'),
(27, 1, 'User Created', 'users', 3, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-06 12:48:00'),
(28, 1, 'User Updated', 'users', 1, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-06 12:48:29'),
(29, 1, 'Page Access: dashboard.php', 'system', NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-06 12:53:49'),
(30, 1, 'Logout', 'auth', 1, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-06 12:53:54'),
(31, 3, 'Login Successful', 'auth', 3, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-06 12:54:14'),
(32, 3, 'Page Access: dashboard.php', 'system', NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-06 12:54:14'),
(33, 3, 'Page Access: dashboard.php', 'system', NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-06 13:01:02'),
(34, 3, 'Page Access: dashboard.php', 'system', NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-06 13:03:16'),
(35, 3, 'Page Access: dashboard.php', 'system', NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-06 13:03:59'),
(36, 3, 'Page Access: dashboard.php', 'system', NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-06 13:11:03'),
(37, 3, 'Logout', 'auth', 3, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-06 13:11:06'),
(38, 3, 'Login Successful', 'auth', 3, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-06 13:11:19'),
(39, 3, 'Page Access: dashboard.php', 'system', NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-06 13:11:19'),
(40, 3, 'Page Access: dashboard.php', 'system', NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-06 13:11:24'),
(41, 3, 'Page Access: dashboard.php', 'system', NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-06 13:11:27'),
(42, 3, 'Logout', 'auth', 3, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-06 13:11:57'),
(43, 3, 'Login Successful', 'auth', 3, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-06 13:16:41'),
(44, 3, 'Page Access: dashboard.php', 'system', NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-06 13:16:41'),
(45, 3, 'User Updated', 'users', 3, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-06 13:17:18'),
(46, 3, 'User Updated', 'users', 3, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-06 13:17:32'),
(47, 3, 'Page Access: add.php', 'system', NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-06 15:54:32'),
(48, 3, 'Page Access: approve.php', 'system', NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-06 16:00:48'),
(49, 3, 'Page Access: add.php', 'system', NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-06 16:11:17'),
(50, 3, 'Loan Product Updated', 'products', 4, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-06 16:11:51'),
(51, 3, 'Page Access: approve.php', 'system', NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-06 16:12:02'),
(52, 3, 'Settings Updated', 'system', NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-06 16:35:21'),
(53, 3, 'Database Backup Created', 'system', NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-06 16:42:38'),
(54, 3, 'Backup File Deleted', 'system', NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-06 16:43:20'),
(55, 3, 'Page Access: dashboard.php', 'system', NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-06 16:48:00'),
(56, 3, 'Logout', 'auth', 3, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-06 16:48:24'),
(57, 3, 'Login Successful', 'auth', 3, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-06 16:48:35'),
(58, 3, 'Page Access: dashboard.php', 'system', NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-06 16:48:35'),
(59, 3, 'Page Access: dashboard.php', 'system', NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-06 16:50:07'),
(60, 3, 'Page Access: dashboard.php', 'system', NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-06 16:52:24'),
(61, 3, 'Page Access: dashboard.php', 'system', NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-06 16:52:37'),
(62, 3, 'Page Access: dashboard.php', 'system', NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-06 16:54:45'),
(63, 3, 'Page Access: dashboard.php', 'system', NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-06 16:55:20'),
(64, 3, 'Page Access: dashboard.php', 'system', NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-06 16:55:55'),
(65, 3, 'Page Access: dashboard.php', 'system', NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-06 16:56:36'),
(66, 3, 'Page Access: dashboard.php', 'system', NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-06 16:57:16'),
(67, 3, 'User Updated', 'users', 2, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-06 17:01:32'),
(68, 3, 'User Updated', 'users', 1, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-06 17:01:55'),
(69, 3, 'Logout', 'auth', 3, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-06 17:02:43'),
(70, 2, 'Login Successful', 'auth', 2, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-06 17:02:55'),
(71, 2, 'Page Access: dashboard.php', 'system', NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-06 17:02:55'),
(72, 2, 'Logout', 'auth', 2, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-06 17:03:15'),
(73, 2, 'Login Successful', 'auth', 2, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-06 17:03:26'),
(74, 2, 'Page Access: dashboard.php', 'system', NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-06 17:03:27'),
(75, 2, 'Page Access: dashboard.php', 'system', NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-07 07:41:06'),
(76, 2, 'Page Access: dashboard.php', 'system', NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-07 07:47:10'),
(77, 2, 'Page Access: add.php', 'system', NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-07 07:48:14'),
(78, 2, 'Page Access: add.php', 'system', NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-07 07:48:28'),
(79, 2, 'Page Access: create.php', 'system', NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-07 07:59:20'),
(80, 2, 'Page Access: create.php', 'system', NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-07 08:22:56'),
(81, 2, 'Page Access: dashboard.php', 'system', NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-07 09:26:09'),
(82, 2, 'Logout', 'auth', 2, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-07 09:26:15'),
(83, 3, 'Login Successful', 'auth', 3, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-07 09:26:54'),
(84, 3, 'Page Access: dashboard.php', 'system', NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-07 09:26:57'),
(85, 3, 'Page Access: add.php', 'system', NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-07 09:27:08'),
(86, 3, 'Page Access: dashboard.php', 'system', NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-07 09:27:27'),
(87, 3, 'Page Access: dashboard.php', 'system', NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-07 09:35:29'),
(88, 3, 'Page Access: dashboard.php', 'system', NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-07 09:40:18'),
(89, 3, 'Page Access: dashboard.php', 'system', NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-07 09:40:48'),
(90, 3, 'Logout', 'auth', 3, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-07 09:40:50'),
(91, 3, 'Login Successful', 'auth', 3, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-07 09:41:09'),
(92, 3, 'Page Access: dashboard.php', 'system', NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-07 09:41:09'),
(93, 3, 'Logout', 'auth', 3, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-07 09:42:45'),
(94, 3, 'Login Successful', 'auth', 3, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-07 09:53:51'),
(95, 3, 'Page Access: dashboard.php', 'system', NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-07 09:53:51'),
(96, 3, 'Company Profile Updated', 'settings', NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-07 09:54:23'),
(97, 3, 'Page Access: dashboard.php', 'system', NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-07 09:54:32'),
(98, 3, 'Logout', 'auth', 3, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-07 09:54:48'),
(99, 3, 'Login Successful', 'auth', 3, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-07 09:55:31'),
(100, 3, 'Page Access: dashboard.php', 'system', NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-07 09:55:31'),
(101, 3, 'Logout', 'auth', 3, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-07 09:55:52'),
(102, 3, 'Admin Login Successful', 'auth', 3, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-07 10:12:27'),
(103, 3, 'Page Access: dashboard.php', 'system', NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-07 10:12:27'),
(104, 3, 'Page Access: dashboard.php', 'system', NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-07 10:12:43'),
(105, 3, 'Page Access: dashboard.php', 'system', NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-07 10:12:53'),
(106, 3, 'Page Access: dashboard.php', 'system', NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-07 10:13:47'),
(107, 3, 'Page Access: dashboard.php', 'system', NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-07 10:16:59'),
(108, 3, 'Page Access: dashboard.php', 'system', NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-07 10:19:50'),
(109, 3, 'Page Access: dashboard.php', 'system', NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-07 10:19:55'),
(110, 3, 'Page Access: dashboard.php', 'system', NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-07 10:29:38'),
(111, 3, 'Logout', 'auth', 3, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-07 10:29:51'),
(112, 3, 'Admin Login Successful', 'auth', 3, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-07 10:30:01'),
(113, 3, 'Page Access: dashboard.php', 'system', NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-07 10:30:01'),
(114, 3, 'Logout', 'auth', 3, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-07 10:30:07'),
(115, 3, 'Admin Login Successful', 'auth', 3, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-07 10:30:20'),
(116, 3, 'Page Access: dashboard.php', 'system', NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-07 10:30:20'),
(117, 3, 'Logout', 'auth', 3, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-07 10:31:00'),
(118, 2, 'Employee Login Successful', 'auth', 2, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-07 10:36:56'),
(119, 2, 'Page Access: dashboard.php', 'system', NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-07 10:36:57'),
(120, 2, 'Logout', 'auth', 2, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-07 10:37:05'),
(121, 2, 'Employee Login Successful', 'auth', 2, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-07 10:37:15'),
(122, 2, 'Page Access: dashboard.php', 'system', NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-07 10:37:15'),
(123, 2, 'Logout', 'auth', 2, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-07 10:37:34'),
(124, 3, 'Admin Login Successful', 'auth', 3, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-07 10:41:08'),
(125, 3, 'Page Access: dashboard.php', 'system', NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-07 10:41:08'),
(126, 3, 'Page Access: dashboard.php', 'system', NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-07 10:42:19'),
(127, 3, 'Logout', 'auth', 3, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-07 10:42:45'),
(128, 2, 'Employee Login Successful', 'auth', 2, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-07 10:43:08'),
(129, 2, 'Page Access: dashboard.php', 'system', NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-07 10:43:08'),
(130, 2, 'Logout', 'auth', 2, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-07 10:43:46'),
(131, 3, 'Admin Login Successful', 'auth', 3, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-07 10:44:00'),
(132, 3, 'Page Access: dashboard.php', 'system', NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-07 10:44:00'),
(133, 3, 'Logout', 'auth', 3, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-07 10:54:25'),
(134, 3, 'Admin Login Successful', 'auth', 3, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-07 11:08:12'),
(135, 3, 'Page Access: dashboard.php', 'system', NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-07 11:08:12'),
(136, 3, 'Company Profile Updated', 'settings', NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-07 11:09:59'),
(137, 3, 'Page Access: dashboard.php', 'system', NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-07 11:10:08'),
(138, 3, 'Logout', 'auth', 3, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-07 11:10:25');

-- --------------------------------------------------------

--
-- Table structure for table `customers`
--

CREATE TABLE `customers` (
  `id` int(11) NOT NULL,
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
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Triggers `customers`
--
DELIMITER $$
CREATE TRIGGER `trg_customer_code` BEFORE INSERT ON `customers` FOR EACH ROW BEGIN
    IF NEW.customer_code IS NULL THEN
        CALL sp_generate_customer_code(@new_code);
        SET NEW.customer_code = @new_code;
    END IF;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `loans`
--

CREATE TABLE `loans` (
  `id` int(11) NOT NULL,
  `customer_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `loan_number` varchar(20) NOT NULL,
  `principal_amount` decimal(15,2) NOT NULL,
  `interest_rate` decimal(5,2) NOT NULL,
  `interest_type` enum('flat','reducing_balance') DEFAULT 'reducing_balance',
  `total_interest` decimal(15,2) NOT NULL,
  `processing_fee` decimal(10,2) DEFAULT 0.00,
  `total_amount` decimal(15,2) NOT NULL COMMENT 'Principal + Interest + Fees',
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
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Triggers `loans`
--
DELIMITER $$
CREATE TRIGGER `trg_loan_number` BEFORE INSERT ON `loans` FOR EACH ROW BEGIN
    IF NEW.loan_number IS NULL THEN
        CALL sp_generate_loan_number(@new_number);
        SET NEW.loan_number = @new_number;
    END IF;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `loan_collateral`
--

CREATE TABLE `loan_collateral` (
  `id` int(11) NOT NULL,
  `loan_id` int(11) NOT NULL,
  `collateral_type` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `estimated_value` decimal(15,2) DEFAULT NULL,
  `document_reference` varchar(100) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `loan_guarantors`
--

CREATE TABLE `loan_guarantors` (
  `id` int(11) NOT NULL,
  `loan_id` int(11) NOT NULL,
  `customer_id` int(11) NOT NULL COMMENT 'Guarantor is also a customer',
  `relationship` varchar(50) DEFAULT NULL,
  `status` enum('pending','approved','rejected') DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `loan_products`
--

CREATE TABLE `loan_products` (
  `id` int(11) NOT NULL,
  `product_name` varchar(100) NOT NULL,
  `product_code` varchar(20) NOT NULL,
  `description` text DEFAULT NULL,
  `min_amount` decimal(15,2) NOT NULL,
  `max_amount` decimal(15,2) NOT NULL,
  `interest_rate` decimal(5,2) NOT NULL COMMENT 'Annual interest rate',
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
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `loan_products`
--

INSERT INTO `loan_products` (`id`, `product_name`, `product_code`, `description`, `min_amount`, `max_amount`, `interest_rate`, `interest_type`, `duration_min_months`, `duration_max_months`, `processing_fee_percentage`, `processing_fee_fixed`, `late_fee_percentage`, `late_fee_fixed`, `collateral_required`, `guarantor_required`, `min_savings_balance`, `status`, `created_by`, `created_at`, `updated_at`) VALUES
(1, 'Personal Loan Basic', 'PLB001', 'Small personal loans for emergencies and personal needs', 500.00, 5000.00, 15.00, 'reducing_balance', 1, 6, 2.00, 0.00, 5.00, 0.00, 0, 1, 0.00, 'active', NULL, '2026-05-05 16:02:49', '2026-05-05 16:02:49'),
(2, 'Personal Loan Standard', 'PLS002', 'Medium personal loans for various personal projects', 5001.00, 20000.00, 12.00, 'reducing_balance', 3, 12, 2.00, 0.00, 5.00, 0.00, 0, 1, 0.00, 'active', NULL, '2026-05-05 16:02:49', '2026-05-05 16:02:49'),
(3, 'Business Starter Loan', 'BSL003', 'Small business loans for startups and small businesses', 1000.00, 10000.00, 10.00, 'reducing_balance', 3, 12, 3.00, 0.00, 5.00, 0.00, 0, 1, 0.00, 'active', NULL, '2026-05-05 16:02:49', '2026-05-05 16:02:49'),
(4, 'Business Growth Loan', 'BGL004', 'Larger business loans for expanding businesses', 20001.01, 50000.00, 8.00, 'reducing_balance', 6, 24, 0.00, 0.00, 5.00, 0.00, 0, 1, 0.00, 'active', NULL, '2026-05-05 16:02:49', '2026-05-06 16:11:51'),
(5, 'Education Loan', 'EDL005', 'Loans for school fees and educational expenses', 500.00, 15000.00, 10.00, 'reducing_balance', 3, 24, 1.00, 0.00, 5.00, 0.00, 0, 1, 0.00, 'active', NULL, '2026-05-05 16:02:49', '2026-05-05 16:02:49'),
(6, 'Emergency Loan', 'EML006', 'Quick emergency loans with fast approval', 100.00, 3000.00, 12.00, 'flat', 1, 3, 1.00, 0.00, 3.00, 0.00, 0, 0, 0.00, 'active', NULL, '2026-05-05 16:02:49', '2026-05-05 16:02:49'),
(7, 'Group Loan', 'GRL007', 'Loans for registered groups and associations', 5000.00, 100000.00, 8.00, 'reducing_balance', 6, 36, 2.00, 0.00, 5.00, 0.00, 0, 1, 0.00, 'active', NULL, '2026-05-05 16:02:49', '2026-05-05 16:02:49');

-- --------------------------------------------------------

--
-- Table structure for table `loan_repayments`
--

CREATE TABLE `loan_repayments` (
  `id` int(11) NOT NULL,
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
  `collection_officer` int(11) DEFAULT NULL COMMENT 'Employee who collected payment',
  `notes` text DEFAULT NULL,
  `processed_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Triggers `loan_repayments`
--
DELIMITER $$
CREATE TRIGGER `trg_after_repayment` AFTER INSERT ON `loan_repayments` FOR EACH ROW BEGIN
    UPDATE loans 
    SET amount_paid = amount_paid + NEW.amount,
        balance = balance - NEW.amount,
        status = CASE 
            WHEN balance - NEW.amount <= 0 THEN 'completed'
            ELSE status 
        END,
        actual_end_date = CASE 
            WHEN balance - NEW.amount <= 0 THEN NEW.payment_date 
            ELSE actual_end_date 
        END
    WHERE id = NEW.loan_id;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `loan_schedule`
--

CREATE TABLE `loan_schedule` (
  `id` int(11) NOT NULL,
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
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

CREATE TABLE `notifications` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `title` varchar(100) NOT NULL,
  `message` text NOT NULL,
  `type` enum('info','warning','success','danger') DEFAULT 'info',
  `is_read` tinyint(1) DEFAULT 0,
  `link` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `savings_accounts`
--

CREATE TABLE `savings_accounts` (
  `id` int(11) NOT NULL,
  `customer_id` int(11) NOT NULL,
  `account_number` varchar(20) NOT NULL,
  `account_type` enum('regular','susu','fixed_deposit') NOT NULL DEFAULT 'regular',
  `balance` decimal(15,2) DEFAULT 0.00,
  `interest_rate` decimal(5,2) DEFAULT 0.00,
  `susu_amount` decimal(10,2) DEFAULT 0.00 COMMENT 'Daily susu contribution amount',
  `susu_collection_day` enum('daily','weekly','monthly') DEFAULT 'daily',
  `status` enum('active','dormant','closed') DEFAULT 'active',
  `opened_date` date NOT NULL,
  `closed_date` date DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Triggers `savings_accounts`
--
DELIMITER $$
CREATE TRIGGER `trg_savings_account` BEFORE INSERT ON `savings_accounts` FOR EACH ROW BEGIN
    IF NEW.account_number IS NULL THEN
        CALL sp_generate_savings_account(@new_account);
        SET NEW.account_number = @new_account;
    END IF;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `savings_loan_link`
--

CREATE TABLE `savings_loan_link` (
  `id` int(11) NOT NULL,
  `loan_id` int(11) NOT NULL,
  `savings_account_id` int(11) NOT NULL,
  `lien_amount` decimal(15,2) NOT NULL COMMENT 'Amount held as security',
  `status` enum('active','released') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `savings_transactions`
--

CREATE TABLE `savings_transactions` (
  `id` int(11) NOT NULL,
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
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Triggers `savings_transactions`
--
DELIMITER $$
CREATE TRIGGER `trg_after_savings_transaction` AFTER INSERT ON `savings_transactions` FOR EACH ROW BEGIN
    IF NEW.transaction_type IN ('deposit', 'susu_collection') THEN
        UPDATE savings_accounts SET balance = balance + NEW.amount WHERE id = NEW.account_id;
    ELSEIF NEW.transaction_type IN ('withdrawal', 'fee') THEN
        UPDATE savings_accounts SET balance = balance - NEW.amount WHERE id = NEW.account_id;
    END IF;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `system_settings`
--

CREATE TABLE `system_settings` (
  `id` int(11) NOT NULL,
  `setting_key` varchar(50) NOT NULL,
  `setting_value` text DEFAULT NULL,
  `setting_type` enum('text','number','boolean','json') DEFAULT 'text',
  `description` varchar(255) DEFAULT NULL,
  `updated_by` int(11) DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `system_settings`
--

INSERT INTO `system_settings` (`id`, `setting_key`, `setting_value`, `setting_type`, `description`, `updated_by`, `updated_at`) VALUES
(1, 'company_name', 'Western Eye Susu Enterprise', 'text', 'Company name', 3, '2026-05-07 11:09:59'),
(2, 'company_address', 'Takoradi Mpintin First Junction', 'text', 'Company address', 3, '2026-05-07 11:09:59'),
(3, 'company_phone', '+233 53 392 4067', 'text', 'Company phone number', 3, '2026-05-07 11:09:59'),
(4, 'company_email', 'info@nkwa.com', 'text', 'Company email', 3, '2026-05-06 16:35:21'),
(5, 'default_currency', 'GHS', 'text', 'Default currency', 3, '2026-05-06 16:35:21'),
(6, 'loan_max_active', '3', 'number', 'Maximum active loans per customer', 3, '2026-05-06 16:35:21'),
(7, 'savings_min_balance', '50.00', 'number', 'Minimum savings account balance', 3, '2026-05-06 16:35:21'),
(8, 'susu_default_amount', '10.00', 'number', 'Default daily susu amount', 3, '2026-05-06 16:35:21');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `user_type` enum('admin','employee') NOT NULL DEFAULT 'employee',
  `is_active` tinyint(1) DEFAULT 1,
  `last_login` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `password`, `full_name`, `email`, `phone`, `user_type`, `is_active`, `last_login`, `created_at`, `updated_at`) VALUES
(1, 'admin', '$2y$10$CwLXW5h4nQbfCl0xKQuND.JBQ45BlyVq78gdxUEf2MPfZevCVFLwS', 'System Administrator', 'admin@nkwa.com', '0240000000', 'admin', 1, '2026-05-06 12:11:23', '2026-05-05 16:02:49', '2026-05-06 17:01:55'),
(2, 'employee1', '$2y$10$tvucN8wHAX6eikdPru8/EOH2HPU/mrfkLye/Ms88ph8B2mPqji.aq', 'John Doe', 'john@nkwa.com', '0240000001', 'employee', 1, '2026-05-07 10:43:08', '2026-05-05 16:02:49', '2026-05-07 10:43:08'),
(3, 'Awortwe', '$2y$10$x5lWmpbN./VISW/tByGPa.eJBa9ePUhWKkwZMXVMyLdAsaN54/gGC', 'Awortwe Enock', 'enockawor@gmail.com', '0245227067', 'admin', 1, '2026-05-07 11:08:12', '2026-05-06 12:48:00', '2026-05-07 11:08:12');

-- --------------------------------------------------------

--
-- Stand-in structure for view `v_active_loans`
-- (See below for the actual view)
--
CREATE TABLE `v_active_loans` (
`id` int(11)
,`loan_number` varchar(20)
,`customer_code` varchar(20)
,`customer_name` varchar(101)
,`customer_phone` varchar(20)
,`product_name` varchar(100)
,`principal_amount` decimal(15,2)
,`total_amount` decimal(15,2)
,`monthly_payment` decimal(15,2)
,`amount_paid` decimal(15,2)
,`balance` decimal(15,2)
,`disbursement_date` date
,`expected_end_date` date
,`days_remaining` int(7)
,`repayment_percentage` decimal(21,2)
,`status` enum('pending','approved','disbursed','active','completed','defaulted','written_off','rejected')
);

-- --------------------------------------------------------

--
-- Stand-in structure for view `v_customer_savings`
-- (See below for the actual view)
--
CREATE TABLE `v_customer_savings` (
`customer_id` int(11)
,`customer_code` varchar(20)
,`customer_name` varchar(101)
,`phone` varchar(20)
,`total_accounts` bigint(21)
,`total_savings` decimal(37,2)
,`last_account_opened` date
);

-- --------------------------------------------------------

--
-- Stand-in structure for view `v_daily_collections`
-- (See below for the actual view)
--
CREATE TABLE `v_daily_collections` (
`collection_date` date
,`total_transactions` bigint(21)
,`total_collected` decimal(37,2)
,`principal_collected` decimal(37,2)
,`interest_collected` decimal(37,2)
,`late_fees_collected` decimal(32,2)
);

-- --------------------------------------------------------

--
-- Stand-in structure for view `v_loan_portfolio`
-- (See below for the actual view)
--
CREATE TABLE `v_loan_portfolio` (
`product_name` varchar(100)
,`total_loans` bigint(21)
,`total_disbursed` decimal(37,2)
,`total_repaid` decimal(37,2)
,`total_outstanding` decimal(37,2)
,`avg_interest_rate` decimal(6,2)
,`defaulted_loans` bigint(21)
);

-- --------------------------------------------------------

--
-- Structure for view `v_active_loans`
--
DROP TABLE IF EXISTS `v_active_loans`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `v_active_loans`  AS SELECT `l`.`id` AS `id`, `l`.`loan_number` AS `loan_number`, `c`.`customer_code` AS `customer_code`, concat(`c`.`first_name`,' ',`c`.`last_name`) AS `customer_name`, `c`.`phone` AS `customer_phone`, `lp`.`product_name` AS `product_name`, `l`.`principal_amount` AS `principal_amount`, `l`.`total_amount` AS `total_amount`, `l`.`monthly_payment` AS `monthly_payment`, `l`.`amount_paid` AS `amount_paid`, `l`.`balance` AS `balance`, `l`.`disbursement_date` AS `disbursement_date`, `l`.`expected_end_date` AS `expected_end_date`, to_days(`l`.`expected_end_date`) - to_days(curdate()) AS `days_remaining`, round(`l`.`amount_paid` / `l`.`total_amount` * 100,2) AS `repayment_percentage`, `l`.`status` AS `status` FROM ((`loans` `l` join `customers` `c` on(`l`.`customer_id` = `c`.`id`)) join `loan_products` `lp` on(`l`.`product_id` = `lp`.`id`)) WHERE `l`.`status` in ('active','disbursed') ;

-- --------------------------------------------------------

--
-- Structure for view `v_customer_savings`
--
DROP TABLE IF EXISTS `v_customer_savings`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `v_customer_savings`  AS SELECT `c`.`id` AS `customer_id`, `c`.`customer_code` AS `customer_code`, concat(`c`.`first_name`,' ',`c`.`last_name`) AS `customer_name`, `c`.`phone` AS `phone`, count(`sa`.`id`) AS `total_accounts`, coalesce(sum(`sa`.`balance`),0) AS `total_savings`, max(`sa`.`opened_date`) AS `last_account_opened` FROM (`customers` `c` left join `savings_accounts` `sa` on(`c`.`id` = `sa`.`customer_id` and `sa`.`status` = 'active')) GROUP BY `c`.`id`, `c`.`customer_code`, `c`.`first_name`, `c`.`last_name`, `c`.`phone` ;

-- --------------------------------------------------------

--
-- Structure for view `v_daily_collections`
--
DROP TABLE IF EXISTS `v_daily_collections`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `v_daily_collections`  AS SELECT cast(`loan_repayments`.`payment_date` as date) AS `collection_date`, count(0) AS `total_transactions`, coalesce(sum(`loan_repayments`.`amount`),0) AS `total_collected`, coalesce(sum(`loan_repayments`.`principal_paid`),0) AS `principal_collected`, coalesce(sum(`loan_repayments`.`interest_paid`),0) AS `interest_collected`, coalesce(sum(`loan_repayments`.`late_fee`),0) AS `late_fees_collected` FROM `loan_repayments` GROUP BY cast(`loan_repayments`.`payment_date` as date) ;

-- --------------------------------------------------------

--
-- Structure for view `v_loan_portfolio`
--
DROP TABLE IF EXISTS `v_loan_portfolio`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `v_loan_portfolio`  AS SELECT `lp`.`product_name` AS `product_name`, count(`l`.`id`) AS `total_loans`, coalesce(sum(`l`.`principal_amount`),0) AS `total_disbursed`, coalesce(sum(`l`.`amount_paid`),0) AS `total_repaid`, coalesce(sum(`l`.`balance`),0) AS `total_outstanding`, round(avg(`l`.`interest_rate`),2) AS `avg_interest_rate`, count(case when `l`.`status` = 'defaulted' then 1 end) AS `defaulted_loans` FROM (`loan_products` `lp` left join `loans` `l` on(`lp`.`id` = `l`.`product_id`)) GROUP BY `lp`.`id`, `lp`.`product_name` ;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `audit_trail`
--
ALTER TABLE `audit_trail`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_audit_user` (`user_id`),
  ADD KEY `idx_audit_module` (`module`,`created_at`);

--
-- Indexes for table `customers`
--
ALTER TABLE `customers`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `customer_code` (`customer_code`),
  ADD KEY `created_by` (`created_by`),
  ADD KEY `idx_customer_code` (`customer_code`),
  ADD KEY `idx_customer_status` (`status`),
  ADD KEY `idx_customer_phone` (`phone`);

--
-- Indexes for table `loans`
--
ALTER TABLE `loans`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `loan_number` (`loan_number`),
  ADD KEY `product_id` (`product_id`),
  ADD KEY `approved_by` (`approved_by`),
  ADD KEY `disbursed_by` (`disbursed_by`),
  ADD KEY `created_by` (`created_by`),
  ADD KEY `idx_loan_number` (`loan_number`),
  ADD KEY `idx_loan_customer` (`customer_id`),
  ADD KEY `idx_loan_status` (`status`),
  ADD KEY `idx_loan_dates` (`disbursement_date`,`expected_end_date`);

--
-- Indexes for table `loan_collateral`
--
ALTER TABLE `loan_collateral`
  ADD PRIMARY KEY (`id`),
  ADD KEY `loan_id` (`loan_id`);

--
-- Indexes for table `loan_guarantors`
--
ALTER TABLE `loan_guarantors`
  ADD PRIMARY KEY (`id`),
  ADD KEY `loan_id` (`loan_id`),
  ADD KEY `customer_id` (`customer_id`);

--
-- Indexes for table `loan_products`
--
ALTER TABLE `loan_products`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `product_code` (`product_code`),
  ADD KEY `created_by` (`created_by`);

--
-- Indexes for table `loan_repayments`
--
ALTER TABLE `loan_repayments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `collection_officer` (`collection_officer`),
  ADD KEY `processed_by` (`processed_by`),
  ADD KEY `idx_repayment_loan` (`loan_id`),
  ADD KEY `idx_repayment_date` (`payment_date`);

--
-- Indexes for table `loan_schedule`
--
ALTER TABLE `loan_schedule`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_schedule_loan` (`loan_id`),
  ADD KEY `idx_schedule_due_date` (`due_date`,`status`);

--
-- Indexes for table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_notification_user` (`user_id`,`is_read`);

--
-- Indexes for table `savings_accounts`
--
ALTER TABLE `savings_accounts`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `account_number` (`account_number`),
  ADD KEY `created_by` (`created_by`),
  ADD KEY `idx_savings_account_number` (`account_number`),
  ADD KEY `idx_savings_customer` (`customer_id`),
  ADD KEY `idx_savings_status` (`status`);

--
-- Indexes for table `savings_loan_link`
--
ALTER TABLE `savings_loan_link`
  ADD PRIMARY KEY (`id`),
  ADD KEY `loan_id` (`loan_id`),
  ADD KEY `savings_account_id` (`savings_account_id`);

--
-- Indexes for table `savings_transactions`
--
ALTER TABLE `savings_transactions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `processed_by` (`processed_by`),
  ADD KEY `idx_transaction_account` (`account_id`),
  ADD KEY `idx_transaction_date` (`transaction_date`);

--
-- Indexes for table `system_settings`
--
ALTER TABLE `system_settings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `setting_key` (`setting_key`),
  ADD KEY `updated_by` (`updated_by`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `audit_trail`
--
ALTER TABLE `audit_trail`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=139;

--
-- AUTO_INCREMENT for table `customers`
--
ALTER TABLE `customers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `loans`
--
ALTER TABLE `loans`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `loan_collateral`
--
ALTER TABLE `loan_collateral`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `loan_guarantors`
--
ALTER TABLE `loan_guarantors`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `loan_products`
--
ALTER TABLE `loan_products`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `loan_repayments`
--
ALTER TABLE `loan_repayments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `loan_schedule`
--
ALTER TABLE `loan_schedule`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `savings_accounts`
--
ALTER TABLE `savings_accounts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `savings_loan_link`
--
ALTER TABLE `savings_loan_link`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `savings_transactions`
--
ALTER TABLE `savings_transactions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `system_settings`
--
ALTER TABLE `system_settings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `audit_trail`
--
ALTER TABLE `audit_trail`
  ADD CONSTRAINT `audit_trail_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `customers`
--
ALTER TABLE `customers`
  ADD CONSTRAINT `customers_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `loans`
--
ALTER TABLE `loans`
  ADD CONSTRAINT `loans_ibfk_1` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`),
  ADD CONSTRAINT `loans_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `loan_products` (`id`),
  ADD CONSTRAINT `loans_ibfk_3` FOREIGN KEY (`approved_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `loans_ibfk_4` FOREIGN KEY (`disbursed_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `loans_ibfk_5` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `loan_collateral`
--
ALTER TABLE `loan_collateral`
  ADD CONSTRAINT `loan_collateral_ibfk_1` FOREIGN KEY (`loan_id`) REFERENCES `loans` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `loan_guarantors`
--
ALTER TABLE `loan_guarantors`
  ADD CONSTRAINT `loan_guarantors_ibfk_1` FOREIGN KEY (`loan_id`) REFERENCES `loans` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `loan_guarantors_ibfk_2` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`);

--
-- Constraints for table `loan_products`
--
ALTER TABLE `loan_products`
  ADD CONSTRAINT `loan_products_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `loan_repayments`
--
ALTER TABLE `loan_repayments`
  ADD CONSTRAINT `loan_repayments_ibfk_1` FOREIGN KEY (`loan_id`) REFERENCES `loans` (`id`),
  ADD CONSTRAINT `loan_repayments_ibfk_2` FOREIGN KEY (`collection_officer`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `loan_repayments_ibfk_3` FOREIGN KEY (`processed_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `loan_schedule`
--
ALTER TABLE `loan_schedule`
  ADD CONSTRAINT `loan_schedule_ibfk_1` FOREIGN KEY (`loan_id`) REFERENCES `loans` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `notifications`
--
ALTER TABLE `notifications`
  ADD CONSTRAINT `notifications_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `savings_accounts`
--
ALTER TABLE `savings_accounts`
  ADD CONSTRAINT `savings_accounts_ibfk_1` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`),
  ADD CONSTRAINT `savings_accounts_ibfk_2` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `savings_loan_link`
--
ALTER TABLE `savings_loan_link`
  ADD CONSTRAINT `savings_loan_link_ibfk_1` FOREIGN KEY (`loan_id`) REFERENCES `loans` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `savings_loan_link_ibfk_2` FOREIGN KEY (`savings_account_id`) REFERENCES `savings_accounts` (`id`);

--
-- Constraints for table `savings_transactions`
--
ALTER TABLE `savings_transactions`
  ADD CONSTRAINT `savings_transactions_ibfk_1` FOREIGN KEY (`account_id`) REFERENCES `savings_accounts` (`id`),
  ADD CONSTRAINT `savings_transactions_ibfk_2` FOREIGN KEY (`processed_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `system_settings`
--
ALTER TABLE `system_settings`
  ADD CONSTRAINT `system_settings_ibfk_1` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
