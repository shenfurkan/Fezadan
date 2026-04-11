<?php
$page_title = 'Gizlilik Politikası | FEZADAN';
require_once ROOT . '/app/Views/inc/header.php';
?>

<style>
    .font-body { font-family: 'EB Garamond', serif; }
    
    .texture-overlay {
        position: fixed; inset: 0; pointer-events: none; z-index: 0; opacity: 0.06;
        background-image: url("data:image/svg+xml,%3Csvg viewBox='0 0 200 200' xmlns='http://www.w3.org/2000/svg'%3E%3Cfilter id='noise'%3E%3CfeTurbulence type='fractalNoise' baseFrequency='0.9' numOctaves='3' stitchTiles='stitch'/%3E%3C/filter%3E%3Crect width='100%25' height='100%25' filter='url(%23noise)' opacity='1'/%3E%3C/svg%3E");
        mix-blend-mode: multiply;
    }

    /* Fezadan'a özgü siyah tonu ve okuma kolaylığı */
    .policy-text {
        color: #1a1a1a;
        line-height: 1.8;
        text-align: justify;
    }
    .policy-heading {
        color: #1a1a1a;
    }

    /* Gece modu için uyumluluk */
    [data-theme="dark"] .policy-text,
    [data-theme="dark"] .policy-heading {
        color: var(--text-main);
    }

    /* Karanlık temada başlık alt çizgisini krem rengine dönüştürür */
    [data-theme="dark"] .policy-header-border {
        border-bottom-color: var(--text-main) !important;
    }
</style>

<div class="texture-overlay"></div>

<main class="relative z-10 w-full px-6 py-12 md:py-24 min-w-0 max-w-4xl mx-auto flex-grow">
    
    <header class="mb-16 border-b border-[var(--line-color)] policy-header-border pb-10">
        <h1 class="font-syne text-4xl md:text-6xl font-bold leading-[0.9] tracking-tight policy-heading uppercase">
            Gizlilik Politikası
        </h1>
        <p class="mt-6 indent-1 md:indent-2 font-mono text-xs md:text-sm text-[var(--text-accent)] font-bold tracking-widest uppercase">
            Son Güncelleme: <?php echo date('d F Y'); ?>
        </p>
    </header>

    <div class="font-body text-lg md:text-xl space-y-16">
        
        <section>
            <div class="flex flex-col md:flex-row md:items-baseline gap-2 md:gap-4 mb-6">
                <h2 class="font-syne text-xl md:text-2xl font-bold uppercase tracking-wide policy-heading">Temel İlkeler ve Açık Kaynak Şeffaflığı</h2>
            </div>
            <p class="policy-text">
                Fezadan.org, mimari yapısı itibarıyla privacy by design ilkesi üzerine inşa edilmiştir. Geleneksel blog servislerinin aksine platformumuz, kullanıcı verilerinin toplanmaması ve dijital ayak izinin en aza indirilmesi esasına dayanır. Sitenin tüm kaynak kodları açık olup, gizlilik taahhütlerimiz teknik olarak doğrulanabilir ve şeffaf bir yapıdadır. Hedefimiz, bireyin kişisel veri mahremiyetini tam anlamıyla koruyan kolektif bilinçli bir okuma deneyimi sunmaktır.
            </p>
        </section>

        <section>
            <div class="flex flex-col md:flex-row md:items-baseline gap-2 md:gap-4 mb-6">
                <h2 class="font-syne text-xl md:text-2xl font-bold uppercase tracking-wide policy-heading">Veri Toplama, Çerezler ve Üçüncü Taraf Hizmetleri</h2>
            </div>
            <p class="policy-text">
                Platform üzerinde, kullanıcıların site içi davranışlarını izleyen, profilleyen veya takip eden hiçbir üçüncü taraf çerez (cookie) bulunmamaktadır. Ziyaretçilerin IP adresleri sunucu kayıtlarında tutulmaz, depolanmaz ve işlenmez; dolayısıyla kullanıcı kimliği sistem üzerinde tamamen anonim kalır. Site altyapısı, yalnızca siber güvenlik, DDoS saldırılarına karşı koruma ve güvenlik duvarı (firewall) hizmeti sağlayan Cloudflare ile sınırlı bir veri iletişimine sahiptir. Bunun dışında hiçbir üçüncü taraf servisle veri paylaşımı yapılmamaktadır.
            </p>
        </section>

        <section>
            <div class="flex flex-col md:flex-row md:items-baseline gap-2 md:gap-4 mb-6">
                <h2 class="font-syne text-xl md:text-2xl font-bold uppercase tracking-wide policy-heading">Teknik Altyapı</h2>
            </div>
            <p class="policy-text">
                Kullanıcı mahremiyetini korumak amacıyla, sitenin görsel ve teknik bileşenleri dış kaynaklardan bağımsız hale getirilmiştir. Yazı tipleri (fontlar) ve stil dosyaları (Tailwind CSS dahil) harici CDN (İçerik Dağıtım Ağı) servisleri yerine doğrudan kendi sunucumuz üzerinden (self-hosted) servis edilir. Bu yöntem, tarayıcınızın Google Fonts veya benzeri dış sağlayıcılara istek göndermesini engelleyerek dolaylı veri sızıntılarını önler.
            </p>
        </section>

        <section>
            <div class="flex flex-col md:flex-row md:items-baseline gap-2 md:gap-4 mb-6">
                <h2 class="font-syne text-xl md:text-2xl font-bold uppercase tracking-wide policy-heading">Analitik</h2>
            </div>
            <p class="policy-text">
                Site trafiğinin ölçümlenmesi, kullanıcıyı tanımlamayan ve IP adresi ile ilişkilendirilmeyen, sunucu tabanlı yerel sayaçlar (local counters) aracılığıyla gerçekleştirilir. Bu sistem, bireysel kullanıcı davranışlarını analiz etmez; yalnızca toplam görüntülenme sayısı gibi genel istatistiksel verileri barındırır ve kişisel veri içermez.
            </p>
        </section>

        <section>
            <div class="flex flex-col md:flex-row md:items-baseline gap-2 md:gap-4 mb-6">
                <h2 class="font-syne text-xl md:text-2xl font-bold uppercase tracking-wide policy-heading">Reklam Politikası</h2>
            </div>
            <p class="policy-text">
                Fezadan.org, mevcut yayın döneminde reklamsız bir yapıya sahiptir ve ilk altı aylık süreçte bu politikanın sürdürülmesi hedeflenmektedir. Gelecek dönemlerde, yalnızca sunucu ve barındırma maliyetlerinin karşılanması amacıyla reklam kabul edilmesi durumunda, bu içerikler "kişiselleştirilmiş reklam" teknolojilerinden arındırılmış olacaktır. Olası reklamlar, kullanıcı verisine veya tarayıcı geçmişine dayanmayan, site içeriğine doğrudan gömülü ve izleme kodu (tracker) içermeyen statik görsellerden oluşacaktır.
            </p>
        </section>

    </div>
</main>

<?php require_once ROOT . '/app/Views/inc/footer.php'; ?>