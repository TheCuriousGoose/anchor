<?php

namespace App\Support;

use DOMComment;
use DOMDocument;
use DOMElement;
use DOMNode;
use DOMXPath;

/**
 * Strips note body HTML down to a small allow-list of rich-text and
 * checklist tags produced by the Tiptap editor, so a raw API request
 * can never persist script tags or event-handler attributes.
 */
class HtmlSanitizer
{
    private const ALLOWED_TAGS = [
        'p', 'br', 'strong', 'b', 'em', 'i', 's', 'strike', 'u',
        'h1', 'h2', 'h3', 'ul', 'ol', 'li', 'blockquote', 'code', 'pre',
        'span', 'div', 'label', 'input', 'hr',
    ];

    /** @var array<string, array<int, string>> */
    private const ALLOWED_ATTRIBUTES = [
        'ul' => ['data-type'],
        'li' => ['data-type', 'data-checked'],
        'input' => ['type', 'checked', 'disabled'],
    ];

    private const REMOVE_ENTIRELY = ['script', 'style', 'iframe', 'object', 'embed', 'form'];

    public static function clean(string $html): string
    {
        if (trim($html) === '') {
            return '';
        }

        $document = new DOMDocument;
        libxml_use_internal_errors(true);
        $document->loadHTML(
            '<?xml encoding="utf-8" ?><div id="sanitizer-root">'.$html.'</div>',
            LIBXML_NOERROR | LIBXML_NOWARNING,
        );
        libxml_clear_errors();

        $xpath = new DOMXPath($document);
        $matches = $xpath->query('//div[@id="sanitizer-root"]');
        $root = $matches === false ? null : $matches->item(0);

        if (! $root instanceof DOMElement) {
            return '';
        }

        self::sanitizeChildren($root);

        $result = '';

        foreach (iterator_to_array($root->childNodes) as $child) {
            $result .= $document->saveHTML($child);
        }

        return trim($result);
    }

    private static function sanitizeChildren(DOMNode $node): void
    {
        foreach (iterator_to_array($node->childNodes) as $child) {
            if ($child instanceof DOMComment) {
                $node->removeChild($child);

                continue;
            }

            if (! $child instanceof DOMElement) {
                continue;
            }

            $tag = strtolower($child->tagName);

            if (in_array($tag, self::REMOVE_ENTIRELY, true)) {
                $node->removeChild($child);

                continue;
            }

            if (! in_array($tag, self::ALLOWED_TAGS, true)) {
                self::sanitizeChildren($child);

                while ($child->firstChild) {
                    $node->insertBefore($child->firstChild, $child);
                }

                $node->removeChild($child);

                continue;
            }

            if ($tag === 'input' && strtolower($child->getAttribute('type')) !== 'checkbox') {
                $node->removeChild($child);

                continue;
            }

            $allowedAttributes = self::ALLOWED_ATTRIBUTES[$tag] ?? [];

            foreach (iterator_to_array($child->attributes ?? []) as $attribute) {
                if (! in_array(strtolower($attribute->name), $allowedAttributes, true)) {
                    $child->removeAttribute($attribute->name);
                }
            }

            self::sanitizeChildren($child);
        }
    }
}
