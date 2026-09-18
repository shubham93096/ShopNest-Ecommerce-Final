<?php
// api/search.php — Product Search Autocomplete
require_once __DIR__ . '/../includes/functions.php';
header('Content-Type: application/json');

$q = trim($_GET['q'] ?? '');
if (strlen($q) < 2) { echo json_encode(['products'=>[]]); exit; }

$pdo  = getDB();
$stmt = $pdo->prepare("
    SELECT p.id,p.name,p.slug,p.price,p.sale_price,p.images
    FROM products p
    WHERE p.status=1 AND p.name LIKE ?
    LIMIT 8
");
$stmt->execute(["%$q%"]);
$products = $stmt->fetchAll();

$results = array_map(function($p) {
    return [
        'id'    => $p['id'],
        'name'  => $p['name'],
        'slug'  => $p['slug'],
        'price' => formatPrice(getEffectivePrice($p)),
        'image' => getProductPrimaryImage($p),
    ];
}, $products);

echo json_encode(['products' => $results]);
