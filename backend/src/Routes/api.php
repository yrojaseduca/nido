<?php

declare(strict_types=1);

use App\Auth\SessionAuthenticator;
use App\Config\Config;
use App\Database\Connection;
use App\Http\JsonRequest;
use App\Http\JsonResponse;
use App\Repositories\WorkspaceRepository;
use App\Services\AuthService;
use App\Services\WorkspaceService;
use App\Services\TaskService;
use App\Services\InvitationService;
use App\Services\EventService;
use App\Services\MemberService;

return static function (Base $f3): void {
    $f3->route('GET /api/health', static function (): void {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['service' => 'nido', 'environment' => Config::get('APP_ENV', 'development')], JSON_THROW_ON_ERROR);
    });

    $f3->route('GET /api/health/database', static function (): void {
        Connection::create()->query('SELECT 1');
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['ok' => true], JSON_THROW_ON_ERROR);
    });

    $f3->route('GET /api/workspaces', static function (): void {
        $user = (new SessionAuthenticator())->authenticate((string) ($_SERVER['HTTP_AUTHORIZATION'] ?? ''));
        if ($user === null) {
            JsonResponse::send(['error' => 'Unauthenticated'], 401);

            return;
        }

        JsonResponse::send(['data' => (new WorkspaceRepository())->forUser((int) $user['id'])]);
    });

    $f3->route('POST /api/auth/register', static function (): void {
        $data = JsonRequest::body();

        try {
            $session = (new AuthService())->register((string) ($data['name'] ?? ''), (string) ($data['email'] ?? ''), (string) ($data['password'] ?? ''));
            JsonResponse::send(['data' => $session], 201);
        } catch (\Throwable) {
            JsonResponse::send(['error' => 'Invalid registration data'], 422);
        }
    });

    $f3->route('POST /api/auth/login', static function (): void {
        $data = JsonRequest::body();
        $session = (new AuthService())->login((string) ($data['email'] ?? ''), (string) ($data['password'] ?? ''));
        if ($session === null) {
            JsonResponse::send(['error' => 'Invalid credentials'], 401);

            return;
        }

        JsonResponse::send(['data' => $session]);
    });

    $f3->route('POST /api/auth/verify-email', static function (): void {
        $token = (string) (JsonRequest::body()['token'] ?? '');
        $verified = (new AuthService())->verifyEmail($token);
        JsonResponse::send(['data' => ['verified' => $verified]], $verified ? 200 : 422);
    });

    $f3->route('POST /api/auth/logout', static function (): void {
        (new SessionAuthenticator())->revoke((string) ($_SERVER['HTTP_AUTHORIZATION'] ?? ''));
        JsonResponse::send(['data' => ['logged_out' => true]]);
    });

    $f3->route('POST /api/workspaces', static function (): void {
        $user = (new SessionAuthenticator())->authenticate((string) ($_SERVER['HTTP_AUTHORIZATION'] ?? ''));
        if ($user === null) {
            JsonResponse::send(['error' => 'Unauthenticated'], 401);

            return;
        }

        $name = trim((string) (JsonRequest::body()['name'] ?? ''));
        if ($name === '') {
            JsonResponse::send(['error' => 'Workspace name is required'], 422);

            return;
        }

        JsonResponse::send(['data' => (new WorkspaceService())->create((int) $user['id'], $name)], 201);
    });

    $f3->route('GET /api/workspaces/@workspaceId', static function (Base $f3): void {
        $user = (new SessionAuthenticator())->authenticate((string) ($_SERVER['HTTP_AUTHORIZATION'] ?? ''));
        if ($user === null) {
            JsonResponse::send(['error' => 'Unauthenticated'], 401);

            return;
        }

        $workspace = (new WorkspaceRepository())->findMember((string) $f3->get('PARAMS.workspaceId'), (int) $user['id']);
        if ($workspace === null) {
            JsonResponse::send(['error' => 'Workspace not found'], 404);

            return;
        }

        JsonResponse::send(['data' => $workspace]);
    });

    $f3->route('GET /api/workspaces/@workspaceId/tasks', static function (Base $f3): void {
        $user = (new SessionAuthenticator())->authenticate((string) ($_SERVER['HTTP_AUTHORIZATION'] ?? ''));
        $workspaceId = (string) $f3->get('PARAMS.workspaceId');
        if ($user === null || (new WorkspaceRepository())->findMember($workspaceId, (int) ($user['id'] ?? 0)) === null) {
            JsonResponse::send(['error' => 'Workspace not found'], 404);
            return;
        }

        JsonResponse::send(['data' => (new TaskService())->listForMember($workspaceId, (int) $user['id'])]);
    });

    $f3->route('POST /api/workspaces/@workspaceId/tasks', static function (Base $f3): void {
        $user = (new SessionAuthenticator())->authenticate((string) ($_SERVER['HTTP_AUTHORIZATION'] ?? ''));
        $workspaceId = (string) $f3->get('PARAMS.workspaceId');
        if ($user === null || (new WorkspaceRepository())->findMember($workspaceId, (int) ($user['id'] ?? 0)) === null) {
            JsonResponse::send(['error' => 'Workspace not found'], 404);
            return;
        }

        $data = JsonRequest::body();
        try {
            $dueAt = isset($data['due_at']) && $data['due_at'] !== '' ? str_replace('T', ' ', (string) $data['due_at']) : null;
            $task = (new TaskService())->create($workspaceId, (int) $user['id'], trim((string) ($data['title'] ?? '')), (string) ($data['visibility'] ?? 'shared'), $dueAt);
            JsonResponse::send(['data' => $task], 201);
        } catch (\Throwable) {
            JsonResponse::send(['error' => 'Invalid task data'], 422);
        }
    });

    $f3->route('PATCH /api/workspaces/@workspaceId/tasks/@taskId', static function (Base $f3): void {
        $user = (new SessionAuthenticator())->authenticate((string) ($_SERVER['HTTP_AUTHORIZATION'] ?? ''));
        $workspaceId = (string) $f3->get('PARAMS.workspaceId');
        if ($user === null || (new WorkspaceRepository())->findMember($workspaceId, (int) ($user['id'] ?? 0)) === null) {
            JsonResponse::send(['error' => 'Workspace not found'], 404);
            return;
        }

        $status = (string) (JsonRequest::body()['status'] ?? '');
        $updated = (new TaskService())->updateStatus($workspaceId, (int) $f3->get('PARAMS.taskId'), (int) $user['id'], $status);
        if (!$updated) {
            JsonResponse::send(['error' => 'Task not found or not editable'], 404);
            return;
        }

        JsonResponse::send(['data' => ['status' => $status]]);
    });

    $f3->route('GET /api/workspaces/@workspaceId/invitations', static function (Base $f3): void {
        $user = (new SessionAuthenticator())->authenticate((string) ($_SERVER['HTTP_AUTHORIZATION'] ?? ''));
        $workspaceId = (string) $f3->get('PARAMS.workspaceId');
        $member = $user === null ? null : (new WorkspaceRepository())->findMember($workspaceId, (int) $user['id']);
        if ($member === null || $member['role'] !== 'admin') {
            JsonResponse::send(['error' => 'Forbidden'], $user === null ? 401 : 403);
            return;
        }

        JsonResponse::send(['data' => (new InvitationService())->list($workspaceId)]);
    });

    $f3->route('POST /api/workspaces/@workspaceId/invitations', static function (Base $f3): void {
        $user = (new SessionAuthenticator())->authenticate((string) ($_SERVER['HTTP_AUTHORIZATION'] ?? ''));
        $workspaceId = (string) $f3->get('PARAMS.workspaceId');
        $member = $user === null ? null : (new WorkspaceRepository())->findMember($workspaceId, (int) $user['id']);
        if ($member === null || $member['role'] !== 'admin') {
            JsonResponse::send(['error' => 'Forbidden'], $user === null ? 401 : 403);
            return;
        }

        $data = JsonRequest::body();
        try {
            JsonResponse::send(['data' => (new InvitationService())->create($workspaceId, (int) $user['id'], (string) ($data['email'] ?? ''), (string) ($data['role'] ?? 'member'))], 201);
        } catch (\Throwable) {
            JsonResponse::send(['error' => 'Invalid invitation data'], 422);
        }
    });

    $f3->route('DELETE /api/workspaces/@workspaceId/invitations/@invitationId', static function (Base $f3): void {
        $user = (new SessionAuthenticator())->authenticate((string) ($_SERVER['HTTP_AUTHORIZATION'] ?? ''));
        $workspaceId = (string) $f3->get('PARAMS.workspaceId');
        $member = $user === null ? null : (new WorkspaceRepository())->findMember($workspaceId, (int) $user['id']);
        if ($member === null || $member['role'] !== 'admin') {
            JsonResponse::send(['error' => 'Forbidden'], $user === null ? 401 : 403);
            return;
        }

        $revoked = (new InvitationService())->revoke($workspaceId, (int) $f3->get('PARAMS.invitationId'));
        JsonResponse::send(['data' => ['revoked' => $revoked]], $revoked ? 200 : 404);
    });

    $f3->route('POST /api/invitations/accept', static function (): void {
        $user = (new SessionAuthenticator())->authenticate((string) ($_SERVER['HTTP_AUTHORIZATION'] ?? ''));
        if ($user === null) {
            JsonResponse::send(['error' => 'Unauthenticated'], 401);
            return;
        }

        $data = JsonRequest::body();
        $accepted = (new InvitationService())->accept((string) ($data['token'] ?? ''), (int) $user['id'], (string) $user['email']);
        JsonResponse::send(['data' => ['accepted' => $accepted]], $accepted ? 200 : 404);
    });

    $f3->route('GET /api/workspaces/@workspaceId/events', static function (Base $f3): void {
        $user = (new SessionAuthenticator())->authenticate((string) ($_SERVER['HTTP_AUTHORIZATION'] ?? ''));
        $workspaceId = (string) $f3->get('PARAMS.workspaceId');
        if ($user === null || (new WorkspaceRepository())->findMember($workspaceId, (int) ($user['id'] ?? 0)) === null) {
            JsonResponse::send(['error' => 'Workspace not found'], 404);
            return;
        }

        JsonResponse::send(['data' => (new EventService())->listForMember($workspaceId, (int) $user['id'])]);
    });

    $f3->route('POST /api/workspaces/@workspaceId/events', static function (Base $f3): void {
        $user = (new SessionAuthenticator())->authenticate((string) ($_SERVER['HTTP_AUTHORIZATION'] ?? ''));
        $workspaceId = (string) $f3->get('PARAMS.workspaceId');
        if ($user === null || (new WorkspaceRepository())->findMember($workspaceId, (int) ($user['id'] ?? 0)) === null) {
            JsonResponse::send(['error' => 'Workspace not found'], 404);
            return;
        }

        $data = JsonRequest::body();
        try {
            $event = (new EventService())->create($workspaceId, (int) $user['id'], trim((string) ($data['title'] ?? '')), (string) ($data['starts_at'] ?? ''), isset($data['ends_at']) ? (string) $data['ends_at'] : null, (string) ($data['visibility'] ?? 'shared'));
            JsonResponse::send(['data' => $event], 201);
        } catch (\Throwable) {
            JsonResponse::send(['error' => 'Invalid event data'], 422);
        }
    });

    $f3->route('GET /api/workspaces/@workspaceId/members', static function (Base $f3): void {
        $user = (new SessionAuthenticator())->authenticate((string) ($_SERVER['HTTP_AUTHORIZATION'] ?? ''));
        $workspaceId = (string) $f3->get('PARAMS.workspaceId');
        if ($user === null || (new WorkspaceRepository())->findMember($workspaceId, (int) ($user['id'] ?? 0)) === null) { JsonResponse::send(['error' => 'Workspace not found'], 404); return; }
        JsonResponse::send(['data' => (new MemberService())->list($workspaceId)]);
    });

    $f3->route('PATCH /api/workspaces/@workspaceId/members/@memberId', static function (Base $f3): void {
        $user = (new SessionAuthenticator())->authenticate((string) ($_SERVER['HTTP_AUTHORIZATION'] ?? ''));
        $workspaceId = (string) $f3->get('PARAMS.workspaceId');
        $member = $user === null ? null : (new WorkspaceRepository())->findMember($workspaceId, (int) $user['id']);
        if ($member === null || $member['role'] !== 'admin') { JsonResponse::send(['error' => 'Forbidden'], $user === null ? 401 : 403); return; }
        try { $updated = (new MemberService())->changeRole($workspaceId, (int) $f3->get('PARAMS.memberId'), (string) (JsonRequest::body()['role'] ?? '')); JsonResponse::send(['data' => ['updated' => $updated]], $updated ? 200 : 404); } catch (\Throwable $exception) { JsonResponse::send(['error' => $exception->getMessage()], 422); }
    });

    $f3->route('DELETE /api/workspaces/@workspaceId/members/@memberId', static function (Base $f3): void {
        $user = (new SessionAuthenticator())->authenticate((string) ($_SERVER['HTTP_AUTHORIZATION'] ?? ''));
        $workspaceId = (string) $f3->get('PARAMS.workspaceId');
        $member = $user === null ? null : (new WorkspaceRepository())->findMember($workspaceId, (int) $user['id']);
        if ($member === null || $member['role'] !== 'admin') { JsonResponse::send(['error' => 'Forbidden'], $user === null ? 401 : 403); return; }
        try { $removed = (new MemberService())->remove($workspaceId, (int) $f3->get('PARAMS.memberId')); JsonResponse::send(['data' => ['removed' => $removed]], $removed ? 200 : 404); } catch (\Throwable $exception) { JsonResponse::send(['error' => $exception->getMessage()], 422); }
    });
};
