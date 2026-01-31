<script setup lang="ts">
import logo from '@/assets/logos/logo-nocturna-h-current-new.svg?raw';
import {
  NavigationMenu,
  NavigationMenuItem,
  NavigationMenuLink,
  NavigationMenuList,
  navigationMenuTriggerStyle,
} from '@/components/ui/navigation-menu';
import { Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

const navLinks = [
  { href: '/', label: 'Inici' },
  // { href: '/inscripcion', label: 'Inscripció' },
  { href: '/inscripcions/consulta', label: 'Consulta Inscripció' },
  { href: '/inscripcions/inscrits', label: 'Llistat Inscrits' },
];

const currentPath = computed(() => usePage().url);

const isActive = (href: string) => {
  if (href === '/') return currentPath.value === '/';
  return currentPath.value.startsWith(href);
};
</script>

<template>
  <header>
    <div class="mx-auto flex max-w-7xl items-center gap-10 px-4 py-4 md:px-16">
      <div class="flex items-center gap-4">
        <Link
          href="/"
          class="flex shrink-0 items-center justify-center rounded-full border border-black! px-4 py-3 text-black md:px-6 md:py-2"
        >
          <span class="inline-block h-5 md:h-8 [&>svg]:h-full [&>svg]:w-auto" v-html="logo" />
        </Link>
        <h1 class="text-sm font-bold">UEC Tortosa</h1>
      </div>

      <NavigationMenu :viewport="false" class="ml-auto hidden md:flex">
        <NavigationMenuList class="rounded-full bg-white p-1">
          <NavigationMenuItem v-for="link in navLinks" :key="link.href">
            <NavigationMenuLink :class="navigationMenuTriggerStyle()" as-child>
              <Link
                :href="link.href"
                :class="['h-auto rounded-full! px-4! py-2!', isActive(link.href) && 'bg-gray-200']"
              >
                {{ link.label }}
              </Link>
            </NavigationMenuLink>
          </NavigationMenuItem>
        </NavigationMenuList>
      </NavigationMenu>
    </div>
  </header>
</template>

<style scoped></style>
