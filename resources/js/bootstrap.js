import axios from 'axios';

axios.defaults.baseURL = import.meta.env.VITE_API_BASE_URL || '/';
axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';
axios.defaults.withCredentials = true;
axios.defaults.withXSRFToken = true;

window.axios = axios;

export default axios;
