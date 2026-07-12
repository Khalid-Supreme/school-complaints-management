import './bootstrap';
import { createApp } from 'vue';
import { createPinia } from 'pinia';
import PrimeVue from 'primevue/config';
import ToastService from 'primevue/toastservice';
import App from './App.vue';
import router from './router';
import { sagePreset, pt } from './primevue-theme';

import Tooltip from 'primevue/tooltip';

const app = createApp(App);
const pinia = createPinia();

app.use(pinia);
app.use(router);
app.use(ToastService);
app.directive('tooltip', Tooltip); 

app.use(PrimeVue, {
    theme: {
        preset: sagePreset,
        options: {
            dark: false,  
        },
    },
    pt: pt,
});

app.mount('#app');
