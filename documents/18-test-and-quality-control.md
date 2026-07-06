# 18. Test, Kalite Kontrol ve Hata Ayıklama

## Test Altyapısı

Fezadan projesinde otomatik birim test framework'ü (PHPUnit vb.) bulunmaz. Bunun yerine aşağıdaki araçlar ve yöntemler kullanılır:

| Araç | Tür | Açıklama |
|------|-----|----------|
| [integration_tests.php](file:///d:/Fezadan/scripts/integration_tests.php) | API/Route Integration | Rota, Robots, Sitemap, CSRF ve Auth testleri |
| [check-site.py](file:///d:/Fezadan/scripts/check-site.py) | Selenium E2E | Tarayıcı üzerinden canlı site testi |
| `php -l` | Sözdizimi kontrolü | PHP dosyalarında parse hatası taraması |
| `npm test` | — | Kullanılmaz (exit 1) |
| `APP_DEBUG=1` | Hata ayıklama | Localhost'ta hata detaylarını gösterir |
| [AdminLog.php](file:///d:/Fezadan/app/Core/AdminLog.php) | Loglama | Admin eylemlerini JSON formatında kaydeder |
| [ErrorHandler.php](file:///d:/Fezadan/app/Core/ErrorHandler.php) | Hata yakalama | Production'da trace dosyalarını `logs/` klasörüne yazar |

---

## PHP CLI Entegrasyon Testleri

Fezadan projesinin sunucu taraflı temel işlevselliği, `scripts/` klasöründeki özel test betikleriyle doğrulanır. Bu testler herhangi bir framework (PHPUnit vb.) gerektirmez.

```bash
# Rota, CSRF, Admin İzolasyonu ve SEO çıktı testleri
php scripts/integration_tests.php
```

---

## Selenium Smoke Test — check-site.py

[scripts/check-site.py](file:///d:/Fezadan/scripts/check-site.py) Python betiği, canlı sitenin temel işlevlerini tarayıcı üzerinden test eder.

### Gereksinimler

- **Python 3** (venv içinde `selenium` paketi)
- **Firefox Developer Edition** → `C:\Program Files\Firefox Developer Edition\firefox.exe`
- **Geckodriver** (Firefox WebDriver)
- Site `http://localhost:8080` adresinde çalışıyor olmalı

### Çalıştırma

```bash
python scripts/check-site.py
```

### Test Edilenler

| Test | Kontrol |
|------|---------|
| Anasayfa | Sayfa başlığı "FEZADAN" içermeli, `/` → `/tr` veya `/en` yönlendirmesi |
| Makale listesi | `/tr/makaleler` sayfasındaki makale linkleri taranır |
| Hakkında | `/tr/hakkinda` sayfası kontrol edilir |
| (diğer testler) | ... |

### Çıktı

- Konsola `[PASS]` veya `[FAIL]` sonuçları yazdırılır.
- Her test adımında `scripts/screenshots/` klasörüne ekran görüntüsü kaydedilir.

### Sorun Giderme

| Hata | Çözüm |
|------|-------|
| Firefox binary bulunamadı | Firefox Dev Edition'ı belirtilen yola kurun veya script'teki `binary_location` satırını güncelleyin |
| Geckodriver log kilidi | `geckodriver.log` dosyasını silin, arka plan Python işlemlerini kapatın |
| Siteye erişilemiyor | `docker-compose up -d` ile servislerin çalıştığından emin olun |

---

## PHP Sözdizimi (Syntax) Kontrolü

### Tek Dosya Kontrolü

```bash
php -l app/Controllers/AdminController.php
```

Başarılı: `No syntax errors detected in app/Controllers/AdminController.php`
Hatalı: `Parse error: syntax error, unexpected '}' in ... on line 42`

### Tüm Projeyi Tarama (PowerShell)

```powershell
Get-ChildItem -Recurse -Filter *.php | ForEach-Object { php -l $_.FullName } | Select-String -Pattern "Parse error"
```

Bu komut, projedeki tüm PHP dosyalarını tarar ve sadece hata içerenleri listeler.

---

## Hata Ayıklama (Debugging)

### APP_DEBUG ile Hata Detayları

[public_html/index.php](file:///d:/Fezadan/public_html/index.php) dosyası, sadece **localhost**'tan gelen isteklerde ve `APP_DEBUG=1` ortam değişkeni ayarlıysa PHP hatalarını ekranda gösterir:

```bash
# Docker ortamında .env dosyasına ekleyin:
APP_DEBUG=1
```

### ErrorHandler — Production Hata Yönetimi

[app/Core/ErrorHandler.php](file:///d:/Fezadan/app/Core/ErrorHandler.php) sınıfı:

- **Production'da:** PHP hatalarını gizler, kullanıcıya özel 500 hata sayfası gösterir.
- **Hata izleri (trace):** `logs/` klasörüne timestamp'li dosyalar olarak yazılır.
- **404 hataları:** [app/Views/errors/404.php](file:///d:/Fezadan/app/Views/errors/404.php) görünümü ile özel sayfa.

### AdminLog — Admin Eylem Takibi

[app/Core/AdminLog.php](file:///d:/Fezadan/app/Core/AdminLog.php) sınıfı, yönetim panelinde yapılan işlemleri JSON formatında `logs/admin.log` dosyasına kaydeder:

```json
{"timestamp": "2026-06-15 14:30:00", "level": "INFO", "admin": "antigravity", "action": "article_created", "article_id": 42}
```

- **Otomatik Rotasyon:** Log dosyası 1 MB'ı aştığında gzip ile sıkıştırılarak arşivlenir ve yeni log dosyası oluşturulur.
- **Seviyelendirme:** `INFO`, `WARNING`, `ERROR` seviyeleri.

### Hata Loglarını İnceleme

```bash
# Docker ortamında:
docker-compose exec php cat /var/www/html/logs/admin.log

# cPanel'de:
# Metrics → Errors sayfasından Apache/PHP hata loglarını görüntüleyin
```

---

## Yaygın Hata Ayıklama Yöntemleri

### Beyaz Sayfa (White Screen / 500 Hatası)

1. `.env` dosyasının varlığını ve doğruluğunu kontrol edin.
2. `composer install` yapıldı mı? (`vendor/autoload.php` var mı?)
3. `php -l` ile değiştirilen dosyalarda sözdizimi hatası var mı?
4. cPanel → Error Log sayfasını kontrol edin.

### Session / Giriş Döngüsü

Yerel ortamda HTTP ile çalışırken giriş yapılamıyorsa:
- `index.php` içinde `session.cookie_secure` ayarının localhost için `0` olduğundan emin olun.
- `/tmp` veya session dizinine yazma izni olduğunu kontrol edin.

### R2 Yükleme Hataları

- `.env` dosyasındaki `R2_*` veya `CDN_*` değişkenlerinin doğru olduğunu kontrol edin.
- `php -m | grep -i gd` ile GD kütüphanesinin yüklü olduğunu doğrulayın.
- `upload_max_filesize` ve `post_max_size` limitlerini kontrol edin.

---

## Geliştirme Sırasında Hızlı Kontroller

### Değişiklik Sonrası Kontrol Listesi

1. `php -l` ile değiştirilen PHP dosyalarında sözdizimi hatası yok.
2. `docker-compose up -d` ile servisler çalışıyor.
3. `npm run dev` ile CSS değişiklikleri derleniyor.
4. `http://localhost:8080` adresinde site doğru görünüyor.
5. Admin panelinde (`http://localhost:8080/yonetim`) giriş yapılabiliyor.
6. Medya yükleme (kapak görseli) çalışıyor.

### Docker Ortamında Canlı Log Takibi

```bash
# Apache error log:
docker-compose logs -f php

# MySQL log:
docker-compose logs -f db
```

---

## Üretim (Production) Ortamı Kontrolleri

Canlıya almadan önce:

- [ ] `APP_ENV=production` ve `APP_DEBUG=false`
- [ ] `npm run build` ile CSS minify edilmiş
- [ ] `composer install --no-dev --optimize-autoloader` çalıştırılmış
- [ ] `.env` dosyasındaki tüm değişkenler canlı ortam için doğru
- [ ] `APP_SECURITY_SALT` rastgele 64 karakter (varsayılan `change-me` değil)
- [ ] Document Root `public_html/` olarak ayarlanmış
- [ ] Cron job'lar kurulmuş (sitemap, publish-scheduled, backup-db)
- [ ] `scripts/check-site.py` canlı siteye karşı çalıştırılmış (URL'yi güncelleyerek)

---

## Tasarım Korumaları, Önemli Kurallar ve Kod Prensipleri

Geliştirme yaparken sistemin güvenliğini, gizliliğini ve kararlılığını korumak için uygulanan bazı spesifik mimari korumalar ve tasarım kararları aşağıda listelenmiştir:

### 1. Subdomain Yönlendirme Güvenliği (App.php)
- **Kural:** `anonymitycheck` subdomain'inden gelen istekler, normal ana site rotalarından (dil ön ekleri, 404 yönlendirmeleri, yazar/makale slug kontrolleri) tamamen izole edilmelidir.
- **Uygulama:** [App.php](file:///d:/Fezadan/app/Core/App.php#L116) yönlendiricisinde, hostname `anonymitycheck.` ile başlıyorsa istek doğrudan `AnonymityCheckController`'a yönlendirilir ve dil tespiti vb. mantık atlatılır. Bu sayede olası çakışmalar engellenir.

### 2. İstilacı Olmayan Yerel Uygulama Keşfi (Port Sniffing)
- **Sorun:** Kullanıcının bilgisayarında yüklü uygulamaları (Discord, Steam, Tor vb.) tespit etmek için `discord://` gibi custom protokol handler'ları tetiklemek, modern tarayıcılarda kullanıcıya engelleyici ve rahatsız edici güvenlik uyarı pencereleri gösterir.
- **Çözüm:** Bunun yerine yerel ağ soketlerine (localhost TCP portları) **sessiz port koklama (port sniffing)** uygulanır:
  - Discord RPC için `127.0.0.1:6463`
  - Steam Client için `127.0.0.1:57343` ve `127.0.0.1:27015`
  - Tor SOCKS Proxy için `127.0.0.1:9050`
- **CORS bypass:** Tarayıcı güvenlik gereği localhost'tan gelen ham cevabı okutmaz, ancak port kapalıysa anında `TypeError: Failed to fetch` hatası dönerken; port açıksa (CORS hatası verse dahi) ağ bağlantısı gerçekleştiği için hata tipi veya süre davranışı farklıdır. Bu fark yakalanarak kullanıcının masaüstü uygulamaları sessizce doğrulanır.

### 3. Kullanıcı Gizliliği ve Yerel Hesaplama (Local Sandbox)
- **Prensip:** Gizlilik test aracı, kendisi bir takip aracına dönüşmemelidir.
- **Korumalar:**
  - Tüm gelişmiş tarama işlemleri (Canvas hash üretimi, WebGL parametre ölçümü, AudioContext eğrisi hesaplama, Font ölçümleri, XS-Leaks timing testleri) **%100 kullanıcının tarayıcısında (client-side)** çalışır.
  - Sunucu tarafında tutulan günlük loglar (`logs/anonymity_check.jsonl`), ziyaretçinin IP adresini ve User-Agent bilgisini ham haliyle kaydetmez. Değerler, günlük değişen bir salt anahtarıyla SHA-256 hash işleminden geçirilerek maskelenir ve bu log dosyası her gün otomatik olarak temizlenir (truncation).

### 4. XS-Leaks Timing Attack Kalibrasyonu
- **Nasıl Çalışır?** Kullanıcının Google veya GitHub hesaplarında oturumunun açık olup olmadığını anlamak için bu sitelerin giriş kontrol yönlendirmelerine (`CheckCookie` vb.) istekler atılarak gecikme süresi (`performance.now()`) ölçülür. 
- **Koruma:** İstekler `mode: 'no-cors'` ve `cache: 'no-store'` ile atılır. Oturumu açık olan kullanıcılarda istek yönlendirme zinciri çok hızlı çözümlenirken (önbellek veya oturum çerez doğrulaması sayesinde <120ms), oturumu kapalı kullanıcılarda tam login sayfalarına yönlendirme yapıldığından süre belirgin şekilde uzar (>250ms).

### 5. Git Hijyeni ve Sırların Korunması (.gitignore)
- **Dosya Dışı Bırakma:** `.env` ve `app/Config/.security_salt` gibi gizli anahtarlar kesinlikle git repolarına commit edilmemelidir.
- **Kök Klasör Markdown Plan Koruması:** `yeniplan.md`, `brainstorming.md`, `analyses.md` vb. gibi geliştirme sırasında kullanılan geçici planlama dosyalarının uzak sunucuya (live/push) giderek repoda kalabalık yapmasını önlemek için `.gitignore` dosyasına kök dizin kuralları eklenmiştir:
  ```gitignore
  # Ignore temporary plan and brainstorm markdown files at root
  /*.md
  !/README.md
  !/AGENTS.md
  ```
- **Yerel Temizlik:** Root seviyesindeki geçici planlama markdown dosyaları `.gitignore` kapsamındadır ve commit edilmez.

---

## Hızlı Başvuru: Sık Kullanılan Komutlar

```bash
# PHP sözdizimi kontrolü (tek dosya)
php -l app/Views/front/anonymity_check.php

# PHP sözdizimi kontrolü (tüm proje)
Get-ChildItem -Recurse -Filter *.php | ForEach-Object { php -l $_.FullName }

# Git'ten dosyayı indeksinden temizleme (Yerel kopyayı korur)
git rm --cached unwanted-file.php

# Tailwind CSS watch
npm run dev

# Tailwind CSS production build
npm run build

# Docker servisleri yeniden başlatma
docker-compose down; docker-compose up -d

# Docker konteyner durumlarını listeleme
docker-compose ps

# Selenium smoke testi
python scripts/check-site.py

# Sitemap zorla üretim
php cron/generate-sitemap.php --force

# Planlı makaleleri yayınlama
php cron/publish-scheduled.php

# Veritabanı yedekleme
php cron/backup-db.php
```
