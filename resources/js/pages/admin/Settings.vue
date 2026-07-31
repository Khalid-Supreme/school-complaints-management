<template>
    <div class="space-y-8">
        <!-- Header -->
        <div class="flex flex-col sm:flex-row sm:justify-between sm:items-center gap-4">
            <div>
                <h1 class="text-2xl lg:text-3xl font-semibold text-charcoal tracking-tight">Application Settings</h1>
                <p class="text-slate-400 text-sm mt-1 font-medium">Manage application branding and basic configuration</p>
            </div>
        </div>

        <!-- Branding Section -->
        <Card class="shadow-card border border-sage-100">
            <template #title>
                <div class="flex items-center gap-2 text-base font-semibold pb-4 mb-4 border-b border-sage-100">
                    <i class="pi pi-palette text-sage-600"></i>
                    <span class="text-charcoal">Branding</span>
                </div>
            </template>
            <template #content>
                <div class="space-y-6">
                    <!-- App Name -->
                    <div>
                        <label for="app_name" class="block text-sm font-medium text-charcoal/80 mb-2">Application Name</label>
                        <InputText id="app_name" v-model="form.app_name" class="w-full !bg-white !border-sage-200/60 !rounded-lg !py-2.5 !px-4" :disabled="saving" />
                    </div>

                    <!-- Logo -->
                    <div>
                        <label class="block text-sm font-medium text-charcoal/80 mb-2">Logo</label>
                        <div class="flex items-center gap-4 flex-wrap">
<div v-if="settings && settings.logo_url" class="relative">
                            <img :src="settings.logo_url" alt="Current Logo" class="h-16 w-auto rounded-lg border border-sage-100 bg-white" />
                                <Button icon="pi pi-trash" text severity="secondary" size="small" class="absolute -top-2 -right-2 !bg-red-50 hover:!bg-red-100 !text-red-600" @click="removeLogo" :disabled="saving" />
                            </div>
                            <div v-else class="h-16 w-16 rounded-lg border-2 border-dashed border-sage-200 flex items-center justify-center text-slate-400">
                                <i class="pi pi-image text-xl"></i>
                            </div> 
                            <div class="flex-1 min-w-[200px]">
                                <input type="file" ref="logoInput" accept="image/png,image/jpeg,image/svg+xml" class="hidden" @change="onLogoSelect" />
                                <Button label="Upload Logo" icon="pi pi-upload" @click="$refs.logoInput.click()" :disabled="saving" class="!bg-sage-100 hover:!bg-sage-200 !border-none !text-sage-700 !font-medium" />
                                <p class="text-xs text-slate-400 mt-1">PNG, JPG, or SVG. Max 2MB. Recommended: 200x60px</p>
                            </div>
                        </div>
                    </div>

                    <!-- Favicon -->
                    <div>
                        <label class="block text-sm font-medium text-charcoal/80 mb-2">Favicon</label>
                        <div class="flex items-center gap-4 flex-wrap">
<div v-if="settings && settings.favicon_url" class="relative">
                            <img :src="settings.favicon_url" alt="Current Favicon" class="h-10 w-10 rounded border border-sage-100 bg-white" />
                                <Button icon="pi pi-trash" text severity="secondary" size="small" class="absolute -top-2 -right-2 !bg-red-50 hover:!bg-red-100 !text-red-600" @click="removeFavicon" :disabled="saving" />
                            </div>
                            <div v-else class="h-10 w-10 rounded border-2 border-dashed border-sage-200 flex items-center justify-center text-slate-400">
                                <i class="pi pi-image text-lg"></i>
                            </div>
                            <div class="flex-1 min-w-[200px]">
                                <input type="file" ref="faviconInput" accept="image/png,image/x-icon" class="hidden" @change="onFaviconSelect" />
                                <Button label="Upload Favicon" icon="pi pi-upload" @click="$refs.faviconInput.click()" :disabled="saving" class="!bg-sage-100 hover:!bg-sage-200 !border-none !text-sage-700 !font-medium" />
                                <p class="text-xs text-slate-400 mt-1">PNG or ICO. Max 512KB. Recommended: 32x32px</p>
                            </div>
                        </div>
                    </div>

                    <!-- Save Branding Button -->
                    <div class="pt-4 border-t border-sage-100">
                        <Button label="Save Branding" icon="pi pi-check" :loading="saving" class="!bg-sage-600 hover:!bg-sage-700 !border-none !text-white !font-semibold !rounded-lg !py-2 !px-4" @click="saveSettings('branding')" />
                    </div>
                </div>
            </template>
        </Card>

        <!-- Contact Information Section -->
        <Card class="shadow-card border border-sage-100">
            <template #title>
                <div class="flex items-center gap-2 text-base font-semibold pb-4 mb-4 border-b border-sage-100">
                    <i class="pi pi-envelope text-sage-600"></i>
                    <span class="text-charcoal">Contact Information</span>
                </div>
            </template>
            <template #content>
                <div class="space-y-4">
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label for="contact_email" class="block text-sm font-medium text-charcoal/80 mb-2">Contact Email</label>
                            <InputText id="contact_email" v-model="form.contact_email" type="email" placeholder="support@schoolvoice.app" class="w-full !bg-white !border-sage-200/60 !rounded-lg !py-2.5 !px-4" :disabled="saving" />
                        </div>
                        <div>
                            <label for="contact_phone" class="block text-sm font-medium text-charcoal/80 mb-2">Contact Phone</label>
                            <InputText id="contact_phone" v-model="form.contact_phone" placeholder="+1 (555) 000-0000" class="w-full !bg-white !border-sage-200/60 !rounded-lg !py-2.5 !px-4" :disabled="saving" />
                        </div>
                    </div>
                    <div>
                        <label for="address" class="block text-sm font-medium text-charcoal/80 mb-2">Address</label>
                        <Textarea id="address" v-model="form.address" rows="3" placeholder="Institution address..." class="w-full !bg-white !border-sage-200/60 !rounded-lg !py-2.5 !px-4" :disabled="saving" />
                    </div>
                    <div class="pt-4 border-t border-sage-100">
                        <Button label="Save Contact Info" icon="pi pi-check" :loading="saving" class="!bg-sage-600 hover:!bg-sage-700 !border-none !text-white !font-semibold !rounded-lg !py-2 !px-4" @click="saveSettings('contact')" />
                    </div>
                </div>
            </template>
        </Card>

        <!-- Social Links Section -->
        <Card class="shadow-card border border-sage-100">
            <template #title>
                <div class="flex items-center gap-2 text-base font-semibold pb-4 mb-4 border-b border-sage-100">
                    <i class="pi pi-share-alt text-sage-600"></i>
                    <span class="text-charcoal">Social Links</span>
                </div>
            </template>
            <template #content>
                <div class="space-y-4">
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                        <div>
                            <label for="social_twitter" class="block text-sm font-medium text-charcoal/80 mb-2">Twitter / X</label>
                            <InputText id="social_twitter" v-model="form.social_links.twitter" placeholder="https://twitter.com/..." class="w-full !bg-white !border-sage-200/60 !rounded-lg !py-2.5 !px-4" :disabled="saving" />
                        </div>
                        <div>
                            <label for="social_facebook" class="block text-sm font-medium text-charcoal/80 mb-2">Facebook</label>
                            <InputText id="social_facebook" v-model="form.social_links.facebook" placeholder="https://facebook.com/..." class="w-full !bg-white !border-sage-200/60 !rounded-lg !py-2.5 !px-4" :disabled="saving" />
                        </div>
                        <div>
                            <label for="social_linkedin" class="block text-sm font-medium text-charcoal/80 mb-2">LinkedIn</label>
                            <InputText id="social_linkedin" v-model="form.social_links.linkedin" placeholder="https://linkedin.com/..." class="w-full !bg-white !border-sage-200/60 !rounded-lg !py-2.5 !px-4" :disabled="saving" />
                        </div>
                    </div>
                    <div class="pt-4 border-t border-sage-100">
                        <Button label="Save Social Links" icon="pi pi-check" :loading="saving" class="!bg-sage-600 hover:!bg-sage-700 !border-none !text-white !font-semibold !rounded-lg !py-2 !px-4" @click="saveSettings('social')" />
                    </div>
                </div>
            </template>
        </Card>
    </div>
</template>

<script setup>
import { ref, onMounted, reactive, computed } from 'vue';
import { useToast } from 'primevue/usetoast';
import api from '../../services/api';
import Card from 'primevue/card';
import InputText from 'primevue/inputtext';
import Textarea from 'primevue/textarea';
import Button from 'primevue/button';

const toast = useToast();
const saving = ref(false);
const settings = ref(null);

const form = reactive({
    app_name: '',
    contact_email: '',
    contact_phone: '',
    address: '',
    social_links: {
        twitter: '',
        facebook: '',
        linkedin: '',
    },
});

const logoInput = ref(null);
const faviconInput = ref(null);
const selectedLogo = ref(null);
const selectedFavicon = ref(null);

const loadSettings = async () => {
    try {
        const res = await api.get('/api/settings');
        settings.value = res.data.data;
        form.app_name = settings.value.app_name || '';
        form.contact_email = settings.value.contact_email || '';
        form.contact_phone = settings.value.contact_phone || '';
        form.address = settings.value.address || '';
        form.social_links = settings.value.social_links || { twitter: '', facebook: '', linkedin: '' };
    } catch (e) {
        toast.add({ severity: 'error', summary: 'Error', detail: 'Failed to load settings', life: 3000 });
    }
};

const saveSettings = async (section) => {
    saving.value = true;
    try {
        const data = {};
        if (section === 'branding') data.app_name = form.app_name;
        else if (section === 'contact') {
            data.contact_email = form.contact_email;
            data.contact_phone = form.contact_phone;
            data.address = form.address;
        } else if (section === 'social') {
            data.social_links = form.social_links;
        }

        const res = await api.put('/api/settings', data);
        if (res.data.success) {
            toast.add({ severity: 'success', summary: 'Saved', detail: 'Settings updated successfully', life: 3000 });
            await loadSettings();
        }
    } catch (e) {
        toast.add({ severity: 'error', summary: 'Error', detail: e.response?.data?.message || 'Failed to save settings', life: 3000 });
    } finally {
        saving.value = false;
    }
};

const onLogoSelect = (e) => {
    const file = e.target.files[0];
    if (!file) return;
    if (file.size > 2 * 1024 * 1024) {
        toast.add({ severity: 'error', summary: 'File too large', detail: 'Logo must be under 2MB', life: 3000 });
        return;
    }
    selectedLogo.value = file;
    uploadLogo();
};

const onFaviconSelect = (e) => {
    const file = e.target.files[0];
    if (!file) return;
    if (file.size > 512 * 1024) {
        toast.add({ severity: 'error', summary: 'File too large', detail: 'Favicon must be under 512KB', life: 3000 });
        return;
    }
    selectedFavicon.value = file;
    uploadFavicon();
};

const uploadLogo = async () => {
    if (!selectedLogo.value) return;
    saving.value = true;
    try {
        const formData = new FormData();
        formData.append('logo', selectedLogo.value);
        const res = await api.post('/api/settings/logo', formData, {
            headers: { 'Content-Type': 'multipart/form-data' }
        });
        if (res.data.success) {
            toast.add({ severity: 'success', summary: 'Uploaded', detail: 'Logo uploaded successfully', life: 3000 });
            await loadSettings();
            selectedLogo.value = null;
            if (logoInput.value) logoInput.value.value = '';
        }
    } catch (e) {
        toast.add({ severity: 'error', summary: 'Error', detail: e.response?.data?.message || 'Failed to upload logo', life: 3000 });
    } finally {
        saving.value = false;
    }
};

const uploadFavicon = async () => {
    if (!selectedFavicon.value) return;
    saving.value = true;
    try {
        const formData = new FormData();
        formData.append('favicon', selectedFavicon.value);
        const res = await api.post('/api/settings/favicon', formData, {
            headers: { 'Content-Type': 'multipart/form-data' }
        });
        if (res.data.success) {
            toast.add({ severity: 'success', summary: 'Uploaded', detail: 'Favicon uploaded successfully', life: 3000 });
            await loadSettings();
            selectedFavicon.value = null;
            if (faviconInput.value) faviconInput.value.value = '';
        }
    } catch (e) {
        toast.add({ severity: 'error', summary: 'Error', detail: e.response?.data?.message || 'Failed to upload favicon', life: 3000 });
    } finally {
        saving.value = false;
    }
};

const removeLogo = async () => {
    saving.value = true;
    try {
        const res = await api.delete('/api/settings/logo');
        if (res.data.success) {
            toast.add({ severity: 'success', summary: 'Removed', detail: 'Logo removed successfully', life: 3000 });
            await loadSettings();
        }
    } catch (e) {
        toast.add({ severity: 'error', summary: 'Error', detail: 'Failed to remove logo', life: 3000 });
    } finally {
        saving.value = false;
    }
};

const removeFavicon = async () => {
    saving.value = true;
    try {
        const res = await api.delete('/api/settings/favicon');
        if (res.data.success) {
            toast.add({ severity: 'success', summary: 'Removed', detail: 'Favicon removed successfully', life: 3000 });
            await loadSettings();
        }
    } catch (e) {
        toast.add({ severity: 'error', summary: 'Error', detail: 'Failed to remove favicon', life: 3000 });
    } finally {
        saving.value = false;
    }
};

onMounted(loadSettings);
</script>