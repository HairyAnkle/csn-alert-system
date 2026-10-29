import { SafeAreaView } from 'react-native-safe-area-context';
import { Ionicons } from '@expo/vector-icons';
import { useCallback, useEffect, useRef, useState } from 'react';
import {
  ActivityIndicator,
  KeyboardAvoidingView,
  Linking,
  Modal,
  Platform,
  Pressable,
  ScrollView,
  StatusBar,
  Text,
  TextInput,
  View,
} from 'react-native';

import { useApp } from '../context/AppContext';
import { BottomTabBar } from '../components/BottomTabBar';
import { PrimaryButton } from '../components/PrimaryButton';
import { SectionCard } from '../components/SectionCard';
import { CSN_HOTLINE, CSN_SUPPORT_EMAIL, EMERGENCY_NUMBER, POLL_INTERVAL_MS } from '../constants/app';
import { CHAT_QUICK_TOPICS, FAQ_ITEMS, SAFETY_RESOURCES, VIDEO_TUTORIALS } from '../constants/supportContent';
import { api } from '../services/api';
import { generateSmartSupportReply } from '../services/devMock';
import { C } from '../theme/colors';
import { styles } from '../theme/styles';
import { callEmergencyNumber } from '../utils/device';
import { formatDateTime } from '../utils/format';

/* ============================================================================
 * SCREEN 11 — SUPPORT & HELP (Table 1.11)
 * ========================================================================== */

/** Unique client-side message id (module scope keeps impure calls out of render). */
const makeMessageId = (prefix) => `${prefix}-${Date.now()}-${Math.random().toString(36).slice(2, 7)}`;

/**
 * Fetches the guardian's support chat thread from the Web Dashboard API.
 * Returns mapped messages, or null if empty / endpoint not yet deployed.
 */
async function fetchSupportMessages(token) {
  try {
    const res = await api('/support/messages', { token });
    const rows = Array.isArray(res?.data)
      ? res.data
      : Array.isArray(res?.messages)
      ? res.messages
      : Array.isArray(res)
      ? res
      : null;
    if (!rows || rows.length === 0) return null;
    return rows.map((m) => ({
      id: String(m.id),
      sender: m.sender === 'user' || m.sender_type === 'guardian' ? 'user' : 'support',
      sender_name: m.sender_name || (m.sender === 'user' ? 'You' : 'Parañaque CSN Desk'),
      text: m.text || m.body || m.message || '',
      time: m.created_at ? formatDateTime(m.created_at) : 'Just now',
    }));
  } catch {
    // Keep local messages if backend endpoint is not yet migrated on web dashboard
    return null;
  }
}

export function SupportScreen({ token, guardian, child, go, onLogout, showToast, unread }) {
  const { isDark, colors, t, lang } = useApp();
  const [catTab, setCatTab]           = useState('faqs'); // 'faqs' | 'resources' | 'videos'
  const [openFaqIdx, setOpenFaqIdx]   = useState(0);
  const [activeVideo, setActiveVideo] = useState(null);
  const [chatOpen, setChatOpen]       = useState(false);
  const [chatInput, setChatInput]     = useState('');
  const [chatSending, setChatSending] = useState(false);
  const [chatTyping, setChatTyping]   = useState(false);
  const [chatSynced, setChatSynced]   = useState(false);
  const chatScrollRef                 = useRef(null);
  const [chatMessages, setChatMessages] = useState([
    {
      id: 'm1',
      sender: 'support',
      sender_name: 'Parañaque CSN Desk',
      text: 'Mabuhay! Welcome to Parañaque CSN Center Live Support (Col. E. de Leon St., Brgy. Sto. Niño). How can we assist you and your child today?',
      time: 'Online',
    },
  ]);

  const loadChatMessages = useCallback(() => fetchSupportMessages(token), [token]);

  // Live chat polling (every POLL_INTERVAL_MS) while the chat modal is open
  useEffect(() => {
    if (!chatOpen) return undefined;
    let active = true;
    const sync = () =>
      loadChatMessages().then((rows) => {
        if (active && Array.isArray(rows) && rows.length > 0) {
          setChatMessages((prev) => {
            if (!prev || prev.length === 0) return rows;

            const serverIds = new Set(rows.map((r) => String(r.id)));

            // Retain any local messages (in-flight user messages or automated replies)
            // that are not yet echoed back by the server
            const localOnly = prev.filter((p) => {
              if (serverIds.has(String(p.id))) return false;
              const alreadyOnServer = rows.some(
                (r) => r.sender === p.sender && (r.text || '').trim() === (p.text || '').trim()
              );
              return !alreadyOnServer;
            });

            if (localOnly.length === 0) {
              return rows;
            }

            return [...rows, ...localOnly];
          });
          setChatSynced(true);
        }
      });
    sync();
    const interval = setInterval(sync, POLL_INTERVAL_MS);
    return () => {
      active = false;
      clearInterval(interval);
    };
  }, [chatOpen, loadChatMessages]);

  const sendChatMessage = async (overrideText) => {
    const raw = typeof overrideText === 'string' ? overrideText : chatInput;
    const trimmed = raw.trim();
    if (!trimmed || chatSending) return;

    const tempId = makeMessageId('u');
    const userMsg = {
      id: tempId,
      sender: 'user',
      sender_name: guardian?.name || (lang === 'Filipino' ? 'Ikaw' : 'You'),
      text: trimmed,
      time: 'Just now',
    };

    setChatMessages((prev) => [...prev, userMsg]);
    if (typeof overrideText !== 'string') {
      setChatInput('');
    }
    setChatSending(true);
    setChatTyping(true);

    setTimeout(() => {
      chatScrollRef.current?.scrollToEnd?.({ animated: true });
    }, 80);

    try {
      const res = await api('/support/messages', {
        method: 'POST',
        token,
        body: {
          body: trimmed,
          message: trimmed,
          child_id: child?.id || null,
        },
      });

      const serverUser = res?.data;
      const serverReply = res?.reply;

      setTimeout(() => {
        setChatTyping(false);
        setChatMessages((prev) => {
          const updated = prev.map((m) =>
            m.id === tempId && serverUser?.id
              ? { ...m, id: String(serverUser.id) }
              : m
          );
          const replyText =
            serverReply?.text ||
            serverReply?.body ||
            generateSmartSupportReply(trimmed, child);
          const replyId = serverReply?.id ? String(serverReply.id) : makeMessageId('s');
          if (updated.some((m) => m.id === replyId)) return updated;
          return [
            ...updated,
            {
              id: replyId,
              sender: 'support',
              sender_name: serverReply?.sender_name || 'Parañaque CSN Desk',
              text: replyText,
              time: 'Just now',
            },
          ];
        });
        setTimeout(() => {
          chatScrollRef.current?.scrollToEnd?.({ animated: true });
        }, 100);
      }, 650);
    } catch {
      // Fallback smart reply if web server route hasn't been added yet
      setTimeout(() => {
        setChatTyping(false);
        const replyMsg = {
          id: makeMessageId('s'),
          sender: 'support',
          sender_name: 'Parañaque CSN Desk',
          text: generateSmartSupportReply(trimmed, child),
          time: 'Just now',
        };
        setChatMessages((prev) => [...prev, replyMsg]);
        setTimeout(() => {
          chatScrollRef.current?.scrollToEnd?.({ animated: true });
        }, 100);
      }, 650);
    } finally {
      setChatSending(false);
    }
  };

  return (
    <SafeAreaView style={[styles.flex, { backgroundColor: colors.bg }]} edges={['top', 'left', 'right']}>
      <StatusBar barStyle="light-content" backgroundColor={C.brand} />

      <View style={[styles.innerHeader, styles.row, { justifyContent: 'space-between' }]}>
        <Text style={styles.innerHeaderTitle} accessibilityRole="header">{t('supportTitle')}</Text>
        <Pressable
          onPress={() => go('settings')}
          accessibilityRole="button"
          accessibilityLabel="Open App Settings"
          style={styles.headerActionBtn}
        >
          <Ionicons name="settings-outline" size={16} color={C.white} style={{ marginRight: 4 }} />
          <Text style={{ color: C.white, fontSize: 12, fontWeight: '700' }}>Settings</Text>
        </Pressable>
      </View>

      {/* ── Category Tabs: FAQs, Emergency Safety Resources, Video Tutorials (Table 1.11) ── */}
      <View style={[styles.pillTabContainer, { backgroundColor: colors.bg }]}>
        <View style={[styles.pillTabBar, isDark && { backgroundColor: colors.card, borderColor: colors.border, borderWidth: 1 }]}>
          {[
            { key: 'faqs',      label: t('faqTab') },
            { key: 'resources', label: t('resourcesTab') },
            { key: 'videos',    label: t('videosTab') },
          ].map((tItem) => (
            <Pressable
              key={tItem.key}
              onPress={() => setCatTab(tItem.key)}
              accessibilityRole="tab"
              accessibilityState={{ selected: catTab === tItem.key }}
              style={[styles.pillTab, catTab === tItem.key && (isDark ? { backgroundColor: colors.border } : styles.pillTabActive)]}
            >
              <Text style={[styles.pillTabText, catTab === tItem.key && { color: isDark ? colors.text : C.brand }]}>
                {tItem.label}
              </Text>
            </Pressable>
          ))}
        </View>
      </View>

      <ScrollView contentContainerStyle={styles.supportScroll} showsVerticalScrollIndicator={false}>
        {/* ── Contact Support Options (Table 1.11: Direct Phone Hotline, Support Email, Live Chat Trigger) ── */}
        <Text style={[styles.sectionHeading, isDark && { color: colors.brand }]}>{t('directContactHeading')}</Text>
        <View style={[styles.row, { gap: 10, marginBottom: 14 }]}>
          <Pressable
            onPress={() => callEmergencyNumber(EMERGENCY_NUMBER)}
            accessibilityRole="button"
            style={[styles.contactQuickCard, { backgroundColor: C.critical }]}
          >
            <Ionicons name="call" size={22} color={C.white} />
            <Text style={styles.contactQuickTitle}>Call {EMERGENCY_NUMBER}</Text>
            <Text style={styles.contactQuickSub}>24/7 Emergency</Text>
          </Pressable>

          <Pressable
            onPress={() => callEmergencyNumber(CSN_HOTLINE)}
            accessibilityRole="button"
            style={[styles.contactQuickCard, { backgroundColor: C.brand }]}
          >
            <Ionicons name="call-outline" size={22} color={C.white} />
            <Text style={styles.contactQuickTitle}>CSN Hotline</Text>
            <Text style={styles.contactQuickSub}>{CSN_HOTLINE}</Text>
          </Pressable>
        </View>

        <View style={[styles.row, { gap: 10, marginBottom: 20 }]}>
          <Pressable
            onPress={() => Linking.openURL(`mailto:${CSN_SUPPORT_EMAIL}?subject=Para%C3%B1aque%20CSN%20Alert%20Support`)}
            accessibilityRole="button"
            style={[styles.contactSecondaryCard, isDark && { backgroundColor: colors.card, borderColor: colors.border }]}
          >
            <Ionicons name="mail-outline" size={20} color={colors.brand} style={{ marginRight: 8 }} />
            <View style={{ flex: 1 }}>
              <Text style={{ color: colors.text, fontSize: 13, fontWeight: '800' }}>Email Support</Text>
              <Text style={{ color: colors.textSec, fontSize: 11 }} numberOfLines={1}>{CSN_SUPPORT_EMAIL}</Text>
            </View>
          </Pressable>

          <Pressable
            onPress={() => setChatOpen(true)}
            accessibilityRole="button"
            style={[styles.contactSecondaryCard, { borderColor: colors.success, backgroundColor: isDark ? '#064E3B' : C.successLight }]}
          >
            <Ionicons name="chatbubbles" size={20} color={colors.success} style={{ marginRight: 8 }} />
            <View style={{ flex: 1 }}>
              <Text style={{ color: colors.success, fontSize: 13, fontWeight: '800' }}>Live Chat</Text>
              <Text style={{ color: colors.success, fontSize: 11 }}>Chat with CSN Desk</Text>
            </View>
          </Pressable>
        </View>

        {/* ── TAB 1: FAQs (Table 1.11) ── */}
        {catTab === 'faqs' && (
          <>
            <Text style={[styles.sectionHeading, isDark && { color: colors.brand }]}>{t('faqHeading')}</Text>
            {FAQ_ITEMS.map((item, idx) => {
              const open = openFaqIdx === idx;
              return (
                <Pressable
                  key={item.q}
                  onPress={() => setOpenFaqIdx(open ? -1 : idx)}
                  style={[styles.faqCard, isDark && { backgroundColor: colors.card, borderColor: colors.border, borderWidth: 1 }]}
                >
                  <View style={[styles.row, { justifyContent: 'space-between' }]}>
                    <Text style={[styles.supportCardTitle, { fontSize: 15, marginRight: 8 }, isDark && { color: colors.text }]}>{item.q}</Text>
                    <Ionicons name={open ? 'chevron-up' : 'chevron-down'} size={18} color={colors.brand} />
                  </View>
                  {open && (
                    <Text style={[styles.supportCardBody, { marginTop: 10, paddingTop: 10, borderTopWidth: 1, borderTopColor: isDark ? colors.border : C.border }, isDark && { color: colors.textSec }]}>
                      {item.a}
                    </Text>
                  )}
                </Pressable>
              );
            })}
          </>
        )}

        {/* ── TAB 2: Emergency Safety Resources (Table 1.11) ── */}
        {catTab === 'resources' && (
          <>
            <Text style={[styles.sectionHeading, isDark && { color: colors.brand }]}>{t('resourcesTab')}</Text>
            {SAFETY_RESOURCES.map((res) => (
              <SectionCard key={res.title} style={{ marginBottom: 12 }}>
                <View style={[styles.row, { marginBottom: 8 }]}>
                  <View style={[styles.supportIconCircle, { backgroundColor: res.color + '18' }]}>
                    <Ionicons name={res.icon} size={20} color={res.color} />
                  </View>
                  <View style={{ flex: 1 }}>
                    <Text style={{ color: res.color, fontSize: 11, fontWeight: '800', textTransform: 'uppercase' }}>
                      {res.tag}
                    </Text>
                    <Text style={[styles.supportCardTitle, isDark && { color: colors.text }]}>{res.title}</Text>
                  </View>
                </View>
                {res.steps.map((step, i) => (
                  <View key={step} style={[styles.row, { alignItems: 'flex-start', marginTop: 6 }]}>
                    <Text style={{ color: res.color, fontWeight: '800', marginRight: 8 }}>{i + 1}.</Text>
                    <Text style={[styles.supportCardBody, { flex: 1 }, isDark && { color: colors.textSec }]}>{step}</Text>
                  </View>
                ))}
              </SectionCard>
            ))}
          </>
        )}

        {/* ── TAB 3: Video Tutorials (Table 1.11) ── */}
        {catTab === 'videos' && (
          <>
            <Text style={[styles.sectionHeading, isDark && { color: colors.brand }]}>{t('stepByStepVideos')}</Text>
            {VIDEO_TUTORIALS.map((vid) => (
              <Pressable
                key={vid.id}
                onPress={() => setActiveVideo(vid)}
                style={[styles.videoCard, isDark && { backgroundColor: colors.card, borderColor: colors.border, borderWidth: 1 }]}
              >
                <View style={styles.videoThumb}>
                  <Ionicons name="play-circle" size={40} color={C.white} />
                  <View style={styles.videoDurationPill}>
                    <Text style={{ color: C.white, fontSize: 11, fontWeight: '800' }}>{vid.duration}</Text>
                  </View>
                </View>
                <View style={{ padding: 14 }}>
                  <Text style={[styles.supportCardTitle, isDark && { color: colors.text }]}>{vid.title}</Text>
                  <Text style={[styles.supportCardBody, { marginTop: 4 }, isDark && { color: colors.textSec }]}>{vid.summary}</Text>
                </View>
              </Pressable>
            ))}
          </>
        )}

        {/* Settings & Sign Out shortcut */}
        <Pressable
          onPress={() => go('settings')}
          accessibilityRole="button"
          style={({ pressed }) => [
            styles.logoutBtn,
            isDark && { borderColor: colors.border, backgroundColor: colors.card },
            pressed && { opacity: 0.75 },
          ]}
        >
          <Ionicons name="settings-outline" size={20} color={colors.brand} style={{ marginRight: 8 }} />
          <Text style={[styles.logoutBtnText, { color: colors.brand }]}>{t('openSettingsBtn')}</Text>
        </Pressable>

        <View style={{ height: 16 }} />
      </ScrollView>

      {/* Video Tutorial Interactive Walkthrough Modal */}
      <Modal visible={!!activeVideo} transparent animationType="fade" onRequestClose={() => setActiveVideo(null)}>
        <View style={styles.modalBackdrop}>
          <View style={[styles.modalSheet, isDark && { backgroundColor: colors.card }]}>
            <View style={styles.modalHandle} />
            {activeVideo && (
              <>
                <View style={[styles.videoThumb, { borderRadius: 16, height: 150, marginBottom: 14 }]}>
                  <Ionicons name="videocam" size={44} color={C.white} />
                  <Text style={{ color: C.white, fontWeight: '800', marginTop: 8 }}>
                    Interactive Tutorial Preview ({activeVideo.duration})
                  </Text>
                </View>
                <Text style={[styles.modalTitle, isDark && { color: colors.text }]}>{activeVideo.title}</Text>
                <Text style={[styles.modalBody, isDark && { color: colors.textSec }]}>{activeVideo.summary}</Text>
                <View style={{ backgroundColor: colors.bg, padding: 14, borderRadius: 14, marginBottom: 16 }}>
                  {activeVideo.steps.map((s) => (
                    <View key={s} style={[styles.row, { alignItems: 'flex-start', marginBottom: 8 }]}>
                      <Ionicons name="checkmark-circle" size={18} color={colors.brand} style={{ marginRight: 8, marginTop: 2 }} />
                      <Text style={{ flex: 1, color: colors.text, fontSize: 14, lineHeight: 20 }}>{s}</Text>
                    </View>
                  ))}
                </View>
                <PrimaryButton title={t('closeBtn')} tone="brand" onPress={() => setActiveVideo(null)} />
              </>
            )}
          </View>
        </View>
      </Modal>

      {/* Live Chat Request Trigger Modal (Table 1.11) */}
      <Modal visible={chatOpen} transparent animationType="slide" onRequestClose={() => setChatOpen(false)}>
        <View style={styles.modalBackdrop}>
          <KeyboardAvoidingView
            behavior={Platform.OS === 'ios' ? 'padding' : undefined}
            style={{ width: '100%' }}
          >
            <View style={[styles.modalSheet, isDark && { backgroundColor: colors.card }]}>
              <View style={styles.modalHandle} />
              <View style={[styles.row, { justifyContent: 'space-between', marginBottom: 10 }]}>
                <View style={styles.row}>
                  <View style={[styles.supportIconCircle, { backgroundColor: isDark ? '#064E3B' : C.successLight }]}>
                    <Ionicons name="chatbubbles" size={20} color={colors.success} />
                  </View>
                  <View>
                    <Text style={{ color: colors.text, fontSize: 17, fontWeight: '800' }}>CSN Support Live Chat</Text>
                    <Text style={{ color: colors.success, fontSize: 12, fontWeight: '600' }}>
                      ● Online — Parañaque CSN Help Desk {chatSynced ? '(Synced)' : ''}
                    </Text>
                  </View>
                </View>
                <View style={[styles.row, { gap: 4 }]}>
                  <Pressable
                    onPress={() => {
                      setChatMessages([
                        {
                          id: 'm1',
                          sender: 'support',
                          sender_name: 'Parañaque CSN Desk',
                          text: 'Mabuhay! Welcome to Parañaque CSN Center Live Support (Col. E. de Leon St., Brgy. Sto. Niño). How can we assist you and your child today?',
                          time: 'Online',
                        },
                      ]);
                      showToast('Chat history cleared.');
                    }}
                    style={[styles.backBtn, { marginLeft: 0 }]}
                    accessibilityRole="button"
                    accessibilityLabel="Clear chat messages"
                  >
                    <Ionicons name="refresh-outline" size={20} color={colors.muted} />
                  </Pressable>
                  <Pressable
                    onPress={() => setChatOpen(false)}
                    style={[styles.backBtn, { marginLeft: 0 }]}
                    accessibilityRole="button"
                    accessibilityLabel="Close live chat"
                  >
                    <Ionicons name="close" size={24} color={colors.muted} />
                  </Pressable>
                </View>
              </View>

              {/* Quick Topic Chips */}
              <ScrollView
                horizontal
                showsHorizontalScrollIndicator={false}
                style={{ marginBottom: 10 }}
                contentContainerStyle={{ gap: 6, paddingRight: 8 }}
              >
                {CHAT_QUICK_TOPICS.map((topic) => (
                  <Pressable
                    key={topic}
                    onPress={() => sendChatMessage(topic)}
                    accessibilityRole="button"
                    style={{
                      backgroundColor: isDark ? colors.bg : C.brandSoft,
                      borderColor: colors.brand + '40',
                      borderWidth: 1,
                      paddingHorizontal: 12,
                      paddingVertical: 6,
                      borderRadius: 999,
                    }}
                  >
                    <Text style={{ color: colors.brand, fontSize: 12, fontWeight: '700' }}>{topic}</Text>
                  </Pressable>
                ))}
              </ScrollView>

              <ScrollView
                ref={chatScrollRef}
                style={{ maxHeight: 280, marginBottom: 12 }}
                contentContainerStyle={{ paddingVertical: 4 }}
                onContentSizeChange={() => chatScrollRef.current?.scrollToEnd?.({ animated: true })}
              >
                {chatMessages.map((m) => (
                  <View
                    key={m.id}
                    style={[
                      styles.chatBubble,
                      m.sender === 'user'
                        ? styles.chatBubbleUser
                        : [
                            styles.chatBubbleSupport,
                            isDark && { backgroundColor: colors.bg, borderColor: colors.border, borderWidth: 1 },
                          ],
                    ]}
                  >
                    <View style={[styles.row, { justifyContent: 'space-between', marginBottom: 3 }]}>
                      <Text
                        style={{
                          color: m.sender === 'user' ? 'rgba(255,255,255,0.85)' : colors.brand,
                          fontSize: 11,
                          fontWeight: '800',
                        }}
                      >
                        {m.sender_name || (m.sender === 'user' ? (lang === 'Filipino' ? 'Ikaw' : 'You') : 'Parañaque CSN Desk')}
                      </Text>
                      {!!m.time && (
                        <Text
                          style={{
                            color: m.sender === 'user' ? 'rgba(255,255,255,0.75)' : colors.muted,
                            fontSize: 10,
                            marginLeft: 8,
                          }}
                        >
                          {m.time}
                        </Text>
                      )}
                    </View>
                    <Text style={{ color: m.sender === 'user' ? C.white : colors.text, fontSize: 14, lineHeight: 20 }}>
                      {m.text}
                    </Text>
                  </View>
                ))}
                {chatTyping && (
                  <View style={[styles.chatBubble, styles.chatBubbleSupport, isDark && { backgroundColor: colors.bg, borderColor: colors.border, borderWidth: 1 }, { flexDirection: 'row', alignItems: 'center', gap: 8 }]}>
                    <ActivityIndicator size="small" color={colors.brand} />
                    <Text style={{ color: colors.textSec, fontSize: 13, fontStyle: 'italic' }}>
                      CSN Social Welfare Coordinator is typing…
                    </Text>
                  </View>
                )}
              </ScrollView>

              <View style={[styles.row, { gap: 8 }]}>
                <View style={[styles.inputWrap, { flex: 1 }, isDark && { backgroundColor: colors.bg, borderColor: colors.border }]}>
                  <TextInput
                    value={chatInput}
                    onChangeText={setChatInput}
                    onSubmitEditing={() => sendChatMessage()}
                    returnKeyType="send"
                    blurOnSubmit={false}
                    placeholder="Type your message to CSN Support..."
                    placeholderTextColor={colors.muted}
                    style={[styles.inputInner, isDark && { color: colors.text }]}
                  />
                </View>
                <Pressable
                  onPress={() => sendChatMessage()}
                  disabled={!chatInput.trim() || chatSending}
                  style={[
                    styles.chatSendBtn,
                    (!chatInput.trim() || chatSending) && { opacity: 0.5 },
                  ]}
                  accessibilityRole="button"
                  accessibilityLabel="Send chat message"
                >
                  <Ionicons name="send" size={20} color={C.white} />
                </Pressable>
              </View>
            </View>
          </KeyboardAvoidingView>
        </View>
      </Modal>

      <BottomTabBar current="support" onPress={(k) => go(k)} unread={unread} />
    </SafeAreaView>
  );
}
