import { apiRequest } from './api';

export function fetchTasks(workspaceId) {
    return apiRequest(`/workspaces/${workspaceId}/tasks`);
}

export function createTask(workspaceId, title, visibility = 'shared', dueAt = null) {
    return apiRequest(`/workspaces/${workspaceId}/tasks`, {
        method: 'POST',
        body: JSON.stringify({ title, visibility, due_at: dueAt }),
    });
}

export function updateTaskStatus(workspaceId, taskId, status) {
    return apiRequest(`/workspaces/${workspaceId}/tasks/${taskId}`, {
        method: 'PATCH',
        body: JSON.stringify({ status }),
    });
}
