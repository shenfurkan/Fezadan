# Robots, Sitemap, security.txt ve SEO Operasyonu

Bu dokuman Fezadan'in arama motoru ve guvenlik bildirim dosyalarini nasil
uretmesini bekledigimizi anlatir.

## Public SEO Dosyalari

| Dosya/Endpoint | Rol |
|---|---|
| `/robots.txt` | Botlara hangi alanlarin taranabilecegini soyler |
| `/sitemap.xml` | Sitemap index veya URL listesi verir |
| `/.well-known/security.txt` | Guvenlik bildirimi icin iletisim/policy dosyasi |
| canonical/hreflang/meta | Sayfa bazli SEO sinyalleri |
| OG image/meta | Sosyal medya paylasim sinyalleri |

Bu dosyalar public olmalidir. Admin veya session gerektirmemelidir.

## robots.txt Mantigi

`robots.txt` dinamik uretiliyorsa hosta gore farkli davranabilir:

- `fezadan.org` ana site sayfalarini isaret eder.
- `notlar.fezadan.org` notlar subdomain kurallarini isaret eder.
- Admin, auth, gecici veya internal pathler disallow edilir.
- Sitemap satiri dogru canonical hostu gostermelidir.

Ornek mantik:

```txt
User-agent: *
Disallow: /yonetim
Sitemap: https://fezadan.org/sitemap.xml
```

Bu sadece format ornegidir; canli icerik uygulamanin urettigi dosyadan kontrol
edilmelidir.

## sitemap.xml Mantigi

Sitemap iki formatta olabilir:

| Format | Root tag | Ne demek? |
|---|---|---|
| Sitemap index | `sitemapindex` | Birden fazla sitemap dosyasini listeler |
| URL set | `urlset` | Direkt URL listeler |

Health check sadece `<url>` sayarsa sitemap index'i 0 URL gibi yanlis
yorumlayabilir. Dogru kontrol sitemap index ve urlset ayrimini bilmelidir.

Beklenen sitemap kaynaklari:

- yayinlanmis makaleler
- statik sayfalar
- kategori/tag sayfalari
- notlar veya alt site URL'leri, eger public indeksleniyorsa

Scheduled article yayina cikinca sitemap dirty flag set edilebilir ve cron/bakim
gorevi sitemap'i yeniler.

## security.txt

`security.txt` yolu:

```txt
https://fezadan.org/.well-known/security.txt
```

Amaci guvenlik arastirmacilarina dogru iletisim ve politika bilgisini vermektir.
Bu dosya hassas bilgi tasimamali; sadece public iletisim ve policy bilgisi
tasimalidir.

Cloudflare veya cPanel routing bu dosyayi engelliyorsa security header health check
basarili olsa bile guvenlik bildirim akisi eksik kalir.

## Search Console ve TXT Iliskisi

Google Search Console gibi servisler domain sahipligini TXT ile dogrular. Bu
dokumanin DNS kismi ile SEO kismi burada birlesir:

- Search Console paneli TXT token verir.
- Token Cloudflare DNS'e eklenir.
- Google domaini dogrular.
- Sitemap URL'i Search Console'a gonderilir.

Token gercek degeri repo icine yazilmaz.

## Admin Health Check Ciktilari

SEO ile ilgili health check satirlari su sekilde okunmalidir:

| Satir | Anlam |
|---|---|
| `SITEMAP DOSYASI` | sitemap endpoint/dosya mevcut mu? |
| `SITEMAP DIRTY FLAG` | yeniden uretim gerekiyor mu? |
| `SITEMAP URL SAYISI` | urlset veya sitemapindex dogru sayiliyor mu? |
| `ROBOTS.TXT` | public ve dinamik uretim calisiyor mu? |
| `SEO EKSIKLERI` | meta/OG/canonical gibi alanlarda eksik var mi? |
| `BOS KATEGORILER` | kategori pivot/tablo sorgusu dogru mu? |

Bir health check uyarisi gorulunce once "uyari gercek veri problemi mi, yoksa
kontrol sorgusu eski semaya mi bakiyor?" ayrimi yapilmalidir.

## Canli Kontrol Komutlari

```bash
curl -I -A "Mozilla/5.0" https://fezadan.org/robots.txt
curl -A "Mozilla/5.0" https://fezadan.org/robots.txt
curl -I -A "Mozilla/5.0" https://fezadan.org/sitemap.xml
curl -A "Mozilla/5.0" https://fezadan.org/sitemap.xml
curl -I -A "Mozilla/5.0" https://fezadan.org/.well-known/security.txt
```

Bare curl ile 403 alinirsa browser User-Agent ile tekrar bakilir. Cloudflare/WAF
cevabi ile uygulama cevabi karistirilmamalidir.

## Operasyon Karar Agaci

1. URL browser UA ile erisilebilir mi?
2. Cloudflare cache eski cevap tutuyor mu?
3. cPanel deploy son commit'i yayinlamis mi?
4. Sitemap index mi, urlset mi?
5. Veritabani yayinlanmis icerik donduruyor mu?
6. `robots.txt` canonical hostu dogru mu?
7. Search Console TXT dogrulamasi Cloudflare DNS'te duruyor mu?

