<?php
/**
 * Tek PDO bağlantısı.
 *
 * Her controller'ın yeni `new PDO(...)` açmasını engeller — TCP/auth handshake
 * her istekte tek sefer olur. Persistent connection değil; tek-istek-tek-bağlantı.
 *
 * Kullanım:
 *   $pdo = Db::pdo();
 */
class Db
{
    /** @var \PDO|null */
    private static $pdo = null;

    public static function pdo(): \PDO
    {
        if (self::$pdo === null) {
            $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=' . DB_CHARSET;
            self::$pdo = new \PDO($dsn, DB_USER, DB_PASS, [
                \PDO::ATTR_ERRMODE            => \PDO::ERRMODE_EXCEPTION,
                \PDO::ATTR_EMULATE_PREPARES   => false,
                \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
            ]);
        }
        return self::$pdo;
    }

    /** Sadece test/ayarsızlık durumları için bağlantıyı sıfırla. */
    public static function reset(): void
    {
        self::$pdo = null;
    }
}
