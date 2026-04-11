<script setup>
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import { Head } from "@inertiajs/vue3";
import { ref, onMounted } from 'vue';
import axios from 'axios';

const user = ref({ name: '', email: '', roles: [] });
const kpis = ref({ total_inscritos: 0, alumnos_sin_pagos: 0, total_cobranza_generada: 0 });
const lista_sin_pagos = ref([]);
const cargando = ref(true);

onMounted(async () => {
    try {
        const res = await axios.get('/mi-panel/data');
        user.value = res.data.usuario;
        kpis.value = res.data.kpis;
        lista_sin_pagos.value = res.data.lista_sin_pagos;
    } catch (error) {
        console.error("Error al cargar mi panel:", error);
    } finally {
        cargando.value = false;
    }
});
</script>

<template>
  <AuthenticatedLayout>
    <Head title="Mi Panel de Trabajo" />
    
    <div class="bg-gray-50 min-h-screen pb-10">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 pt-8">
        
        <!-- Header del Perfil -->
        <div class="bg-indigo-700 rounded-2xl shadow-lg p-6 mb-8 text-white relative overflow-hidden">
            <div class="absolute right-0 top-0 opacity-10">
                <v-icon size="200">mdi-monitor-dashboard</v-icon>
            </div>
            <div class="relative z-10 flex items-center">
                <v-avatar color="white" size="80" class="mr-6 elevation-2">
                    <span class="text-3xl font-bold text-indigo-700">{{ user.name.charAt(0).toUpperCase() }}</span>
                </v-avatar>
                <div>
                    <h2 class="text-3xl font-bold mb-1">Bienvenido, {{ user.name }}</h2>
                    <p class="text-indigo-100 flex items-center mb-2">
                        <v-icon size="small" class="mr-2">mdi-email</v-icon> {{ user.email }}
                    </p>
                    <div class="flex gap-2">
                        <v-chip v-for="rol in user.roles" :key="rol" color="indigo-lighten-4" variant="flat" size="small" class="font-bold text-indigo-900 border border-indigo-200">
                            Rol Asignado: {{ rol }}
                        </v-chip>
                        <v-chip v-if="user.roles.length === 0" color="orange-lighten-4" text-color="orange-darken-4" size="small">Sin Rol Específico</v-chip>
                    </div>
                </div>
            </div>
        </div>

        <div v-if="cargando" class="text-center py-12">
            <v-progress-circular indeterminate color="indigo"></v-progress-circular>
            <p class="mt-4 text-gray-500">Cargando métricas de rendimiento...</p>
        </div>

        <div v-else>
            <!-- KPIs -->
            <h3 class="text-xl font-bold text-gray-800 mb-4 flex items-center">
                <v-icon color="indigo-darken-2" class="mr-2">mdi-trending-up</v-icon> Mis Métricas de Rendimiento
            </h3>
            
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-10">
                <!-- KPI Total Inscritos -->
                <v-card class="rounded-xl border border-gray-100 shadow-sm transition hover:shadow-md" elevation="0">
                    <v-card-text class="pa-6">
                        <div class="flex justify-between items-start mb-4">
                            <div>
                                <p class="text-gray-500 text-sm font-bold uppercase tracking-wider mb-1">Mis Admisiones</p>
                                <h4 class="text-4xl font-black text-indigo-700">{{ kpis.total_inscritos }}</h4>
                            </div>
                            <v-avatar color="indigo-50" rounded size="56">
                                <v-icon color="indigo-700" size="32">mdi-account-multiple-plus</v-icon>
                            </v-avatar>
                        </div>
                        <p class="text-xs text-gray-500">Personas que has matriculado en total desde tu ingreso.</p>
                    </v-card-text>
                </v-card>

                <!-- KPI Cobranza -->
                <v-card class="rounded-xl border border-gray-100 shadow-sm transition hover:shadow-md" elevation="0">
                    <v-card-text class="pa-6">
                        <div class="flex justify-between items-start mb-4">
                            <div>
                                <p class="text-gray-500 text-sm font-bold uppercase tracking-wider mb-1">Total Cobrado (Por Ti)</p>
                                <h4 class="text-3xl font-black text-green-600">${{ Number(kpis.total_cobranza_generada).toLocaleString() }}</h4>
                            </div>
                            <v-avatar color="green-50" rounded size="56">
                                <v-icon color="green-700" size="32">mdi-cash-multiple</v-icon>
                            </v-avatar>
                        </div>
                        <p class="text-xs text-gray-500">Monto total de colegiaturas que has procesado personalmente en caja.</p>
                    </v-card-text>
                </v-card>

                <!-- KPI Alerta -->
                <v-card class="rounded-xl border border-red-100 shadow-sm transition hover:shadow-md bg-red-50" elevation="0">
                    <v-card-text class="pa-6">
                        <div class="flex justify-between items-start mb-4">
                            <div>
                                <p class="text-red-800 text-sm font-bold uppercase tracking-wider mb-1">Inscripciones "Muertas"</p>
                                <h4 class="text-4xl font-black text-red-600">{{ kpis.alumnos_sin_pagos }}</h4>
                            </div>
                            <v-avatar color="red-100" rounded size="56">
                                <v-icon color="red-700" size="32">mdi-alert-octagon</v-icon>
                            </v-avatar>
                        </div>
                        <p class="text-xs text-red-600">Alumnos tuyos que se matricularon pero NUNCA hicieron su primer pago de colegiatura.</p>
                    </v-card-text>
                </v-card>
            </div>

            <!-- Tabla de Inscripciones Huérfanas -->
            <div class="mt-8">
                <h3 class="text-xl font-bold text-gray-800 mb-4 flex items-center">
                    <v-icon color="red-darken-2" class="mr-2">mdi-account-alert</v-icon> Seguimiento Urgente (Tus alumnos con 0 pagos)
                </h3>
                
                <v-card elevation="0" class="border border-gray-200 shadow-sm rounded-xl overflow-hidden">
                    <div v-if="lista_sin_pagos.length === 0" class="text-center py-12 bg-white">
                        <v-icon size="64" color="green-lighten-2">mdi-check-circle-outline</v-icon>
                        <h4 class="text-lg font-bold text-gray-700 mt-4">¡Excelente trabajo!</h4>
                        <p class="text-sm text-gray-500 mt-1">Todos los alumnos que has inscrito tienen al menos un pago de colegiatura registrado.</p>
                    </div>
                    
                    <v-table v-else class="bg-white">
                        <thead class="bg-gray-50 border-b border-gray-200">
                            <tr>
                                <th class="text-left font-bold text-gray-600 pa-4">ID</th>
                                <th class="text-left font-bold text-gray-600 pa-4">Nombre del Alumno</th>
                                <th class="text-left font-bold text-gray-600 pa-4">Diplomado Inscrito</th>
                                <th class="text-left font-bold text-gray-600 pa-4">Fecha de Inscripción</th>
                                <th class="text-left font-bold text-gray-600 pa-4">Acción / Contacto</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="item in lista_sin_pagos" :key="item.id" class="hover:bg-red-50 transition border-b border-gray-100">
                                <td class="pa-4 font-mono text-gray-500">#{{ item.id }}</td>
                                <td class="pa-4 font-bold text-gray-800">{{ item.nombre_alumno }}</td>
                                <td class="pa-4 text-indigo-700 font-medium">{{ item.diplomado }}</td>
                                <td class="pa-4 text-gray-600">
                                    <v-icon size="small" class="mr-1">mdi-calendar</v-icon>{{ item.fecha_inscripcion }}
                                </td>
                                <td class="pa-4">
                                    <v-btn 
                                        color="green" 
                                        variant="flat" 
                                        size="small" 
                                        prepend-icon="mdi-whatsapp"
                                        :href="'https://wa.me/' + item.celular"
                                        target="_blank"
                                    >
                                        {{ item.celular }}
                                    </v-btn>
                                </td>
                            </tr>
                        </tbody>
                    </v-table>
                </v-card>
            </div>

        </div>

      </div>
    </div>
  </AuthenticatedLayout>
</template>
