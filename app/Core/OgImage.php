<?php
namespace App\Core;

class OgImage
{
    private const WIDTH = 1200;
    private const HEIGHT = 630;
    private const FONT_PATH = '/app/Core/Fonts/SpaceGrotesk-Variable.ttf';

    private const BG_COLOR = [254, 249, 225];
    private const TEXT_COLOR = [109, 35, 35];
    private const ACCENT_COLOR = [163, 29, 29];
    private const BORDER_COLOR = [163, 29, 29];

    private const BG_HEX = '#FEF9E1';
    private const TEXT_HEX = '#6D2323';
    private const ACCENT_HEX = '#A31D1D';

    public static function generate(string $title, string $authorName, string $categoryName = ''): ?string
    {
        $fontPath = \ROOT . self::FONT_PATH;
        $hasGd = extension_loaded('gd') && file_exists($fontPath);

        if ($hasGd) {
            $result = self::generatePng($title, $authorName, $categoryName, $fontPath);
            if ($result !== null) {
                return $result;
            }
        }

        return self::generateSvg($title, $authorName, $categoryName);
    }

    private static function generatePng(string $title, string $authorName, string $categoryName, string $fontPath): ?string
    {
        $img = imagecreatetruecolor(self::WIDTH, self::HEIGHT);
        if (!$img) {
            return null;
        }

        imagealphablending($img, true);
        imagesavealpha($img, true);

        $bg = imagecolorallocate($img, ...self::BG_COLOR);
        $textColor = imagecolorallocate($img, ...self::TEXT_COLOR);
        $accentColor = imagecolorallocate($img, ...self::ACCENT_COLOR);
        $borderColor = imagecolorallocate($img, ...self::BORDER_COLOR);

        imagefill($img, 0, 0, $bg);

        imageline($img, 0, 0, self::WIDTH - 1, 0, $borderColor);
        imageline($img, 0, self::HEIGHT - 1, self::WIDTH - 1, self::HEIGHT - 1, $borderColor);
        imageline($img, 0, 0, 0, self::HEIGHT - 1, $borderColor);
        imageline($img, self::WIDTH - 1, 0, self::WIDTH - 1, self::HEIGHT - 1, $borderColor);

        imageline($img, 60, 60, self::WIDTH - 60, 60, $borderColor);
        imageline($img, 60, self::HEIGHT - 60, self::WIDTH - 60, self::HEIGHT - 60, $borderColor);

        $y = 100;

        if ($categoryName !== '') {
            $catFontSize = 18;
            $catBox = imagettfbbox($catFontSize, 0, $fontPath, mb_strtoupper($categoryName));
            imagettftext($img, $catFontSize, 0, 80, $y + $catBox[1], $accentColor, $fontPath, mb_strtoupper($categoryName));
            $y += 50;
        }

        $titleFontSize = 52;
        $maxWidth = self::WIDTH - 160;
        $titleLines = self::wrapText($fontPath, $title, $titleFontSize, $maxWidth);
        $maxLines = 4;
        $titleLines = array_slice($titleLines, 0, $maxLines);

        foreach ($titleLines as $line) {
            $box = imagettfbbox($titleFontSize, 0, $fontPath, $line);
            $lineHeight = abs($box[7] - $box[1]);
            imagettftext($img, $titleFontSize, 0, 80, $y + $lineHeight, $textColor, $fontPath, $line);
            $y += $lineHeight + 16;
        }

        $y = self::HEIGHT - 110;
        imageline($img, 80, $y, 300, $y, $accentColor);
        $y += 30;

        $authorFontSize = 22;
        $authorLabel = mb_strtoupper($authorName ?: 'FEZADAN');
        imagettftext($img, $authorFontSize, 0, 80, $y, $accentColor, $fontPath, $authorLabel);

        $logoPath = \ROOT . '/public_html/cdn/logo-light.png';
        if (file_exists($logoPath)) {
            $logo = null;
            $ext = strtolower(pathinfo($logoPath, PATHINFO_EXTENSION));
            if ($ext === 'png') {
                $logo = @imagecreatefrompng($logoPath);
            }
            if ($logo) {
                $logoW = imagesx($logo);
                $logoH = imagesy($logo);
                $targetH = 50;
                $ratio = $targetH / $logoH;
                $targetW = (int)($logoW * $ratio);
                $logoX = self::WIDTH - 80 - $targetW;
                $logoY = $y - 30;
                imagecopyresampled($img, $logo, $logoX, $logoY, 0, 0, $targetW, $targetH, $logoW, $logoH);
                imagedestroy($logo);
            }
        }

        $tmpFile = tempnam(sys_get_temp_dir(), 'og_') . '.png';
        $ok = imagepng($img, $tmpFile, 9);
        imagedestroy($img);

        return $ok ? $tmpFile : null;
    }

    private static function generateSvg(string $title, string $authorName, string $categoryName): ?string
    {
        $w = self::WIDTH;
        $h = self::HEIGHT;
        $bgHex = self::BG_HEX;
        $accentHex = self::ACCENT_HEX;
        $textHex = self::TEXT_HEX;

        $titleLines = self::wrapTextSvg($title, 42);
        $titleLines = array_slice($titleLines, 0, 4);

        $titleY = $categoryName !== '' ? 160 : 120;
        $titleSvg = '';
        foreach ($titleLines as $i => $line) {
            $ly = $titleY + ($i * 60);
            $escaped = self::xmlEscape($line);
            $titleSvg .= "<text x=\"80\" y=\"{$ly}\" font-family=\"Georgia, serif\" font-size=\"48\" font-weight=\"bold\" fill=\"{$textHex}\">{$escaped}</text>\n";
        }

        $categorySvg = '';
        if ($categoryName !== '') {
            $categoryEsc = self::xmlEscape(mb_strtoupper($categoryName));
            $categorySvg = "<text x=\"80\" y=\"100\" font-family=\"Arial, sans-serif\" font-size=\"18\" font-weight=\"bold\" letter-spacing=\"2\" fill=\"{$accentHex}\">{$categoryEsc}</text>";
        }

        $authorEsc = self::xmlEscape(mb_strtoupper($authorName ?: 'FEZADAN'));
        $footerY = $h - 80;
        $lineY = $footerY - 20;
        $wMinus60 = $w - 60;
        $wMinus80 = $w - 80;
        $wMinus2 = $w - 2;
        $hMinus60 = $h - 60;
        $hMinus2 = $h - 2;

        $svg = <<<SVG
<?xml version="1.0" encoding="UTF-8"?>
<svg xmlns="http://www.w3.org/2000/svg" width="{$w}" height="{$h}" viewBox="0 0 {$w} {$h}">
<rect width="{$w}" height="{$h}" fill="{$bgHex}"/>
<rect x="1" y="1" width="{$wMinus2}" height="{$hMinus2}" fill="none" stroke="{$accentHex}" stroke-width="2"/>
<line x1="60" y1="60" x2="{$wMinus60}" y2="60" stroke="{$accentHex}" stroke-width="1"/>
<line x1="60" y1="{$hMinus60}" x2="{$wMinus60}" y2="{$hMinus60}" stroke="{$accentHex}" stroke-width="1"/>
{$categorySvg}
{$titleSvg}
<line x1="80" y1="{$lineY}" x2="300" y2="{$lineY}" stroke="{$accentHex}" stroke-width="2"/>
<text x="80" y="{$footerY}" font-family="Arial, sans-serif" font-size="22" font-weight="bold" fill="{$accentHex}">{$authorEsc}</text>
<text x="{$wMinus80}" y="{$footerY}" font-family="Arial, sans-serif" font-size="20" font-weight="bold" fill="{$accentHex}" text-anchor="end">FEZADAN</text>
</svg>
SVG;

        $tmpFile = tempnam(sys_get_temp_dir(), 'og_') . '.svg';
        $ok = file_put_contents($tmpFile, $svg) !== false;

        return $ok ? $tmpFile : null;
    }

    private static function wrapText(string $fontPath, string $text, int $fontSize, int $maxWidth): array
    {
        $words = explode(' ', $text);
        $lines = [];
        $currentLine = '';

        foreach ($words as $word) {
            $testLine = $currentLine === '' ? $word : $currentLine . ' ' . $word;
            $box = imagettfbbox($fontSize, 0, $fontPath, $testLine);
            $lineWidth = $box[2] - $box[0];

            if ($lineWidth > $maxWidth && $currentLine !== '') {
                $lines[] = $currentLine;
                $currentLine = $word;
            } else {
                $currentLine = $testLine;
            }
        }

        if ($currentLine !== '') {
            $lines[] = $currentLine;
        }

        return $lines;
    }

    private static function wrapTextSvg(string $text, int $maxCharsPerLine): array
    {
        $words = explode(' ', $text);
        $lines = [];
        $currentLine = '';

        foreach ($words as $word) {
            $testLine = $currentLine === '' ? $word : $currentLine . ' ' . $word;
            if (mb_strlen($testLine) > $maxCharsPerLine && $currentLine !== '') {
                $lines[] = $currentLine;
                $currentLine = $word;
            } else {
                $currentLine = $testLine;
            }
        }

        if ($currentLine !== '') {
            $lines[] = $currentLine;
        }

        return $lines;
    }

    private static function xmlEscape(string $s): string
    {
        return htmlspecialchars($s, ENT_XML1 | ENT_QUOTES, 'UTF-8');
    }
}
