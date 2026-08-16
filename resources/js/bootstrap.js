import axios from 'axios';
import './onboarding-tour-local';

window.axios = axios;

window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';
