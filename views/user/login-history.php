<?php
/**
 * Login History View
 */

if (!isAuthenticated()) {
    redirect(APP_URL . '/login');
}

try {
    $db = getConnection();
    $userId = getCurrentUserId();
    
    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    $limit = ITEMS_PER_PAGE;
    $offset = ($page - 1) * $limit;
    
    // Get total count
    $countStmt = $db->prepare('SELECT COUNT(*) as total FROM login_history WHERE user_id = ?');
    $countStmt->execute([$userId]);
    $totalCount = $countStmt->fetch()['total'];
    $totalPages = ceil($totalCount / $limit);
    
    // Get login history
    $stmt = $db->prepare('
        SELECT * FROM login_history
        WHERE user_id = ?
        ORDER BY login_time DESC
        LIMIT ? OFFSET ?
    ');
    $stmt->execute([$userId, $limit, $offset]);
    $logins = $stmt->fetchAll();
    
} catch (Exception $e) {
    logMessage('Login history error: ' . $e->getMessage(), 'error');
    $error = 'Failed to load login history';
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login History - ProTrade</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="<?= APP_URL ?>/assets/css/style.css">
</head>
<body>
    <?php include 'views/components/navbar.php'; ?>
    
    <div class="container-fluid py-4">
        <div class="row mb-4">
            <div class="col-md-12">
                <h1 class="h3 mb-0">Login History</h1>
            </div>
        </div>
        
        <div class="row">
            <div class="col-md-12">
                <div class="card border-0 shadow-sm">
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead class="table-light">
                                    <tr>
                                        <th>Date & Time</th>
                                        <th>Device</th>
                                        <th>Browser</th>
                                        <th>OS</th>
                                        <th>IP Address</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (count($logins) > 0): ?>
                                        <?php foreach ($logins as $login): ?>
                                            <tr>
                                                <td>
                                                    <small><?= formatDate($login['login_time']) ?></small>
                                                </td>
                                                <td>
                                                    <?= e($login['device_type'] ?? 'Unknown') ?>
                                                </td>
                                                <td>
                                                    <?= e($login['browser'] ?? 'Unknown') ?>
                                                </td>
                                                <td>
                                                    <?= e($login['os'] ?? 'Unknown') ?>
                                                </td>
                                                <td>
                                                    <small class="text-monospace"><?= e($login['ip_address']) ?></small>
                                                </td>
                                                <td>
                                                    <?php if ($login['login_status'] === 'Success'): ?>
                                                        <span class="badge bg-success">Success</span>
                                                    <?php elseif ($login['login_status'] === 'Failed'): ?>
                                                        <span class="badge bg-danger">Failed</span>
                                                    <?php else: ?>
                                                        <span class="badge bg-warning">Blocked</span>
                                                    <?php endif; ?>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="6" class="text-center text-muted py-4">
                                                No login history found
                                            </td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                        
                        <?php if ($totalPages > 1): ?>
                            <nav aria-label="Page navigation">
                                <ul class="pagination">
                                    <?php for ($p = 1; $p <= $totalPages; $p++): ?>
                                        <li class="page-item <?= $p === $page ? 'active' : '' ?>">
                                            <a class="page-link" href="?page=<?= $p ?>"><?= $p ?></a>
                                        </li>
                                    <?php endfor; ?>
                                </ul>
                            </nav>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <?php include 'views/components/footer.php'; ?>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
