<?php

require_once ROOT . '/app/Core/ArtProvider.php';

class ArtProviderMet extends ArtProvider {

    public function fetchArtwork() {
        // Step 1: Get a list of Object IDs for Paintings with images
        $searchUrl = "https://collectionapi.metmuseum.org/public/collection/v1/search?hasImages=true&q=Painting";
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $searchUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 2);
        curl_setopt($ch, CURLOPT_TIMEOUT, 5);
        $searchResponse = curl_exec($ch);
        curl_close($ch);

        if ($searchResponse) {
            $searchJson = json_decode($searchResponse, true);
            if (!empty($searchJson['objectIDs'])) {
                $objectIDs = $searchJson['objectIDs'];
                // Try a few IDs only; this runs during web requests.
                for ($i = 0; $i < 3; $i++) {
                    $randomId = $objectIDs[array_rand($objectIDs)];
                    
                    $objectUrl = "https://collectionapi.metmuseum.org/public/collection/v1/objects/{$randomId}";
                    $ch2 = curl_init();
                    curl_setopt($ch2, CURLOPT_URL, $objectUrl);
                    curl_setopt($ch2, CURLOPT_RETURNTRANSFER, true);
                    curl_setopt($ch2, CURLOPT_CONNECTTIMEOUT, 2);
                    curl_setopt($ch2, CURLOPT_TIMEOUT, 4);
                    $objectResponse = curl_exec($ch2);
                    curl_close($ch2);

                    if ($objectResponse) {
                        $item = json_decode($objectResponse, true);
                        if (!empty($item['primaryImage']) && $item['isPublicDomain']) {
                            
                            $titleLower = strtolower($item['title'] ?? '');
                            $mediumLower = strtolower($item['medium'] ?? '');
                            $objectNameLower = strtolower($item['objectName'] ?? '');
                            $classificationLower = strtolower($item['classification'] ?? '');
                            $haystack = $titleLower . ' ' . $mediumLower . ' ' . $objectNameLower . ' ' . $classificationLower;
                            $excludeWords = ['vase', 'pottery', 'ceramic', 'vessel', 'bowl', 'plate', 'fragment', 'sword', 'shield', 'album leaf', 'fan', 'calligraphy', 'manuscript', 'textile'];
                            $skip = false;
                            foreach ($excludeWords as $word) {
                                if (strpos($haystack, $word) !== false) {
                                    $skip = true;
                                    break;
                                }
                            }
                            if ($skip) continue;

                            $description = $item['objectDescription'] ?? null;
                            if ($description) {
                                $description = strip_tags($description);
                            }

                            return [
                                'title' => $item['title'] ?? 'İsimsiz',
                                'artist' => !empty($item['artistDisplayName']) ? $item['artistDisplayName'] : 'Bilinmeyen Sanatçı',
                                'artist_bio' => $item['artistDisplayBio'] ?? null,
                                'date_display' => $item['objectDate'] ?? null,
                                'medium' => $item['medium'] ?? null,
                                'dimensions' => $item['dimensions'] ?? null,
                                'image_url' => $item['primaryImage'],
                                'thumbnail_url' => $item['primaryImageSmall'] ?? $item['primaryImage'],
                                'provider' => 'Metropolitan Museum of Art',
                                'external_id' => $this->sanitizeId($item['objectID'] ?? uniqid()),
                                'external_url' => $item['objectURL'] ?? null,
                                'description_en' => $description,
                                'is_public_domain' => 1
                            ];
                        }
                    }
                }
            }
        }
        return null;
    }
}
