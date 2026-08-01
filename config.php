<?php

declare(strict_types=1);

function load_env(string $path): void
{
    if (!is_file($path)) {
        return;
    }

    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    if ($lines === false) {
        return;
    }

    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '' || str_starts_with($line, '#') || !str_contains($line, '=')) {
            continue;
        }

        [$key, $value] = explode('=', $line, 2);
        $key = trim($key);
        $value = trim($value, " \t\"'");

        if ($key !== '' && getenv($key) === false) {
            putenv("{$key}={$value}");
            $_ENV[$key] = $value;
        }
    }
}

load_env(__DIR__ . '/.env');

define('APP_ROOT', __DIR__);
define('DATA_DIR', __DIR__ . '/data');
define('TOKEN_FILE', DATA_DIR . '/token.json');
define('EMAIL_TEMPLATE_FILE', __DIR__ . '/templates/email.html');

define('MICROSOFT_CLIENT_ID', getenv('MICROSOFT_CLIENT_ID') ?: '');
define('MICROSOFT_CLIENT_SECRET', getenv('MICROSOFT_CLIENT_SECRET') ?: '');
define('MICROSOFT_TENANT_ID', getenv('MICROSOFT_TENANT_ID') ?: 'common');
define('APP_URL', rtrim(getenv('APP_URL') ?: 'http://localhost/Projects/email-sender', '/'));
define('APP_TIMEZONE', getenv('TIMEZONE') ?: 'Europe/Oslo');
define('REDIRECT_URI', APP_URL . '/api/callback.php');
define('SUBJECT_PREFIX', 'Samarbeid');

date_default_timezone_set(APP_TIMEZONE);

if (!is_dir(DATA_DIR)) {
    mkdir(DATA_DIR, 0775, true);
}
