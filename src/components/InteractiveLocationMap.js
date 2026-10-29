import { Ionicons } from '@expo/vector-icons';
import { useCallback, useEffect, useMemo, useRef } from 'react';
import { Platform, Pressable, Text, View } from 'react-native';

import { DEFAULT_PARANAQUE_COORDS } from '../constants/app';
import { NEARBY_EMERGENCY_CENTERS } from '../constants/data';
import { buildLeafletMapHtml } from '../services/leafletMapHtml';
import { C } from '../theme/colors';
import { styles } from '../theme/styles';
import { openExternalMap } from '../utils/geo';

let NativeWebView = null;
if (Platform.OS !== 'web') {
  try {
    NativeWebView = require('react-native-webview').WebView;
  } catch {
    NativeWebView = null;
  }
}

/**
 * Interactive OpenStreetMap + Leaflet Map Card (Table 1.6)
 * Works on Web (via <iframe>) and Native iOS/Android (via <WebView>).
 */
export function InteractiveLocationMap({
  coords,
  isStreaming,
  centers = NEARBY_EMERGENCY_CENTERS,
  selectedCenter,
  onSelectCenter,
  childName,
}) {
  const lat = coords ? coords.latitude : DEFAULT_PARANAQUE_COORDS.latitude;
  const lng = coords ? coords.longitude : DEFAULT_PARANAQUE_COORDS.longitude;
  const iframeRef = useRef(null);
  const webViewRef = useRef(null);

  const selectedId = selectedCenter ? selectedCenter.id : centers[0]?.id;

  // Send command to Leaflet map when selectedCenter changes
  const sendCommandToLeaflet = useCallback((cmdObj) => {
    const str = JSON.stringify(cmdObj);
    if (Platform.OS === 'web') {
      if (iframeRef.current && iframeRef.current.contentWindow) {
        iframeRef.current.contentWindow.postMessage(str, '*');
      }
    } else if (webViewRef.current && typeof webViewRef.current.postMessage === 'function') {
      webViewRef.current.postMessage(str);
    }
  }, []);

  useEffect(() => {
    if (selectedCenter) {
      sendCommandToLeaflet({ type: 'FOCUS_CENTER', center: selectedCenter });
    }
  }, [selectedCenter, sendCommandToLeaflet]);

  // Listen for marker clicks from Web iframe
  useEffect(() => {
    if (Platform.OS !== 'web' || typeof window === 'undefined') return undefined;
    const onMsg = (e) => {
      try {
        const data = typeof e.data === 'string' ? JSON.parse(e.data) : e.data;
        if (data && data.type === 'SELECT_CENTER' && data.id && onSelectCenter) {
          const found = centers.find((c) => c.id === data.id);
          if (found) onSelectCenter(found);
        }
      } catch {}
    };
    window.addEventListener('message', onMsg);
    return () => window.removeEventListener('message', onMsg);
  }, [centers, onSelectCenter]);

  const latKey = lat.toFixed(4);
  const lngKey = lng.toFixed(4);
  const centerCount = centers.length;

  const mapHtml = useMemo(
    () =>
      buildLeafletMapHtml({
        userLat: lat,
        userLng: lng,
        isStreaming,
        childName: childName ? `${childName} (You)` : 'Your GPS Position',
        centers,
        selectedId,
      }),
    // Rebuild initial HTML when coordinates, streaming state, or discovered centers list changes
    // eslint-disable-next-line react-hooks/exhaustive-deps
    [latKey, lngKey, isStreaming, childName, centerCount]
  );

  return (
    <View style={styles.mapContainer}>
      {/* Top Status & Map Controls Bar */}
      <View style={styles.leafletToolbar}>
        <View style={[styles.row, { flex: 1 }]}>
          <View style={[styles.gpsDot, { backgroundColor: isStreaming ? C.success : C.muted, marginRight: 6 }]} />
          <Text style={{ color: C.text, fontSize: 12, fontWeight: '800' }} numberOfLines={1}>
            {isStreaming ? 'Live GPS Active' : 'GPS Paused'}
          </Text>
        </View>
        <View style={[styles.row, { gap: 6 }]}>
          <Pressable
            onPress={() => sendCommandToLeaflet({ type: 'RECENTER_USER' })}
            accessibilityRole="button"
            accessibilityLabel="Center map on my GPS position"
            style={styles.leafletToolBtn}
          >
            <Ionicons name="locate" size={14} color={C.brand} style={{ marginRight: 3 }} />
            <Text style={styles.leafletToolBtnText}>My GPS</Text>
          </Pressable>
          <Pressable
            onPress={() => sendCommandToLeaflet({ type: 'FIT_ALL' })}
            accessibilityRole="button"
            accessibilityLabel="Show all nearby emergency centers"
            style={styles.leafletToolBtn}
          >
            <Ionicons name="scan-outline" size={14} color={C.brand} style={{ marginRight: 3 }} />
            <Text style={styles.leafletToolBtnText}>Fit All</Text>
          </Pressable>
        </View>
      </View>

      {/* Interactive Leaflet Map Surface */}
      <View style={styles.mapCanvas}>
        {Platform.OS === 'web' ? (
          <iframe
            ref={iframeRef}
            title="Parañaque CSN OpenStreetMap Leaflet View"
            srcDoc={mapHtml}
            style={{
              width: '100%',
              height: '100%',
              border: 'none',
              display: 'block',
            }}
          />
        ) : NativeWebView ? (
          <NativeWebView
            ref={webViewRef}
            originWhitelist={['*']}
            source={{ html: mapHtml }}
            style={{ flex: 1, width: '100%', height: '100%' }}
            javaScriptEnabled
            domStorageEnabled
            onMessage={(event) => {
              try {
                const data = JSON.parse(event.nativeEvent.data);
                if (data && data.type === 'SELECT_CENTER' && data.id && onSelectCenter) {
                  const found = centers.find((c) => c.id === data.id);
                  if (found) onSelectCenter(found);
                }
              } catch {}
            }}
          />
        ) : (
          <View style={{ alignItems: 'center', justifyContent: 'center', padding: 20 }}>
            <Ionicons name="map" size={36} color={C.brand} />
            <Text style={{ color: C.text, fontWeight: '700', marginTop: 8 }}>
              Map Ready ({lat.toFixed(4)}, {lng.toFixed(4)})
            </Text>
          </View>
        )}
      </View>

      {/* Footer Bar with external map launcher */}
      <View style={styles.osmBar}>
        <Ionicons name="navigate-circle-outline" size={16} color={C.brand} style={{ marginRight: 6 }} />
        <Text style={{ flex: 1, fontSize: 12, color: C.textSec }} numberOfLines={1}>
          Tap any marker on the map or a center card below to view route
        </Text>
        <Pressable
          onPress={() => openExternalMap(lat, lng, childName || 'Guardian Location')}
          accessibilityRole="button"
        >
          <Text style={{ color: C.brand, fontSize: 12, fontWeight: '800' }}>Google/Apple Maps ↗</Text>
        </Pressable>
      </View>
    </View>
  );
}
