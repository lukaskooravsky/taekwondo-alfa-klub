<?php
declare(strict_types=1);

function album_clean(array $in): array
{
    $desc = trim(input_str($in['description'] ?? ''));
    // popis z editora je HTML (rich=1); bez JS príde prostý text
    $desc = input_str($in['rich'] ?? '') === '1' ? rich_from_form($desc) : rich_clean(rich_from_text($desc));
    $f = ['title' => trim(input_str($in['title'] ?? '')), 'date' => trim(input_str($in['date'] ?? '')), 'description' => $desc];
    $err = [];
    if ($f['title'] === '') $err['title'] = 'Doplň názov albumu.';
    elseif (str_len($f['title']) > 120) $err['title'] = 'Názov môže mať najviac 120 znakov.';
    if (!valid_date($f['date'])) $err['date'] = 'Zadaj platný dátum.';
    if (str_len(rich_text($desc)) > 5000 || strlen($desc) > 50000) $err['description'] = 'Popis je príliš dlhý (max 5 000 znakov).';
    if ($err) throw new ValidationException($err);
    return $f;
}

function albums_all(): array
{
    $albums = store_read('albums', 'albums');
    usort($albums, fn(array $a, array $b): int => [$b['date'], $b['created']] <=> [$a['date'], $a['created']]);
    return $albums;
}

/** Albumy na verejný web — len tie, ktoré majú aspoň jednu fotku. */
function albums_public(): array
{
    return array_values(array_filter(albums_all(), fn(array $a): bool => $a['photos'] !== []));
}

function album_get(string $id): ?array
{
    if (!valid_id($id, 'a')) return null;
    foreach (store_read('albums', 'albums') as $a) {
        if ($a['id'] === $id) return $a;
    }
    return null;
}

function album_create(array $in): array
{
    $now = now_iso();
    $album = ['id' => new_id('a')] + album_clean($in) + ['cover' => null, 'photos' => [], 'created' => $now, 'updated' => $now];
    store_update('albums', 'albums', function (array &$albums) use ($album): void { $albums[] = $album; });
    return $album;
}

/** Spoločná úprava jedného albumu; $fn(array &$album) vráti výsledok. Ak album neexistuje, vráti $missing. */
function album_modify(string $id, callable $fn, mixed $missing = null): mixed
{
    if (!valid_id($id, 'a')) return $missing;
    return store_update('albums', 'albums', function (array &$albums) use ($id, $fn, $missing): mixed {
        foreach ($albums as $i => $a) {
            if ($a['id'] !== $id) continue;
            $result = $fn($a);
            $a['updated'] = now_iso();
            $albums[$i] = $a;
            return $result;
        }
        return $missing;
    });
}

function album_update(string $id, array $in): ?array
{
    $f = album_clean($in);
    $oldDesc = '';
    $album = album_modify($id, function (array &$a) use ($f, &$oldDesc): array {
        $oldDesc = $a['description'] ?? '';
        $a = array_merge($a, $f);
        return $a;
    });
    if ($album !== null) rich_gc_images(rich_images($oldDesc));   // fotky vymazané z popisu
    return $album;
}

function album_delete(string $id): bool
{
    if (!valid_id($id, 'a')) return false;
    store_backup_before_delete('albums');
    $desc = '';
    $found = store_update('albums', 'albums', function (array &$albums) use ($id, &$desc): bool {
        foreach ($albums as $i => $a) {
            if ($a['id'] === $id) {
                $desc = $a['description'] ?? '';
                array_splice($albums, $i, 1);
                return true;
            }
        }
        return false;
    });
    if ($found) {
        upload_delete_dir($id);
        rich_gc_images(rich_images($desc));
    }
    return $found;
}

function album_add_photo(string $albumId, string $photoId, array $stored): ?array
{
    $photo = ['id' => $photoId, 'full' => $stored['full'], 'thumb' => $stored['thumb'], 'w' => $stored['w'], 'h' => $stored['h'], 'added' => now_iso()];
    return album_modify($albumId, function (array &$a) use ($photo): array {
        $a['photos'][] = $photo;
        return $photo;
    });
}

function album_delete_photo(string $albumId, string $photoId): bool
{
    store_backup_before_delete('albums');
    $removed = album_modify($albumId, function (array &$a) use ($photoId): ?array {
        foreach ($a['photos'] as $i => $p) {
            if ($p['id'] === $photoId) {
                array_splice($a['photos'], $i, 1);
                if ($a['cover'] === $photoId) $a['cover'] = null;
                return $p;
            }
        }
        return null;
    });
    if ($removed === null) return false;
    upload_delete($removed['full']);
    upload_delete($removed['thumb']);
    return true;
}

function album_set_cover(string $albumId, string $photoId): bool
{
    return album_modify($albumId, function (array &$a) use ($photoId): bool {
        if (!in_array($photoId, array_column($a['photos'], 'id'), true)) return false;
        $a['cover'] = $photoId;
        return true;
    }, false);
}

/** Titulná fotka albumu: nastavená, inak prvá; null pre prázdny album. */
function album_cover(array $album): ?array
{
    foreach ($album['photos'] as $p) {
        if ($p['id'] === $album['cover']) return $p;
    }
    return $album['photos'][0] ?? null;
}
