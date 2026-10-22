<script setup>
import { computed, onMounted, ref, watch } from 'vue';
import { useWorkspaceStore } from './stores/workspace';
import { apiRequest } from './services/api';
import { createTask as createTaskRequest, fetchTasks, updateTaskStatus } from './services/tasks';
import { createInvitation, fetchInvitations, revokeInvitation } from './services/invitations';
import { createEvent, fetchEvents } from './services/events';
import { fetchMembers, removeMember, updateMemberRole } from './services/members';
import { decideAssignment, fetchPendingAssignments, proposeAssignment } from './services/assignments';
import { addChecklistItem, addTaskComment, fetchTaskDetail, updateChecklistItem } from './services/task-details';

const active = ref('Inicio');
const homeSection = ref('workspaces');
const composerOpen = ref(false);
const workspaceStore = useWorkspaceStore();
const authenticated = ref(Boolean(localStorage.getItem('nido_token')));
const authMode = ref('login');
const authError = ref('');
const authForm = ref({ name: '', email: '', password: '' });
const verificationToken = ref('');
const workspaceName = ref('');
const workspaceError = ref('');
const invitations = ref([]);
const invitationEmail = ref('');
const invitationRole = ref('member');
const invitationError = ref('');
const events = ref([]);
const eventTitle = ref('');
const eventStart = ref('');
const eventError = ref('');
const members = ref([]);
const memberError = ref('');
const assignments = ref([]);
const detailTask = ref(null);
const detailError = ref('');
const checklistTitle = ref('');
const commentBody = ref('');
const taskDueAt = ref('');
const calendarView = ref('week');
const calendarDate = ref('2026-10-22');
const tasks = ref([
    { title: 'Poner lavadora', time: '10:00', category: 'Hogar', color: 'blue', done: false },
    { title: 'Comprar regalo', time: '12:00', category: 'Personal', color: 'pink', done: false },
    { title: 'Llevar a Leo al médico', time: '17:30', category: 'Familia', color: 'violet', done: false },
    { title: 'Sacar la basura', time: '20:00', category: 'Hogar', color: 'green', done: false },
]);
const tabs = ['Inicio', 'Calendario', 'Tareas', 'Balance', 'Mi hogar'];
function mapTask(task) {
    const dueAt = task.due_at ? task.due_at.replace('T', ' ') : null;
    return { id: task.id, title: task.title, dueAt, time: dueAt ? dueAt.slice(8, 16) : 'Hoy', category: task.visibility === 'private' ? 'Personal' : 'Hogar', color: task.visibility === 'private' ? 'pink' : 'blue', done: task.status === 'completed' };
}
const selectedCalendarDate = computed(() => new Date(`${calendarDate.value}T12:00:00`));
function localDateIso(date) {
    return `${date.getFullYear()}-${String(date.getMonth() + 1).padStart(2, '0')}-${String(date.getDate()).padStart(2, '0')}`;
}
const calendarDays = computed(() => {
    const date = new Date(selectedCalendarDate.value);
    const offset = (date.getDay() + 6) % 7;
    date.setDate(date.getDate() - offset);
    return Array.from({ length: 7 }, (_, index) => {
        const day = new Date(date);
        day.setDate(date.getDate() + index);
        return { label: day.toLocaleDateString('es-ES', { weekday: 'short' }).slice(0, 2).toUpperCase(), number: day.getDate(), iso: localDateIso(day) };
    });
});
const monthDays = computed(() => {
    const first = new Date(selectedCalendarDate.value.getFullYear(), selectedCalendarDate.value.getMonth(), 1);
    const start = new Date(first);
    start.setDate(1 - ((first.getDay() + 6) % 7));
    return Array.from({ length: 42 }, (_, index) => { const day = new Date(start); day.setDate(start.getDate() + index); return { number: day.getDate(), iso: localDateIso(day), current: day.getMonth() === first.getMonth() }; });
});
const calendarEvents = computed(() => {
    const selected = selectedCalendarDate.value;
    const start = new Date(selected);
    const end = new Date(selected);
    if (calendarView.value === 'week') {
        const offset = (selected.getDay() + 6) % 7;
        start.setDate(selected.getDate() - offset);
        end.setDate(start.getDate() + 6);
    } else if (calendarView.value === 'month') {
        start.setDate(1);
        end.setMonth(selected.getMonth() + 1, 0);
    }
    const startIso = localDateIso(start);
    const endIso = localDateIso(end);
    return events.value.filter((event) => {
        const date = event.starts_at.slice(0, 10);
        return date >= startIso && date <= endIso;
    });
});

function moveCalendar(direction) {
    const date = new Date(selectedCalendarDate.value);
    date.setDate(date.getDate() + (calendarView.value === 'month' ? direction * 30 : direction * (calendarView.value === 'week' ? 7 : 1)));
    calendarDate.value = localDateIso(date);
}

function setCalendarView(view) {
    calendarView.value = view;
}

function formatEventTime(event) {
    const date = event.starts_at.replace('T', ' ');
    const day = `${date.slice(8, 10)}/${date.slice(5, 7)}`;
    const time = date.slice(11, 16);
    return calendarView.value === 'day' ? time : `${day} · ${time}`;
}

onMounted(() => workspaceStore.load());

watch(() => workspaceStore.selectedId, async (workspaceId) => {
    if (!workspaceId || !authenticated.value) return;
    try {
        const response = await fetchTasks(workspaceId);
        tasks.value = (response.data || []).map(mapTask);
    } catch {
        // La vista mantiene los datos locales si el workspace aún no tiene tareas disponibles.
    }
    if (workspaceStore.selectedWorkspace?.role === 'admin') {
        try {
            invitations.value = (await fetchInvitations(workspaceId)).data || [];
        } catch {
            invitations.value = [];
        }
    }
    try { events.value = (await fetchEvents(workspaceId)).data || []; } catch { events.value = []; }
    try { members.value = (await fetchMembers(workspaceId)).data || []; } catch { members.value = []; }
}, { immediate: true });

onMounted(async () => {
    if (authenticated.value) {
        try { assignments.value = (await fetchPendingAssignments()).data || []; } catch { assignments.value = []; }
    }
});

async function submitAuth() {
    authError.value = '';
    if (authMode.value === 'verify') {
        try {
            await apiRequest('/auth/verify-email', { method: 'POST', body: JSON.stringify({ token: verificationToken.value }) });
            authMode.value = 'login';
            authError.value = 'Email verificado. Ya puedes iniciar sesión.';
        } catch (error) { authError.value = error.message; }
        return;
    }
    try {
        const endpoint = authMode.value === 'login' ? '/auth/login' : '/auth/register';
        const response = await apiRequest(endpoint, { method: 'POST', body: JSON.stringify(authForm.value) });
        if (authMode.value === 'register') {
            authError.value = 'Cuenta creada. Verifica tu email antes de iniciar sesión.';
            authMode.value = 'login';
            return;
        }
        localStorage.setItem('nido_token', response.data.token);
        authenticated.value = true;
        await workspaceStore.load();
    } catch (error) {
        authError.value = error.message;
    }
}

function logout() {
    localStorage.removeItem('nido_token');
    localStorage.removeItem('nido_workspace_id');
    authenticated.value = false;
    workspaceStore.workspaces = [];
}

async function createWorkspace() {
    workspaceError.value = '';
    if (!workspaceName.value.trim()) return;
    try {
        const response = await apiRequest('/workspaces', { method: 'POST', body: JSON.stringify({ name: workspaceName.value }) });
        await workspaceStore.load();
        workspaceStore.select(response.data.id);
        workspaceName.value = '';
    } catch (error) {
        workspaceError.value = error.message;
    }
}

async function createTask(title) {
    if (!title.trim()) return;
    if (workspaceStore.selectedId) {
        try {
            await createTaskRequest(workspaceStore.selectedId, title.trim(), 'shared', taskDueAt.value);
            await fetchTasks(workspaceStore.selectedId).then((response) => {
                tasks.value = (response.data || []).map(mapTask);
            });
            composerOpen.value = false;
            taskDueAt.value = '';
            return;
        } catch {
            // Fallback local para permitir seguir usando la interfaz durante el desarrollo.
        }
    }
    if (!title.trim()) return;
    tasks.value.unshift({ title: title.trim(), time: 'Hoy', category: 'Hogar', color: 'blue', done: false });
    composerOpen.value = false;
    taskDueAt.value = '';
}

async function sendInvitation() {
    invitationError.value = '';
    if (!invitationEmail.value.trim() || !workspaceStore.selectedId) return;
    try {
        const response = await createInvitation(workspaceStore.selectedId, invitationEmail.value, invitationRole.value);
        invitations.value.unshift(response.data);
        invitationEmail.value = '';
    } catch (error) {
        invitationError.value = error.message;
    }
}

async function cancelInvitation(invitation) {
    await revokeInvitation(workspaceStore.selectedId, invitation.id);
    invitation.status = 'revoked';
}

async function addEvent() {
    eventError.value = '';
    if (!eventTitle.value.trim() || !eventStart.value || !workspaceStore.selectedId) return;
    try {
        const response = await createEvent(workspaceStore.selectedId, { title: eventTitle.value.trim(), starts_at: eventStart.value.replace('T', ' '), visibility: 'shared' });
        events.value.push(response.data);
        eventTitle.value = '';
        eventStart.value = '';
    } catch (error) { eventError.value = error.message; }
}

async function changeMemberRole(member, role) {
    memberError.value = '';
    try { await updateMemberRole(workspaceStore.selectedId, member.id, role); member.role = role; } catch (error) { memberError.value = error.message; }
}

async function kickMember(member) {
    memberError.value = '';
    try { await removeMember(workspaceStore.selectedId, member.id); member.active = 0; } catch (error) { memberError.value = error.message; }
}

async function decideAssignmentRequest(assignment, decision) {
    await decideAssignment(assignment.id, decision);
    assignments.value = assignments.value.filter((item) => item.id !== assignment.id);
}

async function assignTask(task, event) {
    if (!workspaceStore.selectedId || !task.id || !event.target.value) return;
    try { await proposeAssignment(workspaceStore.selectedId, task.id, Number(event.target.value)); event.target.value = ''; } catch (error) { memberError.value = error.message; }
}

async function openTask(task) {
    if (!workspaceStore.selectedId || !task.id) return;
    detailError.value = '';
    try { detailTask.value = (await fetchTaskDetail(workspaceStore.selectedId, task.id)).data; } catch (error) { detailError.value = error.message; }
}

async function addChecklist() {
    if (!checklistTitle.value.trim()) return;
    const item = (await addChecklistItem(workspaceStore.selectedId, detailTask.value.id, checklistTitle.value.trim())).data;
    detailTask.value.checklist.push(item); checklistTitle.value = '';
}

async function toggleChecklist(item) {
    await updateChecklistItem(workspaceStore.selectedId, detailTask.value.id, item.id, !item.completed);
    item.completed = item.completed ? 0 : 1;
}

async function addComment() {
    if (!commentBody.value.trim()) return;
    const comment = (await addTaskComment(workspaceStore.selectedId, detailTask.value.id, commentBody.value.trim())).data;
    detailTask.value.comments.push(comment); commentBody.value = '';
}

async function toggleTask(task) {
    const nextStatus = task.done ? 'pending' : 'completed';
    task.done = !task.done;
    if (!workspaceStore.selectedId || !task.id) return;
    try {
        await updateTaskStatus(workspaceStore.selectedId, task.id, nextStatus);
    } catch {
        task.done = !task.done;
    }
}

</script>

<template>
    <div v-if="!authenticated" class="auth-background">
        <main class="auth-card">
            <div class="auth-logo">⌂</div><p class="eyebrow">Nuestro hogar</p>
            <h1>{{ authMode === 'login' ? 'Bienvenido de nuevo' : authMode === 'register' ? 'Crea tu cuenta' : 'Verifica tu email' }}</h1>
            <p class="auth-description">Organiza, comparte y equilibra tu hogar.</p>
            <form class="auth-form" @submit.prevent="submitAuth">
                <label v-if="authMode === 'verify'">Token de verificación<input v-model="verificationToken" required placeholder="Pega aquí tu token"></label>
                <label v-if="authMode === 'register'">Nombre<input v-model="authForm.name" required autocomplete="name" placeholder="Tu nombre"></label>
                <label v-if="authMode !== 'verify'">Email<input v-model="authForm.email" required type="email" autocomplete="email" placeholder="tu@email.com"></label>
                <label v-if="authMode !== 'verify'">Contraseña<input v-model="authForm.password" required minlength="8" type="password" autocomplete="current-password" placeholder="Mínimo 8 caracteres"></label>
                <p v-if="authError" class="auth-error">{{ authError }}</p><button class="primary-button" type="submit">{{ authMode === 'login' ? 'Entrar' : authMode === 'verify' ? 'Verificar email' : 'Crear cuenta' }}</button>
            </form>
            <button class="auth-switch" @click="authMode = authMode === 'login' ? 'register' : 'login'">{{ authMode === 'login' ? '¿Aún no tienes cuenta? Regístrate' : 'Volver al login' }}</button><button v-if="authMode === 'login'" class="auth-switch" @click="authMode = 'verify'">Tengo un token de verificación</button>
        </main>
    </div>
    <div v-else class="app-background">
        <main class="app-shell">
            <header class="topbar">
                <div><select v-if="workspaceStore.workspaces.length" v-model="workspaceStore.selectedId" class="workspace-select" @change="workspaceStore.select(workspaceStore.selectedId)"><option v-for="workspace in workspaceStore.workspaces" :key="workspace.id" :value="workspace.id">{{ workspace.name }}</option></select><p v-else class="eyebrow">Nuestro hogar</p><h1>¡Buenos días, Alex!</h1><p class="date-label">Jueves, 22 de octubre</p></div>
                <div class="header-actions"><button class="icon-button">♧<span>2</span></button><button class="avatar avatar-blue" aria-label="Cerrar sesión" @click="logout">A</button></div>
            </header>
            <section v-if="active === 'Inicio'" class="summary-grid">
                <article class="summary-card summary-blue"><strong>{{ tasks.length }}</strong><span>Tareas hoy</span></article>
                <article class="summary-card summary-green"><strong>{{ tasks.filter((task) => task.done).length }}</strong><span>Hechas</span></article>
                <article class="summary-card summary-pink"><strong>1</strong><span>Solicitudes</span></article>
            </section>
            <section v-if="active === 'Inicio'" class="section-block"><div class="section-heading"><h2>Próximas tareas</h2><button class="text-button">Ver todas ›</button></div>
                <div class="task-list"><article v-for="task in tasks" :key="task.title" class="task-card" :class="{ 'task-done': task.done }">
                    <button class="check-button" :class="{ checked: task.done }" @click="toggleTask(task)">{{ task.done ? '✓' : '' }}</button>
                    <div class="task-icon" :class="`task-${task.color}`">▣</div><div class="task-content"><strong>{{ task.title }}</strong><span>{{ task.time }} · {{ task.category }}</span></div><div class="avatar avatar-pink">M</div>
                </article></div>
            </section>
            <section v-if="active === 'Inicio' && assignments.length" class="request-card"><div class="request-icon">✉</div><div><strong>Tienes {{ assignments.length }} solicitud{{ assignments.length === 1 ? '' : 'es' }}</strong><p>{{ assignments[0].proposed_by }} quiere asignarte<br>“{{ assignments[0].title }}”.</p><div class="assignment-actions"><button @click="decideAssignmentRequest(assignments[0], 'accepted')">Aceptar</button><button @click="decideAssignmentRequest(assignments[0], 'rejected')">Rechazar</button></div></div></section>
            <section v-if="active === 'Inicio'" class="activity-card"><div class="section-heading"><h2>Actividad del hogar</h2><button class="text-button">Ver más ›</button></div><p>🟣 María ha completado “Comprar regalo”.</p><p>🟢 Leo ha aceptado una tarea.</p></section>
            <section v-if="active === 'Calendario'" class="content-view"><div class="section-heading"><h2>{{ selectedCalendarDate.toLocaleDateString('es-ES', { month: 'long', year: 'numeric' }) }}</h2><div class="calendar-controls"><button @click="moveCalendar(-1)">‹</button><button @click="moveCalendar(1)">›</button></div></div><div class="calendar-views"><button :class="{ selected: calendarView === 'day' }" @click="calendarView = 'day'">Día</button><button :class="{ selected: calendarView === 'week' }" @click="calendarView = 'week'">Semana</button><button :class="{ selected: calendarView === 'month' }" @click="calendarView = 'month'">Mes</button></div><div v-if="calendarView !== 'month'" class="calendar-strip"><span v-for="day in calendarDays" :key="day.iso">{{ day.label }}</span><strong v-for="day in calendarDays" :key="`number-${day.iso}`" :class="{ today: day.iso === calendarDate }" @click="calendarDate = day.iso">{{ day.number }}</strong></div><div v-else class="month-grid"><button v-for="day in monthDays" :key="day.iso" :class="{ muted: !day.current, today: day.iso === calendarDate }" @click="calendarDate = day.iso">{{ day.number }}</button></div><h3>{{ selectedCalendarDate.toLocaleDateString('es-ES', { weekday: 'long', day: 'numeric', month: 'long' }) }}</h3><article v-for="event in calendarEvents" :key="`event-${event.id}`" class="calendar-task event-task"><time>{{ formatEventTime(event) }}</time><div><strong>{{ event.title }}</strong><span>Cita compartida</span></div></article><p v-if="!calendarEvents.length" class="calendar-empty">No hay citas para este día.</p><form class="event-create" @submit.prevent="addEvent"><input v-model="eventTitle" required placeholder="Nueva cita"><input v-model="eventStart" required type="datetime-local"><button class="primary-button">Añadir al calendario</button><p v-if="eventError" class="auth-error">{{ eventError }}</p></form></section>
            <section v-if="active === 'Tareas'" class="content-view"><div class="section-heading"><h2>Tareas</h2><button class="text-button">Filtrar</button></div><div class="task-filters"><span class="selected-filter">Todas</span><span>Compartidas</span><span>Privadas</span></div><div class="task-list"><article v-for="task in tasks" :key="`all-${task.title}`" class="task-card"><button class="check-button" :class="{ checked: task.done }" @click="toggleTask(task)">{{ task.done ? '✓' : '' }}</button><div class="task-icon" :class="`task-${task.color}`">▣</div><div class="task-content"><strong>{{ task.title }}</strong><span>{{ task.time }} · {{ task.category }}</span></div><select v-if="task.id && members.length" class="task-assign-select" @change="assignTask(task, $event)"><option value="">Asignar</option><option v-for="member in members" :key="member.id" :value="member.id">{{ member.name }}</option></select></article></div></section>
            <section v-if="active === 'Balance'" class="content-view"><div class="section-heading"><h2>Balance</h2><button class="text-button">Semana⌄</button></div><div class="balance-card"><h3>Tiempo por persona</h3><div class="balance-row"><strong>Alex</strong><div><i style="width: 78%"></i></div><span>12 h</span></div><div class="balance-row"><strong>María</strong><div><i class="pink-bar" style="width: 62%"></i></div><span>10 h</span></div><div class="balance-row"><strong>Leo</strong><div><i class="orange-bar" style="width: 34%"></i></div><span>5 h</span></div></div><div class="balance-card"><h3>Tareas completadas</h3><strong class="balance-number">{{ tasks.filter((task) => task.done).length }} / {{ tasks.length }}</strong><p>El progreso del hogar se actualizará al completar tareas.</p></div></section>
            <section v-if="active === 'Mi hogar'" class="empty-view"><div class="empty-icon">♧</div><h2>Mi hogar</h2><div class="home-tabs"><button :class="{ selected: homeSection === 'workspaces' }" @click="homeSection = 'workspaces'">Hogares</button><button :class="{ selected: homeSection === 'invitations' }" @click="homeSection = 'invitations'">Invitaciones</button></div><template v-if="homeSection === 'workspaces'"><p v-if="!workspaceStore.workspaces.length">Crea tu primer hogar para empezar a organizarte.</p><button v-for="workspace in workspaceStore.workspaces" :key="workspace.id" class="workspace-row" @click="workspaceStore.select(workspace.id)">{{ workspace.name }} <span>{{ workspace.role }}</span></button><form class="workspace-create" @submit.prevent="createWorkspace"><input v-model="workspaceName" placeholder="Nombre del nuevo hogar"><button class="primary-button">Crear hogar</button><p v-if="workspaceError" class="auth-error">{{ workspaceError }}</p></form></template><div v-if="homeSection === 'members'" class="members-panel"><h3>Miembros del hogar</h3><p v-if="memberError" class="auth-error">{{ memberError }}</p><div v-for="member in members.filter((item) => item.active)" :key="member.id" class="member-row"><span><strong>{{ member.name }}</strong><small>{{ member.email }}</small></span><select :value="member.role" :disabled="workspaceStore.selectedWorkspace?.role !== 'admin'" @change="changeMemberRole(member, $event.target.value)"><option value="admin">Admin</option><option value="member">Miembro</option><option value="guest">Invitado</option></select><button v-if="workspaceStore.selectedWorkspace?.role === 'admin'" @click="kickMember(member)">Expulsar</button></div></div><div v-if="homeSection === 'invitations' && workspaceStore.selectedWorkspace?.role === 'admin'" class="invitation-panel"><h3>Invitar a tu hogar</h3><form @submit.prevent="sendInvitation"><input v-model="invitationEmail" type="email" required placeholder="email@ejemplo.com"><select v-model="invitationRole"><option value="member">Miembro</option><option value="guest">Invitado</option><option value="admin">Administrador</option></select><button class="primary-button">Enviar invitación</button><p v-if="invitationError" class="auth-error">{{ invitationError }}</p></form><div v-for="invitation in invitations" :key="invitation.id || invitation.token" class="invitation-row"><span>{{ invitation.email }} · {{ invitation.role }}</span><button v-if="invitation.status === 'pending'" @click="cancelInvitation(invitation)">Revocar</button><small>{{ invitation.status }}</small></div></div><p v-if="homeSection === 'invitations' && workspaceStore.selectedWorkspace?.role !== 'admin'" class="calendar-empty">Solo los administradores pueden gestionar invitaciones.</p></section>
        </main>
        <div v-if="detailTask" class="modal-backdrop" @click.self="detailTask = null"><article class="task-detail"><button class="modal-close" @click="detailTask = null">×</button><h2>{{ detailTask.title }}</h2><p v-if="detailTask.instructions">{{ detailTask.instructions }}</p><h3>Checklist</h3><label v-for="item in detailTask.checklist" :key="item.id" class="checklist-item"><input type="checkbox" :checked="Boolean(item.completed)" @change="toggleChecklist(item)">{{ item.title }}</label><form @submit.prevent="addChecklist"><input v-model="checklistTitle" placeholder="Añadir elemento"><button class="primary-button">Añadir</button></form><h3>Comentarios</h3><p v-for="comment in detailTask.comments" :key="comment.id" class="task-comment"><strong>{{ comment.author }}</strong> {{ comment.body }}</p><form @submit.prevent="addComment"><input v-model="commentBody" placeholder="Escribe un comentario"><button class="primary-button">Comentar</button></form><p v-if="detailError" class="auth-error">{{ detailError }}</p></article></div>
        <button class="floating-action" aria-label="Crear tarea" @click="composerOpen = true">+</button>
        <nav class="bottom-nav"><button v-for="tab in tabs" :key="tab" class="nav-item" :class="{ active: active === tab }" @click="active = tab"><span class="nav-icon">{{ tab === 'Inicio' ? '⌂' : tab === 'Tareas' ? '✓' : tab === 'Balance' ? '◔' : tab === 'Calendario' ? '▣' : '♧' }}</span>{{ tab }}</button></nav>
        <div v-if="composerOpen" class="modal-backdrop" @click.self="composerOpen = false"><form class="task-composer" @submit.prevent="createTask($event.target.title.value)"><button type="button" class="modal-close" @click="composerOpen = false">×</button><h2>Nueva tarea</h2><p>Añade algo para {{ workspaceStore.selectedWorkspace?.name || 'Nuestro hogar' }}</p><input name="title" autofocus placeholder="¿Qué hay que hacer?"><label class="task-date-field">Fecha y hora<input v-model="taskDueAt" type="datetime-local"></label><div class="composer-options"><span>Compartida</span></div><button class="primary-button">Crear tarea</button></form></div>
    </div>
</template>
