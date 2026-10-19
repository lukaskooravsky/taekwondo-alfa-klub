<?php
declare(strict_types=1);
// Titulná fotka aktuality — nahrá sa vopred (base64 v textovom poli, bez dočasného priečinka PHP); formulár potom pošle len odkaz image_ref.
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
try {
    $in = api_input();
} catch (UploadException $e) {
    json_out(413, ['ok' => false, 'error' => $e->getMessage()]);
}
if (empty($in) && (int)($_SERVER['CONTENT_LENGTH'] ?? 0) > 0) json_out(413, ['ok' => false, 'error' => 'Server fotku neprijal (limit ' . ini_get('post_max_size') . ') — skús ju znova alebo menšiu.']);
if (!csrf_check($in['csrf'] ?? null)) json_out(403, ['ok' => false, 'error' => 'Platnosť stránky vypršala. Obnov ju a skús znova.']);

try {
    $stored = upload_from_api('posts', new_id('p'));
    if ($stored === null) json_out(422, ['ok' => false, 'error' => 'Nevybral si žiadnu fotku.']);
    json_out(200, ['ok' => true, 'ref' => $stored['full'] . '|' . $stored['thumb'], 'thumb' => upload_url($stored['thumb'])]);
} catch (UploadException $e) {
    json_out(422, ['ok' => false, 'error' => $e->getMessage()]);
}
