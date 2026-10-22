<?php

declare(strict_types=1);

namespace App\Services;

use App\Database\Connection;
use PDO;
use RuntimeException;

final class MemberService
{
    public function list(string $workspaceId): array
    {
        $statement = Connection::create()->prepare('SELECT u.id, u.name, u.email, wm.role, wm.active, wm.joined_at FROM workspace_members wm INNER JOIN users u ON u.id = wm.user_id WHERE wm.workspace_id = :workspace_id ORDER BY wm.role, u.name');
        $statement->execute(['workspace_id' => $workspaceId]);

        return $statement->fetchAll(PDO::FETCH_ASSOC);
    }

    public function changeRole(string $workspaceId, int $memberId, string $role): bool
    {
        if (!in_array($role, ['admin', 'member', 'guest'], true)) return false;
        $pdo = Connection::create();
        $current = $pdo->prepare('SELECT role FROM workspace_members WHERE workspace_id = :workspace_id AND user_id = :user_id AND active = 1');
        $current->execute(['workspace_id' => $workspaceId, 'user_id' => $memberId]);
        $member = $current->fetch(PDO::FETCH_ASSOC);
        if ($member === false) return false;
        if ($member['role'] === 'admin' && $role !== 'admin' && $this->activeAdmins($workspaceId) <= 1) throw new RuntimeException('Workspace must retain an admin');
        $statement = $pdo->prepare('UPDATE workspace_members SET role = :role WHERE workspace_id = :workspace_id AND user_id = :user_id AND active = 1');
        $statement->execute(['role' => $role, 'workspace_id' => $workspaceId, 'user_id' => $memberId]);
        return $statement->rowCount() > 0;
    }

    public function remove(string $workspaceId, int $memberId): bool
    {
        $pdo = Connection::create();
        $current = $pdo->prepare('SELECT role FROM workspace_members WHERE workspace_id = :workspace_id AND user_id = :user_id AND active = 1');
        $current->execute(['workspace_id' => $workspaceId, 'user_id' => $memberId]);
        $member = $current->fetch(PDO::FETCH_ASSOC);
        if ($member === false) return false;
        if ($member['role'] === 'admin' && $this->activeAdmins($workspaceId) <= 1) throw new RuntimeException('Workspace must retain an admin');
        $statement = $pdo->prepare('UPDATE workspace_members SET active = 0 WHERE workspace_id = :workspace_id AND user_id = :user_id');
        $statement->execute(['workspace_id' => $workspaceId, 'user_id' => $memberId]);
        return $statement->rowCount() > 0;
    }

    private function activeAdmins(string $workspaceId): int
    {
        $statement = Connection::create()->prepare("SELECT COUNT(*) FROM workspace_members WHERE workspace_id = :workspace_id AND role = 'admin' AND active = 1");
        $statement->execute(['workspace_id' => $workspaceId]);
        return (int) $statement->fetchColumn();
    }
}
