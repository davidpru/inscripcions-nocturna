<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Checkbox } from '@/components/ui/checkbox';
import { Head, useForm } from '@inertiajs/vue3';
import { Mountain, Lock, Mail, AlertCircle } from 'lucide-vue-next';

const form = useForm({
  email: '',
  password: '',
  remember: false,
});

const submit = () => {
  form.post('/admin/login', {
    onFinish: () => {
      form.password = '';
    },
  });
};
</script>

<template>
  <Head title="Iniciar Sessió - Admin" />
  
  <div class="min-h-screen flex items-center justify-center bg-linear-to-br from-slate-100 to-slate-200 p-4">
    <div class="w-full max-w-md">
      <!-- Logo y título -->
      <div class="text-center mb-8">
        <div class="inline-flex items-center justify-center w-16 h-16 bg-primary rounded-2xl mb-4">
          <Mountain class="w-10 h-10 text-primary-foreground" />
        </div>
        <h1 class="text-2xl font-bold text-slate-900">Administració Nocturna</h1>
        <p class="text-slate-500 mt-1">Accedeix al panell d'administració</p>
      </div>

      <Card class="shadow-xl border-0">
        <CardHeader class="space-y-1 pb-4">
          <CardTitle class="text-xl">Iniciar Sessió</CardTitle>
          <CardDescription>
            Introdueix les teves credencials per accedir
          </CardDescription>
        </CardHeader>
        
        <CardContent>
          <form @submit.prevent="submit" class="space-y-4">
            <!-- Error general -->
            <div 
              v-if="form.errors.email || form.errors.password" 
              class="flex items-center gap-2 p-3 rounded-lg bg-destructive/10 text-destructive text-sm"
            >
              <AlertCircle class="w-4 h-4 shrink-0" />
              <span>{{ form.errors.email || form.errors.password }}</span>
            </div>

            <!-- Email -->
            <div class="space-y-2">
              <Label for="email">Correu electrònic</Label>
              <div class="relative">
                <Mail class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400" />
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
                <Lock class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400" />
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
              <Checkbox 
                id="remember" 
                :checked="form.remember"
                @update:checked="form.remember = $event"
              />
              <Label for="remember" class="text-sm font-normal cursor-pointer">
                Recorda'm
              </Label>
            </div>

            <!-- Submit -->
            <Button 
              type="submit" 
              class="w-full"
              :disabled="form.processing"
            >
              <span v-if="form.processing" class="flex items-center gap-2">
                <svg class="animate-spin h-4 w-4" viewBox="0 0 24 24">
                  <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" fill="none" />
                  <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z" />
                </svg>
                Accedint...
              </span>
              <span v-else>Accedir</span>
            </Button>
          </form>
        </CardContent>
      </Card>

      <!-- Link para volver -->
      <div class="text-center mt-6">
        <a href="/" class="text-sm text-slate-500 hover:text-slate-700 transition-colors">
          ← Tornar a la pàgina principal
        </a>
      </div>
    </div>
  </div>
</template>
