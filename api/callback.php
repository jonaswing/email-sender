<?php

declare(strict_types=1);

require_once __DIR__ . '/../lib/Auth.php';
require_once __DIR__ . '/../config.php';

if (isset($_GET['error'])) {
    header('Location: ' . APP_URL . '/?auth_error=' . rawurlencode((string) $_GET['error']));
    exit;
}

$code = $_GET['code'] ?? '';
if ($code === '') {
    header('Location: ' . APP_URL . '/?auth_error=missing_code');
    exit;
}

try {
    $token = Auth::exchangeCode($code);
    Auth::saveToken($token);
    header('Location: ' . APP_URL . '/?auth=ok');
} catch (Throwable $e) {
    header('Location: ' . APP_URL . '/?auth_error=' . rawurlencode($e->getMessage()));
}

exit;
