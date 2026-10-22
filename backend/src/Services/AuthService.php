<?php

declare(strict_types=1);

namespace App\Services;

use App\Database\Connection;
use PDO;
use RuntimeException;

final class AuthService
{
    public function register(string $name, string $email, string $password): array
    {
        if ($name === '' || !filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($password) < 8) {
            throw new RuntimeException('Invalid registration data');
        }

        $pdo = Connection::create();
        $verificationToken = bin2hex(random_bytes(32));
        $statement = $pdo->prepare(
            'INSERT INTO users (name, email, password_hash, email_verification_token_hash, email_verification_expires_at)
             VALUES (:name, :email, :password_hash, :verification_hash, DATE_ADD(CURRENT_TIMESTAMP, INTERVAL 24 HOUR))'
        );
        $statement->execute([
            'name' => $name,
            'email' => strtolower($email),
            'password_hash' => password_hash($password, PASSWORD_DEFAULT),
            'verification_hash' => hash('sha256', $verificationToken),
        ]);

        return ['user_id' => (int) $pdo->lastInsertId(), 'verification_token' => $verificationToken];
    }

    public function login(string $email, string $password): ?array
    {
        $pdo = Connection::create();
        $statement = $pdo->prepare('SELECT * FROM users WHERE email = :email AND active = 1 AND email_verified_at IS NOT NULL');
        $statement->execute(['email' => strtolower($email)]);
        $user = $statement->fetch(PDO::FETCH_ASSOC);

        if ($user === false || !password_verify($password, (string) $user['password_hash'])) {
            return null;
        }

        return $this->createSession($pdo, (int) $user['id']);
    }

    public function verifyEmail(string $token): bool
    {
        $statement = Connection::create()->prepare(
            'UPDATE users SET email_verified_at = CURRENT_TIMESTAMP, email_verification_token_hash = NULL, email_verification_expires_at = NULL
             WHERE email_verification_token_hash = :token_hash AND email_verification_expires_at > CURRENT_TIMESTAMP'
        );
        $statement->execute(['token_hash' => hash('sha256', $token)]);

        return $statement->rowCount() > 0;
    }

    private function createSession(PDO $pdo, int $userId): array
    {
        $token = bin2hex(random_bytes(32));
        $statement = $pdo->prepare(
            'INSERT INTO user_sessions (user_id, token_hash, expires_at)
             VALUES (:user_id, :token_hash, DATE_ADD(CURRENT_TIMESTAMP, INTERVAL 30 DAY))'
        );
        $statement->execute(['user_id' => $userId, 'token_hash' => hash('sha256', $token)]);

        return ['token' => $token, 'user_id' => $userId];
    }
}
