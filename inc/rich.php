<?php
declare(strict_types=1);

/*
 * Formátovaný text z editora (Quill v admine). Server prepustí len povolené značky, triedy, farby a médiá —
 * všetko ostatné (skripty, on* atribúty, javascript: odkazy, cudzie štýly, cudzie obrázky/iframe) zahodí.
 * Čistí sa pri uložení aj pri zobrazení, takže ani ručne upravený data/*.json nepodstrčí do webu nebezpečné HTML.
 * Povolené písma, veľkosti a farby musia sedieť s lištou v admin/admin.js.
 */

const RICH_FONTS = ['saira', 'georgia', 'arial', 'courier'];   // + predvolený Barlow (bez triedy)
const RICH_SIZES = ['small', 'large', 'huge'];
const RICH_ALIGN = ['center', 'right', 'justify'];
/** Farby textu — všetky čitateľné na čiernom pozadí webu. */
const RICH_COLORS = ['#ffffff', '#f3efe6', '#c3bdb1', '#948e83', '#e6bd6a', '#c8a04e', '#ffd54f', '#ff9f43',
    '#ff8a65', '#ef5350', '#f48fb1', '#ba68c8', '#4fc3f7', '#80cbc4', '#66bb6a', '#aed581'];
/** Zvýraznenie (pozadie) — tmavé odtiene, na ktorých je svetlý text webu čitateľný. */
const RICH_BACKGROUNDS = ['#5c4a1f', '#3a2e14', '#4a1f1f', '#1f3d2a', '#1a3247', '#3b2449', '#2a2a31', '#4d3b10'];
/** Fotky vložené do textu — len vlastné z uploads/inline (nahráva admin/api/inline-image.php). */
const RICH_IMG_RE = '~^uploads/inline/i_[a-z0-9]{6,32}\.(jpg|png|webp)$~D';

const RICH_BLOCK = ['p', 'h2', 'h3', 'ul', 'ol', 'li', 'blockquote', 'hr'];
const RICH_INLINE = ['br', 'strong', 'em', 'u', 's', 'a', 'span'];
/** Prvky, ktoré sa zahodia aj s obsahom (ostatné nepovolené sa „rozbalia“ — ostane ich text). */
const RICH_DROP = ['script', 'style', 'object', 'embed', 'svg', 'math', 'template', 'noscript', 'form', 'frame', 'frameset',
    'input', 'button', 'textarea', 'select', 'video', 'audio', 'canvas', 'head', 'title', 'meta', 'link', 'base'];

/** Bezpečné HTML z editora. $inline = len riadkové formátovanie (krátky úvod): odseky a zoznamy → <br>, bez médií. */
function rich_clean(string $html, bool $inline = false): string
{
    $html = trim(str_replace(["\r\n", "\r"], "\n", $html));
    if ($html === '') return '';
    // bez značiek = prostý text (textarea bez JS editora) — editor vždy posiela aspoň <p>
    if (!preg_match('~<[a-z/!]~i', $html)) $html = rich_from_text($html, $inline);
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
    $out = preg_replace('~(?:<p>(?:<br>)?</p>)+$~', '', trim($out));  // prázdne odseky na konci (Quill ich pridáva)
    if ($inline) $out = preg_replace('~^(?:<br>)+|(?:<br>)+$~', '', $out);
    return rich_text($out) === '' && !preg_match('~<(img|iframe|hr)\b~', $out) ? '' : $out;
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
        if ($tag === 'img' || $tag === 'iframe') {            // vložené médiá: len overené zdroje, nikdy v úvode
            $media = $inline ? null : ($tag === 'img' ? rich_img($child) : rich_video($child));
            $media ? $node->replaceChild($media, $child) : $node->removeChild($child);
            continue;
        }
        rich_walk($child, $inline);
        $map = ['b' => 'strong', 'i' => 'em', 'strike' => 's', 'del' => 's', 'h1' => 'h2', 'h4' => 'h3', 'h5' => 'h3', 'h6' => 'h3'];
        if (isset($map[$tag])) $child = rich_rename($child, $tag = $map[$tag]);

        $allowed = $inline ? RICH_INLINE : array_merge(RICH_BLOCK, RICH_INLINE);
        if (!in_array($tag, $allowed, true)) {
            $block = in_array($tag, ['p', 'h2', 'h3', 'li', 'div', 'blockquote'], true);
            if ($tag === 'hr') $node->removeChild($child);
            else rich_unwrap($child, $inline && $block);
            continue;
        }
        rich_attrs($child, $tag);
        if ($tag === 'hr') while ($child->firstChild) $child->removeChild($child->firstChild);
        if ($tag === 'span' && !$child->hasAttributes()) rich_unwrap($child, false);
    }
}

function rich_attrs(DOMElement $el, string $tag): void
{
    $keep = [];
    if ($tag === 'a') {
        $href = trim($el->getAttribute('href'));
        if (preg_match('~^www\.[a-z0-9-]+\.~i', $href)) $href = 'https://' . $href;   // „www.facebook.com/…“ bez https
        if (preg_match('~^tel:~i', $href)) $href = preg_match('~^tel:\+?[0-9][0-9 /-]{4,20}$~i', $href) ? 'tel:' . preg_replace('~[^0-9+]~', '', substr($href, 4)) : '';
        if (preg_match('~^(https?://|mailto:|tel:)~i', $href)) {
            $keep = str_starts_with(strtolower($href), 'tel:') ? ['href' => $href] : ['href' => $href, 'target' => '_blank', 'rel' => 'noopener'];
        }
    }
    if (in_array($tag, ['span', 'p', 'h2', 'h3', 'li', 'blockquote', 'strong', 'em', 'u', 's', 'a'], true)) {
        $isBlock = in_array($tag, ['p', 'h2', 'h3', 'li', 'blockquote'], true);
        $classes = [];
        foreach (preg_split('/\s+/', $el->getAttribute('class'), -1, PREG_SPLIT_NO_EMPTY) as $c) {
            if (preg_match('/^ql-font-(.+)$/', $c, $m) && in_array($m[1], RICH_FONTS, true)) $classes[] = $c;
            if (preg_match('/^ql-size-(.+)$/', $c, $m) && in_array($m[1], RICH_SIZES, true)) $classes[] = $c;
            if ($isBlock && preg_match('/^ql-align-(.+)$/', $c, $m) && in_array($m[1], RICH_ALIGN, true)) $classes[] = $c;
        }
        if ($classes) $keep['class'] = implode(' ', array_unique($classes));
        $css = $el->getAttribute('style');
        $style = [];
        if (preg_match('/(?:^|;)\s*color\s*:\s*([^;]+)/i', $css, $m) && ($hex = rich_color($m[1])) !== null) {
            $style[] = "color:$hex";
        }
        if (preg_match('/(?:^|;)\s*background(?:-color)?\s*:\s*([^;]+)/i', $css, $m)
            && ($hex = rich_color($m[1], RICH_BACKGROUNDS)) !== null) {
            $style[] = "background-color:$hex";
        }
        if ($style) $keep['style'] = implode(';', $style);
    }
    foreach (iterator_to_array($el->attributes) as $attr) $el->removeAttribute($attr->name);
    foreach ($keep as $k => $v) $el->setAttribute($k, $v);
    if ($tag === 'a' && !isset($keep['href'])) rich_unwrap($el, false);
}

/** #rgb, #rrggbb alebo rgb(r, g, b) → #rrggbb, ak je v palete; inak null. */
function rich_color(string $v, array $palette = RICH_COLORS): ?string
{
    $v = strtolower(trim($v));
    if (preg_match('/^#([0-9a-f])([0-9a-f])([0-9a-f])$/', $v, $m)) $v = "#$m[1]$m[1]$m[2]$m[2]$m[3]$m[3]";
    elseif (preg_match('/^rgb\(\s*(\d{1,3})\s*,\s*(\d{1,3})\s*,\s*(\d{1,3})\s*\)$/', $v, $m)) {
        $v = sprintf('#%02x%02x%02x', min(255, (int)$m[1]), min(255, (int)$m[2]), min(255, (int)$m[3]));
    }
    return in_array($v, $palette, true) ? $v : null;
}

/** Fotka v texte: len z uploads/inline (v admine má cestu ../uploads/…), vždy s alt a lazy loading. */
function rich_img(DOMElement $el): ?DOMElement
{
    $src = trim($el->getAttribute('src'));
    if (str_starts_with($src, SITE_URL . '/')) $src = substr($src, strlen(SITE_URL) + 1);   // skopírované z vlastného webu
    $src = preg_replace('~^(?:\.\./)+|^/~', '', $src);
    if (!preg_match(RICH_IMG_RE, $src)) return null;
    $img = $el->ownerDocument->createElement('img');
    $img->setAttribute('src', $src);
    $size = @getimagesize(uploads_dir() . '/' . substr($src, strlen('uploads/')));
    if ($size) {
        $img->setAttribute('width', (string)$size[0]);
        $img->setAttribute('height', (string)$size[1]);
    }
    $img->setAttribute('alt', mb_substr(trim($el->getAttribute('alt')), 0, 200));
    $img->setAttribute('loading', 'lazy');
    return $img;
}

/** YouTube ID z bežných tvarov odkazu (watch, youtu.be, shorts, embed, live); inak null. */
function rich_youtube_id(string $url): ?string
{
    $re = '~^https?://(?:www\.|m\.)?(?:youtube(?:-nocookie)?\.com/(?:watch\?(?:[^#]*&)?v=|embed/|shorts/|live/)|youtu\.be/)'
        . '([A-Za-z0-9_-]{11})(?:[?&#/].*)?$~D';
    return preg_match($re, trim($url), $m) ? $m[1] : null;
}

/** Video len z YouTube, vždy cez youtube-nocookie (bez sledovacích cookies, kým sa nespustí). */
function rich_video(DOMElement $el): ?DOMElement
{
    $id = rich_youtube_id($el->getAttribute('src'));
    if ($id === null) return null;
    $f = $el->ownerDocument->createElement('iframe');
    $attrs = ['class' => 'ql-video', 'src' => "https://www.youtube-nocookie.com/embed/$id", 'title' => 'Video',
        'loading' => 'lazy', 'allowfullscreen' => 'allowfullscreen', 'referrerpolicy' => 'strict-origin-when-cross-origin',
        'allow' => 'encrypted-media; picture-in-picture; fullscreen'];
    foreach ($attrs as $k => $v) $f->setAttribute($k, $v);
    return $f;
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

/** Fotky z uploads/inline použité v HTML (relatívne k uploads/, napr. inline/i_x.webp). */
function rich_images(string $html): array
{
    preg_match_all('~uploads/(inline/i_[a-z0-9]{6,32}\.(?:jpg|png|webp))~', $html, $m);
    return array_values(array_unique($m[1]));
}

/**
 * Zmaže fotky z textu, ktoré po zmene/zmazaní už nič nepoužíva (aj náhľad _t v ľubovoľnom formáte).
 * Volá sa po zápise dát; fotka použitá v inej aktualite či albume ostane.
 */
function rich_gc_images(array $candidates): void
{
    if (!$candidates) return;
    try {
        $all = '';
        foreach (store_read('posts', 'posts') as $p) $all .= ($p['excerpt'] ?? '') . ($p['body'] ?? '');
        foreach (store_read('albums', 'albums') as $a) $all .= $a['description'] ?? '';
        $used = rich_images($all);
    } catch (StoreException $e) {
        error_log($e->getMessage());
        return;                                                    // radšej nič nemazať
    }
    foreach (array_diff($candidates, $used) as $rel) {
        upload_delete($rel);
        foreach (['jpg', 'png', 'webp'] as $ext) upload_delete(preg_replace('~\.(jpg|png|webp)$~', "_t.$ext", $rel));
    }
}

/**
 * Fotky v uploads/inline, ktoré nepoužíva žiadna aktualita ani album (neuložený/zrušený text) → kôš.
 * Lehota = platnosť prihlásenia + 1 deň: rozpracovaný neuložený text v otvorenej karte prehliadača dlhšie neprežije.
 */
function rich_sweep_inline(?int $now = null): void
{
    $limit = ($now ?? time()) - SESSION_LIFETIME - 86400;
    $old = [];
    foreach (glob(uploads_dir() . '/inline/i_*.*') ?: [] as $f) {
        $name = basename($f);
        if (preg_match('~^i_[a-z0-9]{6,32}\.(jpg|png|webp)$~D', $name) && (int)@filemtime($f) < $limit) $old[] = "inline/$name";
    }
    rich_gc_images($old);
}

/** Čistý text z HTML (dĺžka, meta description, kontrola prázdnoty). */
function rich_text(string $html): string
{
    $html = preg_replace('~<br\s*/?>|</(p|h[1-6]|li|div|blockquote)>~i', "\n", $html);
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
