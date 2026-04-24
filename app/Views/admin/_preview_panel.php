<?php
/**
 * _preview_panel.php
 * Önizleme paneli partial — create.php ve edit.php tarafından çağrılır.
 */
?>

<style>
    /* ============================================================
       ÖNİZLEME — fonts.css + read.php ile BİREBİR aynı stiller
       ============================================================ */

    #previewPanel {
        font-family: 'Space Grotesk', sans-serif;
        -webkit-font-smoothing: antialiased;
    }

    #previewPanel .font-body,
    #previewPanel .journal-text {
        font-family: 'EB Garamond', serif;
    }

    /* Sabit renkler iptal edilerek CSS değişkenlerine bağlandı */
    #previewPanel .journal-text {
        font-size: 1.25rem;
        line-height: 1.8;
        color: var(--text-main);
    }

    #previewPanel .font-syne,
    #previewPanel h1,
    #previewPanel .main-article-title {
        font-family: 'Syne', sans-serif;
    }

    #previewPanel .main-article-title {
        color: var(--text-main);
    }

    #previewPanel .font-mono {
        font-family: 'JetBrains Mono', monospace;
    }

    #previewPanel .journal-text h2,
    #previewPanel .journal-text h3 {
        font-family: 'Space Grotesk', sans-serif;
        font-weight: 700;
        text-transform: uppercase;
        margin-top: 2.5rem;
        margin-bottom: 1rem;
        color: var(--text-accent);
        letter-spacing: -0.02em;
        scroll-margin-top: 100px;
    }

    #previewPanel .journal-text p   { margin-bottom: 1.25em; }
    #previewPanel .journal-text a   { color: var(--text-accent); text-decoration: underline; }

    #previewPanel .journal-text ul {
        list-style-type: disc;
        padding-left: 1.5em;
        margin-bottom: 1.5em;
    }

    #previewPanel .journal-text ol {
        list-style-type: decimal;
        padding-left: 1.5em;
        margin-bottom: 1.5em;
    }

    #previewPanel .journal-text blockquote {
        border-left: 4px solid var(--text-accent);
        padding-left: 1.5rem;
        font-style: italic;
        color: var(--text-main);
        margin: 1.5rem 0;
    }

    #previewPanel .journal-text strong { font-weight: 700; }
    #previewPanel .journal-text em     { font-style: italic; }

    #previewPanel .article-grid {
        display: grid;
        grid-template-columns: 1fr;
        max-width: 1920px;
        margin: 0 auto;
        min-height: 100%;
    }

    @media (min-width: 1280px) {
        #previewPanel .article-grid {
            grid-template-columns: 1fr 56rem 1fr;
        }
        #previewPanel .article-grid.no-toc {
            grid-template-columns: 1fr 56rem 1fr;
        }
        #previewPanel .article-grid.no-toc #prev-toc-sidebar {
            visibility: hidden;
            pointer-events: none;
        }
    }

    #prev-toc-sidebar {
        display: none;
        position: sticky;
        top: 0;
        justify-self: end;
        max-height: 100vh;
        overflow-y: auto;
        padding: 1.5rem 2rem 1.5rem 0;
        width: 15rem;
        scrollbar-width: none;
        -ms-overflow-style: none;
    }

    #prev-toc-sidebar::-webkit-scrollbar { display: none; }

    @media (min-width: 1280px) {
        #prev-toc-sidebar { display: block; }
    }

    .prev-toc-list {
        position: static;
        list-style: none;
        padding: 0;
        margin: 0;
    }

    #prev-toc-sidebar > div {
        position: relative;
    }

    .prev-toc-track {
        position: absolute;
        right: -1rem; top: 0;
        width: 2px;
        background: var(--line-color);
        opacity: 0.4;
        border-radius: 1px;
    }

    .prev-toc-progress-line {
        position: absolute;
        right: -1rem; top: 0;
        width: 2px; height: 0;
        background: var(--text-accent);
        border-radius: 1px;
        transition: height 0.15s ease-out;
        z-index: 1;
    }

    .prev-toc-item {
        position: relative;
        margin-bottom: 0.15rem;
    }

    .prev-toc-link {
        display: block;
        text-align: right;
        padding: 0.35rem 1rem 0.35rem 0;
        text-decoration: none;
        color: var(--text-main);
        opacity: 0.25;
        font-size: 0.95rem;
        font-family: 'Space Grotesk', sans-serif;
        font-weight: 600;
        line-height: 1.35;
        transition: all 0.2s;
    }

    .prev-toc-item[data-level="1"] .prev-toc-link {
        font-family: 'Syne', sans-serif;
        font-size: 1.05rem;
        font-weight: 700;
        color: var(--text-accent);
        opacity: 1;
        padding-right: 2rem;
        padding-bottom: 0.5rem;
    }

    .prev-toc-item[data-level="3"] .prev-toc-link {
        font-size: 0.85rem;
        opacity: 0.2;
    }

    .prev-toc-dot {
        position: absolute;
        right: calc(-1rem - 3px);
        top: 50%;
        transform: translateY(-50%);
        width: 8px; height: 8px;
        box-sizing: border-box;
        border-radius: 50%;
        background: var(--line-color);
        border: 2px solid var(--bg-paper);
        z-index: 2;
        transition: all 0.2s;
    }

    .prev-toc-item[data-level="1"] .prev-toc-dot {
        width: 10px; height: 10px;
        right: calc(-1rem - 4px);
        background: var(--text-accent);
        border: none;
    }

    .prev-toc-item[data-level="3"] .prev-toc-dot {
        width: 6px; height: 6px;
        right: calc(-1rem - 2px);
    }

    .prev-toc-item.toc-active .prev-toc-link { opacity: 1; color: var(--text-accent); }
    .prev-toc-item.toc-active .prev-toc-dot  { background: var(--text-accent); box-shadow: 0 0 0 3px rgba(163,29,29,0.2); border: none; }
    .prev-toc-item.toc-passed .prev-toc-link { opacity: 0.6; color: var(--text-accent); }
    .prev-toc-item.toc-passed .prev-toc-dot  { background: var(--text-accent); border: none; }

    #previewPanel .journal-text figure {
        margin: 2rem 0;
        display: block;
    }

    #previewPanel .journal-text figure img {
        display: block;
        max-width: 100%;
        height: auto;
    }

    #previewPanel .journal-text figure figcaption {
        display: block;
        margin-top: 0.5rem;
        font-family: 'JetBrains Mono', monospace;
        font-size: 0.78rem;
        line-height: 1.5;
        color: var(--text-accent);
        opacity: 0.7;
        font-style: italic;
        padding-left: 0.75rem;
        border-left: 2px solid var(--text-accent);
    }

    #previewPanel .journal-text figure figcaption[contenteditable] {
        pointer-events: none;
        user-select: none;
        cursor: default;
        -webkit-user-modify: read-only;
    }

    #previewPanel .journal-text figure figcaption:empty {
        display: none;
    }

    #prev-progress-bar {
        position: fixed; top: 0; left: 0;
        height: 4px;
        background: var(--text-accent);
        width: 0%;
        z-index: 9999;
        transition: width 0.1s;
    }
</style>

<div id="previewPanel" style="display:none; overflow-y:auto; background:var(--bg-paper);"
     class="flex-1">
    <div id="prev-progress-bar"></div>

    <div class="article-grid">

        <aside id="prev-toc-sidebar">
            <div style="position:relative;">
                <div class="prev-toc-track"         id="prev-toc-track"></div>
                <div class="prev-toc-progress-line" id="prev-toc-progress"></div>
                <ul class="prev-toc-list" id="prev-toc-list"></ul>
            </div>
        </aside>

        <main class="relative z-10 w-full px-6 py-12 md:py-20 min-w-0">
            <article>

                <header class="mb-12 border-b border-[var(--line-color)] pb-10">
                    <div class="flex flex-wrap items-center gap-2 md:gap-3 font-mono text-xs
                                text-[var(--text-accent)] mb-6 uppercase tracking-wider font-bold">
                        <span id="prev-date"><?php echo $preview_date ?? date('d F Y'); ?></span>
                        <span class="opacity-60 font-light px-1">&mdash;</span>
                        <span id="prev-cats" class="opacity-50">KATEGORİ SEÇİLMEDİ</span>
                        <span class="opacity-60 font-light px-1">&mdash;</span>
                        <span class="flex items-center gap-2">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                      d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            <span id="prev-readtime">1</span> DK OKUMA
                        </span>
                    </div>

                    <h1 id="prev-title"
                        class="main-article-title font-syne text-5xl md:text-7xl font-bold leading-[0.9] tracking-tight mb-8">
                        Başlık buraya gelecek...
                    </h1>

                    <p id="prev-desc"
                       class="font-body text-xl md:text-2xl italic leading-normal
                              text-[var(--text-main)] opacity-90 pl-6 border-l-4 border-[var(--text-accent)]"
                       style="display:none;"></p>
                </header>

                <div class="journal-text font-body">

                    <figure id="prev-cover-wrap" class="my-12" style="display:none;">
                        <div class="p-2 border border-[var(--line-color)]">
                            <div class="aspect-video w-full overflow-hidden">
                                <img id="prev-cover" src="" alt="Kapak Görseli"
                                     class="w-full h-full object-cover mix-blend-multiply contrast-110">
                            </div>
                        </div>
                    </figure>

                    <div id="prev-content">
                        <p class="opacity-40 italic">İçerik editörden yansıyacak...</p>
                    </div>

                    <div id="prev-refs-wrap" class="my-12 border border-[var(--line-color)]"
                         style="display:none;">
                        <button type="button" onclick="togglePrevRefs()"
                                class="w-full flex justify-between items-center p-4
                                       hover:bg-[var(--bg-secondary)]/30 transition-colors group">
                            <span class="font-syne font-bold uppercase text-sm tracking-widest
                                         text-[var(--text-accent)] flex items-center gap-2">
                                <span class="w-2 h-2 bg-[var(--text-accent)] rounded-full inline-block"></span>
                                KAYNAKÇA VE NOTLAR
                            </span>
                            <span id="prev-refs-icon" class="font-mono text-xl">+</span>
                        </button>
                        <div id="prev-refs-content"
                             class="hidden border-t border-[var(--line-color)] bg-[var(--bg-secondary)]/10 p-6">
                            <ul id="prev-refs-list"
                                class="space-y-3 font-mono text-xs text-[var(--text-main)]/80 list-none p-0 m-0">
                            </ul>
                        </div>
                    </div>

                </div>
            </article>
        </main>

        <div class="hidden xl:block"></div>
    </div>
</div>

<script>
(function () {
    window.switchTab = function(tab) {
        const writePanel   = document.getElementById('writePanel') || document.getElementById('uploadForm');
        const previewPanel = document.getElementById('previewPanel');
        const tabWrite     = document.getElementById('tabWrite');
        const tabPreview   = document.getElementById('tabPreview');

        if (tab === 'preview') {
            writePanel.style.display   = 'none';
            previewPanel.style.display = 'block';
            tabWrite.style.cssText   = _tabStyle(false);
            tabPreview.style.cssText = _tabStyle(true);
            updatePreview();
            
            previewPanel.querySelectorAll('figcaption[contenteditable]').forEach(function(el) {
                el.removeAttribute('contenteditable');
                el.removeAttribute('data-placeholder');
            });
        } else {
            previewPanel.style.display = 'none';
            writePanel.style.display   = writePanel.id === 'uploadForm' ? 'grid' : 'flex';
            tabWrite.style.cssText   = _tabStyle(true);
            tabPreview.style.cssText = _tabStyle(false);
        }
    };

    function _tabStyle(active) {
        return active
            ? 'font-family:monospace;font-size:0.625rem;text-transform:uppercase;padding:0.5rem 1rem;font-weight:700;border:2px solid var(--text-main);background:var(--text-main);color:var(--bg-paper);cursor:pointer;'
            : 'font-family:monospace;font-size:0.625rem;text-transform:uppercase;padding:0.5rem 1rem;font-weight:700;border:2px solid var(--text-main);background:transparent;color:var(--text-main);cursor:pointer;';
    }

    window.renderPrevRefs = function(refsText) {
        const refsWrap = document.getElementById('prev-refs-wrap');
        const refsList = document.getElementById('prev-refs-list');
        if (!refsText.trim()) { refsWrap.style.display = 'none'; return; }

        refsWrap.style.display = 'block';
        refsList.innerHTML = '';
        refsText.split('\n').forEach(line => {
            line = line.trim(); if (!line) return;
            let key = '', val = line;
            if (line.includes('=')) {
                [key, val] = line.split('=', 2); key = key.trim(); val = val.trim();
            } else if (/^\[(\d+)\]/.test(line)) {
                key = line.match(/^\[(\d+)\]/)[1];
                val = line.replace(/^\[\d+\]/, '').trim();
            }
            const isUrl = /^https?:\/\//.test(val);
            const li = document.createElement('li');
            li.className = 'flex gap-3 p-2 border border-transparent';
            li.style.scrollMarginTop = '100px';
            if (key) li.id = `prev-ref-item-${key}`;
            li.innerHTML = key
                ? `<a href="#prev-ref-link-${key}" data-ref-back="prev-ref-link-${key}"
                      class="font-bold text-[var(--text-accent)] flex-shrink-0 hover:underline cursor-pointer">[${key}]</a>` +
                  (isUrl
                      ? `<a href="${val}" target="_blank"
                            class="underline decoration-[var(--text-accent)] hover:text-[var(--text-accent)] break-all">${val} ↗</a>`
                      : `<span>${val}</span>`)
                : `<span class="text-[var(--text-accent)] flex-shrink-0">•</span><span>${val}</span>`;
            refsList.appendChild(li);
        });
    };

    window.togglePrevRefs = function() {
        const content = document.getElementById('prev-refs-content');
        const icon    = document.getElementById('prev-refs-icon');
        content.classList.toggle('hidden');
        icon.textContent = content.classList.contains('hidden') ? '+' : '−';
    };

    document.getElementById('prev-content').addEventListener('click', function(e) {
        const a = e.target.closest('a[href^="#prev-ref-item"]');
        if (!a) return;
        e.preventDefault();
        const targetId    = a.getAttribute('href').slice(1);
        const refsContent = document.getElementById('prev-refs-content');
        if (refsContent.classList.contains('hidden')) togglePrevRefs();
        const panel = document.getElementById('previewPanel');
        setTimeout(() => {
            const target = document.getElementById(targetId);
            if (!target) return;
            target.style.background = 'rgba(163,29,29,0.1)';
            target.style.border     = '1px solid rgba(163,29,29,0.3)';
            setTimeout(() => { target.style.background = ''; target.style.border = ''; }, 2000);
            _panelScrollTo(panel, target);
        }, 120);
    });

    document.getElementById('prev-refs-list').addEventListener('click', function(e) {
        const a = e.target.closest('a[data-ref-back]');
        if (!a) return;
        e.preventDefault();
        const target = document.getElementById(a.getAttribute('data-ref-back'));
        if (target) _panelScrollTo(document.getElementById('previewPanel'), target);
    });

    function _panelScrollTo(panel, target) {
        let offsetTop = 0;
        let el = target;
        while (el && el !== panel) {
            offsetTop += el.offsetTop;
            el = el.offsetParent;
        }
        panel.scrollTo({ top: Math.max(0, offsetTop - 80), behavior: 'smooth' });
    }

    let _headings     = [];
    let _tocItems     = [];
    let _activeIndex  = -1;
    let _tocStartTop  = 0;   
    let _tocItemTops  = [];  

    function _resetToc() {
        const tocList    = document.getElementById('prev-toc-list');
        const progressEl = document.getElementById('prev-toc-progress');
        tocList.innerHTML = '';
        _headings    = [];
        _tocItems    = [];
        _activeIndex = -1;
        _tocStartTop = 0;
        _tocItemTops = [];
        if (progressEl) progressEl.style.height = '0';
    }

    window.buildPrevToc = function() {
        _resetToc();

        const tocList    = document.getElementById('prev-toc-list');
        const sidebar    = document.getElementById('prev-toc-sidebar');
        const track      = document.getElementById('prev-toc-track');   
        const progressEl = document.getElementById('prev-toc-progress'); 
        const contentDiv = document.getElementById('prev-content');
        const headingEls = contentDiv.querySelectorAll('h2, h3');

        const grid = document.querySelector('#previewPanel .article-grid');
        if (headingEls.length === 0) {
            if (grid) grid.classList.add('no-toc');
            return;
        }
        if (grid) grid.classList.remove('no-toc');

        const titleEl = document.getElementById('prev-title');
        _headings.push({ el: titleEl, id: 'prev-article-top', level: 1 });
        tocList.appendChild(_makeTocItem(1, titleEl.textContent.trim(), 'prev-article-top'));

        headingEls.forEach((el, i) => {
            el.id = 'prev-heading-' + i;
            el.style.scrollMarginTop = '100px';
            const level = parseInt(el.tagName.charAt(1));
            _headings.push({ el: el, id: el.id, level: level });
            tocList.appendChild(_makeTocItem(level, el.textContent.trim(), el.id));
        });

        const refsWrap = document.getElementById('prev-refs-wrap');
        if (refsWrap.style.display !== 'none') {
            refsWrap.id = 'prev-refs-section';
            refsWrap.style.scrollMarginTop = '100px';
            _headings.push({ el: refsWrap, id: 'prev-refs-section', level: 2 });
            tocList.appendChild(_makeTocItem(2, 'Kaynakça ve Notlar', 'prev-refs-section'));
        }

        const _myItems = _tocItems.slice();

        function _applyTrack(attempt) {
            if (_myItems !== _tocItems && _tocItems.length === 0) return;
            if (_myItems.length < 2) return;

            const tops = _myItems.map(li => li.offsetTop + li.offsetHeight / 2);
            const startTop = tops[0];
            const endTop   = tops[tops.length - 1];

            if ((endTop - startTop) <= 0 && attempt < 20) {
                setTimeout(() => _applyTrack(attempt + 1), 50);
                return;
            }

            _tocStartTop = startTop;
            _tocItemTops = tops;

            track.style.top    = startTop + 'px';
            track.style.height = Math.max(0, endTop - startTop) + 'px';
            progressEl.style.top = startTop + 'px';
            updatePrevScrollSpy();
        }

        requestAnimationFrame(() => setTimeout(() => _applyTrack(0), 0));
    };

    function _makeTocItem(level, text, targetId) {
        const li = document.createElement('li');
        li.className     = 'prev-toc-item';
        li.dataset.level = level;

        const a = document.createElement('a');
        a.className   = 'prev-toc-link';
        a.href        = '#' + targetId;
        a.textContent = text;
        a.addEventListener('click', e => {
            e.preventDefault();
            const panel = document.getElementById('previewPanel');
            if (targetId === 'prev-article-top') {
                panel.scrollTo({ top: 0, behavior: 'smooth' });
            } else {
                const target = document.getElementById(targetId);
                if (!target) return;
                
                let offsetTop = 0;
                let el = target;
                while (el && el !== panel) {
                    offsetTop += el.offsetTop;
                    el = el.offsetParent;
                }
                panel.scrollTo({ top: Math.max(0, offsetTop - 100), behavior: 'smooth' });
            }
        });

        const dot = document.createElement('span');
        dot.className = 'prev-toc-dot';

        li.appendChild(a);
        li.appendChild(dot);
        _tocItems.push(li);
        return li;
    }

    window.updatePrevScrollSpy = function() {
        if (_headings.length === 0) return;
        const panel     = document.getElementById('previewPanel');
        const scrollTop = panel.scrollTop;
        const offset    = 120;
        let currentIdx  = -1;

        if (scrollTop + panel.clientHeight >= panel.scrollHeight - 10) {
            currentIdx = _headings.length - 1;
        } else {
            for (let i = _headings.length - 2; i >= 0; i--) {
                const headingTop = _headings[i].el.getBoundingClientRect().top
                                 - panel.getBoundingClientRect().top
                                 + scrollTop - offset;
                if (scrollTop >= headingTop) { currentIdx = i; break; }
            }
        }

        if (currentIdx !== _activeIndex) {
            _activeIndex = currentIdx;
            _tocItems.forEach((item, i) => {
                item.classList.remove('toc-active', 'toc-passed');
                if (i === currentIdx)     item.classList.add('toc-active');
                else if (i < currentIdx) item.classList.add('toc-passed');
            });
        }

        const progressEl = document.getElementById('prev-toc-progress');
        if (currentIdx >= 0 && _tocItemTops.length > 0 && progressEl) {
            const dotCenter = _tocItemTops[currentIdx] ?? _tocStartTop;
            progressEl.style.top    = _tocStartTop + 'px';
            progressEl.style.height = Math.max(0, dotCenter - _tocStartTop) + 'px';
        } else if (progressEl) {
            progressEl.style.height = '0';
        }
    };

    window._bindPreviewScroll = function() {
        const pp = document.getElementById('previewPanel');
        pp.onscroll = function() {
            const pct = pp.scrollHeight - pp.clientHeight > 0
                ? (pp.scrollTop / (pp.scrollHeight - pp.clientHeight)) * 100
                : 0;
            document.getElementById('prev-progress-bar').style.width = pct + '%';
            updatePrevScrollSpy();
        };
    };

})();
</script>