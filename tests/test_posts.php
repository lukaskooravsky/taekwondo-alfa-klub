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

test('formátovaná aktualita (rich=1): čistenie, limity podľa textu, zobrazenie', function () {
    $in = sample_post(['rich' => '1', 'excerpt' => '<p><strong>Úvod</strong> 🥇</p>',
        'body' => '<h2>Nadpis</h2><p onclick="x">Text <a href="javascript:x">zlý</a></p>']);
    $c = post_clean($in);
    eq(true, $c['rich']);
    eq('<strong>Úvod</strong> 🥇', $c['excerpt']);
    eq('<h2>Nadpis</h2><p>Text zlý</p>', $c['body']);
    // 300 znakov textu prejde aj s dlhým HTML okolo
    post_clean(sample_post(['rich' => '1', 'excerpt' => '<p><span class="ql-size-large">' . str_repeat('a', 300) . '</span></p>']));
    $e = throws(fn() => post_clean(sample_post(['rich' => '1', 'excerpt' => '<p><br></p>'])), ValidationException::class);
    eq('Doplň krátky úvod.', $e->errors['excerpt']);
    $p = post_create($in, null);
    eq('<strong>Úvod</strong> 🥇', post_excerpt_html($p));
    eq('Úvod 🥇', post_excerpt_text($p));
    eq('<div class="rich"><h2>Nadpis</h2><p>Text zlý</p></div>', post_body_html($p));
    ok(str_contains(render_post_card($p), '<p><strong>Úvod</strong> 🥇</p>'));
});

test('stará aktualita bez rich ostáva prostý text', function () {
    $p = post_create(sample_post(['excerpt' => 'Pozor <b>', 'body' => "a\n\nb"]), null);
    eq(false, $p['rich']);
    eq('Pozor &lt;b&gt;', post_excerpt_html($p));
    eq("<p>a</p>\n<p>b</p>", post_body_html($p));
    unset($p['rich']); // dáta spred editora kľúč vôbec nemajú
    eq('Pozor &lt;b&gt;', post_excerpt_html($p));
});

test('titulná fotka aktuality si pamätá rozmery a karta ich vypíše', function () {
    $p = post_create(sample_post(), ['full' => 'posts/p_abcdef.jpg', 'thumb' => 'posts/p_abcdef_t.jpg', 'w' => 1600, 'h' => 900]);
    eq(['full' => 'posts/p_abcdef.jpg', 'thumb' => 'posts/p_abcdef_t.jpg', 'w' => 1600, 'h' => 900], $p['image']);
    ok(str_contains(render_post_card($p), 'width="1600" height="900"'));
    ok(str_contains(render_post_article($p), 'width="1600" height="900"'));
});

test('koncept aktuality: nie je verejný, stav sa mení len tlačidlom, Enter stav ponechá', function () {
    $d = post_create(sample_post(['title' => 'Koncept']), null, 'draft');
    $p = post_create(sample_post(['title' => 'Verejná']), null);
    ok(is_draft($d) && !is_draft($p));
    eq(['Verejná'], array_column(posts_public(), 'title'));
    eq(2, count(posts_all()), 'admin vidí oba');
    eq('draft', post_update($d['id'], sample_post(['title' => 'Koncept 2']), null, status_from_request([]))['status'], 'Uložiť = ponechať');
    eq('published', post_update($d['id'], sample_post(), null, status_from_request(['publish' => '1']))['status']);
    eq('draft', post_update($d['id'], sample_post(), null, status_from_request(['draft' => '1']))['status']);
    unset($p['status']);
    ok(!is_draft($p), 'staré dáta bez stavu = zverejnené');
});

test('zverejnenie konceptu: nezmenený starý dátum → dnešný, zmenený ručne ostane', function () {
    $d = post_create(sample_post(['date' => '2026-01-05']), null, 'draft');
    store_update('posts', 'posts', function (array &$posts): void { $posts[0]['created'] = '2026-01-05T10:00:00+01:00'; });
    eq(date('Y-m-d'), post_update($d['id'], sample_post(['date' => '2026-01-05']), null, 'published')['date'], 'dnešný');
    $e = post_create(sample_post(['date' => '2026-01-05']), null, 'draft');
    eq('2025-12-24', post_update($e['id'], sample_post(['date' => '2025-12-24']), null, 'published')['date'], 'ručne zadaný ostane');
});
