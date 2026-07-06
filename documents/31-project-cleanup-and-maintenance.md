# Project Cleanup and Maintenance Notes

Bu belge, `projeraporplani.md` sonrasındaki derleme/toparlama kararlarını tek yerde tutar. Kod davranışı değiştiren refactorlar küçük parçalara bölünmeden yapılmamalıdır.

## Son Temizlik Oturumu (2026-07-03)

### Silinen Tracked Dosyalar

- `scripts/extract.php`, `scripts/extract-db.php`, `scripts/extract-login-js.php`, `scripts/extract-read-js.php` — tek seferlik refactor/çıkartma yardımcıları
- `scripts/refactor-forms.php` — eski form refactor yardımcısı
- `scripts/get-article-url.php` — tek seferlik URL çıkartma
- `scripts/test-new-features.php`, `scripts/test-svg-fallback.php`, `scripts/test-upload-pipeline.php` — eski test yardımcıları
- `public_html/readme.md` — boş dosya
- `error-check.txt`, `index.html`, `stem-cells.html`, `template-tag.tpl` — eski statik/geçici dosyalar
- `split_anonymity.php`, `split_anonymity.py` — tek seferlik bölme yardımcıları
- `upgrade_mitigations.py` — eski yükseltme aracı
- `manifesto.txt` — statik manifesto metni (PHP view içinde yönetiliyor)
- `localhost.sql` — eski seed dump (ignored, fiziksel silindi)

### Admin UI Sadeleştirmesi

- Dashboard: Sistem durumu yazısı, güncelleme bildirimleri, yama notu kartı ve LiteCaptcha kartı kaldırıldı. Terminal sadece `?scan=1` ile render ediliyor, fake gecikme animasyonu yok.
- Makale oluşturma (`/yonetim/create`): Şablon dropdown, otomatik kayıt timer/göstergesi kaldırıldı. Editor stabilizasyon iyileştirmeleri eklendi (Word import fallback, çoklu yazar checkbox, invalid cover temizleme, submit buton koruması).
- Sağ panel taşması azaltıldı, ana yazı alanı genişletildi, header sadeleştirildi.
- Editor selector'ları korundu: `#summernote`, `#wordImportTrigger`, `#manualSlug`, `#draftBtn`, `#submitBtn`, `#uploadForm`.

## Repo Hijyeni

- Root seviyesindeki generated `all_files*` dökümleri runtime gerektirmez. `.gitignore` kapsamındadır, git indexinden çıkarıldı ve fiziksel olarak silindi.
- Bozuk Windows path isimli `d??Fezadan?...` artefaktları runtime parçası değildir. Ignore koruması eklendi ve fiziksel olarak silindi.
- `public_html/test-deploy.php` ve `public_html/run_deploy.php` eski/yerel helper dosyalarıdır. Production deploy yolu cPanel Git olmalıdır.

## Asset Kararları

- `close-popups.gif` yerine `close-popups.webp` kullanılmaktadır. WebP, aynı görsel işlevi daha düşük payload ile sağlar.
- `cool-gif-1.gif`, `cool-gif-2.gif` ve `popups.gif` referanssız oldukları için deploy payload'ından kaldırıldı.
- Web runtime fontlarının source-of-truth dizini `public_html/assets/fonts/` kabul edilir.
- Root `assets/fonts/` dizini körlemesine silinmemelidir; `OgImage.php` şu anda `assets/fonts/SpaceGrotesk-Variable.ttf` dosyasını kullanır.
- Font sadeleştirmesi yapılacaksa önce runtime referansları ve OG image üretimi birlikte doğrulanmalıdır.

## Vendored JS Takibi

| Paket | Yerel dosya | Bilinen sürüm / durum | Kullanım |
|---|---|---|---|
| PDF.js | `public_html/assets/js/pdf.js`, `pdf.worker.js` | `5.6.205` | Notlar PDF görüntüleme |
| Mammoth | `public_html/assets/js/mammoth.browser.min.js` | Header sürümü net değil | Admin DOCX import |
| Summernote | `public_html/assets/plugins/summernote/`, fallback `assets/js/summernote-lite.min.js` | Header sürümü net değil | Admin editor |
| Leaflet | `public_html/assets/plugins/leaflet/leaflet.js` | `1.9.4` | Anonymity check harita |

Güncelleme kuralı: bundle değiştirilirse ilgili admin/editor veya harita akışı manuel smoke testten geçmeden deploy edilmemelidir.

**Mammoth ve Summernote Manuel Güncelleme Prosedürü:**
- Mammoth: `https://github.com/mwilliamson/mammoth.js/releases` adresinden güncel `mammoth.browser.min.js` indirilerek `public_html/assets/js/` içerisine kopyalanmalıdır.
- Summernote: `https://github.com/summernote/summernote/releases` adresinden son sürüm indirilerek `public_html/assets/plugins/summernote/` içerisine açılmalı ve gerekirse fallback olan `summernote-lite.min.js` dosyası güncellenmelidir.

## Mimari Geçiş Sırası

- `AdminSystem.php` tek hamlede parçalanmamalıdır. Önce health/sitemap, sonra reset-password/admins/logs, sonra OG/TTS, sonra authors blokları ayrılmalıdır.
- Admin method adları, `AdminController` auth/CSRF/write/json listeleri ve router whitelist ile aynı kalmalıdır.
- Router dispatch artık controller bazlı whitelist mantığıyla düşünülmelidir. Yeni public action eklendiğinde hem controller hem whitelist birlikte güncellenmelidir.
- `SeoController::regenerateAllCache()` dosya yazan internal/helper akıştır; public route whitelist'ine eklenmemelidir. Sitemap üretimi admin veya cron hattından yürümelidir.
- `Db.php` self-healing schema işlemleri hemen kaldırılmamalıdır. Önce idempotent CLI migration komutu ve integration test seti hazırlanmalıdır.
- `read.php`, `login.php`, `create.php` ve `edit.php` gibi uzun view dosyalarındaki inline JavaScript ve business logic ayrı `assets/js/...` veya `app/Helpers/...` katmanlarına taşınmalıdır. Bu işlem de her dosya için ayrı bir PR/commit olacak şekilde tek tek yapılmalı, inline JS önce script tag'leri içinde nonce-only duruma getirilmeli, sonra tamamen harici dosyalara ayrılmalıdır.

## Operasyon Sonrası Kontroller

- Production deploy sonrası `robots.txt`, `sitemap.xml`, `sitemap_main.xml` ve `sitemap_notes.xml` canlı URL ile doğrulanmalıdır.
- Service worker install/cache davranışı tarayıcı console üzerinden kontrol edilmelidir.
- R2 upload, `/uploads/...` fallback, LiteCaptcha ve cron işleri local refactor tamamlandıktan sonra ayrı operasyon kontrolü olarak ele alınmalıdır.
