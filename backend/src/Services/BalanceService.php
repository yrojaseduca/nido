<?php

declare(strict_types=1);

namespace App\Services;

use App\Database\Connection;
use PDO;

final class BalanceService
{
    public function summary(string $workspaceId): array
    {
        $pdo = Connection::create();
        $time = $pdo->prepare("SELECT COALESCE(SUM(estimated_minutes), 0) AS estimated_minutes, COALESCE(SUM(actual_minutes), 0) AS actual_minutes FROM tasks WHERE workspace_id = :workspace_id AND visibility = 'shared' AND status <> 'archived'");
        $time->execute(['workspace_id' => $workspaceId]);
        $effort = $pdo->prepare("SELECT COALESCE(AVG(effort), 0) AS average_effort, COUNT(effort) AS rated_tasks FROM tasks WHERE workspace_id = :workspace_id AND visibility = 'shared' AND status <> 'archived'");
        $effort->execute(['workspace_id' => $workspaceId]);
        $people = $pdo->prepare("SELECT u.id, u.name, COALESCE(SUM(t.estimated_minutes), 0) AS estimated_minutes, COALESCE(SUM(t.actual_minutes), 0) AS actual_minutes, COALESCE(AVG(t.effort), 0) AS average_effort FROM workspace_members wm INNER JOIN users u ON u.id = wm.user_id LEFT JOIN tasks t ON t.workspace_id = wm.workspace_id AND t.owner_user_id = u.id AND t.visibility = 'shared' WHERE wm.workspace_id = :workspace_id AND wm.active = 1 GROUP BY u.id, u.name ORDER BY estimated_minutes DESC");
        $people->execute(['workspace_id' => $workspaceId]);
        return ['time' => $time->fetch(PDO::FETCH_ASSOC), 'effort' => $effort->fetch(PDO::FETCH_ASSOC), 'people' => $people->fetchAll(PDO::FETCH_ASSOC)];
    }
}
