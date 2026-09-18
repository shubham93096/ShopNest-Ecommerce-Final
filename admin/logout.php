<?php
// admin/logout.php
require_once __DIR__ . '/../includes/functions.php';
unset($_SESSION[SESSION_ADMIN]);
session_regenerate_id(true);
redirect(APP_URL . '/admin/login.php');
