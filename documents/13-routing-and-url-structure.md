# 13. Routing ve URL Yapısı

## Routing Nedir? (Temel Kavram)

**Routing** (yönlendirme), gelen HTTP isteğinin URL'sine bakarak hangi Controller'ın hangi metodunun çalıştırılacağını belirleyen sistemdir. Fezadan'da tüm routing işlemleri [app/Core/App.php](file:///d:/Fezadan/app/Core/App.php) tarafından yönetilir.

---

## İsteğin Adım Adım İşlenmesi

### Adım 1 — Apache mod_rewrite (URL → index.php)

Apache, [public_html/.htaccess](file:///d:/Fezadan/public_html/.htaccess) içindeki `RewriteRule` ile **dosya veya klasör olarak var olmayan tüm istekleri** `index.php?url=...` biçimine dönüştürür:

| Tarayıcı URL'si | Apache'nin İlettiği |
|-----------------|---------------------|
| `/tr/makaleler` | `index.php?url=tr/makaleler` |
| `/tr/furkan/uzay-ve-feza` | `index.php?url=tr/furkan/uzay-ve-feza` |
| `/yonetim/login` | `index.php?url=yonetim/login` |

> **Not:** `/yonetim`, `/uploads`, `/cdn`, `/assets` yolları dil prefix'i (`/tr`, `/en`) zorunluluğundan **muaftır**. Bu yollar doğrudan çalışır.

---

### Adım 2 — App.php (Router) — Güvenlik Filtreleri

Router çalışmaya başlamadan önce şu kontrolleri yapar:

1. **Method Override Engeli:** `X-HTTP-Method-Override`, `X-HTTP-Method`, `X-Method-Override` başlıkları reddedilir → **400 Bad Request**.
2. **Path Traversal Koruması:** URL'de `..`, `%2e%2e`, `%2e.`, `.%2e`, `%2f` geçiyorsa → **400 Bad Request**.
3. **Çift Slaş ve Trailing Slash Temizliği:** `//` → `/`, sondaki `/` silinir. Gerekirse **301 Redirect** ile canonical URL'ye yönlendirilir.
4. **Admin Yolu Engelleme:** `/admin`, `/panel`, `/dashboard` ve alt yolları → **404 Not Found**.
5. **Özel Statik Rotalar:** `robots.txt` ve `sitemap*.xml` istekleri `SeoController`'a yönlendirilir.

---

### Adım 3 — Dil Prefix Tespiti (`/tr` veya `/en`)

Router, URL'nin ilk segmentini okur:

```php
if ($url[0] === 'en') → dil = EN, segment silinir
if ($url[0] === 'tr') → dil = TR, segment silinir
```

| Gelen URL | Dil | Controller'a Giden |
|-----------|-----|--------------------|
| `/tr/makaleler` | TR | `['makaleler']` |
| `/en/articles` | EN | `['articles']` |
| `/tr` | TR | `['home']` (boşsa `home` atanır) |

Dil prefix'i yoksa ve host `notlar.` ile başlamıyorsa:
- Kök `/` → `Accept-Language` başlığına göre **307 Temporary Redirect** ile `/tr` veya `/en`'e yönlendirilir.
- Diğer sayfalar → **301 Permanent Redirect** ile `/tr/...` ön ekine yönlendirilir.

---

### Adım 4 — Subdomain Routing (`notlar.*`)

Host `notlar.` ile başlıyorsa, dil mantığı tamamen atlanır ve doğrudan [app/Controllers/NotlarController.php](file:///d:/Fezadan/app/Controllers/NotlarController.php) çağrılır. Sadece `GET` ve `HEAD` isteklerine izin verilir. Detaylar için [16. Notlar Subdomain](16-notlar-subdomain.md) belgesine bakın.

---

### Adım 5 — Controller Çözümleme

URL'nin ilk segmenti alınır, ilk harfi büyütülür ve sonuna `Controller.php` eklenir:

| URL Segmenti | Çözümlenen Controller |
|-------------|----------------------|
| `makaleler` | `ArticlesController` |
| `hakkinda` | `PageController` |
| `yazar` | `AuthorController` |
| `yonetim` | `AdminController` |

Eğer dosya varsa Controller yüklenir, ilk segment URL'den silinir.

**Controller bulunamazsa**, segment bir yazar slug'ı olarak değerlendirilir (`authors` tablosunda `slug` kontrolü):
- **Eşleşme var, ikinci segment yok:** → `301 Redirect` → `/tr/yazar/{slug}`
- **Eşleşme var, ikinci segment var:** → `ArticleController::index($articleSlug, $authorSlug)`
- **Eşleşme yok:** → `404 Not Found`

---

### Adım 6 — Method Çözümleme

URL'nin ikinci segmenti (varsa) Controller'daki method'u belirler:

```
Öncelik 1: Birebir eşleşme → method_exists($controller, $url[1])
Öncelik 2: kebab-case → camelCase dönüşümü
```

| URL Segmenti | Aranan Method |
|-------------|---------------|
| `login` | `login()` (birebir) |
| `forgot-password` | `forgotPassword()` (camelCase) |
| `send-reset-link` | `sendResetLink()` (camelCase) |

Method bulunamazsa `index()` varsayılan olarak çağrılır. Kalan segmentler parametre olarak geçilir.

---

## Tam URL Desenleri Tablosu

### Ana Site (`fezadan.org`)

| URL Deseni | Controller | Method | Parametreler |
|-----------|-----------|--------|-------------|
| `/tr` veya `/en` | `HomeController` | `index()` | — |
| `/tr/makaleler` | `ArticlesController` | `index()` | — |
| `/tr/makale/{slug}` | `ArticleController` | `index($slug, null)` | `slug` |
| `/tr/yazar/{slug}` | `AuthorController` | `index($slug)` | `slug` |
| `/tr/{yazar}` | (301 → `/tr/yazar/{yazar}`) | — | — |
| `/tr/{yazar}/{makale}` | `ArticleController` | `index($makale, $yazar)` | `makale`, `yazar` |
| `/tr/hakkinda` | `PageController` | `renderPage()` | — |
| `/tr/manifesto` | `PageController` | `renderPage()` | — |
| `/tr/privacy` | `PageController` | `privacy()` | — |
| `/tr/search` | `SearchController` | `index()` | — |
| `/tr/rss` | `RssController` | `index()` | — |
| `/tr/teyit` | `PageController` | `renderPage()` | — |
| `/yonetim` | `AdminController` | `index()` | — |
| `/yonetim/login` | `AdminController` | `login()` | — |
| `/yonetim/create` | `AdminController` | `create()` | — |
| `/yonetim/edit/{id}` | `AdminController` | `edit($id)` | `id` |
| `/yonetim/forgot-password` | `AdminController` | `forgotPassword()` | — |
| `/robots.txt` | `SeoController` | `robots()` | — |
| `/sitemap.xml` | `SeoController` | `sitemapIndex()` | — |

### Subdomain (`notlar.fezadan.org`)

| URL Deseni | Method | Açıklama |
|-----------|--------|----------|
| `/` | `index()` | Not listesi (sayfalı, filtrelenebilir) |
| `/not/{slug}` | `read($slug)` | Not detay sayfası |
| `/not/view/{slug}` | `viewPdf($slug)` | PDF tarayıcıda görüntüleme (inline stream) |
| `/not/download/{slug}` | `download($slug)` | PDF indirme (rate limit: 3/dk/IP) |
| `/rss` | `rss()` | RSS 2.0 feed (son 30 not) |

---

## `parseUrl()` — URL Ayrıştırma

```php
public function parseUrl() {
    if (isset($_GET['url'])) {
        return explode('/', filter_var(rtrim($_GET['url'], '/'), FILTER_SANITIZE_URL));
    }
    return ['home'];  // Kök dizin → home
}
```

- `?url=` parametresi `.htaccess` tarafından sağlanır
- `FILTER_SANITIZE_URL` ile tüm geçersiz karakterler temizlenir
- Sondaki `/` temizlenir
- Boş URL → `['home']` dizisi (HomeController'ı tetikler)

---

## Canonical URL ve Yönlendirme Zinciri

```
Tarayıcı → Apache (.htaccess RewriteRule)
         → index.php?url=...
         → App::__construct()
            ├─ Path traversal kontrolü
            ├─ Çift slash / trailing slash canonical → 301
            ├─ Admin yolu engelleme → 404
            ├─ robots.txt / sitemap → SeoController
            ├─ Dil tespiti (/tr veya /en)
            ├─ Dil prefix yoksa → 301/307 redirect
            ├─ notlar.* ise → NotlarController
            ├─ Controller çözümleme
            │   ├─ Dosya varsa → Controller yükle
            │   └─ Yoksa → yazar slug kontrolü
            │       ├─ Yazar var → 301 veya ArticleController
            │       └─ Yazar yok → 404
            └─ Method çözümleme + parametreler → View render
```

---

## Önemli Kurallar ve Gotcha'lar

### Dil Prefix'i Olmayan Rotalar
Şu yollar dil prefix'i (`/tr`, `/en`) olmadan çalışır:

```
/yonetim, /yonetim/*
/uploads, /uploads/*
/cdn, /cdn/*
/assets, /assets/*
/scripts, /scripts/*
/admin, /panel, /dashboard  (→ 404)
/robot, /robot/*
```

### Engellenen Admin Yolları
```php
/admin, /admin/*   → 404
/panel, /panel/*   → 404
/dashboard, /dashboard/* → 404
```
Bot taramalarını yanıltmak için sahte 404 döndürülür.

### Path Traversal Koruması
URL'de şu desenlerden herhangi biri varsa istek anında reddedilir:
- `..` (dizin atlatma)
- `%2e%2e`, `%2e.`, `.%2e` (encoded dizin atlatma)
- `%2f` (encoded slash)
- Çözülmüş segment `.` veya `..` ise

### Method Override Engeli
HTTP başlığı ile method değiştirme (`X-HTTP-Method-Override` vb.) tamamen engellenmiştir.
