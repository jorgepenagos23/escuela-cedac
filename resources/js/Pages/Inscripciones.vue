<script setup>
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import { Head } from "@inertiajs/vue3";
const customFilter = (items, search) => {
  if (!search.trim()) return items;

  const normalizedSearch = search.toLowerCase();

  return items.filter(item => {
    // Aquí puedes personalizar qué campos quieres buscar en tus elementos
    return Object.values(item).some(value => {
      if (typeof value === 'string') {
        return value.toLowerCase().includes(normalizedSearch);
      }
      return false;
    });
  });
};

</script>

<template>
    <div class="watermark-container">


        <!-- Contenido de tu vista -->
        <div class="max-w-9xl mx-auto p-6 lg:p-8">

            <Head title="Cedac" />
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
                        <v-data-table
                        :headers="headers"
                        :items="alumnos_inscripcion"
                        :search="search"
                        class="bg-white"
                        :header-props="{ class: 'custom-header' }"
                        :item-props="{ class: 'custom-cell' }"
                      >
                      </v-data-table>
                    </v-card>


        </div>
    </div>
</template>
<style>
.custom-header {
    background-color: rgb(211, 255, 12); /* Color de fondo del encabezado */
    color: rgb(0, 0, 0); /* Color del texto del encabezado */
  }

  .custom-cell {
    background-color: lightblue; /* Color de fondo de las celdas */
  }
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

                { key: "nombre_alumno", title: "NOMBRE DEL ALUMNO" },
                { key: "celular", title: "CELULAR" },
                {key:  "adicional", title: "CELULAR ADICIONAL"},
                { key:"nombre_diplomado" , title:"DIPLOMADO" },
                {key: "Asesor", title:"ASESOR"},
                { key: "Tutor" ,title: "TUTOR"},
                { key: "fecha_inscripcion" ,title: "FECHA DE INSCRIPCION"},
                {key:  "fecha_primer_pago_colegiatura" , title:"PRIMER PAGO DE COLEGIATURA"},
                { key: "banco" ,title: "Banco"},
                { key: "numero_cuenta" ,title: "No Cuenta"},
                { key: "CLABE" ,title: "CLABE"},
                { key: "Titular" ,title: "Titular"},
                {key:  "saldo" , title:"Saldo"}





            ],

            alumnos_inscripcion: [],

            search: '',

        };
    },
};
</script>

<style scoped>
.watermark-container::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background-image: url('/public/images/des6.jpg'); /* Reemplaza con la ruta real de tu logo */
    background-repeat: repeat;
    background-position: top left;
    z-index: -1; /* Asegura que el pseudo-elemento esté detrás del contenido */
}
</style>
