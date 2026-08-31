<template>
    <div class="min-h-screen flex items-center justify-center bg-[#F8FAF6] px-6 py-12">
        <Toast />
        <div class="w-full space-y-10 transition-all duration-300" :class="isWide ? 'max-w-[720px]' : 'max-w-[400px]'">
            <div class="text-center mt-0">
                <h2 class="text-[2.0rem] sm:text-[2.25rem] tracking-tight text-sage-700 leading-none">
                    <span class="font-medium lowercase">{{ brandName.primary }}</span>
                    <span v-if="brandName.secondary" class="font-extrabold lowercase">{{ brandName.secondary }}</span>
                </h2>
                <p class="mt-2 text-sm text-slate-400 font-medium">
                    Secure Authentication Portal
                </p>
            </div>
            
<div class="bg-white rounded-[14px] p-6 sm:p-8 lg:p-10"
                style="box-shadow: 0 4px 30px rgba(0, 0, 0, 0.03); border: 1px solid #E8EFE9;">
                <router-view></router-view>
            </div>
        </div>
    </div>
</template>

<script setup>
import Toast from 'primevue/toast';
import { computed } from 'vue';
import { useRoute } from 'vue-router';
import settingsService from '../services/settings';

const route = useRoute();
const brandName = computed(() => settingsService.getBrandNameParts());
const isWide = computed(() => route.name === 'Register');
</script>
