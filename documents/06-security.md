# 6. Güvenlik

## Güvenlik Mimarisi: Katmanlı Savunma

Fezadan projesi, siber tehditlere karşı iç içe çalışan çok katmanlı bir savunma (Defense in Depth) mimarisine sahiptir. Bir güvenlik katmanını aşmayı başaran bir saldırgan, sonraki katmanlar tarafından engellenir.

---

## 1. .env Dosyası ve Güvenlik Tuzları (APP_SALT)

`.env` dosyası veritabanı şifrelerini, API anahtarlarını ve gizli ayarları içerir.
- **Web Erişimi Engeli:** [public_html/.htaccess](file:///d:/Fezadan/public_html/.htaccess) kuralları sayesinde web tarayıcısı üzerinden `.env` dosyasına erişilmeye çalışıldığında doğrudan **403 Forbidden** yanıtı verilir.
- **Git Koruma Filtresi:** `.env` dosyası [**.gitignore**](file:///d:/Fezadan/.gitignore) listesindedir, yanlışlıkla dahi olsa GitHub'a gönderilmesi engellenmiştir.
- **APP_SALT Otomatik Üretimi:** [app/Config/config.php](file:///d:/Fezadan/app/Config/config.php) dosyası, `.env` içerisindeki `APP_SECURITY_SALT` alanı boş bırakıldığında veya varsayılan `change-me` değerinde kaldığında, otomatik olarak 64 karakter uzunluğunda rastgele kriptografik bir tuz üretir. Bunu [app/Config/.security_salt](file:///d:/Fezadan/app/Config/.security_salt) dosyasına yazar ve dosya izinlerini `chmod 0600` (sadece sahibi okuyabilir) olarak kilitler.

---

## 2. Path Traversal ve Dizin Atlatma Koruması

Saldırganların URL üzerinden `../../etc/passwd` gibi sistem dosyalarına erişmesini engellemek için [app/Core/App.php](file:///d:/Fezadan/app/Core/App.php) router katmanında sıkı filtreler uygulanır:
- URL'de `..`, `%2e%2e`, `.%2e`, `%2e.`, `%2f` parametreleri aranır ve bulunursa istek anında kesilerek **400 Bad Request** yanıtı döndürülür.
- URL segmentleri çözüldükten sonra her bir segment tek tek denetlenerek `.` veya `..` değerine sahip olmaları halinde işlem sonlandırılır.

---

## 3. Yönetici Paneli İzolasyonu

- **Admin Yollarının Gizlenmesi:** Botların otomatik tarama yaptığı `/admin`, `/panel`, `/dashboard` gibi yollar [app/Core/App.php](file:///d:/Fezadan/app/Core/App.php) tarafından yakalanarak sahte bir **404 Not Found** yanıtına yönlendirilir. Gerçek admin yolu yalnızca `/yonetim`'dir.
- **Role-Based Access Control (RBAC):** Admin kullanıcıları `superadmin`, `editor` ve `viewer` olarak rollerine göre sınırlandırılır. `viewer` rolü hiçbir yazma metodunu tetikleyemez, salt okunur yetkiye sahiptir.
- **POST Yazma Whitelist'i:** Yönetim panelindeki tüm veri değiştirme (yazma) işlemleri [app/Controllers/AdminController.php](file:///d:/Fezadan/app/Controllers/AdminController.php) içerisindeki `$writeMethods` dizisinde listelenir. Bu listede yer almayan ve POST ile gelen istekler doğrudan reddedilir.

---

## 4. IP Hashleme ve KVKK/GDPR Uyumlu Rate Limit

IP adresleri veritabanında açık metin olarak saklanmaz. Ziyaretçilerin mahremiyetini korumak ve KVKK gereksinimlerini karşılamak amacıyla IP'ler günlük tuzlu hash biçimine dönüştürülür:

```php
$ipHash = hash('sha256', $ip . date('Y-m-d') . APP_SALT);
```
- Bu hash her gün otomatik olarak değişir. Saldırganların veya meşru kullanıcıların IP adresleri veritabanından geriye dönük olarak asla deşifre edilemez.
- **Merkezi RateLimit Yardımcısı:** Tüm hız sınırlama işlemleri [app/Core/RateLimit.php](file:///d:/Fezadan/app/Core/RateLimit.php) sınıfında DRY (Don't Repeat Yourself) ilkesine göre birleştirilmiştir. Makale okuma sayacı (`read_rate_limits` günde 1 kez) ve not indirme sınırlayıcısı (`download_rate_limits` dakikada 3 kez) bu sınıf üzerinden denetlenir.

---

## 5. CSRF (Cross-Site Request Forgery) Koruması

Ziyaretçinin oturumu açıkken harici sitelerden kendi adına işlem yapılmasını önlemek amacıyla [app/Core/Csrf.php](file:///d:/Fezadan/app/Core/Csrf.php) sınıfı kullanılır:
- POST formu barındıran her sayfaya `Csrf::field()` ile rastgele üretilen 64 karakterli token yerleştirilir.
- Form gönderildiğinde `Csrf::verify()` doğrulaması gerçekleştirilir. Zamanlama saldırılarını (Timing attacks) önlemek amacıyla doğrulamada normal karşılaştırma (`==`) yerine sabit sürede çalışan `hash_equals()` fonksiyonu tercih edilir.

---

## 6. Sıkılaştırılmış CSP (Content Security Policy) ve JavaScript İzolasyonu

- **'unsafe-inline' ve 'unsafe-eval' Kısıtlaması:** Önceden sitenin genelinde bulunan esnek CSP kuralları daraltılmıştır. `'unsafe-inline'` ve `'unsafe-eval'` gibi tehlikeli izinler **yalnızca** `/yonetim` (admin paneli) rotasıyla sınırlandırılmıştır. Ziyaretçi tarafındaki tüm sayfalar katı bir izolasyona sahiptir.
- **Dışa Aktarılan JS İş Mantığı:** Eski sürümlerde `read.php`, `login.php` gibi view'ların içinde yer alan inline business logic tamamen `.js` uzantılı harici dosyalara çıkarılarak CSP uyumlu hale getirilmiştir.
- **CSP_NONCE Sabiti:** Her istek için benzersiz bir `CSP_NONCE` üretilir. Görünümlerde (Views) yer almak zorunda olan yapılandırma değişkenleri vb. için `<script nonce="<?= CSP_NONCE ?>">` biçiminde nonce değeri taşınması zorunludur. Aksi halde tarayıcı kodu çalıştırmayı reddeder (XSS Koruması).
- **Lokal mammoth.js Entegrasyonu:** Word belgelerini içeriğe dönüştürmek için kullanılan mammoth.js paketi harici CDN ağlarından yüklenmek yerine yerel dizine çekilmiş olup CSP `'self'` kuralına tam uyum sağlayarak yüklenir.

---

## 7. Otomatik Git-Deploy Webhook Güvenliği

Eski [public_html/git-deploy.php](file:///d:/Fezadan/public_html/git-deploy.php) webhook endpoint'i production deploy yolu değildir; 410 döndürür. Production deploy cPanel Git deployment üzerinden yapılır ve ikinci bir webhook yolu eklenmemelidir.

```php
$signature = $_SERVER['HTTP_X_HUB_SIGNATURE_256'] ?? '';
$expected = 'sha256=' . hash_hmac('sha256', $payload, env_value('DEPLOY_SECRET'));
if (!hash_equals($expected, $signature)) {
    http_response_code(403);
    exit;
}
```
Sadece `.env` içerisindeki `DEPLOY_SECRET` anahtarını bilen yetkili GitHub sunucularının istekleri işlenir.

---

## 8. Log Dosyalarının Korunması

Admin panelinde yapılan tüm işlemlerin loglandığı `logs/` klasörü, web tarayıcısından gelecek doğrudan isteklere karşı `logs/.htaccess` dosyası içerisindeki `Require all denied` yönergesi ile tamamen kapatılmıştır. Loglar sadece PHP dosya okuma API'leri ile admin paneli yetkilendirmesiyle okunabilir.

---

## 9. Dinamik robots.txt ve AI-Bot Engelleme

`robots.txt` artık statik bir dosya değildir. [app/Controllers/SeoController.php](file:///d:/Fezadan/app/Controllers/SeoController.php) tarafından her istekte dinamik olarak üretilir. Bu sayede:

- Host başlığına (`HTTP_HOST`) göre ana site ve `notlar.` subdomain için **farklı kurallar** otomatik olarak sunulur.
- Yeni bir AI-bot engellemesi gerektiğinde tek bir dosyadan (`SeoController.php`) güncellenerek tüm ortamlara anında yansıtılır.

### Engellenen AI Tarayıcıları

Telif hakkı ve içerik koruma amacıyla aşağıdaki AI arama/eğitim botları varsayılan olarak engellenir:

```
User-agent: GPTBot
Disallow: /

User-agent: ChatGPT-User
Disallow: /

User-agent: Google-Extended
Disallow: /

User-agent: anthropic-ai
Disallow: /

User-agent: ClaudeBot
Disallow: /

User-agent: Omgilibot
Disallow: /

User-agent: FacebookBot
Disallow: /
```

Yeni bir bot eklemek için [SeoController::robots()](file:///d:/Fezadan/app/Controllers/SeoController.php) metoduna yukarıdaki formatla ilgili `User-agent` bloğu eklemek yeterlidir.
