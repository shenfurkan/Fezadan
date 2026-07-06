# Canli URL ve Subdomain Haritasi

Bu dokuman Fezadan ekosistemindeki canli hostlari, hangi sistemin hangi URL'den
sorumlu oldugunu ve sorun oldugunda ilk bakilacak yeri listeler.

## Ana Hostlar

| URL | Sorumlu sistem | Rol | Ilk kontrol |
|---|---|---|---|
| `https://fezadan.org` | Fezadan PHP app | Ana site | Cloudflare DNS, cPanel deploy, PHP log |
| `https://www.fezadan.org` | Cloudflare/cPanel | Alias/redirect | DNS ve redirect kurali |
| `https://notlar.fezadan.org` | Fezadan notes routing | Notlar subdomain | DNS, route, R2 dosyalari |
| `https://cdn.fezadan.org` | Cloudflare R2/CDN | Public medya | R2 bucket, CDN_URL, DNS |
| `https://litecaptcha.fezadan.org` | LiteCaptcha app | Captcha/challenge servisi | DNS, deploy, CSP, embed URL |

Bu hostlar ayri ayri saglikli olabilir veya ayri ayri bozulabilir. Ana site
calisiyor diye LiteCaptcha'nin guncel deploy oldugu varsayilmaz.

## Kritik Endpointler

| Endpoint | Beklenen rol |
|---|---|
| `/yonetim` | Admin panel girisi |
| `/robots.txt` | Bot tarama kurallari |
| `/sitemap.xml` | Sitemap index veya URL listesi |
| `/.well-known/security.txt` | Guvenlik iletisim/policy dosyasi |
| LiteCaptcha `/?embed=1` | iframe icin captcha UI |
| LiteCaptcha redirect URL'i | Challenge sonrasi geri donus |

## Servis Sahipligi

```txt
Cloudflare
  -> DNS
  -> TLS/proxy
  -> WAF/cache
  -> R2/custom domain

cPanel
  -> Git deploy
  -> PHP runtime
  -> cron jobs
  -> public_html ve repo checkout

Fezadan
  -> MVC routing
  -> admin panel
  -> SEO/robots/sitemap
  -> R2Storage
  -> LiteCaptcha token verify

LiteCaptcha
  -> challenge UI
  -> browser proof
  -> token/signature
  -> embed/redirect flow
```

## Health Check Komutlari

Browser User-Agent ile:

```bash
curl -I -A "Mozilla/5.0" https://fezadan.org/tr
curl -I -A "Mozilla/5.0" https://notlar.fezadan.org
curl -I -A "Mozilla/5.0" https://cdn.fezadan.org
curl -I -A "Mozilla/5.0" "https://litecaptcha.fezadan.org/?embed=1"
curl -I -A "Mozilla/5.0" https://fezadan.org/robots.txt
curl -I -A "Mozilla/5.0" https://fezadan.org/sitemap.xml
curl -I -A "Mozilla/5.0" https://fezadan.org/.well-known/security.txt
```

DNS tarafinda:

```bash
nslookup fezadan.org
nslookup notlar.fezadan.org
nslookup cdn.fezadan.org
nslookup litecaptcha.fezadan.org
nslookup -type=TXT fezadan.org
```

## Hata Nereden Baslanir?

| Belirti | Baslangic noktasi |
|---|---|
| cPanel deploy butonu pasif | repo clean working tree, `.cpanel.yml`, untracked dosyalar |
| `.env` untracked gorunuyor | `.gitignore` veya `.git/info/exclude` |
| Ana site 403 | Cloudflare/WAF, User-Agent, origin routing |
| Admin health security header uyarisi | browser UA ile header kontrolu, HTTPS algisi |
| LiteCaptcha yanit alamadi | `/?embed=1`, CSP, DNS, LiteCaptcha deploy |
| Gorseller kayip | `CDN_URL`, `R2_PUBLIC_URL`, R2 bucket, cache |
| Sitemap 0 URL | sitemapindex/urlset ayrimi, yayinlanmis icerik, dirty flag |
| Bos kategori SQL hatasi | kategori pivot/tablo semasi ve health check sorgusu |

## Deploy Sonrasi Minimum Smoke Test

Her deploydan sonra en az su kontroller yapilir:

1. cPanel `Deploy HEAD Commit` basarili mi?
2. `git status --short --untracked-files=all` temiz mi?
3. Ana site browser UA ile 200 donuyor mu?
4. `/robots.txt`, `/sitemap.xml`, `security.txt` erisilebilir mi?
5. `cdn.fezadan.org` bir ornek medya dosyasini donduruyor mu?
6. LiteCaptcha `/?embed=1` cevap veriyor mu?
7. Admin health check yeni kritik hata uretmiyor mu?

## Dokuman Iliskileri

- DNS/TXT icin: `24-cloudflare-dns-txt-kayitlari.md`
- R2/CDN icin: `25-cloudflare-r2-cdn-medya-operasyonu.md`
- LiteCaptcha ic mantik icin: `26-litecaptcha-calisma-prensibi.md`
- Fezadan entegrasyonu icin: `27-litecaptcha-fezadan-entegrasyon-akisi.md`
- Security headers icin: `28-security-headers-csp-cloudflare.md`
- SEO public dosyalari icin: `29-robots-sitemap-securitytxt-seo-operasyonu.md`

