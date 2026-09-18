<?php
// admin/login.php
require_once __DIR__ . '/../includes/functions.php';

if (isAdminLoggedIn()) redirect(APP_URL . '/admin/index.php');

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    $attempts = $_SESSION['admin_attempts'] ?? 0;
    if ($attempts >= 5) { $error = 'Too many attempts. Please wait.'; }
    else {
        $email = trim($_POST['email'] ?? '');
        $pass  = $_POST['password'] ?? '';
        $pdo   = getDB();
        $stmt  = $pdo->prepare("SELECT * FROM admins WHERE email=? AND status=1");
        $stmt->execute([$email]);
        $admin = $stmt->fetch();

        if ($admin && password_verify($pass, $admin['password'])) {
            $_SESSION[SESSION_ADMIN] = ['id'=>$admin['id'],'name'=>$admin['name'],'email'=>$admin['email'],'role'=>$admin['role']];
            $_SESSION['admin_attempts'] = 0;
            session_regenerate_id(true);
            $pdo->prepare("UPDATE admins SET last_login=NOW() WHERE id=?")->execute([$admin['id']]);
            redirect(APP_URL . '/admin/index.php');
        } else {
            $_SESSION['admin_attempts'] = $attempts + 1;
            $error = 'Invalid credentials.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Admin Login | ShopNest</title>
  <link rel="icon" href="<?= APP_URL ?>/assets/images/logo.png" type="image/png">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
  <link rel="stylesheet" href="<?= APP_URL ?>/assets/css/admin.css">
  <style>
    body { min-height:100vh; display:flex; align-items:center; justify-content:center; background:var(--admin-bg); }
    body::before { content:''; position:fixed; inset:0; background:radial-gradient(ellipse at 20% 30%,rgba(99,102,241,.2),transparent 60%),radial-gradient(ellipse at 80% 70%,rgba(168,85,247,.15),transparent 60%); pointer-events:none; }
  </style>
</head>
<body>
<div class="position-relative" style="z-index:1;width:100%;max-width:420px;padding:1rem;">
  <div style="text-align:center;margin-bottom:2rem;">
    <a href="<?= APP_URL ?>/" style="font-family:'Outfit',sans-serif;font-weight:800;font-size:2rem;background:linear-gradient(135deg,#6366f1,#a855f7);-webkit-background-clip:text;-webkit-text-fill-color:transparent;background-clip:text;">ShopNest</a>
    <div style="font-size:.8rem;background:rgba(99,102,241,.15);color:#818cf8;padding:.2rem .75rem;border-radius:4px;display:inline-block;margin-top:.5rem;border:1px solid rgba(99,102,241,.3);">Admin Panel</div>
  </div>

  <div style="background:var(--admin-card);border:1px solid var(--admin-border);border-radius:16px;padding:2rem;">
    <h1 style="font-family:'Outfit',sans-serif;font-weight:700;font-size:1.4rem;color:var(--text);margin-bottom:.3rem;">Welcome back</h1>
    <p style="color:var(--text-muted);font-size:.875rem;margin-bottom:1.5rem;">Sign in to the admin dashboard</p>

    <?php if($error): ?>
    <div class="alert alert-danger"><i class="bi bi-exclamation-triangle me-2"></i><?= e($error) ?></div>
    <?php endif; ?>

    <form method="POST">
      <?= csrfInput() ?>
      <div class="mb-3">
        <label class="form-label">Email Address</label>
        <input type="email" name="email" class="form-control" placeholder="admin@shopnest.com" value="<?= e($_POST['email']??'') ?>" required autofocus>
      </div>
      <div class="mb-4">
        <label class="form-label">Password</label>
        <input type="password" name="password" class="form-control" placeholder="••••••••" required>

      </div>
      <button type="submit" class="btn-admin-primary w-100 d-flex justify-content-center py-2" style="font-size:.95rem;">
        <i class="bi bi-shield-lock me-2"></i>Sign In
      </button>
    </form>

    <div class="text-center mt-3">
      <a href="<?= APP_URL ?>/" style="color:var(--text-dim);font-size:.8rem;"><i class="bi bi-arrow-left me-1"></i>Back to Store</a>
    </div>
  </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
