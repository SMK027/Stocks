import React, { useState, useEffect } from 'react';
import {
  View,
  Text,
  StyleSheet,
  ScrollView,
  Alert,
  KeyboardAvoidingView,
  Platform,
  TouchableOpacity,
} from 'react-native';
import { useToast } from '../../components/Toast';
import { SafeAreaView } from 'react-native-safe-area-context';
import { NativeStackScreenProps } from '@react-navigation/native-stack';
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { Ionicons } from '@expo/vector-icons';
import { SpacesStackParamList } from '../../types';
import { upsertInventoryItem, updateInventoryItem, getInventoryItem } from '../../api/inventory';
import { getProducts } from '../../api/products';
import { getLocations } from '../../api/locations';
import { colors, spacing, typography, borderRadius } from '../../theme';
import Input from '../../components/Input';
import Button from '../../components/Button';
import LoadingView from '../../components/LoadingView';
import DatePickerField from '../../components/DatePickerField';

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

  const selectedProduct = products.find((p) => p.id === productId);
  const selectedLocation = locations.find((l) => l.id === locationId);

  const showProductPicker = () => {
    if (products.length === 0) {
      Alert.alert('Aucun produit', "Créez d'abord des produits dans cet espace.");
      return;
    }
    Alert.alert(
      'Choisir un produit',
      undefined,
      [
        ...products.map((p) => ({ text: p.name, onPress: () => setProductId(p.id) })),
        { text: 'Annuler', style: 'cancel' as const },
      ],
    );
  };

  const showLocationPicker = () => {
    if (locations.length === 0) {
      Alert.alert('Aucun emplacement', "Créez d'abord des emplacements dans cet espace.");
      return;
    }
    Alert.alert(
      'Choisir un emplacement',
      undefined,
      [
        ...locations.map((l) => ({ text: l.name, onPress: () => setLocationId(l.id) })),
        { text: 'Annuler', style: 'cancel' as const },
      ],
    );
  };

  return (
    <SafeAreaView style={styles.safe} edges={['bottom', 'left', 'right']}>
      <KeyboardAvoidingView
        style={{ flex: 1 }}
        behavior={Platform.OS === 'ios' ? 'padding' : 'height'}
      >
        <ScrollView contentContainerStyle={styles.content} keyboardShouldPersistTaps="handled">

          {/* Sélecteur produit */}
          <View>
            <Text style={styles.label}>Produit *</Text>
            <TouchableOpacity style={styles.picker} onPress={showProductPicker} activeOpacity={0.75}>
              <Ionicons name="barcode-outline" size={18} color={colors.textSecondary} />
              <Text style={[styles.pickerText, !selectedProduct && styles.pickerPlaceholder]}>
                {selectedProduct ? selectedProduct.name : 'Sélectionner un produit'}
              </Text>
              <Ionicons name="chevron-down" size={16} color={colors.textMuted} />
            </TouchableOpacity>
          </View>

          {/* Sélecteur emplacement */}
          <View>
            <Text style={styles.label}>Emplacement *</Text>
            <TouchableOpacity style={styles.picker} onPress={showLocationPicker} activeOpacity={0.75}>
              <Ionicons name="location-outline" size={18} color={colors.textSecondary} />
              <Text style={[styles.pickerText, !selectedLocation && styles.pickerPlaceholder]}>
                {selectedLocation ? selectedLocation.name : 'Sélectionner un emplacement'}
              </Text>
              <Ionicons name="chevron-down" size={16} color={colors.textMuted} />
            </TouchableOpacity>
          </View>

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
  label: { ...typography.label, color: colors.text, marginBottom: spacing.xs },
  picker: {
    flexDirection: 'row',
    alignItems: 'center',
    backgroundColor: colors.white,
    borderWidth: 1.5,
    borderColor: colors.border,
    borderRadius: borderRadius.md,
    paddingHorizontal: spacing.md,
    height: 50,
    gap: spacing.sm,
  },
  pickerText: { flex: 1, ...typography.body, color: colors.text },
  pickerPlaceholder: { color: colors.placeholder },
});
