import { Alert, Linking, Platform } from 'react-native';

import { EMERGENCY_NUMBER } from '../constants/app';

export function callEmergencyNumber(num = EMERGENCY_NUMBER) {
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

export function openAppSettings() {
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
