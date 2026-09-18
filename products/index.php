<?php
// products/index.php — Product Listing Page
define('PAGE_TITLE', 'Products');
require_once __DIR__ . '/../includes/functions.php';

$pdo = getDB();

// Filters
$q        = trim($_GET['q'] ?? '');
$catSlug  = trim($_GET['category'] ?? '');
$sort     = $_GET['sort'] ?? 'newest';
$minPrice = (float)($_GET['min_price'] ?? 0);
$maxPrice = (float)($_GET['max_price'] ?? 100000);
$featured = isset($_GET['featured']);
$sale     = isset($_GET['sale']);
$page     = max(1, (int)($_GET['page'] ?? 1));

// Category lookup
$catId = null;
$catName = 'All Products';
if ($catSlug) {
    $cs = $pdo->prepare("SELECT * FROM categories WHERE slug=?");
    $cs->execute([$catSlug]);
    $cat = $cs->fetch();
    if ($cat) { $catId = $cat['id']; $catName = $cat['name']; }
}

// Build query
$where = ['p.status=1'];
$params = [];
if ($q)        { $where[] = 'p.name LIKE ?'; $params[] = "%$q%"; }
if ($catId)    { $where[] = 'p.category_id=?'; $params[] = $catId; }
if ($minPrice) { $where[] = 'p.price >= ?'; $params[] = $minPrice; }
if ($maxPrice < 100000) { $where[] = 'p.price <= ?'; $params[] = $maxPrice; }
if ($featured) { $where[] = 'p.is_featured=1'; }
if ($sale)     { $where[] = 'p.sale_price IS NOT NULL AND p.sale_price > 0'; }

$whereSql = 'WHERE ' . implode(' AND ', $where);

$orderSql = match($sort) {
    'price_asc'  => 'p.price ASC',
    'price_desc' => 'p.price DESC',
    'rating'     => 'avg_rating DESC',
    'name'       => 'p.name ASC',
    default      => 'p.created_at DESC',
};

$countStmt = $pdo->prepare("SELECT COUNT(DISTINCT p.id) FROM products p LEFT JOIN categories c ON c.id=p.category_id $whereSql");
$countStmt->execute($params);
$total = (int)$countStmt->fetchColumn();

$pagi = paginate($total, PRODUCTS_PER_PAGE, $page, APP_URL . '/products/index.php?' . http_build_query(array_filter(['q'=>$q,'category'=>$catSlug,'sort'=>$sort,'min_price'=>$minPrice?:null,'max_price'=>$maxPrice<100000?$maxPrice:null,'featured'=>$featured?'1':null,'sale'=>$sale?'1':null])));

$stmt = $pdo->prepare("
    SELECT p.*, c.name AS cat_name, c.slug AS cat_slug,
           COALESCE(AVG(r.rating),0) AS avg_rating,
           COUNT(r.id) AS review_count
    FROM products p
    LEFT JOIN categories c ON c.id=p.category_id
    LEFT JOIN reviews r ON r.product_id=p.id AND r.status='approved'
    $whereSql
    GROUP BY p.id
    ORDER BY $orderSql
    LIMIT ? OFFSET ?
");
$stmt->execute([...$params, PRODUCTS_PER_PAGE, $pagi['offset']]);
$products = $stmt->fetchAll();

$categories = $pdo->query("SELECT c.*,COUNT(p.id) AS cnt FROM categories c LEFT JOIN products p ON p.category_id=c.id AND p.status=1 WHERE c.status=1 GROUP BY c.id ORDER BY c.sort_order")->fetchAll();
$wishlistIds = getWishlistIds();
$priceRange  = $pdo->query("SELECT MIN(price) AS mn, MAX(price) AS mx FROM products WHERE status=1")->fetch();

define('META_DESC', "Browse $total products" . ($catName !== 'All Products' ? " in $catName" : '') . " — ShopNest");
require_once __DIR__ . '/../includes/header.php';
?>

<div class="container py-4">
  <!-- Breadcrumb -->
  <div class="breadcrumb-wrap">
    <nav><ol class="breadcrumb">
      <li class="breadcrumb-item"><a href="<?= APP_URL ?>/">Home</a></li>
      <li class="breadcrumb-item<?= $catSlug?'':' active' ?>"><a href="<?= APP_URL ?>/products/index.php">Products</a></li>
      <?php if($catSlug): ?><li class="breadcrumb-item active"><?= e($catName) ?></li><?php endif; ?>
    </ol></nav>
  </div>

  <div class="row g-4">
    <!-- ── Sidebar Filter ── -->
    <div class="col-lg-3">
      <div class="filter-sidebar">
        <div class="filter-title d-flex justify-content-between align-items-center">
          <span><i class="bi bi-funnel me-2"></i>Filters</span>
          <a href="<?= APP_URL ?>/products/index.php" class="btn-icon view" style="text-decoration:none;font-size:.75rem;width:auto;padding:0 .5rem;">Clear</a>
        </div>

        <form method="GET" id="filter-form">
          <?php if($q): ?><input type="hidden" name="q" value="<?= e($q) ?>"><?php endif; ?>

          <!-- Categories -->
          <div class="filter-group">
            <div class="filter-group-title">Category</div>
            <?php foreach($categories as $c): ?>
            <div class="filter-check">
              <div class="form-check">
                <input class="form-check-input" type="radio" name="category" id="cat-<?= $c['id'] ?>"
                       value="<?= e($c['slug']) ?>" <?= $catSlug===$c['slug']?'checked':'' ?>
                       onchange="this.form.submit()">
                <label class="form-check-label" for="cat-<?= $c['id'] ?>">
                  <?= e($c['name']) ?> <span style="color:var(--text-dim);font-size:.75rem;">(<?= $c['cnt'] ?? 0 ?>)</span>
                </label>
              </div>
            </div>
            <?php endforeach; ?>
          </div>

          <!-- Price Range -->
          <div class="filter-group">
            <div class="filter-group-title">Price Range</div>
            <div class="mb-2">
              <span id="price-display" style="color:var(--text-muted);font-size:.85rem;">
                ₹<?= number_format($minPrice) ?> – ₹<?= number_format($maxPrice < 100000 ? $maxPrice : ($priceRange['mx']??10000)) ?>
              </span>
            </div>
            <div class="price-range">
              <input type="number" id="price-min" name="min_price" class="form-control price-input" value="<?= $minPrice ?>" min="0" max="100000" placeholder="Min">
              <span style="color:var(--text-dim);">–</span>
              <input type="number" id="price-max" name="max_price" class="form-control price-input" value="<?= $maxPrice < 100000 ? $maxPrice : '' ?>" min="0" max="100000" placeholder="Max">
            </div>
            <button type="submit" class="btn btn-primary btn-sm w-100 mt-2">Apply</button>
          </div>

          <!-- Other Filters -->
          <div class="filter-group">
            <div class="filter-group-title">Special</div>
            <div class="form-check mb-2">
              <input class="form-check-input" type="checkbox" name="featured" id="chk-featured" value="1"
                     <?= $featured?'checked':'' ?> onchange="this.form.submit()">
              <label class="form-check-label" for="chk-featured">⭐ Featured</label>
            </div>
            <div class="form-check">
              <input class="form-check-input" type="checkbox" name="sale" id="chk-sale" value="1"
                     <?= $sale?'checked':'' ?> onchange="this.form.submit()">
              <label class="form-check-label" for="chk-sale">🔥 On Sale</label>
            </div>
          </div>
        </form>
      </div>
    </div>

    <!-- ── Product Grid ── -->
    <div class="col-lg-9">
      <!-- Header bar -->
      <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-4">
        <div>
          <h1 style="font-size:1.4rem;font-weight:700;color:var(--text);margin:0;">
            <?= $q ? "Search: \"$q\"" : e($catName) ?>
          </h1>
          <small style="color:var(--text-muted);"><?= number_format($total) ?> products found</small>
        </div>
        <div class="d-flex align-items-center gap-2">
          <?php if($q): ?>
          <form action="<?= APP_URL ?>/products/index.php" method="GET" class="d-flex">
            <?php if($catSlug): ?><input type="hidden" name="category" value="<?= e($catSlug) ?>"><?php endif; ?>
            <input type="text" name="q" value="<?= e($q) ?>" class="form-control form-control-sm" placeholder="Search…" style="width:160px;">
            <button class="btn btn-sm btn-primary ms-1"><i class="bi bi-search"></i></button>
          </form>
          <?php endif; ?>
          <select class="form-select form-select-sm" style="width:auto;" onchange="location='<?= APP_URL ?>/products/index.php?<?= http_build_query(array_filter(['q'=>$q,'category'=>$catSlug])) ?>&sort='+this.value">
            <option value="newest" <?= $sort=='newest'?'selected':'' ?>>Newest</option>
            <option value="price_asc" <?= $sort=='price_asc'?'selected':'' ?>>Price: Low–High</option>
            <option value="price_desc" <?= $sort=='price_desc'?'selected':'' ?>>Price: High–Low</option>
            <option value="rating" <?= $sort=='rating'?'selected':'' ?>>Top Rated</option>
            <option value="name" <?= $sort=='name'?'selected':'' ?>>A–Z</option>
          </select>
        </div>
      </div>

      <!-- Products -->
      <?php if(empty($products)): ?>
      <div class="empty-state">
        <div class="empty-state-icon">🔍</div>
        <h4>No products found</h4>
        <p>Try adjusting your filters or search term.</p>
        <a href="<?= APP_URL ?>/products/index.php" class="btn btn-primary mt-2">View All Products</a>
      </div>
      <?php else: ?>
      <div class="row g-4">
        <?php foreach($products as $p):
          $img   = getProductPrimaryImage($p);
          $price = getEffectivePrice($p);
          $disc  = getDiscountPercent($p);
          $inWish = in_array($p['id'], $wishlistIds);
        ?>
        <div class="col-6 col-md-4">
          <div class="product-card h-100">
            <div class="product-card-img">
              <a href="<?= APP_URL ?>/products/detail.php?slug=<?= e($p['slug']) ?>">
                <img src="<?= e($img) ?>" alt="<?= e($p['name']) ?>" loading="lazy" onerror="this.src='<?= APP_URL ?>/assets/images/no-image.png'">
              </a>
              <div class="product-badges">
                <?php if($disc > 0): ?><span class="badge-sale"><?= $disc ?>% OFF</span><?php endif; ?>
                <?php if($p['stock'] == 0): ?><span class="badge-out">Out of Stock</span><?php endif; ?>
              </div>
              <div class="product-actions">
                <a href="#" class="action-btn btn-wishlist <?= $inWish?'wishlisted':'' ?>" data-id="<?= $p['id'] ?>">
                  <i class="bi bi-heart<?= $inWish?'-fill':'' ?>"></i>
                </a>
                <a href="<?= APP_URL ?>/products/detail.php?slug=<?= e($p['slug']) ?>" class="action-btn">
                  <i class="bi bi-eye"></i>
                </a>
              </div>
            </div>
            <div class="product-card-body flex-grow-1">
              <div class="product-category"><?= e($p['cat_name'] ?? '') ?></div>
              <div class="product-name">
                <a href="<?= APP_URL ?>/products/detail.php?slug=<?= e($p['slug']) ?>"><?= e($p['name']) ?></a>
              </div>
              <?php if($p['avg_rating'] > 0): ?>
              <div class="product-rating">
                <span class="stars"><?= renderStars($p['avg_rating']) ?></span>
                <span class="rating-count">(<?= $p['review_count'] ?>)</span>
              </div>
              <?php endif; ?>
              <div class="product-price">
                <span class="price-current"><?= formatPrice($price) ?></span>
                <?php if($disc > 0): ?>
                <span class="price-original"><?= formatPrice($p['price']) ?></span>
                <span class="price-discount">-<?= $disc ?>%</span>
                <?php endif; ?>
              </div>
            </div>
            <div class="product-card-footer">
              <button class="btn-add-cart" data-id="<?= $p['id'] ?>" <?= $p['stock']==0?'disabled':'' ?>>
                <?= $p['stock']==0 ? 'Out of Stock' : '<i class="bi bi-bag-plus me-1"></i>Add to Cart' ?>
              </button>
            </div>
          </div>
        </div>
        <?php endforeach; ?>
      </div>

      <!-- Pagination -->
      <div class="mt-4"><?= renderPagination($pagi) ?></div>
      <?php endif; ?>
    </div>
  </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
