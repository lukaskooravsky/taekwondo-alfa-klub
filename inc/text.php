<?php
declare(strict_types=1);

function e(?string $s): string
{
    return htmlspecialchars((string)$s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/** Prostý text → HTML: prázdny riadok = nový odsek, riadok = <br>, http(s) URL = odkaz. */
function text_to_html(string $text): string
{
    $text = trim(str_replace(["\r\n", "\r"], "\n", $text));
    if ($text === '') return '';
    $out = [];
    foreach (preg_split('/\n[ \t]*\n+/', $text) as $para) {
        $html = e(trim($para));
        // URL hľadáme až v escapovanom texte; entity úvodzoviek a <> do URL nepatria
        $html = preg_replace_callback('~https?://(?:(?!&quot;|&#039;|&lt;|&gt;)[^\s<])+~u', function (array $m): string {
            $url = rtrim($m[0], '.,;:!?)');
            $tail = substr($m[0], strlen($url));
            return '<a href="' . $url . '" target="_blank" rel="noopener">' . $url . '</a>' . $tail;
        }, $html);
        $out[] = '<p>' . str_replace("\n", '<br>', $html) . '</p>'; // nl2br by nechal \n za <br>
    }
    return implode("\n", $out);
}

function valid_date(string $d): bool
{
    $dt = DateTimeImmutable::createFromFormat('!Y-m-d', $d);
    return $dt !== false && $dt->format('Y-m-d') === $d;
}

function format_date_sk(string $ymd): string
{
    return valid_date($ymd) ? DateTimeImmutable::createFromFormat('!Y-m-d', $ymd)->format('d. m. Y') : '';
}

/** Hodnota z $_GET/$_POST ako text: pole (?id[]=x) ani neplatné UTF-8 neprejdú (inak "Array" / JsonException). */
function input_str(mixed $v): string
{
    return is_string($v) && (!function_exists('mb_check_encoding') || mb_check_encoding($v, 'UTF-8')) ? $v : '';
}

function str_len(string $s): int
{
    return mb_strlen($s, 'UTF-8');
}

function sk_plural(int $n, string $one, string $few, string $many): string
{
    $word = $n === 1 ? $one : ($n >= 2 && $n <= 4 ? $few : $many);
    return "$n $word";
}

/** Koncept = uložené, ale ešte nezverejnené (vidí ho len prihlásený tréner). Bez kľúča = zverejnené (staršie dáta). */
function is_draft(array $item): bool
{
    return ($item['status'] ?? 'published') === 'draft';
}

/**
 * Stav z tlačidiel formulára: name="publish" → zverejniť, name="draft" → do konceptov, inak (Uložiť, Enter v políčku)
 * ponechať súčasný stav. null = ponechať.
 */
function status_from_request(array $in): ?string
{
    if (!empty($in['publish'])) return 'published';
    if (!empty($in['draft'])) return 'draft';
    return null;
}

/** Text na vyhľadávanie: malé písmená bez diakritiky („Súťaž“ → „sutaz“). */
function text_fold(string $s): string
{
    if (class_exists(Normalizer::class)) {
        $n = Normalizer::normalize($s, Normalizer::FORM_D);
        if ($n !== false) $s = preg_replace('/\p{Mn}+/u', '', $n);
    } else {
        $s = strtr($s, ['á' => 'a', 'ä' => 'a', 'č' => 'c', 'ď' => 'd', 'é' => 'e', 'ě' => 'e', 'í' => 'i', 'ĺ' => 'l', 'ľ' => 'l',
            'ň' => 'n', 'ó' => 'o', 'ô' => 'o', 'ö' => 'o', 'ŕ' => 'r', 'ř' => 'r', 'š' => 's', 'ť' => 't', 'ú' => 'u', 'ů' => 'u',
            'ü' => 'u', 'ý' => 'y', 'ž' => 'z', 'Á' => 'A', 'Ä' => 'A', 'Č' => 'C', 'Ď' => 'D', 'É' => 'E', 'Ě' => 'E', 'Í' => 'I',
            'Ĺ' => 'L', 'Ľ' => 'L', 'Ň' => 'N', 'Ó' => 'O', 'Ô' => 'O', 'Ö' => 'O', 'Ŕ' => 'R', 'Ř' => 'R', 'Š' => 'S', 'Ť' => 'T',
            'Ú' => 'U', 'Ů' => 'U', 'Ü' => 'U', 'Ý' => 'Y', 'Ž' => 'Z']);
    }
    return mb_strtolower(trim(preg_replace('/\s+/u', ' ', $s)), 'UTF-8');
}

/**
 * Filter zoznamu v admine (aktuality, albumy). $items: [['fold' => hľadaný text, 'year' => '2026', 'flag' => '1'|'0',
 * 'date' => 'Y-m-d', 'title' => …, 'row' => pôvodná položka], …]. Vráti vyfiltrované a zoradené položky.
 */
function admin_filter(array $items, string $q, string $year, string $flag, string $sort, string $status = ''): array
{
    $words = array_filter(explode(' ', text_fold($q)), fn($w) => $w !== '');
    $out = array_values(array_filter($items, function (array $it) use ($words, $year, $flag, $status): bool {
        foreach ($words as $w) if (!str_contains($it['fold'], $w)) return false;
        return ($year === '' || $it['year'] === $year) && ($flag === '' || $it['flag'] === $flag)
            && ($status === '' || ($it['status'] ?? 'published') === $status);
    }));
    if ($sort === 'old') usort($out, fn($a, $b) => $a['date'] <=> $b['date']);
    elseif ($sort === 'az') usort($out, fn($a, $b) => strcoll(text_fold($a['title']), text_fold($b['title'])));
    return $out;                                               // 'new' = poradie z posts_all()/albums_all()
}
