import React, { useState } from 'react';
import {
  View,
  Text,
  StyleSheet,
  KeyboardAvoidingView,
  Platform,
  ScrollView,
  Alert,
} from 'react-native';
import { SafeAreaView } from 'react-native-safe-area-context';
import { useMutation } from '@tanstack/react-query';
import { useAuthStore } from '../../store/authStore';
import { login } from '../../api/auth';
import { colors, spacing, typography, borderRadius } from '../../theme';
import Input from '../../components/Input';
import Button from '../../components/Button';

export default function LoginScreen() {
  const [username, setUsername] = useState('');
  const [password, setPassword] = useState('');
  const { setAuth } = useAuthStore();

  const loginMutation = useMutation({
    mutationFn: () => login(username.trim(), password),
    onSuccess: async (data) => {
      await setAuth(data.token, data.user);
    },
    onError: (err: any) => {
      const message =
        err?.response?.data?.message ?? 'Identifiants incorrects. Veuillez réessayer.';
      Alert.alert('Erreur de connexion', message);
    },
  });

  const canSubmit = username.trim().length > 0 && password.length > 0;

  return (
    <SafeAreaView style={styles.safe}>
      <KeyboardAvoidingView
        style={{ flex: 1 }}
        behavior={Platform.OS === 'ios' ? 'padding' : 'height'}
      >
        <ScrollView
          contentContainerStyle={styles.container}
          keyboardShouldPersistTaps="handled"
          showsVerticalScrollIndicator={false}
        >
          {/* Logo */}
          <View style={styles.logoWrap}>
            <View style={styles.logoCircle}>
              <Text style={styles.logoLetter}>S</Text>
            </View>
            <Text style={styles.appName}>Gestion de Stocks</Text>
            <Text style={styles.tagline}>Connectez-vous à votre compte</Text>
          </View>

          {/* Formulaire */}
          <View style={styles.card}>
            <Input
              label="Nom d'utilisateur"
              value={username}
              onChangeText={setUsername}
              autoCapitalize="none"
              autoCorrect={false}
              placeholder="Votre identifiant"
              leftIcon="person-outline"
              returnKeyType="next"
            />
            <Input
              label="Mot de passe"
              value={password}
              onChangeText={setPassword}
              secureTextEntry
              placeholder="Votre mot de passe"
              leftIcon="lock-closed-outline"
              returnKeyType="done"
              onSubmitEditing={() => canSubmit && loginMutation.mutate()}
            />
            <Button
              title="Se connecter"
              onPress={() => loginMutation.mutate()}
              loading={loginMutation.isPending}
              disabled={!canSubmit}
              fullWidth
              icon="log-in-outline"
              style={styles.submitBtn}
            />
          </View>

          <Text style={styles.hint}>
            Pas encore de compte ? Créez-en un sur le site web.
          </Text>
        </ScrollView>
      </KeyboardAvoidingView>
    </SafeAreaView>
  );
}

const styles = StyleSheet.create({
  safe: { flex: 1, backgroundColor: colors.background },
  container: { flexGrow: 1, justifyContent: 'center', padding: spacing.lg, gap: spacing.lg },
  logoWrap: { alignItems: 'center', gap: spacing.sm },
  logoCircle: {
    width: 80,
    height: 80,
    borderRadius: 40,
    backgroundColor: colors.primary,
    justifyContent: 'center',
    alignItems: 'center',
  },
  logoLetter: { fontSize: 36, fontWeight: '800', color: colors.white },
  appName: { ...typography.h2, color: colors.text },
  tagline: { ...typography.body, color: colors.textSecondary },
  card: {
    backgroundColor: colors.card,
    borderRadius: borderRadius.xl,
    padding: spacing.lg,
    gap: spacing.md,
    shadowColor: '#000',
    shadowOffset: { width: 0, height: 2 },
    shadowOpacity: 0.08,
    shadowRadius: 12,
    elevation: 4,
  },
  submitBtn: { marginTop: spacing.xs },
  hint: {
    ...typography.bodySmall,
    color: colors.textMuted,
    textAlign: 'center',
  },
});
