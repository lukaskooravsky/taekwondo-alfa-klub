<?php
declare(strict_types=1);

// Nastavenia webu v kóde — jedno miesto pre doménu, indexovanie a fakty o klube (SEO, štruktúrované dáta, llms.txt).

const SITE_DEMO = false; // true = zobrazí pásik „Náhľad webu (demo)…“

/**
 * Verejná adresa webu bez lomky na konci. Prázdne = web si ju zistí sám z adresy, na ktorej beží
 * (pri presune na novú doménu netreba nič meniť). Vyplň (napr. 'https://www.klub.sk') len ak treba jednu pevnú adresu.
 */
const SITE_URL_FIXED = '';

/** Dočasné ukážky — tu sa web neindexuje (aby Google neskôr nepovažoval ostrú doménu za duplikát). */
const SITE_DEMO_HOSTS = ['alfa.oravsky.sk'];

/** Host z požiadavky, len ak vyzerá ako doména/IP (Host hlavičku posiela prehliadač — nevkladať ju naslepo). */
function site_request_host(): string
{
    $host = strtolower((string)($_SERVER['HTTP_HOST'] ?? ''));
    return preg_match('/^[a-z0-9.-]+(:\d+)?$|^\[[0-9a-f:]+\](:\d+)?$/D', $host) === 1 ? $host : '';
}

function site_host_is_local(string $host): bool
{
    return $host === '' || preg_match('/^(localhost|127\.|\[::1\]|10\.|192\.168\.|[^.]+(:\d+)?$)/', $host) === 1;
}

define('SITE_URL', SITE_URL_FIXED !== '' ? SITE_URL_FIXED : (static function (): string {
    $host = site_request_host();
    if ($host === '') return 'http://localhost';
    return (site_host_is_local($host) ? 'http://' : 'https://') . $host;
})());

/**
 * true = vyhľadávače a AI smú web indexovať (robots.txt Allow + sitemap, bez noindex).
 * Automaticky: áno na ostrej doméne, nie na dočasnej ukážke (SITE_DEMO_HOSTS) a lokálne.
 */
define('SITE_INDEX', (static function (): bool {
    $host = (string)parse_url(SITE_URL, PHP_URL_HOST);
    return !site_host_is_local($host) && !in_array($host, SITE_DEMO_HOSTS, true);
})());

const SITE_NAME = 'ALFA Taekwondo Club Bratislava';

/** Fakty o klube — zobrazujú sa vyhľadávačom a AI asistentom. Pri zmene uprav aj texty stránok. */
const CLUB = [
    'name' => SITE_NAME,
    'sport' => 'ITF Taekwon-Do',
    'description' => 'Klub ITF Taekwon-Do v Bratislave (Petržalka) pod vedením Ľubomíra Závodného, IV. Dan, Majstra Európy. '
        . 'Tréningy pre deti, mládež aj dospelých, začiatočníkov aj pokročilých.',
    'coach' => 'Ľubomír Závodný',
    'coach_title' => 'IV. Dan čierny pás, Majster Európy',
    'phone' => '+421911818815',
    'phone_display' => '0911 818 815',
    'email' => 'lub.zavodny1@gmail.com',
    'street' => 'Holíčska 50',
    'place' => 'ZŠ Holíčska 50',
    'district' => 'Petržalka',
    'postal_code' => '85105',
    'city' => 'Bratislava',
    'country' => 'SK',
    // tréningy (deň ISO: Tuesday…); skupiny sú v rozvrhu na stránke O nás
    'hours' => [['Tuesday', '16:30', '19:30'], ['Thursday', '16:30', '19:30']],
    'groups' => ['začiatočníci 16:30–17:30', 'stredne pokročilí 17:30–18:30', 'pokročilí a dospelí 18:30–19:30'],
];
