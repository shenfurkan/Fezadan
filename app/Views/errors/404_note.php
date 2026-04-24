<?php 
$page_title = "FEZADAN | Frekans bulunamadı";
require_once ROOT . '/app/Views/inc/notes_header.php'; 
?>

<style>
    body { overflow: hidden; height: 100vh; }
    .bg-text-404 {
        position: absolute; top: 40%; left: 50%; transform: translate(-50%, -50%);
        font-family: 'Syne', sans-serif; font-size: 20vw; font-weight: 800;
        color: var(--text-main); opacity: 0.05; pointer-events: none; z-index: 0; line-height: 1;
    }
</style>

<main class="flex-grow relative flex flex-col justify-center items-center text-center px-4">
    <div class="bg-text-404">404</div>
    <div class="relative z-10">
        <h1 class="font-syne text-4xl md:text-6xl font-bold text-[var(--text-main)] mb-4 uppercase tracking-wider">
            Not Bulunamadı
        </h1>
        <p class="text-[var(--text-main)] opacity-80 text-lg md:text-xl font-light mb-10 tracking-wide">
            Bu not muhtemelen silindi veya hiç var olmadı...
        </p>
        <a href="/" class="inline-block border border-[var(--text-accent)] text-[var(--text-accent)] px-8 py-3 uppercase tracking-[0.2em] text-xs font-bold font-syne hover:bg-[var(--text-accent)] hover:text-[#FEF9E1] transition-all duration-300">
            Arşive Dön
        </a>
    </div>
</main>

<?php 
?>