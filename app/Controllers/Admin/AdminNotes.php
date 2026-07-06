<?php

trait AdminNotes
{
    private function normalizeNoteLang(string $lang): string
    {
        $lang = strtoupper(trim($lang));
        $allowed = ['TR', 'EN'];
        return in_array($lang, $allowed, true) ? $lang : 'TR';
    }

    private function pdfUploadError(array $file, int $maxBytes = 52428800): ?string
    {
        $error = $file['error'] ?? UPLOAD_ERR_NO_FILE;
        if ($error !== UPLOAD_ERR_OK) {
            $map = [
                UPLOAD_ERR_INI_SIZE => 'PDF sunucu limitinden büyük.',
                UPLOAD_ERR_FORM_SIZE => 'PDF form limitinden büyük.',
                UPLOAD_ERR_PARTIAL => 'PDF yüklemesi yarım kaldı.',
                UPLOAD_ERR_NO_FILE => 'PDF dosyası seçilmedi.',
                UPLOAD_ERR_NO_TMP_DIR => 'Geçici yükleme klasörü yok.',
                UPLOAD_ERR_CANT_WRITE => 'PDF diske yazılamadı.',
                UPLOAD_ERR_EXTENSION => 'PDF yüklemesi PHP eklentisi tarafından durduruldu.',
            ];
            return $map[$error] ?? 'PDF yüklenemedi.';
        }

        $size = (int)($file['size'] ?? 0);
        if ($size <= 0) {
            return 'PDF dosyası boş görünüyor.';
        }
        if ($size > $maxBytes) {
            return 'PDF 50 MB sınırını aşıyor.';
        }

        $tmp = (string)($file['tmp_name'] ?? '');
        if ($tmp === '' || !is_uploaded_file($tmp)) {
            return 'PDF geçici dosyası doğrulanamadı.';
        }

        $ext = strtolower(pathinfo((string)($file['name'] ?? ''), PATHINFO_EXTENSION));
        if ($ext !== 'pdf') {
            return 'Yalnızca PDF dosyası yüklenebilir.';
        }

        $mime = '';
        if (function_exists('finfo_open')) {
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            if ($finfo) {
                $mime = (string)finfo_file($finfo, $tmp);
                finfo_close($finfo);
            }
        }
        if ($mime !== '' && !in_array($mime, ['application/pdf', 'application/x-pdf'], true)) {
            return 'Dosya türü PDF olarak doğrulanamadı.';
        }

        $handle = @fopen($tmp, 'rb');
        if (!$handle) {
            return 'PDF dosyası okunamadı.';
        }
        $magic = fread($handle, 5);
        fclose($handle);
        if ($magic !== '%PDF-') {
            return 'PDF imzası geçersiz.';
        }

        return null;
    }

    public function addNote()
    {
        if (!isset($_SESSION['admin_logged_in'])) { header('Location: /yonetim'); exit; }

        try {
            $pdo = $this->getPDO();
            $categories = $pdo->query("SELECT * FROM categories ORDER BY name ASC")->fetchAll(\PDO::FETCH_ASSOC);

            $notes = $pdo->query("SELECT n.*, GROUP_CONCAT(c.name SEPARATOR ', ') as category_names
                                  FROM notes n
                                  LEFT JOIN note_categories nc ON n.id = nc.note_id
                                  LEFT JOIN categories c ON nc.category_id = c.id
                                  GROUP BY n.id
                                  ORDER BY n.created_at DESC")->fetchAll(\PDO::FETCH_ASSOC);

            $stats = $pdo->query("SELECT COUNT(*) as total_count, SUM(file_size) as total_size FROM notes")->fetch(\PDO::FETCH_ASSOC);

            $this->view('admin/add-note', [
                'categories' => $categories,
                'notes'      => $notes,
                'stats'      => $stats,
            ]);
        }
        catch (\PDOException $e) {
            throw new \Exception("Not Listesi Hatası: " . $e->getMessage());
        }
    }

    public function storeNote()
    {
        $requestStarted = microtime(true);
        $this->requirePost();
        $this->guardPostSizeOrFail('/yonetim/add-note', 'storeNote');
        $requestId = $this->requestId();

        $title              = trim($_POST['title'] ?? '');
        $desc               = $_POST['description'] ?? '';
        $uploader_name      = $_SESSION['admin_name'] ?? 'yonetim';
        $selectedCategories = $_POST['categories'] ?? [];
        $lang               = $this->normalizeNoteLang((string)($_POST['lang'] ?? 'TR'));

        $this->adminCheckpoint('storeNote_input_parsed', [
            'endpoint' => 'storeNote',
            'request_id' => $requestId,
            'title_chars' => mb_strlen($title, 'UTF-8'),
            'has_pdf_file' => isset($_FILES['pdf_file']),
            'pdf_file_error' => $_FILES['pdf_file']['error'] ?? null,
            'pdf_file_size' => $_FILES['pdf_file']['size'] ?? 0,
            'categories_raw_count' => is_array($selectedCategories) ? count($selectedCategories) : 0,
            'lang' => $lang,
        ], $requestStarted);

        if ($title === '') {
            $this->failBackWithFlash('/yonetim/add-note', 'Not kaydedilemedi: Başlık zorunlu.', [
                'endpoint' => 'storeNote',
                'request_id' => $requestId,
            ], 'note_title_missing');
        }

        $pdfError = isset($_FILES['pdf_file']) ? $this->pdfUploadError($_FILES['pdf_file']) : 'PDF dosyası seçilmedi.';
        if ($pdfError !== null) {
            $this->failBackWithFlash('/yonetim/add-note', $pdfError, [
                'endpoint' => 'storeNote',
                'request_id' => $requestId,
                'detail' => $pdfError,
                'name' => $_FILES['pdf_file']['name'] ?? '',
                'size' => $_FILES['pdf_file']['size'] ?? 0,
                'php_error' => $_FILES['pdf_file']['error'] ?? null,
            ], 'invalid_pdf');
        }

        $r2Path = null;
        try {
            $pdo  = $this->getPDO();
            $selectedCategories = $this->validateCategoryIds($pdo, $selectedCategories);

            // Türkçe karakter güvenli slug + benzersizlik
            $slug = $this->uniqueSlug($pdo, 'notes', $this->createSlug($title));

            $this->adminCheckpoint('storeNote_validation_complete', [
                'endpoint' => 'storeNote',
                'request_id' => $requestId,
                'slug' => $slug,
                'categories_validated_count' => count($selectedCategories),
            ]);

            require_once ROOT . '/app/Core/R2Storage.php';
            $r2 = \App\Core\R2Storage::instance();
            $r2Path = $r2->uploadPDF($_FILES['pdf_file']['tmp_name'], $slug);

            if (!$r2Path) {
                throw new \Exception('Cloudflare R2 yükleme hatası oluştu: ' . json_encode($r2->getLastAwsError(), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
            }

            $this->adminCheckpoint('storeNote_pdf_upload_done', [
                'endpoint' => 'storeNote',
                'request_id' => $requestId,
                'r2_path' => $r2Path,
            ]);

            $pdo->beginTransaction();
            $stmt = $pdo->prepare("INSERT INTO notes (title, slug, description, r2_path, uploader_name, file_size, lang) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$title, $slug, $desc, $r2Path, $uploader_name, (int)$_FILES['pdf_file']['size'], $lang]);
            $noteId = (int)$pdo->lastInsertId();

            if (!empty($selectedCategories)) {
                $stmtCat = $pdo->prepare("INSERT INTO note_categories (note_id, category_id) VALUES (?, ?)");
                foreach ($selectedCategories as $catId) {
                    $stmtCat->execute([$noteId, $catId]);
                }
            }

            $pdo->commit();
            \App\Services\SitemapService::markSitemapDirty();

            $this->adminCheckpoint('storeNote_redirect', [
                'endpoint' => 'storeNote',
                'request_id' => $requestId,
                'note_id' => $noteId,
                'r2_path' => $r2Path,
            ], $requestStarted);

            Flash::set('Not ve PDF başarıyla kaydedildi.');
            header('Location: /yonetim/add-note?status=success');
            exit;
        }
        catch (\Throwable $e) {
            if (isset($pdo) && $pdo->inTransaction()) {
                $pdo->rollBack();
            }
            // R2'ye yüklenmiş ama DB'ye yazılamamış dosyayı temizle (yetim önleme)
            if ($r2Path) {
                try {
                    if (!isset($r2)) {
                        require_once ROOT . '/app/Core/R2Storage.php';
                        $r2 = \App\Core\R2Storage::instance();
                    }
                    $r2->deletePDF($r2Path);
                } catch (\Throwable $cleanupErr) {
                    AdminLog::write('error', 'Not PDF cleanup başarısız.', [
                        'endpoint' => 'storeNote',
                        'request_id' => $requestId,
                        'r2_path' => $r2Path,
                        'exception' => get_class($cleanupErr),
                        'message' => $cleanupErr->getMessage(),
                    ]);
                }
            }
            AdminLog::write('error', 'Not kayıt hatası.', [
                'endpoint' => 'storeNote',
                'request_id' => $requestId,
                'exception' => get_class($e),
                'message' => $e->getMessage(),
                'stage_ms' => $this->elapsedMs($requestStarted),
            ]);
            Flash::set('Not kaydedilemedi. Lütfen PDF/R2 ayarlarını kontrol edin. (Hata kodu: ' . $requestId . ')');
            header('Location: /yonetim/add-note?error=system_failure');
            exit;
        }
    }

    public function deleteNote()
    {
        $requestStarted = microtime(true);
        $this->requirePost();
        $requestId = $this->requestId();

        $id = (int)($_POST['id'] ?? 0);
        if ($id > 0) {
            try {
                $pdo = $this->getPDO();
                $stmt = $pdo->prepare("SELECT r2_path FROM notes WHERE id = ?");
                $stmt->execute([$id]);
                $note = $stmt->fetch(\PDO::FETCH_ASSOC);

                if ($note) {
                    // Önce R2'den dene; başarısız olursa DB kaydı kalır (kullanıcı tekrar deneyebilir)
                    require_once ROOT . '/app/Core/R2Storage.php';
                    $r2 = \App\Core\R2Storage::instance();
                    if (!$r2->deletePDF($note['r2_path'])) {
                        AdminLog::write('error', 'Not PDF R2 silme başarısız.', [
                            'endpoint' => 'deleteNote',
                            'request_id' => $requestId,
                            'note_id' => $id,
                            'r2_path' => $note['r2_path'],
                            'aws_error' => $r2->getLastAwsError(),
                            'stage_ms' => $this->elapsedMs($requestStarted),
                        ]);
                        Flash::set('Not silinemedi: PDF R2 üzerinden kaldırılamadı. (Hata kodu: ' . $requestId . ')');
                        header('Location: /yonetim/add-note?error=delete_failed');
                        exit;
                    }

                    $pdo->prepare("DELETE FROM notes WHERE id = ?")->execute([$id]);
                    \App\Services\SitemapService::markSitemapDirty();
                }
            }
            catch (\Throwable $e) {
                AdminLog::write('error', 'Not silme hatası.', [
                    'endpoint' => 'deleteNote',
                    'request_id' => $requestId,
                    'note_id' => $id,
                    'exception' => get_class($e),
                    'message' => $e->getMessage(),
                    'stage_ms' => $this->elapsedMs($requestStarted),
                ]);
                Flash::set('Not silinemedi. (Hata kodu: ' . $requestId . ')');
                header('Location: /yonetim/add-note?error=delete_failed');
                exit;
            }
        }
        header('Location: /yonetim/add-note?status=deleted');
        exit;
    }

    public function editNote()
    {
        if (!isset($_SESSION['admin_logged_in'])) { header('Location: /yonetim'); exit; }

        $id = (int)($_GET['id'] ?? 0);
        if ($id <= 0) { header('Location: /yonetim/add-note?error=note_not_found'); exit; }

        try {
            $pdo = $this->getPDO();

            $stmt = $pdo->prepare("SELECT * FROM notes WHERE id = ?");
            $stmt->execute([$id]);
            $note = $stmt->fetch(\PDO::FETCH_ASSOC);

            if (!$note) {
                http_response_code(404);
                header('Location: /yonetim/add-note?error=note_not_found');
                exit;
            }

            $cats = $pdo->query("SELECT * FROM categories ORDER BY name ASC")->fetchAll(\PDO::FETCH_ASSOC);

            $noteCatsStmt = $pdo->prepare("SELECT category_id FROM note_categories WHERE note_id = ?");
            $noteCatsStmt->execute([$id]);
            $noteCategoryIds = $noteCatsStmt->fetchAll(\PDO::FETCH_COLUMN);

            $this->view('admin/edit-note', [
                'note'            => $note,
                'categories'      => $cats,
                'noteCategoryIds' => $noteCategoryIds,
            ]);
        }
        catch (\PDOException $e) {
            throw new \Exception("Veritabanı Hatası: " . $e->getMessage());
        }
    }

    public function updateNote()
    {
        $requestStarted = microtime(true);
        $this->requirePost();

        $id                 = (int)($_POST['id'] ?? 0);
        $this->guardPostSizeOrFail($id > 0 ? '/yonetim/edit-note?id=' . $id : '/yonetim/add-note', 'updateNote');
        $requestId = $this->requestId();
        $title              = trim($_POST['title'] ?? '');
        $desc               = $_POST['description'] ?? '';
        $lang               = $this->normalizeNoteLang((string)($_POST['lang'] ?? 'TR'));
        $selectedCategories = $_POST['categories'] ?? [];
        $hasNewPdf          = isset($_FILES['pdf_file']) && $_FILES['pdf_file']['error'] !== UPLOAD_ERR_NO_FILE;

        if ($id <= 0 || $title === '') {
            Flash::set('Not güncellenemedi: Eksik veya geçersiz veri.');
            header('Location: /yonetim/add-note?error=missing_data');
            exit;
        }

        $this->adminCheckpoint('updateNote_input_parsed', [
            'endpoint' => 'updateNote',
            'request_id' => $requestId,
            'note_id' => $id,
            'title_chars' => mb_strlen($title, 'UTF-8'),
            'has_new_pdf' => $hasNewPdf,
            'pdf_file_error' => $_FILES['pdf_file']['error'] ?? null,
            'pdf_file_size' => $_FILES['pdf_file']['size'] ?? 0,
            'categories_raw_count' => is_array($selectedCategories) ? count($selectedCategories) : 0,
            'lang' => $lang,
        ], $requestStarted);

        try {
            $pdo = $this->getPDO();
            $selectedCategories = $this->validateCategoryIds($pdo, $selectedCategories);
            $newR2Path = null;
            $oldR2PathToDelete = null;

            $pdo->beginTransaction();

            if ($hasNewPdf) {
                $pdfError = $this->pdfUploadError($_FILES['pdf_file']);
                if ($pdfError !== null) {
                    AdminLog::write('warning', 'Not PDF güncellemesi reddedildi.', [
                        'endpoint' => 'updateNote',
                        'request_id' => $requestId,
                        'detail' => $pdfError,
                        'name' => $_FILES['pdf_file']['name'] ?? '',
                    ]);
                    Flash::set($pdfError);
                    $pdo->rollBack();
                    header('Location: /yonetim/edit-note?id=' . $id . '&error=invalid_pdf');
                    exit;
                }

                // Güncellemeden önce eski R2 yolunu al
                $oldStmt = $pdo->prepare("SELECT r2_path FROM notes WHERE id = ?");
                $oldStmt->execute([$id]);
                $oldR2Path = $oldStmt->fetchColumn();

                require_once ROOT . '/app/Core/R2Storage.php';
                $r2 = \App\Core\R2Storage::instance();
                $newR2Path = $r2->uploadPDF($_FILES['pdf_file']['tmp_name'], $this->createSlug($title));

                if (!$newR2Path) {
                    throw new \Exception('Cloudflare R2 yükleme hatası oluştu: ' . json_encode($r2->getLastAwsError(), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
                }

                $stmt = $pdo->prepare("UPDATE notes SET title = ?, description = ?, lang = ?, r2_path = ?, file_size = ?, updated_at = NOW() WHERE id = ?");
                $stmt->execute([$title, $desc, $lang, $newR2Path, (int)$_FILES['pdf_file']['size'], $id]);

                if ($oldR2Path && $oldR2Path !== $newR2Path) {
                    $oldR2PathToDelete = $oldR2Path;
                }
            } else {
                $stmt = $pdo->prepare("UPDATE notes SET title = ?, description = ?, lang = ?, updated_at = NOW() WHERE id = ?");
                $stmt->execute([$title, $desc, $lang, $id]);
            }

            $pdo->prepare("DELETE FROM note_categories WHERE note_id = ?")->execute([$id]);

            if (!empty($selectedCategories)) {
                $stmtCat = $pdo->prepare("INSERT INTO note_categories (note_id, category_id) VALUES (?, ?)");
                foreach ($selectedCategories as $catId) {
                    $stmtCat->execute([$id, $catId]);
                }
            }

            $pdo->commit();
            \App\Services\SitemapService::markSitemapDirty();

            // Nesne depolama işlemsel değildir. Önceki PDF'i yalnızca DB commit başarılı olduktan sonra sil ki rollback asla silinmiş bir nesneye işaret etmesin.
            if ($oldR2PathToDelete) {
                try {
                    if (!isset($r2)) {
                        require_once ROOT . '/app/Core/R2Storage.php';
                        $r2 = \App\Core\R2Storage::instance();
                    }
                    if (!$r2->deletePDF($oldR2PathToDelete)) {
                        AdminLog::write('error', 'Eski not PDF R2 silme başarısız.', [
                            'endpoint' => 'updateNote',
                            'request_id' => $requestId,
                            'note_id' => $id,
                            'r2_path' => $oldR2PathToDelete,
                            'aws_error' => $r2->getLastAwsError(),
                        ]);
                    }
                } catch (\Throwable $e) {
                    AdminLog::write('error', 'Eski not PDF cleanup istisnası.', [
                        'endpoint' => 'updateNote',
                        'request_id' => $requestId,
                        'note_id' => $id,
                        'exception' => get_class($e),
                        'message' => $e->getMessage(),
                    ]);
                }
            }

            $this->adminCheckpoint('updateNote_redirect', [
                'endpoint' => 'updateNote',
                'request_id' => $requestId,
                'note_id' => $id,
                'has_new_pdf' => $hasNewPdf,
            ], $requestStarted);

            Flash::set($hasNewPdf ? 'Not ve PDF başarıyla güncellendi.' : 'Not başarıyla güncellendi.');
            header('Location: /yonetim/add-note?status=updated');
            exit;
        }
        catch (\Throwable $e) {
            if (isset($pdo) && $pdo->inTransaction()) $pdo->rollBack();
            if (!empty($newR2Path)) {
                try {
                    require_once ROOT . '/app/Core/R2Storage.php';
                    \App\Core\R2Storage::instance()->deletePDF($newR2Path);
                } catch (\Throwable $cleanupErr) {
                    AdminLog::write('error', 'Güncelleme sonrası yeni PDF cleanup başarısız.', [
                        'endpoint' => 'updateNote',
                        'request_id' => $requestId,
                        'note_id' => $id,
                        'r2_path' => $newR2Path,
                        'exception' => get_class($cleanupErr),
                        'message' => $cleanupErr->getMessage(),
                    ]);
                }
            }
            AdminLog::write('error', 'Not güncelleme hatası.', [
                'endpoint' => 'updateNote',
                'request_id' => $requestId,
                'note_id' => $id,
                'exception' => get_class($e),
                'message' => $e->getMessage(),
                'stage_ms' => $this->elapsedMs($requestStarted),
            ]);
            Flash::set('Not güncellenemedi. (Hata kodu: ' . $requestId . ')');
            header('Location: /yonetim/edit-note?id=' . $id . '&error=system_failure');
            exit;
        }
    }
}
