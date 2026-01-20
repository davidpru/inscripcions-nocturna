<script setup lang="ts">
import logoLight from '@/assets/logos/logo-nocturna-h-light.svg';
import { Link } from '@inertiajs/vue3';
</script>

<template>
  <header class="bg-red-600 shadow-sm dark:bg-red-600">
    <div class="mx-auto flex max-w-7xl items-center gap-10 px-4 py-4 sm:px-6 lg:px-8">
      <Link href="/" class="flex items-center gap-4">
        <img :src="logoLight" alt="Nocturna Fredes Paüls" class="h-10" />
      </Link>
    </div>
  </header>
</template>

<style scoped></style>
