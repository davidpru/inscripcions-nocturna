<script setup lang="ts">
import logoLight from '@/assets/logos/logo-nocturna-h-light.svg';
import { Link } from '@inertiajs/vue3';
</script>

<template>
  <header class="bg-destructive shadow-sm">
    <div class="mx-auto flex max-w-7xl items-center gap-10 px-4 py-4 sm:px-6 lg:px-6">
      <Link href="/" class="flex items-center gap-4">
        <img :src="logoLight" alt="Nocturna Fredes Paüls" class="h-10" />
      </Link>

      <div class="none ml-auto md:block">
        <div class="flex items-center gap-6 text-sm font-medium text-white">
          <div>
            <Link href="/" class="hover:underline">Inici</Link>
          </div>
          <div>
            <Link href="/inscripcion" class="hover:underline">Inscripció</Link>
          </div>
          <div>
            <Link href="/inscripcion/consulta" class="hover:underline">Consulta Inscripció</Link>
          </div>
          <div>
            <Link href="/inscripcion/listado" class="hover:underline">Llistat Inscrits</Link>
          </div>
        </div>
      </div>
    </div>
  </header>
</template>

<style scoped></style>
