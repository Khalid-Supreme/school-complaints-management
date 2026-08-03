import { ref } from 'vue';
import api from '../services/api';

const settings = ref(null);
const loading = ref(false);
let loadPromise = null;

export function useSettings() {
    const loadSettings = async (force = false) => {
        if (settings.value && !force) return settings.value;
        if (loadPromise) return loadPromise;

        loading.value = true;
        loadPromise = (async () => {
            try {
                const res = await api.get('/api/settings');
                settings.value = res.data.data;
                return settings.value;
            } catch (e) {
                console.error('Failed to load settings:', e);
                return null;
            } finally {
                loading.value = false;
                loadPromise = null;
            }
        })();
        return loadPromise;
    };

    const getSettings = () => settings.value;

    const getAppName = () => settings.value?.app_name || 'SchoolVoice';

    const getBrandNameParts = () => {
        const appName = getAppName().toLowerCase();
        const parts = appName.split(/\s+/).filter(Boolean);

        if (parts.length >= 2) {
            return {
                primary: parts[0],
                secondary: parts.slice(1).join(' '),
            };
        }

        const compact = appName.replace(/\s+/g, '');
        if (compact === 'schoolvoice') {
            return { primary: 'school', secondary: 'voice' };
        }

        return { primary: appName, secondary: '' };
    };

    const getContactEmail = () => settings.value?.contact_email || null;

    const getContactPhone = () => settings.value?.contact_phone || null;

    const getAddress = () => settings.value?.address || null;

    const getSocialLinks = () => settings.value?.social_links || {};

    return {
        loadSettings,
        getSettings,
        getAppName,
        getBrandNameParts,
        getContactEmail,
        getContactPhone,
        getAddress,
        getSocialLinks,
        settings,
        loading,
    };
}

// Singleton instance for global access
const settingsService = useSettings();
export default settingsService;