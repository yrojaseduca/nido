import { apiRequest } from './api';

export function fetchPendingAssignments() { return apiRequest('/assignments/pending'); }
export function decideAssignment(assignmentId, decision) { return apiRequest(`/assignments/${assignmentId}`, { method: 'PATCH', body: JSON.stringify({ decision }) }); }
export function proposeAssignment(workspaceId, taskId, assignedToUserId) { return apiRequest(`/workspaces/${workspaceId}/tasks/${taskId}/assignments`, { method: 'POST', body: JSON.stringify({ assigned_to_user_id: assignedToUserId }) }); }
