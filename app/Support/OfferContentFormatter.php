<?php

namespace App\Support;

/**
 * Turns offer content into e-mail-safe HTML. Content is either plain text with
 * blank-line separated paragraphs (AI output) or HTML from the rich-text editor.
 */
class OfferContentFormatter
{
    private const ALLOWED_TAGS = '<p><br><strong><b><em><i><u><ul><ol><li><h2><h3><blockquote><a>';

    public function toHtml(?string $content): string
    {
        $content = trim((string) $content);

        if ($content === '') {
            return '';
        }

        // Plain text: paragraphs on blank lines, single newlines become <br>.
        if (! preg_match('#<\s*/?\s*(p|br|strong|b|em|i|u|ul|ol|li|h[1-6]|blockquote|a|div|span)(?![a-z])#i', $content)) {
            $paragraphs = preg_split('/\R{2,}/', $content) ?: [];

            return implode("\n", array_map(
                fn (string $p): string => '<p>'.nl2br(e(trim($p))).'</p>',
                array_filter($paragraphs, fn (string $p): bool => trim($p) !== '')
            ));
        }

        return $this->sanitize($content);
    }

    private function sanitize(string $html): string
    {
        $html = (string) preg_replace('#<(script|style)\b[^>]*>.*?</\1>#is', '', $html);
        $html = strip_tags($html, self::ALLOWED_TAGS);

        // Drop every attribute except a safe href on links.
        return (string) preg_replace_callback('#<(\w+)([^>]*)>#', function (array $m): string {
            $tag = strtolower($m[1]);

            if ($tag === 'a' && preg_match('#href\s*=\s*"((?:https?://|mailto:)[^"]*)"#i', $m[2], $href)) {
                return '<a href="'.e(html_entity_decode($href[1])).'">';
            }

            return '<'.$tag.'>';
        }, $html);
    }
}
