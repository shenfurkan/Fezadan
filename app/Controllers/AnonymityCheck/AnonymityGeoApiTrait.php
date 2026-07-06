<?php
trait AnonymityGeoApiTrait
{
    public function torCheck() {
        header('Content-Type: application/json; charset=utf-8');
        header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');

        $result = [
            'ok' => false,
            'source' => 'check.torproject.org/api/ip',
            'is_tor' => null,
            'ip' => null,
            'error' => null,
        ];

        $context = stream_context_create([
            'http' => [
                'timeout' => 3,
                'ignore_errors' => true,
                'header' => "Accept: application/json\r\nUser-Agent: Fezadan-AnonymityCheck/1.0\r\n",
            ],
        ]);

        $body = @file_get_contents('https://check.torproject.org/api/ip', false, $context);
        if ($body === false) {
            $result['error'] = 'Tor Project request failed';
            echo json_encode($result, JSON_UNESCAPED_SLASHES);
            exit;
        }

        $payload = json_decode($body, true);
        if (!is_array($payload) || !array_key_exists('IsTor', $payload)) {
            $result['error'] = 'Unexpected Tor Project response';
            echo json_encode($result, JSON_UNESCAPED_SLASHES);
            exit;
        }

        $result['ok'] = true;
        $result['is_tor'] = (bool)$payload['IsTor'];
        $result['ip'] = isset($payload['IP']) ? (string)$payload['IP'] : null;

        echo json_encode($result, JSON_UNESCAPED_SLASHES);
        exit;
    }

    public function ispLookup() {
        header('Content-Type: application/json; charset=utf-8');
        header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');

        $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
        $geo = $this->geolocateIp($ip);
        if ($geo['ok'] && ($geo['asn'] || $geo['org'] || $geo['isp'])) {
            echo json_encode([
                'ok' => true,
                'org' => $geo['org'] ?: ($geo['isp'] ?: ''),
                'asn' => $geo['asn'] ?: '',
            ], JSON_UNESCAPED_SLASHES);
        } else {
            echo json_encode(['ok' => false, 'error' => $geo['error'] ?? 'ISP lookup failed'], JSON_UNESCAPED_SLASHES);
        }
        exit;
    }

    public function geoLookup() {
        header('Content-Type: application/json; charset=utf-8');
        header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');

        $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
        $dir = ROOT . '/storage/anonymity';
        if (!is_dir($dir)) {
            @mkdir($dir, 0755, true);
        }

        $salt = defined('APP_SALT') ? APP_SALT : 'fezadan-anonymity-check';
        $hash = hash('sha256', 'geo_lookup|' . $ip . '|' . date('Y-m-d') . '|' . $salt);
        $rateFile = $dir . '/georate_' . $hash . '.json';
        $now = time();
        $window = 60;
        $limit = 5;
        $hits = [];
        if (is_file($rateFile)) {
            $decoded = json_decode((string)@file_get_contents($rateFile), true);
            if (is_array($decoded)) {
                foreach ($decoded as $ts) {
                    if ((int)$ts > ($now - $window)) {
                        $hits[] = (int)$ts;
                    }
                }
            }
        }
        if (count($hits) >= $limit) {
            http_response_code(429);
            echo json_encode(['ok' => false, 'error' => 'Too many requests']);
            exit;
        }
        $hits[] = $now;
        @file_put_contents($rateFile, json_encode($hits), LOCK_EX);
        @chmod($rateFile, 0600);
        $this->cleanupOldResults($dir);

        $result = $this->geolocateIp($ip);
        echo json_encode($result, JSON_UNESCAPED_SLASHES);
        exit;
    }

    private function geolocateIp(string $ip, bool $allowExternal = true): array {
        $result = [
            'ok' => false,
            'sources' => [],
            'country_code' => '',
            'country' => '',
            'city' => '',
            'region' => '',
            'latitude' => null,
            'longitude' => null,
            'timezone' => '',
            'isp' => '',
            'org' => '',
            'asn' => '',
            'error' => null,
        ];

        if (getenv('APP_ENV') === 'testing') {
            return [
                'ok' => true,
                'sources' => ['testing_mock'],
                'country_code' => 'TR',
                'country' => 'Turkey',
                'city' => 'Istanbul',
                'region' => 'Istanbul',
                'latitude' => 41.0082,
                'longitude' => 28.9784,
                'timezone' => 'Europe/Istanbul',
                'isp' => 'Mock ISP',
                'org' => 'Mock Organization',
                'asn' => 'AS12345',
                'error' => null,
            ];
        }

        $hasCity = false;
        $hasAsn = false;

        // Katman 1: Yerel Şehir MMDB (MaxMind GeoLite2-City veya DB-IP City Lite)
        $cityDbCandidates = [
            ROOT . '/storage/anonymity/GeoLite2-City.mmdb' => 'maxmind_city',
            ROOT . '/storage/anonymity/dbip-city-lite.mmdb' => 'dbip_city',
        ];
        foreach ($cityDbCandidates as $cityDbPath => $citySourceLabel) {
            if (!is_file($cityDbPath)) continue;
            try {
                $reader = new \MaxMind\Db\Reader($cityDbPath);
                $record = $reader->get($ip);
                $reader->close();
                if ($record && isset($record['country']['iso_code'])) {
                    $result['country_code'] = (string)$record['country']['iso_code'];
                    $result['country'] = (string)($record['country']['names']['en'] ?? $record['country']['iso_code']);
                    $result['sources'][] = $citySourceLabel;
                    $hasCity = true;
                    if (isset($record['city']['names']['en'])) {
                        $result['city'] = (string)$record['city']['names']['en'];
                    }
                    if (isset($record['subdivisions'][0]['names']['en'])) {
                        $result['region'] = (string)$record['subdivisions'][0]['names']['en'];
                    }
                    if (isset($record['location']['latitude'])) {
                        $result['latitude'] = (float)$record['location']['latitude'];
                    }
                    if (isset($record['location']['longitude'])) {
                        $result['longitude'] = (float)$record['location']['longitude'];
                    }
                    if (isset($record['location']['time_zone'])) {
                        $result['timezone'] = (string)$record['location']['time_zone'];
                    }
                }
                break;
            } catch (\Exception $e) {
                // Sonraki kaynağa düş
            }
        }

        // Katman 2: Yerel ASN MMDB (MaxMind GeoLite2-ASN veya DB-IP ASN Lite)
        $asnDbCandidates = [
            ROOT . '/storage/anonymity/GeoLite2-ASN.mmdb' => 'maxmind_asn',
            ROOT . '/storage/anonymity/dbip-asn-lite.mmdb' => 'dbip_asn',
        ];
        foreach ($asnDbCandidates as $asnDbPath => $asnSourceLabel) {
            if (!is_file($asnDbPath)) continue;
            try {
                $reader = new \MaxMind\Db\Reader($asnDbPath);
                $record = $reader->get($ip);
                $reader->close();
                if ($record && !empty($record['autonomous_system_organization'])) {
                    $result['asn'] = isset($record['autonomous_system_number'])
                        ? 'AS' . $record['autonomous_system_number']
                        : '';
                    $result['org'] = (string)$record['autonomous_system_organization'];
                    $result['sources'][] = $asnSourceLabel;
                    $hasAsn = true;
                }
                break;
            } catch (\Exception $e) {
                // Sonraki kaynağa düş
            }
        }

        // Katman 3: ip-api.com ücretsiz katman (45 istek/dk, anahtar gerekmez). Yalnızca kullanıcı tetiklemeli uç noktalardan sonra çalışır.
        if ($allowExternal && (!$hasCity || !$hasAsn)) {
            $apiUrl = 'http://ip-api.com/json/' . $ip
                . '?fields=status,message,country,countryCode,regionName,city,lat,lon,timezone,isp,org,as,query';
            $context = stream_context_create([
                'http' => [
                    'timeout' => 3,
                    'ignore_errors' => true,
                    'header' => "Accept: application/json\r\nUser-Agent: Fezadan-AnonymityCheck/1.0\r\n",
                ],
            ]);
            $body = @file_get_contents($apiUrl, false, $context);
            if ($body !== false) {
                $payload = json_decode($body, true);
                if (is_array($payload) && ($payload['status'] ?? '') === 'success') {
                    if (!$hasCity) {
                        $result['country_code'] = (string)($payload['countryCode'] ?? '');
                        $result['country'] = (string)($payload['country'] ?? '');
                        $result['city'] = (string)($payload['city'] ?? '');
                        $result['region'] = (string)($payload['regionName'] ?? '');
                        $result['latitude'] = isset($payload['lat']) ? (float)$payload['lat'] : null;
                        $result['longitude'] = isset($payload['lon']) ? (float)$payload['lon'] : null;
                        $result['timezone'] = (string)($payload['timezone'] ?? '');
                    }
                    if (!$hasAsn && isset($payload['as'])) {
                        $asRaw = (string)$payload['as'];
                        if (preg_match('/^AS(\d+)\s+(.+)$/', $asRaw, $m)) {
                            $result['asn'] = 'AS' . $m[1];
                            $result['org'] = $m[2];
                        } else {
                            $result['asn'] = $asRaw;
                        }
                        if (!empty($payload['isp'])) {
                            $result['isp'] = (string)$payload['isp'];
                        }
                        if (empty($result['org']) && !empty($payload['org'])) {
                            $result['org'] = (string)$payload['org'];
                        }
                    }
                    if (!in_array('ip-api.com', $result['sources'], true)) {
                        $result['sources'][] = 'ip-api.com';
                    }
                } elseif (($payload['status'] ?? '') === 'fail') {
                    $result['error'] = $payload['message'] ?? 'API request failed';
                }
            }
        }

        $result['ok'] = !empty($result['sources']);
        if (!$result['ok'] && $result['error'] === null) {
            $result['error'] = 'All geolocation sources unavailable';
        }
        return $result;
    }
}
