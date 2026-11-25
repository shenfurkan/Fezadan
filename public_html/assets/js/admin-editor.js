// @ts-check
(function (window, document, $) {
    'use strict';

    if (!$ || !$.fn || !$.fn.summernote) {
        window.FezadanAdminEditor = { init: function () { console.error('Summernote is not loaded.'); } };
        return;
    }

    var DEFAULT_TOOLBAR = [
        ['undo', ['undo', 'redo']],
        ['style', ['style']],
        ['font', ['bold', 'italic', 'underline']],
        ['para', ['ul', 'ol']],
        ['insert', ['link', 'picture']],
        ['advanced-font', ['strikethrough', 'clear']],
        ['advanced-fontname', ['fontname']],
        ['advanced-fontsize', ['fontsize']],
        ['advanced-color', ['color']],
        ['advanced-paragraph', ['paragraph', 'height']],
        ['advanced-insert', ['video', 'table', 'hr', 'blockquote']],
        ['advanced-view', ['fullscreen', 'codeview']]
    ];

    function qs(selector, root) {
        return selector ? (root || document).querySelector(selector) : null;
    }

    function qsa(selector, root) {
        return Array.prototype.slice.call((root || document).querySelectorAll(selector));
    }

    function textOf(el) {
        return el ? (el.textContent || '').trim() : '';
    }

    function htmlToText(html) {
        var template = document.createElement('template');
        template.innerHTML = html || '';
        return (template.content.textContent || '').trim();
    }

    function readerPreviewHtml(html) {
        var template = document.createElement('template');
        template.innerHTML = html || '';
        qsa('script, iframe, object, embed, form, input, select, textarea, button, applet, audio, video, source, track, link, style, meta, base, frame, frameset', template.content).forEach(function (node) { node.remove(); });
        qsa('*', template.content).forEach(function (node) {
            Array.from(node.attributes).forEach(function (attribute) {
                if (/^on/i.test(attribute.name) || attribute.name === 'contenteditable' || attribute.name === 'data-placeholder') node.removeAttribute(attribute.name);
            });
        });
        return template.innerHTML;
    }

    function safeJsonParse(value) {
        try { return JSON.parse(value); } catch (e) { return null; }
    }

    function randomId(prefix) {
        return (prefix || 'id') + '-' + Date.now().toString(36) + '-' + Math.random().toString(36).slice(2, 8);
    }

    function escapeHtml(value) {
        return String(value || '').replace(/[&<>"']/g, function (ch) {
            return ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' })[ch];
        });
    }

    // Walk prose text, not serialized HTML: formulas, code and link attributes are opaque.
    function referenceTextNodes(root) {
        var walker = document.createTreeWalker(root, NodeFilter.SHOW_TEXT);
        var nodes = [];
        var node;
        while ((node = walker.nextNode())) {
            if (node.parentElement && !node.parentElement.closest('.fezadan-math, pre, code, a, script, style')) nodes.push(node);
        }
        return nodes;
    }

    function linkPreviewReferences(root) {
        var seen = new Set();
        referenceTextNodes(root).forEach(function (node) {
            var parts = node.textContent.split(/(\[\d+\])/g);
            if (parts.length === 1) return;
            var fragment = document.createDocumentFragment();
            parts.forEach(function (part) {
                var match = part.match(/^\[(\d+)\]$/);
                if (!match) { fragment.appendChild(document.createTextNode(part)); return; }
                var num = match[1];
                var sup = document.createElement('sup');
                sup.className = 'reference-sup';
                var link = document.createElement('a');
                link.href = '#prev-ref-item-' + num;
                link.className = 'text-[var(--text-accent)] hover:underline';
                link.style.fontFamily = '"JetBrains Mono", monospace';
                link.style.fontSize = '0.75rem';
                link.textContent = part;
                if (!seen.has(num)) link.id = 'prev-ref-link-' + num;
                seen.add(num);
                sup.appendChild(link);
                fragment.appendChild(sup);
            });
            node.parentNode.replaceChild(fragment, node);
        });
    }

    function initThemeToggle() {
        qsa('.theme-switch-wrapper').forEach(function (btn) {
            if (btn.dataset.editorBound === '1') return;
            btn.dataset.editorBound = '1';
            btn.addEventListener('keydown', function (e) {
                if (e.key === 'Enter' || e.key === ' ') {
                    e.preventDefault();
                    btn.click();
                }
            });
            btn.addEventListener('click', function () {
                var currentTheme = document.documentElement.getAttribute('data-theme');
                var newTheme = currentTheme === 'dark' ? 'light' : 'dark';
                document.documentElement.setAttribute('data-theme', newTheme);
                try { localStorage.setItem('theme', newTheme); } catch (_) { /* Theme still works without storage. */ }
            });
        });
    }

    function slugify(text) {
        return String(text || '').toLowerCase()
            .replace(/ğ/g, 'g').replace(/ü/g, 'u').replace(/ş/g, 's')
            .replace(/ı/g, 'i').replace(/ö/g, 'o').replace(/ç/g, 'c')
            .replace(/[^a-z0-9\s-]/g, '')
            .replace(/\s+/g, '-')
            .replace(/-+/g, '-')
            .replace(/^-|-$/g, '')
            .substring(0, 80);
    }

    function setTabButtonState(active) {
        ['Write', 'Preview'].forEach(function (name) {
            var btn = document.getElementById('tab' + name);
            if (!btn) return;
            var isActive = (name.toLowerCase() === active);
            btn.setAttribute('aria-pressed', isActive ? 'true' : 'false');
            btn.classList.toggle('bg-[var(--text-main)]', isActive);
            btn.classList.toggle('text-[var(--bg-paper)]', isActive);
            btn.classList.toggle('text-[var(--text-main)]', !isActive);
        });
    }

    function previewDocument() {
        var frame = /** @type {HTMLIFrameElement} */ (document.getElementById('previewFrame'));
        return frame && frame.dataset.ready === '1' ? frame.contentDocument : null;
    }

    function ensurePreviewGlobals() {
        var preview = /** @type {HTMLDialogElement} */ (document.getElementById('previewPanel'));
        var frame = /** @type {HTMLIFrameElement} */ (document.getElementById('previewFrame'));
        var template = /** @type {HTMLTemplateElement} */ (document.getElementById('previewTemplate'));
        if (!preview || !frame || !template) return;

        window.switchTab = function (tab) {
            if (tab === 'preview') {
                if (!preview.open) preview.showModal();
                document.documentElement.classList.add('editor-preview-open');
                if (typeof window.updatePreview === 'function') window.updatePreview();
                setTabButtonState('preview');
                if (frame.contentWindow) frame.contentWindow.scrollTo(0, 0);
                return;
            }
            if (preview.open) preview.close();
            setTabButtonState('write');
        };

        if (preview.dataset.previewBound !== '1') {
            preview.dataset.previewBound = '1';
            preview.addEventListener('close', function () {
                document.documentElement.classList.remove('editor-preview-open');
                setTabButtonState('write');
            });
            document.getElementById('previewClose').addEventListener('click', function () { window.switchTab('write'); });
            qsa('[data-preview-device]', preview).forEach(function (button) {
                button.addEventListener('click', function () {
                    document.getElementById('previewStage').dataset.device = button.dataset.previewDevice;
                    qsa('[data-preview-device]', preview).forEach(function (other) {
                        other.setAttribute('aria-pressed', String(other === button));
                    });
                });
            });
            frame.addEventListener('load', function () {
                frame.dataset.ready = '1';
                var doc = frame.contentDocument;
                if (!doc) return;
                // Events inside an iframe do not bubble to the editor document.
                doc.addEventListener('keydown', function (event) {
                    if (event.key === 'Escape' || ((event.ctrlKey || event.metaKey) && event.shiftKey && event.key.toLowerCase() === 'p')) {
                        event.preventDefault();
                        window.switchTab('write');
                    }
                });
                if (window.updatePreview) window.updatePreview();
            });
            // No allow-scripts, forms, popups or top navigation: draft HTML cannot
            // execute in the authenticated admin page or escape the reader frame.
            frame.srcdoc = '<!doctype html><html lang="tr"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"></head><body>' + template.innerHTML + '</body></html>';
        }

        var writeBtn = document.getElementById('tabWrite');
        var previewBtn = document.getElementById('tabPreview');
        if (writeBtn && writeBtn.dataset.previewBound !== '1') {
            writeBtn.dataset.previewBound = '1';
            writeBtn.addEventListener('click', function () { window.switchTab('write'); });
        }
        if (previewBtn && previewBtn.dataset.previewBound !== '1') {
            previewBtn.dataset.previewBound = '1';
            previewBtn.addEventListener('click', function () { window.switchTab('preview'); });
        }

        window.renderPrevRefs = function (value) {
            var doc = previewDocument();
            if (!doc) return;
            var wrap = doc.getElementById('prev-refs-wrap');
            var list = doc.getElementById('prev-refs');
            if (!wrap || !list) return;

            var lines = String(value || '').split('\n').map(function (line) { return line.trim(); }).filter(Boolean);
            wrap.hidden = lines.length === 0;
            if (!lines.length) {
                list.innerHTML = '';
                return;
            }

            list.innerHTML = lines.map(function (line, index) {
                var match = line.match(/^(\d+)\s*=\s*(.+)$/) || line.match(/^\[(\d+)\]\s*(.+)$/);
                var num = match ? match[1] : String(index + 1);
                var body = match ? match[2] : line;
                var escaped = escapeHtml(body);
                if (/^https?:\/\//i.test(body)) {
                    escaped = '<a href="' + escapeHtml(body) + '" target="_blank" rel="noopener noreferrer" class="underline hover:text-[var(--text-accent)]">' + escapeHtml(body) + '</a>';
                }
                return '<li id="prev-ref-item-' + escapeHtml(num) + '" class="scroll-mt-24"><span class="font-bold">[' + escapeHtml(num) + ']</span> ' + escaped + '</li>';
            }).join('');
        };

        window.buildPrevToc = function () {
            var doc = previewDocument();
            if (!doc) return;
            var content = doc.getElementById('prev-content');
            var toc = doc.getElementById('prev-toc');
            if (!content || !toc) return;

            var headings = qsa('h2, h3', content).filter(function (heading) { return textOf(heading); });
            doc.getElementById('prev-toc-wrap').hidden = headings.length === 0;
            if (!headings.length) {
                toc.innerHTML = '<p class="opacity-50">Başlıklar eklendikçe burada görünür.</p>';
                return;
            }

            toc.innerHTML = headings.map(function (heading, index) {
                heading.id = 'prev-heading-' + index;
                return '<a href="#' + heading.id + '" data-level="' + heading.tagName.slice(1) + '">' + escapeHtml(textOf(heading)) + '</a>';
            }).join('');
        };

        window._bindPreviewScroll = function () {
            var doc = previewDocument();
            if (!doc || doc.documentElement.dataset.scrollBound === '1') return;
            doc.documentElement.dataset.scrollBound = '1';
            doc.addEventListener('click', function (e) {
                var link = /** @type {Element} */ (e.target).closest('a[href]');
                if (!link) return;
                e.preventDefault();
                var href = link.getAttribute('href');
                if (href.charAt(0) === '#') {
                    var target = doc.getElementById(href.slice(1));
                    if (target) target.scrollIntoView({ block: 'start' });
                } else {
                    try {
                        var url = new URL(href, window.location.href);
                        if (/^https?:$/.test(url.protocol)) window.open(url.href, '_blank', 'noopener,noreferrer');
                    } catch (_) { /* An invalid draft link must not break the preview. */ }
                }
            });
        };
    }

    function injectRuntimeStyles() {
        if (document.getElementById('admin-editor-runtime-style')) return;
        var style = document.createElement('style');
        style.id = 'admin-editor-runtime-style';
        style.textContent = [
            '.editor-status-box{border:2px solid var(--text-main);background:var(--bg-paper);color:var(--text-main);padding:.75rem 1rem;margin:.75rem 0;font-family:JetBrains Mono,monospace;font-size:.75rem;display:none;}',
            '.editor-status-box[data-kind="error"]{border-color:var(--text-accent);color:var(--text-accent);background:rgba(163,29,29,.08);}',
            '.editor-status-box[data-kind="success"]{border-color:var(--text-main);background:rgba(109,35,35,.08);}',
            '.quality-panel{border:2px solid var(--text-main);background:rgba(254,249,225,.72);padding:1rem;margin:1rem 0;}',
            '[data-theme="dark"] .quality-panel{background:rgba(31,18,18,.72);}',
            '.quality-panel h4{font-family:Syne,sans-serif;font-weight:800;text-transform:uppercase;margin:0 0 .75rem;}',
            '.quality-state{display:inline-flex;align-items:center;border:2px solid var(--text-main);padding:.35rem .55rem;font-family:JetBrains Mono,monospace;font-size:.7rem;font-weight:800;text-transform:uppercase;}',
            '.quality-state.ready{background:var(--text-main);color:var(--bg-paper);}',
            '.quality-state.warning{background:#fff4bf;color:#6D2323;border-color:#b7791f;}',
            '.quality-state.critical{background:var(--text-accent);color:var(--bg-paper);border-color:var(--text-accent);}',
            '.quality-list{display:flex;flex-direction:column;gap:.45rem;margin-top:.85rem;}',
            '.quality-item{display:grid;grid-template-columns:auto 1fr;gap:.55rem;font-family:JetBrains Mono,monospace;font-size:.72rem;line-height:1.35;}',
            '.quality-item .dot{width:.7rem;height:.7rem;border:1px solid var(--text-main);margin-top:.2rem;}',
            '.quality-item.ready .dot{background:var(--text-main);}',
            '.quality-item.warning .dot{background:#d69e2e;border-color:#b7791f;}',
            '.quality-item.critical .dot{background:var(--text-accent);border-color:var(--text-accent);}',
            '.note-editable ul{list-style-type:disc!important;padding-left:1.5em!important;margin:.75rem 0 1rem!important;}',
            '.note-editable ol{list-style-type:decimal!important;padding-left:1.5em!important;margin:.75rem 0 1rem!important;}',
            '.note-editable li{display:list-item!important;margin:.25rem 0!important;}',
            '.note-editable pre{background:#1a1a1a;color:#FEF9E1;padding:1rem;overflow:auto;font-family:JetBrains Mono,monospace;}',
            '.note-editable table{border-collapse:collapse;width:100%;margin:1rem 0;}',
            '.note-editable th,.note-editable td{border:1px solid var(--line-color);padding:.5rem;}',
            '.fezadan-import-error{border:2px dashed var(--text-accent);padding:1rem;background:rgba(163,29,29,.08);color:var(--text-accent);font-family:JetBrains Mono,monospace;font-size:.78rem;}',
            '.fezadan-import-error strong{display:block;font-family:Syne,sans-serif;text-transform:uppercase;margin-bottom:.35rem;}'
        ].join('\n');
        document.head.appendChild(style);
    }

    function AdminEditor(config) {
        this.config = Object.assign({
            formSelector: '',
            editorSelector: '#summernote',
            titleSelector: 'input[name="title"]',
            descSelector: 'textarea[name="desc"]',
            refsSelector: 'textarea[name="refs"]',
            categorySelector: 'input[name="categories[]"]',
            authorSelector: 'select[name="author_id"]',
            coverInputSelector: 'input[name="cover_image"]',
            coverPreviewSelector: '',
            coverPlaceholderSelector: '',
            coverPreviewStateSelector: '',
            coverFileNameSelector: '',
            existingCoverUrl: '',
            wordInputSelector: '',
            draftKey: 'fezadan_editor_draft',
            csrfToken: '',
            uploadUrl: '/yonetim/upload-content-image',
            height: 500,
            placeholder: 'Yazmaya başlayın...',
            clearButtonSelector: '',
            statusInputSelector: '#statusInput',
            draftButtonSelector: '#draftBtn',
            publishButtonSelector: '#publishBtn',
            loadingSelector: '#loadingOverlay',
            templateSelectSelector: '#templateSelect',
            deferWordImages: false
        }, config || {});

        this.form = qs(this.config.formSelector);
        this.editor = $(this.config.editorSelector);
        this.coverDataUrl = '';
        this.hasCoverError = false;
        this.importFailures = [];
        this.deferredWordTransfer = null;
        this.isDirty = false;
        this.pendingImageUploads = 0;
        this.updateTimer = null;
        this.backupTimer = null;
    }

    AdminEditor.prototype.init = function () {
        if (!this.form || !this.editor.length) return;
        injectRuntimeStyles();
        initThemeToggle();
        this.statusBox = this.ensureStatusBox();
        this.qualityPanel = this.ensureQualityPanel();
        this.initSummernote();
        var self = this;
        qsa('.note-editable img', this.form).forEach(function (image) { self.preserveImageDimensions(image); });
        this.form.addEventListener('load', function (event) {
            var image = /** @type {HTMLImageElement} */ (event.target);
            if (image.tagName === 'IMG' && image.closest('.note-editable')) self.preserveImageDimensions(image);
        }, true);
        this.bindWorkspace();
        this.bindShortcuts();
        this.bindTemplates();
        this.bindWordImport();
        this.bindScientificTools();
        this.bindCoverPreview();
        this.bindDraftActions();
        this.bindSlugGeneration();
        this.bindAnalysis();
        this.bindSubmit();
        this.startHeartbeat();
        this.setSaveState('Henüz değişiklik yok');
        this.restoreDraftIfNeeded();
        this.updatePreview();
        this.updateQuality();
        this.bindBeforeUnload();
        window.updatePreview = this.updatePreview.bind(this);
    };

    AdminEditor.prototype.bindSlugGeneration = function () {
        var title = qs(this.config.titleSelector, this.form);
        var slug = qs('#manualSlug', this.form);
        var button = qs('#generateSlugBtn', this.form);
        if (!title || !slug) return;

        slug.dataset.manual = slug.value.trim() ? '1' : '';
        var updatingSlugFromCode = false;

        title.addEventListener('input', function () {
            if (!slug.dataset.manual) {
                updatingSlugFromCode = true;
                slug.value = slugify(title.value);
                slug.dispatchEvent(new Event('input', { bubbles: true }));
                updatingSlugFromCode = false;
            }
        });

        slug.addEventListener('input', function () {
            if (updatingSlugFromCode) return;
            slug.dataset.manual = slug.value.trim() ? '1' : '';
        });

        if (button) {
            button.addEventListener('click', function () {
                updatingSlugFromCode = true;
                slug.value = slugify(title.value);
                slug.dataset.manual = '';
                slug.dispatchEvent(new Event('input', { bubbles: true }));
                updatingSlugFromCode = false;
            });
        }
    };

    AdminEditor.prototype.bindWorkspace = function () {
        var self = this;
        var focusButton = document.getElementById('editorFocusToggle');
        var settings = document.getElementById('editorSettings');
        this.setFocusMode = function (enabled) {
            if (!focusButton || !settings) return;
            settings.hidden = enabled;
            document.body.classList.toggle('editor-focus', enabled);
            focusButton.setAttribute('aria-pressed', String(enabled));
            focusButton.textContent = enabled ? 'Yayın bilgilerini göster' : 'Odaklan';
        };
        if (focusButton && settings) {
            focusButton.addEventListener('click', function () {
                self.setFocusMode(focusButton.getAttribute('aria-pressed') !== 'true');
            });
            document.addEventListener('keydown', function (event) {
                if (event.key === 'Escape' && !event.defaultPrevented && !document.querySelector('dialog[open], .note-modal.open, .note-editor.fullscreen') && settings.hidden) {
                    self.setFocusMode(false);
                    focusButton.focus();
                }
            });
        }
        var settingsJump = document.getElementById('editorSettingsJump');
        if (settingsJump && settings) {
            settingsJump.addEventListener('click', function () {
                if (settings.hidden) self.setFocusMode(false);
            });
        }
        // Native validation must be able to reach required fields inside a closed section.
        this.form.addEventListener('invalid', function (event) {
            self.revealField(/** @type {HTMLElement} */ (event.target));
        }, true);
        if (!document.body.classList.contains('editor-workspace')) return;
        var header = this.form.querySelector('.editor-header');
        if (header) {
            // The sticky header can wrap when the viewport, fonts or focus mode change.
            var updateHeaderHeight = function () {
                self.form.style.setProperty('--editor-header-height', header.getBoundingClientRect().height + 'px');
            };
            updateHeaderHeight();
            new ResizeObserver(updateHeaderHeight).observe(header);
        }
        var toolbar = this.form.querySelector('.note-toolbar');
        var advanced = qsa('[class*="note-advanced-"]', toolbar);
        if (toolbar && advanced.length) {
            var more = document.createElement('button');
            more.type = 'button';
            more.className = 'note-btn editor-toolbar-more';
            more.textContent = 'Diğer araçlar';
            more.setAttribute('aria-expanded', 'false');
            more.setAttribute('aria-controls', advanced.map(function (group, index) {
                // The edit form's hidden name="id" input shadows form.id in the DOM.
                group.id = self.form.getAttribute('id') + '-advanced-' + index;
                group.hidden = true;
                return group.id;
            }).join(' '));
            more.addEventListener('mousedown', function (event) {
                event.preventDefault();
                self.editor.summernote('saveRange');
            });
            more.addEventListener('click', function () {
                var open = more.getAttribute('aria-expanded') !== 'true';
                advanced.forEach(function (group) { group.hidden = !open; });
                more.setAttribute('aria-expanded', String(open));
            });
            toolbar.appendChild(more);
            qsa('button[tabindex="-1"]', toolbar).forEach(function (button) {
                if (!button.closest('.note-dropdown-menu')) button.setAttribute('tabindex', '0');
            });
        }
        var editable = this.form.querySelector('.note-editable');
        if (editable) {
            editable.setAttribute('role', 'textbox');
            editable.setAttribute('aria-label', 'Makale metni');
            editable.setAttribute('aria-multiline', 'true');
        }
    };

    AdminEditor.prototype.revealField = function (field) {
        if (!field) return;
        if (this.setFocusMode && field.closest('#editorSettings')) this.setFocusMode(false);
        for (var parent = field.parentElement; parent; parent = parent.parentElement) {
            if (parent instanceof HTMLDetailsElement) parent.open = true;
        }
    };

    AdminEditor.prototype.bindBeforeUnload = function () {
        var self = this;
        window.addEventListener('beforeunload', function (e) {
            if (self.isDirty) {
                e.preventDefault();
                e.returnValue = '';
            }
        });
        window.addEventListener('pagehide', function () {
            if (self.isDirty) self.saveDraft();
        });
    };

    AdminEditor.prototype.setSaveState = function (message) {
        var state = document.getElementById('editorSaveState');
        if (state) state.textContent = message;
    };

    AdminEditor.prototype.scheduleDraftBackup = function () {
        var self = this;
        this.setSaveState('Kaydedilmemiş değişiklikler');
        clearTimeout(this.backupTimer);
        this.backupTimer = setTimeout(function () { self.saveDraft(); }, 1200);
    };

    AdminEditor.prototype.ensureStatusBox = function () {
        var box = qs('#editorStatusBox', this.form);
        if (box) return box;
        box = document.createElement('div');
        box.id = 'editorStatusBox';
        box.className = 'editor-status-box';
        var editorEl = qs(this.config.editorSelector);
        if (editorEl && editorEl.parentNode) {
            editorEl.parentNode.insertBefore(box, editorEl);
        } else {
            this.form.insertBefore(box, this.form.firstChild);
        }
        return box;
    };

    AdminEditor.prototype.ensureQualityPanel = function () {
        var panel = qs('#qualityPanel', this.form) || document.getElementById('qualityPanel');
        if (!panel) {
            panel = document.createElement('section');
            panel.id = 'qualityPanel';
            panel.className = 'quality-panel';
            panel.innerHTML = '<div class="flex items-center justify-between gap-3"><h4>Yazi Kalitesi</h4><span id="qualityState" class="quality-state warning">Kontrol</span></div><div id="qualityMeta" class="font-mono text-[10px] opacity-70 mt-2"></div><div id="qualityList" class="quality-list"></div>';
            var draftBtn = qs(this.config.draftButtonSelector, this.form);
            if (draftBtn && draftBtn.parentNode) {
                draftBtn.parentNode.insertBefore(panel, draftBtn);
            } else {
                this.form.appendChild(panel);
            }
        }
        return panel;
    };

    AdminEditor.prototype.showStatus = function (message, kind) {
        if (!this.statusBox) return;
        this.statusBox.textContent = message;
        this.statusBox.dataset.kind = kind || 'info';
        this.statusBox.style.display = message ? 'block' : 'none';
        if (message && window.FezadanToast && ['error', 'warning', 'success'].indexOf(kind || '') !== -1) {
            window.FezadanToast[kind](message);
        }
    };

    AdminEditor.prototype.getCode = function () {
        return this.editor.summernote('code') || '';
    };

    AdminEditor.prototype.setCode = function (html) {
        this.editor.summernote('code', html || '');
    };

    AdminEditor.prototype.isEmptyHtml = function (html) {
        return !html || html.trim() === '' || html.trim() === '<p><br></p>';
    };

    AdminEditor.prototype.insertHtmlSafely = function (html) {
        var current = this.getCode();
        var next = this.isEmptyHtml(current) ? html : current + '<p><br></p>' + html;
        this.setCode(next);
        this.editor.summernote('focus');
        this.updatePreview();
        this.updateQuality();
    };

    AdminEditor.prototype.initSummernote = function () {
        var self = this;
        this.editor.summernote({
            placeholder: this.config.placeholder,
            tabsize: 2,
            height: this.config.height,
            toolbar: DEFAULT_TOOLBAR,
            followingToolbar: false,
            disableDragAndDrop: false,
            callbacks: {
                onKeydown: function (e) {
                    if (e.keyCode === 9) {
                        e.preventDefault();
                        document.execCommand('insertText', false, '    ');
                    }
                    if (self.shortcutHandler) {
                        self.shortcutHandler(e);
                    }
                    if (e.key === ' ') {
                        var selection = window.getSelection();
                        if (selection.rangeCount) {
                            var range = selection.getRangeAt(0);
                            var node = range.startContainer;
                            if (node.nodeType === Node.TEXT_NODE) {
                                var text = node.textContent;
                                var cursorOffset = range.startOffset;
                                var textBeforeCursor = text.substring(0, cursorOffset);
                                if (textBeforeCursor === '##') {
                                    e.preventDefault();
                                    node.textContent = text.substring(cursorOffset);
                                    self.editor.summernote('formatH2');
                                    return;
                                }
                                if (textBeforeCursor === '###') {
                                    e.preventDefault();
                                    node.textContent = text.substring(cursorOffset);
                                    self.editor.summernote('formatH3');
                                    return;
                                }
                                if (textBeforeCursor === '>') {
                                    e.preventDefault();
                                    node.textContent = text.substring(cursorOffset);
                                    self.editor.summernote('formatBlockquote');
                                    return;
                                }
                            }
                        }
                    }
                },
                onKeyup: function (e) {
                    var selection = window.getSelection();
                    if (selection.rangeCount) {
                        var range = selection.getRangeAt(0);
                        var node = range.startContainer;
                        if (node.nodeType === Node.TEXT_NODE) {
                            var text = node.textContent;
                            var boldRegex = /\*\*([^\*]+)\*\*/;
                            var match = text.match(boldRegex);
                            if (match) {
                                var matchIndex = match.index;
                                var matchText = match[0];
                                var innerText = match[1];
                                var beforeText = text.substring(0, matchIndex);
                                var afterText = text.substring(matchIndex + matchText.length);
                                var beforeNode = document.createTextNode(beforeText);
                                var strongNode = document.createElement('strong');
                                strongNode.textContent = innerText;
                                var afterNode = document.createTextNode(afterText);
                                var parentNode = node.parentNode;
                                if (parentNode) {
                                    parentNode.insertBefore(beforeNode, node);
                                    parentNode.insertBefore(strongNode, node);
                                    parentNode.insertBefore(afterNode, node);
                                    parentNode.removeChild(node);
                                    var newRange = document.createRange();
                                    newRange.setStart(afterNode, 0);
                                    newRange.collapse(true);
                                    selection.removeAllRanges();
                                    selection.addRange(newRange);
                                }
                            }
                        }
                    }
                    self.scheduleUpdates();
                },
                onImageUpload: function (files) {
                    return self.uploadImages(Array.prototype.slice.call(files || []));
                },
                onChange: function () {
                    self.scheduleUpdates();
                },
                onPaste: function (e) {
                    var clipboardData = (e.originalEvent || e).clipboardData || window.clipboardData;
                    if (clipboardData) {
                        var html = clipboardData.getData('text/html');
                        var text = clipboardData.getData('text/plain');
                        if (html) {
                            e.preventDefault();
                            var clean = self.stripFontStyles(html);
                            document.execCommand('insertHTML', false, clean);
                        } else if (text) {
                            e.preventDefault();
                            document.execCommand('insertText', false, text);
                        }
                    }
                    self.scheduleUpdates();
                }
            }
        });
    };

    AdminEditor.prototype.normalizeExistingImages = function () {
        var self = this;
        $('.note-editable').find('img').each(function () {
            self.preserveImageDimensions(this);
            var img = $(this);
            if (img.closest('figure').length) return;
            img.wrap('<figure></figure>');
            img.after('<figcaption contenteditable="true" data-placeholder="Görsel açıklaması (isteğe bağlı)..."></figcaption>');
        });
    };

    // Persist the intrinsic ratio in saved HTML so images reserve space before loading.
    // Old articles acquire it when opened and saved; no server-side URL fetching is needed.
    AdminEditor.prototype.preserveImageDimensions = function (image) {
        function recordSize() {
            if (image.naturalWidth > 0 && image.naturalHeight > 0
                && !image.hasAttribute('width') && !image.hasAttribute('height')) {
                image.setAttribute('width', String(image.naturalWidth));
                image.setAttribute('height', String(image.naturalHeight));
            }
        }
        if (image.complete) recordSize();
        else image.addEventListener('load', recordSize, { once: true });
    };

    AdminEditor.prototype.uploadImages = function (files) {
        var self = this;
        if (!files.length) return Promise.resolve();
        var editable = this.form.querySelector('.note-editable');
        if (!editable || this.editor.summernote('codeview.isActivated')) return Promise.resolve();
        var hadWarning = false;
        var failures = 0;
        this.pendingImageUploads += files.length;
        var codeviewButton = /** @type {HTMLButtonElement} */ (this.form.querySelector('.btn-codeview'));
        // Code view keeps a separate HTML snapshot; switching mid-upload would lose results.
        if (codeviewButton) codeviewButton.disabled = true;
        this.isDirty = true;
        // Anchor each upload now, before asynchronous work or further typing moves the cursor.
        var uploads = files.map(function (file) {
            var placeholder = document.createElement('span');
            placeholder.className = 'editor-image-upload';
            placeholder.contentEditable = 'false';
            placeholder.setAttribute('data-import-error', '1');
            placeholder.setAttribute('role', 'status');
            placeholder.textContent = (file.name || 'Görsel') + ' yükleniyor…';
            self.editor.summernote('insertNode', placeholder);
            return { file: file, placeholder: placeholder };
        });
        this.showStatus('Görsel yükleniyor...', 'info');
        return uploads.reduce(function (chain, upload) {
            return chain.then(function () {
                return self.uploadImage(upload.file, { source: 'editor' }).then(function (payload) {
                    // Removing an upload marker while it loads must not resurrect the image.
                    if (!editable.contains(upload.placeholder)) return;
                    var altText = prompt('Görsel açıklaması girin (alt text):') || '';
                    var image = document.createElement('img');
                    image.src = payload.url;
                    self.preserveImageDimensions(image);
                    image.alt = altText;
                    image.style.maxWidth = '100%';
                    image.style.height = 'auto';
                    self.editor.summernote('editor.beforeCommand');
                    upload.placeholder.replaceWith(image);
                    self.editor.summernote('editor.afterCommand');
                    if (payload.visibility_warning) {
                        hadWarning = true;
                        self.showStatus(payload.visibility_warning + ' (Hata kodu: ' + payload.request_id + ')', 'error');
                    }
                }).catch(function (err) {
                    failures++;
                    if (editable.contains(upload.placeholder)) {
                        self.editor.summernote('editor.beforeCommand');
                        upload.placeholder.className = 'fezadan-import-error';
                        upload.placeholder.textContent = (upload.file.name || 'Görsel') + ' yüklenemedi: ' + err.message + ' Bu uyarıyı silip görseli yeniden ekleyin.';
                        self.editor.summernote('editor.afterCommand');
                    }
                }).finally(function () {
                    self.pendingImageUploads--;
                    if (codeviewButton && self.pendingImageUploads === 0) codeviewButton.disabled = false;
                    self.updatePreview();
                    self.updateQuality();
                });
            });
        }, Promise.resolve()).then(function () {
            if (failures) self.showStatus(failures + ' görsel yüklenemedi. Metindeki hata açıklamalarını kontrol edin; diğer görseller işlendi.', 'error');
            else if (!hadWarning) self.showStatus('Görsel yüklendi.', 'success');
        });
    };

    AdminEditor.prototype.prepareImageForUpload = function (file) {
        if (!file || !/^image\/(jpeg|png|webp)$/.test(file.type || '') || file.size < 1024 * 1024) {
            return Promise.resolve(file);
        }

        return new Promise(function (resolve) {
            var img = new Image();
            var objectUrl = URL.createObjectURL(file);
            img.onload = function () {
                URL.revokeObjectURL(objectUrl);
                var maxSide = 1800;
                var scale = Math.min(1, maxSide / Math.max(img.width, img.height));
                if (scale >= 1 && file.size < 1600 * 1024) {
                    resolve(file);
                    return;
                }

                var canvas = document.createElement('canvas');
                canvas.width = Math.max(1, Math.round(img.width * scale));
                canvas.height = Math.max(1, Math.round(img.height * scale));
                var ctx = canvas.getContext('2d');
                if (!ctx || !canvas.toBlob) {
                    resolve(file);
                    return;
                }
                ctx.drawImage(img, 0, 0, canvas.width, canvas.height);
                canvas.toBlob(function (blob) {
                    if (!blob || blob.size >= file.size) {
                        resolve(file);
                        return;
                    }
                    // Browsers without a WebP encoder may return PNG despite the requested type.
                    var extension = { 'image/webp': '.webp', 'image/png': '.png', 'image/jpeg': '.jpg' }[blob.type];
                    if (!extension) {
                        resolve(file);
                        return;
                    }
                    var baseName = (file.name || 'image').replace(/\.[^.]+$/, '');
                    resolve(new File([blob], baseName + extension, { type: blob.type }));
                }, 'image/webp', 0.86);
            };
            img.onerror = function () {
                URL.revokeObjectURL(objectUrl);
                resolve(file);
            };
            img.src = objectUrl;
        });
    };

    AdminEditor.prototype.uploadImage = function (file, options) {
        var self = this;
        options = options || {};
        var original = file;
        return this.prepareImageForUpload(file).then(function (preparedFile) {
            var data = new FormData();
            data.append('file', preparedFile);
            data.append('_csrf', self.config.csrfToken);
            data.append('upload_source', options.source || 'editor');
            data.append('client_name', original.name || preparedFile.name || '');
            data.append('client_size', original.size || 0);
            data.append('client_type', original.type || preparedFile.type || '');
            if (options.importId) data.append('import_id', options.importId);
            if (typeof options.imageIndex !== 'undefined') data.append('image_index', String(options.imageIndex));
            var titleInput = qs(self.config.titleSelector, self.form);
            if (titleInput && titleInput.value.trim() !== '') {
                data.append('slug', titleInput.value.trim());
            }

            var request = window.FezadanFetch
                ? window.FezadanFetch(self.config.uploadUrl, { method: 'POST', body: data }, { toastOnError: false })
                : fetch(self.config.uploadUrl, { method: 'POST', body: data, credentials: 'same-origin' }).then(function (res) {
                    return res.text().then(function (text) {
                        var payload = safeJsonParse(text) || {};
                        if (!res.ok) {
                            var httpErr = new Error(payload.error || ('Sunucu hatası: ' + res.status));
                            httpErr.requestId = payload.request_id || '';
                            httpErr.payload = payload;
                            throw httpErr;
                        }
                        return payload;
                    });
                });

            return request.then(function (payload) {
                if (!payload || !payload.success || !payload.url) {
                    var suffix = payload && payload.request_id ? ' (Hata kodu: ' + payload.request_id + ')' : '';
                    var err = new Error(((payload && payload.error) || 'Yükleme başarısız.') + suffix);
                    err.requestId = payload && payload.request_id ? payload.request_id : '';
                    err.payload = payload;
                    throw err;
                }
                return payload;
            });
        });
    };

    AdminEditor.prototype.figureHtml = function (url, altText) {
        var alt = altText ? ' alt="' + String(altText).replace(/"/g, '&quot;') + '"' : '';
        return '<img src="' + String(url).replace(/"/g, '&quot;') + '"' + alt + ' style="max-width:100%;height:auto;"><p><br></p>';
    };

    AdminEditor.prototype.dataUrlToFile = function (dataUrl, filename) {
        var parts = dataUrl.split(',');
        var mime = (parts[0].match(/:(.*?);/) || [])[1] || 'image/png';
        var extension = { 'image/jpeg': '.jpg', 'image/png': '.png', 'image/webp': '.webp', 'image/gif': '.gif' }[mime];
        if (extension) filename = filename.replace(/\.[^.]+$/, '') + extension;
        var bytes = atob(parts[1] || '');
        var n = bytes.length;
        var data = new Uint8Array(n);
        while (n--) data[n] = bytes.charCodeAt(n);
        return new File([data], filename, { type: mime });
    };

    AdminEditor.prototype.runLimited = function (items, limit, worker) {
        var results = new Array(items.length);
        var next = 0;
        var active = 0;
        return new Promise(function (resolve) {
            function launch() {
                if (next >= items.length && active === 0) {
                    resolve(results);
                    return;
                }
                while (active < limit && next < items.length) {
                    (function (index) {
                        active += 1;
                        Promise.resolve(worker(items[index], index)).then(function (value) {
                            results[index] = { ok: true, value: value };
                        }).catch(function (err) {
                            results[index] = { ok: false, error: err };
                        }).finally(function () {
                            active -= 1;
                            launch();
                        });
                    })(next++);
                }
            }
            launch();
        });
    };

    AdminEditor.prototype.importErrorHtml = function (err, index, importId) {
        var requestId = err && err.requestId ? err.requestId : '';
        var message = err && err.message ? err.message : 'Bilinmeyen yükleme hatası.';
        return '<figure class="fezadan-import-error" data-import-error="1" data-import-id="' + escapeHtml(importId) + '">' +
            '<strong>[GÖRSEL YÜKLENEMEDİ: ' + escapeHtml(message) + ']</strong>' +
            '<div>Word import görseli #' + (index + 1) + '</div>' +
            (requestId ? '<div>Hata kodu: ' + escapeHtml(requestId) + '</div>' : '') +
            '<figcaption>Bu bloğu silip görseli yeniden yükleyebilir veya taslak olarak kaydedebilirsiniz.</figcaption>' +
        '</figure>';
    };

    AdminEditor.prototype.replaceImportImageWithError = function (doc, img, err, index, importId) {
        var wrapper = doc.createElement('div');
        wrapper.innerHTML = this.importErrorHtml(err, index, importId);
        var replacement = wrapper.firstChild;
        if (img.parentNode && replacement) {
            img.parentNode.replaceChild(replacement, img);
        }
        this.importFailures.push({
            importId: importId,
            index: index,
            requestId: err && err.requestId ? err.requestId : '',
            message: err && err.message ? err.message : 'Bilinmeyen yükleme hatası.'
        });
        if (window.FezadanToast) {
            window.FezadanToast.error('Bir görsel yüklenemedi. Detay editörde işaretlendi.');
        }
    };

    AdminEditor.prototype.ensureDeferredWordImageInput = function () {
        var input = /** @type {HTMLInputElement} */ (document.getElementById('deferredWordImages'));
        if (!input) {
            input = document.createElement('input');
            input.type = 'file';
            input.id = 'deferredWordImages';
            input.name = 'word_images[]';
            input.multiple = true;
            input.hidden = true;
            this.form.appendChild(input);
        }
        if (!this.deferredWordTransfer && window.DataTransfer) {
            try {
                this.deferredWordTransfer = new DataTransfer();
            } catch (e) {
                this.deferredWordTransfer = null;
            }
        }
        return input;
    };

    AdminEditor.prototype.deferWordImage = function (doc, img, file, index, importId) {
        var input = this.ensureDeferredWordImageInput();
        if (!this.deferredWordTransfer) {
            return false;
        }

        var deferredIndex = this.deferredWordTransfer.items.length;
        this.deferredWordTransfer.items.add(file);
        input.files = this.deferredWordTransfer.files;

        var figure = doc.createElement('figure');
        figure.setAttribute('data-word-image-pending', '1');
        figure.setAttribute('data-word-image-index', String(deferredIndex));
        figure.setAttribute('data-import-id', importId);
        figure.setAttribute('data-word-image-name', file.name || ('word-image-' + index));
        figure.className = 'fezadan-import-error';
        figure.innerHTML = '<strong>[WORD GÖRSELİ KAYIT SIRASINDA YÜKLENECEK]</strong>' +
            '<div>' + escapeHtml(file.name || ('Word görseli #' + (index + 1))) + '</div>' +
            '<figcaption>Taslak veya yayın kaydında CDN/R2 yüklemesi yapılacak. Kaydetmeden çıkarsanız görsel yüklenmez.</figcaption>';

        if (img.parentNode) {
            img.parentNode.replaceChild(figure, img);
        }
        return true;
    };

    AdminEditor.prototype.hasUnresolvedImportErrors = function () {
        var current = this.getCode();
        return current.indexOf('data-import-error="1"') !== -1 || current.indexOf("data-import-error='1'") !== -1;
    };

    AdminEditor.prototype.loadMammoth = function () {
        return new Promise(function (resolve, reject) {
            if (window.mammoth) {
                resolve(undefined);
                return;
            }
            var script = document.createElement('script');
            script.src = '/assets/js/mammoth.browser.min.js';
            var nonce = document.querySelector('script[nonce]');
            if (nonce) script.setAttribute('nonce', nonce.getAttribute('nonce'));
            script.onload = function () { resolve(undefined); };
            script.onerror = function () { reject(new Error('Word kütüphanesi yüklenemedi.')); };
            document.head.appendChild(script);
        });
    };

    AdminEditor.prototype.bindScientificTools = function () {
        var self = this;
        var trigger = /** @type {HTMLButtonElement} */ (document.getElementById('scientificImportTrigger'));
        var input = /** @type {HTMLInputElement} */ (document.getElementById('scientificInput'));
        var cancel = /** @type {HTMLButtonElement} */ (document.getElementById('scientificImportCancel'));
        var dialog = /** @type {HTMLDialogElement} */ (document.getElementById('formulaDialog'));
        var source = /** @type {HTMLTextAreaElement} */ (document.getElementById('formulaSource'));
        var display = /** @type {HTMLInputElement} */ (document.getElementById('formulaDisplay'));
        var preview = document.getElementById('formulaPreview');
        var error = document.getElementById('formulaError');
        var save = /** @type {HTMLButtonElement} */ (document.getElementById('formulaSave'));
        var remove = /** @type {HTMLButtonElement} */ (document.getElementById('formulaDelete'));
        var editable = /** @type {HTMLElement} */ (this.editor.next('.note-editor').find('.note-editable')[0]);
        if (!trigger || !input || !dialog || !editable) return;
        var editing = null;

        function formulaNode() {
            var node = document.createElement('span');
            node.className = 'fezadan-math';
            node.dataset.mathDisplay = display.checked ? '1' : '0';
            node.contentEditable = 'false';
            node.tabIndex = 0;
            node.textContent = source.value.trim();
            return node;
        }
        function formulaPreview() {
            preview.replaceChildren(formulaNode());
            error.textContent = '';
            if (window.FezadanMath && source.value.trim()) window.FezadanMath.render(preview).then(function () {
                if (preview.querySelector('.fezadan-math-error')) error.textContent = 'Formül gösterilemedi. TeX kaynağını kontrol edin.';
            });
        }
        function openFormula(node) {
            if (self.editor.summernote('codeview.isActivated')) {
                self.showStatus('Formül eklemek için kod görünümünü kapatın.', 'warning');
                return;
            }
            editing = node;
            self.editor.summernote('saveRange');
            source.value = node ? node.textContent : '';
            display.checked = node ? node.dataset.mathDisplay === '1' : false;
            save.textContent = node ? 'Güncelle' : 'Ekle';
            remove.hidden = !node;
            formulaPreview();
            dialog.showModal();
            source.focus();
        }
        document.getElementById('formulaTrigger').addEventListener('click', function () { openFormula(null); });
        editable.addEventListener('click', function (event) {
            var node = /** @type {Element} */ (event.target).closest('.fezadan-math');
            if (node) { event.preventDefault(); openFormula(node); }
        });
        editable.addEventListener('keydown', function (event) {
            var node = /** @type {Element} */ (event.target).closest('.fezadan-math');
            if (node && (event.key === 'Enter' || event.key === ' ')) { event.preventDefault(); openFormula(node); }
        });
        // Keep restored/imported formulas keyboard-accessible without saving renderer DOM.
        function prepareFormulaNodes() {
            editable.querySelectorAll('.fezadan-math').forEach(function (node) {
                node.setAttribute('contenteditable', 'false');
                node.setAttribute('tabindex', '0');
                node.setAttribute('title', 'Formülü düzenle');
                node.setAttribute('role', 'button');
                node.setAttribute('aria-label', 'Formülü düzenle: ' + node.textContent.trim());
            });
        }
        var observer = new MutationObserver(prepareFormulaNodes);
        observer.observe(editable, { childList: true, subtree: true });
        prepareFormulaNodes();
        var previewTimer;
        source.addEventListener('input', function () { clearTimeout(previewTimer); previewTimer = setTimeout(formulaPreview, 250); });
        display.addEventListener('change', formulaPreview);
        document.getElementById('formulaCancel').addEventListener('click', function () { dialog.close(); });
        // Buttons inside the article form must never submit it from this modal.
        source.addEventListener('keydown', function (event) { event.stopPropagation(); });
        save.addEventListener('click', function () {
            var text = source.value.trim();
            if (!text || new TextEncoder().encode(text).length > 12000) {
                error.textContent = 'Formül boş olamaz ve 12 KB sınırını aşamaz.';
                return;
            }
            if (!editing && editable.querySelectorAll('.fezadan-math').length >= 1000) {
                error.textContent = 'Yazı başına en fazla 1000 formül eklenebilir.';
                return;
            }
            var node = formulaNode();
            self.editor.summernote('restoreRange');
            if (editing && editable.contains(editing)) {
                self.editor.summernote('editor.beforeCommand');
                editing.replaceWith(node);
                self.editor.summernote('editor.afterCommand');
            } else {
                self.editor.summernote('insertNode', node);
            }
            self.isDirty = true;
            dialog.close();
            self.updatePreview();
            self.updateQuality();
        });
        remove.addEventListener('click', function () {
            if (editing && editable.contains(editing)) {
                self.editor.summernote('editor.beforeCommand');
                editing.remove();
                self.editor.summernote('editor.afterCommand');
                self.isDirty = true;
            }
            dialog.close();
            self.updatePreview();
            self.updateQuality();
        });

        trigger.addEventListener('click', function () { input.click(); });
        cancel.addEventListener('click', function () { if (self.cancelScientificImport) self.cancelScientificImport(); });
        input.addEventListener('change', async function () {
            var file = input.files && input.files[0];
            input.value = '';
            if (!file || self.scientificImportPending) return;
            if (!/\.(tex|md|markdown)$/i.test(file.name) || file.size > 524288) {
                self.showStatus('UTF-8 .tex veya .md dosyası seçin (en fazla 512 KB).', 'error');
                return;
            }
            self.scientificImportPending = true;
            trigger.disabled = true;
            cancel.hidden = false;
            var worker;
            var timer;
            var controller = new AbortController();
            try {
                var text = new TextDecoder('utf-8', { fatal: true }).decode(await file.arrayBuffer());
                var converted = await new Promise(function (resolve, reject) {
                    worker = new Worker(input.dataset.workerUrl, { type: 'module' });
                    self.cancelScientificImport = function () { controller.abort(); reject(new Error('Aktarım iptal edildi; mevcut içerik değişmedi.')); };
                    timer = setTimeout(function () { controller.abort(); reject(new Error('Aktarım zaman aşımına uğradı; daha küçük bir belge deneyin.')); }, 120000);
                    worker.onmessage = function (event) {
                        if (event.data.stage) self.showStatus(event.data.stage, 'info');
                        else if (event.data.error) reject(new Error(event.data.error));
                        else resolve(event.data);
                    };
                    worker.onerror = function () { reject(new Error('Dönüştürücü çalıştırılamadı. Tarayıcı desteğini ve yerel dosyaları kontrol edin.')); };
                    worker.postMessage({ source: text, format: /\.tex$/i.test(file.name) ? 'latex' : 'markdown' });
                });
                self.showStatus('Aktarılan içerik sunucuda denetleniyor.', 'info');
                var data = new FormData();
                data.append('_csrf', self.config.csrfToken);
                data.append('html', converted.html);
                var response = await fetch('/yonetim/prepare-scientific-import', { method: 'POST', body: data, credentials: 'same-origin', signal: controller.signal });
                var result = await response.json();
                if (!response.ok || !result.success) throw new Error(result.error || 'İçerik denetlenemedi.');
                if (controller.signal.aborted) throw new Error('Aktarım iptal edildi; mevcut içerik değişmedi.');
                if (!result.html.trim()) throw new Error('Aktarılabilir içerik bulunamadı.');
                self.insertHtmlSafely(result.html);
                self.isDirty = true;
                var warnings = Array.from(new Set((converted.warnings || []).concat(result.warnings || [])));
                self.showStatus('İçerik aktarıldı (' + result.formula_count + ' formül). Önizlemeyi kontrol edin.' + (warnings.length ? '\n' + warnings.join('\n') : ''), warnings.length ? 'warning' : 'success');
            } catch (error) {
                self.showStatus(controller.signal.aborted ? 'Aktarım durduruldu; mevcut içerik değişmedi.' : 'Aktarım başarısız: ' + error.message, 'error');
            } finally {
                if (worker) worker.terminate();
                clearTimeout(timer);
                self.cancelScientificImport = null;
                self.scientificImportPending = false;
                trigger.disabled = false;
                cancel.hidden = true;
            }
        });
    };

    AdminEditor.prototype.bindShortcuts = function () {
        var self = this;
        function handleShortcut(e) {
            if (e.defaultPrevented) return;
            if ((e.ctrlKey || e.metaKey) && e.key === 's') {
                e.preventDefault();
                var saveButton = qs('[data-shortcut-save]', self.form) || qs(self.config.draftButtonSelector, self.form);
                if (saveButton) {
                    saveButton.click();
                } else {
                    self.saveDraft();
                }
            }
            if ((e.ctrlKey || e.metaKey) && e.shiftKey && (e.key === 'P' || e.key === 'p')) {
                e.preventDefault();
                var previewPanel = /** @type {HTMLDialogElement} */ (document.getElementById('previewPanel'));
                var isPreviewVisible = previewPanel && previewPanel.open;
                if (window.switchTab) {
                    window.switchTab(isPreviewVisible ? 'write' : 'preview');
                }
            }
        }
        document.addEventListener('keydown', handleShortcut);
        self.shortcutHandler = handleShortcut;
    };

    AdminEditor.prototype.bindTemplates = function () {
        var self = this;
        var select = qs(this.config.templateSelectSelector, this.form);
        if (!select) return;
        select.addEventListener('change', function () {
            var val = select.value;
            if (!val) return;
            if (confirm('Seçtiğiniz şablon içeriğe eklenecektir. Devam etmek istiyor musunuz?')) {
                var html = '';
                if (val === 'interview') {
                    html = '<h2>GİRİŞ</h2>' +
                           '<p>Röportaj yapılan kişi hakkında kısa bir tanıtım ve giriş paragrafı...</p>' +
                           '<p><strong>FEZADAN:</strong> İlk soru buraya yazılacak?</p>' +
                           '<p><strong>CEVAP:</strong> İlk cevap buraya yazılacak...</p>' +
                           '<p><strong>FEZADAN:</strong> İkinci soru buraya yazılacak?</p>' +
                           '<p><strong>CEVAP:</strong> İkinci cevap buraya yazılacak...</p>' +
                           '<h2>SONUÇ</h2>' +
                           '<p>Röportajın genel değerlendirmesi ve kapanış...</p>';
                } else if (val === 'review') {
                    html = '<h2>GENEL BAKIŞ</h2>' +
                           '<p>İncelenen konu/ürün/eser hakkında kısa bir özet ve giriş...</p>' +
                           '<blockquote><strong>İNCELEME PUANI:</strong> 9/10<br><strong>ÖNE ÇIKAN ÖZELLİK:</strong> Benzersiz tasarım ve yüksek performans.</blockquote>' +
                           '<h2>TASARIM VE DENEYİM</h2>' +
                           '<p>Tasarım detayları ve kullanım deneyimi...</p>' +
                           '<h2>ARTILAR VE EKSİLER</h2>' +
                           '<ul>' +
                               '<li><strong>(+) Artı:</strong> Detaylı açıklama...</li>' +
                               '<li><strong>(-) Eksi:</strong> Detaylı açıklama...</li>' +
                           '</ul>' +
                           '<h2>KARAR</h2>' +
                           '<p>Son karar ve tavsiyeler...</p>';
                }
                if (html) {
                    self.insertHtmlSafely(html);
                }
            }
            select.value = '';
        });
    };

    AdminEditor.prototype.stripFontStyles = function (html) {
        // HTML yapısını korur; sadece font-family ve font-size inline stillerini temizler
        return html.replace(/(<[^>]+)\sstyle\s*=\s*(["'])([^"']*?)\2/gi, function (match, tag, q, styles) {
            var cleaned = styles
                .replace(/font-family\s*:[^;]+;?/gi, '')
                .replace(/font-size\s*:[^;]+;?/gi, '')
                .replace(/^\s*;+|;+\s*$/g, '')
                .trim();
            return cleaned ? tag + ' style=' + q + cleaned + q : tag;
        });
    };

    AdminEditor.prototype.bindWordImport = function () {
        var self = this;
        var input = qs(this.config.wordInputSelector);
        if (!input) return;
        // CSP uyumlu: onclick inline kullanmak yerine wordImportTrigger butonunu JS ile bağla
        var trigger = document.getElementById('wordImportTrigger');
        if (trigger) {
            trigger.addEventListener('click', function () { input.click(); });
        }
        input.addEventListener('change', function () {
            if (!input.files || !input.files[0] || self.wordImportPending) return;
            self.wordImportPending = true;
            self.showLoading(true, 'Word kütüphanesi yükleniyor...');
            self.loadMammoth().then(function () {
                self.showLoading(true, 'Word dosyası işleniyor...');
                var reader = new FileReader();
                reader.onload = function (e) {
                    window.mammoth.convertToHtml({ arrayBuffer: e.target.result }).then(function (result) {
                        var parser = new DOMParser();
                        var doc = parser.parseFromString(result.value || '', 'text/html');
                        // Mammoth drops Word equations (OMML) with only a warning. Never publish an
                        // apparently complete article without them. Style warnings lose no content.
                        if ((result.messages || []).some(function (message) { return /oMath/i.test(message.message); })) {
                            var warningBlock = doc.createElement('p');
                            warningBlock.className = 'fezadan-import-error';
                            warningBlock.setAttribute('data-import-error', '1');
                            warningBlock.textContent = 'Word denklemleri aktarılamadı. Denklemleri "Formül ekle" ile yeniden ekleyip bu uyarıyı kaldırın.';
                            doc.body.appendChild(warningBlock);
                        }
                        var images = Array.prototype.slice.call(doc.getElementsByTagName('img')).filter(function (img) {
                            return img.src && img.src.indexOf('data:') === 0;
                        });
                        var importId = randomId('word');
                        self.importFailures = self.importFailures.filter(function (item) { return item.importId !== importId; });
                        if (self.config.deferWordImages && images.length) {
                            var deferred = 0;
                            images.forEach(function (img, index) {
                                var ext = img.src.indexOf('image/jpeg') >= 0 ? '.jpg' : '.png';
                                var file = self.dataUrlToFile(img.src, 'word-image-' + Date.now() + '-' + index + ext);
                                if (self.deferWordImage(doc, img, file, index, importId)) {
                                    deferred += 1;
                                } else {
                                    self.replaceImportImageWithError(doc, img, {
                                        message: 'Tarayıcı Word görselini kayıt için hazırlayamadı.'
                                    }, index, importId);
                                }
                            });
                            self.insertHtmlSafely(doc.body.innerHTML);
                            if (deferred === images.length) {
                                self.showStatus('Word içeriği aktarıldı; ' + deferred + ' görsel kaydettiğinizde yüklenecek.', 'success');
                            } else {
                                self.showStatus('Word içeriği aktarıldı; bazı görseller hazırlanamadı. Hata bloklarını silmeden yayınlayamazsınız, taslak kaydı serbesttir.', 'error');
                            }
                            return;
                        }
                        return self.runLimited(images, 2, function (img, index) {
                            var ext = img.src.indexOf('image/jpeg') >= 0 ? '.jpg' : '.png';
                            var file = self.dataUrlToFile(img.src, 'word-image-' + Date.now() + '-' + index + ext);
                            return self.uploadImage(file, {
                                source: 'word_import',
                                importId: importId,
                                imageIndex: index
                            }).then(function (payload) {
                                img.src = payload.url;
                                img.setAttribute('data-import-id', importId);
                                if (payload.visibility_warning) {
                                    img.setAttribute('data-visibility-warning', payload.visibility_warning);
                                }
                                return payload;
                            }).catch(function (err) {
                                self.replaceImportImageWithError(doc, img, err, index, importId);
                                throw err;
                            });
                        }).then(function (results) {
                            var failed = results.filter(function (item) { return item && !item.ok; }).length;
                            self.insertHtmlSafely(doc.body.innerHTML);
                            if (failed) {
                                self.showStatus('Word içeriği aktarıldı; ' + failed + ' görsel yüklenemedi. Hata bloklarını silmeden yayınlayamazsınız, taslak kaydı serbesttir.', 'error');
                            } else {
                                self.showStatus(images.length ? ('Word içeriği aktarıldı; ' + images.length + ' görsel yüklendi.') : 'Word içeriği aktarıldı.', 'success');
                            }
                        });
                    }).catch(function (err) {
                        self.showStatus('Word dosyası okunamadı: ' + err.message, 'error');
                    }).finally(function () {
                        self.wordImportPending = false;
                        self.showLoading(false);
                        input.value = '';
                    });
                };
                reader.onerror = function () {
                    self.wordImportPending = false;
                    self.showLoading(false);
                    self.showStatus('Word dosyası okunamadı.', 'error');
                    input.value = '';
                };
                reader.readAsArrayBuffer(input.files[0]);
            }).catch(function (err) {
                self.wordImportPending = false;
                self.showLoading(false);
                self.showStatus(err.message, 'error');
                input.value = '';
            });
        });
    };

    AdminEditor.prototype.bindCoverPreview = function () {
        var self = this;
        var input = qs(this.config.coverInputSelector, this.form);
        if (!input) return;
        input.addEventListener('change', function () {
            var file = input.files && input.files[0];
            self.hasCoverError = false;
            function rejectCover(message) {
                self.coverDataUrl = '';
                input.value = '';
                self.updateCoverPreview('', '');
                self.showStatus(message, 'error');
                self.updatePreview();
                self.updateQuality();
            }
            if (!file) {
                self.coverDataUrl = '';
                self.updateCoverPreview('');
                self.updatePreview();
                self.updateQuality();
                return;
            }
            if (!/^image\/(jpeg|png|webp)$/.test(file.type || '')) {
                rejectCover('Kapak görseli JPG, PNG veya WebP olmalı.');
                return;
            }
            if (file.size > 5 * 1024 * 1024) {
                rejectCover('Kapak görseli 5MB sınırını aşıyor.');
                return;
            }
            var reader = new FileReader();
            reader.onload = function (e) {
                self.coverDataUrl = e.target.result;
                self.updateCoverPreview(self.coverDataUrl, file.name);
                self.showStatus('', 'info');
                self.updatePreview();
                self.updateQuality();
            };
            reader.onerror = function () {
                self.hasCoverError = true;
                self.coverDataUrl = '';
                self.updateCoverPreview('', '');
                self.showStatus('Kapak görseli okunamadı. Farklı bir dosya seçin.', 'error');
                self.updatePreview();
                self.updateQuality();
            };
            reader.readAsDataURL(file);
        });
    };

    AdminEditor.prototype.updateCoverPreview = function (src, filename) {
        var img = qs(this.config.coverPreviewSelector);
        var placeholder = qs(this.config.coverPlaceholderSelector);
        var previewState = qs(this.config.coverPreviewStateSelector);
        var fileName = qs(this.config.coverFileNameSelector);
        if (img) {
            if (src) img.src = src;
            else img.removeAttribute('src');
        }
        if (placeholder) placeholder.classList.toggle('hidden', !!src);
        if (previewState) previewState.classList.toggle('hidden', !src);
        if (fileName) fileName.textContent = filename || '';
        var editThumb = /** @type {HTMLImageElement} */ (document.getElementById('currentCoverThumb'));
        if (editThumb && src) editThumb.src = src;
    };

    AdminEditor.prototype.bindDraftActions = function () {
        var status = qs(this.config.statusInputSelector, this.form);
        var draftBtn = qs(this.config.draftButtonSelector, this.form);
        var publishBtn = qs(this.config.publishButtonSelector, this.form);
        if (draftBtn && status) {
            draftBtn.addEventListener('click', function () {
                status.value = 'draft';
                draftBtn.form.requestSubmit();
            });
        }
        if (publishBtn && status) {
            publishBtn.addEventListener('click', function () {
                status.value = 'published';
            });
        }
    };

    AdminEditor.prototype.draftKeys = function () {
        return {
            recovery: this.config.draftKey + ':recovery',
            title: this.config.draftKey + ':title',
            content: this.config.draftKey + ':content',
            desc: this.config.draftKey + ':desc',
            refs: this.config.draftKey + ':refs'
        };
    };

    AdminEditor.prototype.saveDraft = function () {
        var keys = this.draftKeys();
        var title = qs(this.config.titleSelector, this.form);
        var desc = qs(this.config.descSelector, this.form);
        var refs = qs(this.config.refsSelector, this.form);
        try {
            localStorage.setItem(keys.recovery, JSON.stringify({
                title: title ? title.value : '',
                content: this.getCode(),
                desc: desc ? desc.value : '',
                refs: refs ? refs.value : ''
            }));
            this.setSaveState('Metin bu tarayıcıda yedeklendi; dosyalar ve yayın bilgileri hariç');
        } catch (_) {
            this.setSaveState('Tarayıcı yedeği alınamadı; taslak kaydedin');
        }
    };

    AdminEditor.prototype.restoreDraftIfNeeded = function () {
        var keys = this.draftKeys();
        var saved;
        try {
            saved = safeJsonParse(localStorage.getItem(keys.recovery) || 'null');
            if (!saved || typeof saved !== 'object') {
                saved = {
                    title: localStorage.getItem(keys.title),
                    content: localStorage.getItem(keys.content),
                    desc: localStorage.getItem(keys.desc),
                    refs: localStorage.getItem(keys.refs)
                };
                if (Object.values(saved).every(function (value) { return value === null; })) return;
            }
        } catch (_) { return; /* Browser backups are optional. */ }
        var title = qs(this.config.titleSelector, this.form);
        var desc = qs(this.config.descSelector, this.form);
        var refs = qs(this.config.refsSelector, this.form);
        if ((saved.title === null || saved.title === (title ? title.value : ''))
            && (saved.content === null || saved.content === this.getCode())
            && (saved.desc === null || saved.desc === (desc ? desc.value : ''))
            && (saved.refs === null || saved.refs === (refs ? refs.value : ''))) return;
        if (!window.confirm('Kaydedilmemiş bir tarayıcı yedeği bulundu. Başlık, metin, özet ve kaynakçayı geri yüklemek ister misiniz? Dosyalar ve yayın ayarları yedekte değildir.')) return;
        if (title && typeof saved.title === 'string') title.value = saved.title;
        if (typeof saved.content === 'string') {
            var recovered = document.createElement('template');
            recovered.innerHTML = saved.content;
            qsa('[data-word-image-pending], .editor-image-upload', recovered.content).forEach(function (node) {
                node.removeAttribute('data-word-image-pending');
                node.removeAttribute('data-word-image-index');
                node.classList.remove('editor-image-upload');
                node.classList.add('fezadan-import-error');
                node.setAttribute('data-import-error', '1');
                node.textContent = 'Bu görsel yedeklenemedi. Görseli yeniden ekleyip bu uyarıyı kaldırın.';
            });
            this.setCode(recovered.innerHTML);
        }
        if (desc && typeof saved.desc === 'string') desc.value = saved.desc;
        if (refs && typeof saved.refs === 'string') refs.value = saved.refs;
        this.isDirty = true;
        this.setSaveState('Tarayıcı yedeği geri yüklendi');
        this.updatePreview();
        this.updateQuality();
    };

    AdminEditor.prototype.bindSubmit = function () {
        var self = this;
        this.form.addEventListener('submit', function (e) {
            if (self.pendingImageUploads > 0) {
                e.preventDefault();
                self.showLoading(false);
                self.showStatus('Kaydetmeden önce görsellerin yüklenmesini bekleyin.', 'warning');
                return;
            }
            if (self.scientificImportPending || self.wordImportPending) {
                e.preventDefault();
                self.showStatus('Kaydetmeden önce aktarımın tamamlanmasını bekleyin veya aktarımı iptal edin.', 'warning');
                return;
            }
            var authorInputs = qsa('input[name="authors[]"]', self.form);
            if (authorInputs.length && !qsa('input[name="authors[]"]:checked', self.form).length) {
                e.preventDefault();
                self.showLoading(false);
                self.showStatus('Lütfen en az bir yazar seçin.', 'error');
                self.revealField(authorInputs[0]);
                authorInputs[0].focus();
                return;
            }

            if (self.hasCoverError) {
                e.preventDefault();
                self.showLoading(false);
                self.showStatus('Kapak görselinde hata var. Farklı bir dosya seçin veya alanı boş bırakın.', 'error');
                var coverInput = qs(self.config.coverInputSelector, self.form);
                self.revealField(coverInput);
                if (coverInput) coverInput.focus();
                return;
            }

            var postMax = parseInt(self.form.getAttribute('data-post-max-bytes') || '0', 10);
            if (postMax > 0) {
                var totalBytes = 0;
                try {
                    var fd = new FormData(self.form);
                    fd.forEach(function (value) {
                        if (typeof value !== 'string') totalBytes += value.size; // File entries; text fields are strings
                        else totalBytes += new Blob([String(value || '')]).size;
                    });
                } catch (err) {
                    totalBytes = 0;
                }
                if (totalBytes > postMax) {
                    e.preventDefault();
                    self.showLoading(false);
                    self.showStatus('Form boyutu sunucu limitini aşıyor. Görselleri küçültüp tekrar deneyin.', 'error');
                    return;
                }
                if (totalBytes > postMax * 0.9 && !window.confirm('Form boyutu sunucu limitine çok yakın. Kayıt başarısız olabilir. Devam edilsin mi?')) {
                    e.preventDefault();
                    self.showLoading(false);
                    return;
                }
            }

            var status = qs(self.config.statusInputSelector, self.form);
            var isDraft = status && status.value === 'draft';
            if (!isDraft && self.hasUnresolvedImportErrors()) {
                e.preventDefault();
                self.showLoading(false);
                self.showStatus('Aktarılamayan içerik var. İşaretli bölümleri düzeltin veya kaldırın; isterseniz taslak olarak kaydedebilirsiniz.', 'error');
                var editable = document.querySelector('.note-editable [data-import-error="1"]');
                if (editable) editable.scrollIntoView({ behavior: 'smooth', block: 'center' });
                return;
            }
            self.isDirty = false;
            self.saveDraft();
            try {
                sessionStorage.setItem('fezadan_pending_draft_clear', JSON.stringify(self.draftKeys()));
            } catch (_) { /* Server submission does not depend on browser storage. */ }
            self.showLoading(true, 'İşleniyor...');
            var submit = qs(self.config.publishButtonSelector, self.form) || qs('button[type="submit"]', self.form);
            [qs(self.config.draftButtonSelector, self.form), submit].forEach(function (button) {
                if (!button) return;
                button.disabled = true;
                button.style.opacity = '0.65';
            });
        });
    };

    AdminEditor.prototype.showLoading = function (show) {
        var overlay = qs(this.config.loadingSelector);
        if (!overlay) return;
        overlay.classList.toggle('hidden', !show);
        overlay.classList.toggle('flex', !!show);
    };

    AdminEditor.prototype.startHeartbeat = function () {
        setInterval(function () {
            fetch('/yonetim/dashboard', { method: 'HEAD', credentials: 'same-origin' }).catch(function () {});
        }, 10 * 60 * 1000);
    };

    AdminEditor.prototype.bindAnalysis = function () {
        var self = this;
        var lastContent = this.getCode();
        this.editor.on('summernote.change', function (_, content) {
            if (content !== lastContent) {
                self.isDirty = true;
                self.scheduleDraftBackup();
            }
            lastContent = content;
        });
        // Navigation keys and header buttons are not article changes.
        ['input', 'change'].forEach(function (eventName) {
            self.form.addEventListener(eventName, function (event) {
                if (/** @type {Element} */ (event.target).closest('#formulaDialog, #scientificInput')) return;
                self.isDirty = true;
                self.scheduleDraftBackup();
                self.scheduleUpdates();
            });
        });
    };

    // Summernote change, keyup and the bubbling input event describe the same edit.
    // Coalesce expensive HTML parsing/quality checks outside the keystroke handler.
    AdminEditor.prototype.scheduleUpdates = function () {
        var self = this;
        clearTimeout(this.updateTimer);
        this.updateTimer = setTimeout(function () {
            self.updateTimer = null;
            self.updatePreview();
            self.updateQuality();
        }, 150);
    };

    AdminEditor.prototype.updatePreview = function () {
        var title = qs(this.config.titleSelector, this.form);
        var desc = qs(this.config.descSelector, this.form);
        var refs = qs(this.config.refsSelector, this.form);
        var titleValue = title ? title.value.trim() : '';
        var descValue = desc ? desc.value.trim() : '';
        var content = this.getCode();
        var plain = htmlToText(content);
        var words = plain ? plain.split(/\s+/).filter(Boolean).length : 0;
        var minutes = Math.max(1, Math.ceil(words / 200));
        var wordCounter = document.getElementById('wordCounter');
        if (wordCounter) wordCounter.textContent = words ? words + ' kelime · yaklaşık ' + minutes + ' dk' : '0 kelime';

        var authors = qsa('input[name="authors[]"]:checked', this.form);
        var lang = qs('select[name="lang"]', this.form);
        var isEn = lang && lang.value === 'EN';
        var panel = /** @type {HTMLDialogElement} */ (document.getElementById('previewPanel'));
        var seoTitle = qs('[name="seo_title"]', this.form);
        var seoDesc = qs('[name="seo_description"]', this.form);
        var slug = qs('#manualSlug', this.form);
        var snippetTitle = document.getElementById('snippetTitle');
        var snippetDesc = document.getElementById('snippetDesc');
        var snippetUrl = document.getElementById('snippetUrl');
        if (snippetTitle) snippetTitle.textContent = (seoTitle && seoTitle.value.trim()) || titleValue || 'Sayfa başlığı';
        if (snippetDesc) snippetDesc.textContent = (seoDesc && seoDesc.value.trim()) || descValue || plain.slice(0, 160) || 'Açıklama eklediğinizde burada görünür.';
        if (snippetUrl) {
            var site = panel && panel.dataset.siteUrl || window.location.origin;
            var articleSlug = (slug && slug.value.trim()) || slugify(titleValue);
            snippetUrl.textContent = site.replace(/^https?:\/\//, '') + (isEn ? '/en/post/' : '/tr/yazi/') + (articleSlug ? encodeURIComponent(articleSlug) : 'makale-slugu');
        }

        // Keep typing cheap and avoid loading draft images in a hidden preview.
        var doc = previewDocument();
        if (!panel || !panel.open || !doc) return;
        doc.documentElement.lang = isEn ? 'en' : 'tr';
        doc.documentElement.setAttribute('data-theme', document.documentElement.getAttribute('data-theme') || 'light');
        var prevTitle = doc.getElementById('prev-title');
        var prevDesc = doc.getElementById('prev-desc');
        var prevContent = doc.getElementById('prev-content');
        if (!prevTitle || !prevContent) return;
        prevTitle.textContent = titleValue || (isEn ? 'Your title will appear here…' : 'Başlık buraya gelecek…');
        prevDesc.hidden = !descValue;
        prevDesc.textContent = descValue ? '“' + descValue + '”' : '';

        if (content && !this.isEmptyHtml(content)) {
            prevContent.innerHTML = readerPreviewHtml(content);
            linkPreviewReferences(prevContent);
        } else {
            prevContent.textContent = isEn ? 'Your content will appear here.' : 'İçerik eklediğinizde burada görünür.';
        }

        doc.getElementById('prev-readtime').textContent = minutes + (isEn ? ' min read' : ' dk okuma');
        var publishAt = qs('[name="publish_at"]', this.form);
        var prevDate = doc.getElementById('prev-date');
        if (!prevDate.dataset.initialDate) prevDate.dataset.initialDate = prevDate.textContent;
        var date = publishAt && publishAt.value ? new Date(publishAt.value) : null;
        prevDate.textContent = date && !isNaN(date.getTime()) ? date.toLocaleDateString(isEn ? 'en-US' : 'tr-TR', { day: 'numeric', month: 'long', year: 'numeric' }) : prevDate.dataset.initialDate;
        var prevAuthors = doc.getElementById('prev-authors');
        prevAuthors.hidden = !authors.length;
        prevAuthors.textContent = (isEn ? 'Authors: ' : 'Yazarlar: ') + authors.map(function (input) { return textOf(input.closest('label').querySelector('span')); }).join(' & ');

        var coverWrap = doc.getElementById('prev-cover-wrap');
        var prevCover = /** @type {HTMLImageElement} */ (doc.getElementById('prev-cover'));
        var coverUrl = this.coverDataUrl || this.config.existingCoverUrl || '';
        if (coverWrap && prevCover) {
            coverWrap.hidden = !coverUrl;
            if (coverUrl) prevCover.src = coverUrl;
            else prevCover.removeAttribute('src');
            prevCover.alt = titleValue + (isEn ? ' / cover image' : ' / kapak görseli');
        }

        var checked = qsa(this.config.categorySelector + ':checked', this.form);
        var catNames = checked.map(function (cb) {
            var label = cb.closest('label');
            return textOf(label ? label.querySelector('span') : null);
        }).filter(Boolean);
        var prevCats = doc.getElementById('prev-cats');
        if (prevCats) {
            prevCats.textContent = catNames.length ? catNames.join(', ') : (isEn ? 'GENERAL' : 'GENEL');
        }

        doc.getElementById('prev-refs-heading').textContent = isEn ? 'References' : 'Kaynakça';
        doc.getElementById('prev-toc-heading').textContent = isEn ? 'Contents' : 'İçindekiler';
        if (window.renderPrevRefs && refs) window.renderPrevRefs(refs.value);
        if (window.buildPrevToc) window.buildPrevToc();
        if (window._bindPreviewScroll) window._bindPreviewScroll();
        if (window.FezadanMath) window.FezadanMath.render(prevContent);
    };

    AdminEditor.prototype.updateQuality = function () {
        if (!this.qualityPanel) return;
        var result = this.analyze();
        var title = qs(this.config.titleSelector, this.form);
        var empty = result.wordCount === 0 && (!title || !title.value.trim());
        var state = this.qualityPanel.querySelector('#qualityState');
        var meta = this.qualityPanel.querySelector('#qualityMeta');
        var list = this.qualityPanel.querySelector('#qualityList');
        if (state) {
            state.className = 'quality-state ' + (empty ? 'neutral' : result.state);
            state.textContent = empty ? 'Yeni taslak' : result.label;
        }
        if (meta) {
            meta.textContent = empty ? 'Başlık ve metinle başlayın. Yayın bilgilerini daha sonra tamamlayabilirsiniz.' : result.wordCount + ' kelime / ' + result.readTime + ' dk okuma';
        }
        if (list) {
            list.innerHTML = (empty ? [] : result.items).map(function (item) {
                return '<div class="quality-item ' + item.level + '"><span class="dot"></span><span>' + item.text + '</span></div>';
            }).join('');
        }
    };

    AdminEditor.prototype.analyze = function () {
        var title = qs(this.config.titleSelector, this.form);
        var desc = qs(this.config.descSelector, this.form);
        var refs = qs(this.config.refsSelector, this.form);
        var author = qs(this.config.authorSelector, this.form);
        var authorInputs = qsa('input[name="authors[]"]', this.form);
        var content = this.getCode();
        var plain = htmlToText(content);
        var words = plain ? plain.split(/\s+/).filter(Boolean) : [];
        var items = [];
        var critical = 0;
        var warning = 0;

        function add(level, text) {
            items.push({ level: level, text: text });
            if (level === 'critical') critical += 1;
            if (level === 'warning') warning += 1;
        }

        var titleLen = title ? title.value.trim().length : 0;
        if (titleLen < 12) add('critical', 'Başlık çok kısa.');
        else if (titleLen > 90) add('warning', 'Başlık çok uzun; listeleme ve SEO için kısaltılabilir.');
        else add('ready', 'Başlık uzunluğu iyi.');

        var descLen = desc ? desc.value.trim().length : 0;
        if (descLen < 40) add('critical', 'Kısa özet eksik veya çok kısa.');
        else if (descLen > 220) add('warning', 'Kısa özet uzun; listeleme kartlarında taşabilir.');
        else add('ready', 'Kısa özet dengeli.');

        if (words.length < 80) add('critical', 'İçerik çok kısa.');
        else if (words.length < 300) add('warning', 'İçerik kısa; okuma deneyimi için geliştirme düşünülebilir.');
        else add('ready', 'Kelime sayısı yeterli.');

        var cats = qsa(this.config.categorySelector + ':checked', this.form).length;
        if (!cats) add('warning', 'Kategori seçilmedi.');
        else add('ready', 'Kategori seçildi.');

        if (authorInputs.length) {
            if (!qsa('input[name="authors[]"]:checked', this.form).length) add('critical', 'Yazar seçilmedi.');
            else add('ready', 'Yazar seçildi.');
        } else if (author && !author.value) add('critical', 'Yazar seçilmedi.');
        else add('ready', 'Yazar seçildi.');

        if (!this.coverDataUrl && !this.config.existingCoverUrl && !qs(this.config.coverInputSelector, this.form)?.files?.length) {
            add('warning', 'Kapak görseli yok.');
        } else if (this.hasCoverError) {
            add('critical', 'Kapak görselinde hata var.');
        } else {
            add('ready', 'Kapak görseli hazır.');
        }

        if (this.hasUnresolvedImportErrors()) {
            add('critical', 'Aktarılamayan içerik var; yayınlamadan önce işaretli bölümleri düzeltin.');
        }

        var refText = refs ? refs.value.trim() : '';
        var refContainer = document.createElement('div');
        refContainer.innerHTML = content;
        var prose = referenceTextNodes(refContainer).map(function (node) { return node.textContent; }).join(' ');
        var usedRefs = Array.from(new Set((prose.match(/\[(\d+)\]/g) || []).map(function (m) { return m.replace(/\D/g, ''); })));
        var declaredRefs = {};
        refText.split('\n').forEach(function (line) {
            line = line.trim();
            var match = line.match(/^(\d+)\s*=/) || line.match(/^\[(\d+)\]/);
            if (match) declaredRefs[match[1]] = true;
        });
        var missingRefs = usedRefs.filter(function (num) { return !declaredRefs[num]; });
        if (usedRefs.length && missingRefs.length) add('critical', 'Metindeki referansların kaynakçada karşılığı eksik: ' + missingRefs.join(', '));
        else if (!refText) add('warning', 'Kaynakça alanı boş.');
        else add('ready', 'Kaynakça kontrolü iyi.');

        var div = document.createElement('div');
        div.innerHTML = content;
        var longParagraphs = qsa('p', div).filter(function (p) { return textOf(p).length > 450; }).length;
        if (longParagraphs) add('warning', longParagraphs + ' paragraf çok uzun.');
        else add('ready', 'Paragraf uzunlukları okunabilir.');

        var h2Count = qsa('h2', div).length;
        var h3Count = qsa('h3', div).length;
        if (h3Count && !h2Count) add('warning', 'H3 başlık var ama H2 başlık yok; hiyerarşi bozulabilir.');
        else add('ready', 'Başlık hiyerarşisi uygun.');

        var emptyLinks = qsa('a', div).filter(function (a) { return !a.getAttribute('href') || a.getAttribute('href') === '#'; }).length;
        if (emptyLinks) add('warning', emptyLinks + ' boş link bulundu.');

        var state = critical ? 'critical' : (warning ? 'warning' : 'ready');
        return {
            state: state,
            label: critical ? 'Eksik alanlar' : (warning ? 'Öneriler var' : 'Kontrol tamam'),
            wordCount: words.length,
            readTime: Math.max(1, Math.ceil(words.length / 200)),
            items: items
        };
    };

    window.FezadanAdminEditor = {
        init: function (config) {
            ensurePreviewGlobals();
            var editor = new AdminEditor(config || {});
            editor.init();
            return editor;
        }
    };
})(window, document, window.jQuery);
