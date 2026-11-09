<?php

declare(strict_types=1);

final class Database
{
    private static ?PDO $pdo = null;

    public static function conn(): PDO
    {
        if (self::$pdo instanceof PDO) {
            return self::$pdo;
        }

        $cfg = require __DIR__ . '/../config/database.php';

        $dsn = sprintf(
            '%s:host=%s;port=%d;dbname=%s;charset=%s',
            $cfg['driver'],
            $cfg['host'],
            (int)$cfg['port'],
            $cfg['dbname'],
            $cfg['charset']
        );

        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION, // mostra erros
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,       // retorna arrays assoc
            PDO::ATTR_EMULATE_PREPARES   => false,                  // segurança/performance
        ];

        self::$pdo = new PDO($dsn, $cfg['user'], $cfg['pass'], $options);

        return self::$pdo;
    }
}
