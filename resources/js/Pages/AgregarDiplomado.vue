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
          <v-toolbar title="Dar de alta Alumnos" color="indigo">
            <v-toolbar-items>

              <v-btn
              color="green"
              prepend-icon="mdi-microsoft-excel"
              >Subir Excel</v-btn>

            </v-toolbar-items>

            <v-divider class="mx-2" vertical></v-divider>

            <v-btn icon="mdi-dots-vertical"></v-btn>
          </v-toolbar>

          <h2 class="font-semibold text-xl text-gray-800 leading-tight"></h2>
          <v-container>
            <div class="leading-loose">
              <form class="max-w-7xl m-4 p-10 bg-white rounded shadow-xl">
                <p class="text-gray-800 font-medium">Datos del Diplomado</p>
                <div class="">
                  <label class="block text-sm text-gray-00" for="cus_name">Nombre Completo</label>
                  <input
                    class="w-full px-5 py-1 text-gray-700 bg-gray-200 rounded"
                    id="cus_name"
                    name="cus_name"
                    type="text"
                    required=""
                    placeholder="Nombre Completo"
                    aria-label="Name"
                  />
                </div>
                <div class="mt-2">
                  <label class="block text-sm text-gray-600" for="cus_email">Correo</label>
                  <input
                    class="w-full px-5 py-4 text-gray-700 bg-gray-200 rounded"
                    id="cus_email"
                    name="cus_email"
                    type="text"
                    required=""
                    placeholder="Correo"
                    aria-label="Email"
                  />
                </div>
                <div class="mt-2">
                  <label class="block text-sm text-gray-600" for="cus_email"
                    >Direccion</label
                  >
                  <input
                    class="w-full px-2 py-2 text-gray-700 bg-gray-200 rounded"
                    id="cus_email"
                    name="cus_email"
                    type="text"
                    required=""
                    placeholder="Direccion"
                    aria-label="Email"
                  />
                </div>


                <div class="inline-block mt-2 -mx-1 pl-1 w-1/2">
                    <label class="block text-sm text-gray-600" for="cus_email">Telefono</label>


                  <input
                    class="w-full px-2 py-2 text-gray-700 bg-gray-200 rounded"
                    id="cus_email"
                    name="cus_email"
                    type="text"
                    required=""
                    placeholder="Telefono"
                    aria-label="Email"
                  />
                </div>


                <p class="mt-4 text-gray-800 font-medium">Diplomado al que ingresa</p>

                <div                     class="w-full px-2 py-2 text-gray-700 bg-white rounded"
                >
                  <select id="countries" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500">
                    <option selected>Elige un diplomado</option>
                    <option value="US">Diplomado 1 States</option>
                    <option value="CA">Diplomado 2</option>
                    <option value="FR">Diplomado 3</option>
                    <option value="DE">Diplomado 4</option>
                </select>

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
