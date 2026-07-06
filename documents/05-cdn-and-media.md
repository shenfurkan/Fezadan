# 5. Cloudflare R2 ve Medya Yönetimi

## Temel Kavram: Medya Dosyaları Nereye Gidiyor?

Sitenin disk doluluk oranını minimize etmek, yüksek trafik anlarında I/O darboğazını önlemek ve bant genişliğini küresel olarak optimize etmek amacıyla Fezadan projesinde tüm medya varlıkları (görseller, PDF notlar) **Cloudflare R2** bulut depolama servisinde saklanır.

Cloudflare R2, AWS S3 API standartları ile tam uyumlu çalışır ve **Egress (veri çıkış) ücreti barındırmaz**. Bu sayede yüksek trafikli dosya indirme veya resim görüntüleme süreçlerinde ek bir trafik maliyeti oluşmaz.

---

## Hangi Dosyalar R2'ye Yüklenir?

| Tür | R2'deki Klasör | Maksimum Boyut | Yükleme Sayısı |
|-----|---------------|----------------|----------------|
| Makale kapak görseli | `/uploads/covers/` | 5 MB | 1 adet |
| Makale içeriğindeki görsel | `/uploads/content/` | 5 MB | Sınırsız |
| Yazar profil fotoğrafı | `/uploads/authors/` | 5 MB | 1 adet |
| Otomatik OpenGraph görseli | `/uploads/og/` | 5 MB | 1 adet (Makale başına) |
| PDF ders notu | `/notlar/` | 50 MB | Sınırsız |

---

## Upload Pipeline (Yükleme Süreci) — Adım Adım

Medya dosyası yüklendiğinde [app/Core/Upload.php](file:///d:/Fezadan/app/Core/Upload.php) ve [app/Core/R2Storage.php](file:///d:/Fezadan/app/Core/R2Storage.php) kütüphaneleri arka planda şu güvenlik ve optimizasyon işlemlerini yürütür:

### Adım 1 — Dosya ve MIME Tipi Denetimi
- **Uzantı Sınırları:** Sadece izin verilen uzantılar (`jpg`, `jpeg`, `png`, `webp`, `gif`, `pdf`) kabul edilir.
- **Magic Bytes Doğrulaması:** Dosya içeriği `getimagesize()` ile taranır. Uzantısı değiştirilerek yüklenmeye çalışılan sahte dosyalar engellenir. PDF dosyaları için `%PDF-` dosya başlığı (magic bytes) kontrol edilir.
- **HTTP POST Doğrulaması:** `is_uploaded_file()` kullanılarak yerel dosya sızdırma girişimleri önlenir.

### Adım 2 — Geçici Çalışma Alanı ve Metadata Temizliği
Sunucu üzerinde rastgele isimli geçici bir klasör (`sys_get_temp_dir() . '/fezadan_uploads_...'`) oluşturulur.
- **Güvenlik İçin Yeniden Kodlama (Re-encode):** Görseller Imagick (veya GD fallback) aracılığıyla yeniden kodlanarak açılır. `stripImage()` komutu ile dosyanın içerdiği tüm EXIF/metadata bilgileri temizlenir. Bu sayede görsel içerisine gizlenmiş zararlı PHP kod parçaları (Steganography veya Polyglot dosyalar) tamamen yok edilir.

### Adım 3 — WebP Dönüştürme
Boyut optimizasyonu sağlamak amacıyla görseller, Imagick veya GD yardımıyla kaliteden ödün vermeden %82 sıkıştırma oranıyla modern **WebP** formatına dönüştürülür. WebP desteği bulunmayan kısıtlı sunucu ortamlarında sistem otomatik olarak orijinal biçimi (JPEG/PNG) korur ve hata vermeden çalışmaya devam eder.

### Adım 4 — Cloudflare R2'ye Yükleme
Hazırlanan dosya AWS S3 PHP SDK kullanılarak R2 depolama alanına gönderilir. Yükleme esnasında dosyaya `Cache-Control: public, max-age=31536000, immutable` (1 yıl önbellek) başlığı eklenir. Böylece tarayıcılar görseli bir kez indirdikten sonra tekrar sunucuya sormaz.

### Adım 5 — Geçici Dosyaları Temizleme & URL Çözümleme
R2 yüklemesi tamamlandıktan sonra sunucu diskindeki tüm geçici dosyalar temizlenir. Medya varlığının yolu veritabanına `/uploads/covers/dosya-adi.webp` biçiminde kaydedilir. Kullanıcıya gösterilirken `Upload::assetUrl()` fonksiyonu yardımıyla CDN adresi eklenir.

**CDN Fallback Önceliği:** `CDN_URL` → `R2_PUBLIC_URL` → `SITE_URL`

---

## R2Storage Sınıfı — Depolama Yöneticisi

[app/Core/R2Storage.php](file:///d:/Fezadan/app/Core/R2Storage.php) sınıfı Singleton kalıbıyla R2 API bağlantısını tekilleştirir.

### streamView() ve streamDownload() — Range Desteği
PDF notları sunulurken istemcilerin veya tarayıcıların PDF'i sayfa sayfa talep edebilmesi için **HTTP Range** desteği sunulur. 
- İstemci `Range: bytes=0-1024` isteği gönderdiğinde, `R2Storage` tüm PDF dosyasını indirmek yerine sadece ilgili byte aralığını R2'den çekerek tarayıcıya iletir.
- Bu sayede 50-100 MB boyutundaki büyük PDF ders notları bile mobil cihazlarda anında açılır ve sunucu bant genişliği korunur.

### updateNote() — Not PDF Güncelleme
Admin panelinde bir not güncellenirken yeni bir PDF yüklendiğinde:
1. Eski PDF dosyasının R2 depolama alanındaki fiziksel kaydı `deleteFile()` ile tamamen silinir.
2. Yeni PDF R2'ye yüklenir ve güncel dosya boyutu (`file_size`) veritabanına işlenir.

---

## Yerel PDF.js Entegrasyonu
Ziyaretçilerin PDF dosyalarını ek bir tarayıcı eklentisine ihtiyaç duymadan görüntüleyebilmesi için Mozilla tarafından geliştirilen **PDF.js** aracı yerel olarak entegre edilmiştir. Güvenlik kuralları (CSP) nedeniyle PDF.js kütüphaneleri dış sunuculardan değil, sitenin kendi varlık klasöründen yüklenir.
