<template>
  <component :is="layout">
    <router-view />
  </component>
</template>

<script setup>
import { computed } from 'vue';
import { useRoute } from 'vue-router';
import { useIdleTimeout } from './composables/useIdleTimeout';

useIdleTimeout();

const route = useRoute();
const layout = computed(() => route.meta.layout || 'div');
</script>
