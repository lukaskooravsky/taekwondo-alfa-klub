<?php
declare(strict_types=1);
// Nahratie fotky vloženej do textu v editore (aktualita, popis albumu) → uploads/inline/
require __DIR__ . '/../../inc/bootstrap.php';
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

try {
    $stored = upload_from_request('full', 'thumb', 'inline', new_id('i'));
    if ($stored === null) json_out(422, ['ok' => false, 'error' => 'Nevybral si žiadnu fotku.']);
    json_out(200, ['ok' => true, 'src' => upload_url($stored['full']), 'w' => $stored['w'], 'h' => $stored['h']]);
} catch (UploadException $e) {
    json_out(422, ['ok' => false, 'error' => $e->getMessage()]);
}
