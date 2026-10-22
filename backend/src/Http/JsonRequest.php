<?php

declare(strict_types=1);

namespace App\Http;

final class JsonRequest
{
    public static function body(): array
    {
        $body = json_decode((string) file_get_contents('php://input'), true);

        return is_array($body) ? $body : [];
    }
}
