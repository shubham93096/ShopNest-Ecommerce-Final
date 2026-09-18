<?php
// customer/cart.php
define('PAGE_TITLE', 'My Cart');
require_once __DIR__ . '/../includes/auth_check.php';

$pdo   = getDB();
$items = getCartItems();
$total = getCartTotal();
$count = count($items);
$shipping = $total >= 499 ? 0 : 49;
$tax      = round($total * 0.05, 2);
$grand    = $total + $shipping + $tax;

require_once __DIR__ . '/../includes/header.php';
?>

<div class="container py-4">
  <div class="breadcrumb-wrap">
    <nav><ol class="breadcrumb">
      <li class="breadcrumb-item"><a href="<?= APP_URL ?>/">Home</a></li>
      <li class="breadcrumb-item active">My Cart</li>
    </ol></nav>
  </div>

  <h1 style="font-family:'Outfit',sans-serif;font-weight:700;font-size:1.7rem;color:var(--text);margin-bottom:1.5rem;">
    <i class="bi bi-bag me-2" style="color:var(--primary-light);"></i>Shopping Cart
    <span style="font-size:1rem;color:var(--text-muted);font-weight:400;"> (<?= $count ?> items)</span>
  </h1>

  <?php if(empty($items)): ?>
  <div class="empty-state">
    <div class="empty-state-icon">🛒</div>
    <h4>Your cart is empty</h4>
    <p>Looks like you haven't added anything yet.</p>
    <a href="<?= APP_URL ?>/products/index.php" class="btn btn-primary mt-3 px-4">
      <i class="bi bi-bag me-2"></i>Start Shopping
    </a>
  </div>

  <?php else: ?>
  <div class="row g-4">
    <div class="col-lg-8">
      <div class="card">
        <div class="card-body p-0">
          <div class="table-responsive">
            <table class="table cart-table mb-0">
              <thead>
                <tr>
                  <th style="min-width:280px;">Product</th>
                  <th>Price</th>
                  <th>Quantity</th>
                  <th>Total</th>
                  <th></th>
                </tr>
              </thead>
              <tbody>
                <?php foreach($items as $item):
                  $itPrice = getEffectivePrice($item);
                  $itTotal = $itPrice * $item['quantity'];
                  $itImg   = getProductPrimaryImage($item);
                ?>
                <tr>
                  <td>
                    <div class="d-flex align-items-center gap-3">
                      <a href="<?= APP_URL ?>/products/detail.php?slug=<?= e($item['slug']) ?>">
                        <img src="<?= e($itImg) ?>" class="cart-product-img" alt="<?= e($item['name']) ?>" onerror="this.src='<?= APP_URL ?>/assets/images/no-image.png'">
                      </a>
                      <div>
                        <a href="<?= APP_URL ?>/products/detail.php?slug=<?= e($item['slug']) ?>" style="color:var(--text);font-weight:600;font-size:.9rem;"><?= e($item['name']) ?></a>
                        <?php if($item['stock'] <= 5 && $item['stock'] > 0): ?>
                        <div style="color:#fcd34d;font-size:.75rem;">⚠️ Only <?= $item['stock'] ?> left</div>
                        <?php endif; ?>
                      </div>
                    </div>
                  </td>
                  <td>
                    <span style="color:var(--text);font-weight:600;"><?= formatPrice($itPrice) ?></span>
                    <?php if($item['sale_price']): ?>
                    <div style="color:var(--text-dim);font-size:.75rem;text-decoration:line-through;"><?= formatPrice($item['price']) ?></div>
                    <?php endif; ?>
                  </td>
                  <td>
                    <div class="qty-stepper">
                      <button class="qty-btn" data-dir="down" data-id="<?= $item['product_id'] ?>">–</button>
                      <input class="qty-value" type="text" value="<?= $item['quantity'] ?>" readonly>
                      <button class="qty-btn" data-dir="up" data-id="<?= $item['product_id'] ?>">+</button>
                    </div>
                  </td>
                  <td>
                    <span style="color:var(--text);font-weight:700;" data-total="<?= $item['product_id'] ?>">
                      <?= formatPrice($itTotal) ?>
                    </span>
                  </td>
                  <td>
                    <button class="btn-icon delete btn-remove-cart" data-id="<?= $item['product_id'] ?>" title="Remove">
                      <i class="bi bi-trash"></i>
                    </button>
                  </td>
                </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        </div>
        <div class="card-footer d-flex justify-content-between align-items-center flex-wrap gap-2">
          <a href="<?= APP_URL ?>/products/index.php" class="btn btn-outline-primary btn-sm">
            <i class="bi bi-arrow-left me-1"></i>Continue Shopping
          </a>
          <small style="color:var(--text-muted);">Prices include applicable taxes</small>
        </div>
      </div>
    </div>

    <div class="col-lg-4">
      <div class="order-summary-card">
        <h5 style="color:var(--text);font-weight:700;margin-bottom:1.5rem;padding-bottom:.75rem;border-bottom:1px solid var(--border-light);">
          <i class="bi bi-receipt me-2" style="color:var(--primary-light);"></i>Order Summary
        </h5>

        <div class="summary-row">
          <span>Subtotal (<?= $count ?> items)</span>
          <span style="color:var(--text);"><?= formatPrice($total) ?></span>
        </div>
        <div class="summary-row">
          <span>Shipping</span>
          <span style="color:<?= $shipping==0?'var(--success)':'var(--text)' ?>;">
            <?= $shipping == 0 ? '🆓 Free' : formatPrice($shipping) ?>
          </span>
        </div>
        <div class="summary-row">
          <span>GST (5%)</span>
          <span style="color:var(--text);"><?= formatPrice($tax) ?></span>
        </div>

        <?php if($total < 499): ?>
        <div style="background:rgba(245,158,11,.1);border:1px solid rgba(245,158,11,.3);border-radius:8px;padding:.75rem;margin:.75rem 0;font-size:.8rem;color:#fcd34d;">
          ⚡ Add <?= formatPrice(499 - $total) ?> more for free shipping!
        </div>
        <?php endif; ?>

        <div class="summary-row total">
          <span>Total</span>
          <span id="cart-grand-total"><?= formatPrice($grand) ?></span>
        </div>

        <a href="<?= APP_URL ?>/customer/checkout.php" class="btn btn-primary w-100 btn-lg mt-3">
          <i class="bi bi-credit-card me-2"></i>Proceed to Checkout
        </a>
        <div class="text-center mt-2">
          <small style="color:var(--text-dim);">🔒 Secure Checkout — SSL Encrypted</small>
        </div>
      </div>
    </div>
  </div>
  <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
