<script setup>
import { ref, computed, onMounted } from 'vue';
import axios from 'axios';
import swal from 'sweetalert';
import { usePage } from '@inertiajs/vue3';

const page   = usePage();
const userId = computed(() => page.props.auth?.user?.id);

// ── Estado ────────────────────────────────────────────────────────────────────
const AlumnosEstadoPagar = ref([]);
const statusMap          = ref({});   // { alumno_id: 'Vencido'|'Esta Semana'|'Próximo' }
const cuentaDeposito     = ref([]);
const cargando           = ref(false);
const busqueda           = ref('');
const filtroDiplomado    = ref(null);
const filtroStatus       = ref('todos');

// ── Modal de Caja ─────────────────────────────────────────────────────────────
const mostrarModal   = ref(false);
const alumnoActivo   = ref(null);
const loadingModal   = ref(false);
const planData       = ref(null);
const historialPagos = ref([]);
const montoCobrar    = ref(null);
const selectedTitular= ref(null);
const comprobante    = ref(null);
const fechaCobro     = ref('');
const procesando     = ref(false);
const snack          = ref(false);
const snackMsg       = ref('');
const snackColor     = ref('success');

const toast = (msg, color = 'success') => { snackMsg.value = msg; snackColor.value = color; snack.value = true; };

// ── Computed ──────────────────────────────────────────────────────────────────
const alumnosCalculados = computed(() => {
    let items = AlumnosEstadoPagar.value;
    if (busqueda.value.trim()) {
        const q = busqueda.value.toLowerCase();
        items = items.filter(a =>
            a.nombre_completo?.toLowerCase().includes(q) ||
            a.nombre_diplomado?.toLowerCase().includes(q)
        );
    }
    if (filtroDiplomado.value) items = items.filter(a => a.nombre_diplomado === filtroDiplomado.value);
    if (filtroStatus.value !== 'todos') items = items.filter(a => statusMap.value[a.alumno_id] === filtroStatus.value);
    return items;
});

const listaDiplomadosUnicos = computed(() => {
    const s = new Set(AlumnosEstadoPagar.value.map(a => a.nombre_diplomado).filter(Boolean));
    return Array.from(s).sort();
});

const totalSaldo     = computed(() => alumnosCalculados.value.reduce((s, a) => s + parseFloat(a.Pendiente_Pagar || 0), 0));
const vencidosCount  = computed(() => alumnosCalculados.value.filter(a => statusMap.value[a.alumno_id] === 'Vencido').length);
const semanaCount    = computed(() => alumnosCalculados.value.filter(a => statusMap.value[a.alumno_id] === 'Esta Semana').length);

const proximaCuota = computed(() => {
    if (!planData.value?.plan_pagos?.length) return null;
    return planData.value.plan_pagos.find(c => c.estado === 'pendiente') || null;
});

// Datos para exportar (expuestos al padre)
const datosExportables = computed(() => alumnosCalculados.value.map((a, i) => ({
    'No.':         i + 1,
    'Alumno':      a.nombre_completo,
    'Diplomado':   a.nombre_diplomado,
    'Campaña':     a.campaña,
    'Grupo':       a.grupo,
    'Saldo ($)':   Number(a.Pendiente_Pagar),
    'Estado':      statusMap.value[a.alumno_id] || '—',
})));

// ── Helpers Visuales ─────────────────────────────────────────────────────────
const statusColor = (id) => ({ 'Vencido': 'error', 'Esta Semana': 'orange-darken-1', 'Próximo': 'success' }[statusMap.value[id]] ?? 'grey');
const statusLabel = (id) => statusMap.value[id] ?? 'Sin dato';
const statusIcon  = (id) => ({ 'Vencido': 'mdi-alert-circle', 'Esta Semana': 'mdi-clock-alert-outline', 'Próximo': 'mdi-check-circle-outline' }[statusMap.value[id]] ?? 'mdi-minus-circle-outline');

const cuotaBgClass = (cuota) => {
    if (cuota.estado === 'pagado') return 'cuota-pagada';
    const vencida = new Date(cuota.fecha + 'T12:00:00') < new Date();
    return vencida ? 'cuota-vencida' : 'cuota-pendiente';
};
const cuotaIcono = (cuota) => {
    if (cuota.estado === 'pagado') return { icon: 'mdi-check-circle', color: 'success' };
    return new Date(cuota.fecha + 'T12:00:00') < new Date()
        ? { icon: 'mdi-alert-circle', color: 'error' }
        : { icon: 'mdi-clock-outline', color: 'blue' };
};
const initials    = (name) => (name || '?').split(' ').filter(w => w).slice(0, 2).map(w => w[0]).join('').toUpperCase();
const avatarBg    = (name) => {
    const colors = ['#3730a3','#1d4ed8','#0f766e','#7c3aed','#c2410c','#15803d'];
    return colors[Math.abs(((name || 'A').charCodeAt(0) - 65)) % colors.length];
};

// ── Carga de Datos ────────────────────────────────────────────────────────────
const setFechaActual = () => {
    const t = new Date();
    fechaCobro.value = `${t.getFullYear()}-${String(t.getMonth()+1).padStart(2,'0')}-${String(t.getDate()).padStart(2,'0')}`;
};

const obtenerListaAlumnos = async () => {
    cargando.value = true;
    try {
        const [listRes, calRes] = await Promise.all([
            axios.get('/api/v1/directorio/pagos/mensualidades'),
            axios.get('/api/v1/pagos/calendario'),
        ]);
        AlumnosEstadoPagar.value = listRes.data.AlumnosEstadoPagar || [];
        const map = {};
        (calRes.data.vencidos    || []).forEach(a => { map[a.id] = 'Vencido'; });
        (calRes.data.esta_semana || []).forEach(a => { map[a.id] = 'Esta Semana'; });
        (calRes.data.proximos    || []).forEach(a => { map[a.id] = 'Próximo'; });
        statusMap.value = map;
    } catch (e) { console.error(e); }
    finally { cargando.value = false; }
};

const obtenerNumeroCuenta = async () => {
    try {
        const res = await axios.get('/api/v1/cuentadeposito/index/2024/numero');
        cuentaDeposito.value = res.data.cuentaDeposito || [];
    } catch (e) { console.error(e); }
};

// ── Terminal de Caja ──────────────────────────────────────────────────────────
const abrirCaja = async (alumno) => {
    alumnoActivo.value    = alumno;
    planData.value        = null;
    historialPagos.value  = [];
    montoCobrar.value     = null;
    selectedTitular.value = null;
    comprobante.value     = null;
    loadingModal.value    = true;
    mostrarModal.value    = true;

    try {
        const [planRes, histRes] = await Promise.all([
            axios.get(`/api/v1/alumno/${alumno.alumno_id}/plan-pagos`),
            axios.get(`/api/v1/mostrar/alumno/status/${alumno.alumno_id}`),
        ]);
        planData.value       = planRes.data;
        historialPagos.value = histRes.data.pagosColegiaturaAlumno2 || [];
        // Pre-llena con la próxima cuota pendiente
        const next = planRes.data.plan_pagos?.find(c => c.estado === 'pendiente');
        if (next) montoCobrar.value = Number(next.monto);
    } catch (e) { console.error(e); }
    finally { loadingModal.value = false; }
};

const enviarPago = async () => {
    if (!montoCobrar.value || !selectedTitular.value) {
        swal("Campos Requeridos", "Ingresa el monto y selecciona la cuenta receptora.", "warning"); return;
    }
    if (Number(montoCobrar.value) <= 0) {
        swal("Monto Inválido", "El monto debe ser mayor a $0 MXN.", "warning"); return;
    }
    if (alumnoActivo.value && Number(montoCobrar.value) > Number(alumnoActivo.value.Pendiente_Pagar)) {
        swal("Excede el Saldo", `El máximo es $${Number(alumnoActivo.value.Pendiente_Pagar).toLocaleString('es-MX')} MXN.`, "error"); return;
    }

    procesando.value = true;
    const formData = new FormData();
    formData.append('Fecha_PrimerContacto', fechaCobro.value);
    formData.append('pago_colegiatura',     montoCobrar.value);
    formData.append('tutor',               userId.value);
    formData.append('status',              'Activo');
    formData.append('cuentadeposito',      selectedTitular.value);
    formData.append('alumno_id',           alumnoActivo.value.alumno_id);
    formData.append('diplomado_id',        alumnoActivo.value.diplomado_id);
    if (comprobante.value) formData.append('comprobante', comprobante.value);

    try {
        const res = await axios.post('api/v1/pagosabonos/crear', formData, {
            headers: { 'Content-Type': 'multipart/form-data' },
        });
        swal("¡Cobro Registrado!", "El abono fue aplicado correctamente al expediente.", "success");
        const idPago = res.data.pago?.id;
        if (idPago) setTimeout(() => window.open('/pagos/' + idPago + '/pdf', '_blank'), 800);
        await Promise.all([abrirCaja(alumnoActivo.value), obtenerListaAlumnos()]);
    } catch (e) {
        swal("Error en Caja", e.response?.data?.error ?? "Error al procesar el pago.", "error");
    } finally {
        procesando.value = false;
    }
};

const descargarPDF = (id) => window.open('/pagos/' + id + '/pdf', '_blank');

// ── Expuesto al padre ─────────────────────────────────────────────────────────
const forzarBusqueda = (nombre) => {
    busqueda.value = nombre;
    const match = alumnosCalculados.value.find(a => a.nombre_completo === nombre);
    if (match) setTimeout(() => abrirCaja(match), 100);
};

defineExpose({ forzarBusqueda, datosExportables });

onMounted(() => {
    setFechaActual();
    obtenerListaAlumnos();
    obtenerNumeroCuenta();
});
</script>

<template>
  <div class="bg-transparent">

    <!-- ══ KPI Strip ══════════════════════════════════════════════════════════ -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 mb-4">

      <div class="kpi-card">
        <div class="kpi-icon bg-indigo-100"><v-icon color="indigo-darken-2" size="20">mdi-account-group</v-icon></div>
        <div>
          <div class="kpi-label">En Cartera</div>
          <div class="kpi-value text-indigo-800">{{ alumnosCalculados.length }}</div>
        </div>
      </div>

      <div class="kpi-card">
        <div class="kpi-icon bg-red-100"><v-icon color="red-darken-2" size="20">mdi-currency-usd</v-icon></div>
        <div>
          <div class="kpi-label">Total Pendiente</div>
          <div class="kpi-value text-red-700">${{ Math.round(totalSaldo).toLocaleString('es-MX') }}</div>
        </div>
      </div>

      <div class="kpi-card cursor-pointer" @click="filtroStatus = filtroStatus === 'Vencido' ? 'todos' : 'Vencido'">
        <div class="kpi-icon bg-red-50"><v-icon color="red" size="20">mdi-alert-circle</v-icon></div>
        <div>
          <div class="kpi-label">Vencidos</div>
          <div class="kpi-value text-red-600">{{ vencidosCount }}</div>
        </div>
      </div>

      <div class="kpi-card cursor-pointer" @click="filtroStatus = filtroStatus === 'Esta Semana' ? 'todos' : 'Esta Semana'">
        <div class="kpi-icon bg-orange-50"><v-icon color="orange-darken-1" size="20">mdi-clock-alert-outline</v-icon></div>
        <div>
          <div class="kpi-label">Esta Semana</div>
          <div class="kpi-value text-orange-600">{{ semanaCount }}</div>
        </div>
      </div>

    </div>

    <!-- ══ Barra de Búsqueda y Filtros ════════════════════════════════════════ -->
    <div class="flex flex-col md:flex-row gap-3 mb-4">

      <v-text-field
        v-model="busqueda"
        placeholder="Buscar alumno o programa..."
        variant="outlined"
        density="compact"
        hide-details
        prepend-inner-icon="mdi-magnify"
        clearable
        class="bg-white"
        style="max-width: 340px"
      />

      <v-select
        v-model="filtroDiplomado"
        :items="listaDiplomadosUnicos"
        label="Diplomado"
        variant="outlined"
        density="compact"
        hide-details
        clearable
        class="bg-white"
        style="max-width: 280px"
      />

      <v-btn-toggle v-model="filtroStatus" mandatory variant="outlined" density="compact" rounded="lg" class="flex-shrink-0">
        <v-btn value="todos"       size="small" class="text-xs px-3">Todos</v-btn>
        <v-btn value="Vencido"     size="small" class="text-xs px-3" color="error">Vencidos</v-btn>
        <v-btn value="Esta Semana" size="small" class="text-xs px-3" color="orange">Esta Semana</v-btn>
        <v-btn value="Próximo"     size="small" class="text-xs px-3" color="success">Próximos</v-btn>
      </v-btn-toggle>

      <v-progress-circular v-if="cargando" indeterminate color="indigo" size="24" class="ml-2 self-center" />

    </div>

    <!-- ══ Tabla Principal ════════════════════════════════════════════════════ -->
    <v-card variant="outlined" class="border-gray-200 overflow-hidden rounded-xl">
      <v-data-table
        :headers="[
          { title: '', key: 'avatar',          sortable: false, width: '52px' },
          { title: 'Alumno',                   key: 'nombre_completo',  sortable: true },
          { title: 'Programa',                 key: 'nombre_diplomado', sortable: true },
          { title: 'Estado',                   key: 'estado',           sortable: false, align: 'center', width: '130px' },
          { title: 'Saldo Pendiente',          key: 'Pendiente_Pagar',  sortable: true,  align: 'end',    width: '150px' },
          { title: '',                         key: 'acciones',         sortable: false, align: 'center', width: '120px' },
        ]"
        :items="alumnosCalculados"
        density="comfortable"
        hover
        fixed-header
        height="520"
        :items-per-page="50"
        :loading="cargando"
        class="caja-tabla"
      >
        <!-- Avatar + iniciales -->
        <template #item.avatar="{ item }">
          <v-avatar :style="{ background: avatarBg(item.nombre_completo) }" size="34">
            <span class="text-white text-xs font-bold">{{ initials(item.nombre_completo) }}</span>
          </v-avatar>
        </template>

        <!-- Nombre + programa (responsive) -->
        <template #item.nombre_completo="{ item }">
          <div class="font-semibold text-gray-900 leading-tight">{{ item.nombre_completo }}</div>
          <div class="text-xs text-gray-400 md:hidden mt-0.5">{{ item.nombre_diplomado }}</div>
        </template>

        <!-- Programa -->
        <template #item.nombre_diplomado="{ item }">
          <div class="text-xs text-indigo-700 font-medium leading-snug">{{ item.nombre_diplomado }}</div>
          <div class="text-[0.65rem] text-gray-400">{{ item.campaña }} · {{ item.grupo }}</div>
        </template>

        <!-- Status -->
        <template #item.estado="{ item }">
          <v-chip
            :color="statusColor(item.alumno_id)"
            size="x-small"
            variant="flat"
            :prepend-icon="statusIcon(item.alumno_id)"
          >
            {{ statusLabel(item.alumno_id) }}
          </v-chip>
        </template>

        <!-- Saldo -->
        <template #item.Pendiente_Pagar="{ item }">
          <div class="text-right font-black text-base"
               :class="statusMap[item.alumno_id] === 'Vencido' ? 'text-red-600' : 'text-gray-800'">
            ${{ Number(item.Pendiente_Pagar).toLocaleString('es-MX') }}
          </div>
        </template>

        <!-- Acción COBRAR -->
        <template #item.acciones="{ item }">
          <v-btn
            color="green-darken-1"
            variant="flat"
            size="small"
            rounded="lg"
            prepend-icon="mdi-point-of-sale"
            @click="abrirCaja(item)"
          >
            Cobrar
          </v-btn>
        </template>

        <template #no-data>
          <div class="py-12 text-center text-gray-400">
            <v-icon size="48" class="mb-3 opacity-30">mdi-account-search-outline</v-icon>
            <p class="text-sm">Sin resultados. Ajusta los filtros.</p>
          </div>
        </template>
      </v-data-table>
    </v-card>

    <!-- ══ MODAL TERMINAL DE CAJA ERP ════════════════════════════════════════ -->
    <v-dialog v-model="mostrarModal" max-width="1000" scrollable>
      <v-card rounded="xl" class="overflow-hidden" v-if="alumnoActivo">

        <!-- Header -->
        <div class="caja-modal-header">
          <div class="flex items-center gap-3">
            <div class="w-11 h-11 rounded-xl flex items-center justify-center text-white text-sm font-black"
                 :style="{ background: avatarBg(alumnoActivo.nombre_completo) }">
              {{ initials(alumnoActivo.nombre_completo) }}
            </div>
            <div>
              <div class="text-white font-bold text-base leading-tight">{{ alumnoActivo.nombre_completo }}</div>
              <div class="text-indigo-200 text-xs mt-0.5">{{ alumnoActivo.nombre_diplomado }} · {{ alumnoActivo.campaña }} {{ alumnoActivo.grupo }}</div>
            </div>
          </div>

          <div class="flex items-center gap-3">
            <v-chip
              :color="statusColor(alumnoActivo.alumno_id)"
              size="small"
              variant="flat"
              :prepend-icon="statusIcon(alumnoActivo.alumno_id)"
            >
              {{ statusLabel(alumnoActivo.alumno_id) }}
            </v-chip>
            <div class="text-right">
              <div class="text-xs text-indigo-300">Saldo pendiente</div>
              <div class="text-xl font-black text-red-300">${{ Number(alumnoActivo.Pendiente_Pagar).toLocaleString('es-MX') }}</div>
            </div>
            <v-btn icon="mdi-close" variant="text" color="white" size="small" @click="mostrarModal = false" />
          </div>
        </div>

        <v-card-text class="p-0 bg-gray-50">
          <div v-if="loadingModal" class="flex items-center justify-center py-20">
            <v-progress-circular indeterminate color="indigo" size="48" />
            <span class="ml-4 text-gray-500 font-medium">Cargando expediente...</span>
          </div>

          <div v-else class="grid grid-cols-1 lg:grid-cols-5 gap-0">

            <!-- ═══ COL IZQUIERDA: Plan de Pagos (3 cols) ════════════════════ -->
            <div class="lg:col-span-3 border-r border-gray-200 bg-white">
              <div class="px-5 py-3 border-b border-gray-100 flex items-center justify-between">
                <h4 class="font-bold text-gray-700 text-sm flex items-center gap-2">
                  <v-icon size="16" color="indigo">mdi-calendar-multiselect</v-icon>
                  Plan de Pagos
                </h4>
                <div v-if="planData?.plan_pagos?.length" class="text-xs text-gray-400">
                  {{ planData.plan_pagos.filter(c => c.estado === 'pagado').length }} / {{ planData.plan_pagos.length }} pagadas
                </div>
              </div>

              <!-- Plan de pagos -->
              <div class="plan-lista px-4 py-3 space-y-1.5 max-h-64 overflow-y-auto">
                <div v-if="!planData?.plan_pagos?.length" class="text-center py-6 text-gray-400 text-sm">
                  <v-icon size="32" class="mb-2 opacity-30">mdi-calendar-remove</v-icon>
                  <p>Sin plan de pagos registrado.</p>
                </div>

                <div
                  v-for="cuota in planData?.plan_pagos"
                  :key="cuota.numero"
                  class="plan-cuota"
                  :class="[cuotaBgClass(cuota), cuota === proximaCuota ? 'plan-cuota--next' : '']"
                >
                  <div class="flex items-center gap-1 shrink-0">
                    <v-icon :color="cuotaIcono(cuota).color" size="16">{{ cuotaIcono(cuota).icon }}</v-icon>
                    <span class="text-xs font-bold text-gray-500 w-5 text-right">{{ cuota.numero }}</span>
                  </div>
                  <div class="flex-1 min-w-0">
                    <div class="text-xs font-semibold text-gray-700">{{ cuota.descripcion }}</div>
                    <div class="text-[0.65rem] text-gray-400 font-mono">{{ cuota.fecha }}</div>
                  </div>
                  <div class="text-sm font-black shrink-0" :class="cuota.estado === 'pagado' ? 'text-green-600' : 'text-gray-800'">
                    ${{ Number(cuota.monto).toLocaleString('es-MX') }}
                  </div>
                  <v-chip v-if="cuota === proximaCuota" color="orange" size="x-small" variant="flat" class="shrink-0">
                    Siguiente
                  </v-chip>
                  <v-chip v-else-if="cuota.estado === 'pagado'" color="success" size="x-small" variant="flat" class="shrink-0">
                    Pagado
                  </v-chip>
                </div>
              </div>

              <!-- Historial de pagos -->
              <div class="px-5 py-3 border-t border-gray-100">
                <h4 class="font-bold text-gray-700 text-sm flex items-center gap-2 mb-2">
                  <v-icon size="16" color="green-darken-1">mdi-receipt-text-check</v-icon>
                  Historial de Abonos
                </h4>
                <div class="max-h-40 overflow-y-auto space-y-1">
                  <div v-if="!historialPagos.length" class="text-xs text-gray-400 text-center py-3">Sin abonos registrados.</div>
                  <div v-for="(p, i) in historialPagos" :key="i"
                       class="flex items-center gap-3 bg-green-50 border border-green-100 rounded-lg px-3 py-1.5">
                    <v-icon color="green-darken-1" size="14">mdi-cash-check</v-icon>
                    <div class="flex-1">
                      <span class="text-xs font-bold text-green-800">+${{ Number(p.pago_colegiatura).toLocaleString('es-MX') }} MXN</span>
                      <span class="text-[0.65rem] text-gray-500 ml-2">{{ p.Fecha_PrimerContacto }}</span>
                    </div>
                    <v-btn size="x-small" icon="mdi-file-pdf-box" variant="text" color="red" @click="descargarPDF(p.idpago)" />
                  </div>
                </div>
              </div>
            </div>

            <!-- ═══ COL DERECHA: Formulario de Cobro (2 cols) ════════════════ -->
            <div class="lg:col-span-2 p-5 flex flex-col gap-4">
              <h4 class="font-bold text-gray-700 text-sm flex items-center gap-2">
                <v-icon size="16" color="green-darken-1">mdi-cash-register</v-icon>
                Registrar Cobro
              </h4>

              <!-- Acceso rápido al monto -->
              <div>
                <div class="text-xs text-gray-500 mb-1.5 font-semibold uppercase tracking-wide">Monto rápido</div>
                <div class="flex flex-wrap gap-2">
                  <v-btn
                    v-if="proximaCuota"
                    size="small" variant="flat" color="indigo-darken-1" rounded="lg"
                    @click="montoCobrar = Number(proximaCuota.monto)"
                  >
                    <v-icon size="14" class="mr-1">mdi-calendar-check</v-icon>
                    Cuota: ${{ Number(proximaCuota?.monto || 0).toLocaleString('es-MX') }}
                  </v-btn>
                  <v-btn
                    size="small" variant="tonal" color="red" rounded="lg"
                    @click="montoCobrar = Number(alumnoActivo.Pendiente_Pagar)"
                  >
                    <v-icon size="14" class="mr-1">mdi-wallet</v-icon>
                    Liquidar: ${{ Number(alumnoActivo.Pendiente_Pagar).toLocaleString('es-MX') }}
                  </v-btn>
                </div>
              </div>

              <!-- Monto a cobrar -->
              <v-text-field
                v-model.number="montoCobrar"
                label="Importe a cobrar (MXN)"
                variant="outlined"
                density="comfortable"
                type="number"
                prefix="$"
                hide-details
                bg-color="white"
                :rules="[v => v > 0 || 'Debe ser mayor a 0']"
              />

              <!-- Cuenta receptora -->
              <v-select
                v-model="selectedTitular"
                :items="cuentaDeposito"
                item-title="banco"
                item-value="id"
                label="Cuenta Receptora"
                variant="outlined"
                density="comfortable"
                hide-details
                bg-color="white"
              >
                <template #item="{ props, item }">
                  <v-list-item v-bind="props" :title="item.raw.banco" :subtitle="item.raw.titular" />
                </template>
              </v-select>

              <!-- Fecha -->
              <v-text-field
                v-model="fechaCobro"
                label="Fecha de pago"
                variant="filled"
                density="comfortable"
                type="date"
                hide-details
                bg-color="blue-grey-lighten-5"
              />

              <!-- Comprobante -->
              <v-file-input
                v-model="comprobante"
                label="Comprobante (opcional)"
                variant="outlined"
                density="comfortable"
                prepend-icon=""
                prepend-inner-icon="mdi-paperclip"
                hide-details
                bg-color="white"
                accept="image/*,application/pdf"
              />

              <v-spacer />

              <!-- Botón COBRAR -->
              <v-btn
                color="green-darken-1"
                size="large"
                variant="flat"
                block
                rounded="lg"
                prepend-icon="mdi-printer-pos-check"
                :loading="procesando"
                class="text-base font-bold mt-2"
                @click="enviarPago"
              >
                Registrar Cobro + PDF
              </v-btn>

              <v-btn
                variant="text"
                color="grey-darken-2"
                size="small"
                block
                @click="mostrarModal = false"
              >
                Cerrar
              </v-btn>
            </div>

          </div>
        </v-card-text>
      </v-card>
    </v-dialog>

    <!-- Snackbar -->
    <v-snackbar v-model="snack" :color="snackColor" location="bottom right" :timeout="3000" rounded="xl">
      {{ snackMsg }}
    </v-snackbar>
  </div>
</template>

<style scoped>
/* ── KPI cards ── */
.kpi-card {
    display: flex;
    align-items: center;
    gap: 12px;
    background: white;
    border: 1px solid #e5e7eb;
    border-radius: 14px;
    padding: 14px 16px;
    box-shadow: 0 1px 4px rgba(0,0,0,0.04);
    transition: box-shadow 0.15s;
}
.kpi-card:hover { box-shadow: 0 3px 10px rgba(0,0,0,0.08); }
.kpi-icon { border-radius: 10px; padding: 8px; display: flex; }
.kpi-label { font-size: 0.7rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em; color: #9ca3af; }
.kpi-value { font-size: 1.25rem; font-weight: 800; line-height: 1.2; }

/* ── Tabla ── */
.caja-tabla :deep(.v-data-table__tr):hover td { background: #f0f9ff; }
.caja-tabla :deep(.v-data-table-header) th { background: #f8fafc !important; font-size: 0.7rem; font-weight: 800; text-transform: uppercase; letter-spacing: 0.05em; color: #64748b; }

/* ── Modal header ── */
.caja-modal-header {
    background: linear-gradient(135deg, #1e1b4b 0%, #3730a3 100%);
    padding: 18px 22px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
}

/* ── Plan de pagos ── */
.plan-cuota {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 8px 12px;
    border-radius: 10px;
    border: 1px solid #e5e7eb;
    background: white;
    transition: all 0.15s;
}
.plan-cuota--next {
    border-color: #f97316;
    background: #fff7ed;
    box-shadow: 0 0 0 2px rgba(249,115,22,0.15);
}
.cuota-pagada  { opacity: 0.55; }
.cuota-vencida { border-color: #fca5a5; background: #fff1f2; }
.cuota-pendiente { }
</style>
