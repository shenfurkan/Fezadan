<?php
/**
 * Görsel yükleme sertleştirme yardımcı sınıfı.
 * - Extension whitelist
 * - getimagesize ile gerçek MIME doğrulama
 * - GD ile yeniden işleme (polyglot/EXIF payload kırma)
 */
class Upload
{
    private static $lastError = '';
    private static $lastMeta = [];

    /** @var array<string,string> */
    private static $allowed = [
        'jpg'  => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'png'  => 'image/png',
        'webp' => 'image/webp',
        'gif'  => 'image/gif',
    ];

    public static function imageUploadError(array $file, int $maxBytes = 5242880): ?string
    {
        if (!isset($file['error'])) return 'Dosya bilgisi sunucuya ulaşmadı.';
        if ($file['error'] !== UPLOAD_ERR_OK) {
            $map = [
                UPLOAD_ERR_INI_SIZE => 'Dosya sunucunun upload_max_filesize limitini aşıyor.',
                UPLOAD_ERR_FORM_SIZE => 'Dosya form limitini aşıyor.',
                UPLOAD_ERR_PARTIAL => 'Dosya eksik yüklendi. Bağlantı kesilmiş olabilir.',
                UPLOAD_ERR_NO_FILE => 'Dosya seçilmedi.',
                UPLOAD_ERR_NO_TMP_DIR => 'Sunucuda geçici yükleme klasörü yok.',
                UPLOAD_ERR_CANT_WRITE => 'Sunucu dosyayı diske yazamadı.',
                UPLOAD_ERR_EXTENSION => 'Bir PHP eklentisi yüklemeyi durdurdu.',
            ];
            return $map[$file['error']] ?? 'Bilinmeyen yükleme hatası: ' . $file['error'];
        }
        if (empty($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) return 'Geçici dosya doğrulanamadı.';
        if (($file['size'] ?? 0) <= 0) return 'Dosya boş görünüyor.';
        if (($file['size'] ?? 0) > $maxBytes) return 'Dosya çok büyük. Maksimum limit: ' . round($maxBytes / 1048576) . 'MB.';

        $ext = strtolower(pathinfo($file['name'] ?? '', PATHINFO_EXTENSION));
        if (!isset(self::$allowed[$ext])) return 'Desteklenmeyen dosya tipi. İzin verilenler: jpg, jpeg, png, webp, gif.';

        $info = @getimagesize($file['tmp_name']);
        if (!$info || !isset($info['mime'])) return 'Dosya gerçek bir görsel olarak okunamadı.';
        if ($info['mime'] !== self::$allowed[$ext] && !($info['mime'] === 'image/jpeg' && in_array($ext, ['jpg', 'jpeg'], true))) {
            return 'Dosya uzantısı ile gerçek tipi uyuşmuyor. Algılanan tip: ' . $info['mime'];
        }

        return null;
    }

    public static function lastError(): string
    {
        return self::$lastError;
    }

    public static function lastMeta(): array
    {
        return self::$lastMeta;
    }

    private static function setLastError(string $message): void
    {
        self::$lastError = $message;
        if ($message !== '') {
            self::$lastMeta['error'] = $message;
        }
    }

    /**
     * Yüklenen $file'ı ($_FILES tek girdi) doğrular ve hedef dizine kaydeder.
     * Başarılı: dosya adı (string) döner. Hata: null.
     *
     * @param array $file       $_FILES['x']
     * @param string $destDir   Mutlak dizin (sonunda / olabilir)
     * @param string $prefix    uniqid prefix
     * @param int $maxBytes     Maksimum dosya boyutu (varsayılan 5MB)
     */
    public static function saveImage(array $file, string $destDir, string $prefix = 'img_', int $maxBytes = 5242880): ?string
    {
        if (!isset($file['error']) || $file['error'] !== UPLOAD_ERR_OK) return null;
        if (empty($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) return null;
        if (($file['size'] ?? 0) <= 0 || $file['size'] > $maxBytes) return null;

        $ext = strtolower(pathinfo($file['name'] ?? '', PATHINFO_EXTENSION));
        if (!isset(self::$allowed[$ext])) return null;

        $info = @getimagesize($file['tmp_name']);
        if (!$info || !isset($info['mime'])) return null;
        if ($info['mime'] !== self::$allowed[$ext]) {
            // Bazı kameralarda jpg/jpeg uyuşmazlığı tolere edilsin
            if (!($info['mime'] === 'image/jpeg' && in_array($ext, ['jpg', 'jpeg'], true))) {
                return null;
            }
        }

        if (!is_dir($destDir)) {
            @mkdir($destDir, 0755, true);
        }

        $newName = uniqid($prefix, true) . '.' . $ext;
        $destPath = rtrim($destDir, '/') . '/' . $newName;

        $saved = self::reencodeWithImagick($file['tmp_name'], $destPath, $ext);
        if (!$saved) {
            $saved = self::reencodeWithGd($file['tmp_name'], $destPath, $ext);
        }

        if ($saved) {
            return $newName;
        }

        // Fallback: doğrulanmış dosyayı taşı
        if (move_uploaded_file($file['tmp_name'], $destPath)) {
            return $newName;
        }
        return null;
    }

    private static function reencodeWithImagick(string $sourcePath, string $destPath, string $ext): bool
    {
        if (!class_exists('Imagick') || !is_file($sourcePath)) {
            return false;
        }

        try {
            $iw = new \Imagick($sourcePath);
            $iw->setIteratorIndex(0);

            switch ($ext) {
                case 'jpg':
                case 'jpeg':
                    $iw->setImageFormat('jpeg');
                    $iw->setImageCompressionQuality(88);
                    $iw->stripImage();
                    break;
                case 'png':
                    $iw->setImageFormat('png');
                    $iw->setImageCompressionQuality(88);
                    $iw->stripImage();
                    break;
                case 'webp':
                    $iw->setImageFormat('webp');
                    $iw->setImageCompressionQuality(88);
                    $iw->setOption('webp:method', '6');
                    $iw->stripImage();
                    break;
                case 'gif':
                    $iw->setImageFormat('gif');
                    break;
                default:
                    $iw->clear();
                    $iw->destroy();
                    return false;
            }

            $ok = (bool)$iw->writeImage($destPath);
            $iw->clear();
            $iw->destroy();
            return $ok;
        } catch (\Throwable $e) {
            return false;
        }
    }

    private static function reencodeWithGd(string $sourcePath, string $destPath, string $ext): bool
    {
        if (!function_exists('imagecreatefromstring') || !is_file($sourcePath)) {
            return false;
        }

        $raw = @file_get_contents($sourcePath);
        if ($raw === false) {
            return false;
        }

        $im = @imagecreatefromstring($raw);
        if ($im === false) {
            return false;
        }

        $ok = false;
        switch ($ext) {
            case 'jpg':
            case 'jpeg':
                $ok = @imagejpeg($im, $destPath, 88);
                break;
            case 'png':
                @imagesavealpha($im, true);
                $ok = @imagepng($im, $destPath, 6);
                break;
            case 'webp':
                @imagesavealpha($im, true);
                $ok = function_exists('imagewebp') ? @imagewebp($im, $destPath, 88) : false;
                break;
            case 'gif':
                $ok = @imagegif($im, $destPath);
                break;
        }

        @imagedestroy($im);
        return (bool)$ok;
    }


    public static function saveImageToR2(array $file, string $folder = 'uploads', string $prefix = 'img_', int $maxBytes = 5242880, string $slugBase = ''): ?string
    {
        $totalStarted = microtime(true);
        self::setLastError('');
        self::$lastMeta = [
            'original_name' => $file['name'] ?? '',
            'original_size' => (int)($file['size'] ?? 0),
            'max_bytes' => $maxBytes,
            'folder' => trim($folder, '/'),
            'prefix' => $prefix,
            'timings_ms' => [],
            'capabilities' => self::imageCapabilities(),
        ];

        // cPanel/Apache open_basedir kısıtı için güvenli geçici dizin seçimi
        $tmpCandidates = [
            ini_get('upload_tmp_dir') ?: '',          // .user.ini upload_tmp_dir
            ini_get('sys_temp_dir') ?: '',             // .user.ini sys_temp_dir
            rtrim(sys_get_temp_dir(), DIRECTORY_SEPARATOR), // PHP varsayılanı
        ];
        $baseTmp = '';
        foreach ($tmpCandidates as $candidate) {
            $candidate = trim($candidate);
            if ($candidate !== '' && (is_dir($candidate) || @mkdir($candidate, 0755, true)) && is_writable($candidate)) {
                $baseTmp = $candidate;
                break;
            }
        }
        if ($baseTmp === '') {
            self::$lastMeta['stage'] = 'temp_dir';
            self::$lastMeta['total_ms'] = self::elapsedMs($totalStarted);
            self::setLastError('Yazılabilir geçici dizin bulunamadı. upload_tmp_dir veya /tmp kontrol edilmeli.');
            return null;
        }

        $tmpDir = $baseTmp . DIRECTORY_SEPARATOR . 'fezadan_uploads_' . bin2hex(random_bytes(6));
        if (!@mkdir($tmpDir, 0700, true) && !is_dir($tmpDir)) {
            self::$lastMeta['stage'] = 'temp_dir';
            self::$lastMeta['total_ms'] = self::elapsedMs($totalStarted);
            self::setLastError('Geçici yükleme klasörü oluşturulamadı: ' . $tmpDir);
            return null;
        }

        $localStarted = microtime(true);
        $stored = self::saveImage($file, $tmpDir, $prefix, $maxBytes);
        self::$lastMeta['timings_ms']['local_reencode'] = self::elapsedMs($localStarted);
        if ($stored === null) {
            self::$lastMeta['stage'] = 'local_reencode';
            self::$lastMeta['total_ms'] = self::elapsedMs($totalStarted);
            self::setLastError('Görsel doğrulama veya yerel yeniden işleme başarısız oldu.');
            @rmdir($tmpDir);
            return null;
        }

        $sourcePath = $tmpDir . DIRECTORY_SEPARATOR . $stored;
        $folder = trim($folder, '/');
        self::$lastMeta['source_ext'] = strtolower((string)pathinfo($sourcePath, PATHINFO_EXTENSION));
        self::$lastMeta['source_bytes'] = is_file($sourcePath) ? (int)filesize($sourcePath) : 0;

        $seed = trim($slugBase) !== ''
            ? $slugBase
            : (string)pathinfo((string)($file['name'] ?? ''), PATHINFO_FILENAME);
        $webpName = self::buildWebpName($seed, $prefix);
        $webpUploadPath = $tmpDir . DIRECTORY_SEPARATOR . $webpName;

        $sourceExt = strtolower((string)pathinfo($sourcePath, PATHINFO_EXTENSION));
        $derivedWebpPath = preg_replace('/\.[^.]+$/', '.webp', $sourcePath);

        $webpStarted = microtime(true);
        $webpReady = false;
        if ($sourceExt === 'webp') {
            $webpReady = @copy($sourcePath, $webpUploadPath);
            self::$lastMeta['webp_method'] = 'copy_source';
        } elseif ($derivedWebpPath !== $sourcePath && is_file($derivedWebpPath)) {
            $webpReady = @copy($derivedWebpPath, $webpUploadPath);
            self::$lastMeta['webp_method'] = 'copy_derived';
        }

        if (!$webpReady) {
            $webpReady = self::convertToWebp($sourcePath, $webpUploadPath);
            self::$lastMeta['webp_method'] = 'convert';
        }
        self::$lastMeta['timings_ms']['webp'] = self::elapsedMs($webpStarted);
        self::$lastMeta['webp_bytes'] = is_file($webpUploadPath) ? (int)filesize($webpUploadPath) : 0;

        // WebP dönüştürme başarısızsa orijinal formatı kullan (Apache/cPanel GD kısıtı)
        if (!$webpReady || !is_file($webpUploadPath)) {
            error_log('Upload: WebP dönüştürme başarısız (imagewebp/Imagick yok?), orijinal format kullanılıyor: ' . $sourceExt);
            self::$lastMeta['webp_fallback'] = true;
            self::$lastMeta['webp_method'] = 'original_fallback';
            $webpUploadPath = $sourcePath;
            $uploadExt      = $sourceExt;
            $mimeMap        = [
                'jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg',
                'png' => 'image/png',  'gif'  => 'image/gif',
                'webp' => 'image/webp',
            ];
            $uploadMime     = $mimeMap[$uploadExt] ?? 'image/jpeg';
            // Nesne anahtarını uzantıya göre yeniden oluştur
            $webpName       = preg_replace('/\.webp$/', '.' . $uploadExt, $webpName);
        } else {
            $uploadMime = 'image/webp';
        }

        $objectKey = ($folder !== '' ? $folder . '/' : '') . $webpName;
        self::$lastMeta['object_key'] = $objectKey;

        try {
            require_once ROOT . '/app/Core/R2Storage.php';
            $r2 = \App\Core\R2Storage::instance();
            $r2Started = microtime(true);
            $uploaded = $r2->uploadFile($webpUploadPath, $objectKey, $uploadMime ?? 'image/webp');
            self::$lastMeta['timings_ms']['r2_upload'] = self::elapsedMs($r2Started);
            if (!$uploaded) {
                self::$lastMeta['stage'] = 'r2_upload';
                self::$lastMeta['total_ms'] = self::elapsedMs($totalStarted);
                self::setLastError('R2 uploadFile false döndü. Bucket, endpoint, erişim anahtarları veya yetkiler kontrol edilmeli.');
                self::cleanupTempUpload($tmpDir);
                return null;
            }

            self::cleanupTempUpload($tmpDir);
            self::$lastMeta['stage'] = 'done';
            self::$lastMeta['stored_path'] = '/' . $objectKey;
            self::$lastMeta['total_ms'] = self::elapsedMs($totalStarted);
            return '/' . $objectKey;
        } catch (\Throwable $e) {
            self::$lastMeta['stage'] = 'r2_exception';
            self::$lastMeta['total_ms'] = self::elapsedMs($totalStarted);
            self::setLastError('R2 yükleme istisnası: ' . $e->getMessage());
            error_log('R2 görsel yükleme hatası: ' . $e->getMessage());
            self::cleanupTempUpload($tmpDir);
            return null;
        }
    }

    private static function elapsedMs(float $started): int
    {
        return (int)round((microtime(true) - $started) * 1000);
    }

    private static function imageCapabilities(): array
    {
        return [
            'imagick' => class_exists('Imagick'),
            'gd' => function_exists('imagecreatefromstring'),
            'webp' => function_exists('imagewebp'),
            'jpeg' => function_exists('imagejpeg'),
            'png' => function_exists('imagepng'),
        ];
    }

    private static function buildWebpName(string $seed, string $fallbackPrefix): string
    {
        $base = self::slugify($seed);
        if ($base === '') {
            $fallback = trim($fallbackPrefix, "_ \t\n\r\0\x0B");
            $base = self::slugify($fallback);
        }
        if ($base === '') {
            $base = 'image';
        }

        try {
            $suffix = substr(bin2hex(random_bytes(4)), 0, 8);
        } catch (\Throwable $e) {
            $suffix = substr(sha1(uniqid('', true)), 0, 8);
        }

        return $base . '-' . $suffix . '.webp';
    }

    private static function slugify(string $value): string
    {
        $value = trim($value);
        if ($value === '') {
            return '';
        }

        $find = ['Ç', 'Ş', 'Ğ', 'Ü', 'İ', 'Ö', 'ç', 'ş', 'ğ', 'ü', 'ö', 'ı'];
        $replace = ['c', 's', 'g', 'u', 'i', 'o', 'c', 's', 'g', 'u', 'o', 'i'];
        $value = strtolower(str_replace($find, $replace, $value));
        $value = preg_replace('/[^a-z0-9]+/', '-', $value);
        $value = trim((string)$value, '-');

        return $value;
    }

    private static function convertToWebp(string $sourcePath, string $destPath, int $quality = 82): bool
    {
        if (!is_file($sourcePath)) {
            return false;
        }

        if (class_exists('Imagick')) {
            try {
                $iw = new \Imagick($sourcePath);
                $iw->setIteratorIndex(0);
                $iw->setImageFormat('webp');
                $iw->setImageCompressionQuality($quality);
                $iw->setOption('webp:method', '6');
                $iw->stripImage();
                $ok = $iw->writeImage($destPath);
                $iw->clear();
                $iw->destroy();
                return (bool)$ok;
            } catch (\Throwable $e) {
                // GD fallback below
            }
        }

        if (function_exists('imagecreatefromstring') && function_exists('imagewebp')) {
            $raw = @file_get_contents($sourcePath);
            if ($raw !== false) {
                $im = @imagecreatefromstring($raw);
                if ($im !== false) {
                    if (function_exists('imagepalettetotruecolor')) {
                        @imagepalettetotruecolor($im);
                    }
                    @imagesavealpha($im, true);
                    $ok = @imagewebp($im, $destPath, $quality);
                    @imagedestroy($im);
                    if ($ok) {
                        return true;
                    }
                }
            }
        }

        return false;
    }

    private static function cleanupTempUpload(string $tmpDir): void
    {
        $files = @glob($tmpDir . DIRECTORY_SEPARATOR . '*');
        if (is_array($files)) {
            foreach ($files as $filePath) {
                if (is_file($filePath)) {
                    @unlink($filePath);
                }
            }
        }
        @rmdir($tmpDir);
    }

    private static function localPublicFile(string $path): ?string
    {
        $path = trim($path);
        if ($path === '' || strpos($path, "\0") !== false) {
            return null;
        }

        $urlPath = preg_match('#^https?://#i', $path)
            ? (string)parse_url($path, PHP_URL_PATH)
            : (string)(parse_url($path, PHP_URL_PATH) ?: $path);

        if ($urlPath === '') {
            return null;
        }

        $rel = '/' . ltrim($urlPath, '/');
        $localRel = str_replace('/', DIRECTORY_SEPARATOR, $rel);
        $candidates = [];

        if (defined('ROOT')) {
            $root = rtrim(ROOT, '/\\');
            $candidates[] = $root . DIRECTORY_SEPARATOR . 'public_html' . $localRel;
            $candidates[] = $root . $localRel;
        }

        $docRoot = $_SERVER['DOCUMENT_ROOT'] ?? '';
        if ($docRoot !== '') {
            $candidates[] = rtrim($docRoot, '/\\') . $localRel;
        }

        foreach (array_unique($candidates) as $candidate) {
            if ($candidate !== '' && is_file($candidate)) {
                return $candidate;
            }
        }

        return null;
    }

    /**
     * Verilen `/uploads/foo.jpg` yoluna karşılık WebP varyantı varsa relative URL döner,
     * yoksa null. View'larda <picture> üretirken kullanılır.
     */
    public static function webpVariant(string $relativePath): ?string
    {
        $path = trim($relativePath);
        if ($path === '') {
            return null;
        }

        $urlPath = preg_match('#^https?://#i', $path)
            ? (string)parse_url($path, PHP_URL_PATH)
            : (string)(parse_url($path, PHP_URL_PATH) ?: $path);

        if ($urlPath === '') {
            return null;
        }

        $rel = '/' . ltrim($urlPath, '/');
        $webpRel = preg_replace('/\.(jpe?g|png)$/i', '.webp', $rel);
        if (!is_string($webpRel) || $webpRel === $rel) {
            return null;
        }

        return self::localPublicFile($webpRel) ? $webpRel : null;
    }

    public static function assetUrl(string $path): string
    {
        $path = trim($path);
        if ($path === '') return '';

        $cdnBase = defined('CDN_URL') && CDN_URL !== '' ? rtrim(CDN_URL, '/') : '';
        $siteBase = defined('SITE_URL') ? rtrim(SITE_URL, '/') : '';

        // Eğer mutlak URL ise
        if (preg_match('#^https?://#i', $path)) {
            $siteHost = (string)parse_url($siteBase, PHP_URL_HOST);
            $urlHost = (string)parse_url($path, PHP_URL_HOST);
            $urlPath = (string)parse_url($path, PHP_URL_PATH);

            if ($cdnBase !== '' && $siteBase !== '' && $cdnBase !== $siteBase
                && $siteHost !== '' && $urlHost !== ''
                && strcasecmp($siteHost, $urlHost) === 0
                && strpos('/' . ltrim($urlPath, '/'), '/uploads/') === 0) {
                return $cdnBase . '/' . ltrim($urlPath, '/');
            }

            if ($cdnBase !== '' && $siteBase !== '' && $cdnBase !== $siteBase) {
                $cdnHost = (string)parse_url($cdnBase, PHP_URL_HOST);

                if ($cdnHost !== '' && $urlHost !== '' && strcasecmp($cdnHost, $urlHost) === 0 && $urlPath !== '') {
                    $rel = '/' . ltrim($urlPath, '/');
                    if (strpos($rel, '/uploads/') === 0) {
                        return $cdnBase . $rel; // CDN önceliği
                    }
                    if (self::localPublicFile($rel)) {
                        return $siteBase . $rel;
                    }
                }
            }

            return $path;
        }

        if (strpos($path, '//') === 0) return 'https:' . $path;

        $rel = '/' . ltrim($path, '/');
        
        // Eğer relative path ve /uploads/ ise ve CDN varsa, CDN kullan
        if (strpos($rel, '/uploads/') === 0 && $cdnBase !== '') {
            return $cdnBase . $rel;
        }

        if ($cdnBase !== '' && $siteBase !== '' && $cdnBase !== $siteBase) {
            if (self::localPublicFile($rel)) {
                return $siteBase . $rel;
            }
        }

        $base = $cdnBase !== '' ? $cdnBase : $siteBase;
        return $base !== '' ? $base . $rel : $rel;
    }

    public static function publicUrlWarning(string $storedPath, string $publicUrl): string
    {
        $publicUrl = trim($publicUrl);
        if ($publicUrl === '') {
            return 'Public görsel URL boş üretildi.';
        }

        if (!preg_match('#^https?://#i', $publicUrl)) {
            return 'Public görsel URL mutlak değil: ' . $publicUrl;
        }

        $urlPath = '/' . ltrim((string)parse_url($publicUrl, PHP_URL_PATH), '/');
        if ($urlPath === '/') {
            return 'Public görsel URL path bilgisi taşımıyor.';
        }

        $cdnBase = defined('CDN_URL') && CDN_URL !== '' ? rtrim(CDN_URL, '/') : '';
        $siteBase = defined('SITE_URL') ? rtrim(SITE_URL, '/') : '';
        if ($cdnBase === '' || $cdnBase === $siteBase) {
            $rel = '/' . ltrim((string)(parse_url($storedPath, PHP_URL_PATH) ?: $storedPath), '/');
            if (strpos($rel, '/uploads/') === 0 && !self::localPublicFile($rel)) {
                return 'CDN_URL/R2_PUBLIC_URL site URL ile aynı veya boş. /uploads için R2 proxy fallback kullanılacak; kalıcı çözüm olarak public CDN domaini tanımlayın.';
            }
        }

        return '';
    }

    public static function webpUrl(string $relativePath): ?string
    {
        $webp = self::webpVariant($relativePath);
        if ($webp) return self::assetUrl($webp);

        $path = trim($relativePath);
        if (preg_match('#^https?://#i', $path)) {
            $urlPath = (string)parse_url($path, PHP_URL_PATH);
            if ($urlPath === '') {
                return null;
            }

            if (preg_match('/\.webp$/i', $urlPath)) {
                return self::assetUrl($path);
            }

            $webp = self::webpVariant($urlPath);
            return $webp ? self::assetUrl($webp) : null;
        }

        $urlPath = (string)(parse_url($path, PHP_URL_PATH) ?: $path);
        if (preg_match('/\.webp$/i', $urlPath)) {
            return self::assetUrl($path);
        }

        return null;
    }
}
