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
            <v-toolbar-items>

                <v-dialog max-width="800">
                    <template v-slot:activator="{ props: activatorProps }">
                      <v-btn
                        v-bind="activatorProps"
                        color="surface-variant"
                        text="Diplomados "
                        variant="flat"
                        prepend-icon=" mdi-coffee-to-go-outline"
                      ></v-btn>
                    </template>

                    <template v-slot:default="{ isActive }">
                      <v-card title=" Consulta de 3 Productos Mas Vendidos">
                        <v-card-text>
                        </v-card-text>

                        <v-card-actions>
                          <v-spacer></v-spacer>

                          <v-btn text="Cerrar Consulta" @click="isActive.value = false"></v-btn>
                        </v-card-actions>
                      </v-card>
                    </template>
                  </v-dialog>


                  <v-dialog max-width="800">
                    <template v-slot:activator="{ props: activatorProps }">
                      <v-btn
                        v-bind="activatorProps"
                        color="surface-variant"
                        text="Grafica "
                        variant="flat"
                        prepend-icon="mdi-chart-box-multiple-outline"
                      ></v-btn>
                    </template>

                    <template v-slot:default="{ isActive }">
                      <v-card title="Consulta por Grafica">
                        <v-card-text>

                        </v-card-text>

                        <v-card-actions>
                          <v-spacer></v-spacer>

                          <v-btn text="Cerrar Consulta" @click="isActive.value = false"></v-btn>
                        </v-card-actions>
                      </v-card>
                    </template>
                  </v-dialog>


                  <v-dialog max-width="1200">
                    <template v-slot:activator="{ props: activatorProps }">
                        <v-btn
                          v-bind="activatorProps"
                          color="surface-variant"
                          text="Consulta por Fechas "
                          variant="flat"
                          prepend-icon="mdi-chart-box-multiple-outline"
                        ></v-btn>
                      </template>

                      <template v-slot:default="{ isActive }">
                        <v-card title="Consulta por fechas">
                          <v-card-text>

                          </v-card-text>

                          <v-card-actions>
                            <v-spacer></v-spacer>

                            <v-btn text="Cerrar Consulta" @click="isActive.value = false"></v-btn>
                          </v-card-actions>
                        </v-card>
                      </template>

                </v-dialog>


            </v-toolbar-items>

            <v-divider class="mx-2" vertical></v-divider>

            <v-btn icon="mdi-dots-vertical"></v-btn>
          </v-toolbar>


        </template>

        <div class="max-w-7xl mx-auto p-6 lg:p-8">


            <v-card>

                <v-toolbar title="Recaudo de  Mensualidades por Diplomado " color="indigo">
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

            <v-toolbar title="Recaudo de Inscripciones por Diplomado" color="indigo">
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

        <v-card color="blue " flat>
            <v-card title="Alumnos con Pagos Colegiaturas">
                <template v-slot:text>
                    <v-text-field v-model="search" label="Buscar Abonos Netos Alumnos" prepend-inner-icon="mdi-magnify"
                        variant="outlined" hide-details single-line></v-text-field>
                </template>
            </v-card>
            <v-data-table :headers="headers" :items="pagosAbonosNetos" :search="search" class="bg-white "
            :header-props="{ class: 'custom-header' }"



            >
            </v-data-table>

        </v-card>
 <v-card color="blue " flat>
            <v-card title="Alumnos Saldo Pendiente">
                <template v-slot:text>
                    <v-text-field v-model="search" label="Buscar Abonos Netos Alumnos" prepend-inner-icon="mdi-magnify"
                        variant="outlined" hide-details single-line></v-text-field>
                </template>
            </v-card>
            <v-data-table :headers="headersDeudas" :items="pagosPendientesNetos" :search="search" class="bg-red"



           >
            </v-data-table>

        </v-card>

</div>



      </AuthenticatedLayout>
    </div>
  </div>
</template>
<style>
.custom-header {
    background-color: rgb(0, 184, 18); /* Color de fondo del encabezado */
    color: rgb(248, 248, 248); /* Color del texto del encabezado */
  }
  .custom-header-pendiente {
    background-color: rgb(253, 29, 29); /* Color de fondo del encabezado */
    color: rgb(255, 249, 249); /* Color del texto del encabezado */
  }
</style>
<script>
import axios from 'axios';
import Chart from 'chart.js/auto'; // Importa solo lo necesario de Chart.js

export default {

    data(){
return {
        dialog1:false,

            headers: [
                {
                    align: "start",
                    key: "name",
                    sortable: false,
                },
                { key: "nombre_alumno", title: "Alumno" },
                { key: "Diplomado", title: "Diplomado" },
                { key: "TotaldePagos", title: "Colegiaturas Realizadas" },
                { key: "FechasColegiaturas", title: "Colegiaturas" },
                { key: "Fecha", title: "Fechas de Pago Colegiaturas" },
                { key: "costo_total", title: "Costo Diplomado" },
                { key: "Saldo Pendiente", title: "Saldo Pendiente por pagar" },
                { key: "monto_inscripcion", title: "Inscripcion" },




            ],
            headersDeudas: [
                {
                    align: "start",
                    key: "name",
                    sortable: false,
                },
                { key: "id_Diplomado", title: "Id" },
                { key: "nombre_completo", title: "Alumno" },
                { key: "Diplomado", title: "Diplomado" },
                { key: "TotalFechasAbono", title: "Pagos Pendientes" },
                { key: "FechasAbono", title: "Total Fechas de Pago" },
                { key: "costo_total", title: "Costo Diplomado" },
                { key: "TotalPagadoAbono", title: "Total de Abonos" },
                { key: "monto_inscripcion", title: "Inscripcion" },



            ],

            search:"",
                pagosAbonosNetos:[]
}

    },

methods:{

    obtenerAlumnos_Abonos_Pagados(){

        axios.get('/api/v1/pagosmensualidadespendientes/api2024H')
        .then(res => {
            this.pagosAbonosNetos = res.data.Alumnos_Abonos_Pagados;
            console.log(res)
        })
        .catch(err => {
            console.error(err);
        })

    },

    obtenerSumaInscripcion(){
        axios.get('/api/v1/inscripciones/recaudacion/suma')
      .then(response => {
        const datosProductos = response.data.sumaIncripciones; // Cambiar a response.data.SumaPagos

        // Extraer los nombres de los productos y los totales pagados
        const nombresProductos = datosProductos.map(producto => producto.nombre_diplomado);
        const totalesPagados = datosProductos.map(producto => producto.TotalInscripcion);

        // Crear la gráfica de barras
        const ctx = document.getElementById('miGrafico2').getContext('2d');
        const miGrafico2 = new Chart(ctx, {
          type: 'bar',
          data: {
            labels: nombresProductos,
            datasets: [{
              label: 'Total Pagado por Inscritos',
              data: totalesPagados,
              backgroundColor: 'rgba(16, 183, 250)', // Color de fondo de las barras
        borderColor: 'rgba(75, 192, 192, 1)', // Color del borde de las barras
              borderWidth: 1
            }]
          },
          options: {
            scales: {
              y: {
                beginAtZero: true // Comenzar el eje y desde cero
              }
            }
          }
        });
      })
      .catch(error => {
        console.error('Error al obtener los datos de la API:', error);
      });

    },


},

    created() {


        this.obtenerAlumnos_Abonos_Pagados();
        this.obtenerSumaInscripcion();





    axios.get('api/v1/pagosmensualidatestotal/api2024G')
      .then(response => {
        const datosProductos = response.data.SumaPagos; // Cambiar a response.data.SumaPagos

        // Extraer los nombres de los productos y los totales pagados
        const nombresProductos = datosProductos.map(producto => producto.Diplomado);
        const totalesPagados = datosProductos.map(producto => producto.TotalPagadoAbono);

        // Crear la gráfica de barras
        const ctx = document.getElementById('miGrafico').getContext('2d');
        const miGrafico = new Chart(ctx, {
          type: 'bar',
          data: {
            labels: nombresProductos,
            datasets: [{
              label: 'Total Pagado',
              data: totalesPagados,
              backgroundColor: 'rgb(88, 214, 141)', // Color de las barras
              borderColor: 'rgb(252, 7, 7)', // Color del borde de las barras
              borderWidth: 1
            }]
          },
          options: {
            scales: {
              y: {
                beginAtZero: true // Comenzar el eje y desde cero
              }
            }
          }
        });
      })
      .catch(error => {
        console.error('Error al obtener los datos de la API:', error);
      });

  }

};
</script>

<style>
/* Agrega estilos necesarios si es necesario */
</style>
