<script setup>
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import { Head, usePage } from "@inertiajs/vue3";

const page = usePage();
const userId = page.props.userId;

console.log('user id', userId);
</script>

<template>
    <div class="watermark-container">
        <!-- Contenido de tu vista -->
        <div class="max-w-4xl mx-auto p-4 lg:p-6">
            <Head title="Cedac" />

                    <v-toolbar title="Formulario de Inscripción " color="light-blue-darken-4">
                        <v-toolbar-items>
                        </v-toolbar-items>
                        <v-divider class="mx-2" vertical></v-divider>
                        <v-btn>Generar Ficha</v-btn>
                    </v-toolbar>


                <v-container>
                    <div class="leading-loose">
                        <form class="max-w-full bg-white rounded shadow-xl p-4 lg:p-6" @submit.prevent="EnviarInscripcion">
                            <div class="grid gap-6 mb-6 lg:grid-cols-2">
                                <v-text-field v-model="nombre_alumno" variant="outlined" label="NOMBRE COMPLETO" prepend-icon="mdi-account-arrow-right"></v-text-field>
                                <v-text-field v-model="celular" variant="outlined" label="CELULAR" prepend-icon="mdi-phone"></v-text-field>
                                <v-text-field v-model="adicional" variant="outlined" label="CELULAR ADICIONAL" prepend-icon="mdi-phone-plus"></v-text-field>
                                <div>
                                    <label class="block text-sm text-gray-600" for="alumno">Numero de Cuenta</label>
                                    <select id="diplomado" v-model="selectedTitular" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                                        <option disabled selected>Selecciona un Numero de Cuenta</option>
                                        <option v-for="titular in cuentaDeposito" :key="titular.id" :value="titular.id">{{ titular.titular }} - {{ titular.CLABE }}</option>
                                    </select>
                                </div>
                                <v-text-field v-model="monto_inscripcion" variant="outlined" label="Monto Inscripcion" prepend-icon="mdi-currency-usd" type="number"></v-text-field>
                                <v-text-field v-model="fecha_inscripcion" variant="outlined" label="Fecha de Inscripción" type="date" readonly></v-text-field>
                                <v-text-field v-model="fecha_primer_pago_colegiatura" variant="outlined" label="Fecha de Primer Colegiatura" type="date"></v-text-field>
                            </div>

                            <div class="mb-6">
                                <label class="block text-sm text-gray-600">Diplomado y Grupo</label>
                                <select v-model="seleccionGrupo" @change="actualizarDiplomado" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                                    <option disabled selected>Selecciona un Grupo</option>
                                    <option v-for="grupo in Grupos" :key="grupo.id" :value="grupo.id">
                                        Diplomado: {{ grupo.nombre }} - Grupo: {{ grupo.grupo }} - Campaña: {{ grupo.campaña }}
                                    </option>
                                </select>
                            </div>

                            <div class="mb-6">
                                <label class="block text-sm text-gray-600">Tutoria</label>
                                <select v-model="selectedTutor" @change="actualizarDiplomado" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                                    <option disabled selected>Selecciona un Tutor</option>
                                    <option v-for="grupo in asesores" :key="grupo.id" :value="grupo.id">
                                        {{ grupo.name }}
                                    </option>
                                </select>
                            </div>

                            <div class="mb-6">
                                <label class="block text-sm text-gray-600">Matriculador</label>
                                <v-text-field v-model="asesor" variant="outlined" readonly label="Matriculador" :value="$page.props.auth.user.name"></v-text-field>
                            </div>

                            <div class="flex items-start mb-6">
                                <div class="flex items-center h-5">
                                    <input id="remember" type="checkbox" value="" class="w-4 h-4 border border-gray-300 rounded bg-gray-50 focus:ring-3 focus:ring-blue-300 dark:bg-gray-700 dark:border-gray-600 dark:focus:ring-blue-600 dark:ring-offset-gray-800" required>
                                </div>
                                <label for="remember" class="ml-2 text-sm text-gray-900 dark:text-gray-400">Los datos ingresados son correctos.</label>
                            </div>

                            <div class="flex justify-between">
                                <v-btn color="green" size="large" variant="elevated" type="submit" prepend-icon="mdi-send">Enviar</v-btn>
                                <v-btn @click="limpiarFormulario" color="red" size="large" variant="elevated" prepend-icon="mdi-eraser">Vaciar</v-btn>
                            </div>

                        </form>
                    </div>
                </v-container>
        </div>
    </div>
</template>

<style scoped>
.watermark-container {
    position: relative;
    padding: 20px;
    border-radius: 8px;
    box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
    overflow: hidden;
}

.watermark-container::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background-image: url('/public/images/logo.jpg');
    background-repeat: repeat;
    background-position: top left;
    filter: blur(8px);
    z-index: -1;
}
</style>

<script>
import Estadisticas from "./Estadisticas.vue";
import axios from 'axios';
import { usePage } from '@inertiajs/vue3';
import swal from 'sweetalert';

export default {
    created() {
        this.obtenerAlumnos();
        this.obtenerDiplomados();
        this.obtenerNumeroCuenta();
        this.obtenerGrupos();
        this.setFechaActual();
        this.aseoresLista();
    },

    components: {
        Estadisticas,
    },

    data() {
        return {
            seleccionGrupo: null,
            seleccionDiplomado: null,
            fecha_primer_pago_colegiatura: "",
            fecha_inscripcion: "",
            monto_inscripcion: null,
            fecha: null,
            alumno_id: null,
            diplomado_id: null,
            selectedAlumno: null,
            selectedDiplomado: null,
            selectedTitular: null,
            Grupos: [],
            nombre_alumno: null,
            celular: null,
            adicional: null,
            alumnos: [],
            Alumnos: [],
            cuentaDeposito: [],
            asesores: [],
            idDiplomado: null,
        };
    },

    methods: {
        limpiarFormulario() {
            this.nombre_alumno = '';
            this.celular = '';
            this.adicional = '';
            this.selectedTitular = null;
            this.monto_inscripcion = '';
            this.fecha_primer_pago_colegiatura = '';
            this.selectedTutor = '';
            this.seleccionGrupo = null;
        },

        actualizarDiplomado() {
            const grupoSeleccionado = this.Grupos.find(grupo => grupo.id === this.seleccionGrupo);
            this.seleccionDiplomado = grupoSeleccionado ? grupoSeleccionado.diplomado_id : null;
        },

        setFechaActual() {
            const today = new Date();
            const year = today.getFullYear();
            const month = String(today.getMonth() + 1).padStart(2, "0");
            const day = String(today.getDate()).padStart(2, "0");
            this.fecha_inscripcion = `${year}-${month}-${day}`;
        },

        EnviarInscripcion() {
            const inscripcion = {
                fecha_primer_pago_colegiatura: this.fecha_primer_pago_colegiatura,
                fecha_inscripcion: this.fecha_inscripcion,
                nombre_alumno: this.nombre_alumno,
                monto_inscripcion: this.monto_inscripcion,
                cuentadeposito: this.selectedTitular,
                grupo_campa: this.seleccionGrupo,
                diplomado_id: this.seleccionDiplomado,
                celular: this.adicional,
                adicional: this.adicional,
                tutor: this.selectedTutor,
                asesor: this.$page.props.auth.user.id,
            };

            axios.post("/api/v1/inscripcion/crear", inscripcion)
                .then(res => {
                    console.log(res);
                    swal("Inscripcion realizada!", "success");
                })
                .catch(err => {
                    console.error(err);
                    swal("Llena los datos corretamente");
                });
        },

        obtenerGrupos() {
            axios.get("/api/v1/grupos/listar")
                .then(res => {
                    this.Grupos = res.data.Grupos;
                })
                .catch(err => {
                    console.error(err);
                });
        },

        obtenerNumeroCuenta() {
            axios.get("/api/v1/cuentadeposito/index/2024/numero")
                .then(res => {
                    this.cuentaDeposito = res.data.cuentaDeposito;
                })
                .catch(err => {
                    console.error(err);
                });
        },

        obtenerAlumnos() {
            axios.get("/api/v1/alumnos/index/2024/nombres")
                .then(res => {
                    this.Alumnos = res.data.Alumnos;
                })
                .catch(err => {
                    console.error(err);
                });
        },

        obtenerDiplomados() {
            axios.get("/api/v1/diplomados/index/2024/diplomados")
                .then(res => {
                    this.NombreDiplomado = res.data.DiplomadoNombre;
                })
                .catch(err => {
                    console.error(err);
                });
        },

        aseoresLista() {
            axios.get('/api/v1/listar/asesores')
                .then(res => {
                    this.asesores = res.data.asesores;
                })
                .catch(err => {
                    console.error(err);
                });
        }
    },
};
</script>
