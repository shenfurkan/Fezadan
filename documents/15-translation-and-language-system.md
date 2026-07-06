# 15. Çeviri ve Çoklu Dil Sistemi

## Genel Mimari

Fezadan, Türkçe (TR) ve İngilizce (EN) olmak üzere iki dilli bir yayın platformudur. Dil sistemi üç katmanda çalışır:

| Katman | Ne Yönetir? | Dosya/Sistem |
|--------|------------|-------------|
| **Arayüz Çevirileri** | Butonlar, menüler, etiketler | `app/Translations/{lang}.php` |
| **İçerik Dili** | Makale/not dili, URL prefix'i | `articles.lang`, `notes.lang` |
| **Çapraz Dil Bağlantısı** | Makalenin diğer dildeki karşılığı | `articles.translation_of` |

---

## `__()` Fonksiyonu — Arayüz Çeviri Yardımcısı

[app/Config/config.php](file:///d:/Fezadan/app/Config/config.php) içinde tanımlı `__()` fonksiyonu, aktif dile göre çeviri metnini döndürür:

```php
function __(string $key, string $default = ''): string {
    // Aktif dil dosyasını yükle: app/Translations/{TR|EN}.php
    // Anahtar varsa çeviriyi döndür
    // Yoksa tr.php'ye (fallback) bak
    // O da yoksa $default veya anahtarın kendisini döndür
}
```

### Kullanım

```php
// Controller içinde:
$pageTitle = __('site.title');

// View içinde:
<h1><?= __('site.title') ?></h1>
<a href="/"><?= __('nav.home') ?></a>
```

### Çeviri Anahtarı Formatı

Nokta (` . `) ile ayrılmış hiyerarşik anahtarlar kullanılır:

```php
'site.title'       → Site ana başlığı
'nav.home'         → Menü "Anasayfa"
'nav.articles'     → Menü "Makaleler"
'article.read_more' → "Devamını Oku" butonu
'search.placeholder' → Arama kutusu placeholder
'pagination.prev'   → "Önceki" sayfa
'pagination.next'   → "Sonraki" sayfa
'notlar.download'   → Not indirme butonu
'errors.404'        → 404 hata mesajı
```

---

## Dil Dosyaları

### Konum
```
app/Translations/
├── tr.php    ← Türkçe (birincil dil, fallback)
└── en.php    ← İngilizce
```

### Format

Her dil dosyası, anahtar-değer çiftlerinden oluşan bir PHP dizisi döndürür:

```php
// tr.php
return [
    'site.title'       => 'Fezadan',
    'nav.home'         => 'Anasayfa',
    'nav.articles'     => 'Makaleler',
    'article.read_more' => 'Okumaya Devam Et',
    'search.placeholder' => 'Ara...',
    'pagination.prev'   => '← Önceki',
    'pagination.next'   => 'Sonraki →',
];
```

```php
// en.php
return [
    'site.title'       => 'Fezadan',
    'nav.home'         => 'Home',
    'nav.articles'     => 'Articles',
    'article.read_more' => 'Continue Reading',
    'search.placeholder' => 'Search...',
    'pagination.prev'   => '← Previous',
    'pagination.next'   => 'Next →',
];
```

### Fallback Mekanizması

1. Aktif dil dosyasında anahtar aranır.
2. Bulunamazsa **Türkçe (`tr.php`)** dosyasına düşülür.
3. Orada da yoksa `$default` parametresi veya anahtarın kendisi döndürülür.

> **Önemli:** Yeni bir anahtar eklendiğinde **mutlaka `tr.php` dosyasına da ekleyin**. `en.php`'de olmayan anahtarlar Türkçe fallback sayesinde hata vermez, ancak `tr.php`'de olmayan anahtarlar ham string olarak görünür.

---

## URL Yardımcı Fonksiyonları

### `langUrl(string $path): string`

Verilen yola aktif dil prefix'ini ekleyerek tam URL oluşturur:

```php
langUrl('/makaleler')   → 'https://fezadan.org/tr/makaleler'
langUrl('/yazar/furkan') → 'https://fezadan.org/tr/yazar/furkan'
```

### `articleUrl(string $author_slug, string $article_slug): string`

Makale için kısa (yazar/makale) formatta URL oluşturur:

```php
articleUrl('furkan', 'uzay-ve-feza')
→ 'https://fezadan.org/tr/furkan/uzay-ve-feza'
```

---

## URL'de Dil Değiştirme

Ziyaretçiler sayfanın üst kısmındaki dil değiştirme bağlantısı ile dil değiştirir. Bu bağlantı `App::getLang()` değerine göre diğer dilin prefix'ini kullanır:

```php
// View içinde:
$otherLang = (App::getLang() === 'TR') ? 'en' : 'tr';
$currentPath = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
// /tr/makaleler → /en/makaleler
$otherPath = '/' . $otherLang . substr($currentPath, 3);
```

---

## Makale Çeviri İlişkisi (`translation_of`)

`articles` tablosundaki `translation_of` sütunu, bir makalenin diğer dildeki karşılığını işaret eder. Bu ilişki **çift yönlüdür**:

```
TR Makale (id: 42, translation_of: 43)
    ↕
EN Makale (id: 43, translation_of: 42)
```

### Admin Panelinde Yönetim

Makale düzenleme sayfasında, diğer dildeki makaleler dropdown ile seçilerek `translation_of` bağlantısı kurulur. Bu sayede:

1. Ziyaretçiler makale sayfasında diller arasında geçiş yapabilir.
2. Site haritasında (`sitemap_main.xml`) otomatik `hreflang` etiketleri oluşturulur.
3. SEO için `<link rel="alternate" hreflang="..." />` başlıkları eklenir.

---

## Yeni Çeviri Anahtarı Ekleme

1. `app/Translations/tr.php` dosyasına yeni anahtar-değer çifti ekleyin:
   ```php
   'search.no_results' => 'Sonuç bulunamadı.',
   ```

2. `app/Translations/en.php` dosyasına İngilizce karşılığını ekleyin:
   ```php
   'search.no_results' => 'No results found.',
   ```

3. View/Controller'da kullanın:
   ```php
   echo __('search.no_results');
   // TR → "Sonuç bulunamadı."
   // EN → "No results found."
   ```

---

## Dil Tabanlı İçerik Filtreleme

Veritabanı sorgularında `lang` sütunu ile aktif dile göre filtreleme yapılır:

```php
$stmt = $pdo->prepare("SELECT * FROM articles WHERE lang = ? AND status = 'published'");
$stmt->execute([App::getLang()]);
```

Bu sayede `/tr/makaleler` sadece Türkçe, `/en/articles` sadece İngilizce makaleleri gösterir.

---

## SEO ve Dil

- Her dil için ayrı canonical URL: `/tr/...` ve `/en/...`
- Sitemap'te `xhtml:link rel="alternate" hreflang="tr/en"` ile çapraz referans
- Sayfa `<head>` içinde `<link rel="alternate" hreflang="..." href="...">` etiketleri
- `Accept-Language` başlığına göre kök URL'den (`/`) otomatik dil yönlendirmesi
