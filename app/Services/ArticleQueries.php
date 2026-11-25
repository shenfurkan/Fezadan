<?php

/**
 * Public article rules in one place: which articles readers may see, and how an
 * article is found from its URL /{lang}/{yazi|post}/{slug}. SQL uses the aliases
 * `articles a` and `authors au`.
 */
final class ArticleQueries
{
    /** Readers only see published articles; scheduled ones go live via cron/publish-scheduled.php. */
    public const PUBLISHED = "a.status = 'published'";

    /**
     * Articles the author wrote or co-wrote: older articles only have articles.author_id,
     * newer ones list every author in article_authors. Both arguments are SQL for the same
     * author id ('?' bound twice, a column like 'au.id', or two distinct named placeholders,
     * since native prepares can't reuse one name).
     */
    public static function byAuthor(string $authorId, ?string $sameAuthorId = null): string
    {
        $sameAuthorId ??= $authorId;
        return "(a.author_id = $authorId OR EXISTS (SELECT 1 FROM article_authors aa
            WHERE aa.article_id = a.id AND aa.author_id = $sameAuthorId))";
    }

    /**
     * Find a published article by slug and language. Old URLs can additionally
     * be checked against the primary author before they are redirected.
     *
     * @return array{id: int, slug: ?string, lang: string, title: string, content: ?string,
     *     short_desc: ?string, author_id: ?int, translation_of: ?int, created_at: string,
     *     meta_keywords: ?string, author_name: ?string, author_bio: ?string,
     *     author_img: ?string, author_slug: ?string, ...}|null
     */
    public static function findPublished(\PDO $pdo, string $slug, string $lang, ?string $authorSlug = null): ?array
    {
        $stmt = $pdo->prepare(
            "SELECT a.*, au.name AS author_name, au.bio AS author_bio,
                    au.image_url AS author_img, au.slug AS author_slug
             FROM articles a
             LEFT JOIN authors au ON au.id = a.author_id
             WHERE a.slug = ? AND a.lang = ? AND " . self::PUBLISHED
             . ($authorSlug !== null ? ' AND au.slug = ?' : '') . "
             LIMIT 1"
        );
        $params = [$slug, $lang];
        if ($authorSlug !== null) $params[] = $authorSlug;
        $stmt->execute($params);
        return $stmt->fetch(\PDO::FETCH_ASSOC) ?: null;
    }

    /**
     * Where a published article lives, found by slug,
     * by id, or as the article whose translation_of is the given id.
     *
     * @param 'slug'|'id'|'translation_of' $column
     * @return array{article_slug: ?string, lang: string, author_slug: ?string}|null
     */
    public static function publishedLink(\PDO $pdo, string $column, string|int $value): ?array
    {
        // PHPDoc types aren't enforced at runtime; this is what keeps caller input out of the SQL.
        // @phpstan-ignore function.alreadyNarrowedType
        if (!in_array($column, ['slug', 'id', 'translation_of'], true)) {
            throw new \InvalidArgumentException('Unsupported article lookup column: ' . $column);
        }
        $stmt = $pdo->prepare(
            "SELECT a.slug AS article_slug, a.lang, au.slug AS author_slug
             FROM articles a
             LEFT JOIN authors au ON au.id = a.author_id
             WHERE a.$column = ? AND " . self::PUBLISHED . "
             LIMIT 1"
        );
        $stmt->execute([$value]);
        return $stmt->fetch(\PDO::FETCH_ASSOC) ?: null;
    }
}
