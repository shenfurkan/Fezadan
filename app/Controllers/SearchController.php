<?php

class SearchController extends Controller
{
    public function autocomplete()
    {
        header('Content-Type: application/json; charset=utf-8');

        $query = isset($_GET['q']) ? trim((string)$_GET['q']) : '';
        if ($query === '' || mb_strlen($query, 'UTF-8') < 2) {
            echo json_encode([]);
            exit;
        }

        try {
            $pdo = Db::pdo();
            
            // Başlıkta LIKE ile ara, yedek olarak FULLTEXT kullan
            $searchTerm = "%" . $query . "%";
            $lang = App::getLang();
            $sql = "SELECT a.id, a.title, a.slug, a.image_url, a.short_desc, a.lang, au.slug AS author_slug
                    FROM articles a
                    LEFT JOIN authors au ON a.author_id = au.id
                    WHERE a.status = 'published' 
                      AND a.lang = :lang
                      AND (a.title LIKE :term OR MATCH(a.title, a.content) AGAINST(:query IN BOOLEAN MODE))
                    ORDER BY a.reads DESC 
                    LIMIT 5";
                    
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                ':lang' => $lang,
                ':term' => $searchTerm,
                ':query' => $query . '*'
            ]);
            $results = $stmt->fetchAll(\PDO::FETCH_ASSOC);

            $formatted = [];
            require_once ROOT . '/app/Core/Upload.php';

            foreach ($results as $item) {
                $formatted[] = [
                    'id' => $item['id'],
                    'title' => htmlspecialchars($item['title'], ENT_QUOTES, 'UTF-8'),
                    'slug' => $item['slug'],
                    'url' => articleUrl($item['author_slug'] ?? 'yazar', $item['slug'], strtolower($item['lang'] ?? $lang)),
                    'image' => $item['image_url'] ? Upload::assetUrl($item['image_url']) : null,
                    'desc' => htmlspecialchars(mb_substr(strip_tags($item['short_desc']), 0, 80, 'UTF-8'), ENT_QUOTES, 'UTF-8') . '...'
                ];
            }

            echo json_encode($formatted, JSON_UNESCAPED_UNICODE);
            exit;
        } catch (\Exception $e) {
            http_response_code(500);
            echo json_encode(['error' => $e->getMessage()]);
            exit;
        }
    }
}
