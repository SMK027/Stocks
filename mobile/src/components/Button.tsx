import React from 'react';
import {
  TouchableOpacity,
  Text,
  StyleSheet,
  ActivityIndicator,
  ViewStyle,
  View,
} from 'react-native';
import { Ionicons } from '@expo/vector-icons';
import { colors, spacing, typography, borderRadius } from '../theme';

type Variant = 'primary' | 'secondary' | 'danger' | 'outline' | 'ghost';
type Size = 'sm' | 'md' | 'lg';

interface ButtonProps {
  title: string;
  onPress: () => void;
  variant?: Variant;
  size?: Size;
  loading?: boolean;
  disabled?: boolean;
  icon?: keyof typeof Ionicons.glyphMap;
  style?: ViewStyle;
  fullWidth?: boolean;
}

const bgMap: Record<Variant, string> = {
  primary: colors.primary,
  secondary: colors.secondary,
  danger: colors.danger,
  outline: 'transparent',
  ghost: 'transparent',
};
const textColorMap: Record<Variant, string> = {
  primary: colors.white,
  secondary: colors.white,
  danger: colors.white,
  outline: colors.primary,
  ghost: colors.textSecondary,
};
const borderMap: Record<Variant, string> = {
  primary: colors.primary,
  secondary: colors.secondary,
  danger: colors.danger,
  outline: colors.primary,
  ghost: 'transparent',
};

export default function Button({
  title,
  onPress,
  variant = 'primary',
  size = 'md',
  loading,
  disabled,
  icon,
  style,
  fullWidth,
}: ButtonProps) {
  const paddingV = { sm: spacing.xs + 2, md: spacing.sm + 4, lg: spacing.md }[size];
  const fontSize = { sm: 13, md: 15, lg: 17 }[size];

  return (
    <TouchableOpacity
      onPress={onPress}
      disabled={disabled || loading}
      activeOpacity={0.75}
      style={[
        styles.base,
        {
          backgroundColor: bgMap[variant],
          borderColor: borderMap[variant],
          paddingVertical: paddingV,
        },
        fullWidth && styles.fullWidth,
        (disabled || loading) && styles.disabled,
        style,
      ]}
    >
      {loading ? (
        <ActivityIndicator size="small" color={textColorMap[variant]} />
      ) : (
        <View style={styles.content}>
          {icon && (
            <Ionicons
              name={icon}
              size={fontSize + 2}
              color={textColorMap[variant]}
              style={styles.icon}
            />
          )}
          <Text style={[styles.text, { color: textColorMap[variant], fontSize }]}>{title}</Text>
        </View>
      )}
    </TouchableOpacity>
  );
}

const styles = StyleSheet.create({
  base: {
    flexDirection: 'row',
    justifyContent: 'center',
    alignItems: 'center',
    borderRadius: borderRadius.md,
    borderWidth: 1.5,
    paddingHorizontal: spacing.lg,
  },
  fullWidth: { alignSelf: 'stretch' },
  content: { flexDirection: 'row', alignItems: 'center' },
  text: { ...typography.label, textAlign: 'center' },
  icon: { marginRight: spacing.xs },
  disabled: { opacity: 0.5 },
});
