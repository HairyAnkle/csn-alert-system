import { Dimensions } from 'react-native';

/* ============================================================================
 * DESIGN TOKENS (Section 1 HCI Standards & WCAG 2.1 AA Compliance)
 * ========================================================================== */

export const LIGHT_COLORS = {
  brand: '#005B96',        // Deep Medical Blue (Section 1 Spec)
  brandDark: '#00426E',    // Deep Medical Blue Dark
  brandLight: '#E0F2FE',   // Soft Medical Sky Light
  sky: '#0284C7',          // Accent Blue
  critical: '#E11D48',     // High-Contrast Crimson Red (#E11D48 / #D9534F)
  criticalDark: '#BE123C', // Crimson Dark
  criticalLight: '#FFE4E6',// Rose 100
  amber: '#F59E0B',        // Warm Amber Warning / Status
  amberDark: '#B45309',    // Amber 700
  amberLight: '#FEF3C7',   // Amber 100
  success: '#10B981',      // Emerald Green Success / Active
  successDark: '#059669',  // Emerald 600
  successLight: '#D1FAE5', // Emerald 100
  bg: '#F8FAFC',           // Soft Off-White / Slate (#F8FAFC)
  card: '#FFFFFF',
  border: '#E2E8F0',       // Slate 200
  text: '#1F2937',         // Dark Charcoal (#1F2937) WCAG 2.1 AA
  textSec: '#475569',      // Slate 600
  muted: '#94A3B8',        // Slate 400
  white: '#FFFFFF',
};

export const DARK_COLORS = {
  brand: '#38BDF8',        // Sky 400 for high dark contrast
  brandDark: '#0284C7',
  brandLight: '#0C4A6E',   // Sky 900
  sky: '#38BDF8',
  critical: '#FB7185',     // Rose 400
  criticalDark: '#E11D48',
  criticalLight: '#4C0519',
  amber: '#FBBF24',        // Amber 400
  amberDark: '#D97706',
  amberLight: '#451A03',
  success: '#34D399',      // Emerald 400
  successDark: '#059669',
  successLight: '#064E3B',
  bg: '#0F172A',           // Slate 900
  card: '#1E293B',         // Slate 800
  border: '#334155',       // Slate 700
  text: '#F8FAFC',         // Slate 50
  textSec: '#94A3B8',      // Slate 400
  muted: '#64748B',        // Slate 500
  white: '#FFFFFF',
};

export const C = LIGHT_COLORS;

export function getThemeColors(theme = 'light') {
  return theme === 'dark' ? DARK_COLORS : LIGHT_COLORS;
}
export const MIN_TOUCH = 48; // Minimum 48x48dp touch target per Section 1 HCI Spec
const { width: SCREEN_W } = Dimensions.get('window');
export const SOS_SIZE = Math.min(SCREEN_W * 0.52, 210);
