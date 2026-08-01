<?php

declare(strict_types=1);

require_once __DIR__ . '/../lib/Auth.php';
require_once __DIR__ . '/../lib/GraphClient.php';
require_once __DIR__ . '/../lib/EmailTemplate.php';
require_once __DIR__ . '/../lib/EmailLog.php';
require_once __DIR__ . '/../lib/Http.php';
require_once __DIR__ . '/../config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    Http::error('POST required', 405);
}

if (!Auth::isLoggedIn()) {
    Http::error('Not logged in', 401);
}

$raw = file_get_contents('php://input');
$payload = json_decode($raw ?: '', true);
$rows = $payload['rows'] ?? null;

if (!is_array($rows) || $rows === []) {
    Http::error('No rows to schedule');
}

$graph = new GraphClient();
$results = [];

foreach ($rows as $index => $row) {
    $rowNum = $index + 1;

    try {
        $to = trim((string) ($row['to_email'] ?? ''));
        $company = trim((string) ($row['company'] ?? ''));
        $industry = trim((string) ($row['industry'] ?? ''));
        $demoUrl = EmailTemplate::normalizeUrl((string) ($row['demo_url'] ?? ''));
        $scheduledAt = trim((string) ($row['scheduled_at'] ?? ''));

        if ($to === '' || $company === '' || $industry === '' || $demoUrl === '' || $scheduledAt === '') {
            throw new InvalidArgumentException('Missing required fields');
        }

        if (!filter_var($to, FILTER_VALIDATE_EMAIL)) {
            throw new InvalidArgumentException('Invalid email: ' . $to);
        }

        $sendAt = DateTimeImmutable::createFromFormat('Y-m-d H:i', $scheduledAt, new DateTimeZone(APP_TIMEZONE));
        if ($sendAt === false) {
            $sendAt = DateTimeImmutable::createFromFormat('Y-m-d\TH:i', $scheduledAt, new DateTimeZone(APP_TIMEZONE));
        }
        if ($sendAt === false) {
            $sendAt = new DateTimeImmutable($scheduledAt, new DateTimeZone(APP_TIMEZONE));
        }

        if ($sendAt <= new DateTimeImmutable('now', new DateTimeZone(APP_TIMEZONE))) {
            throw new InvalidArgumentException('scheduled_at must be in the future');
        }

        $html = EmailTemplate::render($company, $industry, $demoUrl);
        $subject = EmailTemplate::subject($company);
        $scheduled = $graph->scheduleEmail($to, $subject, $html, $sendAt);

        EmailLog::add([
            'to_email' => $to,
            'company' => $company,
            'industry' => $industry,
            'demo_url' => $demoUrl,
            'scheduled_at' => $sendAt->format('Y-m-d H:i'),
        ]);

        $results[] = [
            'row' => $rowNum,
            'ok' => true,
            'to' => $to,
            'subject' => $subject,
            'scheduled_at' => $scheduled['scheduled_at'],
        ];
    } catch (Throwable $e) {
        $results[] = [
            'row' => $rowNum,
            'ok' => false,
            'error' => $e->getMessage(),
            'to' => $row['to_email'] ?? null,
        ];
    }
}

$okCount = count(array_filter($results, static fn(array $r): bool => $r['ok']));

Http::json([
    'ok' => $okCount > 0,
    'scheduled' => $okCount,
    'failed' => count($results) - $okCount,
    'results' => $results,
]);
