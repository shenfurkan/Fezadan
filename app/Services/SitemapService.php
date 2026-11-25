<?php

namespace App\Services;

class SitemapService
{
    public static function generateSitemapInternal(): void
    {
        require_once ROOT . '/app/Controllers/SeoController.php';
        $seo = new \SeoController();
        $seo->regenerateAllCache();
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
