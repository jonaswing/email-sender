<?php

declare(strict_types=1);

require_once __DIR__ . '/../lib/Auth.php';
require_once __DIR__ . '/../lib/GraphClient.php';
require_once __DIR__ . '/../lib/Http.php';

header('Content-Type: application/json; charset=utf-8');

if (!Auth::isConfigured()) {
    Http::json([
        'ok' => true,
        'configured' => false,
        'logged_in' => false,
    ]);
}

if (!Auth::isLoggedIn()) {
    Http::json([
        'ok' => true,
        'configured' => true,
        'logged_in' => false,
    ]);
}

try {
    $graph = new GraphClient();
    $me = $graph->me();
    Http::json([
        'ok' => true,
        'configured' => true,
        'logged_in' => true,
        'user' => [
            'name' => $me['displayName'] ?? '',
            'email' => $me['mail'] ?? ($me['userPrincipalName'] ?? ''),
        ],
    ]);
} catch (Throwable $e) {
    Http::error($e->getMessage(), 500);
}
