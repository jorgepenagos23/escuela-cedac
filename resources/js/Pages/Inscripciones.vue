<script setup>
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import { Head } from "@inertiajs/vue3";
</script>

<template>
    <div>

        <!-- Contenido de tu vista -->
        <div class="max-w-7xl mx-auto p-6 lg:p-8">

            <Head title="Cedac" />
            <AuthenticatedLayout>
                <template #header>
                    <v-toolbar title="Inscripciones" color="indigo">
                        <v-toolbar-items>
                          <v-btn>Generar Reporte</v-btn>

                        </v-toolbar-items>

                        <v-divider class="mx-2" vertical></v-divider>

                        <v-btn icon="mdi-dots-vertical"></v-btn>
                      </v-toolbar>
                    <v-container>
                        <v-row align="center" justify="center">

                        </v-row>

                    </v-container>
                    <v-card color="blue-grey-lighten-5 " flat>
                        <v-card title="Barra de Busqueda">
                            <template v-slot:text>
                                <v-text-field v-model="search" label="Buscar Inscritos" prepend-inner-icon="mdi-magnify"
                                    variant="outlined" hide-details single-line></v-text-field>
                            </template>
                        </v-card>
                        <v-data-table :headers="headers" :items="alumnos_inscripcion" :search="search" class="bg-green-lighten-5" >
                        </v-data-table>
                    </v-card>
                </template>


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
        this.obtenerDiplomados();
    },

    components: {
        Estadisticas,
    },

    methods: {

        obtenerDiplomados() {
            axios
                .get("/api/v1/inscripciones_api2024E")
                .then((response) => {
                    this.alumnos_inscripcion = response.data.alumnos_inscripcion;

                    console.log(response);
                })
                .catch((err) => {
                    console.error(err);
                });
        },
    },

    data() {
        return {

            mostrarDialog1: false,
            mostrarDialog2: false,
            mostrarDialog3: false,

            headers: [
                {
                    align: "start",
                    key: "name",
                    sortable: false,
                },
                { key: "alumno_nombre", title: "Nombre del Alumno" },
                { key: "monto_inscripcion", title: "Inscripcion" },
                { key: "nombre_diplomado", title: "Diplomado" },
                { key: "fecha_inscripcion", title: "Fecha Inscripcion" },
                { key: "Titular", title: "Titular" },
                { key: "banco", title: "Banco" },


            ],

            alumnos_inscripcion: [],

            search: '',

        };
    },
};
</script>

<style scoped>
/* Estilos específicos para esta vista */
</style>
