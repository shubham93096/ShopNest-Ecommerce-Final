<?php
// admin/orders/index.php
define('ADMIN_PAGE_TITLE', 'Orders');
require_once __DIR__ . '/../../admin/includes/header.php';

$pdo    = getDB();
$page   = max(1,(int)($_GET['page']??1));
$status = $_GET['status'] ?? '';
$q      = trim($_GET['q'] ?? '');
$where  = ['1=1'];
$params = [];
if ($status) { $where[] = 'o.status=?'; $params[] = $status; }
if ($q)      { $where[] = '(o.order_number LIKE ? OR u.name LIKE ? OR u.email LIKE ?)'; $params[] = "%$q%"; $params[] = "%$q%"; $params[] = "%$q%"; }
$wsql = 'WHERE '.implode(' AND ',$where);

$cntS = $pdo->prepare("SELECT COUNT(*) FROM orders o LEFT JOIN users u ON u.id=o.user_id $wsql");
$cntS->execute($params);
$total = (int)$cntS->fetchColumn();
$pagi  = paginate($total, ADMIN_PER_PAGE, $page, APP_URL.'/admin/orders/index.php?'.http_build_query(array_filter(['status'=>$status,'q'=>$q])));

$stmt = $pdo->prepare("SELECT o.*,u.name AS customer_name,u.email AS customer_email FROM orders o LEFT JOIN users u ON u.id=o.user_id $wsql ORDER BY o.created_at DESC LIMIT ? OFFSET ?");
$stmt->execute([...$params, ADMIN_PER_PAGE, $pagi['offset']]);
$orders = $stmt->fetchAll();

// Handle quick status update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    verifyCsrf();
    $oid = (int)$_POST['order_id'];
    $ns  = $_POST['new_status'];
    if (in_array($ns, ORDER_STATUSES)) {
        $pdo->prepare("UPDATE orders SET status=?,updated_at=NOW() WHERE id=?")->execute([$ns,$oid]);
        setFlash('success','Order status updated.');
        redirect(APP_URL.'/admin/orders/index.php?'.http_build_query(array_filter(['status'=>$status,'q'=>$q,'page'=>$page])));
    }
}

// Status counts for tabs
$statusCounts = [];
foreach(array_merge([''],ORDER_STATUSES) as $s) {
    $cnt = $pdo->prepare("SELECT COUNT(*) FROM orders".($s?" WHERE status=?":""));
    $cnt->execute($s?[$s]:[]);
    $statusCounts[$s ?: 'all'] = $cnt->fetchColumn();
}
?>

<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
  <h2 style="color:var(--text);font-weight:700;margin:0;">Orders</h2>
  <form method="GET" class="d-flex gap-2">
    <input type="text" name="q" class="form-control" placeholder="Search order/customer…" value="<?= e($q) ?>" style="max-width:220px;">
    <?php if($status): ?><input type="hidden" name="status" value="<?= e($status) ?>"><?php endif; ?>
    <button type="submit" class="btn-admin-primary"><i class="bi bi-search"></i></button>
    <?php if($q): ?><a href="<?= APP_URL ?>/admin/orders/index.php<?= $status?"?status=$status":'' ?>" class="btn-admin-outline">Clear</a><?php endif; ?>
  </form>
</div>

<!-- Status Tabs -->
<div class="d-flex gap-2 flex-wrap mb-4">
  <a href="<?= APP_URL ?>/admin/orders/index.php<?= $q?"?q=$q":'' ?>" class="btn <?= !$status?'btn-primary':'btn-admin-outline' ?>" style="font-size:.8rem;padding:.4rem .8rem;border-radius:8px;">
    All <span style="opacity:.7;">(<?= $statusCounts['all'] ?>)</span>
  </a>
  <?php foreach(ORDER_STATUSES as $s): ?>
  <a href="?status=<?= $s ?><?= $q?"&q=$q":'' ?>" class="btn <?= $status===$s?'btn-primary':'btn-admin-outline' ?>" style="font-size:.8rem;padding:.4rem .8rem;border-radius:8px;">
    <?= ucfirst($s) ?> <span style="opacity:.7;">(<?= $statusCounts[$s]??0 ?>)</span>
  </a>
  <?php endforeach; ?>
</div>

<div class="admin-table-card">
  <div class="table-responsive">
    <table class="admin-table">
      <thead><tr>
        <th>Order #</th><th>Customer</th><th>Items</th><th>Total</th><th>Payment</th><th>Status</th><th>Date</th><th>Actions</th>
      </tr></thead>
      <tbody>
        <?php foreach($orders as $o): ?>
        <tr>
          <td><a href="<?= APP_URL ?>/admin/orders/detail.php?id=<?= $o['id'] ?>" style="color:var(--primary-light);font-weight:600;"><?= e($o['order_number']) ?></a></td>
          <td>
            <div class="cell-primary"><?= e($o['customer_name']) ?></div>
            <div class="cell-muted"><?= e($o['customer_email']) ?></div>
          </td>
          <td style="color:var(--text-muted);">—</td>
          <td style="font-weight:700;color:var(--text);"><?= formatPrice($o['total']) ?></td>
          <td>
            <div style="color:var(--text-muted);font-size:.8rem;"><?= PAYMENT_METHODS[$o['payment_method']]??$o['payment_method'] ?></div>
            <span class="status-badge <?= $o['payment_status'] ?>" style="font-size:.68rem;"><?= ucfirst($o['payment_status']) ?></span>
          </td>
          <td>
            <form method="POST" class="d-flex align-items-center gap-1">
              <?= csrfInput() ?>
              <input type="hidden" name="order_id" value="<?= $o['id'] ?>">
              <input type="hidden" name="update_status" value="1">
              <select name="new_status" class="form-select form-select-sm" style="width:130px;font-size:.78rem;" onchange="this.form.submit()">
                <?php foreach(ORDER_STATUSES as $s): ?>
                <option value="<?= $s ?>" <?= $o['status']===$s?'selected':'' ?>><?= ucfirst($s) ?></option>
                <?php endforeach; ?>
              </select>
            </form>
          </td>
          <td class="cell-muted"><?= date('d M Y', strtotime($o['created_at'])) ?></td>
          <td>
            <a href="<?= APP_URL ?>/admin/orders/detail.php?id=<?= $o['id'] ?>" class="btn-icon view"><i class="bi bi-eye"></i></a>
          </td>
        </tr>
        <?php endforeach; ?>
        <?php if(empty($orders)): ?>
        <tr><td colspan="8" class="text-center py-4" style="color:var(--text-muted);">No orders found.</td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
  <div class="table-pagination">
    <div class="table-pagination-info">Showing <?= count($orders) ?> of <?= $total ?></div>
    <?= renderPagination($pagi) ?>
  </div>
</div>

<?php require_once __DIR__ . '/../../admin/includes/footer.php'; ?>
