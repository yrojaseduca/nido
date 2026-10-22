import { apiRequest } from './api';

export function fetchEvents(workspaceId) { return apiRequest(`/workspaces/${workspaceId}/events`); }
export function createEvent(workspaceId, event) { return apiRequest(`/workspaces/${workspaceId}/events`, { method: 'POST', body: JSON.stringify(event) }); }
