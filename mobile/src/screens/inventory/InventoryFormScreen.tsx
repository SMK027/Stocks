import React, { useState, useEffect } from 'react';
import {
  View,
  StyleSheet,
  ScrollView,
  KeyboardAvoidingView,
  Platform,
} from 'react-native';
import { useToast } from '../../components/Toast';
import { SafeAreaView } from 'react-native-safe-area-context';
import { NativeStackScreenProps } from '@react-navigation/native-stack';
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { SpacesStackParamList } from '../../types';
import { upsertInventoryItem, updateInventoryItem, getInventoryItem } from '../../api/inventory';
import { getProducts } from '../../api/products';
import { getLocations } from '../../api/locations';
import { colors, spacing } from '../../theme';
import Input from '../../components/Input';
import Button from '../../components/Button';
import LoadingView from '../../components/LoadingView';
import DatePickerField from '../../components/DatePickerField';
import AutocompleteField from '../../components/AutocompleteField';

type Props = NativeStackScreenProps<SpacesStackParamList, 'InventoryCreate' | 'InventoryEdit'>;

export default function InventoryFormScreen({ navigation, route }: Props) {
  const { spaceId } = route.params as { spaceId: number; itemId?: number };
  const itemId = (route.params as { itemId?: number }).itemId;
  const isEdit = itemId !== undefined;
  const qc = useQueryClient();
  const { error: toastError, warning: toastWarning } = useToast();

  const [productId, setProductId] = useState<number | null>(null);
  const [locationId, setLocationId] = useState<number | null>(null);
  const [quantity, setQuantity] = useState('1');
  const [stockDate, setStockDate] = useState(new Date().toISOString().split('T')[0]);
  const [expiryDate, setExpiryDate] = useState('');

  const { data: products = [] } = useQuery({
    queryKey: ['products', spaceId],
    queryFn: () => getProducts(spaceId),
  });

  const { data: locations = [] } = useQuery({
    queryKey: ['locations', spaceId],
    queryFn: () => getLocations(spaceId),
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
            onChange={setProductId}
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
            onChange={setExpiryDate}
            placeholder="Sélectionner (optionnel)"
            optional
            icon="time-outline"
          />

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
});
