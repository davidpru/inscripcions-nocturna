<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Head, useForm } from '@inertiajs/vue3';
import { AlertCircle, Loader2, Lock, Mail, Mountain } from 'lucide-vue-next';

const form = useForm({
  email: '',
  password: '',
  remember: false,
});

const submit = () => {
  form.post('/uec-admin/login', {
    onFinish: () => {
      form.password = '';
    },
  });
};
</script>

<template>
  <Head title="Iniciar Sessió - Admin" />

  <div
    class="flex min-h-screen items-center justify-center bg-linear-to-br from-slate-100 to-slate-200 p-4"
  >
    <div class="w-full max-w-md">
      <!-- Logo y título -->
      <div class="mb-8 text-center">
        <div class="bg-primary mb-4 inline-flex h-16 w-16 items-center justify-center rounded-2xl">
          <Mountain class="text-primary-foreground h-10 w-10" />
        </div>
        <h1 class="text-2xl font-bold text-slate-900">Administració Nocturna</h1>
        <!-- <p class="mt-1 text-slate-500">Accedeix al panell d'administració</p> -->
      </div>

      <Card class="border-0 shadow-xl">
        <CardHeader class="space-y-1 pb-4">
          <CardTitle class="text-xl">Iniciar Sessió</CardTitle>
          <CardDescription> Introdueix les teves credencials per accedir </CardDescription>
        </CardHeader>

        <CardContent>
          <form @submit.prevent="submit" class="space-y-4">
            <!-- Error general -->
            <div
              v-if="form.errors.email || form.errors.password"
              class="bg-destructive/10 text-destructive flex items-center gap-2 rounded-lg p-3 text-sm"
            >
              <AlertCircle class="h-4 w-4 shrink-0" />
              <span>{{ form.errors.email || form.errors.password }}</span>
            </div>

            <!-- Email -->
            <div class="space-y-2">
              <Label for="email">Correu electrònic</Label>
              <div class="relative">
                <Mail class="absolute top-1/2 left-3 h-4 w-4 -translate-y-1/2 text-slate-400" />
                <Input
                  id="email"
                  v-model="form.email"
                  type="email"
                  placeholder="admin@example.com"
                  class="pl-10"
                  :class="{ 'border-destructive': form.errors.email }"
                  autocomplete="email"
                  required
                />
              </div>
            </div>

            <!-- Password -->
            <div class="space-y-2">
              <Label for="password">Contrasenya</Label>
              <div class="relative">
                <Lock class="absolute top-1/2 left-3 h-4 w-4 -translate-y-1/2 text-slate-400" />
                <Input
                  id="password"
                  v-model="form.password"
                  type="password"
                  placeholder="••••••••"
                  class="pl-10"
                  :class="{ 'border-destructive': form.errors.password }"
                  autocomplete="current-password"
                  required
                />
              </div>
            </div>

            <!-- Remember me -->
            <div class="flex items-center gap-2">
              <Checkbox id="remember" v-model="form.remember" />
              <Label for="remember" class="cursor-pointer text-sm font-normal select-none">
                Recorda'm
              </Label>
            </div>

            <!-- Submit -->
            <Button
              type="submit"
              class="w-full"
              size="xl"
              variant="destructive"
              :disabled="form.processing"
            >
              <span v-if="form.processing" class="flex items-center gap-2">
                <Loader2 class="h-4 w-4 animate-spin" />
                Accedint...
              </span>
              <span v-else>Accedir</span>
            </Button>
          </form>
        </CardContent>
      </Card>

      <!-- Link para volver -->
      <div class="mt-6 text-center">
        <a href="/" class="text-sm text-slate-500 transition-colors hover:text-slate-700">
          ← Tornar a la pàgina principal
        </a>
      </div>
    </div>
  </div>
</template>
