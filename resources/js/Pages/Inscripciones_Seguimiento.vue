<script setup>
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import { Head } from "@inertiajs/vue3";
</script>

<template>
  <div>
    <Estadisticas />
    <!-- Contenido de tu vista -->
    <div class="max-w-7xl mx-auto p-6 lg:p-8">
      <Head title="Cedac" />
      <AuthenticatedLayout>
        <template #header>
          <v-toolbar
            title="Dar de alta Inscripciones"
            color="light-green-darken-4
          "
          >
            <v-toolbar-items>
              <v-btn>Generar Reportes</v-btn>
            </v-toolbar-items>

            <v-divider class="mx-2" vertical></v-divider>

            <v-btn icon="mdi-dots-vertical"></v-btn>
          </v-toolbar>

          <h2 class="font-semibold text-xl text-gray-800 leading-tight"></h2>
          <v-container>
            <div class="leading-loose">
              <form class="max-w-7xl m-4 p-10 bg-white rounded shadow-xl">
                <p class="text-gray-800 font-medium">Captura el pago por inscripcion</p>
                <div class="">
                  <label class="block text-sm text-gray-00" for="cus_name"></label>
                  <v-text-field
                    v-model="Descripcion"
                    :rules="rules"
                    label="Descripcion"
                  ></v-text-field>
                </div>
                <div class="mt-2">
                  <label class="block text-sm text-gray-600" for="cus_email"
                    >Alumno</label
                  >
                  <select
                    id="countries"
                    class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500"
                  >
                    <option selected>Elige alumno</option>
                    <option value="US">0909090</option>
                  </select>
                </div>
                <div class="mt-2">
                  <label class="block text-sm text-gray-600" for="cus_email"
                    >Diplomado</label
                  >
                  <select
                    id="countries"
                    class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500"
                  >
                    <option selected>Elige diplomado</option>
                    <option value="US">0909090</option>
                  </select>
                </div>
                <div class="mt-2">
                    <label class="block text-sm text-gray-600" for="cus_email"
                      >Numero de Cuenta</label
                    >
                    <select
                      id="countries"
                      class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500"
                    >
                      <option selected>Elige diplomado</option>
                      <option value="US">0909090</option>
                    </select>
                  </div>


               <div class="mt-6">
                <v-text-field
                label="Fecha"
                required
                type="date"
                variant="solo"
                class="w-full px-4 py-2"
              ></v-text-field>
              

               </div>
                <div class="mt-2">
                  <label class="block text-sm text-gray-600" for="cus_email"
                    >Monto Incripcion</label
                  >
                  <v-text-field
                    v-model="monto_inscripcion"
                    :rules="rules"
                    label="Monto"
                  ></v-text-field>
                </div>

            
                <div class="mt-4">
                  <button
                    class="middle none center mr-4 rounded-lg bg-green-500 py-3 px-6 font-sans text-xs font-bold uppercase text-white shadow-md shadow-green-500/20 transition-all hover:shadow-lg hover:shadow-green-500/40 focus:opacity-[0.85] focus:shadow-none active:opacity-[0.85] active:shadow-none disabled:pointer-events-none disabled:opacity-50 disabled:shadow-none"
                    data-ripple-light="true"
                  >
                    Enviar
                  </button>
                  <button
                    class="middle none center mr-4 rounded-lg bg-red-500 py-3 px-6 font-sans text-xs font-bold uppercase text-white shadow-md shadow-red-500/20 transition-all hover:shadow-lg hover:shadow-red-500/40 focus:opacity-[0.85] focus:shadow-none active:opacity-[0.85] active:shadow-none disabled:pointer-events-none disabled:opacity-50 disabled:shadow-none"
                    data-ripple-light="true"
                  >
                    Button
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
    this.obtenerDiplomados();
  },

  components: {
    Estadisticas,
  },

  methods: {
    obtenerDiplomados() {
      axios
        .get("/api/v1/alumnos_api2024A/")
        .then((response) => {
          this.alumnos = response.data.alumnos;

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
        { key: "nombre_completo", title: "Nombre" },
        { key: "matricula", title: "Matricula" },
        { key: "nombre_diplomado", title: "Diplomado" },

        { key: "fecha_nacimiento", title: "Fecha de Nacimiento" },
        { key: "correo", title: "Correo" },
        { key: "telefono", title: "Telefono" },
        { key: "direccion", title: "Direccion" },
      ],

      alumnos: [],

      search: "",
    };
  },
};
</script>

<style scoped>
/* Estilos específicos para esta vista */
</style>
