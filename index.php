<?php
// index.php — ShopNest Homepage
define('PAGE_TITLE', 'Home');
define('META_DESC', 'ShopNest — Discover premium products at unbeatable prices. Electronics, Fashion, Home & more.');
require_once __DIR__ . '/includes/functions.php';

$featured = [];
$cats = [];
$newArrivals = [];
$wishlistIds = [];

try {
    $pdo = getDB();

    // Featured Products
    $featured = $pdo->query("
        SELECT p.*, c.name AS cat_name, c.slug AS cat_slug,
               COALESCE(AVG(r.rating),0) AS avg_rating,
               COUNT(r.id) AS review_count
        FROM products p
        LEFT JOIN categories c ON c.id = p.category_id
        LEFT JOIN reviews r ON r.product_id = p.id AND r.status='approved'
        WHERE p.status=1 AND p.is_featured=1
        GROUP BY p.id, c.name, c.slug
        ORDER BY p.created_at DESC
        LIMIT 8
    ")->fetchAll();

    // Categories with product count
    $cats = $pdo->query("
        SELECT c.*, COUNT(p.id) AS product_count
        FROM categories c
        LEFT JOIN products p ON p.category_id=c.id AND p.status=1
        WHERE c.status=1
        GROUP BY c.id
        ORDER BY c.sort_order
    ")->fetchAll();

    // New arrivals
    $newArrivals = $pdo->query("
        SELECT p.*, c.name AS cat_name,
               COALESCE(AVG(r.rating),0) AS avg_rating
        FROM products p
        LEFT JOIN categories c ON c.id=p.category_id
        LEFT JOIN reviews r ON r.product_id=p.id AND r.status='approved'
        WHERE p.status=1
        GROUP BY p.id, c.name
        ORDER BY p.created_at DESC
        LIMIT 4
    ")->fetchAll();

    $wishlistIds = getWishlistIds();
} catch (Throwable $e) {
    error_log("Index page query notice: " . $e->getMessage());
}

$catIcons = ['electronics'=>'🔌','fashion'=>'👗','home-kitchen'=>'🏠','sports-fitness'=>'🏋️','books'=>'📚','beauty'=>'💄'];

require_once __DIR__ . '/includes/header.php';
?>

<!-- ── Hero ─────────────────────────────────────────────── -->
<section class="hero">
  <div class="hero-grid"></div>
  <div class="container py-5">
    <div class="row align-items-center g-5">
      <div class="col-lg-6 hero-content">
        <span class="hero-eyebrow">⚡ New Arrivals Every Week</span>
        <h1>Shop <span>Premium Products</span> at Best Prices</h1>
        <p>Discover thousands of curated products across Electronics, Fashion, Home & more — delivered fast to your doorstep.</p>
        <div class="d-flex flex-wrap gap-3">
          <a href="<?= APP_URL ?>/products/index.php" class="btn btn-primary btn-lg px-4">
            <i class="bi bi-bag me-2"></i>Shop Now
          </a>
          <a href="<?= APP_URL ?>/products/index.php?featured=1" class="btn btn-outline-primary btn-lg px-4">
            View Featured
          </a>
        </div>
        <div class="d-flex gap-4 mt-4">
          <div>
            <div style="font-family:'Outfit',sans-serif;font-size:1.5rem;font-weight:800;color:#e2e8f0;">500+</div>
            <div style="font-size:.8rem;color:#94a3b8;">Products</div>
          </div>
          <div style="border-left:1px solid rgba(255,255,255,.1);padding-left:1.5rem;">
            <div style="font-family:'Outfit',sans-serif;font-size:1.5rem;font-weight:800;color:#e2e8f0;">10K+</div>
            <div style="font-size:.8rem;color:#94a3b8;">Customers</div>
          </div>
          <div style="border-left:1px solid rgba(255,255,255,.1);padding-left:1.5rem;">
            <div style="font-family:'Outfit',sans-serif;font-size:1.5rem;font-weight:800;color:#e2e8f0;">4.9★</div>
            <div style="font-size:.8rem;color:#94a3b8;">Avg. Rating</div>
          </div>
        </div>
      </div>
      <div class="col-lg-6 d-none d-lg-block hero-image text-center">
        <div style="background:linear-gradient(135deg,rgba(99,102,241,.2),rgba(168,85,247,.15));border-radius:24px;border:1px solid rgba(99,102,241,.2);padding:3rem;position:relative;">
          <div style="font-size:8rem;line-height:1;">🛍️</div>
          <div class="hero-floating-card card-1" style="bottom:10%;left:-15%;">
            <div style="display:flex;align-items:center;gap:.5rem;">
              <span style="color:#10b981;font-size:1.1rem;">✓</span>
              <div><div style="font-weight:600;font-size:.8rem;">Order Placed!</div><div style="font-size:.72rem;color:#94a3b8;">Delivered in 2 days</div></div>
            </div>
          </div>
          <div class="hero-floating-card card-2" style="top:10%;right:-10%;">
            <div style="display:flex;align-items:center;gap:.5rem;">
              <span style="font-size:1.1rem;">⭐</span>
              <div><div style="font-weight:600;font-size:.8rem;">4.9 Rating</div><div style="font-size:.72rem;color:#94a3b8;">2,400+ reviews</div></div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- ── Trust Badges ────────────────────────────────────── -->
<div style="background:var(--bg-card);border-top:1px solid var(--border-light);border-bottom:1px solid var(--border-light);">
  <div class="container">
    <div class="row g-0 text-center">
      <?php $badges = [['🚚','Free Delivery','On orders above ₹499'],['🔒','Secure Payment','100% Protected'],['↩️','Easy Returns','30-day return policy'],['🏆','Premium Quality','Verified products']]; ?>
      <?php foreach($badges as $b): ?>
      <div class="col-6 col-md-3 py-3 px-2" style="border-right:1px solid var(--border-light);">
        <div style="font-size:1.5rem;margin-bottom:.3rem;"><?= $b[0] ?></div>
        <div style="font-weight:600;font-size:.875rem;color:var(--text);"><?= $b[1] ?></div>
        <div style="font-size:.75rem;color:var(--text-muted);"><?= $b[2] ?></div>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
</div>

<!-- ── Shop by Category ───────────────────────────────── -->
<?php if($cats): ?>
<section class="section-py">
  <div class="container">
    <div class="section-heading text-center">
      <span class="eyebrow">Browse</span>
      <h2>Shop by Category</h2>
      <p>Find exactly what you need</p>
    </div>
    <div class="row g-3 justify-content-center">
      <?php foreach($cats as $cat): ?>
      <div class="col-6 col-sm-4 col-md-3 col-lg-2">
        <a href="<?= APP_URL ?>/products/index.php?category=<?= e($cat['slug']) ?>" class="category-card">
          <div class="category-icon"><?= $catIcons[$cat['slug']] ?? '🛒' ?></div>
          <h6><?= e($cat['name']) ?></h6>
          <small><?= $cat['product_count'] ?> items</small>
        </a>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>
<?php endif; ?>

<!-- ── Featured Products ──────────────────────────────── -->
<?php if($featured): ?>
<section class="section-py" style="padding-top:0;">
  <div class="container">
    <div class="section-heading d-flex justify-content-between align-items-end flex-wrap gap-2">
      <div>
        <span class="eyebrow">Handpicked</span>
        <h2>Featured Products</h2>
      </div>
      <a href="<?= APP_URL ?>/products/index.php?featured=1" class="btn btn-outline-primary btn-sm">
        View All <i class="bi bi-arrow-right ms-1"></i>
      </a>
    </div>
    <div class="row g-4">
      <?php foreach($featured as $p):
        $img     = getProductPrimaryImage($p);
        $price   = getEffectivePrice($p);
        $disc    = getDiscountPercent($p);
        $inWish  = in_array($p['id'], $wishlistIds);
      ?>
      <div class="col-6 col-sm-6 col-md-4 col-lg-3">
        <div class="product-card">
          <div class="product-card-img">
            <a href="<?= APP_URL ?>/products/detail.php?slug=<?= e($p['slug']) ?>">
              <img src="<?= e($img) ?>" alt="<?= e($p['name']) ?>" loading="lazy" onerror="this.src='<?= APP_URL ?>/assets/images/no-image.png'">
            </a>
            <div class="product-badges">
              <?php if($disc > 0): ?><span class="badge-sale"><?= $disc ?>% OFF</span><?php endif; ?>
              <?php if($p['is_featured']): ?><span class="badge-featured">⭐ Featured</span><?php endif; ?>
              <?php if($p['stock'] == 0): ?><span class="badge-out">Out of Stock</span><?php endif; ?>
            </div>
            <div class="product-actions">
              <a href="#" class="action-btn btn-wishlist <?= $inWish?'wishlisted':'' ?>" data-id="<?= $p['id'] ?>" title="Wishlist">
                <i class="bi bi-heart<?= $inWish?'-fill':'' ?>"></i>
              </a>
              <a href="<?= APP_URL ?>/products/detail.php?slug=<?= e($p['slug']) ?>" class="action-btn" title="View">
                <i class="bi bi-eye"></i>
              </a>
            </div>
          </div>
          <div class="product-card-body">
            <div class="product-category"><?= e($p['cat_name']) ?></div>
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
  </div>
</section>
<?php endif; ?>

<!-- ── Promo Banner ───────────────────────────────────── -->
<section class="section-py-sm" style="padding-top:0;">
  <div class="container">
    <div style="background:linear-gradient(135deg,rgba(99,102,241,.2),rgba(168,85,247,.15));border:1px solid rgba(99,102,241,.3);border-radius:20px;padding:3rem;text-align:center;position:relative;overflow:hidden;">
      <div style="position:absolute;inset:0;background:radial-gradient(ellipse at center,rgba(99,102,241,.1),transparent 70%);"></div>
      <div style="position:relative;z-index:1;">
        <h2 style="font-family:'Outfit',sans-serif;font-size:2rem;font-weight:800;color:#e2e8f0;margin-bottom:.75rem;">
          🎉 Special Offer — Up to <span class="text-gradient">50% OFF</span>
        </h2>
        <p style="color:#94a3b8;margin-bottom:1.5rem;">Limited time deals on top-rated products. Don't miss out!</p>
        <a href="<?= APP_URL ?>/products/index.php?sale=1" class="btn btn-primary btn-lg px-5">
          Shop the Sale <i class="bi bi-lightning-fill ms-1"></i>
        </a>
      </div>
    </div>
  </div>
</section>

<!-- ── New Arrivals ───────────────────────────────────── -->
<?php if($newArrivals): ?>
<section class="section-py" style="padding-top:0;">
  <div class="container">
    <div class="section-heading d-flex justify-content-between align-items-end flex-wrap gap-2">
      <div>
        <span class="eyebrow">Just Added</span>
        <h2>New Arrivals</h2>
      </div>
      <a href="<?= APP_URL ?>/products/index.php" class="btn btn-outline-primary btn-sm">
        All Products <i class="bi bi-arrow-right ms-1"></i>
      </a>
    </div>
    <div class="row g-4">
      <?php foreach($newArrivals as $p):
        $img = getProductPrimaryImage($p);
        $price = getEffectivePrice($p);
        $disc  = getDiscountPercent($p);
        $inWish = in_array($p['id'], $wishlistIds);
      ?>
      <div class="col-6 col-md-3">
        <div class="product-card">
          <div class="product-card-img">
            <a href="<?= APP_URL ?>/products/detail.php?slug=<?= e($p['slug']) ?>">
              <img src="<?= e($img) ?>" alt="<?= e($p['name']) ?>" loading="lazy" onerror="this.src='<?= APP_URL ?>/assets/images/no-image.png'">
            </a>
            <div class="product-badges">
              <?php if($disc > 0): ?><span class="badge-sale"><?= $disc ?>% OFF</span><?php endif; ?>
              <span class="badge-featured" style="background:linear-gradient(135deg,#10b981,#06b6d4);">NEW</span>
            </div>
            <div class="product-actions">
              <a href="#" class="action-btn btn-wishlist <?= $inWish?'wishlisted':'' ?>" data-id="<?= $p['id'] ?>">
                <i class="bi bi-heart<?= $inWish?'-fill':'' ?>"></i>
              </a>
            </div>
          </div>
          <div class="product-card-body">
            <div class="product-category"><?= e($p['cat_name']) ?></div>
            <div class="product-name">
              <a href="<?= APP_URL ?>/products/detail.php?slug=<?= e($p['slug']) ?>"><?= e($p['name']) ?></a>
            </div>
            <div class="product-price">
              <span class="price-current"><?= formatPrice($price) ?></span>
              <?php if($disc > 0): ?><span class="price-original"><?= formatPrice($p['price']) ?></span><?php endif; ?>
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
  </div>
</section>
<?php endif; ?>

<!-- ── Testimonials ───────────────────────────────────── -->
<section class="section-py-sm" style="background:var(--bg-card);border-top:1px solid var(--border-light);">
  <div class="container">
    <div class="section-heading text-center">
      <span class="eyebrow">What Customers Say</span>
      <h2>Trusted by Thousands</h2>
    </div>
    <div class="row g-4">
      <?php
      $testimonials = [
        ['name'=>'Rahul S.','city'=>'Mumbai','rating'=>5,'text'=>'Amazing quality products and super fast delivery! ShopNest has become my go-to shopping destination.'],
        ['name'=>'Priya M.','city'=>'Bangalore','rating'=>5,'text'=>'The product quality exceeded my expectations. Easy returns and excellent customer service!'],
        ['name'=>'Amit K.','city'=>'Delhi','rating'=>5,'text'=>'Best prices in the market and genuine products. The website is so smooth and easy to use.'],
      ];
      foreach($testimonials as $t): ?>
      <div class="col-md-4">
        <div class="review-card h-100">
          <div class="d-flex align-items-center gap-2 mb-3">
            <div class="reviewer-avatar"><?= strtoupper(substr($t['name'],0,1)) ?></div>
            <div>
              <div class="review-title"><?= e($t['name']) ?></div>
              <div class="review-meta"><?= e($t['city']) ?></div>
            </div>
            <div class="ms-auto stars"><?= str_repeat('★',$t['rating']) ?></div>
          </div>
          <p class="review-body">"<?= e($t['text']) ?>"</p>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
