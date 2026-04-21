import React, { useLayoutEffect, useState } from 'react';
import {
  View,
  Text,
  StyleSheet,
  FlatList,
  TouchableOpacity,
  Alert,
  RefreshControl,
  Modal,
  KeyboardAvoidingView,
  Platform,
  ScrollView,
} from 'react-native';
import { SafeAreaView } from 'react-native-safe-area-context';
import { NativeStackScreenProps } from '@react-navigation/native-stack';
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import { Ionicons } from '@expo/vector-icons';
import { SpacesStackParamList, Member } from '../../types';
import { getMembers, addMember, updateMember, removeMember } from '../../api/spaces';
import { colors, spacing, typography, borderRadius, shadows } from '../../theme';
import LoadingView from '../../components/LoadingView';
import ErrorView from '../../components/ErrorView';
import EmptyState from '../../components/EmptyState';
import Badge from '../../components/Badge';
import Input from '../../components/Input';
import Button from '../../components/Button';

type Props = NativeStackScreenProps<SpacesStackParamList, 'Members'>;

const ROLES = [
  { value: 'membre', label: 'Membre' },
  { value: 'gestionnaire_inventaires', label: 'Gest. inventaires' },
  { value: 'gestionnaire_produits', label: 'Gest. produits' },
  { value: 'gestionnaire_global', label: 'Gest. global' },
  { value: 'administrateur', label: 'Administrateur' },
];

export default function MembersScreen({ navigation, route }: Props) {
  const { spaceId } = route.params;
  const qc = useQueryClient();

  const [showAdd, setShowAdd] = useState(false);
  const [addUsername, setAddUsername] = useState('');
  const [addRole, setAddRole] = useState('membre');

  const { data: members = [], isLoading, isError, refetch, isFetching } = useQuery({
    queryKey: ['members', spaceId],
    queryFn: () => getMembers(spaceId),
  });

  const addMutation = useMutation({
    mutationFn: () => addMember(spaceId, addUsername.trim(), addRole),
    onSuccess: () => {
      qc.invalidateQueries({ queryKey: ['members', spaceId] });
      setShowAdd(false);
      setAddUsername('');
      setAddRole('membre');
    },
    onError: (err: any) =>
      Alert.alert('Erreur', err?.response?.data?.message ?? "Impossible d'ajouter ce membre."),
  });

  const removeMutation = useMutation({
    mutationFn: (userId: number) => removeMember(spaceId, userId),
    onSuccess: () => qc.invalidateQueries({ queryKey: ['members', spaceId] }),
    onError: (err: any) =>
      Alert.alert('Erreur', err?.response?.data?.message ?? 'Impossible de retirer ce membre.'),
  });

  const changeRoleMutation = useMutation({
    mutationFn: ({ userId, role }: { userId: number; role: string }) =>
      updateMember(spaceId, userId, role),
    onSuccess: () => qc.invalidateQueries({ queryKey: ['members', spaceId] }),
    onError: (err: any) =>
      Alert.alert('Erreur', err?.response?.data?.message ?? 'Impossible de modifier le rôle.'),
  });

  useLayoutEffect(() => {
    navigation.setOptions({
      headerRight: () => (
        <TouchableOpacity
          onPress={() => setShowAdd(true)}
          style={{ marginRight: spacing.md }}
          hitSlop={{ top: 10, bottom: 10, left: 10, right: 10 }}
        >
          <Ionicons name="person-add-outline" size={22} color={colors.white} />
        </TouchableOpacity>
      ),
    });
  }, [navigation]);

  const confirmRemove = (m: Member) =>
    Alert.alert('Retirer', `Retirer ${m.username} de cet espace ?`, [
      { text: 'Annuler', style: 'cancel' },
      { text: 'Retirer', style: 'destructive', onPress: () => removeMutation.mutate(m.id) },
    ]);

  const promptRoleChange = (m: Member) => {
    const others = ROLES.filter((r) => r.value !== m.role);
    Alert.alert(
      'Changer le rôle',
      `Rôle actuel : ${ROLES.find((r) => r.value === m.role)?.label ?? m.role}`,
      [
        ...others.map((r) => ({
          text: r.label,
          onPress: () => changeRoleMutation.mutate({ userId: m.id, role: r.value }),
        })),
        { text: 'Annuler', style: 'cancel' as const },
      ],
    );
  };

  if (isLoading) return <LoadingView />;
  if (isError) return <ErrorView onRetry={refetch} />;

  return (
    <SafeAreaView style={styles.safe} edges={['bottom', 'left', 'right']}>
      <FlatList
        data={members}
        keyExtractor={(item) => String(item.id)}
        contentContainerStyle={[styles.list, members.length === 0 && styles.listFlex]}
        refreshControl={
          <RefreshControl
            refreshing={isFetching && !isLoading}
            onRefresh={refetch}
            colors={[colors.primary]}
            tintColor={colors.primary}
          />
        }
        ListEmptyComponent={
          <EmptyState
            icon="people-outline"
            title="Aucun membre"
            subtitle="Ajoutez des membres avec le bouton en haut à droite."
          />
        }
        renderItem={({ item }) => (
          <View style={styles.card}>
            <View style={styles.avatar}>
              <Text style={styles.avatarText}>{item.username.charAt(0).toUpperCase()}</Text>
            </View>
            <View style={styles.info}>
              <Text style={styles.username}>{item.username}</Text>
              <Text style={styles.email} numberOfLines={1}>{item.email}</Text>
              <Badge label={ROLES.find((r) => r.value === item.role)?.label ?? item.role} variant={roleBadge(item.role)} style={{ marginTop: 4 }} />
            </View>
            <View style={styles.actions}>
              <TouchableOpacity onPress={() => promptRoleChange(item)} hitSlop={{ top: 8, bottom: 8, left: 8, right: 8 }}>
                <Ionicons name="swap-horizontal-outline" size={20} color={colors.primary} />
              </TouchableOpacity>
              <TouchableOpacity onPress={() => confirmRemove(item)} hitSlop={{ top: 8, bottom: 8, left: 8, right: 8 }}>
                <Ionicons name="person-remove-outline" size={20} color={colors.danger} />
              </TouchableOpacity>
            </View>
          </View>
        )}
      />

      {/* Modal ajout membre */}
      <Modal visible={showAdd} transparent animationType="slide">
        <View style={styles.modalOverlay}>
          <KeyboardAvoidingView behavior={Platform.OS === 'ios' ? 'padding' : undefined}>
            <View style={styles.modalCard}>
              <View style={styles.modalHeader}>
                <Text style={styles.modalTitle}>Ajouter un membre</Text>
                <TouchableOpacity onPress={() => setShowAdd(false)}>
                  <Ionicons name="close" size={24} color={colors.textSecondary} />
                </TouchableOpacity>
              </View>
              <Input
                label="Nom d'utilisateur *"
                value={addUsername}
                onChangeText={setAddUsername}
                placeholder="Identifiant exact"
                autoCapitalize="none"
                autoFocus
              />
              <Text style={styles.roleLabel}>Rôle</Text>
              <View style={styles.roleList}>
                {ROLES.map((r) => (
                  <TouchableOpacity
                    key={r.value}
                    style={[styles.roleItem, addRole === r.value && styles.roleItemActive]}
                    onPress={() => setAddRole(r.value)}
                  >
                    <Text style={[styles.roleItemText, addRole === r.value && styles.roleItemTextActive]}>{r.label}</Text>
                  </TouchableOpacity>
                ))}
              </View>
              <Button
                title="Ajouter"
                onPress={() => {
                  if (!addUsername.trim()) {
                    Alert.alert('Requis', "Le nom d'utilisateur est obligatoire.");
                    return;
                  }
                  addMutation.mutate();
                }}
                loading={addMutation.isPending}
                fullWidth
              />
            </View>
          </KeyboardAvoidingView>
        </View>
      </Modal>
    </SafeAreaView>
  );
}

function roleBadge(role: string): 'primary' | 'secondary' | 'neutral' {
  if (role === 'administrateur') return 'primary';
  if (role.startsWith('gestionnaire')) return 'secondary';
  return 'neutral';
}

const styles = StyleSheet.create({
  safe: { flex: 1, backgroundColor: colors.background },
  list: { padding: spacing.md, gap: spacing.sm },
  listFlex: { flex: 1 },
  card: {
    flexDirection: 'row',
    alignItems: 'center',
    backgroundColor: colors.card,
    borderRadius: borderRadius.lg,
    padding: spacing.md,
    gap: spacing.md,
    ...shadows.sm,
  },
  avatar: {
    width: 44,
    height: 44,
    borderRadius: 22,
    backgroundColor: colors.purpleLight,
    justifyContent: 'center',
    alignItems: 'center',
    flexShrink: 0,
  },
  avatarText: { fontSize: 18, fontWeight: '700', color: colors.purple },
  info: { flex: 1 },
  username: { ...typography.h3, color: colors.text },
  email: { ...typography.caption, color: colors.textSecondary, marginTop: 2 },
  actions: { flexDirection: 'row', gap: spacing.md, flexShrink: 0 },

  // Modal
  modalOverlay: {
    flex: 1,
    backgroundColor: 'rgba(0,0,0,0.45)',
    justifyContent: 'flex-end',
  },
  modalCard: {
    backgroundColor: colors.card,
    borderTopLeftRadius: borderRadius.xl,
    borderTopRightRadius: borderRadius.xl,
    padding: spacing.lg,
    gap: spacing.md,
    paddingBottom: spacing.xl,
  },
  modalHeader: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
    marginBottom: spacing.xs,
  },
  modalTitle: { ...typography.h3, color: colors.text },
  roleLabel: { ...typography.label, color: colors.text },
  roleList: { flexDirection: 'row', flexWrap: 'wrap', gap: spacing.xs },
  roleItem: {
    paddingHorizontal: spacing.sm,
    paddingVertical: spacing.xs,
    borderRadius: borderRadius.full,
    borderWidth: 1.5,
    borderColor: colors.border,
    backgroundColor: colors.background,
  },
  roleItemActive: {
    borderColor: colors.primary,
    backgroundColor: colors.primaryLight,
  },
  roleItemText: { ...typography.caption, color: colors.textSecondary, fontWeight: '500' },
  roleItemTextActive: { color: colors.primary, fontWeight: '600' },
});
