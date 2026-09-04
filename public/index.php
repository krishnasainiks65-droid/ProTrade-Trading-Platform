<?php
/**
 * ProTrade Trading Platform - Main Entry Point
 */

define('BASE_PATH', dirname(dirname(__FILE__)));
define('PUBLIC_PATH', __DIR__);

require_once BASE_PATH . '/config/config.php';
require_once BASE_PATH . '/config/database.php';
require_once BASE_PATH . '/config/functions.php';

// Check if user session is expired
if (isAuthenticated()) {
    $lastActivity = $_SESSION['last_activity'] ?? time();
    if ((time() - $lastActivity) > SESSION_TIMEOUT) {
        session_destroy();
        redirect(APP_URL . '/login?session_expired=1');
    }
    $_SESSION['last_activity'] = time();
}

// Get the requested page
$page = isset($_GET['page']) ? sanitize($_GET['page']) : 'dashboard';

// Define page routes
$routes = [
    // Public pages
    'login' => 'views/auth/login.php',
    'register' => 'views/auth/register.php',
    'forgot-password' => 'views/auth/forgot-password.php',
    'reset-password' => 'views/auth/reset-password.php',
    'verify-email' => 'views/auth/verify-email.php',
    'logout' => 'views/auth/logout.php',
    
    // User Dashboard
    'dashboard' => 'views/user/dashboard.php',
    'profile' => 'views/user/profile.php',
    'change-password' => 'views/user/change-password.php',
    'login-history' => 'views/user/login-history.php',
    
    // Wallet
    'wallet' => 'views/wallet/wallet.php',
    'deposit' => 'views/wallet/deposit.php',
    'withdraw' => 'views/wallet/withdraw.php',
    'transactions' => 'views/wallet/transactions.php',
    
    // Trading
    'portfolio' => 'views/trading/portfolio.php',
    'holdings' => 'views/trading/holdings.php',
    'buy' => 'views/trading/buy.php',
    'sell' => 'views/trading/sell.php',
    'orders' => 'views/trading/orders.php',
    'trades' => 'views/trading/trades.php',
    'order-book' => 'views/trading/order-book.php',
    
    // Market
    'market' => 'views/market/market.php',
    'watchlist' => 'views/market/watchlist.php',
    'market-details' => 'views/market/market-details.php',
    
    // KYC
    'kyc' => 'views/kyc/kyc.php',
    'kyc-upload' => 'views/kyc/kyc-upload.php',
    
    // Admin
    'admin-dashboard' => 'views/admin/dashboard.php',
    'admin-users' => 'views/admin/users.php',
    'admin-transactions' => 'views/admin/transactions.php',
    'admin-kyc' => 'views/admin/kyc.php',
    'admin-reports' => 'views/admin/reports.php',
    'admin-settings' => 'views/admin/settings.php',
    'admin-securities' => 'views/admin/securities.php',
    'admin-tickets' => 'views/admin/support-tickets.php',
    
    // Notifications
    'notifications' => 'views/notifications/notifications.php',
];

// API routes
if (strpos($page, 'api/') === 0) {
    $apiRoute = str_replace('api/', '', $page);
    $apiFile = 'api/' . $apiRoute . '.php';
    
    if (file_exists($apiFile)) {
        header('Content-Type: application/json');
        require_once $apiFile;
    } else {
        jsonResponse(['error' => 'API endpoint not found'], 404);
    }
    exit();
}

// Check authentication for protected pages
$publicPages = ['login', 'register', 'forgot-password', 'reset-password', 'verify-email'];
if (!isAuthenticated() && !in_array($page, $publicPages)) {
    redirect(APP_URL . '/login');
}

// Check admin access for admin pages
if (strpos($page, 'admin-') === 0) {
    $user = getCurrentUser();
    if (!isset($user['role']) || !in_array($user['role'], ['Super Admin', 'Admin', 'Support', 'Moderator'])) {
        redirect(APP_URL . '/dashboard?unauthorized=1');
    }
}

// Load the requested page
if (isset($routes[$page]) && file_exists($routes[$page])) {
    require_once $routes[$page];
} else {
    require_once 'views/404.php';
}
