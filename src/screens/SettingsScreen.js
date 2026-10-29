import { SafeAreaView } from 'react-native-safe-area-context';
import { Ionicons } from '@expo/vector-icons';
import { useState } from 'react';
import {
  Modal,
  Pressable,
  ScrollView,
  StatusBar,
  Text,
  View,
} from 'react-native';

import { BottomTabBar } from '../components/BottomTabBar';
import { PrimaryButton } from '../components/PrimaryButton';
import { SectionCard } from '../components/SectionCard';
import { useApp } from '../context/AppContext';
import { C } from '../theme/colors';
import { styles } from '../theme/styles';

/* ============================================================================
 * SCREEN 10 — APP SETTINGS (Table 1.10)
 * ========================================================================== */

export function SettingsScreen({ guardian, go, onLogout, showToast, unread }) {
  const { isDark, colors, updateSettings, lang, pushNotifications, t } = useApp();
  const [signOutModal, setSignOutModal] = useState(false);

  const pushOn = pushNotifications;

  return (
    <SafeAreaView style={[styles.flex, { backgroundColor: colors.bg }]} edges={['top', 'left', 'right']}>
      <StatusBar barStyle="light-content" backgroundColor={C.brand} />

      <View style={[styles.innerHeader, styles.row]}>
        <Pressable onPress={() => go('dashboard')} style={[styles.backBtn, { marginLeft: -8, marginRight: 4 }]}>
          <Ionicons name="arrow-back" size={24} color={C.white} />
        </Pressable>
        <Text style={styles.innerHeaderTitle} accessibilityRole="header">
          {t('settingsTitle')}
        </Text>
      </View>

      <ScrollView contentContainerStyle={styles.supportScroll} showsVerticalScrollIndicator={false}>
        {/* Account summary card */}
        <SectionCard>
          <View style={styles.row}>
            <View style={[styles.childAvatar, { backgroundColor: C.brand, marginRight: 12 }]}>
              <Ionicons name="person" size={20} color={C.white} />
            </View>
            <View style={{ flex: 1 }}>
              <Text style={[{ fontSize: 17, fontWeight: '800' }, { color: colors.text }]}>
                {(guardian && guardian.name) || (lang === 'Filipino' ? 'Rehistradong Tagapag-alaga' : 'Registered Caregiver')}
              </Text>
              <Text style={[{ fontSize: 13 }, { color: colors.textSec }]}>
                {(guardian && guardian.email) || t('accountSummary')}
              </Text>
            </View>
          </View>
        </SectionCard>

        {/* Preference Toggles (Table 1.10) */}
        <Text style={[styles.sectionHeading, { color: colors.text }]}>
          {t('preferencesHeading')}
        </Text>
        <SectionCard>
          {/* 1. Light / Dark Mode Theme Toggle */}
          <Pressable
            onPress={() => {
              const next = isDark ? 'light' : 'dark';
              updateSettings({ theme: next });
              showToast(next === 'dark' ? 'Dark Mode active.' : 'Light Mode active.');
            }}
            accessibilityRole="switch"
            accessibilityState={{ checked: isDark }}
            style={styles.settingRow}
          >
            <View style={[styles.supportIconCircle, { backgroundColor: isDark ? '#334155' : C.brandLight }]}>
              <Ionicons name={isDark ? 'moon' : 'sunny'} size={20} color={isDark ? '#38BDF8' : C.brand} />
            </View>
            <View style={{ flex: 1, marginRight: 10 }}>
              <Text style={[{ fontSize: 15, fontWeight: '700' }, { color: colors.text }]}>
                {t('appearanceLabel')}
              </Text>
              <Text style={[{ fontSize: 12, marginTop: 2 }, { color: colors.textSec }]}>
                {isDark ? 'Dark Mode active (reduces glare at night)' : t('appearanceSub')}
              </Text>
            </View>
            <View style={[styles.toggleTrack, isDark && styles.toggleTrackActive]}>
              <View style={[styles.toggleThumb, isDark && styles.toggleThumbActive]} />
            </View>
          </Pressable>

          <View style={[styles.settingDivider, isDark && { backgroundColor: colors.border }]} />

          {/* 2. Language Selector (English / Filipino) */}
          <View style={styles.settingRow}>
            <View style={[styles.supportIconCircle, { backgroundColor: isDark ? '#334155' : C.brandLight }]}>
              <Ionicons name="language" size={20} color={isDark ? '#38BDF8' : C.brand} />
            </View>
            <View style={{ flex: 1, marginRight: 10 }}>
              <Text style={[{ fontSize: 15, fontWeight: '700' }, { color: colors.text }]}>
                {t('languageLabel')}
              </Text>
              <Text style={[{ fontSize: 12, marginTop: 2 }, { color: colors.textSec }]}>
                {t('languageSub')}
              </Text>
            </View>
            <View style={styles.langPillGroup}>
              {['English', 'Filipino'].map((l) => {
                const active = lang === l;
                return (
                  <Pressable
                    key={l}
                    onPress={() => {
                      updateSettings({ language: l });
                      showToast(l === 'Filipino' ? 'Binago ang wika sa Filipino.' : 'Language set to English.');
                    }}
                    style={[styles.langPill, active && styles.langPillActive]}
                  >
                    <Text style={[styles.langPillText, active && styles.langPillTextActive]}>{l}</Text>
                  </Pressable>
                );
              })}
            </View>
          </View>

          <View style={[styles.settingDivider, isDark && { backgroundColor: colors.border }]} />

          {/* 3. Push Notifications Toggle */}
          <Pressable
            onPress={() => {
              const next = !pushOn;
              updateSettings({ pushNotifications: next });
              showToast(next ? 'Push notifications enabled.' : 'Push notifications muted.');
            }}
            accessibilityRole="switch"
            accessibilityState={{ checked: pushOn }}
            style={styles.settingRow}
          >
            <View style={[styles.supportIconCircle, { backgroundColor: pushOn ? C.successLight : C.border }]}>
              <Ionicons name={pushOn ? 'notifications' : 'notifications-off'} size={20} color={pushOn ? C.successDark : C.muted} />
            </View>
            <View style={{ flex: 1, marginRight: 10 }}>
              <Text style={{ color: colors.text, fontSize: 15, fontWeight: '700' }}>
                {t('pushLabel')}
              </Text>
              <Text style={{ color: colors.textSec, fontSize: 12, marginTop: 2 }}>
                {pushOn
                  ? 'Receiving responder ETA updates & CSN drill alerts'
                  : 'Push alerts are currently muted'}
              </Text>
            </View>
            <View style={[styles.toggleTrack, pushOn && styles.toggleTrackActive]}>
              <View style={[styles.toggleThumb, pushOn && styles.toggleThumbActive]} />
            </View>
          </Pressable>
        </SectionCard>

        {/* Quick Navigation to Support & Help */}
        <SectionCard>
          <Pressable
            onPress={() => go('support')}
            style={[styles.row, { justifyContent: 'space-between' }]}
          >
            <View style={styles.row}>
              <View style={[styles.supportIconCircle, { backgroundColor: isDark ? '#334155' : C.brandLight }]}>
                <Ionicons name="help-buoy" size={20} color={isDark ? '#38BDF8' : C.brand} />
              </View>
              <View>
                <Text style={[{ fontSize: 15, fontWeight: '700' }, { color: colors.text }]}>
                  {t('supportTitle')}
                </Text>
                <Text style={[{ fontSize: 12 }, { color: colors.textSec }]}>
                  FAQs, Safety Resources, Video Tutorials & Live Chat
                </Text>
              </View>
            </View>
            <Ionicons name="chevron-forward" size={20} color={colors.muted} />
          </Pressable>
        </SectionCard>

        {/* Account Action: "Sign Out" button with Confirmation Modal (Table 1.10) */}
        <Pressable
          onPress={() => setSignOutModal(true)}
          accessibilityRole="button"
          accessibilityLabel="Sign Out"
          style={({ pressed }) => [styles.signOutBtn, pressed && { opacity: 0.8 }]}
        >
          <Ionicons name="log-out-outline" size={20} color={C.critical} style={{ marginRight: 8 }} />
          <Text style={styles.signOutBtnText}>
            {t('signOutLabel')}
          </Text>
        </Pressable>
      </ScrollView>

      {/* Sign Out Confirmation Modal (Table 1.10) */}
      <Modal visible={signOutModal} transparent animationType="fade" onRequestClose={() => setSignOutModal(false)}>
        <View style={styles.modalBackdrop}>
          <View style={[styles.modalSheet, isDark && { backgroundColor: colors.card }]}>
            <View style={styles.modalHandle} />
            <View style={styles.modalIconCircle}>
              <Ionicons name="log-out-outline" size={32} color={C.critical} />
            </View>
            <Text style={[styles.modalTitle, isDark && { color: colors.text }]}>
              {t('confirmSignOutTitle')}
            </Text>
            <Text style={[styles.modalBody, isDark && { color: colors.textSec }]}>
              {t('confirmSignOutMsg')}
            </Text>
            <PrimaryButton
              title={t('signOutLabel')}
              tone="critical"
              icon="log-out-outline"
              onPress={() => {
                setSignOutModal(false);
                onLogout();
              }}
            />
            <PrimaryButton
              title={t('cancelBtn')}
              tone="ghost"
              onPress={() => setSignOutModal(false)}
              style={{ marginTop: 8 }}
            />
          </View>
        </View>
      </Modal>

      <BottomTabBar current="support" onPress={(k) => go(k)} unread={unread} />
    </SafeAreaView>
  );
}
