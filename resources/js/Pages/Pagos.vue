<script setup>
import { ref } from 'vue';
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import { Head, usePage } from "@inertiajs/vue3";
</script>

<template>
  <AuthenticatedLayout>
    <Head title="Histórico de Colegiaturas" />
    
    <div class="bg-gray-50 min-h-screen pb-10">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 pt-8">
        
        <div class="mb-8">
          <h2 class="text-2xl font-bold text-gray-800 flex items-center">
              <v-icon color="indigo-darken-2" class="mr-2">mdi-format-list-bulleted-type</v-icon>
              Resumen de Colegiaturas Aplicadas
          </h2>
          <p class="text-sm text-gray-500 mt-1">
            Explora la tabla de movimientos contables, reimprime recibos consolidados y analiza el expediente completo de adeudos por alumno.
          </p>
        </div>

        <v-card variant="outlined" class="bg-white border-gray-200 shadow-sm rounded-xl overflow-hidden mb-6">
            <div class="bg-gray-100 px-6 py-4 flex flex-col md:flex-row items-center justify-between border-b gap-4">
                <div class="w-full md:w-5/12">
                    <v-text-field
                        v-model="busqueda"
                        placeholder="Buscar por Nombre del Alumno o ID"
                        variant="solo"
                        density="comfortable"
                        hide-details
                        prepend-inner-icon="mdi-magnify"
                        class="shadow-sm rounded-lg bg-white"
                        @keyup="aplicarFiltros"
                    ></v-text-field>
                </div>

                <!-- FILTRO DE DIPLOMADOS -->
                <div class="w-full md:w-5/12">
                    <v-select
                        v-model="filtroDiplomado"
                        :items="listaDiplomadosUnicos"
                        label="Filtrar Movimientos por Diplomado"
                        variant="solo"
                        density="comfortable"
                        hide-details
                        clearable
                        prepend-inner-icon="mdi-filter-variant"
                        class="shadow-sm rounded-lg bg-white"
                        @update:modelValue="aplicarFiltros"
                    ></v-select>
                </div>

                <div class="w-full md:w-2/12 flex space-x-2">
                    <v-btn @click="aplicarFiltros" color="indigo-darken-3" class="flex-1" variant="elevated" prepend-icon="mdi-text-search">Filtrar</v-btn>
                    <v-btn @click="limpiarBusqueda" color="grey-darken-1" variant="tonal" icon="mdi-eraser"></v-btn>
                </div>
            </div>

            <div class="p-4">
                <v-data-table
                    :headers="headers"
                    :items="pagosFiltrados"
                    :search="busqueda"
                    class="elevation-0 bg-transparent"
                    hover
                >
                    <!-- Personalización de columnas -->
                    <template v-slot:item.pago_colegiatura="{ item }">
                        <span class="text-green-600 font-bold">+${{ item.pago_colegiatura }} MXN</span>
                    </template>

                    <template v-slot:item.saldo="{ item }">
                        <span class="text-red-500 font-medium">Debe: ${{ item.saldo }}</span>
                    </template>

                    <template v-slot:item.Fecha_PrimerContacto="{ item }">
                        <span class="text-gray-600 font-mono text-sm">{{ item.Fecha_PrimerContacto }}</span>
                    </template>

                    <template v-slot:item.acciones="{ item }">
                        <div class="flex space-x-2 justify-center">
                            <!-- Boton Cargar PDF -->
                            <v-btn icon="mdi-file-pdf-box" size="small" color="red" variant="tonal" @click="descargarPDF(item.id)" title="Descargar PDF Autorizado"></v-btn>
                            
                            <!-- Botón de Expediente -->
                            <v-dialog max-width="750">
                                <template v-slot:activator="{ props: activatorProps }">
                                    <v-btn v-bind="activatorProps" icon="mdi-folder-account" size="small" color="indigo-darken-2" variant="tonal" @click="consultarHistorialDeuda(item)" title="Ver Expediente de Deuda"></v-btn>
                                </template>
                                
                                <template v-slot:default="{ isActive }">
                                    <v-card rounded="xl" class="overflow-hidden">
                                        <div class="bg-indigo-900 px-6 py-4 flex justify-between items-center text-white">
                                            <div>
                                                <h3 class="text-lg font-medium flex items-center">
                                                    <v-icon color="success" class="mr-2">mdi-account-search</v-icon>
                                                    Expediente de Deuda en Sistema
                                                </h3>
                                                <p class="text-indigo-200 text-xs">Alumno: {{ item.Nombre }}</p>
                                            </div>
                                            <v-chip color="red" size="small" variant="flat">Saldo Restante: ${{ item.saldo }}</v-chip>
                                        </div>

                                        <v-card-text class="bg-gray-50 p-6">
                                            <div class="mb-5 p-4 bg-white border border-gray-200 rounded-xl shadow-sm">
                                                <h4 class="text-gray-700 font-bold mb-3 border-b pb-2">Información de la Matrícula</h4>
                                                <div class="grid grid-cols-2 gap-4 text-sm">
                                                    <div><span class="text-gray-500">Curso:</span> <span class="font-medium text-indigo-700">{{ item.nombre_diplomado }}</span></div>
                                                    <div><span class="text-gray-500">Saldo Restante Actual:</span> <span class="font-bold text-red-600">${{ item.saldo }} MXN</span></div>
                                                    <div class="col-span-2 text-center mt-3">
                                                        <v-btn color="indigo-darken-4" size="small" prepend-icon="mdi-school" variant="outlined" @click="abrirCrudsGlobal(item.Nombre)">Analizar Archivo Global del Estudiante</v-btn>
                                                        <div class="text-xs text-gray-400 mt-1">Busca a este alumno en las demás vistas.</div>
                                                    </div>
                                                </div>
                                            </div>

                                            <h4 class="text-gray-700 font-bold mb-3 border-b pb-2">Histórico Cronológico de Abonos (Este curso)</h4>
                                            
                                            <v-card variant="outlined" class="bg-white max-h-64 overflow-y-auto">
                                                <v-list v-if="historialPagos.length > 0">
                                                    <v-list-item v-for="(pago, idx) in historialPagos" :key="idx" class="border-b">
                                                        <template v-slot:prepend><v-icon color="green">mdi-cash-check</v-icon></template>
                                                        <v-list-item-title class="font-bold text-sm text-gray-800">
                                                            Abono Aplicado: +${{ pago.pago_colegiatura }} MXN
                                                        </v-list-item-title>
                                                        <v-list-item-subtitle class="text-xs text-gray-500 mt-1">
                                                            Folio Central #{{ pago.idpago }} | Operación: {{ pago.Fecha_PrimerContacto }} | Gestor: {{ pago.Tutor }}
                                                        </v-list-item-subtitle>
                                                        <template v-slot:append>
                                                            <v-btn size="x-small" icon="mdi-file-pdf-box" variant="text" color="red" @click="descargarPDF(pago.idpago)" title="Reimprimir Recibo PDF"></v-btn>
                                                        </template>
                                                    </v-list-item>
                                                </v-list>
                                                <div v-else class="text-center text-gray-400 py-6">
                                                    <v-icon size="large" class="mb-2">mdi-cloud-search-outline</v-icon><br>Cargando información del expediente...
                                                </div>
                                            </v-card>
                                        </v-card-text>

                                        <v-card-actions class="bg-gray-100 px-6 py-3 justify-end border-t">
                                            <v-btn variant="elevated" color="grey-darken-3" prepend-icon="mdi-close" @click="isActive.value = false; historialPagos = []">Cerrar Expediente</v-btn>
                                        </v-card-actions>
                                    </v-card>
                                </template>
                            </v-dialog>
                        </div>
                    </template>
                </v-data-table>
            </div>
        </v-card>

      </div>
    </div>
  </AuthenticatedLayout>
</template>

<script>
import swal from "sweetalert";
import axios from 'axios';

export default {
  data() {
    return {
      busqueda: "",
      filtroDiplomado: null,
      pagosOriginales: [],
      pagosFiltrados: [],
      historialPagos: [],
      headers: [
        { title: "ID Sist.", key: "id", sortable: true, align: "start", width: "80px" },
        { title: "Expediente del Alumno", key: "Nombre", sortable: true },
        { title: "Abono Pagado", key: "pago_colegiatura", sortable: true },
        { title: "F. Operación", key: "Fecha_PrimerContacto", sortable: true },
        { title: "Diplomado Suscrito", key: "nombre_diplomado", sortable: true },
        { title: "Saldo Restante", key: "saldo", sortable: true },
        { title: "Tutor Caja", key: "Tutor", sortable: true },
        { title: "Acciones / Consulta", key: "acciones", sortable: false, align: "center" },
      ]
    };
  },
  computed: {
    listaDiplomadosUnicos() {
      const unicos = new Set();
      this.pagosOriginales.forEach(item => {
          if(item.nombre_diplomado) unicos.add(item.nombre_diplomado);
      });
      return Array.from(unicos);
    }
  },
  created() {
    this.obtenerPagosGlobales();
  },
  methods: {
    obtenerPagosGlobales() {
      axios.get("/api/v1/pagos_mensualidades_api2024F")
        .then((response) => {
          this.pagosOriginales = response.data.PagosconMensualidades;
          this.pagosFiltrados = [...this.pagosOriginales];
        })
        .catch((err) => console.error(err));
    },

    aplicarFiltros() {
      let resultados = this.pagosOriginales;
      if (this.filtroDiplomado) {
          resultados = resultados.filter(p => p.nombre_diplomado === this.filtroDiplomado);
      }
      this.pagosFiltrados = resultados;
    },

    limpiarBusqueda() {
      this.busqueda = "";
      this.filtroDiplomado = null;
      this.pagosFiltrados = [...this.pagosOriginales];
    },

    descargarPDF(idDocumento) {
        window.open('/pagos/' + idDocumento + '/pdf', '_blank');
    },

    consultarHistorialDeuda(pagoReferencia) {
        this.historialPagos = []; // reset
        axios.get(`/api/v1/mostrar/alumno/status/${pagoReferencia.alumno_id}`)
            .then(res => {
                this.historialPagos = res.data.pagosColegiaturaAlumno2;
            })
            .catch(err => {
                swal("Error", "No se pudo traer el histórico del estudiante desde sistema...", "error");
                console.error(err);
            });
    },

    abrirCrudsGlobal(nombreAlumno) {
        // Viaja a la vista principal de Alumnos Global inyectándole al cajero el query base
        window.location.href = `/alumnos`;
    }
  }
};
</script>
