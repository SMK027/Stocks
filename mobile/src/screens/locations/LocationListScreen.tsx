import React, { useLayoutEffect, useState } from 'react';
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
import { SpacesStackParamList, Location } from '../../types';
import { getLocations, deleteLocation } from '../../api/locations';
import { colors, spacing, typography, borderRadius, shadows } from '../../theme';
import LoadingView from '../../components/LoadingView';
import ErrorView from '../../components/ErrorView';
import EmptyState from '../../components/EmptyState';

type Props = NativeStackScreenProps<SpacesStackParamList, 'LocationList'>;

export default function LocationListScreen({ navigation, route }: Props) {
  const { spaceId } = route.params;
  const qc = useQueryClient();
  const { error: toastError } = useToast();
  const [search, setSearch] = useState('');

  const { data: locations = [], isLoading, isError, refetch, isFetching } = useQuery({
    queryKey: ['locations', spaceId],
    queryFn: () => getLocations(spaceId),
  });

  const deleteMutation = useMutation({
    mutationFn: (id: number) => deleteLocation(spaceId, id),
    onSuccess: () => qc.invalidateQueries({ queryKey: ['locations', spaceId] }),
    onError: (err: any) =>
      toastError(err?.response?.data?.message ?? 'Impossible de supprimer.'),
  });

  useLayoutEffect(() => {
    navigation.setOptions({
      headerRight: () => (
        <TouchableOpacity
          onPress={() => navigation.navigate('LocationCreate', { spaceId })}
          style={{ marginRight: spacing.md }}
          hitSlop={{ top: 10, bottom: 10, left: 10, right: 10 }}
        >
          <Ionicons name="add" size={26} color={colors.white} />
        </TouchableOpacity>
      ),
    });
  }, [navigation, spaceId]);

  const confirmDelete = (loc: Location) =>
    Alert.alert('Supprimer', `Supprimer l'emplacement "${loc.name}" ?`, [
      { text: 'Annuler', style: 'cancel' },
      { text: 'Supprimer', style: 'destructive', onPress: () => deleteMutation.mutate(loc.id) },
    ]);

  if (isLoading) return <LoadingView />;
  if (isError) return <ErrorView onRetry={refetch} />;

  const q = search.toLowerCase();
  const filtered = locations.filter(
    (l) => l.name.toLowerCase().includes(q) || (l.description ?? '').toLowerCase().includes(q),
  );

  return (
    <SafeAreaView style={styles.safe} edges={['bottom', 'left', 'right']}>
      <View style={styles.searchBar}>
        <Ionicons name="search-outline" size={18} color={colors.textMuted} />
        <TextInput
          style={styles.searchInput}
          value={search}
          onChangeText={setSearch}
          placeholder="Rechercher un emplacement..."
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
          <RefreshControl
            refreshing={isFetching && !isLoading}
            onRefresh={refetch}
            colors={[colors.primary]}
            tintColor={colors.primary}
          />
        }
        ListEmptyComponent={
          <EmptyState
            icon="location-outline"
            title={search ? 'Aucun résultat' : 'Aucun emplacement'}
            subtitle={search ? `Aucun emplacement ne correspond à "${search}"` : "Créez votre premier emplacement avec le bouton +"}
          />
        }
        renderItem={({ item }) => (
          <TouchableOpacity
            style={styles.card}
            onPress={() =>
              navigation.navigate('LocationInventory', {
                spaceId,
                locationId: item.id,
                locationName: item.name,
              })
            }
            activeOpacity={0.75}
          >
            <View style={styles.iconBg}>
              <Ionicons name="location" size={20} color={colors.info} />
            </View>
            <View style={styles.info}>
              <Text style={styles.name}>{item.name}</Text>
              {item.description ? (
                <Text style={styles.desc} numberOfLines={1}>{item.description}</Text>
              ) : null}
            </View>
            <View style={styles.actions}>
              <TouchableOpacity
                onPress={() => navigation.navigate('LocationEdit', { spaceId, locationId: item.id })}
                hitSlop={{ top: 8, bottom: 8, left: 8, right: 8 }}
              >
                <Ionicons name="pencil-outline" size={20} color={colors.primary} />
              </TouchableOpacity>
              <TouchableOpacity
                onPress={() => confirmDelete(item)}
                hitSlop={{ top: 8, bottom: 8, left: 8, right: 8 }}
              >
                <Ionicons name="trash-outline" size={20} color={colors.danger} />
              </TouchableOpacity>
            </View>
          </TouchableOpacity>
        )}
      />
    </SafeAreaView>
  );
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
  iconBg: {
    width: 40,
    height: 40,
    borderRadius: borderRadius.md,
    backgroundColor: colors.infoLight,
    justifyContent: 'center',
    alignItems: 'center',
    flexShrink: 0,
  },
  info: { flex: 1 },
  name: { ...typography.h3, color: colors.text },
  desc: { ...typography.bodySmall, color: colors.textSecondary, marginTop: 2 },
  actions: { flexDirection: 'row', gap: spacing.md, flexShrink: 0 },
});
