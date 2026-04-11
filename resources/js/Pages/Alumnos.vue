<script setup>
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import { Head } from "@inertiajs/vue3";
</script>

<template>
  <AuthenticatedLayout>
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
          
          <div class="mt-4 md:mt-0 space-x-2 flex">
            <!-- Botón para Formato A (Admisiones) -->
            <v-btn
              prepend-icon="mdi-account-plus"
              color="indigo"
              variant="flat"
              @click="$refs.fileInputAdmissions.click()"
              :loading="importingAdmissions"
            >
              Cargar Admisiones
            </v-btn>
            <input
              type="file"
              ref="fileInputAdmissions"
              class="hidden"
              accept=".xlsx,.xls,.csv"
              @change="handleFileUpload($event, 'admissions')"
            />

            <!-- Botón para Formato B (Historial) -->
            <v-btn
              prepend-icon="mdi-history"
              color="success"
              variant="flat"
              @click="$refs.fileInputHistorical.click()"
              :loading="importingHistorical"
            >
              Cargar Histórico
            </v-btn>
            <input
              type="file"
              ref="fileInputHistorical"
              class="hidden"
              accept=".xlsx,.xls,.csv"
              @change="handleFileUpload($event, 'historical')"
            />
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
                        <v-chip color="info" size="small" variant="flat" prepend-icon="mdi-school">{{ item.nombre_diplomado }}</v-chip>
                    </template>
                    <template v-slot:item.telefono="{ item }">
                        <span class="font-medium text-gray-600"><v-icon size="small" class="mr-1">mdi-phone</v-icon>{{ item.telefono || 'N/A' }}</span>
                    </template>
                </v-data-table>
            </div>
        </v-card>

      </div>
    </div>
  </AuthenticatedLayout>
</template>

<script>
import axios from 'axios';
import { router } from '@inertiajs/vue3';

export default {
  data() {
    return {
      search: '',
      alumnos: [],
      importingAdmissions: false,
      importingHistorical: false,
      headers: [
        { title: "Matrícula", key: "matricula", sortable: true, width: "120px" },
        { title: "Nombre Completo del Estudiante", key: "nombre_completo", sortable: true },
        { title: "Programa Académico (Diplomado)", key: "nombre_diplomado", sortable: true },
        { title: "Fecha Ingreso", key: "fecha_nacimiento", align: "center" },
        { title: "Contacto", key: "telefono", sortable: false },
        { title: "Ref. Adicional", key: "correo", sortable: false },
      ]
    };
  },
  created() {
    this.obtenerAlumnos();
  },
  methods: {
    obtenerAlumnos() {
      axios.get('/api/v1/alumnos_api2024A/')
        .then(response => {
          this.alumnos = response.data.alumnos;
        })
        .catch(err => {
          console.error(err);
        });
    },
    handleFileUpload(event, type) {
      const file = event.target.files[0];
      if (!file) return;

      if (type === 'admissions') this.importingAdmissions = true;
      if (type === 'historical') this.importingHistorical = true;

      const formData = new FormData();
      formData.append('file', file);

      const url = type === 'admissions' ? '/alumnos/import/admissions' : '/alumnos/import/historical';

      router.post(url, formData, {
        onSuccess: () => {
          this.importingAdmissions = false;
          this.importingHistorical = false;
          this.obtenerAlumnos();
          alert('Importación completada exitosamente.');
        },
        onError: (errors) => {
          this.importingAdmissions = false;
          this.importingHistorical = false;
          alert('Hubo un error al importar: ' + Object.values(errors).join(', '));
        },
        onFinish: () => {
          this.importingAdmissions = false;
          this.importingHistorical = false;
          event.target.value = ''; // Reset input
        }
      });
    }
  }
};
</script>
