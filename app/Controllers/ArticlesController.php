<?php
class ArticlesController extends Controller {

    private function getPDO() {
        return Db::pdo();
    }

    public function index() {
        try {
            $pdo = $this->getPDO();

            $catId = isset($_GET['cat']) ? (int)$_GET['cat'] : null;
            $authorId = isset($_GET['author']) ? (int)$_GET['author'] : null;
            $searchQuery = isset($_GET['q']) ? trim($_GET['q']) : '';
            $allowedSorts = ['newest', 'oldest', 'az', 'za'];
            $sortOrder = isset($_GET['sort']) && in_array($_GET['sort'], $allowedSorts, true)
                ? $_GET['sort']
                : 'newest';

            $contentLang = App::getLang();
            [$whereSql, $params] = $this->buildFilters($contentLang, $catId, $authorId, $searchQuery);
            $totalArticles = $this->countArticles($pdo, $whereSql, $params);

            $limit = 8;
            $page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
            $totalPages = max(1, (int)ceil($totalArticles / $limit));
            if ($page > $totalPages) {
                $page = $totalPages;
            }
            $offset = ($page - 1) * $limit;

            $articles = $this->fetchArticles($pdo, $whereSql, $params, $sortOrder, $limit, $offset);

            $this->view('front/articles', [
                'articles' => $articles,
                'categories' => $this->fetchCategories($pdo, $contentLang),
                'authors' => $this->fetchAuthors($pdo, $contentLang),
                'currentPage' => $page,
                'totalPages' => $totalPages,
                'totalArticles' => $totalArticles,
                'contentLang' => $contentLang,
                'filters' => [
                    'cat' => $catId,
                    'author' => $authorId,
                    'q' => $searchQuery,
                    'sort' => $sortOrder,
                ],
            ]);
        } catch (\PDOException $e) {
            throw new \Exception("Hata: " . $e->getMessage());
        }
    }

    private function buildFilters(string $lang, ?int $catId, ?int $authorId, string $searchQuery): array {
        $whereClauses = ["a.status = 'published'", "a.lang = :lang"];
        $params = [':lang' => $lang];

        if ($catId) {
            $whereClauses[] = "a.id IN (SELECT article_id FROM article_categories WHERE category_id = :cat)";
            $params[':cat'] = $catId;
        }

        if ($authorId) {
            $whereClauses[] = "a.author_id = :author";
            $params[':author'] = $authorId;
        }

        if ($searchQuery !== '') {
            $whereClauses[] = "MATCH(a.title, a.content) AGAINST(:q IN BOOLEAN MODE)";
            $params[':q'] = $searchQuery;
        }

        return [implode(' AND ', $whereClauses), $params];
    }

    private function countArticles(\PDO $pdo, string $whereSql, array $params): int {
        $totalStmt = $pdo->prepare("SELECT COUNT(DISTINCT a.id) FROM articles a WHERE $whereSql");
        $totalStmt->execute($params);

        return (int)$totalStmt->fetchColumn();
    }

    private function fetchArticles(\PDO $pdo, string $whereSql, array $params, string $sortOrder, int $limit, int $offset): array {
        switch ($sortOrder) {
            case 'oldest':
                $orderBy = "a.created_at ASC";
                break;
            case 'az':
                $orderBy = "a.title ASC";
                break;
            case 'za':
                $orderBy = "a.title DESC";
                break;
            default:
                $orderBy = "a.created_at DESC";
                break;
        }

        $sql = "SELECT a.*, au.name AS author_name, au.slug AS author_slug,
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

        return $this->attachCategories($stmt->fetchAll(\PDO::FETCH_ASSOC));
    }

    private function attachCategories(array $articles): array {
        foreach ($articles as &$article) {
            $cats = [];
            if (!empty($article['categories_raw'])) {
                foreach (explode(';;', $article['categories_raw']) as $row) {
                    if ($row === '') {
                        continue;
                    }
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

        return $articles;
    }

    private function fetchCategories(\PDO $pdo, string $lang): array {
        $catStmt = $pdo->prepare("
            SELECT DISTINCT c.* FROM categories c
            JOIN article_categories ac ON c.id = ac.category_id
            JOIN articles a ON ac.article_id = a.id
            WHERE a.status = 'published' AND a.lang = ?
            ORDER BY c.name ASC
        ");
        $catStmt->execute([$lang]);

        return $catStmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    private function fetchAuthors(\PDO $pdo, string $lang): array {
        $authorStmt = $pdo->prepare("
            SELECT DISTINCT au.id, au.name
            FROM authors au
            JOIN articles a ON au.id = a.author_id
            WHERE a.status = 'published' AND a.lang = ?
            ORDER BY au.name ASC
        ");
        $authorStmt->execute([$lang]);

        return $authorStmt->fetchAll(\PDO::FETCH_ASSOC);
    }
}
