<p align="center">
  <img src="public_html/cdn/logo-light.png" alt="Fezadan Logo" width="240"/>
</p>

<p align="center">
  <b>PHP MVC tabanlı yayın ve arşiv platformu</b>
</p>

<p align="center">
  <img src="https://img.shields.io/badge/version-3.0.0-6D2323" alt="v3.0.0"/>
  <img src="https://img.shields.io/badge/PHP-8.2-777BB4?logo=php&logoColor=white" alt="PHP 8.2"/>
  <img src="https://img.shields.io/badge/MariaDB-10.3%2B-003545?logo=mariadb&logoColor=white" alt="MariaDB"/>
  <img src="https://img.shields.io/badge/Tailwind%20CSS-v4-06B6D4?logo=tailwindcss&logoColor=white" alt="Tailwind"/>
  <img src="https://img.shields.io/badge/Cloudflare%20R2-F38020?logo=cloudflare&logoColor=white" alt="Cloudflare R2"/>
  <img src="https://img.shields.io/badge/License-GPL--3.0-A42E2B?logo=gnu&logoColor=white" alt="GPL-3.0"/>
</p>

---

## Bu Proje Nedir?

Fezadan; makale yayınlama, PDF arşivleme, medya yönetimi ve yönetim paneli işlevlerini tek bir özel MVC çekirdeğinde toplayan bir PHP uygulamasıdır. Laravel veya Symfony gibi genel amaçlı bir framework kullanmaz; yalnızca ihtiyaç duyduğu katmanları içerir.

Bu depo, projenin **herkese açık kod aynasıdır**. Kişisel portfolyo, webmail kurulumu, yayın sunucusu yapılandırması, testler ve veritabanı dökümleri burada yer almaz.

### v3.0.0 ile gelenler

- Trait tabanlı yönetim paneli (`/yonetim`) ve rol ayrımı: `viewer`, `editor`, `superadmin`.
- Makale, not/PDF arşivi, kategori, yazar ve portfolyo yönetimi.
- Cloudflare R2 medya depolama; yüklemede görsel doğrulama, yeniden kodlama ve WebP dönüşümü.
- Nonce tabanlı CSP, CSRF koruması, oturum sıkılaştırması ve giriş hız sınırı.
- Türkçe/İngilizce URL yönlendirme, canonical/hreflang ve otomatik sitemap üretimi.
- IndexNow ile arama motorlarına anında bildirim.
- Davetiyeli e-posta hesabı akışı ve LiteCaptcha doğrulaması.
- Cloudflare Web Analytics için gizlilik dostu, çerezsiz ölçümleme.

## Ayrı Depolar

| Proje | Depo | Lisans |
|---|---|---|
| AnonymityCheck (tarayıcı gizlilik testi) | [shenfurkan/FezadanAnonymityCheck](https://github.com/shenfurkan/FezadanAnonymityCheck) | GPL-3.0 |
| Fezadan Roundcube skin | [shenfurkan/fezadan-roundcube-skin](https://github.com/shenfurkan/fezadan-roundcube-skin) | CC BY-SA 3.0 |

AnonymityCheck bu depoda modül olarak tutulmaz; kendi deposundan kurulur ve `https://anonymitycheck.fezadan.org` adresinde çalışır. Roundcube skin'i de bağımsız bir Elastic alt temasıdır ve webmail kurulumuna ayrıca kopyalanır.

## Gereksinimler

- PHP 8.2 (PDO, mbstring, fileinfo, curl)
- MariaDB 10.3+ / MySQL 5.7+
- Composer
- Node.js ve npm (yalnızca Tailwind derlemesi için)
- Apache veya Nginx; document root `public_html/` olmalı

## Kurulum

1. Depoyu klonlayın ve bağımlılıkları yükleyin:

   ```bash
   git clone https://github.com/shenfurkan/Fezadan.git
   cd Fezadan
   composer install
   npm install
   ```

2. Çevre değişkenlerini hazırlayın:

   ```bash
   cp .env.example .env
   ```

   `.env` içinde veritabanı, `APP_SECURITY_SALT`, R2 ve isteğe bağlı servis anahtarlarını doldurun. `APP_SECURITY_SALT` boş bırakılırsa uygulama `app/Config/.security_salt` dosyasını üretir; bu dosyayı commit etmeyin.

3. Şemayı oluşturun:

   ```bash
   php scripts/migrate-db.php
   ```

   Betik yalnızca eksik tablo ve kolonları ekler; mevcut veriyi silmez.

4. CSS'i derleyin:

   ```bash
   npm run build
   ```

5. Apache document root'unu `public_html/` yapın, `storage/` dizinini yazılabilir bırakın ve siteyi açın.

`/` isteği dil çerezine veya `Accept-Language` başlığına göre `/tr` ya da `/en` adresine yönlenir. Yönetim paneli `/yonetim` altındadır.

## Komutlar

| Komut | Açıklama |
|---|---|
| `npm run dev` | Tailwind CSS'i izleme modunda derler. |
| `npm run build` | Tailwind CSS'i üretim için küçültür. |
| `php scripts/migrate-db.php` | Eksik tablo ve kolonları ekler. |
| `php cron/generate-sitemap.php --force` | Sitemap dosyalarını yeniden üretir. |
| `php cron/publish-scheduled.php` | Zamanlanmış içerikleri yayınlar. |

## Dizin Yapısı

- `app/Config/` — yapılandırma ve `.env` okuyucu.
- `app/Controllers/` — HTTP denetleyicileri; yönetim paneli `Admin/` trait'lerine ayrılmıştır.
- `app/Core/` — router, PDO, oturum, CSRF, yükleme ve hata yönetimi.
- `app/Services/` — R2, IndexNow, e-posta daveti ve içerik sorguları.
- `app/Views/` — ön yüz ve yönetim şablonları.
- `public_html/` — Apache document root; giriş noktası `index.php`.
- `cron/` — CLI görevleri.
- `documents/` — mimari, güvenlik ve bakım kılavuzları.

## Güvenlik Notları

- Yönetim paneli `/admin`, `/panel` ve `/dashboard` adreslerinde bilerek 404 döner.
- Tüm mutatif yönetim istekleri `requirePost()` ve CSRF doğrulamasından geçer.
- Her istekte yönetici kaydı yeniden okunur; silinen hesap veya değişen şifre oturumu sonlandırır.
- Şifre ve passkey girişinde oturum kimliği yenilenir.
- Hassas dosyalar (`app/`, `vendor/`, `.env`) document root dışındadır.
- Bu depoda örnek veya gerçek yönetici hesabı bulunmaz.

## Lisans

Bu proje **GNU General Public License v3.0 (GPL-3.0)** altında yayınlanmıştır. Ayrı depolardaki Roundcube skin'i CC BY-SA 3.0, AnonymityCheck ise GPL-3.0 lisansına sahiptir.
