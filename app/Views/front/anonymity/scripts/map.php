<?php // Leaflet-backed location estimate rendering. ?>
    let leafletMapInstance = null;
    function initMap(lat, lon, zoom = 11) {
        const container = document.getElementById('map');
        if (!container) return;

        const latNum = typeof lat === 'number' ? lat : Number(lat);
        const lonNum = typeof lon === 'number' ? lon : Number(lon);
        const hasCoordinates = lat !== null && lon !== null && Number.isFinite(latNum) && Number.isFinite(lonNum);
        const coordinateText = hasCoordinates
            ? latNum.toFixed(4) + ', ' + lonNum.toFixed(4)
            : 'Coordinates unavailable';
        const locality = [serverSignals.city, serverSignals.region, serverSignals.countryName]
            .filter(Boolean)
            .join(', ') || 'Location unavailable';

        container.innerHTML = '';

        if (hasCoordinates && typeof L !== 'undefined') {
            try {
                const wrapper = document.getElementById('map-wrapper');
                if (wrapper) wrapper.style.position = 'relative';

                const mapDiv = document.createElement('div');
                mapDiv.id = 'osm-map';
                container.appendChild(mapDiv);

                if (leafletMapInstance) {
                    leafletMapInstance.remove();
                    leafletMapInstance = null;
                }

                leafletMapInstance = L.map(mapDiv, {
                    zoomControl: false,
                    attributionControl: false,
                    dragging: false,
                    scrollWheelZoom: false,
                    doubleClickZoom: false,
                    boxZoom: false,
                    touchZoom: false
                }).setView([latNum, lonNum], zoom);

                L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                    maxZoom: 19
                }).addTo(leafletMapInstance);

                L.circle([latNum, lonNum], {
                    color: 'var(--exposed)',
                    fillColor: 'var(--exposed)',
                    fillOpacity: 0.18,
                    radius: 3500,
                    weight: 1.5
                }).addTo(leafletMapInstance);

                const marker = L.circleMarker([latNum, lonNum], {
                    color: 'var(--accent)',
                    fillColor: 'var(--accent)',
                    fillOpacity: 0.8,
                    radius: 5.5,
                    weight: 1
                }).addTo(leafletMapInstance);

                const popup = L.popup({
                    closeButton: false,
                    className: 'custom-map-popup'
                }).setContent(`<strong>${escapeHtml(locality)}</strong>`);
                marker.bindPopup(popup).openPopup();
            } catch (e) {
                console.error("Leaflet map initialization failed:", e);
                const reason = hasCoordinates ? 'Map tile loading failed' : 'No coordinates resolved from IP';
                const geoDesc = locality !== 'Location unavailable'
                    ? 'IP-based estimate:<br><strong>' + escapeHtml(locality) + '</strong> (' + escapeHtml(coordinateText) + ')'
                    : 'No geolocation source available. Add a MaxMind GeoLite2-City database or connect through a CDN with geo headers.';
                container.innerHTML = `
                    <div style="display: flex; flex-direction: column; align-items: center; justify-content: center; height: 100%; text-align: center; padding: 24px; font-family: var(--font-body);">
                        <div style="font-family: var(--font-display); font-size: 1.6rem; text-transform: uppercase; margin-bottom: 6px; letter-spacing: 0.02em;">Map Unavailable</div>
                        <div style="color: var(--fg-muted); font-size: 0.88rem; max-width: 300px; line-height: 1.4;">
                            ${escapeHtml(reason)}. ${geoDesc}
                        </div>
                    </div>
                `;
            }
        } else {
            const geoDesc = locality !== 'Location unavailable'
                ? 'IP-based estimate:<br><strong>' + escapeHtml(locality) + '</strong> (' + escapeHtml(coordinateText) + ')'
                : 'No geolocation source available. Add a MaxMind GeoLite2-City database or connect through a CDN with geo headers.';
            container.innerHTML = `
                <div style="display: flex; flex-direction: column; align-items: center; justify-content: center; height: 100%; text-align: center; padding: 24px; font-family: var(--font-body);">
                    <div style="font-family: var(--font-display); font-size: 1.6rem; text-transform: uppercase; margin-bottom: 6px; letter-spacing: 0.02em;">Map Unavailable</div>
                    <div style="color: var(--fg-muted); font-size: 0.88rem; max-width: 300px; line-height: 1.4;">
                        ${geoDesc}
                    </div>
                </div>
            `;
        }
    }
