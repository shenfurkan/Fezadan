<?php

require_once ROOT . '/app/Core/ArtProvider.php';

class ArtProviderChicago extends ArtProvider {

    public function fetchArtwork() {
        $page = rand(1, 100);
        $url = "https://api.artic.edu/api/v1/artworks/search?query[term][is_public_domain]=true&query[term][artwork_type_title]=Painting&limit=1&page={$page}&fields=id,title,artist_title,date_display,medium_display,dimensions,image_id,description,api_link";

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 2);
        curl_setopt($ch, CURLOPT_TIMEOUT, 5);
        curl_setopt($ch, CURLOPT_USERAGENT, 'AIC-User-Agent: FezadanBot/1.0 (https://fezadan.org)');
        $response = curl_exec($ch);
        curl_close($ch);

        if ($response) {
            $json = json_decode($response, true);
            if (!empty($json['data']) && isset($json['data'][0])) {
                $item = $json['data'][0];

                $titleLower = strtolower($item['title'] ?? '');
                $mediumLower = strtolower($item['medium_display'] ?? '');
                $haystack = $titleLower . ' ' . $mediumLower;
                $excludeWords = ['vase', 'pottery', 'ceramic', 'vessel', 'bowl', 'plate', 'fragment', 'sword', 'shield', 'album leaf', 'fan', 'calligraphy', 'manuscript', 'textile'];
                foreach ($excludeWords as $word) {
                    if (strpos($haystack, $word) !== false) {
                        return null;
                    }
                }

                if (empty($item['image_id'])) {
                    return null;
                }

                $imageId = $item['image_id'];
                $imageUrl = "https://www.artic.edu/iiif/2/{$imageId}/full/1200,/0/default.jpg";
                $thumbnailUrl = "https://www.artic.edu/iiif/2/{$imageId}/full/800,/0/default.jpg";

                $description = $item['description'] ?? null;
                if ($description) {
                    $description = strip_tags($description);
                }

                return [
                    'title' => $item['title'] ?? 'İsimsiz',
                    'artist' => !empty($item['artist_title']) ? $item['artist_title'] : 'Bilinmeyen Sanatçı',
                    'artist_bio' => null, // Chicago API aramalarda bunu kolay döndürmez
                    'date_display' => $item['date_display'] ?? null,
                    'medium' => $item['medium_display'] ?? null,
                    'dimensions' => $item['dimensions'] ?? null,
                    'image_url' => $imageUrl,
                    'thumbnail_url' => $thumbnailUrl,
                    'provider' => 'Art Institute of Chicago',
                    'external_id' => $this->sanitizeId($item['id'] ?? uniqid()),
                    'external_url' => "https://www.artic.edu/artworks/" . $item['id'],
                    'description_en' => $description,
                    'is_public_domain' => 1
                ];
            }
        }
        return null;
    }
}
