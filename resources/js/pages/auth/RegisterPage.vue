<template>
    <div class="space-y-8">
        <div class="text-center">
            <h3 class="text-[1.1rem] sm:text-[1.25rem] font-semibold text-charcoal tracking-tight">{{ isStudent ? 'Create Student Account' : 'Create Staff Account' }}</h3>
            <p class="text-sm text-slate-400 mt-1">Join the secure complaint portal</p>
        </div>

        <div class="flex bg-sage-100/70 rounded-lg p-1 max-w-xs mx-auto">
            <button type="button" class="flex-1 text-sm font-medium py-2 px-4 rounded-md transition-all duration-200" :class="form.account_type === 'student' ? 'bg-white text-charcoal shadow-sm' : 'text-slate-500 hover:text-charcoal'" :disabled="loading" @click="switchType('student')">Student</button>
            <button type="button" class="flex-1 text-sm font-medium py-2 px-4 rounded-md transition-all duration-200" :class="form.account_type === 'staff' ? 'bg-white text-charcoal shadow-sm' : 'text-slate-500 hover:text-charcoal'" :disabled="loading" @click="switchType('staff')">Staff</button>
        </div>

        <Message v-if="errorMessage" severity="error" :closable="false" class="!bg-red-50/50 !border-red-100/50 !text-red-600 !rounded-xl !text-sm">
            {{ errorMessage }}
        </Message>

        <form @submit.prevent="submitRegistration" class="space-y-4 md:space-y-5">
            <!-- Row 1: First Name | Last Name -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-charcoal/80 mb-2">First Name</label>
                    <InputText v-model="form.first_name" class="w-full !bg-white !border-sage-200/60 !text-charcoal !text-[0.85rem] sm:!text-[0.92rem] !rounded-lg !py-3 !px-4 transition-all duration-200 focus:!border-sage-400 focus:!ring-2 focus:!ring-sage-100 hover:!border-sage-300" :class="{ 'p-invalid': errors.first_name || localErrors.first_name }" :disabled="loading" @update:model-value="clearError('first_name')" />
                    <small v-if="errors.first_name || localErrors.first_name" class="text-red-500 block mt-1.5 text-xs font-medium">{{ (errors.first_name && errors.first_name[0]) || localErrors.first_name }}</small>
                </div>
                <div>
                    <label class="block text-sm font-medium text-charcoal/80 mb-2">Last Name</label>
                    <InputText v-model="form.last_name" class="w-full !bg-white !border-sage-200/60 !text-charcoal !text-[0.85rem] sm:!text-[0.92rem] !rounded-lg !py-3 !px-4 transition-all duration-200 focus:!border-sage-400 focus:!ring-2 focus:!ring-sage-100 hover:!border-sage-300" :class="{ 'p-invalid': errors.last_name || localErrors.last_name }" :disabled="loading" @update:model-value="clearError('last_name')" />
                    <small v-if="errors.last_name || localErrors.last_name" class="text-red-500 block mt-1.5 text-xs font-medium">{{ (errors.last_name && errors.last_name[0]) || localErrors.last_name }}</small>
                </div>
            </div>

            <!-- Row 2: Email Address | Gender -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-charcoal/80 mb-2">Email Address</label>
                    <InputText v-model="form.email" type="email" class="w-full !bg-white !border-sage-200/60 !text-charcoal !text-[0.85rem] sm:!text-[0.92rem] !rounded-lg !py-3 !px-4 transition-all duration-200 focus:!border-sage-400 focus:!ring-2 focus:!ring-sage-100 hover:!border-sage-300" :class="{ 'p-invalid': errors.email || localErrors.email }" :disabled="loading" @update:model-value="clearError('email')" />
                    <small v-if="errors.email || localErrors.email" class="text-red-500 block mt-1.5 text-xs font-medium">{{ (errors.email && errors.email[0]) || localErrors.email }}</small>
                </div>
                <div>
                    <label class="block text-sm font-medium text-charcoal/80 mb-2">Gender</label>
                    <Select v-model="form.gender" :options="genderOptions" optionLabel="label" optionValue="value" placeholder="Select gender" class="w-full" :class="{ 'p-invalid': errors.gender || localErrors.gender }" :disabled="loading" @update:model-value="clearError('gender')" />
                    <small v-if="errors.gender || localErrors.gender" class="text-red-500 block mt-1.5 text-xs font-medium">{{ (errors.gender && errors.gender[0]) || localErrors.gender }}</small>
                </div>
            </div>

            <!-- Row 2b: Title (staff only, full width) - appears between Gender and Department -->
            <div v-if="form.account_type === 'staff'">
                <label class="block text-sm font-medium text-charcoal/80 mb-2">Title</label>
                <Select v-model="form.title" :options="titleOptions" optionLabel="label" optionValue="value" placeholder="Select title" class="w-full" :class="{ 'p-invalid': errors.title || localErrors.title }" :disabled="loading" @update:model-value="clearError('title')" />
                <small v-if="errors.title || localErrors.title" class="text-red-500 block mt-1.5 text-xs font-medium">{{ (errors.title && errors.title[0]) || localErrors.title }}</small>
            </div>

            <!-- Row 3: Department (full width) -->
            <div>
                <label class="block text-sm font-medium text-charcoal/80 mb-2">Department</label>
                <Select v-model="form.department" :options="filteredDepartments" optionLabel="name" optionValue="id" :placeholder="deptPlaceholder" class="w-full" :class="{ 'p-invalid': errors.department || localErrors.department }" :loading="loadingDepartments" :disabled="loading" filter @update:model-value="clearError('department')" />
                <small v-if="errors.department || localErrors.department" class="text-red-500 block mt-1.5 text-xs font-medium">{{ (errors.department && errors.department[0]) || localErrors.department }}</small>
            </div>

            <!-- Row 4: Password | Confirm Password (2 cols on desktop) -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-charcoal/80 mb-2">Password</label>
                    <InputText v-model="form.password" type="password" placeholder="At least 8 characters" class="w-full !bg-white !border-sage-200/60 !text-charcoal !text-[0.85rem] sm:!text-[0.92rem] !rounded-lg !py-3 !px-4 transition-all duration-200 focus:!border-sage-400 focus:!ring-2 focus:!ring-sage-100 hover:!border-sage-300" :class="{ 'p-invalid': errors.password || localErrors.password }" :disabled="loading" @update:model-value="clearError('password')" />
                    <PasswordRequirements v-if="form.password.length > 0" :password="form.password" />
                    <small v-if="errors.password || localErrors.password" class="text-red-500 block mt-1.5 text-xs font-medium">{{ (errors.password && errors.password[0]) || localErrors.password }}</small>
                </div>
                <div>
                    <label class="block text-sm font-medium text-charcoal/80 mb-2">Confirm Password</label>
                    <InputText v-model="form.confirm_password" type="password" placeholder="Re-enter your password" class="w-full !bg-white !border-sage-200/60 !text-charcoal !text-[0.85rem] sm:!text-[0.92rem] !rounded-lg !py-3 !px-4 transition-all duration-200 focus:!border-sage-400 focus:!ring-2 focus:!ring-sage-100 hover:!border-sage-300" :class="{ 'p-invalid': errors.confirm_password || localErrors.confirm_password || confirmationMismatch }" :disabled="loading" @update:model-value="clearError('confirm_password')" />
                    <small v-if="confirmationMismatch" class="text-red-500 block mt-1.5 text-xs font-medium">Passwords do not match</small>
                    <small v-else-if="errors.confirm_password || localErrors.confirm_password" class="text-red-500 block mt-1.5 text-xs font-medium">{{ (errors.confirm_password && errors.confirm_password[0]) || localErrors.confirm_password }}</small>
                </div>
            </div>

            <!-- Row 5: Sign Up button -->
            <div class="flex justify-center md:justify-end pt-2">
                <Button type="submit" label="Create Account" :loading="loading" :disabled="loading || !canSubmit" class="w-full md:w-auto md:min-w-[200px] !py-3 !bg-sage-600 hover:!bg-sage-700 !border-none !rounded-lg !text-white !font-semibold !text-[0.9rem] !transition-all !duration-200" :style="{ boxShadow: '0 2px 12px rgba(106, 156, 94, 0.2)' }" />
            </div>
        </form>

        <div class="flex justify-center pt-1">
            <p class="text-sm text-slate-400 font-medium">
                Already have an account?
                <router-link to="/login" class="text-sage-600 hover:text-sage-700 font-semibold underline-offset-2 hover:underline">Login</router-link>
            </p>
        </div>
    </div>
</template>

<script setup>
import { computed, onMounted, reactive, ref } from 'vue';
import { useRouter } from 'vue-router';
import InputText from 'primevue/inputtext';
import Select from 'primevue/select';
import Button from 'primevue/button';
import Message from 'primevue/message';
import api from '../../services/api';
import authService from '../../services/authService';
import PasswordRequirements from '../../components/PasswordRequirements.vue';
import { validatePassword } from '../../utils/passwordPolicy';
import { getSecurityMessage, getThrottleMessage } from '../../utils/securityMessages';

const router = useRouter();

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

const isStudent = computed(() => form.account_type === 'student');

const filteredDepartments = computed(() => {
  if (form.account_type === 'student') {
    return allDepartments.value.filter(d => d.type === 'academic');
  }
  return allDepartments.value;
});

const deptPlaceholder = computed(() => {
  return form.account_type === 'student' ? 'Select academic department' : 'Select department or unit';
});

const passwordChecks = computed(() => validatePassword(form.password));
const confirmationMismatch = computed(() => form.confirm_password.length > 0 && form.password !== form.confirm_password);
const canSubmit = computed(() => passwordChecks.value.isValid && form.password === form.confirm_password);

const initialForm = () => ({
  account_type: 'student',
  first_name: '',
  last_name: '',
  email: '',
  gender: null,
  title: null,
  department: null,
  password: '',
  confirm_password: '',
});

const form = reactive(initialForm());

const switchType = (type) => {
  form.account_type = type;
  form.department = null;
  if (type === 'student') form.title = null;
  clearError('department');
  clearError('title');
};

const fetchDepartments = async () => {
  loadingDepartments.value = true;
  try {
    const response = await api.get('/api/departments');
    allDepartments.value = response.data.data;
  } catch { // silently fail
  } finally {
    loadingDepartments.value = false;
  }
};

const clearError = (field) => {
  if (errors.value[field]) {
    const n = { ...errors.value }; delete n[field]; errors.value = n;
  }
  if (localErrors.value[field]) {
    const n = { ...localErrors.value }; delete n[field]; localErrors.value = n;
  }
  errorMessage.value = '';
};

const validateLocal = () => {
  const errs = {};
  if (!form.first_name.trim()) errs.first_name = 'First name is required';
  if (!form.last_name.trim()) errs.last_name = 'Last name is required';
  if (!form.email.trim()) errs.email = 'Email address is required';
  if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(form.email.trim())) errs.email = errs.email || 'Enter a valid email address';
  if (!form.gender) errs.gender = 'Please select your gender';
  if (form.account_type === 'staff' && !form.title) errs.title = 'Please select your title';
  if (!form.department) errs.department = 'Please select a department';
  if (!form.password) errs.password = 'Password is required';
  else if (!passwordChecks.value.isValid) errs.password = 'Password does not meet all requirements';
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
      first_name: form.first_name.trim(),
      last_name: form.last_name.trim(),
      email: form.email.trim(),
      gender: form.gender,
      department: form.department,
      password: form.password,
      confirm_password: form.confirm_password,
    };
    if (form.account_type === 'staff') payload.title = form.title;
    const response = form.account_type === 'student'
      ? await authService.registerStudent(payload)
      : await authService.registerStaff(payload);
    router.push({ path: '/email-verification', query: { email: response.data.email, institution_id: response.data.institution_id, registered: '1' } });
  } catch (error) {
    const data = error.response?.data;
    const retry = error.response?.headers?.['retry-after'] || data?.retry_after;
    const code = data?.code ? String(data.code).toUpperCase() : null;
    if (data?.errors) errors.value = data.errors;
    if (error.__isSecurityBlock) {
      const mapped = (code ? getSecurityMessage(code, retry) : null) || getThrottleMessage(retry);
      errorMessage.value = mapped ? mapped.detail : (data?.error || data?.message || 'Registration failed');
    } else {
      errorMessage.value = data?.message || data?.error || 'Registration failed';
    }
  } finally {
    loading.value = false;
  }
};

onMounted(fetchDepartments);
</script>
