import { SafeAreaView } from 'react-native-safe-area-context';
import { Ionicons } from '@expo/vector-icons';
import { useState } from 'react';
import {
  ActivityIndicator,
  KeyboardAvoidingView,
  Modal,
  Platform,
  Pressable,
  ScrollView,
  StatusBar,
  Text,
  View,
} from 'react-native';
import {
  firebaseLoginWithEmail,
  firebaseRegisterWithEmail,
  firebaseSendPasswordReset as firebaseResetPassword,
  firebaseGoogleSignIn,
  formatFirebaseAuthError,
} from '../firebase';

import { Field } from '../components/Field';
import { PrimaryButton } from '../components/PrimaryButton';
import { SectionCard } from '../components/SectionCard';
import { IS_DEV } from '../config/env';
import { api } from '../services/api';
import { getDevMockResponse } from '../services/devMock';
import { C, MIN_TOUCH } from '../theme/colors';
import { styles } from '../theme/styles';
import { evaluatePasswordStrength, isValidEmail } from '../utils/validation';

/* ============================================================================
 * SCREEN 2 & SCREEN 3 — LOGIN (Table 1.2) & SIGN-UP (Table 1.3)
 * Redesigned for 0-Cost Operation:
 * - Direct Email & Password authentication with Firebase Auth
 * - Native Google OAuth account selection redirection (prompt: 'select_account')
 * - Full account creation, login, and password reset flows
 * ========================================================================== */

export function AuthScreen({ mode, go, onAuthed, showToast }) {
  const isSignup = mode === 'signup';

  // Common fields
  const [name, setName]         = useState('');
  const [email, setEmail]       = useState('');
  const [password, setPassword] = useState('');
  const [showPass, setShowPass] = useState(false);
  const [errors, setErrors]     = useState({});
  const [loading, setLoading]   = useState(false);
  const [formError, setFormError] = useState('');

  // Forgot Password Modal (Table 1.2)
  const [forgotModalVisible, setForgotModalVisible] = useState(false);
  const [forgotEmail, setForgotEmail]               = useState('');
  const [forgotLoading, setForgotLoading]           = useState(false);
  const [forgotError, setForgotError]               = useState('');

  // Real-time field handlers
  const onChangeEmail = (val) => {
    setEmail(val);
    if (errors.email) {
      setErrors((prev) => ({
        ...prev,
        email: !val.trim() ? 'Please enter your email.' : !isValidEmail(val) ? 'Enter a valid email address.' : undefined,
      }));
    }
  };

  const onChangePassword = (val) => {
    setPassword(val);
    if (errors.password) {
      setErrors((prev) => ({
        ...prev,
        password: !val ? 'Please enter your password.' : isSignup && val.length < 8 ? 'Use at least 8 characters.' : undefined,
      }));
    }
  };

  const pwdStrength = evaluatePasswordStrength(password);

  /* --------------------------------------------------------------------------
   * 1. EMAIL + PASSWORD SUBMIT
   * ------------------------------------------------------------------------ */
  const validateEmailForm = () => {
    const e = {};
    if (isSignup && !name.trim()) e.name = 'Please enter your full name.';
    if (!email.trim()) e.email = 'Please enter your email address.';
    else if (!isValidEmail(email)) e.email = 'Enter a valid email address.';
    if (!password) e.password = 'Please enter your password.';
    else if (isSignup && password.length < 8) e.password = 'Use at least 8 characters.';
    setErrors(e);
    return Object.keys(e).length === 0;
  };

  const submitEmailAuth = async () => {
    setFormError('');
    if (!validateEmailForm()) return;
    setLoading(true);

    try {
      let firebaseToken = null;

      // 1. Authenticate with Firebase Auth SDK
      try {
        if (isSignup) {
          const fbRes = await firebaseRegisterWithEmail(name.trim(), email.trim(), password);
          firebaseToken = fbRes.idToken;
        } else {
          const fbRes = await firebaseLoginWithEmail(email.trim(), password);
          firebaseToken = fbRes.idToken;
        }
      } catch (fbErr) {
        console.warn('Firebase Auth notice:', fbErr?.code, fbErr?.message);
        if (!IS_DEV) {
          if (isSignup && fbErr.code === 'auth/email-already-in-use') {
            throw new Error(formatFirebaseAuthError(fbErr));
          } else if (!isSignup && (fbErr.code === 'auth/wrong-password' || fbErr.code === 'auth/invalid-credential')) {
            throw new Error(formatFirebaseAuthError(fbErr));
          } else if (fbErr.code === 'auth/weak-password' || fbErr.code === 'auth/invalid-email') {
            throw new Error(formatFirebaseAuthError(fbErr));
          }
        }
      }

      // 2. Exchange token with Laravel API
      let data;
      try {
        if (firebaseToken) {
          data = await api('/auth/firebase', {
            method: 'POST',
            body: {
              id_token: firebaseToken,
              name: isSignup ? name.trim() : undefined,
              role: 'guardian',
            },
          });
        } else {
          data = await api(isSignup ? '/auth/register' : '/auth/login', {
            method: 'POST',
            body: isSignup
              ? { name: name.trim(), email: email.trim(), password }
              : { email: email.trim(), password },
          });
        }
      } catch (apiErr) {
        if (IS_DEV) {
          console.log('[DEV TEST MODE] Auth API notice:', apiErr.message, '- serving dev mock');
          data = getDevMockResponse(isSignup ? '/auth/register' : '/auth/login', {
            method: 'POST',
            body: { name: name.trim() || 'Guardian Tester', email: email.trim() },
          });
        } else {
          throw apiErr;
        }
      }

      showToast(isSignup ? 'Account created successfully!' : 'Logged in successfully!');
      onAuthed(data);
    } catch (err) {
      if (err.errors) {
        const mapped = {};
        Object.keys(err.errors).forEach((k) => {
          mapped[k] = Array.isArray(err.errors[k]) ? err.errors[k][0] : String(err.errors[k]);
        });
        setErrors(mapped);
      }
      setFormError(err.message || 'Authentication failed. Please check your credentials.');
    } finally {
      setLoading(false);
    }
  };

  /* --------------------------------------------------------------------------
   * 2. GOOGLE SIGN-IN (Native OAuth with Firebase Authentication)
   * ------------------------------------------------------------------------ */
  const handleGoogleSignIn = async () => {
    setFormError('');
    setLoading(true);

    try {
      const res = await firebaseGoogleSignIn();

      if (!res) {
        return;
      }

      const googleEmail = res.user?.email || '';
      const googleName = res.user?.displayName || googleEmail.split('@')[0] || name.trim() || 'Google Guardian';
      const idToken = res.idToken || null;

      let data;
      try {
        if (idToken) {
          try {
            data = await api('/auth/firebase', {
              method: 'POST',
              body: {
                id_token: idToken,
                name: googleName,
                role: 'guardian',
              },
            });
          } catch (fbApiErr) {
            console.warn('[Auth] /auth/firebase notice, trying /auth/google fallback:', fbApiErr?.message);
            data = await api('/auth/google', {
              method: 'POST',
              body: {
                id_token: idToken,
                email: googleEmail,
                name: googleName,
                role: 'guardian',
              },
            });
          }
        } else {
          data = await api('/auth/google', {
            method: 'POST',
            body: {
              email: googleEmail,
              name: googleName,
              role: 'guardian',
            },
          });
        }
      } catch (apiErr) {
        if (IS_DEV) {
          data = getDevMockResponse('/auth/google', {
            method: 'POST',
            body: { name: googleName, email: googleEmail },
          });
        } else {
          throw apiErr;
        }
      }

      showToast(googleEmail ? `Signed in as ${googleEmail}!` : 'Signed in successfully with Google!');
      onAuthed(data);
    } catch (err) {
      if (err.code === 'auth/popup-closed-by-user') {
        return; // Cleanly ignore user cancel
      }
      setFormError(err.message || 'Google Sign-In failed. Please try again.');
    } finally {
      setLoading(false);
    }
  };

  /* --------------------------------------------------------------------------
   * 4. FORGOT PASSWORD
   * ------------------------------------------------------------------------ */
  const openForgotModal = () => {
    setForgotEmail(email.trim());
    setForgotError('');
    setForgotModalVisible(true);
  };

  const submitForgotPassword = async () => {
    const target = forgotEmail.trim();
    if (!target || !isValidEmail(target)) {
      setForgotError('Please enter a valid email address.');
      return;
    }
    setForgotLoading(true);
    setForgotError('');
    try {
      try {
        await firebaseResetPassword(target);
        setForgotModalVisible(false);
        showToast('Password reset link sent! Check your email.');
        return;
      } catch {
        // Fallback to Laravel reset
      }
      const data = await api('/auth/forgot-password', { method: 'POST', body: { email: target } });
      setForgotModalVisible(false);
      showToast((data && data.message) || 'Password reset link sent. Check your email.');
    } catch (err) {
      setForgotError(err.message || 'Could not send reset email.');
    } finally {
      setForgotLoading(false);
    }
  };

  return (
    <SafeAreaView style={[styles.flex, { backgroundColor: C.bg }]}>
      <StatusBar barStyle="dark-content" backgroundColor={C.bg} />

      {/* Header */}
      <View style={styles.authHeader}>
        <Pressable
          onPress={() => go('welcome')}
          accessibilityRole="button"
          accessibilityLabel="Go back"
          hitSlop={8}
          style={styles.backBtn}
        >
          <Ionicons name="chevron-back" size={26} color={C.text} />
        </Pressable>
        <Text style={styles.authHeaderTitle} accessibilityRole="header">
          {isSignup ? 'Sign Up' : 'Login'}
        </Text>
        <View style={{ width: MIN_TOUCH }} />
      </View>

      <KeyboardAvoidingView style={styles.flex} behavior={Platform.OS === 'ios' ? 'padding' : undefined}>
        <ScrollView contentContainerStyle={styles.authScroll} keyboardShouldPersistTaps="handled">
          <Text style={styles.authSubtitle}>
            {isSignup
              ? 'Create your guardian account to enroll your child and access emergency dispatch.'
              : 'Sign in to your Parañaque CSN guardian account.'}
          </Text>

          {/* Error Banner */}
          {!!formError && (
            <View style={styles.errorBanner} accessibilityLiveRegion="assertive">
              <Ionicons name="alert-circle" size={20} color={C.critical} />
              <Text style={styles.errorBannerText}>{formError}</Text>
            </View>
          )}

          {/* Main Auth Card */}
          <SectionCard style={{ marginTop: 8 }}>
            {isSignup && (
              <Field
                label="Guardian Full Name"
                value={name}
                onChangeText={(v) => {
                  setName(v);
                  if (errors.name && v.trim()) setErrors((p) => ({ ...p, name: undefined }));
                }}
                error={errors.name}
                autoCapitalize="words"
                leftIcon="person-outline"
                placeholder="e.g. Maria Garcia"
              />
            )}

            <Field
              label="Email Address"
              value={email}
              onChangeText={onChangeEmail}
              error={errors.email}
              keyboardType="email-address"
              autoCapitalize="none"
              autoCorrect={false}
              leftIcon="mail-outline"
              placeholder="guardian@email.com"
            />

            <Field
              label="Password"
              value={password}
              onChangeText={onChangePassword}
              error={errors.password}
              secureTextEntry={!showPass}
              autoCapitalize="none"
              leftIcon="lock-closed-outline"
              placeholder="••••••••"
              rightNode={
                <Pressable
                  onPress={() => setShowPass((v) => !v)}
                  hitSlop={10}
                  accessibilityRole="button"
                  accessibilityLabel={showPass ? 'Hide password' : 'Show password'}
                  style={{ minWidth: 36, minHeight: 36, alignItems: 'center', justifyContent: 'center' }}
                >
                  <Ionicons name={showPass ? 'eye-off-outline' : 'eye-outline'} size={20} color={C.muted} />
                </Pressable>
              }
            />

            {isSignup && password.length > 0 && (
              <View style={{ marginTop: -8, marginBottom: 14 }}>
                <View style={{ flexDirection: 'row', gap: 6, marginBottom: 4 }}>
                  {[1, 2, 3, 4].map((lvl) => (
                    <View
                      key={lvl}
                      style={{
                        flex: 1,
                        height: 5,
                        borderRadius: 3,
                        backgroundColor: pwdStrength.score >= lvl ? pwdStrength.color : C.border,
                      }}
                    />
                  ))}
                </View>
                <Text style={{ fontSize: 12, fontWeight: '700', color: pwdStrength.color }}>
                  Password Strength: {pwdStrength.label}
                </Text>
              </View>
            )}

            {!isSignup && (
              <Pressable onPress={openForgotModal} accessibilityRole="button" style={styles.forgotBtn}>
                <Text style={styles.forgotText}>Forgot Password?</Text>
              </Pressable>
            )}

            <PrimaryButton
              title={isSignup ? 'Create Account' : 'Login'}
              onPress={submitEmailAuth}
              loading={loading}
              style={{ marginTop: 4 }}
            />

            {/* Divider */}
            <View style={styles.dividerRow}>
              <View style={styles.dividerLine} />
              <Text style={styles.dividerText}>or continue with</Text>
              <View style={styles.dividerLine} />
            </View>

            {/* Google Sign-In with Account Selection Redirection */}
            <Pressable
              onPress={handleGoogleSignIn}
              disabled={loading}
              accessibilityRole="button"
              accessibilityLabel={isSignup ? 'Sign Up with Google' : 'Sign-In with Google'}
              style={({ pressed }) => [
                styles.googleBtn,
                pressed && { opacity: 0.8 },
                loading && { opacity: 0.6 },
              ]}
            >
              {loading ? (
                <ActivityIndicator size="small" color={C.brand} style={{ marginRight: 8 }} />
              ) : (
                <Ionicons name="logo-google" size={18} color="#EA4335" style={{ marginRight: 8 }} />
              )}
              <Text style={styles.googleBtnText}>
                {isSignup ? 'Sign Up with Google' : 'Sign-In with Google'}
              </Text>
            </Pressable>
            <Text style={{ textAlign: 'center', fontSize: 11, color: C.muted, marginTop: 6 }}>
              Choose your Google Account to sign in seamlessly
            </Text>
          </SectionCard>

          {/* Switch between Login and Sign Up */}
          <Pressable
            onPress={() => {
              setErrors({});
              setFormError('');
              go(isSignup ? 'login' : 'signup');
            }}
            accessibilityRole="button"
            style={styles.switchAuthBtn}
          >
            <Text style={styles.switchAuthText}>
              {isSignup ? 'Already have an account? ' : "Don't have an account? "}
              <Text style={{ color: C.brand, fontWeight: '800' }}>
                {isSignup ? 'Login' : 'Sign Up'}
              </Text>
            </Text>
          </Pressable>
        </ScrollView>
      </KeyboardAvoidingView>

      {/* ── Forgot Password Email Modal (Table 1.2) ── */}
      <Modal
        visible={forgotModalVisible}
        transparent
        animationType="fade"
        onRequestClose={() => setForgotModalVisible(false)}
      >
        <View style={styles.modalBackdrop}>
          <View style={styles.modalSheet}>
            <View style={styles.modalHandle} />
            <View style={[styles.modalIconCircle, { backgroundColor: C.brandLight }]}>
              <Ionicons name="mail-unread-outline" size={30} color={C.brand} />
            </View>
            <Text style={styles.modalTitle}>Reset Your Password</Text>
            <Text style={styles.modalBody}>
              Enter the email address associated with your guardian account and we will send a password reset link.
            </Text>

            {!!forgotError && (
              <View style={[styles.errorBanner, { width: '100%', marginBottom: 12 }]}>
                <Ionicons name="alert-circle" size={18} color={C.critical} />
                <Text style={styles.errorBannerText}>{forgotError}</Text>
              </View>
            )}

            <Field
              label="Registered Email Address"
              value={forgotEmail}
              onChangeText={(t) => { setForgotEmail(t); setForgotError(''); }}
              placeholder="guardian@email.com"
              keyboardType="email-address"
              autoCapitalize="none"
              leftIcon="mail-outline"
            />

            <PrimaryButton
              title="Send Reset Link"
              icon="send"
              loading={forgotLoading}
              onPress={submitForgotPassword}
            />
            <PrimaryButton
              title="Cancel"
              tone="ghost"
              onPress={() => setForgotModalVisible(false)}
              style={{ marginTop: 10 }}
            />
          </View>
        </View>
      </Modal>
    </SafeAreaView>
  );
}
