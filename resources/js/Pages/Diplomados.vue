<script setup>
import { ref } from 'vue';
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import { Head } from "@inertiajs/vue3";
import ErpTopbar from "@/Components/ErpTopbar.vue";
</script>

<template>
  <AuthenticatedLayout>
    <ErpTopbar modulo="Catálogos" titulo="Portafolio de Diplomados" />
    <Head title="Directorio de Diplomados" />
    
    <div class="bg-gray-50 min-h-screen pb-10">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 pt-8">
        
        <div class="mb-8 flex flex-col md:flex-row md:items-center md:justify-between">
          <div class="flex items-center gap-4">
              <v-avatar size="64" rounded class="bg-white shadow">
                  <img src="/images/logo-cedac.jpg" alt="CEDAC Logo" class="h-full w-full object-cover">
              </v-avatar>
              <div>
                  <h2 class="text-2xl font-bold text-gray-800 flex items-center">
                      <v-icon color="indigo-darken-2" class="mr-2">mdi-school-outline</v-icon>
                      Portafolio de Cursos y Diplomados
                  </h2>
                  <p class="text-sm text-gray-500 mt-1">
                    Visualiza y agrega nuevos paquetes educativos que se ofertarán a los prospectos.
                  </p>
              </div>
          </div>
          
          <div class="mt-4 md:mt-0">
              <v-dialog v-model="modalAgregar" max-width="600" persistent>
                  <template v-slot:activator="{ props: activatorProps }">
                      <v-btn v-if="$page.props.auth.user.permissions?.includes('crear_diplomados')" v-bind="activatorProps" color="indigo-darken-3" variant="elevated" prepend-icon="mdi-plus-circle" size="large">
                          Agregar Nuevo Diplomado
                      </v-btn>
                  </template>
                  
                  <v-card rounded="xl">
                      <div class="bg-indigo-900 px-6 py-4 flex justify-between items-center text-white">
                          <h3 class="text-lg font-medium flex items-center">
                              <v-icon class="mr-2">mdi-book-education-outline</v-icon>
                              {{ isEditing ? 'Editar Diplomado y Campaña' : 'Alta de Nuevo Diplomado' }}
                          </h3>
                      </div>

                      <v-card-text class="p-6 bg-gray-50">
                          <form @submit.prevent="guardarDiplomado">
                              <v-text-field
                                  v-model="nuevo.nombre"
                                  label="Nombre Oficial del Diplomado"
                                  variant="outlined"
                                  density="comfortable"
                                  bg-color="white"
                                  prepend-inner-icon="mdi-format-title"
                                  required
                                  class="mb-3"
                              ></v-text-field>

                              <div class="grid grid-cols-2 gap-4 mb-3">
                                  <v-text-field
                                      v-model="nuevo.duracion_mes"
                                      label="Duración (Ej. 6 meses)"
                                      variant="outlined"
                                      density="comfortable"
                                      bg-color="white"
                                      prepend-inner-icon="mdi-calendar-clock"
                                      required
                                  ></v-text-field>
                                  <v-text-field
                                      v-model="nuevo.costo_total"
                                      label="Aportación Total"
                                      prefix="$"
                                      type="number"
                                      variant="outlined"
                                      density="comfortable"
                                      bg-color="white"
                                      required
                                  ></v-text-field>
                              </div>

                              <div class="grid grid-cols-2 gap-4 mb-3">
                                  <v-text-field
                                      v-model="nuevo.campaña"
                                      label="Campaña (Ej. 2026)"
                                      variant="outlined"
                                      density="comfortable"
                                      bg-color="white"
                                      prepend-inner-icon="mdi-bullhorn-outline"
                                  ></v-text-field>
                                  <v-text-field
                                      v-model="nuevo.grupo"
                                      label="Grupo (Ej. A, B, G1)"
                                      variant="outlined"
                                      density="comfortable"
                                      bg-color="white"
                                  ></v-text-field>
                              </div>

                              <v-select
                                  v-model="nuevo.tutor_id"
                                  :items="asesores"
                                  item-title="name"
                                  item-value="id"
                                  label="Asignar un Tutor Base a la Campaña"
                                  variant="outlined"
                                  density="comfortable"
                                  bg-color="white"
                                  prepend-inner-icon="mdi-account-tie"
                                  clearable
                                  class="mb-3"
                              ></v-select>

                              <v-textarea
                                  v-model="nuevo.requisitos"
                                  label="Requisitos de Documentación / Perfil"
                                  variant="outlined"
                                  bg-color="white"
                                  rows="3"
                                  helper-text="Opcional. Separe con comas o guiones."
                              ></v-textarea>

                              <div class="bg-blue-50 p-3 mt-4 rounded-lg flex text-xs text-blue-800 items-start">
                                  <v-icon size="small" class="mr-2 flex-shrink-0">mdi-information</v-icon>
                                  El diplomado quedará disponible de manera inmediata para relacionarle campañas publicitarias o alumnos nuevos.
                              </div>

                              <div class="flex justify-end space-x-3 mt-6">
                                  <v-btn variant="text" color="gray" @click="cerrarModal">Cancelar</v-btn>
                                  <v-btn type="submit" :loading="cargandoEnvio" color="success" prepend-icon="mdi-content-save">{{ isEditing ? 'Guardar Cambios' : 'Registrar Ahora' }}</v-btn>
                              </div>
                          </form>
                      </v-card-text>
                  </v-card>
              </v-dialog>
          </div>
        </div>

        <v-card variant="outlined" class="bg-white border-gray-200 shadow-sm rounded-xl overflow-hidden mb-6">
            <div class="bg-gray-100 px-6 py-4 flex flex-col md:flex-row items-center border-b">
                <div class="w-full md:w-6/12">
                    <v-text-field
                        v-model="search"
                        placeholder="Buscar por Nombre del Curso o Descripción..."
                        variant="solo"
                        density="comfortable"
                        hide-details
                        prepend-inner-icon="mdi-magnify"
                        class="shadow-sm rounded-lg bg-white"
                    ></v-text-field>
                </div>
            </div>

            <div class="p-4">
                <v-data-table
                    :headers="headers"
                    :items="diplomados"
                    :search="search"
                    class="elevation-0 bg-transparent text-gray-800"
                    hover
                >
                    <template v-slot:item.duracion_mes="{ item }">
                        <v-chip color="info" size="small" variant="flat" prepend-icon="mdi-calendar-range">{{ item.duracion_mes }}</v-chip>
                    </template>
                    <template v-slot:item.costo_total="{ item }">
                        <span class="text-green-600 font-bold">${{ item.costo_total }} MXN</span>
                    </template>
                    <template v-slot:item.requisitos="{ item }">
                        <span class="text-xs text-gray-500 whitespace-pre-wrap">{{ item.requisitos || 'No especificados' }}</span>
                    </template>
                    <template v-slot:item.campaña="{ item }">
                        <v-chip v-if="item.campaña" color="deep-purple-lighten-4" size="small" variant="flat">{{ item.campaña }}</v-chip>
                        <span v-else class="text-xs text-gray-400">Sin asignar</span>
                    </template>
                    <template v-slot:item.grupo="{ item }">
                        <v-chip v-if="item.grupo" color="blue-lighten-4" size="small" variant="flat">Gpo: {{ item.grupo }}</v-chip>
                        <span v-else class="text-xs text-gray-400">--</span>
                    </template>
                    <template v-slot:item.tutor_nombre="{ item }">
                        <span class="text-xs font-semibold text-gray-600"><v-icon size="x-small" class="mr-1">mdi-account-tie</v-icon>{{ item.tutor_nombre || 'Sin asignar' }}</span>
                    </template>
                    <template v-slot:item.acciones="{ item }">
                        <v-btn v-if="$page.props.auth.user.permissions?.includes('actualizar_datos_maestros')"
                               icon="mdi-pencil" size="small" color="primary" variant="text" @click="abrirEditar(item)"></v-btn>
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
      search: "",
      modalAgregar: false,
      cargandoEnvio: false,
      diplomados: [],
      asesores: [],
      isEditing: false,
      editId: null,
      nuevo: {
          nombre: '',
          duracion_mes: '',
          costo_total: null,
          requisitos: '',
          campaña: '',
          grupo: '',
          tutor_id: null
      },
      headers: [
        { title: "ID", key: "id", sortable: true, align: "start", width: "80px" },
        { title: "Nombre del Diplomado", key: "nombre", sortable: true },
        { title: "Campaña", key: "campaña", sortable: true },
        { title: "Grupo Asignado", key: "grupo", sortable: true },
        { title: "Tutor Base", key: "tutor_nombre", sortable: true },
        { title: "Dur.", key: "duracion_mes", align: "center", sortable: true },
        { title: "Aportación General", key: "costo_total", align: "center", sortable: true },
        { title: "Acciones", key: "acciones", sortable: false, align: "center" },
      ]
    };
  },
  created() {
    this.obtenerDiplomados();
    this.obtenerAsesores();
  },
  methods: {
    obtenerAsesores() {
      axios.get('/api/v1/listar/asesores')
        .then(res => { this.asesores = res.data.asesores; })
        .catch(err => console.error(err));
    },
    obtenerDiplomados() {
      axios.get("/api/v1/diplomados_api2024C")
        .then((response) => {
          this.diplomados = response.data.diplomados;
        })
        .catch((err) => {
          console.error(err);
        });
    },

    cerrarModal() {
        this.modalAgregar = false;
        this.isEditing = false;
        this.editId = null;
        this.nuevo = { nombre: '', duracion_mes: '', costo_total: null, requisitos: '', campaña: '', grupo: '', tutor_id: null };
    },

    abrirEditar(item) {
        this.isEditing = true;
        this.editId = item.id;
        this.nuevo = {
            nombre: item.nombre,
            duracion_mes: item.duracion_mes,
            costo_total: item.costo_total,
            requisitos: item.requisitos,
            campaña: item.campaña || '',
            grupo: item.grupo || '',
            tutor_id: item.tutor_id || null
        };
        this.modalAgregar = true;
    },

    guardarDiplomado() {
        if(!this.nuevo.nombre || !this.nuevo.duracion_mes || !this.nuevo.costo_total) {
            swal("Campos Faltantes", "Todos los campos principales (Nombre, Duración y Costo) son obligatorios.", "warning");
            return;
        }

        this.cargandoEnvio = true;

        const requestObj = this.isEditing 
            ? axios.put(`/api/v1/diplomado/${this.editId}`, this.nuevo)
            : axios.post('/api/v1/diplomado/crear', this.nuevo);

        requestObj
            .then(res => {
                swal("Excelente", this.isEditing ? "Diplomado actualizado exitosamente" : "El diplomado se registró exitosamente", "success");
                this.cerrarModal();
                this.obtenerDiplomados(); // Recargar tabla reactiva
            })
            .catch(err => {
                swal("Error", "No se pudo guardar la información.", "error");
                console.error(err);
            })
            .finally(() => {
                this.cargandoEnvio = false;
            });
    }
  }
};
</script>
