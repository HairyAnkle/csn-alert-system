import { View } from 'react-native';

import { useApp } from '../context/AppContext';
import { styles } from '../theme/styles';

/** Section card wrapper with automatic dark mode support */
export function SectionCard({ children, style }) {
  const { isDark, colors } = useApp();
  return (
    <View
      style={[
        styles.sectionCard,
        isDark && {
          backgroundColor: colors.card,
          borderColor: colors.border,
          borderWidth: 1,
        },
        style,
      ]}
    >
      {children}
    </View>
  );
}
