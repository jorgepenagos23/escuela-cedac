<template>
  <div class="max-w-8xl mx-auto p-6 lg:p-8">
    <v-card
   
    >
      <v-layout>
        <v-main
        >
          <div>
            <v-card
              class="mx-auto"
              prepend-icon="mdi-school"
              title="Administracion"
              width="auto"
              color="blue-grey-darken-1   "
            >
              <v-row align="center" justify="center" dense>
                <v-col cols="12" md="6">
                  <v-card
                    class="mx-auto"
                    subtitle="Estudiantes"
                    title="Alumnos"
                    color="blue-grey-lighten-5
                    "
                  >
                    <template v-slot:prepend>
                      <v-avatar class="me- mt-2" rounded="0" size="78">
                        <v-img
                          src="https://th.bing.com/th/id/R.6ac043506ac0cd0220fa09ac8557e51d?rik=pn54W8W2K7nYGQ&riu=http%3a%2f%2fwww.pngall.com%2fwp-content%2fuploads%2f5%2fLecture-PNG-Images.png&ehk=%2bLo%2bQkfLJ51v4dp0jEiOKsmFp3oLUYiiDiL5oWSOA3A%3d&risl=&pid=ImgRaw&r=0"
                          cover
                          :with="300"
                        ></v-img>
                      </v-avatar>
                    </template>
                    <template v-slot:append>
                      <v-btn
                        link
                        href="/alumnos"
                        append-icon="mdi-chevron-right"
                        color="blue-grey-darken-1"
                        text="Acceder"
                        variant="elevated"
                        block
                      ></v-btn>
                    </template>
                    <v-card-text>Listado de Alumnos </v-card-text>
                  </v-card>
                </v-col>

                <v-col cols="12" md="6">
                  <v-card
                    class="mx-auto"
                    subtitle="Panel de Diplomados"
                    title="Diplomados"
                    color="blue-darken-3   "
                  >
                    <template v-slot:prepend>
                      <v-avatar class="me- mt-2" rounded="0" size="78">
                        <v-img
                          src="https://cdn3d.iconscout.com/3d/premium/thumb/document-folder-8578222-6805130.png"
                          cover
                          :with="300"
                        ></v-img>
                      </v-avatar>
                    </template>
                    <template v-slot:append>
                      <v-btn
                        link
                        href="/diplomados"
                        append-icon="mdi-chevron-right"
                        color="blue-grey-darken-1"
                        text="Acceder"
                        variant="elevated"
                        block
                      ></v-btn>
                    </template>
                    <v-card-text>Diplomados </v-card-text>
                  </v-card>
                </v-col>
              </v-row>
            </v-card>
            <br /><br />

            <v-card
              class="mx-auto"
              prepend-icon="mdi-school"
              title="Inscripciones"
              width="auto"
              color="light-green-darken-1
                    "
            >
              <v-row align="center" justify="center" dense>
                <v-col cols="12" md="6">
                  <v-card
                    class="mx-auto"
                    subtitle="Seguimiento"
                    title="Inscripciones"
                    color="blue-grey-lighten-5  "
                  >
                    <template v-slot:prepend>
                      <v-avatar class="me-4 mt-2" rounded="0" size="78">
                        <v-img
                          src="https://www.svgrepo.com/show/184220/user-add.svg"
                          cover
                          :with="300"
                        ></v-img>
                      </v-avatar>
                    </template>
                    <template v-slot:append>
                      <v-btn
                        link
                        href="/seguimiento/inscripciones"
                        append-icon="mdi-chevron-right"
                        color="blue-grey-darken-1"
                        text="Acceder"
                        variant="elevated"
                        block
                      ></v-btn>
                    </template>

                    <v-card-text>Generar Pagos de Inscripciones</v-card-text>
                  </v-card>
                </v-col>
                <v-col cols="12" md="6">
                  <v-card
                    append-avatar="https://cdn.vuetifyjs.com/images/john.jpg"
                    class="mx-auto"
                    prepend-avatar="https://cdn.vuetifyjs.com/images/logos/v-alt.svg"
                    subtitle="Gestion de Pagos"
                    title="Consultar Pagos Inscripciones"
                    color="blue-darken-3"
                  >
                    <v-card-text>Consultar Pagos Inscripciones</v-card-text>
                    <template v-slot:append>
                      <v-btn
                        link
                        href="/inscripciones"
                        append-icon="mdi-chevron-right"
                        color="blue-grey-darken-1"
                        text="Acceder"
                        variant="elevated"
                        block
                      ></v-btn>
                    </template>
                    <template v-slot:prepend>
                      <v-avatar class="me-4 mt-2" rounded="0" size="78">
                        <v-img
                          src="https://cdn-icons-png.flaticon.com/512/9084/9084215.png"
                          cover
                          :with="300"
                        ></v-img>
                      </v-avatar>
                    </template>
                  </v-card>
                </v-col>
              </v-row>
            </v-card>
            <br /><br />
            <v-card
              class="mx-auto"
              prepend-icon="mdi-currency-usd"
              title="Cobranza"
              width="auto"
              color="red-darken-4   "
            >
              <v-row align="center" justify="center" dense>
                <v-col cols="12" md="6">
                  <v-card
                    class="mx-auto"
                    subtitle="Registrar Pagos "
                    title="Pagos Mensualidades"
                    color="blue-grey-lighten-5  "
                  >
                    <template v-slot:prepend>
                      <v-avatar class="me- mt-2" rounded="0" size="78">
                        <v-img
                          src="https://cdn3.iconfinder.com/data/icons/cyber-monday-astute-vol-2/512/Pay_Cash-512.png"
                          cover
                          :with="300"
                        ></v-img>
                      </v-avatar>
                    </template>
                    <template v-slot:append>
                      <v-btn
                        link
                        href="/crud-pagos"
                        append-icon="mdi-chevron-right"
                        color="blue-grey-darken-1                                            "
                        text="Acceder"
                        variant="elevated"
                        block
                      ></v-btn>
                    </template>

                    <v-card-text>Generar Pagos de Inscripciones</v-card-text>
                  </v-card>
                </v-col>
                <v-col cols="12" md="6">
                  <v-card
                    append-avatar="https://cdn.vuetifyjs.com/images/john.jpg"
                    class="mx-auto"
                    prepend-avatar="https://cdn.vuetifyjs.com/images/logos/v-alt.svg"
                    subtitle="Gestion de Pagos"
                    title="Consultar Pagos Mensualidades de Matriculas"
                    color="blue-darken-3"
                  >
                    <v-card-text>Consultar Pagos Mensualidades de Matriculas</v-card-text>
                    <template v-slot:append>
                      <v-btn
                        append-icon="mdi-chevron-right"
                        color="blue-grey-darken-1"
                        text="Acceder"
                        variant="elevated"
                        link
                        href="/mensualidades/pagos"
                        block
                      ></v-btn>
                    </template>
                    <template v-slot:prepend>
                      <v-avatar class="me- mt-2" rounded="0" size="78">
                        <v-img
                          src="https://www.easypeasyfinance.com/wp-content/uploads/2021/06/Paycheck-and-Pay-Stub-for-Kids-Teens-Beginners-Frequency.jpg"
                          cover
                          :with="300"
                        ></v-img>
                      </v-avatar>
                    </template>
                  </v-card>
                </v-col>
              </v-row>
            </v-card>

            <v-card
              class="mx-auto"
              prepend-icon="mdi-chart-areaspline"
              title="Resumen"
              width="auto"
              color="white
                "
            >
              <v-row align="left" justify="left" dense>
                <v-col cols="5" md="12">
                  <v-card
                    subtitle="Reportes y Estadisticas "
                    title="Estadisticas"
                    color="teal-darken-4

                                    "
                  >
                    <template v-slot:prepend>
                      <v-avatar class="me- mt-2" rounded="0" size="78">
                        <v-img
                          src="https://cdn1.iconfinder.com/data/icons/social-messaging-ui-black-shapers/3/60-512.png"
                          cover
                          :with="300"
                        ></v-img>
                      </v-avatar>
                    </template>
                    <template v-slot:append>
                      <v-btn
                        link
                        href="/resumen"
                        append-icon="mdi-chevron-right"
                        color="blue-grey-darken-1                                            "
                        text="Acceder"
                        variant="elevated"
                        block
                      ></v-btn>
                    </template>

                    <v-card-text>Estadisticas</v-card-text>
                  </v-card>
                </v-col>
              </v-row>
            </v-card>
          </div>
        </v-main>
      </v-layout>
    </v-card>
  </div>
</template>
<style scoped>
/* Estilos de desplazamiento */
.main-content {
  overflow-y: auto;
  height: calc(
    100vh - 64px
  ); /* Ajusta el valor según el tamaño de tu barra de navegación */
}

/* Estilos de bloqueo de desplazamiento cuando el drawer está abierto */
.scroll-lock {
  overflow-y: hidden;
}
</style>
<script setup>
import "../../css/app.css";
import { Button } from "@/shadcn/ui/button/";
import {
  Card,
  CardContent,
  CardDescription,
  CardFooter,
  CardHeader,
  CardTitle,
} from "@/shadcn/ui/card/";

import {
  AlertDialog,
  AlertDialogAction,
  AlertDialogCancel,
  AlertDialogContent,
  AlertDialogDescription,
  AlertDialogFooter,
  AlertDialogHeader,
  AlertDialogTitle,
  AlertDialogTrigger,
} from "@/shadcn/ui/alert-dialog";

import { ref } from "vue";

const userName = ref("{{ $page.props.auth.user.name }}");

const drawer = ref(true);

const toggleDrawer = () => {
  drawer.value = !drawer.value;
};
</script>

<script>
export default {
  setup() {
    return {};
  },

  data() {
    return {
      drawer: true,
      rail: true,
      review: "30%",
    };
  },

  mounted() {},
};
</script>
