# 11. PHP ve Veritabanı (SQL) Çalışma Rehberi

Bu rehber; FEZADAN sisteminde PHP ile SQL veritabanının nasıl birlikte çalıştığını, şifrelerin nasıl güvenli bir şekilde saklandığını ve arka planda dönen temel süreçleri basit ve anlaşılır bir dille açıklamaktadır.

---

## Temel Kavramlar: PHP ve SQL Nedir? (En Basit Anlatımla)

Sitenin çalışmasını bir restorana benzetebiliriz:

### 1. PHP Nedir? (Restoranın Garsonu ve Şefi)
* **Rolü:** Düşünmek, hesap yapmak, karar vermek ve işleri organize etmek.
* **Nasıl Çalışır:** Ziyaretçi siteye girdiğinde PHP hemen devreye girer. İstekleri dinler, güvenlik kontrollerini yapar, resimleri sıkıştırıp buluta yükler ve sayfayı hazırlayıp ziyaretçinin tarayıcısına gönderir.
* **Hafıza Durumu:** PHP'nin hafızası geçicidir. Ziyaretçi sayfayı kapatıp gittiği an PHP yaptığı her şeyi unutur. Bu yüzden kalıcı bilgilere (makaleler, kullanıcılar vb.) ihtiyaç duyduğunda SQL'e (veritabanına) başvurur.

### 2. SQL / Veritabanı Nedir? (Restoranın Kileri / Arşiv Odası)
* **Rolü:** Tüm verileri düzenli, güvenli ve kalıcı bir şekilde saklamak.
* **Nasıl Çalışır:** İçerisinde düzenli klasörler (bunlara **tablo** diyoruz, örn: `articles` makale tablosu, `admins` yönetici tablosu) bulunur. Veritabanı, kendisine teslim edilen bilgileri yıllarca hiç bozmadan saklar.
* **İletişim Dili (SQL Sorguları):** PHP ile veritabanı konuşmak için **SQL adı verilen ortak bir dili** kullanırlar. PHP, veritabanına *"Bana şu makaleyi getir"* demek için SQL dilinde yazılmış özel cümleler gönderir. 
  * Örn: `SELECT * FROM articles` ifadesi veritabanına *"Makaleler tablosundaki her şeyi seç ve bana getir"* komutudur.

---

## 1. Veritabanını Manuel Olarak İnceleme (phpMyAdmin)

Lokal geliştirme ortamında Docker çalışırken, veritabanına doğrudan görsel bir arayüz üzerinden müdahale edebilir ve inceleyebilirsiniz.

* **Erişim Adresi:** `http://localhost:8000`
* **Kullanıcı Adı:** `root`
* **Şifre:** `root`

phpMyAdmin arayüzüne girdikten sonra sol menüden `fezadano5_site` veritabanını seçerek tüm tabloları (`admins`, `articles`, `categories` vb.) inceleyebilir, elle veri ekleyebilir, silebilir veya güncelleyebilirsiniz.

---

## 2. Admin Giriş Süreci (Şifre Girildiğinde Ne Olur?)

Yönetim paneline şifrenizi girip "Giriş Yap" butonuna bastığınızda arka planda sırasıyla şu adımlar gerçekleşir:

### A. Şifrelerin Güvenli Saklanması (Bcrypt Algoritması)
Veritabanında şifreler asla açık metin halinde saklanmaz. PHP'nin `password_hash()` fonksiyonu ile **Bcrypt** algoritması kullanılarak tek yönlü bir karma (özet/hash) haline getirilir. 
* Örnek: `Antigravity2026!` şifresi veritabanında `$2y$10$DXiPNKH2mkNybasO3/r.2e1Po3Z0v6q0...` şeklinde görünür.
* **Tuzlama (Salt):** Her şifre için arka planda benzersiz ve rastgele 22 karakterli bir "tuz" (salt) üretilir. Bu sayede iki farklı kullanıcı aynı şifreyi seçse bile veritabanındaki hash'leri tamamen farklı olur.
* **Yapay Yavaşlatma (Cost):** Şifre, matematiksel olarak binlerce kez üst üste karıştırılır (zorluk derecesi 10'dur, yani $2^{10} = 1024$ tur döndürülür). Bu, hackerların deneme-yanılma (brute-force) saldırılarını bilgisayar işlemcilerini aşırı yorarak engeller.

### Önemli Ayrıntı: Rastgele Tuz (Salt) Nasıl Geri Bulunur? (En Basit Anlatımla)

Şifreniz ilk oluşturulduğunda üretilen o **rastgele tuz**, veritabanında saklanan uzun hash metninin **kendi içine gömülür**. 

**Sandviç Tarifi Benzetmesi:**
Bunu bir sandviç tarifi gibi düşünebiliriz:
1. Siz bir şifre belirlersiniz (bu bizim **sandviçimiz** olsun).
2. Sistem bu sandviçi korumak için rastgele gizli bir sos üretir (bu bizim **tuzumuz / sosumuz** olsun, örn: "Ballı Hardal").
3. Sistem bu bilgileri bir kağıda yazar: **"Gizli sosumuz Ballı Hardal'dır. Bu sosu döküp mikserde karıştırınca 300 gramlık bir karışım elde etmeliyiz."**
4. Bu tarifi bir kutuya koyup veritabanına kilitler (işte veritabanındaki o uzun `$2y$10$...` metni bu kutudur).

**Giriş Yaparken:**
1. Siz yeni bir sandviç getirip "Bu benim sandviçim" dersiniz.
2. Sistem kutuyu açar, tarifi okur: *"Aha! Bu sandviç için kullandığım gizli sos **Ballı Hardal**'mış"* (uzun metnin içinden tuzu cımbızla çeker).
3. Getirdiğiniz yeni sandviçe **Ballı Hardal** döküp mikserde karıştırır.
4. Elde ettiği karışımı tartar. Eğer tam **300 gram** gelirse, getirdiğiniz sandviçin gerçekten ilk günküyle aynı sandviç (şifre) olduğunu anlar ve kapıyı açar.

**Yani;** sistem tuzu tahmin etmeye çalışmaz. Veritabanında kayıtlı olan o uzun metnin içinden okur ve doğrulamayı o tuzla yapar.

### B. Giriş Adımları
1. **Deneme Sınırı Kontrolü:** PHP önce `login_attempts` tablosuna bakarak o IP adresinden son 3 saatte 3 kereden fazla hatalı giriş yapılıp yapılmadığını kontrol eder. Sınır aşıldıysa girişi engeller.
2. **Kullanıcıyı Arama (SQL):** PHP veritabanına sorar: 
   ```sql
   SELECT * FROM admins WHERE username = 'antigravity';
   ```
3. **Şifre Doğrulama:** PHP, girilen şifreyi veritabanından gelen hash içindeki "tuz" ve "maliyet" parametreleriyle tekrar karıştırır. Sonucu veritabanındaki hash ile karşılaştırır (`password_verify` fonksiyonu).
4. **Oturum Açma (Session):** Eşleşme başarılıysa PHP tarayıcıya geçici bir "oturum çerezi" (bilet) yazar. Artık tarayıcınız bu biletle sayfalar arasında gezinebilir.

---

## 3. Şifre Sıfırlama Süreci (Arka Planda Neler Oluyor?)

Şifrenizi unuttuğunuzda ve sıfırlama talep ettiğinizde gerçekleşen akış:

1. **Talep Girişi:** Kullanıcı e-posta adresini girer. PHP e-postanın geçerli bir `@fezadan.org` adresi olup olmadığını doğrular.
2. **Sıklık Kontrolü (Rate Limit):** Sistem, `admin_password_resets` tablosuna bakarak son **2 dakika** içinde bu yönetici için başka bir bağlantı oluşturulup oluşturulmadığını kontrol eder.
3. **Tek Kullanımlık Kod (Token) Üretimi:** PHP, tahmin edilmesi imkansız rastgele bir `Token` üretir. Bu kodun SHA-256 özeti (hash) `admin_password_resets` tablosuna kaydedilir ve geçerlilik süresi PHP saati üzerinden **15 dakika** olarak ayarlanır (`expires_at`).
4. **HTML E-posta Gönderimi:** E-posta, FEZADAN renk şemasına uygun modern bir HTML formatında hazırlanarak gönderilir. Alt kısma *"Lütfen bu e-postayı yanıtlamayınız..."* uyarısı yerleştirilir.
5. **Bağlantıya Tıklanması:** Kullanıcı gelen kutusundaki linke tıkladığında, tarayıcı uzun token parametresiyle siteye gelir. PHP veritabanına sorar:
   ```sql
   SELECT id, admin_id FROM admin_password_resets 
   WHERE token_hash = :hash AND expires_at > :php_time;
   ```
   *(Buradaki karşılaştırma tamamen PHP saati üzerinden yapıldığı için sunucu saat farklarından kaynaklı anında süre aşımı hataları yaşanmaz).*
6. **Şifre Güncelleme:** Link geçerliyse yeni şifreler istenir. Girilen şifre Bcrypt ile hash'lenerek `admins` tablosundaki kayıt güncellenir ve kullanılan tek kullanımlık token tablodan silinir.

---

## 4. Makale Ekleme ve Silme Akışı

### A. Makale Eklerken (Oluştururken)
1. **Medya Yönetimi (Cloudflare R2):** Kapak resmi yüklendiğinde, PHP dosyayı alır, optimize edip boyutunu küçültür ve Cloudflare R2 nesne depolama servisine yükler. Dosya Fezadan'ın kendi diskinde değil, Cloudflare CDN üzerinde saklanır.
2. **Adres Dostu Dönüşüm (Slug):** Makale başlığı (örn: "Yeni Güncelleme") URL formatına uygun hale getirilir ("yeni-guncelleme" yapılır). Buna **Slug** denir.
3. **Veritabanına Yazma (SQL):** PHP, makale bilgilerini SQL komutuyla `articles` tablosuna yazar:
   ```sql
   INSERT INTO articles (title, slug, content, image_url, ...) VALUES (...);
   ```
4. **Kategori İlişkisi:** Makalenin bağlandığı kategoriler, ara köprü tablosu olan `article_categories` tablosuna kaydedilir.
5. **Site Haritası Güncellemesi:** Veritabanına yazıldıktan sonra sistem geçici klasörde bir `.dirty` (harita güncellenmeli) dosyası oluşturur. Sistemdeki cron scripti bu dosyayı fark ederek `sitemap.xml` dosyasını yeniden üretir.

### B. Makale Silerken
1. **İlişkileri Temizleme:** Önce `article_categories` tablosundan makaleye ait tüm kategori bağları temizlenir.
2. **Kayıt Silme (SQL):** `articles` tablosundan makale kaydı tamamen silinir.
3. **Medya Silme:** Eğer makalede bir kapak görseli varsa, Cloudflare R2 üzerindeki dosya silinerek alan temizliği yapılır.
4. **Site Haritası Güncellemesi:** Siteden kaldırılan makale linkinin Google haritasından temizlenmesi amacıyla site haritası `.dirty` bayrağı tetiklenir.
