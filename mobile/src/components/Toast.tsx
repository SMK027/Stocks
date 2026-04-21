import React, {
  createContext,
  useCallback,
  useContext,
  useRef,
  useState,
} from 'react';
import {
  Animated,
  StyleSheet,
  Text,
  TouchableOpacity,
  View,
} from 'react-native';
import { Ionicons } from '@expo/vector-icons';
import { colors, spacing, borderRadius, typography, shadows } from '../theme';

// ─── Types ───────────────────────────────────────────────────────────────────

type ToastVariant = 'success' | 'error' | 'warning' | 'info';

interface ToastOptions {
  message: string;
  variant?: ToastVariant;
  duration?: number;
}

interface ToastContextValue {
  toast: (opts: ToastOptions) => void;
  success: (message: string) => void;
  error: (message: string) => void;
  warning: (message: string) => void;
  info: (message: string) => void;
}

// ─── Context ─────────────────────────────────────────────────────────────────

const ToastContext = createContext<ToastContextValue | null>(null);

// ─── Config ──────────────────────────────────────────────────────────────────

const variantConfig: Record<
  ToastVariant,
  { bg: string; iconBg: string; text: string; icon: keyof typeof Ionicons.glyphMap }
> = {
  success: {
    bg: colors.secondary,
    iconBg: 'rgba(255,255,255,0.25)',
    text: colors.white,
    icon: 'checkmark-circle',
  },
  error: {
    bg: colors.danger,
    iconBg: 'rgba(255,255,255,0.25)',
    text: colors.white,
    icon: 'close-circle',
  },
  warning: {
    bg: colors.warning,
    iconBg: 'rgba(255,255,255,0.25)',
    text: colors.white,
    icon: 'warning',
  },
  info: {
    bg: colors.info,
    iconBg: 'rgba(255,255,255,0.25)',
    text: colors.white,
    icon: 'information-circle',
  },
};

// ─── Provider ────────────────────────────────────────────────────────────────

interface ToastState {
  message: string;
  variant: ToastVariant;
  id: number;
}

export function ToastProvider({ children }: { children: React.ReactNode }) {
  const [current, setCurrent] = useState<ToastState | null>(null);
  const opacity = useRef(new Animated.Value(0)).current;
  const translateY = useRef(new Animated.Value(-20)).current;
  const timerRef = useRef<ReturnType<typeof setTimeout> | null>(null);
  const idRef = useRef(0);

  const hide = useCallback(() => {
    Animated.parallel([
      Animated.timing(opacity, { toValue: 0, duration: 250, useNativeDriver: true }),
      Animated.timing(translateY, { toValue: -20, duration: 250, useNativeDriver: true }),
    ]).start(() => setCurrent(null));
  }, [opacity, translateY]);

  const toast = useCallback(
    ({ message, variant = 'success', duration = 3000 }: ToastOptions) => {
      // Annule le timer précédent
      if (timerRef.current) clearTimeout(timerRef.current);

      idRef.current += 1;
      setCurrent({ message, variant, id: idRef.current });

      // Reset et lance l'animation d'entrée
      opacity.setValue(0);
      translateY.setValue(-20);
      Animated.parallel([
        Animated.spring(opacity, { toValue: 1, useNativeDriver: true }),
        Animated.spring(translateY, { toValue: 0, useNativeDriver: true, tension: 80, friction: 9 }),
      ]).start();

      timerRef.current = setTimeout(hide, duration);
    },
    [opacity, translateY, hide],
  );

  const success = useCallback((message: string) => toast({ message, variant: 'success' }), [toast]);
  const error   = useCallback((message: string) => toast({ message, variant: 'error' }), [toast]);
  const warning = useCallback((message: string) => toast({ message, variant: 'warning' }), [toast]);
  const info    = useCallback((message: string) => toast({ message, variant: 'info' }), [toast]);

  return (
    <ToastContext.Provider value={{ toast, success, error, warning, info }}>
      {children}
      {current && (
        <Animated.View
          style={[
            styles.container,
            { backgroundColor: variantConfig[current.variant].bg },
            { opacity, transform: [{ translateY }] },
          ]}
          pointerEvents="box-none"
        >
          <View style={[styles.iconWrap, { backgroundColor: variantConfig[current.variant].iconBg }]}>
            <Ionicons
              name={variantConfig[current.variant].icon}
              size={20}
              color={variantConfig[current.variant].text}
            />
          </View>
          <Text style={[styles.message, { color: variantConfig[current.variant].text }]} numberOfLines={3}>
            {current.message}
          </Text>
          <TouchableOpacity onPress={hide} hitSlop={{ top: 8, bottom: 8, left: 8, right: 8 }}>
            <Ionicons name="close" size={18} color={variantConfig[current.variant].text} />
          </TouchableOpacity>
        </Animated.View>
      )}
    </ToastContext.Provider>
  );
}

// ─── Hook ─────────────────────────────────────────────────────────────────────

export function useToast(): ToastContextValue {
  const ctx = useContext(ToastContext);
  if (!ctx) throw new Error('useToast must be used inside <ToastProvider>');
  return ctx;
}

// ─── Styles ──────────────────────────────────────────────────────────────────

const styles = StyleSheet.create({
  container: {
    position: 'absolute',
    top: 56,
    left: spacing.md,
    right: spacing.md,
    zIndex: 9999,
    flexDirection: 'row',
    alignItems: 'center',
    gap: spacing.sm,
    borderRadius: borderRadius.xl,
    paddingVertical: spacing.sm + 4,
    paddingHorizontal: spacing.md,
    ...shadows.md,
  },
  iconWrap: {
    width: 32,
    height: 32,
    borderRadius: 16,
    justifyContent: 'center',
    alignItems: 'center',
    flexShrink: 0,
  },
  message: {
    flex: 1,
    ...typography.bodySmall,
    fontWeight: '500',
  },
});
