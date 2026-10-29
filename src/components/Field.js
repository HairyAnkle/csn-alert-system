import { Ionicons } from '@expo/vector-icons';
import { Text, TextInput, View } from 'react-native';

import { useApp } from '../context/AppContext';
import { C } from '../theme/colors';
import { styles } from '../theme/styles';

/** Styled form field with label, optional error, and optional left icon */
export function Field({ label, value, onChangeText, error, multiline, leftIcon, rightNode, ...rest }) {
  const { isDark, colors } = useApp();
  const safeValue = value == null ? '' : String(value);
  return (
    <View style={{ marginBottom: 16 }}>
      {!!label && <Text style={[styles.label, isDark && { color: colors.text }]}>{label}</Text>}
      <View
        style={[
          styles.inputWrap,
          isDark && { backgroundColor: colors.card, borderColor: colors.border },
          !!error && { borderColor: C.critical },
        ]}
      >
        {leftIcon ? (
          <Ionicons name={leftIcon} size={20} color={isDark ? colors.muted : C.muted} style={{ marginRight: 10 }} />
        ) : null}
        <TextInput
          value={safeValue}
          onChangeText={onChangeText}
          multiline={multiline}
          accessibilityLabel={label}
          placeholderTextColor={isDark ? colors.muted : C.muted}
          style={[
            styles.inputInner,
            isDark && { color: colors.text },
            multiline && { minHeight: 96, textAlignVertical: 'top', paddingTop: 12 },
          ]}
          {...rest}
        />
        {rightNode || null}
      </View>
      {!!error && <Text style={styles.errorText}>{error}</Text>}
    </View>
  );
}
