# 10. SSS — Sık Sorulan Sorular

---

## Kurulum ve Konfigürasyon Sorunları

### S: Hostinge yükledim, beyaz sayfa görüyorum. Ne yapmalıyım?
1. `.env` dosyası **var mı** kontrol et (proje kökünde).
2. PHP sürümü **8.0+** mı? (cPanel → Select PHP Version).
3. Veritabanı oluşturuldu mu ve `.env`'deki bilgiler doğru mu?
   - cPanel kullanıcı adı ve veritabanı adının başına otomatik olarak prefix ekler, kontrol edin.
4. `vendor/autoload.php` var mı? (`composer install` yapıldı mı?).
5. cPanel → **Error Log**'a bakın.

### S: Veritabanı bağlantı hatası alıyorum.
cPanel genellikle veritabanı adına ve kullanıcı adına **prefix ekler**:
- Veritabanı adı: `kullaniciadi_veritabani_adi` (sadece `veritabani_adi` değil).
- Kullanıcı adı: `kullaniciadi_kullanici` (sadece `kullanici` değil).
- phpMyAdmin'den bağlantıyı test edin.

### S: 404 sayfa bulunamadı hatası alıyorum.
- **Document Root yanlış ayarlanmış olabilir.** Doğru yol canlı kurulumda `/home/fezadano5/public_html` olmalıdır.
- **Apache mod_rewrite kapalı olabilir.** cPanel'de kontrol edin veya hosting desteğine sorun.

### S: 500 Internal Server Error alıyorum.
cPanel → **Error Log** simgesine tıklayın. En yaygın nedenler:
- PHP extension eksik (pdo_mysql, mbstring, gd).
- Yanlış PHP sürümü.
- `.htaccess`'te hatalı yönerge.

---

## Çeviri ve Dil Yönetimi

### S: Arayüz metinlerini nasıl çevirebilirim?
Arayüzdeki statik metinler (butonlar, menüler, etiketler) [app/Config/config.php](file:///d:/Fezadan/app/Config/config.php) içerisindeki `__()` fonksiyonu ile çevrilir.
- Çeviriler [app/Translations/](file:///d:/Fezadan/app/Translations/) dizinindeki `tr.php` ve `en.php` dosyalarından okunur.
- Görünümlerde (Views) `<?= __('nav.home') ?>` şeklinde kullanılır.
- Eğer talep edilen dilde çeviri bulunamazsa, sistem otomatik olarak Türkçe (`tr.php`) dosyasına düşer (Fallback).

### S: Makale çevirilerini nasıl birbirine bağlarım?
Admin panelinde bir makale düzenlenirken veya oluşturulurken, diğer dildeki karşılığı olan makale `translation_of` seçeneğinden seçilerek kaydedilir. Bu sayede ziyaretçiler yazıyı okurken diller arasında geçiş yapabilir. Site haritasında da bu sayede otomatik olarak çift yönlü `hreflang` etiketleri oluşturulur.

---

## Güvenlik ve Nonce Yapısı

### S: Sayfaya eklediğim JavaScript kodu çalışmıyor, neden?
Sitede uygulanan Content-Security-Policy (CSP) kuralları gereği, `nonce` değeri taşımayan inline scriptlerin çalıştırılması engellenir.
- Sayfaya ekleyeceğiniz inline `<script>` etiketlerine `nonce` özniteliğini eklemeniz gerekir:
  ```html
  <script nonce="<?= CSP_NONCE ?>">
      console.log('Bu kod çalışır.');
  </script>
  ```
- `CSP_NONCE` sabiti her istek için [public_html/index.php](file:///d:/Fezadan/public_html/index.php) tarafından otomatik üretilir.

### S: .env dosyam web'den okunabilir mi?
Hayır. [public_html/.htaccess](file:///d:/Fezadan/public_html/.htaccess) dosyası içerisinde yer alan güvenlik kuralları tarayıcıdan `.env` dosyasına gelen istekleri doğrudan bloke eder ve 403 Forbidden yanıtı döner.

---

## Zamanlama ve Bakım

### S: Planlı makalelerim zamanı gelmesine rağmen yayınlanmıyor.
Planlı makalelerin (`status = 'scheduled'`) yayına alınabilmesi için zamanlanmış cron betiğinin çalışıyor olması gerekir. cPanel → **Cron Jobs** alanına şu betiği dakika başı çalışacak şekilde eklediğinizden emin olun:
```bash
*/5 * * * * /usr/local/bin/php /home/fezadano5/cron/publish-scheduled.php >/dev/null 2>&1
```

### S: Veritabanı yedeklerimi nereden indirebilirim?
Veritabanı yedekleri [cron/backup-db.php](file:///d:/Fezadan/cron/backup-db.php) betiği aracılığıyla her gün sıkıştırılarak Cloudflare R2 depolama alanınızdaki `backups/` klasörüne yüklenir. Cloudflare paneline girerek yedek dosyalarınızı indirebilirsiniz. 7 günden eski yedekler otomatik olarak temizlenir.
