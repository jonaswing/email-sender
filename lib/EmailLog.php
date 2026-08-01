<?php

declare(strict_types=1);

require_once __DIR__ . '/../config.php';

final class EmailLog
{
    private const FILE = DATA_DIR . '/emails.json';

    public static function add(array $row, string $status = 'scheduled'): void
    {
        $items = self::all();
        $items[] = [
            'id' => bin2hex(random_bytes(8)),
            'to_email' => $row['to_email'],
            'company' => $row['company'],
            'industry' => $row['industry'],
            'demo_url' => $row['demo_url'],
            'scheduled_at' => $row['scheduled_at'],
            'status' => $status,
            'created_at' => (new DateTimeImmutable('now', new DateTimeZone(APP_TIMEZONE)))->format('Y-m-d H:i'),
        ];
        self::save($items);
    }

    public static function listScheduled(): array
    {
        self::promoteDueToSent();
        return array_values(array_filter(
            self::all(),
            static fn(array $row): bool => ($row['status'] ?? '') === 'scheduled'
        ));
    }

    public static function listSent(): array
    {
        self::promoteDueToSent();
        $items = array_values(array_filter(
            self::all(),
            static fn(array $row): bool => ($row['status'] ?? '') === 'sent'
        ));
        usort($items, static fn(array $a, array $b): int => strcmp($b['scheduled_at'] ?? '', $a['scheduled_at'] ?? ''));
        return $items;
    }

    private static function promoteDueToSent(): void
    {
        $now = new DateTimeImmutable('now', new DateTimeZone(APP_TIMEZONE));
        $items = self::all();
        $changed = false;

        foreach ($items as &$row) {
            if (($row['status'] ?? '') !== 'scheduled') {
                continue;
            }

            try {
                $when = new DateTimeImmutable((string) $row['scheduled_at'], new DateTimeZone(APP_TIMEZONE));
            } catch (Exception $e) {
                continue;
            }

            if ($when <= $now) {
                $row['status'] = 'sent';
                $changed = true;
            }
        }
        unset($row);

        if ($changed) {
            self::save($items);
        }
    }

    private static function all(): array
    {
        if (!is_file(self::FILE)) {
            return [];
        }

        $raw = file_get_contents(self::FILE);
        if ($raw === false || $raw === '') {
            return [];
        }

        $data = json_decode($raw, true);
        return is_array($data) ? $data : [];
    }

    private static function save(array $items): void
    {
        file_put_contents(self::FILE, json_encode(array_values($items), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    }
}
