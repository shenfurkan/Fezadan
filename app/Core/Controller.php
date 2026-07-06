<?php

/**
 * View render ve slug yardımcılarını sağlayan temel controller.
 *
 * Tüm uygulama controller'ları bu sınıfı genişleterek şunlara erişir:
 * - view()         — app/Views/ altındaki PHP şablonunu render eder
 * - createSlug()   — Türkçe karakter güvenli URL slug'ı üretir
 * - uniqueSlug()   — veritabanı benzersizlik kontrollü slug üretir
 */
class Controller {
    public function view($view, $data = []) {
        if (file_exists(ROOT . '/app/Views/' . $view . '.php')) {
            extract($data, EXTR_SKIP);
            require_once ROOT . '/app/Views/' . $view . '.php';
        } else {
            throw new \RuntimeException("View dosyası bulunamadı: " . $view);
        }
    }
    
    // Slug fonksiyonu
    public function createSlug($str) {
        $find = array('Ç', 'Ş', 'Ğ', 'Ü', 'İ', 'Ö', 'ç', 'ş', 'ğ', 'ü', 'ö', 'ı');
        $replace = array('c', 's', 'g', 'u', 'i', 'o', 'c', 's', 'g', 'u', 'o', 'i');
        $str = strtolower(str_replace($find, $replace, $str));
        $str = preg_replace('/[^a-z0-9\s-]/', '', $str);
        $str = preg_replace('/[\s-]+/', ' ', $str);
        $str = trim($str);
        $str = str_replace(' ', '-', $str);
        if ($str === '') {
            // Türkçe karakterler tamamen filtrelendiyse yedek: rastgele kısa anahtar
            $str = 'icerik-' . substr(bin2hex(random_bytes(3)), 0, 6);
        }
        return $str;
    }

    /**
     * Verilen tabloda slug benzersizliğini garantiler.
     * Çakışma varsa "-2", "-3"... ekleyerek uygun bir slug döndürür.
     *
     * @param \PDO     $pdo
     * @param string   $table       Whitelist'lenmiş tablo adı (articles|authors|notes|categories|patch_notes)
     * @param string   $base        createSlug() çıktısı
     * @param int|null $excludeId   Update senaryosunda kendi id'sini hariç tutmak için
     * @param string   $column      Slug kolon adı (varsayılan 'slug')
     */
    protected function uniqueSlug(\PDO $pdo, string $table, string $base, ?int $excludeId = null, string $column = 'slug'): string
    {
        // SQL injection'a karşı tablo/kolon adlarını whitelist
        $allowedTables = ['articles', 'authors', 'notes', 'categories', 'patch_notes'];
        if (!in_array($table, $allowedTables, true)) {
            throw new \InvalidArgumentException("uniqueSlug: izin verilmeyen tablo '$table'");
        }
        if (!preg_match('/^[a-z_]+$/i', $column)) {
            throw new \InvalidArgumentException("uniqueSlug: geçersiz kolon adı");
        }

        if ($base === '') {
            $base = 'icerik';
        }

        $slug   = $base;
        $suffix = 2;

        $sql = "SELECT 1 FROM `$table` WHERE `$column` = ?"
             . ($excludeId !== null ? " AND id <> ?" : "")
             . " LIMIT 1";

        while (true) {
            $stmt = $pdo->prepare($sql);
            $params = [$slug];
            if ($excludeId !== null) $params[] = $excludeId;
            $stmt->execute($params);
            if (!$stmt->fetchColumn()) {
                return $slug;
            }
            $slug = $base . '-' . $suffix++;
            // Aşırı çakışmada sonsuz döngüyü kır
            if ($suffix > 1000) {
                return $base . '-' . substr(bin2hex(random_bytes(3)), 0, 6);
            }
        }
    }

    // --- RSS / XML yardımcı metodları ---

    protected function xmlEscape(string $s): string {
        return htmlspecialchars($s, ENT_XML1 | ENT_QUOTES, 'UTF-8');
    }

    protected function cdata(string $s): string {
        $safe = str_replace(']]>', ']]]]><![CDATA[>', $s);
        return '<![CDATA[' . $safe . ']]>';
    }

    protected function absoluteUrl(string $url, string $siteBase): string {
        $url = trim($url);
        if ($url === '') return '';
        if (preg_match('#^https?://#i', $url)) return $url;
        if (strpos($url, '//') === 0) return 'https:' . $url;
        if ($url[0] !== '/') $url = '/' . $url;
        return $siteBase . $url;
    }

    protected function guessMime(string $url): string {
        $ext = strtolower(pathinfo(parse_url($url, PHP_URL_PATH) ?? '', PATHINFO_EXTENSION));
        switch ($ext) {
            case 'jpg':
            case 'jpeg': return 'image/jpeg';
            case 'png':  return 'image/png';
            case 'gif':  return 'image/gif';
            case 'webp': return 'image/webp';
            case 'avif': return 'image/avif';
            case 'svg':  return 'image/svg+xml';
            default:     return 'image/jpeg';
        }
    }
}