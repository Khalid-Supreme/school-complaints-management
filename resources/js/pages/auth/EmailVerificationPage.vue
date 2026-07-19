<template>
  <div class="space-y-6 text-center">
    <div>
      <div class="inline-flex w-14 h-14 rounded-[12px] bg-sage-100 border border-sage-200/50 items-center justify-center text-sage-600 mb-5"
        style="box-shadow: 0 4px 20px rgba(106, 156, 94, 0.08);">
        <i class="pi pi-envelope text-xl"></i>
      </div>
      <h1 class="text-[1.6rem] font-semibold text-charcoal tracking-tight">Verify your email</h1>

      <Message v-if="route.query.registered" severity="success" :closable="false"
        class="!bg-green-50/50 !border-green-100/50 !text-green-700 !rounded-xl !text-sm !mt-0">
        Account created successfully! Your {{ idLabel }} is
        <strong class="text-green-800">{{ route.query.institution_id }}</strong>
        <Button
          icon="pi pi-copy"
          class="!p-0 !ml-1.5 !bg-transparent !border-none !text-green-600 hover:!text-green-800 !transition-colors !inline-flex !align-baseline"
          :class="copied ? '!text-green-800' : ''"
          style="height: auto; width: auto; min-width: 0;"
          @click="copyInstitutionId"
          v-tooltip="copied ? 'Copied!' : 'Copy to clipboard'"
        />
      </Message>

      <p class="mt-3 text-sm text-slate-400 font-medium leading-relaxed">
        We sent a verification link to <strong class="text-charcoal">{{ targetEmail || 'your email address' }}</strong>.
      </p>
      <p class="mt-2 text-sm text-slate-400 font-medium leading-relaxed">
        Check your inbox and click the link to activate your account.
      </p>
    </div>

    <Message v-if="statusMessage" severity="success" :closable="false"
      class="!bg-green-50/50 !border-green-100/50 !text-green-700 !rounded-xl !text-sm">
      {{ statusMessage }}
    </Message>
    <Message v-if="errorMessage" severity="error" :closable="false"
      class="!bg-red-50/50 !border-red-100/50 !text-red-600 !rounded-xl !text-sm">
      {{ errorMessage }}
    </Message>

    <div class="text-left space-y-2">
      <label class="block text-sm font-medium text-charcoal/80">Email Address</label>
      <InputText v-model="targetEmail" type="email" placeholder="Enter your email address"
        class="w-full !bg-white !border-sage-200/60 !text-charcoal !text-[0.92rem] !rounded-lg !py-3 !px-4 transition-all duration-200 focus:!border-sage-400 focus:!ring-2 focus:!ring-sage-100 hover:!border-sage-300" />
    </div>

    <div class="flex flex-col gap-3">
      <Button label="Resend verification email" :loading="loading"
        class="!py-3 !bg-sage-600 hover:!bg-sage-700 !border-none !rounded-lg !text-white !font-semibold !text-[0.9rem] !transition-all !duration-200 hover:-translate-y-[1px]"
        :style="{ boxShadow: '0 2px 12px rgba(106, 156, 94, 0.2)' }" />
      <Button label="Back to login" severity="secondary" outlined @click="router.push('/login')" />
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import InputText from 'primevue/inputtext';
import Button from 'primevue/button';
import Message from 'primevue/message';
import { useToast } from 'primevue/usetoast';
import authService from '../../services/authService';

const route = useRoute();
const router = useRouter();
const toast = useToast();
const loading = ref(false);
const statusMessage = ref('');
const errorMessage = ref('');
const copied = ref(false);

const targetEmail = ref(route.query.email || '');

const idLabel = route.query.institution_id?.startsWith('STF') ? 'Staff ID' : 'Student ID';

const copyInstitutionId = async () => {
  try {
    await navigator.clipboard.writeText(route.query.institution_id);
    copied.value = true;
    toast.add({ severity: 'success', summary: 'Copied', detail: 'Institution ID copied to clipboard', life: 2000 });
    setTimeout(() => { copied.value = false; }, 2000);
  } catch {
    toast.add({ severity: 'error', summary: 'Failed', detail: 'Unable to copy', life: 3000 });
  }
};

const resendEmail = async () => {
  loading.value = true;
  errorMessage.value = '';
  statusMessage.value = '';

  try {
    const response = await authService.resendVerification(targetEmail.value);
    statusMessage.value = response.data.message;
  } catch (error) {
    errorMessage.value = error.response?.data?.message || 'Unable to resend verification email.';
  } finally {
    loading.value = false;
  }
};
</script>
