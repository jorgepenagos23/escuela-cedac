<script setup>
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import { Head } from "@inertiajs/vue3";
import TablaAlumnos from "./TablaAlumnos.vue";
import { ref, computed, onMounted, onUnmounted } from 'vue';
import axios from 'axios';
import { useErpWindows } from '@/Composables/useErpWindows';

const erp = useErpWindows();

// ── Datos de cartera ──────────────────────────────────────────────────────────
const vencidos    = ref([]);
const estaSemana  = ref([]);
const proximos    = ref([]);
const cargando    = ref(false);

// ── Filtros ───────────────────────────────────────────────────────────────────
const buscarCartera       = ref('');
const filtroEstadoCartera = ref('urgente');

const carteraUnificada = computed(() => {
    const todos = [
        ...vencidos.value.map(a => ({ ...a, estado: 'Vencido' })),
        ...estaSemana.value.map(a => ({ ...a, estado: 'Esta Semana' })),
        ...proximos.value.map(a => ({ ...a, estado: 'Próximo' })),
    ];
    let lista = todos;
    if (filtroEstadoCartera.value === 'urgente')
        lista = lista.filter(a => a.estado === 'Vencido' || a.estado === 'Esta Semana');
    else if (filtroEstadoCartera.value === 'proximos')
        lista = lista.filter(a => a.estado === 'Próximo');

    if (buscarCartera.value.trim()) {
        const q = buscarCartera.value.toLowerCase();
        lista = lista.filter(a =>
            a.nombre_alumno?.toLowerCase().includes(q) ||
            a.diplomado?.toLowerCase().includes(q) ||
            a.celular?.toLowerCase().includes(q)
        );
    }
    return lista;
});

// ── KPIs ──────────────────────────────────────────────────────────────────────
const totalSaldoVencido  = computed(() => vencidos.value.reduce((s, a) => s + parseFloat(a.saldo || 0), 0));
const totalSaldoSemana   = computed(() => estaSemana.value.reduce((s, a) => s + parseFloat(a.saldo || 0), 0));
const totalSaldoProximos = computed(() => proximos.value.reduce((s, a) => s + parseFloat(a.saldo || 0), 0));
const totalCartera       = computed(() => totalSaldoVencido.value + totalSaldoSemana.value + totalSaldoProximos.value);

// ── Carga ─────────────────────────────────────────────────────────────────────
const cargarCalendario = async () => {
    cargando.value = true;
    try {
        const res = await axios.get('/api/v1/pagos/calendario');
        vencidos.value   = res.data.vencidos;
        estaSemana.value = res.data.esta_semana;
        proximos.value   = res.data.proximos;
        seleccionados.value = [];   // limpiar selección al recargar
    } catch (e) { console.error('Error calendario:', e); }
    finally { cargando.value = false; }
};

// ── Snackbar ──────────────────────────────────────────────────────────────────
const snackbar   = ref(false);
const snackMsg   = ref('');
const snackColor = ref('success');
const mostrarSnack = (msg, color = 'success') => { snackMsg.value = msg; snackColor.value = color; snackbar.value = true; };

// ══════════════════════════════════════════════════════════════════════════════
// MULTI-SELECCIÓN
// ══════════════════════════════════════════════════════════════════════════════
const seleccionados = ref([]);   // array de item.id

const estaSeleccionado  = (id) => seleccionados.value.includes(id);
const algunoSeleccionado = computed(() => seleccionados.value.length > 0);
const todosMarcados = computed(
    () => carteraUnificada.value.length > 0 &&
          carteraUnificada.value.every(i => seleccionados.value.includes(i.id))
);
const indeterminado = computed(
    () => algunoSeleccionado.value && !todosMarcados.value
);

const toggleItem = (id) => {
    const idx = seleccionados.value.indexOf(id);
    if (idx >= 0) seleccionados.value.splice(idx, 1);
    else seleccionados.value.push(id);
};

const toggleTodos = () => {
    if (todosMarcados.value) {
        seleccionados.value = [];
    } else {
        seleccionados.value = carteraUnificada.value.map(i => i.id);
    }
};

const limpiarSeleccion = () => { seleccionados.value = []; };

const itemsSeleccionados = computed(() =>
    carteraUnificada.value.filter(i => seleccionados.value.includes(i.id))
);

// ══════════════════════════════════════════════════════════════════════════════
// WHATSAPP / EMAIL INDIVIDUAL
// ══════════════════════════════════════════════════════════════════════════════
const buildMensajeWA = (item) => {
    const diasRetraso = item.dias_retraso > 0 ? item.dias_retraso : 0;
    const urgencia = item.estado === 'Vencido'
        ? `con *${diasRetraso} día(s) de retraso*`
        : item.estado === 'Esta Semana'
            ? 'que vence *esta semana*'
            : `con vencimiento el *${formatFecha(item.fecha_pago)}*`;
    return `Hola *${item.nombre_alumno}* 👋\n\nLe recordamos que tiene una mensualidad pendiente del programa *${item.diplomado}* ${urgencia}, por un monto de *$${Number(item.saldo).toLocaleString('es-MX')} MXN*.\n\nPuede realizar su pago directamente en nuestras oficinas o mediante transferencia bancaria.\n\n¡Gracias por su preferencia! 🎓\n— *CEDAC*`;
};

const abrirWhatsApp = (item) => {
    if (!item.celular) { mostrarSnack('Este alumno no tiene celular registrado.', 'warning'); return; }
    const num = '52' + item.celular.replace(/[^0-9]/g, '');
    window.open(`https://wa.me/${num}?text=${encodeURIComponent(buildMensajeWA(item))}`, '_blank');
};

const abrirEmail = (item) => {
    if (!item.correo) { mostrarSnack('Este alumno no tiene correo registrado.', 'warning'); return; }
    const asunto = `Recordatorio de pago – ${item.diplomado}`;
    const cuerpo = `Estimado(a) ${item.nombre_alumno},\n\nLe recordamos que tiene una mensualidad pendiente del programa "${item.diplomado}" por $${Number(item.saldo).toLocaleString('es-MX')} MXN con fecha de vencimiento el ${formatFecha(item.fecha_pago)}.\n\nPor favor comuníquese con nosotros para coordinar su pago.\n\nAtentamente,\nCEDAC`;
    window.location.href = `mailto:${item.correo}?subject=${encodeURIComponent(asunto)}&body=${encodeURIComponent(cuerpo)}`;
};

// ══════════════════════════════════════════════════════════════════════════════
// WHATSAPP MASIVO
// ══════════════════════════════════════════════════════════════════════════════
const modalWAMasivo   = ref(false);
const enviandoMasivo  = ref(false);
const waMasivoProgreso = ref(0);

const conCelular    = computed(() => itemsSeleccionados.value.filter(i => i.celular));
const sinCelular    = computed(() => itemsSeleccionados.value.filter(i => !i.celular));

const abrirModalWAMasivo = () => {
    if (!algunoSeleccionado.value) { mostrarSnack('Selecciona al menos un alumno.', 'warning'); return; }
    modalWAMasivo.value   = true;
    waMasivoProgreso.value = 0;
};

const enviarWAMasivo = async () => {
    const lista = conCelular.value;
    if (!lista.length) { mostrarSnack('Ningún alumno seleccionado tiene celular.', 'warning'); return; }
    enviandoMasivo.value = true;
    waMasivoProgreso.value = 0;
    for (let i = 0; i < lista.length; i++) {
        const item = lista[i];
        const num = '52' + item.celular.replace(/[^0-9]/g, '');
        window.open(`https://wa.me/${num}?text=${encodeURIComponent(buildMensajeWA(item))}`, '_blank');
        waMasivoProgreso.value = Math.round(((i + 1) / lista.length) * 100);
        await new Promise(r => setTimeout(r, 600));
    }
    enviandoMasivo.value = false;
    mostrarSnack(`${lista.length} mensajes de WhatsApp abiertos.`, 'success');
    modalWAMasivo.value = false;
};

const copiarNumeros = () => {
    const nums = conCelular.value.map(i => i.celular.replace(/[^0-9]/g, '')).join('\n');
    navigator.clipboard.writeText(nums);
    mostrarSnack('Números copiados al portapapeles.', 'success');
};

// ══════════════════════════════════════════════════════════════════════════════
// EXPORTAR EXCEL
// ══════════════════════════════════════════════════════════════════════════════
const exportarExcel = (soloSeleccionados = false) => {
    import('xlsx').then(XLSX => {
        const fuente = soloSeleccionados ? itemsSeleccionados.value : carteraUnificada.value;
        if (!fuente.length) { mostrarSnack('No hay registros para exportar.', 'warning'); return; }

        const datos = fuente.map((item, i) => ({
            'No.':              i + 1,
            'Alumno':           item.nombre_alumno,
            'Programa':         item.diplomado,
            'Saldo Pendiente':  Number(item.saldo),
            'Fecha de Pago':    item.fecha_pago,
            'Días Retraso':     item.dias_retraso > 0 ? item.dias_retraso : 0,
            'Estado':           item.estado,
            'Celular':          item.celular || '',
            'Correo':           item.correo  || '',
        }));

        // Fila de totales al final
        datos.push({
            'No.':             '',
            'Alumno':          'TOTAL',
            'Programa':        '',
            'Saldo Pendiente': fuente.reduce((s, a) => s + parseFloat(a.saldo || 0), 0),
            'Fecha de Pago':   '',
            'Días Retraso':    '',
            'Estado':          `${fuente.length} registros`,
            'Celular':         '',
            'Correo':          '',
        });

        const ws = XLSX.utils.json_to_sheet(datos);
        // Anchos de columna
        ws['!cols'] = [
            { wch: 5 }, { wch: 32 }, { wch: 36 }, { wch: 16 },
            { wch: 14 }, { wch: 12 }, { wch: 14 }, { wch: 14 }, { wch: 26 },
        ];
        const wb = XLSX.utils.book_new();
        const nombreHoja = soloSeleccionados ? 'Selección' : 'Cartera Completa';
        XLSX.utils.book_append_sheet(wb, ws, nombreHoja);
        const nombreArchivo = soloSeleccionados
            ? `Seleccion_Cobranza_${fuente.length}.xlsx`
            : 'Cartera_Cobranza.xlsx';
        XLSX.writeFile(wb, nombreArchivo);
        mostrarSnack('Excel generado correctamente.', 'success');
    });
};

// ── Exportar PDF ──────────────────────────────────────────────────────────────
const exportarPDF = () => {
    Promise.all([import('jspdf'), import('jspdf-autotable')]).then(([jsPDFModule]) => {
        const jsPDF = jsPDFModule.default || jsPDFModule.jsPDF;
        const doc = new jsPDF();
        doc.setFontSize(14);
        doc.text('Cartera de Cobranza – CEDAC', 14, 15);
        doc.setFontSize(9);
        doc.text(`Generado: ${new Date().toLocaleDateString('es-MX')}`, 14, 22);
        doc.autoTable({
            startY: 27,
            head: [['#', 'Alumno', 'Programa', 'Saldo', 'Vence', 'Estado']],
            body: carteraUnificada.value.map((item, i) => [
                i + 1, item.nombre_alumno, item.diplomado,
                `$${Number(item.saldo).toLocaleString('es-MX')}`,
                item.fecha_pago, item.estado,
            ]),
            theme: 'grid',
            headStyles: { fillColor: [30, 41, 59] },
            styles: { fontSize: 8 },
        });
        doc.save('Cartera_Cobranza.pdf');
        mostrarSnack('PDF generado correctamente.', 'success');
    });
};

// ── Cobrar ────────────────────────────────────────────────────────────────────
const buscadorRef = ref(null);
const hacerCobro = (nombre) => { if (buscadorRef.value) buscadorRef.value.forzarBusqueda(nombre); };

// ── Modal Cancelar Abono ──────────────────────────────────────────────────────
const modalCancelar      = ref(false);
const alumnoSeleccionado = ref(null);
const historialAbonos    = ref([]);
const loadingHistorial   = ref(false);
const abonoCancelarId    = ref(null);
const motivoCancelacion  = ref('');
const cancelando         = ref(false);

const abrirModalCancelar = async (alumno) => {
    alumnoSeleccionado.value = { id: alumno.id, nombre_alumno: alumno.nombre_alumno, diplomado: alumno.diplomado };
    abonoCancelarId.value   = null;
    motivoCancelacion.value = '';
    historialAbonos.value   = [];
    loadingHistorial.value  = true;
    modalCancelar.value     = true;
    try {
        const res = await axios.get(`/api/v1/alumno/${alumno.id}/plan-pagos`);
        historialAbonos.value = res.data.pagos_realizados.filter(p => p.status !== 'Cancelado');
    } catch { mostrarSnack('No se pudo cargar el historial.', 'error'); }
    finally { loadingHistorial.value = false; }
};

const confirmarCancelacion = async () => {
    if (!abonoCancelarId.value) { mostrarSnack('Selecciona el abono a cancelar.', 'warning'); return; }
    if (!motivoCancelacion.value.trim()) { mostrarSnack('Escribe el motivo de cancelación.', 'warning'); return; }
    cancelando.value = true;
    try {
        await axios.post(`/api/v1/pagos/${abonoCancelarId.value}/cancelar`, { motivo: motivoCancelacion.value });
        mostrarSnack('Abono cancelado y saldo restituido.', 'success');
        modalCancelar.value = false;
        cargarCalendario();
    } catch (e) { mostrarSnack(e.response?.data?.error ?? 'Error al cancelar.', 'error'); }
    finally { cancelando.value = false; }
};

// ── Modal Reprogramar Plan ────────────────────────────────────────────────────
const modalPlan     = ref(false);
const planData      = ref(null);
const loadingPlan   = ref(false);
const guardandoPlan = ref(false);
const planEditable  = ref([]);

const totalPlan = computed(() => planEditable.value.reduce((s, c) => s + parseFloat(c.monto || 0), 0));

const abrirModalPlan = async (alumno) => {
    alumnoSeleccionado.value = { id: alumno.id, nombre_alumno: alumno.nombre_alumno, diplomado: alumno.diplomado };
    loadingPlan.value  = true;
    planEditable.value = [];
    planData.value     = null;
    modalPlan.value    = true;
    try {
        const res = await axios.get(`/api/v1/alumno/${alumno.id}/plan-pagos`);
        planData.value = res.data;
        if (res.data.plan_pagos?.length) planEditable.value = res.data.plan_pagos.map(p => ({ ...p }));
        else generarPlanAuto(res.data.inscripcion);
    } catch { mostrarSnack('No se pudo cargar el plan.', 'error'); }
    finally { loadingPlan.value = false; }
};

const generarPlanAuto = (ins) => {
    const saldo = parseFloat(ins.saldo) || 0;
    const n = 4; const c = Math.ceil(saldo / n);
    planEditable.value = Array.from({ length: n }, (_, i) => {
        const f = new Date(); f.setMonth(f.getMonth() + i + 1);
        return { fecha: f.toISOString().split('T')[0], monto: i < n - 1 ? c : saldo - c * (n - 1), descripcion: `Mensualidad ${i + 1}`, estado: 'pendiente', abonado: 0 };
    });
};

const agregarCuota = () => {
    const u = planEditable.value[planEditable.value.length - 1];
    const f = u ? new Date(u.fecha) : new Date(); f.setMonth(f.getMonth() + 1);
    planEditable.value.push({ fecha: f.toISOString().split('T')[0], monto: 0, descripcion: `Mensualidad ${planEditable.value.length + 1}`, estado: 'pendiente', abonado: 0 });
};

const guardarPlan = async () => {
    if (!planEditable.value.length) { mostrarSnack('Agrega al menos una cuota.', 'warning'); return; }
    guardandoPlan.value = true;
    try {
        await axios.post(`/api/v1/alumno/${alumnoSeleccionado.value.id}/plan-pagos`, { plan: planEditable.value });
        mostrarSnack('Plan guardado correctamente.', 'success');
        modalPlan.value = false;
    } catch (e) { mostrarSnack(e.response?.data?.message ?? 'Error al guardar.', 'error'); }
    finally { guardandoPlan.value = false; }
};

// ── Helpers ───────────────────────────────────────────────────────────────────
const formatFecha = (f) => {
    if (!f) return '—';
    const [y, m, d] = f.split('-');
    const meses = ['ene','feb','mar','abr','may','jun','jul','ago','sep','oct','nov','dic'];
    return `${d} ${meses[parseInt(m) - 1]} ${y}`;
};

const colorEstado = (s) => ({ Pagado: 'success', Activo: 'blue', Cancelado: 'error' }[s] ?? 'grey');

// ── Sumatoria de la tabla visible ─────────────────────────────────────────────
const totalMostrado = computed(() =>
    carteraUnificada.value.reduce((s, a) => s + parseFloat(a.saldo || 0), 0)
);
const totalVencidoMostrado = computed(() =>
    carteraUnificada.value.filter(a => a.estado === 'Vencido').reduce((s, a) => s + parseFloat(a.saldo || 0), 0)
);
const totalSemanaMostrado = computed(() =>
    carteraUnificada.value.filter(a => a.estado === 'Esta Semana').reduce((s, a) => s + parseFloat(a.saldo || 0), 0)
);
const totalProximoMostrado = computed(() =>
    carteraUnificada.value.filter(a => a.estado === 'Próximo').reduce((s, a) => s + parseFloat(a.saldo || 0), 0)
);
const totalSeleccionadosMostrado = computed(() =>
    itemsSeleccionados.value.reduce((s, a) => s + parseFloat(a.saldo || 0), 0)
);
const promedioSaldo = computed(() => {
    if (!carteraUnificada.value.length) return 0;
    return totalMostrado.value / carteraUnificada.value.length;
});

onMounted(async () => {
    await cargarCalendario();
    erp.registerTabExport('PagosMensualidades', { label: 'Exportar Excel', icon: 'mdi-microsoft-excel', fn: () => exportarExcel(false) });
});
onUnmounted(() => erp.unregisterTabExport('PagosMensualidades'));
</script>

<template>
  <AuthenticatedLayout>
    <v-app class="bg-slate-50">
      <v-main>
        <Head title="Cartera de Cobranza" />

        <!-- TablaAlumnos oculta: conserva el modal de cobro -->
        <div style="display:none"><TablaAlumnos ref="buscadorRef" /></div>

        <div class="min-h-screen py-6">
          <div class="max-w-screen-xl mx-auto px-4 sm:px-6 lg:px-8">

            <!-- ══ ENCABEZADO ════════════════════════════════════════════════ -->
            <div class="flex items-center justify-between mb-6 flex-wrap gap-3">
              <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-slate-800 flex items-center justify-center">
                  <v-icon color="white" size="20">mdi-currency-usd</v-icon>
                </div>
                <div>
                  <h1 class="text-xl font-bold text-slate-800 leading-tight">Cartera de Cobranza</h1>
                  <p class="text-xs text-slate-500">Mensualidades pendientes · CEDAC</p>
                </div>
              </div>
              <div class="flex items-center gap-2 flex-wrap">
                <v-btn size="small" variant="tonal" prepend-icon="mdi-refresh" :loading="cargando" @click="cargarCalendario">Actualizar</v-btn>
                <v-btn size="small" variant="flat" color="green-darken-2" prepend-icon="mdi-microsoft-excel" @click="exportarExcel(false)">Excel</v-btn>
                <v-btn size="small" variant="flat" color="red-darken-2" prepend-icon="mdi-file-pdf-box" @click="exportarPDF">PDF</v-btn>
              </div>
            </div>

            <!-- ══ KPI CARDS ═════════════════════════════════════════════════ -->
            <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 mb-6">
              <div class="kpi" :class="{'kpi--active': filtroEstadoCartera==='todos'}" @click="filtroEstadoCartera='todos'">
                <div class="kpi__icon bg-slate-100"><v-icon size="18">mdi-account-group-outline</v-icon></div>
                <div>
                  <div class="kpi__label">Total Cartera</div>
                  <div class="kpi__value text-slate-800">{{ vencidos.length + estaSemana.length + proximos.length }}</div>
                  <div class="kpi__sub">${{ Math.round(totalCartera).toLocaleString('es-MX') }}</div>
                </div>
              </div>
              <div class="kpi" :class="{'kpi--active': filtroEstadoCartera==='urgente'}" @click="filtroEstadoCartera='urgente'">
                <div class="kpi__icon bg-red-100"><v-icon color="red-darken-2" size="18">mdi-alert-circle-outline</v-icon></div>
                <div>
                  <div class="kpi__label">Por Cobrar</div>
                  <div class="kpi__value text-red-600">{{ vencidos.length + estaSemana.length }}</div>
                  <div class="kpi__sub text-red-400">${{ Math.round(totalSaldoVencido + totalSaldoSemana).toLocaleString('es-MX') }}</div>
                </div>
              </div>
              <div class="kpi" @click="filtroEstadoCartera='urgente'">
                <div class="kpi__icon bg-orange-50"><v-icon color="orange-darken-2" size="18">mdi-clock-fast</v-icon></div>
                <div>
                  <div class="kpi__label">Vencidos</div>
                  <div class="kpi__value text-orange-700">{{ vencidos.length }}</div>
                  <div class="kpi__sub text-orange-400">${{ Math.round(totalSaldoVencido).toLocaleString('es-MX') }}</div>
                </div>
              </div>
              <div class="kpi" :class="{'kpi--active': filtroEstadoCartera==='proximos'}" @click="filtroEstadoCartera='proximos'">
                <div class="kpi__icon bg-emerald-50"><v-icon color="green-darken-2" size="18">mdi-calendar-check-outline</v-icon></div>
                <div>
                  <div class="kpi__label">Al Corriente</div>
                  <div class="kpi__value text-emerald-700">{{ proximos.length }}</div>
                  <div class="kpi__sub text-emerald-500">${{ Math.round(totalSaldoProximos).toLocaleString('es-MX') }}</div>
                </div>
              </div>
            </div>

            <!-- ══ BARRA DE FILTROS ══════════════════════════════════════════ -->
            <div class="bg-white border border-slate-200 rounded-xl px-4 py-3 flex flex-wrap items-center justify-between gap-3 mb-4 shadow-sm">
              <div class="flex items-center gap-1 bg-slate-100 rounded-lg p-1">
                <button v-for="tab in [
                    { key:'urgente',  label:'Por Cobrar',   icon:'mdi-alert-circle',     count: vencidos.length + estaSemana.length },
                    { key:'proximos', label:'Al Corriente', icon:'mdi-calendar-check',   count: proximos.length },
                    { key:'todos',    label:'Todos',         icon:'mdi-view-list',        count: vencidos.length + estaSemana.length + proximos.length },
                  ]" :key="tab.key"
                  class="tab-btn" :class="filtroEstadoCartera === tab.key ? 'tab-btn--active' : ''"
                  @click="filtroEstadoCartera = tab.key">
                  <v-icon size="13" class="mr-1">{{ tab.icon }}</v-icon>
                  {{ tab.label }}
                  <span class="tab-badge">{{ tab.count }}</span>
                </button>
              </div>
              <div class="flex items-center gap-2 flex-wrap">
                <v-text-field
                  v-model="buscarCartera"
                  placeholder="Buscar alumno, programa, celular..."
                  variant="outlined" density="compact" hide-details
                  prepend-inner-icon="mdi-magnify" clearable bg-color="white"
                  style="min-width:220px; max-width:300px"
                />
                <v-dialog v-model="modalCancelar" max-width="640" persistent>
                  <template #activator="{ props }">
                    <v-btn v-bind="props" size="small" variant="tonal" color="red-darken-1" prepend-icon="mdi-cancel">Cancelar Abono</v-btn>
                  </template>
                  <v-card rounded="xl" class="overflow-hidden">
                    <div class="modal-hdr modal-hdr--red">
                      <span class="modal-hdr__title"><v-icon class="mr-1" size="18">mdi-cancel</v-icon>Cancelar Abono</span>
                      <v-btn icon="mdi-close" variant="text" color="white" size="small" @click="modalCancelar=false" />
                    </div>
                    <v-card-text class="pa-5">
                      <div v-if="!alumnoSeleccionado" class="text-center py-6 text-slate-400 text-sm">
                        <v-icon size="36" class="mb-2">mdi-account-question</v-icon>
                        <p>Abre este modal desde el menú <strong>⋯</strong> de la fila del alumno.</p>
                      </div>
                      <template v-else>
                        <div class="alumno-pill mb-4">
                          <v-icon size="16" color="indigo">mdi-account</v-icon>
                          <span class="font-semibold">{{ alumnoSeleccionado.nombre_alumno }}</span>
                          <span class="text-slate-400 text-xs">· {{ alumnoSeleccionado.diplomado }}</span>
                        </div>
                        <div v-if="loadingHistorial" class="text-center py-4"><v-progress-circular indeterminate color="red" size="28" /></div>
                        <template v-else-if="historialAbonos.length">
                          <p class="text-xs font-semibold text-slate-600 mb-2">Selecciona el abono a cancelar:</p>
                          <div class="abonos-lista">
                            <div v-for="ab in historialAbonos" :key="ab.id"
                                 class="abono-row" :class="abonoCancelarId === ab.id ? 'abono-row--sel' : ''"
                                 @click="abonoCancelarId = ab.id">
                              <v-icon :color="abonoCancelarId===ab.id?'red':'green'" size="16">
                                {{ abonoCancelarId===ab.id ? 'mdi-radiobox-marked' : 'mdi-radiobox-blank' }}
                              </v-icon>
                              <div class="flex-1">
                                <span class="font-bold text-green-700 text-sm">+${{ Number(ab.pago_colegiatura).toLocaleString('es-MX') }}</span>
                                <span class="text-xs text-slate-400 ml-2">{{ ab.Fecha_PrimerContacto }} · #{{ ab.id }}</span>
                              </div>
                              <v-chip size="x-small" :color="colorEstado(ab.status)" variant="flat">{{ ab.status }}</v-chip>
                            </div>
                          </div>
                          <v-divider class="my-3" />
                          <v-textarea v-model="motivoCancelacion" label="Motivo de cancelación *" rows="2" variant="outlined" density="compact" counter="500" maxlength="500" />
                        </template>
                        <div v-else class="text-center py-4 text-slate-400 text-sm">Este alumno no tiene abonos activos.</div>
                      </template>
                    </v-card-text>
                    <v-card-actions class="px-5 pb-4 pt-0 justify-end gap-2">
                      <v-btn variant="text" @click="modalCancelar=false; alumnoSeleccionado=null">Cerrar</v-btn>
                      <v-btn color="red-darken-2" variant="flat" prepend-icon="mdi-trash-can"
                             :loading="cancelando" :disabled="!abonoCancelarId||!motivoCancelacion.trim()"
                             @click="confirmarCancelacion">Confirmar Cancelación</v-btn>
                    </v-card-actions>
                  </v-card>
                </v-dialog>

                <v-dialog v-model="modalPlan" max-width="720" persistent>
                  <template #activator="{ props }">
                    <v-btn v-bind="props" size="small" variant="tonal" color="deep-purple" prepend-icon="mdi-calendar-edit">Plan de Pagos</v-btn>
                  </template>
                  <v-card rounded="xl" class="overflow-hidden">
                    <div class="modal-hdr modal-hdr--purple">
                      <span class="modal-hdr__title"><v-icon class="mr-1" size="18">mdi-calendar-edit</v-icon>Plan de Pagos</span>
                      <v-btn icon="mdi-close" variant="text" color="white" size="small" @click="modalPlan=false" />
                    </div>
                    <v-card-text class="pa-5">
                      <div v-if="!alumnoSeleccionado||!modalPlan" class="text-center py-6 text-slate-400 text-sm">
                        <v-icon size="36" class="mb-2">mdi-account-question</v-icon>
                        <p>Abre este modal desde el menú <strong>⋯</strong> de la fila del alumno.</p>
                      </div>
                      <template v-else>
                        <div class="alumno-pill mb-4">
                          <v-icon size="16" color="purple">mdi-account</v-icon>
                          <span class="font-semibold">{{ alumnoSeleccionado.nombre_alumno }}</span>
                          <span class="text-slate-400 text-xs">· {{ alumnoSeleccionado.diplomado }}</span>
                        </div>
                        <div v-if="planData" class="plan-info-grid mb-4">
                          <div class="plan-info-item"><span class="pi-label">Saldo pendiente</span><span class="pi-val text-red-600 font-bold">${{ Number(planData.inscripcion.saldo).toLocaleString('es-MX') }}</span></div>
                          <div class="plan-info-item"><span class="pi-label">Total del plan</span><span class="pi-val font-bold" :class="totalPlan > planData.inscripcion.saldo ? 'text-red-500':'text-emerald-600'">${{ Number(totalPlan).toLocaleString('es-MX') }}</span></div>
                        </div>
                        <div v-if="loadingPlan" class="text-center py-6"><v-progress-circular indeterminate color="purple" size="28" /></div>
                        <template v-else>
                          <div class="flex items-center justify-between mb-2">
                            <span class="text-xs font-bold text-slate-700 uppercase tracking-wide">Cuotas del Plan</span>
                            <v-btn size="x-small" variant="tonal" color="deep-purple" prepend-icon="mdi-plus" @click="agregarCuota">Agregar</v-btn>
                          </div>
                          <div class="cuotas-lista">
                            <div v-for="(c,i) in planEditable" :key="i" class="cuota-row items-center">
                              <span class="cuota-n">#{{ i+1 }}</span>
                              <v-text-field v-model="c.fecha" type="date" label="Fecha" variant="outlined" density="compact" hide-details class="flex-1" />
                              <v-text-field v-model.number="c.monto" type="number" label="Monto" variant="outlined" density="compact" hide-details prefix="$" style="max-width:110px" />
                              <v-text-field v-model="c.descripcion" label="Descripción" variant="outlined" density="compact" hide-details class="flex-1" />
                              
                              <div class="flex flex-col items-center justify-center min-w-[75px]">
                                <v-chip size="x-small"
                                  :color="c.estado === 'pagado' ? 'green' : (c.estado === 'parcial' ? 'orange' : 'grey')"
                                  class="mb-1 font-bold"
                                  variant="flat">
                                  {{ c.estado ? c.estado.toUpperCase() : 'PENDIENTE' }}
                                </v-chip>
                                <span v-if="c.abonado > 0" class="text-[10px] text-slate-500 font-bold whitespace-nowrap">
                                  Abo: ${{ c.abonado }}
                                </span>
                              </div>

                              <v-btn icon="mdi-trash-can-outline" size="x-small" variant="text" color="red" @click="planEditable.splice(i,1)" />
                            </div>
                            <p v-if="!planEditable.length" class="text-center text-slate-400 text-xs py-4">Sin cuotas. Agrega o genera automáticamente.</p>
                          </div>
                          <v-alert v-if="planData && totalPlan > planData.inscripcion.saldo" type="warning" variant="tonal" density="compact" class="mt-3" icon="mdi-alert">El total excede el saldo pendiente.</v-alert>
                        </template>
                      </template>
                    </v-card-text>
                    <v-card-actions class="px-5 pb-4 pt-0 justify-end gap-2">
                      <v-btn v-if="planData&&!planEditable.length" variant="tonal" color="deep-purple" prepend-icon="mdi-refresh" @click="generarPlanAuto(planData.inscripcion)">Auto (4 cuotas)</v-btn>
                      <v-btn variant="text" @click="modalPlan=false; alumnoSeleccionado=null">Cancelar</v-btn>
                      <v-btn color="deep-purple-darken-2" variant="flat" prepend-icon="mdi-content-save"
                             :loading="guardandoPlan" :disabled="!planEditable.length||!alumnoSeleccionado"
                             @click="guardarPlan">Guardar Plan</v-btn>
                    </v-card-actions>
                  </v-card>
                </v-dialog>
              </div>
            </div>

            <!-- ══ BARRA FLOTANTE DE SELECCIÓN ══════════════════════════════ -->
            <transition name="bulk-bar">
              <div v-if="algunoSeleccionado" class="bulk-bar mb-4">
                <div class="flex items-center gap-3">
                  <div class="bulk-bar__count">
                    <v-icon size="16" class="mr-1">mdi-checkbox-marked</v-icon>
                    {{ seleccionados.length }} alumno{{ seleccionados.length > 1 ? 's' : '' }} seleccionado{{ seleccionados.length > 1 ? 's' : '' }}
                  </div>
                  <span class="text-white/40 text-xs hidden sm:inline">·</span>
                  <span class="text-white/70 text-xs hidden sm:inline">
                    ${{ Math.round(totalSeleccionadosMostrado).toLocaleString('es-MX') }} MXN
                  </span>
                </div>
                <div class="flex items-center gap-2 flex-wrap">
                  <!-- WhatsApp masivo -->
                  <v-btn size="small" color="green-darken-1" variant="flat" rounded="lg"
                         prepend-icon="mdi-whatsapp" @click="abrirModalWAMasivo">
                    WhatsApp Masivo
                    <v-chip size="x-small" color="white" text-color="green" class="ml-1">{{ conCelular.length }}</v-chip>
                  </v-btn>
                  <!-- Exportar selección -->
                  <v-btn size="small" color="emerald" variant="flat" rounded="lg"
                         prepend-icon="mdi-microsoft-excel" @click="exportarExcel(true)">
                    Exportar Selección
                  </v-btn>
                  <!-- Limpiar -->
                  <v-btn size="small" variant="tonal" color="white" rounded="lg"
                         prepend-icon="mdi-close" @click="limpiarSeleccion">
                    Limpiar
                  </v-btn>
                </div>
              </div>
            </transition>

            <!-- ══ TABLA ERP ══════════════════════════════════════════════════ -->
            <div class="bg-white border border-slate-200 rounded-xl shadow-sm overflow-hidden">

              <div class="table-toolbar">
                <div class="flex items-center gap-2">
                  <span class="font-semibold text-sm text-white">
                    {{ filtroEstadoCartera==='urgente' ? 'Por Cobrar' : filtroEstadoCartera==='proximos' ? 'Al Corriente' : 'Cartera Completa' }}
                  </span>
                  <span class="toolbar-badge">{{ carteraUnificada.length }}</span>
                </div>
                <div v-if="cargando" class="flex items-center gap-2 text-xs text-slate-300">
                  <v-progress-circular indeterminate size="14" width="2" color="white" />
                  Actualizando...
                </div>
              </div>

              <div class="overflow-x-auto">
                <table class="erp-table">
                  <thead>
                    <tr>
                      <!-- Checkbox select-all -->
                      <th class="w-10 text-center">
                        <input type="checkbox"
                               :checked="todosMarcados"
                               :indeterminate="indeterminado"
                               class="erp-checkbox"
                               @change="toggleTodos" />
                      </th>
                      <th class="w-8">#</th>
                      <th>Alumno</th>
                      <th>Programa</th>
                      <th class="text-right w-32">Saldo</th>
                      <th class="w-28">Vence</th>
                      <th class="w-24">Atraso</th>
                      <th class="w-28 text-center">Estado</th>
                      <th class="w-32 text-center">Contacto</th>
                      <th class="w-44 text-center">Acciones</th>
                    </tr>
                  </thead>
                  <tbody>
                    <tr v-if="!carteraUnificada.length && !cargando">
                      <td colspan="10" class="text-center py-16 text-slate-400">
                        <v-icon size="40" class="mb-2 opacity-30">mdi-check-circle-outline</v-icon>
                        <p class="text-sm">Sin registros para este filtro.</p>
                      </td>
                    </tr>
                    <tr v-if="cargando && !carteraUnificada.length">
                      <td colspan="10" class="text-center py-16"><v-progress-circular indeterminate color="indigo" size="36" /></td>
                    </tr>
                    <tr
                      v-for="(item, idx) in carteraUnificada"
                      :key="item.id + '-' + idx"
                      class="erp-row"
                      :class="{
                        'erp-row--vencido':    item.estado === 'Vencido',
                        'erp-row--semana':     item.estado === 'Esta Semana',
                        'erp-row--proximo':    item.estado === 'Próximo',
                        'erp-row--selected':   estaSeleccionado(item.id),
                      }"
                    >
                      <!-- Checkbox -->
                      <td class="text-center">
                        <input type="checkbox" :checked="estaSeleccionado(item.id)"
                               class="erp-checkbox" @change="toggleItem(item.id)" />
                      </td>
                      <td class="text-xs text-slate-400 text-center">{{ idx + 1 }}</td>

                      <!-- Alumno -->
                      <td>
                        <div class="flex items-center gap-2">
                          <div class="avatar-ini"
                               :class="{
                                 'avatar-ini--red':    item.estado==='Vencido',
                                 'avatar-ini--orange': item.estado==='Esta Semana',
                                 'avatar-ini--green':  item.estado==='Próximo',
                               }">
                            {{ (item.nombre_alumno||'?').split(' ').filter(w=>w).slice(0,2).map(w=>w[0]).join('').toUpperCase() }}
                          </div>
                          <span class="font-semibold text-slate-800 text-sm leading-tight">{{ item.nombre_alumno }}</span>
                        </div>
                      </td>

                      <!-- Programa -->
                      <td class="text-xs text-indigo-700 font-medium max-w-[180px] truncate" :title="item.diplomado">{{ item.diplomado }}</td>

                      <!-- Saldo -->
                      <td class="text-right font-black text-sm"
                          :class="{'text-red-600':item.estado==='Vencido','text-orange-600':item.estado==='Esta Semana','text-slate-700':item.estado==='Próximo'}">
                        ${{ Number(item.saldo).toLocaleString('es-MX') }}
                      </td>

                      <!-- Vence -->
                      <td class="text-xs text-slate-500 whitespace-nowrap">{{ formatFecha(item.fecha_pago) }}</td>

                      <!-- Atraso -->
                      <td class="text-center">
                        <span v-if="item.dias_retraso > 0" class="atraso-badge">{{ item.dias_retraso }}d</span>
                        <span v-else class="text-xs text-slate-300">—</span>
                      </td>

                      <!-- Estado -->
                      <td class="text-center">
                        <span class="estado-chip"
                              :class="{'estado-chip--red':item.estado==='Vencido','estado-chip--orange':item.estado==='Esta Semana','estado-chip--green':item.estado==='Próximo'}">
                          {{ item.estado==='Esta Semana' ? 'Esta semana' : item.estado }}
                        </span>
                      </td>

                      <!-- Contacto -->
                      <td class="text-center">
                        <div class="flex items-center justify-center gap-1">
                          <v-tooltip text="Enviar WhatsApp" location="top">
                            <template #activator="{ props }">
                              <v-btn v-bind="props" size="x-small" :color="item.celular?'green-darken-1':'grey'" variant="tonal" icon="mdi-whatsapp" @click="abrirWhatsApp(item)" />
                            </template>
                          </v-tooltip>
                          <v-tooltip text="Enviar correo" location="top">
                            <template #activator="{ props }">
                              <v-btn v-bind="props" size="x-small" :color="item.correo?'blue-darken-1':'grey'" variant="tonal" icon="mdi-email-outline" @click="abrirEmail(item)" />
                            </template>
                          </v-tooltip>
                        </div>
                      </td>

                      <!-- Acciones -->
                      <td class="text-center">
                        <div class="flex items-center justify-center gap-1">
                          <v-btn size="x-small" color="green-darken-1" variant="flat" rounded="lg"
                                 prepend-icon="mdi-point-of-sale" class="text-xs"
                                 @click="hacerCobro(item.nombre_alumno)">Cobrar</v-btn>
                          <v-menu>
                            <template #activator="{ props }">
                              <v-btn v-bind="props" size="x-small" variant="tonal" color="slate" icon="mdi-dots-vertical" />
                            </template>
                            <v-list density="compact" class="text-sm">
                              <v-list-item prepend-icon="mdi-cancel" title="Cancelar Abono" @click="abrirModalCancelar(item); modalCancelar=true" />
                              <v-list-item prepend-icon="mdi-calendar-edit" title="Plan de Pagos" @click="abrirModalPlan(item); modalPlan=true" />
                              <v-divider />
                              <v-list-item prepend-icon="mdi-whatsapp" title="WhatsApp" @click="abrirWhatsApp(item)" />
                              <v-list-item prepend-icon="mdi-email-outline" title="Correo electrónico" @click="abrirEmail(item)" />
                            </v-list>
                          </v-menu>
                        </div>
                      </td>
                    </tr>
                  </tbody>
                </table>
              </div>

              <!-- ── Footer totales de tabla ── -->
              <div class="table-footer">
                <span class="text-xs text-slate-500">{{ carteraUnificada.length }} registros</span>
                <div v-if="algunoSeleccionado" class="text-xs text-indigo-600 font-semibold">
                  {{ seleccionados.length }} seleccionados · ${{ Math.round(totalSeleccionadosMostrado).toLocaleString('es-MX') }}
                </div>
                <div class="flex items-center gap-2 text-xs">
                  <span class="text-slate-500">Total mostrado:</span>
                  <span class="font-black text-slate-800 text-sm">${{ Math.round(totalMostrado).toLocaleString('es-MX') }} MXN</span>
                </div>
              </div>
            </div>

            <!-- ══ PANEL DE SUMATORIA ══════════════════════════════════════════ -->
            <div class="mt-4 bg-white border border-slate-200 rounded-xl shadow-sm overflow-hidden">
              <div class="px-4 py-3 border-b border-slate-100 flex items-center gap-2">
                <v-icon size="16" color="indigo">mdi-sigma</v-icon>
                <span class="text-xs font-bold text-slate-700 uppercase tracking-wide">Sumatoria de la Vista Actual</span>
                <span class="text-xs text-slate-400 ml-1">({{ carteraUnificada.length }} registros)</span>
              </div>
              <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-5 divide-x divide-y md:divide-y-0 divide-slate-100">

                <!-- Total general -->
                <div class="sum-cell sum-cell--highlight">
                  <div class="sum-label">Total General</div>
                  <div class="sum-value text-slate-800">${{ Math.round(totalMostrado).toLocaleString('es-MX') }}</div>
                  <div class="sum-sub">{{ carteraUnificada.length }} alumnos</div>
                </div>

                <!-- Vencidos -->
                <div class="sum-cell">
                  <div class="flex items-center gap-1 mb-1">
                    <span class="w-2 h-2 rounded-full bg-red-500 inline-block"></span>
                    <div class="sum-label text-red-600">Vencidos</div>
                  </div>
                  <div class="sum-value text-red-600">${{ Math.round(totalVencidoMostrado).toLocaleString('es-MX') }}</div>
                  <div class="sum-sub">{{ carteraUnificada.filter(a=>a.estado==='Vencido').length }} alumnos</div>
                </div>

                <!-- Esta semana -->
                <div class="sum-cell">
                  <div class="flex items-center gap-1 mb-1">
                    <span class="w-2 h-2 rounded-full bg-orange-400 inline-block"></span>
                    <div class="sum-label text-orange-600">Esta Semana</div>
                  </div>
                  <div class="sum-value text-orange-600">${{ Math.round(totalSemanaMostrado).toLocaleString('es-MX') }}</div>
                  <div class="sum-sub">{{ carteraUnificada.filter(a=>a.estado==='Esta Semana').length }} alumnos</div>
                </div>

                <!-- Próximos -->
                <div class="sum-cell">
                  <div class="flex items-center gap-1 mb-1">
                    <span class="w-2 h-2 rounded-full bg-emerald-400 inline-block"></span>
                    <div class="sum-label text-emerald-600">Próximos</div>
                  </div>
                  <div class="sum-value text-emerald-600">${{ Math.round(totalProximoMostrado).toLocaleString('es-MX') }}</div>
                  <div class="sum-sub">{{ carteraUnificada.filter(a=>a.estado==='Próximo').length }} alumnos</div>
                </div>

                <!-- Promedio -->
                <div class="sum-cell">
                  <div class="sum-label">Saldo Promedio</div>
                  <div class="sum-value text-slate-600">${{ Math.round(promedioSaldo).toLocaleString('es-MX') }}</div>
                  <div class="sum-sub">por alumno</div>
                </div>

              </div>

              <!-- Fila de selección (solo visible si hay seleccionados) -->
              <div v-if="algunoSeleccionado" class="px-4 py-3 border-t border-indigo-100 bg-indigo-50 flex items-center justify-between flex-wrap gap-2">
                <div class="flex items-center gap-2 text-xs">
                  <v-icon size="14" color="indigo">mdi-checkbox-marked</v-icon>
                  <span class="font-semibold text-indigo-700">Selección:</span>
                  <span class="text-indigo-600">{{ seleccionados.length }} alumnos</span>
                </div>
                <div class="flex items-center gap-4 text-xs">
                  <span class="text-indigo-500">Suma seleccionada:</span>
                  <span class="font-black text-indigo-800 text-base">${{ Math.round(totalSeleccionadosMostrado).toLocaleString('es-MX') }} MXN</span>
                  <span class="text-indigo-400">·</span>
                  <span class="text-indigo-500">{{ ((totalSeleccionadosMostrado / totalMostrado) * 100).toFixed(1) }}% del total</span>
                </div>
              </div>
            </div>

          </div>
        </div>

        <!-- ══ MODAL WHATSAPP MASIVO ════════════════════════════════════════ -->
        <v-dialog v-model="modalWAMasivo" max-width="680" persistent>
          <v-card rounded="xl" class="overflow-hidden">
            <div class="modal-hdr" style="background: linear-gradient(135deg,#15803d,#16a34a)">
              <span class="modal-hdr__title">
                <v-icon class="mr-1" size="18">mdi-whatsapp</v-icon>
                WhatsApp Masivo
              </span>
              <v-btn icon="mdi-close" variant="text" color="white" size="small" @click="modalWAMasivo=false" :disabled="enviandoMasivo" />
            </div>
            <v-card-text class="pa-5">
              <!-- Resumen -->
              <div class="grid grid-cols-2 gap-3 mb-5">
                <div class="wa-stat wa-stat--green">
                  <v-icon color="green-darken-2" size="20">mdi-check-circle</v-icon>
                  <div>
                    <div class="wa-stat__val">{{ conCelular.length }}</div>
                    <div class="wa-stat__lbl">Con celular (serán contactados)</div>
                  </div>
                </div>
                <div class="wa-stat wa-stat--grey">
                  <v-icon color="grey" size="20">mdi-alert-circle-outline</v-icon>
                  <div>
                    <div class="wa-stat__val">{{ sinCelular.length }}</div>
                    <div class="wa-stat__lbl">Sin celular (se omitirán)</div>
                  </div>
                </div>
              </div>

              <!-- Lista de contactos -->
              <p class="text-xs font-bold text-slate-600 uppercase tracking-wide mb-2">Alumnos a contactar:</p>
              <div class="wa-lista">
                <div v-for="item in itemsSeleccionados" :key="item.id" class="wa-item"
                     :class="item.celular ? 'wa-item--ok' : 'wa-item--sin'">
                  <div class="flex items-center gap-2 flex-1 min-w-0">
                    <div class="avatar-ini avatar-ini--sm"
                         :class="item.celular ? 'avatar-ini--green' : 'bg-slate-300'">
                      {{ (item.nombre_alumno||'?').split(' ').filter(w=>w).slice(0,2).map(w=>w[0]).join('').toUpperCase() }}
                    </div>
                    <div class="min-w-0">
                      <div class="text-sm font-semibold text-slate-800 truncate">{{ item.nombre_alumno }}</div>
                      <div class="text-xs text-slate-400 truncate">{{ item.diplomado }}</div>
                    </div>
                  </div>
                  <div class="text-right flex-shrink-0">
                    <div class="text-sm font-bold text-slate-700">${{ Number(item.saldo).toLocaleString('es-MX') }}</div>
                    <div v-if="item.celular" class="text-xs text-green-600 font-mono">{{ item.celular }}</div>
                    <div v-else class="text-xs text-slate-400 italic">Sin celular</div>
                  </div>
                </div>
              </div>

              <!-- Aviso de comportamiento -->
              <v-alert type="info" variant="tonal" density="compact" class="mt-4 text-xs" icon="mdi-information-outline">
                Se abrirá <strong>una pestaña de WhatsApp Web por cada contacto</strong> con el mensaje pre-llenado. Tu navegador puede bloquear ventanas emergentes — asegúrate de permitirlas para este sitio.
              </v-alert>

              <!-- Progreso durante envío -->
              <div v-if="enviandoMasivo" class="mt-4">
                <div class="flex items-center justify-between text-xs text-slate-600 mb-1">
                  <span>Abriendo chats...</span>
                  <span>{{ waMasivoProgreso }}%</span>
                </div>
                <v-progress-linear :model-value="waMasivoProgreso" color="green-darken-1" rounded height="6" />
              </div>
            </v-card-text>
            <v-card-actions class="px-5 pb-5 pt-0 gap-2 flex-wrap">
              <v-btn variant="tonal" prepend-icon="mdi-content-copy" @click="copiarNumeros" :disabled="!conCelular.length">
                Copiar números
              </v-btn>
              <v-spacer />
              <v-btn variant="text" @click="modalWAMasivo=false" :disabled="enviandoMasivo">Cancelar</v-btn>
              <v-btn color="green-darken-1" variant="flat" prepend-icon="mdi-send"
                     :loading="enviandoMasivo" :disabled="!conCelular.length"
                     @click="enviarWAMasivo">
                Enviar a {{ conCelular.length }} contacto{{ conCelular.length!==1?'s':'' }}
              </v-btn>
            </v-card-actions>
          </v-card>
        </v-dialog>

        <!-- Snackbar -->
        <v-snackbar v-model="snackbar" :color="snackColor" location="bottom right" :timeout="3800" rounded="xl">
          <v-icon class="mr-2">{{ snackColor==='success'?'mdi-check-circle':snackColor==='warning'?'mdi-alert':'mdi-alert-circle' }}</v-icon>
          {{ snackMsg }}
        </v-snackbar>

      </v-main>
    </v-app>
  </AuthenticatedLayout>
</template>

<style scoped>
/* ── KPI Cards ── */
.kpi {
  display: flex; align-items: center; gap: 12px;
  background: white; border: 1px solid #e2e8f0;
  border-radius: 12px; padding: 14px 16px;
  cursor: pointer; transition: box-shadow .15s, border-color .15s;
  box-shadow: 0 1px 3px rgba(0,0,0,.06);
}
.kpi:hover { box-shadow: 0 4px 12px rgba(0,0,0,.08); }
.kpi--active { border-color: #6366f1; box-shadow: 0 0 0 2px #c7d2fe; }
.kpi__icon { width:38px; height:38px; border-radius:10px; display:flex; align-items:center; justify-content:center; flex-shrink:0; }
.kpi__label { font-size:.7rem; font-weight:600; color:#94a3b8; text-transform:uppercase; letter-spacing:.04em; }
.kpi__value { font-size:1.35rem; font-weight:800; line-height:1.1; }
.kpi__sub   { font-size:.7rem; color:#94a3b8; font-weight:500; }

/* ── Tabs ── */
.tab-btn {
  display:flex; align-items:center; padding:5px 12px;
  border-radius:8px; font-size:.76rem; font-weight:600; color:#64748b;
  cursor:pointer; white-space:nowrap; border:none; background:transparent;
  transition: background .15s, color .15s;
}
.tab-btn:hover { background:white; color:#334155; }
.tab-btn--active { background:white; color:#1e293b; box-shadow:0 1px 4px rgba(0,0,0,.12); }
.tab-badge { margin-left:5px; background:#e2e8f0; color:#475569; border-radius:999px; font-size:.65rem; font-weight:700; padding:1px 6px; }
.tab-btn--active .tab-badge { background:#6366f1; color:white; }

/* ── Barra flotante de selección ── */
.bulk-bar {
  display: flex; align-items: center; justify-content: space-between;
  flex-wrap: wrap; gap: 10px;
  background: linear-gradient(135deg, #1e293b, #0f172a);
  border-radius: 12px; padding: 12px 18px;
  box-shadow: 0 4px 20px rgba(0,0,0,.25);
}
.bulk-bar__count { color: white; font-size: .83rem; font-weight: 700; display: flex; align-items: center; }
.bulk-bar-enter-active, .bulk-bar-leave-active { transition: all .25s ease; }
.bulk-bar-enter-from, .bulk-bar-leave-to { opacity: 0; transform: translateY(-8px); }

/* ── Toolbar tabla ── */
.table-toolbar { display:flex; align-items:center; justify-content:space-between; background:#1e293b; padding:10px 16px; }
.toolbar-badge { background:rgba(255,255,255,.15); color:white; font-size:.65rem; font-weight:700; padding:2px 8px; border-radius:999px; }

/* ── Tabla ERP ── */
.erp-table { width:100%; border-collapse:collapse; font-size:.8rem; }
.erp-table thead tr { background:#f8fafc; border-bottom:2px solid #e2e8f0; }
.erp-table th { padding:9px 12px; text-align:left; font-size:.68rem; font-weight:700; color:#64748b; text-transform:uppercase; letter-spacing:.05em; white-space:nowrap; }
.erp-table td { padding:9px 12px; border-bottom:1px solid #f1f5f9; vertical-align:middle; }
.erp-row { transition:background .1s; }
.erp-row:hover { background:#f8fafc !important; }
.erp-row:nth-child(even) { background:#fafafa; }
.erp-row--vencido { border-left:3px solid #dc2626; }
.erp-row--semana  { border-left:3px solid #f97316; }
.erp-row--proximo { border-left:3px solid #10b981; }
.erp-row--selected { background:#eef2ff !important; }
.erp-row--selected:hover { background:#e0e7ff !important; }

/* ── Checkbox ── */
.erp-checkbox {
  width: 15px; height: 15px; cursor: pointer;
  accent-color: #6366f1;
}

/* ── Avatares ── */
.avatar-ini {
  width:30px; height:30px; border-radius:8px;
  display:flex; align-items:center; justify-content:center;
  font-size:.65rem; font-weight:800; color:white; flex-shrink:0;
  background:#475569;
}
.avatar-ini--sm { width:26px; height:26px; font-size:.6rem; }
.avatar-ini--red    { background:#dc2626; }
.avatar-ini--orange { background:#f97316; }
.avatar-ini--green  { background:#10b981; }

/* ── Estado chips ── */
.estado-chip { display:inline-block; padding:2px 9px; border-radius:999px; font-size:.62rem; font-weight:700; text-transform:uppercase; letter-spacing:.04em; white-space:nowrap; }
.estado-chip--red    { background:#fef2f2; color:#dc2626; border:1px solid #fecaca; }
.estado-chip--orange { background:#fff7ed; color:#ea580c; border:1px solid #fed7aa; }
.estado-chip--green  { background:#f0fdf4; color:#16a34a; border:1px solid #bbf7d0; }

/* ── Atraso ── */
.atraso-badge { display:inline-block; background:#fef2f2; color:#dc2626; border:1px solid #fecaca; border-radius:6px; font-size:.65rem; font-weight:700; padding:1px 6px; }

/* ── Footer tabla ── */
.table-footer { display:flex; align-items:center; justify-content:space-between; padding:10px 16px; background:#f8fafc; border-top:1px solid #e2e8f0; }

/* ── Panel sumatoria ── */
.sum-cell { padding:14px 18px; }
.sum-cell--highlight { background:#f8fafc; }
.sum-label { font-size:.68rem; font-weight:700; color:#94a3b8; text-transform:uppercase; letter-spacing:.05em; margin-bottom:3px; }
.sum-value { font-size:1.1rem; font-weight:800; line-height:1.15; }
.sum-sub   { font-size:.7rem; color:#94a3b8; margin-top:2px; }

/* ── Modal header ── */
.modal-hdr { display:flex; align-items:center; justify-content:space-between; padding:14px 20px; color:white; }
.modal-hdr--red    { background: linear-gradient(135deg,#b91c1c,#ef4444); }
.modal-hdr--purple { background: linear-gradient(135deg,#5b21b6,#7c3aed); }
.modal-hdr__title { font-size:1rem; font-weight:700; display:flex; align-items:center; }

/* ── Alumno pill ── */
.alumno-pill { display:flex; align-items:center; gap:6px; background:#f8fafc; border:1px solid #e2e8f0; border-radius:10px; padding:8px 12px; font-size:.83rem; }

/* ── Abonos lista ── */
.abonos-lista { display:flex; flex-direction:column; gap:6px; max-height:200px; overflow-y:auto; }
.abono-row { display:flex; align-items:center; gap:10px; padding:8px 12px; border-radius:10px; border:1.5px solid #e2e8f0; cursor:pointer; background:white; transition:border-color .15s; }
.abono-row:hover { border-color:#fca5a5; }
.abono-row--sel { border-color:#ef4444; background:#fef2f2; }

/* ── Plan cuotas ── */
.cuotas-lista { display:flex; flex-direction:column; gap:6px; max-height:280px; overflow-y:auto; padding-right:2px; }
.cuota-row { display:flex; align-items:center; gap:6px; }
.cuota-n { font-size:.7rem; font-weight:700; color:#7c3aed; min-width:22px; }
.plan-info-grid { display:grid; grid-template-columns:1fr 1fr; gap:8px; }
.plan-info-item { background:#f8fafc; border:1px solid #e2e8f0; border-radius:8px; padding:8px 12px; }
.pi-label { display:block; font-size:.68rem; font-weight:600; color:#94a3b8; text-transform:uppercase; letter-spacing:.04em; margin-bottom:2px; }
.pi-val { font-size:.95rem; }

/* ── Modal WA Masivo ── */
.wa-stat { display:flex; align-items:center; gap:10px; padding:12px 14px; border-radius:10px; }
.wa-stat--green { background:#f0fdf4; border:1px solid #bbf7d0; }
.wa-stat--grey  { background:#f8fafc; border:1px solid #e2e8f0; }
.wa-stat__val { font-size:1.4rem; font-weight:800; line-height:1; }
.wa-stat__lbl { font-size:.7rem; color:#64748b; margin-top:2px; }

.wa-lista { display:flex; flex-direction:column; gap:4px; max-height:240px; overflow-y:auto; border:1px solid #e2e8f0; border-radius:10px; overflow:hidden; }
.wa-item { display:flex; align-items:center; justify-content:space-between; gap:10px; padding:9px 12px; border-bottom:1px solid #f1f5f9; }
.wa-item:last-child { border-bottom:none; }
.wa-item--ok  { background:white; }
.wa-item--sin { background:#f8fafc; opacity:.6; }

/* ── Scrollbars ── */
.abonos-lista::-webkit-scrollbar, .cuotas-lista::-webkit-scrollbar, .wa-lista::-webkit-scrollbar { width:4px; }
.abonos-lista::-webkit-scrollbar-track, .cuotas-lista::-webkit-scrollbar-track, .wa-lista::-webkit-scrollbar-track { background:transparent; }
.abonos-lista::-webkit-scrollbar-thumb, .cuotas-lista::-webkit-scrollbar-thumb, .wa-lista::-webkit-scrollbar-thumb { background:#cbd5e1; border-radius:4px; }
</style>
