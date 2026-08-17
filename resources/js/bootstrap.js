import axios from 'axios';
import './onboarding-tour';
import './onboarding-replay-confirmation';
import './application-intake-flow';
import './application-page-cleanup';
import './parcel-map-single-tooltip';
import './development-notice';
import '../css/staff-dashboard-hero.css';
import '../css/application-page-cleanup.css';
import '../css/development-notice.css';

window.axios = axios;

window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';
