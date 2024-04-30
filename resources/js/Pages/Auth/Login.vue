<script setup>
import Checkbox from '@/Components/Checkbox.vue';
import GuestLayout from '@/Layouts/GuestLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

defineProps({
    canResetPassword: {
        type: Boolean,
    },
    status: {
        type: String,
    },
});

const form = useForm({
    email: '',
    password: '',
    remember: false,
});

const submit = () => {
    form.post(route('login'), {
        onFinish: () => form.reset('password'),
    });
};
</script>

<template>
    <GuestLayout>
        <Head title="Sistema Cedac" />

        <div v-if="status" class="mb-4 font-medium text-sm text-green-600 ">
            {{ status }}
        </div>

        <form @submit.prevent="submit">
            <div>


                <input class="w-full p-4 text-sm bg-gray-50 focus:outline-none border
                border-gray-200 rounded text-gray-600"

                placeholder="Email"
                id="email"
                type="email"
                 v-model="form.email"
                required
                 autofocus
                autocomplete="username"
                >



                <InputError class="mt-2" :message="form.errors.email" />
            </div>

            <div class="mt-4">

                <input
                     placeholder="Contraseña"
                    id="password"
                    type="password"
                    class="w-full p-4 text-sm bg-gray-50 focus:outline-none border
                     border-gray-200 rounded text-gray-600"
                    v-model="form.password"
                    required
                    autocomplete="current-password"
                />



                <InputError class="mt-2" :message="form.errors.password" />
            </div>

            <div class="block mt-4">
                <label class="flex items-center">
                    <Checkbox name="remember" v-model:checked="form.remember" />
                    <span class="ms-2 text-sm text-gray-600">Recuerdame</span>
                </label>
            </div>

            <div class="flex items-center justify-end mt-4">
                <Link
                    v-if="canResetPassword"
                    :href="route('password.request')"
                    class="underline text-sm text-gray-600 hover:text-gray-900 rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
                >
             Olvidaste tu Contraseña?
                </Link>

                <PrimaryButton class="ms-4" :class="{ 'opacity-25': form.processing }" :disabled="form.processing">
                   Iniciar Sesion
                </PrimaryButton>
            </div>
        </form>
    </GuestLayout>
</template>
