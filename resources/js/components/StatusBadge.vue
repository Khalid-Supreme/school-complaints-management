<template>
    <Tag :value="formattedStatus" :severity="severity" rounded />
</template>

<script setup>
import { computed } from 'vue';
import Tag from 'primevue/tag';

const props = defineProps({
    status: {
        type: String,
        required: true
    }
});

const formattedStatus = computed(() => {
    return props.status.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase());
});

const severity = computed(() => {
    switch (props.status) {
        case 'draft': return 'secondary';
        case 'submitted': return 'info';
        case 'under_review': return 'warning';
        case 'assigned': return 'primary';
        case 'in_progress': return 'primary';
        case 'resolved': return 'success';
        case 'closed': return 'success';
        case 'rejected': return 'danger';
        case 'reopened': return 'warning';
        default: return 'info';
    }
});
</script>
