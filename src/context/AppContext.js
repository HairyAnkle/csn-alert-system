import React, { createContext, useContext, useState, useEffect, useCallback } from 'react';
import AsyncStorage from '@react-native-async-storage/async-storage';
import { getThemeColors, LIGHT_COLORS } from '../theme/colors';
import { getTranslation } from '../constants/translations';

const SETTINGS_KEY = 'csn_guardian_app_settings';

export const AppContext = createContext({
  settings: { theme: 'light', language: 'English', pushNotifications: true },
  theme: 'light',
  isDark: false,
  colors: LIGHT_COLORS,
  lang: 'English',
  pushNotifications: true,
  t: (key) => key,
  updateSettings: () => {},
});

export function AppProvider({ children }) {
  const [settings, setSettings] = useState({
    theme: 'light',
    language: 'English',
    pushNotifications: true,
  });

  // Load saved settings from AsyncStorage on startup
  useEffect(() => {
    let isMounted = true;
    (async () => {
      try {
        const raw = await AsyncStorage.getItem(SETTINGS_KEY);
        if (raw && isMounted) {
          const parsed = JSON.parse(raw);
          setSettings((prev) => ({ ...prev, ...parsed }));
        }
      } catch (_) {}
    })();
    return () => { isMounted = false; };
  }, []);

  const updateSettings = useCallback((patch) => {
    setSettings((prev) => {
      const next = { ...prev, ...patch };
      AsyncStorage.setItem(SETTINGS_KEY, JSON.stringify(next)).catch(() => {});
      return next;
    });
  }, []);

  const isDark = settings.theme === 'dark';
  const colors = getThemeColors(settings.theme);
  const lang = settings.language || 'English';

  const t = useCallback((key) => getTranslation(key, lang), [lang]);

  return (
    <AppContext.Provider
      value={{
        settings,
        theme: settings.theme,
        isDark,
        colors,
        lang,
        pushNotifications: settings.pushNotifications !== false,
        updateSettings,
        t,
      }}
    >
      {children}
    </AppContext.Provider>
  );
}

export function useApp() {
  return useContext(AppContext);
}

