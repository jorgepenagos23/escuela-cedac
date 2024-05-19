<script setup>
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import { Head } from "@inertiajs/vue3";
</script>

<template>
    <div class="watermark-container">

        <!-- Contenido de tu vista -->
        <div class="max-w-7xl mx-auto p-6 lg:p-8">

            <Head title="Cedac" />
            <AuthenticatedLayout>
                <template #header>
                    <v-toolbar title="Formulario de Inscripción de Matricula" color="light-blue-darken-4
          ">
                        <v-toolbar-items>
                            <v-btn>Generar Ficha</v-btn>
                        </v-toolbar-items>

                        <v-divider class="mx-2" vertical></v-divider>

                        <v-btn icon="mdi-dots-vertical"></v-btn>
                    </v-toolbar>

                    <v-container>
                        <div class="leading-loose">

                            <p class="mt-3 text-base text-gray-400 sm:mt-5 sm:text-xl lg:text-lg xl:text-xl"></p>

                            <form class="max-w-8xl m-4 p-10 bg-white

                            rounded shadow-xl" @submit.prevent="EnviarInscripcion">
                                <div class="grid gap-6 mb-6 lg:grid-cols-2">

                                    <div>
                                        <v-text-field v-model="nombre_alumno" variant="outlined">
                                            <template v-slot:label>
                                                <span>
                                                    NOMBRE COMPLETO
                                                    <v-icon icon="    mdi mdi-account-arrow-right"></v-icon>
                                                </span>
                                            </template>
                                        </v-text-field>



                                    </div>

                                    <div>
                                        <v-text-field v-model="celular" variant="outlined" label="CELULAR ">
                                            <template v-slot:label>
                                                <span>
                                                    CELULAR
                                                    <v-icon icon="mdi mdi-phone"></v-icon>
                                                </span>
                                            </template>
                                        </v-text-field>

                                    </div>
                                    <div>
                                        <v-text-field v-model="adicional" variant="outlined" label="CELULAR ADICIONAL">
                                            <template v-slot:label>
                                                <span>
                                                    CELULAR ADICIONAL
                                                    <v-icon icon="mdi mdi-phone-plus"></v-icon>
                                                </span>
                                            </template>
                                        </v-text-field>
                                    </div>

                                    <div>
                                        <label class="block text-sm text-gray-600" for="alumno">Numero de Cuenta</label>
                                        <select id="diplomado" v-model="selectedTitular"
                                            class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500">
                                            <option disabled selected>Selecciona un Numero de Cuenta</option>
                                            <option v-for="titular in cuentaDeposito" :key="titular.id"
                                                :value="titular.id">
                                                {{ titular.titular }}
                                            <option>

                                                {{ titular.CLABE }}
                                            </option>
                                            </option>

                                        </select>
                                    </div>
                                    <div>
                                        <div class="mt-2">
                                            <label for="adicional"
                                                class="block mb-2 text-sm font-medium text-gray-900 dark:text-gray-300">Monto
                                                Inscripcion</label>
                                            <v-text-field v-model="monto_inscripcion"

                                            variant="outlined"
                                            type="number">$</v-text-field>
                                        </div>
                                    </div>
                                    <v-text-field label="Fecha de Inscripción" required readonly color="blue"
                                        v-model="fecha_inscripcion" type="date" variant="outlined"
                                        class="w-full px-4 py-2"></v-text-field>



                                    <v-text-field label="Fecha de Primer Colegiatura" required
                                        v-model="fecha_primer_pago_colegiatura" type="date" variant="outlined"
                                        class="w-full px-4 py-2"></v-text-field>
                                     </div>


                                <div class="mb-6">
                                    <label for="confirm_password" class="block mb-2 text-sm font-medium text-gray-900 dark:text-gray-300">Diplomado y Grupo</label>

                                    <select id="grupo" v-model="seleccionGrupo" @change="actualizarDiplomado"
                                        class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500">
                                        <option disabled selected>Selecciona un Grupo</option>
                                        <option v-for="grupo in Grupos" :key="grupo.id" :value="grupo.id">
                                            Diplomado: {{ grupo.nombre }} - Grupo: {{ grupo.grupo }} - Campaña: {{ grupo.campaña }}
                                        </option>
                                    </select>
                                </div>


                                <div class="mb-6">
                                    <label for="password"
                                        class="block mb-2 text-sm font-medium text-gray-900 dark:text-gray-300">Tutoria</label>
                                    <select id="tutor" v-model="selectedTutor" @change="diplomadoSeleccionado"
                                        class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500">
                                        <option> {{ $page.props.auth.user.name }}</option>
                                    </select>
                                </div>

                                <div class="mb-6">
                                    <label
                                        class="block mb-2 text-sm font-medium text-gray-900 dark:text-gray-300">Adminisones (Asesor)</label>
                                        <v-text-field  required readonly color="blue"
                                        v-model="asesor"  variant="outlined"
                                        class="w-full px-4 py-2">   {{ $page.props.auth.user.name }}</v-text-field>



                                </div>



                                <div class="flex items-start mb-6">
                                    <div class="flex items-center h-5">
                                        <input id="remember" type="checkbox" value=""
                                            class="w-4 h-4 border border-gray-300 rounded bg-gray-50 focus:ring-3 focus:ring-blue-300 dark:bg-gray-700 dark:border-gray-600 dark:focus:ring-blue-600 dark:ring-offset-gray-800"
                                            required>
                                    </div>
                                    <label for="remember"
                                        class="ml-2 text-sm font-medium text-gray-900 dark:text-gray-400">Los datos ingresados son correctos<a href="#"
                                            class="text-blue-600 hover:underline dark:text-blue-500">
                                           </a>.</label>
                                </div>
                                <div class="mt-4">
                                    <v-btn
                                    class="mb-8"
                                    color="green"
                                    size="large"
                                    variant="elevated"
                                    type="submit"

                                    block
                                >
                                    Enviar
                                </v-btn>

                                    <button
                                        class="middle none center mr-4 rounded-lg bg-red-500 py-3 px-6 font-sans text-xs font-bold uppercase text-white shadow-md shadow-red-500/20 transition-all hover:shadow-lg hover:shadow-red-500/40 focus:opacity-[0.85] focus:shadow-none active:opacity-[0.85] active:shadow-none disabled:pointer-events-none disabled:opacity-50 disabled:shadow-none"
                                        data-ripple-light="true">
                                        Vaciar
                                    </button>
                                </div>
                            </form>

                        </div>
                    </v-container>
                </template>
                <v-card> </v-card>
            </AuthenticatedLayout>
        </div>
    </div>
</template>

<style>
.mycard {
    /* width: 1400px; Eliminado */
    justify-content: center;
    text-align: center;
    position: absolute;
    top: 30%;
    left: 0;
    right: 0;
    transform: translateY(-50%);
    background-color: rgba(255, 255, 255, 0.9);
    z-index: -2;
}
</style>
<script>
import Estadisticas from "./Estadisticas.vue";

import swal from 'sweetalert';

export default {
    created() {
        this.obtenerAlumnos();
        this.obtenerDiplomados();
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
            mostrarDialog1: false,
            mostrarDialog2: false,
            mostrarDialog3: false,
            selectedAlumno: null,
            selectedDiplomado: null,
            selectedTitular: null,
            Grupos: [],
            nombre_alumno: null,
            celular:null,
            adicional:null,
            alumnos: [],
            Alumnos: [],
            search: "",
            NombreDiplomado: [],
            cuentaDeposito: [],
            idDiplomado:null,
        };
    },

    created() {
        this.obtenerAlumnos();
        this.obtenerDiplomados();
        this.obtenerNumeroCuenta();
        this.obtenerGrupos();
        this.setFechaActual();
    },
    methods: {
        actualizarDiplomado() {
        // Buscar el grupo seleccionado en el array de Grupos
        const grupoSeleccionado = this.Grupos.find(grupo => grupo.id === this.seleccionGrupo);
        // Asignar el ID del diplomado asociado al grupo seleccionado al modelo seleccionDiplomado
        this.seleccionDiplomado = grupoSeleccionado ? grupoSeleccionado.diplomado_id : null;
    },
        setFechaActual() {
            const today = new Date();
            const year = today.getFullYear();
            const month = String(today.getMonth() + 1).padStart(2, "0"); // Enero es 0
            const day = String(today.getDate()).padStart(2, "0");
            this.fecha_inscripcion = `${year}-${month}-${day}`;
        },

        alumnoSeleccionado() {
        },



        grupoSeleccionado() {
            this.diplomado_id = this.diplomado_id;
            this.seleccionGrupo = this.seleccionGrupo;
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
                asesor:1,
                tutor:1,
            };
            console.log("inscripcion datos enviados ", inscripcion);
            axios
                .post("/api/v1/inscripcion/crear", inscripcion)
                .then((res) => {
                    console.log(res);
                    swal("Inscripcion realizada !", "success");

                })
                .catch((err) => {
                    console.error(err);
                    swal("Llena los datos corretamente");

                });
        },

        obtenerGrupos() {
            axios
                .get("/api/v1/grupos/listar")
                .then((res) => {
                    this.Grupos = res.data.Grupos;
                    this.console.log(res);
                })
                .catch((err) => {
                    console.error(err);
                });
        },

        obtenerNumeroCuenta() {
            axios
                .get("/api/v1/cuentadeposito/index/2024/numero")
                .then((res) => {
                    this.cuentaDeposito = res.data.cuentaDeposito;
                    console.log(res);
                })
                .catch((err) => {
                    console.error(err);
                });
        },

        obtenerAlumnos() {
            axios
                .get("/api/v1/alumnos/index/2024/nombres")
                .then((res) => {
                    this.Alumnos = res.data.Alumnos;
                })
                .catch((err) => {
                    console.error(err);
                });
        },
        obtenerDiplomados() {
            axios
                .get("/api/v1/diplomados/index/2024/diplomados")
                .then((res) => {
                    this.NombreDiplomado = res.data.DiplomadoNombre;
                    console.log("Diplomados", res);
                })
                .catch((err) => {
                    console.error(err);
                });
        },
    },
};
</script>
<style scoped>
.watermark-container {
    position: relative;
    padding: 20px;
    border-radius: 8px;
    box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
    overflow: hidden; /* Asegura que el pseudo-elemento no sobresalga del contenedor */
}

.watermark-container::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background-image: url('/public/images/logo.jpg'); /* Reemplaza con la ruta real de tu logo */
    background-repeat: repeat;
    background-position: top left;
    filter: blur(8px); /* Aplica el desenfoque */
    z-index: -1; /* Asegura que el pseudo-elemento esté detrás del contenido */
}
</style>
