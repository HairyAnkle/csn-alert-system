import { Ionicons } from '@expo/vector-icons';
import { useEffect } from 'react';
import { Animated, Text } from 'react-native';

import { USE_NATIVE_DRIVER } from '../constants/app';
import { useAnimatedValue } from '../hooks/useAnimatedValue';
import { C } from '../theme/colors';
import { styles } from '../theme/styles';

export function Toast({ toast }) {
  const opacity = useAnimatedValue(0);
  useEffect(() => {
    if (!toast) return;
    opacity.setValue(0);
    Animated.sequence([
      Animated.timing(opacity, { toValue: 1, duration: 200, useNativeDriver: USE_NATIVE_DRIVER }),
      Animated.delay(2400),
      Animated.timing(opacity, { toValue: 0, duration: 300, useNativeDriver: USE_NATIVE_DRIVER }),
    ]).start();
  }, [toast, opacity]);
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
