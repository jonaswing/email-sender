<?php

declare(strict_types=1);

require_once __DIR__ . '/Auth.php';
require_once __DIR__ . '/Curl.php';

final class GraphClient
{
    private const BASE = 'https://graph.microsoft.com/v1.0';
    private const DEFERRED_PROP = 'SystemTime 0x3FEF';

    public function me(): array
    {
        return $this->request('GET', '/me?$select=displayName,mail,userPrincipalName');
    }

    public function scheduleEmail(string $to, string $subject, string $html, DateTimeInterface $sendAt): array
    {
        $utc = (new DateTimeImmutable('@' . $sendAt->getTimestamp()))->setTimezone(new DateTimeZone('UTC'));

        $message = $this->request('POST', '/me/messages', [
            'subject' => $subject,
            'body' => [
                'contentType' => 'HTML',
                'content' => $html,
            ],
            'toRecipients' => [[
                'emailAddress' => ['address' => $to],
            ]],
            'singleValueExtendedProperties' => [[
                'id' => self::DEFERRED_PROP,
                'value' => $utc->format('Y-m-d\TH:i:s\Z'),
            ]],
        ]);

        $this->request('POST', '/me/messages/' . rawurlencode($message['id']) . '/send');

        return [
            'id' => $message['id'],
            'subject' => $subject,
            'to' => $to,
            'scheduled_at' => $utc->format(DateTimeInterface::ATOM),
        ];
    }

    public function listScheduled(): array
    {
        $expand = rawurlencode("singleValueExtendedProperties(\$filter=id eq '" . self::DEFERRED_PROP . "')");
        $select = rawurlencode('id,subject,bodyPreview,toRecipients,createdDateTime,isDraft');
        $path = "/me/mailFolders/drafts/messages?\$top=50&\$orderby=createdDateTime desc&\$select={$select}&\$expand={$expand}";

        $data = $this->request('GET', $path);
        $items = [];

        foreach ($data['value'] ?? [] as $message) {
            if (!$this->isOurSubject($message['subject'] ?? '')) {
                continue;
            }

            $deferred = $this->extractDeferred($message);
            if ($deferred === null) {
                continue;
            }

            $items[] = [
                'id' => $message['id'],
                'subject' => $message['subject'] ?? '',
                'to' => $this->firstRecipient($message),
                'preview' => $message['bodyPreview'] ?? '',
                'scheduled_at' => $deferred,
                'created_at' => $message['createdDateTime'] ?? null,
            ];
        }

        usort($items, static fn(array $a, array $b): int => strcmp($a['scheduled_at'], $b['scheduled_at']));

        return $items;
    }

    public function listSent(): array
    {
        $select = rawurlencode('id,subject,bodyPreview,toRecipients,sentDateTime');
        $path = "/me/mailFolders/sentitems/messages?\$top=50&\$orderby=sentDateTime desc&\$select={$select}";

        $data = $this->request('GET', $path);
        $items = [];

        foreach ($data['value'] ?? [] as $message) {
            if (!$this->isOurSubject($message['subject'] ?? '')) {
                continue;
            }

            $items[] = [
                'id' => $message['id'],
                'subject' => $message['subject'] ?? '',
                'to' => $this->firstRecipient($message),
                'preview' => $message['bodyPreview'] ?? '',
                'sent_at' => $message['sentDateTime'] ?? null,
            ];
        }

        return $items;
    }

    private function isOurSubject(string $subject): bool
    {
        return str_starts_with($subject, SUBJECT_PREFIX);
    }

    private function firstRecipient(array $message): string
    {
        return $message['toRecipients'][0]['emailAddress']['address'] ?? '';
    }

    private function extractDeferred(array $message): ?string
    {
        foreach ($message['singleValueExtendedProperties'] ?? [] as $prop) {
            if (($prop['id'] ?? '') === self::DEFERRED_PROP || str_ends_with((string) ($prop['id'] ?? ''), '0x3FEF')) {
                return $prop['value'] ?? null;
            }
        }

        return null;
    }

    private function request(string $method, string $path, ?array $json = null): array
    {
        $token = Auth::getAccessToken();
        if ($token === null) {
            throw new RuntimeException('Not logged in with Microsoft.');
        }

        $ch = curl_init(self::BASE . $path);
        Curl::applyDefaults($ch);
        $headers = [
            'Authorization: Bearer ' . $token,
            'Accept: application/json',
        ];

        $opts = [
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_RETURNTRANSFER => true,
        ];

        if ($json !== null) {
            $headers[] = 'Content-Type: application/json';
            $opts[CURLOPT_POSTFIELDS] = json_encode($json, JSON_UNESCAPED_UNICODE);
        } elseif (in_array($method, ['POST', 'PUT', 'PATCH'], true)) {
            // Graph endpoints like /send require Content-Length even with an empty body.
            $headers[] = 'Content-Length: 0';
            $opts[CURLOPT_POSTFIELDS] = '';
        }

        $opts[CURLOPT_HTTPHEADER] = $headers;
        curl_setopt_array($ch, $opts);
        $response = curl_exec($ch);
        $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($response === false) {
            throw new RuntimeException('Graph request failed: ' . $error);
        }

        if ($status === 202 || $status === 204 || $response === '') {
            return [];
        }

        $data = json_decode($response, true);
        if ($status >= 400) {
            $message = is_array($data)
                ? ($data['error']['message'] ?? json_encode($data))
                : $response;
            throw new RuntimeException('Graph error (' . $status . '): ' . $message);
        }

        return is_array($data) ? $data : [];
    }
}
