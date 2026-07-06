# 22. cPanel Git Control Panel

Bu belge cPanel -> Git Version Control ekraninin bu projede nasil okunacagini anlatir. Amac, deploy hatasinda tahmin etmek yerine paneldeki alanlari dogru yorumlamaktir.

## Paneldeki Alanlar

### Repository Path

cPanel'in Git checkout olarak gordugu dizindir. Bu dizin normal bir Git working tree'dir ve deploy butonu basmadan once temiz olmalidir.

Bu projede iki onemli checkout vardir:

```text
Fezadan ana site: /home/fezadano5
LiteCaptcha:      /home/fezadano5/captcha
```

Bu path altinda `.git` klasoru varsa cPanel burayi repo olarak yonetir. Bu dizinde elle dosya olusturmak, silmek veya File Manager ile test dosyasi atmak deploy'u kilitleyebilir.

### Remote URL

GitHub reposunu gosterir. Ornek:

```text
https://github.com/shenfurkan/LITECAPTCHA.git
```

Remote dogruysa `Update from Remote` son GitHub commit'lerini bu checkout'a ceker.

### Currently Checked-Out Branch

Aktif branch'tir. Bu projelerde beklenen branch:

```text
main
```

Yanlis branch seciliyse panelde HEAD commit beklenen commit olmayabilir.

### HEAD Commit

Canli checkout'in su anda hangi commiti tuttugunu gosterir. `Update from Remote` sonrasi GitHub'daki son commit ile eslesmesi gerekir.

### Last Deployment Information

En son basarili deploy edilen commit bilgisidir. HEAD Commit daha yeni, Last Deployed SHA daha eski olabilir. Bu normaldir: kod cekilmis ama deploy henuz basarili calismamis demektir.

## Butonlar Ne Yapar?

### Update from Remote

GitHub'dan son commitleri ceker. Bu, deploy degildir.

Beklenen etki:

```text
origin/main guncellenir.
working tree clean ise checkout yeni HEAD'e gelir.
```

Kilitlenebilecegi durum:

```text
local untracked veya modified dosyalar remote dosyalarla cakisirsa update sorun cikarabilir.
```

### Deploy HEAD Commit

Checked-out HEAD commit uzerindeki `.cpanel.yml` tasks listesini calistirir.

cPanel bu butonu sadece su sartlarda saglikli calistirir:

```text
.cpanel.yml repo kokunde var.
working tree clean.
branch checked out durumda.
```

## Clean Working Tree Sarti

Paneldeki "No uncommitted changes exist on the checked-out branch" maddesi su komutun bos cikmasi demektir:

```bash
git status --short --untracked-files=all
```

Bos cikti basaridir.

Ornek kilitler:

```text
?? public/test.txt     untracked test dosyasi
?? .env                .env ignore edilmiyor
 D .gitignore          tracked dosya silinmis
 M .cpanel.yml         deploy config localde degismis
```

## .env Panelde Nasil Ele Alinir?

`.env` canli ayar dosyasidir. Git'e girmemelidir, ama Git status'te de gorunmemelidir.

Dogru kontrol:

```bash
git check-ignore -v .env
```

Basarili cikti:

```text
.gitignore:2:.env       .env
```

Bu cikti varsa `.env` dogru durumdadir. Deploy kilidi devam ediyorsa sorun baska dosyadadir.

## .cpanel.yml Bu Projede Ne Yapar?

Fezadan ana sitede `.cpanel.yml`:

```text
app/          -> /home/fezadano5/app/
cron/         -> /home/fezadano5/cron/
public_html/  -> /home/fezadano5/public_html/
composer.*    -> /home/fezadano5/
```

LiteCaptcha'da `.cpanel.yml`:

```text
public/       -> /home/fezadano5/captcha/public/
```

Eger repo checkout ile deploy hedefi ayniysa cleanup tracked dosya silmemelidir. Bu nedenle guard kullanilir:

```bash
if [ "${REPO_ROOT}" != "${DEPLOYPATH}" ]; then
  # cleanup only separate deployment target
fi
```

Fezadan ana repo icin:

```bash
if [ "${REPO_ROOT}" != "${APPROOT}" ]; then
  # cleanup only separate deployment target
fi
```

## Deploy Loglari

cPanel deploy denemelerinin loglari:

```text
/home/fezadano5/.cpanel/logs/vc_*_git_deploy.log
```

En yeni logu bulmak:

```bash
ls -t /home/fezadano5/.cpanel/logs/vc_*_git_deploy.log | head -5
```

Logda bakilacak seyler:

```text
hangi command fail etti
hangi dosya path'i problemli
permission denied var mi
working tree dirty mi
.cpanel.yml parse edildi mi
```

## Guvenli Panel Akisi

LiteCaptcha icin:

```bash
cd /home/fezadano5/captcha
git status --short --untracked-files=all
git check-ignore -v .env
```

Status bos ise:

```text
1. cPanel -> Update from Remote
2. cPanel -> Deploy HEAD Commit
3. Last Deployment SHA'nin HEAD'e yaklastigini kontrol et
```

Status bos degilse once ilgili dosyayi duzelt. Rastgele `git clean -fd` kullanma.

## Private GitHub Repo ve SSH Baglantisi

Fezadan ve LiteCaptcha repo'lari private ise cPanel Git Version Control remote repository'ye SSH ile baglanmalidir. Public repo kullanmak kodun, deploy notlarinin ve yanlislikla kalmis dosya adlarinin herkese acilmasi riskini buyutur.

Beklenen remote URL formati:

```text
git@github.com:shenfurkan/FezadanDeveloper.git
```

cPanel hesabinda SSH dosyalari genelde buradadir:

```text
/home/fezadano5/.ssh/
```

Bu klasorde su dosyalarin rolleri karistirilmamalidir:

```text
id_rsa          private key; GitHub'a yapistirilmaz
id_rsa.pub      public key; GitHub Deploy Keys'e bu eklenir
config          hangi host icin hangi key kullanilacak
known_hosts     GitHub host fingerprint kaydi; deploy key degildir
authorized_keys hesaba giris icin kullanilir; GitHub deploy key degildir
```

GitHub tarafinda eklenecek key:

```text
Settings -> Deploy keys -> Add deploy key
Key: /home/fezadano5/.ssh/id_rsa.pub icerigi
Allow write access: kapali
```

`/home/fezadano5/.ssh/config` dosyasi su sekilde olmalidir:

```sshconfig
Host github.com
  HostName github.com
  User git
  IdentityFile ~/.ssh/id_rsa
  IdentitiesOnly yes
```

Sonra terminalden test edilir:

```bash
ssh -T git@github.com
```

Basarili durumda GitHub su sekilde bir mesaj verir:

```text
Hi <owner>/<repo>! You've successfully authenticated, but GitHub does not provide shell access.
```

### `ssh -T git@github.com` Sifre Isterse

Bu kritik ayrimdir.

Eger sunu soruyorsa:

```text
Enter passphrase for key '/home/fezadano5/.ssh/id_rsa':
```

Bu GitHub sifresi degildir. SSH key olusturulurken verilen key passphrase'idir. Terminalde passphrase girilince baglanti basarili olabilir, fakat cPanel Git arayuzu bu passphrase'i interaktif olarak giremez. Bu nedenle terminalde `ssh -T git@github.com` basarili olup cPanel panelinde `Permission denied (publickey)` hatasi alinabilir.

Bu durumda yorum:

```text
SSH key dogru.
GitHub deploy key dogru.
Repo erisimi dogru.
Sorun cPanel Git UI'nin passphrase sorusunu cevaplayamamasi.
```

cPanel Git Version Control icin en temiz cozum passphrase'siz, sadece bu repo icin tanimli, read-only deploy key olusturmaktir:

```bash
ssh-keygen -t ed25519 -C "cpanel-fezadan-deploy" -f ~/.ssh/id_fezadan_deploy -N ""
cat ~/.ssh/id_fezadan_deploy.pub
```

`id_fezadan_deploy.pub` icerigi GitHub repo ayarlarina eklenir:

```text
Settings -> Deploy keys -> Add deploy key
Allow write access: kapali
```

Sonra `/home/fezadano5/.ssh/config` bu key'i gostermelidir:

```sshconfig
Host github.com
  HostName github.com
  User git
  IdentityFile ~/.ssh/id_fezadan_deploy
  IdentitiesOnly yes
```

Izinler:

```bash
chmod 700 ~/.ssh
chmod 600 ~/.ssh/id_fezadan_deploy
chmod 644 ~/.ssh/id_fezadan_deploy.pub
chmod 600 ~/.ssh/config
```

Tekrar test:

```bash
ssh -T git@github.com
```

Bu test passphrase sormadan basari mesaji vermelidir. Ancak bundan sonra cPanel Git panelindeki `Update from Remote`, `Clone`, veya `Deploy HEAD Commit` adimlari denenir.

### Ikinci private repo icin ikinci deploy key

Fezadan ve LiteCaptcha ayni GitHub hesabinda olsa bile ayni SSH key'e baglanmamalidir. Dogru model:

```text
Fezadan repo     -> id_fezadan_deploy
LiteCaptcha repo -> id_litecaptcha_deploy
```

GitHub deploy key repo bazlidir. `id_fezadan_deploy.pub` sadece Fezadan repo ayarlarina, `id_litecaptcha_deploy.pub` sadece LiteCaptcha repo ayarlarina eklenir. GitHub'a private key degil, sadece `.pub` dosyasinin icerigi yapistirilir. "Allow write access" kapali kalir.

cPanel hesabinda beklenen dosyalar:

```text
/home/fezadano5/.ssh/config
/home/fezadano5/.ssh/id_fezadan_deploy
/home/fezadano5/.ssh/id_fezadan_deploy.pub
/home/fezadano5/.ssh/id_litecaptcha_deploy
/home/fezadano5/.ssh/id_litecaptcha_deploy.pub
```

`~/.ssh/config` tam hali:

```sshconfig
Host github.com
  HostName github.com
  User git
  IdentityFile ~/.ssh/id_fezadan_deploy
  IdentitiesOnly yes

Host litecaptcha.github.com
  HostName github.com
  User git
  IdentityFile ~/.ssh/id_litecaptcha_deploy
  IdentitiesOnly yes
```

Buradaki `litecaptcha.github.com` gercek bir web sitesi olmak zorunda degildir. Bu, SSH config icinde bir `Host` alias'idir. OpenSSH bu alias'i yakalar ve `HostName github.com` satiri nedeniyle gercek baglantiyi yine GitHub'a yapar; fakat bu kez `id_litecaptcha_deploy` key'i kullanilir.

cPanel Git Version Control clone formu `github-litecaptcha` gibi noktasiz alias'lari "fully-qualified domain name degil" diye reddedebilir. Bu yuzden alias nokta iceren `litecaptcha.github.com` seklinde yazilir.

LiteCaptcha clone URL:

```text
git@litecaptcha.github.com:shenfurkan/LITECAPTCHA.git
```

Fezadan remote URL normal kalabilir:

```text
git@github.com:shenfurkan/<FEZADAN_PRIVATE_REPO>.git
```

Test sirasi:

```bash
ssh -T git@github.com
ssh -T git@litecaptcha.github.com
```

Ilk test Fezadan deploy key'ini, ikinci test LiteCaptcha deploy key'ini dener. LiteCaptcha testinden sonra cPanel'de clone URL olarak `git@github.com:shenfurkan/LITECAPTCHA.git` yazilmaz; bu yazilirsa `Host github.com` blogu calisir ve Fezadan key'i kullanilir. LiteCaptcha icin mutlaka `git@litecaptcha.github.com:shenfurkan/LITECAPTCHA.git` yazilir.

Guvenlik notlari:

```text
1. GitHub'a sadece .pub key eklenir.
2. Private key dosyalari repo'ya, public_html'e veya dokumana konmaz.
3. Deploy key read-only kalir.
4. Her repo kendi key'iyle sinirlanir.
5. Key olustururken sifre/passphrase verilmemesi cPanel icin normaldir; cPanel interaktif passphrase giremez.
```

Eger sunu soruyorsa:

```text
git@github.com's password:
```

Bu yanlistir. GitHub SSH ile hesap sifresi kabul etmez. Bu durumda key hic kullanilmiyor demektir. Kontrol sirasi:

```bash
ls -la ~/.ssh
cat ~/.ssh/config
ssh -vT git@github.com
```

Bakilacak seyler:

```text
~/.ssh/config icinde IdentityFile ~/.ssh/id_rsa var mi?
id_rsa dosyasi gercekten var mi?
id_rsa.pub icerigi GitHub Deploy Keys'e eklenmis mi?
Deploy key read-only ve dogru private repo'ya ekli mi?
known_hosts dosyasina key yapistirilmamis mi?
Repository URL git@github.com:... formatinda mi?
```

Izinler bozuksa duzelt:

```bash
chmod 700 ~/.ssh
chmod 600 ~/.ssh/id_rsa
chmod 644 ~/.ssh/id_rsa.pub
chmod 600 ~/.ssh/config
```

Son test:

```bash
ssh -T git@github.com
git fetch origin
```

Bu iki komut basarili olmadan cPanel'deki `Update from Remote` veya `Deploy HEAD Commit` adimina gecilmez.

## 2026-06-18 Uygulanan SSH Deploy Key Akisi

Bu bolum, Fezadan ve LiteCaptcha private repo gecisinde gercekten uygulanan akisi anlatir. Amac ayni hataya tekrar dusmemek ve cPanel Git panelinde hangi hata mesajinin ne anlama geldigini hizli okumaktir.

### Problem neydi?

cPanel Git Version Control private GitHub repo'ya baglanirken SSH key kullanir. Baslangicta `~/.ssh/config` icinde sadece `github.com` icin tek key seciliyordu:

```sshconfig
Host github.com
  HostName github.com
  User git
  IdentityFile ~/.ssh/id_fezadan_deploy
  IdentitiesOnly yes
```

Bu durumda `git@github.com:...` ile hangi repo klonlanirsa klonlansin ayni key denenir. Bu Fezadan icin dogru olabilir, fakat LiteCaptcha icin yanlistir. GitHub deploy key repo bazlidir; Fezadan repo'ya eklenen key LiteCaptcha repo'yu okuyamaz.

Gorulen hata:

```text
ERROR: Repository not found.
fatal: Could not read from remote repository.
```

Bu hata repo yok anlamina gelmek zorunda degildir. Private repo'da cok sik su anlama gelir:

```text
Kullanilan SSH key bu repo'ya yetkili degil.
```

### LiteCaptcha icin ikinci key neden gerekti?

Dogru model:

```text
FezadanDeveloper repo -> id_fezadan_deploy
LITECAPTCHA repo      -> id_litecaptcha_deploy
```

Her repo kendi deploy key'iyle sinirlanir. Boylece LiteCaptcha key'i calinirsa Fezadan repo okunamaz; Fezadan key'i calinirsa LiteCaptcha repo okunamaz. Deploy key'lerde `Allow write access` kapali kalir.

### cPanel neden `github-litecaptcha` alias'ini kabul etmedi?

SSH icin su alias normalde calisir:

```sshconfig
Host github-litecaptcha
  HostName github.com
  User git
  IdentityFile ~/.ssh/id_litecaptcha_deploy
  IdentitiesOnly yes
```

Fakat cPanel clone formu bunu URL olarak dogrularken su hatayi verdi:

```text
The clone URL must include a valid IP address or a fully-qualified domain name.
```

Sebep: `github-litecaptcha` noktasiz oldugu icin cPanel bunu fully-qualified domain name saymadi.

Bu yuzden alias nokta iceren bir isimle yazildi:

```sshconfig
Host litecaptcha.github.com
  HostName github.com
  User git
  IdentityFile ~/.ssh/id_litecaptcha_deploy
  IdentitiesOnly yes
```

Burada `litecaptcha.github.com` gercek bir web sitesi veya GitHub subdomain'i olmak zorunda degildir. Bu sadece OpenSSH `Host` alias'idir. SSH bu ismi config icinde yakalar, sonra `HostName github.com` satiri nedeniyle gercek baglantiyi GitHub'a yapar.

cPanel formuna girilen LiteCaptcha clone URL:

```text
git@litecaptcha.github.com:shenfurkan/LITECAPTCHA.git
```

Yanlis olan:

```text
git@github.com:shenfurkan/LITECAPTCHA.git
```

Cunku bu yazilirsa `Host github.com` blogu calisir ve Fezadan key'i kullanilir.

### Final SSH config hedefi

`/home/fezadano5/.ssh/config` dosyasinin hedef hali:

```sshconfig
Host github.com
  HostName github.com
  User git
  IdentityFile ~/.ssh/id_fezadan_deploy
  IdentitiesOnly yes

Host litecaptcha.github.com
  HostName github.com
  User git
  IdentityFile ~/.ssh/id_litecaptcha_deploy
  IdentitiesOnly yes
```

Beklenen dosyalar:

```text
/home/fezadano5/.ssh/id_fezadan_deploy
/home/fezadano5/.ssh/id_fezadan_deploy.pub
/home/fezadano5/.ssh/id_litecaptcha_deploy
/home/fezadano5/.ssh/id_litecaptcha_deploy.pub
```

`.pub` dosyalari GitHub Deploy Keys'e eklenir. Private key dosyalari GitHub'a, repo'ya, `public_html` altina veya dokumana konmaz.

### Fezadan tarafinda gorulen hata

Fezadan repo testinde su hata goruldu:

```text
no such identity: /home/fezadano5/.ssh/id_fezadan_deploy: No such file or directory
Permission denied (publickey).
fatal: Could not read from remote repository.
```

Bu su demektir:

```text
SSH config id_fezadan_deploy dosyasini istiyor, fakat dosya sunucuda yok.
```

Gecici olarak `id_rsa` denenince su sonuc alindi:

```text
Enter passphrase for key '/home/fezadano5/.ssh/id_rsa':
Hi shenfurkan/FezadanDeveloper! You've successfully authenticated, but GitHub does not provide shell access.
```

Bu sonuc GitHub yetkisinin dogru oldugunu gosterir, fakat cPanel icin yeterli degildir. Cunku terminalde insan passphrase girebilir; cPanel Git paneli interaktif passphrase giremez.

### `.env` icine SSH passphrase koyulmaz

SSH key passphrase'i `.env` icine yazilmaz.

Sebep:

```text
1. cPanel Git paneli SSH passphrase icin .env okumaz.
2. SSH passphrase GitHub sifresi degildir; ssh surecinin interaktif sordugu key sifresidir.
3. .env icine passphrase koymak secret sizintisi riskini buyutur.
4. .env backup, log, debug veya yanlis deploy ile gorunebilir.
```

Dogru cozum, cPanel deploy icin passphrase'siz ama repo bazli read-only deploy key kullanmaktir.

Fezadan icin passphrase'siz key olusturma:

```bash
ssh-keygen -t ed25519 -f ~/.ssh/id_fezadan_deploy -C "fezadan-cpanel-deploy" -N ""
chmod 600 ~/.ssh/id_fezadan_deploy
chmod 644 ~/.ssh/id_fezadan_deploy.pub
cat ~/.ssh/id_fezadan_deploy.pub
```

`cat` ile yazdirilan public key GitHub'da su repoya eklenir:

```text
shenfurkan/FezadanDeveloper
Settings -> Deploy keys -> Add deploy key
Allow write access: kapali
```

Sonra test:

```bash
ssh -T git@github.com
cd /home/fezadano5/repositories/Fezadan
git ls-remote origin
```

Bu testlerde passphrase sormamasi gerekir. Passphrase soruyorsa cPanel icin hazir degildir.

LiteCaptcha testleri:

```bash
ssh -T git@litecaptcha.github.com
cd /home/fezadano5/repositories/LiteCaptcha
git ls-remote origin
```

### cPanel ekraninda beklenen durum

LiteCaptcha:

```text
Repository Path: /home/fezadano5/repositories/LiteCaptcha
Remote URL:      git@litecaptcha.github.com:shenfurkan/LITECAPTCHA.git
Branch:          main
```

Fezadan:

```text
Repository Path: /home/fezadano5/repositories/Fezadan
Remote URL:      git@github.com:shenfurkan/FezadanDeveloper.git
Branch:          main
```

`Update from Remote` sadece GitHub'dan son commitleri ceker. `Deploy HEAD Commit` `.cpanel.yml` deploy akisini calistirir. Remote erisimi bozuksa once `Update from Remote`/`Try Again` duzeltilir; remote erisimi bozuk halde deploy'a guvenilmez.

## Faz 2 Kaldigimiz Yer

Bu noktada LiteCaptcha clone/deploy akisi cPanel ekraninda calisir hale geldi. Fezadan tarafinda kalan ana is, passphrase'siz `id_fezadan_deploy` key'ini olusturup `shenfurkan/FezadanDeveloper` Deploy Keys'e eklemektir.

Sira:

```text
1. Fezadan icin passphrase'siz id_fezadan_deploy olustur.
2. id_fezadan_deploy.pub icerigini FezadanDeveloper Deploy Keys'e ekle.
3. ssh -T git@github.com komutunun passphrase sormadan basari vermesini dogrula.
4. /home/fezadano5/repositories/Fezadan icinde git ls-remote origin calistir.
5. cPanel Fezadan ekraninda Try Again veya Update from Remote calistir.
6. Fezadan Deploy HEAD Commit calistir.
7. LiteCaptcha Deploy HEAD Commit sonucunu tekrar kontrol et.
8. public_html ve captcha/public altina .git, .env, documents, scripts, Docker, README gibi dosyalar dusmedi mi kontrol et.
9. Ana site, notlar, furkan, litecaptcha ve /yonetim login akisini test et.
10. Bunlar temizse server-cleanup-dry-run.sh ile temizlik fazina gec.
```

Temizlik fazina gecmeden once calistirilacak dry-run:

```bash
cd /home/fezadano5/repositories/Fezadan
scripts/server-cleanup-dry-run.sh
```

Bu betik silme veya tasima yapmaz; sadece hangi path'in `delete`, `quarantine`, `keep` veya `do-not-touch` sinifina girdigini yazdirir. Bu cikti incelenmeden `rm` veya `mv` calistirilmaz.
