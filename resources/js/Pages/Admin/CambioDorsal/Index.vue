<script setup lang="ts">
import { Badge } from '@/components/ui/badge';
import AdminLayout from '@/layouts/AdminLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { CheckCircle, Clock, Copy, ExternalLink, Trash2, XCircle } from 'lucide-vue-next';
import { computed, onUnmounted, ref } from 'vue';

interface ParticipantInfo {
  nom: string;
  dni: string;
  email?: string;
}

interface CanviDorsal {
  id: number;
  token: string;
  estado: 'pendiente' | 'completado' | 'caducado';
  precio: number;
  expires_at: string;
  segons_restants: number;
  created_at: string;
  url: string;
  inscripcion_id: number | null;
  participant_original: ParticipantInfo | null;
  nou_participant: ParticipantInfo | null;
  nom_participant_original: string | null;
}

const props = defineProps<{
  canvis: CanviDorsal[];
}>();

// Countdown reactiu per als pendents
const ticks = ref(0);
let interval: ReturnType<typeof setInterval> | null = null;
if (props.canvis.some((c) => c.estado === 'pendiente')) {
  interval = setInterval(() => ticks.value++, 1000);
}
onUnmounted(() => {
  if (interval) clearInterval(interval);
});

const segsActuals = (canvi: CanviDorsal) => {
  if (canvi.estado !== 'pendiente') return 0;
  const diff = Math.floor((new Date(canvi.expires_at).getTime() - Date.now()) / 1000);
  return Math.max(0, diff);
};

const formatCountdown = (segs: number) => {
  if (segs <= 0) return 'Caducat';
  const h = Math.floor(segs / 3600);
  const m = Math.floor((segs % 3600) / 60);
  const s = segs % 60;
  return `${String(h).padStart(2, '0')}h ${String(m).padStart(2, '0')}m ${String(s).padStart(2, '0')}s`;
};

const formatDate = (iso: string) => {
  const d = new Date(iso);
  return d.toLocaleString('ca-ES', {
    day: '2-digit',
    month: '2-digit',
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
  });
};

const copiat = ref<number | null>(null);
const copiarUrl = async (canvi: CanviDorsal) => {
  await navigator.clipboard.writeText(canvi.url);
  copiat.value = canvi.id;
  setTimeout(() => (copiat.value = null), 2000);
};

const pendents = computed(() => props.canvis.filter((c) => c.estado === 'pendiente'));
const completats = computed(() => props.canvis.filter((c) => c.estado === 'completado'));
const caducats = computed(() => props.canvis.filter((c) => c.estado === 'caducado'));

const eliminar = (canvi: CanviDorsal) => {
  if (!confirm(`Eliminar el canvi de dorsal de ${canvi.participant_original?.nom ?? canvi.nom_participant_original}?`)) return;
  router.delete(`/uec-admin/canvis-dorsal/${canvi.id}`);
};
</script>

<template>
  <AdminLayout>
    <Head title="Canvis de Dorsal" />

    <div class="px-4 py-8">
      <div class="mx-auto max-w-6xl">
        <!-- Header -->
        <div class="mb-6">
          <h1 class="text-3xl font-bold text-slate-900">Canvis de Dorsal</h1>
          <p class="mt-1 text-slate-600">Enllaços generats per canviar titularitat d'inscripció</p>
          <div class="mt-3 flex flex-wrap gap-2 text-sm">
            <span class="rounded-full bg-amber-100 px-3 py-1 font-medium text-amber-800">
              {{ pendents.length }} pendents
            </span>
            <span class="rounded-full bg-green-100 px-3 py-1 font-medium text-green-800">
              {{ completats.length }} completats
            </span>
            <span class="rounded-full bg-slate-100 px-3 py-1 font-medium text-slate-600">
              {{ caducats.length }} caducats
            </span>
          </div>
        </div>

        <!-- Sense registres -->
        <div v-if="canvis.length === 0" class="py-16 text-center text-slate-500">
          No s'ha generat cap canvi de dorsal per a l'edició activa.
        </div>

        <!-- Taula -->
        <div v-else class="overflow-x-auto rounded-lg bg-white shadow">
          <table class="w-full min-w-[580px] text-sm">
            <thead class="bg-slate-50 text-xs tracking-wider text-slate-500 uppercase">
              <tr>
                <th class="px-4 py-3 text-left">Participant original</th>
                <th class="px-4 py-3 text-left">Nou participant</th>
                <th class="px-4 py-3 text-center">Estat</th>
                <th class="px-4 py-3 text-center">Preu</th>
                <th class="px-4 py-3 text-left">Creat</th>
                <th class="px-4 py-3 text-center">Accions</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
              <tr
                v-for="canvi in canvis"
                :key="canvi.id"
                :class="[
                  canvi.estado === 'pendiente' ? 'bg-amber-50' : '',
                  canvi.estado === 'completado' ? 'bg-green-50/40' : '',
                ]"
              >
                <!-- Participant original -->
                <td class="px-4 py-3">
                  <p class="font-medium text-slate-900">
                    {{ canvi.participant_original?.nom ?? canvi.nom_participant_original ?? '—' }}
                  </p>
                  <p class="text-xs text-slate-500">
                    {{ canvi.participant_original?.dni ?? '' }}
                  </p>
                  <Link
                    v-if="canvi.inscripcion_id"
                    :href="`/uec-admin/inscripciones/${canvi.inscripcion_id}`"
                    class="mt-0.5 inline-block text-xs text-blue-600 hover:underline"
                  >
                    #{{ canvi.inscripcion_id }}
                  </Link>
                  <!-- Countdown sota el nom, només pendents -->
                  <p v-if="canvi.estado === 'pendiente'" class="mt-1 font-mono text-xs" :key="ticks">
                    <span :class="segsActuals(canvi) < 3600 ? 'font-semibold text-red-600' : 'text-amber-700'">
                      <Clock class="mr-0.5 inline h-3 w-3" />{{ formatCountdown(segsActuals(canvi)) }}
                    </span>
                  </p>
                </td>

                <!-- Nou participant -->
                <td class="px-4 py-3">
                  <template v-if="canvi.nou_participant">
                    <p class="font-medium text-slate-900">{{ canvi.nou_participant.nom }}</p>
                    <p class="text-xs text-slate-500">{{ canvi.nou_participant.dni }}</p>
                  </template>
                  <span v-else class="text-slate-400">—</span>
                </td>

                <!-- Estat -->
                <td class="px-4 py-3 text-center">
                  <Badge
                    v-if="canvi.estado === 'pendiente'"
                    class="bg-amber-100 text-amber-800 hover:bg-amber-100"
                  >
                    <Clock class="mr-1 h-3 w-3" />
                    Pendent
                  </Badge>
                  <Badge
                    v-else-if="canvi.estado === 'completado'"
                    class="bg-green-100 text-green-800 hover:bg-green-100"
                  >
                    <CheckCircle class="mr-1 h-3 w-3" />
                    Completat
                  </Badge>
                  <Badge v-else class="bg-slate-100 text-slate-600 hover:bg-slate-100">
                    <XCircle class="mr-1 h-3 w-3" />
                    Caducat
                  </Badge>
                </td>

                <!-- Preu -->
                <td class="px-4 py-3 text-center font-medium text-slate-700">
                  {{ canvi.precio.toFixed(2) }}€
                </td>

                <!-- Creat -->
                <td class="px-4 py-3 text-xs text-slate-500">
                  {{ formatDate(canvi.created_at) }}
                </td>

                <!-- Accions -->
                <td class="px-4 py-3">
                  <div class="flex items-center justify-center gap-2">
                    <!-- Copiar URL (només pendents) -->
                    <button
                      v-if="canvi.estado === 'pendiente'"
                      :title="copiat === canvi.id ? 'Copiat!' : 'Copiar enllaç'"
                      class="rounded p-1 text-slate-500 transition hover:bg-slate-100 hover:text-slate-800"
                      @click="copiarUrl(canvi)"
                    >
                      <Copy class="h-4 w-4" />
                    </button>
                    <!-- Obrir URL en nova pestanya (pendent) -->
                    <a
                      v-if="canvi.estado === 'pendiente'"
                      :href="canvi.url"
                      target="_blank"
                      title="Obrir enllaç"
                      class="rounded p-1 text-slate-500 transition hover:bg-slate-100 hover:text-slate-800"
                    >
                      <ExternalLink class="h-4 w-4" />
                    </a>
                    <!-- Eliminar (pendents i caducats) -->
                    <button
                      v-if="canvi.estado !== 'completado'"
                      title="Eliminar"
                      class="rounded p-1 text-slate-400 transition hover:bg-red-50 hover:text-red-600"
                      @click="eliminar(canvi)"
                    >
                      <Trash2 class="h-4 w-4" />
                    </button>
                  </div>
                  <p
                    v-if="copiat === canvi.id"
                    class="mt-1 text-center text-xs font-medium text-green-600"
                  >
                    Copiat!
                  </p>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </AdminLayout>
</template>
