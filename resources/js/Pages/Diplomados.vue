<script setup>
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import { Head } from "@inertiajs/vue3";
</script>

<template>
  <div>
    <Estadisticas />
    <div class="h-screen">
      <Head title="Cedac" />
      <AuthenticatedLayout>
        <template #header>
          <v-toolbar title="Diplomados" color="indigo">
            <v-toolbar-items>
              <v-btn>Agregar Diplomado</v-btn>

              <v-btn>Editar</v-btn>
            </v-toolbar-items>

            <v-divider class="mx-2" vertical></v-divider>

            <v-btn icon="mdi-dots-vertical"></v-btn>
          </v-toolbar>

          <h2 class="font-semibold text-xl text-gray-800 leading-tight"></h2>
          <v-container>
            <v-row align="center" justify="center"> </v-row>
          </v-container>
        </template>
        <v-card> </v-card>

        <v-card color="white " flat>
          <div class="py-0">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
              <v-card title="Barra de Busqueda">
                <template v-slot:text>
                  <v-text-field
                    v-model="search"
                    label="Buscar diplomados"
                    prepend-inner-icon="mdi-magnify"
                    variant="outlined"
                    hide-details
                    single-line
                  ></v-text-field>
                </template>
              </v-card>
              <v-data-table :headers="headers" :items="diplomados" :search="search" class="bg-green-lighten-5
           
              ">
              </v-data-table>

            </div>
          </div>
        </v-card>
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
        .get("/api/v1/diplomados_api2024C")
        .then((response) => {
          this.diplomados = response.data.diplomados;

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
        { key: "nombre", title: "Nombre del Diplomado" },
        { key: "descripcion", title: "Descripcion" },
        { key: "duracion_mes", title: "Duracion Meses" },
        { key: "costo_total", title: "Costo" },
        { key: "requisitos", title: "Requisitos" },
      ],

      diplomados: [],

      search: "",
    };
  },
};
</script>

<style scoped>
.h-screen {
  height: 100vh; /* Establece la altura del contenedor al 100% del viewport height */
}
</style>
