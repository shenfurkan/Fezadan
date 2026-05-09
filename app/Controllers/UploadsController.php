<?php

class UploadsController extends Controller
{
    public function index(...$parts): void
    {
        $parts = array_values(array_filter(array_map('strval', $parts), static function ($part) {
            return $part !== '' && strpos($part, "\0") === false;
        }));

        $objectKey = 'uploads/' . implode('/', $parts);
        if (empty($parts) || strpos($objectKey, '..') !== false) {
            http_response_code(404);
            echo 'Dosya bulunamadı.';
            return;
        }

        try {
            require_once ROOT . '/app/Core/R2Storage.php';
            $r2 = \App\Core\R2Storage::instance();
            $r2->streamPublicFile($objectKey);
        } catch (\Throwable $e) {
            error_log('UploadsController R2 fallback hatası: ' . $e->getMessage());
            http_response_code(404);
            echo 'Dosya bulunamadı.';
        }
    }
}
