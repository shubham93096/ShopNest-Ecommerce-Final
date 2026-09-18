<?php
// customer/login.php
define('PAGE_TITLE', 'Login');
require_once __DIR__ . '/../includes/functions.php';

if (isLoggedIn()) redirect(APP_URL . '/customer/profile.php');

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($email) || empty($password)) {
        $error = 'Please fill in all fields.';
    } else {
        $pdo  = getDB();
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ? AND status = 1");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            $_SESSION[SESSION_USER] = [
                'id'    => $user['id'],
                'name'  => $user['name'],
                'email' => $user['email'],
            ];
            session_regenerate_id(true);

            $redirect = $_SESSION['redirect_after_login'] ?? (APP_URL . '/customer/profile.php');
            unset($_SESSION['redirect_after_login']);
            redirect($redirect);
        } else {
            $error = 'Invalid email or password.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Login | ShopNest</title>
  <link rel="icon" href="<?= APP_URL ?>/assets/images/logo.png" type="image/png">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
  <link rel="stylesheet" href="<?= APP_URL ?>/assets/css/style.css">
</head>
<body>
<div class="auth-wrapper">
  <div class="auth-card">
    <div class="auth-logo">
      <a href="<?= APP_URL ?>/">ShopNest</a>
    </div>
    <h1 class="auth-title">Welcome back</h1>
    <p class="auth-subtitle">Sign in to your account to continue shopping</p>

    <?php if($error): ?>
    <div class="alert alert-danger"><i class="bi bi-exclamation-circle me-2"></i><?= e($error) ?></div>
    <?php endif; ?>

    <form method="POST" novalidate>
      <?= csrfInput() ?>
      <div class="mb-3">
        <label class="form-label" for="email">Email Address</label>
        <div class="input-group">
          <span class="input-group-text" style="background:var(--bg-card2);border-color:var(--border);color:var(--text-muted);">
            <i class="bi bi-envelope"></i>
          </span>
          <input type="email" id="email" name="email" class="form-control" placeholder="you@example.com"
                 value="<?= e($_POST['email'] ?? '') ?>" required autofocus>
        </div>
      </div>
      <div class="mb-3">
        <div class="d-flex justify-content-between">
          <label class="form-label" for="password">Password</label>
          <a href="<?= APP_URL ?>/customer/forgot-password.php" class="form-label" style="color:var(--primary-light);">Forgot password?</a>
        </div>
        <div class="input-group">
          <span class="input-group-text" style="background:var(--bg-card2);border-color:var(--border);color:var(--text-muted);">
            <i class="bi bi-lock"></i>
          </span>
          <input type="password" id="password" name="password" class="form-control" placeholder="Enter your password" required>
          <button type="button" class="input-group-text" style="background:var(--bg-card2);border-color:var(--border);color:var(--text-muted);cursor:pointer;" onclick="togglePwd('password')">
            <i class="bi bi-eye" id="pwd-icon"></i>
          </button>
        </div>
      </div>
      <div class="mb-4">
        <div class="form-check">
          <input class="form-check-input" type="checkbox" id="remember" name="remember">
          <label class="form-check-label" for="remember" style="color:var(--text-muted);">Remember me</label>
        </div>
      </div>
      <button type="submit" class="btn btn-primary w-100 btn-lg">
        <i class="bi bi-box-arrow-in-right me-2"></i>Sign In
      </button>
    </form>

    <div class="auth-divider">or</div>

    <div class="text-center">
      <p style="color:var(--text-muted);font-size:.9rem;">
        Don't have an account?
        <a href="<?= APP_URL ?>/customer/register.php" style="color:var(--primary-light);font-weight:600;">Create one free</a>
      </p>
    </div>


  </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
function togglePwd(id) {
  const i = document.getElementById(id);
  const icon = document.getElementById('pwd-icon');
  if (i.type === 'password') { i.type = 'text'; icon.className = 'bi bi-eye-slash'; }
  else { i.type = 'password'; icon.className = 'bi bi-eye'; }
}
</script>
</body>
</html>
