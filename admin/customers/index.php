<?php
// admin/customers/index.php
define('ADMIN_PAGE_TITLE', 'Customers');
require_once __DIR__ . '/../../admin/includes/header.php';

$pdo  = getDB();
$page = max(1,(int)($_GET['page']??1));
$q    = trim($_GET['q']??'');
$where = ['1=1']; $params = [];
if ($q) { $where[] = '(u.name LIKE ? OR u.email LIKE ? OR u.phone LIKE ?)'; $params[] = "%$q%"; $params[] = "%$q%"; $params[] = "%$q%"; }
$wsql = 'WHERE '.implode(' AND ',$where);

$cntS = $pdo->prepare("SELECT COUNT(*) FROM users u $wsql");
$cntS->execute($params);
$total = (int)$cntS->fetchColumn();
$pagi  = paginate($total, ADMIN_PER_PAGE, $page, APP_URL.'/admin/customers/index.php?'.($q?"q=$q&":''));

$stmt = $pdo->prepare("SELECT u.*,COUNT(o.id) AS order_count,COALESCE(SUM(o.total),0) AS total_spent FROM users u LEFT JOIN orders o ON o.user_id=u.id $wsql GROUP BY u.id ORDER BY u.created_at DESC LIMIT ? OFFSET ?");
$stmt->execute([...$params, ADMIN_PER_PAGE, $pagi['offset']]);
$customers = $stmt->fetchAll();

// Toggle status
if (isset($_GET['toggle']) && is_numeric($_GET['toggle'])) {
    $cuid = (int)$_GET['toggle'];
    $pdo->prepare("UPDATE users SET status=1-status WHERE id=?")->execute([$cuid]);
    redirect(APP_URL.'/admin/customers/index.php'.($q?"?q=$q":''));
}
?>

<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
  <h2 style="color:var(--text);font-weight:700;margin:0;">Customers <span style="color:var(--text-muted);font-size:.875rem;font-weight:400;">(<?= number_format($total) ?>)</span></h2>
  <form method="GET" class="d-flex gap-2">
    <input type="text" name="q" class="form-control" placeholder="Search name, email, phone…" value="<?= e($q) ?>" style="max-width:250px;">
    <button type="submit" class="btn-admin-primary"><i class="bi bi-search"></i></button>
    <?php if($q): ?><a href="<?= APP_URL ?>/admin/customers/index.php" class="btn-admin-outline">Clear</a><?php endif; ?>
  </form>
</div>

<div class="admin-table-card">
  <div class="table-responsive">
    <table class="admin-table" id="customers-table">
      <thead><tr>
        <th>Customer</th><th>Phone</th><th>Orders</th><th>Spent</th><th>Joined</th><th>Status</th><th>Actions</th>
      </tr></thead>
      <tbody>
        <?php foreach($customers as $c): ?>
        <tr>
          <td>
            <div class="d-flex align-items-center gap-2">
              <div style="width:36px;height:36px;border-radius:50%;background:linear-gradient(135deg,#6366f1,#a855f7);display:flex;align-items:center;justify-content:center;font-weight:700;font-size:.85rem;color:#fff;flex-shrink:0;">
                <?= strtoupper(substr($c['name'],0,1)) ?>
              </div>
              <div>
                <div class="cell-primary"><?= e($c['name']) ?></div>
                <div class="cell-muted"><?= e($c['email']) ?></div>
              </div>
            </div>
          </td>
          <td style="color:var(--text-muted);"><?= e($c['phone']??'—') ?></td>
          <td style="color:var(--primary-light);font-weight:600;"><?= $c['order_count'] ?></td>
          <td style="color:var(--text);font-weight:600;"><?= formatPrice($c['total_spent']) ?></td>
          <td class="cell-muted"><?= date('d M Y', strtotime($c['created_at'])) ?></td>
          <td>
            <span class="status-badge <?= $c['status']?'active':'inactive' ?>">
              <span class="status-dot"></span><?= $c['status']?'Active':'Disabled' ?>
            </span>
          </td>
          <td>
            <div class="d-flex gap-1">
              <a href="<?= APP_URL ?>/admin/customers/detail.php?id=<?= $c['id'] ?>" class="btn-icon view"><i class="bi bi-eye"></i></a>
              <a href="?toggle=<?= $c['id'] ?><?= $q?"&q=$q":'' ?>" class="btn-icon <?= $c['status']?'delete':'edit' ?>" data-confirm="<?= $c['status']?'Disable':'Enable' ?> this customer?">
                <i class="bi bi-<?= $c['status']?'person-x':'person-check' ?>"></i>
              </a>
            </div>
          </td>
        </tr>
        <?php endforeach; ?>
        <?php if(empty($customers)): ?>
        <tr><td colspan="7" class="text-center py-4" style="color:var(--text-muted);">No customers found.</td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
  <div class="table-pagination">
    <div class="table-pagination-info">Showing <?= count($customers) ?> of <?= $total ?></div>
    <?= renderPagination($pagi) ?>
  </div>
</div>

<?php require_once __DIR__ . '/../../admin/includes/footer.php'; ?>
