<?php
declare(strict_types=1);

test('e escapuje HTML aj úvodzovky', function () {
    eq('&lt;script&gt;alert(&quot;x&quot;)&lt;/script&gt; &amp; &#039;', e('<script>alert("x")</script> & \''));
    eq('', e(null));
});

test('text_to_html: odseky, zalomenia, escapovanie', function () {
    eq("<p>Prvý<br>riadok</p>\n<p>Druhý &lt;b&gt;</p>", text_to_html("Prvý\r\nriadok\n\n\nDruhý <b>"));
    eq('', text_to_html("  \n "));
});

test('text_to_html: URL je odkaz, bodka za ňou nie', function () {
    eq('<p>Viac na <a href="https://alfa.sk/a?x=1&amp;y=2" target="_blank" rel="noopener">https://alfa.sk/a?x=1&amp;y=2</a>.</p>',
        text_to_html('Viac na https://alfa.sk/a?x=1&y=2.'));
});

test('text_to_html: úvodzovky okolo URL neuniknú do href', function () {
    $html = text_to_html('"https://alfa.sk" a javascript:alert(1)');
    ok(str_contains($html, 'href="https://alfa.sk"'), $html);
    ok(!str_contains($html, 'href="javascript'), $html);
});

test('valid_date a format_date_sk', function () {
    ok(valid_date('2026-09-12'));
    ok(!valid_date('2026-02-30'));
    ok(!valid_date('12.9.2026'));
    ok(!valid_date(''));
    eq('12. 09. 2026', format_date_sk('2026-09-12'));
    eq('', format_date_sk('zle'));
});

test('str_len počíta znaky, nie bajty; sk_plural; input_str', function () {
    eq(5, str_len('ščťžý'));
    eq('1 fotka', sk_plural(1, 'fotka', 'fotky', 'fotiek'));
    eq('3 fotky', sk_plural(3, 'fotka', 'fotky', 'fotiek'));
    eq('5 fotiek', sk_plural(5, 'fotka', 'fotky', 'fotiek'));
    eq('0 fotiek', sk_plural(0, 'fotka', 'fotky', 'fotiek'));
    eq('Ahoj', input_str('Ahoj'));
    eq('', input_str(['pole']), 'pole z ?id[]=x');
    eq('', input_str(null));
    eq('', input_str("ab\xFF\xFEcd"), 'neplatné UTF-8');
});

test('text_fold a admin_filter', function () {
    eq('sutaz v bratislave', text_fold("  Súťaž  v\nBRATISLAVE "));
    $items = [
        ['fold' => 'skusky pasov', 'year' => '2026', 'flag' => '1', 'date' => '2026-06-01', 'title' => 'Skúšky', 'row' => 1],
        ['fold' => 'sutaz bratislava', 'year' => '2025', 'flag' => '0', 'date' => '2025-03-01', 'title' => 'Súťaž', 'row' => 2],
        ['fold' => 'nabor deti', 'year' => '2026', 'flag' => '0', 'date' => '2026-09-01', 'title' => 'Ábor', 'row' => 3],
    ];
    $rows = fn($l) => array_column($l, 'row');
    eq([2], $rows(admin_filter($items, 'SÚŤAŽ brat', '', '', 'new')));
    eq([1, 3], $rows(admin_filter($items, '', '2026', '', 'new')));
    eq([2, 3], $rows(admin_filter($items, '', '', '0', 'new')));
    eq([2, 1, 3], $rows(admin_filter($items, '', '', '', 'old')));
    eq([], admin_filter($items, 'nič', '', '', 'new'));
});
