import React, { useState, useEffect } from 'react';
import {
  View,
  Text,
  StyleSheet,
  ScrollView,
  KeyboardAvoidingView,
  Platform,
} from 'react-native';
import { Ionicons } from '@expo/vector-icons';
import { useToast } from '../../components/Toast';
import { SafeAreaView } from 'react-native-safe-area-context';
import { NativeStackScreenProps } from '@react-navigation/native-stack';
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { SpacesStackParamList } from '../../types';
import { upsertInventoryItem, updateInventoryItem, getInventoryItem } from '../../api/inventory';
import { getProducts } from '../../api/products';
import { getLocations } from '../../api/locations';
import { getCategories } from '../../api/categories';
import { colors, spacing } from '../../theme';
import Input from '../../components/Input';
import Button from '../../components/Button';
import LoadingView from '../../components/LoadingView';
import DatePickerField from '../../components/DatePickerField';
import AutocompleteField from '../../components/AutocompleteField';

type Props = NativeStackScreenProps<SpacesStackParamList, 'InventoryCreate' | 'InventoryEdit'>;

export default function InventoryFormScreen({ navigation, route }: Props) {
  const { spaceId } = route.params as { spaceId: number; itemId?: number; initialLocationId?: number };
  const itemId = (route.params as { itemId?: number }).itemId;
  const initialLocationId = (route.params as { initialLocationId?: number }).initialLocationId ?? null;
  const isEdit = itemId !== undefined;
  const qc = useQueryClient();
  const { error: toastError, warning: toastWarning } = useToast();

  const [productId, setProductId] = useState<number | null>(null);
  const [locationId, setLocationId] = useState<number | null>(initialLocationId);
  const [quantity, setQuantity] = useState('1');
  const [stockDate, setStockDate] = useState(new Date().toISOString().split('T')[0]);
  const [expiryDate, setExpiryDate] = useState('');
  const [isExpiryManual, setIsExpiryManual] = useState(false);
  const [dlcHint, setDlcHint] = useState<string | null>(null);

  const { data: products = [] } = useQuery({
    queryKey: ['products', spaceId],
    queryFn: () => getProducts(spaceId),
  });

  const { data: locations = [] } = useQuery({
    queryKey: ['locations', spaceId],
    queryFn: () => getLocations(spaceId),
  });

  const { data: categories = [] } = useQuery({
    queryKey: ['categories', spaceId],
    queryFn: () => getCategories(spaceId),
  });

  const { data: itemData, isLoading } = useQuery({
    queryKey: ['inventoryItem', spaceId, itemId],
    queryFn: () => getInventoryItem(spaceId, itemId!),
    enabled: isEdit,
  });

  useEffect(() => {
    if (itemData) {
      setProductId(itemData.product_id);
      setLocationId(itemData.location_id);
      setQuantity(String(itemData.quantity));
      setStockDate(itemData.stock_date);
      setExpiryDate(itemData.expiry_date ?? '');
    }
  }, [itemData]);

  const mutation = useMutation({
    mutationFn: () => {
      if (isEdit) {
        return updateInventoryItem(spaceId, itemId!, {
          product_id: productId!,
          location_id: locationId!,
          quantity: parseInt(quantity, 10),
          stock_date: stockDate,
          expiry_date: expiryDate || null,
        });
      }
      return upsertInventoryItem(spaceId, {
        product_id: productId!,
        location_id: locationId!,
        quantity: parseInt(quantity, 10),
        stock_date: stockDate,
        expiry_date: expiryDate || null,
      });
    },
    onSuccess: () => {
      qc.invalidateQueries({ queryKey: ['inventory', spaceId] });
      navigation.goBack();
    },
    onError: (err: any) =>
      toastError(err?.response?.data?.message ?? 'Une erreur est survenue.'),
  });

  // Réinitialise le flag "manuel" et déclenche le recalcul de DLC lors d'un changement de produit
  const handleProductChange = (id: number) => {
    if (!isEdit) {
      setIsExpiryManual(false);
    }
    setProductId(id);
  };

  // Suggestion automatique de DLC selon la catégorie du produit
  useEffect(() => {
    if (isEdit || isExpiryManual || !productId || !stockDate) {
      if (!productId) setDlcHint(null);
      return;
    }
    const product = products.find((p) => p.id === productId);
    if (!product?.category_ids?.length) {
      setDlcHint(null);
      return;
    }
    let minDays: number | null = null;
    for (const catId of product.category_ids) {
      const cat = categories.find((c) => c.id === catId);
      if (cat?.max_consumption_days && cat.max_consumption_days > 0) {
        if (minDays === null || cat.max_consumption_days < minDays) {
          minDays = cat.max_consumption_days;
        }
      }
    }
    if (minDays !== null) {
      const [y, m, d] = stockDate.split('-').map(Number);
      const suggested = new Date(y, m - 1, d);
      suggested.setDate(suggested.getDate() + minDays);
      const iso = `${suggested.getFullYear()}-${String(suggested.getMonth() + 1).padStart(2, '0')}-${String(suggested.getDate()).padStart(2, '0')}`;
      setExpiryDate(iso);
      setDlcHint(`Suggestion : ${minDays} jour${minDays > 1 ? 's' : ''} après la mise en stock`);
    } else {
      setDlcHint(null);
    }
  }, [productId, stockDate, products, categories, isEdit, isExpiryManual]);

  const handleExpiryChange = (val: string) => {
    setExpiryDate(val);
    setIsExpiryManual(true);
    setDlcHint(null);
  };

  const handleSubmit = () => {
    if (!productId) { toastWarning('Sélectionnez un produit.'); return; }
    if (!locationId) { toastWarning('Sélectionnez un emplacement.'); return; }
    const qty = parseInt(quantity, 10);
    if (isNaN(qty) || qty <= 0) { toastWarning('La quantité doit être un nombre positif.'); return; }
    mutation.mutate();
  };

  if (isEdit && isLoading) return <LoadingView />;

  return (
    <SafeAreaView style={styles.safe} edges={['bottom', 'left', 'right']}>
      <KeyboardAvoidingView
        style={{ flex: 1 }}
        behavior={Platform.OS === 'ios' ? 'padding' : 'height'}
      >
        <ScrollView contentContainerStyle={styles.content} keyboardShouldPersistTaps="handled">

          <AutocompleteField
            label="Produit *"
            items={products}
            value={productId}
            onChange={handleProductChange}
            placeholder="Rechercher un produit…"
            icon="barcode-outline"
            emptyMessage="Aucun produit – créez-en d'abord dans cet espace."
          />

          <AutocompleteField
            label="Emplacement *"
            items={locations}
            value={locationId}
            onChange={setLocationId}
            placeholder="Rechercher un emplacement…"
            icon="location-outline"
            emptyMessage="Aucun emplacement – créez-en d'abord dans cet espace."
            disabled={initialLocationId !== null && !isEdit}
          />

          <Input
            label="Quantité *"
            value={quantity}
            onChangeText={setQuantity}
            keyboardType="numeric"
            placeholder="1"
            leftIcon="layers-outline"
          />

          <DatePickerField
            label="Date de mise en stock"
            value={stockDate}
            onChange={setStockDate}
            icon="calendar-outline"
            maxDate={new Date()}
          />

          <DatePickerField
            label="Date limite de consommation"
            value={expiryDate}
            onChange={handleExpiryChange}
            placeholder="Sélectionner (optionnel)"
            optional
            icon="time-outline"
          />
          {dlcHint && (
            <View style={styles.hintRow}>
              <Ionicons name="information-circle-outline" size={14} color={colors.primary} />
              <Text style={styles.hintText}>{dlcHint}</Text>
            </View>
          )}

          <Button
            title={isEdit ? 'Enregistrer' : 'Ajouter au stock'}
            onPress={handleSubmit}
            loading={mutation.isPending}
            fullWidth
            icon={isEdit ? 'checkmark-outline' : 'add-outline'}
          />
        </ScrollView>
      </KeyboardAvoidingView>
    </SafeAreaView>
  );
}

const styles = StyleSheet.create({
  safe: { flex: 1, backgroundColor: colors.background },
  content: { padding: spacing.lg, gap: spacing.md },
  hintRow: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 4,
    marginTop: -spacing.sm + 2,
  },
  hintText: {
    fontSize: 12,
    color: colors.primary,
    fontStyle: 'italic',
    flexShrink: 1,
  },
});
