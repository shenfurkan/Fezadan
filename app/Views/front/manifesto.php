<?php
$siteBase = defined('SITE_URL') ? rtrim(SITE_URL, '/') : 'https://fezadan.org';

$lang = App::getLang();
$isEn = ($lang === 'EN');

$page_title       = $isEn ? 'Guerilla Open Access Manifesto — Freedom of Knowledge | FEZADAN' : 'Açık Erişim Manifestosu — Bilginin Özgürlüğü | FEZADAN';
$page_description = $isEn
    ? 'FEZADAN Manifesto — a declaration on the silent conflict between data and aesthetics. Our principles for independent publishing.'
    : 'FEZADAN manifestosu — veri ve estetik arasındaki sessiz çatışma üzerine bir bildirge. Bağımsız yayıncılık ilkelerimiz.';
$page_canonical   = langUrl('/manifesto');
$og_url           = $page_canonical;
require_once ROOT . '/app/Views/inc/header.php';
?>

<style>
    .texture-overlay {
        position: fixed; inset: 0; pointer-events: none; z-index: 0; opacity: 0.05;
        background-image: url("data:image/svg+xml,%3Csvg viewBox='0 0 200 200' xmlns='http://www.w3.org/2000/svg'%3E%3Cfilter id='noise'%3E%3CfeTurbulence type='fractalNoise' baseFrequency='0.9' numOctaves='3' stitchTiles='stitch'/%3E%3C/filter%3E%3Crect width='100%25' height='100%25' filter='url(%23noise)' opacity='1'/%3E%3C/svg%3E");
        mix-blend-mode: multiply;
    }

    .grid-layout {
        display: grid;
        grid-template-columns: 1fr;
        min-height: 80vh;
        border-bottom: 1px solid var(--line-color);
    }
    @media (min-width: 1024px) {
        .grid-layout { grid-template-columns: 40% 60%; } 
    }
    
    [data-theme="dark"] .grid-layout {
        border-color: var(--text-main) !important;
    }

    .sidebar {
        position: relative;
        overflow: hidden;
        background-color: var(--bg-secondary);
        border-right: 1px solid var(--text-main);
    }
    @media (max-width: 1023px) { 
        .sidebar { 
            border-right: none; 
            border-bottom: 1px solid var(--text-main); 
        } 
    }

    .quote-mark {
        font-family: 'Syne', sans-serif;
        font-size: 8rem;
        line-height: 0;
        opacity: 0.1;
        position: absolute;
        top: 2rem;
        left: -1rem; 
    }
</style>

<div class="texture-overlay"></div>

<main id="main-content" class="relative z-10 w-full max-w-[1920px] mx-auto flex-grow">
    
    <div class="grid-layout">
        
        <aside class="sidebar">
            <canvas id="manifesto-canvas" class="absolute inset-0 w-full h-full pointer-events-none z-0 opacity-25"></canvas>
            <div class="sticky top-12 px-6 py-12 lg:p-16 2xl:p-20 relative z-10" style="padding-left: 20px;">
                <div class="relative">
                    <span class="quote-mark">“</span>
                    
                    <h1 class="font-syne text-5xl md:text-6xl lg:text-4xl xl:text-5xl 2xl:text-7xl font-bold uppercase leading-[0.9] tracking-tighter text-[var(--text-main)] mb-6 break-words">
                        <?= $isEn ? 'Guerilla<br>Open Access<br>Mani&shy;festo' : 'Gerilla<br>Açık Erişim<br>Mani&shy;festo&shy;su' ?>
                    </h1>
                </div>
                
                <div class="space-y-6 mt-8 xl:mt-12">
                    <div class="p-4 border-l-2 border-[var(--text-main)] bg-black/5 dark:bg-white/5">
                        <p class="font-mono text-xs uppercase tracking-widest text-[var(--text-main)] mb-2"><?= $isEn ? 'Context' : 'Bağlam' ?></p>
                        <p class="text-sm opacity-80 leading-relaxed text-[var(--text-main)]">
                            <?= $isEn 
                                ? 'This text was published by Aaron Swartz in July 2008 in Eremo, Italy, as a call to action for digital freedom.'
                                : 'Bu metin, Aaron Swartz tarafından 2008 yılında yayınlanmış; 2017 yılında Zeki Çelikbaş tarafından Swartz\'ın anısına Türkçeye kazandırılmıştır.' ?>
                        </p>
                    </div>
                    
                    <div class="font-mono text-[10px] uppercase opacity-50 tracking-tighter text-[var(--text-main)]">
                        <p><?= $isEn ? 'Author' : 'Yazar' ?>: Aaron Swartz (1986-2013)</p>
                        <?php if (!$isEn): ?>
                        <p>Çeviri: Zeki Çelikbaş</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </aside>

        <article class="px-6 py-12 lg:p-16 2xl:p-20 max-w-4xl">
            <div class="space-y-6 text-lg md:text-xl leading-relaxed opacity-90 text-[var(--text-main)]">
                <?php if ($isEn): ?>
                
                <h2 class="font-syne text-2xl font-bold uppercase mb-4 tracking-tight opacity-100">
                    Information is power. But like all power, there are those who want to keep it for themselves.
                </h2>

                <p>
                    The world’s entire scientific and cultural heritage, published over centuries in books and journals, is increasingly being digitized and locked up by a handful of private corporations. Want to read the papers featuring the most famous results of the sciences? You’ll need to send enormous amounts to publishers like Reed Elsevier.
                </p>

                <p>
                    There are those struggling to change this. The Open Access Movement has fought valiantly to ensure that scientists do not sign their copyrights away but instead ensure their work is published on the Internet, under terms that allow anyone to access it. But even under the best scenarios, their work will only apply to things published in the future. Everything up until now will have been lost.
                </p>

                <p>
                    That is too high a price to pay. Forcing academics to pay money to read the work of their colleagues? Scanning entire libraries but only allowing the folks at Google to read them? Providing scientific articles to those at elite universities in the First World, but not to children in the Global South? It’s outrageous and unacceptable.
                </p>

                <p>
                    “I agree,” many say, “but what can we do? The companies hold the copyrights, they make enormous amounts of money by charging for access, and it’s perfectly legal — there’s nothing we can do to stop them.” But there is something we can, something that’s already being done: we can fight back.
                </p>

                <div class="py-8 my-8 border-y border-[var(--text-main)]/30">
                    <h2 class="font-syne text-2xl font-bold uppercase mb-4 tracking-tight opacity-100">Access is Not a Privilege</h2>
                    <p>
                        Those with access to these resources — students, librarians, scientists — you have been given a privilege. You get to feed at this banquet of knowledge while the rest of the world is locked out. But you need not — indeed, morally, you cannot — keep this privilege for yourselves. You have a duty to share it with the world. And you have: trading passwords with colleagues, filling download requests for friends.
                    </p>
                </div>

                <p>
                    Meanwhile, those who have been locked out are not standing idly by. You have been sneaking through holes and climbing over fences, liberating the information locked up by the publishers and sharing them with your friends.
                </p>

                <p>
                    But all of this action goes on in the dark, hidden underground. It’s called stealing or piracy, as if sharing a wealth of knowledge were the moral equivalent of plundering a ship and murdering its crew. But sharing isn’t immoral — it’s a moral imperative. Only those blinded by greed would refuse to let a friend make a copy.
                </p>

                <p>
                    Large corporations, of course, are blinded by greed. The laws under which they operate require it — their shareholders would revolt at anything less. And the politicians they have bought off back them, passing laws giving them the exclusive power to decide who can make copies.
                </p>

                <p>
                    There is no justice in following unjust laws. It’s time to come into the light and, in the grand tradition of civil disobedience, declare our opposition to this private theft of public culture.
                </p>

                <p>
                    We need to take information, wherever it is stored, make our copies and share them with the world. We need to take stuff that's out of copyright and add it to the archive. We need to buy secret databases and put them on the Web. We need to download scientific journals and upload them to file sharing networks. We need to fight for Guerilla Open Access.
                </p>

                <p class="text-2xl md:text-3xl font-syne font-bold tracking-tighter uppercase pt-12 opacity-100">
                    With enough of us, around the world, we’ll not just send a strong message opposing the privatization of knowledge — we’ll make it a thing of the past.
                    <br><br>
                    <span class="text-[var(--text-accent)]">Will you join us?</span>
                </p>

                <div class="pt-16 border-t border-[var(--text-main)]/30 opacity-60 italic text-sm font-mono uppercase">
                    — Aaron Swartz<br>
                    <span class="text-[10px] opacity-75 font-normal tracking-wide">July 2008, Eremo, Italy</span>
                </div>
                
                <?php else: ?>
                
                <h2 class="font-syne text-2xl font-bold uppercase mb-4 tracking-tight opacity-100"> Bilgi güçtür. Fakat tüm güçler gibi onu kendileri için saklamak isteyenler var. </h2>

                <p>
                    Dergiler ve kitaplarda yüzyıllardan beri yayınlanan dünyanın tüm bilimsel ve kültürel mirası artan miktarda sayısallaştırılıyor ve özel şirketler tarafından işleniyor. Bilimde en son gelişmeleri içeren makalelerimi okumak istiyorsun, Reed Elsevier gibi yayıncılara çok büyük paralar göndermen gerekiyor.
                </p>

                <p>
                    Bunu değiştirmek için mücadele verenler var. Açık Erişim Hareketi bilim insanlarının telif haklarını tamamen vermemeleri için yiğitçe savaştı, sonuçta çalışmaların bir kopyasının internette herkesin erişimine açık olmasını sağladı. Fakat en iyi senaryo ile bile bu gelecekteki çalışmalar için geçerli olacaktır. Şimdiye kadar yapılanlar ise kaybolacaktır.
                </p>

                <div class="py-8 my-8 border-y border-[var(--text-main)]/30">
                    <h2 class="font-syne text-2xl font-bold uppercase mb-4 tracking-tight opacity-100">Erişim Bir Ayrıcalık Değildir</h2>
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

                <p class="text-2xl md:text-3xl font-syne font-bold tracking-tighter uppercase pt-12 opacity-100">
                    Dünyada yeteri kadar olduğumuzda, bilgiyi özelleştirenlere karşı yalnızca güçlü bir mesaj göndermekle kalmayacağız – onu geçmişe gömeceğiz. 
                    <br><br>
                    <span class="text-[var(--text-accent)]">Bize katılacak mısın?</span>
                </p>

                <div class="pt-16 border-t border-[var(--text-main)]/30 opacity-60 italic text-sm font-mono uppercase">
                    — Aaron Swartz
                </div>
                
                <?php endif; ?>
            </div>
        </article>
    </div>

</main>

<script nonce="<?= CSP_NONCE ?>">
document.addEventListener("DOMContentLoaded", () => {
    const canvas = document.getElementById('manifesto-canvas');
    if (!canvas) return;
    const ctx = canvas.getContext('2d');
    const dpr = window.devicePixelRatio || 1;

    let cssWidth, cssHeight;

    function resize() {
        cssWidth = canvas.parentElement.offsetWidth;
        cssHeight = canvas.parentElement.offsetHeight;
        canvas.width = cssWidth * dpr;
        canvas.height = cssHeight * dpr;
        canvas.style.width = cssWidth + 'px';
        canvas.style.height = cssHeight + 'px';
        ctx.setTransform(dpr, 0, 0, dpr, 0, 0);
    }
    resize();

    const resizeObserver = new ResizeObserver(() => { resize(); });
    resizeObserver.observe(canvas.parentElement);

    const particles = [];
    const maxParticles = 35;

    class Particle {
        constructor() {
            this.x = Math.random() * (cssWidth || 1);
            this.y = Math.random() * (cssHeight || 1);
            this.vx = (Math.random() - 0.5) * 0.35;
            this.vy = (Math.random() - 0.5) * 0.35;
            this.radius = Math.random() * 2 + 1.2;
        }
        update() {
            this.x += this.vx;
            this.y += this.vy;
            if (this.x < 0 || this.x > cssWidth) this.vx *= -1;
            if (this.y < 0 || this.y > cssHeight) this.vy *= -1;
        }
        draw(color) {
            ctx.beginPath();
            ctx.arc(this.x, this.y, this.radius, 0, Math.PI * 2);
            ctx.fillStyle = color;
            ctx.fill();
        }
    }

    for (let i = 0; i < maxParticles; i++) {
        particles.push(new Particle());
    }

    function animate() {
        ctx.clearRect(0, 0, cssWidth, cssHeight);
        const color = getComputedStyle(document.documentElement).getPropertyValue('--text-main').trim() || '#6D2323';

        for (let i = 0; i < particles.length; i++) {
            particles[i].update();
            particles[i].draw(color);
            for (let j = i + 1; j < particles.length; j++) {
                const dx = particles[i].x - particles[j].x;
                const dy = particles[i].y - particles[j].y;
                const dist = Math.sqrt(dx * dx + dy * dy);
                if (dist < 110) {
                    ctx.beginPath();
                    ctx.moveTo(particles[i].x, particles[i].y);
                    ctx.lineTo(particles[j].x, particles[j].y);
                    ctx.strokeStyle = color;
                    ctx.globalAlpha = (1 - (dist / 110)) * 0.18;
                    ctx.lineWidth = 0.7;
                    ctx.stroke();
                    ctx.globalAlpha = 1.0;
                }
            }
        }
        requestAnimationFrame(animate);
    }

    animate();
});
</script>

<?php require_once ROOT . '/app/Views/inc/footer.php'; ?>