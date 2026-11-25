<?php

/** Restricted import HTML and canonical, plain-text TeX formula nodes. */
class ScientificContent
{
    public const MAX_HTML_BYTES = 524288;
    public const MAX_MATH_BYTES = 12000;
    public const MAX_FORMULAS = 1000;

    /** @return array{html: string, warnings: array, formula_count: int} */
    public static function prepareImport(string $html): array
    {
        if (!mb_check_encoding($html, 'UTF-8')) {
            throw new InvalidArgumentException('İçerik UTF-8 olarak okunamadı.');
        }
        if (strlen($html) > self::MAX_HTML_BYTES) {
            throw new InvalidArgumentException('Aktarılan içerik 512 KB sınırını aşıyor.');
        }
        [, $body] = self::parse($html);
        [$output, $cleanBody] = self::parse('');
        $warnings = [];
        $formulaCount = 0;
        $allowed = ['p', 'br', 'h2', 'h3', 'h4', 'h5', 'h6', 'strong', 'b', 'em', 'i', 'u', 's',
            'sub', 'sup', 'ul', 'ol', 'li', 'blockquote', 'pre', 'code', 'hr', 'table', 'thead',
            'tbody', 'tfoot', 'tr', 'th', 'td', 'caption', 'a', 'span', 'div', 'section', 'figure', 'figcaption'];
        $discard = ['script', 'style', 'iframe', 'object', 'embed', 'svg', 'math', 'template',
            'form', 'input', 'button', 'textarea', 'select', 'link', 'meta', 'base', 'audio', 'video'];

        $copy = function (DOMNode $node, DOMNode $parent, int $depth) use (&$copy, $output, &$warnings, &$formulaCount, $allowed, $discard): void {
            if ($depth > 64) {
                throw new InvalidArgumentException('Belge yapısı çok derin; daha sade bir belge aktarın.');
            }
            if ($node instanceof DOMText) {
                $parent->appendChild($output->createTextNode($node->textContent));
                return;
            }
            if (!$node instanceof DOMElement) return;
            $tag = strtolower($node->tagName);
            if (in_array($tag, $discard, true)) {
                $warnings[] = 'Desteklenmeyen veya etkin içerik çıkarıldı: ' . $tag;
                return;
            }
            if ($tag === 'span' && (self::hasClass($node, 'math') || self::hasClass($node, 'fezadan-math'))) {
                $source = trim($node->textContent);
                $display = self::hasClass($node, 'display') || $node->getAttribute('data-math-display') === '1';
                if ((substr($source, 0, 2) === '\\(' && substr($source, -2) === '\\)')
                    || (substr($source, 0, 2) === '\\[' && substr($source, -2) === '\\]')) {
                    $source = trim(substr($source, 2, -2));
                }
                self::checkFormula($source, ++$formulaCount);
                $parent->appendChild(self::formula($output, $source, $display));
                return;
            }
            if ($tag === 'img') {
                // Inline: images often sit inside a paragraph, and <p> cannot nest.
                $target = $output->createElement('span');
                $target->setAttribute('class', 'fezadan-import-error');
                $target->setAttribute('data-import-error', '1');
                $target->appendChild($output->createTextNode('Görsel aktarılmadı: ' . mb_substr($node->getAttribute('alt') ?: $node->getAttribute('src'), 0, 200) . '. Görseli editörden ekleyin veya bu uyarıyı kaldırın.'));
                $parent->appendChild($target);
                $warnings[] = 'Bu sürüm görselleri otomatik aktarmıyor; eksik görseller içerikte işaretlendi.';
                return;
            }
            if ($tag === 'h1') $tag = 'h2';
            $target = $parent;
            if (in_array($tag, $allowed, true)) {
                $target = $output->createElement($tag);
                if (preg_match('/^[\p{L}][\p{L}\p{N}_.:-]{0,119}$/uD', $node->getAttribute('id'))) {
                    $target->setAttribute('id', $node->getAttribute('id'));
                }
                if ($node->getAttribute('data-import-error') === '1') {
                    $target->setAttribute('class', 'fezadan-import-error');
                    $target->setAttribute('data-import-error', '1');
                    $warnings[] = 'Aktarılamayan bölümler içerikte işaretlendi; yayınlamadan önce kontrol edin.';
                }
                if ($tag === 'a' && self::safeLink($node->getAttribute('href'))) {
                    $target->setAttribute('href', trim($node->getAttribute('href')));
                    $target->setAttribute('rel', 'noopener noreferrer');
                }
                foreach (['colspan', 'rowspan', 'start'] as $attribute) {
                    $value = $node->getAttribute($attribute);
                    if (in_array($tag, ['th', 'td', 'ol'], true) && ctype_digit($value) && (int)$value > 0 && (int)$value <= 100) {
                        $target->setAttribute($attribute, $value);
                    }
                }
                $parent->appendChild($target);
            } else {
                $warnings[] = 'Desteklenmeyen biçim sadeleştirildi: ' . $tag;
            }
            foreach ($node->childNodes as $child) $copy($child, $target, $depth + 1);
        };
        foreach ($body->childNodes as $node) $copy($node, $cleanBody, 0);
        return ['html' => self::innerHtml($output, $cleanBody), 'warnings' => array_values(array_unique($warnings)), 'formula_count' => $formulaCount];
    }

    public static function hasImportErrors(string $html): bool
    {
        if (strpos($html, 'data-import-error') === false) return false;
        [$dom] = self::parse($html);
        return (new DOMXPath($dom))->query('//*[@data-import-error="1"]')->length > 0;
    }

    /** Normalize only formula nodes, preserving the rest of an existing article. */
    public static function normalizeMath(string $html, bool $validate = false): string
    {
        if (strpos($html, 'fezadan-math') === false) return $html;
        [$dom, $body] = self::parse($html);
        $nodes = (new DOMXPath($dom))->query('//span[contains(concat(" ", normalize-space(@class), " "), " fezadan-math ")]');
        $count = 0;
        if ($nodes !== false) {
            foreach (iterator_to_array($nodes) as $node) {
                if (!$node instanceof DOMElement || !$node->parentNode) continue;
                $source = trim($node->textContent);
                if ($validate) self::checkFormula($source, ++$count);
                $node->parentNode->replaceChild(self::formula($dom, $source, $node->getAttribute('data-math-display') === '1'), $node);
            }
        }
        return self::innerHtml($dom, $body);
    }

    /** Link references in prose, never in formula source, code, links or attributes. */
    public static function linkReferences(string $html): string
    {
        if (strpos($html, 'fezadan-math') === false && !preg_match('/\[\d+\]/', $html)) return $html;
        // Keep legacy articles readable if the host lacks DOM; imports/saves report
        // the missing extension explicitly rather than attempting regex parsing.
        if (!class_exists(DOMDocument::class) || !class_exists(DOMXPath::class)) {
            error_log('[Fezadan][Degraded] Article reference formatting requires the PHP DOM extension.');
            return $html;
        }
        [$dom, $body] = self::parse(self::normalizeMath($html));
        $nodes = (new DOMXPath($dom))->query('//body//text()[not(ancestor::code or ancestor::pre or ancestor::a or ancestor::script or ancestor::style or ancestor::span[contains(concat(" ", normalize-space(@class), " "), " fezadan-math ")])]');
        $seen = [];
        if ($nodes !== false) {
            foreach (iterator_to_array($nodes) as $node) {
                if (!$node->parentNode || !preg_match('/\[\d+\]/', $node->textContent)) continue;
                $parts = preg_split('/(\[\d+\])/', $node->textContent, -1, PREG_SPLIT_DELIM_CAPTURE) ?: [];
                $fragment = $dom->createDocumentFragment();
                foreach ($parts as $part) {
                    if (!preg_match('/^\[(\d+)\]$/D', $part, $match)) {
                        $fragment->appendChild($dom->createTextNode($part));
                        continue;
                    }
                    $number = $match[1];
                    $sup = $dom->createElement('sup');
                    $sup->setAttribute('class', 'reference-sup');
                    $link = $dom->createElement('a');
                    $link->appendChild($dom->createTextNode($part));
                    $link->setAttribute('href', '#ref-item-' . $number);
                    $link->setAttribute('class', 'text-[var(--text-accent)] hover:underline');
                    $link->setAttribute('style', 'scroll-margin-top: 250px;');
                    if (!isset($seen[$number])) $link->setAttribute('id', 'ref-link-' . $number);
                    $seen[$number] = true;
                    $sup->appendChild($link);
                    $fragment->appendChild($sup);
                }
                $node->parentNode->replaceChild($fragment, $node);
            }
        }
        return self::innerHtml($dom, $body);
    }

    /**
     * Display-time allowlist for stored article HTML. Replaces the old regex
     * blocklist so an editor account cannot smuggle in new tags or attributes.
     */
    public static function sanitizeArticle(string $html): string
    {
        if (!class_exists(DOMDocument::class) || !class_exists(DOMXPath::class)) {
            // Fail safe: without DOM, show the source as text instead of trusting attributes.
            return nl2br(htmlspecialchars($html, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'));
        }
        // libxml does not know the HTML5 void <source> and would nest following markup inside it.
        $html = (string) preg_replace('/<source\b([^>]*?)\s*\/?>/i', '<source$1 />', $html);
        [$dom, $body] = self::parse($html);
        $allowed = ['p', 'br', 'span', 'div', 'strong', 'b', 'em', 'i', 'u', 's', 'small', 'sub',
            'sup', 'mark', 'h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'ul', 'ol', 'li', 'blockquote',
            'pre', 'code', 'a', 'img', 'figure', 'figcaption', 'picture', 'source', 'table',
            'thead', 'tbody', 'tfoot', 'tr', 'th', 'td', 'caption', 'hr', 'del', 'ins', 'abbr',
            'cite', 'q', 'kbd', 'samp', 'var', 'time', 'details', 'summary'];
        $discard = ['script', 'style', 'iframe', 'frame', 'frameset', 'object', 'embed', 'applet',
            'svg', 'math', 'template', 'form', 'input', 'button', 'select', 'option', 'optgroup',
            'textarea', 'link', 'meta', 'base', 'audio', 'video', 'track', 'canvas'];
        $global = ['class', 'id', 'title', 'lang', 'dir', 'style', 'data-math-display'];
        $perTag = [
            'a' => ['href', 'target', 'rel'],
            'img' => ['src', 'srcset', 'sizes', 'alt', 'width', 'height', 'loading', 'decoding', 'fetchpriority'],
            'source' => ['srcset', 'sizes', 'type', 'media'],
            'time' => ['datetime'],
            'th' => ['colspan', 'rowspan', 'headers', 'scope'],
            'td' => ['colspan', 'rowspan', 'headers'],
            'ol' => ['start', 'reversed', 'type'],
            'blockquote' => ['cite'],
            'q' => ['cite'],
            'del' => ['datetime', 'cite'],
            'ins' => ['datetime', 'cite'],
        ];

        $clean = function (DOMNode $node) use (&$clean, $allowed, $discard, $global, $perTag): void {
            if ($node instanceof DOMCdataSection || $node instanceof DOMComment || $node instanceof DOMProcessingInstruction) {
                $node->parentNode?->removeChild($node);
                return;
            }
            if (!$node instanceof DOMElement) {
                return; // Text nodes stay.
            }
            $tag = strtolower($node->tagName);
            if (!in_array($tag, $allowed, true)) {
                if (!$node->parentNode) {
                    return;
                }
                if (in_array($tag, $discard, true)) {
                    // Active or inert containers go away with their content.
                    $node->parentNode->removeChild($node);
                    return;
                }
                // Unsupported formatting tags keep their text; every moved child is re-checked.
                $children = iterator_to_array($node->childNodes);
                foreach ($children as $child) {
                    $node->parentNode->insertBefore($child, $node);
                }
                $node->parentNode->removeChild($node);
                foreach ($children as $child) {
                    $clean($child);
                }
                return;
            }
            foreach (iterator_to_array($node->attributes) as $attribute) {
                $name = strtolower($attribute->name);
                $keep = in_array($name, $global, true) || in_array($name, $perTag[$tag] ?? [], true);
                if ($keep && in_array($name, ['href', 'src', 'cite'], true)) {
                    $keep = self::safeUrl($attribute->value, $name === 'src');
                } elseif ($keep && $name === 'srcset') {
                    $keep = self::safeSrcset($attribute->value);
                } elseif ($keep && $name === 'style') {
                    $keep = self::safeStyle($attribute->value);
                } elseif ($keep && $name === 'target') {
                    $keep = $attribute->value === '_blank';
                }
                if (!$keep) {
                    $node->removeAttribute($attribute->name);
                }
            }
            if ($tag === 'a' && $node->getAttribute('target') === '_blank') {
                $node->setAttribute('rel', 'noopener noreferrer');
            }
            foreach (iterator_to_array($node->childNodes) as $child) {
                $clean($child);
            }
        };
        foreach (iterator_to_array($body->childNodes) as $child) {
            $clean($child);
        }
        return self::innerHtml($dom, $body);
    }

    private static function safeUrl(string $value, bool $image): bool
    {
        $value = trim($value);
        if ($value === '' || strlen($value) > 4096 || preg_match('/[\x00-\x20\x7f\\\\]/', $value) === 1) {
            return false;
        }
        if (str_starts_with($value, '#')) {
            return preg_match('/^#[\p{L}\p{N}_.:-]+$/uD', $value) === 1;
        }
        if (preg_match('~^([a-z][a-z0-9+.-]*:)~i', $value, $match) === 1) {
            $scheme = strtolower($match[1]);
            return in_array($scheme, $image ? ['http:', 'https:'] : ['http:', 'https:', 'mailto:'], true);
        }
        return true;
    }

    private static function safeSrcset(string $value): bool
    {
        if ($value === '' || strlen($value) > 8192 || stripos($value, 'data:') !== false) {
            return false;
        }
        foreach (explode(',', $value) as $candidate) {
            $url = preg_split('/\s+/', trim($candidate))[0] ?? '';
            if ($url === '' || !self::safeUrl($url, true)) {
                return false;
            }
        }
        return true;
    }

    private static function safeStyle(string $value): bool
    {
        return strlen($value) <= 2048
            && preg_match('/url\s*\(|expression\s*\(|javascript:|vbscript:|@import|behavior\s*:|-moz-binding|\\\\/i', $value) !== 1;
    }

    private static function checkFormula(string $source, int $count): void
    {
        if ($source === '' || strlen($source) > self::MAX_MATH_BYTES || $count > self::MAX_FORMULAS) {
            throw new InvalidArgumentException('Formül boş veya sınırı aşıyor (formül başına 12 KB, yazı başına 1000 formül).');
        }
    }

    private static function formula(DOMDocument $dom, string $source, bool $display): DOMElement
    {
        $span = $dom->createElement('span');
        $span->setAttribute('class', 'fezadan-math');
        $span->setAttribute('data-math-display', $display ? '1' : '0');
        $span->setAttribute('contenteditable', 'false');
        $span->appendChild($dom->createTextNode($source));
        return $span;
    }

    private static function hasClass(DOMElement $node, string $class): bool
    {
        return in_array($class, preg_split('/\s+/', trim($node->getAttribute('class'))) ?: [], true);
    }

    private static function safeLink(string $href): bool
    {
        $href = trim($href);
        return strlen($href) <= 2048 && !preg_match('/[\x00-\x20\x7f\\\\]/', $href)
            && preg_match('~^(?:https?://|/(?!/)|#[\p{L}\p{N}_.:-]+$)~iu', $href) === 1;
    }

    /** @return array{DOMDocument, DOMElement} */
    private static function parse(string $html): array
    {
        if (!class_exists(DOMDocument::class) || !class_exists(DOMXPath::class)) {
            throw new InvalidArgumentException('Bu işlem için sunucuda PHP DOM eklentisi gereklidir.');
        }
        $dom = new DOMDocument('1.0', 'UTF-8');
        $previous = libxml_use_internal_errors(true);
        try {
            $dom->loadHTML('<?xml encoding="UTF-8"><!DOCTYPE html><html><body>' . $html . '</body></html>', LIBXML_NONET);
        } finally {
            libxml_clear_errors();
            libxml_use_internal_errors($previous);
        }
        $body = $dom->getElementsByTagName('body')->item(0);
        if (!$body instanceof DOMElement) throw new InvalidArgumentException('İçerik HTML olarak okunamadı.');
        return [$dom, $body];
    }

    private static function innerHtml(DOMDocument $dom, DOMElement $body): string
    {
        $html = '';
        foreach ($body->childNodes as $node) $html .= $dom->saveHTML($node);
        return $html;
    }
}
