import { definePreset } from '@primevue/themes';
import Aura from '@primevue/themes/aura';

// Custom pastel sage green theme preset based on Apple's design philosophy.
// All components will inherit these styles automatically.
export const sagePreset = definePreset(Aura, {
    semantic: {
        primary: {
            50:  '#f4f9f0',
            100: '#e2eed9',
            200: '#c9ddbf',
            300: '#a8c69c',
            400: '#88b07d',
            500: '#6a9c5e',
            600: '#55834b',
            700: '#45693c',
            800: '#365230',
            900: '#2a4026',
            950: '#152212',
        },
        surface: {
            0:   '#ffffff',
            50:  '#f8faf6',
            100: '#f0f3ee',
            200: '#e2e7df',
            300: '#cdd5c8',
            400: '#b4bfb0',
            500: '#99a695',
            600: '#7e8d7a',
            700: '#657262',
            800: '#4e594a',
            900: '#3a4237',
            950: '#1f251e',
        },
    },
    components: {
        button: {
            root: {
                borderRadius: '8px',
                padding: '0.65rem 1.25rem',
                fontWeight: '600',
                transition: 'all 0.2s cubic-bezier(0.4, 0, 0.2, 1)',
            },
        },
        inputtext: {
            root: {
                borderRadius: '8px',
                padding: '0.75rem 1rem',
                borderColor: '#E2E8EC',
                background: '#FFFFFF',
                color: '#1A252C',
                fontSize: '0.95rem',
                transition: 'all 0.2s ease',
            },
        },
        datatable: {
            root: {
                borderRadius: '12px',
                overflow: 'hidden',
                border: '1px solid #E2E8EC',
            },
            header: {
                background: '#F8FAF6',
                color: '#1A252C',
                fontWeight: '600',
                padding: '1rem 1.25rem',
            },
            row: {
                padding: '1rem 1.25rem',
                transition: 'background-color 0.2s ease',
            },
        },
        card: {
            root: {
                borderRadius: '12px',
                boxShadow: '0 4px 30px rgba(0, 0, 0, 0.03)',
                border: '1px solid #E8EFE9',
                background: '#FFFFFF',
            },
        },
    },
});

// Helper to extend chaining for 'pt' (pass through) props on the fly
export const pt = {
    button: {
        root: 'font-sans',
    },
    inputtext: {
        root: 'font-sans focus:ring-2 focus:ring-sage-200 focus:border-sage-400',
    },
};
