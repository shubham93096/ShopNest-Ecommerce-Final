<?php
// api/wishlist.php — AJAX Wishlist Toggle
require_once __DIR__ . '/../includes/functions.php';
header('Content-Type: application/json');

if (!isLoggedIn()) {
    echo json_encode(['success'=>false,'redirect'=>APP_URL.'/customer/login.php']);
    exit;
}

$data      = json_decode(file_get_contents('php://input'), true) ?? [];
$productId = (int)($data['product_id'] ?? 0);
$uid       = $_SESSION[SESSION_USER]['id'];
$pdo       = getDB();

if (!$productId) { echo json_encode(['success'=>false,'message'=>'Invalid product.']); exit; }

$exists = $pdo->prepare("SELECT id FROM wishlist WHERE user_id=? AND product_id=?");
$exists->execute([$uid,$productId]);

if ($exists->fetch()) {
    $pdo->prepare("DELETE FROM wishlist WHERE user_id=? AND product_id=?")->execute([$uid,$productId]);
    echo json_encode(['success'=>true,'in_wishlist'=>false,'message'=>'Removed from wishlist.']);
} else {
    $pdo->prepare("INSERT INTO wishlist (user_id,product_id,created_at) VALUES (?,?,NOW())")->execute([$uid,$productId]);
    echo json_encode(['success'=>true,'in_wishlist'=>true,'message'=>'Added to wishlist!']);
}
