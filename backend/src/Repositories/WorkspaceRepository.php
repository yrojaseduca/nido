<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Database\Connection;
use PDO;

final class WorkspaceRepository
{
    public function findMember(string $workspaceId, int $userId): ?array
    {
        $statement = Connection::create()->prepare(
            'SELECT w.*, wm.role FROM workspaces w
             INNER JOIN workspace_members wm ON wm.workspace_id = w.id
             WHERE w.id = :workspace_id AND wm.user_id = :user_id
             AND w.active = 1 AND wm.active = 1'
        );
        $statement->execute(['workspace_id' => $workspaceId, 'user_id' => $userId]);
        $workspace = $statement->fetch(PDO::FETCH_ASSOC);

        return $workspace === false ? null : $workspace;
    }

    public function forUser(int $userId): array
    {
        $statement = Connection::create()->prepare(
            'SELECT w.id, w.name, wm.role FROM workspaces w
             INNER JOIN workspace_members wm ON wm.workspace_id = w.id
             WHERE wm.user_id = :user_id AND w.active = 1 AND wm.active = 1
             ORDER BY w.name'
        );
        $statement->execute(['user_id' => $userId]);

        return $statement->fetchAll(PDO::FETCH_ASSOC);
    }
}
