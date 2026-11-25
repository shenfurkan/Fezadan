<?php
$siteBase = defined('SITE_URL') ? rtrim(SITE_URL, '/') : 'https://fezadan.org';
$lang = App::getLang();
$isEn = ($lang === 'EN');
$page_title       = $isEn ? 'Privacy Policy | Data Protection | FEZADAN' : 'Gizlilik Politikası | Veri Koruma | FEZADAN';
$page_description = $isEn
    ? 'How FEZADAN uses cookieless Cloudflare Web Analytics, essential cookies and technical service providers.'
    : 'FEZADAN’da çerezsiz Cloudflare Web Analytics, gerekli çerezler ve teknik hizmet sağlayıcıları hakkında bilgi.';
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
            <?php if ($isEn): ?>Last Updated: 30 October 2026<?php else: ?>Son Güncelleme: 30 Ekim 2026<?php endif; ?>
        </p>
    </header>

    <div class="space-y-12 text-lg md:text-xl leading-relaxed opacity-90 text-[var(--text-main)]">

        <section class="space-y-4">
            <h2 class="font-syne text-2xl font-bold uppercase tracking-tight opacity-100">
                <?php if ($isEn): ?>Core Principles and Open Source Transparency<?php else: ?>Temel İlkeler ve Açık Kaynak Şeffaflığı<?php endif; ?>
            </h2>
            <p>
                <?php if ($isEn): ?>
                FEZADAN aims to collect only the data needed to publish content, keep the site secure and understand its overall use. We do not use advertising profiles or cross-site tracking. Our source code is publicly available so that the practices described here can be inspected.
                <?php else: ?>
                FEZADAN, içerik sunmak, siteyi güvenli tutmak ve genel kullanımını anlamak için gereken verileri sınırlı tutmayı amaçlar. Reklam profilleri veya siteler arası takip kullanmayız. Burada açıklanan uygulamaların incelenebilmesi için kaynak kodlarımız herkese açıktır.
                <?php endif; ?>
            </p>
        </section>

        <section class="space-y-4">
            <h2 class="font-syne text-2xl font-bold uppercase tracking-tight opacity-100">
                <?php if ($isEn): ?>Data Collection, Cookies and Third-Party Services<?php else: ?>Veri Toplama, Çerezler ve Üçüncü Taraf Hizmetleri<?php endif; ?>
            </h2>
            <p>
                <?php if ($isEn): ?>
                The fezadan_lang cookie remembers your language choice for 30 days. Session cookies are used for admin sign-in and mail-invitation forms; browser storage also remembers preferences such as theme, reading size and the installation prompt. These are separate from analytics. Cloudflare Web Analytics does not use cookies, localStorage or persistent visitor identifiers. Hosting and Cloudflare security services necessarily process connection information, including IP addresses; access, error and security logs may contain technical request data. We therefore do not promise that an IP address is never processed or logged.
                <?php else: ?>
                fezadan_lang çerezi dil tercihinizi 30 gün hatırlar. Yönetim paneli girişi ve e-posta davet formları için oturum çerezleri kullanılır; tema, okuma boyutu ve kurulum hatırlatması gibi tercihler de tarayıcı depolamasında tutulur. Bunlar analitikten ayrıdır. Cloudflare Web Analytics çerez, localStorage veya kalıcı ziyaretçi kimliği kullanmaz. Barındırma ve Cloudflare güvenlik hizmetleri, IP adresi dahil bağlantı bilgilerini zorunlu olarak işler; erişim, hata ve güvenlik kayıtları teknik istek verileri içerebilir. Bu nedenle IP adreslerinin hiçbir zaman işlenmediği veya kaydedilmediği yönünde bir taahhütte bulunmayız.
                <?php endif; ?>
            </p>
        </section>

        <section class="space-y-4">
            <h2 class="font-syne text-2xl font-bold uppercase tracking-tight opacity-100">
                <?php if ($isEn): ?>Technical Infrastructure<?php else: ?>Teknik Altyapı<?php endif; ?>
            </h2>
            <p>
                <?php if ($isEn): ?>
                Fonts, stylesheets and frontend libraries are served from our own infrastructure, not Google Fonts or a public library CDN. Cloudflare provides traffic protection, content delivery and R2 file storage. The Web Analytics beacon is an explicit exception to local script hosting: it loads from static.cloudflareinsights.com and sends measurements to cloudflareinsights.com. PDF downloads and mail invitations may also use our LiteCaptcha verification service. Some article images are hosted by external sources; loading them sends connection information to that source. External services, including social media links you open, have their own privacy policies.
                <?php else: ?>
                Yazı tipleri, stil dosyaları ve arayüz kütüphaneleri Google Fonts veya herkese açık bir kütüphane CDN’i yerine kendi altyapımızdan sunulur. Cloudflare; trafik koruması, içerik dağıtımı ve R2 dosya depolama hizmetleri sağlar. Web Analytics betiği, yerel betik sunumunun açık bir istisnasıdır: static.cloudflareinsights.com adresinden yüklenir ve ölçümleri cloudflareinsights.com adresine gönderir. PDF indirmelerinde ve e-posta davetlerinde ayrıca kendi LiteCaptcha doğrulama hizmetimiz kullanılabilir. Bazı makale görselleri dış kaynaklardan yüklenir; yükleme sırasında o kaynağa bağlantı bilgileri iletilir. Açtığınız sosyal medya bağlantıları dahil dış hizmetlerin kendi gizlilik politikaları geçerlidir.
                <?php endif; ?>
            </p>
        </section>

        <section class="space-y-4">
            <h2 class="font-syne text-2xl font-bold uppercase tracking-tight opacity-100">
                <?php if ($isEn): ?>Analytics<?php else: ?>Analitik<?php endif; ?>
            </h2>
            <p>
                <?php if ($isEn): ?>
                We use Cloudflare Web Analytics to understand page views, visits, referring sites, approximate country, browser/device categories and page performance, including Core Web Vitals. The beacon sends page and referrer information and performance measurements to Cloudflare. Cloudflare states that it does not log URL query strings, use cookies or fingerprint visitors for this service, and that incoming IP addresses are discarded at the nearest data center rather than stored in the RUM databases or logs. This does not mean that no network data is processed. Analytics can be viewed for the previous six months; older data may be sampled.
                <?php else: ?>
                Sayfa görüntülenmelerini, ziyaretleri, yönlendiren siteleri, yaklaşık ülkeyi, tarayıcı/cihaz gruplarını ve Core Web Vitals dahil sayfa performansını anlamak için Cloudflare Web Analytics kullanırız. Betik, sayfa ve yönlendiren sayfa bilgileri ile performans ölçümlerini Cloudflare’e gönderir. Cloudflare bu hizmette URL sorgu parametrelerini kaydetmediğini, çerez veya ziyaretçi parmak izi kullanmadığını ve gelen IP adreslerini RUM veritabanlarına ya da kayıtlarına yazmak yerine en yakın veri merkezinde attığını belirtir. Bu, ağ verilerinin hiç işlenmediği anlamına gelmez. Analitik raporları geçmiş altı ay için görüntülenebilir; daha eski veriler örneklenebilir.
                <?php endif; ?>
            </p>
            <p>
                <?php if ($isEn): ?>
                The beacon is limited to public pages on fezadan.org and www.fezadan.org. It is not loaded on admin, mail-invitation, anonymity-check or notes/PDF pages, other subdomains or local development. We also omit it when the request contains Do Not Track (DNT: 1) or Global Privacy Control (Sec-GPC: 1). Blocking the beacon does not prevent reading the site. Cloudflare may process measurements internationally; this setup does not promise Turkey-only or EU-only storage. See <a class="underline" href="https://developers.cloudflare.com/speed/observatory/rum-beacon/" rel="noopener noreferrer">Cloudflare’s RUM privacy documentation</a> and <a class="underline" href="https://www.cloudflare.com/privacypolicy/" rel="noopener noreferrer">privacy policy</a>.
                <?php else: ?>
                Betik yalnızca fezadan.org ve www.fezadan.org üzerindeki herkese açık sayfalarda yüklenir. Yönetim paneli, e-posta daveti, anonimlik testi, not/PDF sayfaları, diğer alt alan adları ve yerel geliştirme ortamı ölçüm dışındadır. İstekte Do Not Track (DNT: 1) veya Global Privacy Control (Sec-GPC: 1) sinyali varsa betiği yüklemeyiz. Betiği engellemeniz siteyi okumanızı engellemez. Cloudflare ölçümleri farklı ülkelerde işleyebilir; bu kurulum yalnızca Türkiye’de veya AB’de saklama taahhüdü vermez. Ayrıntılar için <a class="underline" href="https://developers.cloudflare.com/speed/observatory/rum-beacon/" rel="noopener noreferrer">Cloudflare RUM gizlilik açıklamasını</a> ve <a class="underline" href="https://www.cloudflare.com/privacypolicy/" rel="noopener noreferrer">gizlilik politikasını</a> inceleyebilirsiniz.
                <?php endif; ?>
            </p>
            <p>
                <?php if ($isEn): ?>
                Local readership and download totals, reading-time tracking and scroll-depth reporting have been retired. We do not send these measurements to our server or to Cloudflare. Historical local records have not been deleted by this change and are no longer updated or displayed. Separate abuse-prevention controls, such as PDF download limits, remain in place; they use a daily salted IP hash rather than a raw IP in the rate-limit table.
                <?php else: ?>
                Yerel okunma ve indirme toplamları, okuma süresi takibi ve kaydırma derinliği raporlaması kaldırılmıştır. Bu ölçümleri kendi sunucumuza veya Cloudflare’e göndermeyiz. Bu değişiklik geçmiş yerel kayıtları silmez; kayıtlar artık güncellenmez veya gösterilmez. PDF indirme limiti gibi kötüye kullanım önlemleri ayrı olarak sürer; indirme limiti tablosunda ham IP yerine günlük tuzlanmış IP özeti kullanılır.
                <?php endif; ?>
            </p>
        </section>

        <section class="space-y-4">
            <h2 class="font-syne text-2xl font-bold uppercase tracking-tight opacity-100">
                <?php if ($isEn): ?>Your Choices and Contact<?php else: ?>Tercihleriniz ve İletişim<?php endif; ?>
            </h2>
            <p>
                <?php if ($isEn): ?>
                You can delete site cookies and local preferences in your browser settings and block analytics requests. The DNT/GPC exclusion above applies to the Cloudflare beacon, not essential security processing. For questions about data processing or requests concerning your personal data, contact <a class="underline" href="mailto:info@fezadan.org">info@fezadan.org</a>. Information you send by email or through an account/invitation form is processed to respond to your request or provide the requested service, not for analytics or advertising.
                <?php else: ?>
                Tarayıcı ayarlarından site çerezlerini ve yerel tercihleri silebilir, analitik isteklerini engelleyebilirsiniz. Yukarıdaki DNT/GPC istisnası Cloudflare betiği içindir; gerekli güvenlik işlemlerini kapsamaz. Veri işleme hakkındaki sorularınız ve kişisel verilerinize ilişkin talepleriniz için <a class="underline" href="mailto:info@fezadan.org">info@fezadan.org</a> adresine yazabilirsiniz. E-posta veya hesap/davet formuyla gönderdiğiniz bilgiler, talebinizi yanıtlamak ya da istediğiniz hizmeti sunmak için işlenir; analitik veya reklam amacıyla kullanılmaz.
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
