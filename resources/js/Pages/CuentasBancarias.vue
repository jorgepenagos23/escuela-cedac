<script setup>
import { ref } from 'vue';
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import { Head, useForm } from "@inertiajs/vue3";
import swal from "sweetalert";

const props = defineProps({
    cuentas: Array
});

// ── Estado ────────────────────────────────────────────────────────────────────
const dialog = ref(false);
const dialogDelete = ref(false);
const isEditing = ref(false);
const itemAEliminar = ref(null);

const form = useForm({
    id: null,
    banco: '',
    titular: '',
    numero_cuenta: '',
    CLABE: ''
});

const headers = [
    { title: "ID", key: "id", align: "start", width: "80px" },
    { title: "Banco", key: "banco" },
    { title: "Titular de la Cuenta", key: "titular" },
    { title: "Número de Cuenta", key: "numero_cuenta" },
    { title: "CLABE Interbancaria", key: "CLABE" },
    { title: "Acciones", key: "acciones", align: "center", sortable: false, width: "120px" }
];

// ── Métodos CRUD ──────────────────────────────────────────────────────────────
const openAddModal = () => {
    isEditing.value = false;
    form.reset();
    form.clearErrors();
    dialog.value = true;
};

const editItem = (item) => {
    isEditing.value = true;
    form.id = item.id;
    form.banco = item.banco || '';
    form.titular = item.titular || '';
    form.numero_cuenta = item.numero_cuenta || '';
    form.CLABE = item.CLABE || '';
    form.clearErrors();
    dialog.value = true;
};

const saveItem = () => {
    if (isEditing.value) {
        form.put(route('cuentas-bancarias.update', form.id), {
            onSuccess: () => {
                dialog.value = false;
                swal("Éxito", "Cuenta bancaria actualizada.", "success");
            },
            onError: (err) => {
                console.error(err);
                swal("Error", "Revisa los campos del formulario.", "error");
            }
        });
    } else {
        form.post(route('cuentas-bancarias.store'), {
            onSuccess: () => {
                dialog.value = false;
                swal("Éxito", "Cuenta bancaria agregada exitosamente.", "success");
            },
            onError: (err) => {
                console.error(err);
                swal("Error", "Revisa los campos del formulario.", "error");
            }
        });
    }
};

const confirmDelete = (item) => {
    itemAEliminar.value = item;
    dialogDelete.value = true;
};

const deleteItem = () => {
    if (!itemAEliminar.value) return;
    form.delete(route('cuentas-bancarias.destroy', itemAEliminar.value.id), {
        onSuccess: () => {
            dialogDelete.value = false;
            itemAEliminar.value = null;
            swal("Eliminado", "La cuenta ha sido eliminada.", "success");
        },
        onError: () => {
            swal("Error", "No se pudo eliminar la cuenta. Es posible que esté en uso en colegiaturas.", "error");
        }
    });
};
</script>

<template>
  <AuthenticatedLayout>
    <Head title="Cuentas Bancarias" />
    
    <div class="bg-slate-50 min-h-screen pb-10">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 pt-8">
        
        <!-- Header -->
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-6">
          <div>
            <h2 class="text-2xl font-bold text-slate-800 flex items-center">
              <div class="w-10 h-10 rounded-xl bg-indigo-900 flex items-center justify-center mr-3 shadow-sm">
                <v-icon color="white" size="20">mdi-bank</v-icon>
              </div>
              Cuentas Bancarias y Depósitos
            </h2>
            <p class="text-sm text-slate-500 mt-1 ml-13">
              Administración de las cuentas disponibles para recepción de colegiaturas (Solo TI).
            </p>
          </div>
          
          <v-btn color="indigo-darken-3" prepend-icon="mdi-plus" @click="openAddModal" variant="flat" class="text-none font-semibold">
            Agregar Cuenta
          </v-btn>
        </div>

        <!-- Main Card -->
        <div class="bg-white border border-slate-200 rounded-xl shadow-sm overflow-hidden">
            <v-data-table
                :headers="headers"
                :items="cuentas"
                hover
                density="comfortable"
                class="text-sm"
            >
                <template v-slot:item.banco="{ item }">
                    <span class="font-bold text-indigo-900">{{ item.banco }}</span>
                </template>
                
                <template v-slot:item.titular="{ item }">
                    <span class="font-medium text-slate-700">{{ item.titular }}</span>
                </template>

                <template v-slot:item.numero_cuenta="{ item }">
                    <span class="font-mono text-slate-600 bg-slate-100 px-2 py-1 rounded">{{ item.numero_cuenta || 'N/A' }}</span>
                </template>

                <template v-slot:item.CLABE="{ item }">
                    <span class="font-mono text-slate-600 bg-slate-100 px-2 py-1 rounded">{{ item.CLABE || 'N/A' }}</span>
                </template>

                <template v-slot:item.acciones="{ item }">
                    <div class="flex justify-center gap-2">
                        <v-btn icon="mdi-pencil" size="x-small" color="blue-darken-2" variant="tonal" @click="editItem(item)" title="Editar"></v-btn>
                        <v-btn icon="mdi-trash-can" size="x-small" color="red-darken-2" variant="tonal" @click="confirmDelete(item)" title="Eliminar"></v-btn>
                    </div>
                </template>

                <template #no-data>
                    <div class="p-8 text-center text-slate-400">
                        <v-icon size="48" class="mb-3 opacity-50">mdi-bank-off-outline</v-icon>
                        <p>No hay cuentas bancarias registradas.</p>
                    </div>
                </template>
            </v-data-table>
        </div>

        <!-- Modal de Creación/Edición -->
        <v-dialog v-model="dialog" max-width="500">
            <v-card rounded="xl">
                <div class="bg-indigo-900 px-6 py-4 flex justify-between items-center text-white">
                    <h3 class="text-lg font-bold flex items-center">
                        <v-icon color="emerald-accent-2" class="mr-2">{{ isEditing ? 'mdi-pencil' : 'mdi-plus' }}</v-icon>
                        {{ isEditing ? 'Editar Cuenta' : 'Nueva Cuenta' }}
                    </h3>
                    <v-btn icon="mdi-close" variant="text" size="small" @click="dialog = false" color="white"></v-btn>
                </div>

                <v-card-text class="pt-6">
                    <form @submit.prevent="saveItem">
                        <div class="space-y-4">
                            <v-text-field
                                v-model="form.banco"
                                label="Nombre del Banco *"
                                variant="outlined"
                                density="comfortable"
                                :error-messages="form.errors.banco"
                            ></v-text-field>

                            <v-text-field
                                v-model="form.titular"
                                label="Titular de la Cuenta *"
                                variant="outlined"
                                density="comfortable"
                                :error-messages="form.errors.titular"
                            ></v-text-field>

                            <v-text-field
                                v-model="form.numero_cuenta"
                                label="Número de Cuenta (Opcional)"
                                variant="outlined"
                                density="comfortable"
                                :error-messages="form.errors.numero_cuenta"
                            ></v-text-field>

                            <v-text-field
                                v-model="form.CLABE"
                                label="CLABE Interbancaria (Opcional)"
                                variant="outlined"
                                density="comfortable"
                                :error-messages="form.errors.CLABE"
                            ></v-text-field>
                        </div>
                    </form>
                </v-card-text>

                <v-card-actions class="px-6 py-4 border-t bg-slate-50 justify-end">
                    <v-btn variant="text" @click="dialog = false">Cancelar</v-btn>
                    <v-btn color="indigo-darken-3" variant="flat" :loading="form.processing" @click="saveItem">
                        Guardar Cuenta
                    </v-btn>
                </v-card-actions>
            </v-card>
        </v-dialog>

        <!-- Modal de Eliminación -->
        <v-dialog v-model="dialogDelete" max-width="400">
            <v-card rounded="xl" class="text-center pb-4">
                <v-card-text class="pt-8">
                    <div class="w-16 h-16 bg-red-100 text-red-600 rounded-full flex items-center justify-center mx-auto mb-4">
                        <v-icon size="32">mdi-alert</v-icon>
                    </div>
                    <h3 class="text-lg font-bold text-slate-800 mb-2">¿Eliminar esta cuenta?</h3>
                    <p class="text-sm text-slate-500">
                        Estás a punto de borrar la cuenta de <strong>{{ itemAEliminar?.banco }}</strong> a nombre de {{ itemAEliminar?.titular }}.<br><br>
                        Si esta cuenta ya ha sido usada en colegiaturas, no se podrá eliminar por integridad de datos.
                    </p>
                </v-card-text>
                <v-card-actions class="justify-center gap-3">
                    <v-btn variant="tonal" color="slate" @click="dialogDelete = false">Cancelar</v-btn>
                    <v-btn color="red-darken-2" variant="flat" :loading="form.processing" @click="deleteItem">Sí, Eliminar</v-btn>
                </v-card-actions>
            </v-card>
        </v-dialog>

      </div>
    </div>
  </AuthenticatedLayout>
</template>
