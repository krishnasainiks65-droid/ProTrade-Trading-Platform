-- ProTrade Trading Platform Database Schema
-- MySQL 8.0+

-- =====================================================
-- USERS TABLE
-- =====================================================
CREATE TABLE IF NOT EXISTS users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    first_name VARCHAR(100) NOT NULL,
    last_name VARCHAR(100) NOT NULL,
    email VARCHAR(120) UNIQUE NOT NULL,
    phone VARCHAR(15) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    date_of_birth DATE,
    gender ENUM('Male', 'Female', 'Other'),
    profile_image VARCHAR(255),
    two_factor_enabled BOOLEAN DEFAULT FALSE,
    kyc_status ENUM('Pending', 'Approved', 'Rejected', 'InProgress') DEFAULT 'Pending',
    kyc_document_url VARCHAR(255),
    account_type ENUM('Individual', 'HUF', 'Corporate') DEFAULT 'Individual',
    account_status ENUM('Active', 'Suspended', 'Closed', 'Blocked') DEFAULT 'Active',
    email_verified BOOLEAN DEFAULT FALSE,
    phone_verified BOOLEAN DEFAULT FALSE,
    remember_token VARCHAR(255),
    last_login TIMESTAMP,
    failed_login_attempts INT DEFAULT 0,
    locked_until TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_email (email),
    INDEX idx_phone (phone),
    INDEX idx_kyc_status (kyc_status),
    INDEX idx_account_status (account_status)
);

-- =====================================================
-- USER OTP TABLE
-- =====================================================
CREATE TABLE IF NOT EXISTS user_otps (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    otp_code VARCHAR(6) NOT NULL,
    otp_type ENUM('Email', 'Phone', 'ResetPassword') NOT NULL,
    is_verified BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    expires_at TIMESTAMP NOT NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_id (user_id),
    INDEX idx_expires_at (expires_at)
);

-- =====================================================
-- LOGIN HISTORY TABLE
-- =====================================================
CREATE TABLE IF NOT EXISTS login_history (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    ip_address VARCHAR(45) NOT NULL,
    user_agent TEXT,
    login_status ENUM('Success', 'Failed', 'Blocked') DEFAULT 'Success',
    failure_reason VARCHAR(255),
    login_time TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    logout_time TIMESTAMP NULL,
    session_duration INT,
    device_type VARCHAR(100),
    browser VARCHAR(100),
    os VARCHAR(100),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_id (user_id),
    INDEX idx_login_time (login_time)
);

-- =====================================================
-- WALLET TABLE
-- =====================================================
CREATE TABLE IF NOT EXISTS wallets (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL UNIQUE,
    available_balance DECIMAL(18, 2) DEFAULT 0.00,
    reserved_balance DECIMAL(18, 2) DEFAULT 0.00,
    total_balance DECIMAL(18, 2) DEFAULT 0.00,
    currency VARCHAR(3) DEFAULT 'INR',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_id (user_id)
);

-- =====================================================
-- WALLET TRANSACTIONS TABLE
-- =====================================================
CREATE TABLE IF NOT EXISTS wallet_transactions (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    wallet_id INT NOT NULL,
    transaction_type ENUM('Deposit', 'Withdrawal', 'Refund', 'Trading', 'Commission', 'Bonus') NOT NULL,
    amount DECIMAL(18, 2) NOT NULL,
    previous_balance DECIMAL(18, 2) NOT NULL,
    new_balance DECIMAL(18, 2) NOT NULL,
    description TEXT,
    reference_id VARCHAR(100),
    payment_method ENUM('Bank Transfer', 'Credit Card', 'Debit Card', 'UPI', 'NetBanking') DEFAULT 'Bank Transfer',
    status ENUM('Pending', 'Processing', 'Completed', 'Failed', 'Cancelled') DEFAULT 'Pending',
    remarks TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (wallet_id) REFERENCES wallets(id) ON DELETE CASCADE,
    INDEX idx_user_id (user_id),
    INDEX idx_wallet_id (wallet_id),
    INDEX idx_created_at (created_at),
    INDEX idx_status (status)
);

-- =====================================================
-- STOCKS/SECURITIES TABLE
-- =====================================================
CREATE TABLE IF NOT EXISTS securities (
    id INT PRIMARY KEY AUTO_INCREMENT,
    symbol VARCHAR(20) UNIQUE NOT NULL,
    name VARCHAR(255) NOT NULL,
    exchange ENUM('NSE', 'BSE') DEFAULT 'NSE',
    sector VARCHAR(100),
    industry VARCHAR(100),
    current_price DECIMAL(18, 4) NOT NULL DEFAULT 0,
    previous_close DECIMAL(18, 4),
    open_price DECIMAL(18, 4),
    high_price DECIMAL(18, 4),
    low_price DECIMAL(18, 4),
    volume BIGINT DEFAULT 0,
    market_cap DECIMAL(20, 2),
    pe_ratio DECIMAL(10, 2),
    eps DECIMAL(10, 4),
    dividend_yield DECIMAL(10, 4),
    is_active BOOLEAN DEFAULT TRUE,
    last_updated TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE INDEX idx_symbol (symbol),
    INDEX idx_exchange (exchange),
    INDEX idx_is_active (is_active)
);

-- =====================================================
-- HOLDINGS TABLE
-- =====================================================
CREATE TABLE IF NOT EXISTS holdings (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    security_id INT NOT NULL,
    quantity INT NOT NULL DEFAULT 0,
    average_buy_price DECIMAL(18, 4) NOT NULL,
    current_price DECIMAL(18, 4) NOT NULL,
    total_cost DECIMAL(18, 2) NOT NULL,
    current_value DECIMAL(18, 2) NOT NULL,
    profit_loss DECIMAL(18, 2),
    profit_loss_percentage DECIMAL(10, 4),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (security_id) REFERENCES securities(id),
    UNIQUE INDEX idx_user_security (user_id, security_id),
    INDEX idx_user_id (user_id)
);

-- =====================================================
-- ORDERS TABLE
-- =====================================================
CREATE TABLE IF NOT EXISTS orders (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    security_id INT NOT NULL,
    order_type ENUM('Buy', 'Sell') NOT NULL,
    order_status ENUM('Pending', 'Executed', 'Partially Executed', 'Cancelled', 'Rejected') DEFAULT 'Pending',
    quantity INT NOT NULL,
    executed_quantity INT DEFAULT 0,
    price DECIMAL(18, 4) NOT NULL,
    order_time TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    execution_time TIMESTAMP NULL,
    expiry_date DATE,
    order_validity ENUM('Day', 'IOC', 'GTD') DEFAULT 'Day',
    total_value DECIMAL(18, 2),
    charges DECIMAL(18, 2) DEFAULT 0,
    net_value DECIMAL(18, 2),
    remarks TEXT,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (security_id) REFERENCES securities(id),
    INDEX idx_user_id (user_id),
    INDEX idx_order_status (order_status),
    INDEX idx_order_time (order_time)
);

-- =====================================================
-- TRADES TABLE
-- =====================================================
CREATE TABLE IF NOT EXISTS trades (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    order_id INT NOT NULL,
    security_id INT NOT NULL,
    trade_type ENUM('Buy', 'Sell') NOT NULL,
    quantity INT NOT NULL,
    price DECIMAL(18, 4) NOT NULL,
    value DECIMAL(18, 2) NOT NULL,
    brokerage_charges DECIMAL(18, 2),
    stt_charges DECIMAL(18, 2),
    other_charges DECIMAL(18, 2),
    total_charges DECIMAL(18, 2),
    net_value DECIMAL(18, 2),
    trade_time TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    settlement_date DATE,
    remarks TEXT,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    FOREIGN KEY (security_id) REFERENCES securities(id),
    INDEX idx_user_id (user_id),
    INDEX idx_trade_time (trade_time)
);

-- =====================================================
-- WATCHLIST TABLE
-- =====================================================
CREATE TABLE IF NOT EXISTS watchlists (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    security_id INT NOT NULL,
    added_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    added_price DECIMAL(18, 4),
    notes TEXT,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (security_id) REFERENCES securities(id),
    UNIQUE INDEX idx_user_security (user_id, security_id),
    INDEX idx_user_id (user_id)
);

-- =====================================================
-- MARKET DATA TABLE (For historical data)
-- =====================================================
CREATE TABLE IF NOT EXISTS market_data (
    id INT PRIMARY KEY AUTO_INCREMENT,
    security_id INT NOT NULL,
    market_date DATE NOT NULL,
    open_price DECIMAL(18, 4),
    high_price DECIMAL(18, 4),
    low_price DECIMAL(18, 4),
    close_price DECIMAL(18, 4),
    volume BIGINT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (security_id) REFERENCES securities(id) ON DELETE CASCADE,
    UNIQUE INDEX idx_security_date (security_id, market_date),
    INDEX idx_market_date (market_date)
);

-- =====================================================
-- ADMIN USERS TABLE
-- =====================================================
CREATE TABLE IF NOT EXISTS admin_users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(120) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    role ENUM('Super Admin', 'Admin', 'Support', 'Moderator') DEFAULT 'Admin',
    permissions TEXT,
    status ENUM('Active', 'Inactive') DEFAULT 'Active',
    last_login TIMESTAMP,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_email (email),
    INDEX idx_role (role)
);

-- =====================================================
-- KYC DOCUMENTS TABLE
-- =====================================================
CREATE TABLE IF NOT EXISTS kyc_documents (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    document_type ENUM('PAN', 'Aadhaar', 'Passport', 'Driving License', 'Voter ID') NOT NULL,
    document_number VARCHAR(50) NOT NULL,
    document_url VARCHAR(255),
    verification_status ENUM('Pending', 'Verified', 'Rejected') DEFAULT 'Pending',
    rejection_reason TEXT,
    verified_by INT,
    verified_at TIMESTAMP,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (verified_by) REFERENCES admin_users(id),
    INDEX idx_user_id (user_id),
    INDEX idx_verification_status (verification_status)
);

-- =====================================================
-- NOTIFICATIONS TABLE
-- =====================================================
CREATE TABLE IF NOT EXISTS notifications (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    title VARCHAR(255) NOT NULL,
    message TEXT NOT NULL,
    notification_type ENUM('Order', 'Trade', 'Wallet', 'KYC', 'System', 'Alert') DEFAULT 'System',
    is_read BOOLEAN DEFAULT FALSE,
    reference_id INT,
    reference_type VARCHAR(50),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    read_at TIMESTAMP NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_id (user_id),
    INDEX idx_is_read (is_read),
    INDEX idx_created_at (created_at)
);

-- =====================================================
-- AUDIT LOG TABLE
-- =====================================================
CREATE TABLE IF NOT EXISTS audit_logs (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT,
    admin_id INT,
    action VARCHAR(255) NOT NULL,
    action_type ENUM('Create', 'Update', 'Delete', 'View', 'Login', 'Download') NOT NULL,
    entity_type VARCHAR(100),
    entity_id INT,
    old_values JSON,
    new_values JSON,
    ip_address VARCHAR(45),
    user_agent TEXT,
    status ENUM('Success', 'Failed') DEFAULT 'Success',
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (admin_id) REFERENCES admin_users(id) ON DELETE SET NULL,
    INDEX idx_user_id (user_id),
    INDEX idx_admin_id (admin_id),
    INDEX idx_created_at (created_at),
    INDEX idx_action_type (action_type)
);

-- =====================================================
-- SETTINGS TABLE
-- =====================================================
CREATE TABLE IF NOT EXISTS settings (
    id INT PRIMARY KEY AUTO_INCREMENT,
    setting_key VARCHAR(100) UNIQUE NOT NULL,
    setting_value TEXT,
    description TEXT,
    data_type ENUM('String', 'Integer', 'Float', 'Boolean', 'JSON') DEFAULT 'String',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_setting_key (setting_key)
);

-- =====================================================
-- SUPPORT TICKETS TABLE
-- =====================================================
CREATE TABLE IF NOT EXISTS support_tickets (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    ticket_number VARCHAR(50) UNIQUE NOT NULL,
    subject VARCHAR(255) NOT NULL,
    description TEXT NOT NULL,
    category VARCHAR(100),
    priority ENUM('Low', 'Medium', 'High', 'Critical') DEFAULT 'Medium',
    status ENUM('Open', 'In Progress', 'Resolved', 'Closed', 'Reopened') DEFAULT 'Open',
    assigned_to INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    resolved_at TIMESTAMP NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (assigned_to) REFERENCES admin_users(id),
    INDEX idx_user_id (user_id),
    INDEX idx_status (status),
    INDEX idx_ticket_number (ticket_number)
);

-- =====================================================
-- SUPPORT TICKET REPLIES TABLE
-- =====================================================
CREATE TABLE IF NOT EXISTS ticket_replies (
    id INT PRIMARY KEY AUTO_INCREMENT,
    ticket_id INT NOT NULL,
    user_id INT,
    admin_id INT,
    message TEXT NOT NULL,
    attachment_url VARCHAR(255),
    is_internal BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (ticket_id) REFERENCES support_tickets(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (admin_id) REFERENCES admin_users(id) ON DELETE SET NULL,
    INDEX idx_ticket_id (ticket_id)
);

-- =====================================================
-- API LOGS TABLE
-- =====================================================
CREATE TABLE IF NOT EXISTS api_logs (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT,
    api_endpoint VARCHAR(255) NOT NULL,
    request_method VARCHAR(10),
    request_data JSON,
    response_status INT,
    response_data JSON,
    ip_address VARCHAR(45),
    user_agent TEXT,
    execution_time FLOAT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_user_id (user_id),
    INDEX idx_created_at (created_at)
);

-- =====================================================
-- Create Indexes
-- =====================================================
CREATE INDEX idx_total_balance ON wallets(total_balance);
CREATE INDEX idx_security_symbol ON securities(symbol);
CREATE INDEX idx_order_date_range ON orders(order_time, order_status);
CREATE INDEX idx_trade_date_range ON trades(trade_time);
CREATE INDEX idx_wallet_transaction_range ON wallet_transactions(created_at, status);
