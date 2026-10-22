<?php

declare(strict_types=1);

namespace App\Services;

use App\Database\Connection;
use PDO;
use RuntimeException;

final class TaskDetailService
{
    public function get(string $workspaceId, int $taskId, int $userId): ?array
    {
        $pdo = Connection::create();
        $task = $pdo->prepare('SELECT * FROM tasks WHERE id = :id AND workspace_id = :workspace_id AND (visibility = \'shared\' OR owner_user_id = :user_id)');
        $task->execute(['id' => $taskId, 'workspace_id' => $workspaceId, 'user_id' => $userId]);
        $result = $task->fetch(PDO::FETCH_ASSOC);
        if ($result === false) return null;
        $items = $pdo->prepare('SELECT id, title, completed, position FROM task_checklist_items WHERE task_id = :task_id AND workspace_id = :workspace_id ORDER BY position, id');
        $items->execute(['task_id' => $taskId, 'workspace_id' => $workspaceId]);
        $comments = $pdo->prepare('SELECT c.id, c.body, c.created_at, u.name AS author FROM task_comments c INNER JOIN users u ON u.id = c.user_id WHERE c.task_id = :task_id AND c.workspace_id = :workspace_id ORDER BY c.created_at');
        $comments->execute(['task_id' => $taskId, 'workspace_id' => $workspaceId]);
        $result['checklist'] = $items->fetchAll(PDO::FETCH_ASSOC);
        $result['comments'] = $comments->fetchAll(PDO::FETCH_ASSOC);
        return $result;
    }

    public function addChecklist(string $workspaceId, int $taskId, int $userId, string $title): array
    {
        if ($this->get($workspaceId, $taskId, $userId) === null || $title === '') throw new RuntimeException('Task not found');
        $pdo = Connection::create();
        $statement = $pdo->prepare('INSERT INTO task_checklist_items (task_id, workspace_id, title, position) SELECT :task_id, :workspace_id, :title, COALESCE(MAX(position) + 1, 0) FROM task_checklist_items WHERE task_id = :position_task');
        $statement->execute(['task_id' => $taskId, 'workspace_id' => $workspaceId, 'title' => $title, 'position_task' => $taskId]);
        return ['id' => (int) $pdo->lastInsertId(), 'title' => $title, 'completed' => 0];
    }

    public function addComment(string $workspaceId, int $taskId, int $userId, string $body): array
    {
        if ($this->get($workspaceId, $taskId, $userId) === null || $body === '') throw new RuntimeException('Task not found');
        $pdo = Connection::create();
        $statement = $pdo->prepare('INSERT INTO task_comments (task_id, workspace_id, user_id, body) VALUES (:task_id, :workspace_id, :user_id, :body)');
        $statement->execute(['task_id' => $taskId, 'workspace_id' => $workspaceId, 'user_id' => $userId, 'body' => $body]);
        return ['id' => (int) $pdo->lastInsertId(), 'body' => $body];
    }

    public function updateChecklist(string $workspaceId, int $taskId, int $itemId, int $userId, bool $completed): bool
    {
        if ($this->get($workspaceId, $taskId, $userId) === null) return false;
        $statement = Connection::create()->prepare('UPDATE task_checklist_items SET completed = :completed WHERE id = :id AND task_id = :task_id AND workspace_id = :workspace_id');
        $statement->execute(['completed' => $completed ? 1 : 0, 'id' => $itemId, 'task_id' => $taskId, 'workspace_id' => $workspaceId]);
        return $statement->rowCount() > 0;
    }
}
