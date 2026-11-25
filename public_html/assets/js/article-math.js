// @ts-check
(function (window, document) {
    'use strict';
    var loading;
    var queue = Promise.resolve();

    function load() {
        if (loading) return loading;
        var timeout;
        loading = new Promise(function (resolve, reject) {
            timeout = setTimeout(function () { reject(new Error('Formül kütüphanesi zamanında yüklenemedi.')); }, 30000);
            // Only explicit formula nodes are rendered. No autoload/require/html packages,
            // automatic prose scanning, third-party fonts or speech worker requests.
            window.MathJax = {
                loader: {
                    paths: { mathjax: '/assets/vendor/mathjax-4.1.3', fonts: '/assets/vendor/mathjax-4.1.3/fonts' },
                    load: ['input/tex-base', 'output/svg', '[tex]/ams', '[tex]/textmacros', '[tex]/boldsymbol', '[tex]/cancel', '[tex]/mathtools', '[tex]/braket'],
                    failed: function () { reject(new Error('Yerel formül bileşeni yüklenemedi.')); }
                },
                startup: { typeset: false },
                tex: { packages: ['base', 'ams', 'newcommand', 'textmacros', 'boldsymbol', 'cancel', 'mathtools', 'braket'], maxBuffer: 12000, maxMacros: 1000, tags: 'none' },
                output: { font: 'mathjax-tex' },
                svg: { fontCache: 'none' },
                options: {}
            };
            var script = document.createElement('script');
            script.src = '/assets/vendor/mathjax-4.1.3/startup.js';
            script.onload = function () {
                window.MathJax.startup.promise.then(function () { resolve(window.MathJax); }, reject);
            };
            script.onerror = function () { reject(new Error('Formül kütüphanesi yüklenemedi.')); };
            document.head.appendChild(script);
        }).catch(function (error) { loading = null; throw error; }).finally(function () { clearTimeout(timeout); });
        return loading;
    }

    /** Render a copy; the editor and saved HTML always retain plain TeX text. */
    function render(root) {
        var nodes = Array.from(root.querySelectorAll('.fezadan-math:not([data-math-rendered])'));
        if (!nodes.length) return Promise.resolve();
        var tasks = nodes.slice(0, 1000).map(function (node) {
            return { node: node, source: node.textContent.trim(), display: node.getAttribute('data-math-display') === '1' };
        });
        var work = queue.then(function () { return load(); }).then(async function (mathjax) {
            var sliceStart = performance.now();
            for (var task of tasks) {
                // tex2svgPromise only yields to microtasks; let the page paint and respond.
                if (performance.now() - sliceStart > 50) {
                    await new Promise(function (resolve) { setTimeout(resolve); });
                    sliceStart = performance.now();
                }
                if (!root.contains(task.node) || task.node.dataset.mathRendered) continue;
                var node = task.node;
                try {
                    if (!task.source || new TextEncoder().encode(task.source).length > 12000) throw new Error('Formül sınırı aşıldı.');
                    // A fresh input context per formula prevents definitions from leaking
                    // into later formulas or other preview renders. SVG fonts are reused.
                    mathjax.startup.input = mathjax.startup.getInputJax();
                    mathjax.startup.document = mathjax.startup.getDocument();
                    var output = await mathjax.tex2svgPromise(task.source, { display: task.display });
                    if (!root.contains(node)) continue;
                    if (output.querySelector('[data-mjx-error]')) throw new Error('Formül işlenemedi.');
                    node.replaceChildren(output);
                    node.dataset.mathRendered = '1';
                    node.setAttribute('role', 'math');
                    node.setAttribute('aria-label', task.source);
                    node.title = task.source;
                } catch (error) {
                    node.textContent = task.source;
                    node.dataset.mathRendered = 'error';
                    node.classList.add('fezadan-math-error');
                    node.title = 'Formül gösterilemedi; TeX kaynağı görüntüleniyor.';
                }
            }
        }).catch(function (error) {
            for (var task of tasks) {
                if (root.contains(task.node)) task.node.title = error.message;
            }
        });
        queue = work;
        return work;
    }

    window.FezadanMath = { render: render };
    function init() {
        var article = document.querySelector('.journal-text');
        if (article) render(article);
    }
    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', init);
    else init();
})(window, document);
