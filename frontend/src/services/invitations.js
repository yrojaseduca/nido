import { apiRequest } from './api';

export function fetchInvitations(workspaceId) {
    return apiRequest(`/workspaces/${workspaceId}/invitations`);
}

export function createInvitation(workspaceId, email, role) {
    return apiRequest(`/workspaces/${workspaceId}/invitations`, { method: 'POST', body: JSON.stringify({ email, role }) });
}

export function revokeInvitation(workspaceId, invitationId) {
    return apiRequest(`/workspaces/${workspaceId}/invitations/${invitationId}`, { method: 'DELETE' });
}
