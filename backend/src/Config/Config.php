<?php

declare(strict_types=1);

namespace App\Config;

final class Config
{
    public static function get(string $key, mixed $default = null): mixed
    {
        $value = getenv($key);

        return $value === false ? $default : $value;
    }
}
