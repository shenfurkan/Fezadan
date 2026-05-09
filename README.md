<p align="center">
  <img src="public_html/cdn/logo-light.png" alt="Fezadan Logo" width="240"/>
</p>

<p align="center">
  <b>Gizlilik Odaklı, Kendi Kendine Yeten Yayın Platformu</b>
</p>

<p align="center">
  <img src="https://img.shields.io/badge/PHP-8.0%2B-777BB4?logo=php&logoColor=white" alt="PHP"/>
  <img src="https://img.shields.io/badge/MySQL-5.7%2B-4479A1?logo=mysql&logoColor=white" alt="MySQL"/>
  <img src="https://img.shields.io/badge/Cloudflare%20R2-F38020?logo=cloudflare&logoColor=white" alt="Cloudflare R2"/>
  <img src="https://img.shields.io/badge/Docker-Ready-2496ED?logo=docker&logoColor=white" alt="Docker"/>
  <img src="https://img.shields.io/badge/License-GPL--3.0-A42E2B?logo=gnu&logoColor=white" alt="GPL-3.0"/>
</p>

---

## Bu Proje Nedir?

Fezadan, dijital yayın yapmak isteyen kolektifler, bağımsız yazarlar, sanat kurumları ve küçük ölçekli medya projeleri için tasarlanmış **gizlilik odaklı, kendi kendine yeten bir yayın platformudur**. WordPress, Medium veya benzeri hazır servislere olan bağımlılığı kırmak; izleme (tracking), veri madenciliği ve güvenlik açıklarından arınmış bir alternatif sunmak için geliştirilmiştir.

Sistem, makale yönetiminden günlük sanat eseri otomasyonuna, notlar arşivinden yönetim paneline kadar tam bir içerik ekosistemini tek bir çekirdekte birleştirir. Tüm veriler sizin sunucunuzda kalır, hiçbir üçüncü taraf analitik aracı ziyaretçilerinizi profillemez ve medya dosyalarınız Cloudflare R2 üzerinden hızlı ve güvenli şekilde servis edilir.

### Kimler İçin?

- Verilerinin tamamına sahip olmak isteyen bağımsız yayıncılar
- Harici plugin ve tema ekosistemine güvenmek istemeyen geliştiriciler
- Ziyaretçi gizliliğini öncelik olarak gören projeler
- Sanatsal içerik üreten ve otomasyon isteyen kolektifler
- Minimal, anlaşılır ve tam denetimde olunan bir kod tabanı arayan ekipler

---

## 1. Mimari Felsefe ve Dışa Bağımlılık

Projenin temel felsefesi, modern web uygulamalarındaki aşırı dışa bağımlılığı ve kullanıcı verilerinin izlenme (tracking) riskini en aza indiren **kendi kendine yeten (self-sufficient)** bir yapı sunmaktır:

* **Gizlilik ve İzlenme Karşıtlığı:** Web sayfasının okunma ve ziyaretçi istatistikleri için Google Analytics gibi üçüncü taraf izleyiciler kullanılmaz. Veriler doğrudan sistemin yerel veritabanında işlenir. Bu sayede son kullanıcı hareketleri profillenmez ve tam gizlilik sağlanır.
* **Lokal Varlık (Asset) Dağıtımı:** CSS framework'leri, JavaScript kütüphaneleri ve yazı tipleri (Fonts) harici public CDN sunucularından çekilmek yerine tamamen projenin yerel kaynaklarından sunulur. Bu mimari, dış sunucularda yaşanabilecek kesintilerin sistemi etkilemesini önlediği gibi, ziyaretçilerin IP adreslerinin istek esnasında üçüncü parti kurumlarla paylaşılmasını da engeller.
* **Minimal Core Yapısı:** Sistem, ihtiyaç duyulmayan özelliklerle şişirilmiş ağır frameworkler barındırmaz. Sisteme özel olarak yazılmış routing (`App.php`) ve veritabanı sürücüleri (`Db.php`) kullanılarak kod tabanında tam denetim ve yüksek performans hedeflenir.

### 1.1 Neden Fezadan Çekirdeğini Kullanmalısınız?

Fezadan, WordPress gibi popüler CMS platformlarına güvenlik, performans ve gizlilik açısından güçlü bir alternatif sunmak amacıyla geliştirilmiştir. Fezadan çekirdeğini tercih etmeniz için temel nedenler şunlardır:

* **Sürekli Saldırı Hedefi Değil:** WordPress'in küresel kullanım oranı %40'ın üzerindedir ve bu popülerlik, onu otomatik bot saldırıları, SQL injection, XSS ve plugin zafiyetleri için birincil hedef haline getirir. Her gün binlerce WordPress sitesi, bilinen zafiyetler kullanılarak hacklenmektedir. Fezadan, özel bir mimari ile bu otomatik saldırı vektörlerinden tamamen izole edilmiştir.
* **Plugin ve Tema Zafiyeti Riski Yok:** WordPress ekosistemi 60.000+ plugin ve binlerce tema içerir. Bu eklentilerin çoğu düzenli güncellenmez ve kritik güvenlik açıkları barındırır. Bir pluginin zafiyeti, tüm siteyi tehlikeye atabilir. Fezadan, tüm core işlevselliği kendisi barındırır ve dış plugin bağımlılığından kaçınır.
* **Hafif ve Optimize Yapı:** WordPress, blog odaklı bir yapıdan gelir ve içerik yönetimi dışında pek çok gereksiz özellik taşır. Bu durum, veritabanı şişkinliğine, yavaş sayfa yükleme sürelerine ve gereksiz kaynak tüketimine yol açar. Fezadan, minimalist bir core ile sadece ihtiyaç duyulan özellikleri sunar.
* **Veri İzleme ve Telemetri Yok:** WordPress core ve birçok plugin, varsayılan olarak kullanım istatistiklerini ve telemetri verilerini toplar. Fezadan, gizlilik odaklı bir tasarım ile hiçbir telemetri verisi toplamaz ve kullanıcı verilerini izlemez.
* **Tam Özelleştirme Kontrolü:** WordPress'in hook sistemi ve template yapısı, derin özelleştirmelerde karmaşıklık yaratır. Fezadan'ın özel MVC mimarisi, projeye özel ihtiyaçlara tam uyum sağlar, kod tabanında tam kontrol sunar ve bakım maliyetini düşürür.
* **Kendi Kendine Yeten Mimari:** WordPress gibi harici bağımlılıklarla değil, tamamen kendi çekirdeğiyle çalışır. Bu sayede güncelleme döngülerine, plugin uyumsuzluklarına ve sürüm çakışmalarına takılmadan uzun vadeli istikrar sağlar.

### 1.2 Neden Google Fonts Kullanılmadı?

Google Fonts kullanımı, bu projede kasıtlı olarak reddedilmiştir ve bunun nedenleri şunlardır:

* **IP Adresi İzleme:** Google Fonts'u kullanmak için Google sunucularına istek gönderilir. Bu istekler, ziyaretçinin IP adresini Google'a ifşa eder ve Google'ın kullanıcı profili oluşturmasına katkıda bulunur.
* **Performans ve Bağımlılık:** Harici CDN'lerden font yüklemek, site yüklenme hızını etkileyebilir. Google sunucularında yaşanabilecek kesintiler veya yavaşlamalar, kullanıcı deneyimini doğrudan etkiler.
* **Lokal Kontrol:** Proje, tüm fontları (Syne, EB Garamond, Space Grotesk, Bebas Neue, JetBrains Mono) WOFF2 formatında yerel olarak barındırır. Bu sayede tam kontrol, gizlilik ve performans garantisi sağlanır. Fontlar Latin Extended karakter setini destekler ve Türkçe karakterleri sorunsuz görüntüler.

---

## 2. Güvenlik Sistemi

Yetkilendirme ve veri bütünlüğü, siber saldırılara karşı aşağıdaki katmanlarla korunur:

* **İzole Panel Rotası:** Standart bot saldırılarını ve otomatik zafiyet taramalarını engellemek için yaygın panel uzantıları kullanılmaz. Yönetim arayüzüne doğrudan izole edilmiş `/yonetim` rotası üzerinden erişilir.
* **Brute Force Koruması:** Yönetim paneline yapılan başarısız giriş denemelerinde IP adresleri `sha256` algoritması ile hashlenerek kaydedilir. Art arda yapılan 3 başarısız denemede ilgili IP adresi 3 saat süreyle engellenir.
* **Session Fixation Koruması:** Oturum açma işleminin başarıyla tamamlanmasının ardından mevcut oturum kimliği (`session_id`) sıfırlanır ve yenilenir.
* **CSRF (Cross-Site Request Forgery) Koruması:** Veri manipülasyonu içeren tüm POST isteklerinde, `Csrf.php` sınıfı aracılığıyla token doğrulaması zorunlu tutulur. Tokenlar `random_bytes(32)` ile üretilir ve `hash_equals()` ile güvenli şekilde doğrulanır.
* **Gelişmiş Hata Yönetimi:** Üretim (production) ortamında, sistem topolojisini ele verebilecek PHP hata detayları tamamen gizlenir. İstekler durumuna göre sistemin kendi oluşturduğu `404` veya `500` HTTP hata sayfaları ile karşılanır.
* **İşlem Kayıtları:** Sistem içerisinde gerçekleştirilen veri ekleme, medya yükleme ve oluşan arka plan hataları, `AdminLog.php` aracılığıyla kaydedilir.
* **PDO Güvenliği:** Veritabanı sorgularında `PDO::ATTR_EMULATE_PREPARES` false olarak ayarlanır, bu sayede gerçek prepared statement kullanılır ve SQL injection riski tamamen ortadan kaldırılır.
* **Input Validation:** Tüm kullanıcı girdileri `filter_var()` ve `FILTER_SANITIZE_URL` gibi PHP filtreleri ile temizlenir. URL parsing işlemlerinde güvenlik önlemleri alınır.
* **HTTP Headers Güvenliği:** Güvenlik header'ları (X-Content-Type-Options, X-Frame-Options vb.) uygulanır ve Content-Type header'ları zorlanır.

---

## 3. Medya Yönetimi ve Cloudflare R2

Tüm medya barındırma ve servis işlemleri Cloudflare R2 tabanlı bir bulut mimarisi üzerinden yönetilir:

### 3.1 Neden CDN Kullanımı?

Fezadan, medya dosyalarını doğrudan web sunucusundan değil, Cloudflare R2 üzerinden CDN (Content Delivery Network) ile sunmak üzere tasarlanmıştır. Bu mimari seçimin temel nedenleri şunlardır:

* **Sunucu Kaynak Tasarrufu:** Medya dosyaları web sunucusundan sunulduğunda, her istek sunucunun CPU, bellek ve bant genişliği kaynaklarını tüketir. Yüksek trafikli sitelerde bu durum sunucunun aşırı yüklenmesine ve yavaşlamasına neden olur. CDN ile medya dosyaları edge sunuculardan sunulur, ana sunucu sadece dinamik içerik üretir.
* **Global Dağıtım ve Hız:** Cloudflare'ın küresel edge ağı, medya dosyalarını dünyanın dört bir yanındaki kullanıcılara en yakın noktadan sunar. Bu, sayfa yükleme sürelerini dramatik olarak kısaltır ve kullanıcı deneyimini iyileştirir. Doğrudan sunucudan servis etmek, coğrafi mesafe nedeniyle gecikmelere yol açar.
* **Ölçeklenebilirlik:** CDN, trafik artışlarında otomatik olarak ölçeklenir. Binlerce eşzamanlı medya isteği geldiğinde bile performans düşüşü yaşanmaz. Doğrudan sunucu tabanlı çözümlerde, trafik artışı sunucu çökmesine veya yavaşlamasına neden olabilir.
* **Maliyet Verimliliği:** Cloudflare R2, egress ücreti almaz (AWS S3'nin aksine). Bu, büyük medya dosyalarının yüksek trafikle sunulması durumunda maliyet avantajı sağlar. Ayrıca sunucu bant genişliği ihtiyacı azaldığı için daha küçük ve ucuz sunucular kullanılabilir.
* **Yüksek Mevcudiyet (High Availability):** CDN sağlayıcıları %99.9+ uptime garantisi sunar. Tek bir sunucunun çökmesi durumunda bile medya dosyaları edge sunuculardan sunulmaya devam eder. Doğrudan sunucu çözümünde, sunucu arızası tüm medya erişimini durdurabilir.
* **DDoS Koruması:** Cloudflare, DDoS saldırılarına karşı güçlü koruma sağlar. Medya dosyaları CDN üzerinden sunulduğunda, saldırılar edge seviyesinde engellenir ve ana sunucuya ulaşmaz.

### 3.2 R2 Entegrasyonu Detayları

1. **Güvenlik İzolasyonu:** Yüklenen medyaların yerel sunucu yerine doğrudan bulut üzerinde (R2) tutulması, zararlı script dosyalarının (malware) yerel sunucuya sızdırılması ve çalıştırılması riskini fiziksel olarak ortadan kaldırır.
2. **Sunucu Yükü Dağıtımı:** Medya dosyaları Cloudflare Edge ağı üzerinden servis edilir. Bu sayede yerel sunucunun bant genişliği ve bellek tüketimi dramatik ölçüde düşürülür.
3. **Format Optimizasyonu:** Yüklenen JPEG ve PNG formatındaki görseller, arka planda otomatik olarak yeni nesil WebP formatına çevrilir. Dosyalar R2 bucket'ına aktarıldıktan sonra sunucudaki tüm geçici kalıntılar temizlenir.
4. **AWS S3 SDK Entegrasyonu:** `R2Storage.php` sınıfı, AWS SDK for PHP (`aws/aws-sdk-php`) kullanılarak S3 uyumlu API üzerinden R2 ile iletişim kurar. Singleton pattern kullanılarak tek bir bağlantı örneği yönetilir.
5. **PDF Yönetimi:** Notlar modülü için PDF dosyaları R2'ye yüklenir, stream edilebilir ve indirilebilir. `streamView()` methodu HTTP Range header'larını destekleyerek partially content (206) yanıtları verebilir.
6. **Cache Stratejisi:** Statik medya dosyaları için `Cache-Control: public, max-age=31536000, immutable` header'ı uygulanarak一年 boyunca tarayıcı önbelleğinde tutulur.
7. **Path Validation:** Dosya yollarında `..` ve diğer path traversal girişimleri engellenir. Sadece izinli uzantılar (webp, jpeg, png, gif, pdf) ve belirli dizinlere erişime izin verilir.

---

## 4. Otonom İçerik Modülleri

Sistem, harici API'ler aracılığıyla kendi sanatsal içeriklerini üretebilecek bir yapılandırmaya sahiptir:

- **Müze API Entegrasyonları:** The Met, Art Institute of Chicago ve Cleveland Museum of Art API'lerinden günlük periyotlarla yeni sanatsal veriler çekilir. `ArtProviderMet.php`, `ArtProviderChicago.php`, ve `ArtProviderCleveland.php` sınıfları her bir müze API'si ile iletişim kurar.
- **Çeviri Mekanizması:** Çekilen yabancı dildeki metinler DeepL API ile otomatik olarak Türkçe'ye çevrilir. DeepL servisinin limitlerinin dolması veya yanıt vermemesi durumunda sistem kesintiye uğramaz; yedek mekanizma (failover) devreye girerek çeviriyi Gemini AI API üzerinden tamamlar.
- **Daily Artwork Sistemi:** `DailyArtwork.php` sınıfı, günlük sanat eserlerini yönetir. Her gün için unique slug oluşturur, Türkçe karakterleri URL-friendly formata çevirir, ve veritabanında saklar. Schema migrations otomatik olarak gerçekleştirilir.
- **Cron Job Entegrasyonu:** `cron/` dizinindeki scriptler, günlük içerik çekme işlemlerini otomatikleştirir. Sistem, her gün yeni bir sanat eseri çekip veritabanına kaydeder.
- **Wikipedia Entegrasyonu:** Sanat eserleri için Wikipedia'dan ek bilgi çekilebilir. `description_source` field'ı içeriğin kaynağını (wikipedia, museum, template, manual) belirler.

---

## 5. Teknik Mimari ve Teknoloji Yığını

### 5.1 Core Teknolojiler

* **PHP:** Sistem PHP ile yazılmıştır. Native PHP kullanılarak framework bağımlılığından kaçınılmıştır.
* **MySQL/MariaDB:** Veritabanı olarak MySQL veya MariaDB kullanılır. `utf8mb4` charset ile tam Unicode desteği sağlanır.
* **AWS SDK for PHP:** Cloudflare R2 ile S3 uyumlu API üzerinden iletişim için `aws/aws-sdk-php` paketi kullanılır (tek external dependency).
* **PDO:** Veritabanı işlemleri için PHP PDO kullanılır. Prepared statements ile SQL injection korunması sağlanır.

### 5.2 MVC Mimarisi

Sistem, özel bir MVC (Model-View-Controller) mimarisi kullanır:

* **App.php:** Merkezi router ve dispatcher. URL parsing, controller loading, method dispatching işlemlerini yönetir. Subdomain routing (notlar.fezadan.org) desteği vardır.
* **Controllers:** HTTP isteklerini karşılar, iş mantığını uygular ve view'ları render eder. Örnek: `HomeController`, `GaleriController`, `YonetimController`.
* **Models:** Veritabanı işlemlerini kapsüller. `Post.php`, `User.php` gibi modeller veri erişim katmanını sağlar.
* **Views:** HTML/PHP şablonları. `app/Views/` dizininde organize edilir. Frontend ve admin view'ları ayrılmıştır.
* **Core Classes:** `Db.php` (singleton PDO connection), `Csrf.php` (CSRF protection), `Auth.php` (authentication), `R2Storage.php` (cloud storage), `DailyArtwork.php` (artwork management).

### 5.3 Routing Sistemi

* **URL Parsing:** `App::parseUrl()` methodu URL'yi parse eder ve `filter_var()` ile sanitization yapar.
* **Controller Autoloading:** URL'deki ilk segment controller adını belirler. CamelCase ve kebab-case desteği vardır.
* **Method Dispatching:** URL'deki ikinci segment method adını belirler. Mevcut değilse 404 döner.
* **Subdomain Routing:** `notlar.` subdomain'i için özel routing uygulanır. PDF viewing ve downloading için özel endpoint'ler vardır.

### 5.4 Dizin Yapısı ve Kurulum

```text
Fezadan/
├── app/
│   ├── Config/        # Ayar ve veritabanı bağlantı dosyaları
│   ├── Controllers/   # HTTP request yöneticileri
│   ├── Core/          # Temel iş mantığı sınıfları (Db, Upload, Csrf vb.)
│   ├── Models/        # Veritabanı modelleri
│   ├── Services/      # Hizmet sınıfları (ImageHandler, WordParser vb.)
│   └── Views/         # HTML/PHP arayüz şablonları
├── public_html/       # Kök web dizini (Document Root)
│   ├── assets/        # Derlenmiş CSS ve JS dosyaları
│   │   ├── css/       # Stylesheet dosyaları (fonts.css, style.css vb.)
│   │   ├── fonts/     # Yerel WOFF2 font dosyaları
│   │   └── js/        # JavaScript dosyaları
│   ├── cdn/           # Lokal statik medya (logolar, faviconlar)
│   └── index.php      # Gelen isteklerin karşılandığı yönlendirici
├── cron/              # Otomatik görev scriptleri
├── migrations/        # Veritabanı migration dosyaları
├── scripts/           # Yardımcı scriptler
├── vendor/            # Composer dependencies (AWS SDK)
├── .env               # Ortam değişkenleri (gitignore'da)
├── .env.example       # Ortam değişkenleri taslağı
├── composer.json      # PHP dependency yönetimi
├── Dockerfile         # Docker container yapılandırması
├── docker-compose.yml # Docker Compose yapılandırması
└── README.md
```

### Gereksinimler

- PHP 8.0 veya üzeri
- MySQL 5.7+ veya MariaDB 10.2+
- Composer
- Apache veya Nginx
- Cloudflare R2 hesabı (medya depolama için)
- DeepL API Key (opsiyonel - çeviri için)
- Gemini API Key (opsiyonel - yedek çeviri için)

### Kurulum

#### Yöntem 1: Docker (Önerilen)

```bash
# Projeyi klonlayın
git clone https://github.com/shenfurkan/Fezadan.git
cd Fezadan

# .env dosyasını oluşturun ve düzenleyin
cp .env.example .env

# Docker ile başlatın
docker-compose up -d
```

#### Yöntem 2: Manuel Kurulum

```bash
# Projeyi klonlayın
git clone https://github.com/shenfurkan/Fezadan.git
cd Fezadan

# Dependencies yükleyin
composer install

# .env dosyasını oluşturun ve düzenleyin
cp .env.example .env
nano .env  # veya düzenleyiciniz
```

**.env dosyasında gerekli ayarları yapın:**
- Veritabanı bağlantı bilgileri
- Cloudflare R2 API anahtarları
- API anahtarları (DeepL, Gemini)
- Güvenlik anahtarları

**Web sunucusu yapılandırması:**
- Document root'u `public_html/` olarak ayarlayın
- PHP-FPM veya Apache mod_php kullanın
- URL rewriting için `.htaccess` (Apache) veya nginx config ayarlarını yapın

**Veritabanı:**
- Boş bir veritabanı oluşturun
- Sistem ilk çalıştırmada gerekli tabloları otomatik oluşturur

**Cron Job (Opsiyonel):**
Günlük sanat eseri çekme için crontab'a ekleyin:
```bash
0 2 * * * php /path/to/Fezadan/cron/daily-artwork.php
```

---

## 6. Frontend Mimari

### 6.1 Font Stratejisi

Proje, tamamen yerel fontlar kullanır ve harici font CDN'lerine bağımlı değildir:

* **Syne:** Başlıklar ve logo için kullanılır. Regular, 500, 600, 700, 800 weight'leri mevcuttur.
* **EB Garamond:** Ana metin (body text) için kullanılır. Regular, italic, 500-800 weight'leri mevcuttur. Klasik ve okunaklı bir serif font'tur.
* **Space Grotesk:** UI elementleri ve modern tipografi için kullanılır. 300, regular, 500, 600, 700 weight'leri mevcuttur.
* **Bebas Neue:** Büyük sayılar ve grid düzenleri için kullanılır.
* **JetBrains Mono:** Teknik içerikler ve admin paneli için kullanılır. 100-800 arası tüm weight'ler mevcuttur.

Tüm fontlar WOFF2 formatında, Latin Extended karakter seti ile (Türkçe karakter desteği) yerel olarak barındırılır.

### 6.2 CSS ve JavaScript

* **CSS:** Custom CSS kullanılır, framework bağımlılığı yoktur. `style.css` ana stil dosyasıdır.
* **JavaScript:** Vanilla JavaScript kullanılır. `main.js` genel frontend işlevselliğini, `admin.js` yönetim paneli işlevselliğini sağlar.
* **PDF.js:** Notlar modülü için PDF görüntüleme amacıyla PDF.js kütüphanesi yerel olarak barındırılır.

### 6.3 Responsive Tasarım

Sistem, mobil-first yaklaşım ile responsive tasarıma sahiptir. Tüm view'lar farklı ekran boyutlarına uyumlu olarak tasarlanmıştır.

---

## 7. Performans Optimizasyonu

* **Singleton Pattern:** `Db.php` ve `R2Storage.php` sınıfları singleton pattern kullanır. Her istekte tek bir veritabanı bağlantısı ve tek bir R2 client örneği oluşturulur.
* **Font Display Swap:** Tüm fontlar `font-display: swap` ile yüklenir, bu sayede metinler hemen görünür olur ve fontlar arka planda yüklenir.
* **Cache Headers:** Statik varlıklar için agresif cache stratejisi uygulanır.
* **Lazy Loading:** Görseller lazy loading tekniği ile yüklenir.
* **Minified Assets:** CSS ve JS dosyaları minified olarak sunulur.
* **HTTP/2:** Sunucu HTTP/2 destekler, bu sayede parallel resource loading mümkündür.

---

## 8. Docker ve Deployment

Proje, Docker ile containerize edilebilir:

* **Dockerfile:** PHP 8.x tabanlı Docker image. Gerekli PHP extension'ları (pdo_mysql, mbstring, curl vb.) yüklenir.
* **docker-compose.yml:** MySQL container ve PHP container'ı birlikte çalıştırır.
* **docker-entrypoint.sh:** Container başlangıç script'i.

Deployment için:
1. Docker image'ı build edin: `docker build -t fezadan .`
2. Docker Compose ile çalıştırın: `docker-compose up -d`
3. Alternatif olarak, traditional VPS deployment da desteklenir (Apache/Nginx + PHP-FPM).

---

## 9. Güvenlik Best Practices

* **Environment Variables:** Hassas bilgiler (API keys, database credentials) `.env` dosyasında saklanır ve git'e dahil edilmez.
* **File Permissions:** Sensitive dosyaların izinleri kısıtlanır. `.env` dosyası web erişimine kapalıdır.
* **Error Handling:** Production ortamında PHP hataları gizlenir, custom error sayfaları gösterilir.
* **Input Sanitization:** Tüm kullanıcı girdileri temizlenir ve validate edilir.
* **Output Escaping:** XSS önleme için tüm çıktılar `htmlspecialchars()` ile escape edilir.
* **Secure Session:** Session cookie'leri `HttpOnly` ve `Secure` flag'leri ile korunur.

---

## 10. Geliştiriciler

* **Furkan Şen:** Projenin mimari tasarım aşamaları, MVC Core yapısının oluşturulması, güvenlik katmanlarının tasarımı, Cloudflare R2 entegrasyonu, yapay zeka (Gemini/DeepL) altyapısının kurulması ve otonom sistem algoritmalarının kodlanması.
* **Suat Işık:** Projenin Notlar modülü altyapısı, frontend hata düzeltmeleri ve sistem genelindeki operasyonel eklentilerin tamamlanması.


## 11. Lisans

Bu proje, Fezadan Collective tarafından geliştirilmiş ve **GNU General Public License v3.0 (GPL-3.0)** lisansı altında açık kaynak olarak yayınlanmıştır.

Proje tamamen açık kaynaktır. Kodları inceleyebilir, değiştirebilir, dağıtabilir ve geliştirebilirsiniz. GPL-3.0 lisansı, tüm türev çalışmaların da aynı lisans altında yayınlanmasını gerektirir.
