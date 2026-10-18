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
