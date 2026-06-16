<script setup>
import { ref, computed, watch, onMounted, onUnmounted, nextTick } from 'vue';
import { usePage } from '@inertiajs/vue3';

// ─── Props / emits ────────────────────────────────────────────────────────────
const props  = defineProps({ modelValue: Boolean });
const emit   = defineEmits(['update:modelValue']);

const page     = usePage();
const authUser = computed(() => page.props.auth?.user);

// ─── Catálogo de módulos (objetos) ────────────────────────────────────────────
const todosLosModulos = computed(() => {
    const esTI   = authUser.value?.roles?.includes('TI');
    const modulos = [
        {
            id: 'admisiones',
            titulo: 'Admisiones',
            subtitulo: 'Inscribir nueva matrícula',
            descripcion: 'Registra a un nuevo alumno, captura sus datos y genera el plan de pagos.',
            icono: 'mdi-account-plus-outline',
            color: 'indigo',
            url: '/seguimiento/inscripciones',
            tags: ['inscribir', 'matrícula', 'nuevo alumno', 'admisión'],
        },
        {
            id: 'buscador',
            titulo: 'Buscador de Alumnos',
            subtitulo: 'Expediente completo',
            descripcion: 'Busca por nombre, celular, correo o CURP. Edita datos y plan de pagos.',
            icono: 'mdi-card-search',
            color: 'deep-purple',
            url: '/buscador-alumnos',
            tags: ['buscar', 'alumno', 'expediente', 'curp', 'plan'],
        },
        {
            id: 'seguimiento',
            titulo: 'Seguimiento',
            subtitulo: 'Consultar inscripciones',
            descripcion: 'Revisa el estatus de inscripciones activas y pasadas del instituto.',
            icono: 'mdi-account-search-outline',
            color: 'teal',
            url: '/inscripciones',
            tags: ['seguimiento', 'inscripciones', 'listado'],
        },
        {
            id: 'caja',
            titulo: 'Caja y Pagos',
            subtitulo: 'Registrar cobros',
            descripcion: 'Cobra colegiaturas, registra abonos, cancela o reprograma pagos.',
            icono: 'mdi-cash-register',
            color: 'green',
            url: '/crud-pagos',
            tags: ['caja', 'cobro', 'pago', 'abono', 'colegiatura'],
        },
        {
            id: 'colegiaturas',
            titulo: 'Colegiaturas',
            subtitulo: 'Tabla de pagos',
            descripcion: 'Consulta el calendario de vencimientos, historial y cartera de pagos.',
            icono: 'mdi-wallet-outline',
            color: 'orange',
            url: '/mensualidades/pagos',
            tags: ['mensualidades', 'pagos', 'vencimientos', 'cartera'],
        },
        {
            id: 'estadisticas',
            titulo: 'Estadísticas',
            subtitulo: 'Resumen financiero',
            descripcion: 'Reportes y gráficos de desempeño financiero y académico del plantel.',
            icono: 'mdi-chart-line',
            color: 'purple',
            url: '/resumen',
            tags: ['estadísticas', 'reportes', 'gráficos', 'finanzas'],
        },
        {
            id: 'dashboard-financiero',
            titulo: 'Dashboard Financiero',
            subtitulo: 'Estado de resultados',
            descripcion: 'Estado de resultados con corte de fechas, cartera vencida, desglose por banco y cajero.',
            icono: 'mdi-finance',
            color: 'indigo',
            url: '/finanzas/dashboard',
            tags: ['dashboard financiero', 'estado de resultados', 'finanzas', 'corte', 'cartera vencida', 'contabilidad', 'compaq'],
        },
        {
            id: 'conciliacion',
            titulo: 'Conciliación de Colegiaturas',
            subtitulo: 'Libro de movimientos',
            descripcion: 'Filtra todos los abonos por fecha, banco o cajero y exporta a Excel para conciliar.',
            icono: 'mdi-file-table-outline',
            color: 'teal',
            url: '/mensualidades/pagos',
            tags: ['conciliación', 'colegiaturas', 'movimientos', 'excel', 'filtrar', 'banco'],
        },
        {
            id: 'contabilidad',
            titulo: 'Contabilidad',
            subtitulo: 'Auditoría de pagos',
            descripcion: 'Conciliación de cuentas, reportes de ingresos y descarga en Excel.',
            icono: 'mdi-chart-line-stacked',
            color: 'red',
            url: '/contabilidad',
            tags: ['contabilidad', 'auditoría', 'excel', 'ingresos'],
        },
        {
            id: 'egresados',
            titulo: 'Alumnos Egresados',
            subtitulo: 'Cartera liquidada',
            descripcion: 'Padrón de alumnos que liquidaron el 100% de su diplomado.',
            icono: 'mdi-account-check',
            color: 'green',
            url: '/alumnos-liquidados',
            tags: ['egresados', 'liquidados', 'certificado', 'padrón'],
        },
        {
            id: 'diplomados',
            titulo: 'Diplomados',
            subtitulo: 'Gestión académica',
            descripcion: 'Crea, edita y consulta la oferta académica de diplomados del plantel.',
            icono: 'mdi-certificate-outline',
            color: 'blue',
            url: '/diplomados',
            tags: ['diplomados', 'cursos', 'académico', 'oferta'],
        },
        {
            id: 'descuentos',
            titulo: 'Lista de Descuentos',
            subtitulo: 'Descuentos de temporada',
            descripcion: 'Crea, activa, suspende o cancela descuentos aplicables en admisiones por diplomado o tutor.',
            icono: 'mdi-tag-multiple',
            color: 'deep-purple',
            url: '/descuentos',
            tags: ['descuento', 'temporada', 'rebaja', 'promoción', 'precio'],
        },
        {
            id: 'mi-panel',
            titulo: 'Mi Panel',
            subtitulo: 'Panel personal',
            descripcion: 'Tu espacio personal de trabajo con actividades y métricas propias.',
            icono: 'mdi-monitor-dashboard',
            color: 'blue-grey',
            url: '/mi-panel',
            tags: ['panel', 'personal', 'mi panel'],
        },
        // Solo TI
        ...(esTI ? [
            {
                id: 'usuarios',
                titulo: 'Usuarios',
                subtitulo: 'Gestión de cuentas',
                descripcion: 'Panel restringido para administrar cuentas y roles del sistema.',
                icono: 'mdi-account-group',
                color: 'deep-orange',
                url: '/users',
                tags: ['usuarios', 'cuentas', 'ti', 'admin'],
            },
            {
                id: 'roles',
                titulo: 'Roles y Permisos',
                subtitulo: 'Configuración de acceso',
                descripcion: 'Define qué puede hacer cada rol dentro del sistema CEDAC.',
                icono: 'mdi-shield-account',
                color: 'deep-orange',
                url: '/roles-permisos',
                tags: ['roles', 'permisos', 'acceso', 'seguridad'],
            },
        ] : []),
    ];
    return modulos;
});

// ─── Estado ───────────────────────────────────────────────────────────────────
const query     = ref('');
const selected  = ref(0);
const inputRef  = ref(null);

// ─── Filtrado ─────────────────────────────────────────────────────────────────
const resultados = computed(() => {
    const q = query.value.trim().toLowerCase();
    if (!q) return todosLosModulos.value;

    return todosLosModulos.value.filter(m => {
        return (
            m.titulo.toLowerCase().includes(q) ||
            m.subtitulo.toLowerCase().includes(q) ||
            m.descripcion.toLowerCase().includes(q) ||
            m.tags.some(t => t.includes(q))
        );
    });
});

// Resetear selected cuando cambian resultados
watch(resultados, () => { selected.value = 0; });

// ─── Abrir / cerrar ───────────────────────────────────────────────────────────
const abierto = computed({
    get: () => props.modelValue,
    set: (v) => emit('update:modelValue', v),
});

watch(abierto, async (v) => {
    if (v) {
        query.value    = '';
        selected.value = 0;
        await nextTick();
        inputRef.value?.focus();
    }
});

// ─── Teclado global Ctrl+K ────────────────────────────────────────────────────
const onKeydown = (e) => {
    if ((e.ctrlKey || e.metaKey) && e.key === 'k') {
        e.preventDefault();
        abierto.value = !abierto.value;
    }
};
onMounted(()  => window.addEventListener('keydown', onKeydown));
onUnmounted(() => window.removeEventListener('keydown', onKeydown));

// ─── Navegación con teclado dentro de la paleta ───────────────────────────────
const onPaletaKeydown = (e) => {
    if (e.key === 'ArrowDown') {
        e.preventDefault();
        selected.value = Math.min(selected.value + 1, resultados.value.length - 1);
        scrollToSelected();
    } else if (e.key === 'ArrowUp') {
        e.preventDefault();
        selected.value = Math.max(selected.value - 1, 0);
        scrollToSelected();
    } else if (e.key === 'Enter') {
        if (resultados.value[selected.value]) {
            navegarA(resultados.value[selected.value].url);
        }
    } else if (e.key === 'Escape') {
        abierto.value = false;
    }
};

const listaRef = ref(null);
const scrollToSelected = () => {
    nextTick(() => {
        const el = listaRef.value?.querySelector('.item--selected');
        el?.scrollIntoView({ block: 'nearest' });
    });
};

// ─── Navegación ───────────────────────────────────────────────────────────────
const navegarA = (url) => {
    abierto.value = false;
    window.location.href = url;
};

// ─── Helpers visuales ─────────────────────────────────────────────────────────
const highlight = (texto, q) => {
    if (!q.trim()) return texto;
    const re = new RegExp(`(${q.replace(/[.*+?^${}()|[\]\\]/g, '\\$&')})`, 'gi');
    return texto.replace(re, '<mark>$1</mark>');
};

const colorMap = {
    indigo: '#4338ca', 'deep-purple': '#7c3aed', teal: '#0d9488',
    green: '#16a34a', orange: '#ea580c', purple: '#9333ea',
    red: '#dc2626', blue: '#2563eb', 'blue-grey': '#475569',
    'deep-orange': '#c2410c',
};
const getBg   = (color) => colorMap[color] ?? '#4338ca';
const getLighter = (color) => getBg(color) + '18';  // ~10% opacity hex
</script>

<template>
    <!-- Overlay -->
    <Teleport to="body">
        <Transition name="cp-fade">
            <div
                v-if="abierto"
                class="cp-overlay"
                @click.self="abierto = false"
            >
                <Transition name="cp-slide">
                    <div v-if="abierto" class="cp-panel" @keydown="onPaletaKeydown">

                        <!-- ── Barra de búsqueda ── -->
                        <div class="cp-searchbar">
                            <div class="cp-searchbar__icon">
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2">
                                    <circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/>
                                </svg>
                            </div>
                            <input
                                ref="inputRef"
                                v-model="query"
                                class="cp-searchbar__input"
                                placeholder="Buscar módulo o función... (Ej: Caja, Alumnos, Pagos)"
                                autocomplete="off"
                                spellcheck="false"
                            />
                            <div v-if="query" class="cp-searchbar__clear" @click="query = ''">
                                <v-icon size="16" color="grey">mdi-close</v-icon>
                            </div>
                            <kbd class="cp-kbd">ESC</kbd>
                        </div>

                        <!-- ── Resultados ── -->
                        <div ref="listaRef" class="cp-results">

                            <!-- Encabezado de sección -->
                            <div class="cp-section-label">
                                <span>{{ query ? `${resultados.length} resultado(s) para "${query}"` : 'Todos los módulos' }}</span>
                                <div class="cp-nav-hint">
                                    <kbd>↑</kbd><kbd>↓</kbd> navegar &nbsp;
                                    <kbd>↵</kbd> abrir
                                </div>
                            </div>

                            <!-- Sin resultados -->
                            <div v-if="resultados.length === 0" class="cp-empty">
                                <v-icon size="40" color="grey-lighten-1">mdi-magnify-remove-outline</v-icon>
                                <p class="mt-2 text-sm text-gray-400">No se encontró ningún módulo para "<strong>{{ query }}</strong>"</p>
                            </div>

                            <!-- Lista de items -->
                            <div
                                v-for="(modulo, idx) in resultados"
                                :key="modulo.id"
                                class="cp-item"
                                :class="{ 'item--selected': idx === selected }"
                                @mouseenter="selected = idx"
                                @click="navegarA(modulo.url)"
                            >
                                <!-- Ícono del módulo -->
                                <div
                                    class="cp-item__icon"
                                    :style="{ background: getLighter(modulo.color), color: getBg(modulo.color) }"
                                >
                                    <v-icon size="22" :color="modulo.color + '-darken-2'">{{ modulo.icono }}</v-icon>
                                </div>

                                <!-- Texto -->
                                <div class="cp-item__body">
                                    <span
                                        class="cp-item__titulo"
                                        v-html="highlight(modulo.titulo, query)"
                                    />
                                    <span
                                        class="cp-item__sub"
                                        v-html="highlight(modulo.subtitulo, query)"
                                    />
                                </div>

                                <!-- Descripción (solo cuando está seleccionado) -->
                                <Transition name="cp-desc">
                                    <span
                                        v-if="idx === selected"
                                        class="cp-item__desc"
                                        v-html="highlight(modulo.descripcion, query)"
                                    />
                                </Transition>

                                <!-- Flecha de acción -->
                                <div class="cp-item__arrow" :style="{ color: getBg(modulo.color) }">
                                    <v-icon size="18">mdi-arrow-right</v-icon>
                                </div>
                            </div>
                        </div>

                        <!-- ── Footer ── -->
                        <div class="cp-footer">
                            <div class="flex items-center gap-4 text-xs text-gray-400">
                                <span class="flex items-center gap-1">
                                    <kbd>Ctrl</kbd><kbd>K</kbd> abrir/cerrar
                                </span>
                                <span class="flex items-center gap-1">
                                    <kbd>↑</kbd><kbd>↓</kbd> navegar
                                </span>
                                <span class="flex items-center gap-1">
                                    <kbd>↵</kbd> abrir módulo
                                </span>
                            </div>
                            <span class="text-xs text-gray-300 hidden sm:block">CEDAC · Sistema de Gestión</span>
                        </div>
                    </div>
                </Transition>
            </div>
        </Transition>
    </Teleport>
</template>

<style scoped>
/* ── Overlay ── */
.cp-overlay {
    position: fixed;
    inset: 0;
    z-index: 9999;
    background: rgba(15, 23, 42, 0.55);
    backdrop-filter: blur(6px);
    display: flex;
    align-items: flex-start;
    justify-content: center;
    padding-top: 10vh;
}

/* ── Panel principal ── */
.cp-panel {
    width: 100%;
    max-width: 640px;
    background: #ffffff;
    border-radius: 20px;
    border: 1px solid #e0e7ff;
    box-shadow:
        0 0 0 1px rgba(99,102,241,0.12),
        0 25px 60px rgba(15,23,42,0.35),
        0 8px 20px rgba(99,102,241,0.15);
    overflow: hidden;
    margin: 0 16px;
}

/* ── Searchbar ── */
.cp-searchbar {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 14px 18px;
    border-bottom: 1.5px solid #f1f5f9;
    background: #fafbff;
}
.cp-searchbar__icon { color: #94a3b8; flex-shrink: 0; }
.cp-searchbar__input {
    flex: 1;
    font-size: 1rem;
    font-weight: 500;
    color: #1e293b;
    border: none;
    outline: none;
    background: transparent;
    font-family: inherit;
}
.cp-searchbar__input::placeholder { color: #94a3b8; font-weight: 400; }
.cp-searchbar__clear { cursor: pointer; flex-shrink: 0; }

/* ── Kbd style ── */
kbd {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-size: 0.68rem;
    font-weight: 700;
    font-family: ui-monospace, monospace;
    background: #f1f5f9;
    color: #64748b;
    border: 1px solid #e2e8f0;
    border-bottom-width: 2px;
    border-radius: 5px;
    padding: 1px 6px;
    line-height: 1.6;
}
.cp-kbd { flex-shrink: 0; }

/* ── Resultados ── */
.cp-results {
    max-height: 420px;
    overflow-y: auto;
    padding: 8px 0;
}
.cp-results::-webkit-scrollbar { width: 5px; }
.cp-results::-webkit-scrollbar-thumb { background: #c7d2fe; border-radius: 4px; }

/* Section label */
.cp-section-label {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 4px 18px 8px;
    font-size: 0.68rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.08em;
    color: #94a3b8;
}
.cp-nav-hint { display: flex; align-items: center; gap: 4px; }

/* Empty state */
.cp-empty {
    display: flex; flex-direction: column;
    align-items: center; justify-content: center;
    padding: 32px;
}

/* ── Item ── */
.cp-item {
    display: flex;
    align-items: center;
    gap: 14px;
    padding: 10px 18px;
    cursor: pointer;
    transition: background 0.1s, border-color 0.1s;
    border-left: 3px solid transparent;
    margin: 1px 0;
}
.cp-item:hover,
.cp-item.item--selected {
    background: #f0f4ff;
    border-left-color: #6366f1;
}

.cp-item__icon {
    width: 42px; height: 42px;
    border-radius: 12px;
    display: flex; align-items: center; justify-content: center;
    flex-shrink: 0;
    transition: transform 0.15s;
}
.cp-item.item--selected .cp-item__icon { transform: scale(1.05); }

.cp-item__body {
    flex: 1;
    display: flex;
    flex-direction: column;
    min-width: 0;
}
.cp-item__titulo {
    font-size: 0.92rem;
    font-weight: 700;
    color: #1e293b;
    line-height: 1.3;
}
.cp-item__sub {
    font-size: 0.76rem;
    color: #64748b;
    font-weight: 500;
    margin-top: 1px;
}
/* Highlight matches */
:deep(mark) {
    background: #fef08a;
    color: #78350f;
    border-radius: 2px;
    padding: 0 1px;
}

.cp-item__desc {
    font-size: 0.74rem;
    color: #64748b;
    max-width: 200px;
    text-align: right;
    line-height: 1.4;
    display: none;
}
@media (min-width: 520px) {
    .cp-item__desc { display: block; }
}

.cp-item__arrow {
    flex-shrink: 0;
    opacity: 0;
    transition: opacity 0.1s, transform 0.15s;
    transform: translateX(-4px);
}
.cp-item.item--selected .cp-item__arrow {
    opacity: 1;
    transform: translateX(0);
}

/* ── Footer ── */
.cp-footer {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 10px 18px;
    border-top: 1.5px solid #f1f5f9;
    background: #fafbff;
}

/* ── Animaciones ── */
.cp-fade-enter-active, .cp-fade-leave-active { transition: opacity 0.18s; }
.cp-fade-enter-from, .cp-fade-leave-to { opacity: 0; }

.cp-slide-enter-active { transition: opacity 0.2s, transform 0.2s cubic-bezier(0.34,1.56,0.64,1); }
.cp-slide-leave-active { transition: opacity 0.15s, transform 0.15s ease-in; }
.cp-slide-enter-from { opacity: 0; transform: translateY(-12px) scale(0.97); }
.cp-slide-leave-to   { opacity: 0; transform: translateY(-8px) scale(0.97); }

.cp-desc-enter-active, .cp-desc-leave-active { transition: opacity 0.15s; }
.cp-desc-enter-from, .cp-desc-leave-to { opacity: 0; }
</style>
