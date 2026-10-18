<?php
declare(strict_types=1);

/*
 * SEO a AI: canonical, robots, náhľady pre sociálne siete (Open Graph), štruktúrované dáta (schema.org JSON-LD),
 * sitemap.xml, robots.txt a llms.txt. Všetko sa riadi konštantami v inc/site.php.
 */

const SEO_DEFAULT_IMAGE = 'assets/logo-full.jpg';
/** Roboty AI vyhľadávačov a asistentov — pri SITE_INDEX smú web čítať (aby klub odporúčali). */
const SEO_AI_BOTS = ['GPTBot', 'OAI-SearchBot', 'ChatGPT-User', 'ClaudeBot', 'Claude-SearchBot', 'Claude-User',
    'PerplexityBot', 'Perplexity-User', 'Google-Extended', 'Applebot-Extended', 'CCBot', 'meta-externalagent'];

/** Absolútna adresa z relatívnej cesty webu ('' = domov). */
function site_abs(string $path = ''): string
{
    $path = ltrim($path, '/');
    return SITE_URL . '/' . ($path === 'index.php' ? '' : $path);
}

/** Verejné stránky v poradí menu: [cesta, názov, popis] — pre sitemap a llms.txt. */
function seo_pages(): array
{
    return [
        ['', 'Domov', 'Úvod, tréningy, aktuality a fotky.'],
        ['co-je-taekwondo.php', 'Čo je taekwondo', 'Pôvod, filozofia a disciplíny ITF Taekwon-Do.'],
        ['pasy.php', 'Pásy', 'Systém technických stupňov gup a majstrovských dan.'],
        ['aktuality.php', 'Aktuality', 'Novinky z klubu: nábory, skúšky, súťaže.'],
        ['galeria.php', 'Galéria', 'Fotky z tréningov, skúšok a súťaží.'],
        ['o-nas.php', 'O nás', 'Tréner, klub a rozvrh tréningov.'],
        ['kontakty.php', 'Kontakt', 'Kontakt a prihláška na skúšobnú hodinu.'],
        ['ochrana-osobnych-udajov.php', 'Ochrana osobných údajov', 'Ako klub spracúva osobné údaje.'],
    ];
}

/** Klub ako schema.org SportsClub (zobrazí sa v Google a AI odpovediach: adresa, tréningy, kontakt). */
function club_jsonld(): array
{
    return [
        '@type' => 'SportsClub',
        '@id' => site_abs() . '#klub',
        'name' => CLUB['name'],
        'url' => site_abs(),
        'logo' => site_abs('assets/logo-mark.jpg'),
        'image' => site_abs(SEO_DEFAULT_IMAGE),
        'description' => CLUB['description'],
        'sport' => CLUB['sport'],
        'telephone' => CLUB['phone'],
        'email' => CLUB['email'],
        'address' => ['@type' => 'PostalAddress', 'streetAddress' => CLUB['street'], 'addressLocality' => CLUB['city'],
            'postalCode' => CLUB['postal_code'], 'addressRegion' => CLUB['district'], 'addressCountry' => CLUB['country']],
        'openingHoursSpecification' => array_map(fn(array $h): array => ['@type' => 'OpeningHoursSpecification',
            'dayOfWeek' => 'https://schema.org/' . $h[0], 'opens' => $h[1], 'closes' => $h[2]], CLUB['hours']),
        'employee' => ['@type' => 'Person', 'name' => CLUB['coach'], 'jobTitle' => 'Tréner — ' . CLUB['coach_title']],
        'areaServed' => CLUB['city'],
    ];
}

/** <script type="application/ld+json"> — JSON bezpečne vložený do HTML (bez </script>). */
function jsonld_tag(array $graph): string
{
    $json = json_encode(['@context' => 'https://schema.org', '@graph' => $graph],
        JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP);
    return '<script type="application/ld+json">' . $json . '</script>';
}

/**
 * Meta značky do <head>. $page: title, description, path (canonical, relatívne), image (relatívne), type (website|article),
 * jsonld (ďalšie objekty), noindex (bool).
 */
function seo_head(array $page, string $fullTitle): string
{
    $path = $page['path'] ?? basename((string)($_SERVER['SCRIPT_NAME'] ?? 'index.php'));
    $url = site_abs($path);
    $desc = trim(preg_replace('/\s+/u', ' ', (string)($page['description'] ?? '')));
    if (mb_strlen($desc) > 300) $desc = mb_substr($desc, 0, 297) . '…';
    $img = site_abs($page['image'] ?? SEO_DEFAULT_IMAGE);
    $index = SITE_INDEX && empty($page['noindex']);
    $tags = [
        '<meta name="description" content="' . e($desc) . '">',
        '<meta name="robots" content="' . ($index ? 'index, follow, max-image-preview:large' : 'noindex, nofollow') . '">',
        '<link rel="canonical" href="' . e($url) . '">',
        '<meta property="og:site_name" content="' . e(SITE_NAME) . '">',
        '<meta property="og:locale" content="sk_SK">',
        '<meta property="og:type" content="' . e($page['type'] ?? 'website') . '">',
        '<meta property="og:title" content="' . e($fullTitle) . '">',
        '<meta property="og:description" content="' . e($desc) . '">',
        '<meta property="og:url" content="' . e($url) . '">',
        '<meta property="og:image" content="' . e($img) . '">',
        '<meta name="twitter:card" content="summary_large_image">',
        '<link rel="icon" href="assets/logo-mark.jpg" type="image/jpeg">',
        '<link rel="apple-touch-icon" href="assets/logo-mark.jpg">',
        '<meta name="theme-color" content="#0a0a0c">',
        '<link rel="alternate" type="application/rss+xml" title="' . e(SITE_NAME . ' — aktuality') . '" href="' . e(site_abs('rss.php')) . '">',
        jsonld_tag(array_merge([club_jsonld()], $page['jsonld'] ?? [])),
    ];
    return implode("\n  ", $tags);
}

/** Článok aktuality pre Google/AI (NewsArticle). */
function post_jsonld(array $p): array
{
    $img = post_image($p);
    return [
        '@type' => 'NewsArticle',
        'headline' => mb_substr($p['title'], 0, 110),
        'description' => post_excerpt_text($p),
        'datePublished' => $p['date'],
        'dateModified' => substr((string)($p['updated'] ?? $p['date']), 0, 10),
        'image' => [site_abs($img['full'])],
        'url' => site_abs('aktualita.php?id=' . rawurlencode($p['id'])),
        'author' => ['@id' => site_abs() . '#klub'],
        'publisher' => ['@id' => site_abs() . '#klub'],
        'inLanguage' => 'sk',
    ];
}
