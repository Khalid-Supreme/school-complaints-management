<template>
    <div class="min-h-screen flex items-center justify-center bg-[#F8FAF6] px-6 py-12">
        <Toast />
        <div class="w-full max-w-[400px] space-y-10">
            <div class="text-center">
                <h2 class="text-[1.35rem] sm:text-[1.65rem] font-semibold text-[#1A252C] tracking-tight">
                    Complaint Management System
                </h2>
                <p class="mt-2 text-sm text-slate-400 font-medium">
                    Secure Authentication Portal
                </p>
            </div>
            
<div class="bg-white rounded-[14px] p-8 sm:p-10"
                style="box-shadow: 0 4px 30px rgba(0, 0, 0, 0.03); border: 1px solid #E8EFE9;">
                <router-view></router-view>
            </div>
        </div>
    </div>
</template>

<script setup>
import Toast from 'primevue/toast';
</script>
