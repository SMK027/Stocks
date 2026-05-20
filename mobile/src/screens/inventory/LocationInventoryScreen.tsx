import React, { useLayoutEffect, useState } from 'react';
import {
  View,
  Text,
  StyleSheet,
  FlatList,
  TouchableOpacity,
  Alert,
  Modal,
  RefreshControl,
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
  deleteInventoryItem,
  decreaseInventoryItem,
  markAsCasse,
  updateInventoryItem,
} from '../../api/inventory';
import { colors, spacing, typography, borderRadius, shadows } from '../../theme';
import LoadingView from '../../components/LoadingView';
import ErrorView from '../../components/ErrorView';
import EmptyState from '../../components/EmptyState';
import Badge from '../../components/Badge';
import DatePickerField from '../../components/DatePickerField';
import Button from '../../components/Button';

type Props = NativeStackScreenProps<SpacesStackParamList, 'LocationInventory'>;

export default function LocationInventoryScreen({ navigation, route }: Props) {
  const { spaceId, locationId, locationName } = route.params;
  const qc = useQueryClient();
  const { error: toastError } = useToast();
  const [search, setSearch] = useState('');

  // ── Modals ────────────────────────────────────────────────────────────────
  const [dlcModalItem, setDlcModalItem] = useState<InventoryItem | null>(null);
  const [dlcDate, setDlcDate] = useState('');
  const [decreaseModalItem, setDecreaseModalItem] = useState<InventoryItem | null>(null);
  const [decreaseQty, setDecreaseQty] = useState('1');
  const [decreaseError, setDecreaseError] = useState<string | null>(null);

  // ── Données ───────────────────────────────────────────────────────────────
  const { data: allItems = [], isLoading, isError, refetch, isFetching } = useQuery({
    queryKey: ['inventory', spaceId, 'actif'],
    queryFn: () => getInventory(spaceId),
  });

  const items = allItems.filter((i) => i.location_id === locationId);

  const q = search.toLowerCase();
  const filtered = items.filter((i) =>
    (i.product_name ?? '').toLowerCase().includes(q),
  );

  const invalidate = () => {
    qc.invalidateQueries({ queryKey: ['inventory', spaceId] });
  };

  // ── Mutations ─────────────────────────────────────────────────────────────
  const deleteMutation = useMutation({
    mutationFn: (id: number) => deleteInventoryItem(spaceId, id),
    onSuccess: invalidate,
    onError: (err: any) =>
      toastError(err?.response?.data?.message ?? 'Impossible de supprimer.'),
  });

  const decreaseMutation = useMutation({
    mutationFn: ({ id, qty }: { id: number; qty: number }) =>
      decreaseInventoryItem(spaceId, id, qty),
    onSuccess: () => {
      invalidate();
      setDecreaseModalItem(null);
    },
    onError: (err: any) =>
      toastError(err?.response?.data?.message ?? 'Impossible de diminuer.'),
  });

  const casseMutation = useMutation({
    mutationFn: (id: number) => markAsCasse(spaceId, id),
    onSuccess: invalidate,
  });

  const dlcMutation = useMutation({
    mutationFn: ({ id, expiry_date }: { id: number; expiry_date: string | null }) =>
      updateInventoryItem(spaceId, id, { expiry_date }),
    onSuccess: () => {
      invalidate();
      setDlcModalItem(null);
    },
    onError: (err: any) =>
      toastError(err?.response?.data?.message ?? 'Impossible de modifier la DLC.'),
  });

  // ── Handlers ──────────────────────────────────────────────────────────────
  const handleDecreaseSubmit = () => {
    if (!decreaseModalItem) return;
    const qty = parseInt(decreaseQty, 10);
    if (isNaN(qty) || qty <= 0) {
      setDecreaseError('La quantité doit être un nombre positif.');
      return;
    }
    if (qty > decreaseModalItem.quantity) {
      setDecreaseError(`La quantité dépasse le stock disponible (${decreaseModalItem.quantity}).`);
      return;
    }
    setDecreaseError(null);
    decreaseMutation.mutate({ id: decreaseModalItem.id, qty });
  };

  const showItemActions = (item: InventoryItem) => {
    Alert.alert(
      item.product_name ?? 'Article',
      `Qté : ${item.quantity}`,
      [
        { text: 'Annuler', style: 'cancel' },
        {
          text: 'Diminuer la quantité',
          onPress: () => {
            setDecreaseQty('1');
            setDecreaseError(null);
            setDecreaseModalItem(item);
          },
        },
        {
          text: 'Mettre en casse',
          onPress: () => casseMutation.mutate(item.id),
        },
        {
          text: 'Modifier la DLC',
          onPress: () => {
            setDlcDate(item.expiry_date ?? '');
            setDlcModalItem(item);
          },
        },
        {
          text: 'Modifier',
          onPress: () => navigation.navigate('InventoryEdit', { spaceId, itemId: item.id }),
        },
        {
          text: 'Supprimer',
          style: 'destructive',
          onPress: () =>
            Alert.alert('Supprimer', `Supprimer ${item.product_name ?? 'cet article'} ?`, [
              { text: 'Annuler', style: 'cancel' },
              {
                text: 'Supprimer',
                style: 'destructive',
                onPress: () => deleteMutation.mutate(item.id),
              },
            ]),
        },
      ],
    );
  };

  // ── Header ────────────────────────────────────────────────────────────────
  useLayoutEffect(() => {
    navigation.setOptions({
      title: locationName,
      headerRight: () => (
        <TouchableOpacity
          onPress={() =>
            navigation.navigate('InventoryCreate', { spaceId, initialLocationId: locationId })
          }
          style={{ marginRight: spacing.md }}
          hitSlop={{ top: 10, bottom: 10, left: 10, right: 10 }}
        >
          <Ionicons name="add" size={26} color={colors.white} />
        </TouchableOpacity>
      ),
    });
  }, [navigation, spaceId, locationId, locationName]);

  // ── Badge DLC ─────────────────────────────────────────────────────────────
  const expiryBadge = (item: InventoryItem) => {
    if (!item.expiry_date) return null;
    const days = Math.ceil((new Date(item.expiry_date).getTime() - Date.now()) / 86400000);
    if (days < 0) return { label: 'Périmé', variant: 'danger' as const };
    if (days <= 7) return { label: `J-${days}`, variant: 'warning' as const };
    return null;
  };

  if (isLoading) return <LoadingView />;
  if (isError) return <ErrorView onRetry={refetch} />;

  return (
    <SafeAreaView style={styles.safe} edges={['bottom', 'left', 'right']}>
      {/* ── Modal diminution ─────────────────────────────────────────────── */}
      <Modal
        visible={decreaseModalItem !== null}
        transparent
        animationType="slide"
        onRequestClose={() => setDecreaseModalItem(null)}
      >
        <TouchableOpacity
          style={styles.modalOverlay}
          activeOpacity={1}
          onPress={() => setDecreaseModalItem(null)}
        >
          <TouchableOpacity activeOpacity={1} style={styles.modalSheet}>
            <View style={styles.modalHandle} />
            <Text style={styles.modalTitle} numberOfLines={1}>
              {decreaseModalItem?.product_name ?? 'Article'}
            </Text>
            <Text style={styles.modalSubtitle}>
              Stock actuel : {decreaseModalItem?.quantity} unité
              {(decreaseModalItem?.quantity ?? 0) > 1 ? 's' : ''}
            </Text>
            <View style={styles.decreaseInputRow}>
              <TouchableOpacity
                style={styles.decreaseStepBtn}
                onPress={() => {
                  const v = Math.max(1, parseInt(decreaseQty || '1', 10) - 1);
                  setDecreaseQty(String(v));
                  setDecreaseError(null);
                }}
              >
                <Ionicons name="remove" size={20} color={colors.primary} />
              </TouchableOpacity>
              <TextInput
                style={styles.decreaseInput}
                value={decreaseQty}
                onChangeText={(t) => { setDecreaseQty(t); setDecreaseError(null); }}
                keyboardType="numeric"
                selectTextOnFocus
                maxLength={6}
              />
              <TouchableOpacity
                style={styles.decreaseStepBtn}
                onPress={() => {
                  const v = parseInt(decreaseQty || '0', 10) + 1;
                  setDecreaseQty(String(v));
                  setDecreaseError(null);
                }}
              >
                <Ionicons name="add" size={20} color={colors.primary} />
              </TouchableOpacity>
            </View>
            {decreaseError && <Text style={styles.decreaseErrorText}>{decreaseError}</Text>}
            <View style={styles.modalActions}>
              <Button
                title="Annuler"
                onPress={() => setDecreaseModalItem(null)}
                variant="outline"
                style={{ flex: 1 }}
              />
              <Button
                title="Confirmer"
                onPress={handleDecreaseSubmit}
                loading={decreaseMutation.isPending}
                style={{ flex: 1 }}
              />
            </View>
          </TouchableOpacity>
        </TouchableOpacity>
      </Modal>

      {/* ── Modal DLC ────────────────────────────────────────────────────── */}
      <Modal
        visible={dlcModalItem !== null}
        transparent
        animationType="slide"
        onRequestClose={() => setDlcModalItem(null)}
      >
        <TouchableOpacity
          style={styles.modalOverlay}
          activeOpacity={1}
          onPress={() => setDlcModalItem(null)}
        >
          <TouchableOpacity activeOpacity={1} style={styles.modalSheet}>
            <View style={styles.modalHandle} />
            <Text style={styles.modalTitle} numberOfLines={1}>
              {dlcModalItem?.product_name ?? 'Article'}
            </Text>
            <Text style={styles.modalSubtitle}>Modifier la date limite de consommation</Text>
            <DatePickerField
              label="DLC"
              value={dlcDate}
              onChange={setDlcDate}
              placeholder="Aucune date (optionnel)"
              optional
              icon="time-outline"
            />
            <View style={styles.modalActions}>
              <Button
                title="Annuler"
                onPress={() => setDlcModalItem(null)}
                variant="outline"
                style={{ flex: 1 }}
              />
              <Button
                title="Enregistrer"
                onPress={() =>
                  dlcModalItem &&
                  dlcMutation.mutate({ id: dlcModalItem.id, expiry_date: dlcDate || null })
                }
                loading={dlcMutation.isPending}
                style={{ flex: 1 }}
              />
            </View>
          </TouchableOpacity>
        </TouchableOpacity>
      </Modal>

      {/* ── Recherche ────────────────────────────────────────────────────── */}
      <View style={styles.searchBar}>
        <Ionicons name="search-outline" size={18} color={colors.textMuted} />
        <TextInput
          style={styles.searchInput}
          value={search}
          onChangeText={setSearch}
          placeholder="Rechercher un produit..."
          placeholderTextColor={colors.placeholder}
          autoCorrect={false}
          clearButtonMode="while-editing"
        />
        {search.length > 0 && (
          <TouchableOpacity
            onPress={() => setSearch('')}
            hitSlop={{ top: 8, bottom: 8, left: 8, right: 8 }}
          >
            <Ionicons name="close-circle" size={18} color={colors.textMuted} />
          </TouchableOpacity>
        )}
      </View>

      {/* ── Liste ────────────────────────────────────────────────────────── */}
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
            icon="cube-outline"
            title={search ? 'Aucun résultat' : 'Aucun article'}
            subtitle={
              search
                ? `Aucun article ne correspond à "${search}"`
                : `Aucun article dans "${locationName}". Appuyez sur + pour en ajouter.`
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
              <View style={styles.qtyBox}>
                <Text style={styles.qtyNum}>{item.quantity}</Text>
                <Text style={styles.qtyUnit}>unités</Text>
              </View>
              <View style={styles.info}>
                <Text style={styles.productName} numberOfLines={1}>
                  {item.product_name ?? `Produit #${item.product_id}`}
                </Text>
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

  // ── Modals ────────────────────────────────────────────────────────────────
  modalOverlay: {
    flex: 1,
    backgroundColor: 'rgba(0,0,0,0.45)',
    justifyContent: 'flex-end',
  },
  modalSheet: {
    backgroundColor: colors.card,
    borderTopLeftRadius: 20,
    borderTopRightRadius: 20,
    padding: spacing.lg,
    gap: spacing.md,
    paddingBottom: spacing.xl,
  },
  modalHandle: {
    width: 40,
    height: 4,
    borderRadius: 2,
    backgroundColor: colors.border,
    alignSelf: 'center',
    marginBottom: spacing.sm,
  },
  modalTitle: { ...typography.h2, color: colors.text },
  modalSubtitle: {
    ...typography.body,
    color: colors.textSecondary,
    marginTop: -spacing.sm,
  },
  modalActions: {
    flexDirection: 'row',
    gap: spacing.md,
    marginTop: spacing.xs,
  },

  // ── Decrease ──────────────────────────────────────────────────────────────
  decreaseInputRow: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'center',
    gap: spacing.md,
  },
  decreaseStepBtn: {
    width: 44,
    height: 44,
    borderRadius: borderRadius.md,
    borderWidth: 1.5,
    borderColor: colors.primary,
    justifyContent: 'center',
    alignItems: 'center',
  },
  decreaseInput: {
    width: 80,
    height: 44,
    borderRadius: borderRadius.md,
    borderWidth: 1.5,
    borderColor: colors.border,
    textAlign: 'center',
    fontSize: 22,
    fontWeight: '700',
    color: colors.text,
  },
  decreaseErrorText: {
    fontSize: 13,
    color: colors.danger,
    textAlign: 'center',
  },
});
