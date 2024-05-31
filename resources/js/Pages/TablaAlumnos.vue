<script setup>
import { ref } from 'vue';
import { usePage } from '@inertiajs/vue3';
import axios from 'axios';
import swal from 'sweetalert';
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import { Head } from "@inertiajs/vue3";
import { provide } from 'vue';

const page = usePage()
console.log('page',page.props)

const userId = page.props.userId;
console.log('user id  ',userId);

</script>
<template>
  <v-app>
    <v-container class="my-8">
      <v-row align="center">
        <v-col cols="12" sm="8" md="6" lg="4">
          <v-text-field
            v-model="busqueda"
            label="Buscar por  o nombre"
            outlined
            dense
            variant="solo"
            prepend-icon="mdi-account-search-outline"
          ></v-text-field>
        </v-col>
        <v-col cols="12" sm="4" md="6" lg="8" class="d-flex justify-end">
          <v-btn @click="buscarAlumnos" color="primary" dark>
            <v-icon>mdi-magnify</v-icon>
          </v-btn>
          <v-btn @click="limpiarBusqueda" color="error" dark>
            <v-icon>mdi-backspace-outline</v-icon>
          </v-btn>
        </v-col>
      </v-row>

      <v-card class="mx-auto" max-width="1000" color="white">
        <v-virtual-scroll
          :items="alumnosFiltrados"
          style="margin-top: 10px"
          item-height="100"
        >
          <template v-slot:default="{ item: alumnos }">
            <v-list-item class="custom-list-item" elevation="16">
              <v-list-item-content class="custom-list-content">
                <v-list-item-title class="font-weight-bold">
                  <v-chip color="black">
                    <v-icon icon="mdi-account-circle-outline" start></v-icon>
                    {{ alumnos.nombre_completo }}
                  </v-chip>
                </v-list-item-title>
              </v-list-item-content>
              <v-list-item-action>
                <v-dialog max-width="1000">
                    <template v-slot:activator="{ props: activatorProps }">
                      <v-btn
                        v-bind="activatorProps"
                        class="mb-2 sm:mb-4"
                        color="green"
                        size="large"
                        variant="flat"
                        prepend-icon="mdi-credit-card-outline"
                        @click="obtenerPagosColegiaturas(alumnos.alumno_id, alumnos.diplomado_id, alumnos.nombre_completo)"
                      >
                        Agregar Colegiatura
                      </v-btn>
                    </template>
                    <template v-slot:default="{ isActive }">
                      <v-card>
                        <v-card-text>
                            <v-card class="mx-auto bg-white dark:bg-gray-800 max-w-full sm:max-w-auto" variant="flat">

                                <v-sheet color="indigo" class="py-2 px-4">
                                    <v-card-item class="flex-wrap">
                                      <template v-slot:prepend>
                                        <v-card-title class="flex items-center justify-start sm:justify-between w-full sm:w-auto">
                                          <v-icon icon="mdi-account-circle" start class="mr-2"></v-icon>
                                          <span class="text-body-1">{{ alumnos.nombre_completo }}</span>
                                        </v-card-title>
                                      </template>

                                      <v-divider class="my-2 sm:mx-2" vertical></v-divider>

                                      <template v-slot:append>
                                        <v-btn class="ms-0 sm:ms-4 text-none text-body-1 mt-2 sm:mt-0" color="red" size="small" variant="flat">
                                          Pendiente ${{ alumnos.Pendiente_Pagar }}
                                        </v-btn>
                                      </template>
                                    </v-card-item>
                                  </v-sheet>

                                <v-card class="m-4 bg-blue-gray-800 rounded-lg" variant="flat">
                                  <v-card-item>
                                    <v-card-title class="text-base md:text-lg lg:text-xl xl:text-2xl font-semibold text-gray-200 dark:text-gray-300 flex flex-col sm:flex-row items-center justify-center sm:justify-start">

                                        <div class="flex flex-col sm:flex-row items-center sm:items-start space-y-2 sm:space-y-0 sm:space-x-4">
                                            <v-icon color="#949cf7" icon="mdi-calendar" start></v-icon>

                                            <span class="text-medium-emphasis font-weight-bold text-sm sm:text-base">
                                              Fecha de Inscripcion: {{ alumnos.fecha_inscripcion }}
                                            </span>

                                            <v-spacer class="hidden sm:block"></v-spacer>

                                            <v-chip class="w-full sm:w-auto" color="primary" variant="flat" prepend-icon="mdi-account-multiple">
                                              Campaña {{ alumnos.campaña }}
                                            </v-chip>

                                            <v-chip class="w-full sm:w-auto" color="primary" variant="flat" prepend-icon="mdi-account-multiple">
                                              Grupo {{ alumnos.grupo }}
                                            </v-chip>
                                          </div>

                                      <div class="flex flex-col sm:flex-row items-center sm:items-start mt-2 sm:mt-0 sm:ml-4">
                                        <v-chip color="green" variant="flat">    {{ alumnos.nombre_diplomado }}</v-chip>
                                        <v-chip color="indigo" variant="flat">  Inscripcion   ${{ alumnos.monto_inscripcion }}</v-chip>
                                      </div>
                                    </v-card-title>
                                    <v-container>
                                      <v-row>
                                        <v-col v-for="(pago, index) in pagosColegiaturaAlumno2" :key="index" class="mb-4" cols="12">
                                          <v-card class="bg-white dark:bg-gray-900 shadow-md rounded-lg">
                                            <v-card-title></v-card-title>
                                            <v-card-text class="p-4">
                                                <p class="text-red-800 font-medium">
                                                    Pagos realizado
                                                      </p>
                                              <div class="flex justify-between items-center mb-2">
                                                <div class="text-green-600 dark:text-green-400 text-lg font-semibold">${{ pago.pago_colegiatura }} MXN</div>
                                                <span class="text-sm font-semibold text-gray-600 dark:text-gray-300">Fecha de Pago : {{ pago.Fecha_PrimerContacto }}</span>
                                              </div>
                                              <div class="flex justify-between items-center">
                                                <span class="text-sm font-semibold text-gray-900 dark:text-gray-200 px-2 py-1">ID pago: {{ pago.idpago }}</span>
                                                <span class="text-sm font-semibold text-gray-900 dark:text-gray-200 px-2 py-1">Tutor: {{ pago.Tutor }}</span>
                                                <span class="text-sm font-semibold text-gray-900 dark:text-gray-200 px-2 py-1">Asesor: {{ pago.Asesor }}</span>
                                              </div>
                                              <div class="mt-2">
                                                <span class="text-sm font-semibold text-gray-900 dark:text-gray-200 px-2 py-1">{{ pago.Titular }}</span>
                                                <span class="text-sm font-semibold text-gray-900 dark:text-gray-200 px-2 py-1">No Cuenta {{ pago.numero_cuenta }}</span>
                                                <span class="text-sm font-semibold text-gray-900 dark:text-gray-200 px-2 py-1">{{ pago.CLABE }}</span>
                                                <span class="text-sm font-semibold text-gray-900 dark:text-gray-200 px-2 py-1">{{ pago.banco }}</span>
                                              </div>
                                            </v-card-text>
                                          </v-card>
                                        </v-col>
                                      </v-row>
                                    </v-container>
                                  </v-card-item>
                                </v-card>
                              </v-card>


                              <form class="max-w-7xl m-4 p-10 bg-gray-50 rounded shadow-xl" @submit.prevent="EnviarPago">
                                <p class="text-gray-800 font-medium">Captura el pago de Colegiatura</p>
                                <div class="mb-6">
                                  <div class="d-flex align-items-center">
                                    <v-chip class="ma-2" color="deep-orange-darken-4">Monto</v-chip>
                                    <v-text-field v-model="pago_colegiatura" color="green" class="white" variant="outlined" type="number">$</v-text-field>
                                  </div>
                                </div>
                                <div class="mb-6">
                                  <div class="d-flex align-items-center">
                                    <v-chip class="ma-2" color="black">Nombre</v-chip>
                                    <select v-model="alumno_id" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-deep-orange-darken-4 -500 focus:border-deep-orange-darken-4 -500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-deep-orange-darken-4 -500 dark:focus:border-deep-orange-darken-4 -500">
                                      <option disabled selected>Selecciona</option>
                                      <option v-for="pago in uniquePagos" :key="pago.id" :value="pago.alumno_id">{{ pago.nombre_completo }}</option>
                                    </select>
                                  </div>
                                </div>
                                <div class="mb-6">
                                  <div class="d-flex align-items-center">
                                    <v-chip class="ma-2" color="black">Diplomado</v-chip>
                                    <select v-model="diplomado_id" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-deep-orange-darken-4 -500 focus:border-deep-orange-darken-4 -500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-deep-orange-darken-4 -500 dark:focus:border-deep-orange-darken-4 -500">
                                      <option disabled selected>Selecciona</option>
                                      <option v-for="pago in uniqueDiplomados" :key="pago.id" :value="pago.diplomado_id">{{ pago.nombre_diplomado }}</option>
                                    </select>
                                  </div>
                                </div>
                                <div class="mb-6">
                                  <div class="d-flex align-items-center">
                                    <v-chip class="ma-2" color="black">No de Cuenta</v-chip>
                                    <select v-model="selectedTitular" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-deep-orange-darken-4 -500 focus:border-deep-orange-darken-4 -500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-deep-orange-darken-4 -500 dark:focus:border-deep-orange-darken-4 -500">
                                      <option disabled selected>Selecciona un Numero de Cuenta</option>
                                      <option v-for="titular in cuentaDeposito" :key="titular.id" :value="titular.id">{{ titular.titular }}
                                        <option>{{ titular.CLABE }}</option>
                                      </option>
                                    </select>
                                  </div>
                                </div>
                                <div class="mb-6">
                                  <div class="d-flex align-items-center">
                                    <v-chip class="ma-2" color="black">Fecha</v-chip>
                                    <v-text-field label="Fecha de Inscripción" required readonly color="black" v-model="fecha_inscripcion" type="date" variant="outlined" class="w-full px-4 py-2"></v-text-field>
                                  </div>
                                </div>


                            <div class="flex items-start mb-6">
                                <div class="flex items-center h-5">
                                    <input id="remember" type="checkbox" value="" class="w-4 h-4 border border-gray-300 rounded bg-gray-50 focus:ring-3 focus:ring-blue-300 dark:bg-gray-700 dark:border-gray-600 dark:focus:ring-blue-600 dark:ring-offset-gray-800" required>
                                </div>
                                <label for="remember" class="ml-2 text-sm text-gray-900 dark:text-gray-400">Los datos ingresados son correctos.</label>
                            </div>
                                    <v-btn color="green" size="large" variant="elevated" type="submit" prepend-icon="mdi-send">Enviar</v-btn>
                                    <v-btn @click="limpiarFormulario" color="red" size="large" variant="elevated" prepend-icon="mdi-eraser">Vaciar</v-btn>

                              </form>



                        </v-card-text>
                        <v-card-actions>
                          <v-spacer></v-spacer>
                          <v-btn text="Cerrar" @click="isActive.value = false"></v-btn>
                        </v-card-actions>
                      </v-card>
                    </template>
                  </v-dialog>


              </v-list-item-action>
            </v-list-item>
          </template>
        </v-virtual-scroll>
      </v-card>
    </v-container>
  </v-app>
</template>



<style scoped>
@media (max-width: 639px) {
    .text-sm {
      font-size: 0.875rem; /* Tailwind text-sm */
    }

    .w-full {
      width: 100%;
    }
  }

.v-card-item {
    display: flex;
    flex-direction: column;
    align-items: start;
  }

  @media (min-width: 640px) {
    .v-card-item {
      flex-direction: row;
      align-items: center;
    }
  }
.custom-list-item {
  border-radius: 8px;
  margin: 10px;
}

.custom-list-content {
  padding: 10px; /* Espaciado interno */
}

.custom-btn {
  background-color: rgb(7, 121, 16); /* Color de fondo personalizado */
  color: white; /* Color del texto personalizado */
  text-align: left;
  justify-content: left;
  border: 1px solid teal; /* Borde personalizado */
}

.custom-btn:hover {
  background-color: #26a01b; /* Cambiar color al pasar el ratón sobre el botón */
}
</style>

<script>
import Swal from "sweetalert"; // Asegúrate de usar "sweetalert2"
import { ref } from 'vue';
import { usePage } from '@inertiajs/vue3';
import axios from 'axios';



export default {

  data() {
    return {
        alumno_id: null, // Variable para almacenar el ID del alumno de forma segura
        alumnos: {
        alumno_id: null, // Variable donde se almacena el ID del alumno del v-model
        // Otras propiedades de alumnos...
      },
      busqueda: "",
      diplomado_id: null,
      AlumnosEstadoPagar: [],
      alumnosFiltrados: [],
      dialog: false,
      alumnoSeleccionado: [],
      pago_colegiatura: null,
      fecha: null,
      mostrarDialog1: false,
      mostrarDialog2: false,
      mostrarDialog3: false,
      selectedAlumno: null,
      selectedDiplomado: null,
      selectedTitular: null,
      Alumnos: [],
      alumnoSeleccionadoId: null,
      search: "",
      fecha_inscripcion: "",
      NombreDiplomado: [],
      cuentaDeposito: [],
      pagosColegiaturaAlumno2:[],
      alumnoIdSeleccionado: null,
      userId:null, // Definir userId aquí

    };
  },
  created() {
    this.obtenerNumeroCuenta();
    this.setFechaActual();
    this.obtenerListaAlumnos();

  },

  computed: {
    uniquePagos() {
      const seen = new Set();
      return this.pagosColegiaturaAlumno2.filter(pago => {
        const isDuplicate = seen.has(pago.nombre_completo);
        seen.add(pago.nombre_completo);
        return !isDuplicate;
      });
    },
    uniqueDiplomados() {
      const seen = new Set();
      return this.pagosColegiaturaAlumno2.filter(pago => {
        const isDuplicate = seen.has(pago.nombre_diplomado);
        seen.add(pago.nombre_diplomado);
        return !isDuplicate;
      });
    }



  },
  mounted() {

},

  methods: {
    limpiarFormulario() {

            this.pago_colegiatura = '';
            this.selectedTitular = null;
            this.monto_inscripcion = '';
            this.fecha_primer_pago_colegiatura = '';
        },

  EnviarPago ()  {
    const page = usePage()
console.log('page',page.props)

const userId = page.props.userId;
console.log('user id  ',userId);



    const inscripcion = {
      Fecha_PrimerContacto: this.fecha_inscripcion,
      pago_colegiatura: this.pago_colegiatura,
      tutor: userId, // Usar el id del tutor pasado como prop
      status: 'Activo',
      cuentadeposito: this.selectedTitular,
      alumno_id: this.alumno_id, // Usando alumnos.alumno_id
      diplomado_id: this.diplomado_id
    };


    axios.post('api/v1/pagosabonos/crear', inscripcion)
      .then(res => {
        console.log(res);
        console.log('Datos de PAGOS enviados:', inscripcion);

        swal("Pago registrado con éxito", "success");
        this.pago_colegiatura='';
        this.fecha_inscripcion='';
        this.selectedTitular='';
        this.obtenerListaAlumnos();
      })
      .catch((err) => {
        swal("Llenar completamente el formuluario");
        console.error(err);
      });
  },
    obtenerPagosColegiaturas(alumno_id, diplomado_id) {
      console.log('Valor de diplomado :', diplomado_id);
      console.log('Valor de alumno_id:', alumno_id);

      // Verificar si alguno de los valores es nulo
      if (alumno_id === null || diplomado_id === null) {
        console.log('Alumno_id o diplomado_id es nulo. No se puede realizar la solicitud.');
        return; // Salir de la función
      }

      const url = `/api/v1/mostrar/alumno/status/${alumno_id}`;

      console.log('Consulta enviada:', url);
      this.enviarDatos(alumno_id, diplomado_id);

      axios.get(url)
        .then((res) => {
          console.log('Valor de alumno_id 2:', alumno_id);
          this.pagosColegiaturaAlumno2 = res.data.pagosColegiaturaAlumno2;
          console.log("colegiaturas del alumno", res.data);
        })
        .catch((err) => {
          console.error(err);
        })
        .finally(() => {
          // Reiniciar el valor de alumno_id a null después de que termine la petición
          this.alumno_id = null;
          this.diplomado_id = null;

          console.log('Valor de reiniciado:', alumno_id, diplomado_id);
        });
    },


    enviarDatos(alumno_id, diplomado_id) {
      // Aquí puedes enviar los datos a otra parte de tu código o a una API
      console.log('Enviando datos:');
      console.log('Alumno ID:', alumno_id);
      console.log('Diplomado ID:', diplomado_id);
    },

    obtenerNumeroCuenta() {
      axios
        .get("/api/v1/cuentadeposito/index/2024/numero")
        .then((res) => {
          this.cuentaDeposito = res.data.cuentaDeposito;
          console.log(res);
        })
        .catch((err) => {
          console.error(err);
        });
    },
    buscarAlumnos() {
      if (this.busqueda.trim() === "") {
        // Si la búsqueda está vacía, mostrar todos los alumnos
        this.alumnosFiltrados = this.AlumnosEstadoPagar.map(alumno => ({ ...alumno }));
      } else {
        // Filtrar los alumnos basados en la búsqueda
        this.alumnosFiltrados = this.AlumnosEstadoPagar.filter((alumno) => {
          const nombreCompletoStr = alumno.nombre_completo
            ? String(alumno.nombre_completo).toLowerCase()
            : "";
          const busquedaStr = this.busqueda.toLowerCase();
          const match = nombreCompletoStr.includes(busquedaStr);
          return match;
        }).map(alumno => ({ ...alumno }));
      }

      if (this.alumnosFiltrados.length === 0) {
        this.mostrarAlertaNoresultados();
      }
    },


    limpiarBusqueda() {
      this.busqueda = "";
      // Restaurar la lista de alumnos filtrados a la lista completa de alumnos
      this.alumnosFiltrados = this.AlumnosEstadoPagar;
    },

    verAlumno(alumno) {},
    mostrarAlertaNoresultados() {
      Swal.fire({
        icon: "info",
        title: "No se hallaron resultados",
        text: "No hay alumnos que coincidan con la búsqueda",
        confirmButtonText: "Aceptar",
      });
    },
    cerrarDialogo() {
      this.dialog = false;
      this.alumnoSeleccionado = {};
    },
    setFechaActual() {

      const today = new Date();
      const year = today.getFullYear();
      const month = String(today.getMonth() + 1).padStart(2, "0");
      const day = String(today.getDate()).padStart(2, "0");
      this.fecha_inscripcion = `${year}-${month}-${day}`;
    },
    obtenerListaAlumnos() {
      axios
        .get("/api/v1/directorio/pagos/mensualidades", {})
        .then((response) => {
          console.log("Respuesta de la API:", response.data);
          this.AlumnosEstadoPagar = response.data.AlumnosEstadoPagar;
          this.alumnosFiltrados = response.data.AlumnosEstadoPagar.map(alumnos => ({ ...alumnos })); // Actualizar alumnosFiltrados
          // No necesitas asignar alumno_id aquí

          // Puedes iterar sobre la lista de alumnos y mostrar los ids en la consola
          this.AlumnosEstadoPagar.forEach(alumnos => {
            console.log('ID del alumno:', alumnos.alumno_id);
            console.log('ID del diplomado:', alumnos.diplomado_id);
          });
        })
        .catch((error) => {
          console.error("Error al obtener las alumnos:", error);
        });
    },

  },
};
</script>
