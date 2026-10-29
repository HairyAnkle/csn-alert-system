import { Text, View } from 'react-native';

import { STATUS_STYLE } from '../constants/data';
import { styles } from '../theme/styles';

export function StatusBadge({ status }) {
  const s = STATUS_STYLE[status] || { label: status || 'Unknown', bg: '#E5E7EB', fg: '#374151' };
  return (
    <View style={[styles.badge, { backgroundColor: s.bg }]}>
      <Text style={[styles.badgeText, { color: s.fg }]}>{s.label}</Text>
    </View>
  );
}
