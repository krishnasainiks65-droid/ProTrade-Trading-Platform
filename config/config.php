<?php
/**
 * Application Configuration
 * ProTrade Trading Platform
 */

define('APP_NAME', 'ProTrade');
define('APP_VERSION', '1.0.0');
define('APP_ENV', getenv('APP_ENV') ?: 'production');
define('APP_DEBUG', getenv('APP_DEBUG') === 'true');
define('APP_URL', getenv('APP_URL') ?: 'http://localhost');

// Session Configuration
define('SESSION_TIMEOUT', 1800); // 30 minutes
define('REMEMBER_ME_DURATION', 7 * 24 * 60 * 60); // 7 days

// Security Configuration
define('ENCRYPTION_KEY', getenv('ENCRYPTION_KEY') ?: 'your-secret-key-change-in-production');
define('JWT_SECRET', getenv('JWT_SECRET') ?: 'your-jwt-secret-key');
define('JWT_EXPIRY', 3600); // 1 hour

// Email Configuration
define('MAIL_DRIVER', getenv('MAIL_DRIVER') ?: 'smtp');
define('MAIL_HOST', getenv('MAIL_HOST') ?: 'smtp.mailtrap.io');
define('MAIL_PORT', getenv('MAIL_PORT') ?: 465);
define('MAIL_USERNAME', getenv('MAIL_USERNAME') ?: '');
define('MAIL_PASSWORD', getenv('MAIL_PASSWORD') ?: '');
define('MAIL_FROM', getenv('MAIL_FROM') ?: 'noreply@protrade.com');
define('MAIL_FROM_NAME', getenv('MAIL_FROM_NAME') ?: 'ProTrade');

// OTP Configuration
define('OTP_LENGTH', 6);
define('OTP_EXPIRY', 300); // 5 minutes

// Trading Configuration
define('MIN_ORDER_VALUE', 1);
define('MAX_ORDER_VALUE', 10000000);
define('TRADING_COMMISSION', 0.05); // 0.05%
define('MARKET_OPEN_HOUR', 9);
define('MARKET_CLOSE_HOUR', 15);

// Wallet Configuration
define('MIN_DEPOSIT', 1000);
define('MAX_DEPOSIT', 10000000);
define('MIN_WITHDRAWAL', 100);
define('WITHDRAWAL_FEE', 0);

// Pagination
define('ITEMS_PER_PAGE', 10);

// File Upload
define('UPLOAD_DIR', __DIR__ . '/../public/uploads/');
define('ALLOWED_FILE_TYPES', ['jpg', 'jpeg', 'png', 'pdf']);
define('MAX_FILE_SIZE', 5 * 1024 * 1024); // 5MB

// API Configuration
define('API_RATE_LIMIT', 100); // requests per minute
define('API_TIMEOUT', 30); // seconds

// Timezone
date_default_timezone_set('Asia/Kolkata');

// Error Reporting
if (APP_DEBUG) {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    error_reporting(E_ALL);
    ini_set('display_errors', 0);
    ini_set('log_errors', 1);
}

// Start Session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
    session_regenerate_id(true);
}