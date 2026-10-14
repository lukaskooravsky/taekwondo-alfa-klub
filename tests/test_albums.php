<?php
declare(strict_types=1);

function add_sample_photo(string $albumId, string $photoId): array
{
    $stored = upload_store_pair(fake_file(fixture_png(tmp("$photoId.png"))), fake_file(fixture_png(tmp("{$photoId}t.png"))), "albums/$albumId", $photoId, false);
    return album_add_photo($albumId, $photoId, $stored);
}

test('album_clean validuje názov a dátum', function () {
    eq(['title' => 'Skúšky', 'date' => '2026-06-20'], album_clean(['title' => ' Skúšky ', 'date' => '2026-06-20']));
    $e = throws(fn() => album_clean(['title' => '', 'date' => 'x']), ValidationException::class);
    eq(['title' => 'Doplň názov albumu.', 'date' => 'Zadaj platný dátum.'], $e->errors);
    throws(fn() => album_clean(['title' => str_repeat('a', 121), 'date' => '2026-06-20']), ValidationException::class);
});

test('album_create, album_get, zoradenie a verejný zoznam', function () {
    $a = album_create(['title' => 'Starý', 'date' => '2025-01-01']);
    $b = album_create(['title' => 'Nový', 'date' => '2026-01-01']);
    ok(valid_id($a['id'], 'a'));
    eq([], $a['photos']);
    eq(null, $a['cover']);
    eq(['Nový', 'Starý'], array_column(albums_all(), 'title'));
    eq([], albums_public(), 'prázdne albumy nie sú verejné');
    add_sample_photo($a['id'], 'f_aaaaa1');
    eq(['Starý'], array_column(albums_public(), 'title'));
    eq('Nový', album_get($b['id'])['title']);
    eq(null, album_get('a_nieje00'));
});

test('album_add_photo do neexistujúceho albumu vráti null', function () {
    eq(null, album_add_photo('a_nieje00', 'f_aaaaa1', ['full' => 'x', 'thumb' => 'x', 'w' => 1, 'h' => 1]));
});

test('titulná fotka: predvolene prvá, dá sa nastaviť, po zmazaní sa vráti na prvú', function () {
    $a = album_create(['title' => 'A', 'date' => '2026-01-01']);
    add_sample_photo($a['id'], 'f_aaaaa1');
    $p2 = add_sample_photo($a['id'], 'f_aaaaa2');
    eq('f_aaaaa1', album_cover(album_get($a['id']))['id']);
    ok(album_set_cover($a['id'], $p2['id']));
    eq('f_aaaaa2', album_cover(album_get($a['id']))['id']);
    ok(!album_set_cover($a['id'], 'f_nieje00'));
    ok(album_delete_photo($a['id'], $p2['id']));
    $fresh = album_get($a['id']);
    eq(null, $fresh['cover']);
    eq('f_aaaaa1', album_cover($fresh)['id']);
    ok(!is_file(upload_path($p2['full'])), 'súbor fotky zmazaný');
});

test('zmazanie poslednej fotky: album ostane v admine, zmizne z verejného zoznamu', function () {
    $a = album_create(['title' => 'A', 'date' => '2026-01-01']);
    $p = add_sample_photo($a['id'], 'f_aaaaa1');
    album_delete_photo($a['id'], $p['id']);
    eq(1, count(albums_all()));
    eq([], albums_public());
    eq(null, album_cover(album_get($a['id'])));
});

test('album_update a album_delete (aj s priečinkom fotiek)', function () {
    $a = album_create(['title' => 'A', 'date' => '2026-01-01']);
    add_sample_photo($a['id'], 'f_aaaaa1');
    eq('B', album_update($a['id'], ['title' => 'B', 'date' => '2026-02-02'])['title']);
    eq(null, album_update('a_nieje00', ['title' => 'B', 'date' => '2026-02-02']));
    ok(album_delete($a['id']));
    eq(null, album_get($a['id']));
    ok(!is_dir(upload_path('albums/' . $a['id'])));
    ok(!album_delete($a['id']));
});
