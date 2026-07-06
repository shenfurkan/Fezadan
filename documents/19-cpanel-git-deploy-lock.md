# 19. cPanel Git Deploy Kilidi

Bu belge cPanel Git Version Control ekraninda gorulen su hatanin proje baglamindaki nedenini ve kesin kontrol adimlarini anlatir:

```text
The system cannot deploy
No uncommitted changes exist on the checked-out branch.
```

cPanel Git deploy icin iki temel sart vardir:

- Repo kokunde checked-in bir `.cpanel.yml` bulunmali.
- Deploy butonuna basmadan once Git working tree tamamen temiz olmali.

Kaynak: https://docs.cpanel.net/knowledge-base/web-services/guide-to-git-deployment/

## Bu Projede Ne Oldu?

Sorun ilk basta `.env` gibi gorundu, ama asil mekanizma suydu:

1. Eski `.cpanel.yml` cleanup komutlari, cPanel'in Git checkout olarak kullandigi dizinin icinde calisti.
2. Bu cleanup, tracked dosyalari sildi: `.gitignore`, `.env.example`, README/KURULUM/LICENSE veya yardimci klasorler.
3. `.gitignore` silinince live checkout icindeki `.env` artik ignore edilmeyebilir hale geldi.
4. Git working tree kirli oldu: tracked deletion ve/veya untracked dosyalar.
5. cPanel temiz working tree goremedigi icin deploy butonunu kilitledi.

Sonraki canli terminal ciktisinda `.env` duzelmis durumdaydi:

```text
.gitignore:2:.env       .env
```

Bu satir varsa `.env` artik deploy kilidinin sebebi degildir. O anda kalan gercek kilit suydu:

```text
?? public/test.txt
```

Yani deploy'u engelleyen aktif dosya untracked `public/test.txt` idi.

## Hata Ciktisi Nasil Okunur?

Her zaman once su komutla basla:

```bash
cd /home/fezadano5/captcha
git status --short --untracked-files=all
```

Anlamlari:

```text
?? dosya          Git tarafindan takip edilmeyen yeni dosya.
 D dosya          Tracked dosya silinmis.
 M dosya          Tracked dosya degismis.
A  dosya          Yeni dosya stage edilmis.
```

cPanel deploy icin bu cikti bos olmalidir.

## .env Kontrolu

`.env` silinmemeli. Canli sunucuda kalir, Git tarafindan ignore edilir.

Dogru kontrol:

```bash
git check-ignore -v .env
```

Basarili cikti ornegi:

```text
.gitignore:2:.env       .env
```

Bu cikti varsa `.env` tamamdir. `git status` icinde `.env` gorunmemelidir.

## LiteCaptcha Kurtarma Komutlari

Canli cPanel terminalinde:

```bash
cd /home/fezadano5/captcha
git status --short --untracked-files=all
git restore .gitignore README.md KURULUM.md LICENSE .env.example
git check-ignore -v .env
git status --short --untracked-files=all
```

Eger kalan tek satir buysa:

```text
?? public/test.txt
```

ve dosya gercekten test dosyasiysa temizle:

```bash
rm -f public/test.txt
git status --short --untracked-files=all
```

Son komut hic cikti vermemelidir.

## Fezadan Ana Repo Kontrolu

Ayni sinif problem ana repo icin de olabilir:

```bash
cd /home/fezadano5
git status --short --untracked-files=all
git restore .gitignore AGENTS.md README.md .env.example package.json package-lock.json app/Controllers/AuthorController.php scripts migrations documents tests storage tmp
git check-ignore -v .env
git status --short --untracked-files=all
```

Bu komutlar `.env` dosyasini silmez. Sadece repo tarafinda silinmis tracked dosyalari geri getirir.

## .git/info/exclude Fallback

Bunu sadece `git check-ignore -v .env` hic cikti vermiyorsa kullan.

Hatali bicim:

```bash
printf "\n.env\n.env.*\n!.env.example\n" >> .git/info/exclude
```

Bash icinde `!` history expansion'a takilip su hatayi verebilir:

```text
event not found
```

Guvenli bicim:

```bash
printf '%s\n' '.env' '.env.*' '!.env.example' >> .git/info/exclude
git check-ignore -v .env
```

Ama `.gitignore:2:.env .env` ciktisi zaten varsa bu fallback gereksizdir.

## Deploy Oncesi Kesin Kabul Kriteri

LiteCaptcha icin:

```bash
cd /home/fezadano5/captcha
git check-ignore -v .env
git status --short --untracked-files=all
```

Basarili durum:

```text
git check-ignore -v .env bir kaynak gosterir.
git status --short --untracked-files=all hic cikti vermez.
```

Bu iki sart saglanmadan cPanel deploy butonuna basma.
