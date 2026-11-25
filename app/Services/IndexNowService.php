<?php

namespace App\Services;

class IndexNowService
{
    public static function submit(array $urls): void
    {
        if (getenv('APP_ENV') === 'testing' || !defined('INDEXNOW_KEY')) {
            return;
        }

        $siteHost = strtolower((string)parse_url(defined('SITE_URL') ? SITE_URL : '', PHP_URL_HOST));
        if (!in_array($siteHost, ['fezadan.org', 'www.fezadan.org'], true)) {
            return;
        }

        $grouped = [];
        foreach ($urls as $url) {
            $url = trim((string)$url);
            $parts = parse_url($url);
            $host = strtolower((string)($parts['host'] ?? ''));
            if ($host === '' || !in_array($parts['scheme'] ?? '', ['http', 'https'], true)) {
                continue;
            }
            if (in_array($host, ['localhost', '127.0.0.1', '::1'], true)) {
                continue;
            }

            $allowedHosts = array_filter([
                strtolower((string)parse_url(defined('SITE_URL') ? SITE_URL : '', PHP_URL_HOST)),
                strtolower((string)parse_url(defined('NOTES_SITE_URL') ? NOTES_SITE_URL : '', PHP_URL_HOST)),
            ]);
            if (!in_array($host, $allowedHosts, true)) {
                continue;
            }

            $grouped[$host][] = $url;
        }

        foreach ($grouped as $host => $hostUrls) {
            foreach (array_chunk(array_values(array_unique($hostUrls)), 1000) as $urlChunk) {
                $payload = json_encode([
                    'host' => $host,
                    'key' => INDEXNOW_KEY,
                    'keyLocation' => 'https://' . $host . '/' . INDEXNOW_KEY . '.txt',
                    'urlList' => $urlChunk,
                ], JSON_UNESCAPED_SLASHES);

                if ($payload === false) {
                    continue;
                }

                try {
                    self::post($payload);
                } catch (\Throwable $e) {
                    error_log('IndexNow gönderimi başarısız: ' . $e->getMessage());
                }
            }
        }
    }

    private static function post(string $payload): void
    {
        $endpoint = 'https://api.indexnow.org/indexnow';

        if (function_exists('curl_init')) {
            $handle = curl_init($endpoint);
            curl_setopt_array($handle, [
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => $payload,
                CURLOPT_HTTPHEADER => ['Content-Type: application/json; charset=utf-8'],
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_CONNECTTIMEOUT => 1,
                CURLOPT_TIMEOUT => 3,
                CURLOPT_SSL_VERIFYPEER => true,
                CURLOPT_SSL_VERIFYHOST => 2,
            ]);
            curl_exec($handle);
            $status = (int)curl_getinfo($handle, CURLINFO_HTTP_CODE);
            $error = curl_error($handle);
            curl_close($handle);

            if ($status < 200 || $status >= 300) {
                throw new \RuntimeException($error !== '' ? $error : 'HTTP ' . $status);
            }
            return;
        }

        $context = stream_context_create([
            'http' => [
                'method' => 'POST',
                'header' => "Content-Type: application/json; charset=utf-8\r\n",
                'content' => $payload,
                'timeout' => 3,
                'ignore_errors' => true,
            ],
        ]);
        $response = @file_get_contents($endpoint, false, $context);
        $statusLine = $http_response_header[0] ?? '';
        preg_match('/\s(\d{3})\s/', $statusLine, $matches);
        $status = (int)($matches[1] ?? 0);
        if ($response === false || $status < 200 || $status >= 300) {
            throw new \RuntimeException($status > 0 ? 'HTTP ' . $status : 'HTTP isteği başarısız.');
        }
    }
}
