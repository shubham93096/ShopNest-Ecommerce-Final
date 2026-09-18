<?php
// api/cart.php — AJAX Cart Handler
require_once __DIR__ . '/../includes/functions.php';
header('Content-Type: application/json');

if (!isLoggedIn()) {
    echo json_encode(['success'=>false,'redirect'=>APP_URL.'/customer/login.php']);
    exit;
}

$data      = json_decode(file_get_contents('php://input'), true) ?? [];
$action    = $data['action'] ?? '';
$productId = (int)($data['product_id'] ?? 0);
$qty       = max(1, min(99, (int)($data['quantity'] ?? 1)));
$uid       = $_SESSION[SESSION_USER]['id'];
$pdo       = getDB();

function cartResponse(PDO $pdo, int $uid, array $extra=[]): string {
    $cnt   = (int)$pdo->prepare("SELECT COALESCE(SUM(quantity),0) FROM cart WHERE user_id=?")->execute([$uid]) ? 0:0;
    $cStmt = $pdo->prepare("SELECT COALESCE(SUM(quantity),0) FROM cart WHERE user_id=?");
    $cStmt->execute([$uid]);
    $cnt = (int)$cStmt->fetchColumn();

    $tStmt = $pdo->prepare("SELECT COALESCE(SUM(c.quantity * COALESCE(p.sale_price,p.price)),0) FROM cart c JOIN products p ON p.id=c.product_id WHERE c.user_id=?");
    $tStmt->execute([$uid]);
    $cartTotal = (float)$tStmt->fetchColumn();

    return json_encode(array_merge(['success'=>true,'cart_count'=>$cnt,'cart_total'=>formatPrice($cartTotal)], $extra));
}

switch ($action) {
    case 'add':
        if (!$productId) { echo json_encode(['success'=>false,'message'=>'Invalid product.']); exit; }
        $pStmt = $pdo->prepare("SELECT * FROM products WHERE id=? AND status=1");
        $pStmt->execute([$productId]);
        $product = $pStmt->fetch();
        if (!$product) { echo json_encode(['success'=>false,'message'=>'Product not found.']); exit; }
        if ($product['stock'] < 1) { echo json_encode(['success'=>false,'message'=>'This product is out of stock.']); exit; }

        $existing = $pdo->prepare("SELECT quantity FROM cart WHERE user_id=? AND product_id=?");
        $existing->execute([$uid,$productId]);
        $cur = $existing->fetchColumn();

        if ($cur !== false) {
            $newQty = min($product['stock'], $cur + $qty);
            $pdo->prepare("UPDATE cart SET quantity=?,updated_at=NOW() WHERE user_id=? AND product_id=?")->execute([$newQty,$uid,$productId]);
        } else {
            $pdo->prepare("INSERT INTO cart (user_id,product_id,quantity,created_at) VALUES (?,?,?,NOW())")->execute([$uid,$productId,min($product['stock'],$qty)]);
        }
        echo cartResponse($pdo, $uid, ['message'=>'Added to cart!']);
        break;

    case 'update':
        if (!$productId) { echo json_encode(['success'=>false,'message'=>'Invalid product.']); exit; }
        $pdo->prepare("UPDATE cart SET quantity=?,updated_at=NOW() WHERE user_id=? AND product_id=?")->execute([$qty,$uid,$productId]);

        $itStmt = $pdo->prepare("SELECT quantity,COALESCE(sale_price,price) AS price FROM cart c JOIN products p ON p.id=c.product_id WHERE c.user_id=? AND c.product_id=?");
        $itStmt->execute([$uid,$productId]);
        $it = $itStmt->fetch();
        $itemTotal = $it ? formatPrice($it['price'] * $it['quantity']) : '';

        echo cartResponse($pdo, $uid, ['item_total'=>$itemTotal]);
        break;

    case 'remove':
        if (!$productId) { echo json_encode(['success'=>false,'message'=>'Invalid product.']); exit; }
        $pdo->prepare("DELETE FROM cart WHERE user_id=? AND product_id=?")->execute([$uid,$productId]);
        echo cartResponse($pdo, $uid, ['message'=>'Item removed.']);
        break;

    default:
        echo json_encode(['success'=>false,'message'=>'Invalid action.']);
}
