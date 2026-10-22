<?php

declare(strict_types=1);

namespace App\Database;

use App\Config\Config;
use PDO;

final class Connection
{
    public static function create(): PDO
    {
        $dsn = sprintf('mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4', Config::get('DB_HOST', 'mariadb'), Config::get('DB_PORT', '3306'), Config::get('DB_DATABASE', 'nido'));

        return new PDO($dsn, (string) Config::get('DB_USERNAME', 'nido'), (string) Config::get('DB_PASSWORD', 'nido'), [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);
    }
}
