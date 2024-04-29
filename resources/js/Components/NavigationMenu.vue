<template>
    <v-card>
        <v-layout>
            <!-- Botón para dispositivos móviles -->

            <v-navigation-drawer v-model="drawer" :rail="rail" permanent @click="rail = false">

                <v-list-item
                    prepend-avatar="https://scontent.ftgz1-1.fna.fbcdn.net/v/t39.30808-6/428632247_379547098154227_7700167967896374770_n.jpg?_nc_cat=104&ccb=1-7&_nc_sid=5f2048&_nc_ohc=QhuAsgNe0oEAb5v6z56&_nc_ht=scontent.ftgz1-1.fna&oh=00_AfDAxBppeSswrZoj5aeH2qsB8dMAowkM1Qdu3NbWNqMJzw&oe=66353C1B"
                    nav :title=$page.props.auth.user.name>

                    <template v-slot:append>
                        <v-btn icon="mdi-chevron-left" variant="text" @click.stop="rail = !rail"></v-btn>
                    </template>
                </v-list-item>

                <v-divider></v-divider>

                <v-list density="compact" nav>

                    <v-list-item prepend-icon="mdi-home-city" title="Home" value="home"></v-list-item>
                    <v-list-item prepend-icon="mdi-account" title="My Account" value="account"></v-list-item>
                    <v-list-item prepend-icon="mdi-account-group-outline" title="Users" value="users"></v-list-item>
                </v-list>

            </v-navigation-drawer>
            <v-main style="height: 450px">

                <div class="div-dashboard">
                    <div class="max-w-7xl w-full mx-auto py-6 sm:px-6 lg:px-8">
                        <div class="flex flex-col lg:flex-row w-full lg:space-x-2 space-y-2 lg:space-y-0 mb-2 lg:mb-4">

                            <div class="w-full lg:w-1/5">
                                <div class="widget w-full p-4 rounded-lg bg-white border-l-4 border-purple-400">
                                    <div class="flex items-center">
                                        <div class="icon w-14 p-3.5 bg-purple-400 text-white rounded-full mr-3">
                                            <svg fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"></path>
                                            </svg>
                                        </div>
                                        <div class="flex flex-col justify-center">
                                            <div class="text-lg">230k</div>
                                            <div class="text-sm text-gray-400">Sales</div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="w-full lg:w-1/5">
                                <div class="widget w-full p-4 rounded-lg bg-white border-l-4 border-blue-400">
                                    <div class="flex items-center">
                                        <div class="icon w-14 p-3.5 bg-blue-400 text-white rounded-full mr-3">
                                            <svg fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                                            </svg>
                                        </div>
                                        <div class="flex flex-col justify-center">
                                            <div class="text-lg">3456</div>
                                            <div class="text-sm text-gray-400">Customers</div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="w-full lg:w-1/5">
                                <div class="widget w-full p-4 rounded-lg bg-white border-l-4 border-yellow-400">
                                    <div class="flex items-center">
                                        <div class="icon w-14 p-3.5 bg-yellow-400 text-white rounded-full mr-3">
                                            <svg fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4" />
                                            </svg>
                                        </div>
                                        <div class="flex flex-col justify-center">
                                            <div class="text-lg">3456</div>
                                            <div class="text-sm text-gray-400">Products</div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="w-full lg:w-1/5">
                                <div class="widget w-full p-4 rounded-lg bg-white border-l-4 border-red-400">
                                    <div class="flex items-center">
                                        <div class="icon w-14 p-3.5 bg-red-400 text-white rounded-full mr-3">
                                            <svg fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z" />
                                            </svg>
                                        </div>
                                        <div class="flex flex-col justify-center">
                                            <div class="text-lg">12658</div>
                                            <div class="text-sm text-gray-400">Orders</div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="w-full lg:w-1/5">
                                <div class="widget w-full p-4 rounded-lg bg-white border-l-4 border-green-400">
                                    <div class="flex items-center">
                                        <div class="icon w-14 p-3.5 bg-green-400 text-white rounded-full mr-3">
                                            <svg fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                            </svg>
                                        </div>
                                        <div class="flex flex-col justify-center">
                                            <div class="text-lg">$948'560</div>
                                            <div class="text-sm text-gray-400">Revenue</div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                        </div>
                    </div>

                </div>
            </v-main>
        </v-layout>
    </v-card>



</template>

<script setup>

import "../../css/app.css";
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
