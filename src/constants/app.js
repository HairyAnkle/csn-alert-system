import { Platform } from 'react-native';

export const EMERGENCY_NUMBER = '911'; // Philippine national emergency hotline
export const CSN_HOTLINE = '(02) 8820-7783'; // CSN Center Parañaque (CSWDD/CSWDO & PDAO) direct hotline
export const CSN_SUPPORT_EMAIL = 'csn.support@paranaque.gov.ph';
// Default fallback coordinates near Col. E. de Leon St., Brgy. Sto. Niño, Parañaque City
export const DEFAULT_PARANAQUE_COORDS = { latitude: 14.4997, longitude: 120.9949 };
export const HOLD_TO_CONFIRM_MS = 1000; // 1-second long-press per Section 1 HCI specification
export const POLL_INTERVAL_MS = 5000;
export const USE_NATIVE_DRIVER = Platform.OS !== 'web';
