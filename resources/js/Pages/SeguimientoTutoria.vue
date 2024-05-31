<script setup>
import { ref } from "vue";
import { usePage } from "@inertiajs/vue3";
import axios from "axios";
import swal from "sweetalert";
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import { Head } from "@inertiajs/vue3";
import { provide } from "vue";

const page = usePage();
console.log("page", page.props);

const userId = page.props.userId;
console.log("user id  ", userId);
</script>

<template>
    <v-app>
        <v-container class="my-8">
            <v-row>
                <v-col cols="12" sm="15">
                    <v-row align="center">
                        <v-col cols="10">
                            <v-text-field v-model="busqueda" label="Buscar por matrícula o nombre" outlined dense
                                variant="solo" prepend-icon="mdi-account-search-outline"></v-text-field>
                        </v-col>
                        <v-col cols="2">
                            <v-btn @click="buscarAlumnos" color="primary" dark block>
                                <v-icon>mdi-magnify</v-icon>
                            </v-btn>
                            <v-btn @click="limpiarBusqueda" color="error" dark block>
                                <v-icon>mdi-backspace-outline</v-icon>
                            </v-btn>
                        </v-col>
                    </v-row>
                </v-col>
            </v-row>

            <v-card class="mx-auto" max-width="100%" color="white">
                <v-virtual-scroll :items="alumnosFiltrados" item-height="100" style="margin-top: 10px;">
                  <template v-slot:default="{ item: alumnos }">
                    <v-list-item class="custom-list-item" elevation="16">
                      <v-list-item-content class="custom-list-content">
                        <v-list-item-title class="font-weight-bold">
                          <div class="flex flex-col sm:flex-row items-start sm:items-center">
                            <v-chip color="blue-darken-4" class="mb-2 sm:mb-0 sm:mr-2">
                              <v-icon icon="mdi-account-circle-outline" start></v-icon>
                              {{ alumnos.nombre_alumno }}
                            </v-chip>
                            <v-chip color="primary" variant="flat" prepend-icon="mdi-account-multiple">
                              {{ alumnos.nombre_diplomado }}
                            </v-chip>
                          </div>
                        </v-list-item-title>
                      </v-list-item-content>
                      <v-list-item-action>
                        <v-dialog max-width="1000">
                          <template v-slot:activator="{ props: activatorProps }">
                            <v-btn v-bind="activatorProps" height="40" class="text-none mb-4" color="green" size="large" prepend-icon="mdi-credit-card-outline"
                              @click="obtenerPagosColegiaturas(alumnos.alumno_id, alumnos.diplomado_id, alumnos.nombre_alumno)">
                              Agregar Primera Colegiatura
                            </v-btn>
                          </template>
                          <template v-slot:default="{ isActive }">
                            <v-card>
                              <v-card-text>
                                <v-card class="mx-auto" color="white" max-width="100%" min-height="auto" variant="flat">
                                  <v-sheet color="indigo" class="py-2 px-4">
                                    <v-card-item class="flex-wrap">
                                      <template v-slot:prepend>
                                        <v-card-title class="flex items-center justify-start sm:justify-between w-full sm:w-auto">
                                          <v-icon icon="mdi-account-circle" start class="mr-2"></v-icon>
                                          <span class="text-body-1">{{ alumnos.nombre_alumno }}</span>
                                        </v-card-title>
                                      </template>
                                      <v-divider class="my-2 sm:mx-2" vertical></v-divider>
                                      <template v-slot:append>
                                        <v-btn class="ms-0 sm:ms-4 text-none text-body-1 mt-2 sm:mt-0" color="red" size="small" variant="flat">
                                          Pendiente ${{ alumnos.saldo }}
                                        </v-btn>
                                      </template>
                                    </v-card-item>
                                  </v-sheet>
                                  <v-card class="ma-4"  rounded="lg" variant="flat">
                                    <v-card-item>
                                      <v-card-title class="text-body-2 d-flex align-center flex-wrap">
                                        <v-icon color="#949cf7" icon="mdi-calendar" start class="mr-2"></v-icon>
                                        <span class="text-medium-emphasis font-weight-bold">
                                          Fecha de Inscripcion : {{ alumnos.fecha_inscripcion }}
                                        </span>

                                        <v-chip class="w-full sm:w-auto" color="primary" variant="flat" prepend-icon="mdi-account-multiple">
                                            Campaña {{ alumnos.campaña }}
                                          </v-chip>
                                          <v-chip class="w-full sm:w-auto" color="primary" variant="flat" prepend-icon="mdi-account-multiple">
                                            Grupo {{ alumnos.grupo }}
                                          </v-chip>
                                          <v-chip class="w-70 sm:w-auto" color="green" variant="flat" prepend-icon="mdi-account-multiple">
                                            {{ alumnos.nombre_diplomado }}
                                          </v-chip>
                                          <v-chip color="indigo" variant="flat">  Inscripcion   ${{ alumnos.monto_inscripcion }}</v-chip>

                                      </v-card-title>
                                      <v-container>
                                        <v-col v-for="(pago, index) in pagosColegiaturaAlumno2" :key="index">
                                          <v-card>
                                            <v-card-title></v-card-title>

                                            <v-card-text>
                                                <p class="text-red-800 font-medium">
                                                     Sin pagos registrados
                                                  </p>
                                              <a href="#" class="block bg-white py-3 border-t">
                                                <div class="px-4 py-2 flex flex-col sm:flex-row justify-between">

                                                  <div class="text-green-darken-3 text-h6 font-weight-bold">
                                                    $ {{ pago.pago_colegiatura }} MXN
                                                  </div>
                                                  <span class="text-sm font-semibold text-gray-600">
                                                    Fecha de Pago: {{ pago.Fecha_PrimerContacto }}
                                                  </span>
                                                  <span class="text-sm font-semibold text-gray-900">
                                                    ID pago: {{ pago.idpago }}
                                                  </span>
                                                  <span class="text-sm font-semibold text-gray-900">
                                                    Tutor: {{ pago.Tutor }}
                                                  </span>
                                                  <span class="text-sm font-semibold text-gray-900">
                                                    Asesor: {{ pago.Asesor }}
                                                  </span>
                                                </div>
                                                <div class="px-4 py-2 flex flex-col">
                                                  <span class="text-sm font-semibold text-gray-900">
                                                    {{ pago.Titular }}
                                                  </span>
                                                  <span class="text-sm font-semibold text-gray-900">
                                                    No Cuenta: {{ pago.numero_cuenta }}
                                                  </span>
                                                  <span class="text-sm font-semibold text-gray-900">
                                                    {{ pago.CLABE }}
                                                  </span>
                                                  <span class="text-sm font-semibold text-gray-900">
                                                    {{ pago.banco }}
                                                  </span>
                                                </div>
                                              </a>
                                            </v-card-text>

                                          </v-card>
                                        </v-col>
                                      </v-container>
                                    </v-card-item>
                                  </v-card>
                                </v-card>
                                <form class="max-w-full m-4 p-10 bg-gray-50 rounded shadow-xl" @submit.prevent="EnviarPago">
                                  <p class="text-gray-800 font-medium">
                                    Captura el pago de Colegiatura
                                  </p>
                                  <div class="mb-6">
                                    <div class="d-flex align-items-center">
                                      <v-chip class="ma-2" color="deep-orange-darken-4">Monto</v-chip>
                                      <v-text-field v-model="pago_colegiatura" color="green" class="white" variant="outlined" type="number">
                                        $
                                      </v-text-field>
                                    </div>
                                  </div>
                                  <div class="mb-6">
                                    <div class="d-flex align-items-center">
                                      <v-chip class="ma-2" color="black">Alumno</v-chip>
                                      <select v-model="alumno_id" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-deep-orange-darken-4 focus:border-deep-orange-darken-4 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-deep-orange-darken-4 dark:focus:border-deep-orange-darken-4">
                                        <option disabled selected>Selecciona</option>
                                        <option v-for="pago in uniqueDiplomados" :key="pago.id" :value="pago.id">
                                          <div class="mb-6">
                                            <label class="block mb-2 text-sm font-medium text-gray-900 dark:text-gray-300"></label>
                                            <v-text-field required readonly color="blue" variant="outlined" class="w-full px-4 py-2">
                                              {{ alumnos.nombre_alumno }}
                                            </v-text-field>
                                          </div>
                                        </option>
                                      </select>
                                    </div>
                                  </div>
                                  <div class="mb-6">
                                    <div class="d-flex align-items-center">
                                      <v-chip class="ma-2" color="black">Diplomado</v-chip>
                                      <select v-model="diplomado_id" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-deep-orange-darken-4 focus:border-deep-orange-darken-4 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-deep-orange-darken-4 dark:focus:border-deep-orange-darken-4">
                                        <option disabled selected>Selecciona</option>
                                        <option v-for="pago in uniqueDiplomados" :key="pago.id" :value="pago.diplomado_id">
                                          <div class="mb-6">
                                            <label class="block mb-2 text-sm font-medium text-gray-900 dark:text-gray-300"></label>
                                            <v-text-field required readonly color="blue" v-model="asesor" variant="outlined" class="w-full px-4 py-2">
                                              {{ alumnos.nombre_diplomado }}
                                            </v-text-field>
                                          </div>
                                        </option>
                                      </select>
                                    </div>
                                  </div>
                                  <div class="mb-6">
                                    <div class="d-flex align-items-center">
                                      <v-chip class="ma-2" color="black">No de Cuenta</v-chip>
                                      <select v-model="selectedTitular" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-deep-orange-darken-4 focus:border-deep-orange-darken-4 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-deep-orange-darken-4 dark:focus:border-deep-orange-darken-4">
                                        <option disabled selected>Selecciona un Numero de Cuenta</option>
                                        <option v-for="titular in cuentaDeposito" :key="titular.id" :value="titular.id">
                                          {{ titular.titular }}
                                          <option>
                                            {{ titular.CLABE }}
                                          </option>
                                        </option>
                                      </select>
                                    </div>
                                  </div>
                                  <div class="mb-6">
                                    <div class="d-flex align-items-center">
                                      <v-chip class="ma-2" color="black">Fecha</v-chip>
                                      <v-text-field label="Fecha" required readonly color="black" v-model="fecha_inscripcion" type="date" variant="outlined" class="w-full px-4 py-2"></v-text-field>
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
import { ref } from "vue";
import { usePage } from "@inertiajs/vue3";
import axios from "axios";

export default {
    data() {
        return {
            alumno_id: null, // Variable para almacenar el ID del alumno de forma segura

            busqueda: "",
            diplomado_id: null,
            pendienteMesUser: [],
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
            fecha_inscripcion: null,
            NombreDiplomado: [],
            cuentaDeposito: [],
            pagosColegiaturaAlumno2: [],
            alumnoIdSeleccionado: null,
            userId: null, // Definir userId aquí
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
            return this.pagosColegiaturaAlumno2.filter((pago) => {
                const isDuplicate = seen.has(pago.nombre_alumno);
                seen.add(pago.nombre_alumno);
                return !isDuplicate;
            });
        },
        uniqueDiplomados() {
            return this.pagosColegiaturaAlumno2;
        },
    },
    mounted() { },

    methods: {

        limpiarFormulario() {

this.pago_colegiatura = '';
this.selectedTitular = null;
this.monto_inscripcion = '';
this.fecha_primer_pago_colegiatura = '';
},

        EnviarPago() {
            const page = usePage();
            console.log("page", page.props);

            const userId = page.props.userId;
            console.log("user id  ", userId);

            const inscripcion = {
                Fecha_PrimerContacto: this.fecha_inscripcion,
                pago_colegiatura: this.pago_colegiatura,
                tutor: userId, // Usar el id del tutor pasado como prop
                status: "Activo",
                cuentadeposito: this.selectedTitular,
                alumno_id: this.alumno_id, // Usando alumnos.alumno_id
                diplomado_id: this.diplomado_id,
            };

            console.log("datos a enviar ", inscripcion);
            axios
                .post("/api/v1/pagosabonos/crear", inscripcion)
                .then((res) => {
                    console.log(res);
                    console.log("Datos de PAGOS enviados:", inscripcion);

                    swal("Pago registrado con éxito", "success");
                    this.pago_colegiatura = "";
                    this.selectedTitular = "";
                    this.obtenerListaAlumnos();
                })
                .catch((err) => {
                    swal("Llenar completamente los campos");
                    console.error(err);
                });
        },

        obtenerPagosColegiaturas(alumno_id, diplomado_id) {
            console.log("Valor de diplomado :", diplomado_id);
            console.log("Valor de alumno_id:", alumno_id);

            // Verificar si alguno de los valores es nulo
            if (alumno_id === null || diplomado_id === null) {
                console.log(
                    "Alumno_id o diplomado_id es nulo. No se puede realizar la solicitud."
                );
                return; // Salir de la función
            }

            const url = `/api/v1/mostrar/alumno/show_sinpago/${alumno_id}`;

            console.log("Consulta enviada:", url);
            this.enviarDatos(alumno_id, diplomado_id);

            axios
                .get(url)
                .then((res) => {
                    console.log("Valor de alumno_id 2:", alumno_id);
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
                    console.log("Valor de reiniciado:", alumno_id, diplomado_id);
                });
        },

        enviarDatos(alumno_id, diplomado_id) {
            // Aquí puedes enviar los datos a otra parte de tu código o a una API
            console.log("Enviando datos:");
            console.log("Alumno ID:", alumno_id);
            console.log("Diplomado ID:", diplomado_id);
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
                this.alumnosFiltrados = this.pendienteMesUser.map((alumno) => ({
                    ...alumno,
                }));
            } else {
                // Filtrar los alumnos basados en la búsqueda
                this.alumnosFiltrados = this.pendienteMesUser
                    .filter((alumno) => {
                        const nombreCompletoStr = alumno.nombre_alumno
                            ? String(alumno.nombre_alumno).toLowerCase()
                            : "";
                        const busquedaStr = this.busqueda.toLowerCase();
                        const match = nombreCompletoStr.includes(busquedaStr);
                        return match;
                    })
                    .map((alumno) => ({ ...alumno }));
            }

            if (this.alumnosFiltrados.length === 0) {
                this.mostrarAlertaNoresultados();
            }
        },

        limpiarBusqueda() {
            this.busqueda = "";
            // Restaurar la lista de alumnos filtrados a la lista completa de alumnos
            this.alumnosFiltrados = this.pendienteMesUser;
        },

        verAlumno(alumno) { },
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
                .get("/api/v1/mensualidad/seguimiento", {})
                .then((response) => {
                    console.log("Respuesta de la API:", response.data);
                    this.pendienteMesUser = response.data.pendienteMesUser;
                    this.alumnosFiltrados = response.data.pendienteMesUser.map(
                        (alumnos) => ({ ...alumnos })
                    ); // Actualizar alumnosFiltrados
                    // No necesitas asignar alumno_id aquí

                    // Puedes iterar sobre la lista de alumnos y mostrar los ids en la consola
                    this.pendienteMesUser.forEach((alumnos) => {
                        console.log("ID del alumno:", alumnos.alumno_id);
                        console.log("ID del diplomado:", alumnos.diplomado_id);
                    });
                })
                .catch((error) => {
                    console.error("Error al obtener las alumnos:", error);
                });
        },
    },
};
</script>
