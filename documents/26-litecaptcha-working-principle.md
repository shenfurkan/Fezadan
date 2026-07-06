# LiteCaptcha Calisma Prensibi

LiteCaptcha Fezadan'dan ayri deploy edilebilen, kendi subdomain'i olan bir
dogrulama servisidir. Amaci klasik form captcha'si gibi sadece bir checkbox
gostermek degil; tarayici davranisi, challenge akisi ve imzali token ile
istegin insan tarayicisindan geldigine dair daha guclu sinyal toplamaktir.

## Rol Ayrimi

| Sistem | Sorumluluk |
|---|---|
| Fezadan | Login, not indirme veya korunan aksiyonu baslatir |
| LiteCaptcha | Challenge UI, browser proof, token ve imza uretir |
| Cloudflare | DNS, TLS, proxy/cache/WAF katmani |
| cPanel | Deploy, PHP runtime, dosya sistemi |

LiteCaptcha kendi basina bir urun gibi dusunulmelidir. Fezadan onu kullanir, ama
tum state Fezadan icine gomulu degildir.

## Calisma Modlari

LiteCaptcha genelde uc sekilde dusunulur:

| Mod | Ornek | Amac |
|---|---|---|
| Embed | `https://litecaptcha.fezadan.org/?embed=1` | iframe icinde kullanmak |
| Redirect | `https://litecaptcha.fezadan.org/?redirect=...` | kullaniciyi captcha sayfasina gonderip geri almak |
| Local/test | local env ayarlari | gelistirme ve test |

Canli health check icin bare `/` her zaman dogru sinyal degildir. Servis guvenlik
nedeniyle dogrudan acilisi sinirlayabilir. Daha dogru kontrol:

```bash
curl -I -A "Mozilla/5.0" "https://litecaptcha.fezadan.org/?embed=1"
```

## Temel Akis

```txt
Kullanici korunan aksiyona gelir
  -> Fezadan LiteCaptcha iframe/redirect akisini baslatir
  -> LiteCaptcha challenge ve browser proof toplar
  -> Token uretilir
  -> Token imzalanir
  -> Fezadan token/imza/sure bilgisini dogrular
  -> Basariliysa asil aksiyon devam eder
```

Bu akista kritik nokta tokenin sadece client tarafinda "basarili" denmesi degildir.
Parent uygulama tokeni server tarafinda dogrulamalidir.

## Secret Iliskisi

LiteCaptcha ve Fezadan ayni imza sirrini bilmelidir:

| Degisken | Nerede | Rol |
|---|---|---|
| `LITECAPTCHA_SECRET` | LiteCaptcha | Token imzalamak |
| `LITECAPTCHA_SECRET` | Fezadan | Token dogrulamak |
| `APP_SECURITY_SALT` | Fezadan fallback | Secret verilmediyse fallback olabilir |
| `LITECAPTCHA_ALLOWED_HOSTS` | LiteCaptcha | Hangi parent hostlarin kullanabilecegi |

Canlida en temiz model: Fezadan ve LiteCaptcha ayni `LITECAPTCHA_SECRET`
degerini kullanir. Bu deger repo icine yazilmaz.

## Token ve Imza

Basarili challenge sonunda tipik olarak su bilgiler parent uygulamaya doner:

- token veya runtime id
- imza (`sig`)
- bitis zamani (`exp`)
- redirect hedefi veya parent origin bilgisi

Fezadan bu bilgileri kontrol eder:

- imza beklenen secret ile uyumlu mu?
- token suresi dolmus mu?
- token tekrar kullanilmis mi?
- origin/host izinli mi?
- aksiyon icin beklenen redirect veya context dogru mu?

Bu kontrollerden biri basarisizsa kullanici tekrar captcha akisine alinmalidir.

## Runtime Dosyalari

LiteCaptcha state'i genelde gecici runtime alanlarinda tutar:

```txt
/tmp/litecaptcha_runtime
/tmp/litecaptcha_tokens
```

Bu dosyalar uygulama source kodu degildir. Deploy cleanup islemleri bu runtime
alanlariyla repo checkout'unu karistirmamalidir. cPanel deploy sorununun ana dersi
budur: repo source ile deploy target ayniysa cleanup komutlari tracked dosyalari
silemez.

## postMessage ve iframe

Embed modda LiteCaptcha iframe olarak acilir ve parent sayfa ile `postMessage`
uzerinden konusur. Bu nedenle iki tarafli guvenlik gerekir:

- Parent sadece beklenen `https://litecaptcha.fezadan.org` origininden gelen
  mesaji kabul etmelidir.
- LiteCaptcha sadece izinli parent originlerine cevap vermelidir.
- Mesaj icerigi sadece UI sinyali degil, server dogrulamasina giden veri
  tasimalidir.

`postMessage` tek basina guvenlik siniri degildir. Asil guvenlik HMAC imza,
sure ve server-side verify ile gelir.

## CSP ve Frame Kurallari

Fezadan parent ise CSP icinde LiteCaptcha iframe'ine izin vermelidir:

```txt
frame-src https://litecaptcha.fezadan.org
```

LiteCaptcha tarafinda da kimlerin iframe icinde acabilecegi sinirlanmalidir:

```txt
frame-ancestors 'self' https://fezadan.org https://notlar.fezadan.org
```

Bu iki kural birbiriyle uyumlu degilse captcha UI yuklenmez veya browser console'da
CSP/frame hatasi gorulur.

## Basarisizlik Tipleri

| Belirti | Muhtemel sebep |
|---|---|
| iframe bos | CSP `frame-src` veya `frame-ancestors` uyumsuz |
| bare `/` 403 | Dogrudan erisim kisitli; `?embed=1` ile test edilmeli |
| token dogrulanmiyor | `LITECAPTCHA_SECRET` uyumsuz veya sure dolmus |
| redirect donmuyor | redirect URL whitelist/origin kontrolu |
| Cloudflare 403 | UA/WAF/bot kuralindan kaynaklanabilir |
| tekrar tekrar captcha | replay veya session/state yazma problemi |

## Saglik Kontrolu

Dogru canli kontrol:

```bash
curl -I -A "Mozilla/5.0" "https://litecaptcha.fezadan.org/?embed=1"
```

Beklenen isaretler:

- HTTP 200 veya uygulamanin beklenen embed cevabi
- LiteCaptcha HTML/config sinyali
- CSP/frame policy ile parent host uyumu
- Fezadan tarafinda token verify basarisi

