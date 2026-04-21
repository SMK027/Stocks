import React, { useState } from 'react';
import {
  View,
  Text,
  StyleSheet,
  TouchableOpacity,
  Modal,
  Platform,
} from 'react-native';
import DateTimePicker, { DateTimePickerEvent } from '@react-native-community/datetimepicker';
import { Ionicons } from '@expo/vector-icons';
import { colors, spacing, typography, borderRadius } from '../theme';

interface Props {
  label: string;
  /** Valeur au format ISO YYYY-MM-DD, ou '' si vide */
  value: string;
  onChange: (iso: string) => void;
  placeholder?: string;
  /** Affiche un bouton pour effacer la valeur */
  optional?: boolean;
  icon?: keyof typeof Ionicons.glyphMap;
  minDate?: Date;
  maxDate?: Date;
}

/** Convertit une chaîne YYYY-MM-DD en Date locale (midi, évite les décalages UTC) */
function parseDate(iso: string): Date {
  const [y, m, d] = iso.split('-').map(Number);
  return new Date(y, m - 1, d, 12, 0, 0);
}

function toIso(date: Date): string {
  const y = date.getFullYear();
  const m = String(date.getMonth() + 1).padStart(2, '0');
  const d = String(date.getDate()).padStart(2, '0');
  return `${y}-${m}-${d}`;
}

export default function DatePickerField({
  label,
  value,
  onChange,
  placeholder = 'Sélectionner une date',
  optional = false,
  icon = 'calendar-outline',
  minDate,
  maxDate,
}: Props) {
  const [visible, setVisible] = useState(false);
  // Utilisé sur iOS pour stocker la sélection temporaire avant confirmation
  const [tempDate, setTempDate] = useState<Date>(new Date());

  const currentDate = value ? parseDate(value) : new Date();

  const displayLabel = value
    ? parseDate(value).toLocaleDateString('fr-FR', {
        day: '2-digit',
        month: 'long',
        year: 'numeric',
      })
    : null;

  const openPicker = () => {
    setTempDate(value ? parseDate(value) : new Date());
    setVisible(true);
  };

  // ─── Android ─────────────────────────────────────────────────────────────
  const handleAndroid = (event: DateTimePickerEvent, date?: Date) => {
    setVisible(false);
    if (event.type === 'set' && date) {
      onChange(toIso(date));
    }
  };

  // ─── iOS ─────────────────────────────────────────────────────────────────
  const handleIOS = (_event: DateTimePickerEvent, date?: Date) => {
    if (date) setTempDate(date);
  };

  const confirmIOS = () => {
    onChange(toIso(tempDate));
    setVisible(false);
  };

  const cancelIOS = () => setVisible(false);

  return (
    <View>
      <Text style={styles.label}>{label}</Text>

      <View style={styles.row}>
        <TouchableOpacity
          style={styles.picker}
          onPress={openPicker}
          activeOpacity={0.75}
        >
          <Ionicons name={icon} size={18} color={colors.textSecondary} />
          <Text style={[styles.pickerText, !displayLabel && styles.placeholder]}>
            {displayLabel ?? placeholder}
          </Text>
          <Ionicons name="chevron-down" size={16} color={colors.textMuted} />
        </TouchableOpacity>

        {optional && value ? (
          <TouchableOpacity
            style={styles.clearBtn}
            onPress={() => onChange('')}
            hitSlop={{ top: 8, bottom: 8, left: 8, right: 8 }}
          >
            <Ionicons name="close-circle" size={22} color={colors.textMuted} />
          </TouchableOpacity>
        ) : null}
      </View>

      {/* ── Android : dialog natif ───────────────────────────────────────── */}
      {Platform.OS === 'android' && visible && (
        <DateTimePicker
          value={currentDate}
          mode="date"
          display="default"
          onChange={handleAndroid}
          minimumDate={minDate}
          maximumDate={maxDate}
        />
      )}

      {/* ── iOS : spinner dans un modal ──────────────────────────────────── */}
      {Platform.OS === 'ios' && (
        <Modal visible={visible} transparent animationType="slide">
          <View style={styles.overlay}>
            <TouchableOpacity style={styles.backdrop} activeOpacity={1} onPress={cancelIOS} />
            <View style={styles.sheet}>
              <View style={styles.sheetHeader}>
                <TouchableOpacity onPress={cancelIOS} hitSlop={{ top: 8, bottom: 8, left: 8, right: 8 }}>
                  <Text style={styles.btnCancel}>Annuler</Text>
                </TouchableOpacity>
                <Text style={styles.sheetTitle}>{label}</Text>
                <TouchableOpacity onPress={confirmIOS} hitSlop={{ top: 8, bottom: 8, left: 8, right: 8 }}>
                  <Text style={styles.btnDone}>OK</Text>
                </TouchableOpacity>
              </View>
              <DateTimePicker
                value={tempDate}
                mode="date"
                display="spinner"
                onChange={handleIOS}
                minimumDate={minDate}
                maximumDate={maxDate}
                locale="fr-FR"
                style={styles.spinner}
              />
            </View>
          </View>
        </Modal>
      )}
    </View>
  );
}

const styles = StyleSheet.create({
  label: {
    ...typography.label,
    color: colors.text,
    marginBottom: spacing.xs,
  },
  row: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: spacing.xs,
  },
  picker: {
    flex: 1,
    flexDirection: 'row',
    alignItems: 'center',
    backgroundColor: colors.white,
    borderWidth: 1.5,
    borderColor: colors.border,
    borderRadius: borderRadius.md,
    paddingHorizontal: spacing.md,
    height: 50,
    gap: spacing.sm,
  },
  pickerText: { flex: 1, ...typography.body, color: colors.text },
  placeholder: { color: colors.placeholder },
  clearBtn: {
    padding: spacing.xs,
  },
  // iOS modal
  overlay: {
    flex: 1,
    justifyContent: 'flex-end',
  },
  backdrop: {
    ...StyleSheet.absoluteFillObject,
    backgroundColor: 'rgba(0,0,0,0.35)',
  },
  sheet: {
    backgroundColor: colors.white,
    borderTopLeftRadius: 20,
    borderTopRightRadius: 20,
    paddingBottom: spacing.xl,
  },
  sheetHeader: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
    paddingHorizontal: spacing.lg,
    paddingVertical: spacing.md,
    borderBottomWidth: 1,
    borderBottomColor: colors.border,
  },
  sheetTitle: {
    ...typography.bodySmall,
    fontWeight: '600',
    color: colors.text,
  },
  btnCancel: {
    ...typography.body,
    color: colors.textSecondary,
  },
  btnDone: {
    ...typography.body,
    color: colors.primary,
    fontWeight: '600',
  },
  spinner: {
    alignSelf: 'stretch',
  },
});
