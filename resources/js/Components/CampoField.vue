<template>
  <div>
    <span class="campo-label">{{ label }}</span>
    <slot />
  </div>
</template>

<script setup>
defineProps({
    label: String
});
</script>
