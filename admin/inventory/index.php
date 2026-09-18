<?php
// admin/inventory/index.php
define('ADMIN_PAGE_TITLE', 'Inventory');
require_once __DIR__ . '/../../admin/includes/header.php';

$pdo    = getDB();
$q      = trim($_GET['q']??'');
$filter = $_GET['filter'] ?? '';
$where  = ['p.status=1'];
$params = [];
if ($q)              { $where[] = '(p.name LIKE ? OR p.sku LIKE ?)'; $params[] = "%$q%"; $params[] = "%$q%"; }
if ($filter==='low') { $where[] = 'p.stock <= 10'; }
if ($filter==='out') { $where[] = 'p.stock = 0'; }
$wsql = 'WHERE '.implode(' AND ',$where);

$stmt = $pdo->prepare("SELECT p.*,c.name AS cat_name FROM products p LEFT JOIN categories c ON c.id=p.category_id $wsql ORDER BY p.stock ASC, p.name");
$stmt->execute($params);
$products = $stmt->fetchAll();

$maxStock = (int)($pdo->query("SELECT MAX(stock) FROM products WHERE status=1")->fetchColumn() ?: 100);

// Handle stock adjustment
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    $pid   = (int)($_POST['product_id'] ?? 0);
    $qty   = (int)($_POST['quantity'] ?? 0);
    $type  = $_POST['type'] ?? 'in';
    $note  = trim($_POST['note'] ?? '');

    if ($pid && $qty !== 0) {
        if ($type === 'in') {
            $pdo->prepare("UPDATE products SET stock=stock+? WHERE id=?")->execute([$qty,$pid]);
        } elseif ($type === 'out') {
            $pdo->prepare("UPDATE products SET stock=GREATEST(stock-?,0) WHERE id=?")->execute([$qty,$pid]);
        } else {
            $pdo->prepare("UPDATE products SET stock=? WHERE id=?")->execute([$qty,$pid]);
        }
        $pdo->prepare("INSERT INTO inventory (product_id,quantity,type,note,created_at) VALUES (?,?,?,?,NOW())")->execute([$pid,$qty,$type,$note]);
        setFlash('success','Stock updated.');
        redirect(APP_URL.'/admin/inventory/index.php'.($q?"?q=$q":''));
    }
}

$lowCount = (int)$pdo->query("SELECT COUNT(*) FROM products WHERE stock<=10 AND status=1")->fetchColumn();
$outCount = (int)$pdo->query("SELECT COUNT(*) FROM products WHERE stock=0 AND status=1")->fetchColumn();
?>

<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
  <div>
    <h2 style="color:var(--text);font-weight:700;margin:0;">Inventory Management</h2>
    <div style="font-size:.875rem;">
      <span style="color:#fca5a5;">⚠️ <?= $outCount ?> out of stock</span> &nbsp;•&nbsp;
      <span style="color:#fcd34d;">🔻 <?= $lowCount ?> low stock</span>
    </div>
  </div>
  <form method="GET" class="d-flex gap-2">
    <input type="text" name="q" class="form-control" placeholder="Search product…" value="<?= e($q) ?>" style="max-width:200px;">
    <select name="filter" class="form-select" style="width:auto;" onchange="this.form.submit()">
      <option value="">All</option>
      <option value="low" <?= $filter==='low'?'selected':'' ?>>Low Stock (≤10)</option>
      <option value="out" <?= $filter==='out'?'selected':'' ?>>Out of Stock</option>
    </select>
    <button type="submit" class="btn-admin-primary"><i class="bi bi-search"></i></button>
  </form>
</div>

<div class="admin-table-card">
  <div class="table-responsive">
    <table class="admin-table">
      <thead><tr><th>Product</th><th>Category</th><th>SKU</th><th>Stock</th><th>Level</th><th>Action</th></tr></thead>
      <tbody>
        <?php foreach($products as $p):
          $pct = $maxStock > 0 ? min(100, ($p['stock']/$maxStock)*100) : 0;
          $isLow = $p['stock'] <= 10 && $p['stock'] > 0;
          $isOut = $p['stock'] === 0;
        ?>
        <tr>
          <td>
            <div class="cell-primary"><?= e($p['name']) ?></div>
            <div class="cell-muted"><?= e($p['brand'] ?? '') ?></div>
          </td>
          <td style="color:var(--text-muted);"><?= e($p['cat_name']??'—') ?></td>
          <td style="font-family:monospace;font-size:.8rem;color:var(--text-dim);"><?= e($p['sku']??'—') ?></td>
          <td>
            <span style="font-size:1.1rem;font-weight:700;color:<?= $isOut?'#fca5a5':($isLow?'#fcd34d':'#6ee7b7') ?>;">
              <?= $p['stock'] ?>
            </span>
            <?php if($isOut): ?><div style="color:#fca5a5;font-size:.72rem;">Out of Stock</div><?php elseif($isLow): ?><div style="color:#fcd34d;font-size:.72rem;">Low Stock</div><?php endif; ?>
          </td>
          <td style="width:120px;">
            <div class="stock-bar">
              <div class="stock-bar-fill <?= $isLow||$isOut?'low':'' ?>" style="width:<?= $pct ?>%;"></div>
            </div>
          </td>
          <td>
            <button class="btn-admin-outline" style="font-size:.75rem;padding:.3rem .6rem;" onclick="openStockModal(<?= $p['id'] ?>, '<?= addslashes($p['name']) ?>', <?= $p['stock'] ?>)">
              <i class="bi bi-plus-minus me-1"></i>Adjust
            </button>
          </td>
        </tr>
        <?php endforeach; ?>
        <?php if(empty($products)): ?><tr><td colspan="6" class="text-center py-3" style="color:var(--text-muted);">No products found.</td></tr><?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<!-- Recent Inventory Log -->
<?php
$log = $pdo->query("SELECT i.*,p.name AS product_name FROM inventory i JOIN products p ON p.id=i.product_id ORDER BY i.created_at DESC LIMIT 15")->fetchAll();
if($log): ?>
<div class="admin-table-card mt-4">
  <div class="admin-table-header"><div class="admin-table-title">Recent Inventory Changes</div></div>
  <div class="table-responsive">
    <table class="admin-table">
      <thead><tr><th>Product</th><th>Type</th><th>Qty</th><th>Note</th><th>Date</th></tr></thead>
      <tbody>
        <?php foreach($log as $l): ?>
        <tr>
          <td class="cell-primary"><?= e($l['product_name']) ?></td>
          <td>
            <span class="status-badge <?= $l['type']==='in'?'delivered':($l['type']==='out'?'cancelled':'processing') ?>">
              <?= strtoupper($l['type']) ?>
            </span>
          </td>
          <td style="font-weight:600;color:<?= $l['type']==='in'?'#6ee7b7':'#fca5a5' ?>;">
            <?= $l['type']==='in'?'+':($l['type']==='out'?'-':'') ?><?= abs($l['quantity']) ?>
          </td>
          <td class="cell-muted"><?= e($l['note']??'—') ?></td>
          <td class="cell-muted"><?= date('d M Y H:i', strtotime($l['created_at'])) ?></td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
<?php endif; ?>

<!-- Adjust Stock Modal -->
<div class="modal fade" id="stockModal" tabindex="-1">
  <div class="modal-dialog"><div class="modal-content">
    <div class="modal-header">
      <h5 class="modal-title">Adjust Stock — <span id="modal-product-name"></span></h5>
      <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
    </div>
    <form method="POST">
      <?= csrfInput() ?>
      <input type="hidden" name="product_id" id="modal-product-id">
      <div class="modal-body">
        <p style="color:var(--text-muted);font-size:.875rem;">Current stock: <strong id="modal-current-stock" style="color:var(--text);"></strong></p>
        <div class="mb-3">
          <label class="form-label">Adjustment Type</label>
          <select name="type" class="form-select">
            <option value="in">➕ Stock In (Add)</option>
            <option value="out">➖ Stock Out (Remove)</option>
            <option value="adjustment">🔄 Set Exact Quantity</option>
          </select>
        </div>
        <div class="mb-3">
          <label class="form-label">Quantity</label>
          <input type="number" name="quantity" class="form-control" min="0" required placeholder="Enter quantity">
        </div>
        <div class="mb-3">
          <label class="form-label">Note</label>
          <input type="text" name="note" class="form-control" placeholder="e.g. Restocked from supplier">
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn-admin-outline" data-bs-dismiss="modal">Cancel</button>
        <button type="submit" class="btn-admin-primary"><i class="bi bi-save2 me-1"></i>Update Stock</button>
      </div>
    </form>
  </div></div>
</div>

<script>
function openStockModal(id, name, stock) {
  document.getElementById('modal-product-id').value = id;
  document.getElementById('modal-product-name').textContent = name;
  document.getElementById('modal-current-stock').textContent = stock + ' units';
  new bootstrap.Modal(document.getElementById('stockModal')).show();
}
</script>

<?php require_once __DIR__ . '/../../admin/includes/footer.php'; ?>
