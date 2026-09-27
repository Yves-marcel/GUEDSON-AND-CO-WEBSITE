<?php
declare(strict_types=1);

namespace App;

use PDO;

final class Database
{
    private static ?PDO $instance = null;

    public static function connection(array $config): PDO
    {
        if (self::$instance === null) {
            $path = $config['path'];
            $dir = dirname($path);
            if (!is_dir($dir)) {
                mkdir($dir, 0775, true);
            }

            $pdo = new PDO('sqlite:' . $path);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            // Force de vraies requêtes préparées côté moteur plutôt qu'une émulation côté PHP
            // (défense en profondeur contre les injections SQL, en plus du binding de paramètres).
            $pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
            $pdo->exec('PRAGMA foreign_keys = ON');

            self::migrate($pdo);
            self::$instance = $pdo;
        }

        return self::$instance;
    }

    private static function migrate(PDO $pdo): void
    {
        $pdo->exec(<<<SQL
            CREATE TABLE IF NOT EXISTS contacts (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                name TEXT NOT NULL,
                email TEXT NOT NULL,
                subject TEXT NOT NULL,
                message TEXT NOT NULL,
                ip_address TEXT,
                user_agent TEXT,
                email_sent INTEGER NOT NULL DEFAULT 0,
                created_at TEXT NOT NULL DEFAULT (datetime('now'))
            )
        SQL);
        $pdo->exec('CREATE INDEX IF NOT EXISTS idx_contacts_ip_created ON contacts (ip_address, created_at)');
    }
}
