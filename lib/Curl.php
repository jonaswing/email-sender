<?php

declare(strict_types=1);

final class Curl
{
    public static function applyDefaults($ch): void
    {
        $ca = self::caBundlePath();
        if ($ca !== null) {
            curl_setopt($ch, CURLOPT_CAINFO, $ca);
        }

        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
    }

    private static function caBundlePath(): ?string
    {
        $candidates = [
            dirname(__DIR__) . DIRECTORY_SEPARATOR . 'certs' . DIRECTORY_SEPARATOR . 'cacert.pem',
            'C:\\wamp64\\bin\\php\\cacert.pem',
        ];

        foreach ($candidates as $path) {
            if (is_file($path)) {
                return $path;
            }
        }

        return null;
    }
}
