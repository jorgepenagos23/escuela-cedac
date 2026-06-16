<script setup>
import { ref } from 'vue';
import ApplicationLogo from '@/Components/ApplicationLogo.vue';
import Dropdown from '@/Components/Dropdown.vue';
import DropdownLink from '@/Components/DropdownLink.vue';
import NavLink from '@/Components/NavLink.vue';
import ResponsiveNavLink from '@/Components/ResponsiveNavLink.vue';
import CommandPalette from '@/Components/CommandPalette.vue';
import { Link } from '@inertiajs/vue3';
import "../../css/app.css";

const showingNavigationDropdown = ref(false);
const paletaAbierta             = ref(false);
const finanzasAbierto           = ref(false);
</script>

<template>
    <div>
        <div class="h-100 w-100 bg-white">
            <nav class="bg-white border-b border-gray-100 shadow-sm">
                <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                    <div class="flex justify-between h-14 items-center">

                        <!-- Logo + Marca -->
                        <div class="flex items-center gap-3">
                            <Link :href="route('dashboard')" class="shrink-0 flex items-center">
                                <ApplicationLogo class="block h-8 w-auto fill-current text-gray-800" />
                            </Link>
                            <span class="hidden sm:block text-xs font-semibold text-gray-400 tracking-widest uppercase border-l border-gray-200 pl-3">
                                Sistema de Gestión
                            </span>
                        </div>

                        <!-- ── Buscador de objetos (botón central) ── -->
                        <div class="flex-1 max-w-sm mx-4 hidden sm:block">
                            <button
                                @click="paletaAbierta = true"
                                class="nav-search-btn group w-full"
                                title="Buscar módulo (Ctrl+K)"
                            >
                                <div class="flex items-center gap-2 flex-1">
                                    <svg class="nav-search-btn__icon" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2">
                                        <circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/>
                                    </svg>
                                    <span class="nav-search-btn__text">Buscar módulo o función...</span>
                                </div>
                                <div class="flex items-center gap-1">
                                    <kbd class="nav-kbd">Ctrl</kbd>
                                    <kbd class="nav-kbd">K</kbd>
                                </div>
                            </button>
                        </div>

                        <!-- Acciones de la derecha -->
                        <div class="flex items-center gap-2">
                            <!-- Botón búsqueda en mobile -->
                            <button
                                @click="paletaAbierta = true"
                                class="sm:hidden p-2 rounded-lg text-gray-500 hover:bg-gray-100 transition"
                                title="Abrir buscador"
                            >
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2">
                                    <circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/>
                                </svg>
                            </button>

                            <!-- Dashboard -->
                            <NavLink :href="route('dashboard')" :active="route().current('dashboard')" class="hidden sm:inline-flex">
                                <v-icon size="16" class="mr-1">mdi-view-dashboard-outline</v-icon>
                                Dashboard
                            </NavLink>

                            <!-- Finanzas dropdown -->
                            <div class="relative hidden sm:block" @mouseenter="finanzasAbierto = true" @mouseleave="finanzasAbierto = false">
                                <button
                                    class="finanzas-nav-btn"
                                    :class="{ 'finanzas-nav-btn--active': finanzasAbierto }"
                                    @click="finanzasAbierto = !finanzasAbierto"
                                >
                                    <v-icon size="16" class="mr-1">mdi-finance</v-icon>
                                    Finanzas
                                    <svg class="h-3 w-3 ml-1 transition-transform" :class="{ 'rotate-180': finanzasAbierto }" viewBox="0 0 20 20" fill="currentColor">
                                        <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                                    </svg>
                                </button>

                                <!-- Panel desplegable -->
                                <Transition name="dropdown-fade">
                                    <div v-show="finanzasAbierto" class="finanzas-dropdown">
                                        <div class="finanzas-dropdown__header">
                                            <span class="text-xs font-bold text-indigo-400 uppercase tracking-widest">Módulo Finanzas</span>
                                        </div>

                                        <Link href="/finanzas/dashboard" class="finanzas-dropdown__item" @click="finanzasAbierto = false">
                                            <div class="finanzas-dropdown__icon" style="background: linear-gradient(135deg,#1a3a5c,#0070f3);">
                                                <v-icon size="15" color="white">mdi-chart-areaspline</v-icon>
                                            </div>
                                            <div>
                                                <div class="finanzas-dropdown__item-title">Dashboard Financiero</div>
                                                <div class="finanzas-dropdown__item-sub">Estado de resultados · Cartera</div>
                                            </div>
                                        </Link>

                                        <Link href="/crud-pagos" class="finanzas-dropdown__item" @click="finanzasAbierto = false">
                                            <div class="finanzas-dropdown__icon" style="background: linear-gradient(135deg,#0d9488,#059669);">
                                                <v-icon size="15" color="white">mdi-cash-multiple</v-icon>
                                            </div>
                                            <div>
                                                <div class="finanzas-dropdown__item-title">Cartera de Cobranza</div>
                                                <div class="finanzas-dropdown__item-sub">Pagos · Adeudos · Abonos</div>
                                            </div>
                                        </Link>

                                        <Link href="/mensualidades/pagos" class="finanzas-dropdown__item" @click="finanzasAbierto = false">
                                            <div class="finanzas-dropdown__icon" style="background: linear-gradient(135deg,#7c3aed,#4f46e5);">
                                                <v-icon size="15" color="white">mdi-file-table-outline</v-icon>
                                            </div>
                                            <div>
                                                <div class="finanzas-dropdown__item-title">Conciliación</div>
                                                <div class="finanzas-dropdown__item-sub">Libro de movimientos · Excel</div>
                                            </div>
                                        </Link>

                                        <Link href="/contabilidad" class="finanzas-dropdown__item" @click="finanzasAbierto = false">
                                            <div class="finanzas-dropdown__icon" style="background: linear-gradient(135deg,#b45309,#d97706);">
                                                <v-icon size="15" color="white">mdi-notebook-outline</v-icon>
                                            </div>
                                            <div>
                                                <div class="finanzas-dropdown__item-title">Contabilidad</div>
                                                <div class="finanzas-dropdown__item-sub">Reportes contables</div>
                                            </div>
                                        </Link>
                                    </div>
                                </Transition>
                            </div>

                            <!-- Menú de usuario -->
                            <Dropdown align="right" width="52">
                                <template #trigger>
                                    <button class="user-menu-btn">
                                        <!-- Avatar con inicial -->
                                        <span class="user-avatar">
                                            {{ $page.props.auth.user.name?.charAt(0)?.toUpperCase() ?? '?' }}
                                        </span>
                                        <span class="hidden sm:block text-sm font-medium text-gray-700 truncate max-w-28">
                                            {{ $page.props.auth.user.name?.split(' ')[0] }}
                                        </span>
                                        <svg class="h-4 w-4 text-gray-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                            <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                                        </svg>
                                    </button>
                                </template>

                                <template #content>
                                    <!-- Cabecera del dropdown -->
                                    <div class="px-4 py-3 border-b border-gray-100">
                                        <p class="text-xs text-gray-400">Conectado como</p>
                                        <p class="text-sm font-semibold text-gray-800 truncate">{{ $page.props.auth.user.name }}</p>
                                        <p class="text-xs text-indigo-500 truncate">{{ $page.props.auth.user.email }}</p>
                                    </div>

                                    <DropdownLink :href="route('vista.mi_panel')">
                                        <v-icon size="small" class="mr-2" color="blue-grey">mdi-monitor-dashboard</v-icon> Mi Panel
                                    </DropdownLink>
                                    <DropdownLink :href="route('buscador.alumnos')">
                                        <v-icon size="small" class="mr-2" color="deep-purple">mdi-card-search</v-icon> Buscador de Alumnos
                                    </DropdownLink>
                                    <DropdownLink :href="route('profile.edit')">
                                        <v-icon size="small" class="mr-2" color="grey">mdi-account</v-icon> Mi Perfil
                                    </DropdownLink>

                                    <!-- Opciones solo TI -->
                                    <template v-if="$page.props.auth.user?.roles?.includes('TI')">
                                        <div class="px-4 py-1 mt-1 border-t border-gray-100">
                                            <span class="text-xs font-bold text-gray-400 uppercase tracking-wide">Administración TI</span>
                                        </div>
                                        <DropdownLink :href="route('users.index')">
                                            <v-icon size="small" class="mr-2" color="deep-orange">mdi-account-group</v-icon> Usuarios
                                        </DropdownLink>
                                        <DropdownLink :href="route('roles_permisos.index')">
                                            <v-icon size="small" class="mr-2" color="deep-orange">mdi-shield-account</v-icon> Roles y Permisos
                                        </DropdownLink>
                                    </template>

                                    <div class="border-t border-gray-100 mt-1">
                                        <DropdownLink :href="route('logout')" method="post" as="button">
                                            <v-icon size="small" class="mr-2" color="red">mdi-logout</v-icon> Cerrar Sesión
                                        </DropdownLink>
                                    </div>
                                </template>
                            </Dropdown>
                        </div>
                    </div>
                </div>
            </nav>

            <!-- Page Heading -->
            <header class="bg-white shadow-sm" v-if="$slots.header">
                <div class="max-w-7xl mx-auto py-4 px-4 sm:px-6 lg:px-8">
                    <slot name="header" />
                </div>
            </header>

            <!-- Page Content -->
            <main>
                <slot />
            </main>
        </div>
    </div>

    <!-- ── Paleta de Comandos (global, Teleport al body) ── -->
    <CommandPalette v-model="paletaAbierta" />
</template>

<style scoped>
/* ── Botón buscador del nav ── */
.nav-search-btn {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 8px;
    padding: 7px 12px;
    background: #f8fafc;
    border: 1.5px solid #e2e8f0;
    border-radius: 10px;
    cursor: pointer;
    transition: all 0.15s;
    color: #64748b;
}
.nav-search-btn:hover {
    background: #f0f4ff;
    border-color: #a5b4fc;
    color: #4338ca;
    box-shadow: 0 0 0 3px rgba(165,180,252,0.2);
}
.nav-search-btn__icon { flex-shrink: 0; color: inherit; }
.nav-search-btn__text { font-size: 0.82rem; color: inherit; }
.nav-kbd {
    display: inline-flex;
    align-items: center;
    font-size: 0.65rem;
    font-weight: 700;
    font-family: ui-monospace, monospace;
    background: white;
    color: #94a3b8;
    border: 1px solid #e2e8f0;
    border-bottom-width: 2px;
    border-radius: 4px;
    padding: 1px 5px;
    line-height: 1.6;
}
.nav-search-btn:hover .nav-kbd {
    border-color: #a5b4fc;
    color: #6366f1;
}

/* ── Dropdown Finanzas ── */
.finanzas-nav-btn {
    display: inline-flex;
    align-items: center;
    padding: 5px 12px;
    font-size: 0.85rem;
    font-weight: 500;
    color: #374151;
    border-radius: 8px;
    border: 1.5px solid transparent;
    background: transparent;
    cursor: pointer;
    transition: all 0.15s;
    white-space: nowrap;
}
.finanzas-nav-btn:hover,
.finanzas-nav-btn--active {
    background: #f0f4ff;
    border-color: #a5b4fc;
    color: #4338ca;
}
.finanzas-dropdown {
    position: absolute;
    top: calc(100% + 6px);
    left: 50%;
    transform: translateX(-50%);
    width: 280px;
    background: white;
    border: 1px solid #e2e8f0;
    border-radius: 14px;
    box-shadow: 0 12px 40px rgba(0,0,0,0.12), 0 4px 16px rgba(0,0,0,0.06);
    z-index: 9999;
    overflow: hidden;
}
.finanzas-dropdown__header {
    padding: 10px 16px 6px;
    border-bottom: 1px solid #f1f5f9;
}
.finanzas-dropdown__item {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 10px 16px;
    text-decoration: none;
    color: inherit;
    transition: background 0.12s;
    cursor: pointer;
}
.finanzas-dropdown__item:hover {
    background: #f8fafc;
}
.finanzas-dropdown__icon {
    width: 34px;
    height: 34px;
    border-radius: 9px;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}
.finanzas-dropdown__item-title {
    font-size: 0.84rem;
    font-weight: 600;
    color: #1e293b;
    line-height: 1.2;
}
.finanzas-dropdown__item-sub {
    font-size: 0.72rem;
    color: #94a3b8;
    margin-top: 2px;
}

/* Animación */
.dropdown-fade-enter-active,
.dropdown-fade-leave-active { transition: opacity 0.15s, transform 0.15s; }
.dropdown-fade-enter-from,
.dropdown-fade-leave-to { opacity: 0; transform: translateX(-50%) translateY(-6px); }

/* ── Menú de usuario ── */
.user-menu-btn {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 5px 10px;
    border-radius: 10px;
    border: 1.5px solid #e2e8f0;
    background: #f8fafc;
    cursor: pointer;
    transition: all 0.15s;
}
.user-menu-btn:hover {
    background: #f0f4ff;
    border-color: #a5b4fc;
}
.user-avatar {
    width: 28px; height: 28px;
    border-radius: 8px;
    background: linear-gradient(135deg, #4338ca, #7c3aed);
    color: white;
    font-size: 0.8rem;
    font-weight: 800;
    display: flex;
    align-items: center;
    justify-content: center;
}
</style>
