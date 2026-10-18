<?php
declare(strict_types=1);

test('upload_check prijme PNG podľa obsahu, nie podľa mena', function () {
    $f = fake_file(fixture_png(tmp('x.bin')), 'fotka.jpg');
    eq('png', upload_check($f));
});

test('upload_check odmietne textový súbor aj PHP vydávané za obrázok', function () {
    file_put_contents(tmp('a.jpg'), 'toto nie je obrázok');
    $e = throws(fn() => upload_check(fake_file(tmp('a.jpg'), 'a.jpg')), UploadException::class);
    eq('Toto nie je fotka vo formáte JPG, PNG alebo WebP.', $e->getMessage());
    file_put_contents(tmp('shell.png'), "<?php echo 'hack'; ?>");
    throws(fn() => upload_check(fake_file(tmp('shell.png'), 'shell.png')), UploadException::class);
});

test('upload_check: chyby PHP uploadu a veľkosť', function () {
    $f = fake_file(fixture_png(tmp('x.png')));
    eq('Fotka je príliš veľká pre server (limit ' . ini_get('upload_max_filesize') . ').', throws(fn() => upload_check(['error' => UPLOAD_ERR_INI_SIZE] + $f), UploadException::class)->getMessage());
    eq('Fotka je príliš veľká (max 5 MB).', throws(fn() => upload_check(['error' => UPLOAD_ERR_FORM_SIZE] + $f), UploadException::class)->getMessage());
    eq('Fotka je príliš veľká (max 5 MB).', throws(fn() => upload_check(['size' => UPLOAD_MAX_BYTES + 1] + $f), UploadException::class)->getMessage());
    eq('Nevybral si žiadnu fotku.', throws(fn() => upload_check(['error' => UPLOAD_ERR_NO_FILE] + $f), UploadException::class)->getMessage());
    eq('Fotku sa nepodarilo nahrať, skús znova.', throws(fn() => upload_check(['error' => UPLOAD_ERR_PARTIAL] + $f), UploadException::class)->getMessage());
});

test('upload_store_pair s náhľadom z prehliadača uloží oba súbory', function () {
    $full = fake_file(fixture_png(tmp('f.png')));
    $thumb = fake_file(fixture_png(tmp('t.png')));
    $r = upload_store_pair($full, $thumb, 'posts', 'p_abcdef', false);
    eq(['full' => 'posts/p_abcdef.png', 'thumb' => 'posts/p_abcdef_t.png', 'w' => 1, 'h' => 1], $r);
    ok(is_file(upload_path('posts/p_abcdef.png')) && is_file(upload_path('posts/p_abcdef_t.png')));
    eq('uploads/posts/p_abcdef.png', upload_url($r['full']));
});

test('upload_store_pair bez náhľadu zmenší fotku cez GD', function () {
    $full = fake_file(fixture_big_png(tmp('big.png'), 2000, 1000));
    $r = upload_store_pair($full, null, 'albums/a_abcdef', 'f_abcdef', false);
    eq(1600, $r['w']);
    eq(800, $r['h']);
    $ext = function_exists('imagewebp') ? 'webp' : 'jpg';
    eq("albums/a_abcdef/f_abcdef.$ext", $r['full']);
    eq("albums/a_abcdef/f_abcdef_t.$ext", $r['thumb']);
    eq(480, getimagesize(upload_path($r['thumb']))[0]);
});

test('gd_check_pixels: nad 25 megapixelov odmietne; ini_bytes', function () {
    gd_check_pixels(5000, 5000); // presne 25 MP prejde
    $e = throws(fn() => gd_check_pixels(5000, 5001), UploadException::class);
    eq('Fotka má príliš vysoké rozlíšenie, zmenši ju prosím.', $e->getMessage());
    eq(512 * 1024 * 1024, ini_bytes('512M'));
    eq(1024 ** 3, ini_bytes('1G'));
    eq(128, ini_bytes('128'));
});

test('upload_store_pair odmietne neplatnú cestu', function () {
    $f = fake_file(fixture_png(tmp('f.png')));
    throws(fn() => upload_store_pair($f, null, '../etc', 'p_abcdef', false), InvalidArgumentException::class);
    throws(fn() => upload_store_pair($f, null, 'posts', '../p_abc', false), InvalidArgumentException::class);
    throws(fn() => upload_store_pair($f, null, "posts\n", 'p_abcdef', false), InvalidArgumentException::class);
});

test('upload_delete zmaže len súbory v uploads so správnym tvarom mena', function () {
    $r = upload_store_pair(fake_file(fixture_png(tmp('f.png'))), fake_file(fixture_png(tmp('t.png'))), 'posts', 'p_abcdef', false);
    file_put_contents(tmp('tajne.txt'), 'x');
    upload_delete('../../tajne.txt');
    ok(is_file(tmp('tajne.txt')), 'súbor mimo uploads ostal');
    upload_delete($r['full']);
    upload_delete($r['thumb']);
    upload_delete(null);
    ok(!is_file(upload_path($r['full'])) && !is_file(upload_path($r['thumb'])));
});

test('upload_delete_dir zmaže priečinok albumu', function () {
    upload_store_pair(fake_file(fixture_png(tmp('f.png'))), null, 'albums/a_abcdef', 'f_abcdef', false);
    upload_delete_dir('a_abcdef');
    ok(!is_dir(upload_path('albums/a_abcdef')));
    upload_delete_dir('../posts'); // neplatné ID — nič nespraví
    ok(is_dir(upload_path('posts')));
});

test('upload_field ignoruje chýbajúce a prázdne polia', function () {
    $_FILES = ['a' => ['error' => UPLOAD_ERR_NO_FILE], 'b' => fake_file(fixture_png(tmp('f.png')))];
    eq(null, upload_field('a'));
    eq(null, upload_field('chyba'));
    ok(is_array(upload_field('b')));
});

test('zmazaná fotka ide do koša a po 30 dňoch sa vymaže', function () {
    ensure_storage();
    eq(data_dir() . '/trash', upload_trash_dir(), 'kôš je v chránených dátach');
    $r = upload_store_pair(fake_file(fixture_png(tmp('a.png'))), fake_file(fixture_png(tmp('b.png'))), 'posts', 'p_trash1', false);
    upload_delete($r['full']);
    ok(!is_file(upload_path($r['full'])), 'z webu preč');
    $inTrash = glob(upload_trash_dir() . '/*__posts__p_trash1.png');
    eq(1, count($inTrash), 'v koši');
    eq(0, upload_trash_purge(), 'čerstvé ostanú');
    touch($inTrash[0], time() - (UPLOAD_TRASH_DAYS + 1) * 86400);
    eq(1, upload_trash_purge());
});
