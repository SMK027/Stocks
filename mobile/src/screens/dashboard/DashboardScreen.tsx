import React from 'react';
import {
  View,
  Text,
  StyleSheet,
  ScrollView,
  TouchableOpacity,
  RefreshControl,
} from 'react-native';
import { SafeAreaView } from 'react-native-safe-area-context';
import { useQuery } from '@tanstack/react-query';
import { Ionicons } from '@expo/vector-icons';
import { useAuthStore } from '../../store/authStore';
import { getSpaces } from '../../api/spaces';
import { Space } from '../../types';
import { colors, spacing, typography, borderRadius, shadows } from '../../theme';
import Card from '../../components/Card';
import Badge from '../../components/Badge';
import LoadingView from '../../components/LoadingView';

export default function DashboardScreen() {
  const { user } = useAuthStore();
  const { data: spaces = [], isLoading, isFetching, refetch } = useQuery({
    queryKey: ['spaces'],
    queryFn: getSpaces,
  });

  if (isLoading) return <LoadingView />;

  const adminSpaces = spaces.filter((s) => s.user_role === 'administrateur').length;
  const totalSpaces = spaces.length;

  return (
    <SafeAreaView style={styles.safe} edges={['bottom', 'left', 'right']}>
      <ScrollView
        contentContainerStyle={styles.content}
        refreshControl={
          <RefreshControl
            refreshing={isFetching && !isLoading}
            onRefresh={refetch}
            colors={[colors.primary]}
            tintColor={colors.primary}
          />
        }
        showsVerticalScrollIndicator={false}
      >
        {/* Salutation */}
        <View style={styles.greeting}>
          <View>
            <Text style={styles.greetingTitle}>Bonjour, {user?.username} 👋</Text>
            <Text style={styles.greetingSubtitle}>Votre tableau de bord</Text>
          </View>
          <View style={styles.roleTag}>
            <Text style={styles.roleText}>
              {user?.global_role === 'admin' ? '🛡 Admin' : '👤 Utilisateur'}
            </Text>
          </View>
        </View>

        {/* Stats */}
        <View style={styles.statsRow}>
          <StatTile
            icon="layers"
            label="Espaces"
            value={totalSpaces}
            color={colors.primary}
            bg={colors.primaryLight}
          />
          <StatTile
            icon="shield-checkmark"
            label="Admin"
            value={adminSpaces}
            color={colors.secondary}
            bg={colors.secondaryLight}
          />
        </View>

        {/* Liste des espaces */}
        <Text style={styles.sectionTitle}>Mes espaces</Text>

        {spaces.length === 0 ? (
          <Card style={styles.emptyCard}>
            <Ionicons name="layers-outline" size={32} color={colors.textMuted} />
            <Text style={styles.emptyText}>
              Aucun espace disponible.{'\n'}Rendez-vous dans l'onglet Espaces pour en créer un.
            </Text>
          </Card>
        ) : (
          spaces.map((space) => <SpaceRow key={space.id} space={space} />)
        )}
      </ScrollView>
    </SafeAreaView>
  );
}

function StatTile({
  icon,
  label,
  value,
  color,
  bg,
}: {
  icon: keyof typeof Ionicons.glyphMap;
  label: string;
  value: number | string;
  color: string;
  bg: string;
}) {
  return (
    <Card style={styles.statTile}>
      <View style={[styles.statIcon, { backgroundColor: bg }]}>
        <Ionicons name={icon} size={24} color={color} />
      </View>
      <Text style={[styles.statValue, { color }]}>{value}</Text>
      <Text style={styles.statLabel}>{label}</Text>
    </Card>
  );
}

function SpaceRow({ space }: { space: Space }) {
  const initial = space.name.charAt(0).toUpperCase();
  return (
    <Card style={styles.spaceCard}>
      <View style={styles.spaceRow}>
        <View style={styles.spaceAvatar}>
          <Text style={styles.spaceAvatarText}>{initial}</Text>
        </View>
        <View style={{ flex: 1 }}>
          <Text style={styles.spaceName}>{space.name}</Text>
          {space.description ? (
            <Text style={styles.spaceDesc} numberOfLines={1}>
              {space.description}
            </Text>
          ) : null}
        </View>
        {space.user_role ? (
          <Badge label={roleShort(space.user_role)} variant={roleBadge(space.user_role)} />
        ) : null}
      </View>
    </Card>
  );
}

function roleShort(role: string): string {
  const map: Record<string, string> = {
    administrateur: 'Admin',
    gestionnaire_global: 'Gest. global',
    gestionnaire_produits: 'Gest. produits',
    gestionnaire_inventaires: 'Gest. inventaires',
    membre: 'Membre',
  };
  return map[role] ?? role;
}

function roleBadge(role: string): 'primary' | 'secondary' | 'neutral' {
  if (role === 'administrateur') return 'primary';
  if (role.startsWith('gestionnaire')) return 'secondary';
  return 'neutral';
}

const styles = StyleSheet.create({
  safe: { flex: 1, backgroundColor: colors.background },
  content: { padding: spacing.md, gap: spacing.md, paddingBottom: spacing.xl },

  greeting: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
    paddingVertical: spacing.sm,
  },
  greetingTitle: { ...typography.h2, color: colors.text },
  greetingSubtitle: { ...typography.bodySmall, color: colors.textSecondary, marginTop: 2 },
  roleTag: {
    backgroundColor: colors.primaryLight,
    paddingHorizontal: spacing.sm,
    paddingVertical: spacing.xs,
    borderRadius: borderRadius.full,
  },
  roleText: { ...typography.caption, color: colors.primary, fontWeight: '600' },

  statsRow: { flexDirection: 'row', gap: spacing.sm },
  statTile: {
    flex: 1,
    alignItems: 'center',
    gap: spacing.xs,
    paddingVertical: spacing.md,
  },
  statIcon: {
    width: 48,
    height: 48,
    borderRadius: 24,
    justifyContent: 'center',
    alignItems: 'center',
  },
  statValue: { fontSize: 26, fontWeight: '700' },
  statLabel: { ...typography.caption, color: colors.textSecondary },

  sectionTitle: { ...typography.h3, color: colors.text },

  emptyCard: { alignItems: 'center', gap: spacing.sm, paddingVertical: spacing.xl },
  emptyText: {
    ...typography.bodySmall,
    color: colors.textSecondary,
    textAlign: 'center',
    lineHeight: 22,
  },

  spaceCard: { marginBottom: 0 },
  spaceRow: { flexDirection: 'row', alignItems: 'center', gap: spacing.md },
  spaceAvatar: {
    width: 44,
    height: 44,
    borderRadius: 22,
    backgroundColor: colors.primaryLight,
    justifyContent: 'center',
    alignItems: 'center',
  },
  spaceAvatarText: { fontSize: 18, fontWeight: '700', color: colors.primary },
  spaceName: { ...typography.h3, color: colors.text },
  spaceDesc: { ...typography.bodySmall, color: colors.textSecondary, marginTop: 2 },
});
