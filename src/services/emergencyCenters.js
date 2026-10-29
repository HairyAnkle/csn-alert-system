import { NEARBY_EMERGENCY_CENTERS } from '../constants/data';
import { calcDistanceKm } from '../utils/geo';

/**
 * Queries the live OpenStreetMap Overpass API for nearby emergency facilities
 * (hospitals, clinics, fire stations, police/rescue) around (lat, lng) within 4.5 km,
 * and merges them with the official Parañaque CSN & DRRMO hubs.
 */
export async function fetchNearbyOsmEmergencyCenters(lat, lng) {
  const radiusMeters = 4500;
  const overpassQuery = `[out:json][timeout:12];(node["amenity"~"hospital|clinic|fire_station|police"](around:${radiusMeters},${lat},${lng});way["amenity"~"hospital|clinic|fire_station|police"](around:${radiusMeters},${lat},${lng}););out center 18;`;
  const endpoints = [
    `https://overpass-api.de/api/interpreter?data=${encodeURIComponent(overpassQuery)}`,
    `https://overpass.kumi.systems/api/interpreter?data=${encodeURIComponent(overpassQuery)}`,
  ];

  let elements = [];
  for (const url of endpoints) {
    try {
      const controller = typeof AbortController !== 'undefined' ? new AbortController() : null;
      const timer = controller ? setTimeout(() => controller.abort(), 8000) : null;
      const res = await fetch(url, controller ? { signal: controller.signal } : undefined);
      if (timer) clearTimeout(timer);
      if (res.ok) {
        const json = await res.json();
        if (json && Array.isArray(json.elements)) {
          elements = json.elements;
          break;
        }
      }
    } catch {
      // Try next Overpass mirror
    }
  }

  const osmCenters = [];
  const seenNames = new Set(NEARBY_EMERGENCY_CENTERS.map((c) => c.name.toLowerCase()));

  elements.forEach((el) => {
    const tags = el.tags || {};
    const name = (tags.name || tags['name:en'] || '').trim();
    if (!name) return;
    const key = name.toLowerCase();
    if (seenNames.has(key) || key.includes('ospital ng parañaque')) return;

    const cLat = el.lat != null ? el.lat : el.center && el.center.lat;
    const cLng = el.lon != null ? el.lon : el.center && el.center.lon;
    if (cLat == null || cLng == null) return;

    seenNames.add(key);
    const amenity = tags.amenity || 'hospital';
    let typeLabel = 'Emergency Medical Facility';
    let color = '#10B981';
    let icon = 'medical';

    if (amenity === 'fire_station') {
      typeLabel = 'Fire & Rescue Station (OSM)';
      color = '#E11D48';
      icon = 'flame';
    } else if (amenity === 'police') {
      typeLabel = 'Police & Emergency Assistance (OSM)';
      color = '#005B96';
      icon = 'shield-checkmark';
    } else if (amenity === 'clinic') {
      typeLabel = 'Medical Clinic / Urgent Care (OSM)';
      color = '#0284C7';
      icon = 'medkit';
    } else {
      typeLabel = 'Emergency Hospital (OSM)';
      color = '#10B981';
      icon = 'medical';
    }

    const rawPhone = tags.phone || tags['contact:phone'] || tags.emergency_telephone_code || '911';
    const street = [tags['addr:housenumber'], tags['addr:street'], tags['addr:suburb'] || tags['addr:city'] || 'Parañaque']
      .filter(Boolean)
      .join(' ');

    osmCenters.push({
      id: `osm-${el.type}-${el.id}`,
      name,
      type: typeLabel,
      role: typeLabel,
      latitude: Number(cLat),
      longitude: Number(cLng),
      lat: Number(cLat),
      lng: Number(cLng),
      phone: String(rawPhone).split(';')[0].trim(),
      address: street || 'Verified via OpenStreetMap GIS',
      color,
      icon,
      isOsmLive: true,
    });
  });

  const combined = [...NEARBY_EMERGENCY_CENTERS, ...osmCenters];
  combined.sort((a, b) => {
    // Keep Parañaque CSN Center first, then sort by distance
    if (a.id === 'csn-center') return -1;
    if (b.id === 'csn-center') return 1;
    const da = calcDistanceKm(lat, lng, a.latitude, a.longitude) ?? 999;
    const db = calcDistanceKm(lat, lng, b.latitude, b.longitude) ?? 999;
    return da - db;
  });

  return combined.slice(0, 10);
}
