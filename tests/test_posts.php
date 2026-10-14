<?php
declare(strict_types=1);

function sample_post(array $over = []): array
{
    return $over + ['title' => 'Nová sezóna', 'date' => '2026-09-12', 'excerpt' => 'Krátky úvod.', 'body' => "Odsek 1\n\nOdsek 2"];
}
function sample_image(string $base): array
{
    return upload_store_pair(fake_file(fixture_png(tmp("$base.png"))), fake_file(fixture_png(tmp("{$base}t.png"))), 'posts', $base, false);
}

test('post_clean orezáva a validuje', function () {
    $c = post_clean(sample_post(['title' => '  Ahoj  ', 'body' => "a\r\nb"]));
    eq('Ahoj', $c['title']);
    eq("a\nb", $c['body']);
    $e = throws(fn() => post_clean(['title' => '', 'date' => '2026-02-30', 'excerpt' => '']), ValidationException::class);
    eq(['title', 'date', 'excerpt'], array_keys($e->errors));
    $e = throws(fn() => post_clean(sample_post(['title' => str_repeat('š', 151)])), ValidationException::class);
    eq('Nadpis môže mať najviac 150 znakov.', $e->errors['title']);
    post_clean(sample_post(['title' => str_repeat('š', 150), 'excerpt' => str_repeat('a', 300)])); // hranice prejdú
});

test('post_create + post_get', function () {
    $p = post_create(sample_post(), null);
    ok(valid_id($p['id'], 'p'));
    eq(null, $p['image']);
    eq($p, post_get($p['id']));
    eq(null, post_get('p_neexistuje'));
    eq(null, post_get('../../x'));
});

test('posts_all radí podľa dátumu zostupne', function () {
    post_create(sample_post(['title' => 'Stará', 'date' => '2026-01-01']), null);
    post_create(sample_post(['title' => 'Nová', 'date' => '2026-09-01']), null);
    post_create(sample_post(['title' => 'Stredná', 'date' => '2026-05-01']), null);
    eq(['Nová', 'Stredná', 'Stará'], array_column(posts_all(), 'title'));
});

test('post_update: null fotku ponechá, false ju odstráni aj so súbormi', function () {
    $img = sample_image('p_img001');
    $p = post_create(sample_post(), $img);
    $u = post_update($p['id'], sample_post(['title' => 'Zmenené']), null);
    eq('Zmenené', $u['title']);
    eq($img['full'], $u['image']['full']);
    ok(is_file(upload_path($img['full'])));
    $u = post_update($p['id'], sample_post(), false);
    eq(null, $u['image']);
    ok(!is_file(upload_path($img['full'])) && !is_file(upload_path($img['thumb'])));
});

test('post_update: nová fotka nahradí starú a stará sa zmaže', function () {
    $old = sample_image('p_img001');
    $new = sample_image('p_img002');
    $p = post_create(sample_post(), $old);
    $u = post_update($p['id'], sample_post(), $new);
    eq($new['full'], $u['image']['full']);
    ok(!is_file(upload_path($old['full'])));
    ok(is_file(upload_path($new['full'])));
    eq(null, post_update('p_neexistuje', sample_post(), null));
});

test('post_delete zmaže záznam aj fotku', function () {
    $img = sample_image('p_img001');
    $p = post_create(sample_post(), $img);
    ok(post_delete($p['id']));
    eq(null, post_get($p['id']));
    ok(!is_file(upload_path($img['full'])));
    ok(!post_delete($p['id']));
    ok(!post_delete('nezmysel'));
});

test('poškodený posts.json: posts_all hodí StoreException', function () {
    ensure_storage();
    file_put_contents(store_path('posts'), 'xx');
    throws(fn() => posts_all(), StoreException::class);
});
