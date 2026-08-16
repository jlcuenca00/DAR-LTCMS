import axios from 'axios';
import './onboarding-tour-v3';

window.axios = axios;

window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';
