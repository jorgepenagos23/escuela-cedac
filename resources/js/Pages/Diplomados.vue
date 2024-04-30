<template>
    <div>
        <Estadisticas />
        <!-- Contenido de tu vista -->

        <v-card class="mycard" flat>

            <v-card-title class="d-flex align-center pe-2">


                <v-spacer></v-spacer>

                <v-text-field
                    v-model="search"
                    density="compact"
                    label="Search"
                    prepend-inner-icon="mdi-magnify"
                    variant="solo-filled"
                    flat
                    hide-details
                    single-line
                ></v-text-field>
            </v-card-title>

            <v-divider></v-divider>
            <v-data-table v-model:search="search" :items="items">
                <template v-slot:header.stock>
                    <div class="text-end">Stock</div>
                </template>

                <template v-slot:item.image="{ item }">
                    <v-card class="my-2" elevation="2" rounded>
                        <v-img
                            :src="`https://tirant.com/wp-content/uploads/2022/07/derecho-internacional.jpg`"
                            height="140"
                            cover
                        ></v-img>
                    </v-card>
                </template>

                <template v-slot:item.rating="{ item }">
                    <v-rating
                        :model-value="item.rating"
                        color="orange-darken-2"
                        density="compact"
                        size="small"
                        readonly
                    ></v-rating>
                </template>

                <template v-slot:item.stock="{ item }">
                    <div class="text-end">
                        <v-chip
                            :color="item.stock ? 'green' : 'red'"
                            :text="item.stock ? 'Activo' : 'Inactivo'"
                            class="text-uppercase"
                            size="small"
                            label
                        ></v-chip>
                    </div>
                </template>
            </v-data-table>
        </v-card>
    </div>
</template>
<style>

.mycard {
width: 1400px;
justify-content: center;
text-align: center;
    position: absolute;
  top: 10;
  left: 0;
  right: 0;
  background-color: rgba(255, 255, 255, 0.9);
  z-index: -2;
}

</style>
<script>
import Estadisticas from "./Estadisticas.vue";

export default {
    components: {
        Estadisticas,
    },

    data() {
        return {
            search: "",
            items: [
                {
                    name: "Diplomado 1",
                    image: "1.png",
                    price: 7000,
                    rating: 5,
                    stock: true,
                },
                {
                    name: "Juicios Orales",
                    image: "1.png",
                    price: 7000,
                    rating: 5,
                    stock: true,
                },
                {
                    name: "Desarrollo de Software",
                    image: "1.png",
                    price: 7000,
                    rating: 5,
                    stock: true,
                },

            ],
        };
    },
};
</script>
<style scoped>
/* Estilos específicos para esta vista */
</style>
