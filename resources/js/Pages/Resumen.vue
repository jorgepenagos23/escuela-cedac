<script setup>
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import { Head } from "@inertiajs/vue3";
</script>

<template>
  <AuthenticatedLayout>
    <Head title="Reporte Financiero y Administrativo" />
    
    <div class="bg-gray-50 min-h-screen pb-10">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 pt-8">
        
        <div class="mb-8">
          <h2 class="text-2xl font-bold text-gray-800 flex items-center">
              <v-icon color="indigo-darken-2" class="mr-2">mdi-finance</v-icon>
              Dashboard Financiero y Operativo
          </h2>
          <p class="text-sm text-gray-500 mt-1">
            Métricas monetarias ejecutivas, proyecciones y estado de cuenta general de la institución.
          </p>
        </div>

        <!-- Tarjetas de KPIs Financieros -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
            <v-card variant="outlined" class="bg-white border-gray-200 shadow-sm rounded-xl">
                <div class="p-5 flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-500">Alumnos Activos</p>
                        <h3 class="text-3xl font-bold text-gray-800 mt-1">{{ totalMatriculasActivas }}</h3>
                    </div>
                    <div class="bg-blue-100 p-3 rounded-full">
                        <v-icon color="blue-darken-3" size="x-large">mdi-account-school-outline</v-icon>
                    </div>
                </div>
                <div class="px-5 py-2 bg-gray-50 text-xs text-gray-500 border-t">Cuota de Inscritos Formales</div>
            </v-card>

            <v-card variant="outlined" class="bg-white border-green-200 shadow-sm rounded-xl">
                <div class="p-5 flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-green-600">Ingresos x Mensualidad</p>
                        <h3 class="text-2xl font-bold text-green-700 mt-1">${{ totalIngresosColegiaturas.toLocaleString('en-US') }}</h3>
                    </div>
                    <div class="bg-green-100 p-3 rounded-full">
                        <v-icon color="green-darken-3" size="x-large">mdi-cash-multiple</v-icon>
                    </div>
                </div>
                <div class="px-5 py-2 bg-green-50 text-xs text-green-700 border-t border-green-100">Dinero en Banco Generado Mxn</div>
            </v-card>

            <v-card variant="outlined" class="bg-white border-indigo-200 shadow-sm rounded-xl">
                <div class="p-5 flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-indigo-600">Ingresos x Inscripciones</p>
                        <h3 class="text-2xl font-bold text-indigo-700 mt-1">${{ totalIngresosInscripciones.toLocaleString('en-US') }}</h3>
                    </div>
                    <div class="bg-indigo-100 p-3 rounded-full">
                        <v-icon color="indigo-darken-3" size="x-large">mdi-cash-register</v-icon>
                    </div>
                </div>
                <div class="px-5 py-2 bg-indigo-50 text-xs text-indigo-700 border-t border-indigo-100">Caja de Apertura (Enganches)</div>
            </v-card>

            <v-card variant="outlined" class="bg-white border-red-200 shadow-sm rounded-xl">
                <div class="p-5 flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-red-600">Cartera Vencida</p>
                        <h3 class="text-2xl font-bold text-red-700 mt-1">${{ totalCarteraVencida.toLocaleString('en-US') }}</h3>
                    </div>
                    <div class="bg-red-100 p-3 rounded-full">
                        <v-icon color="red-darken-3" size="x-large">mdi-alert-circle-outline</v-icon>
                    </div>
                </div>
                <div class="px-5 py-2 bg-red-50 text-xs text-red-700 border-t border-red-100">Saldos Estudiantiles por Cobrar</div>
            </v-card>
        </div>

        <!-- Graficas -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
            <v-card variant="outlined" class="bg-white border-gray-200 shadow-sm rounded-xl p-5">
                <h3 class="text-lg font-bold text-gray-700 mb-4 flex items-center">
                    <v-icon class="mr-2" color="green">mdi-chart-line</v-icon> Tendencia de Mensualidades Recaudadas
                </h3>
                <div class="relative h-64 w-full">
                    <canvas id="miGrafico"></canvas>
                </div>
            </v-card>

            <v-card variant="outlined" class="bg-white border-gray-200 shadow-sm rounded-xl p-5">
                <h3 class="text-lg font-bold text-gray-700 mb-4 flex items-center">
                    <v-icon class="mr-2" color="indigo">mdi-chart-bar</v-icon> Histórico de Inscripciones (Aperturas)
                </h3>
                <div class="relative h-64 w-full">
                    <canvas id="miGrafico2"></canvas>
                </div>
            </v-card>
        </div>

        <!-- Tablas Consolidación  -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
            <!-- Tabla Alumnos Deudores / Cartera Vencida -->
            <v-card variant="outlined" class="bg-white border-gray-200 shadow-sm rounded-xl overflow-hidden h-full">
                <div class="bg-red-50 border-b border-red-100 px-6 py-4 flex justify-between items-center">
                    <h3 class="text-md font-bold text-red-700 flex items-center">
                        <v-icon class="mr-2" color="red">mdi-account-alert</v-icon> Detalle Cartera Vencida
                    </h3>
                </div>
                
                <div class="p-4 border-b bg-white border-gray-100">
                    <v-text-field
                        v-model="searchPendientes"
                        placeholder="Buscar alumno deudor..."
                        variant="solo"
                        density="compact"
                        hide-details
                        prepend-inner-icon="mdi-magnify"
                        class="shadow-sm rounded bg-gray-50 border border-gray-200"
                    ></v-text-field>
                </div>

                <v-data-table
                    :headers="headersPendientes"
                    :items="pagosPendientesNetos"
                    :search="searchPendientes"
                    class="elevation-0 text-sm overflow-x-auto"
                    hover
                    :items-per-page="5"
                >
                    <template v-slot:item.Saldo_Pendiente="{ item }">
                        <span class="text-red-600 font-bold">${{ Number(item.Saldo_Pendiente).toLocaleString('en-US') }}</span>
                    </template>
                </v-data-table>
            </v-card>

            <!-- Tabla Alumnos Liquidados -->
            <v-card variant="outlined" class="bg-white border-gray-200 shadow-sm rounded-xl overflow-hidden h-full">
                <div class="bg-green-50 border-b border-green-100 px-6 py-4">
                    <h3 class="text-md font-bold text-green-700 flex items-center">
                        <v-icon class="mr-2" color="green">mdi-check-decagram</v-icon> Alumnos Salteados (Liquidación)
                    </h3>
                </div>

                <div class="p-4 border-b bg-white border-gray-100">
                    <v-text-field
                        v-model="searchLiquidados"
                        placeholder="Buscar alumno liquidado..."
                        variant="solo"
                        density="compact"
                        hide-details
                        prepend-inner-icon="mdi-magnify"
                        class="shadow-sm rounded bg-gray-50 border border-gray-200"
                    ></v-text-field>
                </div>

                <v-data-table
                    :headers="headersLiquidados"
                    :items="pagosAbonosNetos"
                    :search="searchLiquidados"
                    class="elevation-0 text-sm overflow-x-auto"
                    hover
                    :items-per-page="5"
                >
                    <template v-slot:item.TotalPagadoAbono="{ item }">
                        <span class="text-green-600 font-bold">${{ Number(item.TotalPagadoAbono).toLocaleString('en-US') }}</span>
                    </template>
                </v-data-table>
            </v-card>
        </div>

      </div>
    </div>
  </AuthenticatedLayout>
</template>

<script>
import axios from 'axios';
import Chart from 'chart.js/auto';

export default {
    data() {
        return {
            searchPendientes: "",
            searchLiquidados: "",
            pagosAbonosNetos: [],
            pagosPendientesNetos: [],
            
            // KPIs 
            totalMatriculasActivas: 0,
            totalIngresosColegiaturas: 0,
            totalIngresosInscripciones: 0,
            totalCarteraVencida: 0,

            headersPendientes: [
                { title: "Alumno Moroso / Deuda", key: "nombre_alumno", sortable: true },
                { title: "Diplomado Suscrito", key: "Diplomado", sortable: true },
                { title: "Saldo Vencido MXN", key: "Saldo_Pendiente", sortable: true },
            ],
            
            headersLiquidados: [
                { title: "Alumno Sano", key: "nombre_completo", sortable: true },
                { title: "Diplomado", key: "Diplomado", sortable: true },
                { title: "Abono Neto Histórico", key: "TotalPagadoAbono", sortable: true },
            ]
        };
    },
    mounted() {
        this.cargarDatosDashboard();
    },
    methods: {
        async cargarDatosDashboard() {
            try {
                // Matriculas Activas
                const matRes = await axios.get('/api/v1/matriculas/activas/2024');
                const activasData = matRes.data.data || [];
                if (activasData.length > 0) {
                    this.totalMatriculasActivas = activasData[0].Activas || 0;
                }

                // Alumnos Liquidados (históricos pagados)
                const liquidadosRes = await axios.get('/api/v1/pagosmensualidadespendientes/api2024H');
                this.pagosAbonosNetos = liquidadosRes.data.Alumnos_Abonos_Pagados || [];

                // Cartera Vencida (Alumnos con saldos)
                const pendientesRes = await axios.get('/api/v1/pagospendientes/api2024H');
                this.pagosPendientesNetos = pendientesRes.data.pagosPendientesNetos || [];
                
                // Calcular Cartera Vencida
                this.totalCarteraVencida = this.pagosPendientesNetos.reduce((acc, curr) => acc + Number(curr.Saldo_Pendiente || 0), 0);

                // Gráfica 1: Colegiaturas + Calcular Ingresos
                const colRes = await axios.get('/api/v1/pagosmensualidatestotal/api2024G');
                const datosCol = colRes.data.SumaPagos || [];
                
                this.totalIngresosColegiaturas = datosCol.reduce((acc, curr) => acc + Number(curr.TotalPagadoAbono || 0), 0);

                const etiCol = datosCol.map(p => `${p.Diplomado} (${p.MesAnio})`);
                const totalesCol = datosCol.map(p => p.TotalPagadoAbono);
                
                const ctxCol = document.getElementById('miGrafico');
                if(ctxCol) {
                    new Chart(ctxCol.getContext('2d'), {
                        type: 'line',
                        data: {
                            labels: etiCol,
                            datasets: [{
                                label: 'Volumen Recaudado',
                                data: totalesCol,
                                backgroundColor: 'rgba(74, 222, 128, 0.2)',
                                borderColor: '#22c55e',
                                borderWidth: 3,
                                fill: true,
                                tension: 0.4
                            }]
                        },
                        options: { responsive: true, maintainAspectRatio: false }
                    });
                }

                // Gráfica 2: Inscripciones + Calcular Ingresos
                const insRes = await axios.get('/api/v1/inscripciones/recaudacion/suma');
                const datosIns = insRes.data.sumaIncripciones || [];
                
                this.totalIngresosInscripciones = datosIns.reduce((acc, curr) => acc + Number(curr.TotalInscripcion || 0), 0);

                const etiIns = datosIns.map(p => `${p.nombre_diplomado} (${p.MesAnio})`);
                const totalesIns = datosIns.map(p => p.TotalInscripcion);

                const ctxIns = document.getElementById('miGrafico2');
                if(ctxIns){
                    new Chart(ctxIns.getContext('2d'), {
                        type: 'bar',
                        data: {
                            labels: etiIns,
                            datasets: [{
                                label: 'Ingreso Cajas',
                                data: totalesIns,
                                backgroundColor: '#6366f1',
                                borderRadius: 6,
                            }]
                        },
                        options: { responsive: true, maintainAspectRatio: false }
                    });
                }

            } catch (error) {
                console.error("Error cargando dashboard financiero: ", error);
            }
        }
    }
};
</script>
