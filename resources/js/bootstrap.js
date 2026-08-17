import axios from 'axios';
import './onboarding-tour';
import './onboarding-replay-confirmation';
import './application-intake-flow';
import './parcel-map-single-tooltip';
import '../css/staff-dashboard-hero.css';

window.axios = axios;

window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';
