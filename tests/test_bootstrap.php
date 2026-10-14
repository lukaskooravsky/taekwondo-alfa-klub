<?php
declare(strict_types=1);

test('ensure_storage vytvorí priečinky a data/.htaccess', function () {
    ensure_storage();
    ok(is_dir(data_dir() . '/backup'), 'data/backup');
    ok(is_dir(data_dir() . '/sessions'), 'data/sessions');
    ok(is_dir(uploads_dir() . '/posts'), 'uploads/posts');
    ok(is_dir(uploads_dir() . '/albums'), 'uploads/albums');
    ok(str_contains((string)file_get_contents(data_dir() . '/.htaccess'), 'Require all denied'), 'data/.htaccess');
    ok(!is_file(uploads_dir() . '/.htaccess'), 'uploads/ bez .htaccess (ochranu robí koreňový .htaccess)');
});

test('cfg vráti default pre chýbajúci kľúč', function () {
    eq('x', cfg('neexistuje', 'x'));
    eq($GLOBALS['TMP'] . '/data', data_dir());
    eq($GLOBALS['TMP'] . '/install-token.php', install_token_file());
    mkdir(tmp('web'));
    eq(tmp('web') . '/data', default_data_dir(tmp('web')), 'bez ../alfa-data ostávajú dáta v ./data');
    mkdir(tmp('alfa-data'));
    eq(tmp('alfa-data'), default_data_dir(tmp('web')), 's ../alfa-data idú dáta mimo webu');
});

test('request_is_https a is_local_host', function () {
    ok(request_is_https(['HTTPS' => 'on']));
    ok(!request_is_https(['HTTPS' => 'off']));
    ok(request_is_https(['HTTP_X_FORWARDED_PROTO' => 'https']), 'za proxy hostingu');
    ok(!request_is_https(['HTTP_X_FORWARDED_PROTO' => 'http']));
    ok(request_is_https(['HTTP_X_FORWARDED_SSL' => 'on']));
    ok(request_is_https(['REQUEST_SCHEME' => 'https']));
    ok(request_is_https(['SERVER_PORT' => '443']));
    ok(!request_is_https(['SERVER_PORT' => '80', 'REQUEST_SCHEME' => 'http']));
    ok(!request_is_https([]));
    ok(is_local_host(['HTTP_HOST' => 'localhost:8000']));
    ok(is_local_host(['HTTP_HOST' => '127.0.0.1']));
    ok(!is_local_host(['HTTP_HOST' => 'alfa-taekwondo.sk']));
    ok(!is_local_host(['HTTP_HOST' => 'localhost.evil.sk']));
});
