<template>
  <div class="px-4 py-8">
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
      
      <!-- Card Admisiones -->
      <a href="/seguimiento/inscripciones" class="group block no-underline">
        <v-card
          class="h-full transition-all duration-300 group-hover:-translate-y-1 group-hover:shadow-xl border border-gray-100"
          elevation="0"
          rounded="xl"
        >
          <v-card-item class="pa-6">
            <template v-slot:prepend>
              <v-avatar color="indigo-lighten-5" size="64" rounded="lg" class="mr-4 transition-transform group-hover:scale-110">
                <v-icon color="indigo-darken-2" size="32">mdi-account-plus-outline</v-icon>
              </v-avatar>
            </template>
            <v-card-title class="text-h6 font-weight-bold tracking-tight text-gray-800">Admisiones</v-card-title>
            <v-card-subtitle class="mt-1 text-gray-500">Inscribir Matrícula</v-card-subtitle>
          </v-card-item>
          <v-card-text class="text-body-2 text-gray-600 px-6 pb-6">
            Rellena el formulario de inscripción para registrar a nuevos estudiantes en el sistema.
          </v-card-text>
        </v-card>
      </a>

      <!-- Card Buscador de Alumnos (Acceso Rápido Destacado) -->
      <a href="/buscador-alumnos" class="group block no-underline">
        <v-card
          class="h-full transition-all duration-300 group-hover:-translate-y-2 group-hover:shadow-2xl border-2 border-indigo-200 overflow-hidden relative"
          elevation="0"
          rounded="xl"
        >
          <!-- Gradiente de fondo decorativo -->
          <div class="absolute inset-0 bg-gradient-to-br from-indigo-50 via-purple-50 to-white pointer-events-none" />
          <!-- Detalle superior de color -->
          <div class="h-1 w-full bg-gradient-to-r from-indigo-500 via-purple-500 to-violet-500" />

          <v-card-item class="pa-6 relative">
            <template v-slot:prepend>
              <v-avatar
                class="mr-4 transition-transform group-hover:scale-110 shadow-md"
                size="64"
                rounded="lg"
                style="background: linear-gradient(135deg, #4338ca, #7c3aed);"
              >
                <v-icon color="white" size="32">mdi-card-search</v-icon>
              </v-avatar>
            </template>
            <v-card-title class="text-h6 font-weight-bold tracking-tight text-indigo-900">
              Buscador de Alumnos
            </v-card-title>
            <v-card-subtitle class="mt-1 text-indigo-500 font-medium">Expediente Completo</v-card-subtitle>
          </v-card-item>
          <v-card-text class="text-body-2 text-indigo-700/80 px-6 pb-6 relative">
            Busca por nombre, celular, correo o CURP. Edita datos y gestiona el plan de pagos del alumno.
          </v-card-text>

          <!-- Badge "Nuevo" -->
          <v-chip
            size="x-small"
            color="indigo-darken-2"
            variant="flat"
            class="absolute top-3 right-3 font-bold text-xs"
            prepend-icon="mdi-star"
          >Acceso Rápido</v-chip>
        </v-card>
      </a>

      <!-- Card Seguimiento -->
      <a href="/inscripciones" class="group block no-underline">
        <v-card
          class="h-full transition-all duration-300 group-hover:-translate-y-1 group-hover:shadow-xl border border-gray-100"
          elevation="0"
          rounded="xl"
        >
          <v-card-item class="pa-6">
            <template v-slot:prepend>
              <v-avatar color="teal-lighten-5" size="64" rounded="lg" class="mr-4 transition-transform group-hover:scale-110">
                <v-icon color="teal-darken-2" size="32">mdi-account-search-outline</v-icon>
              </v-avatar>
            </template>
            <v-card-title class="text-h6 font-weight-bold tracking-tight text-gray-800">Seguimiento</v-card-title>
            <v-card-subtitle class="mt-1 text-gray-500">Consultar Inscripciones</v-card-subtitle>
          </v-card-item>
          <v-card-text class="text-body-2 text-gray-600 px-6 pb-6">
            Revisa el estatus de las inscripciones activas y pasadas del instituto.
          </v-card-text>
        </v-card>
      </a>

      <!-- Card Caja -->
      <a href="/crud-pagos" class="group block no-underline">
        <v-card
          class="h-full transition-all duration-300 group-hover:-translate-y-1 group-hover:shadow-xl border border-gray-100"
          elevation="0"
          rounded="xl"
        >
          <v-card-item class="pa-6">
            <template v-slot:prepend>
              <v-avatar color="green-lighten-5" size="64" rounded="lg" class="mr-4 transition-transform group-hover:scale-110">
                <v-icon color="green-darken-2" size="32">mdi-cash-register</v-icon>
              </v-avatar>
            </template>
            <v-card-title class="text-h6 font-weight-bold tracking-tight text-gray-800">Caja</v-card-title>
            <v-card-subtitle class="mt-1 text-gray-500">Registrar Pagos</v-card-subtitle>
          </v-card-item>
          <v-card-text class="text-body-2 text-gray-600 px-6 pb-6">
            Realiza el cobro en caja de las colegiaturas y gestiona nuevos pagos.
          </v-card-text>
        </v-card>
      </a>

      <!-- Card Colegiaturas -->
      <a href="/mensualidades/pagos" class="group block no-underline">
        <v-card
          class="h-full transition-all duration-300 group-hover:-translate-y-1 group-hover:shadow-xl border border-gray-100"
          elevation="0"
          rounded="xl"
        >
          <v-card-item class="pa-6">
            <template v-slot:prepend>
              <v-avatar color="orange-lighten-5" size="64" rounded="lg" class="mr-4 transition-transform group-hover:scale-110">
                <v-icon color="orange-darken-2" size="32">mdi-wallet-outline</v-icon>
              </v-avatar>
            </template>
            <v-card-title class="text-h6 font-weight-bold tracking-tight text-gray-800">Colegiaturas</v-card-title>
            <v-card-subtitle class="mt-1 text-gray-500">Tabla de Pagos</v-card-subtitle>
          </v-card-item>
          <v-card-text class="text-body-2 text-gray-600 px-6 pb-6">
            Consulta el historial, seguimiento y carteras de pago de los alumnos.
          </v-card-text>
        </v-card>
      </a>

      <!-- Card Estadísticas -->
      <a href="/resumen" class="group block no-underline">
        <v-card
          class="h-full transition-all duration-300 group-hover:-translate-y-1 group-hover:shadow-xl border border-gray-100"
          elevation="0"
          rounded="xl"
        >
          <v-card-item class="pa-6">
            <template v-slot:prepend>
              <v-avatar color="purple-lighten-5" size="64" rounded="lg" class="mr-4 transition-transform group-hover:scale-110">
                <v-icon color="purple-darken-2" size="32">mdi-chart-line</v-icon>
              </v-avatar>
            </template>
            <v-card-title class="text-h6 font-weight-bold tracking-tight text-gray-800">Estadísticas</v-card-title>
            <v-card-subtitle class="mt-1 text-gray-500">Resumen Financiero</v-card-subtitle>
          </v-card-item>
          <v-card-text class="text-body-2 text-gray-600 px-6 pb-6">
            Reportes estadísticos y gráficos de desempeño de la escuela y finanzas.
          </v-card-text>
        </v-card>
      </a>

      <!-- Card Contabilidad -->
      <a href="/contabilidad" class="group block no-underline">
        <v-card
          class="h-full transition-all duration-300 group-hover:-translate-y-1 group-hover:shadow-xl border border-gray-100"
          elevation="0"
          rounded="xl"
        >
          <v-card-item class="pa-6">
            <template v-slot:prepend>
              <v-avatar color="red-lighten-5" size="64" rounded="lg" class="mr-4 transition-transform group-hover:scale-110">
                <v-icon color="red-darken-2" size="32">mdi-chart-line-stacked</v-icon>
              </v-avatar>
            </template>
            <v-card-title class="text-h6 font-weight-bold tracking-tight text-gray-800">Contabilidad</v-card-title>
            <v-card-subtitle class="mt-1 text-gray-500">Auditoría de Pagos</v-card-subtitle>
          </v-card-item>
          <v-card-text class="text-body-2 text-gray-600 px-6 pb-6">
            Genera reportes de ingresos, conciliación de cuentas y descarga historiales en Excel.
          </v-card-text>
        </v-card>
      </a>

      <a href="/alumnos-liquidados" class="group block no-underline">
        <v-card
          class="h-full transition-all duration-300 group-hover:-translate-y-1 group-hover:shadow-xl border border-gray-100"
          elevation="0"
          rounded="xl"
        >
          <v-card-item class="pa-6">
            <template v-slot:prepend>
              <v-avatar color="green-lighten-5" size="64" rounded="lg" class="mr-4 transition-transform group-hover:scale-110">
                <v-icon color="green-darken-2" size="32">mdi-account-check</v-icon>
              </v-avatar>
            </template>
            <v-card-title class="text-h6 font-weight-bold tracking-tight text-gray-800">Alumnos Egresados</v-card-title>
            <v-card-subtitle class="mt-1 text-gray-500">Cartera Liquidada</v-card-subtitle>
          </v-card-item>
          <v-card-text class="text-body-2 text-gray-600 px-6 pb-6">
            Consulta el padrón de alumnos que ya han liquidado el 100% de su diplomado y revisa sus expedientes.
          </v-card-text>
        </v-card>
      </a>

      <!-- Card Diplomados -->
      <a href="/diplomados" class="group block no-underline">
        <v-card
          class="h-full transition-all duration-300 group-hover:-translate-y-1 group-hover:shadow-xl border border-gray-100"
          elevation="0"
          rounded="xl"
        >
          <v-card-item class="pa-6">
            <template v-slot:prepend>
              <v-avatar color="blue-lighten-5" size="64" rounded="lg" class="mr-4 transition-transform group-hover:scale-110">
                <v-icon color="blue-darken-2" size="32">mdi-certificate-outline</v-icon>
              </v-avatar>
            </template>
            <v-card-title class="text-h6 font-weight-bold tracking-tight text-gray-800">Diplomados</v-card-title>
            <v-card-subtitle class="mt-1 text-gray-500">Gestión Académica</v-card-subtitle>
          </v-card-item>
          <v-card-text class="text-body-2 text-gray-600 px-6 pb-6">
            Crea, edita o consulta la oferta de diplomados y cursos del plantel.
          </v-card-text>
        </v-card>
      </a>

    <!-- Card Usuarios (Solo administrador TI) -->
      <a v-if="$page.props.auth.user.roles?.includes('TI')" href="/users" class="group block no-underline">
        <v-card
          class="h-full transition-all duration-300 group-hover:-translate-y-1 group-hover:shadow-xl border border-gray-100"
          elevation="0"
          rounded="xl"
        >
          <v-card-item class="pa-6">
            <template v-slot:prepend>
              <v-avatar color="red-lighten-5" size="64" rounded="lg" class="mr-4 transition-transform group-hover:scale-110">
                <v-icon color="red-darken-2" size="32">mdi-shield-account</v-icon>
              </v-avatar>
            </template>
            <v-card-title class="text-h6 font-weight-bold tracking-tight text-gray-800">Cuentas y TI</v-card-title>
            <v-card-subtitle class="mt-1 text-gray-500">Gestión de Usuarios</v-card-subtitle>
          </v-card-item>
          <v-card-text class="text-body-2 text-gray-600 px-6 pb-6">
            Panel restringido para administrar roles de usuarios y permisos del sistema.
          </v-card-text>
        </v-card>
      </a>

    </div>
  </div>
</template>

<style scoped>
a {
  text-decoration: none;
}
</style>

<script setup>
import { Card, CardContent, CardDescription, CardFooter, CardHeader, CardTitle } from "@/shadcn/ui/card/";
import { Button } from "@/shadcn/ui/button/";
import "../../css/app.css";
</script>
