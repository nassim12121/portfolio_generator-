<?php
declare(strict_types=1);

function ensureUsersTable(PDO $pdo): void
{
    $pdo->exec(
        "CREATE TABLE IF NOT EXISTS users (
            id INT AUTO_INCREMENT PRIMARY KEY,
            first_name VARCHAR(50) NOT NULL,
            last_name VARCHAR(50) NOT NULL,
            email VARCHAR(100) NOT NULL UNIQUE,
            password VARCHAR(255) NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        )"
    );

    $hasFirstName = (bool)$pdo->query("SHOW COLUMNS FROM users LIKE 'first_name'")->fetch();
    if (!$hasFirstName) {
        $pdo->exec("ALTER TABLE users ADD COLUMN first_name VARCHAR(50) NOT NULL DEFAULT '' AFTER id");
    }

    $hasLastName = (bool)$pdo->query("SHOW COLUMNS FROM users LIKE 'last_name'")->fetch();
    if (!$hasLastName) {
        $pdo->exec("ALTER TABLE users ADD COLUMN last_name VARCHAR(50) NOT NULL DEFAULT '' AFTER first_name");
    }

    $hasUpdatedAt = (bool)$pdo->query("SHOW COLUMNS FROM users LIKE 'updated_at'")->fetch();
    if (!$hasUpdatedAt) {
        $pdo->exec(
            "ALTER TABLE users ADD COLUMN updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP"
        );
    }
}

function db(): PDO
{
    static $pdo = null;

    if ($pdo instanceof PDO) {
        return $pdo;
    }

    $host = '127.0.0.1';
    $port = 3306;
    $dbname = 'portfolio_gen';
    $username = 'root';
    $password = '';

    $dsn = sprintf('mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4', $host, $port, $dbname);
    $serverDsn = sprintf('mysql:host=%s;port=%d;charset=utf8mb4', $host, $port);

    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ];

    try {
        $pdo = new PDO($dsn, $username, $password, $options);
    } catch (PDOException $e) {
        if (stripos($e->getMessage(), 'Unknown database') === false) {
            throw $e;
        }

        $bootstrapPdo = new PDO($serverDsn, $username, $password, $options);
        $bootstrapPdo->exec(sprintf('CREATE DATABASE IF NOT EXISTS `%s` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci', $dbname));
        $pdo = new PDO($dsn, $username, $password, $options);
    }

    ensureUsersTable($pdo);

    return $pdo;
}
