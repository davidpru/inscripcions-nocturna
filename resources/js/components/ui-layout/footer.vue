<script setup lang="ts">
// Footer component con logos de sponsors
</script>

<template>
  <footer class="mx-auto max-w-6xl px-4 py-10">
    <section
      class="mb-6 flex flex-wrap items-center justify-center gap-4 border-t pt-6 mix-blend-multiply grayscale"
    >
      <img src="@/assets/logos/logo-suport-uec.png" alt="Logo UEC" class="h-7.5" />
      <img src="@/assets/logos/logo-suport-caro.png" alt="Logo Caro" class="h-6.5" />
      <img src="@/assets/logos/logo-suport-gencat.png" alt="Logo Gencat" class="h-6" />
      <img src="@/assets/logos/logo-suport-pnports.png" alt="Logo PNPorts" class="h-6" />
      <img src="@/assets/logos/logo-suport-tinenca.jpg" alt="Logo Tinença" class="h-8" />
    </section>
    <section class="border-t pt-6 text-center text-sm text-slate-900">
      <p class="text-balance">
        <strong>Unió Excursionista de Catalunya de Tortosa</strong> -
        <a href="https://nocturna.uectortosa.cat">Nocturna Fredes Paüls &copy;</a>
        {{ new Date().getFullYear() }}
      </p>
    </section>
  </footer>
</template>
