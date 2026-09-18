<?php
// customer/reset-password.php
define('PAGE_TITLE', 'Reset Password');
require_once __DIR__ . '/../includes/functions.php';

$token = trim($_GET['token'] ?? '');
$pdo   = getDB();
$user  = null;

if ($token) {
    $stmt = $pdo->prepare("SELECT id,name,email FROM users WHERE reset_token=? AND reset_expires > NOW() AND status=1");
    $stmt->execute([$token]);
    $user = $stmt->fetch();
}

$error = $success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    $postToken = $_POST['token'] ?? '';
    $stmt2 = $pdo->prepare("SELECT id FROM users WHERE reset_token=? AND reset_expires > NOW()");
    $stmt2->execute([$postToken]);
    $u = $stmt2->fetch();

    if (!$u) {
        $error = 'Reset link expired or invalid. Please request a new one.';
    } else {
        $pass = $_POST['password'] ?? '';
        $conf = $_POST['confirm'] ?? '';
        if (strlen($pass) < 6) $error = 'Password must be at least 6 characters.';
        elseif ($pass !== $conf) $error = 'Passwords do not match.';
        else {
            $hash = password_hash($pass, PASSWORD_BCRYPT, ['cost'=>12]);
            $pdo->prepare("UPDATE users SET password=?,reset_token=NULL,reset_expires=NULL WHERE id=?")->execute([$hash, $u['id']]);
            $success = 'Password reset successfully! You can now log in.';
            $user = null;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Reset Password | ShopNest</title>
  <link rel="icon" href="<?= APP_URL ?>/assets/images/logo.png" type="image/png">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
  <link rel="stylesheet" href="<?= APP_URL ?>/assets/css/style.css">
</head>
<body>
<div class="auth-wrapper">
  <div class="auth-card">
    <div class="auth-logo"><a href="<?= APP_URL ?>/">ShopNest</a></div>
    <h1 class="auth-title">Reset Password</h1>

    <?php if($success): ?>
    <div class="alert alert-success"><i class="bi bi-check-circle me-2"></i><?= e($success) ?></div>
    <a href="<?= APP_URL ?>/customer/login.php" class="btn btn-primary w-100">Go to Login</a>

    <?php elseif(!$user): ?>
    <div class="alert alert-danger"><i class="bi bi-x-circle me-2"></i>Invalid or expired reset link.</div>
    <a href="<?= APP_URL ?>/customer/forgot-password.php" class="btn btn-primary w-100">Request New Link</a>

    <?php else: ?>
    <?php if($error): ?><div class="alert alert-danger"><?= e($error) ?></div><?php endif; ?>
    <p class="auth-subtitle">Hello <strong style="color:var(--primary-light);"><?= e($user['name']) ?></strong>, set your new password below.</p>
    <form method="POST">
      <?= csrfInput() ?>
      <input type="hidden" name="token" value="<?= e($token) ?>">
      <div class="mb-3">
        <label class="form-label">New Password</label>
        <input type="password" id="password" name="password" class="form-control" placeholder="Min. 6 characters" required>
      </div>
      <div class="mb-4">
        <label class="form-label">Confirm Password</label>
        <input type="password" name="confirm" class="form-control" placeholder="Repeat new password" required>
      </div>
      <button type="submit" class="btn btn-primary w-100 btn-lg">
        <i class="bi bi-shield-check me-2"></i>Reset Password
      </button>
    </form>
    <?php endif; ?>
  </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
