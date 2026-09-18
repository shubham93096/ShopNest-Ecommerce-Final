<?php
// customer/forgot-password.php
define('PAGE_TITLE', 'Forgot Password');
require_once __DIR__ . '/../includes/functions.php';

if (isLoggedIn()) redirect(APP_URL . '/');

$message = $error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    $email = trim($_POST['email'] ?? '');
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } else {
        $pdo = getDB();
        $stmt = $pdo->prepare("SELECT id,name FROM users WHERE email=? AND status=1");
        $stmt->execute([$email]);
        $user = $stmt->fetch();
        if ($user) {
            $token   = bin2hex(random_bytes(32));
            $expires = date('Y-m-d H:i:s', strtotime('+1 hour'));
            $pdo->prepare("UPDATE users SET reset_token=?,reset_expires=? WHERE id=?")->execute([$token,$expires,$user['id']]);
            $link = APP_URL . '/customer/reset-password.php?token=' . $token;
            // In production: send email via AWS SES. For dev, log it.
            $logFile = __DIR__ . '/../logs/password_reset.log';
            file_put_contents($logFile, date('[Y-m-d H:i:s]') . " Reset link for {$email}: {$link}\n", FILE_APPEND);
            sendSNSNotification("Password reset requested for {$email}. Link: {$link}", 'Password Reset');
        }
        // Always show success to prevent email enumeration
        $message = "If that email is registered, you'll receive a reset link. Check logs/password_reset.log for development.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Forgot Password | ShopNest</title>
  <link rel="icon" href="<?= APP_URL ?>/assets/images/logo.png" type="image/png">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
  <link rel="stylesheet" href="<?= APP_URL ?>/assets/css/style.css">
</head>
<body>
<div class="auth-wrapper">
  <div class="auth-card">
    <div class="auth-logo"><a href="<?= APP_URL ?>/">ShopNest</a></div>
    <div class="text-center mb-3" style="font-size:3rem;">🔐</div>
    <h1 class="auth-title text-center">Forgot Password?</h1>
    <p class="auth-subtitle text-center">Enter your email and we'll send you a reset link.</p>

    <?php if($message): ?>
    <div class="alert alert-success"><i class="bi bi-check-circle me-2"></i><?= e($message) ?></div>
    <?php endif; ?>
    <?php if($error): ?>
    <div class="alert alert-danger"><i class="bi bi-exclamation-circle me-2"></i><?= e($error) ?></div>
    <?php endif; ?>

    <?php if(!$message): ?>
    <form method="POST">
      <?= csrfInput() ?>
      <div class="mb-3">
        <label class="form-label">Email Address</label>
        <div class="input-group">
          <span class="input-group-text" style="background:var(--bg-card2);border-color:var(--border);color:var(--text-muted);"><i class="bi bi-envelope"></i></span>
          <input type="email" name="email" class="form-control" placeholder="you@example.com" required autofocus>
        </div>
      </div>
      <button type="submit" class="btn btn-primary w-100 btn-lg">
        <i class="bi bi-send me-2"></i>Send Reset Link
      </button>
    </form>
    <?php endif; ?>

    <div class="text-center mt-3">
      <a href="<?= APP_URL ?>/customer/login.php" style="color:var(--text-muted);font-size:.875rem;">
        <i class="bi bi-arrow-left me-1"></i>Back to Login
      </a>
    </div>
  </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
