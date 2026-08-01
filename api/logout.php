<?php

declare(strict_types=1);

require_once __DIR__ . '/../lib/Auth.php';
require_once __DIR__ . '/../config.php';

Auth::clearToken();
header('Location: ' . APP_URL . '/');
exit;
