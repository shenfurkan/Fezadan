# Portfolio — Ayrı Bir Proje

**Portfolio** (furkan.fezadan.org), Fezadan PHP projesinden **tamamen bağımsız** bir Next.js uygulamasıdır. Bu dosya sadece referans amaçlıdır.

## Özet

| Özellik | Detay |
|---------|-------|
| Amaç | Fotoğraf ve çizim galerisi |
| Dil | TypeScript / React |
| Framework | Next.js |
| Konum | Portfolio/ klasörü (proje kökünde) |
| Bağımlılık | **Fezadan ile Sıfır kod bağımlılığı** |
| Deploy | Ayrı sunucu / ayrı domainde yayınlanır |

## Önemli Notlar

- Fezadan'ın PHP kodları **Portfolio'ya hiçbir şekilde bağımlı değildir**
- Portfolio'nun kendi package.json, Dockerfile, next.config.js dosyaları vardır
- İki proje aynı Git repo'sunda durur ama .gitignore ile ayrılmıştır
- Güncelleme/build işlemleri tamamen bağımsızdır
