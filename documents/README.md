# Fezadan Dokümantasyonu

Projeyi baştan sona anlamak için aşağıdaki bölümleri sırayla okuyabilirsin.

---

## Bölümler

| # | Dosya | İçerik |
|---|-------|--------|
| 0 | [Operasyon Rehberi](OPERATION.md) | Canli deploy, cron, smoke test, rollback |
| 1 | [Proje Hakkında](01-about-project.md) | Bu proje nedir, ne işe yarar, teknoloji yığını |
| 2 | [Mimari ve Klasör Yapısı](02-architecture-and-folders.md) | MVC, isteğin yolculuğu, tüm dosyaların açıklaması |
| 3 | [Veritabanı](03-database.md) | Tablolar, Db.php, self-healing migration, SQL güvenliği |
| 4 | [Yönetim Paneli](04-admin-panel.md) | Admin paneli, giriş güvenliği, yapılabilecek işlemler |
| 5 | [Cloudflare R2 ve Medya](05-cdn-and-media.md) | CDN mimarisi, upload pipeline, WebP dönüşümü |
| 6 | [Güvenlik](06-security.md) | Tüm güvenlik katmanları, .env koruması, CSP, CSRF |
| 7 | [Kurulum ve Deploy](07-installation-and-deploy.md) | Docker, manuel kurulum, cPanel adım adım |
| 8 | [.env Dosyası](08-env-file.md) | Tüm değişkenler, açıklamaları, güvenlik önlemleri |
| 9 | [Cron ve Bakım](09-cron-and-maintenance.md) | Otomatik görevler, sitemap, günlük bakım |
| 10 | [SSS](10-faq.md) | Sık sorulan sorular ve sorun giderme |
| 11 | [PHP ve Veritabanı Rehberi](11-php-and-database-guide.md) | PHP + SQL nasıl çalışır, şifre güvenliği, makale akışı |
| 12 | [Hata Çözüm ve Sorun Giderme](12-error-resolution-and-troubleshooting.md) | Git kilitleri, Docker portları, Selenium, SEO hataları |
| 13 | [Routing ve URL Yapısı](13-routing-and-url-structure.md) | URL desenleri, controller çözümleme, dil prefix, slug mantığı |
| 14 | [Frontend ve TailwindCSS](14-frontend-and-tailwind.md) | CSS/JS/font yapısı, Tailwind v4 kullanımı, CSP etkileşimi |
| 15 | [Çeviri ve Dil Sistemi](15-translation-and-language-system.md) | `__()` fonksiyonu, dil dosyaları, translation_of, hreflang |
| 16 | [Notlar Subdomain](16-notes-subdomain.md) | PDF arşivi, routing, rate limit, PDF.js, R2 streaming |
| 17 | [SEO ve Arama Motorları](17-seo-and-search-engines.md) | robots.txt, sitemap, meta etiketleri, JSON-LD, OG görseller |
| 18 | [Test ve Kalite Kontrol](18-test-and-quality-control.md) | Selenium testi, PHP syntax kontrolü, hata ayıklama, loglama |
| 19 | [cPanel Git Deploy Kilidi](19-cpanel-git-deploy-lock.md) | Clean working tree, .env ignore, untracked dosya kilitleri |
| 20 | [Yayın Mimarisi ve Klasör Mantığı](20-publishing-architecture-and-folder-logic.md) | Fezadan, LiteCaptcha, public_html, runtime state ayrımı |
| 21 | [.env ve cPanel Incident Runbook](21-env-and-cpanel-incident-runbook.md) | 18 Haziran 2026 deploy olayı, public/test.txt, tekrar çözüm akışı |
| 22 | [cPanel Git Control Panel](22-cpanel-git-control-panel.md) | Repository Path, Update from Remote, Deploy HEAD Commit, deploy loglari |
| 23 | [Cron Jobs Çalışma Prensibi](23-cron-jobs-working-principle.md) | Sitemap, scheduled publish, backup cronlari ve cPanel Cron Jobs ayarlari |
| 24 | [Cloudflare DNS ve TXT Kayıtları](24-cloudflare-dns-txt-records.md) | DNS, TXT, SPF/DKIM/DMARC, proxy/DNS-only kararlari |
| 25 | [Cloudflare R2 ve CDN Medya Operasyonu](25-cloudflare-r2-cdn-media-operation.md) | R2 bucket, CDN_URL, R2_PUBLIC_URL, medya cache ve hata ayiklama |
| 26 | [LiteCaptcha Çalışma Prensibi](26-litecaptcha-working-principle.md) | Standalone captcha servisi, embed/redirect, token, HMAC, runtime state |
| 27 | [LiteCaptcha ve Fezadan Entegrasyon Akışı](27-litecaptcha-fezadan-integration-flow.md) | iframe, postMessage, LITECAPTCHA_SECRET, token verify ve CSP |
| 28 | [Security Headers, CSP ve Cloudflare](28-security-headers-csp-cloudflare.md) | HSTS, CSP, frame-ancestors, Cloudflare HTTPS ve cache bypass |
| 29 | [Robots, Sitemap, security.txt ve SEO Operasyonu](29-robots-sitemap-securitytxt-seo-operation.md) | robots.txt, sitemap.xml, sitemapindex, security.txt ve Search Console |
| 30 | [Canlı URL ve Subdomain Haritası](30-live-url-subdomain-map.md) | fezadan.org, notlar, cdn, LiteCaptcha, endpoint sahipligi ve smoke test |
| 31 | [Proje Temizlik ve Bakım Notları](31-project-cleanup-and-maintenance.md) | Repo hijyeni, asset/vendor takibi, router whitelist ve mimari gecis kararlari |
| 32 | [İsteğin Yolculuğu](32-journey-of-a-request.md) | Web isteğinin index.php'den controller ve view'a kadar olan yolculuğu |
| — | [Robot Kontrol Testi](extras/robot-control-test.md) | "İnsan mısınız?" doğrulama sistemi, kaçış oyunu, davranışsal analiz |
| — | [Portfolio](extras/portfolio.md) | Portfolyo modülü detayları |

---

### Hızlı Başlangıç

Docker ile projeyi ayağa kaldırmak için:

```bash
git clone https://github.com/shenfurkan/Fezadan.git
cd Fezadan
cp .env.example .env
docker-compose up -d --build
# http://localhost:8080
```
