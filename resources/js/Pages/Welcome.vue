<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Head } from '@inertiajs/vue3';

defineProps<{
  message: string;
}>();
</script>

<template>
  <Head title="Bienvenido" />

  <div class="flex min-h-screen items-center justify-center bg-gray-100">
    <div class="mx-auto max-w-2xl rounded-lg bg-white p-8 shadow-lg">
      <h1 class="mb-4 text-4xl font-bold text-gray-900">¡Laravel 12 + Inertia + Vue 3!</h1>
      <p class="mb-6 text-lg text-gray-600">
        {{ message }}
      </p>
      <div class="mb-6 space-y-2 text-sm text-gray-500">
        <p>✅ Laravel 12 instalado</p>
        <p>✅ Inertia.js configurado</p>
        <p>✅ Vue 3 funcionando</p>
        <p>✅ Vite configurado</p>
        <p>✅ MySQL en Docker</p>
        <p>✅ shadcn-vue instalado</p>
      </div>
      <div class="flex gap-4">
        <Button>Botón por defecto</Button>
        <Button variant="secondary">Secundario</Button>
        <Button variant="destructive">Destructivo</Button>
        <Button variant="outline">Outline</Button>
      </div>
    </div>
  </div>
</template>
