<?php 
$page_title = "MANIFESTO | FEZADAN";
require_once ROOT . '/app/Views/inc/header.php'; 
?>

<style>
    .font-body { font-family: 'EB Garamond', serif; }
    
    .texture-overlay {
        position: fixed; inset: 0; pointer-events: none; z-index: 0; opacity: 0.05;
        background-image: url("data:image/svg+xml,%3Csvg viewBox='0 0 200 200' xmlns='http://www.w3.org/2000/svg'%3E%3Cfilter id='noise'%3E%3CfeTurbulence type='fractalNoise' baseFrequency='0.9' numOctaves='3' stitchTiles='stitch'/%3E%3C/filter%3E%3Crect width='100%25' height='100%25' filter='url(%23noise)' opacity='1'/%3E%3C/svg%3E");
        mix-blend-mode: multiply;
    }

    .manifesto-grid {
        display: grid;
        grid-template-columns: 1fr;
        min-height: 100vh;
    }
    @media (min-width: 1024px) {
        .manifesto-grid { grid-template-columns: 35% 65%; }
    }
    
    .sidebar-sticky {
        position: sticky;
        top: 0;
        height: fit-content;
        padding: 4rem 2rem;
        border-right: 1px solid var(--line-color);
    }
    @media (max-width: 1023px) { 
        .sidebar-sticky { position: relative; border-right: none; border-bottom: 1px solid var(--line-color); } 
    }

    .manifesto-text {
        color: var(--text-main);
        line-height: 1.7;
        text-align: justify;
    }

    .quote-mark {
        font-family: 'Syne', sans-serif;
        font-size: 8rem;
        line-height: 0;
        opacity: 0.1;
        position: absolute;
        top: 2rem;
        left: 1rem;
    }
</style>

<div class="texture-overlay"></div>

<main class="relative z-10 w-full max-w-[1920px] mx-auto flex-grow">
    
    <div class="manifesto-grid">
        <aside class="sidebar-sticky bg-[var(--bg-secondary)]">
            <div class="relative">
                <span class="quote-mark">“</span>
                <h1 class="font-syne text-5xl md:text-7xl font-bold uppercase leading-[0.9] tracking-tighter mb-8">
                    Gerilla<br>Açık Erişim<br>Manifestosu
                </h1>
            </div>
            
            <div class="space-y-6 mt-12">
                <div class="p-4 border-l-2 border-[var(--text-accent)] bg-black/5 dark:bg-white/5">
                    <p class="font-mono text-xs uppercase tracking-widest text-[var(--text-accent)] mb-2">Bağlam</p>
                    <p class="text-sm opacity-80 leading-relaxed">
                        Bu metin, Aaron Swartz tarafından 2008 yılında yayınlanmış; 2017 yılında Zeki Çelikbaş tarafından Swartz'ın anısına Türkçeye kazandırılmıştır.
                    </p>
                </div>
                
                <div class="font-mono text-[10px] uppercase opacity-50 tracking-tighter">
                    <p>Yazar: Aaron Swartz (1986-2013)</p>
                    <p>Çeviri: Zeki Çelikbaş</p>
                </div>
            </div>
        </aside>

        <article class="p-6 md:p-24 max-w-4xl">
            <div class="font-body text-xl md:text-2xl space-y-12 manifesto-text">
                
                <h2 class="font-syne text-2xl font-bold uppercase mb-4 tracking-tight"> Bilgi güçtür. Fakat tüm güçler gibi onu kendileri için saklamak isteyenler var. </h2>


                <p>
                    Dergiler ve kitaplarda yüzyıllardan beri yayınlanan dünyanın tüm bilimsel ve kültürel mirası artan miktarda sayısallaştırılıyor ve özel şirketler tarafından işleniyor. Bilimde en son gelişmeleri içeren makalelerimi okumak istiyorsun, Reed Elsevier gibi yayıncılara çok büyük paralar göndermen gerekiyor.
                </p>

                <p>
                    Bunu değiştirmek için mücadele verenler var. Açık Erişim Hareketi bilim insanlarının telif haklarını tamamen vermemeleri için yiğitçe savaştı, sonuçta çalışmaların bir kopyasının internette herkesin erişimine açık olmasını sağladı. Fakat en iyi senaryo ile bile bu gelecekteki çalışmalar için geçerli olacaktır. Şimdiye kadar yapılanlar ise kaybolacaktır.
                </p>

                <div class="py-8 my-8 border-y border-[var(--line-color)]">
                    <h2 class="font-syne text-2xl font-bold uppercase mb-4 tracking-tight">Erişim Bir Ayrıcalık Değildir</h2>
                    <p>
                        Bu kaynaklara erişimi olanlar – öğrenciler, kütüphaneciler, bilim insanları – size bir ayrıcalık verilmiştir. Dünyanın geri kalanının erişemediği bu bilgi ziyafetinin bileti sizin elinizdedir. Fakat bu yetkinliği yalnızca kendiniz için tutamazsınız. Bunu dünya ile paylaşma göreviniz var. 
                    </p>
                </div>

                <p>
                    Şifrelerinizi meslektaşlarınız ile paylaşabilir, arkadaşlarınız için indirme yapabilirsiniz. Bu arada dışarıda elleri kilitli olanlar boş boş duruyor değiller. Sen yayıncılar tarafından kilitlenen bilgiyi özgürleştirmek ve arkadaşlarınla paylaşmak için elini taşın altına koymak için çekiniyorsun.
                </p>

                <p>
                    Buna hırsızlık veya korsanlık denilebilir, ahlaken bir geminin yağmalanması, mürettebatının öldürülmesi ile içindeki bilgi hazinesinin paylaşımı ile eşdeğer tutulabilir. <strong>Ama paylaşım ahlaksız değil – ahlaki bir zorunluluktur.</strong> Yalnızca açgözlülük ile gözleri kör edilenler bir arkadaşının kopya isteğini reddedebilir.
                </p>

                <p>
                    Haksız yasaların takip edildiği yerde adalet yoktur. Şimdi aydınlanma zamanı.
                </p>

                <p>
                    Nerede saklanıyorsa saklansın, bilgiyi almalıyız, onu dünya ile paylaşmak için kendi kopyamızı yapmalıyız. Telif hakkı dışında tutulan her şeyi almalı ve arşive eklemeliyiz. Bizim gizli veritabanlarını satın almak ve web'e koymamız gerekir. Bilimsel dergileri indirmeli ve makaleleri paylaşım sitelerine yüklemeliyiz. <strong>"Açık Erişim Gerillası"</strong> için savaşmamız gerekir.
                </p>

                <p class="text-3xl font-syne font-bold tracking-tighter uppercase pt-12">
                    Dünyada yeteri kadar olduğumuzda, bilgiyi özelleştirenlere karşı yalnızca güçlü bir mesaj göndermekle kalmayacağız – onu geçmişe gömeceğiz. 
                    <br><br>
                    <span class="text-[var(--text-accent)]">Bize katılacak mısın?</span>
                </p>

                <div class="pt-16 border-t border-[var(--line-color)] opacity-60 italic text-sm">
                    — Aaron Swartz
                </div>
            </div>
        </article>
    </div>

</main>

<?php require_once ROOT . '/app/Views/inc/footer.php'; ?>