<?php
declare(strict_types=1);
require __DIR__ . '/_init.php';
auth_require();
admin_post_only('posts.php');

try {
    post_delete(input_str($_POST['id'] ?? '')) ? flash('Aktualita zmazaná.') : flash('Aktualita sa nenašla.', 'error');
} catch (StoreException $e) {
    error_log($e->getMessage());
    flash('Nepodarilo sa zmazať, skús znova.', 'error');
}
redirect('posts.php');
