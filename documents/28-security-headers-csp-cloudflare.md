# Security Headers, CSP ve Cloudflare

Bu dokuman Fezadan'in tarayici guvenlik basliklarini, Cloudflare proxy etkisini
ve LiteCaptcha iframe ihtiyacini birlikte anlatir.

## Neden Onemli?

Security header'lar uygulama kodu, browser ve Cloudflare arasindaki guvenlik
sozlesmesidir. Yanlis header:

- login/session davranisini bozabilir,
- LiteCaptcha iframe'ini engelleyebilir,
- CDN assetlerini bloke edebilir,
- arama motoru ve browser tarafinda farkli hata uretebilir.

## Temel Header'lar

| Header | Rol |
|---|---|
| `Content-Security-Policy` / CSP | Script, style, img, frame, connect kaynaklarini sinirlar |
| `Strict-Transport-Security` / HSTS | Browser'a siteyi HTTPS ile kullanmasini soyler |
| `X-Frame-Options` | Eski frame kontrolu; CSP `frame-ancestors` ile birlikte dusunulur |
| `Referrer-Policy` | Referer bilgisinin ne kadar gidecegini belirler |
| `Permissions-Policy` | Kamera, mikrofon, geolocation gibi browser yetkilerini sinirlar |
| `X-Content-Type-Options` | MIME sniffing riskini azaltir |

## Cloudflare Arkasinda HTTPS

Cloudflare proxy acikken origin PHP su header'lardan HTTPS bilgisini anlayabilir:

```txt
X-Forwarded-Proto: https
CF-Visitor: {"scheme":"https"}
```

Uygulama HTTPS'i dogru algilamazsa:

- secure cookie yanlis basilir,
- HSTS eksik gorunur,
- redirect loop olusabilir,
- admin health check security header uyarisi verebilir.

Cloudflare TLS icin `Full (strict)` tercih edilir. `Flexible` mod kalici cozum
degildir; origin tarafinda HTTPS algisini karistirabilir.

## CSP Kaynaklari

Fezadan icin CSP yazarken en az su kaynak tipleri dusunulur:

| CSP alani | Ihtiyac |
|---|---|
| `default-src` | Varsayilan kaynak siniri |
| `script-src` | Uygulama JS (`'nonce-...'` zorunlu), `/yonetim` hariç `'unsafe-inline'` kapalı |
| `style-src` | Tailwind/CSS kaynaklari |
| `img-src` | `self`, `data:`, `https://cdn.fezadan.org`, R2 public hostlari |
| `font-src` | local font veya izinli font kaynaklari |
| `connect-src` | API, LiteCaptcha, gerekiyorsa CDN |
| `frame-src` | `https://litecaptcha.fezadan.org` |
| `frame-ancestors` | Fezadan'in kimler tarafindan iframe'e alinabilecegi |

LiteCaptcha kullanildigi icin `frame-src` unutulursa captcha UI calismaz.

## LiteCaptcha Frame Kurali

Fezadan parent sayfa:

```txt
frame-src https://litecaptcha.fezadan.org
```

LiteCaptcha servis tarafi:

```txt
frame-ancestors 'self' https://fezadan.org https://notlar.fezadan.org
```

Bu iki kural uyumlu olmali. Bir taraf izin verip diger taraf engellerse browser
console'da CSP/frame hatasi gorulur.

## X-Frame-Options Dikkati

`X-Frame-Options: DENY` iframe'i tamamen engeller.

`X-Frame-Options: SAMEORIGIN` farkli subdomain iframe'lerini engelleyebilir.

LiteCaptcha gibi subdomain iframe ihtiyaci varsa modern kontrol CSP
`frame-ancestors` ile yapilmali, eski header'in akisi bozmadigi dogrulanmalidir.

## Cache Bypass Gereken Alanlar

Cloudflare cache su alanlarda kapali veya bypass olmalidir:

- `/yonetim`
- login/logout
- CSRF token ureten endpointler
- LiteCaptcha token/challenge endpointleri
- deploy endpointleri
- session bagimli sayfalar

Cache icin uygun alanlar:

- statik CSS/JS
- gorseller
- PDF/not dosyalari
- `cdn.fezadan.org` altindaki public medya

## Health Check Dogru Okuma

Security header health check yaparken browser gibi istek atmak daha dogrudur:

```bash
curl -I -A "Mozilla/5.0" https://fezadan.org/tr
```

Bare curl 403 donerse bu basliklarin yok oldugu anlamina gelmeyebilir. Cloudflare
veya origin bot kuralindan dolayi farkli cevap uretmis olabilir.

## Beklenen Basarili Sinyaller

- `Strict-Transport-Security` HTTPS sayfada gorunur.
- CSP icinde CDN ve LiteCaptcha kaynaklari izinlidir.
- Admin/session sayfalari cachelenmez.
- `security.txt`, `robots.txt`, `sitemap.xml` gibi public dosyalar erisilebilir.
- LiteCaptcha embed URL'i browser UA ile cevap verir.

## Degisiklik Yaparken

Header degisikligi yaptiktan sonra su kontroller yapilir:

```bash
curl -I -A "Mozilla/5.0" https://fezadan.org/tr
curl -I -A "Mozilla/5.0" "https://litecaptcha.fezadan.org/?embed=1"
curl -I -A "Mozilla/5.0" https://fezadan.org/robots.txt
curl -I -A "Mozilla/5.0" https://fezadan.org/.well-known/security.txt
```

Browser console'da CSP violation yoksa ve login/captcha akisi calisiyorsa header
degisikligi operasyonel olarak saglam kabul edilebilir.

