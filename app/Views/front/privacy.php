<?php
$page_title = 'Gizlilik Politikası | FEZADAN';
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

<main class="relative z-10 w-full px-6 py-12 lg:p-16 2xl:p-20 max-w-4xl mx-auto flex-grow">
    
    <header class="mb-12 border-b border-[var(--text-main)]/30 pb-8">
        <h1 class="font-syne text-5xl md:text-6xl xl:text-7xl font-bold leading-[0.9] tracking-tighter uppercase text-[var(--text-main)] break-words">
            Gizlilik<br>Politikası
        </h1>
        <p class="mt-6 font-mono text-xs md:text-sm text-[var(--text-accent)] font-bold tracking-widest uppercase">
            Son Güncelleme: 23 Nisan 2026
        </p>
    </header>

    <div class="space-y-12 text-lg md:text-xl leading-relaxed opacity-90 text-[var(--text-main)]">
        
        <section class="space-y-4">
            <h2 class="font-syne text-2xl font-bold uppercase tracking-tight opacity-100">
                Temel İlkeler ve Açık Kaynak Şeffaflığı
            </h2>
            <p>
                Fezadan.org, mimari yapısı itibarıyla privacy by design ilkesi üzerine inşa edilmiştir. Geleneksel blog servislerinin aksine platformumuz, kullanıcı verilerinin toplanmaması ve dijital ayak izinin en aza indirilmesi esasına dayanır. Sitenin tüm kaynak kodları açık olup, gizlilik taahhütlerimiz teknik olarak doğrulanabilir ve şeffaf bir yapıdadır. Hedefimiz, bireyin kişisel veri mahremiyetini tam anlamıyla koruyan kolektif bilinçli bir okuma deneyimi sunmaktır.
            </p>
        </section>

        <section class="space-y-4">
            <h2 class="font-syne text-2xl font-bold uppercase tracking-tight opacity-100">
                Veri Toplama, Çerezler ve Üçüncü Taraf Hizmetleri
            </h2>
            <p>
                Platform üzerinde, kullanıcıların site içi davranışlarını izleyen, profilleyen veya takip eden hiçbir üçüncü taraf çerez (cookie) bulunmamaktadır. Ziyaretçilerin IP adresleri sunucu kayıtlarında tutulmaz, depolanmaz ve işlenmez; dolayısıyla kullanıcı kimliği sistem üzerinde tamamen anonim kalır. Site altyapısı, yalnızca siber güvenlik, DDoS saldırılarına karşı koruma ve güvenlik duvarı (firewall) hizmeti sağlayan Cloudflare ile sınırlı bir veri iletişimine sahiptir. Bunun dışında hiçbir üçüncü taraf servisle veri paylaşımı yapılmamaktadır.
            </p>
        </section>

        <section class="space-y-4">
            <h2 class="font-syne text-2xl font-bold uppercase tracking-tight opacity-100">
                Teknik Altyapı
            </h2>
            <p>
                Kullanıcı mahremiyetini korumak amacıyla, sitenin görsel ve teknik bileşenleri dış kaynaklardan bağımsız hale getirilmiştir. Yazı tipleri (fontlar) ve stil dosyaları (Tailwind CSS dahil) harici CDN (İçerik Dağıtım Ağı) servisleri yerine doğrudan kendi sunucumuz üzerinden (self-hosted) servis edilir. Bu yöntem, tarayıcınızın Google Fonts veya benzeri dış sağlayıcılara istek göndermesini engelleyerek dolaylı veri sızıntılarını önler.
            </p>
        </section>

        <section class="space-y-4">
            <h2 class="font-syne text-2xl font-bold uppercase tracking-tight opacity-100">
                Analitik
            </h2>
            <p>
                Site trafiğinin ölçümlenmesi, kullanıcıyı tanımlamayan ve IP adresi ile ilişkilendirilmeyen, sunucu tabanlı yerel sayaçlar (local counters) aracılığıyla gerçekleştirilir. Bu sistem, bireysel kullanıcı davranışlarını analiz etmez; yalnızca toplam görüntülenme sayısı gibi genel istatistiksel verileri barındırır ve kişisel veri içermez.
            </p>
        </section>

        <section class="space-y-4">
            <h2 class="font-syne text-2xl font-bold uppercase tracking-tight opacity-100">
                Reklam Politikası
            </h2>
            <p>
                Fezadan.org, mevcut yayın döneminde reklamsız bir yapıya sahiptir ve ilk altı aylık süreçte bu politikanın sürdürülmesi hedeflenmektedir. Gelecek dönemlerde, yalnızca sunucu ve barındırma maliyetlerinin karşılanması amacıyla reklam kabul edilmesi durumunda, bu içerikler "kişiselleştirilmiş reklam" teknolojilerinden arındırılmış olacaktır. Olası reklamlar, kullanıcı verisine veya tarayıcı geçmişine dayanmayan, site içeriğine doğrudan gömülü ve izleme kodu (tracker) içermeyen statik görsellerden oluşacaktır.
            </p>
        </section>

    </div>
</main>

<?php require_once ROOT . '/app/Views/inc/footer.php'; ?>