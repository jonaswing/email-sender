<?php

declare(strict_types=1);

require_once __DIR__ . '/../lib/Auth.php';
require_once __DIR__ . '/../lib/EmailLog.php';
require_once __DIR__ . '/../lib/Http.php';

if (!Auth::isLoggedIn()) {
    Http::error('Not logged in', 401);
}

try {
    Http::json([
        'ok' => true,
        'scheduled' => EmailLog::listScheduled(),
        'sent' => EmailLog::listSent(),
    ]);
} catch (Throwable $e) {
    Http::error($e->getMessage(), 500);
}
