<?php

declare(strict_types=1);

require_once __DIR__ . '/../lib/Auth.php';

if (!Auth::isConfigured()) {
    http_response_code(500);
    echo 'Missing MICROSOFT_CLIENT_ID / MICROSOFT_CLIENT_SECRET in .env';
    exit;
}

header('Location: ' . Auth::getLoginUrl());
exit;
