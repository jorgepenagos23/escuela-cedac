import { ref, markRaw, computed } from 'vue';
import axios from 'axios';
import { usePage, router } from '@inertiajs/vue3';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';

// Estado Global
const tabs = ref([]);
const activeTabId = ref(null);
const initialTabId = Symbol('initial');

// Estado para controlar el sidebar colapsado
const isSidebarCollapsed = ref(false);

// Estado de carga para refresco
const isRefreshing = ref(false);

// ── Export Registry ───────────────────────────────────────────────────────────
// Cada página registra su función de exportación keyed por nombre de componente
const exportHandlers = ref({}); // { 'ComponentName': { label, icon, fn } }

export function useErpWindows() {
    const page = usePage();

    const toggleSidebar = () => {
        isSidebarCollapsed.value = !isSidebarCollapsed.value;
    };

    // ── Registro de exportaciones por pestaña ────────────────────────────────
    const registerTabExport = (componentName, config) => {
        exportHandlers.value = { ...exportHandlers.value, [componentName]: config };
    };
    const unregisterTabExport = (componentName) => {
        const copy = { ...exportHandlers.value };
        delete copy[componentName];
        exportHandlers.value = copy;
    };
    // Devuelve el handler del tab activo (busca por component_name almacenado en el tab)
    const currentTabExport = computed(() => {
        const tab = tabs.value.find(t => t.id === activeTabId.value);
        if (!tab?.component_name) return null;
        return exportHandlers.value[tab.component_name] || null;
    });

    const initializeRootTab = (title) => {
        if (tabs.value.length === 0) {
            tabs.value.push({
                id: initialTabId,
                title: title || 'Inicio',
                isInitial: true,
                component_name: page.component,   // nombre del componente Inertia actual
            });
            activeTabId.value = initialTabId;
        }
    };

    const openErpWindow = async ({ url, name, icon }) => {
        // Si ya está abierto con esa URL, lo enfocamos
        const existing = tabs.value.find(t => t.url === url);
        if (existing) {
            activeTabId.value = existing.id;
            return;
        }

        try {
            // Pedimos el componente Inertia por AJAX
            const res = await axios.get(url, {
                headers: {
                    'X-Inertia': 'true',
                    'X-Inertia-Version': page.version,
                    'Accept': 'text/html, application/xhtml+xml'
                }
            });

            const inertiaData = res.data;
            const compName = inertiaData.component;
            const props = inertiaData.props;

            // Resolvemos el componente de la página dinámicamente usando Vite
            const moduleGlob = import.meta.glob('../Pages/**/*.vue');
            const resolved = await resolvePageComponent(`../Pages/${compName}.vue`, moduleGlob);
            const vueComp = resolved.default;

            const newId = url; // Date.now().toString(); usamos URL para que sea persistente y único
            
            tabs.value.push({
                id: newId,
                url: url,
                title: name,
                icon: icon,
                component: markRaw(vueComp),
                component_name: compName,          // nombre del componente para el export registry
                props: props,
                isInitial: false
            });
            
            activeTabId.value = newId;

        } catch (error) {
            console.error("Error abriendo módulo en ERP:", error);
            // Fallback
            router.visit(url);
        }
    };

    const closeTab = (id) => {
        const idx = tabs.value.findIndex(t => t.id === id);
        if (idx !== -1) {
            tabs.value.splice(idx, 1);
            if (activeTabId.value === id) {
                // Enfoca la última pestaña disponible
                if (tabs.value.length > 0) {
                    activeTabId.value = tabs.value[tabs.value.length - 1].id;
                } else {
                    activeTabId.value = null; // Si se cierran todas, tal vez ir as dashboard?
                }
            }
        }
    };

    const setActiveTab = (id) => {
        activeTabId.value = id;
    };

    const refreshActiveTab = async () => {
        if (!activeTabId.value || activeTabId.value === initialTabId) {
            // Si es la pestaña inicial y no tiene URL controlada por tabs, recarga normal
            window.location.reload();
            return;
        }

        const tab = tabs.value.find(t => t.id === activeTabId.value);
        if (!tab) return;

        try {
            isRefreshing.value = true;
            // Peticion limpia a la misma URL para traer datos frescos de la DB
            const res = await axios.get(tab.url, {
                headers: {
                    'X-Inertia': 'true',
                    'X-Inertia-Version': page.version,
                    'Accept': 'text/html, application/xhtml+xml'
                }
            });

            // Actualizamos solo los props del componente inyectado
            // Vue al ser reactivo, re-renderizará la tabla/vista
            if (res.data && res.data.props) {
                tab.props = res.data.props;
            }
        } catch (error) {
            console.error("Error al refrescar la pestaña:", error);
        } finally {
            isRefreshing.value = false;
        }
    };

    return {
        tabs,
        activeTabId,
        initialTabId,
        isSidebarCollapsed,
        isRefreshing,
        toggleSidebar,
        initializeRootTab,
        openErpWindow,
        closeTab,
        setActiveTab,
        refreshActiveTab,
        // Export registry
        registerTabExport,
        unregisterTabExport,
        currentTabExport,
    };
}
