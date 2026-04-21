import React from 'react';
import { View, Text, StyleSheet, ViewStyle } from 'react-native';
import { colors, spacing, borderRadius, typography } from '../theme';

type BadgeVariant = 'primary' | 'secondary' | 'danger' | 'warning' | 'info' | 'purple' | 'neutral';

interface BadgeProps {
  label: string;
  variant?: BadgeVariant;
  style?: ViewStyle;
}

const variants: Record<BadgeVariant, { bg: string; text: string }> = {
  primary:   { bg: colors.primaryLight,  text: colors.primary },
  secondary: { bg: colors.secondaryLight, text: colors.secondary },
  danger:    { bg: colors.dangerLight,   text: colors.danger },
  warning:   { bg: colors.warningLight,  text: colors.warning },
  info:      { bg: colors.infoLight,     text: colors.info },
  purple:    { bg: colors.purpleLight,   text: colors.purple },
  neutral:   { bg: colors.border,        text: colors.textSecondary },
};

export default function Badge({ label, variant = 'neutral', style }: BadgeProps) {
  const { bg, text } = variants[variant];
  return (
    <View style={[styles.badge, { backgroundColor: bg }, style]}>
      <Text style={[styles.text, { color: text }]}>{label}</Text>
    </View>
  );
}

const styles = StyleSheet.create({
  badge: {
    paddingHorizontal: spacing.sm,
    paddingVertical: 3,
    borderRadius: borderRadius.full,
    alignSelf: 'flex-start',
  },
  text: { ...typography.caption, fontWeight: '600' },
});
