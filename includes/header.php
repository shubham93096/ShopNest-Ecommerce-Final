<?php
// includes/header.php — Customer-facing shared header
header('Content-Type: text/html; charset=UTF-8');
require_once __DIR__ . '/functions.php';
$cartCount  = isLoggedIn() ? getCartCount() : 0;
$user       = currentUser();
$categories = [];
try {
    $pdo = getDB();
    $categories = $pdo->query("SELECT id,name,slug FROM categories WHERE status=1 ORDER BY sort_order")->fetchAll();
} catch (Throwable $e) {
    error_log("Header categories query notice: " . $e->getMessage());
}
$flash      = getFlash();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="description" content="<?= defined('META_DESC') ? META_DESC : 'ShopNest — Premium Cloud-Based E-Commerce Store' ?>">
  <title><?= defined('PAGE_TITLE') ? PAGE_TITLE . ' | ShopNest' : 'ShopNest — Shop Premium Products' ?></title>

  <!-- Favicon -->
  <link rel="icon" href="<?= APP_URL ?>/assets/images/logo.png" type="image/png">

  <!-- Bootstrap 5 -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <!-- Bootstrap Icons -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
  <!-- Custom CSS -->
  <link rel="stylesheet" href="<?= APP_URL ?>/assets/css/style.css">
</head>
<body>

<!-- Toast Container -->
<div id="toast-container" class="toast-container position-fixed bottom-0 end-0 p-3"></div>

<!-- ── Navbar ─────────────────────────────────────────── -->
<nav class="navbar navbar-main navbar-expand-lg">
  <div class="container">

    <!-- Brand -->
    <a class="navbar-brand" href="<?= APP_URL ?>/">
      <img src="<?= APP_URL ?>/assets/images/logo.png" alt="ShopNest" onerror="this.style.display='none'">
      ShopNest
    </a>

    <!-- Search (desktop) -->
    <div class="navbar-search d-none d-lg-flex ms-3 position-relative">
      <form action="<?= APP_URL ?>/products/index.php" method="GET" class="d-flex">
        <input type="text" id="global-search" name="q" class="form-control" placeholder="Search products…"
               value="<?= e($_GET['q'] ?? '') ?>" autocomplete="off">
        <button class="btn" type="submit"><i class="bi bi-search"></i></button>
      </form>
      <div id="search-suggestions" class="search-dropdown" style="
        display:none; position:absolute; top:100%; left:0; right:0;
        background:#1a1a2e; border:1px solid rgba(99,102,241,.3);
        border-radius:10px; z-index:999; max-height:360px; overflow-y:auto; margin-top:4px;
        box-shadow:0 10px 40px rgba(0,0,0,0.5);"></div>
    </div>

    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#mainNav">
      <span class="navbar-toggler-icon"></span>
    </button>

    <div class="collapse navbar-collapse" id="mainNav">
      <ul class="navbar-nav ms-auto align-items-center gap-1">

        <!-- Mobile Search -->
        <li class="nav-item d-lg-none w-100 mb-2">
          <form action="<?= APP_URL ?>/products/index.php" method="GET" class="d-flex">
            <input type="text" name="q" class="form-control form-control-sm" placeholder="Search…">
            <button class="btn btn-sm btn-primary ms-1" type="submit"><i class="bi bi-search"></i></button>
          </form>
        </li>

        <li class="nav-item">
          <a class="nav-link" href="<?= APP_URL ?>/products/index.php">Products</a>
        </li>
        <?php foreach(array_slice($categories, 0, 4) as $cat): ?>
        <li class="nav-item">
          <a class="nav-link" href="<?= APP_URL ?>/products/index.php?category=<?= e($cat['slug']) ?>">
            <?= e($cat['name']) ?>
          </a>
        </li>
        <?php endforeach; ?>

        <!-- Cart -->
        <li class="nav-item ms-1">
          <a class="btn btn-outline-primary btn-sm cart-btn" href="<?= APP_URL ?>/customer/cart.php">
            <i class="bi bi-bag"></i>
            <span id="cart-count" class="cart-badge" style="<?= $cartCount > 0 ? '' : 'display:none' ?>">
              <?= $cartCount ?>
            </span>
          </a>
        </li>

        <!-- User Menu -->
        <?php if(isLoggedIn()): ?>
        <li class="nav-item dropdown user-menu ms-1">
          <button class="btn btn-sm d-flex align-items-center gap-2" style="background:rgba(99,102,241,.15);border:1px solid rgba(99,102,241,.3);color:#e2e8f0;border-radius:8px;" data-bs-toggle="dropdown">
            <div style="width:26px;height:26px;border-radius:50%;background:linear-gradient(135deg,#6366f1,#a855f7);display:flex;align-items:center;justify-content:center;font-size:.75rem;font-weight:700;color:#fff;">
              <?= strtoupper(substr($user['name'],0,1)) ?>
            </div>
            <?= e(explode(' ',$user['name'])[0]) ?>
            <i class="bi bi-chevron-down" style="font-size:.7rem;"></i>
          </button>
          <ul class="dropdown-menu dropdown-menu-end">
            <li><a class="dropdown-item" href="<?= APP_URL ?>/customer/profile.php"><i class="bi bi-person me-2"></i>My Profile</a></li>
            <li><a class="dropdown-item" href="<?= APP_URL ?>/customer/orders.php"><i class="bi bi-bag-check me-2"></i>My Orders</a></li>
            <li><a class="dropdown-item" href="<?= APP_URL ?>/customer/wishlist.php"><i class="bi bi-heart me-2"></i>Wishlist</a></li>
            <li><hr class="dropdown-divider"></li>
            <li><a class="dropdown-item text-danger" href="<?= APP_URL ?>/customer/logout.php"><i class="bi bi-box-arrow-right me-2"></i>Logout</a></li>
          </ul>
        </li>
        <?php else: ?>
        <li class="nav-item ms-1">
          <a class="btn btn-outline-primary btn-sm" href="<?= APP_URL ?>/customer/login.php">
            <i class="bi bi-person me-1"></i>Login
          </a>
        </li>
        <li class="nav-item ms-1">
          <a class="btn btn-primary btn-sm" href="<?= APP_URL ?>/customer/register.php">Register</a>
        </li>
        <?php endif; ?>
      </ul>
    </div>
  </div>
</nav>

<!-- Category Strip -->
<div class="category-bar d-none d-lg-block">
  <div class="container">
    <ul class="nav">
      <li class="nav-item">
        <a class="nav-link" href="<?= APP_URL ?>/products/index.php"><i class="bi bi-grid me-1"></i>All</a>
      </li>
      <?php foreach($categories as $cat): ?>
      <li class="nav-item">
        <a class="nav-link" href="<?= APP_URL ?>/products/index.php?category=<?= e($cat['slug']) ?>">
          <?= e($cat['name']) ?>
        </a>
      </li>
      <?php endforeach; ?>
    </ul>
  </div>
</div>

<!-- Flash Message -->
<?php if($flash): ?>
<div class="container mt-3">
  <div class="alert alert-<?= $flash['type'] === 'error' ? 'danger' : e($flash['type']) ?> alert-dismissible fade show">
    <?= e($flash['message']) ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
  </div>
</div>
<?php endif; ?>

<style>
.search-dropdown a {
  display:flex; align-items:center; gap:.75rem;
  padding:.65rem 1rem; color:#94a3b8; font-size:.85rem; border-bottom:1px solid rgba(255,255,255,.05);
}
.search-dropdown a:hover { background:rgba(99,102,241,.1); color:#e2e8f0; }
.search-dropdown a:last-child { border-bottom:none; }
</style>
