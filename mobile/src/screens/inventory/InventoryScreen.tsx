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
  updateInventoryItem,
} from '../../api/inventory';
import { colors, spacing, typography, borderRadius, shadows } from '../../theme';
import LoadingView from '../../components/LoadingView';
import ErrorView from '../../components/ErrorView';
import EmptyState from '../../components/EmptyState';
import Badge from '../../components/Badge';
import DatePickerField from '../../components/DatePickerField';
import Button from '../../components/Button';

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
  const [dlcModalItem, setDlcModalItem] = useState<InventoryItem | null>(null);
  const [dlcDate, setDlcDate] = useState('');
  const [decreaseModalItem, setDecreaseModalItem] = useState<InventoryItem | null>(null);
  const [decreaseQty, setDecreaseQty] = useState('1');
  const [decreaseError, setDecreaseError] = useState<string | null>(null);
  const [actionSheetItem, setActionSheetItem] = useState<InventoryItem | null>(null);

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
    mutationFn: ({ id, qty }: { id: number; qty: number }) =>
      decreaseInventoryItem(spaceId, id, qty),
    onSuccess: () => {
      invalidate();
      setDecreaseModalItem(null);
    },
    onError: (err: any) =>
      toastError(err?.response?.data?.message ?? 'Impossible de diminuer.'),
  });

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

  const casseMutation = useMutation({
    mutationFn: (id: number) => markAsCasse(spaceId, id),
    onSuccess: invalidate,
  });

  const uncasseMutation = useMutation({
    mutationFn: (id: number) => unmarkCasse(spaceId, id),
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
    setActionSheetItem(item);
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
      {/* ── Bottom-sheet menu d'actions ──────────────────────────────────── */}
      <Modal
        visible={actionSheetItem !== null}
        transparent
        animationType="slide"
        onRequestClose={() => setActionSheetItem(null)}
      >
        <TouchableOpacity
          style={styles.modalOverlay}
          activeOpacity={1}
          onPress={() => setActionSheetItem(null)}
        >
          <TouchableOpacity activeOpacity={1} style={styles.actionSheet}>
            <View style={styles.modalHandle} />
            <Text style={styles.actionSheetTitle} numberOfLines={1}>
              {actionSheetItem?.product_name ?? 'Article'}
            </Text>
            <Text style={styles.actionSheetSubtitle}>
              Qté : {actionSheetItem?.quantity}
            </Text>

            {activeTab !== 'casse' ? (
              <>
                <TouchableOpacity
                  style={styles.actionRow}
                  onPress={() => {
                    setActionSheetItem(null);
                    setDecreaseQty('1');
                    setDecreaseError(null);
                    setDecreaseModalItem(actionSheetItem);
                  }}
                >
                  <Ionicons name="remove-circle-outline" size={22} color={colors.text} />
                  <Text style={styles.actionLabel}>Diminuer la quantité</Text>
                </TouchableOpacity>
                <TouchableOpacity
                  style={styles.actionRow}
                  onPress={() => {
                    setActionSheetItem(null);
                    actionSheetItem && casseMutation.mutate(actionSheetItem.id);
                  }}
                >
                  <Ionicons name="construct-outline" size={22} color={colors.text} />
                  <Text style={styles.actionLabel}>Mettre en casse</Text>
                </TouchableOpacity>
              </>
            ) : (
              <TouchableOpacity
                style={styles.actionRow}
                onPress={() => {
                  setActionSheetItem(null);
                  actionSheetItem && uncasseMutation.mutate(actionSheetItem.id);
                }}
              >
                <Ionicons name="refresh-outline" size={22} color={colors.text} />
                <Text style={styles.actionLabel}>Remettre en service</Text>
              </TouchableOpacity>
            )}

            <TouchableOpacity
              style={styles.actionRow}
              onPress={() => {
                setActionSheetItem(null);
                if (actionSheetItem) {
                  setDlcDate(actionSheetItem.expiry_date ?? '');
                  setDlcModalItem(actionSheetItem);
                }
              }}
            >
              <Ionicons name="time-outline" size={22} color={colors.text} />
              <Text style={styles.actionLabel}>Modifier la DLC</Text>
            </TouchableOpacity>

            <TouchableOpacity
              style={styles.actionRow}
              onPress={() => {
                setActionSheetItem(null);
                actionSheetItem && navigation.navigate('InventoryEdit', { spaceId, itemId: actionSheetItem.id });
              }}
            >
              <Ionicons name="pencil-outline" size={22} color={colors.text} />
              <Text style={styles.actionLabel}>Modifier</Text>
            </TouchableOpacity>

            <View style={styles.actionSeparator} />

            <TouchableOpacity
              style={styles.actionRow}
              onPress={() => {
                setActionSheetItem(null);
                if (actionSheetItem) {
                  Alert.alert(
                    'Supprimer',
                    `Supprimer ${actionSheetItem.product_name ?? 'cet article'} ?`,
                    [
                      { text: 'Annuler', style: 'cancel' },
                      { text: 'Supprimer', style: 'destructive', onPress: () => deleteMutation.mutate(actionSheetItem.id) },
                    ],
                  );
                }
              }}
            >
              <Ionicons name="trash-outline" size={22} color={colors.danger} />
              <Text style={[styles.actionLabel, { color: colors.danger }]}>Supprimer</Text>
            </TouchableOpacity>

            <TouchableOpacity
              style={[styles.actionRow, styles.actionCancel]}
              onPress={() => setActionSheetItem(null)}
            >
              <Text style={styles.actionCancelLabel}>Annuler</Text>
            </TouchableOpacity>
          </TouchableOpacity>
        </TouchableOpacity>
      </Modal>

      {/* ── Modal diminution quantité ────────────────────────────────────── */}
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
              Stock actuel : {decreaseModalItem?.quantity} unité{(decreaseModalItem?.quantity ?? 0) > 1 ? 's' : ''}
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
            {decreaseError && (
              <Text style={styles.decreaseErrorText}>{decreaseError}</Text>
            )}
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

      {/* Modal modification DLC */}
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
  modalTitle: {
    ...typography.h2,
    color: colors.text,
  },
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
    color: colors.danger ?? '#ef4444',
    textAlign: 'center',
  },

  // ── Action sheet ──────────────────────────────────────────────────────────
  actionSheet: {
    backgroundColor: colors.card,
    borderTopLeftRadius: 20,
    borderTopRightRadius: 20,
    paddingBottom: spacing.xl,
    paddingHorizontal: spacing.lg,
    paddingTop: spacing.sm,
    gap: 2,
  },
  actionSheetTitle: {
    ...typography.h3,
    color: colors.text,
    paddingVertical: spacing.xs,
  },
  actionSheetSubtitle: {
    ...typography.bodySmall,
    color: colors.textSecondary,
    marginBottom: spacing.xs,
  },
  actionRow: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: spacing.md,
    paddingVertical: 14,
    borderBottomWidth: 1,
    borderBottomColor: colors.border,
  },
  actionLabel: {
    ...typography.body,
    color: colors.text,
  },
  actionSeparator: {
    height: spacing.sm,
  },
  actionCancel: {
    justifyContent: 'center',
    borderBottomWidth: 0,
    marginTop: spacing.xs,
  },
  actionCancelLabel: {
    ...typography.body,
    color: colors.textSecondary,
    fontWeight: '600',
    textAlign: 'center',
    flex: 1,
  },

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
