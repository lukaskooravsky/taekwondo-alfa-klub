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
