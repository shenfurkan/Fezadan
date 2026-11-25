<?php

trait AdminPortfolio
{
    private function portfolioObjectKey(string $path): ?string
    {
        if (preg_match('#^https?://#i', $path)) {
            $path = (string)parse_url($path, PHP_URL_PATH);
        }

        $key = ltrim($path, '/');
        return strpos($key, 'portfolio/') === 0 ? $key : null;
    }

    public function portfolio()
    {
        if (!isset($_SESSION['admin_logged_in'])) {
            header('Location: /yonetim');
            exit;
        }

        try {
            $pdo = Db::pdo();
            $items = $pdo->query("SELECT * FROM portfolio_images ORDER BY display_order ASC, id DESC")->fetchAll(\PDO::FETCH_ASSOC);
            $this->view('admin/portfolio_manager', [
                'items' => $items
            ]);
        } catch (\Exception $e) {
            error_log("Portfolyo Verisi Çekilemedi: " . $e->getMessage());
            $this->view('admin/portfolio_manager', [
                'items' => []
            ]);
        }
    }

    public function storePortfolio()
    {
        $this->requirePost();

        $isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
        $portfolioId = 0;

        try {
            $title_tr = trim((string)($_POST['title_tr'] ?? ''));
            $title_en = trim((string)($_POST['title_en'] ?? '')) ?: null;
            $description_tr = trim((string)($_POST['description_tr'] ?? '')) ?: null;
            $description_en = trim((string)($_POST['description_en'] ?? '')) ?: null;
            $type = 'photo';
            $display_order = (int)($_POST['display_order'] ?? 0);

            $image = $_FILES['image'] ?? null;
            if (!is_array($image)
                || !isset($image['error'], $image['name'], $image['tmp_name'], $image['size'])
                || is_array($image['error'])
                || is_array($image['name'])
                || is_array($image['tmp_name'])
                || is_array($image['size'])) {
                throw new \InvalidArgumentException('Yükleme verisi geçersiz. Lütfen görseli yeniden seçip tekrar deneyin.');
            }

            if ($image['error'] === UPLOAD_ERR_NO_FILE) {
                throw new \Exception("Lütfen yüklemek için bir görsel seçin.");
            }

            // Orijinal dosya kalitesinde R2'ye yükle (WebP dönüşümü/sıkıştırma yapmadan)
            $storedPath = Upload::saveOriginalImageToR2($image, 'portfolio', 'port_', 104857600, 'portfolio');
            if ($storedPath === null) {
                throw new \Exception("Görsel yüklenemedi: " . Upload::lastError());
            }

            $pdo = Db::pdo();
            $stmt = $pdo->prepare("INSERT INTO portfolio_images (type, image_url, title_tr, title_en, description_tr, description_en, display_order) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([
                $type,
                $storedPath,
                $title_tr,
                $title_en,
                $description_tr,
                $description_en,
                $display_order
            ]);
            $portfolioId = (int) $pdo->lastInsertId();
            \App\Services\SitemapService::markSitemapDirty();

            try {
                AdminLog::write('info', 'Portfolyo görseli yüklendi.', [
                    'endpoint' => 'storePortfolio',
                    'path' => $storedPath,
                    'title' => $title_tr
                ]);
            } catch (\Throwable $logError) {
                error_log('[PortfolioUpload] success log failed: ' . $logError->getMessage());
            }

            // AJAX akışında session'a yazmıyoruz — JS toast gösteriyor.
            // Yalnızca doğrudan form submit (sayfa yenileme) için set et.
            if (!$isAjax) {
                $_SESSION['success'] = "Görsel başarıyla eklendi.";
            }
        } catch (\Throwable $e) {
            $message = $e instanceof \Exception
                ? $e->getMessage()
                : 'Görsel yüklenirken beklenmeyen bir sunucu hatası oluştu.';
            error_log('[PortfolioUpload] ' . get_class($e) . ': ' . $e->getMessage());
            try {
                AdminLog::write('error', 'Portfolyo görseli yüklenemedi.', [
                    'endpoint' => 'storePortfolio',
                    'exception' => get_class($e),
                    'message' => $e->getMessage(),
                    'content_length' => (int)($_SERVER['CONTENT_LENGTH'] ?? 0),
                ]);
            } catch (\Throwable $logError) {
                error_log('[PortfolioUpload] failure log failed: ' . $logError->getMessage());
            }
            if ($isAjax) {
                header('Content-Type: application/json; charset=utf-8');
                http_response_code(400);
                echo json_encode(['success' => false, 'error' => $message], JSON_UNESCAPED_UNICODE);
                exit;
            }
            $_SESSION['error'] = $message;
        }

        if ($isAjax) {
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['success' => true, 'id' => $portfolioId, 'edit_url' => '/yonetim/portfolio-edit?id=' . $portfolioId], JSON_UNESCAPED_UNICODE);
            exit;
        }

        header('Location: /yonetim/portfolio');
        exit;
    }

    public function portfolioStore()
    {
        $this->storePortfolio();
    }

    public function portfolioEdit()
    {
        if (!isset($_SESSION['admin_logged_in'])) {
            header('Location: /yonetim');
            exit;
        }

        $id = (int)($_GET['id'] ?? 0);
        try {
            $pdo = Db::pdo();
            $stmt = $pdo->prepare("SELECT * FROM portfolio_images WHERE id = ?");
            $stmt->execute([$id]);
            $item = $stmt->fetch(\PDO::FETCH_ASSOC);

            if (!$item) {
                Flash::set('Düzenlenmek istenen portfolyo ögesi bulunamadı.');
                header('Location: /yonetim/portfolio');
                exit;
            }

            $this->view('admin/portfolio_edit', [
                'item' => $item
            ]);
        } catch (\Exception $e) {
            throw new \Exception("Portfolyo Düzenleme Hatası: " . $e->getMessage(), 0, $e);
        }
    }

    public function updatePortfolio()
    {
        $this->requirePost();

        $id = (int)($_POST['id'] ?? 0);
        $title_tr = trim((string)($_POST['title_tr'] ?? ''));
        $title_en = trim($_POST['title_en'] ?? '') ?: null;
        $description_tr = trim($_POST['description_tr'] ?? '') ?: null;
        $description_en = trim($_POST['description_en'] ?? '') ?: null;
        $type = $_POST['type'] ?? 'photo';
        if (!in_array($type, ['photo', 'drawing'], true)) {
            $type = 'photo';
        }
        $display_order = (int)($_POST['display_order'] ?? 0);

        try {
            $pdo = Db::pdo();
            // Mevcut görseli kontrol et
            $stmt = $pdo->prepare("SELECT * FROM portfolio_images WHERE id = ?");
            $stmt->execute([$id]);
            $item = $stmt->fetch(\PDO::FETCH_ASSOC);
            if (!$item) {
                throw new \Exception("Güncellenecek öge bulunamadı.");
            }

            $image_url = $item['image_url'];

            // Eğer yeni bir görsel yüklendiyse
            if (isset($_FILES['image']) && $_FILES['image']['error'] !== UPLOAD_ERR_NO_FILE) {
                // R2'ye orijinal formatta yükle
                $storedPath = Upload::saveOriginalImageToR2($_FILES['image'], 'portfolio', 'port_', 104857600, 'portfolio');
                if ($storedPath === null) {
                    throw new \Exception("Görsel güncellenemedi: " . Upload::lastError());
                }

                // Eski görseli R2'den sil
                try {
                    require_once ROOT . '/app/Core/R2Storage.php';
                    $r2 = \App\Core\R2Storage::instance();
                    $oldKey = $this->portfolioObjectKey((string)$item['image_url']);
                    if ($oldKey !== null) {
                        $r2->deleteObject($oldKey);
                    }
                } catch (\Throwable $e) {
                    error_log("Eski portfolyo görseli R2'den silinirken hata: " . $e->getMessage());
                }

                $image_url = $storedPath;
            }

            $stmtUpdate = $pdo->prepare("UPDATE portfolio_images SET type = ?, image_url = ?, title_tr = ?, title_en = ?, description_tr = ?, description_en = ?, display_order = ? WHERE id = ?");
            $stmtUpdate->execute([
                $type,
                $image_url,
                $title_tr,
                $title_en,
                $description_tr,
                $description_en,
                $display_order,
                $id
            ]);

            AdminLog::write('info', 'Albüm görseli güncellendi.', [
                'endpoint' => 'updatePortfolio',
                'id' => $id
            ]);

            $_SESSION['success'] = "Albüm görseli başarıyla güncellendi.";
            \App\Services\SitemapService::markSitemapDirty();
            header('Location: /yonetim/portfolio');
            exit;
        } catch (\Exception $e) {
            $_SESSION['error'] = $e->getMessage();
            header('Location: /yonetim/portfolio-edit?id=' . $id);
            exit;
        }
    }

    public function portfolioUpdate()
    {
        $this->updatePortfolio();
    }

    public function deletePortfolio()
    {
        $this->requirePost();

        $id = (int)($_POST['id'] ?? 0);
        try {
            $pdo = Db::pdo();
            $stmt = $pdo->prepare("SELECT * FROM portfolio_images WHERE id = ?");
            $stmt->execute([$id]);
            $item = $stmt->fetch(\PDO::FETCH_ASSOC);

            if (!$item) {
                throw new \Exception("Silinecek öge bulunamadı.");
            }

            // R2 CDN'den görseli sil
            $key = $this->portfolioObjectKey((string)($item['image_url'] ?? ''));
            if ($key !== null) {
                require_once ROOT . '/app/Core/R2Storage.php';
                if (!\App\Core\R2Storage::instance()->deleteObject($key)) {
                    throw new \RuntimeException("Görsel R2'den silinemedi. Kayıt korundu; tekrar deneyin.");
                }
            }

            $pdo->prepare("DELETE FROM portfolio_images WHERE id = ?")->execute([$id]);
            \App\Services\SitemapService::markSitemapDirty();

            AdminLog::write('info', 'Portfolyo görseli silindi.', [
                'endpoint' => 'deletePortfolio',
                'id' => $id,
                'title' => $item['title_tr']
            ]);

            $_SESSION['success'] = "Görsel CDN ve veritabanından başarıyla silindi.";
        } catch (\Throwable $e) {
            $_SESSION['error'] = $e->getMessage();
        }

        header('Location: /yonetim/portfolio');
        exit;
    }

    public function portfolioDelete()
    {
        $this->deletePortfolio();
    }

    public function deleteAllPortfolio()
    {
        $this->requirePost();

        try {
            $pdo = Db::pdo();
            $stmt = $pdo->query("SELECT id, image_url FROM portfolio_images");
            $items = $stmt->fetchAll(\PDO::FETCH_ASSOC);

            $deletedR2Count = 0;
            $failedR2Count = 0;
            $deletedCount = 0;
            $delete = $pdo->prepare("DELETE FROM portfolio_images WHERE id = ?");

            if (!empty($items)) {
                require_once ROOT . '/app/Core/R2Storage.php';
                $r2 = \App\Core\R2Storage::instance();

                foreach ($items as $item) {
                    $key = $this->portfolioObjectKey((string)($item['image_url'] ?? ''));
                    if ($key !== null) {
                        try {
                            if (!$r2->deleteObject($key)) {
                                $failedR2Count++;
                                continue;
                            }
                            $deletedR2Count++;
                        } catch (\Throwable $e) {
                            $failedR2Count++;
                            error_log("Portfolyo tümünü silme esnasında R2 görseli silinemedi (ID: {$item['id']}): " . $e->getMessage());
                            continue;
                        }
                    }
                    $delete->execute([$item['id']]);
                    $deletedCount += $delete->rowCount();
                }
            }

            \App\Services\SitemapService::markSitemapDirty();

            AdminLog::write('info', $deletedCount . ' adet portfolyo görseli CDN ve veritabanından kalıcı olarak silindi.', [
                'endpoint' => 'deleteAllPortfolio',
                'db_count' => $deletedCount,
                'r2_deleted' => $deletedR2Count,
                'r2_failed' => $failedR2Count
            ]);

            $_SESSION['success'] = $deletedCount . " adet portfolyo görseli silindi.";
            if ($failedR2Count > 0) {
                $_SESSION['error'] = $failedR2Count . " görsel R2'den silinemedi. Bu kayıtlar korundu; tekrar deneyin.";
            }
        } catch (\Throwable $e) {
            $_SESSION['error'] = "Portfolyo temizlenirken hata oluştu: " . $e->getMessage();
        }

        header('Location: /yonetim/portfolio');
        exit;
    }

    public function portfolioDeleteAll()
    {
        $this->deleteAllPortfolio();
    }

    public function portfolioReorder()
    {
        $this->requirePost();

        $isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';

        try {
            $orders = $_POST['orders'] ?? [];
            if (!is_array($orders)) {
                throw new \Exception("Geçersiz sıralama verisi.");
            }

            $pdo = Db::pdo();
            $pdo->beginTransaction();

            $stmt = $pdo->prepare("UPDATE portfolio_images SET display_order = ? WHERE id = ?");
            foreach ($orders as $id => $orderVal) {
                $stmt->execute([(int)$orderVal, (int)$id]);
            }

            $pdo->commit();

            if ($isAjax) {
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode(['success' => true]);
                exit;
            }

            $_SESSION['success'] = "Sıralama başarıyla güncellendi.";
        } catch (\Exception $e) {
            if (isset($pdo) && $pdo->inTransaction()) {
                $pdo->rollBack();
            }

            if ($isAjax) {
                header('Content-Type: application/json; charset=utf-8');
                http_response_code(400);
                echo json_encode(['success' => false, 'error' => $e->getMessage()]);
                exit;
            }

            $_SESSION['error'] = $e->getMessage();
        }

        header('Location: /yonetim/portfolio');
        exit;
    }
    public function cleanupPortfolioTrash()
    {
        try {
            $pdo = Db::pdo();
            // Fetch items deleted more than 1 day ago
            $stmt = $pdo->query("SELECT id, image_url FROM portfolio_images WHERE deleted_at IS NOT NULL AND deleted_at < NOW() - INTERVAL 1 DAY");
            $expiredItems = $stmt->fetchAll(\PDO::FETCH_ASSOC);

            if (!empty($expiredItems)) {
                require_once ROOT . '/app/Core/R2Storage.php';
                $r2 = \App\Core\R2Storage::instance();

                $idsToDelete = [];
                foreach ($expiredItems as $item) {
                    $key = $this->portfolioObjectKey((string)$item['image_url']);
                    if ($key !== null) {
                        try {
                            $r2->deleteObject($key);
                        } catch (\Throwable $e) {
                            error_log("Çöp kutusundan R2 görseli silinirken hata (ID: {$item['id']}): " . $e->getMessage());
                        }
                    }
                    $idsToDelete[] = (int)$item['id'];
                }

                $placeholders = implode(',', array_fill(0, count($idsToDelete), '?'));
                $delStmt = $pdo->prepare("DELETE FROM portfolio_images WHERE id IN ($placeholders)");
                $delStmt->execute($idsToDelete);

                AdminLog::write('info', count($idsToDelete) . ' adet portfolyo görseli çöp kutusundan kalıcı olarak silindi.', [
                    'endpoint' => 'cleanupPortfolioTrash'
                ]);
            }
        } catch (\Exception $e) {
            error_log("Portfolyo çöp kutusu temizlenirken hata: " . $e->getMessage());
        }
    }
}
