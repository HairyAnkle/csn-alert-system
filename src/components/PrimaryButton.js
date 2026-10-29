import { Ionicons } from '@expo/vector-icons';
import { ActivityIndicator, Pressable, Text, View } from 'react-native';

import { C } from '../theme/colors';
import { styles } from '../theme/styles';

/* ============================================================================
 * SMALL SHARED COMPONENTS
 * ========================================================================== */

export function PrimaryButton({ title, onPress, loading, disabled, tone = 'brand', icon, style }) {
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
