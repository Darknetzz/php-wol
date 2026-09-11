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
            ip TEXT NOT NULL UNIQUE,
            mac TEXT NOT NULL UNIQUE
        )'
    );
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

function computer_create(string $hostname, string $ip, string $mac): void
{
    $stmt = db()->prepare(
        'INSERT INTO computers (hostname, ip, mac) VALUES (?, ?, ?)'
    );
    $stmt->execute([$hostname, $ip, $mac]);
}

function computer_update(int $id, string $hostname, string $ip, string $mac): void
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
