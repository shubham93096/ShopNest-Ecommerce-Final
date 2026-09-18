<?php
// health.php — Lightweight Kubernetes health check endpoint
header('Content-Type: application/json');
http_response_code(200);
echo json_encode([
    'status'    => 'UP',
    'app'       => 'ShopNest',
    'timestamp' => time()
]);
