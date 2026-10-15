<?php
declare(strict_types=1);

test('install_seed nahrá ukážkový obsah a nič neprepíše', function () {
    install_seed(ROOT . '/seed');
    eq(6, count(posts_all()));
    eq('Nová sezóna sa začína', posts_all()[0]['title']);
    eq(1, count(albums_public()));
    foreach (posts_all() as $p) ok(is_file(upload_path($p['image']['full'])), $p['image']['full']);
    foreach (albums_all()[0]['photos'] as $ph) ok(is_file(upload_path($ph['full'])), $ph['full']);

    post_delete('p_seed01');
    install_seed(ROOT . '/seed');
    eq(5, count(posts_all()), 'existujúce dáta sa neprepíšu');
});

test('ukážkové aktuality sa dajú zmazať aj so súbormi', function () {
    install_seed(ROOT . '/seed');
    ok(post_delete('p_seed02'));
    ok(!is_file(upload_path('posts/p_seed02.jpg')));
});

test('install token: zápis, čítanie, overenie', function () {
    $file = install_token_file();
    eq(null, install_token_read($file), 'bez súboru nie je token');
    ok(!install_token_valid('cokolvek', $file));
    $token = install_token_write($file);
    eq(64, strlen($token));
    eq($token, install_token_read($file));
    ok(install_token_valid($token, $file));
    ok(!install_token_valid(strrev($token), $file));
    ok(!install_token_valid('', $file));
    ok(!install_token_valid(null, $file));
    ok(str_starts_with((string)file_get_contents($file), '<?php'), 'súbor je PHP — priamy prístup nič nevypíše');
});

test('install_token_read odmietne súbor bez platného tokenu', function () {
    file_put_contents(install_token_file(), "<?php exit;\n// TOKEN=kratky\n");
    eq(null, install_token_read(install_token_file()));
});

test('install_set_credentials: validácia a výhradné vytvorenie auth.json', function () {
    $e = throws(fn() => install_set_credentials('trener', 'kratke', 'kratke'), ValidationException::class);
    ok(str_starts_with($e->getMessage(), 'Heslu chýba:'));
    $e = throws(fn() => install_set_credentials('ľ', 'Tajne-Heslo1', 'Tajne-Heslo1'), ValidationException::class);
    ok(isset($e->errors['user']));
    $e = throws(fn() => install_set_credentials('trener', 'Tajne-Heslo1', 'Ine-Heslo12'), ValidationException::class);
    eq('Heslá sa nezhodujú.', $e->getMessage());
    ok(install_set_credentials('Trener', 'Tajne-Heslo1', 'Tajne-Heslo1'));
    ok(!install_set_credentials('druhy', 'Ine-Heslo123!', 'Ine-Heslo123!'), 'druhá inštalácia neprepíše');
    ok(password_verify('Tajne-Heslo1', auth_hash()));
    eq('trener', auth_user(), 'meno uložené malými');
});

test('install_requirements: lokálne PHP spĺňa povinné požiadavky', function () {
    eq([], install_requirements()['required']);
});
