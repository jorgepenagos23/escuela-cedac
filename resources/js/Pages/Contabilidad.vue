<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head } from '@inertiajs/vue3';
import { ref, onMounted, computed } from 'vue';
import axios from 'axios';
import * as XLSX from 'xlsx';

const pagos = ref([]);
const retiroTotal = ref(0);
const loading = ref(true);
const search = ref('');

const headers = [
  { title: '# Movimiento', key: 'id_pago', align: 'start' },
  { title: 'Alumno', key: 'nombre_alumno' },
  { title: 'Diplomado / Grupo', key: 'diplomado' },
  { title: 'Monto ($)', key: 'monto', align: 'end' },
  { title: 'Banco (Ingreso)', key: 'banco' },
  { title: 'Titular de Cuenta', key: 'titular_cuenta' },
  { title: 'Cajero Emisor', key: 'cajero' },
  { title: 'Fecha Operación', key: 'fecha_operacion' },
  { title: 'Fecha Sellado Sistema', key: 'fecha_ingreso_sistema' },
];

const fetchContabilidad = async () => {
  loading.value = true;
  try {
    const response = await axios.get('/api/v1/contabilidad/reporte');
    pagos.value = response.data.pagos;
    retiroTotal.value = response.data.retiro_total;
  } catch (error) {
    console.error("Error fetching accounting data", error);
  } finally {
    loading.value = false;
  }
};

const formatCurrency = (value) => {
  return new Intl.NumberFormat('es-MX', {
    style: 'currency',
    currency: 'MXN'
  }).format(value);
};

const formatDate = (dateString) => {
  if (!dateString) return '';
  const date = new Date(dateString);
  return new Intl.DateTimeFormat('es-MX', {
    day: '2-digit', month: '2-digit', year: 'numeric',
    hour: '2-digit', minute: '2-digit'
  }).format(date);
};

const exportToExcel = () => {
    // Preparar datos para excel
    const dataToExport = pagos.value.map(pago => ({
        '# Movimiento': pago.id_pago,
        'Alumno': pago.nombre_alumno,
        'Diplomado': pago.diplomado,
        'Monto Cobrado': Number(pago.monto),
        'Banco Receptor': pago.banco,
        'Titular Cuenta': pago.titular_cuenta,
        'Cajero Emisor': pago.cajero,
        'Fecha y Hora Operación Cliente': pago.fecha_operacion,
        'Fecha Sellado Auditoría (Sistema)': formatDate(pago.fecha_ingreso_sistema),
    }));

    // Crear un Worksheet
    const ws = XLSX.utils.json_to_sheet(dataToExport);
    
    // Crear un Libro y agregar el Worksheet
    const wb = XLSX.utils.book_new();
    XLSX.utils.book_append_sheet(wb, ws, "Contabilidad");

    // Escribir archivo y forzar descarga
    XLSX.writeFile(wb, `Reporte_Contabilidad_${new Date().getTime()}.xlsx`);
};

onMounted(() => {
  fetchContabilidad();
});
</script>

<template>
    <Head title="Módulo de Contabilidad" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex justify-between items-center w-full">
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">Módulo de Contabilidad y Auditoría</h2>
                
                <v-btn 
                    color="green-darken-3" 
                    prepend-icon="mdi-file-excel" 
                    @click="exportToExcel"
                    :disabled="loading || pagos.length === 0"
                    elevation="2"
                    rounded="lg"
                    class="font-weight-bold"
                >
                    Descargar Conciliación Excel
                </v-btn>
            </div>
        </template>

        <div class="py-12 bg-gray-50 min-h-screen">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                
                <!-- Retiro Total / Sumario Card -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
                    <v-card class="bg-indigo-900 border-b-4 border-indigo-500 overflow-hidden" elevation="4" rounded="xl">
                        <v-card-text class="pa-6 relative">
                            <div class="absolute right-0 top-0 opacity-10 mt-2 mr-2">
                                <v-icon size="100" color="white">mdi-safe</v-icon>
                            </div>
                            <div class="text-indigo-200 text-sm font-semibold tracking-wider uppercase mb-1">
                                Retiro Total (Suma de Ingresos Exitosos)
                            </div>
                            <div class="text-4xl font-black text-white flex items-end">
                                {{ formatCurrency(retiroTotal) }}
                                <span class="text-sm font-normal text-indigo-300 ml-2 mb-1">MXN</span>
                            </div>
                            <div class="mt-4 text-xs text-indigo-200 flex items-center">
                                <v-icon size="small" class="mr-1">mdi-information</v-icon>
                                Total acumulado de cobros vigentes registrados en sistema.
                            </div>
                        </v-card-text>
                    </v-card>
                </div>

                <!-- Tabla de Contabilidad -->
                <v-card elevation="2" rounded="xl" class="border border-gray-200">
                    <v-card-title class="bg-white border-b px-6 py-4 flex flex-col sm:flex-row justify-between items-center gap-4">
                        <div class="flex items-center text-gray-800">
                            <v-icon color="indigo" class="mr-2">mdi-history</v-icon>
                            <span class="text-lg font-bold">Registro Detallado de Movimientos de Caja</span>
                        </div>
                        <v-text-field
                            v-model="search"
                            append-inner-icon="mdi-magnify"
                            label="Buscar cobro, alumno, banco o cajero..."
                            single-line
                            hide-details
                            variant="outlined"
                            density="compact"
                            class="max-w-md w-full"
                            bg-color="gray-50"
                            rounded="lg"
                        ></v-text-field>
                    </v-card-title>
                    
                    <v-data-table
                        :headers="headers"
                        :items="pagos"
                        :search="search"
                        :loading="loading"
                        hover
                        class="border-t border-gray-100"
                    >
                        <template v-slot:item.monto="{ item }">
                            <span class="font-bold text-green-700">{{ formatCurrency(item.monto) }}</span>
                        </template>
                        
                        <template v-slot:item.cajero="{ item }">
                            <v-chip size="small" color="blue-grey" variant="flat">{{ item.cajero }}</v-chip>
                        </template>

                        <template v-slot:item.banco="{ item }">
                            <div class="font-medium text-gray-800">{{ item.banco }}</div>
                        </template>

                        <template v-slot:item.fecha_ingreso_sistema="{ item }">
                            <div class="text-xs text-gray-600 block">{{ formatDate(item.fecha_ingreso_sistema) }}</div>
                        </template>
                        
                        <template v-slot:item.diplomado="{ item }">
                            <v-chip size="x-small" color="primary" variant="outlined">{{ item.diplomado }}</v-chip>
                        </template>

                        <template v-slot:loading>
                            <div class="text-center py-6 text-gray-500">
                                <v-progress-circular indeterminate color="indigo" class="mb-2"></v-progress-circular>
                                <p>Cargando registros contables...</p>
                            </div>
                        </template>
                    </v-data-table>
                </v-card>

            </div>
        </div>
    </AuthenticatedLayout>
</template>
