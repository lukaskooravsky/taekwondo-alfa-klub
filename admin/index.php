<?php
declare(strict_types=1);
require __DIR__ . '/_init.php';
auth_require();

try {
    $posts = posts_all();
    $albums = albums_all();
} catch (StoreException $e) {
    error_log($e->getMessage());
    $posts = $albums = null;
}
/** „6 aktualít · 2 koncepty“ — koncepty sú len v admine, na webe ich nevidno. */
$summary = function (array $items, array $words): string {
    $drafts = count(array_filter($items, 'is_draft'));
    return sk_plural(count($items), ...$words) . ($drafts ? ' · ' . sk_plural($drafts, 'koncept', 'koncepty', 'konceptov') : '');
};

admin_header('Administrácia');
?>
<?php if ($posts === null): ?>
<div class="flash flash-error">Dáta sa nepodarilo načítať — obnov zálohu z data/backup/.</div>
<?php endif; ?>
<div class="a-tiles">
  <a class="a-tile" href="posts.php">Aktuality<span><?= $posts === null ? '' : e($summary($posts, ['aktualita', 'aktuality', 'aktualít'])) ?></span></a>
  <a class="a-tile" href="albums.php">Galéria<span><?= $albums === null ? '' : e($summary($albums, ['album', 'albumy', 'albumov'])) ?></span></a>
</div>
<?php
admin_footer();
