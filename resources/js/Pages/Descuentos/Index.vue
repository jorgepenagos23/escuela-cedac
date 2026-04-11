<script setup>
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import { Head, useForm, router } from "@inertiajs/vue3";
import { ref, computed, watch } from "vue";

// ── Props ─────────────────────────────────────────────────────────────────────
const props = defineProps({
    descuentos: Array,
    diplomados: Array,
    tutores:    Array,
});

// ── Filtros de la lista ───────────────────────────────────────────────────────
const filtroEstado  = ref('todos');
const filtroTexto   = ref('');
const filtroPagina  = ref(1);
const POR_PAGINA    = 10;

const descuentosFiltrados = computed(() => {
    let lista = props.descuentos;
    if (filtroEstado.value !== 'todos') lista = lista.filter(d => d.estado === filtroEstado.value);
    if (filtroTexto.value.trim()) {
        const q = filtroTexto.value.toLowerCase();
        lista = lista.filter(d =>
            d.nombre.toLowerCase().includes(q) ||
            (d.descripcion ?? '').toLowerCase().includes(q) ||
            (d.diplomado_nombre ?? '').toLowerCase().includes(q) ||
            (d.tutor_nombre ?? '').toLowerCase().includes(q)
        );
    }
    return lista;
});

const totalPaginas  = computed(() => Math.ceil(descuentosFiltrados.value.length / POR_PAGINA));
const descuentosPagina = computed(() => {
    const start = (filtroPagina.value - 1) * POR_PAGINA;
    return descuentosFiltrados.value.slice(start, start + POR_PAGINA);
});
watch([filtroEstado, filtroTexto], () => filtroPagina.value = 1);

// ── Modal crear/editar ────────────────────────────────────────────────────────
const modalAbierto  = ref(false);
const modoEdicion   = ref(false);
const descSelec     = ref(null);

const form = useForm({
    nombre:       '',
    descripcion:  '',
    tipo:         'porcentaje',
    valor:        '',
    aplica_a:     'general',
    diplomado_id: null,
    tutor_id:     null,
    fecha_inicio: '',
    fecha_fin:    '',
});

const abrirCrear = () => {
    modoEdicion.value = false;
    form.reset();
    form.tipo     = 'porcentaje';
    form.aplica_a = 'general';
    modalAbierto.value = true;
};

const abrirEditar = (d) => {
    modoEdicion.value = true;
    descSelec.value   = d;
    form.nombre       = d.nombre;
    form.descripcion  = d.descripcion ?? '';
    form.tipo         = d.tipo;
    form.valor        = d.valor;
    form.aplica_a     = d.aplica_a;
    form.diplomado_id = d.diplomado_id;
    form.tutor_id     = d.tutor_id;
    form.fecha_inicio = d.fecha_inicio;
    form.fecha_fin    = d.fecha_fin;
    modalAbierto.value = true;
};

const guardar = () => {
    if (modoEdicion.value) {
        form.put(route('descuentos.update', descSelec.value.id), {
            onSuccess: () => { modalAbierto.value = false; },
        });
    } else {
        form.post(route('descuentos.store'), {
            onSuccess: () => { modalAbierto.value = false; form.reset(); },
        });
    }
};

// ── Cambiar Estado ────────────────────────────────────────────────────────────
const menuEstado   = ref(false);
const descAccion   = ref(null);

const confirmarEstado = (d, estado) => {
    router.patch(route('descuentos.estado', d.id), { estado }, {
        preserveScroll: true,
    });
};

const eliminar = (d) => {
    if (!confirm(`¿Eliminar el descuento "${d.nombre}"? Esta acción no se puede deshacer.`)) return;
    router.delete(route('descuentos.destroy', d.id), { preserveScroll: true });
};

// ── Helpers visuales ──────────────────────────────────────────────────────────
const colorEstado = { activo: 'success', suspendido: 'warning', cancelado: 'error' };
const iconEstado  = { activo: 'mdi-check-circle', suspendido: 'mdi-pause-circle', cancelado: 'mdi-cancel' };
const colorAplica = { general: 'indigo', diplomado: 'blue', tutor: 'teal' };
const iconAplica  = { general: 'mdi-earth', diplomado: 'mdi-certificate-outline', tutor: 'mdi-account-tie' };
const labelAplica = { general: 'General', diplomado: 'Por Diplomado', tutor: 'Por Tutor' };

const stats = computed(() => ({
    total:      props.descuentos.length,
    activos:    props.descuentos.filter(d => d.estado === 'activo').length,
    vigentes:   props.descuentos.filter(d => d.vigente).length,
    suspendidos:props.descuentos.filter(d => d.estado === 'suspendido').length,
}));

const diasRestantes = (fechaFin) => {
    const fn = new Date(fechaFin + 'T23:59:59');
    const hoy = new Date();
    return Math.ceil((fn - hoy) / (1000 * 3600 * 24));
};

const previewDescuento = computed(() => {
    if (!form.valor || !form.tipo) return null;
    const base = 7000;
    if (form.tipo === 'porcentaje') {
        const ahorro = Math.round(base * form.valor / 100);
        return { base, ahorro, final: base - ahorro };
    }
    const ahorro = Math.min(base, parseFloat(form.valor));
    return { base, ahorro, final: base - ahorro };
});
</script>

<template>
    <AuthenticatedLayout>
        <Head title="Lista de Descuentos" />

        <div class="min-h-screen bg-gradient-to-br from-slate-50 to-indigo-50 py-8">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">

                <!-- ── Encabezado ── -->
                <div class="flex items-start justify-between mb-8 flex-wrap gap-4">
                    <div class="flex items-center gap-4">
                        <div class="header-icon">
                            <v-icon color="white" size="26">mdi-tag-multiple</v-icon>
                        </div>
                        <div>
                            <h1 class="text-2xl font-bold text-gray-900">Lista de Descuentos</h1>
                            <p class="text-sm text-gray-500 mt-0.5">Gestiona los descuentos de temporada aplicables en admisiones.</p>
                        </div>
                    </div>
                    <v-btn
                        color="indigo-darken-2"
                        variant="flat"
                        prepend-icon="mdi-plus"
                        rounded="lg"
                        @click="abrirCrear"
                    >Nuevo Descuento</v-btn>
                </div>

                <!-- ── Cards de estadísticas ── -->
                <div class="stats-grid mb-8">
                    <div class="stat-card">
                        <div class="stat-icon bg-indigo-50 text-indigo-600"><v-icon>mdi-tag-multiple</v-icon></div>
                        <div>
                            <div class="stat-num">{{ stats.total }}</div>
                            <div class="stat-lbl">Total registrados</div>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon bg-green-50 text-green-600"><v-icon>mdi-check-circle</v-icon></div>
                        <div>
                            <div class="stat-num text-green-700">{{ stats.activos }}</div>
                            <div class="stat-lbl">Activos</div>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon bg-blue-50 text-blue-600"><v-icon>mdi-lightning-bolt</v-icon></div>
                        <div>
                            <div class="stat-num text-blue-700">{{ stats.vigentes }}</div>
                            <div class="stat-lbl">Vigentes hoy</div>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon bg-orange-50 text-orange-500"><v-icon>mdi-pause-circle</v-icon></div>
                        <div>
                            <div class="stat-num text-orange-500">{{ stats.suspendidos }}</div>
                            <div class="stat-lbl">Suspendidos</div>
                        </div>
                    </div>
                </div>

                <!-- ── Filtros ── -->
                <div class="filters-bar mb-5">
                    <v-text-field
                        v-model="filtroTexto"
                        placeholder="Buscar descuento..."
                        variant="solo"
                        hide-details
                        density="compact"
                        prepend-inner-icon="mdi-magnify"
                        rounded="lg"
                        class="max-w-xs"
                        bg-color="white"
                    />
                    <v-btn-toggle v-model="filtroEstado" mandatory variant="outlined" divided rounded="lg" density="compact" color="indigo-darken-2">
                        <v-btn value="todos"      size="small">Todos</v-btn>
                        <v-btn value="activo"     size="small">Activos</v-btn>
                        <v-btn value="suspendido" size="small">Suspendidos</v-btn>
                        <v-btn value="cancelado"  size="small">Cancelados</v-btn>
                    </v-btn-toggle>
                </div>

                <!-- ── Tabla de descuentos ── -->
                <div class="tabla-card">
                    <!-- Sin resultados -->
                    <div v-if="descuentosFiltrados.length === 0" class="tabla-vacia">
                        <v-icon size="56" color="indigo-lighten-3">mdi-tag-off-outline</v-icon>
                        <p class="mt-3 text-gray-400">No se encontraron descuentos con los filtros aplicados.</p>
                        <v-btn class="mt-3" size="small" color="indigo" variant="tonal" @click="abrirCrear">Crear primer descuento</v-btn>
                    </div>

                    <template v-else>
                        <!-- Cabecera tabla -->
                        <div class="tabla-header">
                            <span class="col-nombre">Nombre / Descripción</span>
                            <span class="col-valor">Descuento</span>
                            <span class="col-aplica">Aplica a</span>
                            <span class="col-periodo">Período</span>
                            <span class="col-estado">Estado</span>
                            <span class="col-acciones">Acciones</span>
                        </div>

                        <!-- Filas -->
                        <div
                            v-for="d in descuentosPagina"
                            :key="d.id"
                            class="tabla-fila"
                            :class="{ 'fila-vigente': d.vigente, 'fila-cancelada': d.estado === 'cancelado' }"
                        >
                            <!-- Nombre -->
                            <div class="col-nombre">
                                <div class="flex items-center gap-2">
                                    <div v-if="d.vigente" class="vigente-dot" title="Vigente hoy" />
                                    <div>
                                        <p class="font-semibold text-gray-800 text-sm">{{ d.nombre }}</p>
                                        <p v-if="d.descripcion" class="text-xs text-gray-400 truncate max-w-xs mt-0.5">{{ d.descripcion }}</p>
                                        <p class="text-xs text-gray-300 mt-0.5">Creado por: {{ d.creado_por ?? '—' }} · {{ d.created_at }}</p>
                                    </div>
                                </div>
                            </div>

                            <!-- Valor -->
                            <div class="col-valor">
                                <div class="descuento-badge">
                                    <v-icon size="14" class="mr-0.5">{{ d.tipo === 'porcentaje' ? 'mdi-percent' : 'mdi-currency-usd' }}</v-icon>
                                    {{ d.etiqueta }}
                                </div>
                                <p class="text-xs text-gray-400 mt-0.5">
                                    {{ d.tipo === 'porcentaje' ? 'Porcentaje' : 'Monto fijo' }}
                                </p>
                            </div>

                            <!-- Aplica a -->
                            <div class="col-aplica">
                                <v-chip size="x-small" :color="colorAplica[d.aplica_a]" variant="tonal" :prepend-icon="iconAplica[d.aplica_a]">
                                    {{ labelAplica[d.aplica_a] }}
                                </v-chip>
                                <p v-if="d.diplomado_nombre" class="text-xs text-gray-500 mt-1">{{ d.diplomado_nombre }}</p>
                                <p v-if="d.tutor_nombre"     class="text-xs text-gray-500 mt-1">{{ d.tutor_nombre }}</p>
                            </div>

                            <!-- Período -->
                            <div class="col-periodo">
                                <p class="text-xs font-mono text-gray-700">{{ d.fecha_inicio }}</p>
                                <p class="text-xs text-gray-400">hasta</p>
                                <p class="text-xs font-mono text-gray-700">{{ d.fecha_fin }}</p>
                                <p v-if="d.vigente && diasRestantes(d.fecha_fin) >= 0" class="text-xs text-blue-500 font-semibold mt-1">
                                    {{ diasRestantes(d.fecha_fin) }} día(s) restante(s)
                                </p>
                                <p v-else-if="d.estado === 'activo' && diasRestantes(d.fecha_fin) < 0" class="text-xs text-red-400 mt-1">
                                    Venció hace {{ Math.abs(diasRestantes(d.fecha_fin)) }} día(s)
                                </p>
                            </div>

                            <!-- Estado -->
                            <div class="col-estado">
                                <v-chip :color="colorEstado[d.estado]" size="small" variant="flat" :prepend-icon="iconEstado[d.estado]">
                                    {{ d.estado }}
                                </v-chip>
                            </div>

                            <!-- Acciones -->
                            <div class="col-acciones">
                                <v-menu>
                                    <template #activator="{ props: menuProps }">
                                        <v-btn v-bind="menuProps" icon="mdi-dots-vertical" size="small" variant="text" />
                                    </template>
                                    <v-list density="compact" min-width="180">
                                        <v-list-item prepend-icon="mdi-pencil" title="Editar" @click="abrirEditar(d)" :disabled="d.estado === 'cancelado'" />
                                        <v-divider class="my-1" />
                                        <v-list-item v-if="d.estado !== 'activo'"     prepend-icon="mdi-check-circle" title="Activar"    @click="confirmarEstado(d, 'activo')"     color="success" />
                                        <v-list-item v-if="d.estado === 'activo'"     prepend-icon="mdi-pause-circle" title="Suspender"  @click="confirmarEstado(d, 'suspendido')" color="warning" />
                                        <v-list-item v-if="d.estado !== 'cancelado'"  prepend-icon="mdi-cancel"       title="Cancelar"   @click="confirmarEstado(d, 'cancelado')"  color="error" />
                                        <v-divider v-if="d.estado === 'cancelado'" class="my-1" />
                                        <v-list-item v-if="d.estado === 'cancelado'"  prepend-icon="mdi-trash-can-outline" title="Eliminar" @click="eliminar(d)" color="error" />
                                    </v-list>
                                </v-menu>
                            </div>
                        </div>

                        <!-- Paginación -->
                        <div v-if="totalPaginas > 1" class="tabla-footer">
                            <span class="text-xs text-gray-400">
                                {{ (filtroPagina - 1) * POR_PAGINA + 1 }}–{{ Math.min(filtroPagina * POR_PAGINA, descuentosFiltrados.length) }}
                                de {{ descuentosFiltrados.length }}
                            </span>
                            <v-pagination v-model="filtroPagina" :length="totalPaginas" size="small" density="compact" color="indigo" />
                        </div>
                    </template>
                </div>

            </div>
        </div>

        <!-- ══════════════════════════════════════════════════════════════════ -->
        <!-- Modal Crear / Editar                                               -->
        <!-- ══════════════════════════════════════════════════════════════════ -->
        <v-dialog v-model="modalAbierto" max-width="640" persistent>
            <v-card rounded="xl" elevation="6">
                <!-- Cabecera del modal -->
                <div class="modal-header">
                    <div class="flex items-center gap-3">
                        <v-icon color="white" size="22">mdi-tag-plus</v-icon>
                        <span class="text-white font-bold text-base">
                            {{ modoEdicion ? 'Editar Descuento' : 'Nuevo Descuento' }}
                        </span>
                    </div>
                    <v-btn icon="mdi-close" size="small" variant="text" color="white" @click="modalAbierto = false" />
                </div>

                <v-card-text class="pa-6">
                    <v-form @submit.prevent="guardar">

                        <!-- Fila 1: Nombre + Tipo -->
                        <div class="form-row">
                            <div class="flex-1">
                                <label class="field-label">Nombre del descuento *</label>
                                <v-text-field v-model="form.nombre" variant="outlined" density="compact" hide-details
                                    :error="!!form.errors.nombre" :error-messages="form.errors.nombre"
                                    placeholder="Ej: Descuento de primavera 2026" />
                            </div>
                        </div>

                        <div class="mb-4">
                            <label class="field-label">Descripción</label>
                            <v-textarea v-model="form.descripcion" variant="outlined" density="compact" hide-details rows="2"
                                placeholder="Descripción interna o condiciones del descuento..." />
                        </div>

                        <!-- Fila: Tipo + Valor -->
                        <div class="form-row mb-4">
                            <div class="w-44">
                                <label class="field-label">Tipo *</label>
                                <v-select v-model="form.tipo" :items="[
                                    { title: '% Porcentaje', value: 'porcentaje' },
                                    { title: '$ Monto fijo', value: 'monto_fijo' },
                                ]" item-title="title" item-value="value" variant="outlined" density="compact" hide-details />
                            </div>
                            <div class="flex-1">
                                <label class="field-label">
                                    {{ form.tipo === 'porcentaje' ? 'Porcentaje (%)' : 'Monto fijo (MXN)' }} *
                                </label>
                                <v-text-field v-model="form.valor" type="number" step="0.01" min="0.01"
                                    :prefix="form.tipo === 'monto_fijo' ? '$' : ''"
                                    :suffix="form.tipo === 'porcentaje' ? '%' : 'MXN'"
                                    variant="outlined" density="compact" hide-details />
                            </div>
                        </div>

                        <!-- Preview del descuento (estimado sobre $7,000) -->
                        <div v-if="previewDescuento" class="preview-desc mb-4">
                            <v-icon size="15" color="indigo" class="mr-1">mdi-eye</v-icon>
                            <span class="text-xs text-gray-500 mr-2">Ejemplo sobre $7,000:</span>
                            <span class="text-xs text-green-600 font-bold">−${{ previewDescuento.ahorro.toLocaleString('es-MX') }}</span>
                            <span class="text-xs text-gray-400 mx-1">→ final:</span>
                            <span class="text-xs font-bold text-indigo-700">${{ previewDescuento.final.toLocaleString('es-MX') }}</span>
                        </div>

                        <!-- Aplica a -->
                        <div class="mb-4">
                            <label class="field-label">Aplica a *</label>
                            <v-btn-toggle v-model="form.aplica_a" mandatory variant="outlined" divided rounded="lg" density="compact" color="indigo" class="mb-3">
                                <v-btn value="general"   size="small" prepend-icon="mdi-earth">General</v-btn>
                                <v-btn value="diplomado" size="small" prepend-icon="mdi-certificate-outline">Diplomado</v-btn>
                                <v-btn value="tutor"     size="small" prepend-icon="mdi-account-tie">Tutor</v-btn>
                            </v-btn-toggle>

                            <v-select v-if="form.aplica_a === 'diplomado'"
                                v-model="form.diplomado_id"
                                :items="diplomados" item-title="nombre" item-value="id"
                                label="Seleccionar diplomado" variant="outlined" density="compact" hide-details clearable
                            />
                            <v-select v-if="form.aplica_a === 'tutor'"
                                v-model="form.tutor_id"
                                :items="tutores" item-title="name" item-value="id"
                                label="Seleccionar tutor" variant="outlined" density="compact" hide-details clearable
                            />
                        </div>

                        <!-- Fechas -->
                        <div class="form-row mb-2">
                            <div class="flex-1">
                                <label class="field-label">Fecha inicio *</label>
                                <v-text-field v-model="form.fecha_inicio" type="date" variant="outlined" density="compact" hide-details
                                    :error="!!form.errors.fecha_inicio" :error-messages="form.errors.fecha_inicio" />
                            </div>
                            <div class="flex-1">
                                <label class="field-label">Fecha fin *</label>
                                <v-text-field v-model="form.fecha_fin" type="date" variant="outlined" density="compact" hide-details
                                    :error="!!form.errors.fecha_fin" :error-messages="form.errors.fecha_fin" />
                            </div>
                        </div>

                    </v-form>
                </v-card-text>

                <v-card-actions class="px-6 pb-5 pt-0 gap-2 justify-end">
                    <v-btn variant="text" @click="modalAbierto = false">Cancelar</v-btn>
                    <v-btn
                        color="indigo-darken-2" variant="flat" rounded="lg"
                        :loading="form.processing"
                        prepend-icon="mdi-content-save"
                        @click="guardar"
                    >{{ modoEdicion ? 'Guardar cambios' : 'Crear descuento' }}</v-btn>
                </v-card-actions>
            </v-card>
        </v-dialog>

    </AuthenticatedLayout>
</template>

<style scoped>
/* Header icon */
.header-icon {
    width: 50px; height: 50px;
    border-radius: 14px;
    background: linear-gradient(135deg, #4338ca, #7c3aed);
    display: flex; align-items: center; justify-content: center;
    box-shadow: 0 4px 14px rgba(79,70,229,0.35);
}

/* Stats */
.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));
    gap: 14px;
}
.stat-card {
    background: white;
    border-radius: 14px;
    border: 1px solid #e5e7eb;
    padding: 16px 18px;
    display: flex;
    align-items: center;
    gap: 14px;
    box-shadow: 0 1px 6px rgba(0,0,0,0.05);
}
.stat-icon {
    width: 44px; height: 44px;
    border-radius: 12px;
    display: flex; align-items: center; justify-content: center;
    flex-shrink: 0;
}
.stat-num { font-size: 1.5rem; font-weight: 800; line-height: 1; color: #1e293b; }
.stat-lbl { font-size: 0.72rem; color: #94a3b8; font-weight: 600; text-transform: uppercase; letter-spacing: 0.05em; margin-top: 2px; }

/* Filtros */
.filters-bar { display: flex; align-items: center; gap: 14px; flex-wrap: wrap; }

/* Tabla */
.tabla-card {
    background: white;
    border-radius: 18px;
    border: 1px solid #e5e7eb;
    overflow: hidden;
    box-shadow: 0 4px 16px rgba(0,0,0,0.07);
}
.tabla-vacia {
    display: flex; flex-direction: column;
    align-items: center; justify-content: center;
    padding: 56px 24px;
}
.tabla-header {
    display: grid;
    grid-template-columns: 2fr 1fr 1.2fr 1.2fr 100px 60px;
    gap: 12px;
    padding: 11px 20px;
    background: #f8fafc;
    font-size: 0.68rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.07em;
    color: #94a3b8;
    border-bottom: 1px solid #f1f5f9;
}
.tabla-fila {
    display: grid;
    grid-template-columns: 2fr 1fr 1.2fr 1.2fr 100px 60px;
    gap: 12px;
    align-items: center;
    padding: 14px 20px;
    border-bottom: 1px solid #f8fafc;
    transition: background 0.12s;
}
.tabla-fila:last-of-type { border-bottom: none; }
.tabla-fila:hover { background: #f9fafb; }
.fila-vigente { background: #f0fdf4 !important; border-left: 3px solid #22c55e; }
.fila-cancelada { opacity: 0.55; }

.vigente-dot {
    width: 8px; height: 8px; min-width: 8px;
    border-radius: 50%;
    background: #22c55e;
    box-shadow: 0 0 6px rgba(34,197,94,0.6);
}

.col-nombre, .col-valor, .col-aplica, .col-periodo, .col-estado, .col-acciones {}

/* Badge de descuento */
.descuento-badge {
    display: inline-flex;
    align-items: center;
    background: linear-gradient(135deg, #4338ca, #7c3aed);
    color: white;
    border-radius: 8px;
    padding: 3px 10px;
    font-size: 0.85rem;
    font-weight: 800;
}

/* Footer paginación */
.tabla-footer {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 10px 20px;
    border-top: 1px solid #f1f5f9;
    background: #fafbff;
}

/* Modal */
.modal-header {
    background: linear-gradient(135deg, #4338ca, #7c3aed);
    padding: 16px 20px;
    display: flex;
    justify-content: space-between;
    align-items: center;
}
.form-row { display: flex; gap: 14px; margin-bottom: 14px; }
.field-label {
    display: block;
    font-size: 0.72rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.06em;
    color: #64748b;
    margin-bottom: 5px;
}
.preview-desc {
    display: flex;
    align-items: center;
    background: #f0f4ff;
    border: 1px solid #c7d2fe;
    border-radius: 8px;
    padding: 6px 12px;
}
</style>
