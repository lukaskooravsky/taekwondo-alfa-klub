<?php
declare(strict_types=1);

final class StoreException extends RuntimeException {}

final class ValidationException extends RuntimeException
{
    /** @param array<string,string> $errors pole => slovenská hláška */
    public function __construct(public readonly array $errors)
    {
        parent::__construct(implode(' ', $errors));
    }
}

const BACKUP_KEEP = 20;

function new_id(string $prefix): string
{
    return $prefix . '_' . bin2hex(random_bytes(5));
}

function valid_id(?string $id, string $prefix): bool
{
    return is_string($id) && preg_match('/^' . $prefix . '_[a-z0-9]{6,32}$/D', $id) === 1;
}

function now_iso(): string
{
    return date('c');
}

function store_path(string $name): string
{
    return data_dir() . "/$name.json";
}

/** Prečíta zoznam z data/<name>.json. Chýbajúci súbor = [], poškodený = StoreException (volatile: []). */
function store_read(string $name, string $rootKey, bool $volatile = false): array
{
    $file = store_path($name);
    if (!is_file($file)) return [];
    $data = json_decode((string)file_get_contents($file), true);
    if (!is_array($data) || !is_array($data[$rootKey] ?? null)) {
        if ($volatile) return [];
        throw new StoreException("Súbor $name.json je poškodený.");
    }
    return $data[$rootKey];
}

/**
 * Bezpečná úprava dát: zámok → načítanie → $fn(&$items) → záloha + atomický zápis.
 * Ak $fn vyhodí výnimku, nič sa nezapíše. Vráti návratovú hodnotu $fn.
 */
function store_update(string $name, string $rootKey, callable $fn, bool $volatile = false): mixed
{
    ensure_storage();
    $lock = fopen(data_dir() . '/.lock', 'c');
    if ($lock === false || !flock($lock, LOCK_EX)) {
        throw new StoreException('Nepodarilo sa zamknúť dáta.');
    }
    try {
        $items = store_read($name, $rootKey, $volatile);
        $result = $fn($items);
        store_write_locked($name, [$rootKey => $items], !$volatile);
        return $result;
    } finally {
        flock($lock, LOCK_UN);
        fclose($lock);
    }
}

function store_write_locked(string $name, array $data, bool $backup = true): void
{
    $file = store_path($name);
    if ($backup && is_file($file)) store_backup($name);
    try {
        $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
    } catch (JsonException $e) { // napr. neplatné UTF-8 — admin to ukáže ako bežnú chybu uloženia
        throw new StoreException("Nepodarilo sa uložiť $name.json: " . $e->getMessage());
    }
    $tmp = $file . '.tmp-' . bin2hex(random_bytes(4));
    if (file_put_contents($tmp, $json) === false || !rename($tmp, $file)) {
        @unlink($tmp);
        throw new StoreException("Nepodarilo sa uložiť $name.json.");
    }
}

function store_backup(string $name): void
{
    $dir = data_dir() . '/backup';
    copy(store_path($name), sprintf('%s/%s-%s-%s.json', $dir, $name, date('Ymd-His'), bin2hex(random_bytes(3))));
    $files = glob("$dir/$name-*.json") ?: [];
    usort($files, fn(string $a, string $b): int => filemtime($a) <=> filemtime($b) ?: strcmp($a, $b));
    foreach (array_slice($files, 0, max(0, count($files) - BACKUP_KEEP)) as $old) {
        unlink($old);
    }
}
