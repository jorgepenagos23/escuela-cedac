<template>
    <v-card>
        <v-layout>

            <v-navigation-drawer v-model="drawer" :rail="rail" permanent @click="rail = false" theme="dark">

                <v-list-item
                    prepend-avatar="https://scontent.ftgz1-1.fna.fbcdn.net/v/t39.30808-6/428632247_379547098154227_7700167967896374770_n.jpg?_nc_cat=104&ccb=1-7&_nc_sid=5f2048&_nc_ohc=QhuAsgNe0oEAb5v6z56&_nc_ht=scontent.ftgz1-1.fna&oh=00_AfDAxBppeSswrZoj5aeH2qsB8dMAowkM1Qdu3NbWNqMJzw&oe=66353C1B"
                    nav :title=$page.props.auth.user.name>

                    <template v-slot:append>
                        <v-btn icon="mdi-chevron-left" variant="text" @click.stop="rail = !rail"></v-btn>
                    </template>
                </v-list-item>

                <v-divider></v-divider>

                <v-list density="compact" nav>

                    <v-list-item link href="/dashboard" prepend-icon="mdi-home-city" title="Home"  value="home" ></v-list-item>
                    <v-list-item prepend-icon="mdi-account" title="Mi cuenta" value="account"></v-list-item>
                    <v-list-item prepend-icon="mdi-settings" title="Configuracion" value="Configuracion"></v-list-item>
                </v-list>

            </v-navigation-drawer>
            <v-main style="height: 10px">

            </v-main>
        </v-layout>
    </v-card>



</template>
<style>
.v-main{

    background-color:rgb(201, 199, 198);
    z-index: 3;
}

</style>
<script setup>

import { Button } from '@/shadcn/ui/button/'
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
} from '@/shadcn/ui/alert-dialog'


import { ref } from 'vue';

const userName = ref('{{ $page.props.auth.user.name }}');

const drawer = ref(true);

const toggleDrawer = () => {
    drawer.value = !drawer.value;
};
</script>

<script>

export default {
    setup() {
        return {


        };
    },

    data() {
        return {
            drawer: true,
            rail: true,
        };
    },

    mounted() {
    },
};
</script>
