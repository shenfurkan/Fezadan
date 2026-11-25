<?php

/** Small, public article-cover renditions. Original R2 objects are never changed. */
final class CoverImage
{
    public const WIDTHS = [480, 768, 1200];
    public const SIZES = '(min-width: 1920px) 434px, (min-width: 1024px) calc((100vw - 11.5rem) / 4), (min-width: 768px) calc((100vw - 6.5rem) / 2), calc(100vw - 2rem)';
    public const MAX_BYTES = 8388608;

    public static function sourceKey(string $source): ?string
    {
        $parts = parse_url(trim($source));
        if ($parts === false || isset($parts['query']) || isset($parts['fragment'])) return null;
        if (isset($parts['scheme']) && !in_array(strtolower($parts['scheme']), ['http', 'https'], true)) return null;
        if (isset($parts['host'])) {
            $hosts = [parse_url(SITE_URL, PHP_URL_HOST), parse_url(CDN_URL, PHP_URL_HOST)];
            if (!in_array(strtolower($parts['host']), array_map('strtolower', array_filter($hosts)), true)) return null;
        }
        $key = ltrim((string)($parts['path'] ?? ''), '/');
        if (strpos($key, '..') !== false || !preg_match('#^uploads/covers/[A-Za-z0-9_-][A-Za-z0-9._-]*\.(?:webp|jpe?g|png|gif)$#iD', $key)) return null;
        return $key;
    }

    public static function version(string $source): string
    {
        return substr(hash('sha256', 'cover-v1|' . $source), 0, 16);
    }

    public static function url(int $articleId, string $source, int $width = 480, string $format = 'webp'): string
    {
        if ($articleId < 1 || !in_array($width, self::WIDTHS, true) || !in_array($format, ['webp', 'jpg'], true) || self::sourceKey($source) === null) return '';
        return '/media/cover/' . $articleId . '/' . $width . '/' . self::version($source) . '.' . $format;
    }

    public static function srcset(int $articleId, string $source): string
    {
        if (self::url($articleId, $source) === '') return '';
        return implode(', ', array_map(static fn($width) => self::url($articleId, $source, $width) . ' ' . $width . 'w', self::WIDTHS));
    }

    public static function encode(string $bytes, int $width, string $format = 'webp'): string
    {
        if (strlen($bytes) > self::MAX_BYTES || !in_array($width, self::WIDTHS, true) || !in_array($format, ['webp', 'jpg'], true)) {
            throw new \InvalidArgumentException('Cover image limit exceeded.');
        }
        $info = @getimagesizefromstring($bytes);
        if (!$info || !in_array($info[2], [IMAGETYPE_JPEG, IMAGETYPE_PNG, IMAGETYPE_WEBP, IMAGETYPE_GIF], true)
            || $info[0] < 1 || $info[1] < 1 || $info[0] * $info[1] > 24000000) {
            throw new \InvalidArgumentException('Invalid cover image.');
        }
        // Cards are always 16:9. Cropping here avoids sending invisible portrait pixels.
        $height = (int)round($width * 9 / 16);
        if (class_exists(\Imagick::class) && \Imagick::queryFormats($format === 'jpg' ? 'JPEG' : 'WEBP')) {
            $image = new \Imagick();
            try {
                $image->pingImageBlob($bytes);
                if ($image->getNumberImages() !== 1) throw new \InvalidArgumentException('Animated covers retain their original file.');
                $image->clear();
                $image->readImageBlob($bytes);
                $image->setIteratorIndex(0);
                $image->cropThumbnailImage($width, $height);
                if ($format === 'jpg') {
                    $image->setImageBackgroundColor('white');
                    $image->setImageAlphaChannel(\Imagick::ALPHACHANNEL_REMOVE);
                }
                $image->setImageFormat($format === 'jpg' ? 'jpeg' : 'webp');
                $image->setImageCompressionQuality(76);
                if ($format === 'webp') $image->setOption('webp:method', '4');
                $image->stripImage();
                return $image->getImageBlob();
            } finally {
                $image->clear();
                $image->destroy();
            }
        }
        if (!function_exists('imagecreatefromstring') || !function_exists($format === 'jpg' ? 'imagejpeg' : 'imagewebp')) {
            throw new \RuntimeException('Cover image encoding is unavailable.');
        }
        $original = @imagecreatefromstring($bytes);
        if (!$original) throw new \RuntimeException('Cover image could not be decoded.');
        $small = imagecreatetruecolor($width, $height);
        if (!$small) {
            imagedestroy($original);
            throw new \RuntimeException('Cover image could not be resized.');
        }
        try {
            if ($format === 'jpg') {
                imagefill($small, 0, 0, imagecolorallocate($small, 255, 255, 255));
            } else {
                imagealphablending($small, false);
                imagesavealpha($small, true);
            }
            $scale = max($width / $info[0], $height / $info[1]);
            $cropWidth = (int)round($width / $scale);
            $cropHeight = (int)round($height / $scale);
            imagecopyresampled($small, $original, 0, 0, (int)(($info[0] - $cropWidth) / 2), (int)(($info[1] - $cropHeight) / 2), $width, $height, $cropWidth, $cropHeight);
            ob_start();
            $ok = $format === 'jpg' ? imagejpeg($small, null, 85) : imagewebp($small, null, 76);
            $output = (string)ob_get_clean();
            if (!$ok || $output === '') throw new \RuntimeException('Cover image could not be encoded.');
            return $output;
        } finally {
            imagedestroy($original);
            imagedestroy($small);
        }
    }
}
