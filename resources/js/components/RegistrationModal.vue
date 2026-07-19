<template>
  <Dialog v-model:visible="visibleProxy" :header="dialogHeader" :style="{ width: '28rem' }" @after-hide="resetForm" :closable="!loading" :draggable="false">
    <div class="space-y-5">
      <div class="flex bg-sage-100/70 rounded-lg p-1 max-w-xs mx-auto">
        <button type="button" class="flex-1 text-sm font-medium py-2 px-4 rounded-md transition-all duration-200" :class="form.account_type === 'student' ? 'bg-white text-charcoal shadow-sm' : 'text-slate-500 hover:text-charcoal'" :disabled="loading" @click="switchType('student')">Student</button>
        <button type="button" class="flex-1 text-sm font-medium py-2 px-4 rounded-md transition-all duration-200" :class="form.account_type === 'staff' ? 'bg-white text-charcoal shadow-sm' : 'text-slate-500 hover:text-charcoal'" :disabled="loading" @click="switchType('staff')">Staff</button>
      </div>

      <Message v-if="errorMessage" severity="error" :closable="false"
        class="!bg-red-50/50 !border-red-100/50 !text-red-600 !rounded-xl !text-sm !mt-0">
        {{ errorMessage }}
      </Message>

      <div>
        <label class="block text-sm font-medium text-charcoal/80 mb-2">Full Name</label>
        <InputText v-model="form.full_name" placeholder="e.g. John Doe" class="w-full !bg-white !border-sage-200/60 !text-charcoal !text-[0.92rem] !rounded-lg !py-3 !px-4 transition-all duration-200 focus:!border-sage-400 focus:!ring-2 focus:!ring-sage-100 hover:!border-sage-300" :class="{ 'p-invalid': errors.full_name || localErrors.full_name }" :disabled="loading" @update:model-value="clearError('full_name')" />
        <small v-if="errors.full_name || localErrors.full_name" class="text-red-500 block mt-1.5 text-xs font-medium">{{ (errors.full_name && errors.full_name[0]) || localErrors.full_name }}</small>
      </div>

      <div>
        <label class="block text-sm font-medium text-charcoal/80 mb-2">Email Address</label>
        <InputText v-model="form.email" type="email" placeholder="e.g. johndoe@example.com" class="w-full !bg-white !border-sage-200/60 !text-charcoal !text-[0.92rem] !rounded-lg !py-3 !px-4 transition-all duration-200 focus:!border-sage-400 focus:!ring-2 focus:!ring-sage-100 hover:!border-sage-300" :class="{ 'p-invalid': errors.email || localErrors.email }" :disabled="loading" @update:model-value="clearError('email')" />
        <small v-if="errors.email || localErrors.email" class="text-red-500 block mt-1.5 text-xs font-medium">{{ (errors.email && errors.email[0]) || localErrors.email }}</small>
      </div>

      <div>
        <label class="block text-sm font-medium text-charcoal/80 mb-2">Gender</label>
        <Select v-model="form.gender" :options="genderOptions" optionLabel="label" optionValue="value" placeholder="Select gender" class="w-full" :class="{ 'p-invalid': errors.gender || localErrors.gender }" :disabled="loading" @update:model-value="clearError('gender')" />
        <small v-if="errors.gender || localErrors.gender" class="text-red-500 block mt-1.5 text-xs font-medium">{{ (errors.gender && errors.gender[0]) || localErrors.gender }}</small>
      </div>

      <div v-if="form.account_type === 'staff'">
        <label class="block text-sm font-medium text-charcoal/80 mb-2">Title</label>
        <Select v-model="form.title" :options="titleOptions" optionLabel="label" optionValue="value" placeholder="Select title" class="w-full" :class="{ 'p-invalid': errors.title || localErrors.title }" :disabled="loading" @update:model-value="clearError('title')" />
        <small v-if="errors.title || localErrors.title" class="text-red-500 block mt-1.5 text-xs font-medium">{{ (errors.title && errors.title[0]) || localErrors.title }}</small>
      </div>

      <div>
        <label class="block text-sm font-medium text-charcoal/80 mb-2">Department</label>
        <Select v-model="form.department" :options="filteredDepartments" optionLabel="name" optionValue="id" :placeholder="deptPlaceholder" class="w-full" :class="{ 'p-invalid': errors.department || localErrors.department }" :loading="loadingDepartments" :disabled="loading" filter @update:model-value="clearError('department')" />
        <small v-if="errors.department || localErrors.department" class="text-red-500 block mt-1.5 text-xs font-medium">{{ (errors.department && errors.department[0]) || localErrors.department }}</small>
      </div>

      <div>
        <label class="block text-sm font-medium text-charcoal/80 mb-2">Password</label>
        <InputText v-model="form.password" type="password" placeholder="Min. 8 characters" class="w-full !bg-white !border-sage-200/60 !text-charcoal !text-[0.92rem] !rounded-lg !py-3 !px-4 transition-all duration-200 focus:!border-sage-400 focus:!ring-2 focus:!ring-sage-100 hover:!border-sage-300" :class="{ 'p-invalid': errors.password || localErrors.password }" :disabled="loading" @update:model-value="clearError('password')" />
        <div v-if="form.password.length > 0" class="mt-2">
          <div class="h-1.5 rounded-full bg-sage-100 overflow-hidden">
            <div class="h-full rounded-full transition-all duration-300" :class="strengthBarClass" :style="{ width: strengthPercent + '%' }"></div>
          </div>
          <p class="text-xs font-medium mt-1" :class="strengthTextClass">{{ strengthLabel }}</p>
        </div>
        <small v-if="errors.password || localErrors.password" class="text-red-500 block mt-1.5 text-xs font-medium">{{ (errors.password && errors.password[0]) || localErrors.password }}</small>
      </div>

      <div>
        <label class="block text-sm font-medium text-charcoal/80 mb-2">Confirm Password</label>
        <InputText v-model="form.confirm_password" type="password" placeholder="Re-enter your password" class="w-full !bg-white !border-sage-200/60 !text-charcoal !text-[0.92rem] !rounded-lg !py-3 !px-4 transition-all duration-200 focus:!border-sage-400 focus:!ring-2 focus:!ring-sage-100 hover:!border-sage-300" :class="{ 'p-invalid': errors.confirm_password || localErrors.confirm_password }" :disabled="loading" @update:model-value="clearError('confirm_password')" />
        <small v-if="errors.confirm_password || localErrors.confirm_password" class="text-red-500 block mt-1.5 text-xs font-medium">{{ (errors.confirm_password && errors.confirm_password[0]) || localErrors.confirm_password }}</small>
      </div>

      <div class="flex justify-end gap-2 pt-2">
        <Button label="Cancel" severity="secondary" text :disabled="loading" @click="visibleProxy = false" />
        <Button label="Register" :loading="loading" @click="submitRegistration" class="!py-2.5 !px-5 !bg-sage-600 hover:!bg-sage-700 !border-none !rounded-lg !text-white !font-semibold !text-[0.9rem] !transition-all !duration-200 hover:-translate-y-[0.5px]" :style="{ boxShadow: '0 2px 12px rgba(106, 156, 94, 0.2)' }" />
      </div>
    </div>
  </Dialog>
</template>

<script setup>
import { computed, onMounted, reactive, ref, watch } from 'vue';
import Dialog from 'primevue/dialog';
import InputText from 'primevue/inputtext';
import Select from 'primevue/select';
import Button from 'primevue/button';
import Message from 'primevue/message';
import api from '../services/api';
import authService from '../services/authService';

const props = defineProps({
  visible: {
    type: Boolean,
    default: false,
  },
});

const emit = defineEmits(['update:visible', 'success']);

const visibleProxy = computed({
  get: () => props.visible,
  set: (value) => emit('update:visible', value),
});

const loading = ref(false);
const loadingDepartments = ref(false);
const allDepartments = ref([]);
const errors = ref({});
const localErrors = ref({});
const errorMessage = ref('');

const genderOptions = [
  { label: 'Male', value: 'male' },
  { label: 'Female', value: 'female' },
];

const titleOptions = [
  { label: 'Mr.', value: 'Mr.' },
  { label: 'Mrs.', value: 'Mrs.' },
  { label: 'Miss', value: 'Miss' },
  { label: 'Dr.', value: 'Dr.' },
  { label: 'Prof.', value: 'Prof.' },
];

const dialogHeader = computed(() => {
  return form.account_type === 'student' ? 'Create Student Account' : 'Create Staff Account';
});

const filteredDepartments = computed(() => {
  if (form.account_type === 'student') {
    return allDepartments.value.filter(d => d.type === 'academic');
  }
  return allDepartments.value;
});

const deptPlaceholder = computed(() => {
  return form.account_type === 'student' ? 'Select academic department' : 'Select department or unit';
});

const strengthPercent = computed(() => {
  const p = form.password;
  if (!p) return 0;
  let score = 0;
  if (p.length >= 8) score += 25;
  if (/[a-z]/.test(p)) score += 15;
  if (/[A-Z]/.test(p)) score += 20;
  if (/\d/.test(p)) score += 20;
  if (/[^a-zA-Z0-9]/.test(p)) score += 20;
  return Math.min(score, 100);
});

const strengthLabel = computed(() => {
  const s = strengthPercent.value;
  if (s < 25) return 'Weak';
  if (s < 50) return 'Fair';
  if (s < 75) return 'Good';
  return 'Strong';
});

const strengthBarClass = computed(() => {
  const s = strengthPercent.value;
  if (s < 25) return 'bg-red-400';
  if (s < 50) return 'bg-orange-400';
  if (s < 75) return 'bg-yellow-500';
  return 'bg-green-500';
});

const strengthTextClass = computed(() => {
  const s = strengthPercent.value;
  if (s < 25) return 'text-red-500';
  if (s < 50) return 'text-orange-500';
  if (s < 75) return 'text-yellow-600';
  return 'text-green-600';
});

const initialForm = () => ({
  account_type: 'student',
  full_name: '',
  email: '',
  gender: null,
  title: null,
  department: null,
  password: '',
  confirm_password: '',
});

const form = reactive(initialForm());

const resetForm = () => {
  Object.assign(form, initialForm());
  errors.value = {};
  localErrors.value = {};
  errorMessage.value = '';
};

const switchType = (type) => {
  form.account_type = type;
  form.department = null;
  if (type === 'student') {
    form.title = null;
  }
  clearError('department');
  clearError('title');
};

const fetchDepartments = async () => {
  loadingDepartments.value = true;
  try {
    const response = await api.get('/api/departments');
    allDepartments.value = response.data.data;
  } catch {
    // silently fail
  } finally {
    loadingDepartments.value = false;
  }
};

const clearError = (field) => {
  if (errors.value[field]) {
    const newErrors = { ...errors.value };
    delete newErrors[field];
    errors.value = newErrors;
  }
  if (localErrors.value[field]) {
    const newLocal = { ...localErrors.value };
    delete newLocal[field];
    localErrors.value = newLocal;
  }
  errorMessage.value = '';
};

const validateLocal = () => {
  const errs = {};
  if (!form.full_name.trim()) errs.full_name = 'Full name is required';
  if (!form.email.trim()) errs.email = 'Email address is required';
  if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(form.email.trim())) errs.email = errs.email || 'Enter a valid email address';
  if (!form.gender) errs.gender = 'Please select your gender';
  if (form.account_type === 'staff' && !form.title) errs.title = 'Please select your title';
  if (!form.department) errs.department = 'Please select a department';
  if (!form.password) errs.password = 'Password is required';
  else if (form.password.length < 8) errs.password = 'Password must be at least 8 characters';
  else if (form.password.length < 8) errs.password = 'Password must be at least 8 characters';
  if (!form.confirm_password) errs.confirm_password = 'Please confirm your password';
  else if (form.password !== form.confirm_password) errs.confirm_password = 'Passwords do not match';
  localErrors.value = errs;
  return Object.keys(errs).length === 0;
};

const submitRegistration = async () => {
  errorMessage.value = '';
  errors.value = {};
  if (!validateLocal()) return;

  loading.value = true;

  try {
    const payload = {
      full_name: form.full_name.trim(),
      email: form.email.trim(),
      gender: form.gender,
      department: form.department,
      password: form.password,
      confirm_password: form.confirm_password,
    };

    if (form.account_type === 'staff') {
      payload.title = form.title;
    }

    const response = form.account_type === 'student'
      ? await authService.registerStudent(payload)
      : await authService.registerStaff(payload);

    emit('success', response.data);
    visibleProxy.value = false;
  } catch (error) {
    const data = error.response?.data;
    if (data?.errors) {
      errors.value = data.errors;
    }
    errorMessage.value = data?.message || 'Registration failed';
  } finally {
    loading.value = false;
  }
};

watch(() => props.visible, (val) => {
  if (val) {
    fetchDepartments();
  }
});

onMounted(() => {
  if (props.visible) {
    fetchDepartments();
  }
});
</script>
