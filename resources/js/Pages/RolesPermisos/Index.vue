<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm, usePage } from '@inertiajs/vue3';
import { ref, computed, watch } from 'vue';

const props = defineProps({
    rolesData: Array,      // [{ id, name, permissions: ['ver_pagos', ...] }]
    gruposPermisos: Array, // [{ grupo: 'Pagos', permisos: [{ name, etiqueta }] }]
});

const page = usePage();
const flash = computed(() => page.props.flash ?? {});

// ── Estado local ─────────────────────────────────────────────────────────────
const activeTab    = ref(0);
const saving       = ref(false);
const snackbar     = ref(false);
const snackMsg     = ref('');
const snackColor   = ref('success');

// Clonamos el estado de permisos para edición local (sin mutarlo directamente desde el prop)
const localPerms = ref({});

const initLocal = () => {
    const map = {};
    props.rolesData.forEach(role => {
        map[role.id] = [...role.permissions];
    });
    localPerms.value = map;
};
initLocal();

// Observar cambios en flash (respuestas del servidor)
watch(() => page.props.flash, (f) => {
    if (f?.success) { snackMsg.value = f.success; snackColor.value = 'success'; snackbar.value = true; }
    if (f?.error)   { snackMsg.value = f.error;   snackColor.value = 'error';   snackbar.value = true; }
}, { deep: true });

// ── Helpers ───────────────────────────────────────────────────────────────────
const roleActivo = computed(() => props.rolesData[activeTab.value]);

const tienePermiso = (permisoName) => {
    if (!roleActivo.value) return false;
    return localPerms.value[roleActivo.value.id]?.includes(permisoName) ?? false;
};

const togglePermiso = (permisoName) => {
    const id = roleActivo.value?.id;
    if (!id) return;
    const arr = localPerms.value[id];
    const idx = arr.indexOf(permisoName);
    if (idx === -1) arr.push(permisoName);
    else arr.splice(idx, 1);
};

// Calcular si todos los permisos de un grupo están activos
const grupoCompleto = (grupo) => {
    return grupo.permisos.every(p => tienePermiso(p.name));
};

// Calcular si algunos (pero no todos) están activos
const grupoIndeterminate = (grupo) => {
    const total   = grupo.permisos.length;
    const activos = grupo.permisos.filter(p => tienePermiso(p.name)).length;
    return activos > 0 && activos < total;
};

// Toggle todo un grupo
const toggleGrupo = (grupo) => {
    const id = roleActivo.value?.id;
    if (!id) return;
    const arr  = localPerms.value[id];
    const todos = grupoCompleto(grupo);
    grupo.permisos.forEach(p => {
        const idx = arr.indexOf(p.name);
        if (todos) {
            if (idx !== -1) arr.splice(idx, 1); // quitar todos
        } else {
            if (idx === -1) arr.push(p.name);   // agregar faltantes
        }
    });
};

// Cuántos permisos activos tiene el rol actual
const totalActivos = computed(() => {
    if (!roleActivo.value) return 0;
    return localPerms.value[roleActivo.value.id]?.length ?? 0;
});

const totalPermisos = computed(() =>
    props.gruposPermisos.reduce((acc, g) => acc + g.permisos.length, 0)
);

// ── Guardar ───────────────────────────────────────────────────────────────────
const form = useForm({});

const guardar = () => {
    const id   = roleActivo.value?.id;
    const perms = localPerms.value[id] ?? [];

    saving.value = true;
    form.transform(() => ({ permissions: perms }))
        .post(route('roles_permisos.update', id), {
            onFinish: () => { saving.value = false; },
        });
};

// Color por nombre de rol
const colorRol = (name) => {
    const map = {
        Administrador: 'deep-purple',
        Tutoria:       'teal',
        Admisiones:    'blue-darken-2',
        Tutor:         'orange',
        Alumno:        'grey-darken-1',
    };
    return map[name] ?? 'indigo';
};

const iconoRol = (name) => {
    const map = {
        Administrador: 'mdi-shield-crown',
        Tutoria:       'mdi-account-heart',
        Admisiones:    'mdi-account-check',
        Tutor:         'mdi-school',
        Alumno:        'mdi-account-student',
    };
    return map[name] ?? 'mdi-account-cog';
};

const iconoGrupo = (grupo) => {
    const map = {
        'Datos Maestros': 'mdi-database-cog',
        'Alumnos':        'mdi-account-group',
        'Pagos':          'mdi-cash-multiple',
        'Tutoría':        'mdi-heart-pulse',
        'Admisiones':     'mdi-clipboard-account',
        'Administración': 'mdi-shield-lock',
    };
    return map[grupo] ?? 'mdi-tag-multiple';
};
</script>

<template>
    <Head title="Roles y Permisos" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center gap-3">
                <v-icon color="deep-purple-darken-2" size="28">mdi-shield-account</v-icon>
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                    Gestión de Roles y Permisos
                </h2>
            </div>
        </template>

        <div class="rp-page">

            <!-- ── CABECERA INFORMATIVA ───────────────────────────────────── -->
            <div class="rp-header-card">
                <div class="rp-header-left">
                    <div class="rp-header-icon">
                        <v-icon size="32" color="white">mdi-lock-check</v-icon>
                    </div>
                    <div>
                        <p class="rp-header-title">Control de acceso por roles</p>
                        <p class="rp-header-sub">
                            Selecciona un rol y activa o desactiva permisos individuales.
                            El rol <strong>TI</strong> tiene acceso total y no es modificable desde aquí.
                        </p>
                    </div>
                </div>
                <div class="rp-header-stat">
                    <span class="rp-stat-num">{{ props.rolesData.length }}</span>
                    <span class="rp-stat-label">roles configurables</span>
                </div>
            </div>

            <!-- ── CONTENEDOR PRINCIPAL ───────────────────────────────────── -->
            <div class="rp-main">

                <!-- Tabs de roles (lateral) -->
                <div class="rp-sidebar">
                    <p class="rp-sidebar-title">Roles del sistema</p>
                    <div
                        v-for="(role, idx) in rolesData"
                        :key="role.id"
                        class="rp-role-card"
                        :class="{ 'rp-role-card--active': activeTab === idx }"
                        @click="activeTab = idx"
                    >
                        <v-icon :color="colorRol(role.name)" size="20">{{ iconoRol(role.name) }}</v-icon>
                        <div class="rp-role-info">
                            <span class="rp-role-name">{{ role.name }}</span>
                            <span class="rp-role-perms">
                                {{ localPerms[role.id]?.length ?? 0 }} permisos activos
                            </span>
                        </div>
                        <v-icon size="16" class="rp-role-arrow">mdi-chevron-right</v-icon>
                    </div>
                </div>

                <!-- Panel de permisos -->
                <div class="rp-content" v-if="roleActivo">

                    <!-- Encabezado del rol -->
                    <div class="rp-content-header">
                        <div class="flex items-center gap-3">
                            <v-icon :color="colorRol(roleActivo.name)" size="28">
                                {{ iconoRol(roleActivo.name) }}
                            </v-icon>
                            <div>
                                <h3 class="rp-content-title">{{ roleActivo.name }}</h3>
                                <span class="rp-content-sub">
                                    {{ totalActivos }} de {{ totalPermisos }} permisos habilitados
                                </span>
                            </div>
                        </div>

                        <!-- Barra de progreso -->
                        <v-progress-linear
                            :model-value="(totalActivos / totalPermisos) * 100"
                            :color="colorRol(roleActivo.name)"
                            height="6"
                            rounded
                            class="rp-progress"
                        />
                    </div>

                    <!-- Grupos de permisos -->
                    <div class="rp-groups">
                        <div
                            v-for="grupo in gruposPermisos"
                            :key="grupo.grupo"
                            class="rp-group"
                        >
                            <!-- Cabecera del grupo -->
                            <div class="rp-group-header" @click="toggleGrupo(grupo)">
                                <div class="flex items-center gap-2">
                                    <v-icon size="18" color="deep-purple-darken-2">
                                        {{ iconoGrupo(grupo.grupo) }}
                                    </v-icon>
                                    <span class="rp-group-title">{{ grupo.grupo }}</span>
                                    <v-chip size="x-small" color="deep-purple-lighten-4" text-color="deep-purple-darken-3" class="ml-1">
                                        {{ grupo.permisos.filter(p => tienePermiso(p.name)).length }}/{{ grupo.permisos.length }}
                                    </v-chip>
                                </div>
                                <v-checkbox
                                    :model-value="grupoCompleto(grupo)"
                                    :indeterminate="grupoIndeterminate(grupo)"
                                    color="deep-purple-darken-2"
                                    hide-details
                                    density="compact"
                                    @click.stop="toggleGrupo(grupo)"
                                />
                            </div>

                            <!-- Permisos individuales -->
                            <div class="rp-perms-grid">
                                <div
                                    v-for="permiso in grupo.permisos"
                                    :key="permiso.name"
                                    class="rp-perm-item"
                                    :class="{ 'rp-perm-item--active': tienePermiso(permiso.name) }"
                                    @click="togglePermiso(permiso.name)"
                                >
                                    <div class="rp-perm-left">
                                        <div class="rp-perm-dot" :class="{ 'rp-perm-dot--on': tienePermiso(permiso.name) }"></div>
                                        <span class="rp-perm-label">{{ permiso.etiqueta }}</span>
                                    </div>
                                    <v-switch
                                        :model-value="tienePermiso(permiso.name)"
                                        color="deep-purple-darken-2"
                                        hide-details
                                        density="compact"
                                        @click.stop="togglePermiso(permiso.name)"
                                    />
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Botón guardar -->
                    <div class="rp-footer">
                        <v-btn
                            color="deep-purple-darken-2"
                            variant="flat"
                            size="large"
                            :loading="saving"
                            prepend-icon="mdi-content-save-check"
                            class="rp-save-btn"
                            @click="guardar"
                        >
                            Guardar permisos de {{ roleActivo.name }}
                        </v-btn>
                    </div>
                </div>
            </div>
        </div>

        <!-- Snackbar de respuesta -->
        <v-snackbar
            v-model="snackbar"
            :color="snackColor"
            location="bottom right"
            :timeout="3500"
            rounded="xl"
        >
            <v-icon class="mr-2">{{ snackColor === 'success' ? 'mdi-check-circle' : 'mdi-alert-circle' }}</v-icon>
            {{ snackMsg }}
        </v-snackbar>

    </AuthenticatedLayout>
</template>

<style scoped>
/* ── Página ── */
.rp-page {
    padding: 32px 24px;
    max-width: 1200px;
    margin: 0 auto;
    display: flex;
    flex-direction: column;
    gap: 24px;
}

/* ── Header Card ── */
.rp-header-card {
    background: linear-gradient(135deg, #5c35c9 0%, #3949ab 100%);
    border-radius: 20px;
    padding: 24px 32px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    color: white;
    box-shadow: 0 8px 32px rgba(92, 53, 201, 0.25);
}
.rp-header-left {
    display: flex;
    align-items: flex-start;
    gap: 20px;
}
.rp-header-icon {
    background: rgba(255,255,255,0.15);
    border-radius: 14px;
    padding: 12px;
    backdrop-filter: blur(10px);
}
.rp-header-title {
    font-size: 1.15rem;
    font-weight: 700;
    margin-bottom: 4px;
}
.rp-header-sub {
    font-size: 0.85rem;
    opacity: 0.85;
    max-width: 480px;
    line-height: 1.5;
}
.rp-header-stat {
    text-align: center;
    background: rgba(255,255,255,0.12);
    border-radius: 14px;
    padding: 16px 24px;
}
.rp-stat-num {
    display: block;
    font-size: 2.5rem;
    font-weight: 800;
    line-height: 1;
}
.rp-stat-label {
    font-size: 0.75rem;
    opacity: 0.8;
    text-transform: uppercase;
    letter-spacing: 0.05em;
}

/* ── Main layout ── */
.rp-main {
    display: flex;
    gap: 20px;
    align-items: flex-start;
}

/* ── Sidebar roles ── */
.rp-sidebar {
    width: 220px;
    flex-shrink: 0;
}
.rp-sidebar-title {
    font-size: 0.72rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.08em;
    color: #9e9e9e;
    margin-bottom: 10px;
    padding-left: 4px;
}
.rp-role-card {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 12px 14px;
    border-radius: 14px;
    cursor: pointer;
    margin-bottom: 6px;
    transition: all 0.2s ease;
    background: #f8f9ff;
    border: 1.5px solid transparent;
}
.rp-role-card:hover {
    background: #ede7f6;
    border-color: #b39ddb;
}
.rp-role-card--active {
    background: #ede7f6;
    border-color: #7c3aed;
    box-shadow: 0 2px 12px rgba(124, 58, 237, 0.15);
}
.rp-role-info {
    flex: 1;
    display: flex;
    flex-direction: column;
}
.rp-role-name {
    font-size: 0.88rem;
    font-weight: 600;
    color: #212121;
}
.rp-role-perms {
    font-size: 0.72rem;
    color: #9e9e9e;
}
.rp-role-arrow {
    color: #bdbdbd;
    transition: transform 0.2s;
}
.rp-role-card--active .rp-role-arrow {
    transform: translateX(2px);
    color: #7c3aed;
}

/* ── Contenido de permisos ── */
.rp-content {
    flex: 1;
    display: flex;
    flex-direction: column;
    gap: 16px;
}
.rp-content-header {
    background: white;
    border-radius: 18px;
    padding: 20px 24px;
    border: 1px solid #ede7f6;
    box-shadow: 0 2px 8px rgba(0,0,0,0.04);
}
.rp-content-title {
    font-size: 1.1rem;
    font-weight: 700;
    color: #212121;
}
.rp-content-sub {
    font-size: 0.8rem;
    color: #9e9e9e;
}
.rp-progress {
    margin-top: 14px;
    border-radius: 10px !important;
}

/* ── Grupos ── */
.rp-groups {
    display: flex;
    flex-direction: column;
    gap: 12px;
}
.rp-group {
    background: white;
    border-radius: 18px;
    border: 1px solid #ede7f6;
    overflow: hidden;
    box-shadow: 0 2px 8px rgba(0,0,0,0.04);
    transition: box-shadow 0.2s;
}
.rp-group:hover {
    box-shadow: 0 4px 16px rgba(92, 53, 201, 0.1);
}
.rp-group-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 14px 20px;
    background: #faf8ff;
    cursor: pointer;
    border-bottom: 1px solid #ede7f6;
    user-select: none;
}
.rp-group-title {
    font-size: 0.9rem;
    font-weight: 700;
    color: #424242;
    letter-spacing: 0.01em;
}
.rp-perms-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
    gap: 0;
}
.rp-perm-item {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 12px 20px;
    cursor: pointer;
    border-bottom: 1px solid #f5f5f5;
    border-right: 1px solid #f5f5f5;
    transition: background 0.15s;
}
.rp-perm-item:hover {
    background: #f9f5ff;
}
.rp-perm-item--active {
    background: #f5f0ff;
}
.rp-perm-left {
    display: flex;
    align-items: center;
    gap: 10px;
}
.rp-perm-dot {
    width: 8px;
    height: 8px;
    border-radius: 50%;
    background: #e0e0e0;
    transition: background 0.2s;
    flex-shrink: 0;
}
.rp-perm-dot--on {
    background: #7c3aed;
    box-shadow: 0 0 6px rgba(124, 58, 237, 0.5);
}
.rp-perm-label {
    font-size: 0.84rem;
    color: #424242;
    font-weight: 500;
}
.rp-perm-item--active .rp-perm-label {
    color: #5c35c9;
    font-weight: 600;
}

/* ── Footer Guardar ── */
.rp-footer {
    display: flex;
    justify-content: flex-end;
    padding: 4px 0 8px;
}
.rp-save-btn {
    border-radius: 14px !important;
    padding: 0 28px !important;
    font-weight: 600 !important;
    letter-spacing: 0.02em !important;
    box-shadow: 0 4px 16px rgba(92, 53, 201, 0.3) !important;
}

@media (max-width: 768px) {
    .rp-main { flex-direction: column; }
    .rp-sidebar { width: 100%; }
    .rp-header-card { flex-direction: column; gap: 16px; }
    .rp-perms-grid { grid-template-columns: 1fr; }
}
</style>
