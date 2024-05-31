<script setup>
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import { Head } from "@inertiajs/vue3";
import Consulta3 from "./Consulta3.vue";
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

                <v-dialog max-width="1200">
                    <template v-slot:activator="{ props: activatorProps }">
                      <v-btn
                        v-bind="activatorProps"
                        color="indigo"
                        text="Colegiaturas "
                        variant="flat"
                        prepend-icon="mdi-currency-usd"
                      ></v-btn>
                    </template>

                    <template v-slot:default="{ isActive }">
                      <v-card title=" Busqueda por Periodo para Colegiaturas ">
                        <v-card-text>
                            <Consulta3></Consulta3>
                        </v-card-text>
                        <v-card-actions>
                          <v-spacer></v-spacer>

                          <v-btn text="Cerrar Consulta" @click="isActive.value = false"></v-btn>
                        </v-card-actions>
                      </v-card>
                    </template>
                  </v-dialog>


                  <v-dialog max-width="auto">
                    <template v-slot:activator="{ props: activatorProps }">
                      <v-btn
                        v-bind="activatorProps"
                        color="indigo"
                        text=" Base de Datos Liquidados "
                        variant="flat"
                        prepend-icon="mdi-database-search"
                      ></v-btn>
                    </template>

                    <template v-slot:default="{ isActive }">
                      <v-card title="">
                        <v-card-text>

        <v-card color="blue " flat>
            <v-card title="Alumnos Liquidados">
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
                          color="indigo"
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


            <v-card class="mt-6">

                <v-toolbar title="Recaudo de  Mensualidades por Diplomado (por MES ) " color="blue-darken-4
                ">
                    <v-toolbar-items> </v-toolbar-items>


                    <v-divider class="mx-2" vertical></v-divider>

                    <v-btn icon="mdi-dots-vertical"></v-btn>
                  </v-toolbar>

                  <v-container>
                        <v-card>
                            <div class="chart-container" style="height: 60vh;">
                                <canvas id="miGrafico"></canvas>
                          </div>
                        </v-card>
                  </v-container>
            </v-card>
<br><br>
<v-card class="mt-6">

            <v-toolbar title="Recaudo de Inscripciones por Diplomado  (por MES  ) " color="blue-darken-4
            ">
                <v-toolbar-items> </v-toolbar-items>

                <v-divider class="mx-2" vertical></v-divider>

                <v-btn icon="mdi-dots-vertical"></v-btn>
              </v-toolbar>

              <v-container>
                <div class="chart-container">
                    <canvas id="miGrafico2"></canvas>
                  </div>
              </v-container>
        </v-card>


        <v-card color="blue " flat>
            <v-toolbar title=" Alumnos Saldo Pendiente" color="red">
                <v-toolbar-items> </v-toolbar-items>

                <v-divider class="mx-2" vertical></v-divider>

                <v-btn icon="mdi-dots-vertical"></v-btn>
              </v-toolbar>

            <v-card title="Buscar matricula ">

                <template v-slot:text>
                    <v-text-field v-model="search" label="Buscar Abonos Netos Alumnos" prepend-inner-icon="mdi-magnify"
                        variant="outlined" hide-details single-line></v-text-field>
                </template>
            </v-card>
            <v-data-table :headers="headersPendientes" :items="pagosPendientesNetos" :search="search" class=""



           >
            </v-data-table>

        </v-card>

</div>



      </AuthenticatedLayout>
    </div>
  </div>
</template>




<script>
import axios from 'axios';
import Consulta3 from './Consulta3.vue'
import Chart from 'chart.js/auto'; // Importa solo lo necesario de Chart.js

export default {

	components: { Consulta3 },
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
            headersPendientes: [
                {
                    align: "start",
                    key: "name",
                    sortable: false,
                },
                { key: "id", title: "Id Alumno" },
                { key: "nombre_alumno", title: "Alumno" },
                { key: "Diplomado", title: "Diplomado" },
                { key: "Fecha", title: "Fechas de Pago" },
                { key: "costo_total", title: "Costo Diplomado" },
                { key: "TotaldePagos", title: "Total de Colegiaturas" },
                { key: "monto_inscripcion", title: "Inscripcion" },
                { key: "Saldo_Pendiente", title: "Saldo_Pendiente" },



            ],

            search:"",
                pagosAbonosNetos:[],
                pagosPendientesNetos:[]

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


    obtenerAlumnos_Abonos_Pendientes(){

axios.get('/api/v1/pagospendientes/api2024H')
.then(res => {
    this.pagosPendientesNetos = res.data.pagosPendientesNetos;
    console.log('pendiente pagar',res)
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
        const etiquetas = datosProductos.map(producto => `${producto.nombre_diplomado} (${producto.MesAnio})`);

        const nombresProductos = datosProductos.map(producto => producto.nombre_diplomado);
        const totalesPagados = datosProductos.map(producto => producto.TotalInscripcion);

        // Crear la gráfica de barras
        const ctx = document.getElementById('miGrafico2').getContext('2d');
        const miGrafico2 = new Chart(ctx, {
          type: 'bar',
          data: {
            labels: etiquetas,
            datasets: [{
              label: 'Total Pagado por Inscritos $',
              data: totalesPagados,
              backgroundColor: 'rgb(240, 128, 128)', // Color de fondo de las barras
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
        this.obtenerAlumnos_Abonos_Pendientes();

        axios.get('api/v1/pagosmensualidatestotal/api2024G')
  .then(response => {
    const datosProductos = response.data.SumaPagos;

    // Crear etiquetas que incluyan tanto el nombre del diplomado como el MesAnio
    const etiquetas = datosProductos.map(producto => `${producto.Diplomado} (${producto.MesAnio})`);
    const totalesPagados = datosProductos.map(producto => producto.TotalPagadoAbono);

    // Crear la gráfica de barras
    const ctx = document.getElementById('miGrafico').getContext('2d');
    const miGrafico = new Chart(ctx, {
      type: 'bar',
      data: {
        labels: etiquetas,
        datasets: [{
          label: 'Total Pagado $',
          data: totalesPagados,
          backgroundColor: '#3D9970', // Color de las barras con efecto 3D
borderColor: '#2E856E', // Color del borde de las barras
// Color sólido del borde de las barras

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
