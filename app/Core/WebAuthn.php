<?php

namespace App\Core;

class CborDecoder
{
    /**
     * Decodes a CBOR byte string.
     * Supports basic integers, byte/text strings, arrays, and maps.
     */
    public static function decode(string $data, int &$offset = 0)
    {
        if ($offset >= strlen($data)) {
            throw new \RuntimeException("CBOR: Unexpected end of input");
        }

        $byte = ord($data[$offset++]);
        $major = $byte >> 5;
        $val = $byte & 0x1f;

        if ($val === 24) {
            if ($offset >= strlen($data)) throw new \RuntimeException("CBOR: Unexpected end of input");
            $val = ord($data[$offset++]);
        } elseif ($val === 25) {
            if ($offset + 1 >= strlen($data)) throw new \RuntimeException("CBOR: Unexpected end of input");
            $val = (ord($data[$offset++]) << 8) | ord($data[$offset++]);
        } elseif ($val === 26) {
            if ($offset + 3 >= strlen($data)) throw new \RuntimeException("CBOR: Unexpected end of input");
            $val = (ord($data[$offset++]) << 24) | (ord($data[$offset++]) << 16) | (ord($data[$offset++]) << 8) | ord($data[$offset++]);
        } elseif ($val === 27) {
            if ($offset + 7 >= strlen($data)) throw new \RuntimeException("CBOR: Unexpected end of input");
            $val = 0;
            for ($i = 0; $i < 8; $i++) {
                $val = ($val << 8) | ord($data[$offset++]);
            }
        }

        switch ($major) {
            case 0: // Unsigned integer
                return $val;
            case 1: // Negative integer
                return -1 - $val;
            case 2: // Byte string
                if ($offset + $val > strlen($data)) throw new \RuntimeException("CBOR: Byte string length exceeds input size");
                $str = substr($data, $offset, $val);
                $offset += $val;
                return $str;
            case 3: // Text string
                if ($offset + $val > strlen($data)) throw new \RuntimeException("CBOR: Text string length exceeds input size");
                $str = substr($data, $offset, $val);
                $offset += $val;
                return $str;
            case 4: // Array
                $arr = [];
                for ($i = 0; $i < $val; $i++) {
                    $arr[] = self::decode($data, $offset);
                }
                return $arr;
            case 5: // Map
                $map = [];
                for ($i = 0; $i < $val; $i++) {
                    $k = self::decode($data, $offset);
                    $v = self::decode($data, $offset);
                    $map[$k] = $v;
                }
                return $map;
            default:
                throw new \RuntimeException("CBOR: Unsupported major type " . $major);
        }
    }
}

class WebAuthn
{
    public static function base64url_encode(string $data): string
    {
        return str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($data));
    }

    public static function base64url_decode(string $data): string
    {
        $len = strlen($data) % 4;
        if ($len) {
            $data .= str_repeat('=', 4 - $len);
        }
        return base64_decode(str_replace(['-', '_'], ['+', '/'], $data));
    }

    /**
     * Verifies WebAuthn credential registration.
     * Returns array containing base64url encoded credentialId and PEM public key.
     */
    public static function verifyRegistration(
        string $clientDataJsonB64,
        string $attestationObjectB64,
        string $sessionChallengeB64,
        string $expectedOrigin
    ): array {
        $clientDataJson = self::base64url_decode($clientDataJsonB64);
        $clientData = json_decode($clientDataJson, true);

        if (!is_array($clientData)) {
            throw new \InvalidArgumentException("Geçersiz clientDataJSON.");
        }

        // Validate type, challenge, origin
        if (($clientData['type'] ?? '') !== 'webauthn.create') {
            throw new \InvalidArgumentException("Geçersiz işlem tipi.");
        }

        if (($clientData['challenge'] ?? '') !== $sessionChallengeB64) {
            throw new \InvalidArgumentException("İşlem doğrulaması başarısız (challenge uyuşmazlığı).");
        }

        $origin = rtrim($clientData['origin'] ?? '', '/');
        $expectedOrigin = rtrim($expectedOrigin, '/');
        if ($origin !== $expectedOrigin) {
            throw new \InvalidArgumentException("Geçersiz kaynak kökeni (origin uyuşmazlığı): " . $origin . " vs " . $expectedOrigin);
        }

        $attestationObject = self::base64url_decode($attestationObjectB64);
        $offset = 0;
        $att = CborDecoder::decode($attestationObject, $offset);

        if (!is_array($att) || !isset($att['authData'])) {
            throw new \InvalidArgumentException("Geçersiz attestationObject.");
        }

        $authData = $att['authData'];
        if (strlen($authData) < 37) {
            throw new \InvalidArgumentException("Geçersiz authData boyutu.");
        }

        $flags = ord($authData[32]);
        $up = ($flags & 0x01) !== 0; // User Present
        $uv = ($flags & 0x04) !== 0; // User Verified
        $at = ($flags & 0x40) !== 0; // Attested credential data present

        if (!$at) {
            throw new \InvalidArgumentException("Attested credential data bulunamadı.");
        }

        // Read credential data (starts at index 37)
        // aaguid: 16 bytes (37-52)
        // credIdLen: 2 bytes (53-54)
        $credIdLen = (ord($authData[53]) << 8) | ord($authData[54]);
        if (strlen($authData) < 55 + $credIdLen) {
            throw new \InvalidArgumentException("Eksik authData boyutu.");
        }

        $credentialId = substr($authData, 55, $credIdLen);
        $publicKeyBytes = substr($authData, 55 + $credIdLen);

        $pkOffset = 0;
        $pubKeyMap = CborDecoder::decode($publicKeyBytes, $pkOffset);

        if (!is_array($pubKeyMap)) {
            throw new \InvalidArgumentException("Geçersiz public key COSE haritası.");
        }

        // Ensure EC2 algorithm ES256 (-7)
        $kty = $pubKeyMap[1] ?? null;
        $alg = $pubKeyMap[3] ?? null;
        $crv = $pubKeyMap[-1] ?? null;
        $x = $pubKeyMap[-2] ?? null;
        $y = $pubKeyMap[-3] ?? null;

        if ($kty !== 2 || $alg !== -7 || $crv !== 1 || !is_string($x) || !is_string($y)) {
            throw new \InvalidArgumentException("Yalnızca ES256 (P-256) algoritmaları desteklenmektedir.");
        }

        if (strlen($x) !== 32 || strlen($y) !== 32) {
            throw new \InvalidArgumentException("Geçersiz koordinat uzunlukları.");
        }

        // Convert coordinates to PEM format
        $der = pack('H*', '3059301306072a8648ce3d020106082a8648ce3d03010703420004') . $x . $y;
        $pem = "-----BEGIN PUBLIC KEY-----\n" . chunk_split(base64_encode($der), 64, "\n") . "-----END PUBLIC KEY-----\n";

        return [
            'credentialId' => self::base64url_encode($credentialId),
            'publicKey' => $pem,
        ];
    }

    /**
     * Verifies WebAuthn assertion login.
     * Returns true on success, throws exception on failure.
     */
    public static function verifyAssertion(
        string $clientDataJsonB64,
        string $authenticatorDataB64,
        string $signatureB64,
        string $sessionChallengeB64,
        string $expectedOrigin,
        string $pemPublicKey
    ): bool {
        $clientDataJson = self::base64url_decode($clientDataJsonB64);
        $clientData = json_decode($clientDataJson, true);

        if (!is_array($clientData)) {
            throw new \InvalidArgumentException("Geçersiz clientDataJSON.");
        }

        if (($clientData['type'] ?? '') !== 'webauthn.get') {
            throw new \InvalidArgumentException("Geçersiz işlem tipi.");
        }

        if (($clientData['challenge'] ?? '') !== $sessionChallengeB64) {
            throw new \InvalidArgumentException("İşlem doğrulaması başarısız (challenge uyuşmazlığı).");
        }

        $origin = rtrim($clientData['origin'] ?? '', '/');
        $expectedOrigin = rtrim($expectedOrigin, '/');
        if ($origin !== $expectedOrigin) {
            throw new \InvalidArgumentException("Geçersiz kaynak kökeni (origin uyuşmazlığı): " . $origin . " vs " . $expectedOrigin);
        }

        $authData = self::base64url_decode($authenticatorDataB64);
        $signature = self::base64url_decode($signatureB64);

        if (strlen($authData) < 37) {
            throw new \InvalidArgumentException("Geçersiz authenticatorData.");
        }

        $verifyData = $authData . hash('sha256', $clientDataJson, true);
        $ok = openssl_verify($verifyData, $signature, $pemPublicKey, OPENSSL_ALGO_SHA256);

        if ($ok !== 1) {
            throw new \InvalidArgumentException("İmza doğrulanamadı.");
        }

        return true;
    }

    public static function assertionSignCount(string $authenticatorDataB64): int
    {
        $authData = self::base64url_decode($authenticatorDataB64);
        if (strlen($authData) < 37) {
            throw new \InvalidArgumentException("Geçersiz authenticatorData.");
        }
        $parts = unpack('Ncount', substr($authData, 33, 4));
        return (int)($parts['count'] ?? 0);
    }
}
