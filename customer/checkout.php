<?php
// customer/checkout.php
define('PAGE_TITLE', 'Checkout');
require_once __DIR__ . '/../includes/auth_check.php';

$pdo   = getDB();
$items = getCartItems();
if (empty($items)) redirect(APP_URL . '/customer/cart.php');

$user  = currentUser();
$pdo2  = getDB();
$stmt  = $pdo2->prepare("SELECT * FROM users WHERE id=?");
$stmt->execute([$user['id']]);
$profile = $stmt->fetch();

$subtotal = getCartTotal();
$shipping = $subtotal >= 499 ? 0 : 49;
$tax      = round($subtotal * 0.05, 2);
$total    = $subtotal + $shipping + $tax;

$errors = [];
$paymentMethods = PAYMENT_METHODS;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    $name    = trim($_POST['ship_name'] ?? '');
    $phone   = trim($_POST['ship_phone'] ?? '');
    $address = trim($_POST['ship_address'] ?? '');
    $city    = trim($_POST['ship_city'] ?? '');
    $state   = trim($_POST['ship_state'] ?? '');
    $pincode = trim($_POST['ship_pincode'] ?? '');
    $method  = $_POST['payment_method'] ?? 'cod';
    $notes   = trim($_POST['notes'] ?? '');

    if (empty($name))    $errors[] = 'Full name is required.';
    if (empty($phone))   $errors[] = 'Phone number is required.';
    if (empty($address)) $errors[] = 'Address is required.';
    if (empty($city))    $errors[] = 'City is required.';
    if (empty($state))   $errors[] = 'State is required.';
    if (empty($pincode)) $errors[] = 'Pincode is required.';
    if (!array_key_exists($method, $paymentMethods)) $errors[] = 'Invalid payment method.';

    if (empty($errors)) {
        // Re-fetch items & totals for safety
        $cartItems = getCartItems();
        if (empty($cartItems)) redirect(APP_URL . '/customer/cart.php');

        $sub  = 0;
        foreach($cartItems as $ci) $sub += getEffectivePrice($ci) * $ci['quantity'];
        $shp  = $sub >= 499 ? 0 : 49;
        $tx   = round($sub * 0.05, 2);
        $tot  = $sub + $shp + $tx;

        $orderNum = generateOrderNumber();

        // Insert order — 18 columns, 18 bound parameters
        $os = $pdo->prepare("
            INSERT INTO orders
              (order_number,user_id,subtotal,discount,shipping_charge,tax,total,
               status,payment_method,payment_status,
               shipping_name,shipping_phone,shipping_address,
               shipping_city,shipping_state,shipping_pincode,notes,created_at)
            VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,NOW())
        ");
        $os->execute([
            $orderNum, $user['id'], $sub,
            0,         $shp,        $tx,  $tot,
            'pending', $method,     'pending',
            $name,     $phone,      $address,
            $city,     $state,      $pincode, $notes
        ]);
        $orderId = $pdo->lastInsertId();

        // Insert order items & update stock
        foreach($cartItems as $ci) {
            $ip = getEffectivePrice($ci);
            $it = $ip * $ci['quantity'];
            $pdo->prepare("INSERT INTO order_items (order_id,product_id,product_name,product_image,quantity,price,total) VALUES (?,?,?,?,?,?,?)")
                ->execute([$orderId,$ci['product_id'],$ci['name'],getProductImages($ci)[0]??null,$ci['quantity'],$ip,$it]);
            $pdo->prepare("UPDATE products SET stock=GREATEST(stock-?,0) WHERE id=?")->execute([$ci['quantity'],$ci['product_id']]);
            $pdo->prepare("INSERT INTO inventory (product_id,quantity,type,reference,note,created_at) VALUES (?,?,?,?,?,NOW())")
                ->execute([$ci['product_id'], -$ci['quantity'], 'out', $orderNum, "Order {$orderNum}"]);
        }

        // Payment record
        $pdo->prepare("INSERT INTO payments (order_id,amount,method,status,created_at) VALUES (?,?,?,'pending',NOW())")
            ->execute([$orderId, $tot, $method]);

        // Clear cart
        $pdo->prepare("DELETE FROM cart WHERE user_id=?")->execute([$user['id']]);

        // SNS notification
        sendSNSNotification("New order {$orderNum} placed by {$profile['name']} for " . formatPrice($tot), 'New Order Received');

        redirect(APP_URL . '/customer/order-success.php?order=' . urlencode($orderNum));
    }
}

require_once __DIR__ . '/../includes/header.php';
?>

<div class="container py-4">
  <div class="breadcrumb-wrap">
    <nav><ol class="breadcrumb">
      <li class="breadcrumb-item"><a href="<?= APP_URL ?>/">Home</a></li>
      <li class="breadcrumb-item"><a href="<?= APP_URL ?>/customer/cart.php">Cart</a></li>
      <li class="breadcrumb-item active">Checkout</li>
    </ol></nav>
  </div>

  <h1 style="font-family:'Outfit',sans-serif;font-weight:700;font-size:1.7rem;color:var(--text);margin-bottom:1.5rem;">
    <i class="bi bi-credit-card me-2" style="color:var(--primary-light);"></i>Secure Checkout
  </h1>

  <?php if($errors): ?>
  <div class="alert alert-danger">
    <?php foreach($errors as $e): ?><div><i class="bi bi-x-circle me-1"></i><?= e($e) ?></div><?php endforeach; ?>
  </div>
  <?php endif; ?>

  <form method="POST" novalidate>
    <?= csrfInput() ?>
    <div class="row g-4">
      <div class="col-lg-7">

        <!-- Shipping Address -->
        <div class="checkout-section">
          <div class="checkout-section-title">
            <span class="step-num">1</span> Shipping Address
          </div>
          <?php if($profile && $profile['address']): ?>
          <div class="form-check mb-3">
            <input class="form-check-input" type="checkbox" id="use-profile-addr" name="use_profile"
                   data-address='<?= json_encode(['name'=>$profile['name'],'phone'=>$profile['phone'],'address'=>$profile['address'],'city'=>$profile['city'],'state'=>$profile['state'],'pincode'=>$profile['pincode']]) ?>'>
            <label class="form-check-label" for="use-profile-addr" style="color:var(--text-muted);">
              Use my saved address
            </label>
          </div>
          <?php endif; ?>
          <div class="row g-3">
            <div class="col-md-6">
              <label class="form-label">Full Name *</label>
              <input type="text" id="ship_name" name="ship_name" class="form-control address-field"
                     value="<?= e($_POST['ship_name'] ?? $profile['name'] ?? '') ?>" required>
            </div>
            <div class="col-md-6">
              <label class="form-label">Phone *</label>
              <input type="tel" id="ship_phone" name="ship_phone" class="form-control address-field"
                     value="<?= e($_POST['ship_phone'] ?? $profile['phone'] ?? '') ?>" required>
            </div>
            <div class="col-12">
              <label class="form-label">Address *</label>
              <input type="text" id="ship_address" name="ship_address" class="form-control address-field"
                     placeholder="House no., Street, Area" value="<?= e($_POST['ship_address'] ?? $profile['address'] ?? '') ?>" required>
            </div>
            <div class="col-md-4">
              <label class="form-label">City *</label>
              <input type="text" id="ship_city" name="ship_city" class="form-control address-field"
                     value="<?= e($_POST['ship_city'] ?? $profile['city'] ?? '') ?>" required>
            </div>
            <div class="col-md-4">
              <label class="form-label">State *</label>
              <input type="text" id="ship_state" name="ship_state" class="form-control address-field"
                     value="<?= e($_POST['ship_state'] ?? $profile['state'] ?? '') ?>" required>
            </div>
            <div class="col-md-4">
              <label class="form-label">Pincode *</label>
              <input type="text" id="ship_pincode" name="ship_pincode" class="form-control address-field"
                     maxlength="6" value="<?= e($_POST['ship_pincode'] ?? $profile['pincode'] ?? '') ?>" required>
            </div>
            <div class="col-12">
              <label class="form-label">Order Notes (optional)</label>
              <textarea name="notes" class="form-control" rows="2" placeholder="Any special delivery instructions…"><?= e($_POST['notes'] ?? '') ?></textarea>
            </div>
          </div>
        </div>

        <!-- Payment Method -->
        <div class="checkout-section">
          <div class="checkout-section-title">
            <span class="step-num">2</span> Payment Method
          </div>
          <?php
          $payIcons = ['cod'=>'💵','upi'=>'📱','card'=>'💳','netbanking'=>'🏦'];
          $payDescs = ['cod'=>'Pay when your order arrives','upi'=>'Pay via UPI apps (GPay, PhonePe, etc.)','card'=>'Visa, Mastercard, RuPay','netbanking'=>'Internet banking via all major banks'];
          $selMethod = $_POST['payment_method'] ?? 'cod';
          ?>
          <?php foreach($paymentMethods as $key => $label): ?>
          <div class="payment-option <?= $selMethod===$key?'selected':'' ?>" onclick="selectPayment('<?= $key ?>')">
            <input type="radio" name="payment_method" value="<?= $key ?>" id="pay-<?= $key ?>" <?= $selMethod===$key?'checked':'' ?> style="accent-color:var(--primary);">
            <div class="payment-icon"><?= $payIcons[$key] ?? '💰' ?></div>
            <div>
              <div style="font-weight:600;color:var(--text);font-size:.9rem;"><?= $label ?></div>
              <div style="font-size:.78rem;color:var(--text-muted);"><?= $payDescs[$key] ?? '' ?></div>
            </div>
          </div>
          <?php endforeach; ?>
        </div>
      </div>

      <!-- Order Summary -->
      <div class="col-lg-5">
        <div class="order-summary-card">
          <h5 style="color:var(--text);font-weight:700;margin-bottom:1.2rem;">Order Summary</h5>
          <?php foreach($items as $item):
            $ip = getEffectivePrice($item);
          ?>
          <div class="d-flex gap-2 mb-2 align-items-center">
            <img src="<?= e(getProductPrimaryImage($item)) ?>" style="width:44px;height:44px;border-radius:6px;object-fit:cover;border:1px solid var(--border-light);" onerror="this.src='<?= APP_URL ?>/assets/images/no-image.png'">
            <div class="flex-grow-1">
              <div style="font-size:.82rem;color:var(--text);font-weight:500;line-height:1.3;"><?= e($item['name']) ?></div>
              <div style="font-size:.75rem;color:var(--text-muted);">Qty: <?= $item['quantity'] ?></div>
            </div>
            <div style="font-weight:600;color:var(--text);font-size:.875rem;"><?= formatPrice($ip * $item['quantity']) ?></div>
          </div>
          <?php endforeach; ?>
          <hr style="border-color:var(--border-light);">
          <div class="summary-row"><span>Subtotal</span><span style="color:var(--text);"><?= formatPrice($subtotal) ?></span></div>
          <div class="summary-row"><span>Shipping</span><span style="color:<?= $shipping==0?'var(--success)':'var(--text)' ?>;"><?= $shipping==0?'Free':formatPrice($shipping) ?></span></div>
          <div class="summary-row"><span>GST (5%)</span><span style="color:var(--text);"><?= formatPrice($tax) ?></span></div>
          <div class="summary-row total"><span>Grand Total</span><span><?= formatPrice($total) ?></span></div>

          <button type="submit" class="btn btn-primary w-100 btn-lg mt-3">
            <i class="bi bi-shield-check me-2"></i>Place Order — <?= formatPrice($total) ?>
          </button>
          <div class="text-center mt-2">
            <small style="color:var(--text-dim);">🔒 Secured by ShopNest</small>
          </div>
        </div>
      </div>
    </div>
  </form>
</div>

<script>
function selectPayment(key) {
  document.querySelectorAll('.payment-option').forEach(el => el.classList.remove('selected'));
  const opt = document.querySelector(`.payment-option input[value="${key}"]`);
  if (opt) { opt.checked = true; opt.closest('.payment-option').classList.add('selected'); }
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
