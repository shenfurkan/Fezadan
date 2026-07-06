# 7. Kurulum ve Deploy

## Ön Gereksinimler

Fezadan projesinin sunucunuzda çalıştırılabilmesi için gereken asgari sistem bileşenleri:

| Yazılım | Minimum Versiyon | Neden Gerekli? |
|---------|-----------------|----------------|
| PHP | 8.0 (8.2 önerilir) | Modern dil yapıları (readonly, match, named arguments) |
| MySQL / MariaDB | 5.7 / 10.2 | InnoDB tabloları ve FULLTEXT arama indeksleri için |
| Apache | 2.4 | `.htaccess` mod_rewrite kuralları ve güvenlik başlıkları için |
| Composer | 2.x | R2 entegrasyonunda kullanılan AWS SDK paketini kurmak için |
| Node.js | 18.x | TailwindCSS v4 derleme araçları için |

### Gerekli PHP Extension'ları
```
pdo_mysql  → Veritabanı bağlantısı ve self-healing migration işlemleri
mbstring   → Türkçe UTF-8 karakter desteği
curl       → Cloudflare R2 bulut depolama API istekleri
gd         → OG görsel üretimi (libfreetype ve libjpeg kütüphaneleriyle birlikte)
fileinfo   → Yüklenen PDF ve görsellerin gerçek MIME tipini okuma
xml        → Sitemap ve RSS şablonları oluşturma
```

---

## Yöntem 1: Docker ile Kurulum (Önerilen)

Docker ortamı projenin çalışması için gereken tüm sistem bağımlılıklarını izole bir şekilde hazırlar.

### 1. Dosyaları İndirin ve Yapılandırın
```bash
git clone https://github.com/shenfurkan/Fezadan.git
cd Fezadan
cp .env.example .env
```
Docker varsayılan ayarlar ile çalışacağından `.env` dosyasında bir değişiklik yapmanız gerekmez.

### 2. Docker Container'larını Ayağa Kaldırın
```bash
docker-compose up -d --build
```
Bu komut [Dockerfile](file:///d:/Fezadan/Dockerfile) içeriğine göre PHP 8.2 + Apache imajını hazırlar.
- **GD Kurulum Detayı:** Dockerfile içerisinde GD kütüphanesi otomatik olarak `libfreetype6-dev`, `libjpeg62-turbo-dev` ve `libpng-dev` bağımlılıkları ile derlenir. Bu sayede yerel ortamda OG Görsel Üretimi sorunsuz çalışır.

### 3. Servislerin Durumu
- **Web Sitesi:** `http://localhost:8080` (Kök dizin [public_html/](file:///d:/Fezadan/public_html/) olarak ayarlanmıştır).
- **phpMyAdmin:** `http://localhost:8000` (Kullanıcı: `root`, Şifre: `root`).
- **Veritabanı:** Eğer `localhost.sql` dump dosyası proje kökünde bulunuyorsa, ilk açılışta MySQL container'ına otomatik olarak yüklenir. Bu dosya repoda tutulmaz, isteğe bağlıdır.

---

## Yöntem 2: cPanel ile Yayına Alma (Canlı Sunucu)

Canlı hosting ortamında sıfırdan kurulum adımları:

### Adım 1 — Veritabanını Hazırlayın
1. cPanel → **MySQL Databases** bölümünden boş bir veritabanı oluşturun (ör: `kullanici_fezadan`).
2. Yeni bir MySQL kullanıcısı ekleyin ve veritabanına bağlayarak **ALL PRIVILEGES** yetkisi verin.

### Adım 2 — PHP Sürümünü ve Eklentileri Seçin
1. cPanel → **Select PHP Version** alanından PHP sürümünü `8.2` veya `8.3` olarak seçin.
2. `pdo_mysql`, `mbstring`, `curl`, `fileinfo`, `xml` ve `gd` eklentilerinin aktif olduğunu doğrulayın.

### Adım 3 — Dosyaları Yükleyin
- **Seçenek A (Otomatik):** cPanel → **Git Version Control** üzerinden `https://github.com/shenfurkan/Fezadan.git` adresini klonlayın.
- **Seçenek B (Manuel):** Proje dosyalarını FTP veya File Manager yardımıyla sunucunuzun `public_html` dizinine yükleyin.

### Adım 4 — Document Root Ayarı (Kritik)
Güvenlik nedeniyle projenin çekirdek dosyaları (`app/`, `.env` vb.) web erişimine tamamen kapalı olmalıdır. Bu yüzden hosting domain ayarlarından **Document Root** alanını projenin alt klasörü olan `public_html` yapmanız gerekir.
- **Doğru Yol:** cPanel ana kurulumunda web root `/home/fezadano5/public_html` olmalıdır.
- **Subdomain (Alt Alan Adı) Yapılandırması (Örn: `furkan.fezadan.org`):**
  * cPanel'de subdomain oluştururken **Document Root** alanına `/home/public_html/furkan` gibi tam/mutlak sunucu yolları yazmak hatalıdır ve sitenin 404/boş sayfa dönmesine yol açar.
  * cPanel bu alanı ev dizininizden (home directory) itibaren göreceli olarak kabul eder. Bu yüzden subdomain yolu sadece **`public_html/furkan`** (veya projenizin kurulu olduğu dizine göre `public_html/fezadan/public_html/furkan`) şeklinde girilmelidir.
  * Cloudflare DNS panelinde alt alan adını (örn: `furkan`) ana alan adına yönlendirmek için bir **CNAME** kaydı oluşturup hedefi `fezadan.org` yapmalı ve **Proxied (Turuncu Bulut)** modunu aktif etmelisiniz.
  * Sunucunun ana IP adresi **`213.238.183.121`**'dir.

### Adım 5 — .env Dosyasını Yapılandırın
Proje kök dizinindeki `.env` dosyasını düzenleyin:
- `SITE_URL` ve `NOTES_SITE_URL` alanlarına canlı site adreslerinizi yazın.
- Veritabanı adı, kullanıcısı ve şifresini girin.
- `APP_SECURITY_SALT` alanını boş bırakabilir veya rastgele 64 karakter tanımlayabilirsiniz (Sistem boş bırakılırsa `app/Config/.security_salt` dosyasını otomatik üretir).
- Canlı ortamda hataların ziyaretçilere sızdırılmasını önlemek için `APP_ENV=production` ve `APP_DEBUG=false` olarak ayarlayın.

### Adım 6 — Composer Bağımlılıklarını Kurun
cPanel üzerinde terminal erişiminiz varsa:
```bash
composer install --no-dev --optimize-autoloader
```
Terminal yoksa, kendi bilgisayarınızda `composer install` çalıştırıp oluşan `vendor/` klasörünü FTP ile sunucuya yükleyin.

---

## Yöntem 3: Otomatik Deploy (GitHub Webhook)

Production deploy için aktif yol cPanel Git deployment'tır. [public_html/git-deploy.php](file:///d:/Fezadan/public_html/git-deploy.php) eski webhook endpoint'idir ve 410 döndürür:
1. GitHub reponuzda **Settings → Webhooks** sayfasına gidin.
2. Webhook deploy kullanılmaz; cPanel Git Version Control ekranında Update from Remote ve Deploy HEAD Commit akışı izlenir.
3. Content Type alanını `application/json` yapın.
4. `.env` dosyasına eklediğiniz `DEPLOY_SECRET` şifresini Secret alanına girin.
5. Değişiklikleri kaydedin. cPanel Git deploy akışı production kodunu günceller; ayrı bir webhook endpoint'i kullanılmaz.

---

## Zamanlanmış Görevler (Cron Jobs) Kurulumu

Sistemin düzgün çalışması için cPanel → **Cron Jobs** paneline şu üç görevi ekleyin:

### 1. Site Haritası Üretimi (Saat Başı)
Sitemap dirty flag sistemini denetleyerek içerik değişmişse site haritalarını otomatik günceller.
```bash
*/5 * * * * /usr/local/bin/php /home/fezadano5/cron/generate-sitemap.php >/dev/null 2>&1
```

### 2. Planlı Makale Yayınlama (Dakika Başı)
Zamanı gelen planlı taslakları otomatik olarak yayına alır.
```bash
*/5 * * * * /usr/local/bin/php /home/fezadano5/cron/publish-scheduled.php >/dev/null 2>&1
```

### 3. Veritabanı Yedeğini R2'ye Alma (Günlük)
Veritabanını yedekleyip sıkıştırarak Cloudflare R2'ye yükler. 7 günden eski yedekleri otomatik siler.
```bash
0 3 * * * /usr/local/bin/php /home/fezadano5/cron/backup-db.php >/dev/null 2>&1
```
