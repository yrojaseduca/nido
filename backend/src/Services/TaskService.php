<?php

declare(strict_types=1);

namespace App\Services;

use App\Database\Connection;
use PDO;
use RuntimeException;

final class TaskService
{
    public function create(string $workspaceId, int $userId, string $title, string $visibility, ?string $dueAt = null): array
    {
        if ($title === '' || !in_array($visibility, ['private', 'shared'], true)) {
            throw new RuntimeException('Invalid task data');
        }

        $pdo = Connection::create();
        $statement = $pdo->prepare(
            'INSERT INTO tasks (workspace_id, owner_user_id, title, visibility, due_at)
             VALUES (:workspace_id, :owner_user_id, :title, :visibility, :due_at)'
        );
        $statement->execute([
            'workspace_id' => $workspaceId,
            'owner_user_id' => $userId,
            'title' => $title,
            'visibility' => $visibility,
            'due_at' => $dueAt,
        ]);

        return ['id' => (int) $pdo->lastInsertId(), 'workspace_id' => $workspaceId, 'title' => $title, 'visibility' => $visibility, 'status' => 'pending', 'due_at' => $dueAt];
    }

    public function listForMember(string $workspaceId, int $userId): array
    {
        $statement = Connection::create()->prepare(
            'SELECT id, workspace_id, owner_user_id, title, visibility, status, due_at, created_at
             FROM tasks
             WHERE workspace_id = :workspace_id AND (visibility = \'shared\' OR owner_user_id = :owner_user_id)
             ORDER BY status, due_at IS NULL, due_at, created_at DESC'
        );
        $statement->execute(['workspace_id' => $workspaceId, 'owner_user_id' => $userId]);

        return $statement->fetchAll(PDO::FETCH_ASSOC);
    }

    public function updateStatus(string $workspaceId, int $taskId, int $userId, string $status): bool
    {
        if (!in_array($status, ['pending', 'completed'], true)) {
            return false;
        }

        $statement = Connection::create()->prepare(
            'UPDATE tasks SET status = :status
             WHERE id = :id AND workspace_id = :workspace_id AND owner_user_id = :owner_user_id'
        );
        $statement->execute([
            'status' => $status,
            'id' => $taskId,
            'workspace_id' => $workspaceId,
            'owner_user_id' => $userId,
        ]);

        return $statement->rowCount() > 0;
    }
}
