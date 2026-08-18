import axios from 'axios';
import './onboarding-tour';
import './onboarding-replay-confirmation';
import './application-intake-flow';
import './application-page-cleanup';
import './staff-list-filters';
import './dashboard-work-queue';
import './parcel-map-single-tooltip';
import './geodetic-geometry-workflow';
import './development-notice';
import '../css/staff-dashboard-hero.css';
import '../css/application-page-cleanup.css';
import '../css/staff-list-filters.css';
import '../css/application-table-toolbar.css';
import '../css/filter-control-normalization.css';
import '../css/active-filter-alignment.css';
import '../css/geodetic-geometry-workflow.css';
import '../css/development-notice.css';
import '../css/password-recovery-spacing.css';

window.axios = axios;

window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';
