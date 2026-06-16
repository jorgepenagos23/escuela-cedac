<script setup>
import { ref, computed, onMounted } from 'vue';
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import ErpTopbar from "@/Components/ErpTopbar.vue";
import { Head, usePage } from "@inertiajs/vue3";
import axios from 'axios';

// ─── Estado ────────────────────────────────────────────────────────────────────
const pagosOriginales = ref([]);
const cargando        = ref(false);
const snackbar        = ref(false);
const snackMsg        = ref('');
const snackColor      = ref('success');

const mostrarSnack = (msg, color = 'success') => {
    snackMsg.value = msg; snackColor.value = color; snackbar.value = true;
};

// ─── Filtros ───────────────────────────────────────────────────────────────────
const filtroBusqueda  = ref('');
const filtroDiplomado = ref('');
const filtroBanco     = ref('');
const filtroCajero    = ref('');
const filtroStatus    = ref('activo');
const filtroDesde     = ref('');
const filtroHasta     = ref('');

// ─── Catálogos para selects ────────────────────────────────────────────────────
const diplomadosUnicos = computed(() => [...new Set(pagosOriginales.value.map(p => p.diplomado).filter(Boolean))].sort());
const bancosUnicos     = computed(() => [...new Set(pagosOriginales.value.map(p => p.banco).filter(Boolean))].sort());
const cajerosUnicos    = computed(() => [...new Set(pagosOriginales.value.map(p => p.cajero).filter(Boolean))].sort());

// ─── Datos filtrados ───────────────────────────────────────────────────────────
const pagosFiltrados = computed(() => {
    let r = pagosOriginales.value;

    if (filtroStatus.value)
        r = r.filter(p => p.status === filtroStatus.value);

    if (filtroBusqueda.value.trim()) {
        const q = filtroBusqueda.value.toLowerCase();
        r = r.filter(p =>
            p.nombre_alumno?.toLowerCase().includes(q) ||
            String(p.id).includes(q) ||
            p.cajero?.toLowerCase().includes(q)
        );
    }
    if (filtroDiplomado.value)
        r = r.filter(p => p.diplomado === filtroDiplomado.value);
    if (filtroBanco.value)
        r = r.filter(p => p.banco === filtroBanco.value);
    if (filtroCajero.value)
        r = r.filter(p => p.cajero === filtroCajero.value);
    if (filtroDesde.value)
        r = r.filter(p => p.fecha_operacion >= filtroDesde.value);
    if (filtroHasta.value)
        r = r.filter(p => p.fecha_operacion <= filtroHasta.value);

    return r;
});

// ─── KPIs ──────────────────────────────────────────────────────────────────────
const kpiTotal    = computed(() => pagosFiltrados.value.filter(p => p.status === 'activo').reduce((s, p) => s + parseFloat(p.monto || 0), 0));
const kpiAbonos   = computed(() => pagosFiltrados.value.filter(p => p.status === 'activo').length);
const kpiAlumnos  = computed(() => new Set(pagosFiltrados.value.map(p => p.alumno_id)).size);
const kpiCancelados = computed(() => pagosFiltrados.value.filter(p => p.status === 'Cancelado').length);

const fmt = (n) => Number(n).toLocaleString('es-MX', { minimumFractionDigits: 2 });

// ─── Paginación ────────────────────────────────────────────────────────────────
const paginaActual   = ref(1);
const porPagina      = 30;
const totalPaginas   = computed(() => Math.ceil(pagosFiltrados.value.length / porPagina));
const registrosPag   = computed(() =>
    pagosFiltrados.value.slice((paginaActual.value - 1) * porPagina, paginaActual.value * porPagina)
);

// Resetear página al cambiar filtros
const resetPagina = () => { paginaActual.value = 1; };

// ─── Carga inicial ─────────────────────────────────────────────────────────────
const cargarPagos = async () => {
    cargando.value = true;
    try {
        const res = await axios.get('/api/v1/pagos/conciliacion');
        pagosOriginales.value = res.data.pagos;
    } catch (e) {
        mostrarSnack('Error al cargar los movimientos.', 'error');
    } finally {
        cargando.value = false;
    }
};

const limpiarFiltros = () => {
    filtroBusqueda.value  = '';
    filtroDiplomado.value = '';
    filtroBanco.value     = '';
    filtroCajero.value    = '';
    filtroStatus.value    = 'activo';
    filtroDesde.value     = '';
    filtroHasta.value     = '';
    resetPagina();
};

onMounted(cargarPagos);

// ─── PDF ───────────────────────────────────────────────────────────────────────
const descargarPDF = (id) => window.open('/pagos/' + id + '/pdf', '_blank');

// ─── Expediente ────────────────────────────────────────────────────────────────
const modalExpediente  = ref(false);
const expedienteAlumno = ref(null);
const historialPagos   = ref([]);
const cargandoExp      = ref(false);

const abrirExpediente = async (pago) => {
    expedienteAlumno.value = pago;
    historialPagos.value   = [];
    cargandoExp.value      = true;
    modalExpediente.value  = true;
    try {
        const res = await axios.get(`/api/v1/mostrar/alumno/status/${pago.alumno_id}`);
        historialPagos.value = res.data.pagosColegiaturaAlumno2 ?? [];
    } catch (e) {
        mostrarSnack('No se pudo cargar el expediente.', 'error');
    } finally {
        cargandoExp.value = false;
    }
};

// ─── Excel ─────────────────────────────────────────────────────────────────────
const exportarExcel = () => {
    import('xlsx').then(XLSX => {
        const datos = pagosFiltrados.value.map((p, i) => ({
            'No.':              i + 1,
            'Folio':            p.id,
            'Alumno':           p.nombre_alumno,
            'Diplomado':        p.diplomado,
            'Monto Abono':      Number(p.monto),
            'Saldo Actual':     Number(p.saldo_actual),
            'Fecha Operación':  p.fecha_operacion,
            'Fecha Registro':   p.fecha_ingreso ? p.fecha_ingreso.split('T')[0] : '',
            'Banco':            p.banco,
            'Titular Cuenta':   p.titular_cuenta,
            'No. Cuenta':       p.numero_cuenta,
            'Cajero':           p.cajero,
            'Status':           p.status,
        }));

        const ws = XLSX.utils.json_to_sheet(datos);

        // Anchos de columna automáticos
        ws['!cols'] = [
            { wch: 5 }, { wch: 8 }, { wch: 32 }, { wch: 28 }, { wch: 14 },
            { wch: 14 }, { wch: 14 }, { wch: 16 }, { wch: 20 }, { wch: 28 },
            { wch: 20 }, { wch: 22 }, { wch: 10 }
        ];

        const wb = XLSX.utils.book_new();
        XLSX.utils.book_append_sheet(wb, ws, 'Conciliacion');

        const fecha = new Date().toLocaleDateString('es-MX').replace(/\//g, '-');
        const filtros = [
            filtroDiplomado.value ? `_${filtroDiplomado.value.substring(0, 12)}` : '',
            filtroDesde.value ? `_desde${filtroDesde.value}` : '',
            filtroHasta.value ? `_hasta${filtroHasta.value}` : '',
        ].join('');

        XLSX.writeFile(wb, `Conciliacion_CEDAC${filtros}_${fecha}.xlsx`);
        mostrarSnack(`Excel generado con ${pagosFiltrados.value.length} movimientos.`, 'success');
    });
};
</script>

<template>
  <AuthenticatedLayout>
    <ErpTopbar modulo="Finanzas" titulo="Resumen de Colegiaturas Aplicadas" />
    <Head title="Conciliación de Colegiaturas" />

    <div class="erp-root">

      <!-- ── Título de módulo ── -->
      <div class="erp-module-header">
        <div class="erp-module-header__title">
          <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#1a3a5c" stroke-width="2" class="mr-2 flex-shrink-0"><rect x="2" y="3" width="20" height="14" rx="2"/><path d="M8 21h8M12 17v4"/><path d="M6 7h12M6 11h8"/></svg>
          Libro de Movimientos Contables — Conciliación
        </div>
        <div class="erp-module-header__sub">
          Filtra y exporta todos los abonos registrados por periodo, banco, cajero o diplomado para conciliar manualmente contra tu estado de cuenta bancario.
        </div>
      </div>

      <div class="erp-body">

        <!-- ══ KPI STRIP ══ -->
        <div class="kpi-strip">
          <div class="kpi-card kpi-card--blue">
            <div class="kpi-icon"><svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M12 8v4l3 3"/></svg></div>
            <div>
              <div class="kpi-val">${{ fmt(kpiTotal) }}</div>
              <div class="kpi-lbl">Total Recaudado</div>
              <div class="kpi-sub">abonos activos filtrados</div>
            </div>
          </div>
          <div class="kpi-card kpi-card--teal">
            <div class="kpi-icon"><svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2"><path d="M3 3h18v18H3z" rx="2"/><path d="M9 9h6M9 13h4"/></svg></div>
            <div>
              <div class="kpi-val">{{ kpiAbonos.toLocaleString('es-MX') }}</div>
              <div class="kpi-lbl">Movimientos</div>
              <div class="kpi-sub">abonos activos</div>
            </div>
          </div>
          <div class="kpi-card kpi-card--navy">
            <div class="kpi-icon"><svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2"><circle cx="9" cy="7" r="4"/><path d="M3 21v-2a4 4 0 0 1 4-4h4"/><circle cx="17" cy="17" r="4"/><path d="M17 14v3l2 2"/></svg></div>
            <div>
              <div class="kpi-val">{{ kpiAlumnos }}</div>
              <div class="kpi-lbl">Alumnos Únicos</div>
              <div class="kpi-sub">en selección actual</div>
            </div>
          </div>
          <div class="kpi-card kpi-card--red">
            <div class="kpi-icon"><svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="m15 9-6 6M9 9l6 6"/></svg></div>
            <div>
              <div class="kpi-val">{{ kpiCancelados }}</div>
              <div class="kpi-lbl">Cancelados</div>
              <div class="kpi-sub">en selección actual</div>
            </div>
          </div>
        </div>

        <!-- ══ PANEL DE FILTROS TIPO SAP ══ -->
        <div class="filtros-panel">
          <div class="filtros-panel__header">
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="#1a3a5c" stroke-width="2.5"><path d="M22 3H2l8 9.46V19l4 2v-8.54L22 3z"/></svg>
            <span>Parámetros de Selección</span>
            <span class="filtros-panel__count">{{ pagosFiltrados.length }} registros encontrados</span>
          </div>

          <div class="filtros-grid">
            <!-- Fila 1: Búsqueda + Diplomado + Banco + Cajero -->
            <div class="filtro-grupo filtro-grupo--wide">
              <label class="filtro-label">Alumno / Folio</label>
              <div class="filtro-input-wrap">
                <svg class="filtro-icon" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/></svg>
                <input v-model="filtroBusqueda" type="text" placeholder="Nombre del alumno o ID folio..." class="filtro-input" @input="resetPagina" />
              </div>
            </div>

            <div class="filtro-grupo">
              <label class="filtro-label">Diplomado / Programa</label>
              <select v-model="filtroDiplomado" class="filtro-select" @change="resetPagina">
                <option value="">Todos los programas</option>
                <option v-for="d in diplomadosUnicos" :key="d" :value="d">{{ d }}</option>
              </select>
            </div>

            <div class="filtro-grupo">
              <label class="filtro-label">Banco / Cuenta</label>
              <select v-model="filtroBanco" class="filtro-select" @change="resetPagina">
                <option value="">Todos los bancos</option>
                <option v-for="b in bancosUnicos" :key="b" :value="b">{{ b }}</option>
              </select>
            </div>

            <div class="filtro-grupo">
              <label class="filtro-label">Cajero / Gestor</label>
              <select v-model="filtroCajero" class="filtro-select" @change="resetPagina">
                <option value="">Todos los cajeros</option>
                <option v-for="c in cajerosUnicos" :key="c" :value="c">{{ c }}</option>
              </select>
            </div>

            <!-- Fila 2: Fechas + Status + Acciones -->
            <div class="filtro-grupo">
              <label class="filtro-label">Fecha Operación — Desde</label>
              <input v-model="filtroDesde" type="date" class="filtro-input filtro-input--date" @change="resetPagina" />
            </div>

            <div class="filtro-grupo">
              <label class="filtro-label">Fecha Operación — Hasta</label>
              <input v-model="filtroHasta" type="date" class="filtro-input filtro-input--date" @change="resetPagina" />
            </div>

            <div class="filtro-grupo">
              <label class="filtro-label">Status del Movimiento</label>
              <select v-model="filtroStatus" class="filtro-select" @change="resetPagina">
                <option value="">Todos</option>
                <option value="activo">Activo</option>
                <option value="Cancelado">Cancelado</option>
              </select>
            </div>

            <!-- Botones de acción -->
            <div class="filtro-grupo filtro-grupo--actions">
              <label class="filtro-label">&nbsp;</label>
              <div class="flex gap-2">
                <button class="sap-btn sap-btn--primary" @click="exportarExcel" :disabled="!pagosFiltrados.length">
                  <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><path d="M14 2v6h6M9 13l2 2 4-4"/></svg>
                  Exportar Excel
                </button>
                <button class="sap-btn sap-btn--ghost" @click="limpiarFiltros">
                  <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M3 6h18M8 6V4h8v2M19 6l-1 14H6L5 6"/></svg>
                  Limpiar
                </button>
                <button class="sap-btn sap-btn--outline" @click="cargarPagos" :disabled="cargando">
                  <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" :class="cargando ? 'spin' : ''"><path d="M23 4v6h-6"/><path d="M1 20v-6h6"/><path d="M3.51 9a9 9 0 0 1 14.85-3.36L23 10M1 14l4.64 4.36A9 9 0 0 0 20.49 15"/></svg>
                </button>
              </div>
            </div>
          </div>
        </div>

        <!-- ══ TABLA SAP ══ -->
        <div class="sap-table-wrap">
          <!-- Cabecera de tabla con info -->
          <div class="sap-table-topbar">
            <div class="sap-table-topbar__left">
              <span class="sap-table-topbar__title">Movimientos de Colegiaturas</span>
              <span class="sap-table-topbar__badge">{{ pagosFiltrados.length }} registros</span>
            </div>
            <div class="sap-table-topbar__right text-xs text-gray-400">
              <template v-if="filtroDesde || filtroHasta">
                Periodo: {{ filtroDesde || '—' }} → {{ filtroHasta || 'hoy' }}
              </template>
              <template v-else>Sin filtro de fechas</template>
            </div>
          </div>

          <div class="overflow-x-auto">
            <table class="sap-table">
              <thead>
                <tr>
                  <th style="width:44px">#</th>
                  <th style="width:70px">Folio</th>
                  <th>Alumno</th>
                  <th>Diplomado</th>
                  <th style="width:120px">Monto Abono</th>
                  <th style="width:105px">Saldo Actual</th>
                  <th style="width:108px">F. Operación</th>
                  <th style="width:108px">F. Registro</th>
                  <th style="width:140px">Banco / Cuenta</th>
                  <th style="width:120px">Cajero</th>
                  <th style="width:80px; text-align:center">Status</th>
                  <th style="width:80px; text-align:center">Acciones</th>
                </tr>
              </thead>
              <tbody>
                <tr v-if="cargando">
                  <td colspan="12" class="text-center py-14">
                    <v-progress-circular indeterminate color="#1a3a5c" size="38" />
                    <p class="text-sm text-gray-400 mt-3">Cargando movimientos...</p>
                  </td>
                </tr>
                <tr v-else-if="!registrosPag.length">
                  <td colspan="12" class="text-center py-14">
                    <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="#d1d5db" stroke-width="1.5" class="mx-auto mb-3"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><path d="M14 2v6h6M9 13h6M9 17h4"/></svg>
                    <p class="text-sm text-gray-400">Sin registros con los filtros aplicados</p>
                  </td>
                </tr>
                <tr v-for="(p, idx) in registrosPag" :key="p.id"
                    :class="['sap-row', p.status === 'Cancelado' ? 'sap-row--cancelled' : '']">
                  <td class="text-center text-gray-400 text-xs font-mono">{{ (paginaActual - 1) * porPagina + idx + 1 }}</td>
                  <td class="font-mono text-xs text-gray-500">#{{ p.id }}</td>
                  <td>
                    <div class="sap-alumno">
                      <div class="sap-avatar">{{ (p.nombre_alumno ?? '?').charAt(0).toUpperCase() }}</div>
                      <span class="sap-alumno__name">{{ p.nombre_alumno }}</span>
                    </div>
                  </td>
                  <td class="text-xs text-blue-800">{{ p.diplomado }}</td>
                  <td>
                    <span class="sap-monto" :class="p.status === 'Cancelado' ? 'sap-monto--cancelled' : 'sap-monto--ok'">
                      <template v-if="p.status !== 'Cancelado'">+${{ fmt(p.monto) }}</template>
                      <template v-else><s class="opacity-60">${{ fmt(p.monto) }}</s></template>
                    </span>
                  </td>
                  <td>
                    <span class="text-xs font-semibold" :class="parseFloat(p.saldo_actual) <= 0 ? 'text-green-600' : 'text-red-600'">
                      ${{ fmt(p.saldo_actual) }}
                    </span>
                  </td>
                  <td class="font-mono text-xs text-gray-500">{{ p.fecha_operacion }}</td>
                  <td class="font-mono text-xs text-gray-400">{{ p.fecha_ingreso ? p.fecha_ingreso.split('T')[0] : '—' }}</td>
                  <td>
                    <div class="text-xs text-gray-700 font-semibold leading-tight">{{ p.banco }}</div>
                    <div class="text-xs text-gray-400">{{ p.titular_cuenta }}</div>
                  </td>
                  <td class="text-xs text-gray-600">{{ p.cajero }}</td>
                  <td class="text-center">
                    <span class="sap-badge" :class="p.status === 'Cancelado' ? 'sap-badge--red' : 'sap-badge--green'">
                      {{ p.status === 'Cancelado' ? 'Cancelado' : 'Activo' }}
                    </span>
                  </td>
                  <td>
                    <div class="flex gap-1 justify-center">
                      <button class="sap-action sap-action--pdf" title="Reimprimir recibo PDF" @click="descargarPDF(p.id)">
                        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><path d="M14 2v6h6"/></svg>
                      </button>
                      <button class="sap-action sap-action--exp" title="Ver expediente del alumno" @click="abrirExpediente(p)">
                        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z"/></svg>
                      </button>
                    </div>
                  </td>
                </tr>
              </tbody>
              <!-- Fila totales -->
              <tfoot v-if="pagosFiltrados.length">
                <tr class="sap-tfoot">
                  <td colspan="4" class="text-right font-bold text-xs text-gray-600">TOTAL SELECCIÓN (activos):</td>
                  <td class="font-bold text-green-700 text-sm">${{ fmt(kpiTotal) }}</td>
                  <td colspan="7"></td>
                </tr>
              </tfoot>
            </table>
          </div>
        </div>

        <!-- Paginación -->
        <div v-if="totalPaginas > 1" class="sap-pagination">
          <span class="sap-pag-info">
            Mostrando {{ (paginaActual - 1) * porPagina + 1 }}–{{ Math.min(paginaActual * porPagina, pagosFiltrados.length) }}
            de {{ pagosFiltrados.length }}
          </span>
          <div class="sap-pag-btns">
            <button class="sap-pag-btn" :disabled="paginaActual === 1" @click="paginaActual--">‹</button>
            <template v-for="p in totalPaginas" :key="p">
              <button v-if="p === 1 || p === totalPaginas || Math.abs(p - paginaActual) <= 2"
                      class="sap-pag-btn" :class="{ 'sap-pag-btn--active': paginaActual === p }"
                      @click="paginaActual = p">{{ p }}</button>
              <span v-else-if="Math.abs(p - paginaActual) === 3" class="sap-pag-ellipsis">…</span>
            </template>
            <button class="sap-pag-btn" :disabled="paginaActual === totalPaginas" @click="paginaActual++">›</button>
          </div>
        </div>

      </div><!-- /erp-body -->
    </div><!-- /erp-root -->

    <!-- ══ MODAL EXPEDIENTE ══ -->
    <v-dialog v-model="modalExpediente" max-width="720" scrollable>
      <v-card rounded="xl" class="overflow-hidden">
        <div class="exp-hdr">
          <div>
            <p class="exp-hdr__title">
              <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2" class="mr-2 inline"><path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z"/></svg>
              Expediente de Abonos
            </p>
            <p v-if="expedienteAlumno" class="exp-hdr__sub">{{ expedienteAlumno.nombre_alumno }} — {{ expedienteAlumno.diplomado }}</p>
          </div>
          <div class="flex items-center gap-3">
            <span v-if="expedienteAlumno" class="exp-saldo-chip" :class="parseFloat(expedienteAlumno.saldo_actual) <= 0 ? 'exp-saldo-chip--ok' : 'exp-saldo-chip--red'">
              Saldo: ${{ fmt(expedienteAlumno?.saldo_actual ?? 0) }}
            </span>
            <v-btn icon="mdi-close" variant="text" color="white" size="small" @click="modalExpediente = false" />
          </div>
        </div>

        <v-card-text class="pa-0">
          <div v-if="cargandoExp" class="text-center py-12">
            <v-progress-circular indeterminate color="#1a3a5c" />
            <p class="text-sm text-gray-400 mt-3">Cargando historial...</p>
          </div>

          <template v-else>
            <!-- Resumen del alumno -->
            <div v-if="expedienteAlumno" class="exp-info-bar">
              <div class="exp-info-item">
                <span class="exp-info-label">Diplomado</span>
                <span class="exp-info-val">{{ expedienteAlumno.diplomado }}</span>
              </div>
              <div class="exp-info-item">
                <span class="exp-info-label">Banco Referencia</span>
                <span class="exp-info-val">{{ expedienteAlumno.banco }}</span>
              </div>
              <div class="exp-info-item">
                <span class="exp-info-label">Saldo Restante</span>
                <span class="exp-info-val font-bold" :class="parseFloat(expedienteAlumno.saldo_actual) <= 0 ? 'text-green-600' : 'text-red-600'">
                  ${{ fmt(expedienteAlumno.saldo_actual) }} MXN
                </span>
              </div>
            </div>

            <!-- Tabla de historial -->
            <div class="overflow-x-auto">
              <table class="exp-table">
                <thead>
                  <tr>
                    <th style="width:60px">Folio</th>
                    <th style="width:120px">Monto</th>
                    <th style="width:110px">Fecha Op.</th>
                    <th>Cajero</th>
                    <th style="width:80px">Status</th>
                    <th style="width:48px; text-align:center">PDF</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-if="!historialPagos.length">
                    <td colspan="6" class="text-center py-8 text-gray-400 text-sm">Sin movimientos registrados para este alumno.</td>
                  </tr>
                  <tr v-for="h in historialPagos" :key="h.idpago"
                      :class="h.status === 'Cancelado' ? 'exp-row--cancelled' : ''">
                    <td class="font-mono text-xs text-gray-500">#{{ h.idpago }}</td>
                    <td>
                      <span class="font-bold text-sm" :class="h.status === 'Cancelado' ? 'text-gray-400 line-through' : 'text-green-700'">
                        +${{ Number(h.pago_colegiatura).toLocaleString('es-MX', { minimumFractionDigits: 2 }) }}
                      </span>
                    </td>
                    <td class="font-mono text-xs text-gray-600">{{ h.Fecha_PrimerContacto }}</td>
                    <td class="text-xs text-gray-600">{{ h.Tutor }}</td>
                    <td>
                      <span class="sap-badge" :class="h.status === 'Cancelado' ? 'sap-badge--red' : 'sap-badge--green'">
                        {{ h.status }}
                      </span>
                    </td>
                    <td class="text-center">
                      <button class="sap-action sap-action--pdf" @click="descargarPDF(h.idpago)" title="Reimprimir">
                        <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><path d="M14 2v6h6"/></svg>
                      </button>
                    </td>
                  </tr>
                </tbody>
                <tfoot v-if="historialPagos.filter(h => h.status !== 'Cancelado').length">
                  <tr>
                    <td class="text-right text-xs font-bold text-gray-600" colspan="1">Total pagado:</td>
                    <td class="font-bold text-green-700">
                      ${{ fmt(historialPagos.filter(h => h.status !== 'Cancelado').reduce((s, h) => s + parseFloat(h.pago_colegiatura || 0), 0)) }}
                    </td>
                    <td colspan="4"></td>
                  </tr>
                </tfoot>
              </table>
            </div>
          </template>
        </v-card-text>

        <v-card-actions class="px-6 py-4 border-t justify-end">
          <button class="sap-btn sap-btn--ghost" @click="modalExpediente = false">Cerrar</button>
        </v-card-actions>
      </v-card>
    </v-dialog>

    <!-- Snackbar -->
    <v-snackbar v-model="snackbar" :color="snackColor" location="bottom right" :timeout="3500" rounded="xl">
      {{ snackMsg }}
    </v-snackbar>

  </AuthenticatedLayout>
</template>

<style scoped>
/* ── Layout base ── */
.erp-root { background: #f0f4f8; min-height: 100vh; font-family: -apple-system, 'Segoe UI', Roboto, sans-serif; }

/* ── Módulo header ── */
.erp-module-header { background: #fff; border-bottom: 1px solid #dde3ea; padding: 14px 28px; }
.erp-module-header__title { font-size: 1.05rem; font-weight: 700; color: #1a3a5c; display: flex; align-items: center; }
.erp-module-header__sub   { font-size: 0.78rem; color: #64748b; margin-top: 3px; }

/* ── Body ── */
.erp-body { padding: 18px 28px 40px; }

/* ── KPI Strip ── */
.kpi-strip { display: grid; grid-template-columns: repeat(4, 1fr); gap: 14px; margin-bottom: 16px; }
@media (max-width: 900px) { .kpi-strip { grid-template-columns: repeat(2, 1fr); } }
.kpi-card { display: flex; align-items: center; gap: 14px; padding: 16px 20px; border-radius: 10px; box-shadow: 0 1px 4px rgba(0,0,0,.08); }
.kpi-card--blue  { background: linear-gradient(135deg, #1a3a5c 0%, #0070f3 100%); }
.kpi-card--teal  { background: linear-gradient(135deg, #0f4c3a 0%, #059669 100%); }
.kpi-card--navy  { background: linear-gradient(135deg, #312e81 0%, #6366f1 100%); }
.kpi-card--red   { background: linear-gradient(135deg, #7f1d1d 0%, #dc2626 100%); }
.kpi-icon { opacity: 0.9; flex-shrink: 0; }
.kpi-val  { font-size: 1.2rem; font-weight: 800; color: #fff; line-height: 1.2; }
.kpi-lbl  { font-size: 0.72rem; color: rgba(255,255,255,.75); font-weight: 600; text-transform: uppercase; letter-spacing: 0.05em; margin-top: 2px; }
.kpi-sub  { font-size: 0.68rem; color: rgba(255,255,255,.50); margin-top: 1px; }

/* ── Panel de filtros SAP ── */
.filtros-panel {
    background: #fff;
    border: 1px solid #dde3ea;
    border-radius: 10px;
    margin-bottom: 14px;
    overflow: hidden;
}
.filtros-panel__header {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 10px 18px;
    background: #f1f5f9;
    border-bottom: 1px solid #dde3ea;
    font-size: 0.8rem;
    font-weight: 700;
    color: #1a3a5c;
}
.filtros-panel__count {
    margin-left: auto;
    background: #1a3a5c;
    color: #fff;
    font-size: 0.72rem;
    padding: 2px 10px;
    border-radius: 999px;
    font-weight: 600;
}
.filtros-grid {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 14px;
    padding: 16px 18px;
}
@media (max-width: 1100px) { .filtros-grid { grid-template-columns: repeat(2, 1fr); } }
@media (max-width: 640px)  { .filtros-grid { grid-template-columns: 1fr; } }

.filtro-grupo { display: flex; flex-direction: column; gap: 4px; }
.filtro-grupo--wide { grid-column: span 2; }
@media (max-width: 1100px) { .filtro-grupo--wide { grid-column: span 1; } }
.filtro-grupo--actions { display: flex; flex-direction: column; gap: 4px; }

.filtro-label { font-size: 0.72rem; font-weight: 600; color: #64748b; text-transform: uppercase; letter-spacing: 0.05em; }

.filtro-input-wrap { position: relative; display: flex; align-items: center; }
.filtro-icon { position: absolute; left: 10px; color: #94a3b8; pointer-events: none; }

.filtro-input {
    width: 100%;
    padding: 7px 10px 7px 32px;
    border: 1px solid #cbd5e1;
    border-radius: 6px;
    font-size: 0.8rem;
    background: #f8fafc;
    color: #1e293b;
    outline: none;
}
.filtro-input--date { padding-left: 10px; }
.filtro-input:focus { border-color: #1a3a5c; background: #fff; box-shadow: 0 0 0 2px rgba(26,58,92,.12); }

.filtro-select {
    width: 100%;
    padding: 7px 10px;
    border: 1px solid #cbd5e1;
    border-radius: 6px;
    font-size: 0.8rem;
    background: #f8fafc;
    color: #1e293b;
    outline: none;
    cursor: pointer;
}
.filtro-select:focus { border-color: #1a3a5c; }

/* ── Botones SAP ── */
.sap-btn {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 8px 14px;
    border-radius: 6px;
    font-size: 0.78rem;
    font-weight: 600;
    cursor: pointer;
    border: 1px solid transparent;
    transition: all 0.12s;
    white-space: nowrap;
}
.sap-btn:disabled { opacity: 0.4; cursor: not-allowed; }
.sap-btn--primary { background: #1a3a5c; color: #fff; border-color: #1a3a5c; }
.sap-btn--primary:hover:not(:disabled) { background: #142d47; }
.sap-btn--ghost   { background: #f1f5f9; color: #475569; border-color: #dde3ea; }
.sap-btn--ghost:hover { background: #e2e8f0; }
.sap-btn--outline { background: #fff; color: #475569; border-color: #dde3ea; }
.sap-btn--outline:hover { background: #f8fafc; }

@keyframes spin { to { transform: rotate(360deg); } }
.spin { animation: spin 0.8s linear infinite; }

/* ── Tabla SAP ── */
.sap-table-wrap {
    background: #fff;
    border: 1px solid #dde3ea;
    border-radius: 10px;
    overflow: hidden;
    margin-bottom: 12px;
}
.sap-table-topbar {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 10px 16px;
    background: #f8fafc;
    border-bottom: 1px solid #dde3ea;
}
.sap-table-topbar__title { font-size: 0.82rem; font-weight: 700; color: #1a3a5c; }
.sap-table-topbar__badge {
    display: inline-block;
    margin-left: 8px;
    background: #e0e7ff;
    color: #3730a3;
    font-size: 0.68rem;
    font-weight: 700;
    padding: 1px 8px;
    border-radius: 999px;
}

.sap-table { width: 100%; border-collapse: collapse; font-size: 0.79rem; }
.sap-table thead tr { background: #1a3a5c; }
.sap-table thead th {
    padding: 10px 13px;
    text-align: left;
    font-size: 0.67rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.07em;
    color: rgba(255,255,255,.85);
    white-space: nowrap;
    border-bottom: 2px solid #132d47;
}
.sap-table td { padding: 8px 13px; border-bottom: 1px solid #edf2f7; vertical-align: middle; }
.sap-table tbody tr:nth-child(even) { background: #f4f7fb; }
.sap-table tbody tr:hover { background: #e8f0fe !important; }
.sap-row--cancelled { opacity: 0.65; }

.sap-table tfoot td { padding: 9px 13px; background: #f1f5f9; border-top: 2px solid #dde3ea; }

/* Avatar alumno */
.sap-alumno { display: flex; align-items: center; gap: 8px; }
.sap-avatar {
    width: 26px; height: 26px;
    border-radius: 50%;
    background: #1a3a5c;
    color: #fff;
    font-size: 0.7rem;
    font-weight: 700;
    display: flex; align-items: center; justify-content: center;
    flex-shrink: 0;
    text-transform: uppercase;
}
.sap-alumno__name { font-weight: 600; color: #1e293b; }

/* Monto */
.sap-monto { font-weight: 700; font-size: 0.85rem; }
.sap-monto--ok        { color: #16a34a; }
.sap-monto--cancelled { color: #9ca3af; }

/* Badges */
.sap-badge {
    display: inline-block;
    padding: 2px 8px;
    border-radius: 999px;
    font-size: 0.63rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.04em;
    white-space: nowrap;
}
.sap-badge--green { background: #f0fdf4; color: #16a34a; border: 1px solid #bbf7d0; }
.sap-badge--red   { background: #fef2f2; color: #dc2626; border: 1px solid #fecaca; }

/* Acciones por fila */
.sap-action {
    width: 28px; height: 28px;
    border-radius: 5px;
    display: inline-flex; align-items: center; justify-content: center;
    cursor: pointer; border: 1px solid transparent;
    transition: all 0.12s;
}
.sap-action--pdf { background: #fef2f2; border-color: #fecaca; color: #dc2626; }
.sap-action--pdf:hover { background: #fee2e2; }
.sap-action--exp { background: #eff6ff; border-color: #bfdbfe; color: #2563eb; }
.sap-action--exp:hover { background: #dbeafe; }

/* ── Paginación ── */
.sap-pagination {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 10px 4px;
    font-size: 0.78rem;
    color: #64748b;
}
.sap-pag-btns { display: flex; gap: 3px; }
.sap-pag-btn {
    min-width: 30px; height: 30px;
    padding: 0 6px;
    border-radius: 5px;
    border: 1px solid #dde3ea;
    background: #fff;
    font-size: 0.78rem;
    color: #475569;
    cursor: pointer;
    transition: all 0.12s;
    display: flex; align-items: center; justify-content: center;
}
.sap-pag-btn:hover:not(:disabled) { background: #f1f5f9; border-color: #1a3a5c; color: #1a3a5c; }
.sap-pag-btn--active { background: #1a3a5c; border-color: #1a3a5c; color: #fff; font-weight: 700; }
.sap-pag-btn:disabled { opacity: 0.35; cursor: not-allowed; }
.sap-pag-ellipsis { display: flex; align-items: center; padding: 0 4px; color: #94a3b8; }

/* ── Modal expediente ── */
.exp-hdr {
    background: linear-gradient(135deg, #1a3a5c, #0070f3);
    display: flex; align-items: flex-start;
    justify-content: space-between;
    padding: 18px 22px;
    color: white;
}
.exp-hdr__title { font-size: 1rem; font-weight: 700; display: flex; align-items: center; margin-bottom: 3px; }
.exp-hdr__sub   { font-size: 0.78rem; opacity: 0.8; }
.exp-saldo-chip {
    font-size: 0.75rem; font-weight: 700;
    padding: 3px 12px;
    border-radius: 999px;
}
.exp-saldo-chip--ok  { background: rgba(22,163,74,.25); color: #4ade80; border: 1px solid rgba(74,222,128,.3); }
.exp-saldo-chip--red { background: rgba(220,38,38,.25); color: #fca5a5; border: 1px solid rgba(252,165,165,.3); }

.exp-info-bar {
    display: flex; gap: 0;
    border-bottom: 1px solid #e5e7eb;
    background: #f8fafc;
}
.exp-info-item {
    flex: 1;
    padding: 12px 18px;
    border-right: 1px solid #e5e7eb;
    display: flex; flex-direction: column; gap: 2px;
}
.exp-info-item:last-child { border-right: none; }
.exp-info-label { font-size: 0.68rem; font-weight: 600; color: #94a3b8; text-transform: uppercase; letter-spacing: 0.05em; }
.exp-info-val   { font-size: 0.84rem; color: #1e293b; font-weight: 500; }

.exp-table { width: 100%; border-collapse: collapse; font-size: 0.8rem; }
.exp-table thead tr { background: #f1f5f9; }
.exp-table thead th { padding: 9px 14px; text-align: left; font-size: 0.68rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.06em; color: #475569; border-bottom: 1px solid #e2e8f0; white-space: nowrap; }
.exp-table td { padding: 8px 14px; border-bottom: 1px solid #f1f5f9; vertical-align: middle; }
.exp-table tfoot td { padding: 9px 14px; background: #f8fafc; border-top: 2px solid #e2e8f0; }
.exp-row--cancelled { opacity: 0.6; }
</style>
