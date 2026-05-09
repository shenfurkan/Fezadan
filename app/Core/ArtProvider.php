<?php

abstract class ArtProvider
{
    protected $deepLKey;
    protected $geminiKey;

    public function __construct()
    {
        $this->deepLKey = $_ENV['DEEPL_API_KEY'] ?? getenv('DEEPL_API_KEY');
        $this->geminiKey = $_ENV['GEMINI_API_KEY'] ?? getenv('GEMINI_API_KEY');
    }

    abstract public function fetchArtwork();

    protected function sanitizeId($id)
    {
        return preg_replace('/[^a-zA-Z0-9_.-]/', '', (string)$id);
    }

    protected function getDeepLTranslation($text)
    {
        if (empty($this->deepLKey) || empty(trim((string)$text))) {
            return null;
        }

        $ch = curl_init('https://api-free.deepl.com/v2/translate');
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => http_build_query([
                'text' => $text,
                'source_lang' => 'EN',
                'target_lang' => 'TR',
            ]),
            CURLOPT_HTTPHEADER => [
                'Authorization: DeepL-Auth-Key ' . $this->deepLKey,
                'Content-Type: application/x-www-form-urlencoded',
            ],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CONNECTTIMEOUT => 3,
            CURLOPT_TIMEOUT => 7,
        ]);

        $response = curl_exec($ch);
        curl_close($ch);

        $json = $response ? json_decode($response, true) : null;
        return $json['translations'][0]['text'] ?? null;
    }

    protected function getGeminiDescription($title, $artist, $date, $medium, $descriptionEn)
    {
        if (empty($this->geminiKey) || empty(trim((string)$descriptionEn))) {
            return null;
        }

        $prompt = "GÖREV: Aşağıdaki İngilizce metni Türkçeye çevir ve müze kataloğu formatında yeniden yaz.\n\n"
            . "Kurallar:\n"
            . "- Sadece verilen metindeki bilgileri kullan.\n"
            . "- Metinde olmayan bilgi, tarih, yorum veya detay ekleme.\n"
            . "- Akıcı, doğal Türkçe kullan.\n"
            . "- Sadece çevrilmiş metni yaz.\n\n"
            . "Eser: {$title}\n"
            . "Sanatçı: {$artist}\n"
            . "Tarih: {$date}\n"
            . "Teknik: {$medium}\n\n"
            . "Çevrilecek metin:\n{$descriptionEn}";

        $payload = json_encode([
            'contents' => [['parts' => [['text' => $prompt]]]],
            'generationConfig' => [
                'temperature' => 0.4,
                'maxOutputTokens' => 500,
            ],
        ], JSON_UNESCAPED_UNICODE);

        $ch = curl_init('https://generativelanguage.googleapis.com/v1beta/models/gemini-flash-latest:generateContent');
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $payload,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'X-goog-api-key: ' . $this->geminiKey,
            ],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CONNECTTIMEOUT => 3,
            CURLOPT_TIMEOUT => 10,
        ]);

        $response = curl_exec($ch);
        $httpCode = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode !== 200 || !$response) {
            error_log("Gemini galeri açıklaması alınamadı: HTTP {$httpCode}");
            return null;
        }

        $json = json_decode($response, true);
        $text = $json['candidates'][0]['content']['parts'][0]['text'] ?? null;
        if ($text && mb_strlen(trim($text), 'UTF-8') >= 40) {
            return trim($text);
        }

        return null;
    }

    protected function searchWikipedia($title, $artist)
    {
        $artist = trim((string)$artist);
        if ($this->isUnknownArtist($artist)) {
            return null;
        }

        $searchUrl = 'https://en.wikipedia.org/w/api.php?action=query&list=search&srsearch='
            . urlencode('"' . $artist . '" artist painter')
            . '&utf8=&format=json&srlimit=1';

        $response = $this->curlGet($searchUrl, 5);
        $json = $response ? json_decode($response, true) : null;
        if (empty($json['query']['search'][0]['title'])) {
            return null;
        }

        $pageTitle = $json['query']['search'][0]['title'];
        if (!$this->isLikelyArtistPageTitle($pageTitle, $artist)) {
            return null;
        }

        $extractUrl = 'https://en.wikipedia.org/w/api.php?action=query&titles='
            . urlencode($pageTitle)
            . '&prop=extracts&exintro=true&explaintext=true&format=json';
        $response = $this->curlGet($extractUrl, 5);
        $json = $response ? json_decode($response, true) : null;
        $pages = $json['query']['pages'] ?? [];
        $page = is_array($pages) ? reset($pages) : null;
        $extract = is_array($page) ? trim((string)($page['extract'] ?? '')) : '';

        if ($extract === '' || !$this->isLikelyArtistExtract($extract, $artist)) {
            return null;
        }

        return [
            'text' => $extract,
            'url' => 'https://en.wikipedia.org/wiki/' . str_replace(' ', '_', $pageTitle),
        ];
    }

    protected function generateTemplateDescription($title, $artist, $date, $medium, $museum)
    {
        $parts = [];
        if (!$this->isUnknownArtist((string)$artist)) {
            $parts[] = "Sanatçı: {$artist}.";
        }
        if (!empty($date)) {
            $parts[] = "Tarih: {$date}.";
        }
        if (!empty($medium)) {
            $parts[] = "Teknik: {$medium}.";
        }
        $parts[] = "Eser {$museum} koleksiyonundan seçilmiştir.";
        return implode(' ', $parts);
    }

    protected function isUnknownArtist(string $artist): bool
    {
        $artist = trim(mb_strtolower($artist, 'UTF-8'));
        if ($artist === '') {
            return true;
        }
        $unknown = ['bilinmeyen sanatçı', 'bilinmeyen sanatci', 'unknown artist', 'unknown', 'anonymous', 'anonim', 'maker unknown', 'unidentified artist'];
        return in_array($artist, $unknown, true);
    }

    protected function isLikelyArtistPageTitle(string $pageTitle, string $artist): bool
    {
        $title = mb_strtolower($pageTitle, 'UTF-8');
        $artist = mb_strtolower($artist, 'UTF-8');
        if ($title === $artist || strpos($title, $artist) !== false) {
            return true;
        }

        $tokens = preg_split('/\s+/', preg_replace('/[^\p{L}\p{N}\s]/u', ' ', $artist));
        $tokens = array_values(array_filter($tokens ?: [], static fn($token) => mb_strlen($token, 'UTF-8') >= 3));
        if (!$tokens) {
            return false;
        }

        $matches = 0;
        foreach ($tokens as $token) {
            if (strpos($title, mb_strtolower($token, 'UTF-8')) !== false) {
                $matches++;
            }
        }

        return $matches >= min(2, count($tokens));
    }

    protected function isLikelyArtistExtract(string $extract, string $artist): bool
    {
        $text = mb_strtolower($extract, 'UTF-8');
        if (!$this->isLikelyArtistPageTitle($extract, $artist)) {
            return false;
        }

        foreach (['artist', 'painter', 'sculptor', 'printmaker', 'engraver', 'illustrator', 'calligrapher', 'photographer', 'sanatçı', 'ressam'] as $signal) {
            if (strpos($text, $signal) !== false) {
                return true;
            }
        }

        return false;
    }

    protected function curlGet(string $url, int $timeout = 6)
    {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CONNECTTIMEOUT => 3,
            CURLOPT_TIMEOUT => $timeout,
            CURLOPT_USERAGENT => 'FezadanBot/1.0 (https://fezadan.org)',
        ]);
        $response = curl_exec($ch);
        curl_close($ch);
        return $response ?: null;
    }

    public static function getRandomArtwork(bool $enrichDescriptions = false)
    {
        $providerClasses = [
            'ArtProviderCleveland' => ROOT . '/app/Core/ArtProviderCleveland.php',
            'ArtProviderChicago' => ROOT . '/app/Core/ArtProviderChicago.php',
            'ArtProviderMet' => ROOT . '/app/Core/ArtProviderMet.php',
        ];
        $classNames = array_keys($providerClasses);
        shuffle($classNames);

        $attemptsPerProvider = $enrichDescriptions ? 2 : 1;
        foreach ($classNames as $className) {
            require_once $providerClasses[$className];
            $provider = new $className();

            for ($attempt = 0; $attempt < $attemptsPerProvider; $attempt++) {
                try {
                    $artwork = $provider->fetchArtwork();
                    if ($artwork) {
                        return self::finalizeArtwork($provider, $artwork, $enrichDescriptions);
                    }
                } catch (\Throwable $e) {
                    error_log($className . ' galeri sağlayıcı hatası: ' . $e->getMessage());
                    break;
                }
            }
        }

        return self::fallbackArtwork();
    }

    private static function finalizeArtwork(ArtProvider $provider, array $artwork, bool $enrichDescriptions): array
    {
        $descTr = null;
        $descSource = 'template';
        $wikiUrl = null;

        if ($enrichDescriptions) {
            $wikiData = $provider->searchWikipedia($artwork['title'] ?? '', $artwork['artist'] ?? '');
            if ($wikiData) {
                $wikiUrl = $wikiData['url'];
                if (empty($artwork['artist_bio'])) {
                    $artwork['artist_bio'] = $wikiData['text'];
                }
            }

            $descEn = $artwork['description_en'] ?? '';
            $descTr = $provider->getGeminiDescription(
                $artwork['title'] ?? '',
                $artwork['artist'] ?? '',
                $artwork['date_display'] ?? '',
                $artwork['medium'] ?? '',
                $descEn
            );
            if (!$descTr && $descEn !== '') {
                $descTr = $provider->getDeepLTranslation($descEn);
            }
            if ($descTr) {
                $descSource = 'museum';
            }
        }

        if (!$descTr) {
            $descTr = $provider->generateTemplateDescription(
                $artwork['title'] ?? 'İsimsiz',
                $artwork['artist'] ?? 'Bilinmeyen Sanatçı',
                $artwork['date_display'] ?? '',
                $artwork['medium'] ?? '',
                $artwork['provider'] ?? 'müze'
            );
        }

        $artwork['description_tr'] = $descTr;
        $artwork['description_source'] = $descSource;
        $artwork['wikipedia_url'] = $wikiUrl;

        return $artwork;
    }

    private static function fallbackArtwork(): array
    {
        return [
            'title' => 'The Great Wave off Kanagawa',
            'artist' => 'Katsushika Hokusai',
            'artist_bio' => null,
            'date_display' => 'yak. 1830-1832',
            'medium' => 'Renkli ahşap baskı',
            'dimensions' => null,
            'image_url' => 'https://upload.wikimedia.org/wikipedia/commons/thumb/0/0a/The_Great_Wave_off_Kanagawa.jpg/1280px-The_Great_Wave_off_Kanagawa.jpg',
            'thumbnail_url' => 'https://upload.wikimedia.org/wikipedia/commons/thumb/0/0a/The_Great_Wave_off_Kanagawa.jpg/640px-The_Great_Wave_off_Kanagawa.jpg',
            'provider' => 'Wikimedia Commons',
            'external_id' => 'fallback-great-wave',
            'external_url' => 'https://commons.wikimedia.org/wiki/File:The_Great_Wave_off_Kanagawa.jpg',
            'description_en' => null,
            'description_tr' => 'Dış müze servisleri geçici olarak yanıt vermediği için galeri, kamu malı olan bu yedek eseri gösteriyor. Bağlantı düzeldiğinde yeni günlük eser otomatik olarak kaydedilir.',
            'description_source' => 'template',
            'wikipedia_url' => null,
            'is_public_domain' => 1,
        ];
    }
}
