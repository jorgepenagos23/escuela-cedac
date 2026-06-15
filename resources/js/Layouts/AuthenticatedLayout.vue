<script setup>
import { ref, provide, inject, onMounted } from 'vue';
import { usePage, Link } from '@inertiajs/vue3';
import ApplicationLogo from '@/Components/ApplicationLogo.vue';
import Dropdown from '@/Components/Dropdown.vue';
import DropdownLink from '@/Components/DropdownLink.vue';
import CommandPalette from '@/Components/CommandPalette.vue';
import { useErpWindows } from '@/Composables/useErpWindows';
import "../../css/app.css";

// Comprobamos si estamos dentro del contendor del ERP
const inErpShell = inject('in-erp-shell', false);
if (!inErpShell) {
    provide('in-erp-shell', true);
}

const erp = useErpWindows();
const page = usePage();
const authUser = page.props.auth?.user;
const paletaAbierta = ref(false);

const appModules = [
    { name: 'Dashboard', url: route('dashboard'), icon: 'mdi-view-dashboard-outline' },
    { name: 'Mi Panel', url: route('vista.mi_panel'), icon: 'mdi-monitor-dashboard' },
    { name: 'Admisiones', url: route('seguimiento.inscripciones'), icon: 'mdi-account-plus-outline' },
    { name: 'Buscador de Alumnos', url: route('buscador.alumnos'), icon: 'mdi-card-search-outline' },
    { name: 'Seguimiento', url: route('vista.inscripciones'), icon: 'mdi-account-search-outline' },
    { name: 'Caja y Pagos', url: '/crud-pagos', icon: 'mdi-cash-register' },
    { name: 'Colegiaturas', url: route('vista.pagos'), icon: 'mdi-wallet-outline' },
    { name: 'Contabilidad', url: route('vista.contabilidad'), icon: 'mdi-chart-line-stacked' },
    { name: 'Financiero', url: route('vista.financiero'), icon: 'mdi-finance' },
    { name: 'Diplomados', url: route('index.diplomado'), icon: 'mdi-certificate-outline' },
    { name: 'Estadísticas', url: route('Estadisticas'), icon: 'mdi-chart-line' },
];

onMounted(() => {
    if (!inErpShell) {
        // Inicializamos la primera tab correspondiente a la visita que hicimos al navegador (ej. dashboard)
        erp.initializeRootTab(page.props.title || document.title || 'Módulo Principal');
    }
});

const openModule = (mod) => {
    erp.openErpWindow({ url: mod.url, name: mod.name, icon: mod.icon });
};
</script>

<template>
  <template v-if="inErpShell">
     <!-- Si ya estamos dentro del ERP, simplemente pasamos el contenido transparente -->
     <slot />
  </template>

  <template v-else>
      <div class="h-screen w-full flex bg-[#f4f6f9] overflow-hidden font-sans">
          
          <!-- SIDEBAR IZQUIERDO -->
          <aside class="erp-sidebar flex flex-col bg-slate-900 text-white transition-all duration-300 shadow-xl overflow-hidden z-20" 
                 :class="erp.isSidebarCollapsed.value ? 'w-[72px]' : 'w-64'">
              
              <!-- Logo Área -->
              <div class="h-14 flex items-center px-4 border-b border-slate-800/80 shrink-0 select-none">
                  <ApplicationLogo class="block h-8 w-8 fill-current text-indigo-400 shrink-0" />
                  <span v-if="!erp.isSidebarCollapsed.value" class="ml-3 font-bold text-[0.8rem] tracking-widest uppercase truncate fade-in">
                      Escuela CEDAC
                  </span>
              </div>

              <!-- Content Scrollable (Módulos) -->
              <div class="flex-1 overflow-y-auto px-3 py-4 flex flex-col gap-[2px] sidebar-scroll">
                  <!-- Buscador Rapido -->
                  <button @click="paletaAbierta = true" class="sidebar-search-btn mb-4 group" :title="'Buscar Función'">
                      <v-icon size="20" color="grey-lighten-1">mdi-magnify</v-icon>
                      <template v-if="!erp.isSidebarCollapsed.value">
                          <span class="ml-3 text-[0.85rem] text-gray-400 font-medium group-hover:text-white transition-colors">Buscar módulo...</span>
                          <kbd class="ml-auto text-[0.6rem] bg-slate-800 border border-slate-700 px-1.5 py-0.5 rounded text-gray-400 font-mono">⌘K</kbd>
                      </template>
                  </button>

                  <div v-if="!erp.isSidebarCollapsed.value" class="text-[0.65rem] font-bold text-slate-500 uppercase tracking-widest mb-2 mt-2 px-2">
                      Principal
                  </div>

                  <!-- Botones Navegación ERP -->
                  <button v-for="mod in appModules" :key="mod.url" @click="openModule(mod)"
                      class="sidebar-nav-item" :class="{ 'justify-center px-0': erp.isSidebarCollapsed.value }" :title="mod.name">
                      <v-icon size="20" class="shrink-0 text-slate-400 group-hover:text-white transition-colors">{{ mod.icon }}</v-icon>
                      <span v-if="!erp.isSidebarCollapsed.value" class="ml-3 text-[0.85rem] truncate">{{ mod.name }}</span>
                  </button>

                  <template v-if="authUser?.roles?.includes('TI')">
                      <div v-if="!erp.isSidebarCollapsed.value" class="text-[0.65rem] font-bold text-slate-500 uppercase tracking-widest mb-2 mt-4 px-2">
                          TI Administrador
                      </div>
                      <button @click="openModule({ name: 'Usuarios', url: route('users.index'), icon: 'mdi-account-group' })" class="sidebar-nav-item" :title="'Usuarios'" :class="{ 'justify-center px-0': erp.isSidebarCollapsed.value }">
                          <v-icon size="20" class="shrink-0 text-orange-400">mdi-account-group</v-icon>
                          <span v-if="!erp.isSidebarCollapsed.value" class="ml-3 text-[0.85rem] truncate">Usuarios</span>
                      </button>
                      <button @click="openModule({ name: 'Roles y Permisos', url: route('roles_permisos.index'), icon: 'mdi-shield-account' })" class="sidebar-nav-item" :title="'Roles y Permisos'" :class="{ 'justify-center px-0': erp.isSidebarCollapsed.value }">
                          <v-icon size="20" class="shrink-0 text-orange-400">mdi-shield-account</v-icon>
                          <span v-if="!erp.isSidebarCollapsed.value" class="ml-3 text-[0.85rem] truncate">Roles y Permisos</span>
                      </button>
                      <button @click="openModule({ name: 'Cuentas Bancarias', url: route('cuentas-bancarias.index'), icon: 'mdi-bank' })" class="sidebar-nav-item" :title="'Cuentas Bancarias'" :class="{ 'justify-center px-0': erp.isSidebarCollapsed.value }">
                          <v-icon size="20" class="shrink-0 text-orange-400">mdi-bank</v-icon>
                          <span v-if="!erp.isSidebarCollapsed.value" class="ml-3 text-[0.85rem] truncate">Cuentas Bancarias</span>
                      </button>
                  </template>
              </div>

              <!-- Footer Sidebar (Usuario) -->
              <div class="px-3 py-4 border-t border-slate-800/80 shrink-0 bg-slate-900/80 flex flex-col gap-3">
                  <!-- Usuario Info -->
                  <div class="flex items-center px-1" :class="{ 'justify-center': erp.isSidebarCollapsed.value }">
                      <v-avatar color="indigo" size="32" class="shrink-0 text-white font-bold text-xs ring-2 ring-slate-700">
                          {{ authUser?.name?.charAt(0)?.toUpperCase() ?? 'U' }}
                      </v-avatar>
                      <div v-if="!erp.isSidebarCollapsed.value" class="ml-3 text-left overflow-hidden">
                          <p class="text-[0.8rem] font-semibold text-slate-200 truncate leading-tight">{{ authUser?.name?.split(' ')[0] }} {{ authUser?.name?.split(' ')[1] || '' }}</p>
                          <p class="text-[0.6rem] text-slate-400 truncate mt-0.5">{{ authUser?.email }}</p>
                          <p class="text-[0.55rem] text-indigo-300 font-bold uppercase tracking-widest mt-1">
                              {{ authUser?.roles && authUser.roles.length > 0 ? authUser.roles.join(', ') : 'SIN ROL' }}
                          </p>
                      </div>
                  </div>

                  <!-- Botón de Salir -->
                  <Link :href="route('logout')" method="post" as="button" 
                        class="flex items-center p-2 rounded-lg text-red-400 hover:bg-red-500/10 hover:text-red-300 transition-colors w-full border border-red-500/20" 
                        :class="{ 'justify-center': erp.isSidebarCollapsed.value }" 
                        title="Cerrar Sesión Segura">
                      <v-icon size="18" class="shrink-0">mdi-power</v-icon>
                      <span v-if="!erp.isSidebarCollapsed.value" class="ml-3 text-[0.7rem] font-bold uppercase tracking-wider">Cerrar Sesión</span>
                  </Link>
                  
                  <!-- Toggle Sidebar -->
                  <button @click="erp.toggleSidebar()" class="w-full text-slate-500 hover:text-white flex justify-center py-1.5 rounded-lg hover:bg-slate-800 transition-colors" title="Contraer/Expandir Panel">
                      <v-icon size="20">{{ erp.isSidebarCollapsed.value ? 'mdi-chevron-double-right' : 'mdi-chevron-double-left' }}</v-icon>
                  </button>
              </div>
          </aside>

          <!-- AREA PRINCIPAL (TABS + CONTENT) -->
          <main class="flex-1 flex flex-col min-w-0 bg-[#f8fafc] z-10 shadow-[-10px_0_15px_rgba(0,0,0,0.03)]">
              <!-- TABS HEADER -->
              <header class="h-10 bg-[#e2e8f0]/40 backdrop-blur-sm border-b border-gray-200 flex items-end px-2 shrink-0 overflow-x-auto tabs-scroll pt-1 gap-1">
                  <!-- Pestañas iterables -->
                  <div v-for="tab in erp.tabs.value" :key="String(tab.id)"
                       @click="erp.setActiveTab(tab.id)"
                       class="erp-tab group shrink-0 relative"
                       :class="{'erp-tab--active': erp.activeTabId.value === tab.id}">
                      
                      <div class="absolute inset-0 bg-white opacity-0 transition-opacity rounded-t-lg pointer-events-none" :class="{ 'opacity-100': erp.activeTabId.value === tab.id }"></div>
                      
                      <v-icon v-if="tab.icon" size="14" class="mr-1.5 opacity-60 transition-opacity z-10 relative" :class="{ 'opacity-100 text-indigo-600': erp.activeTabId.value === tab.id }">{{ tab.icon }}</v-icon>
                      <span class="text-[0.78rem] truncate font-medium max-w-[160px] select-none z-10 relative transition-colors" :class="erp.activeTabId.value === tab.id ? 'text-gray-900' : 'text-gray-500'">{{ tab.title }}</span>
                      
                      <button v-if="!tab.isInitial" @click.stop="erp.closeTab(tab.id)" 
                              class="ml-2 w-5 h-5 rounded-md hover:bg-gray-200/80 hover:text-red-500 flex items-center justify-center opacity-0 group-hover:opacity-100 transition-all z-10 relative"
                              :class="{ 'opacity-100 bg-gray-100 text-gray-400': erp.activeTabId.value === tab.id }">
                          <v-icon size="11">mdi-close</v-icon>
                      </button>

                      <!-- Separador entre tabs inactivas -->
                      <div class="absolute right-[-1px] top-[20%] bottom-[20%] w-[1px] bg-gray-300 transition-opacity" 
                           v-if="erp.activeTabId.value !== tab.id && tab !== erp.tabs.value[erp.tabs.value.length-1]"></div>
                  </div>
              </header>

              <!-- BARRA DE ACCIONES (ACTION BAR) -->
              <div class="h-12 bg-white border-b border-gray-200 flex items-center px-4 justify-between shrink-0 shadow-sm z-10">
                  <!-- Lado Izquierdo: Acciones Globales (Refrescar) -->
                  <div class="flex items-center gap-2">
                      <button @click="erp.refreshActiveTab()" 
                              class="flex items-center gap-1.5 px-3 py-1.5 rounded-md text-slate-600 hover:text-indigo-600 hover:bg-indigo-50 border border-transparent hover:border-indigo-100 transition-all" 
                              :disabled="erp.isRefreshing.value"
                              title="Recargar datos de la pestaña actual">
                          <v-icon size="16" :class="{ 'animate-spin': erp.isRefreshing.value }">mdi-refresh</v-icon>
                          <span class="text-sm font-semibold">Refresh</span>
                      </button>
                      
                      <!-- Indicador de cargando -->
                      <span v-if="erp.isRefreshing.value" class="text-xs text-indigo-400 font-medium ml-2 animate-pulse">
                          Cargando cambios...
                      </span>
                  </div>
                  
                  <!-- Lado Derecho: Exportar dinámico + acciones de pestaña -->
                  <div class="flex items-center gap-2">
                      <!-- Botón de exportación contextual: cambia según el tab activo -->
                      <template v-if="erp.currentTabExport.value">
                          <div class="h-5 w-px bg-gray-200"></div>
                          <button
                              @click="erp.currentTabExport.value.fn()"
                              class="flex items-center gap-1.5 px-3 py-1.5 rounded-md text-emerald-700 hover:text-white hover:bg-emerald-600 border border-emerald-200 hover:border-emerald-500 transition-all"
                              :title="erp.currentTabExport.value.label"
                          >
                              <v-icon size="16">{{ erp.currentTabExport.value.icon || 'mdi-microsoft-excel' }}</v-icon>
                              <span class="text-sm font-semibold">{{ erp.currentTabExport.value.label }}</span>
                          </button>
                      </template>
                      <!-- Slot para acciones adicionales inyectadas por componentes -->
                      <div id="erp-tab-actions" class="flex items-center gap-2"></div>
                  </div>
              </div>

              <!-- PANEL DE CONTENIDO (Páginas cargadas) -->
              <section class="flex-1 overflow-auto relative bg-white">
                  
                  <!-- Slot Original (Punto de entrada inicial de Inertia) -->
                  <div v-show="erp.activeTabId.value === erp.initialTabId" class="w-full h-full">
                      <slot />
                  </div>

                  <!-- Componentes dinámicos resueltos por el backend ERP -->
                  <template v-for="tab in erp.tabs.value" :key="String(tab.id)">
                      <div v-if="!tab.isInitial" v-show="erp.activeTabId.value === tab.id" class="w-full h-full">
                          <!-- Vue component dinámico -->
                          <component :is="tab.component" v-bind="tab.props" />
                      </div>
                  </template>
              </section>
          </main>

      </div>

      <!-- Command Palette Teleportado globalmente -->
      <CommandPalette v-model="paletaAbierta" />
  </template>
</template>

<style scoped>
/* ── Sidebar Estilos ── */
.sidebar-scroll::-webkit-scrollbar { width: 4px; }
.sidebar-scroll::-webkit-scrollbar-thumb { background: rgba(255,255,255,0.1); border-radius: 4px; }

.sidebar-search-btn {
    display: flex; align-items: center; justify-content: center;
    padding: 8px 12px; border-radius: 10px;
    background: #1e293b; border: 1px solid rgba(255,255,255,0.05); transition: all 0.2s;
}
.sidebar-search-btn:hover { background: #0f172a; border-color: rgba(99,102,241,0.5); box-shadow: 0 0 10px rgba(99,102,241,0.1); }

.sidebar-nav-item {
    display: flex; align-items: center; padding: 10px 14px;
    border-radius: 10px; color: #94a3b8; transition: all 0.15s ease-out;
    user-select: none; position: relative;
    overflow: hidden;
}
.sidebar-nav-item:hover { background: rgba(255,255,255,0.04); color: white; }
.sidebar-nav-item:active { transform: scale(0.98); }

.fade-in { animation: fadeIn 0.3s ease-out forwards; }
@keyframes fadeIn { from { opacity: 0; } to { opacity: 1; } }

/* ── Tabs Estilos ── */
.erp-tab {
    display: inline-flex; align-items: center; padding: 0 12px 0 14px; height: 35px;
    cursor: pointer; transition: all 0.15s;
    user-select: none; border-top-left-radius: 8px; border-top-right-radius: 8px;
}
.erp-tab--active {
    box-shadow: 0 -2px 0 0 #4f46e5 inset;
}
.tabs-scroll::-webkit-scrollbar { height: 2px; }
.tabs-scroll::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 4px; }
</style>
