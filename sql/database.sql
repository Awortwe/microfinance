-- ============================================
-- NKWA MICROFINANCE DATABASE SCHEMA
-- Version: 1.0.0
-- ============================================

-- Create database
CREATE DATABASE IF NOT EXISTS microfinance_db;
USE microfinance_db;

-- ============================================
-- USERS TABLE (for authentication)
-- ============================================
CREATE TABLE IF NOT EXISTS users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    full_name VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    phone VARCHAR(20),
    user_type ENUM('admin', 'employee') NOT NULL DEFAULT 'employee',
    is_active BOOLEAN DEFAULT TRUE,
    remember_token VARCHAR(64) NULL,
    last_login TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- CUSTOMERS TABLE
-- ============================================
CREATE TABLE IF NOT EXISTS customers (
    id INT PRIMARY KEY AUTO_INCREMENT,
    customer_code VARCHAR(20) UNIQUE NOT NULL,
    first_name VARCHAR(50) NOT NULL,
    last_name VARCHAR(50) NOT NULL,
    email VARCHAR(100),
    phone VARCHAR(20) NOT NULL,
    alternate_phone VARCHAR(20),
    address TEXT,
    city VARCHAR(50),
    region VARCHAR(50),
    id_type ENUM('Ghana Card', 'Voter ID', 'Passport', 'Driver License', 'NHIS') DEFAULT 'Ghana Card',
    id_number VARCHAR(50),
    occupation VARCHAR(100),
    business_name VARCHAR(100),
    business_address TEXT,
    date_of_birth DATE,
    gender ENUM('Male', 'Female', 'Other'),
    marital_status ENUM('Single', 'Married', 'Divorced', 'Widowed'),
    photo VARCHAR(255),
    id_document VARCHAR(255),
    status ENUM('active', 'inactive', 'blacklisted') DEFAULT 'active',
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- SAVINGS ACCOUNTS TABLE (Susu)
-- ============================================
CREATE TABLE IF NOT EXISTS savings_accounts (
    id INT PRIMARY KEY AUTO_INCREMENT,
    customer_id INT NOT NULL,
    account_number VARCHAR(20) UNIQUE NOT NULL,
    account_type ENUM('regular', 'susu', 'fixed_deposit') NOT NULL DEFAULT 'regular',
    balance DECIMAL(15,2) DEFAULT 0.00,
    interest_rate DECIMAL(5,2) DEFAULT 0.00,
    susu_amount DECIMAL(10,2) DEFAULT 0.00 COMMENT 'Daily susu contribution amount',
    susu_collection_day ENUM('daily', 'weekly', 'monthly') DEFAULT 'daily',
    status ENUM('active', 'dormant', 'closed') DEFAULT 'active',
    opened_date DATE NOT NULL,
    closed_date DATE,
    notes TEXT,
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE RESTRICT,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- SAVINGS TRANSACTIONS TABLE
-- ============================================
CREATE TABLE IF NOT EXISTS savings_transactions (
    id INT PRIMARY KEY AUTO_INCREMENT,
    account_id INT NOT NULL,
    transaction_type ENUM('deposit', 'withdrawal', 'interest', 'fee', 'susu_collection') NOT NULL,
    amount DECIMAL(15,2) NOT NULL,
    balance_before DECIMAL(15,2) NOT NULL,
    balance_after DECIMAL(15,2) NOT NULL,
    description TEXT,
    transaction_date DATE NOT NULL,
    transaction_time TIME NOT NULL,
    payment_method ENUM('cash', 'transfer', 'mobile_money', 'cheque') DEFAULT 'cash',
    reference_number VARCHAR(50),
    processed_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (account_id) REFERENCES savings_accounts(id) ON DELETE RESTRICT,
    FOREIGN KEY (processed_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- LOAN PRODUCTS TABLE
-- ============================================
CREATE TABLE IF NOT EXISTS loan_products (
    id INT PRIMARY KEY AUTO_INCREMENT,
    product_name VARCHAR(100) NOT NULL,
    product_code VARCHAR(20) UNIQUE NOT NULL,
    description TEXT,
    min_amount DECIMAL(15,2) NOT NULL,
    max_amount DECIMAL(15,2) NOT NULL,
    interest_rate DECIMAL(5,2) NOT NULL COMMENT 'Annual interest rate',
    interest_type ENUM('flat', 'reducing_balance') DEFAULT 'reducing_balance',
    duration_min_months INT NOT NULL,
    duration_max_months INT NOT NULL,
    processing_fee_percentage DECIMAL(5,2) DEFAULT 0.00,
    processing_fee_fixed DECIMAL(10,2) DEFAULT 0.00,
    late_fee_percentage DECIMAL(5,2) DEFAULT 0.00,
    late_fee_fixed DECIMAL(10,2) DEFAULT 0.00,
    collateral_required BOOLEAN DEFAULT FALSE,
    guarantor_required BOOLEAN DEFAULT TRUE,
    min_savings_balance DECIMAL(15,2) DEFAULT 0.00,
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- LOANS TABLE
-- ============================================
CREATE TABLE IF NOT EXISTS loans (
    id INT PRIMARY KEY AUTO_INCREMENT,
    customer_id INT NOT NULL,
    product_id INT NOT NULL,
    loan_number VARCHAR(20) UNIQUE NOT NULL,
    principal_amount DECIMAL(15,2) NOT NULL,
    interest_rate DECIMAL(5,2) NOT NULL,
    interest_type ENUM('flat', 'reducing_balance') DEFAULT 'reducing_balance',
    total_interest DECIMAL(15,2) NOT NULL,
    processing_fee DECIMAL(10,2) DEFAULT 0.00,
    total_amount DECIMAL(15,2) NOT NULL COMMENT 'Principal + Interest + Fees',
    monthly_payment DECIMAL(15,2) NOT NULL,
    duration_months INT NOT NULL,
    amount_paid DECIMAL(15,2) DEFAULT 0.00,
    balance DECIMAL(15,2) NOT NULL,
    application_date DATE NOT NULL,
    approval_date DATE,
    disbursement_date DATE,
    expected_end_date DATE,
    actual_end_date DATE,
    status ENUM('pending', 'approved', 'disbursed', 'active', 'completed', 'defaulted', 'written_off', 'rejected') DEFAULT 'pending',
    rejection_reason TEXT,
    notes TEXT,
    approved_by INT,
    disbursed_by INT,
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE RESTRICT,
    FOREIGN KEY (product_id) REFERENCES loan_products(id) ON DELETE RESTRICT,
    FOREIGN KEY (approved_by) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (disbursed_by) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- LOAN GUARANTORS TABLE
-- ============================================
CREATE TABLE IF NOT EXISTS loan_guarantors (
    id INT PRIMARY KEY AUTO_INCREMENT,
    loan_id INT NOT NULL,
    customer_id INT NOT NULL COMMENT 'Guarantor is also a customer',
    relationship VARCHAR(50),
    status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (loan_id) REFERENCES loans(id) ON DELETE CASCADE,
    FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- LOAN COLLATERAL TABLE
-- ============================================
CREATE TABLE IF NOT EXISTS loan_collateral (
    id INT PRIMARY KEY AUTO_INCREMENT,
    loan_id INT NOT NULL,
    collateral_type VARCHAR(100) NOT NULL,
    description TEXT,
    estimated_value DECIMAL(15,2),
    document_reference VARCHAR(100),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (loan_id) REFERENCES loans(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- LOAN REPAYMENTS TABLE
-- ============================================
CREATE TABLE IF NOT EXISTS loan_repayments (
    id INT PRIMARY KEY AUTO_INCREMENT,
    loan_id INT NOT NULL,
    amount DECIMAL(15,2) NOT NULL,
    principal_paid DECIMAL(15,2) NOT NULL,
    interest_paid DECIMAL(15,2) NOT NULL,
    fee_paid DECIMAL(10,2) DEFAULT 0.00,
    late_fee DECIMAL(10,2) DEFAULT 0.00,
    balance_before DECIMAL(15,2) NOT NULL,
    balance_after DECIMAL(15,2) NOT NULL,
    payment_date DATE NOT NULL,
    payment_time TIME NOT NULL,
    payment_method ENUM('cash', 'transfer', 'mobile_money', 'cheque') DEFAULT 'cash',
    reference_number VARCHAR(50),
    notes TEXT,
    processed_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (loan_id) REFERENCES loans(id) ON DELETE RESTRICT,
    FOREIGN KEY (processed_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- LOAN SCHEDULE TABLE (Amortization)
-- ============================================
CREATE TABLE IF NOT EXISTS loan_schedule (
    id INT PRIMARY KEY AUTO_INCREMENT,
    loan_id INT NOT NULL,
    installment_number INT NOT NULL,
    due_date DATE NOT NULL,
    principal_amount DECIMAL(15,2) NOT NULL,
    interest_amount DECIMAL(15,2) NOT NULL,
    total_amount DECIMAL(15,2) NOT NULL,
    amount_paid DECIMAL(15,2) DEFAULT 0.00,
    balance_after DECIMAL(15,2) NOT NULL,
    status ENUM('pending', 'partial', 'paid', 'overdue') DEFAULT 'pending',
    paid_date DATE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (loan_id) REFERENCES loans(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- SAVINGS TO LOAN LINK TABLE (Savings as collateral)
-- ============================================
CREATE TABLE IF NOT EXISTS savings_loan_link (
    id INT PRIMARY KEY AUTO_INCREMENT,
    loan_id INT NOT NULL,
    savings_account_id INT NOT NULL,
    lien_amount DECIMAL(15,2) NOT NULL COMMENT 'Amount held as security',
    status ENUM('active', 'released') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (loan_id) REFERENCES loans(id) ON DELETE CASCADE,
    FOREIGN KEY (savings_account_id) REFERENCES savings_accounts(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- AUDIT TRAIL TABLE (System Logs)
-- ============================================
CREATE TABLE IF NOT EXISTS audit_trail (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT,
    action VARCHAR(255) NOT NULL,
    module VARCHAR(50) NOT NULL,
    record_id INT,
    old_values TEXT,
    new_values TEXT,
    ip_address VARCHAR(45),
    user_agent TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- SYSTEM SETTINGS TABLE
-- ============================================
CREATE TABLE IF NOT EXISTS system_settings (
    id INT PRIMARY KEY AUTO_INCREMENT,
    setting_key VARCHAR(50) UNIQUE NOT NULL,
    setting_value TEXT,
    setting_type ENUM('text', 'number', 'boolean', 'json') DEFAULT 'text',
    description VARCHAR(255),
    updated_by INT,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (updated_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- NOTIFICATIONS TABLE
-- ============================================
CREATE TABLE IF NOT EXISTS notifications (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    title VARCHAR(100) NOT NULL,
    message TEXT NOT NULL,
    type ENUM('info', 'warning', 'success', 'danger') DEFAULT 'info',
    is_read BOOLEAN DEFAULT FALSE,
    link VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- INSERT DEFAULT DATA
-- ============================================

-- Default Admin User (password: admin123)
INSERT INTO users (username, password, full_name, email, phone, user_type) VALUES 
('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'System Administrator', 'admin@nkwa.com', '0240000000', 'admin');

-- Default Employee User (password: employee123)
INSERT INTO users (username, password, full_name, email, phone, user_type) VALUES 
('employee1', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'John Doe', 'john@nkwa.com', '0240000001', 'employee');

-- Default Employee User 2 (password: employee123)
INSERT INTO users (username, password, full_name, email, phone, user_type) VALUES 
('employee2', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Jane Smith', 'jane@nkwa.com', '0240000002', 'employee');

-- Loan Products
INSERT INTO loan_products (product_name, product_code, description, min_amount, max_amount, interest_rate, interest_type, duration_min_months, duration_max_months, processing_fee_percentage, late_fee_percentage, guarantor_required, collateral_required) VALUES
('Personal Loan Basic', 'PLB001', 'Small personal loans for emergencies and personal needs. Quick approval within 24 hours.', 500.00, 5000.00, 15.00, 'reducing_balance', 1, 6, 2.00, 5.00, TRUE, FALSE),
('Personal Loan Standard', 'PLS002', 'Medium personal loans for various personal projects and needs.', 5001.00, 20000.00, 12.00, 'reducing_balance', 3, 12, 2.00, 5.00, TRUE, FALSE),
('Business Starter Loan', 'BSL003', 'Small business loans for startups and small businesses. Perfect for inventory and working capital.', 1000.00, 10000.00, 10.00, 'reducing_balance', 3, 12, 3.00, 5.00, TRUE, FALSE),
('Business Growth Loan', 'BGL004', 'Larger business loans for expanding businesses. Ideal for expansion and equipment purchase.', 10001.00, 50000.00, 8.00, 'reducing_balance', 6, 24, 3.00, 5.00, TRUE, TRUE),
('Education Loan', 'EDL005', 'Loans for school fees and educational expenses. Flexible repayment during school holidays.', 500.00, 15000.00, 10.00, 'reducing_balance', 3, 24, 1.00, 5.00, TRUE, FALSE),
('Emergency Loan', 'EML006', 'Quick emergency loans with fast approval. Get funds within hours for urgent needs.', 100.00, 3000.00, 12.00, 'flat', 1, 3, 1.00, 3.00, FALSE, FALSE),
('Group Loan', 'GRL007', 'Loans for registered groups and associations. Group guarantee reduces individual risk.', 5000.00, 100000.00, 8.00, 'reducing_balance', 6, 36, 2.00, 5.00, TRUE, TRUE),
('Agricultural Loan', 'AGL008', 'Loans for farmers and agricultural businesses. Seasonal repayment options available.', 1000.00, 20000.00, 10.00, 'reducing_balance', 3, 12, 2.00, 5.00, TRUE, FALSE);

-- System Settings
INSERT INTO system_settings (setting_key, setting_value, setting_type, description) VALUES
('company_name', 'Nkwa Microfinance', 'text', 'Company name displayed across the system'),
('company_address', '123 Independence Avenue, Adjacent to ECG Office, Kumasi, Ghana', 'text', 'Company physical address'),
('company_phone', '+233 24 567 8901', 'text', 'Company primary phone number'),
('company_email', 'info@nkwa.com', 'text', 'Company email address'),
('default_currency', 'GHS', 'text', 'Default currency for all transactions'),
('loan_max_active', '3', 'number', 'Maximum active loans allowed per customer'),
('savings_min_balance', '50.00', 'number', 'Minimum balance required for savings accounts'),
('susu_default_amount', '10.00', 'number', 'Default daily susu collection amount'),
('late_fee_percentage', '5.00', 'number', 'Default late payment fee percentage'),
('session_lifetime', '28800', 'number', 'Session lifetime in seconds (8 hours)');

-- ============================================
-- CREATE INDEXES FOR BETTER PERFORMANCE
-- ============================================
CREATE INDEX idx_customer_code ON customers(customer_code);
CREATE INDEX idx_customer_status ON customers(status);
CREATE INDEX idx_customer_phone ON customers(phone);
CREATE INDEX idx_customer_name ON customers(first_name, last_name);
CREATE INDEX idx_customer_created_by ON customers(created_by);
CREATE INDEX idx_savings_account_number ON savings_accounts(account_number);
CREATE INDEX idx_savings_customer ON savings_accounts(customer_id);
CREATE INDEX idx_savings_status ON savings_accounts(status);
CREATE INDEX idx_savings_created_by ON savings_accounts(created_by);
CREATE INDEX idx_loan_number ON loans(loan_number);
CREATE INDEX idx_loan_customer ON loans(customer_id);
CREATE INDEX idx_loan_status ON loans(status);
CREATE INDEX idx_loan_dates ON loans(disbursement_date, expected_end_date);
CREATE INDEX idx_loan_created_by ON loans(created_by);
CREATE INDEX idx_repayment_loan ON loan_repayments(loan_id);
CREATE INDEX idx_repayment_date ON loan_repayments(payment_date);
CREATE INDEX idx_repayment_processed_by ON loan_repayments(processed_by);
CREATE INDEX idx_schedule_loan ON loan_schedule(loan_id);
CREATE INDEX idx_schedule_due_date ON loan_schedule(due_date, status);
CREATE INDEX idx_transaction_account ON savings_transactions(account_id);
CREATE INDEX idx_transaction_date ON savings_transactions(transaction_date);
CREATE INDEX idx_transaction_processed_by ON savings_transactions(processed_by);
CREATE INDEX idx_audit_user ON audit_trail(user_id);
CREATE INDEX idx_audit_module ON audit_trail(module, created_at);
CREATE INDEX idx_audit_created ON audit_trail(created_at);
CREATE INDEX idx_notification_user ON notifications(user_id, is_read);

-- ============================================
-- CREATE VIEWS FOR COMMON REPORTS
-- ============================================

-- Active Loans Summary View
CREATE OR REPLACE VIEW v_active_loans AS
SELECT 
    l.id,
    l.loan_number,
    c.customer_code,
    CONCAT(c.first_name, ' ', c.last_name) AS customer_name,
    c.phone AS customer_phone,
    lp.product_name,
    l.principal_amount,
    l.total_amount,
    l.monthly_payment,
    l.amount_paid,
    l.balance,
    l.disbursement_date,
    l.expected_end_date,
    DATEDIFF(l.expected_end_date, CURDATE()) AS days_remaining,
    ROUND((l.amount_paid / l.total_amount) * 100, 2) AS repayment_percentage,
    l.status
FROM loans l
JOIN customers c ON l.customer_id = c.id
JOIN loan_products lp ON l.product_id = lp.id
WHERE l.status IN ('active', 'disbursed');

-- Customer Savings Summary View
CREATE OR REPLACE VIEW v_customer_savings AS
SELECT 
    c.id AS customer_id,
    c.customer_code,
    CONCAT(c.first_name, ' ', c.last_name) AS customer_name,
    c.phone,
    COUNT(sa.id) AS total_accounts,
    COALESCE(SUM(sa.balance), 0) AS total_savings,
    MAX(sa.opened_date) AS last_account_opened
FROM customers c
LEFT JOIN savings_accounts sa ON c.id = sa.customer_id AND sa.status = 'active'
GROUP BY c.id, c.customer_code, c.first_name, c.last_name, c.phone;

-- Daily Collections View
CREATE OR REPLACE VIEW v_daily_collections AS
SELECT 
    DATE(payment_date) AS collection_date,
    COUNT(*) AS total_transactions,
    COALESCE(SUM(amount), 0) AS total_collected,
    COALESCE(SUM(principal_paid), 0) AS principal_collected,
    COALESCE(SUM(interest_paid), 0) AS interest_collected,
    COALESCE(SUM(late_fee), 0) AS late_fees_collected
FROM loan_repayments
GROUP BY DATE(payment_date);

-- Loan Portfolio Summary View
CREATE OR REPLACE VIEW v_loan_portfolio AS
SELECT 
    lp.product_name,
    COUNT(l.id) AS total_loans,
    COALESCE(SUM(l.principal_amount), 0) AS total_disbursed,
    COALESCE(SUM(l.amount_paid), 0) AS total_repaid,
    COALESCE(SUM(l.balance), 0) AS total_outstanding,
    ROUND(AVG(l.interest_rate), 2) AS avg_interest_rate,
    COUNT(CASE WHEN l.status = 'defaulted' THEN 1 END) AS defaulted_loans,
    COUNT(CASE WHEN l.status = 'completed' THEN 1 END) AS completed_loans
FROM loan_products lp
LEFT JOIN loans l ON lp.id = l.product_id
GROUP BY lp.id, lp.product_name;

-- Employee Performance View
CREATE OR REPLACE VIEW v_employee_performance AS
SELECT 
    u.id AS employee_id,
    u.full_name AS employee_name,
    COUNT(DISTINCT c.id) AS total_customers,
    COUNT(DISTINCT l.id) AS total_loans_created,
    COALESCE(SUM(l.principal_amount), 0) AS total_loan_amount,
    COUNT(DISTINCT lr.id) AS total_repayments,
    COALESCE(SUM(lr.amount), 0) AS total_collections
FROM users u
LEFT JOIN customers c ON u.id = c.created_by
LEFT JOIN loans l ON u.id = l.created_by
LEFT JOIN loan_repayments lr ON u.id = lr.processed_by
WHERE u.user_type = 'employee' AND u.is_active = 1
GROUP BY u.id, u.full_name;

-- ============================================
-- END OF DATABASE SCHEMA
-- ============================================