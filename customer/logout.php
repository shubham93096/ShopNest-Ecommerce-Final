<?php
// customer/logout.php
require_once __DIR__ . '/../includes/functions.php';
session_destroy();
session_start();
setFlash('info', 'You have been logged out.');
redirect(APP_URL . '/customer/login.php');
