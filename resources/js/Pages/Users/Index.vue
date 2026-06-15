<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps({
    users: Array,
    roles: Array,
});

const form = useForm({
    role: '',
});

const createDialog = ref(false);
const createForm = useForm({
    name: '',
    email: '',
    password: '',
    password_confirmation: '',
    role: '',
});

const editDialog = ref(false);
const selectedUser = ref(null);

const openEditModal = (user) => {
    selectedUser.value = user;
    // Si el usuario ya tiene un rol, asignamos el primero al formulario
    form.role = user.roles && user.roles.length > 0 ? user.roles[0].name : '';
    editDialog.value = true;
};

const submitRoleUpdate = () => {
    form.post(route('users.updateRole', selectedUser.value.id), {
        onSuccess: () => {
            editDialog.value = false;
        },
    });
};

const openCreateModal = () => {
    createForm.reset();
    createForm.clearErrors();
    createDialog.value = true;
};

const submitCreateUser = () => {
    createForm.post(route('users.store'), {
        onSuccess: () => {
            createDialog.value = false;
            createForm.reset();
        },
    });
};

</script>

<template>
    <Head title="Gestión de Usuarios" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Gestión de Usuarios</h2>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                
                <v-card elevation="0" rounded="xl" class="border border-gray-100 pa-6">
                    <div class="flex justify-between items-center mb-4">
                        <v-card-title class="text-h6 font-weight-bold tracking-tight text-gray-800 pa-0">
                            Directorio de Usuarios del Sistema
                        </v-card-title>
                        
                        <v-btn
                            prepend-icon="mdi-account-plus"
                            color="indigo-darken-2"
                            variant="flat"
                            @click="openCreateModal"
                        >
                            Nuevo Usuario
                        </v-btn>
                    </div>
                    
                    <v-table>
                        <thead>
                            <tr>
                                <th class="text-left font-weight-bold">Nombre</th>
                                <th class="text-left font-weight-bold">Correo Electrónico</th>
                                <th class="text-left font-weight-bold">Rol Actual</th>
                                <th class="text-center font-weight-bold">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="user in users" :key="user.id" class="hover:bg-gray-50">
                                <td>{{ user.name }}</td>
                                <td>{{ user.email }}</td>
                                <td>
                                    <v-chip 
                                        :color="user.roles.length > 0 && user.roles[0].name === 'TI' ? 'red' : 'indigo'" 
                                        size="small" 
                                        variant="flat"
                                    >
                                        {{ user.roles.length > 0 ? user.roles[0].name : 'Sin rol asignado' }}
                                    </v-chip>
                                </td>
                                <td class="text-center">
                                    <v-btn 
                                        icon="mdi-pencil" 
                                        variant="text" 
                                        color="blue-darken-2"
                                        size="small"
                                        @click="openEditModal(user)"
                                    ></v-btn>
                                </td>
                            </tr>
                        </tbody>
                    </v-table>
                </v-card>

            </div>
        </div>

        <!-- Dialogo (Modal) para Editar Rol -->
        <v-dialog v-model="editDialog" max-width="500">
            <v-card rounded="xl" class="pa-4">
                <v-card-title class="text-h6 font-weight-bold mb-2">
                    Cambiar Rol de Usuario
                </v-card-title>
                <v-card-text>
                    <p class="mb-4 text-gray-600">
                        Seleccione el nuevo nivel de acceso para <strong>{{ selectedUser?.name }}</strong>.
                    </p>
                    
                    <v-select
                        v-model="form.role"
                        :items="roles"
                        item-title="name"
                        item-value="name"
                        label="Nuevo Rol"
                        variant="outlined"
                        dense
                    ></v-select>
                </v-card-text>

                <v-card-actions class="justify-end bg-gray-50 pa-3">
                    <v-btn
                        variant="text"
                        color="grey-darken-1"
                        @click="editDialog = false"
                    >
                        Cancelar
                    </v-btn>
                    <v-btn
                        variant="flat"
                        color="indigo-darken-2"
                        @click="submitRoleUpdate"
                        :loading="form.processing"
                    >
                        Guardar Cambios
                    </v-btn>
                </v-card-actions>
            </v-card>
        </v-dialog>

        <!-- Dialogo (Modal) para Crear Nuevo Usuario -->
        <v-dialog v-model="createDialog" max-width="500" persistent>
            <v-card rounded="xl" class="overflow-hidden">
                <v-card-title class="bg-indigo-900 text-white py-3 px-4 flex justify-between items-center">
                    <span class="text-base font-semibold"><v-icon class="mr-2">mdi-account-plus</v-icon> Alta de Usuario</span>
                    <v-btn icon="mdi-close" variant="text" size="small" color="white" @click="createDialog = false" :disabled="createForm.processing"></v-btn>
                </v-card-title>

                <v-card-text class="pa-5 pt-6 bg-gray-50">
                    <div class="space-y-4">
                        <v-text-field
                            v-model="createForm.name"
                            label="Nombre Completo"
                            variant="outlined"
                            density="comfortable"
                            prepend-inner-icon="mdi-account"
                            :error-messages="createForm.errors.name"
                            required
                        ></v-text-field>

                        <v-text-field
                            v-model="createForm.email"
                            label="Correo Electrónico"
                            type="email"
                            variant="outlined"
                            density="comfortable"
                            prepend-inner-icon="mdi-email"
                            :error-messages="createForm.errors.email"
                            required
                        ></v-text-field>

                        <v-text-field
                            v-model="createForm.password"
                            label="Contraseña"
                            type="password"
                            variant="outlined"
                            density="comfortable"
                            prepend-inner-icon="mdi-lock"
                            :error-messages="createForm.errors.password"
                            required
                        ></v-text-field>

                        <v-text-field
                            v-model="createForm.password_confirmation"
                            label="Confirmar Contraseña"
                            type="password"
                            variant="outlined"
                            density="comfortable"
                            prepend-inner-icon="mdi-lock-check"
                            :error-messages="createForm.errors.password_confirmation"
                            required
                        ></v-text-field>

                        <v-select
                            v-model="createForm.role"
                            :items="roles"
                            item-title="name"
                            item-value="name"
                            label="Asignar Rol"
                            variant="outlined"
                            density="comfortable"
                            prepend-inner-icon="mdi-shield-account"
                            :error-messages="createForm.errors.role"
                            required
                        ></v-select>
                    </div>
                </v-card-text>

                <v-card-actions class="bg-gray-200 py-3 px-4 shadow-inner flex justify-end gap-2">
                    <v-btn 
                        variant="text" 
                        color="grey-darken-3" 
                        @click="createDialog = false"
                        :disabled="createForm.processing"
                    >
                        Cancelar
                    </v-btn>
                    <v-btn 
                        variant="flat" 
                        color="indigo-darken-2" 
                        @click="submitCreateUser"
                        :loading="createForm.processing"
                        prepend-icon="mdi-check"
                    >
                        Generar Acceso
                    </v-btn>
                </v-card-actions>
            </v-card>
        </v-dialog>

    </AuthenticatedLayout>
</template>
