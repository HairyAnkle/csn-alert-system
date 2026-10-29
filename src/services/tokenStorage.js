/* Persistent storage helper for token across app launches */
let SecureStore = null;
try {
  SecureStore = require('expo-secure-store');
} catch {
  // Graceful fallback if expo-secure-store native module is unlinked
}

const STORAGE_KEY_TOKEN = 'csn_guardian_token';

export async function saveStoredToken(t) {
  try {
    if (SecureStore && SecureStore.setItemAsync) {
      await SecureStore.setItemAsync(STORAGE_KEY_TOKEN, t);
      return;
    }
  } catch {}
  try {
    if (typeof localStorage !== 'undefined') {
      localStorage.setItem(STORAGE_KEY_TOKEN, t);
    }
  } catch {}
}

export async function getStoredToken() {
  try {
    if (SecureStore && SecureStore.getItemAsync) {
      const val = await SecureStore.getItemAsync(STORAGE_KEY_TOKEN);
      if (val) return val;
    }
  } catch {}
  try {
    if (typeof localStorage !== 'undefined') {
      return localStorage.getItem(STORAGE_KEY_TOKEN);
    }
  } catch {}
  return null;
}

export async function removeStoredToken() {
  try {
    if (SecureStore && SecureStore.deleteItemAsync) {
      await SecureStore.deleteItemAsync(STORAGE_KEY_TOKEN);
      return;
    }
  } catch {}
  try {
    if (typeof localStorage !== 'undefined') {
      localStorage.removeItem(STORAGE_KEY_TOKEN);
    }
  } catch {}
}
