import { NEARBY_EMERGENCY_CENTERS } from '../constants/data';

/**
 * Generates the self-contained HTML for the interactive OpenStreetMap + Leaflet 1.9.4 map,
 * complete with custom markers, popups, live OSRM road routing between user & selected center,
 * and two-way postMessage bridge for Web <iframe> and Native <WebView>.
 */
export function buildLeafletMapHtml({ userLat, userLng, isStreaming, childName, centers, selectedId }) {
  const payload = JSON.stringify({
    userLat,
    userLng,
    isStreaming,
    childName: childName || 'Your GPS Position',
    centers: centers || NEARBY_EMERGENCY_CENTERS,
    selectedId: selectedId || (centers && centers[0] && centers[0].id) || 'csn-center',
  });

  return `<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no" />
  <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
  <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
  <style>
    * { box-sizing: border-box; margin: 0; padding: 0; }
    html, body, #map { width: 100%; height: 100%; font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; background: #E2E8F0; }
    .user-marker-wrap {
      position: relative;
      width: 36px;
      height: 36px;
      display: flex;
      align-items: center;
      justify-content: center;
    }
    .user-pulse {
      position: absolute;
      width: 44px;
      height: 44px;
      border-radius: 50%;
      background: rgba(16, 185, 129, 0.32);
      border: 2px solid #10B981;
      animation: pulseRing 1.8s infinite ease-out;
    }
    @keyframes pulseRing {
      0% { transform: scale(0.7); opacity: 1; }
      100% { transform: scale(1.75); opacity: 0; }
    }
    .user-dot {
      position: relative;
      width: 22px;
      height: 22px;
      border-radius: 50%;
      background: #005B96;
      border: 3px solid #FFFFFF;
      box-shadow: 0 2px 8px rgba(0,0,0,0.35);
      z-index: 2;
    }
    .facility-pin {
      display: flex;
      flex-direction: column;
      align-items: center;
      transform: translateY(-8px);
    }
    .facility-bubble {
      width: 28px;
      height: 28px;
      border-radius: 50%;
      border: 2px solid #FFFFFF;
      box-shadow: 0 2px 6px rgba(15,23,42,0.35);
      display: flex;
      align-items: center;
      justify-content: center;
      color: #FFFFFF;
      font-weight: 900;
      font-size: 12px;
      transition: transform 0.2s ease;
    }
    .facility-bubble.selected {
      transform: scale(1.2);
      box-shadow: 0 0 0 3px rgba(0, 91, 150, 0.3), 0 3px 8px rgba(0,0,0,0.35);
    }
    .facility-label {
      margin-top: 3px;
      background: rgba(255,255,255,0.92);
      color: #0F172A;
      font-size: 9px;
      font-weight: 700;
      padding: 1px 6px;
      border-radius: 4px;
      white-space: nowrap;
      max-width: 90px;
      overflow: hidden;
      text-overflow: ellipsis;
      box-shadow: 0 1px 3px rgba(0,0,0,0.15);
      border: 1px solid #CBD5E1;
      pointer-events: none;
    }
    .route-badge {
      position: absolute;
      bottom: 8px;
      left: 8px;
      max-width: calc(100% - 16px);
      z-index: 1000;
      background: rgba(15, 23, 42, 0.90);
      color: #FFFFFF;
      padding: 6px 12px;
      border-radius: 999px;
      font-size: 11px;
      font-weight: 700;
      box-shadow: 0 2px 8px rgba(0,0,0,0.25);
      pointer-events: none;
      white-space: nowrap;
      overflow: hidden;
      text-overflow: ellipsis;
    }
    .leaflet-control-attribution {
      display: none !important;
    }
    .leaflet-popup-content-wrapper {
      border-radius: 12px;
      font-size: 12px;
    }
    .leaflet-popup-content {
      margin: 10px 12px;
      line-height: 1.4;
    }
  </style>
</head>
<body>
  <div id="map"></div>
  <div id="routeBadge" class="route-badge">Calculating route…</div>
  <script>
    const STATE = ${payload};
    const map = L.map('map', { zoomControl: true, attributionControl: false }).setView([STATE.userLat, STATE.userLng], 14);

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
      maxZoom: 19
    }).addTo(map);

    let userMarker = null;
    let accuracyCircle = null;
    let routeLayer = null;
    const markersById = {};

    function sendToApp(msg) {
      const str = JSON.stringify(msg);
      if (window.ReactNativeWebView && window.ReactNativeWebView.postMessage) {
        window.ReactNativeWebView.postMessage(str);
      } else if (window.parent && window.parent !== window) {
        window.parent.postMessage(str, '*');
      }
    }

    function symbolForCenter(c) {
      if (c.id === 'csn-center') return '★';
      if (c.icon === 'flame') return '🔥';
      if (c.icon === 'shield-checkmark') return '🛡';
      return '✚';
    }

    function renderUser() {
      const html = '<div class="user-marker-wrap">' +
        (STATE.isStreaming ? '<div class="user-pulse"></div>' : '') +
        '<div class="user-dot" style="background:' + (STATE.isStreaming ? '#005B96' : '#64748B') + '"></div>' +
        '</div>';
      const icon = L.divIcon({
        className: '',
        html: html,
        iconSize: [36, 36],
        iconAnchor: [18, 18]
      });

      if (userMarker) {
        userMarker.setLatLng([STATE.userLat, STATE.userLng]);
        userMarker.setIcon(icon);
      } else {
        userMarker = L.marker([STATE.userLat, STATE.userLng], { icon: icon, zIndexOffset: 1000 })
          .addTo(map)
          .bindPopup('<b>' + STATE.childName + '</b><br/>Live GPS: ' + STATE.userLat.toFixed(5) + ', ' + STATE.userLng.toFixed(5));
      }

      if (accuracyCircle) {
        accuracyCircle.setLatLng([STATE.userLat, STATE.userLng]);
      } else {
        accuracyCircle = L.circle([STATE.userLat, STATE.userLng], {
          radius: 120,
          color: STATE.isStreaming ? '#10B981' : '#94A3B8',
          fillColor: STATE.isStreaming ? '#10B981' : '#94A3B8',
          fillOpacity: 0.12,
          weight: 1.5
        }).addTo(map);
      }
    }

    function drawRouteToSelected() {
      const target = STATE.centers.find(c => c.id === STATE.selectedId) || STATE.centers[0];
      const badge = document.getElementById('routeBadge');
      if (!target) {
        badge.textContent = 'Select a facility to view route';
        return;
      }

      const tLat = target.latitude != null ? target.latitude : target.lat;
      const tLng = target.longitude != null ? target.longitude : target.lng;

      // Draw immediate fallback dashed line first
      if (routeLayer) map.removeLayer(routeLayer);
      routeLayer = L.polyline([[STATE.userLat, STATE.userLng], [tLat, tLng]], {
        color: target.color || '#005B96',
        weight: 4,
        dashArray: '8, 8',
        opacity: 0.85
      }).addTo(map);

      badge.textContent = 'Route to ' + target.name;

      // Query OSRM for real road geometry & driving ETA
      const osrmUrl = 'https://router.project-osrm.org/route/v1/driving/' +
        STATE.userLng + ',' + STATE.userLat + ';' + tLng + ',' + tLat +
        '?overview=full&geometries=geojson';

      fetch(osrmUrl)
        .then(r => r.json())
        .then(data => {
          if (data && data.routes && data.routes[0]) {
            const route = data.routes[0];
            const coords = route.geometry.coordinates.map(pt => [pt[1], pt[0]]);
            if (routeLayer) map.removeLayer(routeLayer);
            routeLayer = L.polyline(coords, {
              color: target.color || '#005B96',
              weight: 5,
              opacity: 0.9
            }).addTo(map);
            const km = (route.distance / 1000).toFixed(1);
            const mins = Math.max(1, Math.round(route.duration / 60));
            badge.textContent = '🚗 ' + km + ' km (' + mins + ' min drive) to ' + target.name;
          }
        })
        .catch(() => {});
    }

    function renderCenters() {
      Object.keys(markersById).forEach(k => {
        map.removeLayer(markersById[k]);
        delete markersById[k];
      });

      STATE.centers.forEach(c => {
        const cLat = c.latitude != null ? c.latitude : c.lat;
        const cLng = c.longitude != null ? c.longitude : c.lng;
        const isSel = c.id === STATE.selectedId;
        const shortName = c.name.replace('Parañaque ', '');
        const pinHtml =
          '<div class="facility-pin">' +
            '<div class="facility-bubble ' + (isSel ? 'selected' : '') + '" style="background:' + (c.color || '#005B96') + '">' +
              symbolForCenter(c) +
            '</div>' +
            '<div class="facility-label">' + shortName + '</div>' +
          '</div>';

        const icon = L.divIcon({
          className: '',
          html: pinHtml,
          iconSize: [120, 46],
          iconAnchor: [60, 28],
          popupAnchor: [0, -24]
        });

        const popupHtml =
          '<div style="min-width:170px">' +
            '<div style="font-weight:800;color:#005B96;font-size:13px;margin-bottom:2px">' + c.name + '</div>' +
            '<div style="color:#475569;font-size:11px;margin-bottom:4px">' + (c.type || c.role || '') + '</div>' +
            '<div style="color:#1F2937;font-size:11px">📍 ' + (c.address || 'Parañaque City') + '</div>' +
            (c.phone ? '<div style="color:#E11D48;font-weight:700;font-size:11px;margin-top:4px">📞 ' + c.phone + '</div>' : '') +
          '</div>';

        const m = L.marker([cLat, cLng], { icon: icon, zIndexOffset: isSel ? 800 : 200 })
          .addTo(map)
          .bindPopup(popupHtml);

        m.on('click', () => {
          STATE.selectedId = c.id;
          renderCenters();
          drawRouteToSelected();
          sendToApp({ type: 'SELECT_CENTER', id: c.id });
        });

        markersById[c.id] = m;
      });
    }

    function fitAllBounds() {
      const pts = [[STATE.userLat, STATE.userLng]];
      STATE.centers.forEach(c => {
        const cLat = c.latitude != null ? c.latitude : c.lat;
        const cLng = c.longitude != null ? c.longitude : c.lng;
        if (cLat != null && cLng != null) pts.push([cLat, cLng]);
      });
      if (pts.length > 1) {
        map.fitBounds(pts, { padding: [36, 36], maxZoom: 15 });
      }
    }

    renderUser();
    renderCenters();
    drawRouteToSelected();
    fitAllBounds();

    function handleCommand(cmd) {
      if (!cmd || typeof cmd !== 'object') return;
      if (cmd.type === 'FOCUS_CENTER' && cmd.center) {
        STATE.selectedId = cmd.center.id;
        renderCenters();
        drawRouteToSelected();
        const cLat = cmd.center.latitude != null ? cmd.center.latitude : cmd.center.lat;
        const cLng = cmd.center.longitude != null ? cmd.center.longitude : cmd.center.lng;
        map.flyTo([cLat, cLng], 16, { duration: 0.7 });
        if (markersById[cmd.center.id]) {
          markersById[cmd.center.id].openPopup();
        }
      } else if (cmd.type === 'RECENTER_USER') {
        map.flyTo([STATE.userLat, STATE.userLng], 16, { duration: 0.6 });
        if (userMarker) userMarker.openPopup();
      } else if (cmd.type === 'FIT_ALL') {
        fitAllBounds();
      }
    }

    window.addEventListener('message', (e) => {
      try {
        const data = typeof e.data === 'string' ? JSON.parse(e.data) : e.data;
        handleCommand(data);
      } catch (err) {}
    });
    document.addEventListener('message', (e) => {
      try {
        const data = typeof e.data === 'string' ? JSON.parse(e.data) : e.data;
        handleCommand(data);
      } catch (err) {}
    });
  </script>
</body>
</html>`;
}
