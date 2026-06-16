<script setup>
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import { Head } from "@inertiajs/vue3";
import ErpTopbar from "@/Components/ErpTopbar.vue";
</script>

<template>
  <AuthenticatedLayout>
    <ErpTopbar modulo="Alumnos" titulo="Alumnos Liquidados" />
    <Head title="Directorio Global de Alumnos" />

    <div class="bg-gray-50 min-h-screen pb-10">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 pt-8">
        
        <div class="mb-8 flex flex-col md:flex-row md:items-center md:justify-between">
          <div>
              <h2 class="text-2xl font-bold text-gray-800 flex items-center">
                  <v-icon color="indigo-darken-2" class="mr-2">mdi-account-group-outline</v-icon>
                  Padrón Global de Estudiantes
              </h2>
              <p class="text-sm text-gray-500 mt-1">
                Buscador maestro. Encuentra rápido a cualquier alumno ingresando su nombre, matrícula o diplomado asignado.
              </p>
          </div>
          
          <div class="mt-4 md:mt-0 space-x-2">
            <!-- Funciones Administrativas Pendientes (Opcional CRUD independiente) -->
          </div>
        </div>

        <v-card variant="outlined" class="bg-white border-gray-200 shadow-sm rounded-xl overflow-hidden mb-6">
            <div class="bg-gray-100 px-6 py-4 flex flex-col md:flex-row items-center border-b">
                <div class="w-full">
                    <v-text-field
                        v-model="search"
                        placeholder="Buscar por Nombre, Matrícula o Diplomado..."
                        variant="solo"
                        density="comfortable"
                        hide-details
                        prepend-inner-icon="mdi-account-search"
                        class="shadow-sm rounded-lg bg-white"
                        autofocus
                    ></v-text-field>
                </div>
            </div>

            <div class="p-4">
                <v-data-table
                    :headers="headers"
                    :items="alumnos"
                    :search="search"
                    class="elevation-0 bg-transparent text-gray-800"
                    hover
                >
                    <template v-slot:item.matricula="{ item }">
                        <span class="font-mono text-indigo-700 font-bold">#{{ item.matricula.toString().padStart(5, '0') }}</span>
                    </template>
                    <template v-slot:item.nombre_diplomado="{ item }">
                        <v-chip color="green" size="small" variant="flat" prepend-icon="mdi-check-decagram">{{ item.nombre_diplomado }}</v-chip>
                    </template>
                    <template v-slot:item.telefono="{ item }">
                        <span class="font-medium text-gray-600"><v-icon size="small" class="mr-1" color="green">mdi-whatsapp</v-icon>{{ item.telefono || 'N/A' }}</span>
                    </template>
                    <template v-slot:item.acciones="{ item }">
                        <v-btn color="indigo" size="small" variant="outlined" prepend-icon="mdi-folder-account" @click="verExpediente(item)">
                            Ver Expediente
                        </v-btn>
                    </template>
                </v-data-table>
            </div>
        </v-card>

      </div>
    </div>

    <!-- Modal del Expediente -->
    <v-dialog v-model="dialogExpediente" max-width="900px">
        <v-card class="rounded-xl overflow-hidden">
            <v-card-title class="bg-indigo-700 text-white pa-4 flex items-center justify-between">
                <span class="text-h6 font-bold flex items-center">
                    <v-icon class="mr-2">mdi-folder-account-outline</v-icon>
                    Expediente del Estudiante
                </span>
                <v-btn icon="mdi-close" variant="text" @click="dialogExpediente = false" color="white"></v-btn>
            </v-card-title>
            
            <v-card-text class="bg-gray-50 pa-6">
                <div v-if="cargandoExpediente" class="text-center py-10">
                    <v-progress-circular indeterminate color="indigo"></v-progress-circular>
                    <p class="mt-4 text-gray-500">Recopilando historial completo...</p>
                </div>
                <div v-else>
                    <div class="mb-6 flex items-center bg-white p-4 rounded-lg shadow-sm border border-gray-100">
                        <v-avatar color="indigo-100" size="64" class="mr-4">
                            <v-icon size="32" color="indigo-700">mdi-school</v-icon>
                        </v-avatar>
                        <div>
                            <h3 class="text-xl font-bold text-gray-800">{{ alumnoSeleccionado?.nombre_completo }}</h3>
                            <p class="text-gray-500 text-sm mt-1">
                                <v-icon size="small">mdi-phone</v-icon> {{ alumnoSeleccionado?.telefono || 'Sin celular' }} |
                                <v-icon size="small">mdi-email</v-icon> {{ alumnoSeleccionado?.correo || 'Sin correo' }}
                            </p>
                        </div>
                    </div>

                    <h4 class="text-lg font-bold text-gray-700 mb-4 border-b pb-2">Historial Académico (Diplomados y Cursos)</h4>
                    
                    <div v-if="expediente.length === 0" class="text-gray-500 italic text-center py-4">No hay registros detallados disponibles.</div>
                    
                    <div v-for="(inscripcion, index) in expediente" :key="index" class="bg-white rounded-lg shadow-sm border border-gray-200 mb-6 overflow-hidden">
                        <div class="bg-gray-100 px-4 py-3 border-b border-gray-200 flex justify-between items-center">
                            <div class="font-bold text-indigo-800 flex items-center">
                                <v-icon color="indigo" class="mr-2">mdi-certificate</v-icon>
                                {{ inscripcion.diplomado?.nombre || 'Diplomado Desconocido' }}
                            </div>
                            <div class="text-sm">
                                <v-chip v-if="inscripcion.saldo <= 0" color="green" size="small" variant="flat" prepend-icon="mdi-check">Liquidado</v-chip>
                                <v-chip v-else color="red" size="small" variant="flat" prepend-icon="mdi-alert">Adeudo: ${{ Number(inscripcion.saldo).toLocaleString() }}</v-chip>
                            </div>
                        </div>
                        
                        <div class="p-4 grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <p class="text-sm font-bold text-gray-600 mb-2 border-b pb-1">Información de Ingreso</p>
                                <ul class="text-sm text-gray-700 space-y-2">
                                    <li><strong>Fecha:</strong> {{ inscripcion.fecha_inscripcion }}</li>
                                    <li><strong>Identificador único:</strong> Matrícula #{{ inscripcion.id.toString().padStart(5, '0') }}</li>
                                    <li><strong>Monto de Inscripción Pagado:</strong> ${{ Number(inscripcion.monto_inscripcion).toLocaleString() }}</li>
                                    <li><strong>Inscrito por:</strong> <v-chip size="x-small">{{ inscripcion.usuario_tutor?.name || inscripcion.usuario_asesor?.name || 'Sistema Auto' }}</v-chip></li>
                                </ul>
                            </div>
                            <div>
                                <p class="text-sm font-bold text-gray-600 mb-2 border-b pb-1">Desglose de Mensualidades ({{ inscripcion.pagos?.length || 0 }})</p>
                                <div v-if="inscripcion.pagos && inscripcion.pagos.length > 0" class="max-h-40 overflow-y-auto pr-2">
                                    <ul class="text-sm text-gray-700 space-y-2">
                                        <li v-for="pago in inscripcion.pagos" :key="pago.id" class="flex flex-col bg-gray-50 px-3 py-2 rounded border border-gray-100">
                                            <div class="flex justify-between items-center w-full">
                                                <span><v-icon size="x-small" color="green" class="mr-1">mdi-cash-check</v-icon> {{ pago.Fecha_PrimerContacto }}</span>
                                                <span class="font-bold text-green-700">+${{ Number(pago.pago_colegiatura).toLocaleString() }}</span>
                                            </div>
                                            <div class="text-xs text-gray-500 mt-1 flex items-center">
                                                <v-icon size="x-small" class="mr-1">mdi-account-tie</v-icon> Cobrado por: <strong class="ml-1 text-indigo-700">{{ pago.usuario_tutor?.name || 'Sistema / Desconocido' }}</strong>
                                            </div>
                                        </li>
                                    </ul>
                                </div>
                                <div v-else class="text-xs text-gray-400 italic py-2">Sin historial de pagos registrados en este diplomado.</div>
                            </div>
                        </div>
                        
                        <!-- Panel de Certificado (Solo si liquidó el saldo) -->
                        <div class="bg-gray-50 p-3 border-t flex justify-between items-center bg-green-50" v-if="inscripcion.saldo <= 0">
                            <span class="text-xs font-medium text-green-800"><v-icon size="small" color="green">mdi-information</v-icon> Estudiante cumple los requisitos financieros para titulación.</span>
                            <v-btn 
                                color="green-darken-3" 
                                variant="flat" 
                                size="small"
                                prepend-icon="mdi-printer" 
                                @click="imprimirCertificado(inscripcion.id)"
                            >
                                Expedir Certificado
                            </v-btn>
                        </div>
                    </div>
                </div>
            </v-card-text>
        </v-card>
    </v-dialog>

  </AuthenticatedLayout>
</template>

<script>
import axios from 'axios';

export default {
  data() {
    return {
      search: '',
      alumnos: [],
      headers: [
        { title: "ID.", key: "matricula", sortable: true, width: "90px" },
        { title: "Alumno Egresado/Liquidado", key: "nombre_completo", sortable: true },
        { title: "Diplomado Pagado", key: "nombre_diplomado", sortable: true },
        { title: "Fecha de Alta", key: "fecha_apertura", align: "center" },
        { title: "Fecha de Último Pago", key: "fecha_liquidacion", align: "center" },
        { title: "Contacto", key: "telefono", sortable: false },
        { title: "Acciones", key: "acciones", sortable: false },
      ],
      dialogExpediente: false,
      cargandoExpediente: false,
      alumnoSeleccionado: null,
      expediente: []
    };
  },
  created() {
    this.obtenerAlumnos();
  },
  methods: {
    obtenerAlumnos() {
      axios.get('/api/v1/alumnos-liquidados')
        .then(response => {
          this.alumnos = response.data.alumnos;
        })
        .catch(err => {
          console.error(err);
        });
    },
    verExpediente(item) {
        this.alumnoSeleccionado = item;
        this.dialogExpediente = true;
        this.cargandoExpediente = true;
        
        axios.get('/api/v1/alumnos-liquidados/expediente?nombre=' + encodeURIComponent(item.nombre_completo))
            .then(response => {
                this.expediente = response.data.expediente;
            })
            .catch(err => {
                console.error('Error al cargar expediente', err);
            })
            .finally(() => {
                this.cargandoExpediente = false;
            });
    },
    imprimirCertificado(idInscripcion) {
        window.open('/alumnos-liquidados/certificado/' + idInscripcion, '_blank');
    }
  }
};
</script>
