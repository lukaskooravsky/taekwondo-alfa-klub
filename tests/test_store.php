<?php
declare(strict_types=1);

test('new_id a valid_id', function () {
    $id = new_id('p');
    ok(valid_id($id, 'p'), $id);
    ok(!valid_id($id, 'a'));
    ok(!valid_id('p_../../x', 'p'));
    ok(!valid_id(null, 'p'));
    ok(!valid_id("p_abcdef\n", 'p'), 'koncový \n neprejde (modifikátor D)');
    ok($id !== new_id('p'));
});

test('store_read: chýbajúci súbor = prázdny zoznam', function () {
    eq([], store_read('posts', 'posts'));
});

test('store_update zapíše a vráti výsledok funkcie', function () {
    $r = store_update('posts', 'posts', function (array &$items) { $items[] = ['id' => 'p_aaaaaa']; return 'hotovo'; });
    eq('hotovo', $r);
    eq([['id' => 'p_aaaaaa']], store_read('posts', 'posts'));
    ok(!glob(data_dir() . '/posts.json.tmp-*'), 'nezostal dočasný súbor');
});

test('store_update: výnimka vo funkcii nič nezapíše', function () {
    store_update('posts', 'posts', function (array &$items) { $items[] = ['id' => 'p_aaaaaa']; });
    throws(function () {
        store_update('posts', 'posts', function (array &$items) { $items = []; throw new RuntimeException('stop'); });
    }, RuntimeException::class);
    eq(1, count(store_read('posts', 'posts')));
});

test('poškodený JSON: read hodí StoreException a update ho neprepíše', function () {
    ensure_storage();
    file_put_contents(store_path('posts'), '{nie je json');
    throws(fn() => store_read('posts', 'posts'), StoreException::class);
    throws(fn() => store_update('posts', 'posts', function (array &$i) { $i = []; }), StoreException::class);
    eq('{nie je json', file_get_contents(store_path('posts')));
});

test('zálohy: drží sa najviac BACKUP_KEEP verzií', function () {
    for ($i = 0; $i < BACKUP_KEEP + 5; $i++) {
        store_update('posts', 'posts', function (array &$items) use ($i) { $items[] = ['n' => $i]; });
    }
    eq(BACKUP_KEEP, count(glob(data_dir() . '/backup/posts-*.json')));
});

test('volatile súbor: bez záloh, poškodený = prázdny a prepíše sa', function () {
    store_update('login-attempts', 'attempts', function (array &$a) { $a['x'] = 1; }, true);
    store_update('login-attempts', 'attempts', function (array &$a) { $a['y'] = 2; }, true);
    eq([], glob(data_dir() . '/backup/login-attempts-*.json') ?: []);
    file_put_contents(store_path('login-attempts'), '{rozbité');
    eq([], store_read('login-attempts', 'attempts', true));
    store_update('login-attempts', 'attempts', function (array &$a) { $a['z'] = 3; }, true);
    eq(['z' => 3], store_read('login-attempts', 'attempts', true));
});

test('ValidationException nesie chyby podľa polí', function () {
    $e = new ValidationException(['title' => 'Doplň nadpis.']);
    eq(['title' => 'Doplň nadpis.'], $e->errors);
    eq('Doplň nadpis.', $e->getMessage());
});

test('záloha pred zmazaním prežije rotáciu bežných záloh a drží sa 30 dní', function () {
    store_update('posts', 'posts', function (array &$p): void { $p[] = ['id' => 'p_aaaaaa']; });
    store_update('posts', 'posts', function (array &$p): void { $p[] = ['id' => 'p_bbbbbb']; });
    store_backup_before_delete('posts');
    for ($i = 0; $i < BACKUP_KEEP + 5; $i++) store_update('posts', 'posts', function (array &$p) use ($i): void { $p[0]['n'] = $i; });
    $pinned = glob(data_dir() . '/backup/posts-pred-zmazanim-*.json');
    eq(1, count($pinned), 'záloha pred zmazaním ostala');
    eq(BACKUP_KEEP, count(glob(data_dir() . '/backup/posts-[0-9]*.json')), 'bežné sa rotujú');
    touch($pinned[0], time() - (BACKUP_DELETE_DAYS + 1) * 86400);
    store_backup_before_delete('posts');
    eq(1, count(glob(data_dir() . '/backup/posts-pred-zmazanim-*.json')), 'stará po 30 dňoch preč, nová je');
});
