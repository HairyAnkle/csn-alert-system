/**
 * Parañaque CSN Emergency Alert System — Guardian Mobile App (Expo / React Native)
 * Install (inside your Expo project):
 *   npx expo install expo-location @expo/vector-icons
 */
import { Ionicons } from '@expo/vector-icons';
import Constants from 'expo-constants';
import * as Location from 'expo-location';
import { useCallback, useEffect, useRef, useState } from 'react';
import {
  ActivityIndicator,
  Alert,
  Animated,
  Dimensions,
  Easing,
  KeyboardAvoidingView,
  Linking,
  Modal,
  Platform,
  Pressable,
  SafeAreaView,
  ScrollView,
  StatusBar,
  StyleSheet,
  Text,
  TextInput,
  View,
} from 'react-native';
import {
  firebaseLoginWithEmail,
  firebaseRegisterWithEmail,
  firebaseResetPassword,
  firebaseGoogleSignIn,
  firebaseLogout,
  formatFirebaseAuthError,
} from './src/firebase';

/* ============================================================================
 * CONFIG
 * ========================================================================== */

// Dynamic API base URL resolution:
// - Web on iPhone / local Wi-Fi: automatically uses http://<lan-ip>:8000/api
// - Web on PC localhost: uses http://localhost:8000/api
// - Web under csn-alert-system.test: uses http://csn-alert-system.test/api
// - Native device / Expo Go: uses Expo debugger host IP or LAN fallback
const getApiBaseUrl = () => {
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

  return 'http://192.168.1.26:8000/api';
};

const API_BASE_URL = getApiBaseUrl();

function normalizePhoneNumber(raw) {
  if (!raw) return '';
  let digits = raw.replace(/[^0-9+]/g, '');
  if (digits.startsWith('+63')) {
    digits = '0' + digits.slice(3);
  } else if (digits.startsWith('63') && digits.length === 12) {
    digits = '0' + digits.slice(2);
  } else if (digits.length === 10 && digits.startsWith('9')) {
    digits = '0' + digits;
  }
  return digits;
}

const isFirebaseOAuthDomain = () => {
  if (Platform.OS !== 'web' || typeof window === 'undefined') return false;
  const host = window.location?.hostname || '';
  return host === 'localhost' || host === '127.0.0.1' || host.endsWith('.firebaseapp.com') || host.endsWith('.web.app');
};

// Development only: lets you open the Dashboard UI without a backend.
const DEV_BYPASS_LOGIN = false;

const EMERGENCY_NUMBER = '911'; // Philippine national emergency hotline
const HOLD_TO_CONFIRM_MS = 1500;
const POLL_INTERVAL_MS = 5000;
const USE_NATIVE_DRIVER = Platform.OS !== 'web';

function callEmergencyNumber(num = EMERGENCY_NUMBER) {
  const url = `tel:${num}`;
  if (Platform.OS === 'web' && typeof window !== 'undefined') {
    window.location.href = url;
    return;
  }
  if (Linking && typeof Linking.openURL === 'function') {
    Linking.openURL(url).catch(() => {
      Alert.alert('Unable to Place Call', `Please dial ${num} directly.`);
    });
  }
}

function openAppSettings() {
  if (Platform.OS !== 'web' && Linking && typeof Linking.openSettings === 'function') {
    Linking.openSettings().catch(() => {});
  } else {
    Alert.alert(
      'Location Access',
      Platform.OS === 'web'
        ? 'Location access in web browsers requires HTTPS or enabling location permission in Safari settings. Note: In the native Expo Go mobile app, GPS accesses hardware directly.'
        : 'Please enable Location Services in your device Settings.'
    );
  }
}

/* Persistent storage helper for token across app launches */
let SecureStore = null;
try {
  SecureStore = require('expo-secure-store');
} catch (e) {
  // Graceful fallback if expo-secure-store native module is unlinked
}

const STORAGE_KEY_TOKEN = 'csn_guardian_token';

async function saveStoredToken(t) {
  try {
    if (SecureStore && SecureStore.setItemAsync) {
      await SecureStore.setItemAsync(STORAGE_KEY_TOKEN, t);
      return;
    }
  } catch (e) {}
  try {
    if (typeof localStorage !== 'undefined') {
      localStorage.setItem(STORAGE_KEY_TOKEN, t);
    }
  } catch (e) {}
}

async function getStoredToken() {
  try {
    if (SecureStore && SecureStore.getItemAsync) {
      const val = await SecureStore.getItemAsync(STORAGE_KEY_TOKEN);
      if (val) return val;
    }
  } catch (e) {}
  try {
    if (typeof localStorage !== 'undefined') {
      return localStorage.getItem(STORAGE_KEY_TOKEN);
    }
  } catch (e) {}
  return null;
}

async function removeStoredToken() {
  try {
    if (SecureStore && SecureStore.deleteItemAsync) {
      await SecureStore.deleteItemAsync(STORAGE_KEY_TOKEN);
      return;
    }
  } catch (e) {}
  try {
    if (typeof localStorage !== 'undefined') {
      localStorage.removeItem(STORAGE_KEY_TOKEN);
    }
  } catch (e) {}
}

/* ============================================================================
 * API CONTRACT (build these in Laravel, all JSON, Bearer-token auth)
 *
 *  POST   /auth/login               {email, password}        -> {token, guardian:{name}, child}
 *  POST   /auth/register            {name, email, password}  -> {token, guardian:{name}, child}
 *  POST   /auth/forgot-password     {email}                  -> {message}
 *  GET    /me                                                -> {guardian:{name}, child}
 *  PUT    /child                    {medical profile fields} -> {child}
 *  POST   /emergencies              {type, lat, lng}         -> {emergency}
 *  GET    /emergencies/{id}                                  -> {emergency}
 *  PATCH  /emergencies/{id}/location {lat, lng}              -> {emergency}
 *  POST   /emergencies/{id}/cancel                           -> {emergency}
 *  GET    /emergencies                                       -> {emergencies:[...]}
 *  GET    /announcements                                     -> {announcements:[...]}
 *
 *  child:      { name, age, disability, allergies, medications,
 *                communication_notes, guardian_phone,
 *                emergency_contact_name, emergency_contact_phone }
 *  emergency:  { id, type, status: 'pending'|'responding'|'resolved'|'cancelled',
 *                responder: null | {name, unit, eta}, reported_at }
 *  announcement: { id, title, body, created_at }
 * ========================================================================== */

async function api(path, { method = 'GET', body, token } = {}) {
  if (!API_BASE_URL) {
    throw new Error('Server address is not set. Set API_BASE_URL at the top of App.js.');
  }
  let res;
  try {
    res = await fetch(`${API_BASE_URL}${path}`, {
      method,
      headers: {
        'Content-Type': 'application/json',
        Accept: 'application/json',
        ...(token ? { Authorization: `Bearer ${token}` } : {}),
      },
      body: body ? JSON.stringify(body) : undefined,
    });
  } catch (e) {
    console.warn(`[API] Failed to fetch ${API_BASE_URL}${path}:`, e);
    throw new Error('Cannot reach the server. Check your internet connection.');
  }
  let data = null;
  try {
    data = await res.json();
  } catch (e) {
    /* empty or non-JSON body */
  }
  if (!res.ok) {
    const err = new Error((data && data.message) || `Request failed (${res.status})`);
    err.errors = data && data.errors;
    throw err;
  }
  return data;
}

/* ============================================================================
 * DESIGN TOKENS
 * ========================================================================== */

const C = {
  brand: '#0284C7',        // Sky 600
  brandDark: '#0369A1',    // Sky 700
  brandLight: '#E0F2FE',   // Sky 100
  sky: '#0EA5E9',          // Sky 500
  critical: '#E11D48',     // Rose 600
  criticalDark: '#BE123C', // Rose 700
  criticalLight: '#FFE4E6',// Rose 100
  amber: '#F59E0B',        // Amber 500
  amberDark: '#B45309',    // Amber 700
  amberLight: '#FEF3C7',   // Amber 100
  success: '#10B981',      // Emerald 500
  successDark: '#059669',  // Emerald 600
  successLight: '#D1FAE5', // Emerald 100
  bg: '#F8FAFC',           // Slate 50
  card: '#FFFFFF',
  border: '#E2E8F0',       // Slate 200
  text: '#0F172A',         // Slate 900
  textSec: '#475569',      // Slate 600
  muted: '#94A3B8',        // Slate 400
  white: '#FFFFFF',
};
const MIN_TOUCH = 48;
const { width: SCREEN_W } = Dimensions.get('window');
const SOS_SIZE = Math.min(SCREEN_W * 0.52, 220);

const EMERGENCY_TYPES = [
  { key: 'medical_crisis', label: 'Medical', icon: 'medkit' },
  { key: 'seizure_sensory', label: 'Seizure / Overload', icon: 'pulse' },
  { key: 'wandering', label: 'Missing', icon: 'walk' },
  { key: 'fire', label: 'Fire', icon: 'flame' },
];

const DISABILITY_CHIPS = [
  'Down Syndrome',
  'Cerebral Palsy',
  'Hearing Impairment',
  'Visual Impairment',
  'Others',
];

const STATUS_STYLE = {
  pending:    { label: 'Pending',    bg: '#FEF3C7', fg: '#92400E' },
  responding: { label: 'Responding', bg: '#DBEAFE', fg: '#1E40AF' },
  resolved:   { label: 'Resolved',   bg: '#D1FAE5', fg: '#065F46' },
  cancelled:  { label: 'Cancelled',  bg: '#E5E7EB', fg: '#374151' },
};

const formatDateTime = (iso) => {
  if (!iso) return '';
  const d = new Date(iso);
  if (isNaN(d.getTime())) return '';
  return d.toLocaleString('en-PH', {
    timeZone: 'Asia/Manila',
    month: 'short',
    day: 'numeric',
    hour: 'numeric',
    minute: '2-digit',
  });
};

const isValidEmail = (v) => /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(v.trim());

/* ============================================================================
 * SMALL SHARED COMPONENTS
 * ========================================================================== */

function PrimaryButton({ title, onPress, loading, disabled, tone = 'brand', icon, style }) {
  const bg =
    tone === 'brand'    ? C.brand :
    tone === 'critical' ? C.critical :
    tone === 'success'  ? C.success : C.white;
  const isOutline = tone === 'outline';
  const isGhost   = tone === 'ghost';
  return (
    <Pressable
      accessibilityRole="button"
      accessibilityLabel={title}
      accessibilityState={{ disabled: !!(disabled || loading) }}
      disabled={disabled || loading}
      onPress={onPress}
      style={({ pressed }) => [
        styles.btn,
        { backgroundColor: bg },
        isOutline && { borderWidth: 1.5, borderColor: C.brand, backgroundColor: 'transparent' },
        isGhost   && { borderWidth: 1.5, borderColor: C.border, backgroundColor: 'transparent' },
        (disabled || loading) && { opacity: 0.55 },
        pressed && { opacity: 0.82 },
        style,
      ]}
    >
      {loading ? (
        <ActivityIndicator color={isOutline || isGhost ? C.brand : C.white} />
      ) : (
        <View style={styles.row}>
          {icon ? (
            <Ionicons
              name={icon}
              size={20}
              color={isOutline || isGhost ? C.brand : C.white}
              style={{ marginRight: 8 }}
            />
          ) : null}
          <Text
            style={[
              styles.btnText,
              (isOutline || isGhost) && { color: C.brand },
            ]}
          >
            {title}
          </Text>
        </View>
      )}
    </Pressable>
  );
}

/** Styled form field with label, optional error, and optional left icon */
function Field({ label, value, onChangeText, error, multiline, leftIcon, rightNode, ...rest }) {
  const safeValue = value == null ? '' : String(value);
  return (
    <View style={{ marginBottom: 16 }}>
      {!!label && <Text style={styles.label}>{label}</Text>}
      <View style={[styles.inputWrap, !!error && { borderColor: C.critical }]}>
        {leftIcon ? (
          <Ionicons name={leftIcon} size={20} color={C.muted} style={{ marginRight: 10 }} />
        ) : null}
        <TextInput
          value={safeValue}
          onChangeText={onChangeText}
          multiline={multiline}
          accessibilityLabel={label}
          placeholderTextColor={C.muted}
          style={[
            styles.inputInner,
            multiline && { minHeight: 96, textAlignVertical: 'top', paddingTop: 12 },
          ]}
          {...rest}
        />
        {rightNode || null}
      </View>
      {!!error && <Text style={styles.errorText}>{error}</Text>}
    </View>
  );
}

function StatusBadge({ status }) {
  const s = STATUS_STYLE[status] || { label: status || 'Unknown', bg: '#E5E7EB', fg: '#374151' };
  return (
    <View style={[styles.badge, { backgroundColor: s.bg }]}>
      <Text style={[styles.badgeText, { color: s.fg }]}>{s.label}</Text>
    </View>
  );
}

function Toast({ toast }) {
  const opacity = useRef(new Animated.Value(0)).current;
  useEffect(() => {
    if (!toast) return;
    opacity.setValue(0);
    Animated.sequence([
      Animated.timing(opacity, { toValue: 1, duration: 200, useNativeDriver: USE_NATIVE_DRIVER }),
      Animated.delay(2400),
      Animated.timing(opacity, { toValue: 0, duration: 300, useNativeDriver: USE_NATIVE_DRIVER }),
    ]).start();
  }, [toast]);
  if (!toast) return null;
  const isErr = toast.tone === 'error';
  return (
    <Animated.View
      pointerEvents="none"
      accessibilityLiveRegion="polite"
      style={[
        styles.toast,
        { opacity, backgroundColor: isErr ? C.criticalDark : C.successDark },
      ]}
    >
      <Ionicons name={isErr ? 'alert-circle' : 'checkmark-circle'} size={20} color={C.white} />
      <Text style={styles.toastText}>{toast.message}</Text>
    </Animated.View>
  );
}

/** Bottom Tab Bar — visible on dashboard/medical/history/support */
const TAB_ITEMS = [
  { key: 'dashboard', label: 'Home',    icon: 'home',         iconActive: 'home' },
  { key: 'medical',   label: 'Medical', icon: 'heart-outline', iconActive: 'heart' },
  { key: 'history',   label: 'History', icon: 'time-outline',  iconActive: 'time' },
  { key: 'support',   label: 'Help',    icon: 'help-circle-outline', iconActive: 'help-circle' },
];

function BottomTabBar({ current, onPress, unread }) {
  return (
    <View style={styles.tabBar}>
      {TAB_ITEMS.map((t) => {
        const active = current === t.key;
        return (
          <Pressable
            key={t.key}
            accessibilityRole="tab"
            accessibilityLabel={t.label}
            accessibilityState={{ selected: active }}
            onPress={() => onPress(t.key)}
            style={({ pressed }) => [styles.tabBarItem, pressed && { opacity: 0.7 }]}
          >
            <View style={{ position: 'relative' }}>
              <Ionicons
                name={active ? t.iconActive : t.icon}
                size={24}
                color={active ? C.brand : C.muted}
              />
              {t.key === 'history' && unread > 0 && (
                <View style={styles.tabBadge}>
                  <Text style={styles.tabBadgeText}>{unread > 9 ? '9+' : unread}</Text>
                </View>
              )}
            </View>
            <Text style={[styles.tabBarLabel, active && { color: C.brand }]}>{t.label}</Text>
          </Pressable>
        );
      })}
    </View>
  );
}

/** Section card wrapper */
function SectionCard({ children, style }) {
  return <View style={[styles.sectionCard, style]}>{children}</View>;
}

/* ============================================================================
 * SCREEN 1a — WELCOME
 * ========================================================================== */

function WelcomeScreen({ go }) {
  const fadeIn = useRef(new Animated.Value(0)).current;
  const slideY = useRef(new Animated.Value(30)).current;

  useEffect(() => {
    Animated.parallel([
      Animated.timing(fadeIn, { toValue: 1, duration: 600, useNativeDriver: USE_NATIVE_DRIVER }),
      Animated.timing(slideY, { toValue: 0, duration: 600, easing: Easing.out(Easing.quad), useNativeDriver: USE_NATIVE_DRIVER }),
    ]).start();
  }, []);

  return (
    <SafeAreaView style={[styles.flex, { backgroundColor: C.brand }]}>
      <StatusBar barStyle="light-content" backgroundColor={C.brand} />
      <View style={styles.welcomeBody}>
        <Animated.View style={{ alignItems: 'center', opacity: fadeIn, transform: [{ translateY: slideY }] }}>
          {/* Logo */}
          <View style={styles.logoCircle}>
            <Ionicons name="shield-checkmark" size={56} color={C.brand} />
          </View>

          {/* Branding */}
          <Text style={styles.welcomeTitle}>Parañaque CSN</Text>
          <Text style={styles.welcomeSubtitle}>Emergency Alert System</Text>

          {/* Divider accent */}
          <View style={styles.welcomeDivider} />

          <Text style={styles.welcomeTagline}>
            Fast, dignified help for children with{'\n'}special needs — one hold away.
          </Text>
        </Animated.View>
      </View>

      <Animated.View style={[styles.welcomeActions, { opacity: fadeIn }]}>
        {/* Sign In */}
        <Pressable
          accessibilityRole="button"
          accessibilityLabel="Sign In"
          onPress={() => go('login')}
          style={({ pressed }) => [styles.welcomeLoginBtn, pressed && { opacity: 0.88 }]}
        >
          <Text style={styles.welcomeLoginText}>Sign In</Text>
        </Pressable>

        {/* Sign Up */}
        <Pressable
          accessibilityRole="button"
          accessibilityLabel="Sign Up"
          onPress={() => go('signup')}
          style={({ pressed }) => [styles.welcomeSignupBtn, pressed && { opacity: 0.75 }]}
        >
          <Text style={styles.welcomeSignupText}>Create Account</Text>
        </Pressable>

        <Text style={styles.welcomeFooter}>
          Parañaque City Social Welfare — CSN Center
        </Text>
      </Animated.View>
    </SafeAreaView>
  );
}

/* ============================================================================
 * SCREEN 1b — LOGIN / SIGN UP (shared layout, `mode` prop switches)
 * ========================================================================== */

function AuthScreen({ mode, go, onAuthed, showToast }) {
  const isSignup = mode === 'signup';
  const [name, setName]         = useState('');
  const [email, setEmail]       = useState('');
  const [password, setPassword] = useState('');
  const [showPass, setShowPass] = useState(false);
  const [errors, setErrors]     = useState({});
  const [loading, setLoading]   = useState(false);
  const [formError, setFormError] = useState('');
  const [googleModalVisible, setGoogleModalVisible] = useState(false);
  const [googleInputEmail, setGoogleInputEmail] = useState('');
  const [googleInputName, setGoogleInputName] = useState('');
  const [googleError, setGoogleError] = useState('');
  const [phoneModalVisible, setPhoneModalVisible] = useState(false);
  const [phoneInput, setPhoneInput] = useState('');
  const [phoneNameInput, setPhoneNameInput] = useState('');
  const [phoneError, setPhoneError] = useState('');

  const validate = () => {
    const e = {};
    if (isSignup && !name.trim()) e.name = 'Please enter your full name.';
    if (!email.trim()) e.email = 'Please enter your email.';
    else if (!isValidEmail(email)) e.email = 'Enter a valid email address.';
    if (!password) e.password = 'Please enter your password.';
    else if (isSignup && password.length < 8) e.password = 'Use at least 8 characters.';
    setErrors(e);
    return Object.keys(e).length === 0;
  };

  const submit = async () => {
    setFormError('');
    if (!validate()) return;
    setLoading(true);

    try {
      let firebaseToken = null;

      // 1. Authenticate with Firebase Auth SDK (csn-paranaque)
      try {
        if (isSignup) {
          const fbRes = await firebaseRegisterWithEmail(name.trim(), email.trim(), password);
          firebaseToken = fbRes.idToken;
        } else {
          const fbRes = await firebaseLoginWithEmail(email.trim(), password);
          firebaseToken = fbRes.idToken;
        }
      } catch (fbErr) {
        console.warn('Firebase Auth notice:', fbErr?.code, fbErr?.message);
        if (isSignup && fbErr.code === 'auth/email-already-in-use') {
          throw new Error(formatFirebaseAuthError(fbErr));
        } else if (!isSignup && (fbErr.code === 'auth/wrong-password' || fbErr.code === 'auth/invalid-credential')) {
          throw new Error(formatFirebaseAuthError(fbErr));
        } else if (fbErr.code === 'auth/weak-password' || fbErr.code === 'auth/invalid-email') {
          throw new Error(formatFirebaseAuthError(fbErr));
        }
        // If user already existed prior to Firebase or network is blocked, fallback will run below
      }

      // 2. Sync / exchange with Laravel Gateway
      let data;
      if (firebaseToken) {
        data = await api('/auth/firebase', {
          method: 'POST',
          body: {
            id_token: firebaseToken,
            name: isSignup ? name.trim() : undefined,
            role: 'guardian',
          },
        });
      } else {
        data = await api(isSignup ? '/auth/register' : '/auth/login', {
          method: 'POST',
          body: isSignup
            ? { name: name.trim(), email: email.trim(), password }
            : { email: email.trim(), password },
        });
      }

      showToast(isSignup ? 'Account created successfully!' : 'Signed in successfully!');
      onAuthed(data);
    } catch (err) {
      if (err.errors) {
        const mapped = {};
        Object.keys(err.errors).forEach((k) => {
          mapped[k] = Array.isArray(err.errors[k]) ? err.errors[k][0] : String(err.errors[k]);
        });
        setErrors(mapped);
      }
      setFormError(err.message || 'Authentication failed.');
    } finally {
      setLoading(false);
    }
  };

  const forgot = async () => {
    if (!email.trim() || !isValidEmail(email)) {
      setErrors({ email: 'Enter your email above first, then tap "Forgot Password?".' });
      return;
    }
    try {
      try {
        await firebaseResetPassword(email.trim());
        showToast('Password reset link sent via Firebase! Check your email.');
        return;
      } catch (fbErr) {
        // Fallback to Laravel reset link
      }
      const data = await api('/auth/forgot-password', { method: 'POST', body: { email: email.trim() } });
      showToast((data && data.message) || 'Password reset link sent. Check your email.');
    } catch (err) {
      showToast(err.message, 'error');
    }
  };

  const performGoogleAuth = async (targetEmail, targetName, idToken = null) => {
    const cleanEmail = (targetEmail || '').trim();
    if (!idToken && (!cleanEmail || !isValidEmail(cleanEmail))) {
      setGoogleError('Please enter a valid Google email address.');
      return;
    }
    setLoading(true);
    setGoogleError('');
    setFormError('');
    try {
      let data;
      if (idToken) {
        data = await api('/auth/firebase', {
          method: 'POST',
          body: {
            id_token: idToken,
            name: (targetName || '').trim() || 'Google User',
            role: 'guardian',
          },
        });
      } else {
        data = await api('/auth/google', {
          method: 'POST',
          body: {
            email: cleanEmail,
            name: (targetName || '').trim() || undefined,
            role: 'guardian',
          },
        });
      }
      setGoogleModalVisible(false);
      showToast('Signed in successfully with Google!');
      onAuthed(data);
    } catch (err) {
      setGoogleError(err.message || 'Google Sign-In failed.');
      setFormError(err.message || 'Google Sign-In failed.');
    } finally {
      setLoading(false);
    }
  };

  const google = () => {
    setGoogleError('');
    setFormError('');

    if (isFirebaseOAuthDomain()) {
      (async () => {
        try {
          setLoading(true);
          const res = await firebaseGoogleSignIn();
          if (res && res.idToken) {
            await performGoogleAuth(res.user?.email, res.user?.displayName, res.idToken);
            return;
          }
        } catch (fbErr) {
          console.warn('[Google Auth] Popup skipped or blocked:', fbErr?.code || fbErr?.message);
        } finally {
          setLoading(false);
        }
        setGoogleInputEmail(email.trim() && isValidEmail(email) ? email.trim() : '');
        setGoogleInputName(name.trim() || '');
        setGoogleModalVisible(true);
      })();
      return;
    }

    setGoogleInputEmail(email.trim() && isValidEmail(email) ? email.trim() : '');
    setGoogleInputName(name.trim() || '');
    setGoogleModalVisible(true);
  };

  const performPhoneAuth = async (phoneNumber, phoneName) => {
    const raw = (phoneNumber || '').trim();
    const cleaned = normalizePhoneNumber(raw);
    if (!cleaned || cleaned.length < 10) {
      setPhoneError('Please enter an 11-digit mobile number (e.g. 09171234567).');
      return;
    }
    setLoading(true);
    setPhoneError('');
    setFormError('');
    try {
      const data = await api('/auth/phone', {
        method: 'POST',
        body: {
          phone: cleaned,
          name: (phoneName || '').trim() || undefined,
          role: 'guardian',
        },
      });
      setPhoneModalVisible(false);
      showToast('Signed in successfully with Phone!');
      onAuthed(data);
    } catch (err) {
      setPhoneError(err.message || 'Phone Sign-In failed.');
      setFormError(err.message || 'Phone Sign-In failed.');
    } finally {
      setLoading(false);
    }
  };

  return (
    <SafeAreaView style={[styles.flex, { backgroundColor: C.bg }]}>
      <StatusBar barStyle="dark-content" backgroundColor={C.bg} />

      {/* Inline back header */}
      <View style={styles.authHeader}>
        <Pressable
          onPress={() => go('welcome')}
          accessibilityRole="button"
          accessibilityLabel="Go back"
          hitSlop={8}
          style={styles.backBtn}
        >
          <Ionicons name="chevron-back" size={26} color={C.text} />
        </Pressable>
        <Text style={styles.authHeaderTitle} accessibilityRole="header">
          {isSignup ? 'Create Account' : 'Welcome back'}
        </Text>
        <View style={{ width: MIN_TOUCH }} />
      </View>

      <KeyboardAvoidingView style={styles.flex} behavior={Platform.OS === 'ios' ? 'padding' : undefined}>
        <ScrollView contentContainerStyle={styles.authScroll} keyboardShouldPersistTaps="handled">

          {/* Subtitle */}
          <Text style={styles.authSubtitle}>
            {isSignup
              ? 'Register to protect your child.'
              : 'Sign in to your guardian account.'}
          </Text>

          {/* Error banner */}
          {!!formError && (
            <View style={styles.errorBanner} accessibilityLiveRegion="assertive">
              <Ionicons name="alert-circle" size={20} color={C.critical} />
              <Text style={styles.errorBannerText}>{formError}</Text>
            </View>
          )}

          {/* Card */}
          <SectionCard style={{ marginTop: 8 }}>
            {isSignup && (
              <Field
                label="Full Name"
                value={name}
                onChangeText={setName}
                error={errors.name}
                autoCapitalize="words"
                leftIcon="person-outline"
                placeholder="Full name"
              />
            )}
            <Field
              label="Email Address"
              value={email}
              onChangeText={setEmail}
              error={errors.email}
              keyboardType="email-address"
              autoCapitalize="none"
              autoCorrect={false}
              leftIcon="mail-outline"
              placeholder="Email address"
            />
            <Field
              label="Password"
              value={password}
              onChangeText={setPassword}
              error={errors.password}
              secureTextEntry={!showPass}
              autoCapitalize="none"
              leftIcon="lock-closed-outline"
              placeholder="••••••••"
              rightNode={
                <Pressable onPress={() => setShowPass((v) => !v)} hitSlop={8}>
                  <Ionicons name={showPass ? 'eye-off-outline' : 'eye-outline'} size={20} color={C.muted} />
                </Pressable>
              }
            />

            {!isSignup && (
              <Pressable onPress={forgot} accessibilityRole="button" style={styles.forgotBtn}>
                <Text style={styles.forgotText}>Forgot Password?</Text>
              </Pressable>
            )}

            <PrimaryButton
              title={isSignup ? 'Create Account' : 'Sign In'}
              onPress={submit}
              loading={loading}
              style={{ marginTop: 4 }}
            />

            <View style={styles.dividerRow}>
              <View style={styles.dividerLine} />
              <Text style={styles.dividerText}>or continue with</Text>
              <View style={styles.dividerLine} />
            </View>

            <View style={{ flexDirection: 'row', gap: 10 }}>
              <Pressable
                onPress={google}
                accessibilityRole="button"
                accessibilityLabel="Continue with Google"
                style={({ pressed }) => [styles.googleBtn, { flex: 1 }, pressed && { opacity: 0.8 }]}
              >
                <Ionicons name="logo-google" size={18} color="#EA4335" style={{ marginRight: 8 }} />
                <Text style={styles.googleBtnText}>Google</Text>
              </Pressable>

              <Pressable
                onPress={() => {
                  setPhoneError('');
                  setPhoneInput('');
                  setPhoneNameInput(name.trim() || '');
                  setPhoneModalVisible(true);
                }}
                accessibilityRole="button"
                accessibilityLabel="Continue with Phone"
                style={({ pressed }) => [styles.googleBtn, { flex: 1 }, pressed && { opacity: 0.8 }]}
              >
                <Ionicons name="call-outline" size={18} color={C.brand} style={{ marginRight: 8 }} />
                <Text style={styles.googleBtnText}>Phone</Text>
              </Pressable>
            </View>
          </SectionCard>

          <Pressable
            onPress={() => go(isSignup ? 'login' : 'signup')}
            style={[styles.switchAuthBtn]}
          >
            <Text style={styles.switchAuthText}>
              {isSignup ? 'Already have an account? ' : "Don't have an account? "}
              <Text style={{ color: C.brand, fontWeight: '700' }}>
                {isSignup ? 'Sign In' : 'Sign Up'}
              </Text>
            </Text>
          </Pressable>
        </ScrollView>
      </KeyboardAvoidingView>

      {/* ── Google Sign-In Sheet / Modal ── */}
      <Modal visible={googleModalVisible} transparent animationType="slide" onRequestClose={() => { setGoogleModalVisible(false); setGoogleError(''); }}>
        <View style={styles.modalBackdrop}>
          <View style={styles.modalSheet}>
            <View style={styles.modalHandle} />

            <View style={[styles.modalIconCircle, { backgroundColor: '#F1F5F9' }]}>
              <Ionicons name="logo-google" size={32} color="#EA4335" />
            </View>
            <Text style={styles.modalTitle}>Sign In with Google</Text>
            <Text style={styles.modalBody}>
              Authenticate securely with your Google Guardian account.
            </Text>

            {!!googleError && (
              <View style={[styles.errorBanner, { width: '100%', marginBottom: 12 }]}>
                <Ionicons name="alert-circle" size={18} color={C.critical} style={{ marginRight: 8, marginTop: 1 }} />
                <Text style={[styles.errorBannerText, { flex: 1 }]}>{googleError}</Text>
              </View>
            )}

            <View style={{ width: '100%', marginVertical: 12 }}>
              <Text style={styles.label}>Google Account Email</Text>
              <TextInput
                style={[styles.input, { marginBottom: 12 }]}
                value={googleInputEmail}
                onChangeText={(t) => { setGoogleInputEmail(t); setGoogleError(''); }}
                placeholder="name@gmail.com"
                keyboardType="email-address"
                autoCapitalize="none"
              />

              <Text style={styles.label}>Full Name</Text>
              <TextInput
                style={styles.input}
                value={googleInputName}
                onChangeText={setGoogleInputName}
                placeholder="Full name"
              />
            </View>

            <View style={{ width: '100%', gap: 10 }}>
              <PrimaryButton
                title="Continue with Google"
                onPress={() => performGoogleAuth(googleInputEmail, googleInputName)}
                loading={loading}
              />
              <PrimaryButton
                title="Cancel"
                tone="ghost"
                onPress={() => { setGoogleModalVisible(false); setGoogleError(''); }}
              />
            </View>
          </View>
        </View>
      </Modal>

      {/* ── Phone Sign-In Sheet / Modal ── */}
      <Modal visible={phoneModalVisible} transparent animationType="slide" onRequestClose={() => { setPhoneModalVisible(false); setPhoneError(''); }}>
        <View style={styles.modalBackdrop}>
          <View style={styles.modalSheet}>
            <View style={styles.modalHandle} />

            <View style={[styles.modalIconCircle, { backgroundColor: '#EFF6FF' }]}>
              <Ionicons name="call" size={30} color={C.brand} />
            </View>
            <Text style={styles.modalTitle}>Sign In with Phone</Text>
            <Text style={styles.modalBody}>
              Enter your mobile phone number to log in or register your guardian account.
            </Text>

            {!!phoneError && (
              <View style={[styles.errorBanner, { width: '100%', marginBottom: 12 }]}>
                <Ionicons name="alert-circle" size={18} color={C.critical} style={{ marginRight: 8, marginTop: 1 }} />
                <Text style={[styles.errorBannerText, { flex: 1 }]}>{phoneError}</Text>
              </View>
            )}

            <View style={{ width: '100%', marginVertical: 12 }}>
              <Text style={styles.label}>Mobile Phone Number</Text>
              <TextInput
                style={[styles.input, { marginBottom: 12 }]}
                value={phoneInput}
                onChangeText={(t) => { setPhoneInput(t); setPhoneError(''); }}
                placeholder="09XXXXXXXXX"
                keyboardType="phone-pad"
                autoCapitalize="none"
              />

              <Text style={styles.label}>Full Name (Guardian)</Text>
              <TextInput
                style={styles.input}
                value={phoneNameInput}
                onChangeText={setPhoneNameInput}
                placeholder="Full name"
              />
            </View>

            <View style={{ width: '100%', gap: 10 }}>
              <PrimaryButton
                title="Continue with Phone"
                onPress={() => performPhoneAuth(phoneInput, phoneNameInput)}
                loading={loading}
              />
              <PrimaryButton
                title="Cancel"
                tone="ghost"
                onPress={() => { setPhoneModalVisible(false); setPhoneError(''); }}
              />
            </View>
          </View>
        </View>
      </Modal>
    </SafeAreaView>
  );
}

/* ============================================================================
 * SCREEN 2 — DASHBOARD
 * ========================================================================== */

/** HoldToConfirm — progress-bar hold button */
function HoldToConfirm({ onConfirm, busy }) {
  const progress = useRef(new Animated.Value(0)).current;
  const anim = useRef(null);

  const start = () => {
    if (busy) return;
    progress.setValue(0);
    anim.current = Animated.timing(progress, {
      toValue: 1,
      duration: HOLD_TO_CONFIRM_MS,
      easing: Easing.linear,
      useNativeDriver: false,
    });
    anim.current.start(({ finished }) => {
      if (finished) onConfirm();
    });
  };
  const cancel = () => {
    if (anim.current) anim.current.stop();
    progress.setValue(0);
  };

  const width = progress.interpolate({ inputRange: [0, 1], outputRange: ['0%', '100%'] });

  return (
    <Pressable
      onPressIn={start}
      onPressOut={cancel}
      disabled={busy}
      accessibilityRole="button"
      accessibilityLabel="Press and hold to send the emergency alert"
      accessibilityHint="Hold for one and a half seconds"
      style={styles.holdBtn}
    >
      <Animated.View style={[styles.holdFill, { width }]} />
      <View style={styles.row}>
        {busy ? (
          <ActivityIndicator color={C.white} />
        ) : (
          <>
            <Ionicons name="hand-left" size={22} color={C.white} style={{ marginRight: 8 }} />
            <Text style={styles.holdText}>HOLD TO SEND ALERT</Text>
          </>
        )}
      </View>
    </Pressable>
  );
}

function DashboardScreen({ guardian, child, gps, go, onTrigger, triggering, triggerError, clearTriggerError, unread }) {
  const [confirmOpen, setConfirmOpen] = useState(false);
  const [type, setType]               = useState('medical_crisis');

  // SOS outer ring pulse animation
  const ring1 = useRef(new Animated.Value(1)).current;
  const ring2 = useRef(new Animated.Value(1)).current;
  const ringOpacity1 = useRef(new Animated.Value(0.6)).current;
  const ringOpacity2 = useRef(new Animated.Value(0.4)).current;

  useEffect(() => {
    const pulse = (scale, opacity, delay) =>
      Animated.loop(
        Animated.sequence([
          Animated.delay(delay),
          Animated.parallel([
            Animated.timing(scale,   { toValue: 1.55, duration: 1200, easing: Easing.out(Easing.ease), useNativeDriver: USE_NATIVE_DRIVER }),
            Animated.timing(opacity, { toValue: 0,    duration: 1200, easing: Easing.out(Easing.ease), useNativeDriver: USE_NATIVE_DRIVER }),
          ]),
          Animated.parallel([
            Animated.timing(scale,   { toValue: 1, duration: 0, useNativeDriver: USE_NATIVE_DRIVER }),
            Animated.timing(opacity, { toValue: delay === 0 ? 0.6 : 0.4, duration: 0, useNativeDriver: USE_NATIVE_DRIVER }),
          ]),
        ])
      );
    const l1 = pulse(ring1, ringOpacity1, 0);
    const l2 = pulse(ring2, ringOpacity2, 600);
    l1.start(); l2.start();
    return () => { l1.stop(); l2.stop(); };
  }, []);

  const gpsInfo =
    gps.state === 'active'
      ? { text: 'GPS Active & Secured', color: C.successDark, bg: C.successLight, icon: 'location' }
      : gps.state === 'denied'
      ? {
          text: Platform.OS === 'web' ? 'GPS unavailable in browser (Tap for info)' : 'GPS is off. Tap to fix in Settings',
          color: C.criticalDark,
          bg: C.criticalLight,
          icon: 'location-outline',
        }
      : { text: 'Finding your location…', color: C.amberDark, bg: C.amberLight, icon: 'locate' };

  const closeConfirm = () => {
    setConfirmOpen(false);
    clearTriggerError();
  };

  const guardianName = guardian && guardian.name ? guardian.name.split(' ')[0] : 'Guardian';
  const childInitials = child && child.name
    ? child.name.split(' ').map((w) => w[0]).join('').toUpperCase().slice(0, 2)
    : null;

  return (
    <SafeAreaView style={[styles.flex, { backgroundColor: C.bg }]}>
      <StatusBar barStyle="light-content" backgroundColor={C.brand} />

      {/* ── Top Header ── */}
      <View style={styles.dashHeader}>
        <View style={{ flex: 1 }}>
          <Text style={styles.dashGreeting}>Hello, {guardianName} 👋</Text>
          <Text style={styles.dashSubGreeting}>Parañaque CSN Guardian</Text>
        </View>
        {/* Child avatar */}
        {childInitials ? (
          <View style={styles.childAvatar}>
            <Text style={styles.childAvatarText}>{childInitials}</Text>
          </View>
        ) : (
          <View style={[styles.childAvatar, { backgroundColor: C.brandDark }]}>
            <Ionicons name="person" size={22} color={C.white} />
          </View>
        )}
        {/* Notification bell */}
        <Pressable
          onPress={() => go('history', { tab: 'inbox' })}
          accessibilityRole="button"
          accessibilityLabel={`Notifications${unread ? `, ${unread} new` : ''}`}
          style={styles.bellBtn}
        >
          <Ionicons name="notifications" size={24} color={C.white} />
          {unread > 0 && (
            <View style={styles.bellBadge}>
              <Text style={styles.bellBadgeText}>{unread > 9 ? '9+' : unread}</Text>
            </View>
          )}
        </Pressable>
      </View>

      <ScrollView contentContainerStyle={styles.dashScroll} showsVerticalScrollIndicator={false}>

        {/* ── Child Status Card ── */}
        <SectionCard style={styles.childCard}>
          <View style={[styles.row, { justifyContent: 'space-between', marginBottom: 10 }]}>
            <View style={{ flex: 1 }}>
              {child && child.name ? (
                <>
                  <Text style={styles.childName}>{child.name}</Text>
                  <Text style={styles.childAge}>{child.age != null ? `${child.age} yrs old` : ''}</Text>
                </>
              ) : (
                <Text style={styles.childName}>No child profile yet</Text>
              )}
            </View>
            {child && child.disability ? (
              <View style={styles.disabilityChip}>
                <Text style={styles.disabilityChipText}>{child.disability}</Text>
              </View>
            ) : null}
          </View>

          {/* GPS status pill */}
          <Pressable
            onPress={gps.state === 'denied' ? openAppSettings : undefined}
            accessibilityRole={gps.state === 'denied' ? 'button' : 'text'}
            style={[styles.gpsStatusPill, { backgroundColor: gpsInfo.bg }]}
          >
            <View style={[styles.gpsDot, { backgroundColor: gpsInfo.color }]} />
            <Ionicons name={gpsInfo.icon} size={15} color={gpsInfo.color} style={{ marginRight: 4 }} />
            <Text style={[styles.gpsStatusText, { color: gpsInfo.color }]}>{gpsInfo.text}</Text>
          </Pressable>
        </SectionCard>

        {/* ── Quick Access Grid ── */}
        <View style={styles.grid}>
          {[
            { key: 'medical',  label: 'Medical\nProfile',  icon: 'medical',   color: '#0284C7' },
            { key: 'history',  label: 'Alert\nHistory',    icon: 'time',      color: '#7C3AED' },
            { key: 'support',  label: 'Emergency\nHotlines', icon: 'call',   color: '#DC2626' },
          ].map((item) => (
            <Pressable
              key={item.key}
              accessibilityRole="button"
              accessibilityLabel={item.label.replace('\n', ' ')}
              onPress={() =>
                item.key === 'support'
                  ? go('support')
                  : go(item.key, { tab: 'alerts' })
              }
              style={({ pressed }) => [styles.gridItem, pressed && { opacity: 0.8 }]}
            >
              <View style={[styles.gridIcon, { backgroundColor: item.color + '18' }]}>
                <Ionicons name={item.icon} size={26} color={item.color} />
              </View>
              <Text style={styles.gridLabel}>{item.label}</Text>
            </Pressable>
          ))}
        </View>

        {/* ── SOS Button Zone ── */}
        <View style={styles.sosZone}>
          <Text style={styles.sosZoneLabel}>EMERGENCY ALERT</Text>

          {/* Outer pulsing rings */}
          <View style={styles.sosWrapper}>
            <Animated.View
              style={[
                styles.sosRing,
                { width: SOS_SIZE + 60, height: SOS_SIZE + 60, borderRadius: (SOS_SIZE + 60) / 2 },
                { transform: [{ scale: ring1 }], opacity: ringOpacity1 },
              ]}
            />
            <Animated.View
              style={[
                styles.sosRing,
                { width: SOS_SIZE + 40, height: SOS_SIZE + 40, borderRadius: (SOS_SIZE + 40) / 2 },
                { transform: [{ scale: ring2 }], opacity: ringOpacity2 },
              ]}
            />

            {/* Core SOS button */}
            <Pressable
              onPress={() => setConfirmOpen(true)}
              accessibilityRole="button"
              accessibilityLabel="Trigger emergency alert"
              style={({ pressed }) => [
                styles.sosBtn,
                { width: SOS_SIZE, height: SOS_SIZE, borderRadius: SOS_SIZE / 2 },
                pressed && { backgroundColor: C.criticalDark },
              ]}
            >
              <Ionicons name="warning" size={42} color={C.white} />
              <Text style={styles.sosText}>HOLD FOR{'\n'}EMERGENCY</Text>
            </Pressable>
          </View>

          <Text style={styles.sosCaution}>For genuine emergencies only</Text>
        </View>
      </ScrollView>

      {/* ── Bottom Tab Bar ── */}
      <BottomTabBar current="dashboard" onPress={(k) => go(k)} unread={unread} />

      {/* ── Emergency Confirmation Modal ── */}
      <Modal visible={confirmOpen} transparent animationType="slide" onRequestClose={closeConfirm}>
        <View style={styles.modalBackdrop}>
          <View style={styles.modalSheet}>
            {/* Handle */}
            <View style={styles.modalHandle} />

            <View style={styles.modalIconCircle}>
              <Ionicons name="warning" size={34} color={C.critical} />
            </View>
            <Text style={styles.modalTitle}>Trigger Emergency Alert?</Text>
            <Text style={styles.modalBody}>
              CSN Admin and responders will be notified immediately with your live location.
            </Text>

            {/* Type chips */}
            <Text style={[styles.label, { marginBottom: 10, textAlign: 'left' }]}>What is happening?</Text>
            <View style={styles.chipWrap}>
              {EMERGENCY_TYPES.map((t) => {
                const active = type === t.key;
                return (
                  <Pressable
                    key={t.key}
                    onPress={() => setType(t.key)}
                    accessibilityRole="radio"
                    accessibilityState={{ selected: active }}
                    style={[
                      styles.chip,
                      active && { backgroundColor: C.critical, borderColor: C.critical },
                    ]}
                  >
                    <Ionicons
                      name={t.icon}
                      size={15}
                      color={active ? C.white : C.textSec}
                      style={{ marginRight: 5 }}
                    />
                    <Text style={[styles.chipText, active && { color: C.white }]}>{t.label}</Text>
                  </Pressable>
                );
              })}
            </View>

            {/* Error banner */}
            {!!triggerError && (
              <View style={[styles.errorBanner, { marginTop: 12 }]} accessibilityLiveRegion="assertive">
                <Ionicons name="alert-circle" size={20} color={C.critical} />
                <Text style={styles.errorBannerText}>
                  {triggerError} If this keeps failing, call {EMERGENCY_NUMBER} now.
                </Text>
              </View>
            )}

            <View style={{ marginTop: 16 }}>
              <HoldToConfirm busy={triggering} onConfirm={() => onTrigger(type)} />
              {!!triggerError && (
                <PrimaryButton
                  title={`Call ${EMERGENCY_NUMBER}`}
                  tone="brand"
                  icon="call"
                  onPress={() => callEmergencyNumber(EMERGENCY_NUMBER)}
                  style={{ marginTop: 10 }}
                />
              )}
              <PrimaryButton
                title="Cancel"
                tone="ghost"
                onPress={closeConfirm}
                style={{ marginTop: 10 }}
                disabled={triggering}
              />
            </View>
          </View>
        </View>
      </Modal>
    </SafeAreaView>
  );
}

/* ============================================================================
 * SCREEN 3 — EMERGENCY ACTIVE
 * ========================================================================== */

const DISPATCH_STEPS = [
  { id: 1, label: 'Alert Sent',           icon: 'paper-plane',     doneStatuses: ['pending','responding','resolved','cancelled'] },
  { id: 2, label: 'CSN Acknowledged',     icon: 'checkmark-circle',doneStatuses: ['responding','resolved','cancelled'] },
  { id: 3, label: 'Responder En Route',   icon: 'car-sport',       doneStatuses: ['resolved'] },
  { id: 4, label: 'Arrived',              icon: 'flag',            doneStatuses: ['resolved'] },
];

function DispatchStepper({ status }) {
  const blink = useRef(new Animated.Value(1)).current;
  useEffect(() => {
    const loop = Animated.loop(
      Animated.sequence([
        Animated.timing(blink, { toValue: 0.3, duration: 700, useNativeDriver: USE_NATIVE_DRIVER }),
        Animated.timing(blink, { toValue: 1,   duration: 700, useNativeDriver: USE_NATIVE_DRIVER }),
      ])
    );
    loop.start();
    return () => loop.stop();
  }, [blink]);

  return (
    <View style={styles.stepperWrap}>
      {DISPATCH_STEPS.map((step, idx) => {
        const done   = step.doneStatuses.includes(status);
        // The "active" step is the first one not done yet
        const activeIdx = DISPATCH_STEPS.findIndex((s) => !s.doneStatuses.includes(status));
        const isActive = idx === activeIdx && status !== 'cancelled';
        const isLast = idx === DISPATCH_STEPS.length - 1;

        return (
          <View key={step.id} style={styles.stepRow}>
            {/* Icon bubble */}
            <View style={{ alignItems: 'center' }}>
              {isActive ? (
                <Animated.View style={[styles.stepIcon, styles.stepIconActive, { opacity: blink }]}>
                  <Ionicons name={step.icon} size={18} color={C.white} />
                </Animated.View>
              ) : (
                <View style={[styles.stepIcon, done ? styles.stepIconDone : styles.stepIconPending]}>
                  <Ionicons
                    name={done ? 'checkmark' : step.icon}
                    size={18}
                    color={done ? C.white : C.muted}
                  />
                </View>
              )}
              {!isLast && (
                <View style={[styles.stepLine, done && { backgroundColor: C.success }]} />
              )}
            </View>
            {/* Label */}
            <Text style={[styles.stepLabel, done && { color: C.text, fontWeight: '700' }, isActive && { color: C.brand, fontWeight: '800' }]}>
              {step.label}
            </Text>
          </View>
        );
      })}
    </View>
  );
}

function EmergencyActiveScreen({ emergency, coords, onCancel, onDone, cancelling }) {
  const [cancelOpen, setCancelOpen] = useState(false);

  const status  = emergency ? emergency.status : 'pending';
  const done    = status === 'resolved' || status === 'cancelled';
  const screenBg =
    status === 'resolved'  ? C.successDark :
    status === 'cancelled' ? C.textSec :
    status === 'responding'? C.amberDark :
    C.critical;

  const headline =
    status === 'resolved'  ? 'Emergency Resolved ✓' :
    status === 'cancelled' ? 'Alert Cancelled' :
    status === 'responding'? 'Help Is On The Way' :
    'Alert Dispatched';

  const subline =
    status === 'pending'   ? 'Broadcasting to CSN Admin & Responders...' :
    status === 'responding'? 'A responder has accepted your alert.' :
    status === 'resolved'  ? 'The CSN team has marked this as resolved.' :
    'This alert was cancelled.';

  const responder = emergency && emergency.responder;

  return (
    <SafeAreaView style={[styles.flex, { backgroundColor: screenBg }]}>
      <StatusBar barStyle="light-content" backgroundColor={screenBg} />
      <ScrollView contentContainerStyle={{ padding: 20, paddingBottom: 40 }} showsVerticalScrollIndicator={false}>

        {/* ── Banner ── */}
        <View style={styles.activeBanner} accessibilityLiveRegion="assertive">
          <View style={styles.activeBannerIcon}>
            <Ionicons
              name={status === 'resolved' ? 'checkmark-circle' : status === 'cancelled' ? 'close-circle' : 'warning'}
              size={48}
              color={C.white}
            />
          </View>
          <Text style={styles.activeHeadline}>{headline}</Text>
          <Text style={styles.activeSub}>{subline}</Text>
          {!done && <ActivityIndicator color="rgba(255,255,255,0.7)" style={{ marginTop: 10 }} />}
        </View>

        {/* ── Dispatch Stepper ── */}
        <SectionCard style={{ marginBottom: 12 }}>
          <Text style={styles.activeCardLabel}>DISPATCH PROGRESS</Text>
          <DispatchStepper status={status} />
        </SectionCard>

        {/* ── GPS Transmission ── */}
        <SectionCard style={{ marginBottom: 12 }}>
          <View style={[styles.row, { marginBottom: 6 }]}>
            <Ionicons
              name="navigate-circle"
              size={20}
              color={done ? C.muted : C.success}
              style={{ marginRight: 8 }}
            />
            <Text style={styles.activeCardTitle}>
              {done ? 'Location sharing stopped' : 'Sending live location'}
            </Text>
          </View>
          <Text style={styles.coordText}>
            {coords
              ? `${coords.latitude.toFixed(5)}, ${coords.longitude.toFixed(5)}`
              : 'Waiting for GPS signal…'}
          </Text>
        </SectionCard>

        {/* ── Responder Card ── */}
        <SectionCard style={{ marginBottom: 16 }}>
          <Text style={styles.activeCardLabel}>ASSIGNED RESPONDER</Text>
          {responder ? (
            <View style={[styles.row, { marginTop: 10 }]}>
              <View style={styles.responderIcon}>
                <Ionicons name="car-sport" size={22} color={C.brand} />
              </View>
              <View style={{ flex: 1 }}>
                <Text style={styles.responderName}>{responder.unit || responder.name}</Text>
                <Text style={styles.responderMeta}>
                  {status === 'resolved' ? 'Arrived' : 'En Route'}
                  {responder.eta ? ` · ETA ${responder.eta}` : ''}
                </Text>
              </View>
            </View>
          ) : (
            <View style={[styles.row, { marginTop: 10 }]}>
              <View style={[styles.responderIcon, { backgroundColor: C.border }]}>
                <Ionicons name="time-outline" size={22} color={C.muted} />
              </View>
              <Text style={[styles.responderMeta, { flex: 1 }]}>
                {done ? 'No responder was assigned.' : 'Waiting for a responder…'}
              </Text>
            </View>
          )}
        </SectionCard>

        {/* ── Call 911 ── */}
        <Pressable
          onPress={() => callEmergencyNumber(EMERGENCY_NUMBER)}
          accessibilityRole="button"
          accessibilityLabel={`Call ${EMERGENCY_NUMBER}`}
          style={({ pressed }) => [styles.callBtn, pressed && { opacity: 0.85 }]}
        >
          <Ionicons name="call" size={20} color={C.critical} style={{ marginRight: 8 }} />
          <Text style={styles.callBtnText}>Call {EMERGENCY_NUMBER}</Text>
        </Pressable>

        {/* ── Done / Cancel ── */}
        {done ? (
          <Pressable
            onPress={onDone}
            accessibilityRole="button"
            style={[styles.ghostBtn, { marginTop: 12 }]}
          >
            <Text style={styles.ghostBtnText}>Back to Home</Text>
          </Pressable>
        ) : (
          <Pressable
            onPress={() => setCancelOpen(true)}
            accessibilityRole="button"
            accessibilityLabel="Cancel alert, false alarm"
            style={[styles.ghostBtn, { marginTop: 12 }]}
          >
            <Text style={styles.ghostBtnText}>Cancel Alert / False Alarm</Text>
          </Pressable>
        )}
      </ScrollView>

      {/* Cancel confirmation modal */}
      <Modal visible={cancelOpen} transparent animationType="fade" onRequestClose={() => setCancelOpen(false)}>
        <View style={styles.modalBackdrop}>
          <View style={styles.modalSheet}>
            <View style={styles.modalHandle} />
            <Text style={styles.modalTitle}>Cancel this alert?</Text>
            <Text style={styles.modalBody}>
              Only cancel if everyone is safe. Responders already en route will be notified it was a false alarm.
            </Text>
            <PrimaryButton
              title="Yes, it was a false alarm"
              tone="critical"
              loading={cancelling}
              onPress={async () => {
                const ok = await onCancel();
                if (ok) setCancelOpen(false);
              }}
            />
            <PrimaryButton
              title="No, keep the alert active"
              tone="ghost"
              onPress={() => setCancelOpen(false)}
              style={{ marginTop: 10 }}
              disabled={cancelling}
            />
          </View>
        </View>
      </Modal>
    </SafeAreaView>
  );
}

/* ============================================================================
 * SCREEN 4 — MEDICAL PROFILE
 * ========================================================================== */

const EMPTY_PROFILE = {
  name: '',
  age: '',
  disability: '',
  allergies: '',
  medications: '',
  communication_notes: '',
  guardian_phone: '',
  emergency_contact_name: '',
  emergency_contact_phone: '',
};

function MedicalProfileScreen({ child, token, go, onSaved, showToast }) {
  const getInitialForm = (c) => ({
    name: c?.name || '',
    age: c && c.age != null ? String(c.age) : '',
    disability: c?.disability || '',
    allergies: c?.allergies || '',
    medications: c?.medications || '',
    communication_notes: c?.communication_notes || '',
    guardian_phone: c?.guardian_phone || '',
    emergency_contact_name: c?.emergency_contact_name || '',
    emergency_contact_phone: c?.emergency_contact_phone || '',
  });

  const [form, setForm]     = useState(() => getInitialForm(child));
  const [errors, setErrors] = useState({});
  const [saving, setSaving] = useState(false);

  useEffect(() => {
    if (child) {
      setForm(getInitialForm(child));
    }
  }, [child]);

  const set = (key) => (value) => setForm((f) => ({ ...f, [key]: value }));

  const validate = () => {
    const e = {};
    if (!form.name?.trim()) e.name = "Enter your child's full name.";
    if (!form.age?.trim() || isNaN(Number(form.age)) || Number(form.age) < 0 || Number(form.age) > 25)
      e.age = 'Enter a valid age (0–25).';
    if (!form.disability?.trim()) e.disability = 'Select or enter a disability / condition.';
    if (!form.guardian_phone?.trim()) e.guardian_phone = 'Enter your contact number.';
    if (!form.emergency_contact_phone?.trim()) e.emergency_contact_phone = 'Enter an emergency contact number.';
    setErrors(e);
    return Object.keys(e).length === 0;
  };

  const save = async () => {
    if (!validate()) {
      showToast('Please fix the highlighted fields.', 'error');
      return;
    }
    setSaving(true);
    try {
      const data = await api('/child', { method: 'PUT', token, body: { ...form, age: Number(form.age) } });
      onSaved(data.child || form);
      showToast('Changes saved.');
    } catch (err) {
      if (err.errors) {
        const mapped = {};
        Object.keys(err.errors).forEach((k) => {
          mapped[k] = Array.isArray(err.errors[k]) ? err.errors[k][0] : String(err.errors[k]);
        });
        setErrors(mapped);
      }
      showToast(err.message, 'error');
    } finally {
      setSaving(false);
    }
  };

  // Disability chip selection — sets disability field
  const handleDisabilityChip = (chip) => {
    setForm((f) => ({ ...f, disability: f.disability === chip ? '' : chip }));
    if (errors.disability) setErrors((e) => ({ ...e, disability: undefined }));
  };

  return (
    <SafeAreaView style={[styles.flex, { backgroundColor: C.bg }]}>
      <StatusBar barStyle="light-content" backgroundColor={C.brand} />

      {/* Header bar */}
      <View style={styles.innerHeader}>
        <Text style={styles.innerHeaderTitle} accessibilityRole="header">Medical Profile</Text>
      </View>

      <KeyboardAvoidingView style={styles.flex} behavior={Platform.OS === 'ios' ? 'padding' : undefined}>
        <ScrollView contentContainerStyle={styles.medScroll} keyboardShouldPersistTaps="handled" showsVerticalScrollIndicator={false}>

          {/* ── Section: Basic Info ── */}
          <Text style={styles.sectionHeading}>Basic Information</Text>
          <SectionCard>
            <Field label="Child's Full Name" value={form.name} onChangeText={set('name')} error={errors.name} autoCapitalize="words" leftIcon="person-outline" placeholder="Full name" />
            <Field label="Age" value={form.age} onChangeText={set('age')} error={errors.age} keyboardType="number-pad" leftIcon="calendar-outline" placeholder="e.g. 8" />

            {/* Disability quick-select chips */}
            <Text style={styles.label}>Disability Classification</Text>
            <View style={styles.chipWrap}>
              {DISABILITY_CHIPS.map((chip) => {
                const active = form.disability === chip;
                return (
                  <Pressable
                    key={chip}
                    onPress={() => handleDisabilityChip(chip)}
                    accessibilityRole="checkbox"
                    accessibilityState={{ checked: active }}
                    style={[styles.chip, active && { backgroundColor: C.brand, borderColor: C.brand }]}
                  >
                    <Text style={[styles.chipText, active && { color: C.white }]}>{chip}</Text>
                  </Pressable>
                );
              })}
            </View>
            {/* Free-text fallback */}
            <Field
              label="Or type condition"
              value={form.disability}
              onChangeText={set('disability')}
              error={errors.disability}
              placeholder="e.g. ASD, Down Syndrome, Cerebral Palsy"
              style={{ marginTop: 8 }}
            />
          </SectionCard>

          {/* ── Section: Medical Details ── */}
          <Text style={styles.sectionHeading}>Medical Details</Text>
          <SectionCard>
            <Field label="Severe Allergies" value={form.allergies} onChangeText={set('allergies')} multiline placeholder="List allergies, or write 'None'" leftIcon="alert-circle-outline" />
            <Field label="Current Medications" value={form.medications} onChangeText={set('medications')} multiline placeholder="Name and dose, or write 'None'" leftIcon="medkit-outline" />
            <Field label="Communication Notes" value={form.communication_notes} onChangeText={set('communication_notes')} multiline placeholder="e.g. Non-verbal, responds to visual cues" leftIcon="chatbubble-ellipses-outline" />
          </SectionCard>

          {/* ── Section: Emergency Contacts ── */}
          <Text style={styles.sectionHeading}>Emergency Contacts</Text>
          <SectionCard>
            <Field label="Primary Guardian Phone" value={form.guardian_phone} onChangeText={set('guardian_phone')} error={errors.guardian_phone} keyboardType="phone-pad" leftIcon="call-outline" placeholder="+63 9XX XXX XXXX" />
            <Field label="Secondary Contact Name" value={form.emergency_contact_name} onChangeText={set('emergency_contact_name')} autoCapitalize="words" leftIcon="person-add-outline" placeholder="Full name" />
            <Field label="Secondary Contact Phone" value={form.emergency_contact_phone} onChangeText={set('emergency_contact_phone')} error={errors.emergency_contact_phone} keyboardType="phone-pad" leftIcon="call-outline" placeholder="+63 9XX XXX XXXX" />
          </SectionCard>

          <PrimaryButton title="Save Profile Changes" tone="brand" icon="save" onPress={save} loading={saving} style={{ marginTop: 8 }} />
          <View style={{ height: 16 }} />
        </ScrollView>
      </KeyboardAvoidingView>

      <BottomTabBar current="medical" onPress={(k) => go(k)} unread={0} />
    </SafeAreaView>
  );
}

/* ============================================================================
 * SCREEN 5 — ALERT HISTORY & NOTIFICATIONS
 * ========================================================================== */

function AlertHistoryScreen({ token, go, initialTab, showToast, onInboxSeen }) {
  const [tab, setTab]                   = useState(initialTab || 'alerts');
  const [alerts, setAlerts]             = useState([]);
  const [announcements, setAnnouncements] = useState([]);
  const [loading, setLoading]           = useState(true);
  const [error, setError]               = useState('');

  const load = useCallback(async () => {
    setLoading(true);
    setError('');
    try {
      const [a, n] = await Promise.all([
        api('/emergencies',   { token }),
        api('/announcements', { token }),
      ]);
      setAlerts((a && a.emergencies) || []);
      setAnnouncements((n && n.announcements) || []);
    } catch (err) {
      setError(err.message);
    } finally {
      setLoading(false);
    }
  }, [token]);

  useEffect(() => { load(); }, [load]);

  useEffect(() => {
    if (tab === 'inbox' && announcements.length) onInboxSeen(announcements);
  }, [tab, announcements]);

  const typeLabel = (key) => (EMERGENCY_TYPES.find((t) => t.key === key) || {}).label || 'Emergency';

  const statusBorderColor = (s) =>
    s === 'pending'    ? C.amber :
    s === 'responding' ? C.brand :
    s === 'resolved'   ? C.success :
    C.muted;

  return (
    <SafeAreaView style={[styles.flex, { backgroundColor: C.bg }]}>
      <StatusBar barStyle="light-content" backgroundColor={C.brand} />

      {/* Header bar */}
      <View style={styles.innerHeader}>
        <Text style={styles.innerHeaderTitle} accessibilityRole="header">Alerts & Notifications</Text>
      </View>

      {/* ── Pill-style tab bar ── */}
      <View style={styles.pillTabContainer}>
        <View style={styles.pillTabBar}>
          {[{ key: 'alerts', label: 'Alert History' }, { key: 'inbox', label: 'Inbox' }].map((t) => (
            <Pressable
              key={t.key}
              onPress={() => setTab(t.key)}
              accessibilityRole="tab"
              accessibilityState={{ selected: tab === t.key }}
              style={[styles.pillTab, tab === t.key && styles.pillTabActive]}
            >
              <Text style={[styles.pillTabText, tab === t.key && styles.pillTabTextActive]}>
                {t.label}
              </Text>
            </Pressable>
          ))}
        </View>
      </View>

      <ScrollView contentContainerStyle={styles.histScroll} showsVerticalScrollIndicator={false}>
        {loading && <ActivityIndicator color={C.brand} style={{ marginTop: 32 }} />}

        {!loading && !!error && (
          <View style={styles.emptyBox}>
            <Ionicons name="cloud-offline" size={40} color={C.muted} />
            <Text style={styles.emptyText}>{error}</Text>
            <PrimaryButton title="Try again" tone="outline" onPress={load} style={{ marginTop: 12, alignSelf: 'stretch' }} />
          </View>
        )}

        {!loading && !error && tab === 'alerts' && (
          alerts.length === 0 ? (
            <View style={styles.emptyBox}>
              <Ionicons name="shield-checkmark" size={40} color={C.muted} />
              <Text style={styles.emptyText}>No emergency alerts yet.{'\n'}Stay safe!</Text>
            </View>
          ) : (
            alerts.map((a) => (
              <View
                key={a.id}
                style={[styles.histCard, { borderLeftColor: statusBorderColor(a.status) }]}
              >
                <View style={[styles.row, { justifyContent: 'space-between', marginBottom: 4 }]}>
                  <Text style={styles.histCardTitle}>{typeLabel(a.type)}</Text>
                  <StatusBadge status={a.status} />
                </View>
                <Text style={styles.histCardMeta}>{formatDateTime(a.reported_at)}</Text>
                {a.responder ? (
                  <Text style={styles.histCardMeta}>Responder: {a.responder.unit || a.responder.name}</Text>
                ) : null}
              </View>
            ))
          )
        )}

        {!loading && !error && tab === 'inbox' && (
          announcements.length === 0 ? (
            <View style={styles.emptyBox}>
              <Ionicons name="mail-open" size={40} color={C.muted} />
              <Text style={styles.emptyText}>No announcements from{'\n'}the CSN Center yet.</Text>
            </View>
          ) : (
            announcements.map((n) => (
              <View key={n.id} style={styles.inboxCard}>
                <View style={[styles.row, { marginBottom: 6 }]}>
                  <View style={styles.inboxIconCircle}>
                    <Ionicons name="megaphone" size={16} color={C.brand} />
                  </View>
                  <Text style={styles.histCardTitle}>{n.title}</Text>
                </View>
                <Text style={styles.histCardBody}>{n.body}</Text>
                <Text style={styles.histCardMeta}>{formatDateTime(n.created_at)}</Text>
              </View>
            ))
          )
        )}
      </ScrollView>

      <BottomTabBar current="history" onPress={(k) => go(k)} unread={0} />
    </SafeAreaView>
  );
}

/* ============================================================================
 * SUPPORT & HELP
 * ========================================================================== */

function SupportScreen({ go, onLogout }) {
  const INFO_CARDS = [
    {
      icon: 'warning',
      iconColor: C.critical,
      title: 'In an Emergency',
      body: `Use the red SOS button on the Home screen. If the app can't connect, call ${EMERGENCY_NUMBER} directly.`,
    },
    {
      icon: 'information-circle',
      iconColor: C.brand,
      title: 'How the Alert Works',
      body: `1. Tap the red SOS button.\n2. Choose the type of emergency.\n3. Press & hold "Hold to Send Alert".\n\nCSN staff and responders receive your child's medical profile and your live location.`,
    },
    {
      icon: 'headset',
      iconColor: C.success,
      title: 'CSN Center',
      body: 'Parañaque City Social Welfare & Development Office — CSN Center. For non-emergency concerns, visit the office during business hours.',
    },
  ];

  return (
    <SafeAreaView style={[styles.flex, { backgroundColor: C.bg }]}>
      <StatusBar barStyle="light-content" backgroundColor={C.brand} />

      <View style={styles.innerHeader}>
        <Text style={styles.innerHeaderTitle} accessibilityRole="header">Support & Help</Text>
      </View>

      <ScrollView contentContainerStyle={styles.supportScroll} showsVerticalScrollIndicator={false}>
        {/* Call 911 hero */}
        <Pressable
          onPress={() => callEmergencyNumber(EMERGENCY_NUMBER)}
          accessibilityRole="button"
          accessibilityLabel={`Call ${EMERGENCY_NUMBER}`}
          style={({ pressed }) => [styles.call911Btn, pressed && { opacity: 0.88 }]}
        >
          <Ionicons name="call" size={28} color={C.white} style={{ marginRight: 12 }} />
          <View>
            <Text style={styles.call911BtnText}>Call {EMERGENCY_NUMBER}</Text>
            <Text style={styles.call911BtnSub}>Philippine National Emergency Hotline</Text>
          </View>
        </Pressable>

        {INFO_CARDS.map((card) => (
          <SectionCard key={card.title} style={{ marginBottom: 12 }}>
            <View style={[styles.row, { marginBottom: 8 }]}>
              <View style={[styles.supportIconCircle, { backgroundColor: card.iconColor + '18' }]}>
                <Ionicons name={card.icon} size={20} color={card.iconColor} />
              </View>
              <Text style={styles.supportCardTitle}>{card.title}</Text>
            </View>
            <Text style={styles.supportCardBody}>{card.body}</Text>
          </SectionCard>
        ))}

        {/* Log out */}
        <Pressable
          onPress={onLogout}
          accessibilityRole="button"
          accessibilityLabel="Log Out"
          style={({ pressed }) => [styles.logoutBtn, pressed && { opacity: 0.75 }]}
        >
          <Ionicons name="log-out-outline" size={20} color={C.textSec} style={{ marginRight: 8 }} />
          <Text style={styles.logoutBtnText}>Log Out</Text>
        </Pressable>

        <View style={{ height: 16 }} />
      </ScrollView>

      <BottomTabBar current="support" onPress={(k) => go(k)} unread={0} />
    </SafeAreaView>
  );
}

/* ============================================================================
 * ROOT APP
 * ========================================================================== */

export default function App() {
  const [bootstrapping, setBootstrapping] = useState(true);
  const [screen, setScreen]               = useState('welcome');
  const [screenParams, setScreenParams]   = useState({});
  const [token, setToken]                 = useState(null);
  const [guardian, setGuardian]           = useState(null);
  const [child, setChild]                 = useState(null);

  const [gps, setGps]       = useState({ state: 'checking' }); // checking | active | denied
  const [coords, setCoords] = useState(null);

  const [emergency, setEmergency]         = useState(null);
  const [triggering, setTriggering]       = useState(false);
  const [triggerError, setTriggerError]   = useState('');
  const [cancelling, setCancelling]       = useState(false);

  const [unread, setUnread]   = useState(0);
  const [seenIds, setSeenIds] = useState([]);

  const [toast, setToast] = useState(null);
  const showToast = useCallback((message, tone = 'success') => setToast({ message, tone, id: Date.now() }), []);

  const go = useCallback((next, params = {}) => {
    setScreenParams(params);
    setScreen(next);
  }, []);

  /* ---- auth ---- */
  const onAuthed = async (data) => {
    setToken(data.token);
    setGuardian(data.guardian || null);
    setChild(data.child || null);
    if (data.token) {
      await saveStoredToken(data.token);
    }
    go('dashboard');
  };

  const logout = async () => {
    try {
      await firebaseLogout();
      if (token) {
        await api('/auth/logout', { method: 'POST', token });
      }
    } catch (e) {}
    await removeStoredToken();
    setToken(null);
    setGuardian(null);
    setChild(null);
    setEmergency(null);
    go('welcome');
  };

  // Auto-restore login session on startup
  useEffect(() => {
    if (DEV_BYPASS_LOGIN) {
      setBootstrapping(false);
      go('dashboard');
      return;
    }

    let isMounted = true;
    (async () => {
      try {
        const savedToken = await getStoredToken();
        if (savedToken && API_BASE_URL) {
          const data = await api('/me', { token: savedToken });
          if (isMounted && data && data.guardian) {
            setToken(savedToken);
            setGuardian(data.guardian || null);
            setChild(data.child || null);
            go('dashboard');
            return;
          }
        }
      } catch (err) {
        // Token expired or invalid
        await removeStoredToken();
      } finally {
        if (isMounted) setBootstrapping(false);
      }
    })();

    return () => {
      isMounted = false;
    };
  }, [go]);

  /* ---- GPS permission + live position on the dashboard ---- */
  useEffect(() => {
    if (screen !== 'dashboard') return undefined;
    let sub;
    let cancelled = false;
    (async () => {
      try {
        const perm = await Location.requestForegroundPermissionsAsync();
        if (cancelled) return;
        if (perm.status !== 'granted') {
          setGps({ state: 'denied' });
          return;
        }
        sub = await Location.watchPositionAsync(
          { accuracy: Location.Accuracy.High, timeInterval: 10000, distanceInterval: 10 },
          (pos) => {
            setCoords(pos.coords);
            setGps({ state: 'active' });
          }
        );
      } catch (e) {
        if (!cancelled) setGps({ state: 'denied' });
      }
    })();
    return () => {
      cancelled = true;
      if (sub) sub.remove();
    };
  }, [screen]);

  /* ---- unread announcements count (dashboard bell) ---- */
  useEffect(() => {
    if (screen !== 'dashboard' || !token) return;
    api('/announcements', { token })
      .then((d) => {
        const list = (d && d.announcements) || [];
        setUnread(list.filter((n) => !seenIds.includes(n.id)).length);
      })
      .catch(() => {});
  }, [screen, token, seenIds]);

  const onInboxSeen = useCallback((list) => {
    setSeenIds((prev) => {
      const next = Array.from(new Set([...prev, ...list.map((n) => n.id)]));
      return next.length === prev.length ? prev : next;
    });
  }, []);

  /* ---- trigger emergency ---- */
  const triggerEmergency = async (type) => {
    setTriggerError('');
    setTriggering(true);
    let position = coords;
    try {
      const fresh = await Location.getCurrentPositionAsync({ accuracy: Location.Accuracy.High });
      position = fresh.coords;
      setCoords(fresh.coords);
    } catch (e) {
      /* fall back to the last known position, or send without coordinates */
    }
    try {
      const data = await api('/emergencies', {
        method: 'POST',
        token,
        body: { type, lat: position ? position.latitude : null, lng: position ? position.longitude : null },
      });
      setEmergency(data.emergency);
      go('active');
    } catch (err) {
      setTriggerError(err.message);
    } finally {
      setTriggering(false);
    }
  };

  /* ---- while an emergency is active: poll status + stream location ---- */
  const emergencyId     = emergency ? emergency.id : null;
  const emergencyStatus = emergency ? emergency.status : null;
  const isLive =
    screen === 'active' &&
    emergencyId &&
    emergencyStatus !== 'resolved' &&
    emergencyStatus !== 'cancelled';

  useEffect(() => {
    if (!isLive) return undefined;
    const timer = setInterval(async () => {
      try {
        const data = await api(`/emergencies/${emergencyId}`, { token });
        if (data && data.emergency) setEmergency(data.emergency);
      } catch (e) {
        /* keep trying on the next tick */
      }
    }, POLL_INTERVAL_MS);
    return () => clearInterval(timer);
  }, [isLive, emergencyId, token]);

  useEffect(() => {
    if (!isLive) return undefined;
    let sub;
    let cancelled = false;
    (async () => {
      try {
        sub = await Location.watchPositionAsync(
          { accuracy: Location.Accuracy.High, timeInterval: 5000, distanceInterval: 5 },
          (pos) => {
            setCoords(pos.coords);
            api(`/emergencies/${emergencyId}/location`, {
              method: 'PATCH',
              token,
              body: { lat: pos.coords.latitude, lng: pos.coords.longitude },
            }).catch(() => {});
          }
        );
        if (cancelled && sub) sub.remove();
      } catch (e) {
        /* no GPS; alert is already sent */
      }
    })();
    return () => {
      cancelled = true;
      if (sub) sub.remove();
    };
  }, [isLive, emergencyId, token]);

  const cancelEmergency = async () => {
    setCancelling(true);
    try {
      const data = await api(`/emergencies/${emergencyId}/cancel`, { method: 'POST', token });
      setEmergency((data && data.emergency) || { ...emergency, status: 'cancelled' });
      return true;
    } catch (err) {
      showToast(err.message, 'error');
      return false;
    } finally {
      setCancelling(false);
    }
  };

  /* ---- render ---- */
  let content;
  switch (screen) {
    case 'login':
      content = <AuthScreen mode="login" go={go} onAuthed={onAuthed} showToast={showToast} />;
      break;
    case 'signup':
      content = <AuthScreen mode="signup" go={go} onAuthed={onAuthed} showToast={showToast} />;
      break;
    case 'dashboard':
      content = (
        <DashboardScreen
          guardian={guardian}
          child={child}
          gps={gps}
          go={go}
          onTrigger={triggerEmergency}
          triggering={triggering}
          triggerError={triggerError}
          clearTriggerError={() => setTriggerError('')}
          unread={unread}
        />
      );
      break;
    case 'active':
      content = (
        <EmergencyActiveScreen
          emergency={emergency}
          coords={coords}
          onCancel={cancelEmergency}
          cancelling={cancelling}
          onDone={() => {
            setEmergency(null);
            go('dashboard');
          }}
        />
      );
      break;
    case 'medical':
      content = <MedicalProfileScreen child={child} token={token} go={go} onSaved={setChild} showToast={showToast} />;
      break;
    case 'history':
      content = (
        <AlertHistoryScreen
          token={token}
          go={go}
          initialTab={screenParams.tab}
          showToast={showToast}
          onInboxSeen={onInboxSeen}
        />
      );
      break;
    case 'support':
      content = <SupportScreen go={go} onLogout={logout} />;
      break;
    default:
      content = <WelcomeScreen go={go} />;
  }

  if (bootstrapping) {
    return (
      <View style={[styles.flex, { backgroundColor: C.brand, alignItems: 'center', justifyContent: 'center' }]}>
        <StatusBar barStyle="light-content" backgroundColor={C.brand} />
        <View style={styles.logoCircle}>
          <Ionicons name="shield-checkmark" size={40} color={C.brand} />
        </View>
        <ActivityIndicator size="large" color={C.white} style={{ marginTop: 24 }} />
      </View>
    );
  }

  return (
    <View style={styles.flex}>
      <StatusBar barStyle="light-content" backgroundColor={C.brand} />
      {content}
      <Toast toast={toast} />
    </View>
  );
}

/* ============================================================================
 * STYLES
 * ========================================================================== */

const TAB_BAR_H = 64;
const ANDROID_STATUS = Platform.OS === 'android' ? (StatusBar.currentHeight || 0) : 0;

const styles = StyleSheet.create({
  flex: { flex: 1 },
  row: { flexDirection: 'row', alignItems: 'center' },

  /* ── Buttons ── */
  btn: {
    minHeight: MIN_TOUCH + 4,
    borderRadius: 14,
    alignItems: 'center',
    justifyContent: 'center',
    paddingHorizontal: 16,
  },
  btnText: { color: C.white, fontSize: 16, fontWeight: '700' },

  /* ── Form ── */
  label: { color: C.text, fontSize: 14, fontWeight: '600', marginBottom: 6 },
  inputWrap: {
    flexDirection: 'row',
    alignItems: 'center',
    minHeight: MIN_TOUCH,
    backgroundColor: C.white,
    borderWidth: 1.5,
    borderColor: C.border,
    borderRadius: 14,
    paddingHorizontal: 14,
    marginBottom: 0,
  },
  inputInner: {
    flex: 1,
    fontSize: 16,
    color: C.text,
    paddingVertical: Platform.OS === 'ios' ? 12 : 8,
  },
  errorText: { color: C.criticalDark, fontSize: 13, marginTop: 4 },
  errorBanner: {
    flexDirection: 'row',
    alignItems: 'flex-start',
    backgroundColor: C.criticalLight,
    borderRadius: 10,
    padding: 12,
    marginBottom: 14,
    gap: 8,
  },
  errorBannerText: { flex: 1, color: C.criticalDark, fontSize: 14, lineHeight: 20 },

  /* ── Section Card ── */
  sectionCard: {
    backgroundColor: C.card,
    borderRadius: 20,
    padding: 18,
    marginBottom: 16,
    shadowColor: '#0F172A',
    shadowOpacity: 0.06,
    shadowRadius: 8,
    shadowOffset: { width: 0, height: 2 },
    elevation: 3,
  },
  sectionHeading: {
    color: C.brand,
    fontSize: 14,
    fontWeight: '800',
    letterSpacing: 0.5,
    marginBottom: 10,
    marginTop: 4,
    textTransform: 'uppercase',
  },

  /* ── Bottom Tab Bar ── */
  tabBar: {
    flexDirection: 'row',
    backgroundColor: C.card,
    borderTopWidth: 1,
    borderTopColor: C.border,
    height: TAB_BAR_H,
    paddingBottom: Platform.OS === 'ios' ? 8 : 4,
    paddingTop: 4,
    shadowColor: '#0F172A',
    shadowOpacity: 0.08,
    shadowRadius: 12,
    shadowOffset: { width: 0, height: -3 },
    elevation: 10,
  },
  tabBarItem: {
    flex: 1,
    alignItems: 'center',
    justifyContent: 'center',
  },
  tabBarLabel: {
    fontSize: 11,
    fontWeight: '600',
    color: C.muted,
    marginTop: 3,
  },
  tabBadge: {
    position: 'absolute',
    top: -4,
    right: -8,
    minWidth: 16,
    height: 16,
    borderRadius: 8,
    backgroundColor: C.critical,
    alignItems: 'center',
    justifyContent: 'center',
    paddingHorizontal: 3,
  },
  tabBadgeText: { color: C.white, fontSize: 10, fontWeight: '800' },

  /* ── Inner screen header (no back button, for tab screens) ── */
  innerHeader: {
    backgroundColor: C.brand,
    paddingTop: ANDROID_STATUS + 14,
    paddingBottom: 14,
    paddingHorizontal: 20,
    shadowColor: C.brandDark,
    shadowOpacity: 0.18,
    shadowRadius: 6,
    shadowOffset: { width: 0, height: 3 },
    elevation: 6,
  },
  innerHeaderTitle: {
    color: C.white,
    fontSize: 22,
    fontWeight: '800',
  },

  /* ── Welcome screen ── */
  welcomeBody: {
    flex: 1,
    alignItems: 'center',
    justifyContent: 'center',
    paddingHorizontal: 32,
  },
  logoCircle: {
    width: 112,
    height: 112,
    borderRadius: 56,
    backgroundColor: C.white,
    alignItems: 'center',
    justifyContent: 'center',
    marginBottom: 24,
    shadowColor: C.brandDark,
    shadowOpacity: 0.3,
    shadowRadius: 16,
    shadowOffset: { width: 0, height: 8 },
    elevation: 8,
  },
  welcomeTitle: { color: C.white, fontSize: 34, fontWeight: '900', letterSpacing: 0.5 },
  welcomeSubtitle: { color: '#BAE6FD', fontSize: 17, fontWeight: '600', marginTop: 4 },
  welcomeDivider: {
    width: 40,
    height: 3,
    borderRadius: 2,
    backgroundColor: 'rgba(255,255,255,0.35)',
    marginVertical: 20,
  },
  welcomeTagline: {
    color: 'rgba(255,255,255,0.85)',
    fontSize: 16,
    textAlign: 'center',
    lineHeight: 24,
  },
  welcomeActions: { paddingHorizontal: 24, paddingBottom: 40 },
  welcomeLoginBtn: {
    minHeight: 54,
    backgroundColor: C.white,
    borderRadius: 16,
    alignItems: 'center',
    justifyContent: 'center',
    marginBottom: 12,
    shadowColor: C.brandDark,
    shadowOpacity: 0.15,
    shadowRadius: 8,
    shadowOffset: { width: 0, height: 4 },
    elevation: 4,
  },
  welcomeLoginText: { color: C.brand, fontSize: 17, fontWeight: '800' },
  welcomeSignupBtn: {
    minHeight: 54,
    borderRadius: 16,
    borderWidth: 1.5,
    borderColor: 'rgba(255,255,255,0.5)',
    alignItems: 'center',
    justifyContent: 'center',
  },
  welcomeSignupText: { color: C.white, fontSize: 17, fontWeight: '700' },
  welcomeFooter: {
    color: 'rgba(255,255,255,0.5)',
    fontSize: 12,
    textAlign: 'center',
    marginTop: 20,
  },

  /* ── Auth screen ── */
  authHeader: {
    flexDirection: 'row',
    alignItems: 'center',
    paddingHorizontal: 12,
    paddingTop: ANDROID_STATUS + 8,
    paddingBottom: 8,
    backgroundColor: C.bg,
  },
  authHeaderTitle: { flex: 1, textAlign: 'center', fontSize: 20, fontWeight: '800', color: C.text },
  backBtn: {
    width: MIN_TOUCH,
    height: MIN_TOUCH,
    alignItems: 'center',
    justifyContent: 'center',
  },
  authScroll: { padding: 20, paddingBottom: 40 },
  authSubtitle: {
    color: C.textSec,
    fontSize: 16,
    marginBottom: 20,
    lineHeight: 22,
  },
  forgotBtn: { alignSelf: 'flex-end', minHeight: MIN_TOUCH, justifyContent: 'center', marginBottom: 8 },
  forgotText: { color: C.brand, fontSize: 14, fontWeight: '600' },
  dividerRow: { flexDirection: 'row', alignItems: 'center', marginVertical: 16 },
  dividerLine: { flex: 1, height: 1, backgroundColor: C.border },
  dividerText: { marginHorizontal: 12, color: C.muted, fontSize: 14 },
  googleBtn: {
    minHeight: MIN_TOUCH + 4,
    borderRadius: 14,
    borderWidth: 1.5,
    borderColor: C.border,
    backgroundColor: C.white,
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'center',
    paddingHorizontal: 16,
  },
  googleBtnText: { color: C.textSec, fontSize: 16, fontWeight: '700' },
  switchAuthBtn: {
    minHeight: MIN_TOUCH,
    alignItems: 'center',
    justifyContent: 'center',
    marginTop: 12,
  },
  switchAuthText: { color: C.textSec, fontSize: 15 },

  /* ── Dashboard ── */
  dashHeader: {
    backgroundColor: C.brand,
    paddingTop: ANDROID_STATUS + 14,
    paddingBottom: 16,
    paddingHorizontal: 20,
    flexDirection: 'row',
    alignItems: 'center',
    gap: 12,
  },
  dashGreeting: { color: C.white, fontSize: 21, fontWeight: '800' },
  dashSubGreeting: { color: '#BAE6FD', fontSize: 13, fontWeight: '500', marginTop: 2 },
  childAvatar: {
    width: 44,
    height: 44,
    borderRadius: 22,
    backgroundColor: C.sky,
    alignItems: 'center',
    justifyContent: 'center',
    borderWidth: 2,
    borderColor: 'rgba(255,255,255,0.4)',
  },
  childAvatarText: { color: C.white, fontSize: 16, fontWeight: '900' },
  bellBtn: {
    width: MIN_TOUCH,
    height: MIN_TOUCH,
    alignItems: 'center',
    justifyContent: 'center',
  },
  bellBadge: {
    position: 'absolute',
    top: 4,
    right: 4,
    minWidth: 18,
    height: 18,
    borderRadius: 9,
    backgroundColor: C.critical,
    alignItems: 'center',
    justifyContent: 'center',
    paddingHorizontal: 3,
  },
  bellBadgeText: { color: C.white, fontSize: 11, fontWeight: '800' },

  dashScroll: { padding: 18, paddingBottom: TAB_BAR_H + 16 },

  /* Child status card */
  childCard: { marginBottom: 16 },
  childName: { color: C.text, fontSize: 19, fontWeight: '800' },
  childAge: { color: C.textSec, fontSize: 14, marginTop: 2 },
  disabilityChip: {
    backgroundColor: C.brandLight,
    borderRadius: 999,
    paddingHorizontal: 12,
    paddingVertical: 4,
    alignSelf: 'flex-start',
  },
  disabilityChipText: { color: C.brand, fontSize: 13, fontWeight: '700' },
  gpsStatusPill: {
    flexDirection: 'row',
    alignItems: 'center',
    borderRadius: 999,
    paddingHorizontal: 12,
    paddingVertical: 6,
    alignSelf: 'flex-start',
    gap: 4,
  },
  gpsDot: {
    width: 8,
    height: 8,
    borderRadius: 4,
  },
  gpsStatusText: { fontSize: 13, fontWeight: '700' },

  /* Quick grid */
  grid: { flexDirection: 'row', gap: 10, marginBottom: 16 },
  gridItem: {
    flex: 1,
    minHeight: 104,
    backgroundColor: C.card,
    borderRadius: 18,
    borderWidth: 1,
    borderColor: C.border,
    alignItems: 'center',
    justifyContent: 'center',
    padding: 10,
    shadowColor: '#0F172A',
    shadowOpacity: 0.05,
    shadowRadius: 6,
    shadowOffset: { width: 0, height: 2 },
    elevation: 2,
  },
  gridIcon: {
    width: 50,
    height: 50,
    borderRadius: 25,
    alignItems: 'center',
    justifyContent: 'center',
    marginBottom: 8,
  },
  gridLabel: { color: C.text, fontSize: 12, fontWeight: '700', textAlign: 'center' },

  /* SOS zone */
  sosZone: { alignItems: 'center', paddingVertical: 12 },
  sosZoneLabel: {
    color: C.muted,
    fontSize: 11,
    fontWeight: '800',
    letterSpacing: 1.5,
    marginBottom: 20,
    textTransform: 'uppercase',
  },
  sosWrapper: {
    width: SOS_SIZE + 80,
    height: SOS_SIZE + 80,
    alignItems: 'center',
    justifyContent: 'center',
  },
  sosRing: {
    position: 'absolute',
    backgroundColor: C.critical,
  },
  sosBtn: {
    backgroundColor: C.critical,
    alignItems: 'center',
    justifyContent: 'center',
    shadowColor: C.critical,
    shadowOpacity: 0.55,
    shadowRadius: 24,
    shadowOffset: { width: 0, height: 8 },
    elevation: 14,
    position: 'absolute',
  },
  sosText: {
    color: C.white,
    fontSize: 15,
    fontWeight: '900',
    textAlign: 'center',
    marginTop: 8,
    letterSpacing: 0.5,
    lineHeight: 20,
  },
  sosCaution: {
    color: C.muted,
    fontSize: 13,
    marginTop: 22,
    textAlign: 'center',
  },

  /* Modal / Sheet */
  modalBackdrop: {
    flex: 1,
    backgroundColor: 'rgba(15,23,42,0.65)',
    justifyContent: 'flex-end',
  },
  modalSheet: {
    backgroundColor: C.white,
    borderTopLeftRadius: 28,
    borderTopRightRadius: 28,
    padding: 24,
    paddingBottom: 36,
  },
  modalHandle: {
    width: 40,
    height: 4,
    borderRadius: 2,
    backgroundColor: C.border,
    alignSelf: 'center',
    marginBottom: 20,
  },
  modalIconCircle: {
    width: 64,
    height: 64,
    borderRadius: 32,
    backgroundColor: C.criticalLight,
    alignItems: 'center',
    justifyContent: 'center',
    alignSelf: 'center',
    marginBottom: 14,
  },
  modalTitle: { color: C.text, fontSize: 20, fontWeight: '800', textAlign: 'center', marginBottom: 8 },
  modalBody: { color: C.textSec, fontSize: 15, textAlign: 'center', lineHeight: 22, marginBottom: 16 },

  /* Chips */
  chipWrap: { flexDirection: 'row', flexWrap: 'wrap', gap: 8, marginBottom: 14 },
  chip: {
    flexDirection: 'row',
    alignItems: 'center',
    minHeight: 40,
    paddingHorizontal: 14,
    paddingVertical: 6,
    borderRadius: 999,
    borderWidth: 1.5,
    borderColor: C.border,
    backgroundColor: C.white,
  },
  chipText: { color: C.textSec, fontWeight: '700', fontSize: 14 },

  /* Hold to confirm */
  holdBtn: {
    minHeight: 60,
    borderRadius: 14,
    backgroundColor: C.criticalDark,
    overflow: 'hidden',
    alignItems: 'center',
    justifyContent: 'center',
  },
  holdFill: { position: 'absolute', left: 0, top: 0, bottom: 0, backgroundColor: C.critical },
  holdText: { color: C.white, fontSize: 17, fontWeight: '800', letterSpacing: 0.5 },

  /* Active emergency screen */
  activeBanner: { alignItems: 'center', paddingVertical: 20, marginBottom: 12 },
  activeBannerIcon: {
    width: 84,
    height: 84,
    borderRadius: 42,
    backgroundColor: 'rgba(255,255,255,0.2)',
    alignItems: 'center',
    justifyContent: 'center',
    marginBottom: 12,
  },
  activeHeadline: { color: C.white, fontSize: 26, fontWeight: '900', textAlign: 'center' },
  activeSub: { color: 'rgba(255,255,255,0.9)', fontSize: 15, textAlign: 'center', marginTop: 8, lineHeight: 22 },
  activeCardLabel: { color: C.muted, fontSize: 11, fontWeight: '800', letterSpacing: 1, textTransform: 'uppercase', marginBottom: 12 },
  activeCardTitle: { color: C.text, fontSize: 15, fontWeight: '700' },
  coordText: { color: C.text, fontSize: 18, fontWeight: '700', fontVariant: ['tabular-nums'] },
  responderIcon: {
    width: 48,
    height: 48,
    borderRadius: 24,
    backgroundColor: C.brandLight,
    alignItems: 'center',
    justifyContent: 'center',
    marginRight: 12,
  },
  responderName: { color: C.text, fontSize: 17, fontWeight: '800' },
  responderMeta: { color: C.muted, fontSize: 14, marginTop: 2 },
  callBtn: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'center',
    backgroundColor: C.white,
    borderRadius: 14,
    minHeight: MIN_TOUCH + 4,
    paddingHorizontal: 16,
    borderWidth: 1.5,
    borderColor: C.critical,
  },
  callBtnText: { color: C.critical, fontSize: 16, fontWeight: '800' },
  ghostBtn: {
    minHeight: MIN_TOUCH + 4,
    borderRadius: 14,
    alignItems: 'center',
    justifyContent: 'center',
    borderWidth: 1.5,
    borderColor: 'rgba(255,255,255,0.5)',
  },
  ghostBtnText: { color: C.white, fontSize: 16, fontWeight: '700' },

  /* Stepper */
  stepperWrap: { paddingTop: 4 },
  stepRow: { flexDirection: 'row', alignItems: 'flex-start', marginBottom: 0 },
  stepIcon: {
    width: 38,
    height: 38,
    borderRadius: 19,
    alignItems: 'center',
    justifyContent: 'center',
    marginRight: 14,
  },
  stepIconDone:    { backgroundColor: C.success },
  stepIconPending: { backgroundColor: C.border },
  stepIconActive:  { backgroundColor: C.brand },
  stepLine: {
    width: 2,
    height: 24,
    backgroundColor: C.border,
    alignSelf: 'center',
    marginLeft: 18,
    marginVertical: 2,
  },
  stepLabel: { color: C.muted, fontSize: 14, fontWeight: '500', paddingTop: 10, flex: 1 },

  /* Medical profile */
  medScroll: { padding: 18, paddingBottom: TAB_BAR_H + 16 },

  /* Alert history */
  histScroll: { padding: 16, paddingBottom: TAB_BAR_H + 16 },
  pillTabContainer: {
    backgroundColor: C.bg,
    paddingHorizontal: 16,
    paddingVertical: 12,
  },
  pillTabBar: {
    flexDirection: 'row',
    backgroundColor: C.border,
    borderRadius: 999,
    padding: 3,
  },
  pillTab: {
    flex: 1,
    minHeight: 36,
    alignItems: 'center',
    justifyContent: 'center',
    borderRadius: 999,
  },
  pillTabActive: { backgroundColor: C.white, shadowColor: '#000', shadowOpacity: 0.1, shadowRadius: 4, elevation: 2 },
  pillTabText: { color: C.muted, fontSize: 14, fontWeight: '700' },
  pillTabTextActive: { color: C.brand },
  histCard: {
    backgroundColor: C.card,
    borderRadius: 16,
    padding: 16,
    marginBottom: 12,
    borderLeftWidth: 4,
    borderLeftColor: C.muted,
    shadowColor: '#0F172A',
    shadowOpacity: 0.05,
    shadowRadius: 6,
    elevation: 2,
  },
  histCardTitle: { color: C.text, fontSize: 15, fontWeight: '800', flex: 1 },
  histCardBody: { color: C.textSec, fontSize: 14, lineHeight: 21, marginTop: 4 },
  histCardMeta: { color: C.muted, fontSize: 13, marginTop: 4 },
  inboxCard: {
    backgroundColor: C.card,
    borderRadius: 16,
    padding: 16,
    marginBottom: 12,
    borderWidth: 1,
    borderColor: C.border,
    shadowColor: '#0F172A',
    shadowOpacity: 0.04,
    shadowRadius: 4,
    elevation: 2,
  },
  inboxIconCircle: {
    width: 30,
    height: 30,
    borderRadius: 15,
    backgroundColor: C.brandLight,
    alignItems: 'center',
    justifyContent: 'center',
    marginRight: 10,
  },
  badge: { paddingHorizontal: 10, paddingVertical: 4, borderRadius: 999 },
  badgeText: { fontSize: 11, fontWeight: '800' },
  emptyBox: { alignItems: 'center', padding: 40 },
  emptyText: { color: C.muted, fontSize: 15, textAlign: 'center', marginTop: 12, lineHeight: 22 },

  /* Support */
  supportScroll: { padding: 18, paddingBottom: TAB_BAR_H + 16 },
  call911Btn: {
    flexDirection: 'row',
    alignItems: 'center',
    backgroundColor: C.critical,
    borderRadius: 20,
    padding: 20,
    marginBottom: 16,
    shadowColor: C.critical,
    shadowOpacity: 0.4,
    shadowRadius: 12,
    shadowOffset: { width: 0, height: 4 },
    elevation: 8,
  },
  call911BtnText: { color: C.white, fontSize: 22, fontWeight: '900' },
  call911BtnSub: { color: 'rgba(255,255,255,0.8)', fontSize: 13, marginTop: 2 },
  supportIconCircle: {
    width: 38,
    height: 38,
    borderRadius: 19,
    alignItems: 'center',
    justifyContent: 'center',
    marginRight: 12,
  },
  supportCardTitle: { color: C.text, fontSize: 16, fontWeight: '800', flex: 1 },
  supportCardBody: { color: C.textSec, fontSize: 14, lineHeight: 22 },
  logoutBtn: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'center',
    minHeight: MIN_TOUCH,
    borderRadius: 14,
    borderWidth: 1.5,
    borderColor: C.border,
    backgroundColor: C.card,
    paddingHorizontal: 16,
    marginTop: 8,
  },
  logoutBtnText: { color: C.textSec, fontSize: 16, fontWeight: '700' },

  /* Toast */
  toast: {
    position: 'absolute',
    left: 20,
    right: 20,
    bottom: TAB_BAR_H + 12,
    minHeight: MIN_TOUCH,
    borderRadius: 14,
    paddingHorizontal: 16,
    flexDirection: 'row',
    alignItems: 'center',
    gap: 10,
    elevation: 12,
    shadowColor: '#000',
    shadowOpacity: 0.25,
    shadowRadius: 8,
    shadowOffset: { width: 0, height: 4 },
  },
  toastText: { color: C.white, fontSize: 15, fontWeight: '600', flex: 1 },
});