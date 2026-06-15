<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head } from '@inertiajs/vue3';
import { useErpWindows } from '@/Composables/useErpWindows';
import * as XLSX from 'xlsx';
import { ref, computed } from 'vue';

const props = defineProps({
    usuariosActivos: Array,
    modulosTop: Array,
    historial: Array
});

const searchWorker = ref('');

const filteredHistorial = computed(() => {
    if (!searchWorker.value) return props.historial;
    const q = searchWorker.value.toLowerCase();
    return props.historial.filter(h => 
        h.usuario.toLowerCase().includes(q) || 
        h.accion.toLowerCase().includes(q)
    );
});

const getInitials = (name) => {
    return name ? name.split(' ').slice(0,2).map(n => n.charAt(0)).join('').toUpperCase() : 'U';
};
const getColor = (id) => {
    const ids = ['bg-indigo-500', 'bg-blue-500', 'bg-emerald-500', 'bg-purple-500', 'bg-orange-500', 'bg-pink-500'];
    return ids[(id ?? 0) % ids.length];
};

const exportarExcel = () => {
    const list = filteredHistorial.value;
    if (!list || list.length === 0) return;

    const data = list.map(log => ({
        'Nombre del Trabajador': log.usuario,
        'Actividad / Acción': log.accion,
        'Módulo o Ruta Accedida': log.url,
        'Fecha y Hora Exacta': log.fecha,
        'Tiempo Transcurrido': log.hace
    }));

    const worksheet = XLSX.utils.json_to_sheet(data);
    const workbook = XLSX.utils.book_new();
    XLSX.utils.book_append_sheet(workbook, worksheet, 'Bitácora_Productividad');
    
    // Ajustar anchos de columna para mejor presentación
    worksheet['!cols'] = [
        { wch: 30 }, // Trabajador
        { wch: 35 }, // Acción
        { wch: 35 }, // Ruta
        { wch: 25 }, // Fecha
        { wch: 20 }, // Hace
    ];
    
    XLSX.writeFile(workbook, `Reporte_Actividad_Personal_${new Date().toISOString().slice(0,10)}.xlsx`);
};
</script>

<template>
    <Head title="Métricas y Productividad" />

    <AuthenticatedLayout>
        <div class="min-h-screen bg-slate-50 py-8">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                
                <!-- Cabecera -->
                <div class="mb-8 flex items-center justify-between">
                    <div>
                        <h1 class="text-3xl font-bold text-gray-900 tracking-tight">Panel de Productividad</h1>
                        <p class="mt-1 text-sm text-gray-500">Métricas de navegación, módulos visitados y control de personal activo.</p>
                    </div>
                    <div class="bg-white p-3 rounded-xl shadow-sm border border-gray-100 flex items-center gap-4 hidden sm:flex">
                        <div class="text-right">
                            <p class="text-[0.65rem] font-bold text-gray-400 uppercase tracking-widest">Personal Activo (24h)</p>
                            <p class="text-xl font-black text-indigo-600">{{ usuariosActivos.length }} Usuarios</p>
                        </div>
                        <div class="h-10 w-10 rounded-full bg-indigo-50 flex items-center justify-center">
                            <v-icon color="indigo">mdi-account-group</v-icon>
                        </div>
                    </div>
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                    
                    <!-- Columna Izquierda: Activos y Modulos -->
                    <div class="col-span-1 space-y-8">
                        
                        <!-- Panel: Usuarios Activos -->
                        <div class="bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden">
                            <div class="px-5 py-4 border-b border-gray-100 bg-gray-50 flex items-center gap-2">
                                <v-icon size="20" color="green-darken-1">mdi-account-clock</v-icon>
                                <h3 class="font-bold text-gray-800">Conexiones Recientes</h3>
                            </div>
                            <div class="p-0">
                                <div v-if="usuariosActivos.length === 0" class="p-6 text-center text-gray-400 text-sm">
                                    No hay datos registrados aún.
                                </div>
                                <ul class="divide-y divide-gray-100">
                                    <li v-for="user in usuariosActivos" :key="user.id" class="p-4 hover:bg-slate-50 transition-colors flex items-center gap-3">
                                        <div class="h-10 w-10 rounded-xl flex items-center justify-center text-white font-bold text-sm shadow-sm" :class="getColor(user.id)">
                                            {{ getInitials(user.name) }}
                                        </div>
                                        <div class="flex-1 min-w-0">
                                            <p class="text-sm font-bold text-gray-900 truncate">{{ user.name }}</p>
                                            <p class="text-[0.68rem] text-gray-500 truncate">{{ user.email }}</p>
                                        </div>
                                        <v-chip size="x-small" color="green" variant="flat">Activo</v-chip>
                                    </li>
                                </ul>
                            </div>
                        </div>

                        <!-- Panel: Módulos más usados -->
                        <div class="bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden">
                            <div class="px-5 py-4 border-b border-gray-100 bg-gray-50 flex items-center gap-2">
                                <v-icon size="20" color="blue-darken-2">mdi-chart-bar</v-icon>
                                <h3 class="font-bold text-gray-800">Módulos más visitados (7 Días)</h3>
                            </div>
                            <div class="p-0">
                                <ul class="divide-y divide-gray-100">
                                    <li v-for="modulo in modulosTop" :key="modulo.path" class="p-4 flex items-center justify-between group hover:bg-slate-50">
                                        <div class="flex items-center gap-3">
                                            <v-icon size="18" color="gray-400" class="group-hover:text-blue-500 transition-colors">mdi-folder-eye</v-icon>
                                            <span class="text-sm font-medium text-gray-700">{{ modulo.path }}</span>
                                        </div>
                                        <span class="bg-blue-50 text-blue-700 font-bold px-2.5 py-0.5 rounded-full text-xs border border-blue-100">
                                            {{ modulo.total_visitas }} clicks
                                        </span>
                                    </li>
                                </ul>
                            </div>
                        </div>

                    </div>

                    <!-- Columna Derecha: Historial detallado -->
                    <div class="col-span-1 lg:col-span-2">
                        <div class="bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden h-full flex flex-col">
                            <div class="px-5 py-4 border-b border-gray-100 bg-gray-50 flex items-center justify-between">
                                <div class="flex items-center gap-2">
                                    <v-icon size="20" color="indigo-darken-2">mdi-history</v-icon>
                                    <h3 class="font-bold text-gray-800">Bitácora Global de Navegación</h3>
                                </div>
                                <div class="flex items-center gap-3 flex-wrap">
                                    <v-text-field
                                        v-model="searchWorker"
                                        prepend-inner-icon="mdi-magnify"
                                        label="Buscar trabajador..."
                                        variant="solo"
                                        density="compact"
                                        hide-details
                                        class="min-w-[200px]"
                                    ></v-text-field>
                                    <v-btn size="small" color="success" variant="flat" prepend-icon="mdi-file-excel" @click="exportarExcel" :disabled="filteredHistorial.length === 0">
                                        Descargar Log ({{ filteredHistorial.length }})
                                    </v-btn>
                                </div>
                            </div>
                            <!-- Tabla -->
                            <div class="flex-1 overflow-auto max-h-[800px]">
                                <table class="w-full text-left border-collapse">
                                    <thead class="bg-white sticky top-0 border-b border-gray-200 shadow-sm z-10">
                                        <tr>
                                            <th class="py-3 px-5 text-xs font-bold text-gray-500 uppercase tracking-wider">Usuario</th>
                                            <th class="py-3 px-5 text-xs font-bold text-gray-500 uppercase tracking-wider">Acción</th>
                                            <th class="py-3 px-5 text-xs font-bold text-gray-500 uppercase tracking-wider">Ruta/Módulo</th>
                                            <th class="py-3 px-5 text-xs font-bold text-gray-500 uppercase tracking-wider text-right">Momento</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-gray-100">
                                        <tr v-for="log in filteredHistorial" :key="log.id" class="hover:bg-slate-50 transition-colors">
                                            <td class="py-3 px-5">
                                                <span class="font-semibold text-sm text-gray-800">{{ log.usuario }}</span>
                                            </td>
                                            <td class="py-3 px-5">
                                                <v-chip size="x-small" color="indigo-lighten-4" class="text-indigo-800 font-medium">
                                                    {{ log.accion }}
                                                </v-chip>
                                            </td>
                                            <td class="py-3 px-5 truncate max-w-[200px]">
                                                <span class="text-sm text-gray-600 font-mono text-[0.75rem] bg-gray-100 px-1.5 py-0.5 rounded">{{ log.url }}</span>
                                            </td>
                                            <td class="py-3 px-5 text-right">
                                                <p class="text-sm font-medium text-gray-900">{{ log.hace }}</p>
                                                <p class="text-xs text-gray-400">{{ log.fecha }}</p>
                                            </td>
                                        </tr>
                                        <tr v-if="historial.length === 0">
                                            <td colspan="4" class="py-12 text-center text-gray-400">
                                                <v-icon size="40" class="mb-2 opacity-50">mdi-clipboard-text-off</v-icon>
                                                <p>Todavía no hay navegaciones registradas en el motor.</p>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
