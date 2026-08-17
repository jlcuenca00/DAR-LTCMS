import axios from 'axios';
import './onboarding-tour';
import './onboarding-replay-confirmation';
import './application-intake-flow';

window.axios = axios;

window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';
