# 17. SEO ve Arama Motoru Optimizasyonu

## Genel Bakış

Fezadan, arama motorlarında görünürlüğü artırmak için kapsamlı bir SEO altyapısına sahiptir. Tüm SEO işlevleri [app/Controllers/SeoController.php](file:///d:/Fezadan/app/Controllers/SeoController.php) dosyasında merkezi olarak yönetilir.

| SEO Özelliği | Yöneten | Açıklama |
|-------------|---------|----------|
| robots.txt | `SeoController::robots()` | Her istekte dinamik üretilir, host'a göre ayrışır |
| sitemap.xml | `SeoController` + cron | Hibrit statik/dinamik mimari |
| Meta etiketleri | Controller'lar | `seo_title`, `seo_description`, canonical URL |
| hreflang | Sitemap + `<head>` | Çok dilli sayfalarda çapraz referans |
| JSON-LD | View'lar | Yapılandırılmış veri (Structured Data) |
| OG etiketleri | `OgImage.php` + View'lar | Sosyal medya paylaşım görselleri |
| AI bot engelleme | `robots.txt` + `<meta>` | İçerik eğitimi koruması |

---

## robots.txt — Dinamik Üretim

### Statik Dosya Değil!

`robots.txt` artık statik bir dosya değil, her istekte `SeoController::robots()` tarafından dinamik olarak üretilir:

```php
public function robots() {
    // Host'a göre ana site mi notlar mı ayırt eder
    // Her iki durumda da farklı kurallar döndürür
}
```

### Ana Site (`fezadan.org/robots.txt`)
- Googlebot, Bingbot gibi arama motorlarına izin verilir
- AI botları (GPTBot, ChatGPT-User, Google-Extended, ClaudeBot, anthropic-ai, Omgilibot, FacebookBot) engellenir
- `Sitemap:` direktifi ile site haritası adresi belirtilir

### Notlar Subdomain (`notlar.fezadan.org/robots.txt`)
- Sadece not listesi ve RSS feed'e izin verilir
- Aynı AI bot engellemeleri uygulanır
- `Sitemap:` direktifi `sitemap_notes.xml`'i gösterir

### Yeni AI Bot Ekleme

Yeni bir AI botunu engellemek için `SeoController::robots()` metoduna şu formatla satır eklenir:

```php
$rules[] = "User-agent: YeniBotAdi";
$rules[] = "Disallow: /";
```

---

## Site Haritası (Sitemap) — Hibrit Mimari

### Üç Sitemap Dosyası

| Dosya | İçerik |
|-------|--------|
| `sitemap.xml` | Sitemap index (diğer iki haritayı referanslar) |
| `sitemap_main.xml` | Ana site: statik sayfalar, makaleler, yazarlar, kategoriler |
| `sitemap_notes.xml` | Notlar subdomain: not listesi ve detay sayfaları |

### Hibrit Statik / Dinamik Akış

```
[Admin paneli değişiklik] → markSitemapDirty()
    ↓
/tmp/fezadan-sitemap.dirty bayrağı oluşturulur
    ↓
SeoController::regenerateAllCache() → Statik XML dosyaları yeniden yazılır
    ↓
[Apache .htaccess]
    ├─ Statik dosya VAR → Apache doğrudan sunar (hızlı)
    └─ Statik dosya YOK → PHP fallback → SeoController dinamik üretir
```

### Dirty Flag Sistemi

Admin panelinde makale/not eklendiğinde, güncellendiğinde veya silindiğinde:
1. `sys_get_temp_dir() . '/fezadan-sitemap.dirty'` bayrağı oluşturulur.
2. `SeoController::regenerateAllCache()` anında çağrılarak statik dosyalar güncellenir.
3. Cron job her 5 dakikada bir bayrağı kontrol eder (çift güvence).

### hreflang Desteği

Çok dilli makaleler için `sitemap_main.xml` içinde `xhtml:link` etiketleri:

```xml
<url>
    <loc>https://fezadan.org/tr/furkan/uzay-ve-feza</loc>
    <xhtml:link rel="alternate" hreflang="tr" href="https://fezadan.org/tr/furkan/uzay-ve-feza" />
    <xhtml:link rel="alternate" hreflang="en" href="https://fezadan.org/en/furkan/space-and-feza" />
</url>
```

Bu sayede Google, sayfaların dil varyantlarını doğru şekilde indeksler.

---

## Meta Etiketleri ve Sayfa Başlıkları

### SEO Başlık ve Açıklama

Her makale için admin panelinden ayrı ayrı yapılandırılabilir:

```php
// articles tablosu:
seo_title        → <title> etiketi
seo_description  → <meta name="description">
```

View'larda kullanımı:

```html
<title><?= htmlspecialchars($article['seo_title'] ?? $article['title']) ?></title>
<meta name="description" content="<?= htmlspecialchars($article['seo_description'] ?? '') ?>">
```

### Canonical URL

Her sayfada kendi canonical adresi belirtilir:

```html
<link rel="canonical" href="https://fezadan.org/tr/furkan/uzay-ve-feza">
```

### hreflang Alternatifleri

Çeviri bağlantısı olan makalelerde:

```html
<link rel="alternate" hreflang="tr" href="https://fezadan.org/tr/furkan/uzay-ve-feza">
<link rel="alternate" hreflang="en" href="https://fezadan.org/en/furkan/space-and-feza">
```

### AI İçerik Koruma

Tüm sayfalarda `<meta name="robots" content="noai, noimageai">` etiketi bulunur. Bu, arama motorlarına ve AI botlarına içeriğin AI eğitimi için kullanılmaması gerektiğini bildirir.

---

## JSON-LD Yapılandırılmış Veri

Anasayfa ve makale sayfalarında, arama motorlarının içeriği daha iyi anlaması için JSON-LD formatında yapılandırılmış veri bulunur:

```html
<script type="application/ld+json" nonce="<?= CSP_NONCE ?>">
{
    "@context": "https://schema.org",
    "@type": "Article",
    "headline": "Makale Başlığı",
    "author": { "@type": "Person", "name": "Yazar Adı" },
    "datePublished": "2026-01-15",
    "url": "https://fezadan.org/tr/yazar/makale"
}
</script>
```

> **Dikkat:** JSON-LD script etiketleri de `nonce` özniteliği taşımalıdır.

---

## OpenGraph (Sosyal Medya) Etiketleri

### Otomatik OG Görsel Üretimi

[app/Core/OgImage.php](file:///d:/Fezadan/app/Core/OgImage.php) sınıfı, makale başlığı ve yazar bilgisini kullanarak **1200×630px** boyutunda otomatik OpenGraph banner'ı üretir:

- GD kütüphanesi ile PNG formatında
- SVG fallback (GD yoksa)
- Üretilen görsel R2'ye yüklenir, `articles.og_image` sütununa kaydedilir

### OG Meta Etiketleri

```html
<meta property="og:title" content="Makale Başlığı">
<meta property="og:description" content="Makale açıklaması">
<meta property="og:image" content="https://cdn.fezadan.org/uploads/og/makale.webp">
<meta property="og:url" content="https://fezadan.org/tr/yazar/makale">
<meta property="og:type" content="article">
<meta name="twitter:card" content="summary_large_image">
```

---

## SeoController Metodları

[app/Controllers/SeoController.php](file:///d:/Fezadan/app/Controllers/SeoController.php) tüm SEO üretimini yönetir:

| Metod | Görevi |
|-------|--------|
| `robots()` | Dinamik `robots.txt` üretimi (host'a göre ayrışır) |
| `sitemapIndex()` | `sitemap.xml` sitemapindex |
| `sitemapMain()` | `sitemap_main.xml` (makaleler + yazarlar + kategoriler + hreflang) |
| `sitemapNotes()` | `sitemap_notes.xml` (notlar) |
| `regenerateAllCache()` | 3 sitemap dosyasını sıfırdan üretir |
| `getCacheOrGenerate()` | Önbellekten okur, gerekirse yeniden üretir |
| `getMaxDbModificationTime()` | DB'deki en son değişiklik zamanını sorgular |
| `serveXml()` | XML yanıtını `Last-Modified` ve `304 Not Modified` başlıklarıyla gönderir |
| `writeAtomic()` | Dosyayı atomik olarak yazar (`tempnam` + `rename`) |

---

## Önbellek ve Performans

### Akıllı Önbellek

`getCacheOrGenerate()` metodu, sitemap önbelleğinin taze olup olmadığını kontrol eder:

```php
// Önbellek dosyasının değiştirilme zamanı ≥ DB'deki en son değişiklik zamanı ise
// → Önbellekten okur (hızlı)
// Değilse → Veritabanından sıfırdan üretir
```

### 304 Not Modified

`serveXml()` metodu, `If-Modified-Since` başlığını kontrol eder. İstemci önbelleği taze ise **304 Not Modified** yanıtı döner ve gereksiz veri transferini önler.

### Atomik Dosya Yazımı

`writeAtomic()` metodu, sitemap dosyalarını güvenli şekilde yazar:

```php
// 1. Geçici dosyaya yaz (tempnam)
// 2. rename() ile hedef dosyaya taşı (atomik işlem)
// 3. Başarısız olursa doğrudan file_put_contents (fallback)
```

Bu sayede yazma işlemi sırasında gelen bir istek yarım dosya okumaz.

---

## Cron Job Kurulumu

```bash
# Her 5 dakikada bir dirty flag kontrolü:
*/5 * * * * /usr/local/bin/php /home/fezadano5/cron/generate-sitemap.php >/dev/null 2>&1

# Manuel zorla üretim:
php cron/generate-sitemap.php --force
```

---

## Arama Motoru Araçları Entegrasyonu

### Google Search Console
- Manuel indeks talebi: `https://search.google.com/search-console`
- URL inceleme aracı ile yeni sayfaları indekse ekleme

### Bing Webmaster Tools
- Site Scan ile SEO sağlık kontrolü
- URL Inspection ile arama sonuçlarını güncelleme

Detaylı sorun giderme için [12-hata-cozum-ve-sorun-giderme.md](12-hata-cozum-ve-sorun-giderme.md) belgesine bakın (Bölüm 9: SEO Hataları ve Çözümleri).
