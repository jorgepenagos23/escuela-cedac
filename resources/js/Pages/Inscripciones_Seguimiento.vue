<script setup>
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import { Head, usePage } from "@inertiajs/vue3";
import axios from 'axios';
import swal from 'sweetalert';
import { ref, computed, onMounted, watch } from 'vue';
import estadosMunicipiosData from '@/estados-municipios.json';

const page     = usePage();
const authUser = page.props.auth.user;

// ── Permisos desde Spatie (compartidos via HandleInertiaRequests) ─────────────
const puedeProgramarPlan = computed(() =>
    authUser?.permissions?.includes('agregar_descuento') ||   // re-usa el permiso existente
    authUser?.permissions?.includes('editar_pagos_manual') ||
    authUser?.roles?.includes('TI') ||
    authUser?.roles?.includes('Administrador')
);

// Estados
const isSubmitted           = ref(false);
const isEditing             = ref(true);
const idInscripcionGenerada = ref(null);
const costoDiplomadoRef     = ref(0);
const numMensualidades      = ref(5); // por defecto, se sobreescribe al elegir diplomado

// ── Formulario maestro ────────────────────────────────────────────────────────
const form = ref({
    nombre_alumno:                '',
    celular:                      '',
    adicional:                    '',
    selectedTitular:              null,
    monto_inscripcion:            '',
    fecha_inscripcion:            '',
    fecha_primer_pago_colegiatura:'',
    seleccionGrupo:               null,
    seleccionDiplomado:           null,
    selectedTutor:                null,
    asesor:                       authUser.id,
    correo:                       '',
    curp:                         '',
    nombre_emergencia:            '',
    parentesco_emergencia:        '',
    estado:                       '',
    municipio:                    '',
    direccion_completa:           '',
    metodo_pago_inscripcion:      '',
    descuento_id:                 null,
});

const metodosPago = ['Efectivo', 'Transferencia Bancaria', 'Depósito en OXXO', 'Tarjeta de Crédito/Débito', 'Paypal'];

// Catálogos
const cuentaDeposito = ref([]);
const Grupos         = ref([]);
const asesores       = ref([]);
const descuentosVigentes = ref([]);

const estados = computed(() => Object.keys(estadosMunicipiosData));
const municipiosDelEstado = computed(() => {
    if (form.value.estado && estadosMunicipiosData[form.value.estado]) {
        return estadosMunicipiosData[form.value.estado];
    }
    return [];
});

watch(() => form.value.estado, (newVal, oldVal) => {
    if (oldVal && isEditing.value) {
        form.value.municipio = '';
    }
});

// ── Buscador de alumnos (Re-inscripciones) ────────────────────────────────────
const searchingAlumnos = ref(false);
const searchAlumnosQuery = ref('');
const alumnosEncontrados = ref([]);
const alumnoBuscado = ref(null);
const tipoIngreso = ref('nuevo');

let debounceTimeout;
const onSearchAlumnosChange = (val) => {
    if (!val || val.length < 3) {
        alumnosEncontrados.value = [];
        return;
    }
    searchAlumnosQuery.value = val;
    clearTimeout(debounceTimeout);
    debounceTimeout = setTimeout(async () => {
        searchingAlumnos.value = true;
        try {
            const { data } = await axios.get('/api/v1/alumnos/buscar', { params: { q: searchAlumnosQuery.value } });
            alumnosEncontrados.value = data.alumnos || [];
        } catch (e) {
            console.error('Error buscando alumno:', e);
        } finally {
            searchingAlumnos.value = false;
        }
    }, 400);
};

const seleccionarAlumnoExistente = (alumno) => {
    if (!alumno) return;
    form.value.nombre_alumno = alumno.nombre_alumno || '';
    form.value.celular = alumno.celular || '';
    form.value.adicional = alumno.adicional || '';
    form.value.correo = alumno.correo || '';
    form.value.curp = alumno.curp || '';
    form.value.nombre_emergencia = alumno.nombre_emergencia || '';
    form.value.parentesco_emergencia = alumno.parentesco_emergencia || '';
    if (alumno.estado) form.value.estado = alumno.estado;
    // Forzar actualización diferida para Municipio
    setTimeout(() => {
        if (alumno.municipio) form.value.municipio = alumno.municipio;
    }, 100);
    form.value.direccion_completa = alumno.direccion_completa || '';
};

const cambiarTipoIngreso = (tipo) => {
    tipoIngreso.value = tipo;
    if (tipo === 'nuevo') {
        const resetFields = ['nombre_alumno', 'celular', 'adicional', 'correo', 'curp', 'nombre_emergencia', 'parentesco_emergencia', 'estado', 'municipio', 'direccion_completa'];
        resetFields.forEach(f => form.value[f] = '');
        alumnoBuscado.value = null;
        alumnosEncontrados.value = [];
    }
};

// ── Plan de pagos ─────────────────────────────────────────────────────────────
const planPagos         = ref([]);   // [{ numero, fecha, monto, descripcion, estado }]
const editandoPlan      = ref(false);
const guardandoPlan     = ref(false);
const planModificado    = ref(false);
const snackbar          = ref(false);
const snackMsg          = ref('');
const snackColor        = ref('success');

const mostrarSnack = (msg, color = 'success') => {
    snackMsg.value = msg; snackColor.value = color; snackbar.value = true;
};

// Genera el plan automáticamente basado en el saldo y la fecha del primer pago
const generarPlanAutomatico = (saldo, fechaPrimerPago, numCuotas) => {
    const total = parseFloat(saldo) || 0;
    if (!total || !fechaPrimerPago || !numCuotas) return;

    const montoCuota     = Math.floor((total / numCuotas) * 100) / 100;
    const montoUltima    = Math.round((total - montoCuota * (numCuotas - 1)) * 100) / 100;

    const fechaBase = new Date(fechaPrimerPago + 'T12:00:00'); // evitar offset
    planPagos.value = Array.from({ length: numCuotas }, (_, i) => {
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
    planModificado.value = false;
};

const totalPlan    = computed(() => planPagos.value.reduce((a, c) => a + parseFloat(c.monto || 0), 0));
const montoDescuentoCalculado = computed(() => {
    const g = Grupos.value.find(g => g.id === form.value.seleccionGrupo);
    const costo = g ? parseFloat(g.costo_total) : 0;
    if (form.value.descuento_id) {
        const d = descuentosVigentes.value.find(x => x.id === form.value.descuento_id);
        if (d) {
            return d.tipo === 'porcentaje' ? Math.round(costo * d.valor / 100) : parseFloat(d.valor);
        }
    }
    return 0;
});

const saldoReal    = computed(() => {
    const g = Grupos.value.find(g => g.id === form.value.seleccionGrupo);
    const costo = g ? parseFloat(g.costo_total) : 0;
    const ins   = parseFloat(form.value.monto_inscripcion) || 0;
    return Math.max(costo - ins - montoDescuentoCalculado.value, 0);
});

const agregarCuota = () => {
    const ultima = planPagos.value[planPagos.value.length - 1];
    const f = ultima ? new Date(ultima.fecha + 'T12:00:00') : new Date();
    f.setMonth(f.getMonth() + 1);
    planPagos.value.push({
        numero:      planPagos.value.length + 1,
        fecha:       f.toISOString().split('T')[0],
        monto:       0,
        descripcion: `Mensualidad ${planPagos.value.length + 1}`,
        estado:      'pendiente',
    });
    planModificado.value = true;
};

const quitarCuota = (idx) => { planPagos.value.splice(idx, 1); planModificado.value = true; };

// Recalcular plan cuando cambian monto inscripción, fecha primer pago o descuento
watch([
    () => form.value.monto_inscripcion, 
    () => form.value.fecha_primer_pago_colegiatura,
    () => form.value.descuento_id
], () => {
    if (!isSubmitted.value && form.value.fecha_primer_pago_colegiatura) {
        generarPlanAutomatico(saldoReal.value, form.value.fecha_primer_pago_colegiatura, numMensualidades.value);
    }
});

watch(() => form.value.selectedTutor, () => {
    cargarDescuentos();
});

// Guardar plan modificado
const guardarPlanModificado = async () => {
    if (!idInscripcionGenerada.value) return;
    guardandoPlan.value = true;
    try {
        await axios.post(`/api/v1/alumno/${idInscripcionGenerada.value}/plan-pagos`, {
            plan: planPagos.value,
        });
        mostrarSnack('Plan de pagos actualizado correctamente.', 'success');
        planModificado.value = false;
        editandoPlan.value   = false;
    } catch (e) {
        mostrarSnack(e.response?.data?.message ?? 'Error al guardar el plan.', 'error');
    } finally {
        guardandoPlan.value = false;
    }
};

// Regenerar plan al 100% desde los datos actuales
const regenerarPlan = () => {
    generarPlanAutomatico(saldoReal.value, form.value.fecha_primer_pago_colegiatura, numMensualidades.value);
    if (idInscripcionGenerada.value) guardarPlanModificado();
};

// ── Formulario principal ──────────────────────────────────────────────────────
const limpiarFormulario = () => {
    form.value = {
        nombre_alumno:                '',
        celular:                      '',
        adicional:                    '',
        selectedTitular:              null,
        monto_inscripcion:            '',
        fecha_inscripcion:            form.value.fecha_inscripcion,
        fecha_primer_pago_colegiatura:'',
        seleccionGrupo:               null,
        seleccionDiplomado:           null,
        selectedTutor:                null,
        asesor:                       authUser.id,
        correo:                       '',
        curp:                         '',
        nombre_emergencia:            '',
        parentesco_emergencia:        '',
        estado:                       '',
        municipio:                    '',
        direccion_completa:           '',
        metodo_pago_inscripcion:      '',
        descuento_id:                 null,
    };
    planPagos.value      = [];
    isSubmitted.value    = false;
    isEditing.value      = true;
    editandoPlan.value   = false;
    planModificado.value = false;
    tipoIngreso.value    = 'nuevo';
    alumnoBuscado.value  = null;
    alumnosEncontrados.value = [];
};

const habilitarEdicion = () => { isEditing.value = true; };

const actualizarDiplomado = () => {
    const grupoSeleccionado = Grupos.value.find(g => g.id === form.value.seleccionGrupo);
    form.value.seleccionDiplomado = grupoSeleccionado ? grupoSeleccionado.diplomado_id : null;
    
    // Auto-asignar el tutor de la campaña si está disponible
    if (grupoSeleccionado && grupoSeleccionado.tutor_id) {
        form.value.selectedTutor = grupoSeleccionado.tutor_id;
    }

    costoDiplomadoRef.value       = grupoSeleccionado ? grupoSeleccionado.costo_total  : 0;
    numMensualidades.value        = grupoSeleccionado?.duracion_mes > 0 ? grupoSeleccionado.duracion_mes : 5;

    // Recalcular vista previa del plan
    if (form.value.fecha_primer_pago_colegiatura) {
        generarPlanAutomatico(saldoReal.value, form.value.fecha_primer_pago_colegiatura, numMensualidades.value);
    }
    
    cargarDescuentos();
};

const cargarDescuentos = () => {
    const params = {
        diplomado_id: form.value.seleccionDiplomado,
        tutor_id:     form.value.selectedTutor
    };
    axios.get("/api/v1/descuentos/vigentes", { params }).then(res => {
        descuentosVigentes.value = res.data.descuentos;
        // Si el descuento actual ya no es válido, lo quitamos
        if (form.value.descuento_id && !descuentosVigentes.value.find(d => d.id === form.value.descuento_id)) {
            form.value.descuento_id = null;
        }
    });
};

const setFechaActual = () => {
    const today = new Date();
    form.value.fecha_inscripcion =
        `${today.getFullYear()}-${String(today.getMonth()+1).padStart(2,'0')}-${String(today.getDate()).padStart(2,'0')}`;
};

const EnviarInscripcion = () => {
    const inscripcion = {
        fecha_primer_pago_colegiatura: form.value.fecha_primer_pago_colegiatura,
        fecha_inscripcion:             form.value.fecha_inscripcion,
        nombre_alumno:                 form.value.nombre_alumno,
        monto_inscripcion:             form.value.monto_inscripcion,
        cuentadeposito:                form.value.selectedTitular,
        grupo_campa:                   form.value.seleccionGrupo,
        diplomado_id:                  form.value.seleccionDiplomado,
        celular:                       form.value.celular,
        adicional:                     form.value.adicional,
        tutor:                         form.value.selectedTutor,
        asesor:                        authUser.id,
        correo:                        form.value.correo,
        curp:                          form.value.curp,
        nombre_emergencia:             form.value.nombre_emergencia,
        parentesco_emergencia:         form.value.parentesco_emergencia,
        estado:                        form.value.estado,
        municipio:                     form.value.municipio,
        direccion_completa:            form.value.direccion_completa,
        metodo_pago_inscripcion:       form.value.metodo_pago_inscripcion,
        descuento_id:                  form.value.descuento_id,
    };

    axios.post("/api/v1/inscripcion/crear", inscripcion)
        .then(res => {
            idInscripcionGenerada.value = res.data.inscripcion.id;

            // Cargar el plan generado por el servidor
            if (res.data.plan_pagos && res.data.plan_pagos.length > 0) {
                planPagos.value = res.data.plan_pagos;
            }

            swal("¡Inscripción Guardada!", "Los datos maestros y el plan de pagos han sido generados correctamente.", "success");
            isSubmitted.value = true;
            isEditing.value   = false;
        })
        .catch(err => {
            console.error(err);
            swal("Error", "Verifica que todos los campos obligatorios estén llenos.", "error");
        });
};

const imprimirFicha = () => {
    if (idInscripcionGenerada.value) {
        window.open('/seguimiento/inscripciones/comprobante/' + idInscripcionGenerada.value, '_blank');
    }
};

const obtenerGrupos = () => {
    axios.get("/api/v1/grupos/listar").then(res => Grupos.value = res.data.Grupos);
};
const obtenerNumeroCuenta = () => {
    axios.get("/api/v1/cuentadeposito/index/2024/numero").then(res => cuentaDeposito.value = res.data.cuentaDeposito);
};
const aseoresLista = () => {
    axios.get('/api/v1/listar/asesores').then(res => asesores.value = res.data.asesores);
};

onMounted(() => {
    setFechaActual();
    obtenerGrupos();
    obtenerNumeroCuenta();
    aseoresLista();
});

// Color de la barra de progreso del plan
const progresoColor = computed(() => {
    if (!saldoReal.value) return 'indigo';
    const diff = Math.abs(totalPlan.value - saldoReal.value);
    return diff < 1 ? 'success' : totalPlan.value > saldoReal.value ? 'error' : 'warning';
});

const progresoPorc = computed(() => {
    if (!saldoReal.value) return 0;
    return Math.min((totalPlan.value / saldoReal.value) * 100, 100);
});

const colorEstadoCuota = (estado) => {
    const map = { pendiente: 'grey', pagado: 'success', vencido: 'error' };
    return map[estado] ?? 'grey';
};
const iconEstadoCuota = (estado) => {
    const map = { pendiente: 'mdi-clock-outline', pagado: 'mdi-check-circle', vencido: 'mdi-alert-circle' };
    return map[estado] ?? 'mdi-clock-outline';
};
</script>

<template>
    <Head title="Panel de Inscripciones" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex justify-between items-center bg-transparent">
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">Módulo Corporativo de Admisiones</h2>
                <v-btn v-if="isSubmitted" @click="imprimirFicha" color="primary" variant="flat" prepend-icon="mdi-file-pdf-box">Generar Ficha PDF</v-btn>
            </div>
        </template>

        <div class="py-10">
            <div class="max-w-6xl mx-auto sm:px-6 lg:px-8 space-y-6">

                <!-- ── Card del Expediente de Admisión ── -->
                <v-card elevation="0" rounded="xl" class="border border-gray-200 shadow-sm overflow-hidden">
                    <div class="bg-indigo-900 px-6 py-4 flex justify-between items-center">
                        <div>
                            <h3 class="text-white text-lg font-medium flex items-center">
                                <v-icon class="mr-2" color="white">mdi-account-student</v-icon>
                                Expediente de Admisión
                            </h3>
                            <p class="text-indigo-200 text-sm mt-1">Llenado de datos maestros de alumno y finanzas.</p>
                        </div>
                        <v-chip v-if="!isEditing && isSubmitted" color="success" variant="flat" prepend-icon="mdi-lock">
                            Expediente Bloqueado
                        </v-chip>
                        <v-chip v-else color="warning" variant="flat" prepend-icon="mdi-pencil">
                            En Edición
                        </v-chip>
                    </div>

                    <form @submit.prevent="EnviarInscripcion" class="p-6 bg-gray-50">
                        <!-- SECCIÓN DE TIPO DE INGRESO (Buscador) -->
                        <div v-if="isEditing" class="mb-6 p-4 rounded-lg border-2 border-indigo-100 bg-indigo-50/50">
                            <div class="flex flex-col sm:flex-row items-center gap-4">
                                <span class="text-sm font-bold text-indigo-900 uppercase tracking-wide mr-2">Tipo de Ingreso:</span>
                                <v-btn-toggle v-model="tipoIngreso" color="indigo-darken-3" variant="outlined" divided mandatory @update:modelValue="cambiarTipoIngreso">
                                    <v-btn value="nuevo" prepend-icon="mdi-account-plus">Alumno Nuevo</v-btn>
                                    <v-btn value="existente" prepend-icon="mdi-account-search">Alumno Existente</v-btn>
                                </v-btn-toggle>
                            </div>

                            <v-expand-transition>
                                <div v-if="tipoIngreso === 'existente'" class="mt-4 pt-4 border-t border-indigo-100">
                                    <v-autocomplete
                                        v-model="alumnoBuscado"
                                        :items="alumnosEncontrados"
                                        item-title="nombre_alumno"
                                        item-value="id"
                                        label="Buscar por Nombre, Matrícula o CURP..."
                                        variant="solo-filled"
                                        density="comfortable"
                                        prepend-inner-icon="mdi-magnify"
                                        hide-no-data
                                        hide-details
                                        return-object
                                        @update:search="onSearchAlumnosChange"
                                        :loading="searchingAlumnos"
                                        @update:modelValue="seleccionarAlumnoExistente"
                                        placeholder="Ingresa al menos 3 caracteres para buscar"
                                    >
                                        <template v-slot:item="{ props, item }">
                                            <v-list-item v-bind="props" :title="item.raw.nombre_alumno || '—'" :subtitle="`CURP: ${item.raw.curp || 'N/A'} | Tel: ${item.raw.celular || '—'}`">
                                                <template v-slot:prepend>
                                                    <v-avatar color="indigo-lighten-4" size="36">
                                                        <v-icon color="indigo">mdi-account</v-icon>
                                                    </v-avatar>
                                                </template>
                                            </v-list-item>
                                        </template>
                                    </v-autocomplete>
                                </div>
                            </v-expand-transition>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">

                            <!-- Sección 1: Datos Generales -->
                            <v-card variant="outlined" class="bg-white border-gray-200">
                                <v-card-title class="text-base font-semibold text-gray-700 bg-gray-100/50 py-3 border-b flex items-center">
                                    <v-icon size="small" class="mr-2">mdi-card-account-details-outline</v-icon> Datos Generales
                                </v-card-title>
                                <v-card-text class="pt-4">
                                    <v-text-field v-model="form.nombre_alumno" label="Nombre del Alumno" variant="outlined" density="comfortable" :readonly="!isEditing" required />
                                    <div class="grid grid-cols-2 gap-4">
                                        <v-text-field v-model="form.celular" label="Num. Celular" variant="outlined" density="comfortable" :readonly="!isEditing" />
                                        <v-text-field v-model="form.adicional" label="Contacto Adicional" variant="outlined" density="comfortable" :readonly="!isEditing" />
                                    </div>
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                                        <v-text-field v-model="form.correo" label="Correo Electrónico" variant="outlined" density="comfortable" :readonly="!isEditing" />
                                        <v-text-field v-model="form.curp" label="CURP" variant="outlined" density="comfortable" :readonly="!isEditing" />
                                    </div>
                                    <v-text-field :value="authUser.name" label="Usuario Matriculador (Responsable)" variant="filled" density="comfortable" readonly append-inner-icon="mdi-shield-check" />
                                </v-card-text>
                            </v-card>

                            <!-- Sección 1.5: Domicilio y Emergencia -->
                            <v-card variant="outlined" class="md:col-span-2 bg-white border-gray-200">
                                <v-card-title class="text-base font-semibold text-gray-700 bg-gray-100/50 py-3 border-b flex items-center">
                                    <v-icon size="small" class="mr-2">mdi-map-marker-radius</v-icon> Datos Domiciliarios y Emergencia
                                </v-card-title>
                                <v-card-text class="pt-4">
                                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
                                        <v-autocomplete v-model="form.estado" :items="estados" label="Estado / Provincia" variant="outlined" density="comfortable" :readonly="!isEditing" />
                                        <v-autocomplete v-model="form.municipio" :items="municipiosDelEstado" label="Ciudad / Municipio" variant="outlined" density="comfortable" :readonly="!isEditing" :disabled="!form.estado" />
                                        <v-text-field v-model="form.direccion_completa" label="Dirección Completa (Calle, Col, CP)" variant="outlined" density="comfortable" :readonly="!isEditing" />
                                    </div>
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                        <v-text-field v-model="form.nombre_emergencia" label="Contacto de Emergencia" variant="outlined" density="comfortable" :readonly="!isEditing" />
                                        <v-text-field v-model="form.parentesco_emergencia" label="Parentesco (Madre, Esposo, etc)" variant="outlined" density="comfortable" :readonly="!isEditing" />
                                    </div>
                                </v-card-text>
                            </v-card>

                            <!-- Sección 2: Matrícula Académica -->
                            <v-card variant="outlined" class="bg-white border-gray-200">
                                <v-card-title class="text-base font-semibold text-gray-700 bg-gray-100/50 py-3 border-b flex items-center">
                                    <v-icon size="small" class="mr-2">mdi-school-outline</v-icon> Matrícula Académica
                                </v-card-title>
                                <v-card-text class="pt-4">
                                    <div class="relative">
                                        <v-select
                                            v-model="form.seleccionGrupo"
                                            :items="Grupos"
                                            item-title="nombre"
                                            item-value="id"
                                            label="Programa y Grupo"
                                            variant="outlined"
                                            density="comfortable"
                                            :readonly="!isEditing"
                                            @update:modelValue="actualizarDiplomado"
                                        >
                                            <template v-slot:item="{ props, item }">
                                                <v-list-item v-bind="props"
                                                    :title="`Diplomado: ${item.raw.nombre}`"
                                                    :subtitle="`Grupo: ${item.raw.grupo} | Costo: $${Number(item.raw.costo_total).toLocaleString()} | ${item.raw.duracion_mes ?? 5} meses`"
                                                />
                                            </template>
                                            <template v-slot:selection="{ item }">
                                                <span>{{ item.raw.nombre }} (Gpo: {{ item.raw.grupo }})</span>
                                            </template>
                                        </v-select>

                                        <div v-if="costoDiplomadoRef > 0 && isEditing" class="text-indigo-800 bg-indigo-50 border border-indigo-200 rounded p-2 text-sm mt-[-10px] mb-4 flex items-center justify-between">
                                            <span><strong><v-icon size="small" color="indigo" class="mr-1">mdi-tag-outline</v-icon> Costo Oficial del Programa:</strong></span>
                                            <span class="font-black text-lg">${{ Number(costoDiplomadoRef).toLocaleString() }} MXN</span>
                                        </div>
                                    </div>
                                    <v-select
                                        v-model="form.selectedTutor"
                                        :items="asesores"
                                        item-title="name"
                                        item-value="id"
                                        label="Tutor Asignado"
                                        variant="outlined"
                                        density="comfortable"
                                        :readonly="!isEditing"
                                    />

                                    <v-select
                                        v-if="descuentosVigentes.length > 0 && isEditing"
                                        v-model="form.descuento_id"
                                        :items="descuentosVigentes"
                                        item-title="nombre"
                                        item-value="id"
                                        label="Aplicar Descuento de Temporada"
                                        variant="outlined"
                                        density="comfortable"
                                        clearable
                                        prepend-inner-icon="mdi-tag-heart"
                                        color="deep-purple-darken-2"
                                        hint="El descuento se aplicará sobre el costo total del programa"
                                        persistent-hint
                                    >
                                        <template v-slot:item="{ props, item }">
                                            <v-list-item v-bind="props" :title="item.raw.nombre" :subtitle="`Valor: ${item.raw.etiqueta} | Aplica: ${item.raw.aplica_a}`" />
                                        </template>
                                    </v-select>
                                </v-card-text>
                            </v-card>

                            <!-- Sección 3: Disposiciones Financieras -->
                            <v-card variant="outlined" class="md:col-span-2 bg-white border-gray-200">
                                <v-card-title class="text-base font-semibold text-gray-700 bg-gray-100/50 py-3 border-b flex items-center">
                                    <v-icon size="small" class="mr-2">mdi-cash-multiple</v-icon> Disposiciones Financieras
                                </v-card-title>
                                <v-card-text class="pt-4">
                                    <div class="grid grid-cols-1 md:grid-cols-5 gap-4">
                                        <v-select
                                            v-model="form.selectedTitular"
                                            :items="cuentaDeposito"
                                            item-title="titular"
                                            item-value="id"
                                            label="Cuenta Receptora"
                                            variant="outlined"
                                            density="comfortable"
                                            :readonly="!isEditing"
                                        >
                                            <template v-slot:item="{ props, item }">
                                                <v-list-item v-bind="props" :title="item.raw.banco" :subtitle="`${item.raw.titular} | C: ${item.raw.CLABE}`" />
                                            </template>
                                        </v-select>

                                        <v-select
                                            v-model="form.metodo_pago_inscripcion"
                                            :items="metodosPago"
                                            label="Método de Pago"
                                            variant="outlined"
                                            density="comfortable"
                                            :readonly="!isEditing"
                                        />

                                        <v-text-field
                                            v-model="form.monto_inscripcion"
                                            label="Monto Inscripción"
                                            variant="outlined"
                                            density="comfortable"
                                            prefix="$"
                                            type="number"
                                            :readonly="!isEditing"
                                        />

                                        <v-text-field
                                            v-model="form.fecha_inscripcion"
                                            label="Fecha Alta"
                                            variant="filled"
                                            density="comfortable"
                                            type="date"
                                            readonly
                                        />

                                        <v-text-field
                                            v-model="form.fecha_primer_pago_colegiatura"
                                            label="1er Pago Colegiatura"
                                            variant="outlined"
                                            density="comfortable"
                                            type="date"
                                            :readonly="!isEditing"
                                        />
                                    </div>
                                </v-card-text>
                            </v-card>
                        </div>

                        <!-- Barra de Botones del Formulario -->
                        <div class="mt-8 flex justify-between items-center bg-gray-200/50 p-4 rounded-lg">
                            <div>
                                <v-btn v-if="!isEditing" @click="limpiarFormulario" color="indigo-darken-3" variant="text" prepend-icon="mdi-plus">Registrar Otra Matrícula</v-btn>
                            </div>
                            <div class="space-x-4">
                                <v-btn v-if="isEditing" @click="limpiarFormulario" variant="tonal" color="grey-darken-2">Limpiar Datos</v-btn>
                                <v-btn v-if="!isEditing && authUser.roles?.includes('TI')" @click="habilitarEdicion" color="warning" variant="elevated" prepend-icon="mdi-pencil-lock-open">Desbloquear Edición (TI)</v-btn>
                                <v-btn v-if="isEditing" type="submit" color="indigo-darken-3" variant="elevated" prepend-icon="mdi-content-save-check">Guardar Inscripción Estricta</v-btn>
                            </div>
                        </div>
                    </form>
                </v-card>

                <!-- ══════════════════════════════════════════════════════════════ -->
                <!-- ── SECCIÓN ESQUEMA DE PLAN DE PAGOS ── -->
                <!-- ══════════════════════════════════════════════════════════════ -->
                <v-card
                    elevation="0"
                    rounded="xl"
                    class="border overflow-hidden shadow-sm"
                    :class="isSubmitted ? 'border-indigo-200' : 'border-gray-200 opacity-80'"
                >
                    <!-- Header del panel -->
                    <div class="plan-header">
                        <div class="flex items-center gap-3">
                            <div class="plan-header__icon">
                                <v-icon color="white" size="22">mdi-calendar-multiselect</v-icon>
                            </div>
                            <div>
                                <h3 class="plan-header__title">Esquema de Plan de Pagos</h3>
                                <p class="plan-header__sub">
                                    Mensualidades generadas automáticamente desde la fecha del 1er pago.
                                    <span v-if="isSubmitted"> · Folio: <strong>#{{ idInscripcionGenerada }}</strong></span>
                                </p>
                            </div>
                        </div>

                        <!-- Acciones del plan -->
                        <div class="flex items-center gap-2" v-if="isSubmitted">
                            <!-- Sólo quien tiene permiso puede editar -->
                            <v-tooltip v-if="!puedeProgramarPlan" location="top" text="No tienes permiso para editar el plan de pagos.">
                                <template #activator="{ props }">
                                    <v-icon v-bind="props" color="white" size="18">mdi-lock</v-icon>
                                </template>
                            </v-tooltip>

                            <v-btn
                                v-if="puedeProgramarPlan && !editandoPlan"
                                size="small"
                                color="white"
                                variant="outlined"
                                prepend-icon="mdi-pencil"
                                @click="editandoPlan = true"
                            >
                                Editar Plan
                            </v-btn>
                            <v-btn
                                v-if="puedeProgramarPlan && !editandoPlan"
                                size="small"
                                color="white"
                                variant="text"
                                prepend-icon="mdi-refresh"
                                @click="regenerarPlan"
                            >
                                Regenerar
                            </v-btn>
                            <!-- Guardar cambios del plan -->
                            <template v-if="editandoPlan">
                                <v-btn
                                    size="small"
                                    color="success"
                                    variant="flat"
                                    prepend-icon="mdi-content-save"
                                    :loading="guardandoPlan"
                                    @click="guardarPlanModificado"
                                >
                                    Guardar Plan
                                </v-btn>
                                <v-btn
                                    size="small"
                                    color="white"
                                    variant="text"
                                    @click="editandoPlan = false"
                                >
                                    Cancelar
                                </v-btn>
                            </template>
                        </div>
                    </div>

                    <div class="p-6 bg-white">

                        <!-- Si aún no se ha guardado la inscripción → vista previa -->
                        <div v-if="!isSubmitted && planPagos.length === 0" class="plan-preview-empty">
                            <v-icon size="48" color="indigo-lighten-3">mdi-calendar-clock</v-icon>
                            <p class="mt-3 text-gray-500 text-sm text-center">
                                El esquema de pagos se generará automáticamente al ingresar el <strong>costo del diplomado</strong>,
                                el <strong>monto de inscripción</strong> y la <strong>fecha del 1er pago</strong>.
                            </p>
                        </div>

                        <!-- Vista previa antes de guardar (sin folio) -->
                        <template v-else-if="!isSubmitted && planPagos.length > 0">
                            <div class="plan-preview-badge mb-4">
                                <v-icon size="16" class="mr-1">mdi-eye-outline</v-icon>
                                Vista previa del plan — se guardará automáticamente al registrar la inscripción
                            </div>
                            <plan-tabla :plan="planPagos" :editable="false" :saldo-real="saldoReal" />
                        </template>

                        <!-- Vista final (inscripción guardada) -->
                        <template v-else-if="isSubmitted">
                            <!-- Resumen financiero -->
                            <div class="plan-resumen">
                                <div class="plan-resumen__item">
                                    <span class="plan-resumen__label">Saldo a financiar</span>
                                    <span class="plan-resumen__valor text-gray-800">${{ Number(saldoReal || totalPlan).toLocaleString('es-MX') }} MXN</span>
                                </div>
                                <div v-if="montoDescuentoCalculado > 0" class="plan-resumen__item">
                                    <span class="plan-resumen__label">Descuento aplicado</span>
                                    <span class="plan-resumen__valor text-green-600">−${{ montoDescuentoCalculado.toLocaleString('es-MX') }} MXN</span>
                                </div>
                                <div class="plan-resumen__item">
                                    <span class="plan-resumen__label">Total cuotas en plan</span>
                                    <span class="plan-resumen__valor" :class="Math.abs(totalPlan - (saldoReal || totalPlan)) < 1 ? 'text-green-600' : 'text-red-500'">
                                        ${{ Number(totalPlan).toLocaleString('es-MX') }} MXN
                                    </span>
                                </div>
                                <div class="plan-resumen__item">
                                    <span class="plan-resumen__label">Número de mensualidades</span>
                                    <span class="plan-resumen__valor text-indigo-700">{{ planPagos.length }} cuotas</span>
                                </div>
                                <div class="plan-resumen__item">
                                    <span class="plan-resumen__label">Primera mensualidad</span>
                                    <span class="plan-resumen__valor text-gray-600">{{ planPagos[0]?.fecha ?? '—' }}</span>
                                </div>
                            </div>

                            <!-- Barra de cobertura -->
                            <div class="mb-5">
                                <div class="flex justify-between text-xs text-gray-500 mb-1">
                                    <span>Cobertura del plan</span>
                                    <span>{{ Math.round(progresoPorc) }}%</span>
                                </div>
                                <v-progress-linear
                                    :model-value="progresoPorc"
                                    :color="progresoColor"
                                    height="8"
                                    rounded
                                />
                            </div>

                            <!-- Tabla de cuotas -->
                            <div class="cuotas-tabla-wrap">
                                <div class="cuotas-tabla-header">
                                    <span>#</span>
                                    <span>Fecha de Pago</span>
                                    <span>Monto</span>
                                    <span>Descripción</span>
                                    <span>Estado</span>
                                    <span v-if="editandoPlan && puedeProgramarPlan"></span>
                                </div>

                                <div
                                    v-for="(cuota, idx) in planPagos"
                                    :key="idx"
                                    class="cuota-fila"
                                    :class="{
                                        'cuota-fila--pagada'  : cuota.estado === 'pagado',
                                        'cuota-fila--vencida' : cuota.estado === 'vencido',
                                    }"
                                >
                                    <!-- Número -->
                                    <div class="cuota-num-badge">{{ cuota.numero ?? idx + 1 }}</div>

                                    <!-- Fecha -->
                                    <div>
                                        <template v-if="editandoPlan && puedeProgramarPlan">
                                            <v-text-field
                                                v-model="cuota.fecha"
                                                type="date"
                                                variant="outlined"
                                                density="compact"
                                                hide-details
                                                @input="planModificado = true"
                                            />
                                        </template>
                                        <span v-else class="cuota-fecha">{{ cuota.fecha }}</span>
                                    </div>

                                    <!-- Monto -->
                                    <div>
                                        <template v-if="editandoPlan && puedeProgramarPlan">
                                            <v-text-field
                                                v-model.number="cuota.monto"
                                                type="number"
                                                prefix="$"
                                                variant="outlined"
                                                density="compact"
                                                hide-details
                                                @input="planModificado = true"
                                            />
                                        </template>
                                        <span v-else class="cuota-monto">${{ Number(cuota.monto).toLocaleString('es-MX') }} MXN</span>
                                    </div>

                                    <!-- Descripción -->
                                    <div>
                                        <template v-if="editandoPlan && puedeProgramarPlan">
                                            <v-text-field
                                                v-model="cuota.descripcion"
                                                variant="outlined"
                                                density="compact"
                                                hide-details
                                                @input="planModificado = true"
                                            />
                                        </template>
                                        <span v-else class="text-sm text-gray-600">{{ cuota.descripcion }}</span>
                                    </div>

                                    <!-- Estado -->
                                    <div>
                                        <v-chip
                                            :color="colorEstadoCuota(cuota.estado)"
                                            size="x-small"
                                            variant="flat"
                                            :prepend-icon="iconEstadoCuota(cuota.estado)"
                                        >
                                            {{ cuota.estado }}
                                        </v-chip>
                                    </div>

                                    <!-- Quitar (solo en edición) -->
                                    <div v-if="editandoPlan && puedeProgramarPlan">
                                        <v-btn icon="mdi-trash-can-outline" size="x-small" variant="text" color="red" @click="quitarCuota(idx)" />
                                    </div>
                                </div>
                            </div>

                            <!-- Agregar cuota (solo en edición) -->
                            <div v-if="editandoPlan && puedeProgramarPlan" class="mt-4 flex justify-end">
                                <v-btn
                                    prepend-icon="mdi-plus-circle"
                                    variant="tonal"
                                    color="indigo-darken-2"
                                    size="small"
                                    @click="agregarCuota"
                                >
                                    Agregar cuota
                                </v-btn>
                            </div>

                            <!-- Aviso: total excede saldo -->
                            <v-alert
                                v-if="saldoReal > 0 && Math.abs(totalPlan - saldoReal) > 1"
                                type="warning"
                                variant="tonal"
                                density="compact"
                                class="mt-4"
                                icon="mdi-alert"
                            >
                                La suma del plan (<strong>${{ Number(totalPlan).toLocaleString('es-MX') }}</strong>)
                                no coincide exactamente con el saldo a financiar (<strong>${{ Number(saldoReal).toLocaleString('es-MX') }}</strong>).
                                Ajusta los montos o regenera el plan.
                            </v-alert>

                            <!-- Aviso: sin permiso para editar -->
                            <v-alert
                                v-if="!puedeProgramarPlan"
                                type="info"
                                variant="tonal"
                                density="compact"
                                class="mt-4"
                                icon="mdi-lock-outline"
                            >
                                No tienes permiso para modificar el plan de pagos. Contacta a un Administrador o TI.
                            </v-alert>
                        </template>
                    </div>
                </v-card>

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
/* ── Header del plan ── */
.plan-header {
    background: linear-gradient(135deg, #3730a3 0%, #4f46e5 100%);
    padding: 18px 24px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
}
.plan-header__icon {
    background: rgba(255,255,255,0.15);
    border-radius: 10px;
    padding: 10px;
    display: flex;
    align-items: center;
}
.plan-header__title {
    font-size: 1rem;
    font-weight: 700;
    color: white;
    margin-bottom: 2px;
}
.plan-header__sub {
    font-size: 0.78rem;
    color: rgba(255,255,255,0.75);
}

/* ── Vista previa vacía ── */
.plan-preview-empty {
    display: flex;
    flex-direction: column;
    align-items: center;
    padding: 40px 20px;
    background: #f8f9ff;
    border-radius: 12px;
    border: 1.5px dashed #c7d2fe;
}

/* ── Badge vista previa ── */
.plan-preview-badge {
    display: inline-flex;
    align-items: center;
    font-size: 0.78rem;
    font-weight: 600;
    color: #7c3aed;
    background: #f5f3ff;
    border: 1px solid #ddd6fe;
    border-radius: 8px;
    padding: 4px 12px;
}

/* ── Resumen financiero ── */
.plan-resumen {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
    gap: 12px;
    margin-bottom: 20px;
    background: #f9fafb;
    border-radius: 14px;
    padding: 16px;
    border: 1px solid #e5e7eb;
}
.plan-resumen__item { display: flex; flex-direction: column; gap: 2px; }
.plan-resumen__label { font-size: 0.72rem; text-transform: uppercase; letter-spacing: 0.05em; color: #9ca3af; font-weight: 600; }
.plan-resumen__valor { font-size: 0.95rem; font-weight: 700; }

/* ── Tabla de cuotas ── */
.cuotas-tabla-wrap { border: 1px solid #e5e7eb; border-radius: 14px; overflow: hidden; }
.cuotas-tabla-header {
    display: grid;
    grid-template-columns: 40px 1fr 1fr 1.5fr 100px 36px;
    gap: 8px;
    padding: 10px 16px;
    background: #f3f4f6;
    font-size: 0.72rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.06em;
    color: #6b7280;
    border-bottom: 1px solid #e5e7eb;
}
.cuota-fila {
    display: grid;
    grid-template-columns: 40px 1fr 1fr 1.5fr 100px 36px;
    gap: 8px;
    align-items: center;
    padding: 12px 16px;
    border-bottom: 1px solid #f3f4f6;
    transition: background 0.15s;
}
.cuota-fila:last-child { border-bottom: none; }
.cuota-fila:hover { background: #f9fafb; }
.cuota-fila--pagada  { background: #f0fdf4 !important; }
.cuota-fila--vencida { background: #fef2f2 !important; }

.cuota-num-badge {
    width: 28px; height: 28px;
    border-radius: 50%;
    background: #e0e7ff;
    color: #4f46e5;
    font-size: 0.78rem;
    font-weight: 700;
    display: flex;
    align-items: center;
    justify-content: center;
}
.cuota-fecha { font-size: 0.85rem; color: #374151; font-family: monospace; font-weight: 600; }
.cuota-monto { font-size: 0.88rem; font-weight: 700; color: #166534; }
</style>
