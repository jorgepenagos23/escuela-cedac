<script setup>
import { ref, computed, onMounted } from 'vue';
import { usePage } from '@inertiajs/vue3';
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import ErpTopbar from "@/Components/ErpTopbar.vue";
import { Head } from "@inertiajs/vue3";
import axios from 'axios';

const page = usePage();
const userId = computed(() => page.props.auth.user.id);

// ─── Datos cartera ─────────────────────────────────────────────────────────────
const vencidos   = ref([]);
const estaSemana = ref([]);
const proximos   = ref([]);
const cargando   = ref(false);

const cargarCalendario = async () => {
    cargando.value = true;
    try {
        const res = await axios.get('/api/v1/pagos/calendario');
        vencidos.value   = res.data.vencidos;
        estaSemana.value = res.data.esta_semana;
        proximos.value   = res.data.proximos;
    } catch (e) {
        mostrarSnack('Error al cargar la cartera.', 'error');
    } finally {
        cargando.value = false;
    }
};

onMounted(() => {
    cargarCalendario();
    cargarCuentas();
});

// ─── Filtros ───────────────────────────────────────────────────────────────────
const buscarCartera        = ref('');
const filtroEstadoCartera  = ref('todos');
const filtroDiplomado      = ref('');

const carteraUnificada = computed(() => {
    const todos = [
        ...vencidos.value.map(a => ({ ...a, estado: 'Vencido' })),
        ...estaSemana.value.map(a => ({ ...a, estado: 'Esta Semana' })),
        ...proximos.value.map(a => ({ ...a, estado: 'Próximo' }))
    ];
    let r = todos;
    if (filtroEstadoCartera.value !== 'todos')
        r = r.filter(a => a.estado === filtroEstadoCartera.value);
    if (filtroDiplomado.value)
        r = r.filter(a => a.diplomado === filtroDiplomado.value);
    if (buscarCartera.value.trim()) {
        const q = buscarCartera.value.toLowerCase();
        r = r.filter(a =>
            a.nombre_alumno?.toLowerCase().includes(q) ||
            a.diplomado?.toLowerCase().includes(q) ||
            a.celular?.toLowerCase().includes(q)
        );
    }
    return r;
});

const diplomadosUnicos = computed(() => [...new Set(
    [...vencidos.value, ...estaSemana.value, ...proximos.value].map(a => a.diplomado).filter(Boolean)
)]);

// ─── KPIs ──────────────────────────────────────────────────────────────────────
const kpiTotal    = computed(() => carteraUnificada.value.reduce((s, a) => s + parseFloat(a.saldo || 0), 0));
const kpiVencidos = computed(() => vencidos.value.reduce((s, a) => s + parseFloat(a.saldo || 0), 0));
const kpiSemana   = computed(() => estaSemana.value.reduce((s, a) => s + parseFloat(a.saldo || 0), 0));
const kpiProximos = computed(() => proximos.value.reduce((s, a) => s + parseFloat(a.saldo || 0), 0));

const fmt = (n) => Number(n).toLocaleString('es-MX', { minimumFractionDigits: 2 });

// ─── Snackbar ──────────────────────────────────────────────────────────────────
const snackbar  = ref(false);
const snackMsg  = ref('');
const snackColor = ref('success');
const mostrarSnack = (msg, color = 'success') => {
    snackMsg.value = msg; snackColor.value = color; snackbar.value = true;
};

// ─── Cuentas bancarias ─────────────────────────────────────────────────────────
const cuentas = ref([]);
const cargarCuentas = async () => {
    try {
        const res = await axios.get('/api/v1/cuentadeposito/index/2024/numero');
        cuentas.value = res.data.cuentaDeposito;
    } catch (e) { console.error(e); }
};

// ─── Modal Pago Rápido ─────────────────────────────────────────────────────────
const modalPago     = ref(false);
const pagoAlumno    = ref(null);
const pagoForm      = ref({ monto: '', fecha: new Date().toISOString().split('T')[0], cuenta_id: null, comprobante: null });
const pagoEnvio     = ref(false);

const abrirPago = (item) => {
    pagoAlumno.value = item;
    pagoForm.value = {
        monto:       '',
        fecha:       new Date().toISOString().split('T')[0],
        cuenta_id:   null,
        comprobante: null,
    };
    modalPago.value = true;
};

const realizarPago = async () => {
    if (!pagoForm.value.monto || pagoForm.value.monto <= 0) {
        mostrarSnack('Ingresa un monto válido.', 'warning'); return;
    }
    if (!pagoForm.value.cuenta_id) {
        mostrarSnack('Selecciona la cuenta de destino.', 'warning'); return;
    }
    if (!pagoForm.value.comprobante) {
        mostrarSnack('Adjunta el comprobante de pago.', 'warning'); return;
    }

    pagoEnvio.value = true;
    try {
        const fd = new FormData();
        fd.append('alumno_id',             pagoAlumno.value.id);
        fd.append('diplomado_id',          pagoAlumno.value.diplomado_id);
        fd.append('pago_colegiatura',      pagoForm.value.monto);
        fd.append('Fecha_PrimerContacto',  pagoForm.value.fecha);
        fd.append('cuentadeposito',        pagoForm.value.cuenta_id);
        fd.append('tutor',                 userId.value);
        fd.append('comprobante',           pagoForm.value.comprobante);

        await axios.post('/api/v1/pagosabonos/crear', fd, {
            headers: { 'Content-Type': 'multipart/form-data' }
        });

        mostrarSnack(`Pago de $${fmt(pagoForm.value.monto)} registrado correctamente.`, 'success');
        modalPago.value = false;
        await cargarCalendario();
    } catch (e) {
        mostrarSnack(e.response?.data?.error ?? 'Error al registrar el pago.', 'error');
    } finally {
        pagoEnvio.value = false;
    }
};

// ─── Modal Cancelar Abono ──────────────────────────────────────────────────────
const modalCancelar      = ref(false);
const alumnoSeleccionado = ref(null);
const historialAbonos    = ref([]);
const loadingHistorial   = ref(false);
const abonoCancelarId    = ref(null);
const motivoCancelacion  = ref('');
const cancelando         = ref(false);

const abrirModalCancelar = async (alumno) => {
    alumnoSeleccionado.value = alumno;
    abonoCancelarId.value    = null;
    motivoCancelacion.value  = '';
    historialAbonos.value    = [];
    loadingHistorial.value   = true;
    modalCancelar.value      = true;
    try {
        const res = await axios.get(`/api/v1/alumno/${alumno.id}/plan-pagos`);
        historialAbonos.value = res.data.pagos_realizados.filter(p => p.status !== 'Cancelado');
    } catch (e) {
        mostrarSnack('No se pudo cargar el historial.', 'error');
    } finally {
        loadingHistorial.value = false;
    }
};

const confirmarCancelacion = async () => {
    if (!abonoCancelarId.value) { mostrarSnack('Selecciona el abono a cancelar.', 'warning'); return; }
    if (!motivoCancelacion.value.trim()) { mostrarSnack('Escribe el motivo.', 'warning'); return; }
    cancelando.value = true;
    try {
        await axios.post(`/api/v1/pagos/${abonoCancelarId.value}/cancelar`, { motivo: motivoCancelacion.value });
        mostrarSnack('Abono cancelado y saldo restituido.', 'success');
        modalCancelar.value = false;
        await cargarCalendario();
    } catch (e) {
        mostrarSnack(e.response?.data?.error ?? 'Error al cancelar.', 'error');
    } finally {
        cancelando.value = false;
    }
};

// ─── Modal Reprogramar Plan ────────────────────────────────────────────────────
const modalPlan     = ref(false);
const planData      = ref(null);
const loadingPlan   = ref(false);
const guardandoPlan = ref(false);
const planEditable  = ref([]);

const abrirModalPlan = async (alumno) => {
    alumnoSeleccionado.value = alumno;
    loadingPlan.value = true;
    planEditable.value = [];
    planData.value = null;
    modalPlan.value = true;
    try {
        const res = await axios.get(`/api/v1/alumno/${alumno.id}/plan-pagos`);
        planData.value = res.data;
        if (res.data.plan_pagos && res.data.plan_pagos.length > 0) {
            planEditable.value = res.data.plan_pagos.map(p => ({ ...p }));
        } else {
            generarPlanAutomatico(res.data.inscripcion);
        }
    } catch (e) {
        mostrarSnack('No se pudo cargar el plan.', 'error');
    } finally {
        loadingPlan.value = false;
    }
};

const generarPlanAutomatico = (inscripcion) => {
    const saldo = parseFloat(inscripcion.saldo) || 0;
    const n = 4;
    const cuota = Math.ceil(saldo / n);
    planEditable.value = Array.from({ length: n }, (_, i) => {
        const d = new Date();
        d.setMonth(d.getMonth() + i + 1);
        return { fecha: d.toISOString().split('T')[0], monto: i < n - 1 ? cuota : saldo - cuota * (n - 1), descripcion: `Mensualidad ${i + 1}` };
    });
};

const agregarCuota = () => {
    const ultima = planEditable.value[planEditable.value.length - 1];
    const d = ultima ? new Date(ultima.fecha) : new Date();
    d.setMonth(d.getMonth() + 1);
    planEditable.value.push({ fecha: d.toISOString().split('T')[0], monto: 0, descripcion: `Mensualidad ${planEditable.value.length + 1}` });
};

const quitarCuota = (idx) => planEditable.value.splice(idx, 1);

const totalPlan = computed(() => planEditable.value.reduce((s, c) => s + parseFloat(c.monto || 0), 0));

const guardarPlan = async () => {
    if (!planEditable.value.length) { mostrarSnack('Agrega al menos una cuota.', 'warning'); return; }
    guardandoPlan.value = true;
    try {
        await axios.post(`/api/v1/alumno/${alumnoSeleccionado.value.id}/plan-pagos`, { plan: planEditable.value });
        mostrarSnack('Plan guardado exitosamente.', 'success');
        modalPlan.value = false;
    } catch (e) {
        mostrarSnack(e.response?.data?.message ?? 'Error al guardar el plan.', 'error');
    } finally {
        guardandoPlan.value = false;
    }
};

// ─── Exportar Excel ────────────────────────────────────────────────────────────
const exportarExcel = () => {
    import('xlsx').then(XLSX => {
        const datos = carteraUnificada.value.map((item, i) => ({
            'No.': i + 1,
            'Alumno': item.nombre_alumno,
            'Diplomado': item.diplomado,
            'Saldo Pendiente': Number(item.saldo),
            'Próximo Pago': item.fecha_pago,
            'Estado': item.estado,
            'Días Retraso': item.dias_retraso,
            'Celular': item.celular || 'N/A'
        }));
        const ws = XLSX.utils.json_to_sheet(datos);
        const wb = XLSX.utils.book_new();
        XLSX.utils.book_append_sheet(wb, ws, 'Cobranza');
        XLSX.writeFile(wb, `Cartera_Cobranza_${new Date().toLocaleDateString('es-MX').replace(/\//g,'-')}.xlsx`);
        mostrarSnack('Excel generado.', 'success');
    });
};

// ─── Exportar PDF ──────────────────────────────────────────────────────────────
const exportarPDF = () => {
    Promise.all([import('jspdf'), import('jspdf-autotable')]).then(([jsPDFModule]) => {
        const jsPDF = jsPDFModule.default || jsPDFModule.jsPDF;
        const doc = new jsPDF();
        doc.setFontSize(13);
        doc.text('Reporte Cartera de Cobranza — CEDAC', 14, 15);
        doc.setFontSize(9);
        doc.text(`Fecha: ${new Date().toLocaleDateString('es-MX')}   Total: $${fmt(kpiTotal.value)} MXN`, 14, 22);
        doc.autoTable({
            startY: 28,
            head: [['#', 'Alumno', 'Diplomado', 'Saldo', 'Próximo Pago', 'Estado']],
            body: carteraUnificada.value.map((a, i) => [i + 1, a.nombre_alumno, a.diplomado, `$${fmt(a.saldo)}`, a.fecha_pago, a.estado]),
            theme: 'grid',
            headStyles: { fillColor: [26, 58, 92] },
            styles: { fontSize: 8 }
        });
        doc.save('Reporte_Cobranza.pdf');
        mostrarSnack('PDF generado.', 'success');
    });
};

// ─── Paginación ────────────────────────────────────────────────────────────────
const paginaActual  = ref(1);
const itemsPorPagina = 25;
const totalPaginas  = computed(() => Math.ceil(carteraUnificada.value.length / itemsPorPagina));
const registrosPagina = computed(() =>
    carteraUnificada.value.slice((paginaActual.value - 1) * itemsPorPagina, paginaActual.value * itemsPorPagina)
);
</script>

<template>
  <AuthenticatedLayout>
    <Head title="Cartera de Cobranza" />

    <div class="erp-root">

      <!-- ══ HEADER ERP ══════════════════════════════════════════════════════════ -->
      <ErpTopbar modulo="Finanzas" titulo="Cartera de Cobranza" />

      <!-- ══ TÍTULO DE MÓDULO ════════════════════════════════════════════════════ -->
      <div class="erp-module-header">
        <div class="erp-module-header__title">
          <v-icon size="22" color="#1a3a5c" class="mr-2">mdi-currency-usd</v-icon>
          Módulo de Cobranza y Cartera
        </div>
        <div class="erp-module-header__sub">
          Gestiona los saldos pendientes, registra abonos y exporta reportes de tu cartera activa.
        </div>
      </div>

      <div class="erp-body">

        <!-- ══ KPI STRIP ══════════════════════════════════════════════════════════ -->
        <div class="kpi-strip">
          <div class="kpi-card kpi-card--blue">
            <div class="kpi-card__icon"><v-icon size="28" color="white">mdi-wallet</v-icon></div>
            <div class="kpi-card__data">
              <div class="kpi-card__value">${{ fmt(kpiTotal) }}</div>
              <div class="kpi-card__label">Cartera Total</div>
              <div class="kpi-card__count">{{ carteraUnificada.length }} alumnos activos</div>
            </div>
          </div>
          <div class="kpi-card kpi-card--red">
            <div class="kpi-card__icon"><v-icon size="28" color="white">mdi-alert-circle</v-icon></div>
            <div class="kpi-card__data">
              <div class="kpi-card__value">${{ fmt(kpiVencidos) }}</div>
              <div class="kpi-card__label">Pagos Vencidos</div>
              <div class="kpi-card__count">{{ vencidos.length }} alumnos en mora</div>
            </div>
          </div>
          <div class="kpi-card kpi-card--orange">
            <div class="kpi-card__icon"><v-icon size="28" color="white">mdi-clock-alert</v-icon></div>
            <div class="kpi-card__data">
              <div class="kpi-card__value">${{ fmt(kpiSemana) }}</div>
              <div class="kpi-card__label">Vencen Esta Semana</div>
              <div class="kpi-card__count">{{ estaSemana.length }} próximos a vencer</div>
            </div>
          </div>
          <div class="kpi-card kpi-card--green">
            <div class="kpi-card__icon"><v-icon size="28" color="white">mdi-check-circle</v-icon></div>
            <div class="kpi-card__data">
              <div class="kpi-card__value">${{ fmt(kpiProximos) }}</div>
              <div class="kpi-card__label">Pagos Próximos</div>
              <div class="kpi-card__count">{{ proximos.length }} al corriente</div>
            </div>
          </div>
        </div>

        <!-- ══ TOOLBAR ════════════════════════════════════════════════════════════ -->
        <div class="erp-toolbar">
          <div class="erp-toolbar__filters">
            <div class="erp-search-wrap">
              <v-icon size="16" color="#64748b" class="erp-search-icon">mdi-magnify</v-icon>
              <input
                v-model="buscarCartera"
                type="text"
                placeholder="Buscar alumno, diplomado, celular..."
                class="erp-search"
              />
            </div>
            <select v-model="filtroEstadoCartera" class="erp-select">
              <option value="todos">Todos los estados</option>
              <option value="Vencido">Vencidos</option>
              <option value="Esta Semana">Esta Semana</option>
              <option value="Próximo">Próximos</option>
            </select>
            <select v-model="filtroDiplomado" class="erp-select">
              <option value="">Todos los diplomados</option>
              <option v-for="d in diplomadosUnicos" :key="d" :value="d">{{ d }}</option>
            </select>
          </div>
          <div class="erp-toolbar__actions">
            <!-- Cancelar Abono -->
            <v-dialog v-model="modalCancelar" max-width="640" persistent>
              <template #activator="{ props }">
                <button v-bind="props" class="erp-btn erp-btn--ghost-red">
                  <v-icon size="15" class="mr-1">mdi-cancel</v-icon>Cancelar Abono
                </button>
              </template>
              <v-card rounded="xl" class="overflow-hidden">
                <div class="modal-hdr modal-hdr--red">
                  <div>
                    <p class="modal-hdr__title"><v-icon class="mr-2">mdi-cancel</v-icon>Cancelar Abono Aplicado</p>
                    <p class="modal-hdr__sub">Esta acción revertirá el monto al saldo del alumno.</p>
                  </div>
                  <v-btn icon="mdi-close" variant="text" color="white" @click="modalCancelar = false; alumnoSeleccionado = null" />
                </div>
                <v-card-text class="pa-6">
                  <template v-if="alumnoSeleccionado">
                    <div class="modal-alumno-chip mb-4">
                      <v-icon size="16" color="indigo" class="mr-1">mdi-account</v-icon>
                      {{ alumnoSeleccionado.nombre_alumno }} — {{ alumnoSeleccionado.diplomado }}
                    </div>
                    <p class="text-sm font-semibold text-gray-700 mb-3">Selecciona el abono a cancelar:</p>
                    <div v-if="loadingHistorial" class="text-center py-6">
                      <v-progress-circular indeterminate color="red-darken-1" />
                    </div>
                    <template v-else-if="historialAbonos.length">
                      <div class="abonos-lista">
                        <div v-for="a in historialAbonos" :key="a.id"
                             class="abono-item" :class="{ 'abono-item--sel': abonoCancelarId === a.id }"
                             @click="abonoCancelarId = a.id">
                          <v-icon :color="abonoCancelarId === a.id ? 'red' : 'green'" size="20">
                            {{ abonoCancelarId === a.id ? 'mdi-radiobox-marked' : 'mdi-radiobox-blank' }}
                          </v-icon>
                          <div class="flex-1">
                            <span class="block font-bold text-green-700 text-sm">+${{ Number(a.pago_colegiatura).toLocaleString('es-MX') }} MXN</span>
                            <span class="text-xs text-gray-400">{{ a.Fecha_PrimerContacto }} · Folio #{{ a.id }}</span>
                          </div>
                          <v-chip size="x-small" color="success" variant="flat">{{ a.status }}</v-chip>
                        </div>
                      </div>
                      <v-divider class="my-4" />
                      <v-textarea v-model="motivoCancelacion" label="Motivo de cancelación *" rows="3" variant="outlined" density="compact" counter="500" maxlength="500" />
                    </template>
                    <div v-else class="text-center py-6 text-gray-400 text-sm">Sin abonos activos para este alumno.</div>
                  </template>
                  <div v-else class="text-center py-10 text-gray-400">
                    <v-icon size="44" color="red-lighten-3" class="mb-2">mdi-account-question</v-icon>
                    <p class="text-sm">Usa el botón <strong>Cancelar</strong> en la fila de la tabla para seleccionar un alumno.</p>
                  </div>
                </v-card-text>
                <v-card-actions class="px-6 pb-5 justify-end gap-2">
                  <v-btn variant="tonal" color="grey" @click="modalCancelar = false; alumnoSeleccionado = null">Cerrar</v-btn>
                  <v-btn color="red-darken-2" variant="flat" prepend-icon="mdi-trash-can"
                         :loading="cancelando" :disabled="!abonoCancelarId || !motivoCancelacion.trim()"
                         @click="confirmarCancelacion">Confirmar Cancelación</v-btn>
                </v-card-actions>
              </v-card>
            </v-dialog>

            <!-- Reprogramar Plan -->
            <v-dialog v-model="modalPlan" max-width="720" persistent>
              <template #activator="{ props }">
                <button v-bind="props" class="erp-btn erp-btn--ghost-purple">
                  <v-icon size="15" class="mr-1">mdi-calendar-edit</v-icon>Reprogramar Plan
                </button>
              </template>
              <v-card rounded="xl" class="overflow-hidden">
                <div class="modal-hdr modal-hdr--purple">
                  <div>
                    <p class="modal-hdr__title"><v-icon class="mr-2">mdi-calendar-edit</v-icon>Plan de Pagos</p>
                    <p class="modal-hdr__sub">Define las fechas y montos de mensualidades.</p>
                  </div>
                  <v-btn icon="mdi-close" variant="text" color="white" @click="modalPlan = false; alumnoSeleccionado = null" />
                </div>
                <v-card-text class="pa-6">
                  <template v-if="alumnoSeleccionado && planData">
                    <div class="modal-alumno-chip mb-4">
                      <v-icon size="16" color="deep-purple" class="mr-1">mdi-account</v-icon>
                      {{ planData.inscripcion.nombre_alumno }} · Saldo:
                      <strong class="text-red-600 ml-1">${{ Number(planData.inscripcion.saldo).toLocaleString('es-MX') }} MXN</strong>
                      <span class="ml-2 text-xs" :class="totalPlan > planData.inscripcion.saldo ? 'text-red-500' : 'text-green-600'">
                        (Plan: ${{ Number(totalPlan).toLocaleString('es-MX') }})
                      </span>
                    </div>
                    <div v-if="loadingPlan" class="text-center py-6"><v-progress-circular indeterminate color="deep-purple" /></div>
                    <template v-else>
                      <div class="flex justify-between items-center mb-3">
                        <span class="text-sm font-bold text-gray-700">Cuotas del Plan</span>
                        <v-btn size="small" variant="tonal" color="deep-purple" prepend-icon="mdi-plus-circle" @click="agregarCuota">Agregar cuota</v-btn>
                      </div>
                      <div class="cuotas-lista">
                        <div v-for="(c, i) in planEditable" :key="i" class="cuota-row">
                          <span class="cuota-num">#{{ i + 1 }}</span>
                          <v-text-field v-model="c.fecha" type="date" label="Fecha" variant="outlined" density="compact" hide-details class="flex-1" />
                          <v-text-field v-model.number="c.monto" type="number" label="Monto" prefix="$" variant="outlined" density="compact" hide-details style="max-width:110px" />
                          <v-text-field v-model="c.descripcion" label="Descripción" variant="outlined" density="compact" hide-details class="flex-1" />
                          <v-btn icon="mdi-trash-can-outline" size="small" variant="text" color="red" @click="quitarCuota(i)" />
                        </div>
                        <div v-if="!planEditable.length" class="text-center py-4 text-gray-400 text-sm">Sin cuotas. Usa "Agregar cuota" o genera automáticamente.</div>
                      </div>
                      <v-alert v-if="planData && totalPlan > planData.inscripcion.saldo" type="warning" variant="tonal" density="compact" class="mt-3" icon="mdi-alert">
                        El plan (${{ Number(totalPlan).toLocaleString('es-MX') }}) excede el saldo (${{ Number(planData.inscripcion.saldo).toLocaleString('es-MX') }}).
                      </v-alert>
                    </template>
                  </template>
                  <div v-else-if="!alumnoSeleccionado" class="text-center py-10 text-gray-400">
                    <v-icon size="44" color="deep-purple-lighten-3" class="mb-2">mdi-account-question</v-icon>
                    <p class="text-sm">Usa el botón <strong>Plan</strong> en la fila de la tabla para seleccionar un alumno.</p>
                  </div>
                  <div v-else class="text-center py-6"><v-progress-circular indeterminate color="deep-purple" /></div>
                </v-card-text>
                <v-card-actions class="px-6 pb-5 justify-end gap-2">
                  <v-btn v-if="planData && !planEditable.length" variant="tonal" color="deep-purple" prepend-icon="mdi-refresh" @click="generarPlanAutomatico(planData.inscripcion)">Auto (4 cuotas)</v-btn>
                  <v-btn variant="tonal" color="grey" @click="modalPlan = false; alumnoSeleccionado = null">Cancelar</v-btn>
                  <v-btn color="deep-purple-darken-2" variant="flat" prepend-icon="mdi-content-save" :loading="guardandoPlan" :disabled="!planEditable.length || !alumnoSeleccionado" @click="guardarPlan">Guardar Plan</v-btn>
                </v-card-actions>
              </v-card>
            </v-dialog>

            <button class="erp-btn erp-btn--green" @click="exportarExcel">
              <v-icon size="15" class="mr-1">mdi-file-excel</v-icon>Excel
            </button>
            <button class="erp-btn erp-btn--red" @click="exportarPDF">
              <v-icon size="15" class="mr-1">mdi-file-pdf-box</v-icon>PDF
            </button>
            <button class="erp-btn erp-btn--outline" @click="cargarCalendario" :disabled="cargando">
              <v-icon size="15" :class="cargando ? 'erp-spin' : ''">mdi-refresh</v-icon>
            </button>
          </div>
        </div>

        <!-- ══ TABLA CARTERA ══════════════════════════════════════════════════════ -->
        <div class="erp-table-wrap">
          <table class="erp-table">
            <thead>
              <tr>
                <th style="width:42px">#</th>
                <th>Alumno</th>
                <th>Diplomado / Programa</th>
                <th style="width:130px">Saldo Pendiente</th>
                <th style="width:115px">Próximo Pago</th>
                <th style="width:80px">Días</th>
                <th style="width:110px">Estado</th>
                <th style="width:130px">Celular</th>
                <th style="width:148px; text-align:center">Acciones</th>
              </tr>
            </thead>
            <tbody>
              <tr v-if="cargando">
                <td colspan="9" class="text-center py-12">
                  <v-progress-circular indeterminate color="#1a3a5c" size="36" />
                  <p class="text-sm text-gray-400 mt-3">Cargando cartera...</p>
                </td>
              </tr>
              <tr v-else-if="!registrosPagina.length">
                <td colspan="9" class="text-center py-12">
                  <v-icon size="48" color="grey-lighten-2" class="mb-3">mdi-table-off</v-icon>
                  <p class="text-sm text-gray-400">No se encontraron registros con los filtros aplicados.</p>
                </td>
              </tr>
              <tr v-for="(item, idx) in registrosPagina" :key="item.id + '-' + idx"
                  :class="['erp-row', `erp-row--${item.estado === 'Vencido' ? 'red' : item.estado === 'Esta Semana' ? 'orange' : 'green'}`]">
                <td class="text-center text-gray-400 text-xs font-mono">
                  {{ (paginaActual - 1) * 25 + idx + 1 }}
                </td>
                <td>
                  <div class="erp-alumno">
                    <div class="erp-alumno__avatar">{{ item.nombre_alumno?.charAt(0) }}</div>
                    <span class="erp-alumno__name">{{ item.nombre_alumno }}</span>
                  </div>
                </td>
                <td class="text-xs text-blue-800">{{ item.diplomado }}</td>
                <td>
                  <span class="erp-saldo" :class="`erp-saldo--${item.estado === 'Vencido' ? 'red' : item.estado === 'Esta Semana' ? 'orange' : 'green'}`">
                    ${{ fmt(item.saldo) }}
                  </span>
                </td>
                <td class="text-xs text-gray-500 font-mono">{{ item.fecha_pago }}</td>
                <td class="text-center">
                  <span v-if="item.dias_retraso > 0" class="erp-dias erp-dias--red">+{{ item.dias_retraso }}d</span>
                  <span v-else-if="item.dias_retraso === 0" class="erp-dias erp-dias--orange">Hoy</span>
                  <span v-else class="erp-dias erp-dias--blue">{{ Math.abs(item.dias_retraso) }}d</span>
                </td>
                <td>
                  <span class="erp-badge"
                    :class="{
                      'erp-badge--red':    item.estado === 'Vencido',
                      'erp-badge--orange': item.estado === 'Esta Semana',
                      'erp-badge--green':  item.estado === 'Próximo'
                    }">{{ item.estado }}</span>
                </td>
                <td class="text-xs">
                  <a v-if="item.celular" :href="'https://wa.me/52' + item.celular.replace(/[^0-9]/g,'')" target="_blank"
                     class="erp-wa-link">
                    <v-icon size="13" class="mr-0.5">mdi-whatsapp</v-icon>{{ item.celular }}
                  </a>
                  <span v-else class="text-gray-300">—</span>
                </td>
                <td>
                  <div class="erp-actions">
                    <button class="erp-action erp-action--pay" title="Registrar pago" @click="abrirPago(item)">
                      <v-icon size="14">mdi-cash-plus</v-icon>
                    </button>
                    <button class="erp-action erp-action--cancel" title="Cancelar abono" @click="abrirModalCancelar(item)">
                      <v-icon size="14">mdi-cancel</v-icon>
                    </button>
                    <button class="erp-action erp-action--plan" title="Plan de pagos" @click="abrirModalPlan(item)">
                      <v-icon size="14">mdi-calendar-edit</v-icon>
                    </button>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- Paginación -->
        <div v-if="totalPaginas > 1" class="erp-pagination">
          <span class="erp-pag-info">
            Mostrando {{ (paginaActual - 1) * 25 + 1 }}–{{ Math.min(paginaActual * 25, carteraUnificada.length) }}
            de {{ carteraUnificada.length }} registros
          </span>
          <div class="erp-pag-btns">
            <button class="erp-pag-btn" :disabled="paginaActual === 1" @click="paginaActual--">
              <v-icon size="16">mdi-chevron-left</v-icon>
            </button>
            <button v-for="p in totalPaginas" :key="p"
                    class="erp-pag-btn" :class="{ 'erp-pag-btn--active': paginaActual === p }"
                    @click="paginaActual = p">{{ p }}</button>
            <button class="erp-pag-btn" :disabled="paginaActual === totalPaginas" @click="paginaActual++">
              <v-icon size="16">mdi-chevron-right</v-icon>
            </button>
          </div>
        </div>

      </div><!-- /erp-body -->
    </div><!-- /erp-root -->

    <!-- ══ MODAL PAGO RÁPIDO ══════════════════════════════════════════════════════ -->
    <v-dialog v-model="modalPago" max-width="520" persistent>
      <v-card rounded="xl" class="overflow-hidden">
        <div class="modal-hdr modal-hdr--blue">
          <div>
            <p class="modal-hdr__title"><v-icon class="mr-2">mdi-cash-register</v-icon>Registrar Pago</p>
            <p class="modal-hdr__sub">Captura el abono y adjunta el comprobante correspondiente.</p>
          </div>
          <v-btn icon="mdi-close" variant="text" color="white" @click="modalPago = false" />
        </div>

        <v-card-text v-if="pagoAlumno" class="pa-6">
          <!-- Resumen alumno -->
          <div class="pago-alumno-info">
            <div class="pago-alumno-info__row">
              <span class="pago-info-label">Alumno</span>
              <span class="pago-info-val font-bold">{{ pagoAlumno.nombre_alumno }}</span>
            </div>
            <div class="pago-alumno-info__row">
              <span class="pago-info-label">Programa</span>
              <span class="pago-info-val">{{ pagoAlumno.diplomado }}</span>
            </div>
            <div class="pago-alumno-info__row">
              <span class="pago-info-label">Saldo Pendiente</span>
              <span class="pago-info-val text-red-600 font-bold">${{ fmt(pagoAlumno.saldo) }} MXN</span>
            </div>
          </div>

          <v-divider class="my-4" />

          <!-- Formulario -->
          <div class="grid grid-cols-2 gap-4 mb-4">
            <v-text-field
              v-model="pagoForm.monto"
              type="number"
              label="Monto del Abono *"
              prefix="$"
              variant="outlined"
              density="comfortable"
              :hint="`Saldo: $${fmt(pagoAlumno.saldo)}`"
              persistent-hint
              prepend-inner-icon="mdi-cash"
              required
            />
            <v-text-field
              v-model="pagoForm.fecha"
              type="date"
              label="Fecha del Pago *"
              variant="outlined"
              density="comfortable"
              prepend-inner-icon="mdi-calendar"
              required
            />
          </div>

          <v-select
            v-model="pagoForm.cuenta_id"
            :items="cuentas"
            item-title="titular"
            item-value="id"
            label="Cuenta / Banco de Destino *"
            variant="outlined"
            density="comfortable"
            prepend-inner-icon="mdi-bank"
            class="mb-4"
            required
          />

          <div class="erp-file-upload" :class="{ 'erp-file-upload--filled': pagoForm.comprobante }">
            <v-icon size="20" color="#1a3a5c" class="mb-1">mdi-file-upload-outline</v-icon>
            <p class="text-xs font-semibold text-gray-700 mb-1">
              {{ pagoForm.comprobante ? pagoForm.comprobante.name : 'Adjuntar Comprobante de Pago *' }}
            </p>
            <p class="text-xs text-gray-400">PDF, JPG, PNG — requerido por el sistema</p>
            <input type="file" accept=".pdf,.jpg,.jpeg,.png" class="erp-file-input"
                   @change="pagoForm.comprobante = $event.target.files[0]" />
          </div>
        </v-card-text>

        <v-card-actions class="px-6 pb-5 justify-end gap-2">
          <v-btn variant="tonal" color="grey" @click="modalPago = false">Cancelar</v-btn>
          <v-btn color="#1a3a5c" variant="flat" prepend-icon="mdi-content-save"
                 :loading="pagoEnvio" @click="realizarPago">
            <span class="text-white">Registrar Pago</span>
          </v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>

    <!-- Snackbar -->
    <v-snackbar v-model="snackbar" :color="snackColor" location="bottom right" :timeout="3800" rounded="xl">
      <v-icon class="mr-2">{{ snackColor === 'success' ? 'mdi-check-circle' : snackColor === 'warning' ? 'mdi-alert' : 'mdi-alert-circle' }}</v-icon>
      {{ snackMsg }}
    </v-snackbar>

  </AuthenticatedLayout>
</template>

<style scoped>
/* ═══════════════════════════════════════════════════════════════
   LAYOUT BASE
═══════════════════════════════════════════════════════════════ */
.erp-root {
    background: #f0f4f8;
    min-height: 100vh;
    font-family: -apple-system, 'Segoe UI', Roboto, sans-serif;
}

/* ═══════════════════════════════════════════════════════════════
   MODULE HEADER
═══════════════════════════════════════════════════════════════ */
.erp-module-header {
    background: #fff;
    border-bottom: 1px solid #dde3ea;
    padding: 16px 28px;
}
.erp-module-header__title {
    font-size: 1.15rem;
    font-weight: 700;
    color: #1a3a5c;
    display: flex;
    align-items: center;
}
.erp-module-header__sub {
    font-size: 0.78rem;
    color: #64748b;
    margin-top: 3px;
}

/* ═══════════════════════════════════════════════════════════════
   BODY
═══════════════════════════════════════════════════════════════ */
.erp-body { padding: 20px 28px 40px; }

/* ═══════════════════════════════════════════════════════════════
   KPI STRIP
═══════════════════════════════════════════════════════════════ */
.kpi-strip {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 14px;
    margin-bottom: 18px;
}
@media (max-width: 900px) { .kpi-strip { grid-template-columns: repeat(2, 1fr); } }
.kpi-card {
    display: flex;
    align-items: center;
    gap: 14px;
    padding: 18px 20px;
    border-radius: 10px;
    box-shadow: 0 1px 4px rgba(0,0,0,.08);
}
.kpi-card--blue   { background: linear-gradient(135deg, #1a3a5c 0%, #0070f3 100%); }
.kpi-card--red    { background: linear-gradient(135deg, #7f1d1d 0%, #dc2626 100%); }
.kpi-card--orange { background: linear-gradient(135deg, #92400e 0%, #f97316 100%); }
.kpi-card--green  { background: linear-gradient(135deg, #14532d 0%, #16a34a 100%); }
.kpi-card__icon { opacity: 0.9; }
.kpi-card__value { font-size: 1.25rem; font-weight: 800; color: #fff; line-height: 1.2; }
.kpi-card__label { font-size: 0.72rem; color: rgba(255,255,255,.75); font-weight: 600; text-transform: uppercase; letter-spacing: 0.05em; margin-top: 2px; }
.kpi-card__count { font-size: 0.7rem; color: rgba(255,255,255,.55); margin-top: 2px; }

/* ═══════════════════════════════════════════════════════════════
   TOOLBAR
═══════════════════════════════════════════════════════════════ */
.erp-toolbar {
    background: #fff;
    border: 1px solid #dde3ea;
    border-radius: 10px;
    padding: 12px 16px;
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    justify-content: space-between;
    gap: 10px;
    margin-bottom: 14px;
}
.erp-toolbar__filters { display: flex; align-items: center; gap: 8px; flex-wrap: wrap; }
.erp-toolbar__actions { display: flex; align-items: center; gap: 8px; flex-wrap: wrap; }

.erp-search-wrap {
    position: relative;
    display: flex;
    align-items: center;
}
.erp-search-icon { position: absolute; left: 10px; }
.erp-search {
    padding: 7px 12px 7px 32px;
    border: 1px solid #cbd5e1;
    border-radius: 7px;
    font-size: 0.8rem;
    width: 240px;
    background: #f8fafc;
    color: #1e293b;
    outline: none;
}
.erp-search:focus { border-color: #0070f3; background: #fff; box-shadow: 0 0 0 2px rgba(0,112,243,.15); }

.erp-select {
    padding: 7px 12px;
    border: 1px solid #cbd5e1;
    border-radius: 7px;
    font-size: 0.8rem;
    background: #f8fafc;
    color: #1e293b;
    outline: none;
    cursor: pointer;
}
.erp-select:focus { border-color: #0070f3; }

/* Botones toolbar */
.erp-btn {
    display: inline-flex;
    align-items: center;
    padding: 7px 14px;
    border-radius: 7px;
    font-size: 0.8rem;
    font-weight: 600;
    cursor: pointer;
    border: 1px solid transparent;
    transition: all 0.15s;
    white-space: nowrap;
}
.erp-btn--green   { background: #16a34a; color: #fff; }
.erp-btn--green:hover { background: #15803d; }
.erp-btn--red     { background: #dc2626; color: #fff; }
.erp-btn--red:hover { background: #b91c1c; }
.erp-btn--outline { background: #fff; border-color: #cbd5e1; color: #475569; }
.erp-btn--outline:hover { background: #f1f5f9; }
.erp-btn--ghost-red    { background: #fef2f2; border: 1px solid #fecaca; color: #dc2626; }
.erp-btn--ghost-red:hover { background: #fee2e2; }
.erp-btn--ghost-purple { background: #faf5ff; border: 1px solid #e9d5ff; color: #7c3aed; }
.erp-btn--ghost-purple:hover { background: #f3e8ff; }

@keyframes spin { to { transform: rotate(360deg); } }
.erp-spin { animation: spin 0.8s linear infinite; }

/* ═══════════════════════════════════════════════════════════════
   TABLA
═══════════════════════════════════════════════════════════════ */
.erp-table-wrap {
    background: #fff;
    border: 1px solid #dde3ea;
    border-radius: 10px;
    overflow: hidden;
    overflow-x: auto;
}
.erp-table {
    width: 100%;
    border-collapse: collapse;
    font-size: 0.8rem;
}
.erp-table thead tr {
    background: #1a3a5c;
}
.erp-table thead th {
    padding: 11px 14px;
    text-align: left;
    font-size: 0.68rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.07em;
    color: rgba(255,255,255,.85);
    white-space: nowrap;
    border-bottom: 2px solid #132d47;
}
.erp-table td {
    padding: 9px 14px;
    border-bottom: 1px solid #edf2f7;
    vertical-align: middle;
}
.erp-table tbody tr:nth-child(even) { background: #f4f7fb; }
.erp-table tbody tr:hover { background: #e8f0fe !important; }

/* Border-left por estado */
.erp-row--red    { border-left: 3px solid #dc2626; }
.erp-row--orange { border-left: 3px solid #f97316; }
.erp-row--green  { border-left: 3px solid #16a34a; }

/* Avatar alumno */
.erp-alumno { display: flex; align-items: center; gap: 9px; }
.erp-alumno__avatar {
    width: 28px; height: 28px;
    border-radius: 50%;
    background: #1a3a5c;
    color: #fff;
    font-size: 0.72rem;
    font-weight: 700;
    display: flex; align-items: center; justify-content: center;
    flex-shrink: 0;
    text-transform: uppercase;
}
.erp-alumno__name { font-weight: 600; color: #1e293b; }

/* Saldo */
.erp-saldo { font-weight: 700; font-size: 0.85rem; }
.erp-saldo--red    { color: #dc2626; }
.erp-saldo--orange { color: #f97316; }
.erp-saldo--green  { color: #16a34a; }

/* Días */
.erp-dias { font-size: 0.72rem; font-weight: 700; padding: 2px 7px; border-radius: 999px; }
.erp-dias--red    { background: #fef2f2; color: #dc2626; }
.erp-dias--orange { background: #fff7ed; color: #f97316; }
.erp-dias--blue   { background: #eff6ff; color: #2563eb; }

/* Badge estado */
.erp-badge {
    display: inline-block;
    padding: 2px 10px;
    border-radius: 999px;
    font-size: 0.65rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.04em;
    white-space: nowrap;
}
.erp-badge--red    { background: #fef2f2; color: #dc2626; border: 1px solid #fecaca; }
.erp-badge--orange { background: #fff7ed; color: #ea580c; border: 1px solid #fed7aa; }
.erp-badge--green  { background: #f0fdf4; color: #16a34a; border: 1px solid #bbf7d0; }

/* WhatsApp link */
.erp-wa-link { display: flex; align-items: center; gap: 3px; color: #16a34a; font-size: 0.76rem; text-decoration: none; }
.erp-wa-link:hover { color: #15803d; }

/* Acciones por fila */
.erp-actions { display: flex; gap: 4px; justify-content: center; }
.erp-action {
    width: 30px; height: 30px;
    border-radius: 6px;
    display: flex; align-items: center; justify-content: center;
    cursor: pointer;
    border: 1px solid transparent;
    transition: all 0.15s;
}
.erp-action--pay    { background: #f0fdf4; border-color: #bbf7d0; color: #16a34a; }
.erp-action--pay:hover { background: #dcfce7; }
.erp-action--cancel { background: #fef2f2; border-color: #fecaca; color: #dc2626; }
.erp-action--cancel:hover { background: #fee2e2; }
.erp-action--plan   { background: #faf5ff; border-color: #e9d5ff; color: #7c3aed; }
.erp-action--plan:hover { background: #f3e8ff; }

/* ═══════════════════════════════════════════════════════════════
   PAGINACIÓN
═══════════════════════════════════════════════════════════════ */
.erp-pagination {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 12px 6px;
    font-size: 0.78rem;
    color: #64748b;
}
.erp-pag-info {}
.erp-pag-btns { display: flex; gap: 4px; }
.erp-pag-btn {
    min-width: 32px;
    height: 32px;
    padding: 0 8px;
    border-radius: 6px;
    border: 1px solid #dde3ea;
    background: #fff;
    font-size: 0.78rem;
    color: #475569;
    cursor: pointer;
    display: flex; align-items: center; justify-content: center;
    transition: all 0.12s;
}
.erp-pag-btn:hover:not(:disabled) { background: #f1f5f9; border-color: #0070f3; color: #0070f3; }
.erp-pag-btn--active { background: #1a3a5c; border-color: #1a3a5c; color: #fff; font-weight: 700; }
.erp-pag-btn:disabled { opacity: 0.35; cursor: not-allowed; }

/* ═══════════════════════════════════════════════════════════════
   MODALES
═══════════════════════════════════════════════════════════════ */
.modal-hdr {
    display: flex; align-items: flex-start;
    justify-content: space-between;
    padding: 20px 24px;
    color: white;
}
.modal-hdr--blue   { background: linear-gradient(135deg, #1a3a5c, #0070f3); }
.modal-hdr--red    { background: linear-gradient(135deg, #7f1d1d, #ef4444); }
.modal-hdr--purple { background: linear-gradient(135deg, #4c1d95, #7c3aed); }
.modal-hdr__title { font-size: 1.05rem; font-weight: 700; display: flex; align-items: center; margin-bottom: 4px; }
.modal-hdr__sub   { font-size: 0.78rem; opacity: 0.85; }

.modal-alumno-chip {
    display: flex;
    align-items: center;
    background: #f1f5f9;
    border: 1px solid #dde3ea;
    border-radius: 8px;
    padding: 8px 14px;
    font-size: 0.83rem;
    color: #1e293b;
}

/* Resumen pago alumno */
.pago-alumno-info {
    background: #f8fafc;
    border: 1px solid #dde3ea;
    border-radius: 10px;
    padding: 14px 18px;
}
.pago-alumno-info__row { display: flex; align-items: center; gap: 8px; margin-bottom: 6px; font-size: 0.84rem; }
.pago-alumno-info__row:last-child { margin-bottom: 0; }
.pago-info-label { color: #64748b; min-width: 130px; font-size: 0.78rem; }
.pago-info-val   { color: #1e293b; }

/* File upload zone */
.erp-file-upload {
    position: relative;
    border: 2px dashed #cbd5e1;
    border-radius: 10px;
    padding: 18px;
    text-align: center;
    cursor: pointer;
    transition: all 0.15s;
    background: #f8fafc;
}
.erp-file-upload:hover { border-color: #0070f3; background: #eff6ff; }
.erp-file-upload--filled { border-color: #16a34a; background: #f0fdf4; }
.erp-file-input {
    position: absolute; inset: 0;
    opacity: 0; cursor: pointer; width: 100%; height: 100%;
}

/* Abonos lista */
.abonos-lista {
    display: flex; flex-direction: column; gap: 8px;
    max-height: 220px; overflow-y: auto; padding-right: 4px;
}
.abono-item {
    display: flex; align-items: center; gap: 12px;
    padding: 10px 14px; border-radius: 10px;
    border: 1.5px solid #e5e7eb; cursor: pointer;
    transition: all 0.12s; background: white;
}
.abono-item:hover { border-color: #fca5a5; background: #fef2f2; }
.abono-item--sel { border-color: #ef4444; background: #fef2f2; box-shadow: 0 0 0 2px #fca5a5; }

/* Cuotas plan */
.cuotas-lista { display: flex; flex-direction: column; gap: 8px; max-height: 280px; overflow-y: auto; padding-right: 4px; }
.cuota-row { display: flex; align-items: center; gap: 8px; }
.cuota-num { font-size: 0.72rem; font-weight: 700; color: #7c3aed; min-width: 26px; }
</style>
