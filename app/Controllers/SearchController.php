<?php

class SearchController extends Controller
{
    public function autocomplete()
    {
        header('Content-Type: application/json; charset=utf-8');

        $query = is_string($_GET['q'] ?? null) ? trim($_GET['q']) : '';
        if (mb_strlen($query, 'UTF-8') > 200) {
            http_response_code(400);
            echo json_encode(['error' => 'Arama metni çok uzun.']);
            exit;
        }
        if ($query === '' || mb_strlen($query, 'UTF-8') < 2) {
            echo json_encode([]);
            exit;
        }

        try {
            $pdo = Db::pdo();

            // Search using LIKE on title and FULLTEXT as fallback
            $searchTerm = "%" . $query . "%";
            $lang = App::getLang();
            $sql = "SELECT a.id, a.title, a.slug, a.image_url, a.short_desc, a.lang, au.slug AS author_slug
                    FROM articles a
                    LEFT JOIN authors au ON a.author_id = au.id
                    WHERE " . ArticleQueries::PUBLISHED . "
                      AND a.lang = :lang
                      AND (a.title LIKE :term OR MATCH(a.title, a.content) AGAINST(:query IN NATURAL LANGUAGE MODE))
                    ORDER BY a.created_at DESC, a.id DESC
                    LIMIT 5";

            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                ':lang' => $lang,
                ':term' => $searchTerm,
                ':query' => $query
            ]);
            $results = $stmt->fetchAll(\PDO::FETCH_ASSOC);

            $formatted = [];
            require_once ROOT . '/app/Core/Upload.php';

            foreach ($results as $item) {
                $formatted[] = [
                    'id' => $item['id'],
                    'title' => $item['title'],
                    'slug' => $item['slug'],
                    'url' => articleUrl($item['slug'], $item['lang'] ?? $lang),
                    'image' => $item['image_url'] ? Upload::assetUrl($item['image_url']) : null,
                    'desc' => mb_substr(strip_tags((string)($item['short_desc'] ?? '')), 0, 80, 'UTF-8') . '...'
                ];
            }

            echo json_encode($formatted, JSON_UNESCAPED_UNICODE);
            exit;
        } catch (\Exception $e) {
            error_log('Search autocomplete failed: ' . $e->getMessage());
            http_response_code(500);
            echo json_encode(['error' => 'Arama şu anda kullanılamıyor.']);
            exit;
        }
    }
}
