import React, { useLayoutEffect, useState } from 'react';
import {
  View,
  Text,
  StyleSheet,
  FlatList,
  TouchableOpacity,
  Alert,
  RefreshControl,
  ScrollView,
  TextInput,
} from 'react-native';
import { useToast } from '../../components/Toast';
import { SafeAreaView } from 'react-native-safe-area-context';
import { NativeStackScreenProps } from '@react-navigation/native-stack';
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import { Ionicons } from '@expo/vector-icons';
import { SpacesStackParamList, InventoryItem } from '../../types';
import {
  getInventory,
  getExpiringItems,
  getExpiredItems,
  getCasseItems,
  deleteInventoryItem,
  decreaseInventoryItem,
  markAsCasse,
  unmarkCasse,
} from '../../api/inventory';
import { colors, spacing, typography, borderRadius, shadows } from '../../theme';
import LoadingView from '../../components/LoadingView';
import ErrorView from '../../components/ErrorView';
import EmptyState from '../../components/EmptyState';
import Badge from '../../components/Badge';

type Props = NativeStackScreenProps<SpacesStackParamList, 'Inventory'>;

type Tab = 'actif' | 'expirant' | 'perime' | 'casse';

const TABS: { key: Tab; label: string; icon: keyof typeof Ionicons.glyphMap }[] = [
  { key: 'actif',    label: 'Actif',     icon: 'cube-outline' },
  { key: 'expirant', label: 'Expirant',  icon: 'time-outline' },
  { key: 'perime',   label: 'Périmé',    icon: 'skull-outline' },
  { key: 'casse',    label: 'Casse',     icon: 'construct-outline' },
];

export default function InventoryScreen({ navigation, route }: Props) {
  const { spaceId } = route.params;
  const qc = useQueryClient();
  const { error: toastError } = useToast();
  const [activeTab, setActiveTab] = useState<Tab>('actif');
  const [search, setSearch] = useState('');

  const queries = {
    actif:    useQuery({ queryKey: ['inventory', spaceId, 'actif'],    queryFn: () => getInventory(spaceId) }),
    expirant: useQuery({ queryKey: ['inventory', spaceId, 'expirant'], queryFn: () => getExpiringItems(spaceId, 7) }),
    perime:   useQuery({ queryKey: ['inventory', spaceId, 'perime'],   queryFn: () => getExpiredItems(spaceId) }),
    casse:    useQuery({ queryKey: ['inventory', spaceId, 'casse'],    queryFn: () => getCasseItems(spaceId) }),
  };

  const current = queries[activeTab];
  const items: InventoryItem[] = current.data ?? [];

  const q = search.toLowerCase();
  const filtered = items.filter(
    (i) =>
      (i.product_name ?? '').toLowerCase().includes(q) ||
      (i.location_name ?? '').toLowerCase().includes(q),
  );

  const invalidate = () => {
    Object.keys(queries).forEach((tab) =>
      qc.invalidateQueries({ queryKey: ['inventory', spaceId, tab] }),
    );
  };

  const deleteMutation = useMutation({
    mutationFn: (id: number) => deleteInventoryItem(spaceId, id),
    onSuccess: invalidate,
    onError: (err: any) =>
      toastError(err?.response?.data?.message ?? 'Impossible de supprimer.'),
  });

  const decreaseMutation = useMutation({
    mutationFn: (id: number) => decreaseInventoryItem(spaceId, id, 1),
    onSuccess: invalidate,
    onError: (err: any) =>
      toastError(err?.response?.data?.message ?? 'Impossible de diminuer.'),
  });

  const casseMutation = useMutation({
    mutationFn: (id: number) => markAsCasse(spaceId, id),
    onSuccess: invalidate,
  });

  const uncasseMutation = useMutation({
    mutationFn: (id: number) => unmarkCasse(spaceId, id),
    onSuccess: invalidate,
  });

  useLayoutEffect(() => {
    navigation.setOptions({
      headerRight: () => (
        <TouchableOpacity
          onPress={() => navigation.navigate('InventoryCreate', { spaceId })}
          style={{ marginRight: spacing.md }}
          hitSlop={{ top: 10, bottom: 10, left: 10, right: 10 }}
        >
          <Ionicons name="add" size={26} color={colors.white} />
        </TouchableOpacity>
      ),
    });
  }, [navigation, spaceId]);

  const showItemActions = (item: InventoryItem) => {
    const options: Array<{ text: string; style?: 'cancel' | 'destructive'; onPress?: () => void }> = [
      { text: 'Annuler', style: 'cancel' },
    ];

    if (activeTab !== 'casse') {
      options.push({
        text: '−1 (diminuer)',
        onPress: () =>
          Alert.alert('Diminuer', 'Retirer 1 unité ?', [
            { text: 'Annuler', style: 'cancel' },
            { text: 'Confirmer', onPress: () => decreaseMutation.mutate(item.id) },
          ]),
      });
      options.push({
        text: 'Mettre en casse',
        onPress: () => casseMutation.mutate(item.id),
      });
    } else {
      options.push({
        text: 'Remettre en service',
        onPress: () => uncasseMutation.mutate(item.id),
      });
    }

    options.push({
      text: 'Modifier',
      onPress: () => navigation.navigate('InventoryEdit', { spaceId, itemId: item.id }),
    });
    options.push({
      text: 'Supprimer',
      style: 'destructive',
      onPress: () =>
        Alert.alert('Supprimer', `Supprimer ${item.product_name ?? 'cet article'} ?`, [
          { text: 'Annuler', style: 'cancel' },
          { text: 'Supprimer', style: 'destructive', onPress: () => deleteMutation.mutate(item.id) },
        ]),
    });

    Alert.alert(item.product_name ?? 'Article', `Qté : ${item.quantity}`, options);
  };

  const expiryBadge = (item: InventoryItem): { label: string; variant: 'danger' | 'warning' | 'neutral' } | null => {
    if (!item.expiry_date) return null;
    const days = Math.ceil((new Date(item.expiry_date).getTime() - Date.now()) / 86400000);
    if (days < 0) return { label: 'Périmé', variant: 'danger' };
    if (days <= 7) return { label: `J-${days}`, variant: 'warning' };
    return null;
  };

  if (current.isLoading) return <LoadingView />;
  if (current.isError) return <ErrorView onRetry={current.refetch} />;

  return (
    <SafeAreaView style={styles.safe} edges={['bottom', 'left', 'right']}>
      {/* Onglets */}
      <View style={styles.tabBar}>
        <ScrollView horizontal showsHorizontalScrollIndicator={false} contentContainerStyle={styles.tabContent}>
          {TABS.map(({ key, label, icon }) => {
            const isActive = activeTab === key;
            return (
              <TouchableOpacity
                key={key}
                style={[styles.tab, isActive && styles.tabActive]}
                onPress={() => { setActiveTab(key); setSearch(''); }}
                activeOpacity={0.75}
              >
                <Ionicons
                  name={icon}
                  size={15}
                  color={isActive ? colors.primary : colors.textSecondary}
                />
                <Text style={[styles.tabText, isActive && styles.tabTextActive]}>{label}</Text>
                {queries[key].data && (
                  <View style={[styles.tabBadge, isActive && styles.tabBadgeActive]}>
                    <Text style={[styles.tabBadgeText, isActive && styles.tabBadgeTextActive]}>
                      {queries[key].data!.length}
                    </Text>
                  </View>
                )}
              </TouchableOpacity>
            );
          })}
        </ScrollView>
      </View>

      {/* Recherche */}
      <View style={styles.searchBar}>
        <Ionicons name="search-outline" size={18} color={colors.textMuted} />
        <TextInput
          style={styles.searchInput}
          value={search}
          onChangeText={setSearch}
          placeholder="Rechercher un produit ou emplacement..."
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

      {/* Liste */}
      <FlatList
        data={filtered}
        keyExtractor={(item) => String(item.id)}
        contentContainerStyle={[styles.list, filtered.length === 0 && styles.listFlex]}
        refreshControl={
          <RefreshControl
            refreshing={current.isFetching && !current.isLoading}
            onRefresh={current.refetch}
            colors={[colors.primary]}
            tintColor={colors.primary}
          />
        }
        ListEmptyComponent={
          <EmptyState
            icon="cube-outline"
            title={search ? 'Aucun résultat' : 'Aucun article'}
            subtitle={
              search
                ? `Aucun article ne correspond à "${search}"`
                : activeTab === 'actif'
                ? 'Ajoutez des articles avec le bouton +'
                : 'Aucun article dans cette catégorie.'
            }
          />
        }
        renderItem={({ item }) => {
          const badge = expiryBadge(item);
          return (
            <TouchableOpacity
              style={styles.card}
              onPress={() => showItemActions(item)}
              activeOpacity={0.75}
            >
              {/* Quantité */}
              <View style={styles.qtyBox}>
                <Text style={styles.qtyNum}>{item.quantity}</Text>
                <Text style={styles.qtyUnit}>unités</Text>
              </View>

              {/* Info */}
              <View style={styles.info}>
                <Text style={styles.productName} numberOfLines={1}>
                  {item.product_name ?? `Produit #${item.product_id}`}
                </Text>
                <View style={styles.metaRow}>
                  <Ionicons name="location-outline" size={12} color={colors.textMuted} />
                  <Text style={styles.meta}>
                    {item.location_name ?? `Emplacement #${item.location_id}`}
                  </Text>
                </View>
                {item.expiry_date && (
                  <View style={styles.metaRow}>
                    <Ionicons name="calendar-outline" size={12} color={colors.textMuted} />
                    <Text style={styles.meta}>
                      Exp. {new Date(item.expiry_date).toLocaleDateString('fr-FR')}
                    </Text>
                  </View>
                )}
                {badge && (
                  <Badge label={badge.label} variant={badge.variant} style={{ marginTop: 4 }} />
                )}
                {item.is_casse && <Badge label="Casse" variant="danger" style={{ marginTop: 4 }} />}
              </View>

              <Ionicons name="ellipsis-vertical" size={18} color={colors.textMuted} />
            </TouchableOpacity>
          );
        }}
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
    marginHorizontal: spacing.md,
    marginTop: spacing.sm,
    marginBottom: 0,
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

  tabBar: {
    backgroundColor: colors.card,
    borderBottomWidth: 1,
    borderBottomColor: colors.border,
  },
  tabContent: { paddingHorizontal: spacing.md, paddingVertical: spacing.sm, gap: spacing.xs },
  tab: {
    flexDirection: 'row',
    alignItems: 'center',
    paddingHorizontal: spacing.sm,
    paddingVertical: spacing.xs,
    borderRadius: borderRadius.full,
    gap: 5,
    borderWidth: 1.5,
    borderColor: 'transparent',
  },
  tabActive: { borderColor: colors.primary, backgroundColor: colors.primaryLight },
  tabText: { ...typography.caption, color: colors.textSecondary, fontWeight: '500' },
  tabTextActive: { color: colors.primary, fontWeight: '600' },
  tabBadge: {
    minWidth: 18,
    height: 18,
    borderRadius: 9,
    backgroundColor: colors.border,
    justifyContent: 'center',
    alignItems: 'center',
    paddingHorizontal: 4,
  },
  tabBadgeActive: { backgroundColor: colors.primary },
  tabBadgeText: { fontSize: 10, fontWeight: '700', color: colors.textSecondary },
  tabBadgeTextActive: { color: colors.white },

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
  qtyBox: {
    minWidth: 46,
    alignItems: 'center',
    backgroundColor: colors.primaryLight,
    borderRadius: borderRadius.md,
    paddingVertical: spacing.xs,
    paddingHorizontal: spacing.xs,
    flexShrink: 0,
  },
  qtyNum: { fontSize: 20, fontWeight: '700', color: colors.primary },
  qtyUnit: { fontSize: 10, color: colors.primary, fontWeight: '500' },
  info: { flex: 1 },
  productName: { ...typography.h3, color: colors.text },
  metaRow: { flexDirection: 'row', alignItems: 'center', gap: 4, marginTop: 3 },
  meta: { ...typography.caption, color: colors.textSecondary },
});
