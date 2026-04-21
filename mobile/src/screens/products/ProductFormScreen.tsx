import React, { useState, useEffect } from 'react';
import {
  View,
  Text,
  StyleSheet,
  ScrollView,
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
import { createProduct, updateProduct, getProduct } from '../../api/products';
import { getCategories } from '../../api/categories';
import { colors, spacing, typography, borderRadius } from '../../theme';
import Input from '../../components/Input';
import Button from '../../components/Button';
import LoadingView from '../../components/LoadingView';

type Props = NativeStackScreenProps<SpacesStackParamList, 'ProductCreate' | 'ProductEdit'>;

export default function ProductFormScreen({ navigation, route }: Props) {
  const { spaceId } = route.params as { spaceId: number; productId?: number };
  const productId = (route.params as { productId?: number }).productId;
  const isEdit = productId !== undefined;
  const qc = useQueryClient();
  const { error: toastError, warning: toastWarning } = useToast();

  const [name, setName] = useState('');
  const [description, setDescription] = useState('');
  const [selectedCategoryIds, setSelectedCategoryIds] = useState<number[]>([]);

  const { data: productData, isLoading: loadingProduct } = useQuery({
    queryKey: ['product', spaceId, productId],
    queryFn: () => getProduct(spaceId, productId!),
    enabled: isEdit,
  });

  const { data: categories = [] } = useQuery({
    queryKey: ['categories', spaceId],
    queryFn: () => getCategories(spaceId),
  });

  useEffect(() => {
    if (productData) {
      setName(productData.name);
      setDescription(productData.description ?? '');
      if (productData.categories) {
        setSelectedCategoryIds(productData.categories.map((c) => c.id));
      }
    }
  }, [productData]);

  const toggleCategory = (id: number) => {
    setSelectedCategoryIds((prev) =>
      prev.includes(id) ? prev.filter((x) => x !== id) : [...prev, id],
    );
  };

  const mutation = useMutation({
    mutationFn: () => {
      const payload = {
        name: name.trim(),
        description: description.trim() || undefined,
        category_ids: selectedCategoryIds,
      };
      return isEdit
        ? updateProduct(spaceId, productId!, payload)
        : createProduct(spaceId, payload);
    },
    onSuccess: () => {
      qc.invalidateQueries({ queryKey: ['products', spaceId] });
      navigation.goBack();
    },
    onError: (err: any) =>
      toastError(err?.response?.data?.message ?? 'Une erreur est survenue.'),
  });

  if (isEdit && loadingProduct) return <LoadingView />;

  return (
    <SafeAreaView style={styles.safe} edges={['bottom', 'left', 'right']}>
      <KeyboardAvoidingView
        style={{ flex: 1 }}
        behavior={Platform.OS === 'ios' ? 'padding' : 'height'}
      >
        <ScrollView contentContainerStyle={styles.content} keyboardShouldPersistTaps="handled">
          <Input
            label="Nom *"
            value={name}
            onChangeText={setName}
            placeholder="Nom du produit"
            autoFocus={!isEdit}
          />
          <Input
            label="Description"
            value={description}
            onChangeText={setDescription}
            placeholder="Description (optionnel)"
            multiline
            numberOfLines={3}
          />

          {categories.length > 0 && (
            <View style={styles.catSection}>
              <Text style={styles.catLabel}>Catégories</Text>
              <View style={styles.catList}>
                {categories.map((cat) => {
                  const selected = selectedCategoryIds.includes(cat.id);
                  return (
                    <TouchableOpacity
                      key={cat.id}
                      style={[styles.catChip, selected && styles.catChipSelected]}
                      onPress={() => toggleCategory(cat.id)}
                      activeOpacity={0.75}
                    >
                      {selected && (
                        <Ionicons
                          name="checkmark"
                          size={13}
                          color={colors.warning}
                          style={{ marginRight: 4 }}
                        />
                      )}
                      <Text
                        style={[styles.catChipText, selected && styles.catChipTextSelected]}
                      >
                        {cat.name}
                      </Text>
                    </TouchableOpacity>
                  );
                })}
              </View>
            </View>
          )}

          <Button
            title={isEdit ? 'Enregistrer' : 'Créer le produit'}
            onPress={() => {
              if (!name.trim()) {
                toastWarning('Le nom est obligatoire.');
                return;
              }
              mutation.mutate();
            }}
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
  catSection: { gap: spacing.xs },
  catLabel: { ...typography.label, color: colors.text },
  catList: { flexDirection: 'row', flexWrap: 'wrap', gap: spacing.xs },
  catChip: {
    flexDirection: 'row',
    alignItems: 'center',
    paddingHorizontal: spacing.sm,
    paddingVertical: spacing.xs,
    borderRadius: borderRadius.full,
    borderWidth: 1.5,
    borderColor: colors.border,
    backgroundColor: colors.background,
  },
  catChipSelected: {
    borderColor: colors.warning,
    backgroundColor: colors.warningLight,
  },
  catChipText: { ...typography.caption, color: colors.textSecondary, fontWeight: '500' },
  catChipTextSelected: { color: colors.warning, fontWeight: '600' },
});
