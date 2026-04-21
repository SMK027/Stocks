import React, { useLayoutEffect } from 'react';
import {
  View,
  Text,
  StyleSheet,
  FlatList,
  TouchableOpacity,
  Alert,
  RefreshControl,
  TextInput,
} from 'react-native';
import { useToast } from '../../components/Toast';
import { SafeAreaView } from 'react-native-safe-area-context';
import { NativeStackScreenProps } from '@react-navigation/native-stack';
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import { Ionicons } from '@expo/vector-icons';
import { SpacesStackParamList, Space } from '../../types';
import { getSpaces, deleteSpace } from '../../api/spaces';
import { colors, spacing, typography, borderRadius, shadows } from '../../theme';
import LoadingView from '../../components/LoadingView';
import ErrorView from '../../components/ErrorView';
import EmptyState from '../../components/EmptyState';
import Badge from '../../components/Badge';

type Props = NativeStackScreenProps<SpacesStackParamList, 'SpaceList'>;

export default function SpaceListScreen({ navigation }: Props) {
  const qc = useQueryClient();
  const { error: toastError } = useToast();
  const [search, setSearch] = React.useState('');
  const { data: spaces = [], isLoading, isError, refetch, isFetching } = useQuery({
    queryKey: ['spaces'],
    queryFn: getSpaces,
  });

  const deleteMutation = useMutation({
    mutationFn: deleteSpace,
    onSuccess: () => qc.invalidateQueries({ queryKey: ['spaces'] }),
    onError: (err: any) =>
      toastError(err?.response?.data?.message ?? 'Impossible de supprimer.'),
  });

  useLayoutEffect(() => {
    navigation.setOptions({
      headerRight: () => (
        <TouchableOpacity
          onPress={() => navigation.navigate('SpaceCreate')}
          style={{ marginRight: spacing.md }}
          hitSlop={{ top: 10, bottom: 10, left: 10, right: 10 }}
        >
          <Ionicons name="add" size={26} color={colors.white} />
        </TouchableOpacity>
      ),
    });
  }, [navigation]);

  const confirmDelete = (space: Space) =>
    Alert.alert(
      'Supprimer',
      `Supprimer l'espace "${space.name}" ? Cette action est irréversible.`,
      [
        { text: 'Annuler', style: 'cancel' },
        {
          text: 'Supprimer',
          style: 'destructive',
          onPress: () => deleteMutation.mutate(space.id),
        },
      ],
    );

  if (isLoading) return <LoadingView />;
  if (isError) return <ErrorView onRetry={refetch} />;

  const q = search.toLowerCase();
  const filtered = spaces.filter(
    (s) => s.name.toLowerCase().includes(q) || (s.description ?? '').toLowerCase().includes(q),
  );

  return (
    <SafeAreaView style={styles.safe} edges={['bottom', 'left', 'right']}>
      <View style={styles.searchBar}>
        <Ionicons name="search-outline" size={18} color={colors.textMuted} />
        <TextInput
          style={styles.searchInput}
          value={search}
          onChangeText={setSearch}
          placeholder="Rechercher un espace..."
          placeholderTextColor={colors.placeholder}
          autoCorrect={false}
          clearButtonMode="while-editing"
        />
        {search.length > 0 && (
          <TouchableOpacity onPress={() => setSearch('')} hitSlop={{ top: 8, bottom: 8, left: 8, right: 8 }}>
            <Ionicons name="close-circle" size={18} color={colors.textMuted} />
          </TouchableOpacity>
        )}
      </View>
      <FlatList
        data={filtered}
        keyExtractor={(item) => String(item.id)}
        contentContainerStyle={[styles.list, filtered.length === 0 && styles.listFlex]}
        refreshControl={
          <RefreshControl refreshing={isFetching && !isLoading} onRefresh={refetch} colors={[colors.primary]} tintColor={colors.primary} />
        }
        ListEmptyComponent={
          <EmptyState
            icon="layers-outline"
            title={search ? 'Aucun résultat' : 'Aucun espace'}
            subtitle={search ? `Aucun espace ne correspond à "${search}"` : 'Créez votre premier espace avec le bouton +'}
          />
        }
        renderItem={({ item }) => (
          <TouchableOpacity
            style={styles.card}
            onPress={() =>
              navigation.navigate('SpaceDetail', {
                spaceId: item.id,
                spaceName: item.name,
              })
            }
            activeOpacity={0.75}
          >
            <View style={styles.avatar}>
              <Text style={styles.avatarText}>{item.name.charAt(0).toUpperCase()}</Text>
            </View>
            <View style={styles.info}>
              <Text style={styles.name}>{item.name}</Text>
              {item.description ? (
                <Text style={styles.desc} numberOfLines={1}>
                  {item.description}
                </Text>
              ) : null}
              {item.user_role ? (
                <Badge
                  label={roleLabel(item.user_role)}
                  variant={roleBadge(item.user_role)}
                  style={{ marginTop: 4 }}
                />
              ) : null}
            </View>
            <View style={styles.actions}>
              <TouchableOpacity
                onPress={() => navigation.navigate('SpaceEdit', { spaceId: item.id })}
                hitSlop={{ top: 8, bottom: 8, left: 8, right: 8 }}
              >
                <Ionicons name="pencil-outline" size={20} color={colors.primary} />
              </TouchableOpacity>
              {item.user_role === 'administrateur' && (
                <TouchableOpacity
                  onPress={() => confirmDelete(item)}
                  hitSlop={{ top: 8, bottom: 8, left: 8, right: 8 }}
                >
                  <Ionicons name="trash-outline" size={20} color={colors.danger} />
                </TouchableOpacity>
              )}
            </View>
          </TouchableOpacity>
        )}
      />
    </SafeAreaView>
  );
}

function roleLabel(role: string): string {
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
  searchBar: {
    flexDirection: 'row',
    alignItems: 'center',
    backgroundColor: colors.card,
    margin: spacing.md,
    marginBottom: spacing.sm,
    borderRadius: borderRadius.lg,
    paddingHorizontal: spacing.md,
    paddingVertical: spacing.sm,
    gap: spacing.sm,
    shadowColor: '#000',
    shadowOffset: { width: 0, height: 1 },
    shadowOpacity: 0.06,
    shadowRadius: 3,
    elevation: 2,
  },
  searchInput: {
    flex: 1,
    fontSize: 15,
    color: colors.text,
    paddingVertical: 0,
  },
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
    width: 46,
    height: 46,
    borderRadius: 23,
    backgroundColor: colors.primaryLight,
    justifyContent: 'center',
    alignItems: 'center',
    flexShrink: 0,
  },
  avatarText: { fontSize: 20, fontWeight: '700', color: colors.primary },
  info: { flex: 1 },
  name: { ...typography.h3, color: colors.text },
  desc: { ...typography.bodySmall, color: colors.textSecondary, marginTop: 2 },
  actions: { flexDirection: 'row', gap: spacing.md, flexShrink: 0 },
});
