<?php
// admin/index.php — Dashboard
define('ADMIN_PAGE_TITLE', 'Dashboard');
require_once __DIR__ . '/../admin/includes/header.php';

$stats = getDashboardStats();
$pdo   = getDB();

// Revenue last 7 days
$revenueData = $pdo->query("
    SELECT DATE(created_at) AS day, COALESCE(SUM(total),0) AS revenue
    FROM orders WHERE payment_status='paid' AND created_at >= DATE_SUB(NOW(),INTERVAL 7 DAY)
    GROUP BY DATE(created_at) ORDER BY day
")->fetchAll();
$labels  = []; $revValues = [];
for($i=6; $i>=0; $i--) {
    $d = date('Y-m-d', strtotime("-{$i} days"));
    $labels[] = date('d M', strtotime($d));
    $found = array_filter($revenueData, fn($r) => $r['day']===$d);
    $revValues[] = $found ? array_values($found)[0]['revenue'] : 0;
}

// Orders by status
$ordersByStatus = $pdo->query("SELECT status, COUNT(*) AS cnt FROM orders GROUP BY status")->fetchAll();
$oLabels = array_column($ordersByStatus,'status');
$oValues = array_column($ordersByStatus,'cnt');

// Recent orders
$recentOrders = $pdo->query("
    SELECT o.*,u.name AS customer_name FROM orders o
    LEFT JOIN users u ON u.id=o.user_id
    ORDER BY o.created_at DESC LIMIT 8
")->fetchAll();

// Low stock products
$lowStock = $pdo->query("SELECT p.*,c.name AS cat_name FROM products p LEFT JOIN categories c ON c.id=p.category_id WHERE p.stock <= 5 AND p.status=1 ORDER BY p.stock ASC LIMIT 5")->fetchAll();

// Top selling
$topSelling = $pdo->query("
    SELECT p.name,p.slug,SUM(oi.quantity) AS sold,SUM(oi.total) AS revenue
    FROM order_items oi JOIN products p ON p.id=oi.product_id
    GROUP BY p.id ORDER BY sold DESC LIMIT 5
")->fetchAll();
?>

<!-- Stat Cards -->
<div class="row g-4 mb-4">
  <div class="col-sm-6 col-xl-3">
    <div class="admin-stat indigo">
      <div class="stat-icon-box indigo"><i class="bi bi-currency-rupee"></i></div>
      <div class="stat-value" data-value="<?= $stats['total_revenue'] ?>" data-prefix="₹">₹0</div>
      <div class="stat-label">Total Revenue</div>
      <span class="stat-badge up"><i class="bi bi-arrow-up"></i> Paid orders</span>
    </div>
  </div>
  <div class="col-sm-6 col-xl-3">
    <div class="admin-stat purple">
      <div class="stat-icon-box purple"><i class="bi bi-bag-check"></i></div>
      <div class="stat-value" data-value="<?= $stats['total_orders'] ?>"><?= number_format($stats['total_orders']) ?></div>
      <div class="stat-label">Total Orders</div>
      <span class="stat-badge up"><i class="bi bi-clock"></i> <?= $stats['pending_orders'] ?> pending</span>
    </div>
  </div>
  <div class="col-sm-6 col-xl-3">
    <div class="admin-stat green">
      <div class="stat-icon-box green"><i class="bi bi-people"></i></div>
      <div class="stat-value" data-value="<?= $stats['total_customers'] ?>"><?= number_format($stats['total_customers']) ?></div>
      <div class="stat-label">Customers</div>
      <span class="stat-badge up"><i class="bi bi-person-check"></i> Active</span>
    </div>
  </div>
  <div class="col-sm-6 col-xl-3">
    <div class="admin-stat amber">
      <div class="stat-icon-box amber"><i class="bi bi-box-seam"></i></div>
      <div class="stat-value" data-value="<?= $stats['total_products'] ?>"><?= number_format($stats['total_products']) ?></div>
      <div class="stat-label">Products</div>
      <?php if($stats['low_stock'] > 0): ?>
      <span class="stat-badge down"><i class="bi bi-exclamation-triangle"></i> <?= $stats['low_stock'] ?> low stock</span>
      <?php endif; ?>
    </div>
  </div>
</div>

<!-- Charts Row -->
<div class="row g-4 mb-4">
  <div class="col-lg-8">
    <div class="chart-card">
      <div class="chart-header">
        <div>
          <div class="chart-title">Revenue — Last 7 Days</div>
          <div class="chart-subtitle">Daily revenue from paid orders</div>
        </div>
      </div>
      <div class="chart-wrap" style="height:260px;">
        <canvas id="revenue-chart"></canvas>
      </div>
    </div>
  </div>
  <div class="col-lg-4">
    <div class="chart-card">
      <div class="chart-header">
        <div class="chart-title">Orders by Status</div>
      </div>
      <div class="chart-wrap" style="height:260px;">
        <canvas id="orders-chart"></canvas>
      </div>
    </div>
  </div>
</div>

<!-- Recent Orders + Top Selling -->
<div class="row g-4 mb-4">
  <div class="col-lg-8">
    <div class="admin-table-card">
      <div class="admin-table-header">
        <div class="admin-table-title">Recent Orders</div>
        <a href="<?= APP_URL ?>/admin/orders/index.php" class="btn-admin-outline">View All</a>
      </div>
      <div class="table-responsive">
        <table class="admin-table">
          <thead><tr>
            <th>Order #</th><th>Customer</th><th>Amount</th><th>Method</th><th>Status</th><th>Date</th>
          </tr></thead>
          <tbody>
            <?php foreach($recentOrders as $o): ?>
            <tr>
              <td><a href="<?= APP_URL ?>/admin/orders/detail.php?id=<?= $o['id'] ?>" style="color:var(--primary-light);font-weight:600;"><?= e($o['order_number']) ?></a></td>
              <td class="cell-primary"><?= e($o['customer_name']) ?></td>
              <td style="color:var(--text);font-weight:600;"><?= formatPrice($o['total']) ?></td>
              <td style="color:var(--text-muted);"><?= PAYMENT_METHODS[$o['payment_method']] ?? $o['payment_method'] ?></td>
              <td><span class="status-badge <?= $o['status'] ?>"><span class="status-dot"></span><?= ucfirst($o['status']) ?></span></td>
              <td class="cell-muted"><?= date('d M', strtotime($o['created_at'])) ?></td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
  <div class="col-lg-4">
    <!-- Top Selling -->
    <div class="admin-table-card mb-4">
      <div class="admin-table-header">
        <div class="admin-table-title">🏆 Top Selling</div>
      </div>
      <?php foreach($topSelling as $i=>$p): ?>
      <div class="d-flex align-items-center gap-3 px-3 py-2" style="border-bottom:1px solid var(--admin-border-lg);">
        <div style="width:24px;height:24px;border-radius:50%;background:<?= ['rgba(99,102,241,.3)','rgba(168,85,247,.3)','rgba(6,182,212,.3)','rgba(16,185,129,.3)','rgba(245,158,11,.3)'][$i]?>;display:flex;align-items:center;justify-content:center;font-size:.75rem;font-weight:700;color:var(--text);flex-shrink:0;"><?= $i+1 ?></div>
        <div class="flex-grow-1" style="min-width:0;">
          <div style="font-size:.82rem;color:var(--text);font-weight:500;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;"><?= e($p['name']) ?></div>
          <div style="font-size:.72rem;color:var(--text-dim);"><?= $p['sold'] ?> sold</div>
        </div>
        <div style="font-size:.82rem;color:#6ee7b7;font-weight:600;white-space:nowrap;"><?= formatPrice($p['revenue']) ?></div>
      </div>
      <?php endforeach; ?>
    </div>

    <!-- Low Stock Alert -->
    <?php if($lowStock): ?>
    <div class="admin-table-card">
      <div class="admin-table-header">
        <div class="admin-table-title">⚠️ Low Stock</div>
        <a href="<?= APP_URL ?>/admin/inventory/index.php" class="btn-admin-outline" style="font-size:.75rem;">Manage</a>
      </div>
      <?php foreach($lowStock as $p): ?>
      <div class="d-flex align-items-center justify-content-between px-3 py-2" style="border-bottom:1px solid var(--admin-border-lg);">
        <div style="font-size:.82rem;color:var(--text);font-weight:500;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;max-width:160px;"><?= e($p['name']) ?></div>
        <span class="status-badge <?= $p['stock']==0?'cancelled':'pending' ?>"><?= $p['stock'] ?> left</span>
      </div>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>
  </div>
</div>

<script>
initRevenueChart(<?= json_encode($labels) ?>, <?= json_encode($revValues) ?>);
initOrdersChart(<?= json_encode(array_map('ucfirst',$oLabels)) ?>, <?= json_encode($oValues) ?>);
</script>

<?php require_once __DIR__ . '/../admin/includes/footer.php'; ?>
