<?php
// admin/includes/header.php — Admin Shared Header
require_once __DIR__ . '/../../includes/functions.php';
requireAdminLogin();
$admin = $_SESSION[SESSION_ADMIN];
$pdo   = getDB();
$pendingOrders = (int)$pdo->query("SELECT COUNT(*) FROM orders WHERE status='pending'")->fetchColumn();
$currentPage = basename($_SERVER['PHP_SELF']);
$currentDir  = basename(dirname($_SERVER['PHP_SELF']));

function isAdminNav(string $file, string $dir='admin'): string {
    global $currentPage, $currentDir;
    if ($dir === 'admin' && $currentPage === $file) return 'active';
    if ($currentDir === $dir) return 'active';
    return '';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= defined('ADMIN_PAGE_TITLE') ? ADMIN_PAGE_TITLE . ' | ShopNest Admin' : 'ShopNest Admin' ?></title>
  <link rel="icon" href="<?= APP_URL ?>/assets/images/logo.png" type="image/png">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
  <link rel="stylesheet" href="<?= APP_URL ?>/assets/css/admin.css">
</head>
<body>

<!-- Toast Container -->
<div id="toast-container" class="toast-container position-fixed bottom-0 end-0 p-3"></div>

<!-- Sidebar Overlay -->
<div class="sidebar-overlay" id="sidebar-overlay"></div>

<div class="admin-layout">
  <!-- ── Sidebar ──── -->
  <aside class="admin-sidebar" id="admin-sidebar">
    <div class="sidebar-logo">
      <a href="<?= APP_URL ?>/admin/index.php">
        ShopNest
        <span class="admin-badge">Admin</span>
      </a>
    </div>

    <nav class="sidebar-nav">
      <a href="<?= APP_URL ?>/admin/index.php" class="nav-link <?= isAdminNav('index.php') ?>">
        <i class="bi bi-grid"></i> Dashboard
      </a>

      <div class="nav-section-title">Catalog</div>
      <a href="<?= APP_URL ?>/admin/products/index.php" class="nav-link <?= isAdminNav('index.php','products') ?>">
        <i class="bi bi-box-seam"></i> Products
      </a>
      <a href="<?= APP_URL ?>/admin/products/add.php" class="nav-link <?= isAdminNav('add.php','products') ?>">
        <i class="bi bi-plus-square"></i> Add Product
      </a>
      <a href="<?= APP_URL ?>/admin/categories/index.php" class="nav-link <?= isAdminNav('index.php','categories') ?>">
        <i class="bi bi-tags"></i> Categories
      </a>

      <div class="nav-section-title">Sales</div>
      <a href="<?= APP_URL ?>/admin/orders/index.php" class="nav-link <?= isAdminNav('index.php','orders') ?>">
        <i class="bi bi-bag-check"></i> Orders
        <?php if($pendingOrders > 0): ?>
        <span class="badge"><?= $pendingOrders ?></span>
        <?php endif; ?>
      </a>
      <a href="<?= APP_URL ?>/admin/customers/index.php" class="nav-link <?= isAdminNav('index.php','customers') ?>">
        <i class="bi bi-people"></i> Customers
      </a>

      <div class="nav-section-title">Reports</div>
      <a href="<?= APP_URL ?>/admin/reports/sales.php" class="nav-link <?= isAdminNav('sales.php','reports') ?>">
        <i class="bi bi-graph-up"></i> Sales Report
      </a>
      <a href="<?= APP_URL ?>/admin/inventory/index.php" class="nav-link <?= isAdminNav('index.php','inventory') ?>">
        <i class="bi bi-archive"></i> Inventory
      </a>
    </nav>

    <div class="sidebar-footer">
      <div class="sidebar-user">
        <div class="sidebar-user-avatar"><?= strtoupper(substr($admin['name'],0,1)) ?></div>
        <div>
          <div class="sidebar-user-name"><?= e($admin['name']) ?></div>
          <div class="sidebar-user-role"><?= ucfirst(str_replace('_',' ',$admin['role'])) ?></div>
        </div>
        <a href="<?= APP_URL ?>/admin/logout.php" class="sidebar-user-logout" title="Logout">
          <i class="bi bi-box-arrow-right"></i>
        </a>
      </div>
    </div>
  </aside>

  <!-- ── Main ────── -->
  <main class="admin-main">
    <!-- Top Bar -->
    <header class="admin-topbar">
      <button id="menu-toggle" class="topbar-btn d-lg-none" title="Menu">
        <i class="bi bi-list" style="font-size:1.2rem;"></i>
      </button>
      <div class="topbar-title"><?= defined('ADMIN_PAGE_TITLE') ? ADMIN_PAGE_TITLE : 'Dashboard' ?></div>
      <div class="topbar-actions">
        <a href="<?= APP_URL ?>/admin/orders/index.php?status=pending" class="topbar-btn" title="Pending orders">
          <i class="bi bi-bell"></i>
          <?php if($pendingOrders > 0): ?><div class="topbar-notif-dot"></div><?php endif; ?>
        </a>
        <a href="<?= APP_URL ?>/" target="_blank" class="topbar-view-site">
          <i class="bi bi-box-arrow-up-right me-1"></i>View Store
        </a>
      </div>
    </header>

    <!-- Flash Message -->
    <?php $flash = getFlash(); if($flash): ?>
    <div class="px-4 pt-3">
      <div class="alert alert-<?= $flash['type']==='error'?'danger':e($flash['type']) ?> alert-dismissible">
        <?= e($flash['message']) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
      </div>
    </div>
    <?php endif; ?>

    <div class="admin-body">
