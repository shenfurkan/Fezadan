<?php 
$page_title = "FEZADAN | Hakkında";
require_once ROOT . '/app/Views/inc/header.php'; 
?>

<style>
    .grid-layout {
        display: grid;
        grid-template-columns: 1fr;
        min-height: 80vh;
        border-bottom: 1px solid var(--line-color);
    }
    @media (min-width: 1024px) {
        .grid-layout { grid-template-columns: 40% 60%; }
    }
    
    .sidebar {
        background-color: var(--bg-secondary);
        border-right: 1px solid var(--text-main);
        padding: 4rem 2rem;
        display: flex;
        flex-direction: column;
        justify-content: space-between;
    }
    @media (max-width: 1023px) { 
        .sidebar { border-right: none; border-bottom: 1px solid var(--text-main); } 
    }

    .content-area {
        padding: 4rem 2rem;
        max-width: 800px;
    }

    .animate-spin-slow { animation: spin 10s linear infinite; }
    @keyframes spin { 100% { transform: rotate(360deg); } }

    /* Gece modu için sadece ana ızgara çizgisi kalibrasyonu */
    [data-theme="dark"] .grid-layout {
        border-color: var(--text-main) !important;
    }
</style>

<main class="flex-grow w-full max-w-[1920px] mx-auto">
    
    <div class="grid-layout">
        <aside class="sidebar">
            <div>
                <h1 class="font-syne text-5xl md:text-7xl font-bold uppercase leading-[0.9] text-[var(--text-main)] mb-6">
                    İnsan<br>Raporu
                </h1>
                <div class="w-16 h-1 bg-[var(--text-main)] opacity-50 mb-6"></div>
            </div>
            
            <div class="mt-12 hidden lg:block">
                <svg width="100" height="100" viewBox="0 0 100 100" class="animate-spin-slow text-[var(--text-main)] opacity-70">
                <path d="M0,50 a50,50 0 1,1 0,1 z" fill="none" stroke="currentColor" stroke-width="2" stroke-dasharray="10 5"/>
                <circle cx="50" cy="50" r="10" fill="currentColor"/>
            </svg>
            </div>
        </aside>

        <article class="content-area">
            <h2 class="font-syne text-2xl font-bold mb-8 text-[var(--text-main)] uppercase">MİSYON TANIMI</h2>
            
            <div class="space-y-6 text-lg leading-relaxed opacity-90 text-[var(--text-main)]">
                <p>
                    fezadan.org, açık kaynak felsefesi ve bilginin özgürlüğü ilkeleri üzerine inşa edilmiş, gözetim kapitalizmine karşı duran özerk bir dijital direniş uzayıdır. Günümüzde internet, özel insan deneyimini tek taraflı olarak gasp edip kâr amaçlı davranışsal veriye dönüştüren, kullanıcıları satılık birer meta olarak gören gözetim kapitalizminin tahakkümü altındadır. Bizler, şirketlerin gözetim yoluyla kurduğu bu hiyerarşik panoptikon sistemini tümüyle reddediyoruz.
                </p>
                <p>
                    Felsefemizin merkezinde iki büyük dijital özgürlük mirası yatmaktadır: İnsanlığın tüm bilimsel ve kültürel mirasını özel mülkiyete hapseden açgözlü yapılara karşı Aaron Swartz'ın "Gerilla Açık Erişim Manifestosu"nda savunduğu üzere, bilgiyi paylaşmanın bir hırsızlık değil, ahlaki bir zorunluluk olduğu gerçeğidirİkincisi ise Cypherpunk hareketinin temel aldığı "güçlüler için şeffaflık, zayıflar için mahremiyet" ilkesidir Şifrepunk manifestosunun vurguladığı gibi, hükümetlerin veya dev, yüzsüz şirketlerin kendi lütuflarıyla bize mahremiyet ve özgürlük bahşetmesini bekleyemeyiz; kendi mahremiyetimizi kendi yazdığımız kodlarla, yazdığımız yazılarla, kurduğumuz açık sistemlerle bizzat kendimiz savunmalıyız.
                </p>
            </div>

            <div class="mt-16">
                <h3 class="font-syne text-xl font-bold mb-4 text-[var(--text-main)]">İLETİŞİM KANALLARI</h3>
                <div class="flex flex-col gap-2 text-sm font-mono text-[var(--text-main)]">
                    <a href="mailto:info@fezadan.org" class="hover:text-[var(--text-accent)] transition-colors">→ info@fezadan.org</a>
                    <a href="https://www.twitter.com/fezadanorg" class="hover:text-[var(--text-accent)] transition-colors">→ twitter.com/fezadanorg</a>
                    <a href="https://www.instagram.com/fezadanorg/" class="hover:text-[var(--text-accent)] transition-colors">→ instagram.com/fezadanorg</a>
                </div>
            </div>
        </article>
    </div>

</main>

<?php 
require_once ROOT . '/app/Views/inc/footer.php'; 
?>