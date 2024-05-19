<script setup>
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import { Head } from "@inertiajs/vue3";
</script>

<template>
  <div class="watermark-container">
    <!-- Contenido de tu vista -->
    <div class="max-w-11xl mx-auto p-6 lg:p-8">
      <Head title="Cedac" />
      <v-toolbar title="Tabla de Pagos Colegiaturas  " color="indigo">
        <v-toolbar-items> </v-toolbar-items>

        <v-divider class="mx-2" vertical></v-divider>

        <v-btn icon="mdi-dots-vertical"></v-btn>
      </v-toolbar>
      <v-container> </v-container>
      <v-card color="blue-grey-lighten-5" flat>
        <v-card title="Barra de Busqueda">
          <template v-slot:text>
            <v-text-field
              v-model="search"
              label="Buscar Inscritos"
              prepend-inner-icon="mdi-magnify"
              variant="outlined"
              hide-details
              single-line
            ></v-text-field>
          </template>
        </v-card>
        <v-data-table
          :headers="headers"
          :items="PagosconMensualidades"
          :search="search"
          class="white"
          :header-props="{ class: 'custom-header' }"
        >
        </v-data-table>
      </v-card>
    </div>
  </div>
</template>
<style>
.custom-header {
  background-color: rgb(0, 255, 195); /* Color de fondo del encabezado */
  color: rgb(0, 0, 0); /* Color del texto del encabezado */
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

.watermark-container::before {
  content: "";
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background-image: url("/public/images/des5.jpg"); /* Reemplaza con la ruta real de tu logo */
  background-repeat: repeat;
  background-position: top left;
  z-index: -1; /* Asegura que el pseudo-elemento esté detrás del contenido */
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
        { key: "id", title: "Id Pago" },
        { key: "Nombre", title: "NOMBRE DEL ALUMNO" },
        { key: "pago_colegiatura", title: "Colegiatura" },
        { key: "Fecha_PrimerContacto", title: "Fecha" },
        { key: "status", title: "Status" },
        { key: "nombre_diplomado", title: "Diplomado" },
        { key: "Tutor", title: "Tutor" },
        { key: "numero_cuenta", title: "No Cuenta" },
        { key: "CLABE", title: "CLABE" },
        { key: "Titular", title: "Titular" },
        { key: "banco", title: "Banco" },
        { key: "saldo", title: "saldo" },
      ],

      PagosconMensualidades: [],

      search: "",
    };
  },
};
</script>

<style scoped>
/* Estilos específicos para esta vista */
</style>
