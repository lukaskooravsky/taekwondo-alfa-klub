<?php
declare(strict_types=1);

test('rich_clean: povolené formátovanie ostane', function () {
    $in = '<p><strong>Tučné</strong> <em>kurzíva</em> <u>pod</u> <s>preč</s> 🥇</p><h2>Nadpis</h2><h3>Pod</h3>';
    eq($in, rich_clean($in));
});

test('rich_clean: skripty, on* atribúty a javascript: odkazy zmiznú', function () {
    $out = rich_clean('<p onclick="x()">Ahoj<script>alert(1)</script><img src=x onerror=alert(1)>'
        . '<a href="javascript:alert(1)">zlý</a> <a href="https://alfa.sk" onmouseover="x">dobrý</a></p><style>p{}</style>');
    eq('<p>Ahojzlý <a href="https://alfa.sk" target="_blank" rel="noopener">dobrý</a></p>', $out);
});

test('rich_clean: zoznamy z Quillu → ul/ol, ovládacie span-y preč', function () {
    $q = '<ol><li data-list="bullet"><span class="ql-ui" contenteditable="false"></span>a</li><li data-list="bullet">b</li>'
        . '<li data-list="ordered">c</li></ol>';
    eq('<ul><li>a</li><li>b</li></ul><ol><li>c</li></ol>', rich_clean($q));
    eq('<ol><li>x</li></ol>', rich_clean('<ol><li>x</li></ol>'), 'obyčajný ol ostane');
});

test('rich_clean: písmo, veľkosť a farba len z povolených', function () {
    eq('<p><span class="ql-font-saira ql-size-large" style="color:#e6bd6a">x</span></p>',
        rich_clean('<p><span class="ql-font-saira ql-size-large evil" style="color: rgb(230, 189, 106); position:fixed">x</span></p>'));
    eq('<p>y</p>', rich_clean('<p><span class="ql-font-comic" style="color:#123456">y</span></p>'), 'nepovolené → span preč');
    eq('#ef5350', rich_color('#EF5350'));
    eq(null, rich_color('expression(alert(1))'));
});

test('rich_clean: prázdny editor je prázdny reťazec; neznáme značky sa rozbalia', function () {
    eq('', rich_clean('<p><br></p>'));
    eq('', rich_clean('  '));
    eq('<p>a</p>b', rich_clean('<p><font color="red">a</font><div>b</div></p>'), 'div v p parser uzavrie');
    eq('<p><strong>b</strong><em>i</em></p>', rich_clean('<p><b>b</b><i>i</i></p>'));
});

test('rich_clean inline: odseky a nadpisy na zalomenia, zoznamy preč', function () {
    eq('Prvý <strong>tučný</strong><br>Druhý', rich_clean('<p>Prvý <strong>tučný</strong></p><h2>Druhý</h2>', true));
    eq('a<br>b', rich_clean('<ol><li data-list="bullet">a</li><li data-list="bullet">b</li></ol>', true));
});

test('rich_text a rich_from_text', function () {
    eq("Ahoj & <svet>\nĎalší", rich_text('<p>Ahoj &amp; &lt;svet&gt;</p><p>Ďalší</p>'));
    eq('<p>a<br>&lt;b&gt;</p>', rich_from_text("a\n<b>", true));
    eq("<p>x</p>\n<p>y</p>", rich_from_text("x\n\ny"));
});

test('rich_clean: fotky len z uploads/inline, aj z cesty ../ v admine', function () {
    eq('<p><img src="uploads/inline/i_abc123.webp" alt="" loading="lazy"></p>', rich_clean('<p><img src="../uploads/inline/i_abc123.webp" onerror="x"></p>'));
    eq('', rich_clean('<img src="https://evil.sk/x.png">'));
    eq('', rich_clean('<img src="data:image/png;base64,AAAA">'));
    eq('', rich_clean('<img src="uploads/posts/p_abc123.jpg">'), 'iné priečinky nie');
    eq('', rich_clean('<p><img src="uploads/inline/i_abc123.webp"></p>', true), 'v úvode fotky nie');
});

test('rich_clean: YouTube video cez nocookie, iné iframe preč', function () {
    $out = rich_clean('<iframe class="ql-video" src="https://www.youtube.com/watch?v=dQw4w9WgXcQ&t=5" onload="x"></iframe>');
    ok(str_starts_with($out, '<iframe class="ql-video" src="https://www.youtube-nocookie.com/embed/dQw4w9WgXcQ"'), $out);
    ok(!str_contains($out, 'onload'), $out);
    eq('dQw4w9WgXcQ', rich_youtube_id('https://youtu.be/dQw4w9WgXcQ?si=x'));
    eq('dQw4w9WgXcQ', rich_youtube_id('https://www.youtube.com/shorts/dQw4w9WgXcQ'));
    eq(null, rich_youtube_id('https://evil.sk/embed/dQw4w9WgXcQ'));
    eq(null, rich_youtube_id('https://www.youtube.com.evil.sk/embed/dQw4w9WgXcQ'));
    eq('', rich_clean('<iframe src="https://evil.sk/"></iframe>'));
    eq('', rich_clean('<iframe src="javascript:alert(1)"></iframe>'));
});

test('rich_clean: zarovnanie, citát, čiara, zvýraznenie', function () {
    eq('<p class="ql-align-center">a</p><blockquote class="ql-align-right">b</blockquote><hr>',
        rich_clean('<p class="ql-align-center">a</p><blockquote class="ql-align-right x">b</blockquote><hr onclick="x">'));
    eq('<p><span style="color:#ffd54f;background-color:#5c4a1f">x</span></p>',
        rich_clean('<p><span style="background-color: rgb(92, 74, 31); color: rgb(255, 213, 79);">x</span></p>'));
    eq('<p>y</p>', rich_clean('<p><span style="background:url(javascript:x)">y</span></p>'));
    eq('<p>z</p>', rich_clean('<p><span class="ql-align-center">z</span></p>'), 'zarovnanie len na blokoch');
    eq('a<br>b', rich_clean('<blockquote>a</blockquote><p>b</p>', true));
    eq('', rich_clean('<hr>', true));
});

test('rich_images a rich_gc_images: maže len nepoužité fotky', function () {
    ensure_storage();
    foreach (['i_aaaaaa.webp', 'i_aaaaaa_t.webp', 'i_bbbbbb.jpg', 'i_bbbbbb_t.jpg'] as $f) file_put_contents(upload_path("inline/$f"), 'x');
    eq(['inline/i_aaaaaa.webp'], rich_images('<img src="uploads/inline/i_aaaaaa.webp"><img src="uploads/inline/i_aaaaaa.webp">'));
    post_create(['title' => 'T', 'date' => '2026-01-01', 'excerpt' => 'u', 'rich' => '1', 'body' => '<img src="uploads/inline/i_aaaaaa.webp">'], null);
    rich_gc_images(['inline/i_aaaaaa.webp', 'inline/i_bbbbbb.jpg']);
    ok(is_file(upload_path('inline/i_aaaaaa.webp')), 'použitá ostane');
    ok(!is_file(upload_path('inline/i_bbbbbb.jpg')) && !is_file(upload_path('inline/i_bbbbbb_t.jpg')), 'nepoužitá aj s náhľadom preč');
});

test('fotky z textu sa zmažú pri úprave a zmazaní aktuality aj albumu', function () {
    ensure_storage();
    foreach (['i_cccccc', 'i_dddddd', 'i_eeeeee'] as $b) file_put_contents(upload_path("inline/$b.webp"), 'x');
    $p = post_create(['title' => 'T', 'date' => '2026-01-01', 'excerpt' => 'u', 'rich' => '1',
        'body' => '<p><img src="uploads/inline/i_cccccc.webp"><img src="uploads/inline/i_dddddd.webp"></p>'], null);
    post_update($p['id'], ['title' => 'T', 'date' => '2026-01-01', 'excerpt' => 'u', 'rich' => '1',
        'body' => '<p><img src="uploads/inline/i_cccccc.webp"></p>'], null);
    ok(is_file(upload_path('inline/i_cccccc.webp')) && !is_file(upload_path('inline/i_dddddd.webp')), 'po úprave');
    post_delete($p['id']);
    ok(!is_file(upload_path('inline/i_cccccc.webp')), 'po zmazaní aktuality');
    $a = album_create(['title' => 'A', 'date' => '2026-01-01', 'rich' => '1', 'description' => '<p><img src="uploads/inline/i_eeeeee.webp"></p>']);
    ok(is_file(upload_path('inline/i_eeeeee.webp')));
    album_delete($a['id']);
    ok(!is_file(upload_path('inline/i_eeeeee.webp')), 'po zmazaní albumu');
});

test('fotka v texte: rozmery zo súboru, adresa vlastného webu → relatívna; upratanie neuložených', function () {
    ensure_storage();
    fixture_big_png(upload_path('inline/i_ffffff.png'), 40, 30);
    eq('<p><img src="uploads/inline/i_ffffff.png" width="40" height="30" alt="" loading="lazy"></p>',
        rich_clean('<p><img src="' . SITE_URL . '/uploads/inline/i_ffffff.png"></p>'));
    eq('', rich_clean('<p><img src="https://cudzi.sk/uploads/inline/i_ffffff.png"></p>'), 'cudzia doména nie');
    fixture_big_png(upload_path('inline/i_gggggg.png'), 10, 10);
    touch(upload_path('inline/i_gggggg.png'), time() - 3 * 86400);
    post_create(['title' => 'T', 'date' => '2026-01-01', 'excerpt' => 'u', 'rich' => '1', 'body' => '<p><img src="uploads/inline/i_ffffff.png"></p>'], null);
    touch(upload_path('inline/i_ffffff.png'), time() - 3 * 86400);
    rich_sweep_inline();
    ok(!is_file(upload_path('inline/i_gggggg.png')), 'neuložená stará fotka preč');
    ok(is_file(upload_path('inline/i_ffffff.png')), 'použitá ostane');
});
