<?php
declare(strict_types=1);
require __DIR__ . '/../../inc/bootstrap.php';
send_security_headers(true);
auth_start_session();
header('Content-Type: application/json; charset=utf-8');

function json_out(int $code, array $data): never
{
    http_response_code($code);
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') json_out(405, ['ok' => false, 'error' => 'Nepovolená metóda.']);
if (!auth_is_logged_in()) json_out(401, ['ok' => false, 'error' => 'Prihlásenie vypršalo. Obnov stránku a prihlás sa znova.']);
if (empty($_POST) && (int)($_SERVER['CONTENT_LENGTH'] ?? 0) > 0) json_out(413, ['ok' => false, 'error' => 'Fotka je príliš veľká pre server (limit ' . ini_get('post_max_size') . ').']);
if (!csrf_check($_POST['csrf'] ?? null)) json_out(403, ['ok' => false, 'error' => 'Platnosť stránky vypršala. Obnov ju a skús znova.']);

$albumId = input_str($_POST['album'] ?? '');
try {
    $exists = album_get($albumId) !== null;
} catch (StoreException $e) {
    error_log($e->getMessage());
    json_out(500, ['ok' => false, 'error' => 'Dáta sa nepodarilo načítať — obnov zálohu.']);
}
if (!$exists) json_out(404, ['ok' => false, 'error' => 'Album sa nenašiel.']);

$photoId = new_id('f');
$stored = null;
try {
    $stored = upload_from_request('full', 'thumb', "albums/$albumId", $photoId);
    if ($stored === null) json_out(422, ['ok' => false, 'error' => 'Nevybral si žiadnu fotku.']);
    $photo = album_add_photo($albumId, $photoId, $stored);
    if ($photo === null) {
        upload_delete($stored['full']);
        upload_delete($stored['thumb']);
        json_out(404, ['ok' => false, 'error' => 'Album medzitým niekto zmazal.']);
    }
    json_out(200, ['ok' => true, 'photo' => $photo]);
} catch (UploadException $e) {
    json_out(422, ['ok' => false, 'error' => $e->getMessage()]);
} catch (StoreException $e) {
    error_log($e->getMessage());
    if ($stored) {
        upload_delete($stored['full']);
        upload_delete($stored['thumb']);
    }
    json_out(500, ['ok' => false, 'error' => 'Nepodarilo sa uložiť, skús znova.']);
}
