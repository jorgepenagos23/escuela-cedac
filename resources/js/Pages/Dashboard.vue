<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head } from '@inertiajs/vue3';
import  Navigation from '../Components/NavigationMenu.vue';
import "../../css/app.css";


</script>

<template>
    <AuthenticatedLayout>
        <Head>
            <title>Dashboard</title>
        </Head>
    </AuthenticatedLayout>
 <Navigation></Navigation>

</template>

<script>

</script>
