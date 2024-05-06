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
          <v-toolbar title="Estadisticas" color="indigo">
            <v-toolbar-items> </v-toolbar-items>

            <v-divider class="mx-2" vertical></v-divider>

            <v-btn icon="mdi-dots-vertical"></v-btn>
          </v-toolbar>

      
        </template>

        <div class="max-w-5xl mx-auto p-6 lg:p-8">


            <v-card>

                <v-toolbar title="Total Inscripciones" color="indigo">
                    <v-toolbar-items> </v-toolbar-items>
        
                    <v-divider class="mx-2" vertical></v-divider>
        
                    <v-btn icon="mdi-dots-vertical"></v-btn>
                  </v-toolbar>
        
                  <v-container>
                    <div>
                      <!-- Contenedor del segundo gráfico -->
                      <canvas id="miGrafico" width="900" height="100"></canvas>
                      <!-- Aquí se renderizará el segundo gráfico -->
                    </div>
                  </v-container>
            </v-card>
<br><br>
        <v-card>

            <v-toolbar title="Total Mensualidades" color="indigo">
                <v-toolbar-items> </v-toolbar-items>
    
                <v-divider class="mx-2" vertical></v-divider>
    
                <v-btn icon="mdi-dots-vertical"></v-btn>
              </v-toolbar>
    
              <v-container>
                <div>
                  <!-- Contenedor del segundo gráfico -->
                  <canvas id="miGrafico2" width="900" height="100"></canvas>
                  <!-- Aquí se renderizará el segundo gráfico -->
                </div>
              </v-container>
        </v-card>
</div>

      </AuthenticatedLayout>
    </div>
  </div>
</template>

<script>
import axios from "axios";
import Chart from "chart.js/auto";

export default {
  mounted() {
    // Llama al método para obtener los datos y crear el primer gráfico
    this.obtenerDiplomados();
  },
  methods: {
    obtenerDiplomados() {
      axios
        .get("/api/v1/pagos_mensualidades_api2024F")
        .then((response) => {
          this.PagosconMensualidades = response.data.PagosconMensualidades;
          console.log(response);
          // Llama a la función para crear el gráfico
          this.crearGrafico();
          // Llama al método para crear el segundo gráfico después de asegurarse de que el elemento esté disponible
          this.crearGrafico2();
        })
        .catch((err) => {
          console.error(err);
        });
    },
    // Función para crear el primer gráfico
    crearGrafico() {
      const ctx = document.getElementById("miGrafico").getContext("2d");
      new Chart(ctx, {
        type: "bar",
        data: {
          labels: ["Label1", "Label2", "Label3"],
          datasets: [
            {
              label: "Mi Gráfico",
              data: [10, 20, 30],
              backgroundColor: [
                "rgba(206, 16, 16 )",
                "rgba(39, 109, 240)",
                "rgba(249, 208, 23)",
              ],
              borderColor: [
                "rgba(255, 99, 132, 1)",
                "rgba(54, 162, 235, 1)",
                "rgba(255, 206, 86, 1)",
              ],
              borderWidth: 1,
            },
          ],
        },
        options: {
          scales: {
            y: {
              beginAtZero: true,
            },
          },
        },
      });
    },
    // Función para crear el segundo gráfico
    crearGrafico2() {
      const ctx = document.getElementById("miGrafico2").getContext("2d");
      new Chart(ctx, {
        type: "bar",
        data: {
          labels: ["Label1", "Label2", "Label3"],
          datasets: [
            {
              label: "Mi Segundo Gráfico",
              data: [15, 25, 35],
              backgroundColor: [
                "rgba(206, 16, 16 )",
                "rgba(39, 109, 240)",
                "rgba(249, 208, 23)",
              ],
              borderColor: [
                "rgba(255, 99, 132, 1)",
                "rgba(54, 162, 235, 1)",
                "rgba(255, 206, 86, 1)",
              ],
              borderWidth: 1,
            },
          ],
        },
        options: {
          scales: {
            y: {
              beginAtZero: true,
            },
          },
        },
      });
    },
  },
  data() {
    return {
      headers: [
        { align: "start", key: "name", sortable: false },
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
      search: "",
    };
  },
};
</script>

<style scoped>
/* Estilos específicos para esta vista */
</style>
