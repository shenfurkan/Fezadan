<?php

namespace App\Core;

use Aws\S3\S3Client;
use Aws\Exception\AwsException;

class R2Storage {
    private $client;
    private $bucketName;
    private $publicUrl;

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
            error_log('R2Storage: eksik yapılandırma anahtarları: ' . implode(', ', $missing));
            throw new \RuntimeException('R2 storage yapılandırması eksik.');
        }

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

    public function uploadPDF($tempFilePath, $originalFileName) {
        $extension = strtolower(pathinfo((string)$originalFileName, PATHINFO_EXTENSION));
        if ($extension !== 'pdf') {
            $extension = 'pdf';
        }
        $safeName = md5(uniqid('', true)) . '-' . time() . '.' . $extension;
        $objectKey = 'notlar/' . $safeName;

        try {
            $this->client->putObject([
                'Bucket'      => $this->bucketName,
                'Key'         => $objectKey,
                'SourceFile'  => $tempFilePath,
                'ContentType' => 'application/pdf',
            ]);

            return $objectKey;
        } catch (AwsException $e) {
            error_log("R2 PDF yükleme hatası: " . $e->getMessage());
            return false;
        }
    }

    public function uploadFile(string $sourceFile, string $objectKey, string $contentType): ?string {
        try {
            $objectKey = ltrim($objectKey, '/');
            $this->client->putObject([
                'Bucket'       => $this->bucketName,
                'Key'          => $objectKey,
                'SourceFile'   => $sourceFile,
                'ContentType'  => $contentType,
                'CacheControl' => 'public, max-age=31536000, immutable',
            ]);
            return $objectKey;
        } catch (AwsException $e) {
            error_log("R2 dosya yükleme hatası: " . $e->getMessage());
            return null;
        }
    }

    public function deletePDF($objectKey) {
        try {
            $this->client->deleteObject([
                'Bucket' => $this->bucketName,
                'Key'    => $this->normalizeObjectKey((string)$objectKey),
            ]);
            return true;
        } catch (AwsException $e) {
            error_log("R2 silme hatası: " . $e->getMessage());
            return false;
        }
    }

    public function deleteFile(string $objectKey): bool {
        try {
            $this->client->deleteObject([
                'Bucket' => $this->bucketName,
                'Key'    => $this->normalizeObjectKey($objectKey),
            ]);
            return true;
        } catch (AwsException $e) {
            error_log("R2 dosya silme hatası: " . $e->getMessage());
            return false;
        }
    }

    public function getFileUrl($objectKey) {
        if (preg_match('#^https?://#i', (string)$objectKey)) {
            return (string)$objectKey;
        }
        return rtrim($this->publicUrl, '/') . '/' . $this->normalizeObjectKey((string)$objectKey);
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

            if ($rangeHeader !== '' && preg_match('/bytes=(\d*)-(\d*)/i', $rangeHeader, $matches)) {
                $head = $this->client->headObject([
                    'Bucket' => $this->bucketName,
                    'Key'    => $normalizedKey,
                ]);
                $fileSize = (int)($head['ContentLength'] ?? 0);

                if ($fileSize > 0) {
                    $start = $matches[1] === '' ? 0 : (int)$matches[1];
                    $end = $matches[2] === '' ? ($fileSize - 1) : (int)$matches[2];

                    if ($start >= $fileSize) {
                        http_response_code(416);
                        header('Content-Range: bytes */' . $fileSize);
                        exit;
                    }

                    $end = min($end, $fileSize - 1);
                    if ($end < $start) {
                        $end = $start;
                    }

                    $getParams['Range'] = 'bytes=' . $start . '-' . $end;
                    $statusCode = 206;
                    $contentRange = 'bytes ' . $start . '-' . $end . '/' . $fileSize;
                    $contentLength = $end - $start + 1;
                }
            }

            $result = $this->client->getObject($getParams);
            $safeName = str_replace([' ', '/', '\\', '"'], '_', (string)$displayName) . '.pdf';
            $mimeType = (string)($result['ContentType'] ?? 'application/pdf');
            $finalLength = $contentLength ?? (int)($result['ContentLength'] ?? 0);

            http_response_code($statusCode);
            header('Content-Type: ' . $mimeType);
            header('Content-Disposition: inline; filename="' . $safeName . '"');
            header('Accept-Ranges: bytes');
            header('Cache-Control: public, max-age=300');

            if ($contentRange !== null) {
                header('Content-Range: ' . $contentRange);
            }
            if ($finalLength > 0) {
                header('Content-Length: ' . $finalLength);
            }

            echo $result['Body'];
            exit;
        } catch (AwsException $e) {
            error_log("R2 görüntüleme hatası: " . $e->getMessage());
            http_response_code(500);
            echo "Belge şu anda görüntülenemiyor.";
            exit;
        }
    }

    public function streamPublicFile(string $objectKey): void {
        try {
            if (ob_get_level()) ob_end_clean();

            $normalizedKey = $this->normalizeObjectKey($objectKey);
            if ($normalizedKey === ''
                || strpos($normalizedKey, '..') !== false
                || strpos($normalizedKey, 'uploads/') !== 0
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

            echo $result['Body'];
            exit;
        } catch (AwsException $e) {
            error_log("R2 public dosya hatası: " . $e->getMessage());
            http_response_code(404);
            echo 'Dosya bulunamadı.';
            exit;
        }
    }

    public function streamDownload($objectKey, $displayName) {
        try {
            if (ob_get_level()) ob_end_clean();

            $result = $this->client->getObject([
                'Bucket' => $this->bucketName,
                'Key'    => $this->normalizeObjectKey((string)$objectKey),
            ]);

            $safeName = str_replace([' ', '/', '\\', '"'], '_', (string)$displayName) . '.pdf';

            header('Content-Description: File Transfer');
            header('Content-Type: application/pdf');
            header('Content-Disposition: attachment; filename="' . $safeName . '"');
            header('Content-Transfer-Encoding: binary');
            header('Expires: 0');
            header('Cache-Control: must-revalidate');
            header('Pragma: public');
            header('Content-Length: ' . $result['ContentLength']);

            echo $result['Body'];
            exit;
        } catch (AwsException $e) {
            error_log("R2 indirme hatası: " . $e->getMessage());
            http_response_code(500);
            echo "Dosya şu anda indirilemiyor.";
            exit;
        }
    }
}
