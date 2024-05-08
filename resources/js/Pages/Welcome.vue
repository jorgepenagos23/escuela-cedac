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

    <div
        class="bg-white dark:bg-gray-900"
    >
    <body class="bg-white">
        <header>
          <nav class="p-6">
            <div class="flex justify-between items-center">
              <h1 class="pr-6 border-r-2 text-2xl font-bold text-gray-500">Centro de Desarrollo y Actualización de Competencias
            </h1>
              <div class="flex justify-between flex-grow">
                <div class="flex ml-6 items-center">
                  <span>
                  </span>
                </div>
                <div class="md:flex space-x-6 hidden">
                    <div v-if="canLogin" >
                        <Link
                            v-if="$page.props.auth.user"
                            :href="route('dashboard')"
                            class="font-semibold text-gray-600 hover:text-gray-900 dark:text-gray-400 dark:hover:text-white focus:outline focus:outline-2 focus:rounded-sm focus:outline-red-500"
                            >Sistema Cedac</Link
                        >

                        <template v-else>
                            <Link
                                :href="route('login')"
                                class="font-semibold text-gray-600 hover:text-gray-900 dark:text-gray-400 dark:hover:text-white focus:outline focus:outline-2 focus:rounded-sm focus:outline-red-500"
                                >Sistema CEDAC</Link
                            >


                <!-- Create By Joker Banny
                 <Link
                                v-if="canRegister"
                                :href="route('register')"
                                class="ms-4 font-semibold text-gray-600 hover:text-gray-900 dark:text-gray-400 dark:hover:text-white focus:outline focus:outline-2 focus:rounded-sm focus:outline-red-500"
                                >Registro</Link
                            >
                -->

                        </template>
                    </div>

                </div>
              </div>
            </div>
          </nav>
          <!-- Section Hero -->
          <div class="container mx-auto h-96 rounded-md flex items-center w-screen" style="background-image: url('https://cedac.mx/pluginfile.php/85/course/overviewfiles/INDUCCION.jpg'); background-size: cover;">
            <div class="sm:ml-20 text-gray-50 text-center sm:text-left">
              <h1 class="text-5xl font-bold mb-4">

                <br />

              </h1>
              <p class="text-lg inline-block sm:block"></p>
              <button class="mt-8 px-4 py-2 bg-gray-600 rounded">Inscribete</button>
            </div>
          </div>
        </header>
        <main class="py-16 container mx-auto px-6 md:px-0">
          <section>
            <h1 class="text-3xl font-bold text-gray-600 mb-10">Diplomados</h1>
            <div class="grid sm:grid-cols-3 gap-4 grid-cols-2">
              <div>
                <div class="bg-gray-300 h-80" style="background-image: url('https://scontent.ftgz1-2.fna.fbcdn.net/v/t39.30808-6/439015288_416664924442444_625007343669538073_n.jpg?_nc_cat=100&ccb=1-7&_nc_sid=5f2048&_nc_eui2=AeG-2Tt552bL-sqhXQzQKdEwOVM-8OAZq2s5Uz7w4Bmra6WIqvPth1TOm_ckaog1J6_61StpYabRCM0OE9CD-uRh&_nc_ohc=gEyh40Ypwd8Q7kNvgFzWPLz&_nc_ht=scontent.ftgz1-2.fna&oh=00_AfBL78mbWAXlm45GPHajvSohUQq63gJ5tuu7GRs70s771Q&oe=663FA869'); background-size: 100%;"></div>
                <h3 class="text-lg font-semibold text-gray-500 mt-2" style="text-justify: ">
                  𝐃𝐈𝐏𝐋𝐎𝐌𝐀𝐃𝐎 𝐄𝐍 𝐄𝐒𝐓𝐑𝐀𝐓𝐄𝐆𝐈𝐀𝐒 𝐃𝐄 𝐋𝐈𝐓𝐈𝐆𝐀𝐂𝐈𝐎́𝐍 𝐄𝐍 𝐋𝐀 𝐑𝐄𝐅𝐎𝐑𝐌𝐀 𝐋𝐀𝐁𝐎𝐑𝐀𝐋

                    <span class="text-gray-700"></span></h3>

              </div>
              <div>
                <div class="bg-gray-300 h-80" style="background-image: url('https://scontent.ftgz1-1.fna.fbcdn.net/v/t39.30808-6/439015363_416664524442484_1479507299115418737_n.jpg?_nc_cat=106&ccb=1-7&_nc_sid=5f2048&_nc_eui2=AeE8TIk79M0yF_qqpLQhOaR8svcgmnS0ja6y9yCadLSNrv7qHYhHnxx4i1YP1YlqblozggGsFQnO0pS6jlJb6QDg&_nc_ohc=fBszOKZ8FWoQ7kNvgH9i9Bj&_nc_ht=scontent.ftgz1-1.fna&oh=00_AfBja07ZkevWKqmu_dwvqligO-xkounDHVQ7q0z1GvgC7w&oe=663F8963'); background-size: 100%;"></div>
                <h3 class="text-lg font-semibold text-gray-500 mt-2">
                    𝐃𝐈𝐏𝐋𝐎𝐌𝐀𝐃𝐎 𝐄𝐍 𝐒𝐀𝐋𝐔𝐃 𝐏𝐔́𝐁𝐋𝐈𝐂𝐀 𝐘 𝐄𝐏𝐈𝐃𝐄𝐌𝐈𝐎𝐋𝐎𝐆𝐈́𝐀
                    <span class="text-gray-700"></span></h3>
              </div>
              <div>
                <div class="bg-gray-300 h-80" style="background-image: url('https://scontent.ftgz1-2.fna.fbcdn.net/v/t39.30808-6/439044185_416664141109189_2800529667337489035_n.jpg?_nc_cat=102&ccb=1-7&_nc_sid=5f2048&_nc_eui2=AeGBRnsmYxnQ8v0VrIkLYRMnwBxXLpICmJXAHFcukgKYlalc4Y7siUmUG2wjIDDpgZF8SolaIFe2YFYdhyvqVNpV&_nc_ohc=JTAPh4bw34YQ7kNvgG_cNqj&_nc_ht=scontent.ftgz1-2.fna&oh=00_AfAexhsweWVlVdX39tfrh049azCA2Z5g0kK3xSh_9UFj1g&oe=663FA544'); background-size: 100%;"></div>
                <h3 class="text-lg font-semibold text-gray-500 mt-2">
                    𝐃𝐈𝐏𝐋𝐎𝐌𝐀𝐃𝐎 𝐄𝐍 𝐃𝐈𝐒𝐄𝐍̃𝐎 𝐂𝐔𝐑𝐑𝐈𝐂𝐔𝐋𝐀𝐑 𝐘 𝐀𝐂𝐑𝐄𝐃𝐈𝐓𝐀𝐂𝐈𝐎́𝐍 𝐃𝐄 𝐏𝐑𝐎𝐆𝐑𝐀𝐌𝐀𝐒 𝐃𝐄 𝐒𝐀𝐋𝐔𝐃

                    <span class="text-gray-700"></span></h3>
              </div>
            </div>
            <hr class="w-40 my-14 border-4 rounded-md sm:mx-0 mx-auto" />
          </section>
          <section>
            <h1 class="inline-block text-gray-600 font-bold text-3xl">
                CEDAC es una institución educativa de vanguardia dirigida mediante la iniciativa privada, con registro ante secretaria del trabajo y previsión social del estado de Chiapas, adjunto a la secretaria de Educación Pública.
                <br />

            </h1>

            <div class="grid grid-cols-3 gap-4 mt-10">
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
          <div class="grid gap-5 grid-cols-5 container mx-auto space-x-6">
            <div>
              <h1 class="text-2xl font-bold text-gray-500"></h1>
              <p></p>
              <spa>Cedac 2024</spa>
            </div>
            <div class="pt-2">
              <ul>
                <li></li>
                <li>Acerca </li>
                <li>F.A.Q</li>
                <li>Ayuda</li>
                <li>Contacto</li>
              </ul>
            </div>


            <div class="pt-2">
              <div>
                <div class="flex space-x-4 text-gray-500 text-center">
                  <span
                    ><svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg
                  ></span>
                  <span
                    ><svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" /></svg
                  ></span>
                  <span
                    ><svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 8l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2M5 3a2 2 0 00-2 2v1c0 8.284 6.716 15 15 15h1a2 2 0 002-2v-3.28a1 1 0 00-.684-.948l-4.493-1.498a1 1 0 00-1.21.502l-1.13 2.257a11.042 11.042 0 01-5.516-5.517l2.257-1.128a1 1 0 00.502-1.21L9.228 3.683A1 1 0 008.279 3H5z" /></svg
                  ></span>
                </div>
                <div class="flex mt-12">
                  <p>Privacy & Cookies</p>
                  <p>Terms of use</p>
                </div>
              </div>
            </div>
          </div>
        </footer>
      </body>

        <div class="max-w-7xl mx-auto p-6 lg:p-8">

    <!-- Create By Joker Banny -->


            <div class="flex justify-center mt-16 px-6 sm:items-center sm:justify-between">
                <div class="text-center text-sm sm:text-start">&nbsp;</div>

                <div class="text-center text-sm text-gray-500 dark:text-gray-400 sm:text-end sm:ms-0">
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
