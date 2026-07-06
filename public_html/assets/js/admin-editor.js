(function (window, document, $) {
    'use strict';

    if (!$ || !$.fn || !$.fn.summernote) {
        window.FezadanAdminEditor = { init: function () { console.error('Summernote is not loaded.'); } };
        return;
    }

    var DEFAULT_TOOLBAR = [
        ['undo', ['undo', 'redo']],
        ['style', ['style']],
        ['font', ['bold', 'italic', 'underline', 'strikethrough', 'clear']],
        ['fontname', ['fontname']],
        ['fontsize', ['fontsize']],
        ['color', ['color']],
        ['para', ['paragraph', 'ul', 'ol']],
        ['height', ['height']],
        ['insert', ['link', 'picture', 'video', 'table', 'hr', 'blockquote']],
        ['view', ['fullscreen', 'codeview']]
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
        var div = document.createElement('div');
        div.innerHTML = html || '';
        return (div.textContent || div.innerText || '').trim();
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
                localStorage.setItem('theme', newTheme);
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

    function ensurePreviewGlobals() {
        window.switchTab = function (tab) {
            var preview = document.getElementById('previewPanel');
            var form = document.getElementById('uploadForm') || document.getElementById('editForm');
            if (!preview || !form) return;

            if (tab === 'preview') {
                if (typeof window.updatePreview === 'function') window.updatePreview();
                form.classList.add('hidden');
                preview.classList.remove('hidden');
                setTabButtonState('preview');
                preview.scrollTo({ top: 0, behavior: 'smooth' });
                return;
            }

            preview.classList.add('hidden');
            form.classList.remove('hidden');
            setTabButtonState('write');
        };

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
            var wrap = document.getElementById('prev-refs-wrap');
            var list = document.getElementById('prev-refs');
            if (!wrap || !list) return;

            var lines = String(value || '').split('\n').map(function (line) { return line.trim(); }).filter(Boolean);
            wrap.classList.toggle('hidden', lines.length === 0);
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
            var content = document.getElementById('prev-content');
            var toc = document.getElementById('prev-toc');
            if (!content || !toc) return;

            var headings = qsa('h2, h3', content).filter(function (heading) { return textOf(heading); });
            if (!headings.length) {
                toc.innerHTML = '<p class="opacity-50">Başlıklar eklendikçe burada görünür.</p>';
                return;
            }

            toc.innerHTML = headings.map(function (heading, index) {
                if (!heading.id) heading.id = 'prev-heading-' + index;
                var indent = heading.tagName.toLowerCase() === 'h3' ? 'pl-3 opacity-75' : 'font-bold';
                return '<a href="#' + escapeHtml(heading.id) + '" class="block ' + indent + ' hover:text-[var(--text-accent)]">' + escapeHtml(textOf(heading)) + '</a>';
            }).join('');
        };

        window._bindPreviewScroll = function () {
            var panel = document.getElementById('previewPanel');
            if (!panel || panel.dataset.scrollBound === '1') return;
            panel.dataset.scrollBound = '1';
            panel.addEventListener('click', function (e) {
                var link = e.target.closest('a[href^="#prev-"]');
                if (!link) return;
                var target = document.getElementById(link.getAttribute('href').slice(1));
                if (!target) return;
                e.preventDefault();
                target.scrollIntoView({ behavior: 'smooth', block: 'start' });
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
    }

    AdminEditor.prototype.init = function () {
        if (!this.form || !this.editor.length) return;
        injectRuntimeStyles();
        initThemeToggle();
        this.statusBox = this.ensureStatusBox();
        this.qualityPanel = this.ensureQualityPanel();
        this.initSummernote();
        this.bindShortcuts();
        this.bindTemplates();
        this.bindWordImport();
        this.bindCoverPreview();
        this.bindDraftActions();
        this.bindSlugGeneration();
        this.bindAnalysis();
        this.bindSubmit();
        this.startHeartbeat();
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

    AdminEditor.prototype.bindBeforeUnload = function () {
        var self = this;
        window.addEventListener('beforeunload', function (e) {
            if (self.isDirty) {
                e.preventDefault();
                e.returnValue = '';
            }
        });
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
                    if (self.shortcutHandler) {
                        self.shortcutHandler(e);
                    }
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
                    self.updatePreview();
                    self.updateQuality();
                },
                onImageUpload: function (files) {
                    self.uploadImages(Array.prototype.slice.call(files || []));
                },
                onChange: function () {
                    self.updatePreview();
                    self.updateQuality();
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
                    setTimeout(function () {
                        self.updatePreview();
                        self.updateQuality();
                    }, 0);
                }
            }
        });
    };

    AdminEditor.prototype.normalizeExistingImages = function () {
        $('.note-editable').find('img').each(function () {
            var img = $(this);
            if (img.closest('figure').length) return;
            img.wrap('<figure></figure>');
            img.after('<figcaption contenteditable="true" data-placeholder="Görsel açıklaması (isteğe bağlı)..."></figcaption>');
        });
    };

    AdminEditor.prototype.uploadImages = function (files) {
        var self = this;
        if (!files.length) return;
        var hadWarning = false;
        this.showStatus('Görsel yükleniyor...', 'info');
        files.reduce(function (chain, file) {
            return chain.then(function () {
                return self.uploadImage(file, { source: 'editor' }).then(function (payload) {
                    var altText = prompt('Görsel açıklaması girin (alt text):') || '';
                    self.insertHtmlSafely(self.figureHtml(payload.url, altText));
                    if (payload.visibility_warning) {
                        hadWarning = true;
                        self.showStatus(payload.visibility_warning + ' (Hata kodu: ' + payload.request_id + ')', 'error');
                    }
                });
            });
        }, Promise.resolve()).then(function () {
            if (!hadWarning) self.showStatus('Görsel yüklendi.', 'success');
        }).catch(function (err) {
            self.showStatus('Görsel yüklenemedi: ' + err.message, 'error');
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
                    if (!blob) {
                        resolve(file);
                        return;
                    }
                    var baseName = (file.name || 'image').replace(/\.[^.]+$/, '');
                    resolve(new File([blob], baseName + '.webp', { type: 'image/webp' }));
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
        var input = document.getElementById('deferredWordImages');
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
                resolve();
                return;
            }
            var script = document.createElement('script');
            script.src = '/assets/js/mammoth.browser.min.js';
            var nonce = document.querySelector('script[nonce]');
            if (nonce) script.setAttribute('nonce', nonce.getAttribute('nonce'));
            script.onload = function () { resolve(); };
            script.onerror = function () { reject(new Error('Word kütüphanesi yüklenemedi.')); };
            document.head.appendChild(script);
        });
    };

    AdminEditor.prototype.bindShortcuts = function () {
        var self = this;
        function handleShortcut(e) {
            if ((e.ctrlKey || e.metaKey) && e.key === 's') {
                e.preventDefault();
                var draftBtn = qs(self.config.draftButtonSelector, self.form);
                if (draftBtn) {
                    draftBtn.click();
                } else {
                    self.saveDraft();
                }
            }
            if ((e.ctrlKey || e.metaKey) && e.shiftKey && (e.key === 'P' || e.key === 'p')) {
                e.preventDefault();
                var previewPanel = document.getElementById('previewPanel');
                var isPreviewVisible = previewPanel && !previewPanel.classList.contains('hidden');
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
            if (!input.files || !input.files[0]) return;
            self.showLoading(true, 'Word kütüphanesi yükleniyor...');
            self.loadMammoth().then(function () {
                self.showLoading(true, 'Word dosyası işleniyor...');
                var reader = new FileReader();
                reader.onload = function (e) {
                    window.mammoth.convertToHtml({ arrayBuffer: e.target.result }).then(function (result) {
                        var parser = new DOMParser();
                        var doc = parser.parseFromString(result.value || '', 'text/html');
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
                        self.showLoading(false);
                        input.value = '';
                    });
                };
                reader.onerror = function () {
                    self.showLoading(false);
                    self.showStatus('Word dosyası okunamadı.', 'error');
                    input.value = '';
                };
                reader.readAsArrayBuffer(input.files[0]);
            }).catch(function (err) {
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
        var editThumb = document.getElementById('currentCoverThumb');
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
        var content = this.getCode();
        if ((title && title.value.trim()) || !this.isEmptyHtml(content)) {
            localStorage.setItem(keys.title, title ? title.value : '');
            localStorage.setItem(keys.content, content);
            localStorage.setItem(keys.desc, desc ? desc.value : '');
            localStorage.setItem(keys.refs, refs ? refs.value : '');
        }
    };

    AdminEditor.prototype.restoreDraftIfNeeded = function () {
        var keys = this.draftKeys();
        var savedTitle = localStorage.getItem(keys.title);
        var savedContent = localStorage.getItem(keys.content);
        if (!savedTitle && (!savedContent || this.isEmptyHtml(savedContent))) return;

        var currentContent = this.getCode();
        if (savedContent && savedContent === currentContent) return;
        if (!window.confirm('Bu sayfa için kaydedilmemiş bir tarayıcı yedeği bulundu. Geri yüklemek ister misiniz?')) {
            this.clearDraft();
            return;
        }
        var title = qs(this.config.titleSelector, this.form);
        var desc = qs(this.config.descSelector, this.form);
        var refs = qs(this.config.refsSelector, this.form);
        if (title && savedTitle) title.value = savedTitle;
        if (savedContent) this.setCode(savedContent);
        if (desc && localStorage.getItem(keys.desc) !== null) desc.value = localStorage.getItem(keys.desc);
        if (refs && localStorage.getItem(keys.refs) !== null) refs.value = localStorage.getItem(keys.refs);
        this.updatePreview();
        this.updateQuality();
    };

    AdminEditor.prototype.clearDraft = function () {
        var keys = this.draftKeys();
        Object.keys(keys).forEach(function (key) { localStorage.removeItem(keys[key]); });
    };

    AdminEditor.prototype.bindSubmit = function () {
        var self = this;
        this.form.addEventListener('submit', function (e) {
            var authorInputs = qsa('input[name="authors[]"]', self.form);
            if (authorInputs.length && !qsa('input[name="authors[]"]:checked', self.form).length) {
                e.preventDefault();
                self.showLoading(false);
                self.showStatus('Lütfen en az bir yazar seçin.', 'error');
                return;
            }

            if (self.hasCoverError) {
                e.preventDefault();
                self.showLoading(false);
                self.showStatus('Kapak görselinde hata var. Farklı bir dosya seçin veya alanı boş bırakın.', 'error');
                return;
            }

            var postMax = parseInt(self.form.getAttribute('data-post-max-bytes') || '0', 10);
            if (postMax > 0) {
                var totalBytes = 0;
                try {
                    var fd = new FormData(self.form);
                    fd.forEach(function (value) {
                        if (value && typeof value.size === 'number') totalBytes += value.size;
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
                self.showStatus('Word import sırasında yüklenemeyen görsel var. Hata bloğunu silin veya görseli yeniden yükleyin; isterseniz taslak olarak kaydedebilirsiniz.', 'error');
                var editable = document.querySelector('.note-editable [data-import-error="1"]');
                if (editable) editable.scrollIntoView({ behavior: 'smooth', block: 'center' });
                return;
            }
            self.isDirty = false;
            self.saveDraft();
            sessionStorage.setItem('fezadan_pending_draft_clear', JSON.stringify(self.draftKeys()));
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
        ['input', 'change', 'keyup'].forEach(function (eventName) {
            self.form.addEventListener(eventName, function () {
                self.isDirty = true;
                self.updatePreview();
                self.updateQuality();
            });
        });
    };

    AdminEditor.prototype.updatePreview = function () {
        var title = qs(this.config.titleSelector, this.form);
        var desc = qs(this.config.descSelector, this.form);
        var refs = qs(this.config.refsSelector, this.form);
        var prevTitle = document.getElementById('prev-title');
        var prevDesc = document.getElementById('prev-desc');
        var prevContent = document.getElementById('prev-content');
        if (!prevTitle || !prevContent) return;

        prevTitle.textContent = (title && title.value.trim()) || 'Başlık buraya gelecek...';
        if (prevDesc) {
            var descValue = desc ? desc.value.trim() : '';
            prevDesc.style.display = descValue ? 'block' : 'none';
            if (descValue) prevDesc.textContent = '"' + descValue + '"';
        }

        var content = this.getCode();
        if (content && !this.isEmptyHtml(content)) {
            var seen = [];
            prevContent.innerHTML = content.replace(/\[(\d+)\]/g, function (match, num) {
                var id = '';
                if (seen.indexOf(num) === -1) {
                    id = ' id="prev-ref-link-' + num + '"';
                    seen.push(num);
                }
                return '<sup class="reference-sup"><a href="#prev-ref-item-' + num + '"' + id + ' class="text-[var(--text-accent)] hover:underline" style="font-family:\'JetBrains Mono\',monospace;font-size:0.75rem;">[' + num + ']</a></sup>';
            });
        } else {
            prevContent.innerHTML = '<p class="opacity-40 italic">İçerik editörden yansıyacak...</p>';
        }

        var readTime = document.getElementById('prev-readtime');
        if (readTime) {
            var words = textOf(prevContent).split(/\s+/).filter(Boolean).length;
            readTime.textContent = Math.max(1, Math.ceil(words / 200));
        }

        var wordCounter = document.getElementById('wordCounter');
        if (wordCounter) {
            var wc = textOf(prevContent).split(/\s+/).filter(Boolean).length;
            wordCounter.textContent = wc + ' kelime / ~' + Math.max(1, Math.ceil(wc / 200)) + ' dk';
        }

        var coverWrap = document.getElementById('prev-cover-wrap');
        var prevCover = document.getElementById('prev-cover');
        var coverUrl = this.coverDataUrl || this.config.existingCoverUrl || '';
        if (coverWrap && prevCover) {
            coverWrap.style.display = coverUrl ? 'block' : 'none';
            if (coverUrl) prevCover.src = coverUrl;
        }

        var checked = qsa(this.config.categorySelector + ':checked', this.form);
        var catNames = checked.map(function (cb) {
            var label = cb.closest('label');
            return textOf(label ? label.querySelector('span') : null);
        }).filter(Boolean);
        var prevCats = document.getElementById('prev-cats');
        if (prevCats) {
            prevCats.textContent = catNames.length ? catNames.join(', ') : 'KATEGORİ SEÇİLMEDİ';
            prevCats.style.opacity = catNames.length ? '1' : '0.5';
        }

        if (window.renderPrevRefs && refs) window.renderPrevRefs(refs.value);
        if (window.buildPrevToc) window.buildPrevToc();
        if (window._bindPreviewScroll) window._bindPreviewScroll();
    };

    AdminEditor.prototype.updateQuality = function () {
        if (!this.qualityPanel) return;
        var result = this.analyze();
        var state = this.qualityPanel.querySelector('#qualityState');
        var meta = this.qualityPanel.querySelector('#qualityMeta');
        var list = this.qualityPanel.querySelector('#qualityList');
        if (state) {
            state.className = 'quality-state ' + result.state;
            state.textContent = result.label;
        }
        if (meta) {
            meta.textContent = result.wordCount + ' kelime / ' + result.readTime + ' dk okuma';
        }
        if (list) {
            list.innerHTML = result.items.map(function (item) {
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
            add('critical', 'Word import sırasında yüklenemeyen görsel var; yayınlamadan önce düzeltin.');
        }

        var refText = refs ? refs.value.trim() : '';
        var usedRefs = Array.from(new Set((content.match(/\[(\d+)\]/g) || []).map(function (m) { return m.replace(/\D/g, ''); })));
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
            label: critical ? 'Kritik Eksik' : (warning ? 'Uyarı Var' : 'Yayına Hazır'),
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
