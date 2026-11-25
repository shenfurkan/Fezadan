<?php

namespace App\Services;

/**
 * Part of the on-demand dashboard scan (?scan=1): opens every public page and every admin
 * screen, checks that every file the database points to still exists, that all tables are
 * there, and reads the PHP error log. Read-only: GET/HEAD requests and SELECTs only.
 */
class DeepScanService
{
    /** Tables the code reads or writes (newsletter_subscribers / portfolio_items are unused). */
    private const REQUIRED_TABLES = [
        'admins', 'admin_passkeys', 'admin_password_resets', 'admin_password_reset_rate_limits',
        'articles', 'article_authors', 'article_categories', 'article_corrections',
        'authors', 'categories', 'download_rate_limits', 'login_attempts', 'notes', 'note_categories',
        'patch_notes', 'portfolio_images',
    ];

    private const USER_AGENT = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/126 Safari/537.36 FezadanHealthCheck/1.0';
    private const SLOW_MS = 3000;
    private const MAX_LISTED = 8;

    /** @return list<array{label: string, status: string, message: string, detail: string}> */
    public function run(\PDO $pdo): array
    {
        @set_time_limit(240);

        return [
            $this->checkTables($pdo),
            $this->checkVendor(),
            $this->checkPhpErrorLog(),
            $this->checkStoredFiles($pdo),
            $this->checkPublicPages(),
            $this->checkAdminPages($pdo),
        ];
    }

    private function checkTables(\PDO $pdo): array
    {
        try {
            $existing = array_map('strtolower', $pdo->query(
                'SELECT table_name FROM information_schema.tables WHERE table_schema = DATABASE()'
            )->fetchAll(\PDO::FETCH_COLUMN));
            $missing = array_values(array_diff(self::REQUIRED_TABLES, $existing));
            if ($missing !== []) {
                return $this->row('TABLOLAR', 'fail', count($missing) . ' tablo eksik', implode(', ', $missing));
            }
            return $this->row('TABLOLAR', 'ok', count(self::REQUIRED_TABLES) . ' tablonun hepsi var', '');
        } catch (\Throwable $e) {
            return $this->row('TABLOLAR', 'info', 'Kontrol basarisiz', $e->getMessage());
        }
    }

    /** vendor/ must match composer.lock; a deploy whose `composer install` fails leaves it stale. */
    private function checkVendor(): array
    {
        $lock = json_decode((string)@file_get_contents(ROOT . '/composer.lock'), true);
        $installed = json_decode((string)@file_get_contents(ROOT . '/vendor/composer/installed.json'), true);
        if (!is_array($lock) || !is_array($installed)) {
            return $this->row('VENDOR', 'fail', 'composer.lock veya vendor/ okunamadi', ROOT);
        }

        $have = [];
        foreach ($installed['packages'] ?? $installed as $package) {
            if (isset($package['name'], $package['version'])) {
                $have[$package['name']] = $package['version'];
            }
        }
        $diff = [];
        foreach ($lock['packages'] ?? [] as $package) {
            $version = $have[$package['name']] ?? null;
            if ($version !== $package['version']) {
                $diff[] = $package['name'] . ' ' . ($version ?? 'yok') . ' (olmali ' . $package['version'] . ')';
            }
        }

        if ($diff === []) {
            return $this->row('VENDOR', 'ok', count($lock['packages'] ?? []) . ' paket composer.lock ile ayni', '');
        }
        return $this->row('VENDOR', 'fail', count($diff) . ' paket composer.lock ile uyusmuyor', 'Deploy sirasinda composer install calismamis olabilir: ' . $this->listed($diff));
    }

    /** Serious PHP errors in the last 24h (the log is wiped on every deploy). */
    private function checkPhpErrorLog(): array
    {
        $file = (string)ini_get('error_log');
        if ($file === '' || !is_file($file)) {
            $file = ROOT . '/public_html/error_log';
        }
        if (!is_file($file) || !is_readable($file)) {
            return $this->row('PHP HATA LOGU', 'info', 'Log dosyasi bulunamadi', $file);
        }

        $size = (int)filesize($file);
        $fh = fopen($file, 'rb');
        if ($fh === false) {
            return $this->row('PHP HATA LOGU', 'info', 'Log dosyasi okunamadi', $file);
        }
        fseek($fh, max(0, $size - 512 * 1024)); // the tail is enough for 24h
        $chunk = (string)stream_get_contents($fh);
        fclose($fh);

        $since = time() - 86400;
        $count = 0;
        $last = '';
        foreach (preg_split('/\R/', $chunk) ?: [] as $line) {
            if (!preg_match('/^\[(\d{2}-\w{3}-\d{4} \d{2}:\d{2}:\d{2} [^\]]+)\] (.*)$/', $line, $m)) {
                continue;
            }
            $ts = strtotime($m[1]);
            if ($ts === false || $ts < $since) {
                continue;
            }
            if (preg_match('/\[Fezadan\]\[(Exception|Shutdown|Degraded)\]|PHP Fatal error/', $m[2])) {
                $count++;
                $last = $m[2];
            }
        }

        if ($count === 0) {
            return $this->row('PHP HATA LOGU', 'ok', 'Son 24 saatte ciddi hata yok', '');
        }
        return $this->row('PHP HATA LOGU', $count >= 20 ? 'fail' : 'warn', $count . ' ciddi hata (24 saat)', 'Son: ' . mb_substr($last, 0, 220));
    }

    /** Every cover, OG image, audio file, note PDF, author photo and portfolio image the DB references. */
    private function checkStoredFiles(\PDO $pdo): array
    {
        $refs = $this->storedFileRefs($pdo);
        if ($refs === []) {
            return $this->row('DOSYALAR (R2)', 'info', 'Kontrol edilecek dosya yok', '');
        }

        $missing = [];
        try {
            $r2 = null;
            foreach ($refs as $key => $source) {
                if (is_file(ROOT . '/public_html/' . $key)) {
                    continue; // shipped with the site, or a local-fallback upload
                }
                if (strpos($key, 'assets/') === 0 || strpos($key, 'cdn/') === 0) {
                    $missing[] = $source . ': /' . $key;
                    continue;
                }
                if ($r2 === null) {
                    require_once ROOT . '/app/Core/R2Storage.php';
                    $r2 = \App\Core\R2Storage::instance();
                }
                if (!$r2->objectExists($key)) {
                    $missing[] = $source . ': ' . $key;
                }
            }
        } catch (\Throwable $e) {
            return $this->row('DOSYALAR (R2)', 'fail', "R2'ye ulasilamadi", $e->getMessage());
        }

        if ($missing === []) {
            return $this->row('DOSYALAR (R2)', 'ok', count($refs) . ' dosyanin hepsi yerinde', '');
        }
        return $this->row('DOSYALAR (R2)', 'fail', count($missing) . '/' . count($refs) . ' dosya eksik', $this->listed($missing));
    }

    /** @return array<string, string> object key => where it is used */
    private function storedFileRefs(\PDO $pdo): array
    {
        $portfolioWhere = $pdo->query("SHOW COLUMNS FROM portfolio_images LIKE 'deleted_at'")->rowCount() > 0
            ? ' WHERE deleted_at IS NULL' : '';
        $queries = [
            'makale kapagi' => 'SELECT id, image_url FROM articles',
            'makale OG gorseli' => 'SELECT id, og_image FROM articles',
            'not PDF' => 'SELECT id, r2_path FROM notes',
            'yazar gorseli' => 'SELECT id, image_url FROM authors',
            'portfolyo' => 'SELECT id, image_url FROM portfolio_images' . $portfolioWhere,
        ];

        $refs = [];
        foreach ($queries as $source => $sql) {
            foreach ($pdo->query($sql)->fetchAll(\PDO::FETCH_NUM) as [$id, $value]) {
                $key = $this->objectKey((string)$value);
                if ($key !== null && !isset($refs[$key])) {
                    $refs[$key] = $source . ' #' . $id;
                }
            }
        }
        return $refs;
    }

    /** Stored path or URL -> bucket key; null for empty values and files on other sites. */
    private function objectKey(string $value): ?string
    {
        $value = trim($value);
        if ($value === '') {
            return null;
        }
        if (preg_match('#^https?://#i', $value)) {
            $ourHosts = [];
            foreach ([SITE_URL, CDN_URL, (string)getenv('R2_PUBLIC_URL')] as $base) {
                $host = strtolower((string)parse_url($base, PHP_URL_HOST));
                if ($host !== '') {
                    $ourHosts[] = $host;
                }
            }
            if (!in_array(strtolower((string)parse_url($value, PHP_URL_HOST)), $ourHosts, true)) {
                return null;
            }
            $value = (string)parse_url($value, PHP_URL_PATH);
        }
        $key = ltrim($value, '/');
        return $key === '' ? null : $key;
    }

    private function checkPublicPages(): array
    {
        $urls = [];
        foreach (['sitemap.xml', 'sitemap_main.xml', 'sitemap_notes.xml'] as $file) {
            $xml = @file_get_contents(ROOT . '/public_html/' . $file);
            if ($xml !== false && preg_match_all('#<loc>\s*([^<\s]+)\s*</loc>#', $xml, $m)) {
                foreach ($m[1] as $loc) {
                    $urls[] = html_entity_decode($loc, ENT_QUOTES | ENT_XML1, 'UTF-8');
                }
            }
        }
        $site = rtrim(SITE_URL, '/');
        foreach (['/robots.txt', '/sitemap.xml', '/tr/rss', '/en/rss'] as $path) {
            $urls[] = $site . $path;
        }
        $urls[] = rtrim(NOTES_SITE_URL, '/') . '/';
        $urls = array_slice(array_values(array_unique($urls)), 0, 400);

        $problems = [];
        $notes = [];
        $blocked = 0;
        $results = $this->fetchAll($urls, null, 6);
        foreach ($results as $url => $r) {
            if ($r['blocked']) {
                $blocked++;
                continue;
            }
            $problem = $this->pageProblem($r);
            if ($problem !== null) {
                $problems[] = $this->shortUrl($url) . ' -> ' . $problem;
            } elseif ($r['status'] >= 300) {
                $notes[] = $this->shortUrl($url) . ' -> ' . $r['status'] . ' ' . $this->shortUrl($r['location']);
            } elseif ($r['ms'] > self::SLOW_MS) {
                $notes[] = $this->shortUrl($url) . ' -> yavas (' . $r['ms'] . ' ms)';
            }
        }
        return $this->pagesRow('SAYFALAR', count($urls), $problems, $notes, $blocked, $results);
    }

    private function checkAdminPages(\PDO $pdo): array
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            return $this->row('ADMIN SAYFALARI', 'info', 'Oturum yok, atlandi', '');
        }

        $base = rtrim(SITE_URL, '/') . '/yonetim/';
        $pages = ['dashboard', 'create', 'categories', 'authors', 'admins', 'add-note', 'create-patch', 'portfolio', 'profile', 'logs'];
        foreach (['edit' => 'articles', 'edit-note' => 'notes', 'portfolio-edit' => 'portfolio_images'] as $page => $table) {
            $id = (int)$pdo->query('SELECT MAX(id) FROM ' . $table)->fetchColumn();
            if ($id > 0) {
                $pages[] = $page . '?id=' . $id;
            }
        }
        $urls = array_map(fn (string $page) => $base . $page, $pages);

        // This request holds the session file lock; release it so the page requests (same
        // session) don't block on us, then reopen it for the rest of the dashboard render.
        $cookie = session_name() . '=' . session_id();
        session_write_close();
        try {
            $results = $this->fetchAll($urls, $cookie, 1);
        } finally {
            @session_start();
        }

        $problems = [];
        $notes = [];
        $skipped = [];
        $blocked = 0;
        foreach ($results as $url => $r) {
            if ($r['blocked']) {
                $blocked++;
                continue;
            }
            if ($r['status'] >= 300 && $r['status'] < 400) {
                $target = rtrim((string)parse_url($r['location'], PHP_URL_PATH), '/');
                if ($target === '/yonetim') {
                    $problems[] = $this->shortUrl($url) . ' -> oturum kabul edilmedi';
                } else {
                    $skipped[] = $this->shortUrl($url) . ' (rolunuz acamiyor)';
                }
                continue;
            }
            $problem = $this->pageProblem($r);
            if ($problem !== null) {
                $problems[] = $this->shortUrl($url) . ' -> ' . $problem;
            } elseif ($r['ms'] > self::SLOW_MS) {
                $notes[] = $this->shortUrl($url) . ' -> yavas (' . $r['ms'] . ' ms)';
            }
        }
        return $this->pagesRow('ADMIN SAYFALARI', count($urls), $problems, $notes, $blocked, $results, $skipped);
    }

    /** null = page is fine (redirects are judged by the caller). */
    private function pageProblem(array $r): ?string
    {
        if ($r['error'] !== '') {
            return 'baglanti hatasi: ' . $r['error'];
        }
        $status = $r['status'];
        if ($status >= 300 && $status < 400) {
            return null;
        }
        if ($status !== 200) {
            return 'HTTP ' . $status;
        }
        $type = strtolower($r['type']);
        if (strpos($type, 'html') !== false) {
            if (strpos($r['body'], '<h1>500</h1>') !== false) {
                return 'hata sayfasi';
            }
            if (stripos($r['body'], '</html>') === false) {
                return 'sayfa yarida kesiliyor';
            }
        } elseif (strpos($type, 'xml') !== false && !preg_match('#</(urlset|sitemapindex|rss|feed)>\s*$#', $r['body'])) {
            return 'XML yarida kesiliyor';
        }
        return null;
    }

    /**
     * @param list<string> $problems fail the check
     * @param list<string> $notes    warn (redirects, slow pages)
     * @param array<string, array{status: int, ms: int}> $results
     * @param list<string> $skipped  shown only (pages the current role may not open)
     */
    private function pagesRow(string $label, int $total, array $problems, array $notes, int $blocked, array $results, array $skipped = []): array
    {
        $ms = array_column($results, 'ms');
        $timing = $ms === [] ? '' : 'toplam ' . round(array_sum($ms) / 1000, 1) . ' sn, en yavas ' . max($ms) . ' ms';
        $skippedText = $skipped === [] ? '' : 'atlanan: ' . $this->listed($skipped);
        $detail = implode(' | ', array_filter([$this->listed(array_merge($problems, $notes)), $skippedText, $timing]));
        $total -= count($skipped);

        if ($problems !== []) {
            return $this->row($label, 'fail', count($problems) . '/' . $total . ' sayfada sorun', $detail);
        }
        if ($blocked > 0) {
            return $this->row($label, 'warn', $blocked . ' istek Cloudflare tarafindan engellendi', 'Kalan ' . ($total - $blocked) . ' sayfa sorunsuz | ' . $detail);
        }
        if ($notes !== []) {
            return $this->row($label, 'warn', ($total - count($notes)) . '/' . $total . ' sayfa sorunsuz', $detail);
        }
        return $this->row($label, 'ok', $total . ' sayfanin hepsi sorunsuz', implode(' | ', array_filter([$skippedText, $timing])));
    }

    /**
     * GET every URL, $concurrency at a time, without following redirects.
     *
     * @param list<string> $urls
     * @return array<string, array{status: int, ms: int, type: string, body: string, location: string, error: string, blocked: bool}>
     */
    private function fetchAll(array $urls, ?string $cookie, int $concurrency): array
    {
        if (!function_exists('curl_multi_init')) {
            throw new \RuntimeException('PHP curl eklentisi yok');
        }
        $results = [];
        $queue = $urls;
        $running = [];
        $mh = curl_multi_init();

        while ($queue !== [] || $running !== []) {
            while ($queue !== [] && count($running) < $concurrency) {
                $url = array_shift($queue);
                $ch = curl_init($url);
                $opts = [
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_HEADER => true,
                    CURLOPT_FOLLOWLOCATION => false,
                    CURLOPT_CONNECTTIMEOUT => 5,
                    CURLOPT_TIMEOUT => 20,
                    CURLOPT_ENCODING => '',
                    CURLOPT_USERAGENT => self::USER_AGENT,
                    CURLOPT_HTTPHEADER => ['Accept: text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8'],
                ];
                if ($cookie !== null) {
                    $opts[CURLOPT_COOKIE] = $cookie;
                }
                $connectTo = $this->localConnectTo($url);
                if ($connectTo !== null) {
                    $opts[CURLOPT_CONNECT_TO] = [$connectTo];
                }
                curl_setopt_array($ch, $opts);
                curl_multi_add_handle($mh, $ch);
                $running[spl_object_id($ch)] = [$ch, $url, microtime(true)];
            }

            do {
                $status = curl_multi_exec($mh, $active);
            } while ($status === CURLM_CALL_MULTI_PERFORM);
            if ($active) {
                curl_multi_select($mh, 1.0);
            }

            while (($info = curl_multi_info_read($mh)) !== false) {
                $ch = $info['handle'];
                [, $url, $started] = $running[spl_object_id($ch)];
                unset($running[spl_object_id($ch)]);

                $raw = (string)curl_multi_getcontent($ch);
                $headerSize = (int)curl_getinfo($ch, CURLINFO_HEADER_SIZE);
                $headers = strtolower(substr($raw, 0, $headerSize));
                $body = substr($raw, $headerSize);
                $code = (int)curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
                $results[$url] = [
                    'status' => $code,
                    'ms' => (int)round((microtime(true) - $started) * 1000),
                    'type' => (string)curl_getinfo($ch, CURLINFO_CONTENT_TYPE),
                    'body' => $body,
                    'location' => (string)curl_getinfo($ch, CURLINFO_REDIRECT_URL),
                    'error' => $info['result'] === CURLE_OK ? '' : curl_strerror($info['result']),
                    // Cloudflare may challenge the server's own requests; that is not a site error.
                    'blocked' => in_array($code, [403, 503], true)
                        && (strpos($headers, 'cf-mitigated') !== false || strpos($body, 'Just a moment') !== false),
                ];
                curl_multi_remove_handle($mh, $ch);
            }
        }
        curl_multi_close($mh);

        return $results;
    }

    /** Local Docker only: the host's localhost:8080 is Apache's port 80 inside this container. */
    private function localConnectTo(string $url): ?string
    {
        $host = strtolower((string)parse_url($url, PHP_URL_HOST));
        $isLocalHost = $host === 'localhost' || $host === '127.0.0.1' || substr($host, -10) === '.localhost';
        if (!$isLocalHost || !is_file('/.dockerenv')) {
            return null;
        }
        $scheme = (string)parse_url($url, PHP_URL_SCHEME);
        $port = (int)(parse_url($url, PHP_URL_PORT) ?: ($scheme === 'https' ? 443 : 80));
        return $host . ':' . $port . ':127.0.0.1:80';
    }

    private function shortUrl(string $url): string
    {
        $host = (string)parse_url($url, PHP_URL_HOST);
        $path = (string)parse_url($url, PHP_URL_PATH);
        $query = (string)parse_url($url, PHP_URL_QUERY);
        $mainHost = (string)parse_url(SITE_URL, PHP_URL_HOST);
        return ($host === $mainHost ? '' : $host) . ($path === '' ? '/' : $path) . ($query !== '' ? '?' . $query : '');
    }

    /** @param list<string> $items */
    private function listed(array $items): string
    {
        $shown = array_slice($items, 0, self::MAX_LISTED);
        $more = count($items) - count($shown);
        return implode('; ', $shown) . ($more > 0 ? '; +' . $more . ' daha' : '');
    }

    private function row(string $label, string $status, string $message, string $detail): array
    {
        return ['label' => $label, 'status' => $status, 'message' => $message, 'detail' => $detail];
    }
}
