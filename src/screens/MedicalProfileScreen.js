import { SafeAreaView } from 'react-native-safe-area-context';
import { Ionicons } from '@expo/vector-icons';
import { useEffect, useState } from 'react';
import {
  KeyboardAvoidingView,
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
import { PrimaryButton } from '../components/PrimaryButton';
import { SectionCard } from '../components/SectionCard';
import { COMMUNICATION_TAGS, DISABILITY_CHIPS } from '../constants/data';
import { api } from '../services/api';
import { C } from '../theme/colors';
import { styles } from '../theme/styles';

/* ============================================================================
 * SCREEN 7 — MEDICAL & PERSONAL INFORMATION (Table 1.7)
 * ========================================================================== */

export function MedicalProfileScreen({ child, token, go, onSaved, showToast, unread }) {
  const { isDark, colors, t, lang } = useApp();
  const getInitialForm = (c) => ({
    name: c?.name || '',
    age: c && c.age != null ? String(c.age) : '',
    disability: c?.disability || '',
    allergies: c?.allergies || '',
    medications: c?.medications || '',
    communication_notes: c?.communication_notes || '',
    guardian_phone: c?.guardian_phone || '',
    emergency_contact_name: c?.emergency_contact_name || '',
    emergency_contact_phone: c?.emergency_contact_phone || '',
  });

  const [form, setForm]               = useState(() => getInitialForm(child));
  const [errors, setErrors]           = useState({});
  const [saving, setSaving]           = useState(false);
  const [pickerOpen, setPickerOpen]   = useState(false);

  useEffect(() => {
    if (child) {
      setForm(getInitialForm(child));
    }
  }, [child]);

  const set = (key) => (value) => {
    setForm((f) => ({ ...f, [key]: value }));
    if (errors[key]) setErrors((e) => ({ ...e, [key]: undefined }));
  };

  const validate = () => {
    const e = {};
    if (!form.name?.trim()) e.name = "Enter your child's full name.";
    if (!form.age?.trim() || isNaN(Number(form.age)) || Number(form.age) < 0 || Number(form.age) > 25)
      e.age = 'Enter a valid age (0–25).';
    if (!form.disability?.trim()) e.disability = 'Select or enter a disability classification.';
    if (!form.guardian_phone?.trim()) e.guardian_phone = 'Enter your primary guardian phone number.';
    if (!form.emergency_contact_phone?.trim()) e.emergency_contact_phone = 'Enter a backup emergency contact number.';
    setErrors(e);
    return Object.keys(e).length === 0;
  };

  const save = async () => {
    if (!validate()) {
      showToast('Please fix the highlighted fields.', 'error');
      return;
    }
    setSaving(true);
    try {
      const data = await api('/child', { method: 'PUT', token, body: { ...form, age: Number(form.age) } });
      onSaved(data.child || form);
      showToast('Changes saved successfully.');
    } catch (err) {
      if (err.errors) {
        const mapped = {};
        Object.keys(err.errors).forEach((k) => {
          mapped[k] = Array.isArray(err.errors[k]) ? err.errors[k][0] : String(err.errors[k]);
        });
        setErrors(mapped);
      }
      showToast(err.message, 'error');
    } finally {
      setSaving(false);
    }
  };

  const handleDisabilitySelect = (item) => {
    setForm((f) => ({ ...f, disability: item }));
    if (errors.disability) setErrors((e) => ({ ...e, disability: undefined }));
    setPickerOpen(false);
  };

  const appendCommTag = (tag) => {
    setForm((f) => {
      const current = f.communication_notes ? f.communication_notes.trim() : '';
      if (current.toLowerCase().includes(tag.toLowerCase())) return f;
      const next = current ? `${current}; ${tag}` : tag;
      return { ...f, communication_notes: next };
    });
  };

  return (
    <SafeAreaView style={[styles.flex, { backgroundColor: colors.bg }]} edges={['top', 'left', 'right']}>
      <StatusBar barStyle="light-content" backgroundColor={C.brand} />

      {/* Header bar */}
      <View style={styles.innerHeader}>
        <Text style={styles.innerHeaderTitle} accessibilityRole="header">{t('medicalTitle')}</Text>
      </View>

      <KeyboardAvoidingView style={styles.flex} behavior={Platform.OS === 'ios' ? 'padding' : undefined}>
        <ScrollView contentContainerStyle={styles.medScroll} keyboardShouldPersistTaps="handled" showsVerticalScrollIndicator={false}>

          {/* ── Section 1: Child Profile Fields (Table 1.7) ── */}
          <Text style={[styles.sectionHeading, isDark && { color: colors.brand }]}>{t('childClassification')}</Text>
          <SectionCard>
            <Field
              label="Child's Full Name"
              value={form.name}
              onChangeText={set('name')}
              error={errors.name}
              autoCapitalize="words"
              leftIcon="person-outline"
              placeholder="e.g. Mateo Garcia"
            />
            <Field
              label="Age (Years)"
              value={form.age}
              onChangeText={set('age')}
              error={errors.age}
              keyboardType="number-pad"
              leftIcon="calendar-outline"
              placeholder="e.g. 8"
            />

            {/* Disability Classification Dropdown Trigger (Table 1.7) */}
            <Text style={[styles.label, isDark && { color: colors.text }]}>Disability Classification (Dropdown)</Text>
            <Pressable
              onPress={() => setPickerOpen(true)}
              accessibilityRole="button"
              accessibilityLabel="Open Disability Classification dropdown"
              style={[
                styles.dropdownSelector,
                isDark && { backgroundColor: colors.card, borderColor: colors.border },
                !!errors.disability && { borderColor: C.critical },
              ]}
            >
              <Ionicons name="accessibility-outline" size={20} color={colors.brand} style={{ marginRight: 10 }} />
              <Text
                style={{
                  flex: 1,
                  fontSize: 15,
                  color: form.disability ? colors.text : colors.muted,
                  fontWeight: form.disability ? '700' : '400',
                }}
              >
                {form.disability || (lang === 'Filipino' ? 'Pumili ng Klasipikasyon…' : 'Select Disability Classification…')}
              </Text>
              <Ionicons name="chevron-down" size={20} color={C.muted} />
            </Pressable>

            {/* Quick-select classification chips */}
            <View style={[styles.chipWrap, { marginTop: 10 }]}>
              {DISABILITY_CHIPS.map((chip) => {
                const active = form.disability === chip;
                return (
                  <Pressable
                    key={chip}
                    onPress={() => handleDisabilitySelect(chip)}
                    accessibilityRole="checkbox"
                    accessibilityState={{ checked: active }}
                    style={[
                      styles.chip,
                      isDark && { backgroundColor: colors.card, borderColor: colors.border },
                      active && { backgroundColor: colors.brand, borderColor: colors.brand },
                    ]}
                  >
                    <Text style={[styles.chipText, isDark && { color: colors.text }, active && { color: C.white }]}>{chip}</Text>
                  </Pressable>
                );
              })}
            </View>

            <Field
              label="Custom / Specific Diagnosis"
              value={form.disability}
              onChangeText={set('disability')}
              error={errors.disability}
              placeholder="Or type specific condition..."
              style={{ marginTop: 4 }}
            />
          </SectionCard>

          {/* ── Section 2: Medical Notes & Communication Needs (Table 1.7) ── */}
          <Text style={[styles.sectionHeading, isDark && { color: colors.brand }]}>{t('medicalSensory')}</Text>
          <SectionCard>
            <Field
              label={t('allergiesLabel')}
              value={form.allergies}
              onChangeText={set('allergies')}
              multiline
              placeholder="List food/drug allergies, or write 'None'"
              leftIcon="alert-circle-outline"
            />
            <Field
              label={t('medicationLabel')}
              value={form.medications}
              onChangeText={set('medications')}
              multiline
              placeholder="Medication name, dosage & schedule, or 'None'"
              leftIcon="medkit-outline"
            />

            <Text style={[styles.label, isDark && { color: colors.text }]}>Quick-Add Communication & Sensory Tags</Text>
            <View style={[styles.chipWrap, { marginBottom: 10 }]}>
              {COMMUNICATION_TAGS.map((tag) => (
                <Pressable
                  key={tag}
                  onPress={() => appendCommTag(tag)}
                  style={[styles.commTagChip, isDark && { backgroundColor: colors.card, borderColor: colors.border }]}
                >
                  <Ionicons name="add-circle-outline" size={14} color={colors.brand} style={{ marginRight: 4 }} />
                  <Text style={{ color: colors.brand, fontSize: 12, fontWeight: '700' }}>{tag}</Text>
                </Pressable>
              ))}
            </View>

            <Field
              label={t('notesLabel')}
              value={form.communication_notes}
              onChangeText={set('communication_notes')}
              multiline
              placeholder="e.g. Non-verbal, sound sensitivity, uses visual schedule cards"
              leftIcon="chatbubble-ellipses-outline"
            />
          </SectionCard>

          {/* ── Section 3: Emergency Contacts (Table 1.7) ── */}
          <Text style={[styles.sectionHeading, isDark && { color: colors.brand }]}>{t('emergencyContact')}</Text>
          <SectionCard>
            <Field
              label="Primary Guardian Phone"
              value={form.guardian_phone}
              onChangeText={set('guardian_phone')}
              error={errors.guardian_phone}
              keyboardType="phone-pad"
              leftIcon="call-outline"
              placeholder="+63 9XX XXX XXXX"
            />
            <Field
              label="Backup Contact Name"
              value={form.emergency_contact_name}
              onChangeText={set('emergency_contact_name')}
              autoCapitalize="words"
              leftIcon="person-add-outline"
              placeholder="Full name of secondary contact"
            />
            <Field
              label="Backup Contact Phone"
              value={form.emergency_contact_phone}
              onChangeText={set('emergency_contact_phone')}
              error={errors.emergency_contact_phone}
              keyboardType="phone-pad"
              leftIcon="call-outline"
              placeholder="+63 9XX XXX XXXX"
            />
          </SectionCard>

          {/* Action Control: "Save Changes" button with validation & confirmation toast (Table 1.7) */}
          <PrimaryButton
            title={t('saveProfileBtn')}
            tone="brand"
            icon="save-outline"
            onPress={save}
            loading={saving}
            style={{ marginTop: 8 }}
          />
          <View style={{ height: 16 }} />
        </ScrollView>
      </KeyboardAvoidingView>

      {/* Disability Classification Dropdown Modal */}
      <Modal visible={pickerOpen} transparent animationType="fade" onRequestClose={() => setPickerOpen(false)}>
        <View style={styles.modalBackdrop}>
          <View style={[styles.modalSheet, isDark && { backgroundColor: colors.card }]}>
            <View style={styles.modalHandle} />
            <Text style={[styles.modalTitle, isDark && { color: colors.text }]}>Select Disability Classification</Text>
            <Text style={[styles.modalBody, isDark && { color: colors.textSec }]}>
              Choose the primary classification registered with the Parañaque CSN Center.
            </Text>
            {DISABILITY_CHIPS.map((item) => {
              const selected = form.disability === item;
              return (
                <Pressable
                  key={item}
                  onPress={() => handleDisabilitySelect(item)}
                  style={[
                    styles.dropdownModalItem,
                    isDark && { backgroundColor: colors.bg, borderColor: colors.border },
                    selected && { backgroundColor: isDark ? '#0C4A6E' : C.brandLight, borderColor: colors.brand },
                  ]}
                >
                  <Text style={{ flex: 1, fontSize: 15, fontWeight: selected ? '800' : '600', color: selected ? colors.brand : colors.text }}>
                    {item}
                  </Text>
                  {selected && <Ionicons name="checkmark-circle" size={20} color={colors.brand} />}
                </Pressable>
              );
            })}
            <PrimaryButton
              title={t('closeBtn')}
              tone="ghost"
              onPress={() => setPickerOpen(false)}
              style={{ marginTop: 8 }}
            />
          </View>
        </View>
      </Modal>

      <BottomTabBar current="medical" onPress={(k) => go(k)} unread={unread} />
    </SafeAreaView>
  );
}
