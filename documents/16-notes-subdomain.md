# 16. Notlar Subdomain — Tam Rehber

## Genel Bakış

`notlar.fezadan.org`, Fezadan projesinin PDF doküman paylaşımı için ayrılmış alt alan adıdır. Ana site (`fezadan.org`) ile **aynı kod tabanını** kullanır ancak tamamen farklı bir Controller (`NotlarController`) tarafından yönetilir.

| Özellik | Ana Site | Notlar Subdomain |
|---------|---------|-----------------|
| Adres | `fezadan.org` | `notlar.fezadan.org` |
| Controller | Çeşitli (Home, Makale, vb.) | Sadece `NotlarController` |
| Dil Prefix'i | Zorunlu (`/tr`, `/en`) | Yok |
| HTTP Metodları | GET, POST | Sadece GET, HEAD |
| İçerik Türü | HTML (makaleler) | PDF (dokümanlar) |
| Kimlik Doğrulama | Yok | Yok (herkese açık) |
| Rate Limiti | Yok | 3 indirme/dk/IP |

---

## Routing (Nasıl Çalışır?)

[app/Core/App.php](file:///d:/Fezadan/app/Core/App.php) router'ı, `$_SERVER['HTTP_HOST']` değeri `notlar.` ile başladığında şu akışı izler:

```php
if (strpos($host, 'notlar.') === 0) {
    // Sadece GET/HEAD izin ver
    if ($requestMethod !== 'GET' && $requestMethod !== 'HEAD') {
        → 405 Method Not Allowed
    }

    // NotlarController'ı yükle
    $controller = new NotlarController();

    // URL'ye göre method belirle:
    if ($url[0] === 'not') {
        if ($url[1] === 'download') → download($url[2])
        elseif ($url[1] === 'view') → viewPdf($url[2])
        else → read($url[1])
    } else {
        → index()
    }
}
```

---

## URL Desenleri ve Controller Metodları

| URL | Method | Açıklama |
|-----|--------|----------|
| `/` | `index()` | Not listesi (sayfalı, aranabilir, filtrelenebilir) |
| `/rss` | `rss()` | RSS 2.0 feed (son 30 not) |
| `/not/{slug}` | `read($slug)` | Not detay sayfası |
| `/not/view/{slug}` | `viewPdf($slug)` | PDF tarayıcıda inline görüntüleme |
| `/not/download/{slug}` | `download($slug)` | PDF zorunlu indirme |

**İstek örnekleri:**
```
notlar.fezadan.org/                          → index()
notlar.fezadan.org/rss                       → rss()
notlar.fezadan.org/not/kuantum-fizigi        → read('kuantum-fizigi')
notlar.fezadan.org/not/view/kuantum-fizigi   → viewPdf('kuantum-fizigi')
notlar.fezadan.org/not/download/kuantum-fizigi → download('kuantum-fizigi')
```

---

## Controller Metodları Detaylı

### `index()` — Not Listesi

[app/Controllers/NotlarController.php:index()](file:///d:/Fezadan/app/Controllers/NotlarController.php) şu GET parametrelerini destekler:

| Parametre | Açıklama |
|-----------|----------|
| `?search=` | FULLTEXT arama (`title`, `description` sütunlarında) |
| `?cat=` | Kategori filtresi (`note_categories` join) |
| `?lang=TR` veya `?lang=EN` | Dil filtresi |
| `?page=2` | Sayfalama (12 not/sayfa) |

Görünüm: [app/Views/front/notlar_home.php](file:///d:/Fezadan/app/Views/front/notlar_home.php)

---

### `read($slug)` — Not Detay Sayfası

- Slug ile `notes` tablosundan not bilgilerini çeker.
- PDF görüntüleyici embed linki oluşturur: `pdfUrl = /not/view/{slug}`
- Kategori isimlerini `GROUP_CONCAT` ile birleştirir.
- Bulunamazsa özel 404 görünümü: [app/Views/errors/404_note.php](file:///d:/Fezadan/app/Views/errors/404_note.php)

---

### `viewPdf($slug)` — PDF Görüntüleme (Inline Stream)

- Slug ile `title` ve `r2_path` bilgilerini DB'den okur.
- [app/Core/R2Storage.php:streamView()](file:///d:/Fezadan/app/Core/R2Storage.php) ile PDF'i **HTTP Range desteğiyle** tarayıcıya stream eder.
- `Content-Type: application/pdf` ve `Content-Disposition: inline` başlıkları ile sunulur.
- Bulunamazsa düz metin 404 döner.

---

### `download($slug)` — PDF İndirme (Rate Limit'li)

- **Rate Limit:** [app/Core/RateLimit.php](file:///d:/Fezadan/app/Core/RateLimit.php) ile **dakikada 3 indirme** sınırı. `FOR UPDATE` row lock ile race condition önlenir.
- Limit aşılırsa → `/not/{slug}?error=rate_limit` adresine yönlendirilir.
- Başarılı indirmede `downloads` sayacı artırılır.
- `Content-Disposition: attachment` ile zorunlu indirme olarak sunulur.

---

### `rss()` — RSS 2.0 Feed

- Son 30 notu RSS 2.0 formatında döner.
- `If-Modified-Since` başlığı ile `304 Not Modified` desteği.
- `NOTES_SITE_URL` env değişkeni veya fallback `https://notlar.fezadan.org` kullanılır.
- Doğrudan çıktı verir (view dosyası kullanmaz).

---

## Veritabanı

### notes Tablosu

| Sütun | Tür | Açıklama |
|-------|-----|----------|
| `id` | INT (PK) | Benzersiz not ID |
| `title` | VARCHAR | Not başlığı |
| `slug` | VARCHAR | URL kısa adı (benzersiz) |
| `description` | TEXT | Not açıklaması |
| `r2_path` | VARCHAR | Cloudflare R2'deki PDF yolu (`notlar/dosya.pdf`) |
| `file_size` | BIGINT | Dosya boyutu (byte) |
| `downloads` | INT | Toplam indirilme sayısı |
| `lang` | VARCHAR | Dil kodu (TR veya EN) |
| `created_at` | DATETIME | Oluşturulma tarihi |
| `updated_at` | DATETIME | Güncellenme tarihi |

### FULLTEXT İndeksi

```sql
FULLTEXT INDEX ON notes(title, description)
```

Arama sorguları `MATCH ... AGAINST` ile yapılır.

---

## PDF Depolama ve Sunum

### R2Storage Sınıfı

[app/Core/R2Storage.php](file:///d:/Fezadan/app/Core/R2Storage.php) PDF dosyalarını Cloudflare R2'de saklar ve sunar.

#### `streamView($r2Path)` — Inline Görüntüleme
- `Content-Type: application/pdf`
- `Content-Disposition: inline; filename="belge.pdf"`
- HTTP Range başlıklarını destekler: `Range: bytes=0-1048575`
- Kısmi içerik (206 Partial Content) yanıtı verir
- Büyük PDF'lerde sayfa sayfa yükleme sağlar

#### `streamDownload($r2Path)` — Zorunlu İndirme
- `Content-Disposition: attachment; filename="belge.pdf"`
- Tam dosya boyutu başlığı (`Content-Length`)

#### `deleteFile($r2Path)` — R2'den Silme
- Admin panelinde not güncellenirken eski PDF'in silinmesi için kullanılır.

---

## Rate Limiting Sistemi

[app/Core/RateLimit.php](file:///d:/Fezadan/app/Core/RateLimit.php) sınıfı, `download_rate_limits` tablosunu kullanarak indirme sınırlaması yapar:

```
Dakikada maksimum 3 indirme / IP
```

### IP Gizliliği (KVKK/GDPR Uyumlu)

IP adresleri açık metin olarak saklanmaz. Her gün değişen tuz ile hash'lenir:

```php
$ipHash = hash('sha256', $ip . date('Y-m-d') . APP_SALT);
```

Bu sayede aynı IP her gün farklı bir hash alır ve geri izlenemez.

### `FOR UPDATE` Row Lock

Eşzamanlı isteklerde limit aşımını önlemek için `SELECT ... FOR UPDATE` kullanılır. Bu, aynı anda gelen iki isteğin ikisinin de limiti aşmasını engeller.

---

## PDF.js — Tarayıcı İçi Görüntüleyici

[public_html/assets/js/pdf.mjs](file:///d:/Fezadan/public_html/assets/js/pdf.mjs) ve [pdf.worker.mjs](file:///d:/Fezadan/public_html/assets/js/pdf.worker.mjs) dosyaları, Mozilla PDF.js kütüphanesinin yerel kopyasıdır. 

- Harici CDN'den yüklenmez (CSP uyumlu).
- `notlar.*/not/{slug}` detay sayfasında, embed edilmiş PDF görüntüleyici olarak çalışır.
- Sayfa gezintisi, zoom ve tam ekran desteği sunar.

---

## SEO ve Site Haritası

### Sitemap
`notlar.fezadan.org/sitemap.xml` → `sitemap_notes.xml` (statik veya dinamik fallback)

### robots.txt
`notlar.fezadan.org/robots.txt` → ana siteden farklı kurallar:
- Googlebot ve Bingbot'a izin verilir
- AI botları (GPTBot, ChatGPT-User, Google-Extended, ClaudeBot) engellenir

### Meta Etiketleri
- Sayfalarda `<meta name="robots" content="noai, noimageai">` etiketi bulunur.

---

## Admin Panelinde Not Yönetimi

`/yonetim` paneli üzerinden:

1. **Yeni Not Ekleme:** `storeNote()` — PDF R2'ye yüklenir, slug oluşturulur.
2. **Not Güncelleme:** `updateNote()` — Eski PDF R2'den silinir, yeni PDF yüklenir, `file_size` güncellenir.
3. **Not Silme:** `deleteNote()` — Veritabanı kaydı ve R2'deki PDF dosyası silinir.

> Detaylar için [04-yonetim-paneli.md](04-yonetim-paneli.md) belgesine bakın.
