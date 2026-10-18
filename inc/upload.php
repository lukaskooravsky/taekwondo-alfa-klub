<?php
declare(strict_types=1);

final class UploadException extends RuntimeException {}

const UPLOAD_MAX_BYTES = 5 * 1024 * 1024;
const UPLOAD_TYPES = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
const IMAGE_FULL_MAX = 1600;
const IMAGE_THUMB_MAX = 480;
const GD_MAX_PIXELS = 25_000_000;
// modifikátor D: $ nepustí koncové \n
const UPLOAD_SUBDIR_RE = '~^(posts|inline|albums/a_[a-z0-9]{6,32})$~D'; // inline = fotky vložené do textu v editore
const UPLOAD_BASE_RE = '~^[pfi]_[a-z0-9]{6,32}$~D';
const UPLOAD_REL_RE = '~^(posts|inline|albums/a_[a-z0-9]{6,32})/[pfi]_[a-z0-9]{6,32}(_t)?\.(jpg|png|webp)$~D';

/** Overí záznam z $_FILES; vráti príponu podľa skutočného obsahu súboru. */
function upload_check(array $file): string
{
    $err = $file['error'] ?? UPLOAD_ERR_NO_FILE;
    if ($err === UPLOAD_ERR_INI_SIZE) throw new UploadException('Fotka je príliš veľká pre server (limit ' . ini_get('upload_max_filesize') . ').');
    if ($err === UPLOAD_ERR_FORM_SIZE) throw new UploadException('Fotka je príliš veľká (max 5 MB).');
    if ($err === UPLOAD_ERR_NO_FILE) throw new UploadException('Nevybral si žiadnu fotku.');
    if ($err !== UPLOAD_ERR_OK) throw new UploadException('Fotku sa nepodarilo nahrať, skús znova.');
    $path = (string)($file['tmp_name'] ?? '');
    if (!is_file($path)) throw new UploadException('Fotku sa nepodarilo nahrať, skús znova.');
    if (($file['size'] ?? 0) > UPLOAD_MAX_BYTES || filesize($path) > UPLOAD_MAX_BYTES) {
        throw new UploadException('Fotka je príliš veľká (max 5 MB).');
    }
    $mime = (string)(new finfo(FILEINFO_MIME_TYPE))->file($path);
    $info = @getimagesize($path);
    if (!isset(UPLOAD_TYPES[$mime]) || $info === false || $info['mime'] !== $mime) {
        throw new UploadException('Tento formát sa nedá spracovať — ulož fotku ako JPG, PNG alebo WebP. (iPhone: Nastavenia → Kamera → Formáty → Najkompatibilnejší.)');
    }
    return UPLOAD_TYPES[$mime];
}

function upload_path(string $rel): string { return uploads_dir() . '/' . $rel; }
function upload_url(string $rel): string { return 'uploads/' . $rel; }

/**
 * Uloží fotku ako uploads/<subdir>/<base>.<ext> a náhľad <base>_t.<ext>.
 * $thumb = null → veľkú verziu aj náhľad vyrobí server cez GD; bez GD sa uloží originál a slúži aj ako náhľad.
 */
function upload_store_pair(array $full, ?array $thumb, string $subdir, string $base, bool $fromHttp = true): array
{
    if (!preg_match(UPLOAD_SUBDIR_RE, $subdir) || !preg_match(UPLOAD_BASE_RE, $base)) {
        throw new InvalidArgumentException('Neplatná cesta pre upload.');
    }
    $ext = upload_check($full);
    $tExt = $thumb !== null ? upload_check($thumb) : null;

    ensure_storage();
    if (!is_dir(upload_path($subdir)) && !mkdir(upload_path($subdir), 0775, true)) {
        throw new UploadException('Fotku sa nepodarilo uložiť, skús znova.');
    }

    if ($thumb !== null) {
        $fullRel = "$subdir/$base.$ext";
        $thumbRel = "$subdir/{$base}_t.$tExt";
        upload_move($full['tmp_name'], upload_path($fullRel), $fromHttp);
        upload_move($thumb['tmp_name'], upload_path($thumbRel), $fromHttp);
    } elseif (gd_available()) {
        [$sw, $sh] = getimagesize($full['tmp_name']); // rozmery bez dekódovania (upload_check už overil, že je to obrázok)
        gd_check_pixels($sw, $sh);
        // dekódovaná 24 MP fotka zaberie ~100 MB; limit len zvýšime, nikdy neznížime (Websupport má 512 MB)
        $mem = (string)ini_get('memory_limit');
        if ($mem !== '-1' && ini_bytes($mem) < 256 * 1024 * 1024) @ini_set('memory_limit', '256M');
        $out = function_exists('imagewebp') ? 'webp' : 'jpg';
        $fullRel = "$subdir/$base.$out";
        $thumbRel = "$subdir/{$base}_t.$out";
        gd_resize($full['tmp_name'], upload_path($fullRel), IMAGE_FULL_MAX);
        gd_resize($full['tmp_name'], upload_path($thumbRel), IMAGE_THUMB_MAX);
    } else {
        $fullRel = $thumbRel = "$subdir/$base.$ext";
        upload_move($full['tmp_name'], upload_path($fullRel), $fromHttp);
    }
    [$w, $h] = getimagesize(upload_path($fullRel));
    return ['full' => $fullRel, 'thumb' => $thumbRel, 'w' => $w, 'h' => $h];
}

function upload_move(string $from, string $to, bool $fromHttp): void
{
    $ok = $fromHttp ? move_uploaded_file($from, $to) : copy($from, $to);
    if (!$ok) throw new UploadException('Fotku sa nepodarilo uložiť, skús znova.');
}

function gd_available(): bool
{
    return function_exists('imagecreatefromstring') && (function_exists('imagewebp') || function_exists('imagejpeg'));
}

/** '512M' → bajty (php.ini skratky K/M/G). */
function ini_bytes(string $v): int
{
    $v = trim($v);
    $mult = ['k' => 1024, 'm' => 1024 ** 2, 'g' => 1024 ** 3][strtolower(substr($v, -1))] ?? 1;
    return (int)$v * $mult;
}

function gd_check_pixels(int $w, int $h): void
{
    if ($w * $h > GD_MAX_PIXELS) throw new UploadException('Fotka má príliš vysoké rozlíšenie, zmenši ju prosím.');
}

function gd_resize(string $src, string $dest, int $max): void
{
    $img = @imagecreatefromstring((string)file_get_contents($src));
    if ($img === false) throw new UploadException('Fotku sa nepodarilo spracovať.');
    if (function_exists('exif_read_data')) {
        $exif = @exif_read_data($src);
        $rot = [3 => 180, 6 => -90, 8 => 90][$exif['Orientation'] ?? 1] ?? 0;
        if ($rot !== 0) $img = imagerotate($img, $rot, 0);
    }
    $w = imagesx($img);
    $h = imagesy($img);
    $scale = min(1, $max / max($w, $h));
    $nw = max(1, (int)round($w * $scale));
    $nh = max(1, (int)round($h * $scale));
    $out = imagecreatetruecolor($nw, $nh);
    imagealphablending($out, false);
    imagesavealpha($out, true);
    imagecopyresampled($out, $img, 0, 0, 0, 0, $nw, $nh, $w, $h);
    $ok = str_ends_with($dest, '.webp') ? imagewebp($out, $dest, 82) : imagejpeg($out, $dest, 85);
    if (!$ok) throw new UploadException('Fotku sa nepodarilo uložiť, skús znova.');
}

/** Vráti záznam z $_FILES, alebo null, ak pole chýba / nebol vybraný súbor. */
function upload_field(string $name): ?array
{
    $f = $_FILES[$name] ?? null;
    if (!is_array($f) || !isset($f['error']) || is_array($f['error']) || $f['error'] === UPLOAD_ERR_NO_FILE) return null;
    return $f;
}

/** Uloží fotku z formulára; null, ak sa žiadna nenahrávala. */
function upload_from_request(string $fullField, ?string $thumbField, string $subdir, string $base): ?array
{
    $full = upload_field($fullField);
    if ($full === null) return null;
    return upload_store_pair($full, $thumbField !== null ? upload_field($thumbField) : null, $subdir, $base);
}

const UPLOAD_TRASH_DAYS = 30;

/** Kôš je v data/trash — rovnako chránený ako dáta (aj keď sa data/ presunie mimo webu do ../alfa-data). */
function upload_trash_dir(): string { return data_dir() . '/trash'; }

/**
 * „Zmazaná“ fotka ide do koša data/trash (zvonku neprístupný) a natrvalo zmizne po UPLOAD_TRASH_DAYS dňoch.
 * Po obnove zálohy data/backup/*.json sa dá vrátiť (NASADENIE.md). Názov v koši: <čas>__<cesta s __ namiesto />.
 */
function upload_trash_file(string $path, string $rel): void
{
    $trash = upload_trash_dir();
    if (!is_dir($trash)) @mkdir($trash, 0775, true);
    $to = $trash . '/' . date('Ymd-His') . '__' . str_replace('/', '__', $rel);
    if (!is_dir($trash) || !@rename($path, $to)) @unlink($path);    // bez koša aspoň zmazať
    else @touch($to);   // rename drží čas nahratia — 30 dní v koši sa má počítať od zmazania
    if (random_int(1, 20) === 1) upload_trash_purge();
}

function upload_trash_purge(?int $now = null): int
{
    $limit = ($now ?? time()) - UPLOAD_TRASH_DAYS * 86400;
    $n = 0;
    foreach (glob(upload_trash_dir() . '/*__*') ?: [] as $f) {
        if (is_file($f) && (int)@filemtime($f) < $limit && @unlink($f)) $n++;
    }
    return $n;
}

function upload_delete(?string $rel): void
{
    if ($rel === null || !preg_match(UPLOAD_REL_RE, $rel)) return;
    $path = upload_path($rel);
    if (is_file($path)) upload_trash_file($path, $rel);
}

function upload_delete_dir(string $albumId): void
{
    if (!preg_match('~^a_[a-z0-9]{6,32}$~D', $albumId)) return;
    $dir = upload_path("albums/$albumId");
    if (!is_dir($dir)) return;
    foreach (glob("$dir/*") ?: [] as $f) {
        if (is_file($f)) upload_trash_file($f, "albums/$albumId/" . basename($f));
    }
    @rmdir($dir);
}

/**
 * Denné upratovanie (volá admin/_init.php): kôš fotiek a zálohy pred zmazaním staršie ako 30 dní — aj keď sa dlho nič
 * nemaže (sľub „30 dní“ v GDPR texte a NASADENIE.md). Raz za deň podľa času zmeny súboru data/.cleanup.
 */
function maintenance_daily(?int $now = null): bool
{
    $now ??= time();
    $marker = data_dir() . '/.cleanup';
    if (is_file($marker) && (int)@filemtime($marker) > $now - 86400) return false;
    @touch($marker, $now);
    upload_trash_purge($now);
    $limit = $now - UPLOAD_TRASH_DAYS * 86400;
    foreach (glob(data_dir() . '/backup/*-pred-zmazanim-*.json') ?: [] as $f) {
        if ((int)@filemtime($f) < $limit) @unlink($f);
    }
    // starší kôš priamo v uploads/.trash (verzia z 2026-10-18) — dobehnúť a zmazať
    foreach (glob(uploads_dir() . '/.trash/*__*') ?: [] as $f) {
        if (is_file($f) && (int)@filemtime($f) < $limit) @unlink($f);
    }
    return true;
}
