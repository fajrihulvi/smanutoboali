<?php

namespace App\Modules\Cms\Services;

use Symfony\Component\HtmlSanitizer\HtmlSanitizer;
use Symfony\Component\HtmlSanitizer\HtmlSanitizerConfig;

/**
 * Sanitasi HTML dari editor sebelum disimpan (whitelist tag & atribut).
 * Script, style, iframe, event handler, dan skema `javascript:` dibuang.
 * Pembungkus tak dikenal (div/span dari tempelan) dilepas, teksnya dipertahankan.
 */
class ContentSanitizer
{
    private const MAX_LENGTH = 500_000;

    private ?HtmlSanitizer $sanitizer = null;

    public function clean(?string $html): string
    {
        $html = trim((string) $html);

        if ($html === '') {
            return '';
        }

        $clean = trim($this->sanitizer()->sanitize($html));

        // Editor kosong menghasilkan <p></p>.
        return trim(strip_tags($clean, '<img><hr>')) === '' ? '' : $clean;
    }

    private function sanitizer(): HtmlSanitizer
    {
        if ($this->sanitizer !== null) {
            return $this->sanitizer;
        }

        $config = (new HtmlSanitizerConfig)
            ->allowLinkSchemes(['http', 'https', 'mailto', 'tel'])
            ->allowRelativeLinks()
            ->allowMediaSchemes(['http', 'https'])
            ->allowRelativeMedias()
            ->withMaxInputLength(self::MAX_LENGTH);

        foreach (['p', 'h2', 'h3', 'h4', 'strong', 'b', 'em', 'i', 'u', 's', 'ul', 'ol', 'li', 'blockquote', 'br', 'hr', 'figure', 'figcaption', 'code', 'pre', 'table', 'thead', 'tbody', 'tr', 'colgroup', 'col'] as $tag) {
            $config = $config->allowElement($tag);
        }

        $config = $config
            ->allowElement('a', ['href', 'title', 'target'])
            ->forceAttribute('a', 'rel', 'noopener noreferrer')
            ->allowElement('img', ['src', 'alt', 'title', 'width', 'height'])
            ->allowElement('th', ['colspan', 'rowspan'])
            ->allowElement('td', ['colspan', 'rowspan'])
            ->blockElement('div')
            ->blockElement('span')
            ->blockElement('h1')
            ->blockElement('h5')
            ->blockElement('h6');

        return $this->sanitizer = new HtmlSanitizer($config);
    }
}
