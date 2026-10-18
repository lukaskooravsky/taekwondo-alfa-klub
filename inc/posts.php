<?php
declare(strict_types=1);

/** Očistí a overí vstup formulára aktuality. */
function post_clean(array $in): array
{
    // rich=1 posiela editor v admine (HTML); bez neho (vypnutý JS, staré dáta) ide o prostý text
    $rich = input_str($in['rich'] ?? '') === '1';
    $excerpt = trim(input_str($in['excerpt'] ?? ''));
    $body = trim(str_replace(["\r\n", "\r"], "\n", input_str($in['body'] ?? '')));
    $f = [
        'title' => trim(input_str($in['title'] ?? '')),
        'date' => trim(input_str($in['date'] ?? '')),
        'excerpt' => $rich ? rich_clean($excerpt, true) : $excerpt,
        'body' => $rich ? rich_clean($body) : $body,
        'rich' => $rich,
    ];
    $excerptText = $rich ? rich_text($f['excerpt']) : $f['excerpt'];
    $bodyText = $rich ? rich_text($f['body']) : $f['body'];
    $err = [];
    if ($f['title'] === '') $err['title'] = 'Doplň nadpis.';
    elseif (str_len($f['title']) > 150) $err['title'] = 'Nadpis môže mať najviac 150 znakov.';
    if (!valid_date($f['date'])) $err['date'] = 'Zadaj platný dátum.';
    if ($excerptText === '') $err['excerpt'] = 'Doplň krátky úvod.';
    elseif (str_len($excerptText) > 300) $err['excerpt'] = 'Krátky úvod môže mať najviac 300 znakov.';
    if (str_len($bodyText) > 20000 || strlen($f['body']) > 200000) $err['body'] = 'Text je príliš dlhý (max 20 000 znakov).';
    if ($err) throw new ValidationException($err);
    return $f;
}

function posts_all(): array
{
    $posts = store_read('posts', 'posts');
    usort($posts, fn(array $a, array $b): int => [$b['date'], $b['created']] <=> [$a['date'], $a['created']]);
    return $posts;
}

function post_get(string $id): ?array
{
    if (!valid_id($id, 'p')) return null;
    foreach (store_read('posts', 'posts') as $p) {
        if ($p['id'] === $id) return $p;
    }
    return null;
}

function post_create(array $in, ?array $image): array
{
    $now = now_iso();
    $post = ['id' => new_id('p')] + post_clean($in) + [
        'image' => $image ? ['full' => $image['full'], 'thumb' => $image['thumb']] : null,
        'created' => $now,
        'updated' => $now,
    ];
    store_update('posts', 'posts', function (array &$posts) use ($post): void { $posts[] = $post; });
    return $post;
}

/** $image: null = ponechať, false = odstrániť, array = nahradiť. Staré súbory sa mažú až po zápise dát. */
function post_update(string $id, array $in, array|false|null $image): ?array
{
    $f = post_clean($in);
    $old = null;
    $oldHtml = '';
    $post = store_update('posts', 'posts', function (array &$posts) use ($id, $f, $image, &$old, &$oldHtml): ?array {
        foreach ($posts as $i => $p) {
            if ($p['id'] !== $id) continue;
            $old = $p['image'];
            $oldHtml = $p['excerpt'] . $p['body'];
            $p = array_merge($p, $f, ['updated' => now_iso()]);
            if ($image === false) $p['image'] = null;
            elseif (is_array($image)) $p['image'] = ['full' => $image['full'], 'thumb' => $image['thumb']];
            $posts[$i] = $p;
            return $p;
        }
        return null;
    });
    if ($post !== null && $image !== null) post_delete_files($old);
    if ($post !== null) rich_gc_images(rich_images($oldHtml));   // fotky vymazané z textu
    return $post;
}

function post_delete(string $id): bool
{
    if (!valid_id($id, 'p')) return false;
    $removed = store_update('posts', 'posts', function (array &$posts) use ($id): ?array {
        foreach ($posts as $i => $p) {
            if ($p['id'] === $id) {
                array_splice($posts, $i, 1);
                return $p;
            }
        }
        return null;
    });
    if ($removed === null) return false;
    post_delete_files($removed['image']);
    rich_gc_images(rich_images($removed['excerpt'] . $removed['body']));
    return true;
}

function post_delete_files(?array $image): void
{
    if (!$image) return;
    upload_delete($image['full']);
    upload_delete($image['thumb']);
}

/** Fotka z formulára aktuality: najprv dvojica zmenšená v prehliadači, inak originál (zmenší server). */
function post_image_from_request(): ?array
{
    $base = new_id('p');
    return upload_from_request('image_full', 'image_thumb', 'posts', $base)
        ?? upload_from_request('image', null, 'posts', $base);
}
