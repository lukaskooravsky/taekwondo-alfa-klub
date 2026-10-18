<?php
declare(strict_types=1);

/*
 * Formátovaný text z editora (Quill v admine). Server prepustí len povolené značky, triedy a farby —
 * všetko ostatné (skripty, on* atribúty, javascript: odkazy, cudzie štýly) zahodí. Čistí sa pri uložení
 * aj pri zobrazení, takže ani ručne upravený data/*.json nepodstrčí do webu nebezpečné HTML.
 */

const RICH_FONTS = ['saira', 'georgia', 'arial', 'courier'];   // + predvolený Barlow (bez triedy)
const RICH_SIZES = ['small', 'large', 'huge'];
/** Farby z lišty editora — všetky čitateľné na čiernom pozadí webu. */
const RICH_COLORS = ['#f3efe6', '#c3bdb1', '#948e83', '#e6bd6a', '#c8a04e', '#ef5350', '#ff9f43', '#66bb6a', '#4fc3f7', '#ba68c8'];

const RICH_BLOCK = ['p', 'h2', 'h3', 'ul', 'ol', 'li'];
const RICH_INLINE = ['br', 'strong', 'em', 'u', 's', 'a', 'span'];
/** Prvky, ktoré sa zahodia aj s obsahom (ostatné nepovolené sa „rozbalia“ — ostane ich text). */
const RICH_DROP = ['script', 'style', 'iframe', 'object', 'embed', 'svg', 'math', 'template', 'noscript', 'form',
    'input', 'button', 'textarea', 'select', 'img', 'video', 'audio', 'canvas', 'head', 'title', 'meta', 'link', 'base'];

/** Bezpečné HTML z editora. $inline = len riadkové formátovanie (krátky úvod): odseky a zoznamy → <br>. */
function rich_clean(string $html, bool $inline = false): string
{
    $html = trim(str_replace(["\r\n", "\r"], "\n", $html));
    if ($html === '') return '';
    if (!class_exists(DOMDocument::class)) return '<p>' . nl2br(e(rich_text($html)), false) . '</p>';

    $doc = new DOMDocument();
    @$doc->loadHTML('<?xml encoding="UTF-8"><div id="rich-root">' . $html . '</div>',
        LIBXML_NONET | LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD | LIBXML_NOERROR | LIBXML_NOWARNING);
    $root = $doc->getElementById('rich-root');
    if (!$root) return '';
    rich_lists($root);
    rich_walk($root, $inline);

    $out = '';
    foreach ($root->childNodes as $n) $out .= $doc->saveHTML($n);
    if ($inline) $out = preg_replace('~^(?:<br>)+|(?:<br>)+$~', '', trim($out));
    return rich_text($out) === '' ? '' : trim($out);
}

/** Quill ukladá všetky zoznamy ako <ol><li data-list="bullet|ordered">; prepíšeme na <ul>/<ol>. */
function rich_lists(DOMElement $root): void
{
    $doc = $root->ownerDocument;
    foreach (iterator_to_array($root->getElementsByTagName('ol')) as $ol) {
        $items = array_filter(iterator_to_array($ol->childNodes), fn($n) => $n instanceof DOMElement && $n->tagName === 'li');
        if (!$items || !array_filter($items, fn($li) => $li->hasAttribute('data-list'))) continue;
        $current = null;
        $type = null;
        foreach ($items as $li) {
            $t = $li->getAttribute('data-list') === 'bullet' ? 'ul' : 'ol';
            if ($t !== $type) {
                $current = $doc->createElement($t);
                $ol->parentNode->insertBefore($current, $ol);
                $type = $t;
            }
            $current->appendChild($li);
        }
        $ol->parentNode->removeChild($ol);
    }
}

function rich_walk(DOMNode $node, bool $inline): void
{
    foreach (iterator_to_array($node->childNodes) as $child) {
        if ($child instanceof DOMText) continue;
        if (!$child instanceof DOMElement) {                 // komentáre, CDATA, PI
            $node->removeChild($child);
            continue;
        }
        $tag = strtolower($child->tagName);
        if (in_array($tag, RICH_DROP, true) || str_contains(' ' . $child->getAttribute('class') . ' ', ' ql-ui ')) {
            $node->removeChild($child);
            continue;
        }
        rich_walk($child, $inline);
        $map = ['b' => 'strong', 'i' => 'em', 'strike' => 's', 'del' => 's', 'h1' => 'h2', 'h4' => 'h3', 'h5' => 'h3', 'h6' => 'h3'];
        if (isset($map[$tag])) $child = rich_rename($child, $tag = $map[$tag]);

        $allowed = $inline ? RICH_INLINE : array_merge(RICH_BLOCK, RICH_INLINE);
        if (!in_array($tag, $allowed, true)) {
            $block = in_array($tag, ['p', 'h2', 'h3', 'li', 'div'], true);
            rich_unwrap($child, $inline && $block);
            continue;
        }
        rich_attrs($child, $tag);
        if ($tag === 'span' && !$child->hasAttributes()) rich_unwrap($child, false);
    }
}

function rich_attrs(DOMElement $el, string $tag): void
{
    $keep = [];
    if ($tag === 'a') {
        $href = trim($el->getAttribute('href'));
        if (preg_match('~^(https?://|mailto:)~i', $href)) {
            $keep = ['href' => $href, 'target' => '_blank', 'rel' => 'noopener'];
        }
    }
    if (in_array($tag, ['span', 'p', 'h2', 'h3', 'li', 'strong', 'em', 'u', 's', 'a'], true)) {
        $classes = [];
        foreach (preg_split('/\s+/', $el->getAttribute('class'), -1, PREG_SPLIT_NO_EMPTY) as $c) {
            if (preg_match('/^ql-font-(.+)$/', $c, $m) && in_array($m[1], RICH_FONTS, true)) $classes[] = $c;
            if (preg_match('/^ql-size-(.+)$/', $c, $m) && in_array($m[1], RICH_SIZES, true)) $classes[] = $c;
        }
        if ($classes) $keep['class'] = implode(' ', array_unique($classes));
        if (preg_match('/(?:^|;)\s*color\s*:\s*([^;]+)/i', $el->getAttribute('style'), $m)) {
            $hex = rich_color($m[1]);
            if ($hex !== null) $keep['style'] = 'color:' . $hex;
        }
    }
    foreach (iterator_to_array($el->attributes) as $attr) $el->removeAttribute($attr->name);
    foreach ($keep as $k => $v) $el->setAttribute($k, $v);
    if ($tag === 'a' && !isset($keep['href'])) rich_unwrap($el, false);
}

/** #rgb, #rrggbb alebo rgb(r, g, b) → #rrggbb, ak je v palete; inak null. */
function rich_color(string $v): ?string
{
    $v = strtolower(trim($v));
    if (preg_match('/^#([0-9a-f])([0-9a-f])([0-9a-f])$/', $v, $m)) $v = "#$m[1]$m[1]$m[2]$m[2]$m[3]$m[3]";
    elseif (preg_match('/^rgb\(\s*(\d{1,3})\s*,\s*(\d{1,3})\s*,\s*(\d{1,3})\s*\)$/', $v, $m)) {
        $v = sprintf('#%02x%02x%02x', min(255, (int)$m[1]), min(255, (int)$m[2]), min(255, (int)$m[3]));
    }
    return in_array($v, RICH_COLORS, true) ? $v : null;
}

function rich_rename(DOMElement $el, string $tag): DOMElement
{
    $new = $el->ownerDocument->createElement($tag);
    foreach (iterator_to_array($el->attributes) as $attr) $new->setAttribute($attr->name, $attr->value);
    while ($el->firstChild) $new->appendChild($el->firstChild);
    $el->parentNode->replaceChild($new, $el);
    return $new;
}

/** Nahradí prvok jeho obsahom; $br = za blokom vlož zalomenie riadka (pre riadkový režim). */
function rich_unwrap(DOMElement $el, bool $br): void
{
    $parent = $el->parentNode;
    while ($el->firstChild) $parent->insertBefore($el->firstChild, $el);
    if ($br && $el->nextSibling) $parent->insertBefore($el->ownerDocument->createElement('br'), $el);
    $parent->removeChild($el);
}

/** Čistý text z HTML (dĺžka, meta description, kontrola prázdnoty). */
function rich_text(string $html): string
{
    $html = preg_replace('~<br\s*/?>|</(p|h[1-6]|li|div)>~i', "\n", $html);
    $text = html_entity_decode(strip_tags($html), ENT_QUOTES | ENT_HTML5, 'UTF-8');
    $text = preg_replace('/[ \t\x{00A0}]+/u', ' ', $text);
    return trim(preg_replace('/\s*\n\s*/', "\n", $text));
}

/** Prostý text (staré aktuality) → HTML pre editor. */
function rich_from_text(string $text, bool $inline = false): string
{
    if ($inline) {
        $text = trim(str_replace(["\r\n", "\r"], "\n", $text));
        return $text === '' ? '' : '<p>' . str_replace("\n", '<br>', e($text)) . '</p>';
    }
    return text_to_html($text);
}
