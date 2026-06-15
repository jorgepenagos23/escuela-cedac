<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head } from '@inertiajs/vue3';
import { ref, computed, onMounted, onUnmounted, nextTick } from 'vue';
import axios from 'axios';

// ── Estado ────────────────────────────────────────────────────────────────────
const cargando   = ref(true);
const resumen    = ref(null);
const diarios    = ref([]);
const semanaDiaria = ref([]);
const porDiplomado = ref([]);
const detalleHoy   = ref([]);
const insHoy       = ref([]);
const mesNombre    = ref('');
const semanaLabel  = ref('');
const generadoEn   = ref('');

// ── Filtros tabla diplomado ───────────────────────────────────────────────────
const buscarDiplomado = ref('');
const diplomadosFiltrados = computed(() => {
    const q = buscarDiplomado.value.toLowerCase();
    return porDiplomado.value.filter(d => d.diplomado.toLowerCase().includes(q));
});

const maxTotalDiplomado = computed(() =>
    Math.max(...porDiplomado.value.map(d => d.total), 1)
);

// ── Carga ─────────────────────────────────────────────────────────────────────
const cargar = async () => {
    cargando.value = true;
    try {
        const { data } = await axios.get('/api/v1/financiero/dashboard');
        resumen.value     = data.resumen;
        diarios.value     = data.ingresos_diarios;
        semanaDiaria.value = data.semana_diaria;
        porDiplomado.value = data.por_diplomado;
        detalleHoy.value  = data.detalle_hoy;
        insHoy.value      = data.inscripciones_hoy;
        mesNombre.value   = data.mes_nombre;
        semanaLabel.value = data.semana_label;
        generadoEn.value  = data.generado_en;
        await nextTick();
        renderChart();
    } catch (e) { console.error(e); }
    finally { cargando.value = false; }
};

// ── ApexCharts: tendencia 30 días ─────────────────────────────────────────────
let chartInstance = null;

const renderChart = () => {
    if (chartInstance) { chartInstance.destroy(); chartInstance = null; }
    const el = document.getElementById('chart-tendencia');
    if (!el || !diarios.value.length) return;

    import('apexcharts').then(({ default: ApexCharts }) => {
        const fechas  = diarios.value.map(d => {
            const [, m, day] = d.fecha.split('-');
            return `${day}/${m}`;
        });
        const colData = diarios.value.map(d => d.colegiaturas);
        const insData = diarios.value.map(d => d.inscripciones);
        const totData = diarios.value.map(d => d.total);

        const options = {
            series: [
                { name: 'Colegiaturas', type: 'bar',  data: colData },
                { name: 'Inscripciones', type: 'bar', data: insData },
                { name: 'Total',         type: 'line', data: totData },
            ],
            chart: {
                height: 280,
                type: 'line',
                stacked: false,
                toolbar: { show: false },
                fontFamily: 'inherit',
                animations: { enabled: true, speed: 600 },
            },
            plotOptions: {
                bar: { columnWidth: '60%', borderRadius: 3 },
            },
            colors: ['#6366f1', '#10b981', '#f59e0b'],
            dataLabels: { enabled: false },
            stroke: {
                width: [0, 0, 2.5],
                curve: 'smooth',
            },
            xaxis: {
                categories: fechas,
                labels: { style: { fontSize: '10px', colors: '#94a3b8' }, rotate: -45 },
                axisBorder: { show: false },
                axisTicks:  { show: false },
            },
            yaxis: {
                labels: {
                    formatter: (v) => v >= 1000 ? '$' + (v / 1000).toFixed(0) + 'k' : '$' + v,
                    style: { fontSize: '10px', colors: '#94a3b8' },
                },
            },
            tooltip: {
                shared: true,
                intersect: false,
                y: {
                    formatter: (v) => '$' + Number(v).toLocaleString('es-MX'),
                },
            },
            legend: {
                position: 'top',
                fontSize: '12px',
                fontWeight: 600,
                labels: { colors: '#475569' },
            },
            grid: {
                borderColor: '#f1f5f9',
                strokeDashArray: 4,
                xaxis: { lines: { show: false } },
            },
        };

        chartInstance = new ApexCharts(el, options);
        chartInstance.render();
    });
};

onUnmounted(() => { if (chartInstance) chartInstance.destroy(); });

// ── Formato moneda ────────────────────────────────────────────────────────────
const $ = (v) => '$' + Math.round(Number(v || 0)).toLocaleString('es-MX');
const pct = (part, total) => total > 0 ? ((part / total) * 100).toFixed(1) + '%' : '0%';

const formatFechaCorta = (f) => {
    if (!f) return '—';
    const [, m, d] = (f.split('T')[0]).split('-');
    const meses = ['ene','feb','mar','abr','may','jun','jul','ago','sep','oct','nov','dic'];
    return `${d} ${meses[parseInt(m)-1]}`;
};

// ── Exportar Excel (completo) ─────────────────────────────────────────────────
const exportarExcel = async () => {
    const XLSX = await import('xlsx');
    const wb = XLSX.utils.book_new();

    // Hoja 1: Resumen KPIs
    const resSheet = [
        ['REPORTE FINANCIERO – CEDAC'],
        ['Generado el:', new Date(generadoEn.value).toLocaleString('es-MX')],
        ['Período del mes:', mesNombre.value],
        [],
        ['RESUMEN'],
        ['Concepto', 'Colegiaturas', 'Inscripciones', 'Total'],
        ['HOY',   resumen.value.hoy_colegiaturas,   resumen.value.hoy_inscripciones,   resumen.value.hoy_total],
        ['SEMANA', resumen.value.semana_colegiaturas, resumen.value.semana_inscripciones, resumen.value.semana_total],
        ['MES',   resumen.value.mes_colegiaturas,   resumen.value.mes_inscripciones,   resumen.value.mes_total],
        ['PROYECCIÓN CIERRE MES', '', '', resumen.value.proyeccion_cierre],
    ];
    XLSX.utils.book_append_sheet(wb, XLSX.utils.aoa_to_sheet(resSheet), 'Resumen');

    // Hoja 2: Tendencia 30 días
    const diasData = [['Fecha', 'Colegiaturas', 'Inscripciones', 'Total']];
    diarios.value.forEach(d => diasData.push([d.fecha, d.colegiaturas, d.inscripciones, d.total]));
    XLSX.utils.book_append_sheet(wb, XLSX.utils.aoa_to_sheet(diasData), 'Tendencia 30 días');

    // Hoja 3: Por Diplomado
    const dipData = [['Diplomado', 'Colegiaturas', 'Inscripciones', 'Total', '# Pagos', '# Inscritos']];
    porDiplomado.value.forEach(d => dipData.push([d.diplomado, d.colegiaturas, d.inscripciones, d.total, d.num_pagos, d.num_inscritos]));
    XLSX.utils.book_append_sheet(wb, XLSX.utils.aoa_to_sheet(dipData), 'Por Diplomado');

    // Hoja 4: Detalle Hoy – Colegiaturas
    if (detalleHoy.value.length) {
        const colHoyData = [['ID', 'Alumno', 'Diplomado', 'Monto', 'Banco', 'Cuenta', 'Cajero', 'Registrado']];
        detalleHoy.value.forEach(p => colHoyData.push([p.id, p.nombre_alumno, p.diplomado, p.monto, p.banco, p.titular, p.cajero, p.registrado]));
        XLSX.utils.book_append_sheet(wb, XLSX.utils.aoa_to_sheet(colHoyData), 'Colegiaturas Hoy');
    }

    // Hoja 5: Inscripciones de Hoy
    if (insHoy.value.length) {
        const insHoyData = [['ID', 'Alumno', 'Diplomado', 'Monto Inscripción', 'Asesor', 'Fecha']];
        insHoy.value.forEach(i => insHoyData.push([i.id, i.nombre_alumno, i.diplomado, i.monto, i.asesor, i.fecha_inscripcion]));
        XLSX.utils.book_append_sheet(wb, XLSX.utils.aoa_to_sheet(insHoyData), 'Inscripciones Hoy');
    }

    XLSX.writeFile(wb, `Reporte_Financiero_CEDAC_${new Date().toISOString().split('T')[0]}.xlsx`);
};

// ── Exportar PDF ──────────────────────────────────────────────────────────────
const exportarPDF = async () => {
    const [{ default: jsPDF }, ] = await Promise.all([import('jspdf'), import('jspdf-autotable')]);
    const doc = new jsPDF({ orientation: 'landscape' });

    doc.setFontSize(16); doc.text('Reporte Financiero – CEDAC', 14, 15);
    doc.setFontSize(9);  doc.text(`Período: ${mesNombre.value}   |   Generado: ${new Date(generadoEn.value).toLocaleString('es-MX')}`, 14, 22);

    // Tabla resumen
    doc.autoTable({
        startY: 28,
        head: [['Período', 'Colegiaturas', 'Inscripciones', 'Total']],
        body: [
            ['HOY', $(resumen.value.hoy_colegiaturas), $(resumen.value.hoy_inscripciones), $(resumen.value.hoy_total)],
            ['SEMANA', $(resumen.value.semana_colegiaturas), $(resumen.value.semana_inscripciones), $(resumen.value.semana_total)],
            ['MES', $(resumen.value.mes_colegiaturas), $(resumen.value.mes_inscripciones), $(resumen.value.mes_total)],
            ['PROYECCIÓN CIERRE', '', '', $(resumen.value.proyeccion_cierre)],
        ],
        theme: 'grid',
        headStyles: { fillColor: [30, 41, 59] },
        styles: { fontSize: 9 },
    });

    // Tabla por diplomado
    doc.autoTable({
        startY: doc.lastAutoTable.finalY + 10,
        head: [['Diplomado', 'Colegiaturas', 'Inscripciones', 'Total', '# Pagos', '# Inscritos']],
        body: porDiplomado.value.map(d => [d.diplomado, $(d.colegiaturas), $(d.inscripciones), $(d.total), d.num_pagos, d.num_inscritos]),
        theme: 'striped',
        headStyles: { fillColor: [99, 102, 241] },
        styles: { fontSize: 8 },
    });

    // Tabla detalle hoy (si hay)
    if (detalleHoy.value.length) {
        doc.autoTable({
            startY: doc.lastAutoTable.finalY + 10,
            head: [['#', 'Alumno', 'Diplomado', 'Monto', 'Banco', 'Cajero']],
            body: detalleHoy.value.map((p, i) => [i+1, p.nombre_alumno, p.diplomado, $(p.monto), p.banco, p.cajero]),
            theme: 'grid',
            headStyles: { fillColor: [16, 185, 129] },
            styles: { fontSize: 7.5 },
        });
    }

    doc.save(`Reporte_Financiero_CEDAC_${new Date().toISOString().split('T')[0]}.pdf`);
};

onMounted(cargar);
</script>

<template>
  <AuthenticatedLayout>
    <Head title="Reporte Financiero" />
    <v-app class="bg-slate-50">
      <v-main>

        <div class="min-h-screen py-6">
          <div class="max-w-screen-xl mx-auto px-4 sm:px-6 lg:px-8">

            <!-- ══ ENCABEZADO ══════════════════════════════════════════════════ -->
            <div class="flex items-center justify-between mb-6 flex-wrap gap-3">
              <div class="flex items-center gap-3">
                <div class="w-11 h-11 rounded-xl bg-slate-900 flex items-center justify-center shadow-lg">
                  <v-icon color="white" size="22">mdi-finance</v-icon>
                </div>
                <div>
                  <h1 class="text-xl font-bold text-slate-900 leading-tight">Reporte Financiero</h1>
                  <p class="text-xs text-slate-500 mt-0.5">
                    <v-icon size="12" class="mr-0.5">mdi-calendar</v-icon>
                    {{ mesNombre }} &nbsp;·&nbsp;
                    <v-icon size="12" class="mr-0.5">mdi-clock-outline</v-icon>
                    Actualizado: {{ generadoEn ? new Date(generadoEn).toLocaleTimeString('es-MX', {hour:'2-digit',minute:'2-digit'}) : '—' }}
                  </p>
                </div>
              </div>

              <div class="flex items-center gap-2 flex-wrap">
                <v-btn size="small" variant="tonal" prepend-icon="mdi-refresh"
                       :loading="cargando" @click="cargar">
                  Actualizar
                </v-btn>
                <v-btn size="small" variant="flat" color="green-darken-2"
                       prepend-icon="mdi-microsoft-excel" @click="exportarExcel"
                       :disabled="cargando">
                  Descargar Excel
                </v-btn>
                <v-btn size="small" variant="flat" color="red-darken-2"
                       prepend-icon="mdi-file-pdf-box" @click="exportarPDF"
                       :disabled="cargando">
                  Descargar PDF
                </v-btn>
              </div>
            </div>

            <!-- ── Skeleton de carga ── -->
            <div v-if="cargando" class="space-y-4">
              <div class="grid grid-cols-2 lg:grid-cols-4 gap-3">
                <v-skeleton-loader v-for="i in 4" :key="i" type="card" class="rounded-xl" />
              </div>
              <v-skeleton-loader type="card" height="300" class="rounded-xl" />
            </div>

            <template v-else-if="resumen">

              <!-- ══ KPI CARDS ══════════════════════════════════════════════════ -->
              <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 mb-6">

                <!-- Hoy -->
                <div class="kpi-card kpi-card--indigo">
                  <div class="kpi-card__header">
                    <span class="kpi-card__label">Ingreso de Hoy</span>
                    <div class="kpi-card__icon bg-indigo-100">
                      <v-icon color="indigo-darken-2" size="16">mdi-calendar-today</v-icon>
                    </div>
                  </div>
                  <div class="kpi-card__value">{{ $(resumen.hoy_total) }}</div>
                  <div class="kpi-card__breakdown">
                    <span class="kpi-tag kpi-tag--purple">Col: {{ $(resumen.hoy_colegiaturas) }}</span>
                    <span class="kpi-tag kpi-tag--teal">Ins: {{ $(resumen.hoy_inscripciones) }}</span>
                  </div>
                  <div class="kpi-card__tx">
                    {{ detalleHoy.length + insHoy.length }} transaccion{{ detalleHoy.length + insHoy.length !== 1 ? 'es' : '' }}
                  </div>
                </div>

                <!-- Semana -->
                <div class="kpi-card kpi-card--blue">
                  <div class="kpi-card__header">
                    <span class="kpi-card__label">Esta Semana <span class="kpi-card__sub">{{ semanaLabel }}</span></span>
                    <div class="kpi-card__icon bg-blue-100">
                      <v-icon color="blue-darken-2" size="16">mdi-calendar-week</v-icon>
                    </div>
                  </div>
                  <div class="kpi-card__value">{{ $(resumen.semana_total) }}</div>
                  <div class="kpi-card__breakdown">
                    <span class="kpi-tag kpi-tag--purple">Col: {{ $(resumen.semana_colegiaturas) }}</span>
                    <span class="kpi-tag kpi-tag--teal">Ins: {{ $(resumen.semana_inscripciones) }}</span>
                  </div>
                </div>

                <!-- Mes -->
                <div class="kpi-card kpi-card--emerald">
                  <div class="kpi-card__header">
                    <span class="kpi-card__label">Mes Actual</span>
                    <div class="kpi-card__icon bg-emerald-100">
                      <v-icon color="green-darken-2" size="16">mdi-calendar-month</v-icon>
                    </div>
                  </div>
                  <div class="kpi-card__value">{{ $(resumen.mes_total) }}</div>
                  <div class="kpi-card__breakdown">
                    <span class="kpi-tag kpi-tag--purple">Col: {{ $(resumen.mes_colegiaturas) }}</span>
                    <span class="kpi-tag kpi-tag--teal">Ins: {{ $(resumen.mes_inscripciones) }}</span>
                  </div>
                  <!-- Barra de avance del mes -->
                  <div class="kpi-progress-wrap">
                    <div class="kpi-progress-track">
                      <div class="kpi-progress-fill bg-emerald-400"
                           :style="{width: Math.round((resumen.dia_actual / resumen.dias_en_mes)*100) + '%'}"></div>
                    </div>
                    <span class="kpi-progress-label">Día {{ resumen.dia_actual }}/{{ resumen.dias_en_mes }}</span>
                  </div>
                </div>

                <!-- Proyección -->
                <div class="kpi-card kpi-card--amber">
                  <div class="kpi-card__header">
                    <span class="kpi-card__label">Proyección Cierre</span>
                    <div class="kpi-card__icon bg-amber-100">
                      <v-icon color="orange-darken-2" size="16">mdi-trending-up</v-icon>
                    </div>
                  </div>
                  <div class="kpi-card__value text-amber-700">{{ $(resumen.proyeccion_cierre) }}</div>
                  <div class="text-xs text-amber-600 mt-1">
                    + {{ $(resumen.proyectado_restante) }} esperado en cartera
                  </div>
                  <div class="kpi-progress-wrap">
                    <div class="kpi-progress-track">
                      <div class="kpi-progress-fill bg-amber-400"
                           :style="{width: Math.min(100, Math.round((resumen.mes_total / resumen.proyeccion_cierre)*100)) + '%'}"></div>
                    </div>
                    <span class="kpi-progress-label">{{ pct(resumen.mes_total, resumen.proyeccion_cierre) }} cobrado</span>
                  </div>
                </div>

              </div>

              <!-- ══ FILA: Gráfica 30 días + Semana actual ══════════════════════ -->
              <div class="grid grid-cols-1 lg:grid-cols-3 gap-4 mb-6">

                <!-- Gráfica de tendencia (2/3) -->
                <div class="lg:col-span-2 bg-white border border-slate-200 rounded-xl shadow-sm overflow-hidden">
                  <div class="section-header">
                    <div class="section-header__title">
                      <v-icon size="15" class="mr-1.5">mdi-chart-bar</v-icon>
                      Tendencia de Ingresos · Últimos 30 días
                    </div>
                    <div class="flex items-center gap-2">
                      <span class="legend-dot bg-indigo-400"></span><span class="legend-txt">Colegiaturas</span>
                      <span class="legend-dot bg-emerald-400"></span><span class="legend-txt">Inscripciones</span>
                      <span class="legend-dot bg-amber-400 rounded-none" style="height:2px;width:16px"></span><span class="legend-txt">Total</span>
                    </div>
                  </div>
                  <div class="px-4 pb-3">
                    <div id="chart-tendencia"></div>
                  </div>
                </div>

                <!-- Semana actual (1/3) -->
                <div class="bg-white border border-slate-200 rounded-xl shadow-sm overflow-hidden">
                  <div class="section-header">
                    <div class="section-header__title">
                      <v-icon size="15" class="mr-1.5">mdi-calendar-week-begin</v-icon>
                      Semana Actual
                    </div>
                    <span class="text-xs text-slate-400">{{ semanaLabel }}</span>
                  </div>
                  <div class="px-4 pb-4 space-y-2">
                    <div v-for="dia in semanaDiaria" :key="dia.dia"
                         class="semana-row" :class="{'semana-row--hoy': dia.hoy}">
                      <div class="semana-row__dia" :class="{'text-indigo-700 font-black': dia.hoy}">
                        {{ dia.dia }}
                        <span v-if="dia.hoy" class="hoy-badge">hoy</span>
                      </div>
                      <div class="semana-row__bar-wrap">
                        <div class="semana-row__bar">
                          <div class="semana-row__fill"
                               :class="dia.hoy ? 'bg-indigo-500' : 'bg-slate-200'"
                               :style="{width: semanaDiaria.some(d=>d.total>0) ? Math.round((dia.total / Math.max(...semanaDiaria.map(d=>d.total), 1))*100)+'%' : '0%'}">
                          </div>
                        </div>
                      </div>
                      <div class="semana-row__monto" :class="{'text-indigo-700 font-black': dia.hoy}">
                        {{ dia.total > 0 ? $(dia.total) : '—' }}
                      </div>
                    </div>
                    <div class="semana-total">
                      <span class="text-xs text-slate-500">Total semana:</span>
                      <span class="font-black text-slate-800">{{ $(resumen.semana_total) }}</span>
                    </div>
                  </div>
                </div>

              </div>

              <!-- ══ DESGLOSE POR DIPLOMADO ════════════════════════════════════ -->
              <div class="bg-white border border-slate-200 rounded-xl shadow-sm overflow-hidden mb-6">
                <div class="section-header">
                  <div class="section-header__title">
                    <v-icon size="15" class="mr-1.5">mdi-certificate-outline</v-icon>
                    Ingresos por Diplomado · {{ mesNombre }}
                  </div>
                  <v-text-field
                    v-model="buscarDiplomado"
                    placeholder="Buscar..."
                    variant="outlined"
                    density="compact"
                    hide-details
                    prepend-inner-icon="mdi-magnify"
                    style="max-width:200px"
                    bg-color="white"
                  />
                </div>
                <div class="overflow-x-auto">
                  <table class="fin-table">
                    <thead>
                      <tr>
                        <th>#</th>
                        <th>Diplomado</th>
                        <th class="text-right">Colegiaturas</th>
                        <th class="text-right">Inscripciones</th>
                        <th class="text-right">Total</th>
                        <th class="w-48">Participación</th>
                        <th class="text-center"># Pagos</th>
                        <th class="text-center"># Inscritos</th>
                      </tr>
                    </thead>
                    <tbody>
                      <tr v-if="!diplomadosFiltrados.length">
                        <td colspan="8" class="text-center py-10 text-slate-400 text-sm">Sin datos</td>
                      </tr>
                      <tr v-for="(d, i) in diplomadosFiltrados" :key="d.diplomado_id" class="fin-row">
                        <td class="text-slate-400 text-xs text-center">{{ i + 1 }}</td>
                        <td class="font-semibold text-slate-800 max-w-[220px] truncate" :title="d.diplomado">{{ d.diplomado }}</td>
                        <td class="text-right text-indigo-700 font-semibold">{{ $(d.colegiaturas) }}</td>
                        <td class="text-right text-emerald-700 font-semibold">{{ $(d.inscripciones) }}</td>
                        <td class="text-right font-black text-slate-800">{{ $(d.total) }}</td>
                        <!-- Barra de participación -->
                        <td class="w-48">
                          <div class="flex items-center gap-2">
                            <div class="bar-track flex-1">
                              <div class="bar-fill bg-indigo-500"
                                   :style="{width: Math.round((d.total/maxTotalDiplomado)*100)+'%'}"></div>
                            </div>
                            <span class="text-xs text-slate-500 w-10 text-right">{{ pct(d.total, resumen.mes_total) }}</span>
                          </div>
                        </td>
                        <td class="text-center text-sm text-slate-600">{{ d.num_pagos }}</td>
                        <td class="text-center text-sm text-slate-600">{{ d.num_inscritos }}</td>
                      </tr>
                    </tbody>
                    <tfoot v-if="diplomadosFiltrados.length">
                      <tr class="fin-total-row">
                        <td colspan="2" class="font-bold text-slate-700">TOTAL</td>
                        <td class="text-right font-black text-indigo-700">{{ $(resumen.mes_colegiaturas) }}</td>
                        <td class="text-right font-black text-emerald-700">{{ $(resumen.mes_inscripciones) }}</td>
                        <td class="text-right font-black text-slate-800">{{ $(resumen.mes_total) }}</td>
                        <td></td>
                        <td class="text-center font-bold">{{ porDiplomado.reduce((s,d)=>s+d.num_pagos,0) }}</td>
                        <td class="text-center font-bold">{{ porDiplomado.reduce((s,d)=>s+d.num_inscritos,0) }}</td>
                      </tr>
                    </tfoot>
                  </table>
                </div>
              </div>

              <!-- ══ MOVIMIENTOS DEL DÍA ════════════════════════════════════════ -->
              <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">

                <!-- Colegiaturas de hoy -->
                <div class="bg-white border border-slate-200 rounded-xl shadow-sm overflow-hidden">
                  <div class="section-header">
                    <div class="section-header__title">
                      <v-icon size="15" class="mr-1.5" color="indigo">mdi-cash-register</v-icon>
                      Colegiaturas de Hoy
                    </div>
                    <span class="kpi-tag kpi-tag--purple">{{ $(resumen.hoy_colegiaturas) }}</span>
                  </div>
                  <div class="max-h-72 overflow-y-auto">
                    <div v-if="!detalleHoy.length" class="text-center py-10 text-slate-400 text-sm">
                      <v-icon size="32" class="mb-2 opacity-30">mdi-cash-off</v-icon>
                      <p>Sin colegiaturas registradas hoy.</p>
                    </div>
                    <table v-else class="mov-table">
                      <thead><tr>
                        <th>Alumno</th>
                        <th>Diplomado</th>
                        <th class="text-right">Monto</th>
                        <th>Banco</th>
                        <th>Cajero</th>
                      </tr></thead>
                      <tbody>
                        <tr v-for="p in detalleHoy" :key="p.id" class="mov-row">
                          <td class="font-semibold text-slate-800 text-xs">{{ p.nombre_alumno }}</td>
                          <td class="text-xs text-indigo-600 max-w-[130px] truncate">{{ p.diplomado }}</td>
                          <td class="text-right font-black text-emerald-700 text-sm">${{ Number(p.monto).toLocaleString('es-MX') }}</td>
                          <td class="text-xs text-slate-500">{{ p.banco }}</td>
                          <td class="text-xs text-slate-400">{{ p.cajero }}</td>
                        </tr>
                      </tbody>
                    </table>
                  </div>
                </div>

                <!-- Inscripciones de hoy -->
                <div class="bg-white border border-slate-200 rounded-xl shadow-sm overflow-hidden">
                  <div class="section-header">
                    <div class="section-header__title">
                      <v-icon size="15" class="mr-1.5" color="emerald">mdi-account-plus-outline</v-icon>
                      Inscripciones de Hoy
                    </div>
                    <span class="kpi-tag kpi-tag--teal">{{ $(resumen.hoy_inscripciones) }}</span>
                  </div>
                  <div class="max-h-72 overflow-y-auto">
                    <div v-if="!insHoy.length" class="text-center py-10 text-slate-400 text-sm">
                      <v-icon size="32" class="mb-2 opacity-30">mdi-account-off-outline</v-icon>
                      <p>Sin inscripciones registradas hoy.</p>
                    </div>
                    <table v-else class="mov-table">
                      <thead><tr>
                        <th>Alumno</th>
                        <th>Diplomado</th>
                        <th class="text-right">Inscripción</th>
                        <th>Asesor</th>
                      </tr></thead>
                      <tbody>
                        <tr v-for="ins in insHoy" :key="ins.id" class="mov-row">
                          <td class="font-semibold text-slate-800 text-xs">{{ ins.nombre_alumno }}</td>
                          <td class="text-xs text-indigo-600 max-w-[140px] truncate">{{ ins.diplomado }}</td>
                          <td class="text-right font-black text-emerald-700 text-sm">${{ Number(ins.monto).toLocaleString('es-MX') }}</td>
                          <td class="text-xs text-slate-400">{{ ins.asesor }}</td>
                        </tr>
                      </tbody>
                    </table>
                  </div>
                </div>

              </div>

            </template>

          </div>
        </div>

      </v-main>
    </v-app>
  </AuthenticatedLayout>
</template>

<style scoped>
/* ── KPI Cards ── */
.kpi-card {
  background: white;
  border: 1px solid #e2e8f0;
  border-radius: 14px;
  padding: 16px 18px;
  box-shadow: 0 1px 4px rgba(0,0,0,.05);
  transition: box-shadow .2s;
}
.kpi-card:hover { box-shadow: 0 4px 16px rgba(0,0,0,.08); }
.kpi-card--indigo { border-top: 3px solid #6366f1; }
.kpi-card--blue   { border-top: 3px solid #3b82f6; }
.kpi-card--emerald{ border-top: 3px solid #10b981; }
.kpi-card--amber  { border-top: 3px solid #f59e0b; }

.kpi-card__header {
  display: flex; align-items: flex-start; justify-content: space-between;
  margin-bottom: 6px;
}
.kpi-card__label {
  font-size: .7rem; font-weight: 700; color: #94a3b8;
  text-transform: uppercase; letter-spacing: .05em;
  display: flex; flex-direction: column; gap: 1px;
}
.kpi-card__sub { font-size: .62rem; color: #cbd5e1; font-weight: 500; text-transform: none; }
.kpi-card__icon { width:28px; height:28px; border-radius:8px; display:flex; align-items:center; justify-content:center; flex-shrink:0; }
.kpi-card__value { font-size: 1.5rem; font-weight: 900; color: #1e293b; line-height: 1.1; margin-bottom: 6px; }
.kpi-card__tx { font-size: .68rem; color: #94a3b8; margin-top: 4px; }
.kpi-card__breakdown { display: flex; gap: 6px; flex-wrap: wrap; }

.kpi-tag { display:inline-block; padding:2px 8px; border-radius:999px; font-size:.65rem; font-weight:700; white-space:nowrap; }
.kpi-tag--purple { background:#ede9fe; color:#6d28d9; }
.kpi-tag--teal   { background:#d1fae5; color:#065f46; }

.kpi-progress-wrap { margin-top: 8px; display: flex; align-items: center; gap: 6px; }
.kpi-progress-track { flex: 1; height: 5px; background: #f1f5f9; border-radius: 999px; overflow: hidden; }
.kpi-progress-fill  { height: 100%; border-radius: 999px; transition: width .4s ease; }
.kpi-progress-label { font-size: .62rem; color: #94a3b8; white-space: nowrap; }

/* ── Section header ── */
.section-header {
  display: flex; align-items: center; justify-content: space-between;
  padding: 12px 16px; border-bottom: 1px solid #f1f5f9;
  background: #f8fafc; flex-wrap: wrap; gap: 6px;
}
.section-header__title {
  font-size: .8rem; font-weight: 700; color: #334155;
  display: flex; align-items: center;
}

/* ── Leyenda ── */
.legend-dot { display:inline-block; width:10px; height:10px; border-radius:50%; }
.legend-txt { font-size:.68rem; color:#64748b; margin-right:8px; }

/* ── Semana actual ── */
.semana-row {
  display: flex; align-items: center; gap: 8px;
  padding: 5px 2px; border-radius: 8px;
  transition: background .1s;
}
.semana-row--hoy { background: #eef2ff; padding: 6px 8px; margin: 0 -8px; }
.semana-row__dia { width: 28px; font-size: .72rem; font-weight: 600; color: #64748b; flex-shrink: 0; }
.semana-row__bar-wrap { flex: 1; }
.semana-row__bar { height: 7px; background: #f1f5f9; border-radius: 999px; overflow: hidden; }
.semana-row__fill { height: 100%; border-radius: 999px; transition: width .5s ease; }
.semana-row__monto { width: 80px; text-align: right; font-size: .72rem; color: #64748b; }
.hoy-badge { display:inline-block; background:#6366f1; color:white; border-radius:4px; font-size:.55rem; padding:1px 4px; margin-left:3px; font-weight:700; }
.semana-total { display:flex; justify-content:space-between; padding-top:8px; border-top:1px solid #e2e8f0; margin-top:6px; }

/* ── Tablas financieras ── */
.fin-table { width:100%; border-collapse:collapse; font-size:.78rem; }
.fin-table thead tr { background:#f8fafc; border-bottom:2px solid #e2e8f0; }
.fin-table th { padding:9px 12px; text-align:left; font-size:.65rem; font-weight:700; color:#64748b; text-transform:uppercase; letter-spacing:.04em; white-space:nowrap; }
.fin-table td { padding:8px 12px; border-bottom:1px solid #f1f5f9; vertical-align:middle; }
.fin-row { transition:background .1s; }
.fin-row:hover { background:#f8fafc; }
.fin-row:nth-child(even) { background:#fafafa; }
.fin-total-row td { padding:10px 12px; background:#f1f5f9; border-top:2px solid #e2e8f0; font-size:.78rem; }

/* ── Barra participación ── */
.bar-track { height:7px; background:#f1f5f9; border-radius:999px; overflow:hidden; }
.bar-fill   { height:100%; border-radius:999px; }

/* ── Tablas movimientos del día ── */
.mov-table { width:100%; border-collapse:collapse; font-size:.75rem; }
.mov-table thead tr { background:#f8fafc; border-bottom:1px solid #e2e8f0; }
.mov-table th { padding:7px 12px; text-align:left; font-size:.63rem; font-weight:700; color:#94a3b8; text-transform:uppercase; letter-spacing:.04em; }
.mov-table td { padding:7px 12px; border-bottom:1px solid #f8fafc; vertical-align:middle; }
.mov-row:hover { background:#f8fafc; }

/* ── Scrollbars ── */
.max-h-72::-webkit-scrollbar { width:4px; }
.max-h-72::-webkit-scrollbar-track { background:transparent; }
.max-h-72::-webkit-scrollbar-thumb { background:#cbd5e1; border-radius:4px; }
</style>
