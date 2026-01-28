<script setup lang="ts">
import AdminNavbar from '@/components/ui-layout/AdminNavbar.vue';

defineProps<{
  title?: string;
}>();
</script>

<template>
  <div class="min-h-screen bg-slate-50">
    <AdminNavbar />

    <main>
      <slot />
    </main>
  </div>
</template>
