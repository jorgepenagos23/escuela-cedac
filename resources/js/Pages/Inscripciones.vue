<script setup>
import { ref, watch } from 'vue';
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import { Head } from "@inertiajs/vue3";
</script>

<template>
  <AuthenticatedLayout>
    <Head title="Directorio de Inscripciones" />
    
    <div class="bg-gray-50 min-h-screen pb-10">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 pt-8">
        
        <div class="mb-8">
          <h2 class="text-2xl font-bold text-gray-800 flex items-center">
              <v-icon color="indigo-darken-2" class="mr-2">mdi-account-multiple-check</v-icon>
              Resumen Global de Inscripciones (Matrículas)
          </h2>
          <p class="text-sm text-gray-500 mt-1">
            Consulta el histórico de alumnos inscritos al sistema. Puedes filtrar por rango de fechas según su apertura.
          </p>
        </div>

        <v-card variant="outlined" class="bg-white border-gray-200 shadow-sm rounded-xl overflow-hidden mb-6">
            <div class="bg-gray-100 px-6 py-4 flex flex-col md:flex-row items-center justify-between border-b gap-4">
                
                <!-- Búsqueda General -->
                <div class="w-full md:w-4/12">
                    <v-text-field
                        v-model="busqueda"
                        placeholder="Buscar Nombre Alumno o Resumen..."
                        variant="solo"
                        density="comfortable"
                        hide-details
                        prepend-inner-icon="mdi-magnify"
                        class="shadow-sm rounded-lg bg-white"
                        @keyup="aplicarFiltrosFront"
                    ></v-text-field>
                </div>

                <!-- Filtro Fechas Backend -->
                <div class="w-full md:w-6/12 grid grid-cols-2 gap-2 border-l border-gray-300 pl-4">
                    <v-text-field
                        v-model="filtroFechaInicio"
                        label="Fecha Inicio Cierre"
                        variant="solo"
                        density="comfortable"
                        hide-details
                        type="date"
                        class="shadow-sm rounded-lg bg-white"
                        @change="obtenerDiplomados"
                    ></v-text-field>
                    <v-text-field
                        v-model="filtroFechaFin"
                        label="Fecha Fin Cierre"
                        variant="solo"
                        density="comfortable"
                        hide-details
                        type="date"
                        class="shadow-sm rounded-lg bg-white"
                        @change="obtenerDiplomados"
                    ></v-text-field>
                </div>

                <!-- Botones Accion -->
                <div class="w-full md:w-2/12 flex space-x-2">
                    <v-btn @click="limpiarFiltros" color="grey-darken-1" variant="tonal" class="w-full" prepend-icon="mdi-refresh">Restaurar</v-btn>
                </div>
            </div>

            <div class="p-4">
                <v-data-table
                    :headers="headers"
                    :items="alumnosFiltrados"
                    :search="busqueda"
                    class="elevation-0 bg-transparent"
                    hover
                >
                    <template v-slot:item.nombre_diplomado="{ item }">
                        <v-chip color="indigo" size="small" variant="flat">{{ item.nombre_diplomado }}</v-chip>
                    </template>
                    <template v-slot:item.saldo="{ item }">
                        <span class="text-red-500 font-bold">${{ item.saldo }} MXN</span>
                    </template>
                    <template v-slot:item.acciones="{ item }">
                        <v-btn icon="mdi-printer-pos" size="small" color="primary" variant="tonal" @click="imprimirInscripcion(item)" title="Imprimir Formato de Matrícula"></v-btn>
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
      filtroFechaInicio: "",
      filtroFechaFin: "",
      alumnos_inscripcion: [],
      alumnosFiltrados: [],
      headers: [
        { title: "ID.", key: "id", sortable: true, align: "start", width: "70px" },
        { title: "Expediente del Alumno", key: "nombre_alumno", sortable: true },
        { title: "Diplomado Suscrito", key: "nombre_diplomado", sortable: true },
        { title: "Asesor (Cierre)", key: "Asesor", sortable: true },
        { title: "Fecha Inscripción", key: "fecha_inscripcion", sortable: true },
        { title: "Saldo A Deber", key: "saldo", sortable: true },
        { title: "Imprimir", key: "acciones", sortable: false, align: "center" },
      ]
    };
  },
  created() {
    this.obtenerDiplomados();
  },
  methods: {
    obtenerDiplomados() {
      const params = {};
      if (this.filtroFechaInicio) params.start_date = this.filtroFechaInicio;
      if (this.filtroFechaFin) params.end_date = this.filtroFechaFin;

      axios.get("/api/v1/inscripciones_api2024E", { params })
        .then((response) => {
          this.alumnos_inscripcion = response.data.alumnos_inscripcion;
          this.alumnosFiltrados = [...this.alumnos_inscripcion];
          if(this.alumnos_inscripcion.length === 0 && (this.filtroFechaInicio || this.filtroFechaFin)) {
              swal("Sin Datos", "No hay inscripciones cerradas en ese rango de fechas.", "info");
          }
        })
        .catch((err) => console.error(err));
    },

    aplicarFiltrosFront() {
        if (!this.busqueda.trim()) {
            this.alumnosFiltrados = [...this.alumnos_inscripcion];
            return;
        }
        const term = this.busqueda.toLowerCase();
        this.alumnosFiltrados = this.alumnos_inscripcion.filter(a => 
            String(a.nombre_alumno).toLowerCase().includes(term) ||
            String(a.nombre_diplomado).toLowerCase().includes(term)
        );
    },

    limpiarFiltros() {
      this.busqueda = "";
      this.filtroFechaInicio = "";
      this.filtroFechaFin = "";
      this.obtenerDiplomados();
    },

    imprimirInscripcion(alumno) {
        const logoUrl = "https://escuela-cedac.mypressserver.com/img/logo.jpg"; // Placeholder logico corporativo o se ajusta a local
        
        const htmlContent = `
            <!DOCTYPE html>
            <html lang="es">
            <head>
                <meta charset="UTF-8">
                <title>Ficha de Inscripción Académica</title>
                <style>
                    body { font-family: 'Helvetica Neue', Arial, sans-serif; color: #333; margin: 0; padding: 20px; }
                    .header { text-align: center; border-bottom: 2px solid #283593; padding-bottom: 10px; margin-bottom: 20px; }
                    .header h1 { font-size: 24px; color: #283593; margin: 0; }
                    .header p { font-size: 14px; color: #666; }
                    .card { border: 1px solid #ccc; padding: 15px; border-radius: 8px; margin-bottom: 15px; }
                    .card h3 { margin-top: 0; font-size: 16px; border-bottom: 1px solid #eee; padding-bottom: 5px; color: #444; }
                    table { width: 100%; border-collapse: collapse; font-size: 14px; }
                    th, td { text-align: left; padding: 8px; border-bottom: 1px solid #ddd; }
                    th { font-weight: bold; width: 40%; color: #555; }
                    .footer { text-align: center; font-size: 12px; color: #999; margin-top: 30px; border-top: 1px dotted #ccc; padding-top: 10px; }
                </style>
            </head>
            <body>
                <div class="header">
                    <h1>COMPROBANTE DE INSCRIPCIÓN</h1>
                    <p>Documento Válido de Apertura de Expediente - Sistema Corporativo</p>
                </div>
                
                <div class="card">
                    <h3>DATOS DEL ALUMNO</h3>
                    <table>
                        <tr><th>Matrícula / ID Referencia:</th><td>#${alumno.id}</td></tr>
                        <tr><th>Nombre Completo:</th><td><b>${alumno.nombre_alumno}</b></td></tr>
                        <tr><th>Celular Registrado:</th><td>${alumno.celular || 'No proporcionado'}</td></tr>
                        <tr><th>Fecha Operativa Oficial:</th><td>${alumno.fecha_inscripcion}</td></tr>
                    </table>
                </div>

                <div class="card">
                    <h3>DETALLES ACADÉMICOS Y FINANCIEROS</h3>
                    <table>
                        <tr><th>Programa Académico (Diplomado):</th><td><b>${alumno.nombre_diplomado}</b></td></tr>
                        <tr><th>Asesor Designado (Venta):</th><td>${alumno.Asesor}</td></tr>
                        <tr><th>Monto Inscripción Entregado:</th><td>$${alumno.monto_inscripcion} MXN</td></tr>
                        <tr><th>Saldo Pendiente en Módulo (Deuda Actual):</th><td style="color:red; font-weight:bold;">$${alumno.saldo} MXN</td></tr>
                        <tr><th>Próximo Pago Estimado:</th><td>${alumno.fecha_primer_pago_colegiatura || 'No pactado'}</td></tr>
                    </table>
                </div>

                <div class="footer">
                    Generado el ${new Date().toLocaleDateString()} a las ${new Date().toLocaleTimeString()} por el Sistema de Administración Central.<br>
                    Cualquier alteración a este documento invalida su legitimidad.
                </div>
            </body>
            </html>
        `;

        const printWindow = window.open('', '_blank', 'width=800,height=900');
        printWindow.document.write(htmlContent);
        printWindow.document.close();
        
        // Timeout para asegurar carga de css/DOM previo a disparar el dialogo de impresion del navegador
        setTimeout(() => {
            printWindow.print();
        }, 500);
    }
  }
};
</script>
