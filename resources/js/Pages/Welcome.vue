<script setup>
import { Head, Link } from '@inertiajs/vue3';

defineProps({
    canLogin: {
        type: Boolean,
    },
    canRegister: {
        type: Boolean,
    },
    laravelVersion: {
        type: String,
        required: true,
    },
    phpVersion: {
        type: String,
        required: true,
    },
});
</script>

<template>
    <Head title="Welcome" />

    <div class="bg-white dark:bg-gray-900">
        <header>
            <nav class="p-6">
                <div class="flex justify-between items-center">
                    <h1 class="text-2xl font-bold text-gray-500 pr-6 border-r-2">Centro de Desarrollo y Actualización de Competencias</h1>
                    <div class="flex-grow flex justify-between items-center ml-6">
                        <div class="flex items-center">
                            <span></span>
                        </div>
                        <div class="space-x-6 flex">
                            <div v-if="canLogin">
                                <Link
                                    v-if="$page.props.auth.user"
                                    :href="route('dashboard')"
                                    class="font-semibold text-gray-600 hover:text-gray-900 dark:text-gray-400 dark:hover:text-white focus:outline focus:outline-2 focus:rounded-sm focus:outline-red-500"
                                >Sistema Cedac</Link>

                                <template v-else>
                                    <Link
                                        :href="route('login')"
                                        class="font-semibold text-gray-600 hover:text-gray-900 dark:text-gray-400 dark:hover:text-white focus:outline focus:outline-2 focus:rounded-sm focus:outline-red-500"
                                    >Sistema CEDAC</Link>
                                </template>
                            </div>
                        </div>
                    </div>
                </div>
            </nav>



        </header>

        <main class="py-16 container mx-auto px-6 md:px-0">
            <div class="container mx-auto h-64 sm:h-64 md:h-[36rem] lg:h-[48rem] rounded-md flex items-center justify-center w-full bg-cover bg-center" style="background-image: url('https://cedac.mx/pluginfile.php/85/course/overviewfiles/INDUCCION.jpg');">
                <div class="text-gray-50 text-center sm:text-left sm:ml-20">
                    <h1 class="text-3xl sm:text-4xl md:text-5xl font-bold mb-4"><br /></h1>
                    <p class="text-base sm:text-lg md:text-xl"></p>
                </div>
            </div>

            <section>
                <h1 class="text-3xl font-bold text-gray-600 mb-10">Diplomados</h1>
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                    <div>
                        <img src="./../../../public/images/diplo1.jpg" alt="Descripción de la imagen">
                        <h3 class="text-lg font-semibold text-gray-500 mt-2">𝐃𝐈𝐏𝐋𝐎𝐌𝐀𝐃𝐎 𝐄𝐍 𝐄𝐒𝐓𝐑𝐀𝐓𝐄𝐆𝐈𝐀𝐒 𝐃𝐄 𝐋𝐈𝐓𝐈𝐆𝐀𝐂𝐈𝐎́𝐍 𝐄𝐍 𝐋𝐀 𝐑𝐄𝐅𝐎𝐑𝐌𝐀 𝐋𝐀𝐁𝐎𝐑𝐀𝐋</h3>
                    </div>
                    <div>
                        <img src="./../../../public/images/diplo2.jpg" alt="Descripción de la imagen">
                        <h3 class="text-lg font-semibold text-gray-500 mt-2">𝐃𝐈𝐏𝐋𝐎𝐌𝐀𝐃𝐎 𝐄𝐍 𝐒𝐀𝐋𝐔𝐃 𝐏𝐔́𝐁𝐋𝐈𝐂𝐀 𝐘 𝐄𝐏𝐈𝐃𝐄𝐌𝐈𝐎𝐋𝐎𝐆𝐈́𝐀</h3>
                    </div>
                    <div>
                        <img src="./../../../public/images/diplo3.jpg" alt="Descripción de la imagen">
                        <h3 class="text-lg font-semibold text-gray-500 mt-2">𝐃𝐈𝐏𝐋𝐎𝐌𝐀𝐃𝐎 𝐄𝐍 𝐃𝐈𝐒𝐄𝐍̃𝐎 𝐂𝐔𝐑𝐑𝐈𝐂𝐔𝐋𝐀𝐑 𝐘 𝐀𝐂𝐑𝐄𝐃𝐈𝐓𝐀𝐂𝐈𝐎́𝐍 𝐃𝐄 𝐏𝐑𝐎𝐆𝐑𝐀𝐌𝐀𝐒 𝐃𝐄 𝐒𝐀𝐋𝐔𝐃</h3>
                    </div>
                </div>
                <hr class="w-40 my-14 border-4 rounded-md mx-auto sm:mx-0" />
            </section>
            <section>
                <h1 class="inline-block text-3xl font-bold text-gray-600 text-justify">CEDAC es una institución educativa de vanguardia dirigida mediante la iniciativa privada, con registro ante secretaria del trabajo y previsión social del estado de Chiapas, adjunto a la secretaria de Educación Pública.</h1>
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 mt-10">
                    <div>
                        <h3 class="text-lg font-semibold text-gray-500 mt-2"></h3>
                        <p class="text text-gray-400"></p>
                    </div>
                    <div>
                        <h3 class="text-lg font-semibold text-gray-500 mt-2"></h3>
                        <p class="text text-gray-400"></p>
                    </div>
                    <div>
                        <h3 class="text-lg font-semibold text-gray-500 mt-2"></h3>
                        <p class="text text-gray-400"></p>
                    </div>
                </div>
            </section>
            <div class="mt-14">
                <p></p>
            </div>
        </main>

        <footer class="mb-6 px-6 md:px-0">
            <div class="container mx-auto grid gap-5 grid-cols-1 md:grid-cols-5 space-y-6 md:space-y-0">
                <div>
                    <h1 class="text-2xl font-bold text-gray-500"></h1>
                    <p></p>
                    <span>Cedac 2024</span>
                </div>
                <div class="pt-2">
                    <ul>
                        <li>Acerca</li>
                        <li>F.A.Q</li>
                        <li>Ayuda</li>
                        <li>Contacto</li>
                    </ul>
                </div>
                <div class="pt-2 md:col-span-3">
                    <div class="flex space-x-4 text-gray-500 justify-center md:justify-start">
                        <span>
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </span>
                        <span>
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                            </svg>
                        </span>
                        <span>
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 8l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2M5 3a2 2 0 00-2 2v1c0 8.284 6.716 15 15 15h1a2 2 0 002-2v-3.28a1 1 0 00-.684-.948l-4.493-1.498a1 1 0 00-1.21.502l-1.13 2.257a11.042 11.042 0 01-5.516-5.517l2.257-1.128a1 1 0 00.502-1.21L9.228 3.683A1 1 0 008.279 3H5z" />
                            </svg>
                        </span>
                    </div>
                    <div class="flex justify-center md:justify-start mt-12 space-x-4">
                        <p>Privacy & Cookies</p>
                        <p>Terms of use</p>
                    </div>
                </div>
            </div>
        </footer>

        <div class="max-w-7xl mx-auto p-6 lg:p-8">
            <div class="flex justify-center mt-16 px-6 sm:items-center sm:justify-between">
                <div class="text-center text-sm sm:text-start">&nbsp;</div>
                <div class="text-center text-sm text-gray-500 dark:text-gray-400 sm:text-end sm:ml-0">
                    Laravel v{{ laravelVersion }} (PHP v{{ phpVersion }})
                </div>
            </div>
        </div>
    </div>
</template>

<style>
.bg-dots-darker {
    background-image: url("data:image/svg+xml,%3Csvg width='30' height='30' viewBox='0 0 30 30' fill='none' xmlns='http://www.w3.org/2000/svg'%3E%3Cpath d='M1.22676 0C1.91374 0 2.45351 0.539773 2.45351 1.22676C2.45351 1.91374 1.91374 2.45351 1.22676 2.45351C0.539773 2.45351 0 1.91374 0 1.22676C0 0.539773 0.539773 0 1.22676 0Z' fill='rgba(0,0,0,0.07)'/%3E%3C/svg%3E");
}
@media (prefers-color-scheme: dark) {
    .dark\:bg-dots-lighter {
        background-image: url("data:image/svg+xml,%3Csvg width='30' height='30' viewBox='0 0 30 30' fill='none' xmlns='http://www.w3.org/2000/svg'%3E%3Cpath d='M1.22676 0C1.91374 0 2.45351 0.539773 2.45351 1.22676C2.45351 1.91374 1.91374 2.45351 1.22676 2.45351C0.539773 2.45351 0 1.91374 0 1.22676C0 0.539773 0.539773 0 1.22676 0Z' fill='rgba(255,255,255,0.07)'/%3E%3C/svg%3E");
    }
}
</style>
