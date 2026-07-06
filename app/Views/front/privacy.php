<?php
$siteBase = defined('SITE_URL') ? rtrim(SITE_URL, '/') : 'https://fezadan.org';
$lang = App::getLang();
$isEn = ($lang === 'EN');
$page_title       = $isEn ? 'Privacy Policy — Data Protection | FEZADAN' : 'Gizlilik Politikası — Veri Koruma | FEZADAN';
$page_description = $isEn
    ? 'FEZADAN privacy policy — information about data collection, cookies, and user rights. No tracking, no third-party analytics.'
    : 'FEZADAN gizlilik politikası — veri toplama, çerez ve kullanıcı hakları hakkında bilgi. Takip yok, üçüncü parti analiz yok.';
$page_canonical   = langUrl('/privacy');
$og_url           = $page_canonical;
require_once ROOT . '/app/Views/inc/header.php';
?>

<style>
    .texture-overlay {
        position: fixed; inset: 0; pointer-events: none; z-index: 0; opacity: 0.06;
        background-image: url("data:image/svg+xml,%3Csvg viewBox='0 0 200 200' xmlns='http://www.w3.org/2000/svg'%3E%3Cfilter id='noise'%3E%3CfeTurbulence type='fractalNoise' baseFrequency='0.9' numOctaves='3' stitchTiles='stitch'/%3E%3C/filter%3E%3Crect width='100%25' height='100%25' filter='url(%23noise)' opacity='1'/%3E%3C/svg%3E");
        mix-blend-mode: multiply;
    }
</style>

<div class="texture-overlay"></div>

<main id="main-content" class="relative z-10 w-full px-6 py-12 lg:p-16 2xl:p-20 max-w-4xl mx-auto flex-grow">
    
    <header class="mb-12 border-b border-[var(--text-main)]/30 pb-8">
        <h1 class="font-syne text-5xl md:text-6xl xl:text-7xl font-bold leading-[0.9] tracking-tighter uppercase text-[var(--text-main)] break-words">
            <?php if ($isEn): ?>Privacy<br>Policy<?php else: ?>Gizlilik<br>Politikası<?php endif; ?>
        </h1>
        <p class="mt-6 font-mono text-xs md:text-sm text-[var(--text-accent)] font-bold tracking-widest uppercase">
            <?php if ($isEn): ?>Last Updated: 23 April 2026<?php else: ?>Son Güncelleme: 23 Nisan 2026<?php endif; ?>
        </p>
    </header>

    <div class="space-y-12 text-lg md:text-xl leading-relaxed opacity-90 text-[var(--text-main)]">
        
        <section class="space-y-4">
            <h2 class="font-syne text-2xl font-bold uppercase tracking-tight opacity-100">
                <?php if ($isEn): ?>Core Principles and Open Source Transparency<?php else: ?>Temel İlkeler ve Açık Kaynak Şeffaflığı<?php endif; ?>
            </h2>
            <p>
                <?php if ($isEn): ?>
                Fezadan.org is built on a privacy-by-design architecture. Unlike conventional blogging platforms, ours is founded on the principle of not collecting user data and minimizing digital footprint. The site's entire source code is open, and our privacy commitments are technically verifiable and transparent. Our goal is to provide a collectively conscious reading experience that fully protects individual data privacy.
                <?php else: ?>
                Fezadan.org, mimari yapısı itibarıyla privacy by design ilkesi üzerine inşa edilmiştir. Geleneksel blog servislerinin aksine platformumuz, kullanıcı verilerinin toplanmaması ve dijital ayak izinin en aza indirilmesi esasına dayanır. Sitenin tüm kaynak kodları açık olup, gizlilik taahhütlerimiz teknik olarak doğrulanabilir ve şeffaf bir yapıdadır. Hedefimiz, bireyin kişisel veri mahremiyetini tam anlamıyla koruyan kolektif bilinçli bir okuma deneyimi sunmaktır.
                <?php endif; ?>
            </p>
        </section>

        <section class="space-y-4">
            <h2 class="font-syne text-2xl font-bold uppercase tracking-tight opacity-100">
                <?php if ($isEn): ?>Data Collection, Cookies and Third-Party Services<?php else: ?>Veri Toplama, Çerezler ve Üçüncü Taraf Hizmetleri<?php endif; ?>
            </h2>
            <p>
                <?php if ($isEn): ?>
                There are no third-party cookies on the platform that track, profile, or monitor visitors' on-site behavior. Visitor IP addresses are not stored, retained, or processed in server logs; therefore, user identity remains completely anonymous on the system. The site infrastructure only communicates with Cloudflare for cybersecurity, DDoS protection, and firewall services. Beyond this, no data is shared with any third-party service.
                <?php else: ?>
                Platform üzerinde, kullanıcıların site içi davranışlarını izleyen, profilleyen veya takip eden hiçbir üçüncü taraf çerez (cookie) bulunmamaktadır. Ziyaretçilerin IP adresleri sunucu kayıtlarında tutulmaz, depolanmaz ve işlenmez; dolayısıyla kullanıcı kimliği sistem üzerinde tamamen anonim kalır. Site altyapısı, yalnızca siber güvenlik, DDoS saldırılarına karşı koruma ve güvenlik duvarı (firewall) hizmeti sağlayan Cloudflare ile sınırlı bir veri iletişimine sahiptir. Bunun dışında hiçbir üçüncü taraf servisle veri paylaşımı yapılmamaktadır.
                <?php endif; ?>
            </p>
        </section>

        <section class="space-y-4">
            <h2 class="font-syne text-2xl font-bold uppercase tracking-tight opacity-100">
                <?php if ($isEn): ?>Technical Infrastructure<?php else: ?>Teknik Altyapı<?php endif; ?>
            </h2>
            <p>
                <?php if ($isEn): ?>
                To protect user privacy, the site's visual and technical components are independent of external sources. Fonts and stylesheets (including Tailwind CSS) are served directly from our own server (self-hosted) rather than through third-party CDN services. This prevents your browser from sending requests to Google Fonts or similar external providers, eliminating indirect data leakage.
                <?php else: ?>
                Kullanıcı mahremiyetini korumak amacıyla, sitenin görsel ve teknik bileşenleri dış kaynaklardan bağımsız hale getirilmiştir. Yazı tipleri (fontlar) ve stil dosyaları (Tailwind CSS dahil) harici CDN (İçerik Dağıtım Ağı) servisleri yerine doğrudan kendi sunucumuz üzerinden (self-hosted) servis edilir. Bu yöntem, tarayıcınızın Google Fonts veya benzeri dış sağlayıcılara istek göndermesini engelleyerek dolaylı veri sızıntılarını önler.
                <?php endif; ?>
            </p>
        </section>

        <section class="space-y-4">
            <h2 class="font-syne text-2xl font-bold uppercase tracking-tight opacity-100">
                <?php if ($isEn): ?>Analytics<?php else: ?>Analitik<?php endif; ?>
            </h2>
            <p>
                <?php if ($isEn): ?>
                Site traffic measurement is performed via server-side local counters that do not identify the user and are not linked to IP addresses. This system does not analyze individual user behavior; it only provides aggregate statistical data such as total page views and contains no personal data.
                <?php else: ?>
                Site trafiğinin ölçümlenmesi, kullanıcıyı tanımlamayan ve IP adresi ile ilişkilendirilmeyen, sunucu tabanlı yerel sayaçlar (local counters) aracılığıyla gerçekleştirilir. Bu sistem, bireysel kullanıcı davranışlarını analiz etmez; yalnızca toplam görüntülenme sayısı gibi genel istatistiksel verileri barındırır ve kişisel veri içermez.
                <?php endif; ?>
            </p>
        </section>

        <section class="space-y-4">
            <h2 class="font-syne text-2xl font-bold uppercase tracking-tight opacity-100">
                <?php if ($isEn): ?>Advertising Policy<?php else: ?>Reklam Politikası<?php endif; ?>
            </h2>
            <p>
                <?php if ($isEn): ?>
                Fezadan.org is ad-free during its current publication period, with this policy intended to continue for the first six months. If advertising is accepted in future periods solely to cover server and hosting costs, such content will be free of "personalized advertising" technologies. Any potential ads will consist of static, non-tracking images embedded directly into site content, with no reliance on user data or browser history.
                <?php else: ?>
                Fezadan.org, mevcut yayın döneminde reklamsız bir yapıya sahiptir ve ilk altı aylık süreçte bu politikanın sürdürülmesi hedeflenmektedir. Gelecek dönemlerde, yalnızca sunucu ve barındırma maliyetlerinin karşılanması amacıyla reklam kabul edilmesi durumunda, bu içerikler "kişiselleştirilmiş reklam" teknolojilerinden arındırılmış olacaktır. Olası reklamlar, kullanıcı verisine veya tarayıcı geçmişine dayanmayan, site içeriğine doğrudan gömülü ve izleme kodu (tracker) içermeyen statik görsellerden oluşacaktır.
                <?php endif; ?>
            </p>
        </section>

    </div>
</main>

<?php require_once ROOT . '/app/Views/inc/footer.php'; ?>