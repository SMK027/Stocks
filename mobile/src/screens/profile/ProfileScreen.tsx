import React, { useState, useEffect } from 'react';
import {
  View,
  Text,
  StyleSheet,
  ScrollView,
  Alert,
  KeyboardAvoidingView,
  Platform,
  Switch,
} from 'react-native';
import { SafeAreaView } from 'react-native-safe-area-context';
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import { getMe, updateMe, changePassword } from '../../api/auth';
import { useAuthStore } from '../../store/authStore';
import { colors, spacing, typography, borderRadius, shadows } from '../../theme';
import Input from '../../components/Input';
import Button from '../../components/Button';
import Card from '../../components/Card';
import LoadingView from '../../components/LoadingView';

export default function ProfileScreen() {
  const { user, setUser, logout } = useAuthStore();
  const qc = useQueryClient();

  // Onglet actif : 'info' | 'password'
  const [tab, setTab] = useState<'info' | 'password'>('info');

  // Champs profil
  const [email, setEmail] = useState(user?.email ?? '');
  const [firstName, setFirstName] = useState(user?.firstname ?? '');
  const [lastName, setLastName] = useState(user?.lastname ?? '');
  const [dailyDigest, setDailyDigest] = useState(user?.daily_digest ?? false);

  // Champs mot de passe
  const [currentPwd, setCurrentPwd] = useState('');
  const [newPwd, setNewPwd] = useState('');
  const [confirmPwd, setConfirmPwd] = useState('');

  const { data: fresh } = useQuery({
    queryKey: ['me'],
    queryFn: getMe,
  });

  useEffect(() => {
    if (fresh) {
      setEmail(fresh.email ?? '');
      setFirstName(fresh.firstname ?? '');
      setLastName(fresh.lastname ?? '');
      setDailyDigest(fresh.daily_digest ?? false);
      setUser(fresh);
    }
  }, [fresh]);

  const updateMutation = useMutation({
    mutationFn: () =>
      updateMe({
        email: email.trim(),
        firstname: firstName.trim() || undefined,
        lastname: lastName.trim() || undefined,
        daily_digest: dailyDigest,
      }),
    onSuccess: (updated) => {
      setUser(updated);
      qc.invalidateQueries({ queryKey: ['me'] });
      Alert.alert('Succès', 'Profil mis à jour.');
    },
    onError: (err: any) =>
      Alert.alert('Erreur', err?.response?.data?.message ?? 'Impossible de mettre à jour le profil.'),
  });

  const passwordMutation = useMutation({
    mutationFn: () => changePassword(currentPwd, newPwd),
    onSuccess: () => {
      Alert.alert('Succès', 'Mot de passe modifié.');
      setCurrentPwd('');
      setNewPwd('');
      setConfirmPwd('');
    },
    onError: (err: any) =>
      Alert.alert('Erreur', err?.response?.data?.message ?? 'Impossible de changer le mot de passe.'),
  });

  const handleUpdateProfile = () => {
    if (!email.trim()) {
      Alert.alert('Requis', "L'adresse e-mail est obligatoire.");
      return;
    }
    updateMutation.mutate();
  };

  const handleChangePassword = () => {
    if (!currentPwd || !newPwd || !confirmPwd) {
      Alert.alert('Requis', 'Tous les champs sont obligatoires.');
      return;
    }
    if (newPwd !== confirmPwd) {
      Alert.alert('Erreur', 'Les nouveaux mots de passe ne correspondent pas.');
      return;
    }
    if (newPwd.length < 8) {
      Alert.alert('Erreur', 'Le mot de passe doit contenir au moins 8 caractères.');
      return;
    }
    passwordMutation.mutate();
  };

  const confirmLogout = () =>
    Alert.alert('Déconnexion', 'Voulez-vous vous déconnecter ?', [
      { text: 'Annuler', style: 'cancel' },
      { text: 'Déconnexion', style: 'destructive', onPress: logout },
    ]);

  return (
    <SafeAreaView style={styles.safe} edges={['bottom', 'left', 'right']}>
      <KeyboardAvoidingView
        style={{ flex: 1 }}
        behavior={Platform.OS === 'ios' ? 'padding' : 'height'}
      >
        <ScrollView
          contentContainerStyle={styles.content}
          keyboardShouldPersistTaps="handled"
          showsVerticalScrollIndicator={false}
        >
          {/* Avatar */}
          <View style={styles.avatarSection}>
            <View style={styles.avatar}>
              <Text style={styles.avatarText}>
                {user?.username?.charAt(0).toUpperCase() ?? '?'}
              </Text>
            </View>
            <Text style={styles.username}>{user?.username}</Text>
            <Text style={styles.role}>{user?.global_role === 'admin' ? '🛡 Administrateur' : '👤 Utilisateur'}</Text>
          </View>

          {/* Sélecteur d'onglets */}
          <View style={styles.tabRow}>
            <Button
              title="Informations"
              variant={tab === 'info' ? 'primary' : 'outline'}
              size="sm"
              onPress={() => setTab('info')}
              style={{ flex: 1 }}
            />
            <Button
              title="Mot de passe"
              variant={tab === 'password' ? 'primary' : 'outline'}
              size="sm"
              onPress={() => setTab('password')}
              style={{ flex: 1 }}
            />
          </View>

          {tab === 'info' ? (
            <Card style={styles.formCard}>
              <Input label="Adresse e-mail *" value={email} onChangeText={setEmail} keyboardType="email-address" autoCapitalize="none" leftIcon="mail-outline" />
              <Input label="Prénom" value={firstName} onChangeText={setFirstName} placeholder="Optionnel" leftIcon="person-outline" />
              <Input label="Nom" value={lastName} onChangeText={setLastName} placeholder="Optionnel" leftIcon="person-outline" />
              <View style={styles.switchRow}>
                <View>
                  <Text style={styles.switchLabel}>Résumé quotidien</Text>
                  <Text style={styles.switchHint}>Recevoir un e-mail quotidien</Text>
                </View>
                <Switch
                  value={dailyDigest}
                  onValueChange={setDailyDigest}
                  trackColor={{ false: colors.border, true: colors.primaryLight }}
                  thumbColor={dailyDigest ? colors.primary : colors.textMuted}
                />
              </View>
              <Button
                title="Mettre à jour"
                onPress={handleUpdateProfile}
                loading={updateMutation.isPending}
                fullWidth
                icon="checkmark-outline"
              />
            </Card>
          ) : (
            <Card style={styles.formCard}>
              <Input label="Mot de passe actuel *" value={currentPwd} onChangeText={setCurrentPwd} secureTextEntry leftIcon="lock-closed-outline" />
              <Input label="Nouveau mot de passe *" value={newPwd} onChangeText={setNewPwd} secureTextEntry leftIcon="lock-open-outline" />
              <Input label="Confirmer le nouveau *" value={confirmPwd} onChangeText={setConfirmPwd} secureTextEntry leftIcon="lock-open-outline" />
              <Button
                title="Changer le mot de passe"
                onPress={handleChangePassword}
                loading={passwordMutation.isPending}
                fullWidth
                icon="key-outline"
              />
            </Card>
          )}

          {/* Déconnexion */}
          <Button
            title="Se déconnecter"
            variant="danger"
            onPress={confirmLogout}
            fullWidth
            icon="log-out-outline"
          />
        </ScrollView>
      </KeyboardAvoidingView>
    </SafeAreaView>
  );
}

const styles = StyleSheet.create({
  safe: { flex: 1, backgroundColor: colors.background },
  content: { padding: spacing.lg, gap: spacing.md, paddingBottom: spacing.xl },

  avatarSection: { alignItems: 'center', gap: spacing.xs, paddingVertical: spacing.md },
  avatar: {
    width: 80,
    height: 80,
    borderRadius: 40,
    backgroundColor: colors.primaryLight,
    justifyContent: 'center',
    alignItems: 'center',
  },
  avatarText: { fontSize: 34, fontWeight: '800', color: colors.primary },
  username: { ...typography.h2, color: colors.text },
  role: { ...typography.bodySmall, color: colors.textSecondary },

  tabRow: { flexDirection: 'row', gap: spacing.sm },

  formCard: { gap: spacing.md },

  switchRow: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
    paddingVertical: spacing.sm,
    borderTopWidth: 1,
    borderTopColor: colors.border,
  },
  switchLabel: { ...typography.label, color: colors.text },
  switchHint: { ...typography.caption, color: colors.textSecondary, marginTop: 2 },
});
