# 23. Cron Jobs Calisma Prensibi

Bu belge Fezadan canli sunucusunda cron job'larin ne zaman calistigini, hangi dosyayi tetikledigini ve cPanel Cron Jobs ekranina ne yazilacagini anlatir.

## Cron Neyi Cozer?

Cron, ziyaretci isteginden bagimsiz arka plan islerini calistirir.

Bu projede cron su isler icin kullanilir:

```text
sitemap dosyalarini guncellemek
zamanli makaleleri yayina almak
veritabani yedegini almak
opsiyonel olarak private Git checkout'larini otomatik deploy etmek
```

Deploy islemi normalde manuel cPanel Git Version Control ile yapilir. Otomatik deploy istenirse cron dogrudan dosya kopyalamaz; sadece Git checkout'i fast-forward gunceller ve cPanel deployment tetigini cagirir.

## Canli Cron Komutlari

cPanel -> Cron Jobs ekraninda beklenen ana komutlar:

```bash
*/5 * * * * /usr/local/bin/php /home/fezadano5/cron/generate-sitemap.php >/dev/null 2>&1
*/5 * * * * /usr/local/bin/php /home/fezadano5/cron/publish-scheduled.php >/dev/null 2>&1
0 3 * * * /usr/local/bin/php /home/fezadano5/cron/backup-db.php >/dev/null 2>&1
```

Not: Bazi eski belgelerde cron klasorunu public document root altinda gosteren ornekler geciyor olabilir. Bu canli kurulumda cron klasoru `/home/fezadano5/cron` altindadir.

## Opsiyonel Auto Deploy Cronlari

Auto deploy kullanilacaksa cPanel Cron Jobs ekranina tek satirlik `git pull && uapi` komutu yazmak yerine repodaki scriptler cagrilir. Scriptler:

```text
scripts/autodeploy-fezadan.sh
scripts/autodeploy-litecaptcha.sh
```

Bu scriptler:

```text
1. Ayni anda iki deploy calismasin diye lock alir.
2. origin/main fetch eder.
3. Lokal HEAD zaten origin/main ise deploy tetiklemeden cikar.
4. Degisiklik varsa git pull --ff-only origin main calistirir.
5. cPanel VersionControlDeployment create tetikler.
6. Loglari /home/fezadano5/logs/ altina yazar.
```

cPanel Cron Jobs komutlari:

```bash
* * * * * /bin/sh /home/fezadano5/repositories/Fezadan/scripts/autodeploy-fezadan.sh >/dev/null 2>&1
* * * * * /bin/sh /home/fezadano5/repositories/Fezadan/scripts/autodeploy-litecaptcha.sh >/dev/null 2>&1
```

Log dosyalari:

```text
/home/fezadano5/logs/autodeploy-fezadan.log
/home/fezadano5/logs/autodeploy-litecaptcha.log
```

Auto deploy on kosullari:

```text
1. Fezadan cPanel repo path'i /home/fezadano5/repositories/Fezadan olmali.
2. LiteCaptcha cPanel repo path'i /home/fezadano5/repositories/LiteCaptcha olmali.
3. Fezadan SSH testi passphrase sormadan calismali: ssh -T git@github.com
4. LiteCaptcha SSH testi passphrase sormadan calismali: ssh -T git@litecaptcha.github.com
5. Manuel cPanel Deploy HEAD Commit daha once basarili olmus olmali.
```

Auto deploy devreye alindiginda ayni anda eski webhook deploy veya baska cron deploy akisi kullanilmaz.

## generate-sitemap.php

Dosya:

```text
/home/fezadano5/cron/generate-sitemap.php
```

Gorevi:

```text
dirty flag varsa sitemap cache dosyalarini yeniden uretir.
public_html/sitemap.xml
public_html/sitemap_main.xml
public_html/sitemap_notes.xml
```

Manuel test:

```bash
/usr/local/bin/php /home/fezadano5/cron/generate-sitemap.php --force
```

Kontrol:

```bash
ls -l /home/fezadano5/public_html/sitemap*.xml
curl -sSI https://fezadan.org/sitemap.xml | head
curl -sSI https://notlar.fezadan.org/sitemap.xml | head
```

## publish-scheduled.php

Dosya:

```text
/home/fezadano5/cron/publish-scheduled.php
```

Gorevi:

```text
status = scheduled olan ve publish_at zamani gelmis makaleleri published yapar.
```

Bu cron calismazsa admin panelindeki saglik kontrolunde gecikmis scheduled makale uyarisi gorulebilir.

Manuel test:

```bash
/usr/local/bin/php /home/fezadano5/cron/publish-scheduled.php
```

Kontrol:

```text
Admin panelde planli makale beklenen zamanda yayina alinmali.
Yonetim saglik kontrolunde gecikmis scheduled uyarisi kalmamali.
```

## backup-db.php

Dosya:

```text
/home/fezadano5/cron/backup-db.php
```

Gorevi:

```text
MySQL dump alir.
gzip ile sikistirir.
Cloudflare R2 yedek alanina yukler.
Eski yedekleri retention politikasina gore temizler.
```

Zaman:

```text
Her gun 03:00
```

Manuel test dikkatli yapilmali; veritabani ve R2 islemi oldugu icin gereksiz tekrar calistirma.

## Tek Seferlik Scriptler

Bu dosyalar cron'a surekli baglanmamalidir:

```text
cron/convert-existing-to-webp.php
cron/optimize-legacy-images.php
```

Bunlar migration/bakim amacli tek seferlik islerdir. Surekli cron yapilirlarsa CPU, disk ve dosya churn uretebilirler.

## Output ve Log Mantigi

Cron komutlarinda su kisim vardir:

```bash
>/dev/null 2>&1
```

Anlami:

```text
normal output ve hata output'u cPanel mailine dusmesin.
```

Sorun giderirken gecici olarak output'u log dosyasina yonlendirebilirsin:

```bash
*/5 * * * * /usr/local/bin/php /home/fezadano5/cron/generate-sitemap.php >> /home/fezadano5/logs/cron-sitemap.log 2>&1
```

Sorun cozulunce log sisirmemek icin tekrar `>/dev/null 2>&1` kullan.

## Cron ve Deploy Karistirilmamali

Dogru ayrim:

```text
cPanel Git Control Panel = kodu cekmek ve deploy etmek
Cron Jobs = yayindaki uygulamanin periyodik bakim isleri
```

Yanlis pratik:

```text
Webhook deploy + cPanel deploy + cron deploy ayni anda kullanmak.
```

Bu, hangi mekanizmanin dosya degistirdigini belirsizlestirir.

Bu projede ana deploy yolu:

```text
cPanel Git Version Control
```

`public_html/git-deploy.php` dosyasi eski webhook mantigindan kalma olabilir. Aktif deploy kaynagi olarak kullanmadan once `OPERASYON.md` ve canli davranis kontrol edilmelidir.

## Cron Saglik Kontrolu

Canli terminalde:

```bash
crontab -l
```

Beklenen satirlar:

```text
generate-sitemap.php
publish-scheduled.php
backup-db.php
```

PHP path kontrolu:

```bash
/usr/local/bin/php -v
```

Dosya varlik kontrolu:

```bash
test -f /home/fezadano5/cron/generate-sitemap.php && echo ok
test -f /home/fezadano5/cron/publish-scheduled.php && echo ok
test -f /home/fezadano5/cron/backup-db.php && echo ok
```

Deploy sonrasi cron klasoru guncel mi:

```bash
ls -l /home/fezadano5/cron
```

## Sorun Cikarsa

Sitemap guncellenmiyorsa:

```text
generate-sitemap.php manuel calistir.
public_html/sitemap*.xml timestamp kontrol et.
robots ve sitemap URL'lerini curl ile kontrol et.
```

Planli makale yayinlanmiyorsa:

```text
publish-scheduled.php manuel calistir.
DB'de scheduled ve publish_at degerlerini kontrol et.
Admin saglik panelindeki uyarilara bak.
```

Yedek alinmiyorsa:

```text
backup-db.php manuel calistirmadan once R2 .env degerlerini kontrol et.
R2 credential, bucket ve permission ayarlarini dogrula.
Disk ve kota limitlerini kontrol et.
```
