# LiteCaptcha ve Fezadan Entegrasyon Akisi

Bu dokuman Fezadan ana uygulamasinin LiteCaptcha ile nasil konustugunu operasyon
seviyesinde anlatir. Kod degistirmeden once bu akis anlasilmalidir.

## Ne Zaman Devreye Girer?

LiteCaptcha su tur aksiyonlarda kullanilir:

- Admin/login korumasi
- Not indirme veya hassas public aksiyonlar
- Bot riskinin yuksek oldugu formlar
- Kullanici davranisi dogrulamasi gereken gecisler

Hangi aksiyonda kullanilacagi Fezadan kodu ve env ayarlariyla belirlenir.

## Fezadan Tarafi Env

| Degisken | Rol |
|---|---|
| `LITECAPTCHA_ENABLED` | Entegrasyonu acip kapatir |
| `LITECAPTCHA_URL` | LiteCaptcha servis URL'i |
| `LITECAPTCHA_SECRET` | Token dogrulama secret'i |
| `APP_SECURITY_SALT` | Bazi kurulumlarda fallback imza sirri |
| `SITE_URL` | Ana site origin'i |
| `NOTES_SITE_URL` | Notlar subdomain origin'i |

Canlida `LITECAPTCHA_URL` beklenen deger:

```txt
https://litecaptcha.fezadan.org
```

Secret degerleri dokumantasyona veya git'e yazilmaz.

## Iframe Akisi

```txt
Fezadan sayfasi
  -> LiteCaptcha iframe URL'ini hazirlar
  -> iframe: https://litecaptcha.fezadan.org/?embed=1
  -> LiteCaptcha challenge'i gosterir
  -> Kullanici challenge'i tamamlar
  -> iframe parent'a postMessage yollar
  -> Fezadan token bilgisini server tarafinda dogrular
  -> Aksiyon devam eder veya reddedilir
```

Burada `postMessage` sadece iki pencere arasindaki tasima mekanizmasidir. Karar
server tarafinda verilmelidir.

## Redirect Akisi

```txt
Kullanici korunan URL'e gelir
  -> Fezadan LiteCaptcha redirect URL'i olusturur
  -> Kullanici LiteCaptcha subdomain'ine gider
  -> Challenge tamamlanir
  -> Imzali token ile Fezadan'a geri doner
  -> Fezadan imzayi, sureyi ve hedefi dogrular
```

Redirect hedefi acik redirect uretmeyecek sekilde sinirlanmalidir. Geri donus
URL'i sadece izinli Fezadan hostlarina ait olmalidir.

## postMessage Guvenligi

Parent sayfa su kontrolleri yapmalidir:

- Mesaj `https://litecaptcha.fezadan.org` origininden mi geldi?
- Mesaj beklenen tipte mi?
- Token/imza alanlari var mi?
- UI basarisi server verify ile dogrulandi mi?

LiteCaptcha tarafi su kontrolleri yapmalidir:

- Parent origin `LITECAPTCHA_ALLOWED_HOSTS` icinde mi?
- Challenge ayni session/runtime state icin mi?
- Token suresi ve replay durumu uygun mu?

## CSP Gereksinimleri

Fezadan parent sayfasi icin gerekli kisim:

```txt
frame-src https://litecaptcha.fezadan.org
connect-src https://litecaptcha.fezadan.org
```

LiteCaptcha icin gerekli kisim:

```txt
frame-ancestors 'self' https://fezadan.org https://notlar.fezadan.org
```

`X-Frame-Options: DENY` veya uyumsuz `SAMEORIGIN` iframe akisini bozabilir. Modern
karar CSP `frame-ancestors` uzerinden verilmelidir.

## Basarili Aksiyon Kriteri

Bir LiteCaptcha gecisi basarili sayilmak icin:

1. Challenge client tarafinda tamamlanmis olmalidir.
2. Token server tarafina ulasmis olmalidir.
3. HMAC/signature dogrulanmis olmalidir.
4. `exp` suresi gecmemis olmalidir.
5. Replay veya daha once kullanilmis token olmamalidir.
6. Origin ve redirect hedefi izinli olmalidir.

Sadece iframe'in "ok" demesi yeterli degildir.

## Hata Ayiklama

| Problem | Bakilacak yer |
|---|---|
| Captcha hic acilmiyor | CSP `frame-src`, DNS, Cloudflare WAF |
| iframe aciliyor ama mesaj gelmiyor | browser console, `postMessage` origin kontrolu |
| Mesaj geliyor ama login devam etmiyor | Fezadan token verify |
| Canlida 403, localde calisiyor | Cloudflare, User-Agent, bot/WAF kurallari |
| Token surekli gecersiz | `LITECAPTCHA_SECRET` uyumsuzlugu |

## Deploy Baglantisi

LiteCaptcha ayri repo/deploy olarak dusunulmelidir. Fezadan deploy'u basarili olsa
bile LiteCaptcha subdomain eski kalabilir. Canli dogrulama icin iki taraf da
ayri test edilir:

```bash
curl -I -A "Mozilla/5.0" https://fezadan.org/tr
curl -I -A "Mozilla/5.0" "https://litecaptcha.fezadan.org/?embed=1"
```

