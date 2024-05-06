<script setup>
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import { Head } from "@inertiajs/vue3";
</script>

<template>
    <div>
  

        <!-- Contenido de tu vista -->
        <div class="max-w-11xl mx-auto p-6 lg:p-8">

            <Head title="Cedac" />
            <AuthenticatedLayout>
                <template #header>
                    <v-toolbar title="Pagos Mensualidades" color="indigo">
                        <v-toolbar-items>

                        </v-toolbar-items>

                        <v-divider class="mx-2" vertical></v-divider>

                        <v-btn icon="mdi-dots-vertical"></v-btn>
                      </v-toolbar>
                    <v-container>


                    </v-container>
                        <v-card color="blue-grey-lighten-5" flat>
                        <v-card title="Barra de Busqueda">
                            <template v-slot:text>
                                <v-text-field v-model="search" label="Buscar Inscritos" prepend-inner-icon="mdi-magnify"
                                    variant="outlined" hide-details single-line></v-text-field>
                            </template>
                        </v-card>
                        <v-data-table :headers="headers" :items="PagosconMensualidades" :search="search" class="white" >
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
                .get("/api/v1/pagos_mensualidades_api2024F")
                .then((response) => {
                    this.PagosconMensualidades = response.data.PagosconMensualidades;

                    console.log(response);
                })
                .catch((err) => {
                    console.error(err);
                });
        },
    },

    data() {
        return {


            headers: [
                {
                    align: "start",
                    key: "name",
                    sortable: false,
                },
                { key: "Nombre", title: "Alumno" },
                { key: "monto_abono", title: "Monto Abono" },
                { key: "fecha_abono", title: "Fecha " },
                { key: "descripcion", title: "Detalles" },
                { key: "nombre_diplomado", title: "Diplomado" },
                { key: "numero_cuenta", title: "No Cuenta" },
                { key: "Titular", title: "Titular" },
                { key: "banco", title: "Banco" },



            ],

            PagosconMensualidades: [],

            search: '',

        };
    },
};
</script>

<style scoped>
/* Estilos específicos para esta vista */
</style>
