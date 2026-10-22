<?php

declare(strict_types=1);

namespace App\Services;

use App\Database\Connection;

final class WorkspaceService
{
    public function create(int $userId, string $name): array
    {
        $pdo = Connection::create();
        $workspaceId = strtoupper(bin2hex(random_bytes(13)));
        $workspaceId = substr($workspaceId, 0, 26);

        $pdo->beginTransaction();

        try {
            $workspace = $pdo->prepare(
                'INSERT INTO workspaces (id, name, owner_user_id) VALUES (:id, :name, :owner_user_id)'
            );
            $workspace->execute(['id' => $workspaceId, 'name' => $name, 'owner_user_id' => $userId]);

            $member = $pdo->prepare(
                'INSERT INTO workspace_members (workspace_id, user_id, role) VALUES (:workspace_id, :user_id, \'admin\')'
            );
            $member->execute(['workspace_id' => $workspaceId, 'user_id' => $userId]);
            $pdo->commit();
        } catch (\Throwable $exception) {
            $pdo->rollBack();

            throw $exception;
        }

        return ['id' => $workspaceId, 'name' => $name, 'role' => 'admin'];
    }
}
