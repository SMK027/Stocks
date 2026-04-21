import React, { useRef, useState } from 'react';
import {
  View,
  Text,
  TextInput,
  StyleSheet,
  TouchableOpacity,
  ScrollView,
} from 'react-native';
import { Ionicons } from '@expo/vector-icons';
import { colors, spacing, typography, borderRadius, shadows } from '../theme';

export interface AutocompleteItem {
  id: number;
  name: string;
}

interface Props {
  label: string;
  items: AutocompleteItem[];
  value: number | null;
  onChange: (id: number) => void;
  placeholder?: string;
  icon?: keyof typeof Ionicons.glyphMap;
  emptyMessage?: string;
}

const MAX_VISIBLE = 5;
const ITEM_HEIGHT = 44;

export default function AutocompleteField({
  label,
  items,
  value,
  onChange,
  placeholder = 'Rechercher…',
  icon = 'search-outline',
  emptyMessage = 'Aucun résultat',
}: Props) {
  const [query, setQuery] = useState('');
  const [open, setOpen] = useState(false);
  const inputRef = useRef<TextInput>(null);

  const selected = items.find((i) => i.id === value) ?? null;

  const filtered = query.trim()
    ? items.filter((i) => i.name.toLowerCase().includes(query.toLowerCase()))
    : items;

  const handleSelect = (item: AutocompleteItem) => {
    onChange(item.id);
    setQuery('');
    setOpen(false);
    inputRef.current?.blur();
  };

  const handleClear = () => {
    setQuery('');
    setOpen(true);
    setTimeout(() => inputRef.current?.focus(), 50);
  };

  const handleFocus = () => {
    setOpen(true);
    setQuery('');
  };

  const handleBlur = () => {
    // petit délai pour laisser onPress des items se déclencher avant fermeture
    setTimeout(() => setOpen(false), 150);
  };

  const listHeight = Math.min(filtered.length, MAX_VISIBLE) * ITEM_HEIGHT;

  return (
    <View>
      <Text style={styles.label}>{label}</Text>

      {/* ── Champ de saisie ─────────────────────────────────────────────── */}
      <View style={[styles.inputWrap, open && styles.inputWrapOpen]}>
        <Ionicons
          name={open ? 'search-outline' : icon}
          size={18}
          color={open ? colors.primary : colors.textSecondary}
        />

        {open ? (
          /* Mode recherche */
          <TextInput
            ref={inputRef}
            style={styles.input}
            value={query}
            onChangeText={setQuery}
            placeholder={placeholder}
            placeholderTextColor={colors.placeholder}
            autoCorrect={false}
            autoCapitalize="none"
            onFocus={handleFocus}
            onBlur={handleBlur}
            autoFocus
          />
        ) : (
          /* Mode affichage de la valeur sélectionnée */
          <TouchableOpacity style={styles.inputTouchable} onPress={handleFocus} activeOpacity={0.75}>
            <Text style={[styles.input, !selected && styles.placeholder]} numberOfLines={1}>
              {selected ? selected.name : placeholder}
            </Text>
          </TouchableOpacity>
        )}

        {selected && !open ? (
          <TouchableOpacity onPress={handleClear} hitSlop={{ top: 8, bottom: 8, left: 8, right: 8 }}>
            <Ionicons name="close-circle" size={18} color={colors.textMuted} />
          </TouchableOpacity>
        ) : (
          <Ionicons
            name={open ? 'chevron-up' : 'chevron-down'}
            size={16}
            color={open ? colors.primary : colors.textMuted}
          />
        )}
      </View>

      {/* ── Liste déroulante ────────────────────────────────────────────── */}
      {open && (
        <View style={[styles.dropdown, { height: filtered.length === 0 ? 44 : listHeight }]}>
          {filtered.length === 0 ? (
            <View style={styles.emptyRow}>
              <Text style={styles.emptyText}>{emptyMessage}</Text>
            </View>
          ) : (
            <ScrollView
              keyboardShouldPersistTaps="handled"
              nestedScrollEnabled
              showsVerticalScrollIndicator={filtered.length > MAX_VISIBLE}
            >
              {filtered.map((item, index) => (
                <TouchableOpacity
                  key={String(item.id)}
                  style={[
                    styles.item,
                    item.id === value && styles.itemSelected,
                    index < filtered.length - 1 && styles.itemBorder,
                  ]}
                  onPress={() => handleSelect(item)}
                  activeOpacity={0.7}
                >
                  {item.id === value && (
                    <Ionicons name="checkmark" size={15} color={colors.primary} style={{ marginRight: 6 }} />
                  )}
                  <Text
                    style={[styles.itemText, item.id === value && styles.itemTextSelected]}
                    numberOfLines={1}
                  >
                    {item.name}
                  </Text>
                </TouchableOpacity>
              ))}
            </ScrollView>
          )}
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
  inputWrap: {
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
  inputWrapOpen: {
    borderColor: colors.primary,
    borderBottomLeftRadius: 0,
    borderBottomRightRadius: 0,
  },
  inputTouchable: {
    flex: 1,
    justifyContent: 'center',
  },
  input: {
    flex: 1,
    ...typography.body,
    color: colors.text,
    paddingVertical: 0,
  },
  placeholder: {
    color: colors.placeholder,
  },
  dropdown: {
    backgroundColor: colors.white,
    borderWidth: 1.5,
    borderTopWidth: 0,
    borderColor: colors.primary,
    borderBottomLeftRadius: borderRadius.md,
    borderBottomRightRadius: borderRadius.md,
    overflow: 'hidden',
    ...shadows.sm,
  },
  emptyRow: {
    height: 44,
    justifyContent: 'center',
    paddingHorizontal: spacing.md,
  },
  emptyText: {
    ...typography.bodySmall,
    color: colors.textMuted,
    fontStyle: 'italic',
  },
  item: {
    flexDirection: 'row',
    alignItems: 'center',
    height: ITEM_HEIGHT,
    paddingHorizontal: spacing.md,
  },
  itemBorder: {
    borderBottomWidth: 1,
    borderBottomColor: colors.border,
  },
  itemSelected: {
    backgroundColor: colors.primaryLight,
  },
  itemText: {
    flex: 1,
    ...typography.body,
    color: colors.text,
  },
  itemTextSelected: {
    color: colors.primary,
    fontWeight: '600',
  },
});
