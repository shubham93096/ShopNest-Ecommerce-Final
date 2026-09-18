<?php
// products/detail.php — Product Detail Page
require_once __DIR__ . '/../includes/functions.php';

$slug = trim($_GET['slug'] ?? '');
if (!$slug) redirect(APP_URL . '/products/index.php');

$pdo = getDB();
$stmt = $pdo->prepare("
    SELECT p.*, c.name AS cat_name, c.slug AS cat_slug,
           COALESCE(AVG(r.rating),0) AS avg_rating,
           COUNT(r.id) AS review_count
    FROM products p
    LEFT JOIN categories c ON c.id=p.category_id
    LEFT JOIN reviews r ON r.product_id=p.id AND r.status='approved'
    WHERE p.slug=? AND p.status=1
    GROUP BY p.id
");
$stmt->execute([$slug]);
$product = $stmt->fetch();
if (!$product) redirect(APP_URL . '/products/index.php');

define('PAGE_TITLE', $product['name']);
define('META_DESC', $product['short_desc'] ?: substr($product['description'],0,160));

// Reviews
$reviews = $pdo->prepare("
    SELECT r.*,u.name AS user_name FROM reviews r
    LEFT JOIN users u ON u.id=r.user_id
    WHERE r.product_id=? AND r.status='approved'
    ORDER BY r.created_at DESC LIMIT 10
");
$reviews->execute([$product['id']]);
$reviews = $reviews->fetchAll();

// Related products
$related = $pdo->prepare("
    SELECT p.*,c.name AS cat_name, COALESCE(AVG(r.rating),0) AS avg_rating
    FROM products p
    LEFT JOIN categories c ON c.id=p.category_id
    LEFT JOIN reviews r ON r.product_id=p.id
    WHERE p.category_id=? AND p.id != ? AND p.status=1
    GROUP BY p.id ORDER BY p.is_featured DESC, RAND() LIMIT 4
");
$related->execute([$product['category_id'], $product['id']]);
$related = $related->fetchAll();

$images     = getProductImages($product);
$mainImg    = !empty($images) ? getProductImageUrl($images[0]) : getProductImageUrl('');
$price      = getEffectivePrice($product);
$disc       = getDiscountPercent($product);
$wishlistIds = getWishlistIds();
$inWish     = in_array($product['id'], $wishlistIds);

// Post review
$reviewMsg = $reviewErr = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_review'])) {
    if (!isLoggedIn()) redirect(APP_URL . '/customer/login.php');
    verifyCsrf();
    $rating  = (int)($_POST['rating'] ?? 5);
    $title   = trim($_POST['review_title'] ?? '');
    $comment = trim($_POST['review_comment'] ?? '');
    if ($rating < 1 || $rating > 5) $reviewErr = 'Invalid rating.';
    elseif (empty($comment)) $reviewErr = 'Please write your review.';
    else {
        $pdo->prepare("INSERT INTO reviews (user_id,product_id,rating,title,comment,status,created_at) VALUES (?,?,?,?,?,'approved',NOW())")
            ->execute([$_SESSION[SESSION_USER]['id'], $product['id'], $rating, $title, $comment]);
        $reviewMsg = 'Review submitted! Thank you.';
        redirect(APP_URL . '/products/detail.php?slug=' . urlencode($slug) . '#reviews-section');
    }
}

require_once __DIR__ . '/../includes/header.php';
?>

<div class="container py-4">
  <!-- Breadcrumb -->
  <div class="breadcrumb-wrap">
    <nav><ol class="breadcrumb">
      <li class="breadcrumb-item"><a href="<?= APP_URL ?>/">Home</a></li>
      <li class="breadcrumb-item"><a href="<?= APP_URL ?>/products/index.php">Products</a></li>
      <li class="breadcrumb-item"><a href="<?= APP_URL ?>/products/index.php?category=<?= e($product['cat_slug']) ?>"><?= e($product['cat_name']) ?></a></li>
      <li class="breadcrumb-item active"><?= e($product['name']) ?></li>
    </ol></nav>
  </div>

  <!-- Product Main -->
  <div class="row g-5 mb-5">
    <!-- Gallery -->
    <div class="col-lg-5 detail-gallery">
      <div class="main-image">
        <img id="main-product-img" src="<?= e($mainImg) ?>" alt="<?= e($product['name']) ?>" onerror="this.src='<?= APP_URL ?>/assets/images/no-image.png'">
      </div>
      <?php if(count($images) > 1): ?>
      <div class="thumb-row">
        <?php foreach($images as $i=>$img): ?>
        <div class="thumb <?= $i===0?'active':'' ?>" data-src="<?= e(getProductImageUrl($img)) ?>">
          <img src="<?= e(getProductImageUrl($img)) ?>" alt="" onerror="this.src='<?= APP_URL ?>/assets/images/no-image.png'">
        </div>
        <?php endforeach; ?>
      </div>
      <?php endif; ?>
    </div>

    <!-- Details -->
    <div class="col-lg-7">
      <div class="product-category"><?= e($product['cat_name']) ?></div>
      <h1 class="detail-title"><?= e($product['name']) ?></h1>

      <?php if($product['brand']): ?>
      <div style="color:var(--text-muted);font-size:.875rem;margin-bottom:.75rem;">
        Brand: <strong style="color:var(--text);"><?= e($product['brand']) ?></strong>
        <?php if($product['sku']): ?> &nbsp;•&nbsp; SKU: <span style="color:var(--text-dim);"><?= e($product['sku']) ?></span><?php endif; ?>
      </div>
      <?php endif; ?>

      <!-- Rating -->
      <?php if($product['avg_rating'] > 0): ?>
      <div class="d-flex align-items-center gap-2 mb-1">
        <span class="stars" style="font-size:1.1rem;"><?= renderStars($product['avg_rating']) ?></span>
        <span style="color:var(--text-muted);font-size:.875rem;"><?= number_format($product['avg_rating'],1) ?> (<?= $product['review_count'] ?> reviews)</span>
        <a href="#reviews-section" id="scroll-to-reviews" style="color:var(--primary-light);font-size:.8rem;">See reviews</a>
      </div>
      <?php endif; ?>

      <!-- Price -->
      <div class="d-flex align-items-baseline gap-3 my-3">
        <span style="font-family:'Outfit',sans-serif;font-size:2.2rem;font-weight:800;color:var(--text);"><?= formatPrice($price) ?></span>
        <?php if($disc > 0): ?>
        <span style="font-size:1rem;color:var(--text-dim);text-decoration:line-through;"><?= formatPrice($product['price']) ?></span>
        <span class="badge" style="background:linear-gradient(135deg,#f59e0b,#ef4444);font-size:.85rem;">-<?= $disc ?>% OFF</span>
        <?php endif; ?>
      </div>

      <!-- Stock -->
      <div class="mb-3">
        <?php if($product['stock'] > 10): ?>
        <span class="stock-indicator in-stock"><span class="stock-dot"></span>In Stock (<?= $product['stock'] ?> available)</span>
        <?php elseif($product['stock'] > 0): ?>
        <span class="stock-indicator low-stock"><span class="stock-dot"></span>Only <?= $product['stock'] ?> left!</span>
        <?php else: ?>
        <span class="stock-indicator out-stock"><span class="stock-dot"></span>Out of Stock</span>
        <?php endif; ?>
      </div>

      <!-- Short Desc -->
      <?php if($product['short_desc']): ?>
      <p style="color:var(--text-muted);font-size:.95rem;margin-bottom:1.5rem;"><?= e($product['short_desc']) ?></p>
      <?php endif; ?>

      <!-- Qty + Cart -->
      <?php if($product['stock'] > 0): ?>
      <div class="d-flex align-items-center gap-3 flex-wrap mb-3">
        <div class="qty-box">
          <button type="button" id="qty-down"><i class="bi bi-dash"></i></button>
          <input type="number" id="qty-input" value="1" min="1" max="<?= $product['stock'] ?>">
          <button type="button" id="qty-up"><i class="bi bi-plus"></i></button>
        </div>
        <button class="btn btn-primary btn-lg px-4 btn-add-cart" data-id="<?= $product['id'] ?>">
          <i class="bi bi-bag-plus me-2"></i>Add to Cart
        </button>
        <a href="#" class="btn btn-outline-primary btn-lg btn-wishlist <?= $inWish?'wishlisted':'' ?>" data-id="<?= $product['id'] ?>">
          <i class="bi bi-heart<?= $inWish?'-fill':'' ?> me-2"></i><?= $inWish ? 'Wishlisted' : 'Wishlist' ?>
        </a>
      </div>
      <?php else: ?>
      <div class="d-flex gap-3 mb-3">
        <button class="btn btn-secondary btn-lg px-4" disabled>Out of Stock</button>
        <a href="#" class="btn btn-outline-primary btn-lg btn-wishlist <?= $inWish?'wishlisted':'' ?>" data-id="<?= $product['id'] ?>">
          <i class="bi bi-heart<?= $inWish?'-fill':'' ?> me-2"></i>Wishlist
        </a>
      </div>
      <?php endif; ?>

      <!-- Delivery badges -->
      <div class="d-flex flex-wrap gap-2 mt-3">
        <span class="badge" style="background:rgba(16,185,129,.15);color:#6ee7b7;padding:.5rem .8rem;border-radius:8px;font-size:.8rem;font-weight:500;">🚚 Free Delivery on ₹499+</span>
        <span class="badge" style="background:rgba(6,182,212,.15);color:#67e8f9;padding:.5rem .8rem;border-radius:8px;font-size:.8rem;font-weight:500;">↩ 30-Day Returns</span>
        <span class="badge" style="background:rgba(99,102,241,.15);color:#a5b4fc;padding:.5rem .8rem;border-radius:8px;font-size:.8rem;font-weight:500;">🔒 Secure Payment</span>
      </div>
    </div>
  </div>

  <!-- Description & Reviews Tabs -->
  <div class="mb-5">
    <ul class="nav nav-pills mb-4 gap-2" id="product-tabs">
      <li class="nav-item">
        <button class="btn btn-outline-primary active" data-target="tab-desc" onclick="switchTab(this,'tab-desc')">Description</button>
      </li>
      <li class="nav-item">
        <button class="btn btn-outline-primary" data-target="tab-reviews" onclick="switchTab(this,'tab-reviews')">
          Reviews (<?= count($reviews) ?>)
        </button>
      </li>
    </ul>

    <!-- Description -->
    <div id="tab-desc" class="tab-pane">
      <div class="card p-4" style="line-height:1.8;">
        <p style="color:var(--text-muted);white-space:pre-line;"><?= nl2br(e($product['description'] ?: 'No description available.')) ?></p>
        <?php if($product['weight']): ?>
        <div class="mt-3 pt-3" style="border-top:1px solid var(--border-light);">
          <small style="color:var(--text-muted);">Weight: <?= e($product['weight']) ?> kg</small>
        </div>
        <?php endif; ?>
      </div>
    </div>

    <!-- Reviews -->
    <div id="tab-reviews" class="tab-pane d-none" id="reviews-section">
      <?php if($reviewMsg): ?>
      <div class="alert alert-success mb-3"><i class="bi bi-check-circle me-2"></i><?= e($reviewMsg) ?></div>
      <?php endif; ?>

      <div class="row g-4">
        <div class="col-lg-8">
          <?php if(empty($reviews)): ?>
          <div class="empty-state py-3">
            <div style="font-size:3rem;margin-bottom:1rem;">💬</div>
            <h5 style="color:var(--text);">No reviews yet</h5>
            <p>Be the first to review this product!</p>
          </div>
          <?php else: ?>
          <?php foreach($reviews as $r): ?>
          <div class="review-card">
            <div class="d-flex align-items-start gap-3 mb-2">
              <div class="reviewer-avatar"><?= strtoupper(substr($r['user_name']??'U',0,1)) ?></div>
              <div class="flex-grow-1">
                <div class="d-flex justify-content-between align-items-center">
                  <span class="review-title"><?= e($r['user_name'] ?? 'Anonymous') ?></span>
                  <span class="review-meta"><?= date('d M Y', strtotime($r['created_at'])) ?></span>
                </div>
                <div class="stars"><?= renderStars($r['rating']) ?></div>
              </div>
            </div>
            <?php if($r['title']): ?><div class="review-title mb-1"><?= e($r['title']) ?></div><?php endif; ?>
            <p class="review-body mb-0"><?= e($r['comment']) ?></p>
          </div>
          <?php endforeach; ?>
          <?php endif; ?>
        </div>
        <div class="col-lg-4">
          <?php if(isLoggedIn()): ?>
          <div class="card p-3">
            <h6 style="color:var(--text);font-weight:700;margin-bottom:1rem;">Write a Review</h6>
            <?php if($reviewErr): ?><div class="alert alert-danger"><?= e($reviewErr) ?></div><?php endif; ?>
            <form method="POST">
              <?= csrfInput() ?>
              <div class="mb-3">
                <label class="form-label">Rating</label>
                <div class="d-flex gap-2">
                  <?php for($i=1;$i<=5;$i++): ?>
                  <label style="cursor:pointer;font-size:1.5rem;color:<?= $i<=4?'#f59e0b':'#64748b' ?>;" title="<?= $i ?> star">
                    <input type="radio" name="rating" value="<?= $i ?>" style="display:none;" <?= $i==4?'checked':'' ?>>
                    ★
                  </label>
                  <?php endfor; ?>
                </div>
              </div>
              <div class="mb-2">
                <label class="form-label">Title (optional)</label>
                <input type="text" name="review_title" class="form-control" placeholder="Summary of your review">
              </div>
              <div class="mb-3">
                <label class="form-label">Your Review *</label>
                <textarea name="review_comment" class="form-control" rows="4" placeholder="Share your experience…" required></textarea>
              </div>
              <button type="submit" name="submit_review" class="btn btn-primary w-100">Submit Review</button>
            </form>
          </div>
          <?php else: ?>
          <div class="card p-3 text-center">
            <div style="font-size:2rem;margin-bottom:.5rem;">✍️</div>
            <p style="color:var(--text-muted);font-size:.9rem;">Please log in to write a review.</p>
            <a href="<?= APP_URL ?>/customer/login.php" class="btn btn-primary btn-sm">Login to Review</a>
          </div>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </div>

  <!-- Related Products -->
  <?php if($related): ?>
  <div class="mb-5">
    <div class="section-heading">
      <span class="eyebrow">You may also like</span>
      <h2>Related Products</h2>
    </div>
    <div class="row g-4">
      <?php foreach($related as $p):
        $ri   = getProductPrimaryImage($p);
        $rp   = getEffectivePrice($p);
        $rd   = getDiscountPercent($p);
        $rw   = in_array($p['id'],$wishlistIds);
      ?>
      <div class="col-6 col-md-3">
        <div class="product-card">
          <div class="product-card-img">
            <a href="<?= APP_URL ?>/products/detail.php?slug=<?= e($p['slug']) ?>">
              <img src="<?= e($ri) ?>" alt="<?= e($p['name']) ?>" loading="lazy" onerror="this.src='<?= APP_URL ?>/assets/images/no-image.png'">
            </a>
            <div class="product-badges">
              <?php if($rd > 0): ?><span class="badge-sale"><?= $rd ?>% OFF</span><?php endif; ?>
            </div>
          </div>
          <div class="product-card-body">
            <div class="product-name"><a href="<?= APP_URL ?>/products/detail.php?slug=<?= e($p['slug']) ?>"><?= e($p['name']) ?></a></div>
            <div class="product-price">
              <span class="price-current"><?= formatPrice($rp) ?></span>
              <?php if($rd > 0): ?><span class="price-original"><?= formatPrice($p['price']) ?></span><?php endif; ?>
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
  <?php endif; ?>
</div>

<script>
function switchTab(btn, id) {
  document.querySelectorAll('#product-tabs button').forEach(b => b.classList.remove('active'));
  btn.classList.add('active');
  document.querySelectorAll('.tab-pane').forEach(p => p.classList.add('d-none'));
  document.getElementById(id).classList.remove('d-none');
}
// Star rating interaction
document.querySelectorAll('.review-stars-input label').forEach(lbl => {
  lbl.addEventListener('mouseover', function() {
    const val = parseInt(this.title);
    document.querySelectorAll('.review-stars-input label').forEach((l,i) => {
      l.style.color = i < val ? '#f59e0b' : '#64748b';
    });
  });
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
