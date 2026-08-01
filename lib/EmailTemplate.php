<?php

declare(strict_types=1);

require_once __DIR__ . '/../config.php';

final class EmailTemplate
{
    public static function render(string $company, string $industry, string $demoUrl): string
    {
        $template = file_get_contents(EMAIL_TEMPLATE_FILE);
        if ($template === false) {
            throw new RuntimeException('Email template missing.');
        }

        $demoUrl = self::normalizeUrl($demoUrl);

        return str_replace(
            ['{{company}}', '{{industry}}', '{{demo_url}}'],
            [
                htmlspecialchars($company, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'),
                htmlspecialchars($industry, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'),
                htmlspecialchars($demoUrl, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'),
            ],
            $template
        );
    }

    public static function normalizeUrl(string $url): string
    {
        $url = trim($url);
        if ($url === '') {
            return '';
        }

        if (preg_match('#^https?://#i', $url)) {
            return $url;
        }

        // Fix values like "https:/example.com"
        if (preg_match('#^https?:/#i', $url)) {
            return preg_replace('#^https?:/#i', str_starts_with(strtolower($url), 'https') ? 'https://' : 'http://', $url) ?? $url;
        }

        return 'https://' . $url;
    }

    public static function subject(string $company = ''): string
    {
        return SUBJECT_PREFIX;
    }
}
