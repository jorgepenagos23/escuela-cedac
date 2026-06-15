<script setup>
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import { Head, usePage } from "@inertiajs/vue3";
import { ref, computed, watch, onMounted, onUnmounted } from 'vue';
import axios from 'axios';
import * as XLSX from 'xlsx';
import { useErpWindows } from '@/Composables/useErpWindows';
import SeccionCard from '@/Components/SeccionCard.vue';
import CampoField from '@/Components/CampoField.vue';

const erp = useErpWindows();
const mounted = ref(false);

const page     = usePage();
const authUser = page.props.auth.user;

const puedeProgramarPlan = computed(() =>
    authUser?.roles?.includes('TI') ||
    authUser?.roles?.includes('Administrador') ||
    authUser?.permissions?.includes('editar_pagos_manual')
);

const puedeEditarDatosMaestros = computed(() =>
    !authUser?.roles?.includes('Admisiones')
);

// ── Estado principal ──────────────────────────────────────────────────────────
const busqueda        = ref('');
const alumnos         = ref([]);
const cargando        = ref(false);
const alumnoActivo    = ref(null);   // alumno seleccionado actualmente
const tabActiva       = ref('datos'); // 'datos' | 'plan'
const paginaActual    = ref(1);
const totalPaginas    = ref(1);

// ── Edición de datos ──────────────────────────────────────────────────────────
const editando        = ref(false);
const guardandoDatos  = ref(false);
const formDatos       = ref({});

// ── Plan de pagos ─────────────────────────────────────────────────────────────
const editandoPlan    = ref(false);
const guardandoPlan   = ref(false);
const planEditable    = ref([]);

// ── Snackbar ──────────────────────────────────────────────────────────────────
const snackbar   = ref(false);
const snackMsg   = ref('');
const snackColor = ref('success');
const mostrarSnack = (msg, color = 'success') => {
    snackMsg.value = msg; snackColor.value = color; snackbar.value = true;
};

// ── Búsqueda con debounce ─────────────────────────────────────────────────────
let debounceTimer = null;
watch(busqueda, (val) => {
    clearTimeout(debounceTimer);
    paginaActual.value = 1;
    debounceTimer = setTimeout(() => buscarAlumnos(), 350);
});

watch(paginaActual, (newVal, oldVal) => {
    if (newVal !== oldVal) buscarAlumnos();
});

const buscarAlumnos = async () => {
    cargando.value = true;
    try {
        const res = await axios.get('/api/v1/alumnos/buscar', { params: { q: busqueda.value, page: paginaActual.value } });
        alumnos.value = res.data.alumnos.data;
        totalPaginas.value = res.data.alumnos.last_page;
    } catch (e) {
        mostrarSnack('Error al buscar alumnos.', 'error');
    } finally {
        cargando.value = false;
    }
};

const initialFetch = async () => {
    // Si la pagina esta en estado 1 en el componente y acabamos de montar
    buscarAlumnos();
};

onMounted(() => {
    mounted.value = true;
    initialFetch();
    erp.registerTabExport('BuscadorAlumnos', {
        label: 'Exportar Alumnos Excel',
        icon:  'mdi-microsoft-excel',
        fn:    exportarExcel,
    });
});

onUnmounted(() => {
    erp.unregisterTabExport('BuscadorAlumnos');
});

// ─── Exportar a Excel ─────────────────────────────────────────────────────────
const exportarExcel = () => {
    if (!alumnos.value || alumnos.value.length === 0) {
        alert("No hay alumnos para exportar");
        return;
    }
    
    // Preparar JSON para exportación limpia
    const dataToExport = alumnos.value.map(a => ({
        ID: a.id,
        Nombre: a.nombre_alumno,
        'Diplomado': a.nombre_diplomado || 'No asignado',
        Celular: a.celular,
        Correo: a.correo,
        CURP: a.curp,
        'Saldo ($)': parseFloat(a.saldo || 0)
    }));
    
    const ws = XLSX.utils.json_to_sheet(dataToExport);
    const wb = XLSX.utils.book_new();
    XLSX.utils.book_append_sheet(wb, ws, "Alumnos_Buscador");
    XLSX.writeFile(wb, "Reporte_Alumnos.xlsx");
};

// ── Seleccionar alumno ────────────────────────────────────────────────────────
const seleccionarAlumno = (alumno) => {
    alumnoActivo.value = alumno;
    tabActiva.value    = 'datos';
    editando.value     = false;
    editandoPlan.value = false;
    formDatos.value    = clonar(alumno);
    planEditable.value = alumno.plan_pagos ? alumno.plan_pagos.map(p => ({ ...p })) : [];
};

const clonar = (obj) => JSON.parse(JSON.stringify(obj));

// ── Guardar datos del alumno ──────────────────────────────────────────────────
const guardarDatos = async () => {
    guardandoDatos.value = true;
    try {
        const res = await axios.put(`/api/v1/alumnos/${alumnoActivo.value.id}/actualizar`, formDatos.value);
        alumnoActivo.value = { ...alumnoActivo.value, ...formDatos.value };
        // actualizar en la lista
        const idx = alumnos.value.findIndex(a => a.id === alumnoActivo.value.id);
        if (idx !== -1) alumnos.value[idx] = { ...alumnos.value[idx], ...formDatos.value };
        editando.value = false;
        mostrarSnack('Datos actualizados correctamente.', 'success');
    } catch (e) {
        mostrarSnack(e.response?.data?.message ?? 'Error al guardar datos.', 'error');
    } finally {
        guardandoDatos.value = false;
    }
};

const cancelarEdicion = () => {
    formDatos.value = clonar(alumnoActivo.value);
    editando.value  = false;
};

// ── Plan de pagos ─────────────────────────────────────────────────────────────
const totalPlan  = computed(() => planEditable.value.reduce((a, c) => a + parseFloat(c.monto || 0), 0));
const saldoAlumno = computed(() => parseFloat(alumnoActivo.value?.saldo || 0));

const agregarCuota = () => {
    const ultima = planEditable.value[planEditable.value.length - 1];
    const f = ultima ? new Date(ultima.fecha + 'T12:00:00') : new Date();
    f.setMonth(f.getMonth() + 1);
    planEditable.value.push({
        numero:      planEditable.value.length + 1,
        fecha:       f.toISOString().split('T')[0],
        monto:       0,
        descripcion: `Mensualidad ${planEditable.value.length + 1}`,
        estado:      'pendiente',
    });
};

const quitarCuota = (idx) => planEditable.value.splice(idx, 1);

const regenerarPlanAuto = () => {
    const saldo     = saldoAlumno.value;
    const numCuotas = alumnoActivo.value?.duracion_mes > 0 ? alumnoActivo.value.duracion_mes : 5;
    const fechaBase = alumnoActivo.value?.fecha_primer_pago_colegiatura
        ? new Date(alumnoActivo.value.fecha_primer_pago_colegiatura + 'T12:00:00')
        : (() => { const d = new Date(); d.setMonth(d.getMonth()+1); return d; })();

    const montoCuota  = Math.floor((saldo / numCuotas) * 100) / 100;
    const montoUltima = Math.round((saldo - montoCuota * (numCuotas - 1)) * 100) / 100;

    planEditable.value = Array.from({ length: numCuotas }, (_, i) => {
        const f = new Date(fechaBase);
        f.setMonth(f.getMonth() + i);
        return {
            numero:      i + 1,
            fecha:       f.toISOString().split('T')[0],
            monto:       i < numCuotas - 1 ? montoCuota : montoUltima,
            descripcion: `Mensualidad ${i + 1} de ${numCuotas}`,
            estado:      'pendiente',
        };
    });
};

const guardarPlan = async () => {
    guardandoPlan.value = true;
    try {
        await axios.put(`/api/v1/alumnos/${alumnoActivo.value.id}/actualizar`, {
            plan_pagos: planEditable.value,
        });
        alumnoActivo.value.plan_pagos = clonar(planEditable.value);
        editandoPlan.value = false;
        mostrarSnack('Plan de pagos actualizado.', 'success');
    } catch (e) {
        mostrarSnack('Error al guardar el plan.', 'error');
    } finally {
        guardandoPlan.value = false;
    }
};

const cancelarEditarPlan = () => {
    planEditable.value = alumnoActivo.value.plan_pagos ? alumnoActivo.value.plan_pagos.map(p => ({ ...p })) : [];
    editandoPlan.value = false;
};

// ── Helpers ───────────────────────────────────────────────────────────────────
const colorEstado = (e) => ({ pendiente: 'grey', pagado: 'success', vencido: 'error' })[e] ?? 'grey';
const iconEstado  = (e) => ({ pendiente: 'mdi-clock-outline', pagado: 'mdi-check-circle', vencido: 'mdi-alert-circle' })[e] ?? 'mdi-clock-outline';

const initiales = (nombre) => (nombre ?? '?').split(' ').slice(0, 2).map(p => p[0]).join('').toUpperCase();
const colorAvatar = (id) => {
    const colors = ['indigo', 'deep-purple', 'blue', 'teal', 'green', 'orange', 'red', 'pink'];
    return colors[(id ?? 0) % colors.length];
};

const saldoClass = (saldo) => parseFloat(saldo) <= 0 ? 'text-green-600' : 'text-red-600';

const metodosPago = ['Efectivo', 'Transferencia Bancaria', 'Depósito en OXXO', 'Tarjeta de Crédito/Débito', 'Paypal'];
</script>

<template>
  <AuthenticatedLayout>
    <Head title="Buscador de Alumnos" />

    <!-- ── Telerport: Acción Modular de Barra Superior (Desktop) ── -->
    <Teleport to="#erp-tab-actions" v-if="mounted">
        <!-- New Admission button -->
        <button @click="erp.openErpWindow({ url: route('seguimiento.inscripciones'), name: 'Admisiones', icon: 'mdi-account-plus-outline' })"
                class="flex items-center gap-1.5 px-3 py-1.5 rounded-md bg-indigo-50 text-indigo-700 hover:bg-indigo-100 transition-colors shadow-sm border border-indigo-200">
            <v-icon size="16">mdi-account-plus</v-icon>
            <span class="text-sm font-semibold">Nueva Admisión</span>
        </button>

        <!-- Export Excel button -->
        <button @click="exportarExcel"
                class="flex items-center gap-1.5 px-3 py-1.5 rounded-md bg-emerald-50 text-emerald-700 hover:bg-emerald-100 transition-colors shadow-sm border border-emerald-200"
                :class="{ 'opacity-50 cursor-not-allowed': alumnos.length === 0 }"
                :disabled="alumnos.length === 0">
            <v-icon size="16">mdi-microsoft-excel</v-icon>
            <span class="text-sm font-semibold">Exportar ({{alumnos.length}})</span>
        </button>
    </Teleport>

    <div class="min-h-screen bg-gradient-to-br from-slate-50 to-indigo-50 py-8">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">

        <!-- ── Encabezado ── -->
        <div class="mb-8">
          <div class="flex items-center gap-3 mb-2">
            <div class="header-icon-wrap">
              <v-icon color="white" size="28">mdi-card-search</v-icon>
            </div>
            <div>
              <h1 class="text-2xl font-bold text-gray-900">Buscador de Alumnos</h1>
              <p class="text-sm text-gray-500">Busca, consulta y edita el expediente completo de cualquier alumno.</p>
            </div>
          </div>
        </div>

        <div class="layout-main">

          <!-- ══ Panel izquierdo: búsqueda y resultados ══ -->
          <div class="panel-busqueda">
            <!-- Buscador -->
            <div class="search-box">
              <v-text-field
                v-model="busqueda"
                placeholder="Nombre, celular, correo, CURP o ID..."
                variant="solo"
                density="comfortable"
                hide-details
                prepend-inner-icon="mdi-magnify"
                :loading="cargando"
                clearable
                rounded="xl"
                class="shadow-md"
                bg-color="white"
              />
              <p class="text-xs text-gray-400 mt-2 px-1">Escribe para buscar · Resultados en tiempo real</p>
            </div>

            <!-- Resultados -->
            <div class="resultados-wrap">
              <!-- Estado vacío inicial -->
              <div v-if="!busqueda.trim() && alumnos.length === 0" class="estado-vacio">
                <v-icon size="56" color="indigo-lighten-3">mdi-account-search</v-icon>
                <p class="mt-3 text-sm text-gray-400 text-center">Ingresa un nombre o dato del alumno para comenzar la búsqueda.</p>
              </div>

              <!-- Sin resultados -->
              <div v-else-if="!cargando && busqueda.trim() && alumnos.length === 0" class="estado-vacio">
                <v-icon size="56" color="grey-lighten-1">mdi-account-off</v-icon>
                <p class="mt-3 text-sm text-gray-400 text-center">Sin coincidencias para "<strong>{{ busqueda }}</strong>"</p>
              </div>

              <!-- Lista de alumnos -->
              <div v-else class="space-y-2">
                <div
                  v-for="alumno in alumnos"
                  :key="alumno.id"
                  class="tarjeta-alumno"
                  :class="{ 'tarjeta-alumno--activa': alumnoActivo?.id === alumno.id }"
                  @click="seleccionarAlumno(alumno)"
                >
                  <v-avatar :color="colorAvatar(alumno.id) + '-darken-1'" size="42" class="flex-shrink-0">
                    <span class="text-white font-bold text-sm">{{ initiales(alumno.nombre_alumno) }}</span>
                  </v-avatar>
                  <div class="flex-1 min-w-0">
                    <p class="font-semibold text-gray-800 text-sm truncate leading-tight">{{ alumno.nombre_alumno }}</p>
                    <p class="text-xs text-indigo-600 font-medium truncate mt-0.5">{{ alumno.nombre_diplomado ?? '—' }}</p>
                    <p class="text-xs text-gray-400 mt-0.5">
                      <v-icon size="10" class="mr-0.5">mdi-phone</v-icon>{{ alumno.celular ?? '—' }}
                    </p>
                  </div>
                  <div class="text-right flex-shrink-0">
                    <span class="text-xs font-bold" :class="saldoClass(alumno.saldo)">
                      ${{ Number(alumno.saldo ?? 0).toLocaleString('es-MX') }}
                    </span>
                    <p class="text-xs text-gray-400">saldo</p>
                  </div>
                </div>
              </div>

              <!-- Paginación -->
              <div v-if="totalPaginas > 1" class="mt-4 flex justify-center">
                <v-pagination
                  v-model="paginaActual"
                  :length="totalPaginas"
                  :total-visible="5"
                  density="compact"
                  active-color="indigo"
                ></v-pagination>
              </div>

            </div>
          </div>

          <!-- ══ Panel derecho: expediente del alumno ══ -->
          <div class="panel-expediente">

            <!-- Placeholder cuando no hay alumno seleccionado -->
            <div v-if="!alumnoActivo" class="expediente-vacio">
              <div class="expediente-vacio__contenido">
                <v-icon size="80" color="indigo-lighten-3">mdi-folder-open-outline</v-icon>
                <h3 class="text-xl font-semibold text-gray-400 mt-4">Selecciona un alumno</h3>
                <p class="text-sm text-gray-400 mt-2 text-center max-w-xs">
                  Usa el buscador de la izquierda para encontrar y seleccionar un alumno.
                  Su expediente completo aparecerá aquí.
                </p>
              </div>
            </div>

            <!-- Expediente del alumno seleccionado -->
            <template v-else>

              <!-- Cabecera del alumno -->
              <div class="expediente-header">
                <div class="flex items-center gap-4">
                  <v-avatar :color="colorAvatar(alumnoActivo.id) + '-darken-2'" size="56">
                    <span class="text-white font-bold text-xl">{{ initiales(alumnoActivo.nombre_alumno) }}</span>
                  </v-avatar>
                  <div>
                    <h2 class="text-white text-xl font-bold leading-tight">{{ alumnoActivo.nombre_alumno }}</h2>
                    <div class="flex items-center gap-2 mt-1 flex-wrap">
                      <v-chip size="x-small" color="indigo-lighten-3" variant="flat">
                        <v-icon start size="12">mdi-school</v-icon>
                        {{ alumnoActivo.nombre_diplomado ?? 'Sin diplomado' }}
                      </v-chip>
                      <v-chip size="x-small" :color="parseFloat(alumnoActivo.saldo) <= 0 ? 'success' : 'red-darken-1'" variant="flat">
                        Saldo: ${{ Number(alumnoActivo.saldo ?? 0).toLocaleString('es-MX') }} MXN
                      </v-chip>
                      <v-chip size="x-small" color="white" variant="outlined">
                        ID #{{ alumnoActivo.id }}
                      </v-chip>
                    </div>
                  </div>
                </div>

                <!-- Acciones de cabecera -->
                <div class="flex items-center gap-2 flex-wrap">
                  <v-btn
                    v-if="puedeEditarDatosMaestros && !editando && tabActiva === 'datos'"
                    size="small" color="white" variant="outlined"
                    prepend-icon="mdi-pencil"
                    @click="editando = true"
                  >Editar Datos</v-btn>
                  <template v-if="puedeEditarDatosMaestros && editando && tabActiva === 'datos'">
                    <v-btn size="small" color="success" variant="flat" :loading="guardandoDatos" prepend-icon="mdi-content-save" @click="guardarDatos">Guardar</v-btn>
                    <v-btn size="small" color="white" variant="text" @click="cancelarEdicion">Cancelar</v-btn>
                  </template>

                  <v-btn
                    v-if="puedeProgramarPlan && !editandoPlan && tabActiva === 'plan'"
                    size="small" color="white" variant="outlined"
                    prepend-icon="mdi-calendar-edit"
                    @click="editandoPlan = true"
                  >Editar Plan</v-btn>
                  <template v-if="editandoPlan && tabActiva === 'plan'">
                    <v-btn size="small" color="success" variant="flat" :loading="guardandoPlan" prepend-icon="mdi-content-save" @click="guardarPlan">Guardar Plan</v-btn>
                    <v-btn size="small" color="white" variant="text" @click="cancelarEditarPlan">Cancelar</v-btn>
                  </template>
                </div>
              </div>

              <!-- Tabs -->
              <v-tabs v-model="tabActiva" color="indigo-darken-3" class="bg-white border-b border-gray-100">
                <v-tab value="datos" prepend-icon="mdi-account-details">Datos del Alumno</v-tab>
                <v-tab value="plan" prepend-icon="mdi-calendar-multiselect">Plan de Pagos</v-tab>
              </v-tabs>

              <!-- ── TAB: DATOS DEL ALUMNO ── -->
              <v-window v-model="tabActiva">
                <v-window-item value="datos">
                  <div class="expediente-body">

                    <!-- Bloque: Identificación -->
                    <seccion-card titulo="Identificación Personal" icono="mdi-card-account-details-outline">
                      <div class="campos-grid">
                        <campo-field label="Nombre Completo">
                          <v-text-field v-if="editando" v-model="formDatos.nombre_alumno" variant="outlined" density="compact" hide-details />
                          <span v-else class="campo-valor">{{ alumnoActivo.nombre_alumno }}</span>
                        </campo-field>
                        <campo-field label="CURP">
                          <v-text-field v-if="editando" v-model="formDatos.curp" variant="outlined" density="compact" hide-details />
                          <span v-else class="campo-valor font-mono">{{ alumnoActivo.curp || '—' }}</span>
                        </campo-field>
                        <campo-field label="Celular Principal">
                          <v-text-field v-if="editando" v-model="formDatos.celular" variant="outlined" density="compact" hide-details />
                          <span v-else class="campo-valor">{{ alumnoActivo.celular || '—' }}</span>
                        </campo-field>
                        <campo-field label="Contacto Adicional">
                          <v-text-field v-if="editando" v-model="formDatos.adicional" variant="outlined" density="compact" hide-details />
                          <span v-else class="campo-valor">{{ alumnoActivo.adicional || '—' }}</span>
                        </campo-field>
                        <campo-field label="Correo Electrónico" class="md:col-span-2">
                          <v-text-field v-if="editando" v-model="formDatos.correo" variant="outlined" density="compact" hide-details />
                          <span v-else class="campo-valor">{{ alumnoActivo.correo || '—' }}</span>
                        </campo-field>
                      </div>
                    </seccion-card>

                    <!-- Bloque: Domicilio -->
                    <seccion-card titulo="Domicilio y Emergencia" icono="mdi-map-marker-radius">
                      <div class="campos-grid">
                        <campo-field label="Estado">
                          <v-text-field v-if="editando" v-model="formDatos.estado" variant="outlined" density="compact" hide-details />
                          <span v-else class="campo-valor">{{ alumnoActivo.estado || '—' }}</span>
                        </campo-field>
                        <campo-field label="Municipio / Ciudad">
                          <v-text-field v-if="editando" v-model="formDatos.municipio" variant="outlined" density="compact" hide-details />
                          <span v-else class="campo-valor">{{ alumnoActivo.municipio || '—' }}</span>
                        </campo-field>
                        <campo-field label="Dirección Completa" class="md:col-span-2">
                          <v-text-field v-if="editando" v-model="formDatos.direccion_completa" variant="outlined" density="compact" hide-details />
                          <span v-else class="campo-valor">{{ alumnoActivo.direccion_completa || '—' }}</span>
                        </campo-field>
                        <campo-field label="Contacto de Emergencia">
                          <v-text-field v-if="editando" v-model="formDatos.nombre_emergencia" variant="outlined" density="compact" hide-details />
                          <span v-else class="campo-valor">{{ alumnoActivo.nombre_emergencia || '—' }}</span>
                        </campo-field>
                        <campo-field label="Parentesco">
                          <v-text-field v-if="editando" v-model="formDatos.parentesco_emergencia" variant="outlined" density="compact" hide-details />
                          <span v-else class="campo-valor">{{ alumnoActivo.parentesco_emergencia || '—' }}</span>
                        </campo-field>
                      </div>
                    </seccion-card>

                    <!-- Bloque: Académico y Financiero -->
                    <seccion-card titulo="Académico y Financiero" icono="mdi-cash-multiple">
                      <div class="campos-grid">
                        <campo-field label="Diplomado Inscrito">
                          <span class="campo-valor text-indigo-700 font-semibold">{{ alumnoActivo.nombre_diplomado || '—' }}</span>
                        </campo-field>
                        <campo-field label="Grupo / Campaña">
                          <span class="campo-valor">{{ alumnoActivo.grupo || '—' }} · {{ alumnoActivo.campana || '—' }}</span>
                        </campo-field>
                        <campo-field label="Fecha de Inscripción">
                          <span class="campo-valor font-mono">{{ alumnoActivo.fecha_inscripcion || '—' }}</span>
                        </campo-field>
                        <campo-field label="Monto de Inscripción">
                          <span class="campo-valor text-green-700 font-bold">${{ Number(alumnoActivo.monto_inscripcion || 0).toLocaleString('es-MX') }} MXN</span>
                        </campo-field>
                        <campo-field label="Saldo Pendiente">
                          <span class="campo-valor font-bold" :class="saldoClass(alumnoActivo.saldo)">
                            ${{ Number(alumnoActivo.saldo || 0).toLocaleString('es-MX') }} MXN
                          </span>
                        </campo-field>
                        <campo-field label="Costo Total del Programa">
                          <span class="campo-valor">${{ Number(alumnoActivo.costo_total || 0).toLocaleString('es-MX') }} MXN</span>
                        </campo-field>
                        <campo-field label="1er Pago de Colegiatura">
                          <v-text-field v-if="editando" v-model="formDatos.fecha_primer_pago_colegiatura" type="date" variant="outlined" density="compact" hide-details />
                          <span v-else class="campo-valor font-mono">{{ alumnoActivo.fecha_primer_pago_colegiatura || '—' }}</span>
                        </campo-field>
                        <campo-field label="Método de Pago Inscripción">
                          <v-select v-if="editando" v-model="formDatos.metodo_pago_inscripcion" :items="metodosPago" variant="outlined" density="compact" hide-details />
                          <span v-else class="campo-valor">{{ alumnoActivo.metodo_pago_inscripcion || '—' }}</span>
                        </campo-field>
                        <campo-field label="Tutor Asignado">
                          <span class="campo-valor">{{ alumnoActivo.tutor_nombre || '—' }}</span>
                        </campo-field>
                        <campo-field label="Asesor / Vendedor">
                          <span class="campo-valor">{{ alumnoActivo.asesor_nombre || '—' }}</span>
                        </campo-field>
                        <campo-field v-if="alumnoActivo.descuento_id" label="Descuento Aplicado" class="md:col-span-2">
                          <div class="flex items-center gap-2">
                            <v-icon size="small" color="deep-purple-darken-1">mdi-tag-heart</v-icon>
                            <span class="campo-valor font-semibold text-deep-purple-darken-3">
                              {{ alumnoActivo.descuento_nombre }} 
                              (Ahorro: ${{ Number(alumnoActivo.monto_descuento).toLocaleString('es-MX') }} MXN)
                            </span>
                          </div>
                        </campo-field>
                      </div>
                    </seccion-card>

                  </div>
                </v-window-item>

                <!-- ── TAB: PLAN DE PAGOS ── -->
                <v-window-item value="plan">
                  <div class="expediente-body">

                    <!-- Resumen financiero -->
                    <div class="plan-resumen mb-6">
                      <div class="plan-resumen__item">
                        <span class="pr-label">Saldo a financiar</span>
                        <span class="pr-valor text-gray-800">${{ Number(saldoAlumno).toLocaleString('es-MX') }} MXN</span>
                      </div>
                      <div class="plan-resumen__item">
                        <span class="pr-label">Total en plan</span>
                        <span class="pr-valor" :class="Math.abs(totalPlan - saldoAlumno) < 1 ? 'text-green-600' : 'text-red-500'">
                          ${{ Number(totalPlan).toLocaleString('es-MX') }} MXN
                        </span>
                      </div>
                      <div class="plan-resumen__item">
                        <span class="pr-label">Cuotas</span>
                        <span class="pr-valor text-indigo-700">{{ planEditable.length }}</span>
                      </div>
                      <div class="plan-resumen__item">
                        <span class="pr-label">Duración diplomado</span>
                        <span class="pr-valor text-gray-700">{{ alumnoActivo.duracion_mes ?? '—' }} mes(es)</span>
                      </div>
                    </div>

                    <!-- Barra de herramientas del plan -->
                    <div class="plan-toolbar mb-4" v-if="editandoPlan && puedeProgramarPlan">
                      <v-btn size="small" variant="tonal" color="indigo" prepend-icon="mdi-plus-circle" @click="agregarCuota">
                        Agregar cuota
                      </v-btn>
                      <v-btn size="small" variant="tonal" color="orange" prepend-icon="mdi-refresh" @click="regenerarPlanAuto">
                        Regenerar automático
                      </v-btn>
                    </div>

                    <!-- Sin plan -->
                    <div v-if="planEditable.length === 0" class="plan-vacio">
                      <v-icon size="48" color="indigo-lighten-3">mdi-calendar-blank</v-icon>
                      <p class="mt-3 text-sm text-gray-400 text-center">
                        Este alumno no tiene un plan de pagos configurado.
                        <template v-if="puedeProgramarPlan">
                          <br><v-btn class="mt-2" size="small" color="indigo" variant="tonal" prepend-icon="mdi-calendar-plus" @click="editandoPlan = true; regenerarPlanAuto()">Generar plan automático</v-btn>
                        </template>
                      </p>
                    </div>

                    <!-- Tabla de cuotas -->
                    <template v-else>
                      <div class="cuotas-tabla-wrap">
                        <div class="cuotas-header">
                          <span>#</span>
                          <span>Fecha de Pago</span>
                          <span>Monto</span>
                          <span>Descripción</span>
                          <span>Estado</span>
                          <span v-if="editandoPlan && puedeProgramarPlan"></span>
                        </div>

                        <div
                          v-for="(cuota, idx) in planEditable"
                          :key="idx"
                          class="cuota-fila"
                          :class="{
                            'cuota-fila--pagada' : cuota.estado === 'pagado',
                            'cuota-fila--vencida': cuota.estado === 'vencido',
                          }"
                        >
                          <div class="cuota-num">{{ cuota.numero ?? idx + 1 }}</div>

                          <div>
                            <v-text-field v-if="editandoPlan && puedeProgramarPlan" v-model="cuota.fecha" type="date" variant="outlined" density="compact" hide-details />
                            <span v-else class="font-mono text-sm text-gray-700">{{ cuota.fecha }}</span>
                          </div>

                          <div>
                            <v-text-field v-if="editandoPlan && puedeProgramarPlan" v-model.number="cuota.monto" type="number" prefix="$" variant="outlined" density="compact" hide-details />
                            <span v-else class="font-bold text-sm text-green-700">${{ Number(cuota.monto).toLocaleString('es-MX') }}</span>
                          </div>

                          <div>
                            <v-text-field v-if="editandoPlan && puedeProgramarPlan" v-model="cuota.descripcion" variant="outlined" density="compact" hide-details />
                            <span v-else class="text-sm text-gray-600">{{ cuota.descripcion }}</span>
                          </div>

                          <div>
                            <v-chip :color="colorEstado(cuota.estado)" size="x-small" variant="flat" :prepend-icon="iconEstado(cuota.estado)">
                              {{ cuota.estado }}
                            </v-chip>
                          </div>

                          <div v-if="editandoPlan && puedeProgramarPlan">
                            <v-btn icon="mdi-trash-can-outline" size="x-small" variant="text" color="red" @click="quitarCuota(idx)" />
                          </div>
                        </div>
                      </div>

                      <v-alert
                        v-if="saldoAlumno > 0 && Math.abs(totalPlan - saldoAlumno) > 1"
                        type="warning" variant="tonal" density="compact" class="mt-4" icon="mdi-alert"
                      >
                        El total del plan no coincide con el saldo pendiente. Diferencia: ${{ Math.abs(totalPlan - saldoAlumno).toLocaleString('es-MX') }} MXN.
                      </v-alert>

                      <v-alert
                        v-if="!puedeProgramarPlan"
                        type="info" variant="tonal" density="compact" class="mt-4" icon="mdi-lock-outline"
                      >
                        No tienes permiso para modificar el plan de pagos. Contacta a un Administrador o TI.
                      </v-alert>
                    </template>

                  </div>
                </v-window-item>
              </v-window>

            </template>
          </div>
        </div>

      </div>
    </div>

    <!-- Snackbar -->
    <v-snackbar v-model="snackbar" :color="snackColor" location="bottom right" :timeout="3800" rounded="xl">
      <v-icon class="mr-2">{{ snackColor === 'success' ? 'mdi-check-circle' : 'mdi-alert-circle' }}</v-icon>
      {{ snackMsg }}
    </v-snackbar>

  </AuthenticatedLayout>
</template>

<style scoped>
/* ── Layout ── */
.layout-main {
    display: grid;
    grid-template-columns: 340px 1fr;
    gap: 20px;
    align-items: start;
}
@media (max-width: 900px) {
    .layout-main { grid-template-columns: 1fr; }
}

/* ── Header icon ── */
.header-icon-wrap {
    width: 52px; height: 52px;
    border-radius: 14px;
    background: linear-gradient(135deg, #4338ca, #7c3aed);
    display: flex; align-items: center; justify-content: center;
    box-shadow: 0 4px 14px rgba(79,70,229,0.4);
}

/* ── Panel búsqueda ── */
.panel-busqueda {
    display: flex;
    flex-direction: column;
    gap: 12px;
    position: sticky;
    top: 80px;
}

.search-box { }

.resultados-wrap {
    background: white;
    border-radius: 16px;
    border: 1px solid #e5e7eb;
    padding: 12px;
    min-height: 300px;
    max-height: calc(100vh - 240px);
    overflow-y: auto;
    box-shadow: 0 2px 10px rgba(0,0,0,0.06);
}
.resultados-wrap::-webkit-scrollbar { width: 5px; }
.resultados-wrap::-webkit-scrollbar-thumb { background: #c7d2fe; border-radius: 4px; }

/* ── Tarjeta alumno ── */
.tarjeta-alumno {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 10px 12px;
    border-radius: 12px;
    border: 1.5px solid transparent;
    cursor: pointer;
    transition: all 0.15s ease;
}
.tarjeta-alumno:hover { border-color: #c7d2fe; background: #eef2ff; }
.tarjeta-alumno--activa { border-color: #6366f1 !important; background: #eef2ff !important; box-shadow: 0 0 0 3px #c7d2fe; }

/* ── Estado vacío ── */
.estado-vacio {
    display: flex; flex-direction: column; align-items: center;
    justify-content: center; padding: 40px 16px;
}

/* ── Panel expediente ── */
.panel-expediente {
    background: white;
    border-radius: 20px;
    border: 1px solid #e5e7eb;
    overflow: hidden;
    box-shadow: 0 4px 20px rgba(0,0,0,0.08);
    min-height: 500px;
}

/* Placeholder sin alumno */
.expediente-vacio {
    display: flex; align-items: center; justify-content: center;
    height: 100%; min-height: 500px;
}
.expediente-vacio__contenido {
    display: flex; flex-direction: column; align-items: center;
    padding: 40px;
}

/* ── Header del expediente ── */
.expediente-header {
    background: linear-gradient(135deg, #3730a3 0%, #6d28d9 100%);
    padding: 20px 24px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
    flex-wrap: wrap;
}

/* ── Cuerpo del expediente ── */
.expediente-body {
    padding: 24px;
    background: #f9fafb;
    min-height: 400px;
}

/* ── Secciones de campos ── */
.seccion-titulo {
    font-size: 0.8rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.07em;
    color: #6b7280;
    display: flex;
    align-items: center;
    gap: 6px;
    margin-bottom: 14px;
    padding-bottom: 8px;
    border-bottom: 1.5px solid #e5e7eb;
}
.bg-seccion {
    background: white;
    border-radius: 14px;
    border: 1px solid #e5e7eb;
    padding: 16px 18px;
    margin-bottom: 16px;
}

.campos-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 14px;
}
@media (max-width: 600px) { .campos-grid { grid-template-columns: 1fr; } }

.campo-label {
    font-size: 0.7rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.06em;
    color: #9ca3af;
    display: block;
    margin-bottom: 3px;
}
.campo-valor {
    font-size: 0.9rem;
    color: #111827;
    display: block;
}

/* ── Plan de pagos ── */
.plan-resumen {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(160px, 1fr));
    gap: 10px;
    background: white;
    border-radius: 14px;
    border: 1px solid #e5e7eb;
    padding: 14px 18px;
}
.plan-resumen__item { display: flex; flex-direction: column; gap: 2px; }
.pr-label { font-size: 0.7rem; font-weight: 600; text-transform: uppercase; letter-spacing: 0.06em; color: #9ca3af; }
.pr-valor { font-size: 0.95rem; font-weight: 700; }

.plan-toolbar { display: flex; gap: 8px; flex-wrap: wrap; }

.plan-vacio {
    display: flex; flex-direction: column; align-items: center;
    justify-content: center; padding: 40px; background: white;
    border-radius: 14px; border: 1.5px dashed #c7d2fe;
}

/* ── Tabla cuotas ── */
.cuotas-tabla-wrap {
    border: 1px solid #e5e7eb;
    border-radius: 14px;
    overflow: hidden;
    background: white;
}
.cuotas-header {
    display: grid;
    grid-template-columns: 40px 1fr 1fr 1.5fr 90px 36px;
    gap: 8px;
    padding: 10px 16px;
    background: #f3f4f6;
    font-size: 0.7rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.06em;
    color: #6b7280;
    border-bottom: 1px solid #e5e7eb;
}
.cuota-fila {
    display: grid;
    grid-template-columns: 40px 1fr 1fr 1.5fr 90px 36px;
    gap: 8px;
    align-items: center;
    padding: 11px 16px;
    border-bottom: 1px solid #f3f4f6;
    transition: background 0.12s;
}
.cuota-fila:last-child { border-bottom: none; }
.cuota-fila:hover { background: #f9fafb; }
.cuota-fila--pagada { background: #f0fdf4 !important; }
.cuota-fila--vencida { background: #fef2f2 !important; }
.cuota-num {
    width: 28px; height: 28px;
    border-radius: 50%;
    background: #e0e7ff; color: #4f46e5;
    font-size: 0.78rem; font-weight: 700;
    display: flex; align-items: center; justify-content: center;
}
</style>
