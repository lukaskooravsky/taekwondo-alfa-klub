<?php
declare(strict_types=1);
// llms.txt (cez .htaccess) — stručný prehľad klubu pre AI asistentov (ChatGPT, Claude, Perplexity…), https://llmstxt.org
require __DIR__ . '/inc/bootstrap.php';
header('Content-Type: text/plain; charset=utf-8');

$c = CLUB;
echo '# ', SITE_NAME, "\n\n> ", $c['description'], "\n\n";
echo "## Základné informácie\n\n";
echo '- Šport: ', $c['sport'], "\n";
echo '- Tréner: ', $c['coach'], ' (', $c['coach_title'], ")\n";
echo '- Miesto tréningov: ', $c['place'], ', ', $c['city'], ' — ', $c['district'], "\n";
echo '- Tréningy: utorok a štvrtok — ', implode(', ', $c['groups']), "\n";
echo '- Telefón: ', $c['phone_display'], "\n- E-mail: ", $c['email'], "\n";
echo '- Skúšobná hodina: ', site_abs('kontakty.php#prihlaska'), "\n\n";
echo "## Stránky\n\n";
foreach (seo_pages() as [$path, $name, $desc]) echo "- [$name](", site_abs($path), "): $desc\n";
try {
    $posts = array_slice(posts_public(), 0, 10);
    if ($posts) {
        echo "\n## Najnovšie aktuality\n\n";
        foreach ($posts as $p) {
            echo '- [', $p['title'], '](', site_abs('aktualita.php?id=' . rawurlencode($p['id'])), '): ', format_date_sk($p['date']), ' — ',
                str_replace("\n", ' ', post_excerpt_text($p)), "\n";
        }
    }
} catch (StoreException $e) {
    error_log($e->getMessage());
}
