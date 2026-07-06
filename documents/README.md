# Fezadan Dokümantasyonu

Projeyi baştan sona anlamak için aşağıdaki bölümleri sırayla okuyabilirsin.

---

## Bölümler

| # | Dosya | İçerik |
|---|-------|--------|
| 0 | [Operasyon Rehberi](OPERATION.md) | Canli deploy, cron, smoke test, rollback |
| 1 | [Proje Hakkında](01-proje-hakkinda.md) | Bu proje nedir, ne işe yarar, teknoloji yığını |
| 2 | [Mimari ve Klasör Yapısı](02-mimari-ve-klasor.md) | MVC, isteğin yolculuğu, tüm dosyaların açıklaması |
| 3 | [Veritabanı](03-veritabani.md) | Tablolar, Db.php, self-healing migration, SQL güvenliği |
| 4 | [Yönetim Paneli](04-yonetim-paneli.md) | Admin paneli, giriş güvenliği, yapılabilecek işlemler |
| 5 | [Cloudflare R2 ve Medya](05-cdn-ve-medya.md) | CDN mimarisi, upload pipeline, WebP dönüşümü |
| 6 | [Güvenlik](06-guvenlik.md) | Tüm güvenlik katmanları, .env koruması, CSP, CSRF |
| 7 | [Kurulum ve Deploy](07-kurulum-ve-deploy.md) | Docker, manuel kurulum, cPanel adım adım |
| 8 | [.env Dosyası](08-env-dosyasi.md) | Tüm değişkenler, açıklamaları, güvenlik önlemleri |
| 9 | [Cron ve Bakım](09-cron-ve-bakim.md) | Otomatik görevler, sitemap, günlük bakım |
| 10 | [SSS](10-sss.md) | Sık sorulan sorular ve sorun giderme |
| 11 | [PHP ve Veritabanı Rehberi](11-php-ve-veritabani-rehberi.md) | PHP + SQL nasıl çalışır, şifre güvenliği, makale akışı |
| 12 | [Hata Çözüm ve Sorun Giderme](12-hata-cozum-ve-sorun-giderme.md) | Git kilitleri, Docker portları, Selenium, SEO hataları |
| 13 | [Routing ve URL Yapısı](13-routing-ve-url-yapisi.md) | URL desenleri, controller çözümleme, dil prefix, slug mantığı |
| 14 | [Frontend ve TailwindCSS](14-frontend-ve-tailwind.md) | CSS/JS/font yapısı, Tailwind v4 kullanımı, CSP etkileşimi |
| 15 | [Çeviri ve Dil Sistemi](15-ceviri-ve-dil-sistemi.md) | `__()` fonksiyonu, dil dosyaları, translation_of, hreflang |
| 16 | [Notlar Subdomain](16-notlar-subdomain.md) | PDF arşivi, routing, rate limit, PDF.js, R2 streaming |
| 17 | [SEO ve Arama Motorları](17-seo-ve-arama-motorlari.md) | robots.txt, sitemap, meta etiketleri, JSON-LD, OG görseller |
| 18 | [Test ve Kalite Kontrol](18-test-ve-kalite-kontrol.md) | Selenium testi, PHP syntax kontrolü, hata ayıklama, loglama |
| 19 | [cPanel Git Deploy Kilidi](19-cpanel-git-deploy-kilidi.md) | Clean working tree, .env ignore, untracked dosya kilitleri |
| 20 | [Yayın Mimarisi ve Klasör Mantığı](20-yayin-mimarisi-ve-klasor-mantigi.md) | Fezadan, LiteCaptcha, public_html, runtime state ayrımı |
| 21 | [.env ve cPanel Incident Runbook](21-env-ve-cpanel-incident-runbook.md) | 18 Haziran 2026 deploy olayı, public/test.txt, tekrar çözüm akışı |
| 22 | [cPanel Git Control Panel](22-cpanel-git-control-panel.md) | Repository Path, Update from Remote, Deploy HEAD Commit, deploy loglari |
| 23 | [Cron Jobs Çalışma Prensibi](23-cron-jobs-calisma-prensibi.md) | Sitemap, scheduled publish, backup cronlari ve cPanel Cron Jobs ayarlari |
| 24 | [Cloudflare DNS ve TXT Kayitlari](24-cloudflare-dns-txt-kayitlari.md) | DNS, TXT, SPF/DKIM/DMARC, proxy/DNS-only kararlari |
| 25 | [Cloudflare R2 ve CDN Medya Operasyonu](25-cloudflare-r2-cdn-medya-operasyonu.md) | R2 bucket, CDN_URL, R2_PUBLIC_URL, medya cache ve hata ayiklama |
| 26 | [LiteCaptcha Calisma Prensibi](26-litecaptcha-calisma-prensibi.md) | Standalone captcha servisi, embed/redirect, token, HMAC, runtime state |
| 27 | [LiteCaptcha ve Fezadan Entegrasyon Akisi](27-litecaptcha-fezadan-integration-flow.md) | iframe, postMessage, LITECAPTCHA_SECRET, token verify ve CSP |
| 28 | [Security Headers, CSP ve Cloudflare](28-security-headers-csp-cloudflare.md) | HSTS, CSP, frame-ancestors, Cloudflare HTTPS ve cache bypass |
| 29 | [Robots, Sitemap, security.txt ve SEO Operasyonu](29-robots-sitemap-securitytxt-seo-operation.md) | robots.txt, sitemap.xml, sitemapindex, security.txt ve Search Console |
| 30 | [Canli URL ve Subdomain Haritasi](30-live-url-subdomain-map.md) | fezadan.org, notlar, cdn, LiteCaptcha, endpoint sahipligi ve smoke test |
| 31 | [Project Cleanup and Maintenance Notes](31-project-cleanup-and-maintenance.md) | Repo hijyeni, asset/vendor takibi, router whitelist ve mimari gecis kararlari |
| — | [Robot Kontrol Testi](extras/robot-kontrol-testi.md) | "İnsan mısınız?" doğrulama sistemi, kaçış oyunu, davranışsal analiz |
| — | [Portfolio](extras/portfolio.md) | Portfolyo modülü detayları |
| — | [plan.md](../plan.md) | Uygulama planı (Faz 1-5, 2026-06-09) |
| — | [beyinfirtinasi.md](../beyinfirtinasi.md) | Kod inceleme + yol haritası |

---

### Hızlı Başlangıç

Docker ile projeyi ayağa kaldırmak için:

```bash
git clone https://github.com/shenfurkan/Fezadan.git
cd Fezadan
cp .env.example .env
docker-compose up -d
# http://localhost:8080
```

---

*Son guncelleme: 18 Haziran 2026 (Belge 24-30 eklendi: Cloudflare, R2/CDN, LiteCaptcha, security headers, SEO operasyon ve canli URL haritasi)*
