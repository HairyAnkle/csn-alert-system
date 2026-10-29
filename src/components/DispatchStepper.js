import { Ionicons } from '@expo/vector-icons';
import { useEffect } from 'react';
import { Animated, Text, View } from 'react-native';

import { useApp } from '../context/AppContext';
import { USE_NATIVE_DRIVER } from '../constants/app';
import { useAnimatedValue } from '../hooks/useAnimatedValue';
import { C } from '../theme/colors';
import { styles } from '../theme/styles';

const DISPATCH_STEPS = [
  { id: 1, label: 'Alert Sent',           icon: 'paper-plane',     doneStatuses: ['pending','responding','resolved','cancelled'] },
  { id: 2, label: 'CSN Acknowledged',     icon: 'checkmark-circle',doneStatuses: ['responding','resolved','cancelled'] },
  { id: 3, label: 'Responder En Route',   icon: 'car-sport',       doneStatuses: ['resolved'] },
  { id: 4, label: 'Arrived',              icon: 'flag',            doneStatuses: ['resolved'] },
];

export function DispatchStepper({ status }) {
  const { isDark, colors } = useApp();
  const blink = useAnimatedValue(1);
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
        const activeIdx = DISPATCH_STEPS.findIndex((s) => !s.doneStatuses.includes(status));
        const isActive = idx === activeIdx && status !== 'cancelled';
        const isLast = idx === DISPATCH_STEPS.length - 1;

        return (
          <View key={step.id} style={styles.stepRow}>
            <View style={{ alignItems: 'center' }}>
              {isActive ? (
                <Animated.View style={[styles.stepIcon, styles.stepIconActive, isDark && { backgroundColor: colors.brand }, { opacity: blink }]}>
                  <Ionicons name={step.icon} size={18} color={isDark ? colors.card : C.white} />
                </Animated.View>
              ) : (
                <View style={[styles.stepIcon, done ? styles.stepIconDone : [styles.stepIconPending, isDark && { backgroundColor: colors.border }]]}>
                  <Ionicons
                    name={done ? 'checkmark' : step.icon}
                    size={18}
                    color={done ? C.white : colors.muted}
                  />
                </View>
              )}
              {!isLast && (
                <View style={[styles.stepLine, isDark && { backgroundColor: colors.border }, done && { backgroundColor: colors.success }]} />
              )}
            </View>
            <Text
              style={[
                styles.stepLabel,
                { color: colors.muted },
                done && { color: colors.text, fontWeight: '700' },
                isActive && { color: colors.brand, fontWeight: '800' },
              ]}
            >
              {step.label}
            </Text>
          </View>
        );
      })}
    </View>
  );
}
