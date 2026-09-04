<?php
/**
 * User Profile View
 */

if (!isAuthenticated()) {
    redirect(APP_URL . '/login');
}

$error = '';
$success = '';
$userId = getCurrentUserId();

try {
    $db = getConnection();
    
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
        if ($_POST['action'] === 'update_profile') {
            $firstName = sanitize($_POST['first_name'] ?? '');
            $lastName = sanitize($_POST['last_name'] ?? '');
            $phone = sanitize($_POST['phone'] ?? '');
            
            if (!$firstName || !$lastName) {
                $error = 'First name and last name are required';
            } else {
                $stmt = $db->prepare('
                    UPDATE users SET first_name = ?, last_name = ?, phone = ? WHERE id = ?
                ');
                $stmt->execute([$firstName, $lastName, $phone, $userId]);
                $success = 'Profile updated successfully';
                $_SESSION['user']['first_name'] = $firstName;
                $_SESSION['user']['last_name'] = $lastName;
            }
        }
    }
    
    $stmt = $db->prepare('SELECT * FROM users WHERE id = ?');
    $stmt->execute([$userId]);
    $user = $stmt->fetch();
    
} catch (Exception $e) {
    $error = 'An error occurred';
    logMessage('Profile error: ' . $e->getMessage(), 'error');
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile - ProTrade</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="<?= APP_URL ?>/assets/css/style.css">
</head>
<body>
    <?php include 'views/components/navbar.php'; ?>
    
    <div class="container-fluid py-4">
        <div class="row mb-4">
            <div class="col-md-12">
                <h1 class="h3 mb-0">My Profile</h1>
            </div>
        </div>
        
        <?php if ($error): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <?= e($error) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        
        <?php if ($success): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <?= e($success) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        
        <div class="row">
            <div class="col-md-4 mb-4">
                <div class="card border-0 shadow-sm">
                    <div class="card-body text-center">
                        <div class="mb-3">
                            <img src="<?= e($user['profile_image'] ?? 'https://via.placeholder.com/150') ?>" alt="Profile" class="rounded-circle" width="100" height="100">
                        </div>
                        <h5 class="mb-1"><?= e($user['first_name'] . ' ' . $user['last_name']) ?></h5>
                        <p class="text-muted mb-3"><?= e($user['email']) ?></p>
                        <div class="badge-group">
                            <?php if ($user['email_verified']): ?>
                                <span class="badge bg-success">Email Verified</span>
                            <?php else: ?>
                                <span class="badge bg-warning">Email Pending</span>
                            <?php endif; ?>
                            
                            <?php if ($user['phone_verified']): ?>
                                <span class="badge bg-success">Phone Verified</span>
                            <?php else: ?>
                                <span class="badge bg-warning">Phone Pending</span>
                            <?php endif; ?>
                        </div>
                        <hr>
                        <p class="text-muted mb-2">KYC Status</p>
                        <span class="badge bg-<?= $user['kyc_status'] === 'Approved' ? 'success' : ($user['kyc_status'] === 'Pending' ? 'warning' : 'danger') ?>">
                            <?= e($user['kyc_status']) ?>
                        </span>
                        <?php if ($user['kyc_status'] !== 'Approved'): ?>
                            <div class="mt-3">
                                <a href="<?= APP_URL ?>/kyc-upload" class="btn btn-sm btn-primary w-100">
                                    Complete KYC
                                </a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <div class="col-md-8 mb-4">
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-light border-bottom">
                        <h5 class="mb-0">Personal Information</h5>
                    </div>
                    <div class="card-body">
                        <form method="POST" class="needs-validation" novalidate>
                            <input type="hidden" name="action" value="update_profile">
                            
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label for="first_name" class="form-label">First Name</label>
                                    <input type="text" class="form-control" id="first_name" name="first_name" value="<?= e($user['first_name']) ?>" required>
                                </div>
                                <div class="col-md-6">
                                    <label for="last_name" class="form-label">Last Name</label>
                                    <input type="text" class="form-control" id="last_name" name="last_name" value="<?= e($user['last_name']) ?>" required>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="email" class="form-label">Email Address</label>
                                <input type="email" class="form-control" id="email" value="<?= e($user['email']) ?>" disabled>
                                <small class="text-muted">Email cannot be changed</small>
                            </div>
                            
                            <div class="mb-3">
                                <label for="phone" class="form-label">Phone Number</label>
                                <input type="tel" class="form-control" id="phone" name="phone" value="<?= e($user['phone']) ?>" required>
                            </div>
                            
                            <div class="mb-3">
                                <label for="dob" class="form-label">Date of Birth</label>
                                <input type="date" class="form-control" id="dob" value="<?= e($user['date_of_birth'] ?? '') ?>" disabled>
                            </div>
                            
                            <div class="mb-3">
                                <label for="account_type" class="form-label">Account Type</label>
                                <input type="text" class="form-control" id="account_type" value="<?= e($user['account_type']) ?>" disabled>
                            </div>
                            
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-check-circle"></i> Update Profile
                            </button>
                        </form>
                    </div>
                </div>
                
                <div class="card border-0 shadow-sm mt-4">
                    <div class="card-header bg-light border-bottom">
                        <h5 class="mb-0">Security</h5>
                    </div>
                    <div class="card-body">
                        <p class="text-muted mb-3">Manage your account security settings</p>
                        <a href="<?= APP_URL ?>/change-password" class="btn btn-outline-primary">
                            <i class="bi bi-lock"></i> Change Password
                        </a>
                        <a href="<?= APP_URL ?>/login-history" class="btn btn-outline-secondary">
                            <i class="bi bi-clock-history"></i> Login History
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <?php include 'views/components/footer.php'; ?>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
