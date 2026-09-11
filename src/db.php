<?php

declare(strict_types=1);

require_once __DIR__ . '/helpers.php';

function db(): PDO
{
    static $pdo = null;
    if ($pdo instanceof PDO) {
        return $pdo;
    }

    $path = data_dir() . '/wol.sqlite';
    $pdo = new PDO('sqlite:' . $path, null, null, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
    $pdo->exec('PRAGMA foreign_keys = ON');
    migrate($pdo);

    return $pdo;
}

function migrate(PDO $pdo): void
{
    $pdo->exec(
        'CREATE TABLE IF NOT EXISTS computers (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            hostname TEXT NOT NULL,
            ip TEXT UNIQUE,
            mac TEXT UNIQUE
        )'
    );

    // Older installs used NOT NULL on ip/mac — rebuild so empties can be stored as NULL.
    $cols = $pdo->query('PRAGMA table_info(computers)')->fetchAll();
    $needsRelax = false;
    foreach ($cols as $col) {
        if (in_array($col['name'], ['ip', 'mac'], true) && (int) $col['notnull'] === 1) {
            $needsRelax = true;
            break;
        }
    }
    if (!$needsRelax) {
        return;
    }

    $pdo->exec('BEGIN');
    $pdo->exec(
        'CREATE TABLE computers__new (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            hostname TEXT NOT NULL,
            ip TEXT UNIQUE,
            mac TEXT UNIQUE
        )'
    );
    $pdo->exec(
        "INSERT INTO computers__new (id, hostname, ip, mac)
         SELECT id, hostname,
           CASE WHEN ip IS NULL OR trim(ip) = '' THEN NULL ELSE ip END,
           CASE WHEN mac IS NULL OR trim(mac) = '' THEN NULL ELSE mac END
         FROM computers"
    );
    $pdo->exec('DROP TABLE computers');
    $pdo->exec('ALTER TABLE computers__new RENAME TO computers');
    $pdo->exec('COMMIT');
}

function computers_all(): array
{
    return db()->query('SELECT * FROM computers ORDER BY hostname COLLATE NOCASE ASC')->fetchAll();
}

function computer_find(int $id): ?array
{
    $stmt = db()->prepare('SELECT * FROM computers WHERE id = ?');
    $stmt->execute([$id]);
    $row = $stmt->fetch();

    return $row ?: null;
}

function computer_create(string $hostname, ?string $ip, ?string $mac): int
{
    $stmt = db()->prepare(
        'INSERT INTO computers (hostname, ip, mac) VALUES (?, ?, ?)'
    );
    $stmt->execute([$hostname, $ip, $mac]);

    return (int) db()->lastInsertId();
}

function computer_update(int $id, string $hostname, ?string $ip, ?string $mac): void
{
    $stmt = db()->prepare(
        'UPDATE computers SET hostname = ?, ip = ?, mac = ? WHERE id = ?'
    );
    $stmt->execute([$hostname, $ip, $mac, $id]);
}

function computer_delete(int $id): void
{
    $stmt = db()->prepare('DELETE FROM computers WHERE id = ?');
    $stmt->execute([$id]);
}
