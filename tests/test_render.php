<?php
declare(strict_types=1);

test('render_post_card escapuje text a odkazuje na detail', function () {
    $p = ['id' => 'p_abcdef', 'title' => '<script>x</script> "Q"', 'date' => '2026-09-12', 'excerpt' => 'a & b', 'image' => null];
    $html = render_post_card($p);
    ok(!str_contains($html, '<script>'), $html);
    ok(str_contains($html, '&lt;script&gt;x&lt;/script&gt; &quot;Q&quot;'), $html);
    ok(str_contains($html, 'a &amp; b'));
    ok(str_contains($html, 'href="aktualita.php?id=p_abcdef"'));
    ok(str_contains($html, '12. 09. 2026'));
    ok(str_contains($html, 'src="assets/logo-full.jpg"'), 'bez fotky = logo');
    ok(str_contains($html, 'class="post reveal"'));
});

test('post_image použije náhľad z uploads', function () {
    eq(['full' => 'uploads/posts/p_a.webp', 'thumb' => 'uploads/posts/p_a_t.webp'],
        post_image(['image' => ['full' => 'posts/p_a.webp', 'thumb' => 'posts/p_a_t.webp']]));
});

test('render_gallery_figure: náhľad v odkaze na plnú fotku (lightbox, klávesnica, bez JS)', function () {
    $html = render_gallery_figure(['full' => 'albums/a_x/f_1.webp', 'thumb' => 'albums/a_x/f_1_t.webp', 'w' => 1600, 'h' => 900], 'Album "1"');
    ok(str_contains($html, 'src="uploads/albums/a_x/f_1_t.webp"'));
    ok(str_contains($html, '<a href="uploads/albums/a_x/f_1.webp" data-lightbox'), $html);
    ok(str_contains($html, 'width="1600" height="900"'), 'rozmery proti posunu stránky');
    ok(str_contains($html, 'alt="Album &quot;1&quot;"'));
});

test('render_album_tile: titulná fotka, počet, odkaz', function () {
    $a = ['id' => 'a_abcdef', 'title' => 'Skúšky', 'date' => '2026-06-20', 'cover' => null, 'photos' => [
        ['id' => 'f_1', 'full' => 'albums/a_abcdef/f_1.webp', 'thumb' => 'albums/a_abcdef/f_1_t.webp'],
        ['id' => 'f_2', 'full' => 'albums/a_abcdef/f_2.webp', 'thumb' => 'albums/a_abcdef/f_2_t.webp'],
    ]];
    $html = render_album_tile($a);
    ok(str_contains($html, 'href="galeria.php?album=a_abcdef"'));
    ok(str_contains($html, 'uploads/albums/a_abcdef/f_1_t.webp'));
    ok(str_contains($html, '2 fotky'));
    ok(str_contains($html, '20. 06. 2026'));
});
