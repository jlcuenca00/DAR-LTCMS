import axios from 'axios';
import L from 'leaflet';
import proj4 from 'proj4';
import 'leaflet/dist/leaflet.css';
import './onboarding-tour';
import './role-onboarding-tours';
import './onboarding-replay-confirmation';
import './application-intake-flow';
import './application-page-cleanup';
import './application-citizens-charter-flow';
import './application-final-decision-presentation';
import './staff-list-filters';
import './dashboard-work-queue';
import './staff-dashboard-hero';
import './parcel-map-single-tooltip';
import './carto-basemap-key';
import './geodetic-geometry-workflow';
import './geodetic-existing-coordinate-reference';
import './account-panel';
import './notification-dropdown';
import './user-management-linked-records';
import './staff-record-row-navigation';
import './responsive-hardening';
import './mobile-portal-polish';
import './ui-ux-system';
import './ui-ux-last-mile';
import './ui-ux-public';
import '../css/staff-dashboard-hero.css';
import '../css/dashboard-citizens-charter.css';
import '../css/application-page-cleanup.css';
import '../css/staff-list-filters.css';
import '../css/application-table-toolbar.css';
import '../css/filter-control-normalization.css';
import '../css/active-filter-alignment.css';
import '../css/geodetic-geometry-workflow.css';
import '../css/geodetic-responsive.css';
import '../css/password-recovery-spacing.css';
import '../css/account-panel-polish.css';
import '../css/user-management-linked-records.css';
import '../css/staff-record-row-navigation.css';
import '../css/responsive-hardening-last-mile.css';
import '../css/ui-ux-system.css';
import '../css/ui-ux-last-mile.css';
import '../css/application-modal-viewport.css';
import '../css/ui-ux-public.css';

window.axios = axios;
window.L = L;

// PRS92 / Philippines zone 4 (EPSG:3124), the projected CRS used for Negros parcel survey coordinates.
proj4.defs(
    'EPSG:3124',
    '+proj=tmerc +lat_0=0 +lon_0=123 +k=0.99995 +x_0=500000 +y_0=0 +ellps=clrk66 +towgs84=-127.62,-67.24,-47.04,3.068,-4.903,-1.578,-1.06 +units=m +no_defs +type=crs'
);
proj4.defs('EPSG:4326', '+proj=longlat +datum=WGS84 +no_defs +type=crs');

window.DarLtcmsProjection = Object.freeze({
    sourceCrs: 'EPSG:3124',
    displayCrs: 'EPSG:4326',
    toWgs84(easting, northing) {
        const x = Number(easting);
        const y = Number(northing);

        if (!Number.isFinite(x) || !Number.isFinite(y)) {
            throw new TypeError('PRS92 Zone IV coordinates must be finite numbers.');
        }

        return proj4('EPSG:3124', 'EPSG:4326', [x, y]);
    },
    toPrs92(longitude, latitude) {
        const x = Number(longitude);
        const y = Number(latitude);

        if (!Number.isFinite(x) || !Number.isFinite(y)) {
            throw new TypeError('WGS84 coordinates must be finite numbers.');
        }

        return proj4('EPSG:4326', 'EPSG:3124', [x, y]);
    },
});

window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';
