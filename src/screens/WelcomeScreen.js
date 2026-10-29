import { SafeAreaView } from 'react-native-safe-area-context';
import { Ionicons } from '@expo/vector-icons';
import { useEffect } from 'react';
import {
  Animated,
  Easing,
  Pressable,
  StatusBar,
  Text,
  View,
} from 'react-native';

import { USE_NATIVE_DRIVER } from '../constants/app';
import { useAnimatedValue } from '../hooks/useAnimatedValue';
import { C } from '../theme/colors';
import { styles } from '../theme/styles';

/* ============================================================================
 * SCREEN 1 — WELCOME SCREEN (Table 1.0)
 * ========================================================================== */

export function WelcomeScreen({ go }) {
  const fadeIn = useAnimatedValue(0);
  const slideY = useAnimatedValue(30);

  useEffect(() => {
    Animated.parallel([
      Animated.timing(fadeIn, { toValue: 1, duration: 600, useNativeDriver: USE_NATIVE_DRIVER }),
      Animated.timing(slideY, { toValue: 0, duration: 600, easing: Easing.out(Easing.quad), useNativeDriver: USE_NATIVE_DRIVER }),
    ]).start();
  }, [fadeIn, slideY]);

  return (
    <SafeAreaView style={[styles.flex, { backgroundColor: C.brand }]}>
      <StatusBar barStyle="light-content" backgroundColor={C.brand} />
      <View style={styles.welcomeBody}>
        <Animated.View style={{ alignItems: 'center', opacity: fadeIn, transform: [{ translateY: slideY }] }}>
          {/* CSN Center Branding Logo */}
          <View style={styles.logoCircle}>
            <Ionicons name="shield-checkmark" size={56} color={C.brand} />
          </View>

          {/* Table 1.0 Branding Title */}
          <Text style={styles.welcomeTitle}>Parañaque CSN Alert App</Text>
          <Text style={styles.welcomeSubtitle}>Children with Special Needs Emergency System</Text>

          {/* Divider accent */}
          <View style={styles.welcomeDivider} />

          <Text style={styles.welcomeTagline}>
            Fast, dignified emergency response and live GPS protection for children with special needs.
          </Text>
        </Animated.View>
      </View>

      <Animated.View style={[styles.welcomeActions, { opacity: fadeIn }]}>
        {/* Login Button (Table 1.0) */}
        <Pressable
          accessibilityRole="button"
          accessibilityLabel="Login"
          onPress={() => go('login')}
          style={({ pressed }) => [styles.welcomeLoginBtn, pressed && { opacity: 0.88 }]}
        >
          <Text style={styles.welcomeLoginText}>Login</Text>
        </Pressable>

        {/* Sign Up Button (Table 1.0) */}
        <Pressable
          accessibilityRole="button"
          accessibilityLabel="Sign Up"
          onPress={() => go('signup')}
          style={({ pressed }) => [styles.welcomeSignupBtn, pressed && { opacity: 0.75 }]}
        >
          <Text style={styles.welcomeSignupText}>Sign Up</Text>
        </Pressable>

      </Animated.View>
    </SafeAreaView>
  );
}
