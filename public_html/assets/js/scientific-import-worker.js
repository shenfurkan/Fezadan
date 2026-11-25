// @ts-check
// Module worker: isolated, memory-only filesystem. No native compiler or remote fetches.
import { createPandocInstance } from '../vendor/pandoc-1.1.0/core.js';

const MAX_BYTES = 524288;
const MAX_FORMULAS = 1000;
const MAX_MATH_BYTES = 12000;
const LAYOUT_ONLY = /^(?:\\(?:maketitle|tableofcontents|newpage|clearpage|pagebreak|centering|noindent|bigskip|medskip|smallskip|[vh]space\*?\{[^{}]*\})\s*)+$/;
const bytes = new TextEncoder();

function escapeHtml(value) {
    return String(value).replace(/[&<>"']/g, ch => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' })[ch]);
}

function checkedResult(result) {
    if (!result.stdout) throw new Error(result.stderr || 'Belge dönüştürülemedi.');
    if (bytes.encode(result.stdout).length > MAX_BYTES * 4) throw new Error('Dönüştürülen belge çok büyük.');
    return result;
}

/** Keep unrenderable source visible instead of silently dropping it in HTML. */
function protectUnsupported(ast, warnings) {
    let formulas = 0;
    function visit(node, depth) {
        if (depth > 64) throw new Error('Belge yapısı çok derin.');
        if (!node || typeof node !== 'object') return node;
        if (Array.isArray(node)) return node.map(child => visit(child, depth + 1)).filter(child => child !== undefined);
        if (node.t === 'Math') {
            if (++formulas > MAX_FORMULAS || bytes.encode(node.c[1]).length > MAX_MATH_BYTES) {
                throw new Error('Formül sınırı aşıldı (1000 formül, formül başına 12 KB).');
            }
        }
        // Raw HTML from Markdown goes to the server allow-list; only TeX is unrenderable.
        if ((node.t === 'RawBlock' || node.t === 'RawInline') && node.c[0] !== 'html') {
            // Page-layout commands have no web equivalent; dropping them loses no content.
            if (LAYOUT_ONLY.test(node.c[1].trim())) return undefined;
            const tag = node.t === 'RawBlock' ? 'p' : 'span';
            warnings.push('Desteklenmeyen komut veya ham içerik yazıda işaretlendi.');
            return { t: node.t, c: ['html', '<' + tag + ' class="fezadan-import-error" data-import-error="1">Aktarılamayan bölüm: ' + escapeHtml(node.c[1]) + '</' + tag + '>'] };
        }
        if (node.t === 'Cite') {
            warnings.push('Kaynakça otomatik çözümlenmiyor; atıfları ve kaynakçayı kontrol edin.');
        }
        for (const key of Object.keys(node)) node[key] = visit(node[key], depth + 1);
        return node;
    }
    // Title/author metadata isn't silently substituted into the article form,
    // but the abstract is article content: keep it at the top instead of dropping it.
    const abstract = ast.meta && ast.meta.abstract;
    if (abstract) ast.blocks.unshift(...(abstract.t === 'MetaBlocks' ? abstract.c : [{ t: 'Para', c: abstract.c }]));
    if (Object.keys(ast.meta || {}).length) warnings.push('Belge üst bilgilerini (başlık, yazar vb.) formda ayrıca kontrol edin.' + (abstract ? ' Özet yazının başına eklendi.' : ''));
    ast.blocks = visit(ast.blocks, 0);
    return ast;
}

self.onmessage = async event => {
    try {
        const { source, format } = event.data;
        if (!['latex', 'markdown'].includes(format) || typeof source !== 'string' || !source.trim() || bytes.encode(source).length > MAX_BYTES) {
            throw new Error('UTF-8 .tex veya .md dosyası seçin (en fazla 512 KB).');
        }
        self.postMessage({ stage: 'Dönüştürücü yükleniyor; ilk kullanımda indirme biraz sürebilir.' });
        const response = await fetch(new URL('../vendor/pandoc-1.1.0/pandoc.wasm.gz', import.meta.url));
        if (!response.ok) throw new Error('Yerel Pandoc dosyası yüklenemedi.');
        let binary = await response.arrayBuffer();
        const magic = new Uint8Array(binary, 0, 2);
        // Some hosts send Content-Encoding: gzip and the browser already decodes it.
        if (magic[0] === 31 && magic[1] === 139) {
            binary = await new Response(new Blob([binary]).stream().pipeThrough(new DecompressionStream('gzip'))).arrayBuffer();
        }
        const pandoc = await createPandocInstance(binary);
        self.postMessage({ stage: 'Belge dönüştürülüyor.' });
        const warnings = [];
        const parsed = checkedResult(await pandoc.convert({
            from: format === 'latex' ? 'latex+raw_tex' : 'markdown+tex_math_dollars+tex_math_single_backslash+raw_tex',
            to: 'json'
        }, source, {}));
        const ast = protectUnsupported(JSON.parse(parsed.stdout), warnings);
        const output = checkedResult(await pandoc.convert({
            // The article title is the page's h1: \section -> h2, \subsection -> h3, ...
            from: 'json', to: 'html5', standalone: false, 'shift-heading-level-by': 1,
            'html-math-method': { method: 'mathjax' }, wrap: 'none'
        }, JSON.stringify(ast), {}));
        for (const result of [parsed, output]) {
            for (const warning of result.warnings || []) warnings.push(warning.pretty || warning.type || 'Belgeyi kontrol edin.');
            if (result.stderr) warnings.push(result.stderr);
        }
        if (bytes.encode(output.stdout).length > MAX_BYTES) throw new Error('Aktarılan HTML 512 KB sınırını aşıyor.');
        self.postMessage({ html: output.stdout, warnings: [...new Set(warnings)] });
    } catch (error) {
        self.postMessage({ error: error instanceof Error ? error.message : 'Belge aktarılamadı.' });
    }
};
