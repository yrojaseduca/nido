<?php

declare(strict_types=1);

namespace App\Services;

use App\Database\Connection;
use PDO;
use RuntimeException;

final class AssignmentService
{
    public function propose(string $workspaceId, int $taskId, int $proposerId, int $assigneeId): array
    {
        $pdo = Connection::create();
        $check = $pdo->prepare("SELECT t.id FROM tasks t INNER JOIN workspace_members m ON m.workspace_id = t.workspace_id AND m.user_id = :assignee_id AND m.active = 1 WHERE t.id = :task_id AND t.workspace_id = :workspace_id AND t.owner_user_id = :proposer_id");
        $check->execute(['task_id' => $taskId, 'workspace_id' => $workspaceId, 'proposer_id' => $proposerId, 'assignee_id' => $assigneeId]);
        if ($check->fetchColumn() === false || $proposerId === $assigneeId) throw new RuntimeException('Invalid assignment');
        $statement = $pdo->prepare('INSERT INTO task_assignments (task_id, workspace_id, proposed_by_user_id, assigned_to_user_id) VALUES (:task_id, :workspace_id, :proposer_id, :assignee_id)');
        $statement->execute(['task_id' => $taskId, 'workspace_id' => $workspaceId, 'proposer_id' => $proposerId, 'assignee_id' => $assigneeId]);
        return ['id' => (int) $pdo->lastInsertId(), 'task_id' => $taskId, 'assigned_to_user_id' => $assigneeId, 'status' => 'pending'];
    }

    public function pendingForUser(int $userId): array
    {
        $statement = Connection::create()->prepare("SELECT a.id, a.task_id, a.workspace_id, t.title, u.name AS proposed_by, a.proposed_at FROM task_assignments a INNER JOIN tasks t ON t.id = a.task_id INNER JOIN users u ON u.id = a.proposed_by_user_id WHERE a.assigned_to_user_id = :user_id AND a.status = 'pending' ORDER BY a.proposed_at DESC");
        $statement->execute(['user_id' => $userId]);
        return $statement->fetchAll(PDO::FETCH_ASSOC);
    }

    public function decide(int $assignmentId, int $userId, string $decision): bool
    {
        if (!in_array($decision, ['accepted', 'rejected'], true)) return false;
        $statement = Connection::create()->prepare('UPDATE task_assignments SET status = :status, decided_at = CURRENT_TIMESTAMP WHERE id = :id AND assigned_to_user_id = :user_id AND status = \'pending\'');
        $statement->execute(['status' => $decision, 'id' => $assignmentId, 'user_id' => $userId]);
        return $statement->rowCount() > 0;
    }
}
