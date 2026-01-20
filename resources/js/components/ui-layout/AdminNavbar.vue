<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Link, usePage } from '@inertiajs/vue3';
import { Calendar, ClipboardList, Home, Menu, X } from 'lucide-vue-next';
import { computed, ref } from 'vue';

const mobileMenuOpen = ref(false);

const page = usePage();
const currentPath = computed(() => page.url);

const isActive = (path: string) => {
  return currentPath.value.startsWith(path);
};

const navItems = [
  { name: 'Dashboard', href: '/admin', icon: Home, exact: true },
  { name: 'Inscripciones', href: '/admin/inscripciones', icon: ClipboardList },
  { name: 'Ediciones', href: '/admin/ediciones', icon: Calendar },
  // { name: 'Participantes', href: '/admin/participantes', icon: Users },
];

const isActiveItem = (item: (typeof navItems)[0]) => {
  if (item.exact) {
    return currentPath.value === item.href;
  }
  return currentPath.value.startsWith(item.href);
};
</script>

<template>
  <nav class="border-b border-slate-200 bg-white shadow-sm dark:border-slate-700 dark:bg-slate-800">
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
      <div class="flex h-16 items-center justify-between">
        <!-- Logo y navegación desktop -->
        <div class="flex items-center">
          <Link href="/admin" class="flex-shrink-0">
            <span class="text-xl font-bold text-red-600 dark:text-red-500"> Nocturna Admin </span>
          </Link>

          <!-- Navegación desktop -->
          <div class="ml-10 hidden md:block">
            <div class="flex items-center space-x-1">
              <Link
                v-for="item in navItems"
                :key="item.name"
                :href="item.href"
                class="flex items-center gap-2 rounded-md px-3 py-2 text-sm font-medium transition-colors"
                :class="
                  isActiveItem(item)
                    ? 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400'
                    : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900 dark:text-slate-300 dark:hover:bg-slate-700 dark:hover:text-slate-100'
                "
              >
                <component :is="item.icon" class="h-4 w-4" />
                {{ item.name }}
              </Link>
            </div>
          </div>
        </div>

        <!-- Botones derecha -->
        <div class="hidden items-center gap-4 md:flex">
          <Link
            href="/"
            class="text-sm text-slate-500 hover:text-slate-700 dark:text-slate-400 dark:hover:text-slate-200"
          >
            ← Volver al sitio
          </Link>
        </div>

        <!-- Botón menú móvil -->
        <div class="flex md:hidden">
          <Button variant="ghost" size="icon" @click="mobileMenuOpen = !mobileMenuOpen">
            <Menu v-if="!mobileMenuOpen" class="h-6 w-6" />
            <X v-else class="h-6 w-6" />
          </Button>
        </div>
      </div>
    </div>

    <!-- Menú móvil -->
    <div v-if="mobileMenuOpen" class="border-t border-slate-200 md:hidden dark:border-slate-700">
      <div class="space-y-1 px-2 pt-2 pb-3">
        <Link
          v-for="item in navItems"
          :key="item.name"
          :href="item.href"
          class="flex items-center gap-2 rounded-md px-3 py-2 text-base font-medium"
          :class="
            isActiveItem(item)
              ? 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400'
              : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900 dark:text-slate-300 dark:hover:bg-slate-700 dark:hover:text-slate-100'
          "
          @click="mobileMenuOpen = false"
        >
          <component :is="item.icon" class="h-5 w-5" />
          {{ item.name }}
        </Link>

        <div class="border-t border-slate-200 pt-2 dark:border-slate-700">
          <Link
            href="/"
            class="flex items-center gap-2 rounded-md px-3 py-2 text-base font-medium text-slate-600 hover:bg-slate-100 dark:text-slate-300 dark:hover:bg-slate-700"
            @click="mobileMenuOpen = false"
          >
            ← Volver al sitio
          </Link>
        </div>
      </div>
    </div>
  </nav>
</template>
