<?php

/** Proxy headers are meaningful only when the connection came from Cloudflare. */
final class HttpRequest
{
    // https://www.cloudflare.com/ips/ — never trust arbitrary X-Forwarded-For chains.
    private const CLOUDFLARE_RANGES = [
        '173.245.48.0/20', '103.21.244.0/22', '103.22.200.0/22', '103.31.4.0/22',
        '141.101.64.0/18', '108.162.192.0/18', '190.93.240.0/20', '188.114.96.0/20',
        '197.234.240.0/22', '198.41.128.0/17', '162.158.0.0/15', '104.16.0.0/13',
        '104.24.0.0/14', '172.64.0.0/13', '131.0.72.0/22',
        '2400:cb00::/32', '2606:4700::/32', '2803:f800::/32', '2405:b500::/32',
        '2405:8100::/32', '2a06:98c0::/29', '2c0f:f248::/32',
    ];

    public static function ipInRange(string $ip, string $range): bool
    {
        $parts = explode('/', $range, 2);
        if (count($parts) !== 2 || !ctype_digit($parts[1])) {
            return false;
        }
        $address = @inet_pton($ip);
        $network = @inet_pton($parts[0]);
        $bits = (int)$parts[1];
        if ($address === false || $network === false || strlen($address) !== strlen($network)
            || $bits > strlen($address) * 8) {
            return false;
        }

        $bytes = intdiv($bits, 8);
        if (substr($address, 0, $bytes) !== substr($network, 0, $bytes)) {
            return false;
        }
        $remainder = $bits % 8;
        return $remainder === 0
            || (ord($address[$bytes]) & (0xff << (8 - $remainder)))
                === (ord($network[$bytes]) & (0xff << (8 - $remainder)));
    }

    public static function isTrustedEdge(): bool
    {
        // Preserve the peer before REMOTE_ADDR is replaced with the visitor address.
        $peer = (string)($_SERVER['FEZADAN_PEER_ADDR'] ?? $_SERVER['CONN_REMOTE_ADDR'] ?? $_SERVER['REMOTE_ADDR'] ?? '');
        foreach (self::CLOUDFLARE_RANGES as $range) {
            if (self::ipInRange($peer, $range)) {
                return true;
            }
        }
        // cPanel/LiteSpeed rewrites REMOTE_ADDR from CF-Connecting-IP but hides the peer from PHP, so the
        // ranges above never match there. Equality proves the server already trusted that header; a direct
        // caller can only echo its own address, and the IPv6 swap below stays closed for that path.
        $connecting = (string)($_SERVER['HTTP_CF_CONNECTING_IP'] ?? '');
        return $connecting !== ''
            && $connecting === (string)($_SERVER['REMOTE_ADDR'] ?? '')
            && trim((string)($_SERVER['HTTP_CF_CONNECTING_IPV6'] ?? '')) === '';
    }

    public static function isHttps(): bool
    {
        $https = strtolower((string)($_SERVER['HTTPS'] ?? ''));
        if ($https !== '' && $https !== 'off' && $https !== '0') {
            return true;
        }
        if (!self::isTrustedEdge()) {
            return false;
        }
        $visitor = json_decode((string)($_SERVER['HTTP_CF_VISITOR'] ?? ''), true);
        return strtolower(trim((string)($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? ''))) === 'https'
            || (is_array($visitor) && ($visitor['scheme'] ?? '') === 'https');
    }

    public static function normalizeProxy(): void
    {
        $_SERVER['FEZADAN_PEER_ADDR'] = $_SERVER['FEZADAN_PEER_ADDR']
            ?? $_SERVER['CONN_REMOTE_ADDR'] ?? $_SERVER['REMOTE_ADDR'] ?? '';
        $_SERVER['FEZADAN_TRUSTED_EDGE'] = self::isTrustedEdge();
        if (!$_SERVER['FEZADAN_TRUSTED_EDGE']) {
            return;
        }
        // Pseudo IPv4's overwrite mode keeps the real IPv6 address in this header.
        $ipv6 = (string)($_SERVER['HTTP_CF_CONNECTING_IPV6'] ?? '');
        $ip = (string)($_SERVER['HTTP_CF_CONNECTING_IP'] ?? '');
        if (filter_var($ipv6, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
            $_SERVER['REMOTE_ADDR'] = $ipv6;
        } elseif (filter_var($ip, FILTER_VALIDATE_IP)) {
            $_SERVER['REMOTE_ADDR'] = $ip;
        }
    }

    public static function isLocalHost(): bool
    {
        $host = strtolower((string)($_SERVER['FEZADAN_ORIGINAL_HOST'] ?? $_SERVER['HTTP_HOST'] ?? ''));
        return preg_match('/(?:^|\.)localhost(?::\d+)?$/', $host) === 1
            || preg_match('/^127\.0\.0\.1(?::\d+)?$/', $host) === 1
            || preg_match('/^\[::1\](?::\d+)?$/', $host) === 1;
    }
}
