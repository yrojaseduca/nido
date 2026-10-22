<?php

declare(strict_types=1);

namespace App\Authorization;

use App\Repositories\WorkspaceRepository;

final class WorkspaceAccess
{
    public function __construct(private WorkspaceRepository $workspaces)
    {
    }

    public function member(string $workspaceId, int $userId): ?array
    {
        return $this->workspaces->findMember($workspaceId, $userId);
    }

    public function admin(string $workspaceId, int $userId): ?array
    {
        $member = $this->member($workspaceId, $userId);

        return $member !== null && $member['role'] === 'admin' ? $member : null;
    }
}
