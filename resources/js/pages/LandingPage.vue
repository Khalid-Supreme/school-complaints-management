<template>
  <section class="landing">
    <h1>Welcome to the Secure Complaint Management System</h1>
    <p>A Laravel 13 and Vue 3 foundation for encrypted complaint intake, role-based access, and application-layer security controls.</p>
    <router-link class="primary-link" to="/login">Continue to login</router-link>
  </section>
</template>

<style scoped>
.landing {
  max-width: 760px;
  margin: 0 auto;
  padding: 5rem 1rem;
}

.landing h1 {
  color: #111827;
  font-size: 2.5rem;
  line-height: 1.1;
  margin-bottom: 1rem;
}

.landing p {
  color: #4b5563;
  font-size: 1.1rem;
  line-height: 1.7;
  margin-bottom: 1.5rem;
}

.primary-link {
  color: #ffffff;
  background: #2563eb;
  border-radius: 0.5rem;
  display: inline-flex;
  font-weight: 700;
  padding: 0.85rem 1.1rem;
  text-decoration: none;
}
</style>
