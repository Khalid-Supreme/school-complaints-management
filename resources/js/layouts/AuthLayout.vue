<template>
    <div class="min-h-screen flex items-center justify-center bg-surface-50 dark:bg-surface-900 px-4 sm:px-6 lg:px-8">
        <div class="max-w-md w-full space-y-8">
            <div>
                <h2 class="mt-6 text-center text-3xl font-extrabold text-surface-900 dark:text-surface-0">
                    Complaint Management System
                </h2>
                <p class="mt-2 text-center text-sm text-surface-600 dark:text-surface-400">
                    Secure Authentication Portal
                </p>
            </div>
            
            <div class="bg-surface-0 dark:bg-surface-800 shadow sm:rounded-lg p-8">
                <router-view></router-view>
            </div>
        </div>
    </div>
</template>

<script setup>
// Auth Layout component
</script>
