<?php
declare(strict_types=1);

const PLACEHOLDER_IMAGE = 'assets/logo-full.jpg';

/** URL fotky aktuality (relatívne ku koreňu webu); bez fotky logo klubu. */
function post_image(array $p): array
{
    if (!empty($p['image'])) {
        return ['full' => upload_url($p['image']['full']), 'thumb' => upload_url($p['image']['thumb'])];
    }
    return ['full' => PLACEHOLDER_IMAGE, 'thumb' => PLACEHOLDER_IMAGE];
}

/** Krátky úvod ako HTML (riadkové formátovanie z editora alebo escapovaný prostý text). */
function post_excerpt_html(array $p): string
{
    return !empty($p['rich']) ? rich_clean($p['excerpt'], true) : e($p['excerpt']);
}

function post_excerpt_text(array $p): string
{
    return !empty($p['rich']) ? rich_text($p['excerpt']) : $p['excerpt'];
}

function post_body_html(array $p): string
{
    return !empty($p['rich']) ? '<div class="rich">' . rich_clean($p['body']) . '</div>' : text_to_html($p['body']);
}

function render_post_card(array $p): string
{
    $img = post_image($p);
    $url = e('aktualita.php?id=' . rawurlencode($p['id']));
    return '<article class="post reveal">'
        . '<a href="' . $url . '"><img class="post-img" src="' . e($img['thumb']) . '" alt="" loading="lazy"></a>'
        . '<div class="post-body">'
        . '<span class="date">' . e(format_date_sk($p['date'])) . '</span>'
        . '<h3><a href="' . $url . '">' . e($p['title']) . '</a></h3>'
        . '<p>' . post_excerpt_html($p) . '</p>'
        . '<a class="more" href="' . $url . '">Čítať viac →</a>'
        . '</div></article>';
}

/** Detail aktuality (aktualita.php aj náhľad v admine). */
function render_post_article(array $p): string
{
    $img = post_image($p);
    return '<section class="page-hero"><div class="container">'
        . '<div class="breadcrumb"><a href="index.php">Domov</a> / <a href="aktuality.php">Aktuality</a></div>'
        . '<span class="eyebrow">' . e(format_date_sk($p['date'])) . '</span>'
        . '<h1 class="h1-post">' . e($p['title']) . '</h1>'
        . '</div></section>'
        . '<section><div class="container article">'
        . (!empty($p['image']) ? '<figure class="article-img"><img src="' . e($img['full']) . '" alt="' . e($p['title']) . '"></figure>' : '')
        . '<p class="article-lead">' . post_excerpt_html($p) . '</p>'
        . '<div class="prose">' . post_body_html($p) . '</div>'
        . '<p style="margin-top:2.4rem;"><a class="btn btn-ghost" href="aktuality.php">← Všetky aktuality</a></p>'
        . '</div></section>';
}

function render_gallery_figure(array $photo, string $alt): string
{
    return '<figure class="reveal"><img src="' . e(upload_url($photo['thumb'])) . '" data-full="' . e(upload_url($photo['full']))
        . '" alt="' . e($alt) . '" loading="lazy"></figure>';
}

function render_album_tile(array $a): string
{
    $cover = album_cover($a);
    $src = $cover ? upload_url($cover['thumb']) : PLACEHOLDER_IMAGE;
    return '<a class="album reveal" href="' . e('galeria.php?album=' . rawurlencode($a['id'])) . '">'
        . '<img src="' . e($src) . '" alt="" loading="lazy">'
        . '<div class="album-body">'
        . '<span class="album-meta">' . e(format_date_sk($a['date'])) . ' · ' . e(sk_plural(count($a['photos']), 'fotka', 'fotky', 'fotiek')) . '</span>'
        . '<h3>' . e($a['title']) . '</h3>'
        . '</div></a>';
}

/** 404 stránka v dizajne webu. */
function not_found(string $message, string $backUrl, string $backLabel): never
{
    http_response_code(404);
    $page = ['title' => 'Stránka sa nenašla', 'description' => ''];
    require ROOT . '/inc/layout-header.php';
    echo '<section class="page-hero"><div class="container">'
        . '<span class="eyebrow">Chyba 404</span><h1 class="h1-post">Stránka sa nenašla</h1>'
        . '<p>' . e($message) . '</p>'
        . '<p style="margin-top:2rem;"><a class="btn btn-ghost" href="' . e($backUrl) . '">' . e($backLabel) . '</a></p>'
        . '</div></section>';
    require ROOT . '/inc/layout-footer.php';
    exit;
}
