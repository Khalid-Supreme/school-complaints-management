<template>
    <ul class="mt-2 space-y-1">
        <li
            v-for="requirement in PASSWORD_REQUIREMENTS"
            :key="requirement.key"
            class="flex items-center gap-1.5 text-xs"
            :class="checks[requirement.key] ? 'text-green-600' : 'text-slate-400'"
        >
            <i :class="['pi text-[0.6rem]', checks[requirement.key] ? 'pi-check text-green-500' : 'pi-circle text-slate-300']"></i>
            <span>{{ requirement.label }}</span>
        </li>
    </ul>
</template>

<script setup>
import { computed } from 'vue';
import { PASSWORD_REQUIREMENTS, validatePassword } from '../utils/passwordPolicy';

const props = defineProps({
    password: {
        type: String,
        default: '',
    },
});

const checks = computed(() => validatePassword(props.password));
</script>
