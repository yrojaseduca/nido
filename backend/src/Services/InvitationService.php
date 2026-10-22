<?php

declare(strict_types=1);

namespace App\Services;

use App\Database\Connection;
use PDO;
use RuntimeException;

final class InvitationService
{
    public function create(string $workspaceId, int $inviterId, string $email, string $role): array
    {
        if (!filter_var($email, FILTER_VALIDATE_EMAIL) || !in_array($role, ['admin', 'member', 'guest'], true)) {
            throw new RuntimeException('Invalid invitation data');
        }

        $token = bin2hex(random_bytes(32));
        $statement = Connection::create()->prepare(
            'INSERT INTO workspace_invitations (workspace_id, invited_by_user_id, email, role, token_hash, expires_at)
             VALUES (:workspace_id, :invited_by_user_id, :email, :role, :token_hash, DATE_ADD(CURRENT_TIMESTAMP, INTERVAL 7 DAY))'
        );
        $statement->execute([
            'workspace_id' => $workspaceId,
            'invited_by_user_id' => $inviterId,
            'email' => strtolower($email),
            'role' => $role,
            'token_hash' => hash('sha256', $token),
        ]);

        return ['token' => $token, 'email' => strtolower($email), 'role' => $role, 'status' => 'pending'];
    }

    public function list(string $workspaceId): array
    {
        $statement = Connection::create()->prepare(
            'SELECT id, email, role, status, expires_at, created_at FROM workspace_invitations
             WHERE workspace_id = :workspace_id ORDER BY created_at DESC'
        );
        $statement->execute(['workspace_id' => $workspaceId]);

        return $statement->fetchAll(PDO::FETCH_ASSOC);
    }

    public function revoke(string $workspaceId, int $invitationId): bool
    {
        $statement = Connection::create()->prepare(
            "UPDATE workspace_invitations SET status = 'revoked'
             WHERE id = :id AND workspace_id = :workspace_id AND status = 'pending'"
        );
        $statement->execute(['id' => $invitationId, 'workspace_id' => $workspaceId]);

        return $statement->rowCount() > 0;
    }

    public function accept(string $token, int $userId, string $email): bool
    {
        $pdo = Connection::create();
        $statement = $pdo->prepare(
            "SELECT * FROM workspace_invitations WHERE token_hash = :token_hash
             AND status = 'pending' AND expires_at > CURRENT_TIMESTAMP FOR UPDATE"
        );
        $pdo->beginTransaction();
        $statement->execute(['token_hash' => hash('sha256', $token)]);
        $invitation = $statement->fetch(PDO::FETCH_ASSOC);
        if ($invitation === false || strtolower($invitation['email']) !== strtolower($email)) {
            $pdo->rollBack();
            return false;
        }

        $member = $pdo->prepare(
            'INSERT INTO workspace_members (workspace_id, user_id, role) VALUES (:workspace_id, :user_id, :role)
             ON DUPLICATE KEY UPDATE role = VALUES(role), active = 1'
        );
        $member->execute(['workspace_id' => $invitation['workspace_id'], 'user_id' => $userId, 'role' => $invitation['role']]);
        $update = $pdo->prepare("UPDATE workspace_invitations SET status = 'accepted' WHERE id = :id");
        $update->execute(['id' => $invitation['id']]);
        $pdo->commit();

        return true;
    }
}
