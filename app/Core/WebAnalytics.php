<?php

/** Cloudflare auto-injection must stay off: only these public pages may load the beacon. */
final class WebAnalytics
{
    // Public beacon identifier, not an API credential. Reuse the existing Cloudflare site.
    public const SITE_TOKEN = '8be719e985514310bdca884f9dcd677b';

    public static function isEnabled(array $server): bool
    {
        if (!in_array(strtolower((string)($server['HTTP_HOST'] ?? '')), ['fezadan.org', 'www.fezadan.org'], true)
            || !in_array($server['REQUEST_METHOD'] ?? '', ['GET', 'HEAD'], true)
            || ($server['HTTP_DNT'] ?? '') === '1'
            || ($server['HTTP_SEC_GPC'] ?? '') === '1') {
            return false;
        }

        $path = rawurldecode(explode('?', (string)($server['REQUEST_URI'] ?? '/'), 2)[0]);
        if (!preg_match('#^/(?:tr|en)(?:/|$)#i', $path)) {
            return false;
        }

        return !preg_match('#^/(?:tr|en)/(?:yonetim|admin|panel|dashboard|posta-davet|mail-invitation|mailinvite|notes|notlar|not)(?:/|$)#i', $path);
    }
}
