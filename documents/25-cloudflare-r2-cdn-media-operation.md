# Cloudflare R2 ve CDN Medya Operasyonu

Bu dokuman Fezadan'da medya dosyalarinin nasil saklandigini, hangi env
degiskenleriyle R2/CDN'e baglanildigini ve canli hata durumunda nereden kontrol
edilecegini anlatir.

## Mimari

Medya akisi su sekildedir:

```txt
Admin panel / uygulama upload
  -> PHP uygulamasi
  -> gecici/local isleme
  -> R2Storage
  -> Cloudflare R2 bucket
  -> R2 public/custom domain
  -> CDN_URL / cdn.fezadan.org
  -> kullanici tarayicisi
```

Uygulama dosyayi sadece veritabanina yazmaz. Dosyanin public URL'i, R2 bucket ve
CDN ayarlarina gore uretilir. Bu nedenle R2 veya CDN bozulursa makale icerigi
durabilir, fakat asla ilk refleks olarak veritabani silinmemelidir.

## Env Degiskenleri

| Degisken | Rol |
|---|---|
| `R2_ACCOUNT_ID` | Cloudflare hesap kimligi |
| `R2_ACCESS_KEY_ID` | R2 API access key |
| `R2_SECRET_ACCESS_KEY` | R2 API secret key |
| `R2_BUCKET_NAME` | Medya dosyalarinin yazildigi bucket |
| `R2_PUBLIC_URL` | Bucket veya custom domain public base URL |
| `CDN_URL` | Site icinde tercih edilen CDN base URL |

`R2_SECRET_ACCESS_KEY` ve benzeri degerler repo icine yazilmaz. `.env.example`
sadece degisken adlarini ve placeholder mantigini gostermelidir.

### Local (Yerel) Test Ortamında R2 Ayarı

Local test yaparken (Docker kullanırken) yüklenen görsel ve PDF dosyalarını görebilmek için projenin kök dizinindeki `.env` dosyasına mutlaka kendi R2 anahtarlarınızı girmelisiniz. Aksi halde tüm medya dosyaları 404 verecek veya kırık görünecektir.

**.env Örneği:**
```env
R2_ACCOUNT_ID="cloudflare_hesap_id_niz"
R2_ACCESS_KEY_ID="r2_erisim_anahtariniz"
R2_SECRET_ACCESS_KEY="r2_gizli_anahtariniz"
R2_BUCKET_NAME="fezadan-storage"
R2_PUBLIC_URL="https://cdn.fezadan.org"
```

*Not: Kendi Cloudflare panelinizden **R2 > Manage R2 API Tokens** menüsüne giderek "Object Read & Write" yetkisiyle bir token oluşturabilir ve bu bilgileri local test ortamınıza alabilirsiniz. Docker'ı yeniden başlatmanıza gerek kalmaz, sadece sayfayı yenilediğinizde çalışacaktır.*

## CDN_URL ve R2_PUBLIC_URL Farki

`R2_PUBLIC_URL` R2 tarafindaki public erisim tabanidir. Bu bazen Cloudflare'in
verdigi R2 public URL'i, bazen de custom domain olabilir.

`CDN_URL` uygulamanin public sayfalarda kullanmak istedigi medya tabanidir.
Canlida bu genelde:

```txt
https://cdn.fezadan.org
```

seklinde dusunulur.

Pratik kural:

- Upload ve storage istemcisi R2 degiskenlerine bakar.
- Public HTML tarafinda gosterilecek URL icin `CDN_URL` tercih edilir.
- `CDN_URL` bos veya hataliysa uygulama `R2_PUBLIC_URL` veya local fallback
  davranisina donebilir; bu davranis koddan dogrulanmalidir.

## Dosya Gruplari

Projede medya genelde su islevsel gruplara ayrilir:

| Grup | Ornek kullanim |
|---|---|
| Makale kapaklari | article image / cover |
| Makale icerik gorselleri | editor upload |
| Notlar dosyalari | PDF veya not dosyasi |
| OG gorselleri | sosyal medya paylasim gorseli |
| Yazar gorselleri | profil/avatar |
| Portfolio gorselleri | portfolio modulu assetleri |

Bu gruplarin tamaminda ayni prensip gecer: DB kaydi dosyanin ne oldugunu bilir,
R2/CDN ise dosyanin nereden servis edilecegini belirler.

## Cache Mantigi

Medya dosyalari statiktir ve cachelenebilir. Dinamik endpointler cachelenmemelidir.

Cache icin uygun:

- Gorseller
- PDF/not dosyalari
- Versiyonlu statik assetler
- WebP/thumbnail ciktilari

Cache icin riskli:

- `/yonetim`
- Login/logout
- CSRF veya session bagimli endpointler
- Captcha token endpointleri
- Deploy veya bakim endpointleri

Cloudflare kural yazarken URL pattern ile bu ayrim yapilmalidir. `cdn.fezadan.org`
statik medya icin agresif cache alabilir; `fezadan.org` altindaki dinamik sayfalar
daha dikkatli ele alinmalidir.

## R2 Erisilemezse Ne Bozulur?

R2 erisim problemi su belirtileri uretir:

- Yeni medya yukleme basarisiz olur.
- Var olan gorseller 404/403 doner.
- Admin health check `R2 BAGLANTISI` uyarisi verebilir.
- OG image veya makale image kontrolleri eksik gorunebilir.
- Notlar sayfasinda PDF/gorsel erisimi bozulabilir.

Bu durum veritabani problemiyle karistirilmamalidir. Once storage ve DNS/CDN
kontrol edilmelidir.

## Ilk Kontrol Listesi

```bash
# Env isimleri mevcut mu?
grep -E "R2_|CDN_URL" .env

# CDN host DNS cozuyor mu?
nslookup cdn.fezadan.org

# Public medya URL'i browser UA ile cevap veriyor mu?
curl -I -A "Mozilla/5.0" https://cdn.fezadan.org/<ornek-dosya>
```

cPanel tarafinda PHP loglari ve uygulama loglari da kontrol edilmelidir. R2 API
yetkisi bozulduysa uygulama genelde upload aninda hata uretir; CDN/DNS bozulduysa
dosya yuklenmis olsa bile public URL calismaz.

## Guvenlik Kurallari

- R2 access key ve secret asla commit edilmez.
- Public bucket sadece public olmasi gereken medya icin kullanilir.
- Private dosyalar public CDN URL'i ile servis edilmemelidir.
- Upload edilen dosyalarda mime/type ve boyut kontrolu uygulama tarafinda kalir.
- Cloudflare cache purge problemi cozmezse R2 object varligi ayrica kontrol edilir.

## Operasyon Notu

Bir medya sorunu geldiginde karar agaci:

1. DB kaydi var mi?
2. Dosya R2 bucket icinde var mi?
3. `R2_PUBLIC_URL` dogru hostu gosteriyor mu?
4. `CDN_URL` public sayfada dogru uretiliyor mu?
5. `cdn.fezadan.org` DNS kaydi dogru mu?
6. Cloudflare cache eski/bozuk cevabi tutuyor mu?

