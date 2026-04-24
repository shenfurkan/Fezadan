<?php

namespace App\Core;

use Aws\S3\S3Client;
use Aws\Exception\AwsException;

class R2Storage {
    private $client;
    private $bucketName;
    private $publicUrl;

    public function __construct() {
        $envPath = ROOT . '/.env';
        $env = [];

        if (file_exists($envPath)) {
            // Dosyayı satır satır okuyoruz
            $lines = file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            foreach ($lines as $line) {
                // Yorum satırlarını (# veya !) atla
                if (strpos(trim($line), '#') === 0 || strpos(trim($line), '!') === 0) continue;

                // Anahtar ve değeri ayır (sadece ilk '=' işaretinden böler)
                list($name, $value) = explode('=', $line, 2);
                $name = trim($name);
                $value = trim($value);

                // Değerin etrafındaki tırnakları (") temizle
                $value = trim($value, '"\'');
                
                $env[$name] = $value;
            }
        }

        // Bilgileri alıyoruz
        $accountId = $env['R2_ACCOUNT_ID'] ?? '';
        $accessKeyId = $env['R2_ACCESS_KEY_ID'] ?? '';
        $accessKeySecret = $env['R2_SECRET_ACCESS_KEY'] ?? '';
        
        $this->bucketName = $env['R2_BUCKET_NAME'] ?? '';
        $this->publicUrl = $env['R2_PUBLIC_URL'] ?? '';

        if (empty($this->bucketName)) {
            die("Sistem Hatası: .env dosyasında R2_BUCKET_NAME bulunamadı veya okunamadı.");
        }

        $this->client = new \Aws\S3\S3Client([
            'version'     => 'latest',
            'region'      => 'auto',
            'endpoint'    => "https://{$accountId}.r2.cloudflarestorage.com",
            'credentials' => [
                'key'    => $accessKeyId,
                'secret' => $accessKeySecret,
            ],
        ]);
    }

    /**
     * PDF dosyasını Cloudflare R2'ye yükler.
     * * @param string $tempFilePath Yüklenen dosyanın geçici yolu (tmp_name)
     * @param string $originalFileName Dosyanın orijinal adı
     * @return string|bool Başarılıysa dosya yolu (key), başarısızsa false döner
     */
    public function uploadPDF($tempFilePath, $originalFileName) {
        // Benzersiz ve güvenli bir dosya adı oluşturuyoruz
        $extension = pathinfo($originalFileName, PATHINFO_EXTENSION);
        $safeName = md5(uniqid('', true)) . '-' . time() . '.' . $extension;
        
        // R2 içindeki dizin yolu
        $objectKey = 'notlar/' . $safeName;

        try {
            $this->client->putObject([
                'Bucket'      => $this->bucketName,
                'Key'         => $objectKey,
                'SourceFile'  => $tempFilePath,
                'ContentType' => 'application/pdf',
                // Bucket dışa açıksa dosyaların okunabilmesi için ACL ayarı gerekebilir
                // Eğer Cloudflare panelinden bucket'ı public yaptıysan bunu yoruma alabilirsin
                // 'ACL'         => 'public-read' 
            ]);

            return $objectKey; 
        } catch (AwsException $e) {
            // Hata loglaması (Fezadan log sistemine veya error_log'a yazdırabilirsin)
            error_log("R2 Yükleme Hatası: " . $e->getMessage());
            return false;
        }
    }

    /**
     * PDF dosyasını Cloudflare R2'den siler.
     */
    public function deletePDF($objectKey) {
        try {
            $this->client->deleteObject([
                'Bucket' => $this->bucketName,
                'Key'    => $objectKey,
            ]);
            return true;
        } catch (\Aws\Exception\AwsException $e) {
            error_log("R2 Silme Hatası: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Dosyanın tam public URL'sini döndürür
     */
    public function getFileUrl($objectKey) {
        return rtrim($this->publicUrl, '/') . '/' . $objectKey;
    }

    /**
     * PDF dosyasını R2'den çeker ve doğrudan indirme olarak sunar.
     * @param string $objectKey R2 üzerindeki dosya yolu
     * @param string $displayName İndirilecek dosyanın adı
     */
    public function streamDownload($objectKey, $displayName) {
        try {
            if (ob_get_level()) ob_end_clean();

            $result = $this->client->getObject([
                'Bucket' => $this->bucketName,
                'Key'    => $objectKey,
            ]);

            // Dosya adını temizleyelim ve .pdf uzantısını ekleyelim
            $safeName = str_replace([' ', '/', '\\'], '_', $displayName) . '.pdf';

            // Tarayıcıya "bu bir dosyadır, bunu indir" diyoruz
            header('Content-Description: File Transfer');
            header('Content-Type: application/pdf');
            header('Content-Disposition: attachment; filename="' . $safeName . '"');
            header('Content-Transfer-Encoding: binary');
            header('Expires: 0');
            header('Cache-Control: must-revalidate');
            header('Pragma: public');
            header('Content-Length: ' . $result['ContentLength']);

            // Dosya içeriğini yazdır
            echo $result['Body'];
            exit;

        } catch (\Aws\Exception\AwsException $e) {
            error_log("R2 İndirme Hatası: " . $e->getMessage());
            die("Dosya indirilemedi.");
        }
    }
}