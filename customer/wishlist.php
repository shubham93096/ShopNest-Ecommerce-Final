<?php
// customer/wishlist.php
define('PAGE_TITLE', 'My Wishlist');
require_once __DIR__ . '/../includes/auth_check.php';

$pdo = getDB();
$uid = $_SESSION[SESSION_USER]['id'];
$stmt = $pdo->prepare("
    SELECT p.*,c.name AS cat_name,
           COALESCE(AVG(r.rating),0) AS avg_rating,
           w.created_at AS wishlisted_at
    FROM wishlist w
    JOIN products p ON p.id=w.product_id
    LEFT JOIN categories c ON c.id=p.category_id
    LEFT JOIN reviews r ON r.product_id=p.id AND r.status='approved'
    WHERE w.user_id=?
    GROUP BY p.id, w.created_at
    ORDER BY w.created_at DESC
");
$stmt->execute([$uid]);
$wishlist = $stmt->fetchAll();
$wishlistIds = array_column($wishlist, 'id');

require_once __DIR__ . '/../includes/header.php';
?>

<div class="container py-4">
  <div class="breadcrumb-wrap">
    <nav><ol class="breadcrumb">
      <li class="breadcrumb-item"><a href="<?= APP_URL ?>/">Home</a></li>
      <li class="breadcrumb-item active">My Wishlist</li>
    </ol></nav>
  </div>

  <div class="row g-4">
    <div class="col-lg-3">
      <?php $u = currentUser(); ?>
      <div class="profile-sidebar">
        <div class="profile-avatar"><?= strtoupper(substr($u['name'],0,1)) ?></div>
        <div class="profile-name"><?= e($u['name']) ?></div>
        <div class="profile-email"><?= e($u['email']) ?></div>
        <ul class="profile-nav">
          <li><a href="<?= APP_URL ?>/customer/profile.php"><i class="bi bi-person"></i> Profile</a></li>
          <li><a href="<?= APP_URL ?>/customer/orders.php"><i class="bi bi-bag-check"></i> My Orders</a></li>
          <li><a href="<?= APP_URL ?>/customer/wishlist.php" class="active"><i class="bi bi-heart"></i> Wishlist</a></li>
          <li><a href="<?= APP_URL ?>/customer/logout.php" style="color:#f87171;"><i class="bi bi-box-arrow-right"></i> Logout</a></li>
        </ul>
      </div>
    </div>

    <div class="col-lg-9">
      <h1 style="font-size:1.4rem;font-weight:700;color:var(--text);margin-bottom:1.5rem;">
        <i class="bi bi-heart me-2" style="color:#f87171;"></i>My Wishlist
        <span style="font-size:.9rem;color:var(--text-muted);font-weight:400;">(<?= count($wishlist) ?> items)</span>
      </h1>

      <?php if(empty($wishlist)): ?>
      <div class="empty-state">
        <div class="empty-state-icon">💔</div>
        <h4>Your wishlist is empty</h4>
        <p>Save items you love and come back later!</p>
        <a href="<?= APP_URL ?>/products/index.php" class="btn btn-primary mt-2">Browse Products</a>
      </div>
      <?php else: ?>
      <div class="row g-4">
        <?php foreach($wishlist as $p):
          $img   = getProductPrimaryImage($p);
          $price = getEffectivePrice($p);
          $disc  = getDiscountPercent($p);
        ?>
        <div class="col-sm-6 col-md-4">
          <div class="product-card h-100">
            <div class="product-card-img">
              <a href="<?= APP_URL ?>/products/detail.php?slug=<?= e($p['slug']) ?>">
                <img src="<?= e($img) ?>" alt="<?= e($p['name']) ?>" loading="lazy" onerror="this.src='<?= APP_URL ?>/assets/images/no-image.png'">
              </a>
              <div class="product-badges">
                <?php if($disc > 0): ?><span class="badge-sale"><?= $disc ?>% OFF</span><?php endif; ?>
                <?php if($p['stock']==0): ?><span class="badge-out">Out of Stock</span><?php endif; ?>
              </div>
              <div class="product-actions" style="opacity:1;transform:none;">
                <a href="#" class="action-btn btn-wishlist wishlisted" data-id="<?= $p['id'] ?>" title="Remove from wishlist">
                  <i class="bi bi-heart-fill"></i>
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
              </div>
              <?php endif; ?>
              <div class="product-price">
                <span class="price-current"><?= formatPrice($price) ?></span>
                <?php if($disc > 0): ?><span class="price-original"><?= formatPrice($p['price']) ?></span><?php endif; ?>
              </div>
              <small style="color:var(--text-dim);">Added <?= date('d M Y', strtotime($p['wishlisted_at'])) ?></small>
            </div>
            <div class="product-card-footer">
              <button class="btn-add-cart" data-id="<?= $p['id'] ?>" <?= $p['stock']==0?'disabled':'' ?>>
                <?= $p['stock']==0 ? 'Out of Stock' : '<i class="bi bi-bag-plus me-1"></i>Move to Cart' ?>
              </button>
            </div>
          </div>
        </div>
        <?php endforeach; ?>
      </div>
      <?php endif; ?>
    </div>
  </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
