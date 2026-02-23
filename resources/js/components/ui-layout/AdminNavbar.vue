<script setup lang="ts">
import { Button } from '@/components/ui/button';
import {
  Sheet,
  SheetClose,
  SheetContent,
  SheetHeader,
  SheetTitle,
  SheetTrigger,
} from '@/components/ui/sheet';
import { Link, router, usePage } from '@inertiajs/vue3';
import {
  Calendar,
  ClipboardList,
  CreditCard,
  Home,
  LogOut,
  Menu,
  Ticket,
  UserCog,
} from 'lucide-vue-next';
import { computed } from 'vue';

const page = usePage();
const currentPath = computed(() => page.url);

const navItems = [
  { name: 'Inici', href: '/uec-admin', icon: Home, exact: true },
  { name: 'Inscripcions', href: '/uec-admin/inscripciones', icon: ClipboardList },
  { name: 'Transaccions', href: '/uec-admin/transacciones', icon: CreditCard },
  { name: 'Edicions', href: '/uec-admin/ediciones', icon: Calendar },
  { name: 'Cupons', href: '/uec-admin/cupones', icon: Ticket },
  { name: 'Usuaris', href: '/uec-admin/usuarios', icon: UserCog },
];

const isActiveItem = (item: (typeof navItems)[0]) => {
  if (item.exact) {
    return currentPath.value === item.href;
  }
  return currentPath.value.startsWith(item.href);
};

const logout = () => {
  router.post('/uec-admin/logout');
};
</script>

<template>
  <nav class="sticky top-0 z-50 border-b border-slate-200 bg-slate-800 shadow-sm">
    <div class="max-w-8xl mx-auto px-4 sm:px-6 lg:px-8">
      <div class="flex h-16 items-center justify-between">
        <!-- Logo y navegación desktop -->
        <div class="flex items-center">
          <Link href="/uec-admin" class="shrink-0">
            <span class="text-xl font-bold text-red-500"> Nocturna Admin </span>
          </Link>

          <!-- Navegación desktop -->
          <div class="ml-10 hidden md:block">
            <div class="flex items-center space-x-1">
              <Link
                v-for="item in navItems"
                :key="item.name"
                :href="item.href"
                class="flex items-center gap-2 rounded-md px-3 py-2 text-sm font-medium text-white transition-colors"
                :class="
                  isActiveItem(item)
                    ? 'bg-slate-950 text-red-100'
                    : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900'
                "
              >
                <component :is="item.icon" class="h-4 w-4" />
                {{ item.name }}
              </Link>
            </div>
          </div>
        </div>

        <!-- Botones derecha -->
        <div class="hidden items-center gap-2 md:flex">
          <Link href="/" class="text-sm text-white hover:text-white"> ← Tornar al web </Link>
          <form @submit.prevent="logout" class="inline">
            <Button variant="ghost" size="sm" type="submit" class="gap-2">
              <LogOut class="h-4 w-4" />
              Sortir
            </Button>
          </form>
        </div>

        <!-- Botón menú móvil -->
        <div class="flex md:hidden">
          <Sheet>
            <SheetTrigger as-child>
              <span size="icon text-white">
                <Menu class="h-8 w-8 text-white" />
              </span>
            </SheetTrigger>
            <SheetContent side="right" class="md:hidden">
              <SheetHeader class="text-left">
                <SheetTitle>Menu</SheetTitle>
              </SheetHeader>
              <div class="mt-4 space-y-2">
                <SheetClose v-for="item in navItems" :key="item.name" as-child>
                  <Link
                    :href="item.href"
                    class="flex items-center gap-2 rounded-md px-3 py-2 text-base font-medium"
                    :class="
                      isActiveItem(item)
                        ? 'bg-slate-900 text-green-200'
                        : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900'
                    "
                  >
                    <component :is="item.icon" class="h-5 w-5" />
                    {{ item.name }}
                  </Link>
                </SheetClose>

                <div class="border-t border-slate-200 pt-2 dark:border-slate-700">
                  <SheetClose as-child>
                    <Link
                      href="/"
                      class="flex items-center gap-2 rounded-md px-3 py-2 text-base font-medium text-slate-600 hover:bg-slate-100"
                    >
                      ← Tornar al web
                    </Link>
                  </SheetClose>
                  <form @submit.prevent="logout" class="mt-2">
                    <Button variant="ghost" size="sm" type="submit" class="gap-2">
                      <LogOut class="h-4 w-4" />
                      Sortir
                    </Button>
                  </form>
                </div>
              </div>
            </SheetContent>
          </Sheet>
        </div>
      </div>
    </div>
  </nav>
</template>
