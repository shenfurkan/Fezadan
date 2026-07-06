# 1. Proje Hakkında

## Bu Proje Nedir?

**Fezadan**, PHP programlama diliyle sıfırdan yazılmış bir yayın platformudur. WordPress, Joomla veya Drupal gibi hazır CMS (İçerik Yönetim Sistemi) çözümlerini kullanmak yerine, tüm bileşenlerin (routing, veritabanı yönetimi, güvenlik, dosya yükleme, admin paneli) kendi mimarisiyle geliştirildiği bağımsız bir sistemdir.

Bu yaklaşım, size sistemin her katmanı üzerinde tam kontrol sağlar. Üçüncü parti eklentilere, tema bağımlılıklarına veya otomatik güncelleme döngülerine ihtiyaç duymadan, sadece ihtiyacınız olan özellikleri barındıran hafif, hızlı ve güvenli bir altyapı sunar.

### Temel Özellikler

**1. Makale Yayınlama & Planlama Sistemi**
Blog veya haber sitesi olarak kullanabileceğiniz, çok dilli (Türkçe/İngilizce) içerik yönetimi sağlar. Makaleler kategorilere ayrılabilir, farklı yazarlar atanabilir, SEO meta verileri (başlık, açıklama, anahtar kelimeler) her makale için ayrı ayrı yapılandırılabilir.
- **İleri Tarihli Yayınlama:** `status = 'scheduled'` ve `publish_at` sütunları ile planlı makale desteği sunulur. [cron/publish-scheduled.php](file:///d:/Fezadan/cron/publish-scheduled.php) betiği aracılığıyla süresi gelen makaleler otomatik olarak yayına alınır.

**2. PDF Doküman Paylaşımı**
`notlar.fezadan.org` subdomain'i altında çalışan ayrı bir uygulama ile PDF dosyaları yayınlanabilir. Akademik makaleler, ders notları, e-kitaplar gibi dokümanlar Cloudflare R2'de saklanır ve tarayıcıda PDF.js kullanılarak görüntülenir.
- **İndirme Sınırları:** Otomatik tarayıcı botlarının notları sömürmesini önlemek amacıyla [app/Core/RateLimit.php](file:///d:/Fezadan/app/Core/RateLimit.php) yardımıyla her IP için dakikada maksimum 3 indirme sınırı konur.

**3. Sıfır Ziyaretçi Takibi (Gizlilik Odaklı)**
Google Analytics, Facebook Pixel, reklam ağları veya herhangi bir üçüncü parti izleme kodu içermez. Ziyaretçilerin IP adresleri bile veritabanında saklanmaz; bunun yerine günlük değişen bir salt (tuz) ile hash'lenir, böylece aynı IP her gün farklı bir kimlik alır ve geri izlenemez (KVKK / GDPR uyumluluğu).

**4. Otomatik OpenGraph Görsel Üretimi**
Sosyal medyada paylaşım yapıldığında otomatik olarak gözükecek yüksek çözünürlüklü banner'lar [app/Core/OgImage.php](file:///d:/Fezadan/app/Core/OgImage.php) kütüphanesi aracılığıyla PHP GD kütüphanesi (PNG) veya SVG fallback formatında üretilir. Üretilen görseller otomatik olarak R2'ye yüklenir.

**5. Gelişmiş Çoklu Dil Desteği**
Uygulamada [app/Translations/](file:///d:/Fezadan/app/Translations/) dizinindeki PHP dil dosyaları ve [app/Config/config.php](file:///d:/Fezadan/app/Config/config.php) içerisindeki `__()` yardımcısı kullanılarak arayüz dili dinamik olarak çevrilir. Makalelerin dil bağlantısı `translation_of` sütunu ile çift yönlü olarak yönetilir.

**6. AI Scraper Engelleme & Opt-Out**
Yapay zeka botlarının sitedeki makaleleri izinsiz eğitmesini engellemek için `SeoController::robots()` üzerinden dinamik `robots.txt` kuralları üretilir. Ayrıca tüm sayfalarda `<meta name="robots" content="noai, noimageai">` etiketleri yer alır.

---

## 2 Site — Tek Kod Tabanı

Projenin mimari olarak ilginç özelliklerinden biri: Aynı PHP kodları iki farklı web sitesini yönetir.

| Adres | Ne İşe Yarar? | Hangi Controller Kullanır? |
|-------|---------------|---------------------------|
| `fezadan.org` | Ana blog sitesi | HomeController, ArticleController, ArticlesController |
| `notlar.fezadan.org` | PDF arşivi | NotlarController |

**Nasıl çalışıyor?** [app/Core/App.php](file:///d:/Fezadan/app/Core/App.php) (router), gelen isteğin hangi domain'den geldiğini `$_SERVER['HTTP_HOST']` değişkeninden okur. Eğer host `notlar.` ile başlıyorsa, istek doğrudan [app/Controllers/NotlarController.php](file:///d:/Fezadan/app/Controllers/NotlarController.php)'a yönlendirilir ve dil prefix'i (`/tr/` veya `/en/`) mantığı tamamen atlanır. Diğer durumlarda standart routing uygulanır.

---

## Neden Var? (Felsefe ve Motivasyon)

### WordPress'in Sorunları

WordPress dünyadaki web sitelerinin %40'ından fazlasını çalıştırıyor. Bu popülerlik, beraberinde ciddi güvenlik ve performans sorunları getirir.
- **Otomatik Saldırı Hedefi:** Yönetici paneli yolları (`/wp-admin`, `/wp-login.php`) herkes tarafından bilindiği için, bot taramaları sürekli olarak bu açıkları hedef alır.
- **Eklenti Bağımlılığı:** SEO, güvenlik, cache vb. işlevler için çok sayıda eklenti kurulması gerekir; bu da veritabanı performansını düşürür ve eklenti geliştiricilerine bağımlılık yaratır.
- **Gizlilik Sorunu:** Ziyaretçilerin hareketleri Google Analytics, Facebook Pixel gibi harici kütüphaneler ile şirketlere sızdırılır.

### Fezadan'ın Çözümü

- **Özel Mimari ile İzolasyon:** WordPress dosya yapısından bağımsız, özel bir MVC yapısıyla yazılmıştır. Bot taramaları hedefsiz kalır.
- **Sıfır Eklenti Bağımlılığı:** Proje, ihtiyacı olan her şeyi kendi içinde barındırır. Dışarıdan indirilen tek bağımlılık Cloudflare R2 bağlantısı için `aws/aws-sdk-php` kütüphanesidir.
- **Güvenli Word / Web İçerik Aktarımı:** Admin editör panelinde Word belgelerini içeriğe dönüştürmek için kullanılan `mammoth.js` kütüphanesi harici CDN yerine yerel olarak (`assets/js/mammoth.browser.min.js`) sunularak CSP kurallarına tam uyum sağlar.
- **Veritabanı Yedekleme Rutinleri:** [cron/backup-db.php](file:///d:/Fezadan/cron/backup-db.php) betiği sayesinde veritabanı her gün yedeklenir, gzip ile sıkıştırılarak R2'de 7 gün saklanır.

---

## Teknoloji Yığını (Hangi Teknolojiler Kullanıldı?)

| Teknoloji | Versiyon | Ne İşe Yarar? |
|-----------|----------|---------------|
| PHP | 8.0+ (8.2 önerilir) | Sunucuda çalışan ana dil |
| MySQL | 5.7+ (8.0 önerilir) | Veritabanı yönetimi (InnoDB Motoru + FULLTEXT Arama) |
| Apache | 2.4+ | Web sunucusu (.htaccess desteği ile) |
| Cloudflare R2 | — | Medya ve PDF dosyaları için bulut depolama |
| TailwindCSS | v4 | Utility-first CSS framework |
| Docker | — | Konteynerize geliştirme ortamı |
| Composer | 2.x | PHP bağımlılık yönetimi |
| AWS SDK | ^3.378 | R2 API entegrasyonu |

### Neler KULLANILMADI?

- **Laravel / Symfony:** Hızlı, hafif ve bağımsız bir çekirdek için kullanılmadı.
- **Google Fonts:** Ziyaretçi IP'sinin Google'a sızmasını önlemek amacıyla yerel WOFF2 fontları kullanılır.
- **jQuery:** Modern JS (ES6) yeterli olduğu için gereksiz yükten kaçınıldı.
- **vlucas/phpdotenv:** Bağımlılıkları azaltmak amacıyla özel bir `.env` okuyucu yazıldı.

---

## Projenin Temel Felsefesi (Kısaca)

1. **Gizlilik önce gelir** — Ziyaretçi verisi toplanmaz, takip edilmez, profillenmez.
2. **Kendi kendine yeter** — Dışarıya bağımlı olma, her şey kendi içinde olsun.
3. **Hafif ol** — Sadece ihtiyacın olan özellikler olsun, gerisi şişkinliktir.
4. **Güvenli ol** — Saldırılara karşı katmanlı koruma.
5. **Kontrol sende** — Kodun her satırı senin kontrolündedir.
