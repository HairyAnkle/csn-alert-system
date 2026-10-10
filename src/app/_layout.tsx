import { Stack } from 'expo-router';
import * as SplashScreen from 'expo-splash-screen';

SplashScreen.preventAutoHideAsync().catch(() => {});

export default function RootLayout() {
  return (
    <Stack screenOptions={{ headerShown: false }}>
      <Stack.Screen name="index" options={{ headerShown: false }} />
    </Stack>
  );
}
