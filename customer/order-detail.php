<?php
// customer/order-detail.php
define('PAGE_TITLE', 'Order Detail');
require_once __DIR__ . '/../includes/auth_check.php';

$pdo = getDB();
$id  = (int)($_GET['id'] ?? 0);
if (!$id) redirect(APP_URL . '/customer/orders.php');

$stmt = $pdo->prepare("SELECT * FROM orders WHERE id=? AND user_id=?");
$stmt->execute([$id, $_SESSION[SESSION_USER]['id']]);
$order = $stmt->fetch();
if (!$order) redirect(APP_URL . '/customer/orders.php');

$items = $pdo->prepare("SELECT oi.*,p.slug FROM order_items oi LEFT JOIN products p ON p.id=oi.product_id WHERE oi.order_id=?");
$items->execute([$order['id']]);
$items = $items->fetchAll();

$payment = $pdo->prepare("SELECT * FROM payments WHERE order_id=? ORDER BY id DESC LIMIT 1");
$payment->execute([$order['id']]);
$payment = $payment->fetch();

require_once __DIR__ . '/../includes/header.php';
?>

<div class="container py-4">
  <div class="breadcrumb-wrap">
    <nav><ol class="breadcrumb">
      <li class="breadcrumb-item"><a href="<?= APP_URL ?>/">Home</a></li>
      <li class="breadcrumb-item"><a href="<?= APP_URL ?>/customer/orders.php">My Orders</a></li>
      <li class="breadcrumb-item active"><?= e($order['order_number']) ?></li>
    </ol></nav>
  </div>

  <div class="d-flex align-items-center justify-content-between mb-4 flex-wrap gap-2">
    <h1 style="font-size:1.4rem;font-weight:700;color:var(--text);margin:0;">
      Order: <span style="color:var(--primary-light);"><?= e($order['order_number']) ?></span>
    </h1>
    <div class="d-flex align-items-center gap-2">
      <?= getOrderStatusBadge($order['status']) ?>
      <a href="<?= APP_URL ?>/customer/orders.php" class="btn btn-outline-primary btn-sm">
        <i class="bi bi-arrow-left me-1"></i>Back
      </a>
    </div>
  </div>

  <div class="row g-4">
    <div class="col-lg-8">
      <!-- Items -->
      <div class="card mb-4">
        <div class="card-header d-flex align-items-center gap-2">
          <i class="bi bi-box-seam" style="color:var(--primary-light);"></i>
          Items Ordered (<?= count($items) ?>)
        </div>
        <div class="card-body p-0">
          <?php foreach($items as $item): ?>
          <div class="d-flex align-items-center gap-3 p-3" style="border-bottom:1px solid var(--border-light);">
            <div style="width:60px;height:60px;background:var(--bg-card2);border-radius:8px;display:flex;align-items:center;justify-content:center;flex-shrink:0;font-size:1.5rem;">📦</div>
            <div class="flex-grow-1">
              <div style="font-weight:600;color:var(--text);font-size:.9rem;">
                <?php if($item['slug']): ?>
                <a href="<?= APP_URL ?>/products/detail.php?slug=<?= e($item['slug']) ?>" style="color:inherit;"><?= e($item['product_name']) ?></a>
                <?php else: ?><?= e($item['product_name']) ?><?php endif; ?>
              </div>
              <div style="color:var(--text-muted);font-size:.8rem;">
                Qty: <?= $item['quantity'] ?> × <?= formatPrice($item['price']) ?>
              </div>
            </div>
            <div style="font-weight:700;color:var(--text);"><?= formatPrice($item['total']) ?></div>
          </div>
          <?php endforeach; ?>
        </div>
      </div>

      <!-- Shipping Address -->
      <div class="card">
        <div class="card-header"><i class="bi bi-geo-alt me-2" style="color:var(--primary-light);"></i>Shipping Address</div>
        <div class="card-body">
          <p style="color:var(--text);margin:0;line-height:2;">
            <strong><?= e($order['shipping_name']) ?></strong><br>
            <?= e($order['shipping_address']) ?><br>
            <?= e($order['shipping_city']) ?>, <?= e($order['shipping_state']) ?> – <?= e($order['shipping_pincode']) ?><br>
            📞 <?= e($order['shipping_phone']) ?>
          </p>
          <?php if($order['notes']): ?>
          <div class="mt-2 pt-2" style="border-top:1px solid var(--border-light);">
            <small style="color:var(--text-muted);">Note: <?= e($order['notes']) ?></small>
          </div>
          <?php endif; ?>
        </div>
      </div>
    </div>

    <div class="col-lg-4">
      <!-- Order Summary -->
      <div class="order-summary-card mb-4">
        <h6 style="color:var(--text);font-weight:700;margin-bottom:1rem;">Order Summary</h6>
        <div class="summary-row"><span>Subtotal</span><span style="color:var(--text);"><?= formatPrice($order['subtotal']) ?></span></div>
        <div class="summary-row"><span>Shipping</span><span style="color:var(--text);"><?= $order['shipping_charge']==0?'Free':formatPrice($order['shipping_charge']) ?></span></div>
        <div class="summary-row"><span>Tax</span><span style="color:var(--text);"><?= formatPrice($order['tax']) ?></span></div>
        <?php if($order['discount']>0): ?>
        <div class="summary-row"><span>Discount</span><span style="color:var(--success);">-<?= formatPrice($order['discount']) ?></span></div>
        <?php endif; ?>
        <div class="summary-row total"><span>Total</span><span><?= formatPrice($order['total']) ?></span></div>
      </div>

      <!-- Payment Info -->
      <div class="card">
        <div class="card-header"><i class="bi bi-credit-card me-2" style="color:var(--primary-light);"></i>Payment</div>
        <div class="card-body">
          <div class="d-flex justify-content-between mb-2">
            <span style="color:var(--text-muted);">Method:</span>
            <strong style="color:var(--text);"><?= PAYMENT_METHODS[$order['payment_method']] ?? $order['payment_method'] ?></strong>
          </div>
          <div class="d-flex justify-content-between mb-2">
            <span style="color:var(--text-muted);">Status:</span>
            <span class="badge <?= $order['payment_status']==='paid'?'bg-success':'bg-warning' ?>">
              <?= ucfirst($order['payment_status']) ?>
            </span>
          </div>
          <?php if($payment && $payment['transaction_id']): ?>
          <div class="d-flex justify-content-between">
            <span style="color:var(--text-muted);">TXN ID:</span>
            <code style="font-size:.78rem;color:var(--primary-light);"><?= e($payment['transaction_id']) ?></code>
          </div>
          <?php endif; ?>
        </div>
      </div>

      <!-- Timeline -->
      <div class="card mt-4">
        <div class="card-header"><i class="bi bi-clock-history me-2" style="color:var(--primary-light);"></i>Order Timeline</div>
        <div class="card-body">
          <?php
          $statuses = ['pending','processing','shipped','delivered'];
          $currentIdx = array_search($order['status'], $statuses);
          if ($order['status'] === 'cancelled') { $statuses = ['pending','cancelled']; $currentIdx = 1; }
          $sIcons = ['pending'=>'⏳','processing'=>'⚙️','shipped'=>'🚚','delivered'=>'✅','cancelled'=>'❌'];
          foreach($statuses as $i=>$s): $done = $i<=$currentIdx; ?>
          <div class="d-flex align-items-center gap-3 mb-2">
            <div style="width:32px;height:32px;border-radius:50%;background:<?= $done?'rgba(99,102,241,.2)':'var(--bg-card2)' ?>;border:2px solid <?= $done?'var(--primary)':'var(--border-light)' ?>;display:flex;align-items:center;justify-content:center;font-size:.9rem;flex-shrink:0;">
              <?= $sIcons[$s] ?? '•' ?>
            </div>
            <div>
              <div style="font-size:.85rem;font-weight:600;color:<?= $done?'var(--text)':'var(--text-dim)' ?>;"><?= ucfirst($s) ?></div>
            </div>
          </div>
          <?php endforeach; ?>
          <div class="mt-2 pt-2" style="border-top:1px solid var(--border-light);">
            <small style="color:var(--text-dim);">Placed: <?= date('d M Y H:i', strtotime($order['created_at'])) ?></small>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
