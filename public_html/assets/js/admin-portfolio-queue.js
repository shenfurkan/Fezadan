// @ts-check
(function () {
    'use strict';

    window.PortfolioQueueUploader = true;

    var SUPPORTED_EXTENSIONS = new Set(['jpg', 'jpeg', 'png', 'webp', 'gif']);
    var MAX_FILE_BYTES = 20 * 1024 * 1024;

    function initPortfolioQueue() {
        var form = document.getElementById('portfolio-upload-form');
        var dropzone = document.getElementById('form-dropzone');
        var input = /** @type {HTMLInputElement} */ (document.getElementById('image-input'));
        var queueBox = document.getElementById('upload-queue');
        var queueList = document.getElementById('upload-queue-list');
        var queueEmpty = document.getElementById('upload-queue-empty');
        var summary = document.getElementById('upload-queue-summary');
        var submitButton = /** @type {HTMLButtonElement} */ (document.getElementById('upload-queue-btn'));
        var overlay = document.getElementById('global-uploading-overlay');

        if (!form || !dropzone || !input || !queueBox || !queueList || !queueEmpty || !summary || !submitButton) {
            return;
        }

        var queue = [];
        var isUploading = false;
        var dragDepth = 0;
        var nextDisplayOrder = Array.from(/** @type {NodeListOf<HTMLInputElement>} */ (document.querySelectorAll('.order-input'))).reduce(function (nextOrder, orderInput) {
            return Math.max(nextOrder, Number(orderInput.value || 0) + 1);
        }, 0);

        function extensionOf(file) {
            var name = String(file && file.name || '');
            var lastDot = name.lastIndexOf('.');
            return lastDot === -1 ? '' : name.slice(lastDot + 1).toLowerCase();
        }

        function fileKey(file) {
            return [file.name, file.size, file.lastModified].join(':');
        }

        function formatBytes(bytes) {
            if (bytes < 1024) return bytes + ' B';
            if (bytes < 1024 * 1024) return (Math.round((bytes / 1024) * 10) / 10) + ' KB';
            return (Math.round((bytes / (1024 * 1024)) * 10) / 10) + ' MB';
        }

        function validationError(file) {
            if (!SUPPORTED_EXTENSIONS.has(extensionOf(file))) {
                return 'Desteklenmeyen format. JPG, PNG, WebP veya GIF seç.';
            }
            if (file.size > MAX_FILE_BYTES) {
                return 'Dosya 20MB sınırını aşıyor.';
            }
            if (file.size <= 0) {
                return 'Dosya boş görünüyor.';
            }
            return '';
        }


        function updateControls() {
            var waiting = queue.filter(function (entry) { return entry.status === 'pending'; }).length;
            submitButton.disabled = isUploading || waiting === 0;
            submitButton.classList.toggle('opacity-50', submitButton.disabled);
            submitButton.classList.toggle('cursor-not-allowed', submitButton.disabled);
            submitButton.textContent = isUploading
                ? 'YÜKLENİYOR...'
                : (waiting > 0 ? 'SIRADAKİLERİ YÜKLE (' + waiting + ')' : 'SIRADAKİLERİ YÜKLE');
        }

        function createAction(label, onClick, className) {
            var button = document.createElement('button');
            button.type = 'button';
            button.className = className;
            button.textContent = label;
            button.addEventListener('click', onClick);
            return button;
        }

        function renderQueue() {
            queueList.replaceChildren();
            queueBox.classList.toggle('hidden', queue.length === 0);
            queueEmpty.classList.toggle('hidden', queue.length > 0);

            queue.forEach(function (entry, index) {
                var row = document.createElement('li');
                row.className = 'flex items-center gap-3 border border-[var(--line-color)]/20 bg-[var(--bg-paper)] p-2';

                if (entry.previewUrl) {
                    var preview = document.createElement('img');
                    preview.src = entry.previewUrl;
                    preview.alt = '';
                    preview.className = 'h-10 w-10 shrink-0 object-cover border border-[var(--line-color)]/30';
                    row.appendChild(preview);
                }

                var details = document.createElement('div');
                details.className = 'min-w-0 flex-1';

                var name = document.createElement('div');
                name.className = 'truncate text-xs font-bold';
                name.textContent = (index + 1) + '. ' + entry.file.name;
                details.appendChild(name);

                var meta = document.createElement('div');
                meta.className = 'text-[10px] font-mono opacity-60';
                meta.textContent = formatBytes(entry.file.size) + ' · sıra ' + entry.displayOrder;
                details.appendChild(meta);

                if (entry.error) {
                    var error = document.createElement('div');
                    error.className = 'mt-1 text-[10px] text-red-600';
                    error.textContent = entry.error;
                    details.appendChild(error);
                }

                row.appendChild(details);

                // Sağ taraf: sadece 'yükleniyor' animasyonu ya da hata için kısa etiket
                if (entry.status === 'uploading') {
                    var spinner = document.createElement('span');
                    spinner.className = 'shrink-0 flex gap-[3px] items-center';
                    spinner.innerHTML = '<span style="width:5px;height:5px;border-radius:50%;background:var(--text-accent);display:inline-block;animation:dot-pulse 1s ease-in-out infinite;animation-delay:0s"></span>' +
                        '<span style="width:5px;height:5px;border-radius:50%;background:var(--text-accent);display:inline-block;animation:dot-pulse 1s ease-in-out infinite;animation-delay:.2s"></span>' +
                        '<span style="width:5px;height:5px;border-radius:50%;background:var(--text-accent);display:inline-block;animation:dot-pulse 1s ease-in-out infinite;animation-delay:.4s"></span>';
                    row.appendChild(spinner);
                } else if (entry.status === 'done') {
                    var doneLabel = document.createElement('span');
                    doneLabel.className = 'shrink-0 text-[10px] font-mono font-bold text-green-700 uppercase';
                    doneLabel.textContent = 'Yüklendi';
                    row.appendChild(doneLabel);
                } else if (entry.status === 'error') {
                    var errLabel = document.createElement('span');
                    errLabel.className = 'shrink-0 text-[10px] font-mono font-bold text-red-600 uppercase';
                    errLabel.textContent = 'Hata';
                    row.appendChild(errLabel);
                }

                var actions = document.createElement('div');
                actions.className = 'flex shrink-0 gap-1';

                if (!isUploading && (entry.status === 'pending' || entry.status === 'error')) {
                    actions.appendChild(createAction('KALDIR', function () {
                        queue = queue.filter(function (item) { return item.id !== entry.id; });
                        if (entry.previewUrl) URL.revokeObjectURL(entry.previewUrl);
                        renderQueue();
                    }, 'border border-[var(--line-color)] px-2 py-1 text-[9px] font-mono font-bold hover:bg-[var(--line-color)] hover:text-[var(--bg-paper)]'));

                    if (entry.status === 'error' && entry.retryable) {
                        actions.appendChild(createAction('TEKRAR DENE', function () {
                            entry.status = 'pending';
                            entry.error = '';
                            renderQueue();
                        }, 'border border-[var(--text-accent)] px-2 py-1 text-[9px] font-mono font-bold text-[var(--text-accent)] hover:bg-[var(--text-accent)] hover:text-white'));
                    }
                }

                if (entry.status === 'done' && entry.editUrl) {
                    var edit = document.createElement('a');
                    edit.href = entry.editUrl;
                    edit.className = 'border border-[var(--text-main)] px-2 py-1 text-[9px] font-mono font-bold hover:bg-[var(--text-main)] hover:text-[var(--bg-paper)]';
                    edit.textContent = 'DÜZENLE';
                    actions.appendChild(edit);
                }

                row.appendChild(actions);
                queueList.appendChild(row);
            });

            var done = queue.filter(function (entry) { return entry.status === 'done'; }).length;
            var failed = queue.filter(function (entry) { return entry.status === 'error'; }).length;
            summary.textContent = queue.length + ' görsel · ' + done + ' yüklendi' + (failed ? ' · ' + failed + ' hata' : '');
            updateControls();
        }

        function enqueueFiles(files) {
            var added = 0;
            var duplicateCount = 0;

            Array.from(files || []).forEach(function (file) {
                var key = fileKey(file);
                if (queue.some(function (entry) { return entry.key === key; })) {
                    duplicateCount++;
                    return;
                }

                var error = validationError(file);
                queue.push({
                    id: key + ':' + queue.length,
                    key: key,
                    file: file,
                    displayOrder: nextDisplayOrder++,
                    status: error ? 'error' : 'pending',
                    error: error,
                    retryable: !error,
                    previewUrl: error || !file.type || file.type.indexOf('image/') !== 0 ? '' : URL.createObjectURL(file),
                    editUrl: ''
                });
                added++;
            });

            input.value = '';
            renderQueue();

            if (duplicateCount && window.FezadanToast) {
                window.FezadanToast.info(duplicateCount + ' yinelenen dosya kuyruğa tekrar eklenmedi.');
            }
            if (added && window.FezadanToast) {
                window.FezadanToast.success(added + ' görsel kuyruğa eklendi.');
            }
        }

        function hasFiles(event) {
            return Boolean(event.dataTransfer && Array.from(event.dataTransfer.types || []).indexOf('Files') !== -1);
        }

        function setOverlay(visible, label, position, total) {
            if (!overlay) return;
            overlay.classList.toggle('hidden', !visible);
            if (!visible) return;

            var titleEl   = document.getElementById('uploading-overlay-title');
            var filenameEl = document.getElementById('uploading-overlay-filename');
            var barEl     = document.getElementById('uploading-overlay-bar');
            var detailEl  = document.getElementById('uploading-overlay-detail');

            if (position && total) {
                if (titleEl) titleEl.textContent = position + ' / ' + total + ' YÜKLENİYOR';
                var pct = Math.round(((position - 1) / total) * 100);
                if (barEl) barEl.style.width = pct + '%';
            } else {
                if (titleEl) titleEl.textContent = 'YÜKLENİYOR...';
                if (barEl) barEl.style.width = '0%';
            }

            if (filenameEl) filenameEl.textContent = label || '';
            if (detailEl) detailEl.textContent = 'Cloudflare R2\'ye kaydediliyor, lütfen pencereyi kapatmayın.';
        }

        function postPortfolio(formData) {
            var request = {
                method: 'POST',
                body: formData,
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            };

            if (window.FezadanFetch) {
                return window.FezadanFetch('/yonetim/store-portfolio', request, { toastOnError: false });
            }

            return fetch('/yonetim/store-portfolio', request).then(function (response) {
                return response.text().then(function (text) {
                    var data = {};
                    try {
                        data = text ? JSON.parse(text) : {};
                    } catch (error) {
                        throw new Error(response.ok
                            ? 'Sunucu geçerli bir yükleme yanıtı döndürmedi.'
                            : 'Sunucu hatası: HTTP ' + response.status);
                    }
                    if (!response.ok || data.success === false) {
                        throw new Error(data.error || 'Görsel yüklenemedi.');
                    }
                    return data;
                });
            });
        }

        /**
         * Büyük görselleri (>2MB) yüklemeden önce Canvas ile yeniden encode eder.
         * Kalite: 0.92 (görsel fark yok, dosya boyutu belirgin şekilde azalır).
         * GIF dosyalarına dokunmaz.
         */
        function compressIfNeeded(file) {
            var THRESHOLD = 2 * 1024 * 1024; // 2 MB
            var QUALITY   = 0.92;

            if (file.size <= THRESHOLD || file.type === 'image/gif') {
                return Promise.resolve(file);
            }

            return new Promise(function (resolve) {
                var img = new Image();
                var objectUrl = URL.createObjectURL(file);

                img.onload = function () {
                    URL.revokeObjectURL(objectUrl);
                    var canvas = document.createElement('canvas');
                    canvas.width  = img.naturalWidth;
                    canvas.height = img.naturalHeight;
                    canvas.getContext('2d').drawImage(img, 0, 0);

                    canvas.toBlob(function (blob) {
                        if (!blob || blob.size >= file.size) {
                            resolve(file); // sıkıştırma işe yaramadıysa orijinali kullan
                            return;
                        }
                        // Dosya adı uzantısını .jpg yap
                        var baseName = file.name.replace(/\.[^.]+$/, '');
                        resolve(new File([blob], baseName + '.jpg', {
                            type: 'image/jpeg',
                            lastModified: file.lastModified
                        }));
                    }, 'image/jpeg', QUALITY);
                };

                img.onerror = function () {
                    URL.revokeObjectURL(objectUrl);
                    resolve(file); // hata olursa orijinali kullan
                };

                img.src = objectUrl;
            });
        }

        async function uploadEntry(entry, position, total) {
            entry.status = 'uploading';
            entry.error = '';
            renderQueue();

            // 2MB üzerindeyse önce sıkıştır
            var fileToUpload = entry.file;
            if (entry.file.size > 2 * 1024 * 1024 && entry.file.type !== 'image/gif') {
                setOverlay(true, 'Hazırlanıyor: ' + entry.file.name, position, total);
                fileToUpload = await compressIfNeeded(entry.file);
            }

            setOverlay(true, fileToUpload.name, position, total);

            var csrf = /** @type {HTMLInputElement} */ (form.querySelector('input[name="_csrf"]'));
            var formData = new FormData();
            formData.append('image', fileToUpload, fileToUpload.name); // formData.append('image', entry.file, entry.file.name)
            formData.append('display_order', String(entry.displayOrder));
            if (csrf) formData.append('_csrf', csrf.value);

            try {
                var data = await postPortfolio(formData);
                if (!data || typeof data !== 'object' || data.success !== true) {
                    throw new Error(data && data.error ? data.error : 'Görsel yüklenemedi.');
                }
                entry.status = 'done';
                entry.editUrl = data.edit_url || (data.id ? '/yonetim/portfolio-edit?id=' + data.id : '');
            } catch (error) {
                entry.status = 'error';
                entry.error = error && error.message ? error.message : 'Yükleme tamamlanamadı.';
                entry.retryable = true;
            }

            renderQueue();
        }

        async function processQueue() {
            if (isUploading) return;

            var pending = queue.filter(function (entry) { return entry.status === 'pending'; });
            if (!pending.length) return;

            isUploading = true;
            renderQueue();

            for (var index = 0; index < pending.length; index++) {
                await uploadEntry(pending[index], index + 1, pending.length);
            }

            isUploading = false;

            // %100 göster, 600ms sonra kapat → ardından bildirim ve kuyruk güncelle
            var barEl = document.getElementById('uploading-overlay-bar');
            var titleEl = document.getElementById('uploading-overlay-title');
            if (barEl) barEl.style.width = '100%';
            if (titleEl) titleEl.textContent = 'TAMAMLANDI!';

            var done = queue.filter(function (entry) { return entry.status === 'done'; }).length;
            var failed = queue.filter(function (entry) { return entry.status === 'error'; }).length;

            setTimeout(function () {
                setOverlay(false, '');
                if (window.FezadanToast) {
                    if (failed) {
                        window.FezadanToast.warning(done + ' görsel yüklendi, ' + failed + ' görsel için hata var.');
                    } else {
                        window.FezadanToast.success(done + ' görsel başarıyla yüklendi. Her satırdaki Düzenle ile bilgileri ekleyebilirsin.');
                    }
                }
                renderQueue();
            }, 600);
        }


        form.addEventListener('submit', function (event) {
            event.preventDefault();
            processQueue();
        });

        dropzone.addEventListener('click', function (event) {
            if (isUploading || event.target === input) return;
            input.click();
        });

        ['dragenter', 'dragover'].forEach(function (eventName) {
            dropzone.addEventListener(eventName, function (event) {
                if (!hasFiles(event)) return;
                event.preventDefault();
                event.stopPropagation();
                dropzone.style.backgroundColor = 'rgba(109, 35, 35, 0.08)';
            });
        });

        dropzone.addEventListener('dragleave', function (event) {
            if (!hasFiles(event)) return;
            event.preventDefault();
            event.stopPropagation();
            dropzone.style.backgroundColor = '';
        });

        dropzone.addEventListener('drop', function (event) {
            if (!hasFiles(event)) return;
            event.preventDefault();
            event.stopPropagation();
            dropzone.style.backgroundColor = '';
            enqueueFiles(event.dataTransfer.files);
        });

        input.addEventListener('change', function () {
            enqueueFiles(input.files);
        });

        window.addEventListener('dragenter', function (event) {
            if (!hasFiles(event)) return;
            event.preventDefault();
            dragDepth++;
            var globalOverlay = document.getElementById('global-drag-overlay');
            if (globalOverlay) globalOverlay.classList.add('show');
        });

        window.addEventListener('dragover', function (event) {
            if (hasFiles(event)) event.preventDefault();
        });

        window.addEventListener('dragleave', function (event) {
            if (!hasFiles(event)) return;
            event.preventDefault();
            dragDepth = Math.max(0, dragDepth - 1);
            if (dragDepth === 0) {
                var globalOverlay = document.getElementById('global-drag-overlay');
                if (globalOverlay) globalOverlay.classList.remove('show');
            }
        });

        window.addEventListener('drop', function (event) {
            if (!hasFiles(event)) return;
            event.preventDefault();
            dragDepth = 0;
            var globalOverlay = document.getElementById('global-drag-overlay');
            if (globalOverlay) globalOverlay.classList.remove('show');
            enqueueFiles(event.dataTransfer.files);
        });

        window.addEventListener('beforeunload', function () {
            queue.forEach(function (entry) {
                if (entry.previewUrl) URL.revokeObjectURL(entry.previewUrl);
            });
        });

        renderQueue();
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initPortfolioQueue);
    } else {
        initPortfolioQueue();
    }
}());
