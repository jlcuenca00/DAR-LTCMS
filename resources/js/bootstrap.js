import axios from 'axios';
import './onboarding-tour';
import './onboarding-replay-confirmation';

window.axios = axios;

window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';
