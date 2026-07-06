# 32. Bir İsteğin Yolculuğu (The Request Journey)

Bu belge, Fezadan projesinde sıradan bir ziyaretçinin tarayıcısından çıkan bir tıklamanın, sunucunun derinliklerine inip tekrar görsel bir web sayfasına dönüşene kadar geçirdiği saniyenin kesirlerindeki yolculuğu "hikayesel" bir biçimde anlatır.

---

## 1. İlk Temas: Cloudflare ve Sınır Güvenliği
Bir ziyaretçi tarayıcısına `fezadan.org` yazdığında veya bir makaleye tıkladığında istek doğrudan sunucuya (origin) ulaşmaz. Önce **Cloudflare** ağına çarpar:
- **DNS ve SSL:** Cloudflare isteği karşılar, HTTPS sertifikasını sunar ve güvenli bağlantıyı kurar.
- **Cache (Önbellek) Kontrolü:** Eğer istek statik bir dosyaysa (bir `.css`, `.js` veya `.webp` görseli) ve Cloudflare'in önbelleğinde (Edge sunucusunda) varsa, sunucuya hiç uğramadan saniyeden kısa sürede ziyaretçiye yollanır.
- **Güvenlik Duvarı (WAF):** İstekte bilinen bir saldırı deseni (SQL Injection, XSS) varsa Cloudflare seviyesinde engellenir.

İstek güvenliyse ve dinamik bir sayfa (PHP) gerektiriyorsa asıl sunucumuza, yani **Apache** web sunucusuna iletilir.

---

## 2. Sunucu Kapısı: `.htaccess` ve Yönlendirme (Routing)
İstek sunucuya ulaştığında `public_html/.htaccess` dosyası onu karşılar:
- Ziyaretçi gizli bir klasöre (örn. `.env`, `logs`, `vendor`, `storage`) girmeye çalışıyorsa anında **403 Forbidden** (Yasak) cevabı verilir.
- İstek geçerli bir dosya veya klasöre yapılmamışsa (ki makale URL'leri böyledir), tüm istekler `index.php?url=...` yapısına dönüştürülerek projenin kalbi olan `index.php` dosyasına aktarılır.

---

## 3. Sistemin Uyanışı: `index.php`
Her sayfa yüklendiğinde projenin giriş kapısı olan `index.php` baştan sona hızlıca çalışır:
1. **Oturum (Session) Güvenliği:** `session.cookie_httponly` ve `session.cookie_secure` gibi katı ayarlar yapılarak ziyaretçinin oturum çerezi başlatılır.
2. **XSS Kalkanı (CSP Nonce):** Ziyaretçinin sayfasına eklenebilecek zararlı kodları durdurmak için o saniyeye özel, tahmin edilemez bir `CSP_NONCE` (kriptografik şifre) üretilir. Tüm sayfa boyunca bu şifreyi bilmeyen hiçbir JavaScript kodu çalıştırılamaz.
3. **Cloudflare IP Doğrulaması:** Ziyaretçinin gerçek IP adresi (`CF-Connecting-IP`), isteğin gerçekten Cloudflare üzerinden geldiği doğrulanarak sisteme alınır. Sahte proxy saldırıları elenir.
4. **Bağımlılıkların Yüklenmesi:** Veritabanı (`Db.php`), Hız Sınırlandırıcı (`RateLimit.php`) ve Yönlendirici (`App.php`) gibi temel parçalar hafızaya alınır.

---

## 4. Dil ve Rota Ayrımı: `App.php`
Sistem artık ne yapılacağını anlamak için URL'ye bakar (örn: `/tr/hakkinda`).
- **Dil Algılama:** İstekte `/tr/` veya `/en/` ön eki aranır. Eğer kullanıcı sadece `fezadan.org` adresine girmişse, tarayıcısının dili (`HTTP_ACCEPT_LANGUAGE`) kontrol edilir. Türkçe kullanıyorsa anında `fezadan.org/tr` adresine **307 Yönlendirmesi** yapılır.
- **Subdomain Kontrolü:** Eğer adres `anonymitycheck.fezadan.org` ise dil kontrolleri es geçilerek direkt gizlilik aracına (`AnonymityCheckController`) bağlanılır.
- **Controller Çözümleme:** Adres `/tr/makaleler` ise sistem bunun `ArticleController` dosyasına gideceğini anlar ve ilgili sınıfı ayağa kaldırır.

---

## 5. Beynin Çalışması: Controller ve Veritabanı (`Db.php`)
Ziyaretçi bir makaleyi okumak için `/tr/yazar-adi/makale-basligi` bağlantısına tıklamış olsun:
1. **Veritabanı Bağlantısı:** `ArticleController`, `Db::pdo()` aracılığıyla MySQL veritabanına güvenli (Prepared Statement) bir bağlantı açar. SQL Injection girişimi varsa (örn: `makale-basligi' OR 1=1`) bu değer sadece metin olarak algılanır, asla kod olarak çalıştırılmaz.
2. **Veri Çekme:** Yazar ve makale slug değerleriyle tablolardan makale bilgileri (başlık, içerik, tarih) çekilir.
3. **Okunma Sayacı (RateLimit):** Makalenin "Okunma Sayısı" artırılacaktır. Ancak sahte tıklamaları engellemek için `RateLimit.php` devreye girer. Ziyaretçinin IP adresi anlık olarak geri döndürülemez bir Hash formuna dönüştürülür. Eğer bu Hash bugün bu makaleyi okumuşsa sayaç artırılmaz. Okumamışsa 1 artırılır.

---

## 6. Arayüzün Çizilmesi: View ve JavaScript
Veriler hazırlandıktan sonra son adım HTML'in üretilmesidir (`Controller::view()`):
1. **HTML İskeleti:** Çekilen makale verisi `app/Views/front/article.php` dosyasına gönderilir. PHP kodları değişkenleri ekrana basar.
2. **Dil Çevirisi:** Sayfadaki "Devamını Oku" veya "Hakkında" gibi butonlar, kullanıcının seçtiği dile göre `__('read_more')` fonksiyonu ile `app/Translations/tr.php` (veya `en.php`) dosyasından Türkçeye veya İngilizceye çevrilir.
3. **Katı JS İzolasyonu:** Sayfa üretilirken hiçbir satıriçi (inline) JavaScript koduna yer verilmez. Dinamik buton işlevleri, harici olan `assets/js/main.js` dosyasından yüklenir.
4. **Header'ların Eklenmesi:** Sayfa yollanmadan hemen önce, 2. adımda üretilen `CSP_NONCE` değeri `Content-Security-Policy` başlığına eklenerek paketlenir.

---

## 7. Görsellerin (R2) Ortaya Çıkışı
Sayfa HTML olarak tarayıcıya ulaştı ve kullanıcı okumaya başladı. Makale içinde bir resim var:
- `<img>` etiketinin `src` adresi `https://cdn.fezadan.org/uploads/makale_kapak.webp` şeklindedir.
- Tarayıcı bu adrese istek atar. Bu adres sunucumuzu değil, doğrudan Cloudflare R2 depolama birimini veya CDN'i gösterir.
- Yükleme esnasında GD/Imagick kütüphaneleriyle süzülerek WebP formatına çevrilmiş bu temiz görsel, ultra yüksek hızda ziyaretçinin ekranında belirir.

---

## 8. Kapanış ve Loglama (`AdminLog.php` & ErrorHandler)
Eğer bu gezintiyi yapan kişi bir Admin ise (örneğin `/yonetim` sayfasındaysa) ve bir işlem yaptıysa, `AdminLog.php` olayı arkada `logs/admin.log` dosyasına kaydeder.
Şayet süreçte veritabanı çöktüyse veya bir hata olduysa, `ErrorHandler.php` bunu ziyaretçiye hissettirmez. Ziyaretçi sadece tasarımlı bir "Beklenmedik bir hata oluştu" (500) sayfası görürken, sorunun tüm detayları (`trace`) yöneticinin incelemesi için güvenli bir log dosyasına saklanır.

İşte tüm bu devasa güvenlik önlemleri, veritabanı sorguları ve dosya okumaları, ziyaretçi o bağlantıya tıkladıktan sadece **milisaniyeler (0.05 sn - 0.2 sn)** sonra tamamlanmış olur.
