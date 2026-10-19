<?php
/**
 * Vygeneruje statickú mapku miesta tréningov (assets/mapa.jpg) z dlaždíc OpenStreetMap, stmavenú do farieb webu.
 * Statický obrázok namiesto vloženej Google mapy = žiadne cookies tretej strany, rýchle načítanie.
 * Použitie: php -d extension=openssl tools/static-map.php [lat] [lon] [zoom]
 */
declare(strict_types=1);

$lat  = (float)($argv[1] ?? 48.1089361);   // ZŠ Holíčska 50 (Nominatim)
$lon  = (float)($argv[2] ?? 17.1023091);
$zoom = (int)($argv[3] ?? 16);
$w = 720; $h = 450;                         // ~1:1 so zobrazením, aby boli popisky ulíc čitateľné
$tile = 256;

$n = 2 ** $zoom;
$px = ($lon + 180) / 360 * $n * $tile;      // stred v pixeloch celého sveta
$py = (1 - log(tan(deg2rad($lat)) + 1 / cos(deg2rad($lat))) / M_PI) / 2 * $n * $tile;
$x0 = $px - $w / 2; $y0 = $py - $h / 2;

$img = imagecreatetruecolor($w, $h);
$ctx = stream_context_create(['http' => ['header' => "User-Agent: alfa-taekwondo-web static map\r\n"]]);
for ($tx = (int)floor($x0 / $tile); $tx <= (int)floor(($x0 + $w) / $tile); $tx++) {
    for ($ty = (int)floor($y0 / $tile); $ty <= (int)floor(($y0 + $h) / $tile); $ty++) {
        $url = "https://tile.openstreetmap.org/$zoom/$tx/$ty.png";
        $data = file_get_contents($url, false, $ctx);
        if ($data === false) exit("Nepodarilo sa stiahnuť $url\n");
        $t = imagecreatefromstring($data);
        imagecopy($img, $t, (int)round($tx * $tile - $x0), (int)round($ty * $tile - $y0), 0, 0, $tile, $tile);
    }
}

// Tmavý štýl: odtiene sivej → negatív → jemne do teplej (zlatistej) farby
imagefilter($img, IMG_FILTER_GRAYSCALE);
imagefilter($img, IMG_FILTER_NEGATE);
imagefilter($img, IMG_FILTER_BRIGHTNESS, -8);
imagefilter($img, IMG_FILTER_CONTRAST, -8);
imagefilter($img, IMG_FILTER_COLORIZE, 10, 6, -6);

// Zlatý špendlík v strede (farby z css/style.css)
imageantialias($img, true);
$gold = imagecolorallocate($img, 0xe6, 0xbd, 0x6a);
$ink  = imagecolorallocate($img, 0x0a, 0x0a, 0x0c);
$halo = imagecolorallocatealpha($img, 0xc8, 0xa0, 0x4e, 100);
$cx = $w / 2; $cy = $h / 2;
imagefilledellipse($img, (int)$cx, (int)$cy, 40, 14, $halo);
imagefilledellipse($img, (int)$cx, (int)($cy - 28), 38, 38, $gold);
imagefilledpolygon($img, [(int)($cx - 16), (int)($cy - 21), (int)($cx + 16), (int)($cy - 21), (int)$cx, (int)$cy], $gold);
imagefilledellipse($img, (int)$cx, (int)($cy - 28), 14, 14, $ink);

$out = dirname(__DIR__) . '/assets/mapa.jpg';
imagejpeg($img, $out, 82);
echo "Uložené: $out (" . round(filesize($out) / 1024) . " kB)\n";
