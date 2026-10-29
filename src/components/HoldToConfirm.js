import { Ionicons } from '@expo/vector-icons';
import { useRef } from 'react';
import {
  ActivityIndicator,
  Animated,
  Easing,
  Pressable,
  Text,
  View,
} from 'react-native';

import { HOLD_TO_CONFIRM_MS } from '../constants/app';
import { useAnimatedValue } from '../hooks/useAnimatedValue';
import { C } from '../theme/colors';
import { styles } from '../theme/styles';

/** HoldToConfirm — 1-second progress-bar hold button (Accidental Trigger Prevention) */
export function HoldToConfirm({ onConfirm, busy, label = 'HOLD 1s TO SEND ALERT' }) {
  const progress = useAnimatedValue(0);
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
      accessibilityLabel="Press and hold for 1 second to send the emergency alert"
      accessibilityHint="Hold for one second to prevent accidental triggers"
      style={styles.holdBtn}
    >
      <Animated.View style={[styles.holdFill, { width }]} />
      <View style={styles.row}>
        {busy ? (
          <ActivityIndicator color={C.white} />
        ) : (
          <>
            <Ionicons name="hand-left" size={22} color={C.white} style={{ marginRight: 8 }} />
            <Text style={styles.holdText}>{label}</Text>
          </>
        )}
      </View>
    </Pressable>
  );
}
