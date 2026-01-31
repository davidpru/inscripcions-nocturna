<script setup lang="ts">
import Footer from '@/components/ui-layout/footer.vue';
import Header from '@/components/ui-layout/header.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from '@/components/ui/table';
import { Link } from '@inertiajs/vue3';
import { Search } from 'lucide-vue-next';
import { computed, ref } from 'vue';

interface Participante {
  nombre: string;
  apellidos: string;
  poblacion: string;
}

interface Inscrito {
  id: number;
  participante: Participante;
  club: string | null;
}

interface Edicion {
  id: number;
  anio: number;
}

const props = defineProps<{
  inscritos: Inscrito[];
  edicion: Edicion;
}>();

const busqueda = ref('');

const inscritosFiltrados = computed(() => {
  if (!busqueda.value.trim()) {
    return props.inscritos;
  }

  const termino = busqueda.value.toLowerCase().trim();
  return props.inscritos.filter((inscrito) => {
    const nombre = inscrito.participante.nombre.toLowerCase();
    const apellidos = inscrito.participante.apellidos.toLowerCase();
    const poblacion = inscrito.participante.poblacion?.toLowerCase() || '';
    const club = inscrito.club?.toLowerCase() || '';

    return (
      nombre.includes(termino) ||
      apellidos.includes(termino) ||
      poblacion.includes(termino) ||
      club.includes(termino)
    );
  });
});
</script>

<template>
  <Header />

  <div class="min-h-screen px-4 py-8">
    <div class="mx-auto max-w-5xl">
      <!-- Header -->
      <div class="mb-8">
        <Link href="/">
          <Button variant="ghost" class="mb-4"> ← Tornar </Button>
        </Link>
        <h1 class="font-expanded mb-2 text-2xl font-bold text-slate-900 md:text-3xl">
          Llistat d'inscrits - Nocturna Fredes Paüls {{ edicion.anio }}
        </h1>
        <p class="text-slate-600">
          Total inscrits: <span class="font-semibold">{{ inscritos.length }}</span>
        </p>
      </div>

      <!-- Buscador -->
      <div class="mb-6">
        <div class="relative max-w-md">
          <Search class="absolute top-1/2 left-3 h-4 w-4 -translate-y-1/2 text-slate-400" />
          <Input
            v-model="busqueda"
            type="text"
            placeholder="Cercar per nom, cognoms, població o club..."
            class="bg-white! pl-10"
          />
        </div>
        <p v-if="busqueda" class="mt-2 text-sm text-slate-500">
          Mostrant {{ inscritosFiltrados.length }} de {{ inscritos.length }} inscrits
        </p>
      </div>

      <!-- Tabla de inscritos -->
      <div class="rounded-lg bg-white shadow">
        <div class="overflow-x-auto">
          <Table>
          <TableHeader>
            <TableRow>
              <TableHead class="w-12">#</TableHead>
              <TableHead>Nom</TableHead>
              <TableHead>Cognoms</TableHead>
              <TableHead>Població</TableHead>
              <TableHead>Club</TableHead>
            </TableRow>
          </TableHeader>
          <TableBody>
            <TableRow
              class="text-uppercase"
              v-for="(inscrito, index) in inscritosFiltrados"
              :key="inscrito.id"
            >
              <TableCell class="font-medium text-slate-500">{{ index + 1 }}</TableCell>
              <TableCell>{{ inscrito.participante.nombre }}</TableCell>
              <TableCell>{{ inscrito.participante.apellidos }}</TableCell>
              <TableCell>{{ inscrito.participante.poblacion || '-' }}</TableCell>
              <TableCell>{{ inscrito.club || '-' }}</TableCell>
            </TableRow>
            <TableRow v-if="inscritosFiltrados.length === 0">
              <TableCell colspan="5" class="py-8 text-center text-slate-500">
                <template v-if="busqueda"> No s'han trobat inscrits amb "{{ busqueda }}" </template>
                <template v-else> Encara no hi ha inscrits </template>
              </TableCell>
            </TableRow>
          </TableBody>
        </Table>
        </div>
      </div>
      <Footer />
    </div>
  </div>
</template>
