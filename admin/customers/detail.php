<?php
// admin/customers/detail.php
define('ADMIN_PAGE_TITLE', 'Customer Detail');
require_once __DIR__ . '/../../admin/includes/header.php';

$pdo = getDB();
$id  = (int)($_GET['id'] ?? 0);
if (!$id) redirect(APP_URL . '/admin/customers/index.php');

$stmt = $pdo->prepare("SELECT * FROM users WHERE id=?");
$stmt->execute([$id]);
$customer = $stmt->fetch();
if (!$customer) redirect(APP_URL . '/admin/customers/index.php');

$orders = $pdo->prepare("SELECT * FROM orders WHERE user_id=? ORDER BY created_at DESC LIMIT 20");
$orders->execute([$id]);
$orders = $orders->fetchAll();

$totSpent = array_sum(array_column($orders,'total'));
$wishCnt  = (int)$pdo->prepare("SELECT COUNT(*) FROM wishlist WHERE user_id=?")->execute([$id]) ? 0 : 0;
$ws = $pdo->prepare("SELECT COUNT(*) FROM wishlist WHERE user_id=?");
$ws->execute([$id]);
$wishCnt = (int)$ws->fetchColumn();
?>

<div class="d-flex align-items-center gap-3 mb-4">
  <a href="<?= APP_URL ?>/admin/customers/index.php" class="btn-icon" style="width:36px;height:36px;"><i class="bi bi-arrow-left"></i></a>
  <h2 style="color:var(--text);font-weight:700;margin:0;">Customer Profile</h2>
</div>

<div class="row g-4">
  <div class="col-lg-4">
    <div class="admin-form-card text-center mb-4">
      <div style="width:72px;height:72px;border-radius:50%;background:linear-gradient(135deg,#6366f1,#a855f7);display:flex;align-items:center;justify-content:center;font-size:1.8rem;font-weight:700;color:#fff;margin:0 auto 1rem;"><?= strtoupper(substr($customer['name'],0,1)) ?></div>
      <h5 style="color:var(--text);font-weight:700;"><?= e($customer['name']) ?></h5>
      <div style="color:var(--text-muted);font-size:.875rem;"><?= e($customer['email']) ?></div>
      <?php if($customer['phone']): ?><div style="color:var(--text-muted);font-size:.875rem;">📞 <?= e($customer['phone']) ?></div><?php endif; ?>
      <div class="mt-2"><span class="status-badge <?= $customer['status']?'active':'inactive' ?>"><span class="status-dot"></span><?= $customer['status']?'Active':'Disabled' ?></span></div>

      <div class="row g-2 mt-3 text-center">
        <div class="col-4">
          <div style="font-weight:700;color:var(--primary-light);font-size:1.3rem;"><?= count($orders) ?></div>
          <div style="font-size:.72rem;color:var(--text-muted);">Orders</div>
        </div>
        <div class="col-4">
          <div style="font-weight:700;color:#6ee7b7;font-size:1.3rem;">₹<?= number_format($totSpent,0) ?></div>
          <div style="font-size:.72rem;color:var(--text-muted);">Spent</div>
        </div>
        <div class="col-4">
          <div style="font-weight:700;color:#f87171;font-size:1.3rem;"><?= $wishCnt ?></div>
          <div style="font-size:.72rem;color:var(--text-muted);">Wishlist</div>
        </div>
      </div>
    </div>

    <div class="admin-form-card">
      <div class="admin-form-section-title"><i class="bi bi-info-circle"></i> Details</div>
      <?php
      $fields = [
        'City'      => $customer['city'],
        'State'     => $customer['state'],
        'Pincode'   => $customer['pincode'],
        'Address'   => $customer['address'],
        'Joined'    => date('d M Y', strtotime($customer['created_at'])),
        'Verified'  => $customer['email_verified'] ? '✅ Yes' : '❌ No',
      ];
      foreach($fields as $label => $val): if($val): ?>
      <div class="d-flex gap-2 mb-2">
        <span style="color:var(--text-dim);font-size:.8rem;min-width:60px;"><?= $label ?></span>
        <span style="color:var(--text);font-size:.85rem;"><?= e($val) ?></span>
      </div>
      <?php endif; endforeach; ?>
    </div>
  </div>

  <div class="col-lg-8">
    <div class="admin-table-card">
      <div class="admin-table-header">
        <div class="admin-table-title">Order History</div>
      </div>
      <div class="table-responsive">
        <table class="admin-table">
          <thead><tr><th>Order #</th><th>Total</th><th>Payment</th><th>Status</th><th>Date</th><th></th></tr></thead>
          <tbody>
            <?php foreach($orders as $o): ?>
            <tr>
              <td style="color:var(--primary-light);font-weight:600;"><?= e($o['order_number']) ?></td>
              <td style="font-weight:700;color:var(--text);"><?= formatPrice($o['total']) ?></td>
              <td><span class="status-badge <?= $o['payment_status'] ?>" style="font-size:.68rem;"><?= ucfirst($o['payment_status']) ?></span></td>
              <td><span class="status-badge <?= $o['status'] ?>" style="font-size:.68rem;"><?= ucfirst($o['status']) ?></span></td>
              <td class="cell-muted"><?= date('d M Y', strtotime($o['created_at'])) ?></td>
              <td><a href="<?= APP_URL ?>/admin/orders/detail.php?id=<?= $o['id'] ?>" class="btn-icon view"><i class="bi bi-eye"></i></a></td>
            </tr>
            <?php endforeach; ?>
            <?php if(empty($orders)): ?><tr><td colspan="6" class="text-center py-3" style="color:var(--text-muted);">No orders yet.</td></tr><?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>

<?php require_once __DIR__ . '/../../admin/includes/footer.php'; ?>
