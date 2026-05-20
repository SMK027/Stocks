import React, { useState } from 'react';
import {
  View,
  Text,
  StyleSheet,
  TouchableOpacity,
  Modal,
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
  // iOS : modal indépendant pour éviter les conflits de rendu imbriqué
  const [iosModalVisible, setIosModalVisible] = useState(false);
  const [iosTempDate, setIosTempDate] = useState<Date>(new Date());

  const currentDate = value ? parseDate(value) : new Date();

  const displayLabel = value
    ? parseDate(value).toLocaleDateString('fr-FR', {
        day: '2-digit',
        month: 'long',
        year: 'numeric',
      })
    : null;

  // ── Android : API impérative ──────────────────────────────────────────────
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

  // ── iOS : Modal overlay indépendant ──────────────────────────────────────
  const openIos = () => {
    setIosTempDate(currentDate);
    setIosModalVisible(true);
  };

  const handleIosChange = (_event: DateTimePickerEvent, date?: Date) => {
    if (date) setIosTempDate(date);
  };

  const confirmIos = () => {
    onChange(toIso(iosTempDate));
    setIosModalVisible(false);
  };

  const cancelIos = () => {
    setIosModalVisible(false);
  };

  const handlePress = () => {
    if (Platform.OS === 'android') {
      openAndroid();
    } else {
      openIos();
    }
  };

  return (
    <View>
      <Text style={styles.label}>{label}</Text>

      <View style={styles.row}>
        <TouchableOpacity
          style={styles.picker}
          onPress={handlePress}
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

      {/* ── iOS : picker dans son propre Modal pour éviter les imbrications ── */}
      {Platform.OS === 'ios' && (
        <Modal
          visible={iosModalVisible}
          transparent
          animationType="slide"
          onRequestClose={cancelIos}
        >
          <TouchableOpacity
            style={styles.iosOverlay}
            activeOpacity={1}
            onPress={cancelIos}
          >
            <TouchableOpacity activeOpacity={1} style={styles.iosSheet}>
              <View style={styles.iosHandle} />
              <View style={styles.iosHeader}>
                <TouchableOpacity onPress={cancelIos} hitSlop={{ top: 8, bottom: 8, left: 8, right: 8 }}>
                  <Text style={styles.iosCancelText}>Annuler</Text>
                </TouchableOpacity>
                <Text style={styles.iosSheetTitle}>{label}</Text>
                <TouchableOpacity onPress={confirmIos} hitSlop={{ top: 8, bottom: 8, left: 8, right: 8 }}>
                  <Text style={styles.iosConfirmText}>Valider</Text>
                </TouchableOpacity>
              </View>
              <DateTimePicker
                value={iosTempDate}
                mode="date"
                display="spinner"
                onChange={handleIosChange}
                minimumDate={minDate}
                maximumDate={maxDate}
                locale="fr-FR"
                style={styles.iosSpinner}
              />
            </TouchableOpacity>
          </TouchableOpacity>
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

  // ── iOS Modal ─────────────────────────────────────────────────────────────
  iosOverlay: {
    flex: 1,
    backgroundColor: 'rgba(0,0,0,0.45)',
    justifyContent: 'flex-end',
  },
  iosSheet: {
    backgroundColor: colors.white,
    borderTopLeftRadius: 20,
    borderTopRightRadius: 20,
    paddingBottom: spacing.xl,
    ...shadows.sm,
  },
  iosHandle: {
    width: 40,
    height: 4,
    borderRadius: 2,
    backgroundColor: colors.border,
    alignSelf: 'center',
    marginTop: spacing.sm,
    marginBottom: spacing.xs,
  },
  iosHeader: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
    paddingHorizontal: spacing.lg,
    paddingVertical: spacing.sm,
    borderBottomWidth: 1,
    borderBottomColor: colors.border,
  },
  iosSheetTitle: {
    ...typography.body,
    fontWeight: '600',
    color: colors.text,
  },
  iosCancelText: {
    ...typography.body,
    color: colors.textSecondary,
  },
  iosConfirmText: {
    ...typography.body,
    color: colors.primary,
    fontWeight: '600',
  },
  iosSpinner: {
    width: '100%',
  },
});

