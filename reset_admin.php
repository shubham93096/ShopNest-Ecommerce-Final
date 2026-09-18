<?php
// ============================================================
//  reset_admin.php — Temporary Admin Password Reset Utility
// ============================================================
require_once __DIR__ . '/config/database.php';

try {
    $pdo = getDB();
    $new_password = 'admin123';
    $hash = password_hash($new_password, PASSWORD_BCRYPT, ['cost' => 12]);
    
    // Check if the admin exists
    $stmt = $pdo->prepare("SELECT id FROM admins WHERE email = 'admin@shopnest.com'");
    $stmt->execute();
    $admin = $stmt->fetch();
    
    if ($admin) {
        $update = $pdo->prepare("UPDATE admins SET password = ? WHERE email = 'admin@shopnest.com'");
        $update->execute([$hash]);
        echo "<h1>Success: Default admin password updated!</h1>";
    } else {
        $insert = $pdo->prepare("INSERT INTO admins (name, email, password, role) VALUES ('Super Admin', 'admin@shopnest.com', ?, 'super_admin')");
        $insert->execute([$hash]);
        echo "<h1>Success: Default admin created!</h1>";
    }
    
    echo "<p>Email: <strong>admin@shopnest.com</strong></p>";
    echo "<p>Password: <strong>admin123</strong></p>";
    echo "<p>Hash used: <code>" . htmlspecialchars($hash) . "</code></p>";
    echo "<p><a href='admin/login.php'>Go to Admin Login</a></p>";
} catch (Exception $e) {
    echo "<h1>Error resetting admin password: " . htmlspecialchars($e->getMessage()) . "</h1>";
}
?>
