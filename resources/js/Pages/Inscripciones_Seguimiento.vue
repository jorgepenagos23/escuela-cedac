<script setup>
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import { Head, usePage } from "@inertiajs/vue3";
import axios from 'axios';
import swal from 'sweetalert';
import { ref, computed, onMounted, watch, nextTick } from 'vue';
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

// ── Buscador de alumnos (Modal de Búsqueda Explícito) ───────────────────────────
const modalBuscarAlumno   = ref(false);
const searchingAlumnos    = ref(false);
const searchAlumnosQuery  = ref('');
const alumnosEncontrados  = ref([]);
const alumnoSeleccionado  = ref(null);   // alumno de reingreso seleccionado
const programaSectionRef  = ref(null);   // ref al card "Programa y Académico" para scroll

const avatarColor = (name) => {
    const colors = ['indigo', 'blue-darken-1', 'teal-darken-1', 'purple-darken-1', 'deep-orange', 'green-darken-1'];
    return colors[Math.abs(((name || 'A').charCodeAt(0) - 65)) % colors.length];
};

let debounceModalTimeout;
const buscarDesdeModal = async () => {
    if (!searchAlumnosQuery.value || searchAlumnosQuery.value.length < 3) {
        alumnosEncontrados.value = [];
        return;
    }
    searchingAlumnos.value = true;
    try {
        const { data } = await axios.get('/api/v1/alumnos/buscar-reingreso', { params: { q: searchAlumnosQuery.value } });
        alumnosEncontrados.value = data.alumnos || [];
    } catch (e) {
        console.error('Error buscando alumno:', e);
    } finally {
        searchingAlumnos.value = false;
    }
};

const onSearchModalCambia = (val) => {
    searchAlumnosQuery.value = val || '';
    clearTimeout(debounceModalTimeout);
    debounceModalTimeout = setTimeout(() => {
        buscarDesdeModal();
    }, 400);
};

const ejecutarBusquedaInmediata = async () => {
    // Al abrir el modal por medio de dar click en la LUPA, o al presionar Enter en el formulario base...
    let queryStr = typeof form.value.nombre_alumno === 'object' ? form.value.nombre_alumno?.nombre_alumno : form.value.nombre_alumno;
    
    modalBuscarAlumno.value = true;
    searchAlumnosQuery.value = queryStr || '';
    
    if (searchAlumnosQuery.value.length >= 3) {
        buscarDesdeModal();
    } else {
        alumnosEncontrados.value = []; // Purgamos rastros previos
    }
};

const onAlumnoSeleccionadoModal = (val) => {
    if (!val) return;

    form.value.nombre_alumno          = val.nombre_alumno || '';
    form.value.celular                = val.celular || '';
    form.value.adicional              = val.adicional || '';
    form.value.correo                 = val.correo || '';
    form.value.curp                   = val.curp || '';
    form.value.nombre_emergencia      = val.nombre_emergencia || '';
    form.value.parentesco_emergencia  = val.parentesco_emergencia || '';
    if (val.estado) form.value.estado = val.estado;
    setTimeout(() => {
        if (val.municipio) form.value.municipio = val.municipio;
    }, 100);
    form.value.direccion_completa = val.direccion_completa || '';

    alumnoSeleccionado.value  = val;
    modalBuscarAlumno.value   = false;

    // Enfocar la sección de Programa para que el usuario seleccione el nuevo diplomado
    nextTick(() => {
        setTimeout(() => {
            programaSectionRef.value?.$el?.scrollIntoView({ behavior: 'smooth', block: 'center' });
        }, 250);
    });
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
    planPagos.value          = [];
    isSubmitted.value        = false;
    isEditing.value          = true;
    editandoPlan.value       = false;
    planModificado.value     = false;
    alumnosEncontrados.value = [];
    alumnoSeleccionado.value = null;
};


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

            swal("¡Inscripción Guardada!", "Los datos maestros y el plan de pagos han sido generados correctamente.", "success")
                .then(() => {
                    limpiarFormulario();
                    isSubmitted.value = false;
                    isEditing.value = true;
                    idInscripcionGenerada.value = null;
                    planPagos.value = [];
                    alumnoSeleccionado.value = null;
                    costoDiplomadoRef.value = 0;
                    setFechaActual();
                });
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

const recargarCatalogos = () => {
    obtenerGrupos();
    obtenerNumeroCuenta();
    aseoresLista();
    cargarDescuentos();
    // Notificación rápida
    swal({
        title: "Catálogos Actualizados",
        text: "Se han sincronizado los últimos descuentos y grupos disponibles.",
        icon: "success",
        timer: 1500,
        buttons: false,
    });
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
                        <div class="flex items-center gap-4">
                            <v-btn icon="mdi-sync" size="small" variant="text" color="white" title="Sincronizar Catálogos (Descuentos, Grupos, Asesores)" @click="recargarCatalogos"></v-btn>
                            <v-chip v-if="!isEditing && isSubmitted" color="success" variant="flat" prepend-icon="mdi-lock">
                                Expediente Bloqueado
                            </v-chip>
                            <v-chip v-else color="warning" variant="flat" prepend-icon="mdi-pencil">
                                En Edición
                            </v-chip>
                        </div>
                    </div>

                    <form @submit.prevent="EnviarInscripcion" class="p-4 bg-slate-50 flex flex-col gap-4">
                        
                        <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
                            
                            <!-- 1. Datos Maestros (Ultra Compacto) -->
                            <v-card variant="outlined" class="bg-white border-gray-200">
                                <v-card-title class="text-sm font-semibold text-gray-700 bg-gray-100 py-1.5 px-3 border-b flex items-center">
                                    <v-icon size="x-small" class="mr-2 text-indigo-500">mdi-card-account-details</v-icon> Perfil del Estudiante
                                </v-card-title>
                                <v-card-text class="pt-3 px-3 pb-2">
                                    <!-- Campo Clásico + Lupa (Búsqueda Explícita) -->
                                    <v-text-field
                                        v-if="isEditing"
                                        v-model="form.nombre_alumno"
                                        label="Nombre del Alumno (Pulsa enter o lupa si existe)"
                                        variant="outlined"
                                        density="compact"
                                        append-inner-icon="mdi-magnify"
                                        @click:append-inner="ejecutarBusquedaInmediata"
                                        @keydown.enter.prevent="ejecutarBusquedaInmediata"
                                        class="mb-2"
                                        required
                                    ></v-text-field>
                                    <v-text-field v-else v-model="form.nombre_alumno" label="Nombre del Alumno" variant="outlined" density="compact" readonly class="mb-2" />

                                    <!-- Banner reingreso: aparece al seleccionar un alumno existente -->
                                    <div v-if="alumnoSeleccionado" class="mb-3 rounded-lg border border-blue-300 bg-blue-50 px-3 py-2 flex items-start gap-2">
                                        <v-icon color="blue-darken-2" size="20" class="mt-0.5 shrink-0">mdi-account-reactivate</v-icon>
                                        <div class="flex-1 min-w-0">
                                            <div class="text-xs font-bold text-blue-800 leading-tight">Alumno de Reingreso</div>
                                            <div class="text-xs text-blue-700 mt-0.5">
                                                <span v-if="Number(alumnoSeleccionado.total_inscripciones) > 1">
                                                    {{ alumnoSeleccionado.total_inscripciones }} programa(s) previo(s)
                                                </span>
                                                <span v-else>Primera re-inscripción</span>
                                                <span v-if="alumnoSeleccionado.ultimo_diplomado" class="ml-1">
                                                    · Último: <strong>{{ alumnoSeleccionado.ultimo_diplomado }}</strong>
                                                </span>
                                            </div>
                                        </div>
                                        <v-btn v-if="isEditing" icon="mdi-close" size="x-small" variant="text" color="blue" class="shrink-0" @click="alumnoSeleccionado = null"></v-btn>
                                    </div>

                                    <div class="grid grid-cols-2 gap-2 mb-2">
                                        <v-text-field v-model="form.celular" label="Num. Celular" variant="outlined" density="compact" hide-details :readonly="!isEditing" />
                                        <v-text-field v-model="form.adicional" label="Cel. Adicional" variant="outlined" density="compact" hide-details :readonly="!isEditing" />
                                    </div>
                                    <div class="grid grid-cols-2 gap-2 mb-2">
                                        <v-text-field v-model="form.correo" label="Correo Electrónico" variant="outlined" density="compact" hide-details :readonly="!isEditing" />
                                        <v-text-field v-model="form.curp" label="CURP" variant="outlined" density="compact" hide-details :readonly="!isEditing" />
                                    </div>
                                    
                                    <!-- Sub-bloque Domicilio dentro del mismo panel -->
                                    <v-divider class="my-2"></v-divider>
                                    <div class="text-[0.65rem] font-bold text-gray-400 uppercase tracking-wide mb-1">Domicilio y Emergencia</div>
                                    <div class="grid grid-cols-2 gap-2 mb-2">
                                        <v-autocomplete v-model="form.estado" :items="estados" label="Estado" variant="outlined" density="compact" hide-details :readonly="!isEditing" />
                                        <v-autocomplete v-model="form.municipio" :items="municipiosDelEstado" label="Municipio" variant="outlined" density="compact" hide-details :readonly="!isEditing" :disabled="!form.estado" />
                                    </div>
                                    <v-text-field v-model="form.direccion_completa" label="Dirección Completa" variant="outlined" density="compact" hide-details class="mb-2" :readonly="!isEditing" />
                                    <div class="grid grid-cols-2 gap-2">
                                        <v-text-field v-model="form.nombre_emergencia" label="Nombre Emergencia" variant="outlined" density="compact" hide-details :readonly="!isEditing" />
                                        <v-text-field v-model="form.parentesco_emergencia" label="Parentesco" variant="outlined" density="compact" hide-details :readonly="!isEditing" />
                                    </div>
                                    <v-text-field :value="authUser.name" label="Matriculador (Auto)" variant="plain" density="compact" class="mt-2 text-xs opacity-70" readonly hide-details />
                                </v-card-text>
                            </v-card>

                            <!-- 2. Matrícula y Finanzas -->
                            <div class="flex flex-col gap-4">
                                <v-card ref="programaSectionRef" variant="outlined"
                                    :class="['bg-white transition-all duration-300', alumnoSeleccionado && isEditing ? 'border-blue-400' : 'border-gray-200']">
                                    <v-card-title class="text-sm font-semibold text-gray-700 bg-gray-100 py-1.5 px-3 border-b flex items-center">
                                        <v-icon size="x-small" class="mr-2 text-blue-500">mdi-school-outline</v-icon> Programa y Académico
                                        <v-chip v-if="alumnoSeleccionado && isEditing" color="blue" size="x-small" variant="flat" class="ml-auto" prepend-icon="mdi-cursor-pointer">Selecciona el nuevo programa</v-chip>
                                    </v-card-title>
                                    <v-card-text class="pt-3 px-3 pb-2">
                                        <v-select v-model="form.seleccionGrupo" :items="Grupos" item-title="nombre" item-value="id" label="Programa a Inscribir" variant="outlined" density="compact" hide-details :readonly="!isEditing" @update:modelValue="actualizarDiplomado" class="mb-2">
                                            <template v-slot:item="{ props, item }">
                                                <v-list-item v-bind="props" :title="item.raw.nombre" :subtitle="`Gpo: ${item.raw.grupo} | $${Number(item.raw.costo_total).toLocaleString()}`" />
                                            </template>
                                            <template v-slot:selection="{ item }"><span>{{ item.raw.nombre }} (Gpo: {{ item.raw.grupo }})</span></template>
                                        </v-select>
                                        
                                        <div v-if="costoDiplomadoRef > 0 && isEditing" class="mb-2 bg-blue-50 text-blue-800 text-xs py-1 px-2 rounded font-semibold text-right">
                                            Costo Base: ${{ Number(costoDiplomadoRef).toLocaleString() }} MXN
                                        </div>

                                        <div class="grid grid-cols-2 gap-2 hover-descuento">
                                            <v-select v-model="form.selectedTutor" :items="asesores" item-title="name" item-value="id" label="Tutor Asignado" variant="outlined" density="compact" hide-details :readonly="!isEditing" />
                                            <v-select v-if="isEditing" v-model="form.descuento_id" :items="descuentosVigentes" item-title="nombre" item-value="id" label="Beca / Descuento" variant="outlined" density="compact" hide-details clearable prepend-inner-icon="mdi-tag" color="deep-purple-darken-2" />
                                        </div>
                                    </v-card-text>
                                </v-card>

                                <v-card variant="outlined" class="bg-white border-gray-200 flex-1">
                                    <v-card-title class="text-sm font-semibold text-gray-700 bg-gray-100 py-1.5 px-3 border-b flex items-center">
                                        <v-icon size="x-small" class="mr-2 text-green-600">mdi-cash-register</v-icon> Cobro Inicial (Finanzas)
                                    </v-card-title>
                                    <v-card-text class="pt-3 px-3 pb-2">
                                        <div class="grid grid-cols-2 gap-2 mb-2">
                                            <v-select v-model="form.selectedTitular" :items="cuentaDeposito" item-title="titular" item-value="id" label="Cuenta Receptora" variant="outlined" density="compact" hide-details :readonly="!isEditing">
                                                <template v-slot:item="{ props, item }"><v-list-item v-bind="props" :title="item.raw.banco" :subtitle="item.raw.titular" /></template>
                                            </v-select>
                                            <v-select v-model="form.metodo_pago_inscripcion" :items="metodosPago" label="Método de Pago" variant="outlined" density="compact" hide-details :readonly="!isEditing" />
                                        </div>
                                        <div class="grid grid-cols-3 gap-2">
                                            <v-text-field v-model="form.monto_inscripcion" label="Abono Ingreso" variant="outlined" density="compact" hide-details prefix="$" type="number" :readonly="!isEditing" />
                                            <v-text-field v-model="form.fecha_inscripcion" label="Alta" variant="filled" density="compact" hide-details type="date" readonly />
                                            <v-text-field v-model="form.fecha_primer_pago_colegiatura" label="Siguiente Pago" variant="outlined" density="compact" hide-details type="date" :readonly="!isEditing" />
                                        </div>
                                    </v-card-text>
                                </v-card>
                            </div>

                        </div>

                        <!-- Barra de Botones del Formulario -->
                        <div class="mt-2 flex justify-between items-center bg-gray-200/50 p-2 px-4 rounded shadow-inner border border-gray-300/50">
                            <div>
                                <v-btn v-if="!isEditing" @click="limpiarFormulario" color="indigo-darken-3" variant="text" size="small" prepend-icon="mdi-plus">Registrar Otra Matrícula</v-btn>
                            </div>
                            <div class="space-x-3">
                                <v-btn v-if="isEditing" @click="limpiarFormulario" variant="tonal" size="small" color="grey-darken-2">Limpiar</v-btn>
<v-btn v-if="isEditing" type="submit" color="indigo-darken-3" size="small" variant="elevated" prepend-icon="mdi-check-all">Procesar Ingreso</v-btn>
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
        
        <!-- ── Modal Explicito de Búsqueda de Alumno ── -->
        <v-dialog v-model="modalBuscarAlumno" max-width="700">
            <v-card rounded="lg">
                <v-card-title class="bg-indigo-900 text-white flex justify-between items-center py-3">
                    <span class="text-base font-semibold"><v-icon class="mr-2">mdi-account-search</v-icon> Búsqueda de Alumno Existente</span>
                    <v-btn icon="mdi-close" variant="text" size="small" color="white" @click="modalBuscarAlumno = false"></v-btn>
                </v-card-title>
                
                <div class="px-6 pt-4 pb-0 bg-gray-50 border-b border-gray-200">
                    <v-text-field
                        :model-value="searchAlumnosQuery"
                        @update:model-value="onSearchModalCambia"
                        label="Escribe para filtrar o buscar otro alumno..."
                        variant="outlined"
                        density="comfortable"
                        prepend-inner-icon="mdi-magnify"
                        clearable
                        autofocus
                        hide-details
                        class="mb-4 bg-white"
                        placeholder="Ej. Juan Perez"
                    ></v-text-field>
                </div>

                <v-card-text class="py-4 bg-gray-50 mb-0" style="min-height: 250px; max-height: 400px; overflow-y: auto;">
                    <div v-if="searchingAlumnos" class="py-8 flex flex-col items-center justify-center">
                        <v-progress-circular indeterminate color="indigo" size="48"></v-progress-circular>
                        <p class="mt-4 text-gray-600 font-medium tracking-wide animate-pulse">Buscando en la base de datos...</p>
                    </div>
                    
                    <div v-else-if="alumnosEncontrados.length === 0 && searchAlumnosQuery?.length >= 3" class="py-8 text-center bg-white rounded border border-dashed border-gray-300">
                        <v-icon size="48" color="grey-lighten-1">mdi-account-question</v-icon>
                        <p class="mt-3 text-gray-700 font-medium text-lg">No hay resultados para "{{ searchAlumnosQuery }}"</p>
                        <p class="text-sm text-gray-500 mt-1 mb-4">Si el nombre es nuevo, cierra esta ventana para declararlo como de Nuevo Ingreso al guardar.</p>
                        <v-btn color="indigo" variant="tonal" @click="modalBuscarAlumno = false">Continuar como Nuevo Ingreso</v-btn>
                    </div>
                    
                    <div v-else-if="alumnosEncontrados.length === 0 && searchAlumnosQuery?.length < 3" class="py-8 text-center bg-transparent">
                        <v-icon size="48" color="grey-lighten-3">mdi-text-search</v-icon>
                        <p class="mt-3 text-gray-500 font-medium text-base">Escribe al menos 3 letras para comenzar la búsqueda automáticamente.</p>
                    </div>
                    
                    <div v-else>
                        <p class="text-xs text-gray-500 mb-3 bg-blue-50 border border-blue-100 px-3 py-2 rounded flex items-center gap-2">
                            <v-icon size="x-small" color="blue">mdi-information-outline</v-icon>
                            {{ alumnosEncontrados.length }} alumno(s) encontrado(s). Haz clic para auto-llenar el perfil y continuar al programa.
                        </p>
                        <v-list class="border rounded shadow-sm bg-white" lines="two">
                            <v-list-item
                                v-for="(item) in alumnosEncontrados" :key="item.id"
                                @click="onAlumnoSeleccionadoModal(item)"
                                class="hover:bg-indigo-50 border-b last:border-b-0 cursor-pointer transition-colors"
                            >
                                <template v-slot:prepend>
                                    <v-avatar :color="avatarColor(item.nombre_alumno)" size="42" class="mr-3">
                                        <span class="text-white text-sm font-bold">
                                            {{ (item.nombre_alumno || '?').split(' ').filter(w => w).slice(0, 2).map(w => w[0]).join('').toUpperCase() }}
                                        </span>
                                    </v-avatar>
                                </template>
                                <v-list-item-title class="font-bold text-gray-800 text-sm">{{ item.nombre_alumno }}</v-list-item-title>
                                <v-list-item-subtitle class="text-xs text-gray-500 mt-0.5">
                                    <span class="mr-3">{{ item.celular || '—' }}</span>
                                    <span class="mr-3 font-mono">{{ item.curp || '—' }}</span>
                                    <span v-if="item.ultimo_diplomado" class="text-indigo-600 font-medium">Último: {{ item.ultimo_diplomado }}</span>
                                </v-list-item-subtitle>
                                <template v-slot:append>
                                    <div class="flex flex-col items-end gap-1">
                                        <v-chip
                                            :color="Number(item.total_inscripciones) > 1 ? 'blue' : 'green'"
                                            size="x-small" variant="flat"
                                            :prepend-icon="Number(item.total_inscripciones) > 1 ? 'mdi-account-reactivate' : 'mdi-account-plus'"
                                        >
                                            {{ Number(item.total_inscripciones) > 1 ? `${item.total_inscripciones} inscripciones` : 'Nuevo ingreso' }}
                                        </v-chip>
                                        <v-btn color="indigo" size="x-small" variant="text" append-icon="mdi-arrow-right">Seleccionar</v-btn>
                                    </div>
                                </template>
                            </v-list-item>
                        </v-list>
                    </div>
                </v-card-text>
                <v-card-actions class="bg-gray-100 py-2 px-4 shadow-inner">
                    <v-spacer></v-spacer>
                    <v-btn color="grey-darken-2" variant="text" @click="modalBuscarAlumno = false">Es Alumno Nuevo (Cerrar)</v-btn>
                </v-card-actions>
            </v-card>
        </v-dialog>

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
