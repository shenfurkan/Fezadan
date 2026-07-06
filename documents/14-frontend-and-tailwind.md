# 14. Frontend ve TailwindCSS Geliştirme Rehberi

## Genel Mimari

Fezadan'ın frontend katmanı, harici CSS/JS framework'lerine bağımlı olmadan çalışır. Tüm varlıklar (CSS, JS, fontlar) projenin kendi `public_html/assets/` klasöründe yerel olarak barındırılır.

| Katman | Teknoloji | Konum |
|--------|-----------|-------|
| CSS Framework | TailwindCSS v4 | `assets/css/` |
| Fontlar | WOFF2 (yerel) | `assets/fonts/` |
| Genel JS | Vanilla JavaScript (ES6) | `assets/js/main.js` |
| Admin JS | Vanilla + Summernote + jQuery | `assets/js/admin.js`, `yonetim-editor.js` |
| PDF Görüntüleyici | PDF.js (Mozilla, yerel) | `assets/js/pdf.mjs`, `pdf.worker.mjs` |
| DOCX İçe Aktarma | Mammoth.js (yerel) | `assets/js/mammoth.browser.min.js` |
| QR Kod | qrcode.js (yerel) | `assets/js/qrcode.min.js` |

---

## TailwindCSS v4 Kurulumu ve Kullanımı

### Dosya Yapısı

```
public_html/assets/css/
├── input.css          ← Tailwind yapılandırması (kaynak)
├── style.css          ← Derlenmiş çıktı (git'e dahil)
└── fonts.css          ← @font-face tanımları
```

### Komutlar

```bash
npm run dev    # Watch modu: input.css → style.css (geliştirme)
npm run build  # Production: input.css → style.css (minify)
```

### input.css İçeriği

[public_html/assets/css/input.css](file:///d:/Fezadan/public_html/assets/css/input.css) dosyası Tailwind v4 `@import` direktifi ve özel tema renklerini tanımlar:

```css
@import "tailwindcss";

@theme {
    --color-main: #6D2323;      /* Ana koyu kırmızı/kahve */
    --color-paper: #FEF9E1;     /* Açık krem arka plan */
    --color-accent: #A31D1D;    /* Vurgu kırmızısı */
}
```

Bu renkler Tailwind utility sınıflarında `bg-main`, `text-paper`, `border-accent` olarak kullanılabilir.

---

## Font Sistemi

Fezadan, ziyaretçi IP'sinin üçüncü parti servislere (Google Fonts vb.) sızmaması için **tüm fontları WOFF2 formatında yerel olarak** barındırır.

| Font | Kullanım Alanı | Ağırlıklar |
|------|---------------|-----------|
| **Syne** | Başlıklar, logo, hero alanları | Regular, 500, 600, 700, 800 |
| **EB Garamond** | Ana metin, makale içeriği | Regular, Italic, 500–800 |
| **Space Grotesk** | UI elementleri, butonlar, menüler | 300, 400, 500, 600, 700 |
| **Bebas Neue** | Büyük sayılar, grid düzenleri | Regular |
| **JetBrains Mono** | Kod blokları, admin panel, teknik içerik | 100–800 (tüm ağırlıklar) |

Tüm fontlar `fonts.css` içinde `@font-face` ile tanımlanır ve `font-display: swap` kullanılarak metinlerin font yüklenmeden önce sistem fontuyla görünmesi sağlanır (FOUT önleme).

```css
@font-face {
    font-family: 'Syne';
    src: url('../fonts/syne-regular.woff2') format('woff2');
    font-weight: 400;
    font-style: normal;
    font-display: swap;
    unicode-range: U+0000-00FF, U+0131, U+0152-0153, ...;
}
```

Latin Extended karakter seti sayesinde Türkçe karakterler (İ, ğ, Ü, Ş, Ç, Ö) sorunsuz görüntülenir.

---

## JavaScript Dosyaları

### main.js — Genel Frontend

[public_html/assets/js/main.js](file:///d:/Fezadan/public_html/assets/js/main.js) tüm ziyaretçi sayfalarında yüklenir. İçerir:
- Mobil menü toggle
- Scroll tabanlı header davranışı
- Tema değiştirme (dark/light)
- Lazy loading görseller

### admin.js — Yönetim Paneli

[public_html/assets/js/admin.js](file:///d:/Fezadan/public_html/assets/js/admin.js) sadece `/yonetim/*` sayfalarında yüklenir:
- Form doğrulama
- Makale içeriği önizleme
- Medya yükleme arayüzü

### yonetim-editor.js — Zengin Metin Editörü

[public_html/assets/js/yonetim-editor.js](file:///d:/Fezadan/public_html/assets/js/yonetim-editor.js) Summernote editörünü yapılandırır:
- Görsel yükleme (R2 pipeline)
- DOCX içe aktarma (Mammoth.js ile)
- OG görsel üretme tetikleyici

### mammoth.browser.min.js — Word İçe Aktarma

[public_html/assets/js/mammoth.browser.min.js](file:///d:/Fezadan/public_html/assets/js/mammoth.browser.min.js) admin panelindeki zengin metin editöründe `.docx` dosyalarını HTML'e dönüştürmek için kullanılır. **Harici CDN'den değil, CSP `'self'` kuralına uygun şekilde yerel dosyadan yüklenir.**

### PDF.js — PDF Görüntüleyici

[public_html/assets/js/pdf.mjs](file:///d:/Fezadan/public_html/assets/js/pdf.mjs) ve [pdf.worker.mjs](file:///d:/Fezadan/public_html/assets/js/pdf.worker.mjs), `notlar.fezadan.org` alt sitesinde PDF'leri tarayıcı içinde görüntülemek için kullanılır.

### jQuery (admin paneli için)

[public_html/assets/js/jquery.min.js](file:///d:/Fezadan/public_html/assets/js/jquery.min.js) sadece admin panelinde Summernote editörünün bağımlılığı olarak bulunur. **Ziyaretçi sayfalarında jQuery kullanılmaz.**

---

## Responsive Tasarım

Sistem **mobile-first** yaklaşımı ile tasarlanmıştır. TailwindCSS'in responsive prefix'leri kullanılır:

```
sm:  → 640px+
md:  → 768px+
lg:  → 1024px+
xl:  → 1280px+
```

Tüm görseller lazy loading ile yüklenir (`loading="lazy"` özniteliği).

---

## CSP ve Frontend Etkileşimi

Content Security Policy (CSP) başlığı frontend geliştirmede şu kısıtlamaları getirir:

1. **Katı İzolasyon:** Ziyaretçi tarafında `'unsafe-inline'` ve `'unsafe-eval'` izinleri tamamen kaldırılmıştır. Bu kurallar sadece `/yonetim` rotasında (Mammoth ve Summernote bağımlılıkları sebebiyle) esnetilir.
2. **Nonce Zorunluluğu:** Kullanılması mecburi olan inline `<script>` etiketleri (örn. sunucudan tarayıcıya JSON verisi aktarırken) `nonce="<?= CSP_NONCE ?>"` özniteliği taşımak zorundadır.
3. **JS Dosyalarına Ayırma:** Eskiden `read.php` veya `login.php` gibi view dosyalarında bulunan business logic, tamamen `.js` uzantılı dış dosyalara taşınmıştır.
4. **Event Handler Kısıtı:** Inline `onclick`, `onload` gibi event handler'lar çalışmaz. Tüm event'ler harici JS dosyasından `addEventListener` ile bağlanmalıdır.
5. **Harici Kaynaklar:** Harici CDN'lerden script/font yükleme izni yoktur (bazı istisnalar: jsDelivr, Cloudflare CDN).

---

## Stil Geliştirme Akışı

1. `npm run dev` ile Tailwind watch modunu başlatın.
2. View dosyalarında (`app/Views/`) Tailwind utility sınıflarını kullanın.
3. Özel stiller için `input.css` dosyasına `@layer` içinde ekleme yapın.
4. Değişiklikler anında `style.css` dosyasına yansır.
5. Canlıya almadan önce `npm run build` ile minify edilmiş sürümü oluşturun.
