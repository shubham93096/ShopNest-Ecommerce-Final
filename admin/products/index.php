<?php
// admin/products/index.php
define('ADMIN_PAGE_TITLE', 'Products');
require_once __DIR__ . '/../../admin/includes/header.php';

$pdo   = getDB();
$page  = max(1,(int)($_GET['page']??1));
$q     = trim($_GET['q']??'');
$cat   = (int)($_GET['cat']??0);
$where = ['p.id > 0'];
$params = [];
if ($q)   { $where[] = 'p.name LIKE ?'; $params[] = "%$q%"; }
if ($cat) { $where[] = 'p.category_id=?'; $params[] = $cat; }
$wsql = 'WHERE '.implode(' AND ',$where);

$cntS = $pdo->prepare("SELECT COUNT(*) FROM products p $wsql");
$cntS->execute($params);
$total = (int)$cntS->fetchColumn();
$pagi  = paginate($total, ADMIN_PER_PAGE, $page, APP_URL.'/admin/products/index.php?'.http_build_query(array_filter(['q'=>$q,'cat'=>$cat])));

$stmt = $pdo->prepare("SELECT p.*,c.name AS cat_name FROM products p LEFT JOIN categories c ON c.id=p.category_id $wsql ORDER BY p.created_at DESC LIMIT ? OFFSET ?");
$stmt->execute([...$params, ADMIN_PER_PAGE, $pagi['offset']]);
$products = $stmt->fetchAll();

$cats = $pdo->query("SELECT id,name FROM categories ORDER BY name")->fetchAll();

// Handle delete
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $pdo->prepare("UPDATE products SET status=0 WHERE id=?")->execute([(int)$_GET['delete']]);
    setFlash('success','Product deleted (deactivated).');
    redirect(APP_URL.'/admin/products/index.php');
}
?>

<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-3">
  <div>
    <h2 style="color:var(--text);font-weight:700;margin:0;">Products</h2>
    <div style="color:var(--text-muted);font-size:.875rem;"><?= number_format($total) ?> products</div>
  </div>
  <a href="<?= APP_URL ?>/admin/products/add.php" class="btn-admin-primary">
    <i class="bi bi-plus-lg"></i> Add Product
  </a>
</div>

<div class="admin-table-card">
  <div class="admin-table-header">
    <form method="GET" class="d-flex gap-2 flex-wrap flex-grow-1">
      <input type="text" name="q" class="form-control" placeholder="Search products…" value="<?= e($q) ?>" style="max-width:220px;">
      <select name="cat" class="form-select" style="max-width:180px;" onchange="this.form.submit()">
        <option value="">All Categories</option>
        <?php foreach($cats as $c): ?>
        <option value="<?= $c['id'] ?>" <?= $cat==$c['id']?'selected':'' ?>><?= e($c['name']) ?></option>
        <?php endforeach; ?>
      </select>
      <button type="submit" class="btn-admin-primary"><i class="bi bi-search"></i></button>
      <?php if($q||$cat): ?><a href="<?= APP_URL ?>/admin/products/index.php" class="btn-admin-outline">Clear</a><?php endif; ?>
    </form>
  </div>
  <div class="table-responsive">
    <table class="admin-table" id="products-table">
      <thead><tr>
        <th>Image</th><th>Name</th><th>Category</th><th>Price</th><th>Sale Price</th><th>Stock</th><th>Status</th><th>Actions</th>
      </tr></thead>
      <tbody>
        <?php foreach($products as $p):
          $imgs = getProductImages($p);
          $img  = !empty($imgs) ? getProductImageUrl($imgs[0]) : APP_URL.'/assets/images/no-image.png';
          $price = getEffectivePrice($p);
        ?>
        <tr>
          <td><img src="<?= e($img) ?>" class="product-img-cell" alt="" onerror="this.src='<?= APP_URL ?>/assets/images/no-image.png'"></td>
          <td>
            <div class="cell-primary"><?= e($p['name']) ?></div>
            <div class="cell-muted"><?= e($p['sku']??'') ?></div>
          </td>
          <td style="color:var(--text-muted);"><?= e($p['cat_name']??'-') ?></td>
          <td style="color:var(--text);"><?= formatPrice($p['price']) ?></td>
          <td style="color:<?= $p['sale_price']?'#6ee7b7':'var(--text-dim)' ?>;">
            <?= $p['sale_price'] ? formatPrice($p['sale_price']) : '—' ?>
          </td>
          <td>
            <span style="color:<?= $p['stock']<=5?($p['stock']==0?'#fca5a5':'#fcd34d'):'#6ee7b7' ?>;font-weight:600;">
              <?= $p['stock'] ?>
            </span>
          </td>
          <td>
            <span class="status-badge <?= $p['status']?'active':'inactive' ?>">
              <span class="status-dot"></span><?= $p['status']?'Active':'Inactive' ?>
            </span>
          </td>
          <td>
            <div class="d-flex gap-1">
              <a href="<?= APP_URL ?>/products/detail.php?slug=<?= e($p['slug']) ?>" target="_blank" class="btn-icon view" title="View"><i class="bi bi-eye"></i></a>
              <a href="<?= APP_URL ?>/admin/products/edit.php?id=<?= $p['id'] ?>" class="btn-icon edit" title="Edit"><i class="bi bi-pencil"></i></a>
              <a href="?delete=<?= $p['id'] ?>" class="btn-icon delete" data-confirm="Delete this product?" title="Delete"><i class="bi bi-trash"></i></a>
            </div>
          </td>
        </tr>
        <?php endforeach; ?>
        <?php if(empty($products)): ?>
        <tr><td colspan="8" class="text-center py-4" style="color:var(--text-muted);">No products found.</td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
  <div class="table-pagination">
    <div class="table-pagination-info">Showing <?= count($products) ?> of <?= $total ?></div>
    <?= renderPagination($pagi) ?>
  </div>
</div>

<?php require_once __DIR__ . '/../../admin/includes/footer.php'; ?>
