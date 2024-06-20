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
                          text=" Colegiaturas Pendientes"
                          variant="flat"
                          prepend-icon="mdi-chart-box-multiple-outline"
                        ></v-btn>
                      </template>

                      <template v-slot:default="{ isActive }">
                        <v-card title="Alumnos Saldo Pendiente">
                          <v-card-text>
                            <div class="bg-blue-500 border border-blue-400 text-white px-4 py-3 rounded relative" role="alert">
                                <strong class="font-bold"></strong>
                                <span class="block sm:inline">Buscar alumno</span>
                                <div class="absolute inset-y-0 right-0 pr-3 flex items-center">
                                  <!-- Barra de búsqueda -->
                                  <input v-model="search" type="text" class="border border-gray-300 rounded-md py-1 px-2 focus:outline-none focus:ring-blue-500 focus:border-blue-500 block w-full sm:text-sm" placeholder="Buscar...">

                                  <!-- Filtro por diplomado -->
                                  <select v-model="selectedDiplomado" class="ml-2 border border-gray-300 rounded-md py-1 px-2 focus:outline-none focus:ring-blue-500 focus:border-blue-500 block w-48 sm:text-sm">
                                    <option value="">Todos los diplomados</option>
                                    <!-- Aquí puedes iterar sobre tus opciones de diplomados -->
                                    <option v-for="diplomado in listaDiplomados" :key="diplomado.id" :value="diplomado.id">{{ diplomado.nombre_diplomado }}</option>
                                </select>
                                </div>
                              </div>

                              <div class="flex flex-col mt-6">
                                <div class="-my-2 overflow-x-auto sm:-mx-6 lg:-mx-8">
                                  <div class="py-2 align-middle inline-block min-w-full sm:px-6 lg:px-8">
                                    <div class="shadow overflow-hidden border-b border-gray-200 sm:rounded-lg">
                                      <table class="min-w-full divide-y divide-gray-200">
                                        <thead class="bg-gray-50">
                                          <tr>
                                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                              Alumno
                                            </th>
                                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                              Diplomado
                                            </th>
                                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                              Colegiaturas Realizadas
                                            </th>

                                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                Saldo Pendiente por pagar
                                              </th>
                                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                              Colegiaturas
                                            </th>
                                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                              Fechas de Pago Colegiaturas
                                            </th>

                                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                Costo Diplomado
                                              </th>
                                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                              Inscripción
                                            </th>
                                          </tr>
                                        </thead>
                                        <tbody class="bg-white divide-y divide-gray-200">
                                          <tr v-for="(item, index) in filteredItems" :key="index">
                                            <td class="px-6 py-4 whitespace-nowrap">
                                              <div class="text-sm text-gray-900">{{ item.nombre_alumno }}</div>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                              <div class="text-sm text-gray-900">{{ item.Diplomado }}</div>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                              <div class="text-sm text-gray-900">{{ item.TotaldePagos }}</div>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <div class="text-sm text-red-900">${{ item.Saldo_Pendiente }}</div>
                                              </td>

                                            <td class="px-6 py-4 whitespace-nowrap">
                                              <div class="text-sm text-gray-900">{{ item.FechasColegiaturas }}</div>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                              <div class="text-sm text-gray-900">{{ item.Fecha }}</div>
                                            </td>


                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <div class="text-sm text-gray-900">${{ item.costo_total }}</div>
                                              </td>

                                            <td class="px-6 py-4 whitespace-nowrap">
                                              <div class="text-sm text-gray-900">${{ item.monto_inscripcion }}</div>
                                            </td>
                                          </tr>
                                        </tbody>
                                      </table>
                                    </div>
                                  </div>
                                </div>
                              </div>

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
            <div class="grid grid-cols-1 gap-5 mt-6 sm:grid-cols-2 lg:grid-cols-4">
                  <div class="p-4 transition-shadow border rounded-lg shadow-sm hover:shadow-lg">
                    <div class="flex items-start justify-between">
                      <div class="flex flex-col space-y-2">
                        <span class="text-black-400 ">Matriculas Activas</span>
                        <li v-for="(matricula, index ) in matriculasActivas" :key="index">
                            <span class="inline-block px-2 text-sm text-black bg-green-300 rounded">     Activas: {{ matricula.Activas }}</span>
                        </li>
                      </div>
                      <div class="p-10 bg-gray-200 rounded-md"></div>
                    </div>
                    <div>
                      <span></span>
                    </div>
                  </div>



                  <div class="p-4 transition-shadow border rounded-lg shadow-sm hover:shadow-lg">
                    <div class="flex items-start justify-between">
                      <div class="flex flex-col space-y-2">
                        <div v-for="diplomado in DiplomadoNombre" :key="diplomado.id">
                            <ul class="text-black-400">   {{ diplomado.nombre }}</ul>
                        </div>
                      </div>
                      <div class="p-10 bg-gray-200 rounded-md"></div>
                    </div>
                    <div>
                     </div>
                  </div>






              </div>



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

            <v-card class="mt-6">

            <v-toolbar title="Recaudo de Inscripciones por Diplomado  (por MES  ) " color="blue-darken-4 ">
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
        listaDiplomados: [], // Lista de opciones de diplomados

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
                pagosPendientesNetos:[],
                matriculasActivas:[],
                DiplomadoNombre:[]
}

    },
    computed: {
  filteredItems() {
    // Aplica los filtros según la búsqueda y el diplomado seleccionado
    let filteredItems = this.pagosPendientesNetos.filter(item => {
      // Filtra por búsqueda
      let matchSearch = item.nombre_alumno.toLowerCase().includes(this.search.toLowerCase());

      // Filtra por diplomado si se ha seleccionado uno
      let matchDiplomado = !this.selectedDiplomado || item.id_Diplomado === this.selectedDiplomado;

      return matchSearch && matchDiplomado;
    });

    return filteredItems;
  }
},
methods:{

    obtenerDiplomados(){

        axios.get('/api/v1/diplomados/index/2024/diplomados')
        .then(res => {

            this.DiplomadoNombre = res.data.DiplomadoNombre;

            console.log(res)
        })
        .catch(err => {
            console.error(err);
        })


    },

    obtenerMatriculasActivas(){
        axios.get('/api/v1/matriculas/activas/2024')
        .then(res => {

            this.matriculasActivas = res.data.data;

            console.log(res)
        })
        .catch(err => {
            console.error(err);
        })


    },


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
        this.obtenerMatriculasActivas();
        this.obtenerDiplomados();

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
