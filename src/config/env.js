import Constants from 'expo-constants';
import { Platform } from 'react-native';

/* ============================================================================
 * CONFIG & HCI STANDARDS
 * ========================================================================== */

export const IS_DEV = typeof __DEV__ !== 'undefined' ? Boolean(__DEV__) : process.env.NODE_ENV !== 'production';

/**
 * CLOUDFLARE TUNNEL URL:
 * Live public HTTPS tunnel to the Parañaque CSN Web Dashboard & Backend.
 * Allows the mobile app to communicate over the internet (mobile data / any Wi-Fi).
 * Set to null or empty string ('') to fall back to local LAN Wi-Fi (192.168.1.x:8000).
 */
export const CLOUDFLARE_TUNNEL_URL = 'https://signing-sponsored-len-medications.trycloudflare.com';

// Dynamic API base URL resolution:
// 1. Cloudflare Tunnel (if active, enables global access over LTE/5G & remote Wi-Fi)
// 2. Web browser on localhost / LAN
// 3. Native Expo Go debugger host IP
// 4. Fallback static LAN IP (192.168.1.24:8000)
export const getApiBaseUrl = () => {
  if (CLOUDFLARE_TUNNEL_URL && CLOUDFLARE_TUNNEL_URL.trim().length > 0) {
    const cleanUrl = CLOUDFLARE_TUNNEL_URL.trim().replace(/\/+$/, '');
    return cleanUrl.endsWith('/api') ? cleanUrl : `${cleanUrl}/api`;
  }

  if (Platform.OS === 'web' && typeof window !== 'undefined' && window.location?.hostname) {
    const host = window.location.hostname;
    if (host === 'csn-alert-system.test') {
      return 'http://csn-alert-system.test/api';
    }
    return `http://${host}:8000/api`;
  }

  const debuggerHost = Constants?.expoConfig?.hostUri || Constants?.manifest2?.extra?.expoGo?.debuggerHost;
  if (debuggerHost) {
    const hostIp = debuggerHost.split(':')[0];
    if (hostIp) {
      return `http://${hostIp}:8000/api`;
    }
  }

  return 'http://192.168.1.24:8000/api';
};

export const API_BASE_URL = getApiBaseUrl();

export const isFirebaseOAuthDomain = () => {
  if (Platform.OS !== 'web' || typeof window === 'undefined') return false;
  const host = window.location?.hostname || '';
  return host === 'localhost' || host === '127.0.0.1' || host.endsWith('.firebaseapp.com') || host.endsWith('.web.app');
};

// Development only: lets you open the Dashboard UI without a backend.
export const DEV_BYPASS_LOGIN = false;
