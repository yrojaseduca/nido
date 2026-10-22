const API_BASE = '/api';

export async function apiRequest(path, options = {}) {
    const token = localStorage.getItem('nido_token');
    const headers = {
        Accept: 'application/json',
        ...(options.body ? { 'Content-Type': 'application/json' } : {}),
        ...(token ? { Authorization: `Bearer ${token}` } : {}),
        ...options.headers,
    };
    const response = await fetch(`${API_BASE}${path}`, { ...options, headers });
    const payload = await response.json().catch(() => ({}));
    if (!response.ok) throw new Error(payload.error || 'No se pudo completar la petición');
    return payload;
}

export function fetchWorkspaces() {
    return apiRequest('/workspaces');
}
