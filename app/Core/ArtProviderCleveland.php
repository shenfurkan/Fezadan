<?php

require_once ROOT . '/app/Core/ArtProvider.php';

class ArtProviderCleveland extends ArtProvider {

    public function fetchArtwork() {
        $skip = rand(0, 4000);
        $url = "https://openaccess-api.clevelandart.org/api/artworks/?has_image=1&type=Painting&limit=1&skip={$skip}";

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 2);
        curl_setopt($ch, CURLOPT_TIMEOUT, 5);
        $response = curl_exec($ch);
        curl_close($ch);

        if ($response) {
            $json = json_decode($response, true);
            if (!empty($json['data']) && isset($json['data'][0])) {
                $item = $json['data'][0];

                // Sanat dışı nesneleri filtrelemek için başlıkta hariç kelimeleri kontrol et (type=Painting genelde yeterli)
                $titleLower = strtolower($item['title'] ?? '');
                $techniqueLower = strtolower($item['technique'] ?? '');
                $typeLower = strtolower($item['type'] ?? '');
                $haystack = $titleLower . ' ' . $techniqueLower . ' ' . $typeLower;
                $excludeWords = ['vase', 'pottery', 'ceramic', 'vessel', 'bowl', 'plate', 'fragment', 'sword', 'shield', 'album leaf', 'fan', 'calligraphy', 'manuscript', 'textile'];
                foreach ($excludeWords as $word) {
                    if (strpos($haystack, $word) !== false) {
                        return null; // Bunu reddet, düzenleyici tekrar denesin
                    }
                }

                $artist = "Bilinmeyen Sanatçı";
                $artistBio = null;
                if (!empty($item['creators']) && isset($item['creators'][0])) {
                    $artist = $item['creators'][0]['description'] ?? $item['creators'][0]['role'] ?? "Bilinmeyen Sanatçı";
                    $artistBio = $item['creators'][0]['biography'] ?? null;
                }

                $imageUrl = $item['images']['print']['url'] ?? $item['images']['web']['url'] ?? null;
                $thumbnailUrl = $item['images']['web']['url'] ?? null;

                if (!$imageUrl) return null;

                $description = $item['description'] ?? $item['did_you_know'] ?? null;
                // Cleveland API açıklamaları bazen HTML etiketleriyle sarılı olarak döner, deepL/wiki eşleme için temizle.
                if ($description) {
                    $description = strip_tags($description);
                }

                return [
                    'title' => $item['title'] ?? 'İsimsiz',
                    'artist' => $artist,
                    'artist_bio' => $artistBio,
                    'date_display' => $item['creation_date'] ?? null,
                    'medium' => $item['technique'] ?? null,
                    'dimensions' => $item['dimensions'] ?? null,
                    'image_url' => $imageUrl,
                    'thumbnail_url' => $thumbnailUrl,
                    'provider' => 'Cleveland Museum of Art',
                    'external_id' => $this->sanitizeId($item['id'] ?? uniqid()),
                    'external_url' => $item['url'] ?? null,
                    'description_en' => $description,
                    'is_public_domain' => 1
                ];
            }
        }
        return null;
    }
}
