<?php
// tools/generate_hashes.php — One-time password hash generator
// Run: d:\xamppp\php\php.exe tools/generate_hashes.php
$passwords = [
    'Customer@123' => ['cost' => 12],
    'Admin@1234'   => ['cost' => 12],
];
foreach ($passwords as $pass => $opts) {
    $hash = password_hash($pass, PASSWORD_BCRYPT, $opts);
    echo "Password: $pass\n";
    echo "Hash: $hash\n";
    echo "Verify: " . (password_verify($pass, $hash) ? "OK" : "FAIL") . "\n\n";
}
