<?php
declare(strict_types=1);

ini_set('display_errors', '0');
error_reporting(E_ALL);

function deploy_env(string $key, string $default = ''): string
{
    $value = getenv($key);
    if (is_string($value) && $value !== '') {
        return $value;
    }

    $envPath = dirname(__DIR__, 2) . '/.env';
    if (!is_file($envPath)) {
        return $default;
    }

    $lines = file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    if (!is_array($lines)) {
        return $default;
    }

    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '' || $line[0] === '#' || $line[0] === '!') {
            continue;
        }
        $parts = explode('=', $line, 2);
        if (count($parts) !== 2 || trim($parts[0]) !== $key) {
            continue;
        }
        return trim(trim($parts[1]), "\"'");
    }

    return $default;
}

function deploy_log(string $message): void
{
    error_log('[deploy] ' . $message);
}

function deploy_run(string $command): bool
{
    $output = [];
    $code = 0;
    exec($command . ' 2>&1', $output, $code);
    deploy_log('command=' . preg_replace('/\s+/', ' ', $command) . ' exit=' . $code);
    if ($code !== 0) {
        deploy_log('output=' . substr(implode("\n", $output), 0, 4000));
    }
    return $code === 0;
}

$secret = deploy_env('DEPLOY_SECRET');
if ($secret === '') {
    deploy_log('DEPLOY_SECRET is empty; deploy disabled');
    http_response_code(503);
    echo "Deploy unavailable.\n";
    exit;
}

$signature = $_SERVER['HTTP_X_HUB_SIGNATURE_256'] ?? '';
$payload = file_get_contents('php://input') ?: '';
$expected = 'sha256=' . hash_hmac('sha256', $payload, $secret);

if (!is_string($signature) || !hash_equals($expected, $signature)) {
    deploy_log('invalid signature from ' . ($_SERVER['REMOTE_ADDR'] ?? 'unknown'));
    http_response_code(403);
    echo "Forbidden.\n";
    exit;
}

$repo = '/home/fezadano5/repo_yedek';
$home = '/home/fezadano5';
$webRoot = '/home/fezadano5/public_html';

deploy_log('deploy started');

$ok = true;
$ok = deploy_run('cd ' . escapeshellarg($repo) . ' && git fetch --all') && $ok;
$resetMain = deploy_run('cd ' . escapeshellarg($repo) . ' && git reset --hard origin/main');
if (!$resetMain) {
    $ok = deploy_run('cd ' . escapeshellarg($repo) . ' && git reset --hard origin/master') && $ok;
}

$ok = deploy_run('cp -Rf ' . escapeshellarg($repo . '/app') . '/* ' . escapeshellarg($home . '/app') . '/') && $ok;
if (is_dir($repo . '/cron')) {
    if (!is_dir($home . '/cron')) {
        @mkdir($home . '/cron', 0755, true);
    }
    $ok = deploy_run('cp -Rf ' . escapeshellarg($repo . '/cron') . '/* ' . escapeshellarg($home . '/cron') . '/') && $ok;
}

$ok = deploy_run('cp -Rf ' . escapeshellarg($repo . '/public_html/assets') . '/* ' . escapeshellarg($webRoot . '/assets') . '/') && $ok;
$ok = deploy_run('cp -Rf ' . escapeshellarg($repo . '/public_html/cdn') . '/* ' . escapeshellarg($webRoot . '/cdn') . '/') && $ok;
if (is_dir($repo . '/public_html/inc')) {
    $ok = deploy_run('cp -Rf ' . escapeshellarg($repo . '/public_html/inc') . '/* ' . escapeshellarg($webRoot . '/inc') . '/') && $ok;
}

$ok = deploy_run('find ' . escapeshellarg($repo . '/public_html') . ' -maxdepth 1 -name ' . escapeshellarg('*.php') . ' ! -name ' . escapeshellarg('config.php') . ' -exec cp -f {} ' . escapeshellarg($webRoot) . '/ \;') && $ok;
$ok = deploy_run('cp -f ' . escapeshellarg($repo . '/public_html/.htaccess') . ' ' . escapeshellarg($webRoot) . '/') && $ok;
$ok = deploy_run('find ' . escapeshellarg($repo . '/public_html') . ' -maxdepth 1 -name ' . escapeshellarg('*.txt') . ' -exec cp -f {} ' . escapeshellarg($webRoot) . '/ \;') && $ok;

if (is_file($repo . '/composer.json')) {
    @copy($repo . '/composer.json', $home . '/composer.json');
    if (is_file($repo . '/composer.lock')) {
        @copy($repo . '/composer.lock', $home . '/composer.lock');
    }
    $ok = deploy_run('cd ' . escapeshellarg($home) . ' && composer dump-autoload --optimize --classmap-authoritative --no-dev') && $ok;
}

if (!$ok) {
    deploy_log('deploy finished with errors');
    http_response_code(500);
    echo "Deploy failed.\n";
    exit;
}

deploy_log('deploy completed');
http_response_code(204);
