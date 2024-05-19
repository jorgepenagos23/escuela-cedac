<script setup>
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import { Head } from "@inertiajs/vue3";
import TablaAlumnos from "./TablaAlumnos.vue";

</script>

<template>
    <div>
        <!-- Contenido de tu vista -->
        <div class="max-w-7xl mx-auto p-6 lg:p-8">

            <Head title="Cedac" />
            <AuthenticatedLayout>
                <template #header>
                    <v-toolbar title="Dar de alta Pagos" color="light-green-darken-4
          ">
                        <v-toolbar-items>
                            <v-btn>Generar Reportes</v-btn>
                        </v-toolbar-items>

                        <v-divider class="mx-2" vertical></v-divider>

                        <v-btn icon="mdi-dots-vertical"></v-btn>
                    </v-toolbar>

                    <h2 class="font-semibold text-xl text-gray-800 leading-tight"></h2>
                    <v-container>
                        <div class="leading-loose">
                            <form class="max-w-7xl m-4 p-10 bg-white rounded shadow-xl" @submit.prevent="EnviarPago">
                                <p class="text-gray-800 font-medium">Captura el pago por Mensualidad</p>
                                <div class="">
                                    <label class="block text-sm text-gray-00" for="cus_name"></label>
                                    <v-text-field v-model="Descripcion" label="Descripcion"></v-text-field>
                                </div>

                                <div class="mt-2">
                                    <label class="block text-sm text-gray-600" for="cus_email">Monto de Mensualidad</label>
                                    <v-text-field v-model="monto_abono" label="Monto" type="number"></v-text-field>

                                </div>

                                <label class="block text-sm text-gray-600" for="cus_email">Alumno</label>

                               <select id="alumno" v-model="selectedAlumno"
                                @change="alumnoSeleccionado"
                                class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500">
                                <option disabled selected>Selecciona un alumno</option>
                                <option v-for="alumno in AlumnosEstadoPagar" :key="alumno.id" :value="alumno.id">{{ alumno.nombre_completo }}</option>
                            </select>
                            <label class="block text-sm text-gray-600" for="cus_email">Diplomado</label>

                            <select id="diplomado" v-model="selectedDiplomado"
                                @change="diplomadoSeleccionado"
                                class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500">
                                <option disabled selected>Selecciona un Diplomado</option>
                                <option v-for="diplomado in NombreDiplomado" :key="diplomado.id" :value="diplomado.id">{{ diplomado.nombre }}</option>
                            </select>




                                <div class="mt-2">
                                    <label class="block text-sm text-gray-600" for="alumno">Numero de Cuenta</label>
                                    <select id="diplomado" v-model="selectedTitular"
                                        class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500">
                                        <option disabled selected>Selecciona un Numero de Cuenta</option>
                                        <option v-for="titular in cuentaDeposito" :key="titular.id" :value="titular.id">
                                            {{ titular.titular }}</option>
                                    </select>
                                </div>


                                <div class="mt-6 flex justify-center">
                                    <v-date-picker  v-model="fecha"
                                        class="w-full px-4 py-2" ></v-date-picker>
                                </div>



                                <div class="mt-4">
                                    <button

                                        class="middle none center mr-4 rounded-lg bg-green-500 py-3 px-6 font-sans text-xs font-bold uppercase text-white shadow-md shadow-green-500/20 transition-all hover:shadow-lg hover:shadow-green-500/40 focus:opacity-[0.85] focus:shadow-none active:opacity-[0.85] active:shadow-none disabled:pointer-events-none disabled:opacity-50 disabled:shadow-none"
                                        data-ripple-light="true">
                                        Enviar
                                    </button>
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
            monto_abono: null,
            fecha: null,
            alumno_id: null,
            diplomado_id: null,
            mostrarDialog1: false,
            mostrarDialog2: false,
            mostrarDialog3: false,
            selectedAlumno: null,
            selectedDiplomado: null,
            selectedTitular: null,

            Descripcion: null,
            AlumnosEstadoPagar: [],
            Alumnos: [],
            search: "",
            NombreDiplomado: [],
            cuentaDeposito: [],
        };
    },



    created() {

        this.obtenerAlumnos();
        this.obtenerDiplomados();
        this.obtenerNumeroCuenta();

    },
    methods: {
        alumnoSeleccionado() {
        this.alumno_id = this.selectedAlumno;
    },

    diplomadoSeleccionado() {
        this.diplomado_id = this.selectedDiplomado;
    },
    EnviarPago() {
        const fechaFormateada = this.fecha.toISOString().split('T')[0];
    const inscripcion = {
        Fecha_PrimerContacto: fechaFormateada,
        pago_colegiatura: this.monto_abono,
        cuentadeposito: this.selectedTitular,
        diplomado_id: this.diplomado_id,
        alumno_id: this.alumno_id,
    };
    console.log('inscripcion datos enviados ',inscripcion);
    axios.post('api/v1/pagosabonos/crear', inscripcion)
        .then(res => {
            console.log(res);
            alert('Pago registrado con  con éxito');
        })
        .catch(err => {
            console.error(err);
            alert('Error al realizar el pago');
        });
},


        obtenerNumeroCuenta() {
            axios.get('/api/v1/cuentadeposito/index/2024/numero')
                .then(res => {
                    this.cuentaDeposito = res.data.cuentaDeposito;
                    console.log(res)
                })
                .catch(err => {
                    console.error(err);
                })

        },


        obtenerAlumnos() {
            axios.get('/api/v1/listar/alumnos/parapagos/')
                .then(res => {
                    this.AlumnosEstadoPagar = res.data.AlumnosEstadoPagar;


                })
                .catch(err => {
                    console.error(err);
                })

        },
        obtenerDiplomados() {
            axios.get('/api/v1/diplomados/index/2024/diplomados')
                .then(res => {
                    this.NombreDiplomado = res.data.DiplomadoNombre;
                    console.log('Diplomados', res)
                })
                .catch(err => {
                    console.error(err);
                })

        },
    }


};
</script>

