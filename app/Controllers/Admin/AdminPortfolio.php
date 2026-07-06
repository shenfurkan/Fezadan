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
            throw new \Exception("Portfolyo Verisi Çekilemedi: " . $e->getMessage());
        }
    }

    public function storePortfolio()
    {
        $this->requirePost();

        $isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';

        $title_tr = trim($_POST['title_tr'] ?? '') ?: 'Görsel';
        $title_en = trim($_POST['title_en'] ?? '') ?: null;
        $description_tr = trim($_POST['description_tr'] ?? '') ?: null;
        $description_en = trim($_POST['description_en'] ?? '') ?: null;
        $type = $_POST['type'] ?? 'photo';
        if (!in_array($type, ['photo', 'drawing'], true)) {
            $type = 'photo';
        }
        $display_order = (int)($_POST['display_order'] ?? 0);

        try {
            if (!isset($_FILES['image']) || $_FILES['image']['error'] === UPLOAD_ERR_NO_FILE) {
                throw new \Exception("Lütfen yüklemek için bir görsel seçin.");
            }

            // Orijinal dosya kalitesinde R2'ye yükle (WebP dönüşümü/sıkıştırma yapmadan)
            $storedPath = Upload::saveOriginalImageToR2($_FILES['image'], 'portfolio', 'port_', 20971520, $this->createSlug($title_tr));
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

            AdminLog::write('info', 'Portfolyo görseli yüklendi.', [
                'endpoint' => 'storePortfolio',
                'path' => $storedPath,
                'title' => $title_tr
            ]);

            $_SESSION['success'] = "Görsel başarıyla eklendi.";
        } catch (\Exception $e) {
            if ($isAjax) {
                header('Content-Type: application/json; charset=utf-8');
                http_response_code(400);
                echo json_encode(['success' => false, 'error' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
                exit;
            }
            $_SESSION['error'] = $e->getMessage();
        }

        if ($isAjax) {
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['success' => true], JSON_UNESCAPED_UNICODE);
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
            throw new \Exception("Portfolyo Düzenleme Hatası: " . $e->getMessage());
        }
    }

    public function updatePortfolio()
    {
        $this->requirePost();

        $id = (int)($_POST['id'] ?? 0);
        $title_tr = trim($_POST['title_tr'] ?? '') ?: 'Görsel';
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
                $storedPath = Upload::saveOriginalImageToR2($_FILES['image'], 'portfolio', 'port_', 20971520, $this->createSlug($title_tr));
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

            AdminLog::write('info', 'Portfolyo görseli güncellendi.', [
                'endpoint' => 'updatePortfolio',
                'id' => $id,
                'path' => $image_url
            ]);

            $_SESSION['success'] = "Görsel başarıyla güncellendi.";
        } catch (\Exception $e) {
            $_SESSION['error'] = $e->getMessage();
            header('Location: /yonetim/portfolio-edit?id=' . $id);
            exit;
        }

        header('Location: /yonetim/portfolio');
        exit;
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

            // R2'den dosyayı sil
            try {
                require_once ROOT . '/app/Core/R2Storage.php';
                $r2 = \App\Core\R2Storage::instance();
                $key = $this->portfolioObjectKey((string)$item['image_url']);
                if ($key !== null) {
                    $r2->deleteObject($key);
                }
            } catch (\Throwable $e) {
                error_log("Portfolyo görseli R2'den silinirken hata: " . $e->getMessage());
            }

            $stmtDel = $pdo->prepare("DELETE FROM portfolio_images WHERE id = ?");
            $stmtDel->execute([$id]);

            AdminLog::write('info', 'Portfolyo görseli silindi.', [
                'endpoint' => 'deletePortfolio',
                'id' => $id,
                'title' => $item['title_tr']
            ]);

            $_SESSION['success'] = "Görsel başarıyla silindi.";
        } catch (\Exception $e) {
            $_SESSION['error'] = $e->getMessage();
        }

        header('Location: /yonetim/portfolio');
        exit;
    }

    public function portfolioDelete()
    {
        $this->deletePortfolio();
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
}
