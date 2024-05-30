<script setup>
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import { Head } from "@inertiajs/vue3";
import TablaAlumnos from "./TablaAlumnos.vue";
</script>

<template>
  <div>
    <!-- Contenido de tu vista -->
    <div class="max-w-9xl mx-auto p-6 lg:p-8">
      <Head title="Cedac" />
      <AuthenticatedLayout>
        <h2 class="font-semibold text-xl text-gray-800 leading-tight"></h2>
        <v-container>
          <div class="leading-loose">
            <v-card class="mx-auto" max-width="auto" max-height="auto" color="light-green
            ">
              <v-card-item class="bg-orange-darken-4">

                  <v-toolbar title="Dar Seguimiento a un nueva matricula"  class="bg-orange-darken-4">
                        <v-toolbar-items>
                            <v-btn


                            link href="/seguimiento/tutorias"
                            >Seguimiento
                        </v-btn>
                        </v-toolbar-items>

                        <v-divider class="mx-2" vertical></v-divider>

                        <v-btn icon="mdi-dots-vertical"></v-btn>
                    </v-toolbar>
              </v-card-item>

              <v-card-text class="pt-4"> Cartera de  Colegiaturas </v-card-text>

              <div class="center">
                <TablaAlumnos></TablaAlumnos>
              </div>
            </v-card>
          </div>
        </v-container>
        <v-card> </v-card>
      </AuthenticatedLayout>
    </div>
  </div>
</template>

<style>
.mycard {
  /* width: 1400px; Eliminado */
  justify-content: center;
  text-align: center;
  position: absolute;
  top: 30%;
  left: 0;
  right: 0;
  transform: translateY(-50%);
  background-color: rgba(255, 255, 255, 0.9);
  z-index: -2;
}

.div {
  text-align: center;
  justify-content: center;
}
</style>

<script>
export default {};
</script>

<style></style>
