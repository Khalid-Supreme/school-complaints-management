<template>
  <div class="auth-layout">
    <slot />
  </div>
</template>

<script setup>
</script>

<style scoped>
.auth-layout {
  display: flex;
  justify-content: center;
  align-items: center;
  min-height: 100vh;
  background-color: #f3f4f6;
}
</style>
