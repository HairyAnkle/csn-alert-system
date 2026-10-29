import { SafeAreaView } from 'react-native-safe-area-context';
import { Ionicons } from '@expo/vector-icons';
import { useState } from 'react';
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
import { DispatchStepper } from '../components/DispatchStepper';
import { InteractiveLocationMap } from '../components/InteractiveLocationMap';
import { PrimaryButton } from '../components/PrimaryButton';
import { SectionCard } from '../components/SectionCard';
import { DEFAULT_PARANAQUE_COORDS, EMERGENCY_NUMBER } from '../constants/app';
import { NEARBY_EMERGENCY_CENTERS } from '../constants/data';
import { C } from '../theme/colors';
import { styles } from '../theme/styles';
import { callEmergencyNumber } from '../utils/device';

export function EmergencyActiveScreen({ emergency, coords, liveSharing, onToggleLiveSharing, onCancel, onDone, cancelling }) {
  const { isDark, colors } = useApp();
  const [cancelOpen, setCancelOpen] = useState(false);
  const [selectedCenter, setSelectedCenter] = useState(NEARBY_EMERGENCY_CENTERS[0]);

  const status  = emergency ? emergency.status : 'pending';
  const urgency = emergency && emergency.urgency ? emergency.urgency : 'high';
  const done    = status === 'resolved' || status === 'cancelled';
  const screenBg =
    status === 'resolved'  ? C.successDark :
    status === 'cancelled' ? C.textSec :
    status === 'responding'? C.amberDark :
    C.critical;

  const headline =
    status === 'resolved'  ? 'Emergency Resolved ✓' :
    status === 'cancelled' ? 'Alert Cancelled' :
    status === 'responding'? 'Help Is On The Way' :
    'Alert Dispatched';

  const subline =
    status === 'pending'   ? 'Broadcasting to CSN Admin & Parañaque DRRMO Responders...' :
    status === 'responding'? 'A responder has accepted your alert and is en route.' :
    status === 'resolved'  ? 'The CSN team has marked this incident as resolved.' :
    'This alert was cancelled.';

  const responder = emergency && emergency.responder;
  const effectiveCoords = coords || (emergency && emergency.lat ? { latitude: emergency.lat, longitude: emergency.lng } : DEFAULT_PARANAQUE_COORDS);

  return (
    <SafeAreaView style={[styles.flex, { backgroundColor: screenBg }]}>
      <StatusBar barStyle="light-content" backgroundColor={screenBg} />
      <ScrollView contentContainerStyle={{ padding: 20, paddingBottom: 40 }} showsVerticalScrollIndicator={false}>

        {/* ── Banner ── */}
        <View style={styles.activeBanner} accessibilityLiveRegion="assertive">
          <View style={styles.activeBannerIcon}>
            <Ionicons
              name={status === 'resolved' ? 'checkmark-circle' : status === 'cancelled' ? 'close-circle' : 'warning'}
              size={48}
              color={C.white}
            />
          </View>
          <Text style={styles.activeHeadline}>{headline}</Text>
          <Text style={styles.activeSub}>{subline}</Text>
          <View style={[styles.row, { marginTop: 10, gap: 8 }]}>
            <View style={[styles.badge, { backgroundColor: 'rgba(255,255,255,0.22)' }]}>
              <Text style={[styles.badgeText, { color: C.white }]}>
                {urgency === 'high' ? '⚡ HIGH URGENCY (DRRMO + CSN)' : 'LOW URGENCY (CSN CENTER)'}
              </Text>
            </View>
          </View>
          {!done && <ActivityIndicator color="rgba(255,255,255,0.7)" style={{ marginTop: 10 }} />}
        </View>

        {/* ── Dispatch Stepper ── */}
        <SectionCard style={{ marginBottom: 12 }}>
          <Text style={[styles.activeCardLabel, isDark && { color: colors.muted }]}>DISPATCH PROGRESS</Text>
          <DispatchStepper status={status} />
          {emergency && (emergency.message || emergency.guardian_notes) ? (
            <View style={{ marginTop: 12, padding: 12, backgroundColor: isDark ? colors.bg : C.bg, borderRadius: 10, borderWidth: isDark ? 1 : 0, borderColor: colors.border }}>
              <Text style={{ color: colors.muted, fontSize: 11, fontWeight: '700' }}>SUBMITTED MESSAGE:</Text>
              <Text style={{ color: colors.text, fontSize: 14, marginTop: 2 }}>
                &ldquo;{emergency.message || emergency.guardian_notes}&rdquo;
              </Text>
            </View>
          ) : null}
        </SectionCard>

        {/* ── Interactive Live Map & GPS Transmission Toggle (Table 1.6) ── */}
        <SectionCard style={{ marginBottom: 12 }}>
          <View style={[styles.row, { justifyContent: 'space-between', marginBottom: 8 }]}>
            <View style={[styles.row, { flex: 1 }]}>
              <Ionicons
                name="navigate-circle"
                size={22}
                color={done || !liveSharing ? colors.muted : colors.success}
                style={{ marginRight: 8 }}
              />
              <View style={{ flex: 1 }}>
                <Text style={[styles.activeCardTitle, isDark && { color: colors.text }]}>
                  {done
                    ? 'Location sharing stopped'
                    : liveSharing
                    ? 'Streaming live GPS location'
                    : 'Live GPS streaming paused'}
                </Text>
                <Text style={[styles.coordText, isDark && { color: colors.text }]}>
                  {effectiveCoords.latitude.toFixed(5)}, {effectiveCoords.longitude.toFixed(5)}
                </Text>
              </View>
            </View>
            {!done && (
              <Pressable
                onPress={onToggleLiveSharing}
                accessibilityRole="switch"
                accessibilityState={{ checked: liveSharing }}
                style={[styles.toggleTrack, liveSharing && styles.toggleTrackActive]}
              >
                <View style={[styles.toggleThumb, liveSharing && styles.toggleThumbActive]} />
              </Pressable>
            )}
          </View>

          <InteractiveLocationMap
            coords={effectiveCoords}
            isStreaming={!done && liveSharing}
            selectedCenter={selectedCenter}
            onSelectCenter={setSelectedCenter}
            childName="Emergency Location"
          />
        </SectionCard>

        {/* ── Responder Card ── */}
        <SectionCard style={{ marginBottom: 16 }}>
          <Text style={[styles.activeCardLabel, isDark && { color: colors.muted }]}>ASSIGNED RESPONDER</Text>
          {responder ? (
            <View style={[styles.row, { marginTop: 10 }]}>
              <View style={[styles.responderIcon, isDark && { backgroundColor: isDark ? '#0C4A6E' : C.brandLight }]}>
                <Ionicons name="car-sport" size={22} color={colors.brand} />
              </View>
              <View style={{ flex: 1, marginLeft: 12 }}>
                <Text style={[styles.responderName, isDark && { color: colors.text }]}>
                  {responder.unit || responder.name || 'Parañaque Emergency Unit'}
                </Text>
                <Text style={[styles.responderMeta, isDark && { color: colors.textSec }]}>
                  {status === 'resolved' ? 'Arrived on Scene' : 'En Route'}
                  {responder.eta ? ` · ETA ${responder.eta}` : ''}
                </Text>
              </View>
            </View>
          ) : (
            <View style={[styles.row, { marginTop: 10 }]}>
              <View style={[styles.responderIcon, isDark && { backgroundColor: colors.border }]}>
                <Ionicons name="time-outline" size={22} color={colors.muted} />
              </View>
              <Text style={[styles.responderMeta, { flex: 1, marginLeft: 12 }, isDark && { color: colors.textSec }]}>
                {done ? 'No responder was assigned.' : 'Assigning nearest Parañaque responder unit…'}
              </Text>
            </View>
          )}
        </SectionCard>

        {/* ── Call 911 ── */}
        <Pressable
          onPress={() => callEmergencyNumber(EMERGENCY_NUMBER)}
          accessibilityRole="button"
          accessibilityLabel={`Call ${EMERGENCY_NUMBER}`}
          style={({ pressed }) => [styles.callBtn, pressed && { opacity: 0.85 }]}
        >
          <Ionicons name="call" size={20} color={C.critical} style={{ marginRight: 8 }} />
          <Text style={styles.callBtnText}>Call {EMERGENCY_NUMBER}</Text>
        </Pressable>

        {/* ── Done / Cancel ── */}
        {done ? (
          <Pressable
            onPress={onDone}
            accessibilityRole="button"
            style={[styles.ghostBtn, { marginTop: 12 }]}
          >
            <Text style={styles.ghostBtnText}>Back to Dashboard</Text>
          </Pressable>
        ) : (
          <Pressable
            onPress={() => setCancelOpen(true)}
            accessibilityRole="button"
            accessibilityLabel="Cancel alert, false alarm"
            style={[styles.ghostBtn, { marginTop: 12 }]}
          >
            <Text style={styles.ghostBtnText}>Cancel Alert / False Alarm</Text>
          </Pressable>
        )}
      </ScrollView>

      {/* Cancel confirmation modal */}
      <Modal visible={cancelOpen} transparent animationType="fade" onRequestClose={() => setCancelOpen(false)}>
        <View style={styles.modalBackdrop}>
          <View style={[styles.modalSheet, isDark && { backgroundColor: colors.card }]}>
            <View style={styles.modalHandle} />
            <Text style={[styles.modalTitle, isDark && { color: colors.text }]}>Cancel this alert?</Text>
            <Text style={[styles.modalBody, isDark && { color: colors.textSec }]}>
              Only cancel if everyone is safe. Responders already en route will be notified it was a false alarm.
            </Text>
            <PrimaryButton
              title="Yes, it was a false alarm"
              tone="critical"
              loading={cancelling}
              onPress={async () => {
                const ok = await onCancel();
                if (ok) setCancelOpen(false);
              }}
            />
            <PrimaryButton
              title="No, keep the alert active"
              tone="ghost"
              onPress={() => setCancelOpen(false)}
              style={{ marginTop: 10 }}
              disabled={cancelling}
            />
          </View>
        </View>
      </Modal>
    </SafeAreaView>
  );
}
