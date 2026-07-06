# 8. .env Dosyası

## .env Nedir? (Temel Kavram)

`.env` (environment — ortam) dosyası, uygulamanın çalışması için gereken gizli veritabanı şifrelerini, API anahtarlarını ve ortam değişkenlerini barındıran yapılandırma dosyasıdır. 

Proje, harici bir PHP dotenv kütüphanesi (vlucas/phpdotenv vb.) kullanmak yerine bağımlılıkları minimize etmek için kendi özel `.env` okuyucusunu kullanır.

---

## Tüm Yapılandırma Değişkenleri

### SİTE AYARLARI

- **`SITE_URL`** (ör: `https://fezadan.org`): Ana sitenin adresi (Sonda `/` olmadan girilmelidir).
- **`NOTES_SITE_URL`** (ör: `https://notlar.fezadan.org`): PDF notlar subdomain adresi.
- **`CANONICAL_URL`** (ör: `https://fezadan.org`): SEO için canonical adres bildirimi.

### VERİTABANI BAĞLANTISI

- **`DB_HOST`** (ör: `localhost` veya Docker için `db`): MySQL sunucu adresi.
- **`DB_NAME`** (ör: `kullanici_site_db`): Veritabanı adı.
- **`DB_USER`** (ör: `kullanici_admin`): Veritabanı kullanıcı adı.
- **`DB_PASS`**: Veritabanı kullanıcısına ait şifre.
- **`DB_CHARSET`** (Varsayılan: `utf8mb4`): Unicode karakter kodlaması.

### GÜVENLİK AYARLARI

- **`APP_SECURITY_SALT`**: CSRF ve IP hash doğrulamalarında kullanılan anahtardır. Boş bırakıldığında veya `change-me` değerinde kalırsa, sistem [app/Config/config.php](file:///d:/Fezadan/app/Config/config.php) aracılığıyla rastgele 64 karakterli tuz üretip [app/Config/.security_salt](file:///d:/Fezadan/app/Config/.security_salt) dosyasına yazar.
- **`DEPLOY_SECRET`**: Sunucunun GitHub webhook'ları üzerinden otomatik olarak güncellenmesi sırasında gelen HTTP isteğinin imzasını (HMAC-SHA256) doğrulamak için kullanılan gizli deploy şifresidir.
- **`JWT_SECRET`**: API token imzalama süreçlerinde kullanılan yedek anahtar.
- **`RECAPTCHA_SITE_KEY`** & **`RECAPTCHA_SECRET_KEY`**: İletişim sayfasındaki robot testleri için Google reCAPTCHA v2 anahtarları.

### HATA AYIKLAMA & ORTAM

- **`APP_ENV`** (`development` veya `production`): Uygulama ortamı. Canlı sunucularda hataların gizlenmesi için `production` yapılmalıdır.
- **`APP_DEBUG`** (`true` veya `false`): Hata detaylarının ekranda gösterilip gösterilmeyeceğini belirler. Canlı ortamda `false` olmalıdır.
- **`SENTRY_DSN`**: Canlı ortam hata bildirim entegrasyonu için Sentry adresi.

### BULUT DEPOLAMA (CLOUDFLARE R2)

- **`CDN_TYPE`** (`cloudflare_r2` veya `local`): Medya dosyalarının depolanacağı alan. `local` seçilirse sunucu yerel diskine, `cloudflare_r2` seçilirse buluta yükleme yapılır.
- **`CDN_ENDPOINT`**: Cloudflare R2 API uç noktası adresi.
- **`CDN_ACCESS_KEY_ID`**: R2 API erişim anahtarı.
- **`CDN_SECRET_ACCESS_KEY`**: R2 API gizli şifresi.
- **`CDN_BUCKET_NAME`**: Depolamanın yapılacağı kova (Bucket) adı.
- **`CDN_URL`**: Cloudflare R2 public bucket adresi veya tanımlanmış özel CDN subdomain'i (ör: `https://cdn.fezadan.org`).

**CDN URL Fallback Çözümleme Zinciri:**
Sistemde görsel veya dosya sunulurken `CDN_URL` tanımlanmamışsa sırasıyla şu öncelik adımları izlenir:
1. `CDN_URL` kullanılır.
2. Tanımlı değilse `R2_PUBLIC_URL` adresine bakılır.
3. O da bulunamazsa varsayılan site adresi (`SITE_URL`) üzerinden yerel disk fallback yolu izlenir.

---

## config.php ve R2Storage.php .env Okuma Kuralları

- **config.php Okuyucusu:** [app/Config/config.php](file:///d:/Fezadan/app/Config/config.php) içerisindeki `env_value()` statik önbelleğe alma mantığı ile çalışır. `#` ile başlayan satırları ve yorumları eler, tırnak işaretlerini temizler.
- **R2Storage.php İzolasyonu:** [app/Core/R2Storage.php](file:///d:/Fezadan/app/Core/R2Storage.php) sınıfı R2 depolama ayarlarını (`R2_*` veya `CDN_*`) bağımsız olarak okur ve kendi bağlantısını kurar. CDN yapılandırılmamışsa dahi çekirdek yapılandırmayı etkilemez.
