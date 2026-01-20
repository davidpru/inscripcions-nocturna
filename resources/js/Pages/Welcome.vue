<script setup>
import { Head } from '@inertiajs/vue3';

defineProps({
    message: String
});
</script>

<template>
    <Head title="Bienvenido" />

    <div class="min-h-screen bg-gray-100 flex items-center justify-center">
        <div class="max-w-2xl mx-auto p-8 bg-white rounded-lg shadow-lg">
            <h1 class="text-4xl font-bold text-gray-900 mb-4">
                ¡Laravel 12 + Inertia + Vue 3!
            </h1>
            <p class="text-lg text-gray-600 mb-6">
                {{ message }}
            </p>
            <div class="space-y-2 text-sm text-gray-500">
                <p>✅ Laravel 12 instalado</p>
                <p>✅ Inertia.js configurado</p>
                <p>✅ Vue 3 funcionando</p>
                <p>✅ Vite configurado</p>
                <p>✅ MySQL en Docker</p>
            </div>
        </div>
    </div>
</template>
