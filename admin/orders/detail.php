<?php
// admin/orders/detail.php
define('ADMIN_PAGE_TITLE', 'Order Detail');
require_once __DIR__ . '/../../admin/includes/header.php';

$pdo = getDB();
$id  = (int)($_GET['id'] ?? 0);
if (!$id) redirect(APP_URL . '/admin/orders/index.php');

$order = $pdo->prepare("SELECT o.*,u.name AS customer_name,u.email AS customer_email,u.phone AS customer_phone FROM orders o LEFT JOIN users u ON u.id=o.user_id WHERE o.id=?");
$order->execute([$id]);
$order = $order->fetch();
if (!$order) redirect(APP_URL . '/admin/orders/index.php');

$items = $pdo->prepare("SELECT * FROM order_items WHERE order_id=?");
$items->execute([$id]);
$items = $items->fetchAll();

$payment = $pdo->prepare("SELECT * FROM payments WHERE order_id=? ORDER BY id DESC LIMIT 1");
$payment->execute([$id]);
$payment = $payment->fetch();

// Handle status update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    $ns = $_POST['status'] ?? '';
    $ps = $_POST['payment_status'] ?? '';
    if (in_array($ns, ORDER_STATUSES)) {
        $pdo->prepare("UPDATE orders SET status=?,updated_at=NOW() WHERE id=?")->execute([$ns,$id]);
        if (in_array($ps,['pending','paid','failed','refunded'])) {
            $pdo->prepare("UPDATE orders SET payment_status=? WHERE id=?")->execute([$ps,$id]);
            if ($ps === 'paid' && $payment) {
                $pdo->prepare("UPDATE payments SET status='paid',paid_at=NOW() WHERE id=?")->execute([$payment['id']]);
            }
        }
        sendSNSNotification("Order {$order['order_number']} status updated to $ns.", 'Order Update');
        setFlash('success', 'Order updated successfully.');
        redirect(APP_URL . '/admin/orders/detail.php?id=' . $id);
    }
}
?>

<div class="d-flex align-items-center gap-3 mb-4 flex-wrap">
  <a href="<?= APP_URL ?>/admin/orders/index.php" class="btn-icon" style="width:36px;height:36px;"><i class="bi bi-arrow-left"></i></a>
  <div>
    <h2 style="color:var(--text);font-weight:700;margin:0;">Order: <?= e($order['order_number']) ?></h2>
    <div style="color:var(--text-muted);font-size:.875rem;"><?= date('d M Y, H:i', strtotime($order['created_at'])) ?></div>
  </div>
  <span class="status-badge <?= $order['status'] ?> ms-auto"><span class="status-dot"></span><?= ucfirst($order['status']) ?></span>
</div>

<div class="row g-4">
  <div class="col-lg-8">
    <!-- Items -->
    <div class="admin-table-card mb-4">
      <div class="admin-table-header">
        <div class="admin-table-title">Order Items (<?= count($items) ?>)</div>
      </div>
      <div class="table-responsive">
        <table class="admin-table">
          <thead><tr><th>Product</th><th>Qty</th><th>Unit Price</th><th>Total</th></tr></thead>
          <tbody>
            <?php foreach($items as $item): ?>
            <tr>
              <td class="cell-primary"><?= e($item['product_name']) ?></td>
              <td style="color:var(--text-muted);"><?= $item['quantity'] ?></td>
              <td style="color:var(--text);"><?= formatPrice($item['price']) ?></td>
              <td style="font-weight:700;color:var(--text);"><?= formatPrice($item['total']) ?></td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
      <div style="padding:1rem 1.2rem;border-top:1px solid var(--admin-border-lg);">
        <div class="d-flex justify-content-between mb-1"><span style="color:var(--text-muted);">Subtotal</span><span style="color:var(--text);"><?= formatPrice($order['subtotal']) ?></span></div>
        <div class="d-flex justify-content-between mb-1"><span style="color:var(--text-muted);">Shipping</span><span style="color:var(--text);"><?= $order['shipping_charge']==0?'Free':formatPrice($order['shipping_charge']) ?></span></div>
        <div class="d-flex justify-content-between mb-1"><span style="color:var(--text-muted);">Tax</span><span style="color:var(--text);"><?= formatPrice($order['tax']) ?></span></div>
        <div class="d-flex justify-content-between pt-2" style="border-top:1px solid var(--admin-border-lg);margin-top:.5rem;">
          <strong style="color:var(--text);">Grand Total</strong>
          <strong style="color:var(--text);font-size:1.1rem;"><?= formatPrice($order['total']) ?></strong>
        </div>
      </div>
    </div>

    <!-- Shipping Address -->
    <div class="admin-form-card">
      <div class="admin-form-section-title"><i class="bi bi-geo-alt"></i> Shipping Address</div>
      <p style="color:var(--text);line-height:2;margin:0;">
        <strong><?= e($order['shipping_name']) ?></strong><br>
        <?= e($order['shipping_address']) ?><br>
        <?= e("{$order['shipping_city']}, {$order['shipping_state']} – {$order['shipping_pincode']}") ?><br>
        📞 <?= e($order['shipping_phone']) ?>
      </p>
      <?php if($order['notes']): ?>
      <div class="mt-2 pt-2" style="border-top:1px solid var(--admin-border-lg);">
        <small style="color:var(--text-muted);">Note: <?= e($order['notes']) ?></small>
      </div>
      <?php endif; ?>
    </div>
  </div>

  <div class="col-lg-4">
    <!-- Update Status -->
    <div class="admin-form-card mb-4">
      <div class="admin-form-section-title"><i class="bi bi-arrow-repeat"></i> Update Order</div>
      <form method="POST">
        <?= csrfInput() ?>
        <div class="mb-3">
          <label class="form-label">Order Status</label>
          <select name="status" class="form-select">
            <?php foreach(ORDER_STATUSES as $s): ?>
            <option value="<?= $s ?>" <?= $order['status']===$s?'selected':'' ?>><?= ucfirst($s) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="mb-3">
          <label class="form-label">Payment Status</label>
          <select name="payment_status" class="form-select">
            <?php foreach(['pending','paid','failed','refunded'] as $ps): ?>
            <option value="<?= $ps ?>" <?= $order['payment_status']===$ps?'selected':'' ?>><?= ucfirst($ps) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <button type="submit" class="btn-admin-primary w-100 justify-content-center"><i class="bi bi-save2"></i> Save Changes</button>
      </form>
    </div>

    <!-- Customer -->
    <div class="admin-form-card mb-4">
      <div class="admin-form-section-title"><i class="bi bi-person"></i> Customer</div>
      <div class="cell-primary"><?= e($order['customer_name']) ?></div>
      <div style="color:var(--text-muted);font-size:.85rem;"><?= e($order['customer_email']) ?></div>
      <?php if($order['customer_phone']): ?>
      <div style="color:var(--text-muted);font-size:.85rem;"><?= e($order['customer_phone']) ?></div>
      <?php endif; ?>
      <div class="mt-2">
        <a href="<?= APP_URL ?>/admin/customers/detail.php?id=<?= $order['user_id'] ?>" class="btn-admin-outline" style="font-size:.78rem;">View Customer</a>
      </div>
    </div>

    <!-- Payment -->
    <div class="admin-form-card">
      <div class="admin-form-section-title"><i class="bi bi-credit-card"></i> Payment</div>
      <div class="d-flex justify-content-between mb-2">
        <span style="color:var(--text-muted);">Method</span>
        <strong style="color:var(--text);"><?= PAYMENT_METHODS[$order['payment_method']]??$order['payment_method'] ?></strong>
      </div>
      <div class="d-flex justify-content-between mb-2">
        <span style="color:var(--text-muted);">Status</span>
        <span class="status-badge <?= $order['payment_status'] ?>"><?= ucfirst($order['payment_status']) ?></span>
      </div>
      <?php if($payment && $payment['paid_at']): ?>
      <div class="d-flex justify-content-between">
        <span style="color:var(--text-muted);">Paid at</span>
        <span style="color:var(--text);font-size:.82rem;"><?= date('d M Y H:i', strtotime($payment['paid_at'])) ?></span>
      </div>
      <?php endif; ?>
    </div>
  </div>
</div>

<?php require_once __DIR__ . '/../../admin/includes/footer.php'; ?>
