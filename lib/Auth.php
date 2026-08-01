<?php

declare(strict_types=1);

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/Curl.php';

final class Auth
{
    private const SCOPES = 'openid profile offline_access User.Read Mail.ReadWrite Mail.Send';

    public static function isConfigured(): bool
    {
        return MICROSOFT_CLIENT_ID !== '' && MICROSOFT_CLIENT_SECRET !== '';
    }

    public static function getLoginUrl(): string
    {
        $params = http_build_query([
            'client_id' => MICROSOFT_CLIENT_ID,
            'response_type' => 'code',
            'redirect_uri' => REDIRECT_URI,
            'response_mode' => 'query',
            'scope' => self::SCOPES,
            'state' => bin2hex(random_bytes(16)),
        ]);

        return 'https://login.microsoftonline.com/' . rawurlencode(MICROSOFT_TENANT_ID) . '/oauth2/v2.0/authorize?' . $params;
    }

    public static function exchangeCode(string $code): array
    {
        return self::tokenRequest([
            'grant_type' => 'authorization_code',
            'code' => $code,
            'redirect_uri' => REDIRECT_URI,
        ]);
    }

    public static function refresh(string $refreshToken): array
    {
        return self::tokenRequest([
            'grant_type' => 'refresh_token',
            'refresh_token' => $refreshToken,
        ]);
    }

    public static function saveToken(array $token): void
    {
        $existing = self::readTokenFile();
        $payload = [
            'access_token' => $token['access_token'],
            'refresh_token' => $token['refresh_token'] ?? ($existing['refresh_token'] ?? null),
            'expires_at' => time() + (int) ($token['expires_in'] ?? 3600) - 60,
            'saved_at' => time(),
        ];

        file_put_contents(TOKEN_FILE, json_encode($payload, JSON_PRETTY_PRINT));
    }

    public static function clearToken(): void
    {
        if (is_file(TOKEN_FILE)) {
            unlink(TOKEN_FILE);
        }
    }

    public static function isLoggedIn(): bool
    {
        return self::getAccessToken() !== null;
    }

    public static function getAccessToken(): ?string
    {
        $token = self::readTokenFile();
        if ($token === null || empty($token['access_token'])) {
            return null;
        }

        if (($token['expires_at'] ?? 0) > time()) {
            return $token['access_token'];
        }

        if (empty($token['refresh_token'])) {
            return null;
        }

        try {
            $fresh = self::refresh($token['refresh_token']);
            self::saveToken($fresh);
            return $fresh['access_token'];
        } catch (Throwable $e) {
            self::clearToken();
            return null;
        }
    }

    private static function readTokenFile(): ?array
    {
        if (!is_file(TOKEN_FILE)) {
            return null;
        }

        $raw = file_get_contents(TOKEN_FILE);
        if ($raw === false || $raw === '') {
            return null;
        }

        $data = json_decode($raw, true);
        return is_array($data) ? $data : null;
    }

    private static function tokenRequest(array $extra): array
    {
        $body = array_merge([
            'client_id' => MICROSOFT_CLIENT_ID,
            'client_secret' => MICROSOFT_CLIENT_SECRET,
            'scope' => self::SCOPES,
        ], $extra);

        $ch = curl_init('https://login.microsoftonline.com/' . rawurlencode(MICROSOFT_TENANT_ID) . '/oauth2/v2.0/token');
        Curl::applyDefaults($ch);
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => http_build_query($body),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => ['Content-Type: application/x-www-form-urlencoded'],
        ]);

        $response = curl_exec($ch);
        $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($response === false) {
            throw new RuntimeException('Token request failed: ' . $error);
        }

        $data = json_decode($response, true);
        if ($status >= 400 || !is_array($data) || empty($data['access_token'])) {
            $message = is_array($data) ? ($data['error_description'] ?? $data['error'] ?? $response) : $response;
            throw new RuntimeException('Token error: ' . $message);
        }

        return $data;
    }
}
