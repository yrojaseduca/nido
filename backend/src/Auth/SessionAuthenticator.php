<?php

declare(strict_types=1);

namespace App\Auth;

use App\Database\Connection;
use PDO;

final class SessionAuthenticator
{
    public function authenticate(string $authorization): ?array
    {
        if (!preg_match('/^Bearer\s+(.+)$/i', $authorization, $matches)) {
            return null;
        }

        $statement = Connection::create()->prepare(
            'SELECT u.* FROM user_sessions s INNER JOIN users u ON u.id = s.user_id
             WHERE s.token_hash = :token_hash AND s.expires_at > CURRENT_TIMESTAMP AND u.active = 1'
        );
        $statement->execute(['token_hash' => hash('sha256', $matches[1])]);
        $user = $statement->fetch(PDO::FETCH_ASSOC);

        return $user === false ? null : $user;
    }

    public function revoke(string $authorization): void
    {
        if (!preg_match('/^Bearer\s+(.+)$/i', $authorization, $matches)) {
            return;
        }

        $statement = Connection::create()->prepare('DELETE FROM user_sessions WHERE token_hash = :token_hash');
        $statement->execute(['token_hash' => hash('sha256', $matches[1])]);
    }
}
