<?php

declare(strict_types=1);

use App\Config\Config;
use App\Database\Connection;

return static function (Base $f3): void {
    $f3->route('GET /api/health', static function (): void {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['service' => 'nido', 'environment' => Config::get('APP_ENV', 'development')], JSON_THROW_ON_ERROR);
    });

    $f3->route('GET /api/health/database', static function (): void {
        Connection::create()->query('SELECT 1');
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['ok' => true], JSON_THROW_ON_ERROR);
    });
};
