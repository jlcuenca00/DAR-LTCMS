import L from 'leaflet';
import 'leaflet/dist/leaflet.css';

const mapElement = document.getElementById('public-parcel-map');
const status = document.getElementById('listing-map-status');
const list = document.getElementById('listing-map-list');
const more = document.getElementById('listing-map-more');
const map = L.map(mapElement, { scrollWheelZoom: false }).setView([9.46, 123.18], 10);
let nextPage = mapElement.dataset.featuresUrl;

L.tileLayer('https://{s}.basemaps.cartocdn.com/light_all/{z}/{x}/{y}{r}.png', {
    maxZoom: 20,
    attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> &copy; <a href="https://carto.com/attributions">CARTO</a>',
}).addTo(map);

function listingDetails(properties) {
    const content = document.createElement('div');
    const location = [properties.barangay, properties.municipality].filter(Boolean).join(', ');
    const heading = document.createElement('strong');
    heading.textContent = properties.display_name;
    const details = document.createElement('p');
    details.textContent = [location, properties.area_hectares && `Approx. ${Number(properties.area_hectares)} ha`].filter(Boolean).join(' · ');
    content.append(heading, details);

    if (properties.demo) {
        const badge = document.createElement('p');
        badge.textContent = 'Demo only · Fictional parcel, unavailable for purchase';
        content.append(badge);
    } else if (/^\+639\d{9}$/.test(properties.mobile)) {
        const link = document.createElement('a');
        link.href = `tel:${properties.mobile}`;
        link.textContent = `Call owner · ${properties.mobile}`;
        link.className = 'parcel-call-link';
        content.append(link);
    }

    return content;
}

const parcels = L.geoJSON([], {
    style: (feature) => ({
        color: feature.properties.demo ? '#92400e' : '#047857',
        dashArray: feature.properties.demo ? '5 4' : null,
        weight: 2,
        fillOpacity: 0.25,
    }),
    onEachFeature(feature, layer) {
        layer.bindPopup(listingDetails(feature.properties));
        const item = document.createElement('li');
        item.className = 'parcel-list-item';
        const locate = document.createElement('button');
        locate.type = 'button';
        locate.className = 'parcel-locate';
        locate.textContent = 'Zoom to parcel';
        locate.setAttribute('aria-label', `Zoom to parcel listed by ${feature.properties.display_name}`);
        locate.addEventListener('click', () => {
            map.fitBounds(layer.getBounds(), { maxZoom: 18, padding: [20, 20] });
            layer.openPopup();
        });
        item.append(listingDetails(feature.properties), locate);
        list.append(item);
    },
}).addTo(map);

async function loadListings() {
    more.disabled = true;
    try {
        const url = new URL(nextPage, window.location.origin);
        const response = await fetch(url.pathname + url.search, { cache: 'no-store' });
        if (!response.ok) throw new Error('Listing request failed');
        const page = await response.json();
        parcels.addData(page.features);
        if (page.features.some((feature) => feature.properties.demo)) {
            document.getElementById('listing-demo-note').hidden = false;
        }
        nextPage = page.next;
        const count = list.children.length;
        status.textContent = count ? `${count} ${count === 1 ? 'listing' : 'listings'} shown.` : 'No parcels are currently listed.';
        if (page.features.length) {
            map.fitBounds(parcels.getBounds(), { maxZoom: 16, padding: [20, 20] });
        }
    } catch {
        status.textContent = 'Listings could not be loaded. Try again.';
    } finally {
        more.hidden = !nextPage;
        more.disabled = false;
    }
}

more.addEventListener('click', loadListings);
loadListings();
