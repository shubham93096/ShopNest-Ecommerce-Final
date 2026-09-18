<?php
// ============================================================
//  config/constants.php — App-wide constants
// ============================================================

define('APP_NAME',     getenv('APP_NAME') ?: 'ShopNest');

// Auto-detect host for dynamic redirects (works for both Docker and local XAMPP)
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || ($_SERVER['SERVER_PORT'] ?? 80) == 443) ? "https" : "http";
$host = $_SERVER['HTTP_HOST'] ?? 'localhost';
$subpath = (isset($_SERVER['SCRIPT_NAME']) && str_starts_with($_SERVER['SCRIPT_NAME'], '/aws-ecommerce')) ? '/aws-ecommerce' : '';
$default_url = "$protocol://$host$subpath";

$env_url = getenv('APP_URL');
if ($env_url && (str_contains($env_url, 'localhost') && $host !== 'localhost')) {
    $app_url = $default_url;
} else {
    $app_url = $env_url ?: $default_url;
}
define('APP_URL', rtrim($app_url, '/'));

define('APP_ENV',      getenv('APP_ENV')  ?: 'development');
define('APP_DEBUG',    getenv('APP_DEBUG') === 'true');

// Currency
define('CURRENCY_SYMBOL', 'Rs. ');
define('CURRENCY_CODE',   'INR');

// Pagination
define('PRODUCTS_PER_PAGE', 12);
define('ORDERS_PER_PAGE',   10);
define('ADMIN_PER_PAGE',    15);

// Upload limits
define('MAX_UPLOAD_SIZE',   5 * 1024 * 1024); // 5 MB
define('ALLOWED_IMG_TYPES', ['image/jpeg', 'image/png', 'image/webp', 'image/gif']);
define('UPLOAD_DIR',        __DIR__ . '/../uploads/products/');

// Order statuses
define('ORDER_STATUSES', ['pending', 'processing', 'shipped', 'delivered', 'cancelled', 'refunded']);

// Payment methods
define('PAYMENT_METHODS', ['cod' => 'Cash on Delivery', 'upi' => 'UPI', 'card' => 'Credit/Debit Card', 'netbanking' => 'Net Banking']);

// Session keys
define('SESSION_USER',  'ecom_user');
define('SESSION_ADMIN', 'ecom_admin');
define('SESSION_CART',  'ecom_cart');

// Paths
define('BASE_DIR', __DIR__ . '/..');
