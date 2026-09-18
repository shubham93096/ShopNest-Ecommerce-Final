<?php
// ============================================================
//  includes/functions.php — Global helper functions
// ============================================================

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../config/aws.php';

// Start session once
if (session_status() === PHP_SESSION_NONE) {
    session_name('shopnest_session');
    session_start();
}

// ── Auth helpers ─────────────────────────────────────────────

function isLoggedIn(): bool {
    return isset($_SESSION[SESSION_USER]) && !empty($_SESSION[SESSION_USER]['id']);
}

function currentUser(): ?array {
    return $_SESSION[SESSION_USER] ?? null;
}

function requireLogin(): void {
    if (!isLoggedIn()) {
        $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'];
        redirect(APP_URL . '/customer/login.php');
    }
}

function isAdminLoggedIn(): bool {
    return isset($_SESSION[SESSION_ADMIN]) && !empty($_SESSION[SESSION_ADMIN]['id']);
}

function requireAdminLogin(): void {
    if (!isAdminLoggedIn()) {
        redirect(APP_URL . '/admin/login.php');
    }
}

// ── Redirect ────────────────────────────────────────────────

function redirect(string $url): never {
    header("Location: $url");
    exit;
}

// ── Sanitize ────────────────────────────────────────────────

function sanitize(mixed $val): string {
    return htmlspecialchars(trim((string)$val), ENT_QUOTES, 'UTF-8');
}

function e(mixed $val): string {
    return sanitize($val);
}

// ── Flash messages ───────────────────────────────────────────

function setFlash(string $type, string $message): void {
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

function getFlash(): ?array {
    if (isset($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }
    return null;
}

function renderFlash(): void {
    $flash = getFlash();
    if ($flash) {
        $icon = match($flash['type']) {
            'success' => '✅',
            'error'   => '❌',
            'warning' => '⚠️',
            default   => 'ℹ️'
        };
        $cls = match($flash['type']) {
            'success' => 'alert-success',
            'error'   => 'alert-danger',
            'warning' => 'alert-warning',
            default   => 'alert-info'
        };
        echo "<div class='alert {$cls} alert-dismissible fade show' role='alert'>
                {$icon} " . e($flash['message']) . "
                <button type='button' class='btn-close' data-bs-dismiss='alert'></button>
              </div>";
    }
}

// ── Slug generator ────────────────────────────────────────────

function slugify(string $text): string {
    $text = strtolower(trim($text));
    $text = preg_replace('/[^a-z0-9\s-]/', '', $text);
    $text = preg_replace('/[\s-]+/', '-', $text);
    return trim($text, '-');
}

// ── Price formatting ──────────────────────────────────────────

function formatPrice(float $amount): string {
    return CURRENCY_SYMBOL . number_format($amount, 2);
}

function getEffectivePrice(array $product): float {
    return ($product['sale_price'] && $product['sale_price'] > 0)
        ? (float)$product['sale_price']
        : (float)$product['price'];
}

function getDiscountPercent(array $product): int {
    if ($product['sale_price'] && $product['sale_price'] > 0 && $product['price'] > 0) {
        return (int)round((($product['price'] - $product['sale_price']) / $product['price']) * 100);
    }
    return 0;
}

// ── Product helpers ───────────────────────────────────────────

function getProductImages(array $product): array {
    if (!empty($product['images'])) {
        $imgs = json_decode($product['images'], true);
        if (is_array($imgs) && count($imgs)) return $imgs;
    }
    return [];
}

function getProductPrimaryImage(array $product): string {
    $imgs = getProductImages($product);
    return !empty($imgs) ? getProductImageUrl($imgs[0]) : getProductImageUrl('');
}

function renderStars(float $rating, bool $interactive = false): string {
    $html = '<span class="stars">';
    for ($i = 1; $i <= 5; $i++) {
        $cls = $i <= $rating ? 'star-filled' : 'star-empty';
        $html .= "<span class='{$cls}'>★</span>";
    }
    $html .= '</span>';
    return $html;
}

// ── Cart helpers ──────────────────────────────────────────────

function getCartCount(): int {
    if (!isLoggedIn()) return 0;
    $pdo = getDB();
    $stmt = $pdo->prepare("SELECT COALESCE(SUM(quantity),0) FROM cart WHERE user_id = ?");
    $stmt->execute([$_SESSION[SESSION_USER]['id']]);
    return (int)$stmt->fetchColumn();
}

function getCartItems(): array {
    if (!isLoggedIn()) return [];
    $pdo = getDB();
    $stmt = $pdo->prepare("
        SELECT c.*, p.name, p.price, p.sale_price, p.images, p.stock, p.slug
        FROM cart c
        JOIN products p ON p.id = c.product_id
        WHERE c.user_id = ?
    ");
    $stmt->execute([$_SESSION[SESSION_USER]['id']]);
    return $stmt->fetchAll();
}

function getCartTotal(): float {
    $items = getCartItems();
    $total = 0;
    foreach ($items as $item) {
        $price = getEffectivePrice($item);
        $total += $price * $item['quantity'];
    }
    return $total;
}

function getWishlistIds(): array {
    if (!isLoggedIn()) return [];
    $pdo = getDB();
    $stmt = $pdo->prepare("SELECT product_id FROM wishlist WHERE user_id = ?");
    $stmt->execute([$_SESSION[SESSION_USER]['id']]);
    return $stmt->fetchAll(PDO::FETCH_COLUMN);
}

// ── Order helpers ─────────────────────────────────────────────

function generateOrderNumber(): string {
    return 'ORD-' . strtoupper(substr(uniqid(), -6)) . '-' . date('Ymd');
}

function getOrderStatusBadge(string $status): string {
    $map = [
        'pending'    => 'warning',
        'processing' => 'info',
        'shipped'    => 'primary',
        'delivered'  => 'success',
        'cancelled'  => 'danger',
        'refunded'   => 'secondary',
    ];
    $cls = $map[$status] ?? 'secondary';
    return "<span class='badge bg-{$cls}'>" . ucfirst($status) . "</span>";
}

// ── File upload helper ────────────────────────────────────────

function uploadProductImage(array $file): string|false {
    if ($file['error'] !== UPLOAD_ERR_OK) return false;
    if ($file['size'] > MAX_UPLOAD_SIZE) return false;
    if (!in_array($file['type'], ALLOWED_IMG_TYPES)) return false;

    $ext      = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = uniqid('prod_') . '.' . strtolower($ext);
    $dest     = UPLOAD_DIR . $filename;

    @mkdir(UPLOAD_DIR, 0755, true);
    return move_uploaded_file($file['tmp_name'], $dest) ? $filename : false;
}

// ── Pagination ────────────────────────────────────────────────

function paginate(int $total, int $perPage, int $current, string $baseUrl): array {
    $totalPages = (int)ceil($total / $perPage);
    return [
        'total'       => $total,
        'per_page'    => $perPage,
        'current'     => $current,
        'total_pages' => $totalPages,
        'offset'      => ($current - 1) * $perPage,
        'base_url'    => $baseUrl,
        'has_prev'    => $current > 1,
        'has_next'    => $current < $totalPages,
    ];
}

function renderPagination(array $p): string {
    if ($p['total_pages'] <= 1) return '';
    $sep = str_contains($p['base_url'], '?') ? '&' : '?';
    $html = '<nav aria-label="Pagination"><ul class="pagination justify-content-center">';
    $html .= '<li class="page-item' . (!$p['has_prev'] ? ' disabled' : '') . '">';
    $html .= '<a class="page-link" href="' . $p['base_url'] . $sep . 'page=' . ($p['current'] - 1) . '">‹ Prev</a></li>';
    for ($i = 1; $i <= $p['total_pages']; $i++) {
        $active = $i === $p['current'] ? ' active' : '';
        $html .= "<li class='page-item{$active}'><a class='page-link' href='{$p['base_url']}{$sep}page={$i}'>{$i}</a></li>";
    }
    $html .= '<li class="page-item' . (!$p['has_next'] ? ' disabled' : '') . '">';
    $html .= '<a class="page-link" href="' . $p['base_url'] . $sep . 'page=' . ($p['current'] + 1) . '">Next ›</a></li>';
    $html .= '</ul></nav>';
    return $html;
}

// ── CSRF ──────────────────────────────────────────────────────

function csrfToken(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verifyCsrf(): void {
    $token = $_POST['csrf_token'] ?? '';
    if (!hash_equals($_SESSION['csrf_token'] ?? '', $token)) {
        http_response_code(403);
        die('Invalid CSRF token.');
    }
}

function csrfInput(): string {
    return '<input type="hidden" name="csrf_token" value="' . csrfToken() . '">';
}

// ── Admin dashboard stats ─────────────────────────────────────

function getDashboardStats(): array {
    $pdo = getDB();
    $stats = [];
    $stats['total_orders']    = (int)$pdo->query("SELECT COUNT(*) FROM orders")->fetchColumn();
    $stats['total_revenue']   = (float)$pdo->query("SELECT COALESCE(SUM(total),0) FROM orders WHERE payment_status='paid'")->fetchColumn();
    $stats['total_customers'] = (int)$pdo->query("SELECT COUNT(*) FROM users WHERE status=1")->fetchColumn();
    $stats['total_products']  = (int)$pdo->query("SELECT COUNT(*) FROM products WHERE status=1")->fetchColumn();
    $stats['pending_orders']  = (int)$pdo->query("SELECT COUNT(*) FROM orders WHERE status='pending'")->fetchColumn();
    $stats['low_stock']       = (int)$pdo->query("SELECT COUNT(*) FROM products WHERE stock <= 5 AND status=1")->fetchColumn();
    return $stats;
}
