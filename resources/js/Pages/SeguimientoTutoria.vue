<script setup>
import { ref } from "vue";
import { usePage } from "@inertiajs/vue3";
import axios from "axios";
import swal from "sweetalert";
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import { Head } from "@inertiajs/vue3";

const page = usePage();
const userId = page.props.userId;
</script>

<template>
    <v-app class="bg-gray-50">
        <v-container class="my-4 max-w-7xl">
            <div class="mb-4">
                <h2 class="text-2xl font-bold text-gray-800 flex items-center">
                    <v-icon color="success" class="mr-2">mdi-account-cash</v-icon>
                    Asignación de Primera Colegiatura
                </h2>
                <p class="text-sm text-gray-500">
                    Alumnos de nuevo ingreso esperando la validación académica mediante alta del primer pago (Apertura de Expediente).
                </p>
            </div>

            <!-- DASHBOARD DE RENDIMIENTO/KPI DEL TUTOR -->
            <v-card v-if="kpiData" variant="outlined" class="bg-indigo-900 border-gray-200 shadow-sm rounded-xl overflow-hidden mb-8">
                <div class="p-6 text-white grid grid-cols-1 md:grid-cols-2 items-center gap-6">
                    <div>
                        <h3 class="text-xl font-bold mb-1 flex items-center">
                            <v-icon color="yellow" class="mr-2">mdi-trophy-award</v-icon>
                            Rendimiento Mensual de Tutor: Cierre de Matrículas
                        </h3>
                        <p class="text-indigo-200 text-sm">
                            Este KPI mide tus inscripciones realizadas exitosamente (las que ya cobraron su primera colegiatura) meta global 20 mensuales.
                        </p>
                    </div>
                    
                    <div class="bg-indigo-800/50 p-4 rounded-xl border border-indigo-700/50">
                        <div class="flex justify-between items-end mb-2">
                            <span class="text-sm font-medium text-indigo-100">Progreso a la meta</span>
                            <span class="text-2xl font-bold font-mono">{{ kpiData.cerradas }} <span class="text-lg text-indigo-300">/ {{ kpiData.meta }}</span></span>
                        </div>
                        <v-progress-linear
                            :model-value="(kpiData.cerradas / kpiData.meta) * 100"
                            color="success"
                            bg-color="indigo-darken-3"
                            height="12"
                            rounded
                            striped
                        ></v-progress-linear>
                        <div class="flex justify-between items-center mt-2 text-xs">
                            <span class="text-indigo-300">0%</span>
                            <span v-if="kpiData.cerradas >= kpiData.meta" class="text-green-300 font-bold">¡Meta Alcanzada! 🎉</span>
                            <span v-else class="text-yellow-300">Faltan {{ kpiData.meta - kpiData.cerradas }} expedientes para tu cuota.</span>
                            <span class="text-indigo-300">100%</span>
                        </div>
                    </div>
                </div>
            </v-card>

            <v-card variant="outlined" class="bg-white border-gray-200 shadow-sm rounded-xl overflow-hidden mb-6">
                <div class="bg-gray-100 px-6 py-4 flex flex-col md:flex-row items-center justify-between border-b gap-4">
                    <div class="w-full md:w-5/12">
                        <v-text-field
                            v-model="busqueda"
                            placeholder="Buscar alumno..."
                            variant="solo"
                            density="comfortable"
                            hide-details
                            prepend-inner-icon="mdi-magnify"
                            class="shadow-sm rounded-lg bg-white"
                            @keyup.enter="buscarAlumnos"
                        ></v-text-field>
                    </div>

                    <!-- NUEVO FILTRO DE DIPLOMADOS -->
                    <div class="w-full md:w-5/12">
                        <v-select
                            v-model="filtroDiplomado"
                            :items="listaDiplomadosUnicos"
                            label="Filtrar por Diplomado"
                            variant="solo"
                            density="comfortable"
                            hide-details
                            clearable
                            prepend-inner-icon="mdi-filter-variant"
                            class="shadow-sm rounded-lg bg-white"
                            @update:modelValue="aplicarFiltros"
                        ></v-select>
                    </div>

                    <div class="w-full md:w-2/12 flex space-x-2">
                        <v-btn @click="buscarAlumnos" color="indigo-darken-3" class="flex-1" variant="elevated" prepend-icon="mdi-magnify">Filtrar</v-btn>
                        <v-btn @click="limpiarBusqueda" color="grey-darken-1" variant="tonal" icon="mdi-eraser"></v-btn>
                    </div>
                </div>

                <v-list class="bg-transparent px-4 py-4">
                    <v-virtual-scroll :items="alumnosFiltrados" item-height="85" style="max-height: 55vh;">
                        <template v-slot:default="{ item: alumno }">
                            <v-list-item class="mb-3 border border-gray-100 shadow-sm rounded-lg bg-white overflow-hidden transition-all hover:border-indigo-300">
                                <template v-slot:prepend>
                                    <v-avatar color="green-lighten-4" size="48" class="mr-4">
                                        <v-icon color="green-darken-4">mdi-account-school</v-icon>
                                    </v-avatar>
                                </template>
                                <v-list-item-title class="font-bold text-gray-800 text-base">
                                    {{ alumno.nombre_alumno }}
                                </v-list-item-title>
                                <v-list-item-subtitle class="text-sm text-gray-500">
                                    <v-chip size="x-small" color="indigo" class="mr-2">{{ alumno.nombre_diplomado }}</v-chip>
                                    Balance Restante Global: <span class="text-red-500 font-bold">${{ alumno.saldo }} MXN</span>
                                </v-list-item-subtitle>
                                
                                <template v-slot:append>
                                    <!-- DIALOG DE COBRO MODO APERTURA -->
                                    <v-dialog max-width="850">
                                        <template v-slot:activator="{ props: activatorProps }">
                                            <v-btn
                                                v-bind="activatorProps"
                                                color="success"
                                                variant="flat"
                                                size="small"
                                                prepend-icon="mdi-file-document-edit"
                                                @click="prepararCobro(alumno.alumno_id, alumno.diplomado_id, alumno.nombre_alumno, alumno)"
                                            >
                                                Timbrar Colegiatura
                                            </v-btn>
                                        </template>

                                        <template v-slot:default="{ isActive }">
                                            <v-card rounded="xl" class="overflow-hidden">
                                                <div class="bg-indigo-900 px-6 py-4 flex justify-between items-center text-white">
                                                    <div>
                                                        <h3 class="text-lg font-medium flex items-center">
                                                            <v-icon color="success" class="mr-2">mdi-point-of-sale</v-icon>
                                                            Apertura de Expediente Financiero
                                                        </h3>
                                                        <p class="text-indigo-200 text-xs">Alumno en proceso: {{ alumno.nombre_alumno }}</p>
                                                    </div>
                                                </div>

                                                <v-card-text class="bg-gray-50 p-6">
                                                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                                                        <!-- FORMULARIO DE COBRO -->
                                                        <div>
                                                            <div class="flex items-center justify-between mb-4 border-b pb-2">
                                                                <h4 class="text-gray-700 font-bold">Datos del Cobro Inicial</h4>
                                                                <v-chip size="small" color="red" variant="flat">Deuda: ${{ alumno.saldo }}</v-chip>
                                                            </div>

                                                            <form @submit.prevent="EnviarPago(isActive)" class="space-y-4">
                                                                <!-- Monto OBLIGATORIO -->
                                                                <v-text-field 
                                                                    v-model.number="pago_colegiatura" 
                                                                    label="Importe 1ª Colegiatura (MXN)" 
                                                                    variant="outlined" 
                                                                    density="comfortable" 
                                                                    type="number" 
                                                                    prefix="$" 
                                                                    bg-color="white" 
                                                                    :max="alumno.saldo"
                                                                    :rules="[
                                                                        v => !!v || 'Requerido',
                                                                        v => v > 0 || 'Debe ser mayor a 0',
                                                                        v => v <= parseFloat(alumno.saldo) || `El tope es la deuda: $${alumno.saldo}`
                                                                    ]"
                                                                    required>
                                                                </v-text-field>

                                                                <div class="grid grid-cols-2 gap-4">
                                                                    <v-text-field v-model="fecha_inscripcion" label="Fecha Bancaria (Sistema Automático)" variant="filled" density="comfortable" type="date" readonly bg-color="gray-100" class="text-gray-500" hint="La fecha no puede ser alterada por motivos de Auditoría." persistent-hint></v-text-field>
                                                                    
                                                                    <v-select
                                                                        v-model="selectedTitular"
                                                                        :items="cuentaDeposito"
                                                                        item-title="banco"
                                                                        item-value="id"
                                                                        label="Cta. Receptora"
                                                                        variant="outlined"
                                                                        density="comfortable"
                                                                        bg-color="white"
                                                                        required
                                                                    >
                                                                         <template v-slot:item="{ props, item }">
                                                                            <v-list-item v-bind="props" :title="item.raw.banco" :subtitle="`T: ${item.raw.titular} (CLABE: ${item.raw.CLABE})`"></v-list-item>
                                                                        </template>
                                                                        <template v-slot:selection="{ item }">
                                                                            <span>{{ item.raw.banco }}</span>
                                                                        </template>
                                                                    </v-select>
                                                                </div>

                                                                <v-file-input
                                                                    v-model="comprobante"
                                                                    label="Comprobante Bancario (PDF o Imagen)"
                                                                    variant="outlined"
                                                                    density="comfortable"
                                                                    bg-color="white"
                                                                    prepend-icon="mdi-cloud-upload"
                                                                    accept="image/*,application/pdf"
                                                                    :rules="[v => !!v || 'Debe subir el ticket de pago forzosamente']"
                                                                    required
                                                                ></v-file-input>

                                                                <div class="bg-blue-50 border border-blue-200 p-3 rounded-lg flex items-start text-xs text-blue-800 mt-2">
                                                                    <v-icon color="info" size="small" class="mr-2 mt-0.5">mdi-shield-check</v-icon>
                                                                    Aprobar este pago desbloqueará al alumno administrativamente en sistema y generará su PDF Académico oficial.
                                                                </div>

                                                                <div class="pt-2">
                                                                    <v-btn type="submit" color="success" size="large" block variant="elevated" prepend-icon="mdi-printer-pos">Aprobar Pago y Generar Ficha PDF</v-btn>
                                                                </div>
                                                            </form>
                                                        </div>

                                                        <!-- RESUMEN ACADEMICO E INDICADOR -->
                                                        <div>
                                                            <div class="mb-4 border-b pb-2">
                                                                <h4 class="text-gray-700 font-bold">Resumen Base</h4>
                                                            </div>
                                                            
                                                            <v-list class="bg-white rounded-lg shadow-sm border border-gray-100">
                                                                <v-list-item>
                                                                    <template v-slot:prepend><v-icon color="indigo">mdi-school</v-icon></template>
                                                                    <v-list-item-title class="text-sm font-bold">{{ alumno.nombre_diplomado }}</v-list-item-title>
                                                                    <v-list-item-subtitle class="text-xs">Programa Formativo</v-list-item-subtitle>
                                                                </v-list-item>
                                                                <v-divider></v-divider>
                                                                <v-list-item>
                                                                    <template v-slot:prepend><v-icon color="indigo">mdi-tag</v-icon></template>
                                                                    <v-list-item-title class="text-sm font-bold">Aportó Inscripción: ${{ alumno.monto_inscripcion }} mxn</v-list-item-title>
                                                                    <v-list-item-subtitle class="text-xs">Día {{ alumno.fecha_inscripcion }} | Gpo. {{ alumno.grupo }}</v-list-item-subtitle>
                                                                </v-list-item>
                                                            </v-list>

                                                            <div class="mt-6 text-center text-gray-400 p-4 border-2 border-dashed border-gray-200 rounded-lg">
                                                                <v-icon size="x-large" class="mb-2">mdi-account-clock</v-icon><br>
                                                                <span class="text-sm">Expediente esperando su primera mensualidad para cierre.</span>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </v-card-text>

                                                <v-card-actions class="bg-gray-100 px-6 py-3 justify-end border-t">
                                                    <v-btn variant="text" color="grey-darken-3" prepend-icon="mdi-close" @click="isActive.value = false; limpiarFormulario()">Cancelar</v-btn>
                                                </v-card-actions>
                                            </v-card>
                                        </template>
                                    </v-dialog>
                                </template>
                            </v-list-item>
                        </template>
                    </v-virtual-scroll>
                </v-list>
            </v-card>
        </v-container>
    </v-app>
</template>

<script>
import swal from "sweetalert";
import { usePage } from "@inertiajs/vue3";
import axios from "axios";

export default {
    data() {
        return {
            alumno_id: null,
            diplomado_id: null,
            busqueda: "",
            filtroDiplomado: null,
            pendienteMesUser: [],
            alumnosFiltrados: [],
            pago_colegiatura: null,
            selectedTitular: null,
            fecha_inscripcion: null,
            cuentaDeposito: [],
            userId: usePage().props.userId,
            alumnoSeleccionadoData: null,
            comprobante: null,
            kpiData: null,
        };
    },
    computed: {
        listaDiplomadosUnicos() {
            // Extraer nombres únicos de diplomados para el v-select
            const unicos = new Set();
            this.pendienteMesUser.forEach(item => {
                if(item.nombre_diplomado) unicos.add(item.nombre_diplomado);
            });
            return Array.from(unicos);
        }
    },
    created() {
        this.obtenerNumeroCuenta();
        this.setFechaActual();
        this.obtenerListaAlumnos();
    },
    methods: {
        limpiarFormulario() {
            this.pago_colegiatura = '';
            this.selectedTitular = null;
            this.comprobante = null;
        },

        prepararCobro(aId, dId, nNombre, infoCompleta) {
            this.alumno_id = aId;
            this.diplomado_id = dId;
            this.alumnoSeleccionadoData = infoCompleta;
            // Al ser primera vez, no hace falta llamar el historial (por regla de negocio están vacíos)
        },

        EnviarPago(isActiveContext) {
            if(!this.pago_colegiatura || !this.selectedTitular || !this.fecha_inscripcion || !this.comprobante){
                swal("Advertencia", "Los campos bancarios, importe y comprobante son requeridos obligatoriamente.", "warning"); return;
            }

            if(this.pago_colegiatura <= 0) {
                 swal("Advertencia", "El monto ingresado debe ser superior a 0.", "warning"); return;
            }

            if(this.pago_colegiatura > this.alumnoSeleccionadoData.saldo) {
                 swal("Monto No Permitido", `Has ingresado $${this.pago_colegiatura}, pero este alumno sólo debe $${this.alumnoSeleccionadoData.saldo} del curso. No se permiten cobros exorbitantes.`, "error"); return;
            }

            let formData = new FormData();
            formData.append('Fecha_PrimerContacto', this.fecha_inscripcion);
            formData.append('pago_colegiatura', this.pago_colegiatura);
            formData.append('tutor', this.userId);
            formData.append('status', 'Activo');
            formData.append('cuentadeposito', this.selectedTitular);
            formData.append('alumno_id', this.alumno_id);
            formData.append('diplomado_id', this.diplomado_id);
            formData.append('comprobante', this.comprobante);

            axios.post("/api/v1/pagosabonos/crear", formData, {
                headers: { 'Content-Type': 'multipart/form-data' }
            })
                .then((res) => {
                    swal("Apertura Completada", "Alumno liberado. Su recibo oficial se generará en este momento...", "success");
                    
                    // Extraer ID y descargar el archivo PDF Oficial Laravel-dompdf autogenerado.
                    const idPagoPdf = res.data.pago.id;
                    setTimeout(() => {
                        window.open('/pagos/' + idPagoPdf + '/pdf', '_blank');
                    }, 1000);

                    this.limpiarFormulario();
                    this.obtenerListaAlumnos(); // Refrescar lista maestra, el alumno al tener 1 pago, desaparecerá de "tutorias pendientes" si la consulta de backend así lo dicta.
                    if(isActiveContext) isActiveContext.value = false; // Cerramos modal.
                })
                .catch((err) => {
                    swal("Error de Comunicación", "Falló el timbrado con el servidor.", "error");
                    console.error(err);
                });
        },

        obtenerNumeroCuenta() {
            axios.get("/api/v1/cuentadeposito/index/2024/numero")
                .then((res) => this.cuentaDeposito = res.data.cuentaDeposito)
                .catch((err) => console.error(err));
        },

        aplicarFiltros() {
            // Método unificado para ejecutar búsqueda textual y dropdown de diplomado
            let resultados = this.pendienteMesUser;

            // 1. Filtrar por búsqueda de nombre
            if (this.busqueda.trim() !== "") {
                const term = this.busqueda.toLowerCase();
                resultados = resultados.filter(a => String(a.nombre_alumno).toLowerCase().includes(term));
            }

            // 2. Filtrar por el Diplomado Seleccionado
            if (this.filtroDiplomado) {
                 resultados = resultados.filter(a => a.nombre_diplomado === this.filtroDiplomado);
            }

            this.alumnosFiltrados = resultados.map(a => ({...a}));

            if(this.alumnosFiltrados.length === 0 && (this.busqueda !== "" || this.filtroDiplomado)){
                swal({icon: "info", title: "Sin Resultados", text: "No hay alumnos inscritos esperando pago en ese rubro.", buttons: "Entendido"});
            }
        },

        buscarAlumnos() {
             this.aplicarFiltros();
        },

        limpiarBusqueda() {
            this.busqueda = "";
            this.filtroDiplomado = null;
            this.alumnosFiltrados = [...this.pendienteMesUser];
        },

        setFechaActual() {
            const today = new Date();
            this.fecha_inscripcion = `${today.getFullYear()}-${String(today.getMonth() + 1).padStart(2, "0")}-${String(today.getDate()).padStart(2, "0")}`;
        },

        obtenerListaAlumnos() {
            axios.get("/api/v1/mensualidad/seguimiento", { params: { tutor_id: this.userId } })
                .then((response) => {
                    this.pendienteMesUser = response.data.pendienteMesUser;
                    this.alumnosFiltrados = response.data.pendienteMesUser.map(a => ({ ...a }));
                    this.kpiData = response.data.kpi;
                })
                .catch((error) => console.error("Error al obtener alumnos rezagados y kpi:", error));
        },
    },
};
</script>
