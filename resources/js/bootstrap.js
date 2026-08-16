import axios from 'axios';
import './onboarding-tour-local';
import './onboarding-tour-notifications';

window.axios = axios;

window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';
