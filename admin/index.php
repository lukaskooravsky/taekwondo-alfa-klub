<?php
declare(strict_types=1);
require __DIR__ . '/_init.php';
auth_require();

try {
    $postCount = count(posts_all());
    $albumCount = count(albums_all());
} catch (StoreException $e) {
    error_log($e->getMessage());
    $postCount = $albumCount = null;
}

admin_header('Administrácia');
?>
<?php if ($postCount === null): ?>
<div class="flash flash-error">Dáta sa nepodarilo načítať — obnov zálohu z data/backup/.</div>
<?php endif; ?>
<div class="a-tiles">
  <a class="a-tile" href="posts.php">Aktuality<span><?= $postCount === null ? '' : e(sk_plural($postCount, 'aktualita', 'aktuality', 'aktualít')) ?></span></a>
  <a class="a-tile" href="albums.php">Galéria<span><?= $albumCount === null ? '' : e(sk_plural($albumCount, 'album', 'albumy', 'albumov')) ?></span></a>
</div>
<p class="a-section"><a href="password.php">Zmeniť e-mail a heslo</a></p>
<?php
admin_footer();
