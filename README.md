<p align="center">
  <img src="darkthemelogo.png" width="200" alt="Fezadan Logo">
</p>

# Fezadan.org PHP MVC CMS Motoru

Fezadan, PHP tabanlı özel bir MVC (Model-View-Controller) mimarisi üzerine inşa edilmiş, hafif, hızlı ve modern bir içerik yönetim sistemi (CMS) ve blog motorudur. Gelişmiş yönetim paneli ve Tailwind CSS ile hazırlanmış duyarlı (responsive) ön yüzü sayesinde içeriklerinizi kolayca yönetmenizi sağlar.

## Özellikler

- **Özel MVC Mimarisi:** İhtiyaca yönelik, esnek ve hafif bir çekirdek (`Core`) ve yönlendirme (routing) yapısı.
- **Kapsamlı Yönetim Paneli:** Makale ekleme/düzenleme, yazar ve kategori yönetimi, detaylı profil ayarları.
- **Modern Arayüz:** Tailwind CSS ile geliştirilmiş modern tasarım, özelleştirilmiş fontlar ve hızlı sayfa yüklemeleri.
- **SEO Uyumlu:** Dahili `sitemap.xml` ve `robots.txt` desteği ile arama motoru optimizasyonu.
- **Güvenlik & Yetkilendirme:** Yönetim paneli erişim kısıtlamaları ve `.htaccess` tabanlı dizin korumaları.
- **Kolay Deploy:** `.cpanel.yml` ve `git-deploy.php` entegrasyonları ile sunucuya otomatik/hızlı sürüm yükleme.

## Kullanılan Teknolojiler

- **Backend:** PHP (Özel MVC)
- **Frontend:** HTML, Tailwind CSS, JavaScript
- **Veritabanı:** MySQL
- **Paket Yönetimi:** NPM (Tailwind derlemeleri için)

## Düşük Dışa Bağımlılık ve Performans

Proje, modern web uygulamalarındaki aşırı dışa bağımlılığı (CDN, Tracker vb.) azaltmak ve gizliliği artırmak amacıyla **self-sufficient (kendi kendine yeten)** bir yapıda tasarlanmıştır:

- **Lokal Sayaç Sistemi:** Ziyaretçi ve okunma istatistikleri için harici takip servisleri (Google Analytics vb.) yerine, PHP tabanlı ve token doğrulamalı yerel bir sayaç algoritması kullanılır.
- **Yerel Asset Yönetimi:** CSS, JavaScript ve tüm yazı tipleri (Fonts) dış kaynaklı linkler üzerinden çağrılmak yerine, doğrudan `/public_html/assets` ve `/cdn` dizinlerinden lokal olarak servis edilir.
- **Gizlilik Odaklı:** Harici script yüklemelerini minimize ederek sayfa yükleme hızlarını optimize eder ve güvenli bir kullanıcı deneyimi sunar.

## Dizin

Proje güvenliği artırmak amacıyla sistem ve açık dizin olarak ikiye ayrılmıştır:

- **`app/`:** Projenin beyni. Tüm Controller (Kontrolcüler), View (Görünümler) ve Core (Çekirdek) dosyaları burada yer alır. Dışarıdan doğrudan erişime kapalıdır.
- **`public_html/`:** Web sunucunuzun okuyacağı kök dizindir. CSS, JS, fontlar, görseller ve gelen istekleri karşılayan ana `index.php` dosyasını barındırır.

## Kurulum

1. Repoyu bilgisayarınıza veya sunucunuza klonlayın:
   ```bash
   git clone https://github.com/shenfurkan/fezadan.git
   ```
2. Web sunucunuzun (Apache, Nginx vb.) kök (Document Root) dizinini projedeki `public_html/` klasörüne yönlendirin.
3. Gerekli veritabanı ayarlarını projedeki ilgili veritabanı bağlantı dosyasından yapılandırın. (Eğer projenizde bir `.sql` dosyası varsa, bunu veritabanınıza içe aktarın.)
4. Tailwind CSS üzerinde değişiklik yapmak isterseniz node modüllerini kurup derleyin:
   ```bash
   npm install
   npm run build
   ```

---

## Geliştiriciler

- **Furkan Şen:** Projenin tasarım kısmı ve ana çekirdek yapısının (MVC Core) oluşturulması.
- **Suat Işık:** Geri kalan tüm özellik eklemeleri, modül geliştirilmeleri, bug (hata) düzeltmeleri ve ince detayların tamamlanması.

Yaklaşık 2 aylık titiz bir gizli geliştirme sürecinin ardından, projeyi tüm modülleriyle birlikte yayınlama kararı aldık. Fezadan, açık kaynak dünyasına ve özgür yazılım topluluğuna nitelikli bir alternatif sunmak amacıyla paylaşılmıştır.
