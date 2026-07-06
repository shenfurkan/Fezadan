<?php

namespace App\Services;

class SitemapService
{
    public static function generateSitemapInternal(): void
    {
        try {
            require_once ROOT . '/app/Controllers/SeoController.php';
            $seo = new \SeoController();
            $seo->regenerateAllCache();
        }
        catch (\Exception $e) {
            error_log('Sitemap üretim hatası: ' . $e->getMessage());
        }
    }

    public static function markSitemapDirty(): void
    {
        @touch(sys_get_temp_dir() . '/fezadan-sitemap.dirty');
        try {
            self::generateSitemapInternal();
        } catch (\Throwable $e) {
            error_log('Inline sitemap generation failed: ' . $e->getMessage());
        }
    }
}
