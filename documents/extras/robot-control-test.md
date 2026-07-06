# LiteCaptcha — "İnsan mısınız?" Doğrulama Sistemi

## Genel Bakış

Fezadan projesinde standart CAPTCHA servisleri (Google reCAPTCHA, hCaptcha vb.) yerine, **tamamen özel yazılmış davranışsal bir robot tespit sistemi** bulunur. Sistem, kullanıcının insan olduğunu kanıtlaması için bir **kaçış oyunu (escape game)** sunar. Bu sistem artık **bağımsız bir proje** olarak `D:\litecaptcha\` dizininde geliştirilmektedir.

| Özellik | Detay |
|---------|-------|
| Proje | [LiteCaptcha](https://litecaptcha.fezadan.org) (bağımsız repo) |
| Konum | `D:\litecaptcha\public\index.php` (tek dosya) |
| Tür | Davranışsal analiz + kaçış oyunu + tarayıcı parmak izi |
| Bağımlılık | Yok (harici servis, veritabanı veya JavaScript kütüphanesi kullanmaz) |
| Backend | Saf PHP, session tabanlı |
| Frontend | Vanilla JavaScript (ES6), Canvas + Font parmak izi |
| Erişim | `litecaptcha.fezadan.org` (subdomain) |
| Entegrasyon | HMAC token redirect flow ile ana uygulamaya bağlı |

---

## Mimari

```
┌──────────────────────────────────────────────────────────┐
│              litecaptcha/public/index.php                │
│                                                          │
│  PHP Backend (satır 1-898)                              │
│  ├─ Session yönetimi (güvenli cookie'ler)                │
│  ├─ Challenge üretimi (nonce, PoW, rolling state)       │
│  ├─ Escape event doğrulama (token zinciri + PoW)        │
│  ├─ Browser proof doğrulama (canvas, font, rAF)         │
│  ├─ Pointer trace analizi (hareket, linearity, jitter)  │
│  ├─ Motion score hesaplama (randomize matris proj.)     │
│  └─ Proof pixel (gizli kaynak yükleme testi)            │
│                                                          │
│  HTML/CSS Arayüz (satır 900-1702)                       │
│  ├─ Dark/Light tema (Space Grotesk + Syne font)         │
│  ├─ Grid arka plan (Fezadan tasarım dili)               │
│  ├─ Honeypot tuzağı (görünmez buton)                    │
│  └─ Browser proof pixel (1px GIF)                       │
│                                                          │
│  JavaScript Frontend (satır 1203-1700)                  │
│  ├─ Boot-time sinyal toplama (webdriver, plugins)       │
│  ├─ rAF delta koleksiyonu (frame zamanlama)             │
│  ├─ Canvas parmak izi (Bezier eğrisi çizimi)            │
│  ├─ Font parmak izi (metin boyutu ölçümü)               │
│  ├─ Kaçış oyunu (cursor yaklaşınca buton kaçar)         │
│  ├─ Proof-of-work çözme (SHA-256 prefix, 250K iter)     │
│  └─ IP API sinyali (sadece yerel IP'ler için)           │
└──────────────────────────────────────────────────────────┘
```

---

## Doğrulama Akışı (Adım Adım)

### Aşama 1 — Challenge Üretimi (PHP)

Kullanıcı `/robot/` sayfasını her açtığında, sunucu benzersiz bir challenge üretir:

| Alan | Amaç |
|------|------|
| `nonce` | 32 karakter rastgele — istek token'ı |
| `issued_at` | Challenge'ın verilme zamanı (300 sn geçerlilik) |
| `required_escapes` | 3 — kaçması gereken buton sayısı |
| `next_event_token` | Her escape olayı için tek kullanımlık token |
| `pow_nonce` + `pow_difficulty` | Proof-of-work parametreleri |
| `rolling_state` | Escape olayları arası durum zinciri (hash) |
| `proof_pixel_nonce` | Gizli 1px GIF için nonce |
| `math_profile` | 3×3 randomize matris + eşik değerleri |
| `expected_buckets` | Escape başına delta/distance/angle bucket'ları |
| `init_left` / `init_top` | Buton başlangıç pozisyonu (randomize) |

**Geçerlilik Süresi:** 300 saniye. Süre dolarsa yeni challenge üretilir.

---

### Aşama 2 — Tarayıcı Ön Kontrolü (JavaScript)

Sayfa yüklenirken ~2.4 saniyelik "Tarayıcı Denetleniyor" ekranı gösterilir. Bu sırada:

1. **WebDriver tespiti:** `navigator.webdriver === true` kontrolü. Selenium/Puppeteer gibi otomasyon araçları anında yakalanır.
2. **Boot sinyalleri:** Dil sayısı, plugin sayısı, touchPoints, userAgent uzunluğu.
3. **rAF delta koleksiyonu:** `requestAnimationFrame` ile frame zamanlama deltaları toplanır (80 örnek).

WebDriver tespit edilirse → anında `hardFail("WEBDRIVER_AT_BOOT")`.

---

### Aşama 3 — Kaçış Oyunu (3 Escape)

"Buton" ekranda rastgele bir konumda belirir. Kullanıcının **3 kez butona yaklaşıp kaçırması** gerekir:

```
Cursor butona 130px'den yaklaşır
    → Buton kaçar (cursor'dan uzaklaşan rastgele açı ile 200-300px)
    → Escape event sunucuya POST edilir
    → Her escape için Proof-of-Work çözülür (SHA-256 prefix)
    → Sunucu rolling state'i günceller, yeni token döner
```

**3 kaçış tamamlanınca:**
- Buton yeşil olur, metni "İnsanım, Doğrula!" olarak değişir
- Kullanıcı butona tıklayabilir

---

### Aşama 4 — Son Doğrulama (pointer modu)

Butona tıklandığında JavaScript tüm verileri toplar ve sunucuya gönderir:

```
POST /robot/?action=verify
{
    mode: "pointer",
    token: <challenge nonce>,
    elapsedMs: <geçen süre>,
    eventToken: <son escape token'ı>,
    escapes: <escape sayısı>,
    mousePath: [{x, y, time}, ...],   // Fare hareket yolu
    browserProof: {                    // Tarayıcı parmak izi
        rafDeltas, domRects,
        canvasHash, fontHash,
        viewport, signature
    },
    click: { x, y, detail, isTrusted }
}
```

---

## Sunucu Tarafı Doğrulama Katmanları

`robot_handle_verify()` fonksiyonu 10+ güvenlik katmanını sırayla kontrol eder:

### 1. Nonce ve Challenge Bütünlüğü
- Token eşleşmeli, challenge tüketilmemiş olmalı
- Maksimum 6 deneme hakkı

### 2. WebDriver Tespiti (Boot)
- `bootSignals.webdriverAtBoot === true` → **RED**

### 3. Honeypot Tuzağı
- Gizli `#verification-honeypot-btn` butonuna tıklanmışsa (`mode: "trap"`) → **RED**

### 4. Escape Event Zinciri (3 kaçış)
- Her escape olayı için token doğrulaması
- Event sıra numarası kontrolü (`BAD_EVENT_INDEX`)
- Proof-of-Work geçerlilik (`BAD_ESCAPE_PROOF`)
- Delta zamanlama (300ms–300s aralığı, 180ms aralıklı)

### 5. Rolling State Zinciri
- Her escape olayında sunucu `rolling_state` hash'ini günceller
- Zincir zehirlenmişse (`rolling_poisoned`) → **RED**
- Eşleşme sayısı eksikse → **RED**
- Son durum hash'i beklenenle eşleşmeli

### 6. Proof Pixel (Tarayıcı Kaynak Testi)
- Sayfada gizli 1×1px GIF (`browser-proof-pixel`) bulunur
- `Sec-Fetch-Dest: image` başlığı ile yüklenmeli
- Yüklenmemişse → `BROWSER_RESOURCE_MISSING`

### 7. Zamanlama Kontrolü
- Sayfa yaşı 2–300 saniye aralığında olmalı
- Toplam geçen süre 1200ms–300s aralığında

### 8. Browser Proof (Canvas + Font + rAF)
- Canvas parmak izi: Canvas'a "Fezadan Robot Proof" metni + Bezier eğrisi çizilir, PNG dataURL'inin SHA-256 hash'i alınır
- Font parmak izi: "Fezadan-proof-0123456789" metninin işlenmiş boyutu ölçülür
- rAF deltaları: En az 18 örnek, min-max aralığı ≥ 0.35ms, standart sapma ≥ 0.08
- DOM rect: En az 3 örnek, buton boyutları tutarlı olmalı
- devicePixelRatio: 0–8 aralığında olmalı
- İmza: Tüm verilerin SHA-256 hash'i eşleşmeli

### 9. Pointer Trace (Fare Hareketi) Analizi
- En az 12 benzersiz 12px-grid noktası (`min_unique_points`)
- En az 900ms hareket süresi (`min_path_duration_ms`)
- Escape zinciri en az 1200ms (`min_escape_chain_ms`)

### 10. Mekanik Hareket Tespiti
- Zamanlama aralıklarının standart sapması < 1.2 ve aralık < 4.0ms → bot benzeri → **RED**

### 11. Doğrusal Hareket Tespiti
- `linearity > 0.965` (neredeyse düz çizgi) ve `angle_change < 0.85` → **RED**

### 12. Motion Score (Hareket Puanı)
- Hareket özellikleri (duration, unique, angle_change, speed_stddev) rastgele 3×3 matris ile projekte edilir
- Organik hareket cezası (linearity + speed stddev) düşülür
- Minimum 42.0 puan gerekli

### 13. Nihai Skorlama
```php
$score = 72
    + min(12, count($path))          // Yol noktası bonusu
    + min(8, $uniquePoints)          // Benzersiz nokta bonusu
    + min(8, floor($stats['stddev'])) // Jitter bonusu
    + min(8, floor($motionScore / 12)) // Hareket puanı bonusu
    + ($ipApiInfo['accepted'] ? 3 : 0); // IP API bonusu
// Maksimum 100
```

---

## Güvenlik Özellikleri

### CSRF ve Session Güvenliği
- Session cookie'leri: `HttpOnly`, `Secure` (HTTPS'te), `SameSite=Lax`
- Tüm istekler `same-origin` credentials ile gönderilir
- Challenge nonce'ı sadece 1 kez kullanılabilir (`consumed` flag)

### Proof-of-Work (PoW)
- Her escape olayında istemci SHA-256 prefix eşleştirmesi yapmak zorundadır
- `pow_difficulty = 3` → `000` ile başlayan hash bulunmalı
- Maksimum 250.000 iterasyon (yavaş cihazlar için üst sınır)
- Bulunamazsa → hata fırlatılır

### Rolling State Zinciri
- Her escape olayından sonra sunucu `rolling_state` hash'ini günceller
- İstemci bu güncellemeyi taklit edemez (hash zinciri sunucuda tutulur)
- Zincir zehirlenmesi (`rolling_poisoned`) anında tespit edilir

### Randomize Matematik Profili
- Her challenge için benzersiz 3×3 projeksiyon matrisi üretilir
- Her challenge için farklı bucket eşikleri (`min_delta_ms`, `max_distance`)
- Buton başlangıç pozisyonu rastgele (%20-80 aralığında)
- Bu sayede her oturumda farklı bir "doğru" davranış profili beklenir

### Headless Tarayıcı Koruması
- `navigator.webdriver` tespiti
- Canvas/Font parmak izi (headless tarayıcılar farklı render eder)
- rAF delta analizi (headless tarayıcılar sabit frame hızı verir)
- Proof pixel yükleme testi (bazı headless modlar kaynakları yüklemez)
- `Sec-Fetch-Dest` başlık kontrolü

---

## Honeypot (Bal Küpü) Tuzağı

HTML'de insan gözüyle görünmeyen bir buton bulunur:

```html
<button class="visually-hidden-trap"
    id="verification-honeypot-btn"
    style="position: absolute; left: -10000px; ...">
    Doğrula
</button>
```

- Botlar sayfadaki tüm butonları tarayıp tıklar → `mode: "trap"` gönderilir → **anında RED**
- Normal kullanıcı bu butonu asla görmez ve tıklayamaz

---

## Proof Pixel (Gizli Görsel)

```html
<div class="browser-proof-pixel"
    style="background-image: url('index.php?action=proof-pixel&nonce=...')">
</div>
```

- 1×1px GIF, sayfa dışında konumlandırılır
- Sunucu tarafında `Sec-Fetch-Dest: image` ve `Accept: image/*` başlıkları kontrol edilir
- Nonce eşleşmeli — aksi halde 404
- Yüklenme zamanı `microtime(true)` ile kaydedilir
- Doğrulama anında pixel'in yüklenip yüklenmediği kontrol edilir

---

## Başarı ve Token Sistemi

Doğrulama başarılı olduğunda:

```php
$_SESSION['robot_challenge']['verified'] = true;
$_SESSION['robot_challenge']['verified_until'] = time() + 900; // 15 dakika
$_SESSION['robot_challenge']['consumed'] = true;
$_SESSION['robot_pass_token'] = bin2hex(random_bytes(16)); // 32 karakter token
```

Dönen JSON:
```json
{
    "ok": true,
    "passToken": "a1b2c3d4e5f6...",
    "message": "Doğrulama başarılı"
}
```

**`robot_pass_token`** 15 dakika geçerlidir ve ana uygulama tarafından tüketilmek üzere session'da saklanır.

---

## Ana Uygulama ile Entegrasyon

Sistem artık **bağımsız bir subdomain** (`litecaptcha.fezadan.org`) olarak çalışır. Entegrasyon HMAC-signed redirect flow ile yapılır:

1. Kullanıcı korumalı sayfaya gelir (örn: `/yonetim` veya `/not/download/x`)
2. Captcha doğrulaması yoksa → `litecaptcha.fezadan.org/?redirect=...` adresine yönlendirilir
3. Kullanıcı kaçış oyununu tamamlar
4. LiteCaptcha başarılı olunca HMAC'li token ile ana siteye redirect yapar: `?rt=...&sig=...&exp=...`
5. Ana site `litecaptcha_verify()` (config.php) ile token'ı doğrular
6. Token replay önleme: `/tmp/litecaptcha_tokens/` dosya sistemi, 60sn TTL

### Entegre Edilen Noktalar

| Sayfa | Controller | Akış |
|-------|-----------|------|
| Admin girişi | `AdminController::index()` | Captcha → token → 15 dk session flag |
| PDF indirme | `NotlarController::download()` | Her indirmede captcha zorunlu |

### Ortam Değişkenleri

```ini
# Fezadan .env:
LITECAPTCHA_URL=https://litecaptcha.fezadan.org

# LiteCaptcha .env:
LITECAPTCHA_SECRET=APP_SECURITY_SALT ile aynı değer
LITECAPTCHA_ALLOWED_HOSTS=fezadan.org,notlar.fezadan.org
```

---

## Arayüz ve Tema

| Özellik | Detay |
|---------|-------|
| Font | Space Grotesk (UI), Syne (başlıklar), JetBrains Mono (teknik metinler) |
| Renk Paleti | `--bg-paper: #FEF9E1`, `--text-main: #6D2323`, `--text-accent: #A31D1D` |
| Dark Mode | `data-theme="dark"` → `--bg-paper: #120A0A`, `--text-main: #E1C89E` |
| Tasarım | Fezadan kart tasarımı (3px border, 10px gölge offset) |
| Grid Arka Plan | 40px grid, %4.5 opaklık |
| Responsive | Max 480px kart, %90 genişlik |

---

## Test Scriptleri

`scripts/` klasöründe robot sistemini test eden betikler bulunur (gitignored, sadece yerel geliştirme):

| Betik | Amaç |
|-------|------|
| `test-captcha.py` | Temel CAPTCHA bypass denemesi |
| `bypass-captcha.py` | Gelişmiş bypass girişimi |
| `advanced-bypass.py` | Tüm katmanlara karşı saldırı simülasyonu |
| `test-honeypot.py` | Honeypot tuzağı testi |
| `test-keyboard-bypass.py` | Klavye tabanlı bypass denemesi |
| `load-test-captcha.py` | Yük testi |

---

## Sınırlamalar ve Bilinen Durumlar

- **Mobil cihazlar:** Touch event'ler için optimize edilmemiştir; kaçış oyunu dokunmatik ekranda zorlayıcı olabilir.
- **Ekran okuyucular:** Erişilebilirlik (accessibility) desteği sınırlıdır. Görme engelli kullanıcılar için alternatif doğrulama yöntemi bulunmaz.
- **Yavaş cihazlar:** PoW hesaplaması (250K iterasyon) eski cihazlarda 1-2 saniye sürebilir.
- **JavaScript zorunlu:** Sistem tamamen JavaScript bağımlıdır; JS kapalıysa çalışmaz.
- **Session bağımlı:** Çerezler kapalıysa veya session süresi dolmuşsa challenge yeniden başlatılır.

---

## Hata Kodları Referansı

| Hata Kodu | Açıklama |
|-----------|----------|
| `BAD_JSON` | Geçersiz JSON payload |
| `BAD_TOKEN` | Challenge nonce eşleşmedi |
| `TOO_MANY_ATTEMPTS` | 6 deneme hakkı aşıldı |
| `CHALLENGE_CONSUMED` | Challenge zaten kullanılmış |
| `WEBDRIVER_AT_BOOT` | WebDriver/otomasyon tespit edildi |
| `HONEYPOT_CLICKED` | Gizli honeypot butonuna tıklandı |
| `BAD_EVENT_TOKEN` | Escape token'ı geçersiz |
| `BAD_EVENT_INDEX` | Escape sıra numarası hatalı |
| `BAD_ESCAPE_PROOF` | Proof-of-work geçersiz |
| `BAD_ESCAPE_TIMING` | Escape zamanlaması geçersiz |
| `BAD_ESCAPE_GEOMETRY` | Mouse/buton konumları geçersiz |
| `EARLY_CLICK` | Yeterli escape yapılmadan butona tıklandı |
| `BROWSER_RESOURCE_MISSING` | Proof pixel yüklenmedi |
| `ROLLING_STATE_POISONED` | Rolling state zinciri bozuldu |
| `ROLLING_STATE_INCOMPLETE` | Yetersiz rolling state eşleşmesi |
| `BAD_FINAL_EVENT_TOKEN` | Son event token'ı hatalı |
| `ESCAPE_CHAIN_TOO_FAST` | Escape zinciri çok hızlı tamamlandı |
| `BAD_BROWSER_PROOF` | Tarayıcı parmak izi doğrulanamadı |
| `POOR_POINTER_TRACE` | Yetersiz fare hareketi |
| `MECHANICAL_POINTER_TIMING` | Mekanik/bot benzeri zamanlama |
| `LINEAR_POINTER_TRACE` | Aşırı doğrusal fare hareketi |
| `LOW_MOTION_SCORE` | Hareket puanı eşiğin altında |
| `BAD_TIMING` | Sayfa yaşı veya geçen süre geçersiz |
