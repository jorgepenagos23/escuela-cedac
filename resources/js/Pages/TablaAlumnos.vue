
<template>
  <div class="bg-transparent">
    <v-container class="my-0 px-0 max-w-full">
      <div class="mb-8">
        <h2 class="text-2xl font-bold text-gray-800 flex items-center">
            <v-icon color="success" class="mr-2">mdi-cash-register</v-icon>
            Módulo de Cajas y Pagos
        </h2>
        <p class="text-sm text-gray-500">Busca alumnos y gestiona el historial o pago de nuevas colegiaturas con recibos oficiales.</p>
      </div>

      <!-- Filtros Maestros -->
      <v-card variant="outlined" class="bg-white border-gray-200 shadow-sm rounded-xl overflow-hidden mb-6">
          <div class="bg-gray-100 px-6 py-4 flex flex-col md:flex-row items-center justify-between border-b gap-4">
              <div class="w-full md:w-5/12">
                  <v-text-field
                      v-model="busqueda"
                      placeholder="Busqueda General..."
                      variant="solo"
                      density="comfortable"
                      hide-details
                      prepend-inner-icon="mdi-magnify"
                      class="shadow-sm rounded-lg bg-white"
                      @keyup.enter="buscarAlumnos"
                  ></v-text-field>
              </div>

              <div class="w-full md:w-5/12">
                  <v-select
                      v-model="filtroDiplomado"
                      :items="listaDiplomadosUnicos"
                      label="Filtro General por Diplomado"
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
                  <v-btn @click="buscarAlumnos" color="indigo-darken-3" class="flex-1" variant="elevated" prepend-icon="mdi-folder-search-outline">Filtrar</v-btn>
                  <v-btn @click="limpiarBusqueda" color="grey-darken-1" variant="tonal" icon="mdi-eraser"></v-btn>
              </div>
          </div>
      </v-card>

      <!-- TABLA DE CARTERA FORMATO EXCEL -->
      <v-card variant="flat" class="bg-white border-gray-200 shadow-sm rounded-xl overflow-hidden mb-6">
          <v-data-table
              :headers="headers"
              :items="alumnosCalculados"
              hover
              class="tabla-excel border-t border-gray-100"
              density="compact"
              fixed-header
              height="680"
              :items-per-page="50"
          >
              <!-- Cabeceras estilo Excel -->
              <template v-slot:header.nombre_completo="{ column }">
                  <div class="header-excel">
                      <span class="text-xs font-black uppercase text-indigo-900 border-b w-full pb-1 mb-2">{{ column.title }}</span>
                      <v-text-field v-model="filtros.nombre" placeholder="Nombre..." variant="plain" density="compact" hide-details prepend-inner-icon="mdi-filter" class="header-excel-input"></v-text-field>
                  </div>
              </template>

              <template v-slot:header.nombre_diplomado="{ column }">
                  <div class="header-excel">
                      <span class="text-xs font-black uppercase text-indigo-900 border-b w-full pb-1 mb-2">{{ column.title }}</span>
                      <v-select v-model="filtros.diplomados_multi" :items="listaDiplomadosUnicos" placeholder="Filtro Excel" multiple density="compact" hide-details variant="plain" class="header-excel-input">
                          <template v-slot:selection="{ item, index }">
                              <span v-if="index === 0" class="text-xs font-bold">{{ item.title }}</span>
                              <span v-if="index === 1" class="text-xs grey--text ml-1">(+{{ filtros.diplomados_multi.length - 1 }})</span>
                          </template>
                      </v-select>
                  </div>
              </template>

              <template v-slot:header.grupo="{ column }">
                  <div class="header-excel">
                      <span class="text-xs font-black uppercase text-indigo-900 border-b w-full pb-1 mb-2">{{ column.title }}</span>
                      <v-text-field v-model="filtros.grupo" placeholder="Filtrar..." variant="plain" density="compact" hide-details class="header-excel-input"></v-text-field>
                  </div>
              </template>

              <template v-slot:item.nombre_completo="{ item }">
                  <div class="font-bold text-gray-800 py-1">{{ item.nombre_completo }}</div>
              </template>

              <template v-slot:item.nombre_diplomado="{ item }">
                  <div class="text-indigo-800 text-xs">{{ item.nombre_diplomado }}</div>
              </template>

              <template v-slot:item.grupo="{ item }">
                  <div class="text-xs text-gray-600">
                      {{ item.campaña }} • {{ item.grupo }}
                  </div>
              </template>

              <template v-slot:item.Pendiente_Pagar="{ item }">
                  <div class="text-end font-black text-red-700 bg-red-50 px-2 rounded-lg border border-red-100">
                      ${{ Number(item.Pendiente_Pagar).toLocaleString('es-MX') }}
                  </div>
              </template>

              <template v-slot:item.acciones="{ item }">
                  <v-btn
                      color="indigo-darken-3"
                      variant="elevated"
                      size="x-small"
                      prepend-icon="mdi-cash-register"
                      class="px-3"
                      @click="abrirCaja(item)"
                  >
                      IR A CAJA
                  </v-btn>
              </template>

              <template v-slot:no-data>
                  <div class="text-center py-6 text-gray-400">
                      <v-icon size="40" class="mb-2">mdi-account-search-outline</v-icon>
                      <p>No se encontraron datos que coincidan con los filtros aplicados.</p>
                  </div>
              </template>
          </v-data-table>
      </v-card>

      <!-- DIALOGO Y FORMULARIO DE PAGOS (GLOBAL) -->
      <v-dialog max-width="850" v-model="mostrarModalCobro">
          <v-card rounded="xl" class="overflow-hidden" v-if="alumnoActivo">
              <div class="bg-indigo-900 px-6 py-4 flex justify-between items-center text-white">
                  <div>
                      <h3 class="text-lg font-medium flex items-center">
                          <v-icon color="success" class="mr-2">mdi-point-of-sale</v-icon>
                          Terminal Corporativa de Caja
                      </h3>
                      <p class="text-indigo-200 text-xs">Alumno: {{ alumnoActivo.nombre_completo }}</p>
                  </div>
                  <v-chip color="red-darken-1" size="small" variant="flat" prepend-icon="mdi-alert-circle">
                      Saldo Pendiente: ${{ alumnoActivo.Pendiente_Pagar }}
                  </v-chip>
              </div>

              <v-card-text class="bg-gray-50 p-6">
                  <v-expansion-panels class="mb-6">
                      <v-expansion-panel elevation="0" class="border border-gray-200 rounded-lg">
                          <v-expansion-panel-title class="bg-white font-bold text-gray-700">
                              <v-icon color="indigo" class="mr-2">mdi-folder-account-outline</v-icon>
                              Ver Expediente Completo del Alumno
                          </v-expansion-panel-title>
                          <v-expansion-panel-text class="bg-gray-50 pt-4">
                              <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 text-sm">
                                  <div><strong class="text-gray-500 block mb-1">Campaña y Grupo:</strong><v-chip size="x-small" color="blue">{{ alumnoActivo.campaña }} ({{ alumnoActivo.grupo }})</v-chip></div>
                                  <div><strong class="text-gray-500 block mb-1">Asesor Vendedor:</strong><v-chip size="x-small" color="orange">{{ alumnoActivo.asesor_nombre }}</v-chip></div>
                                  <div><strong class="text-gray-500 block mb-1">Aperturó (1er Pago):</strong><v-chip size="x-small" color="purple">{{ alumnoActivo.tutor_nombre }}</v-chip></div>
                                  <div><strong class="text-gray-500 block mb-1">Fecha de Ingreso:</strong><v-chip size="x-small" color="gray">{{ alumnoActivo.fecha_inscripcion }}</v-chip></div>
                                  <div><strong class="text-gray-500 block mb-1">Aportación Base:</strong><v-chip size="x-small" color="green">${{ alumnoActivo.monto_inscripcion }} mxn</v-chip></div>
                                  <div class="col-span-2 lg:col-span-3"><strong class="text-gray-500 block mb-1">Cuenta/Banco Receptor Original:</strong><span class="text-xs text-gray-700 bg-white px-2 py-1 rounded border">{{ alumnoActivo.banco_registro }} - {{ alumnoActivo.titular_registro }}</span></div>
                              </div>
                          </v-expansion-panel-text>
                      </v-expansion-panel>
                  </v-expansion-panels>

                  <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                      <div>
                          <form @submit.prevent="EnviarPago(alumnoActivo)" class="space-y-4">
                              <v-text-field v-model.number="pago_colegiatura" label="Importe Cobrado (MXN)" variant="outlined" density="comfortable" type="number" prefix="$" bg-color="white" required></v-text-field>
                              <div class="grid grid-cols-2 gap-4">
                                  <v-text-field v-model="fecha_inscripcion" label="Fecha" variant="filled" density="comfortable" type="date" readonly bg-color="gray-100"></v-text-field>
                                  <v-select v-model="selectedTitular" :items="cuentaDeposito" item-title="banco" item-value="id" label="Cuenta" variant="outlined" density="comfortable" bg-color="white" required></v-select>
                              </div>
                              <v-file-input v-model="comprobante" label="Comprobante" variant="outlined" density="comfortable" bg-color="white" required></v-file-input>
                              <v-btn type="submit" color="success" size="large" variant="elevated" prepend-icon="mdi-printer-pos" block>Cobrar e Imprimir PDF</v-btn>
                          </form>
                      </div>
                      <div>
                          <h4 class="text-gray-700 font-bold mb-4">Historial</h4>
                          <v-card variant="outlined" class="bg-white max-h-80 overflow-y-auto">
                              <v-list>
                                  <v-list-item v-for="(pago, index) in pagosColegiaturaAlumno2" :key="index" class="border-b">
                                      <v-list-item-title class="font-bold text-sm">+${{ pago.pago_colegiatura }} MXN</v-list-item-title>
                                      <v-list-item-subtitle class="text-xs text-gray-500">{{ pago.Fecha_PrimerContacto }}</v-list-item-subtitle>
                                      <template v-slot:append>
                                           <v-btn size="x-small" icon="mdi-file-pdf-box" variant="text" color="red" @click="descargarPDFHistorial(pago.idpago)"></v-btn>
                                      </template>
                                  </v-list-item>
                              </v-list>
                          </v-card>
                      </div>
                  </div>
              </v-card-text>
              <v-card-actions class="bg-gray-100 px-6 py-3 justify-end border-t">
                  <v-btn variant="text" color="grey-darken-3" prepend-icon="mdi-close" @click="mostrarModalCobro = false">Cerrar</v-btn>
              </v-card-actions>
          </v-card>
      </v-dialog>
    </v-container>
  </div>
</template>

<script>
import swal from "sweetalert";
import axios from 'axios';
import { usePage } from '@inertiajs/vue3';

export default {
  data() {
    return {
      headers: [
        { title: 'Alumno (Nombre Completo)', key: 'nombre_completo', align: 'start', sortable: true },
        { title: 'Diplomado', key: 'nombre_diplomado', sortable: true },
        { title: 'Campaña y Grupo', key: 'grupo' },
        { title: 'Saldo Pendiente ($)', key: 'Pendiente_Pagar', align: 'end', sortable: true },
        { title: 'Acciones de Caja', key: 'acciones', align: 'center', sortable: false },
      ],
      alumno_id: null,
      busqueda: "",
      filtroDiplomado: null,
      filtros: { nombre: "", diplomados_multi: [], grupo: "" },
      diplomado_id: null,
      AlumnosEstadoPagar: [],
      pago_colegiatura: null,
      fecha_inscripcion: "",
      cuentaDeposito: [],
      pagosColegiaturaAlumno2: [],
      userId: null,
      comprobante: null,
      mostrarModalCobro: false,
      alumnoActivo: null,
    };
  },
  computed: {
    alumnosCalculados() {
        let items = this.AlumnosEstadoPagar;

        // Búsqueda General
        if (this.busqueda.trim() !== "") {
            const q = this.busqueda.toLowerCase();
            items = items.filter(a => a.nombre_completo?.toLowerCase().includes(q));
        }

        // Filtro Dropdown Diplomado Maestro
        if (this.filtroDiplomado) {
            items = items.filter(a => a.nombre_diplomado === this.filtroDiplomado);
        }

        // Filtro Excel: Nombre
        if (this.filtros.nombre.trim() !== "") {
            const q = this.filtros.nombre.toLowerCase();
            items = items.filter(a => a.nombre_completo?.toLowerCase().includes(q));
        }
        
        // Filtro Excel: Diplomados Multi
        if (this.filtros.diplomados_multi.length > 0) {
            items = items.filter(a => this.filtros.diplomados_multi.includes(a.nombre_diplomado));
        }

        // Filtro Excel: Grupo
        if (this.filtros.grupo.trim() !== "") {
            const q = this.filtros.grupo.toLowerCase();
            items = items.filter(a => a.grupo?.toLowerCase().includes(q));
        }

        return items;
    },
    listaDiplomadosUnicos() {
        const unicos = new Set();
        this.AlumnosEstadoPagar.forEach(item => {
            if(item.nombre_diplomado) unicos.add(item.nombre_diplomado);
        });
        return Array.from(unicos);
    }
  },
  created() {
    this.userId = this.$page.props.auth?.user?.id || this.$page.props.userId;
    this.obtenerNumeroCuenta();
    this.setFechaActual();
    this.obtenerListaAlumnos();
  },
  methods: {
    calcularTotalGrupo(items) {
      if (!items || items.length === 0) return 0;
      return items.reduce((acc, item) => {
          // El objeto item suele venir envuelto en una estructura de v-data-table v3
          // Accedemos a la propiedad raw si es necesario, o al valor directo
          const val = item.raw ? parseFloat(item.raw.Pendiente_Pagar) : parseFloat(item.Pendiente_Pagar || 0);
          return acc + (isNaN(val) ? 0 : val);
      }, 0);
    },

    abrirCaja(alumno) {
        this.alumnoActivo = alumno;
        this.obtenerPagosColegiaturas(alumno.alumno_id, alumno.diplomado_id, alumno.nombre_completo);
        this.mostrarModalCobro = true;
    },

    limpiarFormulario() {
        this.pago_colegiatura = '';
        this.selectedTitular = null;
        this.comprobante = null;
    },
    
    // Descarga manual desde el historial
    descargarPDFHistorial(idPagoGenerado) {
        window.open('/pagos/' + idPagoGenerado + '/pdf', '_blank');
    },

    EnviarPago(alumno_context) {
      // Validaciones básicas
      if (!this.pago_colegiatura || !this.selectedTitular || !this.fecha_inscripcion || !this.comprobante) {
          swal("Requerido", "Asegúrese de agregar un monto, la cuenta receptora y subir el comprobante de pago.", "warning");
           return;
      }

      if(this.pago_colegiatura <= 0) {
          swal("Cifra Inválida", "El monto abonado debe ser estrictamente mayor a 0 MXN.", "warning"); return;
      }

      if(alumno_context && this.pago_colegiatura > alumno_context.Pendiente_Pagar) {
          swal("Monto Exorbitante / Bloqueo", `No puedes recibir $${this.pago_colegiatura}. El tope exacto de la deuda restante de este alumno consta de $${alumno_context.Pendiente_Pagar} MXN en el sistema.`, "error"); return;
      }

      let formData = new FormData();
      formData.append('Fecha_PrimerContacto', this.fecha_inscripcion);
      formData.append('pago_colegiatura', this.pago_colegiatura);
      formData.append('tutor', this.userId);
      formData.append('status', 'Activo');
      formData.append('cuentadeposito', this.selectedTitular);
      formData.append('alumno_id', this.alumno_id);
      formData.append('diplomado_id', this.diplomado_id);
      formData.append('comprobante', this.comprobante);

      axios.post('api/v1/pagosabonos/crear', formData, {
          headers: { 'Content-Type': 'multipart/form-data' }
      })
        .then(res => {
          swal("Corte Exitoso", "El pago oficial ha sido timbrado al expediente. Su recibo PDF se está descargando...", "success");
          
          // CAPTURAR EL ID RECIÉN CREADO EN LA BASE DE DATOS Y ENVIAR AL DESCARGABLE PDF
          const idPagoNuevecito = res.data.pago.id;
          
          setTimeout(() => {
              window.open('/pagos/' + idPagoNuevecito + '/pdf', '_blank');
          }, 1000);

          this.limpiarFormulario();
          this.obtenerPagosColegiaturas(this.alumno_id, this.diplomado_id, "Actualización Historial");
          this.obtenerListaAlumnos(); // Refrescar montos pendientes master
        })
        .catch((err) => {
          swal("Error en Caja", "Ocurrió un error en sistema central. Consulta logs.", "error");
          console.error(err);
        });
    },
    
    obtenerPagosColegiaturas(a_id, d_id, nombre_ref) {
      if (!a_id || !d_id) return;

      this.alumno_id = a_id;
      this.diplomado_id = d_id;
      
      const url = `/api/v1/mostrar/alumno/status/${a_id}`;
      axios.get(url)
        .then((res) => {
          this.pagosColegiaturaAlumno2 = res.data.pagosColegiaturaAlumno2;
        })
        .catch((err) => console.error(err));
    },

    obtenerNumeroCuenta() {
      axios.get("/api/v1/cuentadeposito/index/2024/numero")
        .then((res) => this.cuentaDeposito = res.data.cuentaDeposito )
        .catch((err) => console.error(err));
    },

    aplicarFiltros() {
      // Método unificado para ejecutar búsqueda textual y dropdown de diplomado
      let resultados = this.AlumnosEstadoPagar;

      // 1. Filtrar por búsqueda de nombre
      if (this.busqueda.trim() !== "") {
          const term = this.busqueda.toLowerCase();
          resultados = resultados.filter(a => String(a.nombre_completo).toLowerCase().includes(term));
      }

      // 2. Filtrar por el Diplomado Seleccionado
      if (this.filtroDiplomado) {
           resultados = resultados.filter(a => a.nombre_diplomado === this.filtroDiplomado);
      }

      this.alumnosFiltrados = resultados.map(a => ({...a, showModal: false}));

      if(this.alumnosFiltrados.length === 0 && (this.busqueda !== "" || this.filtroDiplomado)){
          swal({icon: "info", title: "Sin Coincidencias", text: "El alumno / expediente buscado no existe o no cuenta con deuda actual.", buttons: "Entendido"});
      }
    },

    buscarAlumnos() {
      this.aplicarFiltros();
    },

    forzarBusqueda(nombre) {
      this.busqueda = nombre;
      this.filtroDiplomado = null;
      this.aplicarFiltros();

      // Buscar el match exacto y auto-abrir el formulario
      const exactMatch = this.alumnosFiltrados.find(a => a.nombre_completo === nombre);
      if (exactMatch) {
          setTimeout(() => {
              this.abrirCaja(exactMatch);
          }, 100);
      }
    },

    limpiarBusqueda() {
      this.busqueda = "";
      this.filtroDiplomado = null;
      this.filtros.nombre = "";
      this.filtros.diplomados_multi = [];
      this.filtros.grupo = "";
    },

    setFechaActual() {
      const today = new Date();
      this.fecha_inscripcion = `${today.getFullYear()}-${String(today.getMonth() + 1).padStart(2, "0")}-${String(today.getDate()).padStart(2, "0")}`;
    },

    obtenerListaAlumnos() {
      axios.get("/api/v1/directorio/pagos/mensualidades")
        .then((response) => {
          this.AlumnosEstadoPagar = response.data.AlumnosEstadoPagar;
        })
        .catch((error) => console.error(error));
    },
  },
};
</script>

<style>
.tabla-excel {
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif !important;
}
.tabla-excel .v-data-table-header th {
    background-color: #f8fafc !important;
    border-right: 1px solid #e2e8f0 !important;
    border-bottom: 2px solid #cbd5e1 !important;
    padding-top: 8px !important;
    padding-bottom: 8px !important;
}
.tabla-excel .v-data-table__tr td {
    border-right: 1px solid #f1f5f9 !important;
    border-bottom: 1px solid #f1f5f9 !important;
}
.tabla-excel .v-data-table__tr:hover {
    background-color: #f1f5f9 !important;
}

.header-excel {
    display: flex;
    flex-direction: column;
    padding: 4px 0;
}
.header-excel-input {
    background: white !important;
    border-radius: 4px !important;
    border: 1px solid #cbd5e1 !important;
    margin-top: 4px !important;
}
.header-excel-input input {
    font-size: 0.75rem !important;
    padding: 4px 8px !important;
}
</style>
