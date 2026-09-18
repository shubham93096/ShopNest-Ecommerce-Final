<?php
// customer/orders.php
define('PAGE_TITLE', 'My Orders');
require_once __DIR__ . '/../includes/auth_check.php';

$pdo  = getDB();
$uid  = $_SESSION[SESSION_USER]['id'];
$page = max(1,(int)($_GET['page']??1));
$status = $_GET['status'] ?? '';

$where = ['o.user_id=?'];
$params = [$uid];
if ($status) { $where[] = 'o.status=?'; $params[] = $status; }

$total = (int)$pdo->prepare("SELECT COUNT(*) FROM orders o WHERE " . implode(' AND ',$where))->execute($params) ?
    $pdo->prepare("SELECT COUNT(*) FROM orders o WHERE " . implode(' AND ',$where)) : 0;
$cntStmt = $pdo->prepare("SELECT COUNT(*) FROM orders o WHERE " . implode(' AND ',$where));
$cntStmt->execute($params);
$total = (int)$cntStmt->fetchColumn();

$pagi = paginate($total, ORDERS_PER_PAGE, $page, APP_URL . '/customer/orders.php?' . ($status ? "status={$status}&" : ''));

$stmt = $pdo->prepare("
    SELECT o.*, COUNT(oi.id) AS item_count
    FROM orders o
    LEFT JOIN order_items oi ON oi.order_id=o.id
    WHERE " . implode(' AND ',$where) . "
    GROUP BY o.id
    ORDER BY o.created_at DESC
    LIMIT ? OFFSET ?
");
$stmt->execute([...$params, ORDERS_PER_PAGE, $pagi['offset']]);
$orders = $stmt->fetchAll();

require_once __DIR__ . '/../includes/header.php';
?>

<div class="container py-4">
  <div class="breadcrumb-wrap">
    <nav><ol class="breadcrumb">
      <li class="breadcrumb-item"><a href="<?= APP_URL ?>/">Home</a></li>
      <li class="breadcrumb-item active">My Orders</li>
    </ol></nav>
  </div>

  <div class="row g-4">
    <div class="col-lg-3">
      <?php
      $u = currentUser();
      $pStmt = $pdo->prepare("SELECT * FROM users WHERE id=?");
      $pStmt->execute([$u['id']]);
      $profile = $pStmt->fetch();
      ?>
      <div class="profile-sidebar">
        <div class="profile-avatar"><?= strtoupper(substr($u['name'],0,1)) ?></div>
        <div class="profile-name"><?= e($u['name']) ?></div>
        <div class="profile-email"><?= e($u['email']) ?></div>
        <ul class="profile-nav">
          <li><a href="<?= APP_URL ?>/customer/profile.php"><i class="bi bi-person"></i> Profile</a></li>
          <li><a href="<?= APP_URL ?>/customer/orders.php" class="active"><i class="bi bi-bag-check"></i> My Orders</a></li>
          <li><a href="<?= APP_URL ?>/customer/wishlist.php"><i class="bi bi-heart"></i> Wishlist</a></li>
          <li><a href="<?= APP_URL ?>/customer/logout.php" style="color:#f87171;"><i class="bi bi-box-arrow-right"></i> Logout</a></li>
        </ul>
      </div>
    </div>

    <div class="col-lg-9">
      <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-4">
        <h1 style="font-size:1.4rem;font-weight:700;color:var(--text);margin:0;">
          <i class="bi bi-bag-check me-2" style="color:var(--primary-light);"></i>My Orders
          <span style="font-size:.9rem;color:var(--text-muted);font-weight:400;">(<?= $total ?>)</span>
        </h1>
        <select class="form-select form-select-sm" style="width:auto;" onchange="location='<?= APP_URL ?>/customer/orders.php?status='+this.value">
          <option value="">All Status</option>
          <?php foreach(ORDER_STATUSES as $s): ?>
          <option value="<?= $s ?>" <?= $status===$s?'selected':'' ?>><?= ucfirst($s) ?></option>
          <?php endforeach; ?>
        </select>
      </div>

      <?php if(empty($orders)): ?>
      <div class="empty-state">
        <div class="empty-state-icon">📦</div>
        <h4>No orders yet</h4>
        <p>You haven't placed any orders. Start shopping!</p>
        <a href="<?= APP_URL ?>/products/index.php" class="btn btn-primary mt-2">Shop Now</a>
      </div>
      <?php else: ?>
      <?php foreach($orders as $o): ?>
      <div class="order-card">
        <div class="d-flex justify-content-between align-items-start flex-wrap gap-2 mb-3">
          <div>
            <div class="order-number"><?= e($o['order_number']) ?></div>
            <div class="order-meta">
              <span><i class="bi bi-calendar3 me-1"></i><?= date('d M Y', strtotime($o['created_at'])) ?></span>
              <span><i class="bi bi-box-seam me-1"></i><?= $o['item_count'] ?> items</span>
              <span><i class="bi bi-credit-card me-1"></i><?= PAYMENT_METHODS[$o['payment_method']] ?? $o['payment_method'] ?></span>
            </div>
          </div>
          <div class="text-end">
            <?= getOrderStatusBadge($o['status']) ?>
            <div class="order-total mt-1"><?= formatPrice($o['total']) ?></div>
          </div>
        </div>
        <div class="d-flex justify-content-between align-items-center">
          <div>
            <?php if($o['payment_status']==='paid'): ?>
            <span class="badge" style="background:rgba(16,185,129,.15);color:#6ee7b7;font-size:.75rem;">✓ Paid</span>
            <?php else: ?>
            <span class="badge" style="background:rgba(245,158,11,.15);color:#fcd34d;font-size:.75rem;">⏳ <?= ucfirst($o['payment_status']) ?></span>
            <?php endif; ?>
          </div>
          <a href="<?= APP_URL ?>/customer/order-detail.php?id=<?= $o['id'] ?>" class="btn btn-outline-primary btn-sm">
            View Details <i class="bi bi-arrow-right ms-1"></i>
          </a>
        </div>
      </div>
      <?php endforeach; ?>

      <?= renderPagination($pagi) ?>
      <?php endif; ?>
    </div>
  </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
