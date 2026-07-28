<template>
    <div class="space-y-8">
        <div class="flex items-center gap-2 text-sm text-slate-400 mb-2">
            <span class="font-medium">Admin</span>
            <i class="pi pi-chevron-right text-xs"></i>
            <span class="text-charcoal font-semibold">User Management</span>
        </div>

        <div>
            <h1 class="text-2xl lg:text-3xl font-semibold text-charcoal tracking-tight">User Management</h1>
            <p class="text-slate-400 text-sm mt-1 font-medium">Manage student and staff accounts</p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div class="bg-white rounded-xl p-4 sm:p-6 shadow-card transition-all duration-200 hover:shadow-lg cursor-pointer group" style="border: 1px solid #E8EFE9;" @click="router.push('/admin/users/students')">
                <div class="flex items-start gap-5">
                    <div class="w-14 h-14 rounded-xl bg-sage-100 flex items-center justify-center text-sage-600 group-hover:bg-sage-200 transition-colors">
                        <i class="pi pi-users text-2xl"></i>
                    </div>
                    <div class="flex-1 min-w-0">
                        <h3 class="text-lg lg:text-xl font-semibold text-charcoal mb-1">Students</h3>
                        <p class="text-sm text-slate-500 leading-relaxed">View, create, edit, and remove student accounts. Student IDs are auto-generated.</p>
                    </div>
                    <i class="pi pi-arrow-right text-slate-300 group-hover:text-sage-500 transition-colors mt-1.5"></i>
                </div>
            </div>

            <div class="bg-white rounded-xl p-4 sm:p-6 shadow-card transition-all duration-200 hover:shadow-lg cursor-pointer group" style="border: 1px solid #E8EFE9;" @click="router.push('/admin/users/staff')">
                <div class="flex items-start gap-5">
                    <div class="w-14 h-14 rounded-xl bg-sage-100 flex items-center justify-center text-sage-600 group-hover:bg-sage-200 transition-colors">
                        <i class="pi pi-briefcase text-2xl"></i>
                    </div>
                    <div class="flex-1 min-w-0">
                        <h3 class="text-lg lg:text-xl font-semibold text-charcoal mb-1">Staff</h3>
                        <p class="text-sm text-slate-500 leading-relaxed">View, create, edit, and remove staff accounts. Assign or change staff roles.</p>
                    </div>
                    <i class="pi pi-arrow-right text-slate-300 group-hover:text-sage-500 transition-colors mt-1.5"></i>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { useRouter } from 'vue-router';
const router = useRouter();
</script>
