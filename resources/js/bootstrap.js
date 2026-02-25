import axios from 'axios';

window.axios = axios;

window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';

const SESSION_STORAGE_KEY = 'calctek_session_token';

if (typeof window !== 'undefined' && typeof localStorage !== 'undefined') {
    let token = localStorage.getItem(SESSION_STORAGE_KEY);

    if (!token) {
        if (window.crypto && typeof window.crypto.randomUUID === 'function') {
            token = window.crypto.randomUUID();
        } else {
            token = Math.random().toString(36).slice(2) + Date.now().toString(36);
        }

        localStorage.setItem(SESSION_STORAGE_KEY, token);
    }

    window.axios.defaults.headers.common['X-Calculator-Session'] = token;
}
