<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue';
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import { Head } from "@inertiajs/vue3";
import axios from 'axios';
import swal from "sweetalert";
import { useErpWindows } from '@/Composables/useErpWindows';

const erp = useErpWindows();

// ── Estado General ────────────────────────────────────────────────────────────
const cargando = ref(false);
const pagosOriginales = ref([]);
const historialPagos = ref([]);

// ── Filtros ───────────────────────────────────────────────────────────────────
const busqueda = ref('');
const filtroDiplomado = ref(null);
const filtroFechaInicio = ref('');
const filtroFechaFin = ref('');

// ── Tabla ─────────────────────────────────────────────────────────────────────
const headers = [
  { title: "ID Sist.", key: "idpago", sortable: true, align: "start", width: "100px" },
  { title: "Expediente del Alumno", key: "Nombre", sortable: true },
  { title: "Abono Pagado", key: "pago_colegiatura", sortable: true, align: "end" },
  { title: "Fecha de Operación", key: "Fecha_PrimerContacto", sortable: true, align: "center" },
  { title: "Diplomado Suscrito", key: "nombre_diplomado", sortable: true },
  { title: "Saldo Restante", key: "saldo", sortable: true, align: "end" },
  { title: "Tutor Caja", key: "Tutor", sortable: true },
  { title: "Acciones / Consulta", key: "acciones", sortable: false, align: "center", width: "140px" },
];

const itemsPerPage = ref(15);

// ── Datos Computados y Filtrado ───────────────────────────────────────────────
const listaDiplomadosUnicos = computed(() => {
  const unicos = new Set();
  pagosOriginales.value.forEach(item => {
    if (item.nombre_diplomado) unicos.add(item.nombre_diplomado);
  });
  return Array.from(unicos).sort();
});

const pagosFiltrados = computed(() => {
  let resultados = pagosOriginales.value;

  if (filtroDiplomado.value) {
    resultados = resultados.filter(p => p.nombre_diplomado === filtroDiplomado.value);
  }

  if (filtroFechaInicio.value) {
    resultados = resultados.filter(p => p.Fecha_PrimerContacto >= filtroFechaInicio.value);
  }

  if (filtroFechaFin.value) {
    resultados = resultados.filter(p => p.Fecha_PrimerContacto <= filtroFechaFin.value);
  }

  if (busqueda.value.trim() !== '') {
    const term = busqueda.value.toLowerCase();
    resultados = resultados.filter(p => 
      (p.Nombre && p.Nombre.toLowerCase().includes(term)) ||
      (p.idpago && p.idpago.toString().includes(term)) ||
      (p.Tutor && p.Tutor.toLowerCase().includes(term))
    );
  }

  return resultados;
});

// ── KPIs del Dashboard ────────────────────────────────────────────────────────
const kpiTotalRecaudado = computed(() => {
  return pagosFiltrados.value.reduce((sum, item) => sum + parseFloat(item.pago_colegiatura || 0), 0);
});

const kpiTransacciones = computed(() => pagosFiltrados.value.length);

const kpiTicketPromedio = computed(() => {
  if (kpiTransacciones.value === 0) return 0;
  return kpiTotalRecaudado.value / kpiTransacciones.value;
});

// ── Métodos de Carga ──────────────────────────────────────────────────────────
const obtenerPagosGlobales = async () => {
  cargando.value = true;
  try {
    const response = await axios.get("/api/v1/pagos_mensualidades_api2024F");
    // Mapear id a idpago si es necesario
    pagosOriginales.value = response.data.PagosconMensualidades.map(p => ({
        ...p,
        idpago: p.id // PagosController retorna 'id' en lugar de 'idpago' en index()
    }));
  } catch (err) {
    console.error(err);
    swal("Error", "No se pudieron cargar los pagos globales.", "error");
  } finally {
    cargando.value = false;
  }
};

const limpiarFiltros = () => {
  busqueda.value = "";
  filtroDiplomado.value = null;
  filtroFechaInicio.value = "";
  filtroFechaFin.value = "";
};

// ── Exportaciones ─────────────────────────────────────────────────────────────
const exportarExcel = async () => {
    if (pagosFiltrados.value.length === 0) {
        swal("Sin Datos", "No hay registros para exportar con los filtros actuales.", "warning");
        return;
    }
    
    try {
        const XLSX = await import('xlsx');
        
        const datosExcel = pagosFiltrados.value.map(p => ({
            'Folio Sistema': p.idpago,
            'Alumno': p.Nombre,
            'Programa': p.nombre_diplomado,
            'Abono Pagado': parseFloat(p.pago_colegiatura),
            'Fecha Operación': p.Fecha_PrimerContacto,
            'Saldo Restante': parseFloat(p.saldo),
            'Tutor/Gestor': p.Tutor
        }));

        // Fila de totales
        datosExcel.push({
            'Folio Sistema': '',
            'Alumno': 'TOTALES',
            'Programa': '',
            'Abono Pagado': kpiTotalRecaudado.value,
            'Fecha Operación': '',
            'Saldo Restante': '',
            'Tutor/Gestor': `${kpiTransacciones.value} ops`
        });

        const ws = XLSX.utils.json_to_sheet(datosExcel);
        
        // Ajustar anchos
        ws['!cols'] = [
            { wch: 15 }, // Folio
            { wch: 35 }, // Alumno
            { wch: 30 }, // Programa
            { wch: 15 }, // Abono
            { wch: 15 }, // Fecha
            { wch: 15 }, // Saldo
            { wch: 25 }, // Tutor
        ];

        const wb = XLSX.utils.book_new();
        XLSX.utils.book_append_sheet(wb, ws, "Colegiaturas");
        XLSX.writeFile(wb, "Reporte_Colegiaturas.xlsx");
        
    } catch (e) {
        console.error("Error exportando a Excel:", e);
        swal("Error", "Hubo un problema al generar el archivo Excel.", "error");
    }
};

const exportarPDF = async () => {
    if (pagosFiltrados.value.length === 0) {
        swal("Sin Datos", "No hay registros para exportar.", "warning");
        return;
    }

    try {
        const [{ default: jsPDF }, { default: autoTable }] = await Promise.all([
            import('jspdf'),
            import('jspdf-autotable')
        ]);
        
        const doc = new jsPDF('landscape'); // Horizontal para que quepa bien
        
        doc.setFontSize(16);
        doc.text("Reporte de Colegiaturas Aplicadas", 14, 15);
        
        doc.setFontSize(10);
        doc.text(`Generado el: ${new Date().toLocaleDateString('es-MX')}`, 14, 22);
        doc.text(`Total Recaudado: $${kpiTotalRecaudado.value.toLocaleString('es-MX', {minimumFractionDigits: 2})}`, 14, 28);
        doc.text(`Transacciones: ${kpiTransacciones.value}`, 100, 28);

        const tablaDatos = pagosFiltrados.value.map(p => [
            p.idpago,
            p.Nombre,
            p.nombre_diplomado,
            `$${parseFloat(p.pago_colegiatura).toLocaleString('es-MX', {minimumFractionDigits: 2})}`,
            p.Fecha_PrimerContacto,
            `$${parseFloat(p.saldo).toLocaleString('es-MX', {minimumFractionDigits: 2})}`,
            p.Tutor
        ]);

        autoTable(doc, {
            startY: 35,
            head: [['Folio', 'Alumno', 'Programa', 'Abono', 'Fecha', 'Saldo', 'Gestor']],
            body: tablaDatos,
            theme: 'striped',
            headStyles: { fillColor: [49, 46, 129] }, // indigo-900
            styles: { fontSize: 8 },
        });

        doc.save("Reporte_Colegiaturas.pdf");
    } catch (e) {
        console.error("Error exportando a PDF:", e);
        swal("Error", "Hubo un problema al generar el PDF.", "error");
    }
};

// ── Acciones de Fila ──────────────────────────────────────────────────────────
const descargarPDF = (idDocumento) => {
    window.open('/pagos/' + idDocumento + '/pdf', '_blank');
};

const modalExpediente = ref(false);
const alumnoSeleccionado = ref(null);
const loadingHistorial = ref(false);

const consultarHistorialDeuda = async (pagoReferencia) => {
    alumnoSeleccionado.value = pagoReferencia;
    historialPagos.value = [];
    modalExpediente.value = true;
    loadingHistorial.value = true;

    try {
        const res = await axios.get(`/api/v1/mostrar/alumno/status/${pagoReferencia.alumno_id}`);
        historialPagos.value = res.data.pagosColegiaturaAlumno2;
    } catch (err) {
        swal("Error", "No se pudo traer el histórico del estudiante desde sistema...", "error");
        console.error(err);
    } finally {
        loadingHistorial.value = false;
    }
};

const abrirCrudsGlobal = (nombreAlumno) => {
    window.location.href = `/alumnos`;
};

// ── Lifecycle ─────────────────────────────────────────────────────────────────
onMounted(() => {
    obtenerPagosGlobales();
    erp.registerTabExport('Pagos', { label: 'Exportar Excel', icon: 'mdi-microsoft-excel', fn: exportarExcel });
});

onUnmounted(() => {
    erp.unregisterTabExport('Pagos');
});
</script>

<template>
  <AuthenticatedLayout>
    <Head title="Histórico de Colegiaturas" />
    
    <div class="bg-slate-50 min-h-screen pb-10">
      <div class="max-w-[1400px] mx-auto px-4 sm:px-6 lg:px-8 pt-8">
        
        <!-- ── HEADER ────────────────────────────────────────────────────────── -->
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-6">
          <div>
            <h2 class="text-2xl font-bold text-slate-800 flex items-center">
              <div class="w-10 h-10 rounded-xl bg-indigo-900 flex items-center justify-center mr-3 shadow-sm">
                <v-icon color="white" size="20">mdi-cash-multiple</v-icon>
              </div>
              Colegiaturas Aplicadas
            </h2>
            <p class="text-sm text-slate-500 mt-1 ml-13">
              Explora movimientos contables, reimprime recibos y analiza adeudos por alumno.
            </p>
          </div>
          
          <div class="flex items-center gap-2">
            <v-btn color="green-darken-2" prepend-icon="mdi-microsoft-excel" variant="flat" @click="exportarExcel" class="text-none font-semibold shadow-sm">
              Excel
            </v-btn>
            <v-btn color="red-darken-2" prepend-icon="mdi-file-pdf-box" variant="flat" @click="exportarPDF" class="text-none font-semibold shadow-sm">
              PDF
            </v-btn>
          </div>
        </div>

        <!-- ── DASHBOARD KPIs ────────────────────────────────────────────────── -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
          <div class="bg-white rounded-xl p-5 border border-slate-200 shadow-sm flex items-center justify-between transition-transform duration-200 hover:-translate-y-1">
            <div>
              <p class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-1">Total Recaudado</p>
              <h3 class="text-2xl font-black text-emerald-600">
                ${{ kpiTotalRecaudado.toLocaleString('es-MX', { minimumFractionDigits: 2 }) }}
              </h3>
            </div>
            <div class="w-12 h-12 rounded-full bg-emerald-50 flex items-center justify-center">
              <v-icon color="emerald" size="24">mdi-currency-usd</v-icon>
            </div>
          </div>
          
          <div class="bg-white rounded-xl p-5 border border-slate-200 shadow-sm flex items-center justify-between transition-transform duration-200 hover:-translate-y-1">
            <div>
              <p class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-1">Transacciones</p>
              <h3 class="text-2xl font-black text-indigo-700">
                {{ kpiTransacciones.toLocaleString() }}
              </h3>
            </div>
            <div class="w-12 h-12 rounded-full bg-indigo-50 flex items-center justify-center">
              <v-icon color="indigo" size="24">mdi-swap-horizontal</v-icon>
            </div>
          </div>

          <div class="bg-white rounded-xl p-5 border border-slate-200 shadow-sm flex items-center justify-between transition-transform duration-200 hover:-translate-y-1">
            <div>
              <p class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-1">Ticket Promedio</p>
              <h3 class="text-2xl font-black text-amber-600">
                ${{ kpiTicketPromedio.toLocaleString('es-MX', { minimumFractionDigits: 2 }) }}
              </h3>
            </div>
            <div class="w-12 h-12 rounded-full bg-amber-50 flex items-center justify-center">
              <v-icon color="amber-darken-1" size="24">mdi-ticket-percent-outline</v-icon>
            </div>
          </div>
        </div>

        <!-- ── FILTROS ───────────────────────────────────────────────────────── -->
        <div class="bg-white rounded-xl shadow-sm border border-slate-200 mb-6 p-4">
          <div class="flex items-center gap-2 mb-3">
            <v-icon size="18" color="slate-400">mdi-filter-variant</v-icon>
            <h3 class="text-sm font-bold text-slate-700">Filtros de Búsqueda</h3>
          </div>
          <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-3">
            <div class="lg:col-span-2">
              <v-text-field
                v-model="busqueda"
                label="Buscar alumno, gestor o folio"
                variant="outlined"
                density="compact"
                hide-details
                prepend-inner-icon="mdi-magnify"
                bg-color="slate-50"
                clearable
              ></v-text-field>
            </div>
            <div class="lg:col-span-1">
              <v-select
                v-model="filtroDiplomado"
                :items="listaDiplomadosUnicos"
                label="Programa Académico"
                variant="outlined"
                density="compact"
                hide-details
                clearable
                bg-color="slate-50"
              ></v-select>
            </div>
            <div class="lg:col-span-1">
              <v-text-field
                v-model="filtroFechaInicio"
                type="date"
                label="Fecha Inicio"
                variant="outlined"
                density="compact"
                hide-details
                clearable
                bg-color="slate-50"
              ></v-text-field>
            </div>
            <div class="lg:col-span-1 flex gap-2">
              <v-text-field
                v-model="filtroFechaFin"
                type="date"
                label="Fecha Fin"
                variant="outlined"
                density="compact"
                hide-details
                clearable
                bg-color="slate-50"
                class="flex-1"
              ></v-text-field>
              <v-btn icon="mdi-refresh" variant="tonal" color="slate" density="comfortable" @click="limpiarFiltros" title="Limpiar Filtros"></v-btn>
            </div>
          </div>
        </div>

        <!-- ── TABLA PRINCIPAL ───────────────────────────────────────────────── -->
        <div class="bg-white border border-slate-200 rounded-xl shadow-sm overflow-hidden">
            <div class="flex items-center justify-between px-4 py-3 border-b border-slate-100 bg-slate-50">
                <span class="text-sm font-semibold text-slate-700">Movimientos Contables</span>
                <span class="text-xs font-bold bg-indigo-100 text-indigo-800 px-2 py-1 rounded-md">
                    {{ pagosFiltrados.length }} registros
                </span>
            </div>

            <v-data-table
                :headers="headers"
                :items="pagosFiltrados"
                :loading="cargando"
                :items-per-page="itemsPerPage"
                hover
                density="comfortable"
                class="text-sm"
            >
                <!-- Folio -->
                <template v-slot:item.idpago="{ item }">
                    <span class="font-mono text-slate-500 font-bold">#{{ item.idpago }}</span>
                </template>

                <!-- Alumno -->
                <template v-slot:item.Nombre="{ item }">
                    <div class="font-semibold text-slate-800">{{ item.Nombre }}</div>
                </template>

                <!-- Abono -->
                <template v-slot:item.pago_colegiatura="{ item }">
                    <v-chip size="small" color="emerald" variant="flat" class="font-bold">
                        +${{ Number(item.pago_colegiatura).toLocaleString('es-MX', {minimumFractionDigits: 2}) }}
                    </v-chip>
                </template>

                <!-- Fecha -->
                <template v-slot:item.Fecha_PrimerContacto="{ item }">
                    <div class="text-slate-600 font-mono text-xs font-semibold">{{ item.Fecha_PrimerContacto }}</div>
                </template>

                <!-- Diplomado -->
                <template v-slot:item.nombre_diplomado="{ item }">
                    <div class="text-indigo-700 font-medium truncate max-w-[200px]" :title="item.nombre_diplomado">
                        {{ item.nombre_diplomado }}
                    </div>
                </template>

                <!-- Saldo -->
                <template v-slot:item.saldo="{ item }">
                    <span class="font-black" :class="item.saldo > 0 ? 'text-red-600' : 'text-emerald-600'">
                        ${{ Number(item.saldo).toLocaleString('es-MX', {minimumFractionDigits: 2}) }}
                    </span>
                </template>

                <!-- Gestor -->
                <template v-slot:item.Tutor="{ item }">
                    <div class="flex items-center justify-start gap-2">
                        <v-avatar size="24" color="indigo-lighten-4">
                            <span class="text-[10px] font-bold text-indigo-900">{{ (item.Tutor || '?').substring(0,2).toUpperCase() }}</span>
                        </v-avatar>
                        <span class="text-xs text-slate-600 font-medium">{{ item.Tutor }}</span>
                    </div>
                </template>

                <!-- Acciones -->
                <template v-slot:item.acciones="{ item }">
                    <div class="flex justify-center gap-1">
                        <v-tooltip text="Reimprimir Recibo PDF" location="top">
                            <template v-slot:activator="{ props }">
                                <v-btn v-bind="props" icon="mdi-file-pdf-box" size="small" variant="tonal" color="red-darken-1" @click="descargarPDF(item.idpago)"></v-btn>
                            </template>
                        </v-tooltip>
                        
                        <v-tooltip text="Ver Expediente de Deuda" location="top">
                            <template v-slot:activator="{ props }">
                                <v-btn v-bind="props" icon="mdi-folder-account" size="small" variant="tonal" color="indigo-darken-2" @click="consultarHistorialDeuda(item)"></v-btn>
                            </template>
                        </v-tooltip>
                    </div>
                </template>

                <template #no-data>
                    <div class="p-8 text-center text-slate-400">
                        <v-icon size="48" class="mb-3 opacity-50">mdi-cash-remove</v-icon>
                        <p>No se encontraron pagos con los filtros actuales.</p>
                    </div>
                </template>
            </v-data-table>
        </div>

        <!-- ── MODAL EXPEDIENTE ──────────────────────────────────────────────── -->
        <v-dialog v-model="modalExpediente" max-width="750">
            <v-card rounded="xl" class="overflow-hidden border-0 shadow-2xl">
                <div class="bg-indigo-900 px-6 py-4 flex justify-between items-center text-white">
                    <div>
                        <h3 class="text-lg font-bold flex items-center tracking-wide">
                            <v-icon color="emerald-accent-2" class="mr-2">mdi-account-search</v-icon>
                            Expediente de Deuda
                        </h3>
                        <p class="text-indigo-200 text-xs mt-1 font-medium">{{ alumnoSeleccionado?.Nombre }}</p>
                    </div>
                    <div class="text-right">
                        <div class="text-[10px] text-indigo-200 uppercase tracking-wider font-bold mb-1">Saldo Restante</div>
                        <v-chip color="red-accent-3" size="small" variant="flat" class="font-black text-sm">
                            ${{ Number(alumnoSeleccionado?.saldo || 0).toLocaleString('es-MX', {minimumFractionDigits: 2}) }}
                        </v-chip>
                    </div>
                </div>

                <v-card-text class="bg-slate-50 p-6">
                    <div class="mb-6 p-4 bg-white border border-slate-200 rounded-xl shadow-sm">
                        <h4 class="text-slate-700 font-black mb-3 text-sm tracking-wide uppercase flex items-center border-b border-slate-100 pb-2">
                            <v-icon size="16" class="mr-1" color="slate-400">mdi-school</v-icon>
                            Información de Matrícula
                        </h4>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
                            <div><span class="text-slate-400 font-bold text-xs uppercase block">Programa Académico</span> <span class="font-semibold text-indigo-800">{{ alumnoSeleccionado?.nombre_diplomado }}</span></div>
                            <div><span class="text-slate-400 font-bold text-xs uppercase block">Saldo Actual</span> <span class="font-black text-red-600">${{ Number(alumnoSeleccionado?.saldo || 0).toLocaleString('es-MX', {minimumFractionDigits: 2}) }} MXN</span></div>
                        </div>
                        <div class="mt-4 pt-3 border-t border-slate-100 flex justify-end">
                            <v-btn color="indigo-darken-3" size="small" prepend-icon="mdi-magnify-scan" variant="tonal" @click="abrirCrudsGlobal(alumnoSeleccionado?.Nombre)">
                                Buscar Alumno en Global
                            </v-btn>
                        </div>
                    </div>

                    <h4 class="text-slate-700 font-black mb-3 text-sm tracking-wide uppercase flex items-center border-b border-slate-200 pb-2">
                        <v-icon size="16" class="mr-1" color="emerald">mdi-history</v-icon>
                        Histórico de Abonos
                    </h4>
                    
                    <div v-if="loadingHistorial" class="text-center py-10">
                        <v-progress-circular indeterminate color="indigo" size="40" width="4"></v-progress-circular>
                        <p class="text-xs text-slate-400 mt-3 font-semibold uppercase tracking-wider">Consultando sistema...</p>
                    </div>
                    
                    <div v-else class="bg-white border border-slate-200 rounded-xl max-h-72 overflow-y-auto shadow-inner">
                        <v-list lines="two" v-if="historialPagos.length > 0" class="bg-transparent">
                            <v-list-item v-for="(pago, idx) in historialPagos" :key="idx" class="border-b border-slate-100 hover:bg-slate-50 transition-colors">
                                <template v-slot:prepend>
                                    <div class="w-10 h-10 rounded-full bg-emerald-50 flex items-center justify-center mr-3 border border-emerald-100">
                                        <v-icon color="emerald-darken-1" size="20">mdi-cash-check</v-icon>
                                    </div>
                                </template>
                                <v-list-item-title class="font-black text-sm text-slate-800">
                                    Abono: <span class="text-emerald-600">+${{ Number(pago.pago_colegiatura).toLocaleString('es-MX', {minimumFractionDigits: 2}) }} MXN</span>
                                </v-list-item-title>
                                <v-list-item-subtitle class="text-xs text-slate-500 mt-1 font-medium">
                                    <span class="mr-2"><v-icon size="12" class="mr-1">mdi-pound</v-icon>{{ pago.idpago }}</span>
                                    <span class="mr-2"><v-icon size="12" class="mr-1">mdi-calendar</v-icon>{{ pago.Fecha_PrimerContacto }}</span>
                                    <span><v-icon size="12" class="mr-1">mdi-account-tie</v-icon>{{ pago.Tutor }}</span>
                                </v-list-item-subtitle>
                                <template v-slot:append>
                                    <v-tooltip text="Reimprimir Recibo PDF" location="left">
                                        <template v-slot:activator="{ props }">
                                            <v-btn v-bind="props" size="small" icon="mdi-file-pdf-box" variant="tonal" color="red-darken-1" @click="descargarPDF(pago.idpago)"></v-btn>
                                        </template>
                                    </v-tooltip>
                                </template>
                            </v-list-item>
                        </v-list>
                        <div v-else class="text-center text-slate-400 py-10 flex flex-col items-center">
                            <v-icon size="48" class="mb-3 opacity-40">mdi-database-off</v-icon>
                            <span class="text-sm font-semibold">No hay histórico de pagos para mostrar.</span>
                        </div>
                    </div>
                </v-card-text>

                <v-card-actions class="bg-slate-100 px-6 py-3 justify-end border-t border-slate-200">
                    <v-btn variant="elevated" color="slate-800" prepend-icon="mdi-close" @click="modalExpediente = false; historialPagos = []" class="text-none font-bold">
                        Cerrar Expediente
                    </v-btn>
                </v-card-actions>
            </v-card>
        </v-dialog>

      </div>
    </div>
  </AuthenticatedLayout>
</template>

<style scoped>
/* Adjust sweetalert styling slightly if needed, though mostly standard styles apply */
</style>
