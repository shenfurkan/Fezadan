<?php

namespace App\Core;

use Aws\S3\S3Client;
use Aws\Exception\AwsException;

class R2Storage {
    private $client;
    private $bucketName;
    private $backupBucketName;
    private $publicUrl;
    private $lastAwsError = [];

    /** @var R2Storage|null */
    private static $instance = null;

    public static function instance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function envValue(array $env, string $key, string $default = ''): string
    {
        $value = getenv($key);
        if (is_string($value) && $value !== '') {
            return $value;
        }
        return isset($env[$key]) && is_string($env[$key]) ? $env[$key] : $default;
    }

    private function normalizeObjectKey(string $objectKey): string
    {
        $objectKey = trim($objectKey);
        if ($objectKey === '') {
            return '';
        }

        if (preg_match('#^https?://#i', $objectKey)) {
            $path = (string)parse_url($objectKey, PHP_URL_PATH);
            $objectKey = ltrim($path, '/');
        }

        return ltrim($objectKey, '/');
    }

    public function getLastAwsError(): array
    {
        return $this->lastAwsError;
    }

    private function clearLastAwsError(): void
    {
        $this->lastAwsError = [];
    }

    private function rememberAwsError(string $operation, AwsException $e, array $context = []): void
    {
        $this->lastAwsError = [
            'operation' => $operation,
            'aws_code' => $e->getAwsErrorCode(),
            'status_code' => $e->getStatusCode(),
            'message' => $e->getMessage(),
        ] + $context;

        error_log('[R2Storage][CP] ' . $operation . '_exception ' . json_encode($this->lastAwsError, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
        if (class_exists('\\AdminLog')) {
            \AdminLog::write('error', 'R2_EXCEPTION', $this->lastAwsError);
        }
    }

    public function __construct() {
        $envPath = ROOT . '/.env';
        $env = [];

        if (file_exists($envPath)) {
            $lines = file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            foreach ($lines as $line) {
                $line = trim((string)$line);
                if ($line === '' || $line[0] === '#' || $line[0] === '!') {
                    continue;
                }
                $parts = explode('=', $line, 2);
                if (count($parts) !== 2) {
                    continue;
                }
                [$name, $value] = $parts;
                $name = trim($name);
                if ($name !== '') {
                    $env[$name] = trim(trim($value), '"\'');
                }
            }
        }

        $accountId = $this->envValue($env, 'R2_ACCOUNT_ID');
        $accessKeyId = $this->envValue($env, 'R2_ACCESS_KEY_ID');
        $accessKeySecret = $this->envValue($env, 'R2_SECRET_ACCESS_KEY');
        $this->bucketName = $this->envValue($env, 'R2_BUCKET_NAME');
        $this->backupBucketName = $this->envValue($env, 'R2_BACKUP_BUCKET_NAME');
        $this->publicUrl = $this->envValue($env, 'R2_PUBLIC_URL', defined('CDN_URL') ? CDN_URL : '');

        $missing = [];
        foreach ([
            'R2_ACCOUNT_ID' => $accountId,
            'R2_ACCESS_KEY_ID' => $accessKeyId,
            'R2_SECRET_ACCESS_KEY' => $accessKeySecret,
            'R2_BUCKET_NAME' => $this->bucketName,
            'R2_PUBLIC_URL' => $this->publicUrl,
        ] as $key => $value) {
            if (trim((string)$value) === '') {
                $missing[] = $key;
            }
        }

        if (!empty($missing)) {
            if (getenv('APP_ENV') !== 'testing') {
                error_log('R2Storage: eksik yapılandırma anahtarları: ' . implode(', ', $missing));
                throw new \RuntimeException('R2 storage yapılandırması eksik.');
            }
        }

        if (getenv('APP_ENV') !== 'testing') {
            $this->client = new S3Client([
                'version'     => 'latest',
                'region'      => 'auto',
                'endpoint'    => "https://{$accountId}.r2.cloudflarestorage.com",
                'credentials' => [
                    'key'    => $accessKeyId,
                    'secret' => $accessKeySecret,
                ],
            ]);
        }
    }

    public function uploadPDF($tempFilePath, $originalFileName) {
        $this->clearLastAwsError();
        $extension = strtolower(pathinfo((string)$originalFileName, PATHINFO_EXTENSION));
        if ($extension !== 'pdf') {
            $extension = 'pdf';
        }
        $safeName = md5(uniqid('', true)) . '-' . time() . '.' . $extension;
        $objectKey = 'notlar/' . $safeName;

        if (getenv('APP_ENV') === 'testing') {
            return $objectKey;
        }

        try {
            error_log('[R2Storage][CP] upload_pdf_start key=' . $objectKey . ' source_bytes=' . (is_file($tempFilePath) ? (int)filesize($tempFilePath) : 0));
            $this->client->putObject([
                'Bucket'       => $this->bucketName,
                'Key'          => $objectKey,
                'SourceFile'   => $tempFilePath,
                'ContentType'  => 'application/pdf',
                // Keys are hash+timestamp, so PDFs never change; this is what lets the CDN cache them.
                'CacheControl' => 'public, max-age=31536000, immutable',
            ]);

            error_log('[R2Storage][CP] upload_pdf_done key=' . $objectKey);
            return $objectKey;
        } catch (AwsException $e) {
            $this->rememberAwsError('upload_pdf', $e, ['object_key' => $objectKey]);
            return false;
        }
    }

    public function uploadFile(string $sourceFile, string $objectKey, string $contentType): ?string {
        $this->clearLastAwsError();
        if (getenv('APP_ENV') === 'testing') {
            return ltrim($objectKey, '/');
        }
        try {
            $objectKey = ltrim($objectKey, '/');
            error_log('[R2Storage][CP] upload_file_start key=' . $objectKey . ' content_type=' . $contentType . ' source_bytes=' . (is_file($sourceFile) ? (int)filesize($sourceFile) : 0));
            $this->client->putObject([
                'Bucket'       => $this->bucketName,
                'Key'          => $objectKey,
                'SourceFile'   => $sourceFile,
                'ContentType'  => $contentType,
                'CacheControl' => 'public, max-age=31536000, immutable',
            ]);
            error_log('[R2Storage][CP] upload_file_done key=' . $objectKey);
            return $objectKey;
        } catch (AwsException $e) {
            $this->rememberAwsError('upload_file', $e, [
                'object_key' => $objectKey,
                'content_type' => $contentType,
            ]);
            return null;
        }
    }

    public function uploadObject(string $objectKey, string $body, string $contentType): ?string {
        $this->clearLastAwsError();
        $objectKey = $this->normalizeObjectKey($objectKey);
        if (getenv('APP_ENV') === 'testing') {
            return $objectKey;
        }
        try {
            $this->client->putObject([
                'Bucket'       => $this->bucketName,
                'Key'          => $objectKey,
                'Body'         => $body,
                'ContentType'  => $contentType,
                'CacheControl' => 'public, max-age=31536000, immutable',
            ]);
            return $objectKey;
        } catch (AwsException $e) {
            $this->rememberAwsError('upload_object', $e, [
                'object_key' => $objectKey,
                'content_type' => $contentType,
            ]);
            return null;
        }
    }

    private function backupBucket(): string
    {
        $bucket = trim((string)$this->backupBucketName);
        if ($bucket === '') {
            throw new \RuntimeException('R2_BACKUP_BUCKET_NAME tanımlı değil; veritabanı yedeği public medya bucketına yüklenemez.');
        }
        return $bucket;
    }

    public function uploadBackupFile(string $sourceFile, string $objectKey, string $contentType): ?string
    {
        $this->clearLastAwsError();
        $objectKey = $this->normalizeObjectKey($objectKey);
        if (strpos($objectKey, 'backups/') !== 0) {
            throw new \InvalidArgumentException('Backup object key backups/ ile başlamalı.');
        }
        if (getenv('APP_ENV') === 'testing') {
            return $objectKey;
        }

        try {
            $this->client->putObject([
                'Bucket'      => $this->backupBucket(),
                'Key'         => $objectKey,
                'SourceFile'  => $sourceFile,
                'ContentType' => $contentType,
            ]);
            return $objectKey;
        } catch (AwsException $e) {
            $this->rememberAwsError('upload_backup_file', $e, [
                'object_key' => $objectKey,
                'content_type' => $contentType,
            ]);
            return null;
        }
    }

    public function listBackupKeys(string $prefix = 'backups/db-'): array
    {
        if (getenv('APP_ENV') === 'testing') {
            return [];
        }

        $result = $this->client->listObjectsV2([
            'Bucket' => $this->backupBucket(),
            'Prefix' => $this->normalizeObjectKey($prefix),
        ]);

        return $result['Contents'] ?? [];
    }

    public function deleteBackupFile(string $objectKey): bool
    {
        if (getenv('APP_ENV') === 'testing') {
            return true;
        }

        try {
            $this->client->deleteObject([
                'Bucket' => $this->backupBucket(),
                'Key'    => $this->normalizeObjectKey($objectKey),
            ]);
            return true;
        } catch (AwsException $e) {
            $this->rememberAwsError('delete_backup_file', $e, ['object_key' => $objectKey]);
            return false;
        }
    }

    public function deletePDF($objectKey) {
        $this->clearLastAwsError();
        if (getenv('APP_ENV') === 'testing') {
            return true;
        }
        try {
            $this->client->deleteObject([
                'Bucket' => $this->bucketName,
                'Key'    => $this->normalizeObjectKey((string)$objectKey),
            ]);
            return true;
        } catch (AwsException $e) {
            $this->rememberAwsError('delete_pdf', $e, ['object_key' => (string)$objectKey]);
            return false;
        }
    }

    public function deleteFile(string $objectKey): bool {
        $this->clearLastAwsError();
        if (getenv('APP_ENV') === 'testing') {
            return true;
        }
        try {
            $this->client->deleteObject([
                'Bucket' => $this->bucketName,
                'Key'    => $this->normalizeObjectKey($objectKey),
            ]);
            return true;
        } catch (AwsException $e) {
            $this->rememberAwsError('delete_file', $e, ['object_key' => $objectKey]);
            return false;
        }
    }

    public function deleteObject(string $objectKey): bool {
        return $this->deleteFile($objectKey);
    }

    /**
     * Read-only HEAD request. Returns false only when the object is missing (404);
     * any other storage error is thrown so callers can tell "missing" from "unreachable".
     */
    public function objectExists(string $objectKey): bool {
        if (getenv('APP_ENV') === 'testing') {
            return true;
        }
        try {
            $this->client->headObject([
                'Bucket' => $this->bucketName,
                'Key'    => $this->normalizeObjectKey($objectKey),
            ]);
            return true;
        } catch (AwsException $e) {
            $status = $e->getResponse() ? $e->getResponse()->getStatusCode() : 0;
            if ($status === 404) {
                return false;
            }
            throw $e;
        }
    }

    /** @return array{0:int,1:int}|null */
    public static function parseHttpRange(string $rangeHeader, int $fileSize): ?array {
        if ($fileSize <= 0 || !preg_match('/^bytes=(\d*)-(\d*)$/i', trim($rangeHeader), $matches)) {
            return null;
        }

        if ($matches[1] === '' && $matches[2] === '') {
            return null;
        }

        if ($matches[1] === '') {
            $suffixLength = (int)$matches[2];
            if ($suffixLength <= 0) {
                return null;
            }
            $start = max(0, $fileSize - $suffixLength);
            $end = $fileSize - 1;
            return [$start, $end];
        }

        $start = (int)$matches[1];
        if ($start >= $fileSize) {
            return null;
        }

        $end = $matches[2] === '' ? ($fileSize - 1) : (int)$matches[2];
        $end = min($end, $fileSize - 1);
        if ($end < $start) {
            return null;
        }

        return [$start, $end];
    }

    private function streamAwsBody($body): void {
        if (is_object($body) && method_exists($body, 'eof') && method_exists($body, 'read')) {
            while (!$body->eof()) {
                echo $body->read(8192);
                if (function_exists('flush')) {
                    flush();
                }
            }
            return;
        }

        echo $body;
    }

    public function getFileUrl($objectKey) {
        if (strpos($this->normalizeObjectKey((string)$objectKey), 'backups/') === 0) {
            throw new \RuntimeException('Private backup objectleri public URL olarak yayınlanamaz.');
        }
        if (preg_match('#^https?://#i', (string)$objectKey)) {
            return (string)$objectKey;
        }
        return rtrim($this->publicUrl, '/') . '/' . $this->normalizeObjectKey((string)$objectKey);
    }

    /** ASCII fallback plus RFC 5987 filename: safe headers and correct Turkish filenames. */
    public static function pdfContentDisposition(string $name, string $type = 'inline'): string {
        $name = preg_replace('/[\x00-\x1f\x7f"\/\\\\]/', '_', $name) . '.pdf';
        $fallback = preg_replace('/[^a-zA-Z0-9._-]/', '_', $name);
        return ($type === 'attachment' ? 'attachment' : 'inline')
            . '; filename="' . $fallback . '"; filename*=UTF-8\'\'' . rawurlencode($name);
    }

    public function streamView($objectKey, $displayName = 'belge') {
        try {
            if (ob_get_level()) ob_end_clean();

            $normalizedKey = $this->normalizeObjectKey((string)$objectKey);
            if ($normalizedKey === '') {
                http_response_code(404);
                echo 'Belge bulunamadı.';
                exit;
            }

            $getParams = [
                'Bucket' => $this->bucketName,
                'Key'    => $normalizedKey,
            ];

            $statusCode = 200;
            $contentRange = null;
            $contentLength = null;
            $rangeHeader = $_SERVER['HTTP_RANGE'] ?? '';
            $requestMethod = strtoupper((string)($_SERVER['REQUEST_METHOD'] ?? 'GET'));
            $headResult = null;

            if ($rangeHeader !== '') {
                $headResult = $this->client->headObject([
                    'Bucket' => $this->bucketName,
                    'Key'    => $normalizedKey,
                ]);
                $fileSize = (int)($headResult['ContentLength'] ?? 0);

                if ($fileSize > 0) {
                    $range = self::parseHttpRange($rangeHeader, $fileSize);
                    if ($range === null) {
                        http_response_code(416);
                        header('Content-Range: bytes */' . $fileSize);
                        exit;
                    }

                    [$start, $end] = $range;

                    $getParams['Range'] = 'bytes=' . $start . '-' . $end;
                    $statusCode = 206;
                    $contentRange = 'bytes ' . $start . '-' . $end . '/' . $fileSize;
                    $contentLength = $end - $start + 1;
                }
            }

            if ($requestMethod === 'HEAD') {
                if ($headResult === null) {
                    $headResult = $this->client->headObject([
                        'Bucket' => $this->bucketName,
                        'Key'    => $normalizedKey,
                    ]);
                }

                $mimeType = (string)($headResult['ContentType'] ?? 'application/pdf');
                $finalLength = $contentLength ?? (int)($headResult['ContentLength'] ?? 0);

                http_response_code($statusCode);
                header('Content-Type: ' . $mimeType);
                header('Content-Disposition: ' . self::pdfContentDisposition((string)$displayName));
                header('Accept-Ranges: bytes');
                header('Cache-Control: private, no-store, max-age=0');
                if ($contentRange !== null) {
                    header('Content-Range: ' . $contentRange);
                }
                if ($finalLength > 0) {
                    header('Content-Length: ' . $finalLength);
                }
                exit;
            }

            $result = $this->client->getObject($getParams);
            $mimeType = (string)($result['ContentType'] ?? 'application/pdf');
            $finalLength = $contentLength ?? (int)($result['ContentLength'] ?? 0);

            http_response_code($statusCode);
            header('Content-Type: ' . $mimeType);
            header('Content-Disposition: ' . self::pdfContentDisposition((string)$displayName));
            header('Accept-Ranges: bytes');
            header('Cache-Control: private, no-store, max-age=0');

            if ($contentRange !== null) {
                header('Content-Range: ' . $contentRange);
            }
            if ($finalLength > 0) {
                header('Content-Length: ' . $finalLength);
            }

            $this->streamAwsBody($result['Body']);
            exit;
        } catch (AwsException $e) {
            error_log("R2 görüntüleme hatası: " . $e->getMessage());
            http_response_code($e->getStatusCode() === 404 ? 404 : 502);
            header('Cache-Control: private, no-store, max-age=0');
            echo "Belge şu anda görüntülenemiyor.";
            exit;
        }
    }

    public function streamPublicFile(string $objectKey): void {
        if (getenv('APP_ENV') === 'testing') {
            http_response_code(200);
            header('Content-Type: image/webp');
            echo 'mock-image-data';
            exit;
        }
        try {
            if (ob_get_level()) ob_end_clean();

            $normalizedKey = $this->normalizeObjectKey($objectKey);
            if ($normalizedKey === ''
                || strpos($normalizedKey, '..') !== false
                || (strpos($normalizedKey, 'uploads/') !== 0 && strpos($normalizedKey, 'portfolio/') !== 0)
                || !preg_match('/\.(webp|jpe?g|png|gif)$/i', $normalizedKey)) {
                http_response_code(404);
                echo 'Dosya bulunamadı.';
                exit;
            }

            $result = $this->client->getObject([
                'Bucket' => $this->bucketName,
                'Key'    => $normalizedKey,
            ]);

            $ext = strtolower(pathinfo($normalizedKey, PATHINFO_EXTENSION));
            $fallbackTypes = [
                'webp' => 'image/webp',
                'jpg' => 'image/jpeg',
                'jpeg' => 'image/jpeg',
                'png' => 'image/png',
                'gif' => 'image/gif',
            ];
            $mimeType = (string)($result['ContentType'] ?? ($fallbackTypes[$ext] ?? 'application/octet-stream'));
            $fileName = basename($normalizedKey);

            http_response_code(200);
            header('Content-Type: ' . $mimeType);
            header('Content-Disposition: inline; filename="' . str_replace('"', '', $fileName) . '"');
            header('Cache-Control: public, max-age=31536000, immutable');
            if (!empty($result['ContentLength'])) {
                header('Content-Length: ' . (int)$result['ContentLength']);
            }

            $this->streamAwsBody($result['Body']);
            exit;
        } catch (AwsException $e) {
            error_log("R2 public dosya hatası: " . $e->getMessage());
            http_response_code(404);
            echo 'Dosya bulunamadı.';
            exit;
        }
    }

    /** Bounded read for article-cover renditions; no backups, PDFs, remote URLs or arbitrary keys. */
    public function readCoverImage(string $objectKey, int $maxBytes): string {
        if ($maxBytes < 1 || $maxBytes > 8388608 || strpos($objectKey, '..') !== false
            || !preg_match('#^uploads/covers/[A-Za-z0-9_-][A-Za-z0-9._-]*\.(?:webp|jpe?g|png|gif)$#iD', $objectKey)) {
            throw new \InvalidArgumentException('Invalid public cover image key.');
        }
        $result = $this->client->getObject([
            'Bucket' => $this->bucketName,
            'Key' => $objectKey,
            'Range' => 'bytes=0-' . $maxBytes,
            '@http' => ['connect_timeout' => 2, 'timeout' => 8],
            '@retries' => 0, // Fall back to the existing CDN cover rather than delaying first paint.
        ]);
        if ((int)($result['ContentLength'] ?? 0) > $maxBytes) {
            throw new \RuntimeException('Cover image exceeds the byte limit.');
        }
        $body = $result['Body'];
        $bytes = '';
        while (!$body->eof() && strlen($bytes) <= $maxBytes) {
            $bytes .= $body->read(min(8192, $maxBytes + 1 - strlen($bytes)));
        }
        if ($bytes === '' || strlen($bytes) > $maxBytes) {
            throw new \RuntimeException('Cover image exceeds the byte limit or is empty.');
        }
        return $bytes;
    }

    public function streamDownload($objectKey, $displayName) {
        try {
            if (ob_get_level()) ob_end_clean();

            $result = $this->client->getObject([
                'Bucket' => $this->bucketName,
                'Key'    => $this->normalizeObjectKey((string)$objectKey),
            ]);


            header('Content-Description: File Transfer');
            header('Content-Type: application/pdf');
            header('Content-Disposition: ' . self::pdfContentDisposition((string)$displayName, 'attachment'));
            header('Expires: 0');
            header('Cache-Control: private, no-store, max-age=0');
            header('Content-Length: ' . $result['ContentLength']);

            $this->streamAwsBody($result['Body']);
            exit;
        } catch (AwsException $e) {
            error_log("R2 indirme hatası: " . $e->getMessage());
            http_response_code($e->getStatusCode() === 404 ? 404 : 502);
            echo "Dosya şu anda indirilemiyor.";
            exit;
        }
    }
}
