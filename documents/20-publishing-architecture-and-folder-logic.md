# 20. Yayin Mimarisi ve Klasor Mantigi

Bu belge Fezadan, LiteCaptcha ve cPanel arasindaki dizin mantigini netlestirir. Amac, canli sunucuda hangi dosyanin kaynak kod, hangisinin deploy hedefi, hangisinin runtime state oldugunu karistirmamaktir.

## Temel Model

Projede uc farkli dosya turu vardir:

```text
1. Source of truth
   GitHub ve cPanel'in Git checkout olarak gordugu repo dosyalari.

2. Deploy target
   Web sunucusunun ziyaretciye sundugu kopya veya public docroot.

3. Runtime state
   Canli sunucuda uretilen, Git'e girmemesi gereken .env, log, token, cache ve gecici dosyalar.
```

Deploy scriptleri bu uc siniri karistirmamalidir.

## Fezadan Ana Site

Canli sunucuda ana uygulama kok dizini:

```text
/home/fezadano5
```

Bu dizinde su dosya ve klasorler bulunur:

```text
/home/fezadano5/.git
/home/fezadano5/.cpanel.yml
/home/fezadano5/.env
/home/fezadano5/app
/home/fezadano5/cron
/home/fezadano5/composer.json
/home/fezadano5/composer.lock
/home/fezadano5/vendor
```

Public web root:

```text
/home/fezadano5/public_html
```

Ziyaretciye acik dosyalar burada durur:

```text
/home/fezadano5/public_html/index.php
/home/fezadano5/public_html/assets
/home/fezadano5/public_html/robots.txt
/home/fezadano5/public_html/sitemap.xml
```

Kural:

```text
app/, cron/, .env gibi dosyalar dogrudan webden servis edilmemeli.
public_html ziyaretciye acik katmandir.
```

## LiteCaptcha

LiteCaptcha ayri bir cPanel Git checkout olarak durur:

```text
/home/fezadano5/captcha
```

Bu checkout icinde beklenen ana dosyalar:

```text
/home/fezadano5/captcha/.git
/home/fezadano5/captcha/.cpanel.yml
/home/fezadano5/captcha/.gitignore
/home/fezadano5/captcha/.env
/home/fezadano5/captcha/public/index.php
/home/fezadano5/captcha/public/assets/litecaptcha.js
/home/fezadano5/captcha/public/assets/litecaptcha.css
/home/fezadano5/captcha/public/includes/litecaptcha.php
```

`.env` canli sunucuya ozel state'tir. Git'e girmemelidir, ama cPanel working tree icinde untracked olarak da gorunmemelidir. Bunun icin `.gitignore` veya `.git/info/exclude` tarafindan ignore edilmelidir.

## Runtime State

Canli envanterde gorulen runtime alanlari:

```text
/home/fezadano5/.cagefs/tmp/litecaptcha_runtime
/home/fezadano5/.cagefs/tmp/litecaptcha_tokens
/home/fezadano5/.cpanel/logs/vc_*_git_deploy.log
/home/fezadano5/.cpanel/datastore/vc_deploy.sqlite
```

Bu dosyalar deploy mantiginin parcasi degildir. Bunlari Git'e ekleme. Sorun giderirken sadece kanit veya log olarak oku.

## Kritik Deploy Kurali

Repo checkout ile deploy hedefi ayni dizinse cleanup tracked dosyalara dokunamaz.

Yanlis mantik:

```bash
rm -f ${DEPLOYPATH}/.gitignore
rm -f ${DEPLOYPATH}/.env.example
rm -rf ${DEPLOYPATH}/scripts
```

Dogru mantik:

```bash
if [ "${REPO_ROOT}" != "${DEPLOYPATH}" ]; then
  rm -f ${DEPLOYPATH}/.env.example
  rm -rf ${DEPLOYPATH}/scripts
fi
```

Fezadan ana repo icin ayni mantik:

```bash
if [ "${REPO_ROOT}" != "${APPROOT}" ]; then
  # sadece ayri deploy hedefini temizle
fi
```

## Ne Silinebilir?

Repo checkout ile deploy hedefi ayniysa sadece Git tarafindan ignored olan runtime dosyalari temizlenebilir:

```text
error_log
*.log
cache dosyalari
gecici test ciktisi
```

Tracked dosyalar silinmemelidir:

```text
.gitignore
.env.example
README.md
KURULUM.md
LICENSE
scripts/
tests/
documents/
app/
public_html/
```

## Karar Agaci

Deploy kilitlendiyse:

```text
1. git status --short --untracked-files=all calistir.
2. .env gorunuyorsa git check-ignore -v .env calistir.
3. .env ignored ise .env'i birak, status'teki diger dosyaya bak.
4. ?? public/test.txt gibi test dosyasi varsa temizle.
5. D .gitignore gibi tracked deletion varsa git restore ile geri getir.
6. status bos olmadan cPanel deploy'a basma.
```

