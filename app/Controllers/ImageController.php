<?php
require_once dirname(__DIR__) . '/Services/CoverImage.php';

class ImageController extends Controller
{
    public function cover(string $articleId, string $width, string $filename): void
    {
        if (!in_array($_SERVER['REQUEST_METHOD'] ?? 'GET', ['GET', 'HEAD'], true)) {
            http_response_code(405);
            header('Allow: GET, HEAD');
            return;
        }
        if (!preg_match('/^[1-9][0-9]{0,9}$/D', $articleId) || $width !== (string)(int)$width
            || !in_array((int)$width, CoverImage::WIDTHS, true)
            || !preg_match('/^[a-f0-9]{16}\.(webp|jpg)$/D', $filename, $match)) {
            http_response_code(404);
            return;
        }
        // A client can select a published article and one fixed width, never a URL or R2 key.
        $stmt = Db::pdo()->prepare('SELECT a.image_url FROM articles a WHERE a.id = ? AND ' . ArticleQueries::PUBLISHED . ' LIMIT 1');
        $stmt->execute([(int)$articleId]);
        $source = (string)($stmt->fetchColumn() ?: '');
        $key = CoverImage::sourceKey($source);
        $format = $match[1];
        if ($key === null || $filename !== CoverImage::version($source) . '.' . $format) {
            http_response_code(404);
            return;
        }
        $cacheDir = sys_get_temp_dir() . '/fezadan-cover-cache';
        $cached = $cacheDir . '/' . $articleId . '-' . $width . '-' . $filename;
        try {
            if (!is_file($cached)) {
                if (!is_dir($cacheDir) && !@mkdir($cacheDir, 0755, true) && !is_dir($cacheDir)) {
                    throw new \RuntimeException('Cover cache directory is unavailable.');
                }
                require_once ROOT . '/app/Core/R2Storage.php';
                $bytes = \App\Core\R2Storage::instance()->readCoverImage($key, CoverImage::MAX_BYTES);
                $output = CoverImage::encode($bytes, (int)$width, $format);
                $temp = tempnam($cacheDir, 'cover_');
                if ($temp === false) throw new \RuntimeException('Cover cache could not be created.');
                try {
                    if (file_put_contents($temp, $output) !== strlen($output) || !rename($temp, $cached)) {
                        throw new \RuntimeException('Cover cache could not be written.');
                    }
                    @chmod($cached, 0644);
                } finally {
                    if (is_file($temp)) @unlink($temp);
                }
            }
            $etag = '"' . basename($cached) . '"';
            header('Content-Type: ' . ($format === 'jpg' ? 'image/jpeg' : 'image/webp'));
            header('Cache-Control: public, max-age=31536000, immutable');
            header('ETag: ' . $etag);
            if (trim($_SERVER['HTTP_IF_NONE_MATCH'] ?? '') === $etag) {
                http_response_code(304);
                return;
            }
            header('Content-Length: ' . filesize($cached));
            if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'HEAD') readfile($cached);
        } catch (\Throwable $error) {
            error_log('Cover rendition failed: ' . $error->getMessage());
            // Encoding/storage failures must not replace a working cover with a broken image.
            header('Cache-Control: private, no-store, max-age=0');
            header('Location: ' . ($format === 'jpg' ? rtrim(SITE_URL, '/') . '/cdn/logo-light.png' : Upload::assetUrl($source)), true, 302);
        }
    }
}
