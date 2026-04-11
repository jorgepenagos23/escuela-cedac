<script setup>
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import { Head } from "@inertiajs/vue3";
import TablaAlumnos from "./TablaAlumnos.vue";
import { ref, computed, onMounted } from 'vue';
import axios from 'axios';

// ── Calendario de cobranza ────────────────────────────────────────────────────
const vencidos    = ref([]);
const estaSemana  = ref([]);
const proximos    = ref([]);
const buscadorRef = ref(null);
const buscarCartera = ref('');
const filtroEstadoCartera = ref('todos');

const carteraUnificada = computed(() => {
    const todos = [
        ...vencidos.value.map(a => ({ ...a, estado: 'Vencido' })),
        ...estaSemana.value.map(a => ({ ...a, estado: 'Esta Semana' })),
        ...proximos.value.map(a => ({ ...a, estado: 'Próximo' }))
    ];
    let filtrados = todos;
    if (filtroEstadoCartera.value !== 'todos') {
        filtrados = filtrados.filter(a => a.estado === filtroEstadoCartera.value);
    }
    if (buscarCartera.value.trim()) {
        const q = buscarCartera.value.toLowerCase();
        filtrados = filtrados.filter(a =>
            a.nombre_alumno?.toLowerCase().includes(q) ||
            a.diplomado?.toLowerCase().includes(q) ||
            a.celular?.toLowerCase().includes(q)
        );
    }
    return filtrados;
});

const totalCartera = computed(() => carteraUnificada.value.reduce((s, a) => s + parseFloat(a.saldo || 0), 0));
const totalVencidos = computed(() => vencidos.value.reduce((s, a) => s + parseFloat(a.saldo || 0), 0));
const totalSemana = computed(() => estaSemana.value.reduce((s, a) => s + parseFloat(a.saldo || 0), 0));
const totalProximos = computed(() => proximos.value.reduce((s, a) => s + parseFloat(a.saldo || 0), 0));

const hacerAbono = (nombre) => {
    window.scrollTo({ top: 0, behavior: 'smooth' });
    if (buscadorRef.value) buscadorRef.value.forzarBusqueda(nombre);
};

const exportarExcel = () => {
    import('xlsx').then(XLSX => {
        const datosExcel = carteraUnificada.value.map((item, index) => ({
            'No.': index + 1,
            'Alumno': item.nombre_alumno,
            'Diplomado': item.diplomado,
            'Saldo Pendiente': Number(item.saldo),
            'Fecha Pago': item.fecha_pago,
            'Estado': item.estado,
            'Celular': item.celular || 'N/A'
        }));
        
        const worksheet = XLSX.utils.json_to_sheet(datosExcel);
        const workbook = XLSX.utils.book_new();
        XLSX.utils.book_append_sheet(workbook, worksheet, "Cobranza");
        XLSX.writeFile(workbook, "Reporte_Cobranza.xlsx");
        mostrarSnack('Reporte Excel generado exitosamente.', 'success');
    }).catch(e => {
        console.error(e);
        mostrarSnack('Error al generar Excel.', 'error');
    });
};

const exportarPDF = () => {
    Promise.all([
        import('jspdf'), 
        import('jspdf-autotable')
    ]).then(([jsPDFModule]) => {
        // En algunas configuraciones de bundles jsPDF puede venir por default
        const jsPDFConstructor = jsPDFModule.default || jsPDFModule.jsPDF;
        const doc = new jsPDFConstructor();
        
        doc.setFontSize(14);
        doc.text("Reporte de Cartera de Cobranza", 14, 15);
        doc.setFontSize(10);
        doc.text(`Generado el: ${new Date().toLocaleDateString('es-MX')}`, 14, 22);

        const body = carteraUnificada.value.map((item, index) => [
            index + 1,
            item.nombre_alumno,
            item.diplomado,
            `$${Number(item.saldo).toLocaleString('es-MX')}`,
            item.fecha_pago,
            item.estado
        ]);

        doc.autoTable({
            startY: 28,
            head: [['#', 'Alumno', 'Diplomado', 'Saldo', 'Fecha', 'Estado']],
            body: body,
            theme: 'grid',
            headStyles: { fillColor: [49, 46, 129] },
            styles: { fontSize: 8 }
        });

        doc.save("Reporte_Cobranza.pdf");
        mostrarSnack('Reporte PDF generado exitosamente.', 'success');
    }).catch(e => {
        console.error(e);
        mostrarSnack('Error al generar PDF.', 'error');
    });
};

onMounted(async () => {
    try {
        const res = await axios.get('/api/v1/pagos/calendario');
        vencidos.value   = res.data.vencidos;
        estaSemana.value = res.data.esta_semana;
        proximos.value   = res.data.proximos;
    } catch (e) {
        console.error('Error calendario:', e);
    }
});

// ── BARRA DE ACCIONES ─────────────────────────────────────────────────────────
// Se abre al seleccionar un alumno desde el buscador o desde las columnas del calendario

// ─── Modal Cancelar Abono ─────────────────────────────────────────────────────
const modalCancelar     = ref(false);
const alumnoSeleccionado = ref(null);  // { id, nombre_alumno, diplomado }
const historialAbonos   = ref([]);
const loadingHistorial  = ref(false);
const abonoCancelarId   = ref(null);
const motivoCancelacion = ref('');
const cancelando        = ref(false);
const snackbar          = ref(false);
const snackMsg          = ref('');
const snackColor        = ref('success');

const mostrarSnack = (msg, color = 'success') => {
    snackMsg.value = msg; snackColor.value = color; snackbar.value = true;
};

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
        mostrarSnack('No se pudo cargar el historial de abonos.', 'error');
    } finally {
        loadingHistorial.value = false;
    }
};

const confirmarCancelacion = async () => {
    if (!abonoCancelarId.value) {
        mostrarSnack('Selecciona el abono a cancelar.', 'warning'); return;
    }
    if (!motivoCancelacion.value.trim()) {
        mostrarSnack('Debes escribir el motivo de cancelación.', 'warning'); return;
    }
    cancelando.value = true;
    try {
        await axios.post(`/api/v1/pagos/${abonoCancelarId.value}/cancelar`, {
            motivo: motivoCancelacion.value,
        });
        mostrarSnack('Abono cancelado y saldo restituido correctamente.', 'success');
        modalCancelar.value = false;
        // Recargar calendario
        const res = await axios.get('/api/v1/pagos/calendario');
        vencidos.value   = res.data.vencidos;
        estaSemana.value = res.data.esta_semana;
        proximos.value   = res.data.proximos;
    } catch (e) {
        mostrarSnack(e.response?.data?.error ?? 'Error al cancelar el abono.', 'error');
    } finally {
        cancelando.value = false;
    }
};

// ─── Modal Reprogramar Plan ───────────────────────────────────────────────────
const modalPlan         = ref(false);
const planData          = ref(null);   // { inscripcion, pagos_realizados, plan_pagos }
const loadingPlan       = ref(false);
const guardandoPlan     = ref(false);
const planEditable      = ref([]);     // array de cuotas { fecha, monto, descripcion }

const abrirModalPlan = async (alumno) => {
    alumnoSeleccionado.value = alumno;
    loadingPlan.value        = true;
    planEditable.value       = [];
    planData.value           = null;
    modalPlan.value          = true;

    try {
        const res = await axios.get(`/api/v1/alumno/${alumno.id}/plan-pagos`);
        planData.value = res.data;

        // Si ya tiene plan guardado, lo cargamos. Si no, generamos uno automático
        if (res.data.plan_pagos && res.data.plan_pagos.length > 0) {
            planEditable.value = res.data.plan_pagos.map(p => ({ ...p }));
        } else {
            generarPlanAutomatico(res.data.inscripcion);
        }
    } catch (e) {
        mostrarSnack('No se pudo cargar el plan de pagos.', 'error');
    } finally {
        loadingPlan.value = false;
    }
};

const generarPlanAutomatico = (inscripcion) => {
    const saldo     = parseFloat(inscripcion.saldo) || 0;
    const numCuotas = 4;
    const montoCuota = Math.ceil(saldo / numCuotas);

    planEditable.value = Array.from({ length: numCuotas }, (_, i) => {
        const fecha = new Date();
        fecha.setMonth(fecha.getMonth() + i + 1);
        return {
            fecha:       fecha.toISOString().split('T')[0],
            monto:       i < numCuotas - 1 ? montoCuota : saldo - montoCuota * (numCuotas - 1),
            descripcion: `Mensualidad ${i + 1}`,
        };
    });
};

const agregarCuota = () => {
    const ultima = planEditable.value[planEditable.value.length - 1];
    const fecha  = ultima ? new Date(ultima.fecha) : new Date();
    fecha.setMonth(fecha.getMonth() + 1);
    planEditable.value.push({
        fecha:       fecha.toISOString().split('T')[0],
        monto:       0,
        descripcion: `Mensualidad ${planEditable.value.length + 1}`,
    });
};

const quitarCuota = (idx) => {
    planEditable.value.splice(idx, 1);
};

const totalPlan = computed(() =>
    planEditable.value.reduce((acc, c) => acc + parseFloat(c.monto || 0), 0)
);

const guardarPlan = async () => {
    if (planEditable.value.length === 0) {
        mostrarSnack('Agrega al menos una cuota al plan.', 'warning'); return;
    }
    guardandoPlan.value = true;
    try {
        await axios.post(`/api/v1/alumno/${alumnoSeleccionado.value.id}/plan-pagos`, {
            plan: planEditable.value,
        });
        mostrarSnack('Plan de pagos guardado exitosamente.', 'success');
        modalPlan.value = false;
    } catch (e) {
        mostrarSnack(e.response?.data?.message ?? 'Error al guardar el plan.', 'error');
    } finally {
        guardandoPlan.value = false;
    }
};

// Color de estado abono
const colorEstado = (status) => {
    const map = { Pagado: 'success', Activo: 'blue', Cancelado: 'error' };
    return map[status] ?? 'grey';
};
const busquedaRapida      = ref('');
const resultadosBusqueda  = ref([]);

const buscarAlumnoRapido = async () => {
    if (!busquedaRapida.value.trim()) return;
    try {
        const res = await axios.get('/api/v1/pagos/calendario');
        const todos = [...res.data.vencidos, ...res.data.esta_semana, ...res.data.proximos];
        const q = busquedaRapida.value.toLowerCase();
        resultadosBusqueda.value = todos.filter(a =>
            a.nombre_alumno?.toLowerCase().includes(q)
        ).slice(0, 6);
    } catch (e) { console.error(e); }
};
</script>

<template>
  <AuthenticatedLayout>
    <v-app class="bg-gray-50">
      <v-main>
        <Head title="Calendario de Pagos" />
        <div class="min-h-screen py-8">
          <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">

        <!-- ── Encabezado ── -->
        <div class="mb-6 flex items-center gap-4">
          <v-avatar size="62" rounded class="bg-white shadow">
              <img src="/images/logo-cedac.jpg" alt="CEDAC Logo" class="h-full w-full object-cover">
          </v-avatar>
          <div>
            <h2 class="text-2xl font-bold text-gray-800 flex items-center">
                <v-icon color="indigo-darken-2" class="mr-2">mdi-calendar-check</v-icon>
                Calendario de Cobranza
            </h2>
            <p class="text-sm text-gray-500 mt-1">
              Alumnos con mensualidades pendientes agrupados por vencimiento.
            </p>
          </div>
        </div>

        <!-- ══ BARRA DE ACCIONES RÁPIDAS ══════════════════════════════════════ -->
        <div class="barra-acciones mb-6">
            <div class="barra-acciones__label">
                <v-icon size="18" color="white">mdi-lightning-bolt</v-icon>
                <span>Acciones sobre abonos:</span>
            </div>
            <div class="barra-acciones__btns">
                <!-- Cancelar Abono -->
                <v-dialog v-model="modalCancelar" max-width="680" persistent>
                    <template #activator="{ props }">
                        <v-btn
                            v-bind="props"
                            color="red-darken-1"
                            variant="flat"
                            prepend-icon="mdi-cancel"
                            class="barra-btn"
                            rounded="lg"
                        >
                            Cancelar Abono
                        </v-btn>
                    </template>

                    <!-- ── MODAL CANCELAR ABONO ── -->
                    <v-card rounded="xl" class="overflow-hidden">
                        <div class="modal-header modal-header--red">
                            <div>
                                <h3 class="modal-title">
                                    <v-icon class="mr-2">mdi-cancel</v-icon>Cancelar Abono Aplicado
                                </h3>
                                <p class="modal-sub">Esta acción revertirá el monto al saldo pendiente del alumno.</p>
                            </div>
                            <v-btn icon="mdi-close" variant="text" color="white" @click="modalCancelar = false" />
                        </div>

                        <v-card-text class="pa-6">
                            <!-- Buscador de alumno -->
                            <p class="text-sm font-semibold text-gray-700 mb-3">
                                <v-icon size="16" color="red-darken-1" class="mr-1">mdi-account-search</v-icon>
                                Paso 1 — Selecciona el alumno
                            </p>
                            <alumno-selector
                                @seleccionado="abrirModalCancelar"
                            />

                            <template v-if="alumnoSeleccionado && modalCancelar">
                                <v-divider class="my-4" />
                                <p class="text-sm font-semibold text-gray-700 mb-3">
                                    <v-icon size="16" color="red-darken-1" class="mr-1">mdi-cash-remove</v-icon>
                                    Paso 2 — Selecciona el abono a cancelar
                                    <v-chip size="x-small" color="indigo" class="ml-2">{{ alumnoSeleccionado.nombre_alumno }}</v-chip>
                                </p>

                                <div v-if="loadingHistorial" class="text-center py-6">
                                    <v-progress-circular indeterminate color="red-darken-1" />
                                </div>
                                <template v-else-if="historialAbonos.length > 0">
                                    <div class="abonos-lista">
                                        <div
                                            v-for="abono in historialAbonos"
                                            :key="abono.id"
                                            class="abono-item"
                                            :class="{ 'abono-item--selected': abonoCancelarId === abono.id }"
                                            @click="abonoCancelarId = abono.id"
                                        >
                                            <v-icon :color="abonoCancelarId === abono.id ? 'red' : 'green'" size="20">
                                                {{ abonoCancelarId === abono.id ? 'mdi-radiobox-marked' : 'mdi-radiobox-blank' }}
                                            </v-icon>
                                            <div class="abono-info">
                                                <span class="abono-monto">+${{ Number(abono.pago_colegiatura).toLocaleString('es-MX') }} MXN</span>
                                                <span class="abono-fecha">{{ abono.Fecha_PrimerContacto }} · Folio #{{ abono.id }}</span>
                                            </div>
                                            <v-chip size="x-small" :color="colorEstado(abono.status)" variant="flat">
                                                {{ abono.status }}
                                            </v-chip>
                                        </div>
                                    </div>

                                    <v-divider class="my-4" />
                                    <p class="text-sm font-semibold text-gray-700 mb-2">
                                        <v-icon size="16" color="red-darken-1" class="mr-1">mdi-comment-alert</v-icon>
                                        Paso 3 — Motivo de cancelación <span class="text-red-500">*</span>
                                    </p>
                                    <v-textarea
                                        v-model="motivoCancelacion"
                                        label="Describe el motivo..."
                                        rows="3"
                                        variant="outlined"
                                        counter="500"
                                        maxlength="500"
                                        density="compact"
                                    />
                                </template>
                                <div v-else class="text-center py-4 text-gray-400">
                                    <v-icon size="40" class="mb-2">mdi-cash-off</v-icon><br>
                                    Este alumno no tiene abonos activos.
                                </div>
                            </template>

                            <div v-if="!alumnoSeleccionado" class="sin-seleccion">
                                <v-icon size="40" color="red-lighten-3">mdi-account-question</v-icon>
                                <p>Selecciona un alumno de la lista de abonos del calendario o del buscador superior.</p>
                                <div class="mt-4">
                                    <p class="text-xs text-gray-500 mb-3">O busca directamente:</p>
                                    <v-text-field
                                        v-model="busquedaRapida"
                                        label="Nombre del alumno"
                                        variant="outlined"
                                        density="compact"
                                        hide-details
                                        prepend-inner-icon="mdi-magnify"
                                        @keyup.enter="buscarAlumnoRapido"
                                    />
                                    <div v-if="resultadosBusqueda.length > 0" class="resultados-rapidos mt-2">
                                        <div
                                            v-for="a in resultadosBusqueda"
                                            :key="a.id"
                                            class="resultado-item"
                                            @click="abrirModalCancelar(a)"
                                        >
                                            <v-icon size="16" color="indigo" class="mr-2">mdi-account</v-icon>
                                            {{ a.nombre_alumno }} — {{ a.diplomado }}
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </v-card-text>

                        <v-card-actions class="px-6 pb-5 pt-0 justify-end gap-2">
                            <v-btn variant="tonal" color="grey-darken-1" @click="modalCancelar = false; alumnoSeleccionado = null">
                                Cerrar
                            </v-btn>
                            <v-btn
                                color="red-darken-2"
                                variant="flat"
                                prepend-icon="mdi-trash-can"
                                :loading="cancelando"
                                :disabled="!abonoCancelarId || !motivoCancelacion.trim()"
                                @click="confirmarCancelacion"
                            >
                                Confirmar Cancelación
                            </v-btn>
                        </v-card-actions>
                    </v-card>
                </v-dialog>

                <!-- Reprogramar Plan de Pagos -->
                <v-dialog v-model="modalPlan" max-width="750" persistent>
                    <template #activator="{ props }">
                        <v-btn
                            v-bind="props"
                            color="deep-purple-darken-1"
                            variant="flat"
                            prepend-icon="mdi-calendar-edit"
                            class="barra-btn"
                            rounded="lg"
                        >
                            Reprogramar Plan de Pagos
                        </v-btn>
                    </template>

                    <!-- ── MODAL REPROGRAMAR PLAN ── -->
                    <v-card rounded="xl" class="overflow-hidden">
                        <div class="modal-header modal-header--purple">
                            <div>
                                <h3 class="modal-title">
                                    <v-icon class="mr-2">mdi-calendar-edit</v-icon>Esquema de Plan de Pagos
                                </h3>
                                <p class="modal-sub">Define las fechas y montos de las mensualidades del alumno.</p>
                            </div>
                            <v-btn icon="mdi-close" variant="text" color="white" @click="modalPlan = false" />
                        </div>

                        <v-card-text class="pa-6">
                            <!-- Buscador de alumno -->
                            <p class="text-sm font-semibold text-gray-700 mb-3">
                                <v-icon size="16" color="deep-purple-darken-1" class="mr-1">mdi-account-search</v-icon>
                                Paso 1 — Selecciona el alumno
                            </p>

                            <div v-if="!alumnoSeleccionado || !modalPlan" class="sin-seleccion">
                                <v-icon size="40" color="deep-purple-lighten-3">mdi-account-question</v-icon>
                                <p>Abre el plan desde los botones de acción de las columnas de vencimiento.</p>
                            </div>

                            <template v-else>
                                <!-- Info del alumno -->
                                <div v-if="planData" class="info-alumno-plan mb-5">
                                    <div class="info-alumno-plan__row">
                                        <span class="iap-label">Alumno:</span>
                                        <span class="iap-value font-bold">{{ planData.inscripcion.nombre_alumno }}</span>
                                    </div>
                                    <div class="info-alumno-plan__row">
                                        <span class="iap-label">Diplomado:</span>
                                        <span class="iap-value">{{ planData.inscripcion.diplomado }}</span>
                                    </div>
                                    <div class="info-alumno-plan__row">
                                        <span class="iap-label">Saldo pendiente:</span>
                                        <span class="iap-value text-red-600 font-bold">
                                            ${{ Number(planData.inscripcion.saldo).toLocaleString('es-MX') }} MXN
                                        </span>
                                    </div>
                                    <div class="info-alumno-plan__row">
                                        <span class="iap-label">Total del plan actual:</span>
                                        <span class="iap-value" :class="totalPlan > planData.inscripcion.saldo ? 'text-red-500' : 'text-green-600'">
                                            ${{ Number(totalPlan).toLocaleString('es-MX') }} MXN
                                            <v-icon size="14" v-if="totalPlan > planData.inscripcion.saldo" color="red">mdi-alert</v-icon>
                                        </span>
                                    </div>
                                </div>

                                <div v-if="loadingPlan" class="text-center py-6">
                                    <v-progress-circular indeterminate color="deep-purple-darken-1" />
                                </div>

                                <!-- Lista de cuotas editables -->
                                <template v-else>
                                    <div class="cuotas-header">
                                        <span class="text-sm font-bold text-gray-700">Cuotas del Plan</span>
                                        <v-btn
                                            size="small" variant="tonal" color="deep-purple"
                                            prepend-icon="mdi-plus-circle"
                                            @click="agregarCuota"
                                        >
                                            Agregar cuota
                                        </v-btn>
                                    </div>

                                    <div class="cuotas-lista">
                                        <div
                                            v-for="(cuota, idx) in planEditable"
                                            :key="idx"
                                            class="cuota-row"
                                        >
                                            <span class="cuota-num">#{{ idx + 1 }}</span>
                                            <v-text-field
                                                v-model="cuota.fecha"
                                                type="date"
                                                label="Fecha"
                                                variant="outlined"
                                                density="compact"
                                                hide-details
                                                class="cuota-input"
                                            />
                                            <v-text-field
                                                v-model.number="cuota.monto"
                                                type="number"
                                                label="Monto $"
                                                variant="outlined"
                                                density="compact"
                                                hide-details
                                                prefix="$"
                                                class="cuota-input cuota-input--narrow"
                                            />
                                            <v-text-field
                                                v-model="cuota.descripcion"
                                                label="Descripción"
                                                variant="outlined"
                                                density="compact"
                                                hide-details
                                                class="cuota-input"
                                            />
                                            <v-btn
                                                icon="mdi-trash-can-outline"
                                                size="small" variant="text" color="red"
                                                @click="quitarCuota(idx)"
                                            />
                                        </div>

                                        <div v-if="planEditable.length === 0" class="text-center py-4 text-gray-400 text-sm">
                                            Sin cuotas. Haz clic en "Agregar cuota" para comenzar.
                                        </div>
                                    </div>

                                    <!-- Aviso si total excede saldo -->
                                    <v-alert
                                        v-if="planData && totalPlan > planData.inscripcion.saldo"
                                        type="warning"
                                        variant="tonal"
                                        density="compact"
                                        class="mt-3"
                                        icon="mdi-alert"
                                    >
                                        El total del plan (${{ Number(totalPlan).toLocaleString('es-MX') }}) excede el saldo pendiente (${{ Number(planData.inscripcion.saldo).toLocaleString('es-MX') }}).
                                        Revisa los montos.
                                    </v-alert>
                                </template>
                            </template>
                        </v-card-text>

                        <v-card-actions class="px-6 pb-5 pt-0 justify-end gap-2">
                            <v-btn
                                v-if="planData && planEditable.length === 0"
                                variant="tonal"
                                color="deep-purple"
                                prepend-icon="mdi-refresh"
                                @click="generarPlanAutomatico(planData.inscripcion)"
                            >
                                Generar automáticamente (4 cuotas)
                            </v-btn>
                            <v-btn variant="tonal" color="grey-darken-1" @click="modalPlan = false; alumnoSeleccionado = null">
                                Cancelar
                            </v-btn>
                            <v-btn
                                color="deep-purple-darken-2"
                                variant="flat"
                                prepend-icon="mdi-content-save"
                                :loading="guardandoPlan"
                                :disabled="planEditable.length === 0 || !alumnoSeleccionado"
                                @click="guardarPlan"
                            >
                                Guardar Plan
                            </v-btn>
                        </v-card-actions>
                    </v-card>
                </v-dialog>
            </div>
        </div>
        <!-- ══ FIN BARRA DE ACCIONES ══════════════════════════════════════════ -->

        <!-- Buscador / Tabla de cobros -->
        <div class="mb-10">
            <h3 class="text-xl font-bold text-gray-800 flex items-center mb-4">
              <v-icon color="blue-darken-2" class="mr-2">mdi-table-large</v-icon>
              Buscador de Cartera para Cobros y Abonos (Caja)
            </h3>
            <TablaAlumnos ref="buscadorRef"></TablaAlumnos>
        </div>


        <!-- ══ TABLA DE CARTERA UNIFICADA ═══════════════════════════════════ -->
        <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden mb-10">
            <!-- Cabecera con buscador y filtro -->
            <div class="bg-gray-800 text-white px-5 py-3 flex flex-wrap items-center justify-between gap-3">
                <h3 class="font-bold text-sm flex items-center gap-2">
                    <v-icon size="20" color="white">mdi-table-large</v-icon>
                    Cartera de Cobranza
                    <span class="bg-white/20 text-xs px-2 py-0.5 rounded-full ml-1">{{ carteraUnificada.length }} registros</span>
                </h3>
                <div class="flex items-center gap-3">
                    <select v-model="filtroEstadoCartera"
                            class="bg-gray-700 text-white text-xs border border-gray-600 rounded-lg px-3 py-1.5 focus:outline-none focus:ring-2 focus:ring-indigo-400">
                        <option value="todos">Todos los estados</option>
                        <option value="Vencido">🔴 Vencidos</option>
                        <option value="Esta Semana">🟠 Esta Semana</option>
                        <option value="Próximo">🟢 Próximos</option>
                    </select>
                    <input v-model="buscarCartera"
                           type="text"
                           placeholder="Buscar alumno, diplomado..."
                           class="bg-gray-700 text-white text-xs border border-gray-600 rounded-lg px-3 py-1.5 w-56 focus:outline-none focus:ring-2 focus:ring-indigo-400 placeholder-gray-400" />
                    
                    <!-- Botón Exportar PDF -->
                    <v-btn size="small" color="red-darken-2" variant="flat" prepend-icon="mdi-file-pdf-box" @click="exportarPDF">
                        PDF
                    </v-btn>
                    
                    <!-- Botón Exportar Excel -->
                    <v-btn size="small" color="green-darken-2" variant="flat" prepend-icon="mdi-file-excel" @click="exportarExcel">
                        Excel
                    </v-btn>
                </div>
            </div>

            <!-- Tabla estilo Bootstrap -->
            <div class="overflow-x-auto max-h-[600px] overflow-y-auto">
                <table class="cartera-table">
                    <thead>
                        <tr>
                            <th style="width:30px">#</th>
                            <th>Alumno</th>
                            <th>Diplomado</th>
                            <th style="width:110px">Saldo</th>
                            <th style="width:110px">Fecha Pago</th>
                            <th style="width:120px">Estado</th>
                            <th style="width:140px">Celular</th>
                            <th style="width:160px">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-if="carteraUnificada.length === 0">
                            <td colspan="8" class="text-center py-8 text-gray-400">
                                <v-icon size="40" class="mb-2" color="grey-lighten-1">mdi-table-off</v-icon><br>
                                No se encontraron registros
                            </td>
                        </tr>
                        <tr v-for="(item, idx) in carteraUnificada" :key="item.id + '-' + idx"
                            :class="{'cartera-row-vencido': item.estado === 'Vencido', 'cartera-row-semana': item.estado === 'Esta Semana', 'cartera-row-proximo': item.estado === 'Próximo'}">
                            <td class="text-center text-gray-400 text-xs">{{ idx + 1 }}</td>
                            <td class="font-semibold text-gray-800">{{ item.nombre_alumno }}</td>
                            <td class="text-indigo-600 text-xs">{{ item.diplomado }}</td>
                            <td class="font-bold"
                                :class="{'text-red-600': item.estado === 'Vencido', 'text-orange-600': item.estado === 'Esta Semana', 'text-green-600': item.estado === 'Próximo'}">
                                ${{ Number(item.saldo).toLocaleString('en-US') }}
                            </td>
                            <td class="text-xs text-gray-500">{{ item.fecha_pago }}</td>
                            <td>
                                <span class="cartera-badge"
                                      :class="{'cartera-badge--rojo': item.estado === 'Vencido', 'cartera-badge--naranja': item.estado === 'Esta Semana', 'cartera-badge--verde': item.estado === 'Próximo'}">
                                    {{ item.estado }}
                                </span>
                            </td>
                            <td class="text-xs text-gray-500">
                                <a v-if="item.celular" :href="'https://wa.me/52' + item.celular.replace(/[^0-9]/g,'')" target="_blank" class="text-green-600 hover:text-green-800">
                                    <v-icon size="14" color="green">mdi-whatsapp</v-icon> {{ item.celular }}
                                </a>
                            </td>
                            <td>
                                <div class="flex gap-1">
                                    <v-btn size="x-small" color="success" variant="flat"
                                           icon="mdi-cash-plus"
                                           @click="hacerAbono(item.nombre_alumno)" title="Abonar" />
                                    <v-btn size="x-small" color="red" variant="tonal"
                                           icon="mdi-cancel"
                                           @click="abrirModalCancelar(item)" title="Cancelar abono" />
                                    <v-btn size="x-small" color="deep-purple" variant="tonal"
                                           icon="mdi-calendar-edit"
                                           @click="abrirModalPlan(item)" title="Plan de pagos" />
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

      </div>
    </div>

    <!-- ── Snackbar global ── -->
    <v-snackbar
        v-model="snackbar"
        :color="snackColor"
        location="bottom right"
        :timeout="3800"
        rounded="xl"
    >
        <v-icon class="mr-2">
            {{ snackColor === 'success' ? 'mdi-check-circle' : snackColor === 'warning' ? 'mdi-alert' : 'mdi-alert-circle' }}
        </v-icon>
        {{ snackMsg }}
    </v-snackbar>
      </v-main>
    </v-app>
  </AuthenticatedLayout>
</template>



<style scoped>
/* ── Barra de acciones ── */
.barra-acciones {
    display: flex;
    align-items: center;
    gap: 12px;
    background: linear-gradient(135deg, #312e81 0%, #4c1d95 100%);
    border-radius: 16px;
    padding: 14px 20px;
    box-shadow: 0 4px 20px rgba(76, 29, 149, 0.3);
}
.barra-acciones__label {
    display: flex;
    align-items: center;
    gap: 6px;
    color: white;
    font-size: 0.82rem;
    font-weight: 700;
    letter-spacing: 0.04em;
    text-transform: uppercase;
    white-space: nowrap;
    opacity: 0.9;
}
.barra-acciones__btns {
    display: flex;
    gap: 10px;
    flex-wrap: wrap;
}
.barra-btn {
    font-weight: 600 !important;
    letter-spacing: 0.02em !important;
}

/* ── Headers de modales ── */
.modal-header {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    padding: 20px 24px;
    color: white;
}
.modal-header--red    { background: linear-gradient(135deg, #b91c1c, #ef4444); }
.modal-header--purple { background: linear-gradient(135deg, #5b21b6, #7c3aed); }
.modal-title {
    font-size: 1.1rem;
    font-weight: 700;
    display: flex;
    align-items: center;
    margin-bottom: 4px;
}
.modal-sub {
    font-size: 0.8rem;
    opacity: 0.85;
}

/* ── Lista de abonos ── */
.abonos-lista {
    display: flex;
    flex-direction: column;
    gap: 8px;
    max-height: 220px;
    overflow-y: auto;
    padding-right: 4px;
}
.abono-item {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 10px 14px;
    border-radius: 12px;
    border: 1.5px solid #e5e7eb;
    cursor: pointer;
    transition: all 0.15s;
    background: white;
}
.abono-item:hover { border-color: #f87171; background: #fef2f2; }
.abono-item--selected { border-color: #ef4444; background: #fef2f2; box-shadow: 0 0 0 2px #fca5a5; }
.abono-info { flex: 1; }
.abono-monto { display: block; font-weight: 700; font-size: 0.9rem; color: #166534; }
.abono-fecha { font-size: 0.75rem; color: #6b7280; }

/* ── Sin selección placeholder ── */
.sin-seleccion {
    text-align: center;
    padding: 28px 16px;
    color: #9ca3af;
}
.sin-seleccion p { margin-top: 10px; font-size: 0.85rem; }

/* ── Resultados búsqueda rápida ── */
.resultados-rapidos {
    border: 1px solid #e5e7eb;
    border-radius: 10px;
    overflow: hidden;
}
.resultado-item {
    padding: 10px 12px;
    font-size: 0.83rem;
    cursor: pointer;
    border-bottom: 1px solid #f3f4f6;
    transition: background 0.15s;
    display: flex;
    align-items: center;
}
.resultado-item:hover { background: #f5f3ff; }
.resultado-item:last-child { border-bottom: none; }

/* ── Plan de pagos ── */
.info-alumno-plan {
    background: #f9f5ff;
    border: 1px solid #ede9fe;
    border-radius: 12px;
    padding: 14px 18px;
}
.info-alumno-plan__row {
    display: flex;
    align-items: center;
    gap: 8px;
    margin-bottom: 6px;
    font-size: 0.85rem;
}
.info-alumno-plan__row:last-child { margin-bottom: 0; }
.iap-label { color: #6b7280; min-width: 140px; }
.iap-value { color: #111827; }

.cuotas-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 10px;
}
.cuotas-lista {
    display: flex;
    flex-direction: column;
    gap: 8px;
    max-height: 300px;
    overflow-y: auto;
    padding-right: 4px;
}
.cuota-row {
    display: flex;
    align-items: center;
    gap: 8px;
}
.cuota-num {
    font-size: 0.75rem;
    font-weight: 700;
    color: #7c3aed;
    min-width: 26px;
}
.cuota-input { flex: 1; }
.cuota-input--narrow { max-width: 110px; }

.overflow-y-auto::-webkit-scrollbar,
.abonos-lista::-webkit-scrollbar,
.cuotas-lista::-webkit-scrollbar { width: 5px; }
.overflow-y-auto::-webkit-scrollbar-track,
.abonos-lista::-webkit-scrollbar-track,
.cuotas-lista::-webkit-scrollbar-track { background: transparent; }
.overflow-y-auto::-webkit-scrollbar-thumb,
.abonos-lista::-webkit-scrollbar-thumb,
.cuotas-lista::-webkit-scrollbar-thumb { background: #d1d5db; border-radius: 4px; }

/* ══ Tabla de Cartera (estilo Bootstrap) ══ */
.cartera-table {
    width: 100%;
    border-collapse: collapse;
    font-size: 0.8rem;
}
.cartera-table thead {
    position: sticky;
    top: 0;
    z-index: 2;
}
.cartera-table thead tr {
    background: #1f2937;
    color: #fff;
}
.cartera-table th {
    padding: 10px 12px;
    text-align: left;
    font-weight: 700;
    font-size: 0.7rem;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    white-space: nowrap;
    border-bottom: 2px solid #374151;
}
.cartera-table td {
    padding: 8px 12px;
    border-bottom: 1px solid #e5e7eb;
    vertical-align: middle;
}
.cartera-table tbody tr:nth-child(even) {
    background: #f9fafb;
}
.cartera-table tbody tr:hover {
    background: #eef2ff !important;
}

/* Filas coloreadas por estado */
.cartera-row-vencido { border-left: 3px solid #dc2626; }
.cartera-row-semana  { border-left: 3px solid #f97316; }
.cartera-row-proximo { border-left: 3px solid #16a34a; }

/* Badges de estado */
.cartera-badge {
    display: inline-block;
    padding: 2px 10px;
    border-radius: 999px;
    font-size: 0.65rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.04em;
    white-space: nowrap;
}
.cartera-badge--rojo    { background: #fef2f2; color: #dc2626; border: 1px solid #fecaca; }
.cartera-badge--naranja { background: #fff7ed; color: #ea580c; border: 1px solid #fed7aa; }
.cartera-badge--verde   { background: #f0fdf4; color: #16a34a; border: 1px solid #bbf7d0; }
</style>
