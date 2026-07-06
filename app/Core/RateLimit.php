<?php
namespace App\Core;

class RateLimit
{
    private const ALLOWED_COLUMNS = [
        'download_rate_limits' => ['download_time'],
        'read_rate_limits' => ['hit_time', 'article_id'],
    ];

    private static function identifier(string $table, string $column = null): string
    {
        if (!isset(self::ALLOWED_COLUMNS[$table])) {
            throw new \InvalidArgumentException('Unsupported rate limit table.');
        }

        if ($column === null) {
            return '`' . $table . '`';
        }

        if (!in_array($column, self::ALLOWED_COLUMNS[$table], true)) {
            throw new \InvalidArgumentException('Unsupported rate limit column.');
        }

        return '`' . $column . '`';
    }

    public static function ipHash(string $ip = null): string
    {
        $ip = $ip ?? ($_SERVER['REMOTE_ADDR'] ?? 'unknown');
        $dailySalt = date('Y-m-d') . APP_SALT;
        return hash('sha256', $ip . $dailySalt);
    }

    public static function countInWindow(\PDO $pdo, string $table, string $ipHash, int $windowSeconds, string $timeColumn = 'download_time'): int
    {
        $cutoff = date('Y-m-d H:i:s', time() - $windowSeconds);
        $tableSql = self::identifier($table);
        $timeColumnSql = self::identifier($table, $timeColumn);
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM {$tableSql} WHERE ip_hash = ? AND {$timeColumnSql} > ?");
        $stmt->execute([$ipHash, $cutoff]);
        return (int) $stmt->fetchColumn();
    }

    public static function record(\PDO $pdo, string $table, string $ipHash, string $timeColumn = 'download_time'): void
    {
        $tableSql = self::identifier($table);
        $timeColumnSql = self::identifier($table, $timeColumn);
        $stmt = $pdo->prepare("INSERT INTO {$tableSql} (ip_hash, {$timeColumnSql}) VALUES (?, NOW())");
        $stmt->execute([$ipHash]);
    }

    public static function cleanup(\PDO $pdo, string $table, string $timeColumn, int $olderThanSeconds): void
    {
        $cutoff = date('Y-m-d H:i:s', time() - $olderThanSeconds);
        $tableSql = self::identifier($table);
        $timeColumnSql = self::identifier($table, $timeColumn);
        $stmt = $pdo->prepare("DELETE FROM {$tableSql} WHERE {$timeColumnSql} < ?");
        $stmt->execute([$cutoff]);
    }

    public static function recordDailyUnique(\PDO $pdo, string $table, string $ipHash, int $entityId, string $entityColumn = 'article_id'): bool
    {
        $today = date('Y-m-d');
        $tableSql = self::identifier($table);
        $entityColumnSql = self::identifier($table, $entityColumn);
        $stmt = $pdo->prepare(
            "INSERT IGNORE INTO {$tableSql} (ip_hash, {$entityColumnSql}, hit_date, hit_time) VALUES (?, ?, ?, NOW())"
        );
        $stmt->execute([$ipHash, $entityId, $today]);
        return $stmt->rowCount() > 0;
    }

    public static function cleanupDaily(\PDO $pdo, string $table, int $olderThanSeconds = 86400): void
    {
        self::cleanup($pdo, $table, 'hit_time', $olderThanSeconds);
    }
}
