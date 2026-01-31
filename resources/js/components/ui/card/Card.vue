<script setup>
import { cn } from '@/lib/utils';

const props = defineProps({
  class: { type: null, required: false },
});
</script>

<template>
  <div
    :class="
      cn(
        'bg-card/50 text-card-foreground borde rounded-2xl border-none border-yellow-100/50 backdrop-blur',
        props.class
      )
    "
  >
    <slot />
  </div>
  <!-- <div
    :class="
      cn('bg-card text-card-foreground borde rounded-lg border-gray-100 shadow-md', props.class)
    "
  >
    <slot />
  </div> -->
</template>
