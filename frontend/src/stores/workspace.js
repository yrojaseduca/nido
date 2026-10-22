import { computed, ref } from 'vue';
import { defineStore } from 'pinia';
import { fetchWorkspaces } from '../services/api';

export const useWorkspaceStore = defineStore('workspace', () => {
    const workspaces = ref([]);
    const selectedId = ref(localStorage.getItem('nido_workspace_id'));
    const loading = ref(false);
    const error = ref(null);

    const selectedWorkspace = computed(() => workspaces.value.find((workspace) => workspace.id === selectedId.value) || null);

    async function load() {
        if (!localStorage.getItem('nido_token')) return;
        loading.value = true;
        error.value = null;
        try {
            const response = await fetchWorkspaces();
            workspaces.value = response.data || [];
            if (!selectedWorkspace.value && workspaces.value.length) select(workspaces.value[0].id);
        } catch (requestError) {
            error.value = requestError.message;
        } finally {
            loading.value = false;
        }
    }

    function select(workspaceId) {
        selectedId.value = workspaceId;
        localStorage.setItem('nido_workspace_id', workspaceId);
    }

    return { workspaces, selectedId, selectedWorkspace, loading, error, load, select };
});
