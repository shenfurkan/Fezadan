# 3. Veritabanı

## Veritabanı Nedir? (Temel Kavram)

Veritabanı, yapılandırılmış verileri düzenli bir şekilde saklayan, sorgulayan ve yöneten bir sistemdir. Web uygulamalarında kullanıcı bilgileri, içerikler, ayarlar ve ilişkili veriler veritabanında tutulur.

Bu projede **MySQL** (veya MariaDB) kullanılır. MySQL, dünyanın en popüler açık kaynaklı ilişkisel veritabanı yönetim sistemlerinden biridir. `utf8mb4` karakter seti ile çalışır, bu sayede Türkçe karakterler (İ, Ğ, Ü, Ş, Ç, Ö), emoji'ler ve diğer tüm Unicode karakterler sorunsuz şekilde saklanabilir.

**InnoDB Depolama Motoru:** Projedeki tüm veritabanı tabloları, veri bütünlüğünü korumak, yabancı anahtar (Foreign Key) ilişkilerini desteklemek ve satır seviyesinde kilitleme (Row-level locking) ile eşzamanlı işlemleri (Transactions) güvenle yürütmek amacıyla **InnoDB** motoruna dönüştürülmüştür.

---

## Tablolar — Verilerin Saklandığı Yapılar

Tablolar, satırlar (kayıtlar) ve sütunlardan (alanlar) oluşur. Fezadan projesinde öne çıkan temel tablolar:

### Ana İçerik Tabloları

#### articles — Makaleler
Makale içeriklerinin ve yayın durumlarının tutulduğu en kritik tablodur.
- **`id`** (INT, Primary Key): Makale numarası.
- **`title`** (VARCHAR): Makale başlığı.
- **`slug`** (VARCHAR): URL dostu kısa ad (Benzersiz).
- **`content`** (TEXT): HTML formatında içerik.
- **`author_id`** (INT): Yazara referans veren Foreign Key.
- **`status`** (ENUM): `'draft'`, `'published'`, `'scheduled'` (Planlı yayın) değerlerini alır.
- **`lang`** (VARCHAR): Dil kodu (`TR` veya `EN`).
- **`reads`** (INT): Okunma sayısı.
- **`image_url`** (VARCHAR): Kapak görselinin R2 yolu.
- **`seo_title`** (VARCHAR): SEO arama motoru başlığı.
- **`seo_description`** (TEXT): SEO arama motoru açıklaması.
- **`translation_of`** (INT): Makalenin diğer dildeki karşılığının ID'si.
- **`og_image`** (VARCHAR): Otomatik üretilen OpenGraph görselinin R2 depolama yolu.
- **`publish_at`** (DATETIME): Makalenin yayınlanacağı planlanan tarih.
- **`created_at`** (DATETIME): Makalenin sisteme girilme tarihi.

#### authors — Yazarlar
Makale yazarlarının listesi.
- **`id`** (INT, Primary Key): Benzersiz yazar numarası.
- **`name`** (VARCHAR): Yazar adı.
- **`slug`** (VARCHAR): URL dostu yazar adı (ör: `furkan`).
- **`bio`** (TEXT): Yazar biyografisi.
- **`image_url`** (VARCHAR): Profil fotoğrafı.
- **`social_links`** (JSON): Yazarın sosyal ağ bağlantıları (JSON formatında saklanır).

#### categories — Kategoriler
Geniş makale kategorileri (ör: "Bilim", "Teknoloji").
- **`id`** (INT, Primary Key): Kategori ID.
- **`name`** (VARCHAR): Kategori adı.
- **`slug`** (VARCHAR): URL dostu isim.

#### article_categories — Makale-Kategori İlişkisi
Bir makalenin birden fazla kategoriye ait olabilmesini sağlayan çoka-çok (Many-to-Many) ilişki tablosu. Primary key `(article_id, category_id)` ikilisidir.

---

### Güvenlik & Rate Limit Tabloları

#### admins — Yöneticiler
Admin paneline giriş hakkı olan yöneticiler.
- **`id`** (INT, Primary Key): Yönetici ID.
- **`username`** (VARCHAR): Giriş kullanıcı adı.
- **`name`** (VARCHAR): Arayüzde görünecek isim.
- **`password`** (VARCHAR): **Bcrypt** ile güvenli hash'lenmiş şifre.
- **`role`** (VARCHAR): Yetki düzeyi. `'superadmin'`, `'editor'` veya `'viewer'` değerlerini alabilir.
- **`last_login`** (DATETIME): Son başarılı giriş zamanı.

#### login_attempts — Giriş Denemeleri
Yönetici paneli giriş denemelerini takip ederek brute-force saldırılarını önlemek için kullanılır.
- **`ip_hash`** (VARCHAR): Günlük tuzlu IP adresi hash'i.
- **`username_hash`** (VARCHAR): Giriş yapılmaya çalışılan kullanıcı adının hash'lenmiş hali.
- **`attempt_time`** (DATETIME): Giriş deneme zamanı.

#### read_rate_limits — Okuma Limitleri
Aynı IP adresinden bir makalenin okunma sayısının günde en fazla 1 kez artırılmasını sınırlar.
- **`article_id`** (INT): Makale ID.
- **`ip_hash`** (VARCHAR): Günlük IP hash'i.
- **`read_date`** (DATE): Okuma yapılan tarih.

#### download_rate_limits — İndirme Limitleri
notlar subdomain'inden yapılan PDF indirme işlemlerini dakikada 3 indirme ile sınırlar.
- **`ip_hash`** (VARCHAR): Günlük IP hash'i.
- **`download_time`** (DATETIME): İndirme zamanı.

---

### PDF Not Tabloları

#### notes — Notlar
`notlar.fezadan.org` alt sitesinden sunulan PDF dosyaları.
- **`id`** (INT, Primary Key): Benzersiz not ID'si.
- **`title`** (VARCHAR): Not başlığı.
- **`slug`** (VARCHAR): URL kısa adı.
- **`r2_path`** (VARCHAR): Cloudflare R2'deki PDF nesne yolu (`notlar/abc.pdf`).
- **`file_size`** (BIGINT): PDF dosya boyutu (Byte).
- **`downloads`** (INT): Toplam indirilme sayısı.

#### portfolio_images — Portfolyo Görselleri
Portfolyo bölümündeki fotoğraf ve çizimlerin saklandığı tablo.
- **`id`** (INT, Primary Key)
- **`type`** (ENUM): `'photo'` veya `'drawing'`.
- **`image_url`** (VARCHAR): Görsel yolu.
- **`title_tr`** / **`title_en`** (VARCHAR): Başlıklar.
- **`display_order`** (INT): Sıralama numarası.

#### patch_notes — Yama Notları
Yönetim paneli anasayfasında (dashboard) listelenen sistem güncellemeleri ve değişiklik kayıtları.
- **`id`** (INT, Primary Key)
- **`version`** (VARCHAR): Yama sürümü (ör: `1.5`).
- **`title`** (VARCHAR): Yama başlığı.
- **`content`** (TEXT): Detaylı yama içeriği.
- **`author`** (VARCHAR): Yamayı yayınlayan admin.
- **`created_at`** (TIMESTAMP): Yayınlanma tarihi.

## Db.php — Veritabanı Bağlantı Yöneticisi

[app/Core/Db.php](file:///d:/Fezadan/app/Core/Db.php) dosyası, veritabanı bağlantılarını Singleton kalıbı (`Db::pdo()`) ile yönetir.

### PDO Güvenlik Ayarları
```php
self::$pdo = new \PDO($dsn, DB_USER, DB_PASS, [
    \PDO::ATTR_ERRMODE            => \PDO::ERRMODE_EXCEPTION,
    \PDO::ATTR_EMULATE_PREPARES   => false,   // Native Prepared Statements aktif
    \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
]);
```
- **`EMULATE_PREPARES = false`:** MySQL sunucusunun kendi prepared statement yapısını zorunlu kılar. SQL injection açıklarına karşı %100 güvenli koruma sağlar.

---

## Veritabanı Migration (Şema Güncellemeleri)

Geçmişte `Db::pdo()` üzerinden her HTTP isteğinde otomatik çalışan "Self-Healing" şema yapısı, performans ve güvenlik amacıyla kaldırılarak bağımsız bir CLI betiğine taşınmıştır. Veritabanı şemasında (tablo, sütun veya indeks ekleme) yapılacak her türlü yapısal güncelleme artık yalnızca komut satırı üzerinden yönetilir.

**Çalıştırma:**
```bash
php scripts/migrate-db.php
```

### CLI Migration Scriptinin Kapsadığı Kontroller:
1. **InnoDB Conversion:** Tüm tabloların MyISAM motorundan InnoDB motoruna dönüşümü.
2. **Eksik Sütunların Eklenmesi:** `articles` tablosuna `lang`, `seo_title`, `og_image` gibi sütunların, `admins` tablosuna ise `role` sütununun güvenle (idempotent olarak) eklenmesi.
3. **FULLTEXT Arama İndeksleri:** Arama performansını artırmak için `articles(title, content)` ve `notes(title, description)` sütunlarına `FULLTEXT` indekslerinin tanımlanması.

Bu sayede üretim ortamında (production) binlerce HTTP isteğinin her birinde "SHOW COLUMNS" gibi DDL (Data Definition Language) denetimlerinin çalıştırılması engellenmiş, gereksiz veritabanı kilitlenmelerinin (locking) önüne geçilmiştir.

---

## SQL Güvenliği — Prepared Statement

Saldırganların URL veya girdi kutularından zararlı SQL kodları enjekte etmesini önlemek amacıyla tüm sorgular parametrik olarak yürütülür:

```php
// GÜVENLİ YAKLAŞIM
$stmt = $pdo->prepare("SELECT * FROM articles WHERE slug = ?");
$stmt->execute([$slug]);
```
Bu sayede kullanıcıdan gelen veri SQL yorumlayıcısı tarafından kod olarak değil, doğrudan "değer" olarak işlenir ve SQL injection saldırıları tamamen engellenir.
Sıralama durumlarında (`ORDER BY`) parametre kullanılamadığından, girdi verileri güvenli bir whitelist dizisiyle denetlenir.
