# Fezadan Geliştirici Kılavuzu: Sık Karşılaşılan Sorunlar ve Çözümleri

Bu doküman, Fezadan projesinin yerel geliştirme (Docker, Selenium vb.) ve canlı ortam süreçlerinde karşılaşabileceğiniz yaygın teknik aksaklıkları, hata mesajlarını ve bunların adım adım çözümlerini içermektedir.

---

## 1. Git Kilit Hatası (Unable to create 'index.lock')

### Hata Belirtisi
```text
fatal: Unable to create 'D:/Fezadan/.git/index.lock': File exists.
Another git process seems to be running in this repository, or the lock file may be stale.
```

### Neden Olur?
Git, repository veritabanını güncellerken (örneğin dosya durumlarını kontrol ederken veya commit hazırlarken) çakışmaları önlemek için geçici bir `index.lock` kilit dosyası oluşturur. Eğer bu işlem devam ederken terminal zorla kapatılırsa, bir arka plan görevi durdurulursa (kill edilirse) ya da editörün Git entegrasyonu işlem yaparken siz de terminalden Git komutu koşturursanız bu kilit dosyası silinemez ve sonraki tüm yazma işlemlerini engeller.

### Çözüm
Kilit dosyasını el ile veya terminalden güvenli bir şekilde silmeniz gerekir:

*   **PowerShell (Windows - Önerilen):**
    ```powershell
    Remove-Item -Path "D:\Fezadan\.git\index.lock" -Force -ErrorAction SilentlyContinue
    ```
*   **Git Bash / Linux / macOS:**
    ```bash
    rm -f .git/index.lock
    ```
*   **Manuel Yöntem:** `D:\Fezadan\.git\` gizli klasörüne girip `index.lock` dosyasını silin.

---

## 2. Docker Port Çakışması Hatası (Port Already In Use)

### Hata Belirtisi
```text
Error starting userland proxy: bind: address already in use: 0.0.0.0:8080
veya
bind: address already in use: 0.0.0.0:3306
```

### Neden Olur?
Yerel bilgisayarınızda halihazırda çalışan başka bir web sunucusu (IIS, XAMPP, WampServer) port 8080'i veya yerel bir MySQL sunucusu port 3306'yı işgal ediyordur.

### Çözüm
1.  **Portu Kullanan Uygulamayı Bulup Kapatmak (Windows):**
    *   **Port 8080 için:** PowerShell yönetici modunda şu komutu çalıştırıp portu kullanan PID'yi (Process ID) bulun:
        ```powershell
        Get-Process -Id (Get-NetTCPConnection -LocalPort 8080).OwningProcess
        ```
    *   İlgili işlemi durdurun:
        ```powershell
        Stop-Process -Id <PID> -Force
        ```
2.  **Docker Portunu Değiştirmek:**
    *   Eğer çalışan diğer servisi kapatamıyorsanız, `docker-compose.yml` dosyasını açıp sol taraftaki (dışarıya açılan) portları değiştirin:
        ```yaml
        # docker-compose.yml örneği:
        ports:
          - "8089:80" # 8080 yerine 8089 yaptık
        ```

---

## 3. Selenium Smoke Check (check-site.py) Hataları

### A. Tarayıcı veya Geckodriver Bulunamadı Hatası
```text
selenium.common.exceptions.WebDriverException: Message: Expected browser binary location, but unable to find binary in default location
```

#### Çözüm
`scripts/check-site.py` scripti varsayılan olarak Firefox Developer Edition'ı Windows sistemlerde `C:\Program Files\Firefox Developer Edition\firefox.exe` yolunda arar.
*   Eğer Firefox bu yolda kurulu değilse, tarayıcı yolunu kendi kurulumunuza göre güncelleyin.
*   Alternatif olarak, testlerinizi standart Firefox veya Chrome'a uyarlamak isterseniz webdriver tanımlarını değiştirin:
    ```python
    # Standart Firefox için:
    from selenium.webdriver.firefox.options import Options
    options = Options()
    # options.binary_location satırını devre dışı bırakın
    ```

### B. Geckodriver Log Kilidi veya İzin Hataları
Selenium çalışırken kök dizine `geckodriver.log` dosyası yazar ve bazen bu dosya üzerinde yazma/kilit çakışması olabilir. Bu dosya zaten `.gitignore` listesindedir, ancak silinmek istendiğinde kilit hatası verirse terminali veya arka plan python işlemlerini kapatıp tekrar deneyin.

---

## 4. "Session / Oturum Açılamıyor" veya Giriş Sayfasına Yönlendirme Döngüsü

### Hata Belirtisi
Doğru kullanıcı adı ve şifreyi girmenize rağmen yönetim paneline girildiğinde anında tekrar login sayfasına yönlendiriliyorsunuz.

### Neden Olur?
1.  **Çerez Güvenlik Ayarları (session.cookie_secure):** `index.php` dosyasında `session.cookie_secure = 1` ayarlıdır. Bu, tarayıcınızın çerezleri yalnızca güvenli (HTTPS) bağlantılar üzerinden göndereceği anlamına gelir. Eğer yerel ortamda HTTP (örneğin `http://localhost:8080`) üzerinden bağlanıyorsanız tarayıcınız session çerezini kaydetmez veya sunucuya geri göndermez.
2.  **Yazma İzinleri:** Docker veya sunucu ortamında session verilerinin yazıldığı `/tmp` veya `/home/fezadano5/tmp/sessions` dizinine yazma yetkisi bulunmuyordur.

### Çözüm
*   **Yerel Geliştirme İçin:** Yerelde çalışırken HTTPS kullanmıyorsanız `index.php` içinde yerel IP veya localhost tespiti yapılarak `cookie_secure` parametresinin yerel isteklerde `0` yapıldığından emin olun.
*   **İzin Kontrolü (Docker):** Docker konteynerinin `tmp/` klasörüne yazma izni olduğundan emin olun. Gerekirse Docker'ı `--build` ile yeniden başlatın.

---

## 5. R2 Storage Yükleme Hataları (Media Upload Failures)

### Hata Belirtisi
Yönetim panelinde resim veya ses dosyası yüklerken hata alıyorsunuz veya yüklenen görseller kırık görünüyor.

### Neden Olur?
1.  `.env` dosyasındaki Cloudflare R2 kimlik bilgilerinin (`R2_ACCESS_KEY_ID`, `R2_SECRET_ACCESS_KEY`, `R2_BUCKET`) eksik veya yanlış tanımlanması.
2.  Yüklenen dosyanın PHP limitlerini (`upload_max_filesize` veya `post_max_size`) aşması.

### Çözüm
*   `.env` ve `app/Config/config.php` dosyalarında credentials doğruluğunu test edin.
*   `public_html/.htaccess` veya Docker php.ini dosyalarındaki limitleri kontrol edin:
    ```ini
    upload_max_filesize = 50M
    post_max_size = 50M
    memory_limit = 256M
    ```
*   GD veya Imagick kütüphanesinin PHP üzerinde kurulu ve aktif olduğunu doğrulayın (`php -m | grep -i gd`).

---

## 6. PHP Sözdizimi Kontrolleri (Hızlı Tarama)

Kod yazarken veya dosyaları düzenlerken tarayıcıda veya loglarda 500 hatası almadan önce sözdizimi (syntax) hatalarını hızlıca bulmak için PHP CLI'dan yararlanabilirsiniz.

*   **Tek Bir Dosyayı Kontrol Etmek:**
    ```bash
    php -l app/Controllers/AdminController.php
    ```
    *Eğer hata yoksa `No syntax errors detected in...` yanıtı döner.*

*   **Tüm Projedeki PHP Dosyalarını Taramak (PowerShell):**
    ```powershell
    Get-ChildItem -Recurse -Filter *.php | Foreach-Object { php -l $_.FullName } | Select-String -Pattern "Parse error"
    ```
    *Bu komut parse hatası içeren dosyaları hızlıca listeler.*

---

## 7. İzleme Araçlarında (Koality, UptimeRobot vb.) Düşük Erişilebilirlik (Availability) ve Uptime Hataları

### Hata Belirtisi
Sitenin aktif izleme panelinde (örneğin Koality, Semrush, UptimeRobot):
*   **Current Availability:** 100/100 (Şu anki erişim sorunsuz)
*   **Past Availability:** 0/100 (Son 24 saatte sitenin bir kısmına veya tamamına erişilemedi)
*   **Uptime:** 50/100 (Kritik erişilebilirlik hataları uyarısı)

### Neden Olur?
1.  **Cloudflare Bot Koruması / WAF (En Yaygın Sebep):** Fezadan projesi Cloudflare arkasında çalışmaktadır. Cloudflare'in güvenlik duvarı (WAF) veya Bot Mücadele Modu (Bot Fight Mode), otomatik tarama yapan izleme botlarını (crawler) şüpheli veya bot saldırısı olarak algılar. Botlara 503 (Challenge/IUAM) ya da 403 Forbidden cevabı döner ve bu durum izleme panelinize "site çöktü" veya "erişilemiyor" olarak yansır.
2.  **Ağır Yedekleme Cron İşleri:** Gece 03:00 civarında çalışan veritabanı yedekleme cronu (`cron/backup-db.php`) veya cPanel'in tam yedekleme işlemi, paylaşımlı sunucunun CPU/RAM sınırlarını aşarak kısa süreli sunucu yanıt zaman aşımına (Timeout) yol açabilir.
3.  **Projenin Panele Yeni Eklenmiş Olması:** İzleme aracı sisteme yeni tanımlandıysa, geçmiş 24 saate ait veri toplayamadığı için geçmiş puanını `0` olarak başlatır. Bu durum zamanla kendiliğinden düzelir.
4.  **SSL Sertifikası Hataları:** Cloudflare SSL/TLS ayarı "Full (Strict)" değil de "Flexible" ise veya sunucu tarafındaki Let's Encrypt sertifikasında yenilenme sırasında anlık bir kesinti olduysa botlar SSL handshake hatası alabilir.

### Çözüm Adımları
1.  **Cloudflare İstisnaları (Bypass Kuralı) Ekleyin:**
    *   Cloudflare kontrol panelinize girin.
    *   **Security (Güvenlik) -> WAF -> Custom Rules (Özel Kurallar)** sekmesine gelin.
    *   Yeni bir kural ekleyin: *User-Agent* alanı izleme aracınızın adını (örneğin `Koality`, `UptimeRobot`) içeriyorsa veya IP adresi izleme servisinin IP listesindeyse, eylem olarak **Bypass** (Güvenlik Duvarını Atla) seçin.
2.  **Sunucu Hata Loglarını (cPanel/Error Log) İnceleyin:**
    *   cPanel paneline girerek **Metrics -> Errors** (Hatalar) kısmından son 24 saatte gerçek bir Apache veya PHP çökmesi (örn: `Memory limit exceeded`, `Max execution time exceeded`) yaşanıp yaşanmadığını teyit edin.
3.  **Sağlık Kontrolü Uçnoktası (`health.php`):**
    *   Uptime araçlarına doğrudan ana sayfayı kontrol ettirmek yerine hafif ve veri tüketmeyen `public_html/health.php` uçnoktasını (URL: `https://siteadresiniz.com/health.php`) hedef gösterin. Bu dosya veritabanı bağlantısını test eder ve gereksiz görsel/stil yükü oluşturmadan `200 OK` veya `503 Service Unavailable` yanıtı verir.

---

## 8. Cloudflare DNS Ünlem Uyarıları, Sahiplik Doğrulamaları ve Çözümleri
 
### A. MX Kaydı Ünlemi (Mail Yönlendirme Hatası)
#### Belirti
Cloudflare DNS listesinde `fezadan.org` MX kaydının yanında sarı bir ünlem işareti görünür.
#### Neden Olur?
MX kaydının hedefi (Target) doğrudan **Proxied (Turuncu Bulut)** modundaki ana alan adı olan `fezadan.org` olarak ayarlanmıştır. Cloudflare üzerinden proxied durumdaki kayıtlardan e-posta trafiği geçişi yapılması önerilmez.
#### Çözüm
1. Cloudflare DNS ayarlarında yeni bir **A kaydı** oluşturun:
   * **Name:** `mail`
   * **IPv4 Address:** `213.238.183.121`
   * **Proxy Status:** **DNS Only** (Gri bulut olmalıdır).
2. Mevcut MX kaydınızı düzenleyin ve hedef (Target) alanını `fezadan.org` yerine **`mail.fezadan.org`** yapın.
 
### B. Çoklu Subdomain Ünlemi (Örn: `www.notlar.fezadan.org`)
#### Belirti
`www.notlar.fezadan.org` gibi derin/iç içe subdomain A veya CNAME kayıtlarının yanında sarı ünlem işareti görünür.
#### Neden Olur?
Tarayıcılar `https://www.notlar.fezadan.org` adresine girmeye çalıştığında SSL sertifikası uyumsuzluğu yaşanabilir. Varsayılan ücretsiz Cloudflare SSL sertifikası sadece tek seviyeli subdomain'leri (örn: `*.fezadan.org`) kapsar, `*.notlar.fezadan.org` düzeyindeki ikinci derece subdomain'leri kapsamaz.
#### Çözüm
Ziyaretçilerin `notlar.fezadan.org` adresine doğrudan (www olmadan) girmesi hedefleniyorsa, bu `www.notlar.fezadan.org` A kaydını **tamamen silin**. Tek seviyeli subdomain olan `notlar.fezadan.org` sorunsuz çalışacaktır.

### C. Artık Kullanılmayan Sahiplik Doğrulama Kayıtlarının (Ownership Token) Silinmesi
#### Belirti
Google Search Console veya diğer webmaster araçlarında yetkisiz/eski kullanıcıların (örn: `suatisik@proton.me`) yetkisini kaldırmak istediğinizde *"Remove unused ownership token"* uyarısı çıkması ve yetkinin tam iptal edilememesi.
#### Neden Olur?
Bu kullanıcılar geçmişte mülkiyeti doğrulamak amacıyla DNS seviyesinde bir TXT kaydı eklemişlerdir. Siz panellerden yetkiyi kaldırsanız bile DNS'teki TXT kaydı durduğu sürece Google bu kullanıcıyı yetkili kabul etmeye devam eder.
#### Çözüm (Cloudflare DNS Temizliği)
1. **Cloudflare Panelinde:** [dash.cloudflare.com](https://dash.cloudflare.com) adresine giriş yapın.
2. **DNS Kayıtları:** `fezadan.org` alan adını seçip **DNS -> Records** sekmesine gelin.
3. **Kaydı Bulma:** DNS listesindeki **TXT** türündeki kayıtları tarayın ve değeri uyarıda belirtilen token ile başlayan kaydı bulun:
   `google-site-verification=YzHPz6jCksPYrReCNL-tPbpjzglYrwm-LLBZal...`
4. **Silme İşlemi:** İlgili TXT kaydının sağındaki **Edit** (Düzenle) -> **Delete** (Sil) butonlarına basarak kaydı kaldırın.
5. **Google Paneli Onayı:** Silme işleminden 1-2 dakika sonra Search Console ekranına dönüp **"Verify Removal"** (Kaldırmayı Doğrula) butonuna basın. Yetki kalıcı olarak kaldırılacaktır.
 
---
 
## 9. Arama Motoru Optimizasyonu (SEO) Hataları ve Çözümleri
 
### A. "Identical Page Titles" ve "Identical Meta Descriptions" Uyarıları
#### Belirti
Bing Webmaster Tools veya Google Search Console panellerinde `/tr` ve `/en` sayfaları için çakışan/özdeş sayfa başlıkları veya meta açıklamaları uyarıları raporlanır.
#### Neden Olur?
Anasayfa (`app/Views/front/home.php`) veya diğer çok dilli sayfalarda meta verilerin ve JSON-LD yapılandırılmış verilerinin (Structured Data) dile göre ayrıştırılmaması durumunda gerçekleşir. Arama motoru botları her iki dili de taradığında aynı başlık/açıklama değerleriyle karşılaşır ve bunu yinelenen içerik olarak değerlendirir.
#### Çözüm
1. **Dinamik Dil Kontrolü:** Başlık ve açıklamaları statik string olarak tanımlamak yerine, `App::getLang()` ile aktif dili tespit edip dile göre farklı değerler atayın:
   ```php
   $isEn = (App::getLang() === 'EN');
   $page_title = $isEn ? 'English Title' : 'Turkish Title';
   $page_description = $isEn ? 'English Desc' : 'Turkish Desc';
   ```
2. **Canonical ve JSON-LD Eşleşmesi:** Sayfa canonical URL'si (`$page_canonical`) ile JSON-LD şemasındaki `url` alanlarının mutlaka o dile ait dizini (örn: `/tr` veya `/en`) göstermesini sağlayın:
   ```php
   $page_canonical = $siteBase . ($isEn ? '/en' : '/tr');
   // JSON-LD
   'url' => $page_canonical
   ```
   Bu sayede arama motoru botları her iki sayfa arasındaki dilsel ve canonical farklılığı net bir şekilde algılar.

### B. Arama Motoru Önbellek (Cache) Süreçleri ve Snippet Güncellenmesi
#### Durum
Kodda veya meta açıklama metninde düzeltme yapılmasına rağmen Google, DuckDuckGo veya Yandex arama sonuçlarında hala eski başlık ve açıklamaların görünmesi.
#### Neden Olur?
Arama motorları siteleri canlı taramazlar. Kendi veri merkezlerindeki önbelleğe alınmış (cached) sürümü sonuç sayfalarında gösterirler. Sitede yapılan bir değişiklik, arama motorunun botu sitenizi tekrar tarayana kadar arama sonuçlarına yansımaz.
#### Normal Güncellenme Süreleri
* **Google:** Genellikle **2 - 5 gün** içinde anasayfayı otomatik tarar.
* **Bing / DuckDuckGo:** Genellikle **3 - 7 gün** sürer.
* **Yandex:** **1 - 2 hafta** sürer (indeks dalgaları halinde güncellenir).

### C. Google Search Console Manuel İndeks Talebi (Request Indexing)
#### Amaç
Google arama sonuçlarındaki güncellemelerin veya "URL is unknown to Google" (URL Google tarafından bilinmiyor) hatası veren yeni dil sayfalarının (`/tr` veya `/en`) saatler içerisinde taranıp dizine eklenmesini sağlamak.
#### Adımlar
1. **Giriş:** [Google Search Console](https://search.google.com/search-console) paneline gidin.
2. **URL İnceleme:** En üstteki arama kutusuna incelemek istediğiniz tam adresi yazıp aratın (örn: `https://fezadan.org/tr`).
3. **Canlı URL Testi (İsteğe Bağlı):** Sağ üstteki **"Test Live URL"** butonuna basarak Google'ın sayfaya şu anda sorunsuz erişip erişemediğini test edin.
4. **Talep Gönderme:** Ekrandaki **"Request Indexing"** (Dizin Oluşturulmasını İste) bağlantısına tıklayın. 
5. Sayfa sıraya alınır ve Googlebot genellikle 1-24 saat içinde sayfayı ziyaret edip arama sonuçlarını günceller.

### D. Bing Webmaster Tools "Site Scan" ve "URL Inspection" Yönetimi
#### Amaç
Sitenin Bing ve DuckDuckGo üzerindeki SEO sağlığını test etmek, hataların giderildiğini doğrulamak ve arama sonuçlarını hızlıca güncellemek.
#### 1. Site Scan (SEO Analiz Doğrulaması)
Yapılan SEO kod düzeltmelerinin (kısa başlıklar, yinelenen açıklamalar vb.) işe yaradığını doğrulamak için yeni bir tarama başlatabilirsiniz:
1. Bing Webmaster Tools'ta sol menüden **"Site Scan"** sekmesine gidin.
2. **"Start new scan"** butonuna tıklayın.
3. **Scan Name:** `SEO Düzeltme Testi` gibi bir isim verin.
4. **Scope:** `Website` seçeneğini ve `https://fezadan.org/` adresini seçili bırakın.
5. **Gelişmiş Ayarlar:** Varsayılan ayarlarda bırakarak (Max scan depth = 4, Crawling speed = 5) **"Start Scan"** butonuna basın.
6. Tarama bittiğinde "identical titles" gibi hataların sıfıra indiğini göreceksiniz.
#### 2. URL Inspection (Arama Sonucu Güncelleme)
DuckDuckGo ve Bing arama sonuçlarındaki eski başlık metinlerini anında güncellemek için:
1. Sol menüden **"URL Inspection"** (URL İnceleme) sekmesine gidin.
2. `https://fezadan.org/tr` adresini girerek inceleme başlatın ve **"Request Indexing"** (Dizin Oluşturulmasını İste) butonuna tıklayarak işlemi tetikleyin.
3. Aynı işlemi `/en` adresi için de tekrarlayın.

---

## 14. Anonymity Check — "Map Unavailable" Hatası

### Hata Belirtisi

`anonymitycheck.fezadan.org` üzerinde tarama tamamlandıktan veya paylaşılan bir sonuç sayfası (`/resultsXXX`) açıldıktan sonra harita alanında şu mesaj görünür:

```
MAP UNAVAILABLE
No geolocation source available. Add a MaxMind GeoLite2-City database
or connect through a CDN with geo headers.
```

Ayrıca **City: Unavailable**, **Source: Cloudflare** ve **ISP / ASN: Not exposed** görünebilir.

### Neden Olur?

Controller (`AnonymityCheckController.php`) şehir, koordinat ve ASN bilgilerini Cloudflare'in origin'e ilettiği şu header'lardan okur:

| PHP `$_SERVER` anahtarı | Cloudflare header | Varsayılan durum |
|---|---|---|
| `HTTP_CF_IPCOUNTRY` | `CF-IPCountry` | **Her zaman gelir** |
| `HTTP_CF_IPCITY` | `CF-IPCity` | **Managed Transform kapalıysa gelmez** |
| `HTTP_CF_IPLATITUDE` | `CF-IPLatitude` | **Managed Transform kapalıysa gelmez** |
| `HTTP_CF_IPLONGITUDE` | `CF-IPLongitude` | **Managed Transform kapalıysa gelmez** |
| `HTTP_CF_TIMEZONE` | `CF-IPTimeZone` | **Managed Transform kapalıysa gelmez** |

Ülke kodu (`CF-IPCountry`) Cloudflare'de varsayılan olarak açık gelir; bu yüzden bayrak ve ülke adı gösterilir. Ancak şehir, koordinat ve ASN header'ları **"Add visitor location headers" Managed Transform** etkinleştirilmeden origin sunucuya iletilmez. Bu toggle kapalı olunca koordinat verisi gelmiyor ve harita çizilemiyor.

### Çözüm — Cloudflare Panelinden Managed Transform'u Aç

1. [dash.cloudflare.com](https://dash.cloudflare.com) adresinde `fezadan.org` domain'ini seç.
2. Sol menüden **Rules → Transform Rules → Managed Transforms** sekmesine git.
3. **"Add visitor location headers"** satırının toggle'ını **ON** yap.
4. Kaydet.

Bu toggle açılınca sunucuya gelen her istekte şu header'lar otomatik eklenir ve harita çalışmaya başlar:
- `CF-IPCity` → Şehir
- `CF-IPLatitude` / `CF-IPLongitude` → Koordinatlar (harita)
- `CF-IPTimeZone` → Zaman dilimi
- `CF-IPASNOrg` → ISP / ASN

> **Dikkat:** Aynı sayfadaki diğer toggle'lara dokunmayın.
> - **"Remove visitor IP headers"** → Kullanıcı IP'sini gizler, anonymity check çalışmaz.
> - **"Add True-Client-IP header"** → Bu proje için gerekli değil.

### Eski Cached Raporlar

Toggle açıldıktan sonra **yeni** taramalar doğru koordinatla kaydedilecektir. Daha önce koordinatsız kaydedilen `/resultsXXX` paylaşım URL'leri, sayfa açıldığında `/geo-lookup` endpoint'ine anlık istek atarak koordinat almayı dener; bu fallback mekanizması `checkGeoLocation()` JS fonksiyonu üzerinden çalışır.

### Fallback Hiyerarşisi (kod tarafı)

Sunucu tarafında koordinat şu sırayla aranır:

1. **CF header'ları** (`CF-IPLatitude` / `CF-IPLongitude`) — Managed Transform açıksa gelir.
2. **Local MMDB** (`storage/anonymity/GeoLite2-City.mmdb` veya `dbip-city-lite.mmdb`) — Dosya varsa okunur.
3. **ip-api.com** (ücretsiz, 45 istek/dk) — Dış API, sadece kullanıcı tetiklemeli endpoint'lerde çağrılır.

Üç kaynak da koordinat döndüremezse "Map Unavailable" mesajı gösterilir.
