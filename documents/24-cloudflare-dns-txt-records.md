# Cloudflare DNS ve TXT Kayitlari

Bu dokuman Fezadan alan adinin Cloudflare tarafindaki DNS/TXT mantigini anlatir.
Gercek token, API key, secret veya dogrulama degeri burada tutulmaz. Bu degerler
Cloudflare, cPanel, mail saglayici veya arama motoru panelinden kopyalanir.

## Amac

Cloudflare bu projede uc isi birden yapar:

- DNS zone: `fezadan.org` ve alt alan adlarini dogru hedefe yollar.
- Proxy ve TLS: web trafigini HTTPS uzerinden karsilar, origin sunucuya iletir.
- R2/CDN baglantisi: medya dosyalarinin `cdn.fezadan.org` gibi public hostlardan
  servis edilmesini saglar.

DNS tarafinda sorun olursa PHP kodu saglam olsa bile site bozuk gorunur. Bu yuzden
canli hatalarda ilk kontrol edilecek yerlerden biri Cloudflare DNS tablosudur.

## Ana Kayit Mantigi

| Host | Kayit tipi | Hedef | Cloudflare modu | Not |
|---|---|---|---|---|
| `fezadan.org` / `@` | `A`, `AAAA` veya `CNAME` | cPanel/origin hedefi | Proxied | Ana web sitesi |
| `www` | `CNAME` | `fezadan.org` veya origin | Proxied | www yonlendirme/alias |
| `notlar` | `A` veya `CNAME` | cPanel/origin hedefi | Proxied | Notlar subdomain |
| `anonymitycheck` | `CNAME` | `fezadan.org` | Proxied | Tarayıcı analiz / Anonymity Checker subdomain'i |
| `litecaptcha` | `A` veya `CNAME` | LiteCaptcha deploy hedefi | Proxied veya DNS only | iframe/CSP test edilerek secilir |
| `cdn` | `CNAME` | R2 public/custom domain | Cloudflare yonetimli | Statik medya hostu |
| `mail`, `webmail`, `cpanel`, `ftp` | `A` veya `CNAME` | cPanel servisleri | DNS only | Bu servisler proxy edilmemeli |
| MX kayitlari | `MX` | mail saglayici | DNS only | Mail teslimati icin |

Kural basit: HTTP/HTTPS web trafigi proxy olabilir; mail, FTP, cPanel servisleri
DNS only kalmalidir. Mail kayitlarina turuncu bulut acmak teslimat ve baglanti
sorunlari uretir.

## TXT Kayitlari

TXT kayitlari genelde dogrulama ve mail guvenligi icindir. Bunlar secret gibi
davranilmalidir; repo icine gercek deger yazilmaz.

| Kayit | Host | Deger kaynagi | Amac |
|---|---|---|---|
| SPF | `@` | Mail saglayici paneli | Hangi sunucularin alan adina mail atabilecegini soyler |
| DKIM | `<selector>._domainkey` | Mail saglayici paneli | Mail iceriginin imzasini dogrular |
| DMARC | `_dmarc` | Mail politikasi | SPF/DKIM basarisizsa ne yapilacagini soyler |
| Google Search Console | `@` veya verilen host | Google paneli | Domain dogrulama |
| Yandex/Bing/Meta vb. | verilen host | ilgili servis paneli | Harici servis dogrulama |
| Cloudflare dogrulama | verilen host | Cloudflare paneli | Zone/sahiplik dogrulama |

Ornek formatlar asla birebir kullanilacak kesin deger degildir:

```txt
SPF   @                 "v=spf1 include:<mail-provider> -all"
DKIM  <selector>._domainkey "<dkim-public-key-from-provider>"
DMARC _dmarc            "v=DMARC1; p=quarantine; rua=mailto:<report-mail>"
GSC   @                 "google-site-verification=<token-from-google>"
```

Her TXT degeri ilgili panelden alinmali, tek satir halinde Cloudflare DNS
tablosuna girilmeli ve repo dokumanlarina gercek token yazilmamalidir.

## Proxied vs DNS Only Karari

`Proxied` secilirse trafik Cloudflare uzerinden gecer. Avantajlari:

- TLS/HTTPS Cloudflare tarafinda karsilanir.
- Cache, WAF, bot korumasi ve header kurallari uygulanabilir.
- Origin IP dogrudan gorunmeyebilir.

Riskleri:

- Dinamik endpoint yanlis cachelenirse `/yonetim`, token veya captcha akislari
  bozulur.
- Origin HTTPS ayari yanlissa redirect loop veya 525/526 SSL hatalari gorulebilir.
- iframe kullanan LiteCaptcha icin CSP ve frame policy birlikte test edilmelidir.

`DNS only` secilirse Cloudflare sadece DNS cevabi verir, trafik dogrudan origin
sunucuya gider. Mail, FTP, cPanel ve bazi dogrudan servisler icin dogru tercih budur.

## Cloudflare TLS Ayari

Canli site icin beklenen ayar:

- SSL/TLS mode: `Full (strict)` tercih edilir.
- Always Use HTTPS: acik olabilir.
- Automatic HTTPS Rewrites: acik olabilir.
- HSTS: uygulama ve Cloudflare birlikte dusunulerek acilmalidir.

Origin sertifikasi gecersizse `Full (strict)` hata verir. Bu durumda kalici cozum
origin sertifikasini duzeltmektir; `Flexible` moda gecmek PHP tarafinda HTTPS
algisini ve secure cookie davranisini bozabilir.

## Subdomain Ekleme Adımları (Örn: anonymitycheck)

Yeni bir subdomain (örneğin `anonymitycheck.fezadan.org`) tanımlarken hem sunucu tarafında hem de Cloudflare tarafında aşağıdaki adımlar takip edilmelidir:

1. **cPanel Üzerinde Yönlendirme:**
   - cPanel > **Domains** menüsüne gidin.
   - **Create A New Domain** butonuna tıklayın.
   - **Domain** alanına tam subdomain adresini girin: `anonymitycheck.fezadan.org`
   - **Document Root** kısmında **Share document root (/home/fezadano5/public_html) with "fezadan.org"** seçeneğini işaretleyin. Bu ayar, uygulamanın yönlendirici (router) mekanizmasının subdomain isteklerini dinamik olarak karşılamasını sağlar.
   - **Submit** butonuna basarak kaydedin.

2. **Cloudflare Üzerinde DNS Tanımlama:**
   - Cloudflare panelinde ilgili alan adının **DNS** > **Records** sayfasına gidin.
   - **Add Record** butonuna tıklayarak yeni bir kayıt açın.
   - Alanları şu şekilde doldurun:
     - **Type:** `CNAME`
     - **Name:** `anonymitycheck` (veya `anonymitycheck.fezadan.org`)
     - **Target:** `fezadan.org`
     - **Proxy status:** **Proxied** (Turuncu bulut - Aktif). Konum tespiti (`CF-IPCountry` vb.) ve analitik header'larının iletilmesi için proxy modunun açık olması zorunludur.
   - **Save** butonuna tıklayarak DNS kaydını tamamlayın.

## Dogrulama Komutlari

Yerel terminal veya sunucuda:

```bash
nslookup fezadan.org
nslookup -type=TXT fezadan.org
nslookup -type=TXT _dmarc.fezadan.org
nslookup litecaptcha.fezadan.org
nslookup cdn.fezadan.org
```

HTTP tarafinda browser User-Agent ile bakmak daha dogru sinyal verir:

```bash
curl -I -A "Mozilla/5.0" https://fezadan.org/tr
curl -I -A "Mozilla/5.0" https://litecaptcha.fezadan.org/?embed=1
```

Bare curl 403 donerse bu tek basina uygulama bozuk demek degildir; Cloudflare veya
origin tarafinda bot/UA filtresi calisiyor olabilir.

## Incident Baslangic Noktasi

DNS/TXT kaynakli bir sorunda siralama:

1. Cloudflare DNS tablosunda host var mi?
2. Host icin Proxied/DNS only modu dogru mu?
3. Mail kayitlari proxy edilmemis mi?
4. TXT dogrulama degeri ilgili paneldeki degerle birebir ayni mi?
5. Ana site, notlar, cdn ve LiteCaptcha ayri ayri cevap veriyor mu?
6. Cloudflare cache veya WAF dinamik endpointleri engelliyor mu?

