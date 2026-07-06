# 2. Mimari ve Klasör Yapısı

## MVC Mimarisi — Kod Nasıl Organize Edilmiş?

MVC (Model-View-Controller), yazılım dünyasında yaygın kullanılan bir mimari desendir. Bu desen, uygulamanın üç ana katmana ayrılmasını sağlar:

| MVC Katmanı | Sorumluluğu | Bu Projedeki Karşılığı |
|-------------|-------------|------------------------|
| **Controller** (Denetleyici) | Kullanıcıdan gelen isteği işler, Model'den veri çeker, View'a gönderir | [app/Controllers/](file:///d:/Fezadan/app/Controllers/) klasöründeki sınıflar |
| **View** (Görünüm) | Veriyi HTML formatında kullanıcıya sunar | [app/Views/](file:///d:/Fezadan/app/Views/) klasöründeki `.php` şablonları |
| **Model** (Veri) | Veritabanı işlemleri ve veri mantığı | Bu projede ayrı klasör yok, Controller'lar doğrudan `Db::pdo()` kullanır |

### Bu Projede MVC Nasıl Uygulanmış?

Klasik MVC uygulamalarından farklı olarak, bu projede **ayrı bir Model katmanı yoktur**. Bunun nedeni, projenin küçük ve kontrol edilebilir boyutta tutulması hedefidir. Büyük projelerde Model katmanı ayrı dosyalarda (örneğin `app/Models/Article.php`) tutulur, ancak bu ölçekte bu ekstra soyutlama katmanı gereksiz kod kalabalığı yaratır.

Bunun yerine, Controller'lar doğrudan `Db::pdo()` ile veritabanına bağlanır ve SQL sorgularını kendileri yönetir. Bu yaklaşım:
- Kod takibini kolaylaştırır (hangi Controller'ın hangi sorguyu çalıştırdığı bellidir)
- Ekstra dosya ve sınıf tanımlarını ortadan kaldırır
- Küçük-orta ölçekli projeler için yeterli soyutlamayı sağlar

### Controller'lar — İstek İşleyicileri

Her Controller, belirli bir işlevselliği yöneten PHP sınıfıdır. URL'deki ilk segment, hangi Controller'ın çağrılacağını belirler:

| Controller | Görevi | Hangi Sayfada? |
|------------|--------|----------------|
| `HomeController` | Anasayfa, son makaleler, kategoriler | `fezadan.org/tr` |
| `ArticleController` | Tek makale görünümü, yazar bazlı filtreleme, benzer yazılar widget'ı | `fezadan.org/tr/yazar/makale-adi` |
| `ArticlesController` | Makale listesi, kategori filtreleme, sayfalama | `fezadan.org/tr/makaleler` |
| `AuthorController` | Yazar profil sayfası | `fezadan.org/tr/yazar/slug` |
| `NotlarController` | PDF not yönetimi (alt site), indirme limitleme | `notlar.fezadan.org` |
| `AdminController` | Admin paneli (CRUD, sitemap, parola sıfırlama, OG görsel tetikleyici) | `fezadan.org/yonetim` |
| `SeoController` | Dinamik `robots.txt` ve sitemap (`sitemap.xml`, `sitemap_main.xml`, `sitemap_notes.xml`) üretimi — cron ve PHP fallback her ikisinde de çalışır | `fezadan.org/robots.txt`, `/sitemap*.xml` |
| `PageController` | Hakkında, manifesto, gizlilik, bağış ve doğrulama gibi statik front sayfalar | `fezadan.org/tr/hakkinda` |
| `RssController` | RSS feed (XML formatında, dil filtreli) | `fezadan.org/tr/rss` |
| `UploadsController` | CDN'den dosya sunma (yerel disk fallback kanalı) | `fezadan.org/uploads/...` |

**Routing Mekanizması:**
[app/Core/App.php](file:///d:/Fezadan/app/Core/App.php) dosyası, URL'den gelen ilk segmenti alır, `ucfirst()` ile ilk harfini büyütür ve sonuna `Controller.php` ekleyerek dosya yolunu oluşturur. Örneğin:
- `fezadan.org/tr/makale/...` → `ArticleController.php` çağrılır
- `fezadan.org/tr/hakkinda` → `PageController.php` çağrılır
- `fezadan.org/yonetim` → `AdminController.php` çağrılır

İkinci URL segmenti (varsa) Controller içindeki method adını belirler. Method bulunamazsa `index()` varsayılan olarak çağrılır.

### Base Controller (Temel Sınıf)

Tüm Controller'lar [app/Core/Controller.php](file:///d:/Fezadan/app/Core/Controller.php) sınıfından türetilir. Bu temel sınıf, ortak işlevsellikleri barındırır:

```php
class Controller {
    // View yükleme: ['title' => 'Başlık'] → $title = 'Başlık'
    public function view($view, $data = []) {
        extract($data, EXTR_SKIP);  // Diziyi değişkenlere dönüştürür
        require_once ROOT . '/app/Views/' . $view . '.php';
    }
    
    // "Merhaba Dünya" → "merhaba-dunya" (Türkçe karakter desteğiyle)
    public function createSlug($str) { ... }
    
    // Benzersiz slug: çakışma varsa "merhaba-dunya-2", "merhaba-dunya-3"...
    protected function uniqueSlug($pdo, $table, $base, $excludeId = null) { ... }
}
```

---

## Bir İsteğin Yolculuğu (Adım Adım)

Kullanıcı tarayıcıya `https://fezadan.org/tr/furkan/uzay-ve-feza` yazıp Enter'a bastığında arka planda gerçekleşen işlemler:

### Adım 1 — Apache Web Sunucusu ve URL Rewrite
İstek sunucuya ulaştığında, Apache web sunucusu [public_html/.htaccess](file:///d:/Fezadan/public_html/.htaccess) dosyasındaki kuralları uygular. En önemli kural gelen tüm istekleri `index.php`'ye yönlendirir ve URL'yi parametre olarak ekler:
```
Gelen URL:  /tr/furkan/uzay-ve-feza
İletilen:   index.php?url=tr/furkan/uzay-ve-feza
```

### Adım 2 — index.php (Giriş Noktası)
[public_html/index.php](file:///d:/Fezadan/public_html/index.php) sitenin giriş kapısıdır. Şu işlemleri gerçekleştirir:
1. **Oturum Güvenliği:** Cookie parametrelerini HTTPOnly, Secure ve SameSite=Lax olacak şekilde ayarlar.
2. **CSP Nonce Tanımlama:** Inline JS yürütme güvenliği için `CSP_NONCE` sabitini üretir.
3. **Güvenlik Başlıkları:** X-Frame-Options, X-Content-Type-Options vb. HTTP başlıklarını kaydeder.
4. **Proxy / Cloudflare Doğrulama:** Gelen IP adresinin Cloudflare sunucularından gelip gelmediğini CIDR aralıklarıyla doğrular.
5. **Autoloader:** Sınıfları otomatik yükler ve Composer bağımlılıklarını sisteme dahil eder.
6. **Router Başlatma:** `new App()` nesnesini çağırır.

### Adım 3 — App.php (Router)
[app/Core/App.php](file:///d:/Fezadan/app/Core/App.php) router sınıfıdır:
- `X-HTTP-Method-Override` başlığını denetleyerek method override saldırılarını engeller.
- URL'de `..`, `%2e` veya `%2f` gibi dizin atlatma (path traversal) parametreleri varsa isteği hemen sonlandırır.
- `/admin`, `/panel`, `/dashboard` gibi bot hedeflerini doğrudan 404 sayfasına yönlendirir.
- **`/robots.txt`** isteği doğrudan `SeoController::robots()`'a yönlendirilerek dinamik olarak üretilir.
- **`/sitemap*.xml`** isteğinde önce statik dosya varlığı kontrol edilir; varsa Apache sunar, yoksa `SeoController` dinamik üretip döner.
- Subdomain `notlar.` ise dil kurallarını atlayarak doğrudan [app/Controllers/NotlarController.php](file:///d:/Fezadan/app/Controllers/NotlarController.php) çağrısı yapar.
- Dili (`tr` veya `en`) tespit eder. Dil belirtilmemişse Türkçe'ye 301 yönlendirmesi yapar.

### Adım 4 — Controller ve View Render Süreci
Router tarafından belirlenen Controller (örneğin `ArticleController`) çalıştırılır. Controller veritabanı sorgularını yürütür, verileri işler ve `$this->view('front/read', $data)` yardımıyla View şablonunu render ederek kullanıcıya gönderir. Çıktıda yer alan inline betikler `nonce="<?= CSP_NONCE ?>"` korumasına sahiptir.

---

## Klasör Yapısı — Dosyalar Ne İşe Yarar?

### Kök Dizindeki Dosyalar

- [**`.env`**](file:///d:/Fezadan/.env) — GİZLİ: Veritabanı şifreleri, R2 API anahtarları vb. (Git'e eklenmez).
- [**`.env.example`**](file:///d:/Fezadan/.env.example) — `.env` dosyasının boş şablonudur.
- [**`.gitignore`**](file:///d:/Fezadan/.gitignore) — Git sisteminin takip etmeyeceği dosya listesi.
- [**`.cpanel.yml`**](file:///d:/Fezadan/.cpanel.yml) — cPanel Git deploy ayarları.
- [**`Dockerfile`**](file:///d:/Fezadan/Dockerfile) — PHP 8.2 + Apache + GD extension içeren geliştirme konteyneri.
- [**`docker-compose.yml`**](file:///d:/Fezadan/docker-compose.yml) — DB (MySQL 8), PHP-Apache ve phpMyAdmin servisleri.
- [**`composer.json`**](file:///d:/Fezadan/composer.json) — AWS SDK bağımlılık listesi.
- [**`package.json`**](file:///d:/Fezadan/package.json) — TailwindCSS v4 build scriptleri.

---

### app/ — Uygulama Kodları (Web'den Gizli)

Bu klasör web'e doğrudan kapalıdır. `.htaccess` ile korunur.

#### app/Config/
- [**`config.php`**](file:///d:/Fezadan/app/Config/config.php) — `.env` okuyucusunu barındırır. Güvenlik tuzlarını (`APP_SALT`) yönetir, dil switcher yardımcı fonksiyonlarını ve arayüz çeviri helper'ını (`__()`) tanımlar.

#### app/Translations/ (Arayüz Çevirileri)
- [**`tr.php`**](file:///d:/Fezadan/app/Translations/tr.php) — Türkçe kelime ve cümle karşılıkları.
- [**`en.php`**](file:///d:/Fezadan/app/Translations/en.php) — İngilizce kelime ve cümle karşılıkları.

#### app/Core/ (Sistem Çekirdeği)
- [**`App.php`**](file:///d:/Fezadan/app/Core/App.php) — Router ve güvenlik filtresi sınıfı.
- [**`Controller.php`**](file:///d:/Fezadan/app/Core/Controller.php) — Temel denetleyici sınıf.
- [**`Db.php`**](file:///d:/Fezadan/app/Core/Db.php) — Singleton PDO veritabanı bağlantısı ve kendini onaran (self-healing) migration motoru.
- [**`Csrf.php`**](file:///d:/Fezadan/app/Core/Csrf.php) — CSRF token üretimi ve doğrulama sınıfı.
- [**`Flash.php`**](file:///d:/Fezadan/app/Core/Flash.php) — Tek seferlik oturum mesajları helper'ı.
- [**`ErrorHandler.php`**](file:///d:/Fezadan/app/Core/ErrorHandler.php) — Hata yakalayıcı ve loglayıcı.
- [**`AdminLog.php`**](file:///d:/Fezadan/app/Core/AdminLog.php) — Admin panelindeki eylemleri JSON formatında loglama ve rotasyon aracı.
- [**`Upload.php`**](file:///d:/Fezadan/app/Core/Upload.php) — Görsel yeniden kodlama (Imagick/GD), WebP dönüşümü ve R2 pipeline yönetimi.
- [**`R2Storage.php`**](file:///d:/Fezadan/app/Core/R2Storage.php) — Cloudflare R2 dosya depolama, silme ve range-based PDF streaming aracı.
- [**`RateLimit.php`**](file:///d:/Fezadan/app/Core/RateLimit.php) — IP hash (günlük tuzlu) tabanlı indirme ve okuma sınırlama sınıfı.
- [**`OgImage.php`**](file:///d:/Fezadan/app/Core/OgImage.php) — GD/SVG tabanlı otomatik OpenGraph görsel üretici kütüphane.

#### app/Controllers/ (Uygulama Denetleyicileri)
- [**`SeoController.php`**](file:///d:/Fezadan/app/Controllers/SeoController.php) — Dinamik `robots.txt` ve sitemap (`sitemap.xml` / `sitemap_main.xml` / `sitemap_notes.xml`) üretimi. DB timestamp'e göre akıllı önbellekleme, `304 Not Modified` desteği ve atomik dosya yazımı içerir.

---

### public_html/ — Web'e Açık Klasör

Web sunucusunun kök dizini (document root) olarak ayarlanmalıdır.

- [**`index.php`**](file:///d:/Fezadan/public_html/index.php) — İsteklerin ilk giriş kapısı.
- [**`.htaccess`**](file:///d:/Fezadan/public_html/.htaccess) — URL rewrites, sitemap statik-dosya koşullu sunumu ve cache tanımları.
- [**`git-deploy.php`**](file:///d:/Fezadan/public_html/git-deploy.php) — Eski webhook endpoint'i. Production deploy cPanel Git ile yapılır; bu dosya 410 döndürür.
- [**`assets/js/mammoth.browser.min.js`**](file:///d:/Fezadan/public_html/assets/js/mammoth.browser.min.js) — Word dosya import işlemlerinde kullanılan yerel JS paketi (CSP uyumlu).
- **`robots.txt`** — ⚠️ Artık statik **değil**. `SeoController::robots()` tarafından dinamik üretilir. Host başlığına göre ana site / notlar subdomain kuralları otomatik ayrılır. Eski statik robots dosyaları kaldırılmıştır.
- **`sitemap.xml` / `sitemap_main.xml` / `sitemap_notes.xml`** — Cron job'lar her 5 dakikada bir yeniler (statik). Dosya yoksa `SeoController` dinamik olarak üretir (fallback).

---

### cron/ — Zamanlanmış Otomatik Görevler

- [**`generate-sitemap.php`**](file:///d:/Fezadan/cron/generate-sitemap.php) — Dirty flag varlığında sitemap oluşturan betik.
- [**`publish-scheduled.php`**](file:///d:/Fezadan/cron/publish-scheduled.php) — Zamanı gelen makaleleri yayınlayan cron betiği.
- [**`backup-db.php`**](file:///d:/Fezadan/cron/backup-db.php) — Veritabanı yedeğini gzip sıkıştırmasıyla R2 depolamasına yedekleyen ve 7 günden eski yedekleri silen cron.
