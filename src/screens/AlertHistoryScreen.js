import { SafeAreaView } from 'react-native-safe-area-context';
import { Ionicons } from '@expo/vector-icons';
import { useCallback, useEffect, useState } from 'react';
import {
  ActivityIndicator,
  Modal,
  Pressable,
  ScrollView,
  StatusBar,
  Text,
  View,
} from 'react-native';

import { useApp } from '../context/AppContext';
import { BottomTabBar } from '../components/BottomTabBar';
import { PrimaryButton } from '../components/PrimaryButton';
import { StatusBadge } from '../components/StatusBadge';
import { EMERGENCY_TYPES } from '../constants/data';
import { api } from '../services/api';
import { C } from '../theme/colors';
import { styles } from '../theme/styles';
import { formatDateTime } from '../utils/format';
import { openExternalMap } from '../utils/geo';

/* ============================================================================
 * SCREEN 8 — ALERT HISTORY (Table 1.8) & SCREEN 9 — NOTIFICATIONS INBOX (Table 1.9)
 * ========================================================================== */

/** Fetches alert history + announcements. Pure (no React state) so it can be reused safely. */
async function fetchHistoryData(token) {
  try {
    const [a, n] = await Promise.all([
      api('/emergencies',   { token }),
      api('/announcements', { token }),
    ]);
    return {
      alerts: (a && a.emergencies) || [],
      announcements: (n && n.announcements) || [],
    };
  } catch (err) {
    return { error: err.message };
  }
}

export function AlertHistoryScreen({ token, child, go, initialTab, showToast, seenIds, onMarkRead, dismissedIds, onDismissAnnouncement, unread }) {
  const { isDark, colors, t } = useApp();
  const [tab, setTab]                     = useState(initialTab || 'alerts');
  const [alerts, setAlerts]               = useState([]);
  const [announcements, setAnnouncements] = useState([]);
  const [loading, setLoading]             = useState(true);
  const [error, setError]                 = useState('');
  const [expandedAlertId, setExpandedAlertId] = useState(null);
  const [selectedAnnouncement, setSelectedAnnouncement] = useState(null);

  // Sync the active tab when navigated here with a different initialTab (adjust state during render)
  const [prevInitialTab, setPrevInitialTab] = useState(initialTab);
  if (initialTab !== prevInitialTab) {
    setPrevInitialTab(initialTab);
    if (initialTab) setTab(initialTab);
  }

  const applyHistory = useCallback((result) => {
    if (result.error) {
      setError(result.error);
    } else {
      setAlerts(result.alerts);
      if (result.alerts.length > 0) {
        setExpandedAlertId((cur) => (cur == null ? result.alerts[0].id : cur));
      }
      setAnnouncements(result.announcements);
    }
    setLoading(false);
  }, []);

  // Manual retry ("Try again" button)
  const load = useCallback(() => {
    setLoading(true);
    setError('');
    return fetchHistoryData(token).then(applyHistory);
  }, [token, applyHistory]);

  // Initial load
  useEffect(() => {
    let active = true;
    fetchHistoryData(token).then((result) => {
      if (active) applyHistory(result);
    });
    return () => {
      active = false;
    };
  }, [token, applyHistory]);

  const typeInfo = (key) =>
    EMERGENCY_TYPES.find((item) => item.key === key) || { label: 'General Emergency', icon: 'warning' };

  const statusBorderColor = (s) =>
    s === 'pending'    ? colors.amber :
    s === 'responding' ? colors.brand :
    s === 'resolved'   ? colors.success :
    colors.muted;

  const visibleAnnouncements = announcements.filter((n) => !dismissedIds.includes(n.id));

  const openAnnouncementModal = (item) => {
    setSelectedAnnouncement(item);
    onMarkRead([item.id]);
  };

  return (
    <SafeAreaView style={[styles.flex, { backgroundColor: colors.bg }]} edges={['top', 'left', 'right']}>
      <StatusBar barStyle="light-content" backgroundColor={C.brand} />

      {/* Header bar */}
      <View style={[styles.innerHeader, styles.row, { justifyContent: 'space-between' }]}>
        <Text style={styles.innerHeaderTitle} accessibilityRole="header">
          {tab === 'inbox' ? t('inboxTitle') : t('historyTitle')}
        </Text>
        {tab === 'inbox' && visibleAnnouncements.length > 0 && (
          <Pressable
            onPress={() => {
              onMarkRead(visibleAnnouncements.map((n) => n.id));
              showToast('All notifications marked as read.');
            }}
            style={styles.headerActionBtn}
          >
            <Ionicons name="checkmark-done" size={16} color={C.white} style={{ marginRight: 4 }} />
            <Text style={{ color: C.white, fontSize: 12, fontWeight: '700' }}>{t('markAllRead')}</Text>
          </Pressable>
        )}
      </View>

      {/* ── Pill-style tab switcher between Screen 8 (History) & Screen 9 (Inbox) ── */}
      <View style={[styles.pillTabContainer, { backgroundColor: colors.bg }]}>
        <View style={[styles.pillTabBar, isDark && { backgroundColor: colors.card, borderColor: colors.border, borderWidth: 1 }]}>
          {[
            { key: 'alerts', label: t('alertsTab') },
            { key: 'inbox',  label: `${t('inboxTab')}${unread > 0 ? ` (${unread})` : ''}` },
          ].map((tItem) => (
            <Pressable
              key={tItem.key}
              onPress={() => setTab(tItem.key)}
              accessibilityRole="tab"
              accessibilityState={{ selected: tab === tItem.key }}
              style={[styles.pillTab, tab === tItem.key && (isDark ? { backgroundColor: colors.border } : styles.pillTabActive)]}
            >
              <Text style={[styles.pillTabText, tab === tItem.key && { color: isDark ? colors.text : C.brand }]}>
                {tItem.label}
              </Text>
            </Pressable>
          ))}
        </View>
      </View>

      <ScrollView contentContainerStyle={styles.histScroll} showsVerticalScrollIndicator={false}>
        {loading && <ActivityIndicator color={colors.brand} style={{ marginTop: 32 }} />}

        {!loading && !!error && (
          <View style={styles.emptyBox}>
            <Ionicons name="cloud-offline" size={40} color={colors.muted} />
            <Text style={[styles.emptyText, isDark && { color: colors.textSec }]}>{error}</Text>
            <PrimaryButton title="Try again" tone="outline" onPress={load} style={{ marginTop: 12, alignSelf: 'stretch' }} />
          </View>
        )}

        {/* ── SCREEN 8: Alert History with Expandable Detail View (Table 1.8) ── */}
        {!loading && !error && tab === 'alerts' && (
          alerts.length === 0 ? (
            <View style={styles.emptyBox}>
              <Ionicons name="shield-checkmark" size={42} color={colors.muted} />
              <Text style={[styles.emptyText, isDark && { color: colors.textSec }]}>
                {t('noAlerts')}{'\n'}Stay safe!
              </Text>
            </View>
          ) : (
            alerts.map((a) => {
              const info = typeInfo(a.type);
              const isExpanded = expandedAlertId === a.id;
              const urgency = a.urgency || 'high';
              const msg = a.message || a.guardian_notes || 'No custom situation message provided.';
              const hasCoords = a.lat != null && a.lng != null;
              const notifiedList = [
                'Parañaque CSN Center Admin',
                urgency === 'high' ? 'Parañaque DRRMO / 911 Ambulance' : null,
                child && child.emergency_contact_name
                  ? `${child.emergency_contact_name} (Backup Contact)`
                  : 'Registered Emergency Contact',
                a.responder ? (a.responder.unit || a.responder.name) : null,
              ].filter(Boolean);

              return (
                <View
                  key={a.id}
                  style={[
                    styles.histCard,
                    isDark && { backgroundColor: colors.card, borderColor: colors.border, borderWidth: 1 },
                    { borderLeftColor: statusBorderColor(a.status), borderLeftWidth: 4 },
                  ]}
                >
                  <Pressable
                    onPress={() => setExpandedAlertId(isExpanded ? null : a.id)}
                    accessibilityRole="button"
                    accessibilityLabel={`${info.label}, status ${a.status}, tap to ${isExpanded ? 'collapse' : 'expand'} details`}
                  >
                    {/* Summary Header */}
                    <View style={[styles.row, { justifyContent: 'space-between', marginBottom: 6 }]}>
                      <View style={[styles.row, { flex: 1, marginRight: 8 }]}>
                        <View style={[styles.inboxIconCircle, { backgroundColor: statusBorderColor(a.status) + '18' }]}>
                          <Ionicons name={info.icon} size={16} color={statusBorderColor(a.status)} />
                        </View>
                        <View style={{ flex: 1 }}>
                          <Text style={[styles.histCardTitle, isDark && { color: colors.text }]}>{info.label}</Text>
                          <Text style={[styles.histCardMeta, isDark && { color: colors.textSec }]}>{formatDateTime(a.reported_at)}</Text>
                        </View>
                      </View>
                      <StatusBadge status={a.status} />
                    </View>

                    {/* Urgency & Expand indicator row */}
                    <View style={[styles.row, { justifyContent: 'space-between', marginTop: 4 }]}>
                      <View
                        style={[
                          styles.urgencyMiniPill,
                          { backgroundColor: urgency === 'high' ? C.criticalLight : C.amberLight },
                        ]}
                      >
                        <Text
                          style={{
                            fontSize: 11,
                            fontWeight: '800',
                            color: urgency === 'high' ? C.criticalDark : C.amberDark,
                          }}
                        >
                          {urgency === 'high' ? '⚡ HIGH URGENCY' : '• LOW URGENCY'}
                        </Text>
                      </View>

                      <View style={styles.row}>
                        <Text style={{ color: colors.brand, fontSize: 12, fontWeight: '700', marginRight: 4 }}>
                          {isExpanded ? 'Hide Details' : 'View Details'}
                        </Text>
                        <Ionicons
                          name={isExpanded ? 'chevron-up' : 'chevron-down'}
                          size={16}
                          color={colors.brand}
                        />
                      </View>
                    </View>
                  </Pressable>

                  {/* Expandable Detail View (Table 1.8: submitted message, location snapshot, contacts notified) */}
                  {isExpanded && (
                    <View style={[styles.histDetailBox, isDark && { backgroundColor: colors.bg, borderColor: colors.border }]}>
                      {/* Submitted Message */}
                      <View style={{ marginBottom: 10 }}>
                        <Text style={[styles.histDetailLabel, isDark && { color: colors.muted }]}>SUBMITTED SITUATION MESSAGE</Text>
                        <Text style={[styles.histDetailValue, isDark && { color: colors.text }]}>{msg}</Text>
                      </View>

                      {/* Location Snapshot */}
                      <View style={{ marginBottom: 10 }}>
                        <Text style={[styles.histDetailLabel, isDark && { color: colors.muted }]}>GPS LOCATION SNAPSHOT</Text>
                        <View style={[styles.row, { justifyContent: 'space-between', marginTop: 2 }]}>
                          <View style={styles.row}>
                            <Ionicons name="location" size={16} color={colors.brand} style={{ marginRight: 6 }} />
                            <Text style={[styles.histDetailValue, { fontVariant: ['tabular-nums'] }, isDark && { color: colors.text }]}>
                              {hasCoords
                                ? `${Number(a.lat).toFixed(5)}° N, ${Number(a.lng).toFixed(5)}° E`
                                : 'Location sharing was disabled'}
                            </Text>
                          </View>
                          {hasCoords && (
                            <Pressable
                              onPress={() => openExternalMap(Number(a.lat), Number(a.lng), info.label)}
                              accessibilityRole="button"
                              style={styles.mapQuickLink}
                            >
                              <Text style={[styles.mapQuickLinkText, { color: colors.brand }]}>Open Map ↗</Text>
                            </Pressable>
                          )}
                        </View>
                      </View>

                      {/* Contacts & Responders Notified */}
                      <View>
                        <Text style={[styles.histDetailLabel, isDark && { color: colors.muted }]}>CONTACTS & RESPONDERS NOTIFIED</Text>
                        <View style={[styles.chipWrap, { marginTop: 6, marginBottom: 0 }]}>
                          {notifiedList.map((contactName) => (
                            <View key={contactName} style={[styles.notifiedChip, isDark && { backgroundColor: colors.card, borderColor: colors.border }]}>
                              <Ionicons name="checkmark-circle" size={13} color={colors.success} style={{ marginRight: 4 }} />
                              <Text style={{ color: colors.text, fontSize: 12, fontWeight: '600' }}>{contactName}</Text>
                            </View>
                          ))}
                        </View>
                      </View>
                    </View>
                  )}
                </View>
              );
            })
          )
        )}

        {/* ── SCREEN 9: Notifications Inbox (Table 1.9) ── */}
        {!loading && !error && tab === 'inbox' && (
          visibleAnnouncements.length === 0 ? (
            <View style={styles.emptyBox}>
              <Ionicons name="mail-open-outline" size={42} color={colors.muted} />
              <Text style={[styles.emptyText, isDark && { color: colors.textSec }]}>{t('noNotifications')}</Text>
            </View>
          ) : (
            visibleAnnouncements.map((n) => {
              const isRead = seenIds.includes(n.id);
              const cat = n.category || 'announcement';
              const catBadge =
                cat === 'drill'
                  ? { label: 'Emergency Drill', bg: C.amberLight, fg: C.amberDark, icon: 'construct-outline' }
                  : cat === 'update'
                  ? { label: 'App Update', bg: C.successLight, fg: C.successDark, icon: 'sparkles-outline' }
                  : { label: 'Admin Announcement', bg: isDark ? colors.brandLight : C.brandLight, fg: colors.brand, icon: 'megaphone-outline' };

              return (
                <View
                  key={n.id}
                  style={[
                    styles.inboxCard,
                    isDark && { backgroundColor: colors.card, borderColor: colors.border, borderWidth: 1 },
                    !isRead && (isDark ? { backgroundColor: '#0C4A6E', borderColor: colors.brand, borderWidth: 1 } : styles.inboxCardUnread),
                  ]}
                >
                  <View style={[styles.row, { justifyContent: 'space-between', marginBottom: 8 }]}>
                    <View style={styles.row}>
                      {!isRead && <View style={styles.unreadDot} />}
                      <View style={[styles.badge, { backgroundColor: catBadge.bg }]}>
                        <Text style={[styles.badgeText, { color: catBadge.fg }]}>{catBadge.label}</Text>
                      </View>
                    </View>

                    {/* Dismiss / Clear action (Table 1.9) */}
                    <Pressable
                      onPress={() => {
                        onDismissAnnouncement(n.id);
                        showToast('Notification dismissed.');
                      }}
                      hitSlop={10}
                      accessibilityRole="button"
                      accessibilityLabel={`Dismiss ${n.title}`}
                      style={styles.dismissBtn}
                    >
                      <Ionicons name="close" size={18} color={colors.muted} />
                    </Pressable>
                  </View>

                  <Pressable
                    onPress={() => openAnnouncementModal(n)}
                    accessibilityRole="button"
                    accessibilityLabel={`${isRead ? 'Read' : 'Unread'} notification: ${n.title}`}
                  >
                    <Text style={[styles.histCardTitle, isDark && { color: colors.text }, !isRead && { color: isDark ? '#7DD3FC' : C.brandDark }]}>
                      {n.title}
                    </Text>
                    <Text style={[styles.histCardBody, isDark && { color: colors.textSec }]} numberOfLines={2}>
                      {n.body}
                    </Text>

                    <View style={[styles.row, { justifyContent: 'space-between', marginTop: 10 }]}>
                      <Text style={[styles.histCardMeta, isDark && { color: colors.muted }]}>{formatDateTime(n.created_at)}</Text>
                      <Text style={{ color: colors.brand, fontSize: 12, fontWeight: '700' }}>
                        Tap to read full notice →
                      </Text>
                    </View>
                  </Pressable>
                </View>
              );
            })
          )
        )}
      </ScrollView>

      {/* Full Announcement Reader Modal (Table 1.9) */}
      <Modal
        visible={!!selectedAnnouncement}
        transparent
        animationType="fade"
        onRequestClose={() => setSelectedAnnouncement(null)}
      >
        <View style={styles.modalBackdrop}>
          <View style={[styles.modalSheet, isDark && { backgroundColor: colors.card }]}>
            <View style={styles.modalHandle} />
            {selectedAnnouncement && (
              <>
                <View style={[styles.modalIconCircle, { backgroundColor: isDark ? colors.brandLight : C.brandLight }]}>
                  <Ionicons name="megaphone" size={30} color={colors.brand} />
                </View>
                <Text style={[styles.modalTitle, isDark && { color: colors.text }]}>{selectedAnnouncement.title}</Text>
                <Text style={{ color: colors.muted, fontSize: 12, textAlign: 'center', marginBottom: 14 }}>
                  Posted {formatDateTime(selectedAnnouncement.created_at)} • Parañaque CSN Center
                </Text>
                <View style={{ backgroundColor: colors.bg, padding: 16, borderRadius: 14, marginBottom: 18 }}>
                  <Text style={{ color: colors.text, fontSize: 15, lineHeight: 23 }}>
                    {selectedAnnouncement.body}
                  </Text>
                </View>
                <PrimaryButton
                  title="Done Reading"
                  tone="brand"
                  onPress={() => setSelectedAnnouncement(null)}
                />
                <PrimaryButton
                  title="Dismiss from Inbox"
                  tone="ghost"
                  onPress={() => {
                    onDismissAnnouncement(selectedAnnouncement.id);
                    setSelectedAnnouncement(null);
                    showToast('Notification removed from Inbox.');
                  }}
                  style={{ marginTop: 8 }}
                />
              </>
            )}
          </View>
        </View>
      </Modal>

      <BottomTabBar current={tab === 'inbox' ? 'inbox' : 'history'} onPress={(k) => go(k)} unread={unread} />
    </SafeAreaView>
  );
}
