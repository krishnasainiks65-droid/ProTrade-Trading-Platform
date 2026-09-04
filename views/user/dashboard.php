<?php
/**
 * User Dashboard View
 */

if (!isAuthenticated()) {
    redirect(APP_URL . '/login');
}

try {
    $db = getConnection();
    $userId = getCurrentUserId();
    
    // Get wallet information
    $walletStmt = $db->prepare('SELECT * FROM wallets WHERE user_id = ?');
    $walletStmt->execute([$userId]);
    $wallet = $walletStmt->fetch();
    
    // Get holdings
    $holdingsStmt = $db->prepare('
        SELECT h.*, s.symbol, s.name, s.current_price
        FROM holdings h
        JOIN securities s ON h.security_id = s.id
        WHERE h.user_id = ?
    ');
    $holdingsStmt->execute([$userId]);
    $holdings = $holdingsStmt->fetchAll();
    
    // Calculate portfolio value
    $portfolioValue = 0;
    $totalInvested = 0;
    foreach ($holdings as $holding) {
        $portfolioValue += ($holding['quantity'] * $holding['current_price']);
        $totalInvested += $holding['total_cost'];
    }
    
    $profitLoss = $portfolioValue - $totalInvested;
    $profitLossPercentage = $totalInvested > 0 ? ($profitLoss / $totalInvested) * 100 : 0;
    
    // Get recent orders
    $ordersStmt = $db->prepare('
        SELECT o.*, s.symbol, s.name
        FROM orders o
        JOIN securities s ON o.security_id = s.id
        WHERE o.user_id = ?
        ORDER BY o.order_time DESC
        LIMIT 5
    ');
    $ordersStmt->execute([$userId]);
    $recentOrders = $ordersStmt->fetchAll();
    
    // Get recent trades
    $tradesStmt = $db->prepare('
        SELECT t.*, s.symbol, s.name
        FROM trades t
        JOIN securities s ON t.security_id = s.id
        WHERE t.user_id = ?
        ORDER BY t.trade_time DESC
        LIMIT 5
    ');
    $tradesStmt->execute([$userId]);
    $recentTrades = $tradesStmt->fetchAll();
    
    // Get notifications
    $notificationsStmt = $db->prepare('
        SELECT * FROM notifications
        WHERE user_id = ? AND is_read = 0
        ORDER BY created_at DESC
        LIMIT 5
    ');
    $notificationsStmt->execute([$userId]);
    $notifications = $notificationsStmt->fetchAll();
    
} catch (Exception $e) {
    logMessage('Dashboard error: ' . $e->getMessage(), 'error');
    $error = 'Failed to load dashboard data';
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - ProTrade</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="<?= APP_URL ?>/assets/css/style.css">
</head>
<body>
    <?php include 'views/components/navbar.php'; ?>
    
    <div class="container-fluid py-4">
        <div class="row mb-4">
            <div class="col-md-12">
                <h1 class="h3 mb-0">Dashboard</h1>
                <p class="text-muted">Welcome back, <?= e(getCurrentUser()['first_name']) ?></p>
            </div>
        </div>
        
        <!-- Key Metrics -->
        <div class="row mb-4">
            <div class="col-md-3 mb-3">
                <div class="card border-0 shadow-sm">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="flex-grow-1">
                                <p class="text-muted mb-1">Wallet Balance</p>
                                <h4 class="mb-0"><?= formatCurrency($wallet['total_balance'] ?? 0) ?></h4>
                            </div>
                            <div class="text-primary" style="font-size: 2rem;">
                                <i class="bi bi-wallet2"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-md-3 mb-3">
                <div class="card border-0 shadow-sm">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="flex-grow-1">
                                <p class="text-muted mb-1">Portfolio Value</p>
                                <h4 class="mb-0"><?= formatCurrency($portfolioValue) ?></h4>
                            </div>
                            <div class="text-info" style="font-size: 2rem;">
                                <i class="bi bi-graph-up"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-md-3 mb-3">
                <div class="card border-0 shadow-sm">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="flex-grow-1">
                                <p class="text-muted mb-1">Total Invested</p>
                                <h4 class="mb-0"><?= formatCurrency($totalInvested) ?></h4>
                            </div>
                            <div class="text-warning" style="font-size: 2rem;">
                                <i class="bi bi-cash-coin"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-md-3 mb-3">
                <div class="card border-0 shadow-sm">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="flex-grow-1">
                                <p class="text-muted mb-1">Profit/Loss</p>
                                <h4 class="mb-0 <?= $profitLoss >= 0 ? 'text-success' : 'text-danger' ?>">
                                    <?= formatCurrency($profitLoss) ?>
                                </h4>
                                <small class="<?= $profitLossPercentage >= 0 ? 'text-success' : 'text-danger' ?>">
                                    <?= $profitLossPercentage >= 0 ? '+' : '' ?><?= formatPercentage($profitLossPercentage) ?>
                                </small>
                            </div>
                            <div class="<?= $profitLoss >= 0 ? 'text-success' : 'text-danger' ?>" style="font-size: 2rem;">
                                <i class="bi <?= $profitLoss >= 0 ? 'bi-graph-up' : 'bi-graph-down' ?>"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Holdings & Orders -->
        <div class="row mb-4">
            <div class="col-md-6 mb-3">
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-light border-bottom">
                        <h5 class="mb-0">Top Holdings</h5>
                    </div>
                    <div class="card-body">
                        <?php if (count($holdings) > 0): ?>
                            <div class="table-responsive">
                                <table class="table table-sm">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Symbol</th>
                                            <th>Qty</th>
                                            <th>Avg Price</th>
                                            <th>Current</th>
                                            <th class="text-end">P&L</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach (array_slice($holdings, 0, 5) as $holding): ?>
                                            <tr>
                                                <td class="fw-bold"><?= e($holding['symbol']) ?></td>
                                                <td><?= $holding['quantity'] ?></td>
                                                <td><?= formatCurrency($holding['average_buy_price']) ?></td>
                                                <td><?= formatCurrency($holding['current_price']) ?></td>
                                                <td class="text-end <?= $holding['profit_loss'] >= 0 ? 'text-success' : 'text-danger' ?>">
                                                    <?= formatCurrency($holding['profit_loss']) ?>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                            <a href="<?= APP_URL ?>/holdings" class="btn btn-sm btn-primary mt-2">View All Holdings</a>
                        <?php else: ?>
                            <p class="text-muted text-center py-4">No holdings yet. Start trading now!</p>
                            <a href="<?= APP_URL ?>/market" class="btn btn-sm btn-primary w-100">Browse Market</a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <div class="col-md-6 mb-3">
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-light border-bottom">
                        <h5 class="mb-0">Recent Orders</h5>
                    </div>
                    <div class="card-body">
                        <?php if (count($recentOrders) > 0): ?>
                            <div class="table-responsive">
                                <table class="table table-sm">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Symbol</th>
                                            <th>Type</th>
                                            <th>Qty</th>
                                            <th>Price</th>
                                            <th>Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($recentOrders as $order): ?>
                                            <tr>
                                                <td class="fw-bold"><?= e($order['symbol']) ?></td>
                                                <td>
                                                    <span class="badge <?= $order['order_type'] === 'Buy' ? 'bg-success' : 'bg-danger' ?>">
                                                        <?= $order['order_type'] ?>
                                                    </span>
                                                </td>
                                                <td><?= $order['quantity'] ?></td>
                                                <td><?= formatCurrency($order['price']) ?></td>
                                                <td>
                                                    <span class="badge bg-secondary"><?= $order['order_status'] ?></span>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                            <a href="<?= APP_URL ?>/orders" class="btn btn-sm btn-primary mt-2">View All Orders</a>
                        <?php else: ?>
                            <p class="text-muted text-center py-4">No orders yet</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Quick Actions -->
        <div class="row">
            <div class="col-md-12">
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-light border-bottom">
                        <h5 class="mb-0">Quick Actions</h5>
                    </div>
                    <div class="card-body">
                        <div class="row g-2">
                            <div class="col-md-auto">
                                <a href="<?= APP_URL ?>/deposit" class="btn btn-success">
                                    <i class="bi bi-plus-circle"></i> Deposit
                                </a>
                            </div>
                            <div class="col-md-auto">
                                <a href="<?= APP_URL ?>/withdraw" class="btn btn-warning">
                                    <i class="bi bi-dash-circle"></i> Withdraw
                                </a>
                            </div>
                            <div class="col-md-auto">
                                <a href="<?= APP_URL ?>/market" class="btn btn-info">
                                    <i class="bi bi-graph-up"></i> Browse Market
                                </a>
                            </div>
                            <div class="col-md-auto">
                                <a href="<?= APP_URL ?>/watchlist" class="btn btn-outline-primary">
                                    <i class="bi bi-star"></i> Watchlist
                                </a>
                            </div>
                            <div class="col-md-auto">
                                <a href="<?= APP_URL ?>/portfolio" class="btn btn-outline-primary">
                                    <i class="bi bi-briefcase"></i> Portfolio
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <?php include 'views/components/footer.php'; ?>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="<?= APP_URL ?>/assets/js/main.js"></script>
</body>
</html>
