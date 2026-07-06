# 4. Yönetim Paneli

## Nerede ve Nasıl Giriş Yapılır?

- **Adres:** `https://fezadan.org/yonetim`  
- **Giriş ekranı:** `https://fezadan.org/yonetim/login`

**Güvenlik Notu:** `/admin`, `/panel`, `/dashboard` gibi yaygın yönetici paneli yolları **404 hatası** döndürür. Otomatik bot taramaları bu yolları dener, bulamaz ve geçer. Gerçek admin yolu sadece `/yonetim`'dir.

---

## Yapılabilecek Tüm İşlemler

[app/Controllers/AdminController.php](file:///d:/Fezadan/app/Controllers/AdminController.php) admin paneli giriş, yetki ve ortak koruma akışını yönetir. Makale, not, passkey, portfolio ve sistem işlemleri `app/Controllers/Admin/` altındaki trait dosyalarına bölünmüştür.

### 1. Makale Yönetimi
- **Oluşturma & Düzenleme:** Başlık, içerik, yazar, kategori, dil ve kapak görseli alanları yönetilir.
- **Planlı Yayınlama:** Yayın tarihi ileriye dönük ayarlandığında, durum otomatik olarak `'scheduled'` yapılır ve zamanı geldiğinde cron betiğince otomatik olarak yayına alınır.
- **SEO ve Sosyal Medya:** SEO başlığı, açıklaması düzenlenebilir. "OG Görsel Üret" butonu tetiklendiğinde [app/Core/OgImage.php](file:///d:/Fezadan/app/Core/OgImage.php) sınıfı GD/SVG kullanarak otomatik bir OpenGraph banner'ı üretir ve makalenin `og_image` R2 yoluna kaydeder.
- **Çeviri İlişkisi:** Türkçe ve İngilizce makaleler arasında `translation_of` sütunu üzerinden çift yönlü dil geçiş bağlantısı tanımlanır.

### 2. Kategori ve Yazar Yönetimi
- **Kategori İşlemleri:** Yeni kategori oluşturma, silme işlemleri.
- **Yazar Profili:** Yazar adı, biyografisi, profil görseli ve JSON formatında sosyal ağ bağlantıları yönetilir.

### 3. PDF Not Yönetimi
- **PDF Yükleme:** Akademik veya ders notu PDF dosyaları doğrudan Cloudflare R2'ye yüklenir.
- **Not Güncelleme:** `updateNote()` metodu yardımıyla eski PDF dosyaları yenileriyle değiştirilebilir. Yeni PDF yüklenirse eski dosya R2'den otomatik olarak temizlenir ve `file_size` güncellenir.

### 4. Sistem ve Loglar
- **Sitemap Tetikleme:** Arama motorlarının sitenizi güncel indekslemesi için XML site haritası oluşturma işlemi tetiklenebilir.
- **Admin Eylem Kayıtları:** Panelde yapılan her işlem (makale silme, giriş denemeleri vb.) JSON formatında `logs/admin.log` dosyasına yazılır ve 1MB boyut sınırında otomatik arşivlenir.

---

## Giriş Güvenliği — Constructor Katmanı

Yönetim denetleyicisi çalıştırıldığında constructor düzeyinde şu kontroller gerçekleştirilir:

### 1. CLI Modu İstisnası
```php
if (PHP_SAPI === 'cli') {
    return;  // Cron betikleri için giriş zorunluluğunu atla
}
```
Zamanlanmış cron görevlerinin (sitemap üretimi, zamanlı yayın) CLI modunda çalışmasına izin verilir.

### 2. Çoklu Yönetici Rolleri
Yöneticiler, `admins.role` sütununa göre üç seviyede yetkilendirilir:
- **`superadmin`:** Tüm sistem üzerinde tam yetkilidir.
- **`editor`:** İçerik, yazar ve PDF notları düzenleyebilir ancak yeni yazar veya admin hesaplarını yönetemez.
- **`viewer`:** Sistemi sadece görüntüleyebilir. Herhangi bir veritabanı veya dosya silme/güncelleme (yazma) işlemi yapması engellenmiştir.

### 3. Oturum & Sabitleme Koruması
Başarılı giriş anında `session_regenerate_id(true)` çağrılarak oturum ID'si sıfırlanır (Session Fixation koruması). Session cookie'leri HTTPOnly ve Secure bayraklarıyla JavaScript erişimine kapatılır.

### 4. Hız Sınırlaması (Brute-Force)
Aynı IP'den 3 saat içinde 3'ten fazla başarısız giriş denemesi yapılırsa, o IP adresi 3 saatliğine bloke edilir. IP adresleri, günlük tuzlu hash biçiminde saklandığından ertesi gün otomatik olarak blokları kalkar.

### 5. CSRF (POST Yazma) Kontrolleri
Veritabanı veya depolama üzerinde değişiklik yapan tüm POST metotları [app/Controllers/AdminController.php](file:///d:/Fezadan/app/Controllers/AdminController.php) içerisindeki `$writeMethods` dizisinde listelenir. Bu listede yer alan bir metot çağrıldığında [app/Core/Csrf.php](file:///d:/Fezadan/app/Core/Csrf.php) doğrulaması çalıştırılarak form sahteciliği önlenir.

### 6. Parola Sıfırlama Politikası
Admin parolası sıfırlama işlemi sıkı kurallarla korunur:
- Yalnızca `@fezadan.org` e-posta adresine sahip admin hesapları sıfırlama talebinde bulunabilir.
- E-posta sıfırlama bağlantıları sunucunun yerel `mail()` fonksiyonu ile `info@fezadan.org` üzerinden iletilir.
- Sıfırlama token'ları veritabanında açık metin olarak değil, cryptographically güvenli `token_hash` biçiminde tutulur.
- Parola sıfırlama bağlantıları 5 dakika içinde geçerliliğini yitirir ve her admin hesabı için sıfırlama isteği gönderme sıklığı 10 dakikada 1 eylem olacak şekilde rate-limit edilir.

---

## Dashboard

Dashboard sayfası `AdminController::dashboard()` metodundan gelir. Panel üzerinde:

- Sistem durumu ve güncelleme bildirimleri kaldırılmıştır.
- LiteCaptcha durum kartı kaldırılmıştır.
- Terminal/scan görünümü yalnızca `?scan=1` query parametresi ile aktiftir ve fake gecikme animasyonu içermez.
- Yama notu (patch) sistemi kaldırılmıştır; `create-patch` endpoint'i ve `patches` veritabanı sorgusu mevcut değildir.

---

## Makale Oluşturma Sayfası (`/yonetim/create`)

Sayfa [app/Views/admin/create.php](file:///d:/Fezadan/app/Views/admin/create.php) ve [public_html/assets/js/admin-editor.js](file:///d:/Fezadan/public_html/assets/js/admin-editor.js) tarafından yönetilir.

### Kaldırılan Özellikler

- **Şablon ekleme (`templateSelect`):** Makale şablon dropdown ve ilişkili işleyici kaldırılmıştır.
- **Otomatik kayıt (autosave):** `bindAutosave` timer sistemi ve `autosaveIndicator` DOM elementi kaldırılmıştır.

### Stabilizasyon İyileştirmeleri

- Word import (`#wordImportTrigger`): `DataTransfer` desteklenmediğinde `items` array iterasyonu ile safe fallback uygulanır.
- Çoklu yazar checkbox kalite kontrolü: Boş gönderim engellenmiştir.
- Geçersiz kapak görseli önizlemesi temizlenir.
- Submit sırasında taslak (`#draftBtn`) ve yayınla (`#submitBtn`) butonları birlikte disable edilir.
- Preview shortcut: Sayfa `hidden` class durumuna göre doğru çalışır.
- Sağ panel taşması azaltılmış, kalite paneli butonlarla çakışmayı önleyecek şekilde konumlandırılmıştır.
- Ana yazı alanı (`#summernote`) genişletilmiş, iç içe border karmaşası azaltılmıştır.

### Korunan Selector'lar

Aşağıdaki ID'ler korunur ve değiştirilmemelidir:
- `#summernote` — editör alanı
- `#wordImportTrigger` — Word import butonu
- `#manualSlug` — manuel slug girişi
- `#draftBtn` — taslak kaydetme
- `#submitBtn` — yayınlama
- `#uploadForm` — kapak görseli formu
