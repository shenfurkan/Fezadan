# 9. Cron ve Bakım

## Cron Nedir? (Temel Kavram)

**Cron job**, web sunucunuzda belirli zaman aralıklarında (dakikada bir, günde bir vb.) otomatik olarak çalışması planlanan arka plan betikleridir. unix/linux tabanlı sistemlerde otomatik bakım, veri yedekleme ve sistem zamanlamalarını yürütmek amacıyla kullanılır.

---

## 1. Planlı Makale Yayınlama — publish-scheduled.php

**Dosya Konumu:** [cron/publish-scheduled.php](file:///d:/Fezadan/cron/publish-scheduled.php)

### Ne İşe Yarar?
Admin panelinde yayına alınma tarihi ileri bir zaman olarak planlanan ve durumu `'scheduled'` yapılan makaleleri tarar. Zamanı gelen makalelerin durumunu otomatik olarak `'published'` (Yayında) yapar ve ziyaretçilerin erişimine açar.

### cPanel Cron Job Kurulumu
En doğru çalışma için bu betiğin **dakikada bir** tetiklenmesi önerilir:
```bash
*/5 * * * * /usr/local/bin/php /home/fezadano5/cron/publish-scheduled.php >/dev/null 2>&1
```

---

## 2. Site Haritası Üretimi — generate-sitemap.php

**Dosya Konumu:** [cron/generate-sitemap.php](file:///d:/Fezadan/cron/generate-sitemap.php)

### Hibrit Statik / Dinamik Mimari

Sitemap üretimi **iki katmanlı** bir hibrit mimaride çalışır:

```
[Cron Job — her 5 dk]          [Web Botu / Tarayıcı]
        │                               │
        ▼                               ▼
SeoController::regenerateAllCache()   Apache .htaccess
        │                               │
        ├─ public_html/sitemap.xml      ├─ Statik dosya var → Apache doğrudan sunar (hızlı)
        ├─ public_html/sitemap_main.xml └─ Yoksa → PHP fallback → SeoController dinamik üretir
        └─ public_html/sitemap_notes.xml
```

### Akıllı Dirty Flag Sistemi

Admin panelinde makale/not eklendiğinde, güncellendiğinde veya silindiğinde:
1. `/tmp/fezadan-sitemap.dirty` bayrağı oluşturulur.
2. `markSitemapDirty()` anında `SeoController::regenerateAllCache()` çağırarak statik dosyaları günceller.
3. Cron job her 5 dakikada bir bayrak varsa yeniden üretir (çift güvence).
4. `--force` parametresiyle bayrak durumundan bağımsız zorla üretim tetiklenebilir.

### SeoController — Merkezi Üretim Mantığı

Tüm sitemap ve robots.txt üretimi [app/Controllers/SeoController.php](file:///d:/Fezadan/app/Controllers/SeoController.php) dosyasında toplanmıştır:

| Metod | Görevi |
|-------|--------|
| `regenerateAllCache()` | 3 sitemap dosyasını DB'den sıfırdan üretir, `public_html/` ve `/tmp/` konumlarına atomik olarak yazar |
| `sitemapIndex()` | `sitemap.xml` (sitemapindex) dinamik fallback |
| `sitemapMain()` | `sitemap_main.xml` (makaleler + yazarlar + kategoriler + hreflang) dinamik fallback |
| `sitemapNotes()` | `sitemap_notes.xml` (notlar) dinamik fallback |
| `robots()` | `robots.txt` dinamik üretimi (host'a göre ayrışır) |
| `getCacheOrGenerate()` | DB `MAX(updated_at)` timestamp ile önbellekten okur, eskidiyse yeniden üretir |

### .htaccess Koşullu Sunumu

```apache
# Statik dosya varsa doğrudan sun, yoksa PHP üretsin
RewriteCond %{HTTP_HOST} ^notlar\. [NC]
RewriteCond %{DOCUMENT_ROOT}/sitemap_notes.xml -f
RewriteRule ^sitemap\.xml$ sitemap_notes.xml [L]

RewriteCond %{HTTP_HOST} !^notlar\. [NC]
RewriteCond %{DOCUMENT_ROOT}/sitemap.xml -f
RewriteRule ^sitemap\.xml$ sitemap.xml [L]
```

`-f` koşulu sayesinde statik dosya yokken Apache 404 dönmez; genel `index.php` fallback devreye girer.

### sitemap.xml Yönlendirmesi

- `notlar.fezadan.org/sitemap.xml` → `sitemap_notes.xml` (statik) veya `SeoController::sitemapNotes()` (fallback)
- `fezadan.org/sitemap.xml` → `sitemap.xml` sitemapindex (statik) veya `SeoController::sitemapIndex()` (fallback)

### cPanel Cron Job Kurulumu

```bash
*/5 * * * * /usr/local/bin/php /home/fezadano5/cron/generate-sitemap.php >/dev/null 2>&1
```

---

## 3. Veritabanı R2 Yedekleme Betiği — backup-db.php

**Dosya Konumu:** [cron/backup-db.php](file:///d:/Fezadan/cron/backup-db.php)

### Ne İşe Yarar?
Sunucu üzerindeki MySQL veritabanı şemasını ve içerisindeki tüm verileri sql formatında dışa aktarır (Dump), bant genişliğinden tasarruf sağlamak için gzip sıkıştırması uygular ve elde edilen `.sql.gz` paketini Cloudflare R2 depolama alanına yükler.

### 7 Günlük Saklama (Retention) Politikası
R2 depolama maliyetlerini optimize etmek ve eski yedeklerin birikmesini önlemek amacıyla, 7 günden eski yedek paketleri R2 API komutları yardımıyla otomatik olarak taranır ve depodan silinir. Sunucuda son 7 günün yedekleri güvenle saklanır.

### cPanel Cron Job Kurulumu
Sitenin en az aktif olduğu gece saatlerinde **günde bir kez** çalıştırılması önerilir (örneğin gece 03:00):
```bash
0 3 * * * /usr/local/bin/php /home/fezadano5/cron/backup-db.php >/dev/null 2>&1
```

---

## 4. Sistem Logları ve Otomatik Rotasyon

Sistemde yöneticiler tarafından yapılan işlemler JSON formatında `logs/admin.log` dosyasına yazılır.
- **Otomatik Arşivleme:** [app/Core/AdminLog.php](file:///d:/Fezadan/app/Core/AdminLog.php) sınıfı log dosyasının boyutunu denetler. Boyut **1 MB** sınırını aştığında, mevcut dosya otomatik olarak gzip ile sıkıştırılarak `.gz` biçiminde arşiv klasörüne taşınır ve temiz bir log dosyası oluşturularak kayıt işlemine devam edilir. Bu sayede logların disk alanını tüketmesi önlenir.
- **Güvenlik Logları:** Hatalı giriş denemeleri veya sistem uyarıları da `AdminLog` vasıtasıyla seviyelendirilerek (`INFO`, `WARNING`, `ERROR`) kaydedilir.
