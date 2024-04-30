<template>
    <v-card>
        <v-layout>
            <v-navigation-drawer
                v-model="drawer"
                :rail="rail"
                permanent
                @click="rail = false"
            >
                <v-list-item
                    prepend-avatar="https://scontent.ftgz1-1.fna.fbcdn.net/v/t39.30808-6/428632247_379547098154227_7700167967896374770_n.jpg?_nc_cat=104&ccb=1-7&_nc_sid=5f2048&_nc_ohc=QhuAsgNe0oEAb5v6z56&_nc_ht=scontent.ftgz1-1.fna&oh=00_AfDAxBppeSswrZoj5aeH2qsB8dMAowkM1Qdu3NbWNqMJzw&oe=66353C1B"
                    nav
                    :title="$page.props.auth.user.name"
                >
                    <template v-slot:append>
                        <v-btn
                            icon="mdi-chevron-left"
                            variant="text"
                            @click.stop="rail = !rail"
                        ></v-btn>
                    </template>
                </v-list-item>

                <v-divider></v-divider>

                <v-list density="compact" nav>
                    <v-list-item
                        link
                        href="/dashboard"
                        prepend-icon="mdi-home-city"
                        title="Home"
                        value="home"
                    ></v-list-item>
                    <v-list-item
                        prepend-icon="mdi-account"
                        title="Mi cuenta"
                        value="account"
                    ></v-list-item>
                    <v-list-item
                        prepend-icon="mdi-settings"
                        title="Configuracion"
                        value="Configuracion"
                    ></v-list-item>
                </v-list>
            </v-navigation-drawer>
            <v-main>
                <div >
                    <v-card
                        class="mx-auto"
                        prepend-icon="mdi-school"
                        subtitle=""
                        width="auto"
                        color="light-blue-darken-4
"
                    >
                        <template v-slot:title>
                            <span class="font-weight-black"
                                >SECCION ALUMNOS</span
                            >
                        </template>

                        <v-card-text class="bg-surface-light pt-4">
                        </v-card-text>

                        <v-row align="center" justify="center" dense>
                            <v-col cols="12" md="6">
                                <v-card
                                    class="mx-auto"
                                    subtitle="Estudiantes"
                                    title="Alumnos"
                                >
                                    <template v-slot:prepend>
                                        <v-avatar class="me- mt-2" rounded="0" size="78">
                                            <v-img
                                                src="https://previews.123rf.com/images/scrap4vec/scrap4vec2005/scrap4vec200500184/148077898-dise%C3%B1o-de-logotipo-de-ni%C3%B1o-estudiante-o-personas-con-plantilla-de-libro-en-forma-creativa-aislar.jpg"
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
                                            color="light-blue-darken-4"
                                            text="Acceder"
                                            variant="elevated"
                                            block
                                        ></v-btn>
                                    </template>
                                    <v-card-text>Diplomados </v-card-text>
                                </v-card>
                            </v-col>

                            <v-col cols="12" md="6">
                                <v-card
                                    class="mx-auto"
                                    subtitle="Panel de Diplomados"
                                    title="Diplomados"
                                >
                                    <template v-slot:prepend>
                                        <v-avatar class="me- mt-2" rounded="0" size="78">
                                            <v-img
                                                src="https://img.myloview.ru/murals/student-logo-700-191507992.jpg"
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
                                            color="light-blue-darken-4"
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
                        prepend-icon="mdi-currency-usd"
                        subtitle=""
                        width="auto"
                        color="blue-grey-darken-1
"
                    >
                        <template v-slot:title>
                            <span class="font-weight-black">SECCION INSCRIPCION ALUMNOS </span>
                        </template>

                        <v-card-text class="bg-surface-light pt-4">
                        </v-card-text>

                        <v-row align="center" justify="center" dense>
                            <v-col cols="12" md="6">
                                <v-card
                                    class="mx-auto"
                                    subtitle="Seguimiento"
                                    title="Inscripciones"
                                >
                                    <template v-slot:prepend>
                                        <v-avatar color="green-accent-2">
                                            <v-icon
                                                icon="mdi-currency-usd"
                                            ></v-icon>
                                        </v-avatar>
                                    </template>
                                    <template v-slot:append>
                                        <v-btn
                                            append-icon="mdi-chevron-right"
                                            color="green-accent-2"
                                            text="Acceder"
                                            variant="elevated"
                                            block
                                        ></v-btn>
                                    </template>

                                    <v-card-text
                                        >Generar Pagos de
                                        Inscripciones</v-card-text
                                    >
                                </v-card>
                            </v-col>
                            <v-col cols="12" md="6">
                                <v-card
                                    append-avatar="https://cdn.vuetifyjs.com/images/john.jpg"
                                    class="mx-auto"
                                    prepend-avatar="https://cdn.vuetifyjs.com/images/logos/v-alt.svg"
                                    subtitle="Gestion de Pagos"
                                    title="Consultar Pagos Reinscripcion
"
                                >
                                    <v-card-text>Consultar  Pagos Reinscripcion</v-card-text>
                                    <template v-slot:append>
                                        <v-btn
                                            append-icon="mdi-chevron-right"
                                            color="green-accent-2"
                                            text="Acceder"
                                            variant="elevated"
                                            block
                                        ></v-btn>
                                    </template>
                                    <template v-slot:prepend>
                                        <v-avatar class="me-4 mt-2" rounded="0" size="78">
                                            <v-img
                                                src="https://www.hsbc.com.mx/content/dam/hsbc/mx/images/Digital/estados-de-cuenta/img_365.jpg"
                                                cover
                                                :with="300"

                                            ></v-img>
                                        </v-avatar>
                                    </template>
                                </v-card>
                            </v-col>
                        </v-row>
                    </v-card>
                <br><br>

                    <v-card
                        class="mx-auto"
                        prepend-icon="mdi-currency-usd"
                        subtitle=""
                        width="auto"
                        color="red
"
                    >
                        <template v-slot:title>
                            <span class="font-weight-black">SECCION COBRANZA</span>
                        </template>

                        <v-card-text class="bg-surface-light pt-4">
                        </v-card-text>

                        <v-row align="center" justify="center" dense>
                            <v-col cols="12" md="6">
                                <v-card
                                    class="mx-auto"
                                    subtitle="Consultas "
                                    title="Pagos Mensualidades"
                                >
                                    <template v-slot:prepend>
                                        <v-avatar class="me- mt-2" rounded="0" size="78">
                                            <v-img
                                                src="https://www.icvnl.gob.mx/img/Web%20ICV/Iconos%20(ma%CC%81s%20visto)/Pago%20refrendo.png"
                                                cover
                                                :with="300"
                                            ></v-img>

                                        </v-avatar>
                                    </template>
                                    <template v-slot:append>
                                        <v-btn
                                            append-icon="mdi-chevron-right"
                                            color="red-lighten-2"
                                            text="Acceder"
                                            variant="elevated"
                                            block
                                        ></v-btn>
                                    </template>

                                    <v-card-text
                                        >Generar Pagos de
                                        Inscripciones</v-card-text
                                    >
                                </v-card>
                            </v-col>
                            <v-col cols="12" md="6">
                                <v-card
                                    append-avatar="https://cdn.vuetifyjs.com/images/john.jpg"
                                    class="mx-auto"
                                    prepend-avatar="https://cdn.vuetifyjs.com/images/logos/v-alt.svg"
                                    subtitle="Gestion de Pagos"
                                    title="Consultar Pagos Mensualidades de Matriculas"
                                >
                                    <v-card-text>Consultar Pagos Mensualidades de Matriculas</v-card-text>
                                    <template v-slot:append>
                                        <v-btn
                                            append-icon="mdi-chevron-right"
                                            color="red-lighten-2"
                                            text="Acceder"
                                            variant="elevated"
                                            block
                                        ></v-btn>
                                    </template>
                                    <template v-slot:prepend>
                                        <v-avatar class="me- mt-2" rounded="0" size="78">
                                            <v-img
                                                src="https://www.icvnl.gob.mx/img/Web%20ICV/Iconos%20(ma%CC%81s%20visto)/EstadodeCuenta.png"
                                                cover

                                                :with="300"

                                            ></v-img>
                                        </v-avatar>
                                    </template>
                                </v-card>
                            </v-col>
                        </v-row>
                    </v-card>



                </div>
            </v-main>
        </v-layout>
    </v-card>
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
