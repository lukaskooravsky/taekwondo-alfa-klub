<?php
declare(strict_types=1);

// Nastavenia webu v kóde — jedno miesto pre doménu, indexovanie a fakty o klube (SEO, štruktúrované dáta, llms.txt).

const SITE_DEMO = false; // true = zobrazí pásik „Náhľad webu (demo)…“

/** Verejná adresa webu bez lomky na konci. Pri presune na vlastnú doménu zmeň TU (sitemap, canonical, náhľady). */
const SITE_URL = 'https://alfa.oravsky.sk';

/**
 * true = vyhľadávače a AI smú web indexovať (robots.txt Allow + sitemap, bez noindex).
 * Na dočasnej ukážke (alfa.oravsky.sk) nechaj false — inak by Google neskôr považoval ostrú doménu za duplikát.
 */
const SITE_INDEX = false;

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
