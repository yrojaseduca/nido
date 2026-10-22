import { apiRequest } from './api';

export function fetchMembers(workspaceId) { return apiRequest(`/workspaces/${workspaceId}/members`); }
export function updateMemberRole(workspaceId, memberId, role) { return apiRequest(`/workspaces/${workspaceId}/members/${memberId}`, { method: 'PATCH', body: JSON.stringify({ role }) }); }
export function removeMember(workspaceId, memberId) { return apiRequest(`/workspaces/${workspaceId}/members/${memberId}`, { method: 'DELETE' }); }
