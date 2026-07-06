<p align="center">
  <img src="public_html/cdn/logo-light.png" alt="Fezadan Logo" width="240"/>
</p>

<p align="center">
  <b>PHP MVC Tabanlı İçerik Yönetim Sistemi</b>
</p>

<p align="center">
  <img src="https://img.shields.io/badge/PHP-8.0%2B-777BB4?logo=php&logoColor=white" alt="PHP"/>
  <img src="https://img.shields.io/badge/MariaDB-10.3%2B-003545?logo=mariadb&logoColor=white" alt="MariaDB"/>
  <img src="https://img.shields.io/badge/Tailwind%20CSS-v4-06B6D4?logo=tailwindcss&logoColor=white" alt="Tailwind"/>
  <img src="https://img.shields.io/badge/Cloudflare%20R2-F38020?logo=cloudflare&logoColor=white" alt="Cloudflare R2"/>
  <img src="https://img.shields.io/badge/License-GPL--3.0-A42E2B?logo=gnu&logoColor=white" alt="GPL-3.0"/>
</p>

---

## Bu Proje Nedir?

Fezadan, içerik yönetimi, medya depolama optimizasyonu, sanat eseri otomasyonu ve belge (PDF) arşivleme gibi işlevleri sunan, PHP ile geliştirilmiş özel bir MVC uygulamasıdır. Proje, genel kullanıma yönelik geniş kapsamlı CMS çözümlerinin aksine, belirli yayın senaryoları için optimize edilmiş, harici eklentilere ihtiyaç duymayan ve tüm temel işlevleri kendi çekirdeğinde (core) barındıran bir mimari sunar.

Veri gizliliği prensipleri doğrultusunda sistem, ziyaretçi profilleme araçları (örneğin Google Analytics) barındırmaz. UI bileşenleri, JavaScript kütüphaneleri ve font dosyaları dış CDN sunucuları yerine yerel olarak (`public_html/` üzerinden) istemciye iletilir. Bu sayede, ziyaretçilerin IP adreslerinin istek esnasında üçüncü taraf hizmetlerle paylaşılması engellenir.

## Mimari ve Teknolojik Yığın

Sistem, üçüncü taraf bir PHP framework'ü (Laravel, Symfony vb.) yerine projeye özel olarak yazılmış bir Core mimarisi üzerinden çalışır. Bu tercih, uygulamanın yalnızca ihtiyaç duyduğu işlemleri yürütmesini sağlayarak performans artışı ve sistem üzerinde tam kontrol hedefler.

### Backend ve Çekirdek (Core) Sınıflar
- **Dil ve Veritabanı:** Proje **PHP 8.0+** üzerinde koşar ve veri depolama için **MariaDB 10.3+ / MySQL 5.7+** kullanır.
- **Routing ve Request Yaşam Döngüsü (`App.php`):** Sisteme gelen her HTTP isteği `public_html/index.php` üzerinden karşılanıp `app/Core/App.php` router sınıfına aktarılır. Rotalar dinamik olarak URL parse edilerek controller methodlarına yönlendirilir. İzin verilen tüm endpoint'ler `App::ROUTABLE_ACTIONS` dizisiyle korunur.
- **Veritabanı Katmanı (`Db.php`):** Her istekte tek bir PDO bağlantısı oluşturulur (Singleton Pattern). Performans ve SQL Injection güvenliği için emulate prepares kapatılmış (`PDO::ATTR_EMULATE_PREPARES = false`) gerçek prepared statement'lar kullanılır.
- **Medya Yönetimi (`Upload.php` ve `R2Storage.php`):** Yüklenen görseller otomatik olarak yeni nesil **WebP** formatına dönüştürülür ve yerel disk yerine **Cloudflare R2** bulut depolama sunucularına (`aws/aws-sdk-php` aracılığıyla S3 API kullanılarak) aktarılır. Böylece sunucu diski gereksiz yere şişmez ve içerikler Cloudflare uç ağlarından (edge) hızlıca dağıtılır.
- **API Entegrasyonları ve Otomasyon:** The Met, Art Institute of Chicago gibi küresel müzelerin açık veri API'lerinden zamanlanmış cron görevleriyle (Cron Jobs) sanat eserleri çekilir, meta verileri işlenir ve sisteme makale olarak otomatik entegre edilir.

### Frontend ve Kullanıcı Arayüzü
- **Stil Yönetimi (Tailwind v4):** Tasarım sistemi **Tailwind CSS v4** ile kurgulanmıştır. Tailwind doğrudan CLI üzerinden derlenir (herhangi bir yapılandırma dosyası veya PostCSS bağımlılığı olmadan). Çalışma anında `input.css` dosyasındaki kurallar okunarak `style.css` adıyla üretim odaklı olarak minifiye edilir.
- **JavaScript ve DOM Etkileşimi:** Ağır frontend frameworkleri (React, Vue vb.) yerine tamamen Vanilla JS kullanılır. Zengin metin editörleri (Summernote) ve diğer UI kütüphaneleri doğrudan proje dizininden sunulur.
- **Tipografi:** WOFF2 formatındaki yerel fontlar (Syne, EB Garamond, Space Grotesk vb.) kullanılır. Bu fontlar `font-display: swap` ile asenkron yüklenerek sayfa oluşturma hızı optimize edilir.

## Güvenlik Altyapısı

Proje, OWASP standartları ve modern güvenlik yönergeleri dikkate alınarak tasarlanmıştır:

1. **İzole Yönetim Paneli:** `/admin`, `/wp-admin` gibi genel geçer adresler bilerek 404 sayfalarına yönlendirilir. Yönetim paneli rotaları tamamen sistemin kendisine özgü farklı bir endpoint altındadır.
2. **CSP ve Nonce Sistemi:** Sistem sıkı bir Content Security Policy (CSP) uygular. Sayfa içinde (inline) çalışması gereken zorunlu betikler için `public_html/index.php` üzerinde rastgele bir `nonce` değeri üretilir ve sadece bu değeri taşıyan betiklerin tarayıcı tarafından çalıştırılmasına izin verilir.
3. **Korumalı Dosya Yolları:** Root dizindeki hassas dosyalar (`app/`, `vendor/`, `.env`) web sunucusunun okuyamayacağı şekilde Apache Document Root (`public_html/`) dışarısında bırakılmıştır.
4. **Form ve Oturum Güvenliği:**
   - Kimlik doğrulama süreçleri saltlanmış Bcrypt algoritmalarıyla yürütülür.
   - Brute-force saldırılarını önlemek için, yönetim paneline yapılan başarısız giriş denemeleri IP adresleri üzerinden (SHA-256 ile hashlenerek) takip edilir ve belirlenen limiti aşan adresler geçici süreyle bloklanır.
   - Veri bütünlüğünü korumak adına tüm mutatif POST işlemlerinde `Csrf.php` tarafından üretilen token'ların doğrulanması zorunludur.

## Kurulum ve Yerel Geliştirme (Local Development)

Projeyi bilgisayarınızda çalıştırmak için Docker kullanmanız tavsiye edilir. Docker ortamı tüm sistem bileşenlerini ve bağımlılıkları tek bir komutla izole şekilde hazırlar.

### Ön Gereksinimler
- PHP 8.0+
- Composer
- Node.js & npm (Tailwind CSS derlemesi ve Playwright e2e testleri için)
- Docker & Docker Compose (Önerilen)

### Adım Adım Kurulum

1. **Klonlama ve Bağımlılıkların Yüklenmesi:**
   Terminali açın ve projeyi yerel ortamınıza çekin:
   ```bash
   git clone https://github.com/shenfurkan/Fezadan.git
   cd Fezadan
   
   composer install
   npm install
   ```

2. **Çevre Değişkenleri (Environment Config):**
   ```bash
   cp .env.example .env
   ```
   Docker kullanıyorsanız `.env` dosyasındaki varsayılan değerleri değiştirmenize gerek yoktur. Manuel sunucu kurulumlarında bu dosya içine veritabanı, R2 API anahtarları ve URL bilgilerinizi girmelisiniz.

3. **Docker Servislerinin Başlatılması:**
   ```bash
   docker-compose up -d --build
   ```
   Bu komut aşağıdaki lokal servisleri ayağa kaldıracaktır:
   - **PHP/Apache Web Sunucusu:** `http://localhost:8080`
   - **MariaDB Veritabanı:** `localhost:3306`
   - **phpMyAdmin (Veritabanı Yönetimi):** `http://localhost:8000` (Kullanıcı: `root`, Şifre: `root`)

4. **Veritabanı Hazırlığı (Migration):**
   Uygulamanın şema dosyalarını veritabanına yazdırması ve gerekli tabloları kurması için:
   ```bash
   php scripts/migrate-db.php
   ```

5. **Tailwind CSS İzleme Modu:**
   Stil değişikliklerinin anında tarayıcıya yansıması için Tailwind CLI'ı watch modunda başlatın:
   ```bash
   npm run dev
   ```

## Sık Kullanılan Komutlar ve Araçlar

Proje kök dizinindeki betikler ve NPM komutları hayat kurtarıcıdır:

| Komut | Açıklama |
|-------|----------|
| `npm run dev` | Tailwind CSS'i izleme modunda başlatır. `input.css` dosyasındaki değişiklikleri dinler ve `public_html/assets/css/style.css` adresine derler. |
| `npm run build` | CSS dosyalarını üretim (production) için boşluksuz (minified) ve optimize edilmiş bir biçimde derler. |
| `php scripts/migrate-db.php` | Veritabanı üzerindeki eksik tabloları ve kolonları kontrol edip otomatik olarak oluşturur (Self-healing schema migration). |
| `php tests/run.php` | Sistem bileşenlerinin beklenen değerleri üretip üretmediğini kontrol eden özel yazılmış PHP test takımlarını çalıştırır. |
| `npm run test:e2e` (veya `npm test`) | Playwright framework'ü üzerinden uçtan uca (E2E) UI testlerini tarayıcı (Chromium) ortamında simüle ederek koşturur. Çıktılar `playwright-report/` klasörüne yazılır. |
| `npm run preflight` | Sunucuya kod göndermeden önce (deploy öncesi); kodların testlerini koşturan, derleme adımlarını yapan ve veritabanını güncelleyen tam kapsamlı operasyon komutudur. |
| `php cron/generate-sitemap.php --force` | İçerik değişikliklerinden bağımsız olarak, zorunlu sitemap.xml güncellemesini gerçekleştirir. |

## Dizin ve Dosya Yapısı Açıklamaları

Proje organizasyonu modülerliği ve güvenlik izolasyonunu sağlamak için yapılandırılmıştır:

- `app/`: MVC yapısının çekirdeğini barındırır.
  - `Config/`: Sistem yapılandırması ve `.env` analizör sınıfları.
  - `Controllers/`: HTTP isteklerini alıp uygun Model ve View'lara yönlendiren denetleyiciler (Örn: `ArticlesController.php`, `AdminController.php`).
  - `Core/`: Framework bağımsız temel işlevleri sağlayan kilit dosyalar (`Db.php`, `App.php`, `Router.php`, `WebAuthn.php`).
  - `Models/`: Veritabanı tablolarıyla nesne yönelimli eşleşen veri modelleri.
  - `Services/`: İş mantığını küçülten operasyonel katmanlar.
  - `Views/`: Veriyi HTML arayüzlerine döken şablon dosyaları (PHP ve HTML karışımı).
- `public_html/`: Apache web sunucusunun kök dizini (Document Root). Kullanıcının erişebildiği tek yerdir. Statik asset dosyaları (`/assets`, `/cdn`) ve ana entrypoint (`index.php`) buradadır.
- `cron/`: Zamanlanmış görevlerin (örneğin veritabanı yedeği, periyodik sanat eseri yayınlama) CLI üzerinden çalıştırılan PHP betikleri.
- `migrations/`: Sistem güncellemelerinde manuel veya script aracılığıyla çalıştırılan SQL değişim yönergeleri.
- `scripts/`: Test runner, smoke test ve deployment otomasyonları için yardımcı scriptler.
- `tests/`: Projenin regresyon testleri ve Playwright test konfigürasyonları.
- `documents/`: Sistemin nasıl çalıştığına dair kapsamlı teknik bilgi ve altyapı dokümantasyonlarını barındıran dizin. Detaylı teknik kılavuzlar için `documents/README.md` dosyasını inceleyebilirsiniz.

## Routing (Rotalama) Mantığı ve Subdomain Yapısı

1. **Varsayılan Dil (Language) Prefix'i:** `index.php` üzerinden geçen tüm geçerli sayfa isteklerinde URL'in başına dil ekinin (örn: `/tr`, `/en`) eklenmesi beklenir. Eksik durumlarda sistem tarayıcının Accept-Language başlığına bakarak ilgili dile 307 statü koduyla yönlendirme (redirect) yapar.
2. **Subdomain Yapılandırması (`notlar.*` vb.):** Proje ana domaine ek olarak alt alan adlarını (subdomain) okuyabilir. Örneğin `notlar.fezadan.org` adresinden gelen istekler ana dil rotalamasından (prefix kontrolünden) muaf tutularak doğrudan `NotesController` ile özel olarak tasarlanmış PDF arşivi sayfalarına yönlendirilir.

## Deployment ve Canlıya Alma

Projenin yayına alınması süreci (deployment), cPanel üzerinden Git Version Control aracı kullanılarak gerçekleştirilir. Dağıtım kuralları `.cpanel.yml` dosyasında tanımlanmıştır. Buna göre; yalnızca uygulamanın çalışması için gerekli olan klasörler (`app/`, `cron/`, `public_html/`) ve Composer kütüphaneleri hedef üretim (production) klasörüne kopyalanırken, yerel geliştirme dosyaları, test araçları, docker konfigürasyonları ve açık metin dokümanları üretim sunucusundan temizlenerek depolama tasarrufu ve ek güvenlik sağlanır.

*Kapsamlı deploy adımları ve Cloudflare / DNS / sunucu ayarları için lütfen `documents/07-installation-and-deploy.md` rehberine göz atın.*

## Lisans

Bu proje **GNU General Public License v3.0 (GPL-3.0)** lisansı altında yayınlanmıştır. Kodu inceleyebilir, kopyalayabilir ve kendi ihtiyaçlarınıza göre türevlerini açık kaynak olmak şartıyla oluşturabilirsiniz.
