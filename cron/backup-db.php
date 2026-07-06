<?php
/**
 * Cron: Veritabanı yedeği al → gzip → R2'ye yükle.
 *
 * Kullanım (günlük):
 *   0 3 * * * /usr/bin/php /var/www/html/cron/backup-db.php >/dev/null 2>&1
 *
 * R2_BACKUP_BUCKET_NAME içinde /backups/ altında tutulur. 7 günden eski yedekler otomatik silinir.
 */

if (PHP_SAPI !== 'cli') {
    fwrite(STDERR, "CLI ortamında çalıştırılmalı.\n");
    exit(1);
}

$rootDir = dirname(__DIR__);

use App\Core\R2Storage;

try {
    define('ROOT', $rootDir);

    require_once ROOT . '/app/Config/config.php';
    require_once ROOT . '/app/Core/R2Storage.php';

    if (file_exists(ROOT . '/vendor/autoload.php')) {
        require_once ROOT . '/vendor/autoload.php';
    }

    $tmpDir = sys_get_temp_dir();
    $timestamp = date('Y-m-d-His');
    $sqlFile = $tmpDir . '/fezadan-backup-' . $timestamp . '.sql';
    $gzFile = $sqlFile . '.gz';

    $mysqldump = 'mysqldump';
    if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
        $mysqldump = '"C:\\Program Files\\MySQL\\MySQL Server 8.0\\bin\\mysqldump.exe"';
    }

    $cnfFile = $tmpDir . '/.fezadan-mysqldump-' . $timestamp . '.cnf';
    $cnfContent = "[client]\nuser=" . DB_USER . "\npassword=" . DB_PASS . "\nhost=" . DB_HOST . "\n";
    file_put_contents($cnfFile, $cnfContent);
    chmod($cnfFile, 0600);

    $cmd = sprintf(
        '%s --defaults-extra-file=%s --single-transaction --quick %s > %s 2>&1',
        escapeshellcmd($mysqldump),
        escapeshellarg($cnfFile),
        escapeshellarg(DB_NAME),
        escapeshellarg($sqlFile)
    );

    exec($cmd, $output, $returnCode);
    @unlink($cnfFile);

    if ($returnCode !== 0 || !file_exists($sqlFile) || filesize($sqlFile) === 0) {
        throw new RuntimeException('mysqldump başarısız: ' . implode("\n", $output));
    }

    $gz = gzopen($gzFile, 'wb9');
    if (!$gz) {
        throw new RuntimeException('gzip dosyası oluşturulamadı.');
    }
    $fp = fopen($sqlFile, 'rb');
    while (!feof($fp)) {
        gzwrite($gz, fread($fp, 1024 * 1024));
    }
    fclose($fp);
    gzclose($gz);

    $sqlSize = filesize($sqlFile);
    $gzSize = filesize($gzFile);
    echo "[" . date('c') . "] SQL: " . round($sqlSize / 1024) . " KB → gzip: " . round($gzSize / 1024) . " KB\n";

    $r2 = R2Storage::instance();
    $objectKey = 'backups/db-' . $timestamp . '.sql.gz';
    $result = $r2->uploadBackupFile($gzFile, $objectKey, 'application/gzip');

    if ($result === null) {
        throw new RuntimeException('R2 yükleme başarısız.');
    }

    echo "[" . date('c') . "] Yedek yüklendi: " . $objectKey . "\n";

    cleanupOldBackups($r2);

    @mkdir(ROOT . '/storage/cron_heartbeat', 0755, true);
    @file_put_contents(ROOT . '/storage/cron_heartbeat/backup-db.heartbeat', time());

} catch (Throwable $e) {
    fwrite(STDERR, '[' . date('c') . '] HATA: ' . $e->getMessage() . "\n");
    exit(2);
} finally {
    @unlink($sqlFile);
    @unlink($gzFile);
}

function cleanupOldBackups(R2Storage $r2, int $retentionDays = 7): void
{
    try {
        $cutoff = strtotime("-{$retentionDays} days");
        $deleted = 0;

        foreach ($r2->listBackupKeys('backups/db-') as $obj) {
            $key = $obj['Key'];
            $lastModified = $obj['LastModified'];
            $ts = is_string($lastModified) ? strtotime($lastModified) : $lastModified->getTimestamp();

            if ($ts < $cutoff) {
                $r2->deleteBackupFile($key);
                $deleted++;
            }
        }

        if ($deleted > 0) {
            echo "[" . date('c') . "] $deleted eski yedek silindi.\n";
        }
    } catch (Throwable $e) {
        fwrite(STDERR, '[' . date('c') . '] Temizlik uyarısı: ' . $e->getMessage() . "\n");
    }
}
