<script setup lang="ts">
import { cn } from '@/lib/utils';
import { fieldVariants } from '.';

const props = defineProps<{
  class?: any;
  orientation?: 'horizontal' | 'vertical' | 'responsive';
}>();
</script>

<template>
  <div
    role="group"
    data-slot="field"
    :data-orientation="orientation"
    :class="cn(fieldVariants({ orientation }), props.class)"
  >
    <slot />
  </div>
</template>
