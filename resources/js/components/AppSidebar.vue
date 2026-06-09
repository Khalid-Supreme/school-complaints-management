<template>
  <aside class="sidebar">
    <div class="logo">Complaint System</div>
    <nav>
      <ul>
        <li><router-link to="/">Home</router-link></li>
        <li><router-link to="/admin">Admin</router-link></li>
        <li><router-link to="/staff">Staff</router-link></li>
        <li><router-link to="/complainant">Complainant</router-link></li>
        <li><router-link to="/security">Security</router-link></li>
      </ul>
    </nav>
  </aside>
</template>

<style scoped>
.sidebar {
  width: 250px;
  background-color: #1f2937;
  color: white;
  padding: 1rem;
}
.logo {
  font-size: 1.25rem;
  font-weight: bold;
  margin-bottom: 2rem;
}
nav ul {
  list-style: none;
  padding: 0;
}
nav a {
  color: #d1d5db;
  text-decoration: none;
  display: block;
  padding: 0.5rem 0;
}
nav a:hover {
  color: white;
}
</style>
