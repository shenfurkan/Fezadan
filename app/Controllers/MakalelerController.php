<?php
class MakalelerController extends Controller {
    
    private function getPDO() {
        return Db::pdo();
    }

    public function index() {
        try {
            $pdo = $this->getPDO();

            // Parametreler
            $catId = isset($_GET['cat']) ? (int)$_GET['cat'] : null;
            $authorId = isset($_GET['author']) ? (int)$_GET['author'] : null;
            $searchQuery = isset($_GET['q']) ? trim($_GET['q']) : '';
            // Sort whitelist (kullanıcı input'unu hiçbir zaman SQL'e sokmuyoruz)
            $allowedSorts = ['newest','oldest','az','za'];
            $sortOrder = isset($_GET['sort']) && in_array($_GET['sort'], $allowedSorts, true)
                ? $_GET['sort']
                : 'newest';
            $allCategories = $pdo->query("
                SELECT DISTINCT c.* FROM categories c 
                JOIN article_categories ac ON c.id = ac.category_id 
                JOIN articles a ON ac.article_id = a.id 
                WHERE a.status = 'published' 
                ORDER BY c.name ASC
            ")->fetchAll(\PDO::FETCH_ASSOC);
            $allAuthors = $pdo->query("
                SELECT DISTINCT au.id, au.name 
                FROM authors au 
                JOIN articles a ON au.id = a.author_id 
                WHERE a.status = 'published' 
                ORDER BY au.name ASC
            ")->fetchAll(\PDO::FETCH_ASSOC);
            $whereClauses = ["a.status = 'published'"];
            $params = [];

            if ($catId) {
                $whereClauses[] = "a.id IN (SELECT article_id FROM article_categories WHERE category_id = :cat)";
                $params[':cat'] = $catId;
            }
            if ($authorId) {
                $whereClauses[] = "a.author_id = :author";
                $params[':author'] = $authorId;
            }
            // Arama
            if (!empty($searchQuery)) {
                $whereClauses[] = "(a.title LIKE :q OR a.content LIKE :q)";
                $params[':q'] = "%$searchQuery%";
            }

            $whereSql = implode(" AND ", $whereClauses);

            // Sıralama
            switch ($sortOrder) {
                case 'oldest': $orderBy = "a.created_at ASC"; break;
                case 'az': $orderBy = "a.title ASC"; break;
                case 'za': $orderBy = "a.title DESC"; break;
                default: $orderBy = "a.created_at DESC"; break; // newest
            }

            // Sayfalandırma
            $limit  = 8;
            $page   = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
            $offset = ($page - 1) * $limit;

            $totalStmt = $pdo->prepare("SELECT COUNT(DISTINCT a.id) FROM articles a WHERE $whereSql");
            $totalStmt->execute($params);
            $totalArticles = $totalStmt->fetchColumn();
            $totalPages = ceil($totalArticles / $limit);

            // Tek sorguda kategori bilgisi (N+1 yok)
            $sql = "SELECT a.*, au.name as author_name,
                           GROUP_CONCAT(DISTINCT CONCAT_WS('|', c.id, c.name, c.slug) SEPARATOR ';;') AS categories_raw
                    FROM articles a
                    LEFT JOIN authors au ON a.author_id = au.id
                    LEFT JOIN article_categories ac2 ON ac2.article_id = a.id
                    LEFT JOIN categories c ON c.id = ac2.category_id
                    WHERE $whereSql
                    GROUP BY a.id
                    ORDER BY $orderBy
                    LIMIT $limit OFFSET $offset";

            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            $articles = $stmt->fetchAll(\PDO::FETCH_ASSOC);

            foreach ($articles as &$article) {
                $cats = [];
                if (!empty($article['categories_raw'])) {
                    foreach (explode(';;', $article['categories_raw']) as $row) {
                        if ($row === '') continue;
                        [$cid, $cname, $cslug] = array_pad(explode('|', $row, 3), 3, '');
                        if ($cid !== '' && $cname !== '') {
                            $cats[] = ['id' => (int)$cid, 'name' => $cname, 'slug' => $cslug];
                        }
                    }
                }
                $article['categories'] = $cats;
                unset($article['categories_raw']);
            }
            unset($article);

            $this->view('front/makaleler', [
                'articles' => $articles,
                'categories' => $allCategories,
                'authors' => $allAuthors,
                'currentPage' => $page,
                'totalPages' => $totalPages,
                'totalArticles' => $totalArticles,
                'filters' => [
                    'cat' => $catId, 
                    'author' => $authorId, 
                    'q' => $searchQuery,
                    'sort' => $sortOrder
                ]
            ]);

        } catch (\PDOException $e) { throw new \Exception("Hata: " . $e->getMessage()); }
    }

}