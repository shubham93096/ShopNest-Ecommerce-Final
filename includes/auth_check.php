<?php
// includes/auth_check.php — Customer auth guard for pages that require login
require_once __DIR__ . '/functions.php';
if (!isLoggedIn()) {
    $_SESSION['redirect_after_login'] = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS']==='on' ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
    setFlash('info', 'Please log in to continue.');
    redirect(APP_URL . '/customer/login.php');
}
