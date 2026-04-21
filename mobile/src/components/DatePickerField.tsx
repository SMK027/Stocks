import React, { useState } from 'react';
import {
  View,
  Text,
  StyleSheet,
  TouchableOpacity,
  Platform,
} from 'react-native';
import DateTimePicker, {
  DateTimePickerAndroid,
  DateTimePickerEvent,
} from '@react-native-community/datetimepicker';
import { Ionicons } from '@expo/vector-icons';
import { colors, spacing, typography, borderRadius, shadows } from '../theme';

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
  const [expanded, setExpanded] = useState(false);

  const currentDate = value ? parseDate(value) : new Date();

  const displayLabel = value
    ? parseDate(value).toLocaleDateString('fr-FR', {
        day: '2-digit',
        month: 'long',
        year: 'numeric',
      })
    : null;

  // ── Android : API impérative, aucun composant dans le tree ───────────────
  const openAndroid = () => {
    DateTimePickerAndroid.open({
      value: currentDate,
      mode: 'date',
      display: 'default',
      minimumDate: minDate,
      maximumDate: maxDate,
      onChange: (event, date) => {
        if (event.type === 'set' && date) onChange(toIso(date));
      },
    });
  };

  // ── iOS : spinner inline ──────────────────────────────────────────────────
  const handleIOS = (_event: DateTimePickerEvent, date?: Date) => {
    if (date) onChange(toIso(date));
  };

  const handlePress = () => {
    if (Platform.OS === 'android') {
      openAndroid();
    } else {
      setExpanded((v) => !v);
    }
  };

  return (
    <View>
      <Text style={styles.label}>{label}</Text>

      <View style={styles.row}>
        <TouchableOpacity
          style={[styles.picker, expanded && styles.pickerActive]}
          onPress={handlePress}
          activeOpacity={0.75}
        >
          <Ionicons
            name={icon}
            size={18}
            color={expanded ? colors.primary : colors.textSecondary}
          />
          <Text style={[styles.pickerText, !displayLabel && styles.placeholder]}>
            {displayLabel ?? placeholder}
          </Text>
          <Ionicons
            name={expanded ? 'chevron-up' : 'chevron-down'}
            size={16}
            color={expanded ? colors.primary : colors.textMuted}
          />
        </TouchableOpacity>

        {optional && value ? (
          <TouchableOpacity
            style={styles.clearBtn}
            onPress={() => { onChange(''); setExpanded(false); }}
            hitSlop={{ top: 8, bottom: 8, left: 8, right: 8 }}
          >
            <Ionicons name="close-circle" size={22} color={colors.textMuted} />
          </TouchableOpacity>
        ) : null}
      </View>

      {/* ── iOS : spinner affiché en inline ─────────────────────────────── */}
      {Platform.OS === 'ios' && expanded && (
        <View style={styles.inlineSpinner}>
          <DateTimePicker
            value={currentDate}
            mode="date"
            display="spinner"
            onChange={handleIOS}
            minimumDate={minDate}
            maximumDate={maxDate}
            locale="fr-FR"
            style={styles.spinner}
          />
          <TouchableOpacity style={styles.doneBtn} onPress={() => setExpanded(false)}>
            <Text style={styles.doneBtnText}>Valider</Text>
          </TouchableOpacity>
        </View>
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
  pickerActive: {
    borderColor: colors.primary,
    borderBottomLeftRadius: 0,
    borderBottomRightRadius: 0,
  },
  pickerText: { flex: 1, ...typography.body, color: colors.text },
  placeholder: { color: colors.placeholder },
  clearBtn: {
    padding: spacing.xs,
  },
  inlineSpinner: {
    backgroundColor: colors.white,
    borderWidth: 1.5,
    borderTopWidth: 0,
    borderColor: colors.primary,
    borderBottomLeftRadius: borderRadius.md,
    borderBottomRightRadius: borderRadius.md,
    overflow: 'hidden',
    ...shadows.sm,
  },
  spinner: {
    width: '100%',
  },
  doneBtn: {
    alignItems: 'center',
    paddingVertical: spacing.sm,
    borderTopWidth: 1,
    borderTopColor: colors.border,
    marginHorizontal: spacing.md,
  },
  doneBtnText: {
    ...typography.body,
    color: colors.primary,
    fontWeight: '600',
  },
});

