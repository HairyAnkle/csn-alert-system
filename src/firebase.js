import { initializeApp, getApps, getApp } from 'firebase/app';
import {
  getAuth,
  signInWithEmailAndPassword,
  createUserWithEmailAndPassword,
  signInWithPopup,
  GoogleAuthProvider,
  signOut,
  sendPasswordResetEmail,
  updateProfile,
} from 'firebase/auth';
import { Platform } from 'react-native';

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

// Initialize Firebase Auth
export const auth = getAuth(app);

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

/**
 * Sign in with Google Popup (Web environment).
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
  throw new Error('Native Google sign-in requires Google credentials or direct web exchange.');
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

