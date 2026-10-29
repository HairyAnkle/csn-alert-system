import { initializeApp, getApps, getApp } from 'firebase/app';
import {
  getAuth,
  initializeAuth,
  getReactNativePersistence,
  signInWithEmailAndPassword,
  createUserWithEmailAndPassword,
  signInWithPopup,
  signInWithCredential,
  GoogleAuthProvider,
  signOut,
  sendPasswordResetEmail,
  updateProfile,
} from 'firebase/auth';
import AsyncStorage from '@react-native-async-storage/async-storage';
import * as WebBrowser from 'expo-web-browser';
import * as Crypto from 'expo-crypto';
import { Platform } from 'react-native';

try {
  WebBrowser.maybeCompleteAuthSession();
} catch (_) {}

export const firebaseConfig = {
  apiKey: "AIzaSyBTy_U-UYSLg9kcnDjgSKcFzVF7cYg3MbY",
  authDomain: "csn-paranaque.firebaseapp.com",
  projectId: "csn-paranaque",
  storageBucket: "csn-paranaque.firebasestorage.app",
  messagingSenderId: "967210951869",
  appId: "1:967210951869:web:cbaf23ab665ed911efc3d4",
  measurementId: "G-JBDK92QXWD"
};

// Initialize Firebase App
export const app = getApps().length === 0 ? initializeApp(firebaseConfig) : getApp();

// Initialize Firebase Auth with persistence for native platforms
function createFirebaseAuth() {
  if (Platform.OS !== 'web') {
    try {
      return initializeAuth(app, {
        persistence: getReactNativePersistence(AsyncStorage),
      });
    } catch {
      return getAuth(app);
    }
  }
  return getAuth(app);
}

export const auth = createFirebaseAuth();

/**
 * Format Firebase Auth errors into friendly messages.
 */
export function formatFirebaseAuthError(err) {
  if (!err) return 'Authentication failed.';
  const code = err.code || '';
  switch (code) {
    case 'auth/invalid-credential':
    case 'auth/wrong-password':
    case 'auth/user-not-found':
      return 'Incorrect email or password.';
    case 'auth/email-already-in-use':
      return 'This email address is already registered. Please sign in instead.';
    case 'auth/weak-password':
      return 'Password should be at least 6 characters.';
    case 'auth/invalid-email':
      return 'Please enter a valid email address.';
    case 'auth/user-disabled':
      return 'This account has been disabled. Please contact the administrator.';
    case 'auth/popup-closed-by-user':
      return 'Google Sign-In was cancelled.';
    case 'auth/popup-blocked':
      return 'Google Sign-In popup was blocked by browser. Please allow popups.';
    case 'auth/network-request-failed':
      return 'Network connection error. Please check your internet connection.';
    case 'auth/too-many-requests':
      return 'Access temporarily blocked due to many failed attempts. Try again later.';
    default:
      return err.message || 'An error occurred during authentication.';
  }
}

/**
 * Sign in with Email and Password via Firebase Auth.
 */
export async function firebaseLoginWithEmail(email, password) {
  const userCredential = await signInWithEmailAndPassword(auth, email.trim(), password);
  const idToken = await userCredential.user.getIdToken(true);
  return {
    user: userCredential.user,
    idToken,
  };
}

/**
 * Register a new user with Email and Password via Firebase Auth.
 */
export async function firebaseRegisterWithEmail(name, email, password) {
  const userCredential = await createUserWithEmailAndPassword(auth, email.trim(), password);
  if (name && name.trim()) {
    try {
      await updateProfile(userCredential.user, { displayName: name.trim() });
    } catch (_) {
      // Non-critical profile update failure
    }
  }
  const idToken = await userCredential.user.getIdToken(true);
  return {
    user: userCredential.user,
    idToken,
  };
}

/**
 * Send password reset email via Firebase Auth.
 */
export async function firebaseSendPasswordReset(email) {
  return await sendPasswordResetEmail(auth, email.trim());
}
export const firebaseResetPassword = firebaseSendPasswordReset;

// Real Google OAuth Client IDs registered in Firebase project 'csn-paranaque'
export const GOOGLE_IOS_CLIENT_ID = '967210951869-bfm5ct9bcc0vj2m2enpq42qmagco97tn.apps.googleusercontent.com';
export const GOOGLE_IOS_REDIRECT_URI = 'com.googleusercontent.apps.967210951869-bfm5ct9bcc0vj2m2enpq42qmagco97tn:/oauthredirect';
export const GOOGLE_WEB_CLIENT_ID = '967210951869-8d5gtflrnb1tqfdho2j2q8l9lk6ejpig.apps.googleusercontent.com';

const PKCE_CHARSET = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789-._~';

/**
 * Generate RFC 7636 compliant PKCE code_verifier and code_challenge.
 */
export async function generatePKCECodes() {
  const bytes = Crypto.getRandomValues(new Uint8Array(64));
  let codeVerifier = '';
  for (let i = 0; i < bytes.length; i++) {
    codeVerifier += PKCE_CHARSET[bytes[i] % PKCE_CHARSET.length];
  }

  const rawBase64 = await Crypto.digestStringAsync(
    Crypto.CryptoDigestAlgorithm.SHA256,
    codeVerifier,
    { encoding: Crypto.CryptoEncoding.BASE64 }
  );

  const codeChallenge = rawBase64.replace(/\+/g, '-').replace(/\//g, '_').replace(/=+$/, '');
  return { codeVerifier, codeChallenge };
}

/**
 * Safely parse query string and URL fragment/hash parameters.
 */
export function parseAuthUrlParams(url) {
  const params = {};
  if (!url || typeof url !== 'string') return params;

  const queryPart = url.includes('?') ? url.split('?')[1].split('#')[0] : '';
  const hashPart = url.includes('#') ? url.split('#')[1] : '';

  [queryPart, hashPart].forEach((part) => {
    if (!part) return;
    const pairs = part.split('&');
    pairs.forEach((pair) => {
      const idx = pair.indexOf('=');
      if (idx !== -1) {
        const key = decodeURIComponent(pair.slice(0, idx));
        const val = decodeURIComponent(pair.slice(idx + 1));
        if (key && !params[key]) {
          params[key] = val;
        }
      }
    });
  });

  return params;
}

/**
 * Sign in with Google:
 * 1. Web: native Firebase signInWithPopup.
 * 2. Mobile (iOS / Android in Expo Go):
 *    Direct RFC 8252 Authorization Code Flow with PKCE using the official native iOS Client ID.
 *    Redirects straight into the app (no web iframes, no sessionStorage, zero 'missing initial state' errors).
 *    Exchanges code for Google id_token and authenticates natively via signInWithCredential.
 */
export async function firebaseGoogleSignIn() {
  if (Platform.OS === 'web') {
    const provider = new GoogleAuthProvider();
    provider.setCustomParameters({ prompt: 'select_account' });
    const userCredential = await signInWithPopup(auth, provider);
    const idToken = await userCredential.user.getIdToken(true);
    return {
      user: userCredential.user,
      idToken,
    };
  }

  // 1. Generate cryptographic PKCE verifier & challenge
  const { codeVerifier, codeChallenge } = await generatePKCECodes();

  // 2. Build Google OAuth authorization URL with select_account
  const authUrl =
    `https://accounts.google.com/o/oauth2/v2/auth?` +
    `client_id=${encodeURIComponent(GOOGLE_IOS_CLIENT_ID)}` +
    `&redirect_uri=${encodeURIComponent(GOOGLE_IOS_REDIRECT_URI)}` +
    `&response_type=code` +
    `&scope=${encodeURIComponent('openid profile email')}` +
    `&code_challenge=${encodeURIComponent(codeChallenge)}` +
    `&code_challenge_method=S256` +
    `&prompt=select_account`;

  // 3. Open native authentication session
  const authResult = await WebBrowser.openAuthSessionAsync(authUrl, GOOGLE_IOS_REDIRECT_URI, {
    preferEphemeralSession: false,
    showInRecents: true,
  });

  if (authResult.type === 'cancel' || authResult.type === 'dismiss') {
    const err = new Error('Google Sign-In was cancelled.');
    err.code = 'auth/popup-closed-by-user';
    throw err;
  }

  if (authResult.type !== 'success' || !authResult.url) {
    throw new Error('Google sign-in could not be completed.');
  }

  // 4. Extract authorization code from Google's redirect response
  const params = parseAuthUrlParams(authResult.url);
  const code = params.code;

  if (!code) {
    if (params.error) {
      throw new Error(`Google Sign-In error: ${params.error_description || params.error}`);
    }
    throw new Error('Authorization code was not returned by Google.');
  }

  // 5. Exchange authorization code for Google ID token and access token
  const tokenResponse = await fetch('https://oauth2.googleapis.com/token', {
    method: 'POST',
    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
    body: new URLSearchParams({
      code,
      client_id: GOOGLE_IOS_CLIENT_ID,
      redirect_uri: GOOGLE_IOS_REDIRECT_URI,
      grant_type: 'authorization_code',
      code_verifier: codeVerifier,
    }).toString(),
  });

  const tokenData = await tokenResponse.json();

  if (!tokenResponse.ok || !tokenData.id_token) {
    const msg = tokenData.error_description || tokenData.error || 'Failed to exchange authorization code.';
    throw new Error(`Google Token Exchange: ${msg}`);
  }

  // 6. Sign in natively to Firebase Auth using official GoogleAuthProvider credential
  const credential = GoogleAuthProvider.credential(tokenData.id_token);
  const userCredential = await signInWithCredential(auth, credential);
  const firebaseIdToken = await userCredential.user.getIdToken(true);

  return {
    user: userCredential.user,
    idToken: firebaseIdToken || tokenData.id_token,
  };
}

/**
 * Sign out of Firebase Auth.
 */
export async function firebaseLogout() {
  try {
    await signOut(auth);
  } catch (_) {
    // Ignore signout cleanup errors
  }
}

/**
 * Send SMS verification code to a mobile phone number via Firebase Identity Platform REST API.
 * @param {string} phoneNumber - E.164 formatted phone number (e.g. '+639171234567')
 * @returns {Promise<{ sessionInfo: string }>}
 */
export async function firebaseSendPhoneVerificationCode(phoneNumber) {
  const url = `https://identitytoolkit.googleapis.com/v1/accounts:sendVerificationCode?key=${firebaseConfig.apiKey}`;
  const res = await fetch(url, {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ phoneNumber }),
  });

  const data = await res.json();

  if (!res.ok || !data.sessionInfo) {
    const errorMsg = data?.error?.message || 'Failed to send SMS verification code.';
    const err = new Error(errorMsg);
    err.code = data?.error?.message;
    throw err;
  }

  return { sessionInfo: data.sessionInfo };
}

/**
 * Verify SMS 6-digit OTP code with Firebase Identity Platform and complete sign-in.
 * @param {string} sessionInfo - sessionInfo returned from sendVerificationCode
 * @param {string} code - 6 digit SMS OTP
 * @returns {Promise<{ idToken: string, phoneNumber: string, isNewUser: boolean, localId: string }>}
 */
export async function firebaseVerifyPhoneCode(sessionInfo, code) {
  const url = `https://identitytoolkit.googleapis.com/v1/accounts:signInWithPhoneNumber?key=${firebaseConfig.apiKey}`;
  const res = await fetch(url, {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ sessionInfo, code: code.trim() }),
  });

  const data = await res.json();

  if (!res.ok || !data.idToken) {
    const errorMsg = data?.error?.message || 'Invalid or expired verification code.';
    const err = new Error(errorMsg);
    err.code = data?.error?.message;
    throw err;
  }

  return {
    idToken: data.idToken,
    refreshToken: data.refreshToken,
    phoneNumber: data.phoneNumber,
    localId: data.localId,
    isNewUser: data.isNewUser || false,
  };
}


