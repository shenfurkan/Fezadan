<?php

trait AdminArticles
{
    // Yama notu oluşturma sayfası
    public function createPatch()
    {
        if (!isset($_SESSION['admin_logged_in'])) {
            header('Location: /yonetim');
            exit;
        }
        $this->view('admin/create-patch');
    }

    // Kaydetme
    public function storePatch()
    {
        $this->requirePost();

        $title = $_POST['title'] ?? 'Sistem Güncellemesi';
        $content = $_POST['content'] ?? '';
        $author = $_SESSION['admin_user'] ?? 'yonetim';

        try {
            $pdo = $this->getPDO();

            // Önce geçici değerle insert et, sonra LAST_INSERT_ID() ile güncelle (race-safe)
            $stmt = $pdo->prepare("INSERT INTO patch_notes (version, title, content, author) VALUES ('', ?, ?, ?)");
            $stmt->execute([$title, $content, $author]);
            $newId = (int)$pdo->lastInsertId();
            $pdo->prepare("UPDATE patch_notes SET version = ? WHERE id = ?")
                ->execute(['1.' . $newId, $newId]);

            header('Location: /yonetim/dashboard?status=patch_added');
        }
        catch (\PDOException $e) {
            throw new \Exception("Yama Notu Kayıt Hatası: " . $e->getMessage());
        }
    }

    // Yama notu silme
    public function deletePatch()
    {
        $this->requirePost();

        $id = (int)($_POST['id'] ?? 0);
        if ($id > 0) {
            try {
                $pdo = $this->getPDO();
                $stmt = $pdo->prepare("DELETE FROM patch_notes WHERE id = ?");
                $stmt->execute([$id]);
            }
            catch (\PDOException $e) {
                throw new \Exception("Yama Notu Silme Hatası: " . $e->getMessage());
            }
        }
        header('Location: /yonetim/dashboard?status=patch_deleted');
    }

    public function patchDelete()
    {
        $this->deletePatch();
    }

    // --- Makale yönetimi ---

    // Yeni makale oluşturma sayfası
    public function create()
    {
        if (!isset($_SESSION['admin_logged_in'])) {
            header('Location: /yonetim');
            exit;
        }

        try {
            $pdo = $this->getPDO();
            $categories = $pdo->query("SELECT * FROM categories ORDER BY name ASC")->fetchAll(\PDO::FETCH_ASSOC);
            $authors = $pdo->query("SELECT id, name FROM authors ORDER BY name ASC")->fetchAll(\PDO::FETCH_ASSOC);
            $articlesList = $pdo->query("SELECT id, title, lang FROM articles ORDER BY title ASC")->fetchAll(\PDO::FETCH_ASSOC);

            $this->view('admin/create', [
                'authors' => $authors,
                'categories' => $categories,
                'articlesList' => $articlesList
            ]);
        }
        catch (\PDOException $e) {
            throw new \Exception("Veri Çekilemedi: " . $e->getMessage());
        }
    }

    // Makale kaydetme
    public function store()
    {
        $requestStarted = microtime(true);
        $this->requirePost();
        $this->guardPostSizeOrFail('/yonetim/create', 'article_store');

        $requestId = $this->requestId();
        $title              = trim($_POST['title'] ?? '') ?: 'Adsiz';
        $desc               = $_POST['desc'] ?? '';
        $content            = $_POST['content'] ?? '';
        $refs               = $_POST['refs'] ?? '';
        $selectedAuthors    = $_POST['authors'] ?? [];
        if (empty($selectedAuthors) && isset($_POST['author_id']) && (int)$_POST['author_id'] > 0) {
            $selectedAuthors = [(int)$_POST['author_id']];
        }
        $selectedCategories = $_POST['categories'] ?? [];
        $image_db_path      = '';
        $wordImagePaths     = [];
        $status             = ($_POST['status'] ?? 'published') === 'draft' ? 'draft' : 'published';
        $publish_at         = !empty($_POST['publish_at']) ? date('Y-m-d H:i:s', strtotime($_POST['publish_at'])) : null;
        if ($publish_at && strtotime($publish_at) > time() && $status !== 'draft') {
            $status = 'scheduled';
        }
        $lang               = $this->normalizeNoteLang($_POST['lang'] ?? 'TR');
        $meta_keywords      = trim($_POST['meta_keywords'] ?? '');
        $translationFieldSubmitted = array_key_exists('translation_of', $_POST);
        $translation_of     = $translationFieldSubmitted && (int)$_POST['translation_of'] > 0 ? (int)$_POST['translation_of'] : null;
        $seo_title          = trim($_POST['seo_title'] ?? '');
        $seo_description    = trim($_POST['seo_description'] ?? '');

        $this->adminCheckpoint('article_store_input_parsed', [
            'endpoint' => 'article_store',
            'request_id' => $requestId,
            'title_chars' => mb_strlen($title, 'UTF-8'),
            'content_chars' => mb_strlen($content, 'UTF-8'),
            'authors_raw_count' => is_array($selectedAuthors) ? count($selectedAuthors) : 0,
            'categories_raw_count' => is_array($selectedCategories) ? count($selectedCategories) : 0,
            'status' => $status,
            'lang' => $lang,
            'publish_at' => $publish_at,
            'has_cover_file' => isset($_FILES['cover_image']),
            'cover_file_error' => $_FILES['cover_image']['error'] ?? null,
        ], $requestStarted);

        try {
            $dbStarted = microtime(true);
            $pdo = $this->getPDO();
            $this->adminCheckpoint('article_store_db_ready', [
                'endpoint' => 'article_store',
                'request_id' => $requestId,
            ], $dbStarted);

            // Yazar varlık kontrolü
            $validationStarted = microtime(true);
            $validatedAuthors = $this->validateAuthorIds($pdo, $selectedAuthors);
            if (empty($validatedAuthors)) {
                Flash::set('Kayıt başarısız: Lütfen en az bir yazar seçiniz!');
                header("Location: /yonetim/create");
                exit;
            }
            $primaryAuthorId = $validatedAuthors[0];

            // Kategori beyaz listesi
            $selectedCategories = $this->validateCategoryIds($pdo, $selectedCategories);

            $slug = !empty(trim($_POST['manual_slug'] ?? ''))
                ? $this->uniqueSlug($pdo, 'articles', trim($_POST['manual_slug']))
                : $this->uniqueSlug($pdo, 'articles', $this->createSlug($title));

            $this->adminCheckpoint('article_store_validation_complete', [
                'endpoint' => 'article_store',
                'request_id' => $requestId,
                'slug' => $slug,
                'authors_validated_count' => count($validatedAuthors),
                'primary_author_id' => $primaryAuthorId,
                'categories_validated_count' => count($selectedCategories),
                'status' => $status,
                'lang' => $lang,
                'translation_of' => $translation_of,
            ], $validationStarted);

            if (isset($_FILES['cover_image'])) {
                $uploadStarted = microtime(true);
                $this->adminCheckpoint('article_store_cover_upload_start', [
                    'endpoint' => 'article_store',
                    'request_id' => $requestId,
                    'file_name' => $_FILES['cover_image']['name'] ?? '',
                    'file_size' => $_FILES['cover_image']['size'] ?? 0,
                    'file_error' => $_FILES['cover_image']['error'] ?? null,
                ]);
                $storedPath = $this->uploadCoverOrFail($_FILES['cover_image'], $slug, '/yonetim/create', $requestId);
                if ($storedPath !== null) {
                    $image_db_path = $storedPath;
                }
                $this->adminCheckpoint('article_store_cover_upload_done', [
                    'endpoint' => 'article_store',
                    'request_id' => $requestId,
                    'stored_path' => $storedPath,
                    'upload_last_error' => Upload::lastError(),
                    'upload_last_meta' => Upload::lastMeta(),
                ], $uploadStarted);
            }

            $wordImageStarted = microtime(true);
            $wordImageResult = $this->processDeferredWordImagesOrFail(
                $content,
                $slug,
                '/yonetim/create',
                $requestId,
                $image_db_path !== '' ? [$image_db_path] : []
            );
            $content = $wordImageResult['content'];
            $wordImagePaths = $wordImageResult['paths'];
            $this->adminCheckpoint('article_store_word_images_done', [
                'endpoint' => 'article_store',
                'request_id' => $requestId,
                'uploaded_count' => count($wordImagePaths),
            ], $wordImageStarted);

            $transactionStarted = microtime(true);
            $this->adminCheckpoint('article_store_transaction_begin', [
                'endpoint' => 'article_store',
                'request_id' => $requestId,
            ]);
            $pdo->beginTransaction();

            $writeStarted = microtime(true);
            $stmt = $pdo->prepare("INSERT INTO articles (title, slug, short_desc, content, refs, image_url, author_id, status, lang, meta_keywords, translation_of, seo_title, seo_description, publish_at) VALUES (:title, :slug, :desc, :content, :refs, :img, :author_id, :status, :lang, :meta_keywords, :translation_of, :seo_title, :seo_description, :publish_at)");
            $stmt->execute([
                ':title' => $title, ':slug' => $slug, ':desc' => $desc,
                ':content' => $content,
                ':refs' => $refs,
                ':img' => $image_db_path, ':author_id' => $primaryAuthorId,
                ':status' => $status,
                ':lang' => $lang,
                ':meta_keywords' => $meta_keywords ?: null,
                ':translation_of' => $translation_of,
                ':seo_title' => $seo_title ?: null,
                ':seo_description' => $seo_description ?: null,
                ':publish_at' => $publish_at
            ]);
            $articleId = $pdo->lastInsertId();

            if (!empty($validatedAuthors)) {
                $stmtAut = $pdo->prepare("INSERT INTO article_authors (article_id, author_id, display_order) VALUES (?, ?, ?)");
                foreach ($validatedAuthors as $index => $autId) {
                    $stmtAut->execute([$articleId, $autId, $index]);
                }
            }

            if (!empty($selectedCategories)) {
                $stmtCat = $pdo->prepare("INSERT INTO article_categories (article_id, category_id) VALUES (?, ?)");
                foreach ($selectedCategories as $catId) {
                    $stmtCat->execute([$articleId, $catId]);
                }
            }

            $this->adminCheckpoint('article_store_pre_commit', [
                'endpoint' => 'article_store',
                'request_id' => $requestId,
                'article_id' => (int)$articleId,
                'authors_written' => count($validatedAuthors),
                'categories_written' => count($selectedCategories),
            ], $writeStarted);

            $pdo->commit();
            $this->adminCheckpoint('article_store_post_commit', [
                'endpoint' => 'article_store',
                'request_id' => $requestId,
                'article_id' => (int)$articleId,
            ], $transactionStarted);

            $this->markSitemapDirtyWithCheckpoint('article_store', $requestId, (int)$articleId);
            $msg = ($status === 'draft') ? 'draft_saved' : 'success';
            $this->adminCheckpoint('article_store_redirect', [
                'endpoint' => 'article_store',
                'request_id' => $requestId,
                'article_id' => (int)$articleId,
                'status' => $msg,
            ], $requestStarted);
            header('Location: /yonetim/dashboard?status=' . $msg);
        }
        catch (\Throwable $e) {
            if (isset($pdo) && $pdo instanceof \PDO && $pdo->inTransaction()) {
                $pdo->rollBack();
            }
            if ($image_db_path) {
                $this->safeUnlinkUpload($image_db_path);
            }
            foreach ($wordImagePaths as $wordImagePath) {
                $this->safeUnlinkUpload($wordImagePath);
            }
            AdminLog::write('error', 'Makale kayıt hatası.', [
                'endpoint' => 'article_store',
                'request_id' => $requestId ?? $this->requestId(),
                'exception' => get_class($e),
                'message' => $e->getMessage(),
                'stage_ms' => $this->elapsedMs($requestStarted),
            ]);
            throw new \Exception("Makale Kayıt Hatası: " . $e->getMessage());
        }
    }

    /**
     * Word import placeholder'larını yalnızca form submit aşamasında R2 URL'leriyle değiştirir.
     * Kullanıcı placeholder'ı silmişse ilgili dosya hiç yüklenmez.
     */
    private function processDeferredWordImagesOrFail(string $content, string $slug, string $redirectUrl, string $requestId, array $cleanupOnFailure = []): array
    {
        if (strpos($content, 'data-word-image-pending') === false) {
            return ['content' => $content, 'paths' => []];
        }

        $cleanupExisting = function () use ($cleanupOnFailure): void {
            foreach (array_unique(array_filter($cleanupOnFailure)) as $path) {
                $this->safeUnlinkUpload($path);
            }
        };

        if (!class_exists(\DOMDocument::class) || !class_exists(\DOMXPath::class)) {
            $cleanupExisting();
            $this->failBackWithFlash($redirectUrl, 'Word görselleri işlenemedi: Sunucuda DOM eklentisi bulunamadı.', [
                'endpoint' => 'article_store_word_images',
                'request_id' => $requestId,
            ], 'word_image_dom_missing');
        }

        $dom = new \DOMDocument('1.0', 'UTF-8');
        $previousErrors = libxml_use_internal_errors(true);
        $loaded = $dom->loadHTML('<?xml encoding="UTF-8"><!DOCTYPE html><html><body><div id="__word_image_wrapper">' . $content . '</div></body></html>');
        libxml_clear_errors();
        libxml_use_internal_errors($previousErrors);

        if (!$loaded) {
            $cleanupExisting();
            $this->failBackWithFlash($redirectUrl, 'Word görselleri işlenemedi: İçerik HTML olarak okunamadı.', [
                'endpoint' => 'article_store_word_images',
                'request_id' => $requestId,
            ], 'word_image_html_parse_failed');
        }

        $xpath = new \DOMXPath($dom);
        $nodeList = $xpath->query('//*[@data-word-image-pending]');
        $placeholders = [];
        if ($nodeList !== false) {
            foreach ($nodeList as $node) {
                if ($node instanceof \DOMElement) {
                    $placeholders[] = $node;
                }
            }
        }

        if (empty($placeholders)) {
            return ['content' => $content, 'paths' => []];
        }

        $files = $this->normalizeUploadedFileList($_FILES['word_images'] ?? []);
        $uploadedPaths = [];
        $cleanup = function () use (&$uploadedPaths, $cleanupOnFailure): void {
            foreach (array_unique(array_filter(array_merge($uploadedPaths, $cleanupOnFailure))) as $path) {
                $this->safeUnlinkUpload($path);
            }
        };
        $fail = function (string $message, array $context, string $code) use ($redirectUrl, $requestId, $cleanup): void {
            $cleanup();
            $context['endpoint'] = 'article_store_word_images';
            $context['request_id'] = $requestId;
            $this->failBackWithFlash($redirectUrl, $message, $context, $code);
        };

        foreach ($placeholders as $placeholder) {
            $indexRaw = trim($placeholder->getAttribute('data-word-image-index'));
            if ($indexRaw === '' || !ctype_digit($indexRaw)) {
                $fail('Word görseli yüklenemedi: Placeholder bilgisi eksik veya bozuk.', [
                    'placeholder_index' => $indexRaw,
                ], 'word_image_placeholder_invalid');
            }

            $index = (int)$indexRaw;
            if (!isset($files[$index])) {
                $fail('Word görseli yüklenemedi: Form dosyası sunucuya ulaşmadı.', [
                    'placeholder_index' => $index,
                    'file_count' => count($files),
                ], 'word_image_file_missing');
            }

            $file = $files[$index];
            $uploadError = Upload::imageUploadError($file, 5242880);
            if ($uploadError !== null) {
                $fail('Word görseli yüklenemedi: ' . $uploadError, [
                    'placeholder_index' => $index,
                    'name' => $file['name'] ?? '',
                    'size' => $file['size'] ?? 0,
                    'php_error' => $file['error'] ?? null,
                ], 'word_image_validation_failed');
            }

            $storedPath = Upload::saveImageToR2($file, 'uploads/content', 'word_', 5242880, $slug . '-word-' . $index);
            if ($storedPath === null) {
                $detail = Upload::lastError();
                $message = $detail !== ''
                    ? 'Word görseli yüklenemedi: ' . $detail
                    : 'Word görseli doğrulandı ama R2/CDN yüklemesi tamamlanamadı.';
                $fail($message, [
                    'placeholder_index' => $index,
                    'name' => $file['name'] ?? '',
                    'size' => $file['size'] ?? 0,
                    'slug' => $slug,
                    'detail' => $detail,
                    'meta' => Upload::lastMeta(),
                ], 'word_image_upload_failed');
            }

            $uploadedPaths[] = $storedPath;
            $url = Upload::assetUrl($storedPath);
            $img = $dom->createElement('img');
            $img->setAttribute('src', $url);
            $img->setAttribute('alt', $placeholder->getAttribute('data-word-image-name') ?: ($file['name'] ?? 'Word görseli'));
            $importId = $placeholder->getAttribute('data-import-id');
            if ($importId !== '') {
                $img->setAttribute('data-import-id', $importId);
            }

            $visibilityWarning = Upload::publicUrlWarning($storedPath, $url);
            if ($visibilityWarning !== '') {
                $img->setAttribute('data-visibility-warning', $visibilityWarning);
            }

            AdminLog::write($visibilityWarning !== '' ? 'warning' : 'info', 'Word görseli submit aşamasında yüklendi.', [
                'endpoint' => 'article_store_word_images',
                'request_id' => $requestId,
                'placeholder_index' => $index,
                'name' => $file['name'] ?? '',
                'size' => $file['size'] ?? 0,
                'path' => $storedPath,
                'url' => $url,
                'visibility_warning' => $visibilityWarning,
                'meta' => Upload::lastMeta(),
            ]);

            if ($placeholder->parentNode) {
                $placeholder->parentNode->replaceChild($img, $placeholder);
            }
        }

        $wrapper = $dom->getElementById('__word_image_wrapper');
        if (!$wrapper) {
            $cleanup();
            $this->failBackWithFlash($redirectUrl, 'Word görselleri işlenemedi: İçerik wrapper bulunamadı.', [
                'endpoint' => 'article_store_word_images',
                'request_id' => $requestId,
            ], 'word_image_wrapper_missing');
        }

        $html = '';
        foreach ($wrapper->childNodes as $child) {
            $html .= $dom->saveHTML($child);
        }

        return ['content' => $html, 'paths' => $uploadedPaths];
    }

    private function normalizeUploadedFileList(array $field): array
    {
        if (!isset($field['name'])) {
            return [];
        }

        if (!is_array($field['name'])) {
            return [0 => $field];
        }

        $files = [];
        foreach (array_keys($field['name']) as $index) {
            $files[(int)$index] = [
                'name' => $field['name'][$index] ?? '',
                'type' => $field['type'][$index] ?? '',
                'tmp_name' => $field['tmp_name'][$index] ?? '',
                'error' => $field['error'][$index] ?? UPLOAD_ERR_NO_FILE,
                'size' => $field['size'][$index] ?? 0,
            ];
        }

        return $files;
    }

    /** FK validation: var olan author_id'yi geri döndürür; aksi halde 0. */
    private function validateAuthorId(\PDO $pdo, int $id): int
    {
        if ($id <= 0) return 0;
        $stmt = $pdo->prepare("SELECT id FROM authors WHERE id = ?");
        $stmt->execute([$id]);
        return (int)($stmt->fetchColumn() ?: 0);
    }

    /** FK validation: yalnızca DB'de mevcut olan yazar id'lerini döndürür. */
    private function validateAuthorIds(\PDO $pdo, $ids): array
    {
        if (!is_array($ids) || empty($ids)) return [];
        $clean = array_values(array_unique(array_filter(array_map('intval', $ids), fn($v) => $v > 0)));
        if (empty($clean)) return [];
        $place = implode(',', array_fill(0, count($clean), '?'));
        $stmt  = $pdo->prepare("SELECT id FROM authors WHERE id IN ($place)");
        $stmt->execute($clean);
        return array_map('intval', $stmt->fetchAll(\PDO::FETCH_COLUMN));
    }

    /** FK validation: yalnızca DB'de mevcut olan kategori id'lerini döndürür. */
    private function validateCategoryIds(\PDO $pdo, $ids): array
    {
        if (!is_array($ids) || empty($ids)) return [];
        $clean = array_values(array_unique(array_filter(array_map('intval', $ids), fn($v) => $v > 0)));
        if (empty($clean)) return [];
        $place = implode(',', array_fill(0, count($clean), '?'));
        $stmt  = $pdo->prepare("SELECT id FROM categories WHERE id IN ($place)");
        $stmt->execute($clean);
        return array_map('intval', $stmt->fetchAll(\PDO::FETCH_COLUMN));
    }

    // Düzenleme sayfası
    public function edit()
    {
        if (!isset($_SESSION['admin_logged_in'])) {
            header('Location: /yonetim');
            exit;
        }

        $id = (int)($_GET['id'] ?? 0);
        if ($id <= 0) {
            header('Location: /yonetim/dashboard');
            exit;
        }

        try {
            $pdo = $this->getPDO();

            $stmt = $pdo->prepare("SELECT * FROM articles WHERE id = ?");
            $stmt->execute([$id]);
            $article = $stmt->fetch(\PDO::FETCH_ASSOC);

            if (!$article) {
                http_response_code(404);
                $this->view('errors/404_article');
                exit;
            }

            $stmtCat = $pdo->prepare("SELECT category_id FROM article_categories WHERE article_id = ?");
            $stmtCat->execute([$id]);
            $selectedCategories = $stmtCat->fetchAll(\PDO::FETCH_COLUMN);

            // Seçili yazarları getir
            $stmtAut = $pdo->prepare("SELECT author_id FROM article_authors WHERE article_id = ? ORDER BY display_order ASC");
            $stmtAut->execute([$id]);
            $selectedAuthors = $stmtAut->fetchAll(\PDO::FETCH_COLUMN);

            // Düzeltmeleri getir
            $stmtCorrs = $pdo->prepare("SELECT * FROM article_corrections WHERE article_id = ? ORDER BY created_at DESC");
            $stmtCorrs->execute([$id]);
            $corrections = $stmtCorrs->fetchAll(\PDO::FETCH_ASSOC);

            $categories = $pdo->query("SELECT * FROM categories ORDER BY name ASC")->fetchAll(\PDO::FETCH_ASSOC);
            $authors    = $pdo->query("SELECT id, name FROM authors ORDER BY name ASC")->fetchAll(\PDO::FETCH_ASSOC);
            $articlesListStmt = $pdo->prepare("SELECT id, title, lang FROM articles WHERE id != ? ORDER BY title ASC");
            $articlesListStmt->execute([$id]);
            $articlesList = $articlesListStmt->fetchAll(\PDO::FETCH_ASSOC);

            $this->view('admin/edit', [
                'article'            => $article,
                'categories'         => $categories,
                'authors'            => $authors,
                'selectedCategories' => $selectedCategories,
                'selectedAuthors'    => $selectedAuthors,
                'corrections'        => $corrections,
                'articlesList'       => $articlesList
            ]);
        }
        catch (\PDOException $e) {
            throw new \Exception("Veri Hatası: " . $e->getMessage());
        }
    }

    // Makale güncelleme
    public function update()
    {
        $requestStarted = microtime(true);
        $this->requirePost();
        $requestId = $this->requestId();

        $id                 = (int)($_POST['id'] ?? 0);
        $this->guardPostSizeOrFail($id > 0 ? '/yonetim/edit?id=' . $id : '/yonetim/dashboard', 'article_update');
        $title              = trim($_POST['title'] ?? '') ?: 'Adsiz';
        $desc               = $_POST['desc'] ?? '';
        $content            = $_POST['content'] ?? '';
        $refs               = $_POST['refs'] ?? '';
        $selectedAuthors    = $_POST['authors'] ?? [];
        if (empty($selectedAuthors) && isset($_POST['author_id']) && (int)$_POST['author_id'] > 0) {
            $selectedAuthors = [(int)$_POST['author_id']];
        }
        $selectedCategories = $_POST['categories'] ?? [];
        $current_image      = $_POST['current_image'] ?? '';
        $image_db_path      = $current_image;
        $oldImagePath       = $current_image;
        $status             = ($_POST['status'] ?? 'published') === 'draft' ? 'draft' : 'published';
        $publish_at         = !empty($_POST['publish_at']) ? date('Y-m-d H:i:s', strtotime($_POST['publish_at'])) : null;
        if ($publish_at && strtotime($publish_at) > time() && $status !== 'draft') {
            $status = 'scheduled';
        }
        $lang               = $this->normalizeNoteLang($_POST['lang'] ?? 'TR');
        $meta_keywords      = trim($_POST['meta_keywords'] ?? '');
        $translationFieldSubmitted = array_key_exists('translation_of', $_POST);
        $translation_of     = $translationFieldSubmitted && (int)$_POST['translation_of'] > 0 ? (int)$_POST['translation_of'] : null;
        $seo_title          = trim($_POST['seo_title'] ?? '');
        $seo_description    = trim($_POST['seo_description'] ?? '');
        $correction_text    = trim((string)($_POST['correction_text'] ?? ''));

        if ($id <= 0) {
            $this->adminCheckpoint('article_update_invalid_id', [
                'endpoint' => 'article_update',
                'request_id' => $requestId,
                'id' => $id,
            ], $requestStarted);
            header('Location: /yonetim/dashboard?status=invalid');
            exit;
        }

        $newImageStored = null;

        $this->adminCheckpoint('article_update_input_parsed', [
            'endpoint' => 'article_update',
            'request_id' => $requestId,
            'article_id' => $id,
            'title_chars' => mb_strlen($title, 'UTF-8'),
            'content_chars' => mb_strlen($content, 'UTF-8'),
            'authors_raw_count' => is_array($selectedAuthors) ? count($selectedAuthors) : 0,
            'categories_raw_count' => is_array($selectedCategories) ? count($selectedCategories) : 0,
            'status' => $status,
            'lang' => $lang,
            'publish_at' => $publish_at,
            'has_cover_file' => isset($_FILES['cover_image']),
            'cover_file_error' => $_FILES['cover_image']['error'] ?? null,
        ], $requestStarted);

        try {
            $dbStarted = microtime(true);
            $pdo = $this->getPDO();
            $this->adminCheckpoint('article_update_db_ready', [
                'endpoint' => 'article_update',
                'request_id' => $requestId,
                'article_id' => $id,
            ], $dbStarted);
            if (!$translationFieldSubmitted) {
                $existingTranslationStmt = $pdo->prepare('SELECT translation_of FROM articles WHERE id = ?');
                $existingTranslationStmt->execute([$id]);
                $existingTranslation = $existingTranslationStmt->fetchColumn();
                $translation_of = $existingTranslation !== false && $existingTranslation !== null ? (int)$existingTranslation : null;
            }
            $validationStarted = microtime(true);
            $validatedAuthors   = $this->validateAuthorIds($pdo, $selectedAuthors);
            if (empty($validatedAuthors)) {
                Flash::set('Güncelleme başarısız: Lütfen en az bir yazar seçiniz!');
                header("Location: /yonetim/edit?id=" . $id);
                exit;
            }
            $primaryAuthorId    = $validatedAuthors[0];
            $selectedCategories = $this->validateCategoryIds($pdo, $selectedCategories);

            $slug = !empty(trim($_POST['manual_slug'] ?? ''))
                ? $this->uniqueSlug($pdo, 'articles', trim($_POST['manual_slug']), $id)
                : $this->uniqueSlug($pdo, 'articles', $this->createSlug($title), $id);

            $this->adminCheckpoint('article_update_validation_complete', [
                'endpoint' => 'article_update',
                'request_id' => $requestId,
                'article_id' => $id,
                'slug' => $slug,
                'authors_validated_count' => count($validatedAuthors),
                'primary_author_id' => $primaryAuthorId,
                'categories_validated_count' => count($selectedCategories),
                'status' => $status,
                'lang' => $lang,
                'translation_of' => $translation_of,
            ], $validationStarted);

            if (isset($_FILES['cover_image'])) {
                $uploadStarted = microtime(true);
                $this->adminCheckpoint('article_update_cover_upload_start', [
                    'endpoint' => 'article_update',
                    'request_id' => $requestId,
                    'article_id' => $id,
                    'file_name' => $_FILES['cover_image']['name'] ?? '',
                    'file_size' => $_FILES['cover_image']['size'] ?? 0,
                    'file_error' => $_FILES['cover_image']['error'] ?? null,
                ]);
                $storedPath = $this->uploadCoverOrFail($_FILES['cover_image'], $slug, '/yonetim/edit?id=' . $id, $requestId);
                if ($storedPath !== null) {
                    $image_db_path  = $storedPath;
                    $newImageStored = $storedPath;
                }
                $this->adminCheckpoint('article_update_cover_upload_done', [
                    'endpoint' => 'article_update',
                    'request_id' => $requestId,
                    'article_id' => $id,
                    'stored_path' => $storedPath,
                    'upload_last_error' => Upload::lastError(),
                    'upload_last_meta' => Upload::lastMeta(),
                ], $uploadStarted);
            }

            $transactionStarted = microtime(true);
            $this->adminCheckpoint('article_update_transaction_begin', [
                'endpoint' => 'article_update',
                'request_id' => $requestId,
                'article_id' => $id,
            ]);
            $pdo->beginTransaction();

            $writeStarted = microtime(true);
            $sql = "UPDATE articles SET title = ?, slug = ?, short_desc = ?, content = ?, refs = ?, author_id = ?, image_url = ?, status = ?, lang = ?, meta_keywords = ?, translation_of = ?, seo_title = ?, seo_description = ?, publish_at = ?, updated_at = NOW() WHERE id = ?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$title, $slug, $desc, $content, $refs, $primaryAuthorId, $image_db_path, $status, $lang, $meta_keywords ?: null, $translation_of, $seo_title ?: null, $seo_description ?: null, $publish_at, $id]);

            // Yazarları temizle ve yeniden yaz
            $pdo->prepare("DELETE FROM article_authors WHERE article_id = ?")->execute([$id]);
            if (!empty($validatedAuthors)) {
                $stmtAut = $pdo->prepare("INSERT INTO article_authors (article_id, author_id, display_order) VALUES (?, ?, ?)");
                foreach ($validatedAuthors as $index => $autId) {
                    $stmtAut->execute([$id, $autId, $index]);
                }
            }

            $pdo->prepare("DELETE FROM article_categories WHERE article_id = ?")->execute([$id]);
            if (!empty($selectedCategories)) {
                $stmtCat = $pdo->prepare("INSERT INTO article_categories (article_id, category_id) VALUES (?, ?)");
                foreach ($selectedCategories as $catId) {
                    $stmtCat->execute([$id, $catId]);
                }
            }

            // Düzeltme metni varsa ekle
            if ($correction_text !== '') {
                $stmtCorr = $pdo->prepare("INSERT INTO article_corrections (article_id, correction_text) VALUES (?, ?)");
                $stmtCorr->execute([$id, $correction_text]);
            }

            $this->adminCheckpoint('article_update_pre_commit', [
                'endpoint' => 'article_update',
                'request_id' => $requestId,
                'article_id' => $id,
                'authors_written' => count($validatedAuthors),
                'categories_written' => count($selectedCategories),
                'correction_added' => $correction_text !== '',
            ], $writeStarted);

            $pdo->commit();

            $this->adminCheckpoint('article_update_post_commit', [
                'endpoint' => 'article_update',
                'request_id' => $requestId,
                'article_id' => $id,
            ], $transactionStarted);

            // Yeni resim yüklendiyse eski dosyayı disk'ten kaldır
            if ($newImageStored !== null && $oldImagePath !== '' && $oldImagePath !== $image_db_path) {
                $this->safeUnlinkUpload($oldImagePath);
            }

            $this->markSitemapDirtyWithCheckpoint('article_update', $requestId, $id);
            $this->adminCheckpoint('article_update_redirect', [
                'endpoint' => 'article_update',
                'request_id' => $requestId,
                'article_id' => $id,
                'status' => 'updated',
            ], $requestStarted);
            header('Location: /yonetim/dashboard?status=updated');
        }
        catch (\Throwable $e) {
            if (isset($pdo) && $pdo instanceof \PDO && $pdo->inTransaction()) {
                $pdo->rollBack();
            }
            if ($newImageStored !== null) $this->safeUnlinkUpload($newImageStored);
            AdminLog::write('error', 'Makale güncelleme hatası.', [
                'endpoint' => 'article_update',
                'request_id' => $requestId,
                'article_id' => $id,
                'exception' => get_class($e),
                'message' => $e->getMessage(),
                'stage_ms' => $this->elapsedMs($requestStarted),
            ]);
            throw new \Exception("Güncelleme Hatası: " . $e->getMessage());
        }
    }

    private function markSitemapDirtyWithCheckpoint(string $endpoint, string $requestId, int $articleId): void
    {
        $started = microtime(true);
        $this->adminCheckpoint($endpoint . '_sitemap_start', [
            'endpoint' => $endpoint,
            'request_id' => $requestId,
            'article_id' => $articleId,
        ]);

        try {
            \App\Services\SitemapService::markSitemapDirty();
            $this->adminCheckpoint($endpoint . '_sitemap_done', [
                'endpoint' => $endpoint,
                'request_id' => $requestId,
                'article_id' => $articleId,
                'ok' => true,
            ], $started);
        } catch (\Throwable $e) {
            AdminLog::write('error', 'Sitemap işaretleme hatası.', [
                'endpoint' => $endpoint,
                'request_id' => $requestId,
                'article_id' => $articleId,
                'exception' => get_class($e),
                'message' => $e->getMessage(),
            ]);
        }
    }

    /** Yalnızca /uploads/ altındaki dosyaları silmeye izin ver (path traversal koruması). */
    private function safeUnlinkUpload(string $relativePath): bool
    {
        if ($relativePath === '' || strpos($relativePath, '/uploads/') !== 0) return false;
        if (strpos($relativePath, '..') !== false) return false;

        try {
            require_once ROOT . '/app/Core/R2Storage.php';
            $r2 = \App\Core\R2Storage::instance();
            $objectKey = ltrim($relativePath, '/');
            $ok = $r2->deleteFile($objectKey);
            $webpKey = preg_replace('/\.(jpe?g|png)$/i', '.webp', $objectKey);
            if ($webpKey !== $objectKey) {
                $r2->deleteFile($webpKey);
            }
            return $ok;
        } catch (\Throwable $e) {
            $abs  = realpath(ROOT . '/public_html' . $relativePath);
            $base = realpath(ROOT . '/public_html/uploads');
            if ($abs === false || $base === false) return false;
            if (strpos($abs, $base) !== 0) return false;
            $ok = @unlink($abs);
            $webpAbs = preg_replace('/\.[^.]+$/', '.webp', $abs);
            if ($webpAbs !== $abs && is_file($webpAbs)) @unlink($webpAbs);
            return $ok;
        }
    }

    // Makale silme
    public function delete()
    {
        $this->requirePost();

        $id = (int)($_POST['id'] ?? 0);
        if ($id > 0) {
            try {
                $pdo = $this->getPDO();

                $stmt = $pdo->prepare("SELECT image_url FROM articles WHERE id = ?");
                $stmt->execute([$id]);
                $article = $stmt->fetch(\PDO::FETCH_ASSOC);

                $pdo->prepare("DELETE FROM article_categories WHERE article_id = ?")->execute([$id]);
                $pdo->prepare("DELETE FROM articles WHERE id = ?")->execute([$id]);

                if ($article && !empty($article['image_url'])) {
                    $this->safeUnlinkUpload($article['image_url']);
                }

                \App\Services\SitemapService::markSitemapDirty();
            }
            catch (\PDOException $e) {
                throw new \Exception("Makale Silme Hatası: " . $e->getMessage());
            }
        }
        header('Location: /yonetim/dashboard?status=deleted');
    }

    // Taslak yayınlama
    public function publish()
    {
        $this->requirePost();

        $id = (int)($_POST['id'] ?? 0);
        if ($id > 0) {
            try {
                $pdo = $this->getPDO();
                $pdo->prepare("UPDATE articles SET status = 'published' WHERE id = ?")
                    ->execute([$id]);
                \App\Services\SitemapService::markSitemapDirty();
            } catch (\PDOException $e) {
                throw new \Exception("Yayınlama Hatası: " . $e->getMessage());
            }
        }
        header('Location: /yonetim/dashboard?status=published');
    }

    private function uploadCoverOrFail(array $file, string $slug, string $redirectUrl, string $requestId = ''): ?string
    {
        if (($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
            return null;
        }

        $requestId = $requestId !== '' ? $requestId : $this->requestId();

        $uploadError = Upload::imageUploadError($file, 5242880);
        if ($uploadError !== null) {
            $this->failBackWithFlash($redirectUrl, 'Kapak görseli yüklenemedi: ' . $uploadError, [
                'endpoint' => 'coverUpload',
                'request_id' => $requestId,
                'name' => $file['name'] ?? '',
                'size' => $file['size'] ?? 0,
                'php_error' => $file['error'] ?? null,
            ], 'cover_validation_failed');
        }

        $storedPath = Upload::saveImageToR2($file, 'uploads/covers', 'cover_', 5242880, $slug);
        if ($storedPath === null) {
            $detail = Upload::lastError();
            $message = $detail !== ''
                ? 'Kapak görseli yüklenemedi: ' . $detail
                : 'Kapak görseli doğrulandı ama R2/CDN yüklemesi tamamlanamadı.';
            $this->failBackWithFlash($redirectUrl, $message, [
                'endpoint' => 'coverUpload',
                'request_id' => $requestId,
                'name' => $file['name'] ?? '',
                'size' => $file['size'] ?? 0,
                'slug' => $slug,
                'detail' => $detail,
            ], 'cover_upload_failed');
        }

        AdminLog::write('info', 'Kapak görseli yüklendi.', [
            'endpoint' => 'coverUpload',
            'request_id' => $requestId,
            'name' => $file['name'] ?? '',
            'size' => $file['size'] ?? 0,
            'path' => $storedPath,
            'meta' => Upload::lastMeta(),
        ]);

        return $storedPath;
    }

    // Summernote resim yükleme — her durumda JSON döner.
    public function uploadContentImage()
    {
        header('Content-Type: application/json; charset=utf-8');
        $requestStarted = microtime(true);
        $requestId = $this->requestId();
        $baseContext = $this->uploadTelemetryContext([], $requestId);

        if (!isset($_SESSION['admin_logged_in'])) {
            $this->jsonError(403, 'Oturum bulunamadı. Sayfayı yenileyip tekrar giriş yapın.', [
                ...$baseContext,
            ], 'unauthorized');
        }

        if (!isset($_FILES['file'])) {
            $this->jsonError(400, 'Yüklenecek dosya alınamadı.', [
                ...$baseContext,
            ], 'missing_file');
        }

        $file = $_FILES['file'];
        $context = $this->uploadTelemetryContext($file, $requestId);
        $validateStarted = microtime(true);
        $uploadError = Upload::imageUploadError($file, 5242880);
        $context['timings_ms']['validation'] = $this->elapsedMs($validateStarted);
        if ($uploadError !== null) {
            $context['total_ms'] = $this->elapsedMs($requestStarted);
            $this->jsonError(400, $uploadError, $context, 'image_validation_failed');
        }

        try {
            $slugSeed = trim((string)($_POST['slug'] ?? ''));
            $storedPath = Upload::saveImageToR2($file, 'uploads/content', 'content_', 5242880, $slugSeed);
            $meta = Upload::lastMeta();
            $context['slug'] = $slugSeed;
            $context['meta'] = $meta;
            if ($storedPath === null) {
                $detail = Upload::lastError();
                $message = $detail !== ''
                    ? 'Görsel yüklenemedi: ' . $detail
                    : 'Görsel doğrulandı ama R2/CDN yüklemesi tamamlanamadı. R2 anahtarları, bucket veya PHP GD/WebP desteği kontrol edilmeli.';
                $context['detail'] = $detail;
                $context['total_ms'] = $this->elapsedMs($requestStarted);
                $this->jsonError(500, $message, $context, 'image_upload_failed');
            }

            $url = Upload::assetUrl($storedPath);
            $visibilityWarning = Upload::publicUrlWarning($storedPath, $url);
            $context['path'] = $storedPath;
            $context['url'] = $url;
            $context['total_ms'] = $this->elapsedMs($requestStarted);
            if ($visibilityWarning !== '') {
                $context['visibility_warning'] = $visibilityWarning;
            }

            AdminLog::write($visibilityWarning !== '' ? 'warning' : 'info', 'Görsel yüklendi.', $context);

            $response = [
                'success' => true,
                'url' => $url,
                'request_id' => $requestId,
                'path' => $storedPath,
                'meta' => $meta,
            ];
            if ($visibilityWarning !== '') {
                $response['visibility_warning'] = $visibilityWarning;
            }
            echo json_encode($response, JSON_UNESCAPED_UNICODE);
        } catch (\Throwable $e) {
            $this->jsonError(500, 'Görsel yüklenirken sistem hatası oluştu: ' . $e->getMessage(), [
                ...$context,
                'total_ms' => $this->elapsedMs($requestStarted),
            ], 'image_system_error');
        }
        exit;
    }

    // --- Kategori yönetimi ---

    public function categories()
    {
        if (!isset($_SESSION['admin_logged_in'])) {
            header('Location: /yonetim');
            exit;
        }

        try {
            $pdo = $this->getPDO();

            $sql = "SELECT c.*,
                    (SELECT COUNT(*) FROM article_categories ac
                     JOIN articles a ON ac.article_id = a.id
                     WHERE ac.category_id = c.id AND a.status = 'published') as article_count,
                    (SELECT COUNT(*) FROM note_categories nc
                     JOIN notes n ON nc.note_id = n.id
                     WHERE nc.category_id = c.id) as note_count
                    FROM categories c
                    ORDER BY c.name ASC";

            $categories = $pdo->query($sql)->fetchAll(\PDO::FETCH_ASSOC);
            $this->view('admin/categories', ['categories' => $categories]);
        }
        catch (\PDOException $e) {
            throw new \Exception("Kategori Listesi Hatası: " . $e->getMessage());
        }
    }

    public function storeCategory()
    {
        $this->requirePost();

        $name = mb_strtoupper(trim($_POST['name'] ?? ''), 'UTF-8');
        if ($name === '') {
            header('Location: /yonetim/categories?status=empty');
            exit;
        }

        try {
            $pdo  = $this->getPDO();
            $slug = $this->uniqueSlug($pdo, 'categories', $this->createSlug($name));
            $pdo->prepare("INSERT INTO categories (name, slug) VALUES (?, ?)")
                ->execute([$name, $slug]);
            header('Location: /yonetim/categories?status=success');
            exit;
        }
        catch (\PDOException $e) {
            throw new \Exception("Kategori Kayıt Hatası: " . $e->getMessage());
        }
    }

    public function deleteCategory()
    {
        $this->requirePost();

        $id = (int)($_POST['id'] ?? 0);
        if ($id > 0) {
            try {
                $pdo = $this->getPDO();
                $pdo->prepare("DELETE FROM categories WHERE id = ?")->execute([$id]);
            }
            catch (\PDOException $e) {
                throw new \Exception("Silme Hatası: " . $e->getMessage());
            }
        }
        header('Location: /yonetim/categories?status=deleted');
        exit;
    }
}
