import { apiRequest } from './api';

export function fetchTaskDetail(workspaceId, taskId) { return apiRequest(`/workspaces/${workspaceId}/tasks/${taskId}`); }
export function addChecklistItem(workspaceId, taskId, title) { return apiRequest(`/workspaces/${workspaceId}/tasks/${taskId}/checklist`, { method: 'POST', body: JSON.stringify({ title }) }); }
export function updateChecklistItem(workspaceId, taskId, itemId, completed) { return apiRequest(`/workspaces/${workspaceId}/tasks/${taskId}/checklist/${itemId}`, { method: 'PATCH', body: JSON.stringify({ completed }) }); }
export function addTaskComment(workspaceId, taskId, body) { return apiRequest(`/workspaces/${workspaceId}/tasks/${taskId}/comments`, { method: 'POST', body: JSON.stringify({ body }) }); }
