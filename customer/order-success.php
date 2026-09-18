<?php
// customer/order-success.php
define('PAGE_TITLE', 'Order Confirmed');
require_once __DIR__ . '/../includes/auth_check.php';

$orderNum = trim($_GET['order'] ?? '');
if (!$orderNum) redirect(APP_URL . '/');

$pdo = getDB();
$stmt = $pdo->prepare("SELECT o.*,u.name AS customer_name FROM orders o LEFT JOIN users u ON u.id=o.user_id WHERE o.order_number=? AND o.user_id=?");
$stmt->execute([$orderNum, $_SESSION[SESSION_USER]['id']]);
$order = $stmt->fetch();
if (!$order) redirect(APP_URL . '/');

$orderItems = $pdo->prepare("SELECT oi.*,p.slug FROM order_items oi LEFT JOIN products p ON p.id=oi.product_id WHERE oi.order_id=?");
$orderItems->execute([$order['id']]);
$orderItems = $orderItems->fetchAll();

require_once __DIR__ . '/../includes/header.php';
?>

<div class="container py-5">
  <div class="success-wrapper">
    <div class="card p-5 text-center" style="max-width:700px;width:100%;margin:0 auto;">
      <div class="success-icon">🎉</div>
      <h1 style="font-family:'Outfit',sans-serif;font-weight:800;font-size:2rem;color:var(--text);margin-bottom:.5rem;">
        Order Confirmed!
      </h1>
      <p style="color:var(--text-muted);font-size:1rem;margin-bottom:2rem;">
        Thank you, <strong style="color:var(--primary-light);"><?= e($order['customer_name']) ?></strong>!
        Your order has been placed successfully.
      </p>

      <div style="background:rgba(99,102,241,.08);border:1px solid rgba(99,102,241,.2);border-radius:12px;padding:1.5rem;margin-bottom:2rem;">
        <div style="font-size:1.4rem;font-weight:800;color:var(--text);font-family:'Outfit',sans-serif;">
          <?= e($order['order_number']) ?>
        </div>
        <div style="font-size:.8rem;color:var(--text-muted);margin-top:.3rem;">Order Number</div>
      </div>

      <div class="row g-3 mb-4 text-start">
        <div class="col-sm-6">
          <div style="background:var(--bg-card2);border-radius:10px;padding:1rem;">
            <div style="font-size:.7rem;color:var(--text-dim);text-transform:uppercase;letter-spacing:.05em;margin-bottom:.3rem;">Order Date</div>
            <div style="color:var(--text);font-weight:600;"><?= date('d M Y, h:i A', strtotime($order['created_at'])) ?></div>
          </div>
        </div>
        <div class="col-sm-6">
          <div style="background:var(--bg-card2);border-radius:10px;padding:1rem;">
            <div style="font-size:.7rem;color:var(--text-dim);text-transform:uppercase;letter-spacing:.05em;margin-bottom:.3rem;">Payment</div>
            <div style="color:var(--text);font-weight:600;"><?= PAYMENT_METHODS[$order['payment_method']] ?? $order['payment_method'] ?></div>
          </div>
        </div>
        <div class="col-sm-6">
          <div style="background:var(--bg-card2);border-radius:10px;padding:1rem;">
            <div style="font-size:.7rem;color:var(--text-dim);text-transform:uppercase;letter-spacing:.05em;margin-bottom:.3rem;">Delivery To</div>
            <div style="color:var(--text);font-weight:600;font-size:.875rem;"><?= e("{$order['shipping_name']}, {$order['shipping_city']}, {$order['shipping_pincode']}") ?></div>
          </div>
        </div>
        <div class="col-sm-6">
          <div style="background:var(--bg-card2);border-radius:10px;padding:1rem;">
            <div style="font-size:.7rem;color:var(--text-dim);text-transform:uppercase;letter-spacing:.05em;margin-bottom:.3rem;">Order Total</div>
            <div style="color:var(--text);font-weight:800;font-size:1.2rem;font-family:'Outfit',sans-serif;"><?= formatPrice($order['total']) ?></div>
          </div>
        </div>
      </div>

      <!-- Items ordered -->
      <div class="text-start mb-4">
        <h6 style="color:var(--text);font-weight:700;margin-bottom:1rem;">Items Ordered</h6>
        <?php foreach($orderItems as $oi): ?>
        <div class="d-flex align-items-center gap-3 mb-2 p-2" style="background:var(--bg-card2);border-radius:8px;">
          <div style="width:40px;height:40px;background:var(--bg-card);border-radius:6px;display:flex;align-items:center;justify-content:center;font-size:1.2rem;flex-shrink:0;">📦</div>
          <div class="flex-grow-1">
            <div style="color:var(--text);font-size:.875rem;font-weight:500;"><?= e($oi['product_name']) ?></div>
            <div style="color:var(--text-muted);font-size:.78rem;">Qty: <?= $oi['quantity'] ?></div>
          </div>
          <div style="color:var(--text);font-weight:600;font-size:.875rem;"><?= formatPrice($oi['total']) ?></div>
        </div>
        <?php endforeach; ?>
      </div>

      <div class="d-flex gap-3 justify-content-center flex-wrap">
        <a href="<?= APP_URL ?>/customer/orders.php" class="btn btn-outline-primary px-4">
          <i class="bi bi-bag-check me-2"></i>View My Orders
        </a>
        <a href="<?= APP_URL ?>/" class="btn btn-primary px-4">
          <i class="bi bi-house me-2"></i>Continue Shopping
        </a>
      </div>
    </div>
  </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
