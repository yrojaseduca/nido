<?php

declare(strict_types=1);

namespace App\Services;

use App\Database\Connection;
use PDO;
use RuntimeException;

final class EventService
{
    public function create(string $workspaceId, int $userId, string $title, string $startsAt, ?string $endsAt, string $visibility): array
    {
        if ($title === '' || !in_array($visibility, ['private', 'shared'], true)) {
            throw new RuntimeException('Invalid event data');
        }

        $pdo = Connection::create();
        $statement = $pdo->prepare('INSERT INTO workspace_events (workspace_id, created_by_user_id, title, starts_at, ends_at, visibility) VALUES (:workspace_id, :user_id, :title, :starts_at, :ends_at, :visibility)');
        $statement->execute(['workspace_id' => $workspaceId, 'user_id' => $userId, 'title' => $title, 'starts_at' => $startsAt, 'ends_at' => $endsAt, 'visibility' => $visibility]);

        return ['id' => (int) $pdo->lastInsertId(), 'title' => $title, 'starts_at' => $startsAt, 'ends_at' => $endsAt, 'visibility' => $visibility];
    }

    public function listForMember(string $workspaceId, int $userId): array
    {
        $statement = Connection::create()->prepare('SELECT id, title, description, starts_at, ends_at, visibility FROM workspace_events WHERE workspace_id = :workspace_id AND (visibility = \'shared\' OR created_by_user_id = :user_id) ORDER BY starts_at');
        $statement->execute(['workspace_id' => $workspaceId, 'user_id' => $userId]);

        return $statement->fetchAll(PDO::FETCH_ASSOC);
    }
}
