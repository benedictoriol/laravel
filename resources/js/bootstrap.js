import axios from 'axios';
window.axios = axios;

window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';
window.axios.defaults.headers.common['Accept'] = 'application/json';

export function applyApiToken(token) {
    if (token) {
        window.axios.defaults.headers.common['Authorization'] = `Bearer ${token}`;
    } else {
        delete window.axios.defaults.headers.common['Authorization'];
    }
}

const savedToken = window.localStorage.getItem('embro_token');
if (savedToken) {
    applyApiToken(savedToken);
}
