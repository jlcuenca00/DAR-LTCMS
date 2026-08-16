import axios from 'axios';
import './onboarding-tour-driver';

window.axios = axios;

window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';
