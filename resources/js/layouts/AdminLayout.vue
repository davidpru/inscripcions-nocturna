<script setup lang="ts">
import AdminNavbar from '@/components/ui-layout/AdminNavbar.vue';

defineProps<{
  title?: string;
}>();
</script>

<template>
  <div class="min-h-screen bg-slate-50 dark:bg-slate-900">
    <AdminNavbar />

    <main>
      <slot />
    </main>
  </div>
</template>
