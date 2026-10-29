/**
 * Parañaque CSN Emergency Alert System — Guardian Mobile App (Expo / React Native)
 * Install (inside your Expo project):
 *   npx expo install expo-location @expo/vector-icons
 */
import { Ionicons } from '@expo/vector-icons';
import * as Location from 'expo-location';
import { useCallback, useEffect, useState } from 'react';
import { ActivityIndicator, StatusBar, View } from 'react-native';
import { firebaseLogout } from './src/firebase';

import { Toast } from './src/components/Toast';
import { API_BASE_URL, DEV_BYPASS_LOGIN, IS_DEV } from './src/config/env';
import { DEFAULT_PARANAQUE_COORDS, POLL_INTERVAL_MS } from './src/constants/app';
import { AlertHistoryScreen } from './src/screens/AlertHistoryScreen';
import { AuthScreen } from './src/screens/AuthScreen';
import { DashboardScreen } from './src/screens/DashboardScreen';
import { EmergencyActiveScreen } from './src/screens/EmergencyActiveScreen';
import { LiveLocationScreen } from './src/screens/LiveLocationScreen';
import { MedicalProfileScreen } from './src/screens/MedicalProfileScreen';
import { SettingsScreen } from './src/screens/SettingsScreen';
import { SupportScreen } from './src/screens/SupportScreen';
import { WelcomeScreen } from './src/screens/WelcomeScreen';
import { api } from './src/services/api';
import { devState } from './src/services/devMock';
import { getStoredToken, removeStoredToken, saveStoredToken } from './src/services/tokenStorage';
import { C } from './src/theme/colors';
import { styles } from './src/theme/styles';

import { AppProvider, useApp } from './src/context/AppContext';

/* ============================================================================
 * ROOT APP
 * ========================================================================== */

function MainApp() {
  const { settings, updateSettings, isDark, colors, t } = useApp();
  const [bootstrapping, setBootstrapping] = useState(true);
  const [screen, setScreen]               = useState('welcome');
  const [screenParams, setScreenParams]   = useState({});
  const [token, setToken]                 = useState(null);
  const [guardian, setGuardian]           = useState(null);
  const [child, setChild]                 = useState(null);

  const [gps, setGps]               = useState({ state: 'checking' }); // checking | active | denied
  const [coords, setCoords]         = useState(null);
  const [liveSharing, setLiveSharing] = useState(true);

  const [emergency, setEmergency]         = useState(null);
  const [triggering, setTriggering]       = useState(false);
  const [triggerError, setTriggerError]   = useState('');
  const [cancelling, setCancelling]       = useState(false);

  const [unread, setUnread]             = useState(0);
  const [seenIds, setSeenIds]           = useState([]);
  const [dismissedIds, setDismissedIds] = useState([]);

  const [toast, setToast] = useState(null);
  const showToast = useCallback((message, tone = 'success') => setToast({ message, tone, id: Date.now() }), []);

  const go = useCallback((next, params = {}) => {
    setScreenParams(params);
    setScreen(next);
  }, []);

  /* ---- auth ---- */
  const onAuthed = async (data) => {
    setToken(data.token);
    setGuardian(data.guardian || null);
    setChild(data.child || null);
    if (data.token) {
      await saveStoredToken(data.token);
    }
    go('dashboard');
  };

  const logout = async () => {
    try {
      await firebaseLogout();
      if (token) {
        await api('/auth/logout', { method: 'POST', token });
      }
    } catch {}
    await removeStoredToken();
    setToken(null);
    setGuardian(null);
    setChild(null);
    setEmergency(null);
    go('welcome');
  };

  // Auto-restore login session on startup (Screen 1 Table 1.0 auto-redirect logic)
  useEffect(() => {
    let isMounted = true;
    (async () => {
      try {
        if (DEV_BYPASS_LOGIN) {
          if (isMounted) {
            setToken('dev-token');
            setGuardian(devState.guardian);
            setChild(devState.child);
            go('dashboard');
          }
          return;
        }

        const savedToken = await getStoredToken();
        if (savedToken && API_BASE_URL) {
          const data = await api('/me', { token: savedToken });
          if (isMounted && data && data.guardian) {
            setToken(savedToken);
            setGuardian(data.guardian || null);
            setChild(data.child || null);
            go('dashboard');
            return;
          }
        }
      } catch {
        await removeStoredToken();
      } finally {
        if (isMounted) setBootstrapping(false);
      }
    })();

    return () => {
      isMounted = false;
    };
  }, [go]);

  /* ---- GPS permission + live position on Dashboard & Location screens ---- */
  const refreshGps = useCallback(async () => {
    try {
      const perm = await Location.requestForegroundPermissionsAsync();
      if (perm.status !== 'granted') {
        setGps({ state: 'denied' });
        if (IS_DEV) setCoords((prev) => prev || DEFAULT_PARANAQUE_COORDS);
        return;
      }
      const pos = await Location.getCurrentPositionAsync({ accuracy: Location.Accuracy.High });
      setCoords(pos.coords);
      setGps({ state: 'active' });
    } catch {
      setGps({ state: 'denied' });
      if (IS_DEV) setCoords((prev) => prev || DEFAULT_PARANAQUE_COORDS);
    }
  }, []);

  useEffect(() => {
    if (screen !== 'dashboard' && screen !== 'location') return undefined;
    let sub;
    let cancelled = false;
    (async () => {
      try {
        const perm = await Location.requestForegroundPermissionsAsync();
        if (cancelled) return;
        if (perm.status !== 'granted') {
          setGps({ state: 'denied' });
          if (IS_DEV) setCoords((prev) => prev || DEFAULT_PARANAQUE_COORDS);
          return;
        }
        sub = await Location.watchPositionAsync(
          { accuracy: Location.Accuracy.High, timeInterval: 10000, distanceInterval: 10 },
          (pos) => {
            setCoords(pos.coords);
            setGps({ state: 'active' });
          }
        );
      } catch {
        if (!cancelled) {
          setGps({ state: 'denied' });
          if (IS_DEV) setCoords((prev) => prev || DEFAULT_PARANAQUE_COORDS);
        }
      }
    })();
    return () => {
      cancelled = true;
      if (sub) sub.remove();
    };
  }, [screen]);

  /* ---- unread announcements count (dashboard bell & inbox badge) ---- */
  useEffect(() => {
    if (!token) return;
    api('/announcements', { token })
      .then((d) => {
        const list = (d && d.announcements) || [];
        const activeList = list.filter((n) => !dismissedIds.includes(n.id));
        setUnread(activeList.filter((n) => !seenIds.includes(n.id)).length);
      })
      .catch(() => {});
  }, [screen, token, seenIds, dismissedIds]);

  const onMarkRead = useCallback((ids) => {
    setSeenIds((prev) => {
      const next = Array.from(new Set([...prev, ...ids]));
      return next.length === prev.length ? prev : next;
    });
  }, []);

  const onDismissAnnouncement = useCallback((id) => {
    setDismissedIds((prev) => (prev.includes(id) ? prev : [...prev, id]));
  }, []);

  /* ---- trigger emergency (Screen 5 Table 1.5) ---- */
  const triggerEmergency = async (payload) => {
    const isObj = typeof payload === 'object' && payload !== null;
    const type = isObj ? payload.type : payload;
    const urgency = isObj && payload.urgency ? payload.urgency : 'high';
    const message = isObj && payload.message ? payload.message : '';
    const shareLoc = isObj && typeof payload.shareLocation === 'boolean' ? payload.shareLocation : true;

    setLiveSharing(shareLoc);
    setTriggerError('');
    setTriggering(true);
    let position = coords;
    if (shareLoc) {
      try {
        const fresh = await Location.getCurrentPositionAsync({ accuracy: Location.Accuracy.High });
        position = fresh.coords;
        setCoords(fresh.coords);
      } catch {
        if (IS_DEV && !position) position = DEFAULT_PARANAQUE_COORDS;
      }
    }
    try {
      const data = await api('/emergencies', {
        method: 'POST',
        token,
        body: {
          type,
          urgency,
          message,
          guardian_notes: message,
          lat: shareLoc && position ? position.latitude : null,
          lng: shareLoc && position ? position.longitude : null,
        },
      });
      setEmergency(data.emergency);
      go('active');
    } catch (err) {
      setTriggerError(err.message);
    } finally {
      setTriggering(false);
    }
  };

  /* ---- while an emergency is active: poll status + stream location ---- */
  const emergencyId     = emergency ? emergency.id : null;
  const emergencyStatus = emergency ? emergency.status : null;
  const isLive =
    screen === 'active' &&
    emergencyId &&
    emergencyStatus !== 'resolved' &&
    emergencyStatus !== 'cancelled';

  useEffect(() => {
    if (!isLive) return undefined;
    const timer = setInterval(async () => {
      try {
        const data = await api(`/emergencies/${emergencyId}`, { token });
        if (data && data.emergency) setEmergency(data.emergency);
      } catch {
        /* keep trying on the next tick */
      }
    }, POLL_INTERVAL_MS);
    return () => clearInterval(timer);
  }, [isLive, emergencyId, token]);

  useEffect(() => {
    if (!isLive || !liveSharing) return undefined;
    let sub;
    let cancelled = false;
    (async () => {
      try {
        sub = await Location.watchPositionAsync(
          { accuracy: Location.Accuracy.High, timeInterval: 5000, distanceInterval: 5 },
          (pos) => {
            setCoords(pos.coords);
            api(`/emergencies/${emergencyId}/location`, {
              method: 'PATCH',
              token,
              body: { lat: pos.coords.latitude, lng: pos.coords.longitude },
            }).catch(() => {});
          }
        );
        if (cancelled && sub) sub.remove();
      } catch {
        /* no GPS; alert is already sent */
      }
    })();
    return () => {
      cancelled = true;
      if (sub) sub.remove();
    };
  }, [isLive, liveSharing, emergencyId, token]);

  const cancelEmergency = async () => {
    setCancelling(true);
    try {
      const data = await api(`/emergencies/${emergencyId}/cancel`, { method: 'POST', token });
      setEmergency((data && data.emergency) || { ...emergency, status: 'cancelled' });
      return true;
    } catch (err) {
      showToast(err.message, 'error');
      return false;
    } finally {
      setCancelling(false);
    }
  };

  /* ---- render ---- */
  let content;
  switch (screen) {
    case 'login':
      content = <AuthScreen mode="login" go={go} onAuthed={onAuthed} showToast={showToast} />;
      break;
    case 'signup':
      content = <AuthScreen mode="signup" go={go} onAuthed={onAuthed} showToast={showToast} />;
      break;
    case 'dashboard':
      content = (
        <DashboardScreen
          guardian={guardian}
          child={child}
          gps={gps}
          coords={coords}
          go={go}
          onTrigger={triggerEmergency}
          triggering={triggering}
          triggerError={triggerError}
          clearTriggerError={() => setTriggerError('')}
          unread={unread}
        />
      );
      break;
    case 'location':
      content = (
        <LiveLocationScreen
          child={child}
          gps={gps}
          coords={coords}
          liveSharing={liveSharing}
          onToggleLiveSharing={() => {
            setLiveSharing((v) => {
              const next = !v;
              showToast(next ? 'Live GPS location sharing started.' : 'Live GPS location sharing paused.');
              return next;
            });
          }}
          onRefreshGps={refreshGps}
          go={go}
          unread={unread}
        />
      );
      break;
    case 'active':
      content = (
        <EmergencyActiveScreen
          emergency={emergency}
          coords={coords}
          liveSharing={liveSharing}
          onToggleLiveSharing={() => setLiveSharing((v) => !v)}
          onCancel={cancelEmergency}
          cancelling={cancelling}
          onDone={() => {
            setEmergency(null);
            go('dashboard');
          }}
        />
      );
      break;
    case 'medical':
      content = (
        <MedicalProfileScreen
          child={child}
          token={token}
          go={go}
          onSaved={setChild}
          showToast={showToast}
          unread={unread}
        />
      );
      break;
    case 'history':
      content = (
        <AlertHistoryScreen
          token={token}
          child={child}
          go={go}
          initialTab={screenParams.tab || 'alerts'}
          showToast={showToast}
          seenIds={seenIds}
          onMarkRead={onMarkRead}
          dismissedIds={dismissedIds}
          onDismissAnnouncement={onDismissAnnouncement}
          unread={unread}
        />
      );
      break;
    case 'inbox':
      content = (
        <AlertHistoryScreen
          token={token}
          child={child}
          go={go}
          initialTab="inbox"
          showToast={showToast}
          seenIds={seenIds}
          onMarkRead={onMarkRead}
          dismissedIds={dismissedIds}
          onDismissAnnouncement={onDismissAnnouncement}
          unread={unread}
        />
      );
      break;
    case 'settings':
      content = (
        <SettingsScreen
          guardian={guardian}
          settings={settings}
          onUpdateSettings={updateSettings}
          go={go}
          onLogout={logout}
          showToast={showToast}
          unread={unread}
        />
      );
      break;
    case 'support':
      content = (
        <SupportScreen
          token={token}
          guardian={guardian}
          child={child}
          go={go}
          onLogout={logout}
          showToast={showToast}
          unread={unread}
        />
      );
      break;
    default:
      content = <WelcomeScreen go={go} />;
  }

  if (bootstrapping) {
    return (
      <View style={[styles.flex, { backgroundColor: C.brand, alignItems: 'center', justifyContent: 'center' }]}>
        <StatusBar barStyle="light-content" backgroundColor={C.brand} />
        <View style={styles.logoCircle}>
          <Ionicons name="shield-checkmark" size={40} color={C.brand} />
        </View>
        <ActivityIndicator size="large" color={C.white} style={{ marginTop: 24 }} />
      </View>
    );
  }

  return (
    <View style={[styles.flex, { backgroundColor: colors.bg }]}>
      <StatusBar barStyle="light-content" backgroundColor={C.brand} />
      {content}
      <Toast toast={toast} />
    </View>
  );
}

export default function App() {
  return (
    <AppProvider>
      <MainApp />
    </AppProvider>
  );
}
