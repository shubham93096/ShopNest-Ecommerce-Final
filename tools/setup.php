<?php
/**
 * tools/setup.php — One-time database setup & fix tool
 * Access via: http://localhost/aws-ecommerce/tools/setup.php
 * DELETE THIS FILE after running!
 */
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/constants.php';
if (session_status() === PHP_SESSION_NONE) session_start();

$pdo = getDB();
$log = [];
$errors = [];

function logMsg(string $msg, array &$log): void {
    $log[] = $msg;
}

try {
    // ── 1. Fix / insert demo customer ──────────────────────────
    $customerHash = password_hash('Customer@123', PASSWORD_BCRYPT, ['cost' => 12]);
    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->execute(['rahul@example.com']);
    if ($stmt->fetch()) {
        $pdo->prepare("UPDATE users SET password = ? WHERE email = ?")
            ->execute([$customerHash, 'rahul@example.com']);
        logMsg("✅ Demo customer password updated (rahul@example.com / Customer@123)", $log);
    } else {
        $pdo->prepare("INSERT INTO users (name,email,phone,password,city,state,pincode,status,email_verified,created_at) VALUES (?,?,?,?,?,?,?,1,1,NOW())")
            ->execute(['Rahul Sharma','rahul@example.com','9876543210',$customerHash,'Mumbai','Maharashtra','400001']);
        logMsg("✅ Demo customer created (rahul@example.com / Customer@123)", $log);
    }

    // ── 2. Fix / insert demo admin ─────────────────────────────
    $adminHash = password_hash('Admin@1234', PASSWORD_BCRYPT, ['cost' => 12]);
    $chk = $pdo->prepare("SELECT id FROM admins WHERE email = ?");
    $chk->execute(['admin@shopnest.com']);
    if ($chk->fetch()) {
        $pdo->prepare("UPDATE admins SET password = ? WHERE email = ?")
            ->execute([$adminHash, 'admin@shopnest.com']);
        logMsg("✅ Admin password updated (admin@shopnest.com / Admin@1234)", $log);
    } else {
        $pdo->prepare("INSERT INTO admins (name,email,password,role,status,created_at) VALUES (?,?,?,?,1,NOW())")
            ->execute(['Super Admin','admin@shopnest.com',$adminHash,'super_admin']);
        logMsg("✅ Admin account created (admin@shopnest.com / Admin@1234)", $log);
    }

    // ── 3. Add missing columns if they don't exist ─────────────
    $cols = $pdo->query("SHOW COLUMNS FROM users")->fetchAll(PDO::FETCH_COLUMN);
    if (!in_array('reset_token', $cols)) {
        $pdo->exec("ALTER TABLE users ADD COLUMN reset_token VARCHAR(100) DEFAULT NULL, ADD COLUMN reset_expires DATETIME DEFAULT NULL");
        logMsg("✅ Added reset_token/reset_expires columns to users", $log);
    } else {
        logMsg("✔ reset_token column already exists", $log);
    }

    // ── 4. Ensure uploads dirs exist ───────────────────────────
    $dirs = [
        __DIR__ . '/../uploads/products',
        __DIR__ . '/../logs',
    ];
    foreach ($dirs as $dir) {
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
            logMsg("✅ Created directory: $dir", $log);
        } else {
            logMsg("✔ Directory exists: $dir", $log);
        }
    }

    // ── 5. Quick DB stats ──────────────────────────────────────
    $counts = [
        'Users'      => $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn(),
        'Admins'     => $pdo->query("SELECT COUNT(*) FROM admins")->fetchColumn(),
        'Products'   => $pdo->query("SELECT COUNT(*) FROM products")->fetchColumn(),
        'Categories' => $pdo->query("SELECT COUNT(*) FROM categories")->fetchColumn(),
        'Orders'     => $pdo->query("SELECT COUNT(*) FROM orders")->fetchColumn(),
    ];
    logMsg("", $log);
    logMsg("📊 DB Stats:", $log);
    foreach ($counts as $t => $c) {
        logMsg("   • $t: $c", $log);
    }

    // ── 6. Verify passwords ────────────────────────────────────
    logMsg("", $log);
    logMsg("🔐 Password Verification:", $log);
    $custRow = $pdo->query("SELECT password FROM users WHERE email='rahul@example.com'")->fetch();
    $admRow  = $pdo->query("SELECT password FROM admins WHERE email='admin@shopnest.com'")->fetch();
    logMsg("   Customer: " . (password_verify('Customer@123', $custRow['password'] ?? '') ? "✅ VALID" : "❌ FAIL"), $log);
    logMsg("   Admin:    " . (password_verify('Admin@1234',   $admRow['password']  ?? '') ? "✅ VALID" : "❌ FAIL"), $log);

} catch (Exception $e) {
    $errors[] = "❌ Error: " . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>ShopNest — Database Setup</title>
  <style>
    * { margin: 0; padding: 0; box-sizing: border-box; }
    body { font-family: 'Segoe UI', sans-serif; background: #0f0f23; color: #e2e8f0; min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 2rem; }
    .card { background: #1a1a2e; border: 1px solid rgba(99,102,241,.3); border-radius: 16px; padding: 2rem; max-width: 700px; width: 100%; }
    h1 { font-size: 1.5rem; color: #a5b4fc; margin-bottom: 1.5rem; }
    .log { background: #0d0d1a; border-radius: 8px; padding: 1.2rem; font-family: monospace; font-size: .875rem; line-height: 1.8; white-space: pre-wrap; margin-bottom: 1.5rem; border: 1px solid rgba(99,102,241,.2); }
    .error { color: #fca5a5; background: rgba(239,68,68,.1); border: 1px solid rgba(239,68,68,.3); border-radius: 8px; padding: 1rem; margin-bottom: 1rem; font-family: monospace; }
    .links { display: flex; flex-wrap: wrap; gap: .75rem; }
    .btn { display: inline-block; padding: .6rem 1.2rem; border-radius: 8px; text-decoration: none; font-weight: 600; font-size: .875rem; transition: opacity .2s; }
    .btn:hover { opacity: .85; }
    .btn-primary { background: linear-gradient(135deg,#6366f1,#a855f7); color: #fff; }
    .btn-outline { background: transparent; color: #a5b4fc; border: 1px solid rgba(99,102,241,.5); }
    .warn { color: #fcd34d; font-size: .8rem; margin-top: 1rem; padding: .75rem; background: rgba(245,158,11,.1); border: 1px solid rgba(245,158,11,.3); border-radius: 8px; }
  </style>
</head>
<body>
<div class="card">
  <h1>🛠 ShopNest — Database Setup & Fix</h1>
  <?php foreach ($errors as $e): ?>
  <div class="error"><?= htmlspecialchars($e) ?></div>
  <?php endforeach; ?>
  <?php if (empty($errors)): ?>
  <div style="color:#6ee7b7;font-weight:600;margin-bottom:1rem;">✅ Setup completed successfully!</div>
  <?php endif; ?>
  <div class="log"><?= implode("\n", array_map('htmlspecialchars', $log)) ?></div>
  <div class="links">
    <a href="http://localhost/aws-ecommerce/" class="btn btn-primary">🏠 Go to Store</a>
    <a href="http://localhost/aws-ecommerce/customer/login.php" class="btn btn-outline">👤 Customer Login</a>
    <a href="http://localhost/aws-ecommerce/admin/login.php" class="btn btn-outline">🔐 Admin Login</a>
    <a href="http://localhost/aws-ecommerce/products/index.php" class="btn btn-outline">🛒 Products</a>
  </div>
  <div class="warn">⚠️ <strong>Security Warning:</strong> Delete <code>tools/setup.php</code> after setup is complete!</div>
</div>
</body>
</html>
