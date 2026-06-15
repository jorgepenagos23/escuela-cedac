<template>
  <div class="bg-seccion">
    <div class="seccion-titulo">
      <v-icon size="14" color="indigo">{{ icono }}</v-icon>
      {{ titulo }}
    </div>
    <slot />
  </div>
</template>

<script setup>
defineProps({
    titulo: String,
    icono: String
});
</script>
