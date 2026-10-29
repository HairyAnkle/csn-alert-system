import { useState } from 'react';
import { Animated } from 'react-native';

/**
 * Cross-platform replacement for `useRef(new Animated.Value(x)).current`.
 * Creates the Animated.Value once (lazy state init) so it is safe to read during render
 * under the React Compiler rules, and works on react-native-web (which lacks RN's built-in hook).
 */
export function useAnimatedValue(initialValue) {
  const [value] = useState(() => new Animated.Value(initialValue));
  return value;
}

