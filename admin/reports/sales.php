<?php
// admin/reports/sales.php
define('ADMIN_PAGE_TITLE', 'Sales Report');
require_once __DIR__ . '/../../admin/includes/header.php';

$pdo  = getDB();
$from = $_GET['from'] ?? date('Y-m-01');
$to   = $_GET['to']   ?? date('Y-m-d');

// Summary stats for range
$summary = $pdo->prepare("SELECT COUNT(*) AS order_count,COALESCE(SUM(total),0) AS revenue,COALESCE(SUM(discount),0) AS discounts,COALESCE(AVG(total),0) AS avg_order FROM orders WHERE payment_status='paid' AND DATE(created_at) BETWEEN ? AND ?");
$summary->execute([$from,$to]);
$summary = $summary->fetch();

// Daily revenue
$daily = $pdo->prepare("SELECT DATE(created_at) AS day,COUNT(*) AS orders,SUM(total) AS revenue FROM orders WHERE payment_status='paid' AND DATE(created_at) BETWEEN ? AND ? GROUP BY DATE(created_at) ORDER BY day");
$daily->execute([$from,$to]);
$daily = $daily->fetchAll();

// Revenue by category
$byCat = $pdo->prepare("SELECT c.name,SUM(oi.total) AS revenue,SUM(oi.quantity) AS units FROM order_items oi JOIN products p ON p.id=oi.product_id JOIN categories c ON c.id=p.category_id JOIN orders o ON o.id=oi.order_id WHERE o.payment_status='paid' AND DATE(o.created_at) BETWEEN ? AND ? GROUP BY c.id ORDER BY revenue DESC");
$byCat->execute([$from,$to]);
$byCat = $byCat->fetchAll();

// Top products
$topProd = $pdo->prepare("SELECT oi.product_name,SUM(oi.quantity) AS sold,SUM(oi.total) AS revenue FROM order_items oi JOIN orders o ON o.id=oi.order_id WHERE o.payment_status='paid' AND DATE(o.created_at) BETWEEN ? AND ? GROUP BY oi.product_name ORDER BY revenue DESC LIMIT 10");
$topProd->execute([$from,$to]);
$topProd = $topProd->fetchAll();

$chartLabels  = array_column($daily,'day');
$chartRevenue = array_column($daily,'revenue');
$catLabels    = array_column($byCat,'name');
$catRevenue   = array_column($byCat,'revenue');
?>

<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
  <h2 style="color:var(--text);font-weight:700;margin:0;">Sales Report</h2>
  <button id="export-csv" class="btn-admin-outline"><i class="bi bi-download me-1"></i>Export CSV</button>
</div>

<!-- Date Filter -->
<div class="report-filter mb-4">
  <form method="GET" id="report-form" class="d-flex align-items-center gap-3 flex-wrap">
    <div class="d-flex align-items-center gap-2">
      <label style="color:var(--text-muted);font-size:.875rem;">From</label>
      <input type="date" id="date-from" name="from" class="form-control" value="<?= e($from) ?>" style="width:160px;">
    </div>
    <div class="d-flex align-items-center gap-2">
      <label style="color:var(--text-muted);font-size:.875rem;">To</label>
      <input type="date" id="date-to" name="to" class="form-control" value="<?= e($to) ?>" style="width:160px;">
    </div>
    <button type="submit" class="btn-admin-primary"><i class="bi bi-filter me-1"></i>Apply</button>
    <!-- Quick filters -->
    <div class="d-flex gap-1">
      <?php
      $quick = [
        'Today'      => [date('Y-m-d'), date('Y-m-d')],
        'This Week'  => [date('Y-m-d',strtotime('monday this week')), date('Y-m-d')],
        'This Month' => [date('Y-m-01'), date('Y-m-d')],
        'Last Month' => [date('Y-m-01',strtotime('first day of last month')), date('Y-m-t',strtotime('last day of last month'))],
      ];
      foreach($quick as $label => $dates): ?>
      <a href="?from=<?= $dates[0] ?>&to=<?= $dates[1] ?>" class="btn <?= ($from===$dates[0]&&$to===$dates[1])?'btn-primary':'btn-admin-outline' ?>" style="font-size:.78rem;padding:.35rem .7rem;border-radius:8px;"><?= $label ?></a>
      <?php endforeach; ?>
    </div>
  </form>
</div>

<!-- Summary -->
<div class="row g-4 mb-4">
  <?php
  $cards = [
    ['icon'=>'bi-currency-rupee','val'=>formatPrice($summary['revenue']),'label'=>'Total Revenue','color'=>'indigo'],
    ['icon'=>'bi-bag-check','val'=>number_format($summary['order_count']),'label'=>'Paid Orders','color'=>'green'],
    ['icon'=>'bi-calculator','val'=>formatPrice($summary['avg_order']),'label'=>'Avg. Order Value','color'=>'purple'],
    ['icon'=>'bi-tag','val'=>formatPrice($summary['discounts']),'label'=>'Total Discounts','color'=>'amber'],
  ];
  foreach($cards as $card): ?>
  <div class="col-sm-6 col-xl-3">
    <div class="admin-stat <?= $card['color'] ?>">
      <div class="stat-icon-box <?= $card['color'] ?>"><i class="bi <?= $card['icon'] ?>"></i></div>
      <div class="stat-value"><?= $card['val'] ?></div>
      <div class="stat-label"><?= $card['label'] ?></div>
    </div>
  </div>
  <?php endforeach; ?>
</div>

<!-- Charts -->
<div class="row g-4 mb-4">
  <div class="col-lg-8">
    <div class="chart-card">
      <div class="chart-header"><div class="chart-title">Daily Revenue</div></div>
      <div class="chart-wrap" style="height:280px;"><canvas id="revenue-chart"></canvas></div>
    </div>
  </div>
  <div class="col-lg-4">
    <div class="chart-card">
      <div class="chart-header"><div class="chart-title">Revenue by Category</div></div>
      <div class="chart-wrap" style="height:280px;"><canvas id="category-chart"></canvas></div>
    </div>
  </div>
</div>

<!-- Top Products Table -->
<div class="admin-table-card">
  <div class="admin-table-header"><div class="admin-table-title">Top Products in Period</div></div>
  <div class="table-responsive">
    <table class="admin-table" id="report-table">
      <thead><tr><th>#</th><th>Product</th><th>Units Sold</th><th>Revenue</th></tr></thead>
      <tbody>
        <?php foreach($topProd as $i=>$p): ?>
        <tr>
          <td class="cell-muted"><?= $i+1 ?></td>
          <td class="cell-primary"><?= e($p['product_name']) ?></td>
          <td style="color:var(--text-muted);"><?= number_format($p['sold']) ?></td>
          <td style="font-weight:700;color:#6ee7b7;"><?= formatPrice($p['revenue']) ?></td>
        </tr>
        <?php endforeach; ?>
        <?php if(empty($topProd)): ?><tr><td colspan="4" class="text-center py-3" style="color:var(--text-muted);">No data for selected period.</td></tr><?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<!-- Daily Table -->
<?php if($daily): ?>
<div class="admin-table-card mt-4">
  <div class="admin-table-header"><div class="admin-table-title">Daily Breakdown</div></div>
  <div class="table-responsive">
    <table class="admin-table">
      <thead><tr><th>Date</th><th>Orders</th><th>Revenue</th></tr></thead>
      <tbody>
        <?php foreach($daily as $d): ?>
        <tr>
          <td style="color:var(--text);"><?= date('d M Y, D', strtotime($d['day'])) ?></td>
          <td style="color:var(--text-muted);"><?= $d['orders'] ?></td>
          <td style="font-weight:700;color:var(--text);"><?= formatPrice($d['revenue']) ?></td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
<?php endif; ?>

<script>
initRevenueChart(<?= json_encode(array_map(fn($d)=>date('d M',strtotime($d)),$chartLabels)) ?>, <?= json_encode($chartRevenue) ?>);
initCategoryChart(<?= json_encode($catLabels) ?>, <?= json_encode($catRevenue) ?>);
</script>

<?php require_once __DIR__ . '/../../admin/includes/footer.php'; ?>
