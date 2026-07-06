<?php

namespace App\Services;

class MediaService
{
    /**
     * Makale için TTS sentezler ve R2'ye yükler.
     * @param \PDO $pdo
     * @param int $id Makale ID
     * @return string Ses varlık URL'i
     * @throws \Exception
     */
    public function generateAndUploadTts(\PDO $pdo, int $id): string
    {
        $stmt = $pdo->prepare("SELECT title, content FROM articles WHERE id = ? LIMIT 1");
        $stmt->execute([$id]);
        $article = $stmt->fetch(\PDO::FETCH_ASSOC);

        if (!$article) {
            throw new \Exception('Makale bulunamadı.', 404);
        }

        $text = $article['title'] . ". " . strip_tags($article['content']);
        $audioData = $this->synthesizeTextToSpeech($text);

        if ($audioData === null) {
            throw new \Exception('Ses sentezleme başarısız oldu.', 500);
        }

        require_once ROOT . '/app/Core/R2Storage.php';
        $r2 = \App\Core\R2Storage::instance();
        $objectKey = 'uploads/audio/tts_' . $id . '_' . time() . '.mp3';

        $mimeType = 'audio/mpeg';
        $uploadResult = $r2->uploadObject($objectKey, $audioData, $mimeType);
        if (!$uploadResult) {
            throw new \Exception('Ses dosyası bulut depolamaya yüklenemedi.', 500);
        }

        $audioUrl = '/' . $objectKey;
        $pdo->prepare("UPDATE articles SET audio_url = ? WHERE id = ?")
            ->execute([$audioUrl, $id]);

        return \App\Core\Upload::assetUrl($audioUrl);
    }

    private function synthesizeTextToSpeech(string $text): ?string
    {
        $provider = env_value('TTS_PROVIDER', 'mock');
        if ($provider === 'elevenlabs') {
            $apiKey = env_value('ELEVENLABS_API_KEY', '');
            $voiceId = env_value('ELEVENLABS_VOICE_ID', '21m00Tcm4TlvDq8ikWAM');
            if ($apiKey !== '') {
                $url = "https://api.elevenlabs.io/v1/text-to-speech/" . $voiceId;
                $data = json_encode([
                    'text' => mb_substr($text, 0, 5000),
                    'model_id' => 'eleven_multilingual_v2',
                    'voice_settings' => [
                        'stability' => 0.5,
                        'similarity_boost' => 0.75
                    ]
                ]);
                $options = [
                    'http' => [
                        'header'  => "Content-Type: application/json\r\n" .
                                     "xi-api-key: " . $apiKey . "\r\n",
                        'method'  => 'POST',
                        'content' => $data,
                        'ignore_errors' => true,
                        'timeout' => 30
                    ]
                ];
                $context  = stream_context_create($options);
                $result = @file_get_contents($url, false, $context);
                if ($result && strpos($http_response_header[0] ?? '', '200') !== false) {
                    return $result;
                }
            }
        }

        $silentMp3 = base64_decode(
            'SUQzBAAAAAAAI1RTU0UAAAAPAAADTGFtZTMuOTguNFVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVV' .
            'VVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVV' .
            'VVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVE1UAeAAAA' .
            'DwAADwDw/wAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA' .
            'AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA' .
            'AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA' .
            'AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA'
        );
        return $silentMp3;
    }

    /**
     * Makale için OG Görseli üretir ve R2'ye yükler.
     * @param \PDO $pdo
     * @param int $articleId
     * @return array ['url' => ..., 'path' => ...]
     * @throws \Exception
     */
    public function generateAndUploadOgImage(\PDO $pdo, int $articleId): array
    {
        $stmt = $pdo->prepare("
            SELECT a.id, a.title, a.slug, au.name AS author_name, au.slug AS author_slug,
                   GROUP_CONCAT(c.name SEPARATOR ', ') AS categories
            FROM articles a
            LEFT JOIN authors au ON a.author_id = au.id
            LEFT JOIN article_categories ac ON a.id = ac.article_id
            LEFT JOIN categories c ON ac.category_id = c.id
            WHERE a.id = ?
            GROUP BY a.id
        ");
        $stmt->execute([$articleId]);
        $article = $stmt->fetch(\PDO::FETCH_ASSOC);

        if (!$article) {
            throw new \Exception('Makale bulunamadı.');
        }

        require_once ROOT . '/app/Core/OgImage.php';
        $tmpFile = \App\Core\OgImage::generate(
            $article['title'],
            $article['author_name'] ?? 'FEZADAN',
            $article['categories'] ?? ''
        );

        if (!$tmpFile || !file_exists($tmpFile)) {
            throw new \Exception('OG görsel üretilemedi. GD veya font kontrol edilmeli.');
        }

        require_once ROOT . '/app/Core/R2Storage.php';
        $r2 = \App\Core\R2Storage::instance();
        $objectKey = 'uploads/og/og-' . $article['slug'] . '.png';
        $result = $r2->uploadFile($tmpFile, $objectKey, 'image/png');

        @unlink($tmpFile);

        if ($result === null) {
            throw new \Exception('R2 yükleme başarısız.');
        }

        $pdo->prepare("UPDATE articles SET og_image = ? WHERE id = ?")->execute([$objectKey, $articleId]);

        $url = \App\Core\Upload::assetUrl($objectKey);

        return ['url' => $url, 'path' => $objectKey];
    }
}
