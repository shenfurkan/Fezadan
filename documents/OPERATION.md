# Fezadan Operasyon Rehberi

Bu belge canli deploy, cron ve smoke test icin tek kaynak olarak kullanilir.

## Deploy Modeli

- Fezadan icin tek ana deploy yolu cPanel Git Deployment'tir.
- Fezadan cPanel repository path: `/home/cpanel-user/repositories/Fezadan`.
- LiteCaptcha cPanel repository path: `/home/cpanel-user/repositories/LiteCaptcha`.
- Canli uygulama kok varsayimi: `/home/cpanel-user`.
- Web root: `/home/cpanel-user/public_html`.
- `.env`, `logs/`, upload/runtime dosyalari ve generated sitemap dosyalari deploy tarafindan yonetilmez.
- Eski `public_html/git-deploy.php` webhook endpoint'i kapatildi; 410 doner.

`.cpanel.yml` repo kokunden su klasorleri canliya kopyalar:

- `app/` -> `/home/cpanel-user/app/`
- `cron/` -> `/home/cpanel-user/cron/`
- `public_html/` -> `/home/cpanel-user/public_html/`
- `composer.json` ve `composer.lock` -> `/home/cpanel-user/`

Repo zaten `/home/cpanel-user` altinda calisiyorsa deploy no-op olur; tracked dosyalara `touch` atmaz.

## Cron Jobs

Fezadan icin mevcut cronlar:

```bash
*/5 * * * * /usr/local/bin/php /home/cpanel-user/cron/generate-sitemap.php >/dev/null 2>&1
*/5 * * * * /usr/local/bin/php /home/cpanel-user/cron/publish-scheduled.php >/dev/null 2>&1
0 3 * * * /usr/local/bin/php /home/cpanel-user/cron/backup-db.php >/dev/null 2>&1
```

Deploy icin ayrica cron kullanilacaksa tek komut `git pull --ff-only` + cPanel deployment tetigi olmalidir. Ayni anda webhook, manuel copy ve cron deploy calistirilmaz.

Auto deploy kullanilacaksa cPanel Cron Jobs ekraninda repodaki wrapper scriptler cagrilir:

```bash
* * * * * /bin/sh /home/cpanel-user/repositories/Fezadan/scripts/autodeploy-fezadan.sh >/dev/null 2>&1
* * * * * /bin/sh /home/cpanel-user/repositories/Fezadan/scripts/autodeploy-litecaptcha.sh >/dev/null 2>&1
```

Bu scriptler degisiklik yoksa deploy tetiklemez. Degisiklik varsa `git pull --ff-only origin main` calistirir ve `/usr/bin/uapi VersionControlDeployment create` ile ilgili cPanel repo deployment'ini tetikler.

Canli repo path'leri:

```text
Fezadan:     /home/cpanel-user/repositories/Fezadan
LiteCaptcha: /home/cpanel-user/repositories/LiteCaptcha
```

Canli runtime/docroot path'leri:

```text
Fezadan:     /home/cpanel-user/public_html
LiteCaptcha: /home/cpanel-user/captcha/public
```

## Deploy Sonrasi Smoke Test

Fezadan:

```bash
cd /home/cpanel-user/repositories/Fezadan
git status --short
git log -1 --oneline
curl -sSI https://fezadan.org/yonetim | head -30
```

LiteCaptcha:

```bash
cd /home/cpanel-user/captcha/public
php -r 'require "includes/litecaptcha.php"; echo "prod=".(robot_is_production()?"yes":"no").PHP_EOL; echo "salt_len=".strlen(robot_salt()).PHP_EOL; echo "allowed_fezadan=".(robot_is_redirect_allowed("fezadan.org")?"yes":"no").PHP_EOL; echo "allowed_notlar=".(robot_is_redirect_allowed("notlar.fezadan.org")?"yes":"no").PHP_EOL;'
```

Admin render:

```bash
curl -sSL https://fezadan.org/yonetim -o /tmp/yonetim.html
grep -nE 'litecaptcha-check-row|litecaptcha-bridge|litecaptcha.fezadan.org|localhost:8089|captcha_reason' /tmp/yonetim.html
```

Token log:

```bash
tail -120 /home/cpanel-user/logs/captcha_debug.log 2>/dev/null | grep -E 'missing_params|bad_signature|token_expired|token_replay|path|secret_len|uri'
```

## Repo Hijyeni

GitHub'a gelmemesi gereken local/dev dosyalari:

- Docker local dosyalari: `Dockerfile`, `docker-compose.yml`, `docker-entrypoint.sh`, `docker-php-config.php`
- Runtime klasorleri: `vendor/`, `node_modules/`, `logs/`, `backups/`

Runtime icin gerekli buyuk assetler simdilik tracked kalir: PDF.js, mammoth, summernote, fontlar ve portfolio gorselleri.

## Rollback

Deploy sonrasi sorun cikarsa:

```bash
cd /home/cpanel-user/repositories/Fezadan
git log --oneline -5
git reset --hard <onceki_commit>
/usr/bin/uapi VersionControlDeployment create repository_root=/home/cpanel-user/repositories/Fezadan
```

Rollback sonrasinda smoke test bolumu tekrar calistirilir.
