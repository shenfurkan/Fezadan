# 21. .env ve cPanel Incident Runbook

Bu belge 18 Haziran 2026'da yasanan cPanel deploy kilidi olayini kayda gecirir. Amac, ayni hata tekrar oldugunda tahminle degil ciktiya bakarak ilerlemektir.

## Olay Ozeti

Belirti:

```text
The system cannot deploy
For deployment, ensure that your repository meets the following requirements:
1. A valid .cpanel.yml file exists.
2. No uncommitted changes exist on the checked-out branch.
```

Ilk suphe `.env` idi. Son canli terminal ciktisi sorunun artik `.env` olmadigini gosterdi:

```text
git check-ignore -v .env
.gitignore:2:.env       .env
```

Bu cikti su anlama gelir:

```text
.env Git tarafindan ignore ediliyor.
.env deploy'u kilitleyen aktif dosya degil.
```

Kalan gercek sorun:

```text
git status --short --untracked-files=all
?? public/test.txt
```

Bu durumda cPanel deploy'u kilitleyen dosya `public/test.txt` olur.

## Hemen Yapilacak Islem

Eger `public/test.txt` sadece test dosyasiysa:

```bash
cd /home/fezadano5/captcha
rm -f public/test.txt
git status --short --untracked-files=all
```

Basari:

```text
Son git status komutu hic cikti vermemeli.
```

Ardindan cPanel'de:

```text
Update from Remote
Deploy HEAD Commit
```

## Ciktiya Gore Aksiyon Tablosu

| Cikti | Anlam | Aksiyon |
|---|---|---|
| `.gitignore:2:.env .env` | `.env` ignore ediliyor | `.env` ile ugrasma, `git status` icindeki diger dosyalara bak |
| `?? public/test.txt` | Untracked test dosyasi var | Test dosyasiysa `rm -f public/test.txt` |
| `?? .env` | `.env` ignore edilmiyor | `.gitignore` restore et veya `.git/info/exclude` fallback kullan |
| ` D .gitignore` | `.gitignore` tracked dosyasi silinmis | `git restore .gitignore` |
| ` D .env.example` | Ornek env dosyasi silinmis | `git restore .env.example` |
| ` M .cpanel.yml` | Deploy config localde degismis | Degisikligin commit'te olup olmadigini kontrol et, gerekirse restore |
| Bos cikti | Working tree temiz | cPanel deploy denenebilir |

## Standart LiteCaptcha Kontrol Sirasi

Her deploy sorunu icin ayni sirayi izle:

```bash
cd /home/fezadano5/captcha
git status --short --untracked-files=all
git check-ignore -v .env
```

Eger `.gitignore` silinmisse:

```bash
git restore .gitignore README.md KURULUM.md LICENSE .env.example
git check-ignore -v .env
git status --short --untracked-files=all
```

Eger `.env` hala ignore edilmiyorsa:

```bash
printf '%s\n' '.env' '.env.*' '!.env.example' >> .git/info/exclude
git check-ignore -v .env
```

Not: `printf "\n.env\n.env.*\n!.env.example\n"` bicimi Bash'te `!` yuzunden `event not found` hatasi verebilir. Tek tirnakli `printf '%s\n' ...` bicimini kullan.

## Standart Fezadan Ana Repo Kontrol Sirasi

Ana repo icin:

```bash
cd /home/fezadano5
git status --short --untracked-files=all
git check-ignore -v .env
```

Eger cleanup tracked dosyalari sildiyse:

```bash
git restore .gitignore AGENTS.md README.md .env.example package.json package-lock.json app/Controllers/AuthorController.php scripts migrations documents tests storage tmp
git status --short --untracked-files=all
```

## Ne Yapilmamali?

`.env` dosyasini panikle silme:

```text
.env canli ayarlari tutar. Dogru durum, dosyanin var olmasi ama Git tarafindan ignore edilmesidir.
```

Genis `git clean -fd` calistirma:

```text
Canli sunucuda upload, cache, runtime token veya baska dosyalari silebilir.
Sadece ciktida gordugun ve ne oldugunu bildigin dosyayi temizle.
```

`.gitignore` dosyasini deploy cleanup icinde silme:

```text
.gitignore silinirse .env tekrar untracked gorunebilir.
Bu, cPanel deploy kilidini yeniden uretir.
```

## Kalici Cozum Kontrolu

Deploy scriptlerinde su guard bulunmali:

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

Bu guard, cPanel'in repo checkout dizinini kaynak kod olarak korur ve deploy cleanup'in repo dosyalarini silmesini engeller.
