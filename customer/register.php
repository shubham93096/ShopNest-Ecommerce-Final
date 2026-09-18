<?php
// customer/register.php
define('PAGE_TITLE', 'Register');
require_once __DIR__ . '/../includes/functions.php';

if (isLoggedIn()) redirect(APP_URL . '/customer/profile.php');

$errors = [];
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    $name     = trim($_POST['name'] ?? '');
    $email    = trim($_POST['email'] ?? '');
    $phone    = trim($_POST['phone'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm  = $_POST['confirm_password'] ?? '';

    if (empty($name))     $errors[] = 'Full name is required.';
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Valid email is required.';
    if (strlen($password) < 6) $errors[] = 'Password must be at least 6 characters.';
    if ($password !== $confirm) $errors[] = 'Passwords do not match.';

    if (empty($errors)) {
        $pdo = getDB();
        $exists = $pdo->prepare("SELECT id FROM users WHERE email=?");
        $exists->execute([$email]);
        if ($exists->fetch()) {
            $errors[] = 'An account with this email already exists.';
        } else {
            $hash = password_hash($password, PASSWORD_BCRYPT, ['cost'=>12]);
            $stmt = $pdo->prepare("INSERT INTO users (name,email,phone,password,status,email_verified,created_at) VALUES (?,?,?,?,1,0,NOW())");
            $stmt->execute([$name, $email, $phone, $hash]);
            $userId = $pdo->lastInsertId();

            $_SESSION[SESSION_USER] = ['id'=>$userId,'name'=>$name,'email'=>$email];
            session_regenerate_id(true);
            setFlash('success', 'Welcome to ShopNest, ' . $name . '! 🎉');
            redirect(APP_URL . '/');
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Register | ShopNest</title>
  <link rel="icon" href="<?= APP_URL ?>/assets/images/logo.png" type="image/png">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
  <link rel="stylesheet" href="<?= APP_URL ?>/assets/css/style.css">
</head>
<body>
<div class="auth-wrapper">
  <div class="auth-card" style="max-width:500px;">
    <div class="auth-logo">
      <a href="<?= APP_URL ?>/">ShopNest</a>
    </div>
    <h1 class="auth-title">Create account</h1>
    <p class="auth-subtitle">Join thousands of happy shoppers today</p>

    <?php if($errors): ?>
    <div class="alert alert-danger">
      <?php foreach($errors as $err): ?>
      <div><i class="bi bi-x-circle me-1"></i><?= e($err) ?></div>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <form method="POST" novalidate>
      <?= csrfInput() ?>
      <div class="row g-3">
        <div class="col-12">
          <label class="form-label">Full Name *</label>
          <div class="input-group">
            <span class="input-group-text" style="background:var(--bg-card2);border-color:var(--border);color:var(--text-muted);"><i class="bi bi-person"></i></span>
            <input type="text" name="name" class="form-control" placeholder="John Doe" value="<?= e($_POST['name'] ?? '') ?>" required>
          </div>
        </div>
        <div class="col-12">
          <label class="form-label">Email Address *</label>
          <div class="input-group">
            <span class="input-group-text" style="background:var(--bg-card2);border-color:var(--border);color:var(--text-muted);"><i class="bi bi-envelope"></i></span>
            <input type="email" name="email" class="form-control" placeholder="you@example.com" value="<?= e($_POST['email'] ?? '') ?>" required>
          </div>
        </div>
        <div class="col-12">
          <label class="form-label">Phone Number</label>
          <div class="input-group">
            <span class="input-group-text" style="background:var(--bg-card2);border-color:var(--border);color:var(--text-muted);"><i class="bi bi-phone"></i></span>
            <input type="tel" name="phone" class="form-control" placeholder="9876543210" value="<?= e($_POST['phone'] ?? '') ?>">
          </div>
        </div>
        <div class="col-md-6">
          <label class="form-label">Password *</label>
          <input type="password" id="password" name="password" class="form-control" placeholder="Min. 6 characters" required>
        </div>
        <div class="col-md-6">
          <label class="form-label">Confirm Password *</label>
          <input type="password" name="confirm_password" class="form-control" placeholder="Repeat password" required>
        </div>
        <div class="col-12">
          <div class="form-check">
            <input class="form-check-input" type="checkbox" id="terms" required>
            <label class="form-check-label" for="terms" style="color:var(--text-muted);font-size:.875rem;">
              I agree to the <a href="#" style="color:var(--primary-light);">Terms of Service</a> and <a href="#" style="color:var(--primary-light);">Privacy Policy</a>
            </label>
          </div>
        </div>
        <div class="col-12">
          <button type="submit" class="btn btn-primary w-100 btn-lg">
            <i class="bi bi-person-plus me-2"></i>Create Account
          </button>
        </div>
      </div>
    </form>

    <div class="auth-divider">or</div>
    <div class="text-center">
      <p style="color:var(--text-muted);font-size:.9rem;">
        Already have an account?
        <a href="<?= APP_URL ?>/customer/login.php" style="color:var(--primary-light);font-weight:600;">Sign in</a>
      </p>
    </div>
  </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
