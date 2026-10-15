<?php
declare(strict_types=1);

function with_password(string $pw, string $user = 'trener'): void
{
    auth_write_credentials($user, password_hash($pw, PASSWORD_BCRYPT, ['cost' => 4])); // nízky cost = rýchle testy
}

test('správne heslo prihlási, zlé nie', function () {
    with_password('tajneheslo1');
    eq('bad', auth_attempt('trener', 'zle', 'k1'));
    ok(!auth_is_logged_in());
    eq('ok', auth_attempt('trener', 'tajneheslo1', 'k1'));
    ok(auth_is_logged_in());
});

test('bez hesla alebo s poškodeným auth.json sa nedá prihlásiť', function () {
    eq('', auth_hash());
    eq('bad', auth_attempt('trener', '', 'k1'));
    ensure_storage();
    file_put_contents(auth_file(), '{rozbité');
    eq('', auth_hash());
    eq('bad', auth_attempt('trener', 'cokolvek12', 'k1'));
    ok(!auth_is_logged_in());
});

test('5 chybných pokusov zablokuje na 15 minút, potom sa dá znova', function () {
    with_password('tajneheslo1');
    $t = 1_000_000;
    for ($i = 1; $i <= 4; $i++) eq('bad', auth_attempt('trener', 'zle', 'k1', $t));
    eq('locked', auth_attempt('trener', 'zle', 'k1', $t));
    eq('locked', auth_attempt('trener', 'tajneheslo1', 'k1', $t + 60), 'počas blokácie ani správne heslo');
    eq('bad', auth_attempt('trener', 'zle', 'k2', $t), 'iný klient nie je blokovaný');
    eq('ok', auth_attempt('trener', 'tajneheslo1', 'k1', $t + LOGIN_LOCK_SECONDS + 1));
});

test('na 5. pokuse správne heslo ešte prejde a úspech vynuluje počítadlo', function () {
    with_password('tajneheslo1');
    $t = 1_000_000;
    for ($i = 0; $i < 4; $i++) auth_attempt('trener', 'zle', 'k1', $t);
    eq('ok', auth_attempt('trener', 'tajneheslo1', 'k1', $t));
    eq('bad', auth_attempt('trener', 'zle', 'k1', $t), 'po úspechu sa počíta od nuly');
});

test('globálny strop 50 pokusov / 15 min zablokuje všetkých', function () {
    with_password('tajneheslo1');
    $t = 1_000_000;
    for ($i = 0; $i < LOGIN_GLOBAL_MAX - 1; $i++) eq('bad', auth_attempt('trener', 'zle', "g$i", $t));
    eq('locked', auth_attempt('trener', 'zle', 'g49', $t), '50. pokus spustí blokáciu');
    eq('locked', auth_attempt('trener', 'tajneheslo1', 'novy', $t + 10), 'aj nový klient so správnym heslom');
    eq('ok', auth_attempt('trener', 'tajneheslo1', 'novy', $t + LOGIN_LOCK_SECONDS + 1));
});

test('poškodený login-attempts.json sa berie ako prázdny', function () {
    with_password('tajneheslo1');
    ensure_storage();
    file_put_contents(data_dir() . '/login-attempts.json', 'xx');
    eq('ok', auth_attempt('trener', 'tajneheslo1', 'k1'));
});

test('auth_rate_trim drží najviac 500 klientov (najnovších) + globálny záznam', function () {
    $all = ['*' => ['times' => [], 'locked_until' => 0]];
    for ($i = 0; $i < 600; $i++) $all["k$i"] = ['fails' => 1, 'locked_until' => 0, 'last' => $i];
    auth_rate_trim($all);
    eq(LOGIN_MAX_ENTRIES + 1, count($all));
    ok(isset($all['*'], $all['k599']));
    ok(!isset($all['k0']));
});

test('auth_client_ip: hlavičky proxy len pri súkromnej REMOTE_ADDR', function () {
    eq('203.0.113.7', auth_client_ip(['REMOTE_ADDR' => '203.0.113.7', 'HTTP_X_REAL_IP' => '1.2.3.4']), 'verejná adresa ignoruje hlavičky');
    eq('198.51.100.9', auth_client_ip(['REMOTE_ADDR' => '127.0.0.1', 'HTTP_X_REAL_IP' => '198.51.100.9']));
    eq('198.51.100.9', auth_client_ip(['REMOTE_ADDR' => '10.0.0.5', 'HTTP_X_FORWARDED_FOR' => '6.6.6.6, 198.51.100.9']), 'posledná položka = tú pridala proxy; prvú si klient podvrhne');
    eq('10.0.0.5', auth_client_ip(['REMOTE_ADDR' => '10.0.0.5', 'HTTP_X_REAL_IP' => 'nezmysel']), 'neplatná hlavička');
    eq('cli', auth_client_ip([]));
});

test('auth_client_key: IPv6 po /64, IPv4 celá adresa', function () {
    eq(auth_client_key('2001:db8:1:2:aaaa::1'), auth_client_key('2001:db8:1:2:bbbb::2'));
    ok(auth_client_key('2001:db8:1:2::1') !== auth_client_key('2001:db8:1:3::1'));
    ok(auth_client_key('198.51.100.1') !== auth_client_key('198.51.100.2'));
    ok(auth_client_key('::ffff:198.51.100.1') !== auth_client_key('::ffff:198.51.100.2'), 'IPv4-mapped nie sú jedna /64');
    eq(auth_client_key('198.51.100.1'), auth_client_key('::ffff:198.51.100.1'));
});

test('CSRF token; po prihlásení je nový', function () {
    $t = csrf_token();
    eq(64, strlen($t));
    eq($t, csrf_token(), 'token je stabilný v rámci relácie');
    ok(csrf_check($t));
    ok(!csrf_check('zly'));
    ok(!csrf_check(null));
    ok(str_contains(csrf_field(), 'value="' . $t . '"'));
    with_password('tajneheslo1');
    eq('ok', auth_attempt('trener', 'tajneheslo1', 'k1'));
    ok(csrf_token() !== $t, 'po prihlásení nový token');
    $_SESSION = [];
    ok(!csrf_check($t), 'bez relácie neplatí');
});

test('zlé meno sa správa ako zlé heslo; meno nerozlišuje veľké písmená', function () {
    with_password('Tajne-Heslo1');
    eq('bad', auth_attempt('iny', 'Tajne-Heslo1', 'k1'));
    ok(!auth_is_logged_in());
    eq('ok', auth_attempt('  TRENER ', 'Tajne-Heslo1', 'k2'));
});

test('starý auth.json bez mena nikoho neprihlási', function () {
    ensure_storage();
    file_put_contents(auth_file(), json_encode(['hash' => password_hash('Tajne-Heslo1', PASSWORD_BCRYPT, ['cost' => 4])]));
    eq('', auth_user());
    eq('bad', auth_attempt('', 'Tajne-Heslo1', 'k1'));
    eq('', auth_pw_version());
});

test('pravidlá hesla', function () {
    eq(null, auth_password_error('Kopanec-7', 'trener'));
    eq(null, auth_password_error('Žltý pás 9!', 'trener'), 'diakritika a medzera sú v poriadku');
    eq('Heslu chýba: aspoň 8 znakov.', auth_password_error('Ab1!xyz', 'trener'));
    eq('Heslu chýba: veľké písmeno.', auth_password_error('kopanec-7', 'trener'));
    eq('Heslu chýba: malé písmeno.', auth_password_error('KOPANEC-7', 'trener'));
    eq('Heslu chýba: číslicu.', auth_password_error('Kopanec-x', 'trener'));
    eq('Heslu chýba: špeciálny znak (napr. ! ? # * - _).', auth_password_error('Kopanec77', 'trener'));
    eq('Heslu chýba: aspoň 8 znakov, veľké písmeno, číslicu, špeciálny znak (napr. ! ? # * - _).', auth_password_error('abc', 'trener'));
    eq('Heslo nesmie obsahovať prihlasovacie meno.', auth_password_error('Trener-2026', 'trener'));
    ok(str_starts_with((string)auth_password_error(str_repeat('Aa1!', 19), 'trener'), 'Heslo je príliš dlhé'), 'nad 72 bajtov bcrypt ignoruje');
});

test('pravidlá mena', function () {
    eq(null, auth_user_error('trener'));
    eq(null, auth_user_error('lubo.zavodny_1'));
    ok(auth_user_error('ab') !== null, 'krátke');
    ok(auth_user_error('ľubo') !== null, 'diakritika');
    ok(auth_user_error('lubo zavodny') !== null, 'medzera');
    ok(auth_user_error(str_repeat('a', 33)) !== null, 'dlhé');
});

test('zmena hesla zapíše auth.json a nové heslo funguje', function () {
    with_password('Stare-Heslo1');
    $e = throws(fn() => auth_change_credentials('zle', 'trener', 'Nove-Heslo12', 'Nove-Heslo12'), ValidationException::class);
    eq('Súčasné heslo nesedí.', $e->errors['old']);
    $e = throws(fn() => auth_change_credentials('Stare-Heslo1', 'trener', 'kratke', 'kratke'), ValidationException::class);
    ok(str_starts_with($e->errors['new'], 'Heslu chýba:'));
    $e = throws(fn() => auth_change_credentials('Stare-Heslo1', 'trener', 'Nove-Heslo12', 'Ine-Heslo123'), ValidationException::class);
    eq('Nové heslá sa nezhodujú.', $e->errors['new2']);
    $e = throws(fn() => auth_change_credentials('Stare-Heslo1', 'Ľubo', 'Nove-Heslo12', 'Nove-Heslo12'), ValidationException::class);
    ok(isset($e->errors['user']));
    auth_change_credentials('Stare-Heslo1', 'trener', 'Nove-Heslo12', 'Nove-Heslo12');
    ok(password_verify('Nove-Heslo12', auth_hash()));
    eq('ok', auth_attempt('trener', 'Nove-Heslo12', 'k1'));
});

test('zmena len mena: heslo ostáva, staré meno už neplatí', function () {
    with_password('Stare-Heslo1');
    $hash = auth_hash();
    auth_change_credentials('Stare-Heslo1', 'Lubo', '', '');
    eq('lubo', auth_user());
    eq($hash, auth_hash());
    eq('bad', auth_attempt('trener', 'Stare-Heslo1', 'k1'));
    eq('ok', auth_attempt('lubo', 'Stare-Heslo1', 'k2'));
    $e = throws(fn() => auth_change_credentials('Stare-Heslo1', 'heslo', '', ''), ValidationException::class);
    eq('Meno nesmie byť súčasťou hesla.', $e->errors['user']);
});

test('zmena hesla alebo mena odhlási ostatné zariadenia, aktuálne ostane', function () {
    with_password('Stare-Heslo1');
    eq('ok', auth_attempt('trener', 'Stare-Heslo1', 'k1'));
    $otherDevice = $_SESSION;
    auth_change_credentials('Stare-Heslo1', 'trener', 'Nove-Heslo12', 'Nove-Heslo12');
    ok(auth_is_logged_in(), 'aktuálne zariadenie');
    $_SESSION = $otherDevice;
    ok(!auth_is_logged_in(), 'iné zariadenie so starou reláciou');

    with_password('Stare-Heslo1');
    eq('ok', auth_attempt('trener', 'Stare-Heslo1', 'k3'));
    $otherDevice = $_SESSION;
    auth_change_credentials('Stare-Heslo1', 'lubo', '', '');
    ok(auth_is_logged_in(), 'aktuálne zariadenie po zmene mena');
    $_SESSION = $otherDevice;
    ok(!auth_is_logged_in(), 'iné zariadenie po zmene mena');
});

test('auth_write_credentials exclusive: druhý pokus neprepíše', function () {
    ok(auth_write_credentials('trener', 'hash-1', true));
    ok(!auth_write_credentials('ine', 'hash-2', true));
    eq('hash-1', auth_hash());
    eq('trener', auth_user());
});
