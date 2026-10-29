import { Ionicons } from '@expo/vector-icons';
import { Pressable, Text, View } from 'react-native';
import { useSafeAreaInsets } from 'react-native-safe-area-context';

import { useApp } from '../context/AppContext';
import { styles } from '../theme/styles';

/** Bottom Tab Bar — visible across primary screens */
const TAB_ITEMS = [
  { key: 'dashboard', labelKey: 'tabHome',    label: 'Home',     icon: 'home-outline',          iconActive: 'home' },
  { key: 'location',  labelKey: 'tabGps',     label: 'Live GPS', icon: 'location-outline',      iconActive: 'location' },
  { key: 'medical',   labelKey: 'tabMedical', label: 'Medical',  icon: 'heart-outline',         iconActive: 'heart' },
  { key: 'history',   labelKey: 'tabHistory', label: 'History',  icon: 'time-outline',          iconActive: 'time' },
  { key: 'inbox',     labelKey: 'tabInbox',   label: 'Inbox',    icon: 'notifications-outline', iconActive: 'notifications' },
  { key: 'support',   labelKey: 'tabSupport', label: 'Support',  icon: 'help-circle-outline',   iconActive: 'help-circle' },
];

export function BottomTabBar({ current, onPress, unread }) {
  const { isDark, colors, t } = useApp();
  const insets = useSafeAreaInsets();
  const bottomInset = Math.max(insets.bottom, 6);
  const barHeight = 52 + bottomInset;

  return (
    <View
      style={[
        styles.tabBar,
        isDark && {
          backgroundColor: colors.card,
          borderTopColor: colors.border,
        },
        { paddingBottom: bottomInset, height: barHeight },
      ]}
    >
      {TAB_ITEMS.map((item) => {
        const active = current === item.key;
        const displayLabel = t(item.labelKey) || item.label;
        return (
          <Pressable
            key={item.key}
            accessibilityRole="tab"
            accessibilityLabel={displayLabel}
            accessibilityState={{ selected: active }}
            onPress={() => onPress(item.key)}
            style={({ pressed }) => [styles.tabBarItem, pressed && { opacity: 0.7 }]}
          >
            <View style={{ position: 'relative' }}>
              <Ionicons
                name={active ? item.iconActive : item.icon}
                size={22}
                color={active ? colors.brand : colors.muted}
              />
              {item.key === 'inbox' && unread > 0 && (
                <View style={styles.tabBadge}>
                  <Text style={styles.tabBadgeText}>{unread > 9 ? '9+' : unread}</Text>
                </View>
              )}
            </View>
            <Text
              style={[
                styles.tabBarLabel,
                { color: active ? colors.brand : colors.muted },
                active && { fontWeight: '800' },
              ]}
              numberOfLines={1}
            >
              {displayLabel}
            </Text>
          </Pressable>
        );
      })}
    </View>
  );
}
