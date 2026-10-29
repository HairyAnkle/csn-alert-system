import { SafeAreaView } from 'react-native-safe-area-context';
import { Ionicons } from '@expo/vector-icons';
import { useEffect, useState } from 'react';
import {
  Animated,
  Easing,
  Modal,
  Platform,
  Pressable,
  ScrollView,
  StatusBar,
  Text,
  View,
} from 'react-native';

import { useApp } from '../context/AppContext';
import { BottomTabBar } from '../components/BottomTabBar';
import { Field } from '../components/Field';
import { HoldToConfirm } from '../components/HoldToConfirm';
import { PrimaryButton } from '../components/PrimaryButton';
import { SectionCard } from '../components/SectionCard';
import { CSN_HOTLINE, EMERGENCY_NUMBER, USE_NATIVE_DRIVER } from '../constants/app';
import { EMERGENCY_TYPES } from '../constants/data';
import { useAnimatedValue } from '../hooks/useAnimatedValue';
import { C, SOS_SIZE } from '../theme/colors';
import { styles } from '../theme/styles';
import { callEmergencyNumber, openAppSettings } from '../utils/device';
import { formatDisabilitySummary } from '../utils/format';

/* ============================================================================
 * SCREEN 4 & 5 — HOME / DASHBOARD (Table 1.4) & EMERGENCY TRIGGER FLOW (Table 1.5)
 * ========================================================================== */

export function DashboardScreen({ guardian, child, gps, coords, go, onTrigger, triggering, triggerError, clearTriggerError, unread }) {
  const { isDark, colors, t, lang } = useApp();
  const [confirmOpen, setConfirmOpen]           = useState(false);
  const [finalConfirmOpen, setFinalConfirmOpen] = useState(false);
  const [contactsOpen, setContactsOpen]         = useState(false);
  const [type, setType]                         = useState('medical_crisis');
  const [urgency, setUrgency]                   = useState('high'); // 'high' | 'low'
  const [message, setMessage]                   = useState('');
  const [shareLocation, setShareLocation]       = useState(true);

  // SOS outer ring pulse animation
  const ring1 = useAnimatedValue(1);
  const ring2 = useAnimatedValue(1);
  const ringOpacity1 = useAnimatedValue(0.6);
  const ringOpacity2 = useAnimatedValue(0.4);

  useEffect(() => {
    const pulse = (scale, opacity, delay) =>
      Animated.loop(
        Animated.sequence([
          Animated.delay(delay),
          Animated.parallel([
            Animated.timing(scale,   { toValue: 1.55, duration: 1200, easing: Easing.out(Easing.ease), useNativeDriver: USE_NATIVE_DRIVER }),
            Animated.timing(opacity, { toValue: 0,    duration: 1200, easing: Easing.out(Easing.ease), useNativeDriver: USE_NATIVE_DRIVER }),
          ]),
          Animated.parallel([
            Animated.timing(scale,   { toValue: 1, duration: 0, useNativeDriver: USE_NATIVE_DRIVER }),
            Animated.timing(opacity, { toValue: delay === 0 ? 0.6 : 0.4, duration: 0, useNativeDriver: USE_NATIVE_DRIVER }),
          ]),
        ])
      );
    const l1 = pulse(ring1, ringOpacity1, 0);
    const l2 = pulse(ring2, ringOpacity2, 600);
    l1.start(); l2.start();
    return () => { l1.stop(); l2.stop(); };
  }, [ring1, ring2, ringOpacity1, ringOpacity2]);

  const gpsInfo =
    gps.state === 'active'
      ? { text: t('activeReady'), detail: 'Live location active & secured', color: C.successDark, bg: C.successLight, icon: 'location' }
      : gps.state === 'denied'
      ? {
          text: Platform.OS === 'web' ? 'GPS Ready (Simulated on LAN)' : 'GPS Off — Tap to Enable',
          detail: Platform.OS === 'web' ? 'Using Parañaque CSN test coordinates' : 'Tap to open location settings',
          color: Platform.OS === 'web' ? C.amberDark : C.criticalDark,
          bg: Platform.OS === 'web' ? C.amberLight : C.criticalLight,
          icon: 'location-outline',
        }
      : { text: t('loading'), detail: 'Finding satellite lock', color: C.amberDark, bg: C.amberLight, icon: 'locate' };

  const closeConfirm = () => {
    setConfirmOpen(false);
    setFinalConfirmOpen(false);
    clearTriggerError();
  };

  const handleDispatch = () => {
    setFinalConfirmOpen(false);
    onTrigger({
      type,
      urgency,
      message: message.trim(),
      shareLocation,
    });
  };

  const guardianName = guardian && guardian.name ? guardian.name.split(' ')[0] : (lang === 'Filipino' ? 'Tagapag-alaga' : 'Guardian');
  const childInitials = child && child.name
    ? child.name.split(' ').map((w) => w[0]).join('').toUpperCase().slice(0, 2)
    : null;
  const disabilityBadge = formatDisabilitySummary(child);

  return (
    <SafeAreaView style={[styles.flex, { backgroundColor: colors.bg }]} edges={['top', 'left', 'right']}>
      <StatusBar barStyle="light-content" backgroundColor={C.brand} />

      {/* ── Top Caregiver Header (Table 1.4) ── */}
      <View style={styles.dashHeader}>
        {/* Child avatar thumbnail */}
        <Pressable
          onPress={() => go('medical')}
          accessibilityRole="button"
          accessibilityLabel="Open child medical profile"
          style={childInitials ? styles.childAvatar : [styles.childAvatar, { backgroundColor: C.brandDark }]}
        >
          {childInitials ? (
            <Text style={styles.childAvatarText}>{childInitials}</Text>
          ) : (
            <Ionicons name="person" size={20} color={C.white} />
          )}
        </Pressable>

        <View style={{ flex: 1 }}>
          <Text style={styles.dashGreeting}>{lang === 'Filipino' ? `Kumusta, ${guardianName} 👋` : `Hello, ${guardianName} 👋`}</Text>
          <Text style={styles.dashSubGreeting}>Parañaque CSN Caregiver</Text>
        </View>

        {/* Notification bell with badge count */}
        <Pressable
          onPress={() => go('inbox')}
          accessibilityRole="button"
          accessibilityLabel={`Notifications${unread ? `, ${unread} unread` : ''}`}
          style={styles.bellBtn}
        >
          <Ionicons name="notifications" size={23} color={C.white} />
          {unread > 0 && (
            <View style={styles.bellBadge}>
              <Text style={styles.bellBadgeText}>{unread > 9 ? '9+' : unread}</Text>
            </View>
          )}
        </Pressable>

        {/* Settings button (Table 1.10 access) */}
        <Pressable
          onPress={() => go('settings')}
          accessibilityRole="button"
          accessibilityLabel="App Settings"
          style={styles.bellBtn}
        >
          <Ionicons name="settings-outline" size={23} color={C.white} />
        </Pressable>
      </View>

      <ScrollView contentContainerStyle={styles.dashScroll} showsVerticalScrollIndicator={false}>

        {/* ── Child Summary Card (Table 1.4) ── */}
        <SectionCard style={styles.childCard}>
          <View style={[styles.row, { justifyContent: 'space-between', alignItems: 'flex-start', marginBottom: 10 }]}>
            <View style={{ flex: 1, marginRight: 8 }}>
              {child && child.name ? (
                <>
                  <Text style={[styles.childName, { color: colors.text }]}>{child.name}</Text>
                  <Text style={[styles.childAge, { color: colors.textSec }]}>
                    {child.age != null ? `${child.age} yrs old` : 'Age not set'}
                    {child.allergies && child.allergies !== 'None' ? ` • Allergies: ${child.allergies}` : ''}
                  </Text>
                </>
              ) : (
                <>
                  <Text style={[styles.childName, { color: colors.text }]}>{t('noChildEnrolled')}</Text>
                  <Text style={[styles.childAge, { color: colors.textSec }]}>Tap Medical Info below to add your child&apos;s details</Text>
                </>
              )}
            </View>
            {disabilityBadge ? (
              <View style={[styles.disabilityChip, isDark && { backgroundColor: colors.brandLight }]}>
                <Text style={[styles.disabilityChipText, isDark && { color: colors.brand }]}>{disabilityBadge}</Text>
              </View>
            ) : null}
          </View>

          {/* Live GPS status indicator ("GPS Ready") */}
          <View style={[styles.row, { justifyContent: 'space-between', flexWrap: 'wrap', gap: 8 }]}>
            <Pressable
              onPress={() => (gps.state === 'denied' && Platform.OS !== 'web' ? openAppSettings() : go('location'))}
              accessibilityRole="button"
              style={[styles.gpsStatusPill, { backgroundColor: gpsInfo.bg }]}
            >
              <View style={[styles.gpsDot, { backgroundColor: gpsInfo.color }]} />
              <Ionicons name={gpsInfo.icon} size={15} color={gpsInfo.color} style={{ marginRight: 4 }} />
              <Text style={[styles.gpsStatusText, { color: gpsInfo.color }]}>{gpsInfo.text}</Text>
            </Pressable>

            <Pressable
              onPress={() => go('location')}
              accessibilityRole="button"
              style={styles.mapQuickLink}
            >
              <Ionicons name="map-outline" size={14} color={colors.brand} style={{ marginRight: 4 }} />
              <Text style={[styles.mapQuickLinkText, { color: colors.brand }]}>{t('viewLiveMap')}</Text>
            </Pressable>
          </View>
        </SectionCard>

        {/* ── Quick Access Links (Table 1.4) ── */}
        <View style={styles.grid}>
          {[
            { key: 'medical',  label: t('quickMedical'),  icon: 'medkit',   color: '#005B96' },
            { key: 'history',  label: t('quickHistory'),  icon: 'time',     color: '#7C3AED' },
            { key: 'location', label: t('quickLocation'), icon: 'navigate', color: '#10B981' },
            { key: 'contacts', label: t('quickContacts'), icon: 'call',     color: '#E11D48' },
          ].map((item) => (
            <Pressable
              key={item.key}
              accessibilityRole="button"
              accessibilityLabel={item.label.replace('\n', ' ')}
              onPress={() => {
                if (item.key === 'contacts') {
                  setContactsOpen(true);
                } else {
                  go(item.key);
                }
              }}
              style={({ pressed }) => [
                styles.gridItem,
                isDark && { backgroundColor: colors.card, borderColor: colors.border, borderWidth: 1 },
                pressed && { opacity: 0.82 },
              ]}
            >
              <View style={[styles.gridIcon, { backgroundColor: item.color + '18' }]}>
                <Ionicons name={item.icon} size={24} color={item.color} />
              </View>
              <Text style={[styles.gridLabel, { color: colors.text }]}>{item.label}</Text>
            </Pressable>
          ))}
        </View>

        {/* ── Primary Action Zone: Prominent SOS Trigger in Thumb Zone (Table 1.4) ── */}
        <View style={styles.sosZone}>
          <Text style={[styles.sosZoneLabel, isDark && { color: colors.muted }]}>{t('primaryEmergencyTrigger')}</Text>

          <View style={styles.sosWrapper}>
            <Animated.View
              style={[
                styles.sosRing,
                { width: SOS_SIZE + 54, height: SOS_SIZE + 54, borderRadius: (SOS_SIZE + 54) / 2 },
                { transform: [{ scale: ring1 }], opacity: ringOpacity1 },
              ]}
            />
            <Animated.View
              style={[
                styles.sosRing,
                { width: SOS_SIZE + 32, height: SOS_SIZE + 32, borderRadius: (SOS_SIZE + 32) / 2 },
                { transform: [{ scale: ring2 }], opacity: ringOpacity2 },
              ]}
            />

            <Pressable
              onPress={() => setConfirmOpen(true)}
              accessibilityRole="button"
              accessibilityLabel="Trigger Emergency Alert"
              style={({ pressed }) => [
                styles.sosBtn,
                { width: SOS_SIZE, height: SOS_SIZE, borderRadius: SOS_SIZE / 2 },
                pressed && { backgroundColor: C.criticalDark },
              ]}
            >
              <Ionicons name="warning" size={40} color={C.white} />
              <Text style={styles.sosText}>{t('triggerEmergencyAlert')}</Text>
            </Pressable>
          </View>

          {/* Prominent Thumb-Zone Action Button */}
          <Pressable
            onPress={() => setConfirmOpen(true)}
            accessibilityRole="button"
            accessibilityLabel="Trigger Emergency Alert button"
            style={({ pressed }) => [styles.thumbZoneSosBar, pressed && { opacity: 0.9 }]}
          >
            <Ionicons name="alert-circle" size={22} color={C.white} style={{ marginRight: 8 }} />
            <Text style={styles.thumbZoneSosText}>{t('triggerEmergencyAlertBtn')}</Text>
          </Pressable>

          <Text style={[styles.sosCaution, isDark && { color: colors.textSec }]}>
            {t('sosCautionText')}
          </Text>
        </View>
      </ScrollView>

      {/* ── Bottom Tab Bar ── */}
      <BottomTabBar current="dashboard" onPress={(k) => go(k)} unread={unread} />

      {/* ── SCREEN 5: Emergency Alert Trigger Flow Modal (Table 1.5) ── */}
      <Modal visible={confirmOpen} transparent animationType="slide" onRequestClose={closeConfirm}>
        <View style={styles.modalBackdrop}>
          <ScrollView
            contentContainerStyle={{ flexGrow: 1, justifyContent: 'flex-end' }}
            keyboardShouldPersistTaps="handled"
          >
            <View style={[styles.modalSheet, isDark && { backgroundColor: colors.card }]}>
              <View style={styles.modalHandle} />

              <View style={[styles.row, { justifyContent: 'space-between', marginBottom: 8 }]}>
                <View style={styles.row}>
                  <View style={[styles.modalIconCircle, { width: 44, height: 44, borderRadius: 22, marginBottom: 0, marginRight: 10 }]}>
                    <Ionicons name="warning" size={24} color={C.critical} />
                  </View>
                  <View>
                    <Text style={[styles.modalTitle, { textAlign: 'left', marginBottom: 2 }, isDark && { color: colors.text }]}>
                      {t('dispatchEmergencyAlert')}
                    </Text>
                    <Text style={{ color: colors.textSec, fontSize: 13 }}>
                      {t('dispatchSub')}
                    </Text>
                  </View>
                </View>
                <Pressable onPress={closeConfirm} style={styles.backBtn} accessibilityLabel="Close modal">
                  <Ionicons name="close" size={24} color={C.muted} />
                </Pressable>
              </View>

              {/* 1. Alert Type Selector (Table 1.5) */}
              <Text style={[styles.label, { marginTop: 8, marginBottom: 8 }, isDark && { color: colors.text }]}>{t('selectAlertType')}</Text>
              <View style={styles.chipWrap}>
                {EMERGENCY_TYPES.map((tItem) => {
                  const active = type === tItem.key;
                  return (
                    <Pressable
                      key={tItem.key}
                      onPress={() => setType(tItem.key)}
                      accessibilityRole="radio"
                      accessibilityState={{ selected: active }}
                      style={[
                        styles.chip,
                        active
                          ? { backgroundColor: C.critical, borderColor: C.critical }
                          : (isDark && { backgroundColor: colors.bg, borderColor: colors.border }),
                      ]}
                    >
                      <Ionicons
                        name={tItem.icon}
                        size={16}
                        color={active ? C.white : colors.textSec}
                        style={{ marginRight: 6 }}
                      />
                      <Text style={[styles.chipText, active ? { color: C.white } : (isDark && { color: colors.text })]}>{tItem.label}</Text>
                    </Pressable>
                  );
                })}
              </View>

              {/* 2. Urgency Level Selector (Table 1.5) */}
              <Text style={[styles.label, { marginBottom: 8 }, isDark && { color: colors.text }]}>{t('urgencyLevel')}</Text>
              <View style={[styles.row, { gap: 10, marginBottom: 14 }]}>
                <Pressable
                  onPress={() => setUrgency('high')}
                  accessibilityRole="radio"
                  accessibilityState={{ selected: urgency === 'high' }}
                  style={[
                    styles.urgencyOption,
                    isDark && { backgroundColor: colors.bg, borderColor: colors.border },
                    urgency === 'high' && styles.urgencyOptionHighActive,
                  ]}
                >
                  <View style={styles.row}>
                    <Ionicons
                      name="flash"
                      size={18}
                      color={urgency === 'high' ? C.white : C.critical}
                      style={{ marginRight: 6 }}
                    />
                    <Text style={[styles.urgencyTitle, urgency === 'high' ? { color: C.white } : { color: colors.text }]}>
                      {t('highUrgency')}
                    </Text>
                  </View>
                  <Text style={[styles.urgencySub, urgency === 'high' ? { color: 'rgba(255,255,255,0.9)' } : { color: colors.textSec }]}>
                    {t('highUrgencySub')}
                  </Text>
                </Pressable>

                <Pressable
                  onPress={() => setUrgency('low')}
                  accessibilityRole="radio"
                  accessibilityState={{ selected: urgency === 'low' }}
                  style={[
                    styles.urgencyOption,
                    isDark && { backgroundColor: colors.bg, borderColor: colors.border },
                    urgency === 'low' && styles.urgencyOptionLowActive,
                  ]}
                >
                  <View style={styles.row}>
                    <Ionicons
                      name="alert-circle"
                      size={18}
                      color={urgency === 'low' ? C.white : C.amberDark}
                      style={{ marginRight: 6 }}
                    />
                    <Text style={[styles.urgencyTitle, urgency === 'low' ? { color: C.white } : { color: colors.text }]}>
                      {t('lowUrgency')}
                    </Text>
                  </View>
                  <Text style={[styles.urgencySub, urgency === 'low' ? { color: 'rgba(255,255,255,0.9)' } : { color: colors.textSec }]}>
                    {t('lowUrgencySub')}
                  </Text>
                </Pressable>
              </View>

              {/* 3. Optional Message Field (Table 1.5) */}
              <Field
                label={t('optionalMessage')}
                value={message}
                onChangeText={setMessage}
                placeholder="e.g. Child having sensory meltdown near Puregold Sucat, needs quiet transport..."
                multiline
                leftIcon="chatbox-ellipses-outline"
              />

              {/* 4. Location Toggle (Table 1.5) */}
              <Pressable
                onPress={() => setShareLocation((v) => !v)}
                accessibilityRole="switch"
                accessibilityState={{ checked: shareLocation }}
                style={[styles.locationToggleRow, isDark && { backgroundColor: colors.bg, borderColor: colors.border }]}
              >
                <View style={[styles.row, { flex: 1, marginRight: 12 }]}>
                  <View style={[styles.gridIcon, { width: 40, height: 40, borderRadius: 20, marginBottom: 0, marginRight: 10, backgroundColor: shareLocation ? C.successLight : (isDark ? colors.border : C.border) }]}>
                    <Ionicons name={shareLocation ? 'location' : 'location-outline'} size={20} color={shareLocation ? C.successDark : C.muted} />
                  </View>
                  <View style={{ flex: 1 }}>
                    <Text style={{ color: colors.text, fontSize: 15, fontWeight: '700' }}>
                      {t('shareGpsLabel')}
                    </Text>
                    <Text style={{ color: colors.textSec, fontSize: 12, marginTop: 2 }}>
                      {shareLocation
                        ? t('shareGpsSubOn')
                        : t('shareGpsSubOff')}
                    </Text>
                  </View>
                </View>
                <View style={[styles.toggleTrack, shareLocation && styles.toggleTrackActive]}>
                  <View style={[styles.toggleThumb, shareLocation && styles.toggleThumbActive]} />
                </View>
              </Pressable>

              {/* Error banner */}
              {!!triggerError && (
                <View style={[styles.errorBanner, { marginTop: 12 }]} accessibilityLiveRegion="assertive">
                  <Ionicons name="alert-circle" size={20} color={C.critical} />
                  <Text style={styles.errorBannerText}>
                    {triggerError} If this keeps failing, call {EMERGENCY_NUMBER} now.
                  </Text>
                </View>
              )}

              {/* 5. Trigger Actions: 1s Hold-to-Confirm OR Send Alert with Confirmation Dialog */}
              <View style={{ marginTop: 14 }}>
                <HoldToConfirm
                  busy={triggering}
                  label={t('holdToSend')}
                  onConfirm={handleDispatch}
                />

                <PrimaryButton
                  title={t('sendAlertConfirm')}
                  tone="outline"
                  icon="paper-plane-outline"
                  onPress={() => setFinalConfirmOpen(true)}
                  disabled={triggering}
                  style={{ marginTop: 10 }}
                />

                <PrimaryButton
                  title={t('cancelBtn')}
                  tone="ghost"
                  onPress={closeConfirm}
                  style={{ marginTop: 6 }}
                  disabled={triggering}
                />
              </View>
            </View>
          </ScrollView>
        </View>
      </Modal>

      {/* Final Confirmation Dialog for "Send Alert" tap */}
      <Modal visible={finalConfirmOpen} transparent animationType="fade" onRequestClose={() => setFinalConfirmOpen(false)}>
        <View style={styles.modalBackdrop}>
          <View style={[styles.modalSheet, isDark && { backgroundColor: colors.card }]}>
            <View style={styles.modalHandle} />
            <View style={styles.modalIconCircle}>
              <Ionicons name="paper-plane" size={30} color={C.critical} />
            </View>
            <Text style={[styles.modalTitle, isDark && { color: colors.text }]}>{t('confirmDispatchTitle')}</Text>
            <Text style={[styles.modalBody, isDark && { color: colors.textSec }]}>
              You are sending a <Text style={{ fontWeight: '800', color: C.critical }}>{urgency.toUpperCase()} URGENCY</Text>{' '}
              alert ({(EMERGENCY_TYPES.find((item) => item.key === type) || {}).label}) to Parañaque CSN Center
              {urgency === 'high' ? ' and DRRMO Ambulance Dispatch' : ''}.
            </Text>
            <PrimaryButton
              title={t('confirmDispatchBtn')}
              tone="critical"
              icon="warning"
              loading={triggering}
              onPress={handleDispatch}
            />
            <PrimaryButton
              title={t('goBackBtn')}
              tone="ghost"
              onPress={() => setFinalConfirmOpen(false)}
              style={{ marginTop: 8 }}
            />
          </View>
        </View>
      </Modal>

      {/* Quick Emergency Contacts Modal (from Dashboard Quick Access) */}
      <Modal visible={contactsOpen} transparent animationType="slide" onRequestClose={() => setContactsOpen(false)}>
        <View style={styles.modalBackdrop}>
          <View style={[styles.modalSheet, isDark && { backgroundColor: colors.card }]}>
            <View style={styles.modalHandle} />
            <Text style={[styles.modalTitle, isDark && { color: colors.text }]}>{t('emergencyContactsTitle')}</Text>
            <Text style={[styles.modalBody, isDark && { color: colors.textSec }]}>{t('emergencyContactsSub')}</Text>

            {[
              {
                name: 'National Emergency Hotline',
                role: 'Police, Fire, Medical Dispatch',
                phone: EMERGENCY_NUMBER,
                color: C.critical,
                icon: 'call',
              },
              {
                name: 'Parañaque DRRMO / Rescue',
                role: '24/7 City Ambulance & Disaster Response',
                phone: '(02) 8820-PQUE',
                color: C.amberDark,
                icon: 'medkit',
              },
              {
                name: 'Parañaque CSN Center',
                role: 'CSN Center Support Desk',
                phone: CSN_HOTLINE,
                color: C.brand,
                icon: 'shield-checkmark',
              },
              ...(child && child.emergency_contact_phone
                ? [
                    {
                      name: child.emergency_contact_name || 'Backup Family Contact',
                      role: 'Registered Secondary Emergency Contact',
                      phone: child.emergency_contact_phone,
                      color: C.successDark,
                      icon: 'people',
                    },
                  ]
                : []),
            ].map((c) => (
              <Pressable
                key={c.name}
                onPress={() => callEmergencyNumber(c.phone)}
                accessibilityRole="button"
                style={[styles.contactModalRow, isDark && { backgroundColor: colors.bg, borderColor: colors.border }]}
              >
                <View style={[styles.supportIconCircle, { backgroundColor: c.color + '18' }]}>
                  <Ionicons name={c.icon} size={20} color={c.color} />
                </View>
                <View style={{ flex: 1 }}>
                  <Text style={{ color: colors.text, fontSize: 15, fontWeight: '800' }}>{c.name}</Text>
                  <Text style={{ color: colors.textSec, fontSize: 12 }}>{c.role} • {c.phone}</Text>
                </View>
                <Ionicons name="call-outline" size={20} color={c.color} />
              </Pressable>
            ))}

            <PrimaryButton
              title={t('editContactsBtn')}
              tone="outline"
              icon="create-outline"
              onPress={() => {
                setContactsOpen(false);
                go('medical');
              }}
              style={{ marginTop: 12 }}
            />
            <PrimaryButton
              title={t('closeBtn')}
              tone="ghost"
              onPress={() => setContactsOpen(false)}
              style={{ marginTop: 6 }}
            />
          </View>
        </View>
      </Modal>
    </SafeAreaView>
  );
}
