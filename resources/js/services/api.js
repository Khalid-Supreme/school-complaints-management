import axios from '../bootstrap';

export async function initializeCsrf() {
    await axios.get('/sanctum/csrf-cookie');
}

export default axios;
